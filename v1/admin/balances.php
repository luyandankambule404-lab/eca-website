<?php
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/_hub.php';
require_once __DIR__ . '/../includes/pagination.php';
require_once __DIR__ . '/../includes/admin-stats.php';
eca_admin_require('payments.manage');
header('Cache-Control: no-store');

$conn = eca_admin_db();
$search = trim((string) ($_GET['search'] ?? ''));
$status = strtolower(trim((string) ($_GET['status'] ?? '')));
$page = eca_pager_page();
$limit = eca_pager_limit();
$rows = [];
$total = 0;
$totalPages = 1;
$hasBalancesTable = $conn && eca_has_table($conn, 'balances');
if ($hasBalancesTable) {
    $where = ' WHERE 1=1';
    $params = [];
    if (in_array($status, ['due', 'outstanding', 'unpaid', 'pending', 'paid'], true)) {
        $where .= ' AND LOWER(status) = ?';
        $params[] = $status;
    }
    if ($search !== '') {
        $where .= ' AND (status LIKE ? OR company_id LIKE ?)';
        $like = '%' . $search . '%';
        array_push($params, $like, $like);
    }
    $paged = eca_paged_query(
        $conn,
        'SELECT COUNT(*) FROM balances' . $where,
        'SELECT id, company_id, amount, status, created_at FROM balances' . $where . ' ORDER BY id DESC',
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
            'SELECT id, company_id, amount, status, created_at FROM balances' . $where . ' ORDER BY id DESC',
            $params
        );
        $csv = [];
        foreach ($exportRows as $row) {
            $csv[] = [
                $row['id'] ?? '',
                $row['company_id'] ?? '',
                $row['amount'] ?? '',
                $row['status'] ?? '',
                $row['created_at'] ?? '',
            ];
        }
        eca_admin_send_csv('eca-balances', ['ID', 'Company', 'Stored amount', 'Status', 'When'], $csv, 'balances');
    }
}
eca_admin_hub_start('Outstanding balances', 'balances');
?>
<div class="hub-hello">
    <h1 class="hub-hello-title">Outstanding balances<?= $hasBalancesTable ? ' (' . (int) $total . ')' : '' ?></h1>
    <?php if (!$hasBalancesTable): ?>
        <p><strong>DATA NOT AVAILABLE LOCALLY.</strong> The <code>balances</code> table is not present in this local Hub database. This is not a money total of zero.</p>
    <?php else: ?>
        <p>Rows from the local <code>balances</code> table. Empty means no outstanding fee records are stored yet — this is not an invented SZL total.</p>
    <?php endif; ?>
</div>
<form class="hub-card" method="get" style="display:flex;gap:10px;flex-wrap:wrap;align-items:center;margin-bottom:12px;">
    <input type="search" name="search" value="<?= eca_admin_h($search) ?>" placeholder="Company id or status" style="padding:10px 12px;border-radius:10px;border:1px solid #d0d5dd;">
    <select name="status" onchange="this.form.submit()" style="padding:10px 12px;border-radius:10px;border:1px solid #d0d5dd;">
        <option value="">All</option>
        <?php foreach (['due', 'outstanding', 'unpaid', 'pending', 'paid'] as $opt): ?>
            <option value="<?= $opt ?>"<?= $status === $opt ? ' selected' : '' ?>><?= eca_admin_h(strtoupper($opt)) ?></option>
        <?php endforeach; ?>
    </select>
    <button class="hub-btn" type="submit">Filter</button>
    <?= eca_admin_csv_button() ?>
</form>
<div class="hub-card eca-table-panel">
    <table class="hub-table" data-dash-server-page="1">
        <thead><tr><th>ID</th><th>Company</th><th>Stored amount</th><th>Status</th><th>When</th></tr></thead>
        <tbody>
        <?php foreach ($rows as $row): ?>
            <tr>
                <td><?= (int) $row['id'] ?></td>
                <td><?= (int) $row['company_id'] ?></td>
                <td><?= eca_admin_h($row['amount'] ?? '') ?></td>
                <td><?= eca_admin_h($row['status'] ?? '') ?></td>
                <td><?= eca_admin_h($row['created_at'] ?? '') ?></td>
            </tr>
        <?php endforeach; ?>
        <?php if (!$hasBalancesTable): ?>
            <tr data-hub-empty-row><td colspan="5">DATA NOT AVAILABLE LOCALLY — balances table missing.</td></tr>
        <?php elseif (!$rows): ?>
            <tr data-hub-empty-row><td colspan="5">No outstanding balance rows in the local database.</td></tr>
        <?php endif; ?>
        </tbody>
    </table>
</div>
<p class="hub-note"><a href="/admin/payments.php">Payment proofs</a> remain count/status records and are not converted into money here.</p>
<?php
eca_render_request_pager($page, $totalPages, $total, $limit);
eca_admin_hub_end();
?>
