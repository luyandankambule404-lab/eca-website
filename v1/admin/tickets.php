<?php
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/_hub.php';
require_once __DIR__ . '/../includes/content.php';
require_once __DIR__ . '/../includes/pagination.php';
eca_admin_require('tickets.manage');
header('Cache-Control: no-store');
$conn = eca_admin_db();
$status = strtoupper(trim((string) ($_GET['status'] ?? '')));
$search = trim((string) ($_GET['search'] ?? ''));
$page = eca_pager_page();
$limit = eca_pager_limit();
$rows = [];
$total = 0;
$totalPages = 1;
if ($conn) {
    try {
        $where = ' WHERE 1=1';
        $params = [];
        if ($status !== '') {
            $where .= ' AND UPPER(COALESCE(status,"OPEN")) = ?';
            $params[] = $status;
        }
        if ($search !== '') {
            $where .= ' AND (ticket_reference LIKE ? OR name LIKE ? OR subject LIKE ? OR status LIKE ?)';
            $like = '%' . $search . '%';
            array_push($params, $like, $like, $like, $like);
        }
        $paged = eca_paged_query(
            $conn,
            'SELECT COUNT(*) FROM contact_messages' . $where,
            'SELECT * FROM contact_messages' . $where . ' ORDER BY id DESC',
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
                'SELECT ticket_reference, name, email, subject, status, created_at FROM contact_messages' . $where . ' ORDER BY id DESC',
                $params
            );
            $csv = [];
            foreach ($exportRows as $row) {
                $csv[] = [
                    $row['ticket_reference'] ?? '',
                    $row['name'] ?? '',
                    $row['email'] ?? '',
                    $row['subject'] ?? '',
                    $row['status'] ?? '',
                    $row['created_at'] ?? '',
                ];
            }
            eca_admin_send_csv('eca-tickets', ['Ticket', 'Name', 'Email', 'Subject', 'Status', 'Created'], $csv, 'contact_messages');
        }
    } catch (Throwable $e) {
        $rows = [];
    }
}
eca_admin_hub_start('Tickets', 'tickets');
?>
<div class="hub-hello"><h1 class="hub-hello-title">Contact / support (<?= (int) $total ?>)</h1><p>Messages from the public contact form. Ticket numbers are assigned on submit.</p></div>
<form class="hub-card" method="get" style="display:flex;gap:10px;flex-wrap:wrap;align-items:center;margin-bottom:12px;">
    <input type="search" name="search" data-hub-search value="<?= eca_admin_h($search) ?>" placeholder="Type a letter to filter ticket, name or subject" autocomplete="off" style="padding:10px 12px;border-radius:10px;border:1px solid #d0d5dd;">
    <span data-hub-search-count class="hub-live-search-count"></span>
    <select name="status" onchange="this.form.submit()" style="padding:10px 12px;border-radius:10px;border:1px solid #d0d5dd;">
        <option value="">All</option>
        <?php foreach (['OPEN','ASSIGNED','PENDING','RESOLVED'] as $opt): ?>
            <option value="<?= $opt ?>"<?= $status === $opt ? ' selected' : '' ?>><?= $opt ?></option>
        <?php endforeach; ?>
    </select>
    <button class="hub-btn" type="submit">Search</button>
    <?= eca_admin_csv_button() ?>
</form>
<div class="hub-card eca-table-panel">
<h5>Tickets</h5>
<table class="hub-table" data-dash-server-page="1"><thead><tr><th>Ticket</th><th>Name</th><th>Subject</th><th>Status</th></tr></thead><tbody>
<?php foreach ($rows as $row): ?>
<tr>
    <td><a href="/admin/ticket-detail.php?id=<?= (int) $row['id'] ?>"><?= eca_admin_h($row['ticket_reference'] ?? ('#' . $row['id'])) ?></a></td>
    <td><?= eca_admin_h($row['name'] ?? '') ?></td>
    <td><?= eca_admin_h($row['subject'] ?? '') ?></td>
    <td><?= eca_admin_h($row['status'] ?? 'OPEN') ?></td>
</tr>
<?php endforeach; ?>
<?php if (!$rows): ?><tr data-hub-empty-row><td colspan="4">No messages.</td></tr><?php endif; ?>
</tbody></table></div>
<?php
eca_render_request_pager($page, $totalPages, $total, $limit);
eca_admin_hub_end();
?>
