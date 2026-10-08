<?php
session_start();
require_once "config.php";

header('Content-Type: application/json');

function clean_input($value): string {
    return trim((string)$value);
}

$membership_number = clean_input($_GET['membership_number'] ?? '');

if ($membership_number === '' || strlen($membership_number) > 40 || !preg_match('/^[A-Za-z0-9\/_-]+$/', $membership_number)) {
    echo json_encode([
        'status' => 'error',
        'message' => 'Membership number is required.'
    ]);
    exit;
}

$now = time();
$lookups = array_values(array_filter(
    (array) ($_SESSION['cpd_company_lookups'] ?? []),
    static fn ($timestamp): bool => is_int($timestamp) && $timestamp > $now - 60
));
if (count($lookups) >= 30) {
    http_response_code(429);
    echo json_encode([
        'status' => 'error',
        'message' => 'Too many lookup requests. Please wait and try again.'
    ]);
    exit;
}
$lookups[] = $now;
$_SESSION['cpd_company_lookups'] = $lookups;

$stmt = $conn->prepare("
    SELECT 
        TradingName,
        CompanyRegistrationName,
        MembershipNumber,
        Clasification,
        Region,
        Status,
        active
    FROM tbl_client
    WHERE MembershipNumber = ?
    LIMIT 1
");

if (!$stmt) {
    echo json_encode([
        'status' => 'error',
        'message' => 'Database query failed.'
    ]);
    exit;
}

$stmt->bind_param("s", $membership_number);
$stmt->execute();
$result = $stmt->get_result();
$company = $result->fetch_assoc();
$stmt->close();

if (!$company) {
    echo json_encode([
        'status' => 'error',
        'message' => 'No company found for this membership number.'
    ]);
    exit;
}

echo json_encode([
    'status' => 'success',
    'message' => 'Company found.',
    'data' => [
        'company_name' => $company['TradingName'],
        'company_registration_name' => $company['CompanyRegistrationName'],
        'membership_number' => $company['MembershipNumber'],
        'discipline' => $company['Clasification'],
        'region' => $company['Region'],
        'status' => $company['Status'],
        'active' => $company['active']
    ]
]);
exit;