<?php
require_once dirname(__DIR__) . '/includes/wellness-hub.php';

eca_wellness_page_start(
    'Wellness Hub',
    'Wellness Hub',
    'Practical support for the people behind the projects.',
    'home',
    [
        'STRONG MINDS.',
        'STRONG BUSINESSES.',
        'STRONG BUILDS.',
    ]
);
$conn = eca_wellness_db();
$sections = eca_wellness_hub_sections();
unset($sections['home']);
?>
<section class="edu-prose" aria-labelledby="wh-welcome">
    <h2 id="wh-welcome">Welcome to the Wellness Hub</h2>
    <p>The Wellness Hub is part of the ECA digital platform — alongside membership, education, projects and financial-support services. It is here for contractors, supervisors and site teams who carry the pressure of programmes, cash flow, safety and people.</p>
    <p>Use it to understand mental health, look after the eight dimensions of wellness, borrow a short toolbox talk, check in with yourself, and find a clear path to support. Nothing here is a diagnosis.</p>
</section>
<?php eca_wellness_disclaimer(); ?>
<div class="edu-hub-grid">
    <?php foreach ($sections as $item): ?>
        <a class="edu-hub-card" href="<?= eca_wellness_h($item[1]) ?>">
            <i class="fas <?= eca_wellness_h($item[2]) ?>" aria-hidden="true"></i>
            <h2><?= eca_wellness_h($item[0]) ?></h2>
            <p><?= eca_wellness_h($item[3]) ?></p>
            <span>Open section</span>
        </a>
    <?php endforeach; ?>
</div>
<p class="wh-member-note">Members can still <a href="/client/wellness/">sign in</a> for wellness events and member-only materials. The public Hub stays open to every contractor and team.</p>
<?php
if ($conn) {
    $latest = eca_wellness_library_items($conn, '');
    $latest = array_slice($latest, 0, 3);
    if ($latest) {
        echo '<h2 class="wh-section-title">From the Wellness Library</h2>';
        eca_wellness_render_cards($latest);
    }
}
eca_wellness_page_end();
