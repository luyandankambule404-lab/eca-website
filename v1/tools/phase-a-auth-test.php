<?php
/**
 * LOCAL Phase A authorization tests. Does not touch production.
 */

if (PHP_SAPI !== 'cli') {
    fwrite(STDERR, "CLI only.\n");
    exit(1);
}

require_once dirname(__DIR__) . '/includes/authz.php';
require_once dirname(__DIR__) . '/includes/audit.php';

$fail = 0;
$pass = 0;
function expect_true(bool $ok, string $label): void
{
    global $fail, $pass;
    if ($ok) {
        $pass++;
        echo "PASS {$label}\n";
        return;
    }
    $fail++;
    echo "FAIL {$label}\n";
}

if (!eca_rbac_is_local_database()) {
    fwrite(STDERR, "STOP: not local eca_local.\n");
    exit(2);
}

$conn = eca_rbac_pdo();
if (!$conn) {
    fwrite(STDERR, "No local database connection.\n");
    exit(1);
}

expect_true(!eca_can('users.manage', 'admin'), 'admin cannot users.manage');
expect_true(!eca_can('users.view', 'admin'), 'admin cannot users.view');
expect_true(!eca_can('users.promote_super_admin', 'admin'), 'admin cannot promote Super Admin');
expect_true(!eca_can('roles.manage', 'admin'), 'admin cannot roles.manage');
expect_true(!eca_can('permissions.manage', 'admin'), 'admin cannot permissions.manage');
expect_true(!eca_can('settings.manage', 'admin'), 'admin cannot settings.manage');
expect_true(!eca_can('security.view', 'admin'), 'admin cannot security.view');
expect_true(eca_can('members.manage', 'admin'), 'admin keeps members.manage');
expect_true(eca_can('applications.manage', 'admin'), 'admin keeps applications.manage');
expect_true(eca_can('audit.view', 'admin'), 'admin keeps audit.view');
expect_true(eca_can('wellness.manage', 'admin'), 'admin keeps wellness.manage');
expect_true(eca_can('users.manage', 'super_admin'), 'super_admin can users.manage');
expect_true(eca_can('users.promote_super_admin', 'super_admin'), 'super_admin can promote');
expect_true(eca_can('roles.manage', 'super_admin'), 'super_admin can roles.manage');
expect_true(eca_can('permissions.manage', 'super_admin'), 'super_admin can permissions.manage');
expect_true(eca_can('settings.manage', 'super_admin'), 'super_admin can settings.manage');
expect_true(eca_can('security.view', 'super_admin'), 'super_admin can security.view');
expect_true(eca_can('members.manage', 'membership_officer'), 'membership_officer keeps members.manage');
expect_true(!eca_can('users.manage', 'membership_officer'), 'membership_officer cannot users.manage');
expect_true(eca_can('payments.manage', 'finance_officer'), 'finance_officer keeps payments.manage');
expect_true(eca_can('content.manage', 'content_manager'), 'content_manager keeps content.manage');
expect_true(eca_can('education.manage', 'training_officer'), 'training_officer keeps education.manage');
expect_true(!eca_can('hub.access', 'member'), 'member has no hub.access');
expect_true(!eca_can('hub.access', 'public'), 'public has no hub.access');

$roleMap = [];
foreach ($conn->query('SELECT id, slug FROM roles')->fetchAll(PDO::FETCH_ASSOC) as $role) {
    $roleMap[eca_normalize_role((string) $role['slug'])] = (int) $role['id'];
}
expect_true(isset($roleMap['super_admin'], $roleMap['admin'], $roleMap['membership_officer']), 'existing roles preserved');

$stamp = bin2hex(random_bytes(4));
$adminEmail = "phasea.admin.{$stamp}@local.test";
$saEmail = "phasea.sa.{$stamp}@local.test";
$targetEmail = "phasea.target.{$stamp}@local.test";
$hash = password_hash(bin2hex(random_bytes(16)), PASSWORD_DEFAULT);
$hasStatus = eca_hub_users_has_column($conn, 'status');

$insertUser = function (string $email, string $role) use ($conn, $hash, $hasStatus): int {
    $sql = $hasStatus
        ? 'INSERT INTO users (name, email, password, role, status, is_admin, created_at, updated_at) VALUES (?, ?, ?, ?, ?, ?, NOW(), NOW())'
        : 'INSERT INTO users (name, email, password, role, is_admin, created_at, updated_at) VALUES (?, ?, ?, ?, ?, NOW(), NOW())';
    $params = $hasStatus
        ? ['Phase A Test', $email, $hash, $role, 'ACTIVE', $role === 'super_admin' || $role === 'admin' ? 1 : 0]
        : ['Phase A Test', $email, $hash, $role, $role === 'super_admin' || $role === 'admin' ? 1 : 0];
    $conn->prepare($sql)->execute($params);
    return (int) $conn->lastInsertId();
};

$adminId = $insertUser($adminEmail, 'admin');
$saId = $insertUser($saEmail, 'super_admin');
$targetId = $insertUser($targetEmail, 'membership_officer');
$conn->prepare('INSERT INTO user_roles (user_id, role_id) VALUES (?, ?)')->execute([$adminId, $roleMap['admin']]);
$conn->prepare('INSERT INTO user_roles (user_id, role_id) VALUES (?, ?)')->execute([$saId, $roleMap['super_admin']]);
$conn->prepare('INSERT INTO user_roles (user_id, role_id) VALUES (?, ?)')->execute([$targetId, $roleMap['membership_officer']]);

$adminActor = eca_hub_reload_actor($conn, $adminId);
$saActor = eca_hub_reload_actor($conn, $saId);
expect_true(($adminActor['role'] ?? '') === 'admin', 'admin actor reloads as admin');
expect_true(($saActor['role'] ?? '') === 'super_admin', 'super admin actor reloads as super_admin');

$denied = eca_assign_hub_roles($conn, $targetId, [$roleMap['super_admin']], $adminActor);
expect_true(empty($denied['ok']), 'admin POST cannot promote Super Admin');
expect_true(str_contains((string) ($denied['message'] ?? ''), 'Super Admin'), 'admin promotion error is explicit');

$crafted = eca_assign_hub_roles($conn, $targetId, [$roleMap['admin'], $roleMap['super_admin']], $adminActor);
expect_true(empty($crafted['ok']), 'admin cannot assign super_admin even with extra role ids');

$promoted = eca_assign_hub_roles($conn, $targetId, [$roleMap['super_admin']], $saActor);
expect_true(!empty($promoted['ok']), 'super admin can promote another Super Admin');

$demoteOk = eca_assign_hub_roles($conn, $saId, [$roleMap['admin']], eca_hub_reload_actor($conn, $targetId));
expect_true(!empty($demoteOk['ok']), 'super admin can demote another Super Admin when one remains');

$lastDenied = eca_assign_hub_roles($conn, $targetId, [$roleMap['admin']], eca_hub_reload_actor($conn, $targetId));
if (eca_is_final_active_super_admin($conn, $targetId)) {
    expect_true(empty($lastDenied['ok']), 'cannot demote the final Super Admin');
    expect_true(str_contains((string) ($lastDenied['message'] ?? ''), 'final'), 'final Super Admin demote message is explicit');
} else {
    expect_true(eca_count_active_super_admins($conn, $targetId) >= 1, 'another Super Admin exists so the temp account is not final');
}

$restorer = eca_hub_reload_actor($conn, $targetId);
if (!eca_actor_is_super_admin($restorer)) {
    $restorer = eca_hub_reload_actor($conn, 29);
}
$restoreSa = eca_assign_hub_roles($conn, $saId, [$roleMap['super_admin']], $restorer);
expect_true(!empty($restoreSa['ok']) || eca_actor_is_super_admin(eca_hub_reload_actor($conn, $saId)), 'remaining Super Admin can restore the other');

if ($hasStatus) {
    $deactivateLast = eca_set_hub_user_status($conn, $targetId, 'INACTIVE', eca_hub_reload_actor($conn, $saId));
    expect_true(!empty($deactivateLast['ok']), 'can deactivate a Super Admin when another remains');
    $reactivate = eca_set_hub_user_status($conn, $targetId, 'ACTIVE', eca_hub_reload_actor($conn, $saId));
    expect_true(!empty($reactivate['ok']), 'can reactivate a Super Admin');
    eca_set_hub_user_status($conn, $targetId, 'INACTIVE', eca_hub_reload_actor($conn, $saId));
    $blockLast = eca_set_hub_user_status($conn, $saId, 'INACTIVE', eca_hub_reload_actor($conn, $saId));
    if (eca_is_final_active_super_admin($conn, $saId)) {
        expect_true(empty($blockLast['ok']), 'cannot deactivate the final active Super Admin');
        expect_true(str_contains((string) ($blockLast['message'] ?? ''), 'final'), 'final Super Admin deactivate message is explicit');
    } else {
        expect_true(eca_count_active_super_admins($conn, $saId) >= 1, 'another Super Admin exists so temp Super Admin is not final');
        if (!empty($blockLast['ok'])) {
            $statusActor = eca_hub_reload_actor($conn, 29) ?: eca_hub_reload_actor($conn, $saId);
            if ($statusActor) {
                eca_set_hub_user_status($conn, $saId, 'ACTIVE', $statusActor);
            }
        }
    }
}

$permDenied = eca_save_role_permissions($conn, $roleMap['admin'], ['users.manage', 'members.manage'], $adminActor);
expect_true(empty($permDenied['ok']), 'admin cannot change role permissions');

$ids = [$adminId, $saId, $targetId];
$conn->prepare('DELETE FROM user_roles WHERE user_id IN (' . implode(',', array_map('intval', $ids)) . ')')->execute();
$conn->prepare('DELETE FROM users WHERE id IN (' . implode(',', array_map('intval', $ids)) . ')')->execute();

$adminStill = $conn->prepare('SELECT role FROM users WHERE email = ? LIMIT 1');
$adminStill->execute(['admin@eca.co.sz']);
expect_true((string) $adminStill->fetchColumn() === 'admin', 'admin@eca.co.sz remains ADMIN');

echo "Passed={$pass} Failed={$fail}\n";
exit($fail > 0 ? 1 : 0);
