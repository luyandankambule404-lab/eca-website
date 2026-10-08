<?php
require_once "../auth.php";
require_role(['SUPPERADMIN', 'ADMIN']);
require_once "../helpers.php";

$rows = false;
$userTable = ($conn instanceof mysqli && cpd_table_exists($conn, 'user'))
    ? 'user'
    : (($conn instanceof mysqli && cpd_table_exists($conn, 'users')) ? 'users' : '');

if ($userTable !== '') {
    $nameExpr = cpd_table_has_column($conn, $userTable, 'full_name') ? 'u.full_name' : "''";
    $companyExpr = cpd_table_has_column($conn, $userTable, 'company_name') ? 'u.company_name' : "''";
    $emailExpr = cpd_table_has_column($conn, $userTable, 'email') ? 'u.email' : "''";
    $roleFilter = cpd_table_has_column($conn, $userTable, 'role')
        ? " WHERE " . cpd_status_equals_sql('u.role', ['CONTRACTOR'])
        : '';
    $groupId = cpd_table_has_column($conn, $userTable, 'id') ? 'u.id' : $nameExpr;
    $ledgerJoin = ($conn instanceof mysqli && cpd_table_exists($conn, 'cpd_points_ledger') && cpd_table_has_column($conn, $userTable, 'id'))
        ? ' LEFT JOIN cpd_points_ledger l ON l.user_id = u.id'
        : '';
    $pointsExpr = $ledgerJoin !== '' ? 'COALESCE(SUM(l.points),0)' : '0';
    try {
        $rows = $conn->query("
          SELECT {$nameExpr} AS full_name, {$companyExpr} AS company_name, {$emailExpr} AS email,
                 {$pointsExpr} AS total_points
          FROM `{$userTable}` u
          {$ledgerJoin}
          {$roleFilter}
          GROUP BY {$groupId}
          ORDER BY total_points DESC, full_name
        ");
    } catch (Throwable $e) {
        $rows = false;
    }
}

require_once "../header.php";
?>
<div class="d-flex justify-content-between align-items-center mb-3">
  <h4 class="mb-0">Reports: Contractor Points</h4>
  <a class="btn btn-outline-dark" href="/cpd/admin/dashboard.php">Back</a>
</div>

<div class="card shadow-sm eca-form-panel eca-table-panel">
  <div class="card-body">
    <div class="table-responsive">
      <table class="table table-sm table-striped">
        <thead>
          <tr><th>Name</th><th>Company</th><th>Email</th><th>Total Points</th></tr>
        </thead>
        <tbody>
          <?php if ($rows): ?>
          <?php while($r = $rows->fetch_assoc()): ?>
          <tr>
            <td><?=e($r['full_name'])?></td>
            <td><?=e($r['company_name'])?></td>
            <td><?=e($r['email'])?></td>
            <td><b><?=e($r['total_points'])?></b></td>
          </tr>
          <?php endwhile; ?>
          <?php else: ?>
          <tr><td colspan="4" class="text-muted">No contractor point records were found in the database.</td></tr>
          <?php endif; ?>
        </tbody>
      </table>
    </div>
  </div>
</div>

<?php require_once "../footer.php"; ?>
