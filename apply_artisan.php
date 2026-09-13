<?php
session_start();
$currentPage = basename($_SERVER['PHP_SELF']);
$sessionFullName = $_SESSION['full_name'] ?? ''; // used for signature match (if logged in)
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <title>E.C.A | Membership Renewal</title>
  <meta content="width=device-width, initial-scale=1.0" name="viewport">
  <meta content="Eswatini Contractors’ Official Website" name="description">

  <link href="img/favicon.ico" rel="icon">

  <!-- Google Fonts -->
  <link rel="preconnect" href="https://fonts.googleapis.com/">
  <link rel="preconnect" href="https://fonts.gstatic.com/" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Open+Sans:wght@400;500&family=Roboto:wght@500;700;900&display=swap" rel="stylesheet">

  <!-- Icons -->
  <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.10.0/css/all.min.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">

  <!-- Libraries -->
  <link href="lib/animate/animate.min.css" rel="stylesheet">
  <link href="lib/owlcarousel/assets/owl.carousel.min.css" rel="stylesheet">
  <link href="lib/tempusdominus/css/tempusdominus-bootstrap-4.min.css" rel="stylesheet">

  <!-- Bootstrap + Template -->
  <link href="css/bootstrap.min.css" rel="stylesheet">
  <link href="css/style.css" rel="stylesheet">
    <link href="css/theme.css" rel="stylesheet">

  <style>
    .header-custom { background-color:#000066; color:#fff; }
    .header-custom small{ color:#fff; }

    .card{
      background:#fff;
      padding:22px;
      margin-bottom:20px;
      border-radius:10px;
      box-shadow:0 5px 18px rgba(0,0,0,.08);
    }

    /* FAQ */
    .faq-btn{
      width:100%;
      text-align:left;
      background:#000066;
      color:#fff;
      padding:12px;
      margin-top:8px;
      border:none;
      border-radius:6px;
      cursor:pointer;
      font-weight:600;
    }
    .faq-btn:focus{ outline:none; }
    .faq-content{
      display:none;
      padding:12px;
      background:#f9f9f9;
      border-left:4px solid #000066;
      border-radius:6px;
      margin-bottom:6px;
    }

    /* ====== DECLARATION MODAL (Premium) ====== */
    .declaration-card{
      border:0;
      border-radius:18px;
      overflow:hidden;
      box-shadow:0 18px 55px rgba(0,0,0,.18);
      background: linear-gradient(180deg, #ffffff 0%, #fbfbfd 100%);
    }
    .decl-badge{
      width:44px;height:44px;border-radius:14px;
      display:flex;align-items:center;justify-content:center;
      font-weight:800;letter-spacing:.5px;
      color:#fff;
      background: linear-gradient(135deg, #c8102e 0%, #8b0c20 100%);
      box-shadow:0 10px 22px rgba(200,16,46,.25);
    }
    .decl-body{
      padding:16px;
      border-radius:14px;
      border:1px solid rgba(0,0,0,.06);
      background:#fff;
    }
    .decl-list{ margin:0; padding-left:1.2rem; }
    .decl-list li{ margin-bottom:8px; line-height:1.4; }

    .decl-footer{
      padding:14px 16px;
      border-radius:14px;
      border:1px dashed rgba(0,0,0,.10);
      background: rgba(200,16,46,.04);
    }
    .decl-check{
      display:flex;
      gap:12px;
      align-items:flex-start;
      cursor:pointer;
      user-select:none;
    }
    .decl-check input{
      margin-top:4px;
      width:18px;height:18px;
      accent-color:#198754;
    }
    .decl-actions{
      display:flex;
      justify-content:flex-end;
      gap:10px;
    }
    .decl-input{
      border-radius:12px;
      padding:12px 14px;
      border:1px solid rgba(0,0,0,.15);
    }
    .decl-hint{
      display:flex;
      align-items:center;
      gap:10px;
      padding:10px 12px;
      border-radius:12px;
      background: rgba(200,16,46,.10);
      border:1px solid rgba(200,16,46,.18);
      color:#7a0b1b;
      font-weight:600;
    }
    .glow-error{
      box-shadow: 0 0 0 .25rem rgba(200,16,46,.18);
      border-color: rgba(200,16,46,.45) !important;
    }

    /* Shake */
    @keyframes declShake {
      0%{transform:translateX(0)}
      15%{transform:translateX(-10px)}
      30%{transform:translateX(10px)}
      45%{transform:translateX(-8px)}
      60%{transform:translateX(8px)}
      75%{transform:translateX(-5px)}
      90%{transform:translateX(5px)}
      100%{transform:translateX(0)}
    }
    .shake{ animation:declShake .45s ease; }

    /* Code of Conduct scroll box */
    .coc-box{
      max-height: 260px;
      overflow-y: auto;
      padding: 14px;
      border-radius: 14px;
      background: #fff;
      border: 1px solid rgba(0,0,0,.10);
      box-shadow: 0 6px 18px rgba(0,0,0,.06);
    }
    .coc-status{
      display:flex;
      align-items:center;
      gap:10px;
      padding:10px 12px;
      border-radius:12px;
      background: rgba(13,110,253,.08);
      border:1px solid rgba(13,110,253,.18);
      color:#0b3d91;
      font-weight:600;
    }
    .coc-status.done{
      background: rgba(25,135,84,.10);
      border-color: rgba(25,135,84,.20);
      color:#0f5132;
    }

    /* Floating WhatsApp */
    .whatsapp-float{
      position:fixed;
      bottom:100px;
      right:35px;
      background:#25D366;
      color:#fff;
      border-radius:50%;
      width:60px;height:60px;
      display:flex;align-items:center;justify-content:center;
      font-size:30px;
      z-index:1000;
      box-shadow:0 0 10px rgba(37,211,102,.7), 0 0 20px rgba(37,211,102,.5);
      animation:glow 1.5s infinite alternate;
      text-decoration:none;
      transition:transform .3s ease;
    }
    .whatsapp-float:hover{ transform:scale(1.15); }
    @keyframes glow{
      0%{ box-shadow:0 0 5px rgba(37,211,102,.5), 0 0 10px rgba(37,211,102,.3); }
      100%{ box-shadow:0 0 20px rgba(37,211,102,.9), 0 0 40px rgba(37,211,102,.7); }
    }
    
    .faq-btn-ui{
    display:inline-block;
    background:#ffffff;
    color:#b30000;
    padding:10px 20px;
    border-radius:8px;
    font-weight:600;
    text-decoration:none;
    border:2px solid #b30000;
    transition:0.3s;
    box-shadow:0 4px 12px rgba(0,0,0,0.08);
}

.faq-btn-ui:hover{
    background:#b30000;
    color:white;
    transform:translateY(-2px);
}
  </style>
</head>

<body>

<!-- Spinner -->
<div id="spinner" class="show bg-white position-fixed translate-middle w-100 vh-100 top-50 start-50 d-flex align-items-center justify-content-center" style="z-index:9999;">
  <div class="spinner-grow text-primary" style="width:3rem;height:3rem;" role="status">
    <span class="sr-only">Loading...</span>
  </div>
</div>

<?php require __DIR__ . '/includes/site-nav.php'; ?>

<?php
$pageKicker = 'Artisans';
$pageTitle = 'Artisan application';
$pageIntro = 'Apply for artisan membership with the Eswatini Contractors Association.';
$pageCrumb = 'Application';
require __DIR__ . '/includes/page-hero.php';
?>

<div class="container my-5">
  <div class="eca-page-intro">
    <p class="eca-kicker">Artisans</p>
    <h2>Apply as an artisan</h2>
    <p>Complete the form below. The office will review your application within two working days.</p>
  </div>
  <div class="card border-0 eca-form-panel">
    <div class="card-header bg-white border-0 px-0 pt-0">
      <h3 class="m-0">Artisan application form</h3>
    </div>

    <div class="card-body">

      <!-- ✅ ONE FORM ONLY -->
      <form id="mainForm" method="POST" action="save_artisarn.php" enctype="multipart/form-data">

        <!-- Hidden fields for Code of Conduct signing -->
        <input type="hidden" name="declaration_accepted" id="declarationAccepted" value="0">
        <input type="hidden" name="declaration_signature" id="declarationSignature" value="">
        <input type="hidden" name="declaration_designation" id="declarationDesignation" value="">

        <!-- A) Company -->
        <h5 class="text-primary">A) Personal Information</h5>
        <div class="row">
          <div class="col-md-6 mb-3">
            <label class="form-label">Fullnames </label>
            <input type="text" name="registered_name" class="form-control" required>
          </div>
         <div class="col-md-6 mb-3">
            <label class="form-label">Email Address</label>
            <input type="email" name="email" class="form-control" required>
          </div>
        </div>

        <div class="row">
       
          <div class="col-md-6 mb-3">
            <label class="form-label">Cellphone</label>
            <input type="text" name="cellphone" class="form-control">
          </div>
          
          <div class="col-md-6 mb-3">
            <label class="form-label">Telephone</label>
            <input type="text" name="telephone" class="form-control">
          </div>
        </div>
     
        <div class="row">
           <div class="col-md-6 mb-3">
                 <label class="form-label"> Gender</label>
            <select name="gender" class="form-select">
             <option value="MALE">Male</option>
         <option value="FEMALE">Female</option>
            </select>
          </div>
          <div class="col-md-6 mb-3">
            <label class="form-label">Region</label>
            <select name="region" class="form-select">
              <option>Hhohho</option>
              <option>Manzini</option>
              <option>Lubombo</option>
              <option>Shiselweni</option>
            </select>
          </div>
        </div>


  <!-- C) Artisan Trade Category -->
<h5 class="text-primary">C) Artisan Trade Category</h5>
<div class="row">
  <div class="col-md-6 mb-3">
    <select name="specialisation" id="specialisationSelect" class="form-select" required>
      <option value="">-- Select Artisan Trade --</option>

   <option value="Bricklaying & Masonry">Bricklaying & Masonry</option>
<option value="Carpentry, Joinery & Shopfitting">Carpentry, Joinery & Shopfitting</option>
<option value="Plumbing">Plumbing</option>
<option value="Electrical Installation">Electrical Installation</option>
<option value="Welding & Fabrication">Welding & Fabrication</option>
<option value="Flooring (Tiling & Terrazzo)">Flooring (Tiling & Terrazzo)</option>
<option value="Painting, Decorating & Glazing">Painting, Decorating & Glazing</option>
<option value="Roofing">Roofing</option>
<option value="Partitions & Ceiling Installation">Partitions & Ceiling Installation</option>
<option value="Aluminium & Glass Fitting">Aluminium & Glass Fitting</option>
<option value="Air Conditioning & Refrigeration">Air Conditioning & Refrigeration</option>
<option value="Motor Mechanics">Motor Mechanics</option>
<option value="ICT, Electronics & CCTV Installation">ICT, Electronics & CCTV Installation</option>
<option value="Solar Installation">Solar Installation</option>
<option value="Landscaping & Gardening">Landscaping & Gardening</option>
<option value="Fencing">Fencing</option>
<option value="Tree Cutting & Bush Clearing">Tree Cutting & Bush Clearing</option>
<option value="Borehole Drilling & Water Systems">Borehole Drilling & Water Systems</option>
<option value="Waterproofing">Waterproofing</option>
<option value="Mechanical Works">Mechanical Works</option>
<option value="Other">Other</option>
    </select>
  </div>
</div>

        <div class="row" id="otherBox" style="display:none;">
          <div class="col-md-6 mb-3">
            <input type="text" name="specialisation_other" id="otherInput" class="form-control" placeholder="Please specify your specialisation">
          </div>
        </div>



        <!-- E) Terms -->
        <h5 class="text-primary">E) Terms and Conditions</h5>
        <div class="d-flex flex-wrap align-items-center gap-2 mb-3">
          <button type="button" id="cocBtn" class="btn btn-outline-primary" data-bs-toggle="modal" data-bs-target="#declarationModal">
            Read Code of Conduct & Sign
          </button>
          <span class="text-muted">
            <i class="bi bi-shield-check me-1"></i> Required before submission
          </span>
        </div>

        <!-- F) Attachments -->
        <h5 class="text-primary">F) Attachments / Supporting Documents</h5>
        <p>Please upload the following documents (<strong>PDF only</strong>, Max size: 2MB each):</p>

        <div class="row">
          <div class="col-md-6 mb-3">
            <label class="form-label">Graded Test / Qualification</label>
            <input type="file" name="certificate_incorporation" class="form-control file-limit" accept="application/pdf" required>
          </div>
      
        </div>


        <div class="row">
          <div class="col-md-6 mb-3">
            <label class="form-label">ID Copies of Directors (PDFs)</label>
            <input type="file" name="id_copies[]" class="form-control file-limit" accept="application/pdf" multiple>
          </div>
          <div class="col-md-6 mb-3">
            <label class="form-label">Proof of Payment (PDF)</label>
            <input type="file" name="proof_payment" class="form-control file-limit" accept="application/pdf" required>
          </div>
        </div>

        <div class="alert alert-info">
          <strong>Banking Details</strong><br>
          Name: Eswatini Contractors Association <br>
          Bank: Standard Bank <br>
          Branch: Mbabane <br>
          Account Number: <strong>9110003519522</strong><br>
          Branch Code: <strong>663164</strong><br>
          Application Fee: <strong>E750.00</strong><br>
          <em>*Use company name as payment reference.</em>
        </div>

        <button type="submit" id="submitBtn" class="btn btn-success px-4 py-2" disabled>
          Submit Application
        </button>

      </form>
    </div>
  </div>
</div>

<!-- =========================
     CODE OF CONDUCT MODAL
========================= -->
<div class="modal fade" id="declarationModal" tabindex="-1" aria-labelledby="declarationModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered modal-lg">
    <div class="modal-content declaration-card" id="declCard">

      <div class="modal-header border-0 pb-0">
        <div class="d-flex align-items-center gap-2">
          <div class="decl-badge">ECA</div>
          <div>
            <h5 class="modal-title mb-0" id="declarationModalLabel">Code of Conduct & Declaration</h5>
            <small class="text-muted">Scroll the Code of Conduct to the bottom to enable signing</small>
          </div>
        </div>
        <!-- IMPORTANT: data-bs-dismiss added so close works normally -->
        <button type="button" class="btn-close" aria-label="Close" id="declCloseX" data-bs-dismiss="modal"></button>
      </div>

      <div class="modal-body pt-3">
        <div class="decl-body">

          <div class="mb-3">
            <h6 class="fw-bold mb-2">ECA Code of Conduct (Required Reading)</h6>

            <div id="cocScrollBox" class="coc-box">
              <p><strong>Eswatini Contractors Association (ECA) – Code of Conduct for Contractors</strong></p>
              <p>
                By this Code of Conduct, Eswatini Contractors Association (ECA) expect contractors to act socially and
                environmentally responsible and actively work for the implementation of the standards and principles set out forth.
              </p>
<ol style="margin:0; padding-left:20px; text-align:justify;">
  <li style="margin-bottom:6px; text-align:justify;">
    <strong>Health &amp; Safety:</strong> Provide safe and hygienic working environments; prioritize worker safety and prevent accidents/injury.
  </li>
  <li style="margin-bottom:6px; text-align:justify;">
    <strong>Anti-Corruption:</strong> Avoid corruption; ensure integrity, accountability, fairness, and professional conduct.
  </li>
  <li style="margin-bottom:6px; text-align:justify;">
    <strong>Sexual Harassment, Exploitation and Abuse:</strong> Must not sexually harass, exploit, or sexually abuse any individual.
  </li>
  <li style="margin-bottom:6px; text-align:justify;">
    <strong>Fairness:</strong> Be fair in business relationships, pricing, and contracts to give clients best possible value.
  </li>
  <li style="margin-bottom:6px; text-align:justify;">
    <strong>Law:</strong> Comply with local laws and the Association’s Constitution, Codes of Conduct, and by-laws.
  </li>
  <li style="margin-bottom:6px; text-align:justify;">
    <strong>Insurance:</strong> Maintain proper insurance coverage for business, employees and clients.
  </li>
  <li style="margin-bottom:6px; text-align:justify;">
    <strong>Quality:</strong> Perform work in good workmanship aligned with industry standards.
  </li>
  <li style="margin-bottom:6px; text-align:justify;">
    <strong>Professionalism:</strong> Meet professional standards; continue learning and share in healthy competitive spirit.
  </li>
  <li style="margin-bottom:6px; text-align:justify;">
    <strong>Scheduling:</strong> Provide realistic schedules and make every effort to meet them.
  </li>
  <li style="margin-bottom:6px; text-align:justify;">
    <strong>Warranty:</strong> Acknowledge defects and correct them in a mutually agreeable and timely manner.
  </li>
  <li style="text-align:justify;">
    <strong>Training and Education:</strong> Support training activities developed and provided by the Association.
  </li>
</ol>

              <hr>
              <p class="mb-0"><strong>Complaints:</strong> Report suspected breaches to <strong>info@eca.co.sz</strong>.</p>
            </div>

            <div class="coc-status mt-2" id="cocStatus">
              <i class="bi bi-info-circle"></i>
              <span>Scroll to the bottom to enable signing.</span>
            </div>
          </div>

          <hr class="my-3">
<h6 class="fw-bold mb-2">Declaration</h6>

<p class="mb-3" style="text-align:justify;">
  I hereby declare that the information provided in this application is true and correct. 
  I further undertake to abide by the Constitution of the Eswatini Contractors Association (ECA), 
  together with its rules and regulations, as legally amended from time to time. I acknowledge 
  that application and subscription fees are non-refundable in the event that my membership is declined.
</p>

<p class="mb-0" style="text-align:justify;">
  I also declare my commitment to comply with the Anti-Money Laundering Act by ensuring 
  that all payments made to ECA are traceable to my bank account. I consent to the storage and 
  use of my personal data by ECA in accordance with the Data Protection Act and its amendments.
</p>

        <div class="decl-footer mt-4">

          <label class="decl-check">
            <input type="checkbox" id="agreeDeclaration" disabled>
            <span>
              I have read the Code of Conduct and agree to the Declaration.
              <small class="d-block text-muted">Enabled after scrolling to the bottom.</small>
            </span>
          </label>

          <div class="mt-3">
            <label class="form-label fw-semibold mb-1">Designation / Position</label>
            <input type="text" class="form-control decl-input" id="designationInput" placeholder="e.g. Director / Owner / Manager" autocomplete="off" disabled>
            <small class="text-muted d-block mt-1">Required.</small>
          </div>

          <div class="mt-3">
            <label class="form-label fw-semibold mb-1">Signature (type your full name)</label>
            <input type="text" class="form-control decl-input" id="signatureName"
                   placeholder="e.g. Your Full Name "
                   autocomplete="off" disabled>
           
            <div class="decl-hint mt-2" id="declHint" style="display:none;">
              <i class="bi bi-exclamation-triangle"></i>
              <span id="declHintText">Please complete the declaration to continue.</span>
            </div>
          </div>

          <div class="decl-actions mt-3">
            <!-- IMPORTANT: data-bs-dismiss so Cancel always closes -->
            <button type="button" class="btn btn-light" id="declCancel" data-bs-dismiss="modal">Cancel</button>
            <button type="button" class="btn btn-success px-4" id="declConfirm" disabled>Confirm & Continue</button>
          </div>

        </div>
      </div>
    </div>
  </div>
</div>
</div>
<!-- FAQ -->
<div class="container my-5">
  <div class="card">
    <h2>❓ Frequently Asked Questions</h2>

    <div class="faq">

      <button type="button" class="faq-btn">Why is the “Submit Application” button disabled?</button>
      <div class="faq-content">
        The Submit button becomes active only after you read the Code of Conduct, tick the agreement box,
        type your full name as a signature, and add your designation.
      </div>

      <button type="button" class="faq-btn">How do I get “✅ Code of Conduct Signed”?</button>
      <div class="faq-content">
        1) Click <strong>Read Code of Conduct & Sign</strong><br>
        2) Scroll the Code of Conduct to the <strong>bottom</strong><br>
        3) Tick <strong>I have read and agree</strong><br>
        4) Type your <strong>full name</strong> exactly as registered<br>
        5) Enter your <strong>Designation</strong> (e.g. Director)<br>
        6) Click <strong>Confirm & Continue</strong><br>
        ✅ The button changes to <strong>Code of Conduct Signed</strong>.
      </div>

      <button type="button" class="faq-btn">I can’t tick the checkbox / signature is disabled</button>
      <div class="faq-content">
        You must <strong>scroll to the bottom</strong> of the Code of Conduct first. Then signing unlocks.
      </div>

      <button type="button" class="faq-btn">What file format should I upload?</button>
      <div class="faq-content">
        All documents must be uploaded in <strong>PDF format only</strong> (Max 2MB each).
      </div>

      <button type="button" class="faq-btn">How long does approval take?</button>
      <div class="faq-content">
        Processing time depends on document completeness. It may take <strong>2 working days</strong>.
      </div>

      <button type="button" class="faq-btn">Who do I contact for support?</button>
      <div class="faq-content">
        Email <strong>support@eca.co.sz</strong> for technical or application assistance.
      </div>

    </div>
  </div>
</div>

<!-- WhatsApp -->
<a href="https://wa.me/26876702898" target="_blank" class="whatsapp-float">
  <i class="bi bi-whatsapp"></i>
</a>

<!-- JS -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

<script>
  // Spinner off
  window.addEventListener('load', function(){
    const spinner = document.getElementById('spinner');
    if(spinner) spinner.classList.remove('show');
  });

  // Specialisation Other
  const select = document.getElementById("specialisationSelect");
  const otherBox = document.getElementById("otherBox");
  const otherInput = document.getElementById("otherInput");
  if(select){
    select.addEventListener("change", function(){
      if(this.value === "Other"){
        otherBox.style.display = "block";
        otherInput.required = true;
      }else{
        otherBox.style.display = "none";
        otherInput.required = false;
        otherInput.value = "";
      }
    });
  }

  // Owners add/remove
  document.getElementById('add-owner')?.addEventListener('click', function(){
    const wrapper = document.getElementById('owners-wrapper');
    const firstRow = document.querySelector('.owner-row');
    if(!firstRow) return;

    const newRow = firstRow.cloneNode(true);
    newRow.querySelectorAll('input').forEach(i => i.value = "");
    wrapper.appendChild(newRow);

    newRow.querySelector('.remove-owner')?.addEventListener('click', function(){
      newRow.remove();
    });
  });

  document.querySelectorAll('.remove-owner').forEach(btn=>{
    btn.addEventListener('click', function(){
      this.closest('.owner-row')?.remove();
    });
  });
document.getElementById("mainForm").addEventListener("submit", function(e){

    let requiredFiles = [
        "certificate_incorporation",
        "trading_licence",
        "proof_payment",
         "id_copies[]"
    ];

    let missing = [];

    requiredFiles.forEach(function(name){
        let input = document.querySelector('input[name="'+name+'"]');
        if(input && input.files.length === 0){
            missing.push(name.replace(/_/g,' ').toUpperCase());
        }
    });

    if(missing.length > 0){
        e.preventDefault();

        alert("Please upload the following required documents:\n\n" + missing.join("\n"));
        return false;
    }
});

  // PDF only + 2MB each
  document.querySelectorAll(".file-limit").forEach(input => {
    input.addEventListener("change", function(){
      const maxSize = 2 * 1024 * 1024;
      for(const file of this.files){
        const isPDF = file.type === "application/pdf" || file.name.toLowerCase().endsWith(".pdf");
        if(!isPDF){
          alert("Only PDF files are allowed.");
          this.value = "";
          return;
        }
        if(file.size > maxSize){
          alert(file.name + " exceeds 2MB limit.");
          this.value = "";
          return;
        }
      }
    });
  });

  // =========================
  // CODE OF CONDUCT SIGNING
  // =========================
  const REGISTERED_FULL_NAME = "<?= addslashes($sessionFullName) ?>";

  const declModalEl    = document.getElementById('declarationModal');
  const agree          = document.getElementById('agreeDeclaration');
  const signatureInput = document.getElementById('signatureName');
  const designationInp = document.getElementById('designationInput');

  const submitBtn      = document.getElementById('submitBtn');
  const acceptedField  = document.getElementById('declarationAccepted');
  const signatureField = document.getElementById('declarationSignature');
  const designationFld = document.getElementById('declarationDesignation');

  const confirmBtn     = document.getElementById('declConfirm');
  const hint           = document.getElementById('declHint');
  const hintText       = document.getElementById('declHintText');

  const cocBox         = document.getElementById('cocScrollBox');
  const cocStatus      = document.getElementById('cocStatus');
  const cocBtn         = document.getElementById("cocBtn");

  // Allow outside click + ESC to close
  const declModal = new bootstrap.Modal(declModalEl, { backdrop:true, keyboard:true });

  function normalizeName(str){
    return (str || "").trim().replace(/\s+/g, " ");
  }

  function clearHint(){
    hint.style.display = "none";
    signatureInput.classList.remove('glow-error');
    designationInp.classList.remove('glow-error');
  }

  function enableSubmit(){
    submitBtn.disabled = false;
    acceptedField.value = "1";
    signatureField.value = normalizeName(signatureInput.value);
    designationFld.value = normalizeName(designationInp.value);
  }

  function disableSubmit(){
    submitBtn.disabled = true;
    acceptedField.value = "0";
    signatureField.value = "";
    designationFld.value = "";
  }

  function isDeclarationValid(){
    const typed = normalizeName(signatureInput.value);
    const reg   = normalizeName(REGISTERED_FULL_NAME);
    const desig = normalizeName(designationInp.value);

    if(!agree.checked) return false;
    if(desig.length < 2) return false;
    if(typed.length < 2) return false;

    if(reg){
      return typed.toLowerCase() === reg.toLowerCase();
    }
    return true;
  }

  function validateUI(){
    if(isDeclarationValid()){
      clearHint();
      confirmBtn.disabled = false;
      enableSubmit();
      return true;
    }else{
      confirmBtn.disabled = true;
      disableSubmit();
      return false;
    }
  }

  function unlockSigning(){
    agree.disabled = false;
    signatureInput.disabled = false;
    designationInp.disabled = false;

    cocStatus.classList.add('done');
    cocStatus.innerHTML = '<i class="bi bi-check-circle"></i><span>Reading complete. You may now sign.</span>';
  }

  cocBox?.addEventListener('scroll', () => {
    const nearBottom = cocBox.scrollTop + cocBox.clientHeight >= cocBox.scrollHeight - 5;
    if(nearBottom && agree.disabled) unlockSigning();
  });

  declModalEl.addEventListener('shown.bs.modal', () => {
    cocBox.scrollTop = 0;

    agree.checked = false;
    agree.disabled = true;

    signatureInput.value = "";
    signatureInput.disabled = true;

    designationInp.value = "";
    designationInp.disabled = true;

    cocStatus.classList.remove('done');
    cocStatus.innerHTML = '<i class="bi bi-info-circle"></i><span>Scroll to the bottom to enable signing.</span>';

    clearHint();
    validateUI();
  });

  agree.addEventListener('change', validateUI);
  signatureInput.addEventListener('input', validateUI);
  designationInp.addEventListener('input', validateUI);

  function markSigned(){
    cocBtn.classList.remove("btn-outline-primary");
    cocBtn.classList.add("btn-success");
    cocBtn.innerHTML = '<i class="bi bi-check-circle me-1"></i> Code of Conduct Signed';
    cocBtn.disabled = true;
    cocBtn.removeAttribute("data-bs-toggle");
    cocBtn.removeAttribute("data-bs-target");
    submitBtn.scrollIntoView({behavior:"smooth", block:"center"});
  }

  confirmBtn.addEventListener('click', () => {
    if(isDeclarationValid()){
      enableSubmit();
      markSigned();
      declModal.hide();
    }else{
      hintText.textContent = "Please complete the declaration (scroll, tick, designation, signature).";
      hint.style.display = "flex";
      if(!normalizeName(designationInp.value)) designationInp.classList.add('glow-error');
      if(!normalizeName(signatureInput.value)) signatureInput.classList.add('glow-error');
    }
  });

  // When modal closes without valid signing, keep submit disabled
  declModalEl.addEventListener('hidden.bs.modal', () => {
    if(!isDeclarationValid()) disableSubmit();
  });

  // Initial state
  disableSubmit();
</script>

<?php require __DIR__ . '/includes/site-footer.php'; ?>
</body>
</html>
