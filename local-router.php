<?php
header('X-Content-Type-Options: nosniff');
header('X-Frame-Options: SAMEORIGIN');
header('Referrer-Policy: strict-origin-when-cross-origin');
header('Permissions-Policy: camera=(), microphone=(), geolocation=(), payment=(self)');
header("Content-Security-Policy: default-src 'self' https: data:; object-src 'self' blob:; base-uri 'self'; frame-ancestors 'self'; form-action 'self'; script-src 'self' 'unsafe-inline' https:; style-src 'self' 'unsafe-inline' https:; img-src 'self' https: data: blob:; font-src 'self' https: data:; connect-src 'self' https:; frame-src 'self' https: blob:; worker-src 'self' blob: https:");

$eca = __DIR__ . DIRECTORY_SEPARATOR . 'eca-pages';
$v1 = __DIR__ . DIRECTORY_SEPARATOR . 'v1';
$uri = urldecode(parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?? '/');
if ($uri === '' || $uri === '/') {
    $uri = '/index.html';
}

if (str_contains($uri, "\0") || preg_match('#(?:^|/)\.\.(?:/|$)#', $uri)) {
    http_response_code(400);
    echo 'Invalid request path.';
    return true;
}

$mimes = [
    'html' => 'text/html; charset=UTF-8',
    'css' => 'text/css; charset=UTF-8',
    'js' => 'application/javascript; charset=UTF-8',
    'mjs' => 'application/javascript; charset=UTF-8',
    'png' => 'image/png',
    'jpg' => 'image/jpeg',
    'jpeg' => 'image/jpeg',
    'gif' => 'image/gif',
    'webp' => 'image/webp',
    'svg' => 'image/svg+xml',
    'ico' => 'image/x-icon',
    'pdf' => 'application/pdf',
    'woff' => 'font/woff',
    'woff2' => 'font/woff2',
    'ttf' => 'font/ttf',
    'eot' => 'application/vnd.ms-fontobject',
    'mp4' => 'video/mp4',
    'json' => 'application/json',
    'map' => 'application/json',
    'txt' => 'text/plain; charset=UTF-8',
];

function eca_serve_file(string $file, array $mimes): bool
{
    if (!is_file($file)) {
        return false;
    }
    $ext = strtolower(pathinfo($file, PATHINFO_EXTENSION));
    header('Content-Type: ' . ($mimes[$ext] ?? 'application/octet-stream'));
    header('Content-Length: ' . (string) filesize($file));
    readfile($file);
    return true;
}

function eca_run_php(string $php, string $scriptName): bool
{
    if (!is_file($php)) {
        return false;
    }
    $_SERVER['SCRIPT_FILENAME'] = $php;
    $_SERVER['SCRIPT_NAME'] = $scriptName;
    chdir(dirname($php));
    require_once __DIR__ . DIRECTORY_SEPARATOR . 'v1' . DIRECTORY_SEPARATOR . 'includes' . DIRECTORY_SEPARATOR . 'session.php';
    require $php;
    return true;
}

function eca_serve_v1_path(string $v1, string $relative, string $scriptName, array $mimes): bool
{
    $path = $v1 . str_replace('/', DIRECTORY_SEPARATOR, $relative);
    if (is_dir($path)) {
        $indexPhp = rtrim($path, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . 'index.php';
        if (is_file($indexPhp)) {
            return eca_run_php($indexPhp, $scriptName);
        }
        $indexHtml = rtrim($path, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . 'index.html';
        if (is_file($indexHtml)) {
            return eca_serve_file($indexHtml, $mimes);
        }
        return false;
    }
    if (!is_file($path)) {
        return false;
    }
    if (strtolower(pathinfo($path, PATHINFO_EXTENSION)) === 'php') {
        return eca_run_php($path, $scriptName);
    }
    return eca_serve_file($path, $mimes);
}

if (in_array($uri, ['/login', '/login.php', '/login.html'], true)) {
    return eca_run_php($v1 . DIRECTORY_SEPARATOR . 'login.php', '/login.php');
}

if ($uri === '/receive_portal_export.php') {
    http_response_code(404);
    require $v1 . DIRECTORY_SEPARATOR . 'errors' . DIRECTORY_SEPARATOR . '404.php';
    return true;
}

if (preg_match('#^/(uploads/documents|_private)(/|$)#', $uri)) {
    http_response_code(403);
    require $v1 . DIRECTORY_SEPARATOR . 'errors' . DIRECTORY_SEPARATOR . '403.php';
    return true;
}

if (preg_match('#^/cpd/uploads/.*\.(?:php\d*|phtml|phar|cgi|pl|py|sh)$#i', $uri)) {
    http_response_code(403);
    require $v1 . DIRECTORY_SEPARATOR . 'errors' . DIRECTORY_SEPARATOR . '403.php';
    return true;
}

$htmlToPhp = [
    '/index.html' => 'index.php',
    '/directory.html' => 'directory.php',
    '/balingani-directory.html' => 'balingani-directory.php',
    '/news.html' => 'news.php',
    '/resources.html' => 'resources.php',
    '/contact.html' => 'contact.php',
    '/about.html' => 'about.php',
    '/about-bod.html' => 'about-bod.php',
    '/about-by-laws.html' => 'about-by-laws.php',
    '/code-of-conduct.html' => 'code-of-conduct.php',
    '/privacy.html' => 'privacy.php',
    '/faq.html' => 'faq.php',
    '/gallery.html' => 'gallery.php',
    '/checklist.html' => 'checklist.php',
    '/application.html' => 'application.php',
    '/renewal.html' => 'renewal.php',
    '/download.html' => 'download.php',
    '/membership-registration.html' => 'membership-registration.php',
];

$phpPreferred = [
    'index',
    'directory',
    'directory-suggest',
    'membership-registration',
    'balingani-directory',
    'news',
    'news-details',
    'resources',
    'contact',
    'about',
    'about-bod',
    'about-by-laws',
    'code-of-conduct',
    'privacy',
    'faq',
    'gallery',
    'checklist',
    'application',
    'renewal',
    'lookup_member',
    'download',
    'like',
    'likes',
    'search-news',
    'contact_process',
    'submit_membership',
    'save_renewal',
    'save_artisarn',
    'apply_artisan',
    'success',
    'thankyou',
    'contractor',
    'verify',
    'track',
    'document-download',
    'certificate-download',
    'directory-profile',
    'tenders',
    'tender',
    'events',
    'training',
    'education',
    'education-training',
    'education-course',
    'education-knowledge',
    'education-article',
    'education-learner',
    'education-development',
    'education-programme',
    'education-policy',
    'education-resources',
    'education-download',
    'education-view',
    'sitemap',
    'membership-registration',
    'advocacy',
    'advocacy-updates',
    'about-mission',
    'about-structure',
    'about-history',
    'digital-intelligence',
    'professionalization',
    'wellness-inclusivity',
    'technical-support',
];

if (isset($htmlToPhp[$uri])) {
    return eca_run_php($v1 . DIRECTORY_SEPARATOR . $htmlToPhp[$uri], $uri);
}

if (preg_match('#^/([^/]+)\.php$#', $uri, $match) && in_array($match[1], $phpPreferred, true)) {
    return eca_run_php($v1 . DIRECTORY_SEPARATOR . $match[1] . '.php', $uri);
}

if (preg_match('#^/education-article-([A-Za-z0-9-]+)\.html$#', $uri, $match)) {
    $_GET['slug'] = strtolower($match[1]);
    return eca_run_php($v1 . DIRECTORY_SEPARATOR . 'education-article.php', $uri);
}

if (preg_match('#^/cpd(/.*)?$#', $uri, $match)) {
    $rest = $match[1] ?? '';
    if ($rest === '' || $rest === '/') {
        $rest = '/index.php';
    }
    $jquery = DIRECTORY_SEPARATOR . 'code.jquery.com' . DIRECTORY_SEPARATOR . 'cpd' . str_replace('/', DIRECTORY_SEPARATOR, $rest);
    $direct = DIRECTORY_SEPARATOR . 'cpd' . str_replace('/', DIRECTORY_SEPARATOR, $rest);
    if (eca_serve_v1_path($v1, str_replace('\\', '/', $jquery), $uri, $mimes)) {
        return true;
    }
    if (eca_serve_v1_path($v1, str_replace('\\', '/', $direct), $uri, $mimes)) {
        return true;
    }
}

if (preg_match('#^/wellness(/.*)?$#', $uri, $match)) {
    $rest = $match[1] ?? '';
    if ($rest === '' || $rest === '/') {
        $rest = '/index.php';
    }
    if (eca_serve_v1_path($v1, '/wellness' . $rest, $uri, $mimes)) {
        return true;
    }
}

if (preg_match('#^/registration(/.*)?$#', $uri, $match)) {
    $rest = $match[1] ?? '';
    if ($rest === '' || $rest === '/') {
        $rest = '/index.php';
    }
    if (eca_serve_v1_path($v1, '/registration' . $rest, $uri, $mimes)) {
        return true;
    }
}

if (preg_match('#^/documents(/.*)?$#', $uri, $match)) {
    $rest = $match[1] ?? '';
    if ($rest === '' || $rest === '/' || $rest === '/index.html') {
        $rest = '/index.php';
    }
    if (eca_serve_v1_path($v1, '/documents' . $rest, $uri, $mimes)) {
        return true;
    }
}

if (strncmp($uri, '/admin', 6) === 0) {
    $path = $v1 . str_replace('/', DIRECTORY_SEPARATOR, $uri);
    if ($uri === '/admin' || $uri === '/admin/') {
        $path = $v1 . DIRECTORY_SEPARATOR . 'admin' . DIRECTORY_SEPARATOR . 'login.php';
    }
    if (is_dir($path)) {
        $path = rtrim($path, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . 'index.php';
    }
    if (is_file($path) && strtolower(pathinfo($path, PATHINFO_EXTENSION)) === 'php') {
        return eca_run_php($path, $uri);
    }
}

if (preg_match('#^/([^/]+)\.php$#', $uri, $match) && strncmp($uri, '/client', 7) !== 0) {
    $php = $v1 . DIRECTORY_SEPARATOR . $match[1] . '.php';
    $html = $eca . DIRECTORY_SEPARATOR . $match[1] . '.html';
    if (is_file($html)) {
        header('Location: /' . $match[1] . '.html', true, 302);
        return true;
    }
    if (eca_run_php($php, $uri)) {
        return true;
    }
}

if (strpos($uri, '..') === false && preg_match('#\.php$#i', $uri)) {
    $nestedPhp = $v1 . str_replace('/', DIRECTORY_SEPARATOR, $uri);
    if (is_file($nestedPhp)) {
        return eca_run_php($nestedPhp, $uri);
    }
}

if (strncmp($uri, '/client', 7) === 0) {
    $path = $v1 . str_replace('/', DIRECTORY_SEPARATOR, $uri);
    if (is_dir($path)) {
        $path = rtrim($path, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . 'index.php';
    }
    if (is_file($path)) {
        if (strtolower(pathinfo($path, PATHINFO_EXTENSION)) === 'php') {
            return eca_run_php($path, $uri);
        }
        if (eca_serve_file($path, $mimes)) {
            return true;
        }
    }
}

$v1File = $v1 . str_replace('/', DIRECTORY_SEPARATOR, $uri);
if (eca_serve_file($v1File, $mimes)) {
    return true;
}

$file = $eca . str_replace('/', DIRECTORY_SEPARATOR, $uri);
if (eca_serve_file($file, $mimes)) {
    return true;
}

http_response_code(404);
require $v1 . DIRECTORY_SEPARATOR . 'errors' . DIRECTORY_SEPARATOR . '404.php';
return true;
