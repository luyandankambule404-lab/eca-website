<?php
require_once "../auth1.php"; // change to ../auth.php if your system uses auth.php
require_role('CONTRACTOR');
require_once "../config.php";

$user_id    = (int)($_SESSION['user_id'] ?? 0);
$user_email = trim((string)($_SESSION['email'] ?? ''));
$user_name  = trim((string)($_SESSION['full_name'] ?? ''));

if ($user_name === '') {
    $user_name = ($user_email && strpos($user_email, '@') !== false)
        ? explode('@', $user_email)[0]
        : 'User';
}

/* =========================
   HELPERS
========================= */
if (!function_exists('h')) {
    function h($value): string
    {
        return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
    }
}

if (!function_exists('table_exists')) {
    function table_exists(mysqli $conn, string $table): bool
    {
        $tableEsc = $conn->real_escape_string($table);
        $res = $conn->query("SHOW TABLES LIKE '{$tableEsc}'");
        return $res && $res->num_rows > 0;
    }
}

if (!function_exists('column_exists')) {
    function column_exists(mysqli $conn, string $table, string $column): bool
    {
        $tableSafe = str_replace('`', '``', $table);
        $columnEsc = $conn->real_escape_string($column);
        $res = $conn->query("SHOW COLUMNS FROM `{$tableSafe}` LIKE '{$columnEsc}'");
        return $res && $res->num_rows > 0;
    }
}

if (!function_exists('course_metrics')) {
    function course_metrics(array $row): array
    {
        $duration = (int)($row['duration'] ?? 0);
        if ($duration <= 0) {
            $duration = 4;
        }

        $present_days = (int)($row['present_days'] ?? 0);
        if ($present_days < 0) {
            $present_days = 0;
        }
        if ($present_days > $duration) {
            $present_days = $duration;
        }

        $activity_submitted = (int)($row['activity_submitted'] ?? 0);
        $certificate_no = trim((string)($row['certificate_number'] ?? ''));
        $db_status = strtolower(trim((string)($row['training_status'] ?? '')));

        // Attendance = 80%, Activity = 20%
        $attendance_progress = (int) round(($present_days / $duration) * 80);
        $activity_progress   = $activity_submitted ? 20 : 0;
        $progress            = $attendance_progress + $activity_progress;

        // Force completed if DB already says completed or certificate exists
        if ($db_status === 'completed' || $certificate_no !== '') {
            $progress = 100;
            $activity_submitted = 1;
            $activity_progress = 20;
        }

        $progress = max(0, min(100, $progress));

        if ($progress >= 100) {
            $status = 'completed';
            $badge_class = 'badge-complete';
            $badge_text = 'Completed';
        } elseif ($progress > 0 || $db_status === 'inprogress') {
            $status = 'inprogress';
            $badge_class = 'badge-progress';
            $badge_text = 'In Progress';
        } else {
            $status = 'pending';
            $badge_class = 'badge-default';
            $badge_text = 'Pending';
        }

        return [
            'duration'             => $duration,
            'present_days'         => $present_days,
            'activity_submitted'   => $activity_submitted,
            'attendance_progress'  => $attendance_progress,
            'activity_progress'    => $activity_progress,
            'progress'             => $progress,
            'status'               => $status,
            'badge_class'          => $badge_class,
            'badge_text'           => $badge_text,
            'certificate_no'       => $certificate_no,
        ];
    }
}

/* =========================
   SCHEMA CHECKS
========================= */
$hasAttendanceTable = table_exists($conn, 'course_attendance');
$hasActivityColumn  = table_exists($conn, 'cpd_applications') && column_exists($conn, 'cpd_applications', 'activity_submitted');

/* =========================
   COUNTS
========================= */
$generated_count = 0;
$upcoming_count  = 0;

if ($user_email !== '') {
    $certSql = "
        SELECT COUNT(*) AS total
        FROM cpd_applications
        WHERE email = ?
          AND status = 'Approved'
          AND certificate_number IS NOT NULL
          AND TRIM(certificate_number) <> ''
    ";
    if ($stmtCert = $conn->prepare($certSql)) {
        $stmtCert->bind_param("s", $user_email);
        $stmtCert->execute();
        $resCert = $stmtCert->get_result();
        if ($resCert && $rowCert = $resCert->fetch_assoc()) {
            $generated_count = (int)$rowCert['total'];
        }
        $stmtCert->close();
    }
}

$upcomingSql = "SELECT COUNT(*) AS total_upcoming FROM courses WHERE start_date > NOW()";
$upcomingRes = $conn->query($upcomingSql);
if ($upcomingRes && $urow = $upcomingRes->fetch_assoc()) {
    $upcoming_count = (int)$urow['total_upcoming'];
}

/* =========================
   FETCH MY APPROVED COURSES
========================= */
$attendanceSelect = $hasAttendanceTable ? "COALESCE(att.present_days, 0) AS present_days" : "0 AS present_days";
$attendanceJoin   = $hasAttendanceTable ? "
    LEFT JOIN (
        SELECT application_id, COUNT(*) AS present_days
        FROM course_attendance
        WHERE status = 'PRESENT'
        GROUP BY application_id
    ) att ON att.application_id = a.id
" : "";

$activitySelect = $hasActivityColumn ? "COALESCE(a.activity_submitted, 0) AS activity_submitted" : "0 AS activity_submitted";

$course_rows = [];

if ($user_email !== '') {
    $sql = "
        SELECT
            a.id,
            a.course_id,
            a.company_name,
            a.membership_number,
            a.discipline,
            a.full_name,
            a.email,
            a.phone,
            a.id_number,
            a.gender,
            a.position,
            a.learning_objectives,
            a.training_status,
            a.certificate_number,
            {$activitySelect},
            c.title,
            c.description,
            c.banner,
            c.start_date,
            c.end_date,
            c.venue,
            c.points,
            c.duration,
            {$attendanceSelect}
        FROM cpd_applications a
        LEFT JOIN courses c ON c.id = a.course_id
        {$attendanceJoin}
        WHERE a.email = ?
          AND a.status = 'Approved'
        ORDER BY a.id DESC
    ";

    if ($stmt = $conn->prepare($sql)) {
        $stmt->bind_param("s", $user_email);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result) {
            while ($row = $result->fetch_assoc()) {
                $row['_metrics'] = course_metrics($row);
                $course_rows[] = $row;
            }
        }

        $stmt->close();
    }
}

/* =========================
   STATUS COUNTS
========================= */
$total_courses    = count($course_rows);
$inprogress_count = 0;
$completed_count  = 0;
$pending_count    = 0;

foreach ($course_rows as $row) {
    $status = $row['_metrics']['status'] ?? 'pending';
    if ($status === 'completed') {
        $completed_count++;
    } elseif ($status === 'inprogress') {
        $inprogress_count++;
    } else {
        $pending_count++;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Courses</title>

    <link href="https://fonts.googleapis.com/css2?family=Barlow:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
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
            --slate:#5b6d86;
            --slate-soft:#eef2f7;
            --text:#2b4468;
            --muted:#6c7f99;
            --border:#cfd7e3;
            --page-bg:#eef2f7;
            --shadow:0 3px 10px rgba(30, 53, 88, 0.10);
            --shadow-soft:0 6px 18px rgba(30, 53, 88, 0.08);
            --radius:14px;
        }

        *{ box-sizing:border-box; }

        html, body{
            margin:0;
            padding:0;
            overflow-x:hidden;
        }

        body{
            background:linear-gradient(to bottom, #f5f7fb 0%, #edf1f7 100%);
            font-family:'Barlow', sans-serif;
            color:var(--text);
        }

        .layout{
            display:flex;
            min-height:100vh;
        }

        /* SIDEBAR */
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

        /* MAIN */
        .main{
            margin-left:240px;
            width:calc(100% - 240px);
            padding:0;
        }

        /* TOPBAR */
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

        /* STATS */
        .stats-grid{
            display:grid;
            grid-template-columns:repeat(4, 1fr);
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
            height:100%;
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

        .stat-icon.slate{
            background:var(--slate-soft);
            color:var(--slate);
        }

        /* PANEL */
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
            margin-bottom:8px;
            flex-wrap:wrap;
        }

        .card-title-row .small-note{
            font-size:13px;
            color:var(--muted);
            font-weight:700;
        }

        /* COMPACT COURSE CARDS */
        .course-grid{
            display:grid;
            grid-template-columns:repeat(3, minmax(0,1fr));
            gap:16px;
            margin-top:14px;
        }

        .course-card{
            background:#fff;
            border:1px solid #d8e0ea;
            border-radius:14px;
            overflow:hidden;
            transition:.25s ease;
            box-shadow:0 4px 14px rgba(30, 53, 88, 0.06);
            height:100%;
            display:flex;
            flex-direction:column;
        }

        .course-card:hover{
            border-color:#c9d7e6;
            box-shadow:0 10px 22px rgba(30, 53, 88, 0.12);
            transform:translateY(-2px);
        }

        .course-banner{
            height:165px;
            background:#e9eef6 center/cover no-repeat;
            position:relative;
        }

        .course-banner::after{
            content:"";
            position:absolute;
            inset:0;
            background:linear-gradient(180deg, rgba(20,31,51,.08), rgba(20,31,51,.30));
        }

        .course-badge{
            position:absolute;
            top:12px;
            right:12px;
            z-index:2;
            padding:7px 11px;
            border-radius:999px;
            font-size:10px;
            font-weight:800;
            color:#fff;
            letter-spacing:.3px;
            box-shadow:0 3px 10px rgba(0,0,0,.15);
        }

        .badge-progress{ background:linear-gradient(180deg, #f0b323 0%, #d49114 100%); }
        .badge-complete{ background:linear-gradient(180deg, #41ab63 0%, #2d8b4f 100%); }
        .badge-default{ background:linear-gradient(180deg, #919aa7 0%, #6d7682 100%); }

        .course-body{
            padding:14px 14px 16px;
            display:flex;
            flex-direction:column;
            flex:1;
        }

        .course-title{
            font-size:15px;
            font-weight:800;
            color:#b11d4a;
            line-height:1.4;
            margin-bottom:8px;
            display:-webkit-box;
            -webkit-line-clamp:2;
            -webkit-box-orient:vertical;
            overflow:hidden;
            min-height:42px;
        }

        .course-status-line{
            font-size:13px;
            color:#6c7f99;
            font-weight:600;
            margin-bottom:10px;
        }

        .course-mini-meta{
            font-size:12px;
            color:#7a8798;
            margin-top:auto;
            display:flex;
            align-items:center;
            justify-content:space-between;
            gap:8px;
            flex-wrap:wrap;
        }

        .course-mini-chip{
            display:inline-flex;
            align-items:center;
            gap:6px;
            background:#f4f7fb;
            border:1px solid #dbe4ef;
            color:#4c617d;
            border-radius:999px;
            padding:5px 9px;
            font-size:11px;
            font-weight:700;
            max-width:100%;
        }

        .course-mini-chip i{
            flex-shrink:0;
        }

        .course-footer{
            display:flex;
            align-items:center;
            justify-content:space-between;
            gap:8px;
            margin-top:12px;
            flex-wrap:wrap;
        }

        .course-link{
            text-decoration:none;
            color:var(--blue-dark);
            font-size:12px;
            font-weight:800;
            display:inline-flex;
            align-items:center;
            gap:6px;
        }

        .course-link:hover{
            color:var(--blue);
        }

        .course-percent{
            font-size:12px;
            font-weight:800;
            color:#7a8798;
        }

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

        /* MOBILE */
        .mobile-toggle{
            display:none;
        }

        .bottom-nav{
            display:none;
        }

        @media (max-width: 1199px){
            .course-grid{
                grid-template-columns:repeat(2, minmax(0,1fr));
            }
        }

        @media (max-width: 991px){
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

            .user-chip .meta{
                display:none;
            }

            .stats-grid{
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

        @media (max-width: 767px){
            .course-grid{
                grid-template-columns:1fr;
            }

            .course-banner{
                height:180px;
            }
        }
    </style>
<?php require __DIR__ . '/_hub_css.php'; ?>
</head>
<body class="hub-root">

<div class="layout">

    <div class="sidebar" id="sidebar">
        <div class="brand-box">
            <h4><i class="fa-solid fa-graduation-cap me-2"></i> ECA</h4>
            <small>Contractor Learning Portal</small>
        </div>

        <div class="menu-title">Main Menu</div>

        <a href="dashboard.php">
            <div class="left"><i class="fa fa-house"></i> Dashboard</div>
        </a>

        <a href="my_courses.php" class="active">
            <div class="left"><i class="fa fa-book-open"></i> My Courses</div>
        </a>

        <a href="certificates.php">
            <div class="left"><i class="fa fa-certificate"></i> My Certificates</div>
            <span class="menu-badge"><?= (int)$generated_count ?></span>
        </a>

        <a href="resources.php">
            <div class="left"><i class="fa fa-folder-open"></i> Resource Library</div>
        </a>

        <a href="events.php">
            <div class="left"><i class="fa fa-calendar-days"></i> Events</div>
        </a>

        <a href="points_tracker.php">
            <div class="left"><i class="fa fa-star"></i> CPD Tracker</div>
        </a>

        <a href="feedback.php">
            <div class="left"><i class="fa fa-message"></i> Feedback</div>
        </a>

        <a href="/index.php">
            <div class="left"><i class="fa fa-globe"></i> ECA home</div>
        </a>
        <a href="logout.php">
            <div class="left"><i class="fa fa-right-from-bracket"></i> Logout</div>
        </a>
    </div>

    <main class="main">
        <div class="topbar">
            <div class="topbar-left">
                <button class="mobile-toggle" onclick="toggleSidebar()">
                    <i class="fa-solid fa-bars"></i>
                </button>
                <div>
                    <h5>My Courses</h5>
                    <small>Manage all your approved CPD learning courses</small>
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

       

            <div class="panel">
                <div class="panel-header">My Courses</div>
                <div class="panel-body">

                    <div class="card-title-row">
                        <div class="small-note">Short compact course cards with current status</div>
                        <div class="small-note">Total: <?= (int)$total_courses ?></div>
                    </div>

                    <?php if ($total_courses > 0): ?>
                        <div class="course-grid">
                            <?php foreach ($course_rows as $row): ?>
                                <?php
                                    $m = $row['_metrics'];

                                    $banner = !empty($row['banner'])
                                        ? "../" . ltrim((string)$row['banner'], '/')
                                        : "https://images.unsplash.com/photo-1522202176988-66273c2fd55f?q=80&w=1400&auto=format&fit=crop";

                                    $status_line = 'Pending Start';
                                    if ($m['status'] === 'completed') {
                                        $status_line = 'Completed Training';
                                    } elseif ($m['status'] === 'inprogress') {
                                        $status_line = 'Current Training';
                                    }

                                    $short_venue = !empty($row['venue']) ? (string)$row['venue'] : 'Venue TBA';

                                    $primary_link = "#";
                                    $primary_text = "View Course";
                                    $primary_icon = "fa-eye";

                                    if ((int)$row['course_id'] > 0) {
                                        $primary_link = "activity.php?course_id=" . (int)$row['course_id'] . "&application_id=" . (int)$row['id'];
                                        $primary_text = $m['activity_submitted'] ? "Review Activity" : "Open Course";
                                        $primary_icon = $m['activity_submitted'] ? "fa-pen-to-square" : "fa-arrow-right";
                                    }

                                    if ($m['status'] === 'completed' && $m['certificate_no'] !== '') {
                                        $primary_link = "certificates.php";
                                        $primary_text = "View Certificate";
                                        $primary_icon = "fa-certificate";
                                    }
                                ?>
                                <div class="course-card">
                                    <div class="course-banner" style="background-image:url('<?= h($banner) ?>');">
                                        <div class="course-badge <?= h($m['badge_class']) ?>"><?= h($m['badge_text']) ?></div>
                                    </div>

                                    <div class="course-body">
                                        <div class="course-title"><?= h($row['title'] ?? 'Untitled Course') ?></div>

                                        <div class="course-status-line">
                                            <?= h($status_line) ?>
                                        </div>

                                        <div class="course-mini-meta">
                                            <span class="course-mini-chip">
                                                <i class="fa fa-location-dot"></i>
                                                <?= h($short_venue) ?>
                                            </span>

                                            <?php if (!empty($row['points'])): ?>
                                                <span class="course-mini-chip">
                                                    <i class="fa fa-award"></i>
                                                    <?= h($row['points']) ?> CPD
                                                </span>
                                            <?php endif; ?>
                                        </div>

                                        <div class="course-footer">
                                            <a href="<?= h($primary_link) ?>" class="course-link">
                                                <i class="fa <?= h($primary_icon) ?>"></i>
                                                <?= h($primary_text) ?>
                                            </a>
                                            <div class="course-percent"><?= (int)$m['progress'] ?>%</div>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php else: ?>
                        <div class="empty-box">
                            <i class="fa fa-book-open"></i>
                            <h6>No approved courses found</h6>
                            <p>You do not yet have any approved CPD courses assigned to your account.</p>
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

    <a href="my_courses.php" class="active">
        <i class="fa fa-book"></i>
        Courses
    </a>

    <a href="certificates.php">
        <i class="fa fa-certificate"></i>
        Certs
    </a>

    <a href="points_tracker.php">
        <i class="fa fa-star"></i>
        Points
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
</script>

</body>
</html>