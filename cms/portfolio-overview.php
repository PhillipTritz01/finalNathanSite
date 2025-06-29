<?php
require_once 'includes/config.php';

// Enhanced Session Security
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    SecurityHelper::logSecurityEvent('UNAUTHORIZED_ACCESS', 'Access to portfolio overview without login');
    header('Location: login.php'); 
    exit;
}

// Check session timeout
if (isset($_SESSION['last_activity']) && time() - $_SESSION['last_activity'] > 1800) {
    SecurityHelper::logSecurityEvent('SESSION_TIMEOUT', 'Session expired in portfolio overview');
    session_unset(); session_destroy(); header('Location: login.php'); exit;
}

// Validate session integrity
$currentIP = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
if (isset($_SESSION['ip_address']) && $_SESSION['ip_address'] !== $currentIP) {
    SecurityHelper::logSecurityEvent('SESSION_HIJACK_ATTEMPT', "Portfolio overview access with IP mismatch");
    session_unset(); session_destroy(); header('Location: login.php'); exit;
}

$_SESSION['last_activity'] = time();

// Handle logout
if (isset($_GET['logout'])) { 
    SecurityHelper::logSecurityEvent('LOGOUT', 'Admin logout from portfolio overview');
    session_unset(); 
    session_destroy(); 
    header('Location: login.php'); 
    exit; 
}

// DB connection
$conn = getDBConnection();

// Get statistics
$stats = [];
$stats['total_projects'] = $conn->query("SELECT COUNT(*) FROM portfolio_items")->fetchColumn();
$stats['total_images'] = $conn->query("SELECT COUNT(*) FROM portfolio_media")->fetchColumn();

// Get category data
$categoryQueries = [
    'fineart' => [
        'name' => 'Fine Art Photography',
        'description' => 'Artistic and creative photography exploring light, form, and emotion.'
    ],
    'portraits' => [
        'name' => 'Portrait Photography', 
        'description' => 'Capturing personality and emotion in individual and group portraits.'
    ],
    'clients' => [
        'name' => 'Client Photography',
        'description' => 'Professional photography services for businesses and events.'
    ],
    'travel' => [
        'name' => 'Travel Photography',
        'description' => 'Documenting places, cultures, and experiences from around the world.'
    ]
];

$categoryData = [];
foreach ($categoryQueries as $key => $info) {
    $categoryData[$key] = $info;
    
    // Get project count
    $stmt = $conn->prepare("SELECT COUNT(*) FROM portfolio_items WHERE category = ?");
    $stmt->execute([$key]);
    $categoryData[$key]['count'] = $stmt->fetchColumn();
    
    // Get media count
    $stmt = $conn->prepare("SELECT COUNT(pm.id) FROM portfolio_media pm 
                           JOIN portfolio_items pi ON pm.portfolio_item_id = pi.id 
                           WHERE pi.category = ?");
    $stmt->execute([$key]);
    $categoryData[$key]['media_count'] = $stmt->fetchColumn();
    
    // Get latest item for this category
    $stmt = $conn->prepare("SELECT pi.title, pm.media_url FROM portfolio_items pi 
                           LEFT JOIN portfolio_media pm ON pi.id = pm.portfolio_item_id 
                           WHERE pi.category = ? 
                           ORDER BY pi.created_at DESC, pm.display_order ASC 
                           LIMIT 1");
    $stmt->execute([$key]);
    $latest = $stmt->fetch(PDO::FETCH_ASSOC);
    
    $categoryData[$key]['latest_title'] = $latest['title'] ?? null;
    $categoryData[$key]['latest_image'] = $latest['media_url'] ?? null;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Portfolio Overview - CMS</title>
    
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
    
    /* Responsive adjustment for smaller screens */
    @media (max-width: 768px) {
        .theme-switch-wrap {
            right: 24px;
            top: 60px;
        }
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
        .stat-card {
            background: linear-gradient(135deg, #6366f1 0%, #8b5cf6 100%);
            color: white;
            border-radius: 12px;
            padding: 2rem;
            text-align: center;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
        }
        .category-card {
            background: white;
            border-radius: 12px;
            overflow: hidden;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
            transition: transform 0.3s ease;
        }
        .category-card:hover {
            transform: translateY(-5px);
        }
        .category-image {
            height: 200px;
            background-size: cover;
            background-position: center;
            background-color: #f3f4f6;
            position: relative;
        }
        .category-overlay {
            position: absolute;
            inset: 0;
            background: rgba(0,0,0,0.4);
            display: flex;
            align-items: center;
            justify-content: center;
            opacity: 0;
            transition: opacity 0.3s ease;
        }
        .category-card:hover .category-overlay {
            opacity: 1;
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
        .bi-house::before { content: "\f39c"; }
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
                    <span class="nav-link text-white fw-bold">Portfolio Overview</span>
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

            <!-- Main Content -->
            <main class="col-md-9 col-lg-10 p-4">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h1>Portfolio Overview</h1>
                    <a href="../index.php" class="btn btn-outline-secondary">
                        <i class="bi bi-house"></i> View Website
                    </a>
                </div>

                <!-- Statistics -->
                <div class="row mb-5">
                    <div class="col-md-6 mb-3">
                        <div class="stat-card">
                            <h2 class="display-4 fw-bold"><?= $stats['total_projects'] ?></h2>
                            <p class="mb-0 fs-5">Photography Projects</p>
                        </div>
                    </div>
                    <div class="col-md-6 mb-3">
                        <div class="stat-card">
                            <h2 class="display-4 fw-bold"><?= $stats['total_images'] ?></h2>
                            <p class="mb-0 fs-5">Images Captured</p>
                        </div>
                    </div>
                </div>

                <!-- Categories -->
                <h2 class="mb-4">Photography Categories</h2>
                <div class="row">
                    <?php foreach ($categoryData as $key => $data): ?>
                    <div class="col-lg-6 mb-4">
                        <div class="category-card">
                            <div class="category-image" style="background-image: url('<?= $data['latest_image'] ? htmlspecialchars($data['latest_image']) : 'data:image/svg+xml;base64,PHN2ZyB3aWR0aD0iNDAwIiBoZWlnaHQ9IjIwMCIgdmlld0JveD0iMCAwIDQwMCAyMDAiIGZpbGw9Im5vbmUiIHhtbG5zPSJodHRwOi8vd3d3LnczLm9yZy8yMDAwL3N2ZyI+PHJlY3Qgd2lkdGg9IjQwMCIgaGVpZ2h0PSIyMDAiIGZpbGw9IiNmM2Y0ZjYiLz48cGF0aCBkPSJNMTYwIDgwTDEyMCAxMjBIMjgwTDI0MCA4MEgxNjBaIiBmaWxsPSIjZDFkNWRiIi8+PGNpcmNsZSBjeD0iMzQwIiBjeT0iNDAiIHI9IjE1IiBmaWxsPSIjZDFkNWRiIi8+PHRleHQgeD0iMjAwIiB5PSIxNjAiIGZvbnQtZmFtaWx5PSJBcmlhbCwgc2Fucy1zZXJpZiIgZm9udC1zaXplPSIxNCIgZmlsbD0iIzY0NzQ4ZSIgdGV4dC1hbmNob3I9Im1pZGRsZSI+Tm8gSW1hZ2UgQXZhaWxhYmxlPC90ZXh0Pjwvc3ZnPg==' ?>')">
                                <div class="category-overlay">
                                    <a href="../portfolio-<?= $key ?>.php" class="btn btn-light btn-lg">
                                        View Gallery
                                    </a>
                                </div>
                            </div>
                            <div class="p-4">
                                <h4 class="fw-bold mb-2"><?= htmlspecialchars($data['name']) ?></h4>
                                <p class="text-muted mb-3"><?= htmlspecialchars($data['description']) ?></p>
                                <div class="d-flex justify-content-between small text-muted">
                                    <span><strong><?= $data['count'] ?></strong> projects</span>
                                    <span><strong><?= $data['media_count'] ?></strong> images</span>
                                </div>
                                <?php if ($data['latest_title']): ?>
                                <div class="mt-2 small text-muted">
                                    Latest: <?= htmlspecialchars($data['latest_title']) ?>
                                </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>


            </main>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <script>
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
        // Check if Bootstrap Icons loaded properly
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
    </script>
</body>
</html> 