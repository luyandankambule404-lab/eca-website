<?php
require_once "../auth1.php";
require_role('CONTRACTOR');
require_once "../config.php";

$user_id    = $_SESSION['user_id'] ?? 0;
$user_email = $_SESSION['email'] ?? '';
$user_name  = $_SESSION['full_name'] ?? explode('@', $user_email)[0];

$msg = "";
$err = "";

/* ==============================
SUBMIT SUPPORT TICKET
============================== */
if(isset($_POST['submit_ticket'])){

    $subject  = trim($_POST['subject'] ?? '');
    $category = trim($_POST['category'] ?? '');
    $priority = trim($_POST['priority'] ?? '');
    $message  = trim($_POST['message'] ?? '');

    if($subject == '' || $category == '' || $priority == '' || $message == ''){
        $err = "Please complete all required support fields.";
    }else{

        $ticket_no = 'SUP-' . date('Y') . '-' . strtoupper(substr(md5(uniqid()),0,6));

        $stmt = $conn->prepare("
            INSERT INTO support_tickets
            (ticket_no, user_id, full_name, email, subject, category, priority, message, status, created_at)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'Open', NOW())
        ");
        $stmt->bind_param(
            "sissssss",
            $ticket_no,
            $user_id,
            $user_name,
            $user_email,
            $subject,
            $category,
            $priority,
            $message
        );

        if($stmt->execute()){
            $msg = "Support ticket submitted successfully. Ticket No: ".$ticket_no;
        }else{
            $err = "Failed to submit support ticket. Please try again.";
        }
        $stmt->close();
    }
}

/* ==============================
LOAD MY TICKETS
============================== */
$user_email_esc = mysqli_real_escape_string($conn, $user_email);

$tickets = $conn->query("
    SELECT *
    FROM support_tickets
    WHERE email = '$user_email_esc'
    ORDER BY id DESC
    LIMIT 10
");

$total_tickets = $tickets ? $tickets->num_rows : 0;

/* ==============================
COUNTS
============================== */
$open_count = 0;
$resolved_count = 0;

$count_q = $conn->query("
    SELECT status, COUNT(*) total
    FROM support_tickets
    WHERE email = '$user_email_esc'
    GROUP BY status
");

if($count_q){
    while($c = $count_q->fetch_assoc()){
        $status = strtolower(trim($c['status']));
        if($status == 'open' || $status == 'in progress'){
            $open_count += (int)$c['total'];
        }
        if($status == 'resolved' || $status == 'closed'){
            $resolved_count += (int)$c['total'];
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Support Center</title>

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
    --red:#d64545;
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

*{box-sizing:border-box;}

html,body{
    margin:0;
    padding:0;
    overflow-x:hidden;
}

body{
    background:linear-gradient(to bottom, #f5f7fb 0%, #edf1f7 100%);
    font-family:'Barlow',sans-serif;
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

.stat-icon.gold{
    background:var(--gold-soft);
    color:var(--gold);
}

.stat-icon.green{
    background:var(--green-soft);
    color:var(--green);
}

/* PANEL */
.panel{
    background:#fff;
    border:1px solid var(--border);
    border-radius:14px;
    box-shadow:var(--shadow);
    overflow:hidden;
    height:100%;
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

/* ALERTS */
.alert-pro{
    padding:14px 16px;
    border-radius:12px;
    margin-bottom:16px;
    font-weight:700;
    border:1px solid transparent;
    font-size:13px;
}

.alert-success-pro{
    background:#ecfdf5;
    color:#166534;
    border-color:#bbf7d0;
}

.alert-danger-pro{
    background:#fef2f2;
    color:#991b1b;
    border-color:#fecaca;
}

/* FORM */
.form-label{
    font-weight:700;
    color:#334155;
    margin-bottom:8px;
    font-size:13px;
}

.form-control,
.form-select{
    border-radius:12px;
    min-height:48px;
    border:1px solid var(--border);
    box-shadow:none;
    padding:12px 14px;
    font-size:14px;
}

.form-control:focus,
.form-select:focus{
    border-color:#9db5d4;
    box-shadow:0 0 0 .18rem rgba(60,106,161,.10);
}

textarea.form-control{
    min-height:160px;
    resize:none;
}

/* BUTTONS */
.btn-main,
.btn-lite{
    text-decoration:none;
    border-radius:12px;
    padding:11px 14px;
    font-size:13px;
    font-weight:700;
    display:inline-flex;
    align-items:center;
    justify-content:center;
    gap:8px;
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

/* TICKETS */
.ticket-item{
    border:1px solid #d8e0ea;
    border-radius:14px;
    padding:16px;
    background:#fff;
    margin-bottom:12px;
    box-shadow:0 4px 14px rgba(30, 53, 88, 0.04);
}

.ticket-item:last-child{
    margin-bottom:0;
}

.ticket-top{
    display:flex;
    justify-content:space-between;
    align-items:flex-start;
    gap:10px;
    margin-bottom:10px;
}

.ticket-title{
    font-weight:800;
    color:var(--blue-dark);
    margin-bottom:4px;
    font-size:15px;
    line-height:1.4;
}

.ticket-date{
    font-size:12px;
    color:#64748b;
}

.ticket-meta{
    display:flex;
    flex-wrap:wrap;
    gap:8px;
    margin-bottom:10px;
}

.meta-badge{
    display:inline-flex;
    align-items:center;
    gap:6px;
    padding:7px 11px;
    border-radius:999px;
    font-size:11px;
    font-weight:800;
}

.badge-open{ background:#eef4ff; color:#1d4ed8; }
.badge-progress{ background:#fff7ed; color:#c2410c; }
.badge-resolved{ background:#ecfdf5; color:#166534; }

.badge-high{ background:#fef2f2; color:#b91c1c; }
.badge-medium{ background:#fff7ed; color:#c2410c; }
.badge-low{ background:#f0fdf4; color:#15803d; }

.badge-category{
    background:#f8fafc;
    color:#475569;
}

.ticket-msg{
    color:#475569;
    font-size:14px;
    line-height:1.7;
    margin:0;
}

/* TIPS */
.tip-item{
    border:1px solid #e1e8f0;
    border-radius:14px;
    padding:14px;
    background:#fbfdff;
    margin-bottom:10px;
}

.tip-item:last-child{
    margin-bottom:0;
}

.tip-item strong{
    display:block;
    font-size:14px;
    color:var(--blue-dark);
    margin-bottom:6px;
}

.tip-item p{
    margin:0;
    color:var(--muted);
    font-size:13px;
    line-height:1.6;
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

.mobile-toggle{
    display:none;
}

.bottom-nav{
    display:none;
}

/* MOBILE */
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

    <aside class="sidebar" id="sidebar">
        <div class="brand-box">
            <h4><i class="fa-solid fa-graduation-cap me-2"></i> ECA CPD</h4>
            <small>Contractor Learning Portal</small>
        </div>

        <div class="menu-title">Main Menu</div>

        <a href="dashboard.php">
            <div class="left"><i class="fa-solid fa-house"></i> Dashboard</div>
        </a>

        <a href="my_courses.php">
            <div class="left"><i class="fa-solid fa-book-open"></i> My Courses</div>
        </a>

        <a href="certificates.php">
            <div class="left"><i class="fa-solid fa-certificate"></i> Certificates</div>
        </a>

        <a href="resources.php">
            <div class="left"><i class="fa-solid fa-folder-open"></i> Resources</div>
        </a>

        <a href="points_tracker.php">
            <div class="left"><i class="fa-solid fa-chart-line"></i> CPD Tracker</div>
        </a>

        <a href="support.php" class="active">
            <div class="left"><i class="fa-solid fa-headset"></i> Support</div>
            <span class="menu-badge"><?= (int)$open_count ?></span>
        </a>

        <a href="logout.php">
            <div class="left"><i class="fa-solid fa-right-from-bracket"></i> Logout</div>
        </a>
    </aside>

    <main class="main">
        <div class="topbar">
            <div class="topbar-left">
                <button class="mobile-toggle" onclick="toggleSidebar()">
                    <i class="fa-solid fa-bars"></i>
                </button>
                <div>
                    <h5>Support Center</h5>
                    <small>Submit support requests and track ticket progress</small>
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
                <h1>Support Center</h1>
            </div>

            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-top">
                        <div>
                            <span class="stat-label">My Tickets</span>
                            <div class="stat-value"><?= $total_tickets ?></div>
                            <div class="stat-sub">Support tickets submitted by you</div>
                        </div>
                        <div class="stat-icon blue"><i class="fa fa-ticket"></i></div>
                    </div>
                </div>

                <div class="stat-card">
                    <div class="stat-top">
                        <div>
                            <span class="stat-label">Open Tickets</span>
                            <div class="stat-value"><?= $open_count ?></div>
                            <div class="stat-sub">Pending or in progress requests</div>
                        </div>
                        <div class="stat-icon gold"><i class="fa fa-life-ring"></i></div>
                    </div>
                </div>

                <div class="stat-card">
                    <div class="stat-top">
                        <div>
                            <span class="stat-label">Resolved</span>
                            <div class="stat-value"><?= $resolved_count ?></div>
                            <div class="stat-sub">Tickets successfully closed</div>
                        </div>
                        <div class="stat-icon green"><i class="fa fa-circle-check"></i></div>
                    </div>
                </div>
            </div>

            <div class="row g-3">
                <div class="col-lg-7">
                    <div class="panel">
                        <div class="panel-header">Create Support Ticket</div>
                        <div class="panel-body">

                            <?php if($msg): ?>
                                <div class="alert-pro alert-success-pro">
                                    <i class="fa fa-circle-check me-1"></i> <?= $msg ?>
                                </div>
                            <?php endif; ?>

                            <?php if($err): ?>
                                <div class="alert-pro alert-danger-pro">
                                    <i class="fa fa-triangle-exclamation me-1"></i> <?= $err ?>
                                </div>
                            <?php endif; ?>

                            <form method="post">
                                <div class="mb-3">
                                    <label class="form-label">Subject</label>
                                    <input type="text" name="subject" class="form-control" placeholder="Enter support subject" required>
                                </div>

                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label">Category</label>
                                        <select name="category" class="form-select" required>
                                            <option value="">Select category</option>
                                            <option value="Portal Access">Portal Access</option>
                                            <option value="Course Issue">Course Issue</option>
                                            <option value="Certificate Issue">Certificate Issue</option>
                                            <option value="Payments">Payments</option>
                                            <option value="Resources">Resources</option>
                                            <option value="Technical Support">Technical Support</option>
                                            <option value="General Inquiry">General Inquiry</option>
                                        </select>
                                    </div>

                                    <div class="col-md-6 mb-3">
                                        <label class="form-label">Priority</label>
                                        <select name="priority" class="form-select" required>
                                            <option value="">Select priority</option>
                                            <option value="Low">Low</option>
                                            <option value="Medium">Medium</option>
                                            <option value="High">High</option>
                                        </select>
                                    </div>
                                </div>

                                <div class="mb-3">
                                    <label class="form-label">Message</label>
                                    <textarea name="message" class="form-control" placeholder="Describe your issue or support request..." required></textarea>
                                </div>

                                <button type="submit" name="submit_ticket" class="btn-main">
                                    <i class="fa fa-paper-plane"></i> Submit Ticket
                                </button>
                            </form>
                        </div>
                    </div>
                </div>

                <div class="col-lg-5">
                    <div class="panel mb-3">
                        <div class="panel-header">Recent Support Tickets</div>
                        <div class="panel-body">
                            <?php if($tickets && $total_tickets > 0): ?>
                                <?php while($row = $tickets->fetch_assoc()): ?>
                                    <?php
                                        $status = strtolower(trim($row['status']));
                                        $priority = strtolower(trim($row['priority']));

                                        $status_class = 'badge-open';
                                        if($status == 'in progress') $status_class = 'badge-progress';
                                        if($status == 'resolved' || $status == 'closed') $status_class = 'badge-resolved';

                                        $priority_class = 'badge-medium';
                                        if($priority == 'high') $priority_class = 'badge-high';
                                        if($priority == 'low') $priority_class = 'badge-low';
                                    ?>
                                    <div class="ticket-item">
                                        <div class="ticket-top">
                                            <div>
                                                <div class="ticket-title"><?= htmlspecialchars($row['subject']) ?></div>
                                                <div class="ticket-date">
                                                    <?= htmlspecialchars($row['ticket_no']) ?> • <?= date('d M Y h:i A', strtotime($row['created_at'])) ?>
                                                </div>
                                            </div>
                                        </div>

                                        <div class="ticket-meta">
                                            <span class="meta-badge <?= $status_class ?>">
                                                <i class="fa fa-circle"></i> <?= htmlspecialchars($row['status']) ?>
                                            </span>

                                            <span class="meta-badge <?= $priority_class ?>">
                                                <i class="fa fa-flag"></i> <?= htmlspecialchars($row['priority']) ?>
                                            </span>

                                            <span class="meta-badge badge-category">
                                                <i class="fa fa-layer-group"></i> <?= htmlspecialchars($row['category']) ?>
                                            </span>
                                        </div>

                                        <p class="ticket-msg"><?= nl2br(htmlspecialchars($row['message'])) ?></p>
                                    </div>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <div class="empty-box">
                                    <i class="fa fa-headset"></i>
                                    <h6>No support tickets yet</h6>
                                    <p>Your submitted support requests will appear here.</p>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>

                    <div class="panel">
                        <div class="panel-header">Support Tips</div>
                        <div class="panel-body">
                            <div class="tip-item">
                                <strong>Use a clear subject</strong>
                                <p>Mention the exact page, course, or issue for faster support.</p>
                            </div>

                            <div class="tip-item">
                                <strong>Choose the right category</strong>
                                <p>This helps route your ticket to the correct team quickly.</p>
                            </div>

                            <div class="tip-item">
                                <strong>Explain the problem fully</strong>
                                <p>Include steps, dates, certificate numbers, or course names where relevant.</p>
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

    <a href="support.php" class="active">
        <i class="fa fa-headset"></i>
        Support
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