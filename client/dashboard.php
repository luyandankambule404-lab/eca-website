<?php
require_once __DIR__ . '/_shell.php';

$member = eca_portal_member();
$GLOBALS['eca_member'] = $member;
$initial = strtoupper(substr((string) $member['name'], 0, 1));

eca_portal_start('Member dashboard', 'dashboard');
?>

<div class="row">
    <div class="col-lg-3 col-sm-6">
        <div class="cardbox">
            <div class="card-body">
                <div class="statistic-box">
                    <h3><?= eca_h($member['membership'] ?: '—') ?></h3>
                    <p>Membership number</p>
                </div>
                <div class="stat-icon"><i class="fa-solid fa-hashtag"></i></div>
            </div>
        </div>
    </div>
    <div class="col-lg-3 col-sm-6">
        <div class="cardbox">
            <div class="card-body">
                <div class="statistic-box">
                    <h3><?= eca_h($member['registered_name'] ?: $member['name']) ?></h3>
                    <p>Registered company</p>
                </div>
                <div class="stat-icon"><i class="fa-solid fa-building"></i></div>
            </div>
        </div>
    </div>
    <div class="col-lg-3 col-sm-6">
        <div class="cardbox">
            <div class="card-body">
                <div class="statistic-box">
                    <h3><?= eca_h($member['status'] ?: '—') ?></h3>
                    <p>Membership status</p>
                </div>
                <div class="stat-icon"><i class="fa-solid fa-shield-halved"></i></div>
            </div>
        </div>
    </div>
    <div class="col-lg-3 col-sm-6">
        <div class="cardbox">
            <div class="card-body">
                <div class="statistic-box">
                    <h3><?= eca_h($member['classification'] ?: '—') ?></h3>
                    <p>Classification</p>
                </div>
                <div class="stat-icon"><i class="fa-solid fa-layer-group"></i></div>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-lg-8">
        <div class="profile-card">
            <div class="profile-header">
                <div class="profile-avatar"><?= eca_h($initial) ?></div>
                <div class="profile-info">
                    <h2><?= eca_h($member['registered_name'] ?: $member['name']) ?></h2>
                    <span class="status"><?= eca_h($member['status'] ?: 'Member') ?></span>
                </div>
            </div>
            <div class="profile-details">
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
                        <label>Region</label>
                        <span><?= eca_h($member['region'] ?: 'Not on file') ?></span>
                    </div>
                </div>
                <div class="detail-item">
                    <i class="fa-solid fa-map"></i>
                    <div>
                        <label>Address</label>
                        <span><?= eca_h($member['address'] ?: 'Not on file') ?></span>
                    </div>
                </div>
            </div>
            <div class="profile-actions">
                <a class="btn-primary" href="profile.php">View full profile</a>
            </div>
        </div>
    </div>

    <div class="col-lg-4">
        <div class="certificate-card">
            <div class="certificate-header">
                <i class="fa-solid fa-award"></i>
                <h4>Membership certificate</h4>
            </div>
            <div class="certificate-body">
                <ul class="certificate-meta" style="list-style:none;margin:0;padding:0;">
                    <li>
                        <i class="fa-solid fa-calendar"></i>
                        <div>
                            <div class="cpd-card-title">Valid until</div>
                            <div><?= eca_h($member['expiry'] ?: 'Confirm with the office') ?></div>
                        </div>
                    </li>
                    <li>
                        <i class="fa-solid fa-hashtag"></i>
                        <div>
                            <div class="cpd-card-title">Membership</div>
                            <div><?= eca_h($member['membership'] ?: 'Not on file') ?></div>
                        </div>
                    </li>
                </ul>
            </div>
            <div class="certificate-actions">
                <a class="btn-success" href="mailto:support@eca.co.sz">Request certificate</a>
            </div>
        </div>

        <div class="cpd-summary-card">
            <div class="cpd-card-header">
                <i class="fa-solid fa-graduation-cap"></i>
                <h4>CPD points</h4>
            </div>
            <div class="cpd-card-body">
                <div class="cpd-card-title">Current cycle</div>
                <div class="cpd-card-value"><?= eca_h($member['cpd_points'] ?? '0') ?></div>
                <p class="cpd-card-text">Target <?= eca_h($member['cpd_target'] ?? '12') ?> points. Keep training records up to date with the CPD office.</p>
            </div>
            <div class="cpd-card-footer">
                <a class="cpd-btn" href="../code.jquery.com/cpd/login.php">Open CPD portal</a>
            </div>
        </div>
    </div>
</div>

<?php eca_portal_end(); ?>
