<?php
require_once __DIR__ . '/session.php';

function eca_public_url(string $path = '/'): string
{
    require_once __DIR__ . '/env.php';
    if (function_exists('eca_is_local_request') && eca_is_local_request()) {
        $host = (string) ($_SERVER['HTTP_HOST'] ?? '127.0.0.1:8765');
        if ($host === '') {
            $host = '127.0.0.1:8765';
        }
        $https = !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off';
        $scheme = $https ? 'https' : 'http';
        return $scheme . '://' . $host . '/' . ltrim($path, '/');
    }
    $base = rtrim(eca_env('ECA_PUBLIC_URL', 'https://eca.co.sz'), '/');
    return $base . '/' . ltrim($path, '/');
}

function eca_public_head(string $title, string $description, string $path): void
{
    $fullTitle = str_contains($title, 'Eswatini Contractors Association')
        ? $title
        : $title . ' | Eswatini Contractors Association';
    ?>
    <title><?= htmlspecialchars($fullTitle, ENT_QUOTES, 'UTF-8') ?></title>
    <?php
    eca_public_meta($title, $description, $path);
}

function eca_public_meta(
    string $title,
    string $description,
    string $path,
    string $type = 'website',
    string $imagePath = '/img/ecalogo.png'
): void {
    $canonical = eca_public_url($path);
    $image = eca_public_url($imagePath);
    $fullTitle = str_contains($title, 'Eswatini Contractors Association')
        ? $title
        : $title . ' | Eswatini Contractors Association';
    ?>
    <meta name="description" content="<?= htmlspecialchars($description, ENT_QUOTES, 'UTF-8') ?>">
    <meta name="robots" content="index, follow">
    <link rel="canonical" href="<?= htmlspecialchars($canonical, ENT_QUOTES, 'UTF-8') ?>">
    <meta property="og:type" content="<?= htmlspecialchars($type, ENT_QUOTES, 'UTF-8') ?>">
    <meta property="og:title" content="<?= htmlspecialchars($fullTitle, ENT_QUOTES, 'UTF-8') ?>">
    <meta property="og:description" content="<?= htmlspecialchars($description, ENT_QUOTES, 'UTF-8') ?>">
    <meta property="og:url" content="<?= htmlspecialchars($canonical, ENT_QUOTES, 'UTF-8') ?>">
    <meta property="og:site_name" content="Eswatini Contractors Association">
    <meta property="og:image" content="<?= htmlspecialchars($image, ENT_QUOTES, 'UTF-8') ?>">
    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:title" content="<?= htmlspecialchars($fullTitle, ENT_QUOTES, 'UTF-8') ?>">
    <meta name="twitter:description" content="<?= htmlspecialchars($description, ENT_QUOTES, 'UTF-8') ?>">
    <meta name="twitter:image" content="<?= htmlspecialchars($image, ENT_QUOTES, 'UTF-8') ?>">
    <?php
}

function eca_json_ld(array $data): void
{
    echo '<script type="application/ld+json">'
        . json_encode($data, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_HEX_TAG)
        . '</script>';
}
