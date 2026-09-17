<?php
$status = (int) ($pageStatus ?? 500);
$title = (string) ($pageTitle ?? 'Error');
$message = (string) ($pageMessage ?? 'Please try again.');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= htmlspecialchars($title, ENT_QUOTES, 'UTF-8') ?> | ECA</title>
    <link rel="stylesheet" href="/css/theme.css?v=10">
</head>
<body style="margin:0;background:#f4f6f9;color:#192754;font-family:'Open Sans',Arial,sans-serif;">
    <main style="max-width:640px;margin:12vh auto;padding:24px;">
        <p style="margin:0 0 8px;font-size:12px;font-weight:800;letter-spacing:.08em;text-transform:uppercase;color:#d50d0e;">ECA <?= (int) $status ?></p>
        <h1 style="margin:0 0 12px;font-size:1.8rem;"><?= htmlspecialchars($title, ENT_QUOTES, 'UTF-8') ?></h1>
        <p style="color:#667085;line-height:1.6;"><?= htmlspecialchars($message, ENT_QUOTES, 'UTF-8') ?></p>
        <p style="margin-top:24px;"><a href="/" style="color:#192754;font-weight:700;">Back to the website</a></p>
    </main>
</body>
</html>
