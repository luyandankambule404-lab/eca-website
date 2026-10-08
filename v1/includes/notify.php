<?php

require_once __DIR__ . '/portal-db.php';

function eca_notify(array $payload): void
{
    $conn = eca_portal_pdo(false);
    if (!$conn) {
        return;
    }
    $title = trim((string) ($payload['title'] ?? ''));
    $message = trim((string) ($payload['message'] ?? ''));
    if ($title === '' || $message === '') {
        return;
    }
    $userId = (int) ($payload['user_id'] ?? 0);
    $type = strtoupper((string) ($payload['type'] ?? 'ANNOUNCEMENT'));
    $link = (string) ($payload['link'] ?? '');
    $membership = trim((string) ($payload['membership_number'] ?? ''));
    $clientId = (int) ($payload['client_id'] ?? 0);
    try {
        $cols = $conn->query('SHOW COLUMNS FROM notifications')->fetchAll(PDO::FETCH_ASSOC);
        $have = [];
        foreach ($cols as $col) {
            $have[strtolower((string) $col['Field'])] = true;
        }
        $fields = ['user_id', 'title', 'message', 'type', 'link', 'is_read'];
        $values = [$userId, $title, $message, $type, $link !== '' ? $link : null, 0];
        if (!empty($have['membership_number'])) {
            $fields[] = 'membership_number';
            $values[] = $membership !== '' ? $membership : null;
        }
        if (!empty($have['client_id'])) {
            $fields[] = 'client_id';
            $values[] = $clientId > 0 ? $clientId : null;
        }
        $placeholders = implode(',', array_fill(0, count($fields), '?'));
        $sql = 'INSERT INTO notifications (' . implode(',', $fields) . ') VALUES (' . $placeholders . ')';
        $conn->prepare($sql)->execute($values);
    } catch (Throwable $e) {
        error_log('Notification write failed: ' . $e->getMessage());
    }
}

function eca_member_notifications(PDO $conn, array $member, int $limit = 8): array
{
    $membership = trim((string) ($member['membership'] ?? ''));
    $clientId = (int) ($member['client_id'] ?? 0);
    try {
        $sql = 'SELECT * FROM notifications WHERE 1=1';
        $params = [];
        $ors = [];
        if ($membership !== '') {
            $ors[] = 'membership_number = ?';
            $params[] = $membership;
        }
        if ($clientId > 0) {
            $ors[] = 'client_id = ?';
            $params[] = $clientId;
        }
        if (!$ors) {
            return [];
        }
        $sql .= ' AND (' . implode(' OR ', $ors) . ') ORDER BY id DESC LIMIT ' . max(1, $limit);
        $stmt = $conn->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (Throwable $e) {
        return [];
    }
}

function eca_member_notification_scope(array $member): array
{
    $ors = [];
    $params = [];
    $membership = trim((string) ($member['membership'] ?? ''));
    $clientId = (int) ($member['client_id'] ?? 0);
    if ($membership !== '') {
        $ors[] = 'membership_number = ?';
        $params[] = $membership;
    }
    if ($clientId > 0) {
        $ors[] = 'client_id = ?';
        $params[] = $clientId;
    }
    return [$ors, $params];
}

function eca_mark_member_notifications_read(PDO $conn, array $member, ?int $id = null): int
{
    [$ors, $params] = eca_member_notification_scope($member);
    if (!$ors) {
        return 0;
    }
    try {
        $sql = 'UPDATE notifications SET is_read = 1 WHERE (' . implode(' OR ', $ors) . ')';
        if ($id !== null && $id > 0) {
            $sql .= ' AND id = ?';
            $params[] = $id;
        }
        $stmt = $conn->prepare($sql);
        $stmt->execute($params);
        return $stmt->rowCount();
    } catch (Throwable $e) {
        return 0;
    }
}
