<?php
require_once __DIR__ . '/_shell.php';

$member = eca_portal_member();
$GLOBALS['eca_member'] = $member;

eca_portal_start('Certificate', 'certificate');
?>

<div class="certificate-card">
    <div class="certificate-header">
        <i class="fa-solid fa-award"></i>
        <h4>Membership certificate</h4>
    </div>
    <div class="certificate-body">
        <p class="cpd-card-text">Certificates are issued by the ECA office for members in good standing. Use this page to confirm your membership details before you request a copy.</p>
        <ul class="certificate-meta" style="list-style:none;margin:18px 0 0;padding:0;">
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
                <i class="fa-solid fa-calendar"></i>
                <div>
                    <div class="cpd-card-title">Valid until</div>
                    <div><?= eca_h($member['expiry'] ?: 'Confirm with the office') ?></div>
                </div>
            </li>
        </ul>
    </div>
    <div class="certificate-actions">
        <a class="btn-success" href="mailto:support@eca.co.sz?subject=Membership%20certificate%20request">Email a certificate request</a>
    </div>
</div>

<?php eca_portal_end(); ?>
