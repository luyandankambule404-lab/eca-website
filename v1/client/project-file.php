<?php
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/../includes/member-projects.php';
require_once __DIR__ . '/../includes/audit.php';

$member = eca_portal_member();
$portal = eca_db();
$id = (int) ($_GET['id'] ?? 0);
$membership = trim((string) ($member['membership'] ?? ''));
if (!$portal || $id < 1 || $membership === '') {
    http_response_code(404);
    exit('File not found');
}

$row = eca_project_owned_row($portal, $id, $membership);
if (!$row) {
    http_response_code(404);
    exit('File not found');
}

$path = eca_project_dir() . DIRECTORY_SEPARATOR . basename((string) $row['stored_name']);
if (!is_file($path)) {
    http_response_code(404);
    exit('File missing');
}

$ext = strtolower(pathinfo($path, PATHINFO_EXTENSION));
$mimes = [
    'pdf' => 'application/pdf',
    'png' => 'image/png',
    'jpg' => 'image/jpeg',
    'jpeg' => 'image/jpeg',
    'doc' => 'application/msword',
    'docx' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
    'xls' => 'application/vnd.ms-excel',
    'xlsx' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
];
$downloadName = basename((string) ($row['original_name'] ?: $row['stored_name']));
$inline = in_array($ext, ['pdf', 'png', 'jpg', 'jpeg'], true);
eca_audit('project.open', 'member_project_files', (string) $id);
header('Content-Type: ' . ($mimes[$ext] ?? 'application/octet-stream'));
header('Content-Disposition: ' . ($inline ? 'inline' : 'attachment') . '; filename="' . str_replace('"', '', $downloadName) . '"');
header('Content-Length: ' . (string) filesize($path));
header('X-Content-Type-Options: nosniff');
header('Cache-Control: private, no-store');
readfile($path);
exit;
