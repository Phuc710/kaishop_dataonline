<?php
/**
 * Popup Notification Component
 * Hiển thị popup thông báo với overlay mờ trên trang chủ
 */

// Get active popups from database
$active_popups = [];
try {
    $stmt = $pdo->query("SELECT * FROM popup_notifications WHERE is_active = 1 ORDER BY display_order ASC LIMIT 1");
    $active_popups = $stmt->fetchAll();
} catch (Exception $e) {
    // Table might not exist yet, ignore error
}

if (empty($active_popups)) {
    return; // Don't display anything if no active popups
}

$popup = $active_popups[0]; // Get the first popup
?>

<!-- Popup Notification -->
<div id="notification-popup-overlay" class="notification-popup-overlay" style="display:none;">
    <div class="notification-popup-container">
        <button class="popup-close-btn" onclick="closeNotificationPopup()">
            <i class="fas fa-times"></i>
        </button>

        <?php if (!empty($popup['link'])): ?>
            <a href="<?= e($popup['link']) ?>" target="_blank" class="popup-content-link">
            <?php endif; ?>

            <div class="popup-content">
                <?php if (!empty($popup['image'])): ?>
                    <div class="popup-image-bg" style="background-image: url('/kaishop/<?= e($popup['image']) ?>')">
                        <div class="popup-overlay-gradient"></div>
                        <div class="popup-text-overlay">
                            <?php if (!empty($popup['title'])): ?>
                                <div id="popup-html-content-wrapper">
                                    <div class="popup-content-html"><?= nl2br($popup['title']) ?></div>
                                        </div>
                                <?php endif; ?>
                            </div>
                            </div>
                <?php else: ?>
                        <!-- Fallback for popups without image - use background code -->

                                                    <div class="popup-image-bg" <?php if (!empty($popup['background_code'])): ?>style="<?= htmlspecialchars($popup['background_code']) ?>"<?php endif; ?>>
                          <div   class="popup-text-overlay">
                                <?php if (!empty($popup['title'])): ?>
                                 <div    id="popup-html-content-wrapper">
                                        <div class="popup-content-html"><?= nl2br($popup['title']) ?></div>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endif; ?>
            </div>
 
           <?php if (!empty($popup['link'])): ?>
                </a>
        <?php endif; ?>
    </div>
</div>

<style>
    .notification-popup-overlay {
        position: fixed;
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
        background: rgba(0, 0, 0, 0.85);
        z-index: 99999;
        display: flex;
        align-items: center;
        justify-content: center;
        padding: 1.5rem;
        animation: fadeIn 0.4s ease;
        backdrop-filter: blur(8px);
    }

    .notification-popup-container {
        position: relative;
        width: 70vw;
        max-width: 900px;
        max-height: 90vh;
        animation: popupSlideUp 0.6s cubic-bezier(0.34, 1.56, 0.64, 1);
    }

    .popup-close-btn {
        position: absolute;
        top: -18px;
        right: -18px;
        width: 50px;
        height: 50px;
        border-radius: 50%;
        background: linear-gradient(135deg, #ef4444, #dc2626);
        border: 4px solid rgba(15, 23, 42, 0.8);
        color: #ffffff;
        font-size: 1.4rem;
        cursor: pointer;
        z-index: 10;
        display: flex;
        align-items: center;
        justify-content: center;
        transition: all 0.3s ease;
    }

    .popup-close-btn:hover {
        transform: rotate(90deg) scale(1.15);
        background: linear-gradient(135deg, #dc2626, #b91c1c);
    }

    .popup-content-link {
        display: block;
        text-decoration: none;
        color: inherit;
    }

    .popup-content {
        border: 2px solid rgba(124, 58, 237, 0.4);
        border-radius: 20px;
        overflow: hidden;
        box-shadow: 0 25px 60px rgba(0, 0, 0, 0.7),
            0 0 100px rgba(124, 58, 237, 0.3);
        background: transparent;
    }

    .popup-image-bg {
        position: relative;
        width: 100%;
        min-height: 450px;
        max-height: none;
        background-size: cover;
        background-position: center;
        background-repeat: no-repeat;
        display: flex;
        align-items: center;
        justify-content: center;
    }

    .popup-overlay-gradient {
        position: absolute;
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
        backdrop-filter: blur(0px);
    }

    .popup-text-overlay {
        position: relative;
        z-index: 2;
        text-align: center;
        max-width: 100%;
        width: 100%;
    }

    .popup-title-overlay {
        font-size: 2.5rem;
        font-weight: 900;
        color: #ffffff;
        margin: 0 0 1.5rem;
        line-height: 1.2;
        text-shadow: 0 4px 20px rgba(0, 0, 0, 0.8),
            0 0 60px rgba(124, 58, 237, 0.6),
            0 2px 4px rgba(0, 0, 0, 0.4);
        letter-spacing: -0.02em;
    }

    .popup-description-overlay {
        font-size: 1.25rem;
        color: #ffffff;
        margin: 0;
        line-height: 1.8;
        font-weight: 600;
        text-shadow: 0 2px 12px rgba(0, 0, 0, 0.8),
            0 0 40px rgba(249, 115, 22, 0.4),
            0 1px 3px rgba(0, 0, 0, 0.5);
    }

    /* 1. Reset all global styles inside popup */
    #popup-html-content-wrapper {
        all: initial;
        /* Reset toàn bộ */
        font-family: inherit;
        /* Giữ lại font chữ */
        display: block;
        width: 100%;
        box-sizing: border-box;
    }

    #popup-html-content-wrapper * {
        box-sizing: border-box;
    }

    /* 2. Style riêng cho các thẻ chuẩn HTML trong popup */
    #popup-html-content-wrapper h1,
    #popup-html-content-wrapper h2,
    #popup-html-content-wrapper h3,
    #popup-html-content-wrapper p,
    #popup-html-content-wrapper div,
    #popup-html-content-wrapper span {
        margin: 0;
        padding: 0;
        border: 0;
        vertical-align: baseline;
        background: transparent;
        line-height: 1.5;
        color: #fff;
        /* Mặc định text trắng */
        text-shadow: none;
        /* Xóa shadow mặc định của theme */
        letter-spacing: normal;
    }

    #popup-html-content-wrapper h1 {
        font-size: 2.5em;
        font-weight: bold;
        margin-bottom: 0.5em;
    }

    #popup-html-content-wrapper h2 {
        font-size: 2em;
        font-weight: bold;
        margin-bottom: 0.5em;
    }

    #popup-html-content-wrapper p {
        font-size: 1.1em;
        margin-bottom: 1em;
    }

    /* 3. Class Helper Riêng Biệt (Dùng ID để ưu tiên tuyệt đối) */

    /* Gradient Text */
    #popup-html-content-wrapper .popup-gradient-text {
        font-size: 4rem !important;
        font-weight: 900 !important;
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        -webkit-background-clip: text;
        background-clip: text;
        -webkit-text-fill-color: transparent;
        color: transparent;
        /* Fallback */
        margin-bottom: 1rem;
        filter: drop-shadow(0 4px 8px rgba(102, 126, 234, 0.5));
        line-height: 1.2;
        display: inline-block;
        /* Fix lỗi text-clip trên một số trình duyệt */
        width: 100%;
    }

    /* White Text */
    #popup-html-content-wrapper .popup-text-white {
        color: #fff !important;
        font-size: 1.3rem !important;
        text-shadow: 0 2px 10px rgba(0, 0, 0, 0.8) !important;
        margin: 0.5rem 0;
        font-weight: 500;
    }

    /* Glow Text */
    #popup-html-content-wrapper .popup-text-glow {
        color: #fff !important;
        font-size: 3.5rem !important;
        font-weight: bold;
        text-shadow:
            0 0 10px #fff,
            0 0 20px #fff,
            0 0 30px #e60073,
            0 0 40px #e60073,
            0 0 50px #e60073 !important;
        margin: 0;
        line-height: 1.2;
    }

    @keyframes fadeIn {
        from {
            opacity: 0;
        }

        to {
            opacity: 1;
        }
    }

    @keyframes popupSlideUp {
        from {
            opacity: 0;
            transform: translateY(50px) scale(0.9);
        }

        to {
            opacity: 1;
            transform: translateY(0) scale(1);
        }
    }

    /* Responsive */
    @media (max-width: 768px) {
        .notification-popup-overlay {
            padding: 1rem;
        }

        .notification-popup-container {
            width: 95vw;
            max-width: 95vw;
        }

        .popup-image-bg {
            min-height: 250px;
            padding: 2rem 1.5rem;
        }

        .popup-title-overlay {
            font-size: 1.75rem;
        }

        .popup-description-overlay {
            font-size: 1rem;
        }

        .popup-close-btn {
            width: 42px;
            height: 42px;
            font-size: 1.1rem;
            top: -14px;
            right: -14px;
            border-width: 3px;
        }
    }

    @media (max-width: 480px) {
        .popup-image-bg {
            min-height: 200px;
            padding: 1.5rem 1rem;
        }

        .popup-title-overlay {
            font-size: 1.4rem;
        }

        .popup-description-overlay {
            font-size: 0.9rem;
        }
    }
</style>

<script>
    // Show popup - only close on X button, save to session
    (function () {
        const popupId = 'popup_<?= $popup['id'] ?>';

        // Check if user has closed this popup before (in this session)
        if (sessionStorage.getItem(popupId + '_closed')) {
            return; // Don't show popup
        }

        // Show popup immediately (no delay)
        document.getElementById('notification-popup-overlay').style.display = 'flex';
    })();

    function closeNotificationPopup() {
        const popupId = 'popup_<?= $popup['id'] ?>';
        const overlay = document.getElementById('notification-popup-overlay');

        // Save to session that user closed this popup
        sessionStorage.setItem(popupId + '_closed', 'true');

        // Animate and hide
        overlay.style.animation = 'fadeOut 0.3s ease';
        setTimeout(function () {
            overlay.style.display = 'none';
        }, 300);
    }
</script>

<style>
    @keyframes fadeOut {
        from {
            opacity: 1;
        }

        to {
            opacity: 0;
        }
    }
</style>