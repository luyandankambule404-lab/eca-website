<?php
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/_hub.php';
require_once __DIR__ . '/../includes/audit.php';
require_once __DIR__ . '/../includes/pagination.php';
eca_admin_require('users.view');
eca_require_super_admin();
header('Cache-Control: no-store');

$conn = eca_admin_db();
$actor = eca_admin_user() ?? [];
$canAssign = eca_can('users.manage');
$canCreate = eca_can('users.create') || eca_can('users.manage');
$canPromote = eca_actor_is_super_admin($actor) && eca_can('users.promote_super_admin');
$notice = '';
$noticeError = false;

if ($conn && $_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!eca_admin_csrf_ok($_POST['csrf_token'] ?? null)) {
        $notice = 'Your session expired. Please try again.';
        $noticeError = true;
    } elseif (!$canCreate) {
        eca_forbid('Only a Super Admin can create Hub users.');
    } elseif (strtolower(trim((string) ($_POST['action'] ?? ''))) === 'create_user') {
        $result = eca_create_hub_user($conn, $_POST, $actor);
        $notice = (string) ($result['message'] ?? 'The user could not be created.');
        $noticeError = empty($result['ok']);
    } else {
        $notice = 'Unknown action.';
        $noticeError = true;
    }
}

$search = trim((string) ($_GET['search'] ?? ''));
$roleFilter = eca_normalize_role((string) ($_GET['role'] ?? ''));
$statusFilter = strtoupper(trim((string) ($_GET['status'] ?? '')));
$sort = strtolower(trim((string) ($_GET['sort'] ?? 'id')));
$sortMap = [
    'id' => 'id ASC',
    'id_desc' => 'id DESC',
    'name' => 'name ASC',
    'name_desc' => 'name DESC',
    'email' => 'email ASC',
    'role' => 'role ASC',
];
$orderSql = $sortMap[$sort] ?? $sortMap['id'];
$page = eca_pager_page();
$limit = eca_pager_limit();
$users = [];
$total = 0;
$totalPages = 1;
$roles = [];
$assigned = [];
$lastLogins = [];
$hasStatus = $conn && eca_hub_users_has_column($conn, 'status');
$hasCreated = $conn && eca_hub_users_has_column($conn, 'created_at');
if ($conn) {
    $where = ' WHERE 1=1';
    $params = [];
    if ($search !== '') {
        $where .= ' AND (name LIKE ? OR email LIKE ? OR role LIKE ?)';
        $like = '%' . $search . '%';
        array_push($params, $like, $like, $like);
    }
    if ($roleFilter !== '' && in_array($roleFilter, eca_hub_officer_role_slugs(), true)) {
        $where .= ' AND role = ?';
        $params[] = $roleFilter;
    }
    if ($hasStatus && in_array($statusFilter, ['ACTIVE', 'INACTIVE'], true)) {
        $where .= ' AND UPPER(status) = ?';
        $params[] = $statusFilter;
    }
    $select = 'SELECT id, name, email, role, is_admin';
    if ($hasStatus) {
        $select .= ', status';
    }
    if ($hasCreated) {
        $select .= ', created_at';
    }
    $paged = eca_paged_query(
        $conn,
        'SELECT COUNT(*) FROM users' . $where,
        $select . ' FROM users' . $where . ' ORDER BY ' . $orderSql,
        $params,
        $page,
        $limit
    );
    $users = $paged['rows'];
    $total = $paged['total'];
    $page = $paged['page'];
    $totalPages = $paged['pages'];
    if (eca_admin_export_requested()) {
        eca_admin_require_csv_export();
        $exportRows = eca_admin_export_rows(
            $conn,
            $select . ' FROM users' . $where . ' ORDER BY ' . $orderSql,
            $params
        );
        $csv = [];
        foreach ($exportRows as $row) {
            $line = [
                $row['id'] ?? '',
                $row['name'] ?? '',
                $row['email'] ?? '',
                $row['role'] ?? '',
            ];
            if ($hasStatus) {
                $line[] = $row['status'] ?? 'ACTIVE';
            }
            $line[] = $row['created_at'] ?? '';
            $csv[] = $line;
        }
        $headers = ['ID', 'Name', 'Email', 'Role'];
        if ($hasStatus) {
            $headers[] = 'Status';
        }
        $headers[] = 'Created';
        eca_admin_send_csv('eca-hub-users', $headers, $csv, 'users');
    }
    $roles = $conn->query("SELECT * FROM roles WHERE slug <> 'public' AND slug <> 'member' ORDER BY id")->fetchAll(PDO::FETCH_ASSOC);
    $ids = array_map(static fn ($row) => (int) ($row['id'] ?? 0), $users);
    $lastLogins = eca_hub_last_logins($conn, $ids);
    if ($ids) {
        $placeholders = implode(',', array_fill(0, count($ids), '?'));
        $mapStmt = $conn->prepare('SELECT user_id, role_id FROM user_roles WHERE user_id IN (' . $placeholders . ')');
        $mapStmt->execute($ids);
        foreach ($mapStmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
            $assigned[(int) $row['user_id']][] = (int) $row['role_id'];
        }
    }
}
$csrf = eca_admin_csrf();
$createRoles = [];
foreach ($roles as $role) {
    $slug = eca_normalize_role((string) ($role['slug'] ?? ''));
    if ($slug === 'super_admin' && !$canPromote) {
        continue;
    }
    $createRoles[] = $role;
}
$listStart = $total === 0 ? 0 : (($page - 1) * $limit) + 1;
$listEnd = $total === 0 ? 0 : min($total, $page * $limit);
eca_admin_hub_start('Users', 'users');
?>
<style>
.ur-badge{display:inline-block;padding:2px 8px;border-radius:999px;font-size:.72rem;font-weight:700;background:#e8eef7;color:#1f4b7a}
.ur-badge-ok{background:#e7f6ed;color:#11663a}
.ur-badge-warn{background:#fff4df;color:#8a5a00}
.ur-badge-lock{background:#fde8e8;color:#912018}
.ur-meta{color:#667;font-size:.85rem;margin:0 0 12px}
</style>
<div class="hub-hello">
    <h1 class="hub-hello-title">Hub users (<?= (int) $total ?>)</h1>
    <p><?= $canAssign ? 'Search, filter and open an account to assign roles, activate or reset a password. The final Super Admin is protected server-side.' : 'View-only. Role and password changes need a Super Admin.' ?></p>
</div>
<p class="ur-meta">Showing <?= (int) $listStart ?>–<?= (int) $listEnd ?> of <?= (int) $total ?>. Passwords and hashes are never displayed.</p>
<?php if (!$hasStatus): ?>
<p class="hub-note">Account status column is not present in local <code>users</code> — activate/deactivate UI is unavailable until that schema column exists. Default standing is treated as active for login.</p>
<?php endif; ?>
<?php if ($notice): ?><p class="hub-card"<?= $noticeError ? ' style="border-color:#b42318;"' : '' ?>><?= eca_admin_h($notice) ?></p><?php endif; ?>
<form class="hub-card" method="get" style="display:flex;gap:10px;flex-wrap:wrap;align-items:center;margin-bottom:12px;">
    <input type="search" name="search" data-hub-search value="<?= eca_admin_h($search) ?>" placeholder="Name, email or role" autocomplete="off" aria-label="Filter users" style="padding:10px 12px;border-radius:10px;border:1px solid #d0d5dd;">
    <select name="role" onchange="this.form.submit()" style="padding:10px 12px;border-radius:10px;border:1px solid #d0d5dd;">
        <option value="">All roles</option>
        <?php foreach ($roles as $role): ?>
            <?php $slug = eca_normalize_role((string) ($role['slug'] ?? '')); ?>
            <option value="<?= eca_admin_h($slug) ?>"<?= $roleFilter === $slug ? ' selected' : '' ?>><?= eca_admin_h($role['label'] ?? $slug) ?></option>
        <?php endforeach; ?>
    </select>
    <?php if ($hasStatus): ?>
    <select name="status" onchange="this.form.submit()" style="padding:10px 12px;border-radius:10px;border:1px solid #d0d5dd;">
        <option value="">All statuses</option>
        <option value="ACTIVE"<?= $statusFilter === 'ACTIVE' ? ' selected' : '' ?>>ACTIVE</option>
        <option value="INACTIVE"<?= $statusFilter === 'INACTIVE' ? ' selected' : '' ?>>INACTIVE</option>
    </select>
    <?php endif; ?>
    <select name="sort" onchange="this.form.submit()" style="padding:10px 12px;border-radius:10px;border:1px solid #d0d5dd;">
        <option value="id"<?= $sort === 'id' ? ' selected' : '' ?>>ID ↑</option>
        <option value="id_desc"<?= $sort === 'id_desc' ? ' selected' : '' ?>>ID ↓</option>
        <option value="name"<?= $sort === 'name' ? ' selected' : '' ?>>Name A–Z</option>
        <option value="name_desc"<?= $sort === 'name_desc' ? ' selected' : '' ?>>Name Z–A</option>
        <option value="email"<?= $sort === 'email' ? ' selected' : '' ?>>Email</option>
        <option value="role"<?= $sort === 'role' ? ' selected' : '' ?>>Role</option>
    </select>
    <button class="hub-btn" type="submit">Search</button>
    <?= eca_admin_csv_button() ?>
    <?php if ($search !== '' || $roleFilter !== '' || $statusFilter !== ''): ?>
        <a class="hub-home-link" href="/admin/users.php">Clear</a>
    <?php endif; ?>
</form>
<?php if ($canCreate): ?>
<form class="hub-card eca-form-panel" method="post" style="margin-bottom:12px;" autocomplete="off">
    <input type="hidden" name="csrf_token" value="<?= eca_admin_h($csrf) ?>">
    <input type="hidden" name="action" value="create_user">
    <h5 class="text-primary">Create administrative user</h5>
    <div class="mb-3">
        <label class="form-label" for="create-user-name">Full name</label>
        <input class="form-control" id="create-user-name" type="text" name="name" required placeholder="Full name">
    </div>
    <div class="mb-3">
        <label class="form-label" for="create-user-email">Email</label>
        <input class="form-control" id="create-user-email" type="email" name="email" required placeholder="Email" autocomplete="off">
    </div>
    <div class="mb-3">
        <label class="form-label" for="create-user-password">Password</label>
        <input class="form-control" id="create-user-password" type="password" name="password" required minlength="12" placeholder="Password (12+ characters)" autocomplete="new-password">
        <p class="form-text">Minimum 12 characters. Hashed with password_hash — never shown again.</p>
    </div>
    <div class="mb-3">
        <label class="form-label" for="create-user-role">Role</label>
        <select class="form-select" id="create-user-role" name="role" required>
            <?php foreach ($createRoles as $role): ?>
                <option value="<?= eca_admin_h(eca_normalize_role((string) ($role['slug'] ?? ''))) ?>"><?= eca_admin_h($role['label'] ?? $role['slug']) ?></option>
            <?php endforeach; ?>
        </select>
    </div>
    <button class="hub-btn" type="submit">Create user</button>
</form>
<?php endif; ?>
<div class="hub-card eca-table-panel">
    <h5>Users</h5>
    <table class="hub-table" data-dash-server-page="1">
        <thead>
            <tr>
                <th>Name</th>
                <th>Email</th>
                <th>Role</th>
                <th>Status</th>
                <th>Last login</th>
                <?php if ($hasCreated): ?><th>Created</th><?php endif; ?>
                <th></th>
            </tr>
        </thead>
        <tbody>
        <?php foreach ($users as $user): ?>
            <?php
                $userId = (int) ($user['id'] ?? 0);
                $status = strtoupper((string) ($user['status'] ?? 'ACTIVE'));
                if ($status === '') {
                    $status = 'ACTIVE';
                }
                $isFinalSuperAdmin = $conn && eca_is_final_active_super_admin($conn, $userId, $user);
                $badgeClass = $isFinalSuperAdmin ? 'ur-badge-lock' : ($status === 'ACTIVE' ? 'ur-badge-ok' : 'ur-badge-warn');
            ?>
            <tr>
                <td><?= eca_admin_h($user['name'] ?? '') ?></td>
                <td><?= eca_admin_h($user['email'] ?? '') ?></td>
                <td><span class="ur-badge"><?= eca_admin_h($user['role'] ?? '') ?></span></td>
                <td>
                    <span class="ur-badge <?= $badgeClass ?>"><?= eca_admin_h($hasStatus ? $status : 'ACTIVE') ?></span>
                    <?php if ($isFinalSuperAdmin): ?> <span class="ur-badge ur-badge-lock">Final Super Admin</span><?php endif; ?>
                </td>
                <td><?= eca_admin_h($lastLogins[(string) $userId] ?? '—') ?></td>
                <?php if ($hasCreated): ?><td><?= eca_admin_h((string) ($user['created_at'] ?? '—')) ?></td><?php endif; ?>
                <td><a href="/admin/user-detail.php?id=<?= $userId ?>">Open</a></td>
            </tr>
        <?php endforeach; ?>
        <?php if (!$users): ?><tr data-hub-empty-row><td colspan="<?= $hasCreated ? 7 : 6 ?>">No users match.</td></tr><?php endif; ?>
        </tbody>
    </table>
</div>
<?php
eca_render_request_pager($page, $totalPages, $total, $limit);
eca_admin_hub_end();
?>
