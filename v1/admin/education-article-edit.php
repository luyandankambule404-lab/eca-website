<?php
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/_hub.php';
require_once __DIR__ . '/_education.php';
require_once __DIR__ . '/../includes/education.php';
require_once __DIR__ . '/../includes/audit.php';
eca_admin_require('education.manage');
header('Cache-Control: no-store');
$conn = eca_education_db();
eca_education_admin_require_ready($conn, 'Article');
$id = (int) ($_GET['id'] ?? 0);
$kind = (string) ($_GET['kind'] ?? 'knowledge');
$row = [
    'kind' => $kind, 'title' => '', 'category_id' => '', 'resource_type' => 'article', 'summary' => '',
    'body' => '', 'what_changed' => '', 'why_matters' => '', 'contractor_action' => '', 'eca_support' => '',
    'video_url' => '', 'status' => 'DRAFT', 'is_featured' => 0,
];
if ($id > 0) {
    $found = eca_education_one($conn, 'SELECT * FROM education_articles WHERE id = ? LIMIT 1', [$id]);
    if ($found) {
        $row = $found;
        $kind = (string) ($found['kind'] ?? $kind);
    }
}
if (!in_array($kind, ['knowledge', 'policy'], true)) {
    $kind = 'knowledge';
}
$notice = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && eca_admin_csrf_ok($_POST['csrf_token'] ?? null)) {
    $kind = (string) ($_POST['kind'] ?? $kind);
    if (!in_array($kind, ['knowledge', 'policy'], true)) {
        $kind = 'knowledge';
    }
    $title = trim((string) ($_POST['title'] ?? ''));
    $status = strtoupper((string) ($_POST['status'] ?? 'DRAFT'));
    if (!isset(eca_education_publish_statuses()[$status])) {
        $status = 'DRAFT';
    }
    $values = [
        $kind,
        $title,
        eca_education_unique_slug($conn, 'education_articles', $title !== '' ? $title : 'article', $id, $kind),
        (int) ($_POST['category_id'] ?? 0) ?: null,
        trim((string) ($_POST['resource_type'] ?? 'article')),
        trim((string) ($_POST['summary'] ?? '')),
        trim((string) ($_POST['body'] ?? '')),
        trim((string) ($_POST['what_changed'] ?? '')),
        trim((string) ($_POST['why_matters'] ?? '')),
        trim((string) ($_POST['contractor_action'] ?? '')),
        trim((string) ($_POST['eca_support'] ?? '')),
        trim((string) ($_POST['video_url'] ?? '')),
        $status,
        !empty($_POST['is_featured']) ? 1 : 0,
        $status === 'PUBLISHED' ? date('Y-m-d H:i:s') : ($row['published_at'] ?? null),
    ];
    if ($title === '') {
        $notice = 'Title is required.';
    } elseif ($id > 0) {
        $values[] = $id;
        $conn->prepare(
            'UPDATE education_articles SET kind=?, title=?, slug=?, category_id=?, resource_type=?, summary=?, body=?, what_changed=?, why_matters=?, contractor_action=?, eca_support=?, video_url=?, status=?, is_featured=?, published_at=? WHERE id=?'
        )->execute($values);
        eca_audit('education.article.update', 'education_articles', (string) $id);
        $notice = 'Saved.';
        $row = eca_education_one($conn, 'SELECT * FROM education_articles WHERE id = ? LIMIT 1', [$id]) ?: $row;
    } else {
        $conn->prepare(
            'INSERT INTO education_articles (kind, title, slug, category_id, resource_type, summary, body, what_changed, why_matters, contractor_action, eca_support, video_url, status, is_featured, published_at)
             VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)'
        )->execute($values);
        $id = (int) $conn->lastInsertId();
        eca_audit('education.article.create', 'education_articles', (string) $id);
        header('Location: /admin/education-article-edit.php?id=' . $id);
        exit;
    }
}
$style = eca_education_admin_field_style();
$csrf = eca_admin_csrf();
$section = $kind === 'policy' ? 'policy' : 'knowledge';
eca_admin_hub_start($id ? 'Edit article' : 'Add article', 'education');
?>
<div class="hub-hello"><h1 class="hub-hello-title"><?= $id ? 'Edit' : 'Add' ?> <?= $kind === 'policy' ? 'policy update' : 'knowledge item' ?></h1></div>
<?php eca_education_admin_nav($section); ?>
<?php if ($notice): ?><p class="hub-card"><?= eca_admin_h($notice) ?></p><?php endif; ?>
<form class="hub-card" method="post">
    <input type="hidden" name="csrf_token" value="<?= eca_admin_h($csrf) ?>">
    <p><label>Type
        <select name="kind" class="form-control">
            <option value="knowledge"<?= $kind === 'knowledge' ? ' selected' : '' ?>>Knowledge</option>
            <option value="policy"<?= $kind === 'policy' ? ' selected' : '' ?>>Policy</option>
        </select>
    </label></p>
    <p><label>Title<br><input name="title" value="<?= eca_admin_h($row['title'] ?? '') ?>" class="form-control"></label></p>
    <p><label>Category<br>
        <select name="category_id" class="form-control">
            <option value="">None</option>
            <?php foreach (array_merge(eca_education_categories($conn, 'knowledge', false), eca_education_categories($conn, 'policy', false)) as $cat): ?>
                <option value="<?= (int) $cat['id'] ?>"<?= (int) ($row['category_id'] ?? 0) === (int) $cat['id'] ? ' selected' : '' ?>><?= eca_admin_h($cat['section'] . ' · ' . $cat['name']) ?></option>
            <?php endforeach; ?>
        </select>
    </label></p>
    <p><label>Resource type
        <select name="resource_type" class="form-control">
            <?php foreach (eca_education_knowledge_types() as $key => $label): ?>
                <option value="<?= eca_admin_h($key) ?>"<?= ($row['resource_type'] ?? '') === $key ? ' selected' : '' ?>><?= eca_admin_h($label) ?></option>
            <?php endforeach; ?>
        </select>
    </label></p>
    <p><label>Summary<br><textarea name="summary" rows="3" class="form-control"><?= eca_admin_h($row['summary'] ?? '') ?></textarea></label></p>
    <p><label>What changed?<br><textarea name="what_changed" rows="3" class="form-control"><?= eca_admin_h($row['what_changed'] ?? '') ?></textarea></label></p>
    <p><label>Why does it matter to contractors?<br><textarea name="why_matters" rows="3" class="form-control"><?= eca_admin_h($row['why_matters'] ?? '') ?></textarea></label></p>
    <p><label>What must contractors do?<br><textarea name="contractor_action" rows="3" class="form-control"><?= eca_admin_h($row['contractor_action'] ?? '') ?></textarea></label></p>
    <p><label>What support is available from ECA?<br><textarea name="eca_support" rows="3" class="form-control"><?= eca_admin_h($row['eca_support'] ?? '') ?></textarea></label></p>
    <p><label>Full article<br><textarea name="body" rows="6" class="form-control"><?= eca_admin_h($row['body'] ?? '') ?></textarea></label></p>
    <p><label>Video URL<br><input name="video_url" value="<?= eca_admin_h($row['video_url'] ?? '') ?>" class="form-control"></label></p>
    <p><label>Status
        <select name="status" class="form-control">
            <?php foreach (eca_education_publish_statuses() as $key => $label): ?>
                <option value="<?= eca_admin_h($key) ?>"<?= ($row['status'] ?? '') === $key ? ' selected' : '' ?>><?= eca_admin_h($label) ?></option>
            <?php endforeach; ?>
        </select>
    </label></p>
    <p><label><input type="checkbox" name="is_featured" value="1"<?= !empty($row['is_featured']) ? ' checked' : '' ?>> Feature this item</label></p>
    <button class="hub-btn" type="submit">Save</button>
    <a class="hub-home-link" href="/admin/education-articles.php?kind=<?= eca_admin_h($kind) ?>">Back</a>
</form>
<?php eca_admin_hub_end(); ?>
