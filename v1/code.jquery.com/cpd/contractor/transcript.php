<?php
require_once "../auth.php";
require_role('CONTRACTOR');

$user_id = (int)$_SESSION['user_id'];

$total = $conn->prepare("SELECT COALESCE(SUM(points),0) s FROM cpd_points_ledger WHERE user_id=?");
$total->bind_param("i", $user_id);
$total->execute();
$total_points = $total->get_result()->fetch_assoc()['s'] ?? 0;

$ledgerCols = [];
$colRes = $conn->query('SHOW COLUMNS FROM cpd_points_ledger');
if ($colRes) {
    while ($col = $colRes->fetch_assoc()) {
        $ledgerCols[strtolower((string) ($col['Field'] ?? ''))] = true;
    }
}
$noteExpr = !empty($ledgerCols['reason'])
    ? 'l.reason'
    : (!empty($ledgerCols['note']) ? 'l.note' : (!empty($ledgerCols['description']) ? 'l.description' : "''"));
$dateExpr = !empty($ledgerCols['issued_at']) ? 'l.issued_at' : 'l.created_at';

$entries = [];
$rows = $conn->prepare(
    "SELECT {$dateExpr} AS created_at, l.points, {$noteExpr} AS reason, c.title, c.start_date
     FROM cpd_points_ledger l
     LEFT JOIN courses c ON c.id = l.course_id
     WHERE l.user_id=?
     ORDER BY created_at DESC"
);
if ($rows) {
    $rows->bind_param("i", $user_id);
    $rows->execute();
    $result = $rows->get_result();
    if ($result) {
        while ($r = $result->fetch_assoc()) {
            $entries[] = $r;
        }
    }
}

require_once "../cheader.php";
?>

<div class="hub-learn-toolbar">
  <div>
    <h2>My CPD transcript</h2>
    <p class="hub-sub">Points recorded against your learner account.</p>
  </div>
  <div class="hub-learn-actions">
    <a class="hub-btn-navy" href="/cpd/contractor/dashboard.php">Dashboard</a>
  </div>
</div>

<div class="hub-summary hub-learn-summary">
  <div>
    <span>Total points</span>
    <strong><?= e((string)number_format((float)$total_points, 0)) ?></strong>
  </div>
  <div>
    <span>Entries</span>
    <strong><?= count($entries) ?></strong>
  </div>
  <div>
    <span>Latest activity</span>
    <strong><?php
      $latest = $entries[0]['created_at'] ?? '';
      $latest_ts = $latest !== '' ? strtotime((string)$latest) : false;
      echo $latest_ts ? e(date('d M Y', $latest_ts)) : '—';
    ?></strong>
  </div>
</div>

<section class="hub-card eca-table-panel">
  <div class="hub-card-head">
    <h2>Point history</h2>
  </div>
  <?php if (!$entries): ?>
    <div class="hub-empty-card">
      <div class="hub-empty-icon" aria-hidden="true"><i class="fa-solid fa-award"></i></div>
      <h3>No points recorded yet</h3>
      <p>Completed training will add entries to this transcript.</p>
    </div>
  <?php else: ?>
    <div class="table-responsive">
      <table class="hub-table">
        <thead>
          <tr>
            <th>Date</th>
            <th>Course</th>
            <th>Course date</th>
            <th>Reason</th>
            <th>Points</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($entries as $r):
              $created_ts = !empty($r['created_at']) ? strtotime((string)$r['created_at']) : false;
              $course_ts = !empty($r['start_date']) ? strtotime((string)$r['start_date']) : false;
          ?>
          <tr>
            <td><?= $created_ts ? e(date('d M Y', $created_ts)) : '—' ?></td>
            <td><?= e($r['title'] ?? '—') ?></td>
            <td><?= $course_ts ? e(date('d M Y', $course_ts)) : '—' ?></td>
            <td><?= e($r['reason'] ?? '') ?></td>
            <td><b><?= e((string)($r['points'] ?? '')) ?></b></td>
          </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  <?php endif; ?>
</section>

<?php require_once "../footer.php"; ?>
