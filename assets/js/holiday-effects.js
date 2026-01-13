const HolidayEffects = {
    init(mode) {
        console.log('[HolidayEffects] Init mode:', mode);
        if (mode === 'noel') {
            this.startSnow();
        } else if (mode === 'tet') {
            this.startPetals();
        } else if (mode === 'halloween') {
            this.startHalloween();
        }
    },

    startHalloween() {
    },

    startSnow() {
        // Canvas Snow Effect - Ultra High Performance
        const canvas = document.createElement('canvas');
        const ctx = canvas.getContext('2d');

        canvas.style.position = 'fixed';
        canvas.style.top = '0';
        canvas.style.left = '0';
        canvas.style.width = '100%';
        canvas.style.height = '100%';
        canvas.style.pointerEvents = 'none';
        canvas.style.zIndex = '9999';
        document.body.appendChild(canvas);

        const snowflakes = [];
        // Giảm số lượng xuống mức tối ưu nhưng vẫn đẹp
        const flakeCount = window.innerWidth < 768 ? 20 : 40;

        function resize() {
            canvas.width = window.innerWidth;
            canvas.height = window.innerHeight;
        }
        window.addEventListener('resize', resize);
        resize();

        // Khởi tạo tuyết
        for (let i = 0; i < flakeCount; i++) {
            snowflakes.push({
                x: Math.random() * canvas.width,
                y: Math.random() * canvas.height,
                r: Math.random() * 3 + 1, // Bán kính (kích thước)
                d: Math.random() * flakeCount, // Mật độ để rơi lệch nhau
                vx: (Math.random() - 0.5) * 0.5, // Gió ngang nhẹ
                vy: Math.random() * 1 + 0.5 // Tốc độ rơi
            });
        }

        function draw() {
            ctx.clearRect(0, 0, canvas.width, canvas.height);
            ctx.fillStyle = 'rgba(255, 255, 255, 0.8)';
            ctx.beginPath();

            for (let i = 0; i < flakeCount; i++) {
                const f = snowflakes[i];
                ctx.moveTo(f.x, f.y);
                ctx.arc(f.x, f.y, f.r, 0, Math.PI * 2, true);
            }
            ctx.fill();
            update();
            requestAnimationFrame(draw);
        }

        function update() {
            for (let i = 0; i < flakeCount; i++) {
                const f = snowflakes[i];
                f.y += f.vy;
                f.x += f.vx;

                // Reset khi chạm đáy
                if (f.y > canvas.height) {
                    f.y = -10;
                    f.x = Math.random() * canvas.width;
                }

                // Hiệu ứng lắc lư nhẹ
                f.x += Math.sin(f.d) * 0.5;
                f.d += 0.01;
            }
        }

        // Bắt đầu loop
        requestAnimationFrame(draw);
    },

    startPetals() {
        // Init logic for petals (hoa mai/đào)
        const petalCount = 40; // Số lượng cánh hoa
        const container = document.body;

        const style = document.createElement('style');
        style.textContent = `
            .petal {
                position: fixed;
                top: -10px;
                background-color: #ffc107; /* Màu vàng hoa mai */
                border-radius: 150% 0 150% 0;
                user-select: none;
                z-index: 9999;
                pointer-events: none;
                animation-name: fall-petal;
                animation-timing-function: linear;
                animation-iteration-count: infinite;
            }
            /* Nếu muốn hoa đào thì dùng màu này */
            /* .petal.peach { background-color: #ffb7b2; } */

            @keyframes fall-petal {
                0% { top: -10%; transform: translateX(0) rotate(0deg); opacity: 0.7; }
                20% { transform: translateX(20px) rotate(45deg); }
                40% { transform: translateX(-20px) rotate(90deg); }
                60% { transform: translateX(20px) rotate(135deg); }
                80% { transform: translateX(-20px) rotate(180deg); }
                100% { top: 110%; transform: translateX(0) rotate(225deg); opacity: 0; }
            }
        `;
        document.head.appendChild(style);

        for (let i = 0; i < petalCount; i++) {
            const petal = document.createElement('div');
            petal.classList.add('petal');

            // Kích thước ngẫu nhiên cho cánh hoa mai
            const sizeValue = Math.random() * 10 + 10;
            const size = sizeValue + 'px';
            petal.style.width = size;
            petal.style.height = size;

            // Vị trí ngẫu nhiên
            petal.style.left = Math.random() * 100 + 'vw';

            // Tốc độ và độ trễ ngẫu nhiên
            const duration = Math.random() * 3 + 4 + 's';
            const delay = Math.random() * 5 + 's';
            petal.style.animationDuration = duration;
            petal.style.animationDelay = delay;

            // Độ mờ ngẫu nhiên
            petal.style.opacity = Math.random() * 0.8 + 0.2;

            container.appendChild(petal);
        }
    }
};
