<?php
// config/db.php
$host = 'localhost';
$user = 'root';
$password = '';
$dbname = 'akademik06995';

// Buat koneksi mysqli
$conn = new mysqli($host, $user, $password, $dbname);

// Cek koneksi
if ($conn->connect_error) {
    die("Koneksi gagal: " . $conn->connect_error);
}
