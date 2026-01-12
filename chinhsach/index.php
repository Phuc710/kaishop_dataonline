<?php
/**
 * Chính Sách Bảo Hành - Kai Shop
 */
require_once __DIR__ . '/../config/config.php';

$pageTitle = 'Chính Sách Bảo Hành - ' . SITE_NAME;
$pageDescription = 'Chính sách bảo hành và đổi trả tại KaiShop. Cam kết 1 đổi 1 trọn đời, hoàn tiền 100% nếu lỗi. Hỗ trợ nhiệt tình 24/7, bảo vệ quyền lợi khách hàng.';
$pageKeywords = 'chính sách bảo hành kaishop, đổi trả tài khoản, hoàn tiền, bảo vệ quyền lợi khách hàng';

// Load settings
$telegramLink = get_setting('telegram_link', 'https://t.me/Biinj');
$siteName = get_setting('site_name', SITE_NAME);
$pageTitle = 'Chính Sách Bảo Hành - ' . $siteName;
$pageDescription = 'Chính sách bảo hành và đổi trả tại ' . $siteName . '. Cam kết 1 đổi 1 trọn đời, hoàn tiền 100% nếu lỗi. Hỗ trợ nhiệt tình 24/7, bảo vệ quyền lợi khách hàng.';
?>
<?php require_once __DIR__ . '/../includes/header.php'; ?>

<style>
    .policy-container {
        max-width: 1200px;
        margin: 3rem auto;
        padding: 0 2rem;
    }

    .policy-card {
        background: rgba(30, 41, 59, 0.5);
        backdrop-filter: blur(16px);
        border: 1px solid rgba(148, 163, 184, 0.15);
        border-radius: 20px;
        padding: 3rem;
        box-shadow: 0 10px 30px rgba(0, 0, 0, 0.3);
    }

    .policy-header {
        text-align: center;
        margin-bottom: 3rem;
        padding-bottom: 2rem;
        border-bottom: 2px solid rgba(139, 92, 246, 0.2);
    }

    .policy-header h1 {
        color: #f8fafc;
        font-size: 2.75rem;
        font-weight: 800;
        margin-bottom: 1rem;
        background: linear-gradient(135deg, #a78bfa, #8b5cf6);
        -webkit-background-clip: text;
        -webkit-text-fill-color: transparent;
        background-clip: text;
    }

    .policy-header p {
        color: #94a3b8;
        font-size: 1.15rem;
        font-weight: 500;
    }

    .policy-section {
        margin-bottom: 2.5rem;
        padding: 1.5rem;
        background: rgba(15, 23, 42, 0.3);
        border-radius: 12px;
        border-left: 4px solid rgba(139, 92, 246, 0.5);
    }

    .policy-section h2 {
        color: #a78bfa;
        font-size: 1.6rem;
        font-weight: 700;
        margin-bottom: 1.25rem;
        display: flex;
        align-items: center;
        gap: 0.75rem;
    }

    .policy-section h2 i {
        color: #8b5cf6;
        font-size: 1.4rem;
    }

    .policy-section h3 {
        color: #e2e8f0;
        font-size: 1.25rem;
        font-weight: 600;
        margin: 1.75rem 0 1rem;
        padding-left: 0.5rem;
        border-left: 3px solid #ffffff;
    }

    .policy-section p {
        color: #cbd5e1;
        line-height: 1.8;
        margin-bottom: 1rem;
        font-size: 1rem;
    }

    .policy-section ul {
        margin: 1.25rem 0;
        padding-left: 0;
        list-style: none;
    }

    .policy-section li {
        color: #cbd5e1;
        line-height: 1.8;
        margin-bottom: 0.75rem;
        padding-left: 2rem;
        position: relative;
        font-size: 0.95rem;
    }

    .policy-section li:before {
        content: "";
        position: absolute;
        left: 0.5rem;
        top: 0.65rem;
        width: 6px;
        height: 6px;
        background: linear-gradient(135deg, #8b5cf6, #6d28d9);
        border-radius: 50%;
    }

    .policy-section li strong {
        color: #e2e8f0;
        font-weight: 600;
    }

    .highlight-box {
        background: linear-gradient(135deg, rgba(139, 92, 246, 0.12), rgba(109, 40, 217, 0.08));
        border-left: 4px solid #8b5cf6;
        padding: 1.25rem 1.75rem;
        margin: 2rem 0;
        border-radius: 10px;
        box-shadow: 0 4px 12px rgba(139, 92, 246, 0.15);
    }

    .highlight-box p {
        margin-bottom: 0;
        color: #e2e8f0;
    }

    .highlight-box strong {
        color: #a78bfa;
        font-weight: 700;
    }

    .contact-box {
        background: linear-gradient(135deg, rgba(139, 92, 246, 0.15), rgba(109, 40, 217, 0.1));
        border: 2px solid #ffffff;
        border-radius: 16px;
        padding: 2.5rem;
        margin-top: 3rem;
        text-align: center;
        box-shadow: 0 8px 20px rgba(139, 92, 246, 0.2);
    }

    .contact-box h3 {
        color: #f8fafc;
        font-size: 1.75rem;
        font-weight: 700;
        margin-bottom: 1rem;
    }

    .contact-box h3 i {
        color: #8b5cf6;
        margin-right: 0.5rem;
    }

    .contact-box>p {
        color: #cbd5e1;
        font-size: 1.05rem;
    }

    .contact-info {
        display: flex;
        justify-content: center;
        gap: 2.5rem;
        flex-wrap: wrap;
        margin-top: 2rem;
    }

    .contact-item {
        display: flex;
        align-items: center;
        gap: 0.5rem;
        color: #cbd5e1;
        font-size: 1rem;
        font-weight: 500;
        padding: 0.75rem 1.25rem;
        background: rgba(30, 41, 59, 0.5);
        border-radius: 10px;
        border: 1px solid rgba(139, 92, 246, 0.2);
        transition: all 0.3s ease;
    }

    .contact-item:hover {
        background: rgba(139, 92, 246, 0.15);
        border-color: rgba(139, 92, 246, 0.4);
        transform: translateY(-2px);
    }

    .contact-item i {
        color: #8b5cf6;
        font-size: 1.1rem;
    }

    .back-btn {
        display: inline-flex;
        align-items: center;
        gap: 0.5rem;
        background: linear-gradient(135deg, #8b5cf6 0%, #7c3aed 100%);
        color: white;
        padding: 0.875rem 2rem;
        border-radius: 10px;
        text-decoration: none;
        font-weight: 600;
        font-size: 1rem;
        transition: all 0.3s ease;
        margin-top: 2rem;
        box-shadow: 0 4px 12px #ffffff;
    }

    .back-btn:hover {
        transform: translateY(-3px) scale(1.02);
        box-shadow: 0 8px 20px rgba(139, 92, 246, 0.5);
        background: linear-gradient(135deg, #7c3aed 0%, #6d28d9 100%);
    }

    .back-btn i {
        font-size: 1rem;
    }

    @media (max-width: 768px) {
        .policy-container {
            padding: 0 1rem;
            margin: 2rem auto;
        }

        .policy-card {
            padding: 2rem 1.5rem;
        }

        .policy-header h1 {
            font-size: 2rem;
        }

        .policy-section {
            padding: 1rem;
        }

        .contact-info {
            flex-direction: column;
            gap: 1rem;
        }
    }

    /* ==========================================
       LIGHT THEME - POLICY PAGE OVERRIDES
       ========================================== */
    [data-theme="light"] .policy-card {
        background: #ffffff !important;
        border: 1px solid #1f1f1f !important;
        box-shadow: none !important;
        backdrop-filter: none !important;
    }

    [data-theme="light"] .policy-header {
        border-bottom: 2px solid #e2e8f0 !important;
    }

    [data-theme="light"] .policy-header h1 {
        background: linear-gradient(135deg, #3b82f6, #1d4ed8) !important;
        -webkit-background-clip: text !important;
        -webkit-text-fill-color: transparent !important;
        background-clip: text !important;
    }

    [data-theme="light"] .policy-header p {
        color: #64748b !important;
    }

    [data-theme="light"] .policy-section {
        background: #f8fafc !important;
        border-left: 4px solid #3b82f6 !important;
        border: 1px solid #e2e8f0 !important;
        border-left: 4px solid #3b82f6 !important;
    }

    [data-theme="light"] .policy-section h2 {
        color: #1d4ed8 !important;
    }

    [data-theme="light"] .policy-section h2 i {
        color: #3b82f6 !important;
    }

    [data-theme="light"] .policy-section h3 {
        color: #0f172a !important;
        border-left-color: #3b82f6 !important;
    }

    [data-theme="light"] .policy-section p {
        color: #334155 !important;
    }

    [data-theme="light"] .policy-section li {
        color: #334155 !important;
    }

    [data-theme="light"] .policy-section li strong {
        color: #0f172a !important;
    }

    [data-theme="light"] .policy-section li:before {
        background: linear-gradient(135deg, #3b82f6, #1d4ed8) !important;
    }

    [data-theme="light"] .highlight-box {
        background: linear-gradient(135deg, rgba(59, 130, 246, 0.1), rgba(29, 78, 216, 0.05)) !important;
        border-left: 4px solid #3b82f6 !important;
        box-shadow: none !important;
    }

    [data-theme="light"] .highlight-box p {
        color: #1f1f1f !important;
    }

    [data-theme="light"] .highlight-box strong {
        color: #1d4ed8 !important;
    }

    [data-theme="light"] .contact-box {
        background: linear-gradient(135deg, rgba(59, 130, 246, 0.1), rgba(29, 78, 216, 0.05)) !important;
        border: 1px solid #1f1f1f !important;
        box-shadow: none !important;
    }

    [data-theme="light"] .contact-box h3 {
        color: #0f172a !important;
    }

    [data-theme="light"] .contact-box h3 i {
        color: #3b82f6 !important;
    }

    [data-theme="light"] .contact-box>p {
        color: #475569 !important;
    }

    [data-theme="light"] .contact-item {
        background: #ffffff !important;
        border: 1px solid #1f1f1f !important;
        color: #334155 !important;
    }

    [data-theme="light"] .contact-item a {
        color: #000000 !important;
    }

    [data-theme="light"] .contact-item a[href] {
        color: #000000 !important;
    }


    [data-theme="light"] .contact-item:hover {
        background: #f1f5f9 !important;
        border-color: #3b82f6 !important;
    }

    [data-theme="light"] .contact-item i {
        color: #3b82f6 !important;
    }

    [data-theme="light"] .back-btn {
        background: linear-gradient(135deg, #3b82f6 0%, #1d4ed8 100%) !important;
        box-shadow: none !important;
    }

    [data-theme="light"] .back-btn:hover {
        background: linear-gradient(135deg, #2563eb 0%, #1e40af 100%) !important;
        box-shadow: none !important;
    }

    /* Disclaimer section light theme */
    [data-theme="light"] .policy-section[style*="border-left-color: #ef4444"] {
        border-left-color: #ef4444 !important;
        background: rgba(239, 68, 68, 0.05) !important;
    }

    [data-theme="light"] .highlight-box[style*="border-left-color: #ef4444"] {
        border-left-color: #ef4444 !important;
        background: linear-gradient(135deg, rgba(239, 68, 68, 0.1), rgba(220, 38, 38, 0.05)) !important;
    }
</style>

<div class="policy-container">
    <div class="policy-card">
        <div class="policy-header">
            <h1>Chính Sách Bảo Hành</h1>
            <p>Cam kết chất lượng dịch vụ và bảo vệ quyền lợi khách hàng</p>
        </div>

        <div class="policy-section">
            <h2><i class="fas fa-shield-check"></i> 1. Cam Kết Chất Lượng</h2>
            <p>
                Tại <strong><?= $siteName ?></strong>, chúng tôi cam kết cung cấp các sản phẩm và dịch vụ
                chất lượng cao nhất với giá cả hợp lý. Mọi tài khoản, dịch vụ đều được kiểm tra kỹ lưỡng
                trước khi giao đến tay khách hàng.
            </p>

            <ul>
                <li><strong>Sản phẩm chính hãng:</strong> 100% tài khoản được cung cấp từ nguồn uy tín, đảm bảo hoạt
                    động ổn định</li>
                <li><strong>Kiểm tra kỹ càng:</strong> Mỗi sản phẩm đều được test thử nghiệm trước khi bán</li>
                <li><strong>Thông tin minh bạch:</strong> Cung cấp đầy đủ thông tin chi tiết về sản phẩm</li>
                <li><strong>Hỗ trợ tận tình:</strong> Đội ngũ hỗ trợ 24/7 sẵn sàng giải đáp mọi thắc mắc</li>
            </ul>
        </div>

        <div class="policy-section">
            <h2><i class="fas fa-clock"></i> 2. Thời Gian Bảo Hành</h2>

            <h3>2.1. Thời gian bảo hành tiêu chuẩn</h3>
            <ul>
                <li><strong>Tài khoản Premium:</strong> Bảo hành 30 ngày kể từ ngày mua</li>
                <li><strong>Tài khoản Standard:</strong> Bảo hành 15 ngày kể từ ngày mua</li>
                <li><strong>Tài khoản Basic:</strong> Bảo hành 7 ngày kể từ ngày mua</li>
            </ul>

            <div class="highlight-box">
                <p><strong>Lưu ý quan trọng:</strong> Thời gian bảo hành được tính từ thời điểm khách hàng
                    nhận được thông tin tài khoản qua email hoặc tải về từ trang web.</p>
            </div>
        </div>

        <div class="policy-section">
            <h2><i class="fas fa-tools"></i> 3. Điều Kiện Bảo Hành</h2>

            <p>Sản phẩm được bảo hành khi gặp các vấn đề sau:</p>
            <ul>
                <li>Tài khoản không thể đăng nhập do lỗi từ hệ thống</li>
                <li>Tài khoản bị khóa không do lỗi của khách hàng</li>
                <li>Thông tin tài khoản không chính xác hoặc không đúng như mô tả</li>
                <li>Sản phẩm không hoạt động ngay từ lần đầu sử dụng</li>
            </ul>

            <h3>3.2. Trường hợp KHÔNG được bảo hành</h3>
            <ul>
                <li>Khách hàng tự ý thay đổi thông tin tài khoản (email, mật khẩu, số điện thoại...)</li>
                <li>Vi phạm điều khoản sử dụng của nhà cung cấp dịch vụ</li>
                <li>Tài khoản bị khóa do hành vi gian lận, spam, hack, phishing</li>
                <li>Sử dụng tài khoản cho mục đích bất hợp pháp</li>
                <li>Chia sẻ tài khoản cho người khác sử dụng</li>
                <li>Quá thời hạn bảo hành đã quy định</li>
            </ul>
        </div>

        <div class="policy-section">
            <h2><i class="fas fa-exchange-alt"></i> 4. Quy Trình Bảo Hành</h2>

            <p><strong>Bước 1: Gửi yêu cầu bảo hành</strong></p>
            <ul>
                <li>Truy cập vào mục "Tài khoản" → "Đơn hàng của tôi"</li>
                <li>Chọn đơn hàng cần bảo hành và nhấn "Yêu cầu hỗ trợ"</li>
                <li>Điền đầy đủ thông tin về vấn đề gặp phải</li>
            </ul>

            <p><strong>Bước 2: Xác minh thông tin</strong></p>
            <ul>
                <li>Đội ngũ kỹ thuật sẽ kiểm tra thông tin trong vòng 2-4 giờ</li>
                <li>Khách hàng có thể được yêu cầu cung cấp thêm hình ảnh/video minh chứng</li>
            </ul>

            <p><strong>Bước 3: Xử lý bảo hành</strong></p>
            <ul>
                <li><strong>Nếu lỗi do hệ thống:</strong> Đổi tài khoản mới tương đương trong 24h</li>
                <li><strong>Nếu lỗi từ nhà cung cấp:</strong> Hoàn tiền 100% hoặc đổi sản phẩm khác</li>
                <li><strong>Nếu không thuộc điều kiện bảo hành:</strong> Thông báo và giải thích rõ lý do</li>
            </ul>
        </div>

        <div class="policy-section">
            <h2><i class="fas fa-gift"></i> 5. Chính Sách Hoàn Tiền</h2>

            <p>Trong các trường hợp sau, khách hàng được hoàn tiền 100%:</p>
            <ul>
                <li>Tài khoản không thể sử dụng ngay từ đầu và không có sản phẩm thay thế</li>
                <li>Chúng tôi không thể cung cấp sản phẩm trong thời gian quy định</li>
                <li>Sản phẩm không đúng như mô tả và khách hàng không muốn đổi sang sản phẩm khác</li>
            </ul>

            <div class="highlight-box">
                <p><strong>Thời gian hoàn tiền:</strong> 3-7 ngày làm việc kể từ khi xác nhận yêu cầu hoàn tiền hợp lệ.
                    Tiền sẽ được hoàn về tài khoản <?= $siteName ?> hoặc chuyển khoản ngân hàng theo yêu cầu.</p>
            </div>
        </div>

        <div class="policy-section">
            <h2><i class="fas fa-star"></i> 6. Ưu Đãi Đặc Biệt</h2>

            <ul>
                <li><strong>Khách hàng VIP:</strong> Ưu tiên xử lý bảo hành, hỗ trợ 1-1 chuyên biệt</li>
                <li><strong>Khách hàng thân thiết:</strong> Tặng thêm 5-10 ngày bảo hành cho đơn hàng tiếp theo</li>
                <li><strong>Giới thiệu bạn bè:</strong> Nhận voucher giảm giá và tăng thời gian bảo hành</li>
            </ul>
        </div>

        <div class="policy-section" style="border-left-color: #ef4444; background: rgba(239, 68, 68, 0.05)">
            <h2 style="color: #ef4444"><i class="fas fa-exclamation-triangle" style="color: #ef4444"></i> 7. Tuyên Bố
                Miễn Trừ Trách Nhiệm</h2>

            <p><strong style="color: #ef4444">1. Giới hạn trách nhiệm:</strong></p>
            <p>Website chỉ bán tài khoản mạng xã hội cho mục đích quảng cáo, kinh doanh thương mại.
                Chúng tôi <strong>không chịu bất kỳ trách nhiệm dân sự nào</strong> đối với việc sử dụng sai mục đích
                tài khoản hoặc vi phạm pháp luật Việt Nam từ tất cả khách hàng mua hàng từ trang web của chúng tôi.</p>

            <div class="highlight-box"
                style="border-left-color: #ef4444; background: linear-gradient(135deg, rgba(239, 68, 68, 0.12), rgba(220, 38, 38, 0.08))">
                <p><strong style="color: #ef4444">2. Nghiêm cấm:</strong> Khách hàng mua tài khoản sử dụng với mục đích
                    vi phạm pháp luật:
                    <strong>lừa đảo, chiếm đoạt tài sản, chống phá Nhà nước CHXHCN Việt Nam</strong>.
                    Nếu cố tình vi phạm, tài khoản sẽ bị xóa vĩnh viễn và phải chịu trách nhiệm trước pháp luật.
                </p>
            </div>
        </div>

        <div class="contact-box">
            <h3><i class="fas fa-headset"></i> Liên Hệ Hỗ Trợ Bảo Hành</h3>
            <p>Bộ phận hỗ trợ của chúng tôi luôn sẵn sàng phục vụ bạn 24/7</p>

            <div class="contact-info">
                <div class="contact-item">
                    <i class="fas fa-envelope"></i>
                    <span><?= get_setting('site_email', defined('CONTACT_EMAIL') ? CONTACT_EMAIL : '') ?></span>
                </div>
                <!-- Zalo Contact -->
                <div class="contact-item">
                    <i class="fas fa-phone"></i>
                    <a href="<?= get_setting('social_zalo', '#') ?>" target="_blank"
                        style="color:inherit;text-decoration:none"><?= get_setting('contact_phone', '081.242.0710') ?></a>
                </div>
                <div class="contact-item">
                    <i class="fab fa-telegram"></i>
                    <a href="<?= htmlspecialchars($telegramLink) ?>" target="_blank"
                        style="color:inherit;text-decoration:none">Telegram</a>
                </div>
                <div class="contact-item">
                    <i class="fas fa-ticket-alt"></i>
                    <span>Tạo Ticket Hỗ Trợ</span>
                </div>
            </div>

            <a href="<?= url('ticket/create') ?>" class="back-btn">
                <i class="fas fa-headphones"></i>
                Gửi Yêu Cầu Hỗ Trợ
            </a>
        </div>

        <div style="text-align: center;">
            <a href="<?= url('') ?>" class="back-btn">
                <i class="fas fa-arrow-left"></i>
                Quay Lại Trang Chủ
            </a>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>