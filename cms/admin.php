<?php
require_once 'includes/config.php';
require_once 'includes/upload_config.php';
require_once 'includes/image_optimizer.php';

/* ─────── Enhanced Session Security ─────── */
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
  SecurityHelper::logSecurityEvent('UNAUTHORIZED_ACCESS', 'Access to admin without login');
  header('Location: login.php'); exit;
}

// Check session timeout
if (isset($_SESSION['last_activity']) && time() - $_SESSION['last_activity'] > 1800) {
  SecurityHelper::logSecurityEvent('SESSION_TIMEOUT', 'Session expired');
  session_unset(); session_destroy(); header('Location: login.php'); exit;
}

// Validate session integrity (prevent session hijacking)
$currentIP = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
$currentUA = $_SERVER['HTTP_USER_AGENT'] ?? '';

if (isset($_SESSION['ip_address']) && $_SESSION['ip_address'] !== $currentIP) {
  SecurityHelper::logSecurityEvent('SESSION_HIJACK_ATTEMPT', "Original IP: {$_SESSION['ip_address']}, Current IP: $currentIP");
  session_unset(); session_destroy(); header('Location: login.php'); exit;
}

if (isset($_SESSION['user_agent']) && $_SESSION['user_agent'] !== $currentUA) {
  SecurityHelper::logSecurityEvent('SESSION_HIJACK_ATTEMPT', "User agent mismatch");
  session_unset(); session_destroy(); header('Location: login.php'); exit;
}

$_SESSION['last_activity'] = time();

// Secure logout
if (isset($_GET['logout'])) { 
  SecurityHelper::logSecurityEvent('LOGOUT', 'Admin logout');
  session_unset(); session_destroy(); header('Location: login.php'); exit; 
}

/* ─────── Merge raw-JSON into $_POST if frontend used fetch JSON ─────── */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !$_POST) {
  $raw = json_decode(file_get_contents('php://input'), true);
  if (is_array($raw)) foreach ($raw as $k=>$v) $_POST[$k] = $v;
}

/* ─────── DB ─────── */
$conn = getDBConnection();

/* ─────── Add-new / Delete-all ─────── */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {

  // Validate CSRF token for all POST requests
  $csrfToken = $_POST['csrf_token'] ?? '';
  if (!SecurityHelper::validateCSRF($csrfToken)) {
    SecurityHelper::logSecurityEvent('CSRF_VIOLATION', "Action: {$_POST['action']}");
    die('Security validation failed. Please refresh and try again.');
  }

  switch ($_POST['action']) {

  /* add a new portfolio item + its media */
  case 'add_portfolio':
    $title       = SecurityHelper::sanitizeInput($_POST['title'] ?? '', 'string');
    $description = SecurityHelper::sanitizeInput($_POST['description'] ?? '', 'string');
    $category    = SecurityHelper::sanitizeInput($_POST['category'] ?? 'clients', 'string');

    // Validate required fields
    if (empty($title) || empty($description)) {
      SecurityHelper::logSecurityEvent('INVALID_INPUT', 'Empty title or description in portfolio add');
      break;
    }

    try {
      $conn->beginTransaction();
      $conn->prepare("INSERT INTO portfolio_items (title,description,category) VALUES (?,?,?)")
           ->execute([$title,$description,$category]);
      $itemId = $conn->lastInsertId();

      if (!empty($_FILES['media']['name'][0])) {
        SecurityHelper::logSecurityEvent('FILE_UPLOAD', "Portfolio ID: $itemId, Files: " . count($_FILES['media']['name']));

        foreach ($_FILES['media']['name'] as $i => $name) {
          if ($_FILES['media']['error'][$i]) continue;

          // Secure file validation
          $fileData = [
            'name' => $_FILES['media']['name'][$i],
            'type' => $_FILES['media']['type'][$i],
            'tmp_name' => $_FILES['media']['tmp_name'][$i],
            'error' => $_FILES['media']['error'][$i],
            'size' => $_FILES['media']['size'][$i]
          ];

          $validationErrors = SecurityHelper::validateUploadedFile($fileData);
          if (!empty($validationErrors)) {
            SecurityHelper::logSecurityEvent('FILE_UPLOAD_REJECTED', implode(', ', $validationErrors));
            continue; // Skip invalid files
          }

          $mediaType = 'image'; // Only images allowed now for security
          $secureFilename = SecurityHelper::generateSecureFilename($name);
          $file = UPLOAD_DIR . $secureFilename;
          $url  = UPLOAD_URL . $secureFilename;

          if (!move_uploaded_file($_FILES['media']['tmp_name'][$i], $file)) {
            SecurityHelper::logSecurityEvent('FILE_UPLOAD_FAILED', "Failed to move file: $name");
            continue;
          }

          // Set secure file permissions
          chmod($file, 0644);

          // Optimize image after upload (for images only)
          if ($mediaType === 'image' && class_exists('ImageOptimizer')) {
            try {
              ImageOptimizer::optimizeImage($file, 85, 1920, 1080);
              ImageOptimizer::generateThumbnail($file, 300, 300, 80);
            } catch (Exception $e) {
              error_log('Image optimization failed: ' . $e->getMessage());
              // Continue without optimization
            }
          }

          $conn->prepare("INSERT INTO portfolio_media
                          (portfolio_item_id,media_url,media_type,display_order)
                          VALUES (?,?,?,?)")
               ->execute([$itemId,$url,$mediaType,$i]);
        }
      }
      $conn->commit();
      SecurityHelper::logSecurityEvent('PORTFOLIO_ADDED', "ID: $itemId, Title: $title");
    } catch(Throwable $e){ 
      $conn->rollBack(); 
      SecurityHelper::logSecurityEvent('DATABASE_ERROR', $e->getMessage());
    }
    break;

  /* delete ALL media inside one portfolio item */
  case 'delete_portfolio':
    $id = SecurityHelper::sanitizeInput($_POST['id'] ?? 0, 'int');
    if ($id && $id > 0) {
      try {
        $conn->beginTransaction();
        
        // First get portfolio info for logging
        $stmt = $conn->prepare("SELECT title FROM portfolio_items WHERE id = ?");
        $stmt->execute([$id]);
        $portfolioInfo = $stmt->fetch();
        
        // Get media files to delete
        $stmt=$conn->prepare("SELECT media_url FROM portfolio_media WHERE portfolio_item_id=?");
        $stmt->execute([$id]);
        $mediaFiles = $stmt->fetchAll(PDO::FETCH_COLUMN);
        
        // Secure file deletion with path validation
        foreach($mediaFiles as $u){
          $fullPath = realpath(__DIR__.'/../'.$u);
          if ($fullPath && SecurityHelper::validatePath($fullPath, UPLOAD_DIR) && is_file($fullPath)) {
            @unlink($fullPath);
            // Also delete thumbnails and WebP versions
            $thumbPath = dirname($fullPath) . '/thumb_' . basename($fullPath);
            $webpPath = pathinfo($fullPath, PATHINFO_DIRNAME) . '/' . pathinfo($fullPath, PATHINFO_FILENAME) . '.webp';
            @unlink($thumbPath);
            @unlink($webpPath);
          }
        }
        
        $conn->prepare("DELETE FROM portfolio_items WHERE id=?")->execute([$id]);
        $conn->commit();
        
        SecurityHelper::logSecurityEvent('PORTFOLIO_DELETED', "ID: $id, Title: " . ($portfolioInfo['title'] ?? 'Unknown'));
        
        if (strtolower($_SERVER['HTTP_X_REQUESTED_WITH']??'')==='xmlhttprequest'){
          echo json_encode(['success'=>true]); exit;
        }
      }catch(Throwable $e){
        if($conn->inTransaction()) $conn->rollBack();
        SecurityHelper::logSecurityEvent('DELETE_ERROR', "Portfolio ID: $id, Error: " . $e->getMessage());
        if (strtolower($_SERVER['HTTP_X_REQUESTED_WITH']??'')==='xmlhttprequest'){
          http_response_code(500); echo json_encode(['success'=>false]); exit;
        }
      }
    } else {
      SecurityHelper::logSecurityEvent('INVALID_DELETE_ID', "Invalid portfolio ID: $id");
    }
    break;
  }
}

/* ─────── Fetch items ─────── */
$items = $conn->query("SELECT * FROM portfolio_items ORDER BY created_at DESC")
              ->fetchAll(PDO::FETCH_ASSOC);
?>
<!doctype html><html lang="en"><head>
<meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title>CMS Admin</title>

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
// Simplified icon font detection and fallback
(function() {
    const link = document.querySelector('link[href*="bootstrap-icons"]');
    if (link) {
        link.onerror = function() {
            console.log('Primary Bootstrap Icons CDN failed, loading fallback...');
            const fallback = document.createElement('link');
            fallback.rel = 'stylesheet';
            fallback.href = 'https://unpkg.com/bootstrap-icons@1.11.3/font/bootstrap-icons.css';
            fallback.onerror = function() {
                console.log('Fallback Bootstrap Icons CDN also failed');
                // Load local fallback if available
                const localFallback = document.createElement('link');
                localFallback.rel = 'stylesheet';
                localFallback.href = 'assets/css/bootstrap-icons-fallback.css';
                document.head.appendChild(localFallback);
            };
            document.head.appendChild(fallback);
        };
    }
})();
</script>

<!-- PhotoSwipe -->
<link  rel="stylesheet" href="../public/assets/css/photoswipe.css">
<script src="../public/assets/js/photoswipe.umd.min.js"></script>
<script src="../public/assets/js/photoswipe-lightbox.umd.min.js"></script>

<!-- Performance Optimizer -->
<script src="assets/js/performance-optimizer.js"></script>

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
.sidebar { background: var(--sidebar-bg) !important; color: var(--sidebar-text) !important; }
.card, .portfolio-item { background: var(--card-bg) !important; border-color: var(--border) !important; color: var(--text) !important; }

/* Buttons */
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

/* Inputs */
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

/* Misc */
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

.sidebar{min-height:100vh;background:#343a40;color:#fff}
.content{padding:20px}
.portfolio-item{margin-bottom:25px;padding:15px;border:1px solid #dee2e6;border-radius:6px}
.media-preview-container{display:grid;gap:.5rem;grid-template-columns:repeat(4,1fr)}
.media-preview{width:100%;aspect-ratio:1/1;object-fit:cover;border-radius:6px;background:#eee;cursor:pointer}
.thumbnail-link{display:block;width:100%;height:100%}
.thumbnail-link img{transition:transform .2s ease}
.thumbnail-link:hover img{transform:scale(1.05)}
.delete-check{position:absolute;top:6px;right:6px;width:18px;height:18px;z-index:2}
.delete-selected,.delete-all{width:auto;align-self:start}
.delete-selected[disabled]{opacity:.5;pointer-events:none}
.delete-selected.active{background:#dc3545;color:#fff;border-color:#b52a37;box-shadow:0 0 0 .2rem rgba(220,53,69,.25)}
.add-media-input{min-width:180px}
.gallery-grid{display:grid;grid-template-columns:repeat(4,1fr);gap:10px;max-height:70vh;overflow-y:auto}
.gallery-grid .media-wrapper{position:relative}
.gallery-grid img{width:100%;aspect-ratio:1/1;object-fit:cover;border-radius:6px;cursor:pointer;transition:transform .2s}
.gallery-grid img:hover{transform:scale(1.05)}
#backToTop{position:fixed;bottom:25px;right:25px;display:none;z-index:1030}
.portfolio-item.fade-out{opacity:0;transition:opacity .5s}
.toast{background:#fff;border-radius:4px;box-shadow:0 .5rem 1rem rgba(0,0,0,.15)}
.toast-container{z-index:10860}
.loading-spinner{position:absolute;inset:0;display:flex;align-items:center;justify-content:center;background:rgba(0,0,0,.7);z-index:1}
.pswp__counter{font-size:1.05rem}
body, .content, .sidebar, .card, .portfolio-item, input, textarea, select, .btn, .btn-primary, .btn-danger, .btn-outline-danger {
  transition: 
    background-color 0.4s cubic-bezier(.4,0,.2,1),
    color 0.4s cubic-bezier(.4,0,.2,1),
    border-color 0.4s cubic-bezier(.4,0,.2,1);
}
/* Enhanced Modal Styles */
.modal-xl { 
  max-width: min(95vw, 1400px); 
}
.modal-dialog { 
  height: min(90vh, 800px); 
  margin: 1.5rem auto; 
  display: flex; 
  flex-direction: column; 
  justify-content: center; 
}
.modal-content { 
  height: 100%; 
  display: flex; 
  flex-direction: column; 
  border-radius: 12px;
  overflow: hidden;
}
.modal-body { 
  flex: 1 1 auto; 
  overflow-y: auto; 
  scroll-behavior: auto; /* Changed from smooth to auto for better performance */
  padding: 1rem;
  /* Optimize scrolling performance */
  -webkit-overflow-scrolling: touch;
  will-change: scroll-position;
  transform: translateZ(0); /* Force hardware acceleration */
}

/* Gallery Grid Responsive */
.gallery-grid { 
  display: grid; 
  gap: 12px; 
  grid-template-columns: repeat(auto-fill, minmax(150px, 1fr));
}
@media (min-width: 768px) {
  .gallery-grid { 
    grid-template-columns: repeat(auto-fill, minmax(180px, 1fr)); 
    gap: 15px;
  }
}
@media (min-width: 1200px) {
  .gallery-grid { 
    grid-template-columns: repeat(auto-fill, minmax(200px, 1fr)); 
    gap: 18px;
  }
}

/* Media Item Enhancements */
.gallery-grid .media-wrapper {
  position: relative;
  border-radius: 8px;
  overflow: hidden;
  background: #2a2a2a;
  transition: transform 0.2s ease, box-shadow 0.2s ease;
  /* Performance optimizations */
  will-change: transform;
  transform: translateZ(0);
  backface-visibility: hidden;
  contain: layout style paint;
}
.gallery-grid .media-wrapper:hover {
  transform: translateY(-2px) translateZ(0);
  box-shadow: 0 8px 25px rgba(0,0,0,0.3);
}
.gallery-grid .media-wrapper:focus-within {
  outline: 2px solid #0d6efd;
  outline-offset: 2px;
}
.gallery-grid .media-wrapper.loaded {
  animation: fadeInUp 0.3s ease-out;
}
.gallery-grid .media-wrapper.error {
  background: #f8f9fa;
  border: 2px dashed #dee2e6;
}
.gallery-grid img {
  width: 100%;
  aspect-ratio: 1;
  object-fit: cover;
  display: block;
  transition: opacity 0.2s ease;
  /* Performance optimizations */
  will-change: transform, opacity;
  transform: translateZ(0);
}
.gallery-grid img:hover {
  opacity: 0.9;
}
.gallery-grid img[data-error="true"] {
  opacity: 0.5;
  filter: grayscale(1);
}

/* Smooth animations */
@keyframes fadeInUp {
  from {
    opacity: 0;
    transform: translateY(20px) translateZ(0);
  }
  to {
    opacity: 1;
    transform: translateY(0) translateZ(0);
  }
}

/* Optimize scrolling container */
.gallery-grid {
  /* Enable GPU acceleration for smooth scrolling */
  transform: translateZ(0);
  will-change: scroll-position;
  contain: layout style paint;
}

/* Enhanced Checkbox Styles */
.gallery-grid .delete-check, .delete-check, .select-all-modal {
  position: relative;
  width: 20px;
  height: 20px;
  background: rgba(255,255,255,0.95);
  border: 2px solid #dee2e6;
  border-radius: 4px;
  cursor: pointer;
  transition: all 0.2s ease;
  appearance: none;
  -webkit-appearance: none;
  -moz-appearance: none;
}

.gallery-grid .delete-check {
  position: absolute;
  top: 8px;
  right: 8px;
  z-index: 10;
  box-shadow: 0 2px 4px rgba(0,0,0,0.1);
}

/* Checkmark styling */
.delete-check:checked, .select-all-modal:checked {
  background: #28a745;
  border-color: #28a745;
  color: white;
}

.delete-check:checked::after, .select-all-modal:checked::after {
  content: '✓';
  position: absolute;
  top: 50%;
  left: 50%;
  transform: translate(-50%, -50%);
  font-size: 14px;
  font-weight: bold;
  color: white;
  line-height: 1;
}

/* Indeterminate state for select all */
.select-all-modal:indeterminate {
  background: #ffc107;
  border-color: #ffc107;
}

.select-all-modal:indeterminate::after {
  content: '−';
  position: absolute;
  top: 50%;
  left: 50%;
  transform: translate(-50%, -50%);
  font-size: 16px;
  font-weight: bold;
  color: white;
  line-height: 1;
}

/* Focus states */
.delete-check:focus, .select-all-modal:focus {
  outline: none;
  box-shadow: 0 0 0 3px rgba(40,167,69,0.25);
  border-color: #28a745;
}

/* Hover states */
.delete-check:hover, .select-all-modal:hover {
  border-color: #28a745;
  background: rgba(40,167,69,0.1);
}

/* Dashboard checkbox styling */
.media-preview-container .delete-check {
  position: absolute;
  top: 6px;
  right: 6px;
  width: 18px;
  height: 18px;
  z-index: 2;
  box-shadow: 0 1px 3px rgba(0,0,0,0.2);
}

.media-preview-container .delete-check:checked::after {
  font-size: 12px;
}

/* Loading and Error States */
.loading-spinner {
  position: absolute;
  inset: 0;
  display: flex;
  align-items: center;
  justify-content: center;
  background: rgba(0,0,0,0.8);
  z-index: 100;
  backdrop-filter: blur(2px);
}
.error-message {
  margin: 2rem;
  border-radius: 8px;
}
.empty-state {
  padding: 4rem 2rem;
}

/* Selection Counter */
#selectionCount {
  font-size: 0.875rem;
  min-width: 80px;
  text-align: right;
}

/* Select All Label Enhancement */
.form-check label[for="selectAllModal"] {
  font-weight: 600;
  letter-spacing: 0.025em;
  user-select: none;
  cursor: pointer;
}

.form-check label[for="selectAllModal"]:hover {
  color: #e9ecef !important;
}

/* Pagination */
.pagination-container .pagination {
  margin: 0;
}
.pagination-container .page-link {
  background: #343a40;
  border-color: #495057;
  color: #fff;
}
.pagination-container .page-link:hover {
  background: #495057;
  border-color: #6c757d;
  color: #fff;
}
.pagination-container .page-item.active .page-link {
  background: #0d6efd;
  border-color: #0d6efd;
}

/* Mobile Optimizations */
@media (max-width: 767px) {
  .modal-dialog {
    height: 95vh;
    margin: 0.5rem;
  }
  .modal-header {
    flex-direction: column;
    gap: 1rem;
    align-items: stretch !important;
    padding: 1rem;
  }
  .modal-header > div {
    justify-content: center;
  }
  .gallery-grid {
    grid-template-columns: repeat(auto-fill, minmax(120px, 1fr));
    gap: 8px;
  }
  .gallery-grid .delete-check {
    width: 22px;
    height: 22px;
    top: 6px;
    right: 6px;
  }
  .gallery-grid .delete-check:checked::after {
    font-size: 16px;
  }
  .select-all-modal {
    width: 22px;
    height: 22px;
  }
  .select-all-modal:checked::after, .select-all-modal:indeterminate::after {
    font-size: 16px;
  }
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
    <span class="nav-link text-white fw-bold">Manage Portfolio</span>
    <a class="nav-link text-white" href="portfolio-overview.php">Portfolio Overview</a>
    <a class="nav-link text-white" href="gallery-settings.php">Gallery Settings</a>
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
  <h2 class="mb-4">Portfolio Management</h2>

  <!-- ADD NEW -->
  <div class="card mb-4"><div class="card-body">
    <h5 class="card-title">Add New Portfolio Item</h5>
    <form id="addPortfolioForm" method="POST" enctype="multipart/form-data">
      <input type="hidden" name="action" value="add_portfolio">
      <?php echo SecurityHelper::csrfTokenField(); ?>
      <div class="mb-2"><label class="form-label">Title</label><input class="form-control" name="title" required></div>
      <div class="mb-2"><label class="form-label">Description</label><textarea class="form-control" name="description" rows="3" required></textarea></div>
      <div class="mb-2"><label class="form-label">Category</label>
        <select class="form-control" name="category" required>
          <option value="clients">Clients</option><option value="fineart">Fine Art</option>
          <option value="portraits">Portraits</option><option value="travel">Travel</option>
        </select></div>
      <div class="mb-2"><label class="form-label">Media Files</label>
        <input type="file" class="form-control" name="media[]" accept="image/*,video/*,audio/*" multiple required>
      </div>
      <button type="submit" class="btn btn-primary">Add Item</button>
    </form>
  </div></div>

<?php /* ITEMS LOOP */ foreach($items as $it):
      $rows=$conn->prepare("SELECT id,media_url FROM portfolio_media
                             WHERE portfolio_item_id=? ORDER BY display_order,id");
      $rows->execute([$it['id']]);
      $media=$rows->fetchAll(PDO::FETCH_ASSOC);
      $thumbs=array_slice($media,0,8);
?>
  <div class="portfolio-item" id="item-<?=$it['id']?>">
    <div class="d-flex flex-wrap align-items-start gap-4">
      <div class="d-flex flex-column gap-2">
        <!-- thumbs -->
        <div class="media-preview-container pswp-gallery" id="gallery-<?=$it['id']?>">
        <?php foreach($thumbs as $m): 
          // Get image dimensions for PhotoSwipe
          $imgPath = 'uploads/' . $m['media_url'];
          [$w, $h] = @getimagesize($imgPath) ?: [1600, 1200];
        ?>
          <div class="media-wrapper position-relative">
            <a href="<?=htmlspecialchars($m['media_url'])?>" 
               data-pswp-width="<?=$w?>" 
               data-pswp-height="<?=$h?>"
               class="thumbnail-link">
              <img src="<?=htmlspecialchars($m['media_url'])?>" class="media-preview">
            </a>
            <input type="checkbox" class="form-check-input delete-check" data-media-id="<?=$m['id']?>">
          </div>
        <?php endforeach; ?>
        <?php if(count($media)>8): ?>
          <div class="d-flex align-items-center justify-content-center bg-secondary text-white rounded"
               style="width:120px;height:120px;cursor:pointer"
               data-bs-toggle="modal" data-bs-target="#galleryModal"
               data-portfolio-id="<?=$it['id']?>"
               data-items='<?=htmlspecialchars(json_encode($media),ENT_QUOTES)?>'>
            +<?=count($media)-8?>
          </div>
        <?php endif; ?>
        </div>

        <!-- add-more picker -->
        <form class="add-media-form" data-portfolio-id="<?=$it['id']?>" enctype="multipart/form-data">
          <input type="file" name="media[]" multiple class="form-control form-control-sm add-media-input">
        </form>

        <button class="btn btn-danger btn-sm delete-selected" disabled>Delete Selected</button>
        <button class="btn btn-outline-danger btn-sm delete-all" data-id="<?=$it['id']?>">Delete All</button>
      </div>

      <div class="flex-grow-1">
        <h4><?=htmlspecialchars($it['title'])?></h4>
        <p><?=nl2br(htmlspecialchars($it['description']))?></p>
        <small class="text-muted">Category: <?=htmlspecialchars($it['category'])?></small><br>
        <small class="text-muted">Added: <?=date('F j, Y',strtotime($it['created_at']))?></small>
      </div>
    </div>
  </div>
<?php endforeach; ?>

</main></div></div>

<!-- Enhanced Gallery Modal -->
<div class="modal fade" id="galleryModal" tabindex="-1" aria-labelledby="galleryModalLabel" aria-describedby="galleryModalDesc">
  <div class="modal-dialog modal-xl modal-dialog-scrollable">
    <div class="modal-content bg-dark">
      <form class="delete-media-form" role="form" aria-label="Media management form">
        <div class="modal-header border-0 d-flex align-items-center justify-content-between">
          <div class="d-flex align-items-center gap-3">
            <h5 id="galleryModalLabel" class="text-white mb-0">Gallery Manager</h5>
            <div class="form-check d-flex align-items-center">
              <input class="form-check-input select-all-modal" type="checkbox" id="selectAllModal" aria-describedby="selectAllHelp">
              <label for="selectAllModal" class="form-label mb-0 ms-2 text-white fw-semibold">Select All</label>
            </div>
            <small id="selectAllHelp" class="text-muted d-none">Use Ctrl+A to select all items</small>
          </div>
          <div class="d-flex gap-2">
            <span id="selectionCount" class="text-white-50 small align-self-center me-2" aria-live="polite"></span>
            <button type="button" class="btn btn-outline-light btn-sm" id="refreshModal" title="Refresh gallery">
              <i class="bi bi-arrow-clockwise"></i>
            </button>
            <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal" aria-label="Close modal">
              <i class="bi bi-x-lg"></i> Close
            </button>
            <button type="submit" class="btn btn-danger btn-sm delete-selected" disabled aria-describedby="deleteHelp">
              <i class="bi bi-trash"></i> Delete Selected
            </button>
          </div>
        </div>
        <div class="modal-body position-relative" role="main">
          <div id="galleryModalDesc" class="visually-hidden">Gallery view with selectable media items. Use arrow keys to navigate, space to select, and Enter to view full size.</div>
          <div class="loading-spinner d-none" role="status" aria-label="Loading gallery">
            <div class="spinner-border text-light">
              <span class="visually-hidden">Loading gallery...</span>
            </div>
          </div>
          <div class="error-message d-none alert alert-danger" role="alert">
            <i class="bi bi-exclamation-triangle"></i>
            <span class="error-text">An error occurred while loading the gallery.</span>
            <button type="button" class="btn btn-sm btn-outline-danger ms-2" id="retryLoad">Retry</button>
          </div>
          <div class="empty-state d-none text-center text-white-50 py-5">
            <i class="bi bi-images display-4 mb-3"></i>
            <p>No media items found in this gallery.</p>
          </div>
          <div class="gallery-grid" role="grid" aria-label="Media gallery"></div>
          <div class="pagination-container d-none mt-3 d-flex justify-content-center">
            <nav aria-label="Gallery pagination">
              <ul class="pagination pagination-sm">
                <li class="page-item" id="prevPage">
                  <button class="page-link" type="button" aria-label="Previous page">
                    <i class="bi bi-chevron-left"></i>
                  </button>
                </li>
                <li class="page-item active" id="currentPage">
                  <span class="page-link">1</span>
                </li>
                <li class="page-item" id="nextPage">
                  <button class="page-link" type="button" aria-label="Next page">
                    <i class="bi bi-chevron-right"></i>
                  </button>
                </li>
              </ul>
            </nav>
          </div>
        </div>
      </form>
    </div>
  </div>
</div>

<!-- toast -->
<div class="toast-container position-fixed bottom-0 end-0 p-3">
  <div id="toast" class="toast" data-bs-delay="3000">
    <div class="toast-header"><strong class="me-auto" id="toastTitle">Info</strong>
      <button type="button" class="btn-close" data-bs-dismiss="toast"></button>
    </div>
    <div class="toast-body" id="toastMsg"></div>
  </div>
</div>

<!-- back-to-top -->
<button id="backToTop" class="btn btn-primary rounded-circle shadow"><i class="bi bi-chevron-up"></i></button>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
/* ----- helper toast ----- */
function toast(title,msg,isOK=true){
  document.getElementById('toastTitle').textContent=title;
  document.getElementById('toastMsg').textContent=msg;
  document.getElementById('toast').classList.toggle('bg-danger',!isOK);
  bootstrap.Toast.getOrCreateInstance('#toast').show();
}
const postJSON=(url,obj)=>fetch(url,{
  method:'POST',
  headers:{'Content-Type':'application/json','X-Requested-With':'XMLHttpRequest'},
  body:JSON.stringify(obj)
}).then(r=>{if(!r.ok)throw Error(r.status);return r.json()});

/* -------------------------------------------------------------- */
document.addEventListener('DOMContentLoaded',()=>{

/* Bootstrap Icons loaded via onerror fallback above */

/* auto-submit extra-media */
document.querySelectorAll('.add-media-input').forEach(inp=>{
  inp.onchange=()=>{ if(inp.files.length) inp.form.requestSubmit(); };});
document.querySelectorAll('.add-media-form').forEach(f=>{
  f.onsubmit=e=>{
    e.preventDefault();
    const fd=new FormData(f); fd.append('portfolio_id',f.dataset.portfolioId);
    fetch('api/add_media.php',{method:'POST',body:fd,headers:{'X-Requested-With':'XMLHttpRequest'}})
      .then(()=>location.reload())
      .catch(()=>toast('Error','Upload failed',false));
  };
});

/* DELETE ALL */
document.querySelectorAll('.delete-all').forEach(btn=>{
  btn.onclick=()=>{
    if(!confirm('Delete ALL images in this section?'))return;
    postJSON('',{action:'delete_portfolio',id:+btn.dataset.id})
      .then(()=>{
        const el = document.getElementById('item-'+btn.dataset.id);
        if(el){
          el.classList.add('fade-out');
          el.addEventListener('transitionend', function handler(e) {
            if(e.propertyName==='opacity'){
              el.removeEventListener('transitionend', handler);
              el.remove();
            }
          });
        }
      })
      .catch(()=>toast('Error','Delete failed',false));
  };
});

/* dashboard batch-delete */
document.querySelectorAll('.portfolio-item').forEach(card=>{
  const btn   = card.querySelector('.delete-selected');
  const boxes = [...card.querySelectorAll('.delete-check')];
  const sync  = ()=>{btn.disabled=!boxes.some(b=>b.checked);btn.classList.toggle('active',!btn.disabled);};
  card.addEventListener('change',sync); sync();
  btn.onclick=()=>{
    const ids=boxes.filter(b=>b.checked).map(b=>+b.dataset.mediaId);
    if(!ids.length||!confirm('Delete selected?'))return;
    postJSON('api/delete_media.php',{media_ids:ids})
      .then(()=>{
        // Remove deleted thumbnails
        boxes.forEach(b=>{
          if(b.checked){
            const wrap = b.closest('.media-wrapper');
            if(wrap) wrap.remove();
          }
        });
        // Update the +N count
        const previewContainer = card.querySelector('.media-preview-container');
        const allMedia = Array.from(previewContainer.querySelectorAll('.media-wrapper'));
        const plusN = card.querySelector('[data-bs-toggle="modal"][data-bs-target="#galleryModal"]');
        // Get the total media count (including those not shown as thumbs)
        let totalMedia = allMedia.length;
        if(plusN){
          // Try to update the data-items attribute
          let items = [];
          try {
            items = JSON.parse(plusN.getAttribute('data-items')||'[]');
          } catch(e) {}
          // Remove deleted ids from items
          items = items.filter(m=>!ids.includes(m.id));
          plusN.setAttribute('data-items', JSON.stringify(items));
          // Update the +N text or hide if not needed
          const n = items.length - 8;
          if(n > 0){
            plusN.textContent = '+'+n;
          } else {
            plusN.style.display = 'none';
          }
          totalMedia = items.length;
        }
        // If no media left, fade out and remove the portfolio item
        if(totalMedia === 0){
          card.classList.add('fade-out');
          card.addEventListener('transitionend', function handler(e) {
            if(e.propertyName==='opacity'){
              card.removeEventListener('transitionend', handler);
              card.remove();
            }
          });
        }
        sync();
      })
      .catch(()=>toast('Error','Delete failed',false));
  };
});

/* back-to-top */
const top=document.getElementById('backToTop');
window.onscroll=()=>top.style.display=scrollY>300?'block':'none';
top.onclick=()=>scrollTo({top:0,behavior:'smooth'});

/* Enhanced Modal Controller */
const modalEl = document.getElementById('galleryModal');
const modal = new bootstrap.Modal(modalEl);
const grid = modalEl.querySelector('.gallery-grid');
const spinner = modalEl.querySelector('.loading-spinner');
const selectAll = modalEl.querySelector('.select-all-modal');
const delBtn = modalEl.querySelector('.delete-selected');
let currentIds = [], selected = new Set(), lightbox = null, currentPortfolioId = null;

/* open modal from +N */
document.querySelectorAll('[data-bs-target="#galleryModal"]').forEach(btn => {
  btn.onclick = () => {
    const items = JSON.parse(btn.dataset.items || '[]');
    currentIds = items.map(x => x.id);
    selected.clear();
    buildGrid(items);
    modal.show();
    // Store the portfolio id for dashboard sync
    currentPortfolioId = btn.getAttribute('data-portfolio-id');
  };
});

/* build grid + PhotoSwipe anchors */
function buildGrid(items) {
  spinner.classList.remove('d-none');
  grid.innerHTML = '';
  
  // Clean up old lightbox
  if (lightbox) {
    lightbox.destroy();
    lightbox = null;
  }
  
  const pswpDiv = document.createElement('div');
  pswpDiv.className = 'pswp-gallery';
  pswpDiv.id = 'pswp-' + Date.now();
  pswpDiv.style.display = 'none';
  grid.before(pswpDiv);

  items.forEach((it, i) => {
    const wrap = document.createElement('div');
    wrap.className = 'media-wrapper position-relative';
    wrap.innerHTML = `<img src="${it.media_url}" loading="lazy">
                    <input type="checkbox" class="form-check-input delete-check" data-media-id="${it.id}">`;
    grid.appendChild(wrap);

    if (it.media_url.match(/\.(jpe?g|png|gif|webp)$/i)) {
      const a = document.createElement('a');
      a.href = it.media_url;
      a.dataset.pswpWidth = 1600;
      a.dataset.pswpHeight = 1200;
      pswpDiv.appendChild(a);
      wrap.querySelector('img').onclick = () => {
        if (lightbox) lightbox.loadAndOpen(i);
      };
      wrap.querySelector('img').style.cursor = 'zoom-in';
    }
  });

  /* PhotoSwipe instance */
  lightbox = new PhotoSwipeLightbox({
    gallery: '#' + pswpDiv.id,
    children: 'a',
    pswpModule: PhotoSwipe,
    wheelToZoom: true,
    arrowKeys: true,
    padding: { top: 40, bottom: 40, left: 40, right: 40 },
    bgOpacity: 1
  });
  lightbox.init();

  spinner.classList.add('d-none');
  attachCheckboxEvents();
  syncModalUI();
}

/* checkbox events */
function attachCheckboxEvents() {
  grid.querySelectorAll('.delete-check').forEach(cb => {
    cb.onchange = () => {
      const id = parseInt(cb.dataset.mediaId);
      if (cb.checked) selected.add(id);
      else selected.delete(id);
      syncModalUI();
    };
  });
}

/* sync select all + delete button */
function syncModalUI() {
  const total = currentIds.length;
  const selectedCount = selected.size;
  
  if (selectedCount === 0) {
    selectAll.checked = false;
    selectAll.indeterminate = false;
  } else if (selectedCount === total) {
    selectAll.checked = true;
    selectAll.indeterminate = false;
  } else {
    selectAll.checked = false;
    selectAll.indeterminate = true;
  }
  
  delBtn.disabled = selectedCount === 0;
  
  const selectionCount = modalEl.querySelector('#selectionCount');
  if (selectionCount) {
    if (selectedCount === 0) {
      selectionCount.textContent = '';
    } else {
      const percentage = Math.round((selectedCount / total) * 100);
      selectionCount.textContent = `${selectedCount} of ${total} selected (${percentage}%)`;
    }
  }
}

/* select all handler */
selectAll.onchange = () => {
  const shouldSelect = selectAll.checked;
  grid.querySelectorAll('.delete-check').forEach(cb => {
    cb.checked = shouldSelect;
    const id = parseInt(cb.dataset.mediaId);
    if (shouldSelect) selected.add(id);
    else selected.delete(id);
  });
  syncModalUI();
};

/* delete selected media */
modalEl.querySelector('.delete-media-form').onsubmit = async (e) => {
  e.preventDefault();
  if (selected.size === 0) return toast('Warning', 'No items selected');
  
  const count = selected.size;
  if (!confirm(`Delete ${count} selected item${count > 1 ? 's' : ''}?`)) return;
  
  spinner.classList.remove('d-none');
  
  try {
    const idsToDelete = Array.from(selected);
    const formData = new FormData();
    formData.append('delete_media', '1');
    idsToDelete.forEach(id => formData.append('media_ids[]', id));
    
    const response = await fetch('', { method: 'POST', body: formData });
    if (!response.ok) throw new Error('Network response was not ok');
    
    // Remove items from DOM
    idsToDelete.forEach(id => {
      const cb = grid.querySelector(`[data-media-id="${id}"]`);
      if (cb) cb.closest('.media-wrapper')?.remove();
    });
    
    // Update state
    currentIds = currentIds.filter(id => !idsToDelete.includes(id));
    selected.clear();
    
    // Update dashboard
    updateDashboard(idsToDelete);
    
    if (grid.querySelectorAll('.media-wrapper').length === 0) {
      modal.hide();
      toast('Info', 'All media deleted.');
    } else {
      attachCheckboxEvents();
      syncModalUI();
    }
    
    toast('Success', `${count} item${count > 1 ? 's' : ''} deleted successfully`);
    
  } catch (error) {
    console.error('Delete failed:', error);
    toast('Error', 'Delete failed', false);
  } finally {
    spinner.classList.add('d-none');
  }
};

function updateDashboard(deletedIds) {
  if (!currentPortfolioId) return;
  
  const dashCard = document.querySelector(`#item-${currentPortfolioId}`);
  if (!dashCard) return;
  
  const plusN = dashCard.querySelector('[data-bs-toggle="modal"][data-bs-target="#galleryModal"]');
  if (!plusN) return;
  
  try {
    let items = JSON.parse(plusN.getAttribute('data-items') || '[]');
    items = items.filter(m => !deletedIds.includes(m.id));
    plusN.setAttribute('data-items', JSON.stringify(items));
    
    const n = items.length - 8;
    if (n > 0) {
      plusN.textContent = '+' + n;
      plusN.style.display = '';
    } else {
      plusN.style.display = 'none';
    }
  } catch (error) {
    console.error('Failed to update dashboard:', error);
  }
}

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
// On load, set theme from localStorage or system
(function(){
  const saved = localStorage.getItem('theme');
  if(saved) setTheme(saved);
  else if(window.matchMedia && window.matchMedia('(prefers-color-scheme: dark)').matches) setTheme('dark');
  else setTheme('light');
})();

/* Add Portfolio Form AJAX Submission */
document.getElementById('addPortfolioForm').addEventListener('submit', function(e) {
  e.preventDefault();
  const formData = new FormData(this);
  
  fetch('', {
    method: 'POST',
    body: formData
  })
  .then(response => {
    if (!response.ok) throw new Error('Network response was not ok');
    toast('Success', 'Portfolio item added successfully');
    // Clear the form
    this.reset();
    // Reload the page to show the new item
    location.reload();
  })
  .catch(error => {
    console.error('Error:', error);
    toast('Error', 'Failed to add portfolio item', false);
  });
});

/* Initialize PhotoSwipe for thumbnail galleries */
document.querySelectorAll('.media-preview-container.pswp-gallery').forEach(gallery => {
  const lightbox = new PhotoSwipeLightbox({
    gallery: '#' + gallery.id,
    children: 'a.thumbnail-link',
    pswpModule: PhotoSwipe,
    wheelToZoom: true,
    arrowKeys: true,
    padding: { top: 40, bottom: 40, left: 40, right: 40 },
    bgOpacity: 1
  });
  lightbox.init();
});

});
</script>
</body></html>
