<?php
require_once __DIR__ . '/_shell.php';
require_once __DIR__ . '/../includes/certificates.php';
require_once __DIR__ . '/../includes/membership.php';
require_once __DIR__ . '/../includes/documents.php';

$member = eca_portal_member();
$GLOBALS['eca_member'] = $member;
$portal = eca_db();
$life = eca_member_lifecycle($portal, $member);
$clientId = (int) ($life['client_id'] ?? 0);
$history = [];
if ($portal && $clientId > 0 && eca_member_owns_client($member, $clientId, $portal)) {
    $history = eca_member_certificate_history($portal, $clientId);
}

eca_portal_start('Certificate', 'certificate');
?>

<div class="certificate-card">
    <div class="certificate-header">
        <i class="fa-solid fa-award"></i>
        <h4>Membership certificate</h4>
    </div>
    <div class="certificate-body">
        <p class="cpd-card-text">Public verification uses membership standing. An active certificate number is a lookup key — it does not override standing.</p>
        <ul class="certificate-meta">
            <li>
                <i class="fa-solid fa-building"></i>
                <div>
                    <div class="cpd-card-title">Company</div>
                    <div><?= eca_h($member['registered_name'] ?: $member['name']) ?></div>
                </div>
            </li>
            <li>
                <i class="fa-solid fa-hashtag"></i>
                <div>
                    <div class="cpd-card-title">Membership number</div>
                    <div><?= eca_h($member['membership'] ?: 'Not on file') ?></div>
                </div>
            </li>
            <li>
                <i class="fa-solid fa-shield-halved"></i>
                <div>
                    <div class="cpd-card-title">Standing / type</div>
                    <div><?= eca_h($life['standing'] ?: '—') ?> · <?= eca_h($life['membership_type'] ?: '—') ?></div>
                </div>
            </li>
            <li>
                <i class="fa-solid fa-barcode"></i>
                <div>
                    <div class="cpd-card-title">Active certificate</div>
                    <div><?= eca_h($life['certificate_number'] !== '' ? $life['certificate_number'] : 'None on file') ?>
                        <?= $life['certificate_status'] !== '' ? ' · ' . eca_h($life['certificate_status']) : '' ?></div>
                </div>
            </li>
            <li>
                <i class="fa-solid fa-calendar"></i>
                <div>
                    <div class="cpd-card-title">Issued / valid until</div>
                    <div><?= eca_h($life['certificate_issued'] !== '' ? eca_display_date($life['certificate_issued']) : '—') ?>
                        · <?= eca_h($life['expiry'] !== '' ? eca_display_date($life['expiry']) : 'Confirm with the office') ?></div>
                </div>
            </li>
        </ul>
    </div>
    <div class="certificate-actions cert-page-actions">
        <?php if (!empty($life['certificate_id'])): ?>
            <a class="cert-btn cert-btn-primary" href="/certificate-download.php?id=<?= (int) $life['certificate_id'] ?>"><i class="fa-solid fa-download" aria-hidden="true"></i> Download certificate</a>
            <a class="cert-btn cert-btn-secondary" href="/verify.php?cert=<?= urlencode($life['certificate_number']) ?>"><i class="fa-solid fa-shield-halved" aria-hidden="true"></i> Public verify</a>
        <?php endif; ?>
        <a class="cert-btn cert-btn-secondary" href="mailto:support@eca.co.sz?subject=Membership%20certificate%20request"><i class="fa-solid fa-envelope" aria-hidden="true"></i> Email a certificate request</a>
    </div>
</div>

<div class="panel eca-form-panel eca-table-panel">
    <div class="eca-panel-heading"><h3>Certificate history</h3></div>
    <div class="eca-panel-body">
        <table class="hub-table">
            <thead><tr><th>Number</th><th>Status</th><th>Issued</th><th>Expiry</th><th></th></tr></thead>
            <tbody>
            <?php foreach ($history as $row): ?>
                <tr>
                    <td><?= eca_h($row['certificate_number'] ?? '') ?></td>
                    <td><?= eca_h($row['status'] ?? '') ?></td>
                    <td><?= eca_h(eca_display_date((string) ($row['issued_at'] ?? ''), '—')) ?></td>
                    <td><?= eca_h(eca_display_date((string) ($row['expiry_date'] ?? ''), '—')) ?></td>
                    <td>
                        <?php if (strtoupper((string) ($row['status'] ?? '')) === 'ACTIVE'): ?>
                            <a href="/certificate-download.php?id=<?= (int) $row['id'] ?>">Download</a>
                        <?php else: ?>
                            —
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endforeach; ?>
            <?php if (!$history): ?><tr data-hub-empty-row><td colspan="5">No certificates on this membership.</td></tr><?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php eca_portal_end(); ?>
