<?php
require_once "../auth.php";
require_role(['SUPPERADMIN', 'OFFICER']);
require_once "../config.php";

$id=$_POST['course_id'];
$status=$_POST['status'];

$stmt=$conn->prepare("
UPDATE courses 
SET status=? 
WHERE id=?
");

$stmt->bind_param("si",$status,$id);

$stmt->execute();

header("Location: courses.php?msg=Course status updated");

exit;
?>