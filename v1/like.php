<?php
/**
 * Resource like counter — public POST.
 * CSRF required (token or same-origin public post guard).
 */
require_once __DIR__ . '/includes/session.php';
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/includes/portal-db.php';

$conn = eca_portal_pdo(false);

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['file'])) {
    eca_require_public_post();
    $file = trim((string) $_POST['file']);
    $file = str_replace(["../", "..\\", "\0"], '', $file);
    if ($file === '' || !$conn) {
        echo '0';
        exit;
    }
    try {
        $sql = 'INSERT INTO likes (file_name, count) VALUES (:file, 1)
                ON DUPLICATE KEY UPDATE count = count + 1';
        $stmt = $conn->prepare($sql);
        $stmt->execute([':file' => $file]);
        $countStmt = $conn->prepare('SELECT count FROM likes WHERE file_name = :file LIMIT 1');
        $countStmt->execute([':file' => $file]);
        echo (string) ((int) $countStmt->fetchColumn());
    } catch (Throwable $e) {
        echo '0';
    }
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    eca_require_public_post();
    require __DIR__ . '/likes.php';
    exit;
}

http_response_code(405);
echo '0';
