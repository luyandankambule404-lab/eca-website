<?php
require_once "../auth.php";
require_role('SUPPERADMIN');
require_once "../config.php";

$msg = "";
$msg_type = "success";

/* =========================
   HELPER
========================= */
function h($value){
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}

function format_file_size($bytes){
    $bytes = (int)$bytes;

    if ($bytes >= 1048576) {
        return number_format($bytes / 1048576, 2) . " MB";
    }

    if ($bytes >= 1024) {
        return number_format($bytes / 1024, 2) . " KB";
    }

    return $bytes . " B";
}

/* =========================
   CREATE UPLOAD FOLDER
========================= */
$uploadDir = "../uploads/resources/";

if (!is_dir($uploadDir)) {
    mkdir($uploadDir, 0777, true);
}

/* =========================
   DELETE RESOURCE
========================= */
if (isset($_GET['delete']) && is_numeric($_GET['delete'])) {
    $id = (int)$_GET['delete'];

    $q = $conn->prepare("
        SELECT file_path 
        FROM course_resources 
        WHERE id = ? 
        LIMIT 1
    ");

    if ($q) {
        $q->bind_param("i", $id);
        $q->execute();
        $old = $q->get_result()->fetch_assoc();
        $q->close();

        if ($old && !empty($old['file_path']) && file_exists("../" . $old['file_path'])) {
            @unlink("../" . $old['file_path']);
        }
    }

    $stmt = $conn->prepare("
        DELETE FROM course_resources 
        WHERE id = ? 
        LIMIT 1
    ");

    if ($stmt) {
        $stmt->bind_param("i", $id);

        if ($stmt->execute()) {
            $msg = "Resource deleted successfully.";
            $msg_type = "success";
        } else {
            $msg = "Failed to delete resource.";
            $msg_type = "danger";
        }

        $stmt->close();
    } else {
        $msg = "Failed to prepare delete query.";
        $msg_type = "danger";
    }
}

/* =========================
   UPLOAD RESOURCE
========================= */
if (isset($_POST['upload_resource'])) {

    $course_id    = (int)($_POST['course_id'] ?? 0);
    $title        = trim($_POST['title'] ?? '');
    $category     = trim($_POST['category'] ?? '');
    $description  = trim($_POST['description'] ?? '');
    $status       = trim($_POST['status'] ?? 'Published');

    $maxSize = 2 * 1024 * 1024; // 2MB

    if (
        $course_id <= 0 ||
        $title === '' ||
        $category === '' ||
        empty($_FILES['resource_file']['name'])
    ) {
        $msg = "Please complete all required fields and choose a file.";
        $msg_type = "danger";
    } else {

        $originalName = $_FILES['resource_file']['name'];
        $tmpName      = $_FILES['resource_file']['tmp_name'];
        $fileSize     = (int)$_FILES['resource_file']['size'];
        $fileError    = (int)$_FILES['resource_file']['error'];
        $ext          = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));

        $allowed = [
            'pdf',
            'doc',
            'docx',
            'xls',
            'xlsx',
            'ppt',
            'pptx',
            'zip',
            'rar',
            'jpg',
            'jpeg',
            'png',
            'txt'
        ];

        if ($fileError !== UPLOAD_ERR_OK) {
            $msg = "File upload failed. Please try again.";
            $msg_type = "danger";
        } elseif ($fileSize > $maxSize) {
            $msg = "The uploaded file is too large. Maximum allowed size is 2MB.";
            $msg_type = "danger";
        } elseif (!in_array($ext, $allowed, true)) {
            $msg = "Invalid file type. Allowed: pdf, doc, docx, xls, xlsx, ppt, pptx, zip, rar, jpg, jpeg, png, txt.";
            $msg_type = "danger";
        } else {
            $safeName = time() . "_" . preg_replace('/[^A-Za-z0-9_\.-]/', '_', $originalName);
            $fullPath = $uploadDir . $safeName;
            $dbPath   = "uploads/resources/" . $safeName;

            if (move_uploaded_file($tmpName, $fullPath)) {

                $stmt = $conn->prepare("
                    INSERT INTO course_resources
                    (
                        course_id,
                        title,
                        category,
                        description,
                        file_path,
                        file_type,
                        file_size,
                        status,
                        created_at
                    )
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW())
                ");

                if ($stmt) {
                    $stmt->bind_param(
                        "isssssis",
                        $course_id,
                        $title,
                        $category,
                        $description,
                        $dbPath,
                        $ext,
                        $fileSize,
                        $status
                    );

                    if ($stmt->execute()) {
                        $msg = "Course resource uploaded successfully.";
                        $msg_type = "success";
                    } else {
                        $msg = "Upload saved file but failed to insert into database.";
                        $msg_type = "danger";
                    }

                    $stmt->close();
                } else {
                    $msg = "Failed to prepare upload query.";
                    $msg_type = "danger";
                }

            } else {
                $msg = "Failed to upload file.";
                $msg_type = "danger";
            }
        }
    }
}

/* =========================
   LOAD COURSES
========================= */
$courses = $conn->query("
    SELECT 
        id, 
        title, 
        venue, 
        start_date, 
        status
    FROM courses
    ORDER BY id DESC
");

/* =========================
   KPI STATS
========================= */
$total_resources = 0;
$published_count = 0;
$draft_count     = 0;
$pdf_count       = 0;

$q = $conn->query("SELECT COUNT(*) c FROM course_resources");
if ($q) {
    $total_resources = (int)$q->fetch_assoc()['c'];
}

$q = $conn->query("SELECT COUNT(*) c FROM course_resources WHERE status = 'Published'");
if ($q) {
    $published_count = (int)$q->fetch_assoc()['c'];
}

$q = $conn->query("SELECT COUNT(*) c FROM course_resources WHERE status = 'Draft'");
if ($q) {
    $draft_count = (int)$q->fetch_assoc()['c'];
}

$q = $conn->query("SELECT COUNT(*) c FROM course_resources WHERE file_type = 'pdf'");
if ($q) {
    $pdf_count = (int)$q->fetch_assoc()['c'];
}

/* =========================
   LIST RESOURCES
========================= */
$resources = $conn->query("
    SELECT 
        r.*, 
        c.title AS course_title
    FROM course_resources r
    LEFT JOIN courses c 
        ON r.course_id = c.id
    ORDER BY r.id DESC
    LIMIT 20
");

require_once "../header.php";
?>

<style>
:root{
  --brand:#082b57;
  --brand-2:#0d4f9c;
  --brand-3:#25809b;
  --red:#e3262e;
  --gold:#f6b731;
  --green:#16a34a;
  --ink:#0f172a;
  --text:#334155;
  --muted:#64748b;
  --line:#e2e8f0;
  --soft:#f6f9fd;
  --white:#ffffff;
  --shadow:0 22px 55px rgba(15,23,42,.10);
  --shadow-soft:0 12px 28px rgba(15,23,42,.07);
}

body{
  background:
    radial-gradient(circle at top left, rgba(37,128,155,.14), transparent 28%),
    radial-gradient(circle at top right, rgba(13,79,156,.10), transparent 30%),
    linear-gradient(180deg,#f8fbff 0%, #eef3f8 100%) !important;
  color:var(--text);
}

.resource-page{
  padding-bottom:36px;
}

/* =============================
   PAGE HEADER
============================= */
.resource-header{
  background:rgba(255,255,255,.88);
  backdrop-filter:blur(16px);
  border:1px solid rgba(226,232,240,.88);
  border-radius:28px;
  padding:24px 26px;
  box-shadow:var(--shadow-soft);
  margin-bottom:24px;
  position:relative;
  overflow:hidden;
}

.resource-header::before{
  content:"";
  position:absolute;
  top:0;
  left:0;
  width:100%;
  height:6px;
  background:linear-gradient(90deg,var(--brand),var(--brand-2),var(--brand-3));
}

.resource-header::after{
  content:"";
  position:absolute;
  width:220px;
  height:220px;
  right:-90px;
  top:-110px;
  border-radius:50%;
  background:rgba(13,79,156,.08);
}

.resource-header h4{
  color:var(--ink);
  font-size:25px;
  font-weight:950 !important;
  letter-spacing:-.6px !important;
  margin-bottom:4px;
}

.resource-header .text-muted{
  color:var(--muted) !important;
  font-weight:600;
}

/* =============================
   BUTTONS
============================= */
.btn-pill{
  border-radius:999px !important;
  min-height:44px;
  padding:10px 18px !important;
  font-weight:850 !important;
  letter-spacing:.1px;
}

.btn-brand{
  background:linear-gradient(135deg,var(--brand),var(--brand-2));
  color:#fff !important;
  border:none !important;
  box-shadow:0 12px 26px rgba(13,79,156,.22);
  transition:.25s ease;
}

.btn-brand:hover{
  transform:translateY(-2px);
  box-shadow:0 16px 36px rgba(13,79,156,.30);
  color:#fff !important;
}

.btn-soft{
  background:#f1f6fc !important;
  color:var(--brand) !important;
  border:1px solid #dce8f5 !important;
  font-weight:800 !important;
  transition:.25s ease;
}

.btn-soft:hover{
  background:#e6f0fb !important;
  color:var(--brand-2) !important;
  transform:translateY(-1px);
}

.btn-danger{
  background:linear-gradient(135deg,#c81e1e,var(--red)) !important;
  border:none !important;
  box-shadow:0 10px 22px rgba(227,38,46,.20);
  transition:.25s ease;
}

.btn-danger:hover{
  transform:translateY(-2px);
  box-shadow:0 16px 32px rgba(227,38,46,.28);
}

/* =============================
   ALERTS
============================= */
.alert{
  border-radius:18px !important;
  padding:16px 18px !important;
  font-weight:750;
  box-shadow:var(--shadow-soft);
}

.alert-success{
  background:linear-gradient(135deg,#16a34a,#22c55e) !important;
  color:#fff !important;
}

.alert-danger{
  background:linear-gradient(135deg,#dc2626,#ef4444) !important;
  color:#fff !important;
}

/* =============================
   KPI CARDS
============================= */
.kpi-grid{
  margin-top:4px;
}

.kpi-card-premium{
  position:relative;
  min-height:158px;
  overflow:hidden;
  border-radius:26px;
  padding:22px;
  color:#fff;
  box-shadow:var(--shadow);
  border:1px solid rgba(255,255,255,.18);
  transition:.28s ease;
}

.kpi-card-premium:hover{
  transform:translateY(-5px);
  box-shadow:0 28px 70px rgba(15,23,42,.16);
}

.kpi-card-premium::before{
  content:"";
  position:absolute;
  width:160px;
  height:160px;
  right:-65px;
  top:-65px;
  border-radius:50%;
  background:rgba(255,255,255,.16);
}

.kpi-card-premium::after{
  content:"";
  position:absolute;
  width:90px;
  height:90px;
  left:-45px;
  bottom:-45px;
  border-radius:50%;
  background:rgba(255,255,255,.10);
}

.kpi-lead{
  background:linear-gradient(145deg,#082b57,#0d4f9c);
}

.kpi-soft{
  background:linear-gradient(145deg,#0f766e,#14b8a6);
}

.kpi-red{
  background:linear-gradient(145deg,#991b1b,#e3262e);
}

.kpi-dark{
  background:linear-gradient(145deg,#111827,#334155);
}

.kpi-top{
  position:relative;
  z-index:2;
  display:flex;
  align-items:flex-start;
  justify-content:space-between;
  gap:16px;
}

.kpi-label{
  font-size:13px;
  font-weight:800;
  color:rgba(255,255,255,.78);
  text-transform:uppercase;
  letter-spacing:.7px;
}

.kpi-value{
  font-size:42px;
  line-height:1;
  font-weight:950;
  margin-top:10px;
  letter-spacing:-1px;
}

.kpi-icon{
  width:58px;
  height:58px;
  border-radius:20px;
  display:flex;
  align-items:center;
  justify-content:center;
  background:rgba(255,255,255,.16);
  border:1px solid rgba(255,255,255,.22);
  font-size:24px;
  flex-shrink:0;
}

.kpi-foot{
  position:relative;
  z-index:2;
  display:flex;
  align-items:center;
  justify-content:space-between;
  gap:10px;
  margin-top:20px;
  padding-top:14px;
  border-top:1px solid rgba(255,255,255,.18);
  color:rgba(255,255,255,.78);
  font-size:12px;
  font-weight:750;
}

.kpi-chip{
  display:inline-flex;
  align-items:center;
  padding:6px 10px;
  border-radius:999px;
  background:rgba(255,255,255,.14);
  color:#fff;
}

/* =============================
   MAIN CARDS
============================= */
.card-premium{
  border:none !important;
  border-radius:28px !important;
  box-shadow:var(--shadow);
  background:rgba(255,255,255,.92) !important;
  backdrop-filter:blur(18px);
  overflow:hidden;
}

.card-premium .card-body{
  padding:26px !important;
}

.card-section-head{
  padding-bottom:16px;
  margin-bottom:20px !important;
  border-bottom:1px solid var(--line);
}

.card-section-head .title{
  color:var(--ink);
  font-size:19px;
  font-weight:950 !important;
  letter-spacing:-.3px;
}

.card-premium .text-muted{
  color:var(--muted) !important;
}

/* =============================
   FORM CONTROLS
============================= */
.form-label{
  color:#1e293b;
  font-size:13px;
  font-weight:850 !important;
  margin-bottom:8px;
}

.form-control-premium,
.form-select.form-control-premium,
textarea.form-control-premium{
  border-radius:16px !important;
  min-height:50px;
  border:1px solid #dbe5f0 !important;
  background:#fbfdff !important;
  color:var(--ink);
  font-weight:600;
  box-shadow:none !important;
  transition:.22s ease;
}

textarea.form-control-premium{
  min-height:120px;
  resize:vertical;
}

.form-control-premium:focus,
.form-select.form-control-premium:focus{
  background:#fff !important;
  border-color:var(--brand-2) !important;
  box-shadow:0 0 0 .24rem rgba(13,79,156,.12) !important;
}

input[type="file"].form-control-premium{
  padding:13px;
  cursor:pointer;
}

input[type="file"].form-control-premium::file-selector-button{
  border:none;
  background:linear-gradient(135deg,var(--brand),var(--brand-2));
  color:#fff;
  border-radius:12px;
  padding:9px 14px;
  margin-right:12px;
  font-weight:800;
}

/* =============================
   TABLE
============================= */
.table-responsive{
  border-radius:22px;
  overflow:auto;
}

.table-premium{
  margin-bottom:0 !important;
  border-collapse:separate;
  border-spacing:0 10px;
}

.table-premium thead th{
  border:none !important;
  color:var(--muted);
  background:transparent;
  font-size:.76rem;
  font-weight:950;
  text-transform:uppercase;
  letter-spacing:.06em;
  padding:0 14px 8px;
  white-space:nowrap;
}

.table-premium tbody tr{
  background:#fff;
  box-shadow:0 8px 22px rgba(15,23,42,.045);
  transition:.22s ease;
}

.table-premium tbody tr:hover{
  transform:translateY(-2px);
  box-shadow:0 12px 28px rgba(15,23,42,.08);
}

.table-premium tbody td{
  padding:16px 14px !important;
  border-top:1px solid #edf2f7;
  border-bottom:1px solid #edf2f7;
  color:#334155;
  font-size:13px;
  vertical-align:middle !important;
}

.table-premium tbody td:first-child{
  border-left:1px solid #edf2f7;
  border-top-left-radius:18px;
  border-bottom-left-radius:18px;
  font-weight:900;
  color:var(--brand);
}

.table-premium tbody td:last-child{
  border-right:1px solid #edf2f7;
  border-top-right-radius:18px;
  border-bottom-right-radius:18px;
}

.table-premium .fw-bold{
  color:var(--ink);
  font-weight:900 !important;
}

.table-premium .small{
  line-height:1.45;
}

/* =============================
   BADGES
============================= */
.file-badge{
  display:inline-flex;
  align-items:center;
  gap:5px;
  padding:.52rem .86rem;
  border-radius:999px;
  background:#eff6ff;
  color:#1d4ed8;
  font-weight:900;
  font-size:.78rem;
  border:1px solid #dbeafe;
}

.status-badge{
  display:inline-flex;
  align-items:center;
  gap:6px;
  padding:.52rem .86rem;
  border-radius:999px;
  font-weight:900;
  font-size:.78rem;
}

.status-badge i{
  font-size:7px;
}

.status-published{
  background:#ecfdf5;
  color:#166534;
  border:1px solid #bbf7d0;
}

.status-draft{
  background:#fff7ed;
  color:#c2410c;
  border:1px solid #fed7aa;
}

.badge.text-bg-light{
  background:#f8fafc !important;
  color:#334155 !important;
  border:1px solid #e2e8f0;
  font-weight:850;
}

/* =============================
   EMPTY STATE
============================= */
.empty-state{
  background:linear-gradient(180deg,#fbfdff,#f6f9fd);
  border:1px dashed #cbd5e1;
  border-radius:24px;
  color:var(--muted) !important;
}

.empty-state i{
  color:var(--brand-2);
}

/* =============================
   RESPONSIVE
============================= */
@media(max-width:991.98px){
  .kpi-card-premium{
    min-height:140px;
  }

  .card-premium .card-body{
    padding:22px !important;
  }

  .resource-header{
    padding:20px;
  }
}

@media(max-width:575.98px){
  .resource-header h4{
    font-size:20px;
  }

  .kpi-value{
    font-size:34px;
  }

  .kpi-icon{
    width:50px;
    height:50px;
    font-size:20px;
  }

  .btn-pill{
    width:100%;
    justify-content:center;
  }
}
</style>

<div class="resource-page">

  <div class="resource-header d-flex justify-content-between align-items-center flex-wrap gap-2">
    <div>
      <h4>
        <i class="fa-solid fa-cloud-arrow-up me-2 text-primary"></i>
        Course Resources Upload
      </h4>
      <div class="text-muted">
        Upload manuals, templates, notes, slides, and files for CPD courses
      </div>
    </div>

    <div class="d-flex gap-2 flex-wrap">
      <a class="btn btn-brand btn-pill" href="/cpd/admin/dashboard.php">
        <i class="fa-solid fa-house me-2"></i>
        Dashboard
      </a>

      <a class="btn btn-soft btn-pill" href="/cpd/admin/resources.php">
        <i class="fa-solid fa-folder-open me-2"></i>
        Resources
      </a>
    </div>
  </div>

  <?php if ($msg): ?>
    <div class="alert <?= $msg_type === 'success' ? 'alert-success' : 'alert-danger' ?> border-0">
      <i class="fa-solid <?= $msg_type === 'success' ? 'fa-circle-check' : 'fa-triangle-exclamation' ?> me-2"></i>
      <?= h($msg) ?>
    </div>
  <?php endif; ?>

  <div class="row g-3 kpi-grid">

    <div class="col-md-3">
      <div class="kpi-card-premium kpi-lead">
        <div class="kpi-top">
          <div>
            <div class="kpi-label">Total Resources</div>
            <div class="kpi-value"><?= number_format($total_resources) ?></div>
          </div>

          <div class="kpi-icon">
            <i class="fa-solid fa-folder-tree"></i>
          </div>
        </div>

        <div class="kpi-foot">
          <span class="kpi-chip">
            <i class="fa-solid fa-database me-1"></i>
            Stored
          </span>
          <span>
            <i class="fa-solid fa-chart-line me-1"></i>
            Library
          </span>
        </div>
      </div>
    </div>

    <div class="col-md-3">
      <div class="kpi-card-premium kpi-soft">
        <div class="kpi-top">
          <div>
            <div class="kpi-label">Published</div>
            <div class="kpi-value"><?= number_format($published_count) ?></div>
          </div>

          <div class="kpi-icon">
            <i class="fa-solid fa-circle-check"></i>
          </div>
        </div>

        <div class="kpi-foot">
          <span class="kpi-chip">
            <i class="fa-solid fa-earth-africa me-1"></i>
            Visible
          </span>
          <span>
            <i class="fa-solid fa-arrow-right me-1"></i>
            Live
          </span>
        </div>
      </div>
    </div>

    <div class="col-md-3">
      <div class="kpi-card-premium kpi-red">
        <div class="kpi-top">
          <div>
            <div class="kpi-label">Drafts</div>
            <div class="kpi-value"><?= number_format($draft_count) ?></div>
          </div>

          <div class="kpi-icon">
            <i class="fa-solid fa-file-pen"></i>
          </div>
        </div>

        <div class="kpi-foot">
          <span class="kpi-chip">
            <i class="fa-solid fa-lock me-1"></i>
            Hidden
          </span>
          <span>
            <i class="fa-solid fa-arrow-right me-1"></i>
            Review
          </span>
        </div>
      </div>
    </div>

    <div class="col-md-3">
      <div class="kpi-card-premium kpi-dark">
        <div class="kpi-top">
          <div>
            <div class="kpi-label">PDF Files</div>
            <div class="kpi-value"><?= number_format($pdf_count) ?></div>
          </div>

          <div class="kpi-icon">
            <i class="fa-solid fa-file-pdf"></i>
          </div>
        </div>

        <div class="kpi-foot">
          <span class="kpi-chip">
            <i class="fa-solid fa-file-lines me-1"></i>
            Docs
          </span>
          <span>
            <i class="fa-solid fa-arrow-right me-1"></i>
            Popular
          </span>
        </div>
      </div>
    </div>

  </div>

  <div class="row g-3 mt-3">

    <div class="col-lg-5">
      <div class="card card-premium">
        <div class="card-body">

          <div class="card-section-head">
            <div class="title">
              <i class="fa-solid fa-upload me-2 text-primary"></i>
              Upload New Resource
            </div>
            <div class="text-muted small">
              Attach a file to a specific CPD course
            </div>
          </div>

          <form method="POST" enctype="multipart/form-data">

            <div class="mb-3">
              <label class="form-label">Course</label>
              <select name="course_id" class="form-select form-control-premium" required>
                <option value="">Select Course</option>

                <?php if ($courses && $courses->num_rows > 0): ?>
                  <?php while ($c = $courses->fetch_assoc()): ?>
                    <option value="<?= (int)$c['id'] ?>">
                      <?= h($c['title']) ?> — <?= h($c['status']) ?>
                    </option>
                  <?php endwhile; ?>
                <?php endif; ?>
              </select>
            </div>

            <div class="mb-3">
              <label class="form-label">Resource Title</label>
              <input 
                type="text" 
                name="title" 
                class="form-control form-control-premium" 
                placeholder="Enter resource title" 
                required
              >
            </div>

            <div class="row g-3">

              <div class="col-md-6">
                <label class="form-label">Category</label>
                <select name="category" class="form-select form-control-premium" required>
                  <option value="">Select Category</option>
                  <option value="Manual">Manual</option>
                  <option value="Guide">Guide</option>
                  <option value="Template">Template</option>
                  <option value="Policy">Policy</option>
                  <option value="Presentation">Presentation</option>
                  <option value="Form">Form</option>
                  <option value="Notes">Notes</option>
                  <option value="Checklist">Checklist</option>
                </select>
              </div>

              <div class="col-md-6">
                <label class="form-label">Status</label>
                <select name="status" class="form-select form-control-premium" required>
                  <option value="Published">Published</option>
                  <option value="Draft">Draft</option>
                </select>
              </div>

            </div>

            <div class="mt-3 mb-3">
              <label class="form-label">Description</label>
              <textarea 
                name="description" 
                class="form-control form-control-premium" 
                rows="4" 
                placeholder="Enter short description"
              ></textarea>
            </div>

            <div class="mb-3">
              <label class="form-label">
                <i class="fa-solid fa-cloud-arrow-up me-2 text-primary"></i>
                Upload File <span class="text-muted">(Max: 2MB)</span>
              </label>

              <input 
                type="file" 
                name="resource_file" 
                class="form-control form-control-premium"
                accept=".pdf,.doc,.docx,.xls,.xlsx,.ppt,.pptx,.zip,.rar,.jpg,.jpeg,.png,.txt"
                required
              >

              <div class="text-muted small mt-2">
                Allowed: pdf, doc, docx, xls, xlsx, ppt, pptx, zip, rar, jpg, jpeg, png, txt
              </div>
            </div>

            <button type="submit" name="upload_resource" class="btn btn-brand btn-pill px-4">
              <i class="fa-solid fa-upload me-2"></i>
              Upload Resource
            </button>

          </form>

        </div>
      </div>
    </div>

    <div class="col-lg-7">
      <div class="card card-premium">
        <div class="card-body">

          <div class="card-section-head d-flex justify-content-between align-items-center flex-wrap gap-2">
            <div>
              <div class="title">
                <i class="fa-solid fa-folder-open me-2 text-primary"></i>
                Recent Uploaded Resources
              </div>
              <div class="text-muted small">
                Latest files added to the course library
              </div>
            </div>
          </div>

          <?php if ($resources && $resources->num_rows > 0): ?>
            <div class="table-responsive">
              <table class="table table-premium align-middle">
                <thead>
                  <tr>
                    <th>#</th>
                    <th>Course</th>
                    <th>Title</th>
                    <th>Category</th>
                    <th>Type</th>
                    <th>Size</th>
                    <th>Status</th>
                    <th>File</th>
                    <th>Date</th>
                    <th width="110">Action</th>
                  </tr>
                </thead>

                <tbody>
                  <?php $n = 1; ?>
                  <?php while ($row = $resources->fetch_assoc()): ?>
                    <tr>
                      <td><?= $n++ ?></td>

                      <td>
                        <div class="fw-bold">
                          <?= h($row['course_title'] ?? 'Unknown Course') ?>
                        </div>
                      </td>

                      <td>
                        <div class="fw-bold">
                          <?= h($row['title']) ?>
                        </div>

                        <div class="text-muted small">
                          <?= h($row['description']) ?>
                        </div>
                      </td>

                      <td>
                        <span class="badge text-bg-light rounded-pill px-3 py-2">
                          <?= h($row['category']) ?>
                        </span>
                      </td>

                      <td>
                        <span class="file-badge">
                          <i class="fa-solid fa-file me-1"></i>
                          <?= strtoupper(h($row['file_type'])) ?>
                        </span>
                      </td>

                      <td>
                        <span class="text-muted small fw-bold">
                          <?= h(format_file_size($row['file_size'] ?? 0)) ?>
                        </span>
                      </td>

                      <td>
                        <span class="status-badge <?= $row['status'] === 'Published' ? 'status-published' : 'status-draft' ?>">
                          <i class="fa-solid fa-circle me-1"></i>
                          <?= h($row['status']) ?>
                        </span>
                      </td>

                      <td>
                        <a href="/cpd/<?= h($row['file_path']) ?>" target="_blank" class="btn btn-sm btn-soft rounded-pill px-3">
                          <i class="fa-solid fa-eye me-1"></i>
                          Open
                        </a>
                      </td>

                      <td>
                        <div><?= h(date('d M Y', strtotime($row['created_at']))) ?></div>
                        <div class="text-muted small">
                          <?= h(date('h:i A', strtotime($row['created_at']))) ?>
                        </div>
                      </td>

                      <td>
                        <a 
                          href="?delete=<?= (int)$row['id'] ?>"
                          class="btn btn-sm btn-danger rounded-pill px-3"
                          onclick="return confirm('Delete this resource?')"
                        >
                          <i class="fa-solid fa-trash"></i>
                        </a>
                      </td>
                    </tr>
                  <?php endwhile; ?>
                </tbody>
              </table>
            </div>
          <?php else: ?>
            <div class="empty-state text-center py-5 text-muted">
              <i class="fa-solid fa-folder-open mb-3" style="font-size:42px;"></i>
              <div class="fw-bold">No resources uploaded yet</div>
              <div class="small">Uploaded course files will appear here.</div>
            </div>
          <?php endif; ?>

        </div>
      </div>
    </div>

  </div>

</div>

<?php require_once "../footer.php"; ?>