<?php
require_once __DIR__ . '/includes/public-page.php';
require_once __DIR__ . '/includes/education.php';

$conn = eca_education_db();
$programmes = ($conn && eca_education_ready($conn)) ? eca_education_public_programmes($conn) : [];

eca_public_page_start(
    'Contractor Development',
    'Membership development',
    'Contractor Development',
    'Development programmes go beyond a single course. ECA can add new programmes here as they are designed and funded.',
    'Membership development / Development'
);
?>
<div class="edu-wrap">
    <?php eca_education_subnav('development'); ?>
    <?php if (!$programmes): ?>
        <?php eca_education_empty('No published development programmes are listed yet.'); ?>
    <?php else: ?>
        <div class="edu-card-grid">
            <?php foreach ($programmes as $row): ?>
                <article class="edu-card">
                    <?php if (!empty($row['is_featured'])): ?><p class="edu-kicker">Featured</p><?php endif; ?>
                    <h3><?= eca_education_h($row['title'] ?? '') ?></h3>
                    <p><?= eca_education_h($row['summary'] ?? '') ?></p>
                    <a class="edu-more" href="/education-programme.php?slug=<?= urlencode((string) $row['slug']) ?>">View programme</a>
                </article>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>
<?php eca_public_page_end(); ?>
