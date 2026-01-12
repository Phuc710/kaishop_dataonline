class TicketChatClient {
    constructor(ticketId, userId, isAdmin, apiUrl = null) {
        const baseUrl = window.APP_CONFIG?.baseUrl || '';
        this.ticketId = ticketId;
        this.userId = userId;
        this.isAdmin = isAdmin;
        this.apiUrl = apiUrl || `${baseUrl}/ticket`;
        this.pollInterval = null;
        this.lastMessageId = 0;
        this.isPolling = false;
        this.messageContainer = null;
        this.form = null;
        this.textarea = null;
        this.submitBtn = null;
        this.pastedImageFile = null;
    }

    init() {
        this.messageContainer = document.getElementById('messagesList');
        this.form = document.getElementById('replyForm');
        this.textarea = document.getElementById('replyMessage');
        this.submitBtn = this.form?.querySelector('.btn-submit');

        if (!this.form || !this.textarea || !this.submitBtn) {
            return false;
        }

        this.setupEventListeners();
        this.getLastMessageId();
        this.startPolling();
        return true;
    }

    setupEventListeners() {
        this.form.addEventListener('submit', (e) => {
            e.preventDefault();
            this.sendMessage();
        });

        this.textarea.addEventListener('input', () => {
            this.textarea.style.height = 'auto';
            this.textarea.style.height = Math.min(this.textarea.scrollHeight, 120) + 'px';
        });

        this.textarea.addEventListener('keydown', (e) => {
            if (e.key === 'Enter' && !e.shiftKey) {
                e.preventDefault();
                this.form.dispatchEvent(new Event('submit'));
            }
        });

        this.textarea.addEventListener('paste', (e) => {
            const items = e.clipboardData?.items;
            if (!items) return;

            for (let i = 0; i < items.length; i++) {
                if (items[i].type.indexOf('image') !== -1) {
                    e.preventDefault();
                    const file = items[i].getAsFile();
                    if (file) this.handleImagePaste(file);
                    break;
                }
            }
        });

        window.addEventListener('beforeunload', () => this.stopPolling());
    }

    handleImagePaste(file) {
        if (file.size > 5242880) {
            if (window.notify) notify.error('Lỗi!', 'Ảnh quá lớn! Tối đa 5MB');
            return;
        }

        const reader = new FileReader();
        reader.onload = (e) => {
            const existing = this.form.querySelector('.image-paste-preview');
            if (existing) existing.remove();

            const preview = document.createElement('div');
            preview.className = 'image-paste-preview';
            preview.innerHTML = `
                <div style="margin: 0.5rem 0; padding: 0.5rem; background: rgba(99, 102, 241, 0.1); border-radius: 8px; border: 1px dashed rgba(99, 102, 241, 0.3);">
                    <img src="${e.target.result}" style="max-width: 200px; max-height: 150px; border-radius: 4px;" />
                    <div style="margin-top: 0.5rem; font-size: 0.75rem; color: var(--text-muted);">
                        📎 ${file.name} (${(file.size / 1024).toFixed(2)} KB)
                        <button onclick="this.closest('.image-paste-preview').remove(); window.ticketChat.pastedImageFile = null;" style="margin-left: 0.5rem; color: #ef4444; background: none; border: none; cursor: pointer;">Xóa</button>
                    </div>
                </div>
            `;
            this.pastedImageFile = file;
            this.form.insertBefore(preview, this.form.firstChild);
        };
        reader.readAsDataURL(file);
    }

    getLastMessageId() {
        const messages = this.messageContainer?.querySelectorAll('.message-item');
        if (messages && messages.length > 0) {
            this.lastMessageId = messages[messages.length - 1].dataset.messageId || 0;
        }
    }

    async sendMessage() {
        const message = this.textarea.value.trim();
        const hasImage = this.pastedImageFile;

        if (!message && !hasImage) return;

        this.submitBtn.disabled = true;
        const originalHTML = this.submitBtn.innerHTML;
        this.submitBtn.innerHTML = '<span class="loading-icon-inline"></span> Đang Gửi...';

        const formData = new FormData();
        formData.append('ticket_id', this.ticketId);
        formData.append('message', message || '');
        if (hasImage) formData.append('image', this.pastedImageFile);

        try {
            const response = await fetch(`${this.apiUrl}/send_message.php`, {
                method: 'POST',
                body: formData,
                credentials: 'same-origin'
            });

            const result = await response.json();

            if (result.success) {
                this.textarea.value = '';
                this.textarea.style.height = 'auto';

                const preview = this.form.querySelector('.image-paste-preview');
                if (preview) preview.remove();
                this.pastedImageFile = null;

                if (result.data) {
                    this.addMessageToUI(result.data);
                    this.lastMessageId = result.data.id;
                }
                this.scrollToBottom();
            } else {
                if (window.notify) notify.error('Lỗi!', result.message);
            }
        } catch (error) {
            if (window.notify) notify.error('Lỗi!', 'Không thể kết nối máy chủ');
        } finally {
            this.submitBtn.disabled = false;
            this.submitBtn.innerHTML = originalHTML;
        }
    }

    startPolling() {
        if (this.isPolling) return;
        this.isPolling = true;
        this.pollInterval = setInterval(() => this.checkNewMessages(), 1500);
    }

    stopPolling() {
        if (this.pollInterval) {
            clearInterval(this.pollInterval);
            this.pollInterval = null;
            this.isPolling = false;
        }
    }

    async checkNewMessages() {
        if (!this.isPolling) return;

        try {
            const response = await fetch(
                `${this.apiUrl}/get_messages.php?ticket_id=${this.ticketId}&last_id=${this.lastMessageId}`,
                { credentials: 'same-origin' }
            );

            if (!response.ok) return;

            const result = await response.json();

            if (result.success && result.messages && result.messages.length > 0) {
                result.messages.forEach(msg => {
                    if (!document.querySelector(`[data-message-id="${msg.id}"]`)) {
                        this.addMessageToUI(msg);
                        this.lastMessageId = msg.id;
                    }
                });

                if (this.isNearBottom()) this.scrollToBottom();
            }
        } catch (error) { }
    }

    addMessageToUI(msg) {
        if (document.querySelector(`[data-message-id="${msg.id}"]`)) return;

        const isAdmin = msg.is_admin == 1 || msg.role === 'admin';
        const displayName = msg.username || 'User';
        const baseUrl = window.APP_CONFIG?.baseUrl || '';
        let avatarImg;
        if (msg.avatar && (msg.avatar.startsWith('http') || msg.avatar.startsWith('//'))) {
            avatarImg = msg.avatar;
        } else if (msg.avatar) {
            avatarImg = `${baseUrl}/assets/images/uploads/${msg.avatar}`;
        } else {
            avatarImg = isAdmin ? `${baseUrl}/assets/images/admin.png` : `${baseUrl}/assets/images/user.png`;
        }
        const messageClass = isAdmin ? 'admin-message' : 'user-message';

        const messageHTML = `
            <div class="message-item ${messageClass}" data-message-id="${msg.id}" data-user-id="${msg.user_id}">
                <div class="message-avatar">
                    <img src="${avatarImg}" alt="${isAdmin ? 'Admin' : 'User'}">
                </div>
                <div class="message-content">
                    <div class="message-header">
                        ${isAdmin ? '<span class="message-admin-badge">ADMIN</span>' : `<span class="message-author">${this.escapeHtml(displayName)}</span>`}
                    </div>
                    <div style="display: flex; align-items: center; gap: 0.25rem;">
                        <div class="message-bubble">
                            ${msg.message ? this.escapeHtml(msg.message).replace(/\n/g, '<br>') : ''}
                            ${msg.image ? `<img src="${baseUrl}/${msg.image}" alt="Image" onclick="openLightbox('${baseUrl}/${msg.image}')" style="display: block; margin-top: ${msg.message ? '0.5rem' : '0'}; cursor: pointer;">` : ''}
                        </div>
                        ${this.isAdmin ? `
                        <div class="message-actions">
                            <button class="message-action-btn delete" onclick="deleteMessage('${msg.id}')" title="Xóa tin nhắn">
                                <i class="fas fa-trash"></i>
                            </button>
                        </div>
                        ` : ''}
                    </div>
                    <div class="message-time">${this.formatDateTime(msg.created_at)}</div>
                </div>
            </div>
        `;

        if (this.messageContainer) {
            const emptyState = this.messageContainer.querySelector('.empty-messages');
            if (emptyState) emptyState.remove();

            const tempDiv = document.createElement('div');
            tempDiv.innerHTML = messageHTML.trim();
            this.messageContainer.appendChild(tempDiv.firstChild);
        }
    }

    scrollToBottom() {
        if (this.messageContainer) {
            this.messageContainer.scrollTop = this.messageContainer.scrollHeight;
        }
    }

    isNearBottom() {
        if (!this.messageContainer) return true;
        return this.messageContainer.scrollHeight - this.messageContainer.scrollTop - this.messageContainer.clientHeight < 100;
    }

    formatDateTime(timestamp) {
        const date = new Date(timestamp);
        const day = String(date.getDate()).padStart(2, '0');
        const month = String(date.getMonth() + 1).padStart(2, '0');
        const year = date.getFullYear();
        const hours = String(date.getHours()).padStart(2, '0');
        const minutes = String(date.getMinutes()).padStart(2, '0');
        return `${day}/${month}/${year} ${hours}:${minutes}`;
    }

    escapeHtml(text) {
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }

    destroy() {
        this.stopPolling();
    }
}

window.TicketChatClient = TicketChatClient;
