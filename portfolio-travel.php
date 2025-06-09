<?php
/*  ────────────────────────────────────────────────────────────── */
/*  Travel Portfolio – PhotoSwipe v5 (local assets)               */
/*  ────────────────────────────────────────────────────────────── */

require_once 'cms/includes/config.php';
$page_title = "Travel Portfolio - Professional Photography & Cinematography";
include 'includes/header.php';

/* 1. Fetch travel items from the DB */
$conn = getDBConnection();
$stmt = $conn->prepare(
  "SELECT *
     FROM portfolio_items
    WHERE category = 'travel'
 ORDER BY created_at DESC"
);
$stmt->execute();
$items = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!-- ─────────────── PhotoSwipe CSS (LOCAL) ─────────────── -->
<link  rel="stylesheet" href="public/assets/css/photoswipe.css" />

<script src="public/assets/js/photoswipe.umd.min.js"></script>
<script src="public/assets/js/photoswipe-lightbox.umd.min.js"></script>

<style>
  .pswp__bg { background:#fff !important; }
  .pswp__counter { color: #111 !important; font-weight: 600; font-size: 1.1rem; }
  .portfolio-nav {
    position: absolute;
    top: 2rem;
    right: 2rem;
    z-index: 10;
    text-align: right;
  }
  .portfolio-nav-btn {
    background: none;
    border: none;
    color: #222;
    font-size: 0.95rem;
    letter-spacing: 0.15em;
    text-transform: uppercase;
    font-weight: 500;
    cursor: pointer;
    display: flex;
    align-items: center;
    gap: 0.3em;
  }
  .portfolio-nav-dropdown {
    background: #181818;
    color: #fff;
    margin-top: 0.5rem;
    border-radius: 0.15rem;
    box-shadow: 0 2px 8px rgba(0,0,0,0.08);
    padding: 0.5rem 1.5rem 0.5rem 1.5rem;
    min-width: 150px;
    text-align: left;
    position: absolute;
    right: 0;
    display: none;
    flex-direction: column;
  }
  .portfolio-nav.open .portfolio-nav-dropdown { display: flex; }
  .portfolio-nav-dropdown a {
    display: block;
    color: #fff;
    padding: 0.3em 0;
    text-decoration: none;
    font-size: 1.1em;
    font-weight: 400;
    letter-spacing: 0.05em;
    transition: color 0.2s;
  }
  .portfolio-nav-dropdown a.active {
    color: #e57373;
    font-weight: 600;
  }
  .portfolio-nav-dropdown a:not(.active):hover {
    color: #60a5fa;
  }
  .portfolio-nav-icon {
    font-size: 1.2em;
    margin-left: 0.2em;
    vertical-align: middle;
  }
</style>

<section class="min-h-screen bg-[#ede7df] px-8 py-8 relative">
  <div class="max-w-7xl mx-auto">
    <div class="portfolio-nav">
      <button class="portfolio-nav-btn" tabindex="0">
        PORTFOLIO
        <svg class="portfolio-nav-icon" xmlns="http://www.w3.org/2000/svg" width="18" height="18" fill="none" viewBox="0 0 24 24"><path stroke="#222" stroke-width="2" d="M4 8l8 8 8-8"/></svg>
      </button>
      <div class="portfolio-nav-dropdown">
        <a href="portfolio-fineart.php">FINE ART</a>
        <a href="portfolio-portraits.php">PORTRAITS</a>
        <a href="portfolio-clients.php">CLIENTS</a>
        <a href="portfolio-travel.php" class="active">TRAVEL</a>
      </div>
    </div>
    <h1 class="mb-6 text-2xl md:text-3xl font-bold lowercase text-gray-900" style="font-family:serif;">travel</h1>
    <div id="travel-gallery"
         class="pswp-gallery grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-8">
      <?php if ($items): ?>
        <?php foreach ($items as $item): ?>
          <?php if ($item['media_type'] === 'image'): ?>
            <?php
              $imgPath = 'cms/' . htmlspecialchars($item['image_url']);
              $imgSize = @getimagesize($imgPath);
              $imgW = $imgSize ? $imgSize[0] : 1600;
              $imgH = $imgSize ? $imgSize[1] : 1200;
            ?>
            <a href="<?= $imgPath ?>"
               data-pswp-width="<?= $imgW ?>"
               data-pswp-height="<?= $imgH ?>"
               class="block overflow-hidden rounded-lg">
              <img src="<?= $imgPath ?>"
                   alt="<?= htmlspecialchars($item['title']) ?>"
                   class="w-full h-full object-cover" />
            </a>
          <?php elseif ($item['media_type'] === 'video'): ?>
            <div class="overflow-hidden rounded-lg">
              <video class="w-full h-full object-cover" controls>
                <source src="cms/<?= htmlspecialchars($item['image_url']) ?>"
                        type="video/mp4" />
              </video>
            </div>
          <?php elseif ($item['media_type'] === 'audio'): ?>
            <div class="overflow-hidden rounded-lg">
              <audio class="w-full" controls>
                <source src="cms/<?= htmlspecialchars($item['image_url']) ?>"
                        type="audio/mpeg" />
              </audio>
            </div>
          <?php endif; ?>
        <?php endforeach; ?>
      <?php else: ?>
        <p class="text-gray-500 col-span-4 text-center">
          No travel projects found.
        </p>
      <?php endif; ?>
    </div><!-- /gallery wrapper -->
  </div>
</section>

<script src="/assets/js/photoswipe.umd.min.js"></script>
<script src="/assets/js/photoswipe-lightbox.umd.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', () => {
  const lightbox = new PhotoSwipeLightbox({
    gallery:  '#travel-gallery',
    children: 'a',
    pswpModule: PhotoSwipe,
    bgOpacity: 1,
    showHideAnimationType: 'zoom',
    wheelToZoom: true,
    arrowKeys:  true,
    padding:    { top:40, bottom:40, left:40, right:40 }
  });
  lightbox.init();
});

document.addEventListener('DOMContentLoaded', function() {
  const nav = document.querySelector('.portfolio-nav');
  const btn = document.querySelector('.portfolio-nav-btn');
  btn.addEventListener('click', function(e) {
    e.stopPropagation();
    nav.classList.toggle('open');
  });
  document.addEventListener('click', function(e) {
    if (!nav.contains(e.target)) nav.classList.remove('open');
  });
});
</script>

<?php include 'includes/footer.php'; ?> 