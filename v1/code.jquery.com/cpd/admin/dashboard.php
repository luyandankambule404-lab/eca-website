<?php
require_once "../auth.php";
require_role('SUPPERADMIN');
require_once "../config.php";

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

$pending_tables = ['cpd_applications', 'course_applications'];
foreach ($pending_tables as $tbl) {
    $check = $conn->query("SHOW TABLES LIKE '{$tbl}'");
    if ($check && $check->num_rows > 0) {
        $q = $conn->query("SELECT COUNT(*) c FROM {$tbl} WHERE status='PENDING'");
        if ($q) {
            $k_pending = (int)($q->fetch_assoc()['c'] ?? 0);
            break;
        }
    }
}

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
    FROM users
    WHERE role = 'CONTRACTOR'
");
if ($q) $total_companies = (int)($q->fetch_assoc()['total_companies'] ?? 0);

$q = $conn->query("
    SELECT COUNT(*) AS total_trainings
    FROM courses
    WHERE status = 'CLOSED'
      AND start_date >= DATE_SUB(CURDATE(), INTERVAL 3 YEAR)
");
if ($q) $total_trainings = (int)($q->fetch_assoc()['total_trainings'] ?? 0);

/* =========================
   TOP PERFORMER / TOP MEMBERS
========================= */
$top_members = [];
$top_performer = [
    'name'     => 'John Dlamini',
    'position' => 'Project Manager',
       'Company' => 'MSA Construction',
    'points'   => 28,
    'image'    => ''
];

$top_sql = "
    SELECT 
        COALESCE(u.full_name, a.full_name, 'Unknown User') AS full_name,
        COALESCE(MAX(a.position), 'Member') AS position,
        COALESCE(MAX(a.company_name), '') AS company_name,
        COALESCE(SUM(l.points), 0) AS total_points,
        MAX(u.image) AS image
    FROM cpd_points_ledger l
    LEFT JOIN user u ON u.id = l.user_id
    LEFT JOIN cpd_applications a ON a.email = u.email
    GROUP BY l.user_id, u.full_name, a.full_name
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
        COALESCE(a.full_name, u.full_name, 'Unknown') AS full_name,
        COALESCE(a.company_name, u.company_name, '-') AS company_name,
        COALESCE(a.position, 'Member') AS position,
        COALESCE(SUM(l.points),0) AS employee_points,
        GROUP_CONCAT(DISTINCT c.title ORDER BY c.start_date DESC SEPARATOR ', ') AS courses_attended
    FROM user u
    LEFT JOIN cpd_points_ledger l ON l.user_id = u.id
    LEFT JOIN cpd_applications a ON a.email = u.email
    LEFT JOIN courses c ON c.id = a.course_id
    WHERE u.role = 'CONTRACTOR'
    GROUP BY u.id, a.full_name, u.full_name, a.company_name, u.company_name, a.position
    ORDER BY employee_points DESC, full_name ASC
    LIMIT 4
";
$employees_res = $conn->query($employees_sql);
if ($employees_res && $employees_res->num_rows > 0) {
    while ($row = $employees_res->fetch_assoc()) {
        $employees[] = $row;
    }
}
if (empty($employees)) {
    $employees = [
        ['full_name'=>'John Dlamini','company_name'=>'BuildTech Ltd.','position'=>'Project Manager','employee_points'=>28,'courses_attended'=>'Contract Training, Safety Mgmt'],
        ['full_name'=>'Sarah Mthembu','company_name'=>'InfraWorks Inc.','position'=>'Procurement Officer','employee_points'=>15,'courses_attended'=>'Tendering Fundamentals'],
        ['full_name'=>'Thabo Nkosi','company_name'=>'MegaConstruct','position'=>'Site Engineer','employee_points'=>20,'courses_attended'=>'Contract Training, Quality Mgmt'],
        ['full_name'=>'Nomsa Khumalo','company_name'=>'Urban Develop SA','position'=>'HR Specialist','employee_points'=>12,'courses_attended'=>'Workforce Development'],
    ];
}

/* =========================
   PIE DISTRIBUTION
========================= */
$pie_labels = ['Contract Training', 'Safety Management', 'Tendering Fundamentals', 'Quality Mgmt'];
$pie_values = [35, 25, 20, 20];

$cat_counts = [
    'Contract Training'      => 0,
    'Safety Management'      => 0,
    'Tendering Fundamentals' => 0,
    'Quality Mgmt'           => 0
];

$course_dist_sql = "SELECT title FROM courses";
$course_dist_res = $conn->query($course_dist_sql);
if ($course_dist_res && $course_dist_res->num_rows > 0) {
    while ($r = $course_dist_res->fetch_assoc()) {
        $title = strtolower((string)($r['title'] ?? ''));
        if (strpos($title, 'contract') !== false) {
            $cat_counts['Contract Training']++;
        } elseif (strpos($title, 'safety') !== false) {
            $cat_counts['Safety Management']++;
        } elseif (strpos($title, 'tender') !== false) {
            $cat_counts['Tendering Fundamentals']++;
        } elseif (strpos($title, 'quality') !== false) {
            $cat_counts['Quality Mgmt']++;
        }
    }

    $sum = array_sum($cat_counts);
    if ($sum > 0) {
        $pie_labels = array_keys($cat_counts);
        $pie_values = [];
        foreach ($cat_counts as $v) {
            $pie_values[] = round(($v / $sum) * 100);
        }
        $diff = 100 - array_sum($pie_values);
        if (isset($pie_values[0])) {
            $pie_values[0] += $diff;
        }
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
if (empty($upcoming_courses)) {
    $upcoming_courses = [
        ['title' => 'Contract Training', 'start_date' => date('Y-04-01')],
        ['title' => 'Safety Management', 'start_date' => date('Y-06-01')],
    ];
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
  padding:18px 28px;
  margin-bottom:20px;
  display:grid;
  grid-template-columns:1.5fr .9fr .8fr;
  gap:24px;
  box-shadow:var(--eca-shadow);
}

.info-stack{
  display:flex;
  flex-direction:column;
  gap:10px;
  min-width:0;
}

.info-line{
  font-size:15px;
  font-weight:700;
  color:var(--eca-text);
  line-height:1.2;
}

.info-line strong{
  font-weight:800;
  margin-right:6px;
}

.kpi-row{
  display:grid;
  grid-template-columns:1fr 1fr 390px;
  gap:16px;
  margin-bottom:18px;
  align-items:stretch;
}

.metric-card{
  min-height:146px;
  border-radius:16px;
  padding:24px 28px;
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
  font-size:20px;
  font-weight:800;
  line-height:1.2;
  position:relative;
  z-index:2;
}

.metric-value{
  font-size:50px;
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
  font-size:18px;
  font-weight:800;
  padding:14px 20px;
}

.top-performer-body{
  display:flex;
  align-items:center;
  gap:16px;
  padding:14px 18px;
}

.performer-photo{
  width:78px;
  height:78px;
  border-radius:8px;
  object-fit:cover;
  border:1px solid #d9dfe8;
  background:#eef2f7;
  flex-shrink:0;
}

.performer-name{
  font-size:20px;
  font-weight:800;
  color:var(--eca-text);
  line-height:1.1;
  margin-bottom:4px;
}

.performer-role{
  font-size:14px;
  font-weight:600;
  color:#314c79;
  line-height:1.25;
}

.admin-grid{
  display:grid;
  grid-template-columns:1.7fr .8fr;
  gap:16px;
  align-items:start;
}

.left-grid{
  display:grid;
  grid-template-columns:1.15fr .95fr;
  gap:16px;
}

.right-stack{
  display:flex;
  flex-direction:column;
  gap:16px;
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
  padding:14px 18px 10px;
  font-size:17px;
  font-weight:800;
  color:var(--eca-text);
}

.panel-head::after{
  content:"";
  position:absolute;
  left:18px;
  right:18px;
  top:28px;
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
  grid-template-columns:190px 1fr;
  gap:14px;
  align-items:center;
}

.pie-holder{
  display:flex;
  align-items:center;
  justify-content:center;
}

.pie-chart{
  width:178px;
  height:178px;
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
  gap:10px;
  font-size:16px;
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
  margin-top:16px;
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

    <div class="admin-grid">
        <div>
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

                    <div class="course-status-badge">
                        Upcoming
                    </div>
                </div>
            <?php endforeach; ?>
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
                    ?>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once "../footer.php"; ?>