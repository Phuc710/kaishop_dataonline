<?php
class FooterComponent
{
    private $siteName;
    private $siteEmail;
    private $siteLogo;
    private $telegramLink;

    public function __construct()
    {
        $this->siteName = get_setting('site_name', SITE_NAME);
        $this->siteEmail = get_setting('site_email', defined('CONTACT_EMAIL') ? CONTACT_EMAIL : 'kaishop365@gmail.com');
        $this->siteLogo = get_setting('footer_logo', 'images/footer.gif');
        $this->telegramLink = get_setting('telegram_link', 'https://t.me/Biinj');
    }


    public function render()
    {
        ?>
        <footer class="kai-footer">
            <div class="container kai-footer-inner">
                <div class="kai-footer-col">
                    <img src="<?= asset($this->siteLogo) ?>" alt="<?= $this->siteName ?>" class="kai-footer-logo-img">
                    <p class="kai-footer-text">
                        <b style="color: #fff;"><?= $this->siteName ?></b> -
                        <?= get_setting('site_slogan', 'Nơi có All thứ bạn cần uy tín, chất lượng, giá rẻ nhất thị trường.') ?>
                    </p>
                    <p class="kai-footer-badge">Thanh toán tự động • Hỗ trợ 24/7</p>
                </div>

                <div class="kai-footer-col">
                    <h4 class="kai-footer-title">Liên Kết</h4>
                    <ul class="kai-footer-links">
                        <li><a href="<?= url('') ?>">Trang Chủ</a></li>
                        <li><a href="<?= url('sanpham') ?>">Sản Phẩm</a></li>
                        <li><a href="<?= url('user') ?>">Tài Khoản</a></li>
                        <li><a href="<?= url('chinhsach') ?>">Chính Sách</a></li>
                    </ul>
                </div>

                <div class="kai-footer-col">
                    <h4 class="kai-footer-title">Hỗ Trợ</h4>
                    <ul class="kai-footer-links">
                        <li><a href="<?= url('ticket/create') ?>">Tạo Ticket Hỗ Trợ</a></li>
                        <li><a href="<?= url('chinhsach') ?>">Chính Sách Bảo Hành</a></li>
                    </ul>
                </div>

                <div class="kai-footer-col">
                    <h4 class="kai-footer-title">Liên Hệ</h4>
                    <ul class="kai-footer-links">
                        <li><i class="fas fa-envelope"></i> <?= $this->siteEmail ?></li>
                        <li><i class="fas fa-phone"></i> <a
                                href="<?= get_setting('social_zalo', 'https://zalo.me/0812420710') ?>"
                                target="_blank"><?= get_setting('contact_phone', '081.242.0710') ?></a>
                        </li>
                        <li><i class="fab fa-telegram"></i> <a href="<?= $this->telegramLink ?>" target="_blank">Telegram</a>
                        </li>
                    </ul>
                </div>
            </div>

            <!-- Social icons (New Design) -->
            <div class="kai-footer-social">
                <div class="social-buttons">
                    <a href="<?= $this->telegramLink ?>" target="_blank" class="social-btn telegram" aria-label="Telegram">
                        <i class="fab fa-telegram-plane"></i>
                    </a>
                    <a href="<?= get_setting('social_tiktok', 'https://www.tiktok.com/') ?>" target="_blank"
                        class="social-btn tiktok" aria-label="TikTok">
                        <i class="fab fa-tiktok"></i>
                    </a>
                    <a href="<?= get_setting('social_youtube', 'https://youtube.com/') ?>" target="_blank"
                        class="social-btn youtube" aria-label="YouTube">
                        <i class="fab fa-youtube"></i>
                    </a>
                </div>
            </div>

            <div class="kai-footer-bottom">
                <p>&copy; <?= date('Y') ?>         <?= $this->siteName ?>. All rights reserved.</p>
            </div>
        </footer>

        <!-- Scroll to Top Button -->
        <button id="scrollToTop" class="scroll-to-top" aria-label="Scroll to top">
            <i class="fas fa-arrow-up"></i>
        </button>

        <script>
            // Scroll to Top functionality
            const scrollBtn = document.getElementById('scrollToTop');

            window.addEventListener('scroll', () => {
                if (window.pageYOffset > 300) {
                    scrollBtn.classList.add('show');
                } else {
                    scrollBtn.classList.remove('show');
                }
            });

            scrollBtn.addEventListener('click', () => {
                window.scrollTo({
                    top: 0,
                    behavior: 'smooth'
                });
            });
        </script>

        <?php $this->renderStyles(); ?>

        <!-- Real-time Ban Detection -->
        <?php if (isset($_SESSION['user_id'])): ?>
            <script>
                // Set user ID in body dataset for ban detector
                document.body.dataset.userId = '<?= $_SESSION['user_id'] ?>';
            </script>
            <script src="<?= asset('js/ban-detector.js') ?>"></script>
        <?php endif; ?>


        <!-- Security: Image Protection Only -->
        <script src="<?= asset('js/content-protection.js') ?>"></script>

        <!-- Enable Text Copying -->
        <script src="<?= asset('js/enable-copy.js') ?>"></script>
        </body>

        </html>
        <?php
    }

    private function renderStyles()
    {
        ?>
        <style>
            .kai-footer {
                background: radial-gradient(circle at top, #0b1120 0, #020617 55%, #010313 100%);
                border-top: 1px solid rgba(148, 163, 184, 0.25);
                padding: 3rem 0 1.5rem;
                position: relative;
                overflow: hidden;
            }

            .kai-footer::before {
                content: "";
                position: absolute;
                inset: 0;
                background:
                    radial-gradient(circle at 0 0, rgba(124, 58, 237, 0.25), transparent 55%),
                    radial-gradient(circle at 100% 100%, rgba(249, 115, 22, 0.2), transparent 55%);
                opacity: 0.3;
                pointer-events: none;
            }

            .kai-footer-inner {
                position: relative;
                z-index: 1;
                display: grid;
                grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
                gap: 2.5rem;
                max-width: 1400px;
                margin: 0 auto;
                padding: 0 2rem;
            }

            .kai-footer-col {
                color: #cbd5f5;
            }

            .kai-footer-logo-img {
                height: 35px;
                position: static;
            }

            .kai-footer-logo {
                font-size: 1.6rem;
                font-weight: 700;
                color: #f9fafb;
                margin-bottom: 0.4rem;
                -webkit-background-clip: text;
                -webkit-text-fill-color: transparent;
                transition: all 0.3s ease;
            }

            .kai-footer-text {
                color: #94a3b8;
                line-height: 1.6;
                margin-bottom: 0.6rem;
                font-size: 0.95rem;
            }

            .kai-footer-badge {
                display: inline-flex;
                align-items: center;
                padding: 0.3rem 0.7rem;
                border-radius: 999px;
                border: 1px solid rgba(0, 106, 255, 0.6);
                background: rgba(15, 23, 42, 0.95);
                font-size: 0.75rem;
            }

            .kai-footer-title {
                font-size: 1.05rem;
                font-weight: 600;
                margin-bottom: 0.7rem;
            }

            .kai-footer-links {
                list-style: none;
                padding: 0;
                margin: 0;
            }

            .kai-footer-links li {
                margin-bottom: 0.45rem;
                font-size: 0.92rem;
                transition: transform 0.2s ease;
            }

            .kai-footer-links li:hover {
                transform: translateX(5px);
            }

            .kai-footer-links a {
                color: #94a3b8;
                text-decoration: none;
                transition: color 0.25s ease;
            }

            .kai-footer-links a:hover {
                color: #a855f7;
            }

            .kai-footer-links i {
                margin-right: 0.4rem;
                color: #a855f7;
            }

            /* Social icons - Premium Style */
            .kai-footer-social {
                position: relative;
                z-index: 1;
                margin-top: 2rem;
                display: flex;
                justify-content: center;
            }

            .social-buttons {
                display: flex;
                gap: 1.5rem;
                align-items: center;
            }

            .social-btn {
                display: flex;
                align-items: center;
                justify-content: center;
                width: 42px;
                height: 42px;
                border-radius: 50%;
                background: rgba(255, 255, 255, 0.03);
                border: 1px solid rgba(255, 255, 255, 0.08);
                color: #cbd5e1;
                font-size: 1.25rem;
                text-decoration: none;
                transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
                position: relative;
                backdrop-filter: blur(4px);
                overflow: hidden;
            }

            .social-btn:hover {
                transform: translateY(-4px) scale(1.1);
                color: #fff;
            }

            /* Telegram: Blue */
            .social-btn.telegram:hover {
                background: #229ED9;
                border-color: #229ED9;
                box-shadow: 0 0 20px rgba(34, 158, 217, 0.4), 0 0 10px rgba(34, 158, 217, 0.2);
            }

            /* TikTok: Black + Cyan/Red Glow */
            .social-btn.tiktok:hover {
                background: #000;
                border-color: #333;
                box-shadow: -2px -2px 10px rgba(37, 244, 238, 0.4), 2px 2px 10px rgba(254, 44, 85, 0.4);
            }

            /* YouTube: Red */
            .social-btn.youtube:hover {
                background: #FF0000;
                border-color: #FF0000;
                box-shadow: 0 0 20px rgba(255, 0, 0, 0.4), 0 0 10px rgba(255, 0, 0, 0.2);
            }

            .kai-footer-bottom {
                position: relative;
                z-index: 1;
                border-top: 1px solid rgba(148, 163, 184, 0.25);
                margin-top: 1.7rem;
                padding-top: 1.1rem;
                text-align: center;
            }

            .kai-footer-bottom p {
                color: #64748b;
                font-size: 0.85rem;
            }

            @media (max-width: 768px) {
                .kai-footer {
                    padding: 3rem 0 2rem;
                }

                .kai-footer-inner {
                    gap: 2.5rem;
                    grid-template-columns: 1fr;
                    /* Force single column on mobile */
                    text-align: center;
                    /* Center align for better mobile look */
                }

                /* Center the list items */
                .kai-footer-links li {
                    display: flex;
                    justify-content: center;
                }

                .kai-footer-badge {
                    margin: 0 auto;
                }

                .kai-footer-social {
                    margin-top: 3rem;
                }
            }

            /* Scroll to Top Button (Redesigned) */
            .scroll-to-top {
                position: fixed;
                bottom: 30px;
                right: 30px;
                width: 50px;
                height: 50px;
                background: rgba(15, 23, 42, 0.8);
                backdrop-filter: blur(10px);
                border-radius: 50%;
                color: #fff;
                font-size: 20px;
                cursor: pointer;
                opacity: 0;
                visibility: hidden;
                transform: translateY(20px) scale(0.8);
                transition: all 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275);
                z-index: 1000;
                display: flex;
                align-items: center;
                justify-content: center;
                box-shadow: 0 10px 25px rgba(0, 0, 0, 0.3);
            }

            .scroll-to-top.show {
                opacity: 1;
                visibility: visible;
                transform: translateY(0) scale(1);
            }

            .scroll-to-top:hover {
                background: #7c3aed;
                border-color: rgba(124, 58, 237, 0.5);
                transform: translateY(-5px) scale(1.1);
                box-shadow: 0 15px 30px rgba(124, 58, 237, 0.4);
                color: #fff;
            }

            .scroll-to-top:active {
                transform: translateY(-2px) scale(1);
            }

            @media (max-width: 768px) {
                .scroll-to-top {
                    bottom: 20px;
                    right: 20px;
                    width: 45px;
                    height: 45px;
                    font-size: 18px;
                }
            }
        </style>
        <?php
    }
}
