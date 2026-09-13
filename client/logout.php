<?php
require_once __DIR__ . '/auth.php';
eca_logout();
header('Location: index.php');
exit;
