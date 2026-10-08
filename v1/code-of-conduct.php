<?php
require_once __DIR__ . '/includes/public-page.php';
require_once __DIR__ . '/includes/welcome-pack.php';

eca_public_page_start(
    'Code of Conduct',
    'Membership',
    'Code of Conduct',
    'The standards ECA members are expected to follow in their work and in their dealings with clients, workers and the association.',
    'Code of Conduct',
    null,
    true,
    'code-of-conduct.php'
);
?>
<div class="container-xxl py-4">
    <div class="container">
        <div class="eca-page-intro">
            <p class="eca-kicker">Welcome package</p>
            <h2>ECA Code of Conduct for contractors</h2>
            <p>This is the same Code of Conduct members sign when they apply. It remains part of membership after approval.</p>
        </div>
        <div class="card border-0 shadow-sm">
            <div class="card-body p-4">
                <?= eca_code_of_conduct_html() ?>
            </div>
        </div>
        <p class="mt-4"><a href="/about-by-laws.php">Constitution and bylaws</a> · <a href="/privacy.php">Data privacy</a></p>
    </div>
</div>
<?php eca_public_page_end();