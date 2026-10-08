<?php

require_once __DIR__ . '/env.php';

function eca_rbac_pdo(): ?PDO
{
    static $conn = false;
    if ($conn !== false) {
        return $conn;
    }
    if (!class_exists('Database')) {
        $config = dirname(__DIR__) . DIRECTORY_SEPARATOR . 'config.php';
        if (is_file($config)) {
            require_once $config;
        }
    }
    if (!class_exists('Database')) {
        $conn = null;
        return null;
    }
    try {
        $db = new Database();
        $pdo = $db->getConnection(false);
        $conn = $pdo instanceof PDO ? $pdo : null;
    } catch (Throwable $e) {
        $conn = null;
    }
    return $conn;
}

function eca_rbac_is_local_database(?PDO $conn = null): bool
{
    $host = strtolower(trim(eca_env('ECA_DB_HOST', '127.0.0.1')));
    $name = strtolower(trim(eca_env('ECA_DB_NAME', 'eca_local')));
    if (!in_array($host, ['127.0.0.1', 'localhost', '::1'], true) || $name !== 'eca_local') {
        return false;
    }
    $conn = $conn ?: eca_rbac_pdo();
    if (!$conn) {
        return false;
    }
    try {
        $schema = strtolower((string) $conn->query('SELECT DATABASE()')->fetchColumn());
        return $schema === 'eca_local';
    } catch (Throwable $e) {
        return false;
    }
}

function eca_hub_users_has_column(PDO $conn, string $column): bool
{
    static $cache = [];
    $key = strtolower($column);
    if (array_key_exists($key, $cache)) {
        return $cache[$key];
    }
    try {
        $stmt = $conn->prepare(
            'SELECT COUNT(*) FROM information_schema.COLUMNS
             WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND COLUMN_NAME = ?'
        );
        $stmt->execute(['users', $column]);
        $cache[$key] = (int) $stmt->fetchColumn() > 0;
    } catch (Throwable $e) {
        $cache[$key] = false;
    }
    return $cache[$key];
}

function eca_permission_catalog(): array
{
    return [
        'public.view',
        'member.portal',
        'member.profile',
        'hub.access',
        'admin.access',
        'hub.manage',
        'hub.settings',
        'hub.companies',
        'applications.view',
        'applications.review',
        'applications.approve',
        'applications.reject',
        'applications.manage',
        'members.view',
        'members.create',
        'members.edit',
        'members.delete',
        'members.manage',
        'companies.manage',
        'certificates.manage',
        'payments.manage',
        'documents.manage',
        'content.manage',
        'education.manage',
        'wellness.view',
        'wellness.manage',
        'tickets.manage',
        'cpd.view',
        'cpd.manage',
        'audit.view',
        'users.view',
        'users.create',
        'users.edit',
        'users.activate',
        'users.deactivate',
        'users.reset_password',
        'users.manage',
        'users.promote_super_admin',
        'roles.view',
        'roles.manage',
        'permissions.view',
        'permissions.manage',
        'settings.view',
        'settings.manage',
        'security.view',
        'security.manage',
        'reports.view',
        'reports.export',
    ];
}

function eca_super_admin_floor_permissions(): array
{
    return [
        'public.view',
        'hub.access',
        'admin.access',
        'hub.settings',
        'users.view',
        'users.create',
        'users.edit',
        'users.activate',
        'users.deactivate',
        'users.reset_password',
        'users.manage',
        'users.promote_super_admin',
        'roles.view',
        'roles.manage',
        'permissions.view',
        'permissions.manage',
        'settings.view',
        'settings.manage',
        'security.view',
        'security.manage',
        'audit.view',
    ];
}

function eca_super_admin_only_permissions(): array
{
    return [
        'hub.settings',
        'users.view',
        'users.create',
        'users.edit',
        'users.activate',
        'users.deactivate',
        'users.reset_password',
        'users.manage',
        'users.promote_super_admin',
        'roles.view',
        'roles.manage',
        'permissions.view',
        'permissions.manage',
        'settings.view',
        'settings.manage',
        'security.view',
        'security.manage',
    ];
}

function eca_security_audit_actions(): array
{
    return [
        'admin.login',
        'admin.logout',
        'admin.login.failed',
        'access.denied',
        'csrf.rejected',
        'privilege.escalation.denied',
        'document.access.denied',
        'user.created',
        'user.roles',
        'user.promoted',
        'user.demoted',
        'user.activated',
        'user.deactivated',
        'user.status',
        'user.password_reset',
        'user.edited',
        'permissions.changed',
        'roles.changed',
        'settings.changed',
    ];
}

function eca_default_role_permissions(string $role): array
{
    $role = eca_normalize_role($role);
    $base = ['public.view'];
    if ($role === 'member') {
        return array_values(array_unique(array_merge($base, [
            'member.portal',
            'member.profile',
            'wellness.view',
        ])));
    }

    $hub = array_merge($base, ['hub.access']);
    $adminOps = [
        'admin.access',
        'hub.manage',
        'hub.companies',
        'applications.view',
        'applications.review',
        'applications.approve',
        'applications.reject',
        'applications.manage',
        'members.view',
        'members.create',
        'members.edit',
        'members.delete',
        'members.manage',
        'companies.manage',
        'certificates.manage',
        'payments.manage',
        'documents.manage',
        'content.manage',
        'education.manage',
        'wellness.view',
        'wellness.manage',
        'tickets.manage',
        'cpd.view',
        'audit.view',
        'reports.view',
        'reports.export',
    ];

    if ($role === 'super_admin') {
        return array_values(array_unique(array_merge(
            $hub,
            $adminOps,
            eca_permission_catalog(),
            eca_super_admin_floor_permissions()
        )));
    }
    if ($role === 'admin') {
        return array_values(array_unique(array_merge($hub, $adminOps)));
    }
    if ($role === 'membership_officer') {
        return array_values(array_unique(array_merge($hub, [
            'admin.access',
            'hub.companies',
            'applications.view',
            'applications.review',
            'applications.approve',
            'applications.reject',
            'applications.manage',
            'members.view',
            'members.create',
            'members.edit',
            'members.manage',
            'companies.manage',
            'certificates.manage',
            'documents.manage',
            'tickets.manage',
        ])));
    }
    if ($role === 'finance_officer') {
        return array_values(array_unique(array_merge($hub, [
            'admin.access',
            'members.view',
            'members.manage',
            'payments.manage',
            'reports.view',
        ])));
    }
    if ($role === 'content_manager') {
        return array_values(array_unique(array_merge($hub, [
            'admin.access',
            'content.manage',
            'education.manage',
            'wellness.view',
            'wellness.manage',
        ])));
    }
    if ($role === 'training_officer') {
        return array_values(array_unique(array_merge($hub, [
            'admin.access',
            'education.manage',
            'cpd.view',
            'cpd.manage',
        ])));
    }
    return $base;
}

function eca_permission_granted(array $have, string $need): bool
{
    $need = strtolower(trim($need));
    if ($need === '') {
        return false;
    }
    $have = array_map('strval', $have);
    if (in_array($need, $have, true)) {
        return true;
    }
    $parts = explode('.', $need, 2);
    if (count($parts) !== 2) {
        return false;
    }
    [$module, $action] = $parts;
    if ($module === '' || $action === '') {
        return false;
    }
    if (!in_array($module . '.manage', $have, true)) {
        return false;
    }
    return in_array($action, [
        'view',
        'create',
        'edit',
        'delete',
        'review',
        'approve',
        'reject',
        'export',
        'activate',
        'deactivate',
        'reset_password',
    ], true);
}

function eca_rbac_load_stored_permissions(): array
{
    static $loaded = null;
    if ($loaded !== null) {
        return $loaded;
    }
    $loaded = [];
    $conn = eca_rbac_pdo();
    if (!$conn) {
        return $loaded;
    }
    try {
        $rows = $conn->query(
            'SELECT r.slug, rp.permission
             FROM role_permissions rp
             INNER JOIN roles r ON r.id = rp.role_id'
        )->fetchAll(PDO::FETCH_ASSOC);
        foreach ($rows as $row) {
            $slug = eca_normalize_role((string) ($row['slug'] ?? ''));
            $permission = strtolower(trim((string) ($row['permission'] ?? '')));
            if ($slug === '' || $permission === '') {
                continue;
            }
            $loaded[$slug][] = $permission;
        }
    } catch (Throwable $e) {
        $loaded = [];
    }
    return $loaded;
}

function eca_rbac_permissions_for_role(string $role): array
{
    $role = eca_normalize_role($role);
    $defaults = eca_default_role_permissions($role);
    $stored = eca_rbac_load_stored_permissions();
    $permissions = $defaults;
    if (!empty($stored[$role])) {
        $permissions = array_values(array_unique(array_map('strval', $stored[$role])));
    }
    if ($role === 'super_admin') {
        $permissions = array_values(array_unique(array_merge(
            $permissions,
            $defaults,
            eca_super_admin_floor_permissions()
        )));
    } else {
        $blocked = eca_super_admin_only_permissions();
        $stripBlocked = static function (array $list) use ($blocked): array {
            return array_values(array_filter(
                $list,
                static fn (string $permission): bool => !in_array($permission, $blocked, true)
                    && !str_starts_with(strtolower(trim($permission)), 'users.')
            ));
        };
        $permissions = $stripBlocked($permissions);
        if ($permissions === []) {
            $permissions = $stripBlocked($defaults);
        }
    }
    return $permissions;
}

function eca_rbac_safe_meta(array $meta): array
{
    $blocked = [
        'password',
        'passwd',
        'password_hash',
        'hash',
        'token',
        'remember_token',
        'secret',
        'api_key',
        'apikey',
        'jwt',
        'smtp',
        'session',
        'csrf',
        'cookie',
        'authorization',
        'db_pass',
        'db_password',
    ];
    $clean = [];
    foreach ($meta as $key => $value) {
        $name = strtolower((string) $key);
        foreach ($blocked as $needle) {
            if (str_contains($name, $needle)) {
                continue 2;
            }
        }
        if (is_array($value)) {
            $clean[$key] = eca_rbac_safe_meta($value);
            continue;
        }
        if (is_scalar($value) || $value === null) {
            $clean[$key] = $value;
        }
    }
    return $clean;
}

function eca_hub_role_rows(PDO $conn): array
{
    return $conn->query('SELECT id, slug, label FROM roles ORDER BY id')->fetchAll(PDO::FETCH_ASSOC);
}

function eca_hub_user_role_slugs(PDO $conn, int $userId): array
{
    $stmt = $conn->prepare(
        'SELECT r.slug
         FROM user_roles ur
         INNER JOIN roles r ON r.id = ur.role_id
         WHERE ur.user_id = ?'
    );
    $stmt->execute([$userId]);
    $slugs = [];
    foreach ($stmt->fetchAll(PDO::FETCH_COLUMN) as $slug) {
        $normalized = eca_normalize_role((string) $slug);
        if ($normalized !== '' && $normalized !== 'public') {
            $slugs[] = $normalized;
        }
    }
    return array_values(array_unique($slugs));
}

function eca_hub_primary_role_from_slugs(array $slugs, string $fallback = 'public'): string
{
    $normalized = [];
    foreach ($slugs as $slug) {
        $role = eca_normalize_role((string) $slug);
        if ($role !== '') {
            $normalized[] = $role;
        }
    }
    if (in_array('super_admin', $normalized, true)) {
        return 'super_admin';
    }
    if (in_array('admin', $normalized, true)) {
        return 'admin';
    }
    if ($normalized) {
        return (string) reset($normalized);
    }
    return eca_normalize_role($fallback) ?: 'public';
}

function eca_hub_resolve_user_role(PDO $conn, int $userId, string $fallback = 'public'): string
{
    $slugs = eca_hub_user_role_slugs($conn, $userId);
    return eca_hub_primary_role_from_slugs($slugs, $fallback);
}

function eca_hub_user_is_active(array $row): bool
{
    $status = strtoupper(trim((string) ($row['status'] ?? 'ACTIVE')));
    return $status === '' || $status === 'ACTIVE';
}

function eca_count_active_super_admins(PDO $conn, int $exceptUserId = 0): int
{
    $sql = 'SELECT COUNT(DISTINCT u.id)
            FROM users u
            LEFT JOIN user_roles ur ON ur.user_id = u.id
            LEFT JOIN roles r ON r.id = ur.role_id
            WHERE u.id <> ?
              AND (
                LOWER(u.role) IN (\'super_admin\', \'superadmin\', \'supperadmin\')
                OR r.slug = \'super_admin\'
              )';
    $params = [$exceptUserId];
    if (eca_hub_users_has_column($conn, 'status')) {
        $sql .= " AND (u.status IS NULL OR UPPER(u.status) = 'ACTIVE')";
    }
    $stmt = $conn->prepare($sql);
    $stmt->execute($params);
    return (int) $stmt->fetchColumn();
}

function eca_hub_reload_actor(PDO $conn, int $actorId): ?array
{
    if ($actorId <= 0) {
        return null;
    }
    $columns = 'id, name, email, role';
    if (eca_hub_users_has_column($conn, 'status')) {
        $columns .= ', status';
    }
    $stmt = $conn->prepare("SELECT $columns FROM users WHERE id = ? LIMIT 1");
    $stmt->execute([$actorId]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$row) {
        return null;
    }
    if (!eca_hub_user_is_active($row)) {
        return null;
    }
    $role = eca_hub_resolve_user_role($conn, $actorId, (string) ($row['role'] ?? 'public'));
    $row['role'] = $role;
    return $row;
}

function eca_actor_is_super_admin(?array $actor): bool
{
    return eca_normalize_role((string) ($actor['role'] ?? '')) === 'super_admin';
}

function eca_is_final_active_super_admin(PDO $conn, int $userId, ?array $user = null): bool
{
    if ($userId < 1) {
        return false;
    }
    if ($user === null) {
        $columns = 'id, role';
        if (eca_hub_users_has_column($conn, 'status')) {
            $columns .= ', status';
        }
        $stmt = $conn->prepare("SELECT $columns FROM users WHERE id = ? LIMIT 1");
        $stmt->execute([$userId]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
    }
    if (!$user || !eca_hub_user_is_active($user)) {
        return false;
    }
    $role = eca_hub_resolve_user_role($conn, $userId, (string) ($user['role'] ?? 'public'));
    if (eca_normalize_role($role) !== 'super_admin') {
        return false;
    }
    return eca_count_active_super_admins($conn, $userId) < 1;
}

function eca_assign_hub_roles(PDO $conn, int $targetUserId, array $roleIds, array $sessionActor): array
{
    $denied = static fn (string $message): array => ['ok' => false, 'message' => $message];

    $actor = eca_hub_reload_actor($conn, (int) ($sessionActor['id'] ?? 0));
    if (!$actor) {
        return $denied('Your session is no longer authorized.');
    }
    $actorRole = eca_normalize_role((string) ($actor['role'] ?? ''));

    $userStmt = $conn->prepare('SELECT * FROM users WHERE id = ? LIMIT 1');
    $userStmt->execute([$targetUserId]);
    $target = $userStmt->fetch(PDO::FETCH_ASSOC);
    if (!$target) {
        return $denied('That user was not found.');
    }

    $roles = eca_hub_role_rows($conn);
    $valid = [];
    foreach ($roles as $role) {
        $roleId = (int) ($role['id'] ?? 0);
        $slug = eca_normalize_role((string) ($role['slug'] ?? ''));
        if ($roleId > 0 && $slug !== '' && $slug !== 'public') {
            $valid[$roleId] = $slug;
        }
    }

    $selectedIds = [];
    $selectedSlugs = [];
    foreach ($roleIds as $roleId) {
        $roleId = (int) $roleId;
        if (!isset($valid[$roleId])) {
            continue;
        }
        $selectedIds[] = $roleId;
        $selectedSlugs[] = $valid[$roleId];
    }
    $selectedIds = array_values(array_unique($selectedIds));
    $selectedSlugs = array_values(array_unique($selectedSlugs));

    $wantsSuperAdmin = in_array('super_admin', $selectedSlugs, true);
    $currentSlugs = eca_hub_user_role_slugs($conn, $targetUserId);
    if ($currentSlugs === []) {
        $currentSlugs = [eca_normalize_role((string) ($target['role'] ?? 'public'))];
    }
    $targetIsSuperAdmin = in_array('super_admin', $currentSlugs, true)
        || eca_normalize_role((string) ($target['role'] ?? '')) === 'super_admin';
    $targetIsAdmin = in_array('admin', $currentSlugs, true)
        || eca_normalize_role((string) ($target['role'] ?? '')) === 'admin'
        || $targetIsSuperAdmin;

    if ($wantsSuperAdmin && !$targetIsSuperAdmin) {
        if (
            !eca_actor_is_super_admin($actor)
            || !eca_can('users.promote_super_admin', $actorRole)
        ) {
            if (function_exists('eca_audit_security_event')) {
                eca_audit_security_event('privilege.escalation.denied', 'users', (string) $targetUserId, [
                    'attempt' => 'promote_super_admin',
                    'actor_role' => $actorRole,
                    'target_email' => (string) ($target['email'] ?? ''),
                ]);
            }
            return $denied('Only a Super Admin can promote a user to Super Admin.');
        }
    }
    if ($wantsSuperAdmin && !eca_actor_is_super_admin($actor)) {
        if (function_exists('eca_audit_security_event')) {
            eca_audit_security_event('privilege.escalation.denied', 'users', (string) $targetUserId, [
                'attempt' => 'assign_super_admin',
                'actor_role' => $actorRole,
                'target_email' => (string) ($target['email'] ?? ''),
            ]);
        }
        return $denied('Only a Super Admin can assign the Super Admin role.');
    }
    if (!eca_can('users.manage', $actorRole)) {
        if (function_exists('eca_audit_security_event')) {
            eca_audit_security_event('privilege.escalation.denied', 'users', (string) $targetUserId, [
                'attempt' => 'change_roles',
                'actor_role' => $actorRole,
            ]);
        }
        return $denied('You are not allowed to change user roles.');
    }

    if ($targetIsAdmin && !eca_actor_is_super_admin($actor) && !in_array('admin', $selectedSlugs, true) && !$wantsSuperAdmin) {
        if (function_exists('eca_audit_security_event')) {
            eca_audit_security_event('privilege.escalation.denied', 'users', (string) $targetUserId, [
                'attempt' => 'demote_admin',
                'actor_role' => $actorRole,
            ]);
        }
        return $denied('Only a Super Admin can demote an administrator.');
    }

    if ($targetIsSuperAdmin && !$wantsSuperAdmin) {
        if (!eca_actor_is_super_admin($actor)) {
            if (function_exists('eca_audit_security_event')) {
                eca_audit_security_event('privilege.escalation.denied', 'users', (string) $targetUserId, [
                    'attempt' => 'demote_super_admin',
                    'actor_role' => $actorRole,
                ]);
            }
            return $denied('Only a Super Admin can demote a Super Admin.');
        }
        if (eca_count_active_super_admins($conn, $targetUserId) < 1) {
            return $denied('The final Super Admin account cannot be demoted.');
        }
    }

    $primary = eca_hub_primary_role_from_slugs($selectedSlugs, 'public');
    $previousPrimary = eca_normalize_role((string) ($target['role'] ?? 'public'));

    $conn->beginTransaction();
    try {
        $conn->prepare('DELETE FROM user_roles WHERE user_id = ?')->execute([$targetUserId]);
        if ($selectedIds) {
            $insert = $conn->prepare('INSERT INTO user_roles (user_id, role_id) VALUES (?, ?)');
            foreach ($selectedIds as $roleId) {
                $insert->execute([$targetUserId, $roleId]);
            }
        }
        $conn->prepare('UPDATE users SET role = ? WHERE id = ?')->execute([$primary, $targetUserId]);
        if (eca_hub_users_has_column($conn, 'is_admin')) {
            $isAdmin = in_array($primary, ['admin', 'super_admin'], true) ? 1 : 0;
            $conn->prepare('UPDATE users SET is_admin = ? WHERE id = ?')->execute([$isAdmin, $targetUserId]);
        }
        $conn->commit();
    } catch (Throwable $e) {
        $conn->rollBack();
        return $denied('Roles could not be saved.');
    }

    $meta = eca_rbac_safe_meta([
        'target_email' => (string) ($target['email'] ?? ''),
        'from_role' => $previousPrimary,
        'to_role' => $primary,
        'roles' => $selectedSlugs,
        'result' => 'ok',
    ]);
    if (function_exists('eca_audit')) {
        eca_audit('user.roles', 'users', (string) $targetUserId, $meta);
        if (!$targetIsSuperAdmin && $wantsSuperAdmin) {
            eca_audit('user.promoted', 'users', (string) $targetUserId, $meta);
        }
        if ($targetIsSuperAdmin && !$wantsSuperAdmin) {
            eca_audit('user.demoted', 'users', (string) $targetUserId, $meta);
        } elseif ($targetIsAdmin && $previousPrimary === 'admin' && $primary !== 'admin' && $primary !== 'super_admin') {
            eca_audit('user.demoted', 'users', (string) $targetUserId, $meta);
        }
    }

    return [
        'ok' => true,
        'message' => 'Roles saved.',
        'primary' => $primary,
    ];
}

function eca_set_hub_user_status(PDO $conn, int $targetUserId, string $status, array $sessionActor): array
{
    $denied = static fn (string $message): array => ['ok' => false, 'message' => $message];
    $status = strtoupper(trim($status));
    if (!in_array($status, ['ACTIVE', 'INACTIVE'], true)) {
        return $denied('Invalid account status.');
    }
    if (!eca_hub_users_has_column($conn, 'status')) {
        return $denied('Account status is not available yet.');
    }

    $actor = eca_hub_reload_actor($conn, (int) ($sessionActor['id'] ?? 0));
    if (!$actor) {
        return $denied('Your session is no longer authorized.');
    }
    $actorRole = eca_normalize_role((string) ($actor['role'] ?? ''));
    if (!eca_actor_is_super_admin($actor)) {
        return $denied('Only a Super Admin can change Hub account status.');
    }
    $needed = $status === 'ACTIVE' ? 'users.activate' : 'users.deactivate';
    if (!eca_can($needed, $actorRole) && !eca_can('users.manage', $actorRole)) {
        return $denied('You are not allowed to change this account status.');
    }

    $stmt = $conn->prepare('SELECT * FROM users WHERE id = ? LIMIT 1');
    $stmt->execute([$targetUserId]);
    $target = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$target) {
        return $denied('That user was not found.');
    }

    $targetRole = eca_hub_resolve_user_role($conn, $targetUserId, (string) ($target['role'] ?? 'public'));
    if ($status === 'INACTIVE' && $targetRole === 'super_admin' && eca_count_active_super_admins($conn, $targetUserId) < 1) {
        return $denied('The final active Super Admin account cannot be deactivated.');
    }
    if ($status === 'INACTIVE' && (int) ($actor['id'] ?? 0) === $targetUserId) {
        return $denied('You cannot deactivate your own account. Ask another Super Admin.');
    }

    try {
        $conn->prepare('UPDATE users SET status = ? WHERE id = ?')->execute([$status, $targetUserId]);
    } catch (Throwable $e) {
        return $denied('Account status could not be updated.');
    }

    $action = $status === 'ACTIVE' ? 'user.activated' : 'user.deactivated';
    if (function_exists('eca_audit')) {
        eca_audit($action, 'users', (string) $targetUserId, eca_rbac_safe_meta([
            'target_email' => (string) ($target['email'] ?? ''),
            'status' => $status,
            'result' => 'ok',
        ]));
    }

    return [
        'ok' => true,
        'message' => $status === 'ACTIVE' ? 'User activated.' : 'User deactivated.',
        'status' => $status,
    ];
}

function eca_save_role_permissions(PDO $conn, int $roleId, array $permissions, array $sessionActor): array
{
    $denied = static fn (string $message): array => ['ok' => false, 'message' => $message];
    $actor = eca_hub_reload_actor($conn, (int) ($sessionActor['id'] ?? 0));
    if (!$actor || !eca_actor_is_super_admin($actor)) {
        return $denied('Only a Super Admin can change role permissions.');
    }
    $actorRole = eca_normalize_role((string) ($actor['role'] ?? ''));
    if (!eca_can('permissions.manage', $actorRole) && !eca_can('roles.manage', $actorRole)) {
        return $denied('You are not allowed to change permissions.');
    }

    $roleStmt = $conn->prepare('SELECT id, slug, label FROM roles WHERE id = ? LIMIT 1');
    $roleStmt->execute([$roleId]);
    $role = $roleStmt->fetch(PDO::FETCH_ASSOC);
    if (!$role) {
        return $denied('That role was not found.');
    }
    $slug = eca_normalize_role((string) ($role['slug'] ?? ''));
    $catalog = eca_permission_catalog();
    $selected = [];
    foreach ($permissions as $permission) {
        $permission = strtolower(trim((string) $permission));
        if (in_array($permission, $catalog, true)) {
            $selected[] = $permission;
        }
    }
    $selected = array_values(array_unique($selected));

    if ($slug === 'super_admin') {
        $selected = array_values(array_unique(array_merge($selected, eca_super_admin_floor_permissions())));
    } else {
        $blocked = eca_super_admin_only_permissions();
        $selected = array_values(array_filter(
            $selected,
            static fn (string $permission): bool => !in_array($permission, $blocked, true)
        ));
    }

    $conn->beginTransaction();
    try {
        if (!eca_hub_has_role_permissions_table($conn)) {
            $conn->rollBack();
            return $denied(
                'Permissions could not be saved: role_permissions table is not present locally. '
                . 'Apply existing local migration v1/sql/007_role_permissions.sql (or tools/apply-007-local.php) after explicit approval. '
                . 'Until then, Hub uses code default permissions.'
            );
        }
        $conn->prepare('DELETE FROM role_permissions WHERE role_id = ?')->execute([$roleId]);
        if ($selected) {
            $insert = $conn->prepare('INSERT INTO role_permissions (role_id, permission) VALUES (?, ?)');
            foreach ($selected as $permission) {
                $insert->execute([$roleId, $permission]);
            }
        }
        $conn->commit();
    } catch (Throwable $e) {
        $conn->rollBack();
        return $denied('Permissions could not be saved.');
    }

    if (function_exists('eca_audit')) {
        eca_audit('permissions.changed', 'roles', (string) $roleId, eca_rbac_safe_meta([
            'role' => $slug,
            'permissions' => $selected,
            'result' => 'ok',
        ]));
    }

    return ['ok' => true, 'message' => 'Permissions saved for ' . (string) ($role['label'] ?? $slug) . '.'];
}

function eca_seed_default_role_permissions(PDO $conn): void
{
    $roles = eca_hub_role_rows($conn);
    $insert = $conn->prepare(
        'INSERT IGNORE INTO role_permissions (role_id, permission) VALUES (?, ?)'
    );
    foreach ($roles as $role) {
        $roleId = (int) ($role['id'] ?? 0);
        $slug = eca_normalize_role((string) ($role['slug'] ?? ''));
        if ($roleId <= 0 || $slug === '') {
            continue;
        }
        foreach (eca_default_role_permissions($slug) as $permission) {
            if ($slug !== 'super_admin' && in_array($permission, eca_super_admin_only_permissions(), true)) {
                continue;
            }
            $insert->execute([$roleId, $permission]);
        }
    }
    eca_revoke_super_admin_only_from_other_roles($conn);
}

function eca_revoke_super_admin_only_from_other_roles(PDO $conn): void
{
    $blocked = eca_super_admin_only_permissions();
    if ($blocked === []) {
        return;
    }
    $marks = implode(',', array_fill(0, count($blocked), '?'));
    try {
        $stmt = $conn->prepare(
            "DELETE rp FROM role_permissions rp
             INNER JOIN roles r ON r.id = rp.role_id
             WHERE r.slug <> 'super_admin'
               AND rp.permission IN ($marks)"
        );
        $stmt->execute($blocked);
    } catch (Throwable $e) {
        // Local tables may not exist yet during first boot.
    }
}

function eca_permission_groups(): array
{
    $catalog = eca_permission_catalog();
    $groups = [
        'Dashboard & hub' => ['public.view', 'hub.access', 'admin.access', 'hub.manage', 'hub.companies', 'hub.settings'],
        'Members' => ['members.view', 'members.create', 'members.edit', 'members.delete', 'members.manage'],
        'Companies' => ['companies.manage'],
        'Applications' => [
            'applications.view', 'applications.review', 'applications.approve', 'applications.reject', 'applications.manage',
        ],
        'Payments' => ['payments.manage'],
        'Certificates & documents' => ['certificates.manage', 'documents.manage'],
        'CPD & education' => ['cpd.view', 'cpd.manage', 'education.manage'],
        'Wellness' => ['wellness.view', 'wellness.manage'],
        'Content & support' => ['content.manage', 'tickets.manage'],
        'Reports & audit' => ['reports.view', 'reports.export', 'audit.view'],
        'Users & roles' => [
            'users.view', 'users.create', 'users.edit', 'users.activate', 'users.deactivate',
            'users.reset_password', 'users.manage', 'users.promote_super_admin',
            'roles.view', 'roles.manage', 'permissions.view', 'permissions.manage',
        ],
        'Security & settings' => ['security.view', 'security.manage', 'settings.view', 'settings.manage'],
    ];
    $placed = [];
    foreach ($groups as $perms) {
        foreach ($perms as $p) {
            $placed[$p] = true;
        }
    }
    $other = [];
    foreach ($catalog as $permission) {
        if (!isset($placed[$permission])) {
            $other[] = $permission;
        }
    }
    if ($other) {
        $groups['Other'] = $other;
    }
    // Drop empty groups (permission not in catalog).
    foreach ($groups as $label => $perms) {
        $groups[$label] = array_values(array_filter(
            $perms,
            static fn (string $p): bool => in_array($p, $catalog, true)
        ));
        if (!$groups[$label]) {
            unset($groups[$label]);
        }
    }
    return $groups;
}

function eca_hub_has_role_permissions_table(PDO $conn): bool
{
    if (function_exists('eca_has_table')) {
        return eca_has_table($conn, 'role_permissions');
    }
    try {
        $stmt = $conn->query("SHOW TABLES LIKE 'role_permissions'");
        return $stmt && (bool) $stmt->fetchColumn();
    } catch (Throwable $e) {
        return false;
    }
}

function eca_hub_officer_role_slugs(): array
{
    return ['admin', 'super_admin', 'membership_officer', 'finance_officer', 'content_manager', 'training_officer'];
}

function eca_hub_last_logins(PDO $conn, array $userIds): array
{
    $ids = [];
    foreach ($userIds as $id) {
        $id = (int) $id;
        if ($id > 0) {
            $ids[] = $id;
        }
    }
    $ids = array_values(array_unique($ids));
    if (!$ids) {
        return [];
    }
    $placeholders = implode(',', array_fill(0, count($ids), '?'));
    $params = array_map('strval', $ids);
    try {
        $stmt = $conn->prepare(
            "SELECT entity_id, MAX(created_at) AS last_login
             FROM audit_logs
             WHERE action = 'admin.login' AND entity_id IN ($placeholders)
             GROUP BY entity_id"
        );
        $stmt->execute($params);
        $out = [];
        foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
            $out[(string) ($row['entity_id'] ?? '')] = (string) ($row['last_login'] ?? '');
        }
        return $out;
    } catch (Throwable $e) {
        return [];
    }
}

function eca_create_hub_user(PDO $conn, array $input, array $sessionActor): array
{
    $denied = static fn (string $message): array => ['ok' => false, 'message' => $message];
    $actor = eca_hub_reload_actor($conn, (int) ($sessionActor['id'] ?? 0));
    if (!$actor) {
        return $denied('Your session is no longer authorized.');
    }
    $actorRole = eca_normalize_role((string) ($actor['role'] ?? ''));
    if (!eca_can('users.create', $actorRole) && !eca_can('users.manage', $actorRole)) {
        return $denied('You are not allowed to create users.');
    }
    if (!eca_actor_is_super_admin($actor)) {
        return $denied('Only a Super Admin can create administrative users.');
    }

    $name = trim((string) ($input['name'] ?? ''));
    $email = strtolower(trim((string) ($input['email'] ?? '')));
    $password = (string) ($input['password'] ?? '');
    $roleSlug = eca_normalize_role((string) ($input['role'] ?? ''));
    $status = strtoupper(trim((string) ($input['status'] ?? 'ACTIVE')));
    if ($status !== 'INACTIVE') {
        $status = 'ACTIVE';
    }

    if ($name === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        return $denied('Enter a valid name and email address.');
    }
    if (strlen($password) < 12) {
        return $denied('Password must be at least 12 characters.');
    }
    if (!in_array($roleSlug, eca_hub_officer_role_slugs(), true)) {
        return $denied('Choose an administrative Hub role.');
    }
    if ($roleSlug === 'super_admin' && !eca_can('users.promote_super_admin', $actorRole)) {
        return $denied('Only a Super Admin can create another Super Admin.');
    }

    $exists = $conn->prepare('SELECT id FROM users WHERE LOWER(email) = LOWER(?) LIMIT 1');
    $exists->execute([$email]);
    if ($exists->fetchColumn()) {
        return $denied('A user with that email already exists.');
    }

    $roleStmt = $conn->prepare('SELECT id FROM roles WHERE slug = ? LIMIT 1');
    $roleStmt->execute([$roleSlug]);
    $roleId = (int) $roleStmt->fetchColumn();
    if ($roleId <= 0) {
        return $denied('That role is not available.');
    }

    $hash = password_hash($password, PASSWORD_DEFAULT);
    $hasStatus = eca_hub_users_has_column($conn, 'status');
    $isAdmin = in_array($roleSlug, ['admin', 'super_admin'], true) ? 1 : 0;

    $conn->beginTransaction();
    try {
        if ($hasStatus) {
            $sql = 'INSERT INTO users (name, email, password, role, status, is_admin, created_at, updated_at)
                    VALUES (?, ?, ?, ?, ?, ?, NOW(), NOW())';
            $conn->prepare($sql)->execute([$name, $email, $hash, $roleSlug, $status, $isAdmin]);
        } else {
            $sql = 'INSERT INTO users (name, email, password, role, is_admin, created_at, updated_at)
                    VALUES (?, ?, ?, ?, ?, NOW(), NOW())';
            $conn->prepare($sql)->execute([$name, $email, $hash, $roleSlug, $isAdmin]);
        }
        $userId = (int) $conn->lastInsertId();
        $conn->prepare('INSERT INTO user_roles (user_id, role_id) VALUES (?, ?)')->execute([$userId, $roleId]);
        $conn->commit();
    } catch (Throwable $e) {
        $conn->rollBack();
        return $denied('The user could not be created.');
    }

    if (function_exists('eca_audit')) {
        eca_audit('user.created', 'users', (string) $userId, eca_rbac_safe_meta([
            'email' => $email,
            'role' => $roleSlug,
            'status' => $status,
            'result' => 'ok',
        ]));
    }

    return ['ok' => true, 'message' => 'User created.', 'id' => $userId];
}

function eca_update_hub_user(PDO $conn, int $targetUserId, array $input, array $sessionActor): array
{
    $denied = static fn (string $message): array => ['ok' => false, 'message' => $message];
    $actor = eca_hub_reload_actor($conn, (int) ($sessionActor['id'] ?? 0));
    if (!$actor) {
        return $denied('Your session is no longer authorized.');
    }
    $actorRole = eca_normalize_role((string) ($actor['role'] ?? ''));
    if (!eca_actor_is_super_admin($actor)) {
        return $denied('Only a Super Admin can edit Hub users.');
    }
    if (!eca_can('users.edit', $actorRole) && !eca_can('users.manage', $actorRole)) {
        return $denied('You are not allowed to edit users.');
    }

    $stmt = $conn->prepare('SELECT id, email, role FROM users WHERE id = ? LIMIT 1');
    $stmt->execute([$targetUserId]);
    $target = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$target) {
        return $denied('That user was not found.');
    }

    $name = trim((string) ($input['name'] ?? ''));
    if ($name === '') {
        return $denied('Enter a name.');
    }

    try {
        $conn->prepare('UPDATE users SET name = ?, updated_at = NOW() WHERE id = ?')->execute([$name, $targetUserId]);
    } catch (Throwable $e) {
        return $denied('The user could not be updated.');
    }

    if (function_exists('eca_audit')) {
        eca_audit('user.edited', 'users', (string) $targetUserId, eca_rbac_safe_meta([
            'email' => (string) ($target['email'] ?? ''),
            'result' => 'ok',
        ]));
    }

    return ['ok' => true, 'message' => 'User updated.'];
}

function eca_reset_hub_user_password(PDO $conn, int $targetUserId, string $password, array $sessionActor): array
{
    $denied = static fn (string $message): array => ['ok' => false, 'message' => $message];
    $actor = eca_hub_reload_actor($conn, (int) ($sessionActor['id'] ?? 0));
    if (!$actor) {
        return $denied('Your session is no longer authorized.');
    }
    $actorRole = eca_normalize_role((string) ($actor['role'] ?? ''));
    if (!eca_can('users.reset_password', $actorRole) && !eca_can('users.manage', $actorRole)) {
        return $denied('You are not allowed to reset passwords.');
    }
    if (!eca_actor_is_super_admin($actor)) {
        return $denied('Only a Super Admin can reset Hub passwords.');
    }
    if (strlen($password) < 12) {
        return $denied('Password must be at least 12 characters.');
    }

    $stmt = $conn->prepare('SELECT id, email FROM users WHERE id = ? LIMIT 1');
    $stmt->execute([$targetUserId]);
    $target = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$target) {
        return $denied('That user was not found.');
    }

    $hash = password_hash($password, PASSWORD_DEFAULT);
    try {
        $conn->prepare('UPDATE users SET password = ?, updated_at = NOW() WHERE id = ?')->execute([$hash, $targetUserId]);
    } catch (Throwable $e) {
        return $denied('The password could not be reset.');
    }

    if (function_exists('eca_audit')) {
        eca_audit('user.password_reset', 'users', (string) $targetUserId, eca_rbac_safe_meta([
            'email' => (string) ($target['email'] ?? ''),
            'result' => 'ok',
        ]));
    }

    return ['ok' => true, 'message' => 'Password reset.'];
}

function eca_hub_allowed_setting_keys(): array
{
    return [
        'organization_name' => 'Organization name',
        'support_email' => 'Public support email',
        'membership_year_label' => 'Current membership year label',
        'application_fee' => 'Application fee note',
        'membership_fee' => 'Membership fee note',
        'renewal_fee' => 'Renewal fee note',
        'training_fee' => 'Training fee note',
        'event_fee' => 'Event fee note',
    ];
}

function eca_save_hub_settings(PDO $conn, array $posted, array $sessionActor): array
{
    $denied = static fn (string $message): array => ['ok' => false, 'message' => $message];
    $actor = eca_hub_reload_actor($conn, (int) ($sessionActor['id'] ?? 0));
    if (!$actor || !eca_actor_is_super_admin($actor)) {
        return $denied('Only a Super Admin can change system settings.');
    }
    $actorRole = eca_normalize_role((string) ($actor['role'] ?? ''));
    if (!eca_can('settings.manage', $actorRole) && !eca_can('hub.settings', $actorRole)) {
        return $denied('You are not allowed to change settings.');
    }

    $allowed = eca_hub_allowed_setting_keys();
    $saved = [];
    $stmt = $conn->prepare(
        'INSERT INTO settings (setting_key, setting_value) VALUES (?, ?)
         ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value)'
    );
    foreach ($allowed as $key => $label) {
        if (!array_key_exists($key, $posted)) {
            continue;
        }
        $value = trim((string) $posted[$key]);
        if ($key === 'support_email' && $value !== '' && !filter_var($value, FILTER_VALIDATE_EMAIL)) {
            return $denied('Enter a valid support email, or leave it blank.');
        }
        $stmt->execute([$key, $value]);
        $saved[] = $key;
    }
    if ($saved === []) {
        return $denied('No settings were changed.');
    }

    if (function_exists('eca_audit')) {
        eca_audit('settings.changed', 'settings', implode(',', $saved), eca_rbac_safe_meta([
            'keys' => $saved,
            'result' => 'ok',
        ]));
    }

    return ['ok' => true, 'message' => 'Settings saved.'];
}
