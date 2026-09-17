<?php
require_once "../auth.php";
require_role('ADMIN');
require_once "../config.php";

if (!function_exists('e')) {
    function e($v) {
        return htmlspecialchars((string)($v ?? ''), ENT_QUOTES, 'UTF-8');
    }
}

/* =========================
   HELPERS
========================= */
function table_exists(mysqli $conn, string $table): bool {
    $table = $conn->real_escape_string($table);
    $res = $conn->query("SHOW TABLES LIKE '{$table}'");
    return $res && $res->num_rows > 0;
}

function column_exists(mysqli $conn, string $table, string $column): bool {
    $table = $conn->real_escape_string($table);
    $column = $conn->real_escape_string($column);
    $res = $conn->query("SHOW COLUMNS FROM `{$table}` LIKE '{$column}'");
    return $res && $res->num_rows > 0;
}

function fetch_value(mysqli $conn, string $sql, $default = 0) {
    $res = $conn->query($sql);
    if ($res && $row = $res->fetch_row()) {
        return $row[0] ?? $default;
    }
    return $default;
}

function fetch_all_assoc($result): array {
    $rows = [];
    if ($result instanceof mysqli_result) {
        while ($row = $result->fetch_assoc()) {
            $rows[] = $row;
        }
    }
    return $rows;
}

function rating_label(float $rating): string {
    if ($rating >= 4.5) return 'Excellent';
    if ($rating >= 4.0) return 'Very Good';
    if ($rating >= 3.0) return 'Good';
    if ($rating >= 2.0) return 'Fair';
    return 'Needs Improvement';
}

function percent($num, $den): float {
    return ($den > 0) ? round(($num / $den) * 100, 1) : 0.0;
}

function esc_sql_date(mysqli $conn, string $date): string {
    return $conn->real_escape_string($date);
}

function build_date_where(mysqli $conn, string $column, string $from, string $to): string {
    $where = [];
    if ($from !== '') {
        $from = esc_sql_date($conn, $from);
        $where[] = "$column >= '{$from} 00:00:00'";
    }
    if ($to !== '') {
        $to = esc_sql_date($conn, $to);
        $where[] = "$column <= '{$to} 23:59:59'";
    }
    return $where ? (' AND ' . implode(' AND ', $where)) : '';
}

function badge_class_by_score(float $value): string {
    if ($value >= 80) return 'badge-success-soft';
    if ($value >= 50) return 'badge-warning-soft';
    return 'badge-danger-soft';
}

function csv_escape_number($value): string {
    return number_format((float)$value, 2, '.', '');
}

/* =========================
   FILTERS
========================= */
$from   = trim($_GET['from'] ?? '');
$to     = trim($_GET['to'] ?? '');
$export = trim($_GET['export'] ?? '');

if ($from !== '' && !preg_match('/^\d{4}-\d{2}-\d{2}$/', $from)) $from = '';
if ($to !== '' && !preg_match('/^\d{4}-\d{2}-\d{2}$/', $to)) $to = '';

$dateColCourses = column_exists($conn, 'courses', 'start_date') ? 'start_date' : (column_exists($conn, 'courses', 'created_at') ? 'created_at' : 'id');
$dateColApps    = column_exists($conn, 'cpd_applications', 'created_at') ? 'created_at' : 'id';
$dateColLedger  = column_exists($conn, 'cpd_points_ledger', 'issued_at') ? 'issued_at' : (column_exists($conn, 'cpd_points_ledger', 'created_at') ? 'created_at' : 'created_at');

$courseFilter = build_date_where($conn, "c.$dateColCourses", $from, $to);
$appFilter    = build_date_where($conn, "a.$dateColApps", $from, $to);
$ledgerFilter = build_date_where($conn, "l.$dateColLedger", $from, $to);

/* =========================
   FEEDBACK TABLE DETECTION
========================= */
$feedbackTable = null;
foreach (['course_feedback', 'feedback', 'cpd_feedback'] as $tbl) {
    if (table_exists($conn, $tbl)) {
        $feedbackTable = $tbl;
        break;
    }
}

$feedbackDateCol = null;
if ($feedbackTable) {
    foreach (['created_at', 'submitted_at', 'date_added', 'date'] as $fc) {
        if (column_exists($conn, $feedbackTable, $fc)) {
            $feedbackDateCol = $fc;
            break;
        }
    }
}
$feedbackFilter = ($feedbackTable && $feedbackDateCol) ? build_date_where($conn, "f.$feedbackDateCol", $from, $to) : '';

/* =========================
   DETECT SAFE LEDGER JOIN
========================= */
$ledgerJoinOnApplication = false;

if (column_exists($conn, 'cpd_points_ledger', 'user_id')) {
    $checkMap = $conn->query("
        SELECT COUNT(*) AS c
        FROM cpd_points_ledger l
        INNER JOIN cpd_applications a ON a.id = l.user_id
    ");
    if ($checkMap && ($mapRow = $checkMap->fetch_assoc()) && (int)$mapRow['c'] > 0) {
        $ledgerJoinOnApplication = true;
    }
}

$ledgerJoinCompany = $ledgerJoinOnApplication
    ? "LEFT JOIN cpd_points_ledger l ON l.course_id = a.course_id AND l.user_id = a.id"
    : "LEFT JOIN cpd_points_ledger l ON l.course_id = a.course_id";

$ledgerJoinParticipant = $ledgerJoinOnApplication
    ? "LEFT JOIN cpd_points_ledger l ON l.course_id = a.course_id AND l.user_id = a.id"
    : "LEFT JOIN cpd_points_ledger l ON l.course_id = a.course_id";

/* =========================
   KPI DATA
========================= */
$total_trainings = (int) fetch_value($conn, "
    SELECT COUNT(*)
    FROM courses c
    WHERE 1=1 $courseFilter
");

$total_participants = (int) fetch_value($conn, "
    SELECT COUNT(*)
    FROM cpd_applications a
    WHERE a.status IN ('Approved','APPROVED','Completed','COMPLETED') $appFilter
");

$total_companies = (int) fetch_value($conn, "
    SELECT COUNT(DISTINCT TRIM(a.company_name))
    FROM cpd_applications a
    WHERE COALESCE(TRIM(a.company_name),'') <> '' $appFilter
");

$total_points = (float) fetch_value($conn, "
    SELECT COALESCE(SUM(l.points),0)
    FROM cpd_points_ledger l
    WHERE 1=1 $ledgerFilter
", 0);

$total_completed = (int) fetch_value($conn, "
    SELECT COUNT(*)
    FROM cpd_applications a
    WHERE a.training_status IN ('Completed','COMPLETED') $appFilter
");

$completion_rate = percent($total_completed, max($total_participants, 1));
$avg_points_per_participant = $total_participants > 0 ? round($total_points / $total_participants, 2) : 0;

/* =========================
   COMPANY RANKING
========================= */
$company_rankings_sql = "
    SELECT 
        a.company_name,
        COUNT(DISTINCT a.id) AS participants,
        COUNT(DISTINCT a.course_id) AS courses_attended,
        COALESCE(SUM(l.points),0) AS total_points,
        COALESCE(AVG(l.points),0) AS avg_points
    FROM cpd_applications a
    $ledgerJoinCompany
    WHERE COALESCE(TRIM(a.company_name),'') <> ''
      AND a.status IN ('Approved','APPROVED','Completed','COMPLETED')
      $appFilter
    GROUP BY a.company_name
    ORDER BY total_points DESC, participants DESC, a.company_name ASC
";
$company_rankings = fetch_all_assoc($conn->query($company_rankings_sql));
$top_company_points = !empty($company_rankings) ? (float)$company_rankings[0]['total_points'] : 0;

/* =========================
   COURSE PERFORMANCE
========================= */
$course_performance_sql = "
    SELECT 
        c.id,
        c.title,
        c.venue,
        c.start_date,
        c.end_date,
        COALESCE(c.points,0) AS course_points,
        COUNT(DISTINCT a.id) AS participants,
        COUNT(DISTINCT CASE WHEN a.training_status IN ('Completed','COMPLETED') THEN a.id END) AS completed,
        COALESCE(SUM(DISTINCT CASE WHEN l.id IS NOT NULL THEN l.points END),0) AS issued_points
    FROM courses c
    LEFT JOIN cpd_applications a ON a.course_id = c.id
    LEFT JOIN cpd_points_ledger l ON l.course_id = c.id
    WHERE 1=1 $courseFilter
    GROUP BY c.id, c.title, c.venue, c.start_date, c.end_date, c.points
    ORDER BY c.start_date DESC, c.id DESC
";
$course_rows = fetch_all_assoc($conn->query($course_performance_sql));

/* =========================
   PARTICIPANTS
========================= */
$participantOrderCol = column_exists($conn, 'cpd_applications', 'created_at') ? 'a.created_at' : 'a.id';

$participant_details_sql = "
    SELECT 
        a.id,
        a.full_name,
        a.company_name,
        a.position,
        a.discipline,
        a.qualification_level,
        a.qualification_name,
        a.gender,
        c.title AS course_title,
        c.points AS course_points,
        a.training_status,
        COALESCE(SUM(l.points),0) AS earned_points
    FROM cpd_applications a
    LEFT JOIN courses c ON c.id = a.course_id
    $ledgerJoinParticipant
    WHERE a.status IN ('Approved','APPROVED','Completed','COMPLETED')
      $appFilter
    GROUP BY a.id, a.full_name, a.company_name, a.position, a.discipline, a.qualification_level, a.qualification_name, a.gender, c.title, c.points, a.training_status
    ORDER BY $participantOrderCol DESC, a.full_name ASC
";
$participant_rows = fetch_all_assoc($conn->query($participant_details_sql));

/* =========================
   JOB TITLES / DISCIPLINE / GENDER
========================= */
$job_title_sql = "
    SELECT 
        COALESCE(NULLIF(TRIM(a.position),''), 'Not Specified') AS position,
        COUNT(*) AS total
    FROM cpd_applications a
    WHERE a.status IN ('Approved','APPROVED','Completed','COMPLETED')
      $appFilter
    GROUP BY position
    ORDER BY total DESC, position ASC
    LIMIT 10
";
$job_title_rows = fetch_all_assoc($conn->query($job_title_sql));

$discipline_sql = "
    SELECT 
        COALESCE(NULLIF(TRIM(a.discipline),''), 'Not Specified') AS discipline,
        COUNT(*) AS total
    FROM cpd_applications a
    WHERE a.status IN ('Approved','APPROVED','Completed','COMPLETED')
      $appFilter
    GROUP BY discipline
    ORDER BY total DESC, discipline ASC
    LIMIT 10
";
$discipline_rows = fetch_all_assoc($conn->query($discipline_sql));

$gender_sql = "
    SELECT 
        COALESCE(NULLIF(TRIM(a.gender),''), 'Not Specified') AS gender,
        COUNT(*) AS total
    FROM cpd_applications a
    WHERE a.status IN ('Approved','APPROVED','Completed','COMPLETED')
      $appFilter
    GROUP BY gender
    ORDER BY total DESC
";
$gender_rows = fetch_all_assoc($conn->query($gender_sql));

/* =========================
   LEDGER
========================= */
$ledger_rows = fetch_all_assoc($conn->query("
    SELECT 
        l.id,
        " . (column_exists($conn, 'cpd_points_ledger', 'user_id') ? "l.user_id," : "NULL AS user_id,") . "
        l.course_id,
        l.points,
        " . (column_exists($conn, 'cpd_points_ledger', 'reason') ? "l.reason," : "'' AS reason,") . "
        " . (column_exists($conn, 'cpd_points_ledger', 'description') ? "l.description," : "'' AS description,") . "
        l.$dateColLedger AS issued_on,
        c.title AS course_title
    FROM cpd_points_ledger l
    LEFT JOIN courses c ON c.id = l.course_id
    WHERE 1=1 $ledgerFilter
    ORDER BY l.$dateColLedger DESC, l.id DESC
"));

/* =========================
   FEEDBACK DATA
========================= */
$feedback_overview = [
    'avg_rating' => 0,
    'total_reviews' => 0,
    'positive_rate' => 0,
    'top_comments' => []
];
$feedback_course_rows = [];

if ($feedbackTable) {
    $hasRating = column_exists($conn, $feedbackTable, 'rating');
    $commentCol = null;

    foreach (['comment', 'comments', 'feedback'] as $candidate) {
        if (column_exists($conn, $feedbackTable, $candidate)) {
            $commentCol = $candidate;
            break;
        }
    }

    $hasCourseId = column_exists($conn, $feedbackTable, 'course_id');

    if ($hasRating) {
        $feedback_overview['avg_rating'] = (float) fetch_value($conn, "
            SELECT COALESCE(AVG(f.rating),0)
            FROM {$feedbackTable} f
            WHERE 1=1 $feedbackFilter
        ", 0);

        $feedback_overview['total_reviews'] = (int) fetch_value($conn, "
            SELECT COUNT(*)
            FROM {$feedbackTable} f
            WHERE 1=1 $feedbackFilter
        ", 0);

        $positive = (int) fetch_value($conn, "
            SELECT COUNT(*)
            FROM {$feedbackTable} f
            WHERE f.rating >= 4 $feedbackFilter
        ", 0);

        $feedback_overview['positive_rate'] = percent($positive, max($feedback_overview['total_reviews'], 1));
    }

    if ($hasCourseId && $hasRating) {
        $feedback_course_rows = fetch_all_assoc($conn->query("
            SELECT 
                c.title,
                COUNT(f.id) AS total_reviews,
                ROUND(AVG(f.rating),2) AS avg_rating
            FROM {$feedbackTable} f
            LEFT JOIN courses c ON c.id = f.course_id
            WHERE 1=1 $feedbackFilter
            GROUP BY f.course_id, c.title
            ORDER BY avg_rating DESC, total_reviews DESC
        "));
    }

    if ($commentCol) {
        $orderCol = $feedbackDateCol ? "f.$feedbackDateCol DESC" : "f.id DESC";
        $commentsRes = $conn->query("
            SELECT $commentCol AS comment_text
            FROM {$feedbackTable} f
            WHERE COALESCE(TRIM($commentCol),'') <> ''
            $feedbackFilter
            ORDER BY $orderCol
            LIMIT 6
        ");
        if ($commentsRes instanceof mysqli_result) {
            while ($r = $commentsRes->fetch_assoc()) {
                $feedback_overview['top_comments'][] = $r['comment_text'];
            }
        }
    }
}

/* =========================
   AI INSIGHTS
========================= */
$ai_insights = [];

if ($total_trainings > 0) {
    $avg_participants_per_training = round($total_participants / max($total_trainings, 1), 1);
    $ai_insights[] = "Average participation is {$avg_participants_per_training} learners per training within the selected reporting window.";
}

if ($completion_rate >= 80) {
    $ai_insights[] = "Completion performance is strong at {$completion_rate}%, indicating healthy learner engagement and training follow-through.";
} elseif ($completion_rate >= 50) {
    $ai_insights[] = "Completion performance is moderate at {$completion_rate}%. Attendance follow-up and activity reminders could improve outcomes.";
} else {
    $ai_insights[] = "Completion performance is low at {$completion_rate}%. Consider tighter attendance tracking, learner reminders, and clearer post-training requirements.";
}

if (!empty($company_rankings)) {
    $top = $company_rankings[0];
    $ai_insights[] = $top['company_name'] . " leads the company ranking with " . number_format((float)$top['total_points'], 2) . " CPD points across " . (int)$top['participants'] . " participant(s).";
}

if (!empty($job_title_rows)) {
    $topRole = $job_title_rows[0];
    $ai_insights[] = "The most represented job title is {$topRole['position']} with {$topRole['total']} participant(s), which can help target future course design.";
}

if ($feedback_overview['total_reviews'] > 0) {
    $ai_insights[] = "Feedback sentiment is " . rating_label((float)$feedback_overview['avg_rating']) . " with an average rating of " . number_format((float)$feedback_overview['avg_rating'], 2) . "/5 and a positive review rate of {$feedback_overview['positive_rate']}%.";
} else {
    $ai_insights[] = "No structured feedback records were found for the selected period, so feedback insights are currently limited to operational participation metrics.";
}

$ai_recommendations = [
    'Introduce automated reminders for incomplete learners before course closeout.',
    'Prioritize outreach to low-participation companies using targeted course recommendations.',
    'Use top job-title and discipline trends to design role-specific CPD pathways.',
    'Track feedback consistently after every course to strengthen quality assurance and regulatory reporting.'
];

/* =========================
   EXPORTS
========================= */
if ($export === 'csv_company') {
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename=cpd_company_ranking_' . date('Ymd_His') . '.csv');
    $out = fopen('php://output', 'w');
    fputcsv($out, ['Rank', 'Company', 'Participants', 'Courses Attended', 'Total Points', 'Average Points']);
    foreach ($company_rankings as $i => $r) {
        fputcsv($out, [
            $i + 1,
            $r['company_name'],
            $r['participants'],
            $r['courses_attended'],
            csv_escape_number($r['total_points']),
            csv_escape_number($r['avg_points'])
        ]);
    }
    fclose($out);
    exit;
}

if ($export === 'csv_courses') {
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename=cpd_course_performance_' . date('Ymd_His') . '.csv');
    $out = fopen('php://output', 'w');
    fputcsv($out, ['Course', 'Venue', 'Start Date', 'End Date', 'Participants', 'Completed', 'Completion %', 'Course Points', 'Issued Points']);
    foreach ($course_rows as $r) {
        $comp = percent((float)$r['completed'], max((float)$r['participants'], 1));
        fputcsv($out, [
            $r['title'],
            $r['venue'],
            $r['start_date'],
            $r['end_date'],
            $r['participants'],
            $r['completed'],
            $comp,
            csv_escape_number($r['course_points']),
            csv_escape_number($r['issued_points'])
        ]);
    }
    fclose($out);
    exit;
}

if ($export === 'csv_participants') {
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename=cpd_participants_' . date('Ymd_His') . '.csv');
    $out = fopen('php://output', 'w');
    fputcsv($out, ['Full Name', 'Company', 'Job Title', 'Discipline', 'Qualification Level', 'Qualification Name', 'Gender', 'Course', 'Training Status', 'Earned Points']);
    foreach ($participant_rows as $r) {
        fputcsv($out, [
            $r['full_name'],
            $r['company_name'],
            $r['position'],
            $r['discipline'],
            $r['qualification_level'],
            $r['qualification_name'],
            $r['gender'],
            $r['course_title'],
            $r['training_status'],
            csv_escape_number($r['earned_points'])
        ]);
    }
    fclose($out);
    exit;
}

$page_title = 'CPD Points Report';
require_once "../header.php";
?>

<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script src="https://cdn.jsdelivr.net/npm/jspdf@2.5.1/dist/jspdf.umd.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/jspdf-autotable@3.8.2/dist/jspdf.plugin.autotable.min.js"></script>

<style>
:root{
    --brand:#0f172a;
    --brand-2:#143a78;
    --accent:#e3262e;
    --ink:#0f172a;
    --muted:#64748b;
    --line:#e2e8f0;
    --soft:#f8fafc;
    --card:#ffffff;
    --success:#16a34a;
    --warning:#d97706;
    --danger:#dc2626;
    --shadow:0 18px 45px rgba(15,23,42,.10);
    --radius:22px;
}
*{font-family:'Poppins',sans-serif;}
body{background:linear-gradient(180deg,#f8fbff 0%,#f5f7fb 100%); color:var(--ink);}
.report-shell{padding:18px 0 26px;}
.ultra-hero{
    position:relative;
    overflow:hidden;
    background:linear-gradient(135deg,var(--brand) 0%, var(--brand-2) 65%, #1d4ed8 100%);
    border-radius:28px;
    padding:28px;
    color:#fff;
    box-shadow:0 22px 60px rgba(15,23,42,.22);
    margin-bottom:18px;
}
.ultra-hero:before,
.ultra-hero:after{
    content:'';
    position:absolute;
    border-radius:999px;
    background:rgba(255,255,255,.08);
    filter:blur(3px);
}
.ultra-hero:before{width:280px;height:280px;right:-80px;top:-60px;}
.ultra-hero:after{width:200px;height:200px;left:-60px;bottom:-70px;}
.hero-grid{display:grid;grid-template-columns:1.35fr .95fr;gap:18px;position:relative;z-index:2;}
.hero-title{font-size:2rem;font-weight:900;line-height:1.1;margin:0 0 8px;letter-spacing:-.03em;}
.hero-sub{opacity:.92;max-width:760px;margin-bottom:16px;}
.hero-meta{display:flex;flex-wrap:wrap;gap:10px;}
.hero-pill{
    background:rgba(255,255,255,.12);
    color:#fff;
    border:1px solid rgba(255,255,255,.14);
    padding:9px 14px;
    border-radius:999px;
    font-size:.87rem;
    backdrop-filter:blur(10px);
}
.hero-side-card{
    background:rgba(255,255,255,.12);
    border:1px solid rgba(255,255,255,.14);
    border-radius:24px;
    padding:18px;
    backdrop-filter:blur(12px);
}
.hero-side-card .mini-label{font-size:.82rem;opacity:.88;}
.hero-side-card .mini-value{font-size:2rem;font-weight:900;line-height:1;margin:.4rem 0;}
.filter-card,.section-card,.kpi-card,.insight-card{
    background:var(--card);
    border:1px solid rgba(148,163,184,.16);
    border-radius:var(--radius);
    box-shadow:var(--shadow);
}
.filter-card{padding:18px;margin-bottom:18px;}
.section-card{padding:20px;margin-bottom:18px;}
.kpi-grid{display:grid;grid-template-columns:repeat(5,minmax(0,1fr));gap:14px;margin-bottom:18px;}
.kpi-card{padding:18px;position:relative;overflow:hidden;}
.kpi-card:before{
    content:'';
    position:absolute;
    inset:auto -35px -35px auto;
    width:120px;height:120px;border-radius:50%;
    background:linear-gradient(135deg,rgba(29,78,216,.06),rgba(227,38,46,.08));
}
.kpi-label{font-size:.82rem;color:var(--muted);font-weight:600;margin-bottom:8px;}
.kpi-value{font-size:1.9rem;font-weight:900;line-height:1.05;letter-spacing:-.03em;}
.kpi-foot{margin-top:8px;font-size:.82rem;color:var(--muted);}
.section-head{display:flex;justify-content:space-between;align-items:center;gap:12px;flex-wrap:wrap;margin-bottom:14px;}
.section-title{font-size:1.1rem;font-weight:900;margin:0;letter-spacing:-.02em;}
.section-sub{color:var(--muted);font-size:.92rem;}
.grid-2{display:grid;grid-template-columns:1.2fr .8fr;gap:18px;}
.grid-3{display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:16px;}
.table-wrap{overflow:auto;border-radius:18px;border:1px solid #eef2f7;}
.table-premium{width:100%;min-width:860px;border-collapse:separate;border-spacing:0;background:#fff;}
.table-premium thead th{
    background:linear-gradient(180deg,#0f172a,#172554);
    color:#fff;
    padding:13px 14px;
    font-size:.82rem;
    font-weight:700;
    border:none;
    white-space:nowrap;
}
.table-premium tbody td{padding:13px 14px;border-bottom:1px solid #eef2f7;vertical-align:middle;font-size:.92rem;}
.table-premium tbody tr:hover{background:#f8fbff;}
.rank-badge,.soft-badge{
    display:inline-flex;align-items:center;gap:6px;
    padding:6px 10px;border-radius:999px;font-size:.78rem;font-weight:700;
}
.rank-badge{background:#eff6ff;color:#1d4ed8;}
.soft-badge{background:#f8fafc;color:#334155;border:1px solid #e2e8f0;}
.badge-success-soft{background:#ecfdf5;color:#166534;}
.badge-warning-soft{background:#fffbeb;color:#b45309;}
.badge-danger-soft{background:#fef2f2;color:#b91c1c;}
.badge-brand-soft{background:#eff6ff;color:#1d4ed8;}
.chart-card{
    background:linear-gradient(180deg,#ffffff,#fbfdff);
    border:1px solid #edf2f7;
    border-radius:22px;
    padding:18px;
    min-height:380px;
}
.chart-box{position:relative;height:300px;}
.insight-card{padding:18px;height:100%;}
.insight-list{margin:0;padding-left:18px;color:#334155;}
.insight-list li{margin-bottom:10px;}
.ai-grid{display:grid;grid-template-columns:1fr 1fr;gap:18px;}
.comment-pill{
    border-left:4px solid #1d4ed8;
    background:#f8fbff;
    padding:12px 14px;
    border-radius:14px;
    margin-bottom:10px;
    color:#334155;
}
.toolbar{display:flex;gap:10px;flex-wrap:wrap;}
.btn-ultra{
    border:none;
    border-radius:14px;
    padding:11px 16px;
    font-weight:700;
    font-size:.92rem;
    text-decoration:none;
    display:inline-flex;
    align-items:center;
    gap:8px;
    transition:.2s ease;
}
.btn-ultra:hover{transform:translateY(-1px);}
.btn-brand{background:linear-gradient(135deg,#0f172a,#1d4ed8);color:#fff;}
.btn-soft{background:#eef4ff;color:#1d4ed8;}
.btn-danger-soft{background:#fff1f2;color:#be123c;}
.filter-grid{display:grid;grid-template-columns:repeat(4,minmax(0,1fr));gap:14px;align-items:end;}
.input-label{font-size:.84rem;font-weight:700;color:#334155;margin-bottom:6px;display:block;}
.input-premium,.select-premium{
    width:100%;
    border:1px solid #dbe3ef;
    background:#fff;
    border-radius:14px;
    padding:12px 14px;
    outline:none;
    box-shadow:none;
}
.input-premium:focus,.select-premium:focus{border-color:#93c5fd;box-shadow:0 0 0 4px rgba(59,130,246,.12);}
.brand-line{height:4px;width:74px;border-radius:999px;background:linear-gradient(90deg,#e3262e,#1d4ed8);margin-top:8px;}
.company-top-card{
    background:linear-gradient(135deg,#0f172a 0%, #173a74 100%);
    color:#fff;border-radius:22px;padding:18px;box-shadow:0 18px 40px rgba(15,23,42,.18);
}
.company-top-card .name{font-size:1.1rem;font-weight:900;}
.company-top-card .points{font-size:2rem;font-weight:900;}
.small-muted{font-size:.84rem;color:var(--muted);}
.pdf-note{font-size:.82rem;color:#64748b;margin-top:8px;}
@media print{
    .no-print, header, nav, .sidebar, .main-header, .main-sidebar, footer { display:none !important; }
    body{background:#fff !important;}
    .section-card,.filter-card,.kpi-card,.insight-card,.chart-card{box-shadow:none !important;border:1px solid #dbe3ef !important;}
    .ultra-hero{box-shadow:none !important;}
}
@media (max-width:1200px){
    .kpi-grid{grid-template-columns:repeat(3,minmax(0,1fr));}
    .grid-2,.hero-grid,.ai-grid{grid-template-columns:1fr;}
}
@media (max-width:768px){
    .kpi-grid,.grid-3,.filter-grid{grid-template-columns:1fr;}
    .hero-title{font-size:1.5rem;}
    .ultra-hero,.section-card,.filter-card{padding:16px;}
}
</style>

<div class="container-fluid report-shell">
    <div class="ultra-hero" id="reportHeader">
        <div class="hero-grid">
            <div>
                <div class="soft-badge" style="background:rgba(255,255,255,.14);color:#fff;border-color:rgba(255,255,255,.12);">Eswatini Contractors Association • CPD Report</div>
                <h1 class="hero-title">CPD Points Report & Professional Training Intelligence</h1>
                <p class="hero-sub">Ultra-premium reporting dashboard for trainings, courses, participant profiles, company performance, feedback overview, ranking insights, and export-ready compliance reporting.</p>
                <div class="hero-meta">
                    <div class="hero-pill">Date Filter: <?= e($from ?: 'All Dates') ?><?= $to ? ' to ' . e($to) : '' ?></div>
                    <div class="hero-pill">Generated: <?= date('d M Y H:i') ?></div>
                    <div class="hero-pill">Branding: ECA CPD</div>
                </div>
            </div>
            <div class="hero-side-card">
                <div class="mini-label">Report Headline Metric</div>
                <div class="mini-value"><?= number_format($total_points, 2) ?></div>
                <div>Total CPD Points Issued</div>
                <hr style="border-color:rgba(255,255,255,.15)">
                <div class="mini-label">Completion Rate</div>
                <div style="font-weight:800;font-size:1.35rem;"><?= number_format($completion_rate,1) ?>%</div>
            </div>
        </div>
    </div>

    <div class="filter-card no-print">
        <div class="section-head">
            <div>
                <h3 class="section-title">Filter & Export Center</h3>
                <div class="section-sub">Filter the report by date range, export datasets, or generate a branded PDF report.</div>
                <div class="brand-line"></div>
            </div>
            <div class="toolbar">
                <button type="button" class="btn-ultra btn-brand" onclick="downloadPDF()">📄 Export PDF</button>
                <a class="btn-ultra btn-soft" href="?from=<?= urlencode($from) ?>&to=<?= urlencode($to) ?>&export=csv_company">⬇ Company CSV</a>
                <a class="btn-ultra btn-soft" href="?from=<?= urlencode($from) ?>&to=<?= urlencode($to) ?>&export=csv_courses">⬇ Courses CSV</a>
                <a class="btn-ultra btn-soft" href="?from=<?= urlencode($from) ?>&to=<?= urlencode($to) ?>&export=csv_participants">⬇ Participants CSV</a>
                <button type="button" class="btn-ultra btn-danger-soft" onclick="window.print()">🖨 Print</button>
            </div>
        </div>

        <form method="get" class="filter-grid">
            <div>
                <label class="input-label">From Date</label>
                <input type="date" name="from" value="<?= e($from) ?>" class="input-premium">
            </div>
            <div>
                <label class="input-label">To Date</label>
                <input type="date" name="to" value="<?= e($to) ?>" class="input-premium">
            </div>
            <div>
                <label class="input-label">Quick Range</label>
                <select class="select-premium" onchange="applyQuickRange(this.value)">
                    <option value="">Select quick range</option>
                    <option value="30">Last 30 Days</option>
                    <option value="90">Last 90 Days</option>
                    <option value="180">Last 6 Months</option>
                    <option value="365">Last 12 Months</option>
                </select>
            </div>
            <div style="display:flex;gap:10px;align-items:end;">
                <button class="btn-ultra btn-brand" type="submit">Apply Filter</button>
                <a class="btn-ultra btn-soft" href="cpd_reports.php">Reset</a>
            </div>
        </form>
        <div class="pdf-note">PDF export uses a branded jsPDF report with ECA title, executive summary, company ranking, and course performance tables.</div>
    </div>

    <div class="kpi-grid">
        <div class="kpi-card">
            <div class="kpi-label">Total Trainings</div>
            <div class="kpi-value"><?= number_format($total_trainings) ?></div>
            <div class="kpi-foot">Courses within selected date range</div>
        </div>
        <div class="kpi-card">
            <div class="kpi-label">Total Participants</div>
            <div class="kpi-value"><?= number_format($total_participants) ?></div>
            <div class="kpi-foot">Approved / completed learners</div>
        </div>
        <div class="kpi-card">
            <div class="kpi-label">Total Companies</div>
            <div class="kpi-value"><?= number_format($total_companies) ?></div>
            <div class="kpi-foot">Distinct participating companies</div>
        </div>
        <div class="kpi-card">
            <div class="kpi-label">Total Points Issued</div>
            <div class="kpi-value"><?= number_format($total_points, 2) ?></div>
            <div class="kpi-foot">CPD points recorded in ledger</div>
        </div>
        <div class="kpi-card">
            <div class="kpi-label">Completion Rate</div>
            <div class="kpi-value"><?= number_format($completion_rate, 1) ?>%</div>
            <div class="kpi-foot">Completed vs total participants</div>
        </div>
    </div>

    <div class="grid-2">
        <div class="section-card">
            <div class="section-head">
                <div>
                    <h3 class="section-title">Executive Summary</h3>
                    <div class="section-sub">Professional summary of training activity, performance, and participation intensity.</div>
                </div>
            </div>
            <div class="grid-3">
                <div class="insight-card">
                    <div class="kpi-label">Average Points per Participant</div>
                    <div class="kpi-value" style="font-size:1.6rem;"><?= number_format($avg_points_per_participant, 2) ?></div>
                </div>
                <div class="insight-card">
                    <div class="kpi-label">Completed Learners</div>
                    <div class="kpi-value" style="font-size:1.6rem;"><?= number_format($total_completed) ?></div>
                </div>
                <div class="insight-card">
                    <div class="kpi-label">Top Company Points</div>
                    <div class="kpi-value" style="font-size:1.6rem;"><?= number_format($top_company_points, 2) ?></div>
                </div>
            </div>
        </div>

        <div class="company-top-card">
            <div class="small-muted" style="color:rgba(255,255,255,.82);">Top Ranked Company</div>
            <?php if (!empty($company_rankings)): ?>
                <div class="name"><?= e($company_rankings[0]['company_name']) ?></div>
                <div class="points"><?= number_format((float)$company_rankings[0]['total_points'], 2) ?></div>
                <div>CPD points earned</div>
                <div style="margin-top:12px;display:flex;gap:10px;flex-wrap:wrap;">
                    <span class="hero-pill"><?= (int)$company_rankings[0]['participants'] ?> participant(s)</span>
                    <span class="hero-pill"><?= (int)$company_rankings[0]['courses_attended'] ?> course(s)</span>
                </div>
            <?php else: ?>
                <div class="name">No company data available</div>
                <div class="points">0.00</div>
                <div>Awaiting report records</div>
            <?php endif; ?>
        </div>
    </div>

    <div class="grid-2" style="margin-top:18px;">
        <div class="chart-card">
            <div class="section-head">
                <div>
                    <h3 class="section-title">Top Companies by CPD Points</h3>
                    <div class="section-sub">Ranking of participating companies based on total points earned.</div>
                </div>
            </div>
            <div class="chart-box"><canvas id="companyChart"></canvas></div>
        </div>
        <div class="chart-card">
            <div class="section-head">
                <div>
                    <h3 class="section-title">Course Participation Trend</h3>
                    <div class="section-sub">Participant volume by course for the selected date period.</div>
                </div>
            </div>
            <div class="chart-box"><canvas id="courseChart"></canvas></div>
        </div>
    </div>

    <div class="grid-2" style="margin-top:18px;">
        <div class="chart-card">
            <div class="section-head">
                <div>
                    <h3 class="section-title">Job Title Distribution</h3>
                    <div class="section-sub">Top participant job titles captured from applications.</div>
                </div>
            </div>
            <div class="chart-box"><canvas id="jobTitleChart"></canvas></div>
        </div>
        <div class="chart-card">
            <div class="section-head">
                <div>
                    <h3 class="section-title">Gender Distribution</h3>
                    <div class="section-sub">Participant gender mix based on training applications.</div>
                </div>
            </div>
            <div class="chart-box"><canvas id="genderChart"></canvas></div>
        </div>
    </div>

    <div class="section-card" style="margin-top:18px;">
        <div class="section-head">
            <div>
                <h3 class="section-title">Company Ranking Table</h3>
                <div class="section-sub">Professional leaderboard showing company training participation and CPD output.</div>
            </div>
        </div>
        <div class="table-wrap">
            <table class="table-premium" id="companyRankingTable">
                <thead>
                    <tr>
                        <th>Rank</th>
                        <th>Company</th>
                        <th>Participants</th>
                        <th>Courses Attended</th>
                        <th>Total Points</th>
                        <th>Average Points</th>
                        <th>Performance</th>
                    </tr>
                </thead>
                <tbody>
                <?php foreach ($company_rankings as $i => $row): 
                    $score = $top_company_points > 0 ? (($row['total_points'] / $top_company_points) * 100) : 0;
                ?>
                    <tr>
                        <td><span class="rank-badge">#<?= $i + 1 ?></span></td>
                        <td><strong><?= e($row['company_name']) ?></strong></td>
                        <td><?= number_format((int)$row['participants']) ?></td>
                        <td><?= number_format((int)$row['courses_attended']) ?></td>
                        <td><strong><?= number_format((float)$row['total_points'], 2) ?></strong></td>
                        <td><?= number_format((float)($row['avg_points'] ?? 0), 2) ?></td>
                        <td><span class="soft-badge <?= badge_class_by_score($score) ?>"><?= number_format($score,1) ?>%</span></td>
                    </tr>
                <?php endforeach; ?>
                <?php if (empty($company_rankings)): ?>
                    <tr><td colspan="7" class="text-center">No company ranking records found.</td></tr>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <div class="section-card">
        <div class="section-head">
            <div>
                <h3 class="section-title">Training & Course Performance</h3>
                <div class="section-sub">Detailed overview of course participation, completion, and points issued.</div>
            </div>
        </div>
        <div class="table-wrap">
            <table class="table-premium" id="courseTable">
                <thead>
                    <tr>
                        <th>Course</th>
                        <th>Venue</th>
                        <th>Start</th>
                        <th>End</th>
                        <th>Participants</th>
                        <th>Completed</th>
                        <th>Completion %</th>
                        <th>Course Points</th>
                        <th>Issued Points</th>
                    </tr>
                </thead>
                <tbody>
                <?php foreach ($course_rows as $row): 
                    $comp = percent((float)$row['completed'], max((float)$row['participants'], 1));
                ?>
                    <tr>
                        <td><strong><?= e($row['title']) ?></strong></td>
                        <td><?= e($row['venue']) ?></td>
                        <td><?= e($row['start_date']) ?></td>
                        <td><?= e($row['end_date']) ?></td>
                        <td><?= number_format((int)$row['participants']) ?></td>
                        <td><?= number_format((int)$row['completed']) ?></td>
                        <td><span class="soft-badge <?= badge_class_by_score($comp) ?>"><?= number_format($comp,1) ?>%</span></td>
                        <td><?= number_format((float)$row['course_points'], 2) ?></td>
                        <td><strong><?= number_format((float)$row['issued_points'], 2) ?></strong></td>
                    </tr>
                <?php endforeach; ?>
                <?php if (empty($course_rows)): ?>
                    <tr><td colspan="9" class="text-center">No course performance records found.</td></tr>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <div class="section-card">
        <div class="section-head">
            <div>
                <h3 class="section-title">Participant Details</h3>
                <div class="section-sub">Professional participant register including company, job title, discipline, qualification, and points.</div>
            </div>
        </div>
        <div class="table-wrap">
            <table class="table-premium" id="participantTable">
                <thead>
                    <tr>
                        <th>Full Name</th>
                        <th>Company</th>
                        <th>Job Title</th>
                        <th>Discipline</th>
                        <th>Qualification Level</th>
                        <th>Qualification Name</th>
                        <th>Gender</th>
                        <th>Course</th>
                        <th>Status</th>
                        <th>Earned Points</th>
                    </tr>
                </thead>
                <tbody>
                <?php foreach ($participant_rows as $row): ?>
                    <tr>
                        <td><strong><?= e($row['full_name']) ?></strong></td>
                        <td><?= e($row['company_name']) ?></td>
                        <td><?= e($row['position']) ?></td>
                        <td><?= e($row['discipline']) ?></td>
                        <td><?= e($row['qualification_level']) ?></td>
                        <td><?= e($row['qualification_name']) ?></td>
                        <td><?= e($row['gender']) ?></td>
                        <td><?= e($row['course_title']) ?></td>
                        <td><span class="soft-badge badge-brand-soft"><?= e($row['training_status']) ?></span></td>
                        <td><strong><?= number_format((float)$row['earned_points'], 2) ?></strong></td>
                    </tr>
                <?php endforeach; ?>
                <?php if (empty($participant_rows)): ?>
                    <tr><td colspan="10" class="text-center">No participant details found.</td></tr>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <div class="grid-2">
        <div class="section-card">
            <div class="section-head">
                <div>
                    <h3 class="section-title">CPD Points Ledger Summary</h3>
                    <div class="section-sub">Recent ledger entries showing course-linked point issuance activity.</div>
                </div>
            </div>
            <div class="table-wrap">
                <table class="table-premium" id="ledgerTable" style="min-width:720px;">
                    <thead>
                        <tr>
                            <th>Date</th>
                            <th>Course</th>
                            <th>Points</th>
                            <th>Reason</th>
                            <th>Description</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php foreach ($ledger_rows as $row): ?>
                        <tr>
                            <td><?= e($row['issued_on']) ?></td>
                            <td><?= e($row['course_title']) ?></td>
                            <td><strong><?= number_format((float)$row['points'], 2) ?></strong></td>
                            <td><?= e($row['reason']) ?></td>
                            <td><?= e($row['description']) ?></td>
                        </tr>
                    <?php endforeach; ?>
                    <?php if (empty($ledger_rows)): ?>
                        <tr><td colspan="5" class="text-center">No ledger entries found.</td></tr>
                    <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <div class="section-card">
            <div class="section-head">
                <div>
                    <h3 class="section-title">Course Feedback Overview</h3>
                    <div class="section-sub">Average ratings, positivity, and latest qualitative feedback.</div>
                </div>
            </div>
            <div class="grid-3" style="grid-template-columns:repeat(3,minmax(0,1fr));margin-bottom:14px;">
                <div class="insight-card">
                    <div class="kpi-label">Average Rating</div>
                    <div class="kpi-value" style="font-size:1.5rem;"><?= number_format((float)$feedback_overview['avg_rating'], 2) ?></div>
                    <div class="small-muted">/ 5.00</div>
                </div>
                <div class="insight-card">
                    <div class="kpi-label">Total Reviews</div>
                    <div class="kpi-value" style="font-size:1.5rem;"><?= number_format((int)$feedback_overview['total_reviews']) ?></div>
                </div>
                <div class="insight-card">
                    <div class="kpi-label">Positive Rate</div>
                    <div class="kpi-value" style="font-size:1.5rem;"><?= number_format((float)$feedback_overview['positive_rate'], 1) ?>%</div>
                </div>
            </div>

            <?php if (!empty($feedback_course_rows)): ?>
                <div class="table-wrap" style="margin-bottom:14px;">
                    <table class="table-premium" style="min-width:520px;">
                        <thead>
                            <tr>
                                <th>Course</th>
                                <th>Total Reviews</th>
                                <th>Average Rating</th>
                                <th>Rating Class</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($feedback_course_rows as $row): ?>
                                <tr>
                                    <td><strong><?= e($row['title']) ?></strong></td>
                                    <td><?= number_format((int)$row['total_reviews']) ?></td>
                                    <td><?= number_format((float)$row['avg_rating'], 2) ?></td>
                                    <td><span class="soft-badge badge-brand-soft"><?= e(rating_label((float)$row['avg_rating'])) ?></span></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>

            <?php if (!empty($feedback_overview['top_comments'])): ?>
                <?php foreach ($feedback_overview['top_comments'] as $comment): ?>
                    <div class="comment-pill"><?= e($comment) ?></div>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="comment-pill" style="border-left-color:#cbd5e1;">No feedback table or review comments were found in the selected date range.</div>
            <?php endif; ?>
        </div>
    </div>

    <div class="section-card" style="margin-top:18px;">
        <div class="section-head">
            <div>
                <h3 class="section-title">AI Insights & Recommendations</h3>
                <div class="section-sub">Management-ready observations generated from the current report metrics and training performance patterns.</div>
            </div>
        </div>
        <div class="ai-grid">
            <div class="insight-card">
                <div class="kpi-label">AI Insights</div>
                <ul class="insight-list">
                    <?php foreach ($ai_insights as $item): ?>
                        <li><?= e($item) ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
            <div class="insight-card">
                <div class="kpi-label">Recommended Actions</div>
                <ul class="insight-list">
                    <?php foreach ($ai_recommendations as $item): ?>
                        <li><?= e($item) ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        </div>
    </div>
</div>

<script>
const companyLabels = <?= json_encode(array_map(fn($r) => $r['company_name'], array_slice($company_rankings, 0, 8))) ?>;
const companyPoints = <?= json_encode(array_map(fn($r) => (float)$r['total_points'], array_slice($company_rankings, 0, 8))) ?>;
const courseLabels = <?= json_encode(array_map(fn($r) => $r['title'], array_slice($course_rows, 0, 8))) ?>;
const courseParticipants = <?= json_encode(array_map(fn($r) => (int)$r['participants'], array_slice($course_rows, 0, 8))) ?>;
const jobLabels = <?= json_encode(array_map(fn($r) => $r['position'], $job_title_rows)) ?>;
const jobValues = <?= json_encode(array_map(fn($r) => (int)$r['total'], $job_title_rows)) ?>;
const genderLabels = <?= json_encode(array_map(fn($r) => $r['gender'], $gender_rows)) ?>;
const genderValues = <?= json_encode(array_map(fn($r) => (int)$r['total'], $gender_rows)) ?>;

new Chart(document.getElementById('companyChart'), {
    type: 'bar',
    data: {
        labels: companyLabels,
        datasets: [{
            label: 'CPD Points',
            data: companyPoints,
            borderRadius: 10,
            backgroundColor: ['#1d4ed8','#2563eb','#3b82f6','#60a5fa','#93c5fd','#e3262e','#f97316','#0f172a']
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {legend: {display:false}},
        scales: {y: {beginAtZero:true}}
    }
});

new Chart(document.getElementById('courseChart'), {
    type: 'line',
    data: {
        labels: courseLabels,
        datasets: [{
            label: 'Participants',
            data: courseParticipants,
            fill: true,
            tension: .35,
            borderColor: '#1d4ed8',
            backgroundColor: 'rgba(59,130,246,.10)',
            pointBackgroundColor: '#e3262e',
            pointRadius: 4
        }]
    },
    options: {
        responsive:true,
        maintainAspectRatio:false,
        plugins:{legend:{display:true}},
        scales:{y:{beginAtZero:true}}
    }
});

new Chart(document.getElementById('jobTitleChart'), {
    type: 'doughnut',
    data: {
        labels: jobLabels,
        datasets: [{
            data: jobValues,
            backgroundColor: ['#0f172a','#1d4ed8','#2563eb','#3b82f6','#60a5fa','#93c5fd','#e3262e','#fb7185','#22c55e','#f59e0b']
        }]
    },
    options: {responsive:true, maintainAspectRatio:false}
});

new Chart(document.getElementById('genderChart'), {
    type: 'pie',
    data: {
        labels: genderLabels,
        datasets: [{
            data: genderValues,
            backgroundColor: ['#1d4ed8','#e3262e','#0f172a','#60a5fa','#f59e0b','#10b981']
        }]
    },
    options: {responsive:true, maintainAspectRatio:false}
});

function applyQuickRange(days) {
    if (!days) return;
    const today = new Date();
    const start = new Date();
    start.setDate(today.getDate() - parseInt(days, 10));
    const fmt = d => d.toISOString().split('T')[0];
    const url = new URL(window.location.href);
    url.searchParams.set('from', fmt(start));
    url.searchParams.set('to', fmt(today));
    window.location.href = url.toString();
}

async function downloadPDF() {
    const { jsPDF } = window.jspdf;
    const doc = new jsPDF('p', 'pt', 'a4');
    const margin = 36;
    let y = 40;

    doc.setFillColor(15, 23, 42);
    doc.roundedRect(margin, y, 523, 86, 16, 16, 'F');
    doc.setTextColor(255,255,255);
    doc.setFont('helvetica', 'bold');
    doc.setFontSize(18);
    doc.text('Eswatini Contractors Association', margin + 18, y + 28);
    doc.setFontSize(14);
    doc.text('CPD Points Report', margin + 18, y + 50);
    doc.setFont('helvetica', 'normal');
    doc.setFontSize(9);
    doc.text('Generated: <?= date('d M Y H:i') ?>    Filter: <?= e($from ?: 'All Dates') ?><?= $to ? ' to ' . e($to) : '' ?>', margin + 18, y + 69);

    y += 110;
    doc.setTextColor(15,23,42);
    doc.setFont('helvetica', 'bold');
    doc.setFontSize(13);
    doc.text('Executive Summary', margin, y);
    y += 16;

    doc.setFont('helvetica', 'normal');
    doc.setFontSize(10);
    const summaryLines = [
        'Total Trainings: <?= number_format($total_trainings) ?>',
        'Total Participants: <?= number_format($total_participants) ?>',
        'Total Companies: <?= number_format($total_companies) ?>',
        'Total CPD Points: <?= number_format($total_points,2) ?>',
        'Completion Rate: <?= number_format($completion_rate,1) ?>%'
    ];
    summaryLines.forEach(line => { doc.text(line, margin, y); y += 14; });

    y += 10;
    doc.setFont('helvetica', 'bold');
    doc.text('AI Insights', margin, y);
    y += 16;
    doc.setFont('helvetica', 'normal');
    const aiLines = <?= json_encode($ai_insights) ?>;
    aiLines.forEach(item => {
        const wrapped = doc.splitTextToSize('• ' + item, 500);
        doc.text(wrapped, margin, y);
        y += wrapped.length * 12 + 4;
    });

    y += 8;
    doc.autoTable({
        startY: y,
        head: [['Rank','Company','Participants','Courses','Total Points']],
        body: <?= json_encode(array_map(function($r, $i){
            return [
                $i + 1,
                $r['company_name'],
                (int)$r['participants'],
                (int)$r['courses_attended'],
                number_format((float)$r['total_points'],2)
            ];
        }, array_slice($company_rankings,0,10), array_keys(array_slice($company_rankings,0,10)))) ?>,
        theme: 'grid',
        headStyles: { fillColor: [15,23,42] },
        styles: { fontSize: 9 }
    });

    y = doc.lastAutoTable.finalY + 18;
    doc.autoTable({
        startY: y,
        head: [['Course','Participants','Completed','Completion %','Issued Points']],
        body: <?= json_encode(array_map(function($r){
            $comp = percent((float)$r['completed'], max((float)$r['participants'], 1));
            return [
                $r['title'],
                (int)$r['participants'],
                (int)$r['completed'],
                number_format($comp,1) . '%',
                number_format((float)$r['issued_points'],2)
            ];
        }, array_slice($course_rows,0,10))) ?>,
        theme: 'grid',
        headStyles: { fillColor: [29,78,216] },
        styles: { fontSize: 9 }
    });

    doc.save('ECA_CPD_Report_<?= date('Ymd_His') ?>.pdf');
}
</script>

<?php require_once "../footer.php"; ?>