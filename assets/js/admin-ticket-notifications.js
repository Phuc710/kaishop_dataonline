/**
 * Admin Ticket Notifications
 * Auto-refresh ticket count and show notifications for new tickets
 */

class AdminTicketNotifications {
    constructor() {
        this.lastTicketCount = 0;
        this.checkInterval = 30000; // Check every 30 seconds
        this.audioEnabled = true;
        this.notificationSound = null;
        
        this.init();
    }
    
    init() {
        // Get initial count from badge
        const badge = document.querySelector('.ticket-count-badge');
        if (badge) {
            this.lastTicketCount = parseInt(badge.textContent) || 0;
        }
        
        // Create notification sound (optional)
        this.createNotificationSound();
        
        // Start checking
        this.startAutoCheck();
        
        // Check immediately on page load
        this.checkForNewTickets();
    }
    
    createNotificationSound() {
        // Create a simple beep sound using Web Audio API
        try {
            this.audioContext = new (window.AudioContext || window.webkitAudioContext)();
        } catch (e) {
            console.log('Web Audio API not supported');
        }
    }
    
    playNotificationSound() {
        if (!this.audioEnabled || !this.audioContext) return;
        
        try {
            const oscillator = this.audioContext.createOscillator();
            const gainNode = this.audioContext.createGain();
            
            oscillator.connect(gainNode);
            gainNode.connect(this.audioContext.destination);
            
            oscillator.frequency.value = 800;
            oscillator.type = 'sine';
            
            gainNode.gain.setValueAtTime(0.3, this.audioContext.currentTime);
            gainNode.gain.exponentialRampToValueAtTime(0.01, this.audioContext.currentTime + 0.5);
            
            oscillator.start(this.audioContext.currentTime);
            oscillator.stop(this.audioContext.currentTime + 0.5);
        } catch (e) {
            console.log('Error playing sound:', e);
        }
    }
    
    async checkForNewTickets() {
        try {
            const response = await fetch(`${window.API_URL}/admin-notifications.php?action=check_tickets`);
            const data = await response.json();
            
            if (data.success) {
                this.updateBadge(data.count);
                
                // Check if there are new tickets
                if (data.count > this.lastTicketCount && data.has_new) {
                    this.showNewTicketNotification(data.recent_tickets);
                    this.playNotificationSound();
                }
                
                this.lastTicketCount = data.count;
            }
        } catch (error) {
            console.error('Error checking tickets:', error);
        }
    }
    
    updateBadge(count) {
        const badge = document.querySelector('.ticket-count-badge');
        if (badge) {
            badge.textContent = count;
            
            // Highlight badge if there are tickets
            if (count > 0) {
                badge.style.background = 'linear-gradient(135deg, #ef4444, #dc2626)';
                badge.style.animation = 'pulse 2s infinite';
            } else {
                badge.style.background = 'rgba(139, 92, 246, 0.2)';
                badge.style.animation = 'none';
            }
        }
    }
    
    showNewTicketNotification(tickets) {
        if (!tickets || tickets.length === 0) return;
        
        const ticket = tickets[0]; // Show latest ticket
        
        if (window.notify) {
            notify.info('🎫 Ticket Mới!', 
                `<strong>${ticket.username}</strong> vừa tạo ticket mới:<br>
                <em>"${this.truncate(ticket.subject, 50)}"</em><br>
                <small>Mã: ${ticket.ticket_number}</small>`, 
                {
                    duration: 8000,
                    onClick: () => {
                        window.location.href = '?tab=notifications&subtab=tickets';
                    }
                }
            );
        }
        
        // Browser notification (if permitted)
        this.showBrowserNotification(ticket);
    }
    
    showBrowserNotification(ticket) {
        if ('Notification' in window && Notification.permission === 'granted') {
            const notification = new Notification('🎫 Ticket Mới - ' + ticket.username, {
                body: ticket.subject,
                icon: `${window.APP_URL}/assets/images/logo.png`,
                badge: `${window.APP_URL}/assets/images/logo.png`,
                tag: 'new-ticket-' + ticket.id,
                requireInteraction: false
            });
            
            notification.onclick = () => {
                window.focus();
                window.location.href = '?tab=notifications&subtab=tickets';
                notification.close();
            };
            
            setTimeout(() => notification.close(), 10000);
        } else if ('Notification' in window && Notification.permission === 'default') {
            // Request permission
            Notification.requestPermission();
        }
    }
    
    truncate(str, length) {
        if (str.length <= length) return str;
        return str.substring(0, length) + '...';
    }
    
    startAutoCheck() {
        setInterval(() => {
            this.checkForNewTickets();
        }, this.checkInterval);
    }
    
    toggleSound(enabled) {
        this.audioEnabled = enabled;
        localStorage.setItem('admin_ticket_sound', enabled ? '1' : '0');
    }
}

// Initialize when DOM is ready
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', () => {
        window.adminTicketNotifications = new AdminTicketNotifications();
    });
} else {
    window.adminTicketNotifications = new AdminTicketNotifications();
}

// Add pulse animation for badge
const style = document.createElement('style');
style.textContent = `
    @keyframes pulse {
        0%, 100% { transform: scale(1); opacity: 1; }
        50% { transform: scale(1.05); opacity: 0.9; }
    }
    
    .ticket-count-badge {
        display: inline-block;
        padding: 0.2rem 0.6rem;
        border-radius: 12px;
        font-size: 0.75rem;
        font-weight: 700;
        min-width: 20px;
        text-align: center;
        margin-left: 0.5rem;
        transition: all 0.3s ease;
    }
`;
document.head.appendChild(style);
