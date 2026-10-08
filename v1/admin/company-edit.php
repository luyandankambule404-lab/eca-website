<?php
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/_hub.php';
require_once __DIR__ . '/../includes/portal-db.php';
require_once __DIR__ . '/../includes/audit.php';
require_once __DIR__ . '/../includes/admin-ops.php';
eca_admin_require('companies.manage');
header('Cache-Control: no-store, no-cache, must-revalidate');

$id = (int) ($_GET['id'] ?? 0);
$conn = eca_admin_db();
if (!$conn || $id < 1) {
    eca_not_found('Company not found.');
}

$stmt = $conn->prepare('SELECT * FROM companies WHERE id = ? LIMIT 1');
$stmt->execute([$id]);
$row = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$row) {
    eca_not_found('Company not found.');
}

$notice = '';
$noticeError = false;
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!eca_admin_csrf_ok($_POST['csrf_token'] ?? null)) {
        $notice = 'Your session expired. Please try again.';
        $noticeError = true;
    } else {
        $name = trim((string) ($_POST['name'] ?? ''));
        $status = trim((string) ($_POST['status'] ?? 'active'));
        $industry = trim((string) ($_POST['industry'] ?? ''));
        $address = trim((string) ($_POST['address'] ?? ''));
        $phone = trim((string) ($_POST['phone'] ?? ''));
        $email = trim((string) ($_POST['email'] ?? ''));
        $website = trim((string) ($_POST['website'] ?? ''));
        $description = trim((string) ($_POST['description'] ?? ''));
        if (!empty($_POST['suspend'])) {
            $status = 'suspended';
        } elseif (!empty($_POST['reactivate'])) {
            $status = 'active';
        }
        if ($name === '') {
            $notice = 'Company name is required.';
            $noticeError = true;
        } else {
            $upd = $conn->prepare(
                'UPDATE companies SET name = ?, status = ?, industry = ?, address = ?, phone = ?, email = ?, website = ?, description = ? WHERE id = ?'
            );
            $upd->execute([$name, $status, $industry, $address, $phone, $email, $website, $description, $id]);
            eca_audit('company.updated', 'companies', (string) $id);
            $stmt->execute([$id]);
            $row = $stmt->fetch(PDO::FETCH_ASSOC) ?: $row;
            $notice = 'Public directory fields saved.';
        }
    }
}

$portal = eca_portal_pdo(false);
$members = eca_match_members_for_company($portal, $row);
$certs = [];
if ($portal && $members) {
    $ids = array_map(static fn ($m) => (int) ($m['client_id'] ?? 0), $members);
    $ids = array_values(array_filter($ids));
    if ($ids) {
        $marks = implode(',', array_fill(0, count($ids), '?'));
        $certStmt = $portal->prepare(
            "SELECT id, client_id, certificate_number, status, issued_at, membership_number
             FROM membership_certificates WHERE client_id IN ($marks) ORDER BY id DESC LIMIT 20"
        );
        $certStmt->execute($ids);
        $certs = $certStmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }
}

$csrf = eca_admin_csrf();
eca_admin_hub_start('Edit company', 'companies');
?>
<div class="hub-hello">
    <h1 class="hub-hello-title"><?= eca_admin_h($row['name'] ?? '') ?></h1>
    <p>Update public directory fields only. Registration number is not changed here. Membership rows below are lookup matches on registration number, email or company name — not a confirmed legal relationship.</p>
</div>
<p style="display:flex;flex-wrap:wrap;gap:8px;margin:0 0 12px;">
    <a class="hub-btn" href="/admin/company-detail.php?id=<?= (int) $id ?>">Company details</a>
    <a class="hub-btn" href="/admin/companies.php">Back to list</a>
</p>
<?php if ($notice !== ''): ?>
    <p class="hub-card"<?= $noticeError ? ' style="border-color:#b42318;"' : '' ?>><?= eca_admin_h($notice) ?></p>
<?php endif; ?>
<div class="hub-card eca-form-panel">
    <div class="eca-apply-head">
        <h1>Contractor</h1>
        <a href="/admin/companies.php">Back</a>
    </div>
    <h5>Directory</h5>
    <div class="eca-field-grid">
        <div>
            <label class="form-label">Registration number</label>
            <div class="eca-field-value"><?= eca_admin_h($row['registration_number'] ?? '') ?></div>
        </div>
        <div>
            <label class="form-label">Directory status</label>
            <div class="eca-field-value"><?= eca_admin_h($row['status'] ?? '') ?></div>
        </div>
    </div>
    <div class="eca-apply-nav">
        <a class="eca-btn-back" href="/admin/companies.php">Back</a>
        <a class="eca-btn-next" href="/contractor.php?id=<?= (int) $id ?>">View public profile</a>
    </div>
</div>
<form class="hub-card eca-form-panel" method="post">
    <input type="hidden" name="csrf_token" value="<?= eca_admin_h($csrf) ?>">
    <h5>Public listing</h5>
    <div class="eca-field-grid">
        <div>
            <label class="form-label" for="company-name">Name</label>
            <input id="company-name" name="name" value="<?= eca_admin_h($row['name'] ?? '') ?>" class="form-control">
        </div>
        <div>
            <label class="form-label" for="company-status">Status</label>
            <input id="company-status" name="status" value="<?= eca_admin_h($row['status'] ?? '') ?>" class="form-control">
        </div>
        <div>
            <label class="form-label" for="company-industry">Industry / classification</label>
            <input id="company-industry" name="industry" value="<?= eca_admin_h($row['industry'] ?? '') ?>" class="form-control">
        </div>
        <div>
            <label class="form-label" for="company-phone">Phone</label>
            <input id="company-phone" name="phone" value="<?= eca_admin_h($row['phone'] ?? '') ?>" class="form-control">
        </div>
        <div>
            <label class="form-label" for="company-email">Email</label>
            <input id="company-email" name="email" value="<?= eca_admin_h($row['email'] ?? '') ?>" class="form-control">
        </div>
        <div>
            <label class="form-label" for="company-website">Website</label>
            <input id="company-website" name="website" value="<?= eca_admin_h($row['website'] ?? '') ?>" class="form-control">
        </div>
        <div class="eca-field-full">
            <label class="form-label" for="company-address">Region / address</label>
            <input id="company-address" name="address" value="<?= eca_admin_h($row['address'] ?? '') ?>" class="form-control">
        </div>
        <div class="eca-field-full">
            <label class="form-label" for="company-description">Description</label>
            <textarea id="company-description" name="description" rows="4" class="form-control"><?= eca_admin_h($row['description'] ?? '') ?></textarea>
        </div>
    </div>
    <div class="eca-apply-nav">
        <button class="hub-btn eca-btn-back" type="submit" name="suspend" value="1" onclick="return confirm('Suspend this company in the public directory?');">Suspend</button>
        <button class="hub-btn" type="submit">Save</button>
        <button class="hub-btn" type="submit" name="reactivate" value="1">Reactivate</button>
    </div>
</form>
<div class="hub-card eca-table-panel">
    <h5>Lookup matches (not a confirmed legal link)</h5>
    <?php if (!$members): ?>
        <p>No exact email or company-name match in <code>tbl_client</code>. These lookups are not a membership relationship record.</p>
    <?php else: ?>
        <table class="hub-table">
            <thead><tr><th>Membership</th><th>Company</th><th>Standing</th><th></th></tr></thead>
            <tbody>
            <?php foreach ($members as $member): ?>
                <tr>
                    <td><?= eca_admin_h($member['MembershipNumber'] ?? '') ?></td>
                    <td><?= eca_admin_h($member['TradingName'] ?? $member['CompanyRegistrationName'] ?? '') ?></td>
                    <td><?= eca_admin_h($member['active'] ?? '') ?></td>
                    <td><a href="/admin/member-detail.php?id=<?= (int) $member['client_id'] ?>">Open</a></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>
</div>
<div class="hub-card eca-table-panel">
    <h5>Related certificates</h5>
    <?php if (!$certs): ?>
        <p>No certificates for the matched membership records.</p>
    <?php else: ?>
        <table class="hub-table">
            <thead><tr><th>Number</th><th>Status</th><th>Issued</th><th></th></tr></thead>
            <tbody>
            <?php foreach ($certs as $cert): ?>
                <tr>
                    <td><?= eca_admin_h($cert['certificate_number'] ?? '') ?></td>
                    <td><?= eca_admin_h($cert['status'] ?? '') ?></td>
                    <td><?= eca_admin_h($cert['issued_at'] ?? '') ?></td>
                    <td><a href="/verify.php?cert=<?= urlencode((string) ($cert['certificate_number'] ?? '')) ?>">Verify</a></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>
</div>
<?php eca_admin_hub_end(); ?>
