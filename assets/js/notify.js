/**
 * Custom Notification System - Thay thế SweetAlert2
 */

class Notify {
    constructor() {
        this.createContainer();
    }

    createContainer() {
        if (!document.getElementById('notify-container')) {
            // Wait for body to be ready
            if (!document.body) {
                if (document.readyState === 'loading') {
                    document.addEventListener('DOMContentLoaded', () => this.createContainer());
                }
                return;
            }

            const container = document.createElement('div');
            container.id = 'notify-container';
            container.className = 'notify-container';
            document.body.appendChild(container);
        }
    }

    show(options) {
        const defaults = {
            type: 'info', // success, error, warning, info
            title: '',
            message: '',
            duration: 3000,
            position: 'top-center', // top-center, top-right, bottom-center
            showConfirm: false,
            confirmText: 'OK',
            cancelText: 'Hủy',
            onConfirm: null,
            onCancel: null
        };

        const config = { ...defaults, ...options };

        // Ensure container exists
        this.createContainer();
        const container = document.getElementById('notify-container');

        if (!container) {
            console.error('Notify container not found');
            return null;
        }

        // KHÔNG AUTO SCROLL - Notification hiển thị tại chỗ

        // Create notification element
        const notification = document.createElement('div');
        notification.className = `notify notify-${config.type} notify-${config.position}`;

        // Icon based on type
        const icons = {
            success: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20 6L9 17l-5-5"/></svg>',
            error: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M6 18L18 6M6 6l12 12"/></svg>',
            warning: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>',
            info: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><path d="M12 16v-4m0-4h.01"/></svg>'
        };

        let html = `
            <div class="notify-header">
                <div class="notify-icon">${icons[config.type]}</div>
                <div class="notify-content">
                    ${config.title ? `<div class="notify-title">${config.title}</div>` : ''}
                    ${config.message ? `<div class="notify-message">${config.message}</div>` : ''}
                </div>
            </div>
        `;

        if (config.showConfirm) {
            html += `
                <div class="notify-buttons">
                    <button class="notify-btn notify-btn-confirm">${config.confirmText}</button>
                    <button class="notify-btn notify-btn-cancel">${config.cancelText}</button>
                </div>
            `;
        }

        notification.innerHTML = html;

        // Add event listeners for buttons
        if (config.showConfirm) {
            const confirmBtn = notification.querySelector('.notify-btn-confirm');
            const cancelBtn = notification.querySelector('.notify-btn-cancel');

            confirmBtn.addEventListener('click', () => {
                if (config.onConfirm) config.onConfirm();
                this.remove(notification);
            });

            cancelBtn.addEventListener('click', () => {
                if (config.onCancel) config.onCancel();
                this.remove(notification);
            });
        }

        container.appendChild(notification);

        // Trigger animation
        setTimeout(() => notification.classList.add('notify-show'), 10);

        // Auto-remove after duration (if not a confirm dialog)
        if (!config.showConfirm && config.duration > 0) {
            setTimeout(() => {
                this.remove(notification);
            }, config.duration);
        } else if (config.showConfirm && config.duration === 0) {
            // Confirm dialogs stay until user clicks a button
            // Do nothing - buttons will handle removal
        }

        return notification;
    }

    remove(notification) {
        notification.classList.remove('notify-show');
        notification.classList.add('notify-hide');
        setTimeout(() => notification.remove(), 300);
    }

    success(title, message = '', duration = 3000) {
        return this.show({ type: 'success', title, message, duration });
    }

    error(title, message = '', duration = 3000) {
        return this.show({ type: 'error', title, message, duration });
    }

    warning(title, message = '', duration = 3000) {
        return this.show({ type: 'warning', title, message, duration });
    }

    info(title, message = '', duration = 3000) {
        return this.show({ type: 'info', title, message, duration });
    }

    confirm(options) {
        return new Promise((resolve) => {
            const defaults = {
                type: 'warning',
                title: 'Xác nhận',
                message: 'Bạn có chắc chắn?',
                confirmText: 'OK',
                cancelText: 'Hủy'
            };

            this.show({
                ...defaults,
                ...options,
                showConfirm: true,
                duration: 0, // No auto-close for confirm dialogs
                onConfirm: () => resolve(true),
                onCancel: () => resolve(false)
            });
        });
    }
}

// Queue for notifications before initialization
let notifyQueue = [];

// Temporary notify object that queues calls
window.notify = {
    success: function (...args) { notifyQueue.push({ type: 'success', args }); },
    error: function (...args) { notifyQueue.push({ type: 'error', args }); },
    warning: function (...args) { notifyQueue.push({ type: 'warning', args }); },
    info: function (...args) { notifyQueue.push({ type: 'info', args }); },
    confirm: function (...args) {
        return new Promise(resolve => {
            notifyQueue.push({ type: 'confirm', args, resolve });
        });
    },
    show: function (...args) { notifyQueue.push({ type: 'show', args }); }
};

// Initialize real notify instance
function initNotify() {
    const realNotify = new Notify();

    // Process queued notifications
    notifyQueue.forEach(item => {
        if (item.type === 'confirm') {
            realNotify.confirm(...item.args).then(item.resolve);
        } else {
            realNotify[item.type](...item.args);
        }
    });
    notifyQueue = [];

    // Replace temp notify with real one
    window.notify = realNotify;
}

// Initialize after DOM is ready
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initNotify);
} else {
    initNotify();
}

// Compatibility helpers (for code that used Swal)
// Only define if not already present from the real SweetAlert2 library
if (typeof Swal === 'undefined') {
    window.Swal = {
        fire: function (titleOrOptions, text, icon) {
            // Ensure notify is initialized
            if (!window.notify || !window.notify.show) {
                console.warn('Notify not yet initialized, queuing...');
                return new Promise(resolve => {
                    setTimeout(() => Swal.fire(titleOrOptions, text, icon).then(resolve), 100);
                });
            }

            if (typeof titleOrOptions === 'object') {
                const opts = titleOrOptions;
                return notify.show({
                    title: opts.title,
                    message: opts.text || opts.html,
                    type: opts.icon,
                    duration: opts.timer || 3000,
                    showConfirm: opts.showConfirmButton !== false,
                    confirmText: opts.confirmButtonText || 'OK',
                    cancelText: opts.cancelButtonText || 'Hủy',
                    position: opts.toast ? 'top-right' : 'top-center'
                });
            } else {
                return notify.show({
                    title: titleOrOptions,
                    message: text,
                    type: icon,
                    duration: 3000
                });
            }
        }
    };
}
