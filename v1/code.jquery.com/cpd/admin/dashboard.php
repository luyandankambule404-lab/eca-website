<?php
require_once "../auth.php";
require_role('SUPPERADMIN');
require_once "../config.php";
require_once "../helpers.php";

/* =========================
   HELPERS
========================= */
if (!function_exists('e')) {
    function e($value){
        return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
    }
}

/* =========================
   BASIC INFO
========================= */
$association_name  = "Eswatini Contractors Association";
$financial_year    = date('Y') . "/" . (date('Y') + 1);
$association_email = "info@eca.co.sz";
$association_site  = "www.eca.co.sz";
$focus_area        = "Construction Operations & Contract Training";

/* =========================
   KPI QUERIES
========================= */
$k_contractors = 0;
$k_courses     = 0;
$k_pending     = 0;
$k_points      = 0;

$q = $conn->query("SELECT COUNT(*) c FROM user WHERE role='CONTRACTOR'");
if ($q) $k_contractors = (int)($q->fetch_assoc()['c'] ?? 0);

$q = $conn->query("SELECT COUNT(*) c FROM courses");
if ($q) $k_courses = (int)($q->fetch_assoc()['c'] ?? 0);

$k_pending = ($conn instanceof mysqli) ? cpd_pending_application_count($conn) : 0;

$q = $conn->query("SELECT COALESCE(SUM(points),0) s FROM cpd_points_ledger");
if ($q) $k_points = (float)($q->fetch_assoc()['s'] ?? 0);

/* =========================
   LEDGER DATE COLUMN
========================= */
$ledgerDateCol = 'created_at';
$dateCols = [];
$cols = $conn->query("SHOW COLUMNS FROM cpd_points_ledger");
if ($cols) {
    while ($c = $cols->fetch_assoc()) {
        $dateCols[] = $c['Field'];
    }
}
if (in_array('issued_at', $dateCols, true)) {
    $ledgerDateCol = 'issued_at';
} elseif (in_array('date', $dateCols, true)) {
    $ledgerDateCol = 'date';
} elseif (in_array('created_at', $dateCols, true)) {
    $ledgerDateCol = 'created_at';
}

/* =========================
   3 YEAR PROGRESS
========================= */
$target_points = 6000.0;
$points_3y     = 0.0;
$progress_pct  = 0;

$q = $conn->query("
    SELECT COALESCE(SUM(points),0) s
    FROM cpd_points_ledger
    WHERE {$ledgerDateCol} >= (NOW() - INTERVAL 3 YEAR)
");
if ($q) {
    $points_3y = (float)($q->fetch_assoc()['s'] ?? 0);
}
$progress_pct = ($target_points > 0) ? min(100, round(($points_3y / $target_points) * 100, 1)) : 0;

/* =========================
   SUMMARY COUNTS
========================= */
$total_learners  = 0;
$total_companies = 0;
$total_trainings = 0;

$q = $conn->query("
    SELECT COUNT(DISTINCT user_id) AS total_learners
    FROM cpd_points_ledger
    WHERE {$ledgerDateCol} >= DATE_SUB(CURDATE(), INTERVAL 3 YEAR)
");
if ($q) $total_learners = (int)($q->fetch_assoc()['total_learners'] ?? 0);

$q = $conn->query("
    SELECT COUNT(*) AS total_companies
    FROM user
    WHERE role = 'CONTRACTOR'
");
if ($q) $total_companies = (int)($q->fetch_assoc()['total_companies'] ?? 0);

$q = $conn->query("
    SELECT COUNT(*) AS total_trainings
    FROM courses
    WHERE " . cpd_status_equals_sql('status', ['CLOSED']) . "
      AND start_date >= DATE_SUB(CURDATE(), INTERVAL 3 YEAR)
");
if ($q) $total_trainings = (int)($q->fetch_assoc()['total_trainings'] ?? 0);

/* =========================
   TOP PERFORMER / TOP MEMBERS
========================= */
$top_members = [];
$top_performer = [
    'name'     => 'No CPD activity',
    'position' => '',
    'Company'  => '',
    'points'   => 0,
    'image'    => ''
];

$top_sql = "
    SELECT 
        COALESCE(u.full_name, MAX(a.full_name), 'Unknown User') AS full_name,
        COALESCE(MAX(a.position), 'Member') AS position,
        COALESCE(MAX(a.company_name), '') AS company_name,
        COALESCE(SUM(l.points), 0) AS total_points,
        MAX(u.image) AS image
    FROM cpd_points_ledger l
    LEFT JOIN user u ON u.id = l.user_id
    LEFT JOIN (
        SELECT email, MAX(full_name) AS full_name, MAX(position) AS position, MAX(company_name) AS company_name
        FROM cpd_applications
        GROUP BY email
    ) a ON a.email = u.email
    GROUP BY l.user_id, u.full_name
    ORDER BY total_points DESC, full_name ASC
    LIMIT 3
";
$top_res = $conn->query($top_sql);
if ($top_res && $top_res->num_rows > 0) {
    while ($row = $top_res->fetch_assoc()) {
        $top_members[] = $row;
    }
}
if (!empty($top_members)) {
    $top_performer = [
        'name'     => $top_members[0]['full_name'] ?? 'Top Member',
        'position' => $top_members[0]['position'] ?? 'Member',
        'Company'  => $top_members[0]['company_name'] ?? '',
        'points'   => (float)($top_members[0]['total_points'] ?? 0),
        'image'    => $top_members[0]['image'] ?? ''
    ];
}

/* =========================
   EMPLOYEE OVERVIEW
========================= */
$employees = [];
$employees_sql = "
    SELECT 
        COALESCE(MAX(a.full_name), u.full_name, 'Unknown') AS full_name,
        COALESCE(MAX(a.company_name), u.company_name, '-') AS company_name,
        COALESCE(MAX(a.position), 'Member') AS position,
        COALESCE(SUM(l.points),0) AS employee_points,
        MAX(a.courses_attended) AS courses_attended
    FROM user u
    LEFT JOIN cpd_points_ledger l ON l.user_id = u.id
    LEFT JOIN (
        SELECT
            applications.email,
            MAX(applications.full_name) AS full_name,
            MAX(applications.company_name) AS company_name,
            MAX(applications.position) AS position,
            GROUP_CONCAT(DISTINCT courses.title ORDER BY courses.start_date DESC SEPARATOR ', ') AS courses_attended
        FROM cpd_applications applications
        LEFT JOIN courses ON courses.id = applications.course_id
        GROUP BY applications.email
    ) a ON a.email = u.email
    WHERE u.role = 'CONTRACTOR'
    GROUP BY u.id, u.full_name, u.company_name
    ORDER BY employee_points DESC, full_name ASC
    LIMIT 4
";
$employees_res = $conn->query($employees_sql);
if ($employees_res && $employees_res->num_rows > 0) {
    while ($row = $employees_res->fetch_assoc()) {
        $employees[] = $row;
    }
}
/* =========================
   PIE DISTRIBUTION
========================= */
$pie_labels = array_fill(0, 4, 'No course data');
$pie_values = array_fill(0, 4, 0);
$course_dist_sql = "
    SELECT c.title, COALESCE(SUM(l.points), 0) AS total
    FROM courses c
    LEFT JOIN cpd_points_ledger l ON l.course_id = c.id
    GROUP BY c.id, c.title
    ORDER BY total DESC, c.title ASC
    LIMIT 4
";
$course_dist_res = $conn->query($course_dist_sql);
if ($course_dist_res && $course_dist_res->num_rows > 0) {
    $distribution = [];
    while ($r = $course_dist_res->fetch_assoc()) {
        $distribution[] = [
            'title' => (string) ($r['title'] ?? 'Untitled course'),
            'total' => (float) ($r['total'] ?? 0),
        ];
    }
    $sum = array_sum(array_column($distribution, 'total'));
    if ($sum > 0) {
        foreach ($distribution as $index => $item) {
            $pie_labels[$index] = $item['title'];
            $pie_values[$index] = round(($item['total'] / $sum) * 100);
        }
        $diff = 100 - array_sum($pie_values);
        $pie_values[0] += $diff;
    }
}

$p1 = max(0, (float)$pie_values[0]);
$p2 = max(0, (float)$pie_values[1]);
$p3 = max(0, (float)$pie_values[2]);
$p4 = max(0, (float)$pie_values[3]);

$s1 = $p1;
$s2 = $p1 + $p2;
$s3 = $p1 + $p2 + $p3;
$s4 = $p1 + $p2 + $p3 + $p4;

/* =========================
   UPCOMING COURSES
========================= */
$upcoming_courses = [];
$up_sql = "
    SELECT title, start_date
    FROM courses
    WHERE start_date >= CURDATE()
    ORDER BY start_date ASC
    LIMIT 2
";
$up_res = $conn->query($up_sql);
if ($up_res && $up_res->num_rows > 0) {
    while ($row = $up_res->fetch_assoc()) {
        $upcoming_courses[] = $row;
    }
}
require_once "../header.php";
?>

<style>
    :root{
  --eca-navy:#000066;
  --eca-red:#d90920;
  --eca-blue:#000066;
  --eca-blue-dark:#000066;
  --eca-green:#000066;
  --eca-page:#f5f7fb;
  --eca-card:#ffffff;
  --eca-border:#e6eaf2;
  --eca-text:#111827;
  --eca-muted:#667085;
  --eca-shadow:0 12px 30px rgba(0, 0, 102, 0.06);
}

*{
  box-sizing:border-box;
}

body{
  background:var(--eca-page) !important;
}

.admin-stage{
  padding:0;
  border-radius:0;
  background:transparent;
}

.cpd-info-strip{
  background:var(--eca-card);
  border:1px solid var(--eca-border);
  border-radius:10px;
  padding:10px 16px;
  margin-bottom:12px;
  display:grid;
  grid-template-columns:1.5fr .9fr .8fr;
  gap:12px;
  box-shadow:var(--eca-shadow);
}

.info-stack{
  display:flex;
  flex-direction:column;
  gap:4px;
  min-width:0;
}

.info-line{
  font-size:13px;
  font-weight:700;
  color:var(--eca-text);
  line-height:1.25;
}

.info-line strong{
  font-weight:800;
  margin-right:6px;
}

.kpi-row{
  display:grid;
  grid-template-columns:1fr 1fr 300px;
  gap:10px;
  margin:10px 0 12px;
  align-items:stretch;
}

.metric-card{
  min-height:72px;
  border-radius:12px;
  padding:12px 16px;
  display:flex;
  align-items:center;
  justify-content:space-between;
  color:#000066;
  position:relative;
  overflow:hidden;
  background:#fff;
  border:1px solid var(--eca-border);
  box-shadow:var(--eca-shadow);
}

.metric-card::after{
  display:none;
}

.metric-card.blue,
.metric-card.orange{
  background:#fff;
}

.metric-label{
  font-size:13px;
  font-weight:800;
  line-height:1.2;
  position:relative;
  z-index:2;
}

.metric-value{
  font-size:28px;
  font-weight:900;
  line-height:1;
  position:relative;
  z-index:2;
}

.top-performer-card{
  background:#fff;
  border:1px solid var(--eca-border);
  border-radius:10px;
  overflow:hidden;
  box-shadow:var(--eca-shadow);
}

.top-performer-head{
  background:#000066;
  color:#fff;
  font-size:13px;
  font-weight:800;
  padding:8px 12px;
}

.top-performer-body{
  display:flex;
  align-items:center;
  gap:10px;
  padding:8px 12px;
}

.performer-photo{
  width:44px;
  height:44px;
  border-radius:8px;
  object-fit:cover;
  border:1px solid #d9dfe8;
  background:#eef2f7;
  flex-shrink:0;
}

.performer-name{
  font-size:14px;
  font-weight:800;
  color:var(--eca-text);
  line-height:1.1;
  margin-bottom:2px;
}

.performer-role{
  font-size:14px;
  font-weight:600;
  color:#314c79;
  line-height:1.25;
}

.admin-grid{
  display:grid;
  grid-template-columns:1.35fr 1fr;
  gap:12px;
  align-items:stretch;
}

.left-grid{
  display:grid;
  grid-template-columns:1fr 1fr;
  gap:12px;
  min-width:0;
}

.right-stack{
  display:grid;
  grid-template-rows:auto 1fr;
  gap:12px;
  min-width:0;
}

.panel-box{
  background:#fff;
  border:1px solid var(--eca-border);
  border-radius:10px;
  overflow:hidden;
  box-shadow:var(--eca-shadow);
}

.panel-head{
  position:relative;
  padding:10px 14px 8px;
  font-size:14px;
  font-weight:800;
  color:var(--eca-text);
}

.panel-head::after{
  content:"";
  position:absolute;
  left:18px;
  right:18px;
  top:22px;
  height:2px;
  background:#d7dde6;
}

.panel-head span{
  position:relative;
  z-index:2;
  background:#fff;
  padding-right:14px;
}

.panel-body-pad{
  padding:14px 16px 18px;
}

.distribution-wrap{
  display:grid;
  grid-template-columns:132px 1fr;
  gap:10px;
  align-items:center;
}

.pie-holder{
  display:flex;
  align-items:center;
  justify-content:center;
}

.pie-chart{
  width:120px;
  height:120px;
  border-radius:50%;
  box-shadow:none;
}

.legend-list{
  display:flex;
  flex-direction:column;
  gap:12px;
}

.legend-item{
  display:flex;
  align-items:center;
  gap:8px;
  font-size:13px;
  font-weight:700;
  color:#27406d;
}

.legend-dot{
  width:14px;
  height:14px;
  border-radius:50%;
  flex-shrink:0;
}

.legend-item strong{
  font-size:16px;
  font-weight:900;
  color:#203867;
}

.upcoming-chart-box{
  height:240px;
  position:relative;
  padding:8px 10px 0;
}

.chart-grid-lines{
  position:absolute;
  inset:18px 10px 48px 10px;
  display:flex;
  flex-direction:column;
  justify-content:space-between;
}

.chart-grid-lines span{
  width:100%;
  border-top:1px solid #dde3eb;
}

.bar-stage{
  position:absolute;
  inset:24px 14px 18px 14px;
  display:flex;
  align-items:flex-end;
  justify-content:center;
  gap:24px;
  border-bottom:2px solid #bcc6d4;
}

.bar-item{
  width:110px;
  text-align:center;
}

.bar{
  width:60px;
  margin:0 auto 10px;
  border-radius:4px 4px 0 0;
  box-shadow:none;
}

.bar.orange{
  background:#d90920;
}

.bar.green{
  background:#000066;
}

.bar-label{
  font-size:14px;
  font-weight:800;
  line-height:1.15;
  color:#29416d;
}

.bar-label .date{
  display:block;
  margin-top:4px;
  color:#4472bd;
  font-size:13px;
  font-weight:800;
}

.progress-panel-body{
  padding:14px 14px 18px;
}

.goal-bar{
  display:grid;
  grid-template-columns:1fr 94px;
  border:1px solid var(--eca-border);
  border-radius:8px;
  overflow:hidden;
}

.goal-left{
  background:#000066;
  color:#fff;
  font-size:15px;
  font-weight:800;
  padding:18px 14px;
  display:flex;
  align-items:center;
}

.goal-right{
  background:#eef2f6;
  color:#42557c;
  font-size:14px;
  font-weight:800;
  display:flex;
  align-items:center;
  justify-content:center;
  text-align:center;
  padding:10px 8px;
}

.goal-right .mini-line{
  display:none;
}

.top-members-list{
  display:grid;
  grid-template-columns:repeat(3, 1fr);
  gap:2px;
  padding:12px 10px 16px;
}

.member-rank{
  display:grid;
  grid-template-columns:1fr;
  justify-items:center;
  text-align:center;
  gap:8px;
  padding:10px 6px;
  border-top:none;
}

.member-rank img{
  width:72px;
  height:72px;
  border-radius:50%;
  object-fit:cover;
  border:6px solid #000066;
  background:#eef2f7;
}

.member-rank .txt{
  font-size:12px;
  line-height:1.25;
  font-weight:900;
  color:#e43a2a;
  text-transform:uppercase;
}

.member-rank .txt span{
  display:block;
  margin-top:4px;
  color:#223969;
  font-size:12px;
  font-weight:700;
  text-transform:none;
}

.member-rank .pts{
  display:none;
}

.table-panel{
  margin-top:16px;
}

.table-responsive{
  overflow-x:auto;
}

.table-cpd{
  width:100%;
  border-collapse:collapse;
  min-width:760px;
}

.table-cpd th{
  background:#e8edf3;
  color:#2a4370;
  border:1px solid #d5dce5;
  padding:13px 16px;
  font-size:14px;
  font-weight:800;
  text-align:left;
}

.table-cpd td{
  background:#fff;
  color:#2a4370;
  border:1px solid #dde3eb;
  padding:12px 16px;
  font-size:14px;
  font-weight:600;
}
.upcoming-list{
  display:flex;
  flex-direction:column;
  gap:12px;
}

.upcoming-course-item{
  display:grid;
  grid-template-columns:62px 1fr auto;
  gap:14px;
  align-items:center;
  background:#f8fafd;
  border:1px solid #dce3ec;
  border-radius:10px;
  padding:12px 14px;
  transition:.25s ease;
}

.upcoming-course-item:hover{
  background:#ffffff;
  transform:translateY(-2px);
  box-shadow:0 6px 16px rgba(17,36,93,.12);
}

.course-date-box{
  width:56px;
  height:56px;
  border-radius:10px;
  background:linear-gradient(135deg,#11245d,#4d86d8);
  color:#fff;
  display:flex;
  flex-direction:column;
  align-items:center;
  justify-content:center;
  box-shadow:0 4px 10px rgba(17,36,93,.22);
}

.course-day{
  font-size:21px;
  font-weight:900;
  line-height:1;
}

.course-month{
  font-size:12px;
  font-weight:800;
  text-transform:uppercase;
  margin-top:4px;
}

.course-list-info{
  min-width:0;
}

.course-list-title{
  font-size:15px;
  font-weight:900;
  color:#203867;
  line-height:1.25;
  margin-bottom:5px;
}

.course-list-date{
  font-size:13px;
  font-weight:700;
  color:#6f7c96;
  display:flex;
  align-items:center;
  gap:6px;
}

.course-list-date i{
  color:#ef3b33;
}

.course-status-badge{
  background:#fff0ef;
  color:#d92f22;
  border:1px solid #ffd1cd;
  border-radius:999px;
  padding:6px 10px;
  font-size:11px;
  font-weight:900;
  text-transform:uppercase;
  white-space:nowrap;
}

@media (max-width:767px){
  .upcoming-course-item{
    grid-template-columns:54px 1fr;
  }

  .course-status-badge{
    grid-column:2;
    width:max-content;
  }

  .course-date-box{
    width:50px;
    height:50px;
  }

  .course-day{
    font-size:18px;
  }
}
.table-cpd td.points{
  text-align:center;
  font-size:20px;
  font-weight:900;
  color:#1f3562;
}

.table-cpd td.course-cell{
  font-size:13px;
  line-height:1.35;
}

.admin-stage::after{
  content:"";
  display:block;
  height:8px;
  background:var(--eca-red);
  margin-top:18px;
  border-radius:0;
}

.full-representatives-panel{
  width:100%;
  margin-top:0;
  grid-column:1 / -1;
}

.full-representatives-panel .table-responsive{
  width:100%;
  overflow-x:auto;
}

.full-representatives-panel .table-cpd{
  width:100%;
  min-width:100%;
}

@media (max-width:1399px){
  .kpi-row{
    grid-template-columns:1fr 1fr 340px;
  }

  .admin-grid{
    grid-template-columns:1fr;
  }

  .left-grid{
    grid-template-columns:1fr 1fr;
  }
}

@media (max-width:1199px){
  .cpd-info-strip,
  .kpi-row,
  .left-grid{
    grid-template-columns:1fr;
  }

  .distribution-wrap{
    grid-template-columns:1fr;
  }

  .top-members-list{
    grid-template-columns:repeat(2,1fr);
  }
}

@media (max-width:767px){
  .cpd-info-strip{
    padding:14px 16px;
    gap:12px;
  }

  .metric-card{
    min-height:118px;
    padding:18px 20px;
  }

  .metric-label{
    font-size:17px;
  }

  .metric-value{
    font-size:38px;
  }

  .performer-photo{
    width:60px;
    height:60px;
  }

  .performer-name{
    font-size:17px;
  }

  .pie-chart{
    width:140px;
    height:140px;
  }

  .top-members-list{
    grid-template-columns:1fr;
  }
}

/* Modern responsive dashboard */
.admin-stage{
  --dash-navy:#192754;
  --dash-red:#d50d0e;
  --dash-line:#e6eaf2;
  --dash-muted:#667085;
  --dash-card:#fff;
  --dash-shadow:0 10px 28px rgba(25,39,84,.06);
}
.admin-stage .cpd-info-strip{
  display:flex;
  flex-wrap:wrap;
  align-items:center;
  gap:8px;
  margin:0 0 14px;
  padding:12px 14px;
  border:1px solid var(--dash-line);
  border-left:3px solid var(--dash-red);
  border-radius:16px;
  background:var(--dash-card);
  box-shadow:var(--dash-shadow);
}
.admin-stage .info-stack{ display:contents; }
.admin-stage .info-line{
  display:inline-flex;
  align-items:center;
  gap:6px;
  max-width:100%;
  margin:0;
  padding:6px 10px;
  border-radius:999px;
  background:#f4f6fb;
  color:var(--dash-navy);
  font-size:12px;
  font-weight:700;
  line-height:1.3;
}
.admin-stage .info-line strong{
  color:var(--dash-muted);
  font-size:10px;
  font-weight:800;
  letter-spacing:.06em;
  text-transform:uppercase;
}
.admin-stage .kpi-row{
  grid-template-columns:repeat(3,minmax(0,1fr));
  gap:10px;
  margin:0 0 16px;
}
.admin-stage .metric-card,
.admin-stage .top-performer-card,
.admin-stage .panel-box{
  border:1px solid var(--dash-line);
  border-radius:14px;
  background:var(--dash-card);
  box-shadow:var(--dash-shadow);
}
.admin-stage .metric-card{
  min-height:88px;
  padding:14px 16px;
  flex-direction:column;
  align-items:flex-start;
  justify-content:space-between;
  gap:8px;
}
.admin-stage .metric-label,
.admin-stage .top-performer-head{
  color:var(--dash-muted);
  font-size:11px;
  font-weight:800;
  letter-spacing:.08em;
  text-transform:uppercase;
}
.admin-stage .metric-value{
  color:var(--dash-navy);
  font-size:1.75rem;
  font-weight:800;
}
.admin-stage .top-performer-head{
  background:transparent;
  padding:14px 16px 0;
}
.admin-stage .top-performer-body{ padding:8px 16px 14px; }
.admin-stage .performer-photo{
  width:42px;
  height:42px;
  border-radius:50%;
}
.admin-stage .performer-name{
  color:var(--dash-navy);
  font-size:14px;
}
.admin-stage .performer-role{
  color:var(--dash-muted);
  font-size:12px;
}
.admin-stage .admin-grid{
  display:grid;
  grid-template-columns:minmax(0,1.4fr) minmax(260px,0.9fr);
  gap:12px;
  margin:0 0 12px;
}
.admin-stage .left-grid{
  display:grid;
  grid-template-columns:repeat(2,minmax(0,1fr));
  gap:12px;
}
.admin-stage .right-stack{
  display:flex;
  flex-direction:column;
  gap:12px;
}
.admin-stage .right-stack .panel-box{ flex:1 1 auto; }
.admin-stage .full-representatives-panel{
  grid-column:1 / -1;
  margin-top:0;
}
.admin-stage .panel-head{
  padding:14px 16px 4px;
  color:var(--dash-navy);
  font-family:"Plus Jakarta Sans",system-ui,sans-serif;
  font-size:15px;
}
body.hub-admin.is-admin-dash .admin-stage .hub-tiles.hub-portals,
.admin-stage .hub-tiles.hub-portals{
  display:grid;
  grid-template-columns:repeat(4,minmax(0,1fr));
  gap:10px;
  margin:0 0 16px;
}
body.hub-admin.is-admin-dash .admin-stage .hub-portal-tile,
.admin-stage .hub-portal-tile{
  display:flex !important;
  flex-direction:column;
  min-height:138px;
  padding:14px 14px 12px;
}
body.hub-admin.is-admin-dash .admin-stage .hub-portal-kicker,
.admin-stage .hub-portal-kicker{
  display:inline-flex !important;
  align-items:center;
  gap:6px;
  margin:0 0 8px;
  color:var(--dash-muted);
  font-size:10px;
  font-weight:800;
  letter-spacing:.08em;
  text-transform:uppercase;
}
body.hub-admin.is-admin-dash .admin-stage .hub-portal-tile h3,
.admin-stage .hub-portal-tile h3{
  font-family:"Plus Jakarta Sans",system-ui,sans-serif !important;
  font-size:0.98rem;
  font-weight:800;
}
body.hub-admin.is-admin-dash .admin-stage .hub-portal-tile p,
.admin-stage .hub-portal-tile p{
  display:-webkit-box;
  -webkit-line-clamp:2;
  -webkit-box-orient:vertical;
  overflow:hidden;
  flex:1 1 auto;
}
body.hub-admin.is-admin-dash .admin-stage .hub-portal-tile > span,
.admin-stage .hub-portal-tile > span{
  margin-top:auto;
  padding-top:8px;
  color:var(--dash-navy);
  font-size:12px;
  font-weight:800;
}
body.hub-admin.is-admin-dash .admin-stage .hub-card-head,
.admin-stage .hub-card-head{
  margin:4px 0 10px !important;
  align-items:end;
}
body.hub-admin.is-admin-dash .admin-stage .hub-card-head h2,
.admin-stage .hub-card-head h2{
  font-family:"Plus Jakarta Sans",system-ui,sans-serif !important;
  font-size:1.2rem !important;
  font-weight:800 !important;
  letter-spacing:-0.02em;
}
.admin-stage .distribution-wrap{
  min-height:160px;
}
.admin-stage .upcoming-list{
  min-height:140px;
}
.admin-stage .panel-head::after{ display:none; }
.admin-stage .panel-head span{
  padding:0;
  background:transparent;
}
.admin-stage .panel-body-pad{ padding:8px 16px 16px; }
.admin-stage .legend-item{
  color:var(--dash-navy);
  font-size:13px;
}
.admin-stage .member-rank img{ border-color:var(--dash-navy); }
.admin-stage .table-responsive{
  overflow-x:auto;
  -webkit-overflow-scrolling:touch;
}
.admin-stage .table-cpd{
  min-width:640px;
  border-collapse:separate;
  border-spacing:0;
}
.admin-stage .table-cpd th{
  background:#f4f6fb;
  color:var(--dash-navy);
  border:0;
  border-bottom:1px solid var(--dash-line);
  font-size:12px;
  letter-spacing:.04em;
  text-transform:uppercase;
}
.admin-stage .table-cpd td{
  border:0;
  border-bottom:1px solid var(--dash-line);
  color:#243056;
}
@media (max-width:1200px){
  .admin-stage .hub-tiles.hub-portals{
    grid-template-columns:repeat(3,minmax(0,1fr));
  }
}
@media (max-width:1100px){
  .admin-stage .kpi-row{ grid-template-columns:1fr 1fr; }
  .admin-stage .top-performer-card{ grid-column:1 / -1; }
  .admin-stage .admin-grid,
  .admin-stage .left-grid,
  .admin-stage .distribution-wrap{ grid-template-columns:1fr; }
  .admin-stage .hub-tiles.hub-portals{
    grid-template-columns:repeat(2,minmax(0,1fr));
  }
  .admin-stage .pie-holder{ justify-content:flex-start; }
}
@media (max-width:700px){
  .admin-stage .kpi-row,
  .admin-stage .top-members-list{ grid-template-columns:1fr; }
  .admin-stage .metric-card{
    min-height:0;
    padding:12px 14px;
  }
  .admin-stage .metric-label{ font-size:11px; }
  .admin-stage .metric-value{ font-size:1.55rem; }
  .admin-stage .performer-photo{ width:40px; height:40px; }
  .admin-stage .performer-name{ font-size:14px; }
  .admin-stage .pie-chart{ width:112px; height:112px; }
  .admin-stage .cpd-info-strip{ padding:10px; }
  .admin-stage .info-line{ font-size:12px; }
}
</style>


<div class="container-fluid px-3 px-lg-4 admin-stage">

    <div class="cpd-info-strip">
        <div class="info-stack">
            <div class="info-line"><strong>Association Name:</strong> <?= e($association_name) ?></div>
            <div class="info-line"><strong>Financial Year:</strong> <?= e($financial_year) ?></div>
            <div class="info-line"><strong>Focus:</strong> <?= e($focus_area) ?></div>
        </div>

        <div class="info-stack">
            <div class="info-line"><strong>Contact:</strong> <?= e($association_email) ?></div>
        </div>

        <div class="info-stack">
            <div class="info-line"><strong>Website:</strong> <?= e($association_site) ?></div>
        </div>
    </div>

    <div class="kpi-row">
        <div class="metric-card blue">
            <div class="metric-label">Total Members</div>
            <div class="metric-value"><?= number_format($k_contractors) ?></div>
        </div>

        <div class="metric-card orange">
            <div class="metric-label">Avg. CPD Points</div>
            <div class="metric-value">
                <?= $k_contractors > 0 ? number_format($k_points / $k_contractors, 1) : '0.0' ?>
            </div>
        </div>

        <div class="top-performer-card">
            <div class="top-performer-head">Top Performer</div>
            <div class="top-performer-body">
                <?php
                    $topImage = !empty($top_performer['image'])
                        ? e($top_performer['image'])
                        : 'https://ui-avatars.com/api/?name=' . urlencode($top_performer['name']) . '&background=e8edf5&color=2f4b73&size=300&rounded=false&bold=true';
                ?>
                <img src="<?= $topImage ?>" alt="Top Performer" class="performer-photo">
                <div>
                    <div class="performer-name"><?= e($top_performer['name']) ?></div>
                    <div class="performer-role"><?= e($top_performer['position']) ?></div>
                    <div class="performer-role"><?= e($top_performer['Company']) ?></div>
                </div>
            </div>
        </div>
    </div>

    <?php if (function_exists('eca_super_admin_can_open_portals') && eca_super_admin_can_open_portals()): ?>
    <div class="hub-card-head">
        <h2>Portals</h2>
        <a href="/admin/wellness/">Open Wellness</a>
    </div>
    <div class="hub-tiles hub-portals">
        <a class="hub-tile hub-portal-tile hub-tile-lilac" href="/admin/wellness/">
            <p class="hub-portal-kicker"><i class="fa-solid fa-heart-pulse" aria-hidden="true"></i> Portal</p>
            <h3>Wellness</h3>
            <p>Manage wellness events, resources and announcements.</p>
            <span>Open →</span>
        </a>
        <a class="hub-tile hub-portal-tile hub-tile-mint" href="/wellness/">
            <p class="hub-portal-kicker"><i class="fa-solid fa-spa" aria-hidden="true"></i> Portal</p>
            <h3>Wellness Hub</h3>
            <p>Public articles, videos, toolbox talks and support pages.</p>
            <span>Open →</span>
        </a>
        <a class="hub-tile hub-portal-tile" href="/client/wellness/">
            <p class="hub-portal-kicker"><i class="fa-solid fa-heart" aria-hidden="true"></i> Portal</p>
            <h3>Member Wellness</h3>
            <p>Signed-in member events, resources and announcements.</p>
            <span>Open →</span>
        </a>
        <a class="hub-tile hub-portal-tile hub-tile-sand" href="/client/dashboard.php">
            <p class="hub-portal-kicker"><i class="fa-solid fa-id-card" aria-hidden="true"></i> Portal</p>
            <h3>Member Hub</h3>
            <p>Membership, certificates, payments and documents.</p>
            <span>Open →</span>
        </a>
        <a class="hub-tile hub-portal-tile" href="/learner-portal.php">
            <p class="hub-portal-kicker"><i class="fa-solid fa-laptop" aria-hidden="true"></i> Portal</p>
            <h3>Learner Portal</h3>
            <p>Courses, applications and CPD records.</p>
            <span>Open →</span>
        </a>
        <a class="hub-tile hub-portal-tile hub-tile-lilac" href="/admin/index.php">
            <p class="hub-portal-kicker"><i class="fa-solid fa-gauge-high" aria-hidden="true"></i> Portal</p>
            <h3>Admin Hub</h3>
            <p>Membership, finance and system administration.</p>
            <span>Open →</span>
        </a>
        <a class="hub-tile hub-portal-tile hub-tile-sand" href="/cpd/officer/dashboard.php">
            <p class="hub-portal-kicker"><i class="fa-solid fa-user-tie" aria-hidden="true"></i> Portal</p>
            <h3>Officer Hub</h3>
            <p>Review applications, attendance and participants.</p>
            <span>Open →</span>
        </a>
        <a class="hub-tile hub-portal-tile hub-tile-mint" href="/education.php">
            <p class="hub-portal-kicker"><i class="fa-solid fa-graduation-cap" aria-hidden="true"></i> Portal</p>
            <h3>Education Hub</h3>
            <p>Public training programmes, CPD and learning resources.</p>
            <span>Open →</span>
        </a>
    </div>
    <?php endif; ?>

    <div class="admin-grid">
        <div class="left-grid">
            <div class="panel-box">
                <div class="panel-head"><span>CPD Points Distribution</span></div>
                <div class="panel-body-pad">
                    <div class="distribution-wrap">
                        <div class="pie-holder">
                            <div class="pie-chart"
                                 style="background:
                                 conic-gradient(
                                    #f08d25 0% <?= $s1 ?>%,
                                    #3f7ccb <?= $s1 ?>% <?= $s2 ?>%,
                                    #52ab58 <?= $s2 ?>% <?= $s3 ?>%,
                                    #f4b227 <?= $s3 ?>% <?= $s4 ?>%
                                 );"></div>
                        </div>
                        <div class="legend-list">
                            <div class="legend-item">
                                <span class="legend-dot" style="background:#f08d25;"></span>
                                <span><?= e($pie_labels[0]) ?> <strong><?= (int)$pie_values[0] ?>%</strong></span>
                            </div>
                            <div class="legend-item">
                                <span class="legend-dot" style="background:#3f7ccb;"></span>
                                <span><?= e($pie_labels[1]) ?> <strong><?= (int)$pie_values[1] ?>%</strong></span>
                            </div>
                            <div class="legend-item">
                                <span class="legend-dot" style="background:#52ab58;"></span>
                                <span><?= e($pie_labels[2]) ?> <strong><?= (int)$pie_values[2] ?>%</strong></span>
                            </div>
                            <div class="legend-item">
                                <span class="legend-dot" style="background:#f4b227;"></span>
                                <span><?= e($pie_labels[3]) ?> <strong><?= (int)$pie_values[3] ?>%</strong></span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="panel-box">
                <div class="panel-head"><span>Upcoming Courses</span></div>
                <div class="panel-body-pad">
                    <div class="upcoming-list">
                        <?php if (empty($upcoming_courses)): ?>
                            <p class="hub-sub" style="margin:0;">No upcoming courses are scheduled.</p>
                        <?php endif; ?>
                        <?php foreach ($upcoming_courses as $idx => $course):
                            $title = $course['title'] ?? 'Course';
                            $date  = !empty($course['start_date'])
                                ? date('d M Y', strtotime($course['start_date']))
                                : date('d M Y');
                            $month = !empty($course['start_date'])
                                ? date('M', strtotime($course['start_date']))
                                : date('M');
                            $day = !empty($course['start_date'])
                                ? date('d', strtotime($course['start_date']))
                                : date('d');
                        ?>
                            <div class="upcoming-course-item">
                                <div class="course-date-box">
                                    <span class="course-day"><?= e($day) ?></span>
                                    <span class="course-month"><?= e($month) ?></span>
                                </div>
                                <div class="course-list-info">
                                    <div class="course-list-title"><?= e($title) ?></div>
                                    <div class="course-list-date">
                                        <i class="bi bi-calendar-event"></i>
                                        <?= e($date) ?>
                                    </div>
                                </div>
                                <div class="course-status-badge">Upcoming</div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        </div>

        <div class="right-stack">
            <div class="panel-box">
                <div class="panel-head"><span>CPD Progress</span></div>
                <div class="progress-panel-body">
                    <div class="goal-bar">
                        <div class="goal-left">Annual Goal: <?= e($target_points) ?> Points</div>
                        <div class="goal-right">
                            <span class="mini-line"></span>
                            <span><?= e($progress_pct) ?>% Completed</span>
                        </div>
                    </div>
                </div>
            </div>

            <div class="panel-box">
                <div class="panel-head"><span>Top Members</span></div>
                <div class="top-members-list">
                    <?php
                    $rank = 1;
                    foreach ($top_members as $member):
                        $img = !empty($member['image'])
                            ? e($member['image'])
                            : 'https://ui-avatars.com/api/?name=' . urlencode($member['full_name']) . '&background=e8edf5&color=2f4b73&size=200&rounded=false&bold=true';
                    ?>
                        <div class="member-rank">
                            <img src="<?= $img ?>" alt="<?= e($member['full_name']) ?>">
                            <div class="txt">
                                <?= $rank ?>. <?= e($member['full_name']) ?>
                                <span class="pts"><?= number_format((float)($member['total_points'] ?? 0), 0) ?> pts</span>
                            </div>
                        </div>
                    <?php
                        $rank++;
                    endforeach;
                    if ($rank === 1):
                    ?>
                        <p class="hub-sub" style="margin:0;padding:4px 16px 16px;">No CPD leaderboard data yet.</p>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <div class="panel-box table-panel full-representatives-panel">
            <div class="panel-head">
                <span>Representatives Overview</span>
            </div>
            <div class="table-responsive">
                <table class="table-cpd">
                    <thead>
                        <tr>
                            <th>Name</th>
                            <th>Company</th>
                            <th>Position</th>
                            <th>CPD Points</th>
                            <th>Courses Attended</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!empty($employees)): ?>
                            <?php foreach ($employees as $emp): ?>
                                <tr>
                                    <td><?= e($emp['full_name'] ?? '-') ?></td>
                                    <td><?= e($emp['company_name'] ?? '-') ?></td>
                                    <td><?= e($emp['position'] ?? '-') ?></td>
                                    <td class="points">
                                        <?= number_format((float)($emp['employee_points'] ?? 0), 0) ?>
                                    </td>
                                    <td class="course-cell">
                                        <?= e($emp['courses_attended'] ?? '-') ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="5" style="text-align:center; padding:25px; font-weight:800; color:#6f7c96;">
                                    No representatives found.
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<?php require_once "../footer.php"; ?>