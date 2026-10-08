<?php
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/_hub.php';
require_once __DIR__ . '/../includes/audit.php';
eca_admin_require('roles.view');
header('Cache-Control: no-store');

$conn = eca_admin_db();
$actor = eca_admin_user() ?? [];
$canManage = eca_can('roles.manage') || eca_can('permissions.manage');
$notice = '';
$noticeError = false;
$hasPermTable = $conn && eca_hub_has_role_permissions_table($conn);

if ($conn && $_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!eca_admin_csrf_ok($_POST['csrf_token'] ?? null)) {
        $notice = 'Your session expired. Please try again.';
        $noticeError = true;
    } elseif (!$canManage) {
        eca_forbid('Only a Super Admin can change role permissions.');
    } else {
        $roleId = (int) ($_POST['role_id'] ?? 0);
        $result = eca_save_role_permissions(
            $conn,
            $roleId,
            (array) ($_POST['permissions'] ?? []),
            $actor
        );
        $notice = (string) ($result['message'] ?? 'Permissions could not be saved.');
        $noticeError = empty($result['ok']);
        $hasPermTable = eca_hub_has_role_permissions_table($conn);
    }
}

$roles = [];
$assigned = [];
$userCounts = [];
$groups = eca_permission_groups();
$floor = eca_super_admin_floor_permissions();
$superOnly = eca_super_admin_only_permissions();
if ($conn) {
    $roles = $conn->query("SELECT * FROM roles WHERE slug <> 'public' ORDER BY id")->fetchAll(PDO::FETCH_ASSOC);
    if ($hasPermTable) {
        try {
            $rows = $conn->query('SELECT role_id, permission FROM role_permissions')->fetchAll(PDO::FETCH_ASSOC);
            foreach ($rows as $row) {
                $assigned[(int) $row['role_id']][] = (string) $row['permission'];
            }
        } catch (Throwable $e) {
            $assigned = [];
        }
    }
    try {
        $countRows = $conn->query(
            "SELECT role, COUNT(*) AS c FROM users GROUP BY role"
        )->fetchAll(PDO::FETCH_ASSOC);
        foreach ($countRows as $row) {
            $userCounts[eca_normalize_role((string) ($row['role'] ?? ''))] = (int) ($row['c'] ?? 0);
        }
    } catch (Throwable $e) {
        $userCounts = [];
    }
}
$csrf = eca_admin_csrf();
eca_admin_hub_start('Roles & Permissions', 'roles');
?>
<link rel="stylesheet" href="/css/roles-permissions.css?v=20261006-1">

<section class="rp-hero">
    <p class="rp-kicker">Access control</p>
    <h1>Roles &amp; permissions</h1>
    <p>Existing ECA roles are preserved. Super Admin–only permissions cannot be granted to other roles, and the Super Admin floor cannot be removed.</p>
</section>

<?php if (!$hasPermTable): ?>
<div class="rp-alert rp-alert-warn" role="status">
    <div class="rp-alert-icon" aria-hidden="true"><i class="fa-solid fa-triangle-exclamation"></i></div>
    <div>
        <strong>Permission persistence unavailable locally</strong>
        <p>The <code>role_permissions</code> table is not present. This screen shows <em>code default</em> permissions.
            Saving requires the existing local migration
            <code>v1/sql/007_role_permissions.sql</code>
            (or <code>v1/tools/apply-007-local.php</code>) after explicit approval — not applied in P2-C.</p>
    </div>
</div>
<?php endif; ?>

<?php if ($notice): ?>
<div class="rp-alert <?= $noticeError ? 'rp-alert-err' : 'rp-alert-ok' ?>" role="status">
    <div class="rp-alert-icon" aria-hidden="true"><i class="fa-solid <?= $noticeError ? 'fa-circle-xmark' : 'fa-circle-check' ?>"></i></div>
    <div><strong><?= eca_admin_h($notice) ?></strong></div>
</div>
<?php endif; ?>

<?php if (!$roles): ?>
<div class="rp-empty">No roles found.</div>
<?php endif; ?>

<div class="rp-stack">
<?php foreach ($roles as $role): ?>
<?php
    $roleId = (int) ($role['id'] ?? 0);
    $slug = eca_normalize_role((string) ($role['slug'] ?? ''));
    $have = $assigned[$roleId] ?? eca_default_role_permissions($slug);
    if ($slug === 'super_admin') {
        $have = array_values(array_unique(array_merge($have, $floor)));
    }
    $permCount = count($have);
    $usersOnRole = (int) ($userCounts[$slug] ?? 0);
    $fromDefaults = !$hasPermTable || !isset($assigned[$roleId]);
?>
<article class="rp-card">
    <header class="rp-card-head">
        <div class="rp-card-title">
            <h2><?= eca_admin_h($role['label'] ?? $slug) ?></h2>
            <div class="rp-badges">
                <span class="rp-badge"><?= eca_admin_h($slug) ?></span>
                <?php if ($fromDefaults): ?><span class="rp-badge rp-badge-warn">defaults</span><?php endif; ?>
                <?php if ($slug === 'super_admin'): ?><span class="rp-badge rp-badge-navy">protected floor</span><?php endif; ?>
            </div>
            <p class="rp-meta">
                <span><strong><?= (int) $permCount ?></strong> permission<?= $permCount === 1 ? '' : 's' ?></span>
                <span class="rp-dot" aria-hidden="true">·</span>
                <span><strong><?= (int) $usersOnRole ?></strong> user<?= $usersOnRole === 1 ? '' : 's' ?> with primary role</span>
            </p>
        </div>
    </header>

    <?php if ($canManage): ?>
    <form method="post" class="rp-form">
        <input type="hidden" name="csrf_token" value="<?= eca_admin_h($csrf) ?>">
        <input type="hidden" name="role_id" value="<?= $roleId ?>">
        <div class="rp-groups">
        <?php foreach ($groups as $groupLabel => $permissions): ?>
            <?php
                $show = [];
                foreach ($permissions as $permission) {
                    $locked = $slug === 'super_admin' && in_array($permission, $floor, true);
                    $forbidden = $slug !== 'super_admin' && in_array($permission, $superOnly, true);
                    if ($forbidden) {
                        continue;
                    }
                    $show[] = [$permission, $locked];
                }
                if (!$show) {
                    continue;
                }
            ?>
            <section class="rp-group">
                <h3><?= eca_admin_h($groupLabel) ?></h3>
                <div class="rp-grid">
                    <?php foreach ($show as [$permission, $locked]): ?>
                        <?php $checked = in_array($permission, $have, true) || $locked; ?>
                        <label class="rp-perm<?= $checked ? ' is-on' : '' ?><?= $locked ? ' is-locked' : '' ?>">
                            <input type="checkbox" name="permissions[]" value="<?= eca_admin_h($permission) ?>"<?= $checked ? ' checked' : '' ?><?= $locked || !$hasPermTable ? ' disabled' : '' ?>>
                            <span class="rp-perm-key"><?= eca_admin_h($permission) ?></span>
                            <?php if ($locked): ?><span class="rp-perm-tag">required</span><?php endif; ?>
                        </label>
                        <?php if ($locked && $hasPermTable): ?>
                            <input type="hidden" name="permissions[]" value="<?= eca_admin_h($permission) ?>">
                        <?php endif; ?>
                    <?php endforeach; ?>
                </div>
            </section>
        <?php endforeach; ?>
        </div>
        <footer class="rp-actions">
            <?php if ($hasPermTable): ?>
                <button class="hub-btn" type="submit">Save permissions</button>
            <?php else: ?>
                <button class="hub-btn" type="submit" disabled title="role_permissions table missing">Save permissions (unavailable)</button>
                <span class="rp-actions-note">Local defaults only — persistence table missing</span>
            <?php endif; ?>
        </footer>
    </form>
    <?php else: ?>
        <div class="rp-readonly">
            <p class="rp-readonly-label">Assigned permissions</p>
            <div class="rp-readonly-list">
                <?php foreach ($have as $permission): ?>
                    <code><?= eca_admin_h($permission) ?></code>
                <?php endforeach; ?>
            </div>
        </div>
    <?php endif; ?>
</article>
<?php endforeach; ?>
</div>
<?php eca_admin_hub_end(); ?>
