<?php
header('Content-Type: application/json; charset=UTF-8');
header('Cache-Control: no-store, no-cache, must-revalidate');

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/includes/company-data.php';

$search = trim((string) ($_GET['search'] ?? $_GET['q'] ?? ''));
$industry = eca_request_industry();
$limit = (int) ($_GET['limit'] ?? 600);
$limit = max(1, min($limit, 2000));

$database = new Database();
$conn = $database->getConnection(false);

if (!$conn) {
    echo json_encode([
        'ok' => true,
        'q' => $search,
        'industry' => $industry,
        'total' => 0,
        'items' => [],
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

$result = eca_fetch_directory($conn, $search, $industry, $limit, 0, 'members', true);
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
