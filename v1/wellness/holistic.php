<?php
require_once dirname(__DIR__) . '/includes/wellness-hub.php';

$dims = eca_wellness_dimensions();
$slug = preg_replace('/[^a-z0-9-]/', '', strtolower(trim((string) ($_GET['dimension'] ?? '')))) ?? '';
$dim = $dims[$slug] ?? null;
$conn = eca_wellness_db();

if ($dim) {
    eca_wellness_page_start($dim[0] . ' wellness', $dim[0] . ' wellness', $dim[1], 'holistic');
    $related = eca_wellness_hub_items($conn, 'holistic');
    $related = array_values(array_filter($related, static function (array $row) use ($slug): bool {
        $itemSlug = (string) ($row['topic_slug'] ?? '');
        return $itemSlug === '' || $itemSlug === $slug;
    }));
    ?>
    <p class="edu-kicker">Holistic wellness</p>
    <div class="edu-prose">
        <p><?= eca_wellness_prose($dim[1]) ?></p>
        <p>No single dimension stands alone. A late payment (financial) can disturb sleep (physical) and snap at the crew (social). Use the other dimensions on this page to see the full picture.</p>
    </div>
    <?php eca_wellness_disclaimer(); ?>
    <p><a class="edu-btn-ghost" href="/wellness/holistic.php">All eight dimensions</a>
       <a class="edu-btn-ghost" href="/wellness/check-in.php">Contractor Check-In</a></p>
    <?php if ($related): ?>
        <h2 class="wh-section-title">Related resources</h2>
        <?php eca_wellness_render_cards($related); ?>
    <?php endif; ?>
    <?php
    eca_wellness_page_end();
    return;
}

eca_wellness_page_start(
    'Holistic Wellness',
    'Holistic Wellness',
    'Eight dimensions of wellness for contractors and their teams — body, mind, work, money and community.',
    'holistic'
);
$cms = eca_wellness_hub_items($conn, 'holistic');
?>
<div class="edu-prose">
    <p>Wellness is more than the absence of injury. These eight dimensions sit together. A strong business still needs rest, meaning, fair work and people who look out for each other.</p>
</div>
<?php eca_wellness_disclaimer(); ?>
<div class="edu-card-grid">
    <?php foreach ($dims as $key => $item): ?>
        <article class="edu-card">
            <p class="edu-kicker">Dimension</p>
            <h3><?= eca_wellness_h($item[0]) ?></h3>
            <p><?= eca_wellness_h($item[1]) ?></p>
            <a class="edu-more" href="/wellness/holistic.php?dimension=<?= eca_wellness_h($key) ?>">Open</a>
        </article>
    <?php endforeach; ?>
</div>
<?php if ($cms): ?>
    <h2 class="wh-section-title">Published articles</h2>
    <?php eca_wellness_render_cards($cms); ?>
<?php endif; ?>
<?php eca_wellness_page_end(); ?>
