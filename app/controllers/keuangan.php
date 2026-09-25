<?php
declare(strict_types=1);

function finance_invoices(): void
{
    $classId = param_int('class_id');
    $status = param('status');
    $q = param('q');

    $sql = 'SELECT i.*, u.name AS student_name, s.nis, c.name AS class_name,
                   COALESCE((SELECT SUM(amount) FROM payments p WHERE p.invoice_id = i.id), 0) AS paid
            FROM invoices i JOIN students s ON s.id = i.student_id JOIN users u ON u.id = s.user_id
            LEFT JOIN classes c ON c.id = s.class_id WHERE 1 = 1';
    $params = [];
    if ($classId) {
        $sql .= ' AND s.class_id = ?';
        $params[] = $classId;
    }
    if ($q !== '') {
        $sql .= ' AND (u.name LIKE ? OR s.nis LIKE ? OR i.title LIKE ?)';
        array_push($params, "%$q%", "%$q%", "%$q%");
    }
    $invoices = db_all($sql . ' ORDER BY i.due_date DESC, i.id DESC', $params);
    if ($status !== '') {
        $invoices = array_values(array_filter($invoices, function ($i) use ($status) {
            $paid = (float) $i['paid'];
            $amount = (float) $i['amount'];
            return match ($status) {
                'lunas'    => $paid >= $amount,
                'sebagian' => $paid > 0 && $paid < $amount,
                'belum'    => $paid < $amount,
                default    => true,
            };
        }));
    }
    render('keuangan/invoices', [
        'invoices' => $invoices,
        'classes'  => db_all('SELECT id, name FROM classes ORDER BY level, name'),
        'classId'  => $classId, 'status' => $status, 'q' => $q,
    ], 'Tagihan & Pembayaran');
}

function finance_invoice_form(): void
{
    $me = current_user();
    if (is_post()) {
        $target = input('target');
        $title = input('title');
        $amount = parse_rupiah(input('amount'));
        $due = input('due_date');
        $errors = [];
        if ($title === '') {
            $errors[] = 'Nama tagihan wajib diisi.';
        }
        if ($amount <= 0) {
            $errors[] = 'Jumlah tagihan harus lebih dari 0.';
        }
        if ($due !== '' && !valid_date($due)) {
            $errors[] = 'Tanggal jatuh tempo tidak valid.';
        }

        $studentIds = [];
        if ($target === 'class') {
            $studentIds = array_column(class_students((int) input('class_id')), 'id');
            if (!$studentIds) {
                $errors[] = 'Kelas yang dipilih tidak memiliki siswa.';
            }
        } elseif ($target === 'all') {
            $studentIds = array_column(db_all('SELECT s.id FROM students s JOIN users u ON u.id = s.user_id WHERE u.active = 1'), 'id');
        } else {
            $sid = (int) input('student_id');
            if (db_value('SELECT id FROM students WHERE id = ?', [$sid])) {
                $studentIds = [$sid];
            } else {
                $errors[] = 'Pilih siswa yang valid.';
            }
        }

        if (!$errors) {
            db_transaction(function () use ($studentIds, $title, $amount, $due, $me) {
                foreach ($studentIds as $sid) {
                    db_insert('invoices', [
                        'student_id' => $sid, 'title' => $title, 'amount' => $amount,
                        'due_date' => nullable($due), 'created_by' => $me['id'], 'created_at' => date('Y-m-d H:i:s'),
                    ]);
                }
            });
            flash('success', count($studentIds) . ' tagihan berhasil dibuat.');
            redirect('keuangan/tagihan');
        }
        foreach ($errors as $err) {
            flash('danger', $err);
        }
        redirect('keuangan/tagihan/form');
    }

    render('keuangan/invoice_form', [
        'classes'  => db_all('SELECT id, name FROM classes ORDER BY level, name'),
        'students' => db_all(
            'SELECT s.id, s.nis, u.name, c.name AS class_name FROM students s JOIN users u ON u.id = s.user_id
             LEFT JOIN classes c ON c.id = s.class_id WHERE u.active = 1 ORDER BY c.name, u.name'
        ),
    ], 'Buat Tagihan');
}

function finance_invoice_delete(): void
{
    $id = (int) input('id');
    if (db_value('SELECT COUNT(*) FROM payments WHERE invoice_id = ?', [$id])) {
        flash('warning', 'Tagihan yang sudah memiliki pembayaran tidak dapat dihapus. Hapus pembayarannya terlebih dahulu.');
    } else {
        db_query('DELETE FROM invoices WHERE id = ?', [$id]);
        flash('success', 'Tagihan dihapus.');
    }
    redirect('keuangan/tagihan');
}

function finance_payment(): void
{
    $id = param_int('id');
    $invoice = db_one(
        'SELECT i.*, u.name AS student_name, s.nis, c.name AS class_name,
                COALESCE((SELECT SUM(amount) FROM payments p WHERE p.invoice_id = i.id), 0) AS paid
         FROM invoices i JOIN students s ON s.id = i.student_id JOIN users u ON u.id = s.user_id
         LEFT JOIN classes c ON c.id = s.class_id WHERE i.id = ?',
        [$id]
    );
    if (!$invoice) {
        abort(404, 'Tagihan tidak ditemukan.');
    }
    $remaining = max(0, (float) $invoice['amount'] - (float) $invoice['paid']);

    if (is_post()) {
        $amount = parse_rupiah(input('amount'));
        $date = input('paid_at');
        $method = input('method');
        if ($amount <= 0 || $amount > $remaining + 0.001) {
            flash('danger', 'Jumlah pembayaran harus lebih dari 0 dan tidak melebihi sisa tagihan (' . rupiah($remaining) . ').');
        } elseif (!valid_date($date) || $date > date('Y-m-d')) {
            flash('danger', 'Tanggal pembayaran tidak valid.');
        } elseif (!in_array($method, PAYMENT_METHODS, true)) {
            flash('danger', 'Metode pembayaran tidak valid.');
        } else {
            $pid = db_insert('payments', [
                'invoice_id' => $id, 'amount' => $amount, 'paid_at' => $date, 'method' => $method,
                'note' => nullable(input('note')), 'received_by' => current_user()['id'], 'created_at' => date('Y-m-d H:i:s'),
            ]);
            flash('success', 'Pembayaran ' . rupiah($amount) . ' dicatat. No. kwitansi: ' . receipt_number($pid, $date));
        }
        redirect('keuangan/bayar', ['id' => $id]);
    }

    $payments = db_all(
        'SELECT p.*, u.name AS receiver FROM payments p LEFT JOIN users u ON u.id = p.received_by
         WHERE p.invoice_id = ? ORDER BY p.paid_at, p.id',
        [$id]
    );
    render('keuangan/payment', compact('invoice', 'payments', 'remaining'), 'Pembayaran Tagihan');
}

function finance_payment_delete(): void
{
    $payment = db_one('SELECT * FROM payments WHERE id = ?', [(int) input('id')]);
    if ($payment) {
        db_query('DELETE FROM payments WHERE id = ?', [$payment['id']]);
        flash('success', 'Pembayaran dibatalkan.');
        redirect('keuangan/bayar', ['id' => $payment['invoice_id']]);
    }
    redirect('keuangan/tagihan');
}

function receipt_number(int $paymentId, string $date): string
{
    return sprintf('KW/%s/%05d', date('Ym', strtotime($date)), $paymentId);
}

function finance_receipt(): void
{
    $me = current_user();
    $payment = db_one(
        'SELECT p.*, i.title, i.amount AS invoice_amount, i.student_id, u.name AS student_name, s.nis,
                c.name AS class_name, r.name AS receiver
         FROM payments p JOIN invoices i ON i.id = p.invoice_id JOIN students s ON s.id = i.student_id
         JOIN users u ON u.id = s.user_id LEFT JOIN classes c ON c.id = s.class_id
         LEFT JOIN users r ON r.id = p.received_by WHERE p.id = ?',
        [param_int('id')]
    );
    if (!$payment) {
        abort(404, 'Data pembayaran tidak ditemukan.');
    }
    if (!can_view_student($me, student_by_id((int) $payment['student_id']))) {
        abort(403, 'Anda tidak memiliki akses ke kwitansi ini.');
    }
    $payment['number'] = receipt_number((int) $payment['id'], $payment['paid_at']);
    render('keuangan/receipt', compact('payment'), 'Kwitansi ' . $payment['number']);
}
