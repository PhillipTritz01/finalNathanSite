<?php
require_once 'includes/config.php';
require_once 'includes/upload_config.php';

/* ─────── Session / auth ─────── */
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
  header('Location: login.php'); exit;
}
if (isset($_SESSION['last_activity']) && time() - $_SESSION['last_activity'] > 1800) {
  session_unset(); session_destroy(); header('Location: login.php'); exit;
}
$_SESSION['last_activity'] = time();
if (isset($_GET['logout'])) { session_unset(); session_destroy(); header('Location: login.php'); exit; }

/* ─────── Merge raw-JSON into $_POST if frontend used fetch JSON ─────── */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !$_POST) {
  $raw = json_decode(file_get_contents('php://input'), true);
  if (is_array($raw)) foreach ($raw as $k=>$v) $_POST[$k] = $v;
}

/* ─────── DB ─────── */
$conn = getDBConnection();

/* ─────── Add-new / Delete-all ─────── */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {

  switch ($_POST['action']) {

  /* add a new portfolio item + its media */
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

        foreach ($_FILES['media']['name'] as $i => $name) {
          if ($_FILES['media']['error'][$i]) continue;
          $type = $_FILES['media']['type'][$i];
          if (!in_array($type,$allowed)) continue;

          $mediaType = str_starts_with($type,'image/') ? 'image'
                    : (str_starts_with($type,'video/') ? 'video' : 'audio');
          $ext  = strtolower(pathinfo($name, PATHINFO_EXTENSION));
          $new  = uniqid().'.'.$ext;
          $file = UPLOAD_DIR.$new;
          $url  = UPLOAD_URL.$new;

          if (!move_uploaded_file($_FILES['media']['tmp_name'][$i], $file)) continue;

          $conn->prepare("INSERT INTO portfolio_media
                          (portfolio_item_id,media_url,media_type,display_order)
                          VALUES (?,?,?,?)")
               ->execute([$itemId,$url,$mediaType,$i]);
        }
      }
      $conn->commit();
    } catch(Throwable $e){ $conn->rollBack(); }
    break;

  /* delete ALL media inside one portfolio item */
  case 'delete_portfolio':
    $id = (int)($_POST['id'] ?? 0);
    if ($id) {
      try {
        $conn->beginTransaction();
        $stmt=$conn->prepare("SELECT media_url FROM portfolio_media WHERE portfolio_item_id=?");
        $stmt->execute([$id]);
        foreach($stmt->fetchAll(PDO::FETCH_COLUMN) as $u){
          $p = realpath(__DIR__.'/../'.$u);
          if ($p && str_starts_with($p, UPLOAD_DIR) && is_file($p)) @unlink($p);
        }
        $conn->prepare("DELETE FROM portfolio_items WHERE id=?")->execute([$id]);
        $conn->commit();
        if (strtolower($_SERVER['HTTP_X_REQUESTED_WITH']??'')==='xmlhttprequest'){
          echo json_encode(['success'=>true]); exit;
        }
      }catch(Throwable $e){
        if($conn->inTransaction()) $conn->rollBack();
        if (strtolower($_SERVER['HTTP_X_REQUESTED_WITH']??'')==='xmlhttprequest'){
          http_response_code(500); echo json_encode(['success'=>false]); exit;
        }
      }
    }
    break;
  }
}

/* ─────── Fetch items ─────── */
$items = $conn->query("SELECT * FROM portfolio_items ORDER BY created_at DESC")
              ->fetchAll(PDO::FETCH_ASSOC);
?>
<!doctype html><html lang="en"><head>
<meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title>CMS Admin</title>

<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css" rel="stylesheet">

<!-- PhotoSwipe -->
<link  rel="stylesheet" href="../public/assets/css/photoswipe.css">
<script src="../public/assets/js/photoswipe.umd.min.js"></script>
<script src="../public/assets/js/photoswipe-lightbox.umd.min.js"></script>

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
#backToTop{position:fixed;bottom:25px;right:25px;display:none;z-index:1030}
.portfolio-item.fade-out{opacity:0;transition:opacity .5s}
.toast{background:#fff;border-radius:4px;box-shadow:0 .5rem 1rem rgba(0,0,0,.15)}
.toast-container{z-index:10860}
.loading-spinner{position:absolute;inset:0;display:flex;align-items:center;justify-content:center;background:rgba(0,0,0,.7);z-index:1}
.pswp__counter{font-size:1.05rem}
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

  <!-- ADD NEW -->
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
      <div class="mb-2"><label class="form-label">Media Files</label>
        <input type="file" class="form-control" name="media[]" accept="image/*,video/*,audio/*" multiple required>
      </div>
      <button class="btn btn-primary">Add Item</button>
    </form>
  </div></div>

<?php /* ITEMS LOOP */ foreach($items as $it):
      $rows=$conn->prepare("SELECT id,media_url FROM portfolio_media
                             WHERE portfolio_item_id=? ORDER BY display_order,id");
      $rows->execute([$it['id']]);
      $media=$rows->fetchAll(PDO::FETCH_ASSOC);
      $thumbs=array_slice($media,0,8);
?>
  <div class="portfolio-item" id="item-<?=$it['id']?>">
    <div class="d-flex flex-wrap align-items-start gap-4">
      <div class="d-flex flex-column gap-2">
        <!-- thumbs -->
        <div class="media-preview-container">
        <?php foreach($thumbs as $m): ?>
          <div class="media-wrapper position-relative">
            <img src="<?=htmlspecialchars($m['media_url'])?>" class="media-preview">
            <input type="checkbox" class="form-check-input delete-check" data-media-id="<?=$m['id']?>">
          </div>
        <?php endforeach; ?>
        <?php if(count($media)>8): ?>
          <div class="d-flex align-items-center justify-content-center bg-secondary text-white rounded"
               style="width:120px;height:120px;cursor:pointer"
               data-bs-toggle="modal" data-bs-target="#galleryModal"
               data-portfolio-id="<?=$it['id']?>"
               data-items='<?=htmlspecialchars(json_encode($media),ENT_QUOTES)?>'>
            +<?=count($media)-8?>
          </div>
        <?php endif; ?>
        </div>

        <!-- add-more picker -->
        <form class="add-media-form" data-portfolio-id="<?=$it['id']?>" enctype="multipart/form-data">
          <input type="file" name="media[]" multiple class="form-control form-control-sm add-media-input">
        </form>

        <button class="btn btn-danger btn-sm delete-selected" disabled>Delete Selected</button>
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
<?php endforeach; ?>

</main></div></div>

<!-- shared gallery modal -->
<div class="modal fade" id="galleryModal" tabindex="-1">
  <div class="modal-dialog modal-xl modal-dialog-scrollable"><div class="modal-content bg-dark">
    <form class="delete-media-form">
      <div class="modal-header border-0 d-flex align-items-center justify-content-between">
        <div class="form-check d-flex align-items-center">
          <input class="form-check-input select-all-modal" type="checkbox" id="selectAllModal">
          <label for="selectAllModal" class="form-label mb-0 ms-2 text-white">Select All</label>
        </div>
        <div class="d-flex gap-2">
          <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">
            <i class="bi bi-x-lg"></i> Close
          </button>
          <button type="submit" class="btn btn-danger btn-sm delete-selected" disabled>
            <i class="bi bi-trash"></i> Delete Selected
          </button>
        </div>
      </div>
      <div class="modal-body position-relative">
        <div class="loading-spinner d-none"><div class="spinner-border text-light"><span class="visually-hidden">Loading</span></div></div>
        <div class="gallery-grid"></div>
      </div>
    </form>
  </div></div>
</div>

<!-- toast -->
<div class="toast-container position-fixed bottom-0 end-0 p-3">
  <div id="toast" class="toast" data-bs-delay="3000">
    <div class="toast-header"><strong class="me-auto" id="toastTitle">Info</strong>
      <button type="button" class="btn-close" data-bs-dismiss="toast"></button>
    </div>
    <div class="toast-body" id="toastMsg"></div>
  </div>
</div>

<!-- back-to-top -->
<button id="backToTop" class="btn btn-primary rounded-circle shadow"><i class="bi bi-chevron-up"></i></button>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
/* ----- helper toast ----- */
function toast(title,msg,isOK=true){
  document.getElementById('toastTitle').textContent=title;
  document.getElementById('toastMsg').textContent=msg;
  document.getElementById('toast').classList.toggle('bg-danger',!isOK);
  bootstrap.Toast.getOrCreateInstance('#toast').show();
}
const postJSON=(url,obj)=>fetch(url,{
  method:'POST',
  headers:{'Content-Type':'application/json','X-Requested-With':'XMLHttpRequest'},
  body:JSON.stringify(obj)
}).then(r=>{if(!r.ok)throw Error(r.status);return r.json()});

/* -------------------------------------------------------------- */
document.addEventListener('DOMContentLoaded',()=>{

/* auto-submit extra-media */
document.querySelectorAll('.add-media-input').forEach(inp=>{
  inp.onchange=()=>{ if(inp.files.length) inp.form.requestSubmit(); };});
document.querySelectorAll('.add-media-form').forEach(f=>{
  f.onsubmit=e=>{
    e.preventDefault();
    const fd=new FormData(f); fd.append('portfolio_id',f.dataset.portfolioId);
    fetch('api/add_media.php',{method:'POST',body:fd,headers:{'X-Requested-With':'XMLHttpRequest'}})
      .then(()=>location.reload())
      .catch(()=>toast('Error','Upload failed',false));
  };
});

/* DELETE ALL */
document.querySelectorAll('.delete-all').forEach(btn=>{
  btn.onclick=()=>{
    if(!confirm('Delete ALL images in this section?'))return;
    postJSON('',{action:'delete_portfolio',id:+btn.dataset.id})
      .then(()=>{
        const el = document.getElementById('item-'+btn.dataset.id);
        if(el){
          el.classList.add('fade-out');
          el.addEventListener('transitionend', function handler(e) {
            if(e.propertyName==='opacity'){
              el.removeEventListener('transitionend', handler);
              el.remove();
            }
          });
        }
      })
      .catch(()=>toast('Error','Delete failed',false));
  };
});

/* dashboard batch-delete */
document.querySelectorAll('.portfolio-item').forEach(card=>{
  const btn   = card.querySelector('.delete-selected');
  const boxes = [...card.querySelectorAll('.delete-check')];
  const sync  = ()=>{btn.disabled=!boxes.some(b=>b.checked);btn.classList.toggle('active',!btn.disabled);};
  card.addEventListener('change',sync); sync();
  btn.onclick=()=>{
    const ids=boxes.filter(b=>b.checked).map(b=>+b.dataset.mediaId);
    if(!ids.length||!confirm('Delete selected?'))return;
    postJSON('api/delete_media.php',{media_ids:ids})
      .then(()=>{
        // Remove deleted thumbnails
        boxes.forEach(b=>{
          if(b.checked){
            const wrap = b.closest('.media-wrapper');
            if(wrap) wrap.remove();
          }
        });
        // Update the +N count
        const previewContainer = card.querySelector('.media-preview-container');
        const allMedia = Array.from(previewContainer.querySelectorAll('.media-wrapper'));
        const plusN = card.querySelector('[data-bs-toggle="modal"][data-bs-target="#galleryModal"]');
        // Get the total media count (including those not shown as thumbs)
        let totalMedia = allMedia.length;
        if(plusN){
          // Try to update the data-items attribute
          let items = [];
          try {
            items = JSON.parse(plusN.getAttribute('data-items')||'[]');
          } catch(e) {}
          // Remove deleted ids from items
          items = items.filter(m=>!ids.includes(m.id));
          plusN.setAttribute('data-items', JSON.stringify(items));
          // Update the +N text or hide if not needed
          const n = items.length - 8;
          if(n > 0){
            plusN.textContent = '+'+n;
          } else {
            plusN.style.display = 'none';
          }
          totalMedia = items.length;
        }
        // If no media left, fade out and remove the portfolio item
        if(totalMedia === 0){
          card.classList.add('fade-out');
          card.addEventListener('transitionend', function handler(e) {
            if(e.propertyName==='opacity'){
              card.removeEventListener('transitionend', handler);
              card.remove();
            }
          });
        }
        sync();
      })
      .catch(()=>toast('Error','Delete failed',false));
  };
});

/* back-to-top */
const top=document.getElementById('backToTop');
window.onscroll=()=>top.style.display=scrollY>300?'block':'none';
top.onclick=()=>scrollTo({top:0,behavior:'smooth'});

/* modal controller */
const modalEl=document.getElementById('galleryModal');
const modal   = new bootstrap.Modal(modalEl);
const grid    = modalEl.querySelector('.gallery-grid');
const spinner = modalEl.querySelector('.loading-spinner');
const selectAll = modalEl.querySelector('.select-all-modal');
const delBtn  = modalEl.querySelector('.delete-selected');
let currentIds=[], selected=new Set(), lightbox=null, currentPortfolioId=null;

/* open modal from +N */
document.querySelectorAll('[data-bs-target="#galleryModal"]').forEach(btn=>{
  btn.onclick=()=>{
    const items=JSON.parse(btn.dataset.items||'[]');
    currentIds = items.map(x=>x.id);
    selected.clear(); 
    buildGrid(items); 
    modal.show();
    // Store the portfolio id for dashboard sync
    currentPortfolioId = btn.getAttribute('data-portfolio-id');
  };
});

/* build grid + PhotoSwipe anchors */
function buildGrid(items){
  spinner.classList.remove('d-none'); 
  grid.innerHTML='';
  const pswpDiv=document.createElement('div');
  pswpDiv.className='pswp-gallery'; 
  pswpDiv.id='pswp-'+Date.now(); 
  pswpDiv.style.display='none';
  grid.before(pswpDiv);

  items.forEach((it,i)=>{
    const wrap=document.createElement('div');
    wrap.className='media-wrapper position-relative';
    wrap.innerHTML=`<img src="${it.media_url}" loading="lazy">
                    <input type="checkbox" class="form-check-input delete-check" data-media-id="${it.id}">`;
    grid.appendChild(wrap);

    if(it.media_url.match(/\.(jpe?g|png|gif|webp)$/i)){
      const a=document.createElement('a'); 
      a.href=it.media_url;
      a.dataset.pswpWidth=1600; 
      a.dataset.pswpHeight=1200;
      pswpDiv.appendChild(a);
      wrap.querySelector('img').onclick=()=>{
        if(lightbox) lightbox.loadAndOpen(i);
      };
      wrap.querySelector('img').style.cursor='zoom-in';
    }
  });

  /* PhotoSwipe instance */
  if(lightbox) {
    lightbox.destroy();
  }
  lightbox = new PhotoSwipeLightbox({
    gallery:'#'+pswpDiv.id, 
    children:'a',
    pswpModule:PhotoSwipe, 
    wheelToZoom:true, 
    arrowKeys:true,
    padding:{top:40,bottom:40,left:40,right:40}, 
    bgOpacity:1
  });
  lightbox.init();

  spinner.classList.add('d-none');
  attachCheckboxEvents();
  syncModalUI();
}

/* checkbox handlers in modal */
function attachCheckboxEvents(){
  // Always clear and rebuild selected set based on checked boxes
  selected.clear();
  grid.querySelectorAll('.delete-check').forEach(cb=>{
    if(cb.checked) selected.add(+cb.dataset.mediaId);
    cb.onchange=()=>{
      if(cb.checked) {
        selected.add(+cb.dataset.mediaId);
      } else {
        selected.delete(+cb.dataset.mediaId);
      }
      syncModalUI();
    };
  });
  selectAll.onchange=()=>{
    const isChecked = selectAll.checked;
    selected.clear();
    grid.querySelectorAll('.delete-check').forEach(cb=>{
      cb.checked = isChecked;
      if(isChecked) {
        selected.add(+cb.dataset.mediaId);
      }
    });
    syncModalUI();
  };
  syncModalUI(); // Always sync UI after attaching events
}

function syncModalUI(){
  // Rebuild selected set based on checked boxes
  selected.clear();
  const boxes = grid.querySelectorAll('.delete-check');
  boxes.forEach(cb=>{ if(cb.checked) selected.add(+cb.dataset.mediaId); });
  delBtn.disabled = !selected.size;
  delBtn.classList.toggle('active', selected.size > 0);
  const total = boxes.length;
  const checked = selected.size;
  selectAll.indeterminate = checked > 0 && checked < total;
  selectAll.checked = checked === total && total > 0;
}

/* submit delete in modal */
modalEl.querySelector('.delete-media-form').onsubmit=e=>{
  e.preventDefault();
  if(!selected.size||!confirm('Delete selected?')) return;
  spinner.classList.remove('d-none');
  const idsToDelete = Array.from(selected);
  postJSON('api/delete_media.php',{media_ids:idsToDelete})
    .then(()=>{
      // Remove deleted media from grid
      idsToDelete.forEach(id => {
        const cb = grid.querySelector('.delete-check[data-media-id="'+id+'"]');
        if(cb){
          const wrap = cb.closest('.media-wrapper');
          if(wrap) wrap.remove();
        }
      });
      // Update currentIds and selected
      currentIds = currentIds.filter(id => !idsToDelete.includes(id));
      selected.clear();
      spinner.classList.add('d-none');
      // --- Update dashboard +N count and data-items ---
      if(currentPortfolioId){
        const dashCard = document.querySelector('.portfolio-item[id="item-'+currentPortfolioId+'"]');
        if(dashCard){
          const plusN = dashCard.querySelector('[data-bs-toggle="modal"][data-bs-target="#galleryModal"]');
          if(plusN){
            let items = [];
            try {
              items = JSON.parse(plusN.getAttribute('data-items')||'[]');
            } catch(e) {}
            // Remove deleted ids from items
            items = items.filter(m=>!idsToDelete.includes(m.id));
            plusN.setAttribute('data-items', JSON.stringify(items));
            // Update the +N text or hide if not needed
            const n = items.length - 8;
            if(n > 0){
              plusN.textContent = '+'+n;
              plusN.style.display = '';
            } else {
              plusN.style.display = 'none';
            }
          }
        }
      }
      // If no media left, close modal and show toast
      if(grid.querySelectorAll('.media-wrapper').length === 0){
        modal.hide();
        toast('Info','All media deleted.');
      } else {
        attachCheckboxEvents();
        syncModalUI();
      }
    })
    .catch(()=>{
      spinner.classList.add('d-none');
      toast('Error','Delete failed',false);
    });
};

/* reset on close */
modalEl.addEventListener('hidden.bs.modal',()=>{
  grid.innerHTML=''; 
  selected.clear(); 
  if(lightbox){ 
    lightbox.destroy(); 
    lightbox=null; 
  }
});
});
</script>
</body></html>
