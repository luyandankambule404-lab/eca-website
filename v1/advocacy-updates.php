<?php
/**
 * Dedicated Advocacy Updates listing — reuses existing news rows (category Advocacy).
 * No schema changes. Empty state when no verified updates exist.
 */
require_once __DIR__ . '/includes/public-page.php';
require_once __DIR__ . '/includes/organization.php';
require_once __DIR__ . '/includes/advocacy-content.php';
require_once __DIR__ . '/config.php';

$db = null;
try {
    $db = (new Database())->getConnection(false);
} catch (Throwable $e) {
    $db = null;
}
$updates = eca_fetch_advocacy_updates($db, 20);

eca_public_page_start(
    'Advocacy Updates',
    'Advocacy, Policy & Legal Affairs',
    'Advocacy Updates',
    'Verified public updates related to ECA advocacy, policy and industry representation.',
    'Advocacy / Updates'
);
eca_org_assets();
?>
<link rel="stylesheet" href="/css/advocacy.css?v=20261008-adv2">
<div class="advocacy-page org-page">
    <div class="adv-wrap">
        <?php eca_org_pillar_nav('advocacy'); ?>
        <section class="adv-panel">
            <p class="adv-kicker">Advocacy, Policy &amp; Legal Affairs</p>
            <h2>Advocacy Updates</h2>
            <p class="adv-lead">
                This page lists news items categorised as Advocacy. It does not invent campaigns.
                Items labelled LOCAL DEMO never appear here.
            </p>
            <div class="adv-hero-actions">
                <a class="adv-btn" href="/advocacy.php">Advocacy overview</a>
                <a class="adv-btn-ghost" href="/contact.php?enquiry_type=advocacy&amp;subject=<?= rawurlencode('Raise an Industry Issue') ?>">Raise an industry issue</a>
            </div>
        </section>
        <section class="adv-panel" aria-labelledby="advUpdatesListHeading">
            <h2 id="advUpdatesListHeading" class="eca-sr-only">Update list</h2>
            <?php eca_render_advocacy_updates($updates); ?>
        </section>
    </div>
</div>
<?php eca_public_page_end(); ?>
