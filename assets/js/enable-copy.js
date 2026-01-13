/**
 * Enable Text Copying Script
 * Allows users to copy text content on all pages
 * Overrides any copy protection scripts
 */

(function () {
    'use strict';

    // Enable text selection globally
    document.addEventListener('DOMContentLoaded', function () {
        // Remove any user-select restrictions
        const style = document.createElement('style');
        style.textContent = `
            * {
                -webkit-user-select: text !important;
                -moz-user-select: text !important;
                -ms-user-select: text !important;
                user-select: text !important;
            }
            
            /* Keep images non-selectable */
            img {
                -webkit-user-select: none !important;
                -moz-user-select: none !important;
                -ms-user-select: none !important;
                user-select: none !important;
            }
        `;
        document.head.appendChild(style);
    });

    // Override any copy prevention
    const enableCopy = function (e) {
        e.stopPropagation();
        return true;
    };

    // Enable copy event
    document.addEventListener('copy', enableCopy, true);

    // Enable select event
    document.addEventListener('selectstart', enableCopy, true);

    // Remove any existing copy prevention listeners
    window.addEventListener('load', function () {
        // Remove user-select: none from all elements except images
        document.querySelectorAll('*:not(img)').forEach(element => {
            element.style.userSelect = 'text';
            element.style.webkitUserSelect = 'text';
            element.style.mozUserSelect = 'text';
            element.style.msUserSelect = 'text';
        });
    });

    // Disable any external copy protection scripts
    Object.defineProperty(document, 'oncopy', {
        get: function () { return null; },
        set: function () { }
    });

    Object.defineProperty(document, 'onselectstart', {
        get: function () { return null; },
        set: function () { }
    });

    Object.defineProperty(document, 'oncontextmenu', {
        get: function () { return null; },
        set: function () { }
    });
})();
