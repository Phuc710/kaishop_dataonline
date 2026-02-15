<?php
/**
 * Privacy Policy Page
 * Chính sách bảo mật - KaiShop
 */

require_once __DIR__ . '/config/config.php';

$page_title = "Chính Sách Bảo Mật";
?>
<!DOCTYPE html>
<html lang="vi">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>
        <?= $page_title ?> - KaiShop
    </title>
    <meta name="description" content="Chính sách bảo mật của KaiShop - Hệ thống thanh toán tự động 24/7">
    <link rel="stylesheet" href="<?= asset('css/style.css') ?>">
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            line-height: 1.6;
            color: #333;
            background: #f5f5f5;
            margin: 0;
            padding: 0;
        }

        .container {
            max-width: 900px;
            margin: 40px auto;
            padding: 40px;
            background: white;
            border-radius: 12px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
        }

        h1 {
            color: #2563eb;
            border-bottom: 3px solid #2563eb;
            padding-bottom: 10px;
            margin-bottom: 30px;
        }

        h2 {
            color: #1e40af;
            margin-top: 30px;
            margin-bottom: 15px;
        }

        h3 {
            color: #3b82f6;
            margin-top: 20px;
            margin-bottom: 10px;
        }

        p {
            margin-bottom: 15px;
        }

        ul {
            margin-left: 20px;
            margin-bottom: 15px;
        }

        li {
            margin-bottom: 8px;
        }

        .last-updated {
            color: #666;
            font-style: italic;
            margin-bottom: 30px;
        }

        .contact-info {
            background: #f0f9ff;
            padding: 20px;
            border-radius: 8px;
            border-left: 4px solid #2563eb;
            margin-top: 30px;
        }

        .back-link {
            display: inline-block;
            margin-top: 30px;
            color: #2563eb;
            text-decoration: none;
            font-weight: 600;
        }

        .back-link:hover {
            text-decoration: underline;
        }
    </style>
</head>

<body>
    <div class="container">
        <h1>
            <?= $page_title ?>
        </h1>
        <p class="last-updated">Cập nhật lần cuối:
            <?= date('d/m/Y') ?>
        </p>

        <h2>1. Giới Thiệu</h2>
        <p>
            Chào mừng bạn đến với <strong>KaiShop</strong> - Hệ thống thanh toán tự động 24/7.
            Chúng tôi cam kết bảo vệ quyền riêng tư và thông tin cá nhân của bạn.
            Chính sách bảo mật này giải thích cách chúng tôi thu thập, sử dụng và bảo vệ thông tin của bạn.
        </p>

        <h2>2. Thông Tin Chúng Tôi Thu Thập</h2>
        <h3>2.1. Thông Tin Cá Nhân</h3>
        <ul>
            <li><strong>Thông tin đăng ký:</strong> Tên, email, số điện thoại</li>
            <li><strong>Thông tin thanh toán:</strong> Lịch sử giao dịch, số dư tài khoản</li>
            <li><strong>Thông tin đăng nhập:</strong> Email, mật khẩu (được mã hóa)</li>
        </ul>

        <h3>2.2. Thông Tin Tự Động</h3>
        <ul>
            <li><strong>Địa chỉ IP:</strong> Để bảo mật và phát hiện gian lận</li>
            <li><strong>Cookies:</strong> Để cải thiện trải nghiệm người dùng</li>
            <li><strong>Thông tin thiết bị:</strong> Loại trình duyệt, hệ điều hành</li>
        </ul>

        <h2>3. Cách Chúng Tôi Sử Dụng Thông Tin</h2>
        <p>Chúng tôi sử dụng thông tin của bạn để:</p>
        <ul>
            <li>Cung cấp và quản lý dịch vụ thanh toán</li>
            <li>Xử lý giao dịch và nạp tiền</li>
            <li>Gửi thông báo về tài khoản và giao dịch</li>
            <li>Cải thiện dịch vụ và trải nghiệm người dùng</li>
            <li>Phát hiện và ngăn chặn gian lận</li>
            <li>Tuân thủ các yêu cầu pháp lý</li>
        </ul>

        <h2>4. Bảo Mật Thông Tin</h2>
        <p>Chúng tôi áp dụng các biện pháp bảo mật sau:</p>
        <ul>
            <li><strong>Mã hóa SSL/TLS:</strong> Bảo vệ dữ liệu truyền tải</li>
            <li><strong>Mã hóa mật khẩu:</strong> Sử dụng bcrypt với cost factor cao</li>
            <li><strong>Xác thực 2 lớp:</strong> reCAPTCHA Enterprise + Cloudflare Turnstile</li>
            <li><strong>Firewall:</strong> Bảo vệ khỏi tấn công mạng</li>
            <li><strong>Giám sát 24/7:</strong> Phát hiện hoạt động bất thường</li>
        </ul>

        <h2>5. Chia Sẻ Thông Tin</h2>
        <p>Chúng tôi <strong>KHÔNG</strong> bán hoặc cho thuê thông tin cá nhân của bạn. Chúng tôi chỉ chia sẻ thông tin
            trong các trường hợp sau:</p>
        <ul>
            <li><strong>Nhà cung cấp dịch vụ:</strong> Google (Firebase Authentication), Cloudflare</li>
            <li><strong>Yêu cầu pháp lý:</strong> Khi được yêu cầu bởi cơ quan chức năng</li>
            <li><strong>Bảo vệ quyền lợi:</strong> Để ngăn chặn gian lận hoặc vi phạm điều khoản</li>
        </ul>

        <h2>6. Quyền Của Bạn</h2>
        <p>Bạn có quyền:</p>
        <ul>
            <li><strong>Truy cập:</strong> Xem thông tin cá nhân của bạn</li>
            <li><strong>Chỉnh sửa:</strong> Cập nhật thông tin không chính xác</li>
            <li><strong>Xóa:</strong> Yêu cầu xóa tài khoản và dữ liệu</li>
            <li><strong>Từ chối:</strong> Từ chối nhận email marketing</li>
            <li><strong>Khiếu nại:</strong> Liên hệ với chúng tôi về vấn đề bảo mật</li>
        </ul>

        <h2>7. Cookies</h2>
        <p>Chúng tôi sử dụng cookies để:</p>
        <ul>
            <li>Duy trì phiên đăng nhập</li>
            <li>Ghi nhớ tùy chọn của bạn (ngôn ngữ, tiền tệ)</li>
            <li>Phân tích lưu lượng truy cập</li>
            <li>Cải thiện hiệu suất website</li>
        </ul>
        <p>Bạn có thể tắt cookies trong cài đặt trình duyệt, nhưng một số tính năng có thể không hoạt động.</p>

        <h2>8. Dịch Vụ Bên Thứ Ba</h2>
        <p>Chúng tôi sử dụng các dịch vụ sau:</p>
        <ul>
            <li><strong>Google Firebase:</strong> Xác thực người dùng</li>
            <li><strong>Google reCAPTCHA Enterprise:</strong> Bảo vệ khỏi bot</li>
            <li><strong>Cloudflare:</strong> CDN và bảo mật</li>
            <li><strong>Sepay:</strong> Xử lý thanh toán ngân hàng</li>
        </ul>

        <h2>9. Lưu Trữ Dữ Liệu</h2>
        <p>
            Dữ liệu của bạn được lưu trữ tại các máy chủ bảo mật ở Việt Nam.
            Chúng tôi lưu trữ thông tin của bạn miễn là tài khoản còn hoạt động hoặc theo yêu cầu pháp lý.
        </p>

        <h2>10. Trẻ Em</h2>
        <p>
            Dịch vụ của chúng tôi không dành cho người dưới 18 tuổi.
            Chúng tôi không cố ý thu thập thông tin từ trẻ em.
        </p>

        <h2>11. Thay Đổi Chính Sách</h2>
        <p>
            Chúng tôi có thể cập nhật chính sách này theo thời gian.
            Thay đổi quan trọng sẽ được thông báo qua email hoặc trên website.
        </p>

        <div class="contact-info">
            <h2>12. Liên Hệ</h2>
            <p>Nếu bạn có câu hỏi về chính sách bảo mật, vui lòng liên hệ:</p>
            <ul>
                <li><strong>Email:</strong>
                    <?= CONTACT_EMAIL ?>
                </li>
                <li><strong>Website:</strong> <a href="<?= BASE_URL ?>">
                        <?= BASE_URL ?>
                    </a></li>
            </ul>
        </div>

        <a href="<?= url('') ?>" class="back-link">← Quay lại trang chủ</a>
    </div>
</body>

</html>