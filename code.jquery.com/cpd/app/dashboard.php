<?php
require_once "../auth1.php";
require_role('CONTRACTOR');
require_once "../config.php";

$user_id    = (int)($_SESSION['user_id'] ?? 0);
$user_email = $_SESSION['email'] ?? '';
$user_name  = $_SESSION['full_name'] ?? (($user_email && strpos($user_email, '@') !== false) ? explode('@', $user_email)[0] : 'User');

$user_email_esc = mysqli_real_escape_string($conn, $user_email);
$user_id_esc    = (int)$user_id;

/* -----------------------------------------------------------
   BASIC USER / COMPANY DATA
----------------------------------------------------------- */
$profile_sql = "
    SELECT a.*, c.title AS course_title, c.start_date, c.end_date, c.points AS course_points, c.venue
    FROM cpd_applications a
    LEFT JOIN courses c ON c.id = a.course_id
    WHERE a.email = '$user_email_esc'
      AND a.status = 'Approved'
    ORDER BY a.created_at DESC
    LIMIT 1
";
$profile_res = $conn->query($profile_sql);
$profile     = ($profile_res && $profile_res->num_rows > 0) ? $profile_res->fetch_assoc() : null;

$company_name = $profile['company_name'] ?? 'BuildTech Ltd.';
$discipline   = $profile['discipline'] ?? 'Engineering & Construction';
$position     = $profile['position'] ?? 'Project Manager';
$phone        = $profile['phone'] ?? '+268 0000 0000';
$focus_area3   = !empty($profile['qualification_name']) ? $profile['qualification_name'] : ($profile['discipline'] ?? 'Construction Operations & Contract Training');
$focus_area        = "Construction Operations & Contract Training";

/* -----------------------------------------------------------
   CPD POINTS
----------------------------------------------------------- */
$points_sql = "
    SELECT COALESCE(SUM(points), 0) AS total_points
    FROM cpd_points_ledger
    WHERE user_id = $user_id_esc
";
$points_res = $conn->query($points_sql);
$points     = 0;
if ($points_res && $row = $points_res->fetch_assoc()) {
    $points = (float)$row['total_points'];
}

$target_points    = 36;
$progress_percent = ($target_points > 0) ? min(100, round(($points / $target_points) * 100, 1)) : 0;
$remaining_points = max(0, $target_points - $points);

/* -----------------------------------------------------------
   CURRENT / RECENT COURSES
----------------------------------------------------------- */
$course_list_sql = "
    SELECT c.*, a.training_status, a.certificate_number, a.created_at AS app_date
    FROM cpd_applications a
    INNER JOIN courses c ON c.id = a.course_id
    WHERE a.email = '$user_email_esc'
      AND a.status = 'Approved'
    ORDER BY c.start_date DESC, a.created_at DESC
    LIMIT 6
";
$course_list_res = $conn->query($course_list_sql);

$course_titles = [];
$activities    = [];

if ($course_list_res && $course_list_res->num_rows > 0) {
    while ($r = $course_list_res->fetch_assoc()) {
        $course_titles[] = $r;
        $date_label = !empty($r['start_date']) ? date('F Y', strtotime($r['start_date'])) : date('F Y', strtotime($r['app_date']));
        $activities[] = [
            'title' => $r['title'],
            'date'  => $date_label
        ];
    }
}

$current_course = $course_titles[0]['title'] ?? 'Contract Training Workshop';
$latest_cert    = 'Safety Management Course';
foreach ($course_titles as $ct) {
    if (!empty($ct['certificate_number']) || strtoupper((string)($ct['training_status'] ?? '')) === 'COMPLETED') {
        $latest_cert = $ct['title'];
        break;
    }
}

/* -----------------------------------------------------------
   PIE / CATEGORY SUMMARY
----------------------------------------------------------- */
$pie_labels = ['Contract Training', 'Safety Management', 'Tendering Fundamentals', 'Quality Mgmt'];
$pie_values = [35, 25, 20, 20];

if (count($course_titles) > 0) {
    $category_map = [
        'Contract Training'        => 0,
        'Safety Management'        => 0,
        'Tendering Fundamentals'   => 0,
        'Quality Mgmt'             => 0
    ];

    foreach ($course_titles as $ct) {
        $title = strtolower($ct['title'] ?? '');
        if (strpos($title, 'contract') !== false) {
            $category_map['Contract Training']++;
        } elseif (strpos($title, 'safety') !== false) {
            $category_map['Safety Management']++;
        } elseif (strpos($title, 'tender') !== false) {
            $category_map['Tendering Fundamentals']++;
        } elseif (strpos($title, 'quality') !== false) {
            $category_map['Quality Mgmt']++;
        }
    }

    $sum_categories = array_sum($category_map);
    if ($sum_categories > 0) {
        $pie_labels = array_keys($category_map);
        $pie_values = [];
        foreach ($category_map as $val) {
            $pie_values[] = round(($val / $sum_categories) * 100);
        }

        // Adjust total to 100
        $diff = 100 - array_sum($pie_values);
        if (isset($pie_values[0])) {
            $pie_values[0] += $diff;
        }
    }
}

 $logged_email = $_SESSION['email'] ?? $email ?? '';

$employees = [];

if (!empty($logged_email)) {
    $stmt = $conn->prepare("
        SELECT 
            a.full_name,
            a.company_name,
            a.position,
            a.email,
            GROUP_CONCAT(DISTINCT c.title ORDER BY c.start_date DESC SEPARATOR ', ') AS courses_attended,
            COALESCE(SUM(DISTINCT l.points), 0) AS employee_points
        FROM cpd_applications a
        LEFT JOIN courses c ON c.id = a.course_id
        LEFT JOIN user u ON u.email = a.email
        LEFT JOIN cpd_points_ledger l ON l.user_id = u.id
        WHERE a.email = ?
          AND a.status = 'Approved'
        GROUP BY a.email, a.full_name, a.company_name, a.position
        ORDER BY a.full_name ASC
        LIMIT 1
    ");
    $stmt->bind_param("s", $logged_email);
    $stmt->execute();
    $employees_res = $stmt->get_result();

    if ($employees_res && $employees_res->num_rows > 0) {
        while ($emp = $employees_res->fetch_assoc()) {
            $employees[] = $emp;
        }
    }
    $stmt->close();
}

if (empty($employees)) {
    $employees = [
        [
            'full_name' => $user_name ?? '',
            'company_name' => $company_name ?? '',
            'position' => $position ?? '',
            'email' => $logged_email,
            'employee_points' => $points ?? 0,
            'courses_attended' => $current_course ?? 'No course attended yet'
        ]
    ];
}
/* -----------------------------------------------------------
   OTHER COUNTS
----------------------------------------------------------- */
$active_courses_count = count($course_titles);

$upcoming_sql = "
    SELECT COUNT(*) AS total_upcoming
    FROM courses
    WHERE start_date > NOW()
";
$upcoming_res = $conn->query($upcoming_sql);
$upcoming_count = 0;
if ($upcoming_res && $urow = $upcoming_res->fetch_assoc()) {
    $upcoming_count = (int)$urow['total_upcoming'];
}

/* -----------------------------------------------------------
   FINANCIAL YEAR
----------------------------------------------------------- */
$current_year = (int)date('Y');
$current_month = (int)date('n');
if ($current_month >= 4) {
    $financial_year = $current_year . '/' . ($current_year + 1);
} else {
    $financial_year = ($current_year - 1) . '/' . $current_year;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>CPD Point System Dashboard</title>

    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&family=Barlow:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link rel="stylesheet" href="/css/dashboard.css">

   <style>
    :root{
        --blue-dark:#000066;
        --blue:#000066;
        --blue-soft:#eef1f8;
        --green:#000066;
        --green-soft:#eef1f8;
        --gold:#d90920;
        --gold-soft:#fff1f3;
        --yellow:#f4c542;
        --silver:#e6eaf2;
        --text:#111827;
        --muted:#667085;
        --border:#e6eaf2;
        --card-bg:#ffffff;
        --page-bg:#f5f7fb;
        --shadow:0 12px 30px rgba(0, 0, 102, 0.06);
    }

    *{
        box-sizing:border-box;
    }

    html, body{
        margin:0;
        padding:0;
        overflow-x:hidden;
    }

    body{
        background:linear-gradient(to bottom, #f5f7fb 0%, #edf1f7 100%);
        font-family:'Plus Jakarta Sans', 'Barlow', sans-serif;
        color:var(--text);
    }

    .layout{
        display:flex;
        min-height:100vh;
    }

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
    }

    .brand-box{
        background:#000066;
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
    }

    .main{
        margin-left:240px;
        width:calc(100% - 240px);
        padding:0;
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

    .dashboard-wrap{
        padding:18px;
    }

    .system-title{
        background:#000066;
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

    .top-flow{
        display:grid;
        grid-template-columns:1fr 55px 1fr 55px 1fr;
        gap:14px;
        align-items:center;
        margin-bottom:16px;
    }

    .flow-arrow{
        text-align:center;
        font-size:28px;
        color:#6a7f9f;
        font-weight:700;
    }

    .flow-card{
        background:var(--card-bg);
        border:1px solid var(--border);
        border-radius:8px;
        box-shadow:var(--shadow);
        overflow:hidden;
        min-width:0;
    }

    .flow-head{
        color:#fff;
        font-size:14px;
        font-weight:800;
        padding:10px 12px;
        display:flex;
        align-items:center;
        justify-content:center;
        gap:8px;
        text-align:center;
    }

    .flow-head.blue{ background:#000066; }
    .flow-head.gray{ background:#334155; }
    .flow-head.green{ background:#d90920; }

    .flow-body{
        text-align:center;
        padding:14px 10px;
        min-height:86px;
        display:flex;
        flex-direction:column;
        align-items:center;
        justify-content:center;
        overflow-wrap:break-word;
        word-break:break-word;
    }

    .flow-body .big{
        font-size:16px;
        font-weight:800;
        color:var(--blue-dark);
        line-height:1.2;
        margin-bottom:4px;
    }

    .flow-body .small{
        font-size:12px;
        color:var(--muted);
        font-weight:600;
    }

    .separator{
        border:none;
        border-top:2px solid #d3dae4;
        margin:16px 0 20px;
    }

    .grid-main{
        display:grid;
        grid-template-columns:220px minmax(0, 1fr) 250px;
        gap:16px;
        align-items:start;
    }

    .panel{
        background:#fff;
        border:1px solid var(--border);
        border-radius:8px;
        box-shadow:var(--shadow);
        min-width:0;
    }

    .panel-header{
        padding:12px 14px 8px;
        font-size:15px;
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
        margin-top:8px;
    }

    .profile-card{
        padding:14px;
        overflow-wrap:break-word;
        word-break:break-word;
    }

    .profile-photo{
        width:100%;
        aspect-ratio:1 / 1;
        border-radius:4px;
        overflow:hidden;
        border:1px solid #d9e0ea;
        background:#f3f5f8;
        margin-bottom:14px;
    }

    .profile-photo img{
        width:100%;
        height:100%;
        object-fit:cover;
    }

    .profile-name{
        font-size:20px;
        font-weight:800;
        color:#234874;
        line-height:1.1;
        margin-bottom:3px;
        text-align:center;
    }

    .profile-role{
        text-align:center;
        font-size:14px;
        color:#2d425f;
        font-weight:600;
        margin-bottom:3px;
    }

    .profile-company{
        text-align:center;
        font-size:15px;
        color:#1c3557;
        font-weight:800;
        margin-bottom:12px;
    }

    .contact-block{
        border-top:2px solid #dde4ec;
        padding-top:12px;
        margin-top:8px;
    }

    .contact-item{
        display:flex;
        align-items:flex-start;
        gap:9px;
        margin-bottom:10px;
        font-size:13px;
        font-weight:600;
        color:#35516f;
        overflow-wrap:break-word;
        word-break:break-word;
    }

    .contact-item i{
        width:18px;
        color:#456ea6;
        font-size:15px;
        margin-top:2px;
        flex-shrink:0;
    }

    .competency-card{
        margin-top:12px;
        padding:12px 14px 14px;
    }

    .competency-title{
        font-size:15px;
        font-weight:800;
        color:var(--blue-dark);
        margin-bottom:8px;
        position:relative;
    }

    .competency-title:after{
        content:"";
        display:block;
        width:100%;
        height:2px;
        background:#d9e0ea;
        margin-top:8px;
    }

    .competency-item{
        display:flex;
        align-items:center;
        gap:9px;
        padding:7px 0;
        font-size:13px;
        color:#314b69;
        font-weight:600;
        border-bottom:1px solid #edf1f5;
    }

    .competency-item:last-child{
        border-bottom:none;
    }

    .competency-item i{
        color:#37a455;
        font-size:18px;
        flex-shrink:0;
    }

    .summary-card{
        padding:0 14px 14px;
    }

    .summary-inner{
        margin:10px 0 0;
        border:1px solid #d7dee7;
        border-radius:6px;
        background:#fff;
        padding:14px;
        display:grid;
        grid-template-columns:220px minmax(0, 1fr);
        gap:18px;
        align-items:center;
    }

    .pie-box{
        display:flex;
        justify-content:center;
        align-items:center;
    }

    .pie-chart{
        width:170px;
        height:170px;
        border-radius:50%;
        box-shadow:inset 0 0 0 1px rgba(255,255,255,0.2), 0 4px 12px rgba(0,0,0,0.08);
    }

    .legend-list{
        display:flex;
        flex-direction:column;
        gap:12px;
        min-width:0;
    }

    .legend-item{
        display:flex;
        align-items:center;
        gap:10px;
        font-size:14px;
        font-weight:700;
        color:#35516f;
        overflow-wrap:break-word;
        word-break:break-word;
    }

    .legend-color{
        width:16px;
        height:16px;
        border-radius:2px;
        flex-shrink:0;
    }

    .legend-item span{
        min-width:0;
    }

    .legend-item span strong{
        font-size:15px;
        color:#243f69;
    }

    .activities-card{
        padding:0 14px 14px;
    }

    .activities-list{
        margin-top:6px;
    }

    .activity-row{
        display:flex;
        align-items:flex-start;
        gap:10px;
        padding:12px 0;
        border-bottom:1px solid #e5ebf2;
        color:#2f4c70;
        font-size:13px;
        font-weight:700;
        overflow-wrap:break-word;
        word-break:break-word;
    }

    .activity-row:last-child{
        border-bottom:none;
        padding-bottom:8px;
    }

    .activity-row i{
        color:#38a34f;
        font-size:22px;
        flex-shrink:0;
        margin-top:1px;
    }

    .activity-row div{
        flex:1;
        min-width:0;
    }

    .activity-row small{
        display:block;
        color:#5d7493;
        font-size:12px;
        font-weight:600;
        margin-top:2px;
    }

    .activity-progress{
        margin-top:10px;
        width:100%;
        height:16px;
        border-radius:4px;
        overflow:hidden;
        background:#e7edf4;
        border:1px solid #d7dee7;
        display:flex;
    }

    .activity-progress .b1{ background:#2f67b1; }
    .activity-progress .b2{ background:#7bc24b; }

    .overview-panel{
        margin-top:18px;
        overflow:hidden;
    }

    .overview-table{
        width:100%;
        border-collapse:collapse;
    }

    .overview-table th{
        background:linear-gradient(180deg, #f4f6f9 0%, #e3e8ef 100%);
        color:#35516f;
        font-size:13px;
        font-weight:800;
        padding:9px 10px;
        border:1px solid #d6dde6;
        text-align:left;
    }

    .overview-table td{
        padding:9px 10px;
        border:1px solid #dfe5ed;
        font-size:13px;
        color:#314b69;
        font-weight:600;
        overflow-wrap:break-word;
        word-break:break-word;
    }

    .overview-table td.points-cell{
        font-size:15px;
        font-weight:800;
        color:#243f69;
        white-space:nowrap;
    }

    .overview-table td.points-cell small{
        font-size:12px;
        font-weight:700;
        color:#4d6482;
    }

    .bottom-benefits{
        margin-top:16px;
        display:grid;
        grid-template-columns:1fr 90px 1fr;
        border:1px solid var(--border);
        border-radius:8px;
        overflow:hidden;
        box-shadow:var(--shadow);
        background:#fff;
    }

    .benefit-box{
        background:#fff;
        min-width:0;
    }

    .benefit-head{
        color:#fff;
        font-weight:800;
        font-size:13px;
        padding:9px 12px;
    }

    .benefit-head.orange{
        background:linear-gradient(90deg, #dd8420 0%, #f2b45b 65%, #f7f1e8 100%);
    }

    .benefit-head.green{
        background:linear-gradient(90deg, #eff8f0 0%, #6ab86d 30%, #30944f 100%);
        text-align:center;
    }

    .benefit-body{
        padding:12px;
        font-size:13px;
        color:#34516f;
        font-weight:700;
        min-height:60px;
        display:flex;
        align-items:center;
        justify-content:center;
        text-align:center;
        overflow-wrap:break-word;
        word-break:break-word;
    }

    .center-growth{
        display:flex;
        flex-direction:column;
        align-items:center;
        justify-content:center;
        gap:4px;
        background:#fff;
    }

    .center-growth .handshake{
        font-size:36px;
        line-height:1;
    }

    .center-growth .growth-text{
        font-size:12px;
        font-weight:800;
        color:#2c4f7b;
        text-align:center;
        padding:0 4px;
    }

    .mobile-toggle{
        display:none;
    }

    @media (max-width: 1400px){
        .grid-main{
            grid-template-columns:210px minmax(0, 1fr) 230px;
            gap:14px;
        }

        .summary-inner{
            grid-template-columns:190px minmax(0, 1fr);
            gap:14px;
        }

        .pie-chart{
            width:150px;
            height:150px;
        }
    }

    @media (max-width: 1199px){
        .grid-main{
            grid-template-columns:1fr;
        }

        .summary-inner{
            grid-template-columns:1fr;
        }

        .top-flow{
            grid-template-columns:1fr;
        }

        .flow-arrow{
            transform:rotate(90deg);
            font-size:26px;
        }

        .bottom-benefits{
            grid-template-columns:1fr;
        }
    }

    @media (max-width: 991px){
        .sidebar{
            left:-260px;
            transition:.25s ease;
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

        .dashboard-wrap{
            padding:12px;
        }

        .system-title h1{
            font-size:22px;
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

        .topbar-left{
            display:flex;
            align-items:center;
            gap:8px;
        }

        .user-chip .meta{
            display:none;
        }
    }

    @media (max-width: 767px){
        .summary-inner{
            padding:12px;
        }

        .pie-chart{
            width:140px;
            height:140px;
        }

        .profile-name{
            font-size:18px;
        }

        .overview-table{
            min-width:760px;
        }

        .table-wrap{
            overflow-x:auto;
        }
    }
</style>
</head>
<body>

<div class="layout">

    <!-- SIDEBAR -->
<div class="sidebar" id="sidebar">
    <div class="brand-box">
        <h4><i class="fa-solid fa-graduation-cap me-2"></i> ECA </h4>
        <small>Contractor Learning Portal</small>
    </div>

    <div class="menu-title">Main Menu</div>

    <a href="dashboard.php">
        <div class="left"><i class="fa fa-house"></i> Dashboard</div>
    </a>

    <a href="my_courses.php">
        <div class="left"><i class="fa fa-book-open"></i> My Courses</div>
    </a>

    <a href="certificates.php" class="active">
        <div class="left"><i class="fa fa-certificate"></i> My Certificates</div>
        <span class="menu-badge"><?=$generated_count?></span>
    </a>

    <a href="resources.php">
        <div class="left"><i class="fa fa-folder-open"></i> Resource Library</div>
    </a>
     <a href="events.php">
        <div class="left"><i class="fa fa-folder-open"></i>Events</div>
    </a>
    <a href="points_tracker.php">
        <div class="left"><i class="fa fa-folder-open"></i>CPD Tracker</div>
    </a>
    
       <a href="feedback.php">
        <div class="left"><i class="fa fa-folder-open"></i>Feedback</div>
    </a>
    <a href="logout.php">
        <div class="left"><i class="fa fa-right-from-bracket"></i> Logout</div>
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
                    <h5>Dashboard Overview</h5>
                    <small>Welcome back, <?= htmlspecialchars($user_name) ?></small>
                </div>
            </div>

            <div class="user-chip">
                <img src="https://ui-avatars.com/api/?name=<?= urlencode($user_name) ?>&background=29486f&color=ffffff&rounded=true&bold=true&size=128" alt="User">
                <div class="meta">
                    <div style="font-weight:800; color:#29486f;"><?= htmlspecialchars($user_name) ?></div>
                    <div style="font-size:13px; color:#6c7f99;"><?= htmlspecialchars($user_email) ?></div>
                </div>
            </div>
        </div>

        <div class="dashboard-wrap">

            <!-- TITLE -->
            <div class="system-title">
                <h1>CPD Point System</h1>
            </div>

            <!-- TOP FLOW -->
            <div class="top-flow">
                <div class="flow-card">
                    <div class="flow-head blue">
                        <i class="fa-solid fa-briefcase"></i> Member Company
                    </div>
                    <div class="flow-body">
                        <div class="big"><?= htmlspecialchars($company_name) ?></div>
                        <div class="small"><?= htmlspecialchars($discipline) ?></div>
                    </div>
                </div>

                <div class="flow-arrow">→</div>

                <div class="flow-card">
                    <div class="flow-head gray">
                        <i class="fa-solid fa-users"></i> Association
                    </div>
                    <div class="flow-body">
                        <div class="big">Eswatini Contractors Association</div>
                        <div class="small">Financial Year: <?= htmlspecialchars($financial_year) ?></div>
                    </div>
                </div>

                <div class="flow-arrow">→</div>

                <div class="flow-card">
                    <div class="flow-head green">
                        <i class="fa-solid fa-arrow-trend-up"></i> Focus Area
                    </div>
                    <div class="flow-body">
                        <div class="big" style="font-size:16px;"><?= htmlspecialchars($focus_area) ?></div>
                    </div>
                </div>
            </div>

            <hr class="separator">

            <!-- MAIN GRID -->
            <div class="grid-main">

                <!-- LEFT COLUMN -->
                <div>
                    <div class="panel profile-card">
                        <div class="profile-photo">
                            <img src="https://ui-avatars.com/api/?name=<?= urlencode($user_name) ?>&background=e9edf3&color=29486f&size=500&rounded=false&bold=true" alt="Profile Photo">
                        </div>

                        <div class="profile-name"><?= htmlspecialchars($user_name) ?></div>
                        <div class="profile-role"><?= htmlspecialchars($position) ?></div>
                        <div class="profile-company"><?= htmlspecialchars($company_name) ?></div>

              
                    </div>

                    <div class="panel competency-card">
                        <div class="competency-title">Core Competencies</div>

                        <div class="competency-item"><i class="fa-solid fa-check"></i> <?= htmlspecialchars($focus_area3) ?></div>
                        
                    </div>
                </div>

                <!-- CENTER COLUMN -->
                <div>
                    <div class="panel">
                        <div class="panel-header">CPD Summary</div>
                        <div class="summary-card">
                            <div class="summary-inner">
                                <div class="pie-box">
                                    <?php
                                    $c1 = max(0, (float)$pie_values[0]);
                                    $c2 = max(0, (float)$pie_values[1]);
                                    $c3 = max(0, (float)$pie_values[2]);
                                    $c4 = max(0, (float)$pie_values[3]);

                                    $s1 = $c1;
                                    $s2 = $c1 + $c2;
                                    $s3 = $c1 + $c2 + $c3;
                                    $s4 = $c1 + $c2 + $c3 + $c4;
                                    ?>
                                    <div class="pie-chart"
                                        style="background:
                                            conic-gradient(
                                                #ef9422 0% <?= $s1 ?>%,
                                                #2f67b1 <?= $s1 ?>% <?= $s2 ?>%,
                                                #2f974d <?= $s2 ?>% <?= $s3 ?>%,
                                                #f0b323 <?= $s3 ?>% <?= $s4 ?>%
                                            );">
                                    </div>
                                </div>

                                <div class="legend-list">
                                    <div class="legend-item">
                                        <div class="legend-color" style="background:#ef9422;"></div>
                                        <span><?= htmlspecialchars($pie_labels[0]) ?> </span>
                                    </div>
                                    <div class="legend-item">
                                        <div class="legend-color" style="background:#2f67b1;"></div>
                                        <span><?= htmlspecialchars($pie_labels[1]) ?></span>
                                    </div>
                                    <div class="legend-item">
                                        <div class="legend-color" style="background:#2f974d;"></div>
                                        <span><?= htmlspecialchars($pie_labels[2]) ?> </span>
                                    </div>
                                    <div class="legend-item">
                                        <div class="legend-color" style="background:#f0b323;"></div>
                                        <span><?= htmlspecialchars($pie_labels[3]) ?> </span>
                                    </div>

                                    <div style="margin-top:10px; padding-top:12px; border-top:1px solid #e1e7ef;">
                                        <div style="font-size:18px; font-weight:800; color:#234874; margin-bottom:4px;">
                                            <?= number_format($points, 0) ?> Points Earned
                                        </div>
                                        <div style="font-size:15px; color:#607792; font-weight:700;">
                                            <?= number_format($remaining_points, 0) ?> Points Remaining · <?= $progress_percent ?>% Complete
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="panel overview-panel">
                        <div class="panel-header">Courses Overview</div>
                        <div class="table-wrap">
                            <table class="overview-table">
                                <thead>
                                    <tr>
                                        <th style="width:20%;">Name</th>
                                        <th style="width:22%;">Company</th>
                                        <th style="width:17%;">Position</th>
                                        <th style="width:14%;">Points</th>
                                        <th>Courses Attended</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($employees as $emp): ?>
                                        <tr>
                                            <td><?= htmlspecialchars($emp['full_name'] ?? '-') ?></td>
                                            <td><?= htmlspecialchars($emp['company_name'] ?? '-') ?></td>
                                            <td><?= htmlspecialchars($emp['position'] ?? '-') ?></td>
                                            <td class="points-cell">
                                                <?= number_format((float)($emp['employee_points'] ?? 0), 0) ?> <small>Points</small>
                                            </td>
                                            <td><?= htmlspecialchars($emp['courses_attended'] ?? '-') ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <div class="bottom-benefits">
                        <div class="benefit-box">
                            <div class="benefit-head orange">Member Company Benefits</div>
                            <div class="benefit-body">
                                Training Support &amp; Industry Networking
                            </div>
                        </div>

                        <div class="center-growth">
                            <div class="handshake">🤝</div>
                            <div class="growth-text">Collaborative Growth</div>
                        </div>

                        <div class="benefit-box">
                            <div class="benefit-head green">Association Focus</div>
                            <div class="benefit-body">
                                Advancing Construction Operations &amp; Contract Training
                            </div>
                        </div>
                    </div>
                </div>

                <!-- RIGHT COLUMN -->
                <div>
                    <div class="panel">
                        <div class="panel-header">CPD Activities</div>
                        <div class="activities-card">
                            <div class="activities-list">
                                <?php
                                if (!empty($activities)) {
                                    $displayActivities = array_slice($activities, 0, 3);
                                    foreach ($displayActivities as $act):
                                ?>
                                    <div class="activity-row">
                                        <i class="fa-solid fa-check"></i>
                                        <div>
                                            <?= htmlspecialchars($act['title']) ?>
                                            <small> – <?= htmlspecialchars($act['date']) ?></small>
                                        </div>
                                    </div>
                                <?php
                                    endforeach;
                                } else {
                                ?>
                                    <div class="activity-row">
                                        <i class="fa-solid fa-check"></i>
                                        <div>Contract Training Workshop <small>– April <?= date('Y') ?></small></div>
                                    </div>
                                    <div class="activity-row">
                                        <i class="fa-solid fa-check"></i>
                                        <div>Safety Management Course <small>– June <?= date('Y') ?></small></div>
                                    </div>
                                    <div class="activity-row">
                                        <i class="fa-solid fa-check"></i>
                                        <div>Quality Management Seminar <small>– July <?= date('Y') ?></small></div>
                                    </div>
                                <?php } ?>

                                <div class="activity-progress">
                                    <div class="b1" style="width:<?= max(18, min(78, $progress_percent)) ?>%;"></div>
                                    <div class="b2" style="width:<?= max(10, min(35, 100 - $progress_percent)) ?>%;"></div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="panel" style="margin-top:18px; padding:16px;">
                        <div style="font-size:18px; font-weight:800; color:#29486f; margin-bottom:10px;">Quick Summary</div>

                        <div style="display:grid; gap:12px;">
                            <div style="border:1px solid #dbe2eb; border-radius:6px; padding:14px;">
                                <div style="font-size:13px; color:#6c7f99; font-weight:700;">Current Course</div>
                                <div style="font-size:20px; font-weight:800; color:#234874;"><?= htmlspecialchars($current_course) ?></div>
                            </div>

                            <div style="border:1px solid #dbe2eb; border-radius:6px; padding:14px;">
                                <div style="font-size:13px; color:#6c7f99; font-weight:700;">Latest Certificate</div>
                                <div style="font-size:20px; font-weight:800; color:#234874;"><?= htmlspecialchars($latest_cert) ?></div>
                            </div>

                            <div style="border:1px solid #dbe2eb; border-radius:6px; padding:14px;">
                                <div style="font-size:13px; color:#6c7f99; font-weight:700;">CPD Progress</div>
                                <div style="font-size:20px; font-weight:800; color:#234874;"><?= number_format($points, 0) ?> / <?= (int)$target_points ?> Points</div>
                                <div style="margin-top:10px; height:14px; background:#edf2f7; border-radius:3px; overflow:hidden; border:1px solid #d9e0ea;">
                                    <div style="height:100%; width:<?= $progress_percent ?>%; background:linear-gradient(90deg, #2f67b1 0%, #6ab86d 100%);"></div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

            </div>
        </div>
    </main>
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
</script>

</body>
</html>