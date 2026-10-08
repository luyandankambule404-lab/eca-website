<?php
require_once __DIR__ . '/includes/session.php';
require_once __DIR__ . '/includes/application-track.php';

$ref = eca_track_normalize_reference((string) ($_GET['ref'] ?? ''));
if (!eca_track_reference_valid($ref)) {
    $ref = '';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Application submitted | ECA</title>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@500;700;800&family=Roboto:wght@500;700&display=swap" rel="stylesheet">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }

        body {
            min-height: 100vh;
            display: flex;
            justify-content: center;
            align-items: center;
            padding: 24px;
            color: #111827;
            background:
                radial-gradient(circle at 80% 18%, rgba(213, 13, 14, 0.22), transparent 28%),
                linear-gradient(135deg, #192754, #192754);
            font-family: "Plus Jakarta Sans", "Roboto", sans-serif;
        }

        .card {
            background: #fff;
            padding: 42px 32px;
            border-radius: 22px;
            max-width: 520px;
            width: 100%;
            text-align: center;
            box-shadow: 0 22px 55px rgba(25, 39, 84, 0.22);
        }

        .kicker {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            margin-bottom: 16px;
            color: #d50d0e;
            font-size: 12px;
            font-weight: 800;
            letter-spacing: 0.14em;
            text-transform: uppercase;
        }

        .card h1 {
            color: #192754;
            margin-bottom: 18px;
            font-size: 28px;
            letter-spacing: -0.03em;
        }

        .card p {
            color: #667085;
            font-size: 17px;
            line-height: 1.7;
        }

        .ref-box {
            margin: 22px 0 8px;
            padding: 16px 14px;
            color: #192754;
            background: #f4f6fa;
            border: 1px solid #d8e0ea;
            border-radius: 14px;
        }

        .ref-box span {
            display: block;
            margin-bottom: 6px;
            color: #d50d0e;
            font-size: 12px;
            font-weight: 800;
            letter-spacing: 0.12em;
            text-transform: uppercase;
        }

        .ref-box strong {
            display: block;
            font-size: clamp(1.15rem, 4vw, 1.45rem);
            letter-spacing: 0.03em;
            word-break: break-word;
            user-select: all;
        }

        .card .actions {
            display: flex;
            flex-wrap: wrap;
            justify-content: center;
            gap: 10px;
            margin-top: 28px;
        }

        .card .btn {
            display: inline-block;
            padding: 12px 24px;
            background-color: #192754;
            color: #fff;
            text-decoration: none;
            border-radius: 999px;
            font-weight: 700;
        }

        .card .btn:hover,
        .card .btn:focus-visible {
            background-color: #d50d0e;
        }
    </style>
</head>
<body>
    <div class="card">
        <p class="kicker">Membership</p>
        <h1>Application submitted</h1>
        <p>
            Your ECA membership application has been successfully submitted.
            <?php if ($ref !== ''): ?>
            Keep your application reference. A copy is also sent to the email address you provided on the application.
            <?php endif; ?>
            Please allow up to <strong>2 working days</strong> for processing.
        </p>
        <?php if ($ref !== ''): ?>
        <p class="ref-box">
            <span>Application Reference</span>
            <strong><?= htmlspecialchars($ref, ENT_QUOTES, 'UTF-8') ?></strong>
        </p>
        <p>Use this reference on the tracking page to follow the status of your application.</p>
        <?php endif; ?>
        <div class="actions">
            <?php if ($ref !== ''): ?>
            <a href="track.php?ref=<?= urlencode($ref) ?>" class="btn">Track application</a>
            <?php endif; ?>
            <a href="index.php" class="btn">Back to home</a>
        </div>
    </div>
</body>
</html>
