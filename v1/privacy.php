<?php
require_once __DIR__ . '/includes/public-page.php';
require_once __DIR__ . '/includes/welcome-pack.php';

eca_public_page_start(
    'Data privacy',
    'Membership',
    'Data privacy',
    'How ECA uses personal and company information provided in membership applications and member records.',
    'Data privacy',
    null,
    true,
    'privacy.php'
);
?>
<div class="container-xxl py-4">
    <div class="container">
        <div class="eca-page-intro">
            <p class="eca-kicker">Welcome package</p>
            <h2>Data privacy</h2>
            <p>This notice reflects the data-protection undertaking in the membership declaration. It is not a substitute for the Data Protection Act.</p>
        </div>
        <div class="card border-0 shadow-sm">
            <div class="card-body p-4">
                <?= eca_privacy_notice_html() ?>
            </div>
        </div>
        <p class="mt-4"><a href="/about.php">About ECA</a> · <a href="/code-of-conduct.php">Code of Conduct</a></p>
    </div>
</div>
<?php eca_public_page_end();