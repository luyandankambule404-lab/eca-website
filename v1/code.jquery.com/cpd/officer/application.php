<?php
require_once "../auth.php";
require_role(['SUPPERADMIN', 'OFFICER']);
require_once "../config.php";
require_once "../helpers.php";
require_once "../header.php";

$result = $conn->query("SELECT * FROM cpd_applications ORDER BY id DESC");
?>

<div class="container mt-4">

<div class="card shadow-sm eca-form-panel eca-table-panel">
<div class="card-body">

<h3 class="mb-3">CPD Applications</h3>
<p class="text-muted">Manage contractor training applications</p>

<table class="table table-bordered table-striped">

<thead class="table-dark">
<tr>
<th>ID</th>
<th>Company</th>
<th>Representative</th>
<th>Membership</th>
<th>Phone</th>
<th>Status</th>
<th>Attachments</th>
<th>Action</th>
</tr>
</thead>

<tbody>

<?php while($row = $result->fetch_assoc()): ?>

<tr>

<td><?= (int)$row['id'] ?></td>

<td>
<strong><?= e($row['company_name']) ?></strong><br>
<span class="text-muted"><?= e($row['discipline']) ?></span>
</td>

<td>
<?= e($row['full_name']) ?><br>
<small><?= e($row['email']) ?></small>
</td>

<td><?= e($row['membership_number']) ?></td>

<td><?= e($row['phone']) ?></td>

<td>
<?php if($row['status']=="Approved"): ?>
<span class="badge bg-success">Approved</span>
<?php elseif($row['status']=="Rejected"): ?>
<span class="badge bg-danger">Rejected</span>
<?php else: ?>
<span class="badge bg-warning">Pending</span>
<?php endif; ?>
</td>

<td>

<?php if($row['qualification']): ?>
<a href="../uploads/<?= e(basename(str_replace('\\', '/', (string)$row['qualification']))) ?>" target="_blank" class="btn btn-sm btn-primary">
Qualification
</a>
<?php endif; ?>

<?php if($row['payment_proof']): ?>
<a href="../uploads/<?= e(basename(str_replace('\\', '/', (string)$row['payment_proof']))) ?>" target="_blank" class="btn btn-sm btn-success">
Payment
</a>
<?php endif; ?>

</td>

<td>
<div class="cpd-app-actions">
<a href="view_application.php?id=<?= (int)$row['id'] ?>" class="btn btn-sm btn-info">
View
</a>

<a href="update_status.php?id=<?= (int)$row['id'] ?>&status=Approved"
class="btn btn-sm btn-success"
onclick="return confirm('Approve this application?')">
Approve
</a>

<a href="update_status.php?id=<?= (int)$row['id'] ?>&status=Rejected"
class="btn btn-sm btn-danger"
onclick="return confirm('Reject this application?')">
Reject
</a>
</div>
</td>

</tr>

<?php endwhile; ?>

</tbody>
</table>

</div>
</div>

</div>

<?php require_once "../footer.php"; ?>