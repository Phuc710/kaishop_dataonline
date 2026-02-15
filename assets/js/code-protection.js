/**
 * Code Protection System
 * Protects against code inspection via DevTools
 */

(function () {
    'use strict';

    // Disable right-click
    document.addEventListener('contextmenu', function (e) {
        e.preventDefault();
        return false;
    }, false);

    // DISABLED: Allow F12 and DevTools
    // Disable F12, Ctrl+Shift+I, Ctrl+Shift+J, Ctrl+U, Ctrl+S
    // document.addEventListener('keydown', function (e) {
    //     // F12
    //     if (e.keyCode === 123) {
    //         e.preventDefault();
    //         return false;
    //     }
    //     // Ctrl+Shift+I (Inspect)
    //     if (e.ctrlKey && e.shiftKey && e.keyCode === 73) {
    //         e.preventDefault();
    //         return false;
    //     }
    //     // Ctrl+Shift+J (Console)
    //     if (e.ctrlKey && e.shiftKey && e.keyCode === 74) {
    //         e.preventDefault();
    //         return false;
    //     }
    //     // Ctrl+Shift+C (Inspect Element)
    //     if (e.ctrlKey && e.shiftKey && e.keyCode === 67) {
    //         e.preventDefault();
    //         return false;
    //     }
    //     // Ctrl+U (View Source)
    //     if (e.ctrlKey && e.keyCode === 85) {
    //         e.preventDefault();
    //         return false;
    //     }
    //     // Ctrl+S (Save Page)
    //     if (e.ctrlKey && e.keyCode === 83) {
    //         e.preventDefault();
    //         return false;
    //     }
    // }, false);

    // Disable text selection
    document.addEventListener('selectstart', function (e) {
        e.preventDefault();
        return false;
    }, false);

    // Disable copy
    document.addEventListener('copy', function (e) {
        e.preventDefault();
        return false;
    }, false);

    // Disable cut
    document.addEventListener('cut', function (e) {
        e.preventDefault();
        return false;
    }, false);

    // DISABLED: DevTools detection
    // let devtoolsOpen = false;
    // const threshold = 200;
    // let devtoolsCheckCount = 0;

    // if (window.innerWidth >= 768) {
    //     setInterval(function () {
    //         const widthThreshold = window.outerWidth - window.innerWidth > threshold;
    //         const heightThreshold = window.outerHeight - window.innerHeight > threshold;

    //         if (widthThreshold || heightThreshold) {
    //             devtoolsCheckCount++;
    //             if (devtoolsCheckCount >= 3 && !devtoolsOpen) {
    //                 devtoolsOpen = true;
    //                 handleDevToolsOpen();
    //             }
    //         } else {
    //             devtoolsCheckCount = 0;
    //             devtoolsOpen = false;
    //         }
    //     }, 1000);
    // }

    // function handleDevToolsOpen() {
    //     document.body.innerHTML = '<div style="display:flex;align-items:center;justify-content:center;height:100vh;background:#0f172a;color:#fff;font-family:sans-serif;text-align:center;"><div><h1 style="font-size:3rem;margin-bottom:1rem;">⚠️ Cảnh báo</h1><p style="font-size:1.2rem;">Vui lòng đóng DevTools để tiếp tục sử dụng.</p><p style="margin-top:1rem;color:#64748b;">Please close DevTools to continue.</p></div></div>';
    // }

    // DISABLED: Debugger trap
    // if (window.location.hostname !== 'localhost' && window.location.hostname !== '127.0.0.1') {
    //     setInterval(function () {
    //         debugger;
    //     }, 2000);
    // }

    // Console protection
    if (typeof console !== 'undefined') {
        const noop = function () { };
        ['log', 'debug', 'info', 'warn', 'error', 'dir', 'trace', 'assert', 'clear'].forEach(function (method) {
            console[method] = noop;
        });
    }

    // Prevent iframe embedding
    if (window.top !== window.self) {
        window.top.location = window.self.location;
    }

})();
