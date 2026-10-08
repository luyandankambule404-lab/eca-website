<?php
/*
|--------------------------------------------------------------------------
| ECA NEWS LIKE ENDPOINT
|--------------------------------------------------------------------------
| Compatible with the Database class already used by the website.
| It records one like per browser session and returns the latest count as JSON.
|--------------------------------------------------------------------------
*/

header('Content-Type: application/json; charset=UTF-8');
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

function sendJson($success, $count = 0, $message = '', $statusCode = 200)
{
    http_response_code($statusCode);
    echo json_encode([
        'success' => (bool)$success,
        'count'   => (int)$count,
        'message' => (string)$message,
    ]);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    sendJson(false, 0, 'Only POST requests are allowed.', 405);
}

$newsId = filter_input(INPUT_POST, 'news_id', FILTER_VALIDATE_INT);
$action = strtolower(trim((string)($_POST['action'] ?? 'like')));

if (!$newsId || $newsId < 1) {
    sendJson(false, 0, 'A valid news ID is required.', 422);
}

if (!in_array($action, ['like', 'unlike'], true)) {
    sendJson(false, 0, 'Invalid like action.', 422);
}

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/includes/portal-db.php';

try {
    $conn = eca_portal_pdo(false);

    if (!$conn instanceof PDO) {
        throw new RuntimeException('The database connection is unavailable.');
    }

    if (!isset($_SESSION['eca_news_likes']) || !is_array($_SESSION['eca_news_likes'])) {
        $_SESSION['eca_news_likes'] = [];
    }

    $sessionKey = (string)$newsId;
    $hasLiked   = isset($_SESSION['eca_news_likes'][$sessionKey]);

    $conn->beginTransaction();

    $check = $conn->prepare(
        "SELECT id, COALESCE(`count`, 0) AS like_count
         FROM news
         WHERE id = :news_id
         LIMIT 1
         FOR UPDATE"
    );
    $check->execute([':news_id' => $newsId]);
    $news = $check->fetch(PDO::FETCH_ASSOC);

    if (!$news) {
        $conn->rollBack();
        sendJson(false, 0, 'News article not found.', 404);
    }

    $currentCount = max(0, (int)$news['like_count']);
    $newCount     = $currentCount;

    if ($action === 'like' && !$hasLiked) {
        $newCount = $currentCount + 1;

        $update = $conn->prepare(
            "UPDATE news
             SET `count` = :like_count
             WHERE id = :news_id"
        );
        $update->execute([
            ':like_count' => $newCount,
            ':news_id'    => $newsId,
        ]);

        $_SESSION['eca_news_likes'][$sessionKey] = true;
    } elseif ($action === 'unlike' && $hasLiked) {
        $newCount = max(0, $currentCount - 1);

        $update = $conn->prepare(
            "UPDATE news
             SET `count` = :like_count
             WHERE id = :news_id"
        );
        $update->execute([
            ':like_count' => $newCount,
            ':news_id'    => $newsId,
        ]);

        unset($_SESSION['eca_news_likes'][$sessionKey]);
    }

    $conn->commit();

    sendJson(true, $newCount, $action === 'like' ? 'News liked.' : 'Like removed.');
} catch (Throwable $error) {
    if (isset($conn) && $conn instanceof PDO && $conn->inTransaction()) {
        $conn->rollBack();
    }

    error_log('ECA news like error: ' . $error->getMessage());
    sendJson(false, 0, 'The like could not be saved. Please try again.', 500);
}
