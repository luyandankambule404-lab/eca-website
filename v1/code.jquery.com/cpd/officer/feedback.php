<?php
require_once "../auth.php";
require_role(['SUPPERADMIN', 'OFFICER']);
require_once "../config.php";
require_once "../helpers.php";

if ($conn instanceof mysqli) {
    cpd_ensure_feedback_table($conn);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    cpd_require_csrf();
}

/* ================= ACTIONS ================= */
$msg = "";
$msg_type = "success";

/* delete feedback */
if(isset($_POST['delete_feedback'])){
    $id = (int)($_POST['feedback_id'] ?? 0);

    $stmt = $conn->prepare("DELETE FROM feedback WHERE id=? LIMIT 1");
    $stmt->bind_param("i", $id);

    if($stmt->execute()){
        $msg = "Feedback deleted successfully.";
        $msg_type = "success";
    }else{
        $msg = "Failed to delete feedback.";
        $msg_type = "danger";
    }
}

/* ================= FILTERS ================= */
$search = trim($_GET['search'] ?? '');
$rating = trim($_GET['rating'] ?? '');
$subject = trim($_GET['subject'] ?? '');

$where = " WHERE 1=1 ";
$params = [];
$types  = "";

if($search !== ''){
    $where .= " AND (full_name LIKE ? OR email LIKE ? OR message LIKE ? OR subject LIKE ?) ";
    $like = "%{$search}%";
    $params[] = $like;
    $params[] = $like;
    $params[] = $like;
    $params[] = $like;
    $types .= "ssss";
}

if($rating !== '' && is_numeric($rating)){
    $where .= " AND rating = ? ";
    $params[] = (int)$rating;
    $types .= "i";
}

if($subject !== ''){
    $where .= " AND subject = ? ";
    $params[] = $subject;
    $types .= "s";
}

/* ================= KPIs ================= */
$k_total = 0;
$k_avg   = 0;
$k_5star = 0;
$k_today = 0;
$subject_stats = false;
$result = false;
$hasFeedback = $conn instanceof mysqli && cpd_table_exists($conn, 'feedback');

if ($hasFeedback) {
    try {
        $q = $conn->query("SELECT COUNT(*) c FROM feedback");
        if($q) $k_total = (int)$q->fetch_assoc()['c'];

        $q = $conn->query("SELECT COALESCE(AVG(rating),0) a FROM feedback");
        if($q) $k_avg = round((float)$q->fetch_assoc()['a'], 1);

        $q = $conn->query("SELECT COUNT(*) c FROM feedback WHERE rating=5");
        if($q) $k_5star = (int)$q->fetch_assoc()['c'];

        $q = $conn->query("SELECT COUNT(*) c FROM feedback WHERE DATE(created_at)=CURDATE()");
        if($q) $k_today = (int)$q->fetch_assoc()['c'];

        $subject_stats = $conn->query("
            SELECT subject, COUNT(*) total
            FROM feedback
            WHERE subject IS NOT NULL AND TRIM(subject) <> ''
            GROUP BY subject
            ORDER BY total DESC, subject ASC
            LIMIT 6
        ");
    } catch (Throwable $e) {
        $subject_stats = false;
        $hasFeedback = false;
    }
}

/* ================= FEEDBACK LIST ================= */
if ($hasFeedback) {
    $sql = "
        SELECT *
        FROM feedback
        $where
        ORDER BY created_at DESC, id DESC
    ";

    $stmt = $conn->prepare($sql);
    if ($stmt) {
        if($types !== ''){
            $stmt->bind_param($types, ...$params);
        }
        $stmt->execute();
        $result = $stmt->get_result();
    }
}

require_once "../header.php";
?>

<div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-3">
  <div>
    <h4 class="mb-0" style="font-weight:950; letter-spacing:-.3px;">Feedback Management</h4>
    <div class="text-muted">Review user experience, ratings, and submitted comments</div>
  </div>
  <div class="d-flex gap-2 flex-wrap">
    <a class="btn btn-brand btn-pill" href="/cpd/admin/dashboard.php">
      <i class="fa-solid fa-house me-2"></i>Dashboard
    </a>
    <a class="btn btn-soft btn-pill" href="/cpd/admin/support.php">
      <i class="fa-solid fa-headset me-2"></i>Support
    </a>
  </div>
</div>

<?php if($msg): ?>
  <div class="alert <?= $msg_type === 'success' ? 'alert-success' : 'alert-danger' ?> rounded-4 border-0 shadow-sm">
    <?= htmlspecialchars($msg) ?>
  </div>
<?php endif; ?>

<!-- KPI CARDS -->
<div class="row g-3 kpi-grid">
  <div class="col-md-3">
    <div class="kpi-card-premium kpi-lead">
      <div class="kpi-top">
        <div>
          <div class="kpi-label">Total Feedback</div>
          <div class="kpi-value"><?= e($k_total) ?></div>
        </div>
        <div class="kpi-icon"><i class="fa-solid fa-comments"></i></div>
      </div>
      <div class="kpi-foot">
        <span class="kpi-chip"><i class="fa-solid fa-circle-info me-1"></i>All time</span>
        <span><i class="fa-regular fa-clock me-1"></i>Live</span>
      </div>
    </div>
  </div>

  <div class="col-md-3">
    <div class="kpi-card-premium kpi-soft">
      <div class="kpi-top">
        <div>
          <div class="kpi-label">Average Rating</div>
          <div class="kpi-value"><?= e($k_avg) ?></div>
        </div>
        <div class="kpi-icon"><i class="fa-solid fa-star"></i></div>
      </div>
      <div class="kpi-foot">
        <span class="kpi-chip"><i class="fa-solid fa-ranking-star me-1"></i>1 to 5</span>
        <span><i class="fa-solid fa-arrow-right me-1"></i>Quality</span>
      </div>
    </div>
  </div>

  <div class="col-md-3">
    <div class="kpi-card-premium kpi-red">
      <div class="kpi-top">
        <div>
          <div class="kpi-label">5-Star Reviews</div>
          <div class="kpi-value"><?= e($k_5star) ?></div>
        </div>
        <div class="kpi-icon"><i class="fa-solid fa-heart"></i></div>
      </div>
      <div class="kpi-foot">
        <span class="kpi-chip"><i class="fa-solid fa-bolt me-1"></i>Positive</span>
        <span><i class="fa-solid fa-arrow-right me-1"></i>Top rated</span>
      </div>
    </div>
  </div>

  <div class="col-md-3">
    <div class="kpi-card-premium kpi-dark">
      <div class="kpi-top">
        <div>
          <div class="kpi-label">Submitted Today</div>
          <div class="kpi-value"><?= e($k_today) ?></div>
        </div>
        <div class="kpi-icon"><i class="fa-solid fa-calendar-day"></i></div>
      </div>
      <div class="kpi-foot">
        <span class="kpi-chip"><i class="fa-solid fa-chart-line me-1"></i>Daily</span>
        <span><i class="fa-solid fa-filter me-1"></i>Monitor</span>
      </div>
    </div>
  </div>
</div>

<div class="row g-3 mt-3">
  <div class="col-lg-8">
    <div class="card card-premium">
      <div class="card-body">
        <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-3">
          <div>
            <div style="font-weight:950;">Filter Feedback</div>
            <div class="text-muted small">Search by name, email, subject, or message</div>
          </div>
        </div>

        <form method="GET" class="row g-2">
          <div class="col-md-5">
            <input type="text" name="search" value="<?= htmlspecialchars($search) ?>" class="form-control form-control-premium" placeholder="Search feedback...">
          </div>

          <div class="col-md-3">
            <select name="subject" class="form-select form-control-premium">
              <option value="">All Subjects</option>
              <option value="Portal Experience" <?= $subject==='Portal Experience'?'selected':'' ?>>Portal Experience</option>
              <option value="Training Quality" <?= $subject==='Training Quality'?'selected':'' ?>>Training Quality</option>
              <option value="Course Content" <?= $subject==='Course Content'?'selected':'' ?>>Course Content</option>
              <option value="Certificates" <?= $subject==='Certificates'?'selected':'' ?>>Certificates</option>
              <option value="Support Service" <?= $subject==='Support Service'?'selected':'' ?>>Support Service</option>
              <option value="System Improvement" <?= $subject==='System Improvement'?'selected':'' ?>>System Improvement</option>
            </select>
          </div>

          <div class="col-md-2">
            <select name="rating" class="form-select form-control-premium">
              <option value="">All Ratings</option>
              <option value="5" <?= $rating==='5'?'selected':'' ?>>5 Stars</option>
              <option value="4" <?= $rating==='4'?'selected':'' ?>>4 Stars</option>
              <option value="3" <?= $rating==='3'?'selected':'' ?>>3 Stars</option>
              <option value="2" <?= $rating==='2'?'selected':'' ?>>2 Stars</option>
              <option value="1" <?= $rating==='1'?'selected':'' ?>>1 Star</option>
            </select>
          </div>

          <div class="col-md-2 d-grid">
            <button class="btn btn-brand btn-pill">
              <i class="fa-solid fa-filter me-2"></i>Filter
            </button>
          </div>
        </form>
      </div>
    </div>

    <div class="card card-premium">
      <div class="card-body">
        <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-3">
          <div>
            <div style="font-weight:950;">Feedback List</div>
            <div class="text-muted small">Latest submitted feedback from contractors</div>
          </div>
        </div>

        <?php if($result && $result->num_rows > 0): ?>
          <div class="table-responsive">
            <table class="table table-premium align-middle">
              <thead>
                <tr>
                  <th>#</th>
                  <th>User</th>
                  <th>Subject</th>
                  <th>Rating</th>
                  <th>Message</th>
                  <th>Date</th>
                  <th width="90">Action</th>
                </tr>
              </thead>
              <tbody>
                <?php $n = 1; while($row = $result->fetch_assoc()): ?>
                  <tr>
                    <td><?= $n++ ?></td>
                    <td>
                      <div class="fw-bold"><?= htmlspecialchars($row['full_name']) ?></div>
                      <div class="text-muted small"><?= htmlspecialchars($row['email']) ?></div>
                    </td>
                    <td>
                      <span class="badge text-bg-light rounded-pill px-3 py-2">
                        <?= htmlspecialchars($row['subject']) ?>
                      </span>
                    </td>
                    <td>
                      <span class="rating-badge rating-<?= (int)$row['rating'] ?>">
                        <i class="fa-solid fa-star me-1"></i><?= (int)$row['rating'] ?>/5
                      </span>
                    </td>
                    <td style="min-width:280px;">
                      <?= nl2br(htmlspecialchars($row['message'])) ?>
                    </td>
                    <td>
                      <div><?= date('d M Y', strtotime($row['created_at'])) ?></div>
                      <div class="text-muted small"><?= date('h:i A', strtotime($row['created_at'])) ?></div>
                    </td>
                    <td>
                      <form method="POST" class="d-inline" onsubmit="return confirm('Delete this feedback?')">
                        <?= cpd_csrf_input() ?>
                        <input type="hidden" name="feedback_id" value="<?= (int)$row['id'] ?>">
                        <button type="submit" name="delete_feedback" class="btn btn-sm btn-danger rounded-pill px-3">
                          <i class="fa-solid fa-trash"></i>
                        </button>
                      </form>
                    </td>
                  </tr>
                <?php endwhile; ?>
              </tbody>
            </table>
          </div>
        <?php else: ?>
          <div class="text-center py-5 text-muted">
            <i class="fa-regular fa-comment-dots mb-3" style="font-size:42px;"></i>
            <div class="fw-bold">No feedback found</div>
            <div class="small">There are no feedback entries matching your filter.</div>
          </div>
        <?php endif; ?>
      </div>
    </div>
  </div>

  <div class="col-lg-4">
    <div class="quick-tile mb-3">
      <div class="t mb-1">Feedback Insight</div>
      <div class="s">Use ratings and message themes to identify portal pain points and training quality issues.</div>
    </div>

    <div class="card card-premium mb-3">
      <div class="card-body">
        <div style="font-weight:950;" class="mb-3">Subject Breakdown</div>

        <?php if($subject_stats && $subject_stats->num_rows > 0): ?>
          <?php while($s = $subject_stats->fetch_assoc()): ?>
            <div class="mb-3">
              <div class="d-flex justify-content-between small mb-1">
                <span class="fw-bold"><?= htmlspecialchars($s['subject']) ?></span>
                <span class="text-muted"><?= (int)$s['total'] ?></span>
              </div>
              <div class="progress progress-brand" style="height:10px;">
                <div class="progress-bar"
                     style="width: <?= $k_total > 0 ? round(($s['total'] / $k_total) * 100,1) : 0 ?>%;"></div>
              </div>
            </div>
          <?php endwhile; ?>
        <?php else: ?>
          <div class="text-muted small">No feedback statistics available yet.</div>
        <?php endif; ?>
      </div>
    </div>

    <div class="quick-tile">
      <div class="t mb-1">Suggested Admin Actions</div>
      <ul class="small mb-0 mt-2">
        <li>Add <b>feedback status</b>: New / Reviewed / Actioned</li>
        <li>Add <b>admin response</b> for internal notes</li>
        <li>Track <b>course-specific feedback</b> by course ID</li>
        <li>Show <b>rating trend chart</b> by month</li>
        <li>Export feedback to <b>Excel / PDF</b></li>
      </ul>
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

.rating-badge{
  display:inline-block;
  padding:.48rem .8rem;
  border-radius:999px;
  font-weight:800;
  font-size:.82rem;
}
.rating-5{ background:#ecfdf5; color:#166534; }
.rating-4{ background:#eff6ff; color:#1d4ed8; }
.rating-3{ background:#fff7ed; color:#c2410c; }
.rating-2{ background:#fef2f2; color:#b91c1c; }
.rating-1{ background:#111827; color:#fff; }
</style>

<?php require_once "../footer.php"; ?>