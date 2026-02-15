/**
 * Real-time Ban Detection Script
 */

(function () {
    // Only run for logged-in users (check if user session exists)
    if (!document.body.dataset.userId) return;

    let banCheckInterval;
    let consecutiveErrors = 0;
    const MAX_ERRORS = 3;
    const POLL_INTERVAL = 10000;

    function checkBanStatus() {
        if (document.hidden) return;

        fetch(`${window.APP_CONFIG.baseUrl}/api/check_ban_status.php`, {
            method: 'GET',
            credentials: 'same-origin',
            cache: 'no-cache'
        })
            .then(response => response.json())
            .then(data => {
                consecutiveErrors = 0; // Reset error counter on success

                if (data.banned) {
                    // User has been banned - force logout
                    clearInterval(banCheckInterval);

                    // Show ban message
                    alert(`🚫 YOUR ACCOUNT HAS BEEN BANNED\n\n${data.reason || 'Your account has been locked by an administrator'}\n\nYou will be logged out immediately.`);

                    // Force logout
                    window.location.href = `${window.APP_CONFIG.baseUrl}/auth/logout.php?reason=banned`;
                }
            })
            .catch(error => {
                consecutiveErrors++;
                console.error('error:', error);

                // Stop checking after too many errors to avoid spam
                if (consecutiveErrors >= MAX_ERRORS) {
                    clearInterval(banCheckInterval);
                    console.warn('disabled due to repeated errors');
                }
            });
    }

    // Check immediately on page load
    checkBanStatus();

    banCheckInterval = setInterval(checkBanStatus, POLL_INTERVAL);

    // Also check when page becomes visible (user switches back to tab)
    document.addEventListener('visibilitychange', function () {
        if (!document.hidden) {
            checkBanStatus();
        }
    });
})();
