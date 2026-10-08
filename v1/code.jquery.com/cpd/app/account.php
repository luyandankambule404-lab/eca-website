<?php
require_once "../auth1.php";
require_role('CONTRACTOR');
require_once "../config.php";

$user_id    = (int) ($_SESSION['user_id'] ?? 0);
$user_email = $_SESSION['email'] ?? '';
$user_name  = $_SESSION['full_name'] ?? 'Contractor';

$userStmt = $conn->prepare("
    SELECT id, full_name, email, role, status, image, password_hash
    FROM user
    WHERE id = ?
    LIMIT 1
");
$userStmt->bind_param("i", $user_id);
$userStmt->execute();
$user = $userStmt->get_result()->fetch_assoc();
$userStmt->close();

if (!$user) {
    eca_destroy_auth_session();
    redirect("/login.php");
}

$msg = "";
$msg_type = "success";

/* ===============================
UPDATE PROFILE
================================ */
if(isset($_POST['update_profile'])){
    cpd_require_csrf();

    $name  = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');

    if($name === '' || $email === ''){
        $msg = "Please fill in all profile fields.";
        $msg_type = "danger";
    } elseif (mb_strlen($name) > 120 || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $msg = "Please enter a valid name and email address.";
        $msg_type = "danger";
    } else {
        $profileStmt = $conn->prepare("
            UPDATE user
            SET full_name = ?, email = ?
            WHERE id = ?
            LIMIT 1
        ");
        $profileStmt->bind_param("ssi", $name, $email, $user_id);
        if ($profileStmt->execute()) {
            $_SESSION['email'] = $email;
            $_SESSION['full_name'] = $name;
            $user['email'] = $email;
            $user['full_name'] = $name;
            $msg = "Profile updated successfully";
            $msg_type = "success";
        } else {
            $msg = "The profile could not be updated. Please try again.";
            $msg_type = "danger";
        }
        $profileStmt->close();
    }
}

/* ===============================
CHANGE PASSWORD
================================ */
if(isset($_POST['change_password'])){
    cpd_require_csrf();

    $current = $_POST['current_password'] ?? '';
    $new     = $_POST['new_password'] ?? '';

    $passwordStmt = $conn->prepare("
        SELECT password_hash
        FROM user
        WHERE id = ?
        LIMIT 1
    ");
    $passwordStmt->bind_param("i", $user_id);
    $passwordStmt->execute();
    $row = $passwordStmt->get_result()->fetch_assoc();
    $passwordStmt->close();

    if (mb_strlen($new) < 10) {
        $msg = "The new password must contain at least 10 characters.";
        $msg_type = "danger";
    } elseif($row && password_verify($current, (string) $row['password_hash'])){

        $newHash = password_hash($new, PASSWORD_DEFAULT);

        $updatePassword = $conn->prepare("
            UPDATE user
            SET password_hash = ?
            WHERE id = ?
            LIMIT 1
        ");
        $updatePassword->bind_param("si", $newHash, $user_id);
        $updatePassword->execute();
        $updatePassword->close();

        $msg = "Password updated successfully";
        $msg_type = "success";

    }else{
        $msg = "Current password incorrect";
        $msg_type = "danger";
    }
}

/* ===============================
UPLOAD PROFILE IMAGE
================================ */
if(isset($_POST['upload_image'])){
    cpd_require_csrf();

    $upload = $_FILES['image'] ?? null;
    if ($upload && ($upload['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_OK) {
        $tmpPath = (string) ($upload['tmp_name'] ?? '');
        $size = (int) ($upload['size'] ?? 0);
        $finfo = new finfo(FILEINFO_MIME_TYPE);
        $mime = $tmpPath !== '' ? (string) $finfo->file($tmpPath) : '';
        $allowed = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp'];

        if ($size < 1 || $size > 2 * 1024 * 1024 || !isset($allowed[$mime]) || @getimagesize($tmpPath) === false) {
            $msg = "Choose a valid JPG, PNG or WebP image smaller than 2 MB.";
            $msg_type = "danger";
        } else {
            $storageDir = dirname(__DIR__, 4) . DIRECTORY_SEPARATOR . '_private' . DIRECTORY_SEPARATOR . 'cpd-profile';
            if (!is_dir($storageDir) && !mkdir($storageDir, 0700, true) && !is_dir($storageDir)) {
                $msg = "Profile image storage is unavailable.";
                $msg_type = "danger";
            } else {
                $fileName = bin2hex(random_bytes(18)) . '.' . $allowed[$mime];
                $fullPath = $storageDir . DIRECTORY_SEPARATOR . $fileName;
                if (move_uploaded_file($tmpPath, $fullPath)) {
                    $storedPath = 'private/' . $fileName;
                    $imageStmt = $conn->prepare("UPDATE user SET image = ? WHERE id = ? LIMIT 1");
                    $imageStmt->bind_param("si", $storedPath, $user_id);
                    $imageStmt->execute();
                    $imageStmt->close();
                    $user['image'] = $storedPath;
                    $msg = "Profile image updated successfully";
                    $msg_type = "success";
                } else {
                    $msg = "Failed to upload image";
                    $msg_type = "danger";
                }
            }
        }
    }else{
        $msg = "Please choose an image first.";
        $msg_type = "danger";
    }
}

$storedImage = (string) ($user['image'] ?? '');
$avatar = str_starts_with($storedImage, 'private/')
    ? 'profile-image.php'
    : ($storedImage !== ''
        ? "../" . ltrim($storedImage, '/')
        : 'profile-image.php');
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">

<title>My Account</title>

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
.user-avatar-small{
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

/* MOBILE HEADER */
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

/* PROFILE PANEL */
.profile-main{
    text-align:center;
}
.profile-avatar{
    width:118px;
    height:118px;
    border-radius:28px;
    object-fit:cover;
    border:4px solid #fff;
    box-shadow:0 10px 22px rgba(26,36,64,.10);
    margin-bottom:14px;
}
.profile-name{
    font-size:22px;
    font-weight:800;
    color:#0f172a;
    margin-bottom:4px;
}
.profile-email{
    color:#64748b;
    font-size:14px;
    margin-bottom:16px;
    word-break:break-word;
}
.profile-chip-wrap{
    display:flex;
    justify-content:center;
    flex-wrap:wrap;
    gap:10px;
    margin-bottom:18px;
}
.profile-chip{
    background:var(--primary-light);
    color:var(--primary);
    border-radius:999px;
    padding:8px 13px;
    font-size:12px;
    font-weight:700;
}

/* FORM */
.form-label{
    font-weight:700;
    color:#334155;
    margin-bottom:8px;
    font-size:13px;
}
.form-control{
    border-radius:12px;
    min-height:48px;
    border:1px solid var(--border);
    box-shadow:none;
    padding:12px 14px;
}
.form-control:focus{
    border-color:#94a3b8;
    box-shadow:0 0 0 .18rem rgba(26,36,64,.08);
}
input[type="file"].form-control{
    padding:12px;
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

/* INFO ROW */
.info-strip{
    display:grid;
    grid-template-columns:repeat(2, minmax(0,1fr));
    gap:12px;
    margin-top:14px;
}
.info-mini{
    background:#f8fbff;
    border:1px solid #e4edf7;
    border-radius:12px;
    padding:12px;
    text-align:left;
}
.info-mini span{
    display:block;
    font-size:11px;
    text-transform:uppercase;
    color:var(--muted);
    font-weight:800;
    margin-bottom:4px;
}
.info-mini strong{
    display:block;
    font-size:13px;
    color:var(--text);
}

/* BOTTOM NAV */
.bottom-nav{display:none;}

@media(max-width:991px){
    .sidebar{
        left:-260px;
    }
    .sidebar.show{
        left:0;
    }
    .header{
        display:none;
    }
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
    .info-strip{
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
        color:var(--primary);
    }
}
</style>
<?php require __DIR__ . '/_hub_css.php'; ?>
</head>
<body class="hub-root">

<div class="mobile-header d-lg-none">
    <i class="fa fa-bars" onclick="toggleSidebar()" style="font-size:20px;cursor:pointer;"></i>
    <div style="font-weight:800;font-size:16px;">My Account</div>
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
        <h3>My Account</h3>
        <small>Manage your profile, password, and profile image</small>
    </div>

    <div class="header-right">
        <button class="icon-btn">
            <i class="fa fa-bell"></i>
            <span class="notify-badge">3</span>
        </button>

        <div class="user-box">
            <img class="user-avatar-small" src="<?=htmlspecialchars($avatar)?>" alt="User Avatar">
            <div class="user-meta">
                <strong><?=htmlspecialchars($user['full_name'] ?? $user_name)?></strong>
                <span><?=htmlspecialchars($user['email'] ?? $user_email)?></span>
            </div>
            <i class="fa fa-angle-down" style="color:#64748b;"></i>
        </div>
    </div>
</div>

<div class="content">

    <?php if($msg): ?>
        <div class="alert-pro <?= $msg_type === 'success' ? 'alert-success-pro' : 'alert-danger-pro' ?>">
            <i class="fa <?= $msg_type === 'success' ? 'fa-circle-check' : 'fa-triangle-exclamation' ?>"></i>
            <?=htmlspecialchars($msg)?>
        </div>
    <?php endif; ?>

    <div class="row g-3">
        <div class="col-lg-4">
            <div class="simple-card profile-main">
                <img src="<?=htmlspecialchars($avatar)?>" class="profile-avatar" alt="Profile Image">

                <div class="profile-name"><?=htmlspecialchars($user['full_name'] ?? '')?></div>
                <div class="profile-email"><?=htmlspecialchars($user['email'] ?? '')?></div>

                <div class="profile-chip-wrap">
                    <span class="profile-chip"><i class="fa fa-user"></i> Contractor</span>
                    <span class="profile-chip"><i class="fa fa-shield-halved"></i> Secure Account</span>
                </div>

                <div class="info-strip">
                    <div class="info-mini">
                        <span>Role</span>
                        <strong><?=htmlspecialchars($user['role'] ?? 'CONTRACTOR')?></strong>
                    </div>
                    <div class="info-mini">
                        <span>Status</span>
                        <strong><?=htmlspecialchars($user['status'] ?? 'Active')?></strong>
                    </div>
                </div>

                <form method="POST" enctype="multipart/form-data" class="mt-4">
                    <?= cpd_csrf_input() ?>
                    <div class="mb-3 text-start">
                        <label class="form-label">Upload New Profile Image</label>
                        <input type="file" name="image" class="form-control" accept="image/*">
                    </div>
                    <button type="submit" name="upload_image" class="btn-main w-100 justify-content-center">
                        <i class="fa fa-image"></i> Update Image
                    </button>
                </form>
            </div>
        </div>

        <div class="col-lg-8">
            <div class="simple-card mb-3">
                <div class="card-title-row">
                    <h5>Edit Profile</h5>
                </div>

                <form method="POST">
                    <?= cpd_csrf_input() ?>
                    <div class="mb-3">
                        <label class="form-label">Full Name</label>
                        <input type="text" name="name" class="form-control" value="<?=htmlspecialchars($user['full_name'] ?? '')?>" required>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Email Address</label>
                        <input type="email" name="email" class="form-control" value="<?=htmlspecialchars($user['email'] ?? '')?>" required>
                    </div>

                    <button type="submit" name="update_profile" class="btn-main">
                        <i class="fa fa-save"></i> Update Profile
                    </button>
                </form>
            </div>

            <div class="simple-card">
                <div class="card-title-row">
                    <h5>Change Password</h5>
                </div>

                <form method="POST">
                    <?= cpd_csrf_input() ?>
                    <div class="mb-3">
                        <label class="form-label">Current Password</label>
                        <input type="password" name="current_password" class="form-control" placeholder="Enter current password" required>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">New Password</label>
                        <input type="password" name="new_password" class="form-control" placeholder="Enter new password" required>
                    </div>

                    <button type="submit" name="change_password" class="btn-lite">
                        <i class="fa fa-lock"></i> Change Password
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>

<div class="bottom-nav d-lg-none">
    <a href="dashboard.php"><i class="fa fa-house"></i>Home</a>
    <a href="my_courses.php"><i class="fa fa-book"></i>Courses</a>
    <a href="certificates.php"><i class="fa fa-certificate"></i>Certs</a>
    <a href="account.php" class="active"><i class="fa fa-user"></i>Account</a>
    <a href="logout.php"><i class="fa fa-right-from-bracket"></i>Logout</a>
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