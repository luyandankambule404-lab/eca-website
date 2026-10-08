<?php
require_once __DIR__ . '/../_shell.php';
require_once __DIR__ . '/../../includes/wellness.php';
require_once __DIR__ . '/../../includes/authz.php';

$member = eca_portal_member();
$GLOBALS['eca_member'] = $member;
eca_require_permission('wellness.view');
$conn = eca_wellness_db();
$membership = trim((string) ($member['membership'] ?? ''));
$rows = [];

if ($conn && $membership !== '') {
    $stmt = $conn->prepare(
        'SELECT r.*, e.title, e.starts_at, e.venue, e.status AS event_status
         FROM wellness_event_registrations r
         INNER JOIN wellness_events e ON e.id = r.event_id
         WHERE r.membership_number = ?
         ORDER BY r.created_at DESC
         LIMIT 50'
    );
    $stmt->execute([$membership]);
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
}

eca_portal_start('My wellness activities', 'wellness');
eca_wellness_member_subnav('activities');
?>
<div class="hub-hello"><h1 class="hub-hello-title">My wellness activities</h1></div>
<div class="hub-card eca-table-panel">
<?php if ($rows): ?>
<table class="hub-table">
    <thead><tr><th>Event</th><th>When</th><th>Venue</th><th>Registered</th><th></th></tr></thead>
    <tbody>
    <?php foreach ($rows as $row): ?>
        <tr>
            <td><?= eca_h($row['title']) ?></td>
            <td><?= eca_h($row['starts_at'] ?? '—') ?></td>
            <td><?= eca_h($row['venue'] ?? '—') ?></td>
            <td><?= eca_h($row['created_at'] ?? '') ?></td>
            <td><a href="/client/wellness/event.php?id=<?= (int) $row['event_id'] ?>">View</a></td>
        </tr>
    <?php endforeach; ?>
    </tbody>
</table>
<?php else: ?>
<p style="padding:16px;">You have not registered for any wellness events yet. <a href="/client/wellness/events.php">Browse events</a></p>
<?php endif; ?>
</div>
<?php eca_portal_end(); ?>
