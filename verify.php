<?php
require_once "config.php";
date_default_timezone_set('Asia/Jakarta'); // Atur zona waktu

$message = "";
$msg_type = "error"; // Default tipe pesan adalah error

// Cek apakah token ada di URL
if (isset($_GET['token']) && !empty($_GET['token'])) {
    
    $token = $_GET['token'];

    // PERUBAHAN: Tambahkan cek waktu (verification_expires > NOW())
    $sql = "SELECT id FROM users WHERE verification_token = ? AND is_verified = 0 AND verification_expires > NOW()";
    
    if ($stmt = $mysqli->prepare($sql)) {
        $stmt->bind_param("s", $token);
        
        if ($stmt->execute()) {
            $stmt->store_result();
            
            // Jika token ditemukan dan belum diverifikasi
            if ($stmt->num_rows == 1) {
                // Token valid, verifikasi akun
                // Hapus token DAN waktu kedaluwarsanya
                $update_sql = "UPDATE users SET is_verified = 1, verification_token = NULL, verification_expires = NULL WHERE verification_token = ?";

                if ($update_stmt = $mysqli->prepare($update_sql)) {
                    $update_stmt->bind_param("s", $token);
                    
                    if ($update_stmt->execute()) {
                        $message = "Verifikasi akun berhasil! Anda sekarang bisa login.";
                        $msg_type = "success";
                    } else {
                        $message = "Gagal memperbarui status verifikasi.";
                    }
                    $update_stmt->close();
                }
            } else {
                $message = "Token verifikasi tidak valid atau sudah kedaluwarsa.";
            }
        } else {
            $message = "Terjadi kesalahan. Silakan coba lagi.";
        }
        $stmt->close();
    }
} else {
    $message = "Token verifikasi tidak ditemukan.";
}

$mysqli->close();
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Verifikasi Akun - MoneyGreat</title>
    <link rel="stylesheet" href="style.css">
    <style>
        .verification-card { max-width: 500px; margin: 50px auto; text-align: center; }
    </style>
</head>
<body>
    <div class="login-container">
        <div class="login-card">
            <div class="login-header">
                <div class="material-logo">
                    <div class="logo-layers">
                        <div class="layer layer-1"></div>
                        <div class="layer layer-2"></div>
                        <div class="layer layer-3"></div>
                    </div>
                </div>
                <h2>Verifikasi</h2>
                <p>MoneyGreat</p>
            <?php 
            // Tampilkan pesan sukses (hijau)
            if ($msg_type == "success") {
                echo '<div class="material-alert" style="background-color: #e6f7ed; border-left-color: #34a853; color: #333;">';
                echo '    <div class="alert-icon" style="color: #34a853; font-weight: bold; font-size: 20px;">✓</div>';
                echo '    <div class="alert-message">' . $message . '</div>';
                echo '</div>';
                echo '<a href="login.php" class="login-btn material-btn" style="text-decoration: none; max-width: 200px; margin: 20px auto;">';
                echo '    <span class="btn-text">LOGIN SEKARANG</span>';
                echo '</a>';
            } else {
            // Tampilkan pesan error (merah)
                echo '<div class="material-alert error">';
                echo '    <div class="alert-icon"><svg viewBox="0 0 24 24" fill="currentColor"><path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm1 15h-2v-2h2v2zm0-4h-2V7h2v6z"/></svg></div>';
                echo '    <div class="alert-message">' . $message . '</div>';
                echo '</div>';
            }
            ?>

        </div>
    </div>
</body>
</html>