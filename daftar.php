<?php
session_start(); // Mulai session

// Cek jika pengguna sudah login, redirect ke dashboard
if (isset($_SESSION["loggedin"]) && $_SESSION["loggedin"] === true) {
    header("location: dashboard.php");
    exit; // Penting
}

// Impor PHPMailer (CARA MANUAL)
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

// Muat file PHPMailer secara manual (pastikan path ini benar)
require 'phpmailer/src/Exception.php';
require 'phpmailer/src/PHPMailer.php';
require 'phpmailer/src/SMTP.php';

require_once "config.php";

// Inisialisasi variabel
$nama = $username = $email = $password = $confirm_password = "";
$nama_err = $username_err = $email_err = $password_err = $confirm_password_err = "";
$turnstile_err = ""; 
$registration_success = ""; // Variabel baru untuk pesan sukses

if ($_SERVER["REQUEST_METHOD"] == "POST") {

    // --- AWAL VERIFIKASI TURNSTILE ---
    $turnstile_secret = "0x4AAAAAAB_K2M2LWkdBHjBi1CHCQFg-HgM"; // Ganti dengan Kunci Rahasia Anda
    $turnstile_response = $_POST['cf-turnstile-response'] ?? '';
    $turnstile_valid = false;

    if (empty($turnstile_response)) {
        $turnstile_err = "Verifikasi CAPTCHA gagal. Silakan muat ulang halaman.";
    } else {
        // (Logika cURL Turnstile Anda)
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
            $turnstile_err = "Verifikasi CAPTCHA gagal. Anda mungkin robot.";
        }
    }
    // --- AKHIR VERIFIKASI TURNSTILE ---

    // HANYA JALANKAN PENDAFTARAN JIKA TURNSTILE VALID
    if ($turnstile_valid) {

        // (Validasi Nama, Username, Email, Password - TETAP SAMA)
        // ... (Validasi Nama)
        if (empty(trim($_POST["nama"]))) { $nama_err = "Nama tidak boleh kosong."; } else { $nama = trim($_POST["nama"]); }
        // ... (Validasi Username)
        if (empty(trim($_POST["username"]))) { $username_err = "Username tidak boleh kosong."; } else {
            $sql = "SELECT id FROM users WHERE username = ?"; /* ... (kode cek duplikat) ... */ 
            if ($stmt = $mysqli->prepare($sql)) {
                $stmt->bind_param("s", $param_username); $param_username = trim($_POST["username"]);
                if ($stmt->execute()) { $stmt->store_result(); if ($stmt->num_rows == 1) { $username_err = "Username ini sudah dipakai."; } else { $username = trim($_POST["username"]); } }
                $stmt->close();
            }
        }
        // ... (Validasi Email)
        if (empty(trim($_POST["email"]))) { $email_err = "Email tidak boleh kosong."; } elseif (!filter_var(trim($_POST["email"]), FILTER_VALIDATE_EMAIL)) { $email_err = "Format email tidak valid."; } else {
            $sql = "SELECT id FROM users WHERE email = ?"; /* ... (kode cek duplikat) ... */
            if ($stmt = $mysqli->prepare($sql)) {
                $stmt->bind_param("s", $param_email); $param_email = trim($_POST["email"]);
                if ($stmt->execute()) { $stmt->store_result(); if ($stmt->num_rows == 1) { $email_err = "Email ini sudah terdaftar."; } else { $email = trim($_POST["email"]); } }
                $stmt->close();
            }
        }
        // ... (Validasi Password & Konfirmasi)
        if (empty(trim($_POST["password"]))) { $password_err = "Password tidak boleh kosong."; } elseif (strlen(trim($_POST["password"])) < 6) { $password_err = "Password minimal 6 karakter."; } else { $password = trim($_POST["password"]); }
        if (empty(trim($_POST["confirm_password"]))) { $confirm_password_err = "Silakan konfirmasi password."; } else { $confirm_password = trim($_POST["confirm_password"]); if (empty($password_err) && ($password != $confirm_password)) { $confirm_password_err = "Password tidak cocok."; } }

        
        // Cek error sebelum insert
        if (empty($nama_err) && empty($username_err) && empty($email_err) && empty($password_err) && empty($confirm_password_err)) {

            // --- PERUBAHAN LOGIKA ---
            // Buat token verifikasi
            $verification_token = bin2hex(random_bytes(50)); // 100 karakter
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);

            // TAMBAHAN BARU: Set waktu kedaluwarsa (24 jam dari sekarang)
            $verification_expires = date("Y-m-d H:i:s", strtotime('+24 hours'));

            // Query INSERT diperbarui (tambah verification_expires)
            $sql = "INSERT INTO users (nama, username, email, password, verification_token, verification_expires, is_verified) VALUES (?, ?, ?, ?, ?, ?, 0)";

            if ($stmt = $mysqli->prepare($sql)) {
                // "ssssss" = 6 string
                $stmt->bind_param("ssssss", $param_nama, $param_username, $param_email, $param_password, $param_token, $param_expires);

                // Set parameter
                $param_nama = $nama;
                $param_username = $username;
                $param_email = $email;
                $param_password = $hashed_password;
                $param_token = $verification_token;
                $param_expires = $verification_expires;
                
                if ($stmt->execute()) {
                    // Berhasil insert, KIRIM EMAIL (bukan redirect)
                    $mail = new PHPMailer(true);
                    $verification_link = "http://localhost/moneygreat/verify.php?token=" . $verification_token;

                    try {
                        // --- Pengaturan Server (Ganti dengan info Anda) ---
                        $mail->SMTPOptions = array('ssl' => array('verify_peer' => false, 'verify_peer_name' => false, 'allow_self_signed' => true));
                        $mail->isSMTP();
                        $mail->Host       = 'smtp.gmail.com';
                        $mail->SMTPAuth   = true;
                        $mail->Username   = 'drovsynn@gmail.com'; // GANTI DENGAN EMAIL ANDA
                        $mail->Password   = 'wuxg hjfe epod sjky';   // GANTI DENGAN APP PASSWORD ANDA
                        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
                        $mail->Port       = 587;
                        // Penerima
                        $mail->setFrom('no-reply@moneygreat.com', 'MoneyGreat');
                        $mail->addAddress($email, $nama);

                        // Konten (Gunakan template yang sama dengan Lupa Password)
                        $mail->isHTML(true);
                        $mail->Subject = 'Verifikasi Akun MoneyGreat Anda';
                        $mail->Body    = <<<EOT
                        <!DOCTYPE html>
                        <html lang="id">
                        <body style="margin: 0; padding: 0; background-color: #f4f4f4; font-family: Arial, sans-serif;">
                            <table align="center" border="0" cellpadding="0" cellspacing="0" width="100%" style="max-width: 600px; margin: 20px auto;">
                                <tr>
                                    <td align="center" style="padding: 20px 0;">
                                        <h1 style="font-size: 28px; color: #333333; margin: 0;">&#9733; MoneyGreat</h1>
                                    </td>
                                </tr>
                                <tr>
                                    <td style="background-color: #ffffff; padding: 40px 30px; border: 1px solid #e0e0e0;">
                                        <h2 style="color: #333333; font-weight: bold; padding-bottom: 20px;">Selamat Datang, $nama!</h2>
                                        <p style="color: #555555; line-height: 24px; padding-bottom: 25px;">
                                            Terima kasih telah mendaftar di MoneyGreat. Satu langkah terakhir, silakan verifikasi alamat email Anda dengan mengklik tombol di bawah ini.
                                            <br><br>
                                            Link ini akan kedaluwarsa dalam 24 jam.
                                        </p>
                                        <table border="0" cellpadding="0" cellspacing="0" style="border-collapse: collapse; margin: 0 auto;">
                                            <tr>
                                                <td align="center" style="background-color: #4285F4; border-radius: 5px;">
                                                    <a href="$verification_link" target="_blank" style="font-size: 16px; font-weight: bold; color: #ffffff; text-decoration: none; display: inline-block; padding: 14px 28px; border-radius: 5px;">
                                                        Verifikasi Email Saya
                                                    </a>
                                                </td>
                                            </tr>
                                        </table>
                                        <p style="color: #777777; font-size: 14px; line-height: 20px; padding-top: 30px; border-top: 1px solid #e9e9e9; margin-top: 30px;">
                                            Jika Anda kesulitan mengklik tombol, salin dan tempel URL di bawah ini ke browser Anda: <br>
                                            <a href="$verification_link" target="_blank" style="color: #4285F4; word-break: break-all;">$verification_link</a>
                                        </p>
                                    </td>
                                </tr>
                                <tr>
                                        <td align="center" style="padding: 20px 30px 20px 30px; color: #999999; font-family: Arial, sans-serif; font-size: 12px;">
                                            © 2025 MoneyGreat. All rights reserved.
                                        </td>
                                    </tr>
                            </table>
                        </body>
                        </html>
                        EOT;
                        $mail->AltBody = "Halo $nama,\n\nVerifikasi akun Anda dengan mengklik link berikut (berlaku 24 jam):\n$verification_link";

                        $mail->send();
                        
                        // Tampilkan pesan sukses di halaman
                        $registration_success = "Registrasi berhasil! Silakan cek email Anda untuk link verifikasi.";

                    } catch (Exception $e) {
                        // Jika email gagal dikirim
                        $email_err = "Gagal mengirim email verifikasi. Error: {$mail->ErrorInfo}";
                    }

                } else {
                    echo "Terjadi kesalahan (Database). Silakan coba lagi.";
                }
                $stmt->close();
            }
        }
        // --- AKHIR PERUBAHAN LOGIKA ---

    } // Akhir dari 'if ($turnstile_valid)'
    
    $mysqli->close();
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Buat Akun - MoneyGreat</title>
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
                <h2>Buat Akun</h2>
                <p>untuk mulai menggunakan MoneyGreat</p>
                <?php 
                // Tampilkan pesan sukses JIKA $registration_success ada isinya
                if (!empty($registration_success)) {
                    echo '<div class="material-alert" style="background-color: #e6f7ed; border-left-color: #34a853; color: #333;">';
                    echo '    <div class="alert-icon" style="color: #34a853; font-weight: bold; font-size: 20px;">✓</div>';
                    echo '    <div class="alert-message">' . $registration_success . '</div>';
                    echo '</div>';
                }
                
                // Tampilkan error (Turnstile atau Gagal Kirim Email)
                $general_err = !empty($turnstile_err) ? $turnstile_err : (!empty($email_err) ? $email_err : '');
                if (!empty($general_err) && empty($registration_success)) {
                    echo '<div class="material-alert error" style="margin-top: 15px;">';
                    // ... (HTML alert error Anda) ...
                    echo '    <div class="alert-message">' . $general_err . '</div>';
                    echo '</div>';
                }
                ?>
            </div>

            <?php if (empty($registration_success)): // Sembunyikan form jika sudah sukses ?>
            <form class="login-form" id="registerForm" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post" novalidate>
                
                <div class="form-group">
                    <div class="input-wrapper">
                        <input type="text" id="nama" name="nama" required autocomplete="name" value="<?php echo $nama; ?>">
                        <label for="nama">Nama</label>
                        <div class="input-line"></div>
                        <div class="ripple-container"></div>
                    </div>
                    <span class="error-message" id="namaError"><?php echo $nama_err; ?></span>
                </div>

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
                    <div class="input-wrapper">
                        <input type="email" id="email" name="email" required autocomplete="email" value="<?php echo $email; ?>">
                        <label for="email">Email</label>
                        <div class="input-line"></div>
                        <div class="ripple-container"></div>
                    </div>
                    <span class="error-message" id="emailError"><?php echo $email_err; ?></span>
                </div>

                <div class="form-group">
                    <div class="input-wrapper password-wrapper">
                        <input type="password" id="password" name="password" required autocomplete="new-password">
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
                
                <div class="form-group">
                    <div class="input-wrapper password-wrapper">
                        <input type="password" id="confirm_password" name="confirm_password" required autocomplete="new-password">
                        <label for="confirm_password">Konfirmasi Password</label>
                        <div class="input-line"></div>
                        <button type="button" class="password-toggle" id="confirmPasswordToggle" aria-label="Toggle password visibility">
                            <div class="toggle-ripple"></div>
                            <span class="toggle-icon"></span>
                        </button>
                        <div class="ripple-container"></div>
                    </div>
                    <span class="error-message" id="confirmPasswordError"><?php echo $confirm_password_err; ?></span>
                </div>
                <div class="cf-turnstile" data-sitekey="0x4AAAAAAB_K2E-oPo1dpvoa"></div>
                <button type="submit" class="login-btn material-btn" style="margin-top: 20px;">
                    <div class="btn-ripple"></div>
                    <span class="btn-text">Buat Akun</span>
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
                </button>
            </div>
            <?php endif; // Akhir dari 'if (empty($registration_success))' ?>
            <div class="signup-link" style="margin-top: 30px;">
                <p>Sudah punya akun? <a href="login.php" class="create-account">Login di sini</p>
            </div>

        </div>
    </div>

    <script src="script.js"></script>
</body>
</html>