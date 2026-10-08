<?php
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/_hub.php';
require_once __DIR__ . '/../includes/portal-db.php';
require_once __DIR__ . '/../includes/admin-stats.php';
require_once __DIR__ . '/../includes/pagination.php';
eca_admin_require('payments.manage');
header('Cache-Control: no-store');

$filter = strtolower(trim((string) ($_GET['status'] ?? '')));
$search = trim((string) ($_GET['search'] ?? ''));
$page = eca_pager_page();
$limit = eca_pager_limit();
$rows = [];
$total = 0;
$totalPages = 1;
$conn = eca_portal_pdo(false);
if ($conn) {
    $where = ' WHERE 1=1';
    $params = [];
    if (in_array($filter, ['pending', 'approved', 'rejected', 'verified'], true)) {
        $dbStatus = $filter === 'verified' ? 'approved' : $filter;
        $where .= ' AND p.status = ?';
        $params[] = $dbStatus;
    }
    if ($search !== '') {
        $where .= ' AND (u.membership_number LIKE ? OR u.full_name LIKE ? OR u.email LIKE ? OR p.status LIKE ? OR p.payment_year LIKE ?)';
        $like = '%' . $search . '%';
        array_push($params, $like, $like, $like, $like, $like);
    }
    $from = 'payments p LEFT JOIN userss u ON u.id = p.user_id';
    $paged = eca_paged_query(
        $conn,
        'SELECT COUNT(*) FROM ' . $from . $where,
        'SELECT p.*, u.membership_number, u.full_name, u.email FROM ' . $from . $where . ' ORDER BY p.id DESC',
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
            'SELECT p.id, p.status, p.payment_year, p.created_at, u.membership_number, u.full_name, u.email
             FROM ' . $from . $where . ' ORDER BY p.id DESC',
            $params
        );
        $csv = [];
        foreach ($exportRows as $row) {
            $csv[] = [
                $row['id'] ?? '',
                $row['membership_number'] ?? '',
                $row['full_name'] ?? '',
                $row['email'] ?? '',
                $row['payment_year'] ?? '',
                eca_payment_label((string) ($row['status'] ?? '')),
                $row['created_at'] ?? '',
            ];
        }
        eca_admin_send_csv('eca-payments', ['ID', 'Membership', 'Name', 'Email', 'Year', 'Status', 'Created'], $csv, 'payments');
    }
}
eca_admin_hub_start('Payments', 'payments');
?>
<div class="hub-hello">
    <h1 class="hub-hello-title">Payment proofs (<?= (int) $total ?>)</h1>
    <p>Manual proof of payment only. These figures are proof counts by status, not SZL totals. No payment provider is connected.</p>
</div>
<form class="hub-card" method="get" style="display:flex;gap:10px;flex-wrap:wrap;align-items:center;margin-bottom:12px;">
    <input type="search" name="search" data-hub-search value="<?= eca_admin_h($search) ?>" placeholder="Type a letter to filter member, year or status" autocomplete="off" style="padding:10px 12px;border-radius:10px;border:1px solid #d0d5dd;">
    <span data-hub-search-count class="hub-live-search-count"></span>
    <select name="status" onchange="this.form.submit()" style="padding:10px 12px;border-radius:10px;border:1px solid #d0d5dd;">
        <option value="">All</option>
        <?php foreach (['pending' => 'PENDING', 'verified' => 'VERIFIED', 'rejected' => 'REJECTED'] as $val => $label): ?>
            <option value="<?= $val ?>"<?= $filter === $val || ($filter === 'approved' && $val === 'verified') ? ' selected' : '' ?>><?= $label ?></option>
        <?php endforeach; ?>
    </select>
    <?= eca_admin_csv_button() ?>
</form>
<div class="hub-card eca-table-panel">
    <h5>Payment proofs</h5>
    <table class="hub-table" data-dash-server-page="1">
        <thead><tr><th>ID</th><th>Member</th><th>Year</th><th>Status</th><th></th></tr></thead>
        <tbody>
        <?php foreach ($rows as $row): ?>
            <tr>
                <td><?= (int) $row['id'] ?></td>
                <td><?= eca_admin_h($row['membership_number'] ?? $row['full_name'] ?? $row['user_id']) ?></td>
                <td><?= eca_admin_h($row['payment_year'] ?? '') ?></td>
                <td><?= eca_admin_h(eca_payment_label((string) ($row['status'] ?? ''))) ?></td>
                <td><a href="/admin/payment-detail.php?id=<?= (int) $row['id'] ?>">Open</a></td>
            </tr>
        <?php endforeach; ?>
        <?php if (!$rows): ?><tr data-hub-empty-row><td colspan="5"><?= $conn ? 'No payment proofs in the local portal database (count is genuinely 0).' : 'DATA NOT AVAILABLE LOCALLY — portal database unavailable.' ?></td></tr><?php endif; ?>
        </tbody>
    </table>
</div>
<?php
eca_render_request_pager($page, $totalPages, $total, $limit);
eca_admin_hub_end();
?>
