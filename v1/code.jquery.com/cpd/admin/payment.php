<?php
require_once "../auth.php";
require_role(['SUPPERADMIN', 'OFFICER', 'ADMIN']);
require_once "../config.php";
require_once dirname(__DIR__, 3) . '/includes/pagination.php';
if ($conn instanceof mysqli) {
    cpd_ensure_wallet_transaction_columns($conn);
}

/* =========================
   HELPERS
========================= */
function h($value){
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}

function valid_date($date){
    return preg_match('/^\d{4}-\d{2}-\d{2}$/', (string)$date);
}

function money($amount){
    return 'E' . number_format((float)$amount, 2);
}

function bind_dynamic_params(mysqli_stmt $stmt, string $types, array &$params){
    if ($types === '' || empty($params)) {
        return;
    }

    $refs = [];
    $refs[] = $types;

    foreach ($params as $key => &$value) {
        $refs[] = &$value;
    }

    call_user_func_array([$stmt, 'bind_param'], $refs);
}

function status_badge($status){
    $status = strtoupper(trim((string)$status));

    if ($status === 'APPROVED' || $status === 'SUCCESS' || $status === 'SUCCESSFUL' || $status === 'PAID') {
        return '<span class="pay-badge approved"><i class="fa-solid fa-circle-check me-1"></i>Approved</span>';
    }

    if ($status === 'FAILED' || $status === 'DECLINED' || $status === 'CANCELLED' || $status === 'CANCELED') {
        return '<span class="pay-badge failed"><i class="fa-solid fa-circle-xmark me-1"></i>Failed</span>';
    }

    return '<span class="pay-badge pending"><i class="fa-solid fa-clock me-1"></i>Pending</span>';
}

function redirect_back(){
    $query = $_GET ? '?' . http_build_query($_GET) : '';
    header("Location: payment.php" . $query);
    exit;
}

/* =========================
   POST ACTIONS
========================= */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_status'])) {
    cpd_require_csrf();
    $transaction_id = (int)($_POST['transaction_id'] ?? 0);
    $new_status = strtoupper(trim($_POST['new_status'] ?? ''));

    $allowed_statuses = ['APPROVED', 'FAILED', 'PENDING'];

    if ($transaction_id <= 0 || !in_array($new_status, $allowed_statuses, true)) {
        $_SESSION['payment_error'] = "Invalid transaction update request.";
        redirect_back();
    }

    $sets = ["status = ?"];
    $types = 'si';
    $bind = [$new_status, $transaction_id];
    if ($new_status === 'APPROVED' && cpd_table_has_column($conn, 'wallet_transactions', 'approved_at')) {
        $sets[] = 'approved_at = COALESCE(approved_at, NOW())';
    } elseif ($new_status === 'PENDING' && cpd_table_has_column($conn, 'wallet_transactions', 'approved_at')) {
        $sets[] = 'approved_at = NULL';
    }
    if (cpd_table_has_column($conn, 'wallet_transactions', 'updated_at')) {
        $sets[] = 'updated_at = NOW()';
    }
    $stmt = $conn->prepare(
        'UPDATE wallet_transactions SET ' . implode(', ', $sets) . ' WHERE id = ? LIMIT 1'
    );
    if ($stmt) {
        $stmt->bind_param($types, $bind[0], $bind[1]);
        if ($stmt->execute()) {
            $_SESSION['payment_success'] = "Transaction updated to " . $new_status . ".";
        } else {
            $_SESSION['payment_error'] = "Unable to update that transaction. Please try again.";
        }
        $stmt->close();
        redirect_back();
    }

    $_SESSION['payment_error'] = "Unable to update that transaction. Please try again.";
    redirect_back();
}

/* =========================
   FILTERS
========================= */
$status_filter = strtoupper(trim($_GET['status'] ?? ''));
$from_date = trim($_GET['from'] ?? '');
$to_date = trim($_GET['to'] ?? '');
$search = trim($_GET['search'] ?? '');

$allowed_filter_statuses = ['APPROVED', 'FAILED', 'PENDING'];

$where = [];
$types = "";
$params = [];

if ($status_filter !== '' && in_array($status_filter, $allowed_filter_statuses, true)) {
    $where[] = "UPPER(t.status) = ?";
    $types .= "s";
    $params[] = $status_filter;
}

if ($from_date !== '' && valid_date($from_date)) {
    $where[] = "DATE(t.created_at) >= ?";
    $types .= "s";
    $params[] = $from_date;
}

if ($to_date !== '' && valid_date($to_date)) {
    $where[] = "DATE(t.created_at) <= ?";
    $types .= "s";
    $params[] = $to_date;
}

if ($search !== '') {
    $searchParts = [];
    foreach (['request_id', 'membership_number', 'reference_id', 'transaction_id', 'mobile_number'] as $column) {
        if (cpd_table_has_column($conn, 'wallet_transactions', $column)) {
            $searchParts[] = 't.' . $column . ' LIKE ?';
        }
    }
    $searchParts[] = 'CAST(t.application_id AS CHAR) LIKE ?';
    $searchParts[] = 'a.full_name LIKE ?';
    $searchParts[] = 'a.company_name LIKE ?';
    $where[] = '(' . implode(' OR ', $searchParts) . ')';
    $like = '%' . $search . '%';
    foreach ($searchParts as $unused) {
        $types .= 's';
        $params[] = $like;
    }
}

$where_sql = "";
if (!empty($where)) {
    $where_sql = "WHERE " . implode(" AND ", $where);
}

/* =========================
   STATS QUERY
========================= */
$stats_sql = "
    SELECT
        COUNT(*) AS total_transactions,
        COALESCE(SUM(t.amount), 0) AS total_amount,

        SUM(CASE WHEN UPPER(t.status) = 'APPROVED' THEN 1 ELSE 0 END) AS approved_count,
        COALESCE(SUM(CASE WHEN UPPER(t.status) = 'APPROVED' THEN t.amount ELSE 0 END), 0) AS approved_amount,

        SUM(CASE WHEN UPPER(t.status) = 'PENDING' THEN 1 ELSE 0 END) AS pending_count,
        COALESCE(SUM(CASE WHEN UPPER(t.status) = 'PENDING' THEN t.amount ELSE 0 END), 0) AS pending_amount,

        SUM(CASE WHEN UPPER(t.status) = 'FAILED' THEN 1 ELSE 0 END) AS failed_count,
        COALESCE(SUM(CASE WHEN UPPER(t.status) = 'FAILED' THEN t.amount ELSE 0 END), 0) AS failed_amount
    FROM wallet_transactions t
    LEFT JOIN cpd_applications a ON a.id = t.application_id
    $where_sql
";

$stats_stmt = $conn->prepare($stats_sql);

if (!$stats_stmt) {
    http_response_code(503);
    exit('Unable to load payment records. Please try again shortly.');
}

$stats_params = $params;
bind_dynamic_params($stats_stmt, $types, $stats_params);
$stats_stmt->execute();
$stats = $stats_stmt->get_result()->fetch_assoc();
$stats_stmt->close();

/* =========================
   TRANSACTIONS QUERY
========================= */
$selectCols = [];
foreach ([
    'id',
    'request_id',
    'membership_number',
    'application_id',
    'reference_id',
    'transaction_id',
    'mobile_number',
    'amount',
    'status',
    'approved_at',
    'updated_at',
    'remote_response',
    'created_at',
] as $column) {
    $selectCols[] = cpd_table_has_column($conn, 'wallet_transactions', $column)
        ? 't.' . $column
        : 'NULL AS ' . $column;
}
$transactions_sql = "
    SELECT
        " . implode(",\n        ", $selectCols) . ",
        a.full_name,
        a.company_name,
        a.course_id
    FROM wallet_transactions t
    LEFT JOIN cpd_applications a ON a.id = t.application_id
    $where_sql
    ORDER BY t.created_at DESC, t.id DESC
";

$page = eca_pager_page();
$limit = eca_pager_limit();
$count_sql = "
    SELECT COUNT(*)
    FROM wallet_transactions t
    LEFT JOIN cpd_applications a ON a.id = t.application_id
    $where_sql
";
$count_stmt = $conn->prepare($count_sql);
if (!$count_stmt) {
    http_response_code(503);
    exit('Unable to load payment records. Please try again shortly.');
}
$count_params = $params;
bind_dynamic_params($count_stmt, $types, $count_params);
$count_stmt->execute();
$count_res = $count_stmt->get_result();
$txn_total = $count_res ? (int) ($count_res->fetch_row()[0] ?? 0) : 0;
$count_stmt->close();
$txn_pages = $txn_total > 0 ? max(1, (int) ceil($txn_total / $limit)) : 1;
$page = eca_pager_redirect_if_out_of_range($page, $txn_pages, $txn_total);
$txn_offset = ($page - 1) * $limit;
$transactions_sql .= ' LIMIT ' . (int) $limit . ' OFFSET ' . (int) $txn_offset;

$transactions_stmt = $conn->prepare($transactions_sql);

if (!$transactions_stmt) {
    http_response_code(503);
    exit('Unable to load payment records. Please try again shortly.');
}

$list_params = $params;
bind_dynamic_params($transactions_stmt, $types, $list_params);
$transactions_stmt->execute();
$transactions = $transactions_stmt->get_result();

/* =========================
   LOAD HEADER
========================= */
require_once "../header.php";
?>

<style>
:root{
    --theme:#1f4e79;
    --theme-dark:#163754;
    --theme-soft:#eef4fa;
    --line:#dbe5ef;
    --text:#1f2937;
    --muted:#6b7280;
    --bg:#f6f8fb;
    --card:#ffffff;
    --success-bg:#eaf8ef;
    --success-text:#166534;
    --danger-bg:#fff1f2;
    --danger-text:#b42318;
    --warning-bg:#fff7e8;
    --warning-text:#b54708;
    --shadow:0 10px 30px rgba(15,23,42,.06);
}

body{
    background:var(--bg);
    color:var(--text);
}

.payment-shell{
    padding:8px 0 25px;
}

.hero-card,
.filter-card,
.stat-card,
.table-card{
    background:var(--card);
    border:1px solid var(--line);
    border-radius:22px;
    box-shadow:var(--shadow);
}

.hero-card{
    padding:26px;
    margin-bottom:22px;
    color:#fff;
    border:none;
    background:
        radial-gradient(circle at top right, rgba(255,255,255,.18), transparent 30%),
        linear-gradient(135deg, #1f4e79, #163754);
}

.hero-title{
    font-size:28px;
    font-weight:900;
    margin-bottom:6px;
}

.hero-sub{
    font-size:14px;
    opacity:.92;
}

.filter-card,
.table-card{
    padding:22px;
}

.stat-card{
    padding:22px;
    height:100%;
}

.stat-label{
    font-size:12px;
    color:var(--muted);
    font-weight:800;
    text-transform:uppercase;
    letter-spacing:.04em;
}

.stat-number{
    font-size:30px;
    font-weight:900;
    color:var(--theme-dark);
    line-height:1.2;
    margin-top:8px;
}

.stat-money{
    color:var(--theme);
    font-weight:800;
    margin-top:4px;
}

.section-title{
    font-size:18px;
    font-weight:900;
    color:var(--theme-dark);
    margin-bottom:4px;
}

.section-sub{
    font-size:13px;
    color:var(--muted);
    margin-bottom:16px;
}

.form-label{
    font-weight:800;
    color:var(--theme-dark);
    margin-bottom:8px;
}

.form-control,
.form-select{
    min-height:46px;
    border-radius:14px;
    border:1px solid var(--line);
    box-shadow:none !important;
}

.form-control:focus,
.form-select:focus{
    border-color:var(--theme);
}

.btn-theme,
.btn-soft,
.btn-success-soft,
.btn-danger-soft,
.btn-warning-soft{
    min-height:42px;
    border-radius:12px;
    font-weight:800;
    border:none;
    padding:9px 14px;
}

.btn-theme{
    background:var(--theme);
    color:#fff;
}

.btn-theme:hover{
    background:var(--theme-dark);
    color:#fff;
}

.btn-soft{
    background:var(--theme-soft);
    color:var(--theme-dark);
    border:1px solid var(--line);
}

.btn-soft:hover{
    background:#ddeaf7;
    color:var(--theme-dark);
}

.btn-success-soft{
    background:#dff5e8;
    color:#166534;
    border:1px solid #cbeed8;
}

.btn-danger-soft{
    background:#ffe4e6;
    color:#b42318;
    border:1px solid #fecdd3;
}

.btn-warning-soft{
    background:#fff7e8;
    color:#b54708;
    border:1px solid #fed7aa;
}

.alert-clean{
    border:none;
    border-radius:16px;
    padding:14px 18px;
    font-weight:800;
    margin-bottom:18px;
}

.alert-clean.success{
    background:#eaf8ef;
    color:#166534;
}

.alert-clean.error{
    background:#fff1f2;
    color:#b42318;
}

.table-wrap{
    overflow-x:auto;
}

.payment-table{
    width:100%;
    border-collapse:separate;
    border-spacing:0 12px;
}

.payment-table thead th{
    background:transparent;
    color:var(--muted);
    font-size:12px;
    font-weight:900;
    text-transform:uppercase;
    letter-spacing:.04em;
    border:none;
    padding:0 14px 10px;
    white-space:nowrap;
}

.payment-table tbody td{
    padding:16px 14px;
    vertical-align:middle;
    background:#fff;
    border-top:1px solid #edf2f7;
    border-bottom:1px solid #edf2f7;
    white-space:nowrap;
}

.payment-table tbody td:first-child{
    border-left:1px solid #edf2f7;
    border-top-left-radius:16px;
    border-bottom-left-radius:16px;
}

.payment-table tbody td:last-child{
    border-right:1px solid #edf2f7;
    border-top-right-radius:16px;
    border-bottom-right-radius:16px;
}

.pay-main{
    font-weight:900;
    color:var(--theme-dark);
}

.pay-sub{
    color:var(--muted);
    font-size:12px;
    margin-top:3px;
}

.pay-badge{
    display:inline-flex;
    align-items:center;
    padding:8px 12px;
    border-radius:999px;
    font-size:12px;
    font-weight:900;
}

.pay-badge.approved{
    background:var(--success-bg);
    color:var(--success-text);
}

.pay-badge.pending{
    background:var(--warning-bg);
    color:var(--warning-text);
}

.pay-badge.failed{
    background:var(--danger-bg);
    color:var(--danger-text);
}

.action-group{
    display:flex;
    gap:7px;
    flex-wrap:wrap;
}

.response-box{
    background:#0f172a;
    color:#e5e7eb;
    border-radius:14px;
    padding:16px;
    max-height:460px;
    overflow:auto;
    font-size:12px;
    white-space:pre-wrap;
}

.empty-state{
    text-align:center;
    padding:34px;
    color:#64748b;
}

@media(max-width:991px){
    .hero-title{
        font-size:22px;
    }

    .stat-number{
        font-size:24px;
    }
}
</style>

<div class="container-fluid mt-4 payment-shell">

    <?php if (!empty($_SESSION['payment_success'])): ?>
        <div class="alert-clean success">
            <i class="fa-solid fa-circle-check me-2"></i>
            <?= h($_SESSION['payment_success']) ?>
        </div>
        <?php unset($_SESSION['payment_success']); ?>
    <?php endif; ?>

    <?php if (!empty($_SESSION['payment_error'])): ?>
        <div class="alert-clean error">
            <i class="fa-solid fa-triangle-exclamation me-2"></i>
            <?= h($_SESSION['payment_error']) ?>
        </div>
        <?php unset($_SESSION['payment_error']); ?>
    <?php endif; ?>

    <div class="hero-card">
        <div class="row g-4 align-items-center">
            <div class="col-lg-8">
                <div class="hero-title">
                    <i class="fa-solid fa-wallet me-2"></i>
                    MoMo Payment Transactions
                </div>
                <div class="hero-sub">
                    Track wallet transactions, approval status, membership numbers, application IDs, and payment references.
                </div>
            </div>

            <div class="col-lg-4 text-lg-end">
                <a href="payment.php" class="btn btn-light rounded-pill px-4 fw-bold">
                    <i class="fa-solid fa-rotate-right me-1"></i>
                    Refresh
                </a>
            </div>
        </div>
    </div>

    <div class="row g-3 mb-4">
        <div class="col-md-3">
            <div class="stat-card">
                <div class="stat-label">Total Transactions</div>
                <div class="stat-number"><?= (int)($stats['total_transactions'] ?? 0) ?></div>
                <div class="stat-money"><?= money($stats['total_amount'] ?? 0) ?></div>
            </div>
        </div>

        <div class="col-md-3">
            <div class="stat-card">
                <div class="stat-label">Approved</div>
                <div class="stat-number"><?= (int)($stats['approved_count'] ?? 0) ?></div>
                <div class="stat-money"><?= money($stats['approved_amount'] ?? 0) ?></div>
            </div>
        </div>

        <div class="col-md-3">
            <div class="stat-card">
                <div class="stat-label">Pending</div>
                <div class="stat-number"><?= (int)($stats['pending_count'] ?? 0) ?></div>
                <div class="stat-money"><?= money($stats['pending_amount'] ?? 0) ?></div>
            </div>
        </div>

        <div class="col-md-3">
            <div class="stat-card">
                <div class="stat-label">Failed</div>
                <div class="stat-number"><?= (int)($stats['failed_count'] ?? 0) ?></div>
                <div class="stat-money"><?= money($stats['failed_amount'] ?? 0) ?></div>
            </div>
        </div>
    </div>

    <div class="filter-card mb-4">
        <div class="section-title">Filter Payments</div>
        <div class="section-sub">Search by request ID, membership number, application ID, reference, phone, applicant, or company.</div>

        <form method="GET">
            <div class="row g-3 align-items-end">
                <div class="col-lg-3 col-md-6">
                    <label class="form-label">Search</label>
                    <input type="text" name="search" class="form-control" value="<?= h($search) ?>" placeholder="Search transaction">
                </div>

                <div class="col-lg-2 col-md-6">
                    <label class="form-label">Status</label>
                    <select name="status" class="form-select">
                        <option value="">All Statuses</option>
                        <option value="APPROVED" <?= $status_filter === 'APPROVED' ? 'selected' : '' ?>>Approved</option>
                        <option value="PENDING" <?= $status_filter === 'PENDING' ? 'selected' : '' ?>>Pending</option>
                        <option value="FAILED" <?= $status_filter === 'FAILED' ? 'selected' : '' ?>>Failed</option>
                    </select>
                </div>

                <div class="col-lg-2 col-md-6">
                    <label class="form-label">From Date</label>
                    <input type="date" name="from" class="form-control" value="<?= h($from_date) ?>">
                </div>

                <div class="col-lg-2 col-md-6">
                    <label class="form-label">To Date</label>
                    <input type="date" name="to" class="form-control" value="<?= h($to_date) ?>">
                </div>

                <div class="col-lg-3 col-md-12">
                    <div class="d-flex gap-2">
                        <button type="submit" class="btn btn-theme flex-fill">
                            <i class="fa-solid fa-filter me-1"></i>
                            Apply Filter
                        </button>

                        <a href="payment.php" class="btn btn-soft">
                            Clear
                        </a>
                    </div>
                </div>
            </div>
        </form>
    </div>

    <div class="table-card">
        <div class="d-flex justify-content-between align-items-start flex-wrap gap-3 mb-3">
            <div>
                <div class="section-title">Transaction Records</div>
                <div class="section-sub">Showing <?= (int) $txn_total ?> wallet transaction(s).</div>
            </div>
        </div>

        <div class="table-wrap">
            <table class="payment-table" id="paymentsTable" data-dash-server-page="1">
                <thead>
                    <tr>
                        <th>#</th>
                      
                        <th>Member / Application</th>
                        <th>Applicant</th>
                        <th>Mobile</th>
                        <th>Amount</th>
                        <th>Status</th>
                        <th>Approved At</th>
                        <th>Created</th>
                        <th>Response</th>
                        <th>Actions</th>
                    </tr>
                </thead>

                <tbody>
                <?php $paymentModals = []; ?>
                <?php if ($txn_total > 0): ?>
                    <?php $i = 1; ?>
                    <?php while ($row = $transactions->fetch_assoc()): ?>
                        <?php
                            $modal_id = "responseModal_" . (int)$row['id'];
                            $status = strtoupper(trim($row['status'] ?? 'PENDING'));
                            if (!empty($row['remote_response'])) {
                                $paymentModals[] = [
                                    'id' => $modal_id,
                                    'request_id' => (string) ($row['request_id'] ?? ''),
                                    'reference_id' => (string) ($row['reference_id'] ?? ''),
                                    'remote_response' => (string) ($row['remote_response'] ?? ''),
                                ];
                            }
                        ?>
                        <tr>
                            <td><?= $i++ ?></td>

                       

                            <td>
                                <div class="pay-main"><?= h($row['membership_number'] ?: 'N/A') ?></div>
                                <div class="pay-sub">Application ID: <?= h($row['application_id'] ?: 'N/A') ?></div>
                            </td>

                            <td>
                                <div class="pay-main"><?= h($row['full_name'] ?: 'N/A') ?></div>
                                <div class="pay-sub"><?= h($row['company_name'] ?: 'No company linked') ?></div>
                            </td>

                            <td><?= h($row['mobile_number'] ?: 'N/A') ?></td>

                            <td>
                                <strong><?= money($row['amount'] ?? 0) ?></strong>
                            </td>

                            <td><?= status_badge($row['status']) ?></td>

                            <td><?= h($row['approved_at'] ?: 'N/A') ?></td>

                            <td>
                                <div><?= h($row['created_at'] ?: 'N/A') ?></div>
                                <div class="pay-sub">Updated: <?= h($row['updated_at'] ?: 'N/A') ?></div>
                            </td>

                            <td>
                                <?php if (!empty($row['remote_response'])): ?>
                                    <button type="button" class="btn btn-soft btn-sm" data-bs-toggle="modal" data-bs-target="#<?= h($modal_id) ?>">
                                        <i class="fa-solid fa-eye me-1"></i>
                                        View
                                    </button>
                                <?php else: ?>
                                    <span class="text-muted small">No response</span>
                                <?php endif; ?>
                            </td>

                            <td>
                                <div class="action-group">
                                    <?php if ($status !== 'APPROVED'): ?>
                                        <form method="POST" class="m-0" onsubmit="return confirm('Mark this transaction as APPROVED?');">
                                            <?= cpd_csrf_input() ?>
                                            <input type="hidden" name="transaction_id" value="<?= (int)$row['id'] ?>">
                                            <input type="hidden" name="new_status" value="APPROVED">
                                            <button type="submit" name="update_status" class="btn btn-success-soft btn-sm">
                                                Approve
                                            </button>
                                        </form>
                                    <?php endif; ?>

                                    <?php if ($status !== 'FAILED'): ?>
                                        <form method="POST" class="m-0" onsubmit="return confirm('Mark this transaction as FAILED?');">
                                            <?= cpd_csrf_input() ?>
                                            <input type="hidden" name="transaction_id" value="<?= (int)$row['id'] ?>">
                                            <input type="hidden" name="new_status" value="FAILED">
                                            <button type="submit" name="update_status" class="btn btn-danger-soft btn-sm">
                                                Fail
                                            </button>
                                        </form>
                                    <?php endif; ?>

                                    <?php if ($status !== 'PENDING'): ?>
                                        <form method="POST" class="m-0" onsubmit="return confirm('Return this transaction to PENDING?');">
                                            <?= cpd_csrf_input() ?>
                                            <input type="hidden" name="transaction_id" value="<?= (int)$row['id'] ?>">
                                            <input type="hidden" name="new_status" value="PENDING">
                                            <button type="submit" name="update_status" class="btn btn-warning-soft btn-sm">
                                                Pending
                                            </button>
                                        </form>
                                    <?php endif; ?>
                                </div>
                            </td>
                        </tr>


                    <?php endwhile; ?>
                <?php endif; ?>
                </tbody>
            </table>
            <?php if ($txn_total <= 0): ?>
                <div class="empty-state mt-3">
                    <i class="fa-solid fa-wallet fa-2x mb-3"></i>
                    <div class="fw-bold">No payment transactions found.</div>
                    <div>Try changing the filters or checking if wallet transactions have been inserted.</div>
                </div>
            <?php endif; ?>
        </div>
        <?php eca_render_request_pager($page, $txn_pages, $txn_total, $limit); ?>
    </div>
</div>

<?php foreach (($paymentModals ?? []) as $modal): ?>
    <div class="modal fade" id="<?= h($modal['id']) ?>" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-centered">
            <div class="modal-content border-0 rounded-4 shadow-lg">
                <div class="modal-header text-white" style="background:linear-gradient(135deg,#1f4e79,#163754);">
                    <h5 class="modal-title fw-bold">
                        <i class="fa-solid fa-code me-2"></i>
                        Remote Response
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <strong>Request ID:</strong> <?= h($modal['request_id']) ?><br>
                        <strong>Reference ID:</strong> <?= h($modal['reference_id']) ?>
                    </div>
                    <pre class="response-box"><?= h($modal['remote_response']) ?></pre>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-soft" data-bs-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>
<?php endforeach; ?>

<link rel="stylesheet" href="https://cdn.datatables.net/1.13.7/css/dataTables.bootstrap5.min.css">
<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<script src="https://cdn.datatables.net/1.13.7/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.7/js/dataTables.bootstrap5.min.js"></script>

<script>
$(document).ready(function(){
    var $table = $('#paymentsTable');
    if (!$table.length) {
        return;
    }
    // DataTables does not support tbody colspan (tn/18). Only init on real data rows.
    var headCols = $table.find('thead tr:first th').length;
    var $rows = $table.find('tbody tr');
    if (headCols < 1 || !$rows.length) {
        return;
    }
    var bodyOk = true;
    $rows.each(function () {
        var $tr = $(this);
        if ($tr.find('td[colspan], th[colspan]').length) {
            bodyOk = false;
            return false;
        }
        if ($tr.children('td, th').length !== headCols) {
            bodyOk = false;
            return false;
        }
    });
    if (!bodyOk) {
        return;
    }
    $table.DataTable({
        paging: false,
        info: false,
        ordering: false,
        searching: false
    });
});
</script>

<?php
$transactions_stmt->close();
require_once "../footer.php";
?>