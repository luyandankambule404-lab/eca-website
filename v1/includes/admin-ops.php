<?php

require_once __DIR__ . '/audit.php';
require_once __DIR__ . '/authz.php';

function eca_application_status_permission(string $status): string
{
    $status = strtoupper(trim($status));
    if ($status === 'APPROVED') {
        return 'applications.approve';
    }
    if ($status === 'REJECTED') {
        return 'applications.reject';
    }
    return 'applications.review';
}

function eca_application_status_audit_action(string $status): string
{
    $status = strtoupper(trim($status));
    return [
        'APPROVED' => 'application.approved',
        'REJECTED' => 'application.rejected',
        'ADDITIONAL INFORMATION REQUIRED' => 'application.returned',
        'UNDER REVIEW' => 'application.reviewed',
        'SUBMITTED' => 'application.status',
    ][$status] ?? 'application.status';
}

function eca_hub_audit_for(string $entityType, string $entityId, int $limit = 20, array $actions = []): array
{
    $conn = eca_audit_db();
    if (!$conn || $entityId === '') {
        return [];
    }
    $limit = max(1, min(50, $limit));
    try {
        $sql = 'SELECT actor_email, action, entity_type, entity_id, created_at
                FROM audit_logs
                WHERE entity_type = ? AND entity_id = ?';
        $params = [$entityType, $entityId];
        if ($actions) {
            $sql .= ' AND action IN (' . implode(',', array_fill(0, count($actions), '?')) . ')';
            foreach ($actions as $action) {
                $params[] = $action;
            }
        }
        $sql .= ' ORDER BY id DESC LIMIT ' . $limit;
        $stmt = $conn->prepare($sql);
        $stmt->execute($params);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
        return is_array($rows) ? $rows : [];
    } catch (Throwable $e) {
        return [];
    }
}

function eca_match_members_for_company(?PDO $portal, array $company): array
{
    if (!$portal) {
        return [];
    }
    $email = strtolower(trim((string) ($company['email'] ?? '')));
    $name = strtolower(trim((string) ($company['name'] ?? '')));
    $reg = strtolower(trim((string) ($company['registration_number'] ?? '')));
    if ($email === '' && $name === '' && $reg === '') {
        return [];
    }
    try {
        $where = [];
        $params = [];
        if ($reg !== '') {
            $where[] = 'LOWER(TRIM(MembershipNumber)) = ?';
            $params[] = $reg;
        }
        if ($email !== '') {
            $where[] = 'LOWER(TRIM(EmailAddress)) = ?';
            $params[] = $email;
        }
        if ($name !== '') {
            $where[] = 'LOWER(TRIM(TradingName)) = ? OR LOWER(TRIM(CompanyRegistrationName)) = ?';
            $params[] = $name;
            $params[] = $name;
        }
        $stmt = $portal->prepare(
            'SELECT client_id, MembershipNumber, TradingName, CompanyRegistrationName, EmailAddress, active, Status, application_status, application_reference, Region, Clasification
             FROM tbl_client
             WHERE ' . implode(' OR ', $where) . '
             ORDER BY
               CASE WHEN MembershipNumber IS NOT NULL AND TRIM(MembershipNumber) <> \'\' THEN 0 ELSE 1 END,
               TradingName ASC
             LIMIT 20'
        );
        $stmt->execute($params);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
        return is_array($rows) ? $rows : [];
    } catch (Throwable $e) {
        return [];
    }
}

function eca_member_cpd_snapshot(?PDO $portal, string $membership): array
{
    $empty = ['applications' => 0, 'pending' => 0, 'points' => 0.0];
    $membership = trim($membership);
    if (!$portal || $membership === '') {
        return $empty;
    }
    try {
        $stmt = $portal->prepare('SELECT COUNT(*) FROM cpd_applications WHERE membership_number = ?');
        $stmt->execute([$membership]);
        $empty['applications'] = (int) $stmt->fetchColumn();
        $stmt = $portal->prepare(
            "SELECT COUNT(*) FROM cpd_applications
             WHERE membership_number = ? AND (status IS NULL OR LOWER(status) IN ('pending','submitted','under review'))"
        );
        $stmt->execute([$membership]);
        $empty['pending'] = (int) $stmt->fetchColumn();
        $email = '';
        $emailStmt = $portal->prepare('SELECT EmailAddress FROM tbl_client WHERE MembershipNumber = ? LIMIT 1');
        $emailStmt->execute([$membership]);
        $email = trim((string) $emailStmt->fetchColumn());
        $userId = 0;
        if ($email !== '') {
            $userStmt = $portal->prepare('SELECT id FROM user WHERE LOWER(email) = LOWER(?) LIMIT 1');
            $userStmt->execute([$email]);
            $userId = (int) $userStmt->fetchColumn();
        }
        if ($userId > 0) {
            $pts = $portal->prepare('SELECT COALESCE(SUM(points), 0) FROM cpd_points_ledger WHERE user_id = ?');
            $pts->execute([$userId]);
            $empty['points'] = (float) $pts->fetchColumn();
        }
    } catch (Throwable $e) {
        return $empty;
    }
    return $empty;
}

function eca_member_wellness_snapshot(?PDO $local, string $membership, int $clientId): array
{
    $empty = ['registrations' => 0];
    if (!$local) {
        return $empty;
    }
    try {
        $where = [];
        $params = [];
        if ($membership !== '') {
            $where[] = 'membership_number = ?';
            $params[] = $membership;
        }
        if ($clientId > 0) {
            $where[] = 'client_id = ?';
            $params[] = $clientId;
        }
        if (!$where) {
            return $empty;
        }
        $stmt = $local->prepare(
            'SELECT COUNT(*) FROM wellness_event_registrations WHERE ' . implode(' OR ', $where)
        );
        $stmt->execute($params);
        $empty['registrations'] = (int) $stmt->fetchColumn();
    } catch (Throwable $e) {
        return $empty;
    }
    return $empty;
}

function eca_audit_content_status(string $entityType, string $entityId, string $status, bool $isPublished): void
{
    eca_audit($entityType . '.status', $entityType, $entityId, ['status' => $status]);
    eca_audit($isPublished ? 'content.published' : 'content.unpublished', $entityType, $entityId, ['status' => $status]);
}

function eca_admin_notice_dismiss_key(string $key): string
{
    return preg_replace('/[^a-z0-9._-]/i', '', $key);
}

function eca_admin_notice_is_dismissed(string $key): bool
{
    $key = eca_admin_notice_dismiss_key($key);
    $store = $_SESSION['eca_admin_dismissed_notices'] ?? [];
    return $key !== '' && !empty($store[$key]);
}

function eca_admin_notice_dismiss(string $key): void
{
    $key = eca_admin_notice_dismiss_key($key);
    if ($key === '') {
        return;
    }
    if (!isset($_SESSION['eca_admin_dismissed_notices']) || !is_array($_SESSION['eca_admin_dismissed_notices'])) {
        $_SESSION['eca_admin_dismissed_notices'] = [];
    }
    $_SESSION['eca_admin_dismissed_notices'][$key] = time();
}
