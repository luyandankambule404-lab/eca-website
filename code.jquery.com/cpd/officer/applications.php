<?php 
require_once "../auth.php";
require_role('ADMIN');
require_once "../config.php";
require_once "../header.php";

/* Load applications with course title */

$result = $conn->query("
SELECT 
cpd_applications.*,
courses.title AS course_name
FROM cpd_applications
LEFT JOIN courses 
ON cpd_applications.course_id = courses.id
WHERE cpd_applications.status != 'Approved'
ORDER BY cpd_applications.id DESC
");
?>

<div class="container mt-4">
<?php if(isset($_GET['msg'])): ?>

<div class="alert alert-success">
<i class="fa fa-check-circle"></i> <?= $_GET['msg'] ?>
</div>

<?php endif; ?>
<div class="card shadow-sm">
<div class="card-body">

<h3 class="mb-3">CPD Applications</h3>
<p class="text-muted">Manage contractor training applications</p>

<div class="table-responsive">

<table id="applicationsTable" class="table table-bordered table-striped align-middle">

<thead class="table-dark">
<tr>
<th>ID</th>
<th>Course</th>
<th>Company</th>
<th>Representative</th>
<th>Phone</th>
<th>Status</th>
<th>Attachments</th>
<th width="200">Action</th>
</tr>
</thead>

<tbody>

<?php while($row = $result->fetch_assoc()): ?>

<tr>

<td><?= $row['id'] ?></td>

<td>
<span class="badge bg-primary">
<?= $row['course_name'] ?>
</span>
</td>

<td>
<strong><?= $row['company_name'] ?></strong><br>
<small class="text-muted"><?= $row['discipline'] ?></small>
</td>

<td>
<?= $row['full_name'] ?><br>
<small class="text-muted"><?= $row['email'] ?></small>
</td>

<td><?= $row['phone'] ?></td>

<td>

<?php if($row['status']=="Approved"): ?>
<span class="badge bg-success">Approved</span>
<?php elseif($row['status']=="Rejected"): ?>
<span class="badge bg-danger">Rejected</span>
<?php else: ?>
<span class="badge bg-warning text-dark">Pending</span>
<?php endif; ?>

</td>

<td>

<?php if(!empty($row['qualification'])): ?>
<a href="../<?= $row['qualification'] ?>" 
target="_blank" 
class="btn btn-sm btn-primary mb-1">
Qualification
</a>
<?php endif; ?>

<?php if(!empty($row['payment_proof'])): ?>
<a href="../<?= $row['payment_proof'] ?>" 
target="_blank" 
class="btn btn-sm btn-success">
Payment
</a>
<?php endif; ?>

</td>

<td>

<button 
class="btn btn-sm btn-info viewApplication"
data-id="<?= $row['id'] ?>">
View
</button>

<a href="update_status.php?id=<?= $row['id'] ?>&status=Approved"
class="btn btn-sm btn-success"
onclick="return confirm('Approve this application?')">
Approve
</a>

<a href="update_status.php?id=<?= $row['id'] ?>&status=Rejected"
class="btn btn-sm btn-danger"
onclick="return confirm('Reject this application?')">
Reject
</a>

</td>

</tr>

<?php endwhile; ?>

</tbody>

</table>

</div>

</div>
</div>

</div>


<!-- APPLICATION MODAL -->

<div class="modal fade" id="applicationModal" tabindex="-1">

<div class="modal-dialog modal-lg">

<div class="modal-content">

<div class="modal-header bg-dark text-white">
<h5 class="modal-title">Application Details</h5>
<button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
</div>

<div class="modal-body" id="applicationDetails">

<div class="text-center p-4">
<div class="spinner-border text-primary"></div>
</div>

</div>

</div>

</div>

</div>


<?php require_once "../footer.php"; ?>


<!-- DATATABLES -->

<link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/jquery.dataTables.min.css">
<link rel="stylesheet" href="https://cdn.datatables.net/buttons/2.4.1/css/buttons.dataTables.min.css">

<script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>

<script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>

<script src="https://cdn.datatables.net/buttons/2.4.1/js/dataTables.buttons.min.js"></script>

<script src="https://cdn.datatables.net/buttons/2.4.1/js/buttons.html5.min.js"></script>

<script src="https://cdnjs.cloudflare.com/ajax/libs/jszip/3.10.1/jszip.min.js"></script>


<script>

$(document).ready(function(){

/* DATATABLE */

$('#applicationsTable').DataTable({

pageLength:10,

dom:'Bfrtip',

buttons:[
{
extend:'excelHtml5',
text:'Export Excel',
title:'CPD Applications'
}
]

});


/* VIEW APPLICATION MODAL */

$(document).on("click",".viewApplication",function(){

var id=$(this).data("id");

/* show modal */

var modal = new bootstrap.Modal(document.getElementById('applicationModal'));
modal.show();

/* loading indicator */

$("#applicationDetails").html(
"<div class='text-center p-4'><div class='spinner-border text-primary'></div></div>"
);

/* AJAX LOAD */

$.ajax({

url:"view_application_modal.php",

method:"GET",

data:{id:id},

success:function(data){

$("#applicationDetails").html(data);

},

error:function(){

$("#applicationDetails").html(
"<div class='alert alert-danger'>Failed to load application details.</div>"
);

}

});

});

});

</script>