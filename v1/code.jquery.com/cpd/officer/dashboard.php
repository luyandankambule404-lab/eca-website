<?php
require_once "../auth.php";
require_role(['SUPPERADMIN', 'OFFICER']);
require_once "../helpers.php";

/* ================= KPIs ================= */
$k_contractors = (int)$conn->query("SELECT COUNT(*) c FROM user WHERE role='CONTRACTOR'")->fetch_assoc()['c'];
$k_courses     = (int)$conn->query("SELECT COUNT(*) c FROM courses")->fetch_assoc()['c'];
$k_pending     = ($conn instanceof mysqli) ? cpd_pending_application_count($conn) : 0;
$k_points      = (float)$conn->query("SELECT COALESCE(SUM(points),0) s FROM cpd_points_ledger")->fetch_assoc()['s'];

/* ================= PROGRESS (36 points / 3 years) ================= */
$ledgerDateCol = 'created_at';
$cols = $conn->query("SHOW COLUMNS FROM cpd_points_ledger");
$dateCols = [];
while ($c = $cols->fetch_assoc()) {
    $dateCols[] = $c['Field'];
}
if (in_array('issued_at', $dateCols, true)) {
    $ledgerDateCol = 'issued_at';
} elseif (in_array('date', $dateCols, true)) {
    $ledgerDateCol = 'date';
} elseif (in_array('created_at', $dateCols, true)) {
    $ledgerDateCol = 'created_at';
}

$points_3y = (float)$conn->query(
    "SELECT COALESCE(SUM(points),0) s
     FROM cpd_points_ledger
     WHERE {$ledgerDateCol} >= (NOW() - INTERVAL 3 YEAR)"
)->fetch_assoc()['s'];

$trainings_3y = 0;
$hasAttendance = $conn->query("SHOW TABLES LIKE 'attendance'")->num_rows > 0;
if ($hasAttendance) {
    $attDateCol = 'created_at';
    $attCols = $conn->query("SHOW COLUMNS FROM attendance");
    $attFields = [];
    while ($ac = $attCols->fetch_assoc()) {
        $attFields[] = $ac['Field'];
    }
    if (in_array('attended_at', $attFields, true)) {
        $attDateCol = 'attended_at';
    } elseif (in_array('date', $attFields, true)) {
        $attDateCol = 'date';
    } elseif (in_array('created_at', $attFields, true)) {
        $attDateCol = 'created_at';
    }

    $attFlag = null;
    foreach (['status', 'present', 'is_present', 'attended'] as $f) {
        if (in_array($f, $attFields, true)) {
            $attFlag = $f;
            break;
        }
    }

    if ($attFlag === 'status') {
        $trainings_3y = (int)$conn->query(
            "SELECT COUNT(*) c FROM attendance
             WHERE {$attDateCol} >= (NOW() - INTERVAL 3 YEAR) AND status='PRESENT'"
        )->fetch_assoc()['c'];
    } elseif (in_array($attFlag, ['present', 'is_present', 'attended'], true)) {
        $trainings_3y = (int)$conn->query(
            "SELECT COUNT(*) c FROM attendance
             WHERE {$attDateCol} >= (NOW() - INTERVAL 3 YEAR) AND {$attFlag}=1"
        )->fetch_assoc()['c'];
    } else {
        $trainings_3y = (int)$conn->query(
            "SELECT COUNT(*) c FROM attendance
             WHERE {$attDateCol} >= (NOW() - INTERVAL 3 YEAR)"
        )->fetch_assoc()['c'];
    }
}

$target_points = 36.0;
$remaining     = max(0, $target_points - $points_3y);
$progress_pct  = ($target_points > 0) ? min(100, round(($points_3y / $target_points) * 100, 1)) : 0;
$points_per_year = (int)round($target_points / 3);

require_once "../header.php";
?>

<style>
.odash {
  --od-navy: #192754;
  --od-red: #c8102e;
  --od-line: #dfe5ef;
  --od-soft: #f4f6fa;
  --od-muted: #5b6b85;
  --od-card: #ffffff;
}

.odash-head {
  display: flex;
  align-items: flex-end;
  justify-content: space-between;
  gap: 1rem;
  flex-wrap: wrap;
  margin-bottom: 1.25rem;
}

.odash-head h2 {
  margin: 0;
  font-size: 1.35rem;
  font-weight: 800;
  letter-spacing: -0.03em;
  color: var(--od-navy);
}

.odash-head p {
  margin: 0.25rem 0 0;
  color: var(--od-muted);
  font-size: 0.92rem;
}

.odash-actions {
  display: flex;
  flex-wrap: wrap;
  gap: 0.55rem;
}

.odash-actions a {
  display: inline-flex;
  align-items: center;
  gap: 0.45rem;
  padding: 0.55rem 0.95rem;
  border-radius: 999px;
  font-size: 0.86rem;
  font-weight: 700;
  text-decoration: none;
  border: 1px solid var(--od-line);
  background: var(--od-card);
  color: var(--od-navy);
  transition: background .18s ease, border-color .18s ease, color .18s ease;
}

.odash-actions a.primary {
  background: var(--od-navy);
  border-color: var(--od-navy);
  color: #fff;
}

.odash-actions a:hover {
  border-color: var(--od-navy);
  color: var(--od-navy);
  background: #eef2f8;
}

.odash-actions a.primary:hover {
  background: var(--od-red);
  border-color: var(--od-red);
  color: #fff;
}

.odash-kpis {
  display: grid;
  grid-template-columns: repeat(4, minmax(0, 1fr));
  gap: 0.9rem;
  margin-bottom: 1.1rem;
}

@media (max-width: 1100px) {
  .odash-kpis { grid-template-columns: repeat(2, minmax(0, 1fr)); }
}
@media (max-width: 560px) {
  .odash-kpis { grid-template-columns: 1fr; }
}

.odash-kpi {
  display: flex;
  flex-direction: column;
  gap: 0.85rem;
  padding: 1.05rem 1.1rem 0.95rem;
  border-radius: 16px;
  background: var(--od-card);
  border: 1px solid var(--od-line);
  text-decoration: none;
  color: inherit;
  min-height: 132px;
  transition: transform .18s ease, box-shadow .18s ease, border-color .18s ease;
}

.odash-kpi:hover {
  transform: translateY(-2px);
  border-color: #c8d2e4;
  box-shadow: 0 12px 28px rgba(25, 39, 84, 0.08);
  color: inherit;
}

.odash-kpi-top {
  display: flex;
  align-items: flex-start;
  justify-content: space-between;
  gap: 0.75rem;
}

.odash-kpi-label {
  margin: 0;
  font-size: 0.72rem;
  font-weight: 800;
  letter-spacing: 0.08em;
  text-transform: uppercase;
  color: var(--od-muted);
}

.odash-kpi-value {
  margin: 0.35rem 0 0;
  font-size: 2rem;
  line-height: 1;
  font-weight: 800;
  letter-spacing: -0.04em;
  color: var(--od-navy);
}

.odash-kpi-icon {
  width: 2.5rem;
  height: 2.5rem;
  border-radius: 12px;
  display: grid;
  place-items: center;
  flex-shrink: 0;
  background: var(--od-soft);
  color: var(--od-navy);
  font-size: 1rem;
}

.odash-kpi.is-alert .odash-kpi-icon {
  background: rgba(200, 16, 46, 0.1);
  color: var(--od-red);
}

.odash-kpi-meta {
  margin-top: auto;
  padding-top: 0.7rem;
  border-top: 1px solid var(--od-line);
  font-size: 0.8rem;
  font-weight: 650;
  color: var(--od-muted);
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 0.5rem;
}

.odash-kpi-meta span:last-child {
  color: var(--od-navy);
  font-weight: 750;
}

.odash-grid {
  display: grid;
  grid-template-columns: minmax(0, 1.7fr) minmax(260px, 1fr);
  gap: 0.9rem;
  align-items: start;
}

@media (max-width: 992px) {
  .odash-grid { grid-template-columns: 1fr; }
}

.odash-panel {
  background: var(--od-card);
  border: 1px solid var(--od-line);
  border-radius: 16px;
  padding: 1.2rem 1.25rem 1.25rem;
}

.odash-panel-head {
  display: flex;
  align-items: flex-start;
  justify-content: space-between;
  gap: 1rem;
  flex-wrap: wrap;
  margin-bottom: 1rem;
}

.odash-panel-head h3 {
  margin: 0;
  font-size: 1.05rem;
  font-weight: 800;
  color: var(--od-navy);
}

.odash-panel-head p {
  margin: 0.2rem 0 0;
  color: var(--od-muted);
  font-size: 0.88rem;
}

.odash-score {
  text-align: right;
}

.odash-score strong {
  display: block;
  font-size: 1.15rem;
  font-weight: 800;
  color: var(--od-navy);
  letter-spacing: -0.02em;
}

.odash-score span {
  color: var(--od-muted);
  font-size: 0.82rem;
  font-weight: 650;
}

.odash-progress {
  height: 12px;
  border-radius: 999px;
  background: #e8edf5;
  overflow: hidden;
}

.odash-progress > span {
  display: block;
  height: 100%;
  border-radius: inherit;
  background: linear-gradient(90deg, var(--od-navy), #2f4f9a);
  min-width: 0;
  transition: width .35s ease;
}

.odash-progress-scale {
  display: flex;
  justify-content: space-between;
  margin-top: 0.45rem;
  color: var(--od-muted);
  font-size: 0.78rem;
}

.odash-stats {
  display: grid;
  grid-template-columns: repeat(3, minmax(0, 1fr));
  gap: 0.65rem;
  margin-top: 1rem;
}

@media (max-width: 640px) {
  .odash-stats { grid-template-columns: 1fr; }
}

.odash-stat {
  background: var(--od-soft);
  border-radius: 12px;
  padding: 0.85rem 0.9rem;
}

.odash-stat .lbl {
  font-size: 0.78rem;
  font-weight: 700;
  color: var(--od-muted);
}

.odash-stat .val {
  margin-top: 0.3rem;
  font-size: 1.35rem;
  font-weight: 800;
  color: var(--od-navy);
  letter-spacing: -0.02em;
}

.odash-note {
  margin: 0.95rem 0 0;
  color: var(--od-muted);
  font-size: 0.8rem;
}

.odash-side {
  display: grid;
  gap: 0.9rem;
}

.odash-side h3 {
  margin: 0 0 0.65rem;
  font-size: 0.98rem;
  font-weight: 800;
  color: var(--od-navy);
}

.odash-insight-list {
  list-style: none;
  margin: 0;
  padding: 0;
  display: grid;
  gap: 0.55rem;
}

.odash-insight-list li {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 0.75rem;
  padding: 0.7rem 0.8rem;
  border-radius: 11px;
  background: var(--od-soft);
  font-size: 0.86rem;
  color: var(--od-muted);
  font-weight: 650;
}

.odash-insight-list strong {
  color: var(--od-navy);
  font-size: 1rem;
  font-weight: 800;
}

.odash-links {
  list-style: none;
  margin: 0;
  padding: 0;
  display: grid;
  gap: 0.4rem;
}

.odash-links a {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 0.75rem;
  padding: 0.72rem 0.8rem;
  border-radius: 11px;
  text-decoration: none;
  color: var(--od-navy);
  font-weight: 700;
  font-size: 0.88rem;
  background: var(--od-soft);
  transition: background .15s ease;
}

.odash-links a:hover {
  background: #e8eef8;
}

.odash-links a i:last-child {
  color: var(--od-muted);
  font-size: 0.75rem;
}
</style>

<div class="odash">
  <div class="odash-head">
    <div>
      <h2>Officer dashboard</h2>
      <p>Review applications, manage courses, and track CPD progress.</p>
    </div>
    <div class="odash-actions">
      <a class="primary" href="/cpd/admin/courses.php"><i class="fa-solid fa-graduation-cap" aria-hidden="true"></i>Manage courses</a>
      <a href="/cpd/admin/applications.php"><i class="fa-solid fa-file-circle-check" aria-hidden="true"></i>Applications</a>
      <a href="/cpd/admin/attendance.php"><i class="fa-solid fa-user-check" aria-hidden="true"></i>Attendance</a>
    </div>
  </div>

  <div class="odash-kpis">
    <a class="odash-kpi" href="/cpd/admin/learners.php">
      <div class="odash-kpi-top">
        <div>
          <p class="odash-kpi-label">Contractors</p>
          <p class="odash-kpi-value"><?= e((string)(int)$k_contractors) ?></p>
        </div>
        <div class="odash-kpi-icon" aria-hidden="true"><i class="fa-solid fa-people-group"></i></div>
      </div>
      <div class="odash-kpi-meta">
        <span>Registered learners</span>
        <span>View</span>
      </div>
    </a>

    <a class="odash-kpi" href="/cpd/admin/courses.php">
      <div class="odash-kpi-top">
        <div>
          <p class="odash-kpi-label">Courses</p>
          <p class="odash-kpi-value"><?= e((string)(int)$k_courses) ?></p>
        </div>
        <div class="odash-kpi-icon" aria-hidden="true"><i class="fa-solid fa-book-open-reader"></i></div>
      </div>
      <div class="odash-kpi-meta">
        <span>Training catalogue</span>
        <span>Manage</span>
      </div>
    </a>

    <a class="odash-kpi<?= $k_pending > 0 ? ' is-alert' : '' ?>" href="/cpd/admin/applications.php">
      <div class="odash-kpi-top">
        <div>
          <p class="odash-kpi-label">Pending apps</p>
          <p class="odash-kpi-value"><?= e((string)(int)$k_pending) ?></p>
        </div>
        <div class="odash-kpi-icon" aria-hidden="true"><i class="fa-solid fa-hourglass-half"></i></div>
      </div>
      <div class="odash-kpi-meta">
        <span><?= $k_pending > 0 ? 'Needs review' : 'All clear' ?></span>
        <span>Review</span>
      </div>
    </a>

    <a class="odash-kpi" href="/cpd/admin/cpd_reports.php">
      <div class="odash-kpi-top">
        <div>
          <p class="odash-kpi-label">Points issued</p>
          <p class="odash-kpi-value"><?= e(rtrim(rtrim(number_format($k_points, 1), '0'), '.')) ?></p>
        </div>
        <div class="odash-kpi-icon" aria-hidden="true"><i class="fa-solid fa-ranking-star"></i></div>
      </div>
      <div class="odash-kpi-meta">
        <span>All-time total</span>
        <span>Reports</span>
      </div>
    </a>
  </div>

  <div class="odash-grid">
    <section class="odash-panel">
      <div class="odash-panel-head">
        <div>
          <h3>CPD progress (last 3 years)</h3>
          <p>Programme target: <?= e((string)(int)$target_points) ?> points over three years</p>
        </div>
        <div class="odash-score">
          <strong><?= e(rtrim(rtrim(number_format($points_3y, 1), '0'), '.')) ?> / <?= e((string)(int)$target_points) ?> pts</strong>
          <span><?= e((string)$progress_pct) ?>% of target</span>
        </div>
      </div>

      <div class="odash-progress" role="progressbar" aria-valuemin="0" aria-valuemax="100" aria-valuenow="<?= e((string)$progress_pct) ?>" aria-label="CPD progress">
        <span style="width: <?= e((string)$progress_pct) ?>%;"></span>
      </div>
      <div class="odash-progress-scale">
        <span>0 pts</span>
        <span><?= e((string)(int)$target_points) ?> pts</span>
      </div>

      <div class="odash-stats">
        <div class="odash-stat">
          <div class="lbl">Trainings attended</div>
          <div class="val"><?= e((string)(int)$trainings_3y) ?></div>
        </div>
        <div class="odash-stat">
          <div class="lbl">Points earned</div>
          <div class="val"><?= e(rtrim(rtrim(number_format($points_3y, 1), '0'), '.')) ?></div>
        </div>
        <div class="odash-stat">
          <div class="lbl">Points remaining</div>
          <div class="val"><?= e(rtrim(rtrim(number_format($remaining, 1), '0'), '.')) ?></div>
        </div>
      </div>

      <p class="odash-note">Totals cover the last three years of issued CPD points.</p>
    </section>

    <aside class="odash-side">
      <section class="odash-panel">
        <h3>Quick insight</h3>
        <ul class="odash-insight-list">
          <li>
            <span>Points per year</span>
            <strong><?= e((string)$points_per_year) ?></strong>
          </li>
          <li>
            <span>Typical course value</span>
            <strong>3 pts</strong>
          </li>
          <li>
            <span>Three-year target</span>
            <strong><?= e((string)(int)$target_points) ?></strong>
          </li>
        </ul>
      </section>

      <section class="odash-panel">
        <h3>Officer shortcuts</h3>
        <ul class="odash-links">
          <li>
            <a href="/cpd/admin/applications.php">
              <span><i class="fa-solid fa-inbox me-2" aria-hidden="true"></i>Review applications</span>
              <i class="fa-solid fa-chevron-right" aria-hidden="true"></i>
            </a>
          </li>
          <li>
            <a href="/cpd/admin/course_students.php">
              <span><i class="fa-solid fa-clipboard-user me-2" aria-hidden="true"></i>Mark attendance</span>
              <i class="fa-solid fa-chevron-right" aria-hidden="true"></i>
            </a>
          </li>
          <li>
            <a href="/cpd/admin/courses.php">
              <span><i class="fa-solid fa-plus me-2" aria-hidden="true"></i>Open courses</span>
              <i class="fa-solid fa-chevron-right" aria-hidden="true"></i>
            </a>
          </li>
          <li>
            <a href="/cpd/admin/cpd_reports.php">
              <span><i class="fa-solid fa-chart-column me-2" aria-hidden="true"></i>CPD reports</span>
              <i class="fa-solid fa-chevron-right" aria-hidden="true"></i>
            </a>
          </li>
        </ul>
      </section>
    </aside>
  </div>
</div>

<?php require_once "../footer.php"; ?>
