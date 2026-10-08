<?php
require_once __DIR__ . "/config.php";
require_once __DIR__ . "/helpers.php";
require_once dirname(__DIR__, 2) . "/includes/hub.php";

$role          = strtoupper((string) ($_SESSION['role'] ?? ''));
$hubPreviewRole = function_exists('eca_hub_preview_staff_role') ? eca_hub_preview_staff_role() : null;
$is_superadmin = (!empty($_SESSION['user_id']) && ($role === 'SUPPERADMIN')) || $hubPreviewRole === 'SUPPERADMIN';
$is_admin      = !empty($_SESSION['user_id']) && ($role === 'ADMIN');
$is_contractor = !empty($_SESSION['user_id']) && (function_exists('eca_is_cpd_learner_role') ? eca_is_cpd_learner_role($role) : $role === 'CONTRACTOR');
$is_officer    = (!empty($_SESSION['user_id']) && ($role === 'OFFICER')) || $hubPreviewRole === 'OFFICER';
$is_website_admin = !empty($_SESSION['eca_admin']);
$is_staff      = $is_superadmin || $is_admin || $is_officer;
$current       = function_exists('eca_hub_current_script') ? eca_hub_current_script() : basename($_SERVER['PHP_SELF']);
$requestPath   = function_exists('eca_hub_request_path') ? eca_hub_request_path() : '';
$learnerActive = function_exists('eca_hub_is_learner_portal_page')
    ? eca_hub_is_learner_portal_page()
    : (str_contains($requestPath, 'learner-portal.php') || str_contains($requestPath, '/cpd/contractor') || str_contains($requestPath, '/cpd/app'));

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
$dashLink  = $is_staff && function_exists('eca_officer_home')
    ? eca_officer_home($chromeRole)
    : '/cpd/contractor/dashboard.php';
$is_learner_shell = $is_contractor && !$is_staff;
$hubTitle  = $learnerActive ? 'Learner Portal' : ($is_superadmin ? 'Super Admin Dashboard' : ($is_admin ? 'Admin Hub' : ($is_officer ? 'Officer Hub' : 'Learner Portal')));
$roleLabel = $is_superadmin ? 'Super Admin' : ($is_admin ? 'Admin' : ($is_officer ? 'Officer' : 'Learner'));
if ($is_superadmin) {
    $fullName = function_exists('eca_hub_super_admin_display_name')
        ? eca_hub_super_admin_display_name($fullName)
        : (preg_match('/^CPD\s+/i', $fullName) ? trim((string) preg_replace('/^CPD\s+/i', '', $fullName)) : $fullName);
    if ($fullName === '' || strcasecmp($fullName, 'SuperAdmin') === 0) {
        $fullName = 'Super Admin';
    }
}
$GLOBALS['eca_hub_shell'] = false;
$bodyClass = [];
if ($is_staff || $is_contractor || $is_website_admin) {
    $bodyClass[] = 'hub-root';
}
if ($is_learner_shell) {
    $bodyClass[] = 'hub-learner';
}
?>
<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title><?= ($is_learner_shell || $learnerActive) ? 'Learner Portal | ECA' : ($is_superadmin ? 'Super Admin Dashboard | ECA' : 'ECA CPD System') ?></title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
<link href="/css/dashboard.css" rel="stylesheet">
<?php eca_hub_assets(); ?>
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
        <h1 class="hub-title"><?= e($hubTitle) ?></h1>
      </div>
      <div class="hub-top-actions header-actions">
        <button class="btn btn-light mobile-toggle" type="button" onclick="toggleSidebar()" aria-label="Menu">
          <i class="fa fa-bars"></i>
        </button>
        <a class="hub-home-link header-home-link" href="/index.php">Home</a>
        <?php eca_origin_dashboard_back('top'); ?>
        <div class="dropdown">
          <a class="hub-chip user-profile dropdown-toggle" href="#" data-bs-toggle="dropdown">
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
    <div class="hub-layout dashboard-shell">
      <aside class="hub-sidebar sidebar">
        <div class="hub-welcome">
          <div class="hub-avatar"><?= e(eca_hub_initial($fullName)) ?></div>
          <div>
            <p class="hub-welcome-label">Welcome,</p>
            <p class="hub-welcome-name"><?= e($fullName) ?></p>
            <p class="hub-welcome-role"><?= e($roleLabel) ?></p>
          </div>
        </div>
        <ul class="sidebar-nav hub-nav">
      <li><a class="<?= $learnerActive ? 'active' : '' ?>" href="/learner-portal.php"><i class="fa-solid fa-graduation-cap" aria-hidden="true"></i> Learner Portal</a></li>
      <?php if ($is_admin): ?>
        <li><a class="<?= $current==='dashboard.php'?'active':'' ?>" href="/cpd/admin/dashboard.php">Dashboard</a></li>
        <li><a class="<?= $current==='applications.php'?'active':'' ?>" href="/cpd/admin/applications.php">Applications</a></li>
        <li><a class="<?= $current==='courses.php'?'active':'' ?>" href="/cpd/admin/courses.php">Courses</a></li>
        <li><a class="<?= $current==='course_students.php'?'active':'' ?>" href="/cpd/admin/course_students.php">Attendance</a></li>
        <li><a class="<?= $current==='learners.php'?'active':'' ?>" href="/cpd/admin/learners.php">Participants</a></li>
        <li><a class="<?= $current==='course_resources.php'?'active':'' ?>" href="/cpd/admin/course_resources.php">Resource Library</a></li>
        <li><a class="<?= $current==='feedback.php'?'active':'' ?>" href="/cpd/admin/feedback.php">Feedback</a></li>
        <li class="nav-item-unique">
          <a class="nav-link-unique" href="/index.php">Main portal</a>
        </li>
        <li><a class="<?= $current==='support.php'?'active':'' ?>" href="/cpd/admin/support.php">Support</a></li>
      <?php endif; ?>

      <?php if ($is_superadmin): ?>
        <li><a class="<?= $current==='users.php'?'active':'' ?>" href="/cpd/admin/users.php">Users</a></li>
      <?php endif; ?>

      <?php if ($is_staff && !$is_admin): ?>
        <li class="nav-item-unique">
          <a class="nav-link-unique" href="/index.php">Main portal</a>
        </li>
      <?php endif; ?>

      <?php if ($is_contractor || $is_officer || $is_website_admin || $is_superadmin): ?>
        <li><a class="<?= $current==='dashboard.php' && str_contains($requestPath, '/contractor') ?'active':'' ?>" href="/cpd/contractor/dashboard.php"><i class="fa-solid fa-gauge-high" aria-hidden="true"></i> Dashboard</a></li>
        <li><a class="<?= $current==='courses.php'?'active':'' ?>" href="/cpd/contractor/courses.php"><i class="fa-solid fa-book-open" aria-hidden="true"></i> Courses</a></li>
        <li><a class="<?= $current==='applications.php'?'active':'' ?>" href="/cpd/contractor/applications.php"><i class="fa-solid fa-file-lines" aria-hidden="true"></i> Applications</a></li>
        <li><a class="<?= $current==='transcript.php'?'active':'' ?>" href="/cpd/contractor/transcript.php"><i class="fa-solid fa-award" aria-hidden="true"></i> Transcript</a></li>
      <?php endif; ?>

        <?php if (function_exists('eca_super_admin_can_open_portals') && eca_super_admin_can_open_portals()): ?>
        <li><a href="/admin/index.php">Admin Hub</a></li>
        <li><a href="/admin/wellness/">Wellness</a></li>
        <li><a href="/wellness/">Wellness Hub</a></li>
        <li><a href="/client/dashboard.php">Member Hub</a></li>
        <li><a href="/client/wellness/">Member Wellness</a></li>
        <li><a href="/cpd/officer/dashboard.php">Officer Hub</a></li>
        <?php endif; ?>
        <?php eca_origin_dashboard_back('nav'); ?>
        <li><a href="/index.php">Home</a></li>
        <li class="logout"><a href="/cpd/logout.php"><?= (function_exists('eca_hub_can_open_portals') && eca_hub_can_open_portals()) ? 'Leave portal' : 'Logout' ?></a></li>
        </ul>
      </aside>
      <main class="hub-main dashboard-content">
        <div class="hub-hello">
          <h1 class="hub-hello-title"><?= e(eca_hub_hello($fullName)) ?></h1>
          <p><?= ($is_learner_shell || $learnerActive) ? 'Courses, applications and your CPD record in one place.' : 'Keep learning, keep growing. Build your future with ECA.' ?></p>
        </div>
<?php else: ?>
<div class="container py-4">
<?php endif; ?>
