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
    $title       = htmlspecialchars(trim($_POST['title'] ?? ''), ENT_QUOTES, 'UTF-8');
    $description = htmlspecialchars(trim($_POST['description'] ?? ''), ENT_QUOTES, 'UTF-8');
    $category    = htmlspecialchars(trim($_POST['category'] ?? 'clients'), ENT_QUOTES, 'UTF-8');

    try {
      $conn->beginTransaction();

      $stmt = $conn->prepare(
        "INSERT INTO portfolio_items (title, description, category) VALUES (?,?,?)"
      );
      $stmt->execute([$title, $description, $category]);
      $portfolio_id = $conn->lastInsertId();

      if (!empty($_FILES['media']['name'][0])) {
        $errors = validateFileUpload($_FILES['media']);
        if ($errors) throw new Exception(implode("\n", $errors));

        $total = count($_FILES['media']['name']);
        for ($i = 0; $i < $total; $i++) {
          if ($_FILES['media']['error'][$i] !== 0) continue;

          $type = $_FILES['media']['type'][$i];
          $ext  = strtolower(pathinfo($_FILES['media']['name'][$i], PATHINFO_EXTENSION));

          $media_type = in_array($type, ALLOWED_IMAGE_TYPES) ? 'image' :
                        (in_array($type, ALLOWED_VIDEO_TYPES) ? 'video' :
                        (in_array($type, ALLOWED_AUDIO_TYPES) ? 'audio' : ''));

          if (!$media_type) continue;

          $target_dir = 'cms/uploads/';
          if (!is_dir($target_dir)) mkdir($target_dir, 0755, true);

          $new_name    = uniqid() . '.' . $ext;
          $target_path = $target_dir . $new_name;

          if (!move_uploaded_file($_FILES['media']['tmp_name'][$i], $target_path))
            throw new Exception('Failed to move uploaded file: ' . $_FILES['media']['name'][$i]);

          $stmt = $conn->prepare(
            "INSERT INTO portfolio_media (portfolio_item_id, media_url, media_type, display_order)
             VALUES (?,?,?,?)"
          );
          $stmt->execute([$portfolio_id, $target_path, $media_type, $i]);
        }
      }

      $conn->commit();
      $success_message = 'Portfolio item added successfully!';
    } catch (Exception $e) {
      $conn->rollBack();
      $error_message = 'Error: ' . $e->getMessage();
    }
    break;

  /* ---------- DELETE ITEM ---------- */
  case 'delete_portfolio':
    $id = filter_input(INPUT_POST, 'id', FILTER_SANITIZE_NUMBER_INT);
    if ($id) {
      try {
        $conn->beginTransaction();

        $stmt = $conn->prepare("SELECT media_url FROM portfolio_media WHERE portfolio_item_id=?");
        $stmt->execute([$id]);
        $files = $stmt->fetchAll(PDO::FETCH_COLUMN);

        $stmt = $conn->prepare("DELETE FROM portfolio_items WHERE id=?");
        $stmt->execute([$id]);

        foreach ($files as $f) if (is_file($f)) @unlink($f);

        $conn->commit();
        if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) &&
            strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') {
          header('Content-Type: application/json');
          echo json_encode(['success' => true]); exit;
        }
      } catch (Exception $e) {
        $conn->rollBack();
        if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) &&
            strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') {
          header('Content-Type: application/json', true, 500);
          echo json_encode(['success' => false]); exit;
        }
      }
    }
    break;
  }
}

/* ───── FETCH ITEMS ──────────────────────────────────────────── */
$portfolio_items = $conn->query("
  SELECT  p.*,
          GROUP_CONCAT(m.media_url)  AS media_urls,
          GROUP_CONCAT(m.media_type) AS media_types
    FROM  portfolio_items p
 LEFT JOIN  portfolio_media m ON p.id = m.portfolio_item_id
  GROUP BY  p.id
  ORDER BY  p.created_at DESC
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
  .media-preview-container{display:grid;gap:.5rem;grid-template-columns:repeat(auto-fill,minmax(120px,1fr))}
  .media-wrapper{position:relative}
  .media-preview{width:100%;aspect-ratio:1/1;object-fit:cover;border-radius:8px;background:#eee}
  .media-controls{position:absolute;top:4px;right:4px;display:flex;gap:4px}
  .media-controls button{width:28px;height:28px;border-radius:50%;padding:0;display:flex;align-items:center;justify-content:center;font-size:1rem;line-height:1;opacity:.9}
  #add-preview-grid{display:flex;flex-wrap:wrap;gap:.5rem;margin-top:1rem}
  #add-preview-grid img{width:100px;height:100px;object-fit:cover;border-radius:6px}

  /* ――― Modal gallery styles ――― */
  .gallery-grid{display:grid;grid-template-columns:repeat(5,1fr);gap:10px}
  .gallery-grid img{width:100%;object-fit:cover;border-radius:6px;transition:transform .25s}
  .gallery-grid img:hover{transform:scale(1.08)}
  .modal.gallery .modal-dialog{max-width:92%;transform:scale(.9);transition:transform .4s ease,opacity .4s ease}
  .modal.gallery.show .modal-dialog{transform:scale(1)}
</style>
</head>
<body>
<div class="container-fluid">
  <div class="row">
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

    <main class="col-md-9 col-lg-10 content">
      <h2 class="mb-4">Portfolio Management</h2>

      <!-- ADD NEW ITEM (unchanged) -->
      <!-- … (code omitted for brevity – stays identical to previous version) … -->

      <!-- CURRENT ITEMS -->
      <h3 class="mb-3">Current Portfolio Items</h3>

      <?php foreach ($portfolio_items as $item): ?>
      <?php
        $media_urls  = $item['media_urls']  ? explode(',', $item['media_urls'])  : [];
        $media_types = $item['media_types'] ? explode(',', $item['media_types']) : [];
        $preview_urls = array_slice($media_urls, 0, 4);
      ?>
      <div class="portfolio-item" id="item-<?= $item['id']; ?>">
        <div class="row">
          <!-- —— Preview block —— -->
          <div class="col-md-3">
            <div class="media-preview-container" data-bs-toggle="modal"
                 data-bs-target="#gallery-<?= $item['id']; ?>" style="cursor:pointer;">
              <?php foreach ($preview_urls as $url): ?>
                <div class="media-wrapper">
                  <img src="<?= htmlspecialchars($url); ?>" class="media-preview"
                       onerror="this.src='https://via.placeholder.com/120x120?text=No+Image';">
                </div>
              <?php endforeach; ?>
              <?php if (count($media_urls) > 4): ?>
                <div class="d-flex align-items-center justify-content-center bg-secondary text-white rounded"
                     style="width:120px;height:120px;">+<?= count($media_urls)-4; ?></div>
              <?php endif; ?>
            </div>

            <!-- add-more form -->
            <form class="add-media-form mt-3" data-portfolio-id="<?= $item['id']; ?>" enctype="multipart/form-data">
              <input type="file" name="media[]" accept="image/*,video/*,audio/*" multiple style="width:160px;">
              <button type="submit" class="btn btn-sm btn-primary mt-1">Add</button>
            </form>
          </div>

          <!-- Info -->
          <div class="col-md-7">
            <h4><?= htmlspecialchars($item['title']); ?></h4>
            <p><?= nl2br(htmlspecialchars($item['description'])); ?></p>
            <small class="text-muted">Category: <?= htmlspecialchars($item['category']); ?></small><br>
            <small class="text-muted">Added: <?= date('F j, Y', strtotime($item['created_at'])); ?></small>
          </div>

          <!-- Delete item -->
          <div class="col-md-2 d-flex align-items-start justify-content-end">
            <button type="button" class="btn btn-danger delete-btn" data-id="<?= $item['id']; ?>">
              <i class="bi bi-trash"></i> Delete
            </button>
          </div>
        </div>
      </div>

      <!-- Modal gallery -->
      <div class="modal fade gallery" id="gallery-<?= $item['id']; ?>" tabindex="-1"
           aria-labelledby="galleryLabel-<?= $item['id']; ?>" aria-hidden="true">
        <div class="modal-dialog modal-xl modal-dialog-scrollable">
          <div class="modal-content bg-dark">
            <div class="modal-header border-0">
              <h5 class="modal-title text-white" id="galleryLabel-<?= $item['id']; ?>">
                <?= htmlspecialchars($item['title']); ?>
              </h5>
              <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
              <div class="gallery-grid">
                <?php foreach ($media_urls as $url): ?>
                  <a href="<?= htmlspecialchars($url); ?>" target="_blank">
                    <img src="<?= htmlspecialchars($url); ?>" alt="">
                  </a>
                <?php endforeach; ?>
              </div>
            </div>
          </div>
        </div>
      </div>
      <?php endforeach; ?>

    </main>
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

<script>
/* ==== fetch helper (with header) ==== */
function sendForm(url, dataObj, method='POST'){
  const fd = dataObj instanceof FormData ? dataObj : new FormData();
  if(!(dataObj instanceof FormData)){
    Object.entries(dataObj).forEach(([k,v])=>fd.append(k,v));
  }
  return fetch(url,{method,body:fd,headers:{'X-Requested-With':'XMLHttpRequest'}})
         .then(r=>{if(!r.ok)throw new Error(r.status);return r.json();});
}

document.addEventListener('DOMContentLoaded',()=>{

  /* Persist category selection */
  const sel=document.getElementById('category');
  if(sel){
    const stored=localStorage.getItem('cms_selected_category');
    if(stored)sel.value=stored;
    sel.addEventListener('change',()=>localStorage.setItem('cms_selected_category',sel.value));
  }

  /* live preview on add-new */
  const mediaInput=document.getElementById('media');
  const previewGrid=document.getElementById('add-preview-grid');
  if(mediaInput){
    mediaInput.addEventListener('change',e=>{
      previewGrid.innerHTML='';
      [...e.target.files].forEach(f=>{
        if(!f.type.startsWith('image/'))return;
        const img=document.createElement('img');
        img.src=URL.createObjectURL(f);
        previewGrid.appendChild(img);
      });
    });
  }

  /* delete portfolio item */
  document.querySelectorAll('.delete-btn').forEach(btn=>{
    btn.addEventListener('click',()=>{
      if(!confirm('Delete this item?'))return;
      sendForm('',{action:'delete_portfolio',id:btn.dataset.id})
       .then(resp=>resp.success?document.getElementById('item-'+btn.dataset.id)?.remove()
                                :alert('Server error while deleting.'))
       .catch(()=>alert('Network error while deleting.'));
    });
  });

  /* delete individual media */
  document.querySelectorAll('.btn-delete-media').forEach(btn=>{
    btn.addEventListener('click',function(){
      if(!confirm('Delete this media?'))return;
      sendForm('cms/api/delete_media.php',{
        portfolio_id:btn.dataset.portfolioId,
        media_index :btn.dataset.mediaIndex
      })
      .then(resp=>resp.success?btn.closest('[data-media-index]').remove()
                              :alert('Error deleting media.'));
    });
  });

  /* replace individual media */
  document.querySelectorAll('.btn-replace-media').forEach(btn=>{
    btn.addEventListener('click',function(){
      const picker=document.createElement('input');
      picker.type='file';picker.accept='image/*,video/*,audio/*';
      picker.onchange=e=>{
        const file=e.target.files[0];if(!file)return;
        const fd=new FormData();
        fd.append('portfolio_id',btn.dataset.portfolioId);
        fd.append('media_index', btn.dataset.mediaIndex);
        fd.append('file',file);
        sendForm('cms/api/replace_media.php',fd)
          .then(resp=>resp.success?location.reload():alert('Error replacing media.'));
      };
      picker.click();
    });
  });

  /* add more media */
  document.querySelectorAll('.add-media-form').forEach(form=>{
    form.addEventListener('submit',function(e){
      e.preventDefault();
      const fd=new FormData(this);
      fd.append('portfolio_id',this.dataset.portfolioId);
      sendForm('cms/api/add_media.php',fd)
        .then(resp=>resp.success?location.reload():alert('Error adding media.'))
        .catch(()=>alert('Network error.'));
    });
  });

});
</script>
</body>
</html>
