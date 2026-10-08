<?php
require_once "../auth1.php"; // change to ../auth.php if your project uses auth.php
require_role('CONTRACTOR');
require_once "../config.php";

$user_email = $_SESSION['email'] ?? '';
$user_name  = $_SESSION['full_name'] ?? explode('@', $user_email)[0];

/*
====================================================
FETCH EVENTS FROM COURSES TABLE
Only upcoming / current future events
====================================================
*/
$events = $conn->query("
    SELECT *
    FROM courses
    WHERE start_date >= NOW()
    ORDER BY start_date ASC
");

$total_events = $events ? $events->num_rows : 0;

$open_count = 0;
$closed_count = 0;

if ($events && $total_events > 0) {
    while ($r = $events->fetch_assoc()) {
        if (strtoupper(trim($r['status'] ?? '')) === 'OPEN') {
            $open_count++;
        } else {
            $closed_count++;
        }
    }
    $events->data_seek(0);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Training Events</title>

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
    --red:#c73b3b;
    --red-soft:#fff1f1;
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

/* SEARCH */
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
    font-family:'Barlow', sans-serif;
    font-size:14px;
    color:var(--text);
}

.search-box input{
    padding:0 14px 0 42px;
}

.filter-select{
    padding:0 14px;
    min-width:200px;
}

/* EVENT GRID */
.event-grid{
    display:grid;
    grid-template-columns:repeat(2, minmax(0,1fr));
    gap:16px;
}

.event-card{
    background:#fff;
    border:1px solid var(--border);
    border-radius:16px;
    overflow:hidden;
    box-shadow:var(--shadow-soft);
    transition:.25s ease;
    height:100%;
}

.event-card:hover{
    transform:translateY(-4px);
    box-shadow:0 16px 35px rgba(30,53,88,.12);
    border-color:#bed0e5;
}

.event-banner{
    height:190px;
    background:#e9eef6 center/cover no-repeat;
    position:relative;
}

.event-banner:after{
    content:"";
    position:absolute;
    inset:0;
    background:linear-gradient(180deg, rgba(8,28,56,.08), rgba(8,28,56,.45));
}

.event-status{
    position:absolute;
    top:14px;
    right:14px;
    z-index:2;
    padding:7px 12px;
    border-radius:999px;
    font-size:11px;
    font-weight:800;
    color:#fff;
    box-shadow:0 8px 20px rgba(0,0,0,.12);
}

.badge-open{ background:linear-gradient(135deg, #2f9a54, #2b7f48); }
.badge-closed{ background:linear-gradient(135deg, #74849a, #58677c); }

.event-body{
    padding:16px;
}

.event-title{
    font-size:18px;
    font-weight:800;
    color:var(--blue-dark);
    margin-bottom:8px;
    line-height:1.35;
}

.event-desc{
    color:var(--muted);
    font-size:13px;
    line-height:1.6;
    margin-bottom:14px;
    min-height:62px;
}

.info-row{
    display:flex;
    flex-wrap:wrap;
    gap:8px;
    margin-bottom:14px;
}

.info-chip{
    display:inline-flex;
    align-items:center;
    gap:6px;
    background:var(--blue-soft);
    color:var(--blue-dark);
    border-radius:999px;
    padding:7px 11px;
    font-size:11px;
    font-weight:800;
}

.meta-grid{
    display:grid;
    grid-template-columns:1fr 1fr;
    gap:10px;
    margin-bottom:14px;
}

.meta-item{
    background:#f8fbff;
    border:1px solid #e2ebf4;
    border-radius:12px;
    padding:10px;
}

.meta-item span{
    display:block;
    font-size:10px;
    font-weight:800;
    color:var(--muted);
    text-transform:uppercase;
    margin-bottom:4px;
    letter-spacing:.4px;
}

.meta-item strong{
    display:block;
    font-size:12px;
    color:var(--text);
    line-height:1.4;
}

.action-row{
    display:flex;
    gap:10px;
    flex-wrap:wrap;
}

.btn-main,
.btn-lite{
    text-decoration:none;
    border-radius:12px;
    padding:10px 12px;
    font-size:13px;
    font-weight:700;
    display:inline-flex;
    align-items:center;
    justify-content:center;
    gap:7px;
    transition:.2s ease;
}

.btn-main{
    background:linear-gradient(135deg, #345b8e 0%, #29486f 100%);
    color:#fff;
    border:1px solid #29486f;
}

.btn-main:hover{
    background:linear-gradient(135deg, #2f578b 0%, #233f61 100%);
    color:#fff;
}

.btn-lite{
    background:#fff;
    color:var(--blue-dark);
    border:1px solid #c4d2e2;
}

.btn-lite:hover{
    background:#f7fbff;
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

    .event-grid{
        grid-template-columns:1fr;
    }

    .meta-grid{
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

        <a href="my_courses.php">
            <div class="left"><i class="fa fa-book-open"></i> My Courses</div>
        </a>

        <a href="certificates.php">
            <div class="left"><i class="fa fa-certificate"></i> My Certificates</div>
        </a>

        <a href="resources.php">
            <div class="left"><i class="fa fa-folder-open"></i> Resource Library</div>
        </a>

        <a href="events.php" class="active">
            <div class="left"><i class="fa fa-calendar-days"></i> Events</div>
            <span class="menu-badge"><?= (int)$total_events ?></span>
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
                    <h5>Training Events</h5>
                    <small>Browse upcoming CPD courses and learning sessions</small>
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
                <h1>Upcoming Training Events</h1>
            </div>

            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-top">
                        <div>
                            <span class="stat-label">Total Events</span>
                            <div class="stat-value"><?= (int)$total_events ?></div>
                            <div class="stat-sub">Upcoming CPD training sessions</div>
                        </div>
                        <div class="stat-icon blue"><i class="fa fa-calendar-days"></i></div>
                    </div>
                </div>

                <div class="stat-card">
                    <div class="stat-top">
                        <div>
                            <span class="stat-label">Open Events</span>
                            <div class="stat-value"><?= (int)$open_count ?></div>
                            <div class="stat-sub">Available for registration</div>
                        </div>
                        <div class="stat-icon green"><i class="fa fa-door-open"></i></div>
                    </div>
                </div>

                <div class="stat-card">
                    <div class="stat-top">
                        <div>
                            <span class="stat-label">Closed Events</span>
                            <div class="stat-value"><?= (int)$closed_count ?></div>
                            <div class="stat-sub">Currently unavailable sessions</div>
                        </div>
                        <div class="stat-icon gold"><i class="fa fa-lock"></i></div>
                    </div>
                </div>
            </div>

            <div class="panel">
                <div class="panel-header">Browse Events</div>
                <div class="panel-body">

                    <div class="card-title-row">
                        <h5>Available Courses and Sessions</h5>
                        <div class="small-note">Search by title, venue, or event status</div>
                    </div>

                    <div class="search-wrap">
                        <div class="search-box">
                            <i class="fa fa-search"></i>
                            <input type="text" id="eventSearch" placeholder="Search events by title, venue or status...">
                        </div>

                        <select class="filter-select" id="eventFilter">
                            <option value="">All Status</option>
                            <option value="open">Open</option>
                            <option value="closed">Closed</option>
                        </select>
                    </div>

                    <?php if($events && $total_events > 0): ?>
                        <div class="event-grid" id="eventGrid">
                            <?php while($row = $events->fetch_assoc()): ?>
                                <?php
                                    $status = strtoupper(trim($row['status'] ?? ''));
                                    $badge_class = ($status === 'OPEN') ? 'badge-open' : 'badge-closed';
                                    $banner = !empty($row['banner']) ? "../" . ltrim($row['banner'], '/')
                                                                     : "https://images.unsplash.com/photo-1516321318423-f06f85e504b3?q=80&w=1400&auto=format&fit=crop";
                                ?>
                                <div class="event-item"
                                     data-title="<?= htmlspecialchars(strtolower($row['title'] ?? '')) ?>"
                                     data-venue="<?= htmlspecialchars(strtolower($row['venue'] ?? '')) ?>"
                                     data-status="<?= htmlspecialchars(strtolower($status)) ?>">

                                    <div class="event-card">
                                        <div class="event-banner" style="background-image:url('<?= htmlspecialchars($banner) ?>');">
                                            <div class="event-status <?= $badge_class ?>">
                                                <?= $status === 'OPEN' ? 'Open Registration' : 'Closed' ?>
                                            </div>
                                        </div>

                                        <div class="event-body">
                                            <div class="event-title"><?= htmlspecialchars($row['title'] ?? 'Untitled Event') ?></div>

                                            <div class="event-desc">
                                                <?= !empty($row['description'])
                                                    ? nl2br(htmlspecialchars(substr($row['description'], 0, 220)))
                                                    : 'No event description available yet.' ?>
                                            </div>

                                            <div class="info-row">
                                                <span class="info-chip">
                                                    <i class="fa fa-location-dot"></i>
                                                    <?= htmlspecialchars($row['venue'] ?? 'Venue TBA') ?>
                                                </span>

                                                <?php if(!empty($row['points'])): ?>
                                                    <span class="info-chip">
                                                        <i class="fa fa-award"></i>
                                                        <?= htmlspecialchars($row['points']) ?> Points
                                                    </span>
                                                <?php endif; ?>

                                                <?php if(!empty($row['duration'])): ?>
                                                    <span class="info-chip">
                                                        <i class="fa fa-clock"></i>
                                                        <?= htmlspecialchars($row['duration']) ?> Day(s)
                                                    </span>
                                                <?php endif; ?>
                                            </div>

                                            <div class="meta-grid">
                                                <div class="meta-item">
                                                    <span>Start Date</span>
                                                    <strong><?= !empty($row['start_date']) ? date('d M Y h:i A', strtotime($row['start_date'])) : 'Not specified' ?></strong>
                                                </div>

                                                <div class="meta-item">
                                                    <span>End Date</span>
                                                    <strong><?= !empty($row['end_date']) ? date('d M Y h:i A', strtotime($row['end_date'])) : 'Not specified' ?></strong>
                                                </div>

                                                <div class="meta-item">
                                                    <span>Venue</span>
                                                    <strong><?= htmlspecialchars($row['venue'] ?? 'Not specified') ?></strong>
                                                </div>

                                                <div class="meta-item">
                                                    <span>Capacity</span>
                                                    <strong><?= htmlspecialchars($row['capacity'] ?? 'Unlimited') ?></strong>
                                                </div>
                                            </div>

                                            <div class="action-row">
                                                <a href="event_details.php?id=<?= (int)$row['id'] ?>" class="btn-main">
                                                    <i class="fa fa-eye"></i> View Details
                                                </a>

                                                <?php if($status === 'OPEN'): ?>
                                                    <a href="apply_course.php?course_id=<?= (int)$row['id'] ?>" class="btn-lite">
                                                        <i class="fa fa-plus-circle"></i> Register Now
                                                    </a>
                                                <?php else: ?>
                                                    <span class="btn-lite">
                                                        <i class="fa fa-lock"></i> Registration Closed
                                                    </span>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                    </div>

                                </div>
                            <?php endwhile; ?>
                        </div>
                    <?php else: ?>
                        <div class="empty-box">
                            <i class="fa fa-calendar-xmark"></i>
                            <h6>No upcoming events</h6>
                            <p>There are currently no upcoming CPD events available.</p>
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

    <a href="events.php" class="active">
        <i class="fa fa-calendar"></i>
        Events
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

const searchInput = document.getElementById('eventSearch');
const filterSelect = document.getElementById('eventFilter');
const items = document.querySelectorAll('.event-item');

function filterEvents(){
    const q = (searchInput?.value || '').toLowerCase().trim();
    const f = (filterSelect?.value || '').toLowerCase().trim();

    items.forEach(item => {
        const title = item.dataset.title || '';
        const venue = item.dataset.venue || '';
        const status = item.dataset.status || '';

        const matchSearch = title.includes(q) || venue.includes(q) || status.includes(q);
        const matchFilter = !f || status.includes(f);

        item.style.display = (matchSearch && matchFilter) ? '' : 'none';
    });
}

if (searchInput) searchInput.addEventListener('input', filterEvents);
if (filterSelect) filterSelect.addEventListener('change', filterEvents);
</script>

</body>
</html>