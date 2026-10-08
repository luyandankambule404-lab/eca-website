<?php
require_once __DIR__ . '/../auth.php';
require_once __DIR__ . '/../_hub.php';
require_once __DIR__ . '/../../includes/wellness.php';
require_once __DIR__ . '/../../includes/wellness-hub.php';
require_once __DIR__ . '/../../includes/audit.php';
eca_admin_require('wellness.manage');
$conn = eca_wellness_db();
if (!$conn) {
    eca_server_error();
}
eca_wellness_hub_ensure_schema($conn);
$id = (int) ($_GET['id'] ?? 0);
$row = [
    'title' => '', 'description' => '', 'body_text' => '', 'category_id' => '', 'external_url' => '',
    'status' => 'DRAFT', 'is_public' => 0, 'file_path' => '', 'hub_section' => '', 'kind' => '', 'topic_slug' => '',
];
if ($id > 0) {
    $stmt = $conn->prepare('SELECT * FROM wellness_resources WHERE id = ? LIMIT 1');
    $stmt->execute([$id]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC) ?: $row;
}
$typeKey = trim((string) ($_GET['type'] ?? ''));
if ($typeKey === '' && $id > 0) {
    $typeKey = eca_wellness_admin_type_from_row($row);
}
if ($typeKey === '' || !isset(eca_wellness_admin_content_types()[$typeKey])) {
    $typeKey = 'resource';
}
$type = eca_wellness_admin_content_type($typeKey);
if (($row['hub_section'] ?? '') === '' && ($row['title'] ?? '') === '') {
    $row['hub_section'] = $type['default_section'];
    $row['kind'] = $type['kind'];
}
$categories = eca_wellness_categories($conn, false);
$notice = '';
$wasDraft = ($row['status'] ?? 'DRAFT') !== 'PUBLISHED';
$videoMax = 32 * 1024 * 1024;
$docMax = 10 * 1024 * 1024;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && eca_admin_csrf_ok($_POST['csrf_token'] ?? null)) {
    $title = trim((string) ($_POST['title'] ?? ''));
    $desc = trim((string) ($_POST['description'] ?? ''));
    $body = trim((string) ($_POST['body_text'] ?? ''));
    $hubSection = !empty($type['lock_section'])
        ? (string) $type['default_section']
        : trim((string) ($_POST['hub_section'] ?? $type['default_section']));
    if (!in_array($hubSection, eca_wellness_hub_section_keys(), true)) {
        $hubSection = $type['default_section'];
    }
    $kind = !empty($type['lock_kind'])
        ? (string) $type['kind']
        : trim((string) ($_POST['kind'] ?? ''));
    if ($kind !== '' && !isset(eca_wellness_hub_kinds()[$kind])) {
        $kind = '';
    }
    $topicSlug = strtolower(trim((string) ($_POST['topic_slug'] ?? '')));
    $topicSlug = preg_replace('/[^a-z0-9-]/', '', $topicSlug) ?? '';
    $catId = (int) ($_POST['category_id'] ?? 0);
    $external = trim((string) ($_POST['external_url'] ?? ''));
    if ($external !== '' && function_exists('eca_wellness_safe_url') && eca_wellness_safe_url($external) === '') {
        $notice = 'The link must be a normal http or https address.';
        $external = '';
    }
    $status = strtoupper((string) ($_POST['status'] ?? 'DRAFT'));
    if (!in_array($status, eca_content_statuses(), true)) {
        $status = 'DRAFT';
    }
    $isPublic = !empty($_POST['is_public']) ? 1 : 0;
    $filePath = trim((string) ($row['file_path'] ?? ''));
    $fileMode = (string) ($type['file'] ?? '');
    if (!empty($_FILES['file']['tmp_name'])) {
        $ext = strtolower((string) pathinfo((string) ($_FILES['file']['name'] ?? ''), PATHINFO_EXTENSION));
        if ($fileMode === 'video' && $ext !== 'mp4') {
            $notice = 'Videos must be MP4 files.';
        } elseif ($fileMode === 'document' && !in_array($ext, ['pdf', 'doc', 'docx'], true)) {
            $notice = 'Documents must be PDF, DOC or DOCX.';
        } else {
            $max = $fileMode === 'video' ? $videoMax : $docMax;
            $stored = eca_wellness_store_upload($_FILES['file'], $typeKey === 'resource' ? 'res' : $typeKey, $max);
            if ($stored) {
                $filePath = $stored;
            } else {
                $notice = $fileMode === 'video'
                    ? 'Video upload failed. Use an MP4 up to 32 MB.'
                    : 'File upload failed or the type is not allowed.';
            }
        }
    }
    if ($title === '') {
        $notice = 'Title is required.';
    } elseif (!empty($type['body_required']) && $body === '') {
        $notice = $type['body_label'] . ' is required.';
    } elseif ($typeKey === 'video' && $filePath === '' && $external === '') {
        $notice = 'Upload an MP4 or add a YouTube / https video link.';
    } elseif ($typeKey === 'document' && $filePath === '') {
        $notice = 'Upload a PDF or Word document.';
    } elseif ($notice === '') {
        $pub = ($status === 'PUBLISHED') ? date('Y-m-d H:i:s') : ($row['published_at'] ?? null);
        if ($id > 0) {
            $conn->prepare(
                'UPDATE wellness_resources SET category_id=?, title=?, description=?, body_text=?, hub_section=?, kind=?, topic_slug=?, file_path=?, file_type=?, external_url=?, status=?, is_public=?, published_at=? WHERE id=?'
            )->execute([
                $catId > 0 ? $catId : null,
                $title,
                $desc,
                $body !== '' ? $body : null,
                $hubSection !== '' ? $hubSection : null,
                $kind !== '' ? $kind : null,
                $topicSlug !== '' ? $topicSlug : null,
                $filePath !== '' ? $filePath : null,
                $filePath !== '' ? pathinfo($filePath, PATHINFO_EXTENSION) : null,
                $external !== '' ? $external : null,
                $status,
                $isPublic,
                $pub,
                $id,
            ]);
            eca_audit('wellness.resource.updated', 'wellness_resources', (string) $id);
            if ($wasDraft && $status === 'PUBLISHED') {
                eca_audit('wellness.resource.published', 'wellness_resources', (string) $id);
            }
            $notice = ucfirst($type['singular']) . ' saved.';
        } else {
            $conn->prepare(
                'INSERT INTO wellness_resources (category_id, title, description, body_text, hub_section, kind, topic_slug, file_path, file_type, external_url, status, is_public, published_at)
                 VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?)'
            )->execute([
                $catId > 0 ? $catId : null,
                $title,
                $desc,
                $body !== '' ? $body : null,
                $hubSection !== '' ? $hubSection : null,
                $kind !== '' ? $kind : null,
                $topicSlug !== '' ? $topicSlug : null,
                $filePath !== '' ? $filePath : null,
                $filePath !== '' ? pathinfo($filePath, PATHINFO_EXTENSION) : null,
                $external !== '' ? $external : null,
                $status,
                $isPublic,
                $status === 'PUBLISHED' ? date('Y-m-d H:i:s') : null,
            ]);
            $id = (int) $conn->lastInsertId();
            eca_audit('wellness.resource.created', 'wellness_resources', (string) $id);
            if ($status === 'PUBLISHED') {
                eca_audit('wellness.resource.published', 'wellness_resources', (string) $id);
            }
            header('Location: /admin/wellness/resource-edit.php?type=' . rawurlencode($typeKey) . '&id=' . $id);
            exit;
        }
        $stmt = $conn->prepare('SELECT * FROM wellness_resources WHERE id = ? LIMIT 1');
        $stmt->execute([$id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC) ?: $row;
        $wasDraft = false;
    }
}

$csrf = eca_admin_csrf();
$back = $typeKey === 'resource'
    ? '/admin/wellness/resources.php'
    : '/admin/wellness/content.php?type=' . rawurlencode($typeKey);
$heading = $id ? 'Edit ' . $type['singular'] : $type['add'];
$field = '';
$topicOptions = ['' => '—'];
foreach (eca_wellness_mental_topics() as $slug => $topic) {
    $topicOptions[$slug] = $topic[0];
}
foreach (eca_wellness_dimensions() as $slug => $dim) {
    $topicOptions[$slug] = $dim[0];
}
$accept = [
    'video' => '.mp4,video/mp4',
    'document' => '.pdf,.doc,.docx',
    'any' => '.pdf,.doc,.docx,.jpg,.jpeg,.png,.webp,.mp4',
][$type['file'] ?? ''] ?? '';

eca_admin_hub_start($heading, 'wellness');
eca_wellness_admin_subnav($type['nav']);
?>
<div class="hub-hello">
    <h1 class="hub-hello-title"><?= eca_admin_h($heading) ?></h1>
    <p><?= eca_admin_h($type['help']) ?></p>
</div>
<?php if ($notice): ?><p class="hub-card"><?= eca_admin_h($notice) ?></p><?php endif; ?>
<form class="hub-card" method="post" enctype="multipart/form-data">
    <input type="hidden" name="csrf_token" value="<?= eca_admin_h($csrf) ?>">
    <p><label>Title<br><input name="title" required value="<?= eca_admin_h($row['title'] ?? '') ?>" class="form-control"></label></p>
    <p><label>Short summary<br><textarea name="description" rows="3" class="form-control"><?= eca_admin_h($row['description'] ?? '') ?></textarea></label></p>
    <p><label><?= eca_admin_h($type['body_label']) ?><?= !empty($type['body_required']) ? ' (required)' : '' ?><br>
        <textarea name="body_text" rows="8" class="form-control"><?= eca_admin_h($row['body_text'] ?? '') ?></textarea>
    </label></p>
    <?php if (empty($type['lock_section'])): ?>
        <p><label>Hub section<br>
            <select name="hub_section" class="form-control">
                <?php foreach (eca_wellness_section_labels() as $key => $label): ?>
                    <option value="<?= eca_admin_h($key) ?>"<?= (string) ($row['hub_section'] ?? '') === $key ? ' selected' : '' ?>><?= eca_admin_h($label) ?></option>
                <?php endforeach; ?>
            </select>
        </label></p>
    <?php endif; ?>
    <?php if (empty($type['lock_kind'])): ?>
        <p><label>Type<br>
            <select name="kind" class="form-control">
                <option value="">—</option>
                <?php foreach (eca_wellness_hub_kinds() as $key => $label): ?>
                    <option value="<?= eca_admin_h($key) ?>"<?= (string) ($row['kind'] ?? '') === $key ? ' selected' : '' ?>><?= eca_admin_h($label) ?></option>
                <?php endforeach; ?>
            </select>
        </label></p>
    <?php endif; ?>
    <?php if (!empty($type['topic'])): ?>
        <p><label>Topic or dimension (optional)<br>
            <select name="topic_slug" class="form-control">
                <?php foreach ($topicOptions as $slug => $label): ?>
                    <option value="<?= eca_admin_h($slug) ?>"<?= (string) ($row['topic_slug'] ?? '') === $slug ? ' selected' : '' ?>><?= eca_admin_h($label) ?></option>
                <?php endforeach; ?>
            </select>
        </label></p>
    <?php endif; ?>
    <p><label>Category<br>
        <select name="category_id" class="form-control">
            <option value="0">—</option>
            <?php foreach ($categories as $cat): ?>
                <option value="<?= (int) $cat['id'] ?>"<?= (int) ($row['category_id'] ?? 0) === (int) $cat['id'] ? ' selected' : '' ?>><?= eca_admin_h($cat['name']) ?></option>
            <?php endforeach; ?>
        </select>
    </label></p>
    <?php if (!empty($type['url'])): ?>
        <p><label><?= $typeKey === 'video' ? 'YouTube or https video link' : 'Optional https link' ?><br>
            <input name="external_url" value="<?= eca_admin_h($row['external_url'] ?? '') ?>" class="form-control">
        </label></p>
    <?php endif; ?>
    <?php if (($type['file'] ?? '') !== ''): ?>
        <p><label>
            <?php if (($type['file'] ?? '') === 'video'): ?>
                Video file (MP4, maximum 32 MB)
            <?php elseif (($type['file'] ?? '') === 'document'): ?>
                Document (PDF, DOC or DOCX, maximum 10 MB)
            <?php else: ?>
                File (PDF, Word, image or MP4)
            <?php endif; ?>
            <br>
            <input type="file" name="file"<?= $accept !== '' ? ' accept="' . eca_admin_h($accept) . '"' : '' ?>>
            <?php if (!empty($row['file_path'])): ?>
                <span class="hub-stat-meta">Current: <?= eca_admin_h($row['file_path']) ?></span>
            <?php endif; ?>
        </label></p>
    <?php endif; ?>
    <p><label><input type="checkbox" name="is_public" value="1"<?= !empty($row['is_public']) ? ' checked' : '' ?>> Show on the public Wellness Hub (non-sensitive only)</label></p>
    <p><label>Status<br>
        <select name="status" class="form-control">
            <?php foreach (eca_content_statuses() as $st): ?>
                <option value="<?= $st ?>"<?= ($row['status'] ?? '') === $st ? ' selected' : '' ?>><?= $st ?></option>
            <?php endforeach; ?>
        </select>
    </label></p>
    <button class="hub-btn" type="submit">Save</button>
    <a class="hub-home-link" href="<?= eca_admin_h($back) ?>">Back</a>
</form>
<?php eca_admin_hub_end(); ?>
