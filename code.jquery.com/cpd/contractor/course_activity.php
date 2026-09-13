<?php
require_once "../auth.php";
require_role('CONTRACTOR');
require_once "../config.php";

$course_id = $_GET['course_id'];
$app_id = $_GET['application_id'] ?? 0;

$msg="";

if(isset($_POST['submit_activity'])){

$q1=$_POST['q1'];
$q2=$_POST['q2'];
$q3=$_POST['q3'];
$q4=$_POST['q4'];

$stmt=$conn->prepare("
INSERT INTO course_activity
(course_id,application_id,q1,q2,q3,q4,submitted_at)
VALUES(?,?,?,?,?, ?,NOW())
");

$stmt->bind_param("iissss",
$course_id,
$app_id,
$q1,
$q2,
$q3,
$q4
);

$stmt->execute();

$msg="Activity submitted successfully";
}

require_once "../cheader.php";
?>
<style>


/* CENTER FORM */

.activity-wrapper{
max-width:720px;
margin:auto;
padding:60px 20px;
}

/* GLOSSY CARD */

.activity-card{
background:rgba(255,255,255,0.08);
backdrop-filter:blur(14px);
border-radius:16px;
padding:35px;
box-shadow:0 20px 45px rgba(0,0,0,.25);
border:1px solid rgba(255,255,255,0.15);
}

/* HEADER */

.activity-title{
font-size:22px;
font-weight:700;
color:#000;
margin-bottom:25px;
}

/* LABEL */

.activity-card label{
color:#000;
font-size:14px;
margin-bottom:6px;
}

/* INPUT */

.activity-card textarea,
.activity-card select{
background:rgba(255,255,255,0.12);
border:1px solid rgba(255,255,255,21);
border-radius:8px;
border-color:#000;
color:#000;
padding:10px;
}

.activity-card textarea::placeholder{
color:#cbd5e1;
}

.activity-card textarea:focus,
.activity-card select:focus{
border-color:#000;
box-shadow:0 0 0 2px rgba(96,165,250,.3);
outline:none;
background:rgba(255,255,255,0.18);
}

/* BUTTON */

.btn-submit{
background:linear-gradient(135deg,#2563eb,#1d4ed8);
border:none;
padding:10px 25px;
border-radius:8px;
font-weight:600;
color:white;
transition:.25s;
}

.btn-submit:hover{
transform:translateY(-2px);
box-shadow:0 8px 20px rgba(0,0,0,.25);
}

/* SUCCESS ALERT */

.alert-success{
background:#16a34a;
border:none;
color:white;
}

</style>



<div class="activity-wrapper">

<div class="activity-card">

<div class="activity-title">
Course Feedback Activity
</div>

<?php if($msg): ?>
<div class="alert alert-success"><?=$msg?></div>
<?php endif; ?>


<form method="POST">

<div class="mb-3">
<label>1. What did you learn from this training?</label>
<textarea name="q1" rows="3" class="form-control" required></textarea>
</div>


<div class="mb-3">
<label>2. How will you apply this knowledge in your work?</label>
<textarea name="q2" rows="3" class="form-control" required></textarea>
</div>


<div class="mb-3">
<label>3. Rate the training quality</label>

<select name="q3" class="form-control">

<option>Excellent</option>
<option>Good</option>
<option>Average</option>
<option>Poor</option>

</select>

</div>


<div class="mb-4">
<label>4. Suggestions for improvement</label>
<textarea name="q4" rows="3" class="form-control"></textarea>
</div>


<button class="btn-submit" name="submit_activity">
Submit Activity
</button>

</form>

</div>

</div>

</div>

<?php require_once "../footer.php"; ?>