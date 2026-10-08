<?php
require_once "../auth1.php";
require_role('CONTRACTOR');
require_once "../config.php";

$user_email = $_SESSION['email'] ?? '';
$user_name  = $_SESSION['full_name'] ?? explode('@', $user_email)[0];

/* =====================================================
GET USER DISCIPLINE FROM LAST APPROVED APPLICATION
===================================================== */
$user_discipline = '';

$discipline_stmt = $conn->prepare("
    SELECT discipline
    FROM cpd_applications
    WHERE email = ?
    ORDER BY id DESC
    LIMIT 1
");
$discipline_stmt->bind_param("s", $user_email);
$discipline_stmt->execute();
$discipline_q = $discipline_stmt->get_result();

if($discipline_q && $discipline_q->num_rows){
    $user_discipline = trim($discipline_q->fetch_assoc()['discipline'] ?? '');
}

/* =====================================================
GET RECOMMENDED COURSES
Priority:
1. Open future courses
2. Try to match discipline in title/description
3. Exclude already applied courses
===================================================== */
$recommended_stmt = $conn->prepare("
    SELECT *
    FROM courses
    WHERE status='OPEN'
      AND start_date >= NOW()
      AND id NOT IN (
            SELECT course_id
            FROM cpd_applications
            WHERE email = ?
      )
    ORDER BY start_date ASC
");
$recommended_stmt->bind_param("s", $user_email);
$recommended_stmt->execute();
$recommended = $recommended_stmt->get_result();

$total_recommended = $recommended ? $recommended->num_rows : 0;

/* =====================================================
COUNT UPCOMING OPEN COURSES
===================================================== */
$open_q = $conn->query("
    SELECT COUNT(*) total_open
    FROM courses
    WHERE status='OPEN'
      AND start_date >= NOW()
");
$total_open = 0;
if($open_q && $open_q->num_rows){
    $total_open = (int)($open_q->fetch_assoc()['total_open'] ?? 0);
}

/* =====================================================
COUNT MATCHED COURSES
===================================================== */
$matched_count = 0;
if($recommended && $recommended->num_rows > 0){
    while($r = $recommended->fetch_assoc()){
        $haystack = strtolower(($r['title'] ?? '').' '.($r['description'] ?? ''));
        if($user_discipline && str_contains($haystack, strtolower($user_discipline))){
            $matched_count++;
        }
    }
    $recommended->data_seek(0);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">

<title>Recommended Courses</title>

<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">

<style>
:root{
    --primary:#082b57;
    --primary2:#0d4f9c;
    --accent:#e3262e;
    --accent2:#ff5d5d;
    --soft:#f4f7fb;
    --text:#1f2937;
    --muted:#6b7280;
    --shadow:0 15px 40px rgba(11,35,74,.14);
    --shadow-soft:0 10px 30px rgba(0,0,0,.08);
}
*{box-sizing:border-box;}
body{
    margin:0;
    font-family:'Poppins',sans-serif;
    background:
        radial-gradient(circle at top right, rgba(227,38,46,0.10), transparent 20%),
        radial-gradient(circle at top left, rgba(13,79,156,0.12), transparent 25%),
        #f4f7fb;
    color:var(--text);
    overflow-x:hidden;
}

/* SIDEBAR */
.sidebar{
    position:fixed;
    top:0;
    left:0;
    width:270px;
    height:100vh;
    z-index:1200;
    background:linear-gradient(180deg,#082b57 0%,#0d4f9c 100%);
    color:#fff;
    padding:22px 16px 20px;
    box-shadow:8px 0 30px rgba(0,0,0,.15);
    transition:.35s ease;
    overflow-y:auto;
}
.brand-box{
    padding:14px 14px 18px;
    border-radius:20px;
    background:rgba(255,255,255,.07);
    border:1px solid rgba(255,255,255,.10);
    margin-bottom:22px;
}
.brand-top{
    display:flex;
    align-items:center;
    gap:12px;
}
.brand-icon{
    width:48px;
    height:48px;
    border-radius:16px;
    background:linear-gradient(135deg,#ff4d4d,#b40f18);
    display:flex;
    align-items:center;
    justify-content:center;
    font-size:20px;
    box-shadow:0 8px 20px rgba(227,38,46,.35);
}
.brand-text h4{
    font-size:18px;
    margin:0;
    font-weight:700;
}
.brand-text small{
    color:rgba(255,255,255,.75);
    font-size:12px;
}
.menu-title{
    font-size:11px;
    text-transform:uppercase;
    letter-spacing:1.2px;
    color:rgba(255,255,255,.6);
    padding:6px 12px 10px;
    font-weight:600;
}
.sidebar a{
    display:flex;
    align-items:center;
    justify-content:space-between;
    gap:10px;
    padding:13px 14px;
    margin-bottom:8px;
    color:rgba(255,255,255,.85);
    text-decoration:none;
    border-radius:16px;
    transition:.28s ease;
}
.sidebar a .left{
    display:flex;
    align-items:center;
    gap:12px;
}
.sidebar a:hover,
.sidebar a.active{
    background:linear-gradient(135deg,#e3262e,#ff5d5d);
    color:#fff;
    transform:translateX(4px);
    box-shadow:0 10px 20px rgba(227,38,46,.28);
}
.menu-badge{
    min-width:24px;
    height:24px;
    padding:0 7px;
    border-radius:999px;
    background:rgba(255,255,255,.16);
    display:flex;
    align-items:center;
    justify-content:center;
    font-size:11px;
    font-weight:700;
}

/* HEADER */
.header{
    position:fixed;
    top:0;
    left:270px;
    right:0;
    height:82px;
    z-index:1100;
    display:flex;
    align-items:center;
    justify-content:space-between;
    padding:0 26px;
    backdrop-filter:blur(14px);
    background:rgba(255,255,255,.80);
    border-bottom:1px solid rgba(8,43,87,.07);
    box-shadow:0 8px 25px rgba(0,0,0,.05);
}
.header-left h3{
    margin:0;
    font-size:22px;
    font-weight:700;
    color:var(--primary);
}
.header-left small{
    color:var(--muted);
    font-size:13px;
}
.header-right{
    display:flex;
    align-items:center;
    gap:16px;
}
.icon-btn{
    width:48px;
    height:48px;
    border:none;
    border-radius:16px;
    background:#fff;
    box-shadow:var(--shadow-soft);
    color:var(--primary);
    display:flex;
    align-items:center;
    justify-content:center;
    position:relative;
}
.notify-badge{
    position:absolute;
    top:8px;
    right:9px;
    width:18px;
    height:18px;
    border-radius:50%;
    background:var(--accent);
    color:#fff;
    font-size:10px;
    display:flex;
    align-items:center;
    justify-content:center;
    font-weight:700;
    border:2px solid #fff;
}
.user-box{
    display:flex;
    align-items:center;
    gap:12px;
    background:linear-gradient(135deg,#ffffff,#f7fbff);
    border:1px solid rgba(8,43,87,.08);
    box-shadow:var(--shadow-soft);
    border-radius:18px;
    padding:8px 12px 8px 10px;
}
.user-avatar{
    width:46px;
    height:46px;
    border-radius:16px;
    border:2px solid #fff;
}
.user-meta strong{
    display:block;
    font-size:14px;
    color:#111827;
}
.user-meta span{
    display:block;
    font-size:12px;
    color:var(--muted);
}

/* MOBILE */
.mobile-header{display:none;}

/* CONTENT */
.content{
    margin-left:270px;
    padding:108px 24px 30px;
    min-height:100vh;
}

/* HERO */
.hero-card{
    position:relative;
    overflow:hidden;
    background:linear-gradient(135deg,#082b57,#0d4f9c);
    color:#fff;
    border-radius:28px;
    padding:28px;
    margin-bottom:24px;
    box-shadow:0 18px 40px rgba(8,43,87,.18);
}
.hero-card::before{
    content:"";
    position:absolute;
    top:-40px;
    right:-20px;
    width:180px;
    height:180px;
    background:rgba(255,255,255,.08);
    border-radius:50%;
}
.hero-card::after{
    content:"";
    position:absolute;
    bottom:-60px;
    right:100px;
    width:160px;
    height:160px;
    background:rgba(227,38,46,.14);
    border-radius:50%;
}
.hero-card h2{
    font-size:28px;
    margin:0 0 8px;
    font-weight:800;
    position:relative;
    z-index:2;
}
.hero-card p{
    margin:0;
    color:rgba(255,255,255,.85);
    position:relative;
    z-index:2;
}

/* STATS */
.stat-card{
    position:relative;
    overflow:hidden;
    border-radius:24px;
    padding:24px;
    color:#fff;
    min-height:140px;
    box-shadow:var(--shadow);
}
.stat-card.primary{ background:linear-gradient(135deg,#082b57,#0d4f9c); }
.stat-card.red{ background:linear-gradient(135deg,#b8141d,#ff4f57); }
.stat-card.dark{ background:linear-gradient(135deg,#1a2440,#334155); }
.stat-card::before{
    content:"";
    position:absolute;
    width:120px;
    height:120px;
    right:-18px;
    top:-18px;
    border-radius:50%;
    background:rgba(255,255,255,.09);
}
.stat-top{
    display:flex;
    justify-content:space-between;
    align-items:flex-start;
    position:relative;
    z-index:2;
}
.stat-label{
    font-size:13px;
    color:rgba(255,255,255,.82);
    margin-bottom:12px;
    display:block;
}
.stat-value{
    font-size:32px;
    font-weight:800;
    line-height:1;
    margin-bottom:8px;
}
.stat-sub{
    font-size:12px;
    color:rgba(255,255,255,.78);
}
.stat-icon{
    width:52px;
    height:52px;
    border-radius:18px;
    background:rgba(255,255,255,.13);
    display:flex;
    align-items:center;
    justify-content:center;
    font-size:22px;
}

/* CARD */
.card-pro{
    background:linear-gradient(180deg,#ffffff,#fbfdff);
    border:1px solid rgba(8,43,87,.06);
    border-radius:24px;
    padding:22px;
    box-shadow:var(--shadow-soft);
    margin-bottom:20px;
}
.card-title-row{
    display:flex;
    justify-content:space-between;
    align-items:center;
    margin-bottom:18px;
}
.card-title-row h5,
.card-title-row h6{
    margin:0;
    font-weight:700;
    color:var(--primary);
}

/* SEARCH */
.search-wrap{
    display:flex;
    gap:12px;
    flex-wrap:wrap;
    margin-bottom:22px;
}
.search-box{
    flex:1;
    min-width:240px;
    position:relative;
}
.search-box i{
    position:absolute;
    top:50%;
    left:16px;
    transform:translateY(-50%);
    color:#64748b;
}
.search-box input,
.filter-select{
    width:100%;
    height:52px;
    border-radius:16px;
    border:1px solid rgba(8,43,87,.08);
    background:#fff;
    padding:0 16px 0 44px;
    outline:none;
    font-family:Poppins,sans-serif;
}
.filter-select{
    padding-left:16px;
    min-width:200px;
}

/* RECOMMEND CARD */
.recommend-card{
    position:relative;
    overflow:hidden;
    border-radius:26px;
    background:#fff;
    border:1px solid rgba(8,43,87,.06);
    box-shadow:0 14px 30px rgba(0,0,0,.06);
    margin-bottom:22px;
    transition:.25s ease;
}
.recommend-card:hover{
    transform:translateY(-4px);
    box-shadow:0 18px 35px rgba(8,43,87,.10);
}
.recommend-banner{
    height:220px;
    background:#eaf1fb center/cover no-repeat;
    position:relative;
}
.recommend-banner::after{
    content:"";
    position:absolute;
    inset:0;
    background:linear-gradient(180deg,rgba(0,0,0,.10),rgba(0,0,0,.48));
}
.recommend-status{
    position:absolute;
    top:16px;
    right:16px;
    z-index:2;
    padding:8px 14px;
    border-radius:999px;
    font-size:12px;
    font-weight:700;
    color:#fff;
}
.badge-open{ background:linear-gradient(135deg,#16a34a,#22c55e); }
.badge-match{ background:linear-gradient(135deg,#e3262e,#ff5d5d); }

.recommend-body{
    padding:22px;
}
.recommend-title{
    font-size:20px;
    font-weight:800;
    color:#0f172a;
    margin-bottom:8px;
}
.recommend-desc{
    color:var(--muted);
    font-size:14px;
    line-height:1.7;
    margin-bottom:18px;
}
.info-row{
    display:flex;
    flex-wrap:wrap;
    gap:10px;
    margin-bottom:18px;
}
.info-chip{
    background:#eef4ff;
    color:var(--primary2);
    border-radius:999px;
    padding:8px 12px;
    font-size:12px;
    font-weight:600;
}
.meta-grid{
    display:grid;
    grid-template-columns:repeat(2, minmax(0,1fr));
    gap:12px;
    margin-bottom:18px;
}
.meta-item{
    background:#f7faff;
    border:1px solid #ecf2fb;
    border-radius:16px;
    padding:14px;
}
.meta-item span{
    display:block;
    font-size:11px;
    text-transform:uppercase;
    letter-spacing:.8px;
    color:#64748b;
    margin-bottom:5px;
    font-weight:700;
}
.meta-item strong{
    display:block;
    font-size:14px;
    color:#0f172a;
    word-break:break-word;
}
.action-row{
    display:flex;
    flex-wrap:wrap;
    gap:12px;
}
.btn-pro{
    border:none;
    border-radius:14px;
    padding:12px 18px;
    font-weight:600;
    text-decoration:none;
    display:inline-flex;
    align-items:center;
    gap:8px;
    transition:.25s ease;
}
.btn-primary-pro{
    background:linear-gradient(135deg,#082b57,#0d4f9c);
    color:#fff;
}
.btn-red-pro{
    background:linear-gradient(135deg,#e3262e,#ff5d5d);
    color:#fff;
}
.btn-light-pro{
    background:#f4f7fb;
    color:#082b57;
    border:1px solid rgba(8,43,87,.08);
}
.btn-pro:hover{
    opacity:.95;
    transform:translateY(-1px);
    color:#fff;
}
.btn-light-pro:hover{
    color:#082b57;
}

/* EMPTY */
.empty-box{
    text-align:center;
    padding:40px 16px;
    border:1px dashed rgba(8,43,87,.12);
    border-radius:22px;
    background:#fbfdff;
}
.empty-box i{
    font-size:38px;
    color:#c0cad9;
    margin-bottom:10px;
}
.empty-box h6{
    font-weight:700;
    margin-bottom:6px;
    color:#334155;
}
.empty-box p{
    margin:0;
    color:#64748b;
    font-size:13px;
}

/* BOTTOM NAV */
.bottom-nav{display:none;}

@media(max-width:991px){
    .sidebar{
        left:-280px;
        width:270px;
    }
    .sidebar.show{
        left:0;
    }
    .header{display:none;}
    .mobile-header{
        display:flex;
        position:fixed;
        top:0;
        left:0;
        right:0;
        height:68px;
        z-index:1250;
        align-items:center;
        justify-content:space-between;
        padding:0 14px;
        background:linear-gradient(135deg,#082b57,#0d4f9c);
        color:#fff;
        box-shadow:0 8px 20px rgba(0,0,0,.15);
    }
    .content{
        margin-left:0;
        padding:86px 14px 90px;
    }
    .meta-grid{
        grid-template-columns:1fr;
    }
    .recommend-banner{
        height:180px;
    }
    .bottom-nav{
        display:flex;
        position:fixed;
        left:0;
        right:0;
        bottom:0;
        z-index:1250;
        background:rgba(255,255,255,.96);
        box-shadow:0 -8px 22px rgba(0,0,0,.10);
        padding:10px 4px;
        justify-content:space-around;
    }
    .bottom-nav a{
        text-decoration:none;
        color:#64748b;
        text-align:center;
        font-size:11px;
        font-weight:600;
        width:20%;
    }
    .bottom-nav a i{
        display:block;
        font-size:18px;
        margin-bottom:4px;
    }
    .bottom-nav a.active{
        color:var(--accent);
    }
}
</style>
<?php require __DIR__ . '/_hub_css.php'; ?>
</head>
<body class="hub-root">

<div class="mobile-header d-lg-none">
    <i class="fa fa-bars" onclick="toggleSidebar()" style="font-size:20px;cursor:pointer;"></i>
    <div style="font-weight:700;font-size:17px;">Recommended</div>
    <div style="position:relative;">
        <i class="fa fa-bell" style="font-size:19px;"></i>
        <span style="position:absolute;top:-6px;right:-8px;background:#ff4d4d;color:#fff;font-size:9px;width:16px;height:16px;display:flex;align-items:center;justify-content:center;border-radius:50%;">3</span>
    </div>
</div>

<div class="sidebar" id="sidebar">
    <div class="brand-box">
        <div class="brand-top">
            <div class="brand-icon"><i class="fa fa-graduation-cap"></i></div>
            <div class="brand-text">
                <h4>CPD Portal</h4>
                <small>Contractor Learning Dashboard</small>
            </div>
        </div>
    </div>

    <div class="menu-title">Main Menu</div>

    <a href="dashboard.php"><div class="left"><i class="fa fa-house"></i> Dashboard</div></a>
    <a href="my_courses.php"><div class="left"><i class="fa fa-book-open"></i> My Courses</div></a>
    <a href="certificates.php"><div class="left"><i class="fa fa-certificate"></i> Certificates</div></a>
    <a href="resources.php"><div class="left"><i class="fa fa-folder-open"></i> Resources</div></a>
    <a href="events.php"><div class="left"><i class="fa fa-calendar-days"></i> Events</div></a>
    <a href="points_tracker.php"><div class="left"><i class="fa fa-chart-line"></i> CPD Tracker</div></a>
    <a href="recommended.php" class="active">
        <div class="left"><i class="fa fa-lightbulb"></i> Recommended</div>
        <span class="menu-badge"><?=$total_recommended?></span>
    </a>
    <a href="feedback.php"><div class="left"><i class="fa fa-star"></i> Feedback</div></a>
    <a href="support.php"><div class="left"><i class="fa fa-headset"></i> Support</div></a>

    <hr style="border-color:rgba(255,255,255,.12);">

    <div class="menu-title">Account</div>
    <a href="account.php"><div class="left"><i class="fa fa-user"></i> Account</div></a>
    <a href="/index.php"><div class="left"><i class="fa fa-globe"></i> ECA home</div></a>
    <a href="logout.php"><div class="left"><i class="fa fa-right-from-bracket"></i> Logout</div></a>
</div>

<div class="header">
    <div class="header-left">
        <h3>Recommended Courses</h3>
        <small>Suggested learning opportunities based on your profile</small>
    </div>

    <div class="header-right">
        <button class="icon-btn">
            <i class="fa fa-bell"></i>
            <span class="notify-badge">3</span>
        </button>

        <div class="user-box">
            <img
                class="user-avatar"
                src="https://ui-avatars.com/api/?name=<?=urlencode($user_name)?>&background=0D4F9C&color=fff&rounded=true&bold=true&size=128"
                alt="User Avatar"
            >
            <div class="user-meta">
                <strong><?=htmlspecialchars($user_name)?></strong>
                <span><?=htmlspecialchars($user_email)?></span>
            </div>
            <i class="fa fa-angle-down" style="color:#64748b;"></i>
        </div>
    </div>
</div>

<div class="content">


    <div class="row g-4 mb-4">
        <div class="col-md-4">
            <div class="stat-card primary">
                <div class="stat-top">
                    <div>
                        <span class="stat-label">Available Recommendations</span>
                        <div class="stat-value"><?=$total_recommended?></div>
                        <div class="stat-sub">Open courses not yet applied for</div>
                    </div>
                    <div class="stat-icon"><i class="fa fa-lightbulb"></i></div>
                </div>
            </div>
        </div>

        <div class="col-md-4">
            <div class="stat-card red">
                <div class="stat-top">
                    <div>
                        <span class="stat-label">Profile Matches</span>
                        <div class="stat-value"><?=$matched_count?></div>
                        <div class="stat-sub">Courses matching your discipline</div>
                    </div>
                    <div class="stat-icon"><i class="fa fa-bullseye"></i></div>
                </div>
            </div>
        </div>

        <div class="col-md-4">
            <div class="stat-card dark">
                <div class="stat-top">
                    <div>
                        <span class="stat-label">Open Courses</span>
                        <div class="stat-value"><?=$total_open?></div>
                        <div class="stat-sub">All current open CPD opportunities</div>
                    </div>
                    <div class="stat-icon"><i class="fa fa-door-open"></i></div>
                </div>
            </div>
        </div>
    </div>

    <div class="card-pro">
        <div class="card-title-row">
            <h5>Suggested For You</h5>
            <span style="font-size:13px;color:#64748b;">Based on your course history and discipline</span>
        </div>

        <div class="search-wrap">
            <div class="search-box">
                <i class="fa fa-search"></i>
                <input type="text" id="recommendSearch" placeholder="Search recommended courses by title, venue or description...">
            </div>

            <select class="filter-select" id="recommendFilter">
                <option value="">All Recommendations</option>
                <option value="match">Best Match</option>
                <option value="open">Open</option>
            </select>
        </div>

        <?php if($recommended && $total_recommended > 0): ?>
            <div id="recommendGrid">
                <?php while($row = $recommended->fetch_assoc()): ?>
                    <?php
                        $banner = !empty($row['banner']) ? "../".$row['banner'] : "https://images.unsplash.com/photo-1516321318423-f06f85e504b3?q=80&w=1400&auto=format&fit=crop";
                        $haystack = strtolower(($row['title'] ?? '').' '.($row['description'] ?? ''));
                        $is_match = ($user_discipline && str_contains($haystack, strtolower($user_discipline)));
                    ?>
                    <div class="recommend-item"
                         data-title="<?=htmlspecialchars(strtolower($row['title'] ?? ''))?>"
                         data-venue="<?=htmlspecialchars(strtolower($row['venue'] ?? ''))?>"
                         data-desc="<?=htmlspecialchars(strtolower($row['description'] ?? ''))?>"
                         data-type="<?= $is_match ? 'match' : 'open' ?>">
                        <div class="recommend-card">
                            <div class="recommend-banner" style="background-image:url('<?=htmlspecialchars($banner)?>');">
                                <div class="recommend-status <?= $is_match ? 'badge-match' : 'badge-open' ?>">
                                    <?= $is_match ? 'Best Match' : 'Open Course' ?>
                                </div>
                            </div>

                            <div class="recommend-body">
                                <div class="recommend-title"><?=htmlspecialchars($row['title'] ?? 'Untitled Course')?></div>

                                <div class="recommend-desc">
                                    <?=!empty($row['description']) ? nl2br(htmlspecialchars(substr($row['description'],0,220))) : 'No course description available yet.'?>
                                </div>

                                <div class="info-row">
                                    <?php if($user_discipline): ?>
                                        <span class="info-chip"><i class="fa fa-briefcase"></i> <?=htmlspecialchars($user_discipline)?></span>
                                    <?php endif; ?>
                                    <span class="info-chip"><i class="fa fa-location-dot"></i> <?=htmlspecialchars($row['venue'] ?? 'Venue TBA')?></span>
                                    <?php if(!empty($row['points'])): ?>
                                        <span class="info-chip"><i class="fa fa-award"></i> <?=htmlspecialchars($row['points'])?> Points</span>
                                    <?php endif; ?>
                                    <?php if(!empty($row['duration'])): ?>
                                        <span class="info-chip"><i class="fa fa-clock"></i> <?=htmlspecialchars($row['duration'])?> Day(s)</span>
                                    <?php endif; ?>
                                </div>

                                <div class="meta-grid">
                                    <div class="meta-item">
                                        <span>Start Date</span>
                                        <strong><?=!empty($row['start_date']) ? date('d M Y h:i A', strtotime($row['start_date'])) : 'Not specified'?></strong>
                                    </div>

                                    <div class="meta-item">
                                        <span>End Date</span>
                                        <strong><?=!empty($row['end_date']) ? date('d M Y h:i A', strtotime($row['end_date'])) : 'Not specified'?></strong>
                                    </div>

                                    <div class="meta-item">
                                        <span>Venue</span>
                                        <strong><?=htmlspecialchars($row['venue'] ?? 'Not specified')?></strong>
                                    </div>

                                    <div class="meta-item">
                                        <span>Capacity</span>
                                        <strong><?=htmlspecialchars($row['capacity'] ?? 'Unlimited')?></strong>
                                    </div>
                                </div>

                                <div class="action-row">
                                    <a href="event_details.php?id=<?=$row['id']?>" class="btn-pro btn-primary-pro">
                                        <i class="fa fa-eye"></i> View Details
                                    </a>

                                    <a href="apply_course.php?course_id=<?=$row['id']?>" class="btn-pro btn-red-pro">
                                        <i class="fa fa-plus-circle"></i> Apply Now
                                    </a>

                                    <a href="events.php" class="btn-pro btn-light-pro">
                                        <i class="fa fa-calendar"></i> More Events
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endwhile; ?>
            </div>
        <?php else: ?>
            <div class="empty-box">
                <i class="fa fa-lightbulb"></i>
                <h6>No recommendations available yet</h6>
                <p>There are no open recommended courses available at the moment.</p>
            </div>
        <?php endif; ?>
    </div>

</div>

<div class="bottom-nav d-lg-none">
    <a href="dashboard.php"><i class="fa fa-house"></i>Home</a>
    <a href="my_courses.php"><i class="fa fa-book"></i>Courses</a>
    <a href="certificates.php"><i class="fa fa-certificate"></i>Certs</a>
    <a href="recommended.php" class="active"><i class="fa fa-lightbulb"></i>Suggest</a>
    <a href="account.php"><i class="fa fa-user"></i>Account</a>
</div>

<script>
function toggleSidebar(){
    document.getElementById('sidebar').classList.toggle('show');
}

document.addEventListener('click', function(e){
    const sidebar = document.getElementById('sidebar');
    const mobileToggle = document.querySelector('.mobile-header .fa-bars');

    if(window.innerWidth <= 991 && mobileToggle){
        if(sidebar.classList.contains('show') && !sidebar.contains(e.target) && !mobileToggle.contains(e.target)){
            sidebar.classList.remove('show');
        }
    }
});

const searchInput = document.getElementById('recommendSearch');
const filterSelect = document.getElementById('recommendFilter');
const items = document.querySelectorAll('.recommend-item');

function filterRecommendations(){
    const q = (searchInput?.value || '').toLowerCase().trim();
    const f = (filterSelect?.value || '').toLowerCase().trim();

    items.forEach(item => {
        const title = item.dataset.title || '';
        const venue = item.dataset.venue || '';
        const desc = item.dataset.desc || '';
        const type = item.dataset.type || '';

        const matchSearch = title.includes(q) || venue.includes(q) || desc.includes(q);
        const matchFilter = !f || type === f;

        item.style.display = (matchSearch && matchFilter) ? '' : 'none';
    });
}

if(searchInput) searchInput.addEventListener('input', filterRecommendations);
if(filterSelect) filterSelect.addEventListener('change', filterRecommendations);
</script>

</body>
</html>