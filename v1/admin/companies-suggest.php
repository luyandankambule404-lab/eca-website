<?php
require_once __DIR__ . '/auth.php';

header('Content-Type: application/json; charset=UTF-8');
header('Cache-Control: no-store, no-cache, must-revalidate');

if (!eca_admin_user()) {
    http_response_code(401);
    echo json_encode(['ok' => false, 'items' => [], 'total' => 0]);
    exit;
}

$search = trim((string) ($_GET['search'] ?? $_GET['q'] ?? ''));
$industry = eca_request_industry();
$conn = eca_admin_db();

if ($search === '' || strlen($search) < 2 || !$conn) {
    echo json_encode([
        'ok' => true,
        'q' => $search,
        'industry' => $industry,
        'total' => 0,
        'items' => [],
    ]);
    exit;
}

$result = eca_fetch_directory($conn, $search, $industry, 12, 0, 'members', true);
$items = [];
foreach ($result['rows'] as $row) {
    $items[] = [
        'id' => (string) ($row['id'] ?? ''),
        'name' => (string) ($row['TradingName'] ?? ''),
        'number' => (string) ($row['MembershipNumber'] ?? ''),
        'industry' => (string) ($row['Clasification'] ?? ''),
        'region' => (string) ($row['Region'] ?? ''),
        'email' => (string) ($row['EmailAddress'] ?? ''),
        'phone' => (string) ($row['Cellphone'] ?? ''),
        'status' => (string) ($row['Status'] ?? ''),
    ];
}

echo json_encode([
    'ok' => true,
    'q' => $search,
    'industry' => $industry,
    'total' => (int) $result['total'],
    'items' => $items,
], JSON_UNESCAPED_UNICODE);
