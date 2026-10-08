<?php
/**
 * About → Our History — presentation only.
 * Content is drawn from existing verified About / homepage history material.
 * No invented dates, events, people, or claims.
 */
require_once __DIR__ . '/includes/public-page.php';
require_once __DIR__ . '/includes/organization.php';

eca_public_page_start(
    'Our History',
    'About ECA',
    'Our History',
    'ECA\'s development from a contractor coalition in 1991 into the professional body representing contractors in Eswatini today.',
    'About ECA / History'
);
eca_org_assets();
?>
<div class="org-page">
    <div class="org-wrap">
        <section class="org-panel">
            <p class="org-kicker">About ECA</p>
            <h2>How ECA developed</h2>
            <p class="org-lead">
                This page summarises verified historical information already published on the ECA website.
                It is kept separate from the current organizational structure and programme pages.
            </p>
            <div class="org-actions">
                <a class="org-btn" href="/about.php">About ECA</a>
                <a class="org-btn-ghost" href="/about-mission.php">Mission &amp; purpose</a>
                <a class="org-btn-ghost" href="/about-structure.php">Organizational structure</a>
                <a class="org-btn-ghost" href="/about-bod.php">Leadership</a>
            </div>
        </section>

        <section class="org-panel">
            <p class="org-kicker">Verified timeline</p>
            <h2>Key milestones</h2>
            <ol class="org-history-list">
                <li>
                    <strong>1991</strong>
                    <p>ECA's roots were established through a coalition of local contractors with a shared vision to promote and protect the interests of Swati contractors.</p>
                </li>
                <li>
                    <strong>2013</strong>
                    <p>ECA played a leading role in drafting the Construction Industry Council (CIC) Act, following advocacy for a regulatory body to bring structure and accountability to the industry — particularly around government procurement procedures.</p>
                </li>
                <li>
                    <strong>2018</strong>
                    <p>Following the country's official name change, the Association began the transition that led to its current ECA identity.</p>
                </li>
                <li>
                    <strong>2019</strong>
                    <p>The Association adopted its current ECA identity (formerly the Swaziland Contractors Association).</p>
                </li>
                <li>
                    <strong>2020</strong>
                    <p>The Women in Construction initiative strengthened focus on participation in the sector.</p>
                </li>
                <li>
                    <strong>2021 – Present</strong>
                    <p>ECA continues representing contractors and advancing Eswatini's construction industry through advocacy and related member services.</p>
                </li>
            </ol>
            <?php eca_org_note('Timeline items above are taken from existing verified ECA website content (About and homepage history). No additional historical claims have been added.'); ?>
        </section>

        <section class="org-panel">
            <p class="org-kicker">From history to today</p>
            <h2>Organizational development</h2>
            <p class="org-lead">
                Advocacy remains the original foundation of the association. Around that core, ECA has developed
                complementary functions described on the organizational structure page — digital systems and data
                intelligence, professionalization and capacity building, member wellness, inclusivity and CSR
                operations, and technical support and advisory.
            </p>
            <div class="org-actions">
                <a class="org-btn" href="/about-structure.php">View organizational structure</a>
                <a class="org-btn-ghost" href="/advocacy.php">Advocacy</a>
            </div>
        </section>
    </div>
</div>
<?php eca_public_page_end(); ?>
