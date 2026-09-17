<?php
session_start();
require_once "config.php";

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require 'PHPMailer/src/Exception.php';
require 'PHPMailer/src/PHPMailer.php';
require 'PHPMailer/src/SMTP.php';

$mail = new PHPMailer(true);

// --- HANDLE FORM SUBMISSION ---
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Sanitize inputs
    $company_name = $conn->real_escape_string($_POST['company_name']);
    $attendee_name = $conn->real_escape_string($_POST['attendee_name']);
    $id_number = $conn->real_escape_string($_POST['id_number']);
    $position = $conn->real_escape_string($_POST['position']);
    $cell_number = $conn->real_escape_string($_POST['cell_number']);
    $training_history = $conn->real_escape_string($_POST['attended_training']);
    $specialisation = $conn->real_escape_string($_POST['specialisation']);
    $Qualification = $conn->real_escape_string($_POST['Qualification']);
     $email = $conn->real_escape_string($_POST['email']);
      $gender = $conn->real_escape_string($_POST['gender']);
    $tendering_fundamentals = isset($_POST['tendering_fundamentals']) ? $_POST['tendering_fundamentals'] : 'N/A';
    $estimating_costing = isset($_POST['estimating_costing']) ? $_POST['estimating_costing'] : 'N/A';

  
    
    $agreement = isset($_POST['agreement']) ? 1 : 0;

    // --- HANDLE FILE UPLOAD ---
    $upload_dir = "../portal/uploads/";
    if (!is_dir($upload_dir)) mkdir($upload_dir, 0777, true);

    $file_name = basename($_FILES["payment_proof"]["name"]);
    $target_file = $upload_dir . time() . "_" . preg_replace("/[^a-zA-Z0-9._-]/", "_", $file_name);
    $file_type = strtolower(pathinfo($target_file, PATHINFO_EXTENSION));
    $allowed = array("jpg","jpeg","png","pdf");

    if (!in_array($file_type, $allowed)) {
        die("Invalid file type. Only PDF, JPG, JPEG, or PNG allowed.");
    }

    if (move_uploaded_file($_FILES["payment_proof"]["tmp_name"], $target_file)) {
        // Save to database
        $stmt = $conn->prepare("INSERT INTO training (company_name, attendee_name, id_number, position, cell_number, attended_training, payment_proof, agreement,specialisation,Qualification,email,gender,tendering_fundamentals,estimating_costing)
                                VALUES (?, ?, ?, ?, ?, ?, ?, ?,?,?,?,?,?,?)");
        $stmt->bind_param("sssssssissssss", $company_name, $attendee_name, $id_number, $position, $cell_number, $training_history, $target_file, $agreement,$specialisation,$Qualification, $email,$gender,$tendering_fundamentals, $estimating_costing);
        $stmt->execute();

    
    if (eca_configure_smtp($mail)) {
    $mail->setFrom('info@eca.co.sz', 'Training Registration');
    $mail->addAddress('info@eca.co.sz');
    $mail->addReplyTo('no-reply@eca.co.sz', 'Training Registration');

    $mail->isHTML(true);
    $mail->Subject = 'Priliminaries & Generals Registration';
$mail->Body    = '
    <h3>New Registration Received</h3>
    <p><strong>Company:</strong> '.$company_name.'</p>
    <p><strong>Attendee:</strong> '.$attendee_name.'</p>
    <p><strong>ID Number:</strong> '.$id_number.'</p>
    <p><strong>Position:</strong> '.$position.'</p>
    <p><strong>Cell:</strong> '.$cell_number.'</p>
    <p><strong>Training History:</strong> '.$training_history.'</p>
    <p>Proof of Payment: uploaded locally</p>
    <hr>
    <p>Submitted via Online Registration Form</p>
';
    try { $mail->send(); } catch (Exception $e) { error_log('Training registration mail skipped: ' . $mail->ErrorInfo); }
    }
   



        echo "<script>
            
                window.location.href='thankyou.php';
              </script>";
    } else {
        echo "File upload failed. Please try again.";
    }
}
$conn->close();
?>
