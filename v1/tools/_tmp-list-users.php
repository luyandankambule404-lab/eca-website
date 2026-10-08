<?php
$pdo = new PDO('mysql:host=127.0.0.1;dbname=eca_local', 'root', '');
$rows = $pdo->query('SELECT id, email, role FROM users ORDER BY id ASC LIMIT 40')->fetchAll(PDO::FETCH_ASSOC);
foreach ($rows as $r) {
    echo $r['id'] . "\t" . $r['email'] . "\t" . $r['role'] . PHP_EOL;
}
