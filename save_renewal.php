<?php
session_start();

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require 'PHPMailer/src/Exception.php';
require 'PHPMailer/src/PHPMailer.php';
require 'PHPMailer/src/SMTP.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
  header("Location: renewal.php");
  exit();
}

/* =========================
   DB CONNECT
========================= */
$conn = new mysqli("localhost", "rapfpnhd_portal", "Z@bpP6BjJ,dV[D)Y", "rapfpnhd_portal");
if ($conn->connect_error) die("DB Connection Error: " . $conn->connect_error);
$conn->set_charset("utf8mb4");

/* =========================
   HELPERS
========================= */
function oneLine($s){
  $s = trim((string)$s);
  return preg_replace('/\s+/', ' ', $s);
}
function isPdfUpload($tmpPath){
  if(!is_file($tmpPath)) return false;
  $finfo = new finfo(FILEINFO_MIME_TYPE);
  $mime = $finfo->file($tmpPath);
  return $mime === 'application/pdf';
}

/* =========================
   ENFORCE DECLARATION SIGNED
   (from hidden fields in HTML)
========================= */
$declAccepted    = (int)($_POST['declaration_accepted'] ?? 0);
$declSignature   = oneLine($_POST['declaration_signature'] ?? '');
$declDesignation = oneLine($_POST['declaration_designation'] ?? '');

if($declAccepted !== 1 || $declSignature === '' || $declDesignation === ''){
  header("Location: renewal.php?error=declaration_required");
  exit();
}

// Optional strict match to logged in session name (if available)
$sessionFullName = oneLine($_SESSION['full_name'] ?? '');
if($sessionFullName !== '' && strcasecmp($declSignature, $sessionFullName) !== 0){
  header("Location: renewal.php?error=signature_mismatch");
  exit();
}

/* =========================
   READ FORM INPUTS (MATCH HTML)
========================= */
$registered_name = oneLine($_POST['registered_name'] ?? '');
$trading_name    = oneLine($_POST['trading_name'] ?? '');
$email           = oneLine($_POST['email'] ?? '');
$cellphone       = oneLine($_POST['cellphone'] ?? '');

$specialisation  = oneLine($_POST['specialisation'] ?? '');
$special_other   = oneLine($_POST['specialisation_other'] ?? '');

if($specialisation === 'Other' && $special_other !== ''){
  $specialisation = "Other - " . $special_other;
}

/* Required fields per your HTML */
if($registered_name === '' || $trading_name === '' || $email === '' || $specialisation === ''){
  header("Location: renewal.php?error=missing_fields");
  exit();
}

/* Optional fields (NOT in your renewal HTML, so default empty) */
$telephone       = oneLine($_POST['telephone'] ?? '');
$address         = oneLine($_POST['address'] ?? '');
$region          = oneLine($_POST['region'] ?? '');
$enterprise_type = oneLine($_POST['enterprise_type'] ?? ''); // not in HTML, keep blank

// If your table expects businesstype, reuse enterprise_type (blank if not provided)
$businesstype    = $enterprise_type;

/* Store in existing "declaration" column */
$declaration = "ACCEPTED - Code of Conduct Signed by: {$declSignature} ({$declDesignation}) on " . date("Y-m-d H:i:s");

/* =========================
   FILE UPLOAD SETTINGS (PDF ONLY)
========================= */
$uploadDirAbs = __DIR__ . "/uploads/documents/";
$uploadDirRel = "uploads/documents/";

if(!is_dir($uploadDirAbs)) mkdir($uploadDirAbs, 0755, true);

$maxSize = 2 * 1024 * 1024; // 2MB

function savePdf($file, $type, $client_id, $conn, $uploadDirAbs, $uploadDirRel, $maxSize){
  if(!isset($file) || !is_array($file)) return null;
  if(($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) return null;

  $name = $file['name'] ?? '';
  $tmp  = $file['tmp_name'] ?? '';
  $size = (int)($file['size'] ?? 0);

  $ext = strtolower(pathinfo($name, PATHINFO_EXTENSION));
  if($ext !== 'pdf') return null;
  if($size <= 0 || $size > $maxSize) return null;

  if(!isPdfUpload($tmp)) return null;

  $newName = $type . "_" . $client_id . "_" . bin2hex(random_bytes(8)) . ".pdf";

  $absPath = $uploadDirAbs . $newName;
  $relPath = $uploadDirRel . $newName;

  if(!move_uploaded_file($tmp, $absPath)) return null;

  $stmt = $conn->prepare("
    INSERT INTO tbl_client_documents (client_id, document_type, file_name, file_path)
    VALUES (?,?,?,?)
  ");
  if(!$stmt) return null;

  $stmt->bind_param("isss", $client_id, $type, $newName, $relPath);
  $stmt->execute();
  $stmt->close();

  return $relPath;
}

/* =========================
   SAVE (TRANSACTION)
========================= */
$conn->begin_transaction();

$uploadedPaths = [];
$client_id = 0;

try{
  /* =========================
     INSERT CLIENT (RENEWAL)
     NOTE: this matches your column list + placeholders.
  ========================= */
  $stmt = $conn->prepare("
    INSERT INTO tbl_client
    (
      CompanyRegistrationName,
      TradingName,
      EmailAddress,
      Cellphone,
      telephone,
      address,
      Region,
      Enterprise,
      businesstype,
      Status,
      Clasification,
      declaration,
      DateOfRegistration,
      active
    )
    VALUES
    (
      ?, ?, ?, ?, ?, ?, ?, ?, ?,
      'Renewal',
      ?, ?,
      NOW(),
      'Pending'
    )
  ");

  if(!$stmt){
    throw new Exception("Prepare failed: " . $conn->error);
  }

  // 11 params, all strings
  $stmt->bind_param(
    "sssssssssss",
    $registered_name,
    $trading_name,
    $email,
    $cellphone,
    $telephone,
    $address,
    $region,
    $enterprise_type, // Enterprise (blank if not in HTML)
    $businesstype,    // businesstype (blank if not in HTML)
    $specialisation,  // Clasification
    $declaration      // declaration
  );

  if(!$stmt->execute()){
    throw new Exception("Insert failed: " . $stmt->error);
  }

  $client_id = $stmt->insert_id;
  $stmt->close();

  /* =========================
     UPLOAD REQUIRED PDFs
  ========================= */
  $filesToUpload = [
    'trading_licence' => 'licence',
    'proof_payment'   => 'payment'
  ];

  foreach($filesToUpload as $field => $type){
    if(!isset($_FILES[$field])){
      throw new Exception("Missing file field: " . $field);
    }

    $saved = savePdf($_FILES[$field], $type, $client_id, $conn, $uploadDirAbs, $uploadDirRel, $maxSize);
    if(!$saved){
      throw new Exception("Invalid or failed upload: " . $field);
    }

    $uploadedPaths[$type] = $saved;
  }

  // Commit DB + file rows
  $conn->commit();

}catch(Exception $e){
  $conn->rollback();
  die("Error saving renewal: " . htmlspecialchars($e->getMessage()));
}

/* =========================
   SEND EMAIL (NON-BLOCKING)
========================= */
$mail = new PHPMailer(true);
try{
  $mail->isSMTP();
  $mail->Host = 'mail.eca.co.sz';
  $mail->SMTPAuth = true;
  $mail->Username = 'info@eca.co.sz';
  $mail->Password = ']F&3jh7.A*dF5jO-xN';
  $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
  $mail->Port = 465;

  $mail->setFrom('info@eca.co.sz', 'ECA Membership Renewal');
  $mail->addAddress('support@eca.co.sz');

  $mail->isHTML(true);
  $mail->Subject = 'New Membership Renewal Submitted';

  $body  = "<h3>New Renewal Submitted</h3>";
  $body .= "<b>Company:</b> " . htmlspecialchars($registered_name) . " (" . htmlspecialchars($trading_name) . ")<br>";
  $body .= "<b>Email:</b> " . htmlspecialchars($email) . "<br>";
  $body .= "<b>Cellphone:</b> " . htmlspecialchars($cellphone) . "<br>";
  $body .= "<b>Specialisation:</b> " . htmlspecialchars($specialisation) . "<br><br>";

  $body .= "<h4>Code of Conduct</h4>";
  $body .= "<b>Status:</b> Accepted<br>";
  $body .= "<b>Signed by:</b> " . htmlspecialchars($declSignature) . "<br>";
  $body .= "<b>Designation:</b> " . htmlspecialchars($declDesignation) . "<br><br>";

  $body .= "<h4>Documents</h4><ul>";
  $body .= "<li>Trading Licence: " . htmlspecialchars($uploadedPaths['licence'] ?? 'Uploaded') . "</li>";
  $body .= "<li>Proof of Payment: " . htmlspecialchars($uploadedPaths['payment'] ?? 'Uploaded') . "</li>";
  $body .= "</ul>";

  $mail->Body = $body;
  $mail->send();
}catch(Exception $e){
  // ignore
}

/* =========================
   SUCCESS
========================= */
header("Location: thankyou.php");
exit();
