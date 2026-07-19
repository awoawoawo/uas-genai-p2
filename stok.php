<?php
session_start();
require 'config.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: login.php");
    exit;
}

$action = isset($_GET['action']) ? $_GET['action'] : 'list';

// --- PROSES TAMBAH PRODUK ---
if (isset($_POST['tambah_produk'])) {
    $nama = $_POST['nama'];
    $kategori = $_POST['kategori'];
    $deskripsi = $_POST['deskripsi'];
    $harga_jual = (int)$_POST['harga_jual'];
    $harga_modal = (int)$_POST['harga_modal'];
    $stok = (int)$_POST['stok'];
    $gambar = null;

    // Handle upload gambar
    if (isset($_FILES['gambar']) && $_FILES['gambar']['error'] == 0) {
        $ext = pathinfo($_FILES['gambar']['name'], PATHINFO_EXTENSION);
        $gambar = time() . '_' . rand(100,999) . '.' . $ext;
        move_uploaded_file($_FILES['gambar']['tmp_name'], 'assets/produk/' . $gambar);
    }

    $stmt = $pdo->prepare("INSERT INTO produk (nama, deskripsi, kategori, harga_jual, harga_modal, stok, gambar) VALUES (?, ?, ?, ?, ?, ?, ?)");
    $stmt->execute([$nama, $deskripsi, $kategori, $harga_jual, $harga_modal, $stok, $gambar]);
    
    $_SESSION['msg'] = "Produk berhasil ditambahkan!";
    $_SESSION['msg_type'] = "success";
    header("Location: stok.php");
    exit;
}

// --- PROSES EDIT PRODUK ---
if (isset($_POST['edit_produk'])) {
    $id = $_POST['id'];
    $nama = $_POST['nama'];
    $deskripsi = $_POST['deskripsi'];
    $kategori = $_POST['kategori'];
    $harga_jual = (int)$_POST['harga_jual'];
    $harga_modal = (int)$_POST['harga_modal'];
    $stok = (int)$_POST['stok'];
    
    // Ambil data lama
    $stmt = $pdo->prepare("SELECT gambar FROM produk WHERE id = ?");
    $stmt->execute([$id]);
    $lama = $stmt->fetch();
    $gambar = $lama['gambar'];

    // Handle upload gambar jika ada
    if (isset($_FILES['gambar']) && $_FILES['gambar']['error'] == 0) {
        $ext = pathinfo($_FILES['gambar']['name'], PATHINFO_EXTENSION);
        $gambar = time() . '_' . rand(100,999) . '.' . $ext;
        move_uploaded_file($_FILES['gambar']['tmp_name'], 'assets/produk/' . $gambar);
        
        // Hapus gambar lama jika ada
        if ($lama['gambar'] && file_exists('assets/produk/' . $lama['gambar'])) {
            unlink('assets/produk/' . $lama['gambar']);
        }
    }

    $stmt = $pdo->prepare("UPDATE produk SET nama=?, deskripsi=?, kategori=?, harga_jual=?, harga_modal=?, stok=?, gambar=? WHERE id=?");
    $stmt->execute([$nama, $deskripsi, $kategori, $harga_jual, $harga_modal, $stok, $gambar, $id]);
    
    $_SESSION['msg'] = "Produk berhasil diupdate!";
    $_SESSION['msg_type'] = "success";
    header("Location: stok.php");
    exit;
}

// --- PROSES HAPUS PRODUK ---
if (isset($_GET['hapus'])) {
    $id = $_GET['hapus'];
    // Hapus gambar
    $stmt = $pdo->prepare("SELECT gambar FROM produk WHERE id = ?");
    $stmt->execute([$id]);
    $p = $stmt->fetch();
    if ($p && $p['gambar'] && file_exists('assets/produk/' . $p['gambar'])) {
        unlink('assets/produk/' . $p['gambar']);
    }
    
    $stmt = $pdo->prepare("DELETE FROM produk WHERE id = ?");
    $stmt->execute([$id]);
    
    $_SESSION['msg'] = "Produk berhasil dihapus!";
    $_SESSION['msg_type'] = "success";
    header("Location: stok.php");
    exit;
}

// Ambil filter kategori
$kategori_filter = isset($_GET['kategori']) ? $_GET['kategori'] : 'semua';
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kelola Stok - Kopi Kuningan</title>
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

    <?php if ($action == 'list'): ?>
    
    <div class="d-flex justify-content-between align-items-center mb-2">
        <h2 style="color: var(--primary-dark);">Daftar Produk & Stok</h2>
    </div>

    <div class="card card-table">
        <div class="tabs" style="margin-bottom: 1rem; border-bottom: none;">
            <span style="padding: 0.5rem 0; font-weight:600; color:var(--text-muted);">Filter Kategori:</span>
            <a href="stok.php?kategori=semua" class="tab-btn <?= $kategori_filter == 'semua' ? 'active' : '' ?>">Semua</a>
            <a href="stok.php?kategori=makanan" class="tab-btn <?= $kategori_filter == 'makanan' ? 'active' : '' ?>">Makanan</a>
            <a href="stok.php?kategori=minuman" class="tab-btn <?= $kategori_filter == 'minuman' ? 'active' : '' ?>">Minuman</a>
        </div>

        <div class="table-responsive">
            <table>
                <thead>
                    <tr>
                        <th>Gambar</th>
                        <th>Nama Produk</th>
                        <th>Kategori</th>
                        <th>Harga Modal</th>
                        <th>Harga Jual</th>
                        <th>Stok</th>
                        <th>Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
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
                    
                    foreach($produk_list as $p):
                    ?>
                    <tr>
                        <td>
                            <?php if($p['gambar'] && file_exists('assets/produk/' . $p['gambar'])): ?>
                                <img src="assets/produk/<?= $p['gambar'] ?>" alt="Img" style="width:50px; height:50px; object-fit:cover; border-radius:4px;">
                            <?php else: ?>
                                <div style="width:50px; height:50px; background:#EEE; border-radius:4px; display:flex; align-items:center; justify-content:center; color:#ccc;">☕</div>
                            <?php endif; ?>
                        </td>
                        <td style="font-weight:600;"><?= htmlspecialchars($p['nama']) ?></td>
                        <td>
                            <span class="badge badge-<?= $p['kategori'] ?>"><?= $p['kategori'] ?></span>
                        </td>
                        <td>Rp <?= number_format($p['harga_modal'], 0, ',', '.') ?></td>
                        <td>Rp <?= number_format($p['harga_jual'], 0, ',', '.') ?></td>
                        <td>
                            <strong style="color: <?= $p['stok'] > 5 ? 'var(--success-color)' : 'var(--danger-color)' ?>">
                                <?= $p['stok'] ?>
                            </strong>
                        </td>
                        <td>
                            <div class="d-flex gap-2">
                                <a href="stok.php?action=edit&id=<?= $p['id'] ?>" class="btn" style="background:#2196F3; color:white; padding:0.4rem 0.8rem; font-size:0.85rem;">Edit</a>
                                <a href="stok.php?hapus=<?= $p['id'] ?>" class="btn btn-danger" style="padding:0.4rem 0.8rem; font-size:0.85rem;" onclick="return confirm('Yakin ingin menghapus produk ini?')">Hapus</a>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                    
                    <?php if(empty($produk_list)): ?>
                    <tr>
                        <td colspan="7" class="text-center" style="padding: 2rem 0; color: var(--text-muted);">Tidak ada data produk.</td>
                    </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
    
    <?php elseif ($action == 'add'): ?>
    
    <div class="card card-table" style="max-width: 600px; margin: 0 auto;">
        <h2 style="color: var(--primary-dark); border-bottom: 1px solid var(--border-color); padding-bottom:1rem; margin-bottom:1.5rem;">Tambah Produk Baru</h2>
        
        <form method="POST" action="stok.php" enctype="multipart/form-data">
            <div class="form-group">
                <label class="form-label">Nama Produk *</label>
                <input type="text" name="nama" class="form-control" required placeholder="Contoh: Kopi Susu Aren">
            </div>
            
            <div class="form-group">
                <label class="form-label">Deskripsi Singkat</label>
                <textarea name="deskripsi" class="form-control" rows="2" placeholder="Contoh: Kopi susu manis dengan gula aren asli"></textarea>
            </div>
            
            <div class="form-group">
                <label class="form-label">Kategori *</label>
                <select name="kategori" class="form-control" required>
                    <option value="">-- Pilih Kategori --</option>
                    <option value="makanan">Makanan</option>
                    <option value="minuman">Minuman</option>
                </select>
            </div>
            
            <div class="row">
                <div class="col-md-6" style="flex:1;">
                    <div class="form-group">
                        <label class="form-label">Harga Modal (Rp) *</label>
                        <input type="number" name="harga_modal" class="form-control" required min="0">
                    </div>
                </div>
                <div class="col-md-6" style="flex:1;">
                    <div class="form-group">
                        <label class="form-label">Harga Jual (Rp) *</label>
                        <input type="number" name="harga_jual" class="form-control" required min="0">
                    </div>
                </div>
            </div>
            
            <div class="form-group">
                <label class="form-label">Stok Awal *</label>
                <input type="number" name="stok" class="form-control" required min="0">
            </div>
            
            <div class="form-group">
                <label class="form-label">Gambar Produk (Opsional)</label>
                <input type="file" name="gambar" class="form-control" accept="image/*">
                <small style="color: var(--text-muted);">Format: JPG, PNG, GIF</small>
            </div>
            
            <div class="d-flex gap-2" style="margin-top: 2rem;">
                <a href="stok.php" class="btn" style="border: 1px solid var(--border-color); color: var(--text-muted); background: white;">Batal</a>
                <button type="submit" name="tambah_produk" class="btn btn-primary">Simpan Produk</button>
            </div>
        </form>
    </div>

    <?php elseif ($action == 'edit' && isset($_GET['id'])): 
        $stmt = $pdo->prepare("SELECT * FROM produk WHERE id = ?");
        $stmt->execute([$_GET['id']]);
        $p = $stmt->fetch();
        if(!$p) die("Produk tidak ditemukan.");
    ?>
    
    <div class="card card-table" style="max-width: 600px; margin: 0 auto;">
        <h2 style="color: var(--primary-dark); border-bottom: 1px solid var(--border-color); padding-bottom:1rem; margin-bottom:1.5rem;">Edit Produk</h2>
        
        <form method="POST" action="stok.php" enctype="multipart/form-data">
            <input type="hidden" name="id" value="<?= $p['id'] ?>">
            
            <div class="form-group">
                <label class="form-label">Nama Produk *</label>
                <input type="text" name="nama" class="form-control" required value="<?= htmlspecialchars($p['nama']) ?>">
            </div>
            
            <div class="form-group">
                <label class="form-label">Deskripsi Singkat</label>
                <textarea name="deskripsi" class="form-control" rows="2" placeholder="Contoh: Kopi susu manis dengan gula aren asli"><?= htmlspecialchars($p['deskripsi'] ?? '') ?></textarea>
            </div>
            
            <div class="form-group">
                <label class="form-label">Kategori *</label>
                <select name="kategori" class="form-control" required>
                    <option value="makanan" <?= $p['kategori'] == 'makanan' ? 'selected' : '' ?>>Makanan</option>
                    <option value="minuman" <?= $p['kategori'] == 'minuman' ? 'selected' : '' ?>>Minuman</option>
                </select>
            </div>
            
            <div class="row">
                <div class="col-md-6" style="flex:1;">
                    <div class="form-group">
                        <label class="form-label">Harga Modal (Rp) *</label>
                        <input type="number" name="harga_modal" class="form-control" required min="0" value="<?= $p['harga_modal'] ?>">
                    </div>
                </div>
                <div class="col-md-6" style="flex:1;">
                    <div class="form-group">
                        <label class="form-label">Harga Jual (Rp) *</label>
                        <input type="number" name="harga_jual" class="form-control" required min="0" value="<?= $p['harga_jual'] ?>">
                    </div>
                </div>
            </div>
            
            <div class="form-group">
                <label class="form-label">Stok * (Ubah untuk update manual)</label>
                <input type="number" name="stok" class="form-control" required min="0" value="<?= $p['stok'] ?>">
            </div>
            
            <div class="form-group">
                <label class="form-label">Update Gambar Produk (Opsional)</label>
                <?php if($p['gambar']): ?>
                    <div style="margin-bottom:0.5rem;">
                        <img src="assets/produk/<?= $p['gambar'] ?>" alt="Img" style="width:100px; border-radius:6px; border:1px solid #ddd;">
                    </div>
                <?php endif; ?>
                <input type="file" name="gambar" class="form-control" accept="image/*">
                <small style="color: var(--text-muted);">Biarkan kosong jika tidak ingin mengubah gambar.</small>
            </div>
            
            <div class="d-flex gap-2" style="margin-top: 2rem;">
                <a href="stok.php" class="btn" style="border: 1px solid var(--border-color); color: var(--text-muted); background: white;">Batal</a>
                <button type="submit" name="edit_produk" class="btn btn-primary">Update Produk</button>
            </div>
        </form>
    </div>

    <?php endif; ?>
</div>

</body>
</html>
