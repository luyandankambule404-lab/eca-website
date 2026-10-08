<?php
require_once dirname(__DIR__) . "/auth.php";
eca_auth_no_store();

$err = "";
$nextParam = (string) ($_POST['next'] ?? $_GET['next'] ?? '/cpd/contractor/dashboard.php');
$localPreview = php_sapi_name() === 'cli-server';

if (!empty($_SESSION['user_id'])) {
    header('Location: ' . cpd_role_destination((string) ($_SESSION['role'] ?? 'CONTRACTOR')));
    exit;
}

if (is_post()) {
    cpd_require_csrf();
    $email = trim($_POST['email'] ?? '');
    $rateIdentity = (string) ($_SERVER['REMOTE_ADDR'] ?? 'unknown') . '|' . strtolower($email);
    if (cpd_rate_limit_exceeded('learner-login', $rateIdentity, 15, 900)) {
        http_response_code(429);
        $err = "Too many login attempts. Please wait 15 minutes and try again.";
    } elseif (empty($conn)) {
        $err = $localPreview
            ? "This computer cannot reach the learner database. Use the preview screens or the live portal."
            : "The learner portal is not available. Please try again shortly.";
    } else {
        $pass = $_POST['password'] ?? '';
        $u = cpd_authenticate($email, $pass, true);
        if (!$u) {
            $err = "Invalid email or password.";
        } else {
            cpd_start_authenticated_session($u);
            $dest = function_exists('eca_login_next')
                ? eca_login_next($nextParam, eca_is_cpd_learner_role((string) $u['role']) ? 'learner' : 'officer')
                : cpd_role_destination((string) $u['role']);
            redirect($dest);
        }
    }
}
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>ECA Contractor Portal Login</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
    <style>
        :root {
            --eca-navy: #041f52;
            --eca-navy-dark: #020f29;
            --eca-red: #c40000;
            --eca-red-bright: #e60000;
            --eca-border: #d4dce8;
            --eca-muted: #66758f;
        }
        * { box-sizing: border-box; }
        body {
            min-height: 100vh;
            margin: 0;
            display: grid;
            place-items: center;
            padding: 24px;
            font-family: Arial, sans-serif;
            color: var(--eca-navy);
            background:
                linear-gradient(90deg, rgba(2,15,41,.98) 0%, rgba(4,31,82,.94) 42%, rgba(4,31,82,.76) 100%),
                url("/cpd/image/construction12.png") right center / cover no-repeat;
        }
        .login-shell { width: 100%; max-width: 470px; }
        .login-card {
            overflow: hidden;
            border: 1px solid rgba(4, 31, 82, .08);
            border-radius: 24px;
            background: #fff;
            box-shadow: 0 26px 70px rgba(4, 31, 82, .19);
        }
        .login-header {
            padding: 30px 28px 27px;
            color: #fff;
            text-align: center;
            background: linear-gradient(135deg, var(--eca-navy-dark), var(--eca-navy));
            border-bottom: 5px solid var(--eca-red);
        }
        .logo-box {
            width: 150px;
            height: 68px;
            margin: 0 auto 16px;
            padding: 8px 12px;
            display: grid;
            place-items: center;
            border-radius: 13px;
            background: #fff;
        }
        .logo-box img { width: 100%; height: 100%; object-fit: contain; }
        .login-header h1 { margin: 0; font-size: 1.55rem; font-weight: 900; }
        .login-header p { margin: 7px 0 0; color: rgba(255,255,255,.82); font-size: .9rem; font-weight: 600; }
        .login-body { padding: 31px; }
        .form-label { margin-bottom: 7px; color: #344054; font-size: .84rem; font-weight: 800; }
        .input-group-text, .form-control, .password-toggle { min-height: 50px; border-color: #cfd8e3; }
        .input-group-text { width: 48px; justify-content: center; color: var(--eca-red); background: #f7f9fc; }
        .form-control { font-weight: 600; }
        .password-toggle { width: 50px; color: var(--eca-navy); background: #f7f9fc; }
        .btn-login {
            min-height: 51px;
            border: 0;
            border-radius: 12px;
            color: #fff;
            background: linear-gradient(135deg, var(--eca-red), var(--eca-red-bright));
            font-weight: 900;
        }
        .btn-login:hover {
            color: #fff;
            background: linear-gradient(135deg, var(--eca-navy-dark), var(--eca-navy));
        }
        .login-note {
            margin-top: 22px;
            padding-top: 18px;
            border-top: 1px solid var(--eca-border);
            color: var(--eca-muted);
            text-align: center;
            font-size: .8rem;
            font-weight: 600;
            line-height: 1.6;
        }
        .login-note a { color: var(--eca-navy); font-weight: 800; }
        @media (max-width: 520px) {
            body { padding: 14px; }
            .login-body { padding: 24px 20px; }
        }
    </style>
</head>
<body>
    <div class="login-shell">
        <div class="login-card">
            <div class="login-header">
                <div class="logo-box">
                    <img src="/cpd/images/logo.jpg" alt="ECA Logo">
                </div>
                <h1>Contractor Learning Portal</h1>
                <p>Eswatini Contractors Association CPD System</p>
            </div>
            <div class="login-body">
                <?php if ($err): ?>
                    <div class="alert alert-danger"><?= e($err) ?></div>
                <?php endif; ?>
                <form method="post" action="" autocomplete="on">
                    <?= cpd_csrf_input() ?>
                    <input type="hidden" name="next" value="<?= e($nextParam) ?>">
                    <div class="mb-3">
                        <label class="form-label" for="email">Email Address</label>
                        <div class="input-group">
                            <span class="input-group-text"><i class="fa-solid fa-envelope"></i></span>
                            <input type="email" name="email" id="email" class="form-control" placeholder="name@example.com" autocomplete="username" required autofocus>
                        </div>
                    </div>
                    <div class="mb-4">
                        <label class="form-label" for="password">Password</label>
                        <div class="input-group">
                            <span class="input-group-text"><i class="fa-solid fa-lock"></i></span>
                            <input type="password" name="password" id="password" class="form-control" placeholder="Enter your password" autocomplete="current-password" required>
                            <button type="button" class="btn password-toggle" id="togglePassword" aria-label="Show password">
                                <i class="fa-solid fa-eye"></i>
                            </button>
                        </div>
                    </div>
                    <button type="submit" class="btn btn-login w-100">
                        <i class="fa-solid fa-right-to-bracket me-2"></i>
                        Sign In
                    </button>
                </form>
                <div class="login-note">
                    Use the email address and the <strong>latest temporary password</strong>
                    sent after your CPD application was approved.<br>
                    Support: trainings@eca.co.sz
                    <?php if ($localPreview): ?>
                        <br><a href="/cpd/login.php">Unified learner login</a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
    <script>
        (function () {
            var passwordInput = document.getElementById('password');
            var toggleButton = document.getElementById('togglePassword');
            if (!passwordInput || !toggleButton) return;
            toggleButton.addEventListener('click', function () {
                var isVisible = passwordInput.type === 'text';
                passwordInput.type = isVisible ? 'password' : 'text';
                toggleButton.innerHTML = isVisible
                    ? '<i class="fa-solid fa-eye"></i>'
                    : '<i class="fa-solid fa-eye-slash"></i>';
                toggleButton.setAttribute('aria-label', isVisible ? 'Show password' : 'Hide password');
            });
        })();
    </script>
</body>
</html>
