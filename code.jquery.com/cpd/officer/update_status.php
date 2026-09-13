<?php
session_start();
require_once "../auth.php";
require_role('ADMIN');
require_once "../config.php";

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require '../PHPMailer/src/Exception.php';
require '../PHPMailer/src/PHPMailer.php';
require '../PHPMailer/src/SMTP.php';



if(!isset($_GET['id']) || !isset($_GET['status'])){
    die("Invalid request");
}

$id = (int)$_GET['id'];
$status = $_GET['status'];


/* =========================================
1️⃣ FETCH APPLICATION + COURSE INFO
========================================= */

$stmt = $conn->prepare("
SELECT 
cpd_applications.*,
courses.title,
courses.description,
courses.start_date,
courses.end_date
FROM cpd_applications
LEFT JOIN courses ON courses.id = cpd_applications.course_id
WHERE cpd_applications.id=?
");

$stmt->bind_param("i",$id);
$stmt->execute();

$result = $stmt->get_result();
$app = $result->fetch_assoc();

$email       = $app['email'];
$company     = $app['company_name'];
$full_name   = $app['full_name'];
$phone       = $app['phone'];
$course      = $app['title'];
$desc        = $app['description'];
$start       = date("d M Y H:i",strtotime($app['start_date']));
$end         = date("d M Y H:i",strtotime($app['end_date']));

$stmt->close();

/* =========================================
2️⃣ GENERATE PASSWORD
========================================= */

$password_plain = rand(10000,99999);
$password_hash  = password_hash($password_plain, PASSWORD_DEFAULT);

/* =========================================
3️⃣ CREATE LOGIN ACCOUNT
========================================= */

$stmt = $conn->prepare("
INSERT INTO user
(company_name, full_name, email, phone, password_hash, status, role, created_at)
VALUES (?,?,?,?,?,'ACTIVE','CONTRACTOR',NOW())
");

$stmt->bind_param("sssss",
$company,
$full_name,
$email,
$phone,
$password_hash
);

$stmt->execute();
$stmt->close();

/* =========================================
4️⃣ UPDATE APPLICATION STATUS
========================================= */

$stmt = $conn->prepare("UPDATE cpd_applications SET status='Approved' WHERE id=?");
$stmt->bind_param("i",$id);
$stmt->execute();
$stmt->close();

/* =========================================
5️⃣ SEND EMAIL
========================================= */

$mail = new PHPMailer(true);

try{

$mail->isSMTP();
$mail->Host       = 'mail.eca.co.sz';
$mail->SMTPAuth   = true;
$mail->Username   = 'info@eca.co.sz';
$mail->Password   = ']F&3jh7.A*dF5jO-xN';
$mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
$mail->Port       = 465;

$mail->setFrom('info@eca.co.sz','ECA CPD Training');
$mail->addAddress($email);
$mail->addBCC('brightwell.kunene@gmail.com');

$mail->isHTML(true);
$mail->Subject = "ECA Training Application Approved";

/* =========================================
EMAIL BODY
========================================= */

$mail->Body = '

<div style="font-family:Segoe UI,Arial;background:#f4f6f9;padding:20px">

<div style="max-width:650px;margin:auto;background:white;border-radius:8px;overflow:hidden">

<div style="background:#06254a;color:white;padding:20px;text-align:center">
<h2>Eswatini Contractors Association</h2>
<p>CPD Training Approval</p>
</div>

<div style="padding:25px;color:#333">

<p>Dear <strong>'.$full_name.'</strong>,</p>

<p>Your CPD training application has been <strong style="color:green">APPROVED</strong>.</p>

<hr>

<h3 style="color:#06254a">Training Details</h3>

<p><strong>Course:</strong> '.$course.'</p>

<p><strong>Description:</strong><br>'.$desc.'</p>

<p><strong>Start Date:</strong> '.$start.'</p>
<p><strong>End Date:</strong> '.$end.'</p>

<hr>

<h3 style="color:#06254a">Your Login Details</h3>

<p><strong>Email:</strong> '.$email.'</p>
<p><strong>Password:</strong> '.$password_plain.'</p>

<p>
Login Here:<br>
<a href="https://eca.co.sz/cpd/index.php">
https://eca.co.sz/cpd/index.php
</a>
</p>

<p style="color:#777">
Please change your password after first login.
</p>

<br>

<p>
Regards,<br>
<strong>ECA Training Office</strong>
</p>

</div>

</div>

</div>

';

$mail->send();

}catch(Exception $e){
    echo "Mailer Error: ".$mail->ErrorInfo;
}

/* =========================================
6️⃣ REDIRECT
========================================= */

header("Location: applications.php?msg=approved");
exit;
?>