<?php
header('Content-Type: application/json');

require_once "../config.php";

/*
|--------------------------------------------------------------------------
| MoMo Callback Endpoint
|--------------------------------------------------------------------------
| Remote system should call this URL after approval:
| https://yourdomain.co.sz/cpd/api/momo_callback.php
|--------------------------------------------------------------------------
*/

$callbackToken = $_POST['api_token'] ?? $_GET['api_token'] ?? '';

if (!defined('MOMO_API_TOKEN') || $callbackToken !== MOMO_API_TOKEN) {
    echo json_encode([
        'status' => 'error',
        'message' => 'Unauthorized callback.'
    ]);
    exit;
}

$requestId = trim($_POST['request_id'] ?? $_GET['request_id'] ?? '');
$referenceId = trim($_POST['reference_id'] ?? $_GET['reference_id'] ?? '');

$remoteStatus = strtoupper(trim(
    $_POST['status'] ??
    $_POST['payment_status'] ??
    $_POST['transaction_status'] ??
    $_POST['result'] ??
    $_GET['status'] ??
    $_GET['payment_status'] ??
    $_GET['transaction_status'] ??
    $_GET['result'] ??
    ''
));

$transactionId = trim(
    $_POST['transaction_id'] ??
    $_POST['txn_id'] ??
    $_POST['momo_transaction_id'] ??
    $_GET['transaction_id'] ??
    $_GET['txn_id'] ??
    $_GET['momo_transaction_id'] ??
    ''
);

if ($requestId === '' && $referenceId === '') {
    echo json_encode([
        'status' => 'error',
        'message' => 'Request ID or Reference ID is required.'
    ]);
    exit;
}

$approvedStatuses = [
    'APPROVED',
    'SUCCESS',
    'SUCCESSFUL',
    'PAID',
    'COMPLETED'
];

$failedStatuses = [
    'FAILED',
    'DECLINED',
    'CANCELLED',
    'CANCELED',
    'ERROR'
];

$localStatus = 'PENDING';

if (in_array($remoteStatus, $approvedStatuses, true)) {
    $localStatus = 'APPROVED';
} elseif (in_array($remoteStatus, $failedStatuses, true)) {
    $localStatus = 'FAILED';
}

$remoteResponse = json_encode([
    'post' => $_POST,
    'get' => $_GET
]);

if ($localStatus === 'APPROVED') {
    $sql = "
        UPDATE wallet_transactions
        SET
            status = 'APPROVED',
            transaction_id = ?,
            approved_at = NOW(),
            updated_at = NOW(),
            remote_response = ?
        WHERE 
            (request_id = ? AND ? <> '')
            OR
            (reference_id = ? AND ? <> '')
        LIMIT 1
    ";
} elseif ($localStatus === 'FAILED') {
    $sql = "
        UPDATE wallet_transactions
        SET
            status = 'FAILED',
            transaction_id = ?,
            updated_at = NOW(),
            remote_response = ?
        WHERE 
            (request_id = ? AND ? <> '')
            OR
            (reference_id = ? AND ? <> '')
        LIMIT 1
    ";
} else {
    $sql = "
        UPDATE wallet_transactions
        SET
            status = 'PENDING',
            transaction_id = ?,
            updated_at = NOW(),
            remote_response = ?
        WHERE 
            (request_id = ? AND ? <> '')
            OR
            (reference_id = ? AND ? <> '')
        LIMIT 1
    ";
}

$stmt = $conn->prepare($sql);

if (!$stmt) {
    echo json_encode([
        'status' => 'error',
        'message' => 'Failed to prepare callback update.',
        'error' => $conn->error
    ]);
    exit;
}

$stmt->bind_param(
    "ssssss",
    $transactionId,
    $remoteResponse,
    $requestId,
    $requestId,
    $referenceId,
    $referenceId
);

$stmt->execute();

$affectedRows = $stmt->affected_rows;
$stmt->close();

if ($affectedRows > 0) {
    echo json_encode([
        'status' => 'success',
        'message' => 'Wallet transaction updated successfully.',
        'local_status' => $localStatus,
        'request_id' => $requestId,
        'reference_id' => $referenceId
    ]);
    exit;
}

echo json_encode([
    'status' => 'error',
    'message' => 'No matching transaction found.',
    'request_id' => $requestId,
    'reference_id' => $referenceId
]);
exit;
?>