<?php

require_once __DIR__ . '/membership.php';
require_once __DIR__ . '/audit.php';

function eca_track_normalize_reference(string $raw): string
{
    $ref = strtoupper(trim($raw));
    $ref = preg_replace('/\s+/', '', $ref) ?? '';
    return $ref;
}

function eca_track_reference_valid(string $ref): bool
{
    return (bool) preg_match('/^ECA-APP-(19|20)\d{2}-\d{4}$/', $ref);
}

function eca_track_status_normalize(string $status): string
{
    $status = strtoupper(trim($status));
    $aliases = [
        'UNDER_REVIEW' => 'UNDER REVIEW',
        'IN REVIEW' => 'UNDER REVIEW',
        'ADDITIONAL_INFORMATION_REQUIRED' => 'ADDITIONAL INFORMATION REQUIRED',
        'RETURNED' => 'ADDITIONAL INFORMATION REQUIRED',
        'DECLINED' => 'REJECTED',
    ];
    $status = $aliases[$status] ?? $status;
    return in_array($status, eca_application_statuses(), true) ? $status : '';
}

function eca_track_status_label(string $status): string
{
    return [
        'SUBMITTED' => 'Submitted',
        'UNDER REVIEW' => 'Under Review',
        'ADDITIONAL INFORMATION REQUIRED' => 'Additional Information Required',
        'APPROVED' => 'Approved',
        'REJECTED' => 'Rejected',
    ][$status] ?? 'Submitted';
}

function eca_track_date_long(string $value): string
{
    $value = trim($value);
    if ($value === '') {
        return '';
    }
    $ts = strtotime($value);
    return $ts === false ? '' : date('j F Y', $ts);
}

function eca_track_date_short(string $value): string
{
    $value = trim($value);
    if ($value === '') {
        return '';
    }
    $ts = strtotime($value);
    return $ts === false ? '' : date('j M Y', $ts);
}

function eca_track_date_iso(string $value): string
{
    $value = trim($value);
    if ($value === '') {
        return '';
    }
    $ts = strtotime($value);
    return $ts === false ? '' : date('Y-m-d', $ts);
}

function eca_track_lookup(?PDO $conn, string $ref): ?array
{
    if (!$conn || !eca_track_reference_valid($ref)) {
        return null;
    }
    try {
        $stmt = $conn->prepare(
            'SELECT client_id, application_reference, application_status, created_at, MembershipNumber
             FROM tbl_client
             WHERE application_reference = ?
             LIMIT 1'
        );
        $stmt->execute([$ref]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$row) {
            return null;
        }
        return [
            'client_id' => (int) ($row['client_id'] ?? 0),
            'application_reference' => (string) ($row['application_reference'] ?? ''),
            'application_status' => eca_track_status_normalize((string) ($row['application_status'] ?? '')) ?: 'SUBMITTED',
            'created_at' => (string) ($row['created_at'] ?? ''),
            'membership_number' => trim((string) ($row['MembershipNumber'] ?? '')),
        ];
    } catch (Throwable $e) {
        throw $e;
    }
}

function eca_track_active_certificate_number(?PDO $conn, int $clientId): string
{
    if (!$conn || $clientId < 1) {
        return '';
    }
    try {
        $stmt = $conn->prepare(
            'SELECT certificate_number
             FROM membership_certificates
             WHERE client_id = ? AND status = ?
             ORDER BY id DESC
             LIMIT 1'
        );
        $stmt->execute([$clientId, 'ACTIVE']);
        $number = trim((string) $stmt->fetchColumn());
        return $number;
    } catch (Throwable $e) {
        return '';
    }
}

function eca_track_history(int $clientId): array
{
    if ($clientId < 1) {
        return [];
    }
    $conn = eca_audit_db();
    if (!$conn) {
        return [];
    }
    $allowed = [
        'application.reviewed',
        'application.returned',
        'application.approved',
        'application.rejected',
    ];
    try {
        $stmt = $conn->prepare(
            'SELECT action, created_at
             FROM audit_logs
             WHERE entity_type = ?
               AND entity_id = ?
               AND action IN (' . implode(',', array_fill(0, count($allowed), '?')) . ')
             ORDER BY created_at ASC, id ASC'
        );
        $stmt->execute(array_merge(['tbl_client', (string) $clientId], $allowed));
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    } catch (Throwable $e) {
        return [];
    }

    $labels = [
        'application.reviewed' => 'Application moved to Under Review',
        'application.returned' => 'Additional Information Required',
        'application.approved' => 'Application approved',
        'application.rejected' => 'Application rejected',
    ];
    $out = [];
    foreach ($rows as $row) {
        $action = (string) ($row['action'] ?? '');
        $at = (string) ($row['created_at'] ?? '');
        if (!isset($labels[$action]) || $at === '') {
            continue;
        }
        $out[] = [
            'action' => $action,
            'label' => $labels[$action],
            'at' => $at,
        ];
    }
    return $out;
}

function eca_track_first_history_date(array $history, string $action): string
{
    foreach ($history as $row) {
        if (($row['action'] ?? '') === $action) {
            return (string) ($row['at'] ?? '');
        }
    }
    return '';
}

function eca_track_earliest_progress_date(array $history): string
{
    foreach ($history as $row) {
        $at = (string) ($row['at'] ?? '');
        if ($at !== '') {
            return $at;
        }
    }
    return '';
}

/**
 * @return list<array{key:string,label:string,state:string,state_label:string,date:string,iso:string}>
 */
function eca_track_timeline(string $status, string $submittedAt, array $history): array
{
    $status = eca_track_status_normalize($status) ?: 'SUBMITTED';
    $reviewedAt = eca_track_first_history_date($history, 'application.reviewed');
    $returnedAt = eca_track_first_history_date($history, 'application.returned');
    $approvedAt = eca_track_first_history_date($history, 'application.approved');
    $rejectedAt = eca_track_first_history_date($history, 'application.rejected');
    $receivedAt = eca_track_earliest_progress_date($history);

    $step = static function (
        string $key,
        string $label,
        string $state,
        string $rawDate = '',
        string $fallbackLabel = ''
    ): array {
        $stateLabels = [
            'complete' => 'Complete',
            'current' => 'Current stage',
            'upcoming' => 'Upcoming',
            'rejected' => 'Rejected',
        ];
        $date = eca_track_date_short($rawDate);
        return [
            'key' => $key,
            'label' => $label,
            'state' => $state,
            'state_label' => $fallbackLabel !== '' ? $fallbackLabel : ($stateLabels[$state] ?? 'Upcoming'),
            'date' => $date,
            'iso' => eca_track_date_iso($rawDate),
        ];
    };

    if ($status === 'SUBMITTED') {
        return [
            $step('submitted', 'Application Submitted', 'current', $submittedAt),
            $step('review', 'Under Review', 'upcoming'),
            $step('decision', 'Final Decision', 'upcoming'),
        ];
    }

    if ($status === 'UNDER REVIEW') {
        return [
            $step('submitted', 'Application Submitted', 'complete', $submittedAt),
            $step('received', 'Application Received', 'complete', $receivedAt),
            $step('review', 'Under Review', 'current', $reviewedAt),
            $step('decision', 'Final Decision', 'upcoming'),
        ];
    }

    if ($status === 'ADDITIONAL INFORMATION REQUIRED') {
        return [
            $step('submitted', 'Application Submitted', 'complete', $submittedAt),
            $step('received', 'Application Received', 'complete', $receivedAt),
            $step('review', 'Under Review', 'complete', $reviewedAt),
            $step('info', 'Additional Information Required', 'current', $returnedAt),
            $step('final', 'Final Review', 'upcoming'),
        ];
    }

    if ($status === 'APPROVED') {
        return [
            $step('submitted', 'Application Submitted', 'complete', $submittedAt),
            $step('received', 'Application Received', 'complete', $receivedAt),
            $step('review', 'Under Review', 'complete', $reviewedAt),
            $step('final', 'Final Review', 'complete', $approvedAt),
            $step('approved', 'Approved', 'complete', $approvedAt),
        ];
    }

    return [
        $step('submitted', 'Application Submitted', 'complete', $submittedAt),
        $step('received', 'Application Received', 'complete', $receivedAt),
        $step('review', 'Under Review', 'complete', $reviewedAt),
        $step('rejected', 'Rejected', 'rejected', $rejectedAt),
    ];
}

function eca_track_status_copy(string $status): array
{
    $status = eca_track_status_normalize($status) ?: 'SUBMITTED';
    return [
        'SUBMITTED' => [
            'title' => 'Application received',
            'message' => 'Your application has been successfully submitted and is awaiting review.',
        ],
        'UNDER REVIEW' => [
            'title' => 'Under review',
            'message' => 'Your application is currently being reviewed by the ECA team.',
        ],
        'ADDITIONAL INFORMATION REQUIRED' => [
            'title' => 'Additional information is required',
            'message' => 'A document is missing or needs to be replaced. Use your application registration number to upload the file and resend the application.',
        ],
        'APPROVED' => [
            'title' => 'Approved',
            'message' => 'Your application has been approved.',
        ],
        'REJECTED' => [
            'title' => 'Rejected',
            'message' => 'Your application has been rejected.',
        ],
    ][$status];
}

function eca_track_public_events(string $submittedAt, array $history): array
{
    $events = [];
    if ($submittedAt !== '') {
        $events[] = [
            'label' => 'Application Submitted',
            'at' => $submittedAt,
        ];
    }
    foreach ($history as $row) {
        $events[] = [
            'label' => (string) ($row['label'] ?? ''),
            'at' => (string) ($row['at'] ?? ''),
        ];
    }
    return array_values(array_filter(
        $events,
        static fn (array $event): bool => $event['label'] !== '' && $event['at'] !== ''
    ));
}
