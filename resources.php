<?php
session_start();
$currentPage = basename($_SERVER['PHP_SELF']);

require_once 'config.php';
$db = new Database();
$conn = $db->getConnection(false);

$downloadCounts = [];
$likeCounts = [];

if ($conn) {
  $stmt = $conn->query("SELECT file_name, count FROM downloads");
  $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
  foreach ($rows as $row) {
    $downloadCounts[$row['file_name']] = (int)$row['count'];
  }

  $stmt = $conn->query("SELECT file_name, count FROM likes");
  $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
  foreach ($rows as $row) {
    $likeCounts[$row['file_name']] = (int)$row['count'];
  }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <title>E.C.A | Resources</title>
  <meta content="width=device-width, initial-scale=1.0" name="viewport">
  <meta content="Eswatini Contractors’ Official Website" name="description">

  <link href="img/favicon.ico" rel="icon">

  <!-- Google Web Fonts -->
  <link rel="preconnect" href="https://fonts.googleapis.com/">
  <link rel="preconnect" href="https://fonts.gstatic.com/" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Open+Sans:wght@400;500&family=Roboto:wght@500;700;900&display=swap" rel="stylesheet">

  <!-- Icon Font Stylesheet (FIXED: add https://) -->
  <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.10.0/css/all.min.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">

  <!-- Libraries Stylesheet -->
  <link href="lib/animate/animate.min.css" rel="stylesheet">
  <link href="lib/owlcarousel/assets/owl.carousel.min.css" rel="stylesheet">
  <link href="lib/tempusdominus/css/tempusdominus-bootstrap-4.min.css" rel="stylesheet" />

  <!-- Bootstrap -->
  <link href="css/bootstrap.min.css" rel="stylesheet">

  <!-- Template -->
  <link href="css/style.css" rel="stylesheet">
    <link href="css/theme.css" rel="stylesheet">

  <style>
    .header-custom{ background:#000066; color:#fff; }
    .header-custom small{ color:#fff; }

    .faq-btn-ui{
      display:inline-block;
      background:#ffffff;
      color:#b30000;
      padding:10px 18px;
      border-radius:8px;
      font-weight:600;
      text-decoration:none;
      border:2px solid #b30000;
      transition:0.3s;
      box-shadow:0 4px 12px rgba(0,0,0,0.08);
      margin-left:6px;
    }
    .faq-btn-ui:hover{ background:#b30000; color:#fff; transform:translateY(-2px); }

    .resource-card{
      border:0;
      border-radius:14px;
      overflow:hidden;
      box-shadow:0 10px 30px rgba(0,0,0,.10);
      transition:transform .2s ease, box-shadow .2s ease;
      height:100%;
    }
    .resource-card:hover{
      transform: translateY(-6px);
      box-shadow:0 18px 45px rgba(0,0,0,.18);
    }
    .resource-thumb{
      width:100%;
      height:160px;
      object-fit:cover;
      border-bottom:1px solid rgba(0,0,0,.06);
    }
    .mini-muted{ font-size:.85rem; color:#6c757d; }

    .like-btn:hover{ transform: scale(1.05); }
    .like-btn{ transition: transform .15s ease; }

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
$pageKicker = 'Documents';
$pageTitle = 'ECA resources';
$pageIntro = 'Download important ECA forms, guides and association documents.';
$pageCrumb = 'Resources';
require __DIR__ . '/includes/page-hero.php';
?>

<!-- Resources -->
<div class="container-xxl py-5">
  <div class="container">
    <div class="eca-page-intro">
      <p class="eca-kicker">Downloads</p>
      <h2>ECA documents</h2>
      <p>Forms, guides and association documents for members and applicants.</p>
    </div>

    <div class="row g-4">

      <?php
      // List resources in one place (easy to maintain)
      
    //   [
     ///     "file" => "2025-2026-ECAMembershipApplicationForm.pdf",
      //    "title" => "Membership Form",
      //    "desc"  => "Membership registration form.",
       //   "img"   => "img/renewal.png"
      //  ],
      $resources = [
        [
          "file" => "Eca-Constitution.pdf",
          "title" => "ECA Constitution",
          "desc"  => "The Eswatini Contractors Association official constitution.",
          "img"   => "img/book.PNG"
        ],
        [
          "file" => "BYLAWS.pdf",
          "title" => "BYLAWS",
          "desc"  => "A Wing of the Eswatini Contractors Association (ECA).",
          "img"   => "img/BYLAWS.png"
        ],
        [
          "file" => "Training-Report-2025.pdf",
          "title" => "Training Report",
          "desc"  => "Contractor Development Training on Fundamentals of Tendering: Level 2",
          "img"   => "img/report.png"
        ],
        [
          "file" => "ECA-WHITE-PAPER-2025.pdf",
          "title" => "Unlocking Inclusive Procurement",
          "desc"  => "Unlocking Inclusive Procurement: Reforming Eswatini’s
Framework for Donor-Funded Infrastructure",
          "img"   => "img/Procurement.png"
        ],
        
            [
          "file" => "ECA 20252026 TRAINING COMPREHENSIVE MEMBERSHIP REPORTS.pdf",
          "title" => "TRAINING COMPREHENSIVE MEMBERSHIP REPORT",
          "desc"  => "",
          "img"   => "img/ECA20252026.png"
        ],
        
        [
          "file" => "CATALOGUE2026-2027.pdf",
          "title" => "ECA 2026 2027 TRAINING CATALOGUE",
          "desc"  => "",
          "img"   => "img/catalogue.png"
        ],
       
      ];

      foreach($resources as $r):
        $file = $r["file"];
        $downloads = $downloadCounts[$file] ?? 0;
        $likes = $likeCounts[$file] ?? 0;
      ?>
      <div class="col-md-6 col-lg-3">
        <div class="card resource-card text-center">
          <img src="<?= htmlspecialchars($r["img"]) ?>" class="resource-thumb" alt="Resource">
          <div class="card-body">
            <h5 class="card-title"><?= htmlspecialchars($r["title"]) ?></h5>
            <p class="card-text small"><?= htmlspecialchars($r["desc"]) ?></p>

            <a href="download.php?file=<?= urlencode($file) ?>" class="btn btn-primary w-100 mb-2">
              <i class="bi bi-download me-1"></i> Download
            </a>

            <div class="mini-muted mb-2">
              <i class="bi bi-bar-chart me-1"></i>
              <span class="download-count" data-file="<?= htmlspecialchars($file) ?>"><?= (int)$downloads ?></span> downloads
            </div>

            <div class="d-flex justify-content-center align-items-center gap-2 flex-wrap">
              <button class="btn btn-outline-primary btn-sm like-btn" data-file="<?= htmlspecialchars($file) ?>">
                <i class="fa fa-thumbs-up me-1"></i>
                Like (<span class="like-count"><?= (int)$likes ?></span>)
              </button>

              <a href="https://facebook.com" target="_blank" class="btn btn-outline-secondary btn-sm">
                <i class="fab fa-facebook-f"></i>
              </a>
              <a href="https://twitter.com" target="_blank" class="btn btn-outline-secondary btn-sm">
                <i class="fab fa-twitter"></i>
              </a>
              <a href="https://linkedin.com" target="_blank" class="btn btn-outline-secondary btn-sm">
                <i class="fab fa-linkedin-in"></i>
              </a>
            </div>

          </div>
        </div>
      </div>
      <?php endforeach; ?>

    </div>
  </div>
</div>

<!-- Map -->
<iframe
  src="https://www.google.com/maps/embed?pb=!1m16!1m12!1m3!1d3575.9398627152696!2d31.139841774754323!3d-26.328446731859643!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!2m1!1sCooper%20Center%20eswatini%20contractors%20Association!5e0!3m2!1sen!2s!4v1756185111680!5m2!1sen!2s"
  width="100%" height="450" style="border:0;" allowfullscreen="" loading="lazy"
  referrerpolicy="no-referrer-when-downgrade"></iframe>

<?php require __DIR__ . '/includes/site-footer.php'; ?>

<!-- WhatsApp -->
<a href="https://wa.me/26876702898" target="_blank" class="whatsapp-float">
  <i class="bi bi-whatsapp"></i>
</a>

<!-- Bootstrap -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

<script>
  // Spinner off
  window.addEventListener('load', function(){
    const spinner = document.getElementById('spinner');
    if(spinner) spinner.classList.remove('show');
  });

  // Likes AJAX
  document.addEventListener("click", function(e){
    const btn = e.target.closest(".like-btn");
    if(!btn) return;

    const file = btn.getAttribute("data-file");
    const countSpan = btn.querySelector(".like-count");

    fetch("like.php", {
      method: "POST",
      headers: {"Content-Type":"application/x-www-form-urlencoded"},
      body: "file=" + encodeURIComponent(file)
    })
    .then(res => res.text())
    .then(count => {
      countSpan.textContent = count;
    })
    .catch(()=>{});
  });
</script>

</body>
</html>
