<?php
require_once "../auth.php";
require_role('CONTRACTOR');
require_once "../config.php";

$user_email = $_SESSION['email'] ?? '';

/* ===============================
MY COURSES
================================ */

$mycourses_stmt = $conn->prepare("
SELECT c.*, a.training_status
FROM cpd_applications a
JOIN courses c ON c.id = a.course_id
WHERE a.email=?
ORDER BY c.start_date DESC
");
$mycourses_stmt->bind_param("s", $user_email);
$mycourses_stmt->execute();
$mycourses = $mycourses_stmt->get_result();

/* ===============================
UPCOMING COURSES
================================ */

$upcoming = $conn->query("
SELECT *
FROM courses
WHERE start_date > NOW()
AND status='OPEN'
ORDER BY start_date ASC
");

?>

<!DOCTYPE html>
<html>
<head>

<meta name="viewport" content="width=device-width, initial-scale=1">

<link rel="stylesheet"
href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css">

<link rel="stylesheet"
href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">

<style>

body{
background:#f4f6f9;
font-family:Poppins;
margin:0;
}

/* HEADER */

.app-header{
background:linear-gradient(135deg,#06254a,#0b4c92);
padding:18px 16px 45px 16px;
color:white;
position:sticky;
top:0;
z-index:999;
border-bottom-left-radius:20px;
border-bottom-right-radius:20px;
box-shadow:0 15px 40px rgba(0,0,0,0.35);
}

.header-inner{
display:flex;
justify-content:space-between;
align-items:center;
}

.header-left{
display:flex;
gap:10px;
align-items:center;
}

.logo{
height:38px;
border-radius:10px;
}

.app-title{
font-weight:700;
font-size:15px;
}

.app-sub{
font-size:11px;
opacity:.8;
}

/* COURSE CARD */

.course-card{

background:white;

border-radius:16px;

padding:18px;

box-shadow:0 10px 25px rgba(0,0,0,.1);

margin-bottom:16px;

}

.course-title{

font-weight:600;

font-size:16px;

}

.course-info{

font-size:13px;

color:#555;

margin-top:6px;

}

.btn-course{

display:block;

margin-top:10px;

background:#e3262e;

color:white;

text-align:center;

padding:10px;

border-radius:10px;

font-weight:600;

text-decoration:none;

}

/* STATUS */

.status{

background:#16a34a;

color:white;

font-size:11px;

padding:4px 10px;

border-radius:20px;

}

/* SECTION TITLE */

.section-title{

font-weight:700;

margin-bottom:10px;

}

/* BOTTOM NAV */

.bottom-nav{

position:fixed;

bottom:12px;

left:50%;

transform:translateX(-50%);

width:92%;

max-width:500px;

background:white;

border-radius:18px;

box-shadow:0 10px 30px rgba(0,0,0,0.15);

display:flex;

justify-content:space-around;

padding:10px;

}

.nav-item{

display:flex;

flex-direction:column;

align-items:center;

font-size:11px;

text-decoration:none;

color:#6b7280;

}

.nav-item.active{

color:#e3262e;

font-weight:600;

}

body{

padding-bottom:90px;

}

</style>

</head>

<body>


<!-- HEADER -->

<div class="app-header">

<div class="header-inner">

<div class="header-left">

<img src="https://eca.co.sz/cpd/images/logo.jpg" class="logo">

<div>
<div class="app-title">ECA CPD</div>
<div class="app-sub">Courses</div>
</div>

</div>

<a href="/index.php" style="color:#fff;font-weight:700;text-decoration:none;">Website</a>

</div>

</div>


<div class="container mt-3">


<!-- MY COURSES -->

<div class="section-title">
<i class="fa fa-book"></i> My Courses
</div>

<?php if($mycourses && $mycourses->num_rows > 0): ?>

<?php while($c=$mycourses->fetch_assoc()): ?>

<div class="course-card">

<div class="course-title">
<?=$c['title']?>
</div>

<div class="course-info">
<i class="fa fa-calendar"></i> <?=$c['start_date']?><br>
<i class="fa fa-map-marker-alt"></i> <?=$c['venue']?>
</div>

<br>

<span class="status">
<?=$c['training_status']?>
</span>

<a href="dashboard.php" class="btn-course">
Open Course
</a>

</div>

<?php endwhile; ?>

<?php else: ?>

<div class="text-muted">You are not enrolled in any course yet.</div>

<?php endif; ?>


<br>


<!-- UPCOMING COURSES -->

<div class="section-title">
<i class="fa fa-calendar"></i> Upcoming Courses
</div>

<?php if($upcoming && $upcoming->num_rows > 0): ?>

<?php while($u=$upcoming->fetch_assoc()): ?>

<div class="course-card">

<div class="course-title">
<?=$u['title']?>
</div>

<div class="course-info">

<i class="fa fa-calendar"></i> <?=$u['start_date']?><br>

<i class="fa fa-map-marker-alt"></i> <?=$u['venue']?>

</div>

<a href="apply.php?course_id=<?=$u['id']?>" class="btn-course">

Register

</a>

</div>

<?php endwhile; ?>

<?php else: ?>

<div class="text-muted">No upcoming courses available.</div>

<?php endif; ?>


</div>


<!-- BOTTOM NAV -->

<div class="bottom-nav">

<a href="dashboard.php" class="nav-item">
<i class="fa fa-home"></i>
<span>Home</span>
</a>

<a href="courses.php" class="nav-item active">
<i class="fa fa-book"></i>
<span>Courses</span>
</a>

<a href="news.php" class="nav-item">
<i class="fa fa-star"></i>
<span>News</span>
</a>

<a href="account.php" class="nav-item">
<i class="fa fa-user"></i>
<span>Account</span>
</a>

</div>

</body>
</html>