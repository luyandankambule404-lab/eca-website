<?php
/**
 * Public Advocacy page — additive content/UI only.
 * No schema, admin redesign, or RBAC changes. Reads existing news rows when available.
 */
require_once __DIR__ . '/includes/public-page.php';
require_once __DIR__ . '/includes/organization.php';
require_once __DIR__ . '/includes/advocacy-content.php';
require_once __DIR__ . '/config.php';

$issueHref = '/contact.php?enquiry_type=advocacy&subject=' . rawurlencode('Raise an Industry Issue');
$advocacyDb = null;
try {
    $advocacyDb = (new Database())->getConnection(false);
} catch (Throwable $e) {
    $advocacyDb = null;
}
$advocacyUpdates = eca_fetch_advocacy_updates($advocacyDb, 8);

eca_public_page_start(
    'Advocacy, Policy & Legal Affairs',
    'Advocacy, Policy & Legal Affairs',
    'THE VOICE OF ESWATINI\'S CONTRACTORS',
    'Advocating for a stronger, fairer and more sustainable construction industry.',
    'Advocacy'
);
eca_org_assets();
?>
<link rel="stylesheet" href="/css/advocacy.css?v=20261008-adv2">

<div class="advocacy-page org-page">
    <div class="adv-wrap">
        <?php eca_org_pillar_nav('advocacy'); ?>
        <section class="adv-panel" aria-labelledby="advIntroHeading">
            <header class="adv-panel-head">
                <p class="adv-kicker">Listen. Represent. Advocate. Impact.</p>
                <h2 id="advIntroHeading">ECA represents the collective voice of contractors</h2>
                <p class="adv-lead">
                    Advocacy, Policy &amp; Legal Affairs is the original heartbeat of the association. ECA engages
                    policymakers and industry stakeholders on issues that affect the construction industry — so
                    contractors are heard, represented and supported.
                </p>
                <div class="adv-hero-actions">
                    <a class="adv-btn" href="#adv-map">Advocacy subsection map</a>
                    <a class="adv-btn-ghost" href="<?= htmlspecialchars($issueHref, ENT_QUOTES, 'UTF-8') ?>">Raise an Industry Issue</a>
                </div>
            </header>
        </section>

        <section class="adv-panel" id="adv-map" aria-labelledby="advMapHeading">
            <header class="adv-panel-head">
                <p class="adv-kicker">Organizational fit</p>
                <h2 id="advMapHeading">Advocacy subsection map</h2>
                <p class="adv-lead">
                    Source-backed focus areas for this department. Items marked SOURCE REQUIRED need further
                    published detail before programme claims can be shown.
                </p>
            </header>
            <div class="adv-grid">
                <article class="adv-card">
                    <i class="fas fa-landmark" aria-hidden="true"></i>
                    <h3>Overview</h3>
                    <p>Core foundation of ECA — creating a level playing field and fostering a conducive business environment.</p>
                </article>
                <article class="adv-card">
                    <i class="fas fa-balance-scale" aria-hidden="true"></i>
                    <h3>Legislation Review</h3>
                    <p>Reviewing legislation affecting members, including examples cited in ECA source material such as the CIC Act and related industry measures.</p>
                </article>
                <article class="adv-card">
                    <i class="fas fa-building" aria-hidden="true"></i>
                    <h3>Regulatory Environment</h3>
                    <p>Engaging on the regulatory environment that shapes how contractors operate.</p>
                </article>
                <article class="adv-card">
                    <i class="fas fa-file-alt" aria-hidden="true"></i>
                    <h3>Industry Policy</h3>
                    <p>Industry policy engagement, including Construction Industry Policy themes cited in ECA background material.</p>
                </article>
                <article class="adv-card">
                    <i class="fas fa-gavel" aria-hidden="true"></i>
                    <h3>Fair Procurement</h3>
                    <p>Advocating for fair procurement and meaningful opportunities for legitimate contractors.</p>
                </article>
                <article class="adv-card">
                    <i class="fas fa-briefcase" aria-hidden="true"></i>
                    <h3>Business Environment</h3>
                    <p>Safeguarding a conducive business environment for contractors.</p>
                </article>
                <article class="adv-card">
                    <i class="fas fa-comments" aria-hidden="true"></i>
                    <h3>Policymaker Engagement</h3>
                    <p>Engaging policymakers on issues that affect the association and its members.</p>
                </article>
                <article class="adv-card">
                    <i class="fas fa-chart-line" aria-hidden="true"></i>
                    <h3>Intelligence-informed advocacy</h3>
                    <p>Digital Systems &amp; Data Intelligence feeds market and procurement insights that can sharpen advocacy arguments.</p>
                </article>
            </div>
            <p class="adv-lead" style="margin-top:14px;">
                <strong>Related pages:</strong>
                <a href="/about-structure.php">Organizational structure</a> ·
                <a href="/digital-intelligence.php">Digital Systems &amp; Data Intelligence</a> ·
                <a href="/education-policy.php">Education policy content</a> ·
                <a href="/contact.php?subject=<?= rawurlencode('Raise an Industry Issue') ?>">Raise an industry issue</a>
            </p>
            <p class="adv-lead" style="margin-top:8px;font-size:0.92rem;color:#667085;">
                Current campaigns, meeting records and impact statistics remain <strong>SOURCE REQUIRED</strong> until formally published.
            </p>
        </section>

        <section class="adv-panel" aria-labelledby="advWhyHeading">
            <header class="adv-panel-head">
                <p class="adv-kicker">Why it matters</p>
                <h2 id="advWhyHeading">Why Does Advocacy Matter?</h2>
                <p class="adv-lead">
                    Contractors operate within an environment shaped by external forces. Alone, one firm may struggle
                    to influence those conditions. Together, the industry has a stronger voice.
                </p>
            </header>
            <ul class="adv-list">
                <li>Government policies</li>
                <li>Procurement systems and tender practice</li>
                <li>Regulations and compliance requirements</li>
                <li>Payment practices and cash-flow risk</li>
                <li>Industry standards and professional expectations</li>
                <li>Skills and workforce development</li>
                <li>Broader economic conditions</li>
            </ul>
            <p class="adv-strong">Together, the industry has a stronger voice.</p>
            <p class="adv-lead" style="margin-top:10px;">
                ECA provides collective representation so contractor concerns can be gathered, clarified and raised
                with the people and institutions that shape the sector.
            </p>
        </section>

        <section class="adv-panel" aria-labelledby="advHowHeading">
            <header class="adv-panel-head">
                <p class="adv-kicker">How we work</p>
                <h2 id="advHowHeading">How ECA Advocates</h2>
                <p class="adv-lead">
                    This is the Advocacy department workflow for handling industry issues. It is related to — but distinct from —
                    the whole-of-association operating model on the organizational structure page
                    (<a href="/about-structure.php">LISTEN → COLLECT → ANALYSE → ADVOCATE → SUPPORT → DEVELOP → IMPACT</a>).
                </p>
            </header>
            <div class="adv-process">
                <?php
                $steps = [
                    ['01', 'LISTEN', 'We listen to contractors.'],
                    ['02', 'UNDERSTAND', 'We gather information and understand the issue.'],
                    ['03', 'REPRESENT', 'We develop the industry\'s position.'],
                    ['04', 'ENGAGE', 'We engage relevant stakeholders.'],
                    ['05', 'ADVOCATE', 'We promote practical solutions.'],
                    ['06', 'FOLLOW UP', 'We monitor progress.'],
                    ['07', 'IMPACT', 'We communicate outcomes.'],
                ];
                foreach ($steps as $i => $step):
                ?>
                    <article class="adv-step">
                        <span class="adv-step-num" aria-hidden="true"><?= htmlspecialchars($step[0], ENT_QUOTES, 'UTF-8') ?></span>
                        <div>
                            <h3><?= htmlspecialchars($step[1], ENT_QUOTES, 'UTF-8') ?></h3>
                            <p><?= htmlspecialchars($step[2], ENT_QUOTES, 'UTF-8') ?></p>
                        </div>
                    </article>
                    <?php if ($i < count($steps) - 1): ?>
                        <div class="adv-step-arrow" aria-hidden="true">↓</div>
                    <?php endif; ?>
                <?php endforeach; ?>
            </div>
        </section>

        <section class="adv-panel" id="adv-priorities" aria-labelledby="advPrioritiesHeading">
            <header class="adv-panel-head">
                <p class="adv-kicker">Priorities</p>
                <h2 id="advPrioritiesHeading">Our Advocacy Priorities</h2>
                <p class="adv-lead">
                    These are advocacy focus areas. They describe what ECA works toward — not claims of completed campaigns.
                </p>
            </header>
            <div class="adv-grid">
                <article class="adv-card">
                    <i class="fas fa-balance-scale" aria-hidden="true"></i>
                    <h3>Fair &amp; Transparent Procurement</h3>
                    <p>Promoting fair and meaningful opportunities for legitimate contractors.</p>
                </article>
                <article class="adv-card">
                    <i class="fas fa-clock" aria-hidden="true"></i>
                    <h3>Timely Contractor Payments</h3>
                    <p>Supporting fair and timely payment practices across the industry.</p>
                </article>
                <article class="adv-card">
                    <i class="fas fa-hard-hat" aria-hidden="true"></i>
                    <h3>Local Contractor Development</h3>
                    <p>Supporting an environment where local contractors can grow and compete.</p>
                </article>
                <article class="adv-card">
                    <i class="fas fa-landmark" aria-hidden="true"></i>
                    <h3>Better Industry Regulation</h3>
                    <p>Providing contractor perspectives on policies and regulations.</p>
                </article>
                <article class="adv-card">
                    <i class="fas fa-graduation-cap" aria-hidden="true"></i>
                    <h3>Skills &amp; Professional Development</h3>
                    <p>Supporting a skilled and professional construction industry.</p>
                </article>
                <article class="adv-card">
                    <i class="fas fa-chart-line" aria-hidden="true"></i>
                    <h3>Industry Growth</h3>
                    <p>Supporting sustainable growth of the construction sector.</p>
                </article>
            </div>
        </section>

        <section class="adv-panel" aria-labelledby="advActionHeading">
            <header class="adv-panel-head">
                <p class="adv-kicker">In practice</p>
                <h2 id="advActionHeading">ECA IN ACTION</h2>
                <p class="adv-lead">
                    Advocacy can take many forms. The activities below describe the kinds of work ECA undertakes —
                    without inventing specific meetings, dates or unpublished outcomes.
                </p>
            </header>
            <div class="adv-action-list">
                <div class="adv-action-item"><i class="fas fa-check" aria-hidden="true"></i><span>Government engagement</span></div>
                <div class="adv-action-item"><i class="fas fa-check" aria-hidden="true"></i><span>Stakeholder meetings</span></div>
                <div class="adv-action-item"><i class="fas fa-check" aria-hidden="true"></i><span>Policy consultations</span></div>
                <div class="adv-action-item"><i class="fas fa-check" aria-hidden="true"></i><span>Industry dialogue</span></div>
                <div class="adv-action-item"><i class="fas fa-check" aria-hidden="true"></i><span>Member representation</span></div>
                <div class="adv-action-item"><i class="fas fa-check" aria-hidden="true"></i><span>Industry research</span></div>
                <div class="adv-action-item"><i class="fas fa-check" aria-hidden="true"></i><span>Policy submissions</span></div>
            </div>
        </section>

        <section class="adv-panel" id="adv-updates" aria-labelledby="advCurrentHeading">
            <header class="adv-panel-head">
                <p class="adv-kicker">Verified updates</p>
                <h2 id="advCurrentHeading">Advocacy Updates</h2>
                <p class="adv-lead">
                    Published items appear only when ECA Communications marks news with an Advocacy category.
                    Local demo articles are excluded. No unverified campaigns are shown.
                </p>
            </header>
            <?php eca_render_advocacy_updates($advocacyUpdates); ?>
            <div class="adv-hero-actions" style="margin-top:16px;">
                <a class="adv-btn-ghost" href="/advocacy-updates.php">View advocacy updates page</a>
                <a class="adv-btn-ghost" href="/news.php">All news</a>
            </div>
        </section>

        <section class="adv-panel adv-voice" aria-labelledby="advVoiceHeading">
            <header class="adv-panel-head">
                <p class="adv-kicker">Participate</p>
                <h2 id="advVoiceHeading">YOUR VOICE MATTERS</h2>
                <p class="adv-lead">What issue is affecting your business or the construction industry?</p>
                <div class="adv-hero-actions">
                    <a class="adv-btn" href="<?= htmlspecialchars($issueHref, ENT_QUOTES, 'UTF-8') ?>">Raise an Industry Issue</a>
                    <a class="adv-btn-ghost" href="/contact.php">Contact ECA</a>
                </div>
            </header>
        </section>

        <section class="adv-panel" aria-labelledby="advImpactHeading">
            <header class="adv-panel-head">
                <p class="adv-kicker">Accountability</p>
                <h2 id="advImpactHeading">Our Advocacy Impact</h2>
                <p class="adv-lead">
                    Future metrics may include industry issues raised, stakeholder engagements, policy submissions,
                    advocacy initiatives and issues resolved — when verified figures are available.
                </p>
            </header>
            <div class="adv-empty org-source-required" role="status">
                <strong>SOURCE REQUIRED — Advocacy impact.</strong>
                Impact statistics will be published only after ECA provides verified figures (issues raised, engagements, submissions, outcomes).
                No unverified numbers are shown on this page.
            </div>
        </section>
    </div>
</div>
<?php eca_public_page_end(); ?>
