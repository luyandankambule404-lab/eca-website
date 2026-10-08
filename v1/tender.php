<?php
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/includes/public-page.php';
require_once __DIR__ . '/includes/http.php';
$id = (int) ($_GET['id'] ?? 0);
$db = new Database();
$conn = $db->getConnection(false);
$row = null;
if ($conn && $id > 0) {
    $stmt = $conn->prepare("SELECT * FROM tenders WHERE id = ? AND status = 'PUBLISHED' LIMIT 1");
    $stmt->execute([$id]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
}
if (!$row) {
    eca_not_found('That tender is not published.');
}
eca_public_page_start((string) $row['title'], 'Tenders', (string) $row['title'], (string) ($row['summary'] ?? ''), 'Tender');
?>
<div class="container-xxl py-4">
    <div class="card company-card shadow-sm">
        <div class="card-body">
            <p><?= nl2br(htmlspecialchars((string) ($row['body'] ?? $row['summary'] ?? ''), ENT_QUOTES, 'UTF-8')) ?></p>
            <?php if (!empty($row['closes_at'])): ?><p><strong>Closes:</strong> <?= htmlspecialchars((string) $row['closes_at'], ENT_QUOTES, 'UTF-8') ?></p><?php endif; ?>
            <p><a href="/tenders.php">Back to tenders</a></p>
        </div>
    </div>
</div>
<?php eca_public_page_end(); ?>
