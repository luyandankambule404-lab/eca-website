<?php
session_start();
require_once "config.php";

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require 'PHPMailer/src/Exception.php';
require 'PHPMailer/src/PHPMailer.php';
require 'PHPMailer/src/SMTP.php';

/* =========================
   GET LATEST OPEN COURSE
========================= */
$courseSql = "SELECT * FROM courses WHERE status='OPEN' ORDER BY id DESC LIMIT 1";
$courseRes = $conn->query($courseSql);
$course = $courseRes ? $courseRes->fetch_assoc() : null;

if (!$course) {
    http_response_code(200);
    echo '<!DOCTYPE html><html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">';
    echo '<title>Registration Closed | ECA Training</title>';
    echo '<style>body{margin:0;font-family:Arial,sans-serif;background:#f5f7fb;color:#192754;}main{max-width:640px;margin:12vh auto;padding:32px;background:#fff;border-radius:16px;box-shadow:0 16px 40px rgba(25,39,84,.12);}h1{margin:0 0 12px;}p{line-height:1.6;}a{color:#d50d0e;font-weight:700;}</style>';
    echo '</head><body><main><p style="color:#d50d0e;font-weight:800;letter-spacing:.08em;text-transform:uppercase;font-size:12px;">ECA CPD</p><h1>Registration closed</h1><p>There is no open CPD course taking applications at the moment.</p><p><a href="/">Back to ECA home</a> · <a href="/cpd/login.php">CPD login</a></p></main></body></html>';
    exit;
}

$msg = "";
$err = "";
$course_id = (int)$course['id'];
$company_details = null;

/* =========================
   COURSE DISPLAY HELPERS
========================= */
$courseStart = !empty($course['start_date']) ? strtotime($course['start_date']) : time();
$courseEnd   = !empty($course['end_date']) ? strtotime($course['end_date']) : $courseStart;

$courseDates = date("d M Y", $courseStart);
if (date("Y-m-d", $courseStart) !== date("Y-m-d", $courseEnd)) {
    $courseDates .= " - " . date("d M Y", $courseEnd);
}

$courseTime = date("h:i A", $courseStart) . " - " . date("H:i", $courseEnd) . " Daily";
$commitment_fee = $course['commitment_fee'] ?? $course['fee'] ?? '350.00';
$coursePoints = $course['points'] ?? '4.00';

/* =========================
   ENSURE UPLOADS FOLDER
========================= */
$upload_dir = __DIR__ . "/uploads/";

if (!is_dir($upload_dir)) {
    @mkdir($upload_dir, 0777, true);
}

/* =========================
   HELPERS
========================= */
function clean_input($value): string {
    return trim((string)$value);
}

if (!function_exists('e')) {
    function e($value): string {
        return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
    }
}

function old($key, $default = ''): string {
    return htmlspecialchars($_POST[$key] ?? $default, ENT_QUOTES, 'UTF-8');
}

function is_selected($key, $value): string {
    return (($_POST[$key] ?? '') === $value) ? 'selected' : '';
}

function is_checked($key): string {
    return isset($_POST[$key]) ? 'checked' : '';
}

function is_checked_value($key, $value): string {
    return (($_POST[$key] ?? '') === $value) ? 'checked' : '';
}

function handle_upload(
    string $field,
    string $upload_dir,
    array $allowed_ext = ['pdf', 'doc', 'docx', 'jpg', 'jpeg', 'png'],
    int $max_bytes = 2097152
): array {
    if (empty($_FILES[$field]['name']) || !is_uploaded_file($_FILES[$field]['tmp_name'])) {
        return ['ok' => true, 'path' => ''];
    }

    if ($_FILES[$field]['error'] !== UPLOAD_ERR_OK) {
        return ['ok' => false, 'error' => "Upload failed for {$field}."];
    }

    if ($_FILES[$field]['size'] > $max_bytes) {
        return ['ok' => false, 'error' => "The {$field} file exceeds the 2 MB size limit."];
    }

    $original = $_FILES[$field]['name'];
    $ext = strtolower(pathinfo($original, PATHINFO_EXTENSION));

    if (!in_array($ext, $allowed_ext, true)) {
        return [
            'ok' => false,
            'error' => "Invalid file type for {$field}. Allowed: " . implode(', ', $allowed_ext)
        ];
    }

    $safeName = time() . "_" . $field . "_" . preg_replace('/[^A-Za-z0-9_\.-]/', '_', $original);
    $targetPath = $upload_dir . $safeName;

    if (!move_uploaded_file($_FILES[$field]['tmp_name'], $targetPath)) {
        return ['ok' => false, 'error' => "Failed to save uploaded file for {$field}."];
    }

    return ['ok' => true, 'path' => "uploads/" . $safeName];
}

function get_company_by_membership(mysqli $conn, string $membership_number): ?array {
    $stmt = $conn->prepare("
        SELECT 
            client_id,
            TradingName,
            CompanyRegistrationName,
            MembershipNumber,
            Clasification,
            Region,
            EmailAddress,
            telephone,
            address,
            Status,
            active
        FROM tbl_client
        WHERE MembershipNumber = ?
        LIMIT 1
    ");

    if (!$stmt) {
        return null;
    }

    $stmt->bind_param("s", $membership_number);
    $stmt->execute();
    $res = $stmt->get_result();
    $company = $res->fetch_assoc();
    $stmt->close();

    return $company ?: null;
}

/* =========================
   FORM SUBMISSION
========================= */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit'])) {

    $course_id           = isset($_POST['course_id']) ? (int)$_POST['course_id'] : 0;
    $membership          = clean_input($_POST['membership_number'] ?? '');

    $name                = clean_input($_POST['full_name'] ?? '');
    $email               = clean_input($_POST['email'] ?? '');
    $phone               = clean_input($_POST['phone'] ?? '');
    $idnum               = clean_input($_POST['id_number'] ?? '');
    $gender              = clean_input($_POST['gender'] ?? '');
    $position            = clean_input($_POST['position'] ?? '');

    $learning            = clean_input($_POST['learning_objectives'] ?? '');
    $qualification_level = clean_input($_POST['qualification_level'] ?? '');
    $qualification_name  = clean_input($_POST['qualification_name'] ?? $qualification_level);
    $payment_method      = clean_input($_POST['payment_method'] ?? '');
    $signature           = clean_input($_POST['signature_name'] ?? '');
    $sig_date            = clean_input($_POST['signature_date'] ?? '');
    $declaration         = isset($_POST['declaration_confirm']) ? 1 : 0;

    if ($membership === '') {
        $err = "Please enter a valid ECA membership number.";
    } else {
        $company_details = get_company_by_membership($conn, $membership);

        if (!$company_details) {
            $err = "No company found for this membership number. Please check the membership number and try again.";
        }
    }

    if ($err === '') {
        $company = clean_input($company_details['TradingName'] ?? '');
        $discipline = clean_input($company_details['Clasification'] ?? '');

        if (
            $course_id <= 0 ||
            $company === '' ||
            $membership === '' ||
            $discipline === '' ||
            $name === '' ||
            $idnum === '' ||
            $position === '' ||
            $qualification_level === '' ||
            $learning === '' ||
            $payment_method === '' ||
            $signature === '' ||
            $sig_date === '' ||
            !$declaration
        ) {
            $err = "Please complete all required fields and confirm the acknowledgement.";
        }
    }

    if ($err === '') {
        $qualification_upload = handle_upload('qualification', $upload_dir);

        if (!$qualification_upload['ok']) {
            $err = $qualification_upload['error'];
        }
    }

    if ($err === '') {
        $payment_upload = handle_upload('payment_proof', $upload_dir);

        if (!$payment_upload['ok']) {
            $err = $payment_upload['error'];
        }
    }

    if ($err === '') {
        $qualification_file = $qualification_upload['path'];
        $payment = $payment_upload['path'];

        $stmt = $conn->prepare("
            INSERT INTO cpd_applications
            (
                course_id,
                company_name,
                membership_number,
                discipline,
                full_name,
                email,
                phone,
                id_number,
                gender,
                position,
                learning_objectives,
                qualification_level,
                qualification_name,
                qualification,
                payment_proof,
                payment_method
            )
            VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)
        ");

        if (!$stmt) {
            $err = "Failed to prepare application query: " . $conn->error;
        } else {
            $stmt->bind_param(
                "isssssssssssssss",
                $course_id,
                $company,
                $membership,
                $discipline,
                $name,
                $email,
                $phone,
                $idnum,
                $gender,
                $position,
                $learning,
                $qualification_level,
                $qualification_name,
                $qualification_file,
                $payment,
                $payment_method
            );

            if ($stmt->execute()) {
                $msg = "Application submitted successfully.";
                $application_id = $stmt->insert_id;

                try {
                    $mail = new PHPMailer(true);

                    if (!eca_configure_smtp($mail)) {
                        throw new Exception('SMTP is not configured locally.');
                    }

                    $mail->setFrom('info@eca.co.sz', 'ECA CPD Training');
                    $mail->addAddress('trainings@eca.co.sz');
                    $mail->addCC('brightwell.kunene@gmail.com');

                    if (!empty($email) && filter_var($email, FILTER_VALIDATE_EMAIL)) {
                        $mail->addReplyTo($email, $name);
                    }

                    $mail->isHTML(true);
                    $mail->Subject = "New CPD Training Application Submitted";

                    $mail->Body = "
                        <div style='font-family:Arial,sans-serif;font-size:14px;color:#222;line-height:1.6'>
                            <h2 style='color:#061f52;margin-bottom:10px;'>New CPD Training Application</h2>
                            <hr>

                            <h3 style='color:#061f52;'>Course Details</h3>
                            <p><strong>Course:</strong> " . e($course['title']) . "</p>
                            <p><strong>Dates:</strong> " . e($courseDates) . "</p>
                            <p><strong>Time:</strong> " . e($courseTime) . "</p>
                            <p><strong>Venue:</strong> " . e($course['venue']) . "</p>
                            <p><strong>CPD Points:</strong> " . e((string)$coursePoints) . "</p>
                            <p><strong>Commitment Fee:</strong> E" . e((string)$commitment_fee) . "</p>

                            <hr>

                            <h3 style='color:#061f52;'>Member / Company Information</h3>
                            <p><strong>Company Name:</strong> " . e($company) . "</p>
                            <p><strong>Membership Number:</strong> " . e($membership) . "</p>
                            <p><strong>CIC Registration Grade / Discipline:</strong> " . e($discipline) . "</p>
                            <p><strong>Region:</strong> " . e($company_details['Region'] ?? '') . "</p>
                            <p><strong>Company Status:</strong> " . e($company_details['Status'] ?? '') . "</p>

                            <hr>

                            <h3 style='color:#061f52;'>Delegate Details</h3>
                            <p><strong>Name:</strong> " . e($name) . "</p>
                            <p><strong>Email:</strong> " . e($email) . "</p>
                            <p><strong>Phone:</strong> " . e($phone) . "</p>
                            <p><strong>ID Number:</strong> " . e($idnum) . "</p>
                            <p><strong>Gender:</strong> " . e($gender) . "</p>
                            <p><strong>Position / Role:</strong> " . e($position) . "</p>

                            <hr>

                            <h3 style='color:#061f52;'>Educational Background</h3>
                            <p><strong>Highest Qualification:</strong> " . e($qualification_level) . "</p>
                            <p><strong>Qualification / Field:</strong> " . e($qualification_name) . "</p>
                            <p><strong>Learning Objectives:</strong><br>" . nl2br(e($learning)) . "</p>

                            <hr>

                            <h3 style='color:#061f52;'>Payment Confirmation</h3>
                            <p><strong>Payment Method:</strong> " . e($payment_method) . "</p>
                            " . (($payment_method === 'EFT') ? "
                            <div style='background:#f8fbff;border:1px solid #d6dde5;border-left:5px solid #061f52;padding:12px 14px;margin:10px 0 12px;'>
                                <p style='margin:0 0 8px;color:#061f52;font-weight:bold;'>EFT Banking Details</p>
                                <p style='margin:3px 0;'><strong>Name:</strong> Eswatini Contractors Association</p>
                                <p style='margin:3px 0;'><strong>Bank:</strong> Standard Bank</p>
                                <p style='margin:3px 0;'><strong>Branch:</strong> Mbabane</p>
                                <p style='margin:3px 0;'><strong>Account Number:</strong> 9110003519522</p>
                                <p style='margin:3px 0;'><strong>Branch Code:</strong> 663164</p>
                            </div>
                            " : "") . "
                            <p><strong>Proof of Payment:</strong> " . e($payment ?: 'Not attached') . "</p>

                            <hr>

                            <h3 style='color:#061f52;'>Declaration</h3>
                            <p><strong>Digital Signature:</strong> " . e($signature) . "</p>
                            <p><strong>Date:</strong> " . e($sig_date) . "</p>

                            <hr>
                            <p>This application was submitted through the <strong>ECA CPD Portal</strong>.</p>
                        </div>
                    ";

                    $mail->send();
                } catch (Exception $e) {
                    error_log("Mail Error: " . $mail->ErrorInfo);
                }

                $_POST = [];
                $company_details = null;
            } else {
                $err = "Error: " . $stmt->error;
            }

            $stmt->close();
        }
    }
}

$readonly_company_name = $company_details['TradingName'] ?? ($_POST['company_name'] ?? '');
$readonly_registration = $company_details['CompanyRegistrationName'] ?? ($_POST['company_registration_name'] ?? '');
$readonly_discipline   = $company_details['Clasification'] ?? ($_POST['discipline'] ?? '');
$readonly_region       = $company_details['Region'] ?? ($_POST['region'] ?? '');
$readonly_email        = $company_details['EmailAddress'] ?? ($_POST['company_email'] ?? '');
$readonly_phone        = $company_details['telephone'] ?? ($_POST['company_phone'] ?? '');
$readonly_status       = $company_details['Status'] ?? ($_POST['company_status'] ?? '');
$readonly_active       = $company_details['active'] ?? ($_POST['company_active'] ?? '');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Training Application Form | ECA CPD System</title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">

    <style>
        :root{
            --navy:#061f52;
            --navy-2:#082b6c;
            --red:#c40000;
            --red-2:#e31b23;
            --soft:#f2f5f8;
            --line:#cfd8dc;
            --dark:#1f2937;
            --muted:#667085;
            --white:#ffffff;
            --shadow:0 20px 55px rgba(6,31,82,.12);
        }

        *{box-sizing:border-box;}

        body{
            margin:0;
            font-family:'Poppins',sans-serif;
            background:
                radial-gradient(circle at top left, rgba(6,31,82,.12), transparent 28%),
                radial-gradient(circle at bottom right, rgba(196,0,0,.08), transparent 30%),
                #eef2f7;
            color:var(--dark);
        }

        .eca-navbar{
            background:linear-gradient(135deg,var(--navy),var(--navy-2));
            border-bottom:4px solid var(--red);
            box-shadow:0 10px 30px rgba(0,0,0,.18);
        }

        .logo-box{
            background:#fff;
            padding:7px 12px;
            border-radius:12px;
            box-shadow:0 8px 20px rgba(0,0,0,.18);
        }

        .brand-logo{
            width:110px;
            height:42px;
            object-fit:contain;
            display:block;
        }

        .brand-sub{
            font-size:12px;
            color:#fff;
            font-weight:700;
            letter-spacing:.02em;
            margin-bottom:0;
        }

        .user-profile{
            display:flex;
            align-items:center;
            justify-content:center;
            width:42px;
            height:42px;
            border-radius:50%;
            background:rgba(255,255,255,.12);
            border:1px solid rgba(255,255,255,.25);
            color:#fff;
            text-decoration:none;
        }

        .page-wrap{padding:34px 0 55px;}

        .pdf-shell{
            max-width:980px;
            margin:0 auto;
        }

        .paper-card{
            background:#fff;
            border-radius:18px;
            box-shadow:var(--shadow);
            border:1px solid rgba(6,31,82,.08);
            padding:34px;
            position:relative;
            overflow:hidden;
        }

        .paper-card:before{
            content:"";
            position:absolute;
            top:0;
            left:0;
            right:0;
            height:8px;
            background:linear-gradient(90deg,var(--navy) 0 72%,var(--red) 72% 100%);
        }

        .form-head{
            display:grid;
            grid-template-columns:1fr auto;
            gap:22px;
            align-items:start;
            padding-bottom:16px;
            border-bottom:4px solid var(--navy);
            margin-bottom:22px;
        }

        .form-head h1{
            color:var(--navy);
            font-size:1.45rem;
            line-height:1.2;
            margin:0 0 8px;
            font-weight:900;
            text-transform:uppercase;
            letter-spacing:.02em;
        }

        .form-head p{
            color:var(--red);
            font-size:.84rem;
            font-weight:800;
            margin:0;
        }

        .points-badge{
            color:var(--navy);
            font-size:.92rem;
            font-weight:900;
            white-space:nowrap;
            padding-top:8px;
        }

        .pdf-title-bar{
            background:var(--navy);
            color:#fff;
            border-radius:6px;
            text-align:center;
            padding:13px 18px;
            font-size:1.16rem;
            font-weight:900;
            letter-spacing:.02em;
            text-transform:uppercase;
            margin-bottom:22px;
        }

        .course-summary{
            border:1px solid #d6dde5;
            background:#f8fafc;
            padding:18px 20px;
            margin-bottom:22px;
        }

        .course-summary .summary-label{
            color:var(--navy);
            font-weight:900;
            font-size:.84rem;
        }

        .course-summary .summary-value{
            color:#4b5563;
            font-weight:600;
            font-size:.84rem;
        }

        .alert{
            border-radius:10px;
            font-weight:700;
            border:0;
        }

        .alert-success{background:#eafaf0;color:#166534;}
        .alert-danger{background:#fff1f2;color:#991b1b;}

        .pdf-section{margin-bottom:18px;}

        .pdf-section-title{
            display:flex;
            align-items:center;
            gap:10px;
            background:#e9eef3;
            color:var(--navy);
            font-weight:900;
            font-size:.92rem;
            text-transform:uppercase;
            padding:8px 13px;
            margin-bottom:14px;
            border-left:7px solid var(--red);
        }

        .lookup-row{
            background:#f7fafc;
            border:1px dashed #b7c2d0;
            padding:14px;
            border-radius:10px;
            margin-bottom:15px;
        }

        .lookup-status{
            font-size:.82rem;
            font-weight:800;
            margin-top:8px;
        }

        .lookup-status.success{color:#15803d;}
        .lookup-status.error{color:#b91c1c;}

        .pdf-label{
            color:#344054;
            font-size:.78rem;
            font-weight:800;
            margin-bottom:6px;
        }

        .form-control,
        .form-select{
            border:0;
            border-bottom:1.8px solid #aeb8c2;
            border-radius:0;
            min-height:40px;
            padding:7px 0 8px;
            font-size:.9rem;
            color:#111827;
            box-shadow:none!important;
            background:#fff;
        }

        .form-control:focus,
        .form-select:focus{
            border-bottom-color:var(--navy);
        }

        .form-control[readonly]{
            background:#fff;
            color:#111827;
            font-weight:700;
        }

        textarea.form-control{
            min-height:85px;
            border:1.6px solid #aeb8c2;
            padding:12px;
            resize:vertical;
        }

        .btn-lookup{
            background:var(--navy);
            color:#fff;
            border:none;
            border-radius:8px;
            min-height:44px;
            font-weight:800;
            width:100%;
        }

        .btn-lookup:hover{
            background:var(--red);
            color:#fff;
        }

        .pdf-checks{
            display:flex;
            flex-wrap:wrap;
            gap:12px 18px;
            align-items:center;
            margin-top:6px;
        }

        .pdf-check{
            display:inline-flex;
            align-items:center;
            gap:7px;
            font-size:.82rem;
            color:#374151;
            font-weight:600;
            cursor:pointer;
        }

        .pdf-check input{
            appearance:none;
            -webkit-appearance:none;
            width:16px;
            height:16px;
            border:1.8px solid #778391;
            background:#fff;
            border-radius:2px;
            display:inline-grid;
            place-content:center;
        }

        .pdf-check input:checked{
            border-color:var(--navy);
            background:var(--navy);
            box-shadow:inset 0 0 0 3px #fff;
        }

        .payment-note{
            font-size:.79rem;
            color:#374151;
            font-style:italic;
            margin-left:6px;
        }

        .banking-details-panel{
            display:none;
            margin-top:14px;
            border:1px solid rgba(6,31,82,.18);
            border-left:7px solid var(--navy);
            border-radius:12px;
            background:linear-gradient(135deg,#f8fbff,#ffffff);
            box-shadow:0 10px 24px rgba(6,31,82,.08);
            overflow:hidden;
        }

        .banking-details-panel.show{
            display:block;
        }

        .banking-details-head{
            background:linear-gradient(135deg,var(--navy),var(--navy-2));
            color:#fff;
            padding:10px 14px;
            font-weight:900;
            font-size:.86rem;
            letter-spacing:.02em;
            text-transform:uppercase;
        }

        .banking-details-body{
            padding:13px 14px 14px;
        }

        .bank-row{
            display:grid;
            grid-template-columns:135px 1fr;
            gap:10px;
            border-bottom:1px solid #e5e7eb;
            padding:7px 0;
            font-size:.86rem;
        }

        .bank-row:last-child{
            border-bottom:0;
        }

        .bank-row span:first-child{
            color:#667085;
            font-weight:800;
        }

        .bank-row span:last-child{
            color:#111827;
            font-weight:900;
        }

        .banking-note{
            margin-top:10px;
            color:#7f1d1d;
            font-size:.78rem;
            font-weight:700;
            background:#fff1f1;
            border:1px solid #f4b5b5;
            padding:8px 10px;
            border-radius:8px;
        }

        .file-panel{
            border:1px dashed #b9c4cf;
            background:#fbfcfe;
            border-radius:10px;
            padding:14px;
            height:100%;
        }

        .declaration{
            border:1px solid #f4b5b5;
            background:#fff1f1;
            color:#7f1d1d;
            padding:14px 16px;
            font-size:.82rem;
            line-height:1.6;
            margin:18px 0;
        }

        .signature-area{
            margin-top:28px;
        }

        .signature-line{
            border-top:2px solid #374151;
            padding-top:8px;
            text-align:center;
            color:#4b5563;
            font-size:.82rem;
            font-weight:600;
        }

        .agree-box{
            border:1px solid #d9e0e8;
            border-radius:10px;
            background:#f8fafc;
            padding:14px;
            margin-top:10px;
        }

        .submit-bar{
            display:flex;
            justify-content:center;
            gap:12px;
            margin-top:28px;
            padding-top:18px;
            border-top:1px solid #e5e7eb;
        }

        .btn-submit-app{
            background:linear-gradient(135deg,var(--red),var(--red-2));
            color:#fff;
            border:none;
            border-radius:10px;
            padding:13px 34px;
            font-weight:900;
            letter-spacing:.02em;
            box-shadow:0 14px 28px rgba(196,0,0,.22);
        }

        .btn-submit-app:hover{
            color:#fff;
            transform:translateY(-1px);
        }

        .btn-momo{
            background:#fbbc04;
            color:#111827;
            border:none;
            border-radius:9px;
            font-weight:900;
            padding:10px 18px;
        }

        .footer-line{
            margin-top:22px;
            color:#475569;
            font-size:.8rem;
            text-align:center;
            border-top:1px solid #e5e7eb;
            padding-top:14px;
        }

        @media print{
            body{background:#fff;}
            .eca-navbar,.submit-bar,.btn-lookup,.btn-momo{display:none!important;}
            .page-wrap{padding:0;}
            .paper-card{box-shadow:none;border:none;border-radius:0;padding:20px;}
        }

        @media(max-width:768px){
            .paper-card{padding:22px 16px;}
            .form-head{grid-template-columns:1fr;}
            .points-badge{padding-top:0;}
            .pdf-title-bar{font-size:.98rem;}
            .pdf-checks{gap:10px 12px;}
            .submit-bar{flex-direction:column;}
            .btn-submit-app{width:100%;}
        }
    </style>
</head>

<body>

<nav class="navbar navbar-expand-lg navbar-dark eca-navbar sticky-top">
    <div class="container-fluid px-3 px-lg-4">
        <a class="navbar-brand d-flex align-items-center gap-3 text-decoration-none" href="/cpd/index.php">
            <div class="logo-box">
                <img src="https://eca.co.sz/cpd/images/logo.jpg" class="brand-logo" alt="ECA Logo">
            </div>
            <div class="brand-text d-none d-sm-block">
                <div class="brand-sub">Eswatini Contractors Association</div>
            </div>
        </a>

        <a class="user-profile" href="#">
            <i class="fa-solid fa-user"></i>
        </a>
    </div>
</nav>

<div class="page-wrap">
    <div class="container pdf-shell">
        <div class="paper-card">

            <div class="form-head">
                <div>
                    <h1>Eswatini Contractors Association</h1>
                    <p>In partnership with the Construction Industry Council (CIC)</p>
                </div>
                <div class="points-badge">CPD Points: <?= e(number_format((float)$coursePoints, 2)) ?></div>
            </div>

            <div class="pdf-title-bar">
                <?= e($course['title']) ?> - Application Form
            </div>

            <div class="course-summary">
                <div class="row gy-2">
                    <div class="col-md-6">
                        <span class="summary-label">Dates:</span>
                        <span class="summary-value"><?= e($courseDates) ?></span>
                    </div>
                    <div class="col-md-6">
                        <span class="summary-label">Time:</span>
                        <span class="summary-value"><?= e($courseTime) ?></span>
                    </div>
                    <div class="col-md-6">
                        <span class="summary-label">Venue:</span>
                        <span class="summary-value"><?= e($course['venue']) ?></span>
                    </div>
                    <div class="col-md-6">
                        <span class="summary-label">Commitment Fee:</span>
                        <span class="summary-value">E<?= e(number_format((float)$commitment_fee, 2)) ?> (Non-refundable)</span>
                    </div>
                </div>
            </div>

            <?php if ($msg): ?>
                <div class="alert alert-success mb-4">
                    <i class="fa-solid fa-circle-check me-2"></i><?= e($msg) ?>
                </div>
            <?php endif; ?>

            <?php if ($err): ?>
                <div class="alert alert-danger mb-4">
                    <i class="fa-solid fa-triangle-exclamation me-2"></i><?= e($err) ?>
                </div>
            <?php endif; ?>

            <form method="POST" enctype="multipart/form-data">
                <input type="hidden" name="course_id" value="<?= (int)$course['id'] ?>">

                <div class="pdf-section">
                    <div class="pdf-section-title">1. Member / Company Information</div>

                    <div class="lookup-row">
                        <div class="row g-3 align-items-end">
                            <div class="col-md-8">
                                <label class="pdf-label">ECA Membership Number (e.g ECA1000)</label>
                                <input
                                    type="text"
                                    name="membership_number"
                                    id="membership_number"
                                    class="form-control"
                                    value="<?= old('membership_number') ?>"
                                    placeholder="Enter ECA membership number"
                                    required
                                >
                            </div>
                            <div class="col-md-4">
                                <button type="button" class="btn btn-lookup" id="lookupCompanyBtn">
                                    <i class="fa-solid fa-magnifying-glass me-2"></i>Find Company
                                </button>
                            </div>
                        </div>
                        <div id="companyLookupStatus" class="lookup-status"></div>
                    </div>

                    <div class="row g-4">
                        <div class="col-md-6">
                            <label class="pdf-label">Company Trading Name</label>
                            <input type="text" name="company_name" id="company_name" class="form-control" value="<?= e($readonly_company_name) ?>" readonly required>
                        </div>

                        <div class="col-md-6">
                            <label class="pdf-label">CIC Registration Grade / Discipline</label>
                            <input type="text" name="discipline" id="discipline" class="form-control" value="<?= e($readonly_discipline) ?>" readonly required>
                        </div>

                        <div class="col-md-6">
                            <label class="pdf-label">Company Registration Name</label>
                            <input type="text" name="company_registration_name" id="company_registration_name" class="form-control" value="<?= e($readonly_registration) ?>" readonly>
                        </div>

                        <div class="col-md-6">
                            <label class="pdf-label">Company Email / Phone</label>
                            <input type="text" name="company_email_phone" id="company_email_phone" class="form-control" value="<?= e(trim($readonly_email . ' / ' . $readonly_phone, ' /')) ?>" readonly>
                            <input type="hidden" name="company_email" id="company_email" value="<?= e($readonly_email) ?>">
                            <input type="hidden" name="company_phone" id="company_phone" value="<?= e($readonly_phone) ?>">
                        </div>

                        <div class="col-md-4">
                            <label class="pdf-label">Region</label>
                            <input type="text" name="region" id="region" class="form-control" value="<?= e($readonly_region) ?>" readonly>
                        </div>

                        <div class="col-md-4">
                            <label class="pdf-label">Company Status</label>
                            <input type="text" name="company_status" id="company_status" class="form-control" value="<?= e($readonly_status) ?>" readonly>
                        </div>

                        <div class="col-md-4">
                            <label class="pdf-label">Active Status</label>
                            <input type="text" name="company_active" id="company_active" class="form-control" value="<?= e($readonly_active) ?>" readonly>
                        </div>
                    </div>
                </div>

                <div class="pdf-section">
                    <div class="pdf-section-title">2. Delegate Details (Individual Attending)</div>

                    <div class="row g-4">
                        <div class="col-md-6">
                            <label class="pdf-label">Full Name (as per ID)</label>
                            <input type="text" name="full_name" class="form-control" value="<?= old('full_name') ?>" required>
                        </div>

                        <div class="col-md-6">
                            <label class="pdf-label">ID Number</label>
                            <input type="text" name="id_number" class="form-control" value="<?= old('id_number') ?>" required>
                        </div>

                        <div class="col-md-6">
                            <label class="pdf-label">Email Address</label>
                            <input type="email" name="email" class="form-control" value="<?= old('email') ?>">
                        </div>

                        <div class="col-md-3">
                            <label class="pdf-label">Phone Number</label>
                            <input type="text" name="phone" class="form-control" value="<?= old('phone') ?>">
                        </div>

                        <div class="col-md-3">
                            <label class="pdf-label">Gender</label>
                            <select name="gender" class="form-select">
                                <option value="">Select</option>
                                <option value="M" <?= is_selected('gender', 'M') ?>>Male</option>
                                <option value="F" <?= is_selected('gender', 'F') ?>>Female</option>
                            </select>
                        </div>

                        <div class="col-12">
                            <label class="pdf-label">Position / Role</label>
                            <div class="pdf-checks">
                                <label class="pdf-check"><input type="radio" name="position" value="Director" <?= is_checked_value('position','Director') ?> required>Director</label>
                                <label class="pdf-check"><input type="radio" name="position" value="Site Agent" <?= is_checked_value('position','Site Agent') ?>>Site Agent</label>
                                <label class="pdf-check"><input type="radio" name="position" value="Quantity Surveyor" <?= is_checked_value('position','Quantity Surveyor') ?>>Quantity Surveyor</label>
                                <label class="pdf-check"><input type="radio" name="position" value="Admin / Other" <?= is_checked_value('position','Admin / Other') ?>>Admin / Other</label>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="pdf-section">
                    <div class="pdf-section-title">3. Educational Background</div>

                    <div class="row g-4">
                        <div class="col-12">
                            <label class="pdf-label">Highest Qualification</label>
                            <div class="pdf-checks">
                                <label class="pdf-check"><input type="radio" name="qualification_level" value="Diploma" <?= is_checked_value('qualification_level','Diploma') ?> required>Diploma</label>
                                <label class="pdf-check"><input type="radio" name="qualification_level" value="BCom/BSc" <?= is_checked_value('qualification_level','BCom/BSc') ?>>BCom/BSc</label>
                                <label class="pdf-check"><input type="radio" name="qualification_level" value="Honours / PGDPM" <?= is_checked_value('qualification_level','Honours / PGDPM') ?>>Honours / PGDPM</label>
                                <label class="pdf-check"><input type="radio" name="qualification_level" value="Master's / PhD" <?= is_checked_value('qualification_level',"Master's / PhD") ?>>Master's / PhD</label>
                            </div>
                        </div>

                        <div class="col-md-6">
                            <label class="pdf-label">Qualification / Field of Study</label>
                            <input type="text" name="qualification_name" class="form-control" value="<?= old('qualification_name') ?>" placeholder="Optional">
                        </div>

                        <div class="col-md-6">
                            <div class="file-panel">
                                <label class="pdf-label">Attach Minimum Qualification</label>
                                <input type="file" name="qualification" class="form-control" accept=".pdf,.doc,.docx,.jpg,.jpeg,.png">
                                <small class="text-muted">Allowed: PDF, DOC, DOCX, JPG, PNG. Maximum 2 MB.</small>
                            </div>
                        </div>

                        <div class="col-12">
                            <label class="pdf-label">Learning Objectives (Briefly state what you hope to achieve)</label>
                            <textarea name="learning_objectives" class="form-control" required><?= old('learning_objectives') ?></textarea>
                        </div>
                    </div>
                </div>

                <div class="pdf-section">
                    <div class="pdf-section-title">4. Payment Confirmation</div>

                    <div class="row g-4 align-items-start">
                        <div class="col-md-7">
                            <label class="pdf-label">Method of Payment</label>
                            <div class="pdf-checks">
                                <label class="pdf-check"><input type="radio" name="payment_method" value="EFT" <?= is_checked_value('payment_method','EFT') ?> required>EFT</label>
                                <label class="pdf-check"><input type="radio" name="payment_method" value="Mobile Money" <?= is_checked_value('payment_method','Mobile Money') ?>>Mobile Money</label>
                                <label class="pdf-check"><input type="radio" name="payment_method" value="Cash" <?= is_checked_value('payment_method','Cash') ?>>Cash</label>
                                <span class="payment-note">*Proof of payment must be attached</span>
                            </div>

                            <div id="eftBankDetails" class="banking-details-panel <?= (($_POST['payment_method'] ?? '') === 'EFT') ? 'show' : '' ?>">
                                <div class="banking-details-head">
                                    <i class="fa-solid fa-building-columns me-2"></i>EFT Banking Details
                                </div>
                                <div class="banking-details-body">
                                    <div class="bank-row"><span>Name:</span><span>Eswatini Contractors Association</span></div>
                                    <div class="bank-row"><span>Bank:</span><span>Standard Bank</span></div>
                                    <div class="bank-row"><span>Branch:</span><span>Mbabane</span></div>
                                    <div class="bank-row"><span>Account Number:</span><span>9110003519522</span></div>
                                    <div class="bank-row"><span>Branch Code:</span><span>663164</span></div>
                                    <div class="banking-note">
                                        Please use your ECA Membership Number or Company Name as the payment reference, then upload proof of payment.
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="col-md-5">
                            <div class="file-panel">
                                <label class="pdf-label">Proof of Payment</label>
                                <input type="file" name="payment_proof" class="form-control" accept=".pdf,.doc,.docx,.jpg,.jpeg,.png">
                                <button type="button" class="btn btn-momo mt-3" data-bs-toggle="modal" data-bs-target="#momoModal">
                                    <i class="fa-solid fa-mobile-screen-button me-2"></i>Pay With MoMo
                                </button>
                            </div>
                        </div>
                    </div>

                    <div class="declaration">
                        <strong>Declaration:</strong> I, the undersigned, agree to abide by the ECA training requirements.
                        I acknowledge that the commitment fee of <strong>E<?= e(number_format((float)$commitment_fee, 2)) ?></strong> is non-refundable
                        and that full attendance <strong>(<?= e($courseTime) ?>)</strong> is required to earn the
                        <strong><?= e(number_format((float)$coursePoints, 2)) ?> CPD points</strong>. I understand that the course assumes basic contract law knowledge.
                    </div>

                    <div class="agree-box">
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" name="declaration_confirm" id="declaration_confirm" required <?= is_checked('declaration_confirm') ?>>
                            <label class="form-check-label fw-semibold" for="declaration_confirm">
                                I confirm that I have read and agree to the declaration above.
                            </label>
                        </div>
                    </div>

                    <div class="row g-5 signature-area">
                        <div class="col-md-6">
                            <input type="text" name="signature_name" class="form-control text-center" value="<?= old('signature_name') ?>" required>
                            <div class="signature-line">Delegate Signature</div>
                        </div>

                        <div class="col-md-6">
                            <input type="date" name="signature_date" class="form-control text-center" value="<?= old('signature_date', date('Y-m-d')) ?>" required>
                            <div class="signature-line">Date</div>
                        </div>
                    </div>
                </div>

                <div class="submit-bar">
                    <button name="submit" class="btn btn-submit-app btn-lg" type="submit">
                        <i class="fa-solid fa-paper-plane me-2"></i>Submit Application
                    </button>
                </div>
            </form>

            <div class="footer-line">
                Contact: +268 2404 4987 | Email: info@eca.co.sz | Website: www.eca.co.sz<br>
                <strong>Strengthening Contractor Capacity through Professionalism and Discipline</strong>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="momoModal" tabindex="-1" aria-labelledby="momoModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg rounded-4 overflow-hidden">
            <div class="modal-header text-white" style="background: linear-gradient(135deg, #061f52, #c40000);">
                <h5 class="modal-title fw-bold" id="momoModalLabel">
                    <i class="fa-solid fa-wallet me-2"></i>Pay With MoMo
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>

            <form id="momoForm" method="POST">
                <input type="hidden" name="membership_number" id="momo_membership_number">
                <input type="hidden" name="application_id" id="momo_application_id" value="<?= isset($application_id) ? (int)$application_id : '' ?>">

                <div class="modal-body p-4">
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Amount</label>
                        <input 
                            type="number" 
                            step="0.01" 
                            min="1" 
                            name="amount" 
                            id="amount" 
                            class="form-control form-control-lg rounded-3" 
                            value="<?= e((string)$commitment_fee) ?>" 
                            required
                        >
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-semibold">Mobile Number</label>
                        <input 
                            type="text" 
                            name="mobile_number" 
                            id="mobile_number" 
                            class="form-control form-control-lg rounded-3" 
                            placeholder="e.g. 26876123456" 
                            required
                        >
                    </div>

                    <div class="alert alert-warning small mb-0 rounded-3">
                        <i class="fa-solid fa-circle-info me-2"></i>
                        After submitting, please check your phone and approve the MoMo payment request.
                    </div>
                </div>

                <div class="modal-footer bg-light">
                    <button type="button" class="btn btn-outline-secondary rounded-pill px-4" data-bs-dismiss="modal">
                        Cancel
                    </button>

                    <button type="submit" class="btn text-white rounded-pill px-4" style="background: linear-gradient(135deg, #061f52, #c40000);">
                        <i class="fa-solid fa-paper-plane me-2"></i>Continue
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<div id="waitingOverlay" style="display:none; position:fixed; inset:0; background:rgba(15,23,42,0.75); z-index:9999;">
    <div class="d-flex align-items-center justify-content-center h-100">
        <div class="bg-white rounded-4 shadow-lg p-5 text-center" style="max-width:420px; width:90%;">
            <div class="spinner-border text-warning mb-3" style="width:3rem; height:3rem;" role="status"></div>
            <h4 class="fw-bold mb-2">Waiting for Approval...</h4>
            <p class="text-muted mb-0">
                A payment request has been sent to your phone.<br>
                Please approve the MoMo transaction to continue.
            </p>
        </div>
    </div>
</div>
   </div>
</div>

<?php if (file_exists(__DIR__ . "/footer.php")) { require_once "footer.php"; } ?>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

<script>
const lookupBtn = document.getElementById('lookupCompanyBtn');
const membershipInput = document.getElementById('membership_number');
const statusBox = document.getElementById('companyLookupStatus');

const companyNameInput = document.getElementById('company_name');
const registrationInput = document.getElementById('company_registration_name');
const disciplineInput = document.getElementById('discipline');
const regionInput = document.getElementById('region');
const companyEmailHidden = document.getElementById('company_email');
const companyPhoneHidden = document.getElementById('company_phone');
const companyEmailPhoneInput = document.getElementById('company_email_phone');
const companyStatusInput = document.getElementById('company_status');
const companyActiveInput = document.getElementById('company_active');

function clearCompanyFields() {
    companyNameInput.value = '';
    registrationInput.value = '';
    disciplineInput.value = '';
    regionInput.value = '';
    companyEmailHidden.value = '';
    companyPhoneHidden.value = '';
    companyEmailPhoneInput.value = '';
    companyStatusInput.value = '';
    companyActiveInput.value = '';
}

function setStatus(message, type) {
    statusBox.textContent = message;
    statusBox.className = 'lookup-status ' + type;
}

function lookupCompany() {
    const membershipNumber = membershipInput.value.trim();
    clearCompanyFields();

    if (!membershipNumber) {
        setStatus('Please enter membership number.', 'error');
        return;
    }

    setStatus('Searching company details...', '');

    fetch('get_company_by_membership.php?membership_number=' + encodeURIComponent(membershipNumber))
        .then(response => response.json())
        .then(data => {
            if (data.status === 'success') {
                const company = data.data;
                const email = company.email || '';
                const phone = company.telephone || '';

                companyNameInput.value = company.company_name || '';
                registrationInput.value = company.company_registration_name || '';
                disciplineInput.value = company.discipline || '';
                regionInput.value = company.region || '';
                companyEmailHidden.value = email;
                companyPhoneHidden.value = phone;
                companyEmailPhoneInput.value = [email, phone].filter(Boolean).join(' / ');
                companyStatusInput.value = company.status || '';
                companyActiveInput.value = company.active || '';

                setStatus('Company details loaded successfully.', 'success');
            } else {
                clearCompanyFields();
                setStatus(data.message || 'Company not found.', 'error');
            }
        })
        .catch(() => {
            clearCompanyFields();
            setStatus('Could not connect to company lookup service.', 'error');
        });
}

if (lookupBtn) {
    lookupBtn.addEventListener('click', lookupCompany);
}

if (membershipInput) {
    membershipInput.addEventListener('blur', function () {
        if (this.value.trim() !== '') {
            lookupCompany();
        }
    });
}

const paymentMethodInputs = document.querySelectorAll('input[name="payment_method"]');
const eftBankDetails = document.getElementById('eftBankDetails');

function toggleEftBankDetails() {
    if (!eftBankDetails) return;

    const selectedPayment = document.querySelector('input[name="payment_method"]:checked');

    if (selectedPayment && selectedPayment.value === 'EFT') {
        eftBankDetails.classList.add('show');
    } else {
        eftBankDetails.classList.remove('show');
    }
}

paymentMethodInputs.forEach(function (input) {
    input.addEventListener('change', toggleEftBankDetails);
});

toggleEftBankDetails();

/* Auto-select Mobile Money when Pay With MoMo button is clicked */

const momoOpenBtn = document.querySelector('[data-bs-target="#momoModal"]');
const momoForm = document.getElementById('momoForm');

if (momoOpenBtn) {
    momoOpenBtn.addEventListener('click', function () {
        const momoRadio = document.querySelector('input[name="payment_method"][value="Mobile Money"]');

        if (momoRadio) {
            momoRadio.checked = true;
        }

        const mainMembershipInput = document.getElementById('membership_number');
        const momoMembershipInput = document.getElementById('momo_membership_number');

        if (mainMembershipInput && momoMembershipInput) {
            momoMembershipInput.value = mainMembershipInput.value.trim();
        }
    });
}

if (momoForm) {
    momoForm.addEventListener('submit', function (e) {
        e.preventDefault();

        const mainMembershipInput = document.getElementById('membership_number');
        const momoMembershipInput = document.getElementById('momo_membership_number');

        if (mainMembershipInput && momoMembershipInput) {
            momoMembershipInput.value = mainMembershipInput.value.trim();
        }

        if (!momoMembershipInput.value) {
            alert('Please enter ECA Membership Number before making payment.');
            return;
        }

        const formData = new FormData(this);
        const waitingOverlay = document.getElementById('waitingOverlay');

        if (waitingOverlay) {
            waitingOverlay.style.display = 'block';
        }

        fetch('api/pay_momo.php', {
            method: 'POST',
            body: formData
        })
        .then(res => res.json())
        .then(data => {
            if (waitingOverlay) {
                waitingOverlay.style.display = 'none';
            }

            if (data.status === 'success') {
                const momoModalEl = document.getElementById('momoModal');

                if (momoModalEl) {
                    const modalInstance =
                        bootstrap.Modal.getInstance(momoModalEl) ||
                        new bootstrap.Modal(momoModalEl);

                    modalInstance.hide();
                }

                document.body.classList.remove('modal-open');
                document.body.style.removeProperty('overflow');
                document.body.style.removeProperty('padding-right');

                document.querySelectorAll('.modal-backdrop').forEach(function (backdrop) {
                    backdrop.remove();
                });

                alert(
                    'MoMo payment request sent successfully.\n\n' +
                    'Please approve the request on your phone.\n' +
                    'Request ID: ' + (data.request_id || 'N/A')
                );

                momoForm.reset();

            } else {
                alert(data.message || 'Payment failed.');
            }
        })
        .catch(() => {
            if (waitingOverlay) {
                waitingOverlay.style.display = 'none';
            }

            alert('An error occurred while sending the payment request.');
        });
    });
}
</script>

</body>
</html>