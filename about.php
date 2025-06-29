<?php
$page_title = "About - Professional Photography & Cinematography";
include 'includes/header.php';

// Include database connection for CMS content
require_once 'cms/includes/config.php';

// Get animation settings for this page
$animationSettings = getPageAnimationSettings('about');

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
        <img src="<?= htmlspecialchars(getPageContent('about', 'hero_image', 'https://images.unsplash.com/photo-1492691527719-9d1e07e534b4')) ?>" 
             alt="About Hero" 
             class="w-full h-full object-cover scale-110 animate-ken-burns">
        <div class="absolute inset-0 bg-black/60 animate-fade-in"></div>
    </div>
    <div class="relative container mx-auto px-4 h-full flex items-center">
        <div class="max-w-3xl text-white animate-slide-up-delay">
            <h1 class="text-6xl font-bold mb-6 leading-tight animate-text-reveal">
                <?= htmlspecialchars(getPageContent('about', 'hero_title', 'About the Artist')) ?>
            </h1>
            <p class="text-2xl mb-8 text-gray-200 animate-slide-up-delay-2">
                <?= htmlspecialchars(getPageContent('about', 'hero_subtitle', 'Capturing life\'s most precious moments through the lens of creativity and passion.')) ?>
            </p>
        </div>
    </div>
</section>

<!-- About Content -->
<section class="py-20 bg-white">
    <div class="container mx-auto px-4">
        <div class="max-w-4xl mx-auto">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-12 items-center mb-20 scroll-reveal">
                <div class="animate-on-scroll slide-in-left">
                    <h2 class="text-4xl font-bold mb-6">
                        <?= htmlspecialchars(getPageContent('about', 'story_title', 'The Story')) ?>
                    </h2>
                    <p class="text-lg text-gray-600 leading-relaxed mb-6">
                        <?= getPageContent('about', 'story_paragraph1', 'With over a decade of experience in visual storytelling, I specialize in capturing authentic moments that tell compelling stories. My work combines technical expertise with artistic vision, creating imagery that resonates with viewers on an emotional level.') ?>
                    </p>
                    <p class="text-lg text-gray-600 leading-relaxed">
                        <?= getPageContent('about', 'story_paragraph2', 'Whether it\'s a commercial project, wedding, or personal portrait session, I approach each assignment with the same dedication to excellence and attention to detail. My goal is to create timeless visual narratives that capture the essence of each moment.') ?>
                    </p>
                </div>
                <div class="relative aspect-square animate-on-scroll slide-in-right">
                    <img src="<?= htmlspecialchars(getPageContent('about', 'story_image', 'https://images.unsplash.com/photo-1492691527719-9d1e07e534b4')) ?>" 
                         alt="Artist Portrait" 
                         class="w-full h-full object-cover rounded-lg shadow-xl hover:shadow-2xl transition-all duration-500 hover:scale-105">
                </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-8 mb-20 animate-on-scroll fade-in-up">
                <div class="text-center p-6 rounded-lg bg-gray-50 hover:bg-blue-50 transition-all duration-300 hover:transform hover:-translate-y-2 shadow-md hover:shadow-lg">
                    <div class="text-4xl font-bold text-blue-600 mb-4 counter" data-target="<?= htmlspecialchars(getPageContent('about', 'stat1_number', '10+')) ?>">0</div>
                    <h3 class="text-xl font-semibold mb-2"><?= htmlspecialchars(getPageContent('about', 'stat1_title', 'Years Experience')) ?></h3>
                    <p class="text-gray-600"><?= htmlspecialchars(getPageContent('about', 'stat1_description', 'Professional photography and cinematography expertise')) ?></p>
                </div>
                <div class="text-center p-6 rounded-lg bg-gray-50 hover:bg-blue-50 transition-all duration-300 hover:transform hover:-translate-y-2 shadow-md hover:shadow-lg animation-delay-200">
                    <div class="text-4xl font-bold text-blue-600 mb-4 counter" data-target="<?= htmlspecialchars(getPageContent('about', 'stat2_number', '500+')) ?>">0</div>
                    <h3 class="text-xl font-semibold mb-2"><?= htmlspecialchars(getPageContent('about', 'stat2_title', 'Projects Completed')) ?></h3>
                    <p class="text-gray-600"><?= htmlspecialchars(getPageContent('about', 'stat2_description', 'Successfully delivered projects for clients worldwide')) ?></p>
                </div>
                <div class="text-center p-6 rounded-lg bg-gray-50 hover:bg-blue-50 transition-all duration-300 hover:transform hover:-translate-y-2 shadow-md hover:shadow-lg animation-delay-400">
                    <div class="text-4xl font-bold text-blue-600 mb-4 counter" data-target="<?= htmlspecialchars(getPageContent('about', 'stat3_number', '100%')) ?>">0</div>
                    <h3 class="text-xl font-semibold mb-2"><?= htmlspecialchars(getPageContent('about', 'stat3_title', 'Client Satisfaction')) ?></h3>
                    <p class="text-gray-600"><?= htmlspecialchars(getPageContent('about', 'stat3_description', 'Dedicated to exceeding client expectations')) ?></p>
                </div>
            </div>

            <div class="text-center animate-on-scroll fade-in-up animation-delay-600">
                <h2 class="text-4xl font-bold mb-8"><?= htmlspecialchars(getPageContent('about', 'cta_title', 'Let\'s Work Together')) ?></h2>
                <p class="text-xl text-gray-600 mb-8 max-w-2xl mx-auto">
                    <?= getPageContent('about', 'cta_description', 'I\'m always excited to take on new projects and collaborate with clients who share my passion for visual storytelling.') ?>
                </p>
                <a href="contact.php" class="inline-block bg-blue-600 text-white px-8 py-4 rounded-full text-lg font-semibold hover:bg-blue-700 hover:scale-105 transition-all duration-300 shadow-lg hover:shadow-xl">
                    <?= htmlspecialchars(getPageContent('about', 'cta_button_text', 'Get in Touch')) ?>
                </a>
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

.fade-in-up {
    transform: translateY(30px);
}

.fade-in-up.animate {
    transform: translateY(0);
}

/* Animation delays */
.animation-delay-200 {
    transition-delay: 0.2s;
}

.animation-delay-400 {
    transition-delay: 0.4s;
}

.animation-delay-600 {
    transition-delay: 0.6s;
}

/* Counter animation */
.counter {
    display: inline-block;
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
                
                // Start counter animation for statistics
                const counters = entry.target.querySelectorAll('.counter');
                counters.forEach(counter => {
                    animateCounter(counter);
                });
            }
        });
    }, observerOptions);

    // Observe all elements with animation classes
    document.querySelectorAll('.animate-on-scroll').forEach(el => {
        observer.observe(el);
    });

    // Counter animation function
    function animateCounter(element) {
        const target = element.getAttribute('data-target');
        const isPercent = target.includes('%');
        const isPlus = target.includes('+');
        const numericValue = parseInt(target.replace(/[^\d]/g, ''));
        
        let current = 0;
        const increment = numericValue / 50; // Animate over 50 steps
        const duration = 2000; // 2 seconds
        const stepTime = duration / 50;

        const timer = setInterval(() => {
            current += increment;
            if (current >= numericValue) {
                current = numericValue;
                clearInterval(timer);
            }
            
            let displayValue = Math.floor(current);
            if (isPercent) displayValue += '%';
            if (isPlus) displayValue += '+';
            
            element.textContent = displayValue;
        }, stepTime);
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
});
</script>

<!-- Page Animation Script -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Get page animation setting
    const refreshAnimation = '<?= $animationSettings['refresh_animation'] ?>';
    
    // Define animation styles (same as home page)
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