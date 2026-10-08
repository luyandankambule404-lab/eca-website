<?php
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/includes/session.php';
require_once __DIR__ . '/includes/public-page.php';
$db = new Database();
$conn = $db->getConnection(false);
$notice = '';
if ($conn && $_SERVER['REQUEST_METHOD'] === 'POST') {
    eca_require_public_post();
    $now = time();
    $registrations = array_values(array_filter(
        (array) ($_SESSION['eca_event_registrations'] ?? []),
        static fn ($timestamp): bool => is_int($timestamp) && $timestamp > $now - 3600
    ));
    if (count($registrations) >= 10) {
        http_response_code(429);
        $notice = 'Too many registration attempts. Please wait and try again.';
    } else {
        $registrations[] = $now;
        $_SESSION['eca_event_registrations'] = $registrations;
    }
    $eventId = (int) ($_POST['event_id'] ?? 0);
    $name = trim((string) ($_POST['name'] ?? ''));
    $email = trim((string) ($_POST['email'] ?? ''));
    $membership = trim((string) ($_POST['membership'] ?? ($_SESSION['eca_member']['membership'] ?? '')));
    if ($notice === '' && $eventId > 0 && $name !== '' && filter_var($email, FILTER_VALIDATE_EMAIL)) {
        try {
            $conn->beginTransaction();
            $ev = $conn->prepare("SELECT id, capacity FROM events WHERE id = ? AND status = 'PUBLISHED' LIMIT 1 FOR UPDATE");
            $ev->execute([$eventId]);
            $event = $ev->fetch(PDO::FETCH_ASSOC);
            if (!$event) {
                $notice = 'That event is not open for registration.';
            } else {
                $cstmt = $conn->prepare('SELECT COUNT(*) FROM event_registrations WHERE event_id = ?');
                $cstmt->execute([$eventId]);
                $count = (int) $cstmt->fetchColumn();
                $cap = $event['capacity'] !== null ? (int) $event['capacity'] : 0;
                if ($cap > 0 && $count >= $cap) {
                    $notice = 'This event is full.';
                } else {
                    $conn->prepare('INSERT INTO event_registrations (event_id, name, email, membership_number) VALUES (?,?,?,?)')
                        ->execute([$eventId, $name, $email, $membership]);
                    $notice = 'Registration saved.';
                }
            }
            $conn->commit();
        } catch (Throwable $e) {
            if ($conn->inTransaction()) {
                $conn->rollBack();
            }
            error_log('Event registration failed: ' . $e->getMessage());
            $notice = 'Registration could not be saved. Please try again.';
        }
    } elseif ($notice === '') {
        $notice = 'Enter a valid name and email address.';
    }
}
$rows = [];
if ($conn) {
    try {
        $rows = $conn->query("SELECT e.*, (SELECT COUNT(*) FROM event_registrations r WHERE r.event_id = e.id) AS registered FROM events e WHERE e.status = 'PUBLISHED' ORDER BY starts_at IS NULL, starts_at ASC, id DESC")->fetchAll(PDO::FETCH_ASSOC);
    } catch (Throwable $e) {
        $rows = [];
    }
}
$member = $_SESSION['eca_member'] ?? null;
eca_public_page_start('Events', 'Association', 'Events', 'Published ECA events. This list stays empty until an event is published.', 'Events');
if ($rows) {
    eca_json_ld([
        '@context' => 'https://schema.org',
        '@graph' => array_map(static function (array $event): array {
            $data = [
                '@type' => 'Event',
                'name' => (string) ($event['title'] ?? ''),
                'description' => (string) ($event['summary'] ?? ''),
                'eventAttendanceMode' => 'https://schema.org/OfflineEventAttendanceMode',
                'organizer' => [
                    '@type' => 'Organization',
                    'name' => 'Eswatini Contractors Association',
                    'url' => eca_public_url('/'),
                ],
            ];
            if (!empty($event['starts_at'])) $data['startDate'] = date(DATE_ATOM, strtotime((string) $event['starts_at']));
            if (!empty($event['ends_at'])) $data['endDate'] = date(DATE_ATOM, strtotime((string) $event['ends_at']));
            if (!empty($event['venue'])) {
                $data['location'] = ['@type' => 'Place', 'name' => (string) $event['venue']];
            }
            return $data;
        }, $rows),
    ]);
}
?>
<div class="container-xxl py-4">
    <?php if ($notice): ?><div class="card shadow-sm mb-4" role="<?= $notice === 'Registration saved.' ? 'status' : 'alert' ?>"><div class="card-body"><?= htmlspecialchars($notice, ENT_QUOTES, 'UTF-8') ?></div></div><?php endif; ?>
    <div class="row g-4">
        <?php if (!$rows): ?>
            <div class="col-12"><div class="card shadow-sm"><div class="card-body"><p class="mb-0">No published events at the moment.</p></div></div></div>
        <?php endif; ?>
        <?php foreach ($rows as $row): ?>
            <div class="col-md-6">
                <div class="card company-card h-100 shadow-sm">
                    <div class="card-header"><?= htmlspecialchars((string) ($row['title'] ?? ''), ENT_QUOTES, 'UTF-8') ?></div>
                    <div class="card-body">
                        <p><?= htmlspecialchars((string) ($row['summary'] ?? ''), ENT_QUOTES, 'UTF-8') ?></p>
                        <p><strong>Venue:</strong> <?= htmlspecialchars((string) ($row['venue'] ?? ''), ENT_QUOTES, 'UTF-8') ?></p>
                        <p><strong>When:</strong> <?= htmlspecialchars((string) ($row['starts_at'] ?? ''), ENT_QUOTES, 'UTF-8') ?></p>
                        <?php
                        $cap = $row['capacity'] !== null ? (int) $row['capacity'] : 0;
                        $reg = (int) ($row['registered'] ?? 0);
                        $startTs = !empty($row['starts_at']) ? strtotime((string) $row['starts_at']) : null;
                        $endTs = !empty($row['ends_at']) ? strtotime((string) $row['ends_at']) : $startTs;
                        $nowTs = time();
                        $eventLabel = $startTs && $startTs > $nowTs
                            ? 'Upcoming'
                            : (($endTs && $endTs < $nowTs) ? 'Completed' : 'Ongoing');
                        $open = $eventLabel !== 'Completed' && ($cap === 0 || $reg < $cap);
                        ?>
                        <p><span class="eca-badge <?= $eventLabel === 'Completed' ? 'status-expired' : ($eventLabel === 'Upcoming' ? 'status-submitted' : 'status-active') ?>"><?= htmlspecialchars($eventLabel, ENT_QUOTES, 'UTF-8') ?></span></p>
                        <?php if ($open): ?>
                        <form method="post">
                            <input type="hidden" name="csrf_token" id="event-csrf-<?= (int) $row['id'] ?>" value="<?= htmlspecialchars(eca_public_csrf_token(), ENT_QUOTES, 'UTF-8') ?>">
                            <input type="hidden" name="event_id" id="event-id-<?= (int) $row['id'] ?>" value="<?= (int) $row['id'] ?>">
                            <div class="mb-3">
                                <label class="form-label" for="event-name-<?= (int) $row['id'] ?>">Name</label>
                                <input class="form-control" id="event-name-<?= (int) $row['id'] ?>" name="name" autocomplete="name" value="<?= htmlspecialchars((string) ($member['name'] ?? ''), ENT_QUOTES, 'UTF-8') ?>" required>
                            </div>
                            <div class="mb-3">
                                <label class="form-label" for="event-email-<?= (int) $row['id'] ?>">Email</label>
                                <input class="form-control" id="event-email-<?= (int) $row['id'] ?>" type="email" name="email" autocomplete="email" inputmode="email" value="<?= htmlspecialchars((string) ($member['email'] ?? ''), ENT_QUOTES, 'UTF-8') ?>" required>
                            </div>
                            <div class="mb-3">
                                <label class="form-label" for="event-membership-<?= (int) $row['id'] ?>">Membership number <span class="fw-normal">(optional)</span></label>
                                <input class="form-control" id="event-membership-<?= (int) $row['id'] ?>" name="membership" autocomplete="off" value="<?= htmlspecialchars((string) ($member['membership'] ?? ''), ENT_QUOTES, 'UTF-8') ?>">
                            </div>
                            <button class="btn btn-primary w-100" type="submit">Register</button>
                        </form>
                        <?php else: ?>
                            <p>Registration is full.</p>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
</div>
<?php eca_public_page_end(); ?>
