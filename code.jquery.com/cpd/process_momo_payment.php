<?php
session_start();
require_once "config.php";

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: index.php");
    exit;
}

$amount = isset($_POST['amount']) ? (float) $_POST['amount'] : 0;
$mobileNumber = trim($_POST['mobile_number'] ?? '');

if ($amount <= 0 || $mobileNumber === '') {
    $_SESSION['error'] = "Please enter a valid amount and mobile number.";
    header("Location: index.php");
    exit;
}

/* Optional: sanitize mobile number */
$mobileNumber = preg_replace('/\D+/', '', $mobileNumber);

/* Example request ID */
$requestId = 'MOMO-' . time() . '-' . rand(1000, 9999);

/* Save transaction first */
$stmt = $conn->prepare("
    INSERT INTO wallet_transactions (request_id, mobile_number, amount, status, created_at)
    VALUES (?, ?, ?, 'PENDING', NOW())
");
$stmt->bind_param("ssd", $requestId, $mobileNumber, $amount);
$stmt->execute();
$stmt->close();

/* Call your MoMo function here */
# $response = paymentMoMo($requestId, $mobileNumber, $amount, 'Wallet Deposit');

/* For now redirect to waiting page */
header("Location: waiting_approval.php?request_id=" . urlencode($requestId));
exit;
?>