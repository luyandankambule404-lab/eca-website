<?php
require_once __DIR__ . '/_shell.php';
require_once __DIR__ . '/../includes/notify.php';
require_once __DIR__ . '/../includes/portal-db.php';
require_once __DIR__ . '/../includes/membership.php';
require_once __DIR__ . '/../includes/member-projects.php';
require_once __DIR__ . '/../includes/audit.php';
require_once __DIR__ . '/../code.jquery.com/cpd/auth.php';

$member = eca_portal_member();
$GLOBALS['eca_member'] = $member;
$initial = strtoupper(substr((string) $member['name'], 0, 1));
$notes = [];
$portal = eca_portal_pdo(false);
$membership = trim((string) ($member['membership'] ?? ''));
$projectNotice = '';
$csrf = eca_csrf_token();
$projectFiles = [];
$projectRecords = [];
$projectStats = ['completed' => 0, 'cycle' => date('Y') . '/' . (date('Y') + 1), 'range' => date('Y')];
$featuredProject = null;
$featuredCover = null;
$cpdPoints = 0.0;
$cpdApps = 0;
$openCourses = 0;

if ($portal) {
    $notes = eca_member_notifications($portal, $member);
    if ($membership !== '') {
        eca_project_backfill($portal, $membership, $member);
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && eca_csrf_ok($_POST['csrf_token'] ?? null) && ($_POST['action'] ?? '') === 'project-upload') {
            $stored = eca_project_store($portal, $member, $_FILES['project_file'] ?? [], (string) ($_POST['title'] ?? ''));
            if ($stored) {
                $projectNotice = 'File uploaded to your project portfolio.';
                eca_audit('project.upload', 'member_project_files', (string) $stored['id']);
            } else {
                $projectNotice = 'Upload a PDF, image, Word or Excel file under 10MB.';
            }
        }
        $projectRecords = eca_project_records($portal, $membership);
        $projectStats = eca_project_stats($portal, $membership);
        $featuredProject = $projectRecords[0] ?? null;
        if ($featuredProject) {
            $featuredCover = eca_project_cover($portal, (int) $featuredProject['id'], $membership);
            $projectFiles = eca_project_list($portal, $membership, 4, (int) $featuredProject['id']);
        } else {
            $projectFiles = eca_project_list($portal, $membership, 4);
        }
    }
    $cpdUser = cpd_sso_from_member($member);
    $userId = (int) ($cpdUser['id'] ?? 0);
    $email = trim((string) ($member['email'] ?? ''));
    try {
        if ($userId > 0) {
            $stmt = $portal->prepare('SELECT COALESCE(SUM(points), 0) FROM cpd_points_ledger WHERE user_id = ?');
            $stmt->execute([$userId]);
            $cpdPoints = (float) $stmt->fetchColumn();
        }
        if ($membership !== '') {
            $stmt = $portal->prepare('SELECT COUNT(*) FROM cpd_applications WHERE membership_number = ?');
            $stmt->execute([$membership]);
        } elseif ($email !== '') {
            $stmt = $portal->prepare('SELECT COUNT(*) FROM cpd_applications WHERE email = ? AND email <> \'\'');
            $stmt->execute([$email]);
        } else {
            $stmt = null;
        }
        $cpdApps = $stmt ? (int) $stmt->fetchColumn() : 0;
        $openCourses = (int) $portal->query("SELECT COUNT(*) FROM courses WHERE UPPER(status) IN ('OPEN', 'PUBLISHED', 'ACTIVE')")->fetchColumn();
    } catch (Throwable $e) {
        // keep zeros
    }
}

$life = eca_member_lifecycle($portal, $member);

eca_portal_start('Member dashboard', 'dashboard', 'is-member-dash', 'Your membership, certificate and CPD record.');
$companyName = trim((string) ($member['registered_name'] ?: $member['name']));
$standing = trim((string) ($life['standing'] ?: $member['status'] ?: 'Member'));
$membershipNo = trim((string) ($member['membership'] ?? '')) ?: '—';
$membershipType = trim((string) ($life['membership_type'] ?? ''));
?>

<div class="hub-stats">
    <article class="hub-stat">
        <div class="hub-stat-label">Membership number</div>
        <div class="hub-stat-value"><?= eca_h($membershipNo) ?></div>
        <?php if ($membershipType !== '' && strcasecmp($membershipType, $standing) !== 0): ?>
            <div class="hub-stat-meta"><?= eca_h($membershipType) ?></div>
        <?php endif; ?>
    </article>
    <article class="hub-stat">
        <div class="hub-stat-label">Registered company</div>
        <div class="hub-stat-value member-dash-stat-name"><?= eca_h($companyName) ?></div>
    </article>
    <article class="hub-stat">
        <div class="hub-stat-label">Standing</div>
        <div class="hub-stat-value"><?= eca_h($standing !== '' ? $standing : '—') ?></div>
    </article>
    <article class="hub-stat">
        <div class="hub-stat-label">Classification</div>
        <div class="hub-stat-value"><?= eca_h($member['classification'] ?: '—') ?></div>
    </article>
</div>

<div class="member-dash-grid">
    <section class="profile-card">
        <div class="profile-header">
            <div class="profile-avatar"><?= eca_h($initial) ?></div>
            <div class="profile-info">
                <h2><?= eca_h($companyName) ?></h2>
                <span class="status"><?= eca_h($standing) ?></span>
            </div>
        </div>
        <div class="profile-details member-dash-facts">
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
        <div class="profile-actions member-dash-actions">
            <a class="hub-btn-navy" href="/client/profile.php">View full profile</a>
        </div>
    </section>

    <div class="member-dash-side">
        <section class="certificate-card">
            <div class="certificate-header">
                <i class="fa-solid fa-award"></i>
                <h4>Membership certificate</h4>
            </div>
            <div class="certificate-body">
                <ul class="certificate-meta">
                    <li>
                        <i class="fa-solid fa-calendar"></i>
                        <div>
                            <div class="cpd-card-title">Valid until</div>
                            <div><?= eca_h($life['expiry'] !== '' ? eca_display_date($life['expiry']) : 'Confirm with the office') ?></div>
                        </div>
                    </li>
                    <li>
                        <i class="fa-solid fa-hashtag"></i>
                        <div>
                            <div class="cpd-card-title">Membership</div>
                            <div><?= eca_h($membershipNo) ?></div>
                        </div>
                    </li>
                </ul>
            </div>
            <div class="certificate-actions member-dash-actions">
                <?php if (!empty($life['certificate_id'])): ?>
                    <a class="hub-btn" href="/certificate-download.php?id=<?= (int) $life['certificate_id'] ?>">Download certificate</a>
                <?php endif; ?>
                <a class="hub-btn-navy" href="/client/certificate.php">Certificate details</a>
            </div>
        </section>

        <section class="cpd-summary-card">
            <div class="cpd-card-header">
                <i class="fa-solid fa-graduation-cap"></i>
                <h4>CPD points</h4>
            </div>
            <div class="cpd-card-body">
                <div class="cpd-card-title">Current cycle</div>
                <div class="cpd-card-value"><?= eca_h(rtrim(rtrim(number_format($cpdPoints, 1, '.', ''), '0'), '.') ?: '0') ?></div>
                <p class="cpd-card-text"><?= (int) $cpdApps ?> application<?= $cpdApps === 1 ? '' : 's' ?> on file. <?= (int) $openCourses ?> course<?= $openCourses === 1 ? '' : 's' ?> open.</p>
            </div>
            <div class="cpd-card-footer member-dash-actions">
                <a class="hub-btn-navy" href="/client/cpd.php">View CPD</a>
                <a class="hub-home-link" href="/learner-portal.php">Learner Portal</a>
            </div>
        </section>
    </div>
</div>

<nav class="member-dash-shortcuts" aria-label="Member shortcuts">
    <a class="hub-tile" href="/client/applications.php"><h3>Applications</h3><p>Track membership applications.</p><span>Open →</span></a>
    <a class="hub-tile hub-tile-sand" href="/client/documents.php"><h3>Documents</h3><p>Files held against this membership.</p><span>Open →</span></a>
    <a class="hub-tile hub-tile-lilac" href="/client/payments.php"><h3>Payments</h3><p>Receipts and outstanding balances.</p><span>Open →</span></a>
    <a class="hub-tile" href="/client/projects.php"><h3>Projects</h3><p>Portfolio, progress and evidence.</p><span>Open →</span></a>
    <a class="hub-tile hub-tile-sand" href="/renewal.php"><h3>Renew</h3><p>Start or continue a renewal.</p><span>Open →</span></a>
    <a class="hub-tile hub-tile-lilac" href="/client/notifications.php"><h3>Notifications</h3><p><?= $notes ? count($notes) . ' on file' : 'None yet' ?>.</p><span>Open →</span></a>
</nav>

<section class="certificate-card member-dash-notes">
    <div class="certificate-header">
        <i class="fa-solid fa-bell"></i>
        <h4>Notifications</h4>
    </div>
    <div class="certificate-body">
        <?php if (!$notes): ?>
            <p class="cpd-card-text">No membership notifications yet.</p>
        <?php else: ?>
            <ul class="certificate-meta">
                <?php foreach (array_slice($notes, 0, 4) as $note): ?>
                    <li>
                        <i class="fa-solid fa-circle-info"></i>
                        <div>
                            <div class="cpd-card-title"><?= eca_h($note['title'] ?? '') ?></div>
                            <div><?= eca_h($note['message'] ?? '') ?></div>
                        </div>
                    </li>
                <?php endforeach; ?>
            </ul>
            <p class="cpd-card-text"><a href="/client/notifications.php">View all notifications</a></p>
        <?php endif; ?>
    </div>
</section>

<?php
$dashStatus = trim((string) ($life['standing'] ?? '')) ?: (trim((string) ($member['status'] ?? '')) ?: 'Active');
$dashProgress = $featuredProject ? max(0, min(100, (int) ($featuredProject['progress'] ?? 0))) : 0;
?>
<section class="proj-board proj-board-dash">
    <header class="proj-head">
        <div>
            <h2>Projects</h2>
            <p>Manage project portfolio, financial performance, progress and supporting evidence.</p>
        </div>
        <a class="proj-add" href="/client/projects.php">Open projects</a>
    </header>
    <?php if ($projectNotice): ?><p class="project-notice"><?= eca_h($projectNotice) ?></p><?php endif; ?>
    <div class="proj-layout">
        <div class="proj-main">
            <div class="proj-kpis">
                <article class="proj-kpi proj-kpi-status">
                    <div class="proj-kpi-label">Member status</div>
                    <div class="proj-kpi-value">
                        <?= eca_h(ucfirst(strtolower($dashStatus))) ?>
                        <span class="proj-dot<?= strcasecmp($dashStatus, 'Active') === 0 || strcasecmp($dashStatus, 'ACTIVE') === 0 ? ' is-on' : '' ?>" aria-hidden="true"></span>
                    </div>
                    <div class="proj-kpi-meta"><?= eca_h($projectStats['cycle']) ?></div>
                </article>
                <article class="proj-kpi">
                    <div class="proj-kpi-label">Completed Projects</div>
                    <div class="proj-kpi-value proj-kpi-count"><?= (int) $projectStats['completed'] ?></div>
                    <div class="proj-kpi-meta"><?= eca_h($projectStats['range']) ?></div>
                    <a class="proj-kpi-link" href="/client/projects.php?view=all">View all</a>
                </article>
            </div>
            <article class="proj-overview">
                <div class="proj-overview-head">
                    <i class="fa-solid fa-folder-open" aria-hidden="true"></i>
                    <h3>Project Overview</h3>
                </div>
                <?php if ($featuredProject): ?>
                    <div class="proj-photo">
                        <?php if ($featuredCover): ?>
                            <img src="/client/project-file.php?id=<?= (int) $featuredCover['id'] ?>" alt="<?= eca_h($featuredProject['title'] ?: 'Project photo') ?>">
                        <?php else: ?>
                            <div class="proj-photo-empty">
                                <i class="fa-solid fa-image" aria-hidden="true"></i>
                                <span>Upload a site photo to use it as the overview image.</span>
                            </div>
                        <?php endif; ?>
                    </div>
                    <div class="proj-meta">
                        <div>
                            <span>Start Date</span>
                            <strong><?= eca_h(eca_project_format_date($featuredProject['start_date'] ?? '')) ?></strong>
                        </div>
                        <div>
                            <span>Finish Date</span>
                            <strong><?= eca_h(eca_project_format_date($featuredProject['finish_date'] ?? '')) ?></strong>
                        </div>
                        <div>
                            <span>Client Entity</span>
                            <strong><?= eca_h(eca_project_na($featuredProject['client_entity'] ?? '')) ?></strong>
                        </div>
                        <div>
                            <span>Contract Type</span>
                            <strong><?= eca_h(eca_project_na($featuredProject['contract_type'] ?? '', 'Lump Sum')) ?></strong>
                        </div>
                    </div>
                    <p class="proj-copy"><?= nl2br(eca_h(eca_project_na($featuredProject['description'] ?? '', 'Open Projects to add an overview, then upload evidence to this folder.'))) ?></p>
                <?php else: ?>
                    <div class="proj-photo-empty">
                        <i class="fa-solid fa-folder-plus" aria-hidden="true"></i>
                        <span>No projects yet. Upload a file below or open Projects to add a portfolio record.</span>
                    </div>
                <?php endif; ?>
                <form class="project-upload" method="post" enctype="multipart/form-data">
                    <input type="hidden" name="csrf_token" value="<?= eca_h($csrf) ?>">
                    <input type="hidden" name="action" value="project-upload">
                    <label>Document title
                        <input type="text" name="title" maxlength="190" placeholder="e.g. Site drawing revision 2">
                    </label>
                    <label>File
                        <input type="file" name="project_file" accept=".pdf,.png,.jpg,.jpeg,.doc,.docx,.xls,.xlsx" required>
                    </label>
                    <button class="btn-primary" type="submit">Upload</button>
                </form>
                <?php if ($projectFiles): ?>
                    <ul class="proj-files">
                        <?php foreach ($projectFiles as $file): ?>
                            <li>
                                <div>
                                    <strong><?= eca_h($file['title'] ?: $file['original_name']) ?></strong>
                                    <span><?= eca_h(!empty($file['created_at']) ? date('d M Y', strtotime((string) $file['created_at'])) : '') ?></span>
                                </div>
                                <a class="hub-btn-navy" href="/client/project-file.php?id=<?= (int) $file['id'] ?>">Open</a>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                <?php endif; ?>
            </article>
        </div>
        <aside class="proj-side">
            <article class="proj-side-card">
                <h3><i class="fa-solid fa-chart-line" aria-hidden="true"></i> Progress</h3>
                <div class="proj-progress-value"><?= $featuredProject ? (int) $dashProgress . '%' : '—' ?></div>
                <div class="proj-progress-bar" aria-hidden="true"><span style="width:<?= $featuredProject ? (int) $dashProgress : 0 ?>%"></span></div>
            </article>
            <article class="proj-side-card">
                <h3><i class="fa-solid fa-list-check" aria-hidden="true"></i> Variations &amp; EOT</h3>
                <div class="proj-field">
                    <span>Pending Variation Orders</span>
                    <strong><?= $featuredProject ? (int) ($featuredProject['pending_vo'] ?? 0) : '0' ?></strong>
                </div>
                <div class="proj-field">
                    <span>Unapproved VO Value</span>
                    <strong><?= $featuredProject ? eca_h(eca_project_money($featuredProject['unapproved_vo_value'] ?? 0)) : 'E 0.00' ?></strong>
                </div>
                <div class="proj-field">
                    <span>EOT Date</span>
                    <strong><?= $featuredProject ? eca_h(eca_project_format_date($featuredProject['eot_date'] ?? '')) : 'N/A' ?></strong>
                </div>
                <div class="proj-field">
                    <span>EOT Status</span>
                    <strong><?= $featuredProject ? eca_h(strtoupper((string) ($featuredProject['eot_status'] ?? 'NOT REQUESTED'))) : 'NOT REQUESTED' ?></strong>
                </div>
            </article>
            <article class="proj-side-card">
                <h3><i class="fa-solid fa-lock" aria-hidden="true"></i> Admin &amp; Dispute Management</h3>
                <p><span>Intervention:</span> <?= $featuredProject ? eca_h(eca_project_na($featuredProject['intervention'] ?? '', 'No')) : 'No' ?></p>
                <p><span>Assigned Staff:</span> <?= $featuredProject ? eca_h(eca_project_na($featuredProject['assigned_staff'] ?? '', 'Not assigned')) : 'Not assigned' ?></p>
                <p><span>Dispute Level:</span> <?= $featuredProject ? eca_h(eca_project_na($featuredProject['dispute_level'] ?? '')) : 'N/A' ?></p>
                <p><span>Next Action:</span> <?= $featuredProject ? eca_h(eca_project_na($featuredProject['next_action'] ?? '')) : 'N/A' ?></p>
            </article>
        </aside>
    </div>
</section>

<?php eca_portal_end(); ?>
