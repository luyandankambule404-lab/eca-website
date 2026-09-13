<?php
require_once "../auth.php";
require_role('ADMIN');
require_once "../config.php";

$id=$_GET['id'];

$stmt=$conn->prepare("DELETE FROM courses WHERE id=?");

$stmt->bind_param("i",$id);

$stmt->execute();

header("Location: courses.php?msg=Course deleted");

exit;
?>