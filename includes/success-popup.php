<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">

<style>
    /* --- Variables --- */
    /* --- Fixed Light Theme Variables for Popup --- */
    #successPopup {
        --p-primary: #059669;
        --p-primary-light: #ecfdf5;
        --p-primary-softer: #d1fae5;
        --p-text-main: #111827;
        --p-text-sub: #4b5563;
        --p-bg-card: #ffffff;
    }

    #successPopup {
        position: fixed;
        inset: 0;
        z-index: 9999999;
        display: none;
        align-items: center;
        justify-content: center;
        font-family: 'Inter', -apple-system, BlinkMacSystemFont, sans-serif;
    }

    #successPopup.show {
        display: flex;
    }

    #successPopup .popup-overlay {
        position: absolute;
        inset: 0;
        background: radial-gradient(circle at 50% 50%, rgba(5, 150, 105, 0.15), rgba(0, 0, 0, 0.85));
        backdrop-filter: blur(8px);
        animation: fadeIn 0.4s ease;
    }

    #confettiCanvas {
        position: absolute;
        inset: 0;
        pointer-events: none;
        z-index: 2;
    }

    #successPopup .popup-container {
        position: relative;
        z-index: 3;
        width: 100%;
        max-width: 100%;
        height: 100vh;
        background: linear-gradient(to bottom, #ffffff, #fafafa);
        border-radius: 0;
        padding: 32px 24px;
        text-align: center;
        box-shadow: 0 30px 60px -12px rgba(0, 0, 0, 0.3),
            0 0 0 1px rgba(255, 255, 255, 0.5) inset;
        animation: popInBounce 0.6s cubic-bezier(0.34, 1.56, 0.64, 1);
        overflow-y: auto;
        display: flex;
        flex-direction: column;
        justify-content: center;
    }

    @media (min-width: 768px) {
        #successPopup .popup-container {
            width: 92%;
            max-width: 500px;
            height: auto;
            border-radius: 28px;
            padding: 48px 36px;
            overflow: hidden;
        }
    }

    #successPopup .popup-container::before {
        content: '';
        position: absolute;
        top: -50%;
        left: -50%;
        width: 200%;
        height: 200%;
        background: radial-gradient(circle, rgba(5, 150, 105, 0.1) 0%, transparent 70%);
        animation: rotate 20s linear infinite;
        pointer-events: none;
    }

    /* Icon chính - Check Mark tự vẽ */
    .success-anim-container {
        position: relative;
        width: 120px;
        height: 120px;
        margin: 0 auto 24px;
        display: grid;
        place-items: center;
    }

    .success-anim-circle {
        position: absolute;
        width: 100%;
        height: 100%;
        background: radial-gradient(circle, var(--p-primary-light), transparent);
        border-radius: 50%;
        animation: rippleWave 2.5s ease-out infinite;
    }

    .success-anim-circle:nth-child(2) {
        animation-delay: 0.5s;
    }

    .success-icon-box {
        position: relative;
        width: 120px;
        height: 120px;
        margin: 0 auto;
        animation: scaleInBounce 0.5s cubic-bezier(0.68, -0.55, 0.265, 1.55) 0.3s both;
    }

    .success-icon-box img {
        width: 100%;
        height: 100%;
        object-fit: contain;
        drop-shadow: 0 10px 20px rgba(16, 185, 129, 0.2);
    }

    #successPopup h2 {
        color: var(--p-text-main);
        font-size: 1.5rem;
        font-weight: 800;
        margin: 0 0 12px 0;
        letter-spacing: -0.03em;
        background: linear-gradient(135deg, #111827, #374151);
        -webkit-background-clip: text;
        -webkit-text-fill-color: transparent;
        background-clip: text;
        animation: slideDown 0.5s ease-out 0.4s both;
    }

    @media (min-width: 768px) {
        #successPopup h2 {
            font-size: 2rem;
        }
    }

    #successPopup .subtitle {
        color: var(--p-text-sub);
        font-size: 0.9rem;
        margin-bottom: 24px;
        line-height: 1.6;
        font-weight: 500;
        animation: slideDown 0.5s ease-out 0.5s both;
    }

    @media (min-width: 768px) {
        #successPopup .subtitle {
            font-size: 1rem;
            margin-bottom: 28px;
        }
    }

    /* --- Receipt Box --- */
    .receipt-box {
        background: linear-gradient(135deg, var(--p-primary-light) 0%, #ffffff 100%);
        border: 2px solid var(--p-primary-softer);
        border-radius: 16px;
        padding: 20px 16px;
        margin-bottom: 24px;
        text-align: left;
        box-shadow: 0 4px 12px rgba(5, 150, 105, 0.08);
        animation: slideUp 0.5s ease-out 0.6s both;
        position: relative;
        overflow: hidden;
    }

    @media (min-width: 768px) {
        .receipt-box {
            border-radius: 20px;
            padding: 28px 24px;
        }
    }

    .receipt-box::before {
        content: '';
        position: absolute;
        top: 0;
        left: 0;
        right: 0;
        height: 3px;
        background: linear-gradient(90deg, #10b981, #059669, #10b981);
        background-size: 200% 100%;
        animation: shimmer 3s linear infinite;
    }

    .receipt-row {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 16px;
        font-size: 0.95rem;
        animation: fadeInRow 0.4s ease-out both;
    }

    .receipt-row:nth-child(1) {
        animation-delay: 0.7s;
    }

    .receipt-row:nth-child(2) {
        animation-delay: 0.75s;
    }

    .receipt-row:nth-child(3) {
        animation-delay: 0.8s;
    }

    .receipt-row:nth-child(4) {
        animation-delay: 0.85s;
    }

    .receipt-row:nth-child(5) {
        animation-delay: 0.9s;
    }

    .receipt-row.total-row {
        margin-bottom: 0;
        padding-top: 16px;
        border-top: 2px dashed #a7f3d0;
        margin-top: 16px;
    }

    .row-label {
        color: var(--p-text-sub);
        font-weight: 600;
        display: flex;
        align-items: center;
        gap: 10px;
    }

    .row-label i {
        color: var(--p-primary);
        font-size: 16px;
        width: 22px;
        text-align: center;
        animation: iconPulse 2s ease-in-out infinite;
    }

    .row-value {
        color: var(--p-text-main);
        font-weight: 700;
    }

    .row-value.highlight {
        color: var(--p-primary);
        font-size: 1.35rem;
        font-weight: 800;
        text-shadow: 0 2px 4px rgba(5, 150, 105, 0.2);
    }

    /* --- Buttons --- */
    .action-group {
        display: flex;
        flex-direction: column;
        gap: 12px;
        animation: slideUp 0.5s ease-out 0.95s both;
    }

    #successPopup .btn {
        width: 100%;
        padding: 16px;
        border-radius: 99px;
        font-size: 1rem;
        font-weight: 700;
        cursor: pointer;
        border: none;
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 8px;
        transition: all 0.2s ease;
    }

    #successPopup .btn-primary {
        background: linear-gradient(135deg, #18181b 0%, #000000 100%) !important;
        color: white !important;
        box-shadow: 0 4px 6px rgba(0, 0, 0, 0.2);
    }

    #successPopup .btn-primary:hover {
        opacity: 0.9;
        transform: translateY(-1px);
        box-shadow: 0 6px 12px rgba(0, 0, 0, 0.15);
    }

    #successPopup .btn-primary:active {
        transform: translateY(0);
        opacity: 1;
    }

    #successPopup .btn-outline {
        background: white;
        color: black;
        border: 2px solid #000000ff;
    }

    #successPopup .btn-outline:hover {
        transform: translateY(-1px);
        box-shadow: 0 8px 16px rgba(0, 0, 0, 0.1);
    }

    #successPopup .btn-outline:active {
        transform: translateY(0);
        opacity: 1;
    }

    /* Animations */
    @keyframes fadeIn {
        from {
            opacity: 0;
        }

        to {
            opacity: 1;
        }
    }

    @keyframes popInBounce {
        0% {
            opacity: 0;
            transform: scale(0.8) translateY(40px);
        }

        60% {
            transform: scale(1.05) translateY(-5px);
        }

        100% {
            opacity: 1;
            transform: scale(1) translateY(0);
        }
    }

    @keyframes rippleWave {
        0% {
            transform: scale(0.8);
            opacity: 0.6;
        }

        50% {
            opacity: 0.3;
        }

        100% {
            transform: scale(1.8);
            opacity: 0;
        }
    }

    @keyframes scaleInBounce {
        0% {
            transform: scale(0) rotate(-180deg);
            opacity: 0;
        }

        50% {
            transform: scale(1.1) rotate(10deg);
        }

        100% {
            transform: scale(1) rotate(0);
            opacity: 1;
        }
    }

    @keyframes stroke {
        100% {
            stroke-dashoffset: 0;
        }
    }

    @keyframes slideDown {
        from {
            opacity: 0;
            transform: translateY(-20px);
        }

        to {
            opacity: 1;
            transform: translateY(0);
        }
    }

    @keyframes slideUp {
        from {
            opacity: 0;
            transform: translateY(20px);
        }

        to {
            opacity: 1;
            transform: translateY(0);
        }
    }

    @keyframes fadeInRow {
        from {
            opacity: 0;
            transform: translateX(-10px);
        }

        to {
            opacity: 1;
            transform: translateX(0);
        }
    }

    @keyframes shimmer {
        0% {
            background-position: -200% 0;
        }

        100% {
            background-position: 200% 0;
        }
    }

    @keyframes rotate {
        from {
            transform: rotate(0deg);
        }

        to {
            transform: rotate(360deg);
        }
    }

    @keyframes iconPulse {

        0%,
        100% {
            transform: scale(1);
        }

        50% {
            transform: scale(1.1);
        }
    }
</style>

<script>
    /* --- Realistic Fireworks --- */
    class RealisticFireworks {
        constructor(canvas) {
            this.canvas = canvas;
            this.ctx = canvas.getContext('2d');
            this.fireworks = [];
            this.particles = [];
            this.timer = 0;
            this.resize();
            window.addEventListener('resize', () => this.resize());
        }

        resize() {
            this.canvas.width = window.innerWidth;
            this.canvas.height = window.innerHeight;
        }

        random(min, max) {
            return Math.random() * (max - min) + min;
        }

        // Calculate distance between two points
        distance(p1x, p1y, p2x, p2y) {
            const xDist = p1x - p2x;
            const yDist = p1y - p2y;
            return Math.sqrt(Math.pow(xDist, 2) + Math.pow(yDist, 2));
        }

        createFirework(sx, sy, tx, ty) {
            // Rocket physics
            this.fireworks.push({
                x: sx,
                y: sy,
                sx: sx,
                sy: sy,
                tx: tx,
                ty: ty,
                distanceToTarget: this.distance(sx, sy, tx, ty),
                distanceTraveled: 0,
                coordinates: [],
                coordinateCount: 3,
                angle: Math.atan2(ty - sy, tx - sx),
                speed: 2,
                acceleration: 1.05,
                brightness: this.random(50, 70),
                targetRadius: 1,
                hue: this.random(0, 360)
            });
        }

        createParticles(x, y, hue) {
            const particleCount = 60; // More particles for realism
            for (let i = 0; i < particleCount; i++) {
                const angle = this.random(0, Math.PI * 2);
                const speed = this.random(1, 10);
                const friction = 0.95;
                const gravity = 1;

                this.particles.push({
                    x: x,
                    y: y,
                    coordinates: [],
                    coordinateCount: 5,
                    angle: angle,
                    speed: speed,
                    friction: friction,
                    gravity: gravity,
                    hue: this.random(hue - 20, hue + 20),
                    brightness: this.random(50, 80),
                    alpha: 1,
                    decay: this.random(0.015, 0.03)
                });
            }
        }

        update() {
            // Use 'destination-out' to create trails
            // However, since we have a transparent canvas on top of a popup, 
            // excessive trails might look messy or clear the underlying content if not careful.
            // But we are clearing the CANVAS only.
            this.ctx.globalCompositeOperation = 'destination-out';
            this.ctx.fillStyle = 'rgba(0, 0, 0, 0.5)';
            this.ctx.fillRect(0, 0, this.canvas.width, this.canvas.height);
            this.ctx.globalCompositeOperation = 'source-over';

            // Loop fireworks (rockets)
            for (let i = this.fireworks.length - 1; i >= 0; i--) {
                const fw = this.fireworks[i];

                // Track coordinates for trail
                if (fw.coordinates.length >= fw.coordinateCount) {
                    fw.coordinates.shift();
                }
                fw.coordinates.push([fw.x, fw.y]);

                // Physics
                fw.speed *= fw.acceleration;
                const vx = Math.cos(fw.angle) * fw.speed;
                const vy = Math.sin(fw.angle) * fw.speed;

                fw.distanceTraveled = this.distance(fw.sx, fw.sy, fw.x + vx, fw.y + vy);

                if (fw.distanceTraveled >= fw.distanceToTarget) {
                    this.createParticles(fw.tx, fw.ty, fw.hue);
                    this.fireworks.splice(i, 1);
                } else {
                    fw.x += vx;
                    fw.y += vy;

                    // Draw rocket line
                    this.ctx.beginPath();
                    this.ctx.moveTo(fw.coordinates[0][0], fw.coordinates[0][1]);
                    this.ctx.lineTo(fw.x, fw.y);
                    this.ctx.strokeStyle = `hsl(${fw.hue}, 100%, ${fw.brightness}%)`;
                    this.ctx.stroke();
                }
            }

            // Loop explosion particles
            for (let i = this.particles.length - 1; i >= 0; i--) {
                const p = this.particles[i];

                if (p.coordinates.length >= p.coordinateCount) {
                    p.coordinates.shift();
                }
                p.coordinates.push([p.x, p.y]);

                // Physics
                p.speed *= p.friction;
                p.x += Math.cos(p.angle) * p.speed;
                p.y += Math.sin(p.angle) * p.speed + p.gravity;
                p.alpha -= p.decay;

                if (p.alpha <= p.decay) {
                    this.particles.splice(i, 1);
                    continue;
                }

                this.ctx.beginPath();
                this.ctx.moveTo(p.coordinates[0][0], p.coordinates[0][1]);
                this.ctx.lineTo(p.x, p.y);
                this.ctx.strokeStyle = `hsla(${p.hue}, 100%, ${p.brightness}%, ${p.alpha})`;
                this.ctx.stroke();
            }

            requestAnimationFrame(() => this.update());
        }

        loop() {
            requestAnimationFrame(() => this.update());

            // Randomly launch fireworks
            setInterval(() => {
                const startX = this.canvas.width / 2;
                const startY = this.canvas.height;
                const targetX = this.random(0, this.canvas.width);
                const targetY = this.random(0, this.canvas.height / 2);
                this.createFirework(startX, startY, targetX, targetY);
                // Also side launches for fun
                if (Math.random() > 0.5) {
                    this.createFirework(this.random(0, this.canvas.width), this.canvas.height, this.random(0, this.canvas.width), this.random(0, this.canvas.height / 2));
                }
            }, 800);

            // Initial barrage
            for (let i = 0; i < 5; i++) {
                setTimeout(() => {
                    this.createFirework(this.random(0, this.canvas.width), this.canvas.height, this.random(0, this.canvas.width), this.random(0, this.canvas.height / 2));
                }, i * 200);
            }
        }
    }

    function showSuccessPopup(data) {

        const curr = data.currency || (typeof currency !== 'undefined' ? currency : 'VND');
        const formatMoney = (amount) => {
            if (amount === undefined || amount === null || isNaN(amount)) return '0đ';
            if (curr === 'VND') return new Intl.NumberFormat('vi-VN').format(amount) + 'đ';
            return '$' + Number(amount).toFixed(2);
        };

        const amountStr = formatMoney(data.amount);
        const now = new Date();
        const timeStr = now.toLocaleTimeString('vi-VN', { hour: '2-digit', minute: '2-digit' }) + ' - ' + now.toLocaleDateString('vi-VN');

        let balance = data.new_balance;
        if (balance === undefined || balance === null) {
            // Fallback calculation if needed
            if (typeof userBalanceVND !== 'undefined' && data.amount) {
                balance = userBalanceVND - data.amount;
            }
        }
        const balanceStr = formatMoney(balance);

        const popupHTML = `
        <div id="successPopup">
            <div class="popup-overlay"></div>
            <canvas id="confettiCanvas"></canvas> 
            <div class="popup-container">
                
                <div class="success-anim-container">
                    <div class="success-anim-circle"></div>
                    <div class="success-anim-circle"></div>
                    <div class="success-icon-box">
                         <img src="<?= asset('images/payment-success.png') ?>" alt="Success">
                    </div>
                </div>

                <h2>Thanh Toán Thành Công!</h2>
                <p class="subtitle">Giao dịch của bạn đã được xử lý hoàn tất.</p>

                <div class="receipt-box">
                    <div class="receipt-row">
                        <span class="row-label"><i class="far fa-clock"></i> Thời gian</span>
                        <span class="row-value">${timeStr}</span>
                    </div>
                    <div class="receipt-row">
                        <span class="row-label"><i class="fas fa-hashtag"></i> Mã giao dịch</span>
                        <span class="row-value" style="font-family: monospace; font-size: 1rem;">${data.order_number}</span>
                    </div>
                     <div class="receipt-row">
                        <span class="row-label"><i class="fas fa-box-open"></i> Dịch vụ</span>
                        <span class="row-value">${data.service_name || 'Thanh toán đơn hàng'}</span>
                    </div>
                    <div class="receipt-row">
                        <span class="row-label"><i class="fas fa-wallet"></i> Số dư mới</span>
                        <span class="row-value">${balanceStr}</span>
                    </div>
                    <div class="receipt-row total-row">
                        <span class="row-label">Tổng thanh toán</span>
                        <span class="row-value highlight">${amountStr}</span>
                    </div>
                </div>

                <div class="action-group">
                    <button onclick="location.href='<?= url('user?tab=orders') ?>'" class="btn btn-primary">
                        Xem Chi Tiết Đơn Hàng
                    </button>
                    <button onclick="location.href='<?= url('giohang') ?>'" class="btn btn-outline">
                        Về Giỏ Hàng
                    </button>
                </div>
            </div>
        </div>
    `;

        const oldPopup = document.getElementById('successPopup');
        if (oldPopup) oldPopup.remove();
        document.body.insertAdjacentHTML('beforeend', popupHTML);

        setTimeout(() => {
            document.getElementById('successPopup').classList.add('show');
            // Start fireworks after popup animation
            setTimeout(() => {
                new RealisticFireworks(document.getElementById('confettiCanvas')).loop();
            }, 800);
        }, 10);
    }

    function closeSuccessPopup() {
        const popup = document.getElementById('successPopup');
        if (popup) {
            popup.style.opacity = '0';
            setTimeout(() => popup.remove(), 300);
        }
    }
</script>