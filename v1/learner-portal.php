<?php
require_once __DIR__ . '/includes/session.php';
require_once __DIR__ . '/client/auth.php';
require_once __DIR__ . '/admin/auth.php';
require_once __DIR__ . '/code.jquery.com/cpd/auth.php';
eca_session_touch();
eca_auth_no_store();

cpd_sso_from_signed_in();

if (!empty($_SESSION['user_id'])) {
    header('Location: /cpd/contractor/dashboard.php');
    exit;
}

if (function_exists('eca_super_admin_can_open_portals') && eca_super_admin_can_open_portals()) {
    $user = cpd_sso_from_signed_in();
    if ($user) {
        header('Location: /cpd/contractor/dashboard.php');
        exit;
    }
}

if (!empty($_SESSION['eca_member'])) {
    header('Location: /client/dashboard.php');
    exit;
}

if (!empty($_SESSION['eca_admin'])) {
    header('Location: /admin/index.php');
    exit;
}

header('Location: /cpd/login.php');
exit;