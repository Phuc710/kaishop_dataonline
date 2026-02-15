/**
 * Product Image Carousel
 * Features: Navigation, Keyboard, Swipe Support, Infinite Loop
 * No Auto-play as requested
 */
class ProductCarousel {
    constructor(container) {
        this.container = container;
        this.track = container.querySelector('.carousel-track');
        this.slides = container.querySelectorAll('.carousel-slide');
        this.prevBtn = container.querySelector('.carousel-btn.prev');
        this.nextBtn = container.querySelector('.carousel-btn.next');
        this.dotsContainer = container.querySelector('.carousel-dots');
        this.counter = container.querySelector('.carousel-counter');
        // Find thumbs in parent since they're outside carousel
        this.thumbsContainer = container.parentElement.querySelector('.carousel-thumbs');

        this.currentIndex = 0;
        this.slideCount = this.slides.length;
        this.isDragging = false;
        this.startX = 0;
        this.currentX = 0;
        this.dragOffset = 0;

        if (this.slideCount <= 1) {
            this.hideControls();
            return;
        }

        this.init();
    }

    init() {
        // Navigation buttons
        if (this.prevBtn) {
            this.prevBtn.addEventListener('click', () => this.prev());
        }
        if (this.nextBtn) {
            this.nextBtn.addEventListener('click', () => this.next());
        }

        // Dots
        if (this.dotsContainer) {
            this.createDots();
        }

        // Thumbnails
        if (this.thumbsContainer) {
            this.setupThumbnails();
        }

        // Keyboard navigation
        document.addEventListener('keydown', (e) => {
            if (!this.isInViewport()) return;
            if (e.key === 'ArrowLeft') this.prev();
            if (e.key === 'ArrowRight') this.next();
        });

        // Touch/Drag support
        this.setupDragSupport();

        // Initial state
        this.updateUI();
    }

    createDots() {
        this.dotsContainer.innerHTML = '';
        for (let i = 0; i < this.slideCount; i++) {
            const dot = document.createElement('button');
            dot.className = 'carousel-dot' + (i === 0 ? ' active' : '');
            dot.setAttribute('aria-label', `Slide ${i + 1}`);
            dot.addEventListener('click', () => this.goTo(i));
            this.dotsContainer.appendChild(dot);
        }
        this.dots = this.dotsContainer.querySelectorAll('.carousel-dot');
    }

    setupThumbnails() {
        const thumbs = this.thumbsContainer.querySelectorAll('.carousel-thumb');
        const self = this;
        thumbs.forEach((thumb, idx) => {
            thumb.addEventListener('click', function (e) {
                e.preventDefault();
                self.goTo(idx);
            });
        });
        this.thumbs = thumbs;
    }

    setupDragSupport() {
        // Mouse events
        this.track.addEventListener('mousedown', (e) => this.startDrag(e));
        document.addEventListener('mousemove', (e) => this.drag(e));
        document.addEventListener('mouseup', () => this.endDrag());

        // Touch events
        this.track.addEventListener('touchstart', (e) => this.startDrag(e), { passive: true });
        this.track.addEventListener('touchmove', (e) => this.drag(e), { passive: true });
        this.track.addEventListener('touchend', () => this.endDrag());
    }

    startDrag(e) {
        this.isDragging = true;
        this.startX = e.type === 'touchstart' ? e.touches[0].clientX : e.clientX;
        this.track.classList.add('dragging');
    }

    drag(e) {
        if (!this.isDragging) return;

        this.currentX = e.type === 'touchmove' ? e.touches[0].clientX : e.clientX;
        this.dragOffset = this.currentX - this.startX;

        const baseOffset = -this.currentIndex * 100;
        const dragPercent = (this.dragOffset / this.container.offsetWidth) * 100;
        this.track.style.transform = `translateX(${baseOffset + dragPercent}%)`;
    }

    endDrag() {
        if (!this.isDragging) return;
        this.isDragging = false;
        this.track.classList.remove('dragging');

        const threshold = 50; // pixels

        if (Math.abs(this.dragOffset) > threshold) {
            if (this.dragOffset > 0) {
                this.prev();
            } else {
                this.next();
            }
        } else {
            this.goTo(this.currentIndex);
        }

        this.dragOffset = 0;
    }

    prev() {
        // Infinite loop
        const newIndex = this.currentIndex === 0 ? this.slideCount - 1 : this.currentIndex - 1;
        this.goTo(newIndex);
    }

    next() {
        // Infinite loop
        const newIndex = this.currentIndex === this.slideCount - 1 ? 0 : this.currentIndex + 1;
        this.goTo(newIndex);
    }

    goTo(index) {
        if (index < 0) index = 0;
        if (index >= this.slideCount) index = this.slideCount - 1;

        this.currentIndex = index;
        this.track.style.transform = `translateX(-${index * 100}%)`;
        this.updateUI();
    }

    updateUI() {
        // Update dots
        if (this.dots) {
            this.dots.forEach((dot, idx) => {
                dot.classList.toggle('active', idx === this.currentIndex);
            });
        }

        // Update thumbnails
        if (this.thumbs) {
            this.thumbs.forEach((thumb, idx) => {
                thumb.classList.toggle('active', idx === this.currentIndex);
            });
        }

        // Update counter
        if (this.counter) {
            this.counter.textContent = `${this.currentIndex + 1} / ${this.slideCount}`;
        }
    }

    hideControls() {
        if (this.prevBtn) this.prevBtn.style.display = 'none';
        if (this.nextBtn) this.nextBtn.style.display = 'none';
        if (this.dotsContainer) this.dotsContainer.style.display = 'none';
        if (this.counter) this.counter.style.display = 'none';
    }

    isInViewport() {
        const rect = this.container.getBoundingClientRect();
        return rect.top < window.innerHeight && rect.bottom > 0;
    }
}

// Auto-initialize carousels
document.addEventListener('DOMContentLoaded', function () {
    document.querySelectorAll('.product-carousel').forEach(carousel => {
        new ProductCarousel(carousel);
    });
});
