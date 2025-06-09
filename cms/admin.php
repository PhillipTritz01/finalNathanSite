<?php
require_once 'includes/config.php';
require_once 'includes/upload_config.php';

/* ───── AUTH & SESSION ───────────────────────────────────────── */
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('Location: login.php'); exit;
}
if (isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity'] > 1800)) {
    session_unset(); session_destroy(); header('Location: login.php'); exit;
}
$_SESSION['last_activity'] = time();
if (isset($_GET['logout'])) { session_unset(); session_destroy(); header('Location: login.php'); exit; }

/* ───── DB ───────────────────────────────────────────────────── */
$conn = getDBConnection();

/* ───── HANDLE POST ACTIONS ─────────────────────────────────── */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    switch ($_POST['action']) {

    /* ---------- ADD NEW ITEM ---------- */
    case 'add_portfolio':
        $title       = htmlspecialchars(trim($_POST['title']       ?? ''), ENT_QUOTES, 'UTF-8');
        $description = htmlspecialchars(trim($_POST['description'] ?? ''), ENT_QUOTES, 'UTF-8');
        $category    = htmlspecialchars(trim($_POST['category']    ?? 'clients'), ENT_QUOTES, 'UTF-8');

        try {
            $conn->beginTransaction();

            /* item row */
            $stmt = $conn->prepare("INSERT INTO portfolio_items (title, description, category)
                                    VALUES (?,?,?)");
            $stmt->execute([$title, $description, $category]);
            $portfolio_id = $conn->lastInsertId();

            /* multiple uploads */
            if (!empty($_FILES['media']['name'][0])) {
                $errors = validateFileUpload($_FILES['media']);
                if ($errors) throw new Exception(implode("\n",$errors));

                $total = count($_FILES['media']['name']);
                for ($i=0;$i<$total;$i++) {
                    if ($_FILES['media']['error'][$i] !== 0) continue;

                    $type  = $_FILES['media']['type'][$i];
                    $ext   = strtolower(pathinfo($_FILES['media']['name'][$i], PATHINFO_EXTENSION));
                    $media_type = in_array($type, ALLOWED_IMAGE_TYPES) ? 'image' :
                                  (in_array($type, ALLOWED_VIDEO_TYPES) ? 'video' :
                                  (in_array($type, ALLOWED_AUDIO_TYPES) ? 'audio' : ''));

                    if (!$media_type) continue;

                    $target_dir  = 'uploads/';
                    $new_name    = uniqid().'.'.$ext;
                    $target_path = $target_dir.$new_name;

                    if (!move_uploaded_file($_FILES['media']['tmp_name'][$i], $target_path))
                        throw new Exception('Failed to move uploaded file: '.$_FILES['media']['name'][$i]);

                    $stmt = $conn->prepare("INSERT INTO portfolio_media
                                            (portfolio_item_id, media_url, media_type, display_order)
                                            VALUES (?,?,?,?)");
                    $stmt->execute([$portfolio_id, $target_path, $media_type, $i]);
                }
            }

            $conn->commit();
            $success_message = 'Portfolio item added successfully!';
        } catch (Exception $e) {
            $conn->rollBack(); $error_message = 'Error: '.$e->getMessage();
        }
        break;

    /* ---------- DELETE ITEM ---------- */
    case 'delete_portfolio':
        $id = filter_input(INPUT_POST,'id',FILTER_SANITIZE_NUMBER_INT);
        if ($id) {
            try {
                $conn->beginTransaction();

                /* fetch file paths */
                $stmt=$conn->prepare("SELECT media_url FROM portfolio_media WHERE portfolio_item_id=?");
                $stmt->execute([$id]);
                $files=$stmt->fetchAll(PDO::FETCH_COLUMN);

                /* delete db rows (ON DELETE CASCADE handles media rows) */
                $stmt=$conn->prepare("DELETE FROM portfolio_items WHERE id=?");
                $stmt->execute([$id]);

                /* delete physical files */
                foreach ($files as $f) if (is_file($f)) @unlink($f);

                $conn->commit();
                /* respond to AJAX with JSON */
                if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) &&
                    strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') {
                    header('Content-Type: application/json');
                    echo json_encode(['success'=>true]); exit;
                }
            } catch (Exception $e) {
                $conn->rollBack();
                if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) &&
                    strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') {
                    header('Content-Type: application/json', true, 500);
                    echo json_encode(['success'=>false]); exit;
                }
            }
        }
        break;
    }
}

/* ───── FETCH ALL ITEMS FOR LIST ─────────────────────────────── */
$portfolio_items = $conn->query("
    SELECT p.*,
           GROUP_CONCAT(m.media_url)  AS media_urls,
           GROUP_CONCAT(m.media_type) AS media_types
      FROM portfolio_items p
 LEFT JOIN portfolio_media m ON p.id = m.portfolio_item_id
  GROUP BY p.id
  ORDER BY p.created_at DESC
")->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1.0">
<title>CMS Admin Dashboard</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.7.2/font/bootstrap-icons.css" rel="stylesheet">
<style>
  .sidebar{min-height:100vh;background:#343a40;color:#fff}
  .content{padding:20px}
  .portfolio-item{margin-bottom:20px;padding:15px;border:1px solid #dee2e6;border-radius:4px}
  .media-preview{max-width:200px;max-height:200px;object-fit:cover}
</style>
</head>
<body>
<div class="container-fluid">
  <div class="row">
    <!-- Sidebar -->
    <aside class="col-md-3 col-lg-2 sidebar p-3">
      <h3 class="mb-4">CMS Dashboard</h3>
      <nav class="nav flex-column">
        <a class="nav-link text-white active" href="#portfolio">Portfolio</a>
        <a class="nav-link text-white" href="../portfolio-clients.php"   target="_blank">View Clients Page</a>
        <a class="nav-link text-white" href="../portfolio-fineart.php"   target="_blank">View Fine Art Page</a>
        <a class="nav-link text-white" href="../portfolio-portraits.php" target="_blank">View Portraits Page</a>
        <a class="nav-link text-white" href="../portfolio-travel.php"    target="_blank">View Travel Page</a>
        <a class="nav-link text-white" href="?logout=1">Logout</a>
      </nav>
    </aside>

    <!-- Main -->
    <main class="col-md-9 col-lg-10 content">

      <h2 class="mb-4">Portfolio Management</h2>

      <!-- ADD NEW ITEM -->
      <div class="card mb-4">
        <div class="card-body">
          <h5 class="card-title">Add New Portfolio Item</h5>

          <?php if (!empty($success_message)): ?>
            <div class="alert alert-success"><?= $success_message; ?></div>
          <?php elseif (!empty($error_message)): ?>
            <div class="alert alert-danger"><?= $error_message; ?></div>
          <?php endif; ?>

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
              <label for="media" class="form-label">Media Files</label>
              <input type="file" class="form-control" id="media" name="media[]"
                     accept="image/*,video/*,audio/*" multiple required>
              <small class="text-muted">
                Supported: JPG, PNG, GIF, WebP, MP4, WebM, OGG, MP3, WAV
              </small>
            </div>

            <button type="submit" class="btn btn-primary">Add Item</button>
          </form>
        </div>
      </div>

      <!-- CURRENT ITEMS -->
      <h3 class="mb-3">Current Portfolio Items</h3>

      <?php foreach ($portfolio_items as $item): ?>
        <?php
          $media_urls  = $item['media_urls']  ? explode(',',$item['media_urls'])  : [];
          $media_types = $item['media_types'] ? explode(',',$item['media_types']) : [];
        ?>
        <div class="portfolio-item" id="item-<?= $item['id']; ?>">
          <div class="row">
            <!-- Media thumbs -->
            <div class="col-md-2">
              <?php foreach ($media_urls as $i=>$url):
                     $type = $media_types[$i] ?? 'image'; ?>
                <?php if ($type==='image'): ?>
                  <img src="cms/<?= htmlspecialchars($url); ?>" class="media-preview mb-2">
                <?php elseif ($type==='video'): ?>
                  <video class="media-preview mb-2" controls>
                    <source src="cms/<?= htmlspecialchars($url); ?>" type="video/mp4">
                  </video>
                <?php elseif ($type==='audio'): ?>
                  <audio class="w-100 mb-2" controls>
                    <source src="cms/<?= htmlspecialchars($url); ?>" type="audio/mpeg">
                  </audio>
                <?php endif; ?>
              <?php endforeach; ?>
            </div>

            <!-- Info -->
            <div class="col-md-8">
              <h4><?= htmlspecialchars($item['title']); ?></h4>
              <p><?= nl2br(htmlspecialchars($item['description'])); ?></p>
              <small class="text-muted">Category: <?= htmlspecialchars($item['category']); ?></small><br>
              <small class="text-muted">Added: <?= date('F j, Y', strtotime($item['created_at'])); ?></small>
            </div>

            <!-- Delete -->
            <div class="col-md-2 d-flex align-items-start justify-content-end">
              <button type="button"
                      class="btn btn-danger delete-btn"
                      data-id="<?= $item['id']; ?>">
                <i class="bi bi-trash"></i> Delete
              </button>
            </div>
          </div>
        </div>
      <?php endforeach; ?>

    </main>
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

<script>
document.addEventListener('DOMContentLoaded', () => {

  /* ─── Persist category selection ───────────────────────── */
  const sel = document.getElementById('category');
  if (sel) {
    const stored = localStorage.getItem('cms_selected_category');
    if (stored) sel.value = stored;
    sel.addEventListener('change', () =>
      localStorage.setItem('cms_selected_category', sel.value)
    );
  }

  /* ─── AJAX delete (no page reload) ─────────────────────── */
  document.querySelectorAll('.delete-btn').forEach(btn => {
    btn.addEventListener('click', () => {
      if (!confirm('Delete this item?')) return;

      const id   = btn.dataset.id;
      const data = new URLSearchParams({ action:'delete_portfolio', id });

      fetch('', {
        method:'POST',
        headers:{
          'X-Requested-With':'XMLHttpRequest',
          'Content-Type':'application/x-www-form-urlencoded'
        },
        body:data
      })
      .then(r => r.ok ? r.json() : Promise.reject())
      .then(resp => {
        if (resp.success) {
          document.getElementById('item-'+id)?.remove();
        } else {
          alert('Server error while deleting.');
        }
      })
      .catch(() => alert('Network error while deleting.'));
    });
  });

});
</script>
</body>
</html>
