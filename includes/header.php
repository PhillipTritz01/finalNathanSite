<?php
/* --------------------------------------------------------------
 *  includes/header.php
 * -------------------------------------------------------------- */
?>
<!DOCTYPE html>
<html lang="en-US">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title><?= $page_title ?? 'Professional Photography'; ?></title>

  <!-- Tailwind + Icons -->
  <script src="https://cdn.tailwindcss.com"></script>
  <link  href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css"
         rel="stylesheet"/>

  <style>
/* ---------- shared link underline ---------- */
.nav-link{position:relative;}
.nav-link::after{
  content:'';position:absolute;left:0;bottom:-4px;width:0;height:2px;
  background:currentColor;transition:width .3s;
}
.nav-link:hover::after,.nav-link.active::after{width:100%;}

/* ---------- outlined-white (for transparent hero) ---------- */
.outlined-white{
  color:#fff !important;
  text-shadow:-1px -1px 0 #000,1px -1px 0 #000,-1px 1px 0 #000,1px 1px 0 #000;
}

/* ---------- desktop dropdown ---------- */
.dropdown{position:relative;}
.dropdown>button{display:inline-flex;align-items:center;gap:.25rem;}
.dropdown>button .chev{transition:transform .3s;}
.dropdown:hover>button .chev{transform:rotate(180deg);}
.dropdown-content{
  @apply bg-white min-w-[200px] rounded shadow;
  position:absolute;top:calc(100% + 4px);left:0;padding:.5rem 0;
  opacity:0;visibility:hidden;transform:translateY(10px);
  transition:opacity .3s,transform .3s,visibility .3s;z-index:1000;
  background:#374151;
}
.dropdown:hover .dropdown-content{opacity:1;visibility:visible;transform:translateY(0);}
.dropdown-content a{display:block;padding:.5rem 1rem;color:#fff;}
.dropdown-content a:hover{background:#2563eb;}

/* ---------- mobile slide-out panel ---------- */
.mobile-menu{
  display:none;position:fixed;inset:0;padding:2rem;background:#fff;z-index:1000;
  transform:translateX(-100%);transition:transform .3s ease-in-out;
}
.mobile-menu.active{transform:translateX(0);}

/* ---------- responsive visibility ---------- */
@media(max-width:768px){
  .desktop-menu{display:none !important;}
  .mobile-menu{display:block;}
  #mobile-menu-button{display:block;}

  /* collapsed portfolio submenu */
  .mobile-dropdown-content{max-height:0;overflow:hidden;transition:max-height .4s ease;}
  .mobile-dropdown.open .mobile-dropdown-content{max-height:500px;}

  /* link fade-in animation */
  .mobile-dropdown-content a{
    opacity:0;transform:translateY(5px);
    transition:opacity .3s,transform .3s;color:#1f2937;
  }
  .mobile-dropdown.open .mobile-dropdown-content a{opacity:1;transform:translateY(0);}

  .mobile-dropdown .chev{transition:transform .3s;}
  .mobile-dropdown.open .chev{transform:rotate(180deg);}

  /* keep submenu visible inside panel */
  .mobile-menu .dropdown-content{
    position:static;box-shadow:none;padding-left:1rem;
    opacity:1 !important;visibility:visible !important;transform:none !important;
  }
}
@media(min-width:769px){
  #mobile-menu-button{display:none !important;}
}

/* hide desktop nav while panel open */
.mobile-menu.active~header .desktop-menu,
.mobile-menu.active~header #mobile-menu-button{display:none !important;}

/* ---------- NEW: hover-to-open submenu (desktop pointer) ---------- */
@media (hover:hover) and (pointer:fine) {
  .mobile-dropdown:hover .mobile-dropdown-content{max-height:500px;}
  .mobile-dropdown:hover .mobile-dropdown-content a{opacity:1;transform:translateY(0);}
  .mobile-dropdown:hover .chev{transform:rotate(180deg);}
}
  </style>
</head>
<body class="bg-gray-50">

<?php
/* ---------------------------------------------------------------
 *  Per-page flags (provide before including header.php)
 * ------------------------------------------------------------- */
$transparent_nav = $transparent_nav ?? false;  // hero overlay?
$sticky_nav      = $sticky_nav      ?? true;   // stick to top?

/* link + icon colour */
$baseLink = 'nav-link transition';
$deskLink = $transparent_nav
            ? "$baseLink outlined-white hover:text-gray-100"
            : "$baseLink text-black hover:text-gray-700";
$iconClr  = $transparent_nav ? 'outlined-white' : 'text-black';

/* header positioning */
$headerPos = $sticky_nav ? 'fixed' : 'absolute';
$headerCls = "$headerPos inset-x-0 top-0 z-50 ".
             ($transparent_nav
              ? 'bg-transparent shadow-none'
              : 'bg-white/80 backdrop-blur-md shadow-sm');

/* padding for page content */
$mainPad = ($sticky_nav && !$transparent_nav) ? 'pt-16' : '';
?>

<!-- ---------- NAVBAR ---------- -->
<header class="<?= $headerCls; ?>">
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
              class="md:hidden absolute right-4 top-4 <?= $iconClr; ?>">
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
/* slide-out panel toggle */
const mobileMenuBtn = document.getElementById('mobile-menu-button');
const closeMenuBtn  = document.getElementById('close-menu');
const mobileMenu    = document.getElementById('mobile-menu');

function toggleMenu(open) {
  if (open === true)  mobileMenu.classList.add('active');
  else if (open===false) mobileMenu.classList.remove('active');
  else mobileMenu.classList.toggle('active');

  document.body.style.overflow =
    mobileMenu.classList.contains('active') ? 'hidden' : '';
}
mobileMenuBtn.addEventListener('click', toggleMenu);
closeMenuBtn .addEventListener('click', toggleMenu);

/* close panel after any link click */
mobileMenu.querySelectorAll('a').forEach(link=>{
  link.addEventListener('click', ()=> {
    if (mobileMenu.classList.contains('active')) toggleMenu(false);
  });
});

/* touch + click toggle for Portfolio submenu */
const mobileDropdown       = document.querySelector('.mobile-dropdown');
const mobileDropdownToggle = document.querySelector('.mobile-dropdown-toggle');
function toggleMobileDropdown(e){
  e.preventDefault();
  mobileDropdown.classList.toggle('open');
}
mobileDropdownToggle.addEventListener('touchstart', toggleMobileDropdown);
mobileDropdownToggle.addEventListener('click', toggleMobileDropdown);
</script>

<!-- page wrapper -->
<main class="<?= $mainPad; ?>"><!-- page content --></main>
</body>
</html>
