<?php

function eca_responsive_assets(): void
{
    static $done = false;
    if ($done) {
        return;
    }
    $done = true;
    echo '<link rel="stylesheet" href="/css/responsive.css?v=6">' . "\n";
    echo '<link rel="stylesheet" href="/css/eca-forms.css?v=20260928-cpd">' . "\n";
    echo '<script src="/js/hub-table-pager.js?v=20260924-6" defer></script>' . "\n";
}
