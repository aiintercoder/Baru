# SIAKAD Sekolah

Aplikasi web Sistem Informasi Akademik & Administrasi Sekolah dengan 7 tipe pengguna:
**Murid, Guru, Wali Kelas, Orang Tua Murid, Administrasi, Kepala Sekolah, dan Tata Usaha**.

Dibangun dengan PHP 8.1+ native (tanpa framework / Composer), PDO, dan Bootstrap 5 (disertakan secara lokal,
jadi tetap berjalan di jaringan sekolah tanpa internet). Database default **SQLite** (tanpa instalasi server),
opsional **MySQL/MariaDB**.

## Fitur per Peran

| Peran | Fitur |
|---|---|
| **Administrasi** | Kelola pengguna semua peran (termasuk data murid: NIS, kelas, orang tua), kelas & wali kelas, mata pelajaran & KKM, penugasan guru pengampu, jadwal pelajaran (dengan deteksi bentrok kelas/guru), pengaturan sekolah & tahun ajaran/semester aktif, laporan, pengumuman |
| **Kepala Sekolah** | Dashboard statistik sekolah, data siswa & guru, laporan absensi per kelas (+ siswa sering alpa), laporan rata-rata nilai per kelas/mapel, laporan keuangan, arsip surat, buat pengumuman, lihat rapor siswa |
| **Tata Usaha** | Buat tagihan (per siswa / per kelas / semua siswa), catat pembayaran (cicilan, validasi tidak melebihi sisa), cetak kwitansi, laporan keuangan per kelas & per metode, arsip surat masuk/keluar, data siswa & guru, pengumuman |
| **Guru** | Jadwal mengajar, input absensi harian kelas yang diajar, input nilai (Tugas, UTS, UAS → nilai akhir & predikat otomatis) |
| **Wali Kelas** | Semua fitur guru + daftar siswa perwalian (kontak orang tua, rekap kehadiran, tunggakan), rekap absensi bulanan, rekap nilai & peringkat kelas, catatan wali kelas untuk rapor, lihat/cetak rapor |
| **Murid** | Dashboard, jadwal pelajaran, nilai & rapor (bisa dicetak), riwayat kehadiran, tagihan & riwayat pembayaran + kwitansi, pengumuman |
| **Orang Tua** | Sama seperti murid untuk anaknya; mendukung **lebih dari satu anak** (pilih anak di bagian atas halaman) |

Pengumuman bisa ditujukan ke semua pengguna atau peran tertentu.

## Menjalankan

```bash
# 1. Buat database + data contoh (semua peran)
php database/install.php --demo

# 2. Jalankan server
php -S localhost:8000 -t public
```

Buka http://localhost:8000. Semua akun demo memakai password **`password123`**:

| Peran | Username |
|---|---|
| Administrasi | `admin` |
| Kepala Sekolah | `kepsek` |
| Tata Usaha | `tu` |
| Guru | `guru1` … `guru4` |
| Wali Kelas | `walikelas1` (VII-A), `walikelas2` (VII-B), `walikelas3` (VIII-A) |
| Murid | `siswa1` … `siswa24` |
| Orang Tua | `ortu1` (punya 2 anak) … `ortu23` |

Instalasi tanpa data demo (hanya akun `admin` dengan password acak yang ditampilkan di terminal):

```bash
php database/install.php
```

Pasang ulang database SQLite dari awal: `php database/install.php --demo --fresh`.

### Memakai MySQL / MariaDB (mis. XAMPP)

Buat database kosong `sekolah`, lalu atur environment variable (atau ubah `config.php`):

```bash
DB_DRIVER=mysql DB_HOST=127.0.0.1 DB_NAME=sekolah DB_USER=root DB_PASS= php database/install.php --demo
```

### Deploy ke Apache / shared hosting

Arahkan document root ke folder `public/`. Jika tidak memungkinkan (folder proyek diletakkan langsung di
`htdocs`), `index.php` di root mengalihkan ke `public/` dan file `.htaccess` memblokir akses ke `app/`,
`database/`, dan `config.php`. Pastikan folder `database/` dapat ditulis oleh web server (untuk SQLite).

## Aturan Penilaian

- Nilai akhir = Tugas 30% + UTS 30% + UAS 40% (atur di `config.php` → `grade_weights`).
  Komponen yang belum diisi tidak dihitung sebagai 0; bobot dinormalisasi ke komponen yang sudah ada.
- Predikat: A ≥ 90, B ≥ 80, C ≥ 70, D < 70. Tuntas bila nilai akhir ≥ KKM mata pelajaran.
- Nilai & catatan rapor disimpan per tahun ajaran + semester aktif (diatur di menu Pengaturan).

## Keamanan

- Password di-hash (`password_hash`), pembatasan percobaan login, regenerasi session ID saat login.
- Token CSRF pada setiap form POST, semua query memakai prepared statement, output di-escape.
- Kontrol akses per rute berdasarkan peran, plus pengecekan kepemilikan data (murid hanya melihat datanya
  sendiri, orang tua hanya anaknya, wali kelas hanya kelas perwaliannya, guru hanya kelas yang diajar).
- Cookie session `HttpOnly` + `SameSite=Lax`; set `APP_DEBUG=false` (default) di produksi.

## Struktur

```
public/            # document root: index.php (front controller) & assets/
app/
  bootstrap.php    # memuat konfigurasi & helper
  routes.php       # daftar rute + peran yang diizinkan
  menu.php         # menu sidebar per peran
  auth.php access.php helpers.php db.php
  controllers/     # admin, guru, walikelas, siswa, keuangan, surat, laporan, data, pengumuman, dashboard
  views/           # template PHP
database/
  schema.sql       # skema tabel (SQLite/MySQL)
  install.php      # instalasi + data demo
tests/smoke_test.php
```

## Pengujian

```bash
php tests/smoke_test.php
```

Tes ini memakai database sementara, login sebagai ketujuh peran, membuka semua menu, menguji pembatasan
akses & CSRF, serta alur utama: input nilai, absensi, catatan rapor, tagihan & pembayaran, kwitansi,
pengumuman bertarget, tambah siswa, deteksi jadwal bentrok, ganti anak (orang tua), ganti password, dan logout.
