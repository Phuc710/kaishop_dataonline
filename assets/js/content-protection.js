
(function () {
    'use strict';

    // Disable right-click on images only
    document.addEventListener('contextmenu', function (e) {
        if (e.target.tagName === 'IMG') {
            e.preventDefault();
            return false;
        }
    });

    // Disable image dragging
    document.addEventListener('dragstart', function (e) {
        if (e.target.tagName === 'IMG') {
            e.preventDefault();
            return false;
        }
    });

    // Prevent image selection
    document.addEventListener('DOMContentLoaded', function () {
        const images = document.querySelectorAll('img');
        images.forEach(img => {
            img.setAttribute('draggable', 'false');
            img.style.userSelect = 'none';
            img.style.webkitUserSelect = 'none';
            img.style.mozUserSelect = 'none';
            img.style.msUserSelect = 'none';
            img.style.pointerEvents = 'auto'; // Keep clickable
        });
    });

})();
