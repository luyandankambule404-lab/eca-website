<?php
require_once "../auth.php";
require_role('ADMIN');
require_once "../config.php";

$msg = "";
$msg_type = "success";

/* =========================
   CREATE UPLOAD FOLDER
========================= */
$uploadDir = "../uploads/resources/";
if(!is_dir($uploadDir)){
    mkdir($uploadDir, 0777, true);
}

/* =========================
   DELETE RESOURCE
========================= */
if(isset($_GET['delete']) && is_numeric($_GET['delete'])){
    $id = (int)$_GET['delete'];

    $q = $conn->prepare("SELECT file_path FROM course_resources WHERE id=? LIMIT 1");
    $q->bind_param("i", $id);
    $q->execute();
    $old = $q->get_result()->fetch_assoc();

    if($old && !empty($old['file_path']) && file_exists("../".$old['file_path'])){
        @unlink("../".$old['file_path']);
    }

    $stmt = $conn->prepare("DELETE FROM course_resources WHERE id=? LIMIT 1");
    $stmt->bind_param("i", $id);

    if($stmt->execute()){
        $msg = "Resource deleted successfully.";
        $msg_type = "success";
    }else{
        $msg = "Failed to delete resource.";
        $msg_type = "danger";
    }
}

/* =========================
   UPLOAD RESOURCE
========================= */
if(isset($_POST['upload_resource'])){

    $course_id    = (int)($_POST['course_id'] ?? 0);
    $title        = trim($_POST['title'] ?? '');
    $category     = trim($_POST['category'] ?? '');
    $description  = trim($_POST['description'] ?? '');
    $status       = trim($_POST['status'] ?? 'Published');

    if($course_id <= 0 || $title === '' || $category === '' || empty($_FILES['resource_file']['name'])){
        $msg = "Please complete all required fields and choose a file.";
        $msg_type = "danger";
    }else{

        $originalName = $_FILES['resource_file']['name'];
        $tmpName      = $_FILES['resource_file']['tmp_name'];
        $fileSize     = $_FILES['resource_file']['size'];
        $ext          = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));
        $safeName     = time() . "_" . preg_replace('/[^A-Za-z0-9_\.-]/', '_', $originalName);
        $fullPath     = $uploadDir . $safeName;
        $dbPath       = "uploads/resources/" . $safeName;

        $allowed = ['pdf','doc','docx','xls','xlsx','ppt','pptx','zip','rar','jpg','jpeg','png','txt'];
        if(!in_array($ext, $allowed)){
            $msg = "Invalid file type. Allowed: pdf, doc, docx, xls, xlsx, ppt, pptx, zip, rar, jpg, jpeg, png, txt.";
            $msg_type = "danger";
        }else{
            if(move_uploaded_file($tmpName, $fullPath)){

                $stmt = $conn->prepare("
                    INSERT INTO course_resources
                    (course_id, title, category, description, file_path, file_type, file_size, status, created_at)
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW())
                ");
                $stmt->bind_param(
                    "isssssss",
                    $course_id,
                    $title,
                    $category,
                    $description,
                    $dbPath,
                    $ext,
                    $fileSize,
                    $status
                );

                if($stmt->execute()){
                    $msg = "Course resource uploaded successfully.";
                    $msg_type = "success";
                }else{
                    $msg = "Upload saved file but failed to insert into database.";
                    $msg_type = "danger";
                }
            }else{
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
    SELECT id, title, venue, start_date, status
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
if($q) $total_resources = (int)$q->fetch_assoc()['c'];

$q = $conn->query("SELECT COUNT(*) c FROM course_resources WHERE status='Published'");
if($q) $published_count = (int)$q->fetch_assoc()['c'];

$q = $conn->query("SELECT COUNT(*) c FROM course_resources WHERE status='Draft'");
if($q) $draft_count = (int)$q->fetch_assoc()['c'];

$q = $conn->query("SELECT COUNT(*) c FROM course_resources WHERE file_type='pdf'");
if($q) $pdf_count = (int)$q->fetch_assoc()['c'];

/* =========================
   LIST RESOURCES
========================= */
$resources = $conn->query("
    SELECT r.*, c.title AS course_title
    FROM course_resources r
    LEFT JOIN courses c ON r.course_id = c.id
    ORDER BY r.id DESC
    LIMIT 20
");

require_once "../header.php";
?>

<div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-3">
  <div>
    <h4 class="mb-0" style="font-weight:950; letter-spacing:-.3px;">Course Resources Upload</h4>
    <div class="text-muted">Upload manuals, templates, notes, slides, and files for CPD courses</div>
  </div>
  <div class="d-flex gap-2 flex-wrap">
    <a class="btn btn-brand btn-pill" href="/cpd/admin/dashboard.php">
      <i class="fa-solid fa-house me-2"></i>Dashboard
    </a>
    <a class="btn btn-soft btn-pill" href="/cpd/admin/resources.php">
      <i class="fa-solid fa-folder-open me-2"></i>Resources
    </a>
  </div>
</div>

<?php if($msg): ?>
  <div class="alert <?= $msg_type === 'success' ? 'alert-success' : 'alert-danger' ?> rounded-4 border-0 shadow-sm">
    <?= htmlspecialchars($msg) ?>
  </div>
<?php endif; ?>

<div class="row g-3 kpi-grid">
  <div class="col-md-3">
    <div class="kpi-card-premium kpi-lead">
      <div class="kpi-top">
        <div>
          <div class="kpi-label">Total Resources</div>
          <div class="kpi-value"><?= $total_resources ?></div>
        </div>
        <div class="kpi-icon"><i class="fa-solid fa-folder-tree"></i></div>
      </div>
      <div class="kpi-foot">
        <span class="kpi-chip"><i class="fa-solid fa-database me-1"></i>Stored</span>
        <span><i class="fa-solid fa-chart-line me-1"></i>Library</span>
      </div>
    </div>
  </div>

  <div class="col-md-3">
    <div class="kpi-card-premium kpi-soft">
      <div class="kpi-top">
        <div>
          <div class="kpi-label">Published</div>
          <div class="kpi-value"><?= $published_count ?></div>
        </div>
        <div class="kpi-icon"><i class="fa-solid fa-circle-check"></i></div>
      </div>
      <div class="kpi-foot">
        <span class="kpi-chip"><i class="fa-solid fa-earth-africa me-1"></i>Visible</span>
        <span><i class="fa-solid fa-arrow-right me-1"></i>Live</span>
      </div>
    </div>
  </div>

  <div class="col-md-3">
    <div class="kpi-card-premium kpi-red">
      <div class="kpi-top">
        <div>
          <div class="kpi-label">Drafts</div>
          <div class="kpi-value"><?= $draft_count ?></div>
        </div>
        <div class="kpi-icon"><i class="fa-solid fa-file-pen"></i></div>
      </div>
      <div class="kpi-foot">
        <span class="kpi-chip"><i class="fa-solid fa-lock me-1"></i>Hidden</span>
        <span><i class="fa-solid fa-arrow-right me-1"></i>Review</span>
      </div>
    </div>
  </div>

  <div class="col-md-3">
    <div class="kpi-card-premium kpi-dark">
      <div class="kpi-top">
        <div>
          <div class="kpi-label">PDF Files</div>
          <div class="kpi-value"><?= $pdf_count ?></div>
        </div>
        <div class="kpi-icon"><i class="fa-solid fa-file-pdf"></i></div>
      </div>
      <div class="kpi-foot">
        <span class="kpi-chip"><i class="fa-solid fa-file-lines me-1"></i>Docs</span>
        <span><i class="fa-solid fa-arrow-right me-1"></i>Popular</span>
      </div>
    </div>
  </div>
</div>

<div class="row g-3 mt-3">
  <div class="col-lg-5">
    <div class="card card-premium">
      <div class="card-body">
        <div class="mb-3">
          <div style="font-weight:950;">Upload New Resource</div>
          <div class="text-muted small">Attach a file to a specific course</div>
        </div>

        <form method="POST" enctype="multipart/form-data">
          <div class="mb-3">
            <label class="form-label fw-bold">Course</label>
            <select name="course_id" class="form-select form-control-premium" required>
              <option value="">Select Course</option>
              <?php if($courses && $courses->num_rows > 0): ?>
                <?php while($c = $courses->fetch_assoc()): ?>
                  <option value="<?= (int)$c['id'] ?>">
                    <?= htmlspecialchars($c['title']) ?> — <?= htmlspecialchars($c['status']) ?>
                  </option>
                <?php endwhile; ?>
              <?php endif; ?>
            </select>
          </div>

          <div class="mb-3">
            <label class="form-label fw-bold">Resource Title</label>
            <input type="text" name="title" class="form-control form-control-premium" placeholder="Enter resource title" required>
          </div>

          <div class="row g-3">
            <div class="col-md-6">
              <label class="form-label fw-bold">Category</label>
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
              <label class="form-label fw-bold">Status</label>
              <select name="status" class="form-select form-control-premium" required>
                <option value="Published">Published</option>
                <option value="Draft">Draft</option>
              </select>
            </div>
          </div>

          <div class="mt-3 mb-3">
            <label class="form-label fw-bold">Description</label>
            <textarea name="description" class="form-control form-control-premium" rows="4" placeholder="Enter short description"></textarea>
          </div>

     <div class="mb-3">
    <label class="form-label fw-bold">Upload File (Max: 2MB)</label>
    
    <input type="file" 
           name="resource_file" 
           class="form-control form-control-premium"
           accept=".pdf,.doc,.docx,.xls,.xlsx,.ppt,.pptx,.zip,.rar,.jpg,.jpeg,.png,.txt"
           required>

    <div class="text-muted small mt-2">
        Allowed: pdf, doc, docx, xls, xlsx, ppt, pptx, zip, rar, jpg, jpeg, png, txt <br>
        Max size: 2MB
    </div>
</div>

          <button type="submit" name="upload_resource" class="btn btn-brand btn-pill px-4">
            <i class="fa-solid fa-upload me-2"></i>Upload Resource
          </button>
        </form>
      </div>
    </div>
  </div>

  <div class="col-lg-7">
    <div class="card card-premium">
      <div class="card-body">
        <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-3">
          <div>
            <div style="font-weight:950;">Recent Uploaded Resources</div>
            <div class="text-muted small">Latest files added to course library</div>
          </div>
        </div>

        <?php if($resources && $resources->num_rows > 0): ?>
          <div class="table-responsive">
            <table class="table table-premium align-middle">
              <thead>
                <tr>
                  <th>#</th>
                  <th>Course</th>
                  <th>Title</th>
                  <th>Category</th>
                  <th>Type</th>
                  <th>Status</th>
                  <th>File</th>
                  <th>Date</th>
                  <th width="120">Action</th>
                </tr>
              </thead>
              <tbody>
                <?php $n=1; while($row = $resources->fetch_assoc()): ?>
                  <tr>
                    <td><?= $n++ ?></td>
                    <td>
                      <div class="fw-bold"><?= htmlspecialchars($row['course_title'] ?? 'Unknown Course') ?></div>
                    </td>
                    <td>
                      <div class="fw-bold"><?= htmlspecialchars($row['title']) ?></div>
                      <div class="text-muted small"><?= htmlspecialchars($row['description']) ?></div>
                    </td>
                    <td>
                      <span class="badge text-bg-light rounded-pill px-3 py-2"><?= htmlspecialchars($row['category']) ?></span>
                    </td>
                    <td>
                      <span class="file-badge">
                        <i class="fa-solid fa-file me-1"></i><?= strtoupper(htmlspecialchars($row['file_type'])) ?>
                      </span>
                    </td>
                    <td>
                      <span class="status-badge <?= $row['status']==='Published' ? 'status-published' : 'status-draft' ?>">
                        <i class="fa-solid fa-circle me-1"></i><?= htmlspecialchars($row['status']) ?>
                      </span>
                    </td>
                    <td>
                      <a href="/cpd/<?= htmlspecialchars($row['file_path']) ?>" target="_blank" class="btn btn-sm btn-soft rounded-pill px-3">
                        <i class="fa-solid fa-eye me-1"></i>Open
                      </a>
                    </td>
                    <td>
                      <div><?= date('d M Y', strtotime($row['created_at'])) ?></div>
                      <div class="text-muted small"><?= date('h:i A', strtotime($row['created_at'])) ?></div>
                    </td>
                    <td>
                      <a href="?delete=<?= (int)$row['id'] ?>"
                         class="btn btn-sm btn-danger rounded-pill px-3"
                         onclick="return confirm('Delete this resource?')">
                         <i class="fa-solid fa-trash"></i>
                      </a>
                    </td>
                  </tr>
                <?php endwhile; ?>
              </tbody>
            </table>
          </div>
        <?php else: ?>
          <div class="text-center py-5 text-muted">
            <i class="fa-solid fa-folder-open mb-3" style="font-size:42px;"></i>
            <div class="fw-bold">No resources uploaded yet</div>
            <div class="small">Uploaded course files will appear here.</div>
          </div>
        <?php endif; ?>
      </div>
    </div>
  </div>
</div>

<style>
.form-control-premium{
  border-radius:16px !important;
  min-height:48px;
  border:1px solid rgba(8,43,87,.08);
  box-shadow:none;
}
.form-control-premium:focus{
  border-color:#0d4f9c;
  box-shadow:0 0 0 0.2rem rgba(13,79,156,.10);
}
.table-premium thead th{
  border-bottom:0;
  color:#64748b;
  font-size:.82rem;
  font-weight:800;
  text-transform:uppercase;
  letter-spacing:.04em;
}
.table-premium tbody tr{
  vertical-align:top;
}
.table-premium tbody td{
  padding-top:1rem;
  padding-bottom:1rem;
}
.file-badge{
  display:inline-block;
  padding:.45rem .8rem;
  border-radius:999px;
  background:#eff6ff;
  color:#1d4ed8;
  font-weight:800;
  font-size:.82rem;
}
.status-badge{
  display:inline-block;
  padding:.45rem .8rem;
  border-radius:999px;
  font-weight:800;
  font-size:.82rem;
}
.status-published{ background:#ecfdf5; color:#166534; }
.status-draft{ background:#fff7ed; color:#c2410c; }
</style>

<?php require_once "../footer.php"; ?>