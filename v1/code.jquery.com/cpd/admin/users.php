<?php
require_once "../auth.php";
require_once dirname(__DIR__, 3) . '/includes/pagination.php';
require_once dirname(__DIR__, 3) . '/includes/authz.php';
require_role('SUPPERADMIN');
eca_require_super_admin();

$msg = $err = "";

/* ===== Allowed values ===== */
$allowed_roles = ['CONTRACTOR', 'OFFICER', 'ADMIN', 'SUPPERADMIN'];
$allowed_statuses = ['ACTIVE', 'SUSPENDED'];

/* ===== Actions: Add / Update / Toggle Status / Reset Password ===== */
if (is_post()) {
  cpd_require_csrf();
  $action = $_POST['action'] ?? '';

  // ADD USER
  if ($action === 'add') {
    $role    = strtoupper(trim($_POST['role'] ?? 'CONTRACTOR'));
    $company = trim($_POST['company_name'] ?? '');
    $name    = trim($_POST['full_name'] ?? '');
    $email   = trim($_POST['email'] ?? '');
    $phone   = trim($_POST['phone'] ?? '');
    $status  = strtoupper(trim($_POST['status'] ?? 'ACTIVE'));
    $pass    = (string)($_POST['password'] ?? '');

    if (!in_array($role, $allowed_roles, true)) {
      $role = 'CONTRACTOR';
    }
    if (!in_array($status, $allowed_statuses, true)) {
      $status = 'ACTIVE';
    }

    if ($name === '' || !filter_var($email, FILTER_VALIDATE_EMAIL) || strlen($pass) < 12) {
      $err = "A valid name, email and password of at least 12 characters are required.";
    } else {
      $chk = $conn->prepare("SELECT id FROM user WHERE email=? LIMIT 1");
      $chk->bind_param("s", $email);
      $chk->execute();
      $exists = $chk->get_result()->fetch_assoc();

      if ($exists) {
        $err = "Email already exists. Use a different email.";
      } else {
        $hash = password_hash($pass, PASSWORD_DEFAULT);

        $stmt = $conn->prepare("INSERT INTO user (role, company_name, full_name, email, phone, password_hash, status, created_at)
                                VALUES (?,?,?,?,?,?,?, NOW())");
        $stmt->bind_param("sssssss", $role, $company, $name, $email, $phone, $hash, $status);
        $stmt->execute();
        $msg = "User added successfully.";
      }
    }
  }

  // UPDATE USER DETAILS
  if ($action === 'update') {
    $id      = (int)($_POST['id'] ?? 0);
    $role    = strtoupper(trim($_POST['role'] ?? 'CONTRACTOR'));
    $company = trim($_POST['company_name'] ?? '');
    $name    = trim($_POST['full_name'] ?? '');
    $email   = trim($_POST['email'] ?? '');
    $phone   = trim($_POST['phone'] ?? '');
    $status  = strtoupper(trim($_POST['status'] ?? 'ACTIVE'));

    if (!in_array($role, $allowed_roles, true)) {
      $role = 'CONTRACTOR';
    }
    if (!in_array($status, $allowed_statuses, true)) {
      $status = 'ACTIVE';
    }

    if ($id <= 0 || $name === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
      $err = "Missing user id, name or email.";
    } else {
      $chk = $conn->prepare("SELECT id FROM user WHERE email=? AND id<>? LIMIT 1");
      $chk->bind_param("si", $email, $id);
      $chk->execute();
      $exists = $chk->get_result()->fetch_assoc();

      if ($exists) {
        $err = "This email is already used by another user.";
      } else {
        $stmt = $conn->prepare("UPDATE user SET role=?, company_name=?, full_name=?, email=?, phone=?, status=? WHERE id=?");
        $stmt->bind_param("ssssssi", $role, $company, $name, $email, $phone, $status, $id);
        $stmt->execute();
        $msg = "User updated successfully.";
      }
    }
  }

  // TOGGLE STATUS
  if ($action === 'toggle') {
    $id = (int)($_POST['id'] ?? 0);

    if ($id <= 0 || $id === (int) ($_SESSION['user_id'] ?? 0)) {
      $err = "Invalid user id.";
    } else {
      $stmt = $conn->prepare("UPDATE user
                              SET status = CASE WHEN status='ACTIVE' THEN 'SUSPENDED' ELSE 'ACTIVE' END
                              WHERE id=?");
      $stmt->bind_param("i", $id);
      $stmt->execute();
      $msg = "User status updated.";
    }
  }

  // RESET PASSWORD
  if ($action === 'reset_password') {
    $id   = (int)($_POST['id'] ?? 0);
    $pass = (string)($_POST['new_password'] ?? '');

    if ($id <= 0 || strlen($pass) < 12) {
      $err = "User id and a password of at least 12 characters are required.";
    } else {
      $hash = password_hash($pass, PASSWORD_DEFAULT);
      $stmt = $conn->prepare("UPDATE user SET password_hash=? WHERE id=?");
      $stmt->bind_param("si", $hash, $id);
      $stmt->execute();
      $msg = "Password reset successfully.";
    }
  }
}

/* ===== Filters + Listing ===== */
$q = trim($_GET['q'] ?? '');
$f_role = strtoupper(trim($_GET['role'] ?? ''));
$f_status = strtoupper(trim($_GET['status'] ?? ''));

if ($f_role !== '' && !in_array($f_role, $allowed_roles, true)) {
  $f_role = '';
}
if ($f_status !== '' && !in_array($f_status, $allowed_statuses, true)) {
  $f_status = '';
}

$where = " WHERE 1=1";
$params = [];
$types  = "";

if ($q !== '') {
  $where .= " AND (full_name LIKE ? OR email LIKE ? OR company_name LIKE ? OR phone LIKE ?)";
  $like = "%$q%";
  $params[] = $like;
  $params[] = $like;
  $params[] = $like;
  $params[] = $like;
  $types .= "ssss";
}
if ($f_role !== '') {
  $where .= " AND role=?";
  $params[] = $f_role;
  $types .= "s";
}
if ($f_status !== '') {
  $where .= " AND status=?";
  $params[] = $f_status;
  $types .= "s";
}

$page = eca_pager_page();
$limit = eca_pager_limit();
$paged = eca_paged_query_mysqli(
  $conn,
  "SELECT COUNT(*) FROM user" . $where,
  "SELECT id, role, company_name, full_name, email, phone, status, created_at FROM user" . $where,
  $types,
  $params,
  $page,
  $limit
);
$userRows = $paged['rows'];
$userTotal = $paged['total'];
$userPage = $paged['page'];
$userPages = $paged['pages'];

function role_badge_color($role) {
  switch ($role) {
    case 'SUPPERADMIN': return '#7c3aed';
    case 'ADMIN': return '#0f172a';
    case 'OFFICER': return '#ea580c';
    case 'CONTRACTOR': return '#25809b';
    default: return '#64748b';
  }
}

require_once "../header.php";
?>

<div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-3">
  <div>
    <h4 class="mb-0" style="font-weight:950;letter-spacing:-.3px;">Manage Users</h4>
    <div class="text-muted">Create and manage Contractor, Officer, Admin and SupperAdmin accounts</div>
  </div>
  <a class="btn btn-soft btn-pill" href="/cpd/admin/dashboard.php"><i class="fa-solid fa-arrow-left me-2"></i>Back</a>
</div>

<?php if($err): ?><div class="alert alert-danger"><?=e($err)?></div><?php endif; ?>
<?php if($msg): ?><div class="alert alert-success"><?=e($msg)?></div><?php endif; ?>

<!-- ADD USER -->
<div class="card card-premium mb-3">
  <div class="card-body">
    <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-2">
      <h5 class="mb-0" style="font-weight:950;">Add User</h5>
      <span class="badge" style="background:#25809b;">Admin Only</span>
    </div>

    <form method="post" class="row g-2">
      <?= cpd_csrf_input() ?>
      <input type="hidden" name="action" value="add">

      <div class="col-md-3">
        <label class="form-label">Role</label>
        <select class="form-select" name="role">
          <option value="CONTRACTOR">CONTRACTOR</option>
          <option value="OFFICER">OFFICER</option>
          <option value="ADMIN">ADMIN</option>
          <option value="SUPPERADMIN">SUPPERADMIN</option>
        </select>
      </div>

      <div class="col-md-3">
        <label class="form-label">Status</label>
        <select class="form-select" name="status">
          <option value="ACTIVE">ACTIVE</option>
          <option value="INACTIVE">INACTIVE</option>
        </select>
      </div>

      <div class="col-md-6">
        <label class="form-label">Company Name</label>
        <input class="form-control" name="company_name" placeholder="e.g. Siyakhula Construction">
      </div>

      <div class="col-md-4">
        <label class="form-label">Full Name *</label>
        <input class="form-control" name="full_name" required>
      </div>

      <div class="col-md-4">
        <label class="form-label">Email *</label>
        <input class="form-control" type="email" name="email" required>
      </div>

      <div class="col-md-4">
        <label class="form-label">Phone</label>
        <input class="form-control" name="phone">
      </div>

      <div class="col-md-6">
        <label class="form-label">Password *</label>
        <input class="form-control" type="password" name="password" required>
      </div>

      <div class="col-12">
        <button class="btn btn-brand btn-pill"><i class="fa-solid fa-user-plus me-2"></i>Create User</button>
      </div>
    </form>
  </div>
</div>

<!-- FILTERS -->
<div class="card card-premium mb-3">
  <div class="card-body">
    <form method="get" class="row g-2 align-items-end">
      <div class="col-md-6">
        <label class="form-label">Search</label>
        <input class="form-control" id="userSearch" name="q" value="<?=e($q)?>" data-hub-search placeholder="Type a letter to filter name, email, company, phone..." autocomplete="off">
      </div>

      <div class="col-md-3">
        <label class="form-label">Role</label>
        <select class="form-select" name="role">
          <option value="">All</option>
          <option value="CONTRACTOR" <?= $f_role==='CONTRACTOR' ? 'selected' : '' ?>>CONTRACTOR</option>
          <option value="OFFICER" <?= $f_role==='OFFICER' ? 'selected' : '' ?>>OFFICER</option>
          <option value="ADMIN" <?= $f_role==='ADMIN' ? 'selected' : '' ?>>ADMIN</option>
          <option value="SUPPERADMIN" <?= $f_role==='SUPPERADMIN' ? 'selected' : '' ?>>SUPPERADMIN</option>
        </select>
      </div>

      <div class="col-md-3">
        <label class="form-label">Status</label>
        <select class="form-select" name="status">
          <option value="">All</option>
          <option value="ACTIVE" <?= $f_status==='ACTIVE' ? 'selected' : '' ?>>ACTIVE</option>
          <option value="INACTIVE" <?= $f_status==='INACTIVE' ? 'selected' : '' ?>>INACTIVE</option>
        </select>
      </div>

      <div class="col-12 d-flex gap-2 flex-wrap">
        <button class="btn btn-soft btn-pill"><i class="fa-solid fa-filter me-2"></i>Apply</button>
        <a class="btn btn-soft btn-pill" href="/cpd/admin/users.php"><i class="fa-solid fa-rotate-left me-2"></i>Reset</a>
      </div>
    </form>
  </div>
</div>

<!-- USERS TABLE -->
<div class="card card-premium">
  <div class="card-body">
    <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-2">
      <h5 class="mb-0" style="font-weight:950;">Users</h5>
      <span class="text-muted small">Tip: Click “Edit” to update details, or “Reset Password”.</span>
    </div>

    <div class="table-responsive">
      <table class="table align-middle" data-dash-server-page="1">
        <thead>
          <tr>
            <th>ID</th>
            <th>Role</th>
            <th>Company</th>
            <th>Full Name</th>
            <th>Email</th>
            <th>Phone</th>
            <th>Status</th>
            <th>Created</th>
            <th class="text-end">Actions</th>
          </tr>
        </thead>

        <tbody>
        <?php foreach ($userRows as $u): ?>
          <tr>
            <td class="fw-bold"><?=e($u['id'])?></td>
            <td>
              <span class="badge" style="background:<?= role_badge_color($u['role']) ?>;">
                <?=e($u['role'])?>
              </span>
            </td>
            <td><?=e($u['company_name'])?></td>
            <td class="fw-bold"><?=e($u['full_name'])?></td>
            <td><?=e($u['email'])?></td>
            <td><?=e($u['phone'])?></td>
            <td>
              <span class="badge <?= $u['status']==='ACTIVE' ? 'bg-success' : 'bg-secondary' ?>">
                <?=e($u['status'])?>
              </span>
            </td>
            <td class="text-muted small"><?=e($u['created_at'])?></td>

            <td class="text-end">
              <button class="btn btn-sm btn-soft btn-pill" type="button"
                      data-bs-toggle="collapse" data-bs-target="#edit<?=e($u['id'])?>">
                <i class="fa-solid fa-pen-to-square me-1"></i>Edit
              </button>

              <form method="post" class="d-inline">
                <?= cpd_csrf_input() ?>
                <input type="hidden" name="action" value="toggle">
                <input type="hidden" name="id" value="<?=e($u['id'])?>">
                <button class="btn btn-sm btn-soft btn-pill" type="submit">
                  <i class="fa-solid fa-power-off me-1"></i><?= $u['status']==='ACTIVE' ? 'Disable' : 'Enable' ?>
                </button>
              </form>

              <button class="btn btn-sm btn-soft btn-pill" type="button"
                      data-bs-toggle="collapse" data-bs-target="#pass<?=e($u['id'])?>">
                <i class="fa-solid fa-key me-1"></i>Reset Password
              </button>
            </td>
          </tr>

          <!-- EDIT PANEL -->
          <tr class="collapse" id="edit<?=e($u['id'])?>">
            <td colspan="9">
              <div class="p-3 rounded-4" style="background:rgba(37,128,155,.06);border:1px solid rgba(15,23,42,.08);">
                <form method="post" class="row g-2">
                  <?= cpd_csrf_input() ?>
                  <input type="hidden" name="action" value="update">
                  <input type="hidden" name="id" value="<?=e($u['id'])?>">

                  <div class="col-md-2">
                    <label class="form-label">Role</label>
                    <select class="form-select" name="role">
                      <option value="CONTRACTOR" <?= $u['role']==='CONTRACTOR' ? 'selected' : '' ?>>CONTRACTOR</option>
                      <option value="OFFICER" <?= $u['role']==='OFFICER' ? 'selected' : '' ?>>OFFICER</option>
                      <option value="ADMIN" <?= $u['role']==='ADMIN' ? 'selected' : '' ?>>ADMIN</option>
                      <option value="SUPPERADMIN" <?= $u['role']==='SUPPERADMIN' ? 'selected' : '' ?>>SUPPERADMIN</option>
                    </select>
                  </div>

                  <div class="col-md-2">
                    <label class="form-label">Status</label>
                    <select class="form-select" name="status">
                      <option value="ACTIVE" <?= $u['status']==='ACTIVE' ? 'selected' : '' ?>>ACTIVE</option>
                      <option value="INACTIVE" <?= $u['status']==='INACTIVE' ? 'selected' : '' ?>>INACTIVE</option>
                    </select>
                  </div>

                  <div class="col-md-4">
                    <label class="form-label">Company</label>
                    <input class="form-control" name="company_name" value="<?=e($u['company_name'])?>">
                  </div>

                  <div class="col-md-4">
                    <label class="form-label">Full Name *</label>
                    <input class="form-control" name="full_name" value="<?=e($u['full_name'])?>" required>
                  </div>

                  <div class="col-md-4">
                    <label class="form-label">Email *</label>
                    <input class="form-control" type="email" name="email" value="<?=e($u['email'])?>" required>
                  </div>

                  <div class="col-md-4">
                    <label class="form-label">Phone</label>
                    <input class="form-control" name="phone" value="<?=e($u['phone'])?>">
                  </div>

                  <div class="col-12">
                    <button class="btn btn-brand btn-pill"><i class="fa-solid fa-floppy-disk me-2"></i>Save Changes</button>
                  </div>
                </form>
              </div>
            </td>
          </tr>

          <!-- RESET PASSWORD PANEL -->
          <tr class="collapse" id="pass<?=e($u['id'])?>">
            <td colspan="9">
              <div class="p-3 rounded-4" style="background:rgba(208,25,25,.06);border:1px solid rgba(15,23,42,.08);">
                <form method="post" class="row g-2 align-items-end">
                  <?= cpd_csrf_input() ?>
                  <input type="hidden" name="action" value="reset_password">
                  <input type="hidden" name="id" value="<?=e($u['id'])?>">

                  <div class="col-md-6">
                    <label class="form-label">New Password</label>
                    <input class="form-control" type="password" name="new_password" required>
                  </div>

                  <div class="col-md-6">
                    <button class="btn btn-danger btn-pill">
                      <i class="fa-solid fa-key me-2"></i>Reset Password
                    </button>
                  </div>
                </form>
              </div>
            </td>
          </tr>

        <?php endforeach; ?>
        </tbody>
      </table>
    </div>
    <?php eca_render_request_pager($userPage, $userPages, $userTotal, $limit); ?>
  </div>
</div>

<?php require_once "../footer.php"; ?>