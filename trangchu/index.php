<?php
/**
 * Trang Chủ - KaiShop
 */

// Define page-specific metadata for SEO
$pageTitle = 'KaiShop - Mua Tài Khoản ChatGPT, Gemini, Canva, Netflix Giá Rẻ';
$pageDescription = 'Mua tài khoản ChatGPT Plus, Gemini Pro, Canva Pro, Netflix, Spotify giá rẻ. Giao dịch tự động 24/7, bảo hành trọn đời. Uy tín #1 VN!';
$pageKeywords = 'chatgpt plus, gemini pro, canva pro, netflix, spotify, youtube premium, source code, mua tài khoản, giá rẻ, uy tín';

// Get stats
$total_products = $pdo->query("SELECT COUNT(*) FROM products WHERE is_active = 1 AND is_hidden = 0")->fetchColumn();
$total_users = $pdo->query("SELECT COUNT(*) FROM users")->fetchColumn();
$total_orders = $pdo->query("SELECT COUNT(*) FROM orders WHERE created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)")->fetchColumn();

// Include Header
require_once __DIR__ . '/../includes/header.php';
?>

<!-- Import Styles -->
<?php require_once __DIR__ . '/home-styles.php'; ?>

<!-- Notification Banner -->
<?php require_once __DIR__ . '/../includes/notification_banner.php'; ?>

<div class="page-wrapper">
    <main>
        <!-- Hero Section -->
        <section id="hero" class="hero">
            <div class="hero-bg-glow"></div>
            <div class="container hero-inner">
                <!-- Hero Left - Logo Image -->
                <div class="hero-left fade-up">
                    <div class="hero-image-wrapper">
                        <img src="<?= asset('images/light_trangchu.png') ?>"
                            alt="Mua tài khoản ChatGPT Gemini Canva Netflix giá rẻ KaiShop"
                            class="hero-logo-image light-mode-img">
                        <img src="<?= asset('images/dark_trangchu.png') ?>"
                            alt="Mua tài khoản ChatGPT Gemini Canva Netflix giá rẻ KaiShop"
                            class="hero-logo-image dark-mode-img">
                        <img src="<?= asset('images/halloween/logo.png') ?>"
                            alt="Mua tài khoản ChatGPT Gemini Canva Netflix giá rẻ KaiShop Halloween"
                            class="hero-logo-image halloween-mode-img">
                        <img src="<?= asset('images/noel/onggianoel.png') ?>"
                            alt="Mua tài khoản ChatGPT Gemini Canva Netflix giá rẻ KaiShop Noel"
                            class="hero-logo-image noel-mode-img">
                        <img src="<?= asset('images/tet/lan.png') ?>"
                            alt="Mua tài khoản ChatGPT Gemini Canva Netflix giá rẻ KaiShop Tết"
                            class="hero-logo-image tet-mode-img">
                    </div>
                </div>

                <!-- Hero Content - Main Text -->
                <div class="hero-content fade-up" style="animation-delay: 0.1s;">
                    <div class="hero-badge">
                        <img src="https://media.giphy.com/media/xje7ITeGqNAFWyvZ7a/giphy.gif" alt="Auto"
                            style="width: 20px; height: 20px; object-fit: contain;">
                        <span>Thanh toán tự động • Hỗ trợ 24/7</span>
                    </div>

                    <h1 class="hero-title">
                        Kho tài nguyên <br>
                        <span class="text-gradient">Chất lượng cao</span>
                    </h1>

                    <div class="typing-container notranslate">
                        <span id="typing-text" class="typing-text"></span>
                        <span class="typing-cursor">|</span>
                    </div>

                    <p class="hero-subtitle">
                        <strong>KaiShop</strong> – Nơi chuyên cung cấp tài khoản premium: ChatGPT Plus, Gemini Pro,
                        Canva Pro, Netflix, Spotify, YouTube Premium, cùng Source Code chất lượng cao. <br>
                        Giao dịch tự động 24/7, bảo hành trọn đời, hỗ trợ nhiệt tình. Trải nghiệm mua sắm <strong>nhanh
                            chóng</strong>, <strong>an toàn</strong> và <strong>tiện lợi</strong> nhất!
                    </p>

                    <div class="hero-actions">
                        <div class="action-buttons">
                            <a href="<?= url('sanpham') ?>" class="btn btn-primary btn-lg shine-effect">
                                <i class="fas fa-shopping-cart"></i> Mua ngay
                            </a>
                            <a href="<?= url('chinhsach') ?>" class="btn btn-ghost btn-lg">
                                Chính sách <i class="fa fa-arrow-right"></i>

                            </a>
                        </div>
                        <div class="trust-indicator">
                            <div class="avatars">
                                <span>+<?= number_format($total_users + 369) ?></span>
                            </div>
                            <small>Khách hàng đã tin tưởng sử dụng dịch vụ</small>
                        </div>
                    </div>

                    <!-- Search Box -->
                    <div class="hero-search-wrapper" onclick="window.location.href='<?= url('sanpham') ?>'">
                        <div class="hero-search">
                            <i class="fas fa-search search-icon"></i>
                            <div class="search-placeholder">
                                <span>Tìm kiếm: </span>
                                <span class="search-keywords-rotate">Source Code, AI, Youtube...</span>
                            </div>
                            <button class="search-btn"><i class="fas fa-arrow-right"></i></button>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- Features -->
        <section id="features" class="section">
            <div class="container">
                <div class="section-header fade-up">
                    <div>
                        <div class="section-label">GIÁ TRỊ CỐT LÕI</div>
                        <h2 class="section-title">Tại sao chọn <span class="text-gradient">KaiShop</span>?</h2>
                        <div class="section-sub">Hơn 10,000+ khách hàng tin tưởng sử dụng tài khoản ChatGPT, Gemini,
                            Canva, Netflix, Spotify, Source Code từ KaiShop. Giá rẻ nhất thị trường, bảo hành trọn đời,
                            hỗ trợ 24/7.</div>
                    </div>
                    <a href="<?= url('chinhsach') ?>" class="section-link">Xem chính sách bảo hành <i
                            class="fas fa-arrow-right"></i></a>
                </div>

                <div class="features-grid stagger-box">
                    <div class="feature-card">
                        <div class="feature-icon-box">
                            <i class="fas fa-robot"></i>
                        </div>
                        <h3 class="feature-title">Giao dịch tự động 100%</h3>
                        <p class="feature-text">
                            Không cần chờ đợi admin phê duyệt. Hệ thống tự động kiểm tra chuyển khoản và trả hàng ngay
                            lập tức bất kể ngày đêm.
                        </p>
                        <div class="feature-meta">
                            <span class="badge badge-success">Speed: 1-5s</span>
                        </div>
                    </div>

                    <div class="feature-card">
                        <div class="feature-icon-box color-2">
                            <i class="fas fa-user-shield"></i>
                        </div>
                        <h3 class="feature-title">Bảo hành trọn gói</h3>
                        <p class="feature-text">
                            Chính sách bảo hành rõ ràng, minh bạch cho từng loại sản phẩm. Hỗ trợ đổi mới 1:1 nếu sản
                            phẩm lỗi trong thời gian cam kết.
                        </p>
                        <div class="feature-meta">
                            <span class="badge badge-primary">Support 24/7</span>
                        </div>
                    </div>

                    <div class="feature-card">
                        <div class="feature-icon-box color-3">
                            <i class="fas fa-hand-holding-usd"></i>
                        </div>
                        <h3 class="feature-title">Sản phẩm đa dạng & Giá tốt</h3>
                        <p class="feature-text">
                            Cung cấp đầy đủ các loại tài khoản giải trí, học tập, làm việc với mức giá cạnh tranh nhất
                            thị trường.
                        </p>
                        <div class="feature-meta">
                            <span class="badge badge-warning">Siêu tiết kiệm</span>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- Product Highlight (Optional/New) -->
        <section class="section bg-soft">
            <div class="container">
                <div class="promotion-banner fade-up">
                    <div class="promo-content">
                        <h2><img src="https://media.giphy.com/media/kEhKBVTIMz6c10g3Lz/giphy.gif" alt="Fire"
                                style="width: 40px; height: 40px; object-fit: contain; vertical-align: middle;"> Deal
                            HOT - Giá Sốc Tháng Này!
                            <img src="https://media.giphy.com/media/kEhKBVTIMz6c10g3Lz/giphy.gif" alt="Fire"
                                style="width: 40px; height: 40px; object-fit: contain; vertical-align: middle;">
                        </h2>
                        <p>Tài khoản ChatGPT Plus chỉ từ 50k, Gemini Pro, Canva Pro, YouTube Premium giá cực ưu đãi! Mua
                            ngay Source Code chất lượng cao với giá tốt nhất. Số lượng có hạn - Đặt hàng ngay để không
                            bỏ lỡ!</p>
                        <a href="<?= url('sanpham') ?>" class="btn btn-white btn-lg" style="color: #000000ff;">Xem tất
                            cả sản phẩm <i class="fas fa-arrow-right"></i></a>
                    </div>
                    <div class="promo-visual">
                        <div class="glass-card">
                            <span>ChatGPT Plus</span>
                            <strong>Chỉ từ 50k</strong>
                        </div>
                        <div class="glass-card offset">
                            <span>Youtube Premium</span>
                            <strong>Chỉ từ 15k</strong>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- Stats -->
        <section class="section">
            <div class="container">
                <div class="dashboard-section fade-up">
                    <div class="dashboard-content">
                        <div>
                            <div class="section-label">THỐNG KÊ HỆ THỐNG</div>
                            <h2 class="h2-title">Nhiều Khách Hàng Tin Tưởng</h2>
                            <p class="text-muted">Đã cung cấp hàng nghìn tài khoản ChatGPT, Gemini, Canva, Netflix,
                                Spotify, Source Code chất lượng cao. Giao dịch tự động 24/7, bảo hành trọn đời, hỗ trợ
                                nhiệt tình.</p>
                        </div>
                    </div>
                    <div class="stats-grid stagger-box">
                        <div class="stat-card">
                            <div class="stat-value counter" data-target="<?= $total_products ?>">0</div>
                            <div class="stat-label">SẢN PHẨM SẴN CÓ</div>
                        </div>
                        <div class="stat-card">
                            <div class="stat-value counter" data-target="<?= $total_users + 369 ?>">0</div>
                            <div class="stat-label">KHÁCH HÀNG TIN DÙNG</div>
                        </div>
                        <div class="stat-card">
                            <div class="stat-value counter" data-target="<?= $total_orders + 560 ?>">0</div>
                            <div class="stat-label">GIAO DỊCH THÀNH CÔNG</div>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- Process Steps -->
        <section class="section">
            <div class="container">
                <div class="section-header text-center fade-up">
                    <div class="section-label">ĐƠN GIẢN HÓA</div>
                    <h2 class="section-title">Mua hàng chỉ với 3 bước</h2>
                </div>

                <div class="process-grid stagger-box">
                    <div class="process-step">
                        <div class="step-line"></div>
                        <div class="process-icon">
                            <i class="fas fa-mouse-pointer"></i>
                        </div>
                        <h3>1. Chọn sản phẩm</h3>
                        <p>Tìm kiếm và chọn gói tài khoản phù hợp với nhu cầu của bạn trên cửa hàng.</p>
                    </div>

                    <div class="process-step">
                        <div class="step-line"></div>
                        <div class="process-icon">
                            <i class="fas fa-qrcode"></i>
                        </div>
                        <h3>2. Thanh toán</h3>
                        <p>Quét mã QR hoặc chuyển khoản. Hệ thống tự động check và cộng tiền trong vài giây.</p>
                    </div>

                    <div class="process-step">
                        <div class="process-icon">
                            <i class="fas fa-gift"></i>
                        </div>
                        <h3>3. Nhận tài khoản</h3>
                        <p>Hệ thống tự động gửi thông tin tài khoản cho bạn ngay lập tức. Done!</p>
                    </div>
                </div>
            </div>
        </section>

        <!-- FAQ -->
        <section class="section">
            <div class="container">
                <div class="section-header fade-up">
                    <h2 class="section-title">Câu hỏi thường gặp</h2>
                </div>

                <div class="faq-grid stagger-box">
                    <div class="faq-item">
                        <div class="faq-head">
                            <span>Tôi nạp tiền nhưng chưa thấy cộng?</span>
                            <i class="fas fa-plus"></i>
                        </div>
                        <div class="faq-body">
                            <p>Hệ thống của chúng tôi quét giao dịch tự động mỗi 30 giây. Thông thường bạn sẽ nhận được
                                tiền trong vòng 1-3 phút. Nếu quá thời gian trên, vui lòng liên hệ Admin để được hỗ trợ
                                kiểm tra ngay lập tức.</p>
                        </div>
                    </div>

                    <div class="faq-item">
                        <div class="faq-head">
                            <span>Chính sách bảo hành như thế nào?</span>
                            <i class="fas fa-plus"></i>
                        </div>
                        <div class="faq-body">
                            <p>Chúng tôi cam kết bảo hành full thời gian cho hầu hết các sản phẩm. Nếu tài khoản bị lỗi
                                mật khẩu, mất premium... bạn sẽ được cấp tài khoản mới hoặc fix lỗi nhanh nhất có thể.
                                Chi tiết xem tại trang Chính sách.</p>
                        </div>
                    </div>

                    <div class="faq-item">
                        <div class="faq-head">
                            <span>Có hỗ trợ cài đặt không?</span>
                            <i class="fas fa-plus"></i>
                        </div>
                        <div class="faq-body">
                            <p>Tất nhiên! Đội ngũ support của KaiShop luôn sẵn sàng hỗ trợ bạn đăng nhập, đổi mật khẩu
                                hoặc hướng dẫn sử dụng sản phẩm qua Ultraview/Teamviewer nếu cần.</p>
                        </div>
                    </div>

                    <div class="faq-item">
                        <div class="faq-head">
                            <span>Tôi có thể làm cộng tác viên không?</span>
                            <i class="fas fa-plus"></i>
                        </div>
                        <div class="faq-body">
                            <p>Có, chúng tôi có chính sách chiết khấu cực tốt cho Đại lý và Cộng tác viên. Vui lòng liên
                                hệ trực tiếp để trao đổi thêm chi tiết.</p>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- CTA -->
        <section id="support" class="section">
            <div class="container">
                <div class="cta-box fade-up">
                    <div class="cta-content">
                        <h2>Sẵn sàng trải nghiệm dịch vụ tốt nhất?</h2>
                        <p>Hãy trải nghiệm sản phẩm chất lượng cao từ KaiShop!</p>
                        <div class="cta-btns">
                            <a href="<?= url('sanpham') ?>" class="btn btn-primary btn-lg">Xem tất cả sản phẩm</a>
                        </div>
                    </div>
                </div>
            </div>
        </section>
    </main>
</div>

<!-- Popup Notification (Required) -->
<?php require_once __DIR__ . '/../includes/popup_notification.php'; ?>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>

<!-- Scripts -->
<script>
    document.addEventListener('DOMContentLoaded', function () {
        // Scroll Animation Observer
        const observerOptions = {
            threshold: 0.15,
            rootMargin: '0px 0px -50px 0px'
        };

        const observer = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    entry.target.classList.add('visible');

                    // Trigger counters if inside
                    if (entry.target.classList.contains('dashboard-section')) {
                        startCounters();
                    }

                    observer.unobserve(entry.target);
                }
            });
        }, observerOptions);

        document.querySelectorAll('.fade-up, .stagger-box').forEach(el => observer.observe(el));

        // FAQ Toggle
        document.querySelectorAll('.faq-head').forEach(header => {
            header.addEventListener('click', () => {
                const item = header.parentElement;
                const isActive = item.classList.contains('active');

                // Close others
                document.querySelectorAll('.faq-item').forEach(i => i.classList.remove('active'));

                if (!isActive) item.classList.add('active');
            });
        });

        // Number Counter Animation
        function startCounters() {
            document.querySelectorAll('.counter').forEach(counter => {
                const target = +counter.getAttribute('data-target');
                const duration = 2000; // ms
                const increment = target / (duration / 16);

                let current = 0;
                const updateCounter = () => {
                    current += increment;
                    if (current < target) {
                        counter.innerText = Math.ceil(current).toLocaleString();
                        requestAnimationFrame(updateCounter);
                    } else {
                        counter.innerText = target.toLocaleString() + '+';
                    }
                };
                updateCounter();
            });
        }

        // Advanced Typing Effect
        // Advanced Typing Effect (fixed)
        const texts = [
            "Nhanh chóng - Tiện lợi - Bảo mật tuyệt đối",
            "Giá rẻ - Uy tín #1 VN - Bảo hành trọn đời",
            "Hỗ trợ nhiệt tình - Mua hàng tự động 24/7"
        ];

        let textIndex = 0;   // đang ở câu nào
        let charIndex = 0;   // đang gõ tới ký tự nào trong câu
        let isDeleting = false; // trạng thái xoá hay gõ

        const el = document.getElementById("typing-text");

        (function type() {
            if (!el) return; // nếu chưa có phần tử thì thoát (tránh lỗi)

            const current = texts[textIndex];
            const visible = current.slice(0, charIndex);

            el.textContent = visible;

            // tốc độ gõ/xoá
            let delay = isDeleting ? 40 : 100;

            if (!isDeleting && charIndex === current.length) {
                // đã gõ xong -> dừng 2s rồi bắt đầu xoá
                isDeleting = true;
                delay = 2000;
            } else if (isDeleting && charIndex === 0) {
                // đã xoá xong -> chuyển sang câu tiếp theo
                isDeleting = false;
                textIndex = (textIndex + 1) % texts.length;
                delay = 200; // nghỉ nhẹ trước khi gõ câu mới
            } else {
                // tiếp tục gõ/xoá
                charIndex += isDeleting ? -1 : 1;
            }

            setTimeout(type, delay);
        })();



    });
</script>