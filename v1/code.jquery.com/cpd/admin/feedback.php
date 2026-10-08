<?php
require_once "../auth.php";
require_role('SUPPERADMIN');
require_once "../config.php";
require_once "../helpers.php";
require_once dirname(__DIR__, 3) . '/includes/pagination.php';

if ($conn instanceof mysqli) {
    cpd_ensure_feedback_table($conn);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    cpd_require_csrf();
}

/* =========================
   HELPERS
========================= */
if (!function_exists('h')) {
    function h($value) {
        return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
    }
}

function ratingBadgeClass($rating) {
    $rating = (int)$rating;

    if ($rating >= 5) return 'rating-5';
    if ($rating === 4) return 'rating-4';
    if ($rating === 3) return 'rating-3';
    if ($rating === 2) return 'rating-2';

    return 'rating-1';
}

/* =========================
   FLASH MESSAGE
========================= */
$msg = $_SESSION['feedback_msg'] ?? "";
$msg_type = $_SESSION['feedback_msg_type'] ?? "success";

unset($_SESSION['feedback_msg'], $_SESSION['feedback_msg_type']);

/* =========================
   DELETE FEEDBACK
========================= */
if (isset($_POST['delete_feedback'])) {
    $id = (int)($_POST['feedback_id'] ?? 0);

    $stmt = ($conn instanceof mysqli && cpd_table_exists($conn, 'feedback'))
        ? $conn->prepare("DELETE FROM feedback WHERE id = ? LIMIT 1")
        : false;

    if ($stmt) {
        $stmt->bind_param("i", $id);

        if ($stmt->execute()) {
            $_SESSION['feedback_msg'] = "Feedback deleted successfully.";
            $_SESSION['feedback_msg_type'] = "success";
        } else {
            $_SESSION['feedback_msg'] = "Failed to delete feedback.";
            $_SESSION['feedback_msg_type'] = "danger";
        }

        $stmt->close();
    } else {
        $_SESSION['feedback_msg'] = "Failed to prepare delete query.";
        $_SESSION['feedback_msg_type'] = "danger";
    }

    header("Location: feedback.php");
    exit;
}

/* =========================
   FILTERS
========================= */
$search  = trim($_GET['search'] ?? '');
$rating  = trim($_GET['rating'] ?? '');
$subject = trim($_GET['subject'] ?? '');

$where = " WHERE 1=1 ";
$params = [];
$types  = "";

if ($search !== '') {
    $where .= " AND (full_name LIKE ? OR email LIKE ? OR message LIKE ? OR subject LIKE ?) ";
    $like = "%{$search}%";

    $params[] = $like;
    $params[] = $like;
    $params[] = $like;
    $params[] = $like;

    $types .= "ssss";
}

if ($rating !== '' && is_numeric($rating)) {
    $where .= " AND rating = ? ";
    $params[] = (int)$rating;
    $types .= "i";
}

if ($subject !== '') {
    $where .= " AND subject = ? ";
    $params[] = $subject;
    $types .= "s";
}

/* =========================
   KPI STATS
========================= */
$k_total = 0;
$k_avg   = 0;
$k_5star = 0;
$k_today = 0;
$subject_stats = false;

$hasFeedback = $conn instanceof mysqli && cpd_table_exists($conn, 'feedback');
if ($hasFeedback) {
    try {
        $q = $conn->query("SELECT COUNT(*) AS c FROM feedback");
        if ($q) {
            $k_total = (int)($q->fetch_assoc()['c'] ?? 0);
        }

        $q = $conn->query("SELECT COALESCE(AVG(rating),0) AS a FROM feedback");
        if ($q) {
            $k_avg = round((float)($q->fetch_assoc()['a'] ?? 0), 1);
        }

        $q = $conn->query("SELECT COUNT(*) AS c FROM feedback WHERE rating = 5");
        if ($q) {
            $k_5star = (int)($q->fetch_assoc()['c'] ?? 0);
        }

        $q = $conn->query("SELECT COUNT(*) AS c FROM feedback WHERE DATE(created_at) = CURDATE()");
        if ($q) {
            $k_today = (int)($q->fetch_assoc()['c'] ?? 0);
        }

        $subject_stats = $conn->query("
            SELECT subject, COUNT(*) AS total
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

/* =========================
   FEEDBACK LIST
========================= */
$page = eca_pager_page();
$limit = eca_pager_limit();
$feedbackRows = [];
$feedbackTotal = 0;
$feedbackPage = $page;
$feedbackPages = 1;
$feedbackOffset = 0;
if ($hasFeedback) {
    $paged = eca_paged_query_mysqli(
        $conn,
        "SELECT COUNT(*) FROM feedback" . $where,
        "SELECT * FROM feedback" . $where,
        $types,
        $params,
        $page,
        $limit
    );
    $feedbackRows = $paged['rows'];
    $feedbackTotal = $paged['total'];
    $feedbackPage = $paged['page'];
    $feedbackPages = $paged['pages'];
    $feedbackOffset = $paged['offset'];
}

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
    radial-gradient(circle at top left, rgba(37,128,155,.16), transparent 28%),
    radial-gradient(circle at top right, rgba(13,79,156,.12), transparent 32%),
    linear-gradient(180deg,#f8fbff 0%, #eef3f8 100%) !important;
  color:var(--text);
}

.feedback-page{
  padding-bottom:34px;
}

/* =============================
   PAGE HEADER
============================= */
.feedback-header{
  background:rgba(255,255,255,.88);
  backdrop-filter:blur(16px);
  border:1px solid rgba(226,232,240,.88);
  border-radius:28px;
  padding:24px 26px;
  box-shadow:var(--shadow-soft);
  margin-bottom:24px !important;
  position:relative;
  overflow:hidden;
}

.feedback-header::before{
  content:"";
  position:absolute;
  top:0;
  left:0;
  width:100%;
  height:6px;
  background:linear-gradient(90deg,var(--brand),var(--brand-2),var(--brand-3));
}

.feedback-header::after{
  content:"";
  position:absolute;
  width:230px;
  height:230px;
  right:-90px;
  top:-120px;
  border-radius:50%;
  background:rgba(13,79,156,.08);
}

.feedback-header > div{
  position:relative;
  z-index:2;
}

.feedback-header h4{
  color:var(--ink);
  font-size:25px;
  font-weight:950 !important;
  letter-spacing:-.7px !important;
}

.feedback-header .text-muted{
  color:var(--muted) !important;
  font-weight:650;
  margin-top:4px;
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
  width:165px;
  height:165px;
  right:-65px;
  top:-65px;
  border-radius:50%;
  background:rgba(255,255,255,.16);
}

.kpi-card-premium::after{
  content:"";
  position:absolute;
  width:95px;
  height:95px;
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
  margin-bottom:18px;
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
   FILTER FORM
============================= */
.form-control-premium,
.form-select.form-control-premium{
  border-radius:16px !important;
  min-height:50px;
  border:1px solid #dbe5f0 !important;
  background:#fbfdff !important;
  color:var(--ink);
  font-weight:650;
  box-shadow:none !important;
  transition:.22s ease;
}

.form-control-premium:focus,
.form-select.form-control-premium:focus{
  background:#fff !important;
  border-color:var(--brand-2) !important;
  box-shadow:0 0 0 .24rem rgba(13,79,156,.12) !important;
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

.feedback-message{
  min-width:280px;
  line-height:1.65;
  color:#475569;
}

/* =============================
   SUBJECT BADGE
============================= */
.badge.text-bg-light{
  background:#f8fafc !important;
  color:#334155 !important;
  border:1px solid #e2e8f0;
  font-weight:850;
}

/* =============================
   RATING BADGES
============================= */
.rating-badge{
  display:inline-flex;
  align-items:center;
  gap:5px;
  padding:.52rem .86rem;
  border-radius:999px;
  font-weight:900;
  font-size:.78rem;
  border:1px solid transparent;
  white-space:nowrap;
}

.rating-5{
  background:#ecfdf5;
  color:#166534;
  border-color:#bbf7d0;
}

.rating-4{
  background:#eff6ff;
  color:#1d4ed8;
  border-color:#bfdbfe;
}

.rating-3{
  background:#fff7ed;
  color:#c2410c;
  border-color:#fed7aa;
}

.rating-2{
  background:#fef2f2;
  color:#b91c1c;
  border-color:#fecaca;
}

.rating-1{
  background:#111827;
  color:#fff;
  border-color:#111827;
}

/* =============================
   RIGHT SIDE INSIGHT TILES
============================= */
.quick-tile{
  position:relative;
  overflow:hidden;
  border-radius:28px;
  padding:24px;
  background:linear-gradient(145deg,#082b57,#0d4f9c);
  color:#fff;
  box-shadow:var(--shadow);
}

.quick-tile::before{
  content:"";
  position:absolute;
  width:160px;
  height:160px;
  right:-65px;
  top:-65px;
  border-radius:50%;
  background:rgba(255,255,255,.14);
}

.quick-tile .t{
  position:relative;
  z-index:2;
  font-size:18px;
  font-weight:950;
  letter-spacing:-.3px;
}

.quick-tile .s,
.quick-tile ul{
  position:relative;
  z-index:2;
  color:rgba(255,255,255,.82);
  line-height:1.7;
  font-weight:600;
}

.quick-tile ul{
  padding-left:18px;
}

.quick-tile li{
  margin-bottom:7px;
}

/* =============================
   SUBJECT BREAKDOWN
============================= */
.progress-brand{
  border-radius:999px;
  overflow:hidden;
  background:#e9eef5 !important;
}

.progress-brand .progress-bar{
  background:linear-gradient(90deg,var(--brand),var(--brand-2),var(--brand-3)) !important;
  border-radius:999px;
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

  .feedback-header{
    padding:20px;
  }
}

@media(max-width:575.98px){
  .feedback-header h4{
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

  .quick-tile{
    padding:20px;
  }
}
</style>

<div class="feedback-page">

  <div class="feedback-header d-flex justify-content-between align-items-center flex-wrap gap-2">
    <div>
      <h4 class="mb-0">
        <i class="fa-solid fa-comments me-2 text-primary"></i>
        Feedback Management
      </h4>
      <div class="text-muted">
        Review user experience, ratings, and submitted comments
      </div>
    </div>

    <div class="d-flex gap-2 flex-wrap">
      <a class="btn btn-brand btn-pill" href="/cpd/admin/dashboard.php">
        <i class="fa-solid fa-house me-2"></i>
        Dashboard
      </a>

      <a class="btn btn-soft btn-pill" href="/cpd/admin/support.php">
        <i class="fa-solid fa-headset me-2"></i>
        Support
      </a>
    </div>
  </div>

  <?php if ($msg): ?>
    <div class="alert <?= $msg_type === 'success' ? 'alert-success' : 'alert-danger' ?> border-0">
      <i class="fa-solid <?= $msg_type === 'success' ? 'fa-circle-check' : 'fa-triangle-exclamation' ?> me-2"></i>
      <?= h($msg) ?>
    </div>
  <?php endif; ?>

  <!-- KPI CARDS -->
  <div class="row g-3 kpi-grid">

    <div class="col-md-3">
      <div class="kpi-card-premium kpi-lead">
        <div class="kpi-top">
          <div>
            <div class="kpi-label">Total Feedback</div>
            <div class="kpi-value"><?= number_format($k_total) ?></div>
          </div>

          <div class="kpi-icon">
            <i class="fa-solid fa-comments"></i>
          </div>
        </div>

        <div class="kpi-foot">
          <span class="kpi-chip">
            <i class="fa-solid fa-circle-info me-1"></i>
            All time
          </span>
          <span>
            <i class="fa-regular fa-clock me-1"></i>
            Live
          </span>
        </div>
      </div>
    </div>

    <div class="col-md-3">
      <div class="kpi-card-premium kpi-soft">
        <div class="kpi-top">
          <div>
            <div class="kpi-label">Average Rating</div>
            <div class="kpi-value"><?= h($k_avg) ?></div>
          </div>

          <div class="kpi-icon">
            <i class="fa-solid fa-star"></i>
          </div>
        </div>

        <div class="kpi-foot">
          <span class="kpi-chip">
            <i class="fa-solid fa-ranking-star me-1"></i>
            1 to 5
          </span>
          <span>
            <i class="fa-solid fa-arrow-right me-1"></i>
            Quality
          </span>
        </div>
      </div>
    </div>

    <div class="col-md-3">
      <div class="kpi-card-premium kpi-red">
        <div class="kpi-top">
          <div>
            <div class="kpi-label">5-Star Reviews</div>
            <div class="kpi-value"><?= number_format($k_5star) ?></div>
          </div>

          <div class="kpi-icon">
            <i class="fa-solid fa-heart"></i>
          </div>
        </div>

        <div class="kpi-foot">
          <span class="kpi-chip">
            <i class="fa-solid fa-bolt me-1"></i>
            Positive
          </span>
          <span>
            <i class="fa-solid fa-arrow-right me-1"></i>
            Top rated
          </span>
        </div>
      </div>
    </div>

    <div class="col-md-3">
      <div class="kpi-card-premium kpi-dark">
        <div class="kpi-top">
          <div>
            <div class="kpi-label">Submitted Today</div>
            <div class="kpi-value"><?= number_format($k_today) ?></div>
          </div>

          <div class="kpi-icon">
            <i class="fa-solid fa-calendar-day"></i>
          </div>
        </div>

        <div class="kpi-foot">
          <span class="kpi-chip">
            <i class="fa-solid fa-chart-line me-1"></i>
            Daily
          </span>
          <span>
            <i class="fa-solid fa-filter me-1"></i>
            Monitor
          </span>
        </div>
      </div>
    </div>

  </div>

  <div class="row g-3 mt-3">

    <div class="col-lg-8">

      <!-- FILTER CARD -->
      <div class="card card-premium">
        <div class="card-body">

          <div class="card-section-head">
            <div class="title">
              <i class="fa-solid fa-filter me-2 text-primary"></i>
              Filter Feedback
            </div>
            <div class="text-muted small">
              Search by name, email, subject, or message
            </div>
          </div>

          <form method="GET" class="row g-2">
            <div class="col-md-5">
              <input 
                type="text" 
                name="search" 
                value="<?= h($search) ?>" 
                class="form-control form-control-premium" 
                placeholder="Search feedback..."
              >
            </div>

            <div class="col-md-3">
              <select name="subject" class="form-select form-control-premium">
                <option value="">All Subjects</option>
                <option value="Portal Experience" <?= $subject === 'Portal Experience' ? 'selected' : '' ?>>Portal Experience</option>
                <option value="Training Quality" <?= $subject === 'Training Quality' ? 'selected' : '' ?>>Training Quality</option>
                <option value="Course Content" <?= $subject === 'Course Content' ? 'selected' : '' ?>>Course Content</option>
                <option value="Certificates" <?= $subject === 'Certificates' ? 'selected' : '' ?>>Certificates</option>
                <option value="Support Service" <?= $subject === 'Support Service' ? 'selected' : '' ?>>Support Service</option>
                <option value="System Improvement" <?= $subject === 'System Improvement' ? 'selected' : '' ?>>System Improvement</option>
              </select>
            </div>

            <div class="col-md-2">
              <select name="rating" class="form-select form-control-premium">
                <option value="">All Ratings</option>
                <option value="5" <?= $rating === '5' ? 'selected' : '' ?>>5 Stars</option>
                <option value="4" <?= $rating === '4' ? 'selected' : '' ?>>4 Stars</option>
                <option value="3" <?= $rating === '3' ? 'selected' : '' ?>>3 Stars</option>
                <option value="2" <?= $rating === '2' ? 'selected' : '' ?>>2 Stars</option>
                <option value="1" <?= $rating === '1' ? 'selected' : '' ?>>1 Star</option>
              </select>
            </div>

            <div class="col-md-2 d-grid">
              <button class="btn btn-brand btn-pill">
                <i class="fa-solid fa-filter me-2"></i>
                Filter
              </button>
            </div>
          </form>

        </div>
      </div>

      <!-- LIST CARD -->
      <div class="card card-premium">
        <div class="card-body">

          <div class="card-section-head">
            <div class="title">
              <i class="fa-solid fa-list-check me-2 text-primary"></i>
              Feedback List
            </div>
            <div class="text-muted small">
              Latest submitted feedback from contractors
            </div>
          </div>

          <?php if ($feedbackTotal > 0): ?>
            <div class="table-responsive">
              <table class="table table-premium align-middle" data-dash-server-page="1">
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
                  <?php $n = $feedbackOffset + 1; ?>
                  <?php foreach ($feedbackRows as $row): ?>
                    <tr>
                      <td><?= $n++ ?></td>

                      <td>
                        <div class="fw-bold">
                          <?= h($row['full_name'] ?: 'Unknown User') ?>
                        </div>
                        <div class="text-muted small">
                          <?= h($row['email'] ?: 'No email') ?>
                        </div>
                      </td>

                      <td>
                        <span class="badge text-bg-light rounded-pill px-3 py-2">
                          <?= h($row['subject']) ?>
                        </span>
                      </td>

                      <td>
                        <span class="rating-badge <?= ratingBadgeClass($row['rating']) ?>">
                          <i class="fa-solid fa-star me-1"></i>
                          <?= (int)$row['rating'] ?>/5
                        </span>
                      </td>

                      <td class="feedback-message">
                        <?= nl2br(h($row['message'])) ?>
                      </td>

                      <td>
                        <div>
                          <?= h(date('d M Y', strtotime($row['created_at']))) ?>
                        </div>
                        <div class="text-muted small">
                          <?= h(date('h:i A', strtotime($row['created_at']))) ?>
                        </div>
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
                  <?php endforeach; ?>
                </tbody>
              </table>
            </div>
            <?php eca_render_request_pager($feedbackPage, $feedbackPages, $feedbackTotal, $limit); ?>
          <?php else: ?>
            <div class="empty-state text-center py-5 text-muted">
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
        <div class="t mb-1">
          <i class="fa-solid fa-lightbulb me-2"></i>
          Feedback Insight
        </div>
        <div class="s">
          Use ratings and message themes to identify portal pain points and training quality issues.
        </div>
      </div>

      <div class="card card-premium mb-3">
        <div class="card-body">

          <div class="card-section-head">
            <div class="title">
              <i class="fa-solid fa-chart-simple me-2 text-primary"></i>
              Subject Breakdown
            </div>
          </div>

          <?php if ($subject_stats && $subject_stats->num_rows > 0): ?>
            <?php while ($s = $subject_stats->fetch_assoc()): ?>
              <?php
                $subject_total = (int)($s['total'] ?? 0);
                $percentage = $k_total > 0 ? round(($subject_total / $k_total) * 100, 1) : 0;
              ?>

              <div class="mb-3">
                <div class="d-flex justify-content-between small mb-1">
                  <span class="fw-bold">
                    <?= h($s['subject'] ?: 'Unspecified') ?>
                  </span>
                  <span class="text-muted">
                    <?= number_format($subject_total) ?>
                  </span>
                </div>

                <div class="progress progress-brand" style="height:10px;">
                  <div class="progress-bar" style="width: <?= h($percentage) ?>%;"></div>
                </div>
              </div>
            <?php endwhile; ?>
          <?php else: ?>
            <div class="text-muted small">No feedback statistics available yet.</div>
          <?php endif; ?>

        </div>
      </div>

      <div class="quick-tile">
        <div class="t mb-1">
          <i class="fa-solid fa-screwdriver-wrench me-2"></i>
          Suggested Admin Actions
        </div>

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

</div>

<?php
require_once "../footer.php";
?>