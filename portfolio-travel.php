<?php
/* ───────────────────────────────────────── */
/* Travel Portfolio – PhotoSwipe v5 (local)  */
/* ───────────────────────────────────────── */

require_once 'cms/includes/config.php';

$page_title = "Travel Portfolio – Professional Photography & Cinematography";
 $sticky_nav = false;  // uncomment if you want the nav bar non-sticky */
include 'includes/header.php';

/* pull all travel items (with any attached media) */
$conn = getDBConnection();
$stmt = $conn->prepare("
    SELECT p.*,
           GROUP_CONCAT(m.media_url ORDER BY m.display_order, m.id SEPARATOR '|||')  AS media_urls,
           GROUP_CONCAT(m.media_type ORDER BY m.display_order, m.id SEPARATOR '|||') AS media_types
      FROM portfolio_items  p
 LEFT JOIN portfolio_media  m ON p.id = m.portfolio_item_id
     WHERE p.category = 'travel'
  GROUP BY p.id
  ORDER BY p.created_at DESC
");
$stmt->execute();
$items = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<link  rel="stylesheet" href="public/assets/css/photoswipe.css">
<script src="public/assets/js/photoswipe.umd.min.js"></script>
<script src="public/assets/js/photoswipe-lightbox.umd.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/macy@2.5.1/dist/macy.min.js"></script>

<style>
  /* PhotoSwipe tweaks */
  .pswp__bg      { background:#fff !important; }
  .pswp__counter { color:#111 !important; font-weight:600; font-size:1.05rem; }

  /* dropdown (same as clients) */
  .portfolio-nav          { position:relative; text-align:right; }
  .portfolio-nav-btn      { background:none; border:none; color:#222;
                             font-size:.95rem; letter-spacing:.15em;
                             text-transform:uppercase; font-weight:500;
                             cursor:pointer; display:flex; align-items:center; gap:.3em; }
  .portfolio-nav-dropdown { background:#181818; color:#fff; margin-top:.5rem;
                             border-radius:.15rem; box-shadow:0 2px 8px rgb(0 0 0 /.08);
                             padding:.5rem 1.5rem; min-width:150px; position:absolute;
                             right:0; display:none; flex-direction:column; z-index:2001 !important; }
  .portfolio-nav.open .portfolio-nav-dropdown { display:flex; }
  .portfolio-nav-dropdown a       { color:#fff; padding:.3em 0; text-decoration:none;
                                     font-size:1.1em; font-weight:400; letter-spacing:.05em;
                                     transition:color .2s; }
  .portfolio-nav-dropdown a:hover { color:#60a5fa; }
  .portfolio-nav-dropdown a.active{ color:#e57373; font-weight:600; }

  .gallery-zoom img {
    transition: transform 0.4s cubic-bezier(.4,0,.2,1), box-shadow 0.4s cubic-bezier(.4,0,.2,1);
    will-change: transform;
  }
  .gallery-zoom a:hover img,
  .gallery-zoom a:focus img {
    transform: scale(1.07);
    box-shadow: 0 8px 32px rgba(0,0,0,0.18);
    z-index: 2;
  }

  /* Masonry gallery style */
  .gallery-masonry { padding: 0.5rem; }
  .gallery-masonry img { width: 100%; display: block; margin-bottom: 0.5rem; border-radius: 10px; box-shadow: 0 2px 8px rgba(0,0,0,0.08); background: #fff; transition: transform 0.35s ease, box-shadow 0.35s ease; will-change: transform; }
  .gallery-masonry img:hover { transform: scale(1.04); box-shadow: 0 8px 32px rgba(0,0,0,0.18); z-index: 2; }
  @media (max-width: 1200px) { .gallery-masonry { padding: 0.5rem; } }

  .gallery-hidden { opacity: 0; transition: opacity 0.3s; }
</style>

<!-- identical spacing & layout as clients -->
<section class="min-h-screen bg-[#ede7df] px-2 sm:px-4 pt-20 pb-8 relative">
  <div class="mx-auto"><!-- full-width (no max-w) -->

    <!-- heading left, dropdown right -->
    <div class="flex items-center justify-between mb-6">
      <h1 class="text-2xl md:text-3xl font-bold lowercase text-gray-900"
          style="font-family:serif;">travel</h1>

      <div class="portfolio-nav">
        <button class="portfolio-nav-btn">
          PORTFOLIO
          <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18"
               fill="none" viewBox="0 0 24 24"><path stroke="#222" stroke-width="2" d="M4 8l8 8 8-8"/></svg>
        </button>
        <div class="portfolio-nav-dropdown">
          <a href="portfolio-fineart.php">FINE ART</a>
          <a href="portfolio-portraits.php">PORTRAITS</a>
          <a href="portfolio-clients.php">CLIENTS</a>
          <a href="portfolio-travel.php" class="active">TRAVEL</a>
        </div>
      </div>
    </div>

    <!-- responsive gallery grid -->
    <div id="travel-gallery" class="gallery-masonry gallery-hidden">
<?php if ($items): ?>
<?php foreach ($items as $item):
        $urls  = $item['media_urls']  ? explode('|||', $item['media_urls'])  : [];
        $types = $item['media_types'] ? explode('|||', $item['media_types']) : [];

        foreach ($urls as $i => $url):
          $type = $types[$i] ?? 'image';
          $path = 'cms/' . htmlspecialchars($url);

          if ($type === 'image'):
            [$w,$h] = @getimagesize($path) ?: [1600,1200]; ?>
      <a href="<?= $path ?>" data-pswp-width="<?= $w ?>" data-pswp-height="<?= $h ?>">
        <img src="<?= $path ?>" alt="<?= htmlspecialchars($item['title']) ?>">
      </a>

<?php   elseif ($type === 'video'): ?>
      <div>
        <video style="width:100%;border-radius:8px;box-shadow:0 2px 8px rgba(0,0,0,0.08);margin-bottom:1rem;" controls>
          <source src="<?= $path ?>" type="video/mp4">
        </video>
      </div>

<?php   elseif ($type === 'audio'): ?>
      <div style="margin-bottom:1rem;">
        <audio style="width:100%;" controls>
          <source src="<?= $path ?>" type="audio/mpeg">
        </audio>
      </div>
<?php   endif; endforeach; endforeach;
      else: ?>
      <p class="text-gray-500 col-span-4 text-center">No travel projects found.</p>
<?php endif; ?>
    </div><!-- /gallery -->
  </div>
</section>

<script>
document.addEventListener('DOMContentLoaded', () => {
  const lightbox = new PhotoSwipeLightbox({
    gallery: '#travel-gallery',
    children: 'a',
    pswpModule: PhotoSwipe,
    padding: { top: 40, bottom: 40, left: 40, right: 40 },
    bgOpacity: 1,
    zoom: false,
    wheelToZoom: false,
    arrowKeys: true,
    showHideAnimationType: 'zoom',
    transition: true
  });
  lightbox.on('contentClickAction', (e, slide) => {
    lightbox.pswp.close();
    return false;
  });
  lightbox.init();

  const nav = document.querySelector('.portfolio-nav');
  nav.querySelector('.portfolio-nav-btn').addEventListener('click', e => {
    e.stopPropagation(); nav.classList.toggle('open');
  });
  document.addEventListener('click', e => {
    if (!nav.contains(e.target)) nav.classList.remove('open');
  });
});

document.addEventListener('DOMContentLoaded', function () {
  const gallery = document.querySelector('.gallery-masonry');
  // Add gallery animation class from CMS settings BEFORE Macy.js
  const anim = localStorage.getItem('galleryAnimation') || 'rotate';
  gallery.classList.add('animation-' + anim);
  const macy = Macy({
    container: '.gallery-masonry',
    trueOrder: true,
    waitForImages: false,
    margin: 2,
    columns: 4,
    breakAt: {
      1200: 3,
      900: 2,
      600: 1
    }
  });
  const images = gallery.querySelectorAll('img');
  let loaded = 0;
  function showGallery() {
    gallery.classList.remove('gallery-hidden');
    // Add gallery animation class from CMS settings
    const anim = localStorage.getItem('galleryAnimation') || 'rotate';
    gallery.classList.add('animation-' + anim);
  }
  images.forEach(img => {
    if (img.complete) {
      loaded++;
      if (loaded === images.length) {
        macy.recalculate(true);
        showGallery();
      }
    } else {
      img.addEventListener('load', () => {
        loaded++;
        if (loaded === images.length) {
          macy.recalculate(true);
          showGallery();
        }
      });
    }
  });
});
</script>

<?php include 'includes/footer.php'; ?>
