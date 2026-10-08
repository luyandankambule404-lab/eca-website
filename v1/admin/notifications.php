<?php
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/_hub.php';
require_once __DIR__ . '/../includes/portal-db.php';
require_once __DIR__ . '/../includes/admin-stats.php';
require_once __DIR__ . '/../includes/admin-ops.php';
require_once __DIR__ . '/../includes/pagination.php';
eca_admin_require('hub.access');
header('Cache-Control: no-store');

$local = eca_admin_db();
$portal = eca_portal_pdo(false);
$stats = eca_admin_dashboard_stats($local, $portal);
$role = eca_normalize_role((string) ((eca_admin_user()['role'] ?? 'admin')));

if ($_SERVER['REQUEST_METHOD'] === 'POST' && eca_admin_csrf_ok($_POST['csrf_token'] ?? null)) {
    eca_admin_notice_dismiss((string) ($_POST['dismiss'] ?? ''));
    header('Location: /admin/notifications.php');
    exit;
}

$alerts = [];
if (eca_can('applications.manage', $role) && $stats['applications_pending'] > 0 && !eca_admin_notice_is_dismissed('applications_pending')) {
    $alerts[] = ['applications_pending', $stats['applications_pending'] . ' membership application(s) waiting for review', '/admin/applications.php'];
}
if (eca_can('payments.manage', $role) && $stats['payments_pending'] > 0 && !eca_admin_notice_is_dismissed('payments_pending')) {
    $alerts[] = ['payments_pending', $stats['payments_pending'] . ' payment proof(s) waiting for verification', '/admin/payments.php?status=pending'];
}
if (eca_can('tickets.manage', $role) && $stats['tickets_open'] > 0 && !eca_admin_notice_is_dismissed('tickets_open')) {
    $alerts[] = ['tickets_open', $stats['tickets_open'] . ' open support ticket(s)', '/admin/tickets.php'];
}
if (eca_can('certificates.manage', $role) && $stats['certificates_expiring'] > 0 && !eca_admin_notice_is_dismissed('certificates_expiring')) {
    $alerts[] = ['certificates_expiring', $stats['certificates_expiring'] . ' certificate(s) approaching expiry', '/admin/certificates.php'];
}
if (eca_can('members.manage', $role) && $stats['members_pending'] > 0 && !eca_admin_notice_is_dismissed('members_pending')) {
    $alerts[] = ['members_pending', $stats['members_pending'] . ' member(s) with pending standing', '/admin/members.php?standing=Pending'];
}
if (eca_can('members.manage', $role) && $stats['members_near_expiry'] > 0 && !eca_admin_notice_is_dismissed('members_near_expiry')) {
    $alerts[] = ['members_near_expiry', $stats['members_near_expiry'] . ' membership year(s) expiring within 90 days', '/admin/members.php?year_state=near'];
}
if (eca_can('cpd.view', $role) && $stats['cpd_pending'] > 0 && !eca_admin_notice_is_dismissed('cpd_pending')) {
    $alerts[] = ['cpd_pending', $stats['cpd_pending'] . ' CPD application(s) pending in the CPD portal', '/admin/cpd.php'];
}
if ((eca_can('wellness.manage', $role) || eca_can('wellness.view', $role)) && (int) $stats['wellness_events'] > 0 && !eca_admin_notice_is_dismissed('wellness_events')) {
    $alerts[] = ['wellness_events', $stats['wellness_events'] . ' upcoming wellness event(s)', '/admin/wellness/'];
}

$systemEvents = [];
if ($local && eca_can('security.view', $role)) {
    $systemEvents = eca_safe_rows(
        $local,
        "SELECT actor_email, action, entity_type, entity_id, created_at
         FROM audit_logs
         WHERE action IN ('admin.login.failed','user.promoted','user.demoted','user.activated','user.deactivated','permissions.changed','settings.changed')
         ORDER BY id DESC LIMIT 12"
    );
} elseif ($local && eca_can('audit.view', $role)) {
    $blocked = eca_security_audit_actions();
    $marks = implode(',', array_fill(0, count($blocked), '?'));
    $systemEvents = eca_safe_rows(
        $local,
        "SELECT actor_email, action, entity_type, entity_id, created_at
         FROM audit_logs
         WHERE action NOT IN ($marks)
         ORDER BY id DESC LIMIT 8",
        $blocked
    );
}

$memberNotes = [];
if ($portal && (eca_can('content.manage', $role) || eca_can('members.manage', $role))) {
    try {
        $memberNotes = $portal->query(
            'SELECT id, title, type, created_at, membership_number FROM notifications ORDER BY id DESC LIMIT 20'
        )->fetchAll(PDO::FETCH_ASSOC);
    } catch (Throwable $e) {
        $memberNotes = [];
    }
}

eca_admin_hub_start('Notifications', 'notifications');
$csrf = eca_admin_csrf();
?>
<div class="hub-hello">
    <h1 class="hub-hello-title">Notifications</h1>
    <p>Administrative attention is separate from member-facing notifications. This phase does not send email. Dismissed admin alerts are stored only in this browser session.</p>
</div>
<div class="hub-split">
    <section class="hub-card">
        <div class="hub-card-head"><h2>Admin system notifications</h2></div>
        <?php if (!$alerts && !$systemEvents): ?>
            <p class="hub-empty">No administrative alerts right now.</p>
        <?php endif; ?>
        <?php if ($alerts): ?>
            <ul class="hub-alert-list">
                <?php foreach ($alerts as $alert): ?>
                    <li>
                        <a href="<?= eca_admin_h($alert[2]) ?>"><span><?= eca_admin_h($alert[1]) ?></span><strong>Open</strong></a>
                        <form method="post" style="display:inline;">
                            <input type="hidden" name="csrf_token" value="<?= eca_admin_h($csrf) ?>">
                            <input type="hidden" name="dismiss" value="<?= eca_admin_h($alert[0]) ?>">
                            <button class="hub-btn" type="submit">Dismiss</button>
                        </form>
                    </li>
                <?php endforeach; ?>
            </ul>
        <?php endif; ?>
        <?php if ($systemEvents): ?>
            <ul class="hub-activity">
                <?php foreach ($systemEvents as $row): ?>
                    <li>
                        <div>
                            <strong><?= eca_admin_h(eca_admin_audit_label((string) ($row['action'] ?? ''))) ?></strong>
                            <p><?= eca_admin_h($row['actor_email'] ?: 'system') ?></p>
                        </div>
                        <time><?= eca_admin_h($row['created_at'] ?? '') ?></time>
                    </li>
                <?php endforeach; ?>
            </ul>
        <?php endif; ?>
    </section>
    <section class="hub-card">
        <div class="hub-card-head"><h2>Member notifications</h2></div>
        <p class="hub-note">These are existing member-facing records in the portal database. They are not an officer inbox.</p>
        <?php if (!$memberNotes): ?>
            <p class="hub-empty"><?= eca_can('content.manage', $role) || eca_can('members.manage', $role) ? 'No member notifications stored.' : 'Member notification records are limited to membership or content officers.' ?></p>
        <?php else: ?>
            <ul class="hub-activity">
                <?php foreach ($memberNotes as $row): ?>
                    <li>
                        <div>
                            <strong><?= eca_admin_h($row['title'] ?? '') ?></strong>
                            <p><?= eca_admin_h(($row['type'] ?? '') . ' · ' . ($row['membership_number'] ?? 'all')) ?></p>
                        </div>
                        <time><?= eca_admin_h($row['created_at'] ?? '') ?></time>
                    </li>
                <?php endforeach; ?>
            </ul>
        <?php endif; ?>
    </section>
</div>
<?php eca_admin_hub_end(); ?>
