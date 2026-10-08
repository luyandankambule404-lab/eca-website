<?php
require_once __DIR__ . '/../auth.php';
require_once __DIR__ . '/../_hub.php';
require_once __DIR__ . '/../../includes/wellness.php';
require_once __DIR__ . '/../../includes/audit.php';
eca_admin_require('wellness.manage');
$conn = eca_wellness_db();
$notice = '';
if ($conn && $_SERVER['REQUEST_METHOD'] === 'POST' && eca_admin_csrf_ok($_POST['csrf_token'] ?? null)) {
    $action = (string) ($_POST['action'] ?? '');
    if ($action === 'save') {
        $name = trim((string) ($_POST['name'] ?? ''));
        $slug = trim((string) ($_POST['slug'] ?? ''));
        $sort = (int) ($_POST['sort_order'] ?? 0);
        $status = strtoupper((string) ($_POST['status'] ?? 'ACTIVE')) === 'INACTIVE' ? 'INACTIVE' : 'ACTIVE';
        $editId = (int) ($_POST['id'] ?? 0);
        if ($name === '') {
            $notice = 'Name is required.';
        } else {
            if ($slug === '') {
                $slug = strtolower(preg_replace('/[^a-z0-9]+/i', '-', $name) ?? 'cat');
            }
            if ($editId > 0) {
                $conn->prepare('UPDATE wellness_categories SET name=?, slug=?, sort_order=?, status=? WHERE id=?')
                    ->execute([$name, $slug, $sort, $status, $editId]);
                eca_audit('wellness.category.updated', 'wellness_categories', (string) $editId);
            } else {
                $conn->prepare('INSERT INTO wellness_categories (name, slug, sort_order, status) VALUES (?,?,?,?)')
                    ->execute([$name, $slug, $sort, $status]);
                eca_audit('wellness.category.created', 'wellness_categories', (string) $conn->lastInsertId());
            }
            $notice = 'Category saved.';
        }
    }
}
$categories = $conn ? eca_wellness_categories($conn, false) : [];
$csrf = eca_admin_csrf();
eca_admin_hub_start('Wellness categories', 'wellness');
eca_wellness_admin_subnav('categories');
?>
<div class="hub-hello"><h1 class="hub-hello-title">Wellness categories</h1></div>
<?php if ($notice): ?><p class="hub-card"><?= eca_admin_h($notice) ?></p><?php endif; ?>
<form class="hub-card" method="post">
    <input type="hidden" name="csrf_token" value="<?= eca_admin_h($csrf) ?>">
    <input type="hidden" name="action" value="save">
    <input type="hidden" name="id" value="0">
    <h2>Add category</h2>
    <p><label>Name<br><input name="name" required style="width:100%;padding:10px 12px;border-radius:10px;border:1px solid #d0d5dd;"></label></p>
    <p><label>Slug (optional)<br><input name="slug" style="width:100%;padding:10px 12px;border-radius:10px;border:1px solid #d0d5dd;"></label></p>
    <p><label>Sort order<br><input name="sort_order" type="number" value="0" style="padding:10px 12px;border-radius:10px;border:1px solid #d0d5dd;"></label></p>
    <p><label>Status<br><select name="status"><option value="ACTIVE">ACTIVE</option><option value="INACTIVE">INACTIVE</option></select></label></p>
    <button class="hub-btn" type="submit">Add</button>
</form>
<div class="hub-card eca-table-panel">
<table class="hub-table">
    <thead><tr><th>Name</th><th>Slug</th><th>Order</th><th>Status</th></tr></thead>
    <tbody>
    <?php foreach ($categories as $cat): ?>
        <tr>
            <td><?= eca_admin_h($cat['name']) ?></td>
            <td><?= eca_admin_h($cat['slug']) ?></td>
            <td><?= (int) ($cat['sort_order'] ?? 0) ?></td>
            <td><?= eca_admin_h($cat['status']) ?></td>
        </tr>
    <?php endforeach; ?>
    </tbody>
</table>
</div>
<?php eca_admin_hub_end(); ?>
