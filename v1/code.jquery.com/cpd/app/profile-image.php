<?php
require_once "../auth1.php";
require_role('CONTRACTOR');
require_once "../config.php";

$userId = (int) ($_SESSION['user_id'] ?? 0);
$stmt = $conn->prepare("SELECT image, full_name FROM user WHERE id = ? LIMIT 1");
$stmt->bind_param("i", $userId);
$stmt->execute();
$row = $stmt->get_result()->fetch_assoc();
$stmt->close();

$stored = (string) ($row['image'] ?? '');
$fileName = str_starts_with($stored, 'private/') ? substr($stored, 8) : '';

if ($fileName === '') {
    $name = trim((string) ($row['full_name'] ?? 'ECA'));
    $initial = mb_strtoupper(mb_substr($name !== '' ? $name : 'E', 0, 1));
    $safeInitial = htmlspecialchars($initial, ENT_QUOTES | ENT_XML1, 'UTF-8');
    $svg = '<svg xmlns="http://www.w3.org/2000/svg" width="128" height="128" viewBox="0 0 128 128">'
        . '<rect width="128" height="128" rx="18" fill="#192754"/>'
        . '<text x="64" y="80" text-anchor="middle" font-family="Arial,sans-serif" font-size="58" font-weight="700" fill="#fff">'
        . $safeInitial . '</text></svg>';
    header('Content-Type: image/svg+xml; charset=UTF-8');
    header('Cache-Control: private, max-age=3600');
    header('X-Content-Type-Options: nosniff');
    echo $svg;
    exit;
}

if (!preg_match('/\A[a-f0-9]{36}\.(?:jpg|png|webp)\z/', $fileName)) {
    http_response_code(404);
    exit;
}

$path = dirname(__DIR__, 4) . DIRECTORY_SEPARATOR . '_private'
    . DIRECTORY_SEPARATOR . 'cpd-profile' . DIRECTORY_SEPARATOR . $fileName;

if (!is_file($path) || !is_readable($path)) {
    http_response_code(404);
    exit;
}

$extensions = [
    'jpg' => 'image/jpeg',
    'png' => 'image/png',
    'webp' => 'image/webp',
];
$extension = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
$mime = $extensions[$extension] ?? 'application/octet-stream';

header('Content-Type: ' . $mime);
header('Content-Length: ' . (string) filesize($path));
header('Cache-Control: private, max-age=3600');
header('X-Content-Type-Options: nosniff');
readfile($path);
