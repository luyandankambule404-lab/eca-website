<?php
require_once __DIR__ . '/includes/public-page.php';
require_once __DIR__ . '/includes/education.php';

$conn = eca_education_db();
$ready = $conn && eca_education_ready($conn);
$categories = $ready ? eca_education_categories($conn, 'knowledge') : [];
$catSlug = trim((string) ($_GET['category'] ?? ''));
$type = trim((string) ($_GET['type'] ?? ''));
$types = eca_education_knowledge_types();
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
$articles = $ready ? eca_education_public_articles($conn, 'knowledge', $categoryId, $type) : [];

eca_public_page_start(
    'Contractor Knowledge Centre',
    'Membership development',
    'Contractor Knowledge Centre',
    'A practical resource centre for contractors: contracts, procurement, business and finance, project management, compliance, and health and safety.',
    'Membership development / Knowledge'
);
?>
<div class="edu-wrap edu-knowledge-page">
    <section class="edu-panel" aria-labelledby="eduLearnHeading">
        <header class="edu-panel-head">
            <p class="edu-panel-kicker">Learning</p>
            <h2 id="eduLearnHeading">Explore learning</h2>
            <p>Open training, development and support sections from one place.</p>
        </header>
        <div class="edu-hub-grid" aria-label="Learning sections">
            <?php foreach (eca_education_sections() as $key => $item): ?>
                <?php if ($key === 'knowledge') { continue; } ?>
                <a class="edu-hub-card" href="<?= eca_education_h($item[1]) ?>">
                    <i class="fas <?= eca_education_h($item[2]) ?>" aria-hidden="true"></i>
                    <h3><?= eca_education_h($item[0]) ?></h3>
                    <p><?= eca_education_h($item[3]) ?></p>
                    <span>Open →</span>
                </a>
            <?php endforeach; ?>
        </div>
    </section>

    <section class="edu-panel" aria-labelledby="eduBrowseHeading">
        <header class="edu-panel-head">
            <p class="edu-panel-kicker">Library</p>
            <h2 id="eduBrowseHeading">Browse knowledge</h2>
            <p>Filter by category or resource type to find practical contractor guides.</p>
        </header>

        <?php if ($categories): ?>
        <div class="edu-filters" aria-label="Knowledge categories">
            <a href="/education-knowledge.php" class="<?= $catSlug === '' && $type === '' ? 'is-active' : '' ?>">All</a>
            <?php foreach ($categories as $cat): ?>
                <a href="/education-knowledge.php?category=<?= urlencode((string) $cat['slug']) ?>" class="<?= $catSlug === $cat['slug'] ? 'is-active' : '' ?>"><?= eca_education_h($cat['name']) ?></a>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>

        <div class="edu-filters" aria-label="Resource types">
            <?php foreach ($types as $key => $label): ?>
                <a href="/education-knowledge.php?<?= $catSlug !== '' ? 'category=' . urlencode($catSlug) . '&' : '' ?>type=<?= urlencode($key) ?>" class="<?= $type === $key ? 'is-active' : '' ?>"><?= eca_education_h($label) ?></a>
            <?php endforeach; ?>
        </div>

        <?php if (!$articles): ?>
            <?php eca_education_empty('No published knowledge items match this filter yet.'); ?>
        <?php else: ?>
            <div class="edu-card-grid">
                <?php foreach ($articles as $article): ?>
                    <article class="edu-card">
                        <p class="edu-kicker"><?= eca_education_h($article['category_name'] ?? 'Knowledge') ?> · <?= eca_education_h($types[$article['resource_type'] ?? ''] ?? 'Article') ?></p>
                        <h3><?= eca_education_h($article['title'] ?? '') ?></h3>
                        <p><?= eca_education_h($article['summary'] ?? '') ?></p>
                        <a class="edu-more" href="/education-article.php?kind=knowledge&slug=<?= urlencode((string) $article['slug']) ?>">Read</a>
                    </article>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </section>
</div>
<?php eca_public_page_end(); ?>
