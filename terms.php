<?php
/**
 * Terms of Service Page
 * Điều khoản dịch vụ - KaiShop
 */

require_once __DIR__ . '/config/config.php';

$page_title = "Điều Khoản Dịch Vụ";
?>
<!DOCTYPE html>
<html lang="vi">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>
        <?= $page_title ?> - KaiShop
    </title>
    <meta name="description" content="Điều khoản sử dụng dịch vụ KaiShop - Hệ thống thanh toán tự động 24/7">
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

        .important-notice {
            background: #fef3c7;
            padding: 20px;
            border-radius: 8px;
            border-left: 4px solid #f59e0b;
            margin: 20px 0;
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

        <div class="important-notice">
            <strong>⚠️ Quan trọng:</strong> Bằng việc sử dụng dịch vụ KaiShop, bạn đồng ý với tất cả các điều khoản dưới
            đây.
            Vui lòng đọc kỹ trước khi sử dụng.
        </div>

        <h2>1. Giới Thiệu</h2>
        <p>
            <strong>KaiShop</strong> là hệ thống thanh toán tự động 24/7, cung cấp dịch vụ nạp tiền và thanh toán trực
            tuyến.
            Các điều khoản này điều chỉnh việc sử dụng dịch vụ của chúng tôi.
        </p>

        <h2>2. Định Nghĩa</h2>
        <ul>
            <li><strong>"Chúng tôi", "KaiShop":</strong> Nhà cung cấp dịch vụ</li>
            <li><strong>"Bạn", "Người dùng":</strong> Người sử dụng dịch vụ</li>
            <li><strong>"Dịch vụ":</strong> Tất cả các tính năng và chức năng của KaiShop</li>
            <li><strong>"Tài khoản":</strong> Tài khoản người dùng đã đăng ký</li>
        </ul>

        <h2>3. Đăng Ký Tài Khoản</h2>
        <h3>3.1. Điều Kiện Đăng Ký</h3>
        <ul>
            <li>Bạn phải từ <strong>18 tuổi trở lên</strong></li>
            <li>Cung cấp thông tin chính xác và đầy đủ</li>
            <li>Chịu trách nhiệm bảo mật tài khoản của mình</li>
            <li>Không chia sẻ tài khoản cho người khác</li>
        </ul>

        <h3>3.2. Bảo Mật Tài Khoản</h3>
        <ul>
            <li>Sử dụng mật khẩu mạnh và duy nhất</li>
            <li>Không chia sẻ mật khẩu với bất kỳ ai</li>
            <li>Thông báo ngay nếu phát hiện truy cập trái phép</li>
            <li>Chịu trách nhiệm về mọi hoạt động từ tài khoản của bạn</li>
        </ul>

        <h2>4. Sử Dụng Dịch Vụ</h2>
        <h3>4.1. Quyền Sử Dụng</h3>
        <p>Bạn được phép:</p>
        <ul>
            <li>Sử dụng dịch vụ cho mục đích hợp pháp</li>
            <li>Nạp tiền và thanh toán theo quy định</li>
            <li>Truy cập lịch sử giao dịch của mình</li>
        </ul>

        <h3>4.2. Hành Vi Bị Cấm</h3>
        <p>Bạn <strong>KHÔNG ĐƯỢC</strong>:</p>
        <ul>
            <li>Sử dụng dịch vụ cho mục đích bất hợp pháp</li>
            <li>Gian lận, lừa đảo hoặc giả mạo</li>
            <li>Tấn công, hack hoặc phá hoại hệ thống</li>
            <li>Sử dụng bot, script tự động không được phép</li>
            <li>Spam hoặc gửi nội dung độc hại</li>
            <li>Vi phạm quyền sở hữu trí tuệ</li>
        </ul>

        <h2>5. Thanh Toán và Nạp Tiền</h2>
        <h3>5.1. Phương Thức Thanh Toán</h3>
        <ul>
            <li>Chuyển khoản ngân hàng (Sepay)</li>
            <li>Các phương thức khác được công bố trên website</li>
        </ul>

        <h3>5.2. Quy Định Nạp Tiền</h3>
        <ul>
            <li>Số tiền nạp tối thiểu: <strong>10,000 VND</strong></li>
            <li>Thời gian xử lý: <strong>Tự động 24/7</strong></li>
            <li>Mã giao dịch phải chính xác theo hướng dẫn</li>
            <li>Không hoàn tiền nếu sai mã giao dịch</li>
        </ul>

        <h3>5.3. Hoàn Tiền</h3>
        <ul>
            <li>Hoàn tiền chỉ áp dụng trong trường hợp lỗi hệ thống</li>
            <li>Thời gian xử lý hoàn tiền: <strong>3-7 ngày làm việc</strong></li>
            <li>Không hoàn tiền nếu do lỗi người dùng</li>
        </ul>

        <h2>6. Phí Dịch Vụ</h2>
        <ul>
            <li>Phí giao dịch: <strong>Theo quy định tại từng thời điểm</strong></li>
            <li>Chúng tôi có quyền thay đổi phí sau khi thông báo trước</li>
            <li>Phí đã thu không được hoàn lại</li>
        </ul>

        <h2>7. Quyền và Nghĩa Vụ</h2>
        <h3>7.1. Quyền Của Chúng Tôi</h3>
        <ul>
            <li>Từ chối hoặc hủy giao dịch đáng ngờ</li>
            <li>Tạm khóa hoặc khóa vĩnh viễn tài khoản vi phạm</li>
            <li>Thay đổi, tạm ngưng hoặc ngừng dịch vụ</li>
            <li>Thu thập và sử dụng dữ liệu theo chính sách bảo mật</li>
        </ul>

        <h3>7.2. Nghĩa Vụ Của Chúng Tôi</h3>
        <ul>
            <li>Cung cấp dịch vụ ổn định và bảo mật</li>
            <li>Bảo vệ thông tin cá nhân của bạn</li>
            <li>Hỗ trợ kỹ thuật khi cần thiết</li>
            <li>Thông báo về các thay đổi quan trọng</li>
        </ul>

        <h2>8. Giới Hạn Trách Nhiệm</h2>
        <p>Chúng tôi <strong>KHÔNG</strong> chịu trách nhiệm về:</p>
        <ul>
            <li>Thiệt hại do lỗi của bên thứ ba (ngân hàng, nhà mạng)</li>
            <li>Mất mát do bạn không bảo mật tài khoản</li>
            <li>Gián đoạn dịch vụ do bảo trì hoặc sự cố kỹ thuật</li>
            <li>Thiệt hại gián tiếp, ngẫu nhiên hoặc hậu quả</li>
        </ul>

        <h2>9. Bảo Trì và Gián Đoạn</h2>
        <ul>
            <li>Chúng tôi có thể bảo trì hệ thống định kỳ</li>
            <li>Sẽ thông báo trước khi bảo trì (nếu có thể)</li>
            <li>Không chịu trách nhiệm về thiệt hại do gián đoạn dịch vụ</li>
        </ul>

        <h2>10. Sở Hữu Trí Tuệ</h2>
        <ul>
            <li>Tất cả nội dung trên KaiShop thuộc quyền sở hữu của chúng tôi</li>
            <li>Không được sao chép, phân phối mà không có sự cho phép</li>
            <li>Logo, thương hiệu KaiShop được bảo vệ bởi luật sở hữu trí tuệ</li>
        </ul>

        <h2>11. Chấm Dứt Dịch Vụ</h2>
        <h3>11.1. Bạn Có Thể</h3>
        <ul>
            <li>Xóa tài khoản bất kỳ lúc nào</li>
            <li>Ngừng sử dụng dịch vụ mà không cần thông báo</li>
        </ul>

        <h3>11.2. Chúng Tôi Có Thể</h3>
        <ul>
            <li>Khóa tài khoản vi phạm điều khoản</li>
            <li>Ngừng cung cấp dịch vụ sau khi thông báo trước</li>
            <li>Xóa tài khoản không hoạt động sau 12 tháng</li>
        </ul>

        <h2>12. Luật Áp Dụng</h2>
        <p>
            Các điều khoản này được điều chỉnh bởi <strong>Luật pháp Việt Nam</strong>.
            Mọi tranh chấp sẽ được giải quyết tại Tòa án có thẩm quyền tại Việt Nam.
        </p>

        <h2>13. Thay Đổi Điều Khoản</h2>
        <ul>
            <li>Chúng tôi có thể cập nhật điều khoản này theo thời gian</li>
            <li>Thay đổi quan trọng sẽ được thông báo qua email hoặc website</li>
            <li>Việc tiếp tục sử dụng dịch vụ đồng nghĩa với việc chấp nhận điều khoản mới</li>
        </ul>

        <div class="contact-info">
            <h2>14. Liên Hệ</h2>
            <p>Nếu bạn có câu hỏi về điều khoản dịch vụ, vui lòng liên hệ:</p>
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