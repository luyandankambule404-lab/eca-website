<?php
require_once __DIR__ . '/includes/session.php';
require_once __DIR__ . '/includes/portal-db.php';
require_once __DIR__ . '/includes/public-page.php';
require_once __DIR__ . '/includes/application-track.php';

$rawRef = (string) ($_GET['ref'] ?? $_POST['ref'] ?? '');
$submitted = array_key_exists('ref', $_GET) || array_key_exists('ref', $_POST);
$ref = eca_track_normalize_reference($rawRef);

$error = '';
$unavailable = false;
$app = null;
$timeline = [];
$history = [];
$events = [];
$copy = null;
$certificateNumber = '';
$membershipNumber = '';
$memberSignedIn = !empty($_SESSION['eca_member']);

if ($submitted && $ref === '') {
    $error = 'Please enter an application reference.';
} elseif ($submitted && !eca_track_reference_valid($ref)) {
    $error = 'Enter the reference in the format ECA-APP-YYYY-NNNN. Example: ECA-APP-2026-0001.';
} elseif ($submitted) {
    $ip = (string) ($_SERVER['REMOTE_ADDR'] ?? 'unknown');
    if (eca_rate_limit_exceeded('track-lookup', $ip, 20, 900)) {
        $error = 'Please wait a moment and try again.';
    } else {
        try {
            $conn = eca_portal_pdo(false);
            if (!$conn) {
                $unavailable = true;
                $error = 'Application tracking is temporarily unavailable. Please try again shortly.';
            } else {
                $app = eca_track_lookup($conn, $ref);
                if (!$app) {
                    $error = 'We couldn\'t find an application with that reference. Please check the reference number and try again.';
                } else {
                    $status = (string) $app['application_status'];
                    $history = eca_track_history((int) $app['client_id']);
                    $timeline = eca_track_timeline($status, (string) $app['created_at'], $history);
                    $events = eca_track_public_events((string) $app['created_at'], $history);
                    $copy = eca_track_status_copy($status);
                    if ($status === 'APPROVED') {
                        $membershipNumber = (string) $app['membership_number'];
                        $certificateNumber = eca_track_active_certificate_number($conn, (int) $app['client_id']);
                    }
                }
            }
        } catch (Throwable $e) {
            $unavailable = true;
            $error = 'Application tracking is temporarily unavailable. Please try again shortly.';
        }
    }
}

$found = is_array($app);
$status = $found ? (string) $app['application_status'] : '';
$statusLabel = $found ? eca_track_status_label($status) : '';
$submittedLong = $found ? eca_track_date_long((string) $app['created_at']) : '';
$submittedIso = $found ? eca_track_date_iso((string) $app['created_at']) : '';
$statusClass = [
    'SUBMITTED' => 'is-submitted',
    'UNDER REVIEW' => 'is-review',
    'ADDITIONAL INFORMATION REQUIRED' => 'is-info',
    'APPROVED' => 'is-approved',
    'REJECTED' => 'is-rejected',
][$status] ?? 'is-submitted';

eca_public_page_start(
    'Application tracking',
    'Membership',
    'Track Your ECA Application',
    'Enter your application reference number to view the current progress of your application.',
    'Track'
);
?>
<div class="container-xxl py-4 eca-track-page">
    <div class="eca-track">
        <section class="eca-track-card eca-form-panel" aria-labelledby="eca-track-form-heading">
            <h2 id="eca-track-form-heading" class="eca-track-card-title">Track application</h2>
            <form class="eca-track-form" method="get" action="/track.php" novalidate>
                <div class="eca-track-field mb-3">
                    <label class="form-label" for="ref">Application Reference</label>
                    <input
                        class="form-control<?= $error !== '' && !$unavailable && !$found ? ' is-invalid' : '' ?>"
                        id="ref"
                        name="ref"
                        value="<?= htmlspecialchars($ref, ENT_QUOTES, 'UTF-8') ?>"
                        placeholder="ECA-APP-2026-0001"
                        autocomplete="off"
                        spellcheck="false"
                        inputmode="text"
                        autocapitalize="characters"
                        maxlength="20"
                        aria-describedby="ref-help<?= $error !== '' ? ' ref-error' : '' ?>"
                        aria-invalid="<?= $error !== '' && !$found ? 'true' : 'false' ?>"
                    >
                    <p class="eca-track-help" id="ref-help">Example: ECA-APP-2026-0001</p>
                </div>
                <button class="btn btn-primary eca-track-submit" type="submit">Track Application</button>
            </form>
            <?php if ($error !== ''): ?>
                <p class="eca-track-alert" id="ref-error" role="alert"><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?></p>
            <?php endif; ?>
        </section>

        <?php if ($found && $copy): ?>
            <section class="eca-track-card eca-track-result" aria-labelledby="eca-track-result-heading">
                <p class="eca-kicker">Application status</p>
                <h2 id="eca-track-result-heading" class="eca-track-ref"><?= htmlspecialchars((string) $app['application_reference'], ENT_QUOTES, 'UTF-8') ?></h2>
                <p class="eca-track-badge <?= htmlspecialchars($statusClass, ENT_QUOTES, 'UTF-8') ?>">
                    <span class="eca-sr-only">Status: </span><?= htmlspecialchars($statusLabel, ENT_QUOTES, 'UTF-8') ?>
                </p>
                <?php if ($submittedLong !== ''): ?>
                    <p class="eca-track-submitted">
                        Submitted
                        <time datetime="<?= htmlspecialchars($submittedIso, ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars($submittedLong, ENT_QUOTES, 'UTF-8') ?></time>
                    </p>
                <?php endif; ?>
                <div class="eca-track-copy <?= htmlspecialchars($statusClass, ENT_QUOTES, 'UTF-8') ?>">
                    <p class="eca-track-copy-title"><?= htmlspecialchars((string) $copy['title'], ENT_QUOTES, 'UTF-8') ?></p>
                    <p><?= htmlspecialchars((string) $copy['message'], ENT_QUOTES, 'UTF-8') ?></p>
                </div>

                <?php if ($status === 'APPROVED' && ($membershipNumber !== '' || $certificateNumber !== '')): ?>
                    <div class="eca-track-next">
                        <?php if ($membershipNumber !== ''): ?>
                            <p>Membership number <?= htmlspecialchars($membershipNumber, ENT_QUOTES, 'UTF-8') ?> is on record for this approved application.</p>
                        <?php endif; ?>
                        <?php if ($certificateNumber !== ''): ?>
                            <p>
                                <a href="/verify.php?cert=<?= urlencode($certificateNumber) ?>">Verify the public membership certificate</a>
                            </p>
                        <?php else: ?>
                            <p>No active public certificate has been issued for this membership yet.</p>
                        <?php endif; ?>
                    </div>
                <?php elseif ($status === 'APPROVED'): ?>
                    <div class="eca-track-next">
                        <p>This application is approved. Membership and certificate details will appear here once they are on record.</p>
                    </div>
                <?php endif; ?>

                <?php if ($status === 'ADDITIONAL INFORMATION REQUIRED'): ?>
                    <div class="eca-track-next">
                        <p>
                            Use application registration number
                            <strong><?= htmlspecialchars((string) $app['application_reference'], ENT_QUOTES, 'UTF-8') ?></strong>
                            to upload the missing or replacement document and resend the application.
                        </p>
                        <p>
                            <a href="/application-fix.php?ref=<?= urlencode((string) $app['application_reference']) ?>">Upload documents and resend</a>
                            <?php if ($memberSignedIn): ?>
                                or <a href="/client/applications.php">open your member portal applications</a>
                            <?php else: ?>
                                or <a href="<?= htmlspecialchars(eca_login_url('member', '/client/applications.php'), ENT_QUOTES, 'UTF-8') ?>">sign in to the member portal</a>
                            <?php endif; ?>
                            or <a href="/contact.php">contact ECA</a>.
                        </p>
                    </div>
                <?php endif; ?>

                <?php if ($status === 'REJECTED'): ?>
                    <div class="eca-track-next">
                        <p>This application was not approved. If you need clarification, <a href="/contact.php">contact ECA</a> and quote your application reference.</p>
                    </div>
                <?php endif; ?>

                <?php if (in_array($status, ['SUBMITTED', 'UNDER REVIEW'], true)): ?>
                    <div class="eca-track-next">
                        <p>No further action is required while ECA reviews this application. Keep your reference number for status updates.</p>
                    </div>
                <?php endif; ?>

                <ol class="eca-track-timeline" aria-label="Application progress">
                    <?php foreach ($timeline as $step): ?>
                        <?php
                        $state = (string) $step['state'];
                        $icon = [
                            'complete' => 'bi-check-lg',
                            'current' => 'bi-record-circle',
                            'upcoming' => 'bi-circle',
                            'rejected' => 'bi-x-lg',
                        ][$state] ?? 'bi-circle';
                        ?>
                        <li class="is-<?= htmlspecialchars($state, ENT_QUOTES, 'UTF-8') ?>"<?= $state === 'current' ? ' aria-current="step"' : '' ?>>
                            <span class="eca-track-step-icon" aria-hidden="true"><i class="bi <?= $icon ?>"></i></span>
                            <div class="eca-track-step-body">
                                <span class="eca-track-step-label"><?= htmlspecialchars((string) $step['label'], ENT_QUOTES, 'UTF-8') ?></span>
                                <small>
                                    <span class="eca-track-step-state"><?= htmlspecialchars((string) $step['state_label'], ENT_QUOTES, 'UTF-8') ?></span>
                                    <?php if ($step['date'] !== ''): ?>
                                        <time datetime="<?= htmlspecialchars((string) $step['iso'], ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars((string) $step['date'], ENT_QUOTES, 'UTF-8') ?></time>
                                    <?php endif; ?>
                                </small>
                            </div>
                        </li>
                    <?php endforeach; ?>
                </ol>

                <?php if ($events): ?>
                    <h3 class="eca-track-history-title">Recorded progress</h3>
                    <ol class="eca-track-history">
                        <?php foreach ($events as $event): ?>
                            <li>
                                <time datetime="<?= htmlspecialchars(eca_track_date_iso((string) $event['at']), ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars(eca_track_date_short((string) $event['at']), ENT_QUOTES, 'UTF-8') ?></time>
                                <span><?= htmlspecialchars((string) $event['label'], ENT_QUOTES, 'UTF-8') ?></span>
                            </li>
                        <?php endforeach; ?>
                    </ol>
                <?php endif; ?>
            </section>
        <?php endif; ?>
    </div>
</div>
<?php eca_public_page_end(); ?>
