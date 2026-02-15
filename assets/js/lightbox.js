/**
 * Image Lightbox for Carousel
 * Click on carousel image to view fullscreen with navigation
 */

class ImageLightbox {
    constructor() {
        this.lightbox = null;
        this.currentImage = null;
        this.allImages = [];
        this.currentIndex = 0;
        this.init();
    }

    init() {
        // Create lightbox HTML
        this.createLightbox();

        // Add click listeners to carousel images
        document.addEventListener('DOMContentLoaded', () => {
            this.attachListeners();
        });
    }

    createLightbox() {
        const lightboxHTML = `
            <div class="image-lightbox" id="imageLightbox">
                <button class="lightbox-close" aria-label="Close">
                    <i class="fas fa-times"></i>
                </button>
                <button class="lightbox-nav lightbox-prev" aria-label="Previous image">
                    <i class="fas fa-chevron-left"></i>
                </button>
                <button class="lightbox-nav lightbox-next" aria-label="Next image">
                    <i class="fas fa-chevron-right"></i>
                </button>
                <div class="lightbox-content">
                    <img src="" alt="" class="lightbox-image" id="lightboxImage">
                </div>
                <div class="lightbox-counter"></div>
            </div>
        `;

        document.body.insertAdjacentHTML('beforeend', lightboxHTML);
        this.lightbox = document.getElementById('imageLightbox');
        this.lightboxImage = document.getElementById('lightboxImage');
        this.counter = this.lightbox.querySelector('.lightbox-counter');
        this.prevBtn = this.lightbox.querySelector('.lightbox-prev');
        this.nextBtn = this.lightbox.querySelector('.lightbox-next');

        // Close button
        this.lightbox.querySelector('.lightbox-close').addEventListener('click', () => {
            this.close();
        });

        // Navigation buttons
        this.prevBtn.addEventListener('click', (e) => {
            e.stopPropagation();
            this.prev();
        });

        this.nextBtn.addEventListener('click', (e) => {
            e.stopPropagation();
            this.next();
        });

        // Click outside to close
        this.lightbox.addEventListener('click', (e) => {
            if (e.target === this.lightbox) {
                this.close();
            }
        });

        // Keyboard navigation
        document.addEventListener('keydown', (e) => {
            if (!this.lightbox.classList.contains('active')) return;

            if (e.key === 'Escape') {
                this.close();
            } else if (e.key === 'ArrowLeft') {
                this.prev();
            } else if (e.key === 'ArrowRight') {
                this.next();
            }
        });
    }

    attachListeners() {
        // Get all carousel slide images
        const carouselImages = document.querySelectorAll('.carousel-slide img');
        this.allImages = Array.from(carouselImages);

        carouselImages.forEach((img, index) => {
            img.style.cursor = 'pointer';
            img.addEventListener('click', (e) => {
                e.stopPropagation();
                this.open(index);
            });
        });
    }

    open(index) {
        this.currentIndex = index;
        this.updateImage();
        this.lightbox.classList.add('active');
        document.body.style.overflow = 'hidden';
    }

    close() {
        this.lightbox.classList.remove('active');
        document.body.style.overflow = '';
    }

    prev() {
        this.currentIndex = this.currentIndex === 0 ? this.allImages.length - 1 : this.currentIndex - 1;
        this.updateImage();
    }

    next() {
        this.currentIndex = this.currentIndex === this.allImages.length - 1 ? 0 : this.currentIndex + 1;
        this.updateImage();
    }

    updateImage() {
        const img = this.allImages[this.currentIndex];
        this.lightboxImage.src = img.src;
        this.lightboxImage.alt = img.alt;
        this.counter.textContent = `${this.currentIndex + 1} / ${this.allImages.length}`;

        // Hide nav buttons if only one image
        if (this.allImages.length <= 1) {
            this.prevBtn.style.display = 'none';
            this.nextBtn.style.display = 'none';
            this.counter.style.display = 'none';
        }
    }
}

// Initialize lightbox
new ImageLightbox();
