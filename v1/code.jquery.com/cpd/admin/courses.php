<?php
require_once "../auth.php";
require_role('SUPPERADMIN');
require_once "../config.php";

$msg="";
$allowed_statuses = ['OPEN', 'CLOSED', 'DRAFT', 'COMPLETED'];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    cpd_require_csrf();
}

/* CREATE UPLOAD FOLDER IF NOT EXISTS */

$upload_dir = "../uploads/courses/";

if(!is_dir($upload_dir)){
mkdir($upload_dir,0750,true);
}

function cpd_store_course_banner(array $file, string $uploadDir): string
{
    if (($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK || ($file['size'] ?? 0) > 2097152) {
        return '';
    }
    $mime = (new finfo(FILEINFO_MIME_TYPE))->file((string) ($file['tmp_name'] ?? ''));
    $extensions = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp'];
    if (!isset($extensions[$mime]) || @getimagesize((string) $file['tmp_name']) === false) {
        return '';
    }
    $filename = 'course_' . bin2hex(random_bytes(16)) . '.' . $extensions[$mime];
    return move_uploaded_file($file['tmp_name'], $uploadDir . $filename)
        ? 'uploads/courses/' . $filename
        : '';
}


/* ADD COURSE */

if($_SERVER['REQUEST_METHOD']=='POST' && ($_POST['action'] ?? '')==='add'){

$title=$_POST['title'];
$desc=$_POST['description'];
$start=$_POST['start_date'];
$end=$_POST['end_date'];
$venue=$_POST['venue'];
$cap=(int)$_POST['capacity'];
$points=(float)$_POST['points'];
$status=$_POST['status'];
$uid=(int)$_SESSION['user_id'];
if(!in_array($status, $allowed_statuses, true)){
    $status='DRAFT';
}

$banner="";


/* UPLOAD BANNER */

if(isset($_FILES['banner'])){
    $banner = cpd_store_course_banner($_FILES['banner'], $upload_dir);
}


/* INSERT COURSE */

$stmt=$conn->prepare("
INSERT INTO courses
(title,description,banner,start_date,end_date,venue,capacity,points,status,created_by)
VALUES(?,?,?,?,?,?,?,?,?,?)");

$stmt->bind_param(
"ssssssidsi",
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

if($_SERVER['REQUEST_METHOD']=='POST' && ($_POST['action'] ?? '')==='update'){

$id=(int)$_POST['course_id'];

$title=$_POST['title'];
$desc=$_POST['description'];
$start=$_POST['start_date'];
$end=$_POST['end_date'];
$venue=$_POST['venue'];
$cap=(int)$_POST['capacity'];
$points=(float)$_POST['points'];
$status=$_POST['status'];
if(!in_array($status, $allowed_statuses, true)){
    $status='DRAFT';
}


/* HANDLE NEW BANNER */

$banner="";

if(isset($_FILES['banner'])){
    $banner = cpd_store_course_banner($_FILES['banner'], $upload_dir);
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
"ssssssidsi",
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

if(isset($_POST['delete'])){

$id=(int)$_POST['delete'];

$stmt=$conn->prepare("DELETE FROM courses WHERE id=?");
$stmt->bind_param("i", $id);
$stmt->execute();

$msg="Course deleted successfully";

}



/* UPDATE STATUS */

if(isset($_POST['update_status'])){

$id=(int)$_POST['course_id'];
$status=$_POST['status'];

if(in_array($status, $allowed_statuses, true)){
    $stmt=$conn->prepare("UPDATE courses SET status=? WHERE id=?");
    $stmt->bind_param("si", $status, $id);
    $stmt->execute();
    $msg="Course status updated";
}else{
    $msg="Invalid course status";
}

}



/* LOAD COURSES */

$courses = ($conn instanceof mysqli) ? $conn->query("
SELECT 
c.*,
COUNT(a.id) applications,
SUM(CASE WHEN a.status='Approved' THEN 1 ELSE 0 END) learners
FROM courses c
LEFT JOIN cpd_applications a 
ON a.course_id=c.id
GROUP BY c.id
ORDER BY c.start_date DESC
") : false;



/* ANALYTICS */

$total_courses = 0;
$total_apps = 0;
$total_learners = 0;
if ($conn instanceof mysqli) {
    $q = $conn->query("SELECT COUNT(*) c FROM courses");
    if ($q) {
        $total_courses = (int) ($q->fetch_assoc()['c'] ?? 0);
    }
    $q = $conn->query("SELECT COUNT(*) c FROM cpd_applications");
    if ($q) {
        $total_apps = (int) ($q->fetch_assoc()['c'] ?? 0);
    }
    $q = $conn->query("SELECT COUNT(*) c FROM cpd_applications WHERE status='Approved'");
    if ($q) {
        $total_learners = (int) ($q->fetch_assoc()['c'] ?? 0);
    }
}

require_once "../header.php";
require __DIR__ . "/../course-manager-view.php";
require_once "../footer.php";
