<?php
require_once __DIR__ . '/includes/http.php';
require_once __DIR__ . '/includes/public-page.php';
require_once __DIR__ . '/includes/education.php';

$conn = eca_education_db();
$slug = trim((string) ($_GET['slug'] ?? ''));
$row = ($conn && $slug !== '' && eca_education_ready($conn)) ? eca_education_programme_by_slug($conn, $slug) : null;
if (!$row || ($row['status'] ?? '') !== 'PUBLISHED') {
    eca_not_found('That development programme was not found.');
}

eca_public_page_start((string) $row['title'], 'Contractor Development', (string) $row['title'], (string) ($row['summary'] ?? ''), 'Membership development / Development');
?>
<div class="edu-wrap">
    <?php eca_education_subnav('development'); ?>
    <div class="edu-article">
        <p><?= nl2br(eca_education_h((string) ($row['body'] ?: $row['summary']))) ?></p>
        <div class="edu-actions">
            <a class="edu-btn" href="/education-training.php">Related training</a>
            <a class="edu-btn-ghost" href="/education-development.php">All programmes</a>
        </div>
    </div>
</div>
<?php eca_public_page_end(); ?>
