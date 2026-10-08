<?php
require_once "../auth.php";
require_role('CONTRACTOR');
require_once "../config.php";

$user_id    = (int)($_SESSION['user_id'] ?? 0);
$user_email = $_SESSION['email'];

/* ================================
TOTAL CPD POINTS
================================ */

$points_stmt = $conn->prepare("
SELECT COALESCE(SUM(points),0) p
FROM cpd_points_ledger
WHERE user_id=?
");
$points_stmt->bind_param("i", $user_id);
$points_stmt->execute();
$points = $points_stmt->get_result()->fetch_assoc()['p'] ?? 0;


/* ================================
ACTIVE COURSES
================================ */

$courses_stmt = $conn->prepare("
SELECT c.*, a.id AS application_id
FROM cpd_applications a
JOIN courses c ON c.id = a.course_id
WHERE a.email=?
AND a.status='Approved'
");
$courses_stmt->bind_param("s", $user_email);
$courses_stmt->execute();
$result = $courses_stmt->get_result();


/* ================================
UPCOMING COURSES
================================ */

$upcoming = $conn->query("
SELECT *
FROM courses
WHERE start_date > NOW()
ORDER BY start_date ASC
");

$attendance_stmt = $conn->prepare("
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

<meta name="viewport" content="width=device-width, initial-scale=1">

<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css">

<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">

<style>

body{
background:#f4f6f9;
font-family:Poppins;
padding-bottom:90px;
}

/* HEADER */

.app-header{

background:linear-gradient(135deg,#06254a,#0b4c92);
padding:18px;
color:white;
border-bottom-left-radius:20px;
border-bottom-right-radius:20px;

}

.header-inner{
display:flex;
justify-content:space-between;
align-items:center;
}

.logo{
height:38px;
}

.avatar{
width:34px;
height:34px;
border-radius:50%;
}

/* CPD CARD */

.cpd-card{

background:linear-gradient(135deg,#06254a,#0b4c92);
color:white;
border-radius:16px;
padding:20px;
margin-bottom:20px;
box-shadow:0 10px 30px rgba(0,0,0,.2);

}

/* COURSE CARD */

.course-card{

background:white;
border-radius:16px;
padding:18px;
margin-bottom:18px;

box-shadow:0 10px 25px rgba(0,0,0,.1);

}

.course-title{
font-weight:600;
font-size:16px;
}

.progress{
height:8px;
margin-top:10px;
}

/* MINI CARD */

.mini-card{

background:#f7f7f7;
border-radius:12px;
padding:10px;
margin-bottom:10px;

}

/* CERTIFICATE CARD */

.certificate-card{

background:linear-gradient(135deg,#fff,#eef3fb);
border-radius:18px;
padding:18px;
display:flex;
align-items:center;
gap:15px;
box-shadow:0 10px 25px rgba(0,0,0,.1);

}

.cert-icon{

width:50px;
height:50px;
background:#e3262e;
color:white;
border-radius:12px;
display:flex;
align-items:center;
justify-content:center;
font-size:22px;

}

/* NAV */

.bottom-nav{

position:fixed;
bottom:10px;
left:50%;
transform:translateX(-50%);
width:92%;
background:white;
border-radius:18px;
display:flex;
justify-content:space-around;
padding:10px;
box-shadow:0 10px 30px rgba(0,0,0,.15);

}

.nav-item{

text-align:center;
font-size:11px;
color:#555;
text-decoration:none;

}

.nav-item i{

font-size:20px;
display:block;

}

.nav-item.active{
color:#e3262e;
font-weight:600;
}

</style>
</head>

<body>


<!-- HEADER -->

<div class="app-header">

<div class="header-inner">

<img src="https://eca.co.sz/cpd/images/logo.jpg" class="logo">

<div>
<a href="/index.php" style="color:#fff;font-weight:700;text-decoration:none;margin-right:14px;">Website</a>
<i class="fa fa-bell"></i>
</div>

<img src="https://ui-avatars.com/api/?name=<?=$user_email?>" class="avatar">

</div>

<h6 class="mt-2">ECA CPD Learner Portal</h6>

</div>


<div class="container mt-3">


<!-- CPD POINTS CARD -->

<div class="cpd-card">

<h5><?=$points?> CPD Points</h5>

<small>Your professional development progress</small>

<div class="progress mt-2">

<div class="progress-bar bg-danger"
style="width:<?=min(100,$points*8)?>%"></div>

</div>

</div>


<!-- MY COURSES -->

<h6 class="mb-3">My Courses</h6>

<?php while($course = $result->fetch_assoc()): 

$course_id = (int)$course['id'];
$app_id    = (int)$course['application_id'];


/* ATTENDANCE */

$attendance_stmt->bind_param("ii", $course_id, $app_id);
$attendance_stmt->execute();
$attendance = $attendance_stmt->get_result()->fetch_assoc()['c'];


/* ACTIVITIES */

$activity_stmt->bind_param("ii", $course_id, $app_id);
$activity_stmt->execute();
$activity = $activity_stmt->get_result()->fetch_assoc()['c'];


$progress = min(100,$attendance*20 + ($activity>0 ? 20 : 0));

?>

<div class="course-card">

<div class="course-title">

<?=$course['title']?>

</div>

<div class="small text-muted">

<?=$course['start_date']?> • <?=$course['venue']?>

</div>


<div class="progress">

<div class="progress-bar bg-success"
style="width:<?=$progress?>%"></div>

</div>

<small><?=$progress?>% completed</small>


<div class="row mt-3">

<div class="col">

<div class="mini-card text-center">

<b><?=$attendance?></b><br>
Attendance

</div>

</div>

<div class="col">

<div class="mini-card text-center">

<?php if($activity>0): ?>

<span class="badge bg-success">Submitted</span>

<?php else: ?>

<a href="activity.php?course_id=<?=$course_id?>&application_id=<?=$app_id?>"
class="btn btn-warning btn-sm">

Submit Activity

</a>

<?php endif; ?>

</div>

</div>

</div>


<?php if($progress >= 100): ?>

<div class="certificate-card mt-3">

<div class="cert-icon">

<i class="fa fa-award"></i>

</div>

<div>

<b>Certificate Available</b>

<br>

<a href="../training_cerficate.php?id=<?=$app_id?>"
class="btn btn-danger btn-sm mt-2">

Download Certificate

</a>

</div>

</div>

<?php endif; ?>


</div>

<?php endwhile; ?>


<!-- UPCOMING COURSES -->

<h6 class="mt-4">Upcoming Courses</h6>

<div class="card p-3">

<?php while($u=$upcoming->fetch_assoc()): ?>

<div class="d-flex justify-content-between mb-2">

<div>

<b><?=$u['title']?></b><br>

<small><?=$u['start_date']?></small>

</div>

<i class="fa fa-book text-danger"></i>

</div>

<?php endwhile; ?>

</div>


</div>



<!-- MOBILE NAV -->

<div class="bottom-nav">

<a href="dashboard.php" class="nav-item active">

<i class="fa fa-home"></i>
Home

</a>

<a href="course.php" class="nav-item">

<i class="fa fa-book"></i>
Courses

</a>

<a href="points.php" class="nav-item">

<i class="fa fa-star"></i>
Points

</a>

<a href="account.php" class="nav-item">

<i class="fa fa-user"></i>
Account

</a>

</div>

</body>
</html>