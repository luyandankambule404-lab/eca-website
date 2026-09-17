<?php
require_once "../auth.php";
require_role('ADMIN');

$rows = $conn->query("
  SELECT u.full_name, u.company_name, u.email,
         COALESCE(SUM(l.points),0) total_points
  FROM users u
  LEFT JOIN cpd_points_ledger l ON l.user_id=u.id
  WHERE u.role='CONTRACTOR'
  GROUP BY u.id
  ORDER BY total_points DESC, u.full_name
");

require_once "../header.php";
?>
<div class="d-flex justify-content-between align-items-center mb-3">
  <h4 class="mb-0">Reports: Contractor Points</h4>
  <a class="btn btn-outline-dark" href="/cpd/admin/dashboard.php">Back</a>
</div>

<div class="card shadow-sm">
  <div class="card-body">
    <div class="table-responsive">
      <table class="table table-sm table-striped">
        <thead>
          <tr><th>Name</th><th>Company</th><th>Email</th><th>Total Points</th></tr>
        </thead>
        <tbody>
          <?php while($r = $rows->fetch_assoc()): ?>
          <tr>
            <td><?=e($r['full_name'])?></td>
            <td><?=e($r['company_name'])?></td>
            <td><?=e($r['email'])?></td>
            <td><b><?=e($r['total_points'])?></b></td>
          </tr>
          <?php endwhile; ?>
        </tbody>
      </table>
    </div>
  </div>
</div>

<?php require_once "../footer.php"; ?>