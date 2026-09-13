<?php
require_once "../auth.php";
require_role('ADMIN');

/* ================= KPIs ================= */
$k_contractors = (int)$conn->query("SELECT COUNT(*) c FROM user WHERE role='CONTRACTOR'")->fetch_assoc()['c'];
$k_courses     = (int)$conn->query("SELECT COUNT(*) c FROM courses")->fetch_assoc()['c'];
$k_pending     = (int)$conn->query("SELECT COUNT(*) c FROM course_applications WHERE status='PENDING'")->fetch_assoc()['c'];
$k_points      = (float)$conn->query("SELECT COALESCE(SUM(points),0) s FROM cpd_points_ledger")->fetch_assoc()['s'];

/* ================= PROGRESS (12 points / 3 years) =================
   Assumption: cpd_points_ledger has a date column. Try common names.
   If your date column is different, change $ledgerDateCol to your real column.
*/
$ledgerDateCol = 'created_at';
$cols = $conn->query("SHOW COLUMNS FROM cpd_points_ledger");
$dateCols = [];
while($c = $cols->fetch_assoc()){
  $dateCols[] = $c['Field'];
}
if(in_array('issued_at', $dateCols)) $ledgerDateCol = 'issued_at';
elseif(in_array('date', $dateCols)) $ledgerDateCol = 'date';
elseif(in_array('created_at', $dateCols)) $ledgerDateCol = 'created_at';

/* last 3 years window */
$points_3y = 0.0;
$sql3y = "SELECT COALESCE(SUM(points),0) s
          FROM cpd_points_ledger
          WHERE $ledgerDateCol >= (NOW() - INTERVAL 3 YEAR)";
$points_3y = (float)$conn->query($sql3y)->fetch_assoc()['s'];

/* trainings attended in last 3 years
   Assumption: attendance table exists with attended flag + date.
   If your attendance table/columns differ, update this query.
*/
$trainings_3y = 0;
$hasAttendance = $conn->query("SHOW TABLES LIKE 'attendance'")->num_rows > 0;
if($hasAttendance){
  // Try common date column names
  $attDateCol = 'created_at';
  $attCols = $conn->query("SHOW COLUMNS FROM attendance");
  $attFields = [];
  while($ac = $attCols->fetch_assoc()){
    $attFields[] = $ac['Field'];
  }
  if(in_array('attended_at', $attFields)) $attDateCol = 'attended_at';
  elseif(in_array('date', $attFields)) $attDateCol = 'date';
  elseif(in_array('created_at', $attFields)) $attDateCol = 'created_at';

  // Try common “attended” flag names
  $attFlag = null;
  foreach(['status','present','is_present','attended'] as $f){
    if(in_array($f, $attFields)){ $attFlag = $f; break; }
  }

  if($attFlag === 'status'){
    $trainings_3y = (int)$conn->query("SELECT COUNT(*) c FROM attendance WHERE $attDateCol >= (NOW() - INTERVAL 3 YEAR) AND status='PRESENT'")->fetch_assoc()['c'];
  } elseif(in_array($attFlag, ['present','is_present','attended'])){
    $trainings_3y = (int)$conn->query("SELECT COUNT(*) c FROM attendance WHERE $attDateCol >= (NOW() - INTERVAL 3 YEAR) AND $attFlag=1")->fetch_assoc()['c'];
  } else {
    // fallback: count all rows in last 3 years
    $trainings_3y = (int)$conn->query("SELECT COUNT(*) c FROM attendance WHERE $attDateCol >= (NOW() - INTERVAL 3 YEAR)")->fetch_assoc()['c'];
  }
}

/* target */
$target_points = 36.0;
$progress_pct  = ($target_points > 0) ? min(100, round(($points_3y / $target_points) * 100, 1)) : 0;

require_once "../header.php";
?>

<div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-3">
  <div>
    <p class="eca-kicker">CPD office</p>
    <h4 class="mb-0" style="font-weight:800; letter-spacing:-.03em; color:#000066;">Officer dashboard</h4>
    <div class="text-muted">Welcome, <?= e($_SESSION['full_name'] ?? 'Officer') ?></div>
  </div>
  <div class="d-flex gap-2 flex-wrap">
    <a class="btn btn-brand btn-pill" href="/cpd/admin/courses.php"><i class="fa-solid fa-graduation-cap me-2"></i>Manage Courses</a>
    <a class="btn btn-soft btn-pill" href="/cpd/admin/applications.php"><i class="fa-solid fa-file-circle-check me-2"></i>Applications</a>
    <a class="btn btn-soft btn-pill" href="/cpd/admin/attendance.php"><i class="fa-solid fa-user-check me-2"></i>Attendance</a>
  </div>
</div>

<!-- KPI CARDS -->
<div class="row g-3 kpi-grid">
  <div class="col-md-3">
    <div class="kpi-card-premium kpi-dark">
      <div class="kpi-top">
        <div>
          <div class="kpi-label">Contractors</div>
          <div class="kpi-value"><?= e($k_contractors) ?></div>
        </div>
        <div class="kpi-icon"><i class="fa-solid fa-people-group"></i></div>
      </div>
      <div class="kpi-foot">
        <span class="kpi-chip"><i class="fa-solid fa-circle-info me-1"></i>Total</span>
        <span><i class="fa-regular fa-clock me-1"></i>Live</span>
      </div>
    </div>
  </div>

  <div class="col-md-3">
    <div class="kpi-card-premium kpi-dark">
      <div class="kpi-top">
        <div>
          <div class="kpi-label">Courses</div>
          <div class="kpi-value"><?= e($k_courses) ?></div>
        </div>
        <div class="kpi-icon"><i class="fa-solid fa-book-open-reader"></i></div>
      </div>
      <div class="kpi-foot">
        <span class="kpi-chip"><i class="fa-solid fa-plus me-1"></i>Create</span>
        <span><i class="fa-solid fa-arrow-right me-1"></i>Manage</span>
      </div>
    </div>
  </div>

  <div class="col-md-3">
    <div class="kpi-card-premium kpi-dark">
      <div class="kpi-top">
        <div>
          <div class="kpi-label">Pending Apps</div>
          <div class="kpi-value"><?= e($k_pending) ?></div>
        </div>
        <div class="kpi-icon"><i class="fa-solid fa-hourglass-half"></i></div>
      </div>
      <div class="kpi-foot">
        <span class="kpi-chip"><i class="fa-solid fa-bolt me-1"></i>Action</span>
        <span><i class="fa-solid fa-arrow-right me-1"></i>Review</span>
      </div>
    </div>
  </div>

  <div class="col-md-3">
    <div class="kpi-card-premium kpi-dark">
      <div class="kpi-top">
        <div>
          <div class="kpi-label">Points Issued</div>
          <div class="kpi-value"><?= e($k_points) ?></div>
        </div>
        <div class="kpi-icon"><i class="fa-solid fa-ranking-star"></i></div>
      </div>
      <div class="kpi-foot">
        <span class="kpi-chip"><i class="fa-solid fa-chart-line me-1"></i>All time</span>
        <span><i class="fa-solid fa-shield-check me-1"></i>Ledger</span>
      </div>
    </div>
  </div>
</div>

<!-- PROGRESS SECTION -->
<div class="row g-3 mt-3">
  <div class="col-lg-8">
    <div class="card card-premium">
      <div class="card-body">
        <div class="d-flex justify-content-between align-items-start gap-3 flex-wrap">
          <div>
            <div style="font-weight:950;">CPD Progress (Last 3 Years)</div>
            <div class="text-muted small">Target: <b><?= e($target_points) ?></b> points within 3 years</div>
          </div>
          <div class="text-end">
            <div style="font-weight:950; font-size:1.1rem;"><?= e($points_3y) ?> / <?= e($target_points) ?> pts</div>
            <div class="text-muted small"><?= e($progress_pct) ?>%</div>
          </div>
        </div>

        <div class="progress progress-brand mt-3">
          <div class="progress-bar" role="progressbar"
               style="width: <?= e($progress_pct) ?>%;"
               aria-valuenow="<?= e($progress_pct) ?>" aria-valuemin="0" aria-valuemax="100"></div>
        </div>

        <div class="d-flex justify-content-between mt-2 small text-muted">
          <span>0 pts</span>
          <span><?= e($target_points) ?> pts</span>
        </div>

        <div class="row g-2 mt-3">
          <div class="col-md-4">
            <div class="stat-box">
              <div class="lbl">Trainings Attended (3y)</div>
              <div class="val"><?= e($trainings_3y) ?></div>
            </div>
          </div>
          <div class="col-md-4">
            <div class="stat-box">
              <div class="lbl">Points Earned (3y)</div>
              <div class="val"><?= e($points_3y) ?></div>
            </div>
          </div>
          <div class="col-md-4">
            <div class="stat-box">
              <div class="lbl">Points Allocation</div>
              <div class="val"><?= e(max(0, $target_points - $points_3y)) ?></div>
            </div>
          </div>
        </div>

        <div class="text-muted small mt-3">
          Calculated from <code>cpd_points_ledger</code> using <code><?= e($ledgerDateCol) ?></code> (last 3 years).
        </div>
      </div>
    </div>
  </div>
  <div class="col-lg-4">
    <div class="quick-tile mb-3">
      <div class="t mb-1">Quick Insight</div>
      <div class="s">36 points in 3 years is about:</div>
      <ul class="small mb-0 mt-2">
        <li><b>36</b> points per year</li>
        <li><b> 3</b> points per Course</li>
      </ul>
    </div>

    <div class="quick-tile">
      <div class="t mb-1">Suggested Contractor Features</div>
      <div class="s mb-2">High-value features you can add:</div>
      <ul class="small mb-0">
        <li><b>CPD Transcript PDF</b> export (with QR verification)</li>
        <li><b>Points expiry tracking</b> (3-year rolling window)</li>
        <li><b>Email/SMS alerts</b> when points are low or nearing deadline</li>
        <li><b>Training recommendations</b> based on missing points</li>
        <li><b>Attendance QR scan</b> for quick check-in</li>
        <li><b>Company dashboard</b>: points by employee/contractor</li>
        <li><b>Compliance status</b>: “Compliant / Not Compliant” badge</li>
      </ul>
    </div>
  </div>
</div>

<?php require_once "../footer.php"; ?>