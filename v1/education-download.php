<?php
require_once __DIR__ . '/includes/education.php';

$id = (int) ($_GET['id'] ?? 0);
if ($id <= 0) {
    http_response_code(404);
    exit('Not found');
}

$conn = eca_education_db();
if (!$conn || !eca_education_ready($conn)) {
    http_response_code(503);
    exit('Unavailable');
}

$stmt = $conn->prepare('SELECT * FROM education_resources WHERE id = ? LIMIT 1');
$stmt->execute([$id]);
$row = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$row || ($row['status'] ?? '') !== 'PUBLISHED') {
    http_response_code(404);
    exit('Not found');
}

$file = trim((string) ($row['file_path'] ?? ''));
if ($file === '') {
    $url = trim((string) ($row['external_url'] ?? ''));
    if ($url !== '') {
        if (!empty($_GET['inline']) && str_starts_with($url, '/download.php') && !str_contains($url, 'inline=')) {
            $url .= (str_contains($url, '?') ? '&' : '?') . 'inline=1';
        }
        header('Location: ' . $url);
        exit;
    }
    http_response_code(404);
    exit('No file');
}

$path = eca_education_file_path($file);
if (!$path) {
    http_response_code(404);
    exit('File missing');
}

require_once __DIR__ . '/includes/public-file.php';
eca_send_public_file($path, $file, !empty($_GET['inline']));
