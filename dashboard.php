<?php
// Selalu mulai session di paling atas
session_start();

// --- PEMERIKSAAN WAKTU INAKTIF (15 MENIT) ---
// Cek apakah 'expire_time' ada di session (berarti ini adalah session 15 menit)
if (isset($_SESSION['expire_time']) && isset($_SESSION['last_activity'])) {
    
    // Hitung selisih waktu
    $waktu_inaktif = time() - $_SESSION['last_activity'];
    
    if ($waktu_inaktif > $_SESSION['expire_time']) {
        // Jika inaktif lebih dari 15 menit, hancurkan session
        session_unset();
        session_destroy();
        
        // Arahkan kembali ke login (Anda bisa tambahkan pesan)
        header("Location: login.php?status=session_expired");
        exit;
    } else {
        // Jika masih aktif, perbarui 'waktu aktivitas terakhir'
        $_SESSION['last_activity'] = time();
    }
}

// --- PEMERIKSAAN LOGIN STANDAR ---
// Cek apakah pengguna sudah login, jika tidak, redirect ke halaman login
if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true) {
    header("location: login.php");
    exit;
}

// Jika lolos semua pemeriksaan, halaman dashboard akan tampil
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Dashboard - MoneyGreat</title>
    <style>
        body { font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif; display: grid; place-items: center; min-height: 100vh; background-color: #f4f7f6; }
        .dashboard-container { background: #fff; padding: 40px; border-radius: 12px; box-shadow: 0 4px 12px rgba(0,0,0,0.05); text-align: center; }
        .logout-btn { display: inline-block; margin-top: 20px; padding: 10px 18px; background-color: #d93025; color: white; text-decoration: none; border-radius: 8px; font-weight: 500; }
    </style>
</head>
<body>

    <div class="dashboard-container">
        <h1>Halo, <b><?php echo htmlspecialchars($_SESSION["nama"]); ?></b>!</h1>
        <p>Selamat datang di dashboard MoneyGreat Anda.</p>
        
        <?php 
        // Tampilkan pesan jika session 15 menit
        if (isset($_SESSION['expire_time'])) {
            echo "<p style='color: #555; font-size: 0.9em;'><i>(Anda akan logout otomatis setelah 15 menit tidak aktif.)</i></p>";
        }
        ?>
        
        <a href="logout.php" class="logout-btn">Logout</a>
    </div>

</body>
</html>