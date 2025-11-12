<?php
require_once "config.php";
date_default_timezone_set('Asia/Jakarta'); // Pastikan zona waktu diatur

$token = $_GET['token'] ?? '';
$password = $confirm_password = "";
$password_err = $confirm_password_err = "";
$message = $token_err = "";

// --- Variabel Baru ---
// Ini akan menampung error "password sama"
$general_err = ""; 

$is_token_valid = false;
$old_hashed_password = ""; 

if (empty($token)) {
    $token_err = "Token tidak ditemukan. Link tidak valid.";
} else {
    // Ambil ID dan HASH PASSWORD LAMA
    $sql = "SELECT id, password FROM users WHERE reset_token = ? AND reset_token_expires > NOW()";
    
    if ($stmt = $mysqli->prepare($sql)) {
        $stmt->bind_param("s", $token);
        $stmt->execute();
        $stmt->store_result();
        
        if ($stmt->num_rows == 1) {
            // Token valid, simpan hash lama
            $stmt->bind_result($user_id, $old_hashed_password);
            $stmt->fetch();
            $is_token_valid = true;
        } else {
            $token_err = "Token tidak valid atau sudah kedaluwarsa. Silakan minta link baru.";
        }
        $stmt->close();
    }
}

// Jika form disubmit (untuk mengubah password)
if ($_SERVER["REQUEST_METHOD"] == "POST" && $is_token_valid) {
    
    $token = $_POST['token'];

    // Validasi password baru (HANYA cek jika kosong atau < 6 karakter)
    if (empty(trim($_POST["password"]))) {
        $password_err = "Password tidak boleh kosong.";
    } elseif (strlen(trim($_POST["password"])) < 6) {
        $password_err = "Password minimal 6 karakter.";
    } else {
        $password = trim($_POST["password"]);
    }
    
    if (empty(trim($_POST["confirm_password"]))) {
        $confirm_password_err = "Silakan konfirmasi password.";
    } else {
        $confirm_password = trim($_POST["confirm_password"]);
        if (empty($password_err) && ($password != $confirm_password)) {
            $confirm_password_err = "Password tidak cocok.";
        }
    }

    // Jika validasi dasar lolos
    if (empty($password_err) && empty($confirm_password_err)) {
        
        // --- PERUBAHAN LOGIKA ---
        // Cek apakah password baru sama dengan password lama
        if (password_verify($password, $old_hashed_password)) {
            
            // JIKA SAMA: Set error umum, BUKAN $password_err
            $general_err = "Password baru tidak boleh sama dengan password lama Anda.";
            
        } else {
            
            // JIKA BEDA: Lanjutkan proses update
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            $sql = "UPDATE users SET password = ?, reset_token = NULL, reset_token_expires = NULL WHERE reset_token = ?";
            
            if ($stmt = $mysqli->prepare($sql)) {
                $stmt->bind_param("ss", $hashed_password, $token);
                
                if ($stmt->execute()) {
                    $message = "Password Anda berhasil diperbarui! Silakan login.";
                    $is_token_valid = false; // Sembunyikan form setelah sukses
                } else {
                    $message = "Terjadi kesalahan. Silakan coba lagi.";
                }
                $stmt->close();
            }
        }
        // --- AKHIR PERUBAHAN ---
    }
}
$mysqli->close();
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reset Password - MoneyGreat</title>
    <link rel="stylesheet" href="style.css">
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
                <h2>Reset Password</h2>
                <p>Masukkan password baru Anda.</p>
            </div>

            <?php 
            // Tampilkan pesan error token
            if (!empty($token_err)) {
                echo '<div class="material-alert error">';
                echo '    <div class="alert-icon"><svg viewBox="0 0 24 24" fill="currentColor"><path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm1 15h-2v-2h2v2zm0-4h-2V7h2v6z"/></svg></div>';
                echo '    <div class="alert-message">' . $token_err . '</div>';
                echo '</div>';
                echo '<div class="signup-link" style="margin-top: 20px;"><p><a href="forgot-password.php" class="create-account">Minta link baru</a></p></div>';
            }
            
            // --- BLOK BARU ---
            // Tampilkan error umum (misal: password sama)
            if (!empty($general_err)) {
                echo '<div class="material-alert error">';
                echo '    <div class="alert-icon">';
                echo '        <svg viewBox="0 0 24 24" fill="currentColor"><path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm1 15h-2v-2h2v2zm0-4h-2V7h2v6z"/></svg>';
                echo '    </div>';
                echo '    <div class="alert-message">';
                echo         htmlspecialchars($general_err); // Menampilkan error "password sama"
                echo '    </div>';
                echo '</div>';
            }
            
            // Tampilkan pesan sukses
            if (!empty($message)) {
                echo '<div class="material-alert" style="background-color: #e6f7ed; border-left-color: #34a853; color: #333;">';
                echo '    <div class="alert-icon" style="color: #34a853; font-weight: bold; font-size: 20px;">✓</div>';
                echo '    <div class="alert-message">' . $message . '</div>';
                echo '</div>';
                echo '<div class="signup-link" style="margin-top: 20px;"><p><a href="login.php" class="create-account">Login Sekarang</a></p></div>';
            }
            ?>
            
            <?php if ($is_token_valid && empty($message)): // Hanya tampilkan form jika token valid DAN belum sukses ?>
            <form class="login-form" id="resetForm" action="<?php echo htmlspecialchars($_SERVER["REQUEST_URI"]); ?>" method="post" novalidate>
                
                <input type="hidden" name="token" value="<?php echo htmlspecialchars($token); ?>">

                <div class="form-group">
                    <div class="input-wrapper password-wrapper">
                        <input type="password" id="password" name="password" required autocomplete="new-password">
                        <label for="password">Password Baru</label>
                        <div class="input-line"></div>
                        <button type="button" class="password-toggle" id="passwordToggle" aria-label="Toggle password visibility">
                            <div class="toggle-ripple"></div><span class="toggle-icon"></span>
                        </button>
                        <div class="ripple-container"></div>
                    </div>
                    <span class="error-message" id="passwordError"><?php echo $password_err; ?></span>
                </div>
                
                <div class="form-group">
                    <div class="input-wrapper password-wrapper">
                        <input type="password" id="confirm_password" name="confirm_password" required autocomplete="new-password">
                        <label for="confirm_password">Konfirmasi Password Baru</label>
                        <div class="input-line"></div>
                        <button type="button" class="password-toggle" id="confirmPasswordToggle" aria-label="Toggle password visibility">
                            <div class="toggle-ripple"></div><span class="toggle-icon"></span>
                        </button>
                        <div class="ripple-container"></div>
                    </div>
                    <span class="error-message" id="confirmPasswordError"><?php echo $confirm_password_err; ?></span>
                </div>

                <button type="submit" class="login-btn material-btn" style="margin-top: 20px;">
                    <div class="btn-ripple"></div>
                    <span class="btn-text">UBAH PASSWORD</span>
                </button>
            </form>
            <?php endif; ?>
            <div class="signup-link" style="margin-top: 30px;">
                <p>Sudah ingat passwordnya? <a href="login.php" class="create-account">Login di sini</p>
            </div>
        </div>
    </div>
    <script src="script.js"></script>
</body>
</html>