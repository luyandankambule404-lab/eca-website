<?php
require_once "../auth1.php";
require_role('CONTRACTOR');
require_once "../config.php";

if ($conn instanceof mysqli) {
    cpd_ensure_feedback_table($conn);
}

$user_id    = (int)($_SESSION['user_id'] ?? 0);
$user_email = $_SESSION['email'] ?? '';
$user_name  = $_SESSION['full_name'] ?? explode('@', $user_email)[0];

$msg = "";
$err = "";

/* SUBMIT FEEDBACK */
if(isset($_POST['submit_feedback'])){
    $subject = trim($_POST['subject'] ?? '');
    $rating  = (int)($_POST['rating'] ?? 0);
    $message = trim($_POST['message'] ?? '');

    if($subject == "" || $rating < 1 || $rating > 5 || $message == ""){
        $err = "Please complete all feedback fields correctly.";
    }else{
        $stmt = $conn->prepare("
            INSERT INTO feedback (user_id, email, full_name, subject, rating, message, created_at)
            VALUES (?, ?, ?, ?, ?, ?, NOW())
        ");
        $stmt->bind_param("isssis", $user_id, $user_email, $user_name, $subject, $rating, $message);

        if($stmt->execute()){
            $msg = "Thank you. Your feedback has been submitted successfully.";
        }else{
            $err = "Failed to submit feedback. Please try again.";
        }
        $stmt->close();
    }
}

$my_feedback = false;
$total_feedback = 0;
$avg_rating = 0;

if ($conn instanceof mysqli && cpd_table_exists($conn, 'feedback')) {
    $feedback_stmt = $conn->prepare("
        SELECT *
        FROM feedback
        WHERE email = ?
        ORDER BY id DESC
        LIMIT 10
    ");
    if ($feedback_stmt) {
        $feedback_stmt->bind_param("s", $user_email);
        $feedback_stmt->execute();
        $my_feedback = $feedback_stmt->get_result();
        $total_feedback = $my_feedback ? $my_feedback->num_rows : 0;
    }

    $avg_stmt = $conn->prepare("
        SELECT AVG(rating) avg_rating
        FROM feedback
        WHERE email = ?
    ");
    if ($avg_stmt) {
        $avg_stmt->bind_param("s", $user_email);
        $avg_stmt->execute();
        $avg_q = $avg_stmt->get_result();
        if($avg_q && $avg_q->num_rows){
            $avg_rating = round((float)($avg_q->fetch_assoc()['avg_rating'] ?? 0), 1);
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Feedback</title>

<link href="https://fonts.googleapis.com/css2?family=Manrope:wght@400;500;600;700;800&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">

<style>
:root{
    --primary:#1a2440;
    --primary-dark:#1a2440;
    --primary-light:#eef4fa;
    --bg:#f7f9fc;
    --card:#ffffff;
    --text:#1a2440;
    --muted:#64748b;
    --border:#dbe3ec;
    --shadow:0 8px 24px rgba(15,23,42,.06);
    --shadow-soft:0 4px 14px rgba(15,23,42,.04);
    --success:#15803d;
    --warning:#b7791f;
    --danger:#b42318;
    --star:#f59e0b;
}
*{box-sizing:border-box;}
html,body{
    margin:0;
    padding:0;
    font-family:'Manrope',sans-serif;
    background:var(--bg);
    color:var(--text);
    overflow-x:hidden;
}

/* SIDEBAR */
.sidebar{
    position:fixed;
    top:0;
    left:0;
    width:250px;
    height:100vh;
    z-index:1200;
    background:#fff;
    border-right:1px solid var(--border);
    padding:18px 14px;
    overflow-y:auto;
    transition:.3s ease;
}
.brand-box{
    padding:14px;
    border-radius:14px;
    background:var(--primary-light);
    margin-bottom:18px;
    border:1px solid #dde8f3;
}
.brand-top{
    display:flex;
    align-items:center;
    gap:12px;
}
.brand-icon{
    width:44px;
    height:44px;
    border-radius:12px;
    background:var(--primary);
    color:#fff;
    display:flex;
    align-items:center;
    justify-content:center;
    font-size:18px;
}
.brand-text h4{
    margin:0;
    font-size:17px;
    font-weight:800;
    color:var(--primary-dark);
}
.brand-text small{
    color:var(--muted);
    font-size:12px;
}
.menu-title{
    font-size:11px;
    text-transform:uppercase;
    letter-spacing:1px;
    color:var(--muted);
    padding:8px 10px;
    font-weight:700;
}
.sidebar a{
    display:flex;
    align-items:center;
    justify-content:space-between;
    gap:10px;
    padding:12px 12px;
    margin-bottom:6px;
    color:var(--text);
    text-decoration:none;
    border-radius:12px;
    transition:.25s ease;
    font-size:14px;
    font-weight:600;
}
.sidebar a .left{
    display:flex;
    align-items:center;
    gap:10px;
}
.sidebar a i{
    width:18px;
    text-align:center;
    color:var(--primary);
}
.sidebar a:hover,
.sidebar a.active{
    background:var(--primary);
    color:#fff;
}
.sidebar a:hover i,
.sidebar a.active i{
    color:#fff;
}
.menu-badge{
    min-width:22px;
    height:22px;
    padding:0 7px;
    border-radius:999px;
    background:rgba(255,255,255,.18);
    color:inherit;
    font-size:11px;
    display:flex;
    align-items:center;
    justify-content:center;
    font-weight:700;
}

/* HEADER */
.header{
    position:fixed;
    top:0;
    left:250px;
    right:0;
    height:74px;
    z-index:1100;
    background:rgba(255,255,255,.95);
    border-bottom:1px solid var(--border);
    display:flex;
    align-items:center;
    justify-content:space-between;
    padding:0 22px;
    backdrop-filter:blur(10px);
}
.header-left h3{
    margin:0;
    font-size:20px;
    font-weight:800;
    color:var(--primary-dark);
}
.header-left small{
    color:var(--muted);
    font-size:13px;
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
    color:var(--primary);
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
    background:var(--primary);
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
    background:#fff;
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
    color:var(--text);
}
.user-meta span{
    display:block;
    font-size:12px;
    color:var(--muted);
}

.mobile-header{display:none;}

/* CONTENT */
.content{
    margin-left:250px;
    padding:94px 20px 24px;
    min-height:100vh;
}

/* CARDS */
.simple-card{
    background:#fff;
    border:1px solid var(--border);
    border-radius:16px;
    padding:16px;
    box-shadow:var(--shadow-soft);
}
.stat-card{
    background:#fff;
    border:1px solid var(--border);
    border-radius:16px;
    padding:18px;
    box-shadow:var(--shadow-soft);
    height:100%;
    transition:.2s ease;
}
.stat-card:hover{ transform:translateY(-2px); }
.stat-top{
    display:flex;
    justify-content:space-between;
    align-items:flex-start;
    gap:12px;
}
.stat-label{
    display:block;
    font-size:12px;
    font-weight:700;
    color:var(--muted);
    margin-bottom:8px;
    text-transform:uppercase;
    letter-spacing:.4px;
}
.stat-value{
    font-size:26px;
    font-weight:800;
    line-height:1.1;
    color:var(--primary-dark);
    margin-bottom:4px;
}
.stat-sub{
    font-size:12px;
    color:var(--muted);
}
.stat-icon{
    width:42px;
    height:42px;
    border-radius:12px;
    background:var(--primary-light);
    color:var(--primary);
    display:flex;
    align-items:center;
    justify-content:center;
    font-size:18px;
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
    color:var(--primary-dark);
}

/* ALERTS */
.alert-pro{
    padding:14px 16px;
    border-radius:12px;
    margin-bottom:18px;
    font-weight:600;
    border:1px solid transparent;
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
}
.form-control:focus,
.form-select:focus{
    border-color:#94a3b8;
    box-shadow:0 0 0 .18rem rgba(26,36,64,.08);
}
textarea.form-control{
    min-height:140px;
    resize:none;
}

/* RATING */
.rating-grid{
    display:grid;
    grid-template-columns:repeat(5, 1fr);
    gap:12px;
    margin-bottom:18px;
}
.rating-option input{ display:none; }
.rating-card{
    border:1px solid var(--border);
    background:#fff;
    border-radius:14px;
    padding:16px 10px;
    text-align:center;
    cursor:pointer;
    transition:.2s ease;
    height:100%;
}
.rating-card i{
    font-size:18px;
    color:var(--star);
    margin-bottom:8px;
}
.rating-card strong{
    display:block;
    font-size:16px;
    color:#0f172a;
}
.rating-card span{
    display:block;
    font-size:11px;
    color:#64748b;
    margin-top:3px;
}
.rating-option input:checked + .rating-card{
    background:var(--primary);
    border-color:var(--primary);
    box-shadow:0 12px 22px rgba(26,36,64,.14);
}
.rating-option input:checked + .rating-card strong,
.rating-option input:checked + .rating-card span,
.rating-option input:checked + .rating-card i{
    color:#fff;
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
    background:var(--primary);
    color:#fff;
    border:1px solid var(--primary);
}
.btn-main:hover{
    background:#24314f;
    color:#fff;
}
.btn-lite{
    background:#fff;
    color:var(--primary);
    border:1px solid #bcc9d8;
}
.btn-lite:hover{
    background:#f8fbff;
    color:var(--primary);
}

/* FEEDBACK ITEMS */
.feedback-item{
    border:1px solid var(--border);
    border-radius:14px;
    padding:16px;
    background:#fff;
    margin-bottom:12px;
}
.feedback-top{
    display:flex;
    justify-content:space-between;
    align-items:flex-start;
    gap:10px;
    margin-bottom:10px;
}
.feedback-title{
    font-weight:800;
    color:#0f172a;
    margin-bottom:4px;
    font-size:14px;
}
.feedback-date{
    font-size:12px;
    color:#64748b;
}
.feedback-stars{
    color:var(--star);
    font-size:14px;
    white-space:nowrap;
}
.feedback-message{
    color:#475569;
    font-size:14px;
    line-height:1.7;
    margin:0;
}

/* TIP BOX */
.tip-item{
    border:1px solid var(--border);
    border-radius:14px;
    padding:14px;
    background:#fbfdff;
    margin-bottom:10px;
}
.tip-item:last-child{ margin-bottom:0; }
.tip-item strong{
    display:block;
    font-size:14px;
    color:var(--primary-dark);
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

/* BOTTOM NAV */
.bottom-nav{display:none;}

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
        color:var(--primary-dark);
    }
    .content{
        margin-left:0;
        padding:78px 14px 84px;
    }
    .rating-grid{
        grid-template-columns:repeat(2, 1fr);
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
        color:var(--primary);
    }
}
</style>
<?php require __DIR__ . '/_hub_css.php'; ?>
</head>
<body class="hub-root">

<div class="mobile-header d-lg-none">
    <i class="fa fa-bars" onclick="toggleSidebar()" style="font-size:20px;cursor:pointer;"></i>
    <div style="font-weight:800;font-size:16px;">Feedback</div>
    <div style="position:relative;">
        <i class="fa fa-bell" style="font-size:18px;"></i>
        <span style="position:absolute;top:-5px;right:-8px;background:var(--primary);color:#fff;font-size:9px;width:15px;height:15px;display:flex;align-items:center;justify-content:center;border-radius:50%;">3</span>
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

    <a href="/index.php">
        <div class="left"><i class="fa fa-globe"></i> ECA home</div>
    </a>
    <a href="logout.php">
        <div class="left"><i class="fa fa-right-from-bracket"></i> Logout</div>
    </a>
</div>

<div class="header">
    <div class="header-left">
        <h3>Feedback Center</h3>
        <small>Share your experience and help improve the CPD portal</small>
    </div>

    <div class="header-right">
        <button class="icon-btn">
            <i class="fa fa-bell"></i>
            <span class="notify-badge">3</span>
        </button>

        <div class="user-box">
            <img
                class="user-avatar"
                src="https://ui-avatars.com/api/?name=<?=urlencode($user_name)?>&background=1a2440&color=fff&rounded=true&bold=true&size=128"
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

    <div class="row g-3 mb-3">
        <div class="col-md-4">
            <div class="stat-card">
                <div class="stat-top">
                    <div>
                        <span class="stat-label">My Feedback</span>
                        <div class="stat-value"><?=$total_feedback?></div>
                        <div class="stat-sub">Entries submitted from your account</div>
                    </div>
                    <div class="stat-icon"><i class="fa fa-comments"></i></div>
                </div>
            </div>
        </div>

        <div class="col-md-4">
            <div class="stat-card">
                <div class="stat-top">
                    <div>
                        <span class="stat-label">Average Rating</span>
                        <div class="stat-value"><?=$avg_rating ?: '0.0'?></div>
                        <div class="stat-sub">Your average submitted score</div>
                    </div>
                    <div class="stat-icon"><i class="fa fa-star"></i></div>
                </div>
            </div>
        </div>

        <div class="col-md-4">
            <div class="stat-card">
                <div class="stat-top">
                    <div>
                        <span class="stat-label">Support Status</span>
                        <div class="stat-value">OPEN</div>
                        <div class="stat-sub">Feedback channel is available</div>
                    </div>
                    <div class="stat-icon"><i class="fa fa-headset"></i></div>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-3">
        <div class="col-lg-7">
            <div class="simple-card">
                <div class="card-title-row">
                    <h5>Submit Feedback</h5>
                </div>

                <?php if($msg): ?>
                    <div class="alert-pro alert-success-pro">
                        <i class="fa fa-circle-check"></i> <?=$msg?>
                    </div>
                <?php endif; ?>

                <?php if($err): ?>
                    <div class="alert-pro alert-danger-pro">
                        <i class="fa fa-triangle-exclamation"></i> <?=$err?>
                    </div>
                <?php endif; ?>

                <form method="post">
                    <div class="mb-3">
                        <label class="form-label">Feedback Subject</label>
                        <select name="subject" class="form-select" required>
                            <option value="">Select subject</option>
                            <option value="Portal Experience">Portal Experience</option>
                            <option value="Training Quality">Training Quality</option>
                            <option value="Course Content">Course Content</option>
                            <option value="Certificates">Certificates</option>
                            <option value="Support Service">Support Service</option>
                            <option value="System Improvement">System Improvement</option>
                        </select>
                    </div>

                    <label class="form-label">Rate Your Experience</label>
                    <div class="rating-grid">
                        <label class="rating-option">
                            <input type="radio" name="rating" value="1" required>
                            <div class="rating-card">
                                <i class="fa fa-star"></i>
                                <strong>1</strong>
                                <span>Poor</span>
                            </div>
                        </label>

                        <label class="rating-option">
                            <input type="radio" name="rating" value="2">
                            <div class="rating-card">
                                <i class="fa fa-star"></i>
                                <strong>2</strong>
                                <span>Fair</span>
                            </div>
                        </label>

                        <label class="rating-option">
                            <input type="radio" name="rating" value="3">
                            <div class="rating-card">
                                <i class="fa fa-star"></i>
                                <strong>3</strong>
                                <span>Good</span>
                            </div>
                        </label>

                        <label class="rating-option">
                            <input type="radio" name="rating" value="4">
                            <div class="rating-card">
                                <i class="fa fa-star"></i>
                                <strong>4</strong>
                                <span>Very Good</span>
                            </div>
                        </label>

                        <label class="rating-option">
                            <input type="radio" name="rating" value="5">
                            <div class="rating-card">
                                <i class="fa fa-star"></i>
                                <strong>5</strong>
                                <span>Excellent</span>
                            </div>
                        </label>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Your Comments</label>
                        <textarea name="message" class="form-control" placeholder="Share your feedback, suggestions, or experience..." required></textarea>
                    </div>

                    <button type="submit" name="submit_feedback" class="btn-main">
                        <i class="fa fa-paper-plane"></i> Submit Feedback
                    </button>
                </form>
            </div>
        </div>

        <div class="col-lg-5">
            <div class="simple-card">
                <div class="card-title-row">
                    <h6>Recent Feedback</h6>
                </div>

                <?php if($my_feedback && $total_feedback > 0): ?>
                    <?php while($row = $my_feedback->fetch_assoc()): ?>
                        <div class="feedback-item">
                            <div class="feedback-top">
                                <div>
                                    <div class="feedback-title"><?=htmlspecialchars($row['subject'])?></div>
                                    <div class="feedback-date">
                                        <?=date('d M Y h:i A', strtotime($row['created_at']))?>
                                    </div>
                                </div>

                                <div class="feedback-stars">
                                    <?php for($i=1; $i<=5; $i++): ?>
                                        <i class="fa <?=($i <= (int)$row['rating']) ? 'fa-star' : 'fa-regular fa-star'?>"></i>
                                    <?php endfor; ?>
                                </div>
                            </div>

                            <p class="feedback-message"><?=nl2br(htmlspecialchars($row['message']))?></p>
                        </div>
                    <?php endwhile; ?>
                <?php else: ?>
                    <div class="empty-box">
                        <i class="fa fa-comment-dots"></i>
                        <h6>No feedback submitted yet</h6>
                        <p>Your submitted feedback history will appear here.</p>
                    </div>
                <?php endif; ?>
            </div>

            <div class="simple-card mt-3">
                <div class="card-title-row">
                    <h6>Feedback Tips</h6>
                </div>

                <div class="tip-item">
                    <strong>Be specific</strong>
                    <p>Mention the exact course, page, or issue so the team can improve it faster.</p>
                </div>

                <div class="tip-item">
                    <strong>Share suggestions</strong>
                    <p>Suggestions on portal design, training flow, certificates, and resources are welcome.</p>
                </div>

                <div class="tip-item">
                    <strong>Rate honestly</strong>
                    <p>Your score helps track user satisfaction and identify areas for improvement.</p>
                </div>
            </div>
        </div>
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

    <a href="certificates.php">
        <i class="fa fa-certificate"></i>
        Certs
    </a>

    <a href="feedback.php" class="active">
        <i class="fa fa-message"></i>
        Feedback
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
        if(sidebar.classList.contains('show') && !sidebar.contains(e.target) && !mobileToggle.contains(e.target)){
            sidebar.classList.remove('show');
        }
    }
});
</script>

</body>
</html>