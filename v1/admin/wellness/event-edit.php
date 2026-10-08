<?php
require_once __DIR__ . '/../auth.php';
require_once __DIR__ . '/../_hub.php';
require_once __DIR__ . '/../../includes/wellness.php';
require_once __DIR__ . '/../../includes/audit.php';
eca_admin_require('wellness.manage');
$conn = eca_wellness_db();
if (!$conn) {
    eca_server_error();
}
$id = (int) ($_GET['id'] ?? 0);
$row = [
    'title' => '', 'description' => '', 'venue' => '', 'location' => '', 'organizer' => '',
    'contact_info' => '', 'starts_at' => '', 'ends_at' => '', 'capacity' => '',
    'status' => 'DRAFT', 'registration_open' => 1,
];
if ($id > 0) {
    $stmt = $conn->prepare('SELECT * FROM wellness_events WHERE id = ? LIMIT 1');
    $stmt->execute([$id]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC) ?: $row;
}
$notice = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && eca_admin_csrf_ok($_POST['csrf_token'] ?? null)) {
    $title = trim((string) ($_POST['title'] ?? ''));
    $desc = trim((string) ($_POST['description'] ?? ''));
    $venue = trim((string) ($_POST['venue'] ?? ''));
    $location = trim((string) ($_POST['location'] ?? ''));
    $organizer = trim((string) ($_POST['organizer'] ?? ''));
    $contact = trim((string) ($_POST['contact_info'] ?? ''));
    $starts = trim((string) ($_POST['starts_at'] ?? '')) ?: null;
    $ends = trim((string) ($_POST['ends_at'] ?? '')) ?: null;
    $capacity = ($_POST['capacity'] ?? '') !== '' ? (int) $_POST['capacity'] : null;
    $status = strtoupper((string) ($_POST['status'] ?? 'DRAFT'));
    if (!in_array($status, eca_content_statuses(), true)) {
        $status = 'DRAFT';
    }
    $regOpen = !empty($_POST['registration_open']) ? 1 : 0;
    $banner = trim((string) ($row['banner_path'] ?? ''));
    if (!empty($_FILES['banner']['tmp_name'])) {
        $stored = eca_wellness_store_upload($_FILES['banner'], 'evt');
        if ($stored) {
            $banner = $stored;
        }
    }
    if ($title === '') {
        $notice = 'Title is required.';
    } else {
        if ($id > 0) {
            $conn->prepare(
                'UPDATE wellness_events SET title=?, description=?, venue=?, location=?, organizer=?, contact_info=?, starts_at=?, ends_at=?, capacity=?, banner_path=?, status=?, registration_open=? WHERE id=?'
            )->execute([$title, $desc, $venue, $location, $organizer, $contact, $starts, $ends, $capacity, $banner ?: null, $status, $regOpen, $id]);
            eca_audit('wellness.event.updated', 'wellness_events', (string) $id);
            $notice = 'Event saved.';
        } else {
            $conn->prepare(
                'INSERT INTO wellness_events (title, description, venue, location, organizer, contact_info, starts_at, ends_at, capacity, banner_path, status, registration_open)
                 VALUES (?,?,?,?,?,?,?,?,?,?,?,?)'
            )->execute([$title, $desc, $venue, $location, $organizer, $contact, $starts, $ends, $capacity, $banner ?: null, $status, $regOpen]);
            $id = (int) $conn->lastInsertId();
            eca_audit('wellness.event.created', 'wellness_events', (string) $id);
            header('Location: /admin/wellness/event-edit.php?id=' . $id);
            exit;
        }
        $stmt = $conn->prepare('SELECT * FROM wellness_events WHERE id = ? LIMIT 1');
        $stmt->execute([$id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC) ?: $row;
    }
}
$regs = [];
if ($id > 0) {
    $regs = $conn->prepare('SELECT * FROM wellness_event_registrations WHERE event_id = ? ORDER BY id DESC LIMIT 200');
    $regs->execute([$id]);
    $regs = $regs->fetchAll(PDO::FETCH_ASSOC);
}
$csrf = eca_admin_csrf();
eca_admin_hub_start($id ? 'Edit event' : 'Add event', 'wellness');
eca_wellness_admin_subnav('events');
?>
<div class="hub-hello"><h1 class="hub-hello-title"><?= $id ? 'Edit wellness event' : 'Add wellness event' ?></h1></div>
<?php if ($notice): ?><p class="hub-card"><?= eca_admin_h($notice) ?></p><?php endif; ?>
<form class="hub-card" method="post" enctype="multipart/form-data">
    <input type="hidden" name="csrf_token" value="<?= eca_admin_h($csrf) ?>">
    <p><label>Title<br><input name="title" required value="<?= eca_admin_h($row['title'] ?? '') ?>" style="width:100%;padding:10px 12px;border-radius:10px;border:1px solid #d0d5dd;"></label></p>
    <p><label>Description<br><textarea name="description" rows="4" style="width:100%;padding:10px 12px;border-radius:10px;border:1px solid #d0d5dd;"><?= eca_admin_h($row['description'] ?? '') ?></textarea></label></p>
    <p><label>Venue<br><input name="venue" value="<?= eca_admin_h($row['venue'] ?? '') ?>" style="width:100%;padding:10px 12px;border-radius:10px;border:1px solid #d0d5dd;"></label></p>
    <p><label>Location<br><input name="location" value="<?= eca_admin_h($row['location'] ?? '') ?>" style="width:100%;padding:10px 12px;border-radius:10px;border:1px solid #d0d5dd;"></label></p>
    <p><label>Organizer<br><input name="organizer" value="<?= eca_admin_h($row['organizer'] ?? '') ?>" style="width:100%;padding:10px 12px;border-radius:10px;border:1px solid #d0d5dd;"></label></p>
    <p><label>Contact information<br><input name="contact_info" value="<?= eca_admin_h($row['contact_info'] ?? '') ?>" style="width:100%;padding:10px 12px;border-radius:10px;border:1px solid #d0d5dd;"></label></p>
    <p><label>Starts (YYYY-MM-DD HH:MM:SS)<br><input name="starts_at" value="<?= eca_admin_h($row['starts_at'] ?? '') ?>" style="width:100%;padding:10px 12px;border-radius:10px;border:1px solid #d0d5dd;"></label></p>
    <p><label>Ends<br><input name="ends_at" value="<?= eca_admin_h($row['ends_at'] ?? '') ?>" style="width:100%;padding:10px 12px;border-radius:10px;border:1px solid #d0d5dd;"></label></p>
    <p><label>Maximum participants (blank = unlimited)<br><input name="capacity" type="number" min="0" value="<?= eca_admin_h($row['capacity'] ?? '') ?>" style="padding:10px 12px;border-radius:10px;border:1px solid #d0d5dd;"></label></p>
    <p><label>Banner image<br><input type="file" name="banner" accept=".jpg,.jpeg,.png,.webp"></label></p>
    <p><label><input type="checkbox" name="registration_open" value="1"<?= !empty($row['registration_open']) ? ' checked' : '' ?>> Registration open</label></p>
    <p><label>Status<br>
        <select name="status" style="padding:10px 12px;border-radius:10px;border:1px solid #d0d5dd;">
            <?php foreach (eca_content_statuses() as $st): ?>
                <option value="<?= $st ?>"<?= ($row['status'] ?? '') === $st ? ' selected' : '' ?>><?= $st ?></option>
            <?php endforeach; ?>
        </select>
    </label></p>
    <button class="hub-btn" type="submit">Save</button>
    <a class="hub-home-link" href="/admin/wellness/events.php">Back</a>
</form>
<?php if ($regs): ?>
<div class="hub-card eca-table-panel">
<h5>Registrations</h5>
<table class="hub-table">
    <thead><tr><th>Name</th><th>Email</th><th>Membership</th><th>Registered</th></tr></thead>
    <tbody>
    <?php foreach ($regs as $reg): ?>
        <tr>
            <td><?= eca_admin_h($reg['member_name'] ?? '') ?></td>
            <td><?= eca_admin_h($reg['member_email'] ?? '') ?></td>
            <td><?= eca_admin_h($reg['membership_number'] ?? '') ?></td>
            <td><?= eca_admin_h($reg['created_at'] ?? '') ?></td>
        </tr>
    <?php endforeach; ?>
    </tbody>
</table>
</div>
<?php endif; ?>
<?php eca_admin_hub_end(); ?>
