<?php
require_once __DIR__ . '/../auth.php';
require_once __DIR__ . '/../helpers.php';
require_once __DIR__ . '/../config.php';

require_role(['SUPPERADMIN', 'OFFICER']);

define('CPD_STATUS_ALLOWED_ROLES', ['SUPPERADMIN', 'OFFICER']);
require __DIR__ . '/../admin/update_status.php';
