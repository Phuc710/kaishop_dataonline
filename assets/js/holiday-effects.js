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
        const container = document.body;

        // 1. Add Spiderwebs (Corner decorations)
        const webStyle = document.createElement('style');
        webStyle.textContent = `
            .spiderweb {
                position: absolute;
                width: 350px;
                height: 350px;
                background-repeat: no-repeat;
                background-size: contain;
                pointer-events: none;
                z-index: 999;
                opacity: 0.95;
                filter: drop-shadow(0 5px 15px rgba(0,0,0,0.8));
                transition: opacity 0.5s ease;
            }
            .spiderweb-left {
                top: 0;
                left: 0;
                background-image: url('/kaishop/assets/images/halloween/ghost.png');
                transform: scaleX(1) rotate(10deg);
                width: 180px;
                height: 180px;
                margin-top: 100px;

            }
            .spiderweb-right {
                top: 0;
                right: 0;
                margin-top: 30px;
                background-image: url('/kaishop/assets/images/halloween/tonhen.png');
                transform: scaleX(-1) rotate(10deg);
            }
            
            @media (max-width: 768px) {
                .spiderweb, .ghost-float-left {
                    display: none !important;
                }
            }
        `;
        document.head.appendChild(webStyle);

        const webLeft = document.createElement('div');
        webLeft.className = 'spiderweb spiderweb-left';

        const webRight = document.createElement('div');
        webRight.className = 'spiderweb spiderweb-right';

        const ghostLeft = document.createElement('div');
        ghostLeft.className = 'ghost-float-left';

        container.appendChild(webLeft);
        container.appendChild(webRight);
        container.appendChild(ghostLeft);
    },

    startSnow() {
        // Init logic for snowflakes
        const flakeCount = 50; // Số lượng bông tuyết
        const container = document.body;

        const style = document.createElement('style');
        style.textContent = `
            .snowflake {
                position: fixed;
                top: -10px;
                color: #fff;
                user-select: none;
                z-index: 9999;
                pointer-events: none;
                animation-name: fall, sway;
                animation-timing-function: linear, ease-in-out;
                animation-iteration-count: infinite, infinite;
            }
            @keyframes fall {
                0% { top: -10px; }
                100% { top: 100vh; }
            }
            @keyframes sway {
                0% { transform: translateX(0); }
                50% { transform: translateX(50px); }
                100% { transform: translateX(0); }
            }
        `;
        document.head.appendChild(style);

        for (let i = 0; i < flakeCount; i++) {
            const flake = document.createElement('div');
            flake.innerHTML = '&#10052;'; // Ký tự bông tuyết
            flake.classList.add('snowflake');

            // Ngẫu nhiên hóa vị trí, kích thước và tốc độ
            flake.style.left = Math.random() * 100 + 'vw';
            flake.style.opacity = Math.random() * 0.6 + 0.2;
            flake.style.fontSize = (Math.random() * 15 + 10) + 'px';

            const fallDuration = Math.random() * 5 + 5 + 's'; // 5s đến 10s
            const swayDuration = Math.random() * 2 + 2 + 's'; // 2s đến 4s
            const delay = Math.random() * 5 + 's';

            flake.style.animationDuration = `${fallDuration}, ${swayDuration}`;
            flake.style.animationDelay = `${delay}, ${delay}`;

            container.appendChild(flake);
        }
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
