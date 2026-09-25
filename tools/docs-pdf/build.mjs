/**
 * Membangun dokumentasi SIAKAD (docs/*.md) menjadi PDF di docs/pdf/.
 *
 *   cd tools/docs-pdf && npm install && npx playwright install chromium && npm run build
 *
 * Menghasilkan satu PDF per dokumen + satu PDF gabungan, lengkap dengan sampul,
 * daftar isi bernomor halaman, diagram Mermaid, dan tangkapan layar.
 * Variabel CHROMIUM_PATH dapat diisi bila Chromium tidak ditemukan otomatis.
 */
import fs from 'node:fs';
import path from 'node:path';
import { fileURLToPath, pathToFileURL } from 'node:url';
import { Marked } from 'marked';
import { chromium } from 'playwright';
import * as pdfjs from 'pdfjs-dist/legacy/build/pdf.mjs';

const here = path.dirname(fileURLToPath(import.meta.url));
const root = path.resolve(here, '../..');
const docsDir = path.join(root, 'docs');
const outDir = path.join(docsDir, 'pdf');
const mermaidJs = path.join(here, 'node_modules/mermaid/dist/mermaid.min.js');

const DOCS = [
    { key: 'instalasi', md: 'INSTALASI-CPANEL.md', out: 'SIAKAD-Panduan-Instalasi-cPanel.pdf',
      title: 'Panduan Instalasi di cPanel',
      subtitle: 'Langkah demi langkah memasang SIAKAD Sekolah di hosting cPanel — tanpa akses terminal',
      hero: 'img/pma-03-import-berhasil.jpg' },
    { key: 'pengguna', md: 'PANDUAN-PENGGUNA.md', out: 'SIAKAD-Panduan-Pengguna.pdf',
      title: 'Panduan Pengguna',
      subtitle: 'Cara menggunakan aplikasi untuk Administrasi, Kepala Sekolah, Tata Usaha, Guru, Wali Kelas, Murid, dan Orang Tua',
      hero: 'img/02-dashboard-admin.jpg' },
    { key: 'teknis', md: 'DOKUMENTASI-TEKNIS.md', out: 'SIAKAD-Dokumentasi-Teknis.pdf',
      title: 'Dokumentasi Teknis',
      subtitle: 'Arsitektur, konfigurasi, database, hak akses, aturan bisnis, dan pengembangan',
      hero: 'img/05-guru-nilai.jpg' },
];
const COMBINED = { out: 'SIAKAD-Dokumentasi-Lengkap.pdf', title: 'Dokumentasi Lengkap',
    subtitle: 'Panduan Instalasi cPanel · Panduan Pengguna · Dokumentasi Teknis', hero: 'img/02-dashboard-admin.jpg' };

// Lebar area cetak A4 dikurangi margin kiri-kanan (16mm + 16mm) dalam piksel CSS (96 dpi)
const CONTENT_WIDTH_PX = Math.floor((210 - 32) / 25.4 * 96);

const MONTHS = ['Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni', 'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'];
const now = new Date();
const versionLabel = `Versi ${MONTHS[now.getMonth()]} ${now.getFullYear()}`;

const esc = s => String(s).replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;');
const unesc = s => String(s).replace(/&quot;/g, '"').replace(/&#39;/g, "'").replace(/&lt;/g, '<').replace(/&gt;/g, '>').replace(/&amp;/g, '&');
/** Slug ala GitHub agar tautan #anchor di Markdown tetap berfungsi. */
const slug = s => s.toLowerCase().trim()
    .replace(/<[^>]+>/g, '').replace(/[`*_]/g, '')
    .replace(/[^\p{L}\p{N} \-]/gu, '').replace(/ /g, '-');

function preprocess(md) {
    return md
        .replace(/^# .*\n/, '')                                        // judul -> sampul
        .replace(/^## Daftar Isi[\s\S]*?(?=^---\s*$|^## )/m, '')      // diganti daftar isi PDF
        .replace(/^Lihat juga:.*$/gm, '')
        .replace(/^---\s*$/gm, '');
}

/** Markdown -> HTML satu dokumen. prefix membuat id unik di PDF gabungan. */
function renderDoc(doc, { prefix, combined }) {
    const headings = [];
    const byFile = Object.fromEntries(DOCS.map(d => [d.md, d]));
    const marked = new Marked({ gfm: true });
    marked.use({
        renderer: {
            heading({ tokens, depth }) {
                const html = this.parser.parseInline(tokens);
                const id = prefix + slug(tokens.map(t => t.raw).join(''));
                if (depth <= 3) headings.push({ depth, id, html });
                return `<h${depth} id="${id}"><span class="mk" data-id="${id}"></span>${html}</h${depth}>\n`;
            },
            code({ text, lang }) {
                if (lang === 'mermaid') return `<div class="diagram"><pre class="mermaid">${esc(text)}</pre></div>\n`;
                return `<pre><code>${esc(text)}</code></pre>\n`;
            },
            link({ href, tokens }) {
                const text = this.parser.parseInline(tokens);
                if (/^https?:/.test(href)) return `<a href="${esc(href)}">${text}</a>`;
                if (href.startsWith('#')) return `<a href="#${prefix}${esc(href.slice(1))}">${text}</a>`;
                const [file, anchor] = href.split('#');
                const target = byFile[path.basename(file)];
                if (combined && target) {
                    return `<a href="#${target.key}-${anchor ? esc(anchor) : 'top'}">${text}</a>`;
                }
                return `<span class="xref">${text}</span>`;
            },
            // Setiap gambar menjadi figure berketerangan (termasuk yang berada di dalam daftar bernomor)
            image({ href, text }) {
                const caption = esc(unesc(text));
                return `<figure><img src="${esc(href)}" alt="${caption}"><figcaption>${caption}</figcaption></figure>`;
            },
            paragraph({ tokens }) {
                const inner = this.parser.parseInline(tokens);
                return tokens.length === 1 && tokens[0].type === 'image' ? inner + '\n' : `<p>${inner}</p>\n`;
            },
        },
    });
    const src = preprocess(fs.readFileSync(path.join(docsDir, doc.md), 'utf8'));
    return { html: marked.parse(src), headings };
}

function coverHtml(meta) {
    return `<section class="cover">
      <div class="cover-top"><div class="brand">🎓 SIAKAD Sekolah</div><div class="version">${esc(versionLabel)}</div></div>
      <div class="cover-main">
        <div class="eyebrow">Sistem Informasi Akademik &amp; Administrasi Sekolah</div>
        <h1 class="cover-title">${esc(meta.title)}</h1>
        <p class="cover-sub">${esc(meta.subtitle)}</p>
      </div>
      <img class="cover-hero" src="${esc(meta.hero)}" alt="">
      <div class="cover-roles">Murid · Guru · Wali Kelas · Orang Tua Murid · Administrasi · Kepala Sekolah · Tata Usaha</div>
    </section>`;
}

function tocHtml(entries, pages) {
    const rows = entries.map(e => `<li class="toc-${e.depth}"><a href="#${e.id}"><span class="toc-text">${e.html}</span>`
        + `<span class="toc-dots"></span><span class="toc-page">${pages[e.id] ?? ''}</span></a></li>`).join('\n');
    return `<section class="toc"><h1 class="toc-title">Daftar Isi</h1><ul>${rows}</ul></section>`;
}

const CSS = `
@page { size: A4; margin: 18mm 16mm 20mm 16mm; }
* { box-sizing: border-box; }
html { font-family: "DejaVu Sans", "Liberation Sans", Arial, "Noto Color Emoji", sans-serif; font-size: 10pt; color: #1e293b; }
body { margin: 0; line-height: 1.55; }
a { color: #1d4ed8; text-decoration: none; }
.xref { font-weight: 600; color: #1d4ed8; }
h1, h2, h3, h4 { color: #0f172a; line-height: 1.25; break-after: avoid; position: relative; }
h2 { font-size: 17pt; margin: 0 0 10pt; padding-bottom: 6pt; border-bottom: 2.5pt solid #2563eb; break-before: page; }
h3 { font-size: 12.5pt; margin: 16pt 0 6pt; color: #1e3a8a; }
h4 { font-size: 11pt; margin: 12pt 0 4pt; }
p, li { orphans: 3; widows: 3; }
.mk { position: absolute; left: 0; top: 0; font-size: 1px; color: #fff; white-space: nowrap; }
.part-title + h2, .doc-start + h2 { break-before: auto; }
code { font-family: "DejaVu Sans Mono", monospace; font-size: 8.6pt; background: #f1f5f9; padding: 0.5pt 3pt; border-radius: 3pt; color: #9f1239; }
pre { background: #0f172a; color: #e2e8f0; padding: 8pt 10pt; border-radius: 5pt; white-space: pre-wrap; word-break: break-word; font-size: 8.3pt; line-height: 1.45; break-inside: avoid; }
pre code { background: none; color: inherit; padding: 0; font-size: inherit; }
table { width: 100%; border-collapse: collapse; margin: 8pt 0 12pt; font-size: 8.8pt; break-inside: auto; }
th, td { border: 0.6pt solid #cbd5e1; padding: 4pt 6pt; vertical-align: top; text-align: left; }
th { background: #e0e7ff; color: #1e3a8a; }
tr { break-inside: avoid; }
tbody tr:nth-child(even) td { background: #f8fafc; }
blockquote { margin: 10pt 0; padding: 7pt 11pt; background: #fffbeb; border-left: 3.5pt solid #f59e0b; border-radius: 3pt; break-inside: avoid; }
blockquote p { margin: 3pt 0; }
figure { margin: 10pt 0 14pt; text-align: center; break-inside: avoid; }
img { max-width: 100%; }
figure img { display: block; margin: 0 auto; max-width: 100%; max-height: 150mm; border: 0.8pt solid #cbd5e1; border-radius: 4pt; break-inside: avoid; }
figcaption { font-size: 8.5pt; color: #475569; margin-top: 4pt; font-style: italic; }
.img-row { display: flex; gap: 12pt; justify-content: center; margin: 8pt 0 12pt; break-inside: avoid; }
.img-row img { width: 38%; border: 0.8pt solid #cbd5e1; border-radius: 4pt; }
.menu-path { display: inline-block; margin: 2pt 0; padding: 3pt 9pt; background: #eef2ff; border-left: 3pt solid #2563eb;
  border-radius: 3pt; font-size: 9pt; font-weight: 600; color: #1e3a8a; }
.diagram { break-inside: avoid; text-align: center; margin: 10pt 0; }
.diagram svg { max-width: 100%; height: auto; }
pre.mermaid { background: none; color: inherit; padding: 0; }
input[type=checkbox] { margin-right: 4pt; }
li.task-list-item { list-style: none; }

.cover { height: 255mm; width: 100%; padding: 16mm 15mm; border-radius: 8pt; display: flex; flex-direction: column; break-after: page;
  background: linear-gradient(150deg, #1e3a8a 0%, #2563eb 55%, #38bdf8 100%); color: #fff; }
.cover-top { display: flex; justify-content: space-between; font-size: 11pt; opacity: .95; }
.brand { font-weight: 700; font-size: 13pt; }
.cover-main { margin-top: 30mm; }
.eyebrow { text-transform: uppercase; letter-spacing: .12em; font-size: 9pt; opacity: .85; }
.cover-title { color: #fff; font-size: 34pt; margin: 6pt 0 10pt; line-height: 1.1; }
.cover-sub { font-size: 12.5pt; max-width: 150mm; opacity: .95; }
.cover-hero { margin-top: auto; width: 100%; border-radius: 6pt; box-shadow: 0 6pt 24pt rgba(0,0,0,.35); border: 2pt solid rgba(255,255,255,.6); }
.cover-roles { margin-top: 10mm; font-size: 9pt; opacity: .9; text-align: center; }

.toc { break-after: page; }
.toc-title { font-size: 20pt; border-bottom: 2.5pt solid #2563eb; padding-bottom: 6pt; margin-top: 0; }
.toc ul { list-style: none; padding: 0; margin: 0; }
.toc li a { display: flex; align-items: baseline; color: #1e293b; padding: 2.2pt 0; }
.toc-1 a { font-weight: 700; font-size: 12pt; color: #1e3a8a !important; margin-top: 8pt; }
.toc-2 a { font-weight: 600; }
.toc-3 { padding-left: 14pt; font-size: 9pt; }
.toc-dots { flex: 1; border-bottom: 0.8pt dotted #94a3b8; margin: 0 5pt; transform: translateY(-3pt); }
.toc-page { min-width: 16pt; text-align: right; }
.part-title { position: relative; break-before: page; padding: 60mm 0 14mm; margin-bottom: 10mm; border-bottom: 2.5pt solid #2563eb; }
.part-title .num { color: #2563eb; font-weight: 700; letter-spacing: .1em; text-transform: uppercase; font-size: 11pt; }
.part-title h1 { font-size: 30pt; margin: 6pt 0; border: 0; }
.part-title p { font-size: 12pt; color: #475569; max-width: 150mm; }
`;

function pageHtml(meta, bodyParts, tocEntries, pages) {
    return `<!doctype html><html lang="id"><head><meta charset="utf-8">
<base href="${pathToFileURL(docsDir).href}/"><title>SIAKAD Sekolah — ${esc(meta.title)}</title>
<style>${CSS}</style></head><body>
${coverHtml(meta)}
${tocHtml(tocEntries, pages)}
${bodyParts}
<script src="${pathToFileURL(mermaidJs).href}"></script>
<script>
  mermaid.initialize({ startOnLoad: false, theme: 'neutral', securityLevel: 'loose', fontFamily: 'DejaVu Sans',
    er: { useMaxWidth: true }, sequence: { useMaxWidth: true } });
  mermaid.run({ querySelector: '.mermaid' }).then(() => { document.body.dataset.ready = '1'; })
    .catch(e => { document.body.dataset.ready = 'error: ' + e; });
</script></body></html>`;
}

async function findHeadingPages(pdfBuffer) {
    const pdf = await pdfjs.getDocument({ data: new Uint8Array(pdfBuffer), useSystemFonts: true }).promise;
    const pages = {};
    for (let i = 1; i <= pdf.numPages; i++) {
        const text = (await (await pdf.getPage(i)).getTextContent()).items.map(it => it.str).join('');
        for (const m of text.matchAll(/@@([^@]+)@@/g)) pages[m[1]] ??= i;
    }
    return { pages, total: pdf.numPages };
}

async function renderPdf(browser, meta, body, tocEntries, outFile) {
    const page = await browser.newPage();
    const footer = `<div style="width:100%;font-size:7.5pt;color:#64748b;padding:0 16mm;display:flex;justify-content:space-between;font-family:DejaVu Sans,sans-serif">
      <span>SIAKAD Sekolah · ${esc(meta.title)}</span><span>Halaman <span class="pageNumber"></span> dari <span class="totalPages"></span></span></div>`;
    const opts = { format: 'A4', printBackground: true, preferCSSPageSize: true, displayHeaderFooter: true,
        headerTemplate: '<div></div>', footerTemplate: footer };
    const tmp = path.join(outDir, `.tmp-${path.basename(outFile, '.pdf')}.html`);

    const load = async (pages, markers) => {
        let html = pageHtml(meta, body, tocEntries, pages);
        html = html.replace(/<span class="mk" data-id="([^"]+)"><\/span>/g,
            (_, id) => `<span class="mk" data-id="${id}">${markers ? `@@${id}@@` : ''}</span>`);
        fs.writeFileSync(tmp, html);
        await page.goto(pathToFileURL(tmp).href, { waitUntil: 'load' });
        await page.waitForFunction(() => document.body.dataset.ready, null, { timeout: 60000 });
        const state = await page.evaluate(() => document.body.dataset.ready);
        if (state !== '1') throw new Error(`Mermaid gagal: ${state}`);
        await page.evaluate(() => Promise.all([...document.images].map(i => i.complete ? 0 : new Promise(r => { i.onload = i.onerror = r; }))));
        const broken = await page.evaluate(() => [...document.images].filter(i => !i.naturalWidth).map(i => i.getAttribute('src')));
        if (broken.length) throw new Error('Gambar tidak ditemukan: ' + broken.join(', '));
        // Elemen yang lebih lebar dari area cetak membuat Chromium mengecilkan seluruh halaman
        await page.emulateMedia({ media: 'print' });
        await page.setViewportSize({ width: CONTENT_WIDTH_PX, height: 1000 });
        const wide = await page.evaluate(w => [...document.querySelectorAll('body *')]
            .filter(el => el.getBoundingClientRect().right > w + 1 && !el.closest('.mk'))
            .slice(0, 5).map(el => `<${el.tagName.toLowerCase()} class="${el.className}"> ${el.textContent.slice(0, 60)}`), CONTENT_WIDTH_PX);
        if (wide.length) throw new Error('Elemen melebihi lebar halaman:\n  ' + wide.join('\n  '));
    };

    // Tahap 1: cari nomor halaman setiap judul (daftar isi diisi placeholder agar tata letak sama)
    const placeholder = Object.fromEntries(tocEntries.map(e => [e.id, '00']));
    await load(placeholder, true);
    const { pages } = await findHeadingPages(await page.pdf(opts));
    // Tahap 2: render final dengan nomor halaman
    await load(pages, false);
    const buf = await page.pdf(opts);
    fs.writeFileSync(outFile, buf);
    fs.unlinkSync(tmp);
    await page.close();
    const { total } = await findHeadingPages(buf);
    const missing = tocEntries.filter(e => !pages[e.id]).map(e => e.id);
    if (missing.length) console.warn('  ! judul tanpa nomor halaman:', missing.join(', '));
    return total;
}

async function main() {
    fs.mkdirSync(outDir, { recursive: true });
    const browser = await chromium.launch(process.env.CHROMIUM_PATH ? { executablePath: process.env.CHROMIUM_PATH } : {});
    const partsForCombined = [];
    const combinedToc = [];

    for (const [i, doc] of DOCS.entries()) {
        // PDF mandiri
        const single = renderDoc(doc, { prefix: '', combined: false });
        const toc = single.headings.filter(h => h.depth <= (doc.key === 'teknis' ? 2 : 3));
        const bodySingle = `<div class="doc-start"></div>${single.html}`;
        const n = await renderPdf(browser, doc, bodySingle, toc, path.join(outDir, doc.out));
        console.log(`✓ ${doc.out} (${n} halaman)`);

        // Bagian untuk PDF gabungan
        const part = renderDoc(doc, { prefix: `${doc.key}-`, combined: true });
        const partId = `${doc.key}-top`;
        partsForCombined.push(`<section class="part-title" id="${partId}"><span class="mk" data-id="${partId}"></span>
            <div class="num">Bagian ${i + 1}</div><h1>${esc(doc.title)}</h1><p>${esc(doc.subtitle)}</p></section>${part.html}`);
        combinedToc.push({ depth: 1, id: partId, html: `Bagian ${i + 1} — ${esc(doc.title)}` },
            ...part.headings.filter(h => h.depth === 2));
    }

    const n = await renderPdf(browser, COMBINED, partsForCombined.join('\n'), combinedToc, path.join(outDir, COMBINED.out));
    console.log(`✓ ${COMBINED.out} (${n} halaman)`);
    await browser.close();
}

main().catch(e => { console.error(e); process.exit(1); });
