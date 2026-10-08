<?php
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/_hub.php';
require_once __DIR__ . '/../includes/audit.php';
require_once __DIR__ . '/../includes/content.php';
eca_admin_require('content.manage');
$conn = eca_admin_db();
if (!$conn) { eca_server_error(); }
$id = (int) ($_GET['id'] ?? 0);
$row = ['title' => '', 'summary' => '', 'venue' => '', 'starts_at' => '', 'ends_at' => '', 'capacity' => '', 'status' => 'DRAFT'];
if ($id > 0) {
    $stmt = $conn->prepare('SELECT * FROM events WHERE id = ? LIMIT 1');
    $stmt->execute([$id]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC) ?: $row;
}
$notice = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && eca_admin_csrf_ok($_POST['csrf_token'] ?? null)) {
    $title = trim((string) ($_POST['title'] ?? ''));
    $summary = trim((string) ($_POST['summary'] ?? ''));
    $venue = trim((string) ($_POST['venue'] ?? ''));
    $starts = trim((string) ($_POST['starts_at'] ?? '')) ?: null;
    $ends = trim((string) ($_POST['ends_at'] ?? '')) ?: null;
    $capacity = ($_POST['capacity'] ?? '') !== '' ? (int) $_POST['capacity'] : null;
    $status = strtoupper((string) ($_POST['status'] ?? 'DRAFT'));
    if (!in_array($status, eca_content_statuses(), true)) {
        $status = 'DRAFT';
    }
    if ($title === '') {
        $notice = 'Title is required.';
    } elseif ($id > 0) {
        $conn->prepare('UPDATE events SET title=?, summary=?, venue=?, starts_at=?, ends_at=?, capacity=?, status=? WHERE id=?')
            ->execute([$title, $summary, $venue, $starts, $ends, $capacity, $status, $id]);
        eca_audit('event.update', 'events', (string) $id);
        require_once __DIR__ . '/../includes/admin-ops.php';
        eca_audit_content_status('events', (string) $id, $status, $status === 'PUBLISHED');
        $notice = 'Event saved.';
    } else {
        $conn->prepare('INSERT INTO events (title, summary, venue, starts_at, ends_at, capacity, status) VALUES (?,?,?,?,?,?,?)')
            ->execute([$title, $summary, $venue, $starts, $ends, $capacity, $status]);
        $id = (int) $conn->lastInsertId();
        eca_audit('event.create', 'events', (string) $id);
        require_once __DIR__ . '/../includes/admin-ops.php';
        eca_audit_content_status('events', (string) $id, $status, $status === 'PUBLISHED');
        header('Location: /admin/event-edit.php?id=' . $id);
        exit;
    }
    $stmt = $conn->prepare('SELECT * FROM events WHERE id = ? LIMIT 1');
    $stmt->execute([$id]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC) ?: $row;
}
$regs = [];
if ($id > 0) {
    $regs = $conn->prepare('SELECT * FROM event_registrations WHERE event_id = ? ORDER BY id DESC LIMIT 100');
    $regs->execute([$id]);
    $regs = $regs->fetchAll(PDO::FETCH_ASSOC);
}
$csrf = eca_admin_csrf();
eca_admin_hub_start('Edit event', 'events');
?>
<div class="hub-hello"><h1 class="hub-hello-title"><?= $id ? 'Edit event' : 'Add event' ?></h1></div>
<?php if ($notice): ?><p class="hub-card"><?= eca_admin_h($notice) ?></p><?php endif; ?>
<form class="hub-card" method="post">
    <input type="hidden" name="csrf_token" value="<?= eca_admin_h($csrf) ?>">
    <p><label>Title<br><input name="title" value="<?= eca_admin_h($row['title'] ?? '') ?>" class="form-control"></label></p>
    <p><label>Summary<br><textarea name="summary" rows="3" class="form-control"><?= eca_admin_h($row['summary'] ?? '') ?></textarea></label></p>
    <p><label>Venue<br><input name="venue" value="<?= eca_admin_h($row['venue'] ?? '') ?>" class="form-control"></label></p>
    <p><label>Starts<br><input name="starts_at" value="<?= eca_admin_h($row['starts_at'] ?? '') ?>" class="form-control"></label></p>
    <p><label>Ends<br><input name="ends_at" value="<?= eca_admin_h($row['ends_at'] ?? '') ?>" class="form-control"></label></p>
    <p><label>Capacity<br><input name="capacity" type="number" min="0" value="<?= eca_admin_h($row['capacity'] ?? '') ?>" class="form-control"></label></p>
    <p><label>Status
        <select name="status" class="form-control">
            <?php foreach (eca_content_statuses() as $opt): ?>
                <option value="<?= $opt ?>"<?= ($row['status'] ?? '') === $opt ? ' selected' : '' ?>><?= $opt ?></option>
            <?php endforeach; ?>
        </select>
    </label></p>
    <button class="hub-btn" type="submit">Save</button>
    <a class="hub-home-link" href="/admin/events.php">Back</a>
</form>
<?php if ($regs): ?>
<div class="hub-card eca-table-panel">
<h5>Registrations</h5>
<table class="hub-table">
    <thead><tr><th>Name</th><th>Email</th><th>Membership</th></tr></thead>
    <tbody>
    <?php foreach ($regs as $reg): ?>
        <tr>
            <td><?= eca_admin_h($reg['name'] ?? '') ?></td>
            <td><?= eca_admin_h($reg['email'] ?? '') ?></td>
            <td><?= eca_admin_h($reg['membership_number'] ?? '') ?></td>
        </tr>
    <?php endforeach; ?>
    </tbody>
</table>
</div>
<?php endif; ?>
<?php eca_admin_hub_end(); ?>
