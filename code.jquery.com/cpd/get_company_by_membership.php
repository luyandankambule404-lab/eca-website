<?php
session_start();
require_once "config.php";

header('Content-Type: application/json');

function clean_input($value): string {
    return trim((string)$value);
}

$membership_number = clean_input($_GET['membership_number'] ?? '');

if ($membership_number === '') {
    echo json_encode([
        'status' => 'error',
        'message' => 'Membership number is required.'
    ]);
    exit;
}

$stmt = $conn->prepare("
    SELECT 
        client_id,
        TradingName,
        CompanyRegistrationName,
        MembershipNumber,
        Clasification,
        Region,
        EmailAddress,
        telephone,
        address,
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
        'client_id' => $company['client_id'],
        'company_name' => $company['TradingName'],
        'company_registration_name' => $company['CompanyRegistrationName'],
        'membership_number' => $company['MembershipNumber'],
        'discipline' => $company['Clasification'],
        'region' => $company['Region'],
        'email' => $company['EmailAddress'],
        'telephone' => $company['telephone'],
        'address' => $company['address'],
        'status' => $company['Status'],
        'active' => $company['active']
    ]
]);
exit;