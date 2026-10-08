<?php
$pageKicker = $pageKicker ?? 'ECA';
$pageTitle  = $pageTitle ?? 'Page';
$pageIntro  = $pageIntro ?? '';
$pageCrumb  = $pageCrumb ?? $pageTitle;
?>
<?php
$pageTitleLines = $pageTitleLines ?? [];
$heroExtraClass = $pageTitleLines ? ' eca-inner-hero--stacked' : '';
?>
<section class="eca-inner-hero<?= $heroExtraClass ?>"<?= !empty($pageHeroInsideMain) ? '' : ' id="main-content" tabindex="-1"' ?>>
    <div class="container eca-inner-hero-inner">
        <div>
            <p class="eca-kicker"><?= htmlspecialchars($pageKicker, ENT_QUOTES, 'UTF-8') ?></p>
            <?php if ($pageTitleLines): ?>
                <h1><?php foreach ($pageTitleLines as $i => $line): ?><?= $i ? '<br>' : '' ?><?= htmlspecialchars((string) $line, ENT_QUOTES, 'UTF-8') ?><?php endforeach; ?></h1>
            <?php else: ?>
                <h1><?= htmlspecialchars($pageTitle, ENT_QUOTES, 'UTF-8') ?></h1>
            <?php endif; ?>
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
