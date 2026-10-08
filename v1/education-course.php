<?php
require_once __DIR__ . '/includes/http.php';
require_once __DIR__ . '/includes/public-page.php';
require_once __DIR__ . '/includes/education.php';

$conn = eca_education_db();
$slug = trim((string) ($_GET['slug'] ?? ''));
$course = ($conn && $slug !== '' && eca_education_ready($conn)) ? eca_education_course_by_slug($conn, $slug) : null;
if (!$course || !in_array((string) ($course['status'] ?? ''), ['UPCOMING', 'COMPLETED'], true)) {
    eca_not_found('That training course was not found.');
}

$types = eca_education_course_statuses();
$status = (string) ($course['status'] ?? '');
$upcoming = $status === 'UPCOMING';
eca_public_page_start(
    (string) $course['title'],
    'Training & CPD',
    (string) $course['title'],
    (string) ($course['description'] ?? 'ECA training course'),
    'Membership development / Training'
);
?>
<div class="edu-wrap">
    <?php eca_education_subnav('training'); ?>
    <p><span class="edu-badge <?= eca_education_status_class($status) ?>"><?= eca_education_h($types[$status] ?? $status) ?></span></p>
    <dl class="edu-meta">
        <div class="edu-meta-row"><dt>Date</dt><dd><?= eca_education_h(eca_education_course_dates($course)) ?></dd></div>
        <div class="edu-meta-row"><dt>Venue</dt><dd><?= eca_education_h(eca_education_venue_name($course)) ?></dd></div>
        <div class="edu-meta-row"><dt>Target audience</dt><dd><?= eca_education_h($course['audience'] ?: 'ECA members and contractor teams') ?></dd></div>
        <div class="edu-meta-row"><dt>Fees</dt><dd><?= eca_education_h($course['fees'] ?: 'Contact ECA') ?></dd></div>
        <div class="edu-meta-row"><dt>CPD information</dt><dd><?= eca_education_h($course['cpd_info'] ?: (($course['cpd_points'] ?? '') !== '' && $course['cpd_points'] !== null ? $course['cpd_points'] . ' CPD points' : 'CPD details will be confirmed')) ?></dd></div>
        <?php if (!empty($course['facilitator_name'])): ?>
            <div class="edu-meta-row"><dt>Facilitator</dt><dd><?= eca_education_h($course['facilitator_name']) ?><?= !empty($course['facilitator_org']) ? ' · ' . eca_education_h($course['facilitator_org']) : '' ?></dd></div>
        <?php endif; ?>
        <div class="edu-meta-row"><dt>Training status</dt><dd><?= eca_education_h($types[$status] ?? $status) ?></dd></div>
    </dl>
    <div class="edu-prose">
        <h2>Course description</h2>
        <p><?= nl2br(eca_education_h((string) ($course['description'] ?? ''))) ?></p>
    </div>
    <div class="edu-actions">
        <?php if ($upcoming): ?>
            <a class="edu-btn" href="<?= eca_education_h(eca_education_register_href($course)) ?>">Register</a>
        <?php else: ?>
            <span class="edu-btn-ghost">Registration is closed</span>
        <?php endif; ?>
        <a class="edu-btn-ghost" href="<?= eca_education_h(eca_education_learner_href($conn, $course)) ?>">Learner Portal</a>
        <a class="edu-btn-ghost" href="/education-training.php">All training</a>
    </div>
</div>
<?php eca_public_page_end(); ?>
