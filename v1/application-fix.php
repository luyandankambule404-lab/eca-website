<?php
require_once __DIR__ . '/includes/session.php';
require_once __DIR__ . '/includes/portal-db.php';
require_once __DIR__ . '/includes/public-page.php';
require_once __DIR__ . '/includes/application-track.php';
require_once __DIR__ . '/includes/application-docs.php';

$rawRef = (string) ($_GET['ref'] ?? $_POST['ref'] ?? '');
$ref = eca_track_normalize_reference($rawRef);
$email = strtolower(trim((string) ($_POST['email'] ?? $_GET['email'] ?? '')));
$notice = '';
$noticeError = false;
$app = null;
$requests = [];
$open = [];
$member = $_SESSION['eca_member'] ?? null;
if (is_array($member) && function_exists('eca_current_member')) {
    $fresh = eca_current_member(true);
    if (is_array($fresh)) {
        $member = $fresh;
    }
}

$conn = eca_portal_pdo(false);
if ($conn && is_array($member)) {
    $life = eca_member_lifecycle($conn, $member);
    if ((int) ($member['client_id'] ?? 0) < 1 && !empty($life['client_id'])) {
        $member['client_id'] = (int) $life['client_id'];
    }
    if (trim((string) ($member['membership'] ?? '')) === '' && !empty($life['membership'])) {
        $member['membership'] = (string) $life['membership'];
    }
}
if ($conn && $ref !== '' && eca_track_reference_valid($ref)) {
    $app = eca_application_row_by_reference($conn, $ref);
}

$canWork = eca_application_fix_allowed($app, is_array($member) ? $member : null, $email);
if ($app && $conn) {
    $requests = eca_application_doc_requests($conn, (int) $app['client_id']);
    $open = eca_application_open_doc_requests($conn, (int) $app['client_id']);
}

if ($conn && $app && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $ip = (string) ($_SERVER['REMOTE_ADDR'] ?? 'unknown');
    if (!eca_public_csrf_ok($_POST['csrf_token'] ?? null)) {
        $notice = 'Your session expired. Please try again.';
        $noticeError = true;
    } elseif (eca_rate_limit_exceeded('app-fix', $ip . '|' . $ref, 20, 900)) {
        $notice = 'Please wait a moment and try again.';
        $noticeError = true;
    } elseif (!eca_application_fix_allowed($app, is_array($member) ? $member : null, $email)) {
        $notice = 'Enter the email used on this application to continue.';
        $noticeError = true;
    } else {
        $action = (string) ($_POST['action'] ?? '');
        if ($action === 'upload') {
            $requestId = (int) ($_POST['request_id'] ?? 0);
            $stored = eca_application_upload_requested_doc($conn, $requestId, (int) $app['client_id'], $_FILES['document'] ?? []);
            if ($stored) {
                $notice = 'Document uploaded for application registration number ' . $ref . '.';
            } else {
                $notice = 'Upload a PDF or image under 2MB for the requested document.';
                $noticeError = true;
            }
        } elseif ($action === 'resubmit') {
            if (eca_application_resubmit($conn, $app)) {
                $notice = 'Application registration number ' . $ref . ' has been resent for review.';
            } else {
                $notice = 'Upload every requested document before resending the application.';
                $noticeError = true;
            }
        }
        $app = eca_application_row_by_reference($conn, $ref) ?? $app;
        $requests = eca_application_doc_requests($conn, (int) $app['client_id']);
        $open = eca_application_open_doc_requests($conn, (int) $app['client_id']);
        $canWork = eca_application_fix_allowed($app, is_array($member) ? $member : null, $email)
            || strtoupper((string) ($app['application_status'] ?? '')) === 'SUBMITTED';
    }
}

$csrf = eca_public_csrf_token();
$status = $app ? strtoupper((string) ($app['application_status'] ?? '')) : '';

eca_public_page_start(
    'Update application documents',
    'Membership',
    'Upload missing documents',
    'Use your application registration number to replace a missing or improper document and resend the application.',
    'Documents'
);
?>
<div class="container-xxl py-4 eca-track-page">
    <div class="eca-track">
        <section class="eca-track-card eca-form-panel" aria-labelledby="eca-fix-form-heading">
            <h2 id="eca-fix-form-heading" class="eca-track-card-title">Application registration number</h2>
            <form class="eca-track-form" method="get" action="/application-fix.php" novalidate>
                <div class="eca-track-field mb-3">
                    <label class="form-label" for="ref">Registration number</label>
                    <input
                        class="form-control"
                        id="ref"
                        name="ref"
                        value="<?= htmlspecialchars($ref, ENT_QUOTES, 'UTF-8') ?>"
                        placeholder="ECA-APP-2026-0001"
                        autocomplete="off"
                        maxlength="20"
                        required
                    >
                </div>
                <button class="btn eca-track-submit" type="submit">Find application</button>
            </form>
        </section>

        <?php if ($notice !== ''): ?>
            <p class="eca-track-alert" role="alert"><?= htmlspecialchars($notice, ENT_QUOTES, 'UTF-8') ?></p>
        <?php endif; ?>

        <?php if ($ref !== '' && !$app): ?>
            <p class="eca-track-alert" role="alert">We could not find an application with that registration number.</p>
        <?php endif; ?>

        <?php if ($app): ?>
            <section class="eca-track-card eca-track-result">
                <p class="eca-kicker">Application</p>
                <h2 class="eca-track-ref"><?= htmlspecialchars((string) $app['application_reference'], ENT_QUOTES, 'UTF-8') ?></h2>
                <p class="eca-track-badge <?= $status === 'ADDITIONAL INFORMATION REQUIRED' ? 'is-info' : 'is-submitted' ?>">
                    <?= htmlspecialchars(eca_track_status_label($status), ENT_QUOTES, 'UTF-8') ?>
                </p>
                <p><?= htmlspecialchars((string) ($app['TradingName'] ?? $app['CompanyRegistrationName'] ?? ''), ENT_QUOTES, 'UTF-8') ?></p>
            </section>

            <?php if ($status === 'ADDITIONAL INFORMATION REQUIRED' && !$canWork): ?>
                <section class="eca-track-card">
                    <h2 class="eca-track-card-title">Confirm it is your application</h2>
                    <p>Enter the email used when you registered so you can upload the missing document for registration number <?= htmlspecialchars($ref, ENT_QUOTES, 'UTF-8') ?>.</p>
                    <form class="eca-track-form" method="post" action="/application-fix.php">
                        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf, ENT_QUOTES, 'UTF-8') ?>">
                        <input type="hidden" name="ref" value="<?= htmlspecialchars($ref, ENT_QUOTES, 'UTF-8') ?>">
                        <div class="eca-track-field mb-3">
                            <label class="form-label" for="email">Application email</label>
                            <input class="form-control" id="email" name="email" type="email" value="<?= htmlspecialchars($email, ENT_QUOTES, 'UTF-8') ?>" required>
                        </div>
                        <button class="btn btn-primary eca-track-submit" type="submit">Continue</button>
                    </form>
                </section>
            <?php endif; ?>

            <?php if ($canWork || ($app && $requests)): ?>
                <section class="eca-track-card">
                    <h2 class="eca-track-card-title">Requested documents</h2>
                    <?php if (!$requests): ?>
                        <p>No document requests are open for this registration number.</p>
                    <?php endif; ?>
                    <?php foreach ($requests as $row): ?>
                        <?php
                        $isOpen = strtolower((string) ($row['status'] ?? '')) === 'open';
                        $label = eca_document_type_label((string) ($row['document_type'] ?? ''));
                        $reason = strtolower((string) ($row['reason'] ?? '')) === 'improper' ? 'Improper — upload a replacement' : 'Missing';
                        ?>
                        <div class="eca-track-copy is-info" style="margin-bottom:14px;">
                            <p class="eca-track-copy-title"><?= htmlspecialchars($label, ENT_QUOTES, 'UTF-8') ?></p>
                            <p><?= htmlspecialchars($reason, ENT_QUOTES, 'UTF-8') ?> · <?= $isOpen ? 'Waiting for upload' : 'Uploaded' ?></p>
                            <?php if (!empty($row['note'])): ?>
                                <p><?= htmlspecialchars((string) $row['note'], ENT_QUOTES, 'UTF-8') ?></p>
                            <?php endif; ?>
                            <?php if ($isOpen && $canWork && $status === 'ADDITIONAL INFORMATION REQUIRED'): ?>
                                <form method="post" enctype="multipart/form-data" style="margin-top:10px;">
                                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf, ENT_QUOTES, 'UTF-8') ?>">
                                    <input type="hidden" name="ref" value="<?= htmlspecialchars($ref, ENT_QUOTES, 'UTF-8') ?>">
                                    <input type="hidden" name="email" value="<?= htmlspecialchars($email, ENT_QUOTES, 'UTF-8') ?>">
                                    <input type="hidden" name="action" value="upload">
                                    <input type="hidden" name="request_id" value="<?= (int) $row['id'] ?>">
                                    <input class="form-control" type="file" name="document" accept=".pdf,.png,.jpg,.jpeg" required>
                                    <button class="btn eca-track-submit" type="submit" style="margin-top:10px;">Upload <?= htmlspecialchars($label, ENT_QUOTES, 'UTF-8') ?></button>
                                </form>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>

                    <?php if ($canWork && $status === 'ADDITIONAL INFORMATION REQUIRED'): ?>
                        <form method="post">
                            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf, ENT_QUOTES, 'UTF-8') ?>">
                            <input type="hidden" name="ref" value="<?= htmlspecialchars($ref, ENT_QUOTES, 'UTF-8') ?>">
                            <input type="hidden" name="email" value="<?= htmlspecialchars($email, ENT_QUOTES, 'UTF-8') ?>">
                            <input type="hidden" name="action" value="resubmit">
                            <button class="btn eca-track-submit" type="submit"<?= $open ? ' disabled' : '' ?>>
                                Resend application <?= htmlspecialchars($ref, ENT_QUOTES, 'UTF-8') ?>
                            </button>
                        </form>
                        <?php if ($open): ?>
                            <p class="eca-track-help">Upload every requested document before resending.</p>
                        <?php endif; ?>
                    <?php endif; ?>
                </section>
            <?php endif; ?>

            <p class="eca-track-next">
                <a href="/track.php?ref=<?= urlencode($ref) ?>">Track this application</a>
                <?php if ($canWork && !empty($_SESSION['eca_member'])): ?>
                    · <a href="/client/applications.php">Member applications</a>
                <?php endif; ?>
            </p>
        <?php endif; ?>
    </div>
</div>
<?php eca_public_page_end(); ?>
