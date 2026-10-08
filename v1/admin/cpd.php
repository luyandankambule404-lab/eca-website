<?php
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/_hub.php';
require_once __DIR__ . '/../includes/portal-db.php';
require_once __DIR__ . '/../includes/admin-stats.php';
require_once __DIR__ . '/../includes/membership.php';
eca_admin_require('cpd.view');
header('Cache-Control: no-store');

$portal = eca_portal_pdo(false);
$stats = eca_admin_dashboard_stats(eca_admin_db(), $portal);
$apps = eca_safe_rows(
    $portal,
    'SELECT id, full_name, company_name, membership_number, status, created_at
     FROM cpd_applications
     ORDER BY id DESC
     LIMIT 20'
);
$courses = eca_safe_rows(
    $portal,
    'SELECT id, title, status, start_date, end_date, points
     FROM courses
     ORDER BY id DESC
     LIMIT 20'
);

eca_admin_hub_start('CPD', 'cpd');
?>
<div class="hub-hello">
    <h1 class="hub-hello-title">CPD</h1>
    <p>Live counts from the local CPD tables. Super Admin can open the other CPD portals from here without signing in again.</p>
</div>
<div class="hub-stats">
    <article class="hub-stat">
        <div class="hub-stat-label">Applications</div>
        <div class="hub-stat-value"><?= (int) $stats['cpd_applications'] ?></div>
        <div class="hub-stat-meta"><?= (int) $stats['cpd_pending'] ?> pending</div>
    </article>
    <article class="hub-stat">
        <div class="hub-stat-label">Courses</div>
        <div class="hub-stat-value"><?= (int) $stats['cpd_courses'] ?></div>
        <div class="hub-stat-meta"><?= (int) $stats['cpd_open'] ?> open</div>
    </article>
    <article class="hub-stat">
        <div class="hub-stat-label">Points ledger</div>
        <div class="hub-stat-value"><?= eca_admin_h(rtrim(rtrim(number_format((float) $stats['cpd_points'], 1, '.', ''), '0'), '.') ?: '0') ?></div>
        <div class="hub-stat-meta">Sum of recorded points</div>
    </article>
</div>
<div class="hub-card">
    <p>Open the Super Admin dashboard and other CPD portals from here when you are already signed in.</p>
    <p>
        <?php if (function_exists('eca_super_admin_can_open_portals') && eca_super_admin_can_open_portals()): ?>
            <a class="hub-doc-link" href="/cpd/admin/dashboard.php">Open Super Admin</a>
            <a class="hub-doc-link hub-doc-link-ghost" href="/cpd/officer/dashboard.php">Officer Hub</a>
            <a class="hub-doc-link hub-doc-link-ghost" href="/learner-portal.php">Learner Portal</a>
        <?php else: ?>
            <a class="hub-doc-link" href="/admin/login.php?next=<?= urlencode('/cpd/admin/dashboard.php') ?>">Super Admin sign-in</a>
        <?php endif; ?>
    </p>
</div>
<div class="hub-card eca-table-panel">
    <h2 class="hub-doc-heading">Recent applications</h2>
    <table class="hub-table">
        <thead><tr><th>Applicant</th><th>Membership</th><th>Status</th><th>Submitted</th></tr></thead>
        <tbody>
        <?php foreach ($apps as $row): ?>
            <tr>
                <td>
                    <span class="hub-doc-type"><?= eca_admin_h($row['full_name'] ?: ($row['company_name'] ?? 'Applicant')) ?></span>
                    <?php if (!empty($row['company_name']) && trim((string) $row['full_name']) !== ''): ?>
                        <span class="hub-doc-file"><?= eca_admin_h((string) $row['company_name']) ?></span>
                    <?php endif; ?>
                </td>
                <td><?= eca_admin_h($row['membership_number'] ?: '—') ?></td>
                <td><?= eca_admin_h($row['status'] ?: '—') ?></td>
                <td><?= eca_admin_h(eca_display_date((string) ($row['created_at'] ?? ''))) ?></td>
            </tr>
        <?php endforeach; ?>
        <?php if (!$apps): ?><tr data-hub-empty-row><td colspan="4">No CPD applications on file.</td></tr><?php endif; ?>
        </tbody>
    </table>
</div>
<div class="hub-card eca-table-panel">
    <h2 class="hub-doc-heading">Courses</h2>
    <table class="hub-table">
        <thead><tr><th>Course</th><th>Status</th><th>Dates</th><th>Points</th></tr></thead>
        <tbody>
        <?php foreach ($courses as $row): ?>
            <tr>
                <td><span class="hub-doc-type"><?= eca_admin_h($row['title'] ?: 'Course') ?></span></td>
                <td><?= eca_admin_h($row['status'] ?: '—') ?></td>
                <td><?php
                    $start = eca_display_date((string) ($row['start_date'] ?? ''));
                    $end = eca_display_date((string) ($row['end_date'] ?? ''));
                    echo eca_admin_h(($start === '—' && $end === '—') ? '—' : $start . ' – ' . $end);
                ?></td>
                <td><?= eca_admin_h(rtrim(rtrim(number_format((float) ($row['points'] ?? 0), 1, '.', ''), '0'), '.') ?: '0') ?></td>
            </tr>
        <?php endforeach; ?>
        <?php if (!$courses): ?><tr data-hub-empty-row><td colspan="4">No CPD courses on file.</td></tr><?php endif; ?>
        </tbody>
    </table>
</div>
<?php eca_admin_hub_end(); ?>
