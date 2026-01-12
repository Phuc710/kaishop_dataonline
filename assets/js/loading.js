/**
 * LoadingOverlay Class - OOP Loading System
 * Universal loading indicator for the entire website
 */

class LoadingOverlay {
    constructor() {
        this.overlay = null;
        this.isActive = false;
        this.defaultText = 'Đang tải...';
        this.init();
    }

    /**
     * Initialize loading overlay
     */
    init() {
        // Check if overlay already exists
        if (document.getElementById('globalLoadingOverlay')) {
            this.overlay = document.getElementById('globalLoadingOverlay');
            return;
        }

        // Create overlay HTML
        this.overlay = document.createElement('div');
        this.overlay.id = 'globalLoadingOverlay';
        this.overlay.className = 'loading-overlay';
        this.overlay.innerHTML = `
            <div class="loading-wrapper">
                <div class="loading-spinner">
                    <div class="loading-center-dot"></div>
                </div>
                <div class="loading-text">${this.defaultText}</div>
            </div>
        `;

        // Append to body when DOM is ready
        if (document.body) {
            document.body.appendChild(this.overlay);
        } else {
            document.addEventListener('DOMContentLoaded', () => {
                document.body.appendChild(this.overlay);
            });
        }
    }

    /**
     * Show loading overlay
     * @param {string} text - Optional loading text
     */
    show(text = null) {
        if (!this.overlay) {
            this.init();
        }

        // Update text if provided
        if (text) {
            const textElement = this.overlay.querySelector('.loading-text');
            if (textElement) {
                textElement.textContent = text;
            }
        }

        // Show overlay
        setTimeout(() => {
            this.overlay.classList.add('active');
            this.isActive = true;

            // Prevent body scroll
            document.body.style.overflow = 'hidden';
        }, 10);
    }

    /**
     * Hide loading overlay
     * @param {number} delay - Optional delay in milliseconds
     */
    hide(delay = 0) {
        if (!this.overlay || !this.isActive) return;

        setTimeout(() => {
            this.overlay.classList.remove('active');
            this.isActive = false;

            // Restore body scroll
            document.body.style.overflow = '';

            // Reset text to default
            const textElement = this.overlay.querySelector('.loading-text');
            if (textElement) {
                textElement.textContent = this.defaultText;
            }
        }, delay);
    }

    /**
     * Toggle loading state
     */
    toggle() {
        if (this.isActive) {
            this.hide();
        } else {
            this.show();
        }
    }

    /**
     * Show loading for a specific duration
     * @param {number} duration - Duration in milliseconds
     * @param {string} text - Optional loading text
     */
    showFor(duration, text = null) {
        this.show(text);
        setTimeout(() => this.hide(), duration);
    }

    /**
     * Show loading during async operation
     * @param {Promise} promise - Promise to wait for
     * @param {string} text - Optional loading text
     */
    async showDuring(promise, text = null) {
        this.show(text);
        try {
            const result = await promise;
            this.hide();
            return result;
        } catch (error) {
            this.hide();
            throw error;
        }
    }

    /**
     * Check if loading is active
     */
    isLoading() {
        return this.isActive;
    }

    /**
     * Destroy the loading overlay
     */
    destroy() {
        if (this.overlay) {
            this.hide();
            setTimeout(() => {
                this.overlay.remove();
                this.overlay = null;
            }, 300);
        }
    }
}

// Create global instance
const Loading = new LoadingOverlay();

// Export for use in modules (if needed)
if (typeof module !== 'undefined' && module.exports) {
    module.exports = LoadingOverlay;
}

// Make available globally
window.Loading = Loading;
window.LoadingOverlay = LoadingOverlay;

// Auto-hide loading on page load
window.addEventListener('load', () => {
    Loading.hide();
});

// Show loading on page navigation (for SPAs or form submissions)
document.addEventListener('DOMContentLoaded', () => {
    // Intercept form submissions to show loading
    document.addEventListener('submit', (e) => {
        const form = e.target;

        // Skip if form has data-no-loading attribute
        if (form.hasAttribute('data-no-loading')) {
            return;
        }

        // Show loading for form submission
        Loading.show('Đang xử lý...');
    });

    // Intercept link clicks for navigation loading
    document.addEventListener('click', (e) => {
        const link = e.target.closest('a');

        if (link && !link.hasAttribute('data-no-loading')) {
            const href = link.getAttribute('href');

            // Show loading for internal navigation (not external links, anchors, or javascript:)
            if (href &&
                !href.startsWith('#') &&
                !href.startsWith('javascript:') &&
                !href.startsWith('mailto:') &&
                !href.startsWith('tel:') &&
                !link.hasAttribute('target')) {

                Loading.show();
            }
        }
    });
});

// Utility functions for common scenarios
Loading.fetchWithLoading = async function (url, options = {}, loadingText = null) {
    this.show(loadingText || 'Đang tải dữ liệu...');
    try {
        const response = await fetch(url, options);
        this.hide();
        return response;
    } catch (error) {
        this.hide();
        throw error;
    }
};

Loading.redirectWithLoading = function (url, delay = 500) {
    this.show('Đang chuyển hướng...');
    setTimeout(() => {
        window.location.href = url;
    }, delay);
};

