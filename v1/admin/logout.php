<?php
require_once __DIR__ . '/auth.php';
$backToCpd = !empty(($_SESSION['eca_admin'] ?? [])['eca_cpd_hub_preview'])
    && strtoupper(trim((string) ($_SESSION['role'] ?? ''))) === 'SUPPERADMIN';
eca_admin_logout();
header('Location: ' . ($backToCpd ? '/cpd/admin/dashboard.php' : '/admin/login.php'));
exit;
