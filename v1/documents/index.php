<?php
require_once dirname(__DIR__) . '/includes/session.php';
$currentPage = 'documents.php';

$catalog = [
    ['file' => 'Eca-Constitution.pdf', 'title' => 'ECA Constitution', 'group' => 'Governance'],
    ['file' => 'BYLAWS.pdf', 'title' => 'Bylaws', 'group' => 'Governance'],
    ['file' => '2025-2026-ECAMembershipApplicationForm.pdf', 'title' => 'Membership application form', 'group' => 'Membership'],
    ['file' => '2025 2026 ECA Membership Application Form.pdf', 'title' => 'Membership application form (print)', 'group' => 'Membership'],
    ['file' => '2025 2026 MEMBERSHIP RENEWAL FORM.pdf', 'title' => 'Membership renewal form', 'group' => 'Membership'],
    ['file' => 'Training-Report-2025.pdf', 'title' => 'Training report 2025', 'group' => 'Training'],
    ['file' => 'ECA-WHITE-PAPER-2025.pdf', 'title' => 'Inclusive procurement white paper', 'group' => 'Training'],
    ['file' => 'ECA 20252026 TRAINING COMPREHENSIVE MEMBERSHIP REPORTS.pdf', 'title' => 'Training and membership report', 'group' => 'Training'],
    ['file' => 'CATALOGUE2026-2027.pdf', 'title' => 'Training catalogue 2026–2027', 'group' => 'Training'],
];

function eca_document_exists(string $file): bool
{
    $safe = str_replace(['../', '..\\', "\0"], '', $file);
    $dirs = [
        __DIR__ . '/downloads/',
        __DIR__ . '/documents/',
        __DIR__ . '/img/',
        dirname(__DIR__) . '/eca-pages/img/',
    ];
    foreach ($dirs as $dir) {
        if (is_file($dir . $safe)) {
            return true;
        }
    }
    return false;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <?php
    require_once dirname(__DIR__) . '/includes/public-seo.php';
    eca_public_head(
        'Documents and Downloads',
        'Download ECA membership forms, the association constitution, bylaws, training reports, catalogues and industry publications.',
        '/documents/'
    );
    ?>
    <base href="/">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="/img/favicon.ico" rel="icon">
    <link href="https://fonts.googleapis.com/css2?family=Open+Sans:wght@400;500;600;700&family=Roboto:wght@500;700;900&display=swap" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.10.0/css/all.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
    <link href="/css/bootstrap.min.css" rel="stylesheet">
    <link href="/css/style.css" rel="stylesheet">
    <link href="/css/theme.css?v=20260919-4" rel="stylesheet">
    <?php require_once dirname(__DIR__) . '/includes/responsive-assets.php'; eca_responsive_assets(); ?>
</head>
<body>
<?php
$navExt = 'php';
require dirname(__DIR__) . '/includes/site-nav.php';
$pageKicker = 'Library';
$pageTitle = 'Documents';
$pageIntro = 'Download ECA forms, constitution, bylaws and training reports.';
$pageCrumb = 'Documents';
require dirname(__DIR__) . '/includes/page-hero.php';
?>

<div class="container-xxl py-4">
    <div class="container">
        <div class="row g-4">
            <?php foreach ($catalog as $doc):
                $available = eca_document_exists($doc['file']);
            ?>
                <div class="col-md-6 col-lg-4">
                    <div class="card border-0 shadow-sm h-100">
                        <div class="card-body p-4">
                            <p class="eca-kicker"><?= htmlspecialchars($doc['group']) ?></p>
                            <h3><?= htmlspecialchars($doc['title']) ?></h3>
                            <?php if ($available): ?>
                                <a class="btn btn-primary" href="/download.php?file=<?= urlencode($doc['file']) ?>"><i class="bi bi-download me-1"></i> Download</a>
                            <?php else: ?>
                                <p class="mb-0 text-muted">Listed on the live site. The file is not available in this local copy yet.</p>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</div>

<?php require dirname(__DIR__) . '/includes/site-footer.php'; ?>
</body>
</html>
