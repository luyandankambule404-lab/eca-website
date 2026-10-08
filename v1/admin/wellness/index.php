<?php
require_once __DIR__ . '/../auth.php';
require_once __DIR__ . '/../_hub.php';
require_once __DIR__ . '/../../includes/wellness.php';
require_once __DIR__ . '/../../includes/wellness-hub.php';
require_once __DIR__ . '/../../includes/audit.php';
eca_admin_require('wellness.manage');
header('Cache-Control: no-store');
$conn = eca_wellness_db();
if ($conn) {
    eca_wellness_hub_ensure_schema($conn);
}
$stats = $conn ? eca_wellness_admin_stats($conn) : [
    'resources' => 0, 'events_upcoming' => 0, 'announcements' => 0, 'registrations' => 0,
];
$hubStats = $conn ? eca_wellness_hub_stats($conn) : ['checkins' => 0, 'checkins_30d' => 0, 'hub_published' => 0];
$kindCounts = [
    'article' => $conn ? eca_wellness_count_kind($conn, 'article') : 0,
    'video' => $conn ? eca_wellness_count_kind($conn, 'video') : 0,
    'document' => $conn ? eca_wellness_count_kind($conn, 'document') : 0,
    'talk' => $conn ? eca_wellness_count_kind($conn, 'talk') : 0,
    'referral' => $conn ? eca_wellness_count_kind($conn, 'referral') : 0,
    'group' => $conn ? eca_wellness_count_kind($conn, 'group') : 0,
];
$recentRegs = [];
if ($conn) {
    try {
        $recentRegs = $conn->query(
            'SELECT r.*, e.title AS event_title FROM wellness_event_registrations r
             INNER JOIN wellness_events e ON e.id = r.event_id
             ORDER BY r.id DESC LIMIT 8'
        )->fetchAll(PDO::FETCH_ASSOC);
    } catch (Throwable $e) {
        $recentRegs = [];
    }
}
eca_admin_hub_start('Wellness', 'wellness');
eca_wellness_admin_subnav('dashboard');
?>
<div class="hub-hello">
    <h1 class="hub-hello-title">Wellness management</h1>
    <p>Add and edit articles, upload videos and documents, create toolbox talks, update referrals and support-group information, and view general usage statistics. Check-in answers stay anonymous — only totals are shown.</p>
    <p><a class="hub-home-link" href="/wellness/" target="_blank" rel="noopener">View public Wellness Hub</a></p>
</div>
<div class="hub-tiles">
    <a class="hub-tile" href="/admin/wellness/content.php?type=article">
        <h3>Articles</h3>
        <p>Add and edit Wellness Hub articles.</p>
        <span><?= (int) $kindCounts['article'] ?> stored →</span>
    </a>
    <a class="hub-tile hub-tile-sand" href="/admin/wellness/content.php?type=video">
        <h3>Videos</h3>
        <p>Upload an MP4 or add a YouTube link.</p>
        <span><?= (int) $kindCounts['video'] ?> stored →</span>
    </a>
    <a class="hub-tile hub-tile-lilac" href="/admin/wellness/content.php?type=document">
        <h3>Documents</h3>
        <p>Upload PDFs and Word files for the library.</p>
        <span><?= (int) $kindCounts['document'] ?> stored →</span>
    </a>
    <a class="hub-tile hub-tile-mint" href="/admin/wellness/content.php?type=talk">
        <h3>Toolbox talks</h3>
        <p>Create short talks for supervisors on site.</p>
        <span><?= (int) $kindCounts['talk'] ?> stored →</span>
    </a>
    <a class="hub-tile" href="/admin/wellness/resources.php">
        <h3>Wellness resources</h3>
        <p>Manage every Hub item from one list.</p>
        <span><?= (int) $stats['resources'] ?> published →</span>
    </a>
    <a class="hub-tile hub-tile-sand" href="/admin/wellness/content.php?type=referral">
        <h3>Referrals</h3>
        <p>Update verified referral information only.</p>
        <span><?= (int) $kindCounts['referral'] ?> stored →</span>
    </a>
    <a class="hub-tile hub-tile-lilac" href="/admin/wellness/content.php?type=group">
        <h3>Support groups</h3>
        <p>Manage approved, moderated group information.</p>
        <span><?= (int) $kindCounts['group'] ?> stored →</span>
    </a>
    <a class="hub-tile hub-tile-mint" href="/admin/wellness/reports.php">
        <h3>Usage statistics</h3>
        <p>General totals only. No individual check-in scores.</p>
        <span><?= (int) $hubStats['checkins'] ?> check-ins →</span>
    </a>
</div>
<div class="hub-stats">
    <a class="hub-stat" href="/admin/wellness/resources.php">
        <div class="hub-stat-label">Active resources</div>
        <div class="hub-stat-value"><?= (int) $stats['resources'] ?></div>
        <div class="hub-stat-meta"><?= (int) $hubStats['hub_published'] ?> on public Hub sections</div>
    </a>
    <a class="hub-stat" href="/admin/wellness/events.php">
        <div class="hub-stat-label">Upcoming events</div>
        <div class="hub-stat-value"><?= (int) $stats['events_upcoming'] ?></div>
        <div class="hub-stat-meta">Published</div>
    </a>
    <a class="hub-stat" href="/admin/wellness/announcements.php">
        <div class="hub-stat-label">Published announcements</div>
        <div class="hub-stat-value"><?= (int) $stats['announcements'] ?></div>
        <div class="hub-stat-meta">Active now</div>
    </a>
    <article class="hub-stat">
        <div class="hub-stat-label">Registered participants</div>
        <div class="hub-stat-value"><?= (int) $stats['registrations'] ?></div>
        <div class="hub-stat-meta">All wellness events</div>
    </article>
    <article class="hub-stat">
        <div class="hub-stat-label">Anonymous check-ins</div>
        <div class="hub-stat-value"><?= (int) $hubStats['checkins'] ?></div>
        <div class="hub-stat-meta"><?= (int) $hubStats['checkins_30d'] ?> in the last 30 days</div>
    </article>
</div>
<?php if ($recentRegs): ?>
<div class="hub-card-head"><h2>Recent registrations</h2></div>
<div class="hub-card eca-table-panel">
<table class="hub-table">
    <thead><tr><th>Event</th><th>Member</th><th>Membership</th><th>When</th></tr></thead>
    <tbody>
    <?php foreach ($recentRegs as $reg): ?>
        <tr>
            <td><?= eca_admin_h($reg['event_title'] ?? '') ?></td>
            <td><?= eca_admin_h($reg['member_name'] ?? '') ?></td>
            <td><?= eca_admin_h($reg['membership_number'] ?? '') ?></td>
            <td><?= eca_admin_h($reg['created_at'] ?? '') ?></td>
        </tr>
    <?php endforeach; ?>
    </tbody>
</table>
</div>
<?php else: ?>
<div class="hub-card-head"><h2>Recent registrations</h2></div>
<p class="hub-card">No wellness event registrations are stored yet. This is the real local count, not a placeholder.</p>
<?php endif; ?>
<?php eca_admin_hub_end(); ?>
