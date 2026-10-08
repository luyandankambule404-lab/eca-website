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


/* CPD TARGET */

$target_points = 12;
$progress_pct  = min(100,round(($total_points/$target_points)*100,1));


/* FETCH COURSES */

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

?>
<!DOCTYPE html>
<html>
<head>

<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">

<title>ECA CPD</title>

<link rel="manifest" href="/cpd/manifest.json">

<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600;700&display=swap" rel="stylesheet">

<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">

<style>

body{
margin:0;
font-family:Poppins;
background:linear-gradient(180deg,#0f172a,#1e3a5f);
color:#111;
}

.app{
max-width:520px;
margin:auto;
padding:20px;
}

/* HEADER */

.header{
color:white;
margin-bottom:20px;
}

.header h3{
margin:0;
font-weight:700;
}

.header small{
color:#cbd5e1;
}

/* PROGRESS CARD */

.progress-card{
background:linear-gradient(135deg,#2563eb,#1d4ed8);
border-radius:16px;
padding:18px;
color:white;
margin-bottom:20px;
box-shadow:0 10px 25px rgba(0,0,0,.25);
}

/* COURSE CARD */

.course{
background:white;
border-radius:16px;
padding:16px;
margin-bottom:16px;
box-shadow:0 10px 25px rgba(0,0,0,.15);
}

/* PROGRESS BAR */

.progress{
height:6px;
background:#e5e7eb;
border-radius:10px;
margin-top:6px;
}

.progress-bar{
background:linear-gradient(90deg,#22c55e,#16a34a);
height:6px;
border-radius:10px;
}

/* MINI CARDS */

.mini{
border-radius:10px;
padding:10px;
margin-top:10px;
}

.mini-progress{ background:#eff6ff; }
.mini-activity{ background:#fff7ed; }
.mini-cert{ background:#ecfdf5; }

/* BUTTON */

.btn{
padding:6px 10px;
border-radius:8px;
font-size:12px;
border:none;
}

.btn-warning{background:#f59e0b;color:white;}
.btn-success{background:#22c55e;color:white;}

/* BADGE */

.badge{
font-size:11px;
padding:4px 8px;
border-radius:8px;
}

/* NAV BAR */

.navbar{
position:fixed;
bottom:0;
left:0;
right:0;
background:white;
display:flex;
justify-content:space-around;
padding:12px 0;
box-shadow:0 -5px 15px rgba(0,0,0,.15);
}

.navbar a{
text-decoration:none;
color:#334155;
font-size:12px;
text-align:center;
}

.navbar i{
display:block;
font-size:18px;
margin-bottom:2px;
}

</style>

</head>

<body>

<div class="app">

<!-- HEADER -->

<div class="header">

<h3>ECA CPD</h3>

<small>Welcome <?= $_SESSION['full_name'] ?></small>

</div>


<!-- CPD PROGRESS -->

<div class="progress-card">

<div style="display:flex;justify-content:space-between">

<div>

<div style="font-size:12px">CPD Points</div>

<div style="font-size:24px;font-weight:700">
<?= $total_points ?>
</div>

</div>

<div style="font-size:18px;font-weight:600">
<?= $progress_pct ?>%
</div>

</div>

<div class="progress">

<div class="progress-bar" style="width:<?= $progress_pct ?>%"></div>

</div>

</div>


<!-- COURSES -->

<?php while($course=$result->fetch_assoc()): 

$course_id=(int)$course['course_id'];
$app_id=(int)$course['application_id'];


/* ATTENDANCE */

$total_days_stmt->bind_param("i", $course_id);
$total_days_stmt->execute();
$total_days=$total_days_stmt->get_result()->fetch_assoc()['c'];

if($total_days==0) $total_days=1;


$days_stmt->bind_param("ii", $course_id, $app_id);
$days_stmt->execute();
$days=$days_stmt->get_result()->fetch_assoc()['c'];

$attendance_pct=round(($days/$total_days)*100);


/* ACTIVITY */

$activity_stmt->bind_param("ii", $course_id, $app_id);
$activity_stmt->execute();
$activity=$activity_stmt->get_result()->fetch_assoc()['c'];

$activity_done=$activity>0;
$activity_pct=$activity_done?20:0;


/* PROGRESS */

$progress=min(100,$attendance_pct+$activity_pct);


/* CERTIFICATE */

$certificate_ready=($attendance_pct>=80 && $activity_done);

?>

<div class="course">

<b><?= $course['title'] ?></b>

<div style="font-size:12px;color:#6b7280;margin-bottom:8px">
Start <?= date("d M Y",strtotime($course['start_date'])) ?>
</div>


<!-- PROGRESS -->

<div class="mini mini-progress">

<div style="font-size:12px;font-weight:600">
Course Progress
</div>

<div class="progress">
<div class="progress-bar" style="width:<?= $progress ?>%"></div>
</div>

<div style="display:flex;justify-content:space-between;font-size:11px;margin-top:6px">

<span>Attendance <?= $attendance_pct ?>%</span>

<span>Activity <?= $activity_pct ?>%</span>

<span>Total <?= $progress ?>%</span>

</div>

</div>


<!-- ACTIVITY -->

<div class="mini mini-activity">

<div style="display:flex;justify-content:space-between">

<span style="font-size:13px;font-weight:600">
Activity
</span>

<?php if($activity_done): ?>

<span class="badge" style="background:#22c55e;color:white">
Submitted
</span>

<?php else: ?>

<a href="course_activity.php?course_id=<?=$course_id?>" class="btn btn-warning">

Submit

</a>

<?php endif; ?>

</div>

</div>


<!-- CERTIFICATE -->

<div class="mini mini-cert">

<div style="display:flex;justify-content:space-between">

<span style="font-size:13px;font-weight:600">
Certificate
</span>

<?php if($certificate_ready): ?>

<a href="download_certificate.php?course_id=<?=$course_id?>" class="btn btn-success">

Download

</a>

<?php else: ?>

<span class="badge" style="background:#64748b;color:white">
Locked
</span>

<?php endif; ?>

</div>

</div>

</div>

<?php endwhile; ?>

</div>


<!-- MOBILE NAV -->

<div class="navbar">

<a href="dashboard.php">
<i class="fa fa-home"></i>
Home
</a>

<a href="courses.php">
<i class="fa fa-book"></i>
Courses
</a>

<a href="applications.php">
<i class="fa fa-file"></i>
Apps
</a>

<a href="/cpd/contractor/transcript.php">
<i class="fa fa-award"></i>
Transcript
</a>

</div>


<script>

if ("serviceWorker" in navigator) {

navigator.serviceWorker.register("/cpd/service-worker.js");

}

</script>

</body>
</html>