<?php
require_once __DIR__ . '/../auth.php';
require_once __DIR__ . '/../helpers.php';
require_once __DIR__ . '/../config.php';

$allowedRoles = defined('CPD_STATUS_ALLOWED_ROLES')
    ? CPD_STATUS_ALLOWED_ROLES
    : ['SUPPERADMIN', 'ADMIN'];
require_role($allowedRoles);

use PHPMailer\PHPMailer\PHPMailer;

require_once __DIR__ . '/../PHPMailer/src/Exception.php';
require_once __DIR__ . '/../PHPMailer/src/PHPMailer.php';
require_once __DIR__ . '/../PHPMailer/src/SMTP.php';

function cpd_status_redirect(string $result): void
{
    header('Location: applications.php?msg=' . rawurlencode($result));
    exit;
}

function cpd_status_mail(array $application, string $status, ?string $temporaryPassword): bool
{
    $email = trim((string) ($application['email'] ?? ''));
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        return false;
    }

    $name = e($application['full_name'] ?? '');
    $emailHtml = e($email);
    $course = e($application['title'] ?? '');
    $statusHtml = e($status);
    $loginUrl = 'https://eca.co.sz/cpd/login.php';
    $credentials = '';

    if ($status === 'Approved') {
        if ($temporaryPassword !== null) {
            $credentials = '<h3>Your login details</h3>'
                . '<p><strong>Email:</strong> ' . $emailHtml . '<br>'
                . '<strong>Temporary password:</strong> ' . e($temporaryPassword) . '</p>'
                . '<p>Please change your password after your first login.</p>';
        } else {
            $credentials = '<p>An account already exists for this email address. '
                . 'Use your existing credentials to sign in.</p>';
        }
        $credentials .= '<p><a href="' . e($loginUrl) . '">Sign in to the CPD portal</a></p>';
    }

    $mail = new PHPMailer(true);

    try {
        if (!eca_configure_smtp($mail)) {
            return false;
        }

        $mail->setFrom('info@eca.co.sz', 'ECA CPD Training');
        $mail->addAddress($email);
        $mail->addReplyTo('info@eca.co.sz', 'ECA Training Office');
        $mail->isHTML(true);
        $mail->Subject = 'ECA CPD Application ' . $status;
        $mail->Body = '<div style="font-family:Segoe UI,Arial,sans-serif;color:#333">'
            . '<h2>Eswatini Contractors Association</h2>'
            . '<p>Dear <strong>' . $name . '</strong>,</p>'
            . '<p>Your CPD training application'
            . ($course !== '' ? ' for <strong>' . $course . '</strong>' : '')
            . ' has been <strong>' . $statusHtml . '</strong>.</p>'
            . $credentials
            . '<p>Regards,<br><strong>ECA Training Office</strong></p>'
            . '</div>';

        return $mail->send();
    } catch (\Throwable $exception) {
        error_log('CPD status email failed: ' . $exception->getMessage());
        return false;
    }
}

$method = strtoupper((string) ($_SERVER['REQUEST_METHOD'] ?? 'GET'));

if ($method === 'GET') {
    $id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
    $status = (string) ($_GET['status'] ?? '');

    if (!$id || !in_array($status, ['Approved', 'Rejected'], true)) {
        cpd_status_redirect('invalid_request');
    }

    $action = $status === 'Approved' ? 'approve' : 'reject';
    ?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Confirm application status</title>
</head>
<body>
    <main>
        <h1>Confirm application status</h1>
        <p>Are you sure you want to <?= e($action) ?> application #<?= (int) $id ?>?</p>
        <form method="post" action="update_status.php">
            <?= cpd_csrf_input() ?>
            <input type="hidden" name="id" value="<?= (int) $id ?>">
            <input type="hidden" name="status" value="<?= e($status) ?>">
            <button type="submit">Yes, <?= e($action) ?> application</button>
            <a href="applications.php">Cancel</a>
        </form>
    </main>
</body>
</html>
    <?php
    exit;
}

if ($method !== 'POST') {
    cpd_status_redirect('invalid_request');
}

cpd_require_csrf();

$id = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT);
$status = (string) ($_POST['status'] ?? '');
if (!$id || !in_array($status, ['Approved', 'Rejected'], true)) {
    cpd_status_redirect('invalid_request');
}

$application = null;
$temporaryPassword = null;
$accountCreated = false;

try {
    if (!$conn->begin_transaction()) {
        throw new \RuntimeException('Could not begin transaction.');
    }

    $stmt = $conn->prepare(
        'SELECT a.email, a.company_name, a.full_name, a.phone, c.title
         FROM cpd_applications AS a
         LEFT JOIN courses AS c ON c.id = a.course_id
         WHERE a.id = ?
         FOR UPDATE'
    );
    if (!$stmt) {
        throw new \RuntimeException('Could not prepare application query.');
    }
    $stmt->bind_param('i', $id);
    if (!$stmt->execute()) {
        throw new \RuntimeException('Could not fetch application.');
    }
    $application = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if (!$application) {
        $conn->rollback();
        cpd_status_redirect('not_found');
    }

    $stmt = $conn->prepare('UPDATE cpd_applications SET status = ? WHERE id = ?');
    if (!$stmt) {
        throw new \RuntimeException('Could not prepare status update.');
    }
    $stmt->bind_param('si', $status, $id);
    if (!$stmt->execute()) {
        throw new \RuntimeException('Could not update application.');
    }
    $stmt->close();

    if ($status === 'Approved') {
        $email = trim((string) $application['email']);
        $stmt = $conn->prepare('SELECT id FROM user WHERE email = ? LIMIT 1 FOR UPDATE');
        if (!$stmt) {
            throw new \RuntimeException('Could not prepare account lookup.');
        }
        $stmt->bind_param('s', $email);
        if (!$stmt->execute()) {
            throw new \RuntimeException('Could not check account.');
        }
        $existingUser = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        if (!$existingUser) {
            $temporaryPassword = bin2hex(random_bytes(12));
            $passwordHash = password_hash($temporaryPassword, PASSWORD_DEFAULT);
            if ($passwordHash === false) {
                throw new \RuntimeException('Could not hash password.');
            }

            $stmt = $conn->prepare(
                "INSERT INTO user
                 (company_name, full_name, email, phone, password_hash, status, role, created_at)
                 VALUES (?, ?, ?, ?, ?, 'ACTIVE', 'CONTRACTOR', NOW())"
            );
            if (!$stmt) {
                throw new \RuntimeException('Could not prepare account creation.');
            }
            $stmt->bind_param(
                'sssss',
                $application['company_name'],
                $application['full_name'],
                $email,
                $application['phone'],
                $passwordHash
            );
            if (!$stmt->execute()) {
                throw new \RuntimeException('Could not create account.');
            }
            $stmt->close();
            $accountCreated = true;
        }
    }

    if (!$conn->commit()) {
        throw new \RuntimeException('Could not commit transaction.');
    }
} catch (\Throwable $exception) {
    try {
        $conn->rollback();
    } catch (\Throwable $ignored) {
    }
    error_log('CPD status update failed: ' . $exception->getMessage());
    cpd_status_redirect('update_failed');
}

$mailSent = cpd_status_mail($application, $status, $accountCreated ? $temporaryPassword : null);
$result = strtolower($status);
if (!$mailSent) {
    $result .= '_mail_failed';
}
cpd_status_redirect($result);