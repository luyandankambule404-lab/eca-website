<?php

require_once __DIR__ . '/portal-db.php';
require_once __DIR__ . '/company-data.php';
require_once __DIR__ . '/pagination.php';

function eca_safe_count(PDO $conn, string $sql, array $params = []): int
{
    try {
        $stmt = $conn->prepare($sql);
        $stmt->execute($params);
        return (int) $stmt->fetchColumn();
    } catch (Throwable $e) {
        return 0;
    }
}

/**
 * Server-side COUNT + LIMIT/OFFSET feed for the Super Admin dashboard.
 * Never loads the full table into PHP.
 */
function eca_safe_paged_feed(
    ?PDO $conn,
    string $countSql,
    string $selectSql,
    array $params,
    string $pageKey,
    ?int $limit = null
): array {
    $limit = $limit ?? eca_dashboard_feed_limit();
    if (!$conn) {
        return eca_empty_paged_result($pageKey, $limit);
    }
    try {
        return eca_paged_query_named($conn, $countSql, $selectSql, $params, $pageKey, $limit);
    } catch (Throwable $e) {
        return eca_empty_paged_result($pageKey, $limit);
    }
}

function eca_safe_groups(?PDO $conn, string $sql, array $params = []): array
{
    if (!$conn) {
        return [];
    }
    try {
        $stmt = $conn->prepare($sql);
        $stmt->execute($params);
        $out = [];
        foreach ($stmt->fetchAll(PDO::FETCH_NUM) as $row) {
            $key = strtolower(trim((string) ($row[0] ?? '')));
            $out[$key] = (int) ($row[1] ?? 0);
        }
        return $out;
    } catch (Throwable $e) {
        return [];
    }
}

function eca_safe_rows(?PDO $conn, string $sql, array $params = []): array
{
    if (!$conn) {
        return [];
    }
    try {
        $stmt = $conn->prepare($sql);
        $stmt->execute($params);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
        return is_array($rows) ? $rows : [];
    } catch (Throwable $e) {
        return [];
    }
}

function eca_group_sum(array $groups, array $keys): int
{
    $total = 0;
    foreach ($keys as $key) {
        $total += (int) ($groups[strtolower((string) $key)] ?? 0);
    }
    return $total;
}

function eca_payment_label(string $status): string
{
    $status = strtolower(trim($status));
    if ($status === 'approved' || $status === 'verified') {
        return 'VERIFIED';
    }
    if ($status === 'rejected') {
        return 'REJECTED';
    }
    return 'PENDING';
}

function eca_admin_month_keys(int $months = 8): array
{
    $keys = [];
    $start = new DateTimeImmutable('first day of this month');
    for ($i = $months - 1; $i >= 0; $i--) {
        $keys[] = $start->modify('-' . $i . ' months')->format('Y-m');
    }
    return $keys;
}

function eca_admin_fill_months(array $groups, int $months = 8): array
{
    $filled = [];
    foreach (eca_admin_month_keys($months) as $key) {
        $filled[$key] = (int) ($groups[$key] ?? 0);
    }
    return $filled;
}

/**
 * Build RBAC-safe chart series from existing dashboard KPI aggregates.
 * Does not re-query or invent values — presentation layer only.
 *
 * @param array $stats Output of eca_admin_dashboard_stats()
 * @param array $can Keys: members, companies, applications, payments, certificates, cpd, wellness, tickets
 */
function eca_admin_chart_payload(array $stats, array $can): array
{
    $src = $stats['sources_available'] ?? [];
    $out = [];

    if (!empty($can['members']) && !empty($src['portal'])) {
        $trend = $stats['trends_members'] ?? [];
        $labels = [];
        $values = [];
        foreach ($trend as $ym => $count) {
            $dt = DateTimeImmutable::createFromFormat('Y-m', (string) $ym);
            $labels[] = $dt ? $dt->format('M Y') : (string) $ym;
            $values[] = (int) $count;
        }
        $out['membership_trend'] = [
            'title' => 'Members over time',
            'type' => 'line',
            'labels' => $labels,
            'values' => $values,
            'available' => true,
            'has_data' => array_sum($values) > 0,
            'empty' => 'Historical membership trend not available locally',
        ];
    }

    if (!empty($can['applications']) && !empty($src['portal'])) {
        $labels = ['Pending', 'Approved', 'Rejected', 'Returned', 'Joining type', 'Renewal type'];
        $values = [
            (int) ($stats['applications_pending'] ?? 0),
            (int) ($stats['applications_approved'] ?? 0),
            (int) ($stats['applications_rejected'] ?? 0),
            (int) ($stats['applications_returned'] ?? 0),
            (int) ($stats['members_joining'] ?? 0),
            (int) ($stats['members_renewal_type'] ?? 0),
        ];
        // Prefer application pipeline statuses for the donut (clearer management question).
        $appLabels = ['Submitted', 'Under review', 'Returned', 'Approved', 'Rejected'];
        $appValues = [
            (int) ($stats['applications_submitted'] ?? 0),
            (int) ($stats['applications_review'] ?? 0),
            (int) ($stats['applications_returned'] ?? 0),
            (int) ($stats['applications_approved'] ?? 0),
            (int) ($stats['applications_rejected'] ?? 0),
        ];
        $out['applications'] = [
            'title' => 'Application status',
            'type' => 'doughnut',
            'labels' => $appLabels,
            'values' => $appValues,
            'available' => true,
            'has_data' => array_sum($appValues) > 0,
            'empty' => 'No application data available locally.',
        ];
        unset($labels, $values);
    }

    if (!empty($can['payments']) && !empty($src['payments'])) {
        $payLabels = ['Pending', 'Verified', 'Rejected'];
        $payValues = [
            (int) ($stats['payments_pending'] ?? 0),
            (int) ($stats['payments_verified'] ?? 0),
            (int) ($stats['payments_rejected'] ?? 0),
        ];
        $out['payments'] = [
            'title' => 'Payment proofs (counts)',
            'type' => 'doughnut',
            'labels' => $payLabels,
            'values' => $payValues,
            'available' => true,
            'has_data' => array_sum($payValues) > 0,
            'empty' => 'No payment data available locally.',
            'note' => 'Counts only — no amount totals in local schema.',
        ];
    } elseif (!empty($can['payments'])) {
        $out['payments'] = [
            'title' => 'Payment proofs (counts)',
            'type' => 'doughnut',
            'labels' => [],
            'values' => [],
            'available' => false,
            'has_data' => false,
            'empty' => 'No payment data available locally.',
        ];
    }

    if (!empty($can['certificates']) && !empty($src['certificates'])) {
        $active = (int) ($stats['certificates_active'] ?? 0);
        $revoked = (int) ($stats['certificates_revoked'] ?? 0);
        $expiring = (int) ($stats['certificates_expiring'] ?? 0);
        // Expiring is a subset of Active — show as separate bar categories (not double-counted pie).
        $out['certificates'] = [
            'title' => 'Certificate status',
            'type' => 'bar',
            'labels' => ['Active', 'Expiring (90d)', 'Revoked'],
            'values' => [$active, $expiring, $revoked],
            'available' => true,
            'has_data' => ($active + $revoked + $expiring) > 0,
            'empty' => 'No certificate data available locally.',
            'note' => 'Expiring is a subset of Active; Revoked are never counted as Active.',
        ];
    } elseif (!empty($can['certificates'])) {
        $out['certificates'] = [
            'title' => 'Certificate status',
            'type' => 'bar',
            'labels' => [],
            'values' => [],
            'available' => false,
            'has_data' => false,
            'empty' => 'No certificate data available locally.',
        ];
    }

    if (!empty($can['cpd']) && !empty($src['cpd'])) {
        $cpdValues = [
            (int) ($stats['cpd_approved'] ?? 0),
            (int) ($stats['cpd_pending'] ?? 0),
            (int) ($stats['cpd_rejected'] ?? 0),
        ];
        $out['cpd'] = [
            'title' => 'CPD applications',
            'type' => 'bar',
            'labels' => ['Approved', 'Pending', 'Rejected'],
            'values' => $cpdValues,
            'available' => true,
            'has_data' => array_sum($cpdValues) > 0 || (int) ($stats['cpd_applications'] ?? 0) > 0,
            'empty' => 'No CPD application data available locally.',
            'meta' => [
                'points' => (int) ($stats['cpd_points'] ?? 0),
                'courses' => (int) ($stats['cpd_courses'] ?? 0),
            ],
        ];
    } elseif (!empty($can['cpd'])) {
        $out['cpd'] = [
            'title' => 'CPD applications',
            'type' => 'bar',
            'labels' => [],
            'values' => [],
            'available' => false,
            'has_data' => false,
            'empty' => 'No CPD application data available locally.',
        ];
    }

    if (!empty($can['companies']) && !empty($src['companies'])) {
        $statusGroups = $stats['company_statuses'] ?? [];
        if ($statusGroups) {
            $labels = [];
            $values = [];
            foreach ($statusGroups as $status => $count) {
                $labels[] = $status !== '' ? ucfirst((string) $status) : 'Unknown';
                $values[] = (int) $count;
            }
        } else {
            $labels = ['Active', 'Matched to membership', 'Without match'];
            $values = [
                (int) ($stats['companies_active'] ?? 0),
                (int) ($stats['companies_matched_members'] ?? 0),
                (int) ($stats['companies_unmatched_members'] ?? 0),
            ];
        }
        $out['companies'] = [
            'title' => 'Companies',
            'type' => 'doughnut',
            'labels' => $labels,
            'values' => $values,
            'available' => true,
            'has_data' => array_sum($values) > 0,
            'empty' => 'No company data available locally.',
            'note' => 'Uses companies table status / P1 CI matched totals — soft-match not reimplemented.',
        ];
    } elseif (!empty($can['companies'])) {
        $out['companies'] = [
            'title' => 'Companies',
            'type' => 'doughnut',
            'labels' => [],
            'values' => [],
            'available' => false,
            'has_data' => false,
            'empty' => 'DATA NOT AVAILABLE LOCALLY — companies table not available.',
        ];
    }

    if (!empty($can['wellness']) && !empty($src['wellness'])) {
        $wLabels = ['Published events', 'Upcoming events', 'Registrations', 'Announcements', 'Resources'];
        $wValues = [
            (int) ($stats['wellness_events_published'] ?? 0),
            (int) ($stats['wellness_events'] ?? 0),
            (int) ($stats['wellness_registrations'] ?? 0),
            (int) ($stats['wellness_announcements'] ?? 0),
            (int) ($stats['wellness_resources'] ?? 0),
        ];
        $out['wellness'] = [
            'title' => 'Wellness overview',
            'type' => 'bar',
            'labels' => $wLabels,
            'values' => $wValues,
            'available' => true,
            'has_data' => array_sum($wValues) > 0,
            'empty' => 'No wellness data available locally.',
        ];
    } elseif (!empty($can['wellness'])) {
        $out['wellness'] = [
            'title' => 'Wellness overview',
            'type' => 'bar',
            'labels' => [],
            'values' => [],
            'available' => false,
            'has_data' => false,
            'empty' => 'No wellness data available locally.',
        ];
    }

    if (!empty($can['tickets']) && !empty($src['tickets'])) {
        $msg = $stats['support_message_statuses'] ?? [];
        $tix = $stats['support_ticket_statuses'] ?? [];
        $open = (int) ($stats['tickets_open'] ?? 0);
        $closedMsg = eca_group_sum($msg, ['closed', 'resolved', 'done', 'complete']);
        $pendingMsg = eca_group_sum($msg, ['pending', 'assigned', 'open', 'new', '']);
        $closedTix = eca_group_sum($tix, ['closed', 'resolved', 'done']);
        // Open uses existing combined KPI (no double-count of open). Closed = message closed + ticket closed.
        $sLabels = ['Open (combined)', 'Closed messages', 'Closed tickets'];
        $sValues = [$open, $closedMsg, $closedTix];
        $out['support'] = [
            'title' => 'Support overview',
            'type' => 'bar',
            'labels' => $sLabels,
            'values' => $sValues,
            'available' => true,
            'has_data' => array_sum($sValues) > 0 || $pendingMsg > 0,
            'empty' => 'No support data available locally.',
            'note' => 'Open uses existing tickets_open KPI (messages + tickets). Closed counts are separate sources.',
        ];
    } elseif (!empty($can['tickets'])) {
        $out['support'] = [
            'title' => 'Support overview',
            'type' => 'bar',
            'labels' => [],
            'values' => [],
            'available' => false,
            'has_data' => false,
            'empty' => 'No support data available locally.',
        ];
    }

    return $out;
}

function eca_admin_audit_label(string $action): string
{
    $map = [
        'admin.login' => 'Officer signed in',
        'admin.logout' => 'Officer signed out',
        'admin.login.failed' => 'Officer login failed',
        'access.denied' => 'Access denied',
        'csrf.rejected' => 'CSRF token rejected',
        'privilege.escalation.denied' => 'Privilege escalation denied',
        'document.access.denied' => 'Document access denied',
        'application.status' => 'Application status updated',
        'application.note' => 'Application note added',
        'application.reviewed' => 'Application reviewed',
        'application.approved' => 'Application approved',
        'application.rejected' => 'Application rejected',
        'application.returned' => 'Application returned',
        'application.resubmitted' => 'Application resent',
        'document.request' => 'Documents requested',
        'certificate.generate' => 'Certificate issued',
        'certificate.issued' => 'Certificate issued',
        'certificate.revoke' => 'Certificate revoked',
        'payment.verified' => 'Payment proof verified',
        'payment.approved' => 'Payment proof verified',
        'payment.rejected' => 'Payment proof rejected',
        'payment.pending' => 'Payment proof set pending',
        'content.published' => 'Content published',
        'content.unpublished' => 'Content unpublished',
        'company.update' => 'Company updated',
        'company.updated' => 'Company updated',
        'member.approve' => 'Member standing approved',
        'member.hub_login_created' => 'Member hub login created',
        'member.suspend' => 'Member suspended',
        'member.reactivate' => 'Member reactivated',
        'document.review' => 'Document reviewed',
        'certificate.download' => 'Certificate downloaded',
        'user.roles' => 'User roles changed',
        'user.promoted' => 'User promoted',
        'user.demoted' => 'User demoted',
        'user.activated' => 'User activated',
        'user.deactivated' => 'User deactivated',
        'user.created' => 'User created',
        'user.edited' => 'User profile updated',
        'user.password_reset' => 'Password reset',
        'permissions.changed' => 'Role permissions changed',
        'document.download' => 'Document downloaded',
        'project.open' => 'Project opened',
        'project.upload' => 'Project file uploaded',
        'tender.create' => 'Tender created',
        'event.create' => 'Event created',
        'settings.changed' => 'Settings changed',
        'company.viewed' => 'Company viewed',
        'member.note' => 'Member note added',
        'member.email_notice' => 'Member email notice sent',
        'report.view' => 'Report viewed',
        'ticket.update' => 'Support ticket updated',
        'report.export' => 'Report exported',
    ];
    $action = strtolower(trim($action));
    if (isset($map[$action])) {
        return $map[$action];
    }
    return ucwords(str_replace(['.', '_', '-'], ' ', $action));
}

function eca_admin_dashboard_stats(?PDO $local, ?PDO $portal): array
{
    $stats = [
        'members_total' => 0,
        'members_all_clients' => 0,
        'members_without_number' => 0,
        'members_active' => 0,
        'members_expired' => 0,
        'members_pending' => 0,
        'members_suspended' => 0,
        'members_declined' => 0,
        'members_inprogress' => 0,
        'members_other' => 0,
        'members_joining' => 0,
        'members_renewal_type' => 0,
        'members_type_active' => 0,
        'members_near_expiry' => 0,
        'members_regions' => 0,
        'membership_year_rows' => 0,
        'membership_year_current' => 0,
        'membership_period_2025_2026' => 0,
        'companies' => 0,
        'companies_active' => 0,
        'companies_matched_members' => 0,
        'companies_unmatched_members' => 0,
        'owners_total' => 0,
        'applications_pending' => 0,
        'applications_submitted' => 0,
        'applications_review' => 0,
        'applications_returned' => 0,
        'applications_approved' => 0,
        'applications_rejected' => 0,
        'applications_total' => 0,
        'renewals_pending' => 0,
        'payments_pending' => 0,
        'payments_verified' => 0,
        'payments_rejected' => 0,
        'payments_total' => 0,
        'balances_due' => 0,
        'tickets_open' => 0,
        'cpd_courses' => 0,
        'cpd_open' => 0,
        'cpd_pending' => 0,
        'cpd_approved' => 0,
        'cpd_rejected' => 0,
        'cpd_courses_completed' => 0,
        'cpd_applications' => 0,
        'cpd_points' => 0,
        'certificates_issued' => 0,
        'certificates_active' => 0,
        'certificates_revoked' => 0,
        'certificates_expiring' => 0,
        'wellness_resources' => 0,
        'wellness_events' => 0,
        'wellness_events_published' => 0,
        'wellness_announcements' => 0,
        'wellness_checkins' => 0,
        'wellness_checkins_30d' => 0,
        'wellness_hub_published' => 0,
        'wellness_registrations' => 0,
        'officers_total' => 0,
        'officers_active' => 0,
        'super_admins' => 0,
        'membership_standing' => [],
        'application_statuses' => [],
        'company_statuses' => [],
        'support_message_statuses' => [],
        'support_ticket_statuses' => [],
        'trends_members' => [],
        'trends_applications' => [],
        'trends_cpd' => [],
        'activity' => [],
        'recent_members' => [],
        'recent_applications' => [],
        'recent_payments' => [],
        'recent_companies' => [],
        'recent_messages' => [],
        'recent_members_pager' => eca_empty_paged_result('members_page'),
        'recent_applications_pager' => eca_empty_paged_result('applications_page'),
        'recent_payments_pager' => eca_empty_paged_result('payments_page'),
        'recent_companies_pager' => eca_empty_paged_result('companies_page'),
        'recent_messages_pager' => eca_empty_paged_result('messages_page'),
        'activity_pager' => eca_empty_paged_result('audit_page'),
        'sources_available' => [
            'portal' => false,
            'local' => false,
            'companies' => false,
            'payments' => false,
            'certificates' => false,
            'cpd' => false,
            'wellness' => false,
            'tickets' => false,
            'audit' => false,
        ],
    ];

    $stats['sources_available']['portal'] = (bool) $portal;
    $stats['sources_available']['local'] = (bool) $local;
    if ($portal) {
        $standing = eca_safe_groups(
            $portal,
            "SELECT LOWER(COALESCE(active,'')), COUNT(*) FROM tbl_client GROUP BY LOWER(COALESCE(active,''))"
        );
        $stats['membership_standing'] = $standing;
        $stats['members_active'] = (int) ($standing['active'] ?? 0);
        $stats['members_pending'] = (int) ($standing['pending'] ?? 0);
        $stats['members_suspended'] = eca_group_sum($standing, ['suspended', 'suspend']);
        $stats['members_declined'] = eca_safe_count(
            $portal,
            "SELECT COUNT(*) FROM tbl_client WHERE LOWER(TRIM(COALESCE(active,''))) LIKE '%declin%' OR LOWER(TRIM(COALESCE(Status,''))) LIKE '%declin%'"
        );
        $stats['members_inprogress'] = eca_safe_count(
            $portal,
            "SELECT COUNT(*) FROM tbl_client WHERE LOWER(REPLACE(TRIM(COALESCE(active,'')),' ','')) LIKE '%inprogress%' OR LOWER(REPLACE(TRIM(COALESCE(Status,'')),' ','')) LIKE '%inprogress%'"
        );
        foreach ($standing as $key => $count) {
            if (!in_array($key, ['active', 'pending', 'suspended', 'suspend'], true)) {
                $stats['members_other'] += (int) $count;
            }
        }
        $stats['members_all_clients'] = eca_safe_count($portal, 'SELECT COUNT(*) FROM tbl_client');
        $stats['members_total'] = eca_safe_count(
            $portal,
            "SELECT COUNT(*) FROM tbl_client WHERE MembershipNumber IS NOT NULL AND MembershipNumber <> ''"
        );
        $stats['members_without_number'] = max(0, $stats['members_all_clients'] - $stats['members_total']);
        $typeGroups = eca_safe_groups(
            $portal,
            "SELECT LOWER(TRIM(COALESCE(Status,''))), COUNT(*) FROM tbl_client GROUP BY LOWER(TRIM(COALESCE(Status,'')))"
        );
        $stats['members_joining'] = (int) ($typeGroups['joining'] ?? 0);
        $stats['members_renewal_type'] = (int) ($typeGroups['renewal'] ?? 0);
        $stats['members_type_active'] = (int) ($typeGroups['active'] ?? 0);
        $stats['members_regions'] = eca_safe_count(
            $portal,
            "SELECT COUNT(DISTINCT TRIM(Region)) FROM tbl_client WHERE Region IS NOT NULL AND TRIM(Region) <> ''"
        );
        $stats['members_expired'] = eca_safe_count(
            $portal,
            "SELECT COUNT(*) FROM membership_years WHERE status = 'Expired' OR (expiry_date IS NOT NULL AND expiry_date < CURDATE())"
        );
        $stats['members_near_expiry'] = eca_safe_count(
            $portal,
            "SELECT COUNT(*) FROM membership_years
             WHERE status = 'Active'
               AND expiry_date IS NOT NULL
               AND expiry_date >= CURDATE()
               AND expiry_date <= DATE_ADD(CURDATE(), INTERVAL 90 DAY)"
        );
        $stats['membership_year_rows'] = eca_safe_count($portal, 'SELECT COUNT(*) FROM membership_years');
        $stats['membership_year_current'] = eca_safe_count(
            $portal,
            "SELECT COUNT(*) FROM membership_years WHERE status = 'Active' AND (expiry_date IS NULL OR expiry_date >= CURDATE())"
        );
        $stats['membership_period_2025_2026'] = eca_safe_count(
            $portal,
            "SELECT COUNT(DISTINCT client_id) FROM membership_years WHERE year IN ('2025','2026','2025/2026','2025-2026')"
        );

        $apps = eca_safe_groups(
            $portal,
            "SELECT application_status, COUNT(*) FROM tbl_client
             WHERE application_reference IS NOT NULL AND application_reference <> ''
             GROUP BY application_status"
        );
        $stats['application_statuses'] = $apps;
        $stats['applications_submitted'] = eca_group_sum($apps, ['submitted']);
        $stats['applications_review'] = eca_group_sum($apps, ['under review']);
        $stats['applications_returned'] = eca_group_sum($apps, ['additional information required']);
        $stats['applications_approved'] = eca_group_sum($apps, ['approved']);
        $stats['applications_rejected'] = eca_group_sum($apps, ['rejected']);
        $stats['applications_pending'] = $stats['applications_submitted']
            + $stats['applications_review']
            + $stats['applications_returned'];
        $stats['applications_total'] = array_sum($apps);

        $stats['renewals_pending'] = eca_safe_count(
            $portal,
            "SELECT COUNT(*) FROM membership_years WHERE status = 'Pending' AND type = 'Renewal'"
        );

        $payments = eca_safe_groups($portal, 'SELECT status, COUNT(*) FROM payments GROUP BY status');
        $stats['payments_pending'] = eca_group_sum($payments, ['pending']);
        $stats['payments_verified'] = eca_group_sum($payments, ['approved', 'verified']);
        $stats['payments_rejected'] = eca_group_sum($payments, ['rejected']);
        $stats['payments_total'] = array_sum($payments);
        $stats['sources_available']['payments'] = eca_has_table($portal, 'payments');

        $certs = eca_safe_groups($portal, 'SELECT status, COUNT(*) FROM membership_certificates GROUP BY status');
        $stats['certificates_active'] = eca_group_sum($certs, ['active']);
        $stats['certificates_revoked'] = eca_group_sum($certs, ['revoked']);
        $stats['certificates_issued'] = array_sum($certs);
        $stats['certificates_expiring'] = eca_safe_count(
            $portal,
            "SELECT COUNT(*) FROM membership_certificates
             WHERE UPPER(status) = 'ACTIVE'
               AND expiry_date IS NOT NULL
               AND expiry_date >= CURDATE()
               AND expiry_date <= DATE_ADD(CURDATE(), INTERVAL 90 DAY)"
        );
        $stats['sources_available']['certificates'] = eca_has_table($portal, 'membership_certificates');

        $courses = eca_safe_groups($portal, 'SELECT status, COUNT(*) FROM courses GROUP BY status');
        $stats['cpd_courses'] = array_sum($courses);
        $stats['cpd_open'] = eca_group_sum($courses, ['open']);
        $cpdApps = eca_safe_groups($portal, 'SELECT status, COUNT(*) FROM cpd_applications GROUP BY status');
        $stats['cpd_applications'] = array_sum($cpdApps);
        $stats['cpd_pending'] = eca_group_sum($cpdApps, ['pending']);
        $stats['cpd_approved'] = eca_group_sum($cpdApps, ['approved', 'completed', 'accepted']);
        $stats['cpd_rejected'] = eca_group_sum($cpdApps, ['rejected', 'declined']);
        $stats['cpd_courses_completed'] = eca_group_sum($courses, ['completed', 'closed', 'finished']);
        $stats['cpd_points'] = eca_safe_count($portal, 'SELECT COALESCE(SUM(points),0) FROM cpd_points_ledger');
        $stats['sources_available']['cpd'] = eca_has_table($portal, 'cpd_applications');

        $stats['tickets_open'] += eca_safe_count(
            $portal,
            "SELECT COUNT(*) FROM support_tickets WHERE LOWER(status) IN ('open','pending','new')"
        );
        if (eca_has_table($portal, 'support_tickets')) {
            $stats['sources_available']['tickets'] = true;
            $stats['support_ticket_statuses'] = eca_safe_groups(
                $portal,
                "SELECT LOWER(TRIM(COALESCE(status,''))), COUNT(*) FROM support_tickets GROUP BY LOWER(TRIM(COALESCE(status,'')))"
            );
        }

        // Recent feeds — server-side pagination (10/page), independent *_page keys.
        $membersFeed = eca_safe_paged_feed(
            $portal,
            'SELECT COUNT(*) FROM tbl_client',
            'SELECT client_id, TradingName, CompanyRegistrationName, MembershipNumber, active, Status, created_at
             FROM tbl_client
             ORDER BY client_id DESC',
            [],
            'members_page'
        );
        $stats['recent_members'] = $membersFeed['rows'];
        $stats['recent_members_pager'] = $membersFeed;

        $applicationsFeed = eca_safe_paged_feed(
            $portal,
            "SELECT COUNT(*) FROM tbl_client
             WHERE application_reference IS NOT NULL AND application_reference <> ''",
            "SELECT client_id, TradingName, CompanyRegistrationName, Status, application_reference, application_status, created_at
             FROM tbl_client
             WHERE application_reference IS NOT NULL AND application_reference <> ''
             ORDER BY client_id DESC",
            [],
            'applications_page'
        );
        $stats['recent_applications'] = $applicationsFeed['rows'];
        $stats['recent_applications_pager'] = $applicationsFeed;

        $paymentsFeed = eca_safe_paged_feed(
            $portal,
            'SELECT COUNT(*) FROM payments',
            'SELECT id, user_id, status, payment_year, created_at
             FROM payments
             ORDER BY id DESC',
            [],
            'payments_page'
        );
        $stats['recent_payments'] = $paymentsFeed['rows'];
        $stats['recent_payments_pager'] = $paymentsFeed;

        $since = (new DateTimeImmutable('first day of this month'))->modify('-7 months')->format('Y-m-01');
        $stats['trends_members'] = eca_admin_fill_months(eca_safe_groups(
            $portal,
            "SELECT DATE_FORMAT(created_at, '%Y-%m'), COUNT(*) FROM tbl_client
             WHERE created_at >= ? GROUP BY DATE_FORMAT(created_at, '%Y-%m')",
            [$since]
        ));
        $stats['trends_applications'] = eca_admin_fill_months(eca_safe_groups(
            $portal,
            "SELECT DATE_FORMAT(created_at, '%Y-%m'), COUNT(*) FROM tbl_client
             WHERE created_at >= ?
               AND application_reference IS NOT NULL AND application_reference <> ''
             GROUP BY DATE_FORMAT(created_at, '%Y-%m')",
            [$since]
        ));
        $stats['trends_cpd'] = eca_admin_fill_months(eca_safe_groups(
            $portal,
            "SELECT DATE_FORMAT(created_at, '%Y-%m'), COUNT(*) FROM cpd_applications
             WHERE created_at >= ? GROUP BY DATE_FORMAT(created_at, '%Y-%m')",
            [$since]
        ));
    }

    if ($local && !eca_is_live_readonly()) {
        // P1 company KPIs — reuse companies-intelligence soft-match (do not duplicate).
        if (is_file(__DIR__ . '/companies-intelligence.php')) {
            require_once __DIR__ . '/companies-intelligence.php';
            if (function_exists('eca_ci_company_intelligence')) {
                $ci = eca_ci_company_intelligence($local, $portal);
                $ciKpis = $ci['kpis'] ?? [];
                $stats['companies'] = (int) ($ciKpis['companies_total'] ?? 0);
                $stats['companies_active'] = (int) ($ciKpis['companies_active'] ?? 0);
                $stats['companies_matched_members'] = (int) ($ciKpis['matched_to_member'] ?? 0);
                $stats['companies_unmatched_members'] = max(
                    0,
                    (int) $stats['companies'] - (int) $stats['companies_matched_members']
                );
                $stats['owners_total'] = (int) ($ciKpis['owners_total'] ?? 0);
                $stats['sources_available']['companies'] = true;
            }
        }
        if (eca_has_table($local, 'companies')) {
            $stats['company_statuses'] = eca_safe_groups(
                $local,
                "SELECT LOWER(TRIM(COALESCE(status,''))), COUNT(*) FROM companies GROUP BY LOWER(TRIM(COALESCE(status,'')))"
            );
        }
        if ((int) $stats['companies'] === 0 && eca_has_table($local, 'companies')) {
            $stats['companies'] = eca_table_count($local, 'companies');
            $companyStatus = $stats['company_statuses'] ?: eca_safe_groups($local, 'SELECT status, COUNT(*) FROM companies GROUP BY status');
            $stats['companies_active'] = eca_group_sum($companyStatus, ['active']);
            if ($stats['companies_active'] === 0) {
                $stats['companies_active'] = $stats['companies'];
            }
            $stats['sources_available']['companies'] = true;
        }
        $companiesFeed = eca_safe_paged_feed(
            $local,
            'SELECT COUNT(*) FROM companies',
            'SELECT id, name, registration_number, industry, status
             FROM companies
             ORDER BY id DESC',
            [],
            'companies_page'
        );
        $stats['recent_companies'] = $companiesFeed['rows'];
        $stats['recent_companies_pager'] = $companiesFeed;
        $stats['tickets_open'] += eca_safe_count(
            $local,
            "SELECT COUNT(*) FROM contact_messages WHERE status IS NULL OR status IN ('','OPEN','NEW','PENDING','ASSIGNED')"
        );
        if (eca_has_table($local, 'contact_messages')) {
            $stats['sources_available']['tickets'] = true;
            $stats['support_message_statuses'] = eca_safe_groups(
                $local,
                "SELECT LOWER(TRIM(COALESCE(status,''))), COUNT(*) FROM contact_messages GROUP BY LOWER(TRIM(COALESCE(status,'')))"
            );
            $messagesFeed = eca_safe_paged_feed(
                $local,
                'SELECT COUNT(*) FROM contact_messages',
                'SELECT id, name, email, subject, status, created_at
                 FROM contact_messages
                 ORDER BY id DESC',
                [],
                'messages_page'
            );
            $stats['recent_messages'] = $messagesFeed['rows'];
            $stats['recent_messages_pager'] = $messagesFeed;
        }
        $stats['balances_due'] = eca_safe_count(
            $local,
            "SELECT COUNT(*) FROM balances WHERE LOWER(status) IN ('due','outstanding','unpaid','pending')"
        );

        if (is_file(__DIR__ . '/wellness.php')) {
            require_once __DIR__ . '/wellness.php';
            if (function_exists('eca_wellness_admin_stats')) {
                $wellness = eca_wellness_admin_stats($local);
                $stats['wellness_resources'] = (int) ($wellness['resources'] ?? 0);
                $stats['wellness_events'] = (int) ($wellness['events_upcoming'] ?? 0);
                $stats['wellness_announcements'] = (int) ($wellness['announcements'] ?? 0);
                $stats['wellness_registrations'] = (int) ($wellness['registrations'] ?? 0);
                $stats['sources_available']['wellness'] = true;
                // Published events (all published, not only upcoming).
                $stats['wellness_events_published'] = eca_safe_count(
                    $local,
                    "SELECT COUNT(*) FROM wellness_events WHERE status = 'PUBLISHED'"
                );
            }
        }
        if (is_file(__DIR__ . '/wellness-hub.php')) {
            require_once __DIR__ . '/wellness-hub.php';
            if (function_exists('eca_wellness_hub_stats')) {
                $hubWellness = eca_wellness_hub_stats($local);
                $stats['wellness_checkins'] = (int) ($hubWellness['checkins'] ?? 0);
                $stats['wellness_checkins_30d'] = (int) ($hubWellness['checkins_30d'] ?? 0);
                $stats['wellness_hub_published'] = (int) ($hubWellness['hub_published'] ?? 0);
            }
        }
    } elseif (eca_is_live_readonly()) {
        $stats['live_gaps'] = [];
        $source = $portal;
        $take = static function (?PDO $source, string $table, string $sql) use (&$stats): int {
            if (!$source || !eca_has_table($source, $table)) {
                $stats['live_gaps'][] = $table;
                return 0;
            }
            return eca_safe_count($source, $sql);
        };
        if ($source && eca_has_table($source, 'companies')) {
            $stats['companies'] = eca_safe_count($source, 'SELECT COUNT(*) FROM companies');
            $companyStatus = eca_safe_groups($source, 'SELECT status, COUNT(*) FROM companies GROUP BY status');
            $stats['companies_active'] = eca_group_sum($companyStatus, ['active']);
            if ($stats['companies_active'] === 0) {
                $stats['companies_active'] = $stats['companies'];
            }
        } else {
            $stats['live_gaps'][] = 'companies';
        }
        $stats['tickets_open'] += $take(
            $source,
            'contact_messages',
            "SELECT COUNT(*) FROM contact_messages WHERE status IS NULL OR status IN ('','OPEN','NEW','PENDING','ASSIGNED')"
        );
        $stats['balances_due'] = $take(
            $source,
            'balances',
            "SELECT COUNT(*) FROM balances WHERE LOWER(status) IN ('due','outstanding','unpaid','pending')"
        );
        if ($source && is_file(__DIR__ . '/wellness.php') && eca_has_table($source, 'wellness_resources')) {
            require_once __DIR__ . '/wellness.php';
            if (function_exists('eca_wellness_admin_stats')) {
                $wellness = eca_wellness_admin_stats($source);
                $stats['wellness_resources'] = (int) ($wellness['resources'] ?? 0);
                $stats['wellness_events'] = (int) ($wellness['events_upcoming'] ?? 0);
                $stats['wellness_announcements'] = (int) ($wellness['announcements'] ?? 0);
                $stats['wellness_registrations'] = (int) ($wellness['registrations'] ?? 0);
            }
        } else {
            $stats['live_gaps'][] = 'wellness_resources';
        }
        if ($source && is_file(__DIR__ . '/wellness-hub.php') && eca_has_table($source, 'wellness_checkins')) {
            require_once __DIR__ . '/wellness-hub.php';
            if (function_exists('eca_wellness_hub_stats')) {
                $hubWellness = eca_wellness_hub_stats($source);
                $stats['wellness_checkins'] = (int) ($hubWellness['checkins'] ?? 0);
                $stats['wellness_checkins_30d'] = (int) ($hubWellness['checkins_30d'] ?? 0);
                $stats['wellness_hub_published'] = (int) ($hubWellness['hub_published'] ?? 0);
            }
        } else {
            $stats['live_gaps'][] = 'wellness_checkins';
        }
        $stats['live_gaps'] = array_values(array_unique($stats['live_gaps']));
    }

    if ($local) {
        $officerRoles = ['admin', 'super_admin', 'membership_officer', 'finance_officer', 'content_manager', 'training_officer'];
        $placeholders = implode(',', array_fill(0, count($officerRoles), '?'));
        $stats['officers_total'] = eca_safe_count(
            $local,
            "SELECT COUNT(*) FROM users WHERE role IN ($placeholders)",
            $officerRoles
        );
        $stats['officers_active'] = eca_safe_count(
            $local,
            "SELECT COUNT(*) FROM users WHERE role IN ($placeholders) AND (status IS NULL OR UPPER(status) = 'ACTIVE')",
            $officerRoles
        );
        $stats['super_admins'] = eca_safe_count(
            $local,
            "SELECT COUNT(DISTINCT u.id)
             FROM users u
             LEFT JOIN user_roles ur ON ur.user_id = u.id
             LEFT JOIN roles r ON r.id = ur.role_id
             WHERE LOWER(u.role) IN ('super_admin','superadmin','supperadmin')
                OR r.slug = 'super_admin'"
        );

        $exclude = ['project.open', 'project.upload', 'document.download', 'certificate.download'];
        $where = 'WHERE action NOT IN (' . implode(',', array_fill(0, count($exclude), '?')) . ')';
        $params = $exclude;
        if (function_exists('eca_can') && function_exists('eca_security_audit_actions') && !eca_can('security.view')) {
            $blocked = eca_security_audit_actions();
            if ($blocked) {
                $where .= ' AND (action IS NULL OR action NOT IN (' . implode(',', array_fill(0, count($blocked), '?')) . '))';
                foreach ($blocked as $action) {
                    $params[] = $action;
                }
            }
        }
        $activityFeed = eca_safe_paged_feed(
            $local,
            "SELECT COUNT(*) FROM audit_logs $where",
            "SELECT actor_email, actor_type, action, entity_type, entity_id, created_at
             FROM audit_logs $where
             ORDER BY id DESC",
            $params,
            'audit_page'
        );
        $stats['activity'] = $activityFeed['rows'];
        $stats['activity_pager'] = $activityFeed;
        $stats['sources_available']['audit'] = eca_has_table($local, 'audit_logs');
    }

    return $stats;
}
