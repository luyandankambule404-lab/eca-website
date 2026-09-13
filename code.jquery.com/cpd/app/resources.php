<?php
require_once "../auth.php";
require_role('CONTRACTOR');
require_once "../config.php";

/* =========================
   HELPERS
========================= */
function h($value){
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}

function shortText($text, $limit = 140){
    $text = trim((string)$text);

    if ($text === '') {
        return 'No description available.';
    }

    if (function_exists('mb_strimwidth')) {
        return mb_strimwidth($text, 0, $limit, '...');
    }

    return strlen($text) > $limit ? substr($text, 0, $limit) . '...' : $text;
}

function formatDateValue($date){
    if (empty($date) || $date === '0000-00-00' || $date === '0000-00-00 00:00:00') {
        return 'N/A';
    }

    $ts = strtotime((string)$date);
    return $ts ? date('d M Y', $ts) : 'N/A';
}

function formatFileSize($bytes){
    $bytes = (int)$bytes;

    if ($bytes >= 1048576) {
        return number_format($bytes / 1048576, 2) . ' MB';
    }

    if ($bytes >= 1024) {
        return number_format($bytes / 1024, 2) . ' KB';
    }

    return $bytes . ' B';
}

/* =========================
   USER SESSION DETAILS
========================= */
$user_id    = (int)($_SESSION['user_id'] ?? 0);
$user_email = trim($_SESSION['email'] ?? '');
$user_name  = trim($_SESSION['full_name'] ?? '');
$membership_number = trim($_SESSION['membership_number'] ?? '');

$generated_count = 0;

/* =========================
   GET LOGGED-IN USER DETAILS
   from userss table
========================= */
if ($user_id > 0) {
    $uStmt = $conn->prepare("
        SELECT full_name, email, membership_number
        FROM userss
        WHERE id = ?
        LIMIT 1
    ");

    if ($uStmt) {
        $uStmt->bind_param("i", $user_id);
        $uStmt->execute();
        $uRow = $uStmt->get_result()->fetch_assoc();
        $uStmt->close();

        if ($uRow) {
            if (!empty($uRow['full_name'])) {
                $user_name = $uRow['full_name'];
            }

            if (!empty($uRow['email'])) {
                $user_email = $uRow['email'];
            }

            if (!empty($uRow['membership_number'])) {
                $membership_number = $uRow['membership_number'];
            }
        }
    }
}

if ($user_name === '') {
    $user_name = $user_email !== '' ? explode('@', $user_email)[0] : 'Contractor';
}

/* =========================
   CERTIFICATE COUNT
========================= */
if ($membership_number !== '') {
    $certStmt = $conn->prepare("
        SELECT COUNT(*) AS c
        FROM cpd_applications
        WHERE membership_number = ?
          AND status = 'Approved'
          AND training_status = 'Completed'
          AND certificate_number IS NOT NULL
          AND certificate_number <> ''
    ");

    if ($certStmt) {
        $certStmt->bind_param("s", $membership_number);
        $certStmt->execute();
        $certRow = $certStmt->get_result()->fetch_assoc();
        $generated_count = (int)($certRow['c'] ?? 0);
        $certStmt->close();
    }
}

/* =========================
   LOAD RESOURCES
   Match by membership_number OR learner email
========================= */
$resources = false;

if ($membership_number !== '') {
    $stmt = $conn->prepare("
        SELECT DISTINCT
            r.id,
            r.course_id,
            r.title,
            r.category,
            r.description,
            r.file_path,
            r.file_type,
            r.file_size,
            r.status,
            r.created_at,
            c.title AS course_title,
            c.venue,
            c.start_date,
            c.end_date
        FROM course_resources r
        INNER JOIN courses c
            ON c.id = r.course_id
        INNER JOIN cpd_applications a
            ON a.course_id = r.course_id
        WHERE r.status = 'Published'
          AND a.status = 'Approved'
          AND (
                a.membership_number = ?
                OR LOWER(a.email) = LOWER(?)
          )
        ORDER BY r.created_at DESC
    ");

    if ($stmt) {
        $stmt->bind_param("ss", $membership_number, $user_email);
        $stmt->execute();
        $resources = $stmt->get_result();
    }
} else {
    $stmt = $conn->prepare("
        SELECT DISTINCT
            r.id,
            r.course_id,
            r.title,
            r.category,
            r.description,
            r.file_path,
            r.file_type,
            r.file_size,
            r.status,
            r.created_at,
            c.title AS course_title,
            c.venue,
            c.start_date,
            c.end_date
        FROM course_resources r
        INNER JOIN courses c
            ON c.id = r.course_id
        INNER JOIN cpd_applications a
            ON a.course_id = r.course_id
        WHERE r.status = 'Published'
          AND a.status = 'Approved'
          AND LOWER(a.email) = LOWER(?)
        ORDER BY r.created_at DESC
    ");

    if ($stmt) {
        $stmt->bind_param("s", $user_email);
        $stmt->execute();
        $resources = $stmt->get_result();
    }
}

$total_resources = $resources ? $resources->num_rows : 0;

/* =========================
   COUNT DISTINCT COURSES WITH RESOURCES
========================= */
$course_count = 0;

if ($membership_number !== '') {
    $courseStmt = $conn->prepare("
        SELECT COUNT(DISTINCT r.course_id) AS c
        FROM course_resources r
        INNER JOIN cpd_applications a
            ON a.course_id = r.course_id
        WHERE r.status = 'Published'
          AND a.status = 'Approved'
          AND (
                a.membership_number = ?
                OR LOWER(a.email) = LOWER(?)
          )
    ");

    if ($courseStmt) {
        $courseStmt->bind_param("ss", $membership_number, $user_email);
        $courseStmt->execute();
        $courseRow = $courseStmt->get_result()->fetch_assoc();
        $course_count = (int)($courseRow['c'] ?? 0);
        $courseStmt->close();
    }
} else {
    $courseStmt = $conn->prepare("
        SELECT COUNT(DISTINCT r.course_id) AS c
        FROM course_resources r
        INNER JOIN cpd_applications a
            ON a.course_id = r.course_id
        WHERE r.status = 'Published'
          AND a.status = 'Approved'
          AND LOWER(a.email) = LOWER(?)
    ");

    if ($courseStmt) {
        $courseStmt->bind_param("s", $user_email);
        $courseStmt->execute();
        $courseRow = $courseStmt->get_result()->fetch_assoc();
        $course_count = (int)($courseRow['c'] ?? 0);
        $courseStmt->close();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">

<title>Resource Library</title>

<link href="https://fonts.googleapis.com/css2?family=Barlow:wght@400;500;600;700;800&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">

<style>
:root{
    --blue-dark:#29486f;
    --blue:#3c6aa1;
    --blue-soft:#edf3fb;
    --green:#2f9a54;
    --green-soft:#edf8f0;
    --gold:#ef9b21;
    --gold-soft:#fff6e9;
    --red:#b42318;
    --red-soft:#fff1f2;
    --text:#2b4468;
    --muted:#6c7f99;
    --border:#cfd7e3;
    --card-bg:#ffffff;
    --page-bg:#eef2f7;
    --shadow:0 3px 10px rgba(30,53,88,0.10);
    --shadow-soft:0 6px 18px rgba(30,53,88,0.08);
    --radius:14px;
}

*{
    box-sizing:border-box;
}

html,
body{
    margin:0;
    padding:0;
    font-family:'Barlow',sans-serif;
    background:linear-gradient(to bottom, #f5f7fb 0%, #edf1f7 100%);
    color:var(--text);
    overflow-x:hidden;
}

/* =========================
   LAYOUT
========================= */
.layout{
    display:flex;
    min-height:100vh;
}

/* =========================
   SIDEBAR
========================= */
.sidebar{
    width:240px;
    background:#ffffff;
    border-right:1px solid var(--border);
    padding:16px 12px;
    position:fixed;
    top:0;
    left:0;
    bottom:0;
    overflow-y:auto;
    z-index:1000;
    transition:.25s ease;
}

.brand-box{
    background:linear-gradient(180deg, #345b8e 0%, #29486f 100%);
    color:#fff;
    border-radius:14px;
    padding:14px 12px;
    margin-bottom:16px;
    box-shadow:var(--shadow);
}

.brand-box h4{
    margin:0;
    font-size:18px;
    font-weight:800;
}

.brand-box small{
    opacity:.92;
    font-size:11px;
    letter-spacing:.2px;
}

.menu-title{
    font-size:11px;
    color:#7b8ca6;
    font-weight:800;
    text-transform:uppercase;
    margin:12px 8px 8px;
    letter-spacing:.8px;
}

.sidebar a{
    display:flex;
    align-items:center;
    justify-content:space-between;
    text-decoration:none;
    color:var(--text);
    border:1px solid transparent;
    border-radius:12px;
    padding:10px 11px;
    margin-bottom:6px;
    font-size:14px;
    font-weight:700;
    transition:.2s ease;
}

.sidebar a .left{
    display:flex;
    align-items:center;
    gap:9px;
    min-width:0;
}

.sidebar a i{
    color:var(--blue);
    width:16px;
    text-align:center;
    flex-shrink:0;
}

.sidebar a:hover,
.sidebar a.active{
    background:var(--blue-soft);
    border-color:#c8d7ea;
    color:var(--blue-dark);
}

.sidebar a.active i,
.sidebar a:hover i{
    color:var(--blue-dark);
}

.menu-badge{
    min-width:22px;
    height:22px;
    border-radius:999px;
    background:var(--blue-dark);
    color:#fff;
    font-size:11px;
    display:flex;
    align-items:center;
    justify-content:center;
    padding:0 7px;
    flex-shrink:0;
    font-weight:700;
}

/* =========================
   MAIN
========================= */
.main{
    margin-left:240px;
    width:calc(100% - 240px);
}

.topbar{
    background:#fff;
    border-bottom:1px solid var(--border);
    padding:12px 18px;
    display:flex;
    justify-content:space-between;
    align-items:center;
    gap:12px;
}

.topbar h5{
    margin:0;
    font-size:18px;
    font-weight:800;
    color:var(--blue-dark);
}

.topbar small{
    color:var(--muted);
    font-size:12px;
}

.topbar-left{
    display:flex;
    align-items:center;
    gap:8px;
}

.user-chip{
    display:flex;
    align-items:center;
    gap:10px;
    background:#f8fafc;
    border:1px solid var(--border);
    padding:7px 10px;
    border-radius:14px;
    min-width:0;
}

.user-chip img{
    width:40px;
    height:40px;
    border-radius:12px;
    object-fit:cover;
    flex-shrink:0;
}

.user-chip .meta{
    min-width:0;
}

.user-chip .meta div:first-child{
    font-weight:800;
    color:#29486f;
    white-space:nowrap;
    overflow:hidden;
    text-overflow:ellipsis;
    max-width:180px;
}

.user-chip .meta div:last-child{
    font-size:13px;
    color:#6c7f99;
    white-space:nowrap;
    overflow:hidden;
    text-overflow:ellipsis;
    max-width:220px;
}

.page-wrap{
    padding:18px;
}

.system-title{
    background:linear-gradient(180deg, #3f5f8d 0%, #29486f 100%);
    color:#fff;
    text-align:center;
    padding:16px 14px;
    border-radius:0;
    box-shadow:var(--shadow);
    margin-bottom:16px;
}

.system-title h1{
    margin:0;
    font-size:24px;
    font-weight:800;
    letter-spacing:.3px;
}

/* =========================
   STATS
========================= */
.stats-grid{
    display:grid;
    grid-template-columns:repeat(3, 1fr);
    gap:16px;
    margin-bottom:18px;
}

.stat-card{
    background:#fff;
    border:1px solid var(--border);
    border-radius:14px;
    padding:18px;
    box-shadow:var(--shadow);
    transition:.2s ease;
}

.stat-card:hover{
    transform:translateY(-2px);
}

.stat-top{
    display:flex;
    align-items:flex-start;
    justify-content:space-between;
    gap:12px;
}

.stat-label{
    display:block;
    font-size:12px;
    font-weight:800;
    color:var(--muted);
    margin-bottom:8px;
    text-transform:uppercase;
    letter-spacing:.5px;
}

.stat-value{
    font-size:28px;
    font-weight:800;
    line-height:1.1;
    color:var(--blue-dark);
    margin-bottom:4px;
}

.stat-sub{
    font-size:12px;
    color:var(--muted);
    font-weight:600;
}

.stat-icon{
    width:48px;
    height:48px;
    border-radius:14px;
    display:flex;
    align-items:center;
    justify-content:center;
    font-size:18px;
    flex-shrink:0;
}

.stat-icon.blue{
    background:var(--blue-soft);
    color:var(--blue-dark);
}

.stat-icon.green{
    background:var(--green-soft);
    color:var(--green);
}

.stat-icon.gold{
    background:var(--gold-soft);
    color:var(--gold);
}

/* =========================
   PANEL
========================= */
.panel{
    background:#fff;
    border:1px solid var(--border);
    border-radius:14px;
    box-shadow:var(--shadow);
    overflow:hidden;
}

.panel-header{
    padding:14px 16px 10px;
    font-size:16px;
    font-weight:800;
    color:var(--blue-dark);
    position:relative;
}

.panel-header:after{
    content:"";
    display:block;
    width:100%;
    height:2px;
    background:#d9e0ea;
    margin-top:10px;
}

.panel-body{
    padding:16px;
}

.card-title-row{
    display:flex;
    justify-content:space-between;
    align-items:center;
    gap:10px;
    margin-bottom:14px;
    flex-wrap:wrap;
}

.card-title-row .small-note{
    font-size:13px;
    color:var(--muted);
    font-weight:700;
}

/* =========================
   FILTERS
========================= */
.search-wrap{
    display:flex;
    gap:12px;
    flex-wrap:wrap;
    margin-bottom:18px;
}

.search-box{
    flex:1;
    min-width:240px;
    position:relative;
}

.search-box i{
    position:absolute;
    top:50%;
    left:14px;
    transform:translateY(-50%);
    color:var(--muted);
}

.search-box input,
.filter-select{
    width:100%;
    height:48px;
    border-radius:12px;
    border:1px solid var(--border);
    background:#fff;
    outline:none;
    font-family:'Barlow',sans-serif;
    font-size:14px;
    color:var(--text);
}

.search-box input{
    padding:0 14px 0 42px;
}

.filter-select{
    padding:0 14px;
    min-width:210px;
}

/* =========================
   RESOURCE GRID
========================= */
.resource-grid{
    display:grid;
    grid-template-columns:repeat(3, minmax(0,1fr));
    gap:16px;
}

.resource-card{
    background:#fff;
    border:1px solid #d8e0ea;
    border-radius:14px;
    overflow:hidden;
    transition:.25s ease;
    box-shadow:0 4px 14px rgba(30,53,88,0.06);
    height:100%;
    display:flex;
    flex-direction:column;
}

.resource-card:hover{
    border-color:#c9d7e6;
    box-shadow:0 8px 20px rgba(30,53,88,0.10);
    transform:translateY(-2px);
}

.resource-head{
    padding:16px 16px 10px;
    display:flex;
    align-items:center;
    justify-content:space-between;
    gap:10px;
    background:linear-gradient(180deg,#f7fbff 0%, #ffffff 100%);
}

.resource-icon{
    width:52px;
    height:52px;
    border-radius:14px;
    display:flex;
    align-items:center;
    justify-content:center;
    font-size:20px;
    color:#fff;
    background:linear-gradient(180deg, #456ea6 0%, #30537f 100%);
    box-shadow:0 8px 18px rgba(48,83,127,.18);
}

.resource-type-pdf .resource-icon{
    background:linear-gradient(180deg,#d94b4b 0%, #b42318 100%);
}

.resource-type-doc .resource-icon{
    background:linear-gradient(180deg,#3b82f6 0%, #1d4ed8 100%);
}

.resource-type-xls .resource-icon{
    background:linear-gradient(180deg,#22c55e 0%, #15803d 100%);
}

.resource-type-zip .resource-icon{
    background:linear-gradient(180deg,#8b5cf6 0%, #6d28d9 100%);
}

.file-badge{
    font-size:11px;
    font-weight:800;
    padding:7px 10px;
    border-radius:999px;
    background:var(--blue-soft);
    color:var(--blue-dark);
    text-transform:uppercase;
}

.resource-body{
    padding:0 16px 16px;
    flex:1;
}

.resource-title{
    font-size:16px;
    font-weight:800;
    color:var(--blue-dark);
    margin:4px 0 8px;
    line-height:1.4;
    min-height:44px;
}

.resource-desc{
    font-size:13px;
    color:var(--muted);
    line-height:1.6;
    min-height:62px;
    margin-bottom:14px;
}

.resource-course{
    display:inline-flex;
    align-items:center;
    gap:8px;
    background:var(--blue-soft);
    color:var(--blue-dark);
    padding:8px 12px;
    border-radius:999px;
    font-size:12px;
    font-weight:700;
    margin-bottom:14px;
}

.resource-meta{
    display:grid;
    grid-template-columns:1fr 1fr;
    gap:10px;
}

.resource-meta div{
    background:#fafcff;
    border:1px solid #e1e8f0;
    border-radius:12px;
    padding:12px;
}

.resource-meta span{
    display:block;
    font-size:10px;
    font-weight:800;
    color:var(--muted);
    text-transform:uppercase;
    margin-bottom:5px;
    letter-spacing:.4px;
}

.resource-meta strong{
    display:block;
    font-size:12px;
    color:var(--text);
    line-height:1.45;
    word-break:break-word;
}

.resource-actions{
    display:flex;
    gap:10px;
    padding:14px 16px 16px;
    border-top:1px solid #edf2f7;
}

.btn-main,
.btn-lite{
    flex:1;
    text-decoration:none;
    border-radius:12px;
    padding:10px 12px;
    font-size:13px;
    font-weight:700;
    display:flex;
    align-items:center;
    justify-content:center;
    gap:7px;
    transition:.2s ease;
}

.btn-main{
    background:linear-gradient(180deg, #456ea6 0%, #30537f 100%);
    color:#fff;
    border:1px solid #30537f;
    box-shadow:0 4px 10px rgba(48,83,127,.18);
}

.btn-main:hover{
    color:#fff;
    transform:translateY(-1px);
}

.btn-lite{
    background:#fff;
    color:var(--blue-dark);
    border:1px solid #aeb9cb;
}

.btn-lite:hover{
    background:#f5f8fd;
    color:var(--blue-dark);
    border-color:var(--blue);
}

/* =========================
   EMPTY STATE
========================= */
.empty-box{
    text-align:center;
    padding:30px 18px;
    border:1px dashed #d7e1eb;
    border-radius:14px;
    background:#fbfcfe;
    margin-top:10px;
}

.empty-box i{
    font-size:30px;
    color:#a8b4c4;
    margin-bottom:10px;
}

.empty-box h6{
    font-weight:800;
    margin-bottom:6px;
    color:var(--text);
    font-size:17px;
}

.empty-box p{
    margin:0;
    color:var(--muted);
    font-size:13px;
    font-weight:600;
}

.debug-box{
    margin-top:14px;
    padding:12px;
    border-radius:12px;
    background:#f8fafc;
    border:1px solid #e2e8f0;
    font-size:12px;
    color:var(--muted);
    line-height:1.7;
}

.debug-box strong{
    color:var(--blue-dark);
}

/* =========================
   MOBILE
========================= */
.mobile-toggle{
    display:none;
}

.bottom-nav{
    display:none;
}

@media (max-width:1199px){
    .resource-grid{
        grid-template-columns:repeat(2, minmax(0,1fr));
    }
}

@media (max-width:991px){
    .sidebar{
        left:-260px;
    }

    .sidebar.show{
        left:0;
    }

    .main{
        margin-left:0;
        width:100%;
    }

    .topbar{
        padding:10px 12px;
    }

    .page-wrap{
        padding:12px;
    }

    .mobile-toggle{
        display:inline-flex;
        width:40px;
        height:40px;
        align-items:center;
        justify-content:center;
        border:1px solid var(--border);
        border-radius:10px;
        background:#fff;
        color:var(--blue-dark);
        font-size:16px;
        cursor:pointer;
        margin-right:8px;
    }

    .user-chip .meta{
        display:none;
    }

    .stats-grid{
        grid-template-columns:1fr;
    }

    .resource-grid{
        grid-template-columns:1fr;
    }

    .resource-meta{
        grid-template-columns:1fr;
    }

    .bottom-nav{
        display:flex;
        position:fixed;
        left:0;
        right:0;
        bottom:0;
        z-index:1250;
        background:#fff;
        border-top:1px solid var(--border);
        padding:8px 4px calc(8px + env(safe-area-inset-bottom));
        justify-content:space-around;
    }

    .bottom-nav a{
        text-decoration:none;
        color:var(--muted);
        text-align:center;
        font-size:11px;
        font-weight:700;
        width:20%;
    }

    .bottom-nav a i{
        display:block;
        font-size:17px;
        margin-bottom:4px;
    }

    .bottom-nav a.active{
        color:var(--blue-dark);
    }

    .page-wrap{
        padding-bottom:85px;
    }
}

@media (max-width:767px){
    .system-title h1{
        font-size:22px;
    }
}
</style>
</head>

<body>

<div class="layout">

    <!-- SIDEBAR -->
    <div class="sidebar" id="sidebar">
        <div class="brand-box">
            <h4>
                <i class="fa-solid fa-graduation-cap me-2"></i>
                ECA
            </h4>
            <small>Contractor Learning Portal</small>
        </div>

        <div class="menu-title">Main Menu</div>

        <a href="dashboard.php">
            <div class="left">
                <i class="fa fa-house"></i>
                Dashboard
            </div>
        </a>

        <a href="my_courses.php">
            <div class="left">
                <i class="fa fa-book-open"></i>
                My Courses
            </div>
        </a>

        <a href="certificates.php">
            <div class="left">
                <i class="fa fa-certificate"></i>
                My Certificates
            </div>

            <?php if ($generated_count > 0): ?>
                <span class="menu-badge"><?= number_format($generated_count) ?></span>
            <?php endif; ?>
        </a>

        <a href="resources.php" class="active">
            <div class="left">
                <i class="fa fa-folder-open"></i>
                Resource Library
            </div>
        </a>

        <a href="events.php">
            <div class="left">
                <i class="fa fa-calendar-days"></i>
                Events
            </div>
        </a>

        <a href="points_tracker.php">
            <div class="left">
                <i class="fa fa-chart-line"></i>
                CPD Tracker
            </div>
        </a>

        <a href="feedback.php">
            <div class="left">
                <i class="fa fa-comment-dots"></i>
                Feedback
            </div>
        </a>

        <a href="logout.php">
            <div class="left">
                <i class="fa fa-right-from-bracket"></i>
                Logout
            </div>
        </a>
    </div>

    <!-- MAIN -->
    <main class="main">

        <div class="topbar">
            <div class="topbar-left">
                <button class="mobile-toggle" onclick="toggleSidebar()">
                    <i class="fa-solid fa-bars"></i>
                </button>

                <div>
                    <h5>Resource Library</h5>
                    <small>Only resources for courses you are approved to attend</small>
                </div>
            </div>

            <div class="user-chip">
                <img src="https://ui-avatars.com/api/?name=<?= urlencode($user_name) ?>&background=29486f&color=ffffff&rounded=true&bold=true&size=128" alt="User">

                <div class="meta">
                    <div><?= h($user_name) ?></div>
                    <div><?= h($user_email) ?></div>
                </div>
            </div>
        </div>

        <div class="page-wrap">

            <div class="system-title">
                <h1>Resource Library</h1>
            </div>

            <div class="stats-grid">

                <div class="stat-card">
                    <div class="stat-top">
                        <div>
                            <span class="stat-label">My Resources</span>
                            <div class="stat-value"><?= number_format($total_resources) ?></div>
                            <div class="stat-sub">Files available for your enrolled courses</div>
                        </div>

                        <div class="stat-icon blue">
                            <i class="fa fa-folder-open"></i>
                        </div>
                    </div>
                </div>

                <div class="stat-card">
                    <div class="stat-top">
                        <div>
                            <span class="stat-label">Enrolled Courses</span>
                            <div class="stat-value"><?= number_format($course_count) ?></div>
                            <div class="stat-sub">Approved courses with materials</div>
                        </div>

                        <div class="stat-icon green">
                            <i class="fa fa-book-open"></i>
                        </div>
                    </div>
                </div>

                <div class="stat-card">
                    <div class="stat-top">
                        <div>
                            <span class="stat-label">Downloads</span>
                            <div class="stat-value">ON</div>
                            <div class="stat-sub">Resources are ready anytime</div>
                        </div>

                        <div class="stat-icon gold">
                            <i class="fa fa-download"></i>
                        </div>
                    </div>
                </div>

            </div>

            <div class="panel">
                <div class="panel-header">
                    Browse My Resources
                </div>

                <div class="panel-body">

                    <div class="card-title-row">
                        <div></div>
                        <div class="small-note">Only enrolled-course materials</div>
                    </div>

                    <div class="search-wrap">
                        <div class="search-box">
                            <i class="fa fa-search"></i>
                            <input type="text" id="resourceSearch" placeholder="Search by title, course or category...">
                        </div>

                        <select class="filter-select" id="resourceFilter">
                            <option value="">All Categories</option>
                            <option value="manual">Manual</option>
                            <option value="guide">Guide</option>
                            <option value="template">Template</option>
                            <option value="policy">Policy</option>
                            <option value="presentation">Presentation</option>
                            <option value="form">Form</option>
                            <option value="notes">Notes</option>
                            <option value="checklist">Checklist</option>
                        </select>
                    </div>

                    <?php if ($resources && $total_resources > 0): ?>

                        <div class="resource-grid" id="resourceGrid">

                            <?php while ($row = $resources->fetch_assoc()): ?>
                                <?php
                                    $title        = $row['title'] ?? 'Untitled Resource';
                                    $description  = $row['description'] ?? 'No description available.';
                                    $file_path    = $row['file_path'] ?? '#';
                                    $file_type    = strtolower($row['file_type'] ?? 'file');
                                    $category     = $row['category'] ?? 'General';
                                    $created_at   = formatDateValue($row['created_at'] ?? '');
                                    $course_title = $row['course_title'] ?? 'Course';
                                    $file_size    = formatFileSize($row['file_size'] ?? 0);

                                    $type_class = 'resource-type-doc';
                                    $type_icon  = 'fa-file-lines';

                                    if (strpos($file_type, 'pdf') !== false) {
                                        $type_class = 'resource-type-pdf';
                                        $type_icon  = 'fa-file-pdf';
                                    } elseif (
                                        strpos($file_type, 'xls') !== false ||
                                        strpos($file_type, 'xlsx') !== false ||
                                        strpos($file_type, 'csv') !== false
                                    ) {
                                        $type_class = 'resource-type-xls';
                                        $type_icon  = 'fa-file-excel';
                                    } elseif (
                                        strpos($file_type, 'zip') !== false ||
                                        strpos($file_type, 'rar') !== false
                                    ) {
                                        $type_class = 'resource-type-zip';
                                        $type_icon  = 'fa-file-zipper';
                                    } elseif (
                                        strpos($file_type, 'doc') !== false ||
                                        strpos($file_type, 'docx') !== false
                                    ) {
                                        $type_class = 'resource-type-doc';
                                        $type_icon  = 'fa-file-word';
                                    }
                                ?>

                                <div
                                    class="resource-item <?= h($type_class) ?>"
                                    data-title="<?= h(strtolower($title)) ?>"
                                    data-category="<?= h(strtolower($category)) ?>"
                                    data-course="<?= h(strtolower($course_title)) ?>"
                                >
                                    <div class="resource-card">

                                        <div class="resource-head">
                                            <div class="resource-icon">
                                                <i class="fa <?= h($type_icon) ?>"></i>
                                            </div>

                                            <div class="file-badge">
                                                <?= h(strtoupper($file_type)) ?>
                                            </div>
                                        </div>

                                        <div class="resource-body">
                                            <div class="resource-title">
                                                <?= h($title) ?>
                                            </div>

                                            <div class="resource-desc">
                                                <?= h(shortText($description, 140)) ?>
                                            </div>

                                            <div class="resource-course">
                                                <i class="fa fa-book-open"></i>
                                                <?= h($course_title) ?>
                                            </div>

                                            <div class="resource-meta">
                                                <div>
                                                    <span>Category</span>
                                                    <strong><?= h($category) ?></strong>
                                                </div>

                                                <div>
                                                    <span>Uploaded</span>
                                                    <strong><?= h($created_at) ?></strong>
                                                </div>

                                                <div>
                                                    <span>File Size</span>
                                                    <strong><?= h($file_size) ?></strong>
                                                </div>

                                                <div>
                                                    <span>Course Date</span>
                                                    <strong><?= h(formatDateValue($row['start_date'] ?? '')) ?></strong>
                                                </div>
                                            </div>
                                        </div>

                                        <div class="resource-actions">
                                            <a href="../<?= h($file_path) ?>" target="_blank" class="btn-main">
                                                <i class="fa fa-eye"></i>
                                                View
                                            </a>

                                            <a href="../<?= h($file_path) ?>" download class="btn-lite">
                                                <i class="fa fa-download"></i>
                                                Download
                                            </a>
                                        </div>

                                    </div>
                                </div>

                            <?php endwhile; ?>

                        </div>

                    <?php else: ?>

                        <div class="empty-box">
                            <i class="fa fa-folder-open"></i>

                            <h6>No resources available for your courses yet</h6>

                            <p>
                                You will see downloadable files here only for courses you are approved to attend.
                            </p>

                            <div class="debug-box">
                                Detected Membership No:
                                <strong><?= h($membership_number ?: 'Not found') ?></strong><br>

                                Detected Email:
                                <strong><?= h($user_email ?: 'Not found') ?></strong><br>

                                Required checks:
                                <strong>course_resources.status = Published</strong>,
                                <strong>cpd_applications.status = Approved</strong>,
                                and matching <strong>course_id</strong>.
                            </div>
                        </div>

                    <?php endif; ?>

                </div>
            </div>

        </div>
    </main>
</div>

<div class="bottom-nav d-lg-none">
    <a href="dashboard.php">
        <i class="fa fa-house"></i>
        Home
    </a>

    <a href="my_courses.php">
        <i class="fa fa-book"></i>
        Courses
    </a>

    <a href="certificates.php">
        <i class="fa fa-certificate"></i>
        Certs
    </a>

    <a href="resources.php" class="active">
        <i class="fa fa-folder-open"></i>
        Files
    </a>

    <a href="account.php">
        <i class="fa fa-user"></i>
        Account
    </a>
</div>

<script>
function toggleSidebar() {
    document.getElementById('sidebar').classList.toggle('show');
}

document.addEventListener('click', function(e){
    const sidebar = document.getElementById('sidebar');
    const toggle  = document.querySelector('.mobile-toggle');

    if (window.innerWidth <= 991 && sidebar.classList.contains('show')) {
        if (!sidebar.contains(e.target) && toggle && !toggle.contains(e.target)) {
            sidebar.classList.remove('show');
        }
    }
});

const searchInput = document.getElementById('resourceSearch');
const filterSelect = document.getElementById('resourceFilter');
const items = document.querySelectorAll('.resource-item');

function filterResources(){
    const q = (searchInput?.value || '').toLowerCase().trim();
    const f = (filterSelect?.value || '').toLowerCase().trim();

    items.forEach(item => {
        const title = item.dataset.title || '';
        const category = item.dataset.category || '';
        const course = item.dataset.course || '';

        const matchSearch = title.includes(q) || category.includes(q) || course.includes(q);
        const matchFilter = !f || category.includes(f);

        item.style.display = (matchSearch && matchFilter) ? '' : 'none';
    });
}

if (searchInput) {
    searchInput.addEventListener('input', filterResources);
}

if (filterSelect) {
    filterSelect.addEventListener('change', filterResources);
}
</script>

</body>
</html>