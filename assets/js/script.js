/**
 * Martin Shop - Main JavaScript
 * Xử lý animation, counter, và các tính năng tương tác
 */

// DOM Ready
document.addEventListener('DOMContentLoaded', function() {
    // Scroll Reveal Animation
    initScrollReveal();
    
    // Counter Animation
    initCounterAnimation();
    
    // Product Card Glow Effect
    initProductGlowEffect();
    
    // Mobile Menu Toggle
    initMobileMenu();
    
    // Lazy Load Images
    initLazyLoad();
});

/**
 * Scroll Reveal - Hiện elements khi scroll
 */
function initScrollReveal() {
    const reveals = document.querySelectorAll('.reveal');
    
    const revealObserver = new IntersectionObserver((entries) => {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                entry.target.classList.add('active');
                revealObserver.unobserve(entry.target);
            }
        });
    }, {
        threshold: 0.15
    });
    
    reveals.forEach(reveal => {
        revealObserver.observe(reveal);
    });
}

/**
 * Counter Animation - Đếm số tự động
 */
function initCounterAnimation() {
    const counters = document.querySelectorAll('.stat-number');
    
    counters.forEach(counter => {
        const target = parseInt(counter.getAttribute('data-target') || counter.innerText.replace(/[^0-9]/g, ''));
        const duration = 2000; // 2 seconds
        const increment = target / (duration / 16);
        let current = 0;
        
        const counterObserver = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    const timer = setInterval(() => {
                        current += increment;
                        if (current >= target) {
                            counter.innerText = formatNumber(target);
                            clearInterval(timer);
                        } else {
                            counter.innerText = formatNumber(Math.floor(current));
                        }
                    }, 16);
                    counterObserver.unobserve(entry.target);
                }
            });
        }, { threshold: 0.5 });
        
        counterObserver.observe(counter);
    });
}

/**
 * Format number with commas
 */
function formatNumber(num) {
    return num.toString().replace(/\B(?=(\d{3})+(?!\d))/g, ",");
}

/**
 * Product Card Glow Effect - Hiệu ứng sáng theo chuột
 */
function initProductGlowEffect() {
    const productCards = document.querySelectorAll('.product-card');
    
    productCards.forEach(card => {
        card.addEventListener('mousemove', (e) => {
            const rect = card.getBoundingClientRect();
            const x = ((e.clientX - rect.left) / rect.width) * 100;
            const y = ((e.clientY - rect.top) / rect.height) * 100;
            
            card.style.setProperty('--mouse-x', `${x}%`);
            card.style.setProperty('--mouse-y', `${y}%`);
        });
    });
}

/**
 * Mobile Menu Toggle
 */
function initMobileMenu() {
    const menuToggle = document.getElementById('menuToggle');
    const navMenu = document.getElementById('navMenu');
    
    if (menuToggle && navMenu) {
        menuToggle.addEventListener('click', () => {
            navMenu.classList.toggle('active');
        });
    }
}

/**
 * Lazy Load Images
 */
function initLazyLoad() {
    const images = document.querySelectorAll('img[data-src]');
    
    const imageObserver = new IntersectionObserver((entries) => {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                const img = entry.target;
                img.src = img.dataset.src;
                img.removeAttribute('data-src');
                imageObserver.unobserve(img);
            }
        });
    });
    
    images.forEach(img => imageObserver.observe(img));
}

/**
 * Add to Cart
 */
function addToCart(productId) {
    if (!isLoggedIn()) {
        window.location.href = `${window.APP_URL}/auth?redirect=${encodeURIComponent(window.location.pathname)}`;
        return;
    }
    
    fetch(`${window.APP_URL}/giohang/add.php`, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({ product_id: productId, quantity: 1 })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showNotification('Đã thêm vào giỏ hàng!', 'success');
            updateCartCount();
        } else {
            showNotification(data.message || 'Có lỗi xảy ra', 'error');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showNotification('Có lỗi xảy ra', 'error');
    });
}

/**
 * Buy Now
 */
function buyNow(productId) {
    if (!isLoggedIn()) {
        window.location.href = `${window.APP_URL}/auth?redirect=${window.APP_URL}/thanhtoan&product=${productId}`;
        return;
    }
    
    window.location.href = `${window.APP_URL}/thanhtoan?product=${productId}`;
}

/**
 * Check if user is logged in
 */
function isLoggedIn() {
    return document.body.dataset.loggedIn === 'true';
}

/**
 * Update Cart Count
 */
function updateCartCount() {
    fetch(`${window.APP_URL}/giohang/count.php`)
        .then(response => response.json())
        .then(data => {
            const cartCount = document.querySelector('.cart-count');
            if (cartCount) {
                cartCount.textContent = data.count;
                if (data.count > 0) {
                    cartCount.style.display = 'flex';
                } else {
                    cartCount.style.display = 'none';
                }
            }
        })
        .catch(error => console.error('Error:', error));
}

/**
 * Show Notification
 */
function showNotification(message, type = 'info') {
    const notification = document.createElement('div');
    notification.className = `notification notification-${type}`;
    notification.textContent = message;
    notification.style.cssText = `
        position: fixed;
        top: 100px;
        right: 20px;
        background: ${type === 'success' ? '#00ff88' : type === 'error' ? '#ff0055' : '#6f00ff'};
        color: white;
        padding: 15px 25px;
        border-radius: 8px;
        box-shadow: 0 10px 30px rgba(0,0,0,0.3);
        z-index: 9999;
        animation: slideIn 0.3s ease;
    `;
    
    document.body.appendChild(notification);
    
    setTimeout(() => {
        notification.style.animation = 'slideOut 0.3s ease';
        setTimeout(() => notification.remove(), 300);
    }, 3000);
}

/**
 * Search Products
 */
function searchProducts(query) {
    if (!query.trim()) return;
    
    window.location.href = `${window.APP_URL}/sanpham?search=${encodeURIComponent(query)}`;
}

// Animation Keyframes
const style = document.createElement('style');
style.textContent = `
    @keyframes slideIn {
        from { transform: translateX(100%); opacity: 0; }
        to { transform: translateX(0); opacity: 1; }
    }
    @keyframes slideOut {
        from { transform: translateX(0); opacity: 1; }
        to { transform: translateX(100%); opacity: 0; }
    }
`;
document.head.appendChild(style);

/**
 * Update Stock Realtime (WebSocket hoặc Polling)
 * Cập nhật số lượng sản phẩm realtime
 */
function initRealtimeStock() {
    setInterval(() => {
        const productIds = Array.from(document.querySelectorAll('.product-card'))
            .map(card => card.dataset.productId)
            .filter(Boolean);
        
        if (productIds.length === 0) return;
        
        fetch(`${window.API_URL}/products/stock.php`, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ product_ids: productIds })
        })
        .then(response => response.json())
        .then(data => {
            Object.entries(data).forEach(([productId, stock]) => {
                const stockElement = document.querySelector(`.product-card[data-product-id="${productId}"] .stock`);
                if (stockElement) {
                    stockElement.textContent = `Còn ${stock} sản phẩm`;
                    if (stock === 0) {
                        stockElement.parentElement.querySelector('.btn-primary').disabled = true;
                        stockElement.parentElement.querySelector('.btn-primary').textContent = 'Hết hàng';
                    }
                }
            });
        })
        .catch(error => console.error('Error updating stock:', error));
    }, 5000); // Update every 5 seconds
}

// Initialize realtime features if on product pages
if (document.querySelector('.product-card')) {
    initRealtimeStock();
}

/**
 * Theme Toggle - Chế độ sáng/tối
 */
function initThemeToggle() {
    // Tạo nút toggle nếu chưa có
    if (!document.querySelector('.theme-toggle')) {
        const btn = document.createElement('button');
        btn.className = 'theme-toggle';
        btn.innerHTML = '<i class="fas fa-moon"></i>';
        btn.title = 'Chuyển đổi chế độ sáng/tối';
        btn.onclick = toggleTheme;
        document.body.appendChild(btn);
    }
}

function loadTheme() {
    const theme = localStorage.getItem('theme') || 'dark';
    if (theme === 'light') {
        document.body.classList.add('light-mode');
        updateThemeIcon('light');
    }
}

function toggleTheme() {
    document.body.classList.toggle('light-mode');
    const isLight = document.body.classList.contains('light-mode');
    localStorage.setItem('theme', isLight ? 'light' : 'dark');
    updateThemeIcon(isLight ? 'light' : 'dark');
}

function updateThemeIcon(theme) {
    const btn = document.querySelector('.theme-toggle');
    if (btn) {
        btn.innerHTML = theme === 'light' 
            ? '<i class="fas fa-sun"></i>' 
            : '<i class="fas fa-moon"></i>';
    }
}
