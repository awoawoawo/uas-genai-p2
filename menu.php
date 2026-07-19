<?php
session_start();
require 'config.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

// Inisialisasi keranjang
if (!isset($_SESSION['cart'])) {
    $_SESSION['cart'] = [];
}

// Ambil filter kategori
$kategori_filter = isset($_GET['kategori']) ? $_GET['kategori'] : (isset($_POST['kategori']) ? $_POST['kategori'] : 'semua');

// Proses Kosongkan Keranjang (Paling Atas)
if (isset($_GET['action']) && $_GET['action'] == 'clear') {
    unset($_SESSION['cart']);
    $_SESSION['cart'] = [];
    header("Location: menu.php?kategori=" . urlencode($kategori_filter));
    exit;
}
$kategori_filter = isset($_GET['kategori']) ? $_GET['kategori'] : (isset($_POST['kategori']) ? $_POST['kategori'] : 'semua');

// Proses Tambah ke Keranjang
if (isset($_POST['add_to_cart'])) {
    $id = $_POST['produk_id'];
    $qty = (int)$_POST['qty'];
    
    // Cek produk dan stok
    $stmt = $pdo->prepare("SELECT * FROM produk WHERE id = ?");
    $stmt->execute([$id]);
    $produk = $stmt->fetch();
    
    if ($produk) {
        $current_cart_qty = isset($_SESSION['cart'][$id]) ? $_SESSION['cart'][$id]['qty'] : 0;
        $total_requested = $current_cart_qty + $qty;
        
        if ($total_requested <= $produk['stok']) {
            if (isset($_SESSION['cart'][$id])) {
                $_SESSION['cart'][$id]['qty'] += $qty;
            } else {
                $_SESSION['cart'][$id] = [
                    'id' => $produk['id'],
                    'nama' => $produk['nama'],
                    'harga' => $produk['harga_jual'],
                    'harga_modal' => $produk['harga_modal'],
                    'qty' => $qty
                ];
            }
            $_SESSION['msg'] = "Produk berhasil ditambahkan ke keranjang.";
            $_SESSION['msg_type'] = "success";
        } else {
            $_SESSION['msg'] = "Stok tidak mencukupi! Sisa stok: " . $produk['stok'];
            $_SESSION['msg_type'] = "danger";
        }
    }
    header("Location: menu.php?kategori=" . urlencode($kategori_filter));
    exit;
}

// Proses Update Qty di Keranjang (Plus/Minus)
if (isset($_GET['action']) && isset($_GET['id'])) {
    $id = $_GET['id'];
    $action = $_GET['action'];
    
    if (isset($_SESSION['cart'][$id])) {
        if ($action == 'plus') {
            // Cek stok
            $stmt = $pdo->prepare("SELECT stok FROM produk WHERE id = ?");
            $stmt->execute([$id]);
            $stok = $stmt->fetchColumn();
            
            if ($_SESSION['cart'][$id]['qty'] < $stok) {
                $_SESSION['cart'][$id]['qty']++;
            } else {
                $_SESSION['msg'] = "Stok maksimal tercapai!";
                $_SESSION['msg_type'] = "danger";
            }
        } elseif ($action == 'minus') {
            $_SESSION['cart'][$id]['qty']--;
            if ($_SESSION['cart'][$id]['qty'] <= 0) {
                unset($_SESSION['cart'][$id]);
            }
        }
    }
    header("Location: menu.php?kategori=" . urlencode($kategori_filter));
    exit;
}

// (Blok kosongkan keranjang sudah dipindah ke atas)

// Query Produk Filter

// Query Produk
if ($kategori_filter == 'makanan') {
    $stmt = $pdo->prepare("SELECT * FROM produk WHERE kategori = 'makanan' ORDER BY nama ASC");
    $stmt->execute();
} elseif ($kategori_filter == 'minuman') {
    $stmt = $pdo->prepare("SELECT * FROM produk WHERE kategori = 'minuman' ORDER BY nama ASC");
    $stmt->execute();
} else {
    $stmt = $pdo->prepare("SELECT * FROM produk ORDER BY kategori, nama ASC");
    $stmt->execute();
}
$produk_list = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Menu - Kopi Kuningan</title>
    <link rel="stylesheet" href="assets/style.css">
</head>
<body>

<?php include 'includes/navbar.php'; ?>

<div class="container">
    <?php if(isset($_SESSION['msg'])): ?>
        <div class="alert alert-<?= $_SESSION['msg_type'] ?>">
            <?= $_SESSION['msg'] ?>
        </div>
        <?php 
        unset($_SESSION['msg']);
        unset($_SESSION['msg_type']);
        ?>
    <?php endif; ?>

    <div class="row">
        <!-- Kolom Kiri: Daftar Produk -->
        <div class="col-md-8">
            <div class="tabs">
                <a href="menu.php?kategori=semua" class="tab-btn <?= $kategori_filter == 'semua' ? 'active' : '' ?>">Semua</a>
                <a href="menu.php?kategori=makanan" class="tab-btn <?= $kategori_filter == 'makanan' ? 'active' : '' ?>">Makanan</a>
                <a href="menu.php?kategori=minuman" class="tab-btn <?= $kategori_filter == 'minuman' ? 'active' : '' ?>">Minuman</a>
            </div>

            <div class="product-grid">
                <?php foreach($produk_list as $p): ?>
                <div class="card">
                    <div class="card-img">
                        <?php if ($p['gambar'] && file_exists('assets/produk/' . $p['gambar'])): ?>
                            <img src="assets/produk/<?= $p['gambar'] ?>" alt="<?= $p['nama'] ?>" style="width:100%; height:100%; object-fit:cover;">
                        <?php else: ?>
                            <div style="font-size: 2rem; color: #ccc;">☕</div>
                        <?php endif; ?>
                    </div>
                    <div class="card-body">
                        <span class="card-category"><?= htmlspecialchars($p['kategori']) ?></span>
                        <h4 class="card-title" style="margin-bottom: 0.3rem;"><?= htmlspecialchars($p['nama']) ?></h4>
                        <?php if(!empty($p['deskripsi'])): ?>
                            <p style="font-size: 0.85rem; color: var(--text-muted); margin-bottom: 0.8rem; line-height: 1.4; font-family: var(--font-sans); display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden;">
                                <?= htmlspecialchars($p['deskripsi']) ?>
                            </p>
                        <?php else: ?>
                            <div style="margin-bottom: 0.8rem;"></div>
                        <?php endif; ?>
                        <div class="card-price">Rp <?= number_format($p['harga_jual'], 0, ',', '.') ?></div>
                        <div class="card-stock">Sisa stok: <?= $p['stok'] ?></div>
                        
                        <form method="POST" action="menu.php">
                            <input type="hidden" name="produk_id" value="<?= $p['id'] ?>">
                            <input type="hidden" name="qty" value="1">
                            <input type="hidden" name="kategori" value="<?= htmlspecialchars($kategori_filter) ?>">
                            <?php if($p['stok'] > 0): ?>
                                <button type="submit" name="add_to_cart" class="btn btn-primary btn-block">Tambah</button>
                            <?php else: ?>
                                <button type="button" class="btn btn-block disabled" disabled>Habis</button>
                            <?php endif; ?>
                        </form>
                    </div>
                </div>
                <?php endforeach; ?>
                
                <?php if(empty($produk_list)): ?>
                    <p>Tidak ada produk ditemukan.</p>
                <?php endif; ?>
            </div>
        </div>

        <!-- Kolom Kanan: Keranjang -->
        <div class="col-md-4">
            <div class="cart-panel">
                <h3>Keranjang Belanja</h3>
                <div class="cart-items">
                    <?php 
                    $total = 0;
                    if(!empty($_SESSION['cart'])): 
                        foreach($_SESSION['cart'] as $item_id => $item): 
                            $subtotal = $item['harga'] * $item['qty'];
                            $total += $subtotal;
                    ?>
                    <div class="cart-item">
                        <div class="cart-item-info">
                            <div class="cart-item-title"><?= htmlspecialchars($item['nama']) ?></div>
                            <div class="cart-item-price">Rp <?= number_format($item['harga'], 0, ',', '.') ?></div>
                        </div>
                        <div class="cart-item-actions">
                            <a href="menu.php?action=minus&id=<?= $item_id ?>&kategori=<?= urlencode($kategori_filter) ?>" class="qty-btn text-center text-decoration-none" style="display:inline-block; line-height:28px; text-decoration:none; color:black;">-</a>
                            <span><?= $item['qty'] ?></span>
                            <a href="menu.php?action=plus&id=<?= $item_id ?>&kategori=<?= urlencode($kategori_filter) ?>" class="qty-btn text-center text-decoration-none" style="display:inline-block; line-height:28px; text-decoration:none; color:black;">+</a>
                        </div>
                    </div>
                    <?php 
                        endforeach; 
                    else:
                    ?>
                    <p class="text-muted text-center" style="margin: 2rem 0;">Keranjang masih kosong</p>
                    <?php endif; ?>
                </div>
                
                <div class="cart-total-section">
                    <div class="cart-total-row">
                        <span>Total:</span>
                        <span>Rp <?= number_format($total, 0, ',', '.') ?></span>
                    </div>
                    
                    <?php if(!empty($_SESSION['cart'])): ?>
                    <a href="checkout.php" class="btn btn-success btn-block" style="margin-bottom: 0.5rem; text-decoration: none;">Proses Pembayaran</a>
                    <a href="menu.php?action=clear&kategori=<?= urlencode($kategori_filter) ?>" class="btn btn-danger btn-block" style="text-decoration: none; text-align: center; display: block; box-sizing: border-box;">Kosongkan</a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

</body>
</html>
