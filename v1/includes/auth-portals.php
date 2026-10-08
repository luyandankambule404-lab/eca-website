<?php
$authPortalSet = $authPortalSet ?? 'unified';
$sets = [
    'unified' => [
        ['Officer login', '/admin/login.php'],
        ['Member login', '/client/'],
        ['Learner login', '/learner-portal.php'],
    ],
    'officer' => [
        ['Member login', '/client/'],
        ['Learner login', '/learner-portal.php'],
    ],
    'admin' => [
        ['Member login', '/client/'],
        ['Learner login', '/learner-portal.php'],
    ],
    'member' => [
        ['Officer login', '/admin/login.php'],
        ['Learner login', '/learner-portal.php'],
    ],
    'learner' => [
        ['Officer login', '/admin/login.php'],
        ['Member login', '/client/'],
    ],
    'cpd' => [
        ['Officer login', '/admin/login.php'],
        ['Member login', '/client/'],
    ],
    'staff' => [
        ['Member login', '/client/'],
        ['Learner login', '/learner-portal.php'],
    ],
    'register' => [
        ['Learner login', '/learner-portal.php'],
        ['Member login', '/client/'],
    ],
];
$links = $sets[$authPortalSet] ?? $sets['unified'];
$h = static function (string $v): string {
    return htmlspecialchars($v, ENT_QUOTES, 'UTF-8');
};
?>
<nav class="eca-auth-portals" aria-label="Other sign-in options">
    <?php foreach ($links as [$label, $href]): ?>
        <a href="<?= $h($href) ?>"><?= $h($label) ?></a>
    <?php endforeach; ?>
</nav>
