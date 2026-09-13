<?php
require_once __DIR__ . "/config.php";
require_once __DIR__ . "/helpers.php";

$role          = $_SESSION['role'] ?? null;
$is_admin      = !empty($_SESSION['user_id']) && ($role === 'ADMIN');
$is_contractor = !empty($_SESSION['user_id']) && ($role === 'OFFICER');
$current       = basename($_SERVER['PHP_SELF']);
?>
<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>ECA CPD System</title>

<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
<link href="/css/dashboard.css" rel="stylesheet">

<style>
/* ================= THEME ================= */
/* ================= PREMIUM RED THEME ================= */

:root{


/* ================= DARK THEME ================= */

:root{
--primary:#0f172a;
--primary-2:#1e293b;
--accent:#3b82f6;

--bg:#0f172a;
--card:#ffffff;

--ink:#e5e7eb;
--muted:#94a3b8;

--glass:rgba(255,255,255,.06);
--line:rgba(255,255,255,.08);

--shadow:0 20px 45px rgba(0,0,0,.35);
}

/* ================= BODY ================= */

body{
margin:0;
background:
radial-gradient(circle at top right,#ffdede 0%,transparent 40%),
radial-gradient(circle at bottom left,#ffeaea 0%,transparent 40%),
var(--bg);
font-family:system-ui,-apple-system,Segoe UI,Roboto;
}

/* ================= NAVBAR ================= */

.eca-navbar{

background:
linear-gradient(135deg,#0f172a,#0f172a,#0f172c) !important;

box-shadow:
0 10px 30px rgba(0,0,0,.35),
inset 0 -2px 0 rgba(255,255,255,.15);

}

/* ================= BRAND LOGO ================= */
/* LOGO CONTAINER */

.logo-box{

height:44px;
width:100%;

display:flex;
align-items:center;
justify-content:center;

background:rgba(255,255,255,.12);

border-radius:18px;

padding:4px 10px;

box-shadow:0 4px 12px rgba(0,0,0,.2);

}

/* LOGO IMAGE */

.brand-logo{

max-height:32px;
max-width:100%;

object-fit:contain;

display:block;

}
/* ================= SIDEBAR ================= */
.sidebar{
  width:260px;
  position:fixed;
  top:0;
  left:0;
  height:100vh;

  padding:18px 14px;

  /* ✅ MATCH HEADER */
  background: linear-gradient(135deg,#0f172a,#0f172a,#0f172c);

  color:#fff;

  overflow:auto;

  box-shadow:
  6px 0 28px rgba(0,0,0,.35),
  inset -1px 0 0 rgba(255,255,255,.05);
}
/* ================= USER PROFILE ================= */

.user-pill{

display:flex;
align-items:center;
gap:10px;

padding:8px 16px;

border-radius:999px;

background:rgba(255,255,255,.12);

border:1px solid rgba(255,255,255,.25);

font-weight:800;

transition:.2s;

}

.user-pill:hover{

background:rgba(255,255,255,.2);

}

/* ================= KPI PREMIUM CARDS ================= */

.kpi-card-premium{

position:relative;

border-radius:22px;

padding:22px;

color:white;

overflow:hidden;

border:none;

transition:.3s;

box-shadow:
0 18px 50px rgba(0,0,0,.22);

}

/* Shiny gradient */

.kpi-red{

background:
linear-gradient(135deg,#b30d0d,#d01919,#ff3b3b);

}

/* Dark luxury card */

.kpi-dark{

background:
linear-gradient(135deg,#0f172a,#1e293b);

}

/* Soft premium */

.kpi-soft{

background:
linear-gradient(135deg,#c41212,#8c0f0f);

}

/* Hover animation */

.kpi-card-premium:hover{

transform:translateY(-6px) scale(1.01);

box-shadow:
0 28px 70px rgba(0,0,0,.35);

}

/* Glow circles */

.kpi-card-premium::after{

content:"";

position:absolute;

top:-40px;
right:-40px;

width:160px;
height:160px;

background:rgba(255,255,255,.08);

border-radius:50%;

}

/* ================= KPI TEXT ================= */

.kpi-label{

font-size:.9rem;
font-weight:700;
opacity:.85;

}

.kpi-value{

font-size:2.3rem;
font-weight:900;
letter-spacing:-.5px;

}

/* KPI icon */

.kpi-icon{

width:52px;
height:52px;

border-radius:16px;

display:flex;
align-items:center;
justify-content:center;

background:rgba(255,255,255,.18);

font-size:1.3rem;

box-shadow:0 8px 20px rgba(0,0,0,.25);

}

/* ================= PREMIUM CARDS ================= */

.card-premium{

border-radius:18px;

border:1px solid rgba(0,0,0,.06);

background:white;

box-shadow:0 14px 38px rgba(0,0,0,.08);

}

/* ================= COURSE HERO ================= */

.course-hero{

background:
linear-gradient(135deg,#4c0404,#a30e0e,#d01919);

color:white;

border-radius:16px;

padding:34px;

position:relative;

overflow:hidden;

box-shadow:0 22px 55px rgba(0,0,0,.3);

}

/* shine effect */

.course-hero::before{

content:'';

position:absolute;

top:-60px;
right:-60px;

width:220px;
height:220px;

background:rgba(255,255,255,.1);

border-radius:50%;

}

/* ================= STAT BOX ================= */

.stat-box{

border-radius:16px;

border:1px solid rgba(0,0,0,.08);

background:white;

padding:14px;

box-shadow:0 8px 20px rgba(0,0,0,.05);

}

/* ================= QUICK TILES ================= */

.quick-tile{

border-radius:18px;

background:white;

border:1px solid rgba(0,0,0,.08);

box-shadow:0 10px 26px rgba(0,0,0,.08);

padding:14px;

transition:.2s;

}

.quick-tile:hover{

transform:translateY(-4px);

box-shadow:0 18px 40px rgba(0,0,0,.15);

}

/* ================= MODAL CARD ================= */

.app-modal-card{

background:
linear-gradient(135deg,#5b0707,#a10d0d,#d01919);

color:white;

border-radius:14px;

padding:24px;

box-shadow:0 18px 40px rgba(0,0,0,.35);

}

/* ================= SIDEBAR ================= */
/* ================= SIDEBAR ================= */

.dashboard-shell{
  display:flex;
}

/* Main sidebar */


/* Brand area */

.sidebar-brand{
  display:flex;
  gap:10px;
  padding-bottom:14px;
  border-bottom:1px solid rgba(255,255,255,.15);
  margin-bottom:14px;
}

.sidebar-badge{
  width:40px;
  height:40px;
  border-radius:14px;
  background:rgba(255,255,255,.18);
  display:flex;
  align-items:center;
  justify-content:center;

  font-weight:900;
}

/* Menu */

.sidebar-nav{
  list-style:none;
  padding:0;
  margin:0;
}

.sidebar-nav li{
  margin:6px 0;
}

.sidebar-nav a{

  display:flex;
  align-items:center;
  gap:10px;

  padding:12px;

  border-radius:14px;

  color:rgba(255,255,255,.95);

  text-decoration:none;

  font-weight:800;

  transition:.25s;
}

/* Hover */

.sidebar-nav a:hover{

  background:rgba(255,255,255,.15);

  transform:translateX(4px);

}

/* Active */

.sidebar-nav a.active{

  background:rgba(255,255,255,.22);

  box-shadow:
  0 8px 20px rgba(0,0,0,.25);

}

/* Logout */

.sidebar-nav .logout a{
  color:#ffd6d6;
}

/* Content area */

.dashboard-content{
  flex:1;
  margin-left:260px;
  padding:22px;
}
/* ===== PREMIUM LEAD + RED DASHBOARD CARDS ===== */

.kpi-card-premium{
  position: relative;
  border-radius: 22px;
  padding: 22px;
  color: #fff;
  overflow: hidden;
  border: none;
  box-shadow: 0 20px 45px rgba(0,0,0,.15);
  transition: .3s ease;
}

.kpi-card-premium:hover{
  transform: translateY(-4px);
  box-shadow: 0 28px 55px rgba(0,0,0,.22);
}

/* LEAD THEME */
.kpi-lead{
  background: linear-gradient(135deg, #25809b 0%, #134f62 100%);
}

/* RED THEME */
.kpi-red{
  background: linear-gradient(135deg, #d01919 0%, #8c0f0f 100%);
}

/* Decorative Glow Circle */
.kpi-card-premium::after{
  content: "";
  position: absolute;
  right: -40px;
  top: -40px;
  width: 150px;
  height: 150px;
  background: rgba(255,255,255,.08);
  border-radius: 50%;
}

/* Card Content */
.kpi-premium-top{
  display:flex;
  justify-content:space-between;
  align-items:center;
}

.kpi-premium-label{
  font-size: .9rem;
  font-weight: 700;
  opacity: .85;
}

.kpi-premium-value{
  font-size: 2.3rem;
  font-weight: 950;
  letter-spacing: -.5px;
  margin-top: 6px;
}

.kpi-premium-icon{
  width: 50px;
  height: 50px;
  border-radius: 16px;
  display:flex;
  align-items:center;
  justify-content:center;
  background: rgba(255,255,255,.15);
  font-size: 1.2rem;
}

.kpi-premium-footer{
  margin-top: 16px;
  font-size: .85rem;
  opacity: .9;
  display:flex;
  justify-content:space-between;
  align-items:center;
}

/* ===== ADMIN DASHBOARD PREMIUM CARDS (Lead #25809b + Red) ===== */
.kpi-grid{ margin-top: 8px; }

.kpi-card-premium{
  position: relative;
  border-radius: 22px;
  padding: 20px 18px;
  color: #fff;
  overflow: hidden;
  border: 1px solid rgba(255,255,255,.10);
  box-shadow: 0 18px 44px rgba(2,8,23,.14);
  transition: .25s ease;
  min-height: 132px;
}
.kpi-card-premium:hover{ transform: translateY(-4px); box-shadow: 0 26px 58px rgba(2,8,23,.20); }

.kpi-card-premium::after{
  content:"";
  position:absolute;
  right:-50px; top:-50px;
  width: 170px; height: 170px;
  border-radius: 999px;
  background: rgba(255,255,255,.09);
}
.kpi-card-premium::before{
  content:"";
  position:absolute;
  left:-70px; bottom:-70px;
  width: 220px; height: 220px;
  border-radius: 999px;
  background: rgba(0,0,0,.10);
  filter: blur(1px);
}

.kpi-lead{ background: linear-gradient(135deg, #25809b 0%, #134f62 100%); }
.kpi-red{  background: linear-gradient(135deg, #d01919 0%, #8c0f0f 100%); }
.kpi-dark{ background: linear-gradient(135deg, #0f172a 0%, #1e293b 100%); }
.kpi-soft{ background: linear-gradient(135deg, #1f6d84 0%, #25809b 100%); }

.kpi-top{
  position: relative;
  z-index: 2;
  display:flex;
  align-items:flex-start;
  justify-content:space-between;
  gap: 12px;
}
.kpi-label{
  font-weight: 800;
  opacity: .88;
  font-size: .92rem;
}
.kpi-value{
  font-weight: 950;
  letter-spacing: -.6px;
  font-size: 2.25rem;
  line-height: 1.1;
  margin-top: 6px;
}
.kpi-icon{
  width: 50px; height: 50px;
  border-radius: 18px;
  display:flex;
  align-items:center;
  justify-content:center;
  background: rgba(255,255,255,.16);
  border: 1px solid rgba(255,255,255,.18);
  box-shadow: 0 14px 26px rgba(0,0,0,.18);
  font-size: 1.2rem;
}
.kpi-foot{
  position: relative;
  z-index: 2;
  margin-top: 14px;
  display:flex;
  align-items:center;
  justify-content:space-between;
  gap: 10px;
  font-size: .86rem;
  opacity: .92;
}
.kpi-chip{
  padding: 6px 10px;
  border-radius: 999px;
  background: rgba(255,255,255,.14);
  border: 1px solid rgba(255,255,255,.18);
  font-weight: 800;
}

/* ===== Progress Card ===== */
.card-premium{
  border-radius: 20px !important;
  border: 1px solid rgba(15,23,42,.08) !important;
  box-shadow: 0 16px 38px rgba(2,8,23,.10) !important;
}
.progress-brand{
  height: 14px !important;
  border-radius: 999px !important;
  background: rgba(15,23,42,.08) !important;
}
.progress-brand .progress-bar{
  border-radius: 999px !important;
  background: linear-gradient(135deg, #25809b 0%, #134f62 100%) !important;
}
.stat-box{
  border-radius: 16px;
  border: 1px solid rgba(15,23,42,.08);
  background: rgba(255,255,255,.72);
  padding: 12px 12px;
}.course-hero{
background: linear-gradient(135deg,#06254a,#0b3c6d,#0f4f8a);
color:white;
border-radius:12px;
padding:30px;
position:relative;
overflow:hidden;
}

.course-hero::before{
content:'';
position:absolute;
top:-50px;
right:-50px;
width:200px;
height:200px;
background:rgba(255,255,255,0.08);
border-radius:50%;
}

.course-info{
background:rgba(255,255,255,0.08);
padding:15px;
border-radius:10px;
text-align:center;
}

.course-title{
font-size:26px;
font-weight:700;
letter-spacing:.5px;
}

.course-desc{
opacity:.85;
margin-bottom:20px;
}

.course-badge{
background:#ffc107;
color:#000;
font-weight:600;
padding:6px 12px;
border-radius:20px;
}
.stat-box .lbl{ color: var(--muted); font-size: .85rem; font-weight: 800; }
.stat-box .val{ font-weight: 950; font-size: 1.25rem; margin-top: 2px; }

/* ===== Quick action tiles ===== */
.quick-tile{
  border-radius: 18px;
  border: 1px solid rgba(15,23,42,.08);
  background: #fff;
  box-shadow: 0 10px 22px rgba(2,8,23,.06);
  padding: 14px;
}
.quick-tile a{ text-decoration:none; }
.quick-tile .t{ font-weight: 950; }
.quick-tile .s{ color: var(--muted); font-size: .9rem; }

.app-modal-card{
background: linear-gradient(135deg,#06254a,#0c3f75,#0e5aa7);
color:white;
border-radius:12px;
padding:25px;
}

.app-box{
background:rgba(255,255,255,.08);
padding:12px;
border-radius:8px;
margin-bottom:10px;
}

/* BRAND AREA */

.brand-wrap{
text-decoration:none;
}

.logo-box{

background:rgba(255,255,255,.15);
padding:6px;
border-radius:12px;

box-shadow:
0 6px 16px rgba(0,0,0,.25);

}

.brand-logo{
width:110px;
height:36px;
object-fit:contain;
}

.brand-title{
font-weight:900;
font-size:15px;
letter-spacing:.3px;
}

.brand-sub{
font-size:12px;
opacity:.85;
}

/* USER PROFILE */

.user-profile{

display:flex;
align-items:center;
gap:10px;

background:rgba(255,255,255,.12);

border:1px solid rgba(255,255,255,.25);

padding:6px 12px;

border-radius:999px;

color:white;
text-decoration:none;

transition:.2s;

}

.user-profile:hover{
background:rgba(255,255,255,.18);
color:white;
}

/* avatar */

.avatar-circle{

width:38px;
height:38px;

border-radius:50%;

display:flex;
align-items:center;
justify-content:center;

background:rgba(255,255,255,.18);

font-size:15px;

box-shadow:
0 4px 12px rgba(0,0,0,.25);

}

/* username */

.username{
font-weight:900;
font-size:13px;
}

.welcome{
font-size:11px;
opacity:.8;
}

/* dropdown */

.profile-menu{
border-radius:12px;
padding:8px 0;
border:none;
}
<style>

/* ===== Dashboard Cards ===== */

.stat-card{
border-radius:16px;
padding:20px;
color:white;
box-shadow:0 10px 30px rgba(0,0,0,.15);
}

.stat-present{
background:linear-gradient(135deg,#16a34a,#22c55e);
}

.stat-absent{
background:linear-gradient(135deg,#dc2626,#ef4444);
}

.stat-pending{
background:linear-gradient(135deg,#f59e0b,#fbbf24);
}

.stat-title{
font-size:14px;
opacity:.9;
}

.stat-value{
font-size:32px;
font-weight:700;
}

/* ===== Cards ===== */

.card-ui{
border-radius:16px;
border:0;
box-shadow:0 6px 25px rgba(0,0,0,.08);
}

/* ===== Table Styling ===== */

.table-ui{
border-collapse:separate;
border-spacing:0;
}

.table-ui thead{
background:linear-gradient(135deg,#06254a,#0e5aa7);
color:white;
}

.table-ui th{
padding:14px;
font-weight:600;
border:0;
}

.table-ui td{
padding:12px;
border-bottom:1px solid #f1f5f9;
}

.table-ui tbody tr:hover{
background:#f8fafc;
}

/* ===== Badges ===== */

.badge-days{
background:#0ea5e9;
padding:6px 10px;
border-radius:20px;
font-weight:500;
}

/* ===== Buttons ===== */

.btn-present{
background:#16a34a;
border:0;
}

.btn-absent{
background:#dc2626;
border:0;
}

.btn-cancel{
background:#f59e0b;
border:0;
}

/* ===== DataTables Fix ===== */

.dataTables_wrapper .dataTables_paginate .paginate_button{
padding:.4rem .8rem;
border-radius:6px;
margin-left:4px;
}
/* container spacing */
.nav-item-unique {
    margin-bottom: 12px;
}

/* main button */
.nav-link-unique {
    position: relative;
    display: flex;
    align-items: center;
    gap: 12px;
    padding: 12px 16px;
    border-radius: 14px;

    background: rgba(255,255,255,0.05);
    backdrop-filter: blur(12px);
    border: 1px solid rgba(255,255,255,0.08);

    color: #e4e6eb;
    font-weight: 500;
    text-decoration: none;

    transition: all 0.35s ease;
    overflow: hidden;
}

/* glowing gradient border effect */
.nav-link-unique::before {
    content: "";
    position: absolute;
    inset: 0;
    border-radius: 14px;
    padding: 1px;
    background: linear-gradient(135deg, #25809b, #1f6d84, #d01919);
    -webkit-mask: linear-gradient(#000 0 0) content-box, linear-gradient(#000 0 0);
    -webkit-mask-composite: xor;
    mask-composite: exclude;
}

/* hover shine */
.nav-link-unique::after {
    content: "";
    position: absolute;
    top: 0;
    left: -100%;
    width: 60%;
    height: 100%;
    background: linear-gradient(120deg, transparent, rgba(255,255,255,0.25), transparent);
    transform: skewX(-25deg);
    transition: 0.6s;
}

.nav-link-unique:hover::after {
    left: 120%;
}

/* icon circle */
.icon-wrap {
    width: 36px;
    height: 36px;
    border-radius: 10px;
    display: flex;
    align-items: center;
    justify-content: center;

    background: linear-gradient(135deg, #25809b, #134f62);
    color: #fff;
    font-size: 16px;

    box-shadow: 0 4px 12px rgba(37,128,155,0.4);
}

/* text */
.nav-link-unique .text {
    flex: 1;
    font-size: 14px;
}

/* notification dot */
.badge-dot {
    width: 8px;
    height: 8px;
    border-radius: 50%;
    background: #d01919;
    box-shadow: 0 0 8px #d01919;
}

/* hover effect */
.nav-link-unique:hover {
    transform: translateX(6px) scale(1.02);
    background: rgba(37,128,155,0.15);
}

/* active state */
.nav-link-unique.active {
    background: linear-gradient(135deg, #25809b, #134f62);
    color: #fff;
    box-shadow: 0 8px 25px rgba(37,128,155,0.4);
}

.nav-link-unique.active .icon-wrap {
    background: #fff;
    color: #134f62;
}
</style>
</head>

<body>

<div class="eca-dash-topbar d-none d-lg-block">
  <div class="eca-dash-topbar-inner">
    <span>Eswatini Contractors Association</span>
    <a href="mailto:info@eca.co.sz"><i class="fa fa-envelope"></i> info@eca.co.sz</a>
  </div>
</div>

<nav class="navbar navbar-expand-lg eca-navbar sticky-top">
  <div class="container-fluid">

    <!-- BRAND -->
<a class="navbar-brand brand-wrap d-flex align-items-center gap-3" href="/cpd/index.php">

<div class="logo-box">
<img src="/img/ecalogo.png" class="brand-logo" alt="ECA Logo" onerror="this.src='https://eca.co.sz/cpd/images/logo.jpg'">
</div>

<div class="brand-text d-none d-sm-block">
     <?php if($is_admin): ?>
<div class="brand-title">Admin Dashboard</div>
      <?php endif; ?>
<div class="brand-sub">Eswatini Contractors Association</div>
</div>

</a>

    <div class="ms-auto d-flex align-items-center gap-3">

      <?php if(!empty($_SESSION['user_id'])): ?>

      <!-- Mobile Toggle -->
      <button class="btn btn-light mobile-toggle shadow-sm" onclick="toggleSidebar()">
        <i class="fa fa-bars"></i>
      </button>

      <?php
        $fullName  = trim($_SESSION['full_name'] ?? 'User');
        $firstName = explode(' ', $fullName)[0];
        $dashLink  = ($role === 'ADMIN')
            ? "/cpd/admin/dashboard.php"
            : "/cpd/contractor/dashboard.php";
      ?>

      <!-- USER PROFILE -->
      <div class="dropdown">

        <a class="user-profile dropdown-toggle" href="#" data-bs-toggle="dropdown">

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

        <a class="btn btn-light btn-sm px-4 fw-bold shadow-sm" href="/cpd/login.php">
          Login
        </a>

      <?php endif; ?>

    </div>
  </div>
</nav>

<?php if($is_admin || $is_contractor): ?>
<div class="overlay" onclick="document.body.classList.remove('sidebar-open')"></div>

<div class="dashboard-shell">

  <aside class="sidebar">
    <div class="sidebar-brand">
      <div class="sidebar-badge">CPD</div>
      <div>
        <strong><?= $is_admin ? 'Admin Panel' : 'Officer Panel' ?></strong><br>
        <small><?= e($_SESSION['full_name'] ?? '') ?></small>
      </div>
    </div>

    <ul class="sidebar-nav">
      <?php if($is_admin): ?>


<li>
<a class="<?= $current==='dashboard.php'?'active':'' ?>" 
href="/cpd/admin/dashboard.php">
<i class="fa-solid fa-gauge-high"></i> Dashboard
</a>
</li>



<li>
<a class="<?= $current==='courses.php'?'active':'' ?>" 
href="/cpd/admin/courses.php">
<i class="fa-solid fa-graduation-cap"></i> Courses
</a>
</li>
<li>
<a class="<?= $current==='applications.php'?'active':'' ?>" 
href="/cpd/admin/applications.php">
<i class="fa-solid fa-file-circle-check"></i> Applications
</a>
</li>
<li>
<a class="<?= $current==='course_students.php'?'active':'' ?>" 
href="/cpd/admin/course_students.php">
<i class="fa-solid fa-clipboard-check"></i> Attendance
</a>
</li>

<li>
<a class="<?= $current==='learners.php'?'active':'' ?>" 
href="/cpd/admin/learners.php">
<i class="fa-solid fa-user-graduate"></i> Participants
</a>
</li>

<li>
<a class="<?= $current==='course_resources.php'?'active':'' ?>" 
href="/cpd/admin/course_resources.php">
<i class="fa-solid fa-folder-open"></i> Resource Library
</a>
</li>
<li>
<a class="<?= $current==='feedback.php'?'active':'' ?>" 
href="/cpd/admin/feedback.php">
<i class="fa-solid fa-star"></i> Feedback
</a>
</li>

<li class="nav-item-unique">
    <a class="nav-link-unique <?= $current==='dashboard.php'?'active':'' ?>" 
       href="https://eca.co.sz/portal/dashboard.php">
        
        <span class="icon-wrap">
            <i class="fa-solid fa-gauge-high"></i>
        </span>

        <span class="text">Main Portal</span>

        <!-- optional badge -->
        <span class="badge-dot"></span>
    </a>
</li>
<li>
<a class="<?= $current==='support.php'?'active':'' ?>" 
href="/cpd/admin/support.php">
<i class="fa-solid fa-headset"></i> Support
</a>
</li>
<li>
<a class="<?= $current==='users.php'?'active':'' ?>" 
href="/cpd/admin/users.php">
<i class="fa-solid fa-users"></i> Users
</a>
</li>

<?php endif; ?>


      <?php if($is_contractor): ?>
        <li><a class="<?= $current==='dashboard.php'?'active':'' ?>" href="/cpd/contractor/dashboard.php"><i class="fa-solid fa-house"></i>Dashboard</a></li>
        <li><a class="<?= $current==='courses.php'?'active':'' ?>" href="/cpd/contractor/courses.php"><i class="fa-solid fa-graduation-cap"></i>Courses</a></li>
        <li><a class="<?= $current==='applications.php'?'active':'' ?>" href="/cpd/contractor/applications.php"><i class="fa-solid fa-file-circle-check"></i>Applications</a></li>
        <li><a class="<?= $current==='transcript.php'?'active':'' ?>" href="/cpd/contractor/transcript.php"><i class="fa-solid fa-award"></i>Transcript</a></li>
      <?php endif; ?>

      <li class="logout">
        <a href="/cpd/logout.php"><i class="fa-solid fa-right-from-bracket"></i>Logout</a>
      </li>
    </ul>
  </aside>

  <main class="dashboard-content">
<?php else: ?>
<div class="container py-4">
<?php endif; ?>