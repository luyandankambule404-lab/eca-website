<?php

require_once __DIR__ . '/content.php';
require_once __DIR__ . '/notify.php';
require_once __DIR__ . '/documents.php';

function eca_wellness_db(): ?PDO
{
    static $conn = false;
    if ($conn !== false) {
        return $conn;
    }
    if (!class_exists('Database')) {
        $config = dirname(__DIR__) . DIRECTORY_SEPARATOR . 'config.php';
        if (is_file($config)) {
            require_once $config;
        }
    }
    if (!class_exists('Database')) {
        $conn = null;
        return null;
    }
    try {
        $db = new Database();
        $pdo = $db->getConnection(false);
        $conn = $pdo instanceof PDO ? $pdo : null;
    } catch (Throwable $e) {
        $conn = null;
    }
    return $conn;
}

function eca_wellness_upload_dir(): string
{
    $dir = dirname(__DIR__) . DIRECTORY_SEPARATOR . 'storage' . DIRECTORY_SEPARATOR . 'wellness';
    if (!is_dir($dir)) {
        mkdir($dir, 0775, true);
    }
    return $dir;
}

function eca_wellness_allowed_extensions(): array
{
    return ['pdf', 'doc', 'docx', 'jpg', 'jpeg', 'png', 'webp', 'mp4'];
}

function eca_wellness_store_upload(array $file, string $prefix = 'res', int $maxBytes = 10485760): ?string
{
    if (empty($file['tmp_name']) || !is_uploaded_file($file['tmp_name'])) {
        return null;
    }
    $maxBytes = max(1024, $maxBytes);
    if ((int) ($file['size'] ?? 0) > $maxBytes) {
        return null;
    }
    $meta = eca_uploaded_file_meta($file, eca_upload_mime_map(eca_wellness_allowed_extensions()), $maxBytes);
    if (!$meta) {
        return null;
    }
    $ext = $meta['ext'];
    $base = preg_replace('/[^A-Za-z0-9._-]/', '_', basename((string) $file['name']));
    $name = $prefix . '_' . date('YmdHis') . '_' . bin2hex(random_bytes(4)) . '_' . $base;
    $dest = eca_wellness_upload_dir() . DIRECTORY_SEPARATOR . $name;
    if (!move_uploaded_file($file['tmp_name'], $dest)) {
        return null;
    }
    return $name;
}

function eca_wellness_public_resources(?PDO $conn, int $limit = 20): array
{
    if (!$conn) {
        return [];
    }
    $limit = max(1, min(50, $limit));
    try {
        $stmt = $conn->query(
            "SELECT r.id, r.title, r.description, r.file_path, r.file_type, r.external_url, r.published_at, c.name AS category_name
             FROM wellness_resources r
             LEFT JOIN wellness_categories c ON c.id = r.category_id
             WHERE r.status = 'PUBLISHED' AND r.is_public = 1
             ORDER BY r.published_at DESC, r.id DESC
             LIMIT " . $limit
        );
        return $stmt ? ($stmt->fetchAll(PDO::FETCH_ASSOC) ?: []) : [];
    } catch (Throwable $e) {
        return [];
    }
}

function eca_wellness_is_video_file(string $storedName): bool
{
    return strtolower((string) pathinfo($storedName, PATHINFO_EXTENSION)) === 'mp4';
}

function eca_wellness_file_path(string $storedName): ?string
{
    $storedName = basename($storedName);
    if ($storedName === '' || str_contains($storedName, '..')) {
        return null;
    }
    $path = eca_wellness_upload_dir() . DIRECTORY_SEPARATOR . $storedName;
    return is_file($path) ? $path : null;
}

function eca_wellness_active_announcement_sql(): string
{
    return "status = 'PUBLISHED' AND (published_at IS NULL OR published_at <= NOW())
            AND (expires_at IS NULL OR expires_at >= NOW())";
}

function eca_wellness_admin_stats(PDO $conn): array
{
    $stats = [
        'resources' => 0,
        'events_upcoming' => 0,
        'announcements' => 0,
        'registrations' => 0,
    ];
    try {
        $stats['resources'] = (int) $conn->query("SELECT COUNT(*) FROM wellness_resources WHERE status = 'PUBLISHED'")->fetchColumn();
        $stats['events_upcoming'] = (int) $conn->query(
            "SELECT COUNT(*) FROM wellness_events WHERE status = 'PUBLISHED' AND (starts_at IS NULL OR starts_at >= NOW())"
        )->fetchColumn();
        $stats['announcements'] = (int) $conn->query(
            'SELECT COUNT(*) FROM wellness_announcements WHERE ' . eca_wellness_active_announcement_sql()
        )->fetchColumn();
        $stats['registrations'] = (int) $conn->query('SELECT COUNT(*) FROM wellness_event_registrations')->fetchColumn();
    } catch (Throwable $e) {
        // tables may not exist yet
    }
    return $stats;
}

function eca_wellness_event_registration_count(PDO $conn, int $eventId): int
{
    $stmt = $conn->prepare('SELECT COUNT(*) FROM wellness_event_registrations WHERE event_id = ?');
    $stmt->execute([$eventId]);
    return (int) $stmt->fetchColumn();
}

function eca_wellness_member_registered(PDO $conn, int $eventId, string $membership): bool
{
    if ($membership === '') {
        return false;
    }
    $stmt = $conn->prepare('SELECT id FROM wellness_event_registrations WHERE event_id = ? AND membership_number = ? LIMIT 1');
    $stmt->execute([$eventId, $membership]);
    return (bool) $stmt->fetchColumn();
}

function eca_wellness_can_register(array $event, PDO $conn, string $membership): array
{
    $ok = true;
    $reason = '';
    if (($event['status'] ?? '') !== 'PUBLISHED') {
        return [false, 'This event is not open for registration.'];
    }
    if (empty($event['registration_open'])) {
        return [false, 'Registration is closed for this event.'];
    }
    $starts = $event['starts_at'] ?? null;
    if ($starts && strtotime((string) $starts) < time()) {
        return [false, 'This event has already started.'];
    }
    if (($event['status'] ?? '') === 'CLOSED') {
        return [false, 'This event is closed.'];
    }
    $cap = isset($event['capacity']) ? (int) $event['capacity'] : 0;
    if ($cap > 0) {
        $count = eca_wellness_event_registration_count($conn, (int) $event['id']);
        if ($count >= $cap) {
            return [false, 'This event is full.'];
        }
    }
    if (eca_wellness_member_registered($conn, (int) $event['id'], $membership)) {
        return [false, 'You are already registered for this event.'];
    }
    return [$ok, $reason];
}

function eca_wellness_register_member(PDO $conn, array $event, array $member): array
{
    $membership = trim((string) ($member['membership'] ?? ''));
    if ($membership === '') {
        return [false, 'Membership number is required to register.'];
    }
    [$can, $reason] = eca_wellness_can_register($event, $conn, $membership);
    if (!$can) {
        return [false, $reason];
    }
    $clientId = (int) ($member['client_id'] ?? 0);
    $name = trim((string) ($member['name'] ?? ''));
    $email = trim((string) ($member['email'] ?? ''));
    try {
        $conn->prepare(
            'INSERT INTO wellness_event_registrations (event_id, client_id, membership_number, member_name, member_email)
             VALUES (?,?,?,?,?)'
        )->execute([(int) $event['id'], $clientId > 0 ? $clientId : null, $membership, $name, $email]);
    } catch (PDOException $e) {
        if ((int) ($e->errorInfo[1] ?? 0) === 1062) {
            return [false, 'You are already registered for this event.'];
        }
        throw $e;
    }
    $title = (string) ($event['title'] ?? 'Wellness event');
    eca_notify([
        'title' => 'Wellness Event Registration Confirmed',
        'message' => 'You are registered for: ' . $title,
        'type' => 'WELLNESS',
        'link' => '/client/wellness/event.php?id=' . (int) $event['id'],
        'membership_number' => $membership,
        'client_id' => $clientId,
    ]);
    $when = trim((string) ($event['starts_at'] ?? ''));
    if ($when !== '') {
        eca_notify([
            'title' => 'Upcoming Wellness Event',
            'message' => $title . ' — ' . $when,
            'type' => 'WELLNESS',
            'link' => '/client/wellness/event.php?id=' . (int) $event['id'],
            'membership_number' => $membership,
            'client_id' => $clientId,
        ]);
    }
    return [true, 'Registration confirmed.'];
}

function eca_wellness_notify_resource_published(array $member, string $title, int $resourceId): void
{
    eca_notify([
        'title' => 'New Wellness Resource Available',
        'message' => $title,
        'type' => 'WELLNESS',
        'link' => '/client/wellness/resources.php',
        'membership_number' => trim((string) ($member['membership'] ?? '')),
        'client_id' => (int) ($member['client_id'] ?? 0),
    ]);
}

function eca_wellness_notify_announcement(array $member, string $title): void
{
    eca_notify([
        'title' => 'New Wellness Announcement',
        'message' => $title,
        'type' => 'WELLNESS',
        'link' => '/client/wellness/',
        'membership_number' => trim((string) ($member['membership'] ?? '')),
        'client_id' => (int) ($member['client_id'] ?? 0),
    ]);
}

function eca_wellness_categories(PDO $conn, bool $activeOnly = true): array
{
    $sql = 'SELECT * FROM wellness_categories';
    if ($activeOnly) {
        $sql .= " WHERE status = 'ACTIVE'";
    }
    $sql .= ' ORDER BY sort_order ASC, name ASC';
    return $conn->query($sql)->fetchAll(PDO::FETCH_ASSOC) ?: [];
}

function eca_wellness_member_subnav(string $active): void
{
    $items = [
        'home' => ['/', 'Overview'],
        'resources' => ['resources.php', 'Resources'],
        'events' => ['events.php', 'Events'],
        'announcements' => ['announcements.php', 'Announcements'],
        'activities' => ['activities.php', 'My activities'],
    ];
    echo '<nav class="hub-subnav" aria-label="Wellness sections">';
    foreach ($items as $key => $item) {
        $href = $key === 'home' ? '/client/wellness/' : '/client/wellness/' . $item[0];
        $cls = $active === $key ? 'is-active' : '';
        echo '<a class="' . htmlspecialchars($cls, ENT_QUOTES, 'UTF-8') . '" href="' . htmlspecialchars($href, ENT_QUOTES, 'UTF-8') . '">';
        echo htmlspecialchars($item[1], ENT_QUOTES, 'UTF-8') . '</a>';
    }
    echo '</nav>';
}

function eca_wellness_admin_subnav(string $active): void
{
    $items = [
        'dashboard' => ['/admin/wellness/', 'Dashboard'],
        'articles' => ['/admin/wellness/content.php?type=article', 'Articles'],
        'videos' => ['/admin/wellness/content.php?type=video', 'Videos'],
        'documents' => ['/admin/wellness/content.php?type=document', 'Documents'],
        'talks' => ['/admin/wellness/content.php?type=talk', 'Toolbox talks'],
        'referrals' => ['/admin/wellness/content.php?type=referral', 'Referrals'],
        'groups' => ['/admin/wellness/content.php?type=group', 'Groups'],
        'resources' => ['/admin/wellness/resources.php', 'All resources'],
        'events' => ['/admin/wellness/events.php', 'Events'],
        'announcements' => ['/admin/wellness/announcements.php', 'Announcements'],
        'categories' => ['/admin/wellness/categories.php', 'Categories'],
        'reports' => ['/admin/wellness/reports.php', 'Usage'],
    ];
    echo '<nav class="hub-subnav" aria-label="Wellness admin">';
    foreach ($items as $key => $item) {
        $cls = $active === $key ? 'is-active' : '';
        echo '<a class="' . htmlspecialchars($cls, ENT_QUOTES, 'UTF-8') . '" href="' . htmlspecialchars($item[0], ENT_QUOTES, 'UTF-8') . '">';
        echo htmlspecialchars($item[1], ENT_QUOTES, 'UTF-8') . '</a>';
    }
    echo '</nav>';
}
