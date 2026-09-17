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
