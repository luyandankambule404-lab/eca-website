<?php
if (!isset($currentPage) || $currentPage === '') {
    $currentPage = basename($_SERVER['SCRIPT_NAME'] ?? $_SERVER['PHP_SELF'] ?? 'index.php');
}
?>
<div class="eca-landing eca-landing-inner eca-public" data-eca-landing>
    <?php require __DIR__ . '/public-header.php'; ?>
</div>
