<?php
require_once "../auth.php";
require_role('CONTRACTOR');
require_once "../config.php";
require_once "../cheader.php";

$user_email = (string)($_SESSION['email'] ?? '');

$stmt = $conn->prepare("
SELECT c.*, a.status
FROM courses c
JOIN cpd_applications a ON a.course_id = c.id
WHERE a.email = ?
AND a.status = 'Approved'
ORDER BY c.start_date
");
$stmt->bind_param("s", $user_email);
$stmt->execute();
$courses = $stmt->get_result();

if (!$courses) {
    http_response_code(503);
    exit('Unable to load your courses. Please try again shortly.');
}
?>

<div class="hub-learn-toolbar">
    <div>
        <h2>My CPD courses</h2>
        <p class="hub-sub">Approved training, progress and certificates.</p>
    </div>
    <div class="hub-learn-actions">
        <a class="hub-btn" href="/cpd/cpd_application.php">Apply for training</a>
        <a class="hub-btn-navy" href="/cpd/contractor/dashboard.php">Dashboard</a>
    </div>
</div>

<?php if ($courses->num_rows === 0): ?>
    <section class="hub-card">
        <div class="hub-empty-card">
            <div class="hub-empty-icon" aria-hidden="true"><i class="fa-solid fa-book-open"></i></div>
            <h3>You are not enrolled in a course yet</h3>
            <p>Approved applications appear here with progress and daily feedback.</p>
            <div class="hub-learn-actions hub-learn-actions-center">
                <a class="hub-btn" href="/cpd/cpd_application.php">Apply for training</a>
                <a class="hub-btn-navy" href="applications.php">View applications</a>
            </div>
        </div>
    </section>
<?php endif; ?>

<div class="hub-learn-stack">
<?php while ($c = $courses->fetch_assoc()):
    $start = !empty($c['start_date']) ? strtotime((string)$c['start_date']) : false;
    $end = !empty($c['end_date']) ? strtotime((string)$c['end_date']) : false;
    $now = time();
    $total_days = ($start && $end) ? max(1, (int)ceil(($end - $start) / 86400)) : 1;
    $passed_days = $start ? (int)floor(($now - $start) / 86400) : 0;
    $progress = ($passed_days / $total_days) * 100;
    if ($progress < 0) {
        $progress = 0;
    }
    if ($progress > 100) {
        $progress = 100;
    }

    $today = $start ? (int)(floor(($now - $start) / 86400) + 1) : 0;
    $course_id = (int)$c['id'];

    $check_stmt = $conn->prepare("
        SELECT id FROM cpd_daily_feedback
        WHERE user_id = ?
        AND course_id = ?
        AND day_number = ?
    ");
    $check_stmt->bind_param("sii", $user_email, $course_id, $today);
    $check_stmt->execute();
    $check = $check_stmt->get_result();
    $feedback_done = $check ? $check->num_rows : 0;

    $submitted_stmt = $conn->prepare("
        SELECT COUNT(*) c
        FROM cpd_daily_feedback
        WHERE user_id = ?
        AND course_id = ?
    ");
    $submitted_stmt->bind_param("si", $user_email, $course_id);
    $submitted_stmt->execute();
    $res = $submitted_stmt->get_result();
    $row = $res ? $res->fetch_assoc() : ['c' => 0];
    $submitted = (int)($row['c'] ?? 0);
    $completed = $submitted >= $total_days;
    $in_window = $start && $end && $now >= $start && $now <= $end;
?>
    <section class="hub-card hub-learn-course">
        <div class="hub-learn-course-top">
            <h3><?= e($c['title'] ?? '') ?></h3>
            <?php if ($start && $now < $start): ?>
                <span class="badge status-pending">Not started</span>
            <?php elseif ($completed): ?>
                <span class="badge status-approved">Complete</span>
            <?php else: ?>
                <span class="badge status-pending">In progress</span>
            <?php endif; ?>
        </div>
        <?php if (!empty($c['venue'])): ?>
            <p class="hub-sub"><?= e($c['venue']) ?></p>
        <?php endif; ?>

        <?php if ($start && $now >= $start): ?>
            <div class="hub-meter">
                <div class="hub-meter-top">
                    <span>Course timeline</span>
                    <strong><?= (int)round($progress) ?>%</strong>
                </div>
                <div class="hub-progress" aria-hidden="true">
                    <div class="hub-progress-bar" style="width:<?= (int)round($progress) ?>%;"></div>
                </div>
            </div>
        <?php endif; ?>

        <?php if ($feedback_done === 0 && $in_window): ?>
            <form class="hub-learn-feedback eca-form-panel" method="POST" action="submit_feedback.php">
                <?= function_exists('cpd_csrf_input') ? cpd_csrf_input() : '' ?>
                <input type="hidden" name="course_id" value="<?= $course_id ?>">
                <input type="hidden" name="day_number" value="<?= $today ?>">
                <div class="mb-3">
                    <label class="form-label" for="rating-<?= $course_id ?>">Rate today's training</label>
                    <select class="form-select" id="rating-<?= $course_id ?>" name="rating">
                        <option value="5">Excellent</option>
                        <option value="4">Good</option>
                        <option value="3">Average</option>
                        <option value="2">Poor</option>
                        <option value="1">Very Poor</option>
                    </select>
                </div>
                <div class="mb-3">
                    <label class="form-label" for="comment-<?= $course_id ?>">Comment</label>
                    <textarea class="form-control" id="comment-<?= $course_id ?>" name="comment" rows="3" placeholder="Your feedback"></textarea>
                </div>
                <button class="hub-btn" type="submit">Submit daily feedback</button>
            </form>
        <?php endif; ?>

        <div class="hub-learn-course-foot">
            <?php if ($completed): ?>
                <a class="hub-btn" href="download_certificate.php?course_id=<?= $course_id ?>">Download certificate</a>
            <?php else: ?>
                <span class="badge">Complete the course to unlock the certificate</span>
            <?php endif; ?>
        </div>
    </section>
<?php endwhile; ?>
</div>

<?php require_once "../footer.php"; ?>
