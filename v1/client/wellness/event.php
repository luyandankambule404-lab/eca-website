<?php
require_once __DIR__ . '/../_shell.php';
require_once __DIR__ . '/../../includes/wellness.php';
require_once __DIR__ . '/../../includes/authz.php';
require_once __DIR__ . '/../../includes/audit.php';

$member = eca_portal_member();
$GLOBALS['eca_member'] = $member;
eca_require_permission('wellness.view');
$conn = eca_wellness_db();
$id = (int) ($_GET['id'] ?? 0);
$notice = '';
$error = '';

if (!$conn || $id <= 0) {
    eca_error_page(404, 'Not found', 'Event not found.');
}

$stmt = $conn->prepare("SELECT * FROM wellness_events WHERE id = ? AND status = 'PUBLISHED' LIMIT 1");
$stmt->execute([$id]);
$event = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$event) {
    eca_error_page(404, 'Not found', 'Event not found.');
}

$membership = trim((string) ($member['membership'] ?? ''));
$registered = $membership !== '' && eca_wellness_member_registered($conn, $id, $membership);
$regCount = eca_wellness_event_registration_count($conn, $id);

$csrf = eca_csrf_token();
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'register') {
    if (!eca_csrf_ok($_POST['csrf_token'] ?? null)) {
        $error = 'Your session expired. Please try again.';
    } else {
        [$ok, $msg] = eca_wellness_register_member($conn, $event, $member);
        if ($ok) {
            $notice = $msg;
            $registered = true;
            $regCount = eca_wellness_event_registration_count($conn, $id);
        } else {
            $error = $msg;
        }
    }
}

[$canRegister] = eca_wellness_can_register($event, $conn, $membership);

eca_portal_start((string) $event['title'], 'wellness');
eca_wellness_member_subnav('events');
?>
<div class="hub-hello"><h1 class="hub-hello-title"><?= eca_h($event['title']) ?></h1></div>
<?php if ($notice): ?><p class="hub-card" style="border-color:#12b76a;"><?= eca_h($notice) ?></p><?php endif; ?>
<?php if ($error): ?><p class="hub-card" style="border-color:#d50d0e;"><?= eca_h($error) ?></p><?php endif; ?>
<div class="hub-card">
    <?php if (!empty($event['description'])): ?>
        <p><?= nl2br(eca_h($event['description'])) ?></p>
    <?php endif; ?>
    <ul>
        <li><strong>When:</strong> <?= eca_h($event['starts_at'] ?? 'TBC') ?><?php if (!empty($event['ends_at'])): ?> — <?= eca_h($event['ends_at']) ?><?php endif; ?></li>
        <li><strong>Venue:</strong> <?= eca_h($event['venue'] ?? '—') ?></li>
        <li><strong>Location:</strong> <?= eca_h($event['location'] ?? '—') ?></li>
        <li><strong>Organizer:</strong> <?= eca_h($event['organizer'] ?? 'ECA') ?></li>
        <?php if (!empty($event['contact_info'])): ?>
            <li><strong>Contact:</strong> <?= eca_h($event['contact_info']) ?></li>
        <?php endif; ?>
        <li><strong>Registration:</strong> <?= !empty($event['registration_open']) ? 'Open' : 'Closed' ?>
            <?php if (!empty($event['capacity'])): ?> · <?= (int) $regCount ?> / <?= (int) $event['capacity'] ?> places<?php endif; ?>
        </li>
    </ul>
    <?php if ($registered): ?>
        <p><strong>You are registered.</strong> <a href="/client/wellness/activities.php">My wellness activities</a></p>
    <?php elseif ($canRegister): ?>
        <form method="post">
            <input type="hidden" name="csrf_token" value="<?= eca_h($csrf) ?>">
            <input type="hidden" name="action" value="register">
            <button class="hub-btn" type="submit">Register for this event</button>
        </form>
    <?php endif; ?>
    <p><a href="/client/wellness/events.php">Back to events</a></p>
</div>
<?php eca_portal_end(); ?>
