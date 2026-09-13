<?php
require_once "../auth.php";
require_role('CONTRACTOR');
require_once "../config.php";

$user_id    = (int)($_SESSION['user_id'] ?? 0);
$user_email = $_SESSION['email'] ?? '';

/* TOTAL CPD POINTS */

$total = $conn->prepare("
SELECT COALESCE(SUM(points),0) s
FROM cpd_points_ledger
WHERE user_id=?
");

$total->bind_param("i",$user_id);
$total->execute();
$total_points = (float)($total->get_result()->fetch_assoc()['s'] ?? 0);


/* PENDING APPLICATIONS */

$pending = $conn->prepare("
SELECT COUNT(*) c
FROM cpd_applications
WHERE email=? AND status='Pending'
");

$pending->bind_param("s",$user_email);
$pending->execute();
$pending_apps = (int)($pending->get_result()->fetch_assoc()['c'] ?? 0);


/* CPD TARGET */

$target_points = 12;
$progress_pct  = min(100,round(($total_points/$target_points)*100,1));


/* FETCH APPROVED COURSES */

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

$courses->bind_param("s",$user_email);
$courses->execute();

$result = $courses->get_result();

require_once "../cheader.php";
?>

<style>

.course-card{
border-radius:14px;
background:#ffffff;
transition:.25s;
border:1px solid #e5e7eb;
}

.course-card:hover{
transform:translateY(-4px);
box-shadow:0 10px 25px rgba(0,0,0,.15);
}

.progress{
height:8px;
background:#e5e7eb;
border-radius:10px;
}

.progress-bar{
background:#000066;
}

.badge{
font-size:.75rem;
padding:6px 10px;
}

</style>


<div class="container-fluid">


<!-- HEADER -->

<div class="d-flex justify-content-between align-items-center mb-4">

<div>
<p class="eca-kicker">CPD portal</p>
<h4>Contractor dashboard</h4>
<div style="color:#667085">
Welcome, <?= e($_SESSION['full_name']) ?>
</div>
</div>

<div class="d-flex gap-2">

<a class="btn btn-primary" href="courses.php">Browse courses</a>
<a class="btn btn-soft" href="applications.php">My applications</a>
<a class="btn btn-soft" href="transcript.php">My transcript</a>

</div>

</div>



<!-- MY COURSES -->

<div class="row">

<div class="col-12 mb-3">
<h5>My Active Courses</h5>
</div>


<?php if($result->num_rows == 0): ?>

<div class="col-12">
<div class="alert alert-warning">

No approved courses found for:

<br><b><?= $user_email ?></b>

</div>
</div>

<?php endif; ?>


<?php while($course = $result->fetch_assoc()): 

$course_id = $course['course_id'];
$app_id    = $course['application_id'];


/* TOTAL TRAINING DAYS */

$total_days = $conn->query("
SELECT COUNT(DISTINCT attendance_date) c
FROM course_attendance
WHERE course_id='$course_id'
")->fetch_assoc()['c'];

if($total_days==0) $total_days=1;


/* ATTENDANCE */

$days = $conn->query("
SELECT COUNT(*) c
FROM course_attendance
WHERE course_id='$course_id'
AND application_id='$app_id'
AND status='PRESENT'
")->fetch_assoc()['c'];

$attendance_pct = round(($days/$total_days)*100);


/* ACTIVITY */

$activity = $conn->query("
SELECT COUNT(*) c
FROM course_activity
WHERE course_id='$course_id'
AND application_id='$app_id'
")->fetch_assoc()['c'];

$activity_done = $activity > 0;

$activity_pct = $activity_done ? 20 : 0;


/* PROGRESS */

$progress = min(100,$attendance_pct+$activity_pct);


/* STATUS */

$status = $progress>=100 ? "Completed" : "In Progress";


/* CERTIFICATE */

$certificate_ready = ($attendance_pct>=80 && $activity_done);

?>


<div class="col-md-4 mb-4">

<div class="card course-card">

<div class="card-body">

<h6 class="fw-bold"><?= e($course['title']) ?></h6>

<div class="small text-muted mb-3">
Start: <?= date("d M Y",strtotime($course['start_date'])) ?>
</div>


<!-- PROGRESS CARD -->

<div class="mini-card progress-card mb-3">

<div class="mini-title">Course Progress</div>

<div class="progress">
<div class="progress-bar" style="width:<?=$progress?>%"></div>
</div>

<div class="row text-center small mt-2">

<div class="col">
<b><?=$attendance_pct?>%</b><br>
Attendance
</div>

<div class="col">
<b><?=$activity_pct?>%</b><br>
Activity
</div>

<div class="col">
<b><?=$progress?>%</b><br>
Total
</div>

</div>

</div>


<!-- ACTIVITY CARD -->

<div class="mini-card activity-card mb-3">

<div class="mini-title">Course Activity</div>

<?php if($activity_done): ?>

<span class="badge bg-success">Submitted</span>

<?php else: ?>

<a href="course_activity.php?course_id=<?=$course_id?>"
class="btn btn-warning btn-sm">

Submit Activity

</a>

<?php endif; ?>

</div>


<!-- CERTIFICATE CARD -->

<div class="mini-card certificate-card">

<div class="mini-title">Certificate</div>

<?php if($certificate_ready): ?>

<a href="download_certificate.php?course_id=<?=$course_id?>"
class="btn btn-success btn-sm">

Download Certificate

</a>

<?php else: ?>

<span class="badge bg-secondary">
Complete Course First
</span>

<?php endif; ?>

</div>

</div>
</div>
</div>


<?php endwhile; ?>

</div>


</div>

<?php require_once "../footer.php"; ?>