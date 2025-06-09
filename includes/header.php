<?php
/* ------------------------------------------------------------------
 *  includes/header.php
 * ------------------------------------------------------------------ */
?>
<!DOCTYPE html>
<html lang="en-US">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title><?= $page_title ?? 'Professional Photography'; ?></title>

  <!-- Tailwind & Icons -->
  <script src="https://cdn.tailwindcss.com"></script>
  <link rel="stylesheet"
        href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css"/>

  <style>
/* ------------ helpers ------------ */
.nav-link{position:relative;}
.nav-link::after{
  content:'';position:absolute;left:0;bottom:-4px;width:0;height:2px;
  background:currentColor;transition:width .3s ease;
}
.nav-link:hover::after,.nav-link.active::after{width:100%;}

/* white text + black outline for transparent bar */
.outlined-white{
  color:#fff !important;
  /* four-way shadow → faux stroke (works everywhere) */
  text-shadow:
    -1px -1px 0 #000,  1px -1px 0 #000,
    -1px  1px 0 #000,  1px  1px 0 #000;
}

/* ---------- Desktop dropdown ---------- */
.dropdown{position:relative;}
.dropdown>button{display:inline-flex;align-items:center;gap:.25rem;}
.dropdown>button .chev{transition:transform .3s ease;}
.dropdown:hover>button .chev{transform:rotate(180deg);}

.dropdown-content{
  @apply bg-white min-w-[200px] rounded shadow;
  position:absolute;top:calc(100% + 4px);left:0;padding:.5rem 0;
  opacity:0;visibility:hidden;transform:translateY(10px);
  transition:opacity .3s ease,transform .3s ease,visibility .3s;z-index:1000;
  background:#374151;
}
.dropdown:hover .dropdown-content{opacity:1;visibility:visible;transform:translateY(0);}
.dropdown-content a{display:block;padding:.5rem 1rem;color:#fff;}
.dropdown-content a:hover{background:#2563eb;}

/* ---------- Mobile off-canvas panel ---------- */
.mobile-menu{
  display:none;position:fixed;inset:0;padding:2rem;background:#fff;z-index:1000;
  transform:translateX(-100%);transition:transform .3s ease-in-out;
}
.mobile-menu.active{transform:translateX(0);}

/* ---------- Mobile styles ---------- */
@media (max-width:768px){
  .desktop-menu{display:none !important;}
  .mobile-menu{display:block;}
  #mobile-menu-button{display:block;}

  .mobile-dropdown-content{max-height:0;overflow:hidden;transition:max-height .4s ease;}
  .mobile-dropdown.open .mobile-dropdown-content{max-height:500px;}

  .mobile-dropdown-content a{
    opacity:0;transform:translateY(5px);
    transition:opacity .3s,transform .3s;color:#1f2937;
  }
  .mobile-dropdown.open .mobile-dropdown-content a{opacity:1;transform:translateY(0);}
  .mobile-dropdown .chev{transition:transform .3s;}
  .mobile-dropdown.open .chev{transform:rotate(180deg);}
  .mobile-menu .dropdown-content{position:static;box-shadow:none;padding-left:1rem;
    opacity:1 !important;visibility:visible !important;transform:none !important;}
}
@media (min-width:769px){
  #mobile-menu-button{display:none !important;}
}
/* hide desktop nav while panel open */
.mobile-menu.active~header .desktop-menu,
.mobile-menu.active~header #mobile-menu-button{display:none !important;}
  </style>
</head>
<body class="bg-gray-50">

<?php
/* ---------------------------------------------------------------
 *  Pass $transparent_nav = true in a page BEFORE including header
 *  to get the overlay version.
 * ------------------------------------------------------------- */
$transparent_nav = $transparent_nav ?? false;

/* common classes for desktop links */
$baseLink = 'nav-link transition';
$deskLink = $transparent_nav
            ? "$baseLink outlined-white hover:text-gray-100"
            : "$baseLink text-black hover:text-gray-700";

/* burger / close icon colour */
$iconColour = $transparent_nav ? 'outlined-white' : 'text-black';
?>

<!-- ---------- NAVBAR ---------- -->
<header class="<?= $transparent_nav
        ? 'fixed inset-x-0 top-0 z-50 bg-transparent shadow-none'
        : 'fixed inset-x-0 top-0 z-50 bg-white/80 backdrop-blur-md shadow-sm'; ?>">
  <div class="container mx-auto px-4">
    <nav class="py-4 relative">

      <!-- Desktop nav -->
      <ul class="desktop-menu flex items-center justify-between space-x-8">
        <li><a href="index.php"               class="<?= $deskLink; ?>">Home</a></li>

        <li class="dropdown">
          <a href="#" tabindex="-1" class="<?= $deskLink; ?> cursor-default select-none">
            Portfolio <i class="fas fa-chevron-down ml-1 text-base chev"></i>
          </a>
          <ul class="dropdown-content">
            <li><a href="portfolio-fineart.php">Fine Art</a></li>
            <li><a href="portfolio-portraits.php">Portraits</a></li>
            <li><a href="portfolio-clients.php">Clients</a></li>
            <li><a href="portfolio-travel.php">Travel</a></li>
          </ul>
        </li>

        <li><a href="about.php"   class="<?= $deskLink; ?>">About</a></li>
        <li><a href="contact.php" class="<?= $deskLink; ?>">Contact</a></li>
      </ul>

      <!-- Burger -->
      <button id="mobile-menu-button"
              class="md:hidden absolute right-4 top-4 <?= $iconColour; ?>">
        <i class="fas fa-bars text-2xl"></i>
      </button>

    </nav>
  </div>
</header>

<!-- ---------- MOBILE PANEL ---------- -->
<div id="mobile-menu" class="mobile-menu">
  <button id="close-menu"
          class="absolute right-4 top-4 text-black hover:text-gray-700 transition">
    <i class="fas fa-times text-2xl"></i>
  </button>

  <ul class="space-y-6 mt-12">
    <li><a href="index.php" class="block text-2xl text-black hover:text-gray-700">Home</a></li>

    <li class="mobile-dropdown relative">
      <a href="#" tabindex="-1"
         class="block text-2xl text-black hover:text-gray-700 mobile-dropdown-toggle cursor-default select-none">
        Portfolio <i class="fas fa-chevron-down ml-2 text-base chev"></i>
      </a>
      <ul class="mobile-dropdown-content pl-4 mt-2 space-y-2">
        <li><a href="portfolio-fineart.php"   class="block text-xl text-gray-600 hover:text-gray-700">Fine Art</a></li>
        <li><a href="portfolio-portraits.php" class="block text-xl text-gray-600 hover:text-gray-700">Portraits</a></li>
        <li><a href="portfolio-clients.php"   class="block text-xl text-gray-600 hover:text-gray-700">Clients</a></li>
        <li><a href="portfolio-travel.php"    class="block text-xl text-gray-600 hover:text-gray-700">Travel</a></li>
      </ul>
    </li>

    <li><a href="about.php"   class="block text-2xl text-black hover:text-gray-700">About</a></li>
    <li><a href="contact.php" class="block text-2xl text-black hover:text-gray-700">Contact</a></li>
  </ul>
</div>

<!-- ---------- SCRIPTS ---------- -->
<script>
/* slide-out panel */
const mobileMenuBtn = document.getElementById('mobile-menu-button');
const closeMenuBtn  = document.getElementById('close-menu');
const mobileMenu    = document.getElementById('mobile-menu');

function toggleMenu(){
  mobileMenu.classList.toggle('active');
  document.body.style.overflow = mobileMenu.classList.contains('active') ? 'hidden' : '';
}
mobileMenuBtn.addEventListener('click', toggleMenu);
closeMenuBtn .addEventListener('click', toggleMenu);

/* close panel after link click */
mobileMenu.querySelectorAll('a').forEach(link=>{
  link.addEventListener('click', ()=>{
    if(mobileMenu.classList.contains('active')) toggleMenu();
  });
});

/* dropdown toggle for tap */
const mobileDropdown       = document.querySelector('.mobile-dropdown');
const mobileDropdownToggle = document.querySelector('.mobile-dropdown-toggle');

function toggleMobileDropdown(e){
  e.preventDefault();
  mobileDropdown.classList.toggle('open');
}
mobileDropdownToggle.addEventListener('click',      toggleMobileDropdown);
mobileDropdownToggle.addEventListener('touchstart', toggleMobileDropdown);

/* close dropdown if you click outside */
document.addEventListener('click', e=>{
  if(
    mobileDropdown.classList.contains('open') &&
    !mobileDropdown.contains(e.target) &&
    !mobileDropdownToggle.contains(e.target)
  ){
    mobileDropdown.classList.remove('open');
  }
});
</script>

<!-- Main wrapper: only pushes content when header is NOT transparent -->
<main class="<?= $transparent_nav ? '' : 'pt-16'; ?>"><!-- page content --></main>
</body>
</html>