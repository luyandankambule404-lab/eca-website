<?php
require_once __DIR__ . '/includes/session.php';
$currentPage = 'about-by-laws.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <?php
    require_once __DIR__ . '/includes/public-seo.php';
    eca_public_head(
        'Constitution and Bylaws',
        'Read and download the constitution and bylaws governing the Eswatini Contractors Association and its Balingani Women in Construction wing.',
        '/about-by-laws.php'
    );
    ?>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="img/favicon.ico" rel="icon">
    <link href="https://fonts.googleapis.com/css2?family=Open+Sans:wght@400;500;600;700&family=Roboto:wght@500;700;900&display=swap" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.10.0/css/all.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
    <link href="css/bootstrap.min.css" rel="stylesheet">
    <link href="css/style.css" rel="stylesheet">
    <link href="css/theme.css?v=20260918-8" rel="stylesheet">
    <?php require_once __DIR__ . '/includes/responsive-assets.php'; eca_responsive_assets(); ?>
</head>
<body>
<?php require __DIR__ . '/includes/site-nav.php'; ?>
<?php
$pageKicker = 'About ECA';
$pageTitle = 'Association bylaws';
$pageIntro = 'Read the rules that govern ECA membership, conduct and the Balingani women in construction wing.';
$pageCrumb = 'Bylaws';
require __DIR__ . '/includes/page-hero.php';
?>

<div class="container-xxl py-4">
    <div class="container">
        <div class="eca-page-intro">
            <p class="eca-kicker">Governance</p>
            <h2>ECA constitution and bylaws</h2>
            <p>These documents set out how the association is run, member obligations, and the Balingani wing. Download the local copies below.</p>
        </div>
        <div class="row g-4">
            <div class="col-md-6">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-body p-4">
                        <h3>ECA Constitution</h3>
                        <p>The official constitution of the Eswatini Contractors Association.</p>
                        <a class="btn btn-primary" href="download.php?file=Eca-Constitution.pdf"><i class="bi bi-download me-1"></i> Download constitution</a>
                    </div>
                </div>
            </div>
            <div class="col-md-6">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-body p-4">
                        <h3>Bylaws</h3>
                        <p>Bylaws for the association, including the Balingani Women in Construction wing.</p>
                        <a class="btn btn-primary" href="download.php?file=BYLAWS.pdf"><i class="bi bi-download me-1"></i> Download bylaws</a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require __DIR__ . '/includes/site-footer.php'; ?>
</body>
</html>
