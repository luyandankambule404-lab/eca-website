<?php
require_once __DIR__ . '/includes/session.php';
require_once __DIR__ . '/includes/public-page.php';
require_once __DIR__ . '/includes/education.php';

if (eca_is_signed_in()) {
    header('Location: /learner-portal.php');
    exit;
}

$conn = eca_education_db();
$portal = ($conn && eca_education_ready($conn))
    ? eca_education_learner_href($conn)
    : '/cpd/login.php';
$label = ($conn && eca_education_ready($conn))
    ? eca_education_setting($conn, 'learner_portal_label', 'ACCESS LEARNER PORTAL')
    : 'ACCESS LEARNER PORTAL';

eca_public_page_start(
    'Learner Portal',
    'Membership development',
    'Learner Portal',
    'A direct route into the ECA learner and student environment for courses, progress, materials and certificates.',
    'Membership development / Learner Portal'
);
?>
<div class="edu-wrap">
    <?php eca_education_subnav('learner'); ?>
    <div class="edu-learner">
        <div class="edu-prose">
            <p>The Learner Portal is the signed-in space for people taking ECA training. It will continue to grow, but the destination is already live through the CPD learner environment.</p>
            <p>Learners will be able to:</p>
            <ul>
                <li>View courses</li>
                <li>Track progress</li>
                <li>Access learning materials</li>
                <li>Watch videos</li>
                <li>Complete quizzes and assessments</li>
                <li>Receive announcements</li>
                <li>Access certificates</li>
                <li>View completed training</li>
            </ul>
            <div class="edu-actions">
                <a class="edu-btn" href="<?= eca_education_h($portal) ?>"><?= eca_education_h($label) ?></a>
                <a class="edu-btn-ghost" href="/education-training.php">Browse training</a>
            </div>
        </div>
        <aside class="edu-card">
            <p class="edu-kicker">How it connects</p>
            <h3>Courses link here</h3>
            <p>Each public course can carry its own Learner Portal link. Administrators set the default destination and can override it per course.</p>
        </aside>
    </div>
</div>
<?php eca_public_page_end(); ?>
