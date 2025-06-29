<?php
require_once 'includes/config.php';
require_once 'includes/upload_config.php';

// Check if admin is logged in
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    SecurityHelper::logSecurityEvent('UNAUTHORIZED_ACCESS', 'Access to slideshow manager without login');
    header('Location: login.php');
    exit;
}

// Update session activity
$_SESSION['last_activity'] = time();

$success = '';
$error = '';

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $csrfToken = $_POST['csrf_token'] ?? '';
    if (!SecurityHelper::validateCSRF($csrfToken)) {
        SecurityHelper::logSecurityEvent('CSRF_VIOLATION', "Slideshow action: {$_POST['action']}");
        die('Security validation failed. Please refresh and try again.');
    }

    $conn = getDBConnection();
    $action = $_POST['action'] ?? '';

    switch ($action) {
        case 'add_slide':
            $imageUrl = SecurityHelper::sanitizeInput($_POST['image_url'] ?? '', 'string');
            $fileUploaded = false;
            
            // Handle file upload
            if (isset($_FILES['image_file']) && $_FILES['image_file']['error'] === UPLOAD_ERR_OK) {
                $file = $_FILES['image_file'];
                $validationErrors = SecurityHelper::validateUploadedFile($file);
                
                if (empty($validationErrors)) {
                    $secureFilename = SecurityHelper::generateSecureFilename($file['name']);
                    $uploadPath = UPLOAD_DIR . $secureFilename;
                    
                    if (move_uploaded_file($file['tmp_name'], $uploadPath)) {
                        chmod($uploadPath, 0644);
                        
                        // Optimize image
                        if (class_exists('ImageOptimizer')) {
                            try {
                                ImageOptimizer::optimizeImage($uploadPath, 85, 1920, 1080);
                            } catch (Exception $e) {
                                error_log("Image optimization failed: " . $e->getMessage());
                            }
                        }
                        
                        $imageUrl = UPLOAD_URL . $secureFilename;
                        $fileUploaded = true;
                    } else {
                        $error = 'Failed to upload image.';
                    }
                } else {
                    $error = 'Upload validation failed: ' . implode(', ', $validationErrors);
                }
            }
            
            if (empty($error) && (!empty($imageUrl) || $fileUploaded)) {
                try {
                    // Get next order number
                    $stmt = $conn->query("SELECT MAX(display_order) FROM hero_slideshow");
                    $maxOrder = $stmt->fetchColumn() ?: 0;
                    
                    $stmt = $conn->prepare("INSERT INTO hero_slideshow (image_url, display_order) VALUES (?, ?)");
                    $result = $stmt->execute([$imageUrl, $maxOrder + 1]);
                    
                    if ($result) {
                        $success = 'Slideshow image added successfully!';
                        SecurityHelper::logSecurityEvent('SLIDESHOW_IMAGE_ADDED', "URL: $imageUrl");
                    } else {
                        $error = 'Failed to add slideshow image.';
                    }
                } catch (Exception $e) {
                    $error = 'Database error: ' . $e->getMessage();
                }
            } elseif (empty($error)) {
                $error = 'Please provide an image URL or upload a file.';
            }
            break;
            
        case 'delete_slide':
            $id = (int)($_POST['id'] ?? 0);
            if ($id > 0) {
                try {
                    // Get image info for cleanup
                    $stmt = $conn->prepare("SELECT image_url FROM hero_slideshow WHERE id = ?");
                    $stmt->execute([$id]);
                    $imageUrl = $stmt->fetchColumn();
                    
                    // Delete from database
                    $stmt = $conn->prepare("DELETE FROM hero_slideshow WHERE id = ?");
                    $result = $stmt->execute([$id]);
                    
                    if ($result) {
                        // Try to delete file if it's a local upload
                        if ($imageUrl && strpos($imageUrl, 'uploads/') === 0) {
                            $filePath = UPLOAD_DIR . basename($imageUrl);
                            if (file_exists($filePath)) {
                                @unlink($filePath);
                            }
                        }
                        
                        $success = 'Slideshow image deleted successfully!';
                        SecurityHelper::logSecurityEvent('SLIDESHOW_IMAGE_DELETED', "ID: $id");
                    } else {
                        $error = 'Failed to delete slideshow image.';
                    }
                } catch (Exception $e) {
                    $error = 'Database error: ' . $e->getMessage();
                }
            }
            break;
            
        case 'update_order':
            $orders = $_POST['orders'] ?? [];
            if (!empty($orders)) {
                try {
                    $conn->beginTransaction();
                    
                    foreach ($orders as $id => $order) {
                        $stmt = $conn->prepare("UPDATE hero_slideshow SET display_order = ? WHERE id = ?");
                        $stmt->execute([(int)$order, (int)$id]);
                    }
                    
                    $conn->commit();
                    $success = 'Slideshow order updated successfully!';
                    SecurityHelper::logSecurityEvent('SLIDESHOW_ORDER_UPDATED', 'Order updated');
                } catch (Exception $e) {
                    $conn->rollBack();
                    $error = 'Failed to update order: ' . $e->getMessage();
                }
            }
            break;
            
        case 'toggle_active':
            $id = (int)($_POST['id'] ?? 0);
            if ($id > 0) {
                try {
                    $stmt = $conn->prepare("UPDATE hero_slideshow SET is_active = 1 - is_active WHERE id = ?");
                    $result = $stmt->execute([$id]);
                    
                    if ($result) {
                        $success = 'Slideshow image status updated!';
                        SecurityHelper::logSecurityEvent('SLIDESHOW_STATUS_UPDATED', "ID: $id");
                    } else {
                        $error = 'Failed to update image status.';
                    }
                } catch (Exception $e) {
                    $error = 'Database error: ' . $e->getMessage();
                }
            }
            break;
    }
}

// Get slideshow images
$conn = getDBConnection();
$stmt = $conn->query("SELECT * FROM hero_slideshow ORDER BY display_order, id");
$slideshowImages = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Slideshow Manager - CMS</title>
    
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/sortablejs@1.15.0/Sortable.min.css" rel="stylesheet">
    
    <style>
        :root {
            --bg: #f8f9fa;
            --text: #222;
            --sidebar-bg: #343a40;
            --sidebar-text: #fff;
            --card-bg: #fff;
            --border: #dee2e6;
        }
        
        body.dark-mode {
            --bg: #181a1b;
            --text: #e0e0e0;
            --sidebar-bg: #23272b;
            --sidebar-text: #fff;
            --card-bg: #23272b;
            --border: #444;
        }
        
        body { background: var(--bg); color: var(--text); }
        .sidebar { background: var(--sidebar-bg); color: var(--sidebar-text); min-height: 100vh; }
        .card { background: var(--card-bg); border-color: var(--border); color: var(--text); }
        
        .slideshow-item {
            border: 1px solid var(--border);
            border-radius: 8px;
            padding: 15px;
            margin-bottom: 15px;
            background: var(--card-bg);
            cursor: move;
        }
        
        .slideshow-item.sortable-ghost {
            opacity: 0.5;
        }
        
        .slideshow-item img {
            max-width: 200px;
            max-height: 120px;
            object-fit: cover;
            border-radius: 4px;
        }
        
        .drag-handle {
            cursor: grab;
            padding: 5px;
            color: #666;
        }
        
        .drag-handle:active {
            cursor: grabbing;
        }
        
        .inactive-slide {
            opacity: 0.6;
        }
        
        .status-badge {
            font-size: 0.75rem;
        }
    </style>
</head>
<body>
    <div class="container-fluid">
        <div class="row">
            <!-- Sidebar -->
            <aside class="col-md-3 col-lg-2 sidebar p-3">
                <h3 class="mb-4">CMS</h3>
                <nav class="nav flex-column">
                    <span class="nav-link text-white fw-bold">Portfolio Management</span>
                    <a class="nav-link text-white" href="admin.php">Manage Portfolio</a>
                    <a class="nav-link text-white" href="portfolio-overview.php">Portfolio Overview</a>
                    <a class="nav-link text-white" href="gallery-settings.php">Gallery Settings</a>
                    <span class="nav-link text-white fw-bold mt-3">Page Management</span>
                    <a class="nav-link text-white" href="page-manager.php">Page Content</a>
                    <span class="nav-link text-white fw-bold">Slideshow Manager</span>
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
                    <h2><i class="bi bi-images me-2"></i>Hero Slideshow Manager</h2>
                    <div class="d-flex gap-2">
                        <a href="../index.php" class="btn btn-outline-secondary" target="_blank">
                            <i class="bi bi-eye me-1"></i>Preview Landing Page
                        </a>
                        <a href="admin.php" class="btn btn-secondary">
                            <i class="bi bi-arrow-left me-1"></i>Back to Admin
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
                
                <!-- Add New Slide -->
                <div class="card mb-4">
                    <div class="card-header">
                        <h5 class="mb-0"><i class="bi bi-plus-circle me-2"></i>Add New Slide</h5>
                    </div>
                    <div class="card-body">
                        <form method="POST" enctype="multipart/form-data">
                            <?= SecurityHelper::csrfTokenField() ?>
                            <input type="hidden" name="action" value="add_slide">
                            
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label class="form-label">Image URL</label>
                                        <input type="url" name="image_url" class="form-control" placeholder="Enter image URL or upload file below">
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label class="form-label">Or Upload Image</label>
                                        <input type="file" name="image_file" accept="image/*" class="form-control">
                                        <small class="form-text text-muted">Recommended: 1920x1080 or larger for best quality</small>
                                    </div>
                                </div>
                            </div>
                            
                            <button type="submit" class="btn btn-primary">
                                <i class="bi bi-plus me-1"></i>Add to Slideshow
                            </button>
                        </form>
                    </div>
                </div>
                
                <!-- Current Slideshow -->
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0">
                            <i class="bi bi-collection me-2"></i>Current Slideshow Images
                            <span class="badge bg-secondary ms-2"><?= count($slideshowImages) ?> images</span>
                        </h5>
                    </div>
                    <div class="card-body">
                        <?php if (empty($slideshowImages)): ?>
                            <div class="text-center py-5">
                                <i class="bi bi-images text-muted" style="font-size: 3rem;"></i>
                                <h4 class="mt-3">No Slideshow Images</h4>
                                <p class="text-muted">Add some images to get started with your hero slideshow.</p>
                            </div>
                        <?php else: ?>
                            <div class="mb-3">
                                <small class="text-muted">
                                    <i class="bi bi-info-circle me-1"></i>
                                    Drag and drop to reorder images. Click the status badge to enable/disable images.
                                </small>
                            </div>
                            
                            <form method="POST" id="orderForm">
                                <?= SecurityHelper::csrfTokenField() ?>
                                <input type="hidden" name="action" value="update_order">
                                <div id="sortable-list">
                                    <?php foreach ($slideshowImages as $slide): ?>
                                        <div class="slideshow-item <?= $slide['is_active'] ? '' : 'inactive-slide' ?>" data-id="<?= $slide['id'] ?>">
                                            <div class="row align-items-center">
                                                <div class="col-auto">
                                                    <i class="bi bi-grip-vertical drag-handle"></i>
                                                </div>
                                                <div class="col-auto">
                                                    <img src="<?= htmlspecialchars($slide['image_url']) ?>" alt="Slide" class="img-thumbnail">
                                                </div>
                                                <div class="col">
                                                    <div class="d-flex justify-content-between align-items-start">
                                                        <div>
                                                            <h6 class="mb-1 slide-number">Slide #<?= $slide['display_order'] ?></h6>
                                                            <small class="text-muted"><?= htmlspecialchars($slide['image_url']) ?></small>
                                                            <br>
                                                            <small class="text-muted">Added: <?= date('M j, Y', strtotime($slide['created_at'])) ?></small>
                                                        </div>
                                                        <div class="d-flex gap-2">
                                                            <button type="button" onclick="toggleActive(<?= $slide['id'] ?>)" class="btn btn-sm <?= $slide['is_active'] ? 'btn-success' : 'btn-secondary' ?>">
                                                                <?= $slide['is_active'] ? 'Active' : 'Inactive' ?>
                                                            </button>
                                                            <button type="button" onclick="deleteSlide(<?= $slide['id'] ?>)" class="btn btn-sm btn-outline-danger">
                                                                <i class="bi bi-trash"></i>
                                                            </button>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                            <input type="hidden" name="orders[<?= $slide['id'] ?>]" value="<?= $slide['display_order'] ?>" class="order-input">
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                                <div class="mt-3">
                                    <button type="submit" class="btn btn-primary" id="saveOrderBtn" style="display: none;">
                                        <i class="bi bi-save me-1"></i>Save Order
                                    </button>
                                </div>
                            </form>
                        <?php endif; ?>
                    </div>
                </div>
            </main>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sortablejs@1.15.0/Sortable.min.js"></script>
    
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const sortableList = document.getElementById('sortable-list');
            const saveOrderBtn = document.getElementById('saveOrderBtn');
            
            function updateSlideNumbers() {
                const items = sortableList.querySelectorAll('.slideshow-item');
                items.forEach((item, index) => {
                    const orderInput = item.querySelector('.order-input');
                    const slideNumberElement = item.querySelector('.slide-number');
                    
                    if (orderInput) {
                        orderInput.value = index + 1;
                    }
                    
                    if (slideNumberElement) {
                        slideNumberElement.textContent = 'Slide #' + (index + 1);
                    }
                });
            }
            
            if (sortableList) {
                const sortable = Sortable.create(sortableList, {
                    handle: '.drag-handle',
                    animation: 150,
                    ghostClass: 'sortable-ghost',
                    onEnd: function(evt) {
                        // Update order inputs and slide numbers
                        updateSlideNumbers();
                        
                        // Show save button
                        saveOrderBtn.style.display = 'inline-block';
                    }
                });
            }
        });

        function submitAction(action, id, confirmMsg = null) {
            if (confirmMsg && !confirm(confirmMsg)) {
                return;
            }

            const form = document.createElement('form');
            form.method = 'POST';
            form.style.display = 'none';

            const orderForm = document.getElementById('orderForm');
            if (!orderForm) {
                console.error('Could not find order form to get CSRF token');
                return;
            }
            const csrfInput = orderForm.querySelector('input[name="csrf_token"]');
            if (csrfInput) {
                form.appendChild(csrfInput.cloneNode());
            } else {
                console.error('Could not find CSRF token');
                return;
            }
            
            const actionInput = document.createElement('input');
            actionInput.type = 'hidden';
            actionInput.name = 'action';
            actionInput.value = action;
            form.appendChild(actionInput);

            if (id) {
                const idInput = document.createElement('input');
                idInput.type = 'hidden';
                idInput.name = 'id';
                idInput.value = id;
                form.appendChild(idInput);
            }

            document.body.appendChild(form);
            form.submit();
        }

        function deleteSlide(id) {
            submitAction('delete_slide', id, 'Delete this slide?');
        }

        function toggleActive(id) {
            submitAction('toggle_active', id);
        }
    </script>
</body>
</html> 