<?php
require_once __DIR__ . '/_shell.php';
require_once __DIR__ . '/../includes/member-projects.php';
require_once __DIR__ . '/../includes/audit.php';
require_once __DIR__ . '/../includes/notify.php';

$member = eca_portal_member();
$GLOBALS['eca_member'] = $member;
$portal = eca_db();
$membership = trim((string) ($member['membership'] ?? ''));
$notice = (string) ($_SESSION['eca_project_notice'] ?? '');
unset($_SESSION['eca_project_notice']);
$csrf = eca_csrf_token();
$tab = eca_project_folder_tab((string) ($_GET['tab'] ?? 'overview'));
$showForm = isset($_GET['new']) || isset($_GET['edit']);
$viewAll = (string) ($_GET['view'] ?? '') === 'all';
$editTarget = (string) ($_GET['edit'] ?? '');

if ($_SERVER['REQUEST_METHOD'] === 'POST' && eca_csrf_ok($_POST['csrf_token'] ?? null) && $portal && $membership !== '') {
    $action = (string) ($_POST['action'] ?? '');
    $tabAfter = eca_project_folder_tab((string) ($_POST['tab'] ?? $tab));
    if ($action === 'save-project') {
        $id = eca_project_save($portal, $member, $_POST);
        if ($id > 0) {
            eca_audit('project.save', 'member_projects', (string) $id);
            eca_project_flash('Project details saved.');
            header('Location: ' . eca_project_folder_url($id, 'overview'));
            exit;
        }
        eca_project_flash('The project could not be saved.');
        header('Location: /client/projects.php?new=1');
        exit;
    }
    if ($action === 'update-progress') {
        $id = (int) ($_POST['id'] ?? 0);
        if ($id > 0 && eca_project_get($portal, $id, $membership)) {
            eca_project_save_packages(
                $portal,
                $id,
                $membership,
                $_POST['package'] ?? [],
                isset($_POST['progress']) ? (int) $_POST['progress'] : null
            );
            eca_audit('project.progress', 'member_projects', (string) $id);
            eca_project_flash('Progress updated.');
        }
        header('Location: ' . eca_project_folder_url($id, $tabAfter === 'overview' ? 'progress' : $tabAfter));
        exit;
    }
    if ($action === 'update-health') {
        $id = (int) ($_POST['id'] ?? 0);
        if ($id > 0 && eca_project_get($portal, $id, $membership)) {
            eca_project_save_health($portal, $id, $membership, $_POST);
            eca_audit('project.health', 'member_projects', (string) $id);
            eca_project_flash('Project health saved.');
        }
        header('Location: ' . eca_project_folder_url($id, $tabAfter === 'overview' ? 'challenges' : $tabAfter));
        exit;
    }
    if ($action === 'save-capacity') {
        $id = (int) ($_POST['project_id'] ?? 0);
        eca_project_capacity_save($portal, $membership, $_POST);
        eca_audit('project.capacity', 'member_contractor_capacity', $membership);
        eca_project_flash('Capacity profile saved.');
        header('Location: ' . eca_project_folder_url($id, $tabAfter === 'overview' ? 'capacity' : $tabAfter));
        exit;
    }
    if ($action === 'delete-project') {
        $id = (int) ($_POST['id'] ?? 0);
        $ok = eca_project_delete_record($portal, $id, $membership);
        eca_project_flash($ok ? 'Project deleted.' : 'That project could not be deleted.');
        if ($ok) {
            eca_audit('project.delete', 'member_projects', (string) $id);
        }
        header('Location: /client/projects.php');
        exit;
    }
    if ($action === 'delete-file') {
        $ok = eca_project_delete($portal, (int) ($_POST['id'] ?? 0), $membership);
        $projectId = (int) ($_POST['project_id'] ?? 0);
        eca_project_flash($ok ? 'File removed from this project.' : 'That file could not be removed.');
        if ($ok) {
            eca_audit('project.delete', 'member_project_files', (string) ($_POST['id'] ?? ''));
        }
        header('Location: ' . eca_project_folder_url($projectId, 'documents'));
        exit;
    }
    if ($action === 'upload') {
        $projectId = (int) ($_POST['project_id'] ?? 0);
        $stored = eca_project_store($portal, $member, $_FILES['project_file'] ?? [], (string) ($_POST['title'] ?? ''), $projectId);
        if ($stored) {
            eca_audit('project.upload', 'member_project_files', (string) $stored['id']);
            eca_project_flash('Document uploaded to this project.');
            $projectId = (int) ($stored['project_id'] ?? $projectId);
        } else {
            eca_project_flash('Upload a PDF, image, Word or Excel file under 10MB.');
        }
        header('Location: ' . eca_project_folder_url($projectId, 'documents'));
        exit;
    }
}

$projects = [];
$current = null;
$files = [];
$cover = null;
$packages = [];
$timeline = [];
$capacity = eca_project_capacity_blank($member);
$industry = [
    'registered' => 0,
    'active_contractors' => 0,
    'executing' => 0,
    'active_value' => 0,
    'active_projects' => 0,
    'on_track' => 0,
    'needs_attention' => 0,
    'critical' => 0,
    'challenges' => 0,
    'on_track_pct' => 0,
    'needs_pct' => 0,
    'critical_pct' => 0,
];
$notes = [];

if ($portal && $membership !== '') {
    eca_project_backfill($portal, $membership, $member);
    eca_project_hydrate_folder($portal, $member);
    $projects = eca_project_records($portal, $membership);
    $capacity = eca_project_capacity_ensure($portal, $member, eca_project_stats($portal, $membership));
    $industry = eca_project_industry_overview($portal);
    $notes = eca_member_notifications($portal, $member, 20);
    $selectedId = (int) ($_GET['id'] ?? 0);
    if ($selectedId > 0) {
        $current = eca_project_get($portal, $selectedId, $membership);
    }
    if ($current) {
        $files = eca_project_list($portal, $membership, 80, (int) $current['id']);
        $cover = eca_project_cover($portal, (int) $current['id'], $membership);
        $packages = eca_project_packages($portal, (int) $current['id'], $membership);
        $timeline = eca_project_timeline($portal, (int) $current['id'], $membership);
        if (isset($_GET['edit'])) {
            $showForm = $editTarget === '1' || $editTarget === 'profile' || $editTarget === '';
        }
    }
}

$form = $showForm ? array_merge(eca_project_blank(), $current ?: []) : [];
$companyName = trim((string) ($member['registered_name'] ?: $member['name'] ?: 'Member company'));
$membershipNo = trim((string) ($member['membership'] ?? '')) ?: '—';
$initial = strtoupper(substr($companyName, 0, 1));
$messageCount = count($notes);
$progress = $current ? max(0, min(100, (int) ($current['progress'] ?? 0))) : 0;
$health = $current ? trim((string) ($current['health_status'] ?? 'on_track')) : 'on_track';
if (!isset(eca_project_health_options()[$health])) {
    $health = 'on_track';
}
$challenge = $current ? trim((string) ($current['current_challenge'] ?? '')) : '';
$statusKey = $current ? strtoupper((string) ($current['status'] ?? 'ACTIVE')) : 'ACTIVE';
$statusBadge = $statusKey === 'COMPLETED' ? 'is-done' : ($statusKey === 'ON HOLD' ? 'is-hold' : '');
$heroSrc = eca_project_hero_src($cover);
$location = $current ? eca_project_na($current['location'] ?? ($member['region'] ?? ''), $member['region'] ?: '—') : ($member['region'] ?: '—');
$showWorkspace = $current && !$showForm && !$viewAll;
$showList = !$showForm && (!$current || $viewAll);

function eca_pf_h($value): string
{
    return eca_h($value);
}

eca_portal_start('Projects', 'projects', 'is-projects');
?>
        <div class="pf-main">
            <?php if ($notice): ?>
                <p class="pf-notice"><?= eca_pf_h($notice) ?></p>
            <?php endif; ?>

            <div class="pf-crumb">
                <?php if ($showWorkspace || $showForm): ?>
                    <a href="/client/projects.php" aria-label="Back to projects"><i class="fa-solid fa-arrow-left"></i></a>
                <?php endif; ?>
                <span>Projects</span>
            </div>

            <?php if ($showForm): ?>
                <article class="pf-form-card">
                    <div class="pf-card-head">
                        <h2><i class="fa-regular fa-folder-open"></i> <?= !empty($form['id']) ? 'Edit project' : 'Add project' ?></h2>
                    </div>
                    <form class="pf-form" method="post">
                        <input type="hidden" name="csrf_token" value="<?= eca_pf_h($csrf) ?>">
                        <input type="hidden" name="action" value="save-project">
                        <input type="hidden" name="id" value="<?= (int) ($form['id'] ?? 0) ?>">
                        <label>Project title
                            <input type="text" name="title" maxlength="190" required value="<?= eca_pf_h($form['title'] ?? '') ?>">
                        </label>
                        <div class="pf-form-grid">
                            <label>Client
                                <input type="text" name="client_entity" value="<?= eca_pf_h($form['client_entity'] ?? '') ?>">
                            </label>
                            <label>Contract value (E)
                                <input type="text" name="contract_value" value="<?= eca_pf_h($form['contract_value'] ?? '0.00') ?>">
                            </label>
                            <label>Start date
                                <input type="date" name="start_date" value="<?= eca_pf_h($form['start_date'] ?? '') ?>">
                            </label>
                            <label>Expected completion
                                <input type="date" name="finish_date" value="<?= eca_pf_h($form['finish_date'] ?? '') ?>">
                            </label>
                            <label>Contract type
                                <select name="contract_type">
                                    <?php foreach (eca_project_contract_types() as $type): ?>
                                        <option value="<?= eca_pf_h($type) ?>"<?= ($form['contract_type'] ?? '') === $type ? ' selected' : '' ?>><?= eca_pf_h($type) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </label>
                            <label>Location
                                <input type="text" name="location" value="<?= eca_pf_h($form['location'] ?? '') ?>">
                            </label>
                            <label>Current stage
                                <input type="text" name="current_stage" value="<?= eca_pf_h($form['current_stage'] ?? '') ?>">
                            </label>
                            <label>Status
                                <select name="status">
                                    <?php foreach (eca_project_status_options() as $key => $label): ?>
                                        <option value="<?= eca_pf_h($key) ?>"<?= strtoupper((string) ($form['status'] ?? '')) === $key ? ' selected' : '' ?>><?= eca_pf_h($label) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </label>
                            <label>Progress (%)
                                <input type="number" name="progress" min="0" max="100" value="<?= (int) ($form['progress'] ?? 0) ?>">
                            </label>
                            <label>Project manager
                                <input type="text" name="manager_name" value="<?= eca_pf_h($form['manager_name'] ?? '') ?>">
                            </label>
                            <label>Manager phone
                                <input type="text" name="manager_phone" value="<?= eca_pf_h($form['manager_phone'] ?? '') ?>">
                            </label>
                            <label>Manager email
                                <input type="email" name="manager_email" value="<?= eca_pf_h($form['manager_email'] ?? '') ?>">
                            </label>
                        </div>
                        <label>Description
                            <textarea name="description" rows="4"><?= eca_pf_h($form['description'] ?? '') ?></textarea>
                        </label>
                        <input type="hidden" name="health_status" value="<?= eca_pf_h($form['health_status'] ?? 'on_track') ?>">
                        <input type="hidden" name="current_challenge" value="<?= eca_pf_h($form['current_challenge'] ?? '') ?>">
                        <input type="hidden" name="issue_type" value="<?= eca_pf_h($form['issue_type'] ?? '') ?>">
                        <input type="hidden" name="issue_amount" value="<?= eca_pf_h($form['issue_amount'] ?? '0.00') ?>">
                        <input type="hidden" name="issue_days" value="<?= (int) ($form['issue_days'] ?? 0) ?>">
                        <input type="hidden" name="issue_impact" value="<?= eca_pf_h($form['issue_impact'] ?? '') ?>">
                        <input type="hidden" name="eca_action" value="<?= eca_pf_h($form['eca_action'] ?? '') ?>">
                        <input type="hidden" name="eca_action_status" value="<?= eca_pf_h($form['eca_action_status'] ?? '') ?>">
                        <div class="pf-actions">
                            <button class="pf-btn" type="submit">Save project</button>
                            <a class="pf-btn-ghost" href="<?= !empty($form['id']) ? eca_pf_h(eca_project_folder_url((int) $form['id'])) : '/client/projects.php' ?>">Cancel</a>
                        </div>
                    </form>
                </article>

            <?php elseif ($showList): ?>
                <div class="pf-card-head" style="margin-bottom:16px">
                    <h2>Your project folder</h2>
                    <a class="pf-btn-gold" href="/client/projects.php?new=1"><i class="fa-solid fa-plus"></i>&nbsp; Add project</a>
                </div>
                <?php if ($projects): ?>
                    <div class="pf-list">
                        <?php foreach ($projects as $row): ?>
                            <a href="<?= eca_pf_h(eca_project_folder_url((int) $row['id'])) ?>">
                                <strong><?= eca_pf_h($row['title'] ?: 'Untitled project') ?></strong>
                                <span><?= eca_pf_h(eca_project_status_label((string) $row['status'])) ?> · <?= (int) $row['progress'] ?>% · <?= eca_pf_h(eca_project_na($row['location'] ?? '', $member['region'] ?: '—')) ?></span>
                            </a>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <article class="pf-card pf-empty">
                        <p>No projects yet. Add a project to open the folder view used by ECA for progress, capacity and support.</p>
                        <a class="pf-btn-gold" href="/client/projects.php?new=1">Add project</a>
                    </article>
                <?php endif; ?>

            <?php elseif ($showWorkspace): ?>
                <section class="pf-hero">
                    <div class="pf-hero-copy">
                        <h1>
                            <?= eca_pf_h($current['title'] ?: 'Untitled project') ?>
                            <span class="pf-badge <?= eca_pf_h($statusBadge) ?>"><?= eca_pf_h(eca_project_status_label((string) $current['status'])) ?> Project</span>
                        </h1>
                        <p class="pf-company"><?= eca_pf_h($companyName) ?> &nbsp;|&nbsp; <?= eca_pf_h($membershipNo) ?></p>
                        <div class="pf-stats">
                            <div class="pf-stat">
                                <i class="fa-regular fa-building"></i>
                                <div>
                                    <span>Client</span>
                                    <strong><?= eca_pf_h(eca_project_na($current['client_entity'] ?? '', '—')) ?></strong>
                                </div>
                            </div>
                            <div class="pf-stat">
                                <i class="fa-solid fa-coins"></i>
                                <div>
                                    <span>Contract Value</span>
                                    <strong><?= eca_pf_h(eca_project_folder_money($current['contract_value'] ?? 0)) ?></strong>
                                </div>
                            </div>
                            <div class="pf-stat">
                                <i class="fa-regular fa-calendar"></i>
                                <div>
                                    <span>Start Date</span>
                                    <strong><?= eca_pf_h(eca_project_format_month($current['start_date'] ?? '')) ?></strong>
                                </div>
                            </div>
                            <div class="pf-stat">
                                <i class="fa-regular fa-calendar-check"></i>
                                <div>
                                    <span>Expected Completion</span>
                                    <strong><?= eca_pf_h(eca_project_format_month($current['finish_date'] ?? '')) ?></strong>
                                </div>
                            </div>
                            <div class="pf-stat">
                                <i class="fa-solid fa-file-signature"></i>
                                <div>
                                    <span>Contract Type</span>
                                    <strong><?= eca_pf_h(eca_project_na($current['contract_type'] ?? '', 'Lump Sum')) ?></strong>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div>
                        <div class="pf-hero-photo">
                            <img src="<?= eca_pf_h($heroSrc) ?>" alt="<?= eca_pf_h($current['title'] ?: 'Project site') ?>">
                        </div>
                        <p class="pf-loc"><i class="fa-solid fa-location-dot"></i> <?= eca_pf_h($location) ?></p>
                    </div>
                </section>

                <?php if (count($projects) > 1): ?>
                    <div class="pf-tabs" style="border:0;margin-top:-8px">
                        <?php foreach ($projects as $row): ?>
                            <a class="<?= (int) $row['id'] === (int) $current['id'] ? 'is-active' : '' ?>" href="<?= eca_pf_h(eca_project_folder_url((int) $row['id'], $tab)) ?>"><?= eca_pf_h($row['title'] ?: 'Untitled') ?></a>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>

                <nav class="pf-tabs" aria-label="Project sections">
                    <?php foreach (eca_project_folder_tabs() as $key => $label): ?>
                        <a class="<?= $tab === $key ? 'is-active' : '' ?>" href="<?= eca_pf_h(eca_project_folder_url((int) $current['id'], $key)) ?>"><?= eca_pf_h($label) ?></a>
                    <?php endforeach; ?>
                </nav>

                <div class="pf-grid<?= $tab !== 'overview' ? ' is-tab-' . eca_pf_h($tab) : '' ?>">
                    <article class="pf-card pf-card-profile">
                        <div class="pf-card-head">
                            <h2><i class="fa-regular fa-building"></i> 1. Project Profile</h2>
                            <a class="pf-link" href="<?= eca_pf_h(eca_project_folder_url((int) $current['id'], 'overview', ['edit' => 'profile'])) ?>">Edit</a>
                        </div>
                        <div class="pf-facts">
                            <p><?= nl2br(eca_pf_h(eca_project_na($current['description'] ?? '', 'Add a project description under Edit.'))) ?></p>
                            <div>
                                <div class="pf-kv">
                                    <i class="fa-regular fa-calendar"></i>
                                    <div><span>Start Date</span><strong><?= eca_pf_h(eca_project_format_month($current['start_date'] ?? '')) ?></strong></div>
                                </div>
                                <div class="pf-kv" style="margin-top:10px">
                                    <i class="fa-regular fa-calendar-check"></i>
                                    <div><span>Expected Completion</span><strong><?= eca_pf_h(eca_project_format_month($current['finish_date'] ?? '')) ?></strong></div>
                                </div>
                            </div>
                            <div class="pf-kv">
                                <i class="fa-regular fa-building"></i>
                                <div><span>Client</span><strong><?= eca_pf_h(eca_project_na($current['client_entity'] ?? '', '—')) ?></strong></div>
                            </div>
                            <div class="pf-kv">
                                <i class="fa-solid fa-layer-group"></i>
                                <div><span>Current Stage</span><strong><?= eca_pf_h(eca_project_na($current['current_stage'] ?? '', '—')) ?></strong></div>
                            </div>
                            <div class="pf-kv">
                                <i class="fa-solid fa-coins"></i>
                                <div><span>Contract Value</span><strong><?= eca_pf_h(eca_project_folder_money($current['contract_value'] ?? 0)) ?></strong></div>
                            </div>
                            <div class="pf-kv">
                                <i class="fa-regular fa-user"></i>
                                <div>
                                    <span>Project Manager</span>
                                    <strong><?= eca_pf_h(eca_project_na($current['manager_name'] ?? '', '—')) ?></strong>
                                    <span><?= eca_pf_h(trim(($current['manager_phone'] ?? '') . '  ' . ($current['manager_email'] ?? ''))) ?></span>
                                </div>
                            </div>
                            <div class="pf-kv">
                                <i class="fa-solid fa-location-dot"></i>
                                <div><span>Location</span><strong><?= eca_pf_h($location) ?></strong></div>
                            </div>
                            <div class="pf-kv">
                                <i class="fa-solid fa-file-signature"></i>
                                <div><span>Contract Type</span><strong><?= eca_pf_h(eca_project_na($current['contract_type'] ?? '', 'Lump Sum')) ?></strong></div>
                            </div>
                        </div>
                    </article>

                    <article class="pf-card pf-card-progress" id="progress">
                        <div class="pf-card-head">
                            <h2><i class="fa-solid fa-chart-pie"></i> 2. Project Progress</h2>
                            <button class="pf-link" type="button" data-pf-toggle="progressForm">Update Progress</button>
                        </div>
                        <div class="pf-progress-wrap">
                            <div class="pf-donut" aria-label="Overall progress <?= (int) $progress ?> percent">
                                <svg viewBox="0 0 36 36">
                                    <circle class="pf-donut-track" cx="18" cy="18" r="15.9155" fill="none" stroke-width="3.2"></circle>
                                    <circle class="pf-donut-value" cx="18" cy="18" r="15.9155" fill="none" stroke-width="3.2" stroke-linecap="round" stroke-dasharray="<?= (int) $progress ?> <?= 100 - (int) $progress ?>" transform="rotate(-90 18 18)"></circle>
                                </svg>
                                <div class="pf-donut-label">
                                    <strong><?= (int) $progress ?>%</strong>
                                    <span>Overall Progress</span>
                                </div>
                            </div>
                            <ul class="pf-packages">
                                <?php foreach ($packages as $package): ?>
                                    <li>
                                        <b><?= eca_pf_h($package['name']) ?></b>
                                        <span class="pf-dot is-<?= eca_pf_h($package['status']) ?>"><?= eca_pf_h(eca_project_package_status_options()[$package['status']] ?? $package['status']) ?></span>
                                    </li>
                                <?php endforeach; ?>
                            </ul>
                        </div>
                        <form class="pf-form" id="progressForm" method="post" hidden>
                            <input type="hidden" name="csrf_token" value="<?= eca_pf_h($csrf) ?>">
                            <input type="hidden" name="action" value="update-progress">
                            <input type="hidden" name="id" value="<?= (int) $current['id'] ?>">
                            <input type="hidden" name="tab" value="<?= eca_pf_h($tab) ?>">
                            <label>Overall progress (%)
                                <input type="number" name="progress" min="0" max="100" value="<?= (int) $progress ?>">
                            </label>
                            <div class="pf-form-grid">
                                <?php foreach ($packages as $package): ?>
                                    <label><?= eca_pf_h($package['name']) ?>
                                        <select name="package[<?= (int) $package['id'] ?>]">
                                            <?php foreach (eca_project_package_status_options() as $key => $label): ?>
                                                <option value="<?= eca_pf_h($key) ?>"<?= $package['status'] === $key ? ' selected' : '' ?>><?= eca_pf_h($label) ?></option>
                                            <?php endforeach; ?>
                                        </select>
                                    </label>
                                <?php endforeach; ?>
                            </div>
                            <div class="pf-actions">
                                <button class="pf-btn" type="submit">Save progress</button>
                            </div>
                        </form>
                    </article>

                    <article class="pf-card pf-card-health pf-health">
                        <div class="pf-card-head">
                            <h2><i class="fa-regular fa-circle-question"></i> 3. Project Health</h2>
                        </div>
                        <form class="pf-form" method="post" data-pf-autosave>
                            <input type="hidden" name="csrf_token" value="<?= eca_pf_h($csrf) ?>">
                            <input type="hidden" name="action" value="update-health">
                            <input type="hidden" name="id" value="<?= (int) $current['id'] ?>">
                            <input type="hidden" name="tab" value="<?= eca_pf_h($tab) ?>">
                            <input type="hidden" name="contract_value" value="<?= eca_pf_h($current['contract_value'] ?? '0') ?>">
                            <input type="hidden" name="location" value="<?= eca_pf_h($current['location'] ?? '') ?>">
                            <input type="hidden" name="current_stage" value="<?= eca_pf_h($current['current_stage'] ?? '') ?>">
                            <input type="hidden" name="manager_name" value="<?= eca_pf_h($current['manager_name'] ?? '') ?>">
                            <input type="hidden" name="manager_phone" value="<?= eca_pf_h($current['manager_phone'] ?? '') ?>">
                            <input type="hidden" name="manager_email" value="<?= eca_pf_h($current['manager_email'] ?? '') ?>">
                            <p>How is the project doing?</p>
                            <div class="pf-radios pf-inline">
                                <?php foreach (eca_project_health_options() as $key => $label): ?>
                                    <label>
                                        <input type="radio" name="health_status" value="<?= eca_pf_h($key) ?>"<?= $health === $key ? ' checked' : '' ?>>
                                        <span class="pf-dot is-<?= eca_pf_h($key) ?>"><?= eca_pf_h($label) ?></span>
                                    </label>
                                <?php endforeach; ?>
                            </div>
                            <p>Current challenge</p>
                            <div class="pf-challenges">
                                <?php foreach (eca_project_challenge_options() as $key => $label): ?>
                                    <label>
                                        <input type="radio" name="current_challenge" value="<?= eca_pf_h($key) ?>"<?= $challenge === $key ? ' checked' : '' ?>>
                                        <?= eca_pf_h($label) ?>
                                    </label>
                                <?php endforeach; ?>
                            </div>
                            <div class="pf-issue">
                                <div class="pf-card-head">
                                    <h4>Issue details</h4>
                                    <button class="pf-link" type="button" data-pf-toggle="issueFields">Edit</button>
                                </div>
                                <div class="pf-issue-read">
                                    <p><strong><?= eca_pf_h(eca_project_na($current['issue_type'] ?? '', '—')) ?></strong></p>
                                    <div class="pf-issue-grid">
                                        <div><span>Amount</span><strong><?= eca_pf_h(eca_project_folder_money($current['issue_amount'] ?? 0)) ?></strong></div>
                                        <div><span>Outstanding</span><strong><?= (int) ($current['issue_days'] ?? 0) ?> days</strong></div>
                                        <div><span>Impact</span><strong><?= eca_pf_h(eca_project_na($current['issue_impact'] ?? '', '—')) ?></strong></div>
                                        <div><span>ECA action</span><strong><?= eca_pf_h(eca_project_na($current['eca_action'] ?? '', '—')) ?></strong></div>
                                    </div>
                                    <?php if (!empty($current['eca_action_status'])): ?>
                                        <span class="pf-pill"><?= eca_pf_h($current['eca_action_status']) ?></span>
                                    <?php endif; ?>
                                </div>
                                <div id="issueFields" hidden>
                                    <label>Type
                                        <input type="text" name="issue_type" value="<?= eca_pf_h($current['issue_type'] ?? '') ?>">
                                    </label>
                                    <div class="pf-issue-grid">
                                        <label>Amount
                                            <input type="text" name="issue_amount" value="<?= eca_pf_h($current['issue_amount'] ?? '0.00') ?>">
                                        </label>
                                        <label>Outstanding days
                                            <input type="number" name="issue_days" min="0" value="<?= (int) ($current['issue_days'] ?? 0) ?>">
                                        </label>
                                        <label>Impact
                                            <input type="text" name="issue_impact" value="<?= eca_pf_h($current['issue_impact'] ?? '') ?>">
                                        </label>
                                        <label>ECA action
                                            <input type="text" name="eca_action" value="<?= eca_pf_h($current['eca_action'] ?? '') ?>">
                                        </label>
                                    </div>
                                    <label>Status
                                        <select name="eca_action_status">
                                            <option value="">Select</option>
                                            <?php foreach (eca_project_action_status_options() as $opt): ?>
                                                <option value="<?= eca_pf_h($opt) ?>"<?= ($current['eca_action_status'] ?? '') === $opt ? ' selected' : '' ?>><?= eca_pf_h($opt) ?></option>
                                            <?php endforeach; ?>
                                        </select>
                                    </label>
                                    <div class="pf-actions" style="margin-top:12px">
                                        <button class="pf-btn" type="submit">Save health</button>
                                    </div>
                                </div>
                            </div>
                        </form>
                    </article>

                    <article class="pf-card pf-card-capacity">
                        <div class="pf-card-head">
                            <h2><i class="fa-solid fa-users"></i> 4. Contractor Capacity Profile</h2>
                            <button class="pf-link" type="button" data-pf-toggle="capacityForm">Edit</button>
                        </div>
                        <div class="pf-mini">
                            <div>
                                <h4>Company Size</h4>
                                <ul>
                                    <li><span>Employees</span><strong><?= (int) ($capacity['employees'] ?? 0) ?></strong></li>
                                    <li><span>Technical Staff</span><strong><?= (int) ($capacity['technical_staff'] ?? 0) ?></strong></li>
                                    <li><span>Skilled Workers</span><strong><?= (int) ($capacity['skilled_workers'] ?? 0) ?></strong></li>
                                    <li><span>Administrative</span><strong><?= (int) ($capacity['administrative'] ?? 0) ?></strong></li>
                                </ul>
                            </div>
                            <div>
                                <h4>Equipment</h4>
                                <ul>
                                    <li><span>Excavators</span><strong><?= (int) ($capacity['excavators'] ?? 0) ?></strong></li>
                                    <li><span>TLB</span><strong><?= (int) ($capacity['tlb'] ?? 0) ?></strong></li>
                                    <li><span>Trucks</span><strong><?= (int) ($capacity['trucks'] ?? 0) ?></strong></li>
                                    <li><span>Other</span><strong><?= (int) ($capacity['other_equipment'] ?? 0) ?></strong></li>
                                </ul>
                            </div>
                            <div>
                                <h4>Specialisation</h4>
                                <div class="pf-specs">
                                    <span><i class="fa-solid <?= !empty($capacity['spec_building']) ? 'fa-check' : 'fa-minus' ?>"></i> Building</span>
                                    <span><i class="fa-solid <?= !empty($capacity['spec_roads']) ? 'fa-check' : 'fa-minus' ?>"></i> Roads</span>
                                    <span><i class="fa-solid <?= !empty($capacity['spec_civil']) ? 'fa-check' : 'fa-minus' ?>"></i> Civil Works</span>
                                    <span><i class="fa-solid <?= !empty($capacity['spec_water']) ? 'fa-check' : 'fa-minus' ?>"></i> Water &amp; Sanitation</span>
                                </div>
                            </div>
                            <div>
                                <h4>Experience</h4>
                                <ul>
                                    <li><span>Completed Projects</span><strong><?= (int) ($capacity['completed_projects'] ?? 0) ?></strong></li>
                                    <li><span>Total Project Value</span><strong><?= eca_pf_h($capacity['total_project_value'] ?? '—') ?></strong></li>
                                    <li><span>Private Projects</span><strong><?= (int) ($capacity['private_projects'] ?? 0) ?></strong></li>
                                    <li><span>Years Operating</span><strong><?= (int) ($capacity['years_operating'] ?? 0) ?></strong></li>
                                </ul>
                            </div>
                            <div>
                                <h4>Current Workload</h4>
                                <ul>
                                    <li><span>Active Projects</span><strong><?= (int) ($capacity['active_projects'] ?? 0) ?></strong></li>
                                    <li><span>Active Value</span><strong><?= eca_pf_h($capacity['active_value'] ?? '—') ?></strong></li>
                                    <li><span>Projects Requiring Attention</span><strong><?= (int) ($capacity['attention_projects'] ?? 0) ?></strong></li>
                                </ul>
                            </div>
                        </div>
                        <form class="pf-form" id="capacityForm" method="post" hidden>
                            <input type="hidden" name="csrf_token" value="<?= eca_pf_h($csrf) ?>">
                            <input type="hidden" name="action" value="save-capacity">
                            <input type="hidden" name="project_id" value="<?= (int) $current['id'] ?>">
                            <input type="hidden" name="tab" value="<?= eca_pf_h($tab) ?>">
                            <div class="pf-form-grid">
                                <?php
                                $capFields = [
                                    'employees' => 'Employees',
                                    'technical_staff' => 'Technical staff',
                                    'skilled_workers' => 'Skilled workers',
                                    'administrative' => 'Administrative',
                                    'excavators' => 'Excavators',
                                    'tlb' => 'TLB',
                                    'trucks' => 'Trucks',
                                    'other_equipment' => 'Other equipment',
                                    'completed_projects' => 'Completed projects',
                                    'private_projects' => 'Private projects',
                                    'years_operating' => 'Years operating',
                                    'active_projects' => 'Active projects',
                                    'attention_projects' => 'Projects requiring attention',
                                ];
                                foreach ($capFields as $name => $label):
                                ?>
                                    <label><?= eca_pf_h($label) ?>
                                        <input type="number" name="<?= eca_pf_h($name) ?>" min="0" value="<?= (int) ($capacity[$name] ?? 0) ?>">
                                    </label>
                                <?php endforeach; ?>
                                <label>Total project value
                                    <input type="text" name="total_project_value" value="<?= eca_pf_h($capacity['total_project_value'] ?? '') ?>">
                                </label>
                                <label>Active value
                                    <input type="text" name="active_value" value="<?= eca_pf_h($capacity['active_value'] ?? '') ?>">
                                </label>
                            </div>
                            <div class="pf-challenges">
                                <label><input type="checkbox" name="spec_building" value="1"<?= !empty($capacity['spec_building']) ? ' checked' : '' ?>> Building</label>
                                <label><input type="checkbox" name="spec_roads" value="1"<?= !empty($capacity['spec_roads']) ? ' checked' : '' ?>> Roads</label>
                                <label><input type="checkbox" name="spec_civil" value="1"<?= !empty($capacity['spec_civil']) ? ' checked' : '' ?>> Civil Works</label>
                                <label><input type="checkbox" name="spec_water" value="1"<?= !empty($capacity['spec_water']) ? ' checked' : '' ?>> Water &amp; Sanitation</label>
                            </div>
                            <button class="pf-btn" type="submit">Save capacity</button>
                        </form>
                    </article>

                    <article class="pf-card pf-card-eca">
                        <div class="pf-card-head">
                            <h2><i class="fa-solid fa-chart-column"></i> 5. ECA View — Industry Overview</h2>
                            <span class="pf-link">Live local totals</span>
                        </div>
                        <h3 style="margin:0 0 10px;font-size:14px">ECA Contractor Industry Overview</h3>
                        <div class="pf-eca-stats">
                            <div>
                                <span>Registered Contractors</span>
                                <strong><?= number_format((int) $industry['registered']) ?></strong>
                            </div>
                            <div>
                                <span>Active Contractors</span>
                                <strong><?= number_format((int) $industry['active_contractors']) ?></strong>
                            </div>
                            <div>
                                <span>Contractors Executing Projects</span>
                                <strong><?= number_format((int) $industry['executing']) ?></strong>
                            </div>
                        </div>
                        <div class="pf-eca-row">
                            <div class="pf-donut" aria-label="Active projects">
                                <svg viewBox="0 0 36 36">
                                    <circle class="pf-donut-track" cx="18" cy="18" r="15.9155" fill="none" stroke-width="3.2"></circle>
                                    <circle class="pf-donut-value" cx="18" cy="18" r="15.9155" fill="none" stroke-width="3.2" stroke-dasharray="<?= (int) $industry['on_track_pct'] ?> <?= 100 - (int) $industry['on_track_pct'] ?>" transform="rotate(-90 18 18)"></circle>
                                </svg>
                                <div class="pf-donut-label">
                                    <strong><?= number_format((int) $industry['active_projects']) ?></strong>
                                    <span>Active Projects</span>
                                </div>
                            </div>
                            <ul class="pf-legend">
                                <li><span class="pf-dot is-on_track"></span> On track <?= (int) $industry['on_track'] ?> (<?= (int) $industry['on_track_pct'] ?>%)</li>
                                <li><span class="pf-dot is-needs_attention"></span> Needs attention <?= (int) $industry['needs_attention'] ?> (<?= (int) $industry['needs_pct'] ?>%)</li>
                                <li><span class="pf-dot is-critical"></span> Critical <?= (int) $industry['critical'] ?> (<?= (int) $industry['critical_pct'] ?>%)</li>
                                <li><span class="pf-dot"></span> <?= (int) $industry['challenges'] ?> project<?= (int) $industry['challenges'] === 1 ? '' : 's' ?> with a related challenge</li>
                            </ul>
                        </div>
                        <p class="pf-support" style="margin-top:10px">E<?= number_format((float) $industry['active_value'], 0, '.', ',') ?> active project value on this local database.</p>
                    </article>

                    <article class="pf-card pf-card-advocacy">
                        <div class="pf-card-head">
                            <h2>6. Advocacy &amp; Support</h2>
                        </div>
                        <p class="pf-support">ECA uses this information to provide targeted support and advocate for members.</p>
                        <div class="pf-support-grid">
                            <div><i class="fa-solid fa-comments"></i> Address payment delays</div>
                            <div><i class="fa-solid fa-handshake"></i> Engage with clients &amp; authorities</div>
                            <div><i class="fa-solid fa-lightbulb"></i> Facilitate solutions</div>
                            <div><i class="fa-solid fa-bullhorn"></i> Strengthen industry voice</div>
                        </div>
                    </article>

                    <article class="pf-card pf-card-timeline">
                        <div class="pf-card-head">
                            <h2>7. Project Timeline</h2>
                        </div>
                        <ul class="pf-tl">
                            <?php foreach ($timeline as $event): ?>
                                <li class="is-<?= eca_pf_h($event['status']) ?>">
                                    <time><?= eca_pf_h($event['event_date'] ?: '') ?></time>
                                    <i></i>
                                    <span><?= eca_pf_h($event['title']) ?></span>
                                </li>
                            <?php endforeach; ?>
                            <?php if (!$timeline): ?>
                                <li><time>—</time><i></i><span>Add project dates to build a timeline.</span></li>
                            <?php endif; ?>
                        </ul>
                    </article>

                    <article class="pf-card pf-bigger pf-card-bigger">
                        <h3>The Bigger Picture</h3>
                        <p>The Projects Folder is part of the ECA Digital Contractor Hub, helping ECA understand, support and advocate for members and the construction industry.</p>
                        <div class="pf-flow">
                            <div><span><i class="fa-regular fa-user"></i></span>Contractor Profile Who are you and what can you do?</div>
                            <div><span><i class="fa-solid fa-chart-simple"></i></span>Capacity What work are you doing?</div>
                            <div class="is-gold"><span><i class="fa-regular fa-folder"></i></span>Projects Folder What work are you doing?</div>
                            <div><span><i class="fa-solid fa-bars-progress"></i></span>Project Progress How is it progressing?</div>
                            <div><span><i class="fa-solid fa-triangle-exclamation"></i></span>Challenges What is preventing progress?</div>
                            <div><span><i class="fa-solid fa-headset"></i></span>ECA Support Where can ECA assist?</div>
                            <div><span><i class="fa-solid fa-database"></i></span>Aggregated Data What is happening across the industry?</div>
                        </div>
                    </article>

                    <article class="pf-card pf-card-documents">
                        <div class="pf-card-head">
                            <h2><i class="fa-regular fa-folder-open"></i> Documents</h2>
                        </div>
                        <form class="pf-form" method="post" enctype="multipart/form-data">
                            <input type="hidden" name="csrf_token" value="<?= eca_pf_h($csrf) ?>">
                            <input type="hidden" name="action" value="upload">
                            <input type="hidden" name="project_id" value="<?= (int) $current['id'] ?>">
                            <div class="pf-form-grid">
                                <label>Document title
                                    <input type="text" name="title" maxlength="190" placeholder="e.g. Payment certificate 3">
                                </label>
                                <label>File
                                    <input type="file" name="project_file" accept=".pdf,.png,.jpg,.jpeg,.doc,.docx,.xls,.xlsx" required>
                                </label>
                            </div>
                            <button class="pf-btn" type="submit">Upload</button>
                        </form>
                        <ul class="pf-files">
                            <?php foreach ($files as $file): ?>
                                <li>
                                    <div>
                                        <strong><?= eca_pf_h($file['title'] ?: $file['original_name']) ?></strong>
                                        <div class="pf-loc"><?= eca_pf_h($file['original_name']) ?> · <?= eca_pf_h(eca_project_size_label((int) ($file['file_size'] ?? 0))) ?></div>
                                    </div>
                                    <div class="pf-actions">
                                        <a class="pf-btn" href="/client/project-file.php?id=<?= (int) $file['id'] ?>">Open</a>
                                        <form method="post" onsubmit="return confirm('Remove this file from the project?');">
                                            <input type="hidden" name="csrf_token" value="<?= eca_pf_h($csrf) ?>">
                                            <input type="hidden" name="action" value="delete-file">
                                            <input type="hidden" name="id" value="<?= (int) $file['id'] ?>">
                                            <input type="hidden" name="project_id" value="<?= (int) $current['id'] ?>">
                                            <button class="pf-btn-ghost" type="submit">Remove</button>
                                        </form>
                                    </div>
                                </li>
                            <?php endforeach; ?>
                            <?php if (!$files): ?>
                                <li class="pf-empty">No documents yet. Upload a site photo or certificate to this folder.</li>
                            <?php endif; ?>
                        </ul>
                        <form method="post" onsubmit="return confirm('Delete this project and its evidence files?');" style="margin-top:16px">
                            <input type="hidden" name="csrf_token" value="<?= eca_pf_h($csrf) ?>">
                            <input type="hidden" name="action" value="delete-project">
                            <input type="hidden" name="id" value="<?= (int) $current['id'] ?>">
                            <button class="pf-btn-ghost" type="submit">Delete project</button>
                        </form>
                    </article>
                </div>
            <?php endif; ?>
        </div>
<script src="/js/projects-folder.js?v=1" defer></script>
<?php eca_portal_end(); ?>
