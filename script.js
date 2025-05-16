// Booking form submission handler
function handleBookingFormSubmit(event) {
  event.preventDefault();
  alert('Thank you for booking! We will contact you soon.');
  // You can add AJAX here to send form data to a server
}

document.addEventListener('DOMContentLoaded', function () {
  var bookingForm = document.getElementById('booking-form');
  if (bookingForm) {
    bookingForm.addEventListener('submit', handleBookingFormSubmit);
  }

  // Lightbox functionality for gallery images
  var photoGrid = document.querySelector('.photo-grid');
  if (photoGrid) {
    var lightbox = document.createElement('div');
    lightbox.id = 'lightbox';
    lightbox.style.display = 'none';
    lightbox.style.position = 'fixed';
    lightbox.style.top = 0;
    lightbox.style.left = 0;
    lightbox.style.width = '100vw';
    lightbox.style.height = '100vh';
    lightbox.style.background = 'rgba(0,0,0,0.8)';
    lightbox.style.justifyContent = 'center';
    lightbox.style.alignItems = 'center';
    lightbox.style.zIndex = 1000;
    lightbox.style.display = 'flex';
    lightbox.style.flexDirection = 'column';
    lightbox.innerHTML = '<span id="close-lightbox" style="color:#fff;font-size:2rem;cursor:pointer;align-self:flex-end;margin:1rem;">&times;</span><img id="lightbox-img" src="" alt="Large view" style="max-width:90vw;max-height:80vh;border-radius:8px;box-shadow:0 2px 16px rgba(0,0,0,0.3);">';
    document.body.appendChild(lightbox);
    lightbox.style.display = 'none';

    function openLightbox(src, alt) {
      document.getElementById('lightbox-img').src = src;
      document.getElementById('lightbox-img').alt = alt;
      lightbox.style.display = 'flex';
    }

    function closeLightbox() {
      lightbox.style.display = 'none';
      document.getElementById('lightbox-img').src = '';
    }

    photoGrid.querySelectorAll('img').forEach(function (img) {
      img.addEventListener('click', function () {
        openLightbox(img.src, img.alt);
      });
    });

    document.getElementById('close-lightbox').addEventListener('click', closeLightbox);
    lightbox.addEventListener('click', function (e) {
      if (e.target === lightbox) {
        closeLightbox();
      }
    });
  }
});

// Portfolio Filtering
document.addEventListener('DOMContentLoaded', () => {
    const filterButtons = document.querySelectorAll('.filter-button');
    const portfolioItems = document.querySelectorAll('.portfolio-item');

    filterButtons.forEach(button => {
        button.addEventListener('click', () => {
            // Remove active class from all buttons
            filterButtons.forEach(btn => btn.classList.remove('active'));
            // Add active class to clicked button
            button.classList.add('active');

            const filterValue = button.getAttribute('data-filter');

            portfolioItems.forEach(item => {
                if (filterValue === 'all' || item.getAttribute('data-category') === filterValue) {
                    item.style.display = 'block';
                } else {
                    item.style.display = 'none';
                }
            });
        });
    });
});

// Mobile Navigation Toggle
document.addEventListener('DOMContentLoaded', () => {
    const mobileMenuButton = document.querySelector('.mobile-menu-button');
    const navMenu = document.querySelector('.nav-menu');

    if (mobileMenuButton) {
        mobileMenuButton.addEventListener('click', () => {
            navMenu.classList.toggle('active');
        });
    }
});

// Smooth Scrolling for Anchor Links
document.addEventListener('DOMContentLoaded', () => {
    document.querySelectorAll('a[href^="#"]').forEach(anchor => {
        anchor.addEventListener('click', function (e) {
            e.preventDefault();
            const target = document.querySelector(this.getAttribute('href'));
            if (target) {
                target.scrollIntoView({
                    behavior: 'smooth',
                    block: 'start'
                });
            }
        });
    });
});

// Form Validation
document.addEventListener('DOMContentLoaded', () => {
    const forms = document.querySelectorAll('form');
    
    forms.forEach(form => {
        form.addEventListener('submit', (e) => {
            e.preventDefault();
            
            // Basic form validation
            const requiredFields = form.querySelectorAll('[required]');
            let isValid = true;

            requiredFields.forEach(field => {
                if (!field.value.trim()) {
                    isValid = false;
                    field.classList.add('error');
                } else {
                    field.classList.remove('error');
                }
            });

            if (isValid) {
                // Here you would typically send the form data to a server
                console.log('Form submitted successfully');
                form.reset();
            }
        });
    });
});

// Image Lazy Loading
document.addEventListener('DOMContentLoaded', () => {
    const lazyImages = document.querySelectorAll('img[data-src]');
    
    const imageObserver = new IntersectionObserver((entries, observer) => {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                const img = entry.target;
                img.src = img.dataset.src;
                img.removeAttribute('data-src');
                observer.unobserve(img);
            }
        });
    });

    lazyImages.forEach(img => imageObserver.observe(img));
});

// Portfolio Item Hover Effect
document.addEventListener('DOMContentLoaded', () => {
    const portfolioItems = document.querySelectorAll('.portfolio-item');
    
    portfolioItems.forEach(item => {
        item.addEventListener('mouseenter', () => {
            item.querySelector('.portfolio-overlay').style.opacity = '1';
        });
        
        item.addEventListener('mouseleave', () => {
            item.querySelector('.portfolio-overlay').style.opacity = '0';
        });
    });
});

// Header Scroll Effect
document.addEventListener('DOMContentLoaded', () => {
    const header = document.querySelector('.site-header');
    let lastScroll = 0;

    window.addEventListener('scroll', () => {
        const currentScroll = window.pageYOffset;

        if (currentScroll <= 0) {
            header.classList.remove('scroll-up');
            return;
        }

        if (currentScroll > lastScroll && !header.classList.contains('scroll-down')) {
            // Scrolling down
            header.classList.remove('scroll-up');
            header.classList.add('scroll-down');
        } else if (currentScroll < lastScroll && header.classList.contains('scroll-down')) {
            // Scrolling up
            header.classList.remove('scroll-down');
            header.classList.add('scroll-up');
        }
        lastScroll = currentScroll;
    });
}); 