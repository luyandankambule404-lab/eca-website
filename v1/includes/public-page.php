<?php
require_once __DIR__ . '/session.php';
require_once __DIR__ . '/public-seo.php';

function eca_public_page_start(string $title, string $kicker, string $heading, string $intro, string $crumb, ?array $titleLines = null, bool $showHero = true, ?string $navPage = null): void
{
    $currentPage = $navPage ?: basename($_SERVER['SCRIPT_NAME'] ?? 'index.php');
    $navExt = 'php';
    ?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title><?= htmlspecialchars($title, ENT_QUOTES, 'UTF-8') ?> | Eswatini Contractors Association</title>
    <base href="/">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <?php
    $canonicalPath = (string) parse_url($_SERVER['SCRIPT_NAME'] ?? '/', PHP_URL_PATH);
    eca_public_meta($title, $intro, $canonicalPath !== '' ? $canonicalPath : '/');
    eca_json_ld([
        '@context' => 'https://schema.org',
        '@type' => 'WebPage',
        'name' => $title,
        'description' => $intro,
        'url' => eca_public_url($canonicalPath !== '' ? $canonicalPath : '/'),
        'isPartOf' => [
            '@type' => 'WebSite',
            'name' => 'Eswatini Contractors Association',
            'url' => eca_public_url('/'),
        ],
    ]);
    ?>
    <link href="/img/favicon.ico" rel="icon">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.10.0/css/all.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
    <link href="/css/bootstrap.min.css" rel="stylesheet">
    <link href="/css/style.css" rel="stylesheet">
    <link href="/css/theme.css?v=20261008-dirlink" rel="stylesheet">
    <link href="/css/education.css?v=20261007-know2" rel="stylesheet">
    <?php
    $wellnessPath = strtolower((string) (parse_url($_SERVER['REQUEST_URI'] ?? '', PHP_URL_PATH) ?: ''));
    if (str_starts_with($wellnessPath, '/wellness')) {
        echo '<link href="/css/wellness-hub.css?v=20260923-admin" rel="stylesheet">' . "\n    ";
    }
    ?>
    <?php require_once __DIR__ . '/responsive-assets.php'; eca_responsive_assets(); ?>
</head>
<body>
<?php
    require __DIR__ . '/site-nav.php';
    echo '<main id="main-content" tabindex="-1">';
    if ($showHero) {
        $pageKicker = $kicker;
        $pageTitle = $heading;
        $pageTitleLines = $titleLines;
        $pageIntro = $intro;
        $pageCrumb = $crumb;
        $pageHeroInsideMain = true;
        require __DIR__ . '/page-hero.php';
    }
}

function eca_public_page_end(): void
{
    echo '</main>';
    require __DIR__ . '/site-footer.php';
    echo "</body></html>";
}
