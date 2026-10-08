<?php
require_once "../auth.php";
require_role(['SUPPERADMIN', 'OFFICER', 'ADMIN']);
require_once "../config.php";

if (!($conn instanceof mysqli)) {
    http_response_code(503);
    require_once "../header.php";
    ?>
    <section class="card border-0 shadow-sm">
        <div class="card-body p-4 p-lg-5 text-center">
            <div class="display-6 text-danger mb-3" aria-hidden="true">
                <i class="fa-solid fa-database"></i>
            </div>
            <h2 class="h4">CPD database temporarily unavailable</h2>
            <p class="text-muted mb-4">
                Attendance records cannot be loaded right now. No information has been changed.
                Please retry shortly or contact the system administrator.
            </p>
            <a class="btn btn-primary" href="/cpd/admin/course_students.php">Try again</a>
            <a class="btn btn-outline-secondary ms-2" href="/index.php">Return home</a>
        </div>
    </section>
    <?php
    require_once "../footer.php";
    exit;
}

/* =========================
   HELPERS
========================= */
function h($value){
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}

function valid_date($date){
    return preg_match('/^\d{4}-\d{2}-\d{2}$/', (string)$date);
}

function redirect_attendance($course_id, $date){
    header("Location: course_students.php?course_id=" . (int)$course_id . "&date=" . urlencode($date));
    exit;
}

function table_exists($conn, $table){
    $table = $conn->real_escape_string($table);
    $res = $conn->query("SHOW TABLES LIKE '{$table}'");
    return $res && $res->num_rows > 0;
}

function save_attendance_status($conn, $course_id, $application_id, $date, $status, $marked_by){
    $course_id      = (int)$course_id;
    $application_id = (int)$application_id;
    $marked_by      = (int)$marked_by;
    $status         = strtoupper(trim((string)$status));

    if ($course_id <= 0 || $application_id <= 0 || !valid_date($date)) {
        return false;
    }

    if (!in_array($status, ['PRESENT', 'ABSENT', 'PENDING'], true)) {
        return false;
    }

    if ($status === 'PENDING') {
        $delete_stmt = $conn->prepare("
            DELETE FROM course_attendance
            WHERE course_id = ?
              AND application_id = ?
              AND attendance_date = ?
        ");

        if (!$delete_stmt) {
            return false;
        }

        $delete_stmt->bind_param("iis", $course_id, $application_id, $date);
        $ok = $delete_stmt->execute();
        $delete_stmt->close();

        return $ok;
    }

    $check_stmt = $conn->prepare("
        SELECT id
        FROM course_attendance
        WHERE course_id = ?
          AND application_id = ?
          AND attendance_date = ?
        LIMIT 1
    ");

    if (!$check_stmt) {
        return false;
    }

    $check_stmt->bind_param("iis", $course_id, $application_id, $date);
    $check_stmt->execute();
    $existing = $check_stmt->get_result()->fetch_assoc();
    $check_stmt->close();

    if ($existing) {
        $update_stmt = $conn->prepare("
            UPDATE course_attendance
            SET status = ?, marked_by = ?
            WHERE course_id = ?
              AND application_id = ?
              AND attendance_date = ?
        ");

        if (!$update_stmt) {
            return false;
        }

        $update_stmt->bind_param("siiis", $status, $marked_by, $course_id, $application_id, $date);
        $ok = $update_stmt->execute();
        $update_stmt->close();

        return $ok;
    }

    $insert_stmt = $conn->prepare("
        INSERT INTO course_attendance
        (course_id, application_id, attendance_date, status, marked_by)
        VALUES (?, ?, ?, ?, ?)
    ");

    if (!$insert_stmt) {
        return false;
    }

    $insert_stmt->bind_param("iissi", $course_id, $application_id, $date, $status, $marked_by);
    $ok = $insert_stmt->execute();
    $insert_stmt->close();

    return $ok;
}

/* =========================
   GET DATE
========================= */
$today = $_GET['date'] ?? ($_POST['attendance_date'] ?? date('Y-m-d'));

if (!valid_date($today)) {
    $today = date('Y-m-d');
}

/* =========================
   GET COURSE ID
========================= */
$course_id = 0;

if (isset($_GET['course_id']) && (int)$_GET['course_id'] > 0) {
    $course_id = (int)$_GET['course_id'];
} elseif (isset($_POST['course_id']) && (int)$_POST['course_id'] > 0) {
    $course_id = (int)$_POST['course_id'];
} else {
    $open_stmt = $conn->prepare("
        SELECT id
        FROM courses
        WHERE status = 'OPEN'
        ORDER BY start_date DESC, id DESC
        LIMIT 1
    ");

    if ($open_stmt) {
        $open_stmt->execute();
        $open_course = $open_stmt->get_result()->fetch_assoc();

        if ($open_course) {
            $course_id = (int)$open_course['id'];
        }

        $open_stmt->close();
    }
}

/* =========================
   SAVE SINGLE ATTENDANCE
   FIX: process before header.php to prevent blank page
========================= */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && (
    isset($_POST['save_attendance'])
    || isset($_POST['bulk_mark_selected'])
    || isset($_POST['mark_all_present'])
)) {
    cpd_require_csrf();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_attendance'])) {
    $posted_course_id = (int)($_POST['course_id'] ?? 0);
    $app_id           = (int)($_POST['application_id'] ?? 0);
    $status           = strtoupper(trim($_POST['status'] ?? 'PENDING'));
    $date             = $_POST['attendance_date'] ?? date('Y-m-d');
    $uid              = (int)($_SESSION['user_id'] ?? 0);

    if (!valid_date($date)) {
        $date = date('Y-m-d');
    }

    if ($posted_course_id > 0) {
        $course_id = $posted_course_id;
    }

    save_attendance_status($conn, $course_id, $app_id, $date, $status, $uid);

    $_SESSION['attendance_success'] = "Attendance updated successfully.";
    redirect_attendance($course_id, $date);
}

/* =========================
   BULK MARK SELECTED
========================= */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['bulk_mark_selected'])) {
    $posted_course_id = (int)($_POST['course_id'] ?? 0);
    $date             = $_POST['attendance_date'] ?? date('Y-m-d');
    $bulk_status      = strtoupper(trim($_POST['bulk_status'] ?? ''));
    $selected         = $_POST['selected_applications'] ?? [];
    $uid              = (int)($_SESSION['user_id'] ?? 0);

    if (!valid_date($date)) {
        $date = date('Y-m-d');
    }

    if ($posted_course_id > 0) {
        $course_id = $posted_course_id;
    }

    if (!in_array($bulk_status, ['PRESENT', 'ABSENT', 'PENDING'], true)) {
        $_SESSION['attendance_error'] = "Please select a valid attendance status.";
        redirect_attendance($course_id, $date);
    }

    if (empty($selected) || !is_array($selected)) {
        $_SESSION['attendance_error'] = "Please select at least one learner.";
        redirect_attendance($course_id, $date);
    }

    $updated = 0;

    foreach ($selected as $app_id) {
        $app_id = (int)$app_id;

        if ($app_id > 0) {
            if (save_attendance_status($conn, $course_id, $app_id, $date, $bulk_status, $uid)) {
                $updated++;
            }
        }
    }

    $_SESSION['attendance_success'] = $updated . " learner(s) marked as " . $bulk_status . ".";
    redirect_attendance($course_id, $date);
}

/* =========================
   MARK ALL PRESENT
========================= */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['mark_all_present'])) {
    $posted_course_id = (int)($_POST['course_id'] ?? 0);
    $date             = $_POST['attendance_date'] ?? date('Y-m-d');
    $uid              = (int)($_SESSION['user_id'] ?? 0);

    if (!valid_date($date)) {
        $date = date('Y-m-d');
    }

    if ($posted_course_id > 0) {
        $course_id = $posted_course_id;
    }

    $approvedSql = function_exists('cpd_status_equals_sql')
        ? cpd_status_equals_sql('status', ['approved'])
        : "LOWER(TRIM(COALESCE(status,''))) = 'approved'";

    $apps_stmt = $conn->prepare("
        SELECT id
        FROM cpd_applications
        WHERE course_id = ?
          AND {$approvedSql}
    ");

    if ($apps_stmt) {
        $apps_stmt->bind_param("i", $course_id);
        $apps_stmt->execute();
        $apps = $apps_stmt->get_result();

        $updated = 0;

        while ($row = $apps->fetch_assoc()) {
            $app_id = (int)$row['id'];

            if (save_attendance_status($conn, $course_id, $app_id, $date, 'PRESENT', $uid)) {
                $updated++;
            }
        }

        $apps_stmt->close();

        $_SESSION['attendance_success'] = $updated . " learner(s) marked as PRESENT.";
    }

    redirect_attendance($course_id, $date);
}

/* =========================
   LOAD HEADER AFTER POST PROCESSING
========================= */
require_once "../header.php";

/* =========================
   GET COURSES
========================= */
$courses = $conn->query("
    SELECT id, title, status, start_date
    FROM courses
    ORDER BY 
        CASE WHEN status = 'OPEN' THEN 0 ELSE 1 END,
        start_date DESC,
        id DESC
");

/* =========================
   NO COURSE FOUND
========================= */
if ($course_id <= 0) {
    ?>
    <style>
        :root{
            --theme:#1f4e79;
            --theme-dark:#173754;
            --theme-soft:#eef4fa;
            --line:#dbe5ef;
            --bg:#f6f8fb;
            --card:#ffffff;
        }

        body{
            background:var(--bg);
        }

        .empty-card{
            max-width:700px;
            margin:60px auto;
            background:var(--card);
            border:1px solid var(--line);
            border-radius:22px;
            padding:40px;
            text-align:center;
            box-shadow:0 10px 30px rgba(15,23,42,.06);
        }

        .empty-card h3{
            color:var(--theme-dark);
            font-weight:800;
            margin-bottom:10px;
        }

        .empty-card p{
            color:#64748b;
            margin:0;
        }
    </style>

    <div class="container">
        <div class="empty-card">
            <h3>No OPEN course available</h3>
            <p>Please create or open a course first, then come back to attendance.</p>
        </div>
    </div>
    <?php
    require_once "../footer.php";
    exit;
}

/* =========================
   GET CURRENT COURSE
========================= */
$course_stmt = $conn->prepare("
    SELECT *
    FROM courses
    WHERE id = ?
    LIMIT 1
");

$course_stmt->bind_param("i", $course_id);
$course_stmt->execute();
$course = $course_stmt->get_result()->fetch_assoc();
$course_stmt->close();

if (!$course) {
    echo '<div class="container py-4"><div class="alert alert-danger">Selected course not found.</div></div>';
    require_once "../footer.php";
    exit;
}

/* =========================
   COUNTS
========================= */
$count_stmt = $conn->prepare("
    SELECT
        SUM(CASE WHEN status = 'PRESENT' THEN 1 ELSE 0 END) AS present,
        SUM(CASE WHEN status = 'ABSENT' THEN 1 ELSE 0 END) AS absent
    FROM course_attendance
    WHERE course_id = ?
      AND attendance_date = ?
");

$count_stmt->bind_param("is", $course_id, $today);
$count_stmt->execute();
$count_row = $count_stmt->get_result()->fetch_assoc();
$count_stmt->close();

$present = (int)($count_row['present'] ?? 0);
$absent  = (int)($count_row['absent'] ?? 0);

$approvedSql = function_exists('cpd_status_equals_sql')
    ? cpd_status_equals_sql('status', ['approved'])
    : "LOWER(TRIM(COALESCE(status,''))) = 'approved'";
$approvedAliasSql = function_exists('cpd_status_equals_sql')
    ? cpd_status_equals_sql('a.status', ['approved'])
    : "LOWER(TRIM(COALESCE(a.status,''))) = 'approved'";

$total_stmt = $conn->prepare("
    SELECT COUNT(*) AS c
    FROM cpd_applications
    WHERE course_id = ?
      AND {$approvedSql}
");

$total_stmt->bind_param("i", $course_id);
$total_stmt->execute();
$total = (int)($total_stmt->get_result()->fetch_assoc()['c'] ?? 0);
$total_stmt->close();

$pending = max(0, $total - ($present + $absent));

/* =========================
   TOTAL TRAINING DAYS
========================= */
$days_stmt = $conn->prepare("
    SELECT COUNT(DISTINCT attendance_date) AS c
    FROM course_attendance
    WHERE course_id = ?
");

$days_stmt->bind_param("i", $course_id);
$days_stmt->execute();
$total_days = (int)($days_stmt->get_result()->fetch_assoc()['c'] ?? 0);
$days_stmt->close();

if ($total_days <= 0) {
    $total_days = 1;
}

/* =========================
   GET APPROVED APPLICANTS
========================= */
$has_activity_table = table_exists($conn, 'course_activity');

if ($has_activity_table) {
    $students_sql = "
        SELECT 
            a.*,
            COALESCE(ca.status, 'PENDING') AS current_status,
            COALESCE(d.days_attended, 0) AS days_attended,
            CASE WHEN act.id IS NULL THEN 0 ELSE 1 END AS activity_submitted
        FROM cpd_applications a

        LEFT JOIN course_attendance ca
            ON ca.course_id = ?
            AND ca.application_id = a.id
            AND ca.attendance_date = ?

        LEFT JOIN (
            SELECT application_id, COUNT(*) AS days_attended
            FROM course_attendance
            WHERE course_id = ?
              AND status = 'PRESENT'
            GROUP BY application_id
        ) d ON d.application_id = a.id

        LEFT JOIN course_activity act
            ON act.course_id = ?
            AND act.application_id = a.id

        WHERE a.course_id = ?
          AND {$approvedAliasSql}

        ORDER BY a.full_name ASC
    ";

    $students_stmt = $conn->prepare($students_sql);
    $students_stmt->bind_param("isiii", $course_id, $today, $course_id, $course_id, $course_id);
} else {
    $students_sql = "
        SELECT 
            a.*,
            COALESCE(ca.status, 'PENDING') AS current_status,
            COALESCE(d.days_attended, 0) AS days_attended,
            0 AS activity_submitted
        FROM cpd_applications a

        LEFT JOIN course_attendance ca
            ON ca.course_id = ?
            AND ca.application_id = a.id
            AND ca.attendance_date = ?

        LEFT JOIN (
            SELECT application_id, COUNT(*) AS days_attended
            FROM course_attendance
            WHERE course_id = ?
              AND status = 'PRESENT'
            GROUP BY application_id
        ) d ON d.application_id = a.id

        WHERE a.course_id = ?
          AND {$approvedAliasSql}

        ORDER BY a.full_name ASC
    ";

    $students_stmt = $conn->prepare($students_sql);
    $students_stmt->bind_param("isii", $course_id, $today, $course_id, $course_id);
}

$students_stmt->execute();
$students = $students_stmt->get_result();
?>

<style>
:root{
    --theme:#1f4e79;
    --theme-dark:#163754;
    --theme-soft:#eef4fa;
    --theme-soft-2:#f7fbff;
    --line:#dbe5ef;
    --text:#1f2937;
    --muted:#6b7280;
    --bg:#f6f8fb;
    --card:#ffffff;
    --success-bg:#eaf8ef;
    --success-text:#166534;
    --danger-bg:#fff1f2;
    --danger-text:#b42318;
    --warning-bg:#fff7e8;
    --warning-text:#b54708;
    --shadow:0 10px 30px rgba(15,23,42,.06);
}

body{
    background:var(--bg);
    color:var(--text);
}

.page-shell{
    padding:8px 0 18px;
}

.hero-card,
.panel-card,
.table-card,
.stat-card{
    background:var(--card);
    border:1px solid var(--line);
    border-radius:22px;
    box-shadow:var(--shadow);
}

.hero-card{
    padding:26px;
    margin-bottom:22px;
    background:linear-gradient(135deg, #1f4e79, #245c8f);
    color:#fff;
    border:none;
}

.hero-title{
    font-size:28px;
    font-weight:800;
    margin-bottom:6px;
}

.hero-sub{
    font-size:14px;
    opacity:.92;
}

.course-status{
    display:inline-block;
    padding:8px 14px;
    border-radius:999px;
    background:rgba(255,255,255,.16);
    border:1px solid rgba(255,255,255,.18);
    font-size:12px;
    font-weight:700;
    margin-top:10px;
}

.panel-card,
.table-card{
    padding:22px;
}

.stat-card{
    padding:22px;
    text-align:center;
    height:100%;
}

.stat-card .label{
    font-size:13px;
    color:var(--muted);
    font-weight:700;
    text-transform:uppercase;
    letter-spacing:.04em;
}

.stat-card .num{
    font-size:32px;
    color:var(--theme);
    font-weight:800;
    line-height:1.2;
    margin-top:8px;
}

.section-title{
    font-size:18px;
    font-weight:800;
    color:var(--theme-dark);
    margin-bottom:4px;
}

.section-sub{
    font-size:13px;
    color:var(--muted);
    margin-bottom:16px;
}

.form-label{
    font-weight:700;
    color:var(--theme-dark);
    margin-bottom:8px;
}

.form-control,
.form-select{
    min-height:46px;
    border-radius:14px;
    border:1px solid var(--line);
    box-shadow:none !important;
}

.form-control:focus,
.form-select:focus{
    border-color:var(--theme);
}

.btn-theme,
.btn-soft,
.btn-success-soft,
.btn-danger-soft,
.btn-warning-soft{
    min-height:44px;
    border-radius:12px;
    font-weight:700;
    border:none;
    padding:10px 16px;
}

.btn-theme{
    background:var(--theme);
    color:#fff;
}

.btn-theme:hover{
    background:var(--theme-dark);
    color:#fff;
}

.btn-soft{
    background:var(--theme-soft);
    color:var(--theme-dark);
    border:1px solid var(--line);
}

.btn-soft:hover{
    background:#ddeaf7;
    color:var(--theme-dark);
}

.btn-success-soft{
    background:#dff5e8;
    color:#166534;
    border:1px solid #cbeed8;
}

.btn-danger-soft{
    background:#ffe4e6;
    color:#b42318;
    border:1px solid #fecdd3;
}

.btn-warning-soft{
    background:#fff7e8;
    color:#b54708;
    border:1px solid #fed7aa;
}

.bulk-toolbar{
    background:linear-gradient(180deg,#ffffff,#f8fbff);
    border:1px solid var(--line);
    border-radius:18px;
    padding:16px;
    margin-bottom:18px;
}

.table-tools{
    display:flex;
    justify-content:space-between;
    align-items:center;
    gap:12px;
    flex-wrap:wrap;
    margin-bottom:16px;
}

.table-wrap{
    overflow-x:auto;
}

.table-clean{
    width:100%;
    border-collapse:separate;
    border-spacing:0 12px;
}

.table-clean thead th{
    background:transparent;
    color:var(--muted);
    font-size:12px;
    font-weight:800;
    text-transform:uppercase;
    letter-spacing:.04em;
    border:none;
    padding:0 14px 10px;
    white-space:nowrap;
}

.table-clean tbody tr{
    background:#fff;
}

.table-clean tbody td{
    padding:16px 14px;
    vertical-align:middle;
    border-top:1px solid #edf2f7;
    border-bottom:1px solid #edf2f7;
}

.table-clean tbody td:first-child{
    border-left:1px solid #edf2f7;
    border-top-left-radius:16px;
    border-bottom-left-radius:16px;
}

.table-clean tbody td:last-child{
    border-right:1px solid #edf2f7;
    border-top-right-radius:16px;
    border-bottom-right-radius:16px;
}

.student-name{
    font-weight:800;
    color:var(--theme-dark);
}

.student-sub{
    color:var(--muted);
    font-size:13px;
    margin-top:2px;
}

.pill{
    display:inline-block;
    padding:8px 12px;
    border-radius:999px;
    font-size:12px;
    font-weight:800;
}

.pill-days{
    background:var(--theme-soft);
    color:var(--theme-dark);
}

.pill-present{
    background:var(--success-bg);
    color:var(--success-text);
}

.pill-absent{
    background:var(--danger-bg);
    color:var(--danger-text);
}

.pill-pending{
    background:var(--warning-bg);
    color:var(--warning-text);
}

.pill-activity-done{
    background:var(--success-bg);
    color:var(--success-text);
}

.pill-activity-pending{
    background:#fff4ce;
    color:#92400e;
}

.inline-form{
    display:flex;
    gap:10px;
    align-items:center;
    flex-wrap:wrap;
}

.status-select{
    min-width:150px;
}

.search-wrap{
    position:relative;
}

.search-wrap input{
    padding-left:40px;
}

.search-wrap i{
    position:absolute;
    left:14px;
    top:50%;
    transform:translateY(-50%);
    color:var(--muted);
}

.check-cell{
    width:44px;
    text-align:center;
}

.attendance-check{
    width:20px;
    height:20px;
    cursor:pointer;
    accent-color:var(--theme);
}

.alert-clean{
    border:none;
    border-radius:16px;
    padding:14px 18px;
    font-weight:700;
    margin-bottom:18px;
}

.alert-clean.success{
    background:#eaf8ef;
    color:#166534;
}

.alert-clean.error{
    background:#fff1f2;
    color:#b42318;
}

@media(max-width:991px){
    .hero-title{
        font-size:22px;
    }

    .inline-form{
        flex-direction:column;
        align-items:stretch;
    }

    .table-tools{
        align-items:stretch;
    }
}
</style>

<div class="container-fluid mt-4 page-shell">

    <?php if (!empty($_SESSION['attendance_success'])): ?>
        <div class="alert-clean success">
            <i class="fa-solid fa-circle-check me-2"></i>
            <?= h($_SESSION['attendance_success']) ?>
        </div>
        <?php unset($_SESSION['attendance_success']); ?>
    <?php endif; ?>

    <?php if (!empty($_SESSION['attendance_error'])): ?>
        <div class="alert-clean error">
            <i class="fa-solid fa-triangle-exclamation me-2"></i>
            <?= h($_SESSION['attendance_error']) ?>
        </div>
        <?php unset($_SESSION['attendance_error']); ?>
    <?php endif; ?>

    <div class="hero-card">
        <div class="row g-4 align-items-center">
            <div class="col-lg-7">
                <div class="hero-title"><?= h($course['title']) ?></div>
                <div class="hero-sub">
                    Daily attendance register for approved applicants in the selected course.
                </div>
                <div class="course-status">
                    Status: <?= h($course['status'] ?? 'N/A') ?>
                </div>
            </div>

            <div class="col-lg-5">
                <form method="GET">
                    <label class="form-label text-white">Select Course</label>
                    <select name="course_id" class="form-select" onchange="this.form.submit()">
                        <?php if ($courses): ?>
                            <?php while($c = $courses->fetch_assoc()): ?>
                                <option value="<?= (int)$c['id'] ?>" <?= $course_id === (int)$c['id'] ? 'selected' : '' ?>>
                                    <?= h($c['title']) ?><?= $c['status'] === 'OPEN' ? ' (OPEN)' : '' ?>
                                </option>
                            <?php endwhile; ?>
                        <?php endif; ?>
                    </select>
                    <input type="hidden" name="date" value="<?= h($today) ?>">
                </form>
            </div>
        </div>
    </div>

    <div class="row g-3 mb-4">
        <div class="col-md-4">
            <div class="stat-card">
                <div class="label">Present Today</div>
                <div class="num"><?= $present ?></div>
            </div>
        </div>

        <div class="col-md-4">
            <div class="stat-card">
                <div class="label">Absent Today</div>
                <div class="num"><?= $absent ?></div>
            </div>
        </div>

        <div class="col-md-4">
            <div class="stat-card">
                <div class="label">Pending Today</div>
                <div class="num"><?= $pending ?></div>
            </div>
        </div>
    </div>

    <div class="panel-card mb-4">
        <div class="row g-3 align-items-end">
            <div class="col-md-3">
                <div class="section-title">Training Date</div>
                <div class="section-sub">Filter attendance by date</div>
                <form method="GET">
                    <input type="hidden" name="course_id" value="<?= (int)$course_id ?>">
                    <input type="date" name="date" value="<?= h($today) ?>" class="form-control" onchange="this.form.submit()">
                </form>
            </div>

            <div class="col-md-5">
                <div class="section-title">Search Applicants</div>
                <div class="section-sub">Search by name or company</div>
                <div class="search-wrap">
                    <i class="fa-solid fa-magnifying-glass"></i>
                    <input type="text" id="tableSearch" class="form-control" placeholder="Search by learner name or company">
                </div>
            </div>

            <div class="col-md-4 text-md-end">
                <div class="section-title">Quick Actions</div>
                <div class="section-sub">Attendance tools</div>

                <div class="d-flex gap-2 justify-content-md-end flex-wrap">
                    <form method="POST" class="m-0">
                        <?= cpd_csrf_input() ?>
                        <input type="hidden" name="course_id" value="<?= (int)$course_id ?>">
                        <input type="hidden" name="attendance_date" value="<?= h($today) ?>">
                        <button type="submit" name="mark_all_present" class="btn btn-success-soft"
                                onclick="return confirm('Mark all approved applicants as PRESENT for this date?');">
                            <i class="fa-solid fa-users-check me-1"></i>
                            Mark All Present
                        </button>
                    </form>

                    <a href="export_attendance.php?course_id=<?= (int)$course_id ?>" class="btn btn-soft">
                        <i class="fa-solid fa-file-excel me-1"></i>
                        Download Excel
                    </a>
                </div>
            </div>
        </div>
    </div>

    <div class="table-card">
        <div class="table-tools">
            <div>
                <div class="section-title">Approved Applicants</div>
                <div class="section-sub">Mark present, absent, or leave pending</div>
            </div>
        </div>

        <form id="bulkAttendanceForm" method="POST">
            <?= cpd_csrf_input() ?>
            <input type="hidden" name="course_id" value="<?= (int)$course_id ?>">
            <input type="hidden" name="attendance_date" value="<?= h($today) ?>">
        </form>

        <div class="bulk-toolbar">
            <div class="row g-3 align-items-end">
                <div class="col-md-4">
                    <label class="form-label">Bulk Status</label>
                    <select name="bulk_status" class="form-select" form="bulkAttendanceForm">
                        <option value="">Select status</option>
                        <option value="PRESENT">Present</option>
                        <option value="ABSENT">Absent</option>
                        <option value="PENDING">Pending / Clear</option>
                    </select>
                </div>

                <div class="col-md-4">
                    <button type="button"
                            id="bulkMarkSelectedBtn"
                            class="btn btn-theme w-100">
                        <i class="fa-solid fa-check-double me-1"></i>
                        Mark Selected Learners
                    </button>
                </div>

                <div class="col-md-4">
                    <button type="button" class="btn btn-soft w-100" id="clearSelectionBtn">
                        <i class="fa-solid fa-xmark me-1"></i>
                        Clear Selection
                    </button>
                </div>
            </div>
        </div>

        <div class="table-wrap">
            <table class="table-clean" id="studentsTable" data-no-paginate="1">
                <thead>
                    <tr>
                        <th class="check-cell">
                            <input type="checkbox" id="selectAllRows" class="attendance-check">
                        </th>
                        <th>#</th>
                        <th>Applicant</th>
                        <th>Days Attended</th>
                        <th>Attendance %</th>
                        <th>Activity</th>
                        <th>Certificate</th>
                        <th>Attendance</th>
                    </tr>
                </thead>

                <tbody>
                <?php
                $i = 1;
                while ($s = $students->fetch_assoc()):
                    $app_id = (int)$s['id'];
                    $current_status = strtoupper($s['current_status'] ?? 'PENDING');
                    $days_attended = (int)($s['days_attended'] ?? 0);
                    $percentage = $total_days > 0 ? round(($days_attended / $total_days) * 100) : 0;
                    $activity_submitted = (int)($s['activity_submitted'] ?? 0) === 1;
                    $eligible = ($percentage >= 80 && $activity_submitted);

                    $status_class = 'pill-pending';

                    if ($current_status === 'PRESENT') {
                        $status_class = 'pill-present';
                    }

                    if ($current_status === 'ABSENT') {
                        $status_class = 'pill-absent';
                    }
                ?>
                    <tr>
                        <td class="check-cell">
                            <input
                                type="checkbox"
                                name="selected_applications[]"
                                value="<?= $app_id ?>"
                                class="attendance-check row-check"
                                form="bulkAttendanceForm"
                            >
                        </td>

                        <td><?= $i++ ?></td>

                        <td>
                            <div class="student-name"><?= h($s['full_name']) ?></div>
                            <div class="student-sub"><?= h($s['company_name']) ?></div>
                        </td>

                        <td>
                            <span class="pill pill-days"><?= $days_attended ?> / <?= $total_days ?></span>
                        </td>

                        <td>
                            <strong><?= $percentage ?>%</strong>
                        </td>

                        <td>
                            <?php if ($activity_submitted): ?>
                                <span class="pill pill-activity-done">Submitted</span>
                            <?php else: ?>
                                <span class="pill pill-activity-pending">Pending</span>
                            <?php endif; ?>
                        </td>

                        <td>
                            <?php if ($eligible): ?>
                            <a href="../training_cerficate.php?id=<?= $app_id ?>" class="btn btn-success-soft btn-sm">
                                    <i class="fa-solid fa-download me-1"></i>
                                    Download
                                </a>
                            <?php else: ?>
                                <span class="pill pill-pending">Not Eligible</span>
                            <?php endif; ?>
                        </td>

                        <td>
                            <form method="POST" class="inline-form m-0">
                                <?= cpd_csrf_input() ?>
                                <input type="hidden" name="course_id" value="<?= (int)$course_id ?>">
                                <input type="hidden" name="application_id" value="<?= $app_id ?>">
                                <input type="hidden" name="attendance_date" value="<?= h($today) ?>">

                                <span class="pill <?= $status_class ?>"><?= h($current_status) ?></span>

                                <select name="status" class="form-select status-select">
                                    <option value="PENDING" <?= $current_status === 'PENDING' ? 'selected' : '' ?>>Pending</option>
                                    <option value="PRESENT" <?= $current_status === 'PRESENT' ? 'selected' : '' ?>>Present</option>
                                    <option value="ABSENT" <?= $current_status === 'ABSENT' ? 'selected' : '' ?>>Absent</option>
                                </select>

                                <button type="submit" name="save_attendance" class="btn btn-theme">
                                    Save
                                </button>
                            </form>
                        </td>
                    </tr>
                <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<link rel="stylesheet" href="https://cdn.datatables.net/1.13.7/css/dataTables.bootstrap5.min.css">
<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<script src="https://cdn.datatables.net/1.13.7/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.7/js/dataTables.bootstrap5.min.js"></script>

<script>
$(document).ready(function(){
    var $tableEl = $('#studentsTable');
    if (!$tableEl.length) {
        return;
    }

    var headCols = $tableEl.find('thead tr:first th').length;
    var $bodyRows = $tableEl.find('tbody tr');
    var canInit = headCols > 0;
    $bodyRows.each(function () {
        var $tr = $(this);
        if ($tr.find('[colspan]').length || $tr.children('td, th').length !== headCols) {
            canInit = false;
            return false;
        }
    });

    var table = null;
    if (canInit) {
        table = $tableEl.DataTable({
            pageLength: 6,
            lengthMenu: [6, 12, 24, 50],
            ordering: false,
            autoWidth: false,
            language: {
                emptyTable: 'No approved applicants found for this course.',
                zeroRecords: 'No matching applicants found.'
            }
        });
    }

    function checkedLearnerIds() {
        // DataTables keeps non-visible rows out of the live DOM; use its API.
        if (table) {
            var ids = [];
            table.$('.row-check:checked').each(function () {
                var v = parseInt(this.value, 10);
                if (v > 0) {
                    ids.push(v);
                }
            });
            return ids;
        }
        return $('.row-check:checked').map(function () {
            return parseInt(this.value, 10);
        }).get().filter(function (v) { return v > 0; });
    }

    $('#tableSearch').on('keyup', function(){
        if (table) {
            table.search(this.value).draw();
        }
    });

    $('#selectAllRows').on('change', function(){
        var checked = this.checked;
        if (table) {
            table.rows({ search: 'applied' }).nodes().to$().find('.row-check').prop('checked', checked);
            return;
        }
        $('.row-check').prop('checked', checked);
    });

    $('#clearSelectionBtn').on('click', function(){
        if (table) {
            table.$('.row-check').prop('checked', false);
        } else {
            $('.row-check').prop('checked', false);
        }
        $('#selectAllRows').prop('checked', false);
    });

    $tableEl.on('change', '.row-check', function(){
        if (!table) {
            return;
        }
        var totalVisible = table.rows({ search: 'applied' }).nodes().to$().find('.row-check').length;
        var checkedVisible = table.rows({ search: 'applied' }).nodes().to$().find('.row-check:checked').length;
        $('#selectAllRows').prop('checked', totalVisible > 0 && totalVisible === checkedVisible);
    });

    $('#bulkMarkSelectedBtn').on('click', function () {
        var status = String($('select[name="bulk_status"]').val() || '').trim();
        if (!status) {
            window.alert('Please select a bulk attendance status first.');
            return;
        }

        var ids = checkedLearnerIds();
        if (!ids.length) {
            window.alert('Please select at least one learner.');
            return;
        }

        if (!window.confirm('Apply ' + status + ' to ' + ids.length + ' selected learner(s)?')) {
            return;
        }

        var $form = $('#bulkAttendanceForm');
        $form.find('input[name="selected_applications[]"]').remove();
        $form.find('input[name="bulk_status"][type="hidden"]').remove();
        $form.find('input[name="bulk_mark_selected"]').remove();

        ids.forEach(function (id) {
            $('<input>', {
                type: 'hidden',
                name: 'selected_applications[]',
                value: String(id)
            }).appendTo($form);
        });

        $('<input>', { type: 'hidden', name: 'bulk_status', value: status }).appendTo($form);
        $('<input>', { type: 'hidden', name: 'bulk_mark_selected', value: '1' }).appendTo($form);
        $form.trigger('submit');
    });
});
</script>

<?php
$students_stmt->close();
require_once "../footer.php";
?>