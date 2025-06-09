<?php
require_once 'cms/includes/config.php';
$page_title = "Professional Photography & Cinematography Services";
$transparent_nav = true;
include 'includes/header.php';

// Fetch the first image from each portfolio category
define('PORTFOLIO_CATEGORIES', ['fineart', 'portraits', 'clients', 'travel']);
$slideshow_images = [];
$conn = getDBConnection();
foreach (PORTFOLIO_CATEGORIES as $cat) {
  $stmt = $conn->prepare("SELECT image_url FROM portfolio_items WHERE category = ? AND media_type = 'image' ORDER BY created_at DESC LIMIT 1");
  $stmt->execute([$cat]);
  $img = $stmt->fetchColumn();
  if ($img) $slideshow_images[] = 'cms/' . htmlspecialchars($img);
}
?>

<!-- Hero Section with Slideshow -->
<section class="relative h-screen overflow-hidden">
  <div id="hero-slideshow" class="absolute inset-0 w-full h-full z-0">
    <?php foreach ($slideshow_images as $i => $img): ?>
      <img src="<?= $img ?>" class="slideshow-img absolute inset-0 w-full h-full object-cover transition-opacity duration-1000 <?php if ($i !== 0) echo 'opacity-0'; ?>" style="object-position:center;" alt="Slideshow Image <?= $i+1 ?>">
    <?php endforeach; ?>
    <div class="absolute inset-0 bg-black/60"></div>
  </div>
  <div class="relative container mx-auto px-4 h-full flex items-center z-10">
    <div class="max-w-3xl text-white">
      <h1 class="text-6xl font-bold mb-6 leading-tight">Capturing Your Vision Through Lens</h1>
      <p class="text-2xl mb-8 text-gray-200">Professional photography and cinematography services that bring your stories to life.</p>
      <div class="flex gap-4">
        <a href="portfolio.php" class="inline-block bg-white text-gray-900 px-8 py-4 rounded-full text-lg font-semibold hover:bg-gray-100 transition">View Portfolio</a>
        <a href="contact.php" class="inline-block bg-blue-600 text-white px-8 py-4 rounded-full text-lg font-semibold hover:bg-blue-700 transition">Contact Us</a>
      </div>
    </div>
  </div>
</section>

<script>
// Simple JS slideshow
const slides = document.querySelectorAll('.slideshow-img');
let current = 0;
setInterval(() => {
  slides[current].classList.add('opacity-0');
  current = (current + 1) % slides.length;
  slides[current].classList.remove('opacity-0');
}, 5000);
</script>

<?php include 'includes/footer.php'; ?> 