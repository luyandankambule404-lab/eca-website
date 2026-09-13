<?php
if (php_sapi_name() !== 'cli-server') {
    header('Location: login.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>CPD dashboard preview | ECA</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link rel="stylesheet" href="/css/dashboard.css">
    <style>
        body { margin: 0; }
        .preview-wrap { max-width: 1180px; margin: 0 auto; padding: 28px 20px 60px; }
        .kpi-grid .kpi-card-premium { min-height: 140px; padding: 20px; border-radius: 18px; }
        .kpi-top { display: flex; justify-content: space-between; align-items: flex-start; }
        .kpi-value { font-size: 2rem; font-weight: 800; }
        .kpi-label { font-size: 13px; font-weight: 800; letter-spacing: .06em; text-transform: uppercase; }
        .kpi-icon { width: 42px; height: 42px; display: grid; place-items: center; border-radius: 12px; }
        .preview-nav { display: flex; gap: 12px; flex-wrap: wrap; margin: 0 0 28px; }
        .preview-nav a { color: #000066; font-weight: 800; text-decoration: none; }
        .preview-nav a:hover { color: #d90920; }
    </style>
</head>
<body>
    <div class="eca-dash-topbar">
        <div class="eca-dash-topbar-inner">
            <span>Eswatini Contractors Association</span>
            <a href="mailto:info@eca.co.sz">info@eca.co.sz</a>
        </div>
    </div>
    <div class="preview-wrap">
        <p class="eca-kicker">Local preview</p>
        <h1 style="color:#000066;font-weight:800;letter-spacing:-.03em;">CPD dashboards</h1>
        <p style="color:#667085;max-width:640px;">Sample layout only. Live totals appear after upload, when the CPD database is available.</p>
        <div class="preview-nav">
            <a href="login.php">Contractor login</a>
            <a href="admin_login.php">Staff login</a>
            <a href="/index.php">Website</a>
        </div>

        <h2 style="color:#000066;font-size:1.2rem;font-weight:800;margin:28px 0 16px;">Officer / admin</h2>
        <div class="row g-3 kpi-grid">
            <div class="col-md-3"><div class="kpi-card-premium kpi-dark"><div class="kpi-top"><div><div class="kpi-label">Contractors</div><div class="kpi-value">128</div></div><div class="kpi-icon"><i class="fa-solid fa-people-group"></i></div></div></div></div>
            <div class="col-md-3"><div class="kpi-card-premium kpi-dark"><div class="kpi-top"><div><div class="kpi-label">Courses</div><div class="kpi-value">14</div></div><div class="kpi-icon"><i class="fa-solid fa-book-open-reader"></i></div></div></div></div>
            <div class="col-md-3"><div class="kpi-card-premium kpi-dark"><div class="kpi-top"><div><div class="kpi-label">Pending apps</div><div class="kpi-value">6</div></div><div class="kpi-icon"><i class="fa-solid fa-hourglass-half"></i></div></div></div></div>
            <div class="col-md-3"><div class="kpi-card-premium kpi-dark"><div class="kpi-top"><div><div class="kpi-label">Points issued</div><div class="kpi-value">842</div></div><div class="kpi-icon"><i class="fa-solid fa-ranking-star"></i></div></div></div></div>
        </div>

        <div class="row g-3 mt-2">
            <div class="col-lg-8">
                <div class="card card-premium"><div class="card-body">
                    <div class="d-flex justify-content-between"><div><strong>CPD progress (last 3 years)</strong><div class="text-muted small">Target 36 points</div></div><div>18 / 36 pts</div></div>
                    <div class="progress progress-brand mt-3"><div class="progress-bar" style="width:50%"></div></div>
                </div></div>
            </div>
            <div class="col-lg-4">
                <div class="quick-tile"><div class="t mb-1">Quick insight</div><div class="s">12 points a year keeps a member on track.</div></div>
            </div>
        </div>

        <h2 style="color:#000066;font-size:1.2rem;font-weight:800;margin:40px 0 16px;">Contractor</h2>
        <div class="row g-3">
            <div class="col-md-4"><div class="course-card card-premium p-4"><h6>Contract training</h6><div class="small text-muted mb-3">Start 12 May 2026</div><div class="progress"><div class="progress-bar" style="width:70%"></div></div></div></div>
            <div class="col-md-4"><div class="course-card card-premium p-4"><h6>Safety management</h6><div class="small text-muted mb-3">Start 4 Jun 2026</div><div class="progress"><div class="progress-bar" style="width:40%"></div></div></div></div>
            <div class="col-md-4"><div class="course-card card-premium p-4"><h6>Tendering fundamentals</h6><div class="small text-muted mb-3">Start 18 Jul 2026</div><div class="progress"><div class="progress-bar" style="width:20%"></div></div></div></div>
        </div>
    </div>
</body>
</html>
