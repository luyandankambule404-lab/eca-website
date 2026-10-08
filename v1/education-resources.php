<?php
require_once __DIR__ . '/includes/public-page.php';
require_once __DIR__ . '/includes/education.php';

$conn = eca_education_db();
$ready = $conn && eca_education_ready($conn);
$categories = $ready ? eca_education_categories($conn, 'resources') : [];
$types = eca_education_resource_types();
$catSlug = trim((string) ($_GET['category'] ?? ''));
$type = trim((string) ($_GET['type'] ?? ''));
if ($type !== '' && !isset($types[$type])) {
    $type = '';
}
$categoryId = null;
foreach ($categories as $cat) {
    if ($catSlug !== '' && ($cat['slug'] ?? '') === $catSlug) {
        $categoryId = (int) $cat['id'];
        break;
    }
}
$resources = $ready ? eca_education_public_resources($conn, $categoryId, $type) : [];
$topicCategories = eca_education_topic_categories($categories, $types);

eca_public_page_start(
    'Education resources',
    'Membership development',
    'Resources',
    'A central area for downloadable educational material: PDFs, presentations, guides, forms, checklists, videos and reference documents.',
    'Membership development / Resources'
);
?>
<div class="edu-wrap">
    <?php eca_education_subnav('resources'); ?>
    <div class="edu-filters" aria-label="Resource types">
        <a href="/education-resources.php" class="<?= $type === '' && $catSlug === '' ? 'is-active' : '' ?>">All</a>
        <?php foreach ($types as $key => $label): ?>
            <a href="/education-resources.php?type=<?= urlencode($key) ?>" class="<?= $type === $key ? 'is-active' : '' ?>"><?= eca_education_h($label) ?></a>
        <?php endforeach; ?>
    </div>
    <?php if ($topicCategories): ?>
    <div class="edu-filters" aria-label="Resource topics">
        <?php foreach ($topicCategories as $cat): ?>
            <a href="/education-resources.php?category=<?= urlencode((string) $cat['slug']) ?>" class="<?= $catSlug === $cat['slug'] ? 'is-active' : '' ?>"><?= eca_education_h($cat['name']) ?></a>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>
    <?php if (!$resources): ?>
        <?php eca_education_empty('No published education resources match this filter yet.'); ?>
    <?php else: ?>
        <div class="edu-card-grid">
            <?php foreach ($resources as $row): ?>
                <article class="edu-card">
                    <p class="edu-kicker"><?= eca_education_h(eca_education_kicker($types[$row['resource_type'] ?? ''] ?? 'Resource', (string) ($row['category_name'] ?? ''))) ?></p>
                    <h3><?= eca_education_h($row['title'] ?? '') ?></h3>
                    <p><?= eca_education_h($row['description'] ?? '') ?></p>
                    <?php
                    $openHref = eca_education_resource_href($row, 'open');
                    $downloadHref = eca_education_resource_href($row, 'download');
                    $external = str_starts_with((string) ($row['external_url'] ?? ''), 'http');
                    $openIsView = str_starts_with($openHref, '/education-view.php');
                    $rel = $external && !$openIsView ? ' rel="noopener noreferrer"' : '';
                    $openTarget = $openIsView ? '' : ' target="_blank"';
                    $downloadAttr = $external ? '' : ' download';
                    ?>
                    <div class="edu-actions">
                        <a class="edu-btn-ghost" href="<?= eca_education_h($openHref) ?>"<?= $openTarget ?><?= $rel ?>>Open</a>
                        <a class="edu-btn" href="<?= eca_education_h($downloadHref) ?>"<?= $downloadAttr ?><?= $rel ?>>Download</a>
                    </div>
                </article>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>
<?php eca_public_page_end(); ?>
