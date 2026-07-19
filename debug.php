<?php
echo "<h3>Informasi Variabel Environment:</h3>";
echo "MYSQL_URL: " . (getenv('MYSQL_URL') ? 'Ada' : 'TIDAK ADA') . "<br>";
echo "DATABASE_URL: " . (getenv('DATABASE_URL') ? 'Ada' : 'TIDAK ADA') . "<br>";
echo "MYSQLHOST: " . (getenv('MYSQLHOST') ? getenv('MYSQLHOST') : 'TIDAK ADA') . "<br>";
echo "MYSQLUSER: " . (getenv('MYSQLUSER') ? getenv('MYSQLUSER') : 'TIDAK ADA') . "<br>";

echo "<h3>Cek Koneksi:</h3>";
require 'config.php';
echo "<h2 style='color:green;'>Koneksi Sukses!</h2>";
?>
