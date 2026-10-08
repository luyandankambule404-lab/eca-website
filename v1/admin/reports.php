<?php
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/_hub.php';
require_once __DIR__ . '/../includes/portal-db.php';
require_once __DIR__ . '/../includes/admin-stats.php';
eca_admin_require('reports.view');
header('Cache-Control: no-store');

$local = eca_admin_db();
$portal = eca_portal_pdo(false);
$stats = eca_admin_dashboard_stats($local, $portal);
$yearRows = [];
if ($portal) {
    $yearRows = eca_safe_groups($portal, 'SELECT year, COUNT(*) FROM membership_years GROUP BY year');
}
$canExport = eca_can('reports.export');

if ($canExport && eca_admin_export_requested()) {
    $rows = [
        ['Membership', 'Numbered members', $stats['members_total']],
        ['Membership', 'Active standing', $stats['members_active']],
        ['Membership', 'Pending standing', $stats['members_pending']],
        ['Membership', 'Expired membership years', $stats['members_expired']],
        ['Applications', 'With reference', $stats['applications_total']],
        ['Applications', 'Pending review', $stats['applications_pending']],
        ['Applications', 'Approved', $stats['applications_approved']],
        ['Applications', 'Rejected', $stats['applications_rejected']],
        ['Finance', 'Payment proofs pending', $stats['payments_pending']],
        ['Finance', 'Payment proofs verified', $stats['payments_verified']],
        ['Finance', 'Payment proofs rejected', $stats['payments_rejected']],
        ['Finance', 'Outstanding balance rows', $stats['balances_due']],
        ['CPD', 'Applications', $stats['cpd_applications']],
        ['CPD', 'Pending applications', $stats['cpd_pending']],
        ['CPD', 'Courses', $stats['cpd_courses']],
        ['CPD', 'Points ledger sum', $stats['cpd_points']],
        ['Wellness', 'Upcoming events', $stats['wellness_events']],
        ['Wellness', 'Announcements', $stats['wellness_announcements']],
        ['Wellness', 'Published resources', $stats['wellness_resources']],
        ['Wellness', 'Check-ins', $stats['wellness_checkins']],
        ['Wellness', 'Registrations', $stats['wellness_registrations']],
    ];
    if (eca_can('security.view')) {
        $rows[] = ['System', 'Registered officers', $stats['officers_total']];
        $rows[] = ['System', 'Super Admins', $stats['super_admins']];
    }
    eca_admin_send_csv('eca-local-report', ['Category', 'Metric', 'Count'], $rows, 'reports');
}

eca_admin_hub_start('Reports', 'reports');
$canCompanies = eca_can('companies.manage');
$canMembers = eca_can('members.manage');
$canApplications = eca_can('applications.manage');
$canPayments = eca_can('payments.manage');
$canCertificates = eca_can('certificates.manage');
$canCpd = eca_can('cpd.view');
$canWellness = eca_can('wellness.manage');
?>
<div class="hub-hello">
    <h1 class="hub-hello-title">Reports</h1>
    <p>Local reports generated from <code>eca_local</code> / <code>eca_portal_local</code>. Excel/PDF export is deferred — use CSV or print-friendly views where available.</p>
</div>

<div class="hub-card" style="margin-bottom:14px;">
    <h2 style="margin-top:0;">Report catalogue</h2>
    <div class="hub-tiles" style="margin-top:10px;">
        <a class="hub-tile" href="/admin/membership-report.php"><h3>Member status</h3><p>Standing, type, classification, region.</p><span>Open →</span></a>
        <a class="hub-tile" href="/admin/members.php?year_state=near"><h3>Membership expiry</h3><p>Years expiring within 90 days.</p><span>Open →</span></a>
        <a class="hub-tile" href="/admin/members.php?type=Joining"><h3>New / joining</h3><p>Joining applications and members.</p><span>Open →</span></a>
        <a class="hub-tile" href="/admin/members.php?year_state=renewal_pending"><h3>Renewals</h3><p>Pending renewal year rows.</p><span>Open →</span></a>
        <a class="hub-tile" href="/admin/membership-report.php?period=2025-2026"><h3>2025 / 2026</h3><p>Period membership intelligence.</p><span>Open →</span></a>
        <?php if ($canCompanies): ?>
        <a class="hub-tile" href="/admin/companies.php"><h3>Company directory</h3><p>Search, filter, CSV export.</p><span>Open →</span></a>
        <a class="hub-tile" href="/admin/companies.php?status=active"><h3>Active companies</h3><p>Directory status = active.</p><span>Open →</span></a>
        <a class="hub-tile" href="/admin/owners-report.php"><h3>Owners report</h3><p>Client ↔ owner rows (empty owners labelled).</p><span>Open →</span></a>
        <?php endif; ?>
        <?php if ($canApplications): ?>
        <a class="hub-tile" href="/admin/applications.php"><h3>Application status</h3><p>All applications with reference.</p><span>Open →</span></a>
        <a class="hub-tile" href="/admin/applications.php?status=APPROVED"><h3>Approved applications</h3><p>Approved queue.</p><span>Open →</span></a>
        <a class="hub-tile" href="/admin/applications.php?status=REJECTED"><h3>Rejected applications</h3><p>Rejected queue.</p><span>Open →</span></a>
        <a class="hub-tile" href="/admin/applications.php"><h3>Pending applications</h3><p>Submitted / review / returned.</p><span>Open →</span></a>
        <?php endif; ?>
        <?php if ($canPayments): ?>
        <a class="hub-tile" href="/admin/payments.php"><h3>Payment status</h3><p>All payment proofs.</p><span>Open →</span></a>
        <a class="hub-tile" href="/admin/payments.php?status=pending"><h3>Pending proof</h3><p>Awaiting verification.</p><span>Open →</span></a>
        <a class="hub-tile" href="/admin/payments.php?status=verified"><h3>Verified payments</h3><p>Approved / verified.</p><span>Open →</span></a>
        <a class="hub-tile" href="/admin/payments.php?status=rejected"><h3>Rejected payments</h3><p>Rejected proofs.</p><span>Open →</span></a>
        <?php endif; ?>
        <?php if ($canCertificates): ?>
        <a class="hub-tile" href="/admin/certificates.php"><h3>Certificates</h3><p>Active, revoked, expiry.</p><span>Open →</span></a>
        <?php endif; ?>
        <?php if ($canCpd): ?>
        <a class="hub-tile" href="/admin/cpd.php"><h3>CPD reports</h3><p>Applications, courses, points.</p><span>Open →</span></a>
        <?php endif; ?>
        <?php if ($canWellness): ?>
        <a class="hub-tile" href="/admin/wellness/"><h3>Wellness</h3><p>Events, registrations, announcements.</p><span>Open →</span></a>
        <?php endif; ?>
    </div>
    <p class="hub-note" style="margin-top:12px;">Excel/PDF: <strong>DEFERRED</strong> (not in local codebase). CSV and print remain supported on key reports.</p>
</div>

<p style="display:flex;flex-wrap:wrap;gap:8px;margin:0 0 14px;">
    <a class="hub-btn" href="/admin/membership-report.php">Membership Intelligence</a>
    <a class="hub-btn" href="/admin/membership-report.php?period=2025-2026">2025 / 2026 Membership Report</a>
    <?php if ($canCompanies): ?>
        <a class="hub-btn" href="/admin/companies.php">Companies</a>
        <a class="hub-btn" href="/admin/owners-report.php">Owners Report</a>
    <?php endif; ?>
</p>
<?php if ($canExport): ?>
<p class="hub-card"><?= eca_admin_csv_button('Download CSV summary') ?></p>
<?php endif; ?>

<div class="hub-card-head"><h2>Membership</h2></div>
<div class="hub-summary">
    <div><span>All clients</span><strong><?= (int) ($stats['members_all_clients'] ?? 0) ?></strong></div>
    <div><span>Numbered members</span><strong><?= (int) $stats['members_total'] ?></strong></div>
    <div><span>Without membership #</span><strong><?= (int) ($stats['members_without_number'] ?? 0) ?></strong></div>
    <div><span>Active standing</span><strong><?= (int) $stats['members_active'] ?></strong></div>
    <div><span>Pending standing</span><strong><?= (int) $stats['members_pending'] ?></strong></div>
    <div><span>Joining type</span><strong><?= (int) ($stats['members_joining'] ?? 0) ?></strong></div>
    <div><span>Renewal type</span><strong><?= (int) ($stats['members_renewal_type'] ?? 0) ?></strong></div>
    <div><span>Suspended</span><strong><?= (int) $stats['members_suspended'] ?></strong></div>
    <div><span>Declined</span><strong><?= (int) ($stats['members_declined'] ?? 0) ?></strong></div>
    <div><span>Expired years</span><strong><?= (int) $stats['members_expired'] ?></strong></div>
    <div><span>2025/2026 clients</span><strong><?= (int) ($stats['membership_period_2025_2026'] ?? 0) ?></strong></div>
    <div><span>Pending renewals</span><strong><?= (int) $stats['renewals_pending'] ?></strong></div>
</div>
<?php if ($yearRows): ?>
<div class="hub-card" style="margin-top:12px;">
    <p><strong>Membership years by calendar year</strong></p>
    <?php foreach ($yearRows as $year => $count): ?>
        <p><?= eca_admin_h((string) $year) ?> · <?= (int) $count ?></p>
    <?php endforeach; ?>
</div>
<?php endif; ?>

<div class="hub-card-head"><h2>Applications</h2></div>
<div class="hub-summary">
    <div><span>With ECA-APP reference</span><strong><?= (int) $stats['applications_total'] ?></strong></div>
    <div><span>Pending</span><strong><?= (int) $stats['applications_pending'] ?></strong></div>
    <div><span>Approved</span><strong><?= (int) $stats['applications_approved'] ?></strong></div>
    <div><span>Rejected</span><strong><?= (int) $stats['applications_rejected'] ?></strong></div>
</div>

<?php if (eca_can('companies.manage')): ?>
<div class="hub-card-head"><h2>Companies</h2></div>
<div class="hub-summary">
    <div><span>Directory companies</span><strong><?= (int) $stats['companies'] ?></strong></div>
    <div><span>Active companies</span><strong><?= (int) $stats['companies_active'] ?></strong></div>
    <div><span>Matched to membership #</span><strong><?= (int) ($stats['companies_matched_members'] ?? 0) ?></strong></div>
    <div><span>Owner rows</span><strong><?= (int) ($stats['owners_total'] ?? 0) ?></strong></div>
</div>
<p class="hub-note">Owners attach to <code>tbl_client</code>, not to directory <code>companies.id</code>. Empty owner data is shown as unavailable — never invented.</p>
<?php endif; ?>

<div class="hub-card-head"><h2>Finance</h2></div>
<div class="hub-summary">
    <div><span>Proofs pending</span><strong><?= (int) $stats['payments_pending'] ?></strong></div>
    <div><span>Proofs verified</span><strong><?= (int) $stats['payments_verified'] ?></strong></div>
    <div><span>Proofs rejected</span><strong><?= (int) $stats['payments_rejected'] ?></strong></div>
    <div><span>Outstanding balance rows</span><strong><?= (int) $stats['balances_due'] ?></strong></div>
</div>
<p class="hub-note">The payments table has no amount column. Outstanding balances use <code>eca_local.balances</code> when that table exists — locally it is often missing (DATA NOT AVAILABLE LOCALLY). Receipt logs similarly depend on portal <code>receipt_logs</code>.</p>

<div class="hub-card-head"><h2>CPD</h2></div>
<div class="hub-summary">
    <div><span>Applications</span><strong><?= (int) $stats['cpd_applications'] ?></strong></div>
    <div><span>Pending</span><strong><?= (int) $stats['cpd_pending'] ?></strong></div>
    <div><span>Courses</span><strong><?= (int) $stats['cpd_courses'] ?></strong></div>
    <div><span>Points ledger</span><strong><?= (int) $stats['cpd_points'] ?></strong></div>
</div>

<div class="hub-card-head"><h2>Wellness</h2></div>
<div class="hub-summary">
    <div><span>Upcoming events</span><strong><?= (int) $stats['wellness_events'] ?></strong></div>
    <div><span>Announcements</span><strong><?= (int) $stats['wellness_announcements'] ?></strong></div>
    <div><span>Resources</span><strong><?= (int) $stats['wellness_resources'] ?></strong></div>
    <div><span>Check-ins</span><strong><?= (int) $stats['wellness_checkins'] ?></strong></div>
    <div><span>Registrations</span><strong><?= (int) $stats['wellness_registrations'] ?></strong></div>
</div>

<?php if (eca_can('security.view')): ?>
<div class="hub-card-head"><h2>System</h2></div>
<div class="hub-summary">
    <div><span>Registered officers</span><strong><?= (int) $stats['officers_total'] ?></strong></div>
    <div><span>Active officers</span><strong><?= (int) $stats['officers_active'] ?></strong></div>
    <div><span>Super Admins</span><strong><?= (int) $stats['super_admins'] ?></strong></div>
</div>
<?php endif; ?>
<p class="hub-note">Historical trend fabrication is not included. Use Audit logs for event history.</p>
<?php eca_admin_hub_end(); ?>
