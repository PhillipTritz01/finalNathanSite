<?php
/*  ────────────────────────────────────────────────────────────── */
/*  Clients Portfolio – PhotoSwipe v5 (local assets)              */
/*  ────────────────────────────────────────────────────────────── */

require_once 'cms/includes/config.php';

$page_title = "Clients Portfolio – Professional Photography & Cinematography";
/*  Uncomment next line if you want a NON-sticky nav bar on this page  */
  $sticky_nav = false;                                              
include 'includes/header.php';

/* 1. Pull client records + media ---------------------------------- */
$conn = getDBConnection();
$stmt = $conn->prepare("
    SELECT  p.*,
            GROUP_CONCAT(m.media_url)  AS media_urls,
            GROUP_CONCAT(m.media_type) AS media_types
      FROM  portfolio_items  p
 LEFT JOIN  portfolio_media  m ON p.id = m.portfolio_item_id
     WHERE  p.category = 'clients'
  GROUP BY  p.id
  ORDER BY  p.created_at DESC
");
$stmt->execute();
$items = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!-- ───────── PhotoSwipe CSS / JS (LOCAL) ───────── -->
<link  rel="stylesheet" href="public/assets/css/photoswipe.css">
<script src="public/assets/js/photoswipe.umd.min.js"></script>
<script src="public/assets/js/photoswipe-lightbox.umd.min.js"></script>

<style>
  /* PhotoSwipe minor tweaks */
  .pswp__bg      { background:#fff !important; }
  .pswp__counter { color:#111 !important; font-weight:600; font-size:1.05rem; }

  /* Portfolio drop-down styling */
  .portfolio-nav          { position:relative; text-align:right; }
  .portfolio-nav-btn      { background:none; border:none; color:#222;
                             font-size:.95rem; letter-spacing:.15em;
                             text-transform:uppercase; font-weight:500;
                             cursor:pointer; display:flex; align-items:center; gap:.3em; }
  .portfolio-nav-dropdown { background:#181818; color:#fff; margin-top:.5rem;
                             border-radius:.15rem; box-shadow:0 2px 8px rgb(0 0 0 /.08);
                             padding:.5rem 1.5rem; min-width:150px; position:absolute;
                             right:0; display:none; flex-direction:column; }
  .portfolio-nav.open .portfolio-nav-dropdown{ display:flex; }
  .portfolio-nav-dropdown a          { color:#fff; padding:.3em 0; text-decoration:none;
                                        font-size:1.1em; font-weight:400; letter-spacing:.05em;
                                        transition:color .2s; }
  .portfolio-nav-dropdown a:hover    { color:#60a5fa; }
  .portfolio-nav-dropdown a.active   { color:#e57373; font-weight:600; }

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
</style>

<!-- top padding trimmed to 80 px (pt-20) -->
<section class="min-h-screen bg-[#ede7df] px-2 sm:px-4 pt-20 pb-8 relative">
  <div class="mx-auto"><!-- no max-width: gallery spans full width -->

    <!-- heading + dropdown in one line -->
    <div class="flex items-center justify-between mb-6">
      <h1 class="text-2xl md:text-3xl font-bold lowercase text-gray-900"
          style="font-family:serif;">clients</h1>

      <div class="portfolio-nav">
        <button class="portfolio-nav-btn" tabindex="0">
          PORTFOLIO
          <svg class="portfolio-nav-icon" xmlns="http://www.w3.org/2000/svg"
               width="18" height="18" fill="none" viewBox="0 0 24 24">
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

    <!-- responsive masonry-like grid -->
    <div id="clients-gallery"
         class="pswp-gallery gallery-zoom grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-8">

<?php if ($items): ?>
  <?php foreach ($items as $item):
        $urls  = $item['media_urls']  ? explode(',', $item['media_urls'])  : [];
        $types = $item['media_types'] ? explode(',', $item['media_types']) : [];

        foreach ($urls as $idx => $url):
          $type = $types[$idx] ?? 'image';

          /* ——— IMAGES ——— */
          if ($type === 'image'):
            $imgPath = 'cms/' . htmlspecialchars($url);
            [$w,$h]  = @getimagesize($imgPath) ?: [1600,1200];
  ?>
            <a href="<?= $imgPath ?>"
               data-pswp-width="<?= $w ?>" data-pswp-height="<?= $h ?>"
               class="block overflow-hidden rounded-lg">
              <img src="<?= $imgPath ?>" alt="<?= htmlspecialchars($item['title']) ?>"
                   class="w-full h-auto object-contain" />
            </a>

  <?php /* ——— VIDEOS ——— */
          elseif ($type === 'video'): ?>
            <div class="overflow-hidden rounded-lg">
              <video class="w-full h-auto object-contain" controls>
                <source src="cms/<?= htmlspecialchars($url) ?>" type="video/mp4">
              </video>
            </div>

  <?php /* ——— AUDIO ——— */
          elseif ($type === 'audio'): ?>
            <div class="overflow-hidden rounded-lg">
              <audio class="w-full" controls>
                <source src="cms/<?= htmlspecialchars($url) ?>" type="audio/mpeg">
              </audio>
            </div>
  <?php endif; endforeach; endforeach; ?>

<?php else: ?>
      <p class="text-gray-500 col-span-4 text-center">No client projects found.</p>
<?php endif; ?>

    </div><!-- /gallery -->
  </div>
</section>

<script>
/* ---------- PhotoSwipe ---------- */
document.addEventListener('DOMContentLoaded', () => {
  const lightbox = new PhotoSwipeLightbox({
    gallery:  '#clients-gallery',
    children: 'a',
    pswpModule: PhotoSwipe,
    padding: { top:40, bottom:40, left:40, right:40 },
    bgOpacity: 1,
    wheelToZoom: true,
    arrowKeys:   true
  });
  lightbox.init();
});

/* ---------- PORTFOLIO drop-down toggle ---------- */
document.addEventListener('DOMContentLoaded', () => {
  const nav = document.querySelector('.portfolio-nav');
  const btn = nav.querySelector('.portfolio-nav-btn');

  btn.addEventListener('click', e => {
    e.stopPropagation();
    nav.classList.toggle('open');
  });
  document.addEventListener('click', e => {
    if (!nav.contains(e.target)) nav.classList.remove('open');
  });
});
</script>

<?php include 'includes/footer.php'; ?>
