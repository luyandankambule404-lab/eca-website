<?php
require_once __DIR__ . '/includes/session.php';
require_once __DIR__ . '/includes/http.php';
require_once __DIR__ . '/includes/public-page.php';
require_once __DIR__ . '/includes/education.php';

eca_require_login();

$conn = eca_education_db();
$kind = trim((string) ($_GET['kind'] ?? ''));
if (!in_array($kind, ['knowledge', 'policy'], true)) {
    $kind = '';
}
$slug = trim((string) ($_GET['slug'] ?? ''));
$article = null;
if ($conn && $slug !== '' && eca_education_ready($conn)) {
    if ($kind !== '') {
        $article = eca_education_article_by_slug($conn, $kind, $slug);
    } else {
        $article = eca_education_article_by_slug($conn, 'knowledge', $slug)
            ?: eca_education_article_by_slug($conn, 'policy', $slug);
        if ($article) {
            $kind = (string) ($article['kind'] ?? 'knowledge');
        }
    }
}
if ($kind === '') {
    $kind = 'knowledge';
}
if (!$article || ($article['status'] ?? '') !== 'PUBLISHED') {
    eca_not_found('That education article was not found.');
}

$section = $kind === 'policy' ? 'policy' : 'knowledge';
$crumb = $kind === 'policy' ? 'Membership development / Policy' : 'Membership development / Knowledge';
$kicker = $kind === 'policy' ? 'Industry & Policy Education' : 'Contractor Knowledge Centre';
eca_public_page_start((string) $article['title'], $kicker, (string) $article['title'], (string) ($article['summary'] ?? ''), $crumb);
$types = eca_education_knowledge_types();
?>
<div class="edu-wrap">
    <?php eca_education_subnav($section); ?>
    <p class="edu-kicker"><?= eca_education_h($article['category_name'] ?? $kicker) ?><?php if (!empty($article['resource_type']) && isset($types[$article['resource_type']])): ?> · <?= eca_education_h($types[$article['resource_type']]) ?><?php endif; ?></p>
    <?php if ($kind === 'policy'): ?>
        <div class="edu-policy">
            <article class="edu-policy-step">
                <h3>What changed?</h3>
                <p><?= nl2br(eca_education_h((string) ($article['what_changed'] ?: $article['summary']))) ?></p>
            </article>
            <article class="edu-policy-step">
                <h3>Why does it matter to contractors?</h3>
                <p><?= nl2br(eca_education_h((string) ($article['why_matters'] ?: 'This update affects how contractors bid, deliver or stay compliant.'))) ?></p>
            </article>
            <article class="edu-policy-step">
                <h3>What must contractors do?</h3>
                <p><?= nl2br(eca_education_h((string) ($article['contractor_action'] ?: 'Review the requirement against your current bids and site files.'))) ?></p>
            </article>
            <article class="edu-policy-step">
                <h3>What support is available from ECA?</h3>
                <p><?= nl2br(eca_education_h((string) ($article['eca_support'] ?: 'Contact the ECA secretariat for guidance, training and advocacy support.'))) ?></p>
            </article>
        </div>
    <?php endif; ?>
    <div class="edu-article">
        <p><?= nl2br(eca_education_h((string) ($article['body'] ?? ''))) ?></p>
        <?php if (!empty($article['video_url'])): ?>
            <p><a class="edu-btn" href="<?= eca_education_h($article['video_url']) ?>" rel="noopener noreferrer">Watch video</a></p>
        <?php endif; ?>
        <p><a class="edu-btn-ghost" href="<?= $kind === 'policy' ? '/education-policy.php' : '/education-knowledge.php' ?>">Back to <?= $kind === 'policy' ? 'policy education' : 'knowledge centre' ?></a></p>
    </div>
</div>
<?php eca_public_page_end(); ?>
