
(function () {
    const savedTheme = localStorage.getItem('theme') || 'dark';
    document.documentElement.setAttribute('data-theme', savedTheme);

    // Force repaint on mobile to ensure theme is applied
    if (/Android|webOS|iPhone|iPad|iPod|BlackBerry|IEMobile|Opera Mini/i.test(navigator.userAgent)) {
        document.documentElement.style.display = 'none';
        document.documentElement.offsetHeight; // Force reflow
        document.documentElement.style.display = '';
    }
})();

/**
 * KaiShop Theme Switcher
 * Handles light/dark mode toggling with persistence
 */

const ThemeManager = {
    init() {
        // Check local storage or system preference
        const savedTheme = localStorage.getItem('theme');
        // Default to dark if no preference (as per KaiShop original design)
        const theme = savedTheme || 'dark';

        this.applyTheme(theme);

        // Expose toggle function globally
        window.toggleTheme = () => this.toggle();

        // Attach event listener to the specific toggle button
        const toggleBtn = document.getElementById('themeToggle');
        if (toggleBtn) {
            toggleBtn.addEventListener('click', (e) => {
                e.preventDefault();
                this.toggle();
            });
        }
    },

    applyTheme(theme) {
        document.documentElement.setAttribute('data-theme', theme);
        localStorage.setItem('theme', theme);

        // Update icon if it exists
        this.updateIcon(theme);

        // Dispatch event for other components
        window.dispatchEvent(new CustomEvent('themeChanged', { detail: { theme } }));
    },

    toggle() {
        const current = document.documentElement.getAttribute('data-theme') || 'dark';
        const newTheme = current === 'dark' ? 'light' : 'dark';
        this.applyTheme(newTheme);
    },

    updateIcon(theme) {
        let baseUrl = '/kaishop'; // Fallback
        if (window.APP_CONFIG && window.APP_CONFIG.baseUrl) {
            baseUrl = window.APP_CONFIG.baseUrl;
        }
        // Remove trailing slash if present to avoid double slash
        if (baseUrl.endsWith('/')) {
            baseUrl = baseUrl.slice(0, -1);
        }

        const path = `${baseUrl}/assets/images/`;
        const targetIcon = theme === 'light' ? 'moon.png' : 'sun.png';
        const targetSrc = path + targetIcon;

        // Update desktop icon (header & auth)
        const iconElement = document.getElementById('theme-icon');
        if (iconElement) {
            if (iconElement.tagName === 'IMG') {
                iconElement.src = targetSrc;
            } else {
                iconElement.className = theme === 'light' ? 'fas fa-moon' : 'fas fa-sun';
            }
        }

        // Update mobile icon directly
        const mobileIcon = document.getElementById('mobile-theme-icon');
        const mobileText = document.getElementById('mobile-theme-text');

        if (mobileIcon) {
            if (mobileIcon.tagName === 'IMG') {
                mobileIcon.src = targetSrc;
            } else {
                mobileIcon.className = theme === 'light' ? 'fas fa-moon' : 'fas fa-sun';
            }
        }

        // FIX: Correctly set mobile text - when theme is 'dark', show 'Light' (to switch TO light)
        // when theme is 'light', show 'Dark' (to switch TO dark)
        if (mobileText) {
            mobileText.textContent = theme === 'light' ? 'Dark' : 'Light';
        }

        // Update sidebar toggle button
        const toggleBtn = document.getElementById('themeToggle');
        if (toggleBtn) {
            const img = toggleBtn.querySelector('img');
            const span = toggleBtn.querySelector('span');

            if (img) img.src = targetSrc;
            if (span) span.textContent = theme === 'light' ? 'Chế Độ Tối' : 'Chế Độ Sáng';
        }
    }
};

// Initialize on load
document.addEventListener('DOMContentLoaded', () => {
    ThemeManager.init();
});

(function () {
    const savedTheme = localStorage.getItem('theme') || 'dark';
    document.documentElement.setAttribute('data-theme', savedTheme);
})();

