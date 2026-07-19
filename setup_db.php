<?php
require 'config.php';

echo "Memulai import database...<br>";

try {
    $sql = file_get_contents('kopi_kuningan.sql');
    
    // Eksekusi raw SQL (mendukung multi-statement di PDO)
    $pdo->exec($sql);
    
    echo "<h2 style='color:green;'>Database berhasil di-import ke Railway!</h2>";
    echo "<p>Tabel-tabel sudah selesai dibuat.</p>";
    echo "<a href='index.php'>Buka Web Anda</a>";

} catch (PDOException $e) {
    echo "<h2 style='color:red;'>Error saat import: " . $e->getMessage() . "</h2>";
}
?>
