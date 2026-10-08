<?php
require_once __DIR__ . '/includes/public-page.php';
require_once __DIR__ . '/includes/education.php';

$id = (int) ($_GET['id'] ?? 0);
$fileParam = basename(str_replace(['\\', "\0"], '', trim((string) ($_GET['file'] ?? ''))));
$allowedFileExt = ['pdf', 'png', 'jpg', 'jpeg', 'webp', 'gif', 'mp4'];

$title = 'Document';
$description = 'View this education resource in your browser.';
$embedSrc = '';
$downloadHref = '';
$kind = 'file';
$external = false;

$conn = eca_education_db();
$ready = $conn && eca_education_ready($conn);

if ($id > 0) {
    if (!$ready) {
        http_response_code(503);
        exit('Unavailable');
    }
    $stmt = $conn->prepare('SELECT * FROM education_resources WHERE id = ? LIMIT 1');
    $stmt->execute([$id]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$row || ($row['status'] ?? '') !== 'PUBLISHED') {
        http_response_code(404);
        exit('Not found');
    }
    $title = trim((string) ($row['title'] ?? '')) ?: 'Document';
    $description = trim((string) ($row['description'] ?? '')) ?: $description;
    $stored = trim((string) ($row['file_path'] ?? ''));
    $url = trim((string) ($row['external_url'] ?? ''));
    if ($stored !== '') {
        $embedSrc = '/education-download.php?id=' . $id . '&inline=1';
        $downloadHref = '/education-download.php?id=' . $id;
        $kindPath = $stored;
    } elseif ($url !== '') {
        $downloadFile = eca_education_download_file_param($url);
        if ($downloadFile !== '') {
            $embedSrc = eca_education_inline_url($url);
            $downloadHref = $url;
            $kindPath = $downloadFile;
        } else {
            $embedSrc = $url;
            $downloadHref = $url;
            $kindPath = $url;
            $external = str_starts_with($url, 'http');
        }
    } else {
        http_response_code(404);
        exit('No file');
    }
} elseif ($fileParam !== '') {
    $ext = strtolower(pathinfo($fileParam, PATHINFO_EXTENSION));
    if (!in_array($ext, $allowedFileExt, true) || strlen($fileParam) > 200) {
        http_response_code(400);
        exit('Invalid file.');
    }
    $title = $fileParam;
    $embedSrc = '/download.php?file=' . rawurlencode($fileParam) . '&inline=1';
    $downloadHref = '/download.php?file=' . rawurlencode($fileParam);
    $kindPath = $fileParam;
    if ($ready) {
        $stmt = $conn->prepare('SELECT title, description FROM education_resources WHERE status = ? AND external_url LIKE ? LIMIT 1');
        $stmt->execute(['PUBLISHED', '%file=' . $fileParam . '%']);
        $named = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($named) {
            $title = trim((string) ($named['title'] ?? '')) ?: $title;
            if (!empty($named['description'])) {
                $description = (string) $named['description'];
            }
        }
    }
} else {
    http_response_code(400);
    exit('Missing document');
}

$pathForKind = strtolower((string) (parse_url($kindPath, PHP_URL_PATH) ?: $kindPath));
$ext = strtolower(pathinfo($pathForKind, PATHINFO_EXTENSION));
if (in_array($ext, ['png', 'jpg', 'jpeg', 'webp', 'gif'], true)) {
    $kind = 'image';
} elseif ($ext === 'mp4') {
    $kind = 'video';
} elseif ($ext === 'pdf') {
    $kind = 'pdf';
}

eca_public_page_start(
    $title,
    'Membership development',
    $title,
    $description,
    'Membership development / Resources / View',
    null,
    false,
    'education-resources.php'
);
?>
<div class="edu-wrap edu-viewer-page">
    <div class="edu-viewer-bar">
        <a class="edu-btn-ghost" href="/education-resources.php">Back to resources</a>
        <p class="edu-kicker">Viewing in browser</p>
        <a class="edu-btn" href="<?= eca_education_h($downloadHref) ?>"<?= $external ? ' rel="noopener noreferrer" target="_blank"' : ' download' ?>>Download</a>
    </div>
    <?php if ($kind === 'image'): ?>
        <div class="edu-viewer-stage">
            <img class="edu-viewer-image" src="<?= eca_education_h($embedSrc) ?>" alt="<?= eca_education_h($title) ?>">
        </div>
    <?php elseif ($kind === 'video'): ?>
        <div class="edu-viewer-stage">
            <video class="edu-viewer-video" src="<?= eca_education_h($embedSrc) ?>" controls playsinline></video>
        </div>
    <?php elseif ($kind === 'pdf'): ?>
        <div class="edu-viewer-stage edu-pdf-stage" data-pdf-src="<?= eca_education_h($embedSrc) ?>">
            <p class="edu-pdf-status">Loading preview…</p>
            <iframe class="edu-viewer-frame" hidden title="<?= eca_education_h($title) ?>" allow="fullscreen"></iframe>
        </div>
        <script src="/js/edu-pdf-viewer.js?v=20260921-1"></script>
    <?php else: ?>
        <div class="edu-viewer-stage">
            <p class="edu-pdf-status">This file cannot be previewed in the browser. Use Download to save a copy.</p>
        </div>
    <?php endif; ?>
</div>
<?php eca_public_page_end();