<?php
require_once dirname(__DIR__) . '/includes/wellness-hub.php';

$conn = eca_wellness_db();
$groups = eca_wellness_hub_items($conn, 'groups', 'group');
if (!$groups) {
    $groups = eca_wellness_hub_items($conn, 'groups');
}

eca_wellness_page_start(
    'Online Support Groups',
    'Online Support Groups',
    'A controlled area for approved, moderated groups — with privacy rules, not an open chat.',
    'groups'
);
?>
<div class="edu-prose">
    <p>Support groups listed here are approved by ECA. They are for shared experience among contractors and teams. They are not therapy, and they are not a public forum.</p>
</div>
<?php eca_wellness_disclaimer(); ?>

<h2 class="wh-section-title">Privacy and moderation</h2>
<div class="edu-policy">
    <?php foreach (eca_wellness_group_rules() as $rule): ?>
        <article class="edu-policy-step">
            <p><?= eca_wellness_h($rule) ?></p>
        </article>
    <?php endforeach; ?>
</div>

<h2 class="wh-section-title">Approved groups</h2>
<?php
eca_wellness_render_cards(
    $groups,
    'No approved groups are published yet. When the administrator adds a group, joining details and rules will appear here.'
);
eca_wellness_page_end();
