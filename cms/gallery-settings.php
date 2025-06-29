<?php
require_once 'includes/config.php';
require_once 'includes/upload_config.php';
?>
<!doctype html><html lang="en"><head>
<meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title>Gallery Settings – CMS Admin</title>

<!-- Apply theme immediately to prevent flash -->
<script>
(function() {
    const saved = localStorage.getItem('theme');
    if (saved) {
        document.documentElement.classList.add(saved + '-mode');
    } else if (window.matchMedia && window.matchMedia('(prefers-color-scheme: dark)').matches) {
        document.documentElement.classList.add('dark-mode');
    } else {
        document.documentElement.classList.add('light-mode');
    }
})();
</script>

<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">

<!-- Bootstrap Icons - Consolidated Loading -->
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">

<!-- Fallback for Bootstrap Icons -->
<script>
(function() {
    const link = document.querySelector('link[href*="bootstrap-icons"]');
    if (link) {
        link.onerror = function() {
            const fallback = document.createElement('link');
            fallback.rel = 'stylesheet';
            fallback.href = 'https://unpkg.com/bootstrap-icons@1.11.3/font/bootstrap-icons.css';
            document.head.appendChild(fallback);
        };
    }
})();
</script>
<style>
:root {
  --bg: #f8f9fa;
  --text: #222;
  --sidebar-bg: #343a40;
  --sidebar-text: #fff;
  --card-bg: #fff;
  --border: #dee2e6;
  --btn-bg: #0d6efd;
  --btn-text: #fff;
  --btn-border: #0a58ca;
  --btn-danger-bg: #dc3545;
  --btn-danger-text: #fff;
  --btn-danger-border: #b52a37;
  --input-bg: #fff;
  --input-text: #222;
}
body.dark-mode, html.dark-mode {
  --bg: #181a1b;
  --text: #e0e0e0;
  --sidebar-bg: #23272b;
  --sidebar-text: #fff;
  --card-bg: #23272b;
  --border: #444;
  --btn-bg: #2563eb;
  --btn-text: #fff;
  --btn-border: #1a4fb4;
  --btn-danger-bg: #e57373;
  --btn-danger-text: #fff;
  --btn-danger-border: #b52a37;
  --input-bg: #23272b;
  --input-text: #e0e0e0;
}
body, .content { background: var(--bg) !important; color: var(--text) !important; }
.sidebar { background: var(--sidebar-bg) !important; color: var(--sidebar-text) !important; min-height: 100vh; height: 100vh; }
.card, .portfolio-item { background: var(--card-bg) !important; border-color: var(--border) !important; color: var(--text) !important; }
.btn, .btn-primary {
  background-color: var(--btn-bg) !important;
  color: var(--btn-text) !important;
  border-color: var(--btn-border) !important;
}
.btn-primary:hover, .btn-primary:focus {
  background-color: var(--btn-border) !important;
  color: var(--btn-text) !important;
}
.btn-danger, .btn-outline-danger {
  background-color: var(--btn-danger-bg) !important;
  color: var(--btn-danger-text) !important;
  border-color: var(--btn-danger-border) !important;
}
.btn-outline-danger {
  background: transparent !important;
  color: var(--btn-danger-bg) !important;
}
.btn-outline-danger:hover, .btn-outline-danger:focus {
  background-color: var(--btn-danger-bg) !important;
  color: var(--btn-danger-text) !important;
}
input, textarea, select {
  background: var(--input-bg) !important;
  color: var(--input-text) !important;
  border-color: var(--border) !important;
}
input:focus, textarea:focus, select:focus {
  border-color: var(--btn-bg) !important;
  outline: none;
}
.form-label, label {
  color: var(--text) !important;
}
.theme-switch-wrap {
  position: absolute;
  top: 18px;
  right: 24px;
  z-index: 3000;
  display: flex;
  align-items: center;
  gap: 0.5rem;
  user-select: none;
}
.theme-switch-label {
  font-size: 1rem;
  color: var(--text);
  opacity: 0.7;
  letter-spacing: 0.05em;
  transition: color 0.3s, opacity 0.3s;
}
.theme-switch {
  width: 56px;
  height: 28px;
  background: #e0e0e0;
  border-radius: 14px;
  position: relative;
  cursor: pointer;
  transition: background 0.3s;
  display: flex;
  align-items: center;
}
body.dark-mode .theme-switch, html.dark-mode .theme-switch {
  background: #444;
}
.theme-switch-knob {
  position: absolute;
  top: 3px;
  left: 3px;
  width: 22px;
  height: 22px;
  background: #fff;
  border-radius: 50%;
  box-shadow: 0 1px 4px rgba(0,0,0,0.12);
  transition: left 0.3s cubic-bezier(.4,0,.2,1), background 0.3s;
}
body.dark-mode .theme-switch-knob, html.dark-mode .theme-switch-knob {
  left: 31px;
  background: #e0e0e0;
}
.theme-switch-knob::before {
  content: '';
  display: block;
  width: 10px;
  height: 10px;
  border-radius: 50%;
  background: #bbb;
  position: absolute;
  top: 6px;
  left: 6px;
  opacity: 0.5;
}
body.dark-mode .theme-switch-knob::before, html.dark-mode .theme-switch-knob::before {
  background: #222;
  opacity: 0.5;
}
.theme-switch:active .theme-switch-knob {
  box-shadow: 0 2px 8px rgba(0,0,0,0.18);
}
.gallery-settings-info {
  color: #444;
  opacity: 0.95;
  transition: color 0.3s;
}
body.dark-mode .gallery-settings-info, html.dark-mode .gallery-settings-info {
  color: #fff;
  opacity: 0.92;
}

/* Bootstrap Icons - Simplified */
.bi {
    font-family: "bootstrap-icons" !important;
    font-style: normal !important;
    font-weight: normal !important;
    line-height: 1 !important;
    -webkit-font-smoothing: antialiased !important;
    -moz-osx-font-smoothing: grayscale !important;
}
</style>
</head><body>
<div class="theme-switch-wrap">
  <span class="theme-switch-label">DARK</span>
  <div class="theme-switch" id="themeToggle">
    <div class="theme-switch-knob"></div>
  </div>
  <span class="theme-switch-label">LIGHT</span>
</div>
<div class="container-fluid"><div class="row">
<aside class="col-md-3 col-lg-2 sidebar p-3">
  <h3 class="mb-4">CMS</h3>
  <nav class="nav flex-column">
    <span class="nav-link text-white fw-bold">Portfolio Management</span>
    <a class="nav-link text-white" href="admin.php">Manage Portfolio</a>
    <a class="nav-link text-white" href="portfolio-overview.php">Portfolio Overview</a>
    <span class="nav-link text-white fw-bold">Gallery Settings</span>
    <span class="nav-link text-white fw-bold mt-3">Page Management</span>
    <a class="nav-link text-white" href="page-manager.php">Page Content</a>
                    <a class="nav-link text-white" href="slideshow-manager.php">Hero Slideshow</a>
    <span class="nav-link text-white fw-bold mt-3">System</span>
    <a class="nav-link text-white" href="security-status.php">Security Status</a>
    <a class="nav-link text-white" href="settings.php">Admin Settings</a>
    <a class="nav-link text-white" href="performance-monitor.php">Performance Monitor</a>
    <a class="nav-link text-white" href="maintenance.php">Maintenance</a>
    <a class="nav-link text-white" href="?logout=1">Logout</a>
  </nav>
</aside>
<main class="col-md-9 col-lg-10 content">
  <div class="card mt-4" style="max-width:500px;margin:auto;">
    <div class="card-body">
      <h4 class="card-title mb-3">Gallery Settings</h4>
      <div class="mb-3">
        <label for="galleryAnimation" class="form-label">Gallery Animation</label>
        <select id="galleryAnimation" class="form-select">
          <option value="rotate">Gentle Rotate</option>
          <option value="shadow">Shadow Pulse</option>
          <option value="blur">Gentle Blur</option>
          <option value="grayscale">Subtle Grayscale</option>
          <option value="brightness">Brightness Pulse</option>
          <option value="border">Border Pulse</option>
        </select>
      </div>
      <div class="mb-3">
        <label for="refreshAnimation" class="form-label">Refresh Animation</label>
        <select id="refreshAnimation" class="form-select">
          <option value="fade">Fade In</option>
          <option value="slideUp">Slide Up</option>
          <option value="slideDown">Slide Down</option>
          <option value="slideLeft">Slide Left</option>
          <option value="slideRight">Slide Right</option>
          <option value="zoom">Zoom In</option>
          <option value="bounce">Bounce In</option>
          <option value="flip">Flip In</option>
          <option value="rotate">Rotate In</option>
          <option value="elastic">Elastic</option>
        </select>
      </div>
      <div class="gallery-settings-info" style="font-size:0.97em;">Gallery animation is used for resizing the screen. Refresh animation is used when portfolio pages load. Refresh a portfolio page to see the effects.</div>
    </div>
  </div>
</main></div></div>
<script>
// Bootstrap Icons loaded via onerror fallback above
document.addEventListener('DOMContentLoaded', function() {
    // Icons should be loaded
});

// Theme toggle logic
function setTheme(mode) {
  document.documentElement.classList.remove('light-mode', 'dark-mode');
  document.body.classList.remove('light-mode', 'dark-mode');
  document.documentElement.classList.add(mode+'-mode');
  document.body.classList.add(mode+'-mode');
  localStorage.setItem('theme', mode);
}
function toggleTheme() {
  const isDark = document.body.classList.contains('dark-mode') || document.documentElement.classList.contains('dark-mode');
  setTheme(isDark ? 'light' : 'dark');
}
document.getElementById('themeToggle').onclick = toggleTheme;
(function(){
  const saved = localStorage.getItem('theme');
  if(saved) setTheme(saved);
  else if(window.matchMedia && window.matchMedia('(prefers-color-scheme: dark)').matches) setTheme('dark');
  else setTheme('light');
})();
// Gallery animation selector logic
const animSelect = document.getElementById('galleryAnimation');
if(animSelect) {
  animSelect.value = localStorage.getItem('galleryAnimation') || 'rotate';
  animSelect.onchange = function() {
    localStorage.setItem('galleryAnimation', this.value);
    alert('Gallery animation saved! Refresh a portfolio page to see the effect.');
  };
}

// Refresh animation selector logic
const refreshAnimSelect = document.getElementById('refreshAnimation');
if(refreshAnimSelect) {
  refreshAnimSelect.value = localStorage.getItem('refreshAnimation') || 'fade';
  refreshAnimSelect.onchange = function() {
    localStorage.setItem('refreshAnimation', this.value);
    alert('Refresh animation saved! Refresh a portfolio page to see the effect.');
  };
}
</script>
</body></html> 