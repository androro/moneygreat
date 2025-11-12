<?php
date_default_timezone_set('Asia/Jakarta'); // Sesuaikan dengan zona waktu Anda

// Impor PHPMailer (CARA MANUAL)
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

// Muat file PHPMailer secara manual (pastikan path ini benar)
require 'phpMailer/src/Exception.php';
require 'phpMailer/src/PHPMailer.php';
require 'phpMailer/src/SMTP.php';

require_once "config.php";

$email = "";
$email_err = "";
$message = ""; // Pesan sukses atau error

if ($_SERVER["REQUEST_METHOD"] == "POST") {

    if (empty(trim($_POST["email"]))) {
        $email_err = "Email tidak boleh kosong.";
    } elseif (!filter_var(trim($_POST["email"]), FILTER_VALIDATE_EMAIL)) {
        $email_err = "Format email tidak valid.";
    } else {
        $email = trim($_POST["email"]);
    }

    if (empty($email_err)) {
        $sql = "SELECT id FROM users WHERE email = ?";
        if ($stmt = $mysqli->prepare($sql)) {
            $stmt->bind_param("s", $email);
            
            if ($stmt->execute()) {
                $stmt->store_result();
                
                if ($stmt->num_rows == 1) {
                    // Email ditemukan, lanjutkan proses
                    $token = bin2hex(random_bytes(50));
                    $expires = date("Y-m-d H:i:s", strtotime('+1 hour'));

                    $update_sql = "UPDATE users SET reset_token = ?, reset_token_expires = ? WHERE email = ?";
                    if ($update_stmt = $mysqli->prepare($update_sql)) {
                        $update_stmt->bind_param("sss", $token, $expires, $email);
                        $update_stmt->execute();
                        $update_stmt->close();

                        // --- KIRIM EMAIL (Menggunakan PHPMailer) ---
                        $mail = new PHPMailer(true);
                        $reset_link = "http://localhost/moneygreat/reset-password.php?token=" . $token;

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
                            $mail->addAddress($email); // Kirim ke email yang di-input

                            // Konten
                            $mail->isHTML(true);
                            $mail->Subject = 'Reset Password Akun MoneyGreat Anda';
                            
                            // TEMPLATE HTML BARU
                            $mail->Body    = <<<EOT
                            <!DOCTYPE html>
                            <html lang="id">
                            <head>
                                <meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
                                <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
                                <title>Reset Password</title>
                                <style>
                                    body { margin: 0; padding: 0; }
                                    table { border-collapse: collapse; }
                                </style>
                            </head>
                            <body style="margin: 0; padding: 0; background-color: #f4f4f4; font-family: Arial, sans-serif;">
                                <span style="display:none;font-size:1px;color:#ffffff;line-height:1px;max-height:0px;max-width:0px;opacity:0;overflow:hidden;">
                                    Permintaan reset password untuk akun MoneyGreat Anda.
                                </span>
                                <table align="center" border="0" cellpadding="0" cellspacing="0" width="100%" style="max-width: 600px; margin: 20px auto; border-collapse: collapse;">
                                    <tr>
                                        <td align="center" style="padding: 20px 0 20px 0;">
                                            <h1 style="font-size: 28px; color: #333333; margin: 0; font-family: Arial, sans-serif;">&#9733; MoneyGreat</h1>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td style="background-color: #ffffff; padding: 40px 30px 40px 30px; border: 1px solid #e0e0e0;">
                                            <table border="0" cellpadding="0" cellspacing="0" width="100%" style="border-collapse: collapse;">
                                                <tr>
                                                    <td style="color: #333333; font-family: Arial, sans-serif; font-size: 20px; font-weight: bold; padding-bottom: 20px;">
                                                        Lupa Password Anda?
                                                    </td>
                                                </tr>
                                                <tr>
                                                    <td style="color: #555555; font-family: Arial, sans-serif; font-size: 16px; line-height: 24px; padding-bottom: 25px;">
                                                        Halo,<br><br>
                                                        Kami menerima permintaan untuk mereset password akun MoneyGreat Anda. Jika Anda merasa tidak meminta ini, abaikan saja email ini.
                                                        <br><br>
                                                        Link ini akan kedaluwarsa dalam 1 jam.
                                                    </td>
                                                </tr>
                                                <tr>
                                                    <td align="center">
                                                        <table border="0" cellpadding="0" cellspacing="0" style="border-collapse: collapse;">
                                                            <tr>
                                                                <td align="center" style="background-color: #4285F4; border-radius: 5px;">
                                                                    <a href="$reset_link" target="_blank" style="font-size: 16px; font-weight: bold; font-family: Arial, sans-serif; color: #ffffff; text-decoration: none; display: inline-block; padding: 14px 28px; border-radius: 5px;">
                                                                        Reset Password Saya
                                                                    </a>
                                                                </td>
                                                            </tr>
                                                        </table>
                                                    </td>
                                                </tr>
                                                <tr>
                                                    <td style="color: #777777; font-family: Arial, sans-serif; font-size: 14px; line-height: 20px; padding-top: 30px; border-top: 1px solid #e9e9e9; margin-top: 30px;">
                                                        Jika Anda kesulitan mengklik tombol, salin dan tempel URL di bawah ini ke browser Anda:
                                                        <br>
                                                        <a href="$reset_link" target="_blank" style="color: #4285F4; text-decoration: none; font-size: 12px; word-break: break-all;">$reset_link</a>
                                                    </td>
                                                </tr>
                                            </table>
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

                            // AltBody untuk klien email non-HTML
                            $mail->AltBody = "Halo!\n\nUntuk mereset password Anda, silakan kunjungi link berikut (kedaluwarsa dalam 1 jam):\n $reset_link";

                            $mail->send();
                            
                            // Tampilkan pesan sukses
                            $message = "Link reset password telah dikirim ke email Anda.";

                        } catch (Exception $e) {
                            // Jika email gagal dikirim
                            $email_err = "Gagal mengirim email. Error: {$mail->ErrorInfo}";
                        }
                    }
                } else {
                    // Email tidak ditemukan.
                    // Untuk keamanan, kita tetap tampilkan pesan sukses agar peretas tidak tahu.
                    $message = "Link reset password telah dikirim ke email Anda.";
                }
            }
            $stmt->close();
        }
    }
    $mysqli->close();
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Lupa Password - MoneyGreat</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <div class="login-container">
        <div class="login-card">
            <div class="material-logo">
                    <div class="logo-layers">
                        <div class="layer layer-1"></div>
                        <div class="layer layer-2"></div>
                        <div class="layer layer-3"></div>
                    </div>
                </div>
            <div class="login-header">
                <h2>Lupa Password</h2>
                <p>Masukkan email Anda untuk mendapat link reset.</p>
            </div>
            
            <?php 
            // Tampilkan pesan sukses (hijau)
            if (!empty($message)) {
                echo '<div class="material-alert" style="background-color: #e6f7ed; border-left-color: #34a853; color: #333;">';
                echo '    <div class="alert-icon" style="color: #34a853; font-weight: bold; font-size: 20px;">✓</div>';
                echo '    <div class="alert-message">' . $message . '</div>';
                echo '</div>';
            }

            // Tampilkan pesan error (merah)
            if (!empty($email_err)) {
                echo '<div class="material-alert error" style="margin-top: 15px;">';
                echo '    <div class="alert-icon">';
                echo '        <svg viewBox="0 0 24 24" fill="currentColor"><path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm1 15h-2v-2h2v2zm0-4h-2V7h2v6z"/></svg>';
                echo '    </div>';
                echo '    <div class="alert-message">' . $email_err . '</div>';
                echo '</div>';
            }
            ?>

            <?php if (empty($message)): // Sembunyikan form jika sudah sukses ?>
            <form class="login-form" id="forgotForm" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post" novalidate>
                
                <div class="form-group">
                    <div class="input-wrapper">
                        <input type="email" id="email" name="email" required autocomplete="email" value="<?php echo $email; ?>">
                        <label for="email">Email</label>
                        <div class="input-line"></div><div class="ripple-container"></div>
                    </div>
                    <span class="error-message" id="emailError"></span>
                </div>

                <button type="submit" class="login-btn material-btn" style="margin-top: 20px;">
                    <div class="btn-ripple"></div>
                    <span class="btn-text">KIRIM LINK RESET</span>
                </button>
            </form>
            <?php endif; ?>

            <div class="signup-link" style="margin-top: 20px;">
                <p><a href="login.php" class="create-account">Kembali ke Login</a></p>
            </div>
        </div>
    </div>
    </body>
</html>