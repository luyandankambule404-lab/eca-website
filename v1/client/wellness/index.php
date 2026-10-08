<?php
require_once __DIR__ . '/../_shell.php';
require_once __DIR__ . '/../../includes/wellness.php';
require_once __DIR__ . '/../../includes/authz.php';
require_once __DIR__ . '/../../includes/pagination.php';

$member = eca_portal_member();
$GLOBALS['eca_member'] = $member;
eca_require_permission('wellness.view');
$conn = eca_wellness_db();

$upcoming = [];
$resources = [];
$workshops = [];
$announcement = null;
$contacts = [];

if ($conn) {
    try {
        $upcoming = $conn->query(
            "SELECT * FROM wellness_events WHERE status = 'PUBLISHED' AND (starts_at IS NULL OR starts_at >= NOW()) ORDER BY starts_at ASC LIMIT 5"
        )->fetchAll(PDO::FETCH_ASSOC);
        $resources = $conn->query(
            "SELECT r.*, c.name AS category_name FROM wellness_resources r
             LEFT JOIN wellness_categories c ON c.id = r.category_id
             WHERE r.status = 'PUBLISHED' ORDER BY r.published_at DESC, r.id DESC LIMIT 6"
        )->fetchAll(PDO::FETCH_ASSOC);
        $workshops = $conn->query(
            "SELECT * FROM wellness_events WHERE status = 'PUBLISHED' AND title LIKE '%workshop%' ORDER BY starts_at ASC LIMIT 4"
        )->fetchAll(PDO::FETCH_ASSOC);
        $announcement = $conn->query(
            'SELECT * FROM wellness_announcements WHERE ' . eca_wellness_active_announcement_sql() . ' ORDER BY published_at DESC LIMIT 1'
        )->fetch(PDO::FETCH_ASSOC);
        $contacts = $conn->query(
            "SELECT r.* FROM wellness_resources r
             INNER JOIN wellness_categories c ON c.id = r.category_id AND c.slug = 'support-contacts'
             WHERE r.status = 'PUBLISHED' LIMIT 3"
        )->fetchAll(PDO::FETCH_ASSOC);
    } catch (Throwable $e) {
        // tables may be missing until migration runs
    }
}

eca_portal_start('Wellness', 'wellness');
eca_wellness_member_subnav('home');
?>
<div class="hub-hello">
    <h1 class="hub-hello-title">Wellness</h1>
    <p>Welcome to ECA Wellness — supporting healthier, safer and more productive workplaces.</p>
    <p><a class="hub-home-link" href="/wellness/">Open the public Wellness Hub</a> for mental health, check-in, toolbox talks and referrals.</p>
</div>
<div class="hub-stats">
    <a class="hub-stat" href="/client/wellness/events.php">
        <div class="hub-stat-label">Upcoming wellness events</div>
        <div class="hub-stat-value"><?= count($upcoming) ?></div>
        <div class="hub-stat-meta">View calendar</div>
    </a>
    <a class="hub-stat" href="/client/wellness/resources.php">
        <div class="hub-stat-label">Available resources</div>
        <div class="hub-stat-value"><?= count($resources) ?></div>
        <div class="hub-stat-meta">Guides &amp; materials</div>
    </a>
    <a class="hub-stat" href="/client/wellness/events.php">
        <div class="hub-stat-label">Wellness workshops</div>
        <div class="hub-stat-value"><?= count($workshops) ?></div>
        <div class="hub-stat-meta">Sessions</div>
    </a>
    <article class="hub-stat">
        <div class="hub-stat-label">Latest announcement</div>
        <div class="hub-stat-value" style="font-size:1rem;line-height:1.35;"><?= eca_h($announcement['title'] ?? 'None yet') ?></div>
        <div class="hub-stat-meta"><a href="/client/wellness/announcements.php">All news</a></div>
    </article>
</div>

<?php if ($announcement): ?>
<div class="hub-card-head"><h2>Latest wellness news</h2></div>
<div class="hub-card">
    <h3><?= eca_h($announcement['title']) ?></h3>
    <p><?= nl2br(eca_h($announcement['content'])) ?></p>
    <p class="hub-stat-meta">Published <?= eca_h($announcement['published_at'] ?? '') ?></p>
</div>
<?php endif; ?>

<div class="hub-card-head"><h2>Upcoming activities</h2></div>
<div class="hub-card eca-table-panel">
<?php if ($upcoming): ?>
<table class="hub-table">
    <thead><tr><th>Event</th><th>When</th><th>Venue</th><th></th></tr></thead>
    <tbody>
    <?php foreach ($upcoming as $ev): ?>
        <tr>
            <td><?= eca_h($ev['title']) ?></td>
            <td><?= eca_h($ev['starts_at'] ?? '—') ?></td>
            <td><?= eca_h($ev['venue'] ?? '—') ?></td>
            <td><a href="/client/wellness/event.php?id=<?= (int) $ev['id'] ?>">View</a></td>
        </tr>
    <?php endforeach; ?>
    </tbody>
</table>
<?php else: ?>
<p style="padding:16px;">No upcoming events at the moment. Check back soon.</p>
<?php endif; ?>
</div>

<div class="hub-card-head"><h2>Featured resources</h2></div>
<div class="hub-card eca-table-panel">
<?php if ($resources): ?>
<table class="hub-table">
    <thead><tr><th>Title</th><th>Category</th><th>Published</th><th></th></tr></thead>
    <tbody>
    <?php foreach ($resources as $res): ?>
        <tr>
            <td><?= eca_h($res['title']) ?></td>
            <td><?= eca_h($res['category_name'] ?? '—') ?></td>
            <td><?= eca_h($res['published_at'] ?? $res['created_at'] ?? '') ?></td>
            <td><a href="/client/wellness/resources.php#res-<?= (int) $res['id'] ?>">Open</a></td>
        </tr>
    <?php endforeach; ?>
    </tbody>
</table>
<?php else: ?>
<p style="padding:16px;">Resources will appear here when published by ECA.</p>
<?php endif; ?>
</div>

<div class="hub-card-head"><h2>Important contacts</h2></div>
<div class="hub-card">
<?php if ($contacts): ?>
<ul>
<?php foreach ($contacts as $c): ?>
    <li><strong><?= eca_h($c['title']) ?></strong> — <?= eca_h($c['description'] ?? '') ?></li>
<?php endforeach; ?>
</ul>
<?php else: ?>
<p>ECA office: Suite 40, Cooper Centre, Mbabane · <a href="tel:+26824044987">+268 2404 4987</a> · <a href="mailto:info@eca.co.sz">info@eca.co.sz</a></p>
<p class="hub-stat-meta">Emergency: contact local emergency services first.</p>
<?php endif; ?>
</div>
<?php eca_portal_end(); ?>
