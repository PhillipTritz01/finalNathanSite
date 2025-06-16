<?php
require_once 'cms/includes/config.php';

$page_title = "Clients Portfolio – Professional Photography & Cinematography";
$sticky_nav = false;
include 'includes/header.php';

$conn = getDBConnection();
$stmt = $conn->prepare("
    SELECT  p.*,
            GROUP_CONCAT(m.media_url ORDER BY m.display_order, m.id SEPARATOR '|||')  AS media_urls,
            GROUP_CONCAT(m.media_type ORDER BY m.display_order, m.id SEPARATOR '|||') AS media_types
      FROM  portfolio_items  p
 LEFT JOIN  portfolio_media  m ON p.id = m.portfolio_item_id
     WHERE  p.category = 'clients'
  GROUP BY  p.id
  ORDER BY  p.created_at DESC
");
$stmt->execute();
$items = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!-- PhotoSwipe + Macy.js -->
<link rel="stylesheet" href="public/assets/css/photoswipe.css">
<script src="public/assets/js/photoswipe.umd.min.js"></script>
<script src="public/assets/js/photoswipe-lightbox.umd.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/macy@2.5.1/dist/macy.min.js"></script>

<style>
  .pswp__bg { background:#fff !important; }
  .pswp__counter { color:#111; font-weight:600; font-size:1.05rem; }

  .portfolio-nav { position:relative; text-align:right; }
  .portfolio-nav-btn {
    background:none; border:none; color:#222;
    font-size:.95rem; letter-spacing:.15em;
    text-transform:uppercase; font-weight:500;
    cursor:pointer; display:flex; align-items:center; gap:.3em;
  }
  .portfolio-nav-dropdown {
    background:#181818; color:#fff; margin-top:.5rem;
    border-radius:.15rem; box-shadow:0 2px 8px rgb(0 0 0 /.08);
    padding:.5rem 1.5rem; min-width:150px; position:absolute;
    right:0; display:none; flex-direction:column; z-index:2001 !important;
  }
  .portfolio-nav.open .portfolio-nav-dropdown { display:flex; }
  .portfolio-nav-dropdown a {
    color:#fff; padding:.3em 0; text-decoration:none;
    font-size:1.1em; font-weight:400; letter-spacing:.05em;
    transition:color .2s;
  }
  .portfolio-nav-dropdown a:hover { color:#60a5fa; }
  .portfolio-nav-dropdown a.active { color:#e57373; font-weight:600; }

  .gallery-masonry {
    padding: 0.5rem;
  }
  .gallery-masonry img {
    width: 100%;
    display: block;
    margin-bottom: 0.5rem;
    border-radius: 10px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.08);
    background: #fff;
    transition: transform 0.35s ease, box-shadow 0.35s ease;
    will-change: transform;
  }
  .gallery-masonry img:hover {
    transform: scale(1.04);
    box-shadow: 0 8px 32px rgba(0,0,0,0.18);
    z-index: 2;
  }
  .gallery-masonry.resizing img {
    transform: scale(0.98) translateY(-6px);
  }
  .gallery-masonry.animation-rotate.resizing img {
    transform: rotate(-2deg) scale(0.99);
  }
  .gallery-masonry.animation-shadow.resizing img {
    box-shadow: 0 12px 32px rgba(0,0,0,0.22);
  }
  .gallery-masonry.animation-blur.resizing img {
    filter: blur(2px);
  }
  .gallery-masonry.animation-grayscale.resizing img {
    filter: grayscale(0.5);
  }
  .gallery-masonry.animation-brightness.resizing img {
    filter: brightness(1.15);
  }
  .gallery-masonry.animation-border.resizing img {
    border: 2px solid #bbb;
  }
  .gallery-hidden { opacity: 0; transition: opacity 0.3s; }
</style>

<section class="min-h-screen bg-[#ede7df] px-2 sm:px-4 pt-20 pb-8 relative">
  <div class="mx-auto">
    <div class="flex items-center justify-between mb-6">
      <h1 class="text-2xl md:text-3xl font-bold lowercase text-gray-900" style="font-family:serif;">clients</h1>
      <div class="portfolio-nav">
        <button class="portfolio-nav-btn" tabindex="0">
          PORTFOLIO
          <svg class="portfolio-nav-icon" xmlns="http://www.w3.org/2000/svg" width="18" height="18" fill="none" viewBox="0 0 24 24">
            <path stroke="#222" stroke-width="2" d="M4 8l8 8 8-8"/>
          </svg>
        </button>
        <div class="portfolio-nav-dropdown">
          <a href="portfolio-fineart.php">FINE&nbsp;ART</a>
          <a href="portfolio-portraits.php">PORTRAITS</a>
          <a href="portfolio-clients.php" class="active">CLIENTS</a>
          <a href="portfolio-travel.php">TRAVEL</a>
        </div>
      </div>
    </div>

    <div id="clients-gallery" class="gallery-masonry gallery-hidden">
<?php foreach ($items as $item):
  $urls  = $item['media_urls']  ? explode('|||', $item['media_urls'])  : [];
  $types = $item['media_types'] ? explode('|||', $item['media_types']) : [];
  foreach ($urls as $idx => $url):
    $type = $types[$idx] ?? 'image';
    $imgPath = 'cms/' . htmlspecialchars($url);
    if ($type === 'image'):
      [$w, $h] = @getimagesize($imgPath) ?: [1600, 1200]; ?>
      <a href="<?= $imgPath ?>"
         data-pswp-width="<?= $w ?>" data-pswp-height="<?= $h ?>"
         title="<?= htmlspecialchars($item['title']) ?>">
        <img src="<?= $imgPath ?>" alt="<?= htmlspecialchars($item['title']) ?>" />
      </a>
<?php elseif ($type === 'video'): ?>
      <div>
        <video style="width:100%;border-radius:8px;box-shadow:0 2px 8px rgba(0,0,0,0.08);margin-bottom:1rem;" controls>
          <source src="<?= $imgPath ?>" type="video/mp4">
        </video>
      </div>
<?php elseif ($type === 'audio'): ?>
      <div style="margin-bottom:1rem;">
        <audio style="width:100%;" controls>
          <source src="<?= $imgPath ?>" type="audio/mpeg">
        </audio>
      </div>
<?php endif; endforeach; endforeach; ?>
    </div>
  </div>
</section>

<script>
document.addEventListener('DOMContentLoaded', () => {
  const lightbox = new PhotoSwipeLightbox({
    gallery: '#clients-gallery',
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
  const btn = nav.querySelector('.portfolio-nav-btn');
  btn.addEventListener('click', e => {
    e.stopPropagation();
    nav.classList.toggle('open');
  });
  document.addEventListener('click', e => {
    if (!nav.contains(e.target)) nav.classList.remove('open');
  });

  const gallery = document.querySelector('.gallery-masonry');
  const anim = localStorage.getItem('galleryAnimation') || 'rotate';
  gallery.classList.add('animation-' + anim);

  const style = document.createElement('style');
  style.innerHTML = `@media (min-width: 601px) { .gallery-masonry { column-count: initial !important; } }`;
  document.head.appendChild(style);

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

  let loaded = 0;
  function showGallery() {
    gallery.classList.remove('gallery-hidden');
    // Add gallery animation class from CMS settings
    const anim = localStorage.getItem('galleryAnimation') || 'rotate';
    gallery.classList.add('animation-' + anim);
  }
  const images = gallery.querySelectorAll('img');
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

  let resizeTimeout;
  window.addEventListener('resize', () => {
    gallery.classList.add('resizing');
    clearTimeout(resizeTimeout);
    resizeTimeout = setTimeout(() => {
      gallery.classList.remove('resizing');
    }, 500);
  });
});
</script>

<?php include 'includes/footer.php'; ?>
