<?php
require_once __DIR__ . '/includes/session.php';
require_once __DIR__ . '/includes/documents.php';
require_once __DIR__ . '/includes/membership.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require 'PHPMailer/src/Exception.php';
require 'PHPMailer/src/PHPMailer.php';
require 'PHPMailer/src/SMTP.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
  header("Location: renewal.php");
  exit();
}
eca_require_public_post();

$now = time();
$renewalAttempts = array_values(array_filter(
  (array) ($_SESSION['eca_renewal_attempts'] ?? []),
  static fn ($timestamp): bool => is_int($timestamp) && $timestamp > $now - 3600
));
if (count($renewalAttempts) >= 3) {
  http_response_code(429);
  exit('Too many renewal attempts. Please wait before trying again.');
}
$renewalAttempts[] = $now;
$_SESSION['eca_renewal_attempts'] = $renewalAttempts;

/* =========================
   DB CONNECT
========================= */
require_once __DIR__ . '/includes/portal-db.php';
$conn = eca_portal_mysqli();

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
$membership_number = oneLine($_POST['membership_number'] ?? '');

$specialisation  = oneLine($_POST['specialisation'] ?? '');
$special_other   = oneLine($_POST['specialisation_other'] ?? '');
$resolvedSpec    = eca_resolve_specialisation($specialisation, $special_other);
$specialisation  = $resolvedSpec['classification'];

if (
  $registered_name === ''
  || $trading_name === ''
  || $email === ''
  || $specialisation === ''
  || $resolvedSpec['error'] !== ''
  || $membership_number === ''
  || strlen($membership_number) > 40
  || !preg_match('/^[A-Za-z0-9\/_-]+$/', $membership_number)
) {
  header("Location: renewal.php?error=missing_fields");
  exit();
}

$allowedOwnerGenders = ['Male', 'Female'];
$allowedOwnerCitizen = ['Swazi', 'Non-Swazi'];
$ownerNames = $_POST['owner_name'] ?? null;
if (!is_array($ownerNames)) {
  header("Location: renewal.php?error=missing_owners");
  exit();
}
$completeOwners = [];
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
    header("Location: renewal.php?error=missing_owners");
    exit();
  }
  $completeOwners[] = [
    'name' => $owner,
    'citizen' => $citizen,
    'gender' => $gender,
    'shares' => rtrim(rtrim(number_format($sharesNum, 2, '.', ''), '0'), '.'),
  ];
}
if (count($completeOwners) < 1) {
  header("Location: renewal.php?error=missing_owners");
  exit();
}

function getEnterpriseFromOwners(array $genders, array $shares): string
{
  $femaleTotal = 0;
  $femaleMax   = 0;

  foreach ($genders as $i => $g) {
    $gender = strtoupper(trim($g ?? ''));
    $sh     = (float)($shares[$i] ?? 0);
    $isFemale = ($gender === 'F' || $gender === 'FEMALE');
    if ($isFemale) {
      $femaleTotal += $sh;
      if ($sh > $femaleMax) $femaleMax = $sh;
    }
  }

  return ($femaleMax > 50 || $femaleTotal > 50) ? "FEMALE" : "MALE";
}

$ownerGenders = array_column($completeOwners, 'gender');
$ownerShares = array_column($completeOwners, 'shares');
$enterprise = getEnterpriseFromOwners($ownerGenders, $ownerShares);

/* Optional fields (NOT in your renewal HTML, so default empty) */
$telephone       = oneLine($_POST['telephone'] ?? '');
$address         = oneLine($_POST['address'] ?? '');
$region          = oneLine($_POST['region'] ?? '');
$enterprise_type = $enterprise;
$businesstype    = $enterprise_type;

/* Store in existing "declaration" column */
$declaration = "ACCEPTED - Code of Conduct Signed by: {$declSignature} ({$declDesignation}) on " . date("Y-m-d H:i:s");

/* =========================
   FILE UPLOAD SETTINGS (PDF ONLY)
========================= */
$maxSize = 2 * 1024 * 1024; // 2MB

function savePdf($file, $type, $client_id, $conn, $uploadDirAbs, $uploadDirRel, $maxSize){
  $stored = eca_store_private_upload($file, (int) $client_id, $type, $conn, $maxSize);
  return $stored ? $stored['storage_key'] : null;
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
      active,
      MembershipNumber
    )
    VALUES
    (
      ?, ?, ?, ?, ?, ?, ?, ?, ?,
      'Renewal',
      ?, ?,
      NOW(),
      'Pending',
      ?
    )
  ");

  if(!$stmt){
    throw new Exception("Prepare failed: " . $conn->error);
  }

  $stmt->bind_param(
    "ssssssssssss",
    $registered_name,
    $trading_name,
    $email,
    $cellphone,
    $telephone,
    $address,
    $region,
    $enterprise_type,
    $businesstype,
    $specialisation,
    $declaration,
    $membership_number
  );

  if(!$stmt->execute()){
    throw new Exception("Insert failed: " . $stmt->error);
  }

  $client_id = $stmt->insert_id;
  $stmt->close();

  $stmtOwner = $conn->prepare("
      INSERT INTO owners
      (clientid, name, citizen, gender, shares, application_id)
      VALUES (?,?,?,?,?,?)
  ");
  if(!$stmtOwner){
    throw new Exception("Prepare failed for owners: " . $conn->error);
  }
  foreach ($completeOwners as $ownerRow) {
    $ownerName = $ownerRow['name'];
    $ownerCitizen = $ownerRow['citizen'];
    $ownerGender = $ownerRow['gender'];
    $ownerShares = $ownerRow['shares'];
    $application_id = (int) $client_id;
    $stmtOwner->bind_param(
      "issssi",
      $client_id,
      $ownerName,
      $ownerCitizen,
      $ownerGender,
      $ownerShares,
      $application_id
    );
    if(!$stmtOwner->execute()){
      throw new Exception("Owner insert failed: " . $stmtOwner->error);
    }
  }
  $stmtOwner->close();

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
  foreach ($uploadedPaths as $storageKey) {
    if (!is_string($storageKey) || !preg_match('/\A[a-f0-9]{32}\z/', $storageKey)) {
      continue;
    }
    foreach (glob(eca_private_documents_dir() . DIRECTORY_SEPARATOR . '*_' . $storageKey . '.*') ?: [] as $orphan) {
      @unlink($orphan);
    }
  }
  error_log('Membership renewal failed: ' . $e->getMessage());
  http_response_code(500);
  exit('We could not save the renewal. Please review your details and try again.');
}

/* =========================
   SEND EMAIL (NON-BLOCKING)
========================= */
if (eca_smtp_ready()) {
$mail = new PHPMailer(true);
try{
  $mail->isSMTP();
  $mail->Host = eca_env('ECA_SMTP_HOST');
  $mail->SMTPAuth = true;
  $mail->Username = eca_env('ECA_SMTP_USER');
  $mail->Password = eca_env('ECA_SMTP_PASS');
  $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
  $mail->Port = (int) eca_env('ECA_SMTP_PORT', '465');

  $mail->setFrom('info@eca.co.sz', 'ECA Membership Renewal');
  $mail->addAddress('support@eca.co.sz');

  $mail->isHTML(true);
  $mail->Subject = 'New Membership Renewal Submitted';

  $body  = "<h3>New Renewal Submitted</h3>";
  $body .= "<b>Membership number:</b> " . htmlspecialchars($membership_number) . "<br>";
  $body .= "<b>Company:</b> " . htmlspecialchars($registered_name) . " (" . htmlspecialchars($trading_name) . ")<br>";
  $body .= "<b>Email:</b> " . htmlspecialchars($email) . "<br>";
  $body .= "<b>Cellphone:</b> " . htmlspecialchars($cellphone) . "<br>";
  $body .= "<b>Specialisation:</b> " . htmlspecialchars($specialisation) . "<br><br>";

  $body .= "<h4>Owners / shareholders</h4><ul>";
  foreach ($completeOwners as $ownerRow) {
    $body .= "<li>" . htmlspecialchars($ownerRow['name'])
      . " - " . htmlspecialchars($ownerRow['citizen'])
      . ", " . htmlspecialchars($ownerRow['gender'])
      . ", " . htmlspecialchars($ownerRow['shares']) . "%</li>";
  }
  $body .= "</ul>";

  $body .= "<h4>Code of Conduct</h4>";
  $body .= "<b>Status:</b> Accepted<br>";
  $body .= "<b>Signed by:</b> " . htmlspecialchars($declSignature) . "<br>";
  $body .= "<b>Designation:</b> " . htmlspecialchars($declDesignation) . "<br><br>";

  $body .= "<h4>Documents</h4><ul>";
  $body .= "<li>Trading Licence uploaded</li>";
  $body .= "<li>Proof of Payment uploaded</li>";
  $body .= "</ul>";

  $mail->Body = $body;
  $mail->send();
}catch(Exception $e){
  // ignore
}
}

/* =========================
   SUCCESS
========================= */
header("Location: thankyou.php");
exit();
