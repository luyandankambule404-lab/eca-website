<?php
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/_hub.php';
require_once __DIR__ . '/../includes/portal-db.php';
require_once __DIR__ . '/../includes/certificates.php';
require_once __DIR__ . '/../includes/audit.php';
require_once __DIR__ . '/../includes/notify.php';
require_once __DIR__ . '/../includes/pagination.php';
eca_admin_require('certificates.manage');
header('Cache-Control: no-store');

$conn = eca_portal_pdo(false);
$notice = '';
$noticeError = false;
if ($conn && $_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!eca_admin_csrf_ok($_POST['csrf_token'] ?? null)) {
        $notice = 'Your session expired. Please try again.';
        $noticeError = true;
    } else {
        $action = (string) ($_POST['action'] ?? '');
        $id = (int) ($_POST['id'] ?? 0);
        $clientId = (int) ($_POST['client_id'] ?? 0);
        $membership = trim((string) ($_POST['membership_number'] ?? ''));
        if ($action === 'revoke' && $id > 0) {
            $row = $conn->prepare('SELECT * FROM membership_certificates WHERE id = ? LIMIT 1');
            $row->execute([$id]);
            $cert = $row->fetch(PDO::FETCH_ASSOC);
            if ($cert) {
                $conn->prepare('UPDATE membership_certificates SET status = ? WHERE id = ?')->execute(['REVOKED', $id]);
                eca_audit('certificate.revoke', 'membership_certificates', (string) $id, [
                    'certificate_number' => $cert['certificate_number'] ?? '',
                ]);
                eca_notify([
                    'client_id' => (int) $cert['client_id'],
                    'membership_number' => (string) ($cert['membership_number'] ?? ''),
                    'title' => 'Certificate revoked',
                    'message' => 'Certificate ' . ($cert['certificate_number'] ?? '') . ' was revoked.',
                    'type' => 'CERTIFICATE',
                    'link' => '/client/certificate.php',
                ]);
                $notice = 'Certificate revoked.';
            }
        } elseif ($action === 'generate') {
            $client = null;
            if ($membership !== '') {
                $c = $conn->prepare('SELECT * FROM tbl_client WHERE MembershipNumber = ? LIMIT 1');
                $c->execute([$membership]);
                $client = $c->fetch(PDO::FETCH_ASSOC) ?: null;
            } elseif ($clientId > 0) {
                $c = $conn->prepare('SELECT * FROM tbl_client WHERE client_id = ? LIMIT 1');
                $c->execute([$clientId]);
                $client = $c->fetch(PDO::FETCH_ASSOC) ?: null;
            }
            if ($client) {
                eca_issue_certificate($conn, $client, eca_latest_membership_year($conn, $client));
                eca_audit('certificate.issued', 'tbl_client', (string) ($client['client_id'] ?? ''));
                eca_notify([
                    'client_id' => (int) ($client['client_id'] ?? 0),
                    'membership_number' => (string) ($client['MembershipNumber'] ?? ''),
                    'title' => 'Certificate issued',
                    'message' => 'A membership certificate is available to download.',
                    'type' => 'CERTIFICATE',
                    'link' => '/client/certificate.php',
                ]);
                $notice = 'Certificate generated.';
            } else {
                $notice = 'No member matched that membership number or client id.';
                $noticeError = true;
            }
        }
    }
}
$search = trim((string) ($_GET['search'] ?? ''));
$status = strtoupper(trim((string) ($_GET['status'] ?? '')));
if ($status !== '' && !in_array($status, ['ACTIVE', 'REVOKED'], true)) {
    $status = '';
}
$page = eca_pager_page();
$limit = eca_pager_limit();
$rows = [];
$total = 0;
$totalPages = 1;
if ($conn) {
    $where = ' WHERE 1=1';
    $params = [];
    if ($status !== '') {
        $where .= ' AND status = ?';
        $params[] = $status;
    }
    if ($search !== '') {
        $where .= ' AND (certificate_number LIKE ? OR membership_number LIKE ? OR company_name LIKE ? OR status LIKE ?)';
        $like = '%' . $search . '%';
        array_push($params, $like, $like, $like, $like);
    }
    $paged = eca_paged_query(
        $conn,
        'SELECT COUNT(*) FROM membership_certificates' . $where,
        'SELECT * FROM membership_certificates' . $where . ' ORDER BY id DESC',
        $params,
        $page,
        $limit
    );
    $rows = $paged['rows'];
    $total = $paged['total'];
    $page = $paged['page'];
    $totalPages = $paged['pages'];
    if (eca_admin_export_requested()) {
        eca_admin_require_csv_export();
        $exportRows = eca_admin_export_rows(
            $conn,
            'SELECT certificate_number, membership_number, company_name, issued_at, status
             FROM membership_certificates' . $where . ' ORDER BY id DESC',
            $params
        );
        $csv = [];
        foreach ($exportRows as $row) {
            $csv[] = [
                $row['certificate_number'] ?? '',
                $row['membership_number'] ?? '',
                $row['company_name'] ?? '',
                $row['issued_at'] ?? '',
                $row['status'] ?? '',
            ];
        }
        eca_admin_send_csv('eca-certificates', ['Number', 'Membership', 'Company', 'Issued', 'Status'], $csv, 'membership_certificates');
    }
}
$csrf = eca_admin_csrf();
eca_admin_hub_start('Certificates', 'certificates');
?>
<div class="hub-hello">
    <h1 class="hub-hello-title">Certificates (<?= (int) $total ?>)</h1>
    <p>Generate or revoke membership certificates. Revoked certificates do not verify as active. Members download their own copy from the member hub.</p>
</div>
<?php if ($notice): ?><p class="hub-card"<?= $noticeError ? ' style="border-color:#b42318;"' : '' ?>><?= eca_admin_h($notice) ?></p><?php endif; ?>
<form class="hub-card" method="post" style="display:flex;gap:10px;flex-wrap:wrap;align-items:center;">
    <input type="hidden" name="csrf_token" value="<?= eca_admin_h($csrf) ?>">
    <input type="hidden" name="action" value="generate">
    <input name="membership_number" placeholder="Membership number" style="padding:10px 12px;border-radius:10px;border:1px solid #d0d5dd;">
    <input name="client_id" type="number" min="1" placeholder="or client_id" style="padding:10px 12px;border-radius:10px;border:1px solid #d0d5dd;">
    <button class="hub-btn" type="submit">Generate / regenerate</button>
</form>
<form class="hub-card" method="get" style="display:flex;gap:10px;flex-wrap:wrap;align-items:center;margin-bottom:12px;">
    <input type="search" name="search" data-hub-search value="<?= eca_admin_h($search) ?>" placeholder="Certificate, member or company" autocomplete="off" style="padding:10px 12px;border-radius:10px;border:1px solid #d0d5dd;">
    <select name="status" onchange="this.form.submit()" style="padding:10px 12px;border-radius:10px;border:1px solid #d0d5dd;">
        <option value="">All statuses</option>
        <option value="ACTIVE"<?= $status === 'ACTIVE' ? ' selected' : '' ?>>ACTIVE</option>
        <option value="REVOKED"<?= $status === 'REVOKED' ? ' selected' : '' ?>>REVOKED</option>
    </select>
    <button class="hub-btn" type="submit">Search</button>
    <?= eca_admin_csv_button() ?>
</form>
<div class="hub-card eca-table-panel">
    <h5>Certificates</h5>
    <table class="hub-table" data-dash-server-page="1">
        <thead><tr><th>Number</th><th>Member</th><th>Company</th><th>Issued</th><th>Status</th><th></th></tr></thead>
        <tbody>
        <?php foreach ($rows as $row): ?>
            <tr>
                <td><?= eca_admin_h($row['certificate_number'] ?? '') ?></td>
                <td><?php if (!empty($row['client_id'])): ?><a href="/admin/member-detail.php?id=<?= (int) $row['client_id'] ?>"><?= eca_admin_h($row['membership_number'] ?? '') ?></a><?php else: ?><?= eca_admin_h($row['membership_number'] ?? '') ?><?php endif; ?></td>
                <td><?= eca_admin_h($row['company_name'] ?? '') ?></td>
                <td><?= eca_admin_h($row['issued_at'] ?? '') ?></td>
                <td><?= eca_admin_h($row['status'] ?? '') ?></td>
                <td>
                    <a href="/certificate-download.php?id=<?= (int) $row['id'] ?>">Download</a>
                    · <a href="/verify.php?cert=<?= urlencode((string) ($row['certificate_number'] ?? '')) ?>">Verify</a>
                    <?php if (strtoupper((string) ($row['status'] ?? '')) === 'ACTIVE'): ?>
                    <form method="post" style="display:inline;">
                        <input type="hidden" name="csrf_token" value="<?= eca_admin_h($csrf) ?>">
                        <input type="hidden" name="action" value="revoke">
                        <input type="hidden" name="id" value="<?= (int) $row['id'] ?>">
                        <button class="hub-btn" type="submit" onclick="return confirm('Revoke this certificate?');">Revoke</button>
                    </form>
                    <?php endif; ?>
                </td>
            </tr>
        <?php endforeach; ?>
        <?php if (!$rows): ?><tr data-hub-empty-row><td colspan="6">No certificates issued yet.</td></tr><?php endif; ?>
        </tbody>
    </table>
</div>
<?php
eca_render_request_pager($page, $totalPages, $total, $limit);
eca_admin_hub_end();
?>
