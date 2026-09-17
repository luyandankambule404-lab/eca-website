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
    width: calc(100% - 48px);
    max-width: 1132px;
    margin: 0 auto 32px;
    padding: 36px 44px;
    border-radius: 10px;
    background: linear-gradient(90deg, rgba(25, 39, 84, 0.96) 0%, rgba(42, 63, 115, 0.92) 100%);
    box-shadow: 0 18px 40px rgba(25, 39, 84, 0.16);
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
    color: #fff;
    font-size: clamp(1.8rem, 3.4vw, 2.6rem);
    font-weight: 800;
    letter-spacing: -0.03em;
}
.eca-inner-hero p:not(.eca-kicker) {
    margin: 0;
    max-width: 640px;
    color: rgba(233, 238, 248, 0.88);
    line-height: 1.7;
}
.eca-inner-crumb {
    color: rgba(255, 255, 255, 0.82);
    font-size: 13px;
    font-weight: 700;
}
.eca-inner-crumb a { color: rgba(255, 255, 255, 0.88); text-decoration: none; }
.eca-inner-crumb a:hover { color: #fff; }
.eca-inner-crumb span { margin: 0 8px; }
</style>
