<?php
require_once "../auth.php";
require_role('CONTRACTOR');
require_once "../config.php";

$course_id = (int)($_GET['course_id'] ?? $_POST['course_id'] ?? 0);
$app_id = (int)($_GET['application_id'] ?? $_POST['application_id'] ?? 0);

$msg = "";

if (isset($_POST['submit_activity']) && $course_id > 0) {
    $q1 = (string)($_POST['q1'] ?? '');
    $q2 = (string)($_POST['q2'] ?? '');
    $q3 = (string)($_POST['q3'] ?? '');
    $q4 = (string)($_POST['q4'] ?? '');

    $stmt = $conn->prepare("
        INSERT INTO course_activity
        (course_id, application_id, q1, q2, q3, q4, submitted_at)
        VALUES (?, ?, ?, ?, ?, ?, NOW())
    ");
    $stmt->bind_param("iissss", $course_id, $app_id, $q1, $q2, $q3, $q4);
    $stmt->execute();
    $msg = "Activity submitted successfully";
}

require_once "../cheader.php";
?>

<div class="hub-learn-toolbar">
    <div>
        <h2>Course activity</h2>
        <p class="hub-sub">Record what you learned so this course can count toward your progress.</p>
    </div>
    <div class="hub-learn-actions">
        <a class="hub-btn-navy" href="/cpd/contractor/dashboard.php">Dashboard</a>
    </div>
</div>

<section class="hub-card hub-learn-activity">
    <div class="hub-card-head">
        <h2>Course feedback activity</h2>
    </div>

    <?php if ($course_id < 1): ?>
        <p class="hub-empty">Open this form from an active course on your dashboard.</p>
    <?php else: ?>
        <?php if ($msg): ?>
            <div class="alert alert-success"><?= e($msg) ?></div>
        <?php endif; ?>

        <form class="hub-learn-feedback eca-form-panel" method="POST">
            <input type="hidden" name="course_id" value="<?= $course_id ?>">
            <input type="hidden" name="application_id" value="<?= $app_id ?>">

            <div class="mb-3">
                <label class="form-label" for="q1">1. What did you learn from this training?</label>
                <textarea class="form-control" id="q1" name="q1" rows="3" required></textarea>
            </div>
            <div class="mb-3">
                <label class="form-label" for="q2">2. How will you apply this knowledge in your work?</label>
                <textarea class="form-control" id="q2" name="q2" rows="3" required></textarea>
            </div>
            <div class="mb-3">
                <label class="form-label" for="q3">3. Rate the training quality</label>
                <select class="form-select" id="q3" name="q3">
                    <option>Excellent</option>
                    <option>Good</option>
                    <option>Average</option>
                    <option>Poor</option>
                </select>
            </div>
            <div class="mb-3">
                <label class="form-label" for="q4">4. Suggestions for improvement</label>
                <textarea class="form-control" id="q4" name="q4" rows="3"></textarea>
            </div>
            <button class="hub-btn" name="submit_activity" type="submit">Submit activity</button>
        </form>
    <?php endif; ?>
</section>

<?php require_once "../footer.php"; ?>
