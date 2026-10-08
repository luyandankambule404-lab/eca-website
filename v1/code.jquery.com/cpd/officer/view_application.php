<?php
require_once "../auth.php";
require_role(['SUPPERADMIN', 'OFFICER']);
require_once "../config.php";
require_once "../helpers.php";
require_once "../header.php";

$id = (int)($_GET['id'] ?? 0);

$stmt = $conn->prepare("SELECT * FROM cpd_applications WHERE id=?");
$stmt->bind_param("i",$id);
$stmt->execute();
$result = $stmt->get_result();
$row = $result->fetch_assoc();
?>

<div class="container mt-4">

<div class="card shadow">
<div class="card-body">

<h4>Application Details</h4>

<p><strong>Company:</strong> <?= e($row['company_name']) ?></p>
<p><strong>Membership:</strong> <?= e($row['membership_number']) ?></p>
<p><strong>Discipline:</strong> <?= e($row['discipline']) ?></p>

<hr>

<p><strong>Representative:</strong> <?= e($row['full_name']) ?></p>
<p><strong>Email:</strong> <?= e($row['email']) ?></p>
<p><strong>Phone:</strong> <?= e($row['phone']) ?></p>
<p><strong>ID:</strong> <?= e($row['id_number']) ?></p>

<hr>
<p><strong>Position:</strong> <?= e($row['position']) ?></p>

<p><strong>Learning Objectives:</strong></p>
<p><?= nl2br(e($row['learning_objectives'])) ?></p>

<hr>

<p><strong>Qualification:</strong></p>
<a href="../uploads/<?= e(basename(str_replace('\\', '/', (string)$row['qualification']))) ?>" target="_blank">View File</a>

<p><strong>Payment Proof:</strong></p>
<a href="../uploads/<?= e(basename(str_replace('\\', '/', (string)$row['payment_proof']))) ?>" target="_blank">View File</a>

</div>
</div>

</div>

<?php require_once "../footer.php"; ?>