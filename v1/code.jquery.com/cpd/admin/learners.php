<?php 
require_once "../auth.php";
require_role(['SUPPERADMIN', 'OFFICER', 'ADMIN']);
require_once "../config.php";
require_once "../header.php";

/* LOAD ONLY APPROVED + IN PROGRESS */

$result = $conn->query("
SELECT 
cpd_applications.*,
courses.title AS course_name
FROM cpd_applications
LEFT JOIN courses 
ON cpd_applications.course_id = courses.id
WHERE cpd_applications.status='Approved'
ORDER BY cpd_applications.id DESC
");
?>

<div class="container mt-4">

<div class="card shadow-sm">
<div class="card-body">

<h3 class="mb-3">
<i class="fa fa-users"></i> Learners In Training
</h3>

<p class="text-muted">
These are contractors currently attending CPD training.
</p>

<div class="table-responsive">

<table id="learnersTable" class="table table-bordered table-striped align-middle">

<thead class="table-dark">
<tr>

<th>ID</th>
<th>Course</th>
<th>Company</th>
<th>Learner</th>
<th>Phone</th>
<th>Training Status</th>
<th width="150">Action</th>

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
<span class="badge bg-warning text-dark">
<i class="fa fa-spinner"></i><?= $row['training_status'] ?>
</span>
</td>

<td>

<button 
class="btn btn-sm btn-info viewApplication"
data-id="<?= $row['id'] ?>">
View
</button>

<?php if($row['training_status'] != "Completed"): ?>


<?php else: ?>

<a href="../training_cerficate.php?id=<?= $row['id'] ?>"
class="btn btn-sm btn-primary"
target="_blank">
<i class="fa fa-download"></i> Certificate
</a>

<?php endif; ?>

</td>


</tr>

<?php endwhile; ?>

</tbody>

</table>

</div>

</div>
</div>

</div>


<!-- VIEW MODAL -->

<div class="modal fade" id="applicationModal" tabindex="-1">

<div class="modal-dialog modal-lg">

<div class="modal-content">

<div class="modal-header bg-dark text-white">
<h5 class="modal-title">Learner Details</h5>

<button type="button" 
class="btn-close btn-close-white" 
data-bs-dismiss="modal">
</button>

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

$('#learnersTable').DataTable({

pageLength:10,

dom:'Bfrtip',

buttons:[
{
extend:'excelHtml5',
text:'Export Excel',
title:'CPD Learners'
}
]

});


/* VIEW MODAL */

$(document).on("click",".viewApplication",function(){

var id=$(this).data("id");

var modal = new bootstrap.Modal(document.getElementById('applicationModal'));
modal.show();

$("#applicationDetails").html(
"<div class='text-center p-4'><div class='spinner-border text-primary'></div></div>"
);

$.ajax({

url:"view_application_modal.php",

method:"GET",

data:{id:id},

success:function(data){

$("#applicationDetails").html(data);

},

error:function(){

$("#applicationDetails").html(
"<div class='alert alert-danger'>Failed to load learner details.</div>"
);

}

});

});

});

</script>