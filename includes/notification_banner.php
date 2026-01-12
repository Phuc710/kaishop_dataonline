<?php
// Get active notifications from database
$notifications = $pdo->query("SELECT * FROM notification_banners WHERE is_active = 1 ORDER BY display_order ASC")->fetchAll();
?>

<div class="notification-banner-wrapper">
    <?php foreach ($notifications as $notif): ?>
        <?php
        $speed_value = isset($notif['speed']) ? intval($notif['speed']) : 50;
        $duration = 15 - (($speed_value - 1) / 99) * 7; // Map 1-100 to 15-8s
        ?>
        <div class="notification-banner" style="background: linear-gradient(90deg, <?= $notif['bg_color'] ?? '#1c2cbaff' ?>, <?= $notif['bg_color_2'] ?? $notif['bg_color'] ?? '#3a64edff' ?>, <?= $notif['bg_color'] ?? '#1d025bff' ?>);
                color: <?= $notif['text_color'] ?? '#ffffff' ?>; 
                --speed: <?= number_format($duration, 1) ?>s;">
            <div class="marquee-content">
                <?php
                // Repeat content 3 times for seamless continuous loop
                for ($i = 0; $i < 3; $i++):
                    ?>
                    <span class="marquee-item">
                        <?php if (!empty($notif['icon'])): ?>
                            <span class="notif-icon"><?= $notif['icon'] ?></span>
                        <?php endif; ?>
                        <span class="notif-message"><?= $notif['message'] ?></span>
                    </span>
                <?php endfor; ?>
            </div>
        </div>
    <?php endforeach; ?>
</div>

<style>
    .notification-banner-wrapper {
        width: 100%;
        z-index: 998;
        overflow: hidden;
        box-shadow: none;
        left: 0;
        right: 0;
    }

    .notification-banner {
        width: 100%;
        height: 36px;
        overflow: hidden;
        position: relative;
        background-size: 200% 100%;
        animation: gradientShift 5s ease infinite;
        display: flex;
        align-items: center;
        margin: 0;
        padding: 0;
    }

    /* Hiệu ứng shine chạy qua */
    .notification-banner::before {
        content: '';
        position: absolute;
        top: 0;
        left: -100%;
        width: 100%;
        height: 100%;
        background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.15), transparent);
        animation: shine 3s infinite;
        z-index: 1;
    }

    .marquee-content {
        display: flex;
        align-items: center;
        white-space: nowrap;
        animation: marqueeScroll var(--speed, 12s) linear infinite;
        height: 100%;
        position: relative;
        z-index: 2;
    }

    .marquee-item {
        display: inline-flex;
        align-items: center;
        gap: 0.75rem;
        padding: 0 3rem;
        font-weight: 700;
        font-size: 1rem;
        color: #ffffff;
        letter-spacing: 0.5px;
        text-shadow: 0 2px 8px rgba(0, 0, 0, 0.5), 0 0 20px rgba(255, 255, 255, 0.3);
    }

    .notif-icon {
        font-size: 1.4rem;
        display: inline-block;
        animation: bounce 2s infinite;
        filter: drop-shadow(0 0 10px rgba(255, 255, 255, 0.6));
    }

    .notif-message {
        display: inline-block;
    }

    @keyframes marqueeScroll {
        0% {
            transform: translateX(0%);
        }

        100% {
            transform: translateX(-33.333%);
        }
    }

    @keyframes gradientShift {

        0%,
        100% {
            background-position: 0% 50%;
        }

        50% {
            background-position: 100% 50%;
        }
    }

    @keyframes shine {
        0% {
            left: -100%;
        }

        50%,
        100% {
            left: 100%;
        }
    }

    @keyframes bounce {

        0%,
        100% {
            transform: translateY(0) scale(1);
        }

        50% {
            transform: translateY(-3px) scale(1.1);
        }
    }

    /* Responsive */
    @media (max-width: 768px) {
        .notification-banner {
            height: 45px;
        }

        .marquee-item {
            font-size: 0.85rem;
            padding: 0 2rem;
        }

        .notif-icon {
            font-size: 1.1rem;
        }
    }

    @media (max-width: 480px) {
        .notification-banner {
            height: 42px;
        }

        .marquee-item {
            font-size: 0.8rem;
            padding: 0 1.5rem;
            gap: 0.5rem;
        }

        .notif-icon {
            font-size: 1rem;
        }
    }
</style>