<?php
require_once '../includes/config.php';
require_once '../includes/upload_config.php';

header('Content-Type: application/json');

// Security headers for API
SecurityHelper::setSecurityHeaders();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
  SecurityHelper::logSecurityEvent('INVALID_API_METHOD', 'Non-POST request to delete_media API');
  http_response_code(405); echo json_encode(['success'=>false]); exit;
}

/* read ids from form-field OR from raw JSON body */
$ids = null;

/* multipart / form-data case */
if (isset($_POST['media_ids'])) {
  $ids = json_decode($_POST['media_ids'], true);
}

/* raw JSON case */
if ($ids === null) {
  $payload = json_decode(file_get_contents('php://input'), true);
  /* payload could be {media_ids:[…]} OR just the array itself */
  if (isset($payload['media_ids']))      $ids = $payload['media_ids'];
  elseif (is_array($payload))            $ids = $payload;
}

if (!is_array($ids) || !$ids) {
  http_response_code(400); echo json_encode(['success'=>false]); exit;
}

/* keep only positive integers as strings */
$ids = array_values(array_filter($ids, fn($v)=>ctype_digit((string)$v) && $v>0));
if (!$ids) { http_response_code(400); echo json_encode(['success'=>false]); exit; }

try {
  $conn = getDBConnection();
  $conn->beginTransaction();

  $in = implode(',', array_fill(0, count($ids), '?'));
  $stmt = $conn->prepare("SELECT media_url FROM portfolio_media WHERE id IN ($in)");
  $stmt->execute($ids);
  $urls = $stmt->fetchAll(PDO::FETCH_COLUMN);

  $conn->prepare("DELETE FROM portfolio_media WHERE id IN ($in)")->execute($ids);

  foreach ($urls as $u) {
    $p = realpath(__DIR__.'/../'.$u);
    if ($p && str_starts_with($p, UPLOAD_DIR) && is_file($p)) @unlink($p);
  }

  $conn->commit();
  echo json_encode(['success'=>true]);
} catch(Exception $e){
  if ($conn->inTransaction()) $conn->rollBack();
  http_response_code(500); echo json_encode(['success'=>false]);
}
