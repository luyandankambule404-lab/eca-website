<?php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require 'PHPMailer/src/Exception.php';
require 'PHPMailer/src/PHPMailer.php';
require 'PHPMailer/src/SMTP.php';

$mail = new PHPMailer(true);

try {
    // SERVER SETTINGS
    $mail->isSMTP();
    $mail->Host       = 'mail.eca.co.sz';      // ✅ your mail server
    $mail->SMTPAuth   = true;
    $mail->Username   = 'info@eca.co.sz';      // ✅ your email
    $mail->Password   = ']F&3jh7.A*dF5jO-xN'; // ✅ replace with your password
    $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS; // or PHPMailer::ENCRYPTION_STARTTLS
    $mail->Port       = 465; // 465 for SSL, 587 for TLS

    // EMAIL CONTENT
    $mail->setFrom('info@eca.co.sz', 'ECA Registration');
    $mail->addAddress('info@eca.co.sz'); // You can add multiple recipients
    $mail->addReplyTo('no-reply@eca.co.sz', 'ECA Registration');

    $mail->isHTML(true);
    $mail->Subject = 'New Estimating & Pricing Course Registration';
    $mail->Body    = '
        <h3>New Registration Received</h3>
        <p>You have received a new registration for the Estimating & Pricing Course.</p>
        <p>Please log in to your <strong>Admin Dashboard</strong> to view full details.</p>
    ';

    $mail->send();
    echo '✅ Message has been sent successfully';
} catch (Exception $e) {
    echo "❌ Message could not be sent. Mailer Error: {$mail->ErrorInfo}";
}
?>
