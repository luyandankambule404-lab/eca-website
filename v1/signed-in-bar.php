<?php
require_once __DIR__ . '/includes/session.php';
require_once __DIR__ . '/includes/hub.php';

if (!headers_sent()) {
    header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
    header('Content-Type: text/html; charset=UTF-8');
}

if (eca_public_signed_in_href() === null) {
    http_response_code(204);
    exit;
}

eca_public_signed_in_back();
