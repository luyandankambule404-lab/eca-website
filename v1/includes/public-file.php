<?php

function eca_public_file_mime(string $filename): string
{
    $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
    $map = [
        'pdf' => 'application/pdf',
        'png' => 'image/png',
        'jpg' => 'image/jpeg',
        'jpeg' => 'image/jpeg',
        'webp' => 'image/webp',
        'gif' => 'image/gif',
        'mp4' => 'video/mp4',
    ];
    return $map[$ext] ?? 'application/octet-stream';
}

function eca_public_file_safe_name(string $filename): string
{
    $name = basename(str_replace(["\0", '\\'], '', $filename));
    $name = str_replace(["\r", "\n", '"'], '', $name);
    return $name !== '' ? $name : 'document';
}

function eca_send_public_file(string $path, string $downloadName, bool $inline): void
{
    if (!is_file($path)) {
        http_response_code(404);
        header('Content-Type: text/plain; charset=UTF-8');
        echo 'File missing';
        exit;
    }

    while (ob_get_level() > 0) {
        ob_end_clean();
    }

    $name = eca_public_file_safe_name($downloadName);
    $mime = eca_public_file_mime($name);
    $disposition = ($inline ? 'inline' : 'attachment') . '; filename="' . $name . '"';

    header('Content-Type: ' . $mime);
    header('X-Content-Type-Options: nosniff');
    header('Content-Disposition: ' . $disposition);
    header('Content-Length: ' . (string) filesize($path));
    header('Cache-Control: private, max-age=0, must-revalidate');
    header_remove('Pragma');
    header_remove('Expires');
    header_remove('Content-Description');

    if (!$inline) {
        header('Content-Description: File Transfer');
    }

    readfile($path);
    exit;
}
