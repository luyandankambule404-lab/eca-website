<?php
require_once __DIR__ . '/includes/public-page.php';
require_once __DIR__ . '/includes/organization.php';

eca_public_page_start(
    'Technical Support & Advisory',
    'What ECA does',
    'Technical Support & Advisory',
    'Practical guidance areas for business management, contract administration and tender support — presented as an organizational service overview.',
    'Technical Support'
);
eca_org_assets();
?>
<div class="org-page">
    <div class="org-wrap">
        <?php eca_org_pillar_nav('support'); ?>

        <section class="org-panel">
            <p class="org-kicker">Support</p>
            <h2>Technical Support &amp; Advisory</h2>
            <p class="org-lead">
                This pillar describes advisory themes that help contractors manage business health, contracts and
                procurement challenges with greater confidence.
            </p>
            <?php eca_org_note('Informational organizational themes from ECA source material. This page does not create an advisory case-management system. Where a live public tool exists (for example published tenders), it is linked separately as “Available now”.'); ?>
            <div class="org-actions">
                <a class="org-btn" href="/contact.php?enquiry_type=technical&amp;subject=<?= rawurlencode('Technical Support & Advisory enquiry') ?>">Request advisory guidance</a>
                <a class="org-btn-ghost" href="/faq.php">FAQs</a>
                <a class="org-btn-ghost" href="/resources.php">Resources</a>
                <a class="org-btn-ghost" href="/tenders.php">Published tenders</a>
            </div>
        </section>

        <section class="org-panel" id="advisory-intake">
            <p class="org-kicker">Available now</p>
            <h2>Advisory intake (existing contact tickets)</h2>
            <p class="org-lead">
                Members and contractors can request Technical Support &amp; Advisory guidance through the existing Contact form.
                Selecting enquiry type <strong>Technical Support &amp; Advisory</strong> prefixes the ticket subject with
                <code>[Technical Advisory]</code> so staff can filter tickets — without a new CRM or database column.
            </p>
            <div class="org-grid-2">
                <?php
                eca_org_link_card('Submit advisory enquiry', 'Uses the existing contact ticket workflow (status, assignment, reply).', '/contact.php?enquiry_type=technical&subject=' . rawurlencode('Technical Support & Advisory enquiry'), 'Open form →', 'fa-envelope');
                eca_org_topic_card(
                    'Not a case-management system',
                    'Early dispute resolution and contract guidance are organizational themes. Live delivery scope (office hours, eligibility, SLA) remains SOURCE REQUIRED before stronger service claims.',
                    'fa-info-circle'
                );
                ?>
            </div>
        </section>

        <section class="org-panel" id="business">
            <p class="org-kicker">Business Management &amp; Growth</p>
            <h2>Business health and growth themes</h2>
            <div class="org-grid-3">
                <?php
                eca_org_topic_card('Business Health Checks', 'Reviewing practical business pressures that affect contractor sustainability.', 'fa-stethoscope');
                eca_org_topic_card('Financial Literacy & Management', 'Supporting stronger financial awareness for contractor businesses.', 'fa-coins');
                eca_org_topic_card('Cash Flow Management', 'Helping contractors think through cash-flow risk and payment timing.', 'fa-wallet');
                eca_org_topic_card('Strategic Planning', 'Planning for growth, resilience and market positioning.', 'fa-map');
                eca_org_topic_card('Market Diversification', 'Exploring broader market opportunities where appropriate.', 'fa-random');
                eca_org_topic_card('Compliance & Registration Support', 'Guidance themes linked to membership and industry participation requirements.', 'fa-clipboard-check');
                ?>
            </div>
        </section>

        <section class="org-panel" id="contracts">
            <p class="org-kicker">Contract Management &amp; Administration</p>
            <h2>Contract and project administration themes</h2>
            <div class="org-grid-3">
                <?php
                eca_org_topic_card('Contract Clause Interpretation', 'Understanding contractual obligations and practical implications.', 'fa-file-contract');
                eca_org_topic_card('FIDIC / JBCC Awareness', 'Familiarity with common construction contract frameworks used in the industry.', 'fa-balance-scale');
                eca_org_topic_card('Project Administration', 'Guidance themes for orderly project delivery under construction contracts.', 'fa-tasks');
                eca_org_topic_card(
                    'Site Records',
                    'Source material identifies best practices for maintaining site records as part of project administration guidance. Detailed templates and delivery workflows are SOURCE REQUIRED.',
                    'fa-clipboard-list'
                );
                eca_org_topic_card('Variations & Change Orders', 'Managing scope change (variations / change orders) in a structured way.', 'fa-exchange-alt');
                eca_org_topic_card('Extension of Time & Formal Notices', 'Handling extension of time (EOT) claims and issuing formal notices.', 'fa-clock');
                eca_org_topic_card('Subcontractor Management', 'Advisory themes on drafting and managing agreements with sub-trades to minimize risk.', 'fa-user-friends');
                eca_org_topic_card('Early Dispute Resolution', 'Advisory themes to resolve conflicts before they escalate to litigation or arbitration.', 'fa-comments');
                ?>
            </div>
            <?php eca_org_note('SOURCE LOCK: Site Records is named in ECA Technical Support source material under Project Administration Guidance. No downloadable site-record system is claimed here.'); ?>
        </section>

        <section class="org-panel" id="tenders">
            <p class="org-kicker">Tender &amp; Procurement Support</p>
            <h2>Tender readiness themes</h2>
            <div class="org-grid-3">
                <?php
                eca_org_link_card('Published Tenders', 'Review opportunities already published on the ECA public site.', '/tenders.php', 'View tenders →', 'fa-gavel');
                eca_org_topic_card('Tender Documentation Review', 'Understanding tender requirements before submission.', 'fa-file-alt');
                eca_org_topic_card('Bid Costing & Estimation', 'Pricing discipline and estimation awareness for competitive bids.', 'fa-calculator');
                eca_org_topic_card('Tender Risk Assessment', 'Identifying delivery, commercial and compliance risks early.', 'fa-exclamation-triangle');
                eca_org_topic_card('Procurement Support', 'Navigating procurement processes with clearer preparation.', 'fa-shopping-cart');
                eca_org_topic_card('Market & Pricing Awareness', 'Using public opportunity information to stay informed.', 'fa-chart-line');
                ?>
            </div>
        </section>
    </div>
</div>
<?php eca_public_page_end(); ?>
