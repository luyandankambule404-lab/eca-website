<?php
$pageKicker = $pageKicker ?? 'ECA';
$pageTitle  = $pageTitle ?? 'Page';
$pageIntro  = $pageIntro ?? '';
$pageCrumb  = $pageCrumb ?? $pageTitle;
?>
<section class="eca-inner-hero">
    <div class="container eca-inner-hero-inner">
        <div>
            <p class="eca-kicker"><?= htmlspecialchars($pageKicker, ENT_QUOTES, 'UTF-8') ?></p>
            <h1><?= htmlspecialchars($pageTitle, ENT_QUOTES, 'UTF-8') ?></h1>
            <?php if ($pageIntro !== ''): ?>
                <p><?= htmlspecialchars($pageIntro, ENT_QUOTES, 'UTF-8') ?></p>
            <?php endif; ?>
        </div>
        <nav class="eca-inner-crumb" aria-label="breadcrumb">
            <a href="index.php">Home</a>
            <span>/</span>
            <?= htmlspecialchars($pageCrumb, ENT_QUOTES, 'UTF-8') ?>
        </nav>
    </div>
</section>
<style>
.eca-inner-hero {
    padding: 48px 0 28px;
    background:
        radial-gradient(circle at 88% 18%, rgba(217, 9, 32, 0.05), transparent 22%),
        radial-gradient(circle at 8% 80%, rgba(0, 0, 102, 0.05), transparent 24%),
        #f4f6f9;
}
.eca-inner-hero-inner {
    display: flex;
    flex-wrap: wrap;
    align-items: flex-end;
    justify-content: space-between;
    gap: 18px;
}
.eca-inner-hero h1 {
    margin: 0 0 8px;
    color: #000066;
    font-size: clamp(1.8rem, 3.4vw, 2.6rem);
    font-weight: 800;
    letter-spacing: -0.03em;
}
.eca-inner-hero p:not(.eca-kicker) {
    margin: 0;
    max-width: 640px;
    color: #667085;
    line-height: 1.7;
}
.eca-inner-crumb {
    color: #667085;
    font-size: 13px;
    font-weight: 700;
}
.eca-inner-crumb a { color: #000066; text-decoration: none; }
.eca-inner-crumb a:hover { color: #d90920; }
.eca-inner-crumb span { margin: 0 8px; }
</style>
