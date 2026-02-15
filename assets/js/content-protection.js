/**
 * Enhanced Content Protection
 * Prevents copying, screenshots, and content theft
 */

(function () {
    'use strict';

    // Disable right-click context menu
    document.addEventListener('contextmenu', e => {
        e.preventDefault();
        return false;
    });

    // Disable keyboard shortcuts
    document.addEventListener('keydown', e => {
        // DISABLED: Allow F12 and DevTools access
        // F12 - DevTools
        // if (e.key === 'F12') {
        //     e.preventDefault();
        //     return false;
        // }

        // DISABLED: Allow DevTools
        // Ctrl+Shift+I - DevTools
        // if (e.ctrlKey && e.shiftKey && e.key === 'I') {
        //     e.preventDefault();
        //     return false;
        // }

        // DISABLED: Allow Console
        // Ctrl+Shift+J - Console
        // if (e.ctrlKey && e.shiftKey && e.key === 'J') {
        //     e.preventDefault();
        //     return false;
        // }

        // DISABLED: Allow Inspect Element
        // Ctrl+Shift+C - Inspect Element
        // if (e.ctrlKey && e.shiftKey && e.key === 'C') {
        //     e.preventDefault();
        //     return false;
        // }

        // Ctrl+U - View Source
        if (e.ctrlKey && e.key === 'u') {
            e.preventDefault();
            return false;
        }

        // Ctrl+S - Save Page
        if (e.ctrlKey && e.key === 's') {
            e.preventDefault();
            return false;
        }

        // Ctrl+P - Print
        if (e.ctrlKey && e.key === 'p') {
            e.preventDefault();
            return false;
        }

        // Ctrl+C - Copy (optional, might affect user experience)
        // if (e.ctrlKey && e.key === 'c') {
        //     e.preventDefault();
        //     return false;
        // }
    });

    // Disable text selection
    document.addEventListener('selectstart', e => {
        e.preventDefault();
        return false;
    });

    // Disable copy
    document.addEventListener('copy', e => {
        e.clipboardData.setData('text/plain', 'Copying is disabled on this website.');
        e.preventDefault();
        return false;
    });

    // Disable cut
    document.addEventListener('cut', e => {
        e.preventDefault();
        return false;
    });

    // Disable paste (optional)
    // document.addEventListener('paste', e => {
    //     e.preventDefault();
    //     return false;
    // });

    // Disable drag
    document.addEventListener('dragstart', e => {
        e.preventDefault();
        return false;
    });

    // Screenshot detection (Print Screen key)
    document.addEventListener('keyup', e => {
        if (e.key === 'PrintScreen') {
            navigator.clipboard.writeText('');
            // Optional: Show warning
            // alert('Screenshots are disabled on this website.');
        }
    });

    // Detect screenshot via Ctrl+Shift+S (Firefox)
    document.addEventListener('keydown', e => {
        if (e.ctrlKey && e.shiftKey && e.key === 'S') {
            e.preventDefault();
            return false;
        }
    });

    // Watermark overlay (optional - makes screenshots less useful)
    const createWatermark = () => {
        const watermark = document.createElement('div');
        watermark.style.cssText = `
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            pointer-events: none;
            z-index: 9999;
            background-image: repeating-linear-gradient(
                45deg,
                transparent,
                transparent 100px,
                rgba(139, 92, 246, 0.03) 100px,
                rgba(139, 92, 246, 0.03) 200px
            );
        `;
        document.body.appendChild(watermark);
    };

    // Uncomment to enable watermark
    // if (document.readyState === 'loading') {
    //     document.addEventListener('DOMContentLoaded', createWatermark);
    // } else {
    //     createWatermark();
    // }

    // Disable image dragging
    document.addEventListener('DOMContentLoaded', () => {
        const images = document.getElementsByTagName('img');
        for (let img of images) {
            img.addEventListener('dragstart', e => {
                e.preventDefault();
                return false;
            });

            // Disable right-click on images specifically
            img.addEventListener('contextmenu', e => {
                e.preventDefault();
                return false;
            });
        }
    });

    // Detect if page is being viewed in iframe (clickjacking protection)
    if (window.top !== window.self) {
        window.top.location = window.self.location;
    }

    // Blur detection (user switched tabs - might be taking screenshot)
    let blurCount = 0;
    window.addEventListener('blur', () => {
        blurCount++;
        // Optional: Log suspicious activity
        // console.log('Window blur detected:', blurCount);
    });

    // Visibility change detection
    document.addEventListener('visibilitychange', () => {
        if (document.hidden) {
            // Page is hidden - user might be taking screenshot
            // Optional: Log or take action
        }
    });

    // Prevent opening in new window with DevTools
    window.addEventListener('beforeunload', e => {
        if (window.outerWidth - window.innerWidth > 160 ||
            window.outerHeight - window.innerHeight > 160) {
            e.preventDefault();
            e.returnValue = '';
        }
    });

})();

// Add CSS protection
(function () {
    const protectionStyle = document.createElement('style');
    protectionStyle.textContent = `
        /* Disable text selection */
        * {
            -webkit-user-select: none !important;
            -moz-user-select: none !important;
            -ms-user-select: none !important;
            user-select: none !important;
        }

        /* Allow selection for input fields */
        input, textarea {
            -webkit-user-select: text !important;
            -moz-user-select: text !important;
            -ms-user-select: text !important;
            user-select: text !important;
        }

        /* Disable image dragging */
        img {
            -webkit-user-drag: none !important;
            -khtml-user-drag: none !important;
            -moz-user-drag: none !important;
            -o-user-drag: none !important;
            user-drag: none !important;
        }

        /* Re-enable pointer events for clickable images */
        a img, button img, .carousel-thumb img, .carousel-thumbs img {
            pointer-events: auto !important;
        }
    `;
    document.head.appendChild(protectionStyle);
})();
