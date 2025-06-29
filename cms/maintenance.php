<?php
/**
 * Database Maintenance Script for Photography CMS
 * Run this periodically to optimize performance
 * 
 * Usage: 
 * - Via web: http://localhost/photosite/cms/maintenance.php
 * - Via CLI: php maintenance.php
 */

require_once 'includes/config.php';
require_once 'includes/image_optimizer.php';

// Simple authentication for web access
if (isset($_SERVER['HTTP_HOST'])) {
    // Session is already started in config.php
    if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
        die('Access denied. Please log in as admin first.');
    }
}

class DatabaseMaintenance {
    private $conn;
    private $uploadDir;
    
    public function __construct() {
        $this->conn = getDBConnection();
        $this->uploadDir = __DIR__ . '/uploads/';
    }
    
    public function runMaintenance() {
        echo "<h2>🔧 Starting Database Maintenance</h2>\n";
        
        $this->optimizeDatabase();
        $this->cleanupOrphanedFiles();
        $this->optimizeImages();
        $this->updateStats();
        
        echo "<h2>✅ Maintenance Complete!</h2>\n";
    }
    
    private function optimizeDatabase() {
        echo "<h3>📊 Optimizing Database</h3>\n";
        
        try {
            // Analyze tables to update query planner statistics
            $this->conn->exec("ANALYZE");
            echo "✅ Database statistics updated<br>\n";
            
            // Vacuum to reclaim space and optimize
            $this->conn->exec("VACUUM");
            echo "✅ Database vacuumed and optimized<br>\n";
            
            // Update SQLite statistics
            $this->conn->exec("PRAGMA optimize");
            echo "✅ Query optimizer updated<br>\n";
            
            // Check integrity
            $result = $this->conn->query("PRAGMA integrity_check");
            $integrity = $result->fetchColumn();
            if ($integrity === 'ok') {
                echo "✅ Database integrity verified<br>\n";
            } else {
                echo "⚠️ Database integrity issues found: $integrity<br>\n";
            }
            
            // Show database size
            $size = filesize(DB_PATH);
            $sizeFormatted = $this->formatBytes($size);
            echo "📏 Database size: $sizeFormatted<br>\n";
            
        } catch (Exception $e) {
            echo "❌ Database optimization failed: " . $e->getMessage() . "<br>\n";
        }
    }
    
    private function cleanupOrphanedFiles() {
        echo "<h3>🧹 Cleaning Up Orphaned Files</h3>\n";
        
        try {
            // Get all media URLs from database
            $stmt = $this->conn->query("SELECT media_url FROM portfolio_media");
            $dbFiles = $stmt->fetchAll(PDO::FETCH_COLUMN);
            
            // Convert to filename only
            $dbFilenames = array_map(function($url) {
                return basename($url);
            }, $dbFiles);
            
            // Scan upload directory
            $filesRemoved = 0;
            $totalSize = 0;
            
            if (is_dir($this->uploadDir)) {
                $files = scandir($this->uploadDir);
                
                foreach ($files as $file) {
                    if ($file === '.' || $file === '..') continue;
                    
                    $filePath = $this->uploadDir . $file;
                    
                    // Skip if file is referenced in database
                    if (in_array($file, $dbFilenames)) continue;
                    
                    // Skip thumbnails and WebP versions of existing files
                    if (strpos($file, 'thumb_') === 0) {
                        $originalFile = substr($file, 6); // Remove 'thumb_' prefix
                        if (in_array($originalFile, $dbFilenames)) continue;
                    }
                    
                    if (pathinfo($file, PATHINFO_EXTENSION) === 'webp') {
                        $originalFile = pathinfo($file, PATHINFO_FILENAME);
                        foreach ($dbFilenames as $dbFile) {
                            if (pathinfo($dbFile, PATHINFO_FILENAME) === $originalFile) {
                                continue 2; // Skip this WebP file
                            }
                        }
                    }
                    
                    // Remove orphaned file
                    if (is_file($filePath)) {
                        $totalSize += filesize($filePath);
                        unlink($filePath);
                        $filesRemoved++;
                    }
                }
            }
            
            $sizeFormatted = $this->formatBytes($totalSize);
            echo "✅ Removed $filesRemoved orphaned files ($sizeFormatted freed)<br>\n";
            
        } catch (Exception $e) {
            echo "❌ File cleanup failed: " . $e->getMessage() . "<br>\n";
        }
    }
    
    private function optimizeImages() {
        echo "<h3>🖼️ Optimizing Images</h3>\n";
        
        // Check if GD extension is available
        if (!extension_loaded('gd')) {
            echo "⚠️ GD extension not available - image optimization skipped<br>\n";
            return;
        }
        
        try {
            $stmt = $this->conn->query("
                SELECT media_url 
                FROM portfolio_media 
                WHERE media_type = 'image'
            ");
            
            $optimizedCount = 0;
            $totalSizeSaved = 0;
            
            while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                $mediaUrl = $row['media_url'];
                $filePath = __DIR__ . '/../' . $mediaUrl;
                
                if (file_exists($filePath)) {
                    $originalSize = filesize($filePath);
                    
                    // Optimize if not already optimized recently
                    if (!ImageOptimizer::hasThumbnail($filePath)) {
                        ImageOptimizer::generateThumbnail($filePath, 300, 300, 80);
                    }
                    
                    // Generate WebP if not exists
                    if (!ImageOptimizer::hasWebPVersion($filePath)) {
                        // Re-optimize with WebP generation
                        ImageOptimizer::optimizeImage($filePath, 85, 1920, 1080);
                    }
                    
                    $newSize = filesize($filePath);
                    $sizeSaved = $originalSize - $newSize;
                    
                    if ($sizeSaved > 0) {
                        $totalSizeSaved += $sizeSaved;
                        $optimizedCount++;
                    }
                }
            }
            
            $sizeFormatted = $this->formatBytes($totalSizeSaved);
            echo "✅ Optimized $optimizedCount images ($sizeFormatted saved)<br>\n";
            
        } catch (Exception $e) {
            echo "❌ Image optimization failed: " . $e->getMessage() . "<br>\n";
        }
    }
    
    private function updateStats() {
        echo "<h3>📈 Updating Statistics</h3>\n";
        
        try {
            // Count portfolio items by category
            $stmt = $this->conn->query("
                SELECT 
                    category,
                    COUNT(*) as item_count,
                    COUNT(DISTINCT pm.id) as media_count
                FROM portfolio_items pi
                LEFT JOIN portfolio_media pm ON pi.id = pm.portfolio_item_id
                GROUP BY category
            ");
            
            $stats = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            echo "<table border='1' style='border-collapse: collapse; margin: 10px 0;'>\n";
            echo "<tr><th>Category</th><th>Items</th><th>Media Files</th></tr>\n";
            
            $totalItems = 0;
            $totalMedia = 0;
            
            foreach ($stats as $stat) {
                echo "<tr>";
                echo "<td>" . htmlspecialchars($stat['category']) . "</td>";
                echo "<td>" . $stat['item_count'] . "</td>";
                echo "<td>" . $stat['media_count'] . "</td>";
                echo "</tr>\n";
                
                $totalItems += $stat['item_count'];
                $totalMedia += $stat['media_count'];
            }
            
            echo "<tr style='font-weight: bold;'>";
            echo "<td>Total</td>";
            echo "<td>$totalItems</td>";
            echo "<td>$totalMedia</td>";
            echo "</tr>\n";
            echo "</table>\n";
            
            // Calculate upload directory size
            $uploadSize = $this->getDirectorySize($this->uploadDir);
            $uploadFormatted = $this->formatBytes($uploadSize);
            echo "💾 Upload directory size: $uploadFormatted<br>\n";
            
        } catch (Exception $e) {
            echo "❌ Statistics update failed: " . $e->getMessage() . "<br>\n";
        }
    }
    
    private function formatBytes($bytes, $precision = 2) {
        $units = array('B', 'KB', 'MB', 'GB', 'TB');
        
        for ($i = 0; $bytes > 1024 && $i < count($units) - 1; $i++) {
            $bytes /= 1024;
        }
        
        return round($bytes, $precision) . ' ' . $units[$i];
    }
    
    private function getDirectorySize($directory) {
        $size = 0;
        
        if (is_dir($directory)) {
            $files = new RecursiveIteratorIterator(
                new RecursiveDirectoryIterator($directory)
            );
            
            foreach ($files as $file) {
                if ($file->isFile()) {
                    $size += $file->getSize();
                }
            }
        }
        
        return $size;
    }
}

// Set content type for web output
if (isset($_SERVER['HTTP_HOST'])) {
    header('Content-Type: text/html; charset=utf-8');
    ?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Database Maintenance - CMS</title>
    
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
    .card {
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
    .bi-tools::before { content: "\f5dc"; }
    .bi-arrow-left::before { content: "\f128"; }
    
    table { border-collapse: collapse; width: 100%; }
    th, td { border: 1px solid var(--border); padding: 8px; text-align: left; }
    th { background-color: var(--border); }
    .maintenance-output { 
        background: var(--card-bg); 
        border: 1px solid var(--border); 
        border-radius: 8px; 
        padding: 20px; 
        margin: 10px 0;
        font-family: monospace;
        white-space: pre-wrap;
    }
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
                    <a class="nav-link text-white" href="performance-monitor.php">Performance Monitor</a>
                    <span class="nav-link text-white fw-bold">Maintenance</span>
                    <a class="nav-link text-white" href="?logout=1">Logout</a>
                </nav>
            </aside>

            <!-- Main Content -->
            <main class="col-md-9 col-lg-10 p-4">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h1><i class="bi bi-tools"></i> Database Maintenance</h1>
                    <a href="admin.php" class="btn btn-outline-secondary">
                        <i class="bi bi-arrow-left"></i> Back to Admin
                    </a>
                </div>
                
                <div class="card">
                    <div class="card-body">
                        <div class="maintenance-output">
    <?php
}

// Run maintenance
$maintenance = new DatabaseMaintenance();
$maintenance->runMaintenance();

if (isset($_SERVER['HTTP_HOST'])) {
    ?>
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
    </script>
</body>
</html>
    <?php
}
?> 