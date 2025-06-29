<?php
$page_title = "Contact - Professional Photography & Cinematography";
include 'includes/header.php';

// Include database connection for CMS content
require_once 'cms/includes/config.php';

// Get animation settings for this page
$animationSettings = getPageAnimationSettings('contact');

// Function to get page content
function getPageContent($pageName, $sectionKey, $default = '') {
    try {
        $conn = getDBConnection();
        $stmt = $conn->prepare("SELECT content_value FROM page_content WHERE page_name = ? AND section_key = ? AND is_active = 1");
        $stmt->execute([$pageName, $sectionKey]);
        $result = $stmt->fetchColumn();
        return $result ?: $default;
    } catch (Exception $e) {
        return $default;
    }
}
?>

<!-- Hero Section -->
<section class="relative h-screen overflow-hidden">
    <div class="absolute inset-0">
        <img src="<?= htmlspecialchars(getPageContent('contact', 'hero_image', 'https://images.unsplash.com/photo-1492691527719-9d1e07e534b4')) ?>" 
             alt="Contact Hero" 
             class="w-full h-full object-cover scale-110 animate-ken-burns">
        <div class="absolute inset-0 bg-black/60 animate-fade-in"></div>
    </div>
    <div class="relative container mx-auto px-4 h-full flex items-center">
        <div class="max-w-3xl text-white animate-slide-up-delay">
            <h1 class="text-6xl font-bold mb-6 leading-tight animate-text-reveal">
                <?= htmlspecialchars(getPageContent('contact', 'hero_title', 'Get in Touch')) ?>
            </h1>
            <p class="text-2xl mb-8 text-gray-200 animate-slide-up-delay-2">
                <?= htmlspecialchars(getPageContent('contact', 'hero_subtitle', 'Let\'s discuss your next photography or cinematography project.')) ?>
            </p>
        </div>
    </div>
</section>

<!-- Contact Content -->
<section class="py-20 bg-white">
    <div class="container mx-auto px-4">
        <div class="max-w-6xl mx-auto">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-12">
                <!-- Contact Form -->
                <div class="animate-on-scroll slide-in-left">
                    <h2 class="text-4xl font-bold mb-8">
                        <?= htmlspecialchars(getPageContent('contact', 'form_title', 'Send a Message')) ?>
                    </h2>
                    
                    <?php
                    // Display success/error messages
                    if (isset($_GET['success'])) {
                        echo '<div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-4">
                                <strong>Success!</strong> Your message has been sent successfully. We\'ll get back to you soon.
                              </div>';
                    }
                    
                    if (isset($_GET['error'])) {
                        $error = $_GET['error'];
                        $message = 'An error occurred. Please try again.';
                        switch ($error) {
                            case 'rate_limit':
                                $message = 'Too many submissions. Please wait 5 minutes before trying again.';
                                break;
                            case 'security':
                                $message = 'Security validation failed. Please refresh the page and try again.';
                                break;
                            case 'spam':
                                $message = 'Your message was flagged as potential spam. Please modify your message.';
                                break;
                            case 'validation':
                                $message = 'Please check your input: ' . htmlspecialchars($_GET['details'] ?? '');
                                break;
                            case 'system':
                                $message = 'System error. Please try again later or contact us directly.';
                                break;
                        }
                        echo '<div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4">
                                <strong>Error:</strong> ' . $message . '
                              </div>';
                    }
                    ?>
                    
                    <form action="process_contact.php" method="POST" class="space-y-6 contact-form">
                        <?php echo SecurityHelper::csrfTokenField(); ?>
                        <div class="form-group">
                            <label for="name" class="block text-sm font-medium text-gray-700 mb-2">Name</label>
                            <input type="text" id="name" name="name" required
                                class="w-full px-4 py-3 rounded-lg border border-gray-300 focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-all duration-300 hover:border-blue-400 focus:scale-105">
                        </div>
                        <div class="form-group">
                            <label for="email" class="block text-sm font-medium text-gray-700 mb-2">Email</label>
                            <input type="email" id="email" name="email" required
                                class="w-full px-4 py-3 rounded-lg border border-gray-300 focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-all duration-300 hover:border-blue-400 focus:scale-105">
                        </div>
                        <div class="form-group">
                            <label for="subject" class="block text-sm font-medium text-gray-700 mb-2">Subject</label>
                            <input type="text" id="subject" name="subject" required
                                class="w-full px-4 py-3 rounded-lg border border-gray-300 focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-all duration-300 hover:border-blue-400 focus:scale-105">
                        </div>
                        <div class="form-group">
                            <label for="message" class="block text-sm font-medium text-gray-700 mb-2">Message</label>
                            <textarea id="message" name="message" rows="6" required
                                class="w-full px-4 py-3 rounded-lg border border-gray-300 focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-all duration-300 hover:border-blue-400 focus:scale-105 resize-none"></textarea>
                        </div>
                        <button type="submit"
                            class="w-full bg-blue-600 text-white px-8 py-4 rounded-lg text-lg font-semibold hover:bg-blue-700 hover:scale-105 transition-all duration-300 shadow-lg hover:shadow-xl transform-gpu">
                            Send Message
                        </button>
                    </form>
                </div>

                <!-- Contact Information -->
                <div class="animate-on-scroll slide-in-right">
                    <h2 class="text-4xl font-bold mb-8">
                        <?= htmlspecialchars(getPageContent('contact', 'info_title', 'Contact Information')) ?>
                    </h2>
                    <div class="space-y-8">
                        <div class="p-6 bg-gray-50 rounded-lg hover:bg-blue-50 transition-all duration-300 hover:transform hover:-translate-y-1 shadow-md hover:shadow-lg">
                            <h3 class="text-xl font-semibold mb-4">
                                <?= htmlspecialchars(getPageContent('contact', 'location_title', 'Location')) ?>
                            </h3>
                            <p class="text-gray-600">
                                <?= getPageContent('contact', 'location_address', '123 Photography Street<br>New York, NY 10001<br>United States') ?>
                            </p>
                        </div>
                        <div class="p-6 bg-gray-50 rounded-lg hover:bg-blue-50 transition-all duration-300 hover:transform hover:-translate-y-1 shadow-md hover:shadow-lg">
                            <h3 class="text-xl font-semibold mb-4">
                                <?= htmlspecialchars(getPageContent('contact', 'details_title', 'Contact Details')) ?>
                            </h3>
                            <ul class="space-y-4">
                                <li class="flex items-center text-gray-600 hover:text-blue-600 transition-colors duration-300">
                                    <i class="fas fa-phone w-6 mr-3"></i>
                                    <a href="tel:<?= htmlspecialchars(str_replace(['(', ')', ' ', '-'], '', getPageContent('contact', 'phone_number', '+1 (234) 567-890'))) ?>" 
                                       class="hover:text-blue-600 transition-all duration-300 hover:scale-105">
                                        <?= htmlspecialchars(getPageContent('contact', 'phone_number', '+1 (234) 567-890')) ?>
                                    </a>
                                </li>
                                <li class="flex items-center text-gray-600 hover:text-blue-600 transition-colors duration-300">
                                    <i class="fas fa-envelope w-6 mr-3"></i>
                                    <a href="mailto:<?= htmlspecialchars(getPageContent('contact', 'email_address', 'contact@example.com')) ?>" 
                                       class="hover:text-blue-600 transition-all duration-300 hover:scale-105">
                                        <?= htmlspecialchars(getPageContent('contact', 'email_address', 'contact@example.com')) ?>
                                    </a>
                                </li>
                            </ul>
                        </div>
                        <div class="p-6 bg-gray-50 rounded-lg hover:bg-blue-50 transition-all duration-300 hover:transform hover:-translate-y-1 shadow-md hover:shadow-lg">
                            <h3 class="text-xl font-semibold mb-4">
                                <?= htmlspecialchars(getPageContent('contact', 'social_title', 'Follow Me')) ?>
                            </h3>
                            <div class="flex space-x-4">
                                <a href="<?= htmlspecialchars(getPageContent('contact', 'instagram_url', '#')) ?>" 
                                   class="text-gray-600 hover:text-blue-600 transition-all duration-300 text-2xl hover:scale-125 transform-gpu">
                                    <i class="fab fa-instagram"></i>
                                </a>
                                <a href="<?= htmlspecialchars(getPageContent('contact', 'facebook_url', '#')) ?>" 
                                   class="text-gray-600 hover:text-blue-600 transition-all duration-300 text-2xl hover:scale-125 transform-gpu">
                                    <i class="fab fa-facebook"></i>
                                </a>
                                <a href="<?= htmlspecialchars(getPageContent('contact', 'twitter_url', '#')) ?>" 
                                   class="text-gray-600 hover:text-blue-600 transition-all duration-300 text-2xl hover:scale-125 transform-gpu">
                                    <i class="fab fa-twitter"></i>
                                </a>
                                <a href="<?= htmlspecialchars(getPageContent('contact', 'linkedin_url', '#')) ?>" 
                                   class="text-gray-600 hover:text-blue-600 transition-all duration-300 text-2xl hover:scale-125 transform-gpu">
                                    <i class="fab fa-linkedin"></i>
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Animation Styles -->
<style>
/* Keyframe Animations */
@keyframes fadeIn {
    from { opacity: 0; }
    to { opacity: 1; }
}

@keyframes slideUp {
    from { 
        opacity: 0; 
        transform: translateY(50px); 
    }
    to { 
        opacity: 1; 
        transform: translateY(0); 
    }
}

@keyframes slideInLeft {
    from { 
        opacity: 0; 
        transform: translateX(-50px); 
    }
    to { 
        opacity: 1; 
        transform: translateX(0); 
    }
}

@keyframes slideInRight {
    from { 
        opacity: 0; 
        transform: translateX(50px); 
    }
    to { 
        opacity: 1; 
        transform: translateX(0); 
    }
}

@keyframes kenBurns {
    0% { transform: scale(1.1) rotate(0deg); }
    50% { transform: scale(1.15) rotate(0.5deg); }
    100% { transform: scale(1.1) rotate(0deg); }
}

@keyframes textReveal {
    0% { 
        opacity: 0; 
        transform: translateY(30px); 
        filter: blur(3px); 
    }
    100% { 
        opacity: 1; 
        transform: translateY(0); 
        filter: blur(0px); 
    }
}

@keyframes formSlideIn {
    from {
        opacity: 0;
        transform: translateY(20px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

/* Animation Classes */
.animate-fade-in {
    animation: fadeIn 1.5s ease-out;
}

.animate-slide-up-delay {
    animation: slideUp 1s ease-out 0.5s both;
}

.animate-slide-up-delay-2 {
    animation: slideUp 1s ease-out 1s both;
}

.animate-ken-burns {
    animation: kenBurns 20s ease-in-out infinite;
}

.animate-text-reveal {
    animation: textReveal 1.2s ease-out 0.8s both;
}

/* Scroll-triggered animations */
.animate-on-scroll {
    opacity: 0;
    transform: translateY(30px);
    transition: all 0.8s ease-out;
}

.animate-on-scroll.animate {
    opacity: 1;
    transform: translateY(0);
}

.slide-in-left {
    transform: translateX(-50px);
}

.slide-in-left.animate {
    transform: translateX(0);
}

.slide-in-right {
    transform: translateX(50px);
}

.slide-in-right.animate {
    transform: translateX(0);
}

/* Form animations */
.contact-form .form-group {
    opacity: 0;
    transform: translateY(20px);
    animation: formSlideIn 0.6s ease-out forwards;
}

.contact-form .form-group:nth-child(2) { animation-delay: 0.1s; }
.contact-form .form-group:nth-child(3) { animation-delay: 0.2s; }
.contact-form .form-group:nth-child(4) { animation-delay: 0.3s; }
.contact-form .form-group:nth-child(5) { animation-delay: 0.4s; }
.contact-form button { animation-delay: 0.5s; }

/* Focus animations */
.contact-form input:focus,
.contact-form textarea:focus {
    box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
}

/* Hover effects for contact cards */
.contact-info-card {
    transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
}

.contact-info-card:hover {
    transform: translateY(-4px) scale(1.02);
    box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04);
}
</style>

<!-- Animation JavaScript -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Intersection Observer for scroll animations
    const observerOptions = {
        threshold: 0.1,
        rootMargin: '0px 0px -50px 0px'
    };

    const observer = new IntersectionObserver(function(entries) {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                entry.target.classList.add('animate');
                
                // Animate form elements when form section comes into view
                if (entry.target.classList.contains('slide-in-left')) {
                    setTimeout(() => {
                        const formGroups = entry.target.querySelectorAll('.form-group');
                        formGroups.forEach((group, index) => {
                            setTimeout(() => {
                                group.style.opacity = '1';
                                group.style.transform = 'translateY(0)';
                            }, index * 100);
                        });
                    }, 300);
                }
            }
        });
    }, observerOptions);

    // Observe all elements with animation classes
    document.querySelectorAll('.animate-on-scroll').forEach(el => {
        observer.observe(el);
    });

    // Form interaction animations
    const formInputs = document.querySelectorAll('.contact-form input, .contact-form textarea');
    formInputs.forEach(input => {
        input.addEventListener('focus', function() {
            this.parentElement.classList.add('focused');
        });
        
        input.addEventListener('blur', function() {
            this.parentElement.classList.remove('focused');
        });
        
        input.addEventListener('input', function() {
            if (this.value.length > 0) {
                this.parentElement.classList.add('has-content');
            } else {
                this.parentElement.classList.remove('has-content');
            }
        });
    });

    // Form submission animation
    const contactForm = document.querySelector('.contact-form');
    if (contactForm) {
        contactForm.addEventListener('submit', function(e) {
            const submitBtn = this.querySelector('button[type="submit"]');
            submitBtn.innerHTML = `
                <svg class="animate-spin -ml-1 mr-3 h-5 w-5 text-white inline" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                </svg>
                Sending...
            `;
            submitBtn.disabled = true;
        });
    }

    // Smooth scrolling for anchor links
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

    // Add parallax effect to hero section
    window.addEventListener('scroll', function() {
        const scrolled = window.pageYOffset;
        const heroImage = document.querySelector('.animate-ken-burns');
        if (heroImage) {
            const rate = scrolled * -0.5;
            heroImage.style.transform = `translateY(${rate}px) scale(1.1)`;
        }
    });

    // Add typing animation to form labels
    const labels = document.querySelectorAll('.contact-form label');
    labels.forEach(label => {
        const text = label.textContent;
        label.textContent = '';
        
        let i = 0;
        const typeEffect = setInterval(() => {
            if (i < text.length) {
                label.textContent += text.charAt(i);
                i++;
            } else {
                clearInterval(typeEffect);
            }
        }, 50);
    });
});

// CSS animations for spinning loader
const style = document.createElement('style');
style.textContent = `
    @keyframes spin {
        from { transform: rotate(0deg); }
        to { transform: rotate(360deg); }
    }
    .animate-spin {
        animation: spin 1s linear infinite;
    }
`;
document.head.appendChild(style);
</script>

<!-- Page Animation Script -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Get page animation setting
    const refreshAnimation = '<?= $animationSettings['refresh_animation'] ?>';
    
    // Define animation styles (same as other pages)
    const animationStyles = {
        fade: {
            from: { opacity: 0 },
            to: { opacity: 1 },
            duration: '1s',
            easing: 'ease-out'
        },
        slideUp: {
            from: { opacity: 0, transform: 'translateY(50px)' },
            to: { opacity: 1, transform: 'translateY(0)' },
            duration: '0.8s',
            easing: 'ease-out'
        },
        slideDown: {
            from: { opacity: 0, transform: 'translateY(-50px)' },
            to: { opacity: 1, transform: 'translateY(0)' },
            duration: '0.8s',
            easing: 'ease-out'
        },
        slideLeft: {
            from: { opacity: 0, transform: 'translateX(-50px)' },
            to: { opacity: 1, transform: 'translateX(0)' },
            duration: '0.8s',
            easing: 'ease-out'
        },
        slideRight: {
            from: { opacity: 0, transform: 'translateX(50px)' },
            to: { opacity: 1, transform: 'translateX(0)' },
            duration: '0.8s',
            easing: 'ease-out'
        },
        zoom: {
            from: { opacity: 0, transform: 'scale(0.9)' },
            to: { opacity: 1, transform: 'scale(1)' },
            duration: '0.8s',
            easing: 'ease-out'
        },
        bounce: {
            from: { opacity: 0, transform: 'translateY(-30px)' },
            to: { opacity: 1, transform: 'translateY(0)' },
            duration: '1s',
            easing: 'cubic-bezier(0.68, -0.55, 0.265, 1.55)'
        },
        flip: {
            from: { opacity: 0, transform: 'rotateY(90deg)' },
            to: { opacity: 1, transform: 'rotateY(0deg)' },
            duration: '0.8s',
            easing: 'ease-out'
        },
        rotate: {
            from: { opacity: 0, transform: 'rotate(-10deg) scale(0.9)' },
            to: { opacity: 1, transform: 'rotate(0deg) scale(1)' },
            duration: '0.8s',
            easing: 'ease-out'
        },
        elastic: {
            from: { opacity: 0, transform: 'scale(0.7)' },
            to: { opacity: 1, transform: 'scale(1)' },
            duration: '1.2s',
            easing: 'cubic-bezier(0.68, -0.55, 0.265, 1.55)'
        }
    };
    
    // Apply animation to main content
    const animation = animationStyles[refreshAnimation] || animationStyles.fade;
    const mainContent = document.querySelector('main') || document.body;
    
    // Set initial state
    Object.assign(mainContent.style, animation.from);
    mainContent.style.transition = `all ${animation.duration} ${animation.easing}`;
    
    // Animate to final state
    requestAnimationFrame(() => {
        Object.assign(mainContent.style, animation.to);
    });
});
</script>

<?php include 'includes/footer.php'; ?> 