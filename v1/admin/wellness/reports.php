<?php
require_once __DIR__ . '/../auth.php';
require_once __DIR__ . '/../_hub.php';
require_once __DIR__ . '/../../includes/wellness.php';
require_once __DIR__ . '/../../includes/wellness-hub.php';
eca_admin_require('wellness.manage');
$conn = eca_wellness_db();
if ($conn) {
    eca_wellness_hub_ensure_schema($conn);
}
$stats = $conn ? eca_wellness_admin_stats($conn) : [];
$hubStats = $conn ? eca_wellness_hub_stats($conn) : [
    'by_section' => [], 'by_kind' => [], 'by_band' => [], 'checkins' => 0, 'checkins_30d' => 0, 'hub_published' => 0,
];
$byCategory = [];
$topEvents = [];
if ($conn) {
    try {
        $byCategory = $conn->query(
            'SELECT c.name, COUNT(r.id) AS cnt FROM wellness_resources r
             LEFT JOIN wellness_categories c ON c.id = r.category_id
             WHERE r.status = \'PUBLISHED\'
             GROUP BY c.id, c.name ORDER BY cnt DESC'
        )->fetchAll(PDO::FETCH_ASSOC);
        $topEvents = $conn->query(
            'SELECT e.title, COUNT(reg.id) AS cnt FROM wellness_events e
             LEFT JOIN wellness_event_registrations reg ON reg.event_id = e.id
             GROUP BY e.id, e.title ORDER BY cnt DESC LIMIT 10'
        )->fetchAll(PDO::FETCH_ASSOC);
    } catch (Throwable $e) {
        // ignore
    }
}
eca_admin_hub_start('Usage statistics', 'wellness');
eca_wellness_admin_subnav('reports');
$statLabels = [
    'resources' => 'Published resources',
    'events_upcoming' => 'Upcoming events',
    'announcements' => 'Published announcements',
    'registrations' => 'Event registrations',
];
?>
<div class="hub-hello">
    <h1 class="hub-hello-title">Usage statistics</h1>
    <p>General Wellness Hub usage only. Contractor Check-In scores are anonymous and are never shown individually.</p>
</div>
<div class="hub-stats">
    <?php foreach ($stats as $label => $val): ?>
        <article class="hub-stat">
            <div class="hub-stat-label"><?= eca_admin_h($statLabels[$label] ?? str_replace('_', ' ', $label)) ?></div>
            <div class="hub-stat-value"><?= (int) $val ?></div>
        </article>
    <?php endforeach; ?>
    <article class="hub-stat">
        <div class="hub-stat-label">Anonymous check-ins</div>
        <div class="hub-stat-value"><?= (int) ($hubStats['checkins'] ?? 0) ?></div>
    </article>
    <article class="hub-stat">
        <div class="hub-stat-label">Check-ins (30 days)</div>
        <div class="hub-stat-value"><?= (int) ($hubStats['checkins_30d'] ?? 0) ?></div>
    </article>
    <article class="hub-stat">
        <div class="hub-stat-label">Public Hub items</div>
        <div class="hub-stat-value"><?= (int) ($hubStats['hub_published'] ?? 0) ?></div>
    </article>
</div>
<div class="hub-card-head"><h2>Published items by type</h2></div>
<div class="hub-card eca-table-panel">
<table class="hub-table">
    <thead><tr><th>Type</th><th>Published</th></tr></thead>
    <tbody>
    <?php if (empty($hubStats['by_kind'])): ?>
        <tr><td colspan="2">No published items yet.</td></tr>
    <?php endif; ?>
    <?php foreach ($hubStats['by_kind'] as $row): ?>
        <tr><td><?= eca_admin_h(eca_wellness_hub_kinds()[(string) ($row['kind'] ?? '')] ?? ($row['kind'] ?? '')) ?></td><td><?= (int) ($row['cnt'] ?? 0) ?></td></tr>
    <?php endforeach; ?>
    </tbody>
</table>
</div>
<div class="hub-card-head"><h2>Resources by category</h2></div>
<div class="hub-card eca-table-panel">
<table class="hub-table">
    <thead><tr><th>Category</th><th>Published resources</th></tr></thead>
    <tbody>
    <?php foreach ($byCategory as $row): ?>
        <tr><td><?= eca_admin_h($row['name'] ?? 'Uncategorised') ?></td><td><?= (int) ($row['cnt'] ?? 0) ?></td></tr>
    <?php endforeach; ?>
    </tbody>
</table>
</div>
<div class="hub-card-head"><h2>Hub resources by section</h2></div>
<div class="hub-card eca-table-panel">
<table class="hub-table">
    <thead><tr><th>Section</th><th>Published resources</th></tr></thead>
    <tbody>
    <?php foreach ($hubStats['by_section'] as $row): ?>
        <tr><td><?= eca_admin_h(eca_wellness_section_labels()[(string) ($row['hub_section'] ?? '')] ?? ($row['hub_section'] ?? '')) ?></td><td><?= (int) ($row['cnt'] ?? 0) ?></td></tr>
    <?php endforeach; ?>
    </tbody>
</table>
</div>
<div class="hub-card-head"><h2>Anonymous check-in bands</h2></div>
<div class="hub-card eca-table-panel">
<table class="hub-table">
    <thead><tr><th>Band</th><th>Responses</th></tr></thead>
    <tbody>
    <?php if (!$hubStats['by_band']): ?>
        <tr><td colspan="2">No check-ins stored yet.</td></tr>
    <?php endif; ?>
    <?php foreach ($hubStats['by_band'] as $row): ?>
        <tr><td><?= eca_admin_h($row['band'] ?? '') ?></td><td><?= (int) ($row['cnt'] ?? 0) ?></td></tr>
    <?php endforeach; ?>
    </tbody>
</table>
</div>
<div class="hub-card-head"><h2>Event registrations</h2></div>
<div class="hub-card eca-table-panel">
<table class="hub-table">
    <thead><tr><th>Event</th><th>Registrations</th></tr></thead>
    <tbody>
    <?php foreach ($topEvents as $row): ?>
        <tr><td><?= eca_admin_h($row['title'] ?? '') ?></td><td><?= (int) ($row['cnt'] ?? 0) ?></td></tr>
    <?php endforeach; ?>
    </tbody>
</table>
</div>
<?php eca_admin_hub_end(); ?>
