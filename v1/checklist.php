<?php
require_once __DIR__ . '/includes/public-page.php';

eca_public_page_start(
    'Contractor Selection Checklist',
    'Choose with confidence',
    'ECA checklist',
    'Use this checklist when selecting an ECA-registered contractor.',
    'Checklist'
);
?>
<style>
.eca-checklist {
    color: #192754;
}

.eca-checklist-head {
    margin: 0 0 22px;
    padding: 0 0 16px;
    border-bottom: 1px solid #e4e8ef;
}

.eca-checklist-head .eca-checklist-motto {
    margin: 0 0 8px;
    color: #5a6680;
    font-size: .95rem;
    font-style: italic;
}

.eca-checklist-head h2 {
    margin: 0 0 8px;
    color: #192754 !important;
    font-size: 1.35rem;
    font-weight: 800;
    letter-spacing: -.02em;
    text-transform: none;
}

.eca-checklist-head p {
    margin: 0;
    color: #5a6680;
    line-height: 1.6;
}

.eca-checklist-section {
    margin: 0 0 16px;
    padding: 16px 18px;
    border-left: 4px solid #d50d0e;
    border-radius: 0 8px 8px 0;
    background: #f7f9fc;
}

.eca-checklist-section:last-child {
    margin-bottom: 0;
}

.eca-checklist-section h3 {
    margin: 0 0 10px;
    color: #192754 !important;
    font-size: 1.02rem;
    font-weight: 800;
    letter-spacing: .02em;
    text-transform: uppercase;
}

.eca-checklist-list {
    list-style: none;
    margin: 0;
    padding: 0;
}

.eca-checklist-list li {
    display: flex;
    align-items: flex-start;
    gap: 12px;
    padding: 8px 0;
    border-bottom: 1px solid #e4e8ef;
    color: #192754 !important;
    line-height: 1.55;
}

.eca-checklist-list li:last-child {
    border-bottom: 0;
    padding-bottom: 0;
}

.eca-checklist-list li::before {
    content: "✓";
    flex: 0 0 auto;
    margin-top: 1px;
    color: #d50d0e;
    font-weight: 800;
}

.eca-checklist-list a {
    color: #192754;
    font-weight: 700;
    text-decoration: underline;
    text-underline-offset: 2px;
}

.eca-checklist-list a:hover {
    color: #d50d0e;
}
</style>
<div class="container-xxl py-4">
    <div class="row justify-content-center">
        <div class="col-lg-8">
            <article class="eca-form-panel eca-checklist">
                <header class="eca-checklist-head">
                    <p class="eca-checklist-motto">Working together for the construction industry</p>
                    <h2>Contractor Selection Checklist (ECA Members)</h2>
                    <p>Ensure the following are confirmed before signing a contract:</p>
                </header>

                <section class="eca-checklist-section">
                    <h3>1. Membership &amp; Credentials</h3>
                    <ul class="eca-checklist-list">
                        <li>
                            Verify company details and any complaints in the
                            <a href="/directory.php">ECA Membership Directory</a>
                        </li>
                        <li>Confirm a valid ECA membership certificate</li>
                        <li>Check that the contractor holds a valid license</li>
                    </ul>
                </section>

                <section class="eca-checklist-section">
                    <h3>2. Comparison &amp; References</h3>
                    <ul class="eca-checklist-list">
                        <li>Request and compare estimates from several ECA contractors</li>
                        <li>Obtain references from past clients</li>
                    </ul>
                </section>

                <section class="eca-checklist-section">
                    <h3>3. Scope &amp; Deliverables</h3>
                    <ul class="eca-checklist-list">
                        <li>Ensure the scope of work is clearly defined</li>
                        <li>Review a detailed list of materials to be provided</li>
                        <li>Confirm cost requirements at the time of signing</li>
                    </ul>
                </section>

                <section class="eca-checklist-section">
                    <h3>4. Financial Protections</h3>
                    <ul class="eca-checklist-list">
                        <li>Include contract cancellation and refund clauses</li>
                        <li>Ensure payment terms are clearly stated</li>
                        <li>Outline the process for confirming work completion</li>
                    </ul>
                </section>

                <section class="eca-checklist-section">
                    <h3>5. Training &amp; Compliance</h3>
                    <ul class="eca-checklist-list">
                        <li>Verify evidence of attendance at Association organized trainings</li>
                        <li>Confirm agreement to abide by the ECA Code of Conduct</li>
                    </ul>
                </section>
            </article>
        </div>
    </div>
</div>
<?php eca_public_page_end(); ?>
