<?php
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/_hub.php';
require_once __DIR__ . '/../includes/portal-db.php';
require_once __DIR__ . '/../includes/documents.php';
require_once __DIR__ . '/../includes/audit.php';
require_once __DIR__ . '/../includes/notify.php';
require_once __DIR__ . '/../includes/admin-stats.php';
require_once __DIR__ . '/../includes/membership.php';
eca_admin_require('payments.manage');
header('Cache-Control: no-store');

$id = (int) ($_GET['id'] ?? 0);
$conn = eca_portal_pdo(false);
if (!$conn || $id < 1) {
    eca_not_found('Payment not found.');
}

$row = null;
try {
    $stmt = $conn->prepare(
        'SELECT p.*, u.membership_number, u.full_name, u.email
         FROM payments p
         LEFT JOIN userss u ON u.id = p.user_id
         WHERE p.id = ?
         LIMIT 1'
    );
    $stmt->execute([$id]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
} catch (Throwable $e) {
    error_log('payment-detail load failed: ' . $e->getMessage());
    $row = null;
}
if (!$row) {
    eca_not_found('Payment not found.');
}

$notice = '';
$noticeError = false;
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!eca_admin_csrf_ok($_POST['csrf_token'] ?? null)) {
        $notice = 'Your session expired. Please try again.';
        $noticeError = true;
    } else {
        $next = strtolower((string) ($_POST['status'] ?? ''));
        if (in_array($next, ['pending', 'approved', 'rejected'], true)) {
            try {
                $reason = trim((string) ($_POST['decline_reason'] ?? ''));
                $conn->prepare('UPDATE payments SET status = ?, decline_reason = ? WHERE id = ?')->execute([$next, $reason, $id]);
                $audit = $next === 'approved' ? 'payment.verified' : ('payment.' . $next);
                eca_audit($audit, 'payments', (string) $id, ['status' => $next]);
                eca_notify([
                    'membership_number' => (string) ($row['membership_number'] ?? ''),
                    'title' => 'Payment update',
                    'message' => 'Your payment for ' . ($row['payment_year'] ?? '') . ' is ' . eca_payment_label($next) . '.',
                    'type' => 'PAYMENT',
                    'link' => '/client/payments.php',
                ]);
                $stmt->execute([$id]);
                $row = $stmt->fetch(PDO::FETCH_ASSOC) ?: $row;
                if ($next === 'approved') {
                    $yearMemberId = 0;
                    $yearMembership = trim((string) ($row['membership_number'] ?? ''));
                    if ($yearMembership !== '') {
                        try {
                            $m = $conn->prepare('SELECT client_id FROM tbl_client WHERE MembershipNumber = ? LIMIT 1');
                            $m->execute([$yearMembership]);
                            $yearMemberId = (int) $m->fetchColumn();
                        } catch (Throwable $e) {
                            $yearMemberId = 0;
                        }
                    }
                    if ($yearMemberId > 0) {
                        eca_record_membership_year_from_payment(
                            $conn,
                            $yearMemberId,
                            $yearMembership,
                            (string) ($row['payment_year'] ?? ''),
                            (string) ($row['payment_date'] ?? '')
                        );
                    }
                }
                $notice = 'Payment proof ' . strtolower(eca_payment_label($next)) . '.';
            } catch (Throwable $e) {
                error_log('payment-detail update failed: ' . $e->getMessage());
                $notice = 'Payment status could not be updated.';
                $noticeError = true;
            }
        }
    }
}

$memberId = 0;
$membership = (string) ($row['membership_number'] ?? '');
if ($membership !== '') {
    try {
        $m = $conn->prepare('SELECT client_id FROM tbl_client WHERE MembershipNumber = ? LIMIT 1');
        $m->execute([$membership]);
        $memberId = (int) $m->fetchColumn();
    } catch (Throwable $e) {
        $memberId = 0;
    }
}

$proofKey = trim((string) ($row['proof_file'] ?? ''));
$proofDoc = $proofKey !== '' ? eca_find_payment_proof_document($conn, $proofKey) : null;
$proofLabel = $proofDoc
    ? (string) ($proofDoc['original_name'] ?? $proofDoc['file_name'] ?? $proofKey)
    : ($proofKey !== '' ? basename($proofKey) : '');
$proofViewUrl = $proofDoc
    ? '/admin/document-view.php?id=' . (int) $proofDoc['id'] . '&return=' . rawurlencode('/admin/payment-detail.php?id=' . $id)
    : '';
$proofDownloadUrl = $proofDoc
    ? '/document-download.php?id=' . (int) $proofDoc['id']
    : '';

$csrf = eca_admin_csrf();
eca_admin_hub_start('Payment', 'payments');
?>
<div class="hub-hello">
    <h1 class="hub-hello-title">Payment proof <?= (int) $row['id'] ?></h1>
    <p><?= eca_admin_h($row['membership_number'] ?? '') ?> · <?= eca_admin_h($row['payment_year'] ?? '') ?> · proof/status only, not a money total</p>
</div>
<?php if ($notice): ?><p class="hub-card"<?= $noticeError ? ' style="border-color:#b42318;"' : '' ?>><?= eca_admin_h($notice) ?></p><?php endif; ?>
<div class="hub-card eca-form-panel">
    <div class="eca-apply-head">
        <h1>Payment proof</h1>
        <a href="/admin/payments.php">Back</a>
    </div>
    <h5>Proof</h5>
    <div class="eca-field-grid">
        <div>
            <label class="form-label">Member</label>
            <div class="eca-field-value"><?= eca_admin_h($row['full_name'] ?? '') ?> · <?= eca_admin_h($row['membership_number'] ?? '') ?></div>
        </div>
        <div>
            <label class="form-label">Status</label>
            <div class="eca-field-value"><?= eca_admin_h(eca_payment_label((string) ($row['status'] ?? ''))) ?></div>
        </div>
        <div>
            <label class="form-label">Year</label>
            <div class="eca-field-value"><?= eca_admin_h($row['payment_year'] ?? '') ?></div>
        </div>
        <div>
            <label class="form-label">Date</label>
            <div class="eca-field-value"><?= eca_admin_h($row['payment_date'] ?? '') ?></div>
        </div>
        <div class="eca-field-full">
            <label class="form-label">Proof file</label>
            <div class="eca-field-value">
                <?php if ($proofViewUrl !== ''): ?>
                    <?= eca_admin_h($proofLabel !== '' ? $proofLabel : 'Payment proof') ?>
                    · <a href="<?= eca_admin_h($proofViewUrl) ?>">Open proof</a>
                    <?php if ($proofDownloadUrl !== ''): ?>
                        · <a href="<?= eca_admin_h($proofDownloadUrl) ?>">Download</a>
                    <?php endif; ?>
                <?php elseif ($proofLabel !== ''): ?>
                    <?= eca_admin_h($proofLabel) ?>
                    <span class="form-text"> (linked document file is not available in the local document store)</span>
                <?php else: ?>
                    none stored
                <?php endif; ?>
            </div>
        </div>
    </div>
    <form method="post">
        <input type="hidden" name="csrf_token" value="<?= eca_admin_h($csrf) ?>">
        <div class="eca-field-full" style="margin-bottom:14px;">
            <label class="form-label" for="decline_reason">Reason if rejected</label>
            <textarea class="form-control" id="decline_reason" name="decline_reason" rows="2" placeholder="Reason if rejected"><?= eca_admin_h($row['decline_reason'] ?? '') ?></textarea>
        </div>
        <div class="eca-apply-nav">
            <a class="eca-btn-back" href="/admin/payments.php">Back</a>
            <button class="hub-btn" name="status" value="pending" type="submit">Set pending</button>
            <button class="hub-btn" name="status" value="rejected" type="submit" onclick="return confirm('Reject this payment proof?');">Reject</button>
            <button class="hub-btn eca-btn-next" name="status" value="approved" type="submit" onclick="return confirm('Verify this payment proof?');">Verify</button>
        </div>
        <?php if ($memberId > 0): ?>
            <p class="form-text" style="margin-top:10px;"><a href="/admin/member-detail.php?id=<?= $memberId ?>">Open member</a></p>
        <?php endif; ?>
    </form>
</div>
<?php eca_admin_hub_end(); ?>
