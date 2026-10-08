<?php
require_once __DIR__ . '/includes/public-page.php';
require_once __DIR__ . '/includes/education.php';

$intro = 'ECA Education & Capacity Building provides contractors and their teams with practical knowledge, skills and resources to operate professionally, competitively and sustainably.';
eca_public_page_start(
    'Education & Capacity Building',
    'ECA Education & Capacity Building',
    'Building knowledge',
    $intro,
    'Membership development',
    [
        'BUILDING KNOWLEDGE.',
        'STRENGTHENING CONTRACTORS.',
        'PROFESSIONALISING THE INDUSTRY.',
    ]
);
?>
<div class="edu-wrap">
    <?php eca_education_subnav('hub'); ?>
    <div class="edu-hub-grid">
        <?php foreach (eca_education_sections() as $item): ?>
            <a class="edu-hub-card" href="<?= eca_education_h($item[1]) ?>">
                <i class="fas <?= eca_education_h($item[2]) ?>" aria-hidden="true"></i>
                <h2><?= eca_education_h($item[0]) ?></h2>
                <p><?= eca_education_h($item[3]) ?></p>
                <span>Open section</span>
            </a>
        <?php endforeach; ?>
    </div>
</div>
<?php eca_public_page_end(); ?>
