<?php
require_once 'includes/config.php';
require_once 'includes/upload_config.php';
?>
<!doctype html><html lang="en"><head>
<meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title>Gallery Settings – CMS Admin</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css" rel="stylesheet">
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
body.dark-mode {
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
body.dark-mode .theme-switch {
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
body.dark-mode .theme-switch-knob {
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
body.dark-mode .theme-switch-knob::before {
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
body.dark-mode .gallery-settings-info {
  color: #fff;
  opacity: 0.92;
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
    <span class="nav-link text-white fw-bold">Portfolio</span>
    <a class="nav-link text-white active" href="#">Gallery Settings</a>
    <a class="nav-link text-white" href="admin.php">Back to Dashboard</a>
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
      <div class="gallery-settings-info" style="font-size:0.97em;">This animation will be used for all portfolio galleries. Refresh a portfolio page to see the effect.</div>
    </div>
  </div>
</main></div></div>
<script>
// Theme toggle logic
function setTheme(mode) {
  document.body.classList.remove('light-mode', 'dark-mode');
  document.body.classList.add(mode+'-mode');
  localStorage.setItem('theme', mode);
}
function toggleTheme() {
  const isDark = document.body.classList.contains('dark-mode');
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
    alert('Animation style saved! Refresh a portfolio page to see the effect.');
  };
}
</script>
</body></html> 