<?php
require_once 'config.php'; // database connection

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $newsId = intval($_POST['news_id']);
    $action = $_POST['action'];

    if (!$newsId || !in_array($action, ['like', 'unlike'])) {
        echo json_encode(['success' => false]);
        exit;
    }

    // Get current count
    $stmt = $conn->prepare("SELECT count FROM news WHERE id = ?");
    $stmt->bind_param("i", $newsId);
    $stmt->execute();
    $stmt->bind_result($count);
    $stmt->fetch();
    $stmt->close();

    // Update count
    if ($action === 'like') {
        $count++;
    } else {
        $count = max(0, $count - 1);
    }

    $stmt = $conn->prepare("UPDATE news SET count = ? WHERE id = ?");
    $stmt->bind_param("ii", $count, $newsId);
    $success = $stmt->execute();
    $stmt->close();

    echo json_encode(['success' => $success, 'count' => $count]);
    exit;
}
?>
