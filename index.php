<?php
/* ───────────────────────────────────────────────────────────── */
/*  Home / Landing – Slideshow Hero                              */
/* ───────────────────────────────────────────────────────────── */

require_once 'cms/includes/config.php';

$page_title         = "Professional Photography & Cinematography Services";
$transparent_nav    = true;          // translucent bar over hero
$sticky_nav         = false;         // do NOT stick while scrolling
$extra_header_class = 'index-offset';// moves bar down on this page
include 'includes/header.php';

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

/* ───── Fetch animation settings and hero slides ─── */
$animationSettings = getPageAnimationSettings('home');

// Get slideshow images from dedicated slideshow table
$slideshow_images = [];
$conn = getDBConnection();

$stmt = $conn->query("
    SELECT image_url 
    FROM hero_slideshow 
    WHERE is_active = 1 
    ORDER BY display_order, id
");

while ($row = $stmt->fetch()) {
    // Add cms/ prefix if the image is from uploads directory
    $imageUrl = $row['image_url'];
    if (strpos($imageUrl, 'uploads/') === 0) {
        $imageUrl = 'cms/' . $imageUrl;
    }
    $slideshow_images[] = htmlspecialchars($imageUrl);
}

// Fallback: if no slideshow images, use portfolio images as before
if (empty($slideshow_images)) {
    define('PORTFOLIO_CATEGORIES', ['fineart','portraits','clients','travel']);
    
    /* first image of each category */
    foreach (PORTFOLIO_CATEGORIES as $cat) {
        $stmt = $conn->prepare("
            SELECT m.media_url
              FROM portfolio_items p
              JOIN portfolio_media m ON p.id = m.portfolio_item_id
             WHERE p.category = ? AND m.media_type = 'image'
          ORDER BY m.display_order, m.id
             LIMIT 1
        ");
        $stmt->execute([$cat]);
        if ($img = $stmt->fetchColumn()) {
            $slideshow_images[] = 'cms/' . htmlspecialchars($img);
        }
    }
    
    /* pad with random images until we have 4 */
    if (count($slideshow_images) < 4) {
        $stmt = $conn->query("
            SELECT m.media_url
              FROM portfolio_media m
             WHERE m.media_type = 'image'
          ORDER BY RANDOM()
             LIMIT ".(4-count($slideshow_images))
        );
        while ($img = $stmt->fetchColumn()) {
            $slideshow_images[] = 'cms/' . htmlspecialchars($img);
        }
    }
}
?>

<!-- ─── page-specific override: bar lower + tighter nav gap ─── -->
<style>
  header.index-offset { top:120px !important; }   /* move nav further down */
  @media (min-width:769px){
    header.index-offset .desktop-menu { gap:0.25rem !important; }
  }
</style>

<!-- ─────────────── HERO SECTION ─────────────── -->
<section class="relative h-screen overflow-hidden">

  <!-- slideshow -->
  <div id="hero-slideshow" class="absolute inset-0 w-full h-full z-0">
    <?php foreach ($slideshow_images as $i=>$img): ?>
      <img src="<?= $img ?>"
           class="slideshow-img absolute inset-0 w-full h-full object-cover transition-opacity duration-1000 <?= $i? 'opacity-0':''; ?> scale-110 animate-ken-burns"
           style="object-position:center" alt="Slide <?= $i+1; ?>">
    <?php endforeach; ?>
    <div class="absolute inset-0 bg-black/60 animate-fade-in"></div>
  </div>

  <!-- corner accents -->
  <style>
    .jr-corner{position:absolute;width:20px;height:20px;pointer-events:none}
    .jr-corner:before,.jr-corner:after{content:'';position:absolute;background:#fff}
    .jr-corner:before{width:2px;height:100%;left:0;top:0}
    .jr-corner:after {width:100%;height:2px;left:0;top:0}
  </style>
  <div class="pointer-events-none absolute inset-0 z-10">
    <div class="jr-corner" style="top:24px;left:24px"></div>
    <div class="jr-corner" style="top:24px;right:24px;transform:scaleX(-1)"></div>
    <div class="jr-corner" style="bottom:24px;left:24px;transform:scaleY(-1)"></div>
    <div class="jr-corner" style="bottom:24px;right:24px;transform:scale(-1,-1)"></div>
  </div>

  <!-- headline + CTA -->
  <div class="relative container mx-auto px-4 h-full flex items-center z-20">
    <div class="max-w-3xl text-white animate-slide-up-delay">
      <h1 class="text-6xl font-bold mb-6 leading-tight animate-text-reveal">
        <?= htmlspecialchars(getPageContent('home', 'hero_title', 'Capturing Your Vision Through Lens')) ?>
      </h1>
      <p class="text-2xl mb-8 text-gray-200 animate-slide-up-delay-2">
        <?= htmlspecialchars(getPageContent('home', 'hero_subtitle', 'Professional photography and cinematography services that bring your stories to life.')) ?>
      </p>
      <div class="flex gap-4 animate-slide-up-delay-3">
        <a href="portfolio-clients.php"
           class="inline-block bg-white text-gray-900 px-8 py-4 rounded-full text-lg font-semibold hover:bg-gray-100 hover:scale-105 transition-all duration-300 shadow-lg hover:shadow-xl transform-gpu">
          <?= htmlspecialchars(getPageContent('home', 'portfolio_button_text', 'View Portfolio')) ?>
        </a>
        <a href="contact.php"
           class="inline-block bg-blue-600 text-white px-8 py-4 rounded-full text-lg font-semibold hover:bg-blue-700 hover:scale-105 transition-all duration-300 shadow-lg hover:shadow-xl transform-gpu">
          <?= htmlspecialchars(getPageContent('home', 'contact_button_text', 'Contact Us')) ?>
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

@keyframes buttonFloat {
    0%, 100% { transform: translateY(0px); }
    50% { transform: translateY(-2px); }
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

.animate-slide-up-delay-3 {
    animation: slideUp 1s ease-out 1.5s both;
}

.animate-ken-burns {
    animation: kenBurns 20s ease-in-out infinite;
}

.animate-text-reveal {
    animation: textReveal 1.2s ease-out 0.8s both;
}

/* Enhanced button animations */
.animate-slide-up-delay-3 a {
    display: inline-block;
    animation: buttonFloat 3s ease-in-out infinite;
}

.animate-slide-up-delay-3 a:nth-child(2) {
    animation-delay: 0.5s;
}

/* Corner accent animations */
.jr-corner {
    opacity: 0;
    animation: fadeIn 2s ease-out 2s both;
}

.jr-corner:nth-child(1) { animation-delay: 2s; }
.jr-corner:nth-child(2) { animation-delay: 2.2s; }
.jr-corner:nth-child(3) { animation-delay: 2.4s; }
.jr-corner:nth-child(4) { animation-delay: 2.6s; }
</style>

<!-- Enhanced slideshow and animation logic -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Enhanced slideshow fade logic with Ken Burns effect
    const slides = document.querySelectorAll('.slideshow-img');
    let cur = 0;
    
    // Enhanced slideshow transitions
    function nextSlide() {
        slides[cur].classList.add('opacity-0');
        cur = (cur + 1) % slides.length;
        slides[cur].classList.remove('opacity-0');
    }
    
    // Start slideshow after initial animations
    setTimeout(() => {
        setInterval(nextSlide, 6000); // Slower transition for better Ken Burns effect
    }, 3000);
    
    // Parallax effect for hero section
    window.addEventListener('scroll', function() {
        const scrolled = window.pageYOffset;
        const heroSection = document.querySelector('#hero-slideshow');
        if (heroSection && scrolled < window.innerHeight) {
            const rate = scrolled * -0.3;
            heroSection.style.transform = `translateY(${rate}px)`;
        }
    });
    
    // Enhanced button hover effects
    const buttons = document.querySelectorAll('.animate-slide-up-delay-3 a');
    buttons.forEach(button => {
        button.addEventListener('mouseenter', function() {
            this.style.transform = 'scale(1.05) translateY(-2px)';
            this.style.boxShadow = '0 10px 25px rgba(0, 0, 0, 0.2)';
        });
        
        button.addEventListener('mouseleave', function() {
            this.style.transform = 'scale(1) translateY(0px)';
            this.style.boxShadow = '0 4px 15px rgba(0, 0, 0, 0.1)';
        });
    });
    
    // Add interactive corner accents
    const corners = document.querySelectorAll('.jr-corner');
    corners.forEach((corner, index) => {
        corner.addEventListener('mouseenter', function() {
            this.style.transform += ' scale(1.2)';
            this.style.opacity = '0.8';
        });
        
        corner.addEventListener('mouseleave', function() {
            const transforms = [
                '', 
                'scaleX(-1)', 
                'scaleY(-1)', 
                'scale(-1,-1)'
            ];
            this.style.transform = transforms[index];
            this.style.opacity = '1';
        });
    });
    
    // Add keyboard navigation for accessibility
    document.addEventListener('keydown', function(e) {
        if (e.key === 'ArrowLeft' || e.key === 'ArrowRight') {
            if (e.key === 'ArrowLeft') {
                // Previous slide
                slides[cur].classList.add('opacity-0');
                cur = (cur - 1 + slides.length) % slides.length;
                slides[cur].classList.remove('opacity-0');
            } else {
                // Next slide
                nextSlide();
            }
        }
    });
    
    // Preload next images for smooth transitions
    slides.forEach((slide, index) => {
        if (index > 0) {
            const img = new Image();
            img.src = slide.src;
        }
    });
});
</script>

<!-- Page Animation Script -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Get page animation setting
    const refreshAnimation = '<?= $animationSettings['refresh_animation'] ?>';
    
    // Define animation styles
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
