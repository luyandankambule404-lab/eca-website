<?php
require_once __DIR__ . '/includes/public-page.php';
require_once __DIR__ . '/includes/education.php';

$conn = eca_education_db();
$filter = strtoupper(trim((string) ($_GET['status'] ?? '')));
if (!in_array($filter, ['UPCOMING', 'COMPLETED'], true)) {
    $filter = '';
}
$courses = ($conn && eca_education_ready($conn)) ? eca_education_public_courses($conn, $filter) : [];

eca_public_page_start(
    'Training & CPD',
    'Membership development',
    'Training & CPD',
    'Upcoming and completed ECA training. Each course has its own page with dates, venue, audience, fees, CPD information and registration.',
    'Membership development / Training'
);
$types = eca_education_course_statuses();
?>
<div class="edu-wrap">
    <?php eca_education_subnav('training'); ?>
    <div class="edu-filters" aria-label="Filter training">
        <a href="/education-training.php" class="<?= $filter === '' ? 'is-active' : '' ?>">All courses</a>
        <a href="/education-training.php?status=UPCOMING" class="<?= $filter === 'UPCOMING' ? 'is-active' : '' ?>">Upcoming</a>
        <a href="/education-training.php?status=COMPLETED" class="<?= $filter === 'COMPLETED' ? 'is-active' : '' ?>">Completed</a>
    </div>
    <?php if (!$courses): ?>
        <?php eca_education_empty('No published training matches this view yet. Check back shortly or contact the ECA training desk.'); ?>
    <?php else: ?>
        <div class="edu-card-grid">
            <?php foreach ($courses as $course): ?>
                <article class="edu-card">
                    <span class="edu-badge <?= eca_education_status_class((string) $course['status']) ?>"><?= eca_education_h($types[$course['status']] ?? $course['status']) ?></span>
                    <h2><?= eca_education_h($course['title'] ?? '') ?></h2>
                    <p><?= eca_education_h(eca_education_course_dates($course)) ?> · <?= eca_education_h(eca_education_venue_name($course)) ?></p>
                    <div class="edu-actions">
                        <a class="edu-btn-ghost" href="/education-course.php?slug=<?= urlencode((string) $course['slug']) ?>">Course details</a>
                        <?php if (($course['status'] ?? '') === 'UPCOMING'): ?>
                            <a class="edu-btn" href="<?= eca_education_h(eca_education_register_href($course)) ?>">Register</a>
                        <?php endif; ?>
                    </div>
                </article>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>
<?php eca_public_page_end(); ?>
