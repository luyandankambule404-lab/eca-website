<?php
require_once "../auth.php";
require_role('CONTRACTOR');
require_once "../config.php";

$user_id    = (int)($_SESSION['user_id'] ?? 0);
$user_email = $_SESSION['email'] ?? '';

$total = $conn->prepare("
SELECT COALESCE(SUM(points),0) s
FROM cpd_points_ledger
WHERE user_id=?
");
$total->bind_param("i", $user_id);
$total->execute();
$total_points = (float)($total->get_result()->fetch_assoc()['s'] ?? 0);

$pending = $conn->prepare("
SELECT COUNT(*) c
FROM cpd_applications
WHERE email=? AND status='Pending'
");
$pending->bind_param("s", $user_email);
$pending->execute();
$pending_apps = (int)($pending->get_result()->fetch_assoc()['c'] ?? 0);

$target_points = 12;
$progress_pct  = min(100, round(($total_points / $target_points) * 100, 1));

$courses = $conn->prepare("
SELECT
a.id AS application_id,
a.course_id,
c.title,
c.start_date
FROM cpd_applications a
JOIN courses c ON c.id = a.course_id
WHERE a.email=?
AND a.status='Approved'
ORDER BY c.start_date DESC
");
$courses->bind_param("s", $user_email);
$courses->execute();
$result = $courses->get_result();

$total_days_stmt = $conn->prepare("
SELECT COUNT(DISTINCT attendance_date) c
FROM course_attendance
WHERE course_id=?
");
$days_stmt = $conn->prepare("
SELECT COUNT(*) c
FROM course_attendance
WHERE course_id=?
AND application_id=?
AND status='PRESENT'
");
$activity_stmt = $conn->prepare("
SELECT COUNT(*) c
FROM course_activity
WHERE course_id=?
AND application_id=?
");

$course_cards = [];
while ($course = $result->fetch_assoc()) {
    $course_id = (int)$course['course_id'];
    $app_id    = (int)$course['application_id'];

    $total_days_stmt->bind_param("i", $course_id);
    $total_days_stmt->execute();
    $total_days = (int)($total_days_stmt->get_result()->fetch_assoc()['c'] ?? 0);
    if ($total_days === 0) {
        $total_days = 1;
    }

    $days_stmt->bind_param("ii", $course_id, $app_id);
    $days_stmt->execute();
    $days = (int)($days_stmt->get_result()->fetch_assoc()['c'] ?? 0);
    $attendance_pct = round(($days / $total_days) * 100);

    $activity_stmt->bind_param("ii", $course_id, $app_id);
    $activity_stmt->execute();
    $activity = (int)($activity_stmt->get_result()->fetch_assoc()['c'] ?? 0);
    $activity_done = $activity > 0;
    $activity_pct = $activity_done ? 20 : 0;
    $progress = min(100, $attendance_pct + $activity_pct);
    $start_ts = !empty($course['start_date']) ? strtotime((string)$course['start_date']) : false;

    $course_cards[] = [
        'course_id' => $course_id,
        'app_id' => $app_id,
        'title' => (string)($course['title'] ?? ''),
        'start_label' => $start_ts ? date('d M Y', $start_ts) : '',
        'attendance_pct' => $attendance_pct,
        'activity_pct' => $activity_pct,
        'progress' => $progress,
        'activity_done' => $activity_done,
        'certificate_ready' => ($attendance_pct >= 80 && $activity_done),
        'status' => $progress >= 100 ? 'Completed' : 'In progress',
    ];
}

$active_count = count($course_cards);

require_once "../cheader.php";
?>

<div class="hub-learn-toolbar">
    <div>
        <h2>Your learning</h2>
        <p class="hub-sub">Track points, approved courses and what still needs your attention.</p>
    </div>
    <div class="hub-learn-actions">
        <a class="hub-btn" href="courses.php">My courses</a>
        <a class="hub-btn-navy" href="applications.php">Applications</a>
        <a class="hub-btn-navy" href="/cpd/contractor/transcript.php">Transcript</a>
    </div>
</div>

<div class="hub-stats">
    <a class="hub-stat" href="/cpd/contractor/transcript.php">
        <div class="hub-stat-label">CPD points</div>
        <div class="hub-stat-value"><?= e((string)number_format($total_points, 0)) ?></div>
        <div class="hub-stat-meta">Recorded on your transcript</div>
        <div class="hub-stat-link">Open transcript</div>
    </a>
    <a class="hub-stat" href="/cpd/contractor/transcript.php">
        <div class="hub-stat-label">Progress</div>
        <div class="hub-stat-value"><?= e((string)$progress_pct) ?>%</div>
        <div class="hub-stat-meta"><?= e((string)number_format($total_points, 0)) ?> of <?= (int)$target_points ?> target points</div>
        <div class="hub-stat-link">View record</div>
    </a>
    <a class="hub-stat" href="applications.php">
        <div class="hub-stat-label">Pending applications</div>
        <div class="hub-stat-value"><?= (int)$pending_apps ?></div>
        <div class="hub-stat-meta">Awaiting review</div>
        <div class="hub-stat-link">View applications</div>
    </a>
    <a class="hub-stat" href="courses.php">
        <div class="hub-stat-label">Active courses</div>
        <div class="hub-stat-value"><?= (int)$active_count ?></div>
        <div class="hub-stat-meta">Approved and in your list</div>
        <div class="hub-stat-link">Open courses</div>
    </a>
</div>

<div class="hub-tiles">
    <a class="hub-tile" href="courses.php">
        <h3>My courses</h3>
        <p>See progress, daily feedback and certificates.</p>
        <span>Open →</span>
    </a>
    <a class="hub-tile hub-tile-sand" href="applications.php">
        <h3>Applications</h3>
        <p>Check the status of training you have applied for.</p>
        <span>Open →</span>
    </a>
    <a class="hub-tile hub-tile-lilac" href="/cpd/contractor/transcript.php">
        <h3>Transcript</h3>
        <p>Your CPD points and course history.</p>
        <span>Open →</span>
    </a>
    <a class="hub-tile hub-tile-mint" href="/cpd/cpd_application.php">
        <h3>Apply for training</h3>
        <p>Submit a new course application.</p>
        <span>Apply →</span>
    </a>
</div>

<section class="hub-card hub-learn-section">
    <div class="hub-card-head">
        <h2>Active courses</h2>
        <a href="courses.php">View all</a>
    </div>

    <?php if (!$course_cards): ?>
        <div class="hub-empty-card">
            <div class="hub-empty-icon" aria-hidden="true"><i class="fa-solid fa-book-open"></i></div>
            <h3>No approved courses yet</h3>
            <p>When a training application is approved, it will show here with your progress.</p>
            <div class="hub-learn-actions hub-learn-actions-center">
                <a class="hub-btn" href="/cpd/cpd_application.php">Apply for training</a>
                <a class="hub-btn-navy" href="applications.php">Check applications</a>
            </div>
        </div>
    <?php else: ?>
        <div class="hub-learn-grid">
            <?php foreach ($course_cards as $card): ?>
                <article class="hub-learn-course">
                    <div class="hub-learn-course-top">
                        <h3><?= e($card['title']) ?></h3>
                        <span class="badge <?= $card['status'] === 'Completed' ? 'status-approved' : 'status-pending' ?>"><?= e($card['status']) ?></span>
                    </div>
                    <?php if ($card['start_label'] !== ''): ?>
                        <p class="hub-sub">Starts <?= e($card['start_label']) ?></p>
                    <?php endif; ?>

                    <div class="hub-meter">
                        <div class="hub-meter-top">
                            <span>Course progress</span>
                            <strong><?= (int)$card['progress'] ?>%</strong>
                        </div>
                        <div class="hub-progress" aria-hidden="true">
                            <div class="hub-progress-bar" style="width:<?= (int)$card['progress'] ?>%;"></div>
                        </div>
                    </div>

                    <div class="hub-learn-meta">
                        <div><b><?= (int)$card['attendance_pct'] ?>%</b>Attendance</div>
                        <div><b><?= (int)$card['activity_pct'] ?>%</b>Activity</div>
                        <div><b><?= (int)$card['progress'] ?>%</b>Total</div>
                    </div>

                    <div class="hub-learn-course-foot">
                        <?php if ($card['activity_done']): ?>
                            <span class="badge status-approved">Activity submitted</span>
                        <?php else: ?>
                            <a class="hub-btn" href="course_activity.php?course_id=<?= (int)$card['course_id'] ?>&amp;application_id=<?= (int)$card['app_id'] ?>">Submit activity</a>
                        <?php endif; ?>

                        <?php if ($card['certificate_ready']): ?>
                            <a class="hub-btn-navy" href="download_certificate.php?course_id=<?= (int)$card['course_id'] ?>">Download certificate</a>
                        <?php else: ?>
                            <span class="badge">Certificate locked</span>
                        <?php endif; ?>
                    </div>
                </article>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</section>

<?php require_once "../footer.php"; ?>
