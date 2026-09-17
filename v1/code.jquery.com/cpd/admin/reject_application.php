<?php
session_start();
require_once "../auth.php";
require_once "../config.php";
require_once "../helpers.php";

require_role(['SUPPERADMIN', 'OFFICER']);

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require '../PHPMailer/src/Exception.php';
require '../PHPMailer/src/PHPMailer.php';
require '../PHPMailer/src/SMTP.php';

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    die("Invalid request");
}

$id = (int) $_GET['id'];
$error = "";
$reason = "";

$stmt = $conn->prepare("
    SELECT 
        cpd_applications.*,
        courses.title,
        courses.description,
        courses.start_date,
        courses.end_date
    FROM cpd_applications
    LEFT JOIN courses ON courses.id = cpd_applications.course_id
    WHERE cpd_applications.id = ?
    LIMIT 1
");
$stmt->bind_param("i", $id);
$stmt->execute();
$result = $stmt->get_result();
$app = $result->fetch_assoc();
$stmt->close();

if (!$app) {
    die("Application not found");
}

$email     = trim((string)($app['email'] ?? ''));
$company   = trim((string)($app['company_name'] ?? ''));
$full_name = trim((string)($app['full_name'] ?? 'Applicant'));
$phone     = trim((string)($app['phone'] ?? ''));
$course    = trim((string)($app['title'] ?? 'N/A'));
$desc      = trim((string)($app['description'] ?? ''));
$start     = !empty($app['start_date']) ? date("d M Y H:i", strtotime($app['start_date'])) : 'N/A';
$end       = !empty($app['end_date']) ? date("d M Y H:i", strtotime($app['end_date'])) : 'N/A';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $reason = trim($_POST['reason'] ?? '');

    if ($reason === '') {
        $error = "Please provide the reason for rejection.";
    } else {
        $stmt = $conn->prepare("
            UPDATE cpd_applications 
            SET status = 'Rejected',
                rejection_reason = ?,
                rejected_at = NOW()
            WHERE id = ?
        ");
        $stmt->bind_param("si", $reason, $id);

        if (!$stmt->execute()) {
            $error = "Failed to update application status.";
        }
        $stmt->close();

        if ($error === "") {
            $mail = new PHPMailer(true);

            try {
                if (!eca_configure_smtp($mail)) {
                    throw new Exception('SMTP is not configured locally.');
                }

                $mail->setFrom('info@eca.co.sz', 'ECA CPD Training');
                $mail->addAddress($email);
                $mail->addBCC('brightwell.kunene@gmail.com');

                $mail->isHTML(true);
                $mail->Subject = "ECA Training Application Update";

                $mail->Body = '
                <div style="font-family:Segoe UI,Arial,sans-serif;background:#f4f6f9;padding:20px;">
                    <div style="max-width:650px;margin:auto;background:#ffffff;border-radius:10px;overflow:hidden;box-shadow:0 6px 20px rgba(0,0,0,.08);">
                        <div style="background:#7a0f0f;color:#ffffff;padding:22px;text-align:center;">
                            <h2 style="margin:0;">Eswatini Contractors Association</h2>
                            <p style="margin:8px 0 0;">CPD Training Application Update</p>
                        </div>

                        <div style="padding:28px;color:#333333;">
                            <p>Dear <strong>' . e($full_name) . '</strong>,</p>

                            <p>We regret to inform you that your CPD training application has been 
                            <strong style="color:#b00020;">REJECTED</strong>.</p>

                            <hr style="border:none;border-top:1px solid #e5e7eb;margin:20px 0;">

                            <h3 style="color:#06254a;margin-bottom:10px;">Training Details</h3>
                            <p><strong>Course:</strong> ' . e($course) . '</p>
                            <p><strong>Description:</strong><br>' . nl2br(e($desc)) . '</p>
                            <p><strong>Start Date:</strong> ' . e($start) . '</p>
                            <p><strong>End Date:</strong> ' . e($end) . '</p>

                            <hr style="border:none;border-top:1px solid #e5e7eb;margin:20px 0;">

                            <h3 style="color:#06254a;margin-bottom:10px;">Reason for Rejection</h3>
                            <div style="background:#fff5f5;border:1px solid #f5c2c7;padding:14px 16px;border-radius:8px;color:#842029;">
                                ' . nl2br(e($reason)) . '
                            </div>

                            <p style="margin-top:20px;">You may contact the ECA Training Office for further guidance or clarification.</p>

                            <br>
                            <p>Regards,<br><strong>ECA Training Office</strong></p>
                        </div>
                    </div>
                </div>';

                $mail->send();

                header("Location: applications.php?msg=rejected");
                exit;
            } catch (Exception $ex) {
                $error = "Application rejected, but email failed to send: " . $mail->ErrorInfo;
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reject Application</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body{
            background: linear-gradient(135deg, #f3f4f6, #e5e7eb);
            min-height: 100vh;
            font-family: "Segoe UI", Arial, sans-serif;
        }
        .reject-card{
            max-width: 900px;
            margin: 40px auto;
            background: #fff;
            border-radius: 18px;
            box-shadow: 0 18px 50px rgba(0,0,0,.08);
            overflow: hidden;
        }
        .reject-header{
            background: linear-gradient(135deg, #7a0f0f, #b91c1c);
            color: #fff;
            padding: 24px 30px;
        }
        .reject-body{
            padding: 30px;
        }
        .app-info{
            background: #f8fafc;
            border: 1px solid #e5e7eb;
            border-radius: 12px;
            padding: 20px;
            margin-bottom: 24px;
        }
        .app-info .label{
            font-weight: 700;
            color: #06254a;
            display: inline-block;
            min-width: 140px;
        }
        textarea.form-control{
            min-height: 160px;
            border-radius: 12px;
        }
        .btn-danger{
            background: linear-gradient(135deg, #b91c1c, #7f1d1d);
            border: none;
            border-radius: 12px;
            padding: 12px 22px;
            font-weight: 600;
        }
        .btn-secondary{
            border-radius: 12px;
            padding: 12px 22px;
            font-weight: 600;
        }
    </style>
</head>
<body>
<div class="container">
    <div class="reject-card">
        <div class="reject-header">
            <h2 class="mb-1">Reject CPD Application</h2>
            <p class="mb-0">Review the application details and provide a rejection reason.</p>
        </div>

        <div class="reject-body">
            <?php if ($error): ?>
                <div class="alert alert-danger"><?php echo e($error); ?></div>
            <?php endif; ?>

            <div class="app-info">
                <div class="row g-3">
                    <div class="col-md-6">
                        <div><span class="label">Applicant:</span> <?php echo e($full_name); ?></div>
                        <div><span class="label">Company:</span> <?php echo e($company); ?></div>
                        <div><span class="label">Email:</span> <?php echo e($email); ?></div>
                        <div><span class="label">Phone:</span> <?php echo e($phone); ?></div>
                    </div>
                    <div class="col-md-6">
                        <div><span class="label">Course:</span> <?php echo e($course); ?></div>
                        <div><span class="label">Start Date:</span> <?php echo e($start); ?></div>
                        <div><span class="label">End Date:</span> <?php echo e($end); ?></div>
                        <div><span class="label">Current Status:</span> <?php echo e($app['status'] ?? 'Pending'); ?></div>
                    </div>
                </div>

                <?php if (!empty($desc)): ?>
                    <hr>
                    <div>
                        <span class="label">Description:</span><br>
                        <div class="mt-2 text-muted"><?php echo nl2br(e($desc)); ?></div>
                    </div>
                <?php endif; ?>
            </div>

            <form method="post">
                <div class="mb-3">
                    <label class="form-label fw-bold">Reason for Rejection</label>
                    <textarea name="reason" class="form-control" placeholder="Enter the reason for rejecting this application..." required><?php echo e($reason); ?></textarea>
                </div>

                <div class="d-flex gap-2">
                    <button type="submit" class="btn btn-danger">Reject Application</button>
                    <a href="applications.php" class="btn btn-secondary">Cancel</a>
                </div>
            </form>
        </div>
    </div>
</div>
</body>
</html>