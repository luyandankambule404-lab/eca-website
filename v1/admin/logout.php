<?php
require_once __DIR__ . '/auth.php';
eca_admin_logout();
header('Location: /admin/login.php');
exit;
