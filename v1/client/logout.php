<?php
require_once __DIR__ . '/auth.php';
if (function_exists('eca_leave_other_portal') && eca_leave_other_portal()) {
    header('Location: /admin/index.php');
    exit;
}
eca_logout();
header('Location: /client/');
exit;
