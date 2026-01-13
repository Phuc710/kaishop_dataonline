<style>
    /* ============================================
       KaiShop Homepage - Modern UI Styles
       Complete Rewrite with Fixed Layout
    ============================================ */

    :root {
        --bg-main: #020617;
        --bg-card: rgba(30, 41, 59, 0.5);
        --bg-card-hover: rgba(30, 41, 59, 0.8);
        --primary: #8b5cf6;
        --primary-light: #a78bfa;
        --secondary: #ec4899;
        --accent: #f59e0b;
        --success: #10b981;
        --text-main: #f8fafc;
        --text-muted: #94a3b8;
        --border-color: rgba(148, 163, 184, 0.15);
        --radius-sm: 8px;
        --radius-md: 16px;
        --radius-lg: 24px;
        --radius-xl: 32px;
        --transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    }

    /* Hero Image Theme Switching - Mutually Exclusive */
    .light-mode-img,
    .halloween-mode-img,
    .noel-mode-img,
    .tet-mode-img {
        display: none !important;
    }

    .dark-mode-img {
        display: block !important;
    }

    [data-theme="light"] .light-mode-img {
        display: block !important;
    }

    [data-theme="light"] .dark-mode-img {
        display: none !important;
    }

    [data-theme="light"] .hero-image-wrapper {
        max-width: 700px !important;
    }

    /* --- LIGHT MODE OVERRIDES --- */
    [data-theme="light"] {
        --bg-main: #f8fafc;
        --bg-card: #ffffff;
        --bg-card-hover: #f1f5f9;
        --text-main: #0f172a;
        --text-muted: #64748b;
        --border-color: #e2e8f0;
    }

    [data-theme="light"] body {
        background: #f8fafc !important;
        color: #0f172a !important;
    }

    [data-theme="light"] .hero-title,
    [data-theme="light"] .section-title,
    [data-theme="light"] .feature-title,
    [data-theme="light"] .promo-content h2,
    [data-theme="light"] .stat-value {
        color: #0f172a !important;
    }

    [data-theme="light"] .hero-subtitle,
    [data-theme="light"] .feature-text,
    [data-theme="light"] .promo-content p,
    [data-theme="light"] .stat-label {
        color: #64748b !important;
    }

    [data-theme="light"] .feature-card,
    [data-theme="light"] .stat-card,
    [data-theme="light"] .hero-badge-soft {
        background: #ffffff !important;
        border-color: #e2e8f0 !important;
        box-shadow: none !important;
        backdrop-filter: none;
    }

    [data-theme="light"] .feature-card:hover,
    [data-theme="light"] .stat-card:hover {
        box-shadow: none !important;
        border-color: var(--primary) !important;
    }

    /* Adjust gradient text for light mode */
    [data-theme="light"] .text-gradient {
        background: linear-gradient(135deg, #7c3aed 0%, #db2777 100%);
        -webkit-background-clip: text;
        -webkit-text-fill-color: transparent;
        background-clip: text;
    }

    /* Fix hero search in light mode */
    [data-theme="light"] .hero-search {
        background: #ffffff !important;
        border-color: #e2e8f0 !important;
        box-shadow: none !important;
    }

    [data-theme="light"] .search-placeholder {
        color: #64748b !important;
    }

    [data-theme="light"] .search-keywords-rotate {
        color: #0f172a !important;
    }

    [data-theme="light"] .search-icon {
        color: #94a3b8 !important;
    }

    [data-theme="light"] .hero-badge-soft strong {
        color: #0f172a !important;
    }

    [data-theme="light"] .hero-badge-soft span {
        color: #64748b !important;
    }


    /* ============ RESET & BODY ============ */
    * {
        margin: 0;
        padding: 0;
        box-sizing: border-box;
    }

    body {
        font-family: "Poppins", -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
        background: #020617;
        min-height: 100vh;
        color: #f8fafc;
        margin: 0;
        padding: 0;
        overflow-x: hidden;
    }

    /* ============ CONTAINER (Sync with Header) ============ */
    .container {
        max-width: 1400px;
        margin: 0 auto;
        padding: 0 2rem;
        width: 100%;
    }

    /* ============ ANIMATIONS ============ */
    @keyframes fadeUp {
        from {
            opacity: 0;
            transform: translateY(30px);
        }

        to {
            opacity: 1;
            transform: translateY(0);
        }
    }

    @keyframes float {

        0%,
        100% {
            transform: translateY(0px);
        }

        50% {
            transform: translateY(-15px);
        }
    }

    @keyframes pulse-glow {

        0%,
        100% {
            box-shadow: none;
        }

        50% {
            box-shadow: none;
        }
    }

    @keyframes blink {

        0%,
        50% {
            opacity: 1;
        }

        51%,
        100% {
            opacity: 0;
        }
    }

    @keyframes fadeUp {
        from {
            opacity: 0;
            transform: translateY(30px);
        }

        to {
            opacity: 1;
            transform: translateY(0);
        }
    }

    .fade-up {
        opacity: 0;
        transform: translateY(25px);
        transition: opacity 0.6s ease-out, transform 0.6s ease-out;
    }

    .fade-up.visible {
        opacity: 1;
        transform: translateY(0);
    }

    .stagger-box>* {
        opacity: 0;
        transform: translateY(20px);
        transition: opacity 0.5s ease, transform 0.5s ease;
    }

    .stagger-box.visible>*:nth-child(1) {
        opacity: 1;
        transform: translateY(0);
        transition-delay: 0s;
    }

    .stagger-box.visible>*:nth-child(2) {
        opacity: 1;
        transform: translateY(0);
        transition-delay: 0s;
    }

    .stagger-box.visible>*:nth-child(3) {
        opacity: 1;
        transform: translateY(0);
        transition-delay: 0s;
    }

    .stagger-box.visible>*:nth-child(4) {
        opacity: 1;
        transform: translateY(0);
        transition-delay: 0s;
    }

    /* ============ LAYOUT ============ */
    .page-wrapper {
        position: relative;
        overflow-x: hidden;
        background: var(--bg-main);
    }

    /* Animated Background (like product page) */
    .page-wrapper::before {
        content: "";
        position: fixed;
        inset: 0;
        background: radial-gradient(ellipse at top, rgba(139, 92, 246, 0.15), transparent 50%),
            radial-gradient(ellipse at bottom, rgba(236, 72, 153, 0.15), transparent 50%);
        pointer-events: none;
        z-index: 0;
        animation: bgPulse 8s ease-in-out infinite;
    }

    @keyframes bgPulse {

        0%,
        100% {
            opacity: 0.5;
        }

        50% {
            opacity: 0.8;
        }
    }

    .section {
        padding: 80px 0;
        position: relative;
        z-index: 1;
    }



    /* ============ HERO SECTION ============ */
    .hero {
        position: relative;
        padding: 20px 0 40px;
        min-height: calc(100vh - 80px);
        display: flex;
        align-items: center;
        z-index: 1;
    }

    .hero-bg-glow {
        display: none;
    }

    .hero-inner {
        display: grid;
        grid-template-columns: 1fr 1.2fr;
        gap: 60px;
        align-items: center;
        position: relative;
        z-index: 1;
    }

    .hero-left {
        display: flex;
        justify-content: center;
        align-items: center;
    }

    .hero-image-wrapper {
        position: relative;
        max-width: 480px;
        width: 100%;
    }

    .hero-logo-image {
        width: 85%;
        height: auto;
        animation: float 6s ease-in-out infinite;
    }

    .hero-content {
        display: flex;
        flex-direction: column;
        align-items: flex-start;
    }

    /* Hero Badge */
    .hero-badge {
        display: inline-flex;
        align-items: center;
        gap: 10px;
        padding: 8px 16px;
        border-radius: 50px;
        background: rgba(139, 92, 246, 0.12);
        border: 1px solid rgba(139, 92, 246, 0.25);
        color: var(--primary-light);
        font-weight: 600;
        font-size: 0.85rem;
        margin-bottom: 20px;
        margin-top: 30px;
        transition: all 0.3s ease;
        box-shadow: 0 4px 12px rgba(139, 92, 246, 0.15);
    }

    /* Hero Title */
    .hero-title {
        font-size: clamp(2.2rem, 4.5vw, 3.5rem);
        font-weight: 800;
        line-height: 1.15;
        background: linear-gradient(135deg, #f8fafc 0%, #ffffffff 100%);
        -webkit-background-clip: text;
        -webkit-text-fill-color: transparent;
        background-clip: text;
        margin-bottom: 12px;
        animation: fadeUp 0.8s ease-out 0.1s backwards;
    }

    .text-gradient {
        background: linear-gradient(135deg, var(--primary-light) 0%, var(--secondary) 100%);
        -webkit-background-clip: text;
        -webkit-text-fill-color: transparent;
        background-clip: text;
    }

    /* Typing Effect */
    .typing-container {
        font-size: 1.3rem;
        font-weight: 600;
        height: 36px;
        display: flex;
        align-items: center;
        margin-bottom: 20px;
        animation: fadeUp 0.8s ease-out 0.2s backwards;
    }

    .typing-text {
        background: linear-gradient(90deg, #06b6d4 0%, #ec4899 50%, #f59e0b 100%);
        -webkit-background-clip: text;
        -webkit-text-fill-color: transparent;
    }

    .typing-cursor {
        color: var(--primary);
        animation: blink 1s infinite;
        margin-left: 2px;
    }

    /* Hero Subtitle */
    .hero-subtitle {
        font-size: 1.05rem;
        line-height: 1.75;
        color: var(--text-muted);
        margin-bottom: 28px;
        max-width: 100%;
        animation: fadeUp 0.8s ease-out 0.3s backwards;
    }

    .hero-subtitle strong {
        color: var(--text-main);
    }

    /* Hero Feature Badges */
    .hero-badges-row {
        display: flex;
        gap: 16px;
        margin-bottom: 28px;
        flex-wrap: wrap;
        animation: fadeUp 0.8s ease-out 0.4s backwards;
    }

    .hero-badge-soft {
        display: flex;
        align-items: center;
        gap: 14px;
        background: var(--bg-card);
        border: 1px solid var(--border-color);
        padding: 14px 20px;
        border-radius: var(--radius-md);
        backdrop-filter: blur(10px);
        transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        flex: 1;
        min-width: 260px;
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.2);
    }

    .hero-badge-soft:hover {
        background: var(--bg-card-hover);
        border-color: rgba(139, 92, 246, 0.5);
        transform: translateY(-4px);
        box-shadow: 0 12px 32px rgba(139, 92, 246, 0.25);
    }

    .hero-badge-soft i {
        font-size: 1.5rem;
        width: 40px;
        height: 40px;
        display: flex;
        align-items: center;
        justify-content: center;
        background: rgba(139, 92, 246, 0.15);
        border-radius: 12px;
        transition: all 0.3s ease;
    }

    .hero-badge-soft:hover i {
        transform: scale(1.1) rotate(-5deg);
        box-shadow: 0 4px 12px rgba(139, 92, 246, 0.3);
    }

    .hero-badge-soft.secondary i {
        background: rgba(16, 185, 129, 0.15);
        color: var(--success);
    }

    .hero-badge-soft i.text-warning {
        color: var(--accent);
    }

    .hero-badge-soft i.text-primary {
        color: var(--primary);
    }

    .hero-badge-soft div {
        display: flex;
        flex-direction: column;
    }

    .hero-badge-soft strong {
        color: var(--text-main);
        font-size: 0.95rem;
        font-weight: 600;
        margin-bottom: 2px;
    }

    .hero-badge-soft span {
        color: var(--text-muted);
        font-size: 0.8rem;
    }

    /* Hero Actions */
    .hero-actions {
        width: 100%;
        margin-bottom: 28px;
        animation: fadeUp 0.8s ease-out 0.5s backwards;
    }

    .action-buttons {
        display: flex;
        gap: 14px;
        flex-wrap: wrap;
        margin-bottom: 16px;
    }

    .trust-indicator {
        display: flex;
        align-items: center;
        gap: 12px;
    }

    .trust-indicator small {
        color: var(--text-muted);
        font-size: 0.85rem;
    }

    .avatars span {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        background: linear-gradient(135deg, var(--primary), var(--secondary));
        color: white;
        font-size: 0.7rem;
        font-weight: 700;
        padding: 6px 10px;
        border-radius: 20px;
    }

    /* Hero Search - Neafy Style (Unified) */
    .hero-search-wrapper {
        width: 100%;
        max-width: 550px;
        margin: 0;
        background: transparent;
        padding: 0;
        border: none;
        animation: fadeUp 0.8s ease-out 0.7s backwards;
    }

    .hero-search {
        display: flex;
        align-items: center;
        justify-content: space-between;
        padding: 6px 6px 6px 24px;
        gap: 12px;
        background: var(--bg-card);
        /* Adapt to theme */
        border: 1px solid var(--border-color);
        border-radius: 99px !important;
        /* Force pill shape */
        box-shadow: none;
        transition: var(--transition);
        width: 100%;
        position: relative;
        overflow: hidden;
        /* Ensure rounded corners */
    }

    .search-icon {
        color: var(--text-muted);
        font-size: 1.2rem;
        flex-shrink: 0;
    }

    .search-placeholder {
        flex: 1;
        color: var(--text-muted);
        font-size: 1rem;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
        display: flex;
        align-items: center;
        gap: 5px;
        text-align: left;
    }

    .search-keywords-rotate {
        color: var(--text-main);
        font-weight: 500;
        opacity: 0.9;
    }

    /* Gradient Button - Neafy Style */
    .search-btn {
        width: 48px;
        height: 48px;
        min-width: 48px;
        /* Force size, prevent shrinking */
        border-radius: 50%;
        background: linear-gradient(135deg, #a855f7 0%, #ec4899 100%);
        /* Purple to Pink gradient */
        border: none;
        color: white;
        display: flex;
        align-items: center;
        justify-content: center;
        cursor: pointer;
        transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        box-shadow: none;
        flex-shrink: 0;
        /* Never shrink */
        margin: 0;
    }

    .search-btn:hover {
        transform: scale(1.15) rotate(0deg);
        box-shadow: 0 8px 24px rgba(168, 85, 247, 0.4);
    }

    .search-btn i {
        font-size: 1.1rem;
        transition: transform 0.3s ease;
    }

    .search-btn:hover i {
        transform: translateX(2px);
    }

    /* Mobile Responsive Fixes - Extra Compact */
    @media (max-width: 768px) {
        .hero-search-wrapper {
            max-width: 90% !important;
            /* Shorter width on mobile */
            margin: 0 auto !important;
            /* Center the search box */
            padding: 0 12px;
            /* Safety padding from screen edges */
        }

        .hero-search {
            padding: 2px 2px 2px 12px !important;
            /* Much smaller padding */
            gap: 6px !important;
            flex-wrap: nowrap !important;
            /* Prevent wrapping */
            flex-direction: row !important;
            /* Force row */
            min-height: 36px !important;
            /* Smaller height */
            box-shadow: none !important;
            /* Lighter shadow */
        }

        .search-icon {
            font-size: 0.95rem !important;
            /* Smaller icon */
        }

        .search-placeholder {
            font-size: 0.75rem !important;
            /* Smaller text */
            flex: 1;
            min-width: 0;
            /* Allow flex item to shrink below content size */
        }

        .search-placeholder span:first-child {
            /* Keep "Tìm kiếm:" text but smaller */
            white-space: nowrap;
            font-size: 0.75rem;
        }

        .search-keywords-rotate {
            display: inline-block;
            max-width: 100%;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
            vertical-align: bottom;
            font-size: 0.75rem;
        }

        /* Extra small button */
        .search-btn {
            width: 32px !important;
            /* Even smaller */
            height: 32px !important;
            min-width: 32px !important;
            max-width: 32px !important;
            border-radius: 50% !important;
            flex: 0 0 32px !important;
            /* Prevent growing or shrinking */
            margin: 0 !important;
            background: linear-gradient(135deg, #a855f7 0%, #ec4899 100%) !important;
            display: flex !important;
            align-items: center !important;
            justify-content: center !important;
            box-shadow: none !important;
        }

        .search-btn i {
            font-size: 0.7rem !important;
            /* Smaller icon */
        }
    }

    /* ============ BUTTONS ============ */
    .btn {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: 10px;
        padding: 14px 28px;
        border-radius: 14px;
        text-decoration: none;
        font-weight: 600;
        font-size: 0.95rem;
        border: none;
        cursor: pointer;
        transition: var(--transition);
        position: relative;
        overflow: hidden;
    }

    .btn i {
        font-size: 1rem;
    }

    .btn-lg {
        padding: 16px 32px;
        font-size: 1rem;
    }

    .btn-primary {
        background: linear-gradient(135deg, #0606d4ff 0%, #0890b2ff 100%);
        color: white;
        box-shadow: 0 8px 24px rgba(6, 6, 212, 0.3);
        transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    }

    .btn-primary:hover {
        transform: translateY(-4px) scale(1.02);
        box-shadow: 0 16px 40px rgba(6, 6, 212, 0.4),
            0 8px 20px rgba(8, 144, 178, 0.3);
    }

    .btn-ghost {
        background: rgba(148, 163, 184, 0.1);
        color: var(--text-main);
        border: 1px solid rgba(148, 163, 184, 0.25);
        transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    }

    .btn-ghost:hover {
        background: rgba(148, 163, 184, 0.2);
        border-color: rgba(148, 163, 184, 0.5);
        transform: translateY(-3px);
        box-shadow: 0 8px 20px rgba(148, 163, 184, 0.2);
    }

    .btn-white {
        background: white;
        color: var(--primary);
        box-shadow: 0 4px 12px rgba(255, 255, 255, 0.2);
        transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    }

    .btn-white:hover {
        transform: translateY(-4px) scale(1.02);
        box-shadow: 0 12px 32px rgba(255, 255, 255, 0.3);
    }

    .text-primary {
        color: var(--primary) !important;
    }

    /* ============ SECTION HEADER ============ */
    .section-header {
        margin-bottom: 50px;
        display: flex;
        justify-content: space-between;
        align-items: flex-end;
        flex-wrap: wrap;
        gap: 20px;
    }

    .section-header.text-center {
        display: block;
        text-align: center;
    }

    .section-label {
        display: inline-block;
        color: var(--accent);
        font-weight: 700;
        letter-spacing: 2px;
        font-size: 1.75rem;
        text-transform: uppercase;
        margin-bottom: 12px;
    }

    .section-title {
        font-size: clamp(1.8rem, 3vw, 2.5rem);
        font-weight: 700;
        color: var(--text-main);
        margin-bottom: 12px;
        line-height: 1.2;
    }

    .section-sub {
        color: var(--text-muted);
        font-size: 1rem;
        line-height: 1.6;
        max-width: 550px;
    }

    .section-link {
        display: inline-flex;
        align-items: center;
        gap: 8px;
        color: var(--primary-light);
        text-decoration: none;
        font-weight: 500;
        font-size: 0.9rem;
        transition: var(--transition);
    }

    .section-link:hover {
        color: var(--secondary);
        gap: 14px;
        transform: translateX(2px);
    }

    /* ============ FEATURES GRID ============ */
    .features-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
        gap: 24px;
    }

    .feature-card {
        background: rgba(30, 41, 59, 0.4);
        border: 2px solid rgba(139, 92, 246, 0.3);
        border-radius: var(--radius-lg);
        padding: 32px;
        backdrop-filter: blur(20px);
        position: relative;
        overflow: hidden;

    }

    .feature-card::before {
        content: '';
        position: absolute;
        inset: 0;
        background: radial-gradient(600px circle at var(--mouse-x, 50%) var(--mouse-y, 50%),
                rgba(139, 92, 246, 0.15),
                transparent 40%);
        opacity: 0;
        transition: opacity 0.4s ease;
        pointer-events: none;
    }

    .feature-icon-box {
        width: 60px;
        height: 60px;
        border-radius: 16px;
        background: rgba(139, 92, 246, 0.12);
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.6rem;
        color: var(--primary);
        margin-bottom: 20px;
        transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
        box-shadow: 0 4px 12px rgba(139, 92, 246, 0.2);
    }


    .feature-title {
        font-size: 1.15rem;
        font-weight: 700;
        color: var(--text-main);
        margin-bottom: 12px;
    }

    .feature-text {
        color: var(--text-muted);
        line-height: 1.65;
        margin-bottom: 18px;
        font-size: 0.95rem;
    }

    .feature-meta .badge {
        display: inline-block;
        padding: 6px 12px;
        border-radius: 8px;
        font-size: 0.7rem;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 0.5px;
    }

    .badge-success {
        background: rgba(16, 185, 129, 0.15);
        color: var(--success);
    }

    .badge-primary {
        background: rgba(139, 92, 246, 0.15);
        color: var(--primary-light);
    }

    .badge-warning {
        background: rgba(245, 158, 11, 0.15);
        color: var(--accent);
    }

    /* ============ PROMOTION BANNER ============ */
    .promotion-banner {
        background: radial-gradient(ellipse at center, rgba(139, 92, 246, 0.15) 0%, var(--bg-main) 70%);
        border: 1px solid rgba(139, 92, 246, 0.2);
        border-radius: var(--radius-xl);
        padding: 50px;
        display: grid;
        grid-template-columns: 1.2fr 1fr;
        gap: 40px;
        align-items: center;
        position: relative;
        overflow: hidden;
    }

    .promotion-banner::before {
        content: '';
        position: absolute;
        width: 400px;
        height: 400px;
        background: rgba(255, 255, 255, 0.05);
        border-radius: 50%;
        top: -200px;
        right: -100px;
    }

    .promo-content {
        position: relative;
        z-index: 1;
    }

    .promo-content h2 {
        font-size: 2rem;
        font-weight: 800;
        color: var(--text-main);
        margin-bottom: 12px;
    }

    .promo-content p {
        color: var(--text-muted);
        font-size: 1.05rem;
        margin-bottom: 24px;
        width: 100%;
    }

    .promo-visual {
        position: relative;
        height: 180px;
    }

    .glass-card {
        position: absolute;
        background: rgba(255, 255, 255, 0.1);
        backdrop-filter: blur(10px);
        padding: 18px 24px;
        border-radius: 16px;
        border: 1px solid rgba(255, 255, 255, 0.2);
        color: white;
        display: flex;
        flex-direction: column;
        gap: 4px;
        transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        box-shadow: 0 8px 24px rgba(0, 0, 0, 0.3);
    }

    .glass-card:hover {
        transform: translateY(-4px) scale(1.05);
        box-shadow: 0 16px 40px rgba(0, 0, 0, 0.4);
        border-color: rgba(255, 255, 255, 0.4);
    }

    .glass-card span {
        font-size: 0.85rem;
        opacity: 0.9;
    }

    .glass-card strong {
        font-size: 1.3rem;
        font-weight: 700;
    }

    .glass-card:first-child {
        top: 0;
        right: 20%;
        transform: rotate(-5deg);
        z-index: 2;
    }

    .glass-card.offset {
        bottom: 0;
        right: 5%;
        transform: rotate(5deg);
        background: rgba(255, 255, 255, 0.05);
    }

    /* ============ STATS SECTION ============ */
    .dashboard-section {
        background: rgba(30, 41, 59, 0.4);
        border: 2px solid rgba(139, 92, 246, 0.4);
        border-radius: var(--radius-xl);
        padding: 50px;
        display: grid;
        grid-template-columns: 1fr 1.5fr;
        gap: 50px;
        align-items: center;
        position: relative;
        overflow: hidden;
    }


    .dashboard-section::before {
        content: '';
        position: absolute;
        width: 350px;
        height: 350px;
        background: var(--primary);
        filter: blur(120px);
        opacity: 0.12;
        top: -100px;
        left: 50%;
        transform: translateX(-50%);
        pointer-events: none;
    }

    .dashboard-content {
        text-align: left;
    }

    .dashboard-icon-lg {
        font-size: 3.5rem;
        color: var(--primary);
        margin-bottom: 20px;
        opacity: 0.6;
    }

    .h2-title {
        font-size: 1.8rem;
        font-weight: 700;
        color: var(--text-main);
        margin-bottom: 12px;
    }

    .text-muted {
        color: var(--text-muted);
        line-height: 1.6;
    }

    .stats-grid {
        display: grid;
        grid-template-columns: repeat(3, 1fr);
        gap: 20px;
    }

    .stat-card {
        background: rgba(30, 41, 59, 0.4);
        text-align: center;
        padding: 28px 20px;
        border-radius: 20px;
        border: 2px solid rgba(139, 92, 246, 0.3);
        transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
        box-shadow: 0 4px 16px rgba(139, 92, 246, 0.15);
        position: relative;
        overflow: hidden;
    }

    .stat-card::before {
        content: '';
        position: absolute;
        inset: 0;
        background: linear-gradient(135deg, rgba(139, 92, 246, 0.1) 0%, rgba(236, 72, 153, 0.1) 100%);
        opacity: 0;
        transition: opacity 0.4s ease;
    }

    .stat-card:hover::before {
        opacity: 1;
    }

    .stat-card:hover {
        transform: translateY(-10px) scale(1.03);
        border-color: rgba(139, 92, 246, 0.7);
        box-shadow: 0 20px 50px rgba(139, 92, 246, 0.3),
            0 10px 25px rgba(0, 0, 0, 0.4);
        background: rgba(30, 41, 59, 0.6);
    }

    .stat-card:hover .stat-value {
        transform: scale(1.1);
        color: var(--primary-light);
    }

    .stat-value {
        font-size: 2.2rem;
        font-weight: 800;
        color: var(--text-main);
        margin-bottom: 8px;
        transition: all 0.3s ease;
    }

    .stat-label {
        font-size: 0.7rem;
        font-weight: 600;
        color: var(--text-muted);
        letter-spacing: 1px;
        text-transform: uppercase;
    }

    /* ============ PROCESS STEPS ============ */
    .process-grid {
        display: grid;
        grid-template-columns: repeat(3, 1fr);
        gap: 28px;
        margin-top: 40px;
    }

    .process-step {
        background: rgba(30, 41, 59, 0.4);
        text-align: center;
        padding: 36px 24px;
        border: 2px solid rgba(139, 92, 246, 0.4);
        border-radius: var(--radius-lg);
        transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
        position: relative;
        overflow: hidden;
        box-shadow: 0 8px 32px rgba(139, 92, 246, 0.15);
    }

    .process-step::before {
        content: '';
        position: absolute;
        inset: 0;
        border-radius: var(--radius-lg);
        background: radial-gradient(500px circle at var(--mouse-x, 50%) var(--mouse-y, 50%),
                rgba(139, 92, 246, 0.15),
                transparent 40%);
        opacity: 0;
        transition: opacity 0.3s ease;
        pointer-events: none;
    }

    .process-step:hover::before {
        opacity: 1;
    }

    .process-step:hover {
        border-color: rgba(139, 92, 246, 0.8);
        box-shadow: 0 20px 60px rgba(139, 92, 246, 0.35),
            0 10px 30px rgba(0, 0, 0, 0.4);
        transform: translateY(-8px) scale(1.02);
        background: rgba(30, 41, 59, 0.6);
    }

    .process-step:hover .process-icon {
        background: linear-gradient(135deg, rgba(30, 41, 59, 0.9) 0%, rgba(30, 41, 59, 0.95) 100%);
        border-color: rgba(139, 92, 246, 0.8);
    }

    .process-icon {
        width: 72px;
        height: 72px;
        border-radius: 20px;
        background: linear-gradient(135deg, var(--bg-main) 0%, rgba(30, 41, 59, 0.8) 100%);
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.8rem;
        color: var(--primary);
        margin: 0 auto 24px;
        border: 1px solid var(--border-color);
    }

    .process-step h3 {
        font-size: 1.15rem;
        font-weight: 700;
        color: var(--text-main);
        margin-bottom: 12px;
    }

    .process-step p {
        color: var(--text-muted);
        font-size: 0.9rem;
        line-height: 1.6;
    }

    /* ============ FAQ ============ */
    .faq-grid {
        display: grid;
        grid-template-columns: 1fr;
        gap: 16px;
        max-width: 800px;
        margin: 0 auto;
    }

    .faq-item {
        background: rgba(30, 41, 59, 0.4);
        border: 1px solid var(--border-color);
        border-radius: var(--radius-md);
        overflow: hidden;
        transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        box-shadow: 0 2px 8px rgba(0, 0, 0, 0.2);
    }

    .faq-item:hover {
        border-color: rgba(139, 92, 246, 0.4);
        box-shadow: 0 8px 24px rgba(139, 92, 246, 0.2);
        transform: translateY(-2px);
        background: rgba(30, 41, 59, 0.6);
    }

    .faq-item.active {
        border-color: rgba(139, 92, 246, 0.6);
        box-shadow: 0 8px 24px rgba(139, 92, 246, 0.25);
    }

    .faq-head {
        padding: 20px 24px;
        display: flex;
        justify-content: space-between;
        align-items: center;
        cursor: pointer;
        font-weight: 600;
        font-size: 0.95rem;
        color: var(--text-main);
        transition: background 0.2s;
    }

    .faq-head:hover {
        background: rgba(255, 255, 255, 0.08);
    }

    .faq-head i {
        color: var(--text-muted);
        font-size: 0.9rem;
        transition: transform 0.3s, color 0.3s;
    }

    .faq-item.active .faq-head i {
        transform: rotate(45deg);
        color: var(--primary);
    }

    .faq-body {
        max-height: 0;
        overflow: hidden;
        transition: max-height 0.3s ease, padding 0.3s ease;
    }

    .faq-item.active .faq-body {
        max-height: 200px;
        padding: 0 24px 20px;
    }

    .faq-body p {
        color: var(--text-muted);
        line-height: 1.65;
        font-size: 0.9rem;
    }

    /* ============ CTA SECTION ============ */
    .cta-box {
        background: rgba(30, 41, 59, 0.4);
        border: 2px solid rgba(139, 92, 246, 0.4);
        border-radius: var(--radius-xl);
        padding: 60px 40px;
        text-align: center;
        position: relative;
        overflow: hidden;
    }

    .cta-box::before {
        content: '';
        position: absolute;
        width: 300px;
        height: 300px;
        background: var(--primary);
        filter: blur(120px);
        opacity: 0.15;
        top: -100px;
        left: 50%;
        transform: translateX(-50%);
    }

    .cta-content {
        position: relative;
        z-index: 1;
    }

    .cta-content h2 {
        font-size: 2rem;
        font-weight: 700;
        color: var(--text-main);
        margin-bottom: 14px;
    }

    .cta-content p {
        color: var(--text-muted);
        font-size: 1.05rem;
        max-width: 500px;
        margin: 0 auto 28px;
        line-height: 1.6;
    }

    .cta-btns {
        display: flex;
        justify-content: center;
        gap: 16px;
        flex-wrap: wrap;
    }

    /* ============ RESPONSIVE ============ */
    @media (max-width: 1024px) {
        .hero-inner {
            grid-template-columns: 1fr;
            text-align: center;
            gap: 40px;
        }

        .hero-left {
            order: 1;
        }

        .hero-content {
            order: 2;
            align-items: center;
        }

        .hero-subtitle {
            max-width: 100%;
        }

        .hero-search-wrapper {
            margin: 0 auto;
        }

        .dashboard-section {
            grid-template-columns: 1fr;
            text-align: center;
            gap: 40px;
        }

        .dashboard-icon-lg {
            display: none;
        }

        .promotion-banner {
            grid-template-columns: 1fr;
            text-align: center;
        }

        .promo-visual {
            display: none;
        }

        .promo-content p {
            max-width: 100%;
        }
    }

    /* Hide hero logo image from 1030px down */
    /* ============ RESPONSIVE HERO SECTION (< 1040px) ============ */
    @media (max-width: 1040px) {

        /* Hide Logo Image & Side Content */
        .hero-logo-image,
        .hero-left {
            display: none !important;
        }

        /* Center Main Container */
        .hero-inner {
            grid-template-columns: 1fr !important;
            gap: 20px !important;
            max-width: 650px;
            margin: 0 auto;
        }

        /* Center Content Alignment */
        .hero-content {
            align-items: center;
            text-align: center;
            width: 100%;
            padding: 0 15px;
        }

        .hero-subtitle {
            margin: 0 auto 24px;
            max-width: 90%;
        }

        .typing-container {
            justify-content: center;
        }

        /* Badges: Grid 2 Columns */
        .hero-badges-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 12px;
            width: 100%;
            margin-bottom: 24px;
            justify-content: center;
        }

        .hero-badge-soft {
            min-width: 0;
            width: 100%;
            padding: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            text-align: left;
        }

        /* Action Buttons: Stacked */
        .action-buttons {
            display: flex;
            flex-direction: column;
            gap: 12px;
            width: 100%;
            max-width: 400px;
            margin: 0 auto 20px;
        }

        .action-buttons .btn {
            width: 100% !important;
            justify-content: center;
        }

        .btn-lg {
            padding: 14px 20px;
            width: 100%;
        }

        /* Search Box & Trust */
        .hero-search-wrapper {
            margin: 0 auto;
            max-width: 100%;
        }

        .trust-indicator {
            justify-content: center;
        }
    }

    @media (max-width: 768px) {
        .container {
            padding: 0 1rem;
        }

        .section {
            padding: 40px 0;
        }

        .hero {
            padding: 10px 0 30px;
            min-height: auto;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        /* Hide hero image on mobile */
        .hero-left {
            display: none !important;
        }

        .hero-inner {
            grid-template-columns: 1fr !important;
            gap: 0 !important;
            padding: 0;
            max-width: 500px;
            margin: 0 auto;
        }

        .hero-content {
            order: 1;
            align-items: center;
            text-align: center;
            width: 100%;
            padding: 0;
            display: flex;
            flex-direction: column;
        }

        .hero-badge {
            margin-bottom: 16px;
            font-size: 0.75rem;
            padding: 6px 14px;
        }

        .hero-title {
            font-size: 2.5rem !important;
            margin-bottom: 16px;
            line-height: 1.2;
            word-wrap: break-word;
            overflow-wrap: break-word;
            max-width: 100%;
        }

        .hero-subtitle {
            font-size: 1rem;
            line-height: 1.5;
            margin-bottom: 16px;
            max-width: 90%;
        }

        .typing-container {
            font-size: 1.1rem;
            margin-bottom: 16px;
        }

        /* 2x2 Grid for badges */
        .hero-badges-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            width: 100%;
            margin-bottom: 16px;
            justify-content: center;
        }

        .hero-badge-soft {
            min-width: auto;
            width: 100%;
            max-width: none;
            justify-content: center;
            margin: 0 auto;
            padding: 10px 8px;
            font-size: 0.85rem;
            text-align: center;
        }

        .hero-badge-soft i {
            font-size: 1.4rem;
        }

        .hero-badge-soft strong {
            font-size: 0.85rem;
        }

        .hero-badge-soft span {
            font-size: 0.75rem;
        }

        /* Action buttons mobile - inherit from 1040px but ensure full width */
        .action-buttons {
            width: 100%;
            max-width: 400px;
            margin: 0 auto 16px;
        }

        .btn-lg {
            width: 100%;
            padding: 14px;
            font-size: 15px;
        }

        /* Trust indicator - full width centered */
        .trust-indicator {
            width: 100%;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            margin-bottom: 16px;
            font-size: 0.8rem;
            text-align: center;
        }

        /* Hero search mobile optimization */
        .hero-search-wrapper {
            width: 100%;
            max-width: 400px;
            margin: 10px auto 0;
        }

        .hero-search {
            padding: 10px 14px;
            width: 100%;
            justify-content: center;
        }

        .features-grid,
        .stats-grid,
        .process-grid,
        .faq-grid {
            grid-template-columns: 1fr;
            gap: 20px;
        }

        .dashboard-section {
            padding: 25px 15px;
        }

        .promotion-banner {
            padding: 25px 15px;
        }

        .section-header {
            flex-direction: column;
            align-items: center;
            text-align: center;
            margin-bottom: 30px;
        }

        .section-link {
            width: 100%;
            justify-content: center;
            margin-top: 10px;
        }

        .feature-card {
            padding: 20px;
        }
    }

    @media (max-width: 480px) {
        .container {
            padding: 0 1rem;
        }

        .hero {
            padding: 8px 0 25px;
        }

        .hero-inner {
            max-width: 380px;
        }

        .hero-badge {
            font-size: 0.7rem;
            padding: 5px 12px;
            margin-bottom: 12px;
        }

        .hero-title {
            font-size: 2.5rem !important;
            /* Larger for better mobile visibility */
            line-height: 1.25;
            margin-bottom: 8px;
            word-wrap: break-word;
            overflow-wrap: break-word;
            max-width: 100%;
        }

        .hero-subtitle {
            font-size: 0.95rem;
            margin-bottom: 12px;
            max-width: 95%;
        }

        .typing-container {
            font-size: 1rem;
            margin-bottom: 12px;
        }

        /* 2x2 Grid for badges - smaller */
        .hero-badges-row {
            grid-template-columns: 1fr 1fr;
            gap: 6px;
            margin-bottom: 12px;
        }

        .hero-badge-soft {
            padding: 12px 14px;
            font-size: 0.8rem;
        }

        .hero-badge-soft i {
            font-size: 1.2rem;
        }

        .hero-badge-soft strong {
            font-size: 0.8rem;
        }

        .hero-badge-soft span {
            font-size: 0.7rem;
        }


        .btn-lg {
            padding: 10px 12px;
            font-size: 14px;
        }

        .trust-indicator {
            margin-bottom: 12px;
        }

        .hero-search-wrapper {
            max-width: 350px;
        }

        .hero-search {
            flex-direction: column;
            gap: 8px;
            padding: 10px 12px;
        }

        .search-placeholder {
            width: 100%;
            justify-content: center;
            font-size: 0.8rem;
        }

        .search-btn {
            width: 100%;
            padding: 10px;
            border-radius: 8px;
            font-size: 13px;
        }

        .stat-value {
            font-size: 1.6rem;
        }

        .btn {
            font-size: 13px;
            padding: 10px 16px;
        }

        .section-title {
            font-size: 1.4rem;
        }

        .feature-card {
            padding: 16px;
        }

        .section {
            padding: 30px 0;
        }
    }
</style>