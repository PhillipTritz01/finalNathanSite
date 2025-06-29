/**
 * Performance Optimizer for Photography CMS
 * Handles lazy loading, image optimization, and loading improvements
 */

class PerformanceOptimizer {
    constructor() {
        this.init();
    }

    init() {
        this.setupLazyLoading();
        this.preloadCriticalImages();
        this.optimizeScrollPerformance();
        this.setupImageErrorHandling();
    }

    // Lazy Loading with Intersection Observer
    setupLazyLoading() {
        if ('IntersectionObserver' in window) {
            const lazyImageObserver = new IntersectionObserver((entries, observer) => {
                entries.forEach(entry => {
                    if (entry.isIntersecting) {
                        const img = entry.target;
                        this.loadImage(img);
                        observer.unobserve(img);
                    }
                });
            }, {
                rootMargin: '50px 0px',
                threshold: 0.01
            });

            // Observe all lazy images
            document.querySelectorAll('img[data-src]').forEach(img => {
                lazyImageObserver.observe(img);
            });
        } else {
            // Fallback for older browsers
            this.loadAllImages();
        }
    }

    // Load image with WebP support
    loadImage(img) {
        const webpSrc = img.dataset.webp;
        const fallbackSrc = img.dataset.src;
        
        if (webpSrc && this.supportsWebP()) {
            img.src = webpSrc;
        } else {
            img.src = fallbackSrc;
        }
        
        img.classList.add('loading');
        
        img.onload = () => {
            img.classList.remove('loading');
            img.classList.add('loaded');
        };
        
        img.onerror = () => {
            img.classList.remove('loading');
            if (webpSrc && img.src === webpSrc) {
                // WebP failed, try fallback
                img.src = fallbackSrc;
            }
        };
    }

    // Check WebP support
    supportsWebP() {
        if (this.webpSupport !== undefined) {
            return this.webpSupport;
        }
        
        const canvas = document.createElement('canvas');
        canvas.width = 1;
        canvas.height = 1;
        
        this.webpSupport = canvas.toDataURL('image/webp').indexOf('image/webp') === 5;
        return this.webpSupport;
    }

    // Preload critical above-fold images
    preloadCriticalImages() {
        const criticalImages = document.querySelectorAll('.hero img, .slideshow img');
        criticalImages.forEach(img => {
            if (img.dataset.src) {
                this.loadImage(img);
            }
        });
    }

    // Throttle scroll events for better performance
    optimizeScrollPerformance() {
        let ticking = false;
        
        function updateScrollPosition() {
            // Add scroll-based optimizations here
            ticking = false;
        }
        
        function requestTick() {
            if (!ticking) {
                requestAnimationFrame(updateScrollPosition);
                ticking = true;
            }
        }
        
        window.addEventListener('scroll', requestTick, { passive: true });
    }

    // Handle image loading errors gracefully
    setupImageErrorHandling() {
        document.addEventListener('error', (e) => {
            if (e.target.tagName === 'IMG') {
                const img = e.target;
                
                // Try thumbnail if main image fails
                if (img.dataset.thumbnail && !img.src.includes('thumb_')) {
                    img.src = img.dataset.thumbnail;
                    return;
                }
                
                // Show placeholder
                img.style.display = 'none';
                
                // Create placeholder div
                const placeholder = document.createElement('div');
                placeholder.className = 'image-placeholder';
                placeholder.innerHTML = '📷 Image unavailable';
                placeholder.style.cssText = `
                    display: flex;
                    align-items: center;
                    justify-content: center;
                    background: #f0f0f0;
                    color: #999;
                    min-height: 200px;
                    border-radius: 8px;
                `;
                
                img.parentNode.insertBefore(placeholder, img.nextSibling);
            }
        }, true);
    }

    // Load all images (fallback)
    loadAllImages() {
        document.querySelectorAll('img[data-src]').forEach(img => {
            this.loadImage(img);
        });
    }

    // Preload next page images (for pagination)
    preloadNextPage(nextPageUrl) {
        const link = document.createElement('link');
        link.rel = 'prefetch';
        link.href = nextPageUrl;
        document.head.appendChild(link);
    }

    // Optimize images that are already loaded
    optimizeLoadedImages() {
        const images = document.querySelectorAll('img:not([data-src])');
        images.forEach(img => {
            // Add loading attribute for native lazy loading support
            if ('loading' in HTMLImageElement.prototype) {
                img.loading = 'lazy';
            }
            
            // Add decode hint for better performance
            if ('decoding' in img) {
                img.decoding = 'async';
            }
        });
    }
}

// CSS for image loading states
const performanceCSS = `
    img.loading {
        opacity: 0.3;
        transition: opacity 0.3s ease;
    }
    
    img.loaded {
        opacity: 1;
    }
    
    .image-placeholder {
        animation: pulse 1.5s ease-in-out infinite alternate;
    }
    
    @keyframes pulse {
        0% { opacity: 0.6; }
        100% { opacity: 1; }
    }
    
    /* Optimize gallery performance */
    .gallery-grid {
        contain: layout style paint;
    }
    
    .gallery-item {
        will-change: transform;
        transform: translateZ(0); /* Force hardware acceleration */
    }
    
    /* Smooth scrolling */
    html {
        scroll-behavior: smooth;
    }
    
    @media (prefers-reduced-motion: reduce) {
        html {
            scroll-behavior: auto;
        }
        
        img {
            transition: none !important;
        }
    }
`;

// Add CSS to document
const style = document.createElement('style');
style.textContent = performanceCSS;
document.head.appendChild(style);

// Initialize performance optimizer when DOM is ready
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', () => {
        new PerformanceOptimizer();
    });
} else {
    new PerformanceOptimizer();
}

// Export for use in other scripts
window.PerformanceOptimizer = PerformanceOptimizer; 