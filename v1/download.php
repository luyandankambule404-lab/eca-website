<?php
ob_start();

require_once 'config.php';
require_once __DIR__ . '/includes/portal-db.php';
require_once __DIR__ . '/includes/content.php';

$file = $_GET['file'] ?? '';
$file = trim($file);

if ($file === '' || strlen($file) > 200) {
  http_response_code(400);
  ob_end_clean();
  exit('Invalid file.');
}

$file = str_replace(["../", "..\\", "\0"], '', $file);

$allowedExt = ['pdf', 'png', 'jpg', 'jpeg'];
$ext = strtolower(pathinfo($file, PATHINFO_EXTENSION));
if (!in_array($ext, $allowedExt, true)) {
  http_response_code(403);
  ob_end_clean();
  exit('Not allowed.');
}

$allowedDirs = [
  __DIR__ . '/downloads/',
  __DIR__ . '/documents/',
  __DIR__ . '/img/',
  dirname(__DIR__) . '/eca-pages/img/',
  dirname(__DIR__) . '/eca-pages/downloads/',
  dirname(__DIR__) . '/eca-pages/documents/',
];

$path = null;
foreach ($allowedDirs as $dir) {
  $candidate = rtrim($dir, '/\\') . DIRECTORY_SEPARATOR . $file;
  if (is_file($candidate)) {
    $path = $candidate;
    break;
  }
}

if (!$path) {
  http_response_code(404);
  ob_end_clean();
  header('Content-Type: text/html; charset=UTF-8');
  echo '<!DOCTYPE html><html><head><meta charset="utf-8"><title>Download unavailable</title></head><body>';
  echo '<p>This document is listed on the live site but the file is not in the local copy yet.</p>';
  echo '<p><a href="/resources.html">Back to resources</a></p>';
  echo '</body></html>';
  exit;
}

$inline = !empty($_GET['inline']);

try {
  $resourceConn = eca_portal_pdo(false);
  if ($resourceConn && !eca_resource_is_publicly_downloadable($resourceConn, $file)) {
    http_response_code(404);
    ob_end_clean();
    header('Content-Type: text/html; charset=UTF-8');
    echo '<!DOCTYPE html><html><head><meta charset="utf-8"><title>Download unavailable</title></head><body>';
    echo '<p>This document is not available.</p>';
    echo '<p><a href="/resources.php">Back to resources</a></p>';
    echo '</body></html>';
    exit;
  }
} catch (Throwable $e) {
  // keep serving unmanaged public files
}

if (!$inline) {
  try {
    $conn = eca_portal_pdo(false);
    if ($conn) {
      $sql = 'INSERT INTO downloads (file_name, count) VALUES (:file, 1)
              ON DUPLICATE KEY UPDATE count = count + 1';
      $stmt = $conn->prepare($sql);
      $stmt->execute([':file' => $file]);
    }
  } catch (Throwable $e) {
    // ignore count errors
  }
}

require_once __DIR__ . '/includes/public-file.php';
eca_send_public_file($path, $file, $inline);
