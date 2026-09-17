<?php
require_once "../auth.php";
require_role('ADMIN');
require_once "../config.php";
require_once "../header.php";

$id = $_GET['id'];

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

<p><strong>Company:</strong> <?= $row['company_name'] ?></p>
<p><strong>Membership:</strong> <?= $row['membership_number'] ?></p>
<p><strong>Discipline:</strong> <?= $row['discipline'] ?></p>

<hr>

<p><strong>Representative:</strong> <?= $row['full_name'] ?></p>
<p><strong>Email:</strong> <?= $row['email'] ?></p>
<p><strong>Phone:</strong> <?= $row['phone'] ?></p>
<p><strong>ID:</strong> <?= $row['id_number'] ?></p>

<hr>
<p><strong>Position:</strong> <?= $row['position'] ?></p>

<p><strong>Learning Objectives:</strong></p>
<p><?= nl2br($row['learning_objectives']) ?></p>

<hr>

<p><strong>Qualification:</strong></p>
<a href="../<?= $row['qualification'] ?>" target="_blank">View File</a>

<p><strong>Payment Proof:</strong></p>
<a href="../<?= $row['payment_proof'] ?>" target="_blank">View File</a>

</div>
</div>

</div>

<?php require_once "../footer.php"; ?>