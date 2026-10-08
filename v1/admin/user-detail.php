<?php
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/_hub.php';
require_once __DIR__ . '/../includes/audit.php';
eca_admin_require('users.view');
eca_require_super_admin();
header('Cache-Control: no-store');

$conn = eca_admin_db();
$actor = eca_admin_user() ?? [];
$id = (int) ($_GET['id'] ?? 0);
if (!$conn || $id < 1) {
    eca_not_found('User not found.');
}

$hasStatus = eca_hub_users_has_column($conn, 'status');
$columns = 'id, name, email, role, is_admin, created_at, updated_at';
if ($hasStatus) {
    $columns .= ', status';
}
$stmt = $conn->prepare("SELECT $columns FROM users WHERE id = ? LIMIT 1");
$stmt->execute([$id]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$user) {
    eca_not_found('User not found.');
}

$canAssign = eca_can('users.manage');
$canPromote = eca_actor_is_super_admin($actor) && eca_can('users.promote_super_admin');
$canActivate = eca_can('users.activate') || eca_can('users.manage');
$canDeactivate = eca_can('users.deactivate') || eca_can('users.manage');
$canEdit = eca_can('users.edit') || eca_can('users.manage');
$canReset = eca_can('users.reset_password') || eca_can('users.manage');
$notice = '';
$noticeError = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!eca_admin_csrf_ok($_POST['csrf_token'] ?? null)) {
        $notice = 'Your session expired. Please try again.';
        $noticeError = true;
    } else {
        $action = strtolower(trim((string) ($_POST['action'] ?? '')));
        $allowedActions = [
            'save_roles' => $canAssign,
            'activate' => $canActivate,
            'deactivate' => $canDeactivate,
            'edit_user' => $canEdit,
            'reset_password' => $canReset,
        ];
        if (empty($allowedActions[$action])) {
            eca_forbid('You are not allowed to change Hub users.');
        }
        if ($action === 'save_roles') {
            $result = eca_assign_hub_roles($conn, $id, (array) ($_POST['role_ids'] ?? []), $actor);
        } elseif ($action === 'activate' || $action === 'deactivate') {
            $result = eca_set_hub_user_status($conn, $id, $action === 'activate' ? 'ACTIVE' : 'INACTIVE', $actor);
        } elseif ($action === 'edit_user') {
            $result = eca_update_hub_user($conn, $id, $_POST, $actor);
        } elseif ($action === 'reset_password') {
            $result = eca_reset_hub_user_password($conn, $id, (string) ($_POST['password'] ?? ''), $actor);
        } else {
            $result = ['ok' => false, 'message' => 'Unknown action.'];
        }
        $notice = (string) ($result['message'] ?? 'Request could not be completed.');
        $noticeError = empty($result['ok']);
        $stmt->execute([$id]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC) ?: $user;
    }
}

$roles = $conn->query("SELECT * FROM roles WHERE slug <> 'public' AND slug <> 'member' ORDER BY id")->fetchAll(PDO::FETCH_ASSOC);
$assigned = [];
$map = $conn->prepare('SELECT role_id FROM user_roles WHERE user_id = ?');
$map->execute([$id]);
foreach ($map->fetchAll(PDO::FETCH_COLUMN) as $roleId) {
    $assigned[] = (int) $roleId;
}
$lastLogin = eca_hub_last_logins($conn, [$id]);
$status = strtoupper((string) ($user['status'] ?? 'ACTIVE'));
if ($status === '') {
    $status = 'ACTIVE';
}
$csrf = eca_admin_csrf();
$isFinalSuperAdmin = eca_is_final_active_super_admin($conn, $id, $user);
$isSelf = (int) ($actor['id'] ?? 0) === $id;
eca_admin_hub_start('User', 'users');
?>
<style>
.ur-badge{display:inline-block;padding:2px 8px;border-radius:999px;font-size:.72rem;font-weight:700;background:#e8eef7;color:#1f4b7a}
.ur-badge-ok{background:#e7f6ed;color:#11663a}
.ur-badge-warn{background:#fff4df;color:#8a5a00}
.ur-badge-lock{background:#fde8e8;color:#912018}
</style>
<div class="hub-hello">
    <h1 class="hub-hello-title"><?= eca_admin_h($user['name'] ?? '') ?></h1>
    <p>
        <?= eca_admin_h($user['email'] ?? '') ?>
        · <span class="ur-badge"><?= eca_admin_h($user['role'] ?? '') ?></span>
        · <span class="ur-badge <?= $status === 'ACTIVE' ? 'ur-badge-ok' : 'ur-badge-warn' ?>"><?= eca_admin_h($status) ?></span>
        <?php if ($isFinalSuperAdmin): ?> · <span class="ur-badge ur-badge-lock">Final Super Admin — Protected</span><?php endif; ?>
        <?php if ($isSelf): ?> · <span class="ur-badge">Your account</span><?php endif; ?>
    </p>
</div>
<?php if ($notice): ?><p class="hub-card"<?= $noticeError ? ' style="border-color:#b42318;"' : '' ?>><?= eca_admin_h($notice) ?></p><?php endif; ?>
<div class="hub-card">
    <p><strong>Last login:</strong> <?= eca_admin_h($lastLogin[(string) $id] ?? 'Never recorded') ?></p>
    <p><strong>Created:</strong> <?= eca_admin_h($user['created_at'] ?? '') ?> · <strong>Updated:</strong> <?= eca_admin_h($user['updated_at'] ?? '') ?></p>
    <p class="hub-note">Passwords and hashes are never displayed. Email changes are not supported on this screen (identity stays stable).</p>
    <p><a href="/admin/users.php">Back to users</a></p>
</div>
<?php if ($canEdit): ?>
<form class="hub-card" method="post">
    <input type="hidden" name="csrf_token" value="<?= eca_admin_h($csrf) ?>">
    <input type="hidden" name="action" value="edit_user">
    <p style="margin:0 0 10px;font-weight:800;">Edit name</p>
    <div style="display:flex;gap:10px;flex-wrap:wrap;align-items:center;">
        <input class="form-control" type="text" name="name" required value="<?= eca_admin_h($user['name'] ?? '') ?>">
        <button class="hub-btn" type="submit">Save name</button>
    </div>
</form>
<?php endif; ?>
<?php if ($canAssign): ?>
<form class="hub-card" method="post">
    <input type="hidden" name="csrf_token" value="<?= eca_admin_h($csrf) ?>">
    <input type="hidden" name="action" value="save_roles">
    <p style="margin:0 0 10px;font-weight:800;">Assign Hub roles</p>
    <?php foreach ($roles as $role): ?>
        <?php
            $slug = eca_normalize_role((string) ($role['slug'] ?? ''));
            if ($slug === 'super_admin' && !$canPromote) {
                continue;
            }
            $checked = in_array((int) $role['id'], $assigned, true);
            $lockSuperAdmin = $isFinalSuperAdmin && $slug === 'super_admin';
        ?>
        <?php if ($lockSuperAdmin): ?>
            <input type="hidden" name="role_ids[]" value="<?= (int) $role['id'] ?>">
        <?php endif; ?>
        <label style="margin-right:12px;"><input type="checkbox" name="role_ids[]" value="<?= (int) $role['id'] ?>"<?= $checked ? ' checked' : '' ?><?= $lockSuperAdmin ? ' disabled' : '' ?>> <?= eca_admin_h($role['label'] ?? $role['slug']) ?></label>
    <?php endforeach; ?>
    <?php if ($isFinalSuperAdmin): ?>
        <p class="hub-note" style="margin-top:10px;">Final Super Admin — Protected. This account cannot be demoted while it is the only active Super Admin.</p>
    <?php endif; ?>
    <div style="margin-top:10px;"><button class="hub-btn" type="submit">Save roles</button></div>
</form>
<?php endif; ?>
<?php if ($hasStatus && ($canActivate || $canDeactivate)): ?>
<form class="hub-card" method="post" onsubmit="return confirmStatusChange(this);">
    <input type="hidden" name="csrf_token" value="<?= eca_admin_h($csrf) ?>">
    <?php if ($isFinalSuperAdmin): ?>
        <p class="hub-note" style="margin:0;">Final Super Admin — Protected. The last active Super Admin cannot be deactivated.</p>
    <?php elseif ($isSelf && $status === 'ACTIVE' && $canDeactivate): ?>
        <p class="hub-note" style="margin:0 0 8px;">You cannot deactivate your own account (server-side protected).</p>
        <button class="hub-btn" type="submit" name="action" value="deactivate" disabled>Deactivate (blocked for self)</button>
    <?php elseif ($status !== 'ACTIVE' && $canActivate): ?>
        <button class="hub-btn" type="submit" name="action" value="activate">Activate</button>
    <?php elseif ($status === 'ACTIVE' && $canDeactivate): ?>
        <button class="hub-btn" type="submit" name="action" value="deactivate" data-confirm="Deactivate this Hub user? They will not be able to sign in.">Deactivate</button>
    <?php endif; ?>
</form>
<script>
function confirmStatusChange(form) {
    var btn = form.querySelector('button[type="submit"][name="action"]:focus, button[type="submit"][name="action"]');
    var submitted = document.activeElement;
    if (submitted && submitted.getAttribute('data-confirm')) {
        return window.confirm(submitted.getAttribute('data-confirm'));
    }
    return true;
}
</script>
<?php elseif (!$hasStatus): ?>
<div class="hub-card">
    <p class="hub-note" style="margin:0;">Activate/deactivate is unavailable — local <code>users.status</code> column is not present. No schema change was applied in P2-C.</p>
</div>
<?php endif; ?>
<?php if ($canReset): ?>
<form class="hub-card" method="post" autocomplete="off">
    <input type="hidden" name="csrf_token" value="<?= eca_admin_h($csrf) ?>">
    <input type="hidden" name="action" value="reset_password">
    <p style="margin:0 0 10px;font-weight:800;">Reset password</p>
    <div style="display:flex;gap:10px;flex-wrap:wrap;align-items:center;">
        <input class="form-control" type="password" name="password" required minlength="12" placeholder="New password (12+ characters)" autocomplete="new-password">
        <button class="hub-btn" type="submit">Reset password</button>
    </div>
</form>
<?php endif; ?>
<?php eca_admin_hub_end(); ?>
