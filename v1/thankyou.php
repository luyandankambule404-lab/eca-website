<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Renewal submitted | ECA</title>
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

        .card .btn {
            display: inline-block;
            margin-top: 28px;
            padding: 12px 24px;
            background-color: #192754;
            color: #fff;
            text-decoration: none;
            border-radius: 999px;
            font-weight: 700;
        }

        .card .btn:hover {
            background-color: #d50d0e;
        }
    </style>
</head>
<body>
    <div class="card">
        <p class="kicker">Membership renewal</p>
        <h1>Renewal application submitted</h1>
        <p>
            Dear Applicant,<br><br>
            Your ECA membership renewal application has been successfully submitted.
            Please allow up to <strong>2 working days</strong> for processing.
        </p>
        <a href="index.php" class="btn">Back to home</a>
    </div>

    <script>
        setTimeout(function () {
            window.location.href = 'index.php';
        }, 10000);
    </script>
</body>
</html>
