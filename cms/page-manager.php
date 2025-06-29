<?php
require_once 'includes/config.php';
require_once 'includes/upload_config.php';

// Enhanced Session Security
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    SecurityHelper::logSecurityEvent('UNAUTHORIZED_ACCESS', 'Access to page manager without login');
    header('Location: login.php'); 
    exit;
}

// Check session timeout and integrity
$currentIP = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
if (isset($_SESSION['ip_address']) && $_SESSION['ip_address'] !== $currentIP) {
    SecurityHelper::logSecurityEvent('SESSION_HIJACK_ATTEMPT', "Page manager access with IP mismatch");
    session_unset(); session_destroy(); header('Location: login.php'); exit;
}

$_SESSION['last_activity'] = time();

if (isset($_GET['logout'])) { 
    SecurityHelper::logSecurityEvent('LOGOUT', 'Admin logout from page manager');
    session_unset(); session_destroy(); header('Location: login.php'); exit; 
}

$conn = getDBConnection();
$success = '';
$error = '';

// Initialize page_content table if it doesn't exist
try {
    $result = $conn->query("SELECT name FROM sqlite_master WHERE type='table' AND name='page_content'");
    if (!$result->fetchColumn()) {
        // Run the migration
        $sql = file_get_contents(__DIR__ . '/update_database_pages.sql');
        if ($sql) {
            $conn->exec($sql);
            $success = 'Page content system initialized successfully!';
        }
    }
} catch (Exception $e) {
    $error = 'Database initialization error: ' . $e->getMessage();
}

// Initialize page_settings table for animations
try {
    $result = $conn->query("SELECT name FROM sqlite_master WHERE type='table' AND name='page_settings'");
    if (!$result->fetchColumn()) {
        $sql = file_get_contents(__DIR__ . '/add_page_animations.sql');
        if ($sql) {
            $conn->exec($sql);
            $success .= ' Animation settings initialized successfully!';
        }
    }
} catch (Exception $e) {
    $error = 'Animation initialization error: ' . $e->getMessage();
}

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $csrfToken = $_POST['csrf_token'] ?? '';
    if (!SecurityHelper::validateCSRF($csrfToken)) {
        SecurityHelper::logSecurityEvent('CSRF_VIOLATION', 'Invalid CSRF token in page manager');
        $error = 'Security validation failed. Please try again.';
    } else {
        $action = $_POST['action'] ?? '';
        
        if ($action === 'update_content') {
            $id = (int)($_POST['id'] ?? 0);
            $contentValue = $_POST['content_value'] ?? '';
            $fileUploaded = false;
            
            // Handle optional file upload for images
            if (isset($_FILES['content_file']) && $_FILES['content_file']['error'] === UPLOAD_ERR_OK) {
                $file = $_FILES['content_file'];
                
                // Debug logging
                error_log("Page Manager Upload: Processing file " . $file['name'] . " (size: " . $file['size'] . ")");
                
                // Validate file
                $validationErrors = SecurityHelper::validateUploadedFile($file);
                if (empty($validationErrors)) {
                    // Generate secure filename
                    $secureFilename = SecurityHelper::generateSecureFilename($file['name']);
                    $uploadPath = UPLOAD_DIR . $secureFilename;
                    
                    // Ensure uploads directory exists and is writable
                    if (!is_dir(UPLOAD_DIR)) {
                        mkdir(UPLOAD_DIR, 0755, true);
                    }
                    
                    // Debug logging
                    error_log("Page Manager Upload: Moving to " . $uploadPath);
                    
                    // Move uploaded file
                    if (move_uploaded_file($file['tmp_name'], $uploadPath)) {
                        chmod($uploadPath, 0644);
                        
                        // Optimize image if possible
                        if (class_exists('ImageOptimizer')) {
                            try {
                                ImageOptimizer::optimizeImage($uploadPath, 85, 1920, 1080);
                            } catch (Exception $e) {
                                // Continue even if optimization fails
                                error_log("Image optimization failed: " . $e->getMessage());
                            }
                        }
                        
                        // Set the content value to the correct path for public pages
                        $contentValue = 'cms/' . UPLOAD_URL . $secureFilename;
                        $fileUploaded = true;
                        $success = 'Image uploaded successfully! New path: ' . $contentValue;
                        error_log("Page Manager Upload: Success - Content value set to " . $contentValue);
                    } else {
                        $error = 'Failed to save uploaded file. Check directory permissions. Upload path: ' . $uploadPath;
                        error_log("Page Manager Upload: Failed to move file to " . $uploadPath);
                    }
                } else {
                    $error = 'Upload validation failed: ' . implode(', ', $validationErrors);
                    error_log("Page Manager Upload: Validation failed - " . implode(', ', $validationErrors));
                }
            } elseif (isset($_FILES['content_file']) && $_FILES['content_file']['error'] !== UPLOAD_ERR_NO_FILE) {
                // Handle other upload errors
                $error = 'Upload error: ' . getUploadErrorMessage($_FILES['content_file']['error']);
                error_log("Page Manager Upload: Error - " . $error);
            }
            
            // Only update database if no upload errors occurred OR if no file was uploaded but URL was provided
            if (empty($error) && (!empty($contentValue) || $fileUploaded)) {
                try {
                    $stmt = $conn->prepare("UPDATE page_content SET content_value = ?, updated_at = CURRENT_TIMESTAMP WHERE id = ?");
                    $result = $stmt->execute([$contentValue, $id]);
                    
                    if ($result) {
                        SecurityHelper::logSecurityEvent('PAGE_CONTENT_UPDATED', "ID: $id, Value: $contentValue");
                        if (empty($success)) {
                            $success = 'Content updated successfully!';
                        }
                        // Redirect to refresh the page and show new content
                        header("Location: " . $_SERVER['REQUEST_URI'] . "&updated=1");
                        exit;
                    } else {
                        $error = 'Failed to update database.';
                    }
                } catch (Exception $e) {
                    $error = 'Database error: ' . $e->getMessage();
                    error_log("Page Manager Database Error: " . $e->getMessage());
                }
            } elseif (empty($error) && empty($contentValue) && !$fileUploaded) {
                $error = 'Please provide either a URL or upload a file.';
            }
        } elseif ($action === 'update_animation') {
            $pageName = $_POST['page_name'] ?? '';
            $refreshAnimation = $_POST['refresh_animation'] ?? 'fade';
            $galleryAnimation = $_POST['gallery_animation'] ?? 'rotate';
            
            try {
                $stmt = $conn->prepare("
                    INSERT OR REPLACE INTO page_settings (page_name, refresh_animation, gallery_animation, updated_at) 
                    VALUES (?, ?, ?, CURRENT_TIMESTAMP)
                ");
                $stmt->execute([$pageName, $refreshAnimation, $galleryAnimation]);
                
                SecurityHelper::logSecurityEvent('PAGE_ANIMATION_UPDATED', "Page: $pageName");
                $success = 'Animation settings updated successfully!';
            } catch (Exception $e) {
                $error = 'Failed to update animation settings: ' . $e->getMessage();
            }
        }
    }
}

// Get current page content
$selectedPage = $_GET['page'] ?? 'home';
$pageContent = [];
$pageSettings = [];

try {
    $stmt = $conn->prepare("SELECT * FROM page_content WHERE page_name = ? AND is_active = 1 ORDER BY display_order ASC");
    $stmt->execute([$selectedPage]);
    $pageContent = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    $error = 'Failed to load page content: ' . $e->getMessage();
}

// Get current page animation settings
try {
    $stmt = $conn->prepare("SELECT * FROM page_settings WHERE page_name = ?");
    $stmt->execute([$selectedPage]);
    $pageSettings = $stmt->fetch(PDO::FETCH_ASSOC);
    
    // Set defaults if no settings exist
    if (!$pageSettings) {
        $pageSettings = [
            'page_name' => $selectedPage,
            'refresh_animation' => 'fade',
            'gallery_animation' => 'rotate'
        ];
    }
} catch (Exception $e) {
    $pageSettings = [
        'page_name' => $selectedPage,
        'refresh_animation' => 'fade',
        'gallery_animation' => 'rotate'
    ];
}

// Check for success message from redirect
if (isset($_GET['updated']) && $_GET['updated'] == '1') {
    $success = 'Content updated successfully!';
    // Clean up URL parameter
    echo '<script>
    if (window.history.replaceState) {
        window.history.replaceState(null, null, window.location.pathname + window.location.search.replace(/[\?&]updated=1/, ""));
    }
    </script>';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Page Content Manager - CMS</title>
    
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
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
    
    <!-- Fallback for Bootstrap Icons -->
    <link rel="preconnect" href="https://cdn.jsdelivr.net" crossorigin>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-icons/1.11.3/font/bootstrap-icons.min.css" rel="stylesheet" integrity="sha512-dPXYcDub/aeb08c63jRq/k6GaKccl256JQy/AnOq7CAnEZ9FzSL9wSbcZkMp4R26qAlAVJordOe0bdhWxaPmsw==" crossorigin="anonymous" referrerpolicy="no-referrer" />
    
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
        --btn-outline-bg: transparent;
        --btn-outline-text: #6c757d;
        --btn-outline-border: #6c757d;
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
        --btn-outline-bg: transparent;
        --btn-outline-text: #adb5bd;
        --btn-outline-border: #6c757d;
    }
    
    body { 
        background: var(--bg) !important; 
        color: var(--text) !important;
        transition: background-color 0.4s cubic-bezier(.4,0,.2,1), color 0.4s cubic-bezier(.4,0,.2,1);
    }
    .sidebar { 
        background: var(--sidebar-bg) !important; 
        color: var(--sidebar-text) !important;
        min-height: 100vh;
        transition: background-color 0.4s cubic-bezier(.4,0,.2,1);
    }
    .card {
        background: var(--card-bg) !important;
        border-color: var(--border) !important;
        color: var(--text) !important;
        transition: background-color 0.4s cubic-bezier(.4,0,.2,1), border-color 0.4s cubic-bezier(.4,0,.2,1);
    }
    .btn-primary {
        background-color: var(--btn-bg) !important;
        color: var(--btn-text) !important;
        border-color: var(--btn-border) !important;
    }
    .btn-primary:hover, .btn-primary:focus {
        background-color: var(--btn-border) !important;
        color: var(--btn-text) !important;
    }
    .btn-outline-secondary {
        background: var(--btn-outline-bg) !important;
        color: var(--btn-outline-text) !important;
        border-color: var(--btn-outline-border) !important;
    }
    .btn-outline-secondary:hover, .btn-outline-secondary:focus {
        background-color: var(--btn-outline-border) !important;
        color: var(--card-bg) !important;
    }
    .text-muted {
        color: var(--btn-outline-text) !important;
    }
    
    /* Theme Switch Styles */
    .theme-switch-wrap {
        position: absolute;
        top: 18px;
        right: 200px;
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
    
    .content-item {
        border-radius: 8px;
        padding: 1rem;
        margin-bottom: 1rem;
        border: 1px solid var(--border);
        transition: all 0.3s ease;
    }
    .content-item:hover {
        transform: translateY(-2px);
        box-shadow: 0 4px 12px rgba(0,0,0,0.15);
    }
    .section-header {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        color: white;
        padding: 0.75rem 1rem;
        border-radius: 8px;
        margin-bottom: 1rem;
        font-weight: 600;
    }
    
    /* Bootstrap Icons Fix */
    [class^="bi-"]::before, 
    [class*=" bi-"]::before {
        font-family: "bootstrap-icons" !important;
        font-style: normal !important;
        font-weight: normal !important;
        font-variant: normal !important;
        text-transform: none !important;
        line-height: 1 !important;
        vertical-align: -0.125em !important;
        -webkit-font-smoothing: antialiased !important;
        -moz-osx-font-smoothing: grayscale !important;
        display: inline-block !important;
    }
    
    /* Ensure icons are visible */
    .bi {
        font-family: "bootstrap-icons" !important;
        font-style: normal !important;
        font-weight: normal !important;
        font-variant: normal !important;
        text-transform: none !important;
        line-height: 1 !important;
        vertical-align: -0.125em !important;
        -webkit-font-smoothing: antialiased !important;
        -moz-osx-font-smoothing: grayscale !important;
    }
    
    /* Specific icon fixes */
    .bi-file-earmark-text::before { content: "\f21a"; }
    .bi-eye::before { content: "\f2dd"; }
    .bi-house::before { content: "\f39c"; }
    .bi-person-circle::before { content: "\f506"; }
    .bi-envelope::before { content: "\f2e2"; }
    .bi-files::before { content: "\f30a"; }
    .bi-pencil-square::before { content: "\f4e7"; }
    .bi-save::before { content: "\f54f"; }
    .bi-check-circle::before { content: "\f26a"; }
    .bi-exclamation-triangle::before { content: "\f2f5"; }
    .bi-exclamation-circle::before { content: "\f2f4"; }
    .bi-magic::before { content: "\f436"; }
    .bi-arrow-clockwise::before { content: "\f20c"; }
    .bi-image::before { content: "\f3a8"; }
    </style>
</head>
<body>
    <div class="theme-switch-wrap">
        <span class="theme-switch-label">DARK</span>
        <div class="theme-switch" id="themeToggle">
            <div class="theme-switch-knob"></div>
        </div>
        <span class="theme-switch-label">LIGHT</span>
    </div>
    <div class="container-fluid">
        <div class="row">
            <!-- Sidebar -->
            <aside class="col-md-3 col-lg-2 sidebar p-3">
                <h3 class="mb-4 text-white">CMS</h3>
                <nav class="nav flex-column">
                    <span class="nav-link text-white fw-bold">Portfolio Management</span>
                    <a class="nav-link text-white" href="admin.php">Manage Portfolio</a>
                    <a class="nav-link text-white" href="portfolio-overview.php">Portfolio Overview</a>
                    <a class="nav-link text-white" href="gallery-settings.php">Gallery Settings</a>
                    <span class="nav-link text-white fw-bold mt-3">Page Management</span>
                    <span class="nav-link text-white fw-bold">Page Content</span>
                    <a class="nav-link text-white" href="slideshow-manager.php">Hero Slideshow</a>
                    <span class="nav-link text-white fw-bold mt-3">System</span>
                    <a class="nav-link text-white" href="security-status.php">Security Status</a>
                    <a class="nav-link text-white" href="settings.php">Admin Settings</a>
                    <a class="nav-link text-white" href="performance-monitor.php">Performance Monitor</a>
                    <a class="nav-link text-white" href="maintenance.php">Maintenance</a>
                    <a class="nav-link text-white" href="?logout=1">Logout</a>
                </nav>
            </aside>

            <!-- Main Content -->
            <main class="col-md-9 col-lg-10 p-4">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h1><i class="bi bi-file-earmark-text"></i> Page Content Manager</h1>
                    <div class="d-flex gap-2">
                        <a href="../index.php" class="btn btn-outline-secondary btn-sm" target="_blank">
                            <i class="bi bi-eye"></i> Preview Home
                        </a>
                        <a href="../about.php" class="btn btn-outline-secondary btn-sm" target="_blank">
                            <i class="bi bi-eye"></i> Preview About
                        </a>
                        <a href="../contact.php" class="btn btn-outline-secondary btn-sm" target="_blank">
                            <i class="bi bi-eye"></i> Preview Contact
                        </a>
                        <a href="../index.php" class="btn btn-outline-secondary">
                            <i class="bi bi-house"></i> View Website
                        </a>
                    </div>
                </div>

                <?php if ($success): ?>
                    <div class="alert alert-success alert-dismissible fade show">
                        <i class="bi bi-check-circle me-2"></i><?= htmlspecialchars($success) ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>

                <?php if ($error): ?>
                    <div class="alert alert-danger alert-dismissible fade show">
                        <i class="bi bi-exclamation-triangle me-2"></i><?= htmlspecialchars($error) ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>

                <!-- Page Selection -->
                <div class="row mb-4">
                    <div class="col-12">
                        <div class="card">
                            <div class="card-body">
                                <h5 class="card-title"><i class="bi bi-files me-2"></i>Select Page to Edit</h5>
                                <div class="btn-group" role="group">
                                    <a href="?page=home" class="btn <?= $selectedPage === 'home' ? 'btn-primary' : 'btn-outline-primary' ?>">
                                        <i class="bi bi-house me-2"></i>Home Page
                                    </a>
                                    <a href="?page=about" class="btn <?= $selectedPage === 'about' ? 'btn-primary' : 'btn-outline-primary' ?>">
                                        <i class="bi bi-person-circle me-2"></i>About Page
                                    </a>
                                    <a href="?page=contact" class="btn <?= $selectedPage === 'contact' ? 'btn-primary' : 'btn-outline-primary' ?>">
                                        <i class="bi bi-envelope me-2"></i>Contact Page
                                    </a>
                                </div>
                                <p class="mt-3 text-muted">
                                    <?php 
                                    $descriptions = [
                                        'home' => 'Edit the Home page content including hero title, subtitle, and button text.',
                                        'about' => 'Edit the About page content including hero section, story, statistics, and call-to-action.',
                                        'contact' => 'Edit the Contact page content including hero section, contact information, and social media links.'
                                    ];
                                    echo $descriptions[$selectedPage] ?? 'Select a page to edit its content.';
                                    ?>
                                </p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Animation Settings -->
                <?php if ($selectedPage !== 'home' && $selectedPage !== 'about' && $selectedPage !== 'contact'): ?>
                <div class="row mb-4">
                    <div class="col-12">
                        <div class="card">
                            <div class="card-header">
                                <h5 class="mb-0">
                                    <i class="bi bi-magic me-2"></i>
                                    <?= ucfirst($selectedPage) ?> Page Animation Settings
                                </h5>
                            </div>
                            <div class="card-body">
                                <form method="POST" class="animation-form">
                                    <?= SecurityHelper::csrfTokenField() ?>
                                    <input type="hidden" name="action" value="update_animation">
                                    <input type="hidden" name="page_name" value="<?= $selectedPage ?>">
                                    
                                    <div class="row">
                                        <div class="col-md-6">
                                            <div class="mb-3">
                                                <label for="refreshAnimation" class="form-label">
                                                    <i class="bi bi-arrow-clockwise me-1"></i>Page Refresh Animation
                                                </label>
                                                <select name="refresh_animation" id="refreshAnimation" class="form-select">
                                                    <option value="fade" <?= $pageSettings['refresh_animation'] === 'fade' ? 'selected' : '' ?>>Fade In</option>
                                                    <option value="slideUp" <?= $pageSettings['refresh_animation'] === 'slideUp' ? 'selected' : '' ?>>Slide Up</option>
                                                    <option value="slideDown" <?= $pageSettings['refresh_animation'] === 'slideDown' ? 'selected' : '' ?>>Slide Down</option>
                                                    <option value="slideLeft" <?= $pageSettings['refresh_animation'] === 'slideLeft' ? 'selected' : '' ?>>Slide Left</option>
                                                    <option value="slideRight" <?= $pageSettings['refresh_animation'] === 'slideRight' ? 'selected' : '' ?>>Slide Right</option>
                                                    <option value="zoom" <?= $pageSettings['refresh_animation'] === 'zoom' ? 'selected' : '' ?>>Zoom In</option>
                                                    <option value="bounce" <?= $pageSettings['refresh_animation'] === 'bounce' ? 'selected' : '' ?>>Bounce In</option>
                                                    <option value="flip" <?= $pageSettings['refresh_animation'] === 'flip' ? 'selected' : '' ?>>Flip In</option>
                                                    <option value="rotate" <?= $pageSettings['refresh_animation'] === 'rotate' ? 'selected' : '' ?>>Rotate In</option>
                                                    <option value="elastic" <?= $pageSettings['refresh_animation'] === 'elastic' ? 'selected' : '' ?>>Elastic</option>
                                                </select>
                                                <div class="form-text">Animation used when this page loads or refreshes</div>
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="mb-3">
                                                <label for="galleryAnimation" class="form-label">
                                                    <i class="bi bi-image me-1"></i>Gallery Hover Animation
                                                </label>
                                                <select name="gallery_animation" id="galleryAnimation" class="form-select">
                                                    <option value="rotate" <?= $pageSettings['gallery_animation'] === 'rotate' ? 'selected' : '' ?>>Gentle Rotate</option>
                                                    <option value="shadow" <?= $pageSettings['gallery_animation'] === 'shadow' ? 'selected' : '' ?>>Shadow Pulse</option>
                                                    <option value="blur" <?= $pageSettings['gallery_animation'] === 'blur' ? 'selected' : '' ?>>Gentle Blur</option>
                                                    <option value="grayscale" <?= $pageSettings['gallery_animation'] === 'grayscale' ? 'selected' : '' ?>>Subtle Grayscale</option>
                                                    <option value="brightness" <?= $pageSettings['gallery_animation'] === 'brightness' ? 'selected' : '' ?>>Brightness Pulse</option>
                                                    <option value="border" <?= $pageSettings['gallery_animation'] === 'border' ? 'selected' : '' ?>>Border Pulse</option>
                                                </select>
                                                <div class="form-text">Animation used for image galleries on this page</div>
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <button type="submit" class="btn btn-primary">
                                        <i class="bi bi-save me-1"></i>Save Animation Settings
                                    </button>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
                <?php endif; ?>

                <!-- Content Editor -->
                <?php if (!empty($pageContent)): ?>
                <div class="row">
                    <div class="col-12">
                        <div class="card">
                            <div class="card-header">
                                <h5 class="mb-0">
                                    <i class="bi bi-pencil-square me-2"></i>
                                    <?= ucfirst($selectedPage) ?> Page Content
                                </h5>
                            </div>
                            <div class="card-body">
                                <?php
                                $currentSection = '';
                                foreach ($pageContent as $content):
                                    // Group by section
                                    $section = explode('_', $content['section_key'])[0];
                                    if ($section !== $currentSection):
                                        if ($currentSection !== '') echo '</div>';
                                        $currentSection = $section;
                                        echo '<div class="section-header">' . ucfirst($section) . ' Section</div>';
                                        echo '<div class="mb-4">';
                                    endif;
                                ?>
                                
                                <div class="content-item">
                                    <form method="POST" class="content-form" enctype="multipart/form-data">
                                        <?= SecurityHelper::csrfTokenField() ?>
                                        <input type="hidden" name="action" value="update_content">
                                        <input type="hidden" name="id" value="<?= $content['id'] ?>">
                                        
                                        <div class="mb-3">
                                            <label class="form-label fw-bold">
                                                <?= ucwords(str_replace('_', ' ', $content['section_key'])) ?>
                                                <span class="badge bg-secondary ms-2"><?= $content['content_type'] ?></span>
                                            </label>
                                            
                                            <?php if ($content['content_type'] === 'html'): ?>
                                                <textarea name="content_value" class="form-control" rows="4" placeholder="Enter HTML content..."><?= htmlspecialchars($content['content_value']) ?></textarea>
                                            <?php elseif ($content['content_type'] === 'text'): ?>
                                                <input type="text" name="content_value" class="form-control" value="<?= htmlspecialchars($content['content_value']) ?>" placeholder="Enter text content...">
                                            <?php elseif ($content['content_type'] === 'image'): ?>
                                                <label class="form-label">Image URL</label>
                                                <input type="url" name="content_value" class="form-control mb-2" value="<?= htmlspecialchars($content['content_value']) ?>" placeholder="Enter image URL or upload file below">
                                                <label class="form-label">Or Upload New Image</label>
                                                <input type="file" name="content_file" accept="image/*" class="form-control">
                                                <small class="form-text text-muted">Choose a file to replace the current image, or leave empty to keep current image.</small>
                                                <?php if ($content['content_value']): ?>
                                                    <div class="mt-2">
                                                        <img src="<?= htmlspecialchars($content['content_value']) ?>" alt="Preview" style="max-width: 200px; height: auto; border-radius: 4px;">
                                                    </div>
                                                <?php endif; ?>
                                            <?php else: ?>
                                                <input type="text" name="content_value" class="form-control" value="<?= htmlspecialchars($content['content_value']) ?>" placeholder="Enter content...">
                                            <?php endif; ?>
                                        </div>
                                        
                                        <button type="submit" class="btn btn-primary btn-sm">
                                            <i class="bi bi-save me-1"></i>Update
                                        </button>
                                    </form>
                                </div>
                                
                                <?php endforeach; ?>
                                <?php if ($currentSection !== '') echo '</div>'; ?>
                            </div>
                        </div>
                    </div>
                </div>
                <?php else: ?>
                <div class="text-center py-5">
                    <i class="bi bi-exclamation-circle text-muted" style="font-size: 3rem;"></i>
                    <h4 class="mt-3">No Content Found</h4>
                    <p class="text-muted">There is no content available for the selected page.</p>
                    <div class="d-flex gap-2 justify-content-center">
                        <?php if ($selectedPage !== 'home'): ?>
                        <a href="?page=home" class="btn btn-primary">Try Home Page</a>
                        <?php endif; ?>
                        <?php if ($selectedPage !== 'about'): ?>
                        <a href="?page=about" class="btn btn-primary">Try About Page</a>
                        <?php endif; ?>
                        <?php if ($selectedPage !== 'contact'): ?>
                        <a href="?page=contact" class="btn btn-primary">Try Contact Page</a>
                        <?php endif; ?>
                    </div>
                </div>
                <?php endif; ?>

            </main>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <script>
    // Check if Bootstrap Icons loaded properly
    document.addEventListener('DOMContentLoaded', function() {
        // Create a test icon to check if font loaded
        const testIcon = document.createElement('i');
        testIcon.className = 'bi bi-check';
        testIcon.style.position = 'absolute';
        testIcon.style.left = '-9999px';
        document.body.appendChild(testIcon);
        
        // Check if the icon font loaded
        setTimeout(() => {
            const computed = window.getComputedStyle(testIcon, '::before');
            const content = computed.getPropertyValue('content');
            
            if (content === 'none' || content === '""') {
                console.warn('Bootstrap Icons may not have loaded properly');
                // Fallback: Load from alternative CDN
                const fallbackLink = document.createElement('link');
                fallbackLink.rel = 'stylesheet';
                fallbackLink.href = 'https://unpkg.com/bootstrap-icons@1.11.3/font/bootstrap-icons.css';
                document.head.appendChild(fallbackLink);
            }
            
            document.body.removeChild(testIcon);
        }, 100);
    });
    
    // Theme toggle logic
    function setTheme(mode) {
        document.documentElement.classList.remove('light-mode', 'dark-mode');
        document.body.classList.remove('light-mode', 'dark-mode');
        document.documentElement.classList.add(mode + '-mode');
        document.body.classList.add(mode + '-mode');
        localStorage.setItem('theme', mode);
    }
    
    function toggleTheme() {
        const isDark = document.body.classList.contains('dark-mode') || document.documentElement.classList.contains('dark-mode');
        setTheme(isDark ? 'light' : 'dark');
    }
    
    // Initialize theme toggle
    document.addEventListener('DOMContentLoaded', function() {
        document.getElementById('themeToggle').onclick = toggleTheme;
        
        // Apply saved theme or system preference
        const saved = localStorage.getItem('theme');
        if (saved) {
            setTheme(saved);
        } else if (window.matchMedia && window.matchMedia('(prefers-color-scheme: dark)').matches) {
            setTheme('dark');
        } else {
            setTheme('light');
        }
    });
    
    // Auto-save on form change
    document.querySelectorAll('.content-form').forEach(form => {
        const input = form.querySelector('input[name="content_value"], textarea[name="content_value"]');
        if (input) {
            let timeout;
            input.addEventListener('input', function() {
                clearTimeout(timeout);
                timeout = setTimeout(() => {
                    // Show auto-save indicator
                    const btn = form.querySelector('button[type="submit"]');
                    const originalText = btn.innerHTML;
                    btn.innerHTML = '<i class="bi bi-arrow-clockwise me-1"></i>Auto-saving...';
                    btn.disabled = true;
                    
                    // Submit form
                    form.submit();
                }, 2000); // Auto-save after 2 seconds of no typing
            });
        }
    });
    </script>
</body>
</html> 