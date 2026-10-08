<?php
require_once __DIR__ . '/_shell.php';
require_once __DIR__ . '/../includes/company-data.php';
require_once __DIR__ . '/../includes/membership.php';
require_once __DIR__ . '/../includes/audit.php';

$member = eca_portal_member();
$GLOBALS['eca_member'] = $member;
$notice = '';
$mapped = null;
$ecaConn = eca_admin_db_for_member();
$portal = eca_db();

if ($ecaConn && $member) {
    $mapped = eca_member_directory_company($ecaConn, $member);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!eca_csrf_ok($_POST['csrf_token'] ?? null)) {
        $notice = 'Your session expired. Please try again.';
    } else {
        $email = trim((string) ($_POST['email'] ?? ''));
        $phone = trim((string) ($_POST['phone'] ?? ''));
        $address = trim((string) ($_POST['address'] ?? ''));
        $region = trim((string) ($_POST['region'] ?? ''));
        $membership = trim((string) ($member['membership'] ?? ''));
        $saved = false;
        if ($portal && $membership !== '') {
            $stmt = $portal->prepare(
                'UPDATE tbl_client SET EmailAddress = ?, Cellphone = ?, address = ?, Region = ? WHERE MembershipNumber = ?'
            );
            $stmt->execute([$email, $phone, $address, $region, $membership]);
            $saved = $stmt->rowCount() > 0 || true;
        }
        if ($ecaConn && $mapped) {
            $upd = $ecaConn->prepare('UPDATE companies SET email = ?, phone = ?, address = ? WHERE id = ?');
            $upd->execute([$email, $phone, $address, (int) $mapped['id']]);
        }
        if ($saved) {
            $_SESSION['eca_member']['email'] = $email;
            $_SESSION['eca_member']['phone'] = $phone;
            $_SESSION['eca_member']['address'] = $address;
            $_SESSION['eca_member']['region'] = $region;
            $member = eca_portal_member();
            $GLOBALS['eca_member'] = $member;
            eca_audit('member.profile.update', 'tbl_client', (string) ($member['client_id'] ?? $membership));
            $notice = $mapped
                ? 'Public contact details saved. Your directory listing was also updated.'
                : 'Public contact details saved on your membership record.';
        }
    }
}

function eca_admin_db_for_member(): ?PDO
{
    if (!class_exists('Database')) {
        require_once __DIR__ . '/../config.php';
    }
    try {
        $db = new Database();
        $conn = $db->getConnection(false);
        return $conn instanceof PDO ? $conn : null;
    } catch (Throwable $e) {
        return null;
    }
}

$csrf = eca_csrf_token();
$life = eca_member_lifecycle($portal, $member);
$years = eca_member_year_history($portal, $member, 10);
eca_portal_start('Member profile', 'profile');
?>

<div class="panel eca-form-panel">
    <div class="eca-panel-heading">
        <h3>Membership record</h3>
    </div>
    <div class="eca-panel-body">
        <div class="detail-item">
            <i class="fa-solid fa-hashtag"></i>
            <div>
                <label>Membership number</label>
                <span><?= eca_h($member['membership'] ?: 'Not on file') ?></span>
            </div>
        </div>
        <div class="detail-item">
            <i class="fa-solid fa-building"></i>
            <div>
                <label>Registered name</label>
                <span><?= eca_h($member['registered_name'] ?: $member['name']) ?></span>
            </div>
        </div>
        <div class="detail-item">
            <i class="fa-solid fa-user"></i>
            <div>
                <label>Contact person</label>
                <span><?= eca_h($member['name']) ?></span>
            </div>
        </div>
        <div class="detail-item">
            <i class="fa-solid fa-layer-group"></i>
            <div>
                <label>Classification</label>
                <span><?= eca_h($member['classification'] ?: 'Not on file') ?></span>
            </div>
        </div>
        <div class="detail-item">
            <i class="fa-solid fa-shield-halved"></i>
            <div>
                <label>Standing</label>
                <span><?= eca_h($life['standing'] ?: 'Not on file') ?></span>
            </div>
        </div>
        <div class="detail-item">
            <i class="fa-solid fa-id-card"></i>
            <div>
                <label>Membership type</label>
                <span><?= eca_h($life['membership_type'] ?: 'Not on file') ?></span>
            </div>
        </div>
        <div class="detail-item">
            <i class="fa-solid fa-file-lines"></i>
            <div>
                <label>Application</label>
                <span><?php if ($life['application_reference'] !== ''): ?>
                    <?= eca_h($life['application_reference']) ?> · <?= eca_h($life['application_status'] ?: '—') ?>
                <?php else: ?>None on this membership<?php endif; ?></span>
            </div>
        </div>
        <div class="detail-item">
            <i class="fa-solid fa-calendar"></i>
            <div>
                <label>Membership year / expiry</label>
                <span><?= eca_h($life['year'] !== '' ? $life['year'] : 'No year row') ?>
                    · <?= eca_h($life['expiry'] !== '' ? eca_display_date($life['expiry']) : 'No expiry on file') ?></span>
            </div>
        </div>
    </div>
</div>
<?php if ($years): ?>
<div class="panel eca-form-panel eca-table-panel">
    <div class="eca-panel-heading"><h3>Membership year history</h3></div>
    <div class="eca-panel-body">
        <table class="hub-table">
            <thead><tr><th>Year</th><th>Type</th><th>Status</th><th>Expiry</th></tr></thead>
            <tbody>
            <?php foreach ($years as $yearRow): ?>
                <tr>
                    <td><?= eca_h((string) ($yearRow['year'] ?? '')) ?></td>
                    <td><?= eca_h((string) ($yearRow['type'] ?? '')) ?></td>
                    <td><?= eca_h((string) ($yearRow['status'] ?? '')) ?></td>
                    <td><?= eca_h(eca_display_date((string) ($yearRow['expiry_date'] ?? ''), '—')) ?></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
<?php endif; ?>

<div class="panel eca-form-panel">
    <div class="eca-panel-heading">
        <h3>Public contact</h3>
    </div>
    <div class="eca-panel-body">
        <?php if ($notice !== ''): ?>
            <p><?= eca_h($notice) ?></p>
        <?php endif; ?>
        <form method="post">
            <input type="hidden" name="csrf_token" value="<?= eca_h($csrf) ?>">
            <div class="eca-field-grid">
                <div>
                    <label class="form-label" for="email">Email</label>
                    <input class="form-control" id="email" name="email" type="email" value="<?= eca_h($member['email']) ?>">
                </div>
                <div>
                    <label class="form-label" for="phone">Phone</label>
                    <input class="form-control" id="phone" name="phone" value="<?= eca_h($member['phone']) ?>">
                </div>
                <div>
                    <label class="form-label" for="region">Region</label>
                    <input class="form-control" id="region" name="region" value="<?= eca_h($member['region']) ?>">
                </div>
                <div class="eca-field-full">
                    <label class="form-label" for="address">Physical address</label>
                    <textarea class="form-control" id="address" name="address" rows="2"><?= eca_h($member['address']) ?></textarea>
                </div>
            </div>
            <div class="eca-apply-nav">
                <button class="btn-primary eca-btn-next" type="submit">Save public contact</button>
            </div>
        </form>
    </div>
</div>

<?php eca_portal_end(); ?>
