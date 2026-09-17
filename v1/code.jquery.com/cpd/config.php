<?php

require_once __DIR__ . '/../../includes/portal-db.php';

$DB_HOST = eca_env('ECA_PORTAL_DB_HOST', eca_env('ECA_DB_HOST', '127.0.0.1'));
$DB_USER = eca_env('ECA_PORTAL_DB_USER', eca_env('ECA_DB_USER', 'root'));
$DB_PASS = eca_env('ECA_PORTAL_DB_PASS', eca_env('ECA_DB_PASS', ''));
$DB_NAME = eca_env('ECA_PORTAL_DB_NAME', eca_env('ECA_DB_NAME', 'eca_local'));

$conn = eca_portal_mysqli(php_sapi_name() !== 'cli-server');

require_once dirname(__DIR__, 2) . '/includes/session.php';
require_once dirname(__DIR__, 2) . '/includes/authz.php';
