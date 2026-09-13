<?php
require_once "../auth.php";
require_role('CONTRACTOR');

$user_id = (int)$_SESSION['user_id'];

$total = $conn->prepare("SELECT COALESCE(SUM(points),0) s FROM cpd_points_ledger WHERE user_id=?");
$total->bind_param("i", $user_id);
$total->execute();
$total_points = $total->get_result()->fetch_assoc()['s'] ?? 0;

$rows = $conn->prepare("
  SELECT l.created_at, l.points, l.reason, c.title, c.start_date
  FROM cpd_points_ledger l
  LEFT JOIN courses c ON c.id=l.course_id
  WHERE l.user_id=?
  ORDER BY l.created_at DESC
");
$rows->bind_param("i", $user_id);
$rows->execute();
$rows = $rows->get_result();

require_once "../header.php";
?>
<div class="d-flex justify-content-between align-items-center mb-3">
  <h4 class="mb-0">My CPD Transcript</h4>
  <a class="btn btn-outline-dark" href="/cpd/contractor/dashboard.php">Back</a>
</div>

<div class="card shadow-sm mb-3">
  <div class="card-body">
    <div class="text-muted">Total Points</div>
    <div class="fs-2"><b><?=e($total_points)?></b></div>
  </div>
</div>

<div class="card shadow-sm">
  <div class="card-body">
    <div class="table-responsive">
      <table class="table table-sm table-striped">
        <thead>
          <tr><th>Date</th><th>Course</th><th>Course Date</th><th>Reason</th><th>Points</th></tr>
        </thead>
        <tbody>
          <?php while($r = $rows->fetch_assoc()): ?>
          <tr>
            <td><?=e($r['created_at'])?></td>
            <td><?=e($r['title'] ?? '—')?></td>
            <td><?=e($r['start_date'] ?? '—')?></td>
            <td><?=e($r['reason'])?></td>
            <td><b><?=e($r['points'])?></b></td>
          </tr>
          <?php endwhile; ?>
        </tbody>
      </table>
    </div>
  </div>
</div>

<?php require_once "../footer.php"; ?>