<?php
// Konfigurasi Database
define('DB_SERVER', 'localhost');
define('DB_USERNAME', 'root'); // Ganti jika username database Anda bukan 'root'
define('DB_PASSWORD', '');     // Ganti jika Anda memiliki password untuk database
define('DB_NAME', 'moneygreat_db'); // Nama database yang Anda buat

// Membuat koneksi ke database
$mysqli = new mysqli(DB_SERVER, DB_USERNAME, DB_PASSWORD, DB_NAME);

// Cek koneksi
if ($mysqli === false) {
    die("ERROR: Tidak bisa terhubung ke database. " . $mysqli->connect_error);
}
?>