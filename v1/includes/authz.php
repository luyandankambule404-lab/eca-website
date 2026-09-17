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

function eca_role_permissions(string $role): array
{
    $role = eca_normalize_role($role);
    $base = ['public.view'];
    if ($role === 'member') {
        return array_merge($base, ['member.portal', 'member.profile']);
    }
    $admin = array_merge($base, [
        'member.portal',
        'hub.access',
        'hub.companies',
        'admin.access',
    ]);
    if (in_array($role, ['admin', 'super_admin'], true)) {
        return array_merge($admin, ['hub.manage', 'hub.settings', 'audit.view']);
    }
    if (in_array($role, ['membership_officer', 'finance_officer', 'content_manager', 'training_officer'], true)) {
        return $admin;
    }
    return $base;
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
        $roles[] = eca_normalize_role((string) $_SESSION['role']);
    }
    return array_values(array_unique($roles));
}

function eca_can(string $permission, ?string $role = null): bool
{
    if ($role !== null) {
        return in_array($permission, eca_role_permissions($role), true);
    }
    foreach (eca_actor_roles() as $actorRole) {
        if (in_array($permission, eca_role_permissions($actorRole), true)) {
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
