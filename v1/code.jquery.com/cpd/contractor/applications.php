<?php
require_once "../auth.php";
require_role('CONTRACTOR');
require_once "../cheader.php";

$user_email = (string)($_SESSION['email'] ?? '');
$apps = $conn->prepare("
  SELECT a.status, a.id, c.title, c.start_date, c.points
  FROM cpd_applications a
  INNER JOIN courses c ON c.id = a.course_id
  WHERE a.email = ?
  ORDER BY a.id DESC
");
$apps->bind_param("s", $user_email);
$apps->execute();
$apps = $apps->get_result();
$rows = [];
if ($apps) {
    while ($a = $apps->fetch_assoc()) {
        $rows[] = $a;
    }
}

$status_class = static function (string $status): string {
    $s = strtolower($status);
    if (in_array($s, ['approved', 'active', 'completed'], true)) {
        return 'status-approved';
    }
    if (in_array($s, ['pending', 'review'], true)) {
        return 'status-pending';
    }
    if (in_array($s, ['rejected', 'declined'], true)) {
        return 'status-rejected';
    }
    return 'badge';
};
?>

<div class="hub-learn-toolbar">
  <div>
    <h2>My applications</h2>
    <p class="hub-sub">Training you have applied for and the current status of each request.</p>
  </div>
  <div class="hub-learn-actions">
    <a class="hub-btn" href="/cpd/cpd_application.php">Apply for training</a>
    <a class="hub-btn-navy" href="/cpd/contractor/dashboard.php">Dashboard</a>
  </div>
</div>

<section class="hub-card eca-table-panel">
  <?php if (!$rows): ?>
    <div class="hub-empty-card">
      <div class="hub-empty-icon" aria-hidden="true"><i class="fa-solid fa-file-lines"></i></div>
      <h3>No applications yet</h3>
      <p>When you apply for a course, the status will appear in this list.</p>
      <div class="hub-learn-actions hub-learn-actions-center">
        <a class="hub-btn" href="/cpd/cpd_application.php">Apply for training</a>
      </div>
    </div>
  <?php else: ?>
    <div class="table-responsive">
      <table class="hub-table">
        <thead>
          <tr>
            <th>Course</th>
            <th>Date</th>
            <th>Points</th>
            <th>Status</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($rows as $a):
              $start_ts = !empty($a['start_date']) ? strtotime((string)$a['start_date']) : false;
          ?>
          <tr>
            <td><?= e($a['title'] ?? '') ?></td>
            <td><?= $start_ts ? e(date('d M Y', $start_ts)) : '—' ?></td>
            <td><?= e((string)($a['points'] ?? '')) ?></td>
            <td><span class="badge <?= e($status_class((string)($a['status'] ?? ''))) ?>"><?= e($a['status'] ?? '') ?></span></td>
          </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  <?php endif; ?>
</section>

<?php require_once "../footer.php"; ?>
