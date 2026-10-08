<?php
require_once __DIR__ . '/includes/public-page.php';
require_once __DIR__ . '/includes/organization.php';

eca_public_page_start(
    'Mission & Purpose',
    'About ECA',
    'Mission & Purpose',
    'ECA exists to represent, develop and support contractors so Eswatini\'s construction industry can operate professionally, competitively and sustainably.',
    'About ECA / Mission'
);
eca_org_assets();
?>
<div class="org-page">
    <div class="org-wrap">
        <section class="org-panel">
            <p class="org-kicker">About ECA</p>
            <h2>Our purpose</h2>
            <p class="org-lead">
                The Eswatini Contractors Association serves as the collective voice of contractors. Advocacy is the
                original foundation of the association. Around that core, ECA has developed complementary functions
                in digital intelligence, professionalization, member wellness and technical advisory support.
            </p>
            <div class="org-actions">
                <a class="org-btn" href="/about-structure.php">Organizational structure</a>
                <a class="org-btn-ghost" href="/about-history.php">Our History</a>
                <a class="org-btn-ghost" href="/about.php">About ECA</a>
                <a class="org-btn-ghost" href="/advocacy.php">Advocacy</a>
            </div>
        </section>

        <section class="org-panel">
            <p class="org-kicker">Strategic pillars</p>
            <h2>How ECA delivers its mission</h2>
            <div class="org-grid-3">
                <?php foreach (eca_org_pillars() as $pillar): ?>
                    <?php eca_org_link_card($pillar['verb'] . ' — ' . $pillar['short'], $pillar['blurb'], $pillar['href'], 'Explore →', $pillar['icon']); ?>
                <?php endforeach; ?>
            </div>
        </section>
    </div>
</div>
<?php eca_public_page_end(); ?>
