<?php
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/_hub.php';
require_once __DIR__ . '/../includes/portal-db.php';
require_once __DIR__ . '/../includes/membership.php';
require_once __DIR__ . '/../includes/pagination.php';
eca_admin_require('applications.manage');
header('Cache-Control: no-store, no-cache, must-revalidate');

$search = trim((string) ($_GET['search'] ?? $_GET['q'] ?? ''));
$status = strtoupper(trim((string) ($_GET['status'] ?? '')));
$statuses = eca_application_statuses();
if ($status !== '' && !in_array($status, $statuses, true)) {
    $status = '';
}

$page = eca_pager_page();
$limit = eca_pager_limit();
$rows = [];
$total = 0;
$totalPages = 1;
$conn = eca_portal_pdo(false);
if ($conn) {
    $where = ' WHERE application_reference IS NOT NULL AND application_reference <> ""';
    $params = [];
    if ($status !== '') {
        $where .= ' AND application_status = ?';
        $params[] = $status;
    }
    if ($search !== '') {
        $where .= ' AND (application_reference LIKE ? OR TradingName LIKE ? OR CompanyRegistrationName LIKE ? OR MembershipNumber LIKE ?)';
        $like = '%' . $search . '%';
        array_push($params, $like, $like, $like, $like);
    }
    $paged = eca_paged_query(
        $conn,
        'SELECT COUNT(*) FROM tbl_client' . $where,
        'SELECT client_id, application_reference, application_status, TradingName, CompanyRegistrationName,
                   EmailAddress, Clasification, Status, active, created_at
            FROM tbl_client' . $where . ' ORDER BY created_at DESC',
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
            'SELECT application_reference, application_status, TradingName, CompanyRegistrationName, MembershipNumber, Clasification, Status, active, created_at
             FROM tbl_client' . $where . ' ORDER BY created_at DESC',
            $params
        );
        $csv = [];
        foreach ($exportRows as $row) {
            $csv[] = [
                $row['application_reference'] ?? '',
                $row['application_status'] ?? '',
                $row['TradingName'] ?? '',
                $row['CompanyRegistrationName'] ?? '',
                $row['MembershipNumber'] ?? '',
                $row['Clasification'] ?? '',
                $row['Status'] ?? '',
                $row['active'] ?? '',
                $row['created_at'] ?? '',
            ];
        }
        eca_admin_send_csv('eca-applications', ['Reference', 'Status', 'Trading name', 'Registered name', 'Membership', 'Classification', 'Type', 'Standing', 'Submitted'], $csv, 'tbl_client');
    }
}

eca_admin_hub_start('Applications', 'applications');
?>
<div class="hub-hello">
    <h1 class="hub-hello-title">Membership applications (<?= (int) $total ?>)</h1>
    <p>Search and filter local applications using <code>ECA-APP-YYYY-NNNN</code>. Standing (<code>active</code>) is not the same as application status.</p>
</div>
<form class="hub-card" method="get" action="/admin/applications.php" style="display:flex;gap:10px;flex-wrap:wrap;align-items:center;margin-bottom:12px;">
    <input type="search" name="search" data-hub-search value="<?= eca_admin_h($search) ?>" placeholder="Type a letter to filter reference, company, membership" autocomplete="off" style="padding:10px 12px;border-radius:10px;border:1px solid #d0d5dd;">
    <span data-hub-search-count class="hub-live-search-count"></span>
    <select name="status" onchange="this.form.submit()" style="padding:10px 12px;border-radius:10px;border:1px solid #d0d5dd;">
        <option value="">All statuses</option>
        <?php foreach ($statuses as $opt): ?>
            <option value="<?= eca_admin_h($opt) ?>"<?= $status === $opt ? ' selected' : '' ?>><?= eca_admin_h($opt) ?></option>
        <?php endforeach; ?>
    </select>
    <button class="hub-btn" type="submit">Search</button>
    <?= eca_admin_csv_button() ?>
</form>
<?php if (!$rows): ?>
    <p class="hub-card"><?= $conn ? 'No applications match this search.' : 'The membership database is not available.' ?></p>
<?php else: ?>
<div class="hub-card eca-table-panel">
    <h5>Applications</h5>
    <table class="hub-table" data-dash-server-page="1">
        <thead>
            <tr>
                <th>Reference</th>
                <th>Company</th>
                <th>Status</th>
                <th>Classification</th>
                <th>Submitted</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($rows as $row): ?>
                <tr>
                    <td><a href="/admin/application-detail.php?id=<?= (int) $row['client_id'] ?>"><?= eca_admin_h($row['application_reference'] ?? '') ?></a></td>
                    <td><?= eca_admin_h($row['TradingName'] ?? $row['CompanyRegistrationName'] ?? '') ?></td>
                    <td><?= eca_admin_h($row['application_status'] ?? '') ?></td>
                    <td><?= eca_admin_h($row['Clasification'] ?? '') ?></td>
                    <td><?= eca_admin_h($row['created_at'] ?? '') ?></td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>
<?php
eca_render_request_pager($page, $totalPages, $total, $limit);
endif;
eca_admin_hub_end();
