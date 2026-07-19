<?php
// config.php - Konfigurasi koneksi database PDO
// Cek apakah ada environment variables dari Railway/Cloud, jika tidak gunakan default XAMPP

// Railway kadang menggunakan MYSQL_URL (format: mysql://user:pass@host:port/dbname)
$mysqlUrl = getenv('MYSQL_URL') ?: getenv('DATABASE_URL');

if ($mysqlUrl) {
    $dbparts = parse_url($mysqlUrl);
    $host = $dbparts['host'];
    $port = isset($dbparts['port']) ? $dbparts['port'] : '3306';
    $dbname = ltrim($dbparts['path'], '/');
    $username = $dbparts['user'];
    $password = isset($dbparts['pass']) ? $dbparts['pass'] : '';
} else {
    // Jika tidak ada URL, gunakan variabel terpisah (atau fallback ke lokal)
    $host = getenv('MYSQLHOST') ?: 'localhost';
    $port = getenv('MYSQLPORT') ?: '3306';
    $dbname = getenv('MYSQLDATABASE') ?: 'kopi_kuningan';
    $username = getenv('MYSQLUSER') ?: 'root'; 
    $password = getenv('MYSQLPASSWORD') ?: ''; 
}

try {
    $pdo = new PDO("mysql:host=$host;port=$port;dbname=$dbname;charset=utf8mb4", $username, $password);
    // Set error mode exception agar mudah melacak error SQL
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    // Mengembalikan data sebagai associative array secara default
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die("Koneksi database gagal: " . $e->getMessage());
}
?>
