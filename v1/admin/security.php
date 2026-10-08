<?php
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/_hub.php';
require_once __DIR__ . '/../includes/portal-db.php';
require_once __DIR__ . '/../includes/admin-stats.php';
require_once __DIR__ . '/../includes/audit.php';
eca_admin_require('security.view');
header('Cache-Control: no-store');

$conn = eca_admin_db();
$stats = eca_admin_dashboard_stats($conn, eca_portal_pdo(false));
$officerRoles = eca_hub_officer_role_slugs();
$placeholders = implode(',', array_fill(0, count($officerRoles), '?'));
$activeOfficers = 0;
$inactiveOfficers = 0;
$failed = [];
$logins = [];
$roleChanges = [];
$activations = [];
$securityRows = [];
$hasUserStatus = $conn && function_exists('eca_hub_users_has_column') && eca_hub_users_has_column($conn, 'status');
if ($conn) {
    if ($hasUserStatus) {
        $activeOfficers = eca_safe_count(
            $conn,
            "SELECT COUNT(*) FROM users WHERE role IN ($placeholders) AND (status IS NULL OR UPPER(status) = 'ACTIVE')",
            $officerRoles
        );
        $inactiveOfficers = eca_safe_count(
            $conn,
            "SELECT COUNT(*) FROM users WHERE role IN ($placeholders) AND UPPER(status) = 'INACTIVE'",
            $officerRoles
        );
    } else {
        $activeOfficers = eca_safe_count(
            $conn,
            "SELECT COUNT(*) FROM users WHERE role IN ($placeholders)",
            $officerRoles
        );
        $inactiveOfficers = 0;
    }
    $failed = eca_safe_rows($conn, "SELECT actor_email, created_at, ip FROM audit_logs WHERE action = 'admin.login.failed' ORDER BY id DESC LIMIT 12");
    $logins = eca_safe_rows($conn, "SELECT actor_email, created_at FROM audit_logs WHERE action = 'admin.login' ORDER BY id DESC LIMIT 12");
    $roleChanges = eca_safe_rows($conn, "SELECT actor_email, action, entity_id, created_at FROM audit_logs WHERE action IN ('user.roles','user.promoted','user.demoted') ORDER BY id DESC LIMIT 12");
    $activations = eca_safe_rows($conn, "SELECT actor_email, action, entity_id, created_at FROM audit_logs WHERE action IN ('user.activated','user.deactivated') ORDER BY id DESC LIMIT 12");
    $securityActions = eca_security_audit_actions();
    $marks = implode(',', array_fill(0, count($securityActions), '?'));
    $securityRows = eca_safe_rows(
        $conn,
        "SELECT actor_email, action, entity_type, entity_id, created_at FROM audit_logs WHERE action IN ($marks) ORDER BY id DESC LIMIT 20",
        $securityActions
    );
}
eca_admin_hub_start('Security Centre', 'security');
?>
<div class="hub-hello">
    <h1 class="hub-hello-title">Security Centre</h1>
    <p>Local officer posture and security audit activity. Passwords, hashes, tokens and credentials are never shown.</p>
</div>
<div class="hub-summary">
    <div><span>Active officers</span><strong><?= (int) $activeOfficers ?></strong></div>
    <div><span>Inactive officers</span><strong><?= $hasUserStatus ? (int) $inactiveOfficers : 'N/A' ?></strong></div>
    <div><span>Super Admins</span><strong><?= (int) $stats['super_admins'] ?></strong></div>
</div>
<?php if (!$hasUserStatus): ?>
<p class="hub-note">Officer active/inactive counts use the users list only — <code>users.status</code> is not available locally (DATA NOT AVAILABLE LOCALLY for inactive posture).</p>
<?php endif; ?>
<div class="hub-split" style="margin-top:18px;">
    <section class="hub-card">
        <div class="hub-card-head"><h2>Recent failed logins</h2></div>
        <?php if (!$failed): ?><p class="hub-empty">No failed logins recorded.</p><?php endif; ?>
        <ul class="hub-activity">
            <?php foreach ($failed as $row): ?>
                <li><div><strong><?= eca_admin_h($row['actor_email'] ?: 'unknown') ?></strong><p><?= eca_admin_h($row['ip'] ?? '') ?></p></div><time><?= eca_admin_h($row['created_at'] ?? '') ?></time></li>
            <?php endforeach; ?>
        </ul>
    </section>
    <section class="hub-card">
        <div class="hub-card-head"><h2>Recent successful logins</h2></div>
        <?php if (!$logins): ?><p class="hub-empty">No successful logins recorded.</p><?php endif; ?>
        <ul class="hub-activity">
            <?php foreach ($logins as $row): ?>
                <li><div><strong><?= eca_admin_h($row['actor_email'] ?: 'officer') ?></strong></div><time><?= eca_admin_h($row['created_at'] ?? '') ?></time></li>
            <?php endforeach; ?>
        </ul>
    </section>
</div>
<div class="hub-split">
    <section class="hub-card">
        <div class="hub-card-head"><h2>Role changes</h2></div>
        <?php if (!$roleChanges): ?><p class="hub-empty">No recent role changes.</p><?php endif; ?>
        <ul class="hub-activity">
            <?php foreach ($roleChanges as $row): ?>
                <li><div><strong><?= eca_admin_h(eca_admin_audit_label((string) ($row['action'] ?? ''))) ?></strong><p><?= eca_admin_h(($row['actor_email'] ?? '') . ' · user ' . ($row['entity_id'] ?? '')) ?></p></div><time><?= eca_admin_h($row['created_at'] ?? '') ?></time></li>
            <?php endforeach; ?>
        </ul>
    </section>
    <section class="hub-card">
        <div class="hub-card-head"><h2>Activation / deactivation</h2></div>
        <?php if (!$activations): ?><p class="hub-empty">No recent status changes.</p><?php endif; ?>
        <ul class="hub-activity">
            <?php foreach ($activations as $row): ?>
                <li><div><strong><?= eca_admin_h(eca_admin_audit_label((string) ($row['action'] ?? ''))) ?></strong><p><?= eca_admin_h(($row['actor_email'] ?? '') . ' · user ' . ($row['entity_id'] ?? '')) ?></p></div><time><?= eca_admin_h($row['created_at'] ?? '') ?></time></li>
            <?php endforeach; ?>
        </ul>
    </section>
</div>
<section class="hub-card">
    <div class="hub-card-head"><h2>Security-related audit activity</h2><?php if (eca_can('audit.view')): ?><a href="/admin/audit.php?type=security">Open audit</a><?php endif; ?></div>
    <?php if (!$securityRows): ?><p class="hub-empty">No security audit rows.</p><?php endif; ?>
    <ul class="hub-activity">
        <?php foreach ($securityRows as $row): ?>
            <li>
                <div>
                    <strong><?= eca_admin_h(eca_admin_audit_label((string) ($row['action'] ?? ''))) ?></strong>
                    <p><?= eca_admin_h(($row['actor_email'] ?? 'system') . ' · ' . trim(($row['entity_type'] ?? '') . ' ' . ($row['entity_id'] ?? ''))) ?></p>
                </div>
                <time><?= eca_admin_h($row['created_at'] ?? '') ?></time>
            </li>
        <?php endforeach; ?>
    </ul>
</section>
<?php eca_admin_hub_end(); ?>
