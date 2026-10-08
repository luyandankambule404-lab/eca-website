<?php
require_once __DIR__ . '/includes/session.php';
require_once __DIR__ . '/includes/content.php';
require_once __DIR__ . '/includes/mailer.php';
require_once __DIR__ . '/includes/enquiry-routing.php';
require_once 'config.php';
$db = new Database();
$conn = $db->getConnection();

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    eca_require_public_post();
    $now = time();
    $attempts = array_values(array_filter(
        (array) ($_SESSION['eca_contact_attempts'] ?? []),
        static fn ($timestamp): bool => is_int($timestamp) && $timestamp > $now - 600
    ));
    if (count($attempts) >= 5) {
        http_response_code(429);
        exit('Please wait before sending another message.');
    }
    $attempts[] = $now;
    $_SESSION['eca_contact_attempts'] = $attempts;

    $name    = trim($_POST['name'] ?? '');
    $email   = trim($_POST['email'] ?? '');
    $subject = trim($_POST['subject'] ?? '');
    $message = trim($_POST['message'] ?? '');
    $enquiryType = eca_enquiry_type_from_request();
    $subject = eca_enquiry_apply_subject($subject, $enquiryType);

    if (
        $name !== ''
        && mb_strlen($name) <= 120
        && filter_var($email, FILTER_VALIDATE_EMAIL)
        && mb_strlen($email) <= 190
        && mb_strlen($subject) <= 190
        && $message !== ''
        && mb_strlen($message) <= 5000
    ) {
        $stmt = $conn->prepare("INSERT INTO contact_messages (name, email, subject, message, created_at) 
                                VALUES (:name, :email, :subject, :message, NOW())");
        $stmt->bindParam(':name', $name);
        $stmt->bindParam(':email', $email);
        $stmt->bindParam(':subject', $subject);
        $stmt->bindParam(':message', $message);

        if ($stmt->execute()) {
            $newId = (int) $conn->lastInsertId();
            $ticket = '';
            try {
                $ticket = eca_next_ticket_reference($conn);
                $upd = $conn->prepare('UPDATE contact_messages SET ticket_reference = ?, status = ? WHERE id = ?');
                $upd->execute([$ticket, 'OPEN', $newId]);
                eca_mail_ticket($email, $name, $ticket);
            } catch (Throwable $e) {
                error_log('Contact ticket assign failed: ' . $e->getMessage());
                $ticket = '';
            }
            $smtpHost = eca_env('ECA_SMTP_HOST');
            $smtpUser = eca_env('ECA_SMTP_USER');
            $smtpPass = eca_env('ECA_SMTP_PASS');
            $mailerSrc = __DIR__ . '/PHPMailer-master/src/PHPMailer.php';
            if ($smtpHost !== '' && $smtpUser !== '' && $smtpPass !== '' && is_file($mailerSrc)) {
                require 'PHPMailer-master/src/Exception.php';
                require 'PHPMailer-master/src/PHPMailer.php';
                require 'PHPMailer-master/src/SMTP.php';
                $mail = new PHPMailer(true);
                $mail->isSMTP();
                $mail->Host = $smtpHost;
                $mail->SMTPAuth = true;
                $mail->Username = $smtpUser;
                $mail->Password = $smtpPass;
                $mail->SMTPSecure = 'tls';
                $mail->Port = (int) eca_env('ECA_SMTP_PORT', '587');
                $mail->setFrom($email, $name);
                $mail->addAddress('info@eca.co.sz', 'ECA Info');
                $mail->Subject = 'New Contact Form Message: ' . ($subject ?: 'No Subject');
                $mail->Body = "You have received a new message from the contact form.\n\n".
                    "Name: $name\nEmail: $email\nSubject: $subject\nMessage:\n$message";
                try {
                    $mail->send();
                } catch (Exception $e) {
                    error_log('Contact mail failed: ' . $mail->ErrorInfo);
                }
            }
            if ($ticket !== '') {
                header('Content-Type: application/json; charset=UTF-8');
                echo json_encode([
                    'ok' => true,
                    'ticket_reference' => $ticket,
                    'message' => 'Your message has been sent. Keep this ticket reference: ' . $ticket,
                ], JSON_UNESCAPED_UNICODE);
            } else {
                echo 'success';
            }
        } else {
            echo "Failed to send message.";
        }
    } else {
        echo "Please fill in all required fields.";
    }
}
