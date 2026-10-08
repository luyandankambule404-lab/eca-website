<?php
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/_hub.php';
require_once __DIR__ . '/../includes/portal-db.php';
require_once __DIR__ . '/../includes/companies-intelligence.php';
require_once __DIR__ . '/../includes/membership.php';
require_once __DIR__ . '/../includes/pagination.php';

eca_admin_require('companies.manage');
header('Cache-Control: no-store');

$portal = eca_portal_pdo(false);
$printMode = isset($_GET['print']) && (string) $_GET['print'] === '1';

$filters = [
    'search' => trim((string) ($_GET['search'] ?? '')),
    'standing' => trim((string) ($_GET['standing'] ?? '')),
    'classification' => trim((string) ($_GET['classification'] ?? '')),
    'region' => trim((string) ($_GET['region'] ?? '')),
    'owner_presence' => trim((string) ($_GET['owner_presence'] ?? '')),
    'year' => trim((string) ($_GET['year'] ?? '')),
];
$sort = trim((string) ($_GET['sort'] ?? 'company'));

if ($filters['standing'] !== '' && !in_array($filters['standing'], eca_member_standing_values(), true)) {
    $filters['standing'] = '';
}
if ($filters['owner_presence'] !== '' && !in_array($filters['owner_presence'], ['yes', 'no'], true)) {
    $filters['owner_presence'] = '';
}
if ($filters['year'] !== '' && !preg_match('/^\d{4}$/', $filters['year'])) {
    $filters['year'] = '';
}

$built = eca_ci_owners_filters($filters);
$order = eca_ci_owners_sort($sort);
$page = eca_pager_page();
$limit = eca_pager_limit(10);
$rows = [];
$total = 0;
$totalPages = 1;
$intel = eca_ci_company_intelligence(eca_admin_db(), $portal);
$kpis = $intel['kpis'];

$classifications = [];
$regions = [];
$years = [];
if ($portal) {
    try {
        $classifications = $portal->query("SELECT DISTINCT Clasification FROM tbl_client WHERE Clasification IS NOT NULL AND TRIM(Clasification) <> '' ORDER BY Clasification")->fetchAll(PDO::FETCH_COLUMN) ?: [];
        $regions = $portal->query("SELECT DISTINCT Region FROM tbl_client WHERE Region IS NOT NULL AND TRIM(Region) <> '' ORDER BY Region")->fetchAll(PDO::FETCH_COLUMN) ?: [];
        $years = $portal->query("SELECT DISTINCT year FROM membership_years WHERE year IS NOT NULL AND TRIM(year) <> '' ORDER BY year DESC")->fetchAll(PDO::FETCH_COLUMN) ?: [];
    } catch (Throwable $e) {
        // leave empty
    }

    $countSql = "SELECT COUNT(*) FROM tbl_client c LEFT JOIN owners o ON o.clientid = c.client_id WHERE {$built['where']}";
    $selectSql = "SELECT
            c.client_id,
            c.TradingName,
            c.CompanyRegistrationName,
            c.MembershipNumber,
            c.Region,
            c.Clasification,
            c.Enterprise,
            c.Status,
            c.active,
            c.EmailAddress,
            o.id AS owner_id,
            o.name AS owner_name,
            o.gender AS owner_gender,
            o.citizen AS owner_citizen,
            o.shares AS owner_shares,
            (SELECT COUNT(*) FROM owners ox WHERE ox.clientid = c.client_id) AS owners_count
         FROM tbl_client c
         LEFT JOIN owners o ON o.clientid = c.client_id
         WHERE {$built['where']}
         ORDER BY {$order}";
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
            $ownerName = trim((string) ($row['owner_name'] ?? ''));
            $csv[] = [
                $row['client_id'] ?? '',
                $row['TradingName'] ?? '',
                $row['CompanyRegistrationName'] ?? '',
                $row['MembershipNumber'] ?? '',
                $row['Region'] ?? '',
                $row['Clasification'] ?? '',
                $row['Enterprise'] ?? '',
                $row['active'] ?? '',
                $row['Status'] ?? '',
                $ownerName !== '' ? $ownerName : 'DATA NOT AVAILABLE LOCALLY',
                $ownerName !== '' ? ($row['owner_gender'] ?? '') : '',
                $ownerName !== '' ? ($row['owner_citizen'] ?? '') : '',
                $ownerName !== '' ? ($row['owner_shares'] ?? '') : '',
                $row['owners_count'] ?? 0,
            ];
        }
        eca_admin_send_csv(
            'eca-owners-report',
            [
                'Client ID', 'Trading name', 'Registered company', 'Membership', 'Region', 'Classification',
                'Enterprise', 'Standing', 'Type', 'Owner', 'Gender', 'Citizen', 'Shares', 'Owners count',
            ],
            $csv,
            'owners_report'
        );
    }
}

$queryKeep = array_filter(array_merge($filters, ['sort' => $sort]), static fn ($v) => $v !== '' && $v !== null);

if ($printMode) {
    ?><!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Owners Report | ECA</title>
    <style>
        body{font-family:system-ui,sans-serif;margin:24px}
        table{width:100%;border-collapse:collapse;font-size:12px}
        th,td{border:1px solid #ccd;padding:6px 8px;text-align:left}
        th{background:#eef2f8}
        @media print{.no-print{display:none}}
    </style>
</head>
<body>
<p class="no-print"><button onclick="window.print()">Print</button> · <a href="/admin/owners-report.php?<?= eca_admin_h(http_build_query($queryKeep)) ?>">Back</a></p>
<h1>Companies &amp; Owners Report</h1>
<p>Local data only · <?= (int) $total ?> row(s) · owners available globally: <?= (int) $kpis['owners_total'] ?></p>
<table>
    <thead><tr><th>Client</th><th>Company</th><th>Membership</th><th>Standing</th><th>Owner</th><th>Shares</th><th>Owners #</th></tr></thead>
    <tbody>
    <?php if (!$rows): ?>
        <tr><td colspan="7">No rows match.</td></tr>
    <?php else: foreach ($rows as $row): ?>
        <tr>
            <td><?= (int) ($row['client_id'] ?? 0) ?></td>
            <td><?= eca_admin_h((string) ($row['TradingName'] ?? $row['CompanyRegistrationName'] ?? '')) ?></td>
            <td><?= eca_admin_h((string) ($row['MembershipNumber'] ?? '—')) ?></td>
            <td><?= eca_admin_h((string) ($row['active'] ?? '')) ?></td>
            <td><?= eca_admin_h(trim((string) ($row['owner_name'] ?? '')) !== '' ? (string) $row['owner_name'] : 'DATA NOT AVAILABLE LOCALLY') ?></td>
            <td><?= eca_admin_h((string) ($row['owner_shares'] ?? '—')) ?></td>
            <td><?= (int) ($row['owners_count'] ?? 0) ?></td>
        </tr>
    <?php endforeach; endif; ?>
    </tbody>
</table>
<p>Print shows the current page. Use CSV for the full filtered export (within export limit).</p>
</body>
</html>
    <?php
    exit;
}

eca_audit('report.view', 'owners_report', 'list', ['total' => $total]);
eca_admin_hub_start('Owners Report', 'owners_report');
?>
<style>
.ci-grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(150px,1fr));gap:12px;margin:12px 0}
.ci-card{background:#fff;border:1px solid #d7dde8;border-radius:14px;padding:14px}
.ci-card span{display:block;font-size:.75rem;color:#667;font-weight:700}
.ci-card strong{display:block;font-size:1.35rem;margin-top:4px}
.ci-card small{display:block;margin-top:4px;color:#6b7280;font-size:.72rem}
.ci-note{background:#fff5df;border-left:4px solid #b78103;padding:12px;border-radius:8px;margin:12px 0}
.ci-filters{display:grid;grid-template-columns:repeat(auto-fit,minmax(160px,1fr));gap:10px;align-items:end}
.ci-filters label{display:block;font-size:.75rem;font-weight:700;margin-bottom:4px}
.ci-filters input,.ci-filters select{width:100%;padding:10px 12px;border-radius:10px;border:1px solid #d0d5dd}
.ci-actions{display:flex;flex-wrap:wrap;gap:8px;margin-top:10px}
.ci-empty{color:#6b7280;font-style:italic}
</style>

<div class="hub-hello">
    <h1 class="hub-hello-title">Companies &amp; Owners Report</h1>
    <p>Built from <code>tbl_client</code> LEFT JOIN <code>owners</code> on <code>owners.clientid</code>. Owner fields are never invented.</p>
</div>

<?php if ((int) $kpis['owners_total'] === 0): ?>
<div class="ci-note">
    <strong>Owner rows in local DB: 0.</strong>
    The report still lists membership clients. Owner columns show <em>DATA NOT AVAILABLE LOCALLY</em> until ownership is captured via applications/renewals.
</div>
<?php endif; ?>

<div class="ci-grid">
    <article class="ci-card"><span>Membership clients</span><strong><?= (int) $kpis['clients_total'] ?></strong><small>tbl_client COUNT(*)</small></article>
    <article class="ci-card"><span>Owner rows</span><strong><?= (int) $kpis['owners_total'] ?></strong><small>owners COUNT(*)</small></article>
    <article class="ci-card"><span>Clients with owners</span><strong><?= (int) $kpis['clients_with_owners'] ?></strong><small>DISTINCT owners.clientid</small></article>
    <article class="ci-card"><span>Directory companies</span><strong><?= (int) $kpis['companies_total'] ?></strong><small>eca_local.companies</small></article>
</div>

<div class="hub-card">
    <h2 style="margin-top:0;">Filters</h2>
    <form method="get" class="ci-filters">
        <div>
            <label for="or-search">Search</label>
            <input id="or-search" type="search" name="search" value="<?= eca_admin_h($filters['search']) ?>" placeholder="Company, membership, owner">
        </div>
        <div>
            <label for="or-standing">Standing</label>
            <select id="or-standing" name="standing">
                <option value="">All</option>
                <?php foreach (eca_member_standing_values() as $opt): ?>
                    <option value="<?= eca_admin_h($opt) ?>"<?= $filters['standing'] === $opt ? ' selected' : '' ?>><?= eca_admin_h($opt) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div>
            <label for="or-class">Classification</label>
            <select id="or-class" name="classification">
                <option value="">All</option>
                <?php foreach ($classifications as $opt): ?>
                    <option value="<?= eca_admin_h((string) $opt) ?>"<?= $filters['classification'] === (string) $opt ? ' selected' : '' ?>><?= eca_admin_h((string) $opt) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div>
            <label for="or-region">Region</label>
            <select id="or-region" name="region">
                <option value="">All</option>
                <?php foreach ($regions as $opt): ?>
                    <option value="<?= eca_admin_h((string) $opt) ?>"<?= $filters['region'] === (string) $opt ? ' selected' : '' ?>><?= eca_admin_h((string) $opt) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div>
            <label for="or-year">Membership year</label>
            <select id="or-year" name="year">
                <option value="">All</option>
                <?php foreach ($years as $opt): ?>
                    <option value="<?= eca_admin_h((string) $opt) ?>"<?= $filters['year'] === (string) $opt ? ' selected' : '' ?>><?= eca_admin_h((string) $opt) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div>
            <label for="or-own">Owner data</label>
            <select id="or-own" name="owner_presence">
                <option value="">All clients</option>
                <option value="yes"<?= $filters['owner_presence'] === 'yes' ? ' selected' : '' ?>>Has owner row</option>
                <option value="no"<?= $filters['owner_presence'] === 'no' ? ' selected' : '' ?>>Missing owner row</option>
            </select>
        </div>
        <div>
            <label for="or-sort">Sort</label>
            <select id="or-sort" name="sort">
                <?php foreach (['company' => 'Company', 'owner' => 'Owner', 'membership' => 'Membership', 'region' => 'Region', 'classification' => 'Classification', 'standing' => 'Standing'] as $k => $lab): ?>
                    <option value="<?= eca_admin_h($k) ?>"<?= $sort === $k ? ' selected' : '' ?>><?= eca_admin_h($lab) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div>
            <label for="or-per">Rows / page</label>
            <select id="or-per" name="per_page">
                <?php foreach (eca_pager_allowed_limits() as $opt): ?>
                    <option value="<?= (int) $opt ?>"<?= $limit === (int) $opt ? ' selected' : '' ?>><?= (int) $opt ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="ci-actions" style="grid-column:1/-1;margin:0">
            <button class="hub-btn" type="submit">Apply</button>
            <a class="hub-btn" href="/admin/owners-report.php">Reset</a>
            <?= eca_admin_csv_button('Export CSV (filtered)') ?>
            <a class="hub-btn" href="?<?= eca_admin_h(http_build_query(array_merge($queryKeep, ['print' => '1', 'page' => (string) $page, 'per_page' => (string) $limit]))) ?>">Print-friendly</a>
            <a class="hub-btn" href="/admin/companies.php">Companies</a>
        </div>
    </form>
</div>

<div class="hub-card eca-table-panel" style="margin-top:14px;">
    <h2>Results (<?= (int) $total ?>)</h2>
    <?php if (!$portal): ?>
        <p class="ci-empty">Portal database unavailable.</p>
    <?php else: ?>
    <table class="hub-table" data-dash-server-page="1">
        <thead>
            <tr>
                <th>Client</th>
                <th>Company</th>
                <th>Membership</th>
                <th>Region</th>
                <th>Classification</th>
                <th>Standing</th>
                <th>Owner</th>
                <th>Gender</th>
                <th>Citizen</th>
                <th>Shares</th>
                <th>Owners #</th>
                <th></th>
            </tr>
        </thead>
        <tbody>
        <?php foreach ($rows as $row): ?>
            <?php $ownerName = trim((string) ($row['owner_name'] ?? '')); ?>
            <tr>
                <td><?= (int) ($row['client_id'] ?? 0) ?></td>
                <td><?= eca_admin_h((string) ($row['TradingName'] ?? $row['CompanyRegistrationName'] ?? '')) ?></td>
                <td><?= eca_admin_h((string) (($row['MembershipNumber'] ?? '') !== '' ? $row['MembershipNumber'] : '—')) ?></td>
                <td><?= eca_admin_h((string) ($row['Region'] ?? '—')) ?></td>
                <td><?= eca_admin_h((string) ($row['Clasification'] ?? '—')) ?></td>
                <td><?= eca_admin_h((string) ($row['active'] ?? '—')) ?></td>
                <td><?= $ownerName !== '' ? eca_admin_h($ownerName) : '<em class="ci-empty">DATA NOT AVAILABLE LOCALLY</em>' ?></td>
                <td><?= $ownerName !== '' ? eca_admin_h((string) ($row['owner_gender'] ?? '—')) : '—' ?></td>
                <td><?= $ownerName !== '' ? eca_admin_h((string) ($row['owner_citizen'] ?? '—')) : '—' ?></td>
                <td><?= $ownerName !== '' ? eca_admin_h((string) (($row['owner_shares'] ?? '') !== '' ? $row['owner_shares'] : '—')) : '—' ?></td>
                <td><?= (int) ($row['owners_count'] ?? 0) ?></td>
                <td><?php if (eca_can('members.manage')): ?><a href="/admin/member-detail.php?id=<?= (int) $row['client_id'] ?>">Member</a><?php else: ?>—<?php endif; ?></td>
            </tr>
        <?php endforeach; ?>
        <?php if (!$rows): ?>
            <tr data-hub-empty-row><td colspan="12">No clients match the current filters.</td></tr>
        <?php endif; ?>
        </tbody>
    </table>
    <?php eca_render_request_pager($page, $totalPages, $total, $limit); ?>
    <?php endif; ?>
</div>
<?php
eca_admin_hub_end();
