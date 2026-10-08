<?php

require_once __DIR__ . '/session.php';

function eca_audit_db(): ?PDO
{
    static $conn = false;
    if ($conn !== false) {
        return $conn;
    }
    if (!class_exists('Database')) {
        $config = dirname(__DIR__) . DIRECTORY_SEPARATOR . 'config.php';
        if (is_file($config)) {
            require_once $config;
        }
    }
    if (!class_exists('Database')) {
        $conn = null;
        return null;
    }
    try {
        $db = new Database();
        $pdo = $db->getConnection(false);
        $conn = $pdo instanceof PDO ? $pdo : null;
    } catch (Throwable $e) {
        $conn = null;
    }
    return $conn;
}

function eca_hub_known_audit_actions(): array
{
    return [
        'admin.login',
        'admin.logout',
        'admin.login.failed',
        'access.denied',
        'csrf.rejected',
        'privilege.escalation.denied',
        'application.note',
        'application.status',
        'application.approved',
        'application.rejected',
        'application.returned',
        'application.reviewed',
        'application.resubmitted',
        'document.request',
        'document.access.denied',
        'certificate.issued',
        'certificate.generate',
        'certificate.revoke',
        'certificate.download',
        'company.updated',
        'company.viewed',
        'content.published',
        'content.unpublished',
        'document.review',
        'document.download',
        'document.view',
        'member.approve',
        'member.hub_login_created',
        'member.suspend',
        'member.reactivate',
        'member.note',
        'member.email_notice',
        'payment.verified',
        'payment.rejected',
        'payment.pending',
        'user.created',
        'user.promoted',
        'user.demoted',
        'user.activated',
        'user.deactivated',
        'user.roles',
        'user.edited',
        'user.password_reset',
        'permissions.changed',
        'settings.changed',
        'report.export',
        'report.view',
        'ticket.update',
    ];
}

function eca_hub_audit_filter_actions(?PDO $conn, bool $includeSecurityActions): array
{
    $fromDb = [];
    if ($conn) {
        try {
            $fromDb = $conn->query(
                "SELECT DISTINCT action FROM audit_logs
                 WHERE action IS NOT NULL AND action <> ''
                 ORDER BY action"
            )->fetchAll(PDO::FETCH_COLUMN);
            if (!is_array($fromDb)) {
                $fromDb = [];
            }
        } catch (Throwable $e) {
            $fromDb = [];
        }
    }

    $actions = [];
    foreach (array_merge(eca_hub_known_audit_actions(), $fromDb) as $action) {
        $action = strtolower(trim((string) $action));
        if ($action !== '') {
            $actions[$action] = $action;
        }
    }
    $actions = array_values($actions);
    sort($actions, SORT_STRING);

    if ($includeSecurityActions || !function_exists('eca_security_audit_actions')) {
        return $actions;
    }
    $blocked = eca_security_audit_actions();
    return array_values(array_filter(
        $actions,
        static fn (string $action): bool => !in_array($action, $blocked, true)
    ));
}

/**
 * Request path for security audit meta (never includes query secrets).
 */
function eca_audit_request_path(): string
{
    $uri = (string) ($_SERVER['REQUEST_URI'] ?? '');
    $path = (string) (parse_url($uri, PHP_URL_PATH) ?? '');
    if ($path === '') {
        $path = (string) ($_SERVER['SCRIPT_NAME'] ?? '');
    }
    return substr($path, 0, 255);
}

/**
 * Write a security-oriented audit row with safe request context.
 * Does not accept or log tokens, passwords, or cookies.
 */
function eca_audit_security_event(
    string $action,
    string $entityType = '',
    string $entityId = '',
    array $meta = []
): void {
    $path = eca_audit_request_path();
    $meta = array_merge([
        'path' => $path,
        'method' => strtoupper((string) ($_SERVER['REQUEST_METHOD'] ?? '')),
        'result' => 'denied',
    ], $meta);
    if ($entityType === '' && $path !== '') {
        $entityType = 'route';
        $entityId = $path;
    }
    eca_audit($action, $entityType, $entityId, $meta);
}

function eca_audit(string $action, string $entityType = '', string $entityId = '', array $meta = []): void
{
    $conn = eca_audit_db();
    if (!$conn) {
        return;
    }

    $admin = $_SESSION['eca_admin'] ?? null;
    $member = $_SESSION['eca_member'] ?? null;
    $actorType = 'system';
    $actorId = '';
    $actorEmail = '';
    if (is_array($admin)) {
        $actorType = 'admin';
        $actorId = (string) ($admin['id'] ?? '');
        $actorEmail = (string) ($admin['email'] ?? '');
    } elseif (is_array($member)) {
        $actorType = 'member';
        $actorId = (string) ($member['id'] ?? $member['membership'] ?? '');
        $actorEmail = (string) ($member['email'] ?? '');
    } elseif (!empty($_SESSION['user_id'])) {
        $actorType = 'cpd';
        $actorId = (string) $_SESSION['user_id'];
        $actorEmail = (string) ($_SESSION['email'] ?? '');
    }

    if (function_exists('eca_rbac_safe_meta')) {
        $meta = eca_rbac_safe_meta($meta);
    }

    $ip = (string) ($_SERVER['REMOTE_ADDR'] ?? '');
    $ua = substr((string) ($_SERVER['HTTP_USER_AGENT'] ?? ''), 0, 255);
    $metaJson = $meta ? json_encode($meta, JSON_UNESCAPED_UNICODE) : null;

    try {
        $stmt = $conn->prepare(
            'INSERT INTO audit_logs (actor_type, actor_id, actor_email, action, entity_type, entity_id, ip, user_agent, meta)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)'
        );
        $stmt->execute([
            $actorType,
            $actorId !== '' ? $actorId : null,
            $actorEmail !== '' ? $actorEmail : null,
            $action,
            $entityType !== '' ? $entityType : null,
            $entityId !== '' ? $entityId : null,
            $ip !== '' ? $ip : null,
            $ua !== '' ? $ua : null,
            $metaJson,
        ]);
    } catch (Throwable $e) {
        error_log('Audit log write failed: ' . $e->getMessage());
    }
}
