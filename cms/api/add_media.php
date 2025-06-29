<?php
require_once '../includes/config.php';
require_once '../includes/upload_config.php';   // UPLOAD_DIR & UPLOAD_URL

header('Content-Type: application/json');

// Security headers for API
SecurityHelper::setSecurityHeaders();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
  SecurityHelper::logSecurityEvent('INVALID_API_METHOD', 'Non-POST request to add_media API');
  http_response_code(405); echo json_encode(['success'=>false]); exit;
}

// Validate CSRF token
$csrfToken = $_POST['csrf_token'] ?? '';
if (!SecurityHelper::validateCSRF($csrfToken)) {
  SecurityHelper::logSecurityEvent('API_CSRF_VIOLATION', 'Invalid CSRF token in add_media API');
  http_response_code(403); echo json_encode(['success'=>false, 'error'=>'Security validation failed']); exit;
}

/* basic sanity - the JS always sends portfolio_id */
$itemId = SecurityHelper::sanitizeInput($_POST['portfolio_id'] ?? 0, 'int');
if (!$itemId || $itemId <= 0) { 
  SecurityHelper::logSecurityEvent('INVALID_API_INPUT', 'Invalid portfolio_id in add_media');
  http_response_code(400); echo json_encode(['success'=>false]); exit; 
}

/* nothing selected?  → silently succeed so the UI resets */
if (empty($_FILES['media']['name'][0])) {
  echo json_encode(['success'=>true]); exit;
}

$allowed = array_merge(ALLOWED_IMAGE_TYPES, ALLOWED_VIDEO_TYPES, ALLOWED_AUDIO_TYPES);

try {
  $conn = getDBConnection();
  $conn->beginTransaction();

  /* loop every file the user picked */
  foreach ($_FILES['media']['name'] as $idx => $name) {

      if ($_FILES['media']['error'][$idx] !== 0) continue;          // skip failures

      $type = $_FILES['media']['type'][$idx];
      if (!in_array($type, $allowed)) continue;                     // skip bad MIME

      /* work out media_type column */
      $mediaType = str_starts_with($type, 'image/') ? 'image'
                 : (str_starts_with($type, 'video/') ? 'video' : 'audio');

      /* unique name & full paths */
      $ext   = strtolower(pathinfo($name, PATHINFO_EXTENSION));
      $new   = uniqid().'.'.$ext;
      $disk  = UPLOAD_DIR.$new;     // physical
      $url   = UPLOAD_URL.$new;     // DB / <img src>

      if (!move_uploaded_file($_FILES['media']['tmp_name'][$idx], $disk)) {
          /* skip this file but keep processing others */
          continue;
      }

      /* use display_order = max+1 so new images appear last */
      $nextOrder = $conn->prepare("SELECT COALESCE(MAX(display_order)+1,0)
                                   FROM portfolio_media WHERE portfolio_item_id=?");
      $nextOrder->execute([$itemId]);
      $order = (int)$nextOrder->fetchColumn();

      $conn->prepare("INSERT INTO portfolio_media
                      (portfolio_item_id, media_url, media_type, display_order)
                      VALUES (?,?,?,?)")
           ->execute([$itemId, $url, $mediaType, $order]);
  }

  $conn->commit();
  echo json_encode(['success'=>true]);
} catch (Throwable $e) {
  if ($conn->inTransaction()) $conn->rollBack();
  http_response_code(500); echo json_encode(['success'=>false]);
} 