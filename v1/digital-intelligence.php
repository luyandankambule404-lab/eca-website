<?php
require_once __DIR__ . '/includes/public-page.php';
require_once __DIR__ . '/includes/organization.php';

eca_public_page_start(
    'Digital Systems & Data Intelligence',
    'What ECA does',
    'Digital Systems & Data Intelligence',
    'ECA uses digital systems and contractor information to generate actionable industry intelligence that supports advocacy, development and member services.',
    'Digital Intelligence'
);
eca_org_assets();
?>
<div class="org-page">
    <div class="org-wrap">
        <?php eca_org_pillar_nav('digital'); ?>

        <section class="org-panel">
            <p class="org-kicker">Understand</p>
            <h2>Digital Systems &amp; Data Intelligence</h2>
            <p class="org-lead">
                Digital registration, contractor information and market visibility help ECA understand industry conditions
                and support better representation and development decisions.
            </p>
            <?php eca_org_note('This page is an organizational overview. It links to existing public tools — it does not replace the member directory, tenders or verification systems.'); ?>
        </section>

        <section class="org-panel">
            <p class="org-kicker">Public tools</p>
            <h2>Explore existing digital services</h2>
            <div class="org-grid-3">
                <?php
                eca_org_link_card('Digital Registration Portal', 'Start or continue membership registration online.', '/membership-registration.php', 'Register →', 'fa-id-card');
                eca_org_link_card('Contractor Database', 'Search the public member directory of ECA contractors.', '/directory.php', 'Open directory →', 'fa-database');
                eca_org_link_card('Verify Membership', 'Confirm membership status using existing verification tools.', '/verify.php', 'Verify →', 'fa-check-circle');
                eca_org_link_card('Track Application', 'Follow the status of a membership application.', '/track.php', 'Track →', 'fa-search');
                eca_org_link_card('Tender Intelligence', 'View published tenders and opportunities on the public site.', '/tenders.php', 'View tenders →', 'fa-gavel');
                eca_org_link_card('Balingani Directory', 'Explore the women-in-construction directory.', '/balingani-directory.php', 'Open Balingani →', 'fa-users');
                ?>
            </div>
        </section>

        <section class="org-panel">
            <p class="org-kicker">Focus areas</p>
            <h2>Where intelligence supports the industry</h2>
            <div class="org-grid-2">
                <?php
                eca_org_topic_card('Contractor Insights', 'Understanding contractor profiles and industry participation patterns.', 'fa-chart-bar');
                eca_org_topic_card('Market Intelligence', 'Supporting industry awareness through published opportunities and updates.', 'fa-chart-pie');
                eca_org_topic_card('Industry Research', 'Using information to inform advocacy and capacity-building priorities.', 'fa-flask');
                eca_org_topic_card('Contractor Challenges', 'Helping surface practical issues that affect business delivery and growth.', 'fa-exclamation-circle');
                ?>
            </div>
            <?php eca_org_note('Detailed research publications and internal analytics remain unpublished here unless already available on the public site.'); ?>
        </section>
    </div>
</div>
<?php eca_public_page_end(); ?>
