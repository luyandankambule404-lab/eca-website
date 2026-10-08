<?php
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/_hub.php';
require_once __DIR__ . '/_education.php';
require_once __DIR__ . '/../includes/education.php';
require_once __DIR__ . '/../includes/audit.php';
eca_admin_require('education.manage');
header('Cache-Control: no-store');
$conn = eca_education_db();
eca_education_admin_require_ready($conn, 'Venues & categories');
$notice = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && eca_admin_csrf_ok($_POST['csrf_token'] ?? null)) {
    $action = (string) ($_POST['action'] ?? '');
    if ($action === 'venue') {
        $name = trim((string) ($_POST['name'] ?? ''));
        if ($name !== '') {
            $conn->prepare('INSERT INTO education_venues (name, address, status) VALUES (?,?,?)')->execute([
                $name,
                trim((string) ($_POST['address'] ?? '')),
                'ACTIVE',
            ]);
            eca_audit('education.venue.create', 'education_venues', (string) $conn->lastInsertId());
            $notice = 'Venue added.';
        }
    } elseif ($action === 'facilitator') {
        $name = trim((string) ($_POST['name'] ?? ''));
        if ($name !== '') {
            $conn->prepare('INSERT INTO education_facilitators (name, organisation, bio, status) VALUES (?,?,?,?)')->execute([
                $name,
                trim((string) ($_POST['organisation'] ?? '')),
                trim((string) ($_POST['bio'] ?? '')),
                'ACTIVE',
            ]);
            eca_audit('education.facilitator.create', 'education_facilitators', (string) $conn->lastInsertId());
            $notice = 'Facilitator added.';
        }
    } elseif ($action === 'category') {
        $name = trim((string) ($_POST['name'] ?? ''));
        $section = (string) ($_POST['section'] ?? 'knowledge');
        if (!in_array($section, ['knowledge', 'resources', 'policy'], true)) {
            $section = 'knowledge';
        }
        if ($name !== '') {
            $slug = eca_education_unique_slug($conn, 'education_categories', $name);
            $conn->prepare('INSERT INTO education_categories (section, name, slug, sort_order, status) VALUES (?,?,?,?,?)')->execute([
                $section,
                $name,
                $slug,
                (int) ($_POST['sort_order'] ?? 50),
                'ACTIVE',
            ]);
            eca_audit('education.category.create', 'education_categories', (string) $conn->lastInsertId());
            $notice = 'Category added.';
        }
    }
}
$style = eca_education_admin_field_style();
$csrf = eca_admin_csrf();
eca_admin_hub_start('Education library', 'education');
?>
<div class="hub-hello"><h1 class="hub-hello-title">Venues, facilitators and categories</h1><p>These lists feed the course, knowledge, policy and resource editors.</p></div>
<?php eca_education_admin_nav('library'); ?>
<?php if ($notice): ?><p class="hub-card"><?= eca_admin_h($notice) ?></p><?php endif; ?>

<form class="hub-card" method="post">
    <input type="hidden" name="csrf_token" value="<?= eca_admin_h($csrf) ?>">
    <input type="hidden" name="action" value="venue">
    <h2>Add venue</h2>
    <p><label>Name<br><input name="name" class="form-control"></label></p>
    <p><label>Address<br><input name="address" class="form-control"></label></p>
    <button class="hub-btn" type="submit">Save venue</button>
</form>
<div class="hub-card"><strong>Venues</strong><ul><?php foreach (eca_education_venues($conn, false) as $row): ?><li><?= eca_admin_h($row['name']) ?><?= !empty($row['address']) ? ' — ' . eca_admin_h($row['address']) : '' ?></li><?php endforeach; ?></ul></div>

<form class="hub-card" method="post">
    <input type="hidden" name="csrf_token" value="<?= eca_admin_h($csrf) ?>">
    <input type="hidden" name="action" value="facilitator">
    <h2>Add facilitator</h2>
    <p><label>Name<br><input name="name" class="form-control"></label></p>
    <p><label>Organisation<br><input name="organisation" class="form-control"></label></p>
    <p><label>Bio<br><textarea name="bio" rows="3" class="form-control"></textarea></label></p>
    <button class="hub-btn" type="submit">Save facilitator</button>
</form>
<div class="hub-card"><strong>Facilitators</strong><ul><?php foreach (eca_education_facilitators($conn, false) as $row): ?><li><?= eca_admin_h($row['name']) ?><?= !empty($row['organisation']) ? ' — ' . eca_admin_h($row['organisation']) : '' ?></li><?php endforeach; ?></ul></div>

<form class="hub-card" method="post">
    <input type="hidden" name="csrf_token" value="<?= eca_admin_h($csrf) ?>">
    <input type="hidden" name="action" value="category">
    <h2>Add category</h2>
    <p><label>Section
        <select name="section" class="form-control">
            <option value="knowledge">Knowledge</option>
            <option value="resources">Resources</option>
            <option value="policy">Policy</option>
        </select>
    </label></p>
    <p><label>Name<br><input name="name" class="form-control"></label></p>
    <p><label>Sort order<br><input name="sort_order" value="50" class="form-control"></label></p>
    <button class="hub-btn" type="submit">Save category</button>
</form>
<div class="hub-card"><strong>Categories</strong><ul>
<?php foreach (['knowledge', 'resources', 'policy'] as $section): ?>
    <?php foreach (eca_education_categories($conn, $section, false) as $row): ?>
        <li><?= eca_admin_h($section) ?> · <?= eca_admin_h($row['name']) ?></li>
    <?php endforeach; ?>
<?php endforeach; ?>
</ul></div>
<?php eca_admin_hub_end(); ?>
