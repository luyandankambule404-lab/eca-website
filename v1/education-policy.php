<?php
require_once __DIR__ . '/includes/public-page.php';
require_once __DIR__ . '/includes/education.php';

$conn = eca_education_db();
$ready = $conn && eca_education_ready($conn);
$categories = $ready ? eca_education_categories($conn, 'policy') : [];
$catSlug = trim((string) ($_GET['category'] ?? ''));
$categoryId = null;
foreach ($categories as $cat) {
    if ($catSlug !== '' && ($cat['slug'] ?? '') === $catSlug) {
        $categoryId = (int) $cat['id'];
        break;
    }
}
$articles = $ready ? eca_education_public_articles($conn, 'policy', $categoryId) : [];

eca_public_page_start(
    'Industry & Policy Education',
    'Membership development',
    'Industry & Policy Education',
    'This section connects Education with ECA’s advocacy mandate. Industry developments are explained in practical contractor language.',
    'Membership development / Policy'
);
?>
<div class="edu-wrap">
    <?php eca_education_subnav('policy'); ?>
    <div class="edu-policy">
        <article class="edu-policy-step"><h3>What changed?</h3><p>The regulation, policy, procurement rule or industry requirement that is new or different.</p></article>
        <article class="edu-policy-step"><h3>Why does it matter to contractors?</h3><p>The effect on bidding, delivery, cash flow, compliance or access to work.</p></article>
        <article class="edu-policy-step"><h3>What must contractors do?</h3><p>The practical next step a firm should take on live tenders and site files.</p></article>
        <article class="edu-policy-step"><h3>What support is available from ECA?</h3><p>Guidance, training, templates and advocacy the association can offer.</p></article>
    </div>
    <div class="edu-filters" aria-label="Policy categories">
        <a href="/education-policy.php" class="<?= $catSlug === '' ? 'is-active' : '' ?>">All updates</a>
        <?php foreach ($categories as $cat): ?>
            <a href="/education-policy.php?category=<?= urlencode((string) $cat['slug']) ?>" class="<?= $catSlug === $cat['slug'] ? 'is-active' : '' ?>"><?= eca_education_h($cat['name']) ?></a>
        <?php endforeach; ?>
    </div>
    <?php if (!$articles): ?>
        <?php eca_education_empty('No published policy updates match this filter yet.'); ?>
    <?php else: ?>
        <div class="edu-card-grid">
            <?php foreach ($articles as $article): ?>
                <article class="edu-card">
                    <p class="edu-kicker"><?= eca_education_h($article['category_name'] ?? 'Industry update') ?></p>
                    <h3><?= eca_education_h($article['title'] ?? '') ?></h3>
                    <p><?= eca_education_h($article['summary'] ?? '') ?></p>
                    <a class="edu-more" href="/education-article.php?kind=policy&slug=<?= urlencode((string) $article['slug']) ?>">Read the briefing</a>
                </article>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>
<?php eca_public_page_end(); ?>
