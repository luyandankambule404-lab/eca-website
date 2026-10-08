<?php
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/_hub.php';
require_once __DIR__ . '/../includes/portal-db.php';
require_once __DIR__ . '/../includes/audit.php';
require_once __DIR__ . '/../includes/notify.php';
require_once __DIR__ . '/../includes/admin-stats.php';
require_once __DIR__ . '/../includes/admin-ops.php';
require_once __DIR__ . '/../includes/documents.php';
require_once __DIR__ . '/../includes/membership.php';
eca_admin_require('members.manage');
header('Cache-Control: no-store');

$id = (int) ($_GET['id'] ?? 0);
$conn = eca_portal_pdo(false);
if (!$conn || $id < 1) {
    eca_not_found('Member not found.');
}
$load = static function (PDO $conn, int $id): ?array {
    $stmt = $conn->prepare('SELECT * FROM tbl_client WHERE client_id = ? LIMIT 1');
    $stmt->execute([$id]);
    return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
};
$row = $load($conn, $id);
if (!$row) {
    eca_not_found('Member not found.');
}
$notice = '';
$noticeError = false;
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!eca_admin_csrf_ok($_POST['csrf_token'] ?? null)) {
        $notice = 'Your session expired. Please try again.';
        $noticeError = true;
    } elseif (!eca_can('members.manage') && !eca_can('members.edit')) {
        eca_forbid();
    } else {
        $action = (string) ($_POST['action'] ?? '');
        $adminEmail = (string) (eca_admin_user()['email'] ?? '');
        if ($action === 'standing') {
            $next = (string) ($_POST['standing'] ?? '');
            $map = [
                'approve' => ['Active', 'Renewal'],
                'suspend' => ['Suspended', (string) ($row['Status'] ?? 'Renewal')],
                'reactivate' => ['Active', (string) ($row['Status'] ?? 'Renewal')],
            ];
            if (isset($map[$next])) {
                $conn->prepare('UPDATE tbl_client SET active = ?, Status = ? WHERE client_id = ?')
                    ->execute([$map[$next][0], $map[$next][1], $id]);
                eca_audit('member.' . $next, 'tbl_client', (string) $id, [
                    'standing' => $map[$next][0],
                    'membership_type' => $map[$next][1],
                ]);
                eca_notify([
                    'client_id' => $id,
                    'membership_number' => (string) ($row['MembershipNumber'] ?? ''),
                    'title' => 'Membership update',
                    'message' => 'Your membership standing is now ' . $map[$next][0] . '.',
                    'type' => 'MEMBERSHIP',
                    'link' => '/client/profile.php',
                ]);
                $notice = 'Standing updated.';
                $row = $load($conn, $id) ?? $row;
            }
        } elseif ($action === 'note') {
            $note = trim((string) ($_POST['note'] ?? ''));
            if ($note !== '') {
                $conn->prepare('INSERT INTO membership_application_notes (client_id, admin_email, note) VALUES (?,?,?)')
                    ->execute([$id, $adminEmail, $note]);
                eca_audit('member.note', 'tbl_client', (string) $id);
                $notice = 'Note saved.';
            }
        }
    }
}

$docs = $conn->prepare('SELECT id, document_type, original_name, file_name, reviewed_at FROM tbl_client_documents WHERE client_id = ? ORDER BY id DESC LIMIT 100');
$docs->execute([$id]);
$docs = $docs->fetchAll(PDO::FETCH_ASSOC);
$certs = $conn->prepare('SELECT * FROM membership_certificates WHERE client_id = ? ORDER BY id DESC LIMIT 100');
$certs->execute([$id]);
$certs = $certs->fetchAll(PDO::FETCH_ASSOC);
$pays = [];
try {
    $membership = (string) ($row['MembershipNumber'] ?? '');
    $uid = 0;
    if ($membership !== '') {
        $u = $conn->prepare('SELECT id FROM userss WHERE membership_number = ? LIMIT 1');
        $u->execute([$membership]);
        $uid = (int) $u->fetchColumn();
    }
    if ($uid > 0) {
        $p = $conn->prepare('SELECT id, payment_year, status, payment_date FROM payments WHERE user_id = ? ORDER BY id DESC LIMIT 100');
        $p->execute([$uid]);
        $pays = $p->fetchAll(PDO::FETCH_ASSOC);
    }
} catch (Throwable $e) {
    $pays = [];
}
$notes = $conn->prepare('SELECT * FROM membership_application_notes WHERE client_id = ? ORDER BY id DESC LIMIT 100');
$notes->execute([$id]);
$notes = $notes->fetchAll(PDO::FETCH_ASSOC);
$years = [];
try {
    $yearStmt = $conn->prepare(
        'SELECT year, type, status, payment_date, expiry_date, certificate_number
         FROM membership_years WHERE client_id = ? ORDER BY year DESC, id DESC LIMIT 50'
    );
    $yearStmt->execute([$id]);
    $years = $yearStmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Throwable $e) {
    $years = [];
}
$cpd = eca_member_cpd_snapshot($conn, (string) ($row['MembershipNumber'] ?? ''));
$wellness = eca_member_wellness_snapshot(eca_admin_db(), (string) ($row['MembershipNumber'] ?? ''), $id);
$history = [];
if (eca_can('audit.view')) {
    $history = eca_hub_audit_for('tbl_client', (string) $id, 20);
}
$csrf = eca_admin_csrf();
$latestYear = $years[0] ?? null;
eca_admin_hub_start('Member', 'members');
?>
<div class="hub-hello">
    <h1 class="hub-hello-title"><?= eca_admin_h($row['TradingName'] ?? $row['CompanyRegistrationName'] ?? '') ?></h1>
    <p><?= eca_admin_h($row['MembershipNumber'] ?: 'No membership number') ?> · standing <?= eca_admin_h($row['active'] ?? '') ?> · type <?= eca_admin_h($row['Status'] ?? '') ?></p>
</div>
<?php if ($notice): ?><p class="hub-card"<?= $noticeError ? ' style="border-color:#b42318;"' : '' ?>><?= eca_admin_h($notice) ?></p><?php endif; ?>
<div class="hub-card eca-form-panel">
    <div class="eca-apply-head">
        <h1>Member</h1>
        <a href="/admin/members.php">Back</a>
    </div>
    <h5>Company</h5>
    <div class="eca-field-grid">
        <div>
            <label class="form-label">Registered name</label>
            <div class="eca-field-value"><?= eca_admin_h($row['CompanyRegistrationName'] ?? '') ?></div>
        </div>
        <div>
            <label class="form-label">Trading name</label>
            <div class="eca-field-value"><?= eca_admin_h($row['TradingName'] ?? '') ?></div>
        </div>
        <div>
            <label class="form-label">Membership number</label>
            <div class="eca-field-value"><?= eca_admin_h($row['MembershipNumber'] ?: '—') ?></div>
        </div>
        <div>
            <label class="form-label">Email</label>
            <div class="eca-field-value"><?= eca_admin_h($row['EmailAddress'] ?? '') ?></div>
        </div>
        <div>
            <label class="form-label">Cell</label>
            <div class="eca-field-value"><?= eca_admin_h($row['Cellphone'] ?: ($row['telephone'] ?? '')) ?></div>
        </div>
        <div>
            <label class="form-label">Classification</label>
            <div class="eca-field-value"><?= eca_admin_h($row['Clasification'] ?? '') ?></div>
        </div>
        <div>
            <label class="form-label">Standing (active)</label>
            <div class="eca-field-value"><?= eca_admin_h($row['active'] ?? '') ?></div>
        </div>
        <div>
            <label class="form-label">Membership type (Status)</label>
            <div class="eca-field-value"><?= eca_admin_h($row['Status'] ?? '') ?></div>
        </div>
        <div>
            <label class="form-label">Registered</label>
            <div class="eca-field-value"><?= eca_admin_h($row['DateOfRegistration'] ?? '') ?></div>
        </div>
        <div>
            <label class="form-label">Certificate number</label>
            <div class="eca-field-value"><?= eca_admin_h($row['CertificateNumber'] ?? '') ?></div>
        </div>
        <div>
            <label class="form-label">Latest membership year</label>
            <div class="eca-field-value"><?= eca_admin_h($latestYear['year'] ?? '—') ?>
                · <?= eca_admin_h($latestYear['type'] ?? '') ?>
                · expiry <?= eca_admin_h($latestYear['expiry_date'] ?? '—') ?></div>
        </div>
        <div>
            <label class="form-label">Application</label>
            <div class="eca-field-value">
                <?php if (trim((string) ($row['application_reference'] ?? '')) !== ''): ?>
                    <a href="/admin/application-detail.php?id=<?= $id ?>"><?= eca_admin_h($row['application_reference']) ?></a>
                    · <?= eca_admin_h($row['application_status'] ?? '') ?>
                <?php else: ?>none<?php endif; ?>
            </div>
        </div>
        <div class="eca-field-full">
            <label class="form-label">Physical address</label>
            <div class="eca-field-value eca-field-value-area"><?= eca_admin_h(trim((string) ($row['Region'] ?? '') . ' ' . (string) ($row['address'] ?? ''))) ?></div>
        </div>
    </div>
    <form method="post" class="eca-apply-nav">
        <input type="hidden" name="csrf_token" value="<?= eca_admin_h($csrf) ?>">
        <input type="hidden" name="action" value="standing">
        <button class="hub-btn" name="standing" value="approve" type="submit" onclick="return confirm('Set standing to Active?');">Approve standing</button>
        <button class="hub-btn" name="standing" value="suspend" type="submit" onclick="return confirm('Suspend this member?');">Suspend</button>
        <button class="hub-btn" name="standing" value="reactivate" type="submit" onclick="return confirm('Reactivate this member?');">Reactivate</button>
    </form>
    <p class="form-text">Approve standing sets <code>active</code> to Active and <code>Status</code> to Renewal (existing membership-type workflow). <code>Status</code> is not the standing field.</p>
    <div class="eca-apply-nav">
        <a class="eca-btn-back" href="/admin/members.php">Back</a>
        <a class="eca-btn-next" href="/verify.php?m=<?= urlencode((string) ($row['MembershipNumber'] ?? '')) ?>">Public verify</a>
    </div>
</div>
<div class="hub-card">
    <h2>CPD and wellness</h2>
    <p>CPD applications for this membership number: <?= (int) $cpd['applications'] ?> (<?= (int) $cpd['pending'] ?> pending). Ledger points: <?= eca_admin_h(rtrim(rtrim(number_format((float) ($cpd['points'] ?? 0), 1, '.', ''), '0'), '.') ?: '0') ?>. Open the Hub <a href="/admin/cpd.php">CPD</a> page to review the local lists. Super Admin is a separate dashboard.</p>
    <p>Wellness event registrations: <?= (int) $wellness['registrations'] ?>.</p>
</div>
<div class="hub-card eca-table-panel">
    <h2 class="hub-doc-heading">Documents</h2>
    <table class="hub-table">
        <thead><tr><th>Document</th><th>Status</th><th></th></tr></thead>
        <tbody>
        <?php
        $memberReturn = '/admin/member-detail.php?id=' . $id;
        foreach ($docs as $doc):
        ?>
            <tr>
                <td>
                    <span class="hub-doc-type"><?= eca_admin_h(eca_document_type_label((string) ($doc['document_type'] ?? ''))) ?></span>
                    <span class="hub-doc-file"><?= eca_admin_h($doc['original_name'] ?: 'On file') ?></span>
                </td>
                <td><?php eca_admin_document_status_html($doc['reviewed_at'] ?? null); ?></td>
                <td><?php eca_admin_document_actions($doc, $csrf, ['return' => $memberReturn]); ?></td>
            </tr>
        <?php endforeach; ?>
        <?php if (!$docs): ?><tr data-hub-empty-row><td colspan="3">No documents.</td></tr><?php endif; ?>
        </tbody>
    </table>
</div>
<div class="hub-card eca-table-panel">
    <h5>Certificates</h5>
    <table class="hub-table">
        <thead><tr><th>Number</th><th>Status</th><th>Issued</th><th></th></tr></thead>
        <tbody>
        <?php foreach ($certs as $cert): ?>
            <tr>
                <td><?= eca_admin_h($cert['certificate_number'] ?? '') ?></td>
                <td><?= eca_admin_h($cert['status'] ?? '') ?></td>
                <td><?= eca_admin_h($cert['issued_at'] ?? '') ?></td>
                <td>
                    <a href="/certificate-download.php?id=<?= (int) $cert['id'] ?>">Download</a>
                    · <a href="/verify.php?cert=<?= urlencode((string) ($cert['certificate_number'] ?? '')) ?>">Verify</a>
                </td>
            </tr>
        <?php endforeach; ?>
        <?php if (!$certs): ?><tr data-hub-empty-row><td colspan="4">No certificates. Generate from Applications or Certificates.</td></tr><?php endif; ?>
        </tbody>
    </table>
</div>
<div class="hub-card eca-table-panel">
    <h5>Payment proofs</h5>
    <p>These are proof/status rows, not monetary totals.</p>
    <table class="hub-table">
        <thead><tr><th>Year</th><th>Status</th><th>Date</th><th></th></tr></thead>
        <tbody>
        <?php foreach ($pays as $pay): ?>
            <tr>
                <td><?= eca_admin_h($pay['payment_year'] ?? '') ?></td>
                <td><?= eca_admin_h(eca_payment_label((string) ($pay['status'] ?? ''))) ?></td>
                <td><?= eca_admin_h($pay['payment_date'] ?? '') ?></td>
                <td><a href="/admin/payment-detail.php?id=<?= (int) $pay['id'] ?>">Open</a></td>
            </tr>
        <?php endforeach; ?>
        <?php if (!$pays): ?><tr data-hub-empty-row><td colspan="4">No payment proofs for this membership login.</td></tr><?php endif; ?>
        </tbody>
    </table>
</div>
<div class="hub-card eca-table-panel">
    <h5>Membership years</h5>
    <p>These are existing <code>membership_years</code> rows. Year state (current / expiring / expired / pending renewal) is derived from those dates and statuses — no dates are invented here.</p>
    <table class="hub-table">
        <thead><tr><th>Year</th><th>Type</th><th>Status</th><th>Payment date</th><th>Expiry</th><th>Certificate</th></tr></thead>
        <tbody>
        <?php foreach ($years as $year): ?>
            <tr>
                <td><?= eca_admin_h($year['year'] ?? '') ?></td>
                <td><?= eca_admin_h($year['type'] ?? '') ?></td>
                <td><?= eca_admin_h($year['status'] ?? '') ?></td>
                <td><?= eca_admin_h($year['payment_date'] ?? '') ?></td>
                <td><?= eca_admin_h($year['expiry_date'] ?? '') ?></td>
                <td><?= eca_admin_h($year['certificate_number'] ?? '') ?></td>
            </tr>
        <?php endforeach; ?>
        <?php if (!$years): ?><tr data-hub-empty-row><td colspan="6">No membership year rows.</td></tr><?php endif; ?>
        </tbody>
    </table>
</div>
<?php if ($history): ?>
<div class="hub-card">
    <h2>Hub history</h2>
    <ul class="hub-activity">
        <?php foreach ($history as $item): ?>
            <li>
                <div>
                    <strong><?= eca_admin_h(eca_admin_audit_label((string) ($item['action'] ?? ''))) ?></strong>
                    <p><?= eca_admin_h($item['actor_email'] ?: 'system') ?></p>
                </div>
                <time><?= eca_admin_h($item['created_at'] ?? '') ?></time>
            </li>
        <?php endforeach; ?>
    </ul>
</div>
<?php endif; ?>
<div class="hub-card">
    <h2>Internal notes</h2>
    <form method="post">
        <input type="hidden" name="csrf_token" value="<?= eca_admin_h($csrf) ?>">
        <input type="hidden" name="action" value="note">
        <label class="form-label" for="member-note">Internal note</label>
        <textarea class="form-control" id="member-note" name="note" rows="3"></textarea>
        <button class="hub-btn" type="submit" style="margin-top:10px;">Add note</button>
    </form>
    <?php foreach ($notes as $note): ?>
        <p><strong><?= eca_admin_h($note['admin_email'] ?? '') ?></strong> · <?= eca_admin_h($note['created_at'] ?? '') ?><br><?= eca_admin_h($note['note'] ?? '') ?></p>
    <?php endforeach; ?>
</div>
<?php eca_admin_hub_end(); ?>
