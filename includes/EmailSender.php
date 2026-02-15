<?php

require_once BASE_PATH . '/PHPMailer/src/Exception.php';
require_once BASE_PATH . '/PHPMailer/src/PHPMailer.php';
require_once BASE_PATH . '/PHPMailer/src/SMTP.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

class EmailSender
{
    public static function send($to, $subject, $message)
    {
        $mail = new PHPMailer(true);

        try {
            $mail->isSMTP();
            $mail->Host = SMTP_HOST;
            $mail->SMTPAuth = true;
            $mail->Username = SMTP_USER;
            $mail->Password = SMTP_PASS;
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
            $mail->Port = SMTP_PORT;
            $mail->CharSet = 'UTF-8';

            $mail->setFrom(EMAIL_FROM, EMAIL_FROM_NAME);
            $mail->addAddress($to);


            $mail->isHTML(true);
            $mail->Subject = $subject;
            $mail->Body = $message;
            $mail->AltBody = strip_tags(str_replace(['<br>', '</p>'], ["\n", "\n\n"], $message));

            $mail->send();
            return true;
        } catch (Exception $e) {
            error_log("Email gửi thất bại. Lỗi: {$mail->ErrorInfo}");
            return false;
        }
    }

    public static function sendResetPasswordEmail($email, $token)
    {
        $subject = '🐼 Đặt lại mật khẩu - ' . SITE_NAME;
        $resetLink = url("auth/reset-password?token=$token");

        $message = self::getProfessionalTemplate('reset_password', [
            'action_url' => $resetLink,
            'email' => $email,
            'support_email' => CONTACT_EMAIL ?? 'kaishop365@gmail.com'
        ]);

        return self::send($email, $subject, $message);
    }

    public static function sendWelcomeEmail($user)
    {
        $subject = '🐼 Welcome to ' . SITE_NAME;
        $message = self::getProfessionalTemplate('welcome', [
            'username' => $user['username'] ?? 'bạn',
            'email' => $user['email'],
            'action_url' => url('')
        ]);

        return self::send($user['email'], $subject, $message);
    }

    public static function sendThankYouEmail($user, $reason = 'general')
    {
        $subject = '🐼 Thank you - ' . SITE_NAME;

        $reasonText = [
            'purchase' => 'đơn hàng của bạn',
            'registration' => 'đã tham gia cộng đồng chúng tôi',
            'general' => 'đã sử dụng dịch vụ của chúng tôi'
        ];

        $message = self::getProfessionalTemplate('thank_you', [
            'username' => $user['username'] ?? 'bạn',
            'email' => $user['email'],
            'reason' => $reasonText[$reason] ?? $reasonText['general'],
            'action_url' => url('')
        ]);

        return self::send($user['email'], $subject, $message);
    }

    /**
     * Template Premium LIGHT Bright Mode - No Emoji Icons
     */
    private static function getProfessionalTemplate($type, $data)
    {
        $year = date('Y');
        $siteName = SITE_NAME ?? 'KaiShop';

        // Base URL linh hoạt (localhost hoặc production)
        $baseUrl = defined('SITE_URL') ? rtrim(SITE_URL, '/') : (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http') . '://' . ($_SERVER['HTTP_HOST'] ?? 'localhost');

        $title = $buttonText = $preheader = $content = '';

        // Light Theme Colors - Bright & Clean
        $bg_main = '#ffffff';
        $bg_card = '#ffffff';
        $accent = '#7c3aed';       // Violet chính
        $accent_hover = '#8b5cf6';
        $text_primary = '#1f2937';       // Gray 800
        $text_secondary = '#4b5563';       // Gray 600
        $border = '#e5e7eb';       // Gray 200
        $gradient_top = 'linear-gradient(90deg, #7c3aed 0%, #a78bfa 100%)';
        $button_bg = '#4f46e5';  // Indigo 600

        if ($type === 'reset_password') {
            $title = '🔒 Đặt Lại Mật Khẩu';
            $buttonText = 'Đặt Lại Mật Khẩu Ngay';
            $preheader = 'Link đặt lại mật khẩu - hết hạn sau 60 phút';
            $content = "
                <p>Chào <strong>{$data['email']}</strong> 🐼,</p>
                <p>Chúng tôi vừa nhận được yêu cầu đặt lại mật khẩu cho tài khoản của bạn.</p>
                <p style='color:{$text_secondary};'>Nếu không phải bạn thực hiện, bạn có thể bỏ qua email này. Tài khoản của bạn vẫn an toàn.</p>
                <p>Nhấn nút bên dưới để thiết lập mật khẩu mới (link chỉ có hiệu lực trong 60 phút):</p>
            ";
        } elseif ($type === 'welcome') {
            $title = '🎉 Welcome to ' . $siteName;
            $buttonText = '🚀 Bắt Đầu Ngay';
            $preheader = 'Tài khoản đã sẵn sàng để khám phá!';
            $content = "
                <p>Xin chào <strong>{$data['username']}</strong> 🐼,</p>
                <p>Cảm ơn bạn đã gia nhập <strong style='color:{$accent};'>{$siteName}</strong>.</p>
                <p style='color:{$text_secondary};'>Tài khoản của bạn đã được kích hoạt thành công. Hãy bắt đầu trải nghiệm ngay hôm nay! ✨</p>
            ";
        } elseif ($type === 'thank_you') {
            $title = '💖 Cảm Ơn Bạn Rất Nhiều!';
            $buttonText = '🌟 Tiếp Tục Khám Phá';
            $preheader = 'Cảm ơn đã tin tưởng và ủng hộ KaiShop';
            $content = "
                <p>Chào <strong>{$data['username']}</strong> 🐼,</p>
                <p>Cảm ơn bạn vì {$data['reason']}. 🙏</p>
                <p style='color:{$text_secondary};'>Chúng tôi rất trân trọng sự ủng hộ của bạn và mong được phục vụ bạn trong thời gian tới!</p>
            ";
        }

        return "
        <!DOCTYPE html>
        <html lang='vi'>
        <head>
            <meta charset='UTF-8'>
            <meta name='viewport' content='width=device-width, initial-scale=1.0'>
            <met name='color-scheme' content='light'>
            <title>{$title}</title>
        </head>
        <body style=\"margin:0; padding:0; background:#f8fafc; font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',Roboto,Helvetica,Arial,sans-serif;\">
            <!-- Preheader -->
            <div style='display:none;max-height:0;overflow:hidden;'>{$preheader}</div>

            <table width='100%' border='0' cellpadding='0' cellspacing='0' style='background:#f8fafc;'>
                <tr>
                    <td align='center' style='padding:48px 20px;'>

                        

                        <!-- Main Card -->
                        <table border='0' cellpadding='0' cellspacing='0' style='max-width:620px; margin:0 auto; background:#ffffff; border-radius:16px; border:1px solid #e5e7eb; overflow:hidden; box-shadow:0 4px 20px rgba(0,0,0,0.05);'>
                            <!-- Top Gradient Bar -->
                            <tr>
                                <td height='6' style='background:{$gradient_top};'></td>
                            </tr>

                            <tr>
                                <td style='padding:48px 32px;'>
                                    <!-- KaiShop Logo -->
                                    <div style='margin-bottom:40px; text-align:center;'>
                                        <a href='{$baseUrl}' target='_blank' style='text-decoration:none;'>
                                            <img src='https://i.imghippo.com/files/ohG3559Xk.png' alt='KaiShop' style='max-width:200px; height:auto; display:inline-block; filter:drop-shadow(0 4px 8px rgba(124,58,237,0.4));'>
                                        </a>
                                    </div>
                                    <!-- Title -->
                                    <h1 style='margin:0 0 32px; color:{$text_primary}; font-size:32px; font-weight:700; text-align:center; line-height:1.2;'>
                                        {$title}
                                    </h1>

                                    <!-- Content -->
                                    <div style='color:{$text_primary}; font-size:16px; line-height:1.7;'>
                                        {$content}
                                    </div>

                                    <!-- Button -->
                                    <div style='margin:40px 0; text-align:center;'>
                                        <a href='{$data['action_url']}' target='_blank' style='display:inline-block; padding:16px 48px; background:#4f46e5; color:#ffffff; text-decoration:none; font-weight:600; border-radius:12px; box-shadow:0 4px 12px rgba(79,70,229,0.3);'>{$buttonText}</a>
                                    </div>

                                    <!-- Contact Support -->
                                    <p style='font-size:14px; color:{$text_secondary}; text-align:center; margin:0 0 32px;'>
                                        Mọi thắc mắc vui lòng liên hệ qua Telegram:<br>
                                        <a href='" . (defined('CONTACT_TELEGRAM') ? CONTACT_TELEGRAM : 'https://t.me/kaishop25') . "' target='_blank' style='color:{$accent}; text-decoration:none; display:inline-flex; align-items:center; gap:6px; margin-top:8px;'>
                                            <img src='https://img.icons8.com/color/20/telegram-app--v1.png' alt='Telegram' style='width:20px; height:20px; vertical-align:middle;'>
                                            @" . (defined('CONTACT_TELEGRAM') ? basename(CONTACT_TELEGRAM) : 'kaishop25') . "
                                        </a>
                                    </p>

                                    <!-- Divider -->
                                    <div style='height:1px; background:{$border}; margin:32px 0;'></div>
                                    <!-- Copyright -->
                                    <p style='margin:0; font-size:13px; color:#9ca3af; text-align:center;'>
                                        © {$year} {$siteName}. All rights reserved.
                                    </p>
                                </td>
                            </tr>
                        </table>

                    </td>
                </tr>
            </table>
        </body>
        </html>";
    }
}