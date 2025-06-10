<?php
require_once 'includes/config.php';
require_once 'includes/upload_config.php';

/* ───── Auth & session ───── */
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('Location: login.php'); exit;
}
if (isset($_SESSION['last_activity']) && time() - $_SESSION['last_activity'] > 1800) {
    session_unset(); session_destroy(); header('Location: login.php'); exit;
}
$_SESSION['last_activity'] = time();
if (isset($_GET['logout'])) { session_unset(); session_destroy(); header('Location: login.php'); exit; }

/* ───── DB ───── */
$conn = getDBConnection();

/* ───── add new / delete-all actions ───── */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {

    switch ($_POST['action']) {

    /* add a new portfolio item */
    case 'add_portfolio':
        $title       = htmlspecialchars(trim($_POST['title'] ?? ''), ENT_QUOTES);
        $description = htmlspecialchars(trim($_POST['description'] ?? ''), ENT_QUOTES);
        $category    = htmlspecialchars(trim($_POST['category'] ?? 'clients'), ENT_QUOTES);

        try {
            $conn->beginTransaction();
            $conn->prepare("INSERT INTO portfolio_items (title,description,category) VALUES (?,?,?)")
                 ->execute([$title,$description,$category]);
            $itemId = $conn->lastInsertId();

            if (!empty($_FILES['media']['name'][0])) {
                $allowed = array_merge(ALLOWED_IMAGE_TYPES, ALLOWED_VIDEO_TYPES, ALLOWED_AUDIO_TYPES);
                foreach ($_FILES['media']['name'] as $i=>$name) {
                    if ($_FILES['media']['error'][$i]) continue;
                    $type = $_FILES['media']['type'][$i];
                    if (!in_array($type,$allowed)) continue;

                    $mediaType = str_starts_with($type,'image/') ? 'image'
                              : (str_starts_with($type,'video/') ? 'video' : 'audio');
                    $ext   = strtolower(pathinfo($name,PATHINFO_EXTENSION));
                    $new   = uniqid().'.'.$ext;
                    $file  = UPLOAD_DIR.$new;
                    $url   = UPLOAD_URL.$new;

                    if (!move_uploaded_file($_FILES['media']['tmp_name'][$i],$file)) continue;

                    $order = $i;
                    $conn->prepare("INSERT INTO portfolio_media
                                    (portfolio_item_id,media_url,media_type,display_order)
                                    VALUES (?,?,?,?)")
                         ->execute([$itemId,$url,$mediaType,$order]);
                }
            }
            $conn->commit();
        } catch(Throwable $e){ $conn->rollBack(); }
        break;

    /* delete entire item ("Delete All") */
    case 'delete_portfolio':
        $id = (int)($_POST['id'] ?? 0);
        if ($id) {
            try {
                $conn->beginTransaction();
                $paths=$conn->prepare("SELECT media_url FROM portfolio_media WHERE portfolio_item_id=?");
                $paths->execute([$id]);
                foreach($paths->fetchAll(PDO::FETCH_COLUMN) as $url){
                    $path = realpath(__DIR__.'/../'.$url);
                    if ($path && str_starts_with($path,UPLOAD_DIR) && is_file($path)) @unlink($path);
                }
                $conn->prepare("DELETE FROM portfolio_items WHERE id=?")->execute([$id]);
                $conn->commit();
                if (isAjax()) { echo json_encode(['success'=>true]); exit; }
            } catch(Throwable $e){
                if ($conn->inTransaction()) $conn->rollBack();
                if (isAjax()){
                    http_response_code(500); echo json_encode(['success'=>false]); exit;
                }
            }
        }
        break;
    }
}

/* utility */
function isAjax(): bool { return strtolower($_SERVER['HTTP_X_REQUESTED_WITH'] ?? '')==='xmlhttprequest'; }

/* fetch items */
$items=$conn->query("SELECT * FROM portfolio_items ORDER BY created_at DESC")->fetchAll(PDO::FETCH_ASSOC);
?>
<!doctype html><html lang="en"><head>
<meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title>CMS Admin</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css" rel="stylesheet">
<style>
.sidebar{min-height:100vh;background:#343a40;color:#fff}
.content{padding:20px}
.portfolio-item{margin-bottom:25px;padding:15px;border:1px solid #dee2e6;border-radius:6px}
.media-preview-container{display:grid;gap:.5rem;grid-template-columns:repeat(4,1fr)}
.media-preview{width:100%;aspect-ratio:1/1;object-fit:cover;border-radius:6px;background:#eee;cursor:pointer}
.delete-check{position:absolute;top:6px;right:6px;width:18px;height:18px;z-index:2}
.delete-selected,.delete-all{width:auto;align-self:start}
.delete-selected[disabled]{opacity:.5;pointer-events:none}
.delete-selected.active{background:#dc3545;color:#fff;border-color:#b52a37;box-shadow:0 0 0 .2rem rgba(220,53,69,.25)}
.add-media-input{min-width:180px}
.gallery-grid{display:grid;grid-template-columns:repeat(5,1fr);gap:10px}
.gallery-grid .media-wrapper{position:relative}
.gallery-grid img{width:100%;aspect-ratio:1/1;object-fit:cover;border-radius:6px;cursor:pointer;transition:transform .2s}
.gallery-grid img:hover{transform:scale(1.05)}
#lightbox{position:fixed;inset:0;display:flex;align-items:center;justify-content:center;background:rgba(0,0,0,.9);
          opacity:0;transform:scale(.97);transition:opacity .25s,transform .25s;z-index:1056}
#lightbox.show{opacity:1;transform:scale(1)}
#lightbox img{max-width:95%;max-height:95%;border-radius:4px;box-shadow:0 0 20px rgba(0,0,0,.6)}
.light-nav{position:absolute;top:50%;transform:translateY(-50%);font-size:2rem;color:#fff;background:transparent;border:none;padding:.25rem .5rem;opacity:.7}
.light-nav:hover{opacity:1}.light-prev{left:15px}.light-next{right:15px}
#backToTop{position:fixed;bottom:25px;right:25px;display:none;z-index:1030}
.portfolio-item.fade-out{opacity:0;transition:opacity .5s}
</style>
</head><body>
<div class="container-fluid"><div class="row">
<aside class="col-md-3 col-lg-2 sidebar p-3">
  <h3 class="mb-4">CMS</h3>
  <nav class="nav flex-column">
    <span class="nav-link text-white fw-bold">Portfolio</span>
    <a class="nav-link text-white" href="?logout=1">Logout</a>
  </nav>
</aside>

<main class="col-md-9 col-lg-10 content">
  <h2 class="mb-4">Portfolio Management</h2>

  <!-- ADD NEW ------------------------------------------------------ -->
  <div class="card mb-4"><div class="card-body">
    <h5 class="card-title">Add New Portfolio Item</h5>
    <form method="POST" enctype="multipart/form-data">
      <input type="hidden" name="action" value="add_portfolio">
      <div class="mb-2"><label class="form-label">Title</label><input class="form-control" name="title" required></div>
      <div class="mb-2"><label class="form-label">Description</label><textarea class="form-control" name="description" rows="3" required></textarea></div>
      <div class="mb-2"><label class="form-label">Category</label>
        <select class="form-control" name="category" required>
          <option value="clients">Clients</option><option value="fineart">Fine Art</option>
          <option value="portraits">Portraits</option><option value="travel">Travel</option>
        </select></div>
      <div class="mb-2">
        <label class="form-label">Media Files</label>
        <input type="file" class="form-control" name="media[]" accept="image/*,video/*,audio/*" multiple required>
      </div>
      <button class="btn btn-primary">Add Item</button>
    </form>
  </div></div>

  <!-- ITEMS -------------------------------------------------------- -->
<?php foreach($items as $it):
      $media=$conn->prepare("SELECT id,media_url FROM portfolio_media
                             WHERE portfolio_item_id=? ORDER BY display_order,id");
      $media->execute([$it['id']]);
      $all=$media->fetchAll(PDO::FETCH_ASSOC);
      $thumbs=array_slice($all,0,8);
?>
  <div class="portfolio-item" id="item-<?=$it['id']?>">
    <div class="d-flex flex-wrap align-items-start gap-4">
      <div class="d-flex flex-column gap-2">
        <!-- DASHBOARD THUMBS -->
        <div class="media-preview-container">
        <?php foreach($thumbs as $m): ?>
          <div class="media-wrapper position-relative" data-full="<?=htmlspecialchars($m['media_url'])?>">
            <img src="<?=htmlspecialchars($m['media_url'])?>" class="media-preview">
            <input type="checkbox" class="form-check-input delete-check" data-media-id="<?=$m['id']?>">
          </div>
        <?php endforeach; ?>
        <?php if(count($all)>8): ?>
          <div class="d-flex align-items-center justify-content-center bg-secondary text-white rounded"
               style="width:120px;height:120px;cursor:pointer"
               data-bs-toggle="modal" data-bs-target="#gal<?=$it['id']?>">+<?=count($all)-8?></div>
        <?php endif; ?>
        </div>

        <!-- extra-media picker -->
        <form class="add-media-form" data-portfolio-id="<?=$it['id']?>" enctype="multipart/form-data">
          <input type="file" name="media[]" multiple class="form-control form-control-sm add-media-input">
        </form>

        <button class="btn btn-danger btn-sm delete-selected" disabled
                data-portfolio-id="<?=$it['id']?>">Delete Selected</button>

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

  <!-- MODAL GALLERY -->
  <div class="modal fade" id="gal<?=$it['id']?>" tabindex="-1">
    <div class="modal-dialog modal-xl modal-dialog-scrollable"><div class="modal-content bg-dark">
      <form class="delete-media-form" data-portfolio-id="<?=$it['id']?>">
        <div class="modal-header border-0 d-flex align-items-center justify-content-between">
          <div class="form-check d-flex align-items-center">
            <input type="checkbox" class="form-check-input select-all-modal" id="sel<?=$it['id']?>">
            <label for="sel<?=$it['id']?>" class="form-label mb-0 ms-2 text-white">Select All</label>
          </div>
          <button class="btn btn-danger btn-sm delete-selected" disabled>
            <i class="bi bi-trash"></i> Delete Selected
          </button>
        </div>
        <div class="modal-body"><div class="gallery-grid">
          <?php foreach($all as $m): ?>
          <div class="media-wrapper" data-full="<?=htmlspecialchars($m['media_url'])?>">
            <img src="<?=htmlspecialchars($m['media_url'])?>">
            <input type="checkbox" class="form-check-input delete-check" data-media-id="<?=$m['id']?>">
          </div>
          <?php endforeach; ?>
        </div></div>
      </form>
    </div></div>
  </div>
<?php endforeach; ?>
</main></div></div>

<!-- back-to-top + light-box -->
<button id="backToTop" class="btn btn-primary rounded-circle shadow"><i class="bi bi-chevron-up"></i></button>
<div id="lightbox" class="d-none">
  <button class="light-nav light-prev">&lt;</button>
  <img id="lightImg" src="">
  <button class="light-nav light-next">&gt;</button>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
/* helper ---------------------------------------------------------- */
function sendForm(url,data,method='POST'){
  const fd=data instanceof FormData?data:new FormData();
  if(!(data instanceof FormData))Object.entries(data).forEach(([k,v])=>fd.append(k,v));
  return fetch(url,{method,body:fd,headers:{'X-Requested-With':'XMLHttpRequest'}})
         .then(r=>{if(!r.ok)throw new Error(r.status);return r.json()});
}

document.addEventListener('DOMContentLoaded',()=>{

  /* auto-submit extra-media */
  document.querySelectorAll('.add-media-input').forEach(inp=>{
    inp.onchange=function(){ if(this.files.length) this.form.requestSubmit(); };
  });
  document.querySelectorAll('.add-media-form').forEach(f=>{
    f.onsubmit=e=>{
      e.preventDefault();
      const fd=new FormData(f); fd.append('portfolio_id',f.dataset.portfolioId);
      sendForm('api/add_media.php',fd).then(()=>location.reload())
            .catch(()=>alert('Add error'));
    };
  });

  /* DELETE ALL */
  document.querySelectorAll('.delete-all').forEach(btn=>{
    btn.onclick=()=>{
      if(!confirm('Delete ALL images in this section?')) return;
      const id=btn.dataset.id;
      sendForm('',{action:'delete_portfolio',id})
        .then(()=>{ const el=document.getElementById('item-'+id);
                     if(el){ el.classList.add('fade-out'); setTimeout(()=>el.remove(),500);} })
        .catch(()=>alert('Error deleting all'));
    };
  });

  /* batch-delete (dashboard & modal) */
  function initBatch(scope){
    const btn    = scope.querySelector('.delete-selected');
    const boxes  = [...scope.querySelectorAll('.delete-check')];
    const master = scope.querySelector('.select-all-modal');

    const syncUI=()=>{
      const any = boxes.some(b=>b.checked);
      btn.disabled = !any;
      btn.classList.toggle('active',any);
      if(master) master.checked = boxes.every(b=>b.checked);
    };
    scope.addEventListener('change',syncUI); syncUI();

    if(master){
      master.addEventListener('change',()=>{ boxes.forEach(b=>b.checked=master.checked); syncUI(); });
    }

    scope.addEventListener('submit',e=>{
      e.preventDefault();
      const ids=boxes.filter(b=>b.checked).map(b=>b.dataset.mediaId);
      if(!ids.length||!confirm('Delete selected?')) return;
      sendForm('api/delete_media.php',{media_ids:JSON.stringify(ids)})
        .then(r=>r.success?location.reload():alert('Delete error'))
        .catch(()=>alert('Network error'));
    });
  }
  document.querySelectorAll('.portfolio-item').forEach(initBatch);
  document.querySelectorAll('.delete-media-form').forEach(initBatch);

  /* light-box ------------------------------------------------------ */
  const lb=document.getElementById('lightbox'), img=document.getElementById('lightImg'),
        prev=lb.querySelector('.light-prev'), next=lb.querySelector('.light-next');
  let set=[],idx=0;
  const show=i=>{ idx=(i+set.length)%set.length; img.src=set[idx];
    lb.classList.remove('d-none'); requestAnimationFrame(()=>lb.classList.add('show')); };
  const close=()=>{ lb.classList.remove('show');
    lb.addEventListener('transitionend',()=>lb.classList.add('d-none'),{once:true}); };

  prev.onclick=()=>show(idx-1); next.onclick=()=>show(idx+1);
  lb.onclick=e=>{ if(e.target===lb) close(); };
  document.addEventListener('keyup',e=>{
    if(lb.classList.contains('show')){
      if(e.key==='Escape') close();
      if(e.key==='ArrowLeft') show(idx-1);
      if(e.key==='ArrowRight') show(idx+1);
    }
  });
  document.querySelectorAll('[data-full]').forEach(div=>{
    div.onclick=e=>{
      if(e.target.matches('.delete-check')) return;
      set=[...div.parentElement.querySelectorAll('[data-full]')].map(d=>d.dataset.full);
      show(set.indexOf(div.dataset.full));
    };
  });

  /* back-to-top */
  const top=document.getElementById('backToTop');
  window.onscroll=()=> top.style.display = scrollY>300 ? 'block' : 'none';
  top.onclick=()=> scrollTo({top:0,behavior:'smooth'});
});
</script>
</body></html>
