<?php
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/_hub.php';
require_once __DIR__ . '/../includes/portal-db.php';
require_once __DIR__ . '/../includes/admin-stats.php';
require_once __DIR__ . '/../includes/membership-intelligence.php';
require_once __DIR__ . '/../includes/pagination.php';

eca_admin_require('reports.view');
header('Cache-Control: no-store');

$portal = eca_portal_pdo(false);
$printMode = isset($_GET['print']) && (string) $_GET['print'] === '1';

$filters = [
    'search' => trim((string) ($_GET['search'] ?? '')),
    'standing' => trim((string) ($_GET['standing'] ?? '')),
    'type' => trim((string) ($_GET['type'] ?? '')),
    'classification' => trim((string) ($_GET['classification'] ?? '')),
    'region' => trim((string) ($_GET['region'] ?? '')),
    'has_number' => trim((string) ($_GET['has_number'] ?? '')),
    'year' => trim((string) ($_GET['year'] ?? '')),
    'period' => trim((string) ($_GET['period'] ?? '')),
    'year_state' => trim((string) ($_GET['year_state'] ?? '')),
    'date_from' => trim((string) ($_GET['date_from'] ?? '')),
    'date_to' => trim((string) ($_GET['date_to'] ?? '')),
];
$sort = trim((string) ($_GET['sort'] ?? 'name'));

if ($filters['standing'] !== '' && !in_array($filters['standing'], eca_member_standing_values(), true)) {
    $filters['standing'] = '';
}
if ($filters['type'] !== '' && !in_array($filters['type'], eca_member_type_values(), true)) {
    $filters['type'] = '';
}
if ($filters['has_number'] !== '' && !in_array($filters['has_number'], ['yes', 'no'], true)) {
    $filters['has_number'] = '';
}
if ($filters['year'] !== '' && !preg_match('/^\d{4}$/', $filters['year'])) {
    $filters['year'] = '';
}
if ($filters['period'] !== '' && eca_mi_period_years($filters['period']) === []) {
    $filters['period'] = '';
}
if ($filters['year_state'] !== '' && !isset(eca_membership_year_state_values()[$filters['year_state']])) {
    $filters['year_state'] = '';
}
if ($filters['date_from'] !== '' && !preg_match('/^\d{4}-\d{2}-\d{2}$/', $filters['date_from'])) {
    $filters['date_from'] = '';
}
if ($filters['date_to'] !== '' && !preg_match('/^\d{4}-\d{2}-\d{2}$/', $filters['date_to'])) {
    $filters['date_to'] = '';
}

$built = eca_mi_build_filters($filters);
$order = eca_mi_sort_sql($sort);
$page = eca_pager_page();
$limit = eca_pager_limit(10);
$rows = [];
$total = 0;
$totalPages = 1;
$options = eca_mi_filter_options($portal);
$intel = eca_mi_intelligence($portal);
$quality = eca_mi_data_quality($portal);
$kpis = $intel['kpis'];

$isPeriodReport = $filters['period'] === '2025-2026' || $filters['period'] === '2025/2026';
$pageTitle = $isPeriodReport ? '2025 / 2026 Membership Report' : 'Membership Intelligence';
$navActive = $isPeriodReport ? 'membership_2026' : 'membership_report';

if ($portal) {
    $countSql = eca_mi_count_sql($built['joins'], $built['where']);
    $selectSql = eca_mi_list_select_sql($built['joins'], $built['where'], $order);
    $paged = eca_paged_query($portal, $countSql, $selectSql, $built['params'], $page, $limit);
    $rows = $paged['rows'];
    $total = $paged['total'];
    $page = $paged['page'];
    $totalPages = $paged['pages'];
    $limit = $paged['limit'];

    if (eca_admin_export_requested()) {
        eca_admin_require_csv_export();
        $exportRows = eca_admin_export_rows($portal, $selectSql, $built['params']);
        $csv = [];
        foreach ($exportRows as $row) {
            $csv[] = [
                $row['membership_year'] ?? '',
                $row['MembershipNumber'] ?? '',
                $row['TradingName'] ?? '',
                $row['CompanyRegistrationName'] ?? '',
                $row['EmailAddress'] ?? '',
                $row['Cellphone'] ?? '',
                $row['Region'] ?? '',
                $row['Clasification'] ?? '',
                $row['Enterprise'] ?? '',
                $row['active'] ?? '',
                $row['Status'] ?? '',
                $row['application_status'] ?? '',
                $row['application_reference'] ?? '',
                $row['DateOfRegistration'] ?? '',
                $row['membership_year_type'] ?? '',
                $row['membership_year_status'] ?? '',
                $row['expiry_date'] ?? '',
            ];
        }
        $basename = $isPeriodReport ? 'eca-membership-2025-2026' : 'eca-membership-intelligence';
        eca_admin_send_csv(
            $basename,
            [
                'Membership year', 'Membership number', 'Trading name', 'Registered name', 'Email', 'Cellphone',
                'Region', 'Classification', 'Enterprise', 'Standing (active)', 'Type (Status)',
                'Application status', 'Application reference', 'Date registered',
                'Year type', 'Year status', 'Expiry date',
            ],
            $csv,
            'membership_report'
        );
    }
}

$queryKeep = array_filter(
    array_merge($filters, ['sort' => $sort]),
    static fn ($v) => $v !== '' && $v !== null
);

if ($printMode) {
    eca_audit('report.view', 'membership_report', $isPeriodReport ? '2025-2026' : 'intelligence', [
        'print' => 1,
        'total' => $total,
    ]);
    ?><!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= eca_admin_h($pageTitle) ?> | ECA</title>
    <link rel="stylesheet" href="/css/dashboard-hub.css?v=20261006-mi">
    <style>
        body{font-family:"Plus Jakarta Sans",system-ui,sans-serif;margin:24px;color:#122;background:#fff}
        h1{font-size:1.5rem;margin:0 0 8px}
        .meta{color:#556;margin-bottom:18px}
        table{width:100%;border-collapse:collapse;font-size:12px}
        th,td{border:1px solid #d7dde8;padding:6px 8px;text-align:left}
        th{background:#eef2f8}
        .kpi{display:flex;flex-wrap:wrap;gap:10px;margin:16px 0}
        .kpi div{border:1px solid #d7dde8;padding:10px 12px;border-radius:8px;min-width:120px}
        .kpi strong{display:block;font-size:1.25rem}
        @media print{.no-print{display:none!important}}
    </style>
</head>
<body>
    <p class="no-print"><button type="button" onclick="window.print()">Print</button> · <a href="/admin/membership-report.php?<?= eca_admin_h(http_build_query($queryKeep)) ?>">Back to report</a></p>
    <h1><?= eca_admin_h($pageTitle) ?></h1>
    <p class="meta">Generated <?= eca_admin_h(date('Y-m-d H:i')) ?> from local database · <?= (int) $total ?> matching row(s)</p>
    <div class="kpi">
        <div><span>Total clients</span><strong><?= (int) $kpis['tbl_client_total'] ?></strong></div>
        <div><span>With membership #</span><strong><?= (int) $kpis['with_membership_number'] ?></strong></div>
        <div><span>Active standing</span><strong><?= (int) $kpis['standing_active'] ?></strong></div>
        <div><span>Joining</span><strong><?= (int) $kpis['type_joining'] ?></strong></div>
        <div><span>Renewal type</span><strong><?= (int) $kpis['type_renewal'] ?></strong></div>
        <div><span>2025/2026 year rows</span><strong><?= (int) $kpis['period_2025_2026_clients'] ?></strong></div>
    </div>
    <table>
        <thead>
            <tr>
                <th>Year</th><th>Membership</th><th>Trading name</th><th>Region</th><th>Classification</th>
                <th>Standing</th><th>Type</th><th>Registered</th><th>Expiry</th>
            </tr>
        </thead>
        <tbody>
        <?php if (!$rows): ?>
            <tr><td colspan="9">No rows match the current filters.</td></tr>
        <?php else: ?>
            <?php foreach ($rows as $row): ?>
                <tr>
                    <td><?= eca_admin_h((string) ($row['membership_year'] ?? '—')) ?></td>
                    <td><?= eca_admin_h((string) ($row['MembershipNumber'] ?? '—')) ?></td>
                    <td><?= eca_admin_h((string) ($row['TradingName'] ?? $row['CompanyRegistrationName'] ?? '')) ?></td>
                    <td><?= eca_admin_h((string) ($row['Region'] ?? '')) ?></td>
                    <td><?= eca_admin_h((string) ($row['Clasification'] ?? '')) ?></td>
                    <td><?= eca_admin_h((string) ($row['active'] ?? '')) ?></td>
                    <td><?= eca_admin_h((string) ($row['Status'] ?? '')) ?></td>
                    <td><?= eca_admin_h(eca_display_date((string) ($row['DateOfRegistration'] ?? ''))) ?></td>
                    <td><?= eca_admin_h(eca_display_date((string) ($row['expiry_date'] ?? ''))) ?></td>
                </tr>
            <?php endforeach; ?>
        <?php endif; ?>
        </tbody>
    </table>
    <p class="meta">Print view shows the current page of results (<?= (int) count($rows) ?> of <?= (int) $total ?>). Use CSV export for the full filtered set (up to export limit).</p>
    <script>window.addEventListener('load', function () { /* ready for manual print */ });</script>
</body>
</html>
    <?php
    exit;
}

eca_audit('report.view', 'membership_report', $isPeriodReport ? '2025-2026' : 'intelligence', [
    'total' => $total,
    'filters' => array_keys(array_filter($filters)),
]);

eca_admin_hub_start($pageTitle, $navActive);
?>
<style>
.mi-grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(150px,1fr));gap:12px;margin:12px 0 18px}
.mi-card{background:#fff;border:1px solid #d7dde8;border-radius:14px;padding:14px 16px}
.mi-card span{display:block;font-size:.78rem;color:#5b6475;font-weight:600}
.mi-card strong{display:block;font-size:1.45rem;margin-top:4px;color:#122}
.mi-card small{display:block;margin-top:4px;color:#6b7280;font-size:.72rem;line-height:1.35}
.mi-chip{display:inline-block;padding:3px 8px;border-radius:999px;font-size:.72rem;font-weight:700;background:#eef2f8;color:#243}
.mi-chip.is-ok{background:#e8f7ee;color:#0f6b35}
.mi-chip.is-warn{background:#fff5df;color:#8a5a00}
.mi-chip.is-bad{background:#fde8e8;color:#9b1c1c}
.mi-note{background:#f7f9fc;border-left:4px solid #1f4b7a;padding:12px 14px;border-radius:8px;margin:12px 0;font-size:.9rem}
.mi-filters{display:grid;grid-template-columns:repeat(auto-fit,minmax(160px,1fr));gap:10px;align-items:end}
.mi-filters label{display:block;font-size:.75rem;font-weight:700;color:#445;margin-bottom:4px}
.mi-filters input,.mi-filters select{width:100%;padding:10px 12px;border-radius:10px;border:1px solid #d0d5dd;background:#fff}
.mi-actions{display:flex;flex-wrap:wrap;gap:8px;margin-top:10px}
.mi-split{display:grid;grid-template-columns:repeat(auto-fit,minmax(260px,1fr));gap:12px;margin:14px 0}
.mi-list{margin:0;padding-left:18px}
.mi-list li{margin:4px 0}
@media print{
  .hub-sidebar,.hub-topbar,.hub-overlay,.mobile-toggle,.mi-filters,.mi-actions,.dash-pager,.hub-nav{display:none!important}
  .hub-main{padding:0!important}
}
</style>

<div class="hub-hello">
    <h1 class="hub-hello-title"><?= eca_admin_h($pageTitle) ?></h1>
    <p>Local database only (<code>eca_portal_local</code>). Standing uses <code>tbl_client.active</code>. Membership type uses <code>tbl_client.Status</code>. Year intelligence uses <code>membership_years</code>. Numbers are never hard-coded.</p>
</div>

<div class="mi-note">
    <strong>Why totals differ:</strong>
    <em>Total clients</em> = every <code>tbl_client</code> row (<?= (int) $kpis['tbl_client_total'] ?>).
    <em>With membership number</em> = numbered members only (<?= (int) $kpis['with_membership_number'] ?>).
    Applicants without a number still appear in Total clients / Joining / Pending.
    Dashboard “Total members” historically means numbered members — that is intentional, not a bug.
</div>

<div class="hub-card-head"><h2>Membership overview</h2></div>
<div class="mi-grid">
    <article class="mi-card"><span>Total clients</span><strong><?= (int) $kpis['tbl_client_total'] ?></strong><small>COUNT(*) FROM tbl_client</small></article>
    <article class="mi-card"><span>With membership #</span><strong><?= (int) $kpis['with_membership_number'] ?></strong><small>Non-empty MembershipNumber</small></article>
    <article class="mi-card"><span>Without membership #</span><strong><?= (int) $kpis['without_membership_number'] ?></strong><small>Applicants / incomplete</small></article>
    <article class="mi-card"><span>Active standing</span><strong><?= (int) $kpis['standing_active'] ?></strong><small>active = Active</small></article>
    <article class="mi-card"><span>Pending standing</span><strong><?= (int) $kpis['standing_pending'] ?></strong><small>active = Pending</small></article>
    <article class="mi-card"><span>Suspended</span><strong><?= (int) $kpis['standing_suspended'] ?></strong><small>active LIKE %suspend%</small></article>
    <article class="mi-card"><span>Declined</span><strong><?= (int) $kpis['standing_declined'] ?></strong><small>active/Status declined</small></article>
    <article class="mi-card"><span>In progress</span><strong><?= (int) $kpis['standing_inprogress'] ?></strong><small>Inprogress variants</small></article>
    <article class="mi-card"><span>Joining (type)</span><strong><?= (int) $kpis['type_joining'] ?></strong><small>Status = Joining</small></article>
    <article class="mi-card"><span>Renewal (type)</span><strong><?= (int) $kpis['type_renewal'] ?></strong><small>Status = Renewal</small></article>
    <article class="mi-card"><span>Type Active</span><strong><?= (int) $kpis['type_active'] ?></strong><small>Status = Active</small></article>
    <article class="mi-card"><span>Regions</span><strong><?= (int) $kpis['regions_represented'] ?></strong><small>DISTINCT Region</small></article>
</div>

<div class="hub-card-head"><h2>Membership year intelligence</h2></div>
<div class="mi-grid">
    <article class="mi-card"><span>Year rows</span><strong><?= (int) $kpis['years_total_rows'] ?></strong><small>membership_years COUNT(*)</small></article>
    <article class="mi-card"><span>Current active years</span><strong><?= (int) $kpis['years_current_active'] ?></strong><small>status Active, not past expiry</small></article>
    <article class="mi-card"><span>Expiring (90 days)</span><strong><?= (int) $kpis['years_expiring_90d'] ?></strong><small>Active years near expiry</small></article>
    <article class="mi-card"><span>Expired years</span><strong><?= (int) $kpis['years_expired'] ?></strong><small>Expired status or past date</small></article>
    <article class="mi-card"><span>Pending renewals</span><strong><?= (int) $kpis['years_renewal_pending'] ?></strong><small>type Renewal + Pending</small></article>
    <article class="mi-card"><span>2025/2026 clients</span><strong><?= (int) $kpis['period_2025_2026_clients'] ?></strong><small>Distinct clients with year 2025 or 2026</small></article>
    <article class="mi-card"><span>New (30 days)</span><strong><?= (int) $kpis['new_members_30d'] ?></strong><small>tbl_client.created_at</small></article>
</div>

<div class="mi-actions">
    <a class="hub-btn" href="/admin/membership-report.php?period=2025-2026">Open 2025 / 2026 report</a>
    <a class="hub-btn" href="/admin/members.php">Member list</a>
    <a class="hub-btn" href="/admin/applications.php">Pending applications</a>
    <a class="hub-btn" href="/admin/members.php?type=Renewal">Renewal type members</a>
</div>

<div class="hub-card" style="margin-top:16px;">
    <h2 style="margin-top:0;">Filters</h2>
    <form method="get" class="mi-filters">
        <?php if ($filters['period'] !== ''): ?>
            <input type="hidden" name="period" value="<?= eca_admin_h($filters['period']) ?>">
        <?php endif; ?>
        <div>
            <label for="mi-search">Search</label>
            <input id="mi-search" type="search" name="search" value="<?= eca_admin_h($filters['search']) ?>" placeholder="Name, membership, email, region">
        </div>
        <div>
            <label for="mi-standing">Standing (active)</label>
            <select id="mi-standing" name="standing">
                <option value="">All</option>
                <?php foreach (eca_member_standing_values() as $opt): ?>
                    <option value="<?= eca_admin_h($opt) ?>"<?= $filters['standing'] === $opt ? ' selected' : '' ?>><?= eca_admin_h($opt) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div>
            <label for="mi-type">Joining / Renewal (Status)</label>
            <select id="mi-type" name="type">
                <option value="">All</option>
                <?php foreach (eca_member_type_values() as $opt): ?>
                    <option value="<?= eca_admin_h($opt) ?>"<?= $filters['type'] === $opt ? ' selected' : '' ?>><?= eca_admin_h($opt) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div>
            <label for="mi-class">Classification</label>
            <select id="mi-class" name="classification">
                <option value="">All</option>
                <?php foreach ($options['classifications'] as $opt): ?>
                    <option value="<?= eca_admin_h((string) $opt) ?>"<?= $filters['classification'] === (string) $opt ? ' selected' : '' ?>><?= eca_admin_h((string) $opt) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div>
            <label for="mi-region">Region</label>
            <select id="mi-region" name="region">
                <option value="">All</option>
                <?php foreach ($options['regions'] as $opt): ?>
                    <option value="<?= eca_admin_h((string) $opt) ?>"<?= $filters['region'] === (string) $opt ? ' selected' : '' ?>><?= eca_admin_h((string) $opt) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div>
            <label for="mi-year">Membership year</label>
            <select id="mi-year" name="year">
                <option value="">All years</option>
                <?php foreach ($options['years'] as $opt): ?>
                    <option value="<?= eca_admin_h((string) $opt) ?>"<?= $filters['year'] === (string) $opt ? ' selected' : '' ?>><?= eca_admin_h((string) $opt) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div>
            <label for="mi-year-state">Year state</label>
            <select id="mi-year-state" name="year_state">
                <option value="">All</option>
                <?php foreach (eca_membership_year_state_values() as $key => $label): ?>
                    <option value="<?= eca_admin_h($key) ?>"<?= $filters['year_state'] === $key ? ' selected' : '' ?>><?= eca_admin_h($label) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div>
            <label for="mi-number">Membership number</label>
            <select id="mi-number" name="has_number">
                <option value="">All</option>
                <option value="yes"<?= $filters['has_number'] === 'yes' ? ' selected' : '' ?>>Has number</option>
                <option value="no"<?= $filters['has_number'] === 'no' ? ' selected' : '' ?>>Missing number</option>
            </select>
        </div>
        <div>
            <label for="mi-from">Registered from</label>
            <input id="mi-from" type="date" name="date_from" value="<?= eca_admin_h($filters['date_from']) ?>">
        </div>
        <div>
            <label for="mi-to">Registered to</label>
            <input id="mi-to" type="date" name="date_to" value="<?= eca_admin_h($filters['date_to']) ?>">
        </div>
        <div>
            <label for="mi-sort">Sort</label>
            <select id="mi-sort" name="sort">
                <?php
                $sorts = [
                    'name' => 'Name A–Z',
                    'name_desc' => 'Name Z–A',
                    'membership' => 'Membership #',
                    'region' => 'Region',
                    'classification' => 'Classification',
                    'standing' => 'Standing',
                    'type' => 'Type',
                    'registered' => 'Registered (newest)',
                    'registered_asc' => 'Registered (oldest)',
                    'year' => 'Membership year',
                    'expiry' => 'Expiry date',
                ];
                foreach ($sorts as $key => $label):
                ?>
                    <option value="<?= eca_admin_h($key) ?>"<?= $sort === $key ? ' selected' : '' ?>><?= eca_admin_h($label) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div>
            <label for="mi-per">Rows per page</label>
            <select id="mi-per" name="per_page">
                <?php foreach (eca_pager_allowed_limits() as $opt): ?>
                    <option value="<?= (int) $opt ?>"<?= $limit === (int) $opt ? ' selected' : '' ?>><?= (int) $opt ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="mi-actions" style="grid-column:1/-1;margin:0">
            <button class="hub-btn" type="submit">Apply filters</button>
            <a class="hub-btn" href="/admin/membership-report.php<?= $isPeriodReport ? '?period=2025-2026' : '' ?>">Reset</a>
            <?= eca_admin_csv_button('Export CSV (filtered)') ?>
            <a class="hub-btn" href="?<?= eca_admin_h(http_build_query(array_merge($queryKeep, ['print' => '1', 'per_page' => (string) $limit, 'page' => (string) $page]))) ?>">Print-friendly view</a>
        </div>
    </form>
</div>

<div class="hub-card eca-table-panel" style="margin-top:14px;">
    <div class="hub-card-head">
        <h2 style="margin:0;"><?= $isPeriodReport ? '2025 / 2026 members' : 'Filtered members' ?> (<?= (int) $total ?>)</h2>
    </div>
    <?php if (!$portal): ?>
        <p class="hub-empty">Membership database unavailable.</p>
    <?php else: ?>
    <table class="hub-table" data-dash-server-page="1">
        <thead>
            <tr>
                <th>Year</th>
                <th>Membership</th>
                <th>Company</th>
                <th>Region</th>
                <th>Classification</th>
                <th>Standing</th>
                <th>Type</th>
                <th>Expiry</th>
                <th></th>
            </tr>
        </thead>
        <tbody>
        <?php foreach ($rows as $row): ?>
            <?php
            $standingVal = (string) ($row['active'] ?? '');
            $typeVal = (string) ($row['Status'] ?? '');
            $standClass = 'mi-chip';
            $sv = strtolower($standingVal);
            if ($sv === 'active') {
                $standClass .= ' is-ok';
            } elseif (str_contains($sv, 'pend') || str_contains($sv, 'progress')) {
                $standClass .= ' is-warn';
            } elseif (str_contains($sv, 'suspend') || str_contains($sv, 'declin')) {
                $standClass .= ' is-bad';
            }
            ?>
            <tr>
                <td><?= eca_admin_h((string) ($row['membership_year'] ?? '—')) ?></td>
                <td><?= eca_admin_h((string) (($row['MembershipNumber'] ?? '') !== '' ? $row['MembershipNumber'] : '—')) ?></td>
                <td><?= eca_admin_h((string) ($row['TradingName'] ?? $row['CompanyRegistrationName'] ?? '')) ?></td>
                <td><?= eca_admin_h((string) ($row['Region'] ?? '')) ?></td>
                <td><?= eca_admin_h((string) ($row['Clasification'] ?? '')) ?></td>
                <td><span class="<?= eca_admin_h($standClass) ?>"><?= eca_admin_h($standingVal !== '' ? $standingVal : '—') ?></span></td>
                <td><?= eca_admin_h($typeVal !== '' ? $typeVal : '—') ?></td>
                <td><?= eca_admin_h(eca_display_date((string) ($row['expiry_date'] ?? ''))) ?></td>
                <td><?php if (eca_can('members.manage')): ?><a href="/admin/member-detail.php?id=<?= (int) $row['client_id'] ?>">Open</a><?php else: ?>—<?php endif; ?></td>
            </tr>
        <?php endforeach; ?>
        <?php if (!$rows): ?>
            <tr data-hub-empty-row><td colspan="9">No members match the current filters.</td></tr>
        <?php endif; ?>
        </tbody>
    </table>
    <?php eca_render_request_pager($page, $totalPages, $total, $limit); ?>
    <?php endif; ?>
</div>

<div class="mi-split">
    <section class="hub-card">
        <h2>Classification breakdown</h2>
        <?php if (!$intel['groups']['classification']): ?>
            <p class="hub-empty">No classification data.</p>
        <?php else: ?>
            <ul class="mi-list">
                <?php foreach ($intel['groups']['classification'] as $label => $count): ?>
                    <li><?= eca_admin_h((string) $label) ?> — <strong><?= (int) $count ?></strong></li>
                <?php endforeach; ?>
            </ul>
        <?php endif; ?>
    </section>
    <section class="hub-card">
        <h2>Region breakdown</h2>
        <?php if (!$intel['groups']['region']): ?>
            <p class="hub-empty">No region data.</p>
        <?php else: ?>
            <ul class="mi-list">
                <?php foreach ($intel['groups']['region'] as $label => $count): ?>
                    <li><?= eca_admin_h((string) $label) ?> — <strong><?= (int) $count ?></strong></li>
                <?php endforeach; ?>
            </ul>
        <?php endif; ?>
    </section>
    <section class="hub-card">
        <h2>Membership years</h2>
        <?php if (!$intel['groups']['years']): ?>
            <p class="hub-empty">No membership_years rows yet.</p>
        <?php else: ?>
            <ul class="mi-list">
                <?php foreach ($intel['groups']['years'] as $label => $count): ?>
                    <li><?= eca_admin_h((string) $label) ?> — <strong><?= (int) $count ?></strong> year row(s)</li>
                <?php endforeach; ?>
            </ul>
        <?php endif; ?>
    </section>
</div>

<div class="hub-card">
    <h2>Data quality (read-only)</h2>
    <p>These checks do not modify data. Investigate before changing records.</p>
    <table class="hub-table">
        <thead><tr><th>Check</th><th>Count</th></tr></thead>
        <tbody>
        <?php foreach ($quality as $item): ?>
            <tr>
                <td><?= eca_admin_h($item['label']) ?></td>
                <td><strong><?= (int) $item['count'] ?></strong></td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</div>

<details class="hub-card" style="margin-top:14px;">
    <summary><strong>KPI SQL sources</strong> (for auditors)</summary>
    <ul class="mi-list" style="margin-top:10px;">
        <?php foreach ($intel['sources'] as $key => $sql): ?>
            <li><code><?= eca_admin_h($key) ?></code><br><small><?= eca_admin_h($sql) ?></small></li>
        <?php endforeach; ?>
    </ul>
</details>

<?php
eca_admin_hub_end();
