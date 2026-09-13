<?php
require_once "../auth.php";
require_role('CONTRACTOR');
require_once "../config.php";

$user_id    = $_SESSION['user_id'] ?? 0;
$user_email = $_SESSION['email'] ?? '';
$user_name  = $_SESSION['full_name'] ?? explode('@', $user_email)[0];

/* ====================================================
TOTAL POINTS
==================================================== */
$total_points_q = $conn->query("
    SELECT COALESCE(SUM(points),0) AS total_points
    FROM cpd_points_ledger
    WHERE user_id = '$user_id'
");

$total_points = 0;
if($total_points_q && $total_points_q->num_rows){
    $total_points = (float)($total_points_q->fetch_assoc()['total_points'] ?? 0);
}

/* ====================================================
POINTS HISTORY
==================================================== */
$ledger = $conn->query("
    SELECT 
        l.id,
        l.user_id,
        l.course_id,
        l.points,
        l.reason,
        l.created_at,
        l.issued_at,
        l.description,
        c.title,
        c.venue,
        c.start_date,
        c.end_date,
        c.points AS course_points,
        a.training_status,
        a.certificate_number
    FROM cpd_points_ledger l
    LEFT JOIN courses c 
        ON l.course_id = c.id
    LEFT JOIN cpd_applications a 
        ON a.course_id = l.course_id 
       AND a.email = '$user_email'
       AND a.status = 'Approved'
    WHERE l.user_id = '$user_id'
    ORDER BY l.issued_at DESC, l.id DESC
");

$total_entries = $ledger ? $ledger->num_rows : 0;

/* ====================================================
COMPLETED COURSES COUNT
==================================================== */
$completed_q = $conn->query("
    SELECT COUNT(*) AS total_completed
    FROM cpd_applications
    WHERE email = '$user_email'
      AND status = 'Approved'
      AND training_status = 'Completed'
");

$total_completed = 0;
if($completed_q && $completed_q->num_rows){
    $total_completed = (int)($completed_q->fetch_assoc()['total_completed'] ?? 0);
}

/* ====================================================
POINTS BY COURSE
==================================================== */
$course_points = $conn->query("
    SELECT 
        l.course_id,
        c.title,
        COALESCE(SUM(l.points),0) AS total_course_points
    FROM cpd_points_ledger l
    LEFT JOIN courses c ON l.course_id = c.id
    WHERE l.user_id = '$user_id'
    GROUP BY l.course_id, c.title
    ORDER BY total_course_points DESC
");

$max_course_points = 0;
if($course_points && $course_points->num_rows > 0){
    while($row = $course_points->fetch_assoc()){
        if($row['total_course_points'] > $max_course_points){
            $max_course_points = $row['total_course_points'];
        }
    }
    $course_points->data_seek(0);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>CPD Points Tracker</title>

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
    --text:#2b4468;
    --muted:#6c7f99;
    --border:#cfd7e3;
    --card-bg:#ffffff;
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
    margin-bottom:14px;
    flex-wrap:wrap;
}

.card-title-row h5,
.card-title-row h6{
    margin:0;
    font-size:16px;
    font-weight:800;
    color:var(--blue-dark);
}

.card-title-row .small-note{
    font-size:13px;
    color:var(--muted);
    font-weight:700;
}

/* TABLE */
.table-wrap{
    overflow:auto;
}

.table-pro{
    width:100%;
    border-collapse:separate;
    border-spacing:0;
    min-width:900px;
}

.table-pro thead th{
    font-size:12px;
    text-transform:uppercase;
    letter-spacing:.5px;
    color:var(--muted);
    padding:14px 14px;
    font-weight:800;
    border-bottom:1px solid var(--border);
    background:#f8fbff;
}

.table-pro tbody td{
    padding:14px;
    border-bottom:1px solid #edf2f7;
    vertical-align:middle;
    font-size:14px;
    background:#fff;
    color:var(--text);
}

.table-pro tbody tr:hover td{
    background:#fcfdff;
}

.badge-pro{
    display:inline-flex;
    align-items:center;
    gap:6px;
    padding:7px 12px;
    border-radius:999px;
    font-size:11px;
    font-weight:800;
}

.badge-success{
    background:#eaf7ef;
    color:#1f7a46;
}

.badge-info{
    background:#eef4ff;
    color:#1d4ed8;
}

.badge-muted{
    background:#f1f5f9;
    color:#475569;
}

/* SIDE CARDS */
.progress-item{
    margin-bottom:16px;
}

.progress-head{
    display:flex;
    justify-content:space-between;
    gap:12px;
    margin-bottom:8px;
}

.progress-head strong{
    font-size:14px;
    color:var(--text);
}

.progress-head span{
    font-size:13px;
    color:var(--muted);
    font-weight:700;
}

.progress{
    height:10px;
    border-radius:999px;
    background:#e8eef5;
    overflow:hidden;
    border:1px solid #dde5ee;
}

.progress-bar{
    border-radius:999px;
    background:linear-gradient(90deg, #2f67b1 0%, #6ab86d 100%);
}

.snapshot-row{
    display:flex;
    align-items:center;
    justify-content:space-between;
    gap:12px;
    padding:14px 0;
    border-bottom:1px solid #edf2f7;
}

.snapshot-row:last-child{
    border-bottom:none;
    padding-bottom:0;
}

.snapshot-meta span{
    display:block;
    font-size:12px;
    color:var(--muted);
    margin-bottom:4px;
    font-weight:700;
    text-transform:uppercase;
    letter-spacing:.3px;
}

.snapshot-meta strong{
    display:block;
    font-size:15px;
    color:var(--blue-dark);
    font-weight:800;
}

.snapshot-value{
    font-size:24px;
    font-weight:800;
    color:var(--blue-dark);
}

/* EMPTY */
.empty-box{
    text-align:center;
    padding:28px 16px;
    border:1px dashed #d7e1eb;
    border-radius:14px;
    background:#fbfcfe;
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
}

.empty-box p{
    margin:0;
    color:var(--muted);
    font-size:13px;
}

/* MOBILE */
.mobile-toggle{
    display:none;
}

.bottom-nav{
    display:none;
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


    <main class="main">
        <div class="topbar">
            <div class="topbar-left">
                <button class="mobile-toggle" onclick="toggleSidebar()">
                    <i class="fa-solid fa-bars"></i>
                </button>
                <div>
                    <h5>CPD Points Tracker</h5>
                    <small>Monitor your accumulated CPD points and issued entries</small>
                </div>
            </div>

            <div class="user-chip">
                <img src="https://ui-avatars.com/api/?name=<?= urlencode($user_name) ?>&background=29486f&color=ffffff&rounded=true&bold=true&size=128" alt="User">
                <div class="meta">
                    <div><?= htmlspecialchars($user_name) ?></div>
                    <div><?= htmlspecialchars($user_email) ?></div>
                </div>
            </div>
        </div>

        <div class="page-wrap">

            <div class="system-title">
                <h1>CPD Points Tracker</h1>
            </div>

            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-top">
                        <div>
                            <span class="stat-label">Total CPD Points</span>
                            <div class="stat-value"><?= number_format($total_points,1) ?></div>
                            <div class="stat-sub">All accumulated learning points</div>
                        </div>
                        <div class="stat-icon blue"><i class="fa fa-star"></i></div>
                    </div>
                </div>

                <div class="stat-card">
                    <div class="stat-top">
                        <div>
                            <span class="stat-label">Ledger Entries</span>
                            <div class="stat-value"><?= $total_entries ?></div>
                            <div class="stat-sub">Point records issued to your account</div>
                        </div>
                        <div class="stat-icon gold"><i class="fa fa-list-check"></i></div>
                    </div>
                </div>

                <div class="stat-card">
                    <div class="stat-top">
                        <div>
                            <span class="stat-label">Completed Trainings</span>
                            <div class="stat-value"><?= $total_completed ?></div>
                            <div class="stat-sub">Approved courses marked completed</div>
                        </div>
                        <div class="stat-icon green"><i class="fa fa-award"></i></div>
                    </div>
                </div>
            </div>

            <div class="row g-3">
                <div class="col-lg-8">
                    <div class="panel">
                        <div class="panel-header">Points Ledger History</div>
                        <div class="panel-body">

                            <div class="card-title-row">
                                <div></div>
                                <div class="small-note">Issued CPD point records</div>
                            </div>

                            <?php if($ledger && $total_entries > 0): ?>
                                <div class="table-wrap">
                                    <table class="table-pro">
                                        <thead>
                                            <tr>
                                                <th>#</th>
                                                <th>Course</th>
                                                <th>Reason</th>
                                                <th>Description</th>
                                                <th>Points</th>
                                                <th>Issued</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php $n=1; while($row = $ledger->fetch_assoc()): ?>
                                            <tr>
                                                <td><?= $n++ ?></td>
                                                <td>
                                                    <strong><?= htmlspecialchars($row['title'] ?? 'General CPD Entry') ?></strong>
                                                    <div style="font-size:12px;color:#64748b;margin-top:4px;">
                                                        <i class="fa fa-location-dot"></i>
                                                        <?= htmlspecialchars($row['venue'] ?? 'N/A') ?>
                                                    </div>
                                                </td>
                                                <td>
                                                    <span class="badge-pro badge-info">
                                                        <i class="fa fa-circle-info"></i>
                                                        <?= htmlspecialchars($row['reason'] ?: 'Points Issued') ?>
                                                    </span>
                                                </td>
                                                <td><?= htmlspecialchars($row['description'] ?: 'No description available') ?></td>
                                                <td>
                                                    <span class="badge-pro badge-success">
                                                        <i class="fa fa-plus"></i>
                                                        <?= number_format((float)$row['points'],1) ?> pts
                                                    </span>
                                                </td>
                                                <td>
                                                    <?= !empty($row['issued_at']) ? date('d M Y h:i A', strtotime($row['issued_at'])) : (!empty($row['created_at']) ? date('d M Y h:i A', strtotime($row['created_at'])) : 'N/A') ?>
                                                </td>
                                            </tr>
                                            <?php endwhile; ?>
                                        </tbody>
                                    </table>
                                </div>
                            <?php else: ?>
                                <div class="empty-box">
                                    <i class="fa fa-chart-line"></i>
                                    <h6>No CPD points recorded yet</h6>
                                    <p>Your points history will appear here once points are awarded to your account.</p>
                                </div>
                            <?php endif; ?>

                        </div>
                    </div>
                </div>

                <div class="col-lg-4">
                    <div class="panel">
                        <div class="panel-header">Points by Course</div>
                        <div class="panel-body">
                            <?php if($course_points && $course_points->num_rows > 0): ?>
                                <?php while($cp = $course_points->fetch_assoc()): 
                                    $percent = $max_course_points > 0 ? ($cp['total_course_points'] / $max_course_points) * 100 : 0;
                                ?>
                                <div class="progress-item">
                                    <div class="progress-head">
                                        <strong><?= htmlspecialchars($cp['title'] ?? 'Untitled Course') ?></strong>
                                        <span><?= number_format((float)$cp['total_course_points'],1) ?> pts</span>
                                    </div>
                                    <div class="progress">
                                        <div class="progress-bar" style="width:<?= round($percent,1) ?>%"></div>
                                    </div>
                                </div>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <div class="empty-box">
                                    <i class="fa fa-award"></i>
                                    <h6>No course points yet</h6>
                                    <p>Course-based point distribution will appear here after training completion.</p>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>

                    <div class="panel mt-3">
                        <div class="panel-header">Progress Snapshot</div>
                        <div class="panel-body">
                            <div class="snapshot-row">
                                <div class="snapshot-meta">
                                    <span>Total Points Earned</span>
                                    <strong>Overall earned points</strong>
                                </div>
                                <div class="snapshot-value"><?= number_format($total_points,1) ?></div>
                            </div>

                            <div class="snapshot-row">
                                <div class="snapshot-meta">
                                    <span>Completed Courses</span>
                                    <strong>Finished approved trainings</strong>
                                </div>
                                <div class="snapshot-value"><?= $total_completed ?></div>
                            </div>

                            <div class="snapshot-row">
                                <div class="snapshot-meta">
                                    <span>Ledger Records</span>
                                    <strong>Issued CPD transactions</strong>
                                </div>
                                <div class="snapshot-value"><?= $total_entries ?></div>
                            </div>
                        </div>
                    </div>
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

    <a href="points_tracker.php" class="active">
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