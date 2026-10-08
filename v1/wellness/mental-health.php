<?php
require_once dirname(__DIR__) . '/includes/wellness-hub.php';

$topics = eca_wellness_mental_topics();
$slug = preg_replace('/[^a-z0-9-]/', '', strtolower(trim((string) ($_GET['topic'] ?? '')))) ?? '';
$topic = $topics[$slug] ?? null;
$conn = eca_wellness_db();

if ($topic) {
    eca_wellness_page_start($topic[0], $topic[0], $topic[1], 'mental');
    $related = eca_wellness_hub_items($conn, 'mental-health');
    $related = array_values(array_filter($related, static function (array $row) use ($slug): bool {
        $itemSlug = (string) ($row['topic_slug'] ?? '');
        return $itemSlug === '' || $itemSlug === $slug;
    }));
    ?>
    <p class="edu-kicker">Mental health</p>
    <div class="edu-prose">
        <p><?= eca_wellness_prose($topic[1]) ?></p>
        <p><?= eca_wellness_prose($topic[2]) ?></p>
    </div>
    <?php eca_wellness_disclaimer(); ?>
    <p><a class="edu-btn-ghost" href="/wellness/mental-health.php">All mental-health topics</a>
       <a class="edu-btn-ghost" href="/wellness/support.php">Support &amp; referrals</a></p>
    <?php if ($related): ?>
        <h2 class="wh-section-title">Related resources</h2>
        <?php eca_wellness_render_cards($related); ?>
    <?php endif; ?>
    <?php
    eca_wellness_page_end();
    return;
}

eca_wellness_page_start(
    'Mental Health',
    'Mental Health',
    'Understanding mental health at work — stress, resilience, burnout, stigma and where to get help.',
    'mental'
);
$cms = eca_wellness_hub_items($conn, 'mental-health');
?>
<div class="edu-prose">
    <p>Mental health is part of running a safe site and a viable business. These topics are for awareness and early support. They are not a clinical assessment.</p>
</div>
<?php eca_wellness_disclaimer(); ?>
<div class="edu-card-grid">
    <?php foreach ($topics as $key => $item): ?>
        <article class="edu-card">
            <p class="edu-kicker">Topic</p>
            <h3><?= eca_wellness_h($item[0]) ?></h3>
            <p><?= eca_wellness_h($item[1]) ?></p>
            <a class="edu-more" href="/wellness/mental-health.php?topic=<?= eca_wellness_h($key) ?>">Read</a>
        </article>
    <?php endforeach; ?>
</div>
<?php if ($cms): ?>
    <h2 class="wh-section-title">Published articles</h2>
    <?php eca_wellness_render_cards($cms); ?>
<?php endif; ?>
<?php eca_wellness_page_end(); ?>
