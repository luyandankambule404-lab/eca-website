<?php
require_once __DIR__ . "/../auth.php";
require_role(['SUPPERADMIN', 'ADMIN']);
cpd_require_csrf();

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require __DIR__ . '/../PHPMailer/src/Exception.php';
require __DIR__ . '/../PHPMailer/src/PHPMailer.php';
require __DIR__ . '/../PHPMailer/src/SMTP.php';

$mail = new PHPMailer(true);



if(!isset($_POST['id']) && !isset($_POST['client_id'])){
    header("Location: application.php?msg=error");
    exit;
}

$id = (int) ($_POST['id'] ?? $_POST['client_id']);
if ($id <= 0) {
    header("Location: application.php?msg=error");
    exit;
}



/* =========================================
   1️⃣ FETCH CLIENT INFO
========================================= */
$stmt = $conn->prepare("
    SELECT email, company_name,full_name, phone
    FROM cpd_applications
    WHERE id=?
");
$stmt->bind_param("i",$id);
$stmt->execute();
$stmt->bind_result($email, $trading_name, $full_name,$cellphone);
$stmt->fetch();
$stmt->close();


/* =========================================
   3️⃣ GENERATE 5 DIGIT PASSWORD
========================================= */
$password_plain = bin2hex(random_bytes(10));
$password_hash  = password_hash($password_plain, PASSWORD_DEFAULT);



/* =========================================
   5️⃣ INSERT INTO membership_years
========================================= */


/* =========================================
   6️⃣ CREATE LOGIN ACCOUNT (users)
========================================= */
$stmt = $conn->prepare("
INSERT INTO user
(role, company_name, full_name, email, phone, password_hash, status, created_at)
VALUES ('CONTRACTOR', ?, ?, ?, ?, ?, 'ACTIVE', NOW())
");
$stmt->bind_param("sssss",
    $trading_name,
    $full_name,
    $email,
    $cellphone,
    $password_hash
);
$stmt->execute();
$stmt->close();


/* =========================================
   7️⃣ SEND APPROVAL EMAIL
========================================= */
     if (eca_configure_smtp($mail)) {

        $mail->setFrom('info@eca.co.sz', 'ECA Membership Registration');
      
        $mail->addAddress($email);
        $mail->addReplyTo('info@eca.co.sz', 'ECA Office');

       $mail->isHTML(true);
$mail->Subject = 'ECA Training Approved – Login Details';

$mail->Body = '
<div style="font-family:Segoe UI,Arial,sans-serif;font-size:14px;background:#f5f6fa;padding:20px;">
  <div style="max-width:650px;margin:auto;background:#fff;border-radius:8px;overflow:hidden">

    <div style="background:#c8102e;color:#fff;padding:20px;text-align:center">
        <h2>Eswatini Contractors Association (ECA)</h2>
        <p>Membership Approval Notice</p>
    </div>

    <div style="padding:25px;color:#333">

        <p>Dear <strong>'.htmlspecialchars($trading_name, ENT_QUOTES, 'UTF-8').'</strong>,</p>

        <p>Your <strong>Your Training Application has been APPROVED.</strong></p>

        <hr>

        <h3 style="color:#c8102e">Your Login Details</h3>

    
        <p><strong>Temporary password:</strong> '.htmlspecialchars($password_plain, ENT_QUOTES, 'UTF-8').'</p>

        <p style="margin-top:10px;color:#888">
            Please change your password after first login.
        </p>

        <p><strong>Login Link:</strong> https://eca.co.sz/cpd/login.php</p>

        <br>

        <p>Regards,<br><strong>ECA Membership Office</strong></p>
    </div>
  </div>
</div>
';

        try { $mail->send(); } catch (Exception $e) { error_log('CPD approval mail skipped: ' . $mail->ErrorInfo); }
     }


/* =========================================
   8️⃣ REDIRECT
========================================= */
header("Location: applications.php?msg=approved");
exit;
?>
