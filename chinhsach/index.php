<?php
/**
 * Chính Sách Bảo Hành - Kai Shop
 * Modern UI Design v2.0
 */
require_once __DIR__ . '/../config/config.php';

// Load settings
$telegramLink = get_setting('telegram_link', 'https://t.me/Biinj');
$siteName = get_setting('site_name', SITE_NAME);
$siteEmail = get_setting('site_email', defined('CONTACT_EMAIL') ? CONTACT_EMAIL : '');
$contactPhone = get_setting('contact_phone', '081.242.0710');
$zaloLink = get_setting('social_zalo', '#');

$pageTitle = 'Chính Sách Bảo Hành - ' . $siteName;
$pageDescription = 'Chính sách bảo hành và đổi trả tại ' . $siteName . '. Cam kết 1 đổi 1 trọn đời, hoàn tiền 100% nếu lỗi. Hỗ trợ nhiệt tình 24/7, bảo vệ quyền lợi khách hàng.';
$pageKeywords = 'chính sách bảo hành kaishop, đổi trả tài khoản, hoàn tiền, bảo vệ quyền lợi khách hàng';
?>
<?php require_once __DIR__ . '/../includes/header.php'; ?>

<!-- Policy CSS -->
<link rel="stylesheet" href="<?= url('assets/css/policy.css') ?>">

<div class="policy-container">
    <div class="policy-card">
        <!-- Header -->
        <div class="policy-header">
            <div class="policy-badge">
                <i class="fas fa-shield-halved"></i>
                Được Bảo Vệ
            </div>
            <h1>Chính Sách Bảo Hành</h1>
            <p class="policy-subtitle">
                Cam kết chất lượng dịch vụ và bảo vệ quyền lợi khách hàng tại
                <strong><?= htmlspecialchars($siteName) ?></strong>
            </p>
        </div>

        <!-- Section 1: Cam Kết Chất Lượng -->
        <div class="policy-section">
            <h2>
                <span class="section-icon"><i class="fas fa-medal"></i></span>
                <span class="section-number">1.</span> Cam Kết Chất Lượng
            </h2>
            <p>
                Tại <strong><?= htmlspecialchars($siteName) ?></strong>, chúng tôi cam kết cung cấp các sản phẩm và dịch
                vụ
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

        <!-- Section 2: Thời Gian Bảo Hành -->
        <div class="policy-section">
            <h2>
                <span class="section-icon"><i class="fas fa-clock"></i></span>
                <span class="section-number">2.</span> Thời Gian Bảo Hành
            </h2>

            <h3>2.1. Thời gian bảo hành tiêu chuẩn</h3>
            <ul>
                <li><strong>Tài khoản Premium:</strong> Bảo hành 30 ngày kể từ ngày mua</li>
                <li><strong>Tài khoản Standard:</strong> Bảo hành 15 ngày kể từ ngày mua</li>
                <li><strong>Tài khoản Basic:</strong> Bảo hành 7 ngày kể từ ngày mua</li>
            </ul>

            <div class="highlight-box">
                <p><strong>Lưu ý quan trọng:</strong> Thời gian bảo hành được tính từ thời điểm khách hàng nhận được
                    thông tin tài khoản qua email hoặc tải về từ trang web.</p>
            </div>
        </div>

        <!-- Section 3: Điều Kiện Bảo Hành -->
        <div class="policy-section">
            <h2>
                <span class="section-icon"><i class="fas fa-clipboard-check"></i></span>
                <span class="section-number">3.</span> Điều Kiện Bảo Hành
            </h2>

            <h3>3.1. Trường hợp ĐƯỢC bảo hành</h3>
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

        <!-- Section 4: Quy Trình Bảo Hành -->
        <div class="policy-section">
            <h2>
                <span class="section-icon"><i class="fas fa-arrows-rotate"></i></span>
                <span class="section-number">4.</span> Quy Trình Bảo Hành
            </h2>

            <div class="policy-steps">
                <div class="policy-step">
                    <div class="step-number">1</div>
                    <div class="step-content">
                        <h4>Gửi yêu cầu bảo hành</h4>
                        <ul>
                            <li>Truy cập vào mục "Tài khoản" → "Đơn hàng của tôi"</li>
                            <li>Chọn đơn hàng cần bảo hành và nhấn "Yêu cầu hỗ trợ"</li>
                            <li>Điền đầy đủ thông tin về vấn đề gặp phải</li>
                        </ul>
                    </div>
                </div>

                <div class="policy-step">
                    <div class="step-number">2</div>
                    <div class="step-content">
                        <h4>Xác minh thông tin</h4>
                        <ul>
                            <li>Đội ngũ kỹ thuật sẽ kiểm tra thông tin trong vòng 2-4 giờ</li>
                            <li>Khách hàng có thể được yêu cầu cung cấp thêm hình ảnh/video minh chứng</li>
                        </ul>
                    </div>
                </div>

                <div class="policy-step">
                    <div class="step-number">3</div>
                    <div class="step-content">
                        <h4>Xử lý bảo hành</h4>
                        <ul>
                            <li><strong>Nếu lỗi do hệ thống:</strong> Đổi tài khoản mới tương đương trong 24h</li>
                            <li><strong>Nếu lỗi từ nhà cung cấp:</strong> Hoàn tiền 100% hoặc đổi sản phẩm khác</li>
                            <li><strong>Nếu không thuộc điều kiện bảo hành:</strong> Thông báo và giải thích rõ lý do
                            </li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>

        <!-- Section 5: Chính Sách Hoàn Tiền -->
        <div class="policy-section">
            <h2>
                <span class="section-icon"><i class="fas fa-hand-holding-dollar"></i></span>
                <span class="section-number">5.</span> Chính Sách Hoàn Tiền
            </h2>

            <p>Trong các trường hợp sau, khách hàng được hoàn tiền 100%:</p>
            <ul>
                <li>Tài khoản không thể sử dụng ngay từ đầu và không có sản phẩm thay thế</li>
                <li>Chúng tôi không thể cung cấp sản phẩm trong thời gian quy định</li>
                <li>Sản phẩm không đúng như mô tả và khách hàng không muốn đổi sang sản phẩm khác</li>
            </ul>

            <div class="highlight-box">
                <p><strong>Thời gian hoàn tiền:</strong> 3-7 ngày làm việc kể từ khi xác nhận yêu cầu hoàn tiền hợp lệ.
                    Tiền sẽ được hoàn về tài khoản <?= htmlspecialchars($siteName) ?> hoặc chuyển khoản ngân hàng theo
                    yêu cầu.</p>
            </div>
        </div>

        <!-- Section 6: Ưu Đãi Đặc Biệt -->
        <div class="policy-section">
            <h2>
                <span class="section-icon"><i class="fas fa-crown"></i></span>
                <span class="section-number">6.</span> Ưu Đãi Đặc Biệt
            </h2>
            <ul>
                <li><strong>Khách hàng VIP:</strong> Ưu tiên xử lý bảo hành, hỗ trợ 1-1 chuyên biệt</li>
                <li><strong>Khách hàng thân thiết:</strong> Tặng thêm 5-10 ngày bảo hành cho đơn hàng tiếp theo</li>
                <li><strong>Giới thiệu bạn bè:</strong> Nhận voucher giảm giá và tăng thời gian bảo hành</li>
            </ul>
        </div>

        <!-- Section 7: Disclaimer -->
        <div class="policy-section section-danger">
            <h2>
                <span class="section-icon"><i class="fas fa-triangle-exclamation"></i></span>
                <span class="section-number">7.</span> Tuyên Bố Miễn Trừ Trách Nhiệm
            </h2>

            <p><strong>1. Giới hạn trách nhiệm:</strong></p>
            <p>
                Website chỉ bán tài khoản mạng xã hội cho mục đích quảng cáo, kinh doanh thương mại.
                Chúng tôi <strong>không chịu bất kỳ trách nhiệm dân sự nào</strong> đối với việc sử dụng sai mục đích
                tài khoản hoặc vi phạm pháp luật Việt Nam từ tất cả khách hàng mua hàng từ trang web của chúng tôi.
            </p>

            <div class="highlight-box highlight-danger">
                <p><strong>2. Nghiêm cấm:</strong> Khách hàng mua tài khoản sử dụng với mục đích vi phạm pháp luật:
                    <strong>lừa đảo, chiếm đoạt tài sản, chống phá Nhà nước CHXHCN Việt Nam</strong>.
                    Nếu cố tình vi phạm, tài khoản sẽ bị xóa vĩnh viễn và phải chịu trách nhiệm trước pháp luật.
                </p>
            </div>
        </div>

        <!-- Contact Box -->
        <div class="contact-box">
            <h3><i class="fas fa-headset"></i> Liên Hệ Hỗ Trợ Bảo Hành</h3>
            <p>Bộ phận hỗ trợ của chúng tôi luôn sẵn sàng phục vụ bạn 24/7</p>

            <div class="contact-info">
                <?php if (!empty($siteEmail)): ?>
                    <div class="contact-item">
                        <i class="fas fa-envelope"></i>
                        <span><?= htmlspecialchars($siteEmail) ?></span>
                    </div>
                <?php endif; ?>

                <div class="contact-item">
                    <i class="fas fa-phone"></i>
                    <a href="<?= htmlspecialchars($zaloLink) ?>"
                        target="_blank"><?= htmlspecialchars($contactPhone) ?></a>
                </div>

                <div class="contact-item">
                    <i class="fab fa-telegram"></i>
                    <a href="<?= htmlspecialchars($telegramLink) ?>" target="_blank">Telegram</a>
                </div>

                <div class="contact-item">
                    <i class="fas fa-ticket"></i>
                    <span>Tạo Ticket Hỗ Trợ</span>
                </div>
            </div>

            <div class="policy-actions">
                <a href="<?= url('ticket/create') ?>" class="back-btn">
                    <i class="fas fa-headphones"></i>
                    Gửi Yêu Cầu Hỗ Trợ
                </a>
            </div>
        </div>

        <!-- Back Button -->
        <div class="policy-actions">
            <a href="<?= url('') ?>" class="back-btn">
                <i class="fas fa-arrow-left"></i>
                Quay Lại Trang Chủ
            </a>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>