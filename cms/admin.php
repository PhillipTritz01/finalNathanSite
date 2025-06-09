<?php
require_once 'includes/config.php';

// Check if user is logged in
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('Location: login.php');
    exit;
}

// Check session timeout (30 minutes)
if (isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity'] > 1800)) {
    session_unset();
    session_destroy();
    header('Location: login.php');
    exit;
}
$_SESSION['last_activity'] = time();

// Handle logout
if (isset($_GET['logout'])) {
    session_unset();
    session_destroy();
    header('Location: login.php');
    exit;
}

// Get database connection
$conn = getDBConnection();

// Define allowed file types
$allowed_types = [
    'image' => ['image/jpeg', 'image/png', 'image/gif', 'image/webp'],
    'video' => ['video/mp4', 'video/webm', 'video/ogg'],
    'audio' => ['audio/mpeg', 'audio/ogg', 'audio/wav']
];

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'add_portfolio':
                $title = isset($_POST['title']) ? htmlspecialchars(trim($_POST['title']), ENT_QUOTES, 'UTF-8') : '';
                $description = isset($_POST['description']) ? htmlspecialchars(trim($_POST['description']), ENT_QUOTES, 'UTF-8') : '';
                $category = isset($_POST['category']) ? htmlspecialchars(trim($_POST['category']), ENT_QUOTES, 'UTF-8') : 'general';
                
                // Handle media upload
                if (isset($_FILES['media']) && $_FILES['media']['error'] === 0) {
                    $file_type = $_FILES['media']['type'];
                    $file_extension = strtolower(pathinfo($_FILES["media"]["name"], PATHINFO_EXTENSION));
                    
                    // Check if file type is allowed
                    $is_allowed = false;
                    foreach ($allowed_types as $type => $mimes) {
                        if (in_array($file_type, $mimes)) {
                            $is_allowed = true;
                            $media_type = $type;
                            break;
                        }
                    }
                    
                    if ($is_allowed) {
                        $target_dir = "uploads/";
                        $new_filename = uniqid() . '.' . $file_extension;
                        $target_file = $target_dir . $new_filename;
                        
                        if (move_uploaded_file($_FILES["media"]["tmp_name"], $target_file)) {
                            $stmt = $conn->prepare("INSERT INTO portfolio_items (title, description, image_url, category, media_type) VALUES (?, ?, ?, ?, ?)");
                            $stmt->execute([$title, $description, $target_file, $category, $media_type]);
                        }
                    }
                }
                break;
                
            case 'delete_portfolio':
                $id = filter_input(INPUT_POST, 'id', FILTER_SANITIZE_NUMBER_INT);
                // Get the file path before deleting
                $stmt = $conn->prepare("SELECT image_url FROM portfolio_items WHERE id = ?");
                $stmt->execute([$id]);
                $file = $stmt->fetchColumn();
                
                // Delete from database
                $stmt = $conn->prepare("DELETE FROM portfolio_items WHERE id = ?");
                $stmt->execute([$id]);
                
                // Delete the file if it exists
                if ($file && file_exists($file)) {
                    unlink($file);
                }
                break;
        }
    }
}

// Fetch portfolio items
$stmt = $conn->query("SELECT * FROM portfolio_items ORDER BY created_at DESC");
$portfolio_items = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>CMS Admin Dashboard</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.7.2/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        .sidebar {
            min-height: 100vh;
            background: #343a40;
            color: white;
        }
        .content {
            padding: 20px;
        }
        .portfolio-item {
            margin-bottom: 20px;
            padding: 15px;
            border: 1px solid #dee2e6;
            border-radius: 4px;
        }
        .media-preview {
            max-width: 200px;
            max-height: 200px;
            object-fit: cover;
        }
    </style>
</head>
<body>
    <div class="container-fluid">
        <div class="row">
            <!-- Sidebar -->
            <div class="col-md-3 col-lg-2 sidebar p-3">
                <h3 class="mb-4">CMS Dashboard</h3>
                <nav class="nav flex-column">
                    <a class="nav-link text-white active" href="#portfolio">Portfolio</a>
                    <a class="nav-link text-white" href="../portfolio-clients.php" target="_blank">View Clients Page</a>
                    <a class="nav-link text-white" href="../portfolio-fineart.php" target="_blank">View Fine Art Page</a>
                    <a class="nav-link text-white" href="../portfolio-portraits.php" target="_blank">View Portraits Page</a>
                    <a class="nav-link text-white" href="../portfolio-travel.php" target="_blank">View Travel Page</a>
                    <a class="nav-link text-white" href="?logout=1">Logout</a>
                </nav>
            </div>

            <!-- Main Content -->
            <div class="col-md-9 col-lg-10 content">
                <h2 class="mb-4">Portfolio Management</h2>
                
                <!-- Add New Portfolio Item -->
                <div class="card mb-4">
                    <div class="card-body">
                        <h5 class="card-title">Add New Portfolio Item</h5>
                        <form method="POST" enctype="multipart/form-data">
                            <input type="hidden" name="action" value="add_portfolio">
                            <div class="mb-3">
                                <label for="title" class="form-label">Title</label>
                                <input type="text" class="form-control" id="title" name="title" required>
                            </div>
                            <div class="mb-3">
                                <label for="description" class="form-label">Description</label>
                                <textarea class="form-control" id="description" name="description" rows="3" required></textarea>
                            </div>
                            <div class="mb-3">
                                <label for="category" class="form-label">Category</label>
                                <select class="form-control" id="category" name="category" required>
                                    <option value="clients">Clients</option>
                                    <option value="fineart">Fine Art</option>
                                    <option value="portraits">Portraits</option>
                                    <option value="travel">Travel</option>
                                </select>
                            </div>
                            <div class="mb-3">
                                <label for="media" class="form-label">Media File (Image/Video/Audio)</label>
                                <input type="file" class="form-control" id="media" name="media" accept="image/*,video/*,audio/*" required>
                                <small class="text-muted">Supported formats: JPG, PNG, GIF, WebP, MP4, WebM, OGG, MP3, WAV</small>
                            </div>
                            <button type="submit" class="btn btn-primary">Add Item</button>
                        </form>
                    </div>
                </div>

                <!-- Portfolio Items List -->
                <h3 class="mb-3">Current Portfolio Items</h3>
                <?php foreach ($portfolio_items as $item): ?>
                <div class="portfolio-item">
                    <div class="row">
                        <div class="col-md-2">
                            <?php 
                            $media_type = $item['media_type'] ?? 'image';
                            if ($media_type === 'image'): ?>
                                <img src="<?php echo htmlspecialchars($item['image_url'] ?? ''); ?>" class="media-preview" alt="<?php echo htmlspecialchars($item['title'] ?? ''); ?>">
                            <?php elseif ($media_type === 'video'): ?>
                                <video class="media-preview" controls>
                                    <source src="<?php echo htmlspecialchars($item['image_url'] ?? ''); ?>" type="video/mp4">
                                    Your browser does not support the video tag.
                                </video>
                            <?php elseif ($media_type === 'audio'): ?>
                                <audio class="w-100" controls>
                                    <source src="<?php echo htmlspecialchars($item['image_url'] ?? ''); ?>" type="audio/mpeg">
                                    Your browser does not support the audio tag.
                                </audio>
                            <?php endif; ?>
                        </div>
                        <div class="col-md-8">
                            <h4><?php echo htmlspecialchars($item['title'] ?? ''); ?></h4>
                            <p><?php echo htmlspecialchars($item['description'] ?? ''); ?></p>
                            <small class="text-muted">Category: <?php echo htmlspecialchars($item['category'] ?? 'general'); ?></small><br>
                            <small class="text-muted">Added: <?php echo isset($item['created_at']) ? date('F j, Y', strtotime($item['created_at'])) : ''; ?></small>
                        </div>
                        <div class="col-md-2">
                            <form method="POST" class="d-inline">
                                <input type="hidden" name="action" value="delete_portfolio">
                                <input type="hidden" name="id" value="<?php echo $item['id']; ?>">
                                <button type="submit" class="btn btn-danger" onclick="return confirm('Are you sure you want to delete this item?')">
                                    <i class="bi bi-trash"></i> Delete
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html> 