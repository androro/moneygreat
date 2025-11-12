<?php
// JANGAN panggil session_start() di sini
require_once "config.php";

$username = $password = "";
$username_err = $password_err = $login_err = "";

// Cek jika sudah login dari session sebelumnya
session_start();
if (isset($_SESSION["loggedin"]) && $_SESSION["loggedin"] === true) {
    header("location: dashboard.php");
    exit;
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {

    // --- AWAL VERIFIKASI TURNSTILE ---
    $turnstile_secret = "0x4AAAAAAB_K2M2LWkdBHjBi1CHCQFg-HgM"; // GANTI DENGAN KUNCI RAHASIA ANDA
    $turnstile_response = $_POST['cf-turnstile-response'] ?? '';
    $turnstile_valid = false;
    $login_err = ""; // Reset login error

    if (empty($turnstile_response)) {
        $login_err = "Verifikasi CAPTCHA gagal. Silakan muat ulang halaman.";
    } else {
        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL => 'https://challenges.cloudflare.com/turnstile/v0/siteverify',
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => [
                'secret' => $turnstile_secret,
                'response' => $turnstile_response,
                'remoteip' => $_SERVER['REMOTE_ADDR'],
            ],
            CURLOPT_RETURNTRANSFER => true,
        ]);
        $response = curl_exec($ch);
        curl_close($ch);
        
        $result = json_decode($response);
        
        if (isset($result->success) && $result->success) {
            $turnstile_valid = true;
        } else {
            $login_err = "Verifikasi CAPTCHA gagal. Anda mungkin robot.";
        }
    }
    // --- AKHIR VERIFIKASI TURNSTILE ---

    // HANYA JALANKAN LOGIN JIKA TURNSTILE VALID
    if ($turnstile_valid) {
        
        // (Validasi username dan password kosong...)
        if (empty(trim($_POST["username"]))) {
            $username_err = "Username tidak boleh kosong.";
        } else {
            $username = trim($_POST["username"]);
        }
        if (empty(trim($_POST["password"]))) {
            $password_err = "Password tidak boleh kosong.";
        } else {
            $password = trim($_POST["password"]);
        }
        
        // Lanjutkan jika tidak ada error validasi input
        if (empty($username_err) && empty($password_err)) {
            
            $sql = "SELECT id, nama, username, password, is_verified FROM users WHERE username = ?";
            
            if ($stmt = $mysqli->prepare($sql)) {
                $stmt->bind_param("s", $param_username);
                $param_username = $username;
                
                if ($stmt->execute()) {
                    $stmt->store_result();
                    
                    if ($stmt->num_rows == 1) {
                        $stmt->bind_result($id, $nama, $db_username, $hashed_password, $is_verified);
                        if ($stmt->fetch()) {
                            
                            if (password_verify($password, $hashed_password)) {
                                // --- PERUBAHAN KRITIS (Cek Verifikasi) ---
                                if ($is_verified == 1) {
                                    // SUKSES! Akun terverifikasi
                                    
                                    // (Logika session 15 menit / 30 hari Anda)
                                    if (!empty($_POST["remember"])) { $lifetime = 60 * 60 * 24 * 30; ini_set('session.cookie_lifetime', $lifetime); } 
                                    else { ini_set('session.cookie_lifetime', 900); }
                                    
                                    session_destroy();
                                    session_start();
                                    
                                    $_SESSION["loggedin"] = true;
                                    $_SESSION["id"] = $id;
                                    $_SESSION["username"] = $db_username;
                                    $_SESSION["nama"] = $nama; 
                                    
                                    if (empty($_POST["remember"])) {
                                        $_SESSION['last_activity'] = time();
                                        $_SESSION['expire_time'] = 900;
                                    }
                                    
                                    header("location: dashboard.php");
                                
                                } else {
                                    // Password benar, tapi akun belum diverifikasi
                                    $login_err = "Akun Anda belum diverifikasi. Silakan cek email Anda.";
                                }
                                
                            } else {
                                $login_err = "Password yang Anda masukkan salah.";
                            }
                        }
                    } else {
                        $login_err = "Akun dengan username tersebut tidak ditemukan.";
                    }
                } else {
                    $login_err = "Oops! Terjadi kesalahan.";
                }
                $stmt->close();
            }
        }
    } // Akhir dari 'if ($turnstile_valid)'
    
    $mysqli->close();
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Masuk - MoneyGreat</title>
    <link rel="stylesheet" href="style.css">
    <script src="https://challenges.cloudflare.com/turnstile/v0/api.js" async defer></script>
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
                <h2>Masuk</h2>
                <p>untuk melanjutkan ke akun Anda.</p>
                
                <?php 
                // Bagian ini yang diubah untuk menampilkan alert box
                if (!empty($login_err)) {
                    echo '<div class="material-alert error">';
                    echo '    <div class="alert-icon">';
                    // Contoh ikon alert (Anda bisa ganti dengan SVG jika ada)
                    echo '        <svg viewBox="0 0 24 24" fill="currentColor"><path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm1 15h-2v-2h2v2zm0-4h-2V7h2v6z"/></svg>';
                    echo '    </div>';
                    echo '    <div class="alert-message">';
                    echo         $login_err;
                    echo '    </div>';
                    echo '</div>';
                }        
            ?>
            </div>
            
            <form class="login-form" id="loginForm" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post" novalidate>
                <div class="form-group">
                    <div class="input-wrapper">
                        <input type="text" id="username" name="username" required autocomplete="username" value="<?php echo $username; ?>">
                        <label for="username">Username</label>
                        <div class="input-line"></div>
                        <div class="ripple-container"></div>
                    </div>
                    <span class="error-message" id="usernameError"><?php echo $username_err; ?></span>
                </div>

                <div class="form-group">
                    <div class="input-wrapper password-wrapper">
                        <input type="password" id="password" name="password" required autocomplete="current-password">
                        <label for="password">Password</label>
                        <div class="input-line"></div>
                        <button type="button" class="password-toggle" id="passwordToggle" aria-label="Toggle password visibility">
                            <div class="toggle-ripple"></div>
                            <span class="toggle-icon"></span>
                        </button>
                        <div class="ripple-container"></div>
                    </div>
                    <span class="error-message" id="passwordError"><?php echo $password_err; ?></span>
                </div>

                <div class="form-options">
                    <div class="checkbox-wrapper">
                        <input type="checkbox" id="remember" name="remember">
                        <label for="remember" class="checkbox-label">
                            <div class="checkbox-material">
                                <div class="checkbox-ripple"></div>
                                <svg class="checkbox-icon" viewBox="0 0 24 24">
                                    <path class="checkbox-path" d="M9 16.17L4.83 12l-1.42 1.41L9 19 21 7l-1.41-1.41z"/>
                                </svg>
                            </div>
                            Tetap masuk
                        </label>
                    </div>
                    <a href="forgot-password.php" class="forgot-password">Lupa password?</a>
                </div>
                <div class="cf-turnstile" data-sitekey="0x4AAAAAAB_K2E-oPo1dpvoa"></div>
                <button type="submit" class="login-btn material-btn">
                    <div class="btn-ripple"></div>
                    <span class="btn-text">Masuk</span>
                    <div class="btn-loader">
                        <svg class="loader-circle" viewBox="0 0 50 50">
                            <circle class="loader-path" cx="25" cy="25" r="12" fill="none" stroke="currentColor" stroke-width="3"/>
                        </svg>
                    </div>
                </button>
            </form>

            <div class="divider">
                <span>atau</span>
            </div>

            <div class="social-login">
                <button type="button" class="social-btn google-material">
                    <div class="social-ripple"></div>
                    <div class="social-icon google-icon">
                        <svg viewBox="0 0 24 24">
                            <path fill="#4285F4" d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92c-.26 1.37-1.04 2.53-2.21 3.31v2.77h3.57c2.08-1.92 3.28-4.74 3.28-8.09z"/>
                            <path fill="#34A853" d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84C3.99 20.53 7.7 23 12 23z"/>
                            <path fill="#FBBC05" d="M5.84 14.09c-.22-.66-.35-1.36-.35-2.09s.13-1.43.35-2.09V7.07H2.18C1.43 8.55 1 10.22 1 12s.43 3.45 1.18 4.93l2.85-2.22.81-.62z"/>
                            <path fill="#EA4335" d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 2.09 14.97 1 12 1 7.7 1 3.99 3.47 2.18 7.07l3.66 2.84c.87-2.6 3.3-4.53 6.16-4.53z"/>
                        </svg>
                    </div>
                    <span>Lanjutkan dengan Google (Segera)</span>
            </div>

            <div class="signup-link">
                <p>Belum punya akun? <a href="daftar.php" class="create-account"> Daftar di sini</a></p>
            </div>

            <div class="success-message" id="successMessage">
                </div>
        </div>
    </div>

    <script src="script.js"></script>
</body>
</html>