<?php
require_once dirname(__DIR__) . '/includes/wellness-hub.php';

$conn = eca_wellness_db();
$talks = eca_wellness_hub_items($conn, 'toolbox', 'talk');
if (!$talks) {
    $talks = eca_wellness_hub_items($conn, 'toolbox');
}

eca_wellness_page_start(
    'Hard Hat, Soft Mind',
    'Hard Hat, Soft Mind',
    'Short toolbox talks for supervisors to use with teams on construction sites.',
    'toolbox'
);
?>
<div class="edu-prose">
    <p>Use one talk at the morning huddle or after a near-miss. Two to four minutes is enough. You do not need to be a counsellor — you need a clear question and a next step.</p>
    <p>Print or read from a phone. If someone asks for help, take them to <a href="/wellness/support.php">Support &amp; Referrals</a>.</p>
</div>
<?php eca_wellness_disclaimer(); ?>

<?php if ($talks): ?>
    <?php eca_wellness_render_cards($talks); ?>
<?php else: ?>
    <div class="edu-policy">
        <?php foreach (eca_wellness_toolbox_fallback() as $talk): ?>
            <article class="edu-policy-step">
                <h3><?= eca_wellness_h($talk['title']) ?></h3>
                <p><?= eca_wellness_prose($talk['body']) ?></p>
            </article>
        <?php endforeach; ?>
    </div>
    <p class="wh-member-note">Administrators can replace these starter talks with approved Hard Hat, Soft Mind content from Wellness resources.</p>
<?php endif; ?>
<?php eca_wellness_page_end(); ?>
