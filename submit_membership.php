<?php
session_start();

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require 'PHPMailer/src/Exception.php';
require 'PHPMailer/src/PHPMailer.php';
require 'PHPMailer/src/SMTP.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
  http_response_code(405);
  exit("Method Not Allowed");
}

/* =========================
   DB CONNECT
========================= */
$conn = new mysqli("localhost", "rapfpnhd_portal", "Z@bpP6BjJ,dV[D)Y", "rapfpnhd_portal");
if ($conn->connect_error) die("DB Connection Error: " . $conn->connect_error);
$conn->set_charset("utf8mb4");

/* =========================
   POST FIELDS (MATCH HTML)
========================= */
$registered_name = trim($_POST['registered_name'] ?? '');
$trading_name    = trim($_POST['trading_name'] ?? '');
$email           = trim($_POST['email'] ?? '');
$cellphone       = trim($_POST['cellphone'] ?? '');
$telephone       = trim($_POST['telephone'] ?? '');
$address         = trim($_POST['address'] ?? '');
$region          = trim($_POST['region'] ?? '');
$enterprise_type      = trim($_POST['enterprise_type'] ?? '');

$specialisation       = trim($_POST['specialisation'] ?? '');
$specialisation_other = trim($_POST['specialisation_other'] ?? '');

// Final classification saved to DB
$classification = ($specialisation === 'Other' && $specialisation_other !== '')
  ? ("Other: " . $specialisation_other)
  : $specialisation;

// Declaration/signing (from your modal hidden fields)
$declarationAccepted = (int)($_POST['declaration_accepted'] ?? 0);
$declarationSig      = trim($_POST['declaration_signature'] ?? '');
$declarationDesig    = trim($_POST['declaration_designation'] ?? '');

// what your DB column "declaration" will store (you can adjust format)
$declaration = "Accepted: {$declarationAccepted} | Name: {$declarationSig} | Designation: {$declarationDesig}";

/* =========================
   BASIC VALIDATION
========================= */
if ($registered_name === '' || $trading_name === '' || $email === '' || $region === '') {
  die("Missing required fields.");
}
if ($declarationAccepted !== 1 || $declarationSig === '' || $declarationDesig === '') {
  die("Declaration not completed. Please sign Code of Conduct before submitting.");
}

/* =========================
   UPLOAD CONFIG (PDF ONLY)
========================= */
$uploadDirAbs = __DIR__ . "/uploads/documents/";
$uploadDirRel = "uploads/documents/";

if (!is_dir($uploadDirAbs)) mkdir($uploadDirAbs, 0775, true);

$maxSize = 2 * 1024 * 1024; // 2MB
$allowedExt  = ['pdf'];
$allowedMime = ['application/pdf'];

/* =========================
   SAFE FILE UPLOAD
========================= */

function getEnterpriseFromOwners(array $genders, array $shares): string
{
  $femaleTotal = 0;
  $femaleMax   = 0;

  foreach ($genders as $i => $g) {
    $gender = strtoupper(trim($g ?? ''));
    $sh     = (float)($shares[$i] ?? 0);

    // treat "F" or "FEMALE" as female
    $isFemale = ($gender === 'F' || $gender === 'FEMALE');

    if ($isFemale) {
      $femaleTotal += $sh;
      if ($sh > $femaleMax) $femaleMax = $sh;
    }
  }

  // If any female > 50 OR total female shares > 50 => FEMALE enterprise
  return ($femaleMax > 50 || $femaleTotal > 50) ? "FEMALE" : "MALE";
}
// ===== Enterprise override based on owners =====
if (!empty($_POST['gender']) && is_array($_POST['gender']) && !empty($_POST['shares']) && is_array($_POST['shares'])) {
  $enterprise = getEnterpriseFromOwners($_POST['gender'], $_POST['shares']);
} else {
  // default if owners not provided
  $enterprise = "MALE";
}


function saveFile($file, $type, $client_id, $conn, $uploadDirAbs, $uploadDirRel, $maxSize, $allowedExt, $allowedMime)
{
  if (!isset($file) || !is_array($file)) return null;
  if (($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) return null;

  if (($file['size'] ?? 0) <= 0 || $file['size'] > $maxSize) return null;

  $ext = strtolower(pathinfo($file['name'] ?? '', PATHINFO_EXTENSION));
  if (!in_array($ext, $allowedExt, true)) return null;

  // MIME check (trust server-side, not browser)
  $finfo = new finfo(FILEINFO_MIME_TYPE);
  $mime  = $finfo->file($file['tmp_name']);
  if (!in_array($mime, $allowedMime, true)) return null;

  // file name
  $rand = bin2hex(random_bytes(8));
  $newName = $type . "_" . $client_id . "_" . $rand . "." . $ext;

  $absPath = $uploadDirAbs . $newName;
  $relPath = $uploadDirRel . $newName;

  if (!move_uploaded_file($file['tmp_name'], $absPath)) return null;

  $stmt = $conn->prepare("
      INSERT INTO tbl_client_documents
      (client_id, document_type, file_name, file_path)
      VALUES (?,?,?,?)
  ");
  if (!$stmt) return null;

  $stmt->bind_param("isss", $client_id, $type, $newName, $relPath);
  $stmt->execute();
  $stmt->close();

  return $relPath;
}

/* =========================
   TRANSACTION
========================= */
$conn->begin_transaction();

$uploadedPaths = [];
$client_id = 0;

try {
  /* ========================
     INSERT CLIENT
  ======================== */
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
        businesstype,
        Enterprise,
        Status,
        Clasification,
        declaration,
        DateOfRegistration,
        active
      )
      VALUES
      (
        ?,?,?,?,?,?,?,?,?,
        'Joining',
        ?,?,
        NOW(),
        'Pending'
      )
  ");
  if (!$stmt) throw new Exception("Prepare failed: " . $conn->error);

  // 10 strings
  $stmt->bind_param(
    "sssssssssss",
    $registered_name,
    $trading_name,
    $email,
    $cellphone,
    $telephone,
    $address,
    $region,
    $enterprise_type,
    $enterprise,
    $classification,
    $declaration
  );

  if (!$stmt->execute()) throw new Exception("Insert failed: " . $stmt->error);

  $client_id = $stmt->insert_id;
  $stmt->close();

  /* ========================
     INSERT OWNERS
  ======================== */
  if (!empty($_POST['owner_name']) && is_array($_POST['owner_name'])) {
    $stmtOwner = $conn->prepare("
        INSERT INTO owners
        (clientid, name, citizen, gender, shares, application_id)
        VALUES (?,?,?,?,?,?)
    ");
    if (!$stmtOwner) throw new Exception("Prepare failed for owners: " . $conn->error);

    foreach ($_POST['owner_name'] as $i => $owner) {
      $owner = trim($owner ?? '');
      if ($owner === '') continue;

      $citizen = trim($_POST['citizen'][$i] ?? '');
      $gender  = trim($_POST['gender'][$i] ?? '');
      $shares  = (int)($_POST['shares'][$i] ?? 0);

      $application_id = (int)$client_id;

      $stmtOwner->bind_param("isssii", $client_id, $owner, $citizen, $gender, $shares, $application_id);
      if (!$stmtOwner->execute()) throw new Exception("Owner insert failed: " . $stmtOwner->error);
    }
    $stmtOwner->close();
  }

  /* ========================
     UPLOAD FILES
  ======================== */
  $filesToUpload = [
    'certificate_incorporation' => 'certificate',
    'trading_licence'           => 'licence',
    'form_c'                    => 'form_c',
    'form_j'                    => 'form_j',
    'proof_payment'             => 'payment'
  ];

  foreach ($filesToUpload as $field => $type) {
    if (!empty($_FILES[$field])) {
      $path = saveFile($_FILES[$field], $type, $client_id, $conn, $uploadDirAbs, $uploadDirRel, $maxSize, $allowedExt, $allowedMime);
      if (!$path && in_array($field, ['certificate_incorporation','trading_licence','proof_payment'], true)) {
        throw new Exception("Required file missing or invalid: " . $field);
      }
      if ($path) $uploadedPaths[$type] = $path;
    }
  }
  // Multiple ID copies
  if (!empty($_FILES['id_copies']['name'][0])) {
    foreach ($_FILES['id_copies']['tmp_name'] as $k => $tmp) {
      $file = [
        'name'     => $_FILES['id_copies']['name'][$k] ?? '',
        'tmp_name' => $tmp,
        'error'    => $_FILES['id_copies']['error'][$k] ?? UPLOAD_ERR_NO_FILE,
        'size'     => $_FILES['id_copies']['size'][$k] ?? 0,
      ];
      saveFile($file, 'id_copy', $client_id, $conn, $uploadDirAbs, $uploadDirRel, $maxSize, $allowedExt, $allowedMime);
    }
  }
  $conn->commit();

} catch (Exception $e) {
  $conn->rollback();
  die("Error saving application: " . htmlspecialchars($e->getMessage()));
}

/* ========================
   SEND EMAIL (DO NOT BLOCK)
========================= */
$mail = new PHPMailer(true);
try {
  $mail->isSMTP();
  $mail->Host       = 'mail.eca.co.sz';
  $mail->SMTPAuth   = true;
  $mail->Username   = 'info@eca.co.sz';
  $mail->Password   = ']F&3jh7.A*dF5jO-xN';
  $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
  $mail->Port       = 465;

  $mail->setFrom('info@eca.co.sz', 'ECA Membership Registration');
  $mail->addAddress('support@eca.co.sz');

  $mail->isHTML(true);
  $mail->Subject = 'New Membership Application';

  $body  = "<h3>New Application Submitted</h3>";
  $body .= "<b>Company:</b> " . htmlspecialchars($registered_name) . " (" . htmlspecialchars($trading_name) . ")<br>";
  $body .= "<b>Email:</b> " . htmlspecialchars($email) . "<br>";
  $body .= "<b>Phone:</b> " . htmlspecialchars($cellphone) . "<br>";
  $body .= "<b>Region:</b> " . htmlspecialchars($region) . "<br>";
  $body .= "<b>Enterprise:</b> " . htmlspecialchars($enterprise) . "<br>";
  $body .= "<b>Specialisation:</b> " . htmlspecialchars($classification) . "<br>";
  $body .= "<b>Declaration Signed By:</b> " . htmlspecialchars($declarationSig) . " (" . htmlspecialchars($declarationDesig) . ")<br>";

  if (!empty($_POST['owner_name']) && is_array($_POST['owner_name'])) {
    $body .= "<h4>Owners:</h4><ul>";
    foreach ($_POST['owner_name'] as $i => $owner) {
      $owner = trim($owner ?? '');
      if ($owner === '') continue;

      $citizen = htmlspecialchars($_POST['citizen'][$i] ?? '');
      $gender  = htmlspecialchars($_POST['gender'][$i] ?? '');
      $shares  = htmlspecialchars($_POST['shares'][$i] ?? '');
      $body .= "<li>" . htmlspecialchars($owner) . " - Citizen: {$citizen}, Gender: {$gender}, Shares: {$shares}%</li>";
    }
    $body .= "</ul>";
  }

  if (!empty($uploadedPaths)) {
    $body .= "<h4>Documents Uploaded:</h4><ul>";
    foreach ($uploadedPaths as $type => $path) {
      $body .= "<li>" . htmlspecialchars($type) . ": " . htmlspecialchars($path) . "</li>";
    }
    $body .= "</ul>";
  }

  $mail->Body = $body;
  $mail->send();
} catch (Exception $e) {
  // ignore
}

header("Location: success.php");
exit();
