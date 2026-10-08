<?php
require_once __DIR__ . '/includes/public-page.php';
require_once __DIR__ . '/includes/organization.php';

eca_public_page_start(
    'Organizational Structure',
    'About ECA',
    'Organizational Structure',
    'ECA has evolved from its core advocacy mission into a broader organization supporting contractors through intelligence, development, advisory support and care.',
    'About ECA / Structure'
);
eca_org_assets();
?>
<div class="org-page">
    <div class="org-wrap">
        <section class="org-panel">
            <p class="org-kicker">About ECA</p>
            <h2>How ECA is organized</h2>
            <p class="org-lead"><?= eca_org_h(eca_org_operating_intro()) ?></p>
            <?php eca_org_note('Leadership details are published on the Executive committee page. This page describes organizational functions — not individual appointments.'); ?>
            <div class="org-source-required" role="status" style="margin:12px 0 0;padding:12px 14px;border:1px dashed #c2410c;border-radius:12px;background:#fff7ed;color:#7a2e0e;font-size:0.92rem;">
                <strong>SOURCE REQUIRED — Board currency.</strong>
                Please confirm that the Executive Committee listing on the Leadership page remains current before treating it as authoritative for the current term.
            </div>
            <div class="org-actions">
                <a class="org-btn" href="/about-bod.php">Leadership</a>
                <a class="org-btn-ghost" href="/about-mission.php">Mission &amp; purpose</a>
                <a class="org-btn-ghost" href="/about-history.php">Our History</a>
                <a class="org-btn-ghost" href="/about.php">About ECA</a>
            </div>
        </section>

        <section class="org-panel">
            <p class="org-kicker">Strategic pillars</p>
            <h2>Five organizational functions</h2>
            <div class="org-grid-2">
                <?php foreach (eca_org_pillars() as $pillar): ?>
                    <a class="org-link-card" href="<?= eca_org_h($pillar['href']) ?>">
                        <span class="org-link-icon" aria-hidden="true"><i class="fas <?= eca_org_h($pillar['icon']) ?>"></i></span>
                        <h3><?= eca_org_h($pillar['title']) ?></h3>
                        <p><?= eca_org_h($pillar['focus']) ?></p>
                        <span class="org-link-cta">Open section →</span>
                    </a>
                <?php endforeach; ?>
            </div>
        </section>

        <section class="org-panel">
            <p class="org-kicker">How the functions connect</p>
            <h2>Operating relationships</h2>
            <ul class="org-lead" style="padding-left:18px;max-width:760px;">
                <li>Digital Systems &amp; Data Intelligence generates actionable industry intelligence.</li>
                <li>That intelligence can support Advocacy, Policy &amp; Legal Affairs.</li>
                <li>Data can identify competency gaps for Professionalization &amp; Capacity Building.</li>
                <li>Professionalization develops training and CPD.</li>
                <li>Member Wellness, Inclusivity &amp; CSR Operations addresses the person behind the company.</li>
                <li>Technical Support &amp; Advisory provides practical business, contract and tender support.</li>
            </ul>
        </section>

        <section class="org-panel">
            <p class="org-kicker">Operating model</p>
            <h2>From listening to impact</h2>
            <div class="org-flow">
                <?php foreach (eca_org_operating_steps() as $i => $step): ?>
                    <div class="org-flow-step">
                        <strong><?= eca_org_h($step[0]) ?></strong>
                        <span><?= eca_org_h($step[1]) ?></span>
                    </div>
                    <?php if ($i < count(eca_org_operating_steps()) - 1): ?>
                        <div class="org-flow-arrow" aria-hidden="true">↓</div>
                    <?php endif; ?>
                <?php endforeach; ?>
            </div>
        </section>
    </div>
</div>
<?php eca_public_page_end(); ?>
