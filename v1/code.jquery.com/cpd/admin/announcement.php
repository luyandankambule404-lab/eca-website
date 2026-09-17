<?php
require_once "../auth.php";
require_once "../config.php";
require_once "../header.php";

/*
|--------------------------------------------------------------------------
| ACCESS CONTROL
|--------------------------------------------------------------------------
*/
if (!isset($_SESSION['role']) || !in_array($_SESSION['role'], ['SUPPERADMIN', 'ADMIN', 'OFFICER'])) {
    header("Location: ../login.php");
    exit;
}

if (!function_exists('e')) {
    function e($v) {
        return htmlspecialchars($v ?? '', ENT_QUOTES, 'UTF-8');
    }
}

$msg = "";
$error = "";
/*
|--------------------------------------------------------------------------
| IMAGE UPLOAD HELPER
|--------------------------------------------------------------------------
*/
function uploadAnnouncementImage($fieldName, $oldImage = "")
{
    $uploadDir = dirname(__DIR__) . "/uploads/announcements/";
    $dbPathPrefix = "uploads/announcements/";

    if (!isset($_FILES[$fieldName]) || empty($_FILES[$fieldName]['name'])) {
        return [$oldImage, ""];
    }

    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0775, true);
    }

    if ($_FILES[$fieldName]['error'] !== UPLOAD_ERR_OK) {
        return [$oldImage, "Image upload failed. Please try again."];
    }

    $maxSize = 5 * 1024 * 1024; // 5MB
    if ($_FILES[$fieldName]['size'] > $maxSize) {
        return [$oldImage, "Image is too large. Maximum allowed size is 5MB."];
    }

    $allowedExt = ['jpg', 'jpeg', 'png', 'webp', 'gif'];
    $ext = strtolower(pathinfo($_FILES[$fieldName]['name'], PATHINFO_EXTENSION));

    if (!in_array($ext, $allowedExt)) {
        return [$oldImage, "Invalid image type. Use JPG, JPEG, PNG, WEBP, or GIF only."];
    }

    $imageCheck = @getimagesize($_FILES[$fieldName]['tmp_name']);
    if ($imageCheck === false) {
        return [$oldImage, "Uploaded file is not a valid image."];
    }

    $newFileName = "announcement_" . date("YmdHis") . "_" . rand(1000, 9999) . "." . $ext;
    $destination = $uploadDir . $newFileName;

    if (!move_uploaded_file($_FILES[$fieldName]['tmp_name'], $destination)) {
        return [$oldImage, "Failed to save uploaded image."];
    }

    // Delete old image after successful new upload
    if (!empty($oldImage)) {
        $oldImagePath = dirname(__DIR__) . "/" . $oldImage;
        if (file_exists($oldImagePath)) {
            @unlink($oldImagePath);
        }
    }

    return [$dbPathPrefix . $newFileName, ""];
}

/*
|--------------------------------------------------------------------------
| ADD ANNOUNCEMENT
|--------------------------------------------------------------------------
*/
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'add') {

    $title       = trim($_POST['title'] ?? '');
    $message     = trim($_POST['message'] ?? '');
    $target_role = trim($_POST['target_role'] ?? 'CONTRACTOR');
    $course_id   = !empty($_POST['course_id']) ? (int)$_POST['course_id'] : null;
    $link        = trim($_POST['link'] ?? '');
    $status      = trim($_POST['status'] ?? 'ACTIVE');
    $created_by  = $_SESSION['user_id'] ?? null;

    [$image, $uploadError] = uploadAnnouncementImage("image");

    if ($uploadError !== "") {
        $error = $uploadError;
    } elseif ($title === '' || $message === '') {
        $error = "Title and message are required.";
    } else {
        $stmt = $conn->prepare("
            INSERT INTO announcements 
            (title, message, target_role, course_id, link, image, status, created_by)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?)
        ");

        $stmt->bind_param(
            "sssisssi",
            $title,
            $message,
            $target_role,
            $course_id,
            $link,
            $image,
            $status,
            $created_by
        );

        if ($stmt->execute()) {
            $msg = "Announcement created successfully.";
        } else {
            $error = "Failed to create announcement.";
        }
    }
}

/*
|--------------------------------------------------------------------------
| UPDATE ANNOUNCEMENT
|--------------------------------------------------------------------------
*/
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'update') {

    $id          = (int)($_POST['announcement_id'] ?? 0);
    $title       = trim($_POST['title'] ?? '');
    $message     = trim($_POST['message'] ?? '');
    $target_role = trim($_POST['target_role'] ?? 'CONTRACTOR');
    $course_id   = !empty($_POST['course_id']) ? (int)$_POST['course_id'] : null;
    $link        = trim($_POST['link'] ?? '');
    $status      = trim($_POST['status'] ?? 'ACTIVE');

    $oldImage = "";
    if ($id > 0) {
        $oldStmt = $conn->prepare("SELECT image FROM announcements WHERE id = ?");
        $oldStmt->bind_param("i", $id);
        $oldStmt->execute();
        $oldResult = $oldStmt->get_result();
        if ($oldRow = $oldResult->fetch_assoc()) {
            $oldImage = $oldRow['image'] ?? "";
        }
    }

    [$image, $uploadError] = uploadAnnouncementImage("image", $oldImage);

    if ($uploadError !== "") {
        $error = $uploadError;
    } elseif ($id <= 0 || $title === '' || $message === '') {
        $error = "Please complete all required fields.";
    } else {
        $stmt = $conn->prepare("
            UPDATE announcements SET
                title = ?,
                message = ?,
                target_role = ?,
                course_id = ?,
                link = ?,
                image = ?,
                status = ?
            WHERE id = ?
        ");

        $stmt->bind_param(
            "sssisssi",
            $title,
            $message,
            $target_role,
            $course_id,
            $link,
            $image,
            $status,
            $id
        );

        if ($stmt->execute()) {
            $msg = "Announcement updated successfully.";
        } else {
            $error = "Failed to update announcement.";
        }
    }
}

/*
|--------------------------------------------------------------------------
| DELETE ANNOUNCEMENT
|--------------------------------------------------------------------------
*/
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'delete') {

    $id = (int)($_POST['announcement_id'] ?? 0);

    if ($id > 0) {

        $imgStmt = $conn->prepare("SELECT image FROM announcements WHERE id = ?");
        $imgStmt->bind_param("i", $id);
        $imgStmt->execute();
        $imgResult = $imgStmt->get_result();
        $imgRow = $imgResult->fetch_assoc();
        $imageToDelete = $imgRow['image'] ?? "";

        $stmt = $conn->prepare("DELETE FROM announcements WHERE id = ?");
        $stmt->bind_param("i", $id);

        if ($stmt->execute()) {

            if (!empty($imageToDelete)) {
                $imagePath = dirname(__DIR__) . "/" . $imageToDelete;
                if (file_exists($imagePath)) {
                    @unlink($imagePath);
                }
            }

            $msg = "Announcement deleted successfully.";
        } else {
            $error = "Failed to delete announcement.";
        }
    }
}

/*
|--------------------------------------------------------------------------
| LOAD COURSES
|--------------------------------------------------------------------------
*/
$courses = $conn->query("
    SELECT id, title, start_date 
    FROM courses 
    ORDER BY start_date DESC
");

/*
|--------------------------------------------------------------------------
| LOAD ANNOUNCEMENTS
|--------------------------------------------------------------------------
*/
$announcements = $conn->query("
    SELECT 
        a.*,
        c.title AS course_title
    FROM announcements a
    LEFT JOIN courses c ON c.id = a.course_id
    ORDER BY a.created_at DESC
");

/*
|--------------------------------------------------------------------------
| ANALYTICS
|--------------------------------------------------------------------------
*/
$total_announcements = $conn->query("SELECT COUNT(*) c FROM announcements")->fetch_assoc()['c'] ?? 0;
$active_announcements = $conn->query("SELECT COUNT(*) c FROM announcements WHERE status='ACTIVE'")->fetch_assoc()['c'] ?? 0;
$client_announcements = $conn->query("SELECT COUNT(*) c FROM announcements WHERE target_role='CONTRACTOR' OR target_role='ALL'")->fetch_assoc()['c'] ?? 0;

?>

<!DOCTYPE html>
<html>
<head>
    <title>CPD Announcements</title>

    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">

    <style>
        body {
            background: #eef2f7;
            font-family: 'Poppins', sans-serif;
        }

        .page-wrap {
            padding: 25px;
        }

        .hero-header {
            background: linear-gradient(135deg, #06254a, #25809b);
            color: #fff;
            border-radius: 22px;
            padding: 28px;
            box-shadow: 0 20px 40px rgba(6, 37, 74, 0.22);
            margin-bottom: 25px;
            position: relative;
            overflow: hidden;
        }

        .hero-header::after {
            content: "";
            position: absolute;
            width: 240px;
            height: 240px;
            border-radius: 50%;
            background: rgba(255,255,255,0.10);
            right: -70px;
            top: -80px;
        }

        .hero-header h3 {
            font-weight: 700;
            margin-bottom: 5px;
        }

        .hero-header p {
            margin: 0;
            opacity: .9;
        }

        .top-btn {
            background: #fff;
            color: #06254a;
            border: none;
            font-weight: 700;
            border-radius: 14px;
            padding: 11px 18px;
            box-shadow: 0 10px 25px rgba(0,0,0,0.12);
        }

        .stat-card {
            border: none;
            border-radius: 20px;
            padding: 22px;
            color: #fff;
            box-shadow: 0 14px 30px rgba(0,0,0,0.12);
        }

        .stat-card h4 {
            font-weight: 800;
            margin-bottom: 4px;
        }

        .stat-card span {
            font-size: 13px;
            opacity: .92;
        }

        .bg-one {
            background: linear-gradient(135deg, #06254a, #0b4b7a);
        }

        .bg-two {
            background: linear-gradient(135deg, #25809b, #27b4c8);
        }

        .bg-three {
            background: linear-gradient(135deg, #13a34a, #33c96f);
        }

        .announcement-card {
            background: #fff;
            border-radius: 22px;
            padding: 22px;
            box-shadow: 0 12px 28px rgba(0,0,0,0.08);
            border: 1px solid rgba(0,0,0,0.04);
            margin-bottom: 18px;
            transition: .25s;
            overflow: hidden;
        }

        .announcement-card:hover {
            transform: translateY(-4px);
            box-shadow: 0 18px 35px rgba(0,0,0,0.12);
        }

        .announcement-image {
            width: 100%;
            max-height: 260px;
            object-fit: cover;
            border-radius: 18px;
            margin-bottom: 16px;
            border: 1px solid #e5edf5;
        }

        .announcement-title {
            font-weight: 700;
            color: #06254a;
            margin-bottom: 8px;
        }

        .announcement-message {
            color: #555;
            font-size: 14px;
            line-height: 1.7;
            margin-bottom: 12px;
        }

        .badge-soft {
            border-radius: 30px;
            padding: 7px 12px;
            font-size: 12px;
            font-weight: 600;
            display: inline-block;
            margin-bottom: 5px;
        }

        .badge-active {
            background: #e8fff1;
            color: #0f8b3d;
        }

        .badge-inactive {
            background: #fff0f0;
            color: #c40000;
        }

        .badge-role {
            background: #eef7ff;
            color: #0b5f89;
        }

        .badge-course {
            background: #fff6e5;
            color: #b46d00;
        }

        .action-btn {
            border-radius: 11px;
            font-size: 13px;
            font-weight: 600;
            padding: 8px 12px;
        }

        .form-control,
        .form-select {
            border-radius: 13px;
            padding: 11px 13px;
            border: 1px solid #d9e1ea;
        }

        .modal-content {
            border-radius: 22px;
            border: none;
            overflow: hidden;
        }

        .modal-header {
            background: linear-gradient(135deg, #06254a, #25809b);
            color: #fff;
            border-bottom: none;
        }

        .modal-title {
            font-weight: 700;
        }

        .btn-save {
            background: linear-gradient(135deg, #06254a, #25809b);
            color: #fff;
            border: none;
            border-radius: 14px;
            padding: 10px 20px;
            font-weight: 700;
        }

        .search-box {
            border-radius: 18px;
            padding: 14px 18px;
            box-shadow: 0 10px 25px rgba(0,0,0,0.06);
            border: none;
            margin-bottom: 22px;
        }

        .empty-box {
            background: #fff;
            border-radius: 20px;
            padding: 35px;
            text-align: center;
            color: #777;
        }

        .upload-box {
            background: #f7fbff;
            border: 1px dashed #9eb8d0;
            border-radius: 16px;
            padding: 16px;
        }

        .edit-image-preview {
            width: 100%;
            max-height: 180px;
            object-fit: cover;
            border-radius: 15px;
            border: 1px solid #d9e1ea;
            margin-bottom: 12px;
            display: none;
        }
    </style>
</head>

<body>

<div class="page-wrap">

    <div class="hero-header d-flex justify-content-between align-items-center">
        <div>
            <h3><i class="fa-solid fa-bullhorn"></i> Client Portal Announcements</h3>
            <p>Create important announcements that will appear on the contractor/client portal.</p>
        </div>

        <button class="top-btn" data-bs-toggle="modal" data-bs-target="#addModal">
            <i class="fa-solid fa-plus"></i> New Announcement
        </button>
    </div>

    <?php if ($msg): ?>
        <div class="alert alert-success"><?= e($msg) ?></div>
    <?php endif; ?>

    <?php if ($error): ?>
        <div class="alert alert-danger"><?= e($error) ?></div>
    <?php endif; ?>

    <div class="row g-3 mb-4">
        <div class="col-md-4">
            <div class="stat-card bg-one">
                <h4><?= (int)$total_announcements ?></h4>
                <span>Total Announcements</span>
            </div>
        </div>

        <div class="col-md-4">
            <div class="stat-card bg-two">
                <h4><?= (int)$active_announcements ?></h4>
                <span>Active Announcements</span>
            </div>
        </div>

        <div class="col-md-4">
            <div class="stat-card bg-three">
                <h4><?= (int)$client_announcements ?></h4>
                <span>Visible to Client Portal</span>
            </div>
        </div>
    </div>

    <input 
        type="text" 
        id="announcementSearch" 
        class="form-control search-box" 
        placeholder="Search announcements by title, role, course or message..."
    >

    <div id="announcementList">

        <?php if ($announcements && $announcements->num_rows > 0): ?>

            <?php while ($a = $announcements->fetch_assoc()): ?>

                <div class="announcement-card announcement-item">

                    <?php if (!empty($a['image'])): ?>
                        <img 
                            src="../<?= e($a['image']) ?>" 
                            alt="<?= e($a['title']) ?>" 
                            class="announcement-image"
                        >
                    <?php endif; ?>

                    <div class="d-flex justify-content-between align-items-start gap-3">

                        <div>
                            <h5 class="announcement-title">
                                <?= e($a['title']) ?>
                            </h5>

                            <div class="mb-2">
                                <span class="badge-soft badge-role">
                                    <i class="fa-solid fa-users"></i>
                                    <?= e($a['target_role']) ?>
                                </span>

                                <?php if (!empty($a['course_title'])): ?>
                                    <span class="badge-soft badge-course">
                                        <i class="fa-solid fa-book"></i>
                                        <?= e($a['course_title']) ?>
                                    </span>
                                <?php endif; ?>

                                <?php if ($a['status'] === 'ACTIVE'): ?>
                                    <span class="badge-soft badge-active">
                                        <i class="fa-solid fa-circle-check"></i> ACTIVE
                                    </span>
                                <?php else: ?>
                                    <span class="badge-soft badge-inactive">
                                        <i class="fa-solid fa-circle-xmark"></i> INACTIVE
                                    </span>
                                <?php endif; ?>
                            </div>
                        </div>

                        <small class="text-muted">
                            <?= e(date("d M Y", strtotime($a['created_at']))) ?>
                        </small>

                    </div>

                    <div class="announcement-message">
                        <?= nl2br(e($a['message'])) ?>
                    </div>

                    <?php if (!empty($a['link'])): ?>
                        <a href="<?= e($a['link']) ?>" target="_blank" class="small">
                            <i class="fa-solid fa-link"></i> <?= e($a['link']) ?>
                        </a>
                    <?php endif; ?>

                    <div class="d-flex gap-2 mt-3">

                        <button 
                            class="btn btn-sm btn-warning action-btn editAnnouncement"
                            data-id="<?= (int)$a['id'] ?>"
                            data-title="<?= e($a['title']) ?>"
                            data-message="<?= e($a['message']) ?>"
                            data-target="<?= e($a['target_role']) ?>"
                            data-course="<?= e($a['course_id']) ?>"
                            data-link="<?= e($a['link']) ?>"
                            data-status="<?= e($a['status']) ?>"
                            data-image="<?= e($a['image']) ?>"
                        >
                            <i class="fa-solid fa-pen"></i> Edit
                        </button>

                        <form method="POST" onsubmit="return confirm('Delete this announcement?');">
                            <input type="hidden" name="action" value="delete">
                            <input type="hidden" name="announcement_id" value="<?= (int)$a['id'] ?>">
                            <button class="btn btn-sm btn-danger action-btn">
                                <i class="fa-solid fa-trash"></i> Delete
                            </button>
                        </form>

                    </div>

                </div>

            <?php endwhile; ?>

        <?php else: ?>

            <div class="empty-box">
                <i class="fa-solid fa-bullhorn fa-2x mb-3"></i>
                <h5>No announcements yet</h5>
                <p>Create your first announcement to display on the client portal.</p>
            </div>

        <?php endif; ?>

    </div>

</div>

<!-- ADD MODAL -->
<div class="modal fade" id="addModal">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content">

            <form method="POST" enctype="multipart/form-data">
                <input type="hidden" name="action" value="add">

                <div class="modal-header">
                    <h5 class="modal-title">
                        <i class="fa-solid fa-plus"></i> Create Announcement
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>

                <div class="modal-body">

                    <div class="row g-3">

                        <div class="col-md-8">
                            <label class="form-label">Announcement Title</label>
                            <input type="text" name="title" class="form-control" placeholder="Example: New CPD Training Now Open" required>
                        </div>

                        <div class="col-md-4">
                            <label class="form-label">Status</label>
                            <select name="status" class="form-select">
                                <option value="ACTIVE">ACTIVE</option>
                                <option value="INACTIVE">INACTIVE</option>
                            </select>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label">Show To</label>
                            <select name="target_role" class="form-select">
                                <option value="CONTRACTOR">Client / Contractor Portal</option>
                                <option value="ALL">All Users</option>
                                <option value="ADMIN">Admins Only</option>
                                <option value="OFFICER">Officers Only</option>
                            </select>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label">Link to Course Optional</label>
                            <select name="course_id" class="form-select">
                                <option value="">General Announcement</option>

                                <?php if ($courses): ?>
                                    <?php 
                                    $courses->data_seek(0);
                                    while ($course = $courses->fetch_assoc()): 
                                    ?>
                                        <option value="<?= (int)$course['id'] ?>">
                                            <?= e($course['title']) ?>
                                        </option>
                                    <?php endwhile; ?>
                                <?php endif; ?>

                            </select>
                        </div>

                        <div class="col-12">
                            <label class="form-label">Optional Button Link</label>
                            <input type="url" name="link" class="form-control" placeholder="Example: https://eca.co.sz/cpd/courses.php">
                        </div>

                        <div class="col-12">
                            <div class="upload-box">
                                <label class="form-label">
                                    <i class="fa-solid fa-image"></i> Upload Announcement Image Optional
                                </label>
                                <input type="file" name="image" class="form-control" accept="image/*">
                                <small class="text-muted">Allowed: JPG, JPEG, PNG, WEBP, GIF. Maximum size: 5MB.</small>
                            </div>
                        </div>

                        <div class="col-12">
                            <label class="form-label">Message</label>
                            <textarea name="message" class="form-control" rows="6" required placeholder="Write the announcement message here..."></textarea>
                        </div>

                    </div>

                </div>

                <div class="modal-footer">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                    <button class="btn-save">
                        <i class="fa-solid fa-paper-plane"></i> Publish Announcement
                    </button>
                </div>

            </form>

        </div>
    </div>
</div>

<!-- EDIT MODAL -->
<div class="modal fade" id="editModal">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content">

            <form method="POST" enctype="multipart/form-data">
                <input type="hidden" name="action" value="update">
                <input type="hidden" name="announcement_id" id="edit_id">

                <div class="modal-header">
                    <h5 class="modal-title">
                        <i class="fa-solid fa-pen"></i> Edit Announcement
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>

                <div class="modal-body">

                    <div class="row g-3">

                        <div class="col-md-8">
                            <label class="form-label">Announcement Title</label>
                            <input type="text" name="title" id="edit_title" class="form-control" required>
                        </div>

                        <div class="col-md-4">
                            <label class="form-label">Status</label>
                            <select name="status" id="edit_status" class="form-select">
                                <option value="ACTIVE">ACTIVE</option>
                                <option value="INACTIVE">INACTIVE</option>
                            </select>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label">Show To</label>
                            <select name="target_role" id="edit_target" class="form-select">
                                <option value="CONTRACTOR">Client / Contractor Portal</option>
                                <option value="ALL">All Users</option>
                                <option value="ADMIN">Admins Only</option>
                                <option value="OFFICER">Officers Only</option>
                            </select>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label">Link to Course Optional</label>
                            <select name="course_id" id="edit_course" class="form-select">
                                <option value="">General Announcement</option>

                                <?php if ($courses): ?>
                                    <?php 
                                    $courses->data_seek(0);
                                    while ($course = $courses->fetch_assoc()): 
                                    ?>
                                        <option value="<?= (int)$course['id'] ?>">
                                            <?= e($course['title']) ?>
                                        </option>
                                    <?php endwhile; ?>
                                <?php endif; ?>

                            </select>
                        </div>

                        <div class="col-12">
                            <label class="form-label">Optional Button Link</label>
                            <input type="url" name="link" id="edit_link" class="form-control">
                        </div>

                        <div class="col-12">
                            <div class="upload-box">
                                <label class="form-label">
                                    <i class="fa-solid fa-image"></i> Update Announcement Image Optional
                                </label>

                                <img id="edit_image_preview" class="edit-image-preview" src="" alt="Current announcement image">

                                <input type="file" name="image" class="form-control" accept="image/*">
                                <small class="text-muted">Leave empty if you do not want to change the current image.</small>
                            </div>
                        </div>

                        <div class="col-12">
                            <label class="form-label">Message</label>
                            <textarea name="message" id="edit_message" class="form-control" rows="6" required></textarea>
                        </div>

                    </div>

                </div>

                <div class="modal-footer">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                    <button class="btn-save">
                        <i class="fa-solid fa-save"></i> Update Announcement
                    </button>
                </div>

            </form>

        </div>
    </div>
</div>

<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>

<script>
$("#announcementSearch").on("keyup", function () {
    let value = $(this).val().toLowerCase();

    $(".announcement-item").filter(function () {
        $(this).toggle($(this).text().toLowerCase().indexOf(value) > -1);
    });
});

$(document).on("click", ".editAnnouncement", function () {

    $("#edit_id").val($(this).data("id"));
    $("#edit_title").val($(this).data("title"));
    $("#edit_message").val($(this).data("message"));
    $("#edit_target").val($(this).data("target"));
    $("#edit_course").val($(this).data("course"));
    $("#edit_link").val($(this).data("link"));
    $("#edit_status").val($(this).data("status"));

    let image = $(this).data("image");

    if (image) {
        $("#edit_image_preview")
            .attr("src", "../" + image)
            .show();
    } else {
        $("#edit_image_preview")
            .attr("src", "")
            .hide();
    }

    let modal = new bootstrap.Modal(document.getElementById("editModal"));
    modal.show();
});
</script>

</body>
</html>