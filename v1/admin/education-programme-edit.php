<?php
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/_hub.php';
require_once __DIR__ . '/_education.php';
require_once __DIR__ . '/../includes/education.php';
require_once __DIR__ . '/../includes/audit.php';
eca_admin_require('education.manage');
header('Cache-Control: no-store');
$conn = eca_education_db();
eca_education_admin_require_ready($conn, 'Programme');
$id = (int) ($_GET['id'] ?? 0);
$row = ['title' => '', 'summary' => '', 'body' => '', 'status' => 'DRAFT', 'is_featured' => 0, 'sort_order' => 10];
if ($id > 0) {
    $found = eca_education_one($conn, 'SELECT * FROM education_programmes WHERE id = ? LIMIT 1', [$id]);
    if ($found) {
        $row = $found;
    }
}
$notice = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && eca_admin_csrf_ok($_POST['csrf_token'] ?? null)) {
    $title = trim((string) ($_POST['title'] ?? ''));
    $status = strtoupper((string) ($_POST['status'] ?? 'DRAFT'));
    if (!isset(eca_education_publish_statuses()[$status])) {
        $status = 'DRAFT';
    }
    $values = [
        $title,
        eca_education_unique_slug($conn, 'education_programmes', $title !== '' ? $title : 'programme', $id),
        trim((string) ($_POST['summary'] ?? '')),
        trim((string) ($_POST['body'] ?? '')),
        $status,
        !empty($_POST['is_featured']) ? 1 : 0,
        (int) ($_POST['sort_order'] ?? 10),
    ];
    if ($title === '') {
        $notice = 'Title is required.';
    } elseif ($id > 0) {
        $values[] = $id;
        $conn->prepare('UPDATE education_programmes SET title=?, slug=?, summary=?, body=?, status=?, is_featured=?, sort_order=? WHERE id=?')->execute($values);
        eca_audit('education.programme.update', 'education_programmes', (string) $id);
        $notice = 'Programme saved.';
        $row = eca_education_one($conn, 'SELECT * FROM education_programmes WHERE id = ? LIMIT 1', [$id]) ?: $row;
    } else {
        $conn->prepare('INSERT INTO education_programmes (title, slug, summary, body, status, is_featured, sort_order) VALUES (?,?,?,?,?,?,?)')->execute($values);
        $id = (int) $conn->lastInsertId();
        eca_audit('education.programme.create', 'education_programmes', (string) $id);
        header('Location: /admin/education-programme-edit.php?id=' . $id);
        exit;
    }
}
$style = eca_education_admin_field_style();
$csrf = eca_admin_csrf();
eca_admin_hub_start($id ? 'Edit programme' : 'Add programme', 'education');
?>
<div class="hub-hello"><h1 class="hub-hello-title"><?= $id ? 'Edit programme' : 'Add programme' ?></h1></div>
<?php eca_education_admin_nav('programmes'); ?>
<?php if ($notice): ?><p class="hub-card"><?= eca_admin_h($notice) ?></p><?php endif; ?>
<form class="hub-card" method="post">
    <input type="hidden" name="csrf_token" value="<?= eca_admin_h($csrf) ?>">
    <p><label>Title<br><input name="title" value="<?= eca_admin_h($row['title'] ?? '') ?>" class="form-control"></label></p>
    <p><label>Summary<br><textarea name="summary" rows="3" class="form-control"><?= eca_admin_h($row['summary'] ?? '') ?></textarea></label></p>
    <p><label>Details<br><textarea name="body" rows="7" class="form-control"><?= eca_admin_h($row['body'] ?? '') ?></textarea></label></p>
    <p><label>Sort order<br><input name="sort_order" value="<?= eca_admin_h((string) ($row['sort_order'] ?? '10')) ?>" class="form-control"></label></p>
    <p><label>Status
        <select name="status" class="form-control">
            <?php foreach (eca_education_publish_statuses() as $key => $label): ?>
                <option value="<?= eca_admin_h($key) ?>"<?= ($row['status'] ?? '') === $key ? ' selected' : '' ?>><?= eca_admin_h($label) ?></option>
            <?php endforeach; ?>
        </select>
    </label></p>
    <p><label><input type="checkbox" name="is_featured" value="1"<?= !empty($row['is_featured']) ? ' checked' : '' ?>> Feature this programme</label></p>
    <button class="hub-btn" type="submit">Save programme</button>
    <a class="hub-home-link" href="/admin/education-programmes.php">Back</a>
</form>
<?php eca_admin_hub_end(); ?>
