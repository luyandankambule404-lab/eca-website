<?php
require_once dirname(__DIR__) . '/includes/wellness-hub.php';

$conn = eca_wellness_db();
$referrals = eca_wellness_hub_items($conn, 'support', 'referral');
if (!$referrals) {
    $referrals = eca_wellness_hub_items($conn, 'support');
}

eca_wellness_page_start(
    'Support & Referrals',
    'Support & Referrals',
    'Clear pathways to counselling, psychosocial support, professional services and emergency help.',
    'support'
);
?>
<div class="edu-prose">
    <p>If you need help now, start with the pathway that matches the situation. ECA can point you toward support. We do not provide clinical treatment from this page.</p>
    <p><strong>Documented counselling model:</strong> referral-based via the ECA office — not an online appointment or medical booking system. <a href="/wellness-inclusivity.php#counseling-pathway">See the counselling pathway overview</a>.</p>
</div>
<?php eca_wellness_disclaimer(); ?>

<div class="edu-policy">
    <?php foreach (eca_wellness_support_pathways() as $row): ?>
        <article class="edu-policy-step">
            <h3><?= eca_wellness_h($row[0]) ?></h3>
            <p><?= eca_wellness_h($row[1]) ?></p>
        </article>
    <?php endforeach; ?>
</div>

<section class="edu-prose" aria-labelledby="wh-eca-contact">
    <h2 id="wh-eca-contact">ECA office</h2>
    <p>Suite 40, Cooper Centre, Mbabane, Eswatini</p>
    <p><a href="tel:+26824044987">+268 2404 4987</a> · <a href="mailto:info@eca.co.sz">info@eca.co.sz</a> · <a href="/contact.php">Contact form</a></p>
    <p>Ask for wellness or member-support guidance. Staff can share approved referral information when it has been published by the administrator.</p>
</section>

<h2 class="wh-section-title">Approved referrals</h2>
<?php
eca_wellness_render_cards(
    $referrals,
    'No approved organisations are listed yet. ECA will only publish support organisations it has verified — this page will not invent names.'
);
eca_wellness_page_end();
