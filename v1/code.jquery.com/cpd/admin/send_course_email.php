<?php
session_start();
require_once "../config.php";

/* =========================
   STAFF ACCESS CHECK
========================= */
$currentRole = strtoupper($_SESSION['role'] ?? '');
if (!isset($_SESSION['user_id']) || !in_array($currentRole, ['SUPPERADMIN', 'ADMIN', 'OFFICER'], true)) {
    die("Access denied.");
}

/* =========================
   PHPMailer
========================= */
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require_once "../PHPMailer/src/Exception.php";
require_once "../PHPMailer/src/PHPMailer.php";
require_once "../PHPMailer/src/SMTP.php";

/* =========================
   HELPERS
========================= */
function e($value)
{
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}

function formatDateNice($datetime)
{
    if (empty($datetime) || $datetime === '0000-00-00 00:00:00') {
        return 'TBA';
    }

    $time = strtotime($datetime);
    if (!$time) {
        return 'TBA';
    }

    return date('d M Y \a\t H:i', $time);
}

function createAnnouncementAndContractorNotifications(
    mysqli $conn,
    int $course_id,
    int $staff_id,
    string $course_title,
    string $course_start,
    string $course_end,
    string $course_venue
): array {
    $title = "New CPD Course: " . $course_title;
    $message = "A new CPD course is now available for contractors. "
             . "Course: {$course_title}. "
             . "Start: {$course_start}. "
             . "End: {$course_end}. "
             . "Venue: {$course_venue}. "
             . "Please log in to the CPD portal and apply.";
    $link = "/cpd/cpd_application.php?course_id=" . $course_id;

    $announcement_id = 0;
    $notification_count = 0;

    $conn->begin_transaction();

    try {
        /* =========================
           INSERT ANNOUNCEMENT
           Assumed columns:
           id, title, message, target_role, course_id, link, created_by, status, created_at
        ========================= */
        $target_role = 'CONTRACTOR';
        $status = 'ACTIVE';

        $stmtAnn = $conn->prepare("
            INSERT INTO announcements
            (title, message, target_role, course_id, link, created_by, status, created_at)
            VALUES (?, ?, ?, ?, ?, ?, ?, NOW())
        ");
        if (!$stmtAnn) {
            throw new Exception("Announcement prepare failed: " . $conn->error);
        }

        $stmtAnn->bind_param(
            "sssisis",
            $title,
            $message,
            $target_role,
            $course_id,
            $link,
            $staff_id,
            $status
        );

        if (!$stmtAnn->execute()) {
            throw new Exception("Announcement insert failed: " . $stmtAnn->error);
        }

        $announcement_id = (int)$conn->insert_id;
        $stmtAnn->close();

        /* =========================
           INSERT NOTIFICATIONS FOR ALL ACTIVE CONTRACTORS
           Assumed columns:
           id, user_id, title, message, type, link, is_read, announcement_id, created_at
        ========================= */
        $type = 'ANNOUNCEMENT';

        $stmtNotif = $conn->prepare("
            INSERT INTO notifications
            (user_id, title, message, type, link, is_read, announcement_id, created_at)
            SELECT
                id,
                ?,
                ?,
                ?,
                ?,
                0,
                ?,
                NOW()
            FROM user
            WHERE role = 'CONTRACTOR'
              AND status = 'ACTIVE'
        ");
        if (!$stmtNotif) {
            throw new Exception("Notification prepare failed: " . $conn->error);
        }

        $stmtNotif->bind_param(
            "ssssi",
            $title,
            $message,
            $type,
            $link,
            $announcement_id
        );

        if (!$stmtNotif->execute()) {
            throw new Exception("Notification insert failed: " . $stmtNotif->error);
        }

        $notification_count = $stmtNotif->affected_rows;
        $stmtNotif->close();

        $conn->commit();

        return [
            'announcement_id' => $announcement_id,
            'notification_count' => $notification_count
        ];
    } catch (Exception $ex) {
        $conn->rollback();
        throw $ex;
    }
}

/* =========================
   GET COURSE ID
========================= */
$course_id = isset($_GET['course_id']) ? (int) $_GET['course_id'] : 0;

if ($course_id <= 0) {
    die("Invalid course ID.");
}

/* =========================
   LOAD COURSE
========================= */
$stmt = $conn->prepare("
    SELECT id, title, description, banner, start_date, end_date, venue, capacity, points, status
    FROM courses
    WHERE id = ?
    LIMIT 1
");
$stmt->bind_param("i", $course_id);
$stmt->execute();
$result = $stmt->get_result();

if (!$result || $result->num_rows === 0) {
    die("Course not found.");
}

$course = $result->fetch_assoc();
$stmt->close();

/* =========================
   COURSE DATA
========================= */
$course_title       = $course['title'] ?? 'Course';
$course_description = $course['description'] ?? 'No description available.';
$course_banner      = trim($course['banner'] ?? '');
$course_start       = formatDateNice($course['start_date'] ?? '');
$course_end         = formatDateNice($course['end_date'] ?? '');
$course_venue       = $course['venue'] ?? 'TBA';
$course_capacity    = $course['capacity'] ?? 'N/A';
$course_points      = $course['points'] ?? '0';
$course_status      = $course['status'] ?? 'OPEN';

/* =========================
   BANNER URL
========================= */
$default_banner = "https://eca.co.sz/cpd/assets/images/banner.png";

if (!empty($course_banner)) {
    if (strpos($course_banner, 'http://') === 0 || strpos($course_banner, 'https://') === 0) {
        $banner_url = $course_banner;
    } else {
        $banner_url = "https://eca.co.sz/cpd/" . ltrim($course_banner, '/');
    }
} else {
    $banner_url = $default_banner;
}

/* =========================
   TEST EMAIL ONLY
========================= */
$test_email = "tmsconceptssz@gmail.com";
$subject    = "TEST - Course Details: " . $course_title;

/* =========================
   EMAIL BODY
========================= */
$htmlBody = '
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Course Details</title>
</head>
<body style="margin:0; padding:0; background:#eef3f8; font-family:Arial, Helvetica, sans-serif;">

    <div style="max-width:720px; margin:30px auto; background:#ffffff; border-radius:18px; overflow:hidden; box-shadow:0 10px 30px rgba(0,0,0,0.08);">

        <div style="background:linear-gradient(135deg,#134f62,#25809b); padding:28px 30px; color:#ffffff;">
            <div style="font-size:13px; opacity:0.9; letter-spacing:1px;">ECA CPD TEST EMAIL</div>
            <h1 style="margin:10px 0 6px; font-size:28px; line-height:1.2;">' . e($course_title) . '</h1>
            <p style="margin:0; font-size:14px; opacity:0.95;">Course details preview for testing</p>
        </div>

        <div style="padding:0; background:#ffffff;">
            <img src="' . e($banner_url) . '" alt="Course Banner" style="width:100%; max-height:320px; object-fit:cover; display:block;">
        </div>

        <div style="padding:30px;">
            <p style="margin-top:0; font-size:15px; color:#334155; line-height:1.8;">
                Dear Member,
            </p>

            <p style="font-size:15px; color:#334155; line-height:1.8;">
                Please find below the details of the upcoming CPD course.
            </p>

            <div style="margin:24px 0; border:1px solid #e5e7eb; border-radius:14px; overflow:hidden;">
                <table width="100%" cellpadding="0" cellspacing="0" style="border-collapse:collapse;">
                    <tr>
                        <td style="width:180px; padding:14px 16px; background:#f8fafc; border-bottom:1px solid #e5e7eb; font-weight:bold; color:#0f172a;">Course Title</td>
                        <td style="padding:14px 16px; border-bottom:1px solid #e5e7eb; color:#334155;">' . e($course_title) . '</td>
                    </tr>
                    <tr>
                        <td style="padding:14px 16px; background:#f8fafc; border-bottom:1px solid #e5e7eb; font-weight:bold; color:#0f172a;">Description</td>
                        <td style="padding:14px 16px; border-bottom:1px solid #e5e7eb; color:#334155;">' . nl2br(e($course_description)) . '</td>
                    </tr>
                    <tr>
                        <td style="padding:14px 16px; background:#f8fafc; border-bottom:1px solid #e5e7eb; font-weight:bold; color:#0f172a;">Start Date</td>
                        <td style="padding:14px 16px; border-bottom:1px solid #e5e7eb; color:#334155;">' . e($course_start) . '</td>
                    </tr>
                    <tr>
                        <td style="padding:14px 16px; background:#f8fafc; border-bottom:1px solid #e5e7eb; font-weight:bold; color:#0f172a;">End Date</td>
                        <td style="padding:14px 16px; border-bottom:1px solid #e5e7eb; color:#334155;">' . e($course_end) . '</td>
                    </tr>
                    <tr>
                        <td style="padding:14px 16px; background:#f8fafc; border-bottom:1px solid #e5e7eb; font-weight:bold; color:#0f172a;">Venue</td>
                        <td style="padding:14px 16px; border-bottom:1px solid #e5e7eb; color:#334155;">' . e($course_venue) . '</td>
                    </tr>
                    <tr>
                        <td style="padding:14px 16px; background:#f8fafc; border-bottom:1px solid #e5e7eb; font-weight:bold; color:#0f172a;">Capacity</td>
                        <td style="padding:14px 16px; border-bottom:1px solid #e5e7eb; color:#334155;">' . e($course_capacity) . '</td>
                    </tr>
                    <tr>
                        <td style="padding:14px 16px; background:#f8fafc; border-bottom:1px solid #e5e7eb; font-weight:bold; color:#0f172a;">CPD Points</td>
                        <td style="padding:14px 16px; border-bottom:1px solid #e5e7eb; color:#334155;">' . e($course_points) . '</td>
                    </tr>
                    <tr>
                        <td style="padding:14px 16px; background:#f8fafc; font-weight:bold; color:#0f172a;">Status</td>
                        <td style="padding:14px 16px; color:#334155;">' . e($course_status) . '</td>
                    </tr>
                </table>
            </div>

            <div style="margin:30px 0 18px;">
                <a href="https://eca.co.sz/cpd/cpd_application.php?course_id=' . (int)$course_id . '"
                   style="display:inline-block; padding:14px 24px; border-radius:10px; background:linear-gradient(135deg,#134f62,#25809b); color:#ffffff; text-decoration:none; font-weight:bold; font-size:15px;">
                  Application Link
                </a>
            </div>

            <p style="font-size:14px; color:#64748b; line-height:1.8; margin-bottom:0;">
                Regards,<br>
                Eswatini Contractors Association
            </p>
        </div>
    </div>

</body>
</html>
';

$altBody =
"TEST EMAIL - COURSE DETAILS

Course Title: {$course_title}
Description: {$course_description}
Start Date: {$course_start}
End Date: {$course_end}
Venue: {$course_venue}
Capacity: {$course_capacity}
CPD Points: {$course_points}
Status: {$course_status}

Portal: https://eca.co.sz/cpd/
";

/* =========================
   SEND EMAIL + ANNOUNCEMENT + NOTIFICATIONS
========================= */
$mail = new PHPMailer(true);
$announcement_id = 0;
$notification_count = 0;

try {
    if (!eca_configure_smtp($mail)) {
        throw new Exception('SMTP is not configured locally.');
    }

    $mail->setFrom('noreply@eca.co.sz', 'ECA CPD');
    $mail->addReplyTo('info@eca.co.sz', 'ECA');
    $mail->addAddress($test_email, 'ECA Test');

    $mail->isHTML(true);
    $mail->Subject = $subject;
    $mail->Body    = $htmlBody;
    $mail->AltBody = $altBody;

    $mail->send();

    $admin_id = (int) $_SESSION['user_id'];

    /* email log */
    $stmtLog = $conn->prepare("
        INSERT INTO system_email_logs
        (course_id, sent_by, total_sent, total_failed, created_at)
        VALUES (?, ?, 1, 0, NOW())
    ");
    if (!$stmtLog) {
        throw new Exception("Email log prepare failed: " . $conn->error);
    }
    $stmtLog->bind_param("ii", $course_id, $admin_id);
    if (!$stmtLog->execute()) {
        throw new Exception("Email log insert failed: " . $stmtLog->error);
    }
    $stmtLog->close();

    /* announcement + notifications */
    $notice = createAnnouncementAndContractorNotifications(
        $conn,
        $course_id,
        $admin_id,
        $course_title,
        $course_start,
        $course_end,
        $course_venue
    );

    $announcement_id = (int)($notice['announcement_id'] ?? 0);
    $notification_count = (int)($notice['notification_count'] ?? 0);

} catch (Exception $e) {
    $error = $mail->ErrorInfo ?: $e->getMessage();
    ?>
    <!DOCTYPE html>
    <html lang="en">
    <head>
        <meta charset="UTF-8">
        <title>Email Failed</title>
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <style>
            body{margin:0;font-family:Arial, Helvetica, sans-serif;background:#f1f5f9;}
            .wrap{max-width:700px;margin:50px auto;background:#fff;border-radius:18px;box-shadow:0 10px 25px rgba(0,0,0,.08);overflow:hidden;}
            .top{background:linear-gradient(135deg,#7f1d1d,#dc2626);color:#fff;padding:24px 28px;}
            .body{padding:28px;}
            .btn{display:inline-block;background:#134f62;color:#fff;text-decoration:none;padding:12px 18px;border-radius:10px;font-weight:bold;}
            .error-box{background:#fff5f5;border:1px solid #fecaca;color:#991b1b;padding:14px 16px;border-radius:12px;margin-top:15px;white-space:pre-wrap;word-break:break-word;}
        </style>
    </head>
    <body>
        <div class="wrap">
            <div class="top">
                <h2 style="margin:0;">Email / Announcement Action Failed</h2>
            </div>
            <div class="body">
                <p><strong>Course:</strong> <?= e($course_title) ?></p>
                <p><strong>Test Recipient:</strong> <?= e($test_email) ?></p>

                <div class="error-box"><?= e($error) ?></div>

                <div style="margin-top:20px;">
                    <a href="courses.php" class="btn">Back to Courses</a>
                </div>
            </div>
        </div>
    </body>
    </html>
    <?php
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Submission Completed</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <style>
        body{margin:0;font-family:Arial, Helvetica, sans-serif;background:#eef3f8;}
        .wrap{max-width:760px;margin:50px auto;background:#fff;border-radius:20px;box-shadow:0 12px 30px rgba(0,0,0,.08);overflow:hidden;}
        .top{background:linear-gradient(135deg,#134f62,#25809b);color:#fff;padding:28px 30px;}
        .body{padding:30px;}
        .success{display:inline-block;background:#ecfdf5;color:#166534;border:1px solid #bbf7d0;padding:10px 14px;border-radius:999px;font-weight:bold;margin-bottom:18px;}
        .info-card{background:#f8fafc;border:1px solid #e2e8f0;border-radius:16px;padding:18px 20px;margin-top:18px;}
        .btn{display:inline-block;padding:13px 20px;border-radius:10px;text-decoration:none;font-weight:bold;margin-top:22px;}
        .btn-primary{background:linear-gradient(135deg,#134f62,#25809b);color:#fff;}
        .btn-secondary{background:#e2e8f0;color:#0f172a;margin-left:10px;}
        .mini{color:#64748b;font-size:14px;}
    </style>
</head>
<body>
    <div class="wrap">
        <div class="top">
            <h1 style="margin:0; font-size:28px;">Submission Completed Successfully</h1>
            <p style="margin:8px 0 0; opacity:.95;">Email, announcement, and contractor notifications were created</p>
        </div>

        <div class="body">
            <div class="success">✅ Process completed</div>

            <div class="info-card">
                <p><strong>Recipient:</strong> <?= e($test_email) ?></p>
                <p><strong>Course:</strong> <?= e($course_title) ?></p>
                <p><strong>Venue:</strong> <?= e($course_venue) ?></p>
                <p><strong>Start:</strong> <?= e($course_start) ?></p>
                <p><strong>End:</strong> <?= e($course_end) ?></p>
                <p><strong>CPD Points:</strong> <?= e($course_points) ?></p>
                <p><strong>Announcement ID:</strong> <?= (int)$announcement_id ?></p>
                <p><strong>Contractor Notifications:</strong> <?= (int)$notification_count ?></p>
                <p class="mini" style="margin-bottom:0;">Test email was sent, and contractor notices were recorded in the database.</p>
            </div>

            <a href="courses.php" class="btn btn-primary">Back to Courses</a>
            <a href="send_course_email.php?course_id=<?= (int)$course_id ?>" class="btn btn-secondary">Send Again</a>
        </div>
    </div>
</body>
</html>