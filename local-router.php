<?php
$eca = __DIR__ . DIRECTORY_SEPARATOR . 'eca-pages';
$v1 = __DIR__ . DIRECTORY_SEPARATOR . 'v1';
$uri = urldecode(parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?? '/');
if ($uri === '' || $uri === '/') {
    $uri = '/index.html';
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
    header('Location: /', true, 302);
    return true;
}

if ($uri === '/receive_portal_export.php') {
    http_response_code(404);
    require $v1 . DIRECTORY_SEPARATOR . 'errors' . DIRECTORY_SEPARATOR . '404.php';
    return true;
}

$htmlToPhp = [
    '/directory.html' => 'directory.php',
    '/balingani-directory.html' => 'balingani-directory.php',
    '/news.html' => 'news.php',
    '/resources.html' => 'resources.php',
    '/contact.html' => 'contact.php',
    '/about-by-laws.html' => 'about-by-laws.php',
    '/download.html' => 'download.php',
];

$phpPreferred = [
    'directory',
    'balingani-directory',
    'news',
    'news-details',
    'resources',
    'contact',
    'about-by-laws',
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
];

if (isset($htmlToPhp[$uri])) {
    return eca_run_php($v1 . DIRECTORY_SEPARATOR . $htmlToPhp[$uri], $uri);
}

if (preg_match('#^/([^/]+)\.php$#', $uri, $match) && in_array($match[1], $phpPreferred, true)) {
    return eca_run_php($v1 . DIRECTORY_SEPARATOR . $match[1] . '.php', $uri);
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

$file = $eca . str_replace('/', DIRECTORY_SEPARATOR, $uri);
if (eca_serve_file($file, $mimes)) {
    return true;
}

$v1File = $v1 . str_replace('/', DIRECTORY_SEPARATOR, $uri);
if (eca_serve_file($v1File, $mimes)) {
    return true;
}

http_response_code(404);
require $v1 . DIRECTORY_SEPARATOR . 'errors' . DIRECTORY_SEPARATOR . '404.php';
return true;
