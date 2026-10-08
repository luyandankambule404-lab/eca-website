<?php
require_once dirname(__DIR__) . '/v1/includes/env.php';

$hub = new PDO(
    'mysql:host=' . eca_env('ECA_DB_HOST', '127.0.0.1') . ';dbname=' . eca_env('ECA_DB_NAME', 'eca_local') . ';charset=utf8mb4',
    eca_env('ECA_DB_USER', 'root'),
    eca_env('ECA_DB_PASS', ''),
    [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
);
$portal = new PDO(
    'mysql:host=' . eca_env('ECA_PORTAL_DB_HOST', '127.0.0.1') . ';dbname=' . eca_env('ECA_PORTAL_DB_NAME', 'eca_portal_local') . ';charset=utf8mb4',
    eca_env('ECA_PORTAL_DB_USER', 'root'),
    eca_env('ECA_PORTAL_DB_PASS', ''),
    [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
);

$checks = [
    'hub.users' => $hub->query('SELECT COUNT(*) FROM users')->fetchColumn(),
    'hub.companies' => $hub->query('SELECT COUNT(*) FROM companies')->fetchColumn(),
    'hub.news' => $hub->query('SELECT COUNT(*) FROM news')->fetchColumn(),
    'portal.tbl_client' => $portal->query('SELECT COUNT(*) FROM tbl_client')->fetchColumn(),
    'portal.userss' => $portal->query('SELECT COUNT(*) FROM userss')->fetchColumn(),
    'portal.user' => $portal->query('SELECT COUNT(*) FROM `user`')->fetchColumn(),
    'portal.courses' => $portal->query('SELECT COUNT(*) FROM courses')->fetchColumn(),
    'admin@eca.co.sz' => $hub->query("SELECT COUNT(*) FROM users WHERE email = 'admin@eca.co.sz'")->fetchColumn(),
    'member@eca.co.sz' => $portal->query("SELECT COUNT(*) FROM userss WHERE email = 'member@eca.co.sz'")->fetchColumn(),
    'contractor@eca.co.sz' => $portal->query("SELECT COUNT(*) FROM `user` WHERE email = 'contractor@eca.co.sz'")->fetchColumn(),
];
foreach ($checks as $label => $value) {
    echo $label . '=' . $value . PHP_EOL;
}
