<?php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require_once dirname(__DIR__) . '/includes/portal-db.php';
require 'PHPMailer/src/Exception.php';
require 'PHPMailer/src/PHPMailer.php';
require 'PHPMailer/src/SMTP.php';

if (!eca_smtp_ready()) {
    http_response_code(503);
    exit('Email is not configured on this local copy.');
}

$mail = new PHPMailer(true);

try {
    if (!eca_configure_smtp($mail)) {
        exit('Email is not configured on this local copy.');
    }

    $mail->setFrom('info@eca.co.sz', 'ECA Registration');
    $mail->addAddress('info@eca.co.sz');
    $mail->addReplyTo('no-reply@eca.co.sz', 'ECA Registration');

    $mail->isHTML(true);
    $mail->Subject = 'New Estimating & Pricing Course Registration';
    $mail->Body    = '
        <h3>New Registration Received</h3>
        <p>You have received a new registration for the Estimating & Pricing Course.</p>
        <p>Please log in to your <strong>Admin Dashboard</strong> to view full details.</p>
    ';

    $mail->send();
    echo 'Message has been sent successfully';
} catch (Exception $e) {
    error_log('Local registration sendemail failed: ' . $mail->ErrorInfo);
    echo 'Message could not be sent.';
}
