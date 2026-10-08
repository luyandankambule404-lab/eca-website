<?php
require_once "../auth.php";
require_role('CONTRACTOR');
require_once "../config.php";

$user_id    = (int)($_SESSION['user_id'] ?? 0);
$user_email = $_SESSION['email'] ?? '';
$user_name  = $_SESSION['full_name'] ?? explode('@', $user_email)[0];

$course_id = (int)($_GET['course_id'] ?? 0);
$app_id    = (int)($_GET['application_id'] ?? 0);

$msg = "";

/* ===============================
GET COURSE
================================ */
$course_stmt = $conn->prepare("
SELECT *
FROM courses
WHERE id=?
LIMIT 1
");
$course_stmt->bind_param("i", $course_id);
$course_stmt->execute();
$course = $course_stmt->get_result()->fetch_assoc();

/* ===============================
GET APPLICATION
================================ */
$app_stmt = $conn->prepare("
SELECT *
FROM cpd_applications
WHERE id=?
AND email=?
LIMIT 1
");
$app_stmt->bind_param("is", $app_id, $user_email);
$app_stmt->execute();
$app = $app_stmt->get_result()->fetch_assoc();

/* ===============================
STOP IF INVALID
================================ */
if(!$course || !$app){
    die("Invalid course or application.");
}

/* ===============================
SUBMIT ACTIVITY
================================ */
if(isset($_POST['submit_activity'])){

    $q1 = trim($_POST['q1'] ?? '');
    $q2 = trim($_POST['q2'] ?? '');
    $q3 = trim($_POST['q3'] ?? '');
    $q4 = trim($_POST['q4'] ?? '');

    $stmt = $conn->prepare("
        INSERT INTO course_activity
        (course_id,application_id,q1,q2,q3,q4,submitted_at)
        VALUES(?,?,?,?,?,?,NOW())
    ");

    $stmt->bind_param(
        "iissss",
        $course_id,
        $app_id,
        $q1,
        $q2,
        $q3,
        $q4
    );

    if($stmt->execute()){

        $update_stmt = $conn->prepare("
            UPDATE cpd_applications
            SET training_status='Completed'
            WHERE id=?
        ");
        $update_stmt->bind_param("i", $app_id);
        $update_stmt->execute();

        $points      = (float)($course['points'] ?? 0);
        $courseTitle = $course['title'] ?? 'Course';

        $check_stmt = $conn->prepare("
            SELECT id
            FROM cpd_points_ledger
            WHERE user_id=?
            AND course_id=?
            LIMIT 1
        ");
        $check_stmt->bind_param("ii", $user_id, $course_id);
        $check_stmt->execute();
        $check = $check_stmt->get_result();

        if(!$check->num_rows && $points > 0){

            $reason = "Course Completion";
            $description = "Completed: " . $courseTitle;

            $stmt2 = $conn->prepare("
                INSERT INTO cpd_points_ledger
                (user_id,course_id,points,reason,created_at,issued_at,description)
                VALUES(?,?,?, ?,NOW(),NOW(),?)
            ");

            $stmt2->bind_param(
                "iidss",
                $user_id,
                $course_id,
                $points,
                $reason,
                $description
            );

            $stmt2->execute();
        }

        header("Location: dashboard.php?activity=success");
        exit;

    }else{
        $msg = "Failed to submit activity. Please try again.";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Training Activity</title>

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

body{
    padding-bottom:90px;
}

/* TOP HEADER */
.topbar{
    background:#fff;
    border-bottom:1px solid var(--border);
    position:sticky;
    top:0;
    z-index:1000;
    height:70px;
    display:flex;
    align-items:center;
    justify-content:space-between;
    padding:0 16px;
}

.topbar-left{
    display:flex;
    align-items:center;
    gap:12px;
}

.back-btn{
    width:42px;
    height:42px;
    border-radius:12px;
    border:1px solid var(--border);
    background:#fff;
    color:var(--primary);
    display:flex;
    align-items:center;
    justify-content:center;
    text-decoration:none;
    box-shadow:var(--shadow-soft);
}

.topbar-title h5{
    margin:0;
    font-size:17px;
    font-weight:800;
    color:var(--primary-dark);
}

.topbar-title span{
    display:block;
    font-size:12px;
    color:var(--muted);
}

.topbar-right{
    display:flex;
    align-items:center;
    gap:10px;
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

/* PAGE */
.activity-page{
    max-width:920px;
    margin:0 auto;
    padding:20px 16px 24px;
}

/* HERO CARD */
.hero-card{
    background:#fff;
    border:1px solid var(--border);
    border-radius:20px;
    box-shadow:var(--shadow-soft);
    padding:20px;
    margin-bottom:18px;
}

.hero-top{
    display:flex;
    align-items:flex-start;
    justify-content:space-between;
    gap:16px;
    flex-wrap:wrap;
}

.hero-left{
    display:flex;
    align-items:flex-start;
    gap:14px;
}

.hero-icon{
    width:60px;
    height:60px;
    border-radius:16px;
    background:var(--primary-light);
    color:var(--primary);
    display:flex;
    align-items:center;
    justify-content:center;
    font-size:24px;
    flex-shrink:0;
}

.hero-meta h4{
    margin:0 0 6px;
    font-size:22px;
    font-weight:800;
    color:var(--primary-dark);
}

.hero-meta p{
    margin:0 0 10px;
    color:var(--muted);
    font-size:14px;
    line-height:1.6;
}

.course-chip{
    display:inline-flex;
    align-items:center;
    gap:8px;
    padding:8px 14px;
    border-radius:999px;
    background:var(--primary-light);
    color:var(--primary);
    font-weight:700;
    font-size:12px;
}

.hero-side{
    min-width:190px;
}

.side-box{
    background:#f8fbff;
    border:1px solid #e4edf7;
    border-radius:16px;
    padding:14px;
}

.side-box span{
    display:block;
    font-size:11px;
    text-transform:uppercase;
    letter-spacing:.4px;
    color:var(--muted);
    font-weight:800;
    margin-bottom:6px;
}

.side-box strong{
    display:block;
    font-size:22px;
    color:var(--primary-dark);
    font-weight:800;
    margin-bottom:4px;
}

.side-box small{
    color:var(--muted);
    font-size:12px;
}

.progress-wrap{
    margin-top:16px;
}

.progress-head{
    display:flex;
    justify-content:space-between;
    align-items:center;
    margin-bottom:8px;
    font-size:13px;
    font-weight:700;
    color:#475569;
}

.progress{
    height:10px;
    background:#e8eef5;
    border-radius:999px;
    overflow:hidden;
}

.progress-bar-custom{
    height:10px;
    width:70%;
    background:var(--primary);
    border-radius:999px;
}

/* ALERT */
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

/* MAIN CARD */
.activity-card{
    background:#fff;
    border:1px solid var(--border);
    border-radius:20px;
    box-shadow:var(--shadow-soft);
    padding:20px;
    margin-bottom:18px;
}

.card-title-row{
    display:flex;
    justify-content:space-between;
    align-items:center;
    gap:10px;
    margin-bottom:16px;
    flex-wrap:wrap;
}

.card-title-row h5{
    margin:0;
    font-size:17px;
    font-weight:800;
    color:var(--primary-dark);
}

.card-title-row span{
    font-size:13px;
    color:var(--muted);
    font-weight:700;
}

/* FORM */
.question-block{
    margin-bottom:18px;
}

.question-block label{
    display:block;
    font-weight:700;
    font-size:14px;
    margin-bottom:8px;
    color:#334155;
}

textarea,
select{
    width:100%;
    border:1px solid var(--border);
    border-radius:14px;
    padding:13px 14px;
    font-size:14px;
    font-family:'Manrope',sans-serif;
    background:#fff;
    transition:.2s ease;
    color:var(--text);
}

textarea{
    min-height:120px;
    resize:none;
}

textarea:focus,
select:focus{
    border-color:#94a3b8;
    outline:none;
    box-shadow:0 0 0 .18rem rgba(26,36,64,.08);
}

/* BUTTON */
.btn-submit{
    width:100%;
    background:var(--primary);
    border:1px solid var(--primary);
    padding:14px 16px;
    border-radius:14px;
    color:#fff;
    font-weight:700;
    font-size:15px;
    transition:.2s ease;
}

.btn-submit:hover{
    background:#24314f;
    color:#fff;
}

/* LOCK CARD */
.certificate-lock{
    background:#fff;
    border:1px solid var(--border);
    border-radius:20px;
    padding:18px;
    display:flex;
    align-items:flex-start;
    gap:14px;
    box-shadow:var(--shadow-soft);
}

.lock-icon{
    width:52px;
    height:52px;
    border-radius:14px;
    background:#fef2f2;
    color:var(--danger);
    display:flex;
    align-items:center;
    justify-content:center;
    font-size:20px;
    flex-shrink:0;
}

.certificate-lock h6{
    margin:0 0 6px;
    font-size:15px;
    font-weight:800;
    color:var(--primary-dark);
}

.certificate-lock p{
    margin:0;
    line-height:1.7;
    color:var(--muted);
    font-size:14px;
}

/* BOTTOM NAV */
.bottom-nav{
    position:fixed;
    left:0;
    right:0;
    bottom:0;
    z-index:1200;
    background:#fff;
    border-top:1px solid var(--border);
    padding:8px 4px calc(8px + env(safe-area-inset-bottom));
    display:flex;
    justify-content:space-around;
}

.nav-item{
    display:flex;
    flex-direction:column;
    align-items:center;
    justify-content:center;
    text-decoration:none;
    font-size:11px;
    color:var(--muted);
    width:25%;
    font-weight:700;
}

.nav-item i{
    font-size:17px;
    margin-bottom:4px;
}

.nav-item.active{
    color:var(--primary);
}

@media(max-width:768px){
    .hero-top{
        flex-direction:column;
    }

    .hero-left{
        align-items:flex-start;
    }

    .hero-side{
        width:100%;
        min-width:unset;
    }

    .hero-meta h4{
        font-size:20px;
    }

    .certificate-lock{
        flex-direction:row;
    }
}
</style>
</head>
<body>

<!-- TOP HEADER -->
<div class="topbar">
    <div class="topbar-left">
        <a href="my_courses.php" class="back-btn">
            <i class="fa fa-arrow-left"></i>
        </a>
        <div class="topbar-title">
            <h5>Training Activity</h5>
            <span>Course completion form</span>
        </div>
    </div>

    <div class="topbar-right">
        <a href="/index.php" style="color:#0f172a;font-weight:800;text-decoration:none;margin-right:12px;">Website</a>
        <button class="icon-btn" type="button">
            <i class="fa fa-bell"></i>
            <span class="notify-badge">2</span>
        </button>
    </div>
</div>

<div class="activity-page">

    <div class="hero-card">
        <div class="hero-top">
            <div class="hero-left">
                <div class="hero-icon">
                    <i class="fa fa-certificate"></i>
                </div>

                <div class="hero-meta">
                    <div class="course-chip">
                        <i class="fa fa-book-open"></i>
                        <?= htmlspecialchars($course['title'] ?? 'Course Activity') ?>
                    </div>

                    <h4>Complete Your Training Activity</h4>
                    <p>
                        Submit this activity to complete your training, unlock your certificate,
                        and award your CPD points.
                    </p>
                </div>
            </div>

            <div class="hero-side">
                <div class="side-box">
                    <span>CPD Points</span>
                    <strong><?= number_format((float)($course['points'] ?? 0), 1) ?></strong>
                    <small>Points available after submission</small>
                </div>
            </div>
        </div>

        <div class="progress-wrap">
            <div class="progress-head">
                <span>Completion Progress</span>
                <span>0%</span>
            </div>
            <div class="progress">
                <div class="progress-bar-custom"></div>
            </div>
        </div>
    </div>

    <?php if($msg): ?>
        <div class="alert-pro alert-success-pro">
            <i class="fa fa-circle-check"></i> <?= htmlspecialchars($msg) ?>
        </div>
    <?php endif; ?>

    <div class="activity-card">
        <div class="card-title-row">
            <h5>Activity Questions</h5>
            <span>All required fields must be completed</span>
        </div>

        <form method="POST">

            <div class="question-block">
                <label>1. What did you learn from this training?</label>
                <textarea name="q1" required placeholder="Write what you learned from this training..."></textarea>
            </div>

            <div class="question-block">
                <label>2. How will you apply this knowledge in your work?</label>
                <textarea name="q2" required placeholder="Explain how you will apply it in your work..."></textarea>
            </div>

            <div class="question-block">
                <label>3. Rate the training quality</label>
                <select name="q3" required>
                    <option value="Excellent">Excellent</option>
                    <option value="Good">Good</option>
                    <option value="Average">Average</option>
                    <option value="Poor">Poor</option>
                </select>
            </div>

            <div class="question-block">
                <label>4. Suggestions for improvement</label>
                <textarea name="q4" placeholder="Share suggestions for improving future training..."></textarea>
            </div>

            <button class="btn-submit" name="submit_activity" type="submit">
                <i class="fa fa-check-circle"></i> Submit Activity
            </button>
        </form>
    </div>

    <div class="certificate-lock">
        <div class="lock-icon">
            <i class="fa fa-lock"></i>
        </div>
        <div>
            <h6>Certificate Locked Until Submission</h6>
            <p>Your certificate and CPD points will unlock once the activity is submitted successfully.</p>
        </div>
    </div>

</div>

<div class="bottom-nav">
    <a href="dashboard.php" class="nav-item">
        <i class="fa fa-house"></i>
        <span>Home</span>
    </a>

    <a href="my_courses.php" class="nav-item active">
        <i class="fa fa-book"></i>
        <span>Courses</span>
    </a>

    <a href="certificates.php" class="nav-item">
        <i class="fa fa-certificate"></i>
        <span>Certs</span>
    </a>

    <a href="account.php" class="nav-item">
        <i class="fa fa-user"></i>
        <span>Account</span>
    </a>
</div>

</body>
</html>