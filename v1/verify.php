<?php
require_once __DIR__ . '/includes/session.php';
require_once __DIR__ . '/includes/portal-db.php';
require_once __DIR__ . '/includes/membership.php';
require_once __DIR__ . '/includes/public-page.php';
require_once __DIR__ . '/includes/public-seo.php';

$membership = trim((string) ($_GET['m'] ?? $_POST['membership'] ?? ''));
$cert = trim((string) ($_GET['cert'] ?? $_POST['cert'] ?? ''));
$result = null;
$publicStatus = '';
$isVerified = false;
$year = null;
$searched = $membership !== '' || $cert !== '';
$revokedCert = false;
$rateLimited = false;

$conn = eca_portal_pdo(false);
if ($searched && $conn) {
    $ip = (string) ($_SERVER['REMOTE_ADDR'] ?? 'unknown');
    if (eca_rate_limit_exceeded('verify-lookup', $ip, 30, 900)) {
        $rateLimited = true;
        $searched = true;
        $conn = null;
    }
}
if ($searched && !$rateLimited && $conn) {
    if ($cert !== '') {
        try {
            $revStmt = $conn->prepare(
                'SELECT status FROM membership_certificates WHERE certificate_number = ? ORDER BY id DESC LIMIT 1'
            );
            $revStmt->execute([$cert]);
            $revokedCert = strtoupper((string) $revStmt->fetchColumn()) === 'REVOKED';
        } catch (Throwable $e) {
            $revokedCert = false;
        }
    }
    $result = eca_find_client_for_verify($conn, $membership, $cert);
    if ($result) {
        $year = eca_latest_membership_year($conn, $result);
        $publicStatus = eca_public_membership_status($result, $year);
        $isVerified = in_array(strtolower($publicStatus), ['active', 'approved', 'verified'], true);
        if ($publicStatus === 'Not found') {
            $result = null;
        }
    }
}

$shareUrl = '';
if ($result && !empty($result['MembershipNumber'])) {
    $shareUrl = eca_public_url('/verify.php?m=' . rawurlencode((string) $result['MembershipNumber']));
}

eca_public_page_start(
    'Membership verification',
    'Members',
    'Verify a membership',
    'Enter an ECA membership number or certificate number. Only public standing is shown. Revoked certificates are not verified.',
    'Verify'
);
?>
<div class="container-xxl py-4">
    <div class="row justify-content-center">
        <div class="col-lg-6">
            <div class="card border-0 eca-form-panel">
                <div class="card-body">
                    <h5 class="text-primary">Verify membership</h5>
                    <form method="get" action="/verify.php" class="mb-0">
                        <div class="mb-3">
                            <label class="form-label" for="membership">Membership number</label>
                            <input class="form-control" id="membership" name="m" autocomplete="off" value="<?= htmlspecialchars($membership, ENT_QUOTES, 'UTF-8') ?>" placeholder="e.g. ECA1001">
                        </div>
                        <div class="mb-3">
                            <label class="form-label" for="cert">Certificate number <span class="text-muted">(optional)</span></label>
                            <input class="form-control" id="cert" name="cert" autocomplete="off" value="<?= htmlspecialchars($cert, ENT_QUOTES, 'UTF-8') ?>" placeholder="Certificate number">
                        </div>
                        <button class="btn btn-primary w-100" type="submit">Verify</button>
                    </form>
                </div>
            </div>
            <?php if ($rateLimited): ?>
                <div class="eca-verification-result is-not-found mt-4" role="status">
                    <span class="eca-verification-icon" aria-hidden="true"><i class="bi bi-hourglass-split"></i></span>
                    <div>
                        <strong>Please wait</strong>
                        <p>Too many verification attempts from this network. Please wait a few minutes and try again.</p>
                    </div>
                </div>
            <?php elseif ($searched && !$result): ?>
                <div class="eca-verification-result is-not-found mt-4" role="status">
                    <span class="eca-verification-icon" aria-hidden="true"><i class="bi bi-x-lg"></i></span>
                    <div>
                        <strong><?= $revokedCert ? 'Certificate revoked' : 'Not verified' ?></strong>
                        <p><?= $revokedCert
                            ? 'This certificate number is recorded as revoked and is not a valid public verification.'
                            : 'No matching public ECA membership record was found.' ?></p>
                    </div>
                </div>
            <?php elseif ($result): ?>
                <div class="card company-card shadow-sm mt-4">
                    <div class="eca-verification-result <?= $isVerified ? 'is-verified' : 'is-not-found' ?>" role="status">
                        <span class="eca-verification-icon" aria-hidden="true"><i class="bi <?= $isVerified ? 'bi-check-lg' : 'bi-exclamation-lg' ?>"></i></span>
                        <div>
                            <strong><?= $isVerified ? 'Verified ECA member' : htmlspecialchars($publicStatus, ENT_QUOTES, 'UTF-8') ?></strong>
                            <p><?= htmlspecialchars((string) ($result['TradingName'] ?? $result['CompanyRegistrationName'] ?? 'Member'), ENT_QUOTES, 'UTF-8') ?></p>
                        </div>
                    </div>
                    <div class="card-body">
                        <p><strong>Membership number:</strong> <?= htmlspecialchars((string) ($result['MembershipNumber'] ?? ''), ENT_QUOTES, 'UTF-8') ?></p>
                        <p><strong>Classification:</strong> <?= htmlspecialchars((string) ($result['Clasification'] ?? ''), ENT_QUOTES, 'UTF-8') ?></p>
                        <p><strong>Status:</strong> <?= htmlspecialchars($publicStatus, ENT_QUOTES, 'UTF-8') ?></p>
                        <?php if (!empty($year['expiry_date'])): ?>
                            <p><strong>Expiry:</strong> <?= htmlspecialchars((string) $year['expiry_date'], ENT_QUOTES, 'UTF-8') ?></p>
                        <?php endif; ?>
                        <?php if ($shareUrl !== ''): ?>
                            <label class="form-label" for="verification-link">Shareable verification link</label>
                            <input id="verification-link" class="form-control" type="url" readonly value="<?= htmlspecialchars($shareUrl, ENT_QUOTES, 'UTF-8') ?>">
                        <?php endif; ?>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>
<?php eca_public_page_end(); ?>
