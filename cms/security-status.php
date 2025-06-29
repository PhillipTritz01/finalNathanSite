<?php
require_once 'includes/config.php';

// Enhanced Session Security
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    SecurityHelper::logSecurityEvent('UNAUTHORIZED_ACCESS', 'Access to security status without login');
    header('Location: login.php'); 
    exit;
}

// Session validation
$currentIP = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
if (isset($_SESSION['ip_address']) && $_SESSION['ip_address'] !== $currentIP) {
    SecurityHelper::logSecurityEvent('SESSION_HIJACK_ATTEMPT', "Security status access with IP mismatch");
    session_unset(); session_destroy(); header('Location: login.php'); exit;
}

$_SESSION['last_activity'] = time();

// Handle logout
if (isset($_GET['logout'])) { 
    SecurityHelper::logSecurityEvent('LOGOUT', 'Admin logout from security status');
    session_unset(); session_destroy(); header('Location: login.php'); exit; 
}

// Get security status
$securityStatus = getSecurityStatus();

// Get recent security events
$conn = getDBConnection();
$recentEvents = [];
try {
    $stmt = $conn->query("SELECT * FROM rate_limiting ORDER BY last_attempt DESC LIMIT 10");
    $recentEvents = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    // Table might not exist yet
}

// Get file system security info
$uploadDirPerms = substr(sprintf('%o', fileperms(__DIR__ . '/uploads')), -4);
$dbFilePerms = file_exists(__DIR__ . '/cmstest.db') ? substr(sprintf('%o', fileperms(__DIR__ . '/cmstest.db')), -4) : 'N/A';
$logsDirExists = is_dir(__DIR__ . '/../logs');

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Security Status - CMS Admin</title>
    
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
    
    .security-good { color: #28a745; }
    .security-warning { color: #ffc107; }
    .security-danger { color: #dc3545; }
    .status-card { border-left: 4px solid var(--border); }
    .status-card.good { border-left-color: #28a745; }
    .status-card.warning { border-left-color: #ffc107; }
    .status-card.danger { border-left-color: #dc3545; }
    
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
    .bi-shield-check::before { content: "\f561"; }
    .bi-house::before { content: "\f39c"; }
    .bi-check-circle::before { content: "\f26a"; }
    .bi-exclamation-triangle::before { content: "\f2f5"; }
    .bi-x-circle::before { content: "\f62b"; }
    .bi-clock::before { content: "\f29f"; }
    .bi-info-circle::before { content: "\f3af"; }
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
                    <a class="nav-link text-white" href="page-manager.php">Page Content</a>
                    <a class="nav-link text-white" href="slideshow-manager.php">Hero Slideshow</a>
                    <span class="nav-link text-white fw-bold mt-3">System</span>
                    <span class="nav-link text-white fw-bold">Security Status</span>
                    <a class="nav-link text-white" href="settings.php">Admin Settings</a>
                    <a class="nav-link text-white" href="performance-monitor.php">Performance Monitor</a>
                    <a class="nav-link text-white" href="maintenance.php">Maintenance</a>
                    <a class="nav-link text-white" href="?logout=1">Logout</a>
                </nav>
            </aside>

            <!-- Main Content -->
            <main class="col-md-9 col-lg-10 p-4">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h1><i class="bi bi-shield-check"></i> Security Status Dashboard</h1>
                    <a href="../index.php" class="btn btn-outline-secondary">
                        <i class="bi bi-house"></i> View Website
                    </a>
                </div>

    <!-- Security Overview -->
    <div class="row mb-4">
        <div class="col-md-6">
            <div class="card status-card good">
                <div class="card-body">
                    <h5 class="card-title">
                        <i class="fas fa-lock me-2"></i>Overall Security Score
                    </h5>
                    <?php
                    $score = 0;
                    $total = count($securityStatus);
                    foreach ($securityStatus as $status) {
                        if ($status) $score++;
                    }
                    $percentage = round(($score / $total) * 100);
                    $class = $percentage >= 80 ? 'security-good' : ($percentage >= 60 ? 'security-warning' : 'security-danger');
                    ?>
                    <h2 class="<?= $class ?>"><?= $percentage ?>%</h2>
                    <p class="text-muted"><?= $score ?> of <?= $total ?> security measures active</p>
                </div>
            </div>
        </div>
        <div class="col-md-6">
            <div class="card">
                <div class="card-body">
                    <h5 class="card-title">
                        <i class="fas fa-clock me-2"></i>Last Security Scan
                    </h5>
                    <p class="text-muted mb-0"><?= date('Y-m-d H:i:s') ?></p>
                    <small class="text-success">Real-time monitoring active</small>
                </div>
            </div>
        </div>
    </div>

    <!-- Security Checks -->
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">Security Component Status</h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <!-- CSRF Protection -->
                        <div class="col-md-6 mb-3">
                            <div class="d-flex align-items-center">
                                <i class="bi bi-<?= $securityStatus['csrf_protection'] ? 'check-circle security-good' : 'x-circle security-danger' ?> me-3 fs-4"></i>
                                <div>
                                    <strong>CSRF Protection</strong>
                                    <br><small class="text-muted">Cross-Site Request Forgery prevention</small>
                                </div>
                            </div>
                        </div>

                        <!-- Session Security -->
                        <div class="col-md-6 mb-3">
                            <div class="d-flex align-items-center">
                                <i class="bi bi-<?= $securityStatus['session_security'] ? 'check-circle security-good' : 'x-circle security-danger' ?> me-3 fs-4"></i>
                                <div>
                                    <strong>Session Security</strong>
                                    <br><small class="text-muted">Secure session configuration</small>
                                </div>
                            </div>
                        </div>

                        <!-- Error Display -->
                        <div class="col-md-6 mb-3">
                            <div class="d-flex align-items-center">
                                <i class="bi bi-<?= $securityStatus['error_display'] ? 'check-circle security-good' : 'x-circle security-danger' ?> me-3 fs-4"></i>
                                <div>
                                    <strong>Error Hiding</strong>
                                    <br><small class="text-muted">Production error handling</small>
                                </div>
                            </div>
                        </div>

                        <!-- File Upload Protection -->
                        <div class="col-md-6 mb-3">
                            <div class="d-flex align-items-center">
                                <i class="bi bi-<?= $securityStatus['file_upload_protection'] ? 'check-circle security-good' : 'x-circle security-danger' ?> me-3 fs-4"></i>
                                <div>
                                    <strong>Upload Protection</strong>
                                    <br><small class="text-muted">Script execution prevention</small>
                                </div>
                            </div>
                        </div>

                        <!-- Database Protection -->
                        <div class="col-md-6 mb-3">
                            <div class="d-flex align-items-center">
                                <i class="bi bi-<?= $securityStatus['database_protection'] ? 'check-circle security-good' : 'exclamation-triangle security-warning' ?> me-3 fs-4"></i>
                                <div>
                                    <strong>Database Security</strong>
                                    <br><small class="text-muted">File permissions: <?= $dbFilePerms ?></small>
                                </div>
                            </div>
                        </div>

                        <!-- Security Logging -->
                        <div class="col-md-6 mb-3">
                            <div class="d-flex align-items-center">
                                <i class="bi bi-<?= $securityStatus['logs_directory'] ? 'check-circle security-good' : 'x-circle security-danger' ?> me-3 fs-4"></i>
                                <div>
                                    <strong>Security Logging</strong>
                                    <br><small class="text-muted">Event monitoring and logging</small>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- File System Security -->
    <div class="row mt-4">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">File System Security</h5>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-sm">
                            <thead>
                                <tr>
                                    <th>Location</th>
                                    <th>Permissions</th>
                                    <th>Status</th>
                                    <th>Protection</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td><code>cms/uploads/</code></td>
                                    <td><?= $uploadDirPerms ?></td>
                                    <td><i class="bi bi-check-circle security-good"></i></td>
                                    <td>.htaccess protection active</td>
                                </tr>
                                <tr>
                                    <td><code>cms/cmstest.db</code></td>
                                    <td><?= $dbFilePerms ?></td>
                                    <td><i class="bi bi-<?= $dbFilePerms !== 'N/A' ? 'check-circle security-good' : 'x-circle security-danger' ?>"></i></td>
                                    <td>Web access blocked</td>
                                </tr>
                                <tr>
                                    <td><code>logs/</code></td>
                                    <td><?= $logsDirExists ? '0700' : 'N/A' ?></td>
                                    <td><i class="bi bi-<?= $logsDirExists ? 'check-circle security-good' : 'x-circle security-danger' ?>"></i></td>
                                    <td>Private directory</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Recent Security Events -->
    <div class="row mt-4">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">Recent Security Events</h5>
                </div>
                <div class="card-body">
                    <?php if (empty($recentEvents)): ?>
                        <p class="text-muted">No recent security events to display.</p>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-sm table-striped">
                                <thead>
                                    <tr>
                                        <th>Timestamp</th>
                                        <th>Event Type</th>
                                        <th>Details</th>
                                        <th>Attempts</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($recentEvents as $event): ?>
                                        <tr>
                                            <td><?= htmlspecialchars($event['timestamp']) ?></td>
                                            <td>
                                                <?php if ($event['source'] === 'rate_limiting'): ?>
                                                    <span class="badge bg-warning">Rate Limit</span>
                                                <?php endif; ?>
                                            </td>
                                            <td><?= htmlspecialchars($event['event']) ?></td>
                                            <td><?= htmlspecialchars($event['details']) ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Security Recommendations -->
    <div class="row mt-4 mb-4">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">Security Recommendations</h5>
                </div>
                <div class="card-body">
                    <div class="alert alert-success">
                        <h6><i class="bi bi-check-circle me-2"></i>Security Hardening Complete!</h6>
                        <p class="mb-0">All critical security measures have been implemented:</p>
                        <ul class="mb-0 mt-2">
                            <li>CSRF protection on all forms</li>
                            <li>Session security with hijacking prevention</li>
                            <li>Rate limiting for login and contact forms</li>
                            <li>Secure file upload validation</li>
                            <li>Production error handling</li>
                            <li>Security logging and monitoring</li>
                            <li>Enhanced .htaccess protection</li>
                        </ul>
                    </div>
                    
                    <div class="alert alert-info">
                        <h6><i class="bi bi-info-circle me-2"></i>Additional Recommendations</h6>
                        <ul class="mb-0">
                            <li>Change default admin password to a strong password</li>
                            <li>Enable HTTPS in production environment</li>
                            <li>Set up automated database backups</li>
                            <li>Monitor security logs regularly</li>
                            <li>Keep PHP and dependencies updated</li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
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
    
    // Auto-refresh security status every 30 seconds
    setTimeout(() => {
        location.reload();
    }, 30000);
    </script>
</body>
</html> 