<?php

require_once "../auth.php";
require_role(['SUPPERADMIN', 'OFFICER']);

$id = (int)($_GET['id'] ?? 0);

$stmt = $conn->prepare("
SELECT 
cpd_applications.*,
courses.title AS course_name
FROM cpd_applications
LEFT JOIN courses 
ON cpd_applications.course_id = courses.id
WHERE cpd_applications.id = ?
LIMIT 1
");
$stmt->bind_param("i", $id);
$stmt->execute();
$row = $stmt->get_result()->fetch_assoc();
$stmt->close();
if (!$row) {
    http_response_code(404);
    exit('Application not found.');
}

?>

<style>

.app-card{
background: linear-gradient(135deg,#06254a,#0c3f75,#0e5aa7);
color:white;
border-radius:12px;
padding:25px;
}

.info-box{
background:rgba(255,255,255,.08);
padding:12px;
border-radius:8px;
margin-bottom:10px;
}

</style>


<div class="app-card">

<h4 class="mb-3"><?= e($row['course_name']) ?></h4>

<div class="row">

<div class="col-md-6">
<div class="info-box">
<strong>Company</strong><br>
<?= e($row['company_name']) ?>
</div>
</div>

<div class="col-md-6">
<div class="info-box">
<strong>Membership</strong><br>
<?= e($row['membership_number']) ?>
</div>
</div>

</div>

<div class="row">

<div class="col-md-4">
<div class="info-box">
<strong>Representative</strong><br>
<?= e($row['full_name']) ?>
</div>
</div>

<div class="col-md-4">
<div class="info-box">
<strong>Email</strong><br>
<?= e($row['email']) ?>
</div>
</div>

<div class="col-md-4">
<div class="info-box">
<strong>Phone</strong><br>
<?= e($row['phone']) ?>
</div>
</div>

</div>


<div class="info-box">
<strong>Learning Objectives</strong><br>
<?= nl2br(e($row['learning_objectives'])) ?>
</div>


<div class="mt-3">

<?php if($row['qualification']): ?>

<a href="../uploads/<?= e(basename(str_replace('\\', '/', (string)$row['qualification']))) ?>" 
class="btn btn-light btn-sm" target="_blank">
View Qualification
</a>

<?php endif; ?>


<?php if($row['payment_proof']): ?>

<a href="../uploads/<?= e(basename(str_replace('\\', '/', (string)$row['payment_proof']))) ?>" 
class="btn btn-warning btn-sm" target="_blank">
View Payment Proof
</a>

<?php endif; ?>

</div>

</div>