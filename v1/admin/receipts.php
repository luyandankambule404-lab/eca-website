<?php
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/_hub.php';
require_once __DIR__ . '/../includes/portal-db.php';
require_once __DIR__ . '/../includes/pagination.php';
eca_admin_require('payments.manage');
header('Cache-Control: no-store');

$conn = eca_portal_pdo(false);
$search = trim((string) ($_GET['search'] ?? ''));
$page = eca_pager_page();
$limit = eca_pager_limit();
$rows = [];
$total = 0;
$totalPages = 1;
$hasReceiptLogs = $conn && function_exists('eca_has_table') && eca_has_table($conn, 'receipt_logs');
if ($hasReceiptLogs) {
    try {
    $where = ' WHERE 1=1';
    $params = [];
    if ($search !== '') {
        $where .= ' AND (r.receipt_number LIKE ? OR r.sent_to LIKE ? OR r.action LIKE ? OR r.status LIKE ? OR a.membership_number LIKE ? OR a.full_name LIKE ?)';
        $like = '%' . $search . '%';
        array_push($params, $like, $like, $like, $like, $like, $like);
    }
    $from = 'receipt_logs r LEFT JOIN cpd_applications a ON a.id = r.application_id';
    $paged = eca_paged_query(
        $conn,
        'SELECT COUNT(*) FROM ' . $from . $where,
        'SELECT r.id, r.application_id, r.receipt_number, r.sent_to, r.action, r.status, r.created_at,
                a.membership_number, a.full_name, a.company_name
         FROM ' . $from . $where . ' ORDER BY r.id DESC',
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
            'SELECT r.receipt_number, r.action, r.status, r.sent_to, r.created_at, a.membership_number, a.full_name, a.company_name
             FROM ' . $from . $where . ' ORDER BY r.id DESC',
            $params
        );
        $csv = [];
        foreach ($exportRows as $row) {
            $csv[] = [
                $row['receipt_number'] ?? '',
                $row['membership_number'] ?? '',
                $row['full_name'] ?? '',
                $row['company_name'] ?? '',
                $row['action'] ?? '',
                $row['status'] ?? '',
                $row['sent_to'] ?? '',
                $row['created_at'] ?? '',
            ];
        }
        eca_admin_send_csv('eca-receipts', ['Receipt', 'Membership', 'Name', 'Company', 'Action', 'Status', 'Sent to', 'When'], $csv, 'receipt_logs');
    }
    } catch (Throwable $e) {
        $rows = [];
    }
}
eca_admin_hub_start('Receipts', 'receipts');
?>
<div class="hub-hello">
    <h1 class="hub-hello-title">Receipt logs<?= $hasReceiptLogs ? ' (' . (int) $total . ')' : '' ?></h1>
    <?php if (!$hasReceiptLogs): ?>
        <p><strong>DATA NOT AVAILABLE LOCALLY.</strong> The portal <code>receipt_logs</code> table is not present. This page does not invent receipt activity.</p>
    <?php else: ?>
        <p>These local <code>receipt_logs</code> rows are CPD receipt send/download actions (linked to <code>cpd_applications.id</code>). They are not membership payment transactions and have no SZL amount.</p>
    <?php endif; ?>
</div>
<form class="hub-card" method="get" style="display:flex;gap:10px;flex-wrap:wrap;align-items:center;margin-bottom:12px;">
    <input type="search" name="search" data-hub-search value="<?= eca_admin_h($search) ?>" placeholder="Receipt number, membership or recipient" style="padding:10px 12px;border-radius:10px;border:1px solid #d0d5dd;">
    <button class="hub-btn" type="submit">Search</button>
    <?= eca_admin_csv_button() ?>
</form>
<div class="hub-card eca-table-panel">
    <table class="hub-table" data-dash-server-page="1">
        <thead><tr><th>Receipt</th><th>Member</th><th>Action</th><th>Status</th><th>Sent to</th><th>When</th></tr></thead>
        <tbody>
        <?php foreach ($rows as $row): ?>
            <tr>
                <td><?= eca_admin_h($row['receipt_number'] ?? '') ?></td>
                <td><?= eca_admin_h(trim((string) ($row['membership_number'] ?? '') . ' ' . (string) ($row['full_name'] ?? '')) ?: ('CPD application ' . (int) ($row['application_id'] ?? 0))) ?></td>
                <td><?= eca_admin_h($row['action'] ?? '') ?></td>
                <td><?= eca_admin_h($row['status'] ?? '') ?></td>
                <td><?= eca_admin_h($row['sent_to'] ?? '') ?></td>
                <td><?= eca_admin_h($row['created_at'] ?? '') ?></td>
            </tr>
        <?php endforeach; ?>
        <?php if (!$hasReceiptLogs): ?>
            <tr data-hub-empty-row><td colspan="6">DATA NOT AVAILABLE LOCALLY — receipt_logs table missing.</td></tr>
        <?php elseif (!$rows): ?>
            <tr data-hub-empty-row><td colspan="6">No receipt logs.</td></tr>
        <?php endif; ?>
        </tbody>
    </table>
</div>
<?php
eca_render_request_pager($page, $totalPages, $total, $limit);
eca_admin_hub_end();
?>
