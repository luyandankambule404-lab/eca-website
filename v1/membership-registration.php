<?php
require_once __DIR__ . '/includes/public-page.php';

eca_public_page_start(
    'Membership Registration',
    'Membership',
    'Membership Registration',
    'Choose one option to continue. You can apply for company membership, submit an artisan application, or renew an existing membership.',
    'Membership Registration'
);
?>
<div class="container-xxl py-4">
    <div class="eca-register-panel" role="navigation" aria-label="Membership registration options">
        <a href="/application.php">
            <i class="bi bi-file-earmark-text" aria-hidden="true"></i>
            <span>Membership application</span>
        </a>
        <a href="/apply_artisan.php">
            <i class="bi bi-tools" aria-hidden="true"></i>
            <span>Artisan application</span>
        </a>
        <a href="/renewal.php">
            <i class="bi bi-arrow-repeat" aria-hidden="true"></i>
            <span>Membership renewal</span>
        </a>
    </div>
</div>
<?php eca_public_page_end(); ?>
