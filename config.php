<?php
// config.php - Konfigurasi koneksi database PDO
// Cek apakah ada environment variables dari Railway/Cloud, jika tidak gunakan default XAMPP
$host = getenv('MYSQLHOST') ?: 'localhost';
$port = getenv('MYSQLPORT') ?: '3306';
$dbname = getenv('MYSQLDATABASE') ?: 'kopi_kuningan';
$username = getenv('MYSQLUSER') ?: 'root'; 
$password = getenv('MYSQLPASSWORD') ?: ''; 

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
