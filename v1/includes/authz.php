<?php

require_once __DIR__ . '/session.php';
require_once __DIR__ . '/http.php';

function eca_normalize_role(string $role): string
{
    $role = strtolower(trim($role));
    $role = str_replace([' ', '-'], '_', $role);
    $map = [
        'supperadmin' => 'super_admin',
        'superadmin' => 'super_admin',
        'super_admin' => 'super_admin',
        'administrator' => 'admin',
        'admin' => 'admin',
        'membership_officer' => 'membership_officer',
        'finance_officer' => 'finance_officer',
        'content_manager' => 'content_manager',
        'training_officer' => 'training_officer',
        'officer' => 'training_officer',
        'contractor' => 'member',
        'member' => 'member',
        'public' => 'public',
    ];
    return $map[$role] ?? ($role !== '' ? $role : 'public');
}

require_once __DIR__ . '/rbac.php';

function eca_role_permissions(string $role): array
{
    return eca_rbac_permissions_for_role($role);
}

function eca_actor_roles(): array
{
    $roles = ['public'];
    if (!empty($_SESSION['eca_admin']['role'])) {
        $roles[] = eca_normalize_role((string) $_SESSION['eca_admin']['role']);
    } elseif (!empty($_SESSION['eca_admin'])) {
        $roles[] = 'admin';
    }
    if (!empty($_SESSION['eca_member'])) {
        $roles[] = 'member';
    }
    if (!empty($_SESSION['role'])) {
        $rawRole = (string) $_SESSION['role'];
        if (!eca_is_cpd_staff_role($rawRole) && !eca_is_cpd_learner_role($rawRole)) {
            $roles[] = eca_normalize_role($rawRole);
        }
    }
    return array_values(array_unique($roles));
}

function eca_can(string $permission, ?string $role = null): bool
{
    $permission = strtolower(trim($permission));
    $usersOnly = str_starts_with($permission, 'users.');

    if ($role !== null) {
        $norm = eca_normalize_role($role);
        if ($usersOnly && $norm !== 'super_admin') {
            return false;
        }
        return eca_permission_granted(eca_role_permissions($role), $permission);
    }

    $roles = array_map('eca_normalize_role', eca_actor_roles());
    if ($usersOnly && !in_array('super_admin', $roles, true)) {
        return false;
    }
    foreach ($roles as $actorRole) {
        if (eca_permission_granted(eca_role_permissions($actorRole), $permission)) {
            return true;
        }
    }
    return false;
}

function eca_require_role($roles): void
{
    $needed = is_array($roles) ? $roles : [$roles];
    $needed = array_map('eca_normalize_role', $needed);
    $have = array_map('eca_normalize_role', eca_actor_roles());
    foreach ($needed as $role) {
        if (in_array($role, $have, true)) {
            return;
        }
    }
    $wantsJson = isset($_SERVER['HTTP_ACCEPT']) && str_contains((string) $_SERVER['HTTP_ACCEPT'], 'application/json');
    if ($wantsJson || (isset($_SERVER['SCRIPT_NAME']) && str_ends_with((string) $_SERVER['SCRIPT_NAME'], '-suggest.php'))) {
        eca_json_error('Access denied.', 403);
    }
    eca_forbid();
}

function eca_require_permission(string $permission): void
{
    if (eca_can($permission)) {
        return;
    }
    $wantsJson = isset($_SERVER['HTTP_ACCEPT']) && str_contains((string) $_SERVER['HTTP_ACCEPT'], 'application/json');
    if ($wantsJson) {
        eca_json_error('Access denied.', 403);
    }
    eca_forbid();
}

function eca_require_super_admin(): void
{
    require_once __DIR__ . '/session.php';
    if (session_status() !== PHP_SESSION_ACTIVE) {
        eca_session_start();
    }
    $hubRole = eca_normalize_role((string) ($_SESSION['eca_admin']['role'] ?? ''));
    $cpdRole = strtoupper(trim((string) ($_SESSION['role'] ?? '')));
    $isSuper = $hubRole === 'super_admin'
        || $cpdRole === 'SUPPERADMIN'
        || (function_exists('eca_hub_can_open_portals') && eca_hub_can_open_portals());
    if ($isSuper) {
        return;
    }
    $wantsJson = isset($_SERVER['HTTP_ACCEPT']) && str_contains((string) $_SERVER['HTTP_ACCEPT'], 'application/json');
    if ($wantsJson) {
        eca_json_error('Access denied.', 403);
    }
    eca_forbid('Only a Super Admin can open this page.');
}
