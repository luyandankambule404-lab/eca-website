<?php
require_once "../auth1.php";
require_role('CONTRACTOR');
require_once "../config.php";

$user_id    = (int)($_SESSION['user_id'] ?? 0);
$user_email = $_SESSION['email'] ?? '';
$user_name  = $_SESSION['full_name'] ?? (($user_email && strpos($user_email, '@') !== false) ? explode('@', $user_email)[0] : 'User');

/* -----------------------------------------------------------
   BASIC USER / COMPANY DATA
----------------------------------------------------------- */
$profile_stmt = $conn->prepare("
    SELECT a.*, c.title AS course_title, c.start_date, c.end_date, c.points AS course_points, c.venue
    FROM cpd_applications a
    LEFT JOIN courses c ON c.id = a.course_id
    WHERE a.email = ?
      AND a.status = 'Approved'
    ORDER BY a.created_at DESC
    LIMIT 1
");
$profile_stmt->bind_param("s", $user_email);
$profile_stmt->execute();
$profile_res = $profile_stmt->get_result();
$profile     = ($profile_res && $profile_res->num_rows > 0) ? $profile_res->fetch_assoc() : null;
$profile_stmt->close();

$company_name = $profile['company_name'] ?? $user_name;
$discipline   = $profile['discipline'] ?? 'Not provided';
$position     = $profile['position'] ?? 'Contractor';
$phone        = $profile['phone'] ?? '';
$focus_area   = !empty($profile['qualification_name'])
    ? $profile['qualification_name']
    : ($profile['discipline'] ?? 'Not specified');

/* -----------------------------------------------------------
   CPD POINTS
----------------------------------------------------------- */
$points_stmt = $conn->prepare("
    SELECT COALESCE(SUM(points), 0) AS total_points
    FROM cpd_points_ledger
    WHERE user_id = ?
");
$points_stmt->bind_param("i", $user_id);
$points_stmt->execute();
$points_res = $points_stmt->get_result();
$points     = 0;
if ($points_res && $row = $points_res->fetch_assoc()) {
    $points = (float)$row['total_points'];
}
$points_stmt->close();

$target_points    = max(0, (int) eca_env('ECA_CPD_TARGET_POINTS', '0'));
$progress_percent = ($target_points > 0) ? min(100, round(($points / $target_points) * 100, 1)) : 0;
$remaining_points = max(0, $target_points - $points);

/* -----------------------------------------------------------
   CURRENT / RECENT COURSES
----------------------------------------------------------- */
$course_list_stmt = $conn->prepare("
    SELECT c.*, a.training_status, a.certificate_number, a.created_at AS app_date
    FROM cpd_applications a
    INNER JOIN courses c ON c.id = a.course_id
    WHERE a.email = ?
      AND a.status = 'Approved'
    ORDER BY c.start_date DESC, a.created_at DESC
    LIMIT 6
");
$course_list_stmt->bind_param("s", $user_email);
$course_list_stmt->execute();
$course_list_res = $course_list_stmt->get_result();

$course_titles = [];
$activities    = [];

if ($course_list_res && $course_list_res->num_rows > 0) {
    while ($r = $course_list_res->fetch_assoc()) {
        $course_titles[] = $r;
        $date_label = !empty($r['start_date']) ? date('F Y', strtotime($r['start_date'])) : date('F Y', strtotime($r['app_date']));
        $activities[] = [
            'title' => $r['title'],
            'date'  => $date_label
        ];
    }
}
$course_list_stmt->close();

$current_course = $course_titles[0]['title'] ?? 'No approved course yet';
$latest_cert    = 'No certificate issued';
foreach ($course_titles as $ct) {
    if (!empty($ct['certificate_number']) || strtoupper((string)($ct['training_status'] ?? '')) === 'COMPLETED') {
        $latest_cert = $ct['title'];
        break;
    }
}

/* -----------------------------------------------------------
   PIE / CATEGORY SUMMARY
----------------------------------------------------------- */
$pie_labels = ['Contract Training', 'Safety Management', 'Tendering Fundamentals', 'Quality Mgmt'];
$pie_values = [35, 25, 20, 20];

if (count($course_titles) > 0) {
    $category_map = [
        'Contract Training'        => 0,
        'Safety Management'        => 0,
        'Tendering Fundamentals'   => 0,
        'Quality Mgmt'             => 0
    ];

    foreach ($course_titles as $ct) {
        $title = strtolower($ct['title'] ?? '');
        if (strpos($title, 'contract') !== false) {
            $category_map['Contract Training']++;
        } elseif (strpos($title, 'safety') !== false) {
            $category_map['Safety Management']++;
        } elseif (strpos($title, 'tender') !== false) {
            $category_map['Tendering Fundamentals']++;
        } elseif (strpos($title, 'quality') !== false) {
            $category_map['Quality Mgmt']++;
        }
    }

    $sum_categories = array_sum($category_map);
    if ($sum_categories > 0) {
        $pie_labels = array_keys($category_map);
        $pie_values = [];
        foreach ($category_map as $val) {
            $pie_values[] = round(($val / $sum_categories) * 100);
        }

        // Adjust total to 100
        $diff = 100 - array_sum($pie_values);
        if (isset($pie_values[0])) {
            $pie_values[0] += $diff;
        }
    }
}

 $logged_email = $_SESSION['email'] ?? $email ?? '';

$employees = [];

if (!empty($logged_email)) {
    $stmt = $conn->prepare("
        SELECT 
            a.full_name,
            a.company_name,
            a.position,
            a.email,
            GROUP_CONCAT(DISTINCT c.title ORDER BY c.start_date DESC SEPARATOR ', ') AS courses_attended,
            COALESCE(SUM(DISTINCT l.points), 0) AS employee_points
        FROM cpd_applications a
        LEFT JOIN courses c ON c.id = a.course_id
        LEFT JOIN user u ON u.email = a.email
        LEFT JOIN cpd_points_ledger l ON l.user_id = u.id
        WHERE a.email = ?
          AND a.status = 'Approved'
        GROUP BY a.email, a.full_name, a.company_name, a.position
        ORDER BY a.full_name ASC
        LIMIT 1
    ");
    $stmt->bind_param("s", $logged_email);
    $stmt->execute();
    $employees_res = $stmt->get_result();

    if ($employees_res && $employees_res->num_rows > 0) {
        while ($emp = $employees_res->fetch_assoc()) {
            $employees[] = $emp;
        }
    }
    $stmt->close();
}

if (empty($employees)) {
    $employees = [
        [
            'full_name' => $user_name ?? '',
            'company_name' => $company_name ?? '',
            'position' => $position ?? '',
            'email' => $logged_email,
            'employee_points' => $points ?? 0,
            'courses_attended' => $current_course ?? 'No course attended yet'
        ]
    ];
}
/* -----------------------------------------------------------
   OTHER COUNTS
----------------------------------------------------------- */
$active_courses_count = count($course_titles);

$upcoming_sql = "
    SELECT COUNT(*) AS total_upcoming
    FROM courses
    WHERE start_date > NOW()
";
$upcoming_res = $conn->query($upcoming_sql);
$upcoming_count = 0;
if ($upcoming_res && $urow = $upcoming_res->fetch_assoc()) {
    $upcoming_count = (int)$urow['total_upcoming'];
}

/* -----------------------------------------------------------
   FINANCIAL YEAR
----------------------------------------------------------- */
$current_year = (int)date('Y');
$current_month = (int)date('n');
if ($current_month >= 4) {
    $financial_year = $current_year . '/' . ($current_year + 1);
} else {
    $financial_year = ($current_year - 1) . '/' . $current_year;
}

$generated_count = 0;
foreach ($course_titles as $ct) {
    if (!empty($ct['certificate_number'])) {
        $generated_count++;
    }
}

require_once __DIR__ . '/../../../includes/hub.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>CPD Point System Dashboard</title>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <?php eca_hub_assets(); ?>
</head>
<body class="hub-root">
<div class="hub-page">
    <div class="hub-shell">
        <header class="hub-topbar">
            <a class="hub-brand" href="/index.php">
                <img src="/img/ecalogo.png" alt="Eswatini Contractors Association">
            </a>
            <h1 class="hub-title">Student Hub</h1>
            <div class="hub-top-actions">
                <button class="btn btn-light mobile-toggle" type="button" onclick="toggleSidebar()" aria-label="Menu"><i class="fa fa-bars"></i></button>
                <a class="hub-home-link" href="/index.php">Home</a>
                <?php eca_origin_dashboard_back('top'); ?>
                <div class="hub-chip">
                    <span class="hub-avatar-sm"><i class="fa-solid fa-user"></i></span>
                    Contractor Member
                </div>
            </div>
        </header>
        <div class="hub-layout">
            <aside class="hub-sidebar" id="sidebar">
                <div class="hub-welcome">
                    <div class="hub-avatar"><?= htmlspecialchars(eca_hub_initial($user_name)) ?></div>
                    <div>
                        <p class="hub-welcome-label">Welcome,</p>
                        <p class="hub-welcome-name"><?= htmlspecialchars($user_name) ?></p>
                        <p class="hub-welcome-role"><?= htmlspecialchars($position) ?></p>
                    </div>
                </div>
                <nav class="hub-nav">
                    <a class="is-active" href="/learner-portal.php">Learner Portal</a>
                    <a href="dashboard.php">Dashboard</a>
                    <a href="my_courses.php">My Courses</a>
                    <a href="certificates.php">My Certificates<?php if ($generated_count): ?><span class="hub-badge"><?= (int) $generated_count ?></span><?php endif; ?></a>
                    <a href="resources.php">Resource Library</a>
                    <a href="events.php">Events</a>
                    <a href="points_tracker.php">CPD Tracker</a>
                    <a href="feedback.php">Feedback</a>
                    <?php eca_origin_dashboard_back('item'); ?>
                    <a href="/index.php">Home</a>
                    <a href="logout.php">Logout</a>
                </nav>
            </aside>
            <main class="hub-main">
                <div class="hub-hello">
                    <h1 class="hub-hello-title"><?= htmlspecialchars(eca_hub_hello($user_name)) ?></h1>
                    <p>Keep learning, keep growing. Build your future with ECA.</p>
                </div>

                <div class="hub-stats">
                    <a class="hub-stat" href="my_courses.php">
                        <div class="hub-stat-label">My Courses</div>
                        <div class="hub-stat-value"><?= (int) $active_courses_count ?></div>
                        <div class="hub-stat-meta">In Progress</div>
                        <div class="hub-stat-link">View all</div>
                    </a>
                    <a class="hub-stat" href="points_tracker.php">
                        <div class="hub-stat-label">Learning Progress</div>
                        <div class="hub-stat-value"><?= htmlspecialchars((string) $progress_percent) ?>%</div>
                        <div class="hub-stat-meta">Overall Progress</div>
                        <div class="hub-stat-link">View Details</div>
                    </a>
                    <a class="hub-stat" href="certificates.php">
                        <div class="hub-stat-label">Certificates Earned</div>
                        <div class="hub-stat-value"><?= (int) $generated_count ?></div>
                        <div class="hub-stat-link">View all</div>
                    </a>
                    <a class="hub-stat" href="events.php">
                        <div class="hub-stat-label">Upcoming Training</div>
                        <div class="hub-stat-value"><?= (int) $upcoming_count ?></div>
                        <div class="hub-stat-meta">Registered</div>
                        <div class="hub-stat-link">View Calendar</div>
                    </a>
                </div>

                <div class="hub-split">
                    <section class="hub-card">
                        <div class="hub-card-head">
                            <h2>My Current Courses</h2>
                            <a class="hub-stat-link" href="my_courses.php">View all</a>
                        </div>
                        <?php if ($course_titles): ?>
                            <?php foreach ($course_titles as $ct): ?>
                                <div class="hub-course">
                                    <div style="flex:1;min-width:0;">
                                        <h3><?= htmlspecialchars($ct['title'] ?? '') ?></h3>
                                        <p>
                                            <?php
                                            $meta = [];
                                            if (!empty($ct['venue'])) {
                                                $meta[] = $ct['venue'];
                                            }
                                            if (!empty($ct['start_date'])) {
                                                $meta[] = date('d M Y', strtotime($ct['start_date']));
                                            }
                                            if (!empty($ct['training_status'])) {
                                                $meta[] = $ct['training_status'];
                                            }
                                            echo htmlspecialchars(implode(' · ', $meta));
                                            ?>
                                        </p>
                                        <?php if (strtoupper((string) ($ct['training_status'] ?? '')) === 'COMPLETED'): ?>
                                            <div class="hub-progress"><div class="hub-progress-bar" style="width:100%;"></div></div>
                                        <?php endif; ?>
                                    </div>
                                    <a class="hub-btn-navy" href="my_courses.php">Continue</a>
                                </div>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <p class="hub-sub">No approved courses on file yet.</p>
                        <?php endif; ?>
                    </section>

                    <div>
                        <section class="hub-card" style="margin-bottom:16px;">
                            <div class="hub-card-head">
                                <h2>Upcoming Training</h2>
                                <a class="hub-stat-link" href="events.php">View all</a>
                            </div>
                            <p class="hub-sub" style="margin-bottom:8px;"><?= (int) $upcoming_count ?> upcoming course<?= (int) $upcoming_count === 1 ? '' : 's' ?> in the calendar.</p>
                            <?php
                            $shown_upcoming = 0;
                            foreach ($course_titles as $ct) {
                                if (empty($ct['start_date']) || strtotime($ct['start_date']) <= time()) {
                                    continue;
                                }
                                $shown_upcoming++;
                                ?>
                                <div class="hub-event">
                                    <div class="hub-date">
                                        <strong><?= date('d', strtotime($ct['start_date'])) ?></strong>
                                        <span><?= date('M', strtotime($ct['start_date'])) ?></span>
                                    </div>
                                    <div style="flex:1;">
                                        <h3 style="margin:0;font-size:15px;"><?= htmlspecialchars($ct['title'] ?? '') ?></h3>
                                        <p class="hub-sub" style="margin:0;"><?= htmlspecialchars($ct['venue'] ?? '') ?></p>
                                    </div>
                                    <a class="hub-btn" href="events.php">View</a>
                                </div>
                                <?php
                            }
                            if ($shown_upcoming === 0): ?>
                                <p class="hub-sub" style="margin:0;">Open the calendar for scheduled training.</p>
                            <?php endif; ?>
                        </section>

                        <section class="hub-card">
                            <div class="hub-card-head">
                                <h2>CPD Progress</h2>
                                <a class="hub-stat-link" href="points_tracker.php">View all</a>
                            </div>
                            <h3 style="margin:0 0 6px;font-size:16px;"><?= htmlspecialchars($current_course) ?></h3>
                            <p class="hub-sub">
                                <?php if ($target_points > 0): ?>
                                    <?= number_format($points, 0) ?> / <?= (int) $target_points ?> points · <?= htmlspecialchars((string) $progress_percent) ?>% complete
                                <?php else: ?>
                                    <?= number_format($points, 0) ?> recorded CPD points
                                <?php endif; ?>
                            </p>
                            <div class="hub-progress" style="margin-bottom:12px;"><div class="hub-progress-bar" style="width:<?= htmlspecialchars((string) $progress_percent) ?>%;"></div></div>
                            <p class="hub-sub" style="margin:0;">Latest certificate: <?= htmlspecialchars($latest_cert) ?></p>
                        </section>
                    </div>
                </div>

                <div class="hub-tiles">
                    <a class="hub-tile" href="resources.php">
                        <h3>Resources Library</h3>
                        <p>Access guides, templates, tools and more.</p>
                        <span>Explore →</span>
                    </a>
                    <a class="hub-tile hub-tile-sand" href="certificates.php">
                        <h3>My Certificates</h3>
                        <p>Download and share your certificates.</p>
                        <span>Explore →</span>
                    </a>
                    <a class="hub-tile hub-tile-lilac" href="events.php">
                        <h3>Training Calendar</h3>
                        <p>See upcoming courses and events.</p>
                        <span>Explore →</span>
                    </a>
                    <a class="hub-tile hub-tile-mint" href="feedback.php">
                        <h3>Feedback</h3>
                        <p>Share comments on completed training.</p>
                        <span>Explore →</span>
                    </a>
                </div>

                <section class="hub-card" style="margin-top:18px;">
                    <div class="hub-card-head">
                        <h2>Member company</h2>
                    </div>
                    <p style="margin:0 0 6px;font-weight:800;"><?= htmlspecialchars($company_name) ?></p>
                    <p class="hub-sub" style="margin:0;"><?= htmlspecialchars($discipline) ?> · Financial year <?= htmlspecialchars($financial_year) ?> · <?= htmlspecialchars($focus_area) ?></p>
                </section>

                <div class="hub-split" style="margin-top:18px;">
                    <section class="hub-card">
                        <div class="hub-card-head"><h2>CPD Summary</h2></div>
                        <?php
                        $c1 = max(0, (float)$pie_values[0]);
                        $c2 = max(0, (float)$pie_values[1]);
                        $c3 = max(0, (float)$pie_values[2]);
                        $c4 = max(0, (float)$pie_values[3]);
                        $s1 = $c1;
                        $s2 = $c1 + $c2;
                        $s3 = $c1 + $c2 + $c3;
                        $s4 = $c1 + $c2 + $c3 + $c4;
                        ?>
                        <div style="display:flex;gap:18px;flex-wrap:wrap;align-items:center;">
                            <div style="width:140px;height:140px;border-radius:50%;background:conic-gradient(#ef9422 0% <?= $s1 ?>%, #2f67b1 <?= $s1 ?>% <?= $s2 ?>%, #2f974d <?= $s2 ?>% <?= $s3 ?>%, #f0b323 <?= $s3 ?>% <?= $s4 ?>%);"></div>
                            <div>
                                <p class="hub-sub"><?= htmlspecialchars($pie_labels[0]) ?></p>
                                <p class="hub-sub"><?= htmlspecialchars($pie_labels[1]) ?></p>
                                <p class="hub-sub"><?= htmlspecialchars($pie_labels[2]) ?></p>
                                <p class="hub-sub"><?= htmlspecialchars($pie_labels[3]) ?></p>
                                <p style="margin:8px 0 0;font-weight:800;"><?= number_format($points, 0) ?> points earned · <?= number_format($remaining_points, 0) ?> remaining</p>
                            </div>
                        </div>
                    </section>
                    <section class="hub-card">
                        <div class="hub-card-head"><h2>CPD Activities</h2></div>
                        <?php if (!empty($activities)): ?>
                            <?php foreach (array_slice($activities, 0, 3) as $act): ?>
                                <div class="hub-event">
                                    <div>
                                        <h3 style="margin:0;font-size:15px;"><?= htmlspecialchars($act['title']) ?></h3>
                                        <p class="hub-sub" style="margin:0;"><?= htmlspecialchars($act['date']) ?></p>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <p class="hub-sub" style="margin:0;">No CPD activities on file yet.</p>
                        <?php endif; ?>
                    </section>
                </div>

                <section class="hub-card eca-table-panel" style="margin-top:18px;">
                    <div class="hub-card-head"><h2>Courses Overview</h2></div>
                    <table class="hub-table">
                        <thead>
                            <tr>
                                <th>Name</th>
                                <th>Company</th>
                                <th>Position</th>
                                <th>Points</th>
                                <th>Courses Attended</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($employees as $emp): ?>
                                <tr>
                                    <td><?= htmlspecialchars($emp['full_name'] ?? '-') ?></td>
                                    <td><?= htmlspecialchars($emp['company_name'] ?? '-') ?></td>
                                    <td><?= htmlspecialchars($emp['position'] ?? '-') ?></td>
                                    <td><?= number_format((float)($emp['employee_points'] ?? 0), 0) ?> Points</td>
                                    <td><?= htmlspecialchars($emp['courses_attended'] ?? '-') ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </section>
            </main>
        </div>
    </div>
</div>
<script>
function toggleSidebar() {
    document.body.classList.toggle('sidebar-open');
    const sidebar = document.getElementById('sidebar');
    if (sidebar) sidebar.classList.toggle('show');
}
</script>
</body>
</html>
