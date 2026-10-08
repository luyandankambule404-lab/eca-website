<?php
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/_hub.php';
require_once __DIR__ . '/../includes/portal-db.php';
require_once __DIR__ . '/../includes/companies-intelligence.php';
require_once __DIR__ . '/../includes/membership.php';

eca_admin_require('companies.manage');
header('Cache-Control: no-store');

$id = (int) ($_GET['id'] ?? 0);
$local = eca_admin_db();
$portal = eca_portal_pdo(false);
$company = eca_ci_load_company($local, $id);
if (!$company) {
    eca_not_found('Company not found.');
}

$members = eca_match_members_for_company($portal, $company);
$bundles = [];
foreach ($members as $member) {
    $cid = (int) ($member['client_id'] ?? 0);
    if ($cid > 0) {
        $bundles[$cid] = eca_ci_member_bundle($portal, $cid);
    }
}

$printMode = isset($_GET['print']) && (string) $_GET['print'] === '1';
$title = (string) ($company['name'] ?? 'Company');

if (!$printMode) {
    eca_audit('company.viewed', 'companies', (string) $id, [
        'name' => (string) ($company['name'] ?? ''),
        'matched_members' => count($members),
    ]);
}
if ($printMode) {
    ?><!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title><?= eca_admin_h($title) ?> | ECA</title>
    <style>
        body{font-family:system-ui,sans-serif;margin:24px;color:#122}
        h1{margin:0 0 8px;font-size:1.4rem}
        table{width:100%;border-collapse:collapse;font-size:12px;margin:12px 0}
        th,td{border:1px solid #ccd;padding:6px 8px;text-align:left}
        th{background:#eef2f8}
        .meta{color:#556;margin-bottom:16px}
        @media print{.no-print{display:none}}
    </style>
</head>
<body>
<p class="no-print"><button onclick="window.print()">Print</button> · <a href="/admin/company-detail.php?id=<?= (int) $id ?>">Back</a></p>
<h1><?= eca_admin_h($title) ?></h1>
<p class="meta">Local company detail · generated <?= eca_admin_h(date('Y-m-d H:i')) ?></p>
<table>
    <tr><th>Registration number</th><td><?= eca_admin_h((string) ($company['registration_number'] ?? '—')) ?></td></tr>
    <tr><th>Directory status</th><td><?= eca_admin_h((string) ($company['status'] ?? '—')) ?></td></tr>
    <tr><th>Industry</th><td><?= eca_admin_h((string) ($company['industry'] ?? '—')) ?></td></tr>
    <tr><th>Region / address</th><td><?= eca_admin_h((string) ($company['address'] ?? '—')) ?></td></tr>
    <tr><th>Email</th><td><?= eca_admin_h((string) ($company['email'] ?? '—')) ?></td></tr>
    <tr><th>Phone</th><td><?= eca_admin_h((string) ($company['phone'] ?? '—')) ?></td></tr>
</table>
<h2>Matched membership records</h2>
<?php if (!$members): ?>
<p>DATA NOT AVAILABLE LOCALLY — no soft membership match.</p>
<?php else: ?>
<table>
    <thead><tr><th>Membership</th><th>Trading name</th><th>Standing</th><th>Type</th></tr></thead>
    <tbody>
    <?php foreach ($members as $m): ?>
        <tr>
            <td><?= eca_admin_h((string) ($m['MembershipNumber'] ?? '—')) ?></td>
            <td><?= eca_admin_h((string) ($m['TradingName'] ?? '')) ?></td>
            <td><?= eca_admin_h((string) ($m['active'] ?? '')) ?></td>
            <td><?= eca_admin_h((string) ($m['Status'] ?? '')) ?></td>
        </tr>
    <?php endforeach; ?>
    </tbody>
</table>
<?php endif; ?>
</body>
</html>
    <?php
    exit;
}

eca_admin_hub_start('Company details', 'companies');
?>
<style>
.ci-grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(180px,1fr));gap:10px;margin:12px 0}
.ci-card{border:1px solid #d7dde8;border-radius:12px;padding:12px 14px;background:#fff}
.ci-card span{display:block;font-size:.75rem;color:#667;font-weight:700}
.ci-card strong{display:block;margin-top:4px;font-size:1.05rem}
.ci-note{background:#f7f9fc;border-left:4px solid #1f4b7a;padding:10px 12px;border-radius:8px;margin:12px 0;font-size:.9rem}
.ci-actions{display:flex;flex-wrap:wrap;gap:8px;margin:10px 0}
.ci-empty{color:#6b7280;font-style:italic}
</style>

<div class="hub-hello">
    <h1 class="hub-hello-title"><?= eca_admin_h($title) ?></h1>
    <p>Directory company from <code>eca_local.companies</code>. Membership/owner links are soft matches — there is no hard foreign key to <code>tbl_client</code>.</p>
</div>

<div class="ci-actions">
    <a class="hub-btn" href="/admin/companies.php">Back to companies</a>
    <a class="hub-btn" href="/admin/company-edit.php?id=<?= (int) $id ?>">Edit directory fields</a>
    <a class="hub-btn" href="/contractor.php?id=<?= (int) $id ?>">Public profile</a>
    <a class="hub-btn" href="?id=<?= (int) $id ?>&amp;print=1">Print-friendly</a>
    <a class="hub-btn" href="/admin/owners-report.php?search=<?= urlencode((string) ($company['name'] ?? '')) ?>">Owners report</a>
</div>

<div class="hub-card-head"><h2>Company profile</h2></div>
<div class="ci-grid">
    <article class="ci-card"><span>Registration number</span><strong><?= eca_admin_h((string) (($company['registration_number'] ?? '') !== '' ? $company['registration_number'] : '—')) ?></strong></article>
    <article class="ci-card"><span>Directory status</span><strong><?= eca_admin_h((string) ($company['status'] ?? '—')) ?></strong></article>
    <article class="ci-card"><span>Industry / classification</span><strong><?= eca_admin_h((string) ($company['industry'] ?? '—')) ?></strong></article>
    <article class="ci-card"><span>Region / address</span><strong><?= eca_admin_h((string) ($company['address'] ?? '—')) ?></strong></article>
    <article class="ci-card"><span>Email</span><strong><?= eca_admin_h((string) ($company['email'] ?? '—')) ?></strong></article>
    <article class="ci-card"><span>Phone</span><strong><?= eca_admin_h((string) ($company['phone'] ?? '—')) ?></strong></article>
    <article class="ci-card"><span>Website</span><strong><?= eca_admin_h((string) (($company['website'] ?? '') !== '' ? $company['website'] : '—')) ?></strong></article>
</div>
<?php if (trim((string) ($company['description'] ?? '')) !== ''): ?>
<div class="hub-card"><p><?= nl2br(eca_admin_h((string) $company['description'])) ?></p></div>
<?php endif; ?>

<div class="ci-note">
    Soft membership match uses exact <code>registration_number = MembershipNumber</code>, email, or company name.
    This is <strong>not</strong> a confirmed legal ownership link.
</div>

<div class="hub-card-head"><h2>Related members</h2></div>
<div class="hub-card eca-table-panel">
<?php if (!$members): ?>
    <p class="ci-empty">DATA NOT AVAILABLE LOCALLY — no matching <code>tbl_client</code> row for this directory company.</p>
<?php else: ?>
    <table class="hub-table">
        <thead><tr><th>Membership</th><th>Trading name</th><th>Standing</th><th>Type</th><th>Application</th><th></th></tr></thead>
        <tbody>
        <?php foreach ($members as $m): ?>
            <tr>
                <td><?= eca_admin_h((string) (($m['MembershipNumber'] ?? '') !== '' ? $m['MembershipNumber'] : '—')) ?></td>
                <td><?= eca_admin_h((string) ($m['TradingName'] ?? $m['CompanyRegistrationName'] ?? '')) ?></td>
                <td><?= eca_admin_h((string) ($m['active'] ?? '—')) ?></td>
                <td><?= eca_admin_h((string) ($m['Status'] ?? '—')) ?></td>
                <td><?= eca_admin_h((string) (($m['application_reference'] ?? '') !== '' ? (($m['application_status'] ?? '') . ' · ' . $m['application_reference']) : '—')) ?></td>
                <td><?php if (eca_can('members.manage')): ?><a href="/admin/member-detail.php?id=<?= (int) $m['client_id'] ?>">Open</a><?php else: ?>—<?php endif; ?></td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
<?php endif; ?>
</div>

<?php foreach ($members as $m): ?>
    <?php
    $cid = (int) ($m['client_id'] ?? 0);
    $bundle = $bundles[$cid] ?? ['years' => [], 'certs' => [], 'apps' => [], 'docs' => 0, 'owners' => []];
    $label = trim((string) ($m['MembershipNumber'] ?? '')) ?: ('Client #' . $cid);
    ?>
    <div class="hub-card-head"><h2>Membership detail · <?= eca_admin_h($label) ?></h2></div>
    <div class="hub-split">
        <section class="hub-card eca-table-panel">
            <h5>Membership years</h5>
            <?php if (!$bundle['years']): ?>
                <p class="ci-empty">DATA NOT AVAILABLE LOCALLY — no <code>membership_years</code> rows.</p>
            <?php else: ?>
                <table class="hub-table">
                    <thead><tr><th>Year</th><th>Type</th><th>Status</th><th>Expiry</th></tr></thead>
                    <tbody>
                    <?php foreach ($bundle['years'] as $y): ?>
                        <tr>
                            <td><?= eca_admin_h((string) ($y['year'] ?? '')) ?></td>
                            <td><?= eca_admin_h((string) ($y['type'] ?? '')) ?></td>
                            <td><?= eca_admin_h((string) ($y['status'] ?? '')) ?></td>
                            <td><?= eca_admin_h(eca_display_date((string) ($y['expiry_date'] ?? ''))) ?></td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </section>
        <section class="hub-card eca-table-panel">
            <h5>Certificates</h5>
            <?php if (!$bundle['certs']): ?>
                <p class="ci-empty">DATA NOT AVAILABLE LOCALLY — no certificates for this client.</p>
            <?php else: ?>
                <table class="hub-table">
                    <thead><tr><th>Number</th><th>Status</th><th>Issued</th><th>Expiry</th><th></th></tr></thead>
                    <tbody>
                    <?php foreach ($bundle['certs'] as $cert): ?>
                        <tr>
                            <td><?= eca_admin_h((string) ($cert['certificate_number'] ?? '')) ?></td>
                            <td><?= eca_admin_h((string) ($cert['status'] ?? '')) ?></td>
                            <td><?= eca_admin_h(eca_display_date((string) ($cert['issued_at'] ?? ''))) ?></td>
                            <td><?= eca_admin_h(eca_display_date((string) ($cert['expiry_date'] ?? ''))) ?></td>
                            <td><a href="/verify.php?cert=<?= urlencode((string) ($cert['certificate_number'] ?? '')) ?>">Verify</a></td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
            <p class="hub-stat-meta">Documents on file: <?= (int) $bundle['docs'] ?></p>
        </section>
    </div>
    <div class="hub-card eca-table-panel">
        <h5>Owners / shareholders</h5>
        <?php if (!$bundle['owners']): ?>
            <p class="ci-empty">DATA NOT AVAILABLE LOCALLY — no rows in <code>owners</code> for this client. Owners are created when membership/renewal applications record ownership.</p>
        <?php else: ?>
            <table class="hub-table">
                <thead><tr><th>Owner</th><th>Gender</th><th>Citizen</th><th>Shares</th></tr></thead>
                <tbody>
                <?php foreach ($bundle['owners'] as $o): ?>
                    <tr>
                        <td><?= eca_admin_h((string) ($o['name'] ?? '')) ?></td>
                        <td><?= eca_admin_h((string) ($o['gender'] ?? '—')) ?></td>
                        <td><?= eca_admin_h((string) ($o['citizen'] ?? '—')) ?></td>
                        <td><?= eca_admin_h((string) (($o['shares'] ?? '') !== '' ? $o['shares'] : '—')) ?></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </div>
    <div class="hub-card eca-table-panel">
        <h5>Application history</h5>
        <?php if (!$bundle['apps']): ?>
            <p class="ci-empty">DATA NOT AVAILABLE LOCALLY — no application reference on this client.</p>
        <?php else: ?>
            <table class="hub-table">
                <thead><tr><th>Reference</th><th>Status</th><th>Registered</th><th></th></tr></thead>
                <tbody>
                <?php foreach ($bundle['apps'] as $app): ?>
                    <tr>
                        <td><?= eca_admin_h((string) ($app['application_reference'] ?? '')) ?></td>
                        <td><?= eca_admin_h((string) ($app['application_status'] ?? '')) ?></td>
                        <td><?= eca_admin_h(eca_display_date((string) ($app['DateOfRegistration'] ?? $app['created_at'] ?? ''))) ?></td>
                        <td><?php if (eca_can('applications.manage')): ?><a href="/admin/application-detail.php?id=<?= (int) $app['client_id'] ?>">Open</a><?php else: ?>—<?php endif; ?></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </div>
<?php endforeach; ?>

<?php
eca_admin_hub_end();
