/**
 * AdminModal Class - OOP Pattern
 * Quản lý popup đồng bộ với trạng thái thành công/thất bại/loading
 * Sử dụng chung cho toàn bộ admin
 */

class AdminModal {
    constructor() {
        this.modalElement = null;
        this.currentAction = null;
        this.callbacks = {
            onSuccess: null,
            onError: null,
            onComplete: null
        };
        
        this.init();
    }

    // Khởi tạo modal container
    init() {
        // Wait for body to be ready
        if (!document.body) {
            if (document.readyState === 'loading') {
                document.addEventListener('DOMContentLoaded', () => this.init());
            }
            return;
        }
        
        if (!document.getElementById('admin-modal-container')) {
            const container = document.createElement('div');
            container.id = 'admin-modal-container';
            document.body.appendChild(container);
        }
        
        // Add styles if not exists
        if (!document.getElementById('admin-modal-styles')) {
            this.injectStyles();
        }
    }

    // Inject CSS styles
    injectStyles() {
        const style = document.createElement('style');
        style.id = 'admin-modal-styles';
        style.textContent = `
            .admin-modal-overlay {
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
                opacity: 0;
                animation: fadeIn 0.3s forwards;
            }

            @keyframes fadeIn {
                to { opacity: 1; }
            }

            @keyframes fadeOut {
                to { opacity: 0; }
            }

            @keyframes scaleIn {
                from { 
                    transform: scale(0.9) translateY(-20px);
                    opacity: 0;
                }
                to { 
                    transform: scale(1) translateY(0);
                    opacity: 1;
                }
            }

            @keyframes scaleOut {
                to { 
                    transform: scale(0.9) translateY(20px);
                    opacity: 0;
                }
            }

            @keyframes spin {
                to { transform: rotate(360deg); }
            }

            @keyframes pulse {
                0%, 100% { transform: scale(1); }
                50% { transform: scale(1.05); }
            }

            @keyframes checkmark {
                0% { stroke-dashoffset: 100; }
                100% { stroke-dashoffset: 0; }
            }

            @keyframes errorX {
                0% { stroke-dashoffset: 100; }
                100% { stroke-dashoffset: 0; }
            }

            .admin-modal-box {
                background: linear-gradient(135deg, #1e293b, #0f172a);
                padding: 2.5rem;
                border-radius: 20px;
                border: 1px solid rgba(139, 92, 246, 0.3);
                box-shadow: 0 25px 80px rgba(0, 0, 0, 0.6);
                text-align: center;
                min-width: 400px;
                max-width: 500px;
                position: relative;
                animation: scaleIn 0.4s cubic-bezier(0.34, 1.56, 0.64, 1);
            }

            .admin-modal-box.closing {
                animation: scaleOut 0.3s forwards;
            }

            /* Loading State */
            .admin-modal-loading {
                width: 80px;
                height: 80px;
                margin: 0 auto 1.5rem;
                position: relative;
            }

            .admin-modal-spinner {
                width: 80px;
                height: 80px;
                border: 5px solid rgba(139, 92, 246, 0.2);
                border-top-color: #8b5cf6;
                border-radius: 50%;
                animation: spin 1s linear infinite;
            }

            .admin-modal-spinner-inner {
                position: absolute;
                top: 50%;
                left: 50%;
                transform: translate(-50%, -50%);
                width: 50px;
                height: 50px;
                border: 4px solid rgba(139, 92, 246, 0.3);
                border-top-color: #a78bfa;
                border-radius: 50%;
                animation: spin 0.7s linear infinite reverse;
            }

            /* Success State */
            .admin-modal-success {
                width: 80px;
                height: 80px;
                margin: 0 auto 1.5rem;
                position: relative;
                background: rgba(16, 185, 129, 0.1);
                border-radius: 50%;
                border: 3px solid #10b981;
                animation: pulse 0.6s ease-out;
            }

            .admin-modal-checkmark {
                width: 80px;
                height: 80px;
                stroke: #10b981;
                stroke-width: 4;
                fill: none;
                stroke-linecap: round;
                stroke-dasharray: 100;
                animation: checkmark 0.6s ease-out forwards;
            }

            /* Error State */
            .admin-modal-error {
                width: 80px;
                height: 80px;
                margin: 0 auto 1.5rem;
                position: relative;
                background: rgba(239, 68, 68, 0.1);
                border-radius: 50%;
                border: 3px solid #ef4444;
                animation: pulse 0.6s ease-out;
            }

            .admin-modal-error-icon {
                width: 80px;
                height: 80px;
                stroke: #ef4444;
                stroke-width: 4;
                fill: none;
                stroke-linecap: round;
                stroke-dasharray: 100;
                animation: errorX 0.6s ease-out forwards;
            }

            /* Setup State */
            .admin-modal-setup {
                width: 80px;
                height: 80px;
                margin: 0 auto 1.5rem;
                position: relative;
                background: rgba(59, 130, 246, 0.1);
                border-radius: 50%;
                border: 3px solid #3b82f6;
                animation: pulse 0.6s ease-out;
            }

            .admin-modal-setup-icon {
                width: 80px;
                height: 80px;
                stroke: #3b82f6;
                stroke-width: 3;
                fill: none;
                stroke-linecap: round;
            }

            /* Text Content */
            .admin-modal-title {
                color: #f8fafc;
                font-size: 1.5rem;
                font-weight: 700;
                margin-bottom: 0.75rem;
            }

            .admin-modal-message {
                color: #94a3b8;
                font-size: 1rem;
                line-height: 1.5;
                margin-bottom: 1.5rem;
            }

            .admin-modal-submessage {
                color: #64748b;
                font-size: 0.9rem;
                margin-top: 0.5rem;
            }

            /* Buttons */
            .admin-modal-buttons {
                display: flex;
                gap: 0.75rem;
                justify-content: center;
                margin-top: 1.5rem;
            }

            .admin-modal-btn {
                padding: 0.75rem 2rem;
                border: none;
                border-radius: 12px;
                font-size: 1rem;
                font-weight: 600;
                cursor: pointer;
                transition: all 0.3s;
                display: inline-flex;
                align-items: center;
                gap: 0.5rem;
            }

            .admin-modal-btn-primary {
                background: linear-gradient(135deg, #8b5cf6, #7c3aed);
                color: white;
                box-shadow: 0 4px 15px rgba(139, 92, 246, 0.4);
            }

            .admin-modal-btn-primary:hover {
                transform: translateY(-2px);
                box-shadow: 0 6px 20px rgba(139, 92, 246, 0.6);
            }

            .admin-modal-btn-success {
                background: linear-gradient(135deg, #10b981, #059669);
                color: white;
                box-shadow: 0 4px 15px rgba(16, 185, 129, 0.4);
            }

            .admin-modal-btn-success:hover {
                transform: translateY(-2px);
                box-shadow: 0 6px 20px rgba(16, 185, 129, 0.6);
            }

            .admin-modal-btn-secondary {
                background: rgba(100, 116, 139, 0.2);
                color: #cbd5e1;
                border: 1px solid rgba(100, 116, 139, 0.4);
            }

            .admin-modal-btn-secondary:hover {
                background: rgba(100, 116, 139, 0.3);
                border-color: rgba(100, 116, 139, 0.6);
            }

            /* Progress Bar */
            .admin-modal-progress {
                width: 100%;
                height: 4px;
                background: rgba(139, 92, 246, 0.2);
                border-radius: 2px;
                overflow: hidden;
                margin-top: 1rem;
            }

            .admin-modal-progress-bar {
                height: 100%;
                background: linear-gradient(90deg, #8b5cf6, #a78bfa);
                border-radius: 2px;
                transition: width 0.3s;
                animation: pulse 1.5s infinite;
            }
        `;
        document.head.appendChild(style);
    }

    // Show Loading Modal
    showLoading(message = 'Đang xử lý...', submessage = 'Vui lòng đợi...') {
        this.close(); // Close any existing modal
        
        const container = document.getElementById('admin-modal-container');
        
        const modal = document.createElement('div');
        modal.className = 'admin-modal-overlay';
        modal.innerHTML = `
            <div class="admin-modal-box">
                <div class="admin-modal-loading">
                    <div class="admin-modal-spinner"></div>
                    <div class="admin-modal-spinner-inner"></div>
                </div>
                <div class="admin-modal-title">${message}</div>
                <div class="admin-modal-message">${submessage}</div>
                <div class="admin-modal-progress">
                    <div class="admin-modal-progress-bar" style="width: 60%"></div>
                </div>
            </div>
        `;
        
        container.appendChild(modal);
        this.modalElement = modal;
        
        return this;
    }

    // Show Success Modal
    showSuccess(message = 'Thành công!', submessage = '', options = {}) {
        this.close();
        
        const {
            autoClose = true,
            duration = 2000,
            showButton = false,
            buttonText = 'OK',
            onButtonClick = null
        } = options;
        
        const container = document.getElementById('admin-modal-container');
        
        const modal = document.createElement('div');
        modal.className = 'admin-modal-overlay';
        modal.innerHTML = `
            <div class="admin-modal-box">
                <div class="admin-modal-success">
                    <svg class="admin-modal-checkmark" viewBox="0 0 52 52">
                        <path d="M14 27l7 7 16-16"/>
                    </svg>
                </div>
                <div class="admin-modal-title">🎉 ${message}</div>
                ${submessage ? `<div class="admin-modal-message">${submessage}</div>` : ''}
                ${showButton ? `
                    <div class="admin-modal-buttons">
                        <button class="admin-modal-btn admin-modal-btn-success" onclick="adminModal.close()">
                            <i class="fas fa-check"></i> ${buttonText}
                        </button>
                    </div>
                ` : ''}
            </div>
        `;
        
        container.appendChild(modal);
        this.modalElement = modal;
        
        if (showButton && onButtonClick) {
            modal.querySelector('.admin-modal-btn-success').onclick = () => {
                onButtonClick();
                this.close();
            };
        }
        
        if (autoClose && !showButton) {
            setTimeout(() => this.close(), duration);
        }
        
        // Callback
        if (this.callbacks.onSuccess) {
            this.callbacks.onSuccess();
        }
        
        return this;
    }

    // Show Error Modal
    showError(message = 'Có lỗi xảy ra!', submessage = '', options = {}) {
        this.close();
        
        const {
            autoClose = true,
            duration = 3000,
            showButton = true,
            buttonText = 'Đóng',
            onButtonClick = null
        } = options;
        
        const container = document.getElementById('admin-modal-container');
        
        const modal = document.createElement('div');
        modal.className = 'admin-modal-overlay';
        modal.innerHTML = `
            <div class="admin-modal-box">
                <div class="admin-modal-error">
                    <svg class="admin-modal-error-icon" viewBox="0 0 52 52">
                        <path d="M16 16l20 20M36 16l-20 20"/>
                    </svg>
                </div>
                <div class="admin-modal-title">❌ ${message}</div>
                ${submessage ? `<div class="admin-modal-message">${submessage}</div>` : ''}
                ${showButton ? `
                    <div class="admin-modal-buttons">
                        <button class="admin-modal-btn admin-modal-btn-secondary" onclick="adminModal.close()">
                            <i class="fas fa-times"></i> ${buttonText}
                        </button>
                    </div>
                ` : ''}
            </div>
        `;
        
        container.appendChild(modal);
        this.modalElement = modal;
        
        if (showButton && onButtonClick) {
            modal.querySelector('.admin-modal-btn-secondary').onclick = () => {
                onButtonClick();
                this.close();
            };
        }
        
        if (autoClose && !showButton) {
            setTimeout(() => this.close(), duration);
        }
        
        // Callback
        if (this.callbacks.onError) {
            this.callbacks.onError();
        }
        
        return this;
    }

    // Show Setup Modal (thông báo setup/config)
    showSetup(message = 'Đang cấu hình...', submessage = '', options = {}) {
        this.close();
        
        const {
            autoClose = false,
            duration = 2000,
            showButton = true,
            buttonText = 'OK'
        } = options;
        
        const container = document.getElementById('admin-modal-container');
        
        const modal = document.createElement('div');
        modal.className = 'admin-modal-overlay';
        modal.innerHTML = `
            <div class="admin-modal-box">
                <div class="admin-modal-setup">
                    <svg class="admin-modal-setup-icon" viewBox="0 0 52 52">
                        <circle cx="26" cy="26" r="10" stroke-width="2"/>
                        <path d="M26 10v6M26 36v6M10 26h6M36 26h6M16 16l4 4M32 32l4 4M36 16l-4 4M20 32l-4 4"/>
                    </svg>
                </div>
                <div class="admin-modal-title">⚙️ ${message}</div>
                ${submessage ? `<div class="admin-modal-message">${submessage}</div>` : ''}
                ${showButton ? `
                    <div class="admin-modal-buttons">
                        <button class="admin-modal-btn admin-modal-btn-primary" onclick="adminModal.close()">
                            <i class="fas fa-check"></i> ${buttonText}
                        </button>
                    </div>
                ` : ''}
            </div>
        `;
        
        container.appendChild(modal);
        this.modalElement = modal;
        
        if (autoClose && !showButton) {
            setTimeout(() => this.close(), duration);
        }
        
        return this;
    }

    // Show Confirm Modal
    async confirm(options = {}) {
        const {
            title = 'Xác nhận?',
            message = 'Bạn có chắc chắn?',
            confirmText = 'Xác nhận',
            cancelText = 'Hủy',
            type = 'warning' // warning, danger, info
        } = options;
        
        return new Promise((resolve) => {
            this.close();
            
            const container = document.getElementById('admin-modal-container');
            
            const iconHtml = type === 'danger' 
                ? '<div class="admin-modal-error"><svg class="admin-modal-error-icon" viewBox="0 0 52 52"><path d="M16 16l20 20M36 16l-20 20"/></svg></div>'
                : '<div class="admin-modal-setup"><svg class="admin-modal-setup-icon" viewBox="0 0 52 52"><circle cx="26" cy="26" r="10" stroke-width="2"/><path d="M26 20v8M26 32v2"/></svg></div>';
            
            const modal = document.createElement('div');
            modal.className = 'admin-modal-overlay';
            modal.innerHTML = `
                <div class="admin-modal-box">
                    ${iconHtml}
                    <div class="admin-modal-title">${title}</div>
                    <div class="admin-modal-message">${message}</div>
                    <div class="admin-modal-buttons">
                        <button class="admin-modal-btn admin-modal-btn-primary confirm-yes">
                            <i class="fas fa-check"></i> ${confirmText}
                        </button>
                        <button class="admin-modal-btn admin-modal-btn-secondary confirm-no">
                            <i class="fas fa-times"></i> ${cancelText}
                        </button>
                    </div>
                </div>
            `;
            
            container.appendChild(modal);
            this.modalElement = modal;
            
            modal.querySelector('.confirm-yes').onclick = () => {
                this.close();
                resolve(true);
            };
            
            modal.querySelector('.confirm-no').onclick = () => {
                this.close();
                resolve(false);
            };
            
            // Close on overlay click
            modal.onclick = (e) => {
                if (e.target === modal) {
                    this.close();
                    resolve(false);
                }
            };
        });
    }

    // Close Modal
    close() {
        if (this.modalElement) {
            const box = this.modalElement.querySelector('.admin-modal-box');
            if (box) {
                box.classList.add('closing');
            }
            this.modalElement.style.animation = 'fadeOut 0.3s forwards';
            
            setTimeout(() => {
                if (this.modalElement) {
                    this.modalElement.remove();
                    this.modalElement = null;
                }
            }, 300);
        }
        
        // Callback
        if (this.callbacks.onComplete) {
            this.callbacks.onComplete();
            this.callbacks.onComplete = null;
        }
    }

    // Set Callbacks
    onSuccess(callback) {
        this.callbacks.onSuccess = callback;
        return this;
    }

    onError(callback) {
        this.callbacks.onError = callback;
        return this;
    }

    onComplete(callback) {
        this.callbacks.onComplete = callback;
        return this;
    }

    // Update Loading Message
    updateLoadingMessage(message, submessage = '') {
        if (this.modalElement) {
            const title = this.modalElement.querySelector('.admin-modal-title');
            const msg = this.modalElement.querySelector('.admin-modal-message');
            
            if (title) title.textContent = message;
            if (msg) msg.textContent = submessage;
        }
        return this;
    }

    // Process with states (loading -> success/error)
    async process(action, options = {}) {
        const {
            loadingMessage = 'Đang xử lý...',
            successMessage = 'Thành công!',
            errorMessage = 'Có lỗi xảy ra!',
            successDuration = 2000,
            autoReload = false,
            reloadDelay = 1500
        } = options;
        
        this.showLoading(loadingMessage);
        
        try {
            const result = await action();
            
            this.showSuccess(successMessage, '', {
                autoClose: true,
                duration: successDuration
            });
            
            if (autoReload) {
                setTimeout(() => location.reload(), reloadDelay);
            }
            
            return result;
        } catch (error) {
            this.showError(errorMessage, error.message || '', {
                showButton: true
            });
            throw error;
        }
    }
}

// Initialize global instance
const adminModal = new AdminModal();

// Export for global use
window.adminModal = adminModal;
window.AdminModal = AdminModal;
