<?php
ob_start(); // ✅ prevent early output issues

require_once 'config.php';
$db = new Database();
$conn = $db->getConnection();

$file = $_GET['file'] ?? '';
$file = trim($file);

if ($file === '' || strlen($file) > 200) {
  http_response_code(400);
  ob_end_clean();
  exit("Invalid file.");
}

// prevent traversal
$file = str_replace(["../", "..\\", "\0"], "", $file);

// allow pdf + images
$allowedExt = ['pdf','png','jpg','jpeg'];
$ext = strtolower(pathinfo($file, PATHINFO_EXTENSION));
if (!in_array($ext, $allowedExt)) {
  http_response_code(403);
  ob_end_clean();
  exit("Not allowed.");
}

// allowed folders
$allowedDirs = [
  __DIR__ . "/downloads/",
  __DIR__ . "/img/"
];

$path = null;
foreach ($allowedDirs as $dir) {
  $candidate = $dir . $file;
  if (is_file($candidate)) { $path = $candidate; break; }
}

if (!$path) {
  http_response_code(404);
  ob_end_clean();
  exit("File not found.");
}

// increment downloads
try {
  $sql = "INSERT INTO downloads (file_name, count)
          VALUES (:file, 1)
          ON DUPLICATE KEY UPDATE count = count + 1";
  $stmt = $conn->prepare($sql);
  $stmt->execute([':file' => $file]);
} catch (Exception $e) {
  // ignore
}

// Clean any buffered output BEFORE headers
ob_clean();

$mimeMap = [
  'pdf'  => 'application/pdf',
  'png'  => 'image/png',
  'jpg'  => 'image/jpeg',
  'jpeg' => 'image/jpeg'
];

header("Content-Description: File Transfer");
header("Content-Type: " . ($mimeMap[$ext] ?? "application/octet-stream"));
header('Content-Disposition: attachment; filename="' . basename($file) . '"');
header("Content-Length: " . filesize($path));
header("Cache-Control: no-store, no-cache, must-revalidate");
header("Pragma: no-cache");
header("Expires: 0");

readfile($path);
ob_end_flush();
exit;
