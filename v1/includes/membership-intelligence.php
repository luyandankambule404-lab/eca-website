<?php
/**
 * Local Membership Intelligence helpers.
 * All metrics are query-driven from eca_portal_local (never hard-coded).
 */

require_once __DIR__ . '/membership.php';

function eca_mi_period_years(string $period): array
{
    $period = trim($period);
    if ($period === '2025-2026' || $period === '2025/2026') {
        return ['2025', '2026'];
    }
    if (preg_match('/^\d{4}$/', $period)) {
        return [$period];
    }
    return [];
}

/**
 * @return array{kpis: array<string,int|array>, sources: array<string,string>, groups: array<string,array>}
 */
function eca_mi_intelligence(?PDO $portal): array
{
    $empty = [
        'kpis' => [
            'tbl_client_total' => 0,
            'with_membership_number' => 0,
            'without_membership_number' => 0,
            'standing_active' => 0,
            'standing_pending' => 0,
            'standing_suspended' => 0,
            'standing_declined' => 0,
            'standing_inprogress' => 0,
            'standing_other' => 0,
            'type_joining' => 0,
            'type_renewal' => 0,
            'type_active' => 0,
            'type_other' => 0,
            'years_total_rows' => 0,
            'years_expired' => 0,
            'years_expiring_90d' => 0,
            'years_renewal_pending' => 0,
            'years_current_active' => 0,
            'new_members_30d' => 0,
            'regions_represented' => 0,
            'period_2025_2026_clients' => 0,
        ],
        'sources' => [],
        'groups' => [
            'standing' => [],
            'type' => [],
            'classification' => [],
            'region' => [],
            'enterprise' => [],
            'years' => [],
            'trends' => [],
        ],
    ];
    if (!$portal) {
        return $empty;
    }

    $sources = [
        'tbl_client_total' => "SELECT COUNT(*) FROM tbl_client",
        'with_membership_number' => "SELECT COUNT(*) FROM tbl_client WHERE MembershipNumber IS NOT NULL AND TRIM(MembershipNumber) <> ''",
        'without_membership_number' => "SELECT COUNT(*) FROM tbl_client WHERE MembershipNumber IS NULL OR TRIM(MembershipNumber) = ''",
        'standing_active' => "SELECT COUNT(*) FROM tbl_client WHERE LOWER(TRIM(COALESCE(active,''))) = 'active'",
        'standing_pending' => "SELECT COUNT(*) FROM tbl_client WHERE LOWER(TRIM(COALESCE(active,''))) = 'pending'",
        'standing_suspended' => "SELECT COUNT(*) FROM tbl_client WHERE LOWER(TRIM(COALESCE(active,''))) LIKE '%suspend%'",
        'standing_declined' => "SELECT COUNT(*) FROM tbl_client WHERE LOWER(TRIM(COALESCE(active,''))) LIKE '%declin%' OR LOWER(TRIM(COALESCE(Status,''))) LIKE '%declin%'",
        'standing_inprogress' => "SELECT COUNT(*) FROM tbl_client WHERE LOWER(REPLACE(TRIM(COALESCE(active,'')),' ','')) LIKE '%inprogress%' OR LOWER(REPLACE(TRIM(COALESCE(Status,'')),' ','')) LIKE '%inprogress%'",
        'type_joining' => "SELECT COUNT(*) FROM tbl_client WHERE LOWER(TRIM(COALESCE(Status,''))) = 'joining'",
        'type_renewal' => "SELECT COUNT(*) FROM tbl_client WHERE LOWER(TRIM(COALESCE(Status,''))) = 'renewal'",
        'type_active' => "SELECT COUNT(*) FROM tbl_client WHERE LOWER(TRIM(COALESCE(Status,''))) = 'active'",
        'years_total_rows' => "SELECT COUNT(*) FROM membership_years",
        'years_expired' => "SELECT COUNT(*) FROM membership_years WHERE status = 'Expired' OR (expiry_date IS NOT NULL AND expiry_date < CURDATE())",
        'years_expiring_90d' => "SELECT COUNT(*) FROM membership_years WHERE status = 'Active' AND expiry_date IS NOT NULL AND expiry_date >= CURDATE() AND expiry_date <= DATE_ADD(CURDATE(), INTERVAL 90 DAY)",
        'years_renewal_pending' => "SELECT COUNT(*) FROM membership_years WHERE status = 'Pending' AND type = 'Renewal'",
        'years_current_active' => "SELECT COUNT(*) FROM membership_years WHERE status = 'Active' AND (expiry_date IS NULL OR expiry_date >= CURDATE())",
        'new_members_30d' => "SELECT COUNT(*) FROM tbl_client WHERE created_at IS NOT NULL AND created_at >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)",
        'regions_represented' => "SELECT COUNT(DISTINCT TRIM(Region)) FROM tbl_client WHERE Region IS NOT NULL AND TRIM(Region) <> ''",
        'period_2025_2026_clients' => "SELECT COUNT(DISTINCT client_id) FROM membership_years WHERE year IN ('2025','2026','2025/2026','2025-2026')",
        'standing_group' => "SELECT LOWER(TRIM(COALESCE(active,''))), COUNT(*) FROM tbl_client GROUP BY LOWER(TRIM(COALESCE(active,'')))",
        'type_group' => "SELECT LOWER(TRIM(COALESCE(Status,''))), COUNT(*) FROM tbl_client GROUP BY LOWER(TRIM(COALESCE(Status,'')))",
        'classification_group' => "SELECT COALESCE(NULLIF(TRIM(Clasification),''),'(blank)'), COUNT(*) FROM tbl_client GROUP BY COALESCE(NULLIF(TRIM(Clasification),''),'(blank)')",
        'region_group' => "SELECT COALESCE(NULLIF(TRIM(Region),''),'(blank)'), COUNT(*) FROM tbl_client GROUP BY COALESCE(NULLIF(TRIM(Region),''),'(blank)')",
        'enterprise_group' => "SELECT COALESCE(NULLIF(TRIM(Enterprise),''),'(blank)'), COUNT(*) FROM tbl_client GROUP BY COALESCE(NULLIF(TRIM(Enterprise),''),'(blank)')",
        'years_group' => "SELECT COALESCE(NULLIF(TRIM(year),''),'(blank)'), COUNT(*) FROM membership_years GROUP BY COALESCE(NULLIF(TRIM(year),''),'(blank)') ORDER BY year DESC",
        'trends' => "SELECT DATE_FORMAT(created_at, '%Y-%m'), COUNT(*) FROM tbl_client WHERE created_at IS NOT NULL AND created_at >= DATE_SUB(CURDATE(), INTERVAL 11 MONTH) GROUP BY DATE_FORMAT(created_at, '%Y-%m') ORDER BY 1",
    ];

    $kpis = $empty['kpis'];
    foreach ([
        'tbl_client_total', 'with_membership_number', 'without_membership_number',
        'standing_active', 'standing_pending', 'standing_suspended', 'standing_declined', 'standing_inprogress',
        'type_joining', 'type_renewal', 'type_active',
        'years_total_rows', 'years_expired', 'years_expiring_90d', 'years_renewal_pending', 'years_current_active',
        'new_members_30d', 'regions_represented', 'period_2025_2026_clients',
    ] as $key) {
        $kpis[$key] = eca_safe_count($portal, $sources[$key]);
    }

    $standing = eca_safe_groups($portal, $sources['standing_group']);
    $type = eca_safe_groups($portal, $sources['type_group']);
    $knownStanding = $kpis['standing_active'] + $kpis['standing_pending'] + $kpis['standing_suspended']
        + $kpis['standing_declined'] + $kpis['standing_inprogress'];
    $kpis['standing_other'] = max(0, $kpis['tbl_client_total'] - $knownStanding);
    $knownType = $kpis['type_joining'] + $kpis['type_renewal'] + $kpis['type_active'];
    $kpis['type_other'] = max(0, $kpis['tbl_client_total'] - $knownType);

    return [
        'kpis' => $kpis,
        'sources' => $sources,
        'groups' => [
            'standing' => $standing,
            'type' => $type,
            'classification' => eca_safe_groups($portal, $sources['classification_group']),
            'region' => eca_safe_groups($portal, $sources['region_group']),
            'enterprise' => eca_safe_groups($portal, $sources['enterprise_group']),
            'years' => eca_safe_groups($portal, $sources['years_group']),
            'trends' => eca_safe_groups($portal, $sources['trends']),
        ],
    ];
}

/**
 * Data quality findings only — never mutates data.
 *
 * @return array<int,array{code:string,label:string,count:int,sql:string}>
 */
function eca_mi_data_quality(?PDO $portal): array
{
    if (!$portal) {
        return [];
    }
    $checks = [
        [
            'code' => 'missing_membership_number',
            'label' => 'tbl_client rows without membership number',
            'sql' => "SELECT COUNT(*) FROM tbl_client WHERE MembershipNumber IS NULL OR TRIM(MembershipNumber) = ''",
        ],
        [
            'code' => 'missing_standing',
            'label' => 'tbl_client rows with blank standing (active)',
            'sql' => "SELECT COUNT(*) FROM tbl_client WHERE active IS NULL OR TRIM(active) = ''",
        ],
        [
            'code' => 'missing_type',
            'label' => 'tbl_client rows with blank membership type (Status)',
            'sql' => "SELECT COUNT(*) FROM tbl_client WHERE Status IS NULL OR TRIM(Status) = ''",
        ],
        [
            'code' => 'duplicate_membership_numbers',
            'label' => 'Duplicate non-empty membership numbers',
            'sql' => "SELECT COUNT(*) FROM (
                        SELECT MembershipNumber FROM tbl_client
                        WHERE MembershipNumber IS NOT NULL AND TRIM(MembershipNumber) <> ''
                        GROUP BY MembershipNumber HAVING COUNT(*) > 1
                      ) d",
        ],
        [
            'code' => 'duplicate_emails',
            'label' => 'Duplicate non-empty emails',
            'sql' => "SELECT COUNT(*) FROM (
                        SELECT LOWER(TRIM(EmailAddress)) e FROM tbl_client
                        WHERE EmailAddress IS NOT NULL AND TRIM(EmailAddress) <> ''
                        GROUP BY LOWER(TRIM(EmailAddress)) HAVING COUNT(*) > 1
                      ) d",
        ],
        [
            'code' => 'numbered_without_year',
            'label' => 'Numbered members with no membership_years row',
            'sql' => "SELECT COUNT(*) FROM tbl_client c
                      WHERE c.MembershipNumber IS NOT NULL AND TRIM(c.MembershipNumber) <> ''
                        AND NOT EXISTS (
                          SELECT 1 FROM membership_years y WHERE y.client_id = c.client_id
                        )",
        ],
        [
            'code' => 'orphan_years',
            'label' => 'membership_years rows with no matching tbl_client',
            'sql' => "SELECT COUNT(*) FROM membership_years y
                      WHERE y.client_id IS NULL OR y.client_id = 0
                         OR NOT EXISTS (SELECT 1 FROM tbl_client c WHERE c.client_id = y.client_id)",
        ],
        [
            'code' => 'invalid_expiry',
            'label' => 'membership_years with unusable expiry_date string',
            'sql' => "SELECT COUNT(*) FROM membership_years
                      WHERE expiry_date IS NOT NULL AND expiry_date = '0000-00-00'",
        ],
        [
            'code' => 'expired_years',
            'label' => 'Expired membership year rows',
            'sql' => "SELECT COUNT(*) FROM membership_years
                      WHERE status = 'Expired' OR (expiry_date IS NOT NULL AND expiry_date < CURDATE())",
        ],
    ];
    $out = [];
    foreach ($checks as $check) {
        $out[] = [
            'code' => $check['code'],
            'label' => $check['label'],
            'count' => eca_safe_count($portal, $check['sql']),
            'sql' => $check['sql'],
        ];
    }
    return $out;
}

/**
 * @return array{where:string,params:array,joins:string}
 */
function eca_mi_build_filters(array $filters): array
{
    $where = ['1=1'];
    $params = [];
    $joins = ' LEFT JOIN (
        SELECT y1.*
        FROM membership_years y1
        INNER JOIN (
            SELECT client_id, MAX(id) AS max_id
            FROM membership_years
            GROUP BY client_id
        ) latest ON latest.max_id = y1.id
    ) my ON my.client_id = c.client_id ';

    $search = trim((string) ($filters['search'] ?? ''));
    if ($search !== '') {
        $where[] = '(c.MembershipNumber LIKE ? OR c.TradingName LIKE ? OR c.CompanyRegistrationName LIKE ?
                     OR c.EmailAddress LIKE ? OR c.Region LIKE ? OR c.Clasification LIKE ?)';
        $like = '%' . $search . '%';
        array_push($params, $like, $like, $like, $like, $like, $like);
    }

    $standing = trim((string) ($filters['standing'] ?? ''));
    if ($standing !== '' && in_array($standing, eca_member_standing_values(), true)) {
        $where[] = 'c.active = ?';
        $params[] = $standing;
    }

    $type = trim((string) ($filters['type'] ?? ''));
    if ($type !== '' && in_array($type, eca_member_type_values(), true)) {
        $where[] = 'c.Status = ?';
        $params[] = $type;
    }

    $classification = trim((string) ($filters['classification'] ?? ''));
    if ($classification !== '') {
        $where[] = 'c.Clasification = ?';
        $params[] = $classification;
    }

    $region = trim((string) ($filters['region'] ?? ''));
    if ($region !== '') {
        $where[] = 'c.Region = ?';
        $params[] = $region;
    }

    $hasNumber = trim((string) ($filters['has_number'] ?? ''));
    if ($hasNumber === 'yes') {
        $where[] = "c.MembershipNumber IS NOT NULL AND TRIM(c.MembershipNumber) <> ''";
    } elseif ($hasNumber === 'no') {
        $where[] = "c.MembershipNumber IS NULL OR TRIM(c.MembershipNumber) = ''";
    }

    $period = trim((string) ($filters['period'] ?? ''));
    $years = eca_mi_period_years($period);
    $year = trim((string) ($filters['year'] ?? ''));
    if ($years) {
        $placeholders = implode(',', array_fill(0, count($years), '?'));
        $where[] = "EXISTS (
            SELECT 1 FROM membership_years y
            WHERE y.client_id = c.client_id
              AND (y.year IN ($placeholders) OR y.year IN ('2025/2026','2025-2026'))
        )";
        foreach ($years as $y) {
            $params[] = $y;
        }
    } elseif ($year !== '' && preg_match('/^\d{4}$/', $year)) {
        $where[] = 'EXISTS (SELECT 1 FROM membership_years y WHERE y.client_id = c.client_id AND y.year = ?)';
        $params[] = $year;
    }

    $yearState = trim((string) ($filters['year_state'] ?? ''));
    $stateSql = $yearState !== '' ? eca_membership_year_state_sql($yearState) : null;
    if ($stateSql) {
        // Rewrite helper SQL (uses bare client_id) to c.client_id context.
        $where[] = preg_replace('/\bclient_id\b/', 'c.client_id', $stateSql, 1) ?? $stateSql;
    }

    $dateFrom = trim((string) ($filters['date_from'] ?? ''));
    if ($dateFrom !== '' && preg_match('/^\d{4}-\d{2}-\d{2}$/', $dateFrom)) {
        $where[] = 'DATE(c.DateOfRegistration) >= ?';
        $params[] = $dateFrom;
    }
    $dateTo = trim((string) ($filters['date_to'] ?? ''));
    if ($dateTo !== '' && preg_match('/^\d{4}-\d{2}-\d{2}$/', $dateTo)) {
        $where[] = 'DATE(c.DateOfRegistration) <= ?';
        $params[] = $dateTo;
    }

    return [
        'where' => implode(' AND ', $where),
        'params' => $params,
        'joins' => $joins,
    ];
}

function eca_mi_sort_sql(string $sort): string
{
    $map = [
        'name' => 'COALESCE(c.TradingName, c.CompanyRegistrationName) ASC',
        'name_desc' => 'COALESCE(c.TradingName, c.CompanyRegistrationName) DESC',
        'membership' => 'c.MembershipNumber ASC',
        'membership_desc' => 'c.MembershipNumber DESC',
        'region' => 'c.Region ASC',
        'classification' => 'c.Clasification ASC',
        'standing' => 'c.active ASC',
        'type' => 'c.Status ASC',
        'registered' => 'c.DateOfRegistration DESC',
        'registered_asc' => 'c.DateOfRegistration ASC',
        'year' => 'my.year DESC',
        'expiry' => 'my.expiry_date ASC',
    ];
    return $map[$sort] ?? $map['name'];
}

function eca_mi_list_select_sql(string $joins, string $where, string $order): string
{
    return "SELECT
        c.client_id,
        c.MembershipNumber,
        c.TradingName,
        c.CompanyRegistrationName,
        c.EmailAddress,
        c.Cellphone,
        c.Region,
        c.Clasification,
        c.Enterprise,
        c.Status,
        c.active,
        c.application_status,
        c.application_reference,
        c.DateOfRegistration,
        my.year AS membership_year,
        my.type AS membership_year_type,
        my.status AS membership_year_status,
        my.expiry_date
     FROM tbl_client c
     $joins
     WHERE $where
     ORDER BY $order";
}

function eca_mi_count_sql(string $joins, string $where): string
{
    return "SELECT COUNT(*) FROM tbl_client c $joins WHERE $where";
}

/**
 * Distinct filter option lists from local data.
 *
 * @return array{years:array,classifications:array,regions:array}
 */
function eca_mi_filter_options(?PDO $portal): array
{
    $out = ['years' => [], 'classifications' => [], 'regions' => []];
    if (!$portal) {
        return $out;
    }
    try {
        $out['years'] = $portal->query("SELECT DISTINCT year FROM membership_years WHERE year IS NOT NULL AND TRIM(year) <> '' ORDER BY year DESC")->fetchAll(PDO::FETCH_COLUMN) ?: [];
    } catch (Throwable $e) {
        $out['years'] = [];
    }
    try {
        $out['classifications'] = $portal->query("SELECT DISTINCT Clasification FROM tbl_client WHERE Clasification IS NOT NULL AND TRIM(Clasification) <> '' ORDER BY Clasification ASC")->fetchAll(PDO::FETCH_COLUMN) ?: [];
    } catch (Throwable $e) {
        $out['classifications'] = [];
    }
    try {
        $out['regions'] = $portal->query("SELECT DISTINCT Region FROM tbl_client WHERE Region IS NOT NULL AND TRIM(Region) <> '' ORDER BY Region ASC")->fetchAll(PDO::FETCH_COLUMN) ?: [];
    } catch (Throwable $e) {
        $out['regions'] = [];
    }
    return $out;
}

function eca_mi_badge_class(string $value): string
{
    $v = strtolower(trim($value));
    if (in_array($v, ['active', 'approved'], true)) {
        return 'hub-badge hub-badge-ok';
    }
    if (in_array($v, ['pending', 'joining', 'submitted', 'under review'], true) || str_contains($v, 'progress')) {
        return 'hub-badge hub-badge-warn';
    }
    if (str_contains($v, 'suspend') || str_contains($v, 'declin') || str_contains($v, 'reject') || str_contains($v, 'expir')) {
        return 'hub-badge hub-badge-danger';
    }
    return 'hub-badge';
}
