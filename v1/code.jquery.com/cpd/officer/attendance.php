<?php
require_once "../auth.php";
require_role(['SUPPERADMIN', 'OFFICER']);
require_once "../config.php";
require_once "../header.php";

function h($v){
    return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8');
}

/* =========================
   COURSE / DATE
========================= */
$course_id = 0;

if (isset($_GET['course_id']) && (int)$_GET['course_id'] > 0) {
    $course_id = (int)$_GET['course_id'];
} elseif (isset($_POST['course_id']) && (int)$_POST['course_id'] > 0) {
    $course_id = (int)$_POST['course_id'];
}

$today = $_GET['date'] ?? $_POST['attendance_date'] ?? date('Y-m-d');

if ($course_id <= 0) {
    echo '<div class="container py-4"><div class="alert alert-danger">Invalid course selected.</div></div>';
    require_once "../footer.php";
    exit;
}

/* =========================
   GET COURSE
========================= */
$course_stmt = $conn->prepare("SELECT * FROM courses WHERE id=? LIMIT 1");
$course_stmt->bind_param("i", $course_id);
$course_stmt->execute();
$course = $course_stmt->get_result()->fetch_assoc();

if (!$course) {
    echo '<div class="container py-4"><div class="alert alert-danger">Course not found.</div></div>';
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

        $check = $conn->prepare("
            SELECT id
            FROM course_attendance
            WHERE course_id=? AND application_id=? AND attendance_date=?
            LIMIT 1
        ");
        $check->bind_param("iis", $course_id, $app_id, $date);
        $check->execute();
        $existing = $check->get_result()->fetch_assoc();

        if ($status === 'PENDING') {
            $delete = $conn->prepare("
                DELETE FROM course_attendance
                WHERE course_id=? AND application_id=? AND attendance_date=?
            ");
            $delete->bind_param("iis", $course_id, $app_id, $date);
            $delete->execute();
        } else {
            if ($existing) {
                $update = $conn->prepare("
                    UPDATE course_attendance
                    SET status=?, marked_by=?
                    WHERE course_id=? AND application_id=? AND attendance_date=?
                ");
                $update->bind_param("siiis", $status, $uid, $course_id, $app_id, $date);
                $update->execute();
            } else {
                $insert = $conn->prepare("
                    INSERT INTO course_attendance
                    (course_id, application_id, attendance_date, status, marked_by)
                    VALUES (?, ?, ?, ?, ?)
                ");
                $insert->bind_param("iissi", $course_id, $app_id, $date, $status, $uid);
                $insert->execute();
            }
        }
    }

    header("Location: attendance.php?course_id=".$course_id."&date=".urlencode($date));
    exit;
}

/* =========================
   BULK MARK ALL PRESENT
========================= */
if (isset($_POST['mark_all_present'])) {
    $date = $_POST['attendance_date'] ?? date('Y-m-d');
    $uid  = (int)($_SESSION['user_id'] ?? 0);

    $apps_stmt = $conn->prepare("SELECT id FROM cpd_applications WHERE course_id=?");
    $apps_stmt->bind_param("i", $course_id);
    $apps_stmt->execute();
    $apps = $apps_stmt->get_result();

    while ($row = $apps->fetch_assoc()) {
        $app_id = (int)$row['id'];

        $check = $conn->prepare("
            SELECT id
            FROM course_attendance
            WHERE course_id=? AND application_id=? AND attendance_date=?
            LIMIT 1
        ");
        $check->bind_param("iis", $course_id, $app_id, $date);
        $check->execute();
        $existing = $check->get_result()->fetch_assoc();

        if ($existing) {
            $update = $conn->prepare("
                UPDATE course_attendance
                SET status='PRESENT', marked_by=?
                WHERE course_id=? AND application_id=? AND attendance_date=?
            ");
            $update->bind_param("iiis", $uid, $course_id, $app_id, $date);
            $update->execute();
        } else {
            $status = 'PRESENT';
            $insert = $conn->prepare("
                INSERT INTO course_attendance
                (course_id, application_id, attendance_date, status, marked_by)
                VALUES (?, ?, ?, ?, ?)
            ");
            $insert->bind_param("iissi", $course_id, $app_id, $date, $status, $uid);
            $insert->execute();
        }
    }

    header("Location: attendance.php?course_id=".$course_id."&date=".urlencode($date));
    exit;
}

/* =========================
   STUDENTS
========================= */
$students_stmt = $conn->prepare("
    SELECT *
    FROM cpd_applications
    WHERE course_id=?
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
    WHERE course_id=? AND attendance_date=?
");
$count_stmt->bind_param("is", $course_id, $today);
$count_stmt->execute();
$count_row = $count_stmt->get_result()->fetch_assoc();

$present = (int)($count_row['present'] ?? 0);
$absent  = (int)($count_row['absent'] ?? 0);

$total_stmt = $conn->prepare("SELECT COUNT(*) AS c FROM cpd_applications WHERE course_id=?");
$total_stmt->bind_param("i", $course_id);
$total_stmt->execute();
$total = (int)($total_stmt->get_result()->fetch_assoc()['c'] ?? 0);

$pending = max(0, $total - ($present + $absent));
?>

<style>
:root{
    --theme:#1f4e79;
    --theme-dark:#163754;
    --theme-soft:#eef4fa;
    --line:#d9e3ee;
    --text:#1f2937;
    --muted:#6b7280;
    --white:#ffffff;
}
body{
    background:#f5f7fb;
    color:var(--text);
}
.att-wrap{
    max-width:1400px;
    margin:0 auto;
}
.top-title{
    font-weight:700;
    color:var(--theme-dark);
}
.top-title span{
    color:var(--theme);
}
.clean-card{
    background:var(--white);
    border:1px solid var(--line);
    border-radius:18px;
    box-shadow:0 10px 24px rgba(20,50,90,.05);
}
.stat-card{
    padding:22px;
    text-align:center;
}
.stat-card .label{
    color:var(--muted);
    font-size:14px;
}
.stat-card .num{
    font-size:30px;
    font-weight:700;
    color:var(--theme);
}
.section-pad{
    padding:20px;
}
.form-label{
    font-weight:600;
    color:var(--theme-dark);
}
.form-control,.form-select{
    min-height:44px;
    border-radius:12px;
    border:1px solid var(--line);
    box-shadow:none !important;
}
.form-control:focus,.form-select:focus{
    border-color:var(--theme);
}
.btn-theme{
    background:var(--theme);
    border:1px solid var(--theme);
    color:#fff;
    border-radius:12px;
    font-weight:600;
    min-height:44px;
}
.btn-theme:hover{
    background:var(--theme-dark);
    border-color:var(--theme-dark);
    color:#fff;
}
.btn-soft{
    background:var(--theme-soft);
    color:var(--theme-dark);
    border:1px solid #d8e4f0;
    border-radius:12px;
    font-weight:600;
    min-height:44px;
}
.table thead th{
    background:var(--theme);
    color:#fff;
    border:none;
    white-space:nowrap;
}
.table tbody td{
    vertical-align:middle;
    border-color:#edf2f7;
}
.badge-days{
    background:var(--theme-soft);
    color:var(--theme-dark);
    border:1px solid #d8e4f0;
    border-radius:999px;
    padding:8px 12px;
    font-weight:600;
}
.status-pill{
    padding:7px 12px;
    border-radius:999px;
    font-weight:700;
    font-size:12px;
    display:inline-block;
}
.status-present{
    background:#e9f7ef;
    color:#18794e;
}
.status-absent{
    background:#fff1f2;
    color:#be123c;
}
.status-pending{
    background:#f4f6f8;
    color:#475467;
}
.row-form{
    display:flex;
    gap:10px;
    align-items:center;
    flex-wrap:wrap;
}
.select-status{
    min-width:150px;
}
.table-wrap{
    overflow-x:auto;
}
</style>

<div class="container-fluid mt-4 att-wrap">

    <div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-4">
        <h4 class="top-title mb-0">
            Attendance Register <span><?= h($course['title']) ?></span>
        </h4>

        <div class="d-flex gap-2 flex-wrap">
            <a href="export_attendance.php?course_id=<?= $course_id ?>" class="btn btn-soft">
                Download Excel
            </a>

            <form method="POST" class="m-0">
                <input type="hidden" name="course_id" value="<?= $course_id ?>">
                <input type="hidden" name="attendance_date" value="<?= h($today) ?>">
                <button type="submit" name="mark_all_present" class="btn btn-theme"
                        onclick="return confirm('Mark all trainees as Present for this day?');">
                    Mark All Present
                </button>
            </form>
        </div>
    </div>

    <div class="row g-3 mb-4">
        <div class="col-md-4">
            <div class="clean-card stat-card">
                <div class="label">Attended</div>
                <div class="num"><?= $present ?></div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="clean-card stat-card">
                <div class="label">Not Attended</div>
                <div class="num"><?= $absent ?></div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="clean-card stat-card">
                <div class="label">Pending</div>
                <div class="num"><?= $pending ?></div>
            </div>
        </div>
    </div>

    <div class="clean-card section-pad mb-4">
        <form method="GET">
            <input type="hidden" name="course_id" value="<?= $course_id ?>">

            <div class="row g-3 align-items-end">
                <div class="col-md-3">
                    <label class="form-label">Select Training Day</label>
                    <input type="date" name="date" value="<?= h($today) ?>" class="form-control">
                </div>

                <div class="col-md-5">
                    <label class="form-label">Search by Name</label>
                    <input type="text" id="searchInput" class="form-control" placeholder="Search trainee">
                </div>

                <div class="col-md-2">
                    <button class="btn btn-theme w-100" type="submit">View</button>
                </div>
            </div>
        </form>
    </div>

    <div class="clean-card section-pad">
        <div class="table-wrap">
            <table class="table align-middle" id="studentsTable">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Name</th>
                        <th>Email</th>
                        <th>Phone</th>
                        <th>Company</th>
                        <th>Position</th>
                        <th>Days Attended</th>
                        <th>Current Status</th>
                        <th width="260">Update Attendance</th>
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
                        WHERE course_id=? AND application_id=? AND attendance_date=?
                        LIMIT 1
                    ");
                    $att_stmt->bind_param("iis", $course_id, $app_id, $today);
                    $att_stmt->execute();
                    $att = $att_stmt->get_result()->fetch_assoc();

                    $current_status = $att['status'] ?? 'PENDING';

                    $days_stmt = $conn->prepare("
                        SELECT COUNT(*) AS c
                        FROM course_attendance
                        WHERE course_id=? AND application_id=? AND status='PRESENT'
                    ");
                    $days_stmt->bind_param("ii", $course_id, $app_id);
                    $days_stmt->execute();
                    $days_attended = (int)($days_stmt->get_result()->fetch_assoc()['c'] ?? 0);

                    $pill_class = 'status-pending';
                    if ($current_status === 'PRESENT') $pill_class = 'status-present';
                    if ($current_status === 'ABSENT')  $pill_class = 'status-absent';
                ?>
                    <tr>
                        <td><?= $i++ ?></td>
                        <td class="name"><?= h($s['full_name']) ?></td>
                        <td><?= h($s['email']) ?></td>
                        <td><?= h($s['phone']) ?></td>
                        <td><?= h($s['company_name']) ?></td>
                        <td><?= h($s['position']) ?></td>
                        <td><span class="badge-days"><?= $days_attended ?> Day(s)</span></td>
                        <td>
                            <span class="status-pill <?= $pill_class ?>">
                                <?= h($current_status) ?>
                            </span>
                        </td>
                        <td>
                            <form method="POST" class="row-form m-0">
                                <input type="hidden" name="course_id" value="<?= $course_id ?>">
                                <input type="hidden" name="application_id" value="<?= $app_id ?>">
                                <input type="hidden" name="attendance_date" value="<?= h($today) ?>">

                                <select name="status" class="form-select select-status">
                                    <option value="PENDING" <?= $current_status==='PENDING' ? 'selected' : '' ?>>Pending</option>
                                    <option value="PRESENT" <?= $current_status==='PRESENT' ? 'selected' : '' ?>>Present</option>
                                    <option value="ABSENT" <?= $current_status==='ABSENT' ? 'selected' : '' ?>>Absent</option>
                                </select>

                                <button type="submit" name="save_attendance" class="btn btn-theme">
                                    Save
                                </button>
                            </form>
                        </td>
                    </tr>
                <?php endwhile; ?>

                <?php if ($total === 0): ?>
                    <tr>
                        <td colspan="9" class="text-center text-muted py-4">No trainees found for this course.</td>
                    </tr>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<script>
document.getElementById('searchInput').addEventListener('input', function () {
    const filter = this.value.toLowerCase().trim();
    document.querySelectorAll('#studentsTable tbody tr').forEach(function (row) {
        const haystack = row.textContent.toLowerCase().replace(/\s+/g, ' ');
        row.style.display = !filter || haystack.includes(filter) ? '' : 'none';
    });
});
</script>

<?php require_once "../footer.php"; ?>