<?php

require_once "../auth.php";
require_role(['SUPPERADMIN', 'OFFICER']);

$course_id = (int)($_GET['course_id'] ?? 0);

header("Content-Type: application/vnd.ms-excel");
header("Content-Disposition: attachment; filename=attendance_report.xls");

echo "Name\tEmail\tCompany\tAttendance%\tCertificate\n";

$students_stmt = $conn->prepare("
SELECT *
FROM cpd_applications
WHERE course_id=?
");
$students_stmt->bind_param("i", $course_id);
$students_stmt->execute();
$students = $students_stmt->get_result();

$present_stmt = $conn->prepare("
SELECT COUNT(*) c
FROM course_attendance
WHERE course_id=?
AND application_id=?
AND status='PRESENT'
");

while($s=$students->fetch_assoc()){

$app_id = (int)$s['id'];

$present_stmt->bind_param("ii", $course_id, $app_id);
$present_stmt->execute();
$present = $present_stmt->get_result()->fetch_assoc()['c'];

$days_total = 3;

$percent = round(($present/$days_total)*100);

$cert = $percent >= 70 ? "Eligible" : "Not Eligible";

echo $s['full_name']."\t".
$s['email']."\t".
$s['company_name']."\t".
$percent."%\t".
$cert."\n";

}