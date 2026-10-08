<?php
require_once "../auth1.php";
require_role('CONTRACTOR');
require_once "../config.php";

$user_id    = $_SESSION['user_id'] ?? 0;
$user_email = $_SESSION['email'] ?? '';
$user_name  = $_SESSION['full_name'] ?? explode('@', $user_email)[0];

/* =========================
   HELPER
========================= */
function h($value){
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}

/* =========================
   AUTO-GENERATE CERTIFICATE NUMBERS
   FOR COMPLETED COURSES WHERE EMPTY
========================= */
$pending_stmt = $conn->prepare("
    SELECT id, course_id, created_at, certificate_number
    FROM cpd_applications
    WHERE email = ?
      AND status = 'Approved'
      AND training_status = 'Completed'
      AND (certificate_number IS NULL OR certificate_number = '')
    ORDER BY id ASC
");
$pending_stmt->bind_param("s", $user_email);
$pending_stmt->execute();
$pending_result = $pending_stmt->get_result();

while ($p = $pending_result->fetch_assoc()) {
    $app_id    = (int)$p['id'];
    $course_id = (int)$p['course_id'];
    $year      = !empty($p['created_at']) ? date('Y', strtotime($p['created_at'])) : date('Y');

    $certificate_number = sprintf("ECA-CPD-%s-%06d-%02d", $year, $app_id, $course_id);

    $update_stmt = $conn->prepare("
        UPDATE cpd_applications
        SET certificate_number = ?
        WHERE id = ?
          AND (certificate_number IS NULL OR certificate_number = '')
    ");
    $update_stmt->bind_param("si", $certificate_number, $app_id);
    $update_stmt->execute();
}

/* =========================
   FETCH COMPLETED COURSES
========================= */
$certs_stmt = $conn->prepare("
    SELECT 
        a.*,
        c.title,
        c.description,
        c.banner,
        c.start_date,
        c.end_date,
        c.venue,
        c.points,
        c.duration
    FROM cpd_applications a
    LEFT JOIN courses c ON c.id = a.course_id
    WHERE a.email = ?
      AND a.status = 'Approved'
      AND a.training_status = 'Completed'
    ORDER BY a.id DESC
");
$certs_stmt->bind_param("s", $user_email);
$certs_stmt->execute();
$certs = $certs_stmt->get_result();

$total_certs = $certs ? $certs->num_rows : 0;

/* Count generated certificate numbers */
$generated_count = 0;
if ($certs && $total_certs > 0) {
    while ($r = $certs->fetch_assoc()) {
        if (!empty(trim($r['certificate_number'] ?? ''))) {
            $generated_count++;
        }
    }
    $certs->data_seek(0);
}

$pending_count = max(0, $total_certs - $generated_count);
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">

<title>My Certificates</title>

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

*{box-sizing:border-box;}

html,body{
    margin:0;
    padding:0;
    font-family:'Barlow',sans-serif;
    background:linear-gradient(to bottom, #f5f7fb 0%, #edf1f7 100%);
    color:var(--text);
    overflow-x:hidden;
}

.sidebar{
    position:fixed;
    top:0;
    left:0;
    width:240px;
    height:100vh;
    z-index:1200;
    background:#fff;
    border-right:1px solid var(--border);
    padding:16px 12px;
    overflow-y:auto;
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

.header{
    position:fixed;
    top:0;
    left:240px;
    right:0;
    height:74px;
    z-index:1100;
    background:rgba(255,255,255,0.96);
    border-bottom:1px solid var(--border);
    display:flex;
    align-items:center;
    justify-content:space-between;
    padding:0 20px;
    backdrop-filter:blur(10px);
}

.header-left h3{
    margin:0;
    font-size:20px;
    font-weight:800;
    color:var(--blue-dark);
}

.header-left small{
    color:var(--muted);
    font-size:12px;
    font-weight:600;
}

.header-right{
    display:flex;
    align-items:center;
    gap:12px;
}

.icon-btn{
    width:42px;
    height:42px;
    border:none;
    border-radius:12px;
    background:#fff;
    border:1px solid var(--border);
    color:var(--blue-dark);
    display:flex;
    align-items:center;
    justify-content:center;
    position:relative;
    box-shadow:var(--shadow-soft);
}

.notify-badge{
    position:absolute;
    top:6px;
    right:6px;
    width:16px;
    height:16px;
    border-radius:50%;
    background:var(--blue-dark);
    color:#fff;
    font-size:9px;
    display:flex;
    align-items:center;
    justify-content:center;
    font-weight:700;
}

.user-box{
    display:flex;
    align-items:center;
    gap:10px;
    background:#f8fafc;
    border:1px solid var(--border);
    border-radius:14px;
    padding:6px 10px;
    box-shadow:var(--shadow-soft);
}

.user-avatar{
    width:42px;
    height:42px;
    border-radius:12px;
    object-fit:cover;
}

.user-meta strong{
    display:block;
    font-size:14px;
    color:var(--blue-dark);
    font-weight:800;
}

.user-meta span{
    display:block;
    font-size:12px;
    color:var(--muted);
}

.mobile-header{ display:none; }

.content{
    margin-left:240px;
    padding:94px 18px 24px;
    min-height:100vh;
}

.page-title{
    background:linear-gradient(180deg, #3f5f8d 0%, #29486f 100%);
    color:#fff;
    text-align:center;
    padding:16px 14px;
    border-radius:0;
    box-shadow:var(--shadow);
    margin-bottom:16px;
}

.page-title h1{
    margin:0;
    font-size:24px;
    font-weight:800;
    letter-spacing:.3px;
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

.simple-card{
    background:#fff;
    border:1px solid var(--border);
    border-radius:14px;
    padding:16px;
    box-shadow:var(--shadow);
}

.card-title-row{
    display:flex;
    justify-content:space-between;
    align-items:center;
    gap:10px;
    margin-bottom:14px;
    flex-wrap:wrap;
}

.card-title-row h5{
    margin:0;
    font-size:16px;
    font-weight:800;
    color:var(--blue-dark);
}

.portal-cert-card{
    background:#fff;
    border:1px solid #d8e0ea;
    border-radius:16px;
    box-shadow:0 4px 14px rgba(30, 53, 88, 0.06);
    overflow:hidden;
    transition:all .25s ease;
    display:flex;
    flex-direction:column;
    height:100%;
}

.portal-cert-card:hover{
    transform:translateY(-3px);
    box-shadow:0 10px 22px rgba(30, 53, 88, 0.10);
    border-color:#c9d7e6;
}

.portal-cert-top{
    padding:16px 16px 10px;
    display:flex;
    align-items:center;
    justify-content:space-between;
    background:linear-gradient(180deg,#f7fbff 0%, #ffffff 100%);
}

.portal-cert-icon{
    width:52px;
    height:52px;
    border-radius:14px;
    background:linear-gradient(180deg, #456ea6 0%, #30537f 100%);
    color:#fff;
    display:flex;
    align-items:center;
    justify-content:center;
    font-size:22px;
    box-shadow:0 8px 18px rgba(48,83,127,.18);
}

.portal-cert-status{
    font-size:11px;
    font-weight:800;
    padding:7px 12px;
    border-radius:999px;
    text-transform:uppercase;
    letter-spacing:.4px;
}

.portal-cert-status.issued{
    background:#eaf7ef;
    color:#1f7a46;
}

.portal-cert-status.completed{
    background:#e8f0ff;
    color:#2f67b1;
}

.portal-cert-status.pending{
    background:#fff4df;
    color:#b7791f;
}

.portal-cert-body{
    padding:0 16px 16px;
    flex:1;
}

.portal-cert-title{
    font-size:16px;
    font-weight:800;
    color:var(--blue-dark);
    line-height:1.4;
    margin:4px 0 8px;
    min-height:45px;
}

.portal-cert-user{
    font-size:13px;
    color:var(--muted);
    margin-bottom:14px;
    line-height:1.5;
}

.portal-cert-points{
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
    border:1px solid #d7e3f1;
}

.portal-cert-number{
    background:#fcfcfd;
    border:1px dashed #cfd8e3;
    border-radius:12px;
    padding:12px;
}

.portal-cert-number small{
    display:block;
    font-size:10px;
    text-transform:uppercase;
    color:var(--muted);
    font-weight:700;
    margin-bottom:5px;
    letter-spacing:.5px;
}

.portal-cert-number strong{
    display:block;
    font-size:13px;
    color:var(--blue-dark);
    word-break:break-word;
}

.portal-cert-actions{
    display:flex;
    gap:10px;
    padding:14px 16px 16px;
    border-top:1px solid #edf2f7;
}

.btn-cert-main,
.btn-cert-light{
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

.btn-cert-main{
    background:linear-gradient(180deg, #456ea6 0%, #30537f 100%);
    color:#fff;
    border:1px solid #30537f;
    box-shadow:0 4px 10px rgba(48,83,127,.18);
}

.btn-cert-main:hover{
    color:#fff;
    transform:translateY(-1px);
}

.btn-cert-light{
    background:#fff;
    color:var(--blue-dark);
    border:1px solid #aeb9cb;
}

.btn-cert-light:hover{
    background:#f5f8fd;
    color:var(--blue-dark);
    border-color:var(--blue);
}

.btn-cert-disabled{
    opacity:.82;
    pointer-events:none;
}

.empty-box{
    text-align:center;
    padding:24px 14px;
    border:1px dashed #d7e1eb;
    border-radius:14px;
    background:#fbfcfe;
}

.empty-box i{
    font-size:28px;
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

.bottom-nav{ display:none; }

@media(max-width:991px){
    .sidebar{ left:-260px; }
    .sidebar.show{ left:0; }

    .header{ display:none; }

    .mobile-header{
        display:flex;
        position:fixed;
        top:0;
        left:0;
        right:0;
        height:64px;
        z-index:1250;
        align-items:center;
        justify-content:space-between;
        padding:0 14px;
        background:#fff;
        border-bottom:1px solid var(--border);
        color:var(--blue-dark);
    }

    .mobile-header .m-title{
        font-weight:800;
        font-size:16px;
    }

    .mobile-header .m-actions{
        display:flex;
        align-items:center;
        gap:12px;
    }

    .mobile-header .m-notify{
        position:relative;
        font-size:18px;
    }

    .mobile-header .m-badge{
        position:absolute;
        top:-5px;
        right:-8px;
        background:var(--blue-dark);
        color:#fff;
        font-size:9px;
        width:15px;
        height:15px;
        display:flex;
        align-items:center;
        justify-content:center;
        border-radius:50%;
    }

    .content{
        margin-left:0;
        padding:78px 14px 84px;
    }

    .portal-cert-title{
        min-height:auto;
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
}
</style>
<?php require __DIR__ . '/_hub_css.php'; ?>
</head>
<body class="hub-root">

<div class="mobile-header d-lg-none">
    <i class="fa fa-bars" onclick="toggleSidebar()" style="font-size:20px;cursor:pointer;"></i>
    <div class="m-title">My Certificates</div>
    <div class="m-actions">
        <div class="m-notify">
            <i class="fa fa-bell"></i>
            <span class="m-badge">3</span>
        </div>
    </div>
</div>

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
    <a href="/index.php">
        <div class="left"><i class="fa fa-globe"></i> ECA home</div>
    </a>
    <a href="logout.php">
        <div class="left"><i class="fa fa-right-from-bracket"></i> Logout</div>
    </a>
</div>

<div class="header">
    <div class="header-left">
        <h3>My Certificates</h3>
        <small>Access all issued CPD training certificates</small>
    </div>

    <div class="header-right">
        <button class="icon-btn" title="Notifications">
            <i class="fa fa-bell"></i>
            <span class="notify-badge">3</span>
        </button>

        <div class="user-box">
            <img
                class="user-avatar"
                src="https://ui-avatars.com/api/?name=<?=urlencode($user_name)?>&background=29486f&color=fff&rounded=true&bold=true&size=128"
                alt="User Avatar"
            >
            <div class="user-meta">
                <strong><?=h($user_name)?></strong>
                <span><?=h($user_email)?></span>
            </div>
            <i class="fa fa-angle-down" style="color:#64748b;"></i>
        </div>
    </div>
</div>

<div class="content">

    <div class="page-title">
        <h1>My Certificates</h1>
    </div>

    <div class="row g-3 mb-3">
        <div class="col-md-4">
            <div class="stat-card">
                <div class="stat-top">
                    <div>
                        <span class="stat-label">Completed Courses</span>
                        <div class="stat-value"><?=$total_certs?></div>
                        <div class="stat-sub">Finished courses on your profile</div>
                    </div>
                    <div class="stat-icon blue"><i class="fa fa-circle-check"></i></div>
                </div>
            </div>
        </div>

        <div class="col-md-4">
            <div class="stat-card">
                <div class="stat-top">
                    <div>
                        <span class="stat-label">Certificates Issued</span>
                        <div class="stat-value"><?=$generated_count?></div>
                        <div class="stat-sub">Certificates generated and ready</div>
                    </div>
                    <div class="stat-icon green"><i class="fa fa-certificate"></i></div>
                </div>
            </div>
        </div>

        <div class="col-md-4">
            <div class="stat-card">
                <div class="stat-top">
                    <div>
                        <span class="stat-label">Pending Certificates</span>
                        <div class="stat-value"><?=$pending_count?></div>
                        <div class="stat-sub">Completed courses awaiting issue</div>
                    </div>
                    <div class="stat-icon gold"><i class="fa fa-clock"></i></div>
                </div>
            </div>
        </div>
    </div>

    <div class="simple-card">
        <div class="card-title-row mb-3">
            <h5>My Certificates</h5>
            <div style="font-size:13px;color:var(--muted);font-weight:700;">Ready for viewing and download</div>
        </div>

        <?php if($certs && $total_certs > 0): ?>
            <div class="row g-4">
                <?php while($row = $certs->fetch_assoc()): ?>
                    <?php
                        $cert_no      = trim($row['certificate_number'] ?? '');
                        $has_cert     = !empty($cert_no);
                        $is_completed = (($row['training_status'] ?? '') === 'Completed');

                        $course   = $row['title'] ?? 'Training Certificate';
                        $person   = $row['full_name'] ?? $user_name;
                        $points   = $row['points'] ?? '';

                        $status_class = $has_cert ? 'issued' : ($is_completed ? 'completed' : 'pending');
                        $status_label = $has_cert ? 'Issued' : ($is_completed ? 'Completed' : 'Pending');
                    ?>
                    <div class="col-lg-3 col-md-6">
                        <div class="portal-cert-card h-100">

                            <div class="portal-cert-top">
                                <div class="portal-cert-icon">
                                    <i class="fa fa-certificate"></i>
                                </div>

                                <div class="portal-cert-status <?=$status_class?>">
                                    <?=$status_label?>
                                </div>
                            </div>

                            <div class="portal-cert-body">
                                <h5 class="portal-cert-title"><?= h($course) ?></h5>
                                <p class="portal-cert-user">
                                    Awarded to <strong><?= h($person) ?></strong>
                                </p>

                                <div class="portal-cert-points">
                                    <i class="fa fa-star"></i>
                                    <?= !empty($points) ? h($points).' CPD Points' : 'CPD Points Pending' ?>
                                </div>

                                <div class="portal-cert-number">
                                    <small>Certificate No.</small>
                                    <strong><?= $has_cert ? h($cert_no) : 'Generating…' ?></strong>
                                </div>
                            </div>

                            <div class="portal-cert-actions">
                                <?php if($has_cert): ?>
                                    <a href="../training_cerficate.php?id=<?=$row['id']?>" class="btn-cert-main">
                                        <i class="fa fa-eye"></i> View
                                    </a>
                                    <a href="../training_cerficate.php?id=<?=$row['id']?>" class="btn-cert-light">
                                        <i class="fa fa-download"></i> Download
                                    </a>
                                <?php elseif($is_completed): ?>
                                    <a href="#" class="btn-cert-light btn-cert-disabled">
                                        <i class="fa fa-circle-check"></i> Completed
                                    </a>
                                <?php else: ?>
                                    <a href="#" class="btn-cert-light btn-cert-disabled">
                                        <i class="fa fa-clock"></i> Awaiting
                                    </a>
                                <?php endif; ?>
                            </div>

                        </div>
                    </div>
                <?php endwhile; ?>
            </div>
        <?php else: ?>
            <div class="empty-box">
                <i class="fa fa-certificate"></i>
                <h6>No certificates yet</h6>
                <p>You do not yet have any completed CPD courses with certificates available.</p>
            </div>
        <?php endif; ?>
    </div>
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

    <a href="certificates.php" class="active">
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
function toggleSidebar(){
    document.getElementById('sidebar').classList.toggle('show');
}

document.addEventListener('click', function(e){
    const sidebar = document.getElementById('sidebar');
    const mobileToggle = document.querySelector('.mobile-header .fa-bars');

    if(window.innerWidth <= 991 && mobileToggle){
        if(
            sidebar.classList.contains('show') &&
            !sidebar.contains(e.target) &&
            !mobileToggle.contains(e.target)
        ){
            sidebar.classList.remove('show');
        }
    }
});
</script>

</body>
</html>