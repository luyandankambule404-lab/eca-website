<?php
require_once dirname(__DIR__) . '/includes/wellness-hub.php';

$kinds = eca_wellness_library_kinds();
$kind = trim((string) ($_GET['type'] ?? ''));
if ($kind !== '' && !isset($kinds[$kind])) {
    $kind = '';
}
$conn = eca_wellness_db();
$items = eca_wellness_library_items($conn, $kind);

eca_wellness_page_start(
    'Wellness Library',
    'Wellness Library',
    'Articles, videos, guides, checklists and downloadable resources for contractors and their teams.',
    'library'
);
?>
<div class="edu-filters" aria-label="Library types">
    <a href="/wellness/library.php" class="<?= $kind === '' ? 'is-active' : '' ?>">All</a>
    <?php foreach ($kinds as $key => $label): ?>
        <a href="/wellness/library.php?type=<?= eca_wellness_h($key) ?>" class="<?= $kind === $key ? 'is-active' : '' ?>"><?= eca_wellness_h($label) ?></a>
    <?php endforeach; ?>
</div>
<?php
eca_wellness_render_cards(
    $items,
    'No published library items match this filter yet. The administrator can add articles, videos and documents from Wellness management.'
);
eca_wellness_page_end();
