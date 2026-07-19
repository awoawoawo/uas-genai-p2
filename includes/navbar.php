<?php
// includes/navbar.php
$current_page = basename($_SERVER['PHP_SELF']);
$home_url = 'index.php';
if (isset($_SESSION['role'])) {
    $home_url = ($_SESSION['role'] === 'admin') ? 'rekap.php' : 'menu.php';
}
?>
<nav class="navbar">
    <div class="nav-container">
        <a href="<?= $home_url ?>" class="nav-brand">
            <!-- Simple SVG Icon Coffee -->
            <svg style="transform: translateY(-2px);" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <path d="M18 8h1a4 4 0 0 1 0 8h-1"></path>
                <path d="M2 8h16v9a4 4 0 0 1-4 4H6a4 4 0 0 1-4-4V8z"></path>
                <line x1="6" y1="1" x2="6" y2="4"></line>
                <line x1="10" y1="1" x2="10" y2="4"></line>
                <line x1="14" y1="1" x2="14" y2="4"></line>
            </svg>
            Kopi Kuningan
        </a>
        <?php if(isset($_SESSION['user_id'])): ?>
        <ul class="nav-links">
            <?php if($_SESSION['role'] == 'admin'): ?>
                <li><a href="stok.php" class="<?= $current_page == 'stok.php' ? 'active' : '' ?>">Kelola Stok</a></li>
                <li><a href="rekap.php" class="<?= $current_page == 'rekap.php' ? 'active' : '' ?>">Rekap Keuntungan</a></li>
            <?php else: ?>
                <li><a href="menu.php" class="<?= ($current_page == 'menu.php' || $current_page == 'checkout.php') ? 'active' : '' ?>">Menu</a></li>
            <?php endif; ?>
            <li><a href="logout.php">Logout</a></li>
        </ul>
        <?php endif; ?>
    </div>
</nav>
