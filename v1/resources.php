<?php
require_once __DIR__ . '/includes/session.php';
$currentPage = basename($_SERVER['PHP_SELF']);

require_once 'config.php';
require_once __DIR__ . '/includes/portal-db.php';
$db = new Database();
$local = $db->getConnection(false);
$conn = eca_portal_pdo(false) ?: $local;

$resourceSearch = trim((string) ($_GET['search'] ?? $_GET['q'] ?? ''));
$dbResources = [];
$downloadCounts = [];
$likeCounts = [];
if ($conn) {
  try {
    $sql = "SELECT title, description, file_path, category FROM resources WHERE (status IS NULL OR status IN ('Published','published','Active'))";
    $params = [];
    if ($resourceSearch !== '') {
      $sql .= ' AND (title LIKE ? OR description LIKE ? OR category LIKE ?)';
      $like = '%' . $resourceSearch . '%';
      $params = [$like, $like, $like];
    }
    $sql .= ' ORDER BY id DESC LIMIT 50';
    $stmt = $conn->prepare($sql);
    $stmt->execute($params);
    foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
      $dbResources[] = [
        'file' => (string) ($row['file_path'] ?? ''),
        'title' => (string) ($row['title'] ?? 'Resource'),
        'desc' => (string) ($row['description'] ?? ''),
        'img' => 'img/book.PNG',
      ];
    }
  } catch (Throwable $e) {
    $dbResources = [];
  }
}

if ($conn) {
  $stmt = $conn->query("SELECT file_name, count FROM downloads ORDER BY file_name LIMIT 200");
  $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
  foreach ($rows as $row) {
    $downloadCounts[$row['file_name']] = (int)$row['count'];
  }

  $stmt = $conn->query("SELECT file_name, count FROM likes ORDER BY file_name LIMIT 200");
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
  <?php
  require_once __DIR__ . '/includes/public-seo.php';
  eca_public_head(
      'Contractor Resources',
      'Access Eswatini Contractors Association forms, guidance, reports and downloadable resources for contractors and members.',
      '/resources.php'
  );
  ?>
  <meta content="width=device-width, initial-scale=1.0" name="viewport">

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
    <link href="css/theme.css?v=20260918-8" rel="stylesheet">

      <?php require_once __DIR__ . '/includes/responsive-assets.php'; eca_responsive_assets(); ?>
<style>
    .header-custom{ background:#192754; color:#fff; }
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
  </style>
</head>

<body>
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

<?php require __DIR__ . '/includes/site-footer.php'; ?>

<!-- Bootstrap -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

<script>
  // Spinner off
  window.addEventListener('load', function(){
    const spinner = document.getElementById('spinner');
    if(spinner) spinner.classList.remove('show');
  });

  // Likes AJAX (CSRF-protected)
  document.addEventListener("click", function(e){
    const btn = e.target.closest(".like-btn");
    if(!btn) return;

    const file = btn.getAttribute("data-file");
    const countSpan = btn.querySelector(".like-count");
    const csrf = <?= json_encode(eca_public_csrf_token(), JSON_UNESCAPED_UNICODE) ?>;

    fetch("like.php", {
      method: "POST",
      headers: {"Content-Type":"application/x-www-form-urlencoded"},
      credentials: "same-origin",
      body: "file=" + encodeURIComponent(file) + "&csrf_token=" + encodeURIComponent(csrf)
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
