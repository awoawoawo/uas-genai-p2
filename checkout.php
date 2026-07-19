<?php
session_start();
require 'config.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

if (empty($_SESSION['cart'])) {
    header("Location: menu.php");
    exit;
}

$total_jual = 0;
$total_modal = 0;

foreach ($_SESSION['cart'] as $item) {
    $total_jual += $item['harga'] * $item['qty'];
    $total_modal += $item['harga_modal'] * $item['qty'];
}

// Proses Konfirmasi Pembayaran
if (isset($_POST['konfirmasi_pembayaran'])) {
    try {
        $pdo->beginTransaction();
        
        // 1. Simpan ke tabel transaksi
        $stmt = $pdo->prepare("INSERT INTO transaksi (tanggal, total_jual, total_modal) VALUES (NOW(), ?, ?)");
        $stmt->execute([$total_jual, $total_modal]);
        $transaksi_id = $pdo->lastInsertId();
        
        // 2. Simpan ke tabel transaksi_detail & Kurangi Stok
        $stmt_detail = $pdo->prepare("INSERT INTO transaksi_detail (transaksi_id, produk_id, qty, harga_saat_jual) VALUES (?, ?, ?, ?)");
        $stmt_stok = $pdo->prepare("UPDATE produk SET stok = stok - ? WHERE id = ?");
        
        foreach ($_SESSION['cart'] as $item_id => $item) {
            $stmt_detail->execute([$transaksi_id, $item['id'], $item['qty'], $item['harga']]);
            $stmt_stok->execute([$item['qty'], $item['id']]);
        }
        
        $pdo->commit();
        
        // 3. Kosongkan keranjang
        unset($_SESSION['cart']);
        
        // 4. Set pesan sukses
        $_SESSION['msg'] = "Pembayaran berhasil!";
        $_SESSION['msg_type'] = "success";
        
        header("Location: menu.php");
        exit;
        
    } catch (Exception $e) {
        $pdo->rollBack();
        die("Terjadi kesalahan sistem saat memproses transaksi: " . $e->getMessage());
    }
}

?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Checkout - Kopi Kuningan</title>
    <link rel="stylesheet" href="assets/style.css">
</head>
<body>

<?php include 'includes/navbar.php'; ?>

<div class="container" style="max-width: 900px;">
    <h2 class="text-center mb-2" style="color: var(--text-main); margin-top: 2rem;">Konfirmasi Pembayaran</h2>
    
    <div style="display: flex; flex-wrap: wrap; gap: 2rem; align-items: stretch;">
        
        <!-- Kolom Kiri: Tabel Pesanan -->
        <div style="flex: 1.5; min-width: 300px;">
            <div class="card card-table" style="height: 100%; padding: 1.5rem; margin-bottom: 0;">
                <h4 style="margin-bottom: 1rem; font-family: var(--font-mono); color: var(--text-main); text-transform: uppercase;">Rincian Pesanan</h4>
                <table style="margin-top: 0;">
                    <thead>
                        <tr>
                            <th>Produk</th>
                            <th>Qty</th>
                            <th class="text-right">Subtotal</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach($_SESSION['cart'] as $item): ?>
                        <tr>
                            <td><?= htmlspecialchars($item['nama']) ?></td>
                            <td><?= $item['qty'] ?></td>
                            <td class="text-right">Rp <?= number_format($item['harga'] * $item['qty'], 0, ',', '.') ?></td>
                        </tr>
                        <?php endforeach; ?>
                        <tr>
                            <td colspan="2" class="text-right" style="font-weight:bold; font-size: 1.2rem; color: var(--primary-color);">TOTAL:</td>
                            <td class="text-right" style="font-weight:bold; font-size: 1.2rem; color: var(--primary-color);">Rp <?= number_format($total_jual, 0, ',', '.') ?></td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Kolom Kanan: QRIS dan Tombol -->
        <div style="flex: 1; min-width: 300px;">
            <div class="card card-table" style="height: 100%; padding: 1.5rem; margin-bottom: 0; display: flex; flex-direction: column; justify-content: space-between;">
                <div>
                    <h4 style="margin-bottom: 1rem; font-family: var(--font-mono); color: var(--text-main); text-transform: uppercase; text-align: center; border-bottom: 2px dashed var(--border-color); padding-bottom: 1rem;">Pembayaran</h4>
                    <div class="qris-container" style="margin: 1rem 0 0 0; padding: 0; border: none; text-align: center;">
                        <img src="https://api.qrserver.com/v1/create-qr-code/?size=300x300&data=DummyQRISKopiKuningan" alt="QRIS Kopi Kuningan" class="qris-img" style="border: 2px solid var(--border-color); padding: 0.5rem; background: white;">
                    </div>
                </div>
                
                <form method="POST" style="display: flex; gap: 1rem; justify-content: center; margin-top: 2rem; flex-direction: column;">
                    <button type="submit" name="konfirmasi_pembayaran" class="btn btn-success" style="width: 100%; padding: 1rem; font-size: 1.1rem; display: flex; align-items: center; justify-content: center;">SELESAI</button>
                    <a href="menu.php" class="btn" style="width: 100%; padding: 0.8rem; border: 2px solid var(--border-color); color: var(--text-main); background: var(--surface); text-decoration: none; display: flex; align-items: center; justify-content: center;">KEMBALI</a>
                </form>
            </div>
        </div>

    </div>
</div>

</body>
</html>
