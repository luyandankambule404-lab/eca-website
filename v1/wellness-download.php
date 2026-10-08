<?php
require_once __DIR__ . '/includes/session.php';
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/includes/authz.php';
require_once __DIR__ . '/includes/wellness.php';
require_once __DIR__ . '/client/auth.php';

$id = (int) ($_GET['id'] ?? 0);
if ($id <= 0) {
    http_response_code(404);
    exit('Not found');
}

$conn = eca_wellness_db();
if (!$conn) {
    http_response_code(503);
    exit('Unavailable');
}

$stmt = $conn->prepare('SELECT * FROM wellness_resources WHERE id = ? LIMIT 1');
$stmt->execute([$id]);
$row = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$row || ($row['status'] ?? '') !== 'PUBLISHED') {
    http_response_code(404);
    exit('Not found');
}

$isPublic = !empty($row['is_public']);
$memberOk = !empty($_SESSION['eca_member']) && eca_can('wellness.view');
$adminOk = !empty($_SESSION['eca_admin']) && eca_can('wellness.manage');

if (!$isPublic && !$memberOk && !$adminOk) {
    header('Location: ' . eca_login_url('member', '/client/wellness/'));
    exit;
}

$file = trim((string) ($row['file_path'] ?? ''));
if ($file === '') {
    http_response_code(404);
    exit('No file');
}

$path = eca_wellness_file_path($file);
if (!$path) {
    http_response_code(404);
    exit('File missing');
}

$mime = 'application/octet-stream';
$ext = strtolower(pathinfo($file, PATHINFO_EXTENSION));
$map = [
    'pdf' => 'application/pdf',
    'jpg' => 'image/jpeg',
    'jpeg' => 'image/jpeg',
    'png' => 'image/png',
    'webp' => 'image/webp',
    'mp4' => 'video/mp4',
    'doc' => 'application/msword',
    'docx' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
];
if (isset($map[$ext])) {
    $mime = $map[$ext];
}

$inline = !empty($_GET['inline']) && $ext === 'mp4';
header('Content-Type: ' . $mime);
header('Content-Disposition: ' . ($inline ? 'inline' : 'attachment') . '; filename="' . basename($file) . '"');
header('Content-Length: ' . (string) filesize($path));
readfile($path);
exit;
