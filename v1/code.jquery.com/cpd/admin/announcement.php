<?php
require_once "../auth.php";
require_role(['SUPPERADMIN', 'ADMIN', 'OFFICER']);
require_once "../helpers.php";

if (!function_exists('e')) {
    function e($v) {
        return htmlspecialchars($v ?? '', ENT_QUOTES, 'UTF-8');
    }
}

$msg = "";
$error = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    cpd_require_csrf();
}

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

    $maxSize = 5 * 1024 * 1024;
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

    if (!empty($oldImage)) {
        $oldImagePath = dirname(__DIR__) . "/" . $oldImage;
        if (file_exists($oldImagePath)) {
            @unlink($oldImagePath);
        }
    }

    return [$dbPathPrefix . $newFileName, ""];
}

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

$courseRows = [];
$courseRes = $conn->query("SELECT id, title, start_date FROM courses ORDER BY start_date DESC");
if ($courseRes) {
    while ($row = $courseRes->fetch_assoc()) {
        $courseRows[] = $row;
    }
}

$announcementRows = [];
$annRes = $conn->query("
    SELECT a.*, c.title AS course_title
    FROM announcements a
    LEFT JOIN courses c ON c.id = a.course_id
    ORDER BY a.created_at DESC
");
if ($annRes) {
    while ($row = $annRes->fetch_assoc()) {
        $announcementRows[] = $row;
    }
}

$total_announcements = 0;
$active_announcements = 0;
$client_announcements = 0;
try {
    $q = $conn->query("SELECT COUNT(*) c FROM announcements");
    if ($q) {
        $total_announcements = (int) ($q->fetch_assoc()['c'] ?? 0);
    }
    $q = $conn->query("SELECT COUNT(*) c FROM announcements WHERE " . cpd_status_equals_sql('status', ['ACTIVE', 'Published']));
    if ($q) {
        $active_announcements = (int) ($q->fetch_assoc()['c'] ?? 0);
    }
    $q = $conn->query("SELECT COUNT(*) c FROM announcements WHERE " . cpd_status_equals_sql('target_role', ['CONTRACTOR', 'ALL']));
    if ($q) {
        $client_announcements = (int) ($q->fetch_assoc()['c'] ?? 0);
    }
} catch (Throwable $e) {
    // Keep zero counts if the announcements table is unavailable.
}

require_once "../header.php";
?>
<style>
.ann-page {
    --ann-navy: #192754;
    --ann-navy-soft: #2a3f73;
    --ann-red: #d50d0e;
}

.ann-head {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    gap: 16px;
    flex-wrap: wrap;
    background: #fff;
    border: 1px solid #e8ecf4;
    border-radius: 22px;
    padding: 22px 24px;
    margin: 0 0 18px;
    box-shadow: 0 10px 28px rgba(25, 39, 84, 0.06);
}

.ann-head h1 {
    margin: 0 0 6px;
    color: #192754;
    font-size: 1.35rem;
    font-weight: 800;
    letter-spacing: 0;
    word-spacing: normal;
}

.ann-head p {
    margin: 0;
    color: #6b7690;
    font-size: 0.95rem;
    line-height: 1.55;
}

.ann-page .hub-stats {
    grid-template-columns: repeat(3, minmax(0, 1fr));
}

.ann-page .hub-stat,
.ann-page .hub-stat:nth-child(4n + 1),
.ann-page .hub-stat:nth-child(4n + 2),
.ann-page .hub-stat:nth-child(4n + 3) {
    min-height: 118px;
    border: 0;
    box-shadow: 0 10px 24px rgba(25, 39, 84, 0.12);
}

.ann-page .hub-stat:nth-child(1) {
    background: #192754 !important;
    color: #fff !important;
}

.ann-page .hub-stat:nth-child(2) {
    background: #2a3f73 !important;
    color: #fff !important;
}

.ann-page .hub-stat:nth-child(3) {
    background: #d50d0e !important;
    color: #fff !important;
}

.ann-page .hub-stat .hub-stat-label,
.ann-page .hub-stat .hub-stat-value,
.ann-page .hub-stat:nth-child(4n + 1) .hub-stat-label,
.ann-page .hub-stat:nth-child(4n + 1) .hub-stat-value {
    color: #fff !important;
}

.ann-actions {
    display: flex;
    flex-wrap: wrap;
    gap: 10px;
    margin-top: 8px;
}

.ann-page .hub-link-danger {
    background: none;
    border: 0;
    color: #d50d0e;
    font-weight: 800;
    cursor: pointer;
    padding: 0;
}

.ann-card {
    background: #fff;
    border: 1px solid #e8ecf4;
    border-radius: 22px;
    padding: 22px;
    box-shadow: 0 10px 28px rgba(25, 39, 84, 0.06);
    margin-bottom: 16px;
}

.ann-card img {
    width: 100%;
    max-height: 260px;
    object-fit: cover;
    border-radius: 16px;
    margin-bottom: 16px;
    border: 1px solid #e8ecf4;
}

.ann-card h3 {
    margin: 0 0 8px;
    color: #192754;
    font-size: 1.15rem;
}

.ann-card p {
    margin: 0 0 12px;
    color: #4b5563;
    line-height: 1.65;
}

.ann-meta {
    display: flex;
    flex-wrap: wrap;
    gap: 8px;
    margin-bottom: 10px;
}

.ann-chip {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    min-height: 28px;
    padding: 0 10px;
    border-radius: 999px;
    font-size: 0.75rem;
    font-weight: 800;
}

.ann-chip.is-role { background: #eef2f8; color: #192754; }
.ann-chip.is-course { background: #fff4f4; color: #d50d0e; }
.ann-chip.is-active { background: #e8fff1; color: #0f8b3d; }
.ann-chip.is-inactive { background: #fff0f0; color: #d50d0e; }

.ann-date {
    color: #6b7690;
    font-size: 0.86rem;
    font-weight: 700;
    white-space: nowrap;
}

.ann-search {
    width: 100%;
    min-height: 48px;
    margin: 0 0 20px;
    padding: 12px 16px;
    border: 1px solid #e8ecf4;
    border-radius: 14px;
    background: #fff;
    color: #192754;
}

.ann-modal .modal-header {
    background: #192754;
    color: #fff;
    border-bottom: 4px solid #d50d0e;
}

.ann-modal .btn-save {
    background: #d50d0e;
    color: #fff;
    border: 0;
    border-radius: 999px;
    font-weight: 800;
    padding: 10px 20px;
}

.ann-modal .btn-save:hover {
    background: #b00b0c;
    color: #fff;
}

.ann-upload {
    background: #f4f7fb;
    border: 1px dashed #c5cddc;
    border-radius: 14px;
    padding: 14px;
}

.edit-image-preview {
    width: 100%;
    max-height: 180px;
    object-fit: cover;
    border-radius: 12px;
    border: 1px solid #e8ecf4;
    margin-bottom: 12px;
    display: none;
}

@media (max-width: 900px) {
    .ann-page .hub-stats {
        grid-template-columns: 1fr;
    }
}
</style>

<div class="ann-page">
    <div class="ann-head">
        <div>
            <h1>Client portal announcements</h1>
            <p>Create important announcements that will appear on the contractor / client portal.</p>
        </div>
        <button class="hub-btn" type="button" data-bs-toggle="modal" data-bs-target="#addModal">
            New announcement
        </button>
    </div>

    <?php if ($msg): ?>
        <p class="hub-card"><?= e($msg) ?></p>
    <?php endif; ?>
    <?php if ($error): ?>
        <p class="hub-card" style="border-color:#d50d0e;"><?= e($error) ?></p>
    <?php endif; ?>

    <div class="hub-stats">
        <article class="hub-stat">
            <div class="hub-stat-label">Total announcements</div>
            <div class="hub-stat-value"><?= $total_announcements ?></div>
        </article>
        <article class="hub-stat">
            <div class="hub-stat-label">Active announcements</div>
            <div class="hub-stat-value"><?= $active_announcements ?></div>
        </article>
        <article class="hub-stat">
            <div class="hub-stat-label">Visible to client portal</div>
            <div class="hub-stat-value"><?= $client_announcements ?></div>
        </article>
    </div>

    <input
        type="search"
        id="announcementSearch"
        class="ann-search"
        placeholder="Search announcements by title, role, course or message"
    >

    <div id="announcementList">
        <?php if ($announcementRows): ?>
            <?php foreach ($announcementRows as $a): ?>
                <article class="ann-card announcement-item">
                    <?php if (!empty($a['image'])): ?>
                        <img src="../<?= e($a['image']) ?>" alt="<?= e($a['title']) ?>">
                    <?php endif; ?>
                    <div class="d-flex justify-content-between align-items-start gap-3">
                        <div>
                            <h3><?= e($a['title']) ?></h3>
                            <div class="ann-meta">
                                <span class="ann-chip is-role"><?= e($a['target_role']) ?></span>
                                <?php if (!empty($a['course_title'])): ?>
                                    <span class="ann-chip is-course"><?= e($a['course_title']) ?></span>
                                <?php endif; ?>
                                <?php if (($a['status'] ?? '') === 'ACTIVE'): ?>
                                    <span class="ann-chip is-active">Active</span>
                                <?php else: ?>
                                    <span class="ann-chip is-inactive">Inactive</span>
                                <?php endif; ?>
                            </div>
                        </div>
                        <span class="ann-date"><?= e(date('d M Y', strtotime((string) $a['created_at']))) ?></span>
                    </div>
                    <p><?= nl2br(e($a['message'])) ?></p>
                    <?php if (!empty($a['link'])): ?>
                        <p><a href="<?= e($a['link']) ?>" target="_blank" rel="noopener noreferrer"><?= e($a['link']) ?></a></p>
                    <?php endif; ?>
                    <div class="ann-actions">
                        <button
                            class="hub-btn editAnnouncement"
                            type="button"
                            data-id="<?= (int)$a['id'] ?>"
                            data-title="<?= e($a['title']) ?>"
                            data-message="<?= e($a['message']) ?>"
                            data-target="<?= e($a['target_role']) ?>"
                            data-course="<?= e((string) ($a['course_id'] ?? '')) ?>"
                            data-link="<?= e($a['link']) ?>"
                            data-status="<?= e($a['status']) ?>"
                            data-image="<?= e($a['image']) ?>"
                        >Edit</button>
                        <form method="post" onsubmit="return confirm('Delete this announcement?');">
                            <?= cpd_csrf_input() ?>
                            <input type="hidden" name="action" value="delete">
                            <input type="hidden" name="announcement_id" value="<?= (int)$a['id'] ?>">
                            <button class="hub-link-danger" type="submit">Delete</button>
                        </form>
                    </div>
                </article>
            <?php endforeach; ?>
        <?php else: ?>
            <div class="hub-card">
                <p>No announcements yet. Create the first one to display on the client portal.</p>
            </div>
        <?php endif; ?>
    </div>
</div>

<div class="modal fade ann-modal" id="addModal" tabindex="-1" aria-labelledby="addModalTitle" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content">
            <form method="post" enctype="multipart/form-data">
                <?= cpd_csrf_input() ?>
                <input type="hidden" name="action" value="add">
                <div class="modal-header">
                    <h2 class="modal-title fs-5" id="addModalTitle">Create announcement</h2>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="row g-3">
                        <div class="col-md-8">
                            <label class="form-label">Announcement title</label>
                            <input type="text" name="title" class="form-control" placeholder="Example: New CPD training now open" required>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Status</label>
                            <select name="status" class="form-select">
                                <option value="ACTIVE">ACTIVE</option>
                                <option value="INACTIVE">INACTIVE</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Show to</label>
                            <select name="target_role" class="form-select">
                                <option value="CONTRACTOR">Client / contractor portal</option>
                                <option value="ALL">All users</option>
                                <option value="ADMIN">Admins only</option>
                                <option value="OFFICER">Officers only</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Link to course (optional)</label>
                            <select name="course_id" class="form-select">
                                <option value="">General announcement</option>
                                <?php foreach ($courseRows as $course): ?>
                                    <option value="<?= (int)$course['id'] ?>"><?= e($course['title']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-12">
                            <label class="form-label">Optional button link</label>
                            <input type="url" name="link" class="form-control" placeholder="https://eca.co.sz/education-training.php">
                        </div>
                        <div class="col-12">
                            <div class="ann-upload">
                                <label class="form-label">Announcement image (optional)</label>
                                <input type="file" name="image" class="form-control" accept="image/*">
                                <small class="text-muted">JPG, JPEG, PNG, WEBP or GIF. Maximum 5 MB.</small>
                            </div>
                        </div>
                        <div class="col-12">
                            <label class="form-label">Message</label>
                            <textarea name="message" class="form-control" rows="6" required placeholder="Write the announcement message here"></textarea>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                    <button class="btn-save" type="submit">Publish announcement</button>
                </div>
            </form>
        </div>
    </div>
</div>

<div class="modal fade ann-modal" id="editModal" tabindex="-1" aria-labelledby="editModalTitle" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content">
            <form method="post" enctype="multipart/form-data">
                <?= cpd_csrf_input() ?>
                <input type="hidden" name="action" value="update">
                <input type="hidden" name="announcement_id" id="edit_id">
                <div class="modal-header">
                    <h2 class="modal-title fs-5" id="editModalTitle">Edit announcement</h2>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="row g-3">
                        <div class="col-md-8">
                            <label class="form-label">Announcement title</label>
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
                            <label class="form-label">Show to</label>
                            <select name="target_role" id="edit_target" class="form-select">
                                <option value="CONTRACTOR">Client / contractor portal</option>
                                <option value="ALL">All users</option>
                                <option value="ADMIN">Admins only</option>
                                <option value="OFFICER">Officers only</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Link to course (optional)</label>
                            <select name="course_id" id="edit_course" class="form-select">
                                <option value="">General announcement</option>
                                <?php foreach ($courseRows as $course): ?>
                                    <option value="<?= (int)$course['id'] ?>"><?= e($course['title']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-12">
                            <label class="form-label">Optional button link</label>
                            <input type="url" name="link" id="edit_link" class="form-control">
                        </div>
                        <div class="col-12">
                            <div class="ann-upload">
                                <label class="form-label">Update announcement image (optional)</label>
                                <img id="edit_image_preview" class="edit-image-preview" src="" alt="Current announcement image">
                                <input type="file" name="image" class="form-control" accept="image/*">
                                <small class="text-muted">Leave empty to keep the current image.</small>
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
                    <button class="btn-save" type="submit">Update announcement</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<script>
document.getElementById('announcementSearch')?.addEventListener('keyup', function () {
    const value = this.value.toLowerCase();
    document.querySelectorAll('.announcement-item').forEach(function (item) {
        item.style.display = item.textContent.toLowerCase().indexOf(value) > -1 ? '' : 'none';
    });
});

document.querySelectorAll('.editAnnouncement').forEach(function (button) {
    button.addEventListener('click', function () {
        document.getElementById('edit_id').value = this.dataset.id || '';
        document.getElementById('edit_title').value = this.dataset.title || '';
        document.getElementById('edit_message').value = this.dataset.message || '';
        document.getElementById('edit_target').value = this.dataset.target || '';
        document.getElementById('edit_course').value = this.dataset.course || '';
        document.getElementById('edit_link').value = this.dataset.link || '';
        document.getElementById('edit_status').value = this.dataset.status || '';

        const preview = document.getElementById('edit_image_preview');
        const image = this.dataset.image || '';
        if (image) {
            preview.src = '../' + image;
            preview.style.display = 'block';
        } else {
            preview.src = '';
            preview.style.display = 'none';
        }

        const modalEl = document.getElementById('editModal');
        const modal = bootstrap.Modal.getOrCreateInstance(modalEl);
        modal.show();
    });
});
</script>
<?php require_once "../footer.php"; ?>
