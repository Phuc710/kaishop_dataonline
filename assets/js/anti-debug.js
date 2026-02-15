/**
 * Anti-Debug Protection
 * DISABLED - DevTools access is now allowed
 */

(function () {
    'use strict';

    // DISABLED: All DevTools detection and blocking has been commented out
    // to allow F12 and developer tools access

    /*
    // DevTools detection
    const devtools = {
        isOpen: false,
        orientation: undefined
    };

    const threshold = 160;

    const emitEvent = (isOpen, orientation) => {
        window.dispatchEvent(new CustomEvent('devtoolschange', {
            detail: { isOpen, orientation }
        }));
    };

    // Check DevTools status
    const checkDevTools = () => {
        const widthThreshold = window.outerWidth - window.innerWidth > threshold;
        const heightThreshold = window.outerHeight - window.innerHeight > threshold;
        const orientation = widthThreshold ? 'vertical' : 'horizontal';

        if (!(heightThreshold && widthThreshold) &&
            ((window.Firebug && window.Firebug.chrome && window.Firebug.chrome.isInitialized) ||
                widthThreshold || heightThreshold)) {
            if (!devtools.isOpen || devtools.orientation !== orientation) {
                emitEvent(true, orientation);
            }
            devtools.isOpen = true;
            devtools.orientation = orientation;
        } else {
            if (devtools.isOpen) {
                emitEvent(false, undefined);
            }
            devtools.isOpen = false;
            devtools.orientation = undefined;
        }
    };

    // Check every 500ms
    setInterval(checkDevTools, 500);

    // Debugger trap (aggressive)
    const debuggerTrap = () => {
        const start = performance.now();
        debugger;
        const end = performance.now();

        // If debugger paused execution, time difference will be significant
        if (end - start > 100) {
            window.location.href = 'about:blank';
        }
    };

    // Run debugger trap every 100ms
    setInterval(debuggerTrap, 100);

    // Override console methods
    const noop = () => { };
    const consoleMethods = ['log', 'debug', 'info', 'warn', 'error', 'table', 'trace', 'dir', 'dirxml', 'group', 'groupCollapsed', 'groupEnd', 'clear', 'count', 'countReset', 'assert', 'profile', 'profileEnd', 'time', 'timeLog', 'timeEnd', 'timeStamp'];

    consoleMethods.forEach(method => {
        if (console[method]) {
            console[method] = noop;
        }
    });

    // Action when DevTools detected
    window.addEventListener('devtoolschange', event => {
        if (event.detail.isOpen) {
            // Clear page content
            document.body.innerHTML = `
                <div style="display:flex;align-items:center;justify-content:center;height:100vh;background:#0f172a;color:#fff;font-family:system-ui;">
                    <div style="text-align:center;">
                        <h1 style="font-size:3rem;margin-bottom:1rem;">⚠️</h1>
                        <h2 style="margin-bottom:0.5rem;">Developer Tools Detected</h2>
                        <p style="color:#94a3b8;">This action has been logged.</p>
                    </div>
                </div>
            `;

            // Redirect after 2 seconds
            setTimeout(() => {
                window.location.href = 'about:blank';
            }, 2000);
        }
    });

    // Prevent toString override detection
    const originalToString = Function.prototype.toString;
    Function.prototype.toString = function () {
        if (this === checkDevTools || this === debuggerTrap) {
            return 'function () { [native code] }';
        }
        return originalToString.call(this);
    };

    // Detect Chrome DevTools via console.log timing
    let devtoolsOpen = false;
    const element = new Image();
    Object.defineProperty(element, 'id', {
        get: function () {
            devtoolsOpen = true;
            throw new Error('DevTools detected');
        }
    });

    setInterval(() => {
        devtoolsOpen = false;
        console.log(element);
        if (devtoolsOpen) {
            window.location.href = 'about:blank';
        }
    }, 1000);
    */

    // Prevent iframe embedding (keep this for security)
    if (window.top !== window.self) {
        window.top.location = window.self.location;
    }

})();
