<?php

require_once __DIR__ . '/../../includes/portal-db.php';

$portalConfig = eca_portal_config_for_reads() ?: eca_local_portal_config();
$DB_HOST = $portalConfig['host'];
$DB_USER = $portalConfig['user'];
$DB_PASS = $portalConfig['pass'];
$DB_NAME = $portalConfig['name'];

$conn = eca_portal_mysqli(php_sapi_name() !== 'cli-server');

require_once dirname(__DIR__, 2) . '/includes/session.php';
require_once dirname(__DIR__, 2) . '/includes/authz.php';
