<?php
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/_hub.php';
require_once __DIR__ . '/../includes/portal-db.php';
require_once __DIR__ . '/../includes/audit.php';
require_once __DIR__ . '/../includes/pagination.php';
require_once __DIR__ . '/../includes/documents.php';
require_once __DIR__ . '/../includes/membership.php';
eca_admin_require('documents.manage');
header('Cache-Control: no-store');

$conn = eca_portal_pdo(false);
$notice = '';
if ($conn && $_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!eca_admin_csrf_ok($_POST['csrf_token'] ?? null)) {
        if (eca_admin_wants_json()) {
            eca_admin_json(['ok' => false, 'error' => 'session'], 403);
        }
    } else {
        $id = (int) ($_POST['id'] ?? 0);
        if ($id > 0) {
            $adminEmail = (string) (eca_admin_user()['email'] ?? '');
            $conn->prepare('UPDATE tbl_client_documents SET reviewed_at = NOW(), reviewed_by = ? WHERE id = ?')
                ->execute([$adminEmail, $id]);
            eca_audit('document.review', 'tbl_client_documents', (string) $id);
            $notice = 'Document marked reviewed.';
            if (eca_admin_wants_json()) {
                eca_admin_json(['ok' => true, 'id' => $id]);
            }
        } elseif (eca_admin_wants_json()) {
            eca_admin_json(['ok' => false, 'error' => 'document'], 400);
        }
    }
}
$search = trim((string) ($_GET['search'] ?? ''));
$review = strtolower(trim((string) ($_GET['review'] ?? '')));
$page = eca_pager_page();
$limit = eca_pager_limit();
$rows = [];
$total = 0;
$totalPages = 1;
if ($conn) {
    $where = ' WHERE 1=1';
    $params = [];
    if ($review === 'pending') {
        $where .= ' AND d.reviewed_at IS NULL';
    } elseif ($review === 'reviewed') {
        $where .= ' AND d.reviewed_at IS NOT NULL';
    }
    if ($search !== '') {
        $where .= ' AND (c.TradingName LIKE ? OR c.MembershipNumber LIKE ? OR d.document_type LIKE ? OR d.original_name LIKE ? OR d.reviewed_by LIKE ?)';
        $like = '%' . $search . '%';
        array_push($params, $like, $like, $like, $like, $like);
    }
    $from = 'tbl_client_documents d LEFT JOIN tbl_client c ON c.client_id = d.client_id';
    $paged = eca_paged_query(
        $conn,
        'SELECT COUNT(*) FROM ' . $from . $where,
        'SELECT d.id, d.client_id, d.document_type, d.original_name, d.file_name, d.uploaded_at, d.reviewed_at, d.reviewed_by, c.TradingName, c.MembershipNumber
         FROM ' . $from . $where . ' ORDER BY d.id DESC',
        $params,
        $page,
        $limit
    );
    $rows = $paged['rows'];
    $total = $paged['total'];
    $page = $paged['page'];
    $totalPages = $paged['pages'];
    if (eca_admin_export_requested()) {
        eca_admin_require_csv_export();
        $exportRows = eca_admin_export_rows(
            $conn,
            'SELECT d.id, d.document_type, d.original_name, d.uploaded_at, d.reviewed_at, d.reviewed_by, c.TradingName, c.MembershipNumber
             FROM ' . $from . $where . ' ORDER BY d.id DESC',
            $params
        );
        $csv = [];
        foreach ($exportRows as $row) {
            $csv[] = [
                $row['MembershipNumber'] ?? '',
                $row['TradingName'] ?? '',
                $row['document_type'] ?? '',
                $row['original_name'] ?? '',
                empty($row['reviewed_at']) ? 'Pending' : 'Reviewed',
                $row['reviewed_by'] ?? '',
                $row['uploaded_at'] ?? '',
            ];
        }
        eca_admin_send_csv('eca-documents', ['Membership', 'Company', 'Type', 'File name', 'Review', 'Reviewed by', 'Uploaded'], $csv, 'tbl_client_documents');
    }
}
$csrf = eca_admin_csrf();
eca_admin_hub_start('Documents', 'documents');
?>
<div class="hub-hello">
    <h1 class="hub-hello-title">Document verification (<?= (int) $total ?>)</h1>
    <p>Open a file in the browser to review it. Downloads stay behind sign-in. Storage paths are not shown.</p>
</div>
<?php if ($notice): ?><p class="hub-card"><?= eca_admin_h($notice) ?></p><?php endif; ?>
<?php
$docsReturn = '/admin/documents.php';
$docsQuery = array_filter(['search' => $search, 'review' => $review, 'page' => $page > 1 ? $page : '']);
if ($docsQuery) {
    $docsReturn .= '?' . http_build_query($docsQuery);
}
?>
<form class="hub-card" method="get" style="display:flex;gap:10px;flex-wrap:wrap;align-items:center;margin-bottom:12px;">
    <input type="search" name="search" data-hub-search value="<?= eca_admin_h($search) ?>" placeholder="Member, type or file name" autocomplete="off" style="padding:10px 12px;border-radius:10px;border:1px solid #d0d5dd;">
    <select name="review" onchange="this.form.submit()" style="padding:10px 12px;border-radius:10px;border:1px solid #d0d5dd;">
        <option value="">All reviews</option>
        <option value="pending"<?= $review === 'pending' ? ' selected' : '' ?>>Pending</option>
        <option value="reviewed"<?= $review === 'reviewed' ? ' selected' : '' ?>>Reviewed</option>
    </select>
    <button class="hub-btn" type="submit">Search</button>
    <?= eca_admin_csv_button() ?>
</form>
<div class="hub-card eca-table-panel">
    <h5>Documents</h5>
    <table class="hub-table" data-dash-server-page="1">
        <thead><tr><th>Member</th><th>Document</th><th>Status</th><th></th></tr></thead>
        <tbody>
        <?php foreach ($rows as $row): ?>
            <tr>
                <td><?php if (!empty($row['client_id'])): ?><a href="/admin/member-detail.php?id=<?= (int) $row['client_id'] ?>"><?= eca_admin_h($row['MembershipNumber'] ?? $row['TradingName'] ?? 'Member') ?></a><?php else: ?><?= eca_admin_h($row['MembershipNumber'] ?? $row['TradingName'] ?? '—') ?><?php endif; ?></td>
                <td>
                    <span class="hub-doc-type"><?= eca_admin_h(eca_document_type_label((string) ($row['document_type'] ?? ''))) ?></span>
                    <span class="hub-doc-file"><?= eca_admin_h($row['original_name'] ?: 'On file') ?></span>
                </td>
                <td>
                    <?php eca_admin_document_status_html($row['reviewed_at'] ?? null); ?>
                    <?php if (!empty($row['reviewed_at']) && !empty($row['reviewed_by'])): ?>
                        <span class="hub-doc-file"><?= eca_admin_h($row['reviewed_by']) ?></span>
                    <?php endif; ?>
                </td>
                <td><?php eca_admin_document_actions($row, $csrf, [
                    'can_review' => empty($row['reviewed_at']),
                    'return' => $docsReturn,
                ]); ?></td>
            </tr>
        <?php endforeach; ?>
        <?php if (!$rows): ?><tr data-hub-empty-row><td colspan="4">No documents.</td></tr><?php endif; ?>
        </tbody>
    </table>
</div>
<?php
eca_render_request_pager($page, $totalPages, $total, $limit);
eca_admin_hub_end();
?>
