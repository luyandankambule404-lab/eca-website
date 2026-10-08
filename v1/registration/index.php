<?php
require_once __DIR__ . '/../includes/env.php';
require_once __DIR__ . '/../includes/session.php';
$legacyCsrf = eca_public_csrf_token();
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Course Registration</title>
 <link href="img/favicon.ico" rel="icon">

<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">

<style>
  body {
    background: linear-gradient(135deg, rgba(11,44,102,0.9) 0%, rgba(26,76,160,0.9) 50%, rgba(43,111,210,0.9) 100%),
                url("/img/carousel-1.jpg") center/cover no-repeat;
    font-family: 'Poppins', sans-serif;
    min-height: 100vh;
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 30px;
  }

  .card {
    max-width: 950px;
    background: rgba(255, 255, 255, 0.9);
    border: none;
    border-radius: 20px;
    backdrop-filter: blur(10px);
    box-shadow: 0 8px 30px rgba(0,0,0,0.2);
    overflow: hidden;
    animation: fadeIn 1s ease-in-out;
  }

  @keyframes fadeIn {
    from { opacity: 0; transform: translateY(20px); }
    to { opacity: 1; transform: translateY(0); }
  }

  .card-header {
    background: linear-gradient(90deg, #0b2c66, #1b56b2);
    color: #fff;
    padding: 30px 40px;
    display: flex;
    align-items: center;
    justify-content: space-between;
    flex-wrap: wrap;
    border-bottom: 5px solid rgba(255, 255, 255, 0.2);
  }

  .card-header img {
    width: 180px;
    border-radius: 10px;
    box-shadow: 0 4px 15px rgba(255,255,255,0.3);
  }

  .header-title {
    font-size: 1.8rem;
    font-weight: 700;
    text-shadow: 1px 2px 3px rgba(0,0,0,0.2);
  }

  .header-text p {
    font-size: 1rem;
    opacity: 0.95;
  }

  .card-body {
    padding: 40px;
  }

  .section-title {
    margin-top: 25px;
    font-size: 1.15rem;
    font-weight: 600;
    color: #0b2c66;
    border-left: 5px solid #1b56b2;
    padding-left: 10px;
    margin-bottom: 10px;
  }

  label.form-label {
    font-weight: 500;
  }

  input.form-control, select.form-control {
    border-radius: 10px;
    border: 1px solid #d0d7e2;
    transition: all 0.3s ease;
  }

  input.form-control:focus {
    box-shadow: 0 0 0 4px rgba(27,86,178,0.15);
    border-color: #1b56b2;
  }

  .form-check-label {
    margin-left: 5px;
  }

  .alert {
    border-radius: 10px;
    background-color: rgba(11,44,102,0.05);
    border-left: 4px solid #1b56b2;
  }

  .btn-primary {
    background: linear-gradient(90deg, #0b2c66, #1b56b2);
    border: none;
    border-radius: 50px;
    padding: 12px 40px;
    font-size: 1.1rem;
    letter-spacing: 0.5px;
    transition: all 0.3s ease;
  }

  .btn-primary:hover {
    background: linear-gradient(90deg, #113c88, #1f5fd8);
    transform: translateY(-2px);
    box-shadow: 0 6px 20px rgba(27,86,178,0.4);
  }

  @media (max-width: 768px) {
    .card-header {
      flex-direction: column;
      text-align: center;
      padding: 25px;
    }

    .card-header img {
      width: 150px;
      margin-bottom: 12px;
    }

    .header-title {
      font-size: 1.4rem;
    }

    .card-body {
      padding: 25px;
    }
  }
</style>
</head>
<body>

<div class="card">
  <div class="card-header">
    <img src="images/logo.jpg" alt="ECA Logo">
    <div class="header-text text-md-end text-center">
      <h2 class="header-title mb-1">Preliminaries & Generals Course Registration</h2>
      <p class="mb-0 fw-light">Organized by Eswatini Contractors Association</p>
       <div class="text-md-end text-center mt-3 mt-md-0">
    <p class="mb-1 fw-semibold text-dark">
      📅 <span class="text-white">10–12 November 2025</span>
    </p>
    <p class="mb-0 fw-semibold text-dark">
      📍 <span class="text-white">Sibane Sami Hotel</span>
    </p>
  </div>
    </div>
  </div>
  <div class="card-body">
    <form action="submit_registration.php" method="post" enctype="multipart/form-data">
      <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($legacyCsrf, ENT_QUOTES, 'UTF-8') ?>">
      <!-- Identification -->
      <div class="section-title">Busines Profile </div>
       <div class="row">
        <div class="col-md-6 mb-3">
        <label class="form-label">Company Name</label>
        <input type="text" name="company_name" class="form-control" required>
        </div>
        
         <div class="col-md-6 mb-3">
             <label class="form-label">Company Category</label>
           <input type="text"  name="specialisation" class="form-control">
                           
        </div>
      </div>

      <!-- Attendee Details -->
      <div class="section-title">Attendee Details</div>
      <div class="row">
        <div class="col-md-6 mb-3">
          <label class="form-label">Name & Surname</label>
          <input type="text" name="attendee_name" class="form-control" required>
        </div>
        <div class="col-md-6 mb-3">
  <label class="form-label">Gender</label>
  <select name="gender" class="form-select" required>
    <option value="" selected disabled>-- Select Gender --</option>
    <option value="Male">Male</option>
    <option value="Female">Female</option>
   
  </select>
</div>
        <div class="col-md-6 mb-3">
          <label class="form-label">ID /Passport Number </label>
          <input type="text" name="id_number" class="form-control" required>
        </div>
          <div class="col-md-6 mb-3">
          <label class="form-label">Email address </label>
          <input type="text" name="email" class="form-control">
        </div>
      </div>
      <div class="row">
        <div class="col-md-6 mb-3">
          <label class="form-label">Position</label>
          <input type="text" name="position" class="form-control">
        </div>
        <div class="col-md-6 mb-3">
          <label class="form-label">Cell Number</label>
          <input type="text" name="cell_number" class="form-control">
        </div>
      </div>
      <div class="row">
        <div class="col-md-6 mb-3">
          <label class="form-label">Highest Level of Education Completed </label>
          <input type="text" name="Qualification" class="form-control">
        </div>

      </div>
      <!-- Training History -->
     <div class="row">
  <!-- Training Section -->
<div class="col-md-6 mb-3">
  <div class="section-title">Training History</div>
  <p>Have you previously attended our training?</p>

  <!-- Main Yes/No question -->
  <div class="form-check">
    <input class="form-check-input" type="radio" name="attended_training" value="Yes" id="attendedYes" required>
    <label class="form-check-label" for="attendedYes">Yes</label>
  </div>
  <div class="form-check mb-3">
    <input class="form-check-input" type="radio" name="attended_training" value="No" id="attendedNo" required>
    <label class="form-check-label" for="attendedNo">No, this is my first attendance</label>
  </div>

  <!-- Hidden training options (shown only if Yes is selected) -->
  <div id="previousTrainings" class="mt-3" style="display: none;">
    <div class="mb-2">
      <label class="fw-semibold d-block">1. Tendering Fundamentals</label>
      <div class="form-check form-check-inline">
        <input class="form-check-input" type="radio" name="tendering_fundamentals" value="Yes">
        <label class="form-check-label">Yes</label>
      </div>
      <div class="form-check form-check-inline">
        <input class="form-check-input" type="radio" name="tendering_fundamentals" value="No">
        <label class="form-check-label">No</label>
      </div>
    </div>

    <div class="mb-2">
      <label class="fw-semibold d-block">2. Estimating and Costing</label>
      <div class="form-check form-check-inline">
        <input class="form-check-input" type="radio" name="estimating_costing" value="Yes">
        <label class="form-check-label">Yes</label>
      </div>
      <div class="form-check form-check-inline">
        <input class="form-check-input" type="radio" name="estimating_costing" value="No">
        <label class="form-check-label">No</label>
      </div>
    </div>
  </div>
</div>

<script>
  // Get elements
  const attendedYes = document.getElementById('attendedYes');
  const attendedNo = document.getElementById('attendedNo');
  const previousTrainings = document.getElementById('previousTrainings');

  // Show/hide logic
  attendedYes.addEventListener('change', () => {
    if (attendedYes.checked) {
      previousTrainings.style.display = 'block';
    }
  });

  attendedNo.addEventListener('change', () => {
    if (attendedNo.checked) {
      previousTrainings.style.display = 'none';
    }
  });
</script>
  <!-- Modules Section -->
  <div class="col-md-6 mb-3">
    <div class="section-title">Modules Focus</div>
    <ul class="list-group">
      <li class="list-group-item">Foundations of Preliminaries & Generals</li>
      <li class="list-group-item">Fixed Charges (One-Off Costs)</li>
      <li class="list-group-item">Time-Related Charges (Ongoing Costs)</li>
      <li class="list-group-item">Project-Specific & Miscellaneous Preliminaries</li>
      <li class="list-group-item">Integrating P&G into Tender Strategy & Project Controls</li>
      <li class="list-group-item">P&G Pricing & Cash-Flow Simulation</li>
    </ul>
  </div>
</div>

      <!-- Payment Section -->

      <div class="alert alert-secondary mt-3">
           <div class="section-title">Banking Details</div>
    
        <ul class="mb-0">
          <li>Account Holder: Eswatini Contractors Association</li>
          <li>Bank: Standard Bank, Mbabane</li>
          <li>Account Number: <strong>9110003519522</strong></li>
        </ul>
         <p><strong>Note: </strong>  Use your <strong>Company Name</strong> in the payment reference.</p>
        <div class="mb-3">
        <label class="form-label">Attach Proof of Payment</label>
        <input type="file" name="payment_proof" class="form-control" accept=".pdf,.jpg,.jpeg,.png" required>
      </div>
        
      </div>

      <!-- Agreement -->
<div class="section-title">Registration Terms & Conditions</div>

<div class="card bg-light border-0 shadow-sm p-4 mb-3" style="border-radius: 15px;">
  <ul class="list-group list-group-flush">
    <li class="list-group-item bg-transparent border-0">
      <i class="bi bi-check-circle text-primary me-2"></i>
      <strong>1.</strong> Fill out the form completely and submit to info@eca.co.sz / deliver ECA Offices
    </li>
    <li class="list-group-item bg-transparent border-0">
      <i class="bi bi-check-circle text-primary me-2"></i>
      <strong>2.</strong> <strong>Payment:</strong>   full payment of a NON-REFUNDABLE COMMITMENT fee of E250 per person is required before attending the course. Pay through EFT or Direct Deposit and attach proof of payment with registration form upon return.
    </li>
    <li class="list-group-item bg-transparent border-0">
      <i class="bi bi-check-circle text-primary me-2"></i>
      <strong>3.</strong> <strong>Registration:</strong> includes Workbook, Training presentations. A certificate of attendance will be issued upon completion
    </li>
   
  </ul>

  <div class="mt-3">
    <p class="mb-1"><strong>Participant Agreement:</strong></p>
    <p class="mb-0">
      I confirm that I have read and agree to the terms and conditions of this registration 
      and commit to attending all the days of the training.
    </p>
  </div>

  <div class="form-check mt-4">
    <input class="form-check-input" type="checkbox" id="agreement" name="agreement" value="agree" required>
    <label class="form-check-label fw-semibold" for="agreement">
      I agree to the terms and conditions
    </label>
  </div>
</div>

<div class="text-center mt-4">
  <button id="submitBtn" type="submit" class="btn btn-primary btn-lg shadow-lg" disabled>
    <i class="bi bi-send-fill me-2"></i> Submit Registration
  </button>
</div>

<script>
  const checkbox = document.getElementById('agreement');
  const submitBtn = document.getElementById('submitBtn');

  // Initial disabled state
  submitBtn.disabled = true;
  submitBtn.style.background = 'grey';
  submitBtn.style.cursor = 'not-allowed';

  checkbox.addEventListener('change', () => {
    if (checkbox.checked) {
      submitBtn.disabled = false;
      submitBtn.style.background = 'linear-gradient(90deg, #0b2c66, #1b56b2)';
      submitBtn.style.cursor = 'pointer';
      submitBtn.style.boxShadow = '0 6px 20px rgba(27,86,178,0.4)';
    } else {
      submitBtn.disabled = true;
      submitBtn.style.background = 'grey';
      submitBtn.style.cursor = 'not-allowed';
      submitBtn.style.boxShadow = 'none';
    }
  });
</script>
    </form>
  </div>
</div>

<!-- Bootstrap Icons -->
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">

</body>
</html>
