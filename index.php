<?php
session_start();
require 'config.php';

if (isset($_SESSION['user_id'])) {
    if ($_SESSION['role'] === 'admin') {
        header("Location: rekap.php");
        exit;
    }

    if ($_SESSION['role'] === 'pembeli') {
        header("Location: menu.php");
        exit;
    }
}

// Ambil 4 produk unggulan
$stmt = $pdo->prepare("SELECT * FROM produk ORDER BY id ASC LIMIT 4");
$stmt->execute();
$unggulan = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Beranda - Kopi Kuningan</title>
    <link rel="stylesheet" href="assets/style.css">
    <style>
        .hero {
            position: relative;
            /* Ganti URL di bawah dengan foto asli warung jika sudah ada */
            background-image: url('https://images.unsplash.com/photo-1554118811-1e0d58224f24?auto=format&fit=crop&q=80&w=1200'); 
            background-size: cover;
            background-position: center;
            height: 60vh;
            display: flex;
            align-items: center;
            justify-content: center;
            text-align: center;
            color: #F5F0E6;
            margin-bottom: 3rem;
            border: 4px solid var(--border-color);
            border-radius: 4px;
            box-shadow: 6px 6px 0 var(--border-color);
        }
        .hero::before {
            content: '';
            position: absolute;
            top: 0; left: 0; right: 0; bottom: 0;
            background: rgba(28, 27, 24, 0.75); /* overlay gelap agar teks terbaca */
        }
        .hero-content {
            position: relative;
            z-index: 1;
            padding: 2rem;
        }
        .hero-title {
            font-size: 4rem;
            margin-bottom: 0.5rem;
            color: var(--accent-primary);
            text-shadow: 4px 4px 0 var(--border-color);
            letter-spacing: -2px;
        }
        .hero-tagline {
            font-size: 1.2rem;
            margin-bottom: 2.5rem;
            font-family: var(--font-sans);
            font-weight: 500;
            color: #FFF;
        }
        .section-title {
            text-align: center;
            font-size: 2rem;
            margin-bottom: 2rem;
            text-transform: uppercase;
            border-bottom: 4px solid var(--border-color);
            padding-bottom: 0.5rem;
            display: inline-block;
        }
        .about-section {
            background-color: var(--surface);
            padding: 4rem 2rem;
            border: 2px solid var(--border-color);
            box-shadow: 6px 6px 0 var(--border-color);
            margin-bottom: 3rem;
            text-align: center;
        }
        .footer {
            background-color: var(--text-main);
            color: var(--surface);
            padding: 2.5rem 1rem;
            text-align: center;
            margin-top: 4rem;
            border-top: 6px solid var(--accent-primary);
            font-family: var(--font-mono);
        }
    </style>
</head>
<body>

<?php include 'includes/navbar.php'; ?>

<div class="container">
    <!-- Hero Section -->
    <div class="hero">
        <div class="hero-content">
            <h1 class="hero-title" style="display: flex; align-items: center; justify-content: center; gap: 1rem;">
                <svg style="transform: translateY(-5px);" width="64" height="64" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M18 8h1a4 4 0 0 1 0 8h-1"></path>
                    <path d="M2 8h16v9a4 4 0 0 1-4 4H6a4 4 0 0 1-4-4V8z"></path>
                    <line x1="6" y1="1" x2="6" y2="4"></line>
                    <line x1="10" y1="1" x2="10" y2="4"></line>
                    <line x1="14" y1="1" x2="14" y2="4"></line>
                </svg>
                KOPI KUNINGAN
            </h1>
            <p class="hero-tagline">Cita Rasa Otentik, Secangkir Kenangan dari Masa Lalu.</p>
            <a href="login.php" class="btn btn-primary" style="font-size: 1.2rem; padding: 1rem 2rem;">Login untuk Memesan</a>
        </div>
    </div>

    <!-- Tentang Kami -->
    <div class="about-section">
        <div style="text-align: center; margin-bottom: 1rem;">
            <h2 class="section-title" style="margin: 0 auto 1.5rem auto;">TENTANG KAMI</h2>
        </div>
        <p style="max-width: 800px; margin: 0 auto; font-size: 1.15rem; line-height: 1.8; font-family: var(--font-sans);">
            Berawal dari resep seduhan turun-temurun, <strong>Kopi Kuningan</strong> hadir untuk menyajikan pengalaman minum kopi yang jujur dan otentik. 
            Di tengah bisingnya kedai modern, kami mempertahankan nuansa warung tradisional di mana setiap tegukan membawa Anda kembali 
            pada kehangatan obrolan santai dan kebersamaan yang tak lekang oleh waktu.
        </p>
    </div>

    <!-- Produk Unggulan -->
    <div style="text-align: center; margin-bottom: 2rem;">
        <h2 class="section-title">PILIHAN UNGGULAN</h2>
    </div>
    <div class="product-grid" style="margin-bottom: 4rem;">
        <?php foreach($unggulan as $p): ?>
        <a href="menu.php" style="text-decoration: none; color: inherit; display: block;">
            <div class="card" style="box-shadow: 4px 4px 0 var(--border-color); transform: none; height: 100%;">
                <div class="card-img">
                    <?php if ($p['gambar'] && file_exists('assets/produk/' . $p['gambar'])): ?>
                        <!-- Gambar asli dari database/upload yang sudah disiapkan sebelumnya -->
                        <img src="assets/produk/<?= $p['gambar'] ?>" alt="<?= htmlspecialchars($p['nama']) ?>">
                    <?php else: ?>
                        <!-- Placeholder URL gambar online (Unsplash) jika belum ada gambar lokal -->
                        <img src="https://images.unsplash.com/photo-1509042239860-f550ce710b93?auto=format&fit=crop&q=80&w=400" alt="Produk Kopi Placeholder">
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
                </div>
            </div>
        </a>
        <?php endforeach; ?>
    </div>

    <!-- Jam Operasional & Lokasi -->
    <div class="row" style="margin-bottom: 2rem;">
        <div class="col-md-6" style="flex:1;">
            <div class="card card-table h-100" style="text-align: center; padding: 3rem 2rem;">
                <h3 style="margin-bottom: 1.5rem; text-decoration: underline; text-underline-offset: 4px;">JAM BUKA</h3>
                <p style="font-size: 1.25rem; font-weight: 700; font-family: var(--font-mono); line-height: 1.6;">
                    Setiap Hari<br>
                    08.00 - 22.00 WIB
                </p>
            </div>
        </div>
        <div class="col-md-6" style="flex:1;">
            <div class="card card-table h-100" style="text-align: center; padding: 3rem 2rem;">
                <h3 style="margin-bottom: 1.5rem; text-decoration: underline; text-underline-offset: 4px;">LOKASI KAMI</h3>
                <p style="font-size: 1.1rem; font-family: var(--font-sans); font-weight: 500; line-height: 1.6;">
                    Jl. Meruya Selatan No. 99<br>
                    (Area Kampus UMB)<br>
                    Kembangan, Jakarta Barat
                </p>
            </div>
        </div>
    </div>
</div>

<div class="footer">
    <p style="font-size: 1.2rem; margin-bottom: 0.5rem;">WARUNG KOPI KUNINGAN</p>
    <p style="font-family: var(--font-sans); font-size: 0.9rem; margin-bottom: 1rem;">Hak Cipta Dilindungi &copy; 2026</p>
    <p style="color: var(--accent-primary); font-weight: bold;">Instagram: @kopikuningan | WA: 0812-0000-0000</p>
</div>

</body>
</html>
