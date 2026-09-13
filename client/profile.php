<?php
require_once __DIR__ . '/_shell.php';

$member = eca_portal_member();
$GLOBALS['eca_member'] = $member;

eca_portal_start('Member profile', 'profile');
?>

<div class="panel">
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
            <i class="fa-solid fa-envelope"></i>
            <div>
                <label>Email</label>
                <span><?= eca_h($member['email'] ?: 'Not on file') ?></span>
            </div>
        </div>
        <div class="detail-item">
            <i class="fa-solid fa-phone"></i>
            <div>
                <label>Phone</label>
                <span><?= eca_h($member['phone'] ?: 'Not on file') ?></span>
            </div>
        </div>
        <div class="detail-item">
            <i class="fa-solid fa-location-dot"></i>
            <div>
                <label>Region / address</label>
                <span><?= eca_h(trim(($member['region'] ?? '') . ' / ' . ($member['address'] ?? ''), ' /') ?: 'Not on file') ?></span>
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
                <label>Status</label>
                <span><?= eca_h($member['status'] ?: 'Not on file') ?></span>
            </div>
        </div>
    </div>
    <div class="profile-actions">
        <a class="btn-primary" href="../contact.php">Ask the office to update details</a>
    </div>
</div>

<?php eca_portal_end(); ?>
