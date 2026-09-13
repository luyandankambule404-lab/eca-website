<?php
session_start();
require_once "config.php";

$requestId = $_GET['request_id'] ?? '';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Waiting for Approval</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css" rel="stylesheet">
</head>
<body style="background: linear-gradient(135deg, #0f172a, #1e293b); min-height:100vh;">

<div class="container d-flex align-items-center justify-content-center" style="min-height:100vh;">
    <div class="card border-0 shadow-lg rounded-4 p-4 text-center" style="max-width:500px; width:100%;">
        <div class="mb-3">
            <div class="spinner-border text-warning" style="width:3.5rem; height:3.5rem;" role="status"></div>
        </div>

        <h2 class="fw-bold mb-2">Waiting for Approval</h2>
        <p class="text-muted mb-3">
            We have sent a payment request to your phone.<br>
            Please approve the MoMo prompt to complete the payment.
        </p>

        <div class="alert alert-warning rounded-3">
            <strong>Request ID:</strong> <?= htmlspecialchars($requestId) ?>
        </div>

        <p class="small text-muted mb-0">You will be redirected automatically after a few seconds.</p>
    </div>
</div>
<script>
setTimeout(function () {
    window.location.href = "registration.php";
}, 10000);registration.php
</script>

</body>
</html>