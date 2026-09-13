<?php
require_once __DIR__ . "/config.php";
require_once __DIR__ . "/helpers.php";

$role           = strtoupper($_SESSION['role'] ?? '');
$is_superadmin  = !empty($_SESSION['user_id']) && ($role === 'SUPPERADMIN');
$is_admin       = !empty($_SESSION['user_id']) && ($role === 'ADMIN');
$is_officer     = !empty($_SESSION['user_id']) && ($role === 'OFFICER');
$is_contractor  = !empty($_SESSION['user_id']) && ($role === 'CONTRACTOR');
$is_staff       = $is_superadmin || $is_admin || $is_officer;
$current        = basename($_SERVER['PHP_SELF']);
?>
<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>ECA CPD System</title>

<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
<link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&family=Inter:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">
<link href="/css/dashboard.css" rel="stylesheet">

<style>
:root{
  --eca-navy:#000066;
  --eca-navy-dark:#000066;
  --eca-red:#d90920;
  --eca-page:#f5f7fb;
  --eca-white:#ffffff;
  --eca-text:#111827;
  --eca-line:#e6eaf2;
}

*{
  box-sizing:border-box;
}

html,
body{
  margin:0;
  padding:0;
  overflow-x:hidden;
  font-family:'Inter',sans-serif;
  background:var(--eca-page);
}

body{
  padding-top:102px;
}

/* =========================
   FIXED HEADER
========================= */
.eca-navbar{
  position:fixed !important;
  top:38px;
  left:0;
  right:0;
  height:64px;
  min-height:64px;
  z-index:2000;
  padding:0;
  background:#fff !important;
  border-bottom:1px solid #edf0f5;
  box-shadow:none !important;
}

.eca-navbar .container-fluid{
  position:relative;
  height:64px;
  padding-left:24px;
  padding-right:24px;
  display:flex;
  align-items:center;
}

/* Center Header H1 */
.header-cpd-title{
  position:absolute;
  left:50%;
  top:50%;
  transform:translate(-50%, -50%);
  margin:0;
  color:#000066;
  font-size:22px;
  font-weight:800;
  letter-spacing:-0.03em;
  text-transform:none;
  line-height:1;
  text-align:center;
  white-space:nowrap;
  pointer-events:none;
  z-index:1;
}

/* Brand */
.navbar-brand.brand-wrap{
  position:relative;
  z-index:3;
  display:flex;
  align-items:center;
  gap:16px;
  text-decoration:none;
  margin:0;
  padding:0;
}

.logo-box{
  background:transparent;
  padding:0;
  margin:0;
  border-radius:0;
  box-shadow:none;
  display:flex;
  align-items:center;
}

.brand-logo{
  width:130px;
  max-width:130px;
    border-radius:10px;
  height:auto;
  object-fit:contain;
  display:block;
}

.brand-text{
  line-height:1.15;
}

.brand-title{
  font-size:15px;
  font-weight:800;
  color:#000066;
  margin:0;
}

.brand-sub{
  font-size:13px;
  color:#667085;
  margin:0;
}

/* Right Profile Area */
.header-actions{
  position:relative;
  z-index:4;
}

.user-profile,
.user-pill{
  display:flex;
  align-items:center;
  gap:10px;
  min-height:44px;
  padding:8px 14px;
  border-radius:28px;
  background:#f5f7fb;
  border:1px solid #e6eaf2;
  color:#000066;
  text-decoration:none;
  box-shadow:none;
}

.user-profile:hover,
.user-pill:hover{
  background:#eef1f8;
  color:#000066;
}

.avatar-circle{
  width:36px;
  height:36px;
  border-radius:50%;
  display:flex;
  align-items:center;
  justify-content:center;
  background:rgba(0, 0, 102, 0.08);
  color:#000066;
  font-size:15px;
  box-shadow:none;
  flex-shrink:0;
}

.user-meta{
  line-height:1.05;
}

.welcome{
  font-size:12px;
  color:#667085;
  margin:0;
}

.username{
  font-size:15px;
  font-weight:800;
  color:#000066;
  margin:0;
}

.profile-menu{
  border:none;
  border-radius:12px;
  overflow:hidden;
  box-shadow:0 10px 25px rgba(0,0,0,.16);
}

/* =========================
   LAYOUT
========================= */
.dashboard-shell{
  display:flex;
}

.dashboard-content{
  flex:1;
  margin-left:240px;
  padding:18px;
  min-height:calc(100vh - 102px);
  background:var(--eca-page);
}

/* =========================
   SIDEBAR
========================= */
.sidebar{
  width:240px;
  position:fixed;
  top:102px;
  left:0;
  height:calc(100vh - 102px);
  background:#fff;
  color:#1b1f3b;
  padding:12px 0 16px;
  overflow-y:auto;
  overflow-x:hidden;
  border-right:1px solid #edf0f5;
  z-index:1500;
}

.sidebar-brand{
  display:none;
}

.sidebar-nav{
  list-style:none;
  margin:0;
  padding:0;
}

.sidebar-nav li{
  margin:6px 0;
  padding:0 10px;
}

.sidebar-nav a{
  display:flex;
  align-items:center;
  gap:12px;
  min-height:48px;
  padding:12px 16px;
  border-radius:10px;
  color:#1b1f3b;
  text-decoration:none;
  font-size:14px;
  font-weight:700;
  transition:.2s ease;
  background:transparent;
}

.sidebar-nav a i{
  width:26px;
  text-align:center;
  font-size:18px;
  flex-shrink:0;
}

.sidebar-nav a:hover{
  background:rgba(0, 0, 102, 0.06);
  transform:none;
}

.sidebar-nav a.active{
  background:rgba(0, 0, 102, 0.06);
  box-shadow:inset 3px 0 0 #d90920;
  position:relative;
}

.sidebar-nav a.active::before{
  display:none;
}

.sidebar-nav a.active i,
.sidebar-nav a.active span,
.sidebar-nav a.active{
  position:relative;
  z-index:1;
}

.logout{
  margin-top:18px !important;
}

/* Main Portal Button */
.nav-item-unique{
  margin-top:16px;
  padding:0 12px;
}

.nav-link-unique{
  display:flex;
  align-items:center;
  gap:12px;
  min-height:56px;
  padding:14px 16px;
  border-radius:12px;
  text-decoration:none;
  color:#fff;
  background:#000066;
  border:0;
}

.nav-link-unique::before,
.nav-link-unique::after{
  display:none;
}

.nav-link-unique:hover{
  background:rgba(255,255,255,.08);
  transform:none;
  color:#fff;
}

.icon-wrap{
  width:36px;
  height:36px;
  border-radius:10px;
  display:flex;
  align-items:center;
  justify-content:center;
  background:rgba(255,255,255,.14);
  color:#fff;
  box-shadow:none;
}

.badge-dot{
  width:7px;
  height:7px;
  border-radius:50%;
  background:var(--eca-red);
  box-shadow:none;
  margin-left:auto;
}

/* Overlay */
.overlay{
  position:fixed;
  inset:0;
  background:rgba(0,0,0,.42);
  z-index:1040;
  opacity:0;
  visibility:hidden;
  transition:.25s;
}

.mobile-toggle{
  display:none;
}

/* =========================
   RESPONSIVE
========================= */
@media (max-width:1199.98px){
  .header-cpd-title{
    font-size:24px;
  }

  .brand-logo{
    width:115px;
    max-width:115px;
  }
}

@media (max-width:991.98px){
  .dashboard-content{
    margin-left:0;
    padding:14px;
  }

  .sidebar{
    transform:translateX(-100%);
    transition:.25s ease;
  }

  body.sidebar-open .sidebar{
    transform:translateX(0);
  }

  body.sidebar-open .overlay{
    opacity:1;
    visibility:visible;
  }

  .mobile-toggle{
    display:inline-flex;
  }

  .brand-logo{
    width:95px;
    max-width:95px;
  }

  .brand-title{
    font-size:14px;
  }

  .brand-sub{
    font-size:12px;
  }

  .header-cpd-title{
    font-size:20px;
    letter-spacing:.6px;
  }
}

@media (max-width:767.98px){
  .eca-navbar .container-fluid{
    padding-left:14px;
    padding-right:14px;
  }

  .header-cpd-title{
    font-size:17px;
  }

  .brand-logo{
    width:82px;
    max-width:82px;
  }
}

@media (max-width:575.98px){
  .header-cpd-title{
    font-size:15px;
    letter-spacing:.3px;
    max-width:180px;
    overflow:hidden;
    text-overflow:ellipsis;
  }

  .user-profile{
    padding:8px 10px;
  }

  .avatar-circle{
    width:34px;
    height:34px;
  }
}
</style>
</head>

<body>

<div class="eca-dash-topbar d-none d-lg-block">
  <div class="eca-dash-topbar-inner">
    <span>Eswatini Contractors Association</span>
    <div>
      <a href="mailto:info@eca.co.sz"><i class="fa fa-envelope"></i> info@eca.co.sz</a>
    </div>
  </div>
</div>

<nav class="navbar navbar-expand-lg eca-navbar">
  <div class="container-fluid">

    <a class="navbar-brand brand-wrap d-flex align-items-center gap-3" href="/cpd/index.php">
      <div class="logo-box">
        <img src="/img/ecalogo.png" class="brand-logo" alt="ECA Logo" onerror="this.src='https://eca.co.sz/cpd/images/logo.jpg'">
      </div>

      <div class="brand-text d-none d-sm-block">
        <?php if($is_superadmin): ?>
          <div class="brand-title">SuperAdmin Panel</div>
        <?php elseif($is_admin): ?>
          <div class="brand-title">Admin Panel</div>
        <?php elseif($is_officer): ?>
          <div class="brand-title">Officer Panel</div>
        <?php elseif($is_contractor): ?>
          <div class="brand-title">Contractor Panel</div>
        <?php endif; ?>

        <div class="brand-sub">Eswatini Contractors Association</div>
      </div>
    </a>

    <h1 class="header-cpd-title">CPD Point System</h1>

    <div class="ms-auto d-flex align-items-center gap-3 header-actions">

      <?php if(!empty($_SESSION['user_id'])): ?>

        <button class="btn btn-light mobile-toggle shadow-sm" type="button" onclick="toggleSidebar()">
          <i class="fa fa-bars"></i>
        </button>

        <?php
          $fullName  = trim($_SESSION['full_name'] ?? 'User');
          $firstName = explode(' ', $fullName)[0];

          if ($role === 'SUPPERADMIN') {
            $dashLink = "/cpd/admin/dashboard.php";
          } elseif ($role === 'ADMIN') {
            $dashLink = "/cpd/admin/course_students.php";
          } elseif ($role === 'OFFICER') {
            $dashLink = "/cpd/admin/applications.php";
          } else {
            $dashLink = "/cpd/contractor/dashboard.php";
          }
        ?>

        <div class="dropdown">
          <a class="user-profile dropdown-toggle" href="#" data-bs-toggle="dropdown" aria-expanded="false">
            <div class="avatar-circle">
              <i class="fa-solid fa-user"></i>
            </div>

            <div class="user-meta d-none d-md-block">
              <div class="welcome">Welcome back</div>
              <div class="username"><?= e($firstName) ?></div>
            </div>
          </a>

          <ul class="dropdown-menu dropdown-menu-end shadow-lg profile-menu">
            <li class="px-3 py-2 small text-muted">
              Signed in as <strong><?= e($fullName) ?></strong>
            </li>

            <li><hr class="dropdown-divider"></li>

            <li>
              <a class="dropdown-item" href="<?= $dashLink ?>">
                <i class="fa-solid fa-gauge-high me-2 text-primary"></i>
                Dashboard
              </a>
            </li>

            <li>
              <a class="dropdown-item text-danger" href="/cpd/logout.php">
                <i class="fa-solid fa-right-from-bracket me-2"></i>
                Logout
              </a>
            </li>
          </ul>
        </div>

      <?php else: ?>

      

      <?php endif; ?>

    </div>
  </div>
</nav>

<?php if($is_staff || $is_contractor): ?>

<div class="overlay" onclick="document.body.classList.remove('sidebar-open')"></div>

<div class="dashboard-shell">

  <aside class="sidebar">
    <div class="sidebar-brand">
      <div class="sidebar-badge">CPD</div>
      <div>
        <strong>
          <?= $is_superadmin ? 'SuperAdmin Panel' : ($is_admin ? 'Admin Panel' : ($is_officer ? 'Officer Panel' : 'Contractor Panel')) ?>
        </strong><br>
        <small><?= e($_SESSION['full_name'] ?? '') ?></small>
      </div>
    </div>

    <ul class="sidebar-nav">

      <?php if($is_superadmin): ?>

        <li>
          <a class="<?= $current === 'dashboard.php' ? 'active' : '' ?>" href="/cpd/admin/dashboard.php">
            <i class="fa-solid fa-gauge-high"></i>
            <span>Dashboard</span>
          </a>
        </li>

        <li>
          <a class="<?= $current === 'courses.php' ? 'active' : '' ?>" href="/cpd/admin/courses.php">
            <i class="fa-solid fa-graduation-cap"></i>
            <span>Courses</span>
          </a>
        </li>

        <li>
          <a class="<?= $current === 'applications.php' ? 'active' : '' ?>" href="/cpd/admin/applications.php">
            <i class="fa-solid fa-file-circle-check"></i>
            <span>Applications</span>
          </a>
        </li>
          <li>
          <a class="<?= $current === 'payment.php' ? 'active' : '' ?>" href="/cpd/admin/payment.php">
            <i class="fa-solid fa-file-circle-check"></i>
            <span>Payments</span>
          </a>
        </li>

        <li>
          <a class="<?= $current === 'learners.php' ? 'active' : '' ?>" href="/cpd/admin/learners.php">
            <i class="fa-solid fa-user-graduate"></i>
            <span>Participants</span>
          </a>
        </li>

        <li>
          <a class="<?= $current === 'course_students.php' ? 'active' : '' ?>" href="/cpd/admin/course_students.php">
            <i class="fa-solid fa-clipboard-check"></i>
            <span>Attendance</span>
          </a>
        </li>

        <li>
          <a class="<?= $current === 'course_resources.php' ? 'active' : '' ?>" href="/cpd/admin/course_resources.php">
            <i class="fa-solid fa-folder-open"></i>
            <span>Resource Library</span>
          </a>
        </li>
    <li>
    <a class="<?= $current === 'announcement.php' ? 'active' : '' ?>" href="/cpd/admin/announcement.php">
            <i class="fa-solid fa-star"></i>
            <span>Announcements</span>
          </a>
        </li>
        <li>
          <a class="<?= $current === 'feedback.php' ? 'active' : '' ?>" href="/cpd/admin/feedback.php">
            <i class="fa-solid fa-star"></i>
            <span>Feedback</span>
          </a>
        </li>

        <li>
          <a class="<?= $current === 'support.php' ? 'active' : '' ?>" href="/cpd/admin/support.php">
            <i class="fa-solid fa-headset"></i>
            <span>Support</span>
          </a>
        </li>

        <li>
          <a class="<?= $current === 'users.php' ? 'active' : '' ?>" href="/cpd/admin/users.php">
            <i class="fa-solid fa-users"></i>
            <span>Users</span>
          </a>
        </li>

        <li>
          <a class="<?= $current === 'cpd_reports.php' ? 'active' : '' ?>" href="/cpd/admin/cpd_reports.php">
            <i class="fa-solid fa-chart-line"></i>
            <span>Reports</span>
          </a>
        </li>

        <li class="nav-item-unique">
          <a class="nav-link-unique" href="https://eca.co.sz/portal/dashboard.php" target="_blank">
            <span class="icon-wrap">
              <i class="fa-solid fa-gauge-high"></i>
            </span>
            <span class="text">Main Portal</span>
            <span class="badge-dot"></span>
          </a>
        </li>

      <?php endif; ?>

      <?php if($is_admin): ?>

        <li>
          <a class="<?= $current === 'course_students.php' ? 'active' : '' ?>" href="/cpd/admin/course_students.php">
            <i class="fa-solid fa-clipboard-check"></i>
            <span>Attendance</span>
          </a>
        </li>

        <li>
          <a class="<?= $current === 'learners.php' ? 'active' : '' ?>" href="/cpd/admin/learners.php">
            <i class="fa-solid fa-user-graduate"></i>
            <span>Participants</span>
          </a>
        </li>
  <li>
          <a class="<?= $current === 'payment.php' ? 'active' : '' ?>" href="/cpd/admin/payment.php">
            <i class="fa-solid fa-file-circle-check"></i>
            <span>Payments</span>
          </a>
        </li>
        <li>
          <a class="<?= $current === 'support.php' ? 'active' : '' ?>" href="/cpd/admin/support.php">
            <i class="fa-solid fa-headset"></i>
            <span>Support</span>
          </a>
        </li>

        <li class="nav-item-unique">
          <a class="nav-link-unique" href="https://eca.co.sz/portal/dashboard.php" target="_blank">
            <span class="icon-wrap">
              <i class="fa-solid fa-gauge-high"></i>
            </span>
            <span class="text">Main Portal</span>
            <span class="badge-dot"></span>
          </a>
        </li>

      <?php endif; ?>

      <?php if($is_officer): ?>

        <li>
          <a class="<?= $current === 'applications.php' ? 'active' : '' ?>" href="/cpd/admin/applications.php">
            <i class="fa-solid fa-file-circle-check"></i>
            <span>Applications</span>
          </a>
        </li>

        <li>
          <a class="<?= $current === 'course_students.php' ? 'active' : '' ?>" href="/cpd/admin/course_students.php">
            <i class="fa-solid fa-clipboard-check"></i>
            <span>Attendance</span>
          </a>
        </li>

        <li>
          <a class="<?= $current === 'learners.php' ? 'active' : '' ?>" href="/cpd/admin/learners.php">
            <i class="fa-solid fa-user-graduate"></i>
            <span>Participants</span>
          </a>
        </li>

        <li class="nav-item-unique">
          <a class="nav-link-unique" href="https://eca.co.sz/portal/dashboard.php" target="_blank">
            <span class="icon-wrap">
              <i class="fa-solid fa-gauge-high"></i>
            </span>
            <span class="text">Main Portal</span>
            <span class="badge-dot"></span>
          </a>
        </li>

      <?php endif; ?>

      <?php if($is_contractor): ?>

        <li>
          <a class="<?= $current === 'dashboard.php' ? 'active' : '' ?>" href="/cpd/contractor/dashboard.php">
            <i class="fa-solid fa-house"></i>
            <span>Dashboard</span>
          </a>
        </li>

        <li>
          <a class="<?= $current === 'courses.php' ? 'active' : '' ?>" href="/cpd/contractor/courses.php">
            <i class="fa-solid fa-graduation-cap"></i>
            <span>Courses</span>
          </a>
        </li>

        <li>
          <a class="<?= $current === 'applications.php' ? 'active' : '' ?>" href="/cpd/contractor/applications.php">
            <i class="fa-solid fa-file-circle-check"></i>
            <span>Applications</span>
          </a>
        </li>

        <li>
          <a class="<?= $current === 'transcript.php' ? 'active' : '' ?>" href="/cpd/contractor/transcript.php">
            <i class="fa-solid fa-award"></i>
            <span>Transcript</span>
          </a>
        </li>

      <?php endif; ?>

      <li class="logout">
        <a href="/cpd/logout.php">
          <i class="fa-solid fa-right-from-bracket"></i>
          <span>Logout</span>
        </a>
      </li>

    </ul>
  </aside>

  <main class="dashboard-content">

<?php else: ?>

<div class="container py-4">

<?php endif; ?>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

<script>
function toggleSidebar(){
  document.body.classList.toggle('sidebar-open');
}
</script>