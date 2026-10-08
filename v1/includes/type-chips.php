<?php
$chipBase = $chipBase ?? (parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/');
$search = $search ?? '';
$industry = $industry ?? '';
$typeCounts = $typeCounts ?? [];
$allCount = $allCount ?? 0;
$types = eca_directory_industries();
?>
<nav class="type-chips" aria-label="Filter by type">
    <a class="<?= $industry === '' ? 'is-active' : '' ?>" href="<?= eca_h(eca_type_filter_url($chipBase, '')) ?>">All <span><?= (int) $allCount ?></span></a>
    <?php foreach ($types as $opt): ?>
        <a class="<?= strcasecmp($industry, $opt) === 0 ? 'is-active' : '' ?>" href="<?= eca_h(eca_type_filter_url($chipBase, '', $opt)) ?>"><?= eca_h($opt) ?> <span><?= (int) ($typeCounts[$opt] ?? 0) ?></span></a>
    <?php endforeach; ?>
</nav>
