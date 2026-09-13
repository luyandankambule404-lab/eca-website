<?php
require_once "../auth.php";
require_role('CONTRACTOR');
require_once "../cheader.php";
$user_id = (int)$_SESSION['user_id'];
$apps = $conn->prepare("
  SELECT a.status, a.applied_at, c.title, c.start_date, c.points
  FROM course_applications a
  INNER JOIN courses c ON c.id=a.course_id
  WHERE a.user_id=?
  ORDER BY a.applied_at DESC
");
$apps->bind_param("i", $user_id);
$apps->execute();
$apps = $apps->get_result();


?>
<div class="d-flex justify-content-between align-items-center mb-3">
  <h4 class="mb-0">My Applications</h4>
  <a class="btn btn-outline-dark" href="/cpd/contractor/dashboard.php">Back</a>
</div>
<div class="card shadow-sm">
  <div class="card-body">
    <div class="table-responsive">
      <table class="table table-sm table-striped">
        <thead>
          <tr><th>Course</th><th>Date</th><th>Points</th><th>Status</th><th>Applied At</th></tr>
        </thead>
        <tbody>
          <?php while($a = $apps->fetch_assoc()): ?>
          <tr>
            <td><?=e($a['title'])?></td>
            <td><?=e($a['start_date'])?></td>
            <td><?=e($a['points'])?></td>
            <td><span class="badge bg-secondary"><?=e($a['status'])?></span></td>
            <td><?=e($a['applied_at'])?></td>
          </tr>
          <?php endwhile; ?>
        </tbody>
      </table>
    </div>
  </div>
</div>

<?php require_once "../footer.php"; ?>