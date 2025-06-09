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

/* ───── Fetch one hero slide per category (fallback random) ─── */
define('PORTFOLIO_CATEGORIES', ['fineart','portraits','clients','travel']);
$slideshow_images = [];
$conn = getDBConnection();

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
        $slideshow_images[] = 'cms/'.htmlspecialchars($img);
    }
}
/* pad with random images until we have 4 */
if (count($slideshow_images) < 4) {
    $stmt = $conn->query("
        SELECT m.media_url
          FROM portfolio_media m
         WHERE m.media_type = 'image'
      ORDER BY RAND()
         LIMIT ".(4-count($slideshow_images))
    );
    while ($img = $stmt->fetchColumn()) {
        $slideshow_images[] = 'cms/'.htmlspecialchars($img);
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
           class="slideshow-img absolute inset-0 w-full h-full object-cover transition-opacity duration-1000 <?= $i? 'opacity-0':''; ?>"
           style="object-position:center" alt="Slide <?= $i+1; ?>">
    <?php endforeach; ?>
    <div class="absolute inset-0 bg-black/60"></div>
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
    <div class="max-w-3xl text-white">
      <h1 class="text-6xl font-bold mb-6 leading-tight">
        Capturing Your Vision Through Lens
      </h1>
      <p class="text-2xl mb-8 text-gray-200">
        Professional photography and cinematography services that bring your stories to life.
      </p>
      <div class="flex gap-4">
        <a href="portfolio.php"
           class="inline-block bg-white text-gray-900 px-8 py-4 rounded-full text-lg font-semibold hover:bg-gray-100 transition">
          View Portfolio
        </a>
        <a href="contact.php"
           class="inline-block bg-blue-600 text-white px-8 py-4 rounded-full text-lg font-semibold hover:bg-blue-700 transition">
          Contact Us
        </a>
      </div>
    </div>
  </div>
</section>

<!-- slideshow fade logic -->
<script>
  const slides=document.querySelectorAll('.slideshow-img');
  let cur=0;
  setInterval(()=>{
    slides[cur].classList.add('opacity-0');
    cur=(cur+1)%slides.length;
    slides[cur].classList.remove('opacity-0');
  },5000);
</script>

<?php include 'includes/footer.php'; ?>
