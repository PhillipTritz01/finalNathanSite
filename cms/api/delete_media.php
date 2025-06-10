<?php
require_once '../includes/config.php';
require_once '../includes/upload_config.php';   // for UPLOAD_DIR

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
  http_response_code(405); echo json_encode(['success'=>false]); exit;
}

/* IDs arrive as JSON array of strings or ints */
$ids_json = $_POST['media_ids'] ?? '[]';
$ids      = json_decode($ids_json, true);

if (!is_array($ids) || !$ids) {
  http_response_code(400); echo json_encode(['success'=>false]); exit;
}
$ids = array_values(array_filter($ids,'ctype_digit'));       // keep numeric strings only
if (!$ids) { http_response_code(400); echo json_encode(['success'=>false]); exit; }

try {
  $conn = getDBConnection();
  $conn->beginTransaction();

  /* fetch file URLs first (so we can try to unlink) */
  $in = implode(',', array_fill(0,count($ids),'?'));
  $stmt = $conn->prepare("SELECT media_url FROM portfolio_media WHERE id IN ($in)");
  $stmt->execute($ids);
  $urls = $stmt->fetchAll(PDO::FETCH_COLUMN);

  /* delete DB rows unconditionally */
  $conn->prepare("DELETE FROM portfolio_media WHERE id IN ($in)")->execute($ids);

  /* attempt to unlink each file, but DON’T fail if it’s already gone */
  foreach ($urls as $u) {
      $path = realpath(__DIR__.'/../'.$u);            // cms/api/.. -> cms/<uploads>/file
      if ($path && str_starts_with($path, UPLOAD_DIR) && is_file($path)) {
          @unlink($path);
      }
  }

  $conn->commit();
  echo json_encode(['success'=>true]);
} catch (Throwable $e) {
  if ($conn->inTransaction()) $conn->rollBack();
  http_response_code(500); echo json_encode(['success'=>false]);
}
