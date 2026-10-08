<?php
require_once __DIR__ . '/includes/session.php';
require_once __DIR__ . '/includes/documents.php';
require_once __DIR__ . '/includes/membership.php';
require_once __DIR__ . '/includes/mailer.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require 'PHPMailer/src/Exception.php';
require 'PHPMailer/src/PHPMailer.php';
require 'PHPMailer/src/SMTP.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
  http_response_code(405);
  exit("Method Not Allowed");
}
eca_require_public_post();

$now = time();
$applicationAttempts = array_values(array_filter(
  (array) ($_SESSION['eca_application_attempts'] ?? []),
  static fn ($timestamp): bool => is_int($timestamp) && $timestamp > $now - 3600
));
if (count($applicationAttempts) >= 3) {
  http_response_code(429);
  exit('Too many application attempts. Please wait before trying again.');
}
$applicationAttempts[] = $now;
$_SESSION['eca_application_attempts'] = $applicationAttempts;

/* =========================
   DB CONNECT
========================= */
require_once __DIR__ . '/includes/portal-db.php';
$conn = eca_portal_mysqli();

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
$resolvedSpec = eca_resolve_specialisation($specialisation, $specialisation_other);
$classification = $resolvedSpec['classification'];

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
if ($resolvedSpec['error'] !== '') {
  die($resolvedSpec['error']);
}
if ($declarationAccepted !== 1 || $declarationSig === '' || $declarationDesig === '') {
  die("Declaration not completed. Please sign Code of Conduct before submitting.");
}

$allowedOwnerGenders = ['Male', 'Female'];
$allowedOwnerCitizen = ['Swazi', 'Non-Swazi'];
$ownerNames = $_POST['owner_name'] ?? null;
if (!is_array($ownerNames)) {
  die('Please add at least one owner with full name, gender, share %, and Swazi or Non-Swazi.');
}
$completeOwners = 0;
foreach ($ownerNames as $i => $owner) {
  $owner = trim((string) $owner);
  $citizen = trim((string) ($_POST['citizen'][$i] ?? ''));
  $gender = trim((string) ($_POST['gender'][$i] ?? ''));
  $sharesRaw = trim((string) ($_POST['shares'][$i] ?? ''));
  if ($owner === '' && $citizen === '' && $gender === '' && $sharesRaw === '') {
    continue;
  }
  $sharesNum = is_numeric($sharesRaw) ? (float) $sharesRaw : -1;
  if (
    $owner === ''
    || !in_array($gender, $allowedOwnerGenders, true)
    || !in_array($citizen, $allowedOwnerCitizen, true)
    || $sharesNum < 0
    || $sharesNum > 100
  ) {
    die('Each owner must have full name, gender (Male or Female), share %, and Swazi or Non-Swazi.');
  }
  $completeOwners++;
}
if ($completeOwners < 1) {
  die('Please add at least one owner with full name, gender, share %, and Swazi or Non-Swazi.');
}

$maxSize = 2 * 1024 * 1024; // 2MB
$allowedExt  = ['pdf', 'jpg', 'jpeg', 'png'];
$allowedMime = ['application/pdf', 'image/jpeg', 'image/png'];

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
  $stored = eca_store_private_upload($file, (int) $client_id, $type, $conn, $maxSize);
  return $stored ? $stored['storage_key'] : null;
}

/* =========================
   TRANSACTION
========================= */
$conn->begin_transaction();

$uploadedPaths = [];
$client_id = 0;
$application_reference = '';

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

  $application_reference = eca_next_application_reference($conn);
  $application_status = 'SUBMITTED';
  $refStmt = $conn->prepare('UPDATE tbl_client SET application_reference = ?, application_status = ? WHERE client_id = ?');
  if (!$refStmt) throw new Exception("Could not assign application reference.");
  $refStmt->bind_param('ssi', $application_reference, $application_status, $client_id);
  if (!$refStmt->execute()) throw new Exception("Could not save application reference.");
  $refStmt->close();

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
      if (!$path && in_array($field, ['certificate_incorporation','trading_licence','form_j','proof_payment'], true)) {
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
      $path = saveFile($file, 'id_copy', $client_id, $conn, $uploadDirAbs, $uploadDirRel, $maxSize, $allowedExt, $allowedMime);
      if ($path) {
        $uploadedPaths['id_copy_' . $k] = $path;
      }
    }
  }
  $conn->commit();

} catch (Exception $e) {
  $conn->rollback();
  foreach ($uploadedPaths as $storageKey) {
    if (!is_string($storageKey) || !preg_match('/\A[a-f0-9]{32}\z/', $storageKey)) {
      continue;
    }
    foreach (glob(eca_private_documents_dir() . DIRECTORY_SEPARATOR . '*_' . $storageKey . '.*') ?: [] as $orphan) {
      @unlink($orphan);
    }
  }
  error_log('Membership application failed: ' . $e->getMessage());
  http_response_code(500);
  exit("We could not save the application. Please review your details and try again.");
}

/* ========================
   SEND EMAIL (DO NOT BLOCK)
========================= */
if (eca_smtp_ready()) {
$mail = new PHPMailer(true);
try {
  $mail->isSMTP();
  $mail->Host       = eca_env('ECA_SMTP_HOST');
  $mail->SMTPAuth   = true;
  $mail->Username   = eca_env('ECA_SMTP_USER');
  $mail->Password   = eca_env('ECA_SMTP_PASS');
  $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
  $mail->Port       = (int) eca_env('ECA_SMTP_PORT', '465');

  $mail->setFrom('info@eca.co.sz', 'ECA Membership Registration');
  $mail->addAddress('support@eca.co.sz');

  $mail->isHTML(true);
  $mail->Subject = 'New Membership Application';

  $body  = "<h3>New Application Submitted</h3>";
  if ($application_reference !== '') {
    $body .= "<b>Application Reference:</b> " . htmlspecialchars($application_reference, ENT_QUOTES, 'UTF-8') . "<br>";
  }
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
      $body .= "<li>" . htmlspecialchars($type) . " uploaded</li>";
    }
    $body .= "</ul>";
  }

  $mail->Body = $body;
  $mail->send();
} catch (Exception $e) {
  // ignore
}
}

if ($application_reference !== '' && filter_var($email, FILTER_VALIDATE_EMAIL)) {
    eca_mail_application_received(
        $email,
        $trading_name !== '' ? $trading_name : $registered_name,
        $application_reference
    );
}

header("Location: success.php?ref=" . rawurlencode($application_reference));
exit();
