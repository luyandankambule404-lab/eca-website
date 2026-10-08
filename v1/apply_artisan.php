<?php
require_once __DIR__ . '/includes/session.php';
$currentPage = basename($_SERVER['PHP_SELF']);
$sessionFullName = $_SESSION['full_name'] ?? ''; // used for signature match (if logged in)
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <?php
  require_once __DIR__ . '/includes/public-seo.php';
  eca_public_head(
      'Artisan Membership Application',
      'Apply for artisan membership with the Eswatini Contractors Association and submit your trade credentials and supporting documents online.',
      '/apply_artisan.php'
  );
  ?>
  <meta content="width=device-width, initial-scale=1.0" name="viewport">

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
    <link href="css/theme.css?v=20260926-forms" rel="stylesheet">

      <?php require_once __DIR__ . '/includes/responsive-assets.php'; eca_responsive_assets(); ?>
<style>
    .header-custom { background-color:#192754; color:#fff; }
    .header-custom small{ color:#fff; }

    .card{
      background:#fff;
      padding:10px 12px;
      margin-bottom:12px;
      border-radius:10px;
      box-shadow:0 5px 14px rgba(0,0,0,.06);
    }

    /* FAQ */
    .faq-btn{
      width:100%;
      text-align:left;
      background:#192754;
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
      border-left:4px solid #192754;
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

    /* Compact artisan application — match membership application.php */
    body.eca-apply-compact {
      background: #eef2f6;
    }
    body.eca-apply-compact .eca-inner-hero,
    body.eca-apply-compact .eca-page-hero {
      display: none !important;
    }
    body.eca-apply-compact .eca-site-footer {
      margin-top: 28px;
    }
    body.eca-apply-compact .eca-apply-wrap {
      max-width: 760px;
      width: min(760px, calc(100% - 24px));
      margin: 18px auto 24px;
      padding: 0;
    }
    body.eca-apply-compact:not(.eca-home) .eca-form-panel {
      padding: 22px 24px 24px !important;
      border-radius: 14px !important;
      box-shadow: 0 10px 28px rgba(25,39,84,.1);
    }
    body.eca-apply-compact .eca-form-panel .card-header,
    body.eca-apply-compact:not(.eca-home) .eca-form-panel .card-body {
      padding: 0 !important;
    }
    body.eca-apply-compact .eca-apply-head {
      display: flex;
      align-items: center;
      justify-content: space-between;
      gap: 12px;
      margin-bottom: 14px;
    }
    body.eca-apply-compact .eca-apply-head h1 {
      margin: 0;
      font-size: 1.35rem;
      font-weight: 800;
      line-height: 1.25;
      color: #192754;
    }
    body.eca-apply-compact .eca-apply-head a {
      font-size: .85rem;
      font-weight: 700;
      color: #5a6680;
      text-decoration: none;
    }
    body.eca-apply-compact .eca-apply-dots {
      display: grid;
      grid-template-columns: repeat(4, minmax(0, 1fr));
      gap: 8px;
      margin: 0 0 16px;
    }
    body.eca-apply-compact .eca-apply-dots button {
      min-height: 38px;
      padding: 6px 8px;
      border: 0;
      border-radius: 8px;
      background: #e8edf3;
      color: #5a6680;
      font-size: .78rem;
      font-weight: 800;
      line-height: 1.15;
    }
    body.eca-apply-compact .eca-apply-dots button.is-on {
      background: #192754;
      color: #fff;
    }
    body.eca-apply-compact .eca-apply-dots button.is-done {
      background: #d7e8dc;
      color: #146c43;
    }
    body.eca-apply-compact .eca-apply-step { display: none; }
    body.eca-apply-compact .eca-apply-step.is-on { display: block; }
    body.eca-apply-compact .eca-form-panel h5 {
      font-size: .82rem;
      font-weight: 800;
      letter-spacing: .05em;
      text-transform: uppercase;
      margin: 0 0 12px;
      padding-bottom: 6px;
      border-bottom: 1px solid #e4e8ef;
    }
    body.eca-apply-compact .form-label {
      font-size: .86rem;
      margin-bottom: 4px;
      font-weight: 650;
    }
    body.eca-apply-compact:not(.eca-home) .eca-form-panel .form-control,
    body.eca-apply-compact:not(.eca-home) .eca-form-panel .form-select,
    body.eca-apply-compact:not(.eca-home) .eca-form-panel input[type="text"],
    body.eca-apply-compact:not(.eca-home) .eca-form-panel input[type="email"],
    body.eca-apply-compact:not(.eca-home) .eca-form-panel input[type="tel"],
    body.eca-apply-compact:not(.eca-home) .eca-form-panel input[type="number"],
    body.eca-apply-compact:not(.eca-home) .eca-form-panel input[type="file"],
    body.eca-apply-compact:not(.eca-home) .eca-form-panel textarea,
    body.eca-apply-compact:not(.eca-home) .eca-form-panel select {
      min-height: 44px !important;
      height: 44px !important;
      padding: 8px 12px !important;
      font-size: .95rem !important;
      border-width: 1px !important;
      border-radius: 8px !important;
      line-height: 1.3 !important;
    }
    body.eca-apply-compact:not(.eca-home) .eca-form-panel textarea.form-control {
      height: auto !important;
      min-height: 72px !important;
    }
    body.eca-apply-compact:not(.eca-home) .eca-form-panel input[type="file"] {
      height: auto !important;
      min-height: 42px !important;
      padding: 6px 10px !important;
      font-size: .86rem !important;
    }
    body.eca-apply-compact .mb-3 { margin-bottom: 14px !important; }
    body.eca-apply-compact .mb-2 { margin-bottom: 10px !important; }
    body.eca-apply-compact .row {
      --bs-gutter-x: 14px;
      --bs-gutter-y: 0;
    }
    body.eca-apply-compact .eca-bank-box {
      padding: 12px 14px !important;
      font-size: .86rem;
      line-height: 1.45;
      margin-bottom: 14px;
    }
    body.eca-apply-compact .eca-bank-grid {
      display: grid;
      grid-template-columns: 1fr 1fr;
      gap: 4px 16px;
      margin: 6px 0 0;
    }
    body.eca-apply-compact #submitBtn,
    body.eca-apply-compact #applyBack,
    body.eca-apply-compact #applyNext {
      min-height: 44px;
      padding: 10px 16px !important;
      font-size: .95rem;
    }
    body.eca-apply-compact .eca-apply-nav {
      display: flex;
      gap: 10px;
      margin-top: 16px;
    }
    body.eca-apply-compact .eca-apply-nav .btn { flex: 1; }
    body.eca-apply-compact .form-text {
      font-size: .82rem;
      margin-top: 6px;
    }
    body.eca-apply-compact #cocBtn {
      padding: 8px 12px;
      font-size: .86rem;
    }
    body.eca-apply-compact .eca-faq-wrap {
      max-width: 760px;
      width: min(760px, calc(100% - 24px));
      margin: 0 auto 28px;
      padding: 0;
    }
    body.eca-apply-compact .eca-faq-card {
      padding: 14px 16px !important;
      border-radius: 12px !important;
    }
    body.eca-apply-compact .eca-disclose {
      margin: 0 0 4px;
    }
    body.eca-apply-compact .eca-disclose-btn,
    body.eca-apply-compact .eca-faq-card summary {
      display: flex;
      align-items: center;
      justify-content: space-between;
      gap: 12px;
      width: 100%;
      min-height: 44px;
      padding: 10px 12px;
      border: 1px solid #dce3ec;
      border-radius: 10px;
      background: #f7f9fc;
      color: #192754;
      font-size: .92rem;
      font-weight: 800;
      line-height: 1.25;
      text-align: left;
      cursor: pointer;
      list-style: none;
    }
    body.eca-apply-compact .eca-faq-card summary::-webkit-details-marker { display: none; }
    body.eca-apply-compact .eca-disclose-btn:hover,
    body.eca-apply-compact .eca-faq-card summary:hover {
      background: #fff;
      border-color: rgba(25, 39, 84, .35);
    }
    body.eca-apply-compact .eca-disclose-btn:focus-visible,
    body.eca-apply-compact .eca-faq-card summary:focus-visible {
      outline: 3px solid rgba(200, 16, 46, .28);
      outline-offset: 2px;
    }
    body.eca-apply-compact .eca-disclose-arrow {
      width: 28px;
      height: 28px;
      flex: 0 0 28px;
      border-radius: 50%;
      background: #192754;
      display: grid;
      place-items: center;
      transition: transform 180ms ease, background 180ms ease;
    }
    body.eca-apply-compact .eca-disclose-arrow::before {
      content: "";
      width: 8px;
      height: 8px;
      margin-top: -3px;
      border-right: 2px solid #fff;
      border-bottom: 2px solid #fff;
      transform: rotate(45deg);
    }
    body.eca-apply-compact .eca-disclose-btn[aria-expanded="true"],
    body.eca-apply-compact .eca-faq-card[open] > summary {
      border-color: #192754;
      background: #fff;
    }
    body.eca-apply-compact .eca-disclose-btn[aria-expanded="true"] .eca-disclose-arrow,
    body.eca-apply-compact .eca-faq-card[open] > summary .eca-disclose-arrow {
      background: #c8102e;
      transform: rotate(180deg);
    }
    body.eca-apply-compact .eca-disclose-panel { margin-top: 8px; }
    body.eca-apply-compact .eca-disclose-panel .eca-bank-box { margin-bottom: 0; }
    body.eca-apply-compact .eca-faq-card .faq { margin-top: 10px; }
    body.eca-apply-compact .eca-faq-card .faq-btn {
      min-height: 42px;
      padding: 10px 40px 10px 12px !important;
      margin-top: 0;
      margin-bottom: 6px;
      font-size: .88rem;
    }
    body.eca-apply-compact .eca-faq-card .faq-btn::after {
      width: 22px;
      height: 22px;
      right: 10px;
    }
    body.eca-apply-compact .eca-faq-card .faq-content {
      padding: 10px 12px !important;
      font-size: .88rem;
    }
    body.eca-apply-compact #declarationModal .modal-dialog {
      max-width: 640px;
      margin: 16px auto;
    }
    body.eca-apply-compact .declaration-card .modal-body { padding: 14px 16px 18px; }
    body.eca-apply-compact .decl-body { padding: 14px; }
    body.eca-apply-compact .coc-box { max-height: 220px; }
    @media (max-width: 767.98px) {
      body.eca-apply-compact .eca-apply-wrap,
      body.eca-apply-compact .eca-faq-wrap {
        width: min(760px, calc(100% - 16px));
      }
      body.eca-apply-compact:not(.eca-home) .eca-form-panel {
        padding: 16px !important;
      }
      body.eca-apply-compact .eca-apply-dots button {
        font-size: .68rem;
        min-height: 34px;
      }
      body.eca-apply-compact .eca-bank-grid { grid-template-columns: 1fr; }
    }
  </style>
</head>

<body class="eca-apply-compact">

<!-- Spinner -->
<div id="spinner" class="show bg-white position-fixed translate-middle w-100 vh-100 top-50 start-50 d-flex align-items-center justify-content-center" style="z-index:9999;">
  <div class="spinner-grow text-primary" style="width:3rem;height:3rem;" role="status">
    <span class="sr-only">Loading...</span>
  </div>
</div>

<?php require __DIR__ . '/includes/site-nav.php'; ?>

<div class="container eca-apply-wrap" id="main-content" tabindex="-1">
  <div class="card border-0 eca-form-panel">
    <div class="card-body">

      <form id="mainForm" method="POST" action="save_artisarn.php" enctype="multipart/form-data">
        <input type="hidden" name="csrf_token" id="csrfToken" value="<?= htmlspecialchars(eca_public_csrf_token(), ENT_QUOTES, 'UTF-8') ?>">
        <input type="hidden" name="declaration_accepted" id="declarationAccepted" value="0">
        <input type="hidden" name="declaration_signature" id="declarationSignature" value="">
        <input type="hidden" name="declaration_designation" id="declarationDesignation" value="">

        <div class="eca-apply-head">
          <h1>Artisan application</h1>
          <a href="/membership-registration.php">Back</a>
        </div>

        <nav class="eca-apply-dots" aria-label="Application steps">
          <button type="button" data-go="0" class="is-on">1 Personal</button>
          <button type="button" data-go="1">2 Trade</button>
          <button type="button" data-go="2">3 Documents</button>
          <button type="button" data-go="3">4 Submit</button>
        </nav>

        <div class="eca-apply-step is-on" data-step="0">
          <h5 class="text-primary" id="step-personal">Personal</h5>
          <div class="row">
            <div class="col-6 mb-3">
              <label class="form-label" for="artisanName">Full name</label>
              <input type="text" name="registered_name" id="artisanName" class="form-control" autocomplete="name" required>
            </div>
            <div class="col-6 mb-3">
              <label class="form-label" for="artisanEmail">Email</label>
              <input type="email" name="email" id="artisanEmail" class="form-control" autocomplete="email" inputmode="email" required>
            </div>
            <div class="col-6 mb-3">
              <label class="form-label" for="cellphone">Cell</label>
              <input type="text" name="cellphone" id="cellphone" class="form-control" autocomplete="tel" inputmode="tel">
            </div>
            <div class="col-6 mb-3">
              <label class="form-label" for="telephone">Tel</label>
              <input type="text" name="telephone" id="telephone" class="form-control" autocomplete="tel" inputmode="tel">
            </div>
            <div class="col-6 mb-3">
              <label class="form-label" for="gender">Gender</label>
              <select name="gender" id="gender" class="form-select" autocomplete="sex">
                <option value="MALE">Male</option>
                <option value="FEMALE">Female</option>
              </select>
            </div>
            <div class="col-6 mb-3">
              <label class="form-label" for="region">Region</label>
              <select name="region" id="region" class="form-select" autocomplete="address-level1" required>
                <option>Hhohho</option>
                <option>Manzini</option>
                <option>Lubombo</option>
                <option>Shiselweni</option>
              </select>
            </div>
          </div>
        </div>

        <div class="eca-apply-step" data-step="1">
          <h5 class="text-primary" id="step-trade">Trade</h5>
          <div class="row">
            <div class="col-6 mb-3">
              <label class="form-label" for="specialisationSelect">Artisan trade</label>
              <select name="specialisation" id="specialisationSelect" class="form-select" required>
                <option value="">-- Select --</option>
                <option value="Building">Building</option>
                <option value="Civil">Civil</option>
                <option value="Electrical/Mechanical">Electrical/Mechanical</option>
                <option value="Specialist">Specialist</option>
              </select>
            </div>
          </div>
          <div class="row" id="otherBox" style="display:none;">
            <div class="col-12 mb-3">
              <label class="form-label" for="otherInput">Specify specialisation</label>
              <input type="text" name="specialisation_other" id="otherInput" class="form-control" autocomplete="off" placeholder="Please specify">
            </div>
          </div>
        </div>

        <div class="eca-apply-step" data-step="2">
          <h5 class="text-primary" id="step-documents">Documents (PDF, 2MB)</h5>
          <div class="row">
            <div class="col-12 mb-3">
              <label class="form-label" for="artisanQualification">Graded test / qualification *</label>
              <input type="file" name="certificate_incorporation" id="artisanQualification" class="form-control file-limit" accept="application/pdf" required>
            </div>
            <div class="col-12 mb-3">
              <label class="form-label" for="artisanIdCopies">ID copies *</label>
              <input type="file" name="id_copies[]" id="artisanIdCopies" class="form-control file-limit" accept="application/pdf" multiple required>
            </div>
            <div class="col-12 mb-3">
              <label class="form-label" for="proofPayment">Proof of pay *</label>
              <input type="file" name="proof_payment" id="proofPayment" class="form-control file-limit" accept="application/pdf" required>
            </div>
          </div>
          <div class="eca-disclose">
            <button type="button" class="eca-disclose-btn" aria-expanded="false" aria-controls="bankDetailsPanel">
              <span>Payment &amp; bank details — E750</span>
              <span class="eca-disclose-arrow" aria-hidden="true"></span>
            </button>
            <div id="bankDetailsPanel" class="eca-disclose-panel" hidden>
              <div class="alert alert-info eca-bank-box">
                <strong>Pay E750</strong> — use company name as reference
                <div class="eca-bank-grid">
                  <span>Eswatini Contractors Association</span>
                  <span>Standard Bank, Mbabane</span>
                  <span>Acc <strong>9110003519522</strong></span>
                  <span>Branch <strong>663164</strong></span>
                </div>
              </div>
            </div>
          </div>
        </div>

        <div class="eca-apply-step" data-step="3">
          <h5 class="text-primary" id="step-terms">Sign &amp; send</h5>
          <div class="d-flex flex-wrap align-items-center gap-2 mb-3">
            <button type="button" id="cocBtn" class="btn btn-outline-primary" data-bs-toggle="modal" data-bs-target="#declarationModal">
              Read &amp; sign
            </button>
            <span class="text-muted small">Required before submit</span>
          </div>
          <button type="submit" id="submitBtn" class="btn btn-success w-100" disabled aria-describedby="submit-help">
            Submit
          </button>
          <p id="submit-help" class="form-text">Sign the Code of Conduct before submitting.</p>
        </div>

        <div class="eca-apply-nav">
          <button type="button" class="btn btn-outline-secondary" id="applyBack" disabled>Back</button>
          <button type="button" class="btn btn-primary" id="applyNext">Next</button>
        </div>

      </form>
    </div>
  </div>
</div>

<!-- =========================
     CODE OF CONDUCT MODAL
========================= -->
<div class="modal fade" id="declarationModal" tabindex="-1" role="dialog" aria-modal="true" aria-labelledby="declarationModalLabel" aria-hidden="true">
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

            <div class="coc-status mt-2" id="cocStatus" role="status" aria-live="polite">
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
            <input type="checkbox" id="agreeDeclaration" aria-required="true" disabled>
            <span>
              I have read the Code of Conduct and agree to the Declaration.
              <small class="d-block text-muted">Enabled after scrolling to the bottom.</small>
            </span>
          </label>

          <div class="mt-3">
            <label class="form-label fw-semibold mb-1" for="designationInput">Designation / Position</label>
            <input type="text" class="form-control decl-input" id="designationInput" placeholder="e.g. Director / Owner / Manager" autocomplete="organization-title" aria-required="true" disabled>
            <small class="text-muted d-block mt-1">Required.</small>
          </div>

          <div class="mt-3">
            <label class="form-label fw-semibold mb-1" for="signatureName">Signature (type your full name)</label>
            <input type="text" class="form-control decl-input" id="signatureName"
                   placeholder="e.g. Your Full Name "
                   autocomplete="name" aria-required="true" disabled>
           
            <div class="decl-hint mt-2" id="declHint" role="alert" style="display:none;">
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
<div class="container eca-faq-wrap">
  <details class="card eca-faq-card">
    <summary aria-expanded="false">
      <span>Need help?</span>
      <span class="eca-disclose-arrow" aria-hidden="true"></span>
    </summary>

    <div class="faq">

      <button type="button" class="faq-btn" aria-expanded="false" aria-controls="faq-art-1">Why is the “Submit Application” button disabled?</button>
      <div class="faq-content" id="faq-art-1">
        The Submit button becomes active only after you read the Code of Conduct, tick the agreement box,
        type your full name as a signature, and add your designation.
      </div>

      <button type="button" class="faq-btn" aria-expanded="false" aria-controls="faq-art-2">How do I get “✅ Code of Conduct Signed”?</button>
      <div class="faq-content" id="faq-art-2">
        1) Click <strong>Read Code of Conduct & Sign</strong><br>
        2) Scroll the Code of Conduct to the <strong>bottom</strong><br>
        3) Tick <strong>I have read and agree</strong><br>
        4) Type your <strong>full name</strong> exactly as registered<br>
        5) Enter your <strong>Designation</strong> (e.g. Director)<br>
        6) Click <strong>Confirm & Continue</strong><br>
        ✅ The button changes to <strong>Code of Conduct Signed</strong>.
      </div>

      <button type="button" class="faq-btn" aria-expanded="false" aria-controls="faq-art-3">I can’t tick the checkbox / signature is disabled</button>
      <div class="faq-content" id="faq-art-3">
        You must <strong>scroll to the bottom</strong> of the Code of Conduct first. Then signing unlocks.
      </div>

      <button type="button" class="faq-btn" aria-expanded="false" aria-controls="faq-art-4">What file format should I upload?</button>
      <div class="faq-content" id="faq-art-4">
        All documents must be uploaded in <strong>PDF format only</strong> (Max 2MB each).
      </div>

      <button type="button" class="faq-btn" aria-expanded="false" aria-controls="faq-art-5">How long does approval take?</button>
      <div class="faq-content" id="faq-art-5">
        Processing time depends on document completeness. It may take <strong>2 working days</strong>.
      </div>

      <button type="button" class="faq-btn" aria-expanded="false" aria-controls="faq-art-6">Who do I contact for support?</button>
      <div class="faq-content" id="faq-art-6">
        Email <strong>support@eca.co.sz</strong> for technical or application assistance.
      </div>

    </div>
  </details>
</div>

<!-- JS -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

<script>
  // Spinner off
  window.addEventListener('load', function(){
    const spinner = document.getElementById('spinner');
    if(spinner) spinner.classList.remove('show');
  });

  (function(){
    const steps = Array.from(document.querySelectorAll('.eca-apply-step'));
    const dots = Array.from(document.querySelectorAll('.eca-apply-dots [data-go]'));
    const backBtn = document.getElementById('applyBack');
    const nextBtn = document.getElementById('applyNext');
    let current = 0;

    function showStep(index){
      current = Math.max(0, Math.min(steps.length - 1, index));
      steps.forEach((step, i) => step.classList.toggle('is-on', i === current));
      dots.forEach((dot, i) => {
        dot.classList.toggle('is-on', i === current);
        dot.classList.toggle('is-done', i < current);
      });
      if (backBtn) backBtn.disabled = current === 0;
      if (nextBtn) nextBtn.hidden = current === steps.length - 1;
    }

    function fieldsValid(stepIndex){
      const fields = steps[stepIndex].querySelectorAll('input, select, textarea');
      for (const field of fields) {
        if (field.type === 'hidden') continue;
        if (!field.checkValidity()) {
          showStep(stepIndex);
          field.reportValidity();
          return false;
        }
      }
      return true;
    }

    nextBtn?.addEventListener('click', function(){
      if (fieldsValid(current)) showStep(current + 1);
    });
    backBtn?.addEventListener('click', function(){
      showStep(current - 1);
    });
    dots.forEach(function(dot){
      dot.addEventListener('click', function(){
        const target = parseInt(dot.getAttribute('data-go'), 10);
        if (target > current) {
          for (let i = current; i < target; i++) {
            if (!fieldsValid(i)) return;
          }
        }
        showStep(target);
      });
    });
    document.getElementById('mainForm')?.addEventListener('submit', function(event){
      for (let i = 0; i < steps.length; i++) {
        if (!fieldsValid(i)) {
          event.preventDefault();
          return;
        }
      }
    });
    showStep(0);
  })();

  // Specialist must specify
  const select = document.getElementById("specialisationSelect");
  const otherBox = document.getElementById("otherBox");
  const otherInput = document.getElementById("otherInput");
  if(select && otherBox && otherInput){
    const toggleSpecify = function(){
      const needsSpecify = (this.value === "Specialist" || this.value === "Other");
      otherBox.style.display = needsSpecify ? "block" : "none";
      otherInput.required = needsSpecify;
      if(!needsSpecify){
        otherInput.value = "";
      }
    };
    select.addEventListener("change", toggleSpecify);
    toggleSpecify.call(select);
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

<?php require __DIR__ . '/includes/form-disclose.php'; ?>
<?php require __DIR__ . '/includes/site-footer.php'; ?>
</body>
</html>
