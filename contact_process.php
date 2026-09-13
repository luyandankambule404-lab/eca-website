<?php
require_once 'config.php';
$db = new Database();
$conn = $db->getConnection();

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require 'PHPMailer-master/src/Exception.php';
require 'PHPMailer-master/src/PHPMailer.php';
require 'PHPMailer-master/src/SMTP.php';

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $name    = trim($_POST['name'] ?? '');
    $email   = trim($_POST['email'] ?? '');
    $subject = trim($_POST['subject'] ?? '');
    $message = trim($_POST['message'] ?? '');

    if ($name && $email && $message) {
        $stmt = $conn->prepare("INSERT INTO contact_messages (name, email, subject, message, created_at) 
                                VALUES (:name, :email, :subject, :message, NOW())");
        $stmt->bindParam(':name', $name);
        $stmt->bindParam(':email', $email);
        $stmt->bindParam(':subject', $subject);
        $stmt->bindParam(':message', $message);
        
           
            // Send email via PHPMailer
            $mail = new PHPMailer;
            $mail->isSMTP();
            $mail->Host = 'smtp.technosol.co.sz'; // your SMTP server
            $mail->SMTPAuth = true;
            $mail->Username = 'info@technosol.co.sz'; // SMTP username
            $mail->Password = 'info@2024!';       // SMTP password
            $mail->SMTPSecure = 'tls';             // or 'ssl'
            $mail->Port = 587;                     // or 465 for ssl

            $mail->setFrom($email, $name);
            $mail->addAddress('info@eca.co.sz', 'ECA Info');
            $mail->Subject = "New Contact Form Message: " . ($subject ?: "No Subject");
            $mail->Body    = "You have received a new message from the contact form.\n\n".
                             "Name: $name\nEmail: $email\nSubject: $subject\nMessage:\n$message";


        if ($stmt->execute()) {
            echo "success";
        } else {
            echo "Failed to send message.";
        }
    } else {
        echo "Please fill in all required fields.";
    }
}
?>
