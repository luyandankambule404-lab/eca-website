<?php
require_once "../auth.php";
require_role('ADMIN');
require_once "../config.php";
require_once "../header.php";

$msg="";

/* CREATE UPLOAD FOLDER IF NOT EXISTS */

$upload_dir = "../uploads/courses/";

if(!is_dir($upload_dir)){
mkdir($upload_dir,0777,true);
}


/* ADD COURSE */

if($_SERVER['REQUEST_METHOD']=='POST' && $_POST['action']=='add'){

$title=$_POST['title'];
$desc=$_POST['description'];
$start=$_POST['start_date'];
$end=$_POST['end_date'];
$venue=$_POST['venue'];
$cap=$_POST['capacity'];
$points=$_POST['points'];
$status=$_POST['status'];
$uid=$_SESSION['user_id'];

$banner="";


/* UPLOAD BANNER */

if(isset($_FILES['banner']) && $_FILES['banner']['error']==0){

$ext = pathinfo($_FILES['banner']['name'],PATHINFO_EXTENSION);

$filename = "course_".time().".".$ext;

$target = $upload_dir.$filename;

if(move_uploaded_file($_FILES['banner']['tmp_name'],$target)){

$banner="uploads/courses/".$filename;

}

}


/* INSERT COURSE */

$stmt=$conn->prepare("
INSERT INTO courses
(title,description,banner,start_date,end_date,venue,capacity,points,status,created_by)
VALUES(?,?,?,?,?,?,?,?,?,?)");

$stmt->bind_param(
"ssssssissi",
$title,
$desc,
$banner,
$start,
$end,
$venue,
$cap,
$points,
$status,
$uid
);

$stmt->execute();

$msg="Course created successfully";

}



/* UPDATE COURSE */

if($_SERVER['REQUEST_METHOD']=='POST' && $_POST['action']=='update'){

$id=$_POST['course_id'];

$title=$_POST['title'];
$desc=$_POST['description'];
$start=$_POST['start_date'];
$end=$_POST['end_date'];
$venue=$_POST['venue'];
$cap=$_POST['capacity'];
$points=$_POST['points'];
$status=$_POST['status'];


/* HANDLE NEW BANNER */

$banner="";

if(isset($_FILES['banner']) && $_FILES['banner']['error']==0){

$ext = pathinfo($_FILES['banner']['name'],PATHINFO_EXTENSION);

$filename = "course_".time().".".$ext;

$target = $upload_dir.$filename;

if(move_uploaded_file($_FILES['banner']['tmp_name'],$target)){

$banner="uploads/courses/".$filename;

}

}


/* UPDATE QUERY */

if($banner!=""){

$stmt=$conn->prepare("
UPDATE courses SET
title=?,
description=?,
banner=?,
start_date=?,
end_date=?,
venue=?,
capacity=?,
points=?,
status=?
WHERE id=?");

$stmt->bind_param(
"ssssssissi",
$title,
$desc,
$banner,
$start,
$end,
$venue,
$cap,
$points,
$status,
$id
);

}else{

$stmt=$conn->prepare("
UPDATE courses SET
title=?,
description=?,
start_date=?,
end_date=?,
venue=?,
capacity=?,
points=?,
status=?
WHERE id=?");

$stmt->bind_param(
"sssssidsi",
$title,
$desc,
$start,
$end,
$venue,
$cap,
$points,
$status,
$id
);

}

$stmt->execute();

$msg="Course updated successfully";

}



/* DELETE COURSE */

if(isset($_GET['delete'])){

$id=$_GET['delete'];

$conn->query("DELETE FROM courses WHERE id='$id'");

$msg="Course deleted successfully";

}



/* UPDATE STATUS */

if(isset($_POST['update_status'])){

$id=$_POST['course_id'];
$status=$_POST['status'];

$conn->query("UPDATE courses SET status='$status' WHERE id='$id'");

$msg="Course status updated";

}



/* LOAD COURSES */

$courses=$conn->query("
SELECT 
c.*,
COUNT(a.id) applications,
SUM(CASE WHEN a.status='Approved' THEN 1 ELSE 0 END) learners
FROM courses c
LEFT JOIN cpd_applications a 
ON a.course_id=c.id
GROUP BY c.id
ORDER BY c.start_date DESC
");



/* ANALYTICS */

$total_courses=$conn->query("SELECT COUNT(*) c FROM courses")->fetch_assoc()['c'];

$total_apps=$conn->query("SELECT COUNT(*) c FROM cpd_applications")->fetch_assoc()['c'];

$total_learners=$conn->query("
SELECT COUNT(*) c FROM cpd_applications 
WHERE status='Approved'
")->fetch_assoc()['c'];

?>

<!DOCTYPE html>
<html>
<head>

<title>ECA CPD Courses</title>

<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css">

<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">

<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600&display=swap" rel="stylesheet">

<style>

body{
background:#eef2f7;
font-family:Poppins;
}

.page-header{
display:flex;
justify-content:space-between;
align-items:center;
margin-bottom:20px;
}

.analytics-card{
padding:25px;
border-radius:10px;
color:white;
text-align:center;
}

.course-card{
background:white;
border-radius:14px;
overflow:hidden;
box-shadow:0 10px 25px rgba(0,0,0,0.1);
transition:.3s;
}

.course-card:hover{
transform:translateY(-6px);
}

.course-banner img{
width:100%;
height:180px;
object-fit:cover;
}

.course-content{
padding:20px;
}

.course-meta{
font-size:13px;
color:#777;
}

.course-stats{
display:flex;
justify-content:space-between;
margin-top:10px;
font-size:13px;
}

.course-actions{
display:flex;
gap:8px;
margin-top:15px;
}

.countdown{
margin-top:10px;
font-weight:600;
color:#06254a;
}

</style>

</head>

<body>

<div class="container mt-4">

<div class="page-header">

<h4>CPD Courses Dashboard</h4>

<button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addModal">
Add Course
</button>

</div>

<?php if($msg): ?>

<div class="alert alert-success"><?=$msg?></div>

<?php endif; ?>


<!-- ANALYTICS -->




<!-- SEARCH -->

<input 
type="text"
id="courseSearch"
class="form-control mb-6"
placeholder="Search courses">


<!-- COURSES -->

<div class="row g-6">

<?php while($c=$courses->fetch_assoc()): ?>

<div class="col-md-6 course-item">

<div class="course-card">

<div class="course-banner">

<img src="../<?= !empty($c['banner']) ? $c['banner'] : 'uploads/courses/banner.png' ?>" width="100%">

</div>

<div class="course-content">

<h5><?=$c['title']?></h5>

<div class="course-meta">

<i class="fa fa-calendar"></i> <?=$c['start_date']?><br>

<i class="fa fa-map-marker"></i> <?=$c['venue']?>

</div>

<div class="course-stats">

<div>
<i class="fa fa-users"></i> <?=$c['learners']?>
</div>

<div>
<i class="fa fa-file"></i> <?=$c['applications']?>
</div>

<div>
<i class="fa fa-star"></i> <?=$c['points']?>
</div>

</div>

<div class="countdown" data-date="<?=$c['start_date']?>"></div>


<div class="course-actions">

<a 
href="course_students.php?course_id=<?=$c['id']?>"
class="btn btn-sm btn-success">
<i class="fa fa-user-graduate"></i> Students
</a>

<button class="btn btn-sm btn-warning editCourse"

data-id="<?=$c['id']?>"
data-title="<?=$c['title']?>"
data-desc="<?=$c['description']?>"
data-start="<?=$c['start_date']?>"
data-end="<?=$c['end_date']?>"
data-venue="<?=$c['venue']?>"
data-cap="<?=$c['capacity']?>"
data-points="<?=$c['points']?>"
data-status="<?=$c['status']?>"

>
Edit
</button>

<button class="btn btn-sm btn-info statusCourse"
data-id="<?=$c['id']?>"
data-status="<?=$c['status']?>"
>
<?=$c['status']?>
</button>

<a 
href="?delete=<?=$c['id']?>"
class="btn btn-sm btn-danger"
onclick="return confirm('Delete this course?')"
>
Delete
</a>
<a href="send_course_email.php?course_id=<?= (int)$c['id'] ?>"
   class="btn btn-sm"
   onclick="return confirm('Send this course details email to all members?');"
   style="background:linear-gradient(135deg,#134f62,#25809b);color:#fff;border:none;border-radius:10px;padding:8px 14px;font-weight:600;">
   <i class="fa-solid fa-paper-plane"></i> Email All Members
</a>
</div>
</div>

</div>

</div>

<?php endwhile; ?>

</div>

</div>


<!-- ADD COURSE MODAL -->

<div class="modal fade" id="addModal">

<div class="modal-dialog modal-lg">

<div class="modal-content">

<form method="POST" enctype="multipart/form-data">

<input type="hidden" name="action" value="add">

<div class="modal-header bg-primary text-white">

<h5>Add Course</h5>

<button class="btn-close btn-close-white" data-bs-dismiss="modal"></button>

</div>

<div class="modal-body">

<div class="row g-2">

<div class="col-md-6">

<label>Course Title</label>

<input class="form-control" name="title" required>

</div>

<div class="col-md-3">

<label>Points</label>

<input class="form-control" name="points">

</div>

<div class="col-md-3">

<label>Capacity</label>

<input class="form-control" name="capacity">

</div>

<div class="col-md-6">

<label>Start Date</label>

<input type="datetime-local" class="form-control" name="start_date">

</div>

<div class="col-md-6">

<label>End Date</label>

<input type="datetime-local" class="form-control" name="end_date">

</div>

<div class="col-md-6">

<label>Venue</label>

<input class="form-control" name="venue">

</div>

<div class="col-md-6">

<label>Status</label>

<select class="form-control" name="status">

<option>OPEN</option>
<option>CLOSED</option>
<option>DRAFT</option>
<option>COMPLETED</option>

</select>

</div>

<div class="col-12">

<label>Course Banner</label>

<input type="file" class="form-control" name="banner">

</div>

<div class="col-12">

<label>Description</label>

<textarea class="form-control" name="description"></textarea>

</div>

</div>

</div>

<div class="modal-footer">

<button class="btn btn-success">Save</button>

</div>

</form>

</div>

</div>

</div>
<div class="modal fade" id="editModal">

<div class="modal-dialog modal-lg">

<div class="modal-content">
<form method="POST" enctype="multipart/form-data">

<input type="hidden" name="action" value="update">
<input type="hidden" name="course_id" id="course_id">

<div class="modal-header bg-warning text-white">

<h5>Edit Course</h5>

<button class="btn-close btn-close-white" data-bs-dismiss="modal"></button>

</div>

<div class="modal-body">

<div class="row g-2">

<div class="col-md-6">
<label>Course Title</label>
<input class="form-control" name="title" id="title">
</div>

<div class="col-md-3">
<label>Points</label>
<input class="form-control" name="points" id="points">
</div>

<div class="col-md-3">
<label>Capacity</label>
<input class="form-control" name="capacity" id="capacity">
</div>

<div class="col-md-6">
<label>Start Date</label>
<input type="datetime-local" class="form-control" name="start_date" id="start">
</div>

<div class="col-md-6">
<label>End Date</label>
<input type="datetime-local" class="form-control" name="end_date" id="end">
</div>

<div class="col-md-6">
<label>Venue</label>
<input class="form-control" name="venue" id="venue">
</div>

<div class="col-md-6">
<label>Status</label>

<select class="form-control" name="status" id="status">
<option>OPEN</option>
<option>CLOSED</option>
<option>DRAFT</option>
<option>COMPLETED</option>
</select>

</div>

<div class="col-12">
<label>Description</label>
<textarea class="form-control" name="description" id="description"></textarea>
</div>

</div>
<div class="col-12">

<label>Course Banner</label>

<input type="file" class="form-control" name="banner"id="banner">

</div>
</div>

<div class="modal-footer">
<button class="btn btn-success">Update Course</button>
</div>

</form>

</div>

</div>

</div>

<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>

<script>

/* SEARCH */

$("#courseSearch").on("keyup",function(){

var value=$(this).val().toLowerCase();

$(".course-item").filter(function(){

$(this).toggle($(this).text().toLowerCase().indexOf(value)>-1);

});

});
/* EDIT COURSE */

$(document).on("click",".editCourse",function(){

var modal = new bootstrap.Modal(document.getElementById("editModal"));

$("#course_id").val($(this).data("id"));

$("#title").val($(this).data("title"));

$("#description").val($(this).data("desc"));

$("#start").val($(this).data("start"));

$("#end").val($(this).data("end"));

$("#venue").val($(this).data("venue"));

$("#capacity").val($(this).data("cap"));

$("#points").val($(this).data("points"));
$("#banner").val($(this).data("banner"));


modal.show();

});

/* COUNTDOWN */

document.querySelectorAll(".countdown").forEach(function(el){

var date=new Date(el.dataset.date).getTime();

setInterval(function(){

var now=new Date().getTime();

var distance=date-now;

var days=Math.floor(distance/(1000*60*60*24));

var hours=Math.floor((distance%(1000*60*60*24))/(1000*60*60));

if(distance>0){

el.innerHTML="Starts in "+days+"d "+hours+"h";

}else{

el.innerHTML="Course Started";

}

},1000);

});

</script>

</body>
</html>