<?php

require_once "../config.php";

$course_id = $_GET['course_id'];

header("Content-Type: application/vnd.ms-excel");
header("Content-Disposition: attachment; filename=attendance_report.xls");

echo "Name\tEmail\tCompany\tAttendance%\tCertificate\n";

$students = $conn->query("
SELECT *
FROM cpd_applications
WHERE course_id='$course_id'
");

while($s=$students->fetch_assoc()){

$app_id = $s['id'];

$present = $conn->query("
SELECT COUNT(*) c
FROM course_attendance
WHERE course_id='$course_id'
AND application_id='$app_id'
AND status='PRESENT'
")->fetch_assoc()['c'];

$days_total = 3;

$percent = round(($present/$days_total)*100);

$cert = $percent >= 70 ? "Eligible" : "Not Eligible";

echo $s['full_name']."\t".
$s['email']."\t".
$s['company_name']."\t".
$percent."%\t".
$cert."\n";

}