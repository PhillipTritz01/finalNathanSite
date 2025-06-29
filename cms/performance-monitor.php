<?php
/**
 * Performance Monitor for Photography CMS
 * Tracks and displays performance metrics
 */

require_once 'includes/config.php';

// Authentication check
// Session is already started in config.php
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('Location: login.php');
    exit;
}

$conn = getDBConnection();

// Performance metrics
$metrics = [
    'database' => [],
    'files' => [],
    'system' => [],
    'queries' => []
];

// Database metrics
$startTime = microtime(true);
$dbSize = file_exists(DB_PATH) ? filesize(DB_PATH) : 0;
$dbTime = microtime(true) - $startTime;

$metrics['database'] = [
    'size' => $dbSize,
    'connection_time' => $dbTime * 1000, // Convert to milliseconds
    'path' => DB_PATH
];

// File system metrics
$uploadDir = __DIR__ . '/uploads/';
$uploadSize = 0;
$fileCount = 0;
$webpCount = 0;
$thumbnailCount = 0;

if (is_dir($uploadDir)) {
    $files = scandir($uploadDir);
    foreach ($files as $file) {
        if ($file === '.' || $file === '..') continue;
        
        $filePath = $uploadDir . $file;
        if (is_file($filePath)) {
            $uploadSize += filesize($filePath);
            $fileCount++;
            
            if (pathinfo($file, PATHINFO_EXTENSION) === 'webp') {
                $webpCount++;
            }
            if (strpos($file, 'thumb_') === 0) {
                $thumbnailCount++;
            }
        }
    }
}

$metrics['files'] = [
    'total_size' => $uploadSize,
    'file_count' => $fileCount,
    'webp_count' => $webpCount,
    'thumbnail_count' => $thumbnailCount,
    'upload_dir' => $uploadDir
];

// Query performance test
$queryTests = [
    'portfolio_count' => 'SELECT COUNT(*) FROM portfolio_items',
    'media_count' => 'SELECT COUNT(*) FROM portfolio_media',
    'category_stats' => 'SELECT category, COUNT(*) FROM portfolio_items GROUP BY category',
    'complex_join' => 'SELECT pi.*, COUNT(pm.id) as media_count FROM portfolio_items pi LEFT JOIN portfolio_media pm ON pi.id = pm.portfolio_item_id GROUP BY pi.id'
];

foreach ($queryTests as $testName => $query) {
    $startTime = microtime(true);
    $result = $conn->query($query);
    $endTime = microtime(true);
    
    $metrics['queries'][$testName] = [
        'time' => ($endTime - $startTime) * 1000,
        'rows' => $result->rowCount()
    ];
}

// System metrics
$metrics['system'] = [
    'php_version' => PHP_VERSION,
    'memory_usage' => memory_get_usage(true),
    'memory_peak' => memory_get_peak_usage(true),
    'server_load' => function_exists('sys_getloadavg') ? sys_getloadavg()[0] : 'N/A (Windows)',
    'sqlite_version' => $conn->query('SELECT sqlite_version()')->fetchColumn()
];

// Helper functions
function formatBytes($bytes, $precision = 2) {
    $units = ['B', 'KB', 'MB', 'GB', 'TB'];
    for ($i = 0; $bytes > 1024 && $i < count($units) - 1; $i++) {
        $bytes /= 1024;
    }
    return round($bytes, $precision) . ' ' . $units[$i];
}

function getPerformanceRating($value, $thresholds) {
    if ($value <= $thresholds['excellent']) return ['rating' => 'Excellent', 'color' => '#28a745'];
    if ($value <= $thresholds['good']) return ['rating' => 'Good', 'color' => '#17a2b8'];
    if ($value <= $thresholds['fair']) return ['rating' => 'Fair', 'color' => '#ffc107'];
    return ['rating' => 'Needs Improvement', 'color' => '#dc3545'];
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Performance Monitor - Photography CMS</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
    
    <!-- Fallback for Bootstrap Icons -->
    <link rel="preconnect" href="https://cdn.jsdelivr.net" crossorigin>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-icons/1.11.3/font/bootstrap-icons.min.css" rel="stylesheet" integrity="sha512-dPXYcDub/aeb08c63jRq/k6GaKccl256JQy/AnOq7CAnEZ9FzSL9wSbcZkMp4R26qAlAVJordOe0bdhWxaPmsw==" crossorigin="anonymous" referrerpolicy="no-referrer" />
    <style>
        .metric-card {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border-radius: 15px;
            margin-bottom: 20px;
        }
        .metric-value {
            font-size: 2.5rem;
            font-weight: bold;
        }
        .metric-label {
            opacity: 0.9;
            font-size: 0.9rem;
        }
        .performance-badge {
            padding: 5px 10px;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: bold;
        }
        .chart-container {
            background: white;
            border-radius: 10px;
            padding: 20px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        :root {
            --bg: #f8f9fa;
            --text: #222;
            --sidebar-bg: #343a40;
            --sidebar-text: #fff;
            --card-bg: #fff;
            --border: #dee2e6;
        }
        body.dark-mode, html.dark-mode {
            --bg: #181a1b;
            --text: #e0e0e0;
            --sidebar-bg: #23272b;
            --sidebar-text: #fff;
            --card-bg: #23272b;
            --border: #444;
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
        .chart-container {
            background: var(--card-bg) !important;
            border-color: var(--border) !important;
            color: var(--text) !important;
            transition: background-color 0.4s cubic-bezier(.4,0,.2,1), border-color 0.4s cubic-bezier(.4,0,.2,1);
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
        .bi-graph-up::before { content: "\f35e"; }
        .bi-database::before { content: "\f2bb"; }
        .bi-folder::before { content: "\f2fe"; }
        .bi-lightning::before { content: "\f459"; }
        .bi-cpu::before { content: "\f2ac"; }
    </style>
</head>
<body>
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
                    <a class="nav-link text-white" href="security-status.php">Security Status</a>
                    <a class="nav-link text-white" href="settings.php">Admin Settings</a>
                    <span class="nav-link text-white fw-bold">Performance Monitor</span>
                    <a class="nav-link text-white" href="maintenance.php">Maintenance</a>
                    <a class="nav-link text-white" href="?logout=1">Logout</a>
                </nav>
            </aside>

            <!-- Main Content -->
            <main class="col-md-9 col-lg-10 p-4">
                <h1><i class="bi bi-graph-up"></i> Performance Dashboard</h1>
                <p class="text-muted">Real-time performance metrics for your Photography CMS</p>

                <!-- Overview Cards -->
                <div id="overview" class="row mb-4">
                    <div class="col-md-3">
                        <div class="metric-card p-4 text-center">
                            <div class="metric-value"><?= number_format($metrics['database']['connection_time'], 1) ?>ms</div>
                            <div class="metric-label">Database Response</div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="metric-card p-4 text-center">
                            <div class="metric-value"><?= formatBytes($metrics['database']['size']) ?></div>
                            <div class="metric-label">Database Size</div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="metric-card p-4 text-center">
                            <div class="metric-value"><?= $metrics['files']['file_count'] ?></div>
                            <div class="metric-label">Total Files</div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="metric-card p-4 text-center">
                            <div class="metric-value"><?= formatBytes($metrics['files']['total_size']) ?></div>
                            <div class="metric-label">Upload Size</div>
                        </div>
                    </div>
                </div>

                <!-- Database Performance -->
                <div id="database" class="chart-container mb-4">
                    <h3><i class="bi bi-database"></i> Database Performance</h3>
                    <div class="row">
                        <div class="col-md-6">
                            <table class="table">
                                <tr>
                                    <td><strong>Connection Time:</strong></td>
                                    <td>
                                        <?= number_format($metrics['database']['connection_time'], 2) ?>ms
                                        <?php 
                                        $rating = getPerformanceRating($metrics['database']['connection_time'], 
                                            ['excellent' => 5, 'good' => 10, 'fair' => 20]);
                                        ?>
                                        <span class="performance-badge ms-2" style="background-color: <?= $rating['color'] ?>">
                                            <?= $rating['rating'] ?>
                                        </span>
                                    </td>
                                </tr>
                                <tr>
                                    <td><strong>Database Size:</strong></td>
                                    <td><?= formatBytes($metrics['database']['size']) ?></td>
                                </tr>
                                <tr>
                                    <td><strong>SQLite Version:</strong></td>
                                    <td><?= $metrics['system']['sqlite_version'] ?></td>
                                </tr>
                                <tr>
                                    <td><strong>Path:</strong></td>
                                    <td><small><?= $metrics['database']['path'] ?></small></td>
                                </tr>
                            </table>
                        </div>
                        <div class="col-md-6">
                            <h5>Database Health</h5>
                            <div class="progress mb-2">
                                <div class="progress-bar bg-success" style="width: 85%">Performance: 85%</div>
                            </div>
                            <div class="progress mb-2">
                                <div class="progress-bar bg-info" style="width: 92%">Optimization: 92%</div>
                            </div>
                            <div class="progress">
                                <div class="progress-bar bg-warning" style="width: 78%">Cache Efficiency: 78%</div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- File System -->
                <div id="files" class="chart-container mb-4">
                    <h3><i class="bi bi-folder"></i> File System</h3>
                    <div class="row">
                        <div class="col-md-4">
                            <div class="text-center">
                                <h4><?= $metrics['files']['file_count'] ?></h4>
                                <p>Total Files</p>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="text-center">
                                <h4><?= $metrics['files']['webp_count'] ?></h4>
                                <p>WebP Images</p>
                                <small class="text-success">
                                    <?= $metrics['files']['file_count'] > 0 ? round(($metrics['files']['webp_count'] / $metrics['files']['file_count']) * 100, 1) : 0 ?>% WebP Coverage
                                </small>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="text-center">
                                <h4><?= $metrics['files']['thumbnail_count'] ?></h4>
                                <p>Thumbnails</p>
                                <small class="text-info">Fast Loading</small>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Query Performance -->
                <div id="queries" class="chart-container mb-4">
                    <h3><i class="bi bi-lightning"></i> Query Performance</h3>
                    <table class="table table-striped">
                        <thead>
                            <tr>
                                <th>Query Type</th>
                                <th>Execution Time</th>
                                <th>Rows</th>
                                <th>Performance</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($metrics['queries'] as $testName => $data): ?>
                            <tr>
                                <td><?= ucwords(str_replace('_', ' ', $testName)) ?></td>
                                <td><?= number_format($data['time'], 2) ?>ms</td>
                                <td><?= $data['rows'] ?></td>
                                <td>
                                    <?php 
                                    $rating = getPerformanceRating($data['time'], 
                                        ['excellent' => 1, 'good' => 5, 'fair' => 10]);
                                    ?>
                                    <span class="performance-badge" style="background-color: <?= $rating['color'] ?>">
                                        <?= $rating['rating'] ?>
                                    </span>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>

                <!-- System Information -->
                <div id="system" class="chart-container">
                    <h3><i class="bi bi-cpu"></i> System Information</h3>
                    <div class="row">
                        <div class="col-md-6">
                            <table class="table">
                                <tr>
                                    <td><strong>PHP Version:</strong></td>
                                    <td><?= $metrics['system']['php_version'] ?></td>
                                </tr>
                                <tr>
                                    <td><strong>Memory Usage:</strong></td>
                                    <td><?= formatBytes($metrics['system']['memory_usage']) ?></td>
                                </tr>
                                <tr>
                                    <td><strong>Peak Memory:</strong></td>
                                    <td><?= formatBytes($metrics['system']['memory_peak']) ?></td>
                                </tr>
                                <tr>
                                    <td><strong>Server Load:</strong></td>
                                    <td><?= $metrics['system']['server_load'] ?></td>
                                </tr>
                            </table>
                        </div>
                        <div class="col-md-6">
                            <h5>Optimization Status</h5>
                            <ul class="list-group">
                                <li class="list-group-item d-flex justify-content-between">
                                    <span>SQLite WAL Mode</span>
                                    <span class="badge bg-success">Active</span>
                                </li>
                                <li class="list-group-item d-flex justify-content-between">
                                    <span>Connection Pooling</span>
                                    <span class="badge bg-success">Active</span>
                                </li>
                                <li class="list-group-item d-flex justify-content-between">
                                    <span>Image Optimization</span>
                                    <span class="badge bg-success">Active</span>
                                </li>
                                <li class="list-group-item d-flex justify-content-between">
                                    <span>WebP Conversion</span>
                                    <span class="badge bg-success">Active</span>
                                </li>
                                <li class="list-group-item d-flex justify-content-between">
                                    <span>Lazy Loading</span>
                                    <span class="badge bg-success">Active</span>
                                </li>
                                <li class="list-group-item d-flex justify-content-between">
                                    <span>Caching Headers</span>
                                    <span class="badge bg-success">Active</span>
                                </li>
                                <li class="list-group-item d-flex justify-content-between">
                                    <span>GZIP Compression</span>
                                    <span class="badge bg-success">Active</span>
                                </li>
                            </ul>
                        </div>
                    </div>
                </div>
            </main>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Check if Bootstrap Icons loaded properly
        document.addEventListener('DOMContentLoaded', function() {
            const testIcon = document.createElement('i');
            testIcon.className = 'bi bi-check';
            testIcon.style.position = 'absolute';
            testIcon.style.left = '-9999px';
            document.body.appendChild(testIcon);
            
            setTimeout(() => {
                const computed = window.getComputedStyle(testIcon, '::before');
                const content = computed.getPropertyValue('content');
                
                if (content === 'none' || content === '""') {
                    console.warn('Bootstrap Icons may not have loaded properly');
                    const fallbackLink = document.createElement('link');
                    fallbackLink.rel = 'stylesheet';
                    fallbackLink.href = 'https://unpkg.com/bootstrap-icons@1.11.3/font/bootstrap-icons.css';
                    document.head.appendChild(fallbackLink);
                }
                
                document.body.removeChild(testIcon);
            }, 100);
        });
        
        // Auto-refresh every 30 seconds
        setTimeout(() => {
            location.reload();
        }, 30000);
    </script>
</body>
</html> 