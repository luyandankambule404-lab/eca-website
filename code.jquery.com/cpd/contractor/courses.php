<?php
require_once "../auth.php";
require_role('CONTRACTOR');
require_once "../config.php";
require_once "../cheader.php";
$user_email = $_SESSION['email'] ?? '';

$sql = "
SELECT c.*,a.status
FROM courses c
JOIN cpd_applications a ON a.course_id=c.id
WHERE a.email='$user_email'
AND a.status='Approved'
ORDER BY c.start_date
";

$courses = $conn->query($sql);

if(!$courses){
die("Query Error: ".$conn->error);
}
?>

<!DOCTYPE html>
<html>

<head>

<title>My CPD Courses</title>

<link rel="stylesheet"
href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css">

<style>

body{
background:#f5f7fb;
}

.course-card{
background:white;
padding:25px;
border-radius:12px;
box-shadow:0 8px 25px rgba(0,0,0,0.1);
margin-bottom:25px;
}

.progress{
height:20px;
}

</style>

</head>

<body>

<div class="container mt-4">

<h3 class="mb-4">My CPD Courses</h3>

<?php if($courses->num_rows==0): ?>

<div class="alert alert-warning">
You have not enrolled in any courses yet.
</div>

<?php endif; ?>


<?php while($c=$courses->fetch_assoc()): ?>

<div class="course-card">

<h5><?=$c['title']?></h5>

<p class="text-muted"><?=$c['venue']?></p>

<?php

$start = strtotime($c['start_date']);
$end = strtotime($c['end_date']);
$now = time();

$total_days = max(1,ceil(($end-$start)/86400));

$passed_days = floor(($now-$start)/86400);

$progress = ($passed_days/$total_days)*100;

if($progress < 0) $progress=0;
if($progress >100) $progress=100;

?>

<?php if($now >= $start): ?>

<div class="progress mb-3">

<div class="progress-bar bg-success"
style="width:<?=$progress?>%">

<?=round($progress)?>%

</div>

</div>

<?php else: ?>

<span class="badge bg-warning">Course not started</span>

<?php endif; ?>


<?php

$today = floor(($now-$start)/86400)+1;

$check_sql="
SELECT id FROM cpd_daily_feedback
WHERE user_id='{$user_email}'
AND course_id='{$c['id']}'
AND day_number='$today'
";

$check = $conn->query($check_sql);

$feedback_done = $check ? $check->num_rows : 0;

?>

<?php if($feedback_done==0 && $now>=$start && $now<=$end): ?>

<form method="POST" action="submit_feedback.php">

<input type="hidden" name="course_id" value="<?=$c['id']?>">
<input type="hidden" name="day_number" value="<?=$today?>">

<label class="mt-2">Rate today's training</label>

<select name="rating" class="form-control">

<option value="5">Excellent</option>
<option value="4">Good</option>
<option value="3">Average</option>
<option value="2">Poor</option>
<option value="1">Very Poor</option>

</select>

<textarea
name="comment"
class="form-control mt-2"
placeholder="Your feedback"></textarea>

<button class="btn btn-primary mt-2">
Submit Daily Feedback
</button>

</form>

<?php endif; ?>


<?php

$submitted_sql="
SELECT COUNT(*) c
FROM cpd_daily_feedback
WHERE user_id='{$user_email}'
AND course_id='{$c['id']}'
";

$res = $conn->query($submitted_sql);

$row = $res ? $res->fetch_assoc() : ['c'=>0];

$submitted = $row['c'];

$completed = $submitted >= $total_days;

?>

<div class="mt-3">

<?php if($completed): ?>

<a
href="download_certificate.php?course_id=<?=$c['id']?>"
class="btn btn-success">

Download Certificate

</a>

<?php else: ?>

<button class="btn btn-secondary" disabled>

Complete Course To Unlock Certificate

</button>

<?php endif; ?>

</div>

</div>

<?php endwhile; ?>

</div>

</body>

</html>