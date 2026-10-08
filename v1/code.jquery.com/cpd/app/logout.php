<?php
require_once dirname(__DIR__) . '/config.php';
$hubOwned = (function_exists('eca_hub_can_open_portals') && eca_hub_can_open_portals())
    || (function_exists('eca_super_admin_can_open_portals') && eca_super_admin_can_open_portals() && !empty($_SESSION['eca_admin']));
if ($hubOwned && function_exists('eca_leave_other_portal') && eca_leave_other_portal()) {
    header('Location: /admin/index.php');
    exit;
}
eca_destroy_auth_session();
header('Location: /cpd/login.php');
exit;
