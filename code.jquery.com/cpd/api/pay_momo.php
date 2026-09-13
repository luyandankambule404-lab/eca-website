<?php
header('Content-Type: application/json');
session_start();

require_once "../config.php";

/*
|--------------------------------------------------------------------------
| Add this in config.php:
| define('MOMO_API_TOKEN', 'YOUR_REAL_MOMO_API_TOKEN_HERE');
|--------------------------------------------------------------------------
*/

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode([
        'status' => 'error',
        'message' => 'Invalid request method.'
    ]);
    exit;
}

/* =========================
   GET POST DATA
========================= */
$amount = isset($_POST['amount']) ? (float)$_POST['amount'] : 0;
$mobileNumber = trim($_POST['mobile_number'] ?? '');

$membershipNumber = trim($_POST['membership_number'] ?? '');
$applicationId = isset($_POST['application_id']) && $_POST['application_id'] !== ''
    ? (int)$_POST['application_id']
    : null;

/* =========================
   VALIDATION
========================= */
if ($amount <= 0 || $mobileNumber === '') {
    echo json_encode([
        'status' => 'error',
        'message' => 'Amount and mobile number are required.'
    ]);
    exit;
}

if ($membershipNumber === '') {
    echo json_encode([
        'status' => 'error',
        'message' => 'Membership number is required before making MoMo payment.'
    ]);
    exit;
}

$mobileNumber = preg_replace('/\D+/', '', $mobileNumber);

if (strlen($mobileNumber) < 8) {
    echo json_encode([
        'status' => 'error',
        'message' => 'Please enter a valid mobile number.'
    ]);
    exit;
}

/* =========================
   GENERATE IDS
========================= */
$requestId = 'MOMO-' . date('YmdHis') . '-' . rand(1000, 9999);

$referenceId = sprintf(
    '%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
    mt_rand(0, 0xffff),
    mt_rand(0, 0xffff),
    mt_rand(0, 0xffff),
    mt_rand(0, 0x0fff) | 0x4000,
    mt_rand(0, 0x3fff) | 0x8000,
    mt_rand(0, 0xffff),
    mt_rand(0, 0xffff),
    mt_rand(0, 0xffff)
);

/* =========================
   SAVE LOCAL PENDING TRANSACTION
========================= */
if (!$conn) {
    echo json_encode([
        'status' => 'error',
        'message' => 'Database connection failed.'
    ]);
    exit;
}

$stmt = $conn->prepare("
    INSERT INTO wallet_transactions 
    (
        request_id,
        membership_number,
        application_id,
        reference_id,
        mobile_number,
        amount,
        status,
        created_at,
        updated_at
    )
    VALUES (?, ?, ?, ?, ?, ?, 'PENDING', NOW(), NOW())
");

if (!$stmt) {
    echo json_encode([
        'status' => 'error',
        'message' => 'Failed to prepare transaction query.',
        'error' => $conn->error
    ]);
    exit;
}

if ($applicationId !== null && $applicationId <= 0) {
    $applicationId = null;
}

$stmt->bind_param(
    "ssissd",
    $requestId,
    $membershipNumber,
    $applicationId,
    $referenceId,
    $mobileNumber,
    $amount
);

if (!$stmt->execute()) {
    echo json_encode([
        'status' => 'error',
        'message' => 'Failed to save transaction.',
        'error' => $stmt->error
    ]);
    $stmt->close();
    exit;
}

$stmt->close();

/* =========================
   SEND TO REMOTE MOMO API
========================= */
$remoteUrl = "https://c4.technosol.co.sz/ussd/smart/momo_api_receive.php";
$apiToken  = "eca_momo_SECURE_9f7aB3xK82LmQpR5vT1ZcX8wY6uD4Hs";

if ($apiToken === '') {
    echo json_encode([
        'status' => 'error',
        'message' => 'MoMo API token is not configured.'
    ]);
    exit;
}

$postData = [
    'request_id'         => $requestId,
    'reference_id'       => $referenceId,
    'membership_number'  => $membershipNumber,
    'application_id'     => $applicationId,
    'mobile_number'      => $mobileNumber,
    'amount'             => number_format($amount, 2, '.', ''),
    'total_votes'        => 1,
    'pagent_code'        => 'PAYMENT',
    'api_token'          => $apiToken
];

$ch = curl_init($remoteUrl);

curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($postData));
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_TIMEOUT, 90);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);

$response = curl_exec($ch);
$curlError = curl_error($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

curl_close($ch);

if ($curlError) {
    echo json_encode([
        'status' => 'error',
        'message' => 'Failed to connect to remote MoMo API.',
        'error' => $curlError,
        'request_id' => $requestId,
        'reference_id' => $referenceId
    ]);
    exit;
}

$data = json_decode($response, true);

/* =========================
   UPDATE WALLET TRANSACTION IF APPROVED
========================= */
if ($httpCode == 200 && is_array($data)) {

    $remoteStatus = strtoupper(trim(
        $data['status'] ??
        $data['payment_status'] ??
        $data['transaction_status'] ??
        $data['result'] ??
        ''
    ));

    $transactionId = trim(
        $data['transaction_id'] ??
        $data['txn_id'] ??
        $data['momo_transaction_id'] ??
        ''
    );

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

    $remoteResponseJson = json_encode($data);

    if ($localStatus === 'APPROVED') {
        $updateStmt = $conn->prepare("
            UPDATE wallet_transactions
            SET 
                status = 'APPROVED',
                transaction_id = ?,
                approved_at = NOW(),
                updated_at = NOW(),
                remote_response = ?
            WHERE request_id = ?
            LIMIT 1
        ");

        if ($updateStmt) {
            $updateStmt->bind_param(
                "sss",
                $transactionId,
                $remoteResponseJson,
                $requestId
            );
            $updateStmt->execute();
            $updateStmt->close();
        }
    } elseif ($localStatus === 'FAILED') {
        $updateStmt = $conn->prepare("
            UPDATE wallet_transactions
            SET 
                status = 'FAILED',
                transaction_id = ?,
                updated_at = NOW(),
                remote_response = ?
            WHERE request_id = ?
            LIMIT 1
        ");

        if ($updateStmt) {
            $updateStmt->bind_param(
                "sss",
                $transactionId,
                $remoteResponseJson,
                $requestId
            );
            $updateStmt->execute();
            $updateStmt->close();
        }
    } else {
        $updateStmt = $conn->prepare("
            UPDATE wallet_transactions
            SET 
                updated_at = NOW(),
                remote_response = ?
            WHERE request_id = ?
            LIMIT 1
        ");

        if ($updateStmt) {
            $updateStmt->bind_param(
                "ss",
                $remoteResponseJson,
                $requestId
            );
            $updateStmt->execute();
            $updateStmt->close();
        }
    }

    if (!isset($data['request_id'])) {
        $data['request_id'] = $requestId;
    }

    if (!isset($data['reference_id'])) {
        $data['reference_id'] = $referenceId;
    }

    if (!isset($data['membership_number'])) {
        $data['membership_number'] = $membershipNumber;
    }

    if (!isset($data['application_id'])) {
        $data['application_id'] = $applicationId;
    }

    if (!isset($data['local_status'])) {
        $data['local_status'] = $localStatus;
    }

    echo json_encode($data);
    exit;
}

/* =========================
   INVALID REMOTE RESPONSE
========================= */
$rawResponse = $response;

$updateStmt = $conn->prepare("
    UPDATE wallet_transactions
    SET 
        status = 'FAILED',
        updated_at = NOW(),
        remote_response = ?
    WHERE request_id = ?
    LIMIT 1
");

if ($updateStmt) {
    $updateStmt->bind_param("ss", $rawResponse, $requestId);
    $updateStmt->execute();
    $updateStmt->close();
}

echo json_encode([
    'status' => 'error',
    'message' => 'Invalid response from remote MoMo API.',
    'request_id' => $requestId,
    'reference_id' => $referenceId,
    'membership_number' => $membershipNumber,
    'application_id' => $applicationId,
    'raw' => $response
]);
exit;
?>