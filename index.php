<?php
session_start(); // Mulai session

// Cek jika pengguna sudah login, redirect ke dashboard
if (isset($_SESSION["loggedin"]) && $_SESSION["loggedin"] === true) {
    header("location: dashboard.php");
    exit; // Penting untuk menghentikan eksekusi sisa halaman
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Selamat Datang di MoneyGreat</title>
    <link rel="stylesheet" href="style.css">
    
    <style>
        /* Mengatur kontainer utama */
        .welcome-card {
            text-align: center;
            padding-top: 20px;
        }
        
        /* Mengatur header */
        .welcome-card .login-header {
            margin-bottom: 30px; /* Beri jarak lebih */
        }
        .welcome-card .tagline {
            font-size: 16px;
            color: #555;
            margin-bottom: 35px; /* Jarak sebelum konten */
        }

        /* Konten informatif (Baru) */
        .welcome-content {
            text-align: left; /* Ratakan kiri untuk list */
            color: #333;
            margin-bottom: 35px; /* Jarak sebelum tombol */
            padding: 0 10px; /* Beri padding agar tidak terlalu mepet */
        }
        .welcome-content h3 {
            font-size: 18px;
            font-weight: 500;
            color: #000;
            margin-bottom: 15px;
            text-align: center; /* Judul list tetap di tengah */
        }
        .welcome-content ul {
            list-style: none; /* Hilangkan bullet standar */
            padding: 0;
            margin: 0;
        }
        .welcome-content li {
            font-size: 16px;
            margin-bottom: 12px;
            color: #555;
            display: flex;
            align-items: center;
        }
        /* Penanda list kustom (seperti centang) */
        .welcome-content li::before {
            content: '✓'; /* Ganti dengan SVG jika Anda punya */
            color: #34A853; /* Hijau */
            font-weight: bold;
            font-size: 20px;
            margin-right: 12px;
        }

        /* Mengatur tombol */
        .welcome-card .login-btn {
            margin-bottom: 15px;
            text-decoration: none;
            display: block;
            text-align: center;
        }
        .btn-outline {
            background: #fff;
            color: #4285F4;
            border: 1px solid #dadce0;
        }
        .btn-outline:hover {
            background: #f8f9fa;
        }
        .btn-outline .btn-text {
            color: #4285F4;
        }
    </style>
</head>
<body>
    <div class="login-container">
        <div class="login-card welcome-card">
            
            <div class="login-header">
                <div class="material-logo">
                    <div class="logo-layers">
                        <div class="layer layer-1"></div>
                        <div class="layer layer-2"></div>
                        <div class="layer layer-3"></div>
                    </div>
                </div>
                <h2>Selamat Datang di MoneyGreat</h2>
                <p class="tagline">Solusi cerdas untuk mengelola keuangan pribadi Anda.</p>
            </div>

            <div class="welcome-content">
                <h3>Kenapa Menggunakan MoneyGreat?</h3>
                <ul>
                    <li>Mencatat setiap pemasukan dengan mudah</li>
                    <li>Melacak semua pengeluaran harian Anda</li>
                    <li>Membantu Anda memahami kebiasaan finansial</li>
                    <li>Antarmuka yang bersih dan sederhana</li>
                </ul>
            </div>
            <a href="login.php" class="login-btn material-btn">
                <div class="btn-ripple"></div>
                <span class="btn-text">Masuk</span>
            </a>
            
            <a href="daftar.php" class="login-btn material-btn btn-outline">
                <div class="btn-ripple"></div>
                <span class="btn-text">Buat Akun</span>
            </a>

        </div>
    </div>
</body>
</html>