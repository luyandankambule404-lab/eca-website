<?php
require_once __DIR__ . '/../auth.php';
require_once __DIR__ . '/../_hub.php';
require_once __DIR__ . '/../../includes/wellness.php';
require_once __DIR__ . '/../../includes/audit.php';
eca_admin_require('wellness.manage');
$conn = eca_wellness_db();
if (!$conn) {
    eca_server_error();
}
$id = (int) ($_GET['id'] ?? 0);
$row = ['title' => '', 'content' => '', 'status' => 'DRAFT', 'expires_at' => ''];
if ($id > 0) {
    $stmt = $conn->prepare('SELECT * FROM wellness_announcements WHERE id = ? LIMIT 1');
    $stmt->execute([$id]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC) ?: $row;
}
$notice = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && eca_admin_csrf_ok($_POST['csrf_token'] ?? null)) {
    $title = trim((string) ($_POST['title'] ?? ''));
    $content = trim((string) ($_POST['content'] ?? ''));
    $status = strtoupper((string) ($_POST['status'] ?? 'DRAFT'));
    if (!in_array($status, eca_content_statuses(), true)) {
        $status = 'DRAFT';
    }
    $expires = trim((string) ($_POST['expires_at'] ?? '')) ?: null;
    if ($title === '' || $content === '') {
        $notice = 'Title and content are required.';
    } elseif ($id > 0) {
        $pub = $status === 'PUBLISHED' ? ($row['published_at'] ?? date('Y-m-d H:i:s')) : $row['published_at'];
        $conn->prepare('UPDATE wellness_announcements SET title=?, content=?, status=?, published_at=?, expires_at=? WHERE id=?')
            ->execute([$title, $content, $status, $pub, $expires, $id]);
        eca_audit('wellness.announcement.updated', 'wellness_announcements', (string) $id);
        if ($status === 'PUBLISHED') {
            eca_audit('wellness.announcement.published', 'wellness_announcements', (string) $id);
        }
        $notice = 'Announcement saved.';
    } else {
        $conn->prepare(
            'INSERT INTO wellness_announcements (title, content, status, published_at, expires_at) VALUES (?,?,?,?,?)'
        )->execute([
            $title,
            $content,
            $status,
            $status === 'PUBLISHED' ? date('Y-m-d H:i:s') : null,
            $expires,
        ]);
        $id = (int) $conn->lastInsertId();
        eca_audit('wellness.announcement.created', 'wellness_announcements', (string) $id);
        if ($status === 'PUBLISHED') {
            eca_audit('wellness.announcement.published', 'wellness_announcements', (string) $id);
        }
        header('Location: /admin/wellness/announcement-edit.php?id=' . $id);
        exit;
    }
    $stmt = $conn->prepare('SELECT * FROM wellness_announcements WHERE id = ? LIMIT 1');
    $stmt->execute([$id]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC) ?: $row;
}
$csrf = eca_admin_csrf();
eca_admin_hub_start($id ? 'Edit announcement' : 'Add announcement', 'wellness');
eca_wellness_admin_subnav('announcements');
?>
<div class="hub-hello"><h1 class="hub-hello-title"><?= $id ? 'Edit announcement' : 'Add announcement' ?></h1></div>
<?php if ($notice): ?><p class="hub-card"><?= eca_admin_h($notice) ?></p><?php endif; ?>
<form class="hub-card" method="post">
    <input type="hidden" name="csrf_token" value="<?= eca_admin_h($csrf) ?>">
    <p><label>Title<br><input name="title" required value="<?= eca_admin_h($row['title'] ?? '') ?>" style="width:100%;padding:10px 12px;border-radius:10px;border:1px solid #d0d5dd;"></label></p>
    <p><label>Content<br><textarea name="content" required rows="6" style="width:100%;padding:10px 12px;border-radius:10px;border:1px solid #d0d5dd;"><?= eca_admin_h($row['content'] ?? '') ?></textarea></label></p>
    <p><label>Expiry (optional, YYYY-MM-DD HH:MM:SS)<br><input name="expires_at" value="<?= eca_admin_h($row['expires_at'] ?? '') ?>" style="width:100%;padding:10px 12px;border-radius:10px;border:1px solid #d0d5dd;"></label></p>
    <p><label>Status<br>
        <select name="status" style="padding:10px 12px;border-radius:10px;border:1px solid #d0d5dd;">
            <?php foreach (eca_content_statuses() as $st): ?>
                <option value="<?= $st ?>"<?= ($row['status'] ?? '') === $st ? ' selected' : '' ?>><?= $st ?></option>
            <?php endforeach; ?>
        </select>
    </label></p>
    <button class="hub-btn" type="submit">Save</button>
    <a class="hub-home-link" href="/admin/wellness/announcements.php">Back</a>
</form>
<?php eca_admin_hub_end(); ?>
