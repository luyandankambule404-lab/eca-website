<?php
require_once __DIR__ . "/config.php";
require_once __DIR__ . "/helpers.php";
require_once dirname(__DIR__, 2) . "/includes/hub.php";

$role           = strtoupper($_SESSION['role'] ?? '');
$hubPreviewRole = function_exists('eca_hub_preview_staff_role') ? eca_hub_preview_staff_role() : null;
$is_superadmin  = (!empty($_SESSION['user_id']) && ($role === 'SUPPERADMIN')) || $hubPreviewRole === 'SUPPERADMIN';
$is_admin       = !empty($_SESSION['user_id']) && ($role === 'ADMIN');
$is_officer     = (!empty($_SESSION['user_id']) && ($role === 'OFFICER')) || $hubPreviewRole === 'OFFICER';
$is_contractor  = !empty($_SESSION['user_id']) && (function_exists('eca_is_cpd_learner_role') ? eca_is_cpd_learner_role($role) : $role === 'CONTRACTOR');
$is_website_admin = !empty($_SESSION['eca_admin']);
$is_staff       = $is_superadmin || $is_admin || $is_officer;
$current        = function_exists('eca_hub_current_script') ? eca_hub_current_script() : basename($_SERVER['PHP_SELF']);
$requestPath    = function_exists('eca_hub_request_path') ? eca_hub_request_path() : '';
$learnerActive  = function_exists('eca_hub_is_learner_portal_page')
    ? eca_hub_is_learner_portal_page()
    : str_contains($requestPath, 'learner-portal.php');

$fullName  = trim((string) ($_SESSION['full_name'] ?? 'User'));
if (function_exists('eca_hub_can_open_portals') && eca_hub_can_open_portals()) {
    $hubAdmin = function_exists('eca_hub_session_admin') ? eca_hub_session_admin() : null;
    $hubName = trim((string) ($hubAdmin['name'] ?? ''));
    if ($hubName !== '') {
        $fullName = $hubName;
    }
}
$firstName = explode(' ', $fullName)[0];
$chromeRole = $hubPreviewRole ?: $role;
$isCpdAdminDash = $is_superadmin && str_contains($requestPath, '/cpd/admin') && $current === 'dashboard.php';
$superAdminPageTitles = [
    'dashboard.php' => 'Command Centre',
    'applications.php' => 'Applications',
    'application.php' => 'Application',
    'courses.php' => 'Courses',
    'payment.php' => 'Payments',
    'learners.php' => 'Participants',
    'course_students.php' => 'Attendance',
    'course_resources.php' => 'Resource Library',
    'announcement.php' => 'Announcements',
    'feedback.php' => 'Feedback',
    'support.php' => 'Support',
    'users.php' => 'Users',
    'cpd_reports.php' => 'Reports',
    'reports.php' => 'Reports',
    'attendance.php' => 'Attendance',
    'home.php' => 'Command Centre',
];
if ($chromeRole === 'SUPPERADMIN') {
    $dashLink = "/cpd/admin/dashboard.php";
    $hubTitle = $superAdminPageTitles[$current] ?? 'Super Admin';
    $roleLabel = 'Super Admin';
    $fullName = function_exists('eca_hub_super_admin_display_name')
        ? eca_hub_super_admin_display_name($fullName)
        : (preg_match('/^CPD\s+/i', $fullName) ? trim((string) preg_replace('/^CPD\s+/i', '', $fullName)) : $fullName);
    if ($fullName === '' || strcasecmp($fullName, 'SuperAdmin') === 0) {
        $fullName = 'Super Admin';
    }
} elseif ($chromeRole === 'ADMIN') {
    $dashLink = "/cpd/admin/course_students.php";
    $hubTitle = 'Admin Hub';
    $roleLabel = 'Admin';
} elseif ($chromeRole === 'OFFICER') {
    $dashLink = "/cpd/admin/applications.php";
    $hubTitle = 'Officer Hub';
    $roleLabel = 'Officer';
} else {
    $dashLink = "/cpd/contractor/dashboard.php";
    $hubTitle = 'Learner Portal';
    $roleLabel = 'Learner';
}
$is_learner_shell = $is_contractor && !$is_staff;
$GLOBALS['eca_hub_shell'] = false;
$bodyClass = [];
if ($is_staff || $is_contractor || $is_website_admin) {
    $bodyClass[] = 'hub-root';
}
if ($is_learner_shell) {
    $bodyClass[] = 'hub-learner';
}
if ($is_superadmin) {
    $bodyClass[] = 'hub-admin';
    $bodyClass[] = 'is-super-admin';
    $bodyClass[] = $isCpdAdminDash ? 'is-admin-dash' : 'is-admin-page';
}
?>
<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title><?= $is_learner_shell ? 'Learner Portal | ECA' : ($is_superadmin ? ($isCpdAdminDash ? 'Command Centre | ECA' : e($hubTitle) . ' | ECA') : 'ECA CPD System') ?></title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
<link href="/css/dashboard.css" rel="stylesheet">
<?php eca_hub_assets(); ?>
<?php if ($is_superadmin): ?>
<link rel="stylesheet" href="/css/admin-command.css?v=20261007-dash3">
<?php endif; ?>
</head>
<body<?= $bodyClass ? ' class="' . e(implode(' ', $bodyClass)) . '"' : '' ?>>

<?php if ($is_staff || $is_contractor || $is_website_admin): ?>
<?php $GLOBALS['eca_hub_shell'] = true; ?>
<div class="overlay" onclick="document.body.classList.remove('sidebar-open')"></div>
<div class="hub-page">
  <div class="hub-shell">
    <header class="hub-topbar">
      <a class="hub-brand" href="/index.php">
        <img src="/img/ecalogo.png" class="brand-logo" alt="ECA Logo" onerror="this.src='/img/header1.jpg'">
      </a>
      <div class="hub-title-wrap">
        <?php if ($is_superadmin): ?>
        <p class="hub-title-kicker">Super Admin</p>
        <?php if ($isCpdAdminDash): ?>
        <p class="hub-title">Command Centre</p>
        <?php else: ?>
        <h1 class="hub-title"><?= e($hubTitle) ?></h1>
        <?php endif; ?>
        <?php else: ?>
        <h1 class="hub-title"><?= e($hubTitle) ?></h1>
        <?php endif; ?>
      </div>
      <div class="hub-top-actions header-actions">
        <button class="btn btn-light mobile-toggle" type="button" onclick="toggleSidebar()" aria-label="Menu">
          <i class="fa fa-bars"></i>
        </button>
        <a class="hub-home-link header-home-link" href="/index.php">Home</a>
        <?php eca_origin_dashboard_back('top'); ?>
        <div class="dropdown">
          <a class="hub-chip user-profile dropdown-toggle" href="#" data-bs-toggle="dropdown" aria-expanded="false">
            <span class="hub-avatar-sm avatar-circle"><i class="fa-solid fa-user"></i></span>
            <?= e($roleLabel) ?>
          </a>
          <ul class="dropdown-menu dropdown-menu-end shadow-lg profile-menu">
            <li class="px-3 py-2 small text-muted">Signed in as <strong><?= e($fullName) ?></strong></li>
            <li><hr class="dropdown-divider"></li>
            <li><a class="dropdown-item" href="<?= $dashLink ?>"><i class="fa-solid fa-gauge-high me-2"></i>Dashboard</a></li>
            <li><a class="dropdown-item" href="/index.php"><i class="fa-solid fa-house me-2"></i>Home</a></li>
            <?php if (function_exists('eca_hub_can_open_portals') && eca_hub_can_open_portals()): ?>
            <li><a class="dropdown-item" href="/admin/index.php"><i class="fa-solid fa-arrow-left me-2"></i>Back to Admin Hub</a></li>
            <li><a class="dropdown-item text-danger" href="/cpd/logout.php"><i class="fa-solid fa-right-from-bracket me-2"></i>Leave portal</a></li>
            <?php else: ?>
            <li><a class="dropdown-item text-danger" href="/cpd/logout.php"><i class="fa-solid fa-right-from-bracket me-2"></i>Logout</a></li>
            <?php endif; ?>
          </ul>
        </div>
      </div>
    </header>
    <?php if (($is_staff || $is_website_admin) && function_exists('eca_db_mode_banner')) { eca_db_mode_banner(); } ?>
    <div class="hub-layout dashboard-shell">
      <aside class="hub-sidebar sidebar">
        <div class="hub-welcome">
          <div class="hub-avatar"><?= e(eca_hub_initial($fullName)) ?></div>
          <div>
            <p class="hub-welcome-label"><?= $is_superadmin ? 'Super Admin' : 'Welcome,' ?></p>
            <p class="hub-welcome-name"><?= e($fullName) ?></p>
            <p class="hub-welcome-role"><?= e($roleLabel) ?></p>
          </div>
        </div>
        <ul class="sidebar-nav hub-nav">
      <?php if ($is_superadmin): ?>
        <?php
          $saGroups = [
              'Training' => [
                  ['/cpd/admin/applications.php', 'Applications', ['applications.php', 'application.php', 'view_application.php']],
                  ['/cpd/admin/courses.php', 'Courses', ['courses.php']],
                  ['/cpd/admin/payment.php', 'Payments', ['payment.php']],
                  ['/cpd/admin/learners.php', 'Participants', ['learners.php']],
                  ['/cpd/admin/course_students.php', 'Attendance', ['course_students.php', 'attendance.php']],
                  ['/cpd/admin/course_resources.php', 'Resource Library', ['course_resources.php']],
              ],
              'Communication' => [
                  ['/cpd/admin/announcement.php', 'Announcements', ['announcement.php']],
                  ['/cpd/admin/feedback.php', 'Feedback', ['feedback.php']],
                  ['/cpd/admin/support.php', 'Support', ['support.php']],
              ],
              'Management' => [
                  ['/cpd/admin/users.php', 'Users', ['users.php']],
                  ['/cpd/admin/cpd_reports.php', 'Reports', ['cpd_reports.php', 'reports.php']],
              ],
          ];
        ?>
        <li><a class="<?= in_array($current, ['dashboard.php', 'home.php'], true) ? 'active' : '' ?>" href="/cpd/admin/dashboard.php">Dashboard</a></li>
        <?php foreach ($saGroups as $groupLabel => $groupItems): ?>
          <?php
            $groupOpen = false;
            foreach ($groupItems as $groupItem) {
                if (in_array($current, $groupItem[2], true)) {
                    $groupOpen = true;
                    break;
                }
            }
          ?>
          <li>
            <details class="hub-nav-group"<?= $groupOpen ? ' open' : '' ?>>
              <summary><?= e($groupLabel) ?></summary>
              <?php foreach ($groupItems as $groupItem): ?>
                <a class="<?= in_array($current, $groupItem[2], true) ? 'active' : '' ?>" href="<?= e($groupItem[0]) ?>"><?= e($groupItem[1]) ?></a>
              <?php endforeach; ?>
            </details>
          </li>
        <?php endforeach; ?>
        <li>
          <details class="hub-nav-group"<?= $learnerActive ? ' open' : '' ?>>
            <summary>Portals</summary>
            <a href="/index.php">Main portal</a>
            <a class="<?= $learnerActive ? 'active' : '' ?>" href="/learner-portal.php"><i class="fa-solid fa-graduation-cap" aria-hidden="true"></i> Learner Portal</a>
            <?php if (function_exists('eca_super_admin_can_open_portals') && eca_super_admin_can_open_portals()): ?>
            <a href="/admin/index.php">Admin Hub</a>
            <a href="/admin/wellness/">Wellness</a>
            <a href="/wellness/">Wellness Hub</a>
            <a href="/client/dashboard.php">Member Hub</a>
            <a href="/client/wellness/">Member Wellness</a>
            <a href="/cpd/officer/dashboard.php">Officer Hub</a>
            <?php endif; ?>
          </details>
        </li>
      <?php endif; ?>

      <?php if ($is_admin): ?>
        <li><a class="<?= $current === 'course_students.php' ? 'active' : '' ?>" href="/cpd/admin/course_students.php">Attendance</a></li>
        <li><a class="<?= $current === 'learners.php' ? 'active' : '' ?>" href="/cpd/admin/learners.php">Participants</a></li>
        <li><a class="<?= $current === 'payment.php' ? 'active' : '' ?>" href="/cpd/admin/payment.php">Payments</a></li>
        <li><a class="<?= $current === 'support.php' ? 'active' : '' ?>" href="/cpd/admin/support.php">Support</a></li>
      <?php endif; ?>

      <?php if ($is_officer): ?>
        <li><a class="<?= $current === 'applications.php' ? 'active' : '' ?>" href="/cpd/admin/applications.php">Applications</a></li>
        <li><a class="<?= $current === 'course_students.php' ? 'active' : '' ?>" href="/cpd/admin/course_students.php">Attendance</a></li>
        <li><a class="<?= $current === 'learners.php' ? 'active' : '' ?>" href="/cpd/admin/learners.php">Participants</a></li>
      <?php endif; ?>

      <?php if ($is_staff && !$is_superadmin): ?>
        <li class="nav-item-unique">
          <a class="nav-link-unique" href="/index.php">Main portal</a>
        </li>
      <?php endif; ?>

      <?php if (($is_contractor || $is_website_admin) && !$is_staff): ?>
        <li><a class="<?= $current === 'dashboard.php' ? 'active' : '' ?>" href="/cpd/contractor/dashboard.php"><i class="fa-solid fa-gauge-high" aria-hidden="true"></i> Dashboard</a></li>
        <li><a class="<?= $current === 'courses.php' ? 'active' : '' ?>" href="/cpd/contractor/courses.php"><i class="fa-solid fa-book-open" aria-hidden="true"></i> Courses</a></li>
        <li><a class="<?= $current === 'applications.php' ? 'active' : '' ?>" href="/cpd/contractor/applications.php"><i class="fa-solid fa-file-lines" aria-hidden="true"></i> Applications</a></li>
        <li><a class="<?= $current === 'transcript.php' ? 'active' : '' ?>" href="/cpd/contractor/transcript.php"><i class="fa-solid fa-award" aria-hidden="true"></i> Transcript</a></li>
      <?php endif; ?>

        <?php if (!$is_superadmin): ?>
        <li><a class="<?= $learnerActive ? 'active' : '' ?>" href="/learner-portal.php"><i class="fa-solid fa-graduation-cap" aria-hidden="true"></i> Learner Portal</a></li>
        <?php if (function_exists('eca_super_admin_can_open_portals') && eca_super_admin_can_open_portals()): ?>
        <li><a href="/admin/index.php">Admin Hub</a></li>
        <li><a href="/admin/wellness/">Wellness</a></li>
        <li><a href="/wellness/">Wellness Hub</a></li>
        <li><a href="/client/dashboard.php">Member Hub</a></li>
        <li><a href="/client/wellness/">Member Wellness</a></li>
        <li><a href="/cpd/officer/dashboard.php">Officer Hub</a></li>
        <?php endif; ?>
        <?php endif; ?>
        <?php eca_origin_dashboard_back('nav'); ?>
        <li><a href="/index.php">Home</a></li>
        <li class="logout"><a href="/cpd/logout.php"><?= (function_exists('eca_hub_can_open_portals') && eca_hub_can_open_portals()) ? 'Leave portal' : 'Logout' ?></a></li>
        </ul>
      </aside>
      <main class="hub-main dashboard-content">
        <div class="hub-hello">
          <h1 class="hub-hello-title"><?= e(eca_hub_hello($fullName)) ?></h1>
          <p><?= !empty($is_learner_shell) ? 'Courses, applications and your CPD record in one place.' : 'Keep learning, keep growing. Build your future with ECA.' ?></p>
        </div>
<?php else: ?>
<div class="container py-4">
<?php endif; ?>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
function toggleSidebar(){
  document.body.classList.toggle('sidebar-open');
}
</script>
