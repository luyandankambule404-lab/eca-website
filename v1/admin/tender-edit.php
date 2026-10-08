<?php
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/_hub.php';
require_once __DIR__ . '/../includes/audit.php';
require_once __DIR__ . '/../includes/content.php';
eca_admin_require('content.manage');
$conn = eca_admin_db();
if (!$conn) { eca_server_error(); }
$id = (int) ($_GET['id'] ?? 0);
$row = ['title' => '', 'summary' => '', 'body' => '', 'status' => 'DRAFT', 'closes_at' => '', 'document_name' => ''];
if ($id > 0) {
    $stmt = $conn->prepare('SELECT * FROM tenders WHERE id = ? LIMIT 1');
    $stmt->execute([$id]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC) ?: $row;
}
$notice = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && eca_admin_csrf_ok($_POST['csrf_token'] ?? null)) {
    $title = trim((string) ($_POST['title'] ?? ''));
    $summary = trim((string) ($_POST['summary'] ?? ''));
    $body = trim((string) ($_POST['body'] ?? ''));
    $status = strtoupper((string) ($_POST['status'] ?? 'DRAFT'));
    $closes = trim((string) ($_POST['closes_at'] ?? '')) ?: null;
    if (!in_array($status, eca_content_statuses(), true)) {
        $status = 'DRAFT';
    }
    if ($title === '') {
        $notice = 'Title is required.';
    } elseif ($id > 0) {
        $conn->prepare('UPDATE tenders SET title=?, summary=?, body=?, status=?, closes_at=?, published_at = CASE WHEN ?="PUBLISHED" THEN COALESCE(published_at, NOW()) ELSE published_at END WHERE id=?')
            ->execute([$title, $summary, $body, $status, $closes, $status, $id]);
        eca_audit('tender.update', 'tenders', (string) $id);
        require_once __DIR__ . '/../includes/admin-ops.php';
        eca_audit_content_status('tenders', (string) $id, $status, $status === 'PUBLISHED');
        $notice = 'Tender saved.';
    } else {
        $conn->prepare('INSERT INTO tenders (title, summary, body, status, closes_at, published_at) VALUES (?,?,?,?,?, CASE WHEN ?="PUBLISHED" THEN NOW() ELSE NULL END)')
            ->execute([$title, $summary, $body, $status, $closes, $status]);
        $id = (int) $conn->lastInsertId();
        eca_audit('tender.create', 'tenders', (string) $id);
        require_once __DIR__ . '/../includes/admin-ops.php';
        eca_audit_content_status('tenders', (string) $id, $status, $status === 'PUBLISHED');
        header('Location: /admin/tender-edit.php?id=' . $id);
        exit;
    }
    $stmt = $conn->prepare('SELECT * FROM tenders WHERE id = ? LIMIT 1');
    $stmt->execute([$id]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC) ?: $row;
}
$csrf = eca_admin_csrf();
eca_admin_hub_start('Edit tender', 'tenders');
?>
<div class="hub-hello"><h1 class="hub-hello-title"><?= $id ? 'Edit tender' : 'Add tender' ?></h1></div>
<?php if ($notice): ?><p class="hub-card"><?= eca_admin_h($notice) ?></p><?php endif; ?>
<form class="hub-card" method="post">
    <input type="hidden" name="csrf_token" value="<?= eca_admin_h($csrf) ?>">
    <p><label>Title<br><input name="title" value="<?= eca_admin_h($row['title'] ?? '') ?>" class="form-control"></label></p>
    <p><label>Summary<br><textarea name="summary" rows="3" class="form-control"><?= eca_admin_h($row['summary'] ?? '') ?></textarea></label></p>
    <p><label>Details<br><textarea name="body" rows="6" class="form-control"><?= eca_admin_h($row['body'] ?? '') ?></textarea></label></p>
    <p><label>Closes<br><input name="closes_at" value="<?= eca_admin_h($row['closes_at'] ?? '') ?>" placeholder="YYYY-MM-DD HH:MM" class="form-control"></label></p>
    <p><label>Status
        <select name="status" class="form-control">
            <?php foreach (eca_content_statuses() as $opt): ?>
                <option value="<?= $opt ?>"<?= ($row['status'] ?? '') === $opt ? ' selected' : '' ?>><?= $opt ?></option>
            <?php endforeach; ?>
        </select>
    </label></p>
    <button class="hub-btn" type="submit">Save</button>
    <a class="hub-home-link" href="/admin/tenders.php">Back</a>
    <?php if ($id): ?><a class="hub-home-link" href="/tender.php?id=<?= (int) $id ?>">Public view</a><?php endif; ?>
</form>
<?php eca_admin_hub_end(); ?>
