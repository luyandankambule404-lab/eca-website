<?php
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/_hub.php';
eca_admin_require();

$conn = eca_admin_db();
$counts = [
    'companies' => 0,
    'companies1' => 0,
    'users' => 0,
    'balances' => 0,
    'courses' => 0,
    'meetings' => 0,
];
$typeCounts = [];
if ($conn) {
    foreach (array_keys($counts) as $table) {
        $counts[$table] = eca_table_count($conn, $table);
    }
    $typeCounts = eca_directory_type_counts($conn, 'members');
}
$admin = eca_admin_user();
$helloName = trim((string) ($admin['name'] ?? ''));
if ($helloName === '') {
    $helloName = (string) ($admin['email'] ?? 'Administrator');
}

eca_admin_hub_start('Administrator', 'dashboard');
?>
<div class="hub-hello">
    <h1 class="hub-hello-title"><?= eca_admin_h(eca_hub_hello($helloName)) ?></h1>
    <p>Local records from the exported ECA database. These counts come from the local copy of <code>rapfpnhd_ECA</code>. The original backup file and the live website were not changed.</p>
</div>

<?php if ($typeCounts): ?>
<div class="hub-card-head">
    <h2>Companies by type</h2>
</div>
<p class="hub-sub">Click All for every company, or a type for that list only.</p>
<div class="hub-stats">
    <a class="hub-stat" href="/admin/companies.php">
        <div class="hub-stat-label">All</div>
        <div class="hub-stat-value"><?= (int) ($counts['companies'] ?: array_sum($typeCounts)) ?></div>
        <div class="hub-stat-meta">Companies</div>
        <div class="hub-stat-link">View all</div>
    </a>
    <?php foreach ($typeCounts as $type => $count): ?>
        <a class="hub-stat" href="/admin/companies.php?industry=<?= urlencode($type) ?>">
            <div class="hub-stat-label"><?= eca_admin_h($type) ?></div>
            <div class="hub-stat-value"><?= (int) $count ?></div>
            <div class="hub-stat-meta">Companies</div>
            <div class="hub-stat-link">View Details</div>
        </a>
    <?php endforeach; ?>
</div>
<?php endif; ?>

<div class="hub-card-head">
    <h2>Database tables</h2>
</div>
<div class="hub-stats">
    <?php foreach ($counts as $table => $count): ?>
        <article class="hub-stat">
            <div class="hub-stat-label"><?= eca_admin_h($table) ?></div>
            <div class="hub-stat-value"><?= (int) $count ?></div>
        </article>
    <?php endforeach; ?>
</div>

<div class="hub-tiles">
    <a class="hub-tile" href="/admin/companies.php">
        <h3>Manage companies</h3>
        <p>Open the full company list, search, and filter by type.</p>
        <span>Explore →</span>
    </a>
    <a class="hub-tile hub-tile-sand" href="/directory.html">
        <h3>Public directory</h3>
        <p>View the public member directory used on the website.</p>
        <span>Explore →</span>
    </a>
</div>
<?php eca_admin_hub_end(); ?>
