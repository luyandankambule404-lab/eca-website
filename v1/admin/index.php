<?php
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/_hub.php';
require_once __DIR__ . '/../includes/portal-db.php';
require_once __DIR__ . '/../includes/admin-stats.php';
eca_admin_require('hub.access');

$local = eca_admin_db();
$portal = eca_portal_pdo(false);
$stats = eca_admin_dashboard_stats($local, $portal);
$admin = eca_admin_user();
$role = eca_normalize_role((string) ($admin['role'] ?? 'admin'));
$helloName = trim((string) ($admin['name'] ?? ''));
if ($helloName === '') {
    $helloName = (string) ($admin['email'] ?? 'Administrator');
}
if (str_contains(strtolower($role), 'super') && function_exists('eca_hub_super_admin_display_name')) {
    $helloName = eca_hub_super_admin_display_name($helloName);
}
$typeCounts = $local ? eca_directory_type_counts($local, 'members') : [];
$canMembers = eca_can('members.manage', $role);
$canApplications = eca_can('applications.manage', $role);
$canCompanies = eca_can('companies.manage', $role);
$canCertificates = eca_can('certificates.manage', $role);
$canPayments = eca_can('payments.manage', $role);
$canTickets = eca_can('tickets.manage', $role);
$canWellnessManage = eca_can('wellness.manage', $role);
$canWellnessView = eca_can('wellness.view', $role) || $canWellnessManage;
// Admin CMS routes require wellness.manage — do not treat wellness.view as enough for /admin/wellness/*
$canWellness = $canWellnessManage;
$canCpd = eca_can('cpd.view', $role);
$canEducation = eca_can('education.manage', $role);
$canAudit = eca_can('audit.view', $role);
$canUsers = eca_normalize_role((string) $role) === 'super_admin' && eca_can('users.view', $role);
$canRoles = eca_can('roles.view', $role);
$canReports = eca_can('reports.view', $role);
$canSecurity = eca_can('security.view', $role);
$canSettings = eca_can('settings.view', $role) || eca_can('hub.settings', $role);
$canOpenPortals = (function_exists('eca_super_admin_can_open_portals') && eca_super_admin_can_open_portals())
    || (function_exists('eca_hub_can_open_portals') && eca_hub_can_open_portals($admin));
if ($canOpenPortals) {
    // Portal tiles only — do not elevate wellness.view into /admin/wellness/* CMS access.
    $canCpd = true;
}

$chartPayload = eca_admin_chart_payload($stats, [
    'members' => $canMembers,
    'companies' => $canCompanies,
    'applications' => $canApplications,
    'payments' => $canPayments,
    'certificates' => $canCertificates,
    'cpd' => $canCpd,
    'wellness' => $canWellness || $canWellnessView,
    'tickets' => $canTickets,
]);

function eca_dash_chart_card(string $key, array $charts): void
{
    if (!isset($charts[$key]) || !is_array($charts[$key])) {
        return;
    }
    $c = $charts[$key];
    $title = (string) ($c['title'] ?? 'Chart');
    $empty = (string) ($c['empty'] ?? 'No data available locally.');
    $note = (string) ($c['note'] ?? '');
    $has = !empty($c['has_data']);
    $id = 'exec-chart-' . preg_replace('/[^a-z0-9_-]/i', '', $key);
    echo '<article class="exec-chart-card" data-chart-key="' . eca_admin_h($key) . '">';
    echo '<h3>' . eca_admin_h($title) . '</h3>';
    if ($note !== '') {
        echo '<p class="exec-chart-note">' . eca_admin_h($note) . '</p>';
    }
    if (!$has) {
        echo '<p class="exec-chart-empty">' . eca_admin_h($empty) . '</p>';
    } else {
        echo '<div class="exec-chart-wrap"><canvas id="' . eca_admin_h($id) . '" role="img" aria-label="' . eca_admin_h($title) . '"></canvas></div>';
        if (!empty($c['labels']) && !empty($c['values']) && is_array($c['labels']) && is_array($c['values'])) {
            echo '<ul class="exec-chart-legend">';
            foreach ($c['labels'] as $i => $label) {
                $val = (int) ($c['values'][$i] ?? 0);
                echo '<li><span>' . eca_admin_h((string) $label) . ': <strong>' . $val . '</strong></span></li>';
            }
            echo '</ul>';
        }
    }
    echo '</article>';
}

function eca_dash_portal(string $title, string $meta, string $href, bool $can, string $countLabel = '', string $tone = '', string $icon = 'fa-arrow-right'): void
{
    if (!$can) {
        return;
    }
    echo '<a class="hub-tile hub-portal-tile' . ($tone !== '' ? ' ' . eca_admin_h($tone) : '') . '" href="' . eca_admin_h($href) . '">';
    echo '<span class="cmd-portal-icon" aria-hidden="true"><i class="fa-solid ' . eca_admin_h($icon) . '"></i></span>';
    echo '<div class="cmd-portal-body">';
    echo '<h3>' . eca_admin_h($title) . '</h3>';
    if ($countLabel !== '') {
        echo '<div class="hub-portal-count">' . eca_admin_h($countLabel) . '</div>';
    } else {
        echo '<div class="hub-portal-count is-empty" aria-hidden="true">&nbsp;</div>';
    }
    echo '<p class="hub-portal-meta">' . eca_admin_h($meta) . '</p>';
    echo '<span class="hub-portal-go">Open <i class="fa-solid fa-arrow-right" aria-hidden="true"></i></span>';
    echo '</div>';
    echo '</a>';
}

function eca_dash_stat(string $label, int $value, string $meta, ?string $href, bool $can, string $linkLabel = 'View', string $tone = ''): void
{
    $tag = ($can && $href) ? 'a' : 'article';
    $hrefAttr = ($can && $href) ? ' href="' . eca_admin_h($href) . '"' : '';
    $class = 'hub-stat' . ($tone !== '' ? ' ' . $tone : '');
    echo '<' . $tag . ' class="' . eca_admin_h($class) . '"' . $hrefAttr . '>';
    echo '<div class="hub-stat-label">' . eca_admin_h($label) . '</div>';
    echo '<div class="hub-stat-value">' . $value . '</div>';
    echo '<div class="hub-stat-meta">' . eca_admin_h($meta) . '</div>';
    if ($can && $href) {
        echo '<div class="hub-stat-link">' . eca_admin_h($linkLabel) . '</div>';
    }
    echo '</' . $tag . '>';
}

function eca_dash_meter(string $label, int $value, int $max, ?string $href = null, bool $can = false): void
{
    $pct = $max > 0 ? (int) round(($value / $max) * 100) : 0;
    echo '<div class="hub-meter">';
    echo '<div class="hub-meter-top"><span>' . eca_admin_h($label) . '</span><strong>' . $value . '</strong></div>';
    echo '<div class="hub-progress" aria-hidden="true"><div class="hub-progress-bar" style="width:' . $pct . '%"></div></div>';
    if ($can && $href) {
        echo '<a class="hub-meter-link" href="' . eca_admin_h($href) . '">Open</a>';
    }
    echo '</div>';
}

function eca_dash_trend(string $title, array $months): void
{
    $max = 0;
    foreach ($months as $count) {
        $max = max($max, (int) $count);
    }
    if ($max <= 0) {
        return;
    }
    echo '<div class="hub-trend-block"><h3>' . eca_admin_h($title) . '</h3>';
    foreach ($months as $month => $count) {
        $count = (int) $count;
        $pct = (int) round(($count / $max) * 100);
        $label = DateTimeImmutable::createFromFormat('Y-m', (string) $month);
        $pretty = $label ? $label->format('M Y') : (string) $month;
        echo '<div class="hub-trend-row">';
        echo '<span>' . eca_admin_h($pretty) . '</span>';
        echo '<div class="hub-progress" aria-hidden="true"><div class="hub-progress-bar" style="width:' . $pct . '%"></div></div>';
        echo '<strong>' . $count . '</strong>';
        echo '</div>';
    }
    echo '</div>';
}

$alerts = [];
if ($stats['applications_pending'] > 0 && $canApplications) {
    $alerts[] = [
        $stats['applications_pending'] . ' membership application' . ($stats['applications_pending'] === 1 ? '' : 's') . ' waiting for review',
        '/admin/applications.php',
    ];
}
if ($stats['members_pending'] > 0 && $canMembers) {
    $alerts[] = [
        $stats['members_pending'] . ' member' . ($stats['members_pending'] === 1 ? '' : 's') . ' with pending standing',
        '/admin/members.php?standing=Pending',
    ];
}
if ($stats['members_near_expiry'] > 0 && $canMembers) {
    $alerts[] = [
        $stats['members_near_expiry'] . ' membership year' . ($stats['members_near_expiry'] === 1 ? '' : 's') . ' expiring within 90 days',
        '/admin/members.php?year_state=near',
    ];
}
if ($stats['certificates_expiring'] > 0 && $canCertificates) {
    $alerts[] = [
        $stats['certificates_expiring'] . ' certificate' . ($stats['certificates_expiring'] === 1 ? '' : 's') . ' approaching expiry',
        '/admin/certificates.php',
    ];
}
if ($stats['payments_pending'] > 0 && $canPayments) {
    $alerts[] = [
        $stats['payments_pending'] . ' payment' . ($stats['payments_pending'] === 1 ? '' : 's') . ' waiting for verification',
        '/admin/payments.php?status=pending',
    ];
}
if ($stats['cpd_pending'] > 0 && $canCpd) {
    $alerts[] = [
        $stats['cpd_pending'] . ' CPD application' . ($stats['cpd_pending'] === 1 ? '' : 's') . ' pending in the CPD portal',
        '/admin/cpd.php',
    ];
}
if ($stats['tickets_open'] > 0 && $canTickets) {
    $alerts[] = [
        $stats['tickets_open'] . ' open support ticket' . ($stats['tickets_open'] === 1 ? '' : 's'),
        '/admin/tickets.php',
    ];
}
if ((int) $stats['wellness_events'] > 0 && $canWellness) {
    $alerts[] = [
        $stats['wellness_events'] . ' upcoming wellness event' . ($stats['wellness_events'] === 1 ? '' : 's'),
        '/admin/wellness/',
    ];
}

$quick = [
    ['Review applications', 'Open the membership application queue.', '/admin/applications.php', $canApplications],
    ['Manage members', 'Search, suspend and reactivate portal members.', '/admin/members.php', $canMembers],
    ['Email Centre', 'Send member notices with CC, attachments and signature.', '/admin/email-centre.php', $canMembers],
    ['Manage contractors', 'Open the local contractor directory.', '/admin/companies.php', $canCompanies],
    ['View payments', 'Verify or reject proof of payment.', '/admin/payments.php', $canPayments, 'hub-tile-sand'],
    ['Manage certificates', 'Issue, review or revoke membership certificates.', '/admin/certificates.php', $canCertificates],
    ['CPD administration', 'Review CPD applications and courses in the Admin Hub.', '/admin/cpd.php', $canCpd],
    ['Open Wellness', 'Manage wellness events, resources and announcements.', '/admin/wellness/', $canWellness],
    ['Open Wellness Hub', 'View the public Wellness Hub without another login.', '/wellness/', $canOpenPortals],
    ['Open Member Wellness', 'View member wellness pages without another login.', '/client/wellness/', $canOpenPortals],
    ['Open Member Hub', 'View the member portal without another login.', '/client/dashboard.php', $canOpenPortals],
    ['Open Learner Portal', 'View the learner environment without another login.', '/learner-portal.php', $canOpenPortals],
    ['Open Super Admin', 'View the Super Admin dashboard without another login.', '/cpd/admin/dashboard.php', $canOpenPortals],
    ['Open Officer Hub', 'View the officer portal without another login.', '/cpd/officer/dashboard.php', $canOpenPortals],
    ['Education', 'Manage public education content and learner links.', '/admin/education.php', $canEducation],
    ['Audit logs', 'Read-only history of hub actions.', '/admin/audit.php', $canAudit],
    ['Manage users', 'Hub user accounts and status.', '/admin/users.php', $canUsers],
    ['Roles & permissions', 'Assign permissions to existing roles.', '/admin/roles.php', $canRoles],
    ['Reports', 'Local membership, finance and system summaries.', '/admin/reports.php', $canReports],
    ['Membership Intelligence', 'Filtered membership report, year intelligence and CSV export.', '/admin/membership-report.php', $canReports],
    ['2025 / 2026 Report', 'Membership year report for 2025 and 2026 from local data.', '/admin/membership-report.php?period=2025-2026', $canReports],
    ['Security Centre', 'Officer accounts and security audit activity.', '/admin/security.php', $canSecurity],
    ['System settings', 'Local organization notes. Secrets stay in .env.', '/admin/settings.php', $canSettings],
];

$standingMax = max(
    1,
    (int) $stats['members_active'],
    (int) $stats['members_pending'],
    (int) $stats['members_expired'],
    (int) $stats['members_suspended'],
    (int) $stats['members_other']
);
$appMax = max(
    1,
    (int) $stats['applications_submitted'],
    (int) $stats['applications_review'],
    (int) $stats['applications_returned'],
    (int) $stats['applications_approved'],
    (int) $stats['applications_rejected']
);
$roleLabel = $role !== '' ? ucwords(str_replace('_', ' ', $role)) : 'Administrator';
$isSuper = str_contains(strtolower($role), 'super');
$todayLabel = (new DateTimeImmutable('now'))->format('l, j F Y');
$attentionCount = count($alerts);

eca_admin_hub_start('Dashboard', 'dashboard');
?>
<div class="cmd-stage">
<section class="cmd-hero">
    <div class="cmd-hero-copy">
        <p class="cmd-kicker"><span class="cmd-live" aria-hidden="true"></span><?= $isSuper ? 'Super Admin command centre' : 'Officer command centre' ?></p>
        <h1><?= eca_admin_h(eca_hub_hello($helloName)) ?></h1>
        <p class="cmd-lede"><?php if (eca_is_live_readonly()): ?>Counts read from the production database. Changes are disabled. Staff accounts stay on this computer.<?php else: ?>Live counts from the local ECA membership, CPD, wellness and hub databases.<?php endif; ?></p>
        <?php if (!empty($stats['live_gaps'])): ?>
        <p class="cmd-lede">These production tables were not found, so those figures stay at zero: <?= eca_admin_h(implode(', ', $stats['live_gaps'])) ?>.</p>
        <?php endif; ?>
    </div>
    <aside class="cmd-hero-panel">
        <p class="cmd-panel-label">This session</p>
        <div class="cmd-hero-meta">
            <span class="cmd-chip"><i class="fa-solid fa-user-shield" aria-hidden="true"></i><?= eca_admin_h($roleLabel) ?></span>
            <span class="cmd-chip"><i class="fa-solid fa-calendar-day" aria-hidden="true"></i><?= eca_admin_h($todayLabel) ?></span>
            <?php if ($attentionCount > 0): ?>
            <span class="cmd-chip cmd-chip-alert"><i class="fa-solid fa-bell" aria-hidden="true"></i><?= $attentionCount ?> need<?= $attentionCount === 1 ? 's' : '' ?> attention</span>
            <?php else: ?>
            <span class="cmd-chip cmd-chip-ok"><i class="fa-solid fa-circle-check" aria-hidden="true"></i>Clear queue</span>
            <?php endif; ?>
        </div>
    </aside>
</section>

<?php if ($canOpenPortals): ?>
<section class="cmd-section">
<div class="hub-card-head cmd-section-head">
    <div>
        <p class="cmd-eyebrow">Gateways</p>
        <h2>Portals</h2>
    </div>
    <a href="/admin/wellness/">Open Wellness</a>
</div>
<div class="hub-tiles hub-portals">
    <?php
    eca_dash_portal(
        'Main portal',
        'Public ECA website. Use Back to dashboard on the site to return here.',
        '/index.php',
        true,
        '',
        '',
        'fa-globe'
    );
    eca_dash_portal(
        'Wellness',
        (int) $stats['wellness_announcements'] . ' announcements · ' . (int) $stats['wellness_resources'] . ' resources · ' . (int) $stats['wellness_checkins'] . ' check-ins',
        '/admin/wellness/',
        true,
        (string) (int) $stats['wellness_events'] . ' events',
        'hub-tile-lilac',
        'fa-heart-pulse'
    );
    eca_dash_portal(
        'Wellness Hub',
        'Public articles, videos, toolbox talks and support pages.',
        '/wellness/',
        true,
        (string) (int) $stats['wellness_hub_published'] . ' published',
        'hub-tile-mint',
        'fa-spa'
    );
    eca_dash_portal(
        'Member Wellness',
        'Signed-in member events, resources and announcements.',
        '/client/wellness/',
        true,
        (string) (int) $stats['wellness_registrations'] . ' registrations',
        '',
        'fa-heart'
    );
    eca_dash_portal(
        'Member Hub',
        'Membership, certificates, payments and documents.',
        '/client/dashboard.php',
        true,
        (string) (int) $stats['members_total'] . ' members',
        'hub-tile-sand',
        'fa-id-card'
    );
    eca_dash_portal(
        'Learner Portal',
        'Courses, applications and CPD records.',
        '/learner-portal.php',
        true,
        (string) (int) $stats['cpd_courses'] . ' courses',
        '',
        'fa-laptop'
    );
    eca_dash_portal(
        'Super Admin',
        (int) $stats['cpd_pending'] . ' pending applications · ' . (int) $stats['cpd_points'] . ' points',
        '/cpd/admin/dashboard.php',
        true,
        (string) (int) $stats['cpd_applications'] . ' applications',
        'hub-tile-lilac',
        'fa-user-shield'
    );
    eca_dash_portal(
        'Officer Hub',
        'Review applications, attendance and participants.',
        '/cpd/officer/dashboard.php',
        true,
        (string) (int) $stats['cpd_pending'] . ' pending',
        'hub-tile-sand',
        'fa-user-tie'
    );
    eca_dash_portal(
        'Education',
        'Public education content and learner-portal links.',
        '/admin/education.php',
        $canEducation,
        '',
        'hub-tile-mint',
        'fa-graduation-cap'
    );
    ?>
</div>
</section>
<?php endif; ?>

<section class="cmd-section exec-kpi-strip">
<div class="hub-card-head cmd-section-head">
    <div>
        <p class="cmd-eyebrow">Snapshot</p>
        <h2>Executive KPIs</h2>
    </div>
</div>
<div class="hub-stats">
    <?php
    eca_dash_stat('Members', (int) $stats['members_total'], (int) $stats['members_active'] . ' active standing', '/admin/members.php', $canMembers, 'Open', 'cmd-stat-navy');
    eca_dash_stat('Companies', (int) $stats['companies'], (int) $stats['companies_active'] . ' active', '/admin/companies.php', $canCompanies, 'Open');
    eca_dash_stat('Applications', (int) $stats['applications_total'], (int) $stats['applications_pending'] . ' pending', '/admin/applications.php', $canApplications, 'Open', (int) $stats['applications_pending'] > 0 ? 'cmd-stat-alert' : '');
    eca_dash_stat('Payments', (int) ($stats['payments_total'] ?? 0), (int) $stats['payments_pending'] . ' pending proofs', '/admin/payments.php', $canPayments, 'Open', (int) $stats['payments_pending'] > 0 ? 'cmd-stat-alert' : '');
    eca_dash_stat('Certificates', (int) $stats['certificates_issued'], (int) $stats['certificates_active'] . ' active', '/admin/certificates.php', $canCertificates, 'Open');
    eca_dash_stat('CPD', (int) $stats['cpd_applications'], (int) $stats['cpd_pending'] . ' pending · ' . (int) $stats['cpd_points'] . ' pts', '/admin/cpd.php', $canCpd, 'Open');
    eca_dash_stat('Wellness', (int) $stats['wellness_events'], (int) $stats['wellness_registrations'] . ' registrations', '/admin/wellness/', $canWellness, 'Open');
    eca_dash_stat('Support', (int) $stats['tickets_open'], 'Open tickets / messages', '/admin/tickets.php', $canTickets, 'Open', (int) $stats['tickets_open'] > 0 ? 'cmd-stat-alert' : '');
    ?>
</div>
</section>

<?php if ($chartPayload): ?>
<section class="cmd-section" id="exec-analytics">
<div class="hub-card-head cmd-section-head">
    <div>
        <p class="cmd-eyebrow">Analytics</p>
        <h2>Executive analytics</h2>
    </div>
</div>
<div class="exec-chart-grid">
    <?php eca_dash_chart_card('membership_trend', $chartPayload); ?>
    <?php eca_dash_chart_card('applications', $chartPayload); ?>
</div>
<div class="exec-chart-grid">
    <?php eca_dash_chart_card('payments', $chartPayload); ?>
    <?php eca_dash_chart_card('certificates', $chartPayload); ?>
</div>
<div class="exec-chart-grid">
    <?php eca_dash_chart_card('cpd', $chartPayload); ?>
    <?php eca_dash_chart_card('companies', $chartPayload); ?>
</div>
<div class="exec-chart-grid">
    <?php eca_dash_chart_card('wellness', $chartPayload); ?>
    <?php eca_dash_chart_card('support', $chartPayload); ?>
</div>
<noscript>
    <p class="hub-empty">Enable JavaScript to view interactive charts. KPI totals above remain accurate without charts.</p>
</noscript>
</section>
<?php endif; ?>

<section class="cmd-section">
<div class="hub-card-head cmd-section-head">
    <div>
        <p class="cmd-eyebrow">Membership</p>
        <h2>Membership overview</h2>
    </div>
    <div style="display:flex;gap:10px;flex-wrap:wrap;">
        <?php if ($canMembers): ?><a href="/admin/members.php">View members</a><?php endif; ?>
        <?php if ($canReports): ?><a href="/admin/membership-report.php">Membership Intelligence</a><?php endif; ?>
    </div>
</div>
<p class="hub-stat-meta" style="margin:0 0 10px;">Numbered members (<?= (int) $stats['members_total'] ?>) are a subset of all client rows (<?= (int) $stats['members_all_clients'] ?>). Joining / Renewal come from <code>Status</code>; Active / Pending / Suspended from <code>active</code>.</p>
<?php if (empty($stats['sources_available']['portal'])): ?>
<p class="hub-empty">DATA NOT AVAILABLE LOCALLY — portal database not connected.</p>
<?php else: ?>
<div class="hub-stats">
    <?php
    eca_dash_stat('All clients', (int) $stats['members_all_clients'], 'Every tbl_client row', '/admin/membership-report.php', $canReports, 'Intelligence', 'cmd-stat-navy');
    eca_dash_stat('Numbered members', (int) $stats['members_total'], (int) $stats['members_without_number'] . ' without membership #', '/admin/members.php', $canMembers, 'View');
    eca_dash_stat('Active', (int) $stats['members_active'], 'Standing = Active', '/admin/members.php?standing=Active', $canMembers);
    eca_dash_stat('Pending', (int) $stats['members_pending'], 'Standing = Pending', '/admin/members.php?standing=Pending', $canMembers, 'View', (int) $stats['members_pending'] > 0 ? 'cmd-stat-alert' : '');
    eca_dash_stat('Joining', (int) $stats['members_joining'], 'Type Status = Joining', '/admin/members.php?type=Joining', $canMembers);
    eca_dash_stat('Renewal type', (int) $stats['members_renewal_type'], 'Type Status = Renewal', '/admin/members.php?type=Renewal', $canMembers);
    eca_dash_stat('Suspended', (int) $stats['members_suspended'], 'Standing suspended', '/admin/members.php?standing=Suspended', $canMembers);
    eca_dash_stat('Declined', (int) $stats['members_declined'], 'Declined standing/type', '/admin/membership-report.php?standing=Declined', $canReports);
    eca_dash_stat('Expiring (90 days)', (int) $stats['members_near_expiry'], 'Active years near expiry', '/admin/members.php?year_state=near', $canMembers, 'View', (int) $stats['members_near_expiry'] > 0 ? 'cmd-stat-alert' : '');
    ?>
</div>
<?php if ($canMembers && !empty($stats['recent_members'])): ?>
<div class="hub-card eca-table-panel" style="margin-top:10px;">
    <h3 style="margin:0 0 8px;font-size:0.95rem;">Recent members</h3>
    <table class="hub-table">
        <thead><tr><th>Membership</th><th>Name</th><th>Standing</th><th>Type</th><th></th></tr></thead>
        <tbody>
        <?php foreach ($stats['recent_members'] as $row): ?>
            <tr>
                <td><?= eca_admin_h((string) (($row['MembershipNumber'] ?? '') !== '' ? $row['MembershipNumber'] : '—')) ?></td>
                <td><?= eca_admin_h((string) ($row['TradingName'] ?? $row['CompanyRegistrationName'] ?? '')) ?></td>
                <td><?= eca_admin_h((string) ($row['active'] ?? '')) ?></td>
                <td><?= eca_admin_h((string) ($row['Status'] ?? '')) ?></td>
                <td><a href="/admin/member-detail.php?id=<?= (int) ($row['client_id'] ?? 0) ?>">Open</a></td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
    <?php eca_render_named_request_pager($stats['recent_members_pager'] ?? []); ?>
</div>
<?php elseif ($canMembers): ?>
<p class="hub-empty" style="margin-top:8px;">No members found.</p>
<?php endif; ?>
<?php endif; ?>
</section>

<section class="cmd-section">
<div class="hub-card-head cmd-section-head">
    <div>
        <p class="cmd-eyebrow">Membership year</p>
        <h2>Year &amp; renewals</h2>
    </div>
    <?php if ($canReports): ?><a href="/admin/membership-report.php?period=2025-2026">2025 / 2026 report</a><?php endif; ?>
</div>
<div class="hub-stats">
    <?php
    eca_dash_stat('2025 / 2026 clients', (int) $stats['membership_period_2025_2026'], 'Distinct clients with year 2025 or 2026', '/admin/membership-report.php?period=2025-2026', $canReports, 'Open report', 'cmd-stat-navy');
    eca_dash_stat('Current year rows', (int) $stats['membership_year_current'], (int) $stats['membership_year_rows'] . ' year rows total', '/admin/membership-report.php?year_state=current', $canReports);
    eca_dash_stat('Expiring (90 days)', (int) $stats['members_near_expiry'], 'Active years near expiry', '/admin/members.php?year_state=near', $canMembers, 'View', (int) $stats['members_near_expiry'] > 0 ? 'cmd-stat-alert' : '');
    eca_dash_stat('Expired years', (int) $stats['members_expired'], 'Expired membership_years', '/admin/members.php?year_state=expired', $canMembers, 'View', (int) $stats['members_expired'] > 0 ? 'cmd-stat-alert' : '');
    eca_dash_stat('Pending renewals', (int) $stats['renewals_pending'], 'membership_years Renewal + Pending', '/admin/members.php?year_state=renewal_pending', $canMembers, 'View', (int) $stats['renewals_pending'] > 0 ? 'cmd-stat-alert' : '');
    eca_dash_stat('Regions', (int) $stats['members_regions'], 'Distinct Region values', '/admin/membership-report.php', $canReports);
    ?>
</div>
<p class="mi-actions" style="display:flex;flex-wrap:wrap;gap:8px;margin:8px 0 0;">
    <?php if ($canMembers): ?><a class="hub-btn" href="/admin/members.php">View members</a><?php endif; ?>
    <?php if ($canReports): ?><a class="hub-btn" href="/admin/membership-report.php">Membership reports</a><?php endif; ?>
    <?php if ($canApplications): ?><a class="hub-btn" href="/admin/applications.php">Pending applications</a><?php endif; ?>
    <?php if ($canMembers): ?><a class="hub-btn" href="/admin/members.php?year_state=renewal_pending">Renewals</a><?php endif; ?>
</p>
</section>

<section class="cmd-section">
<div class="hub-card-head cmd-section-head">
    <div>
        <p class="cmd-eyebrow">Pipeline</p>
        <h2>Applications &amp; contractors</h2>
    </div>
</div>
<div class="hub-stats">
    <?php
    eca_dash_stat('Pending applications', (int) $stats['applications_pending'], 'Submitted, in review or returned', '/admin/applications.php', $canApplications, 'View', (int) $stats['applications_pending'] > 0 ? 'cmd-stat-alert' : '');
    eca_dash_stat('Approved applications', (int) $stats['applications_approved'], 'With an ECA-APP reference', '/admin/applications.php?status=APPROVED', $canApplications);
    eca_dash_stat('Rejected / returned', (int) $stats['applications_rejected'] + (int) $stats['applications_returned'], 'Rejected or more information required', '/admin/applications.php', $canApplications);
    eca_dash_stat('Contractors', (int) $stats['companies'], $stats['companies_active'] . ' active in the directory', '/admin/companies.php', $canCompanies, 'View all');
    ?>
</div>
</section>

<?php if ($canCompanies): ?>
<section class="cmd-section">
<div class="hub-card-head cmd-section-head">
    <div>
        <p class="cmd-eyebrow">Companies</p>
        <h2>Company overview</h2>
    </div>
    <a href="/admin/companies.php">View companies</a>
</div>
<div class="hub-stats">
    <?php
    if (empty($stats['sources_available']['companies']) && (int) $stats['companies'] === 0) {
        echo '<p class="hub-empty">DATA NOT AVAILABLE LOCALLY — companies table not available.</p>';
    } else {
    eca_dash_stat('Total companies', (int) $stats['companies'], 'eca_local.companies COUNT(*)', '/admin/companies.php', $canCompanies, 'Open', 'cmd-stat-navy');
    eca_dash_stat('Active companies', (int) $stats['companies_active'], 'status = active (P1 CI)', '/admin/companies.php?status=active', $canCompanies);
    eca_dash_stat('Matched to membership', (int) ($stats['companies_matched_members'] ?? 0), 'eca_ci_company_intelligence matched_to_member', '/admin/companies.php', $canCompanies);
    eca_dash_stat('Without membership match', (int) ($stats['companies_unmatched_members'] ?? 0), 'total − matched (P1 soft match)', '/admin/companies.php', $canCompanies);
    eca_dash_stat('Owner rows', (int) ($stats['owners_total'] ?? 0), 'owners COUNT(*) via CI', '/admin/owners-report.php', $canCompanies, 'Owners report');
    }
    ?>
</div>
<?php if (!empty($stats['recent_companies'])): ?>
<div class="hub-card eca-table-panel" style="margin-top:10px;">
    <h3 style="margin:0 0 8px;font-size:0.95rem;">Recent companies</h3>
    <table class="hub-table">
        <thead><tr><th>Company</th><th>Registration</th><th>Status</th><th></th></tr></thead>
        <tbody>
        <?php foreach ($stats['recent_companies'] as $row): ?>
            <tr>
                <td><?= eca_admin_h((string) ($row['name'] ?? '')) ?></td>
                <td><?= eca_admin_h((string) (($row['registration_number'] ?? '') !== '' ? $row['registration_number'] : '—')) ?></td>
                <td><?= eca_admin_h((string) ($row['status'] ?? '')) ?></td>
                <td><a href="/admin/company-detail.php?id=<?= (int) ($row['id'] ?? 0) ?>">Open</a></td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
    <?php eca_render_named_request_pager($stats['recent_companies_pager'] ?? []); ?>
</div>
<?php elseif (!empty($stats['sources_available']['companies'])): ?>
<p class="hub-empty" style="margin-top:8px;">No companies found.</p>
<?php endif; ?>
<p style="display:flex;flex-wrap:wrap;gap:8px;margin:8px 0 0;">
    <a class="hub-btn" href="/admin/companies.php">View Companies</a>
    <a class="hub-btn" href="/admin/owners-report.php">Owners Report</a>
    <?php if ($canReports): ?>
        <a class="hub-btn" href="/admin/reports.php">Company Reports</a>
    <?php else: ?>
        <a class="hub-btn" href="/admin/companies.php">Company Reports</a>
    <?php endif; ?>
</p>
</section>
<?php endif; ?>

<?php if ($canApplications): ?>
<section class="cmd-section">
<div class="hub-card-head cmd-section-head">
    <div>
        <p class="cmd-eyebrow">Applications</p>
        <h2>Application pipeline</h2>
    </div>
    <a href="/admin/applications.php">View applications</a>
</div>
<?php if (empty($stats['sources_available']['portal'])): ?>
<p class="hub-empty">DATA NOT AVAILABLE LOCALLY — portal database not connected.</p>
<?php else: ?>
<div class="hub-stats">
    <?php
    eca_dash_stat('Total applications', (int) $stats['applications_total'], 'Rows with application_reference', '/admin/applications.php', $canApplications, 'Open', 'cmd-stat-navy');
    eca_dash_stat('Pending', (int) $stats['applications_pending'], 'Submitted / review / returned', '/admin/applications.php', $canApplications, 'View', (int) $stats['applications_pending'] > 0 ? 'cmd-stat-alert' : '');
    eca_dash_stat('Approved', (int) $stats['applications_approved'], 'application_status Approved', '/admin/applications.php?status=APPROVED', $canApplications);
    eca_dash_stat('Rejected', (int) $stats['applications_rejected'], 'application_status Rejected', '/admin/applications.php?status=REJECTED', $canApplications);
    ?>
</div>
<?php if (!empty($stats['recent_applications'])): ?>
<div class="hub-card eca-table-panel" style="margin-top:10px;">
    <h3 style="margin:0 0 8px;font-size:0.95rem;">Recent applications</h3>
    <table class="hub-table">
        <thead><tr><th>Reference</th><th>Applicant</th><th>Type</th><th>Status</th><th>Date</th><th></th></tr></thead>
        <tbody>
        <?php foreach ($stats['recent_applications'] as $row): ?>
            <tr>
                <td><?= eca_admin_h((string) ($row['application_reference'] ?? '—')) ?></td>
                <td><?= eca_admin_h((string) ($row['TradingName'] ?? $row['CompanyRegistrationName'] ?? '')) ?></td>
                <td><?= eca_admin_h((string) (($row['Status'] ?? '') !== '' ? $row['Status'] : '—')) ?></td>
                <td><?= eca_admin_h((string) ($row['application_status'] ?? '')) ?></td>
                <td><?= eca_admin_h((string) ($row['created_at'] ?? '—')) ?></td>
                <td><a href="/admin/application-detail.php?id=<?= (int) ($row['client_id'] ?? 0) ?>">Open</a></td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
    <?php eca_render_named_request_pager($stats['recent_applications_pager'] ?? []); ?>
</div>
<?php else: ?>
<p class="hub-empty" style="margin-top:8px;">No applications found.</p>
<?php endif; ?>
<?php endif; ?>
</section>
<?php endif; ?>

<?php if ($canPayments): ?>
<section class="cmd-section">
<div class="hub-card-head cmd-section-head">
    <div>
        <p class="cmd-eyebrow">Finance</p>
        <h2>Payments</h2>
    </div>
    <a href="/admin/payments.php">View payments</a>
</div>
<?php if (empty($stats['sources_available']['payments'])): ?>
<p class="hub-empty">DATA NOT AVAILABLE LOCALLY — payments table not found.</p>
<?php else: ?>
<div class="hub-stats">
    <?php
    eca_dash_stat('Total proofs', (int) ($stats['payments_total'] ?? 0), 'payments COUNT by status', '/admin/payments.php', $canPayments, 'Open', 'cmd-stat-navy');
    eca_dash_stat('Pending proof', (int) $stats['payments_pending'], 'status = pending', '/admin/payments.php?status=pending', $canPayments, 'View', (int) $stats['payments_pending'] > 0 ? 'cmd-stat-alert' : '');
    eca_dash_stat('Verified', (int) $stats['payments_verified'], 'approved / verified', '/admin/payments.php?status=verified', $canPayments);
    eca_dash_stat('Rejected', (int) $stats['payments_rejected'], 'status = rejected', '/admin/payments.php?status=rejected', $canPayments);
    eca_dash_stat('Balances due', (int) $stats['balances_due'], 'eca_local.balances', '/admin/balances.php', $canPayments);
    ?>
</div>
<?php if (!empty($stats['recent_payments'])): ?>
<div class="hub-card eca-table-panel" style="margin-top:10px;">
    <h3 style="margin:0 0 8px;font-size:0.95rem;">Recent payment proofs</h3>
    <table class="hub-table">
        <thead><tr><th>ID</th><th>Year</th><th>Status</th><th>Created</th><th></th></tr></thead>
        <tbody>
        <?php foreach ($stats['recent_payments'] as $row): ?>
            <tr>
                <td><?= (int) ($row['id'] ?? 0) ?></td>
                <td><?= eca_admin_h((string) ($row['payment_year'] ?? '—')) ?></td>
                <td><?= eca_admin_h(eca_payment_label((string) ($row['status'] ?? ''))) ?></td>
                <td><?= eca_admin_h((string) ($row['created_at'] ?? '')) ?></td>
                <td><a href="/admin/payment-detail.php?id=<?= (int) ($row['id'] ?? 0) ?>">Open</a></td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
    <?php eca_render_named_request_pager($stats['recent_payments_pager'] ?? []); ?>
</div>
<?php else: ?>
<p class="hub-empty" style="margin-top:8px;">No payments found.</p>
<?php endif; ?>
<?php endif; ?>
</section>
<?php endif; ?>

<?php if ($canCertificates): ?>
<section class="cmd-section">
<div class="hub-card-head cmd-section-head">
    <div>
        <p class="cmd-eyebrow">Certificates</p>
        <h2>Membership certificates</h2>
    </div>
    <a href="/admin/certificates.php">View certificates</a>
</div>
<?php if (empty($stats['sources_available']['certificates'])): ?>
<p class="hub-empty">DATA NOT AVAILABLE LOCALLY — membership_certificates not found.</p>
<?php else: ?>
<div class="hub-stats">
    <?php
    eca_dash_stat('Total certificates', (int) $stats['certificates_issued'], 'membership_certificates rows', '/admin/certificates.php', $canCertificates, 'Open', 'cmd-stat-navy');
    eca_dash_stat('Active', (int) $stats['certificates_active'], 'status Active', '/admin/certificates.php', $canCertificates);
    eca_dash_stat('Revoked', (int) $stats['certificates_revoked'], 'status Revoked', '/admin/certificates.php', $canCertificates);
    eca_dash_stat('Expiring (90 days)', (int) $stats['certificates_expiring'], 'Active with near expiry', '/admin/certificates.php', $canCertificates, 'View', (int) $stats['certificates_expiring'] > 0 ? 'cmd-stat-alert' : '');
    ?>
</div>
<?php endif; ?>
</section>
<?php endif; ?>

<?php if ($canCpd): ?>
<section class="cmd-section">
<div class="hub-card-head cmd-section-head">
    <div>
        <p class="cmd-eyebrow">CPD</p>
        <h2>Continuing professional development</h2>
    </div>
    <a href="/admin/cpd.php">Open CPD</a>
</div>
<?php if (empty($stats['sources_available']['cpd'])): ?>
<p class="hub-empty">DATA NOT AVAILABLE LOCALLY — CPD tables not found.</p>
<?php else: ?>
<div class="hub-stats">
    <?php
    eca_dash_stat('Applications', (int) $stats['cpd_applications'], 'cpd_applications', '/admin/cpd.php', $canCpd, 'Open', 'cmd-stat-navy');
    eca_dash_stat('Pending', (int) $stats['cpd_pending'], 'status pending', '/admin/cpd.php', $canCpd, 'View', (int) $stats['cpd_pending'] > 0 ? 'cmd-stat-alert' : '');
    eca_dash_stat('Approved / completed', (int) ($stats['cpd_approved'] ?? 0), 'approved/completed/accepted', '/admin/cpd.php', $canCpd);
    eca_dash_stat('Rejected', (int) ($stats['cpd_rejected'] ?? 0), 'rejected/declined', '/admin/cpd.php', $canCpd);
    eca_dash_stat('Courses', (int) $stats['cpd_courses'], (int) $stats['cpd_open'] . ' open', '/admin/cpd.php', $canCpd);
    eca_dash_stat('Completed courses', (int) ($stats['cpd_courses_completed'] ?? 0), 'status completed/closed/finished', '/admin/cpd.php', $canCpd);
    eca_dash_stat('Points ledger', (int) $stats['cpd_points'], 'SUM(cpd_points_ledger.points)', '/admin/cpd.php', $canCpd);
    ?>
</div>
<?php endif; ?>
</section>
<?php endif; ?>

<?php if ($canWellness): ?>
<section class="cmd-section">
<div class="hub-card-head cmd-section-head">
    <div>
        <p class="cmd-eyebrow">Wellness</p>
        <h2>Wellness programme</h2>
    </div>
    <a href="/admin/wellness/">Open Wellness</a>
</div>
<?php if (empty($stats['sources_available']['wellness'])): ?>
<p class="hub-empty">DATA NOT AVAILABLE LOCALLY — wellness stats helper not loaded.</p>
<?php else: ?>
<div class="hub-stats">
    <?php
    eca_dash_stat('Upcoming events', (int) $stats['wellness_events'], 'PUBLISHED and starts_at >= NOW()', '/admin/wellness/', $canWellness, 'Open', 'cmd-stat-navy');
    eca_dash_stat('Published events', (int) ($stats['wellness_events_published'] ?? 0), 'wellness_events status=PUBLISHED', '/admin/wellness/', $canWellness);
    eca_dash_stat('Announcements', (int) $stats['wellness_announcements'], 'Active announcements', '/admin/wellness/', $canWellness);
    eca_dash_stat('Resources', (int) $stats['wellness_resources'], 'PUBLISHED resources', '/admin/wellness/', $canWellness);
    eca_dash_stat('Registrations', (int) $stats['wellness_registrations'], 'wellness_event_registrations', '/admin/wellness/', $canWellness);
    eca_dash_stat('Hub published', (int) $stats['wellness_hub_published'], 'Public hub items', '/wellness/', $canOpenPortals || $canWellnessView || $canWellnessManage);
    eca_dash_stat('Check-ins', (int) $stats['wellness_checkins'], (int) $stats['wellness_checkins_30d'] . ' in 30 days', '/admin/wellness/', $canWellness);
    ?>
</div>
<?php endif; ?>
</section>
<?php endif; ?>

<?php if ($canTickets || $canAudit): ?>
<section class="cmd-section">
<div class="hub-card-head cmd-section-head">
    <div>
        <p class="cmd-eyebrow">Support</p>
        <h2>Support &amp; communication</h2>
    </div>
    <?php if ($canTickets): ?><a href="/admin/tickets.php">Tickets</a><?php endif; ?>
</div>
<div class="hub-stats">
    <?php
    if ($canTickets) {
        if (!empty($stats['sources_available']['tickets'])) {
            eca_dash_stat('Open tickets / messages', (int) $stats['tickets_open'], 'contact_messages + support_tickets', '/admin/tickets.php', $canTickets, 'Open', (int) $stats['tickets_open'] > 0 ? 'cmd-stat-alert' : 'cmd-stat-navy');
        } else {
            echo '<p class="hub-empty">DATA NOT AVAILABLE LOCALLY — ticket tables not found.</p>';
        }
    }
    if ($canUsers) {
        eca_dash_stat('Officers', (int) $stats['officers_total'], (int) $stats['officers_active'] . ' active', '/admin/users.php', $canUsers);
    }
    ?>
</div>
<?php if ($canTickets && !empty($stats['recent_messages'])): ?>
<div class="hub-card eca-table-panel" style="margin-top:10px;">
    <h3 style="margin:0 0 8px;font-size:0.95rem;">Recent contact messages</h3>
    <table class="hub-table">
        <thead><tr><th>Subject</th><th>From</th><th>Status</th><th></th></tr></thead>
        <tbody>
        <?php foreach ($stats['recent_messages'] as $row): ?>
            <tr>
                <td><?= eca_admin_h((string) ($row['subject'] ?? '—')) ?></td>
                <td><?= eca_admin_h((string) ($row['name'] ?? $row['email'] ?? '')) ?></td>
                <td><?= eca_admin_h((string) ($row['status'] ?? '')) ?></td>
                <td><a href="/admin/ticket-detail.php?id=<?= (int) ($row['id'] ?? 0) ?>">Open</a></td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
    <?php eca_render_named_request_pager($stats['recent_messages_pager'] ?? []); ?>
</div>
<?php elseif ($canTickets && !empty($stats['sources_available']['tickets'])): ?>
<p class="hub-empty" style="margin-top:8px;">No contact messages found.</p>
<?php endif; ?>
</section>
<?php endif; ?>

<section class="cmd-section">
<div class="hub-card-head cmd-section-head">
    <div>
        <p class="cmd-eyebrow">Operations</p>
        <h2>At-a-glance operations</h2>
    </div>
</div>
<div class="hub-stats">
    <?php
    eca_dash_stat('Certificates issued', (int) $stats['certificates_issued'], (int) $stats['certificates_active'] . ' active', '/admin/certificates.php', $canCertificates, 'View', 'cmd-stat-navy');
    eca_dash_stat('Payments pending', (int) $stats['payments_pending'], (int) $stats['payments_verified'] . ' verified proofs', '/admin/payments.php?status=pending', $canPayments, 'View', (int) $stats['payments_pending'] > 0 ? 'cmd-stat-alert' : '');
    eca_dash_stat('CPD activity', (int) $stats['cpd_applications'], (int) $stats['cpd_pending'] . ' pending · ' . (int) $stats['cpd_points'] . ' points', '/admin/cpd.php', $canCpd, 'Open CPD');
    eca_dash_stat(
        'Wellness',
        (int) $stats['wellness_events'],
        (int) $stats['wellness_announcements'] . ' announcements · ' . (int) $stats['wellness_resources'] . ' resources · ' . (int) $stats['wellness_checkins'] . ' check-ins',
        '/admin/wellness/',
        $canWellness
    );
    ?>
</div>
</section>

<div class="hub-split">
    <section class="hub-card">
        <div class="hub-card-head">
            <div>
                <p class="cmd-eyebrow">Standing</p>
                <h2>Membership overview</h2>
            </div>
            <?php if ($canMembers): ?><a href="/admin/members.php">View members</a><?php endif; ?>
        </div>
        <?php
        eca_dash_meter('Active', (int) $stats['members_active'], $standingMax, '/admin/members.php?standing=Active', $canMembers);
        eca_dash_meter('Pending', (int) $stats['members_pending'], $standingMax, '/admin/members.php?standing=Pending', $canMembers);
        eca_dash_meter('Expired', (int) $stats['members_expired'], $standingMax, '/admin/members.php?year_state=expired', $canMembers);
        eca_dash_meter('Suspended', (int) $stats['members_suspended'], $standingMax, '/admin/members.php?standing=Suspended', $canMembers);
        if ((int) $stats['members_other'] > 0) {
            eca_dash_meter('Other standing', (int) $stats['members_other'], $standingMax, '/admin/members.php', $canMembers);
        }
        ?>
    </section>
    <section class="hub-card">
        <div class="hub-card-head">
            <div>
                <p class="cmd-eyebrow">Queue</p>
                <h2>Applications</h2>
            </div>
            <?php if ($canApplications): ?><a href="/admin/applications.php">View applications</a><?php endif; ?>
        </div>
        <?php
        eca_dash_meter('New / submitted', (int) $stats['applications_submitted'], $appMax, '/admin/applications.php?status=SUBMITTED', $canApplications);
        eca_dash_meter('Pending review', (int) $stats['applications_review'], $appMax, '/admin/applications.php?status=UNDER REVIEW', $canApplications);
        eca_dash_meter('Returned', (int) $stats['applications_returned'], $appMax, '/admin/applications.php?status=ADDITIONAL INFORMATION REQUIRED', $canApplications);
        eca_dash_meter('Approved', (int) $stats['applications_approved'], $appMax, '/admin/applications.php?status=APPROVED', $canApplications);
        eca_dash_meter('Rejected', (int) $stats['applications_rejected'], $appMax, '/admin/applications.php?status=REJECTED', $canApplications);
        ?>
    </section>
</div>

<div class="hub-split">
    <section class="hub-card">
        <div class="hub-card-head">
            <div>
                <p class="cmd-eyebrow">Priority</p>
                <h2>Attention required</h2>
            </div>
        </div>
        <?php if (!$alerts): ?>
            <p class="hub-empty">No immediate action required.</p>
        <?php else: ?>
            <ul class="hub-alert-list">
                <?php foreach ($alerts as $alert): ?>
                    <li>
                        <a href="<?= eca_admin_h($alert[1]) ?>">
                            <span><?= eca_admin_h($alert[0]) ?></span>
                            <strong>Open</strong>
                        </a>
                    </li>
                <?php endforeach; ?>
            </ul>
        <?php endif; ?>
        <p class="hub-stat-meta" style="margin-top:12px;">Open tickets: <?= (int) $stats['tickets_open'] ?> · Outstanding balances: <?= (int) $stats['balances_due'] ?> · Open CPD courses: <?= (int) $stats['cpd_open'] ?></p>
    </section>
    <section class="hub-card">
        <div class="hub-card-head">
            <div>
                <p class="cmd-eyebrow">Audit</p>
                <h2>Recent activity</h2>
            </div>
            <?php if ($canAudit): ?><a href="/admin/audit.php">Audit logs</a><?php endif; ?>
        </div>
        <?php if (!$stats['activity']): ?>
            <p class="hub-empty">No recent activity.</p>
        <?php else: ?>
            <ul class="hub-activity">
                <?php foreach ($stats['activity'] as $row): ?>
                    <li>
                        <div>
                            <strong><?= eca_admin_h(eca_admin_audit_label((string) ($row['action'] ?? ''))) ?></strong>
                            <p><?= eca_admin_h($row['actor_email'] ?: ($row['actor_type'] ?? 'system')) ?><?php
                            $entity = trim((string) ($row['entity_type'] ?? '') . ' ' . (string) ($row['entity_id'] ?? ''));
                            if ($entity !== '') {
                                echo ' · ' . eca_admin_h($entity);
                            }
                            ?></p>
                        </div>
                        <time><?= eca_admin_h((string) ($row['created_at'] ?? '')) ?></time>
                    </li>
                <?php endforeach; ?>
            </ul>
            <?php eca_render_named_request_pager($stats['activity_pager'] ?? []); ?>
        <?php endif; ?>
    </section>
</div>

<section class="hub-card cmd-section">
    <div class="hub-card-head">
        <div>
            <p class="cmd-eyebrow">Snapshot</p>
            <h2>System summary</h2>
        </div>
    </div>
    <div class="hub-summary">
        <?php if ($canSecurity): ?>
        <div><span>Registered officers</span><strong><?= (int) $stats['officers_total'] ?></strong></div>
        <div><span>Active officers</span><strong><?= (int) $stats['officers_active'] ?></strong></div>
        <div><span>Super Admins</span><strong><?= (int) $stats['super_admins'] ?></strong></div>
        <?php endif; ?>
        <div><span>Members</span><strong><?= (int) $stats['members_total'] ?></strong></div>
        <div><span>Contractors</span><strong><?= (int) $stats['companies'] ?></strong></div>
        <div><span>Applications</span><strong><?= (int) $stats['applications_total'] ?></strong></div>
        <div><span>Certificates</span><strong><?= (int) $stats['certificates_issued'] ?></strong></div>
        <div><span>CPD applications</span><strong><?= (int) $stats['cpd_applications'] ?></strong></div>
        <div><span>Wellness events</span><strong><?= (int) $stats['wellness_events'] ?></strong></div>
        <div><span>Wellness resources</span><strong><?= (int) $stats['wellness_resources'] ?></strong></div>
        <div><span>Wellness check-ins</span><strong><?= (int) $stats['wellness_checkins'] ?></strong></div>
        <div><span>Open CPD courses</span><strong><?= (int) $stats['cpd_open'] ?></strong></div>
    </div>
</section>

<?php
$hasTrends = array_sum($stats['trends_members']) > 0
    || array_sum($stats['trends_applications']) > 0
    || array_sum($stats['trends_cpd']) > 0;
if ($hasTrends):
?>
<section class="hub-card cmd-section">
    <div class="hub-card-head">
        <div>
            <p class="cmd-eyebrow">History</p>
            <h2>Trends</h2>
        </div>
    </div>
    <p class="hub-stat-meta" style="margin:0 0 10px;">See also <a href="#exec-analytics">Executive analytics</a> for interactive charts. Bar rows below work without JavaScript.</p>
    <div class="hub-trend-grid">
        <?php
        eca_dash_trend('New member records', $stats['trends_members']);
        eca_dash_trend('Applications with a reference', $stats['trends_applications']);
        eca_dash_trend('CPD applications', $stats['trends_cpd']);
        ?>
    </div>
</section>
<?php endif; ?>

<?php if ($typeCounts): ?>
<section class="cmd-section">
<div class="hub-card-head cmd-section-head">
    <div>
        <p class="cmd-eyebrow">Directory</p>
        <h2>Companies by type</h2>
    </div>
</div>
<div class="hub-stats">
    <?php eca_dash_stat('All', (int) $stats['companies'], 'Directory', '/admin/companies.php', $canCompanies, 'View all'); ?>
    <?php foreach ($typeCounts as $type => $count): ?>
        <?php eca_dash_stat((string) $type, (int) $count, 'Companies', '/admin/companies.php?industry=' . rawurlencode((string) $type), $canCompanies, 'View Details'); ?>
    <?php endforeach; ?>
</div>
</section>
<?php endif; ?>

<section class="cmd-section">
<div class="hub-card-head cmd-section-head">
    <div>
        <p class="cmd-eyebrow">Shortcuts</p>
        <h2>Quick actions</h2>
    </div>
</div>
<div class="hub-tiles">
    <?php foreach ($quick as $item): ?>
        <?php if (empty($item[3])) continue; ?>
        <a class="hub-tile<?= !empty($item[4]) ? ' ' . eca_admin_h($item[4]) : '' ?>" href="<?= eca_admin_h($item[2]) ?>">
            <h3><?= eca_admin_h($item[0]) ?></h3>
            <p><?= eca_admin_h($item[1]) ?></p>
            <span>Open →</span>
        </a>
    <?php endforeach; ?>
</div>
</section>
</div>
<?php if ($chartPayload): ?>
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
<script>
(function () {
  if (typeof Chart === 'undefined') return;
  var payload = <?= json_encode($chartPayload, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>;
  var palette = ['#071a3d', '#2864ff', '#ff5148', '#2f9e7b', '#c9852f', '#697792', '#5b4bb7', '#0b6e99'];
  Object.keys(payload).forEach(function (key) {
    var cfg = payload[key];
    if (!cfg || !cfg.has_data) return;
    var canvas = document.getElementById('exec-chart-' + key);
    if (!canvas) return;
    var colors = (cfg.labels || []).map(function (_, i) { return palette[i % palette.length]; });
    var dataset = {
      label: cfg.title || key,
      data: cfg.values || [],
      backgroundColor: cfg.type === 'line' ? 'rgba(40,100,255,0.15)' : colors,
      borderColor: cfg.type === 'line' ? '#2864ff' : colors,
      borderWidth: cfg.type === 'line' ? 2 : 1,
      fill: cfg.type === 'line',
      tension: 0.3,
      pointRadius: 3,
      pointBackgroundColor: '#071a3d'
    };
    new Chart(canvas, {
      type: cfg.type || 'bar',
      data: { labels: cfg.labels || [], datasets: [dataset] },
      options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
          legend: { display: cfg.type === 'doughnut' || cfg.type === 'pie' },
          tooltip: { enabled: true }
        },
        scales: (cfg.type === 'doughnut' || cfg.type === 'pie') ? {} : {
          x: { ticks: { maxRotation: 45, minRotation: 0, font: { size: 11 } }, grid: { display: false } },
          y: { beginAtZero: true, ticks: { precision: 0, font: { size: 11 } }, grid: { color: 'rgba(15,35,74,0.06)' } }
        }
      }
    });
  });
})();
</script>
<?php endif; ?>
<?php eca_admin_hub_end(); ?>
