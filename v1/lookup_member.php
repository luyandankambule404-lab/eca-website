<?php
require_once __DIR__ . '/includes/session.php';
require_once __DIR__ . '/includes/http.php';
require_once __DIR__ . '/includes/portal-db.php';
require_once __DIR__ . '/includes/membership.php';

$ip = (string) ($_SERVER['REMOTE_ADDR'] ?? 'unknown');
if (eca_rate_limit_exceeded('member-lookup', $ip, 30, 60)) {
    eca_json_error('Too many lookup requests. Please wait and try again.', 429);
}

$membership = trim((string) ($_GET['membership_number'] ?? $_GET['m'] ?? ''));
if ($membership === '' || strlen($membership) > 40 || !preg_match('/^[A-Za-z0-9\/_-]+$/', $membership)) {
    eca_json_error('Enter a valid membership number.');
}

$conn = eca_portal_pdo(false);
if (!$conn) {
    eca_json_error('The membership database is not available.', 503);
}

$result = eca_lookup_member_by_number($conn, $membership);
if (empty($result['found']) || empty($result['member'])) {
    eca_json_ok([
        'found' => false,
        'error' => 'No member found for this membership number.',
        'member' => null,
        'owners' => [],
    ]);
}

eca_json_ok([
    'found' => true,
    'member' => $result['member'],
    'owners' => $result['owners'] ?? [],
]);
