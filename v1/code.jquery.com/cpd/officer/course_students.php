<?php
require_once "../auth.php";
require_role(['SUPPERADMIN', 'OFFICER']);
require_once "../config.php";
require_once "../header.php";

if (!($conn instanceof mysqli)) {
    http_response_code(503);
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
            <a class="btn btn-primary" href="/cpd/officer/course_students.php">Try again</a>
            <a class="btn btn-outline-secondary ms-2" href="/index.php">Return home</a>
        </div>
    </section>
    <?php
    require_once "../footer.php";
    exit;
}

function h($value){
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}

/* =========================
   GET DATE
========================= */
$today = $_GET['date'] ?? ($_POST['attendance_date'] ?? date('Y-m-d'));

/* =========================
   GET COURSE ID
   - GET
   - POST
   - DEFAULT TO LATEST OPEN COURSE
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
    $open_stmt->execute();
    $open_course = $open_stmt->get_result()->fetch_assoc();

    if ($open_course) {
        $course_id = (int)$open_course['id'];
    }
}

/* =========================
   GET COURSES
   OPEN FIRST, THEN OTHERS
========================= */
$courses = $conn->query("
    SELECT id, title, status, start_date
    FROM courses
    ORDER BY 
        CASE WHEN status='OPEN' THEN 0 ELSE 1 END,
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
        body{background:var(--bg);}
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

if (!$course) {
    echo '<div class="container py-4"><div class="alert alert-danger">Selected course not found.</div></div>';
    require_once "../footer.php";
    exit;
}

/* =========================
   SAVE / UPDATE ATTENDANCE
========================= */
if (isset($_POST['save_attendance'])) {
    $posted_course_id = (int)($_POST['course_id'] ?? 0);
    $app_id           = (int)($_POST['application_id'] ?? 0);
    $status           = strtoupper(trim($_POST['status'] ?? 'PENDING'));
    $date             = $_POST['attendance_date'] ?? date('Y-m-d');
    $uid              = (int)($_SESSION['user_id'] ?? 0);

    if ($posted_course_id > 0) {
        $course_id = $posted_course_id;
    }

    if ($course_id > 0 && $app_id > 0 && in_array($status, ['PRESENT', 'ABSENT', 'PENDING'], true)) {

        $check_stmt = $conn->prepare("
            SELECT id
            FROM course_attendance
            WHERE course_id = ? AND application_id = ? AND attendance_date = ?
            LIMIT 1
        ");
        $check_stmt->bind_param("iis", $course_id, $app_id, $date);
        $check_stmt->execute();
        $existing = $check_stmt->get_result()->fetch_assoc();

        if ($status === 'PENDING') {
            $delete_stmt = $conn->prepare("
                DELETE FROM course_attendance
                WHERE course_id = ? AND application_id = ? AND attendance_date = ?
            ");
            $delete_stmt->bind_param("iis", $course_id, $app_id, $date);
            $delete_stmt->execute();
        } else {
            if ($existing) {
                $update_stmt = $conn->prepare("
                    UPDATE course_attendance
                    SET status = ?, marked_by = ?
                    WHERE course_id = ? AND application_id = ? AND attendance_date = ?
                ");
                $update_stmt->bind_param("siiis", $status, $uid, $course_id, $app_id, $date);
                $update_stmt->execute();
            } else {
                $insert_stmt = $conn->prepare("
                    INSERT INTO course_attendance
                    (course_id, application_id, attendance_date, status, marked_by)
                    VALUES (?, ?, ?, ?, ?)
                ");
                $insert_stmt->bind_param("iissi", $course_id, $app_id, $date, $status, $uid);
                $insert_stmt->execute();
            }
        }
    }

    header("Location: course_students.php?course_id=" . $course_id . "&date=" . urlencode($date));
    exit;
}

/* =========================
   MARK ALL PRESENT
========================= */
if (isset($_POST['mark_all_present'])) {
    $date = $_POST['attendance_date'] ?? date('Y-m-d');
    $uid  = (int)($_SESSION['user_id'] ?? 0);

    $approvedSql = function_exists('cpd_status_equals_sql')
        ? cpd_status_equals_sql('status', ['approved'])
        : "LOWER(TRIM(COALESCE(status,''))) = 'approved'";

    $apps_stmt = $conn->prepare("
        SELECT id
        FROM cpd_applications
        WHERE course_id = ? AND {$approvedSql}
    ");
    $apps_stmt->bind_param("i", $course_id);
    $apps_stmt->execute();
    $apps = $apps_stmt->get_result();

    while ($row = $apps->fetch_assoc()) {
        $app_id = (int)$row['id'];

        $check_stmt = $conn->prepare("
            SELECT id
            FROM course_attendance
            WHERE course_id = ? AND application_id = ? AND attendance_date = ?
            LIMIT 1
        ");
        $check_stmt->bind_param("iis", $course_id, $app_id, $date);
        $check_stmt->execute();
        $existing = $check_stmt->get_result()->fetch_assoc();

        if ($existing) {
            $status = 'PRESENT';
            $update_stmt = $conn->prepare("
                UPDATE course_attendance
                SET status = ?, marked_by = ?
                WHERE course_id = ? AND application_id = ? AND attendance_date = ?
            ");
            $update_stmt->bind_param("siiis", $status, $uid, $course_id, $app_id, $date);
            $update_stmt->execute();
        } else {
            $status = 'PRESENT';
            $insert_stmt = $conn->prepare("
                INSERT INTO course_attendance
                (course_id, application_id, attendance_date, status, marked_by)
                VALUES (?, ?, ?, ?, ?)
            ");
            $insert_stmt->bind_param("iissi", $course_id, $app_id, $date, $status, $uid);
            $insert_stmt->execute();
        }
    }

    header("Location: course_students.php?course_id=" . $course_id . "&date=" . urlencode($date));
    exit;
}

/* =========================
   GET APPROVED APPLICANTS
========================= */
$approvedSql = function_exists('cpd_status_equals_sql')
    ? cpd_status_equals_sql('status', ['approved'])
    : "LOWER(TRIM(COALESCE(status,''))) = 'approved'";

$students_stmt = $conn->prepare("
    SELECT *
    FROM cpd_applications
    WHERE course_id = ?
      AND {$approvedSql}
    ORDER BY full_name ASC
");
$students_stmt->bind_param("i", $course_id);
$students_stmt->execute();
$students = $students_stmt->get_result();

/* =========================
   COUNTS
========================= */
$count_stmt = $conn->prepare("
    SELECT
        SUM(CASE WHEN status='PRESENT' THEN 1 ELSE 0 END) AS present,
        SUM(CASE WHEN status='ABSENT' THEN 1 ELSE 0 END) AS absent
    FROM course_attendance
    WHERE course_id = ? AND attendance_date = ?
");
$count_stmt->bind_param("is", $course_id, $today);
$count_stmt->execute();
$count_row = $count_stmt->get_result()->fetch_assoc();

$present = (int)($count_row['present'] ?? 0);
$absent  = (int)($count_row['absent'] ?? 0);

$total_stmt = $conn->prepare("
    SELECT COUNT(*) AS c
    FROM cpd_applications
    WHERE course_id = ?
      AND {$approvedSql}
");
$total_stmt->bind_param("i", $course_id);
$total_stmt->execute();
$total = (int)($total_stmt->get_result()->fetch_assoc()['c'] ?? 0);

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

if ($total_days <= 0) {
    $total_days = 1;
}
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
.btn-success-soft{
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

@media(max-width:991px){
    .hero-title{font-size:22px;}
    .inline-form{flex-direction:column; align-items:stretch;}
    .table-tools{align-items:stretch;}
}
</style>

<div class="container-fluid mt-4 page-shell">

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
                        <?php while($c = $courses->fetch_assoc()): ?>
                            <option value="<?= (int)$c['id'] ?>" <?= $course_id == (int)$c['id'] ? 'selected' : '' ?>>
                                <?= h($c['title']) ?><?= $c['status'] === 'OPEN' ? ' (OPEN)' : '' ?>
                            </option>
                        <?php endwhile; ?>
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
                    <input type="hidden" name="course_id" value="<?= $course_id ?>">
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
                        <input type="hidden" name="course_id" value="<?= $course_id ?>">
                        <input type="hidden" name="attendance_date" value="<?= h($today) ?>">
                        <button type="submit" name="mark_all_present" class="btn btn-success-soft"
                                onclick="return confirm('Mark all approved applicants as PRESENT for this date?');">
                            Mark All Present
                        </button>
                    </form>

                    <a href="export_attendance.php?course_id=<?= $course_id ?>" class="btn btn-soft">
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

        <div class="table-wrap">
            <table class="table-clean" id="studentsTable" data-no-paginate="1">
                <thead>
                    <tr>
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

                    $att_stmt = $conn->prepare("
                        SELECT status
                        FROM course_attendance
                        WHERE course_id = ? AND application_id = ? AND attendance_date = ?
                        LIMIT 1
                    ");
                    $att_stmt->bind_param("iis", $course_id, $app_id, $today);
                    $att_stmt->execute();
                    $att = $att_stmt->get_result()->fetch_assoc();

                    $current_status = $att['status'] ?? 'PENDING';

                    $days_attended_stmt = $conn->prepare("
                        SELECT COUNT(*) AS c
                        FROM course_attendance
                        WHERE course_id = ? AND application_id = ? AND status = 'PRESENT'
                    ");
                    $days_attended_stmt->bind_param("ii", $course_id, $app_id);
                    $days_attended_stmt->execute();
                    $days_attended = (int)($days_attended_stmt->get_result()->fetch_assoc()['c'] ?? 0);

                    $percentage = $total_days > 0 ? round(($days_attended / $total_days) * 100) : 0;

                    $activity_stmt = $conn->prepare("
                        SELECT id
                        FROM course_activity
                        WHERE course_id = ? AND application_id = ?
                        LIMIT 1
                    ");
                    $activity_stmt->bind_param("ii", $course_id, $app_id);
                    $activity_stmt->execute();
                    $activity_submitted = $activity_stmt->get_result()->num_rows > 0;

                    $eligible = ($percentage >= 80 && $activity_submitted);

                    $status_class = 'pill-pending';
                    if ($current_status === 'PRESENT') $status_class = 'pill-present';
                    if ($current_status === 'ABSENT') $status_class = 'pill-absent';
                ?>
                    <tr>
                        <td><?= $i++ ?></td>

                        <td>
                            <div class="student-name"><?= h($s['full_name']) ?></div>
                            <div class="student-sub"><?= h($s['company_name']) ?></div>
                        </td>

                        <td>
                            <span class="pill pill-days"><?= $days_attended ?> / <?= $total_days ?></span>
                        </td>

                        <td><strong><?= $percentage ?>%</strong></td>

                        <td>
                            <?php if ($activity_submitted): ?>
                                <span class="pill pill-activity-done">Submitted</span>
                            <?php else: ?>
                                <span class="pill pill-activity-pending">Pending</span>
                            <?php endif; ?>
                        </td>

                        <td>
                            <?php if ($eligible): ?>
                                <a href="/cpd/admin/generate_certificate.php?application_id=<?= $app_id ?>&course_id=<?= $course_id ?>" class="btn btn-success-soft btn-sm">
                                    Download
                                </a>
                            <?php else: ?>
                                <span class="pill pill-pending">Not Eligible</span>
                            <?php endif; ?>
                        </td>

                        <td>
                            <form method="POST" class="inline-form m-0">
                                <input type="hidden" name="course_id" value="<?= $course_id ?>">
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
    var table = $('#studentsTable').DataTable({
        pageLength: 6,
        lengthMenu: [6, 12, 24, 50],
        ordering: false,
        autoWidth: false,
        language: {
            emptyTable: 'No approved applicants found for this course.',
            zeroRecords: 'No matching applicants found.'
        }
    });

    $('#tableSearch').on('keyup', function(){
        table.search(this.value).draw();
    });
});
</script>

<?php require_once "../footer.php"; ?>