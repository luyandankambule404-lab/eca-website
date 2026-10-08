<?php
require_once __DIR__ . '/../../includes/session.php';
$next = (string) ($_GET['next'] ?? '');
header('Location: ' . eca_login_url('officer', $next));
exit;
