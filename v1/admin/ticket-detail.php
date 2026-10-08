<?php
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/_hub.php';
require_once __DIR__ . '/../includes/audit.php';
require_once __DIR__ . '/../includes/content.php';
require_once __DIR__ . '/../includes/mailer.php';
eca_admin_require('tickets.manage');
$id = (int) ($_GET['id'] ?? 0);
$conn = eca_admin_db();
if (!$conn || $id < 1) { eca_not_found('Ticket not found.'); }
$stmt = $conn->prepare('SELECT * FROM contact_messages WHERE id = ? LIMIT 1');
$stmt->execute([$id]);
$row = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$row) { eca_not_found('Ticket not found.'); }
if (empty($row['ticket_reference'])) {
    $ref = eca_next_ticket_reference($conn);
    $conn->prepare('UPDATE contact_messages SET ticket_reference = ?, status = COALESCE(status,"OPEN") WHERE id = ?')->execute([$ref, $id]);
    $stmt->execute([$id]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC) ?: $row;
}
$notice = '';
$noticeError = false;
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!eca_admin_csrf_ok($_POST['csrf_token'] ?? null)) {
        $notice = 'Your session expired. Please try again.';
        $noticeError = true;
    } else {
        $status = strtoupper(trim((string) ($_POST['status'] ?? 'OPEN')));
        $assigned = trim((string) ($_POST['assigned_to'] ?? ''));
        $reply = trim((string) ($_POST['admin_reply'] ?? ''));
        if (!in_array($status, ['OPEN', 'ASSIGNED', 'PENDING', 'RESOLVED'], true)) {
            $status = 'OPEN';
        }
        $conn->prepare('UPDATE contact_messages SET status = ?, assigned_to = ?, admin_reply = ? WHERE id = ?')
            ->execute([$status, $assigned, $reply, $id]);
        eca_audit('ticket.update', 'contact_messages', (string) $id, ['status' => $status]);
        $mailOk = true;
        if ($reply !== '' && !empty($row['email'])) {
            $mailOk = eca_mail_send(
                (string) $row['email'],
                'ECA ' . ($row['ticket_reference'] ?? ''),
                '<p>' . nl2br(htmlspecialchars($reply, ENT_QUOTES, 'UTF-8')) . '</p>'
            );
        }
        $stmt->execute([$id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC) ?: $row;
        if ($reply !== '' && !$mailOk) {
            $notice = 'Ticket saved, but the reply email could not be sent locally.';
            $noticeError = true;
        } else {
            $notice = 'Ticket saved.';
        }
    }
}
$csrf = eca_admin_csrf();
eca_admin_hub_start('Ticket', 'tickets');
?>
<div class="hub-hello"><h1 class="hub-hello-title"><?= eca_admin_h($row['ticket_reference'] ?? '') ?></h1></div>
<?php if ($notice): ?><p class="hub-card"<?= !empty($noticeError) ? ' style="border-color:#b42318;"' : '' ?>><?= eca_admin_h($notice) ?></p><?php endif; ?>
<div class="hub-card eca-form-panel">
    <div class="eca-apply-head">
        <h1>Ticket</h1>
        <a href="/admin/tickets.php">Back</a>
    </div>
    <h5>Message</h5>
    <div class="eca-field-grid">
        <div>
            <label class="form-label">From</label>
            <div class="eca-field-value"><?= eca_admin_h($row['name'] ?? '') ?></div>
        </div>
        <div>
            <label class="form-label">Subject</label>
            <div class="eca-field-value"><?= eca_admin_h($row['subject'] ?? '') ?></div>
        </div>
        <div class="eca-field-full">
            <label class="form-label">Message</label>
            <div class="eca-field-value eca-field-value-area"><?= nl2br(eca_admin_h($row['message'] ?? '')) ?></div>
        </div>
    </div>
</div>
<form class="hub-card eca-form-panel" method="post">
    <input type="hidden" name="csrf_token" value="<?= eca_admin_h($csrf) ?>">
    <h5>Response</h5>
    <div class="eca-field-grid">
        <div>
            <label class="form-label" for="ticket-status">Status</label>
            <select class="form-select" id="ticket-status" name="status">
                <?php foreach (['OPEN','ASSIGNED','PENDING','RESOLVED'] as $opt): ?>
                    <option value="<?= $opt ?>"<?= strtoupper((string) ($row['status'] ?? 'OPEN')) === $opt ? ' selected' : '' ?>><?= $opt ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div>
            <label class="form-label" for="ticket-assigned">Assign to</label>
            <input class="form-control" id="ticket-assigned" name="assigned_to" value="<?= eca_admin_h($row['assigned_to'] ?? '') ?>">
        </div>
        <div class="eca-field-full">
            <label class="form-label" for="ticket-reply">Response</label>
            <textarea class="form-control" id="ticket-reply" name="admin_reply" rows="4"><?= eca_admin_h($row['admin_reply'] ?? '') ?></textarea>
        </div>
    </div>
    <div class="eca-apply-nav">
        <a class="eca-btn-back" href="/admin/tickets.php">Back</a>
        <button class="hub-btn eca-btn-next" type="submit">Save</button>
    </div>
</form>
<?php eca_admin_hub_end(); ?>
