<?php
require_once __DIR__ . '/_shell.php';
require_once __DIR__ . '/../includes/documents.php';
require_once __DIR__ . '/../includes/admin-stats.php';
require_once __DIR__ . '/../includes/audit.php';

$member = eca_portal_member();
$GLOBALS['eca_member'] = $member;
$portal = eca_db();
$notice = '';
$userId = 0;
if ($portal && !empty($member['membership'])) {
    $stmt = $portal->prepare('SELECT id FROM userss WHERE membership_number = ? LIMIT 1');
    $stmt->execute([(string) $member['membership']]);
    $userId = (int) $stmt->fetchColumn();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && eca_csrf_ok($_POST['csrf_token'] ?? null) && $portal && $userId > 0) {
    $year = preg_replace('/\D/', '', (string) ($_POST['payment_year'] ?? date('Y')));
    if (strlen($year) !== 4) {
        $year = date('Y');
    }
    $mysqli = eca_portal_mysqli(false);
    $file = $_FILES['proof'] ?? [];
    $stored = ($mysqli && !empty($file)) ? eca_store_private_upload($file, (int) ($member['client_id'] ?? 0), 'payment', $mysqli) : null;
    if ($stored) {
        $stmt = $portal->prepare('INSERT INTO payments (user_id, payment_year, payment_date, proof_file, status) VALUES (?,?,?,?,?)');
        $stmt->execute([$userId, $year, date('Y-m-d'), $stored['storage_key'], 'pending']);
        eca_audit('payment.upload', 'payments', (string) $portal->lastInsertId());
        $notice = 'Proof of payment submitted for verification.';
    } else {
        $notice = 'Upload a PDF, JPG or PNG under 2MB.';
    }
}

$rows = [];
if ($portal && $userId > 0) {
    $stmt = $portal->prepare('SELECT id, payment_year, status, payment_date FROM payments WHERE user_id = ? ORDER BY id DESC LIMIT 50');
    $stmt->execute([$userId]);
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
}
$csrf = eca_csrf_token();
eca_portal_start('Payments', 'payments');
?>
<div class="panel eca-form-panel eca-table-panel">
    <div class="eca-panel-heading"><h3>Proof of payment</h3></div>
    <div class="eca-panel-body">
        <?php if ($notice): ?><p><?= eca_h($notice) ?></p><?php endif; ?>
        <form method="post" enctype="multipart/form-data">
            <input type="hidden" name="csrf_token" value="<?= eca_h($csrf) ?>">
            <div class="eca-field-grid">
                <div>
                    <label class="form-label" for="payment_year">Year</label>
                    <input class="form-control" id="payment_year" name="payment_year" value="<?= eca_h(date('Y')) ?>">
                </div>
                <div>
                    <label class="form-label" for="proof">Proof (PDF, JPG or PNG)</label>
                    <input class="form-control" id="proof" type="file" name="proof" accept=".pdf,.jpg,.jpeg,.png" required>
                </div>
            </div>
            <div class="eca-apply-nav">
                <button class="btn-primary eca-btn-next" type="submit">Submit for verification</button>
            </div>
        </form>
        <p>These are proof-of-payment rows for this membership login. Status is pending, verified or rejected. Amounts are not shown because these records do not store a currency total.</p>
        <table class="hub-table">
            <thead><tr><th>Year</th><th>Status</th><th>Date</th></tr></thead>
            <tbody>
            <?php foreach ($rows as $row): ?>
                <tr>
                    <td><?= eca_h($row['payment_year'] ?? '') ?></td>
                    <td><?= eca_h(eca_payment_label((string) ($row['status'] ?? ''))) ?></td>
                    <td><?= eca_h($row['payment_date'] ?? '') ?></td>
                </tr>
            <?php endforeach; ?>
            <?php if (!$rows): ?><tr data-hub-empty-row><td colspan="3">No payment records yet.</td></tr><?php endif; ?>
            </tbody>
        </table>
        <?php if (!$userId): ?><p>No membership login record is linked for uploads.</p><?php endif; ?>
    </div>
</div>
<?php eca_portal_end(); ?>
