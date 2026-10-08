<?php
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/_hub.php';
require_once __DIR__ . '/_education.php';
require_once __DIR__ . '/../includes/education.php';
require_once __DIR__ . '/../includes/audit.php';
eca_admin_require('education.manage');
header('Cache-Control: no-store');

$conn = eca_education_db();
eca_education_admin_require_ready($conn, 'Education');
$notice = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && eca_admin_csrf_ok($_POST['csrf_token'] ?? null)) {
    $url = trim((string) ($_POST['learner_portal_url'] ?? ''));
    $label = trim((string) ($_POST['learner_portal_label'] ?? 'ACCESS LEARNER PORTAL'));
    if ($url === '') {
        $notice = 'Learner Portal URL is required.';
    } else {
        eca_education_set_setting($conn, 'learner_portal_url', $url);
        eca_education_set_setting($conn, 'learner_portal_label', $label !== '' ? $label : 'ACCESS LEARNER PORTAL');
        eca_audit('education.settings', 'education_settings', 'learner_portal');
        $notice = 'Learner Portal settings saved.';
    }
}
$counts = eca_education_counts($conn);
$csrf = eca_admin_csrf();
$style = eca_education_admin_field_style();
eca_admin_hub_start('Education', 'education');
?>
<div class="hub-hello">
    <h1 class="hub-hello-title">Education CMS</h1>
    <p>Publish courses, knowledge, programmes, policy briefings and downloadable resources. Featured items appear first on the public Education pages.</p>
</div>
<?php eca_education_admin_nav('overview'); ?>
<?php if ($notice): ?><p class="hub-card"><?= eca_admin_h($notice) ?></p><?php endif; ?>
<div class="hub-stats">
    <a class="hub-stat" href="/admin/education-courses.php"><div class="hub-stat-label">Courses</div><div class="hub-stat-value"><?= (int) $counts['courses'] ?></div><div class="hub-stat-meta"><?= (int) $counts['upcoming'] ?> upcoming</div></a>
    <a class="hub-stat" href="/admin/education-articles.php?kind=knowledge"><div class="hub-stat-label">Knowledge</div><div class="hub-stat-value"><?= (int) $counts['articles'] ?></div><div class="hub-stat-meta">Articles and guides</div></a>
    <a class="hub-stat" href="/admin/education-articles.php?kind=policy"><div class="hub-stat-label">Policy</div><div class="hub-stat-value"><?= (int) $counts['policy'] ?></div><div class="hub-stat-meta">Industry briefings</div></a>
    <a class="hub-stat" href="/admin/education-programmes.php"><div class="hub-stat-label">Programmes</div><div class="hub-stat-value"><?= (int) $counts['programmes'] ?></div><div class="hub-stat-meta">Contractor development</div></a>
    <a class="hub-stat" href="/admin/education-resources.php"><div class="hub-stat-label">Resources</div><div class="hub-stat-value"><?= (int) $counts['resources'] ?></div><div class="hub-stat-meta">Files and links</div></a>
</div>
<form class="hub-card" method="post">
    <input type="hidden" name="csrf_token" value="<?= eca_admin_h($csrf) ?>">
    <h2>Learner Portal</h2>
    <p>This default link is used by the public Learner Portal button and by courses that do not set their own portal URL.</p>
    <p><label>Portal URL<br><input name="learner_portal_url" value="<?= eca_admin_h(eca_education_setting($conn, 'learner_portal_url', '/cpd/login.php')) ?>" class="form-control"></label></p>
    <p><label>Button label<br><input name="learner_portal_label" value="<?= eca_admin_h(eca_education_setting($conn, 'learner_portal_label', 'ACCESS LEARNER PORTAL')) ?>" class="form-control"></label></p>
    <button class="hub-btn" type="submit">Save portal settings</button>
    <a class="hub-home-link" href="/education.php">View public Education</a>
</form>
<?php eca_admin_hub_end(); ?>
