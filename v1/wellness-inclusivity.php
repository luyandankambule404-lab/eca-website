<?php
require_once __DIR__ . '/includes/public-page.php';
require_once __DIR__ . '/includes/organization.php';

eca_public_page_start(
    'Member Wellness, Inclusivity & CSR Operations',
    'What ECA does',
    'Member Wellness, Inclusivity & CSR Operations',
    'Supporting the person behind the company through wellness, inclusion and social responsibility.',
    'Wellness & Inclusivity'
);
eca_org_assets();
?>
<div class="org-page">
    <div class="org-wrap">
        <?php eca_org_pillar_nav('care'); ?>

        <section class="org-panel">
            <p class="org-kicker">Care</p>
            <h2>Member Wellness, Inclusivity &amp; CSR Operations</h2>
            <p class="org-lead">
                Source focus: caring for the person behind the company. This public hub links existing Wellness and
                Balingani tools and presents source-confirmed themes without inventing programme outcomes.
            </p>
            <?php eca_org_note('Wellness tools and events reuse the existing Wellness Hub. No new wellness database is introduced here.'); ?>
            <div class="org-actions">
                <a class="org-btn" href="/wellness/">Open Wellness Hub</a>
                <a class="org-btn-ghost" href="/balingani-directory.php">Balingani directory</a>
            </div>
        </section>

        <section class="org-panel">
            <p class="org-kicker">Balingani</p>
            <h2>Women in Construction</h2>
            <div class="org-grid-2">
                <?php
                eca_org_link_card('Balingani Directory', 'Discover women-owned and women-led contractors in the public directory.', '/balingani-directory.php', 'View directory →', 'fa-female');
                eca_org_topic_card('Inclusion & Empowerment', 'Supporting inclusive industry participation and visibility for women in construction.', 'fa-handshake');
                eca_org_topic_card(
                    'Mentorship — SOURCE REQUIRED',
                    'ECA source material identifies mentorship under the Balingani Wing. Programme structure, eligibility, schedules and application steps remain SOURCE REQUIRED before a mentorship product can be published.',
                    'fa-user-friends'
                );
                ?>
            </div>
            <?php eca_org_note('Management placeholder: provide mentorship brief (purpose, who qualifies, how to join, contact owner) before building signup features.'); ?>
        </section>

        <section class="org-panel">
            <p class="org-kicker">Wellness</p>
            <h2>Member wellness support</h2>
            <div class="org-grid-3">
                <?php
                eca_org_link_card('Wellness Hub', 'Public wellness home for resources, support and information.', '/wellness/', 'Open →', 'fa-heartbeat');
                eca_org_link_card('Mental Health', 'Mental health information within the Wellness Hub.', '/wellness/mental-health.php', 'Open →', 'fa-brain');
                eca_org_link_card('Wellness Library', 'Articles and wellness resources.', '/wellness/library.php', 'Browse →', 'fa-book-open');
                eca_org_link_card('Support & referrals', 'Documented pathways for counselling referrals and psychosocial support.', '/wellness/support.php', 'View pathway →', 'fa-hands-helping');
                eca_org_link_card('Member Wellness Events', 'Signed-in member wellness events (login required).', '/client/wellness/', 'Member access →', 'fa-calendar-check');
                ?>
            </div>
        </section>

        <section class="org-panel" id="counseling-pathway">
            <p class="org-kicker">Counselling pathway</p>
            <h2>How to seek counselling or psychosocial support</h2>
            <p class="org-lead">
                Documented model: the Wellness Hub provides information and early support. ECA does not diagnose or treat
                from the website. Counselling is referral-based — contact the ECA office to ask for a referral pathway.
            </p>
            <ol class="org-history-list">
                <li>
                    <strong>1. Start with Wellness Hub guidance</strong>
                    <p>Use Mental Health resources, the Contractor Check-In, and Support &amp; Referrals for practical next steps.</p>
                </li>
                <li>
                    <strong>2. Ask ECA for a referral pathway</strong>
                    <p>Contact the office (phone, email, or contact form with enquiry type “Wellness / counselling referral request”). Staff share approved referral information when published by the administrator.</p>
                </li>
                <li>
                    <strong>3. Use approved organisations only</strong>
                    <p>Only organisations listed on Support &amp; Referrals are published as verified. If none are listed yet, use ECA office contacts — this site will not invent provider names.</p>
                </li>
                <li>
                    <strong>4. Emergencies</strong>
                    <p>If someone is in immediate danger, contact local emergency services first. Then tell someone you trust, and contact ECA when it is safe.</p>
                </li>
            </ol>
            <div class="org-actions">
                <a class="org-btn" href="/wellness/support.php">Open Support &amp; Referrals</a>
                <a class="org-btn-ghost" href="/contact.php?enquiry_type=wellness&amp;subject=<?= rawurlencode('Wellness / counselling referral request') ?>">Contact ECA</a>
                <a class="org-btn-ghost" href="/wellness/check-in.php">Contractor Check-In</a>
            </div>
            <?php eca_org_note('Source: existing Wellness Hub support pathways. No clinical booking system is claimed or built here.'); ?>
        </section>

        <section class="org-panel">
            <p class="org-kicker">Wellness &amp; financial services</p>
            <h2>Source-confirmed financial theme</h2>
            <div class="org-grid-2">
                <?php
                eca_org_topic_card(
                    'Smart Loan financial services',
                    'ECA source material identifies a Smart Loan financial services theme within wellness and financial support, including easing cash-flow and personal stressors. Detailed product terms, partners and application pathways are SOURCE REQUIRED before public service claims.',
                    'fa-hand-holding-usd'
                );
                eca_org_topic_card(
                    'Financial literacy link',
                    'Technical Support & Advisory source material links financial literacy and working-capital guidance to Smart Loan services in the Wellness department. No loan product is sold on this page.',
                    'fa-coins'
                );
                ?>
            </div>
            <?php eca_org_note('SOURCE LOCK: Smart Loan is named in ECA organizational source material. Application process, interest rates, partners, eligibility and outcomes remain SOURCE REQUIRED.'); ?>
        </section>

        <section class="org-panel">
            <p class="org-kicker">Social impact</p>
            <h2>Inclusivity &amp; CSR themes</h2>
            <div class="org-grid-2">
                <?php
                eca_org_topic_card('Social Inclusion', 'Broader social inclusion themes within member wellness and CSR operations.', 'fa-users');
                eca_org_topic_card('Environmental Responsibility', 'Environmental responsibility is identified in ECA source material as part of holistic wellness and social impact.', 'fa-leaf');
                eca_org_topic_card('Disability Inclusion', 'Disability advocacy is identified in ECA source material under social inclusion.', 'fa-universal-access');
                eca_org_topic_card(
                    'Autism Awareness',
                    'ECA source material identifies autism awareness / autism advocacy within social inclusion and CSR-related engagement. Programme schedules, partners and outcomes are SOURCE REQUIRED.',
                    'fa-heart'
                );
                eca_org_topic_card(
                    'CSR — SOURCE REQUIRED detail',
                    'CSR initiatives are named in ECA source material. Public programme descriptions, partners and calendars remain SOURCE REQUIRED before campaign pages are published.',
                    'fa-globe-africa'
                );
                ?>
            </div>
            <?php eca_org_note('SOURCE LOCK: Autism Awareness is named in ECA organizational source material. Specific campaigns, dates and partners remain SOURCE REQUIRED.'); ?>
            <div class="org-source-required" role="status">
                <strong>Management placeholder — CSR / social impact pack needed:</strong>
                purpose, public activities, partners (public-safe), and contact owner before expanding beyond theme cards.
            </div>
        </section>
    </div>
</div>
<?php eca_public_page_end(); ?>
