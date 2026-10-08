<?php
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/_hub.php';
require_once __DIR__ . '/../includes/portal-db.php';
require_once __DIR__ . '/../includes/membership.php';
require_once __DIR__ . '/../includes/pagination.php';
eca_admin_require('members.manage');
header('Cache-Control: no-store');

$search = trim((string) ($_GET['search'] ?? ''));
$standing = trim((string) ($_GET['standing'] ?? ''));
$type = trim((string) ($_GET['type'] ?? ''));
$appStatus = strtoupper(trim((string) ($_GET['application_status'] ?? '')));
$year = trim((string) ($_GET['year'] ?? ''));
$yearState = trim((string) ($_GET['year_state'] ?? ''));
$legacyStatus = trim((string) ($_GET['status'] ?? ''));
if ($standing === '' && in_array($legacyStatus, eca_member_standing_values(), true)) {
    $standing = $legacyStatus;
}
if ($yearState === '' && strcasecmp($legacyStatus, 'Expired') === 0) {
    $yearState = 'expired';
}
if ($standing !== '' && !in_array($standing, eca_member_standing_values(), true)) {
    $standing = '';
}
if ($yearState !== '' && !isset(eca_membership_year_state_values()[$yearState])) {
    $yearState = '';
}
if ($type !== '' && !in_array($type, eca_member_type_values(), true)) {
    $type = '';
}
if ($appStatus !== '' && !in_array($appStatus, eca_application_statuses(), true)) {
    $appStatus = '';
}
if ($year !== '' && !preg_match('/^\d{4}$/', $year)) {
    $year = '';
}

$page = eca_pager_page();
$limit = eca_pager_limit();
$rows = [];
$total = 0;
$totalPages = 1;
$years = [];
$conn = eca_portal_pdo(false);
if ($conn) {
    try {
        $years = $conn->query('SELECT DISTINCT year FROM membership_years ORDER BY year DESC')->fetchAll(PDO::FETCH_COLUMN);
    } catch (Throwable $e) {
        $years = [];
    }
    $where = ' WHERE 1=1';
    $params = [];
    if ($search !== '') {
        $where .= ' AND (MembershipNumber LIKE ? OR TradingName LIKE ? OR CompanyRegistrationName LIKE ? OR EmailAddress LIKE ?)';
        $like = '%' . $search . '%';
        array_push($params, $like, $like, $like, $like);
    }
    if ($standing !== '') {
        $where .= ' AND active = ?';
        $params[] = $standing;
    }
    if ($type !== '') {
        $where .= ' AND Status = ?';
        $params[] = $type;
    }
    if ($appStatus !== '') {
        $where .= ' AND application_status = ?';
        $params[] = $appStatus;
    }
    if ($year !== '') {
        $where .= ' AND client_id IN (SELECT client_id FROM membership_years WHERE year = ?)';
        $params[] = $year;
    }
    $yearStateSql = $yearState !== '' ? eca_membership_year_state_sql($yearState) : null;
    if ($yearStateSql) {
        $where .= ' AND ' . $yearStateSql;
    }
    $paged = eca_paged_query(
        $conn,
        'SELECT COUNT(*) FROM tbl_client' . $where,
        'SELECT client_id, MembershipNumber, TradingName, CompanyRegistrationName, EmailAddress, Clasification, Status, active, application_status, application_reference
            FROM tbl_client' . $where . ' ORDER BY TradingName ASC',
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
            'SELECT MembershipNumber, TradingName, CompanyRegistrationName, EmailAddress, Clasification, Status, active, application_status, application_reference
             FROM tbl_client' . $where . ' ORDER BY TradingName ASC',
            $params
        );
        $csv = [];
        foreach ($exportRows as $row) {
            $csv[] = [
                $row['MembershipNumber'] ?? '',
                $row['TradingName'] ?? '',
                $row['CompanyRegistrationName'] ?? '',
                $row['EmailAddress'] ?? '',
                $row['active'] ?? '',
                $row['Status'] ?? '',
                $row['application_status'] ?? '',
                $row['application_reference'] ?? '',
                $row['Clasification'] ?? '',
            ];
        }
        eca_admin_send_csv('eca-members', ['Membership', 'Trading name', 'Registered name', 'Email', 'Standing', 'Type', 'Application status', 'Application reference', 'Classification'], $csv, 'tbl_client');
    }
}

eca_admin_hub_start('Members', 'members');
?>
<div class="hub-hello">
    <h1 class="hub-hello-title">Portal members (<?= (int) $total ?>)</h1>
    <p>Standing uses <code>active</code>. Membership type uses <code>Status</code>. Application status is separate. Year filters use <code>membership_years</code> rows only — empty year data stays empty. This list is every matching <code>tbl_client</code> row, not the numbered-member dashboard total.</p>
</div>
<form class="hub-card" method="get" style="display:flex;gap:10px;flex-wrap:wrap;align-items:center;margin-bottom:12px;">
    <input type="search" name="search" data-hub-search value="<?= eca_admin_h($search) ?>" placeholder="Membership, company or email" autocomplete="off" style="padding:10px 12px;border-radius:10px;border:1px solid #d0d5dd;">
    <select name="standing" onchange="this.form.submit()" style="padding:10px 12px;border-radius:10px;border:1px solid #d0d5dd;">
        <option value="">All standing</option>
        <?php foreach (eca_member_standing_values() as $opt): ?>
            <option value="<?= eca_admin_h($opt) ?>"<?= $standing === $opt ? ' selected' : '' ?>><?= eca_admin_h($opt) ?></option>
        <?php endforeach; ?>
    </select>
    <select name="type" onchange="this.form.submit()" style="padding:10px 12px;border-radius:10px;border:1px solid #d0d5dd;">
        <option value="">All membership types</option>
        <?php foreach (eca_member_type_values() as $opt): ?>
            <option value="<?= eca_admin_h($opt) ?>"<?= $type === $opt ? ' selected' : '' ?>><?= eca_admin_h($opt) ?></option>
        <?php endforeach; ?>
    </select>
    <select name="application_status" onchange="this.form.submit()" style="padding:10px 12px;border-radius:10px;border:1px solid #d0d5dd;">
        <option value="">All applications</option>
        <?php foreach (eca_application_statuses() as $opt): ?>
            <option value="<?= eca_admin_h($opt) ?>"<?= $appStatus === $opt ? ' selected' : '' ?>><?= eca_admin_h($opt) ?></option>
        <?php endforeach; ?>
    </select>
    <select name="year" onchange="this.form.submit()" style="padding:10px 12px;border-radius:10px;border:1px solid #d0d5dd;">
        <option value="">All years</option>
        <?php foreach ($years as $opt): ?>
            <option value="<?= eca_admin_h((string) $opt) ?>"<?= $year === (string) $opt ? ' selected' : '' ?>><?= eca_admin_h((string) $opt) ?></option>
        <?php endforeach; ?>
    </select>
    <select name="year_state" onchange="this.form.submit()" style="padding:10px 12px;border-radius:10px;border:1px solid #d0d5dd;">
        <option value="">All year states</option>
        <?php foreach (eca_membership_year_state_values() as $key => $label): ?>
            <option value="<?= eca_admin_h($key) ?>"<?= $yearState === $key ? ' selected' : '' ?>><?= eca_admin_h($label) ?></option>
        <?php endforeach; ?>
    </select>
    <button class="hub-btn" type="submit">Search</button>
    <?= eca_admin_csv_button() ?>
</form>
<div class="hub-card eca-table-panel">
    <h5>Members</h5>
    <table class="hub-table" data-dash-server-page="1">
        <thead><tr><th>Membership</th><th>Company</th><th>Standing</th><th>Type</th><th>Application</th><th></th></tr></thead>
        <tbody>
        <?php foreach ($rows as $row): ?>
            <tr>
                <td><?= eca_admin_h($row['MembershipNumber'] ?? '') ?></td>
                <td><?= eca_admin_h($row['TradingName'] ?? $row['CompanyRegistrationName'] ?? '') ?></td>
                <td><?= eca_admin_h($row['active'] ?? '') ?></td>
                <td><?= eca_admin_h($row['Status'] ?? '') ?></td>
                <td><?= eca_admin_h(($row['application_reference'] ?? '') !== '' ? (($row['application_status'] ?? '') . ' · ' . ($row['application_reference'] ?? '')) : '—') ?></td>
                <td><a href="/admin/member-detail.php?id=<?= (int) $row['client_id'] ?>">Open</a></td>
            </tr>
        <?php endforeach; ?>
        <?php if (!$rows): ?><tr data-hub-empty-row><td colspan="6">No members match.</td></tr><?php endif; ?>
        </tbody>
    </table>
</div>
<?php
eca_render_request_pager($page, $totalPages, $total, $limit);
eca_admin_hub_end();
