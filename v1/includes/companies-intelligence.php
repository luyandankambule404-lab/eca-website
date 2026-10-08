<?php
/**
 * Local Companies / Owners intelligence helpers.
 * Uses eca_local.companies + eca_portal_local.tbl_client/owners — no invented tables.
 */

require_once __DIR__ . '/admin-stats.php';
require_once __DIR__ . '/admin-ops.php';

/**
 * @return array{kpis: array<string,int>, sources: array<string,string>, groups: array<string,array>}
 */
function eca_ci_company_intelligence(?PDO $local, ?PDO $portal): array
{
    $kpis = [
        'companies_total' => 0,
        'companies_active' => 0,
        'companies_inactive' => 0,
        'companies_suspended' => 0,
        'companies_other_status' => 0,
        'with_registration' => 0,
        'without_registration' => 0,
        'matched_to_member' => 0,
        'with_active_certificate' => 0,
        'with_expired_certificate' => 0,
        'clients_total' => 0,
        'clients_with_owners' => 0,
        'owners_total' => 0,
    ];
    $sources = [
        'companies_total' => 'SELECT COUNT(*) FROM companies',
        'companies_active' => "SELECT COUNT(*) FROM companies WHERE LOWER(TRIM(COALESCE(status,''))) = 'active'",
        'companies_inactive' => "SELECT COUNT(*) FROM companies WHERE LOWER(TRIM(COALESCE(status,''))) IN ('inactive','disabled')",
        'companies_suspended' => "SELECT COUNT(*) FROM companies WHERE LOWER(TRIM(COALESCE(status,''))) LIKE '%suspend%'",
        'with_registration' => "SELECT COUNT(*) FROM companies WHERE registration_number IS NOT NULL AND TRIM(registration_number) <> ''",
        'without_registration' => "SELECT COUNT(*) FROM companies WHERE registration_number IS NULL OR TRIM(registration_number) = ''",
        'industry_group' => "SELECT COALESCE(NULLIF(TRIM(industry),''),'(blank)'), COUNT(*) FROM companies GROUP BY COALESCE(NULLIF(TRIM(industry),''),'(blank)')",
        'status_group' => "SELECT COALESCE(NULLIF(TRIM(status),''),'(blank)'), COUNT(*) FROM companies GROUP BY COALESCE(NULLIF(TRIM(status),''),'(blank)')",
        'clients_total' => 'SELECT COUNT(*) FROM tbl_client',
        'owners_total' => 'SELECT COUNT(*) FROM owners',
        'clients_with_owners' => 'SELECT COUNT(DISTINCT clientid) FROM owners WHERE clientid IS NOT NULL AND clientid > 0',
    ];
    $groups = ['industry' => [], 'status' => []];

    if ($local) {
        foreach (['companies_total', 'companies_active', 'companies_inactive', 'companies_suspended', 'with_registration', 'without_registration'] as $key) {
            $kpis[$key] = eca_safe_count($local, $sources[$key]);
        }
        $known = $kpis['companies_active'] + $kpis['companies_inactive'] + $kpis['companies_suspended'];
        $kpis['companies_other_status'] = max(0, $kpis['companies_total'] - $known);
        $groups['industry'] = eca_safe_groups($local, $sources['industry_group']);
        $groups['status'] = eca_safe_groups($local, $sources['status_group']);

        // Soft-match: directory registration_number equals a MembershipNumber (portal DB).
        if ($portal) {
            $matched = 0;
            try {
                $regs = $local->query(
                    "SELECT registration_number FROM companies
                     WHERE registration_number IS NOT NULL AND TRIM(registration_number) <> ''"
                )->fetchAll(PDO::FETCH_COLUMN) ?: [];
                if ($regs) {
                    // Count companies whose reg matches at least one membership number.
                    $stmt2 = $portal->prepare(
                        "SELECT LOWER(TRIM(MembershipNumber)) FROM tbl_client
                         WHERE MembershipNumber IS NOT NULL AND TRIM(MembershipNumber) <> ''"
                    );
                    $stmt2->execute();
                    $memberNums = [];
                    foreach ($stmt2->fetchAll(PDO::FETCH_COLUMN) ?: [] as $num) {
                        $memberNums[strtolower(trim((string) $num))] = true;
                    }
                    foreach ($regs as $reg) {
                        if (isset($memberNums[strtolower(trim((string) $reg))])) {
                            $matched++;
                        }
                    }
                }
            } catch (Throwable $e) {
                $matched = 0;
            }
            $kpis['matched_to_member'] = $matched;

            $activeCerts = 0;
            $expiredCerts = 0;
            try {
                $regs = $local->query(
                    "SELECT registration_number FROM companies
                     WHERE registration_number IS NOT NULL AND TRIM(registration_number) <> ''"
                )->fetchAll(PDO::FETCH_COLUMN) ?: [];
                if ($regs) {
                    $ph = implode(',', array_fill(0, count($regs), '?'));
                    $activeCerts = eca_safe_count(
                        $portal,
                        "SELECT COUNT(DISTINCT c.MembershipNumber)
                         FROM tbl_client c
                         INNER JOIN membership_certificates mc ON mc.client_id = c.client_id
                         WHERE c.MembershipNumber IN ($ph)
                           AND UPPER(mc.status) = 'ACTIVE'
                           AND (mc.expiry_date IS NULL OR mc.expiry_date >= CURDATE())",
                        array_values($regs)
                    );
                    // Count companies (by reg) that have an expired cert on the matched member.
                    $expiredCerts = eca_safe_count(
                        $portal,
                        "SELECT COUNT(DISTINCT c.MembershipNumber)
                         FROM tbl_client c
                         INNER JOIN membership_certificates mc ON mc.client_id = c.client_id
                         WHERE c.MembershipNumber IN ($ph)
                           AND (UPPER(mc.status) = 'EXPIRED'
                                OR (mc.expiry_date IS NOT NULL AND mc.expiry_date < CURDATE()))",
                        array_values($regs)
                    );
                }
            } catch (Throwable $e) {
                $activeCerts = 0;
                $expiredCerts = 0;
            }
            $kpis['with_active_certificate'] = $activeCerts;
            $kpis['with_expired_certificate'] = $expiredCerts;
        } else {
            $kpis['matched_to_member'] = 0;
        }
    }

    if ($portal) {
        $kpis['clients_total'] = eca_safe_count($portal, $sources['clients_total']);
        $kpis['owners_total'] = eca_safe_count($portal, $sources['owners_total']);
        $kpis['clients_with_owners'] = eca_safe_count($portal, $sources['clients_with_owners']);
    }

    $sources['matched_to_member'] = 'companies.registration_number IN (tbl_client.MembershipNumber) — soft match across DBs';
    $sources['with_active_certificate'] = 'Matched MembershipNumber has membership_certificates ACTIVE and not past expiry';
    $sources['with_expired_certificate'] = 'Matched MembershipNumber has expired/past-expiry certificate row';

    return ['kpis' => $kpis, 'sources' => $sources, 'groups' => $groups];
}

/**
 * @return array{where:string,params:array}
 */
function eca_ci_company_filters(array $filters): array
{
    $where = ['1=1'];
    $params = [];

    $search = trim((string) ($filters['search'] ?? ''));
    if ($search !== '') {
        $where[] = '(name LIKE ? OR registration_number LIKE ? OR email LIKE ? OR phone LIKE ? OR address LIKE ? OR industry LIKE ? OR description LIKE ?)';
        $like = '%' . $search . '%';
        array_push($params, $like, $like, $like, $like, $like, $like, $like);
    }

    $status = trim((string) ($filters['status'] ?? ''));
    if ($status !== '') {
        if (strcasecmp($status, 'inactive') === 0) {
            $where[] = "LOWER(TRIM(COALESCE(status,''))) IN ('inactive','disabled')";
        } elseif (strcasecmp($status, 'suspended') === 0) {
            $where[] = "LOWER(TRIM(COALESCE(status,''))) LIKE '%suspend%'";
        } else {
            $where[] = 'LOWER(TRIM(COALESCE(status,\'\'))) = ?';
            $params[] = strtolower($status);
        }
    }

    $industry = trim((string) ($filters['industry'] ?? ''));
    if ($industry !== '') {
        $tokens = function_exists('eca_industry_tokens') ? eca_industry_tokens($industry) : [strtolower($industry)];
        $ors = [];
        foreach ($tokens as $token) {
            $ors[] = 'LOWER(industry) LIKE ?';
            $params[] = '%' . $token . '%';
        }
        if ($ors) {
            $where[] = '(' . implode(' OR ', $ors) . ')';
        }
    }

    $region = trim((string) ($filters['region'] ?? ''));
    if ($region !== '') {
        $where[] = 'LOWER(TRIM(COALESCE(address,\'\'))) = ?';
        $params[] = strtolower($region);
    }

    $hasReg = trim((string) ($filters['has_registration'] ?? ''));
    if ($hasReg === 'yes') {
        $where[] = "registration_number IS NOT NULL AND TRIM(registration_number) <> ''";
    } elseif ($hasReg === 'no') {
        $where[] = "registration_number IS NULL OR TRIM(registration_number) = ''";
    }

    return ['where' => implode(' AND ', $where), 'params' => $params];
}

function eca_ci_company_sort(string $sort): string
{
    $map = [
        'name' => 'name ASC',
        'name_desc' => 'name DESC',
        'registration' => 'registration_number ASC',
        'industry' => 'industry ASC',
        'status' => 'status ASC',
        'region' => 'address ASC',
        'id' => 'id ASC',
        'id_desc' => 'id DESC',
    ];
    return $map[$sort] ?? $map['name'];
}

/**
 * Owners report filters against tbl_client (+ optional owners).
 *
 * @return array{where:string,params:array}
 */
function eca_ci_owners_filters(array $filters): array
{
    $where = ['1=1'];
    $params = [];

    $search = trim((string) ($filters['search'] ?? ''));
    if ($search !== '') {
        $where[] = '(c.TradingName LIKE ? OR c.CompanyRegistrationName LIKE ? OR c.MembershipNumber LIKE ?
                     OR c.EmailAddress LIKE ? OR c.Region LIKE ? OR c.Clasification LIKE ?
                     OR o.name LIKE ?)';
        $like = '%' . $search . '%';
        array_push($params, $like, $like, $like, $like, $like, $like, $like);
    }

    $standing = trim((string) ($filters['standing'] ?? ''));
    if ($standing !== '' && function_exists('eca_member_standing_values') && in_array($standing, eca_member_standing_values(), true)) {
        $where[] = 'c.active = ?';
        $params[] = $standing;
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

    $ownerPresence = trim((string) ($filters['owner_presence'] ?? ''));
    if ($ownerPresence === 'yes') {
        $where[] = 'o.id IS NOT NULL';
    } elseif ($ownerPresence === 'no') {
        $where[] = 'o.id IS NULL';
    }

    $year = trim((string) ($filters['year'] ?? ''));
    if ($year !== '' && preg_match('/^\d{4}$/', $year)) {
        $where[] = 'EXISTS (SELECT 1 FROM membership_years y WHERE y.client_id = c.client_id AND y.year = ?)';
        $params[] = $year;
    }

    return ['where' => implode(' AND ', $where), 'params' => $params];
}

function eca_ci_owners_sort(string $sort): string
{
    $map = [
        'company' => 'COALESCE(c.TradingName, c.CompanyRegistrationName) ASC, o.name ASC',
        'company_desc' => 'COALESCE(c.TradingName, c.CompanyRegistrationName) DESC, o.name ASC',
        'owner' => 'o.name ASC',
        'membership' => 'c.MembershipNumber ASC',
        'region' => 'c.Region ASC',
        'classification' => 'c.Clasification ASC',
        'standing' => 'c.active ASC',
    ];
    return $map[$sort] ?? $map['company'];
}

function eca_ci_load_company(?PDO $local, int $id): ?array
{
    if (!$local || $id < 1) {
        return null;
    }
    try {
        $stmt = $local->prepare('SELECT * FROM companies WHERE id = ? LIMIT 1');
        $stmt->execute([$id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    } catch (Throwable $e) {
        return null;
    }
}

/**
 * @return array{years:array,certs:array,apps:array,docs:int,owners:array}
 */
function eca_ci_member_bundle(?PDO $portal, int $clientId): array
{
    $out = ['years' => [], 'certs' => [], 'apps' => [], 'docs' => 0, 'owners' => []];
    if (!$portal || $clientId < 1) {
        return $out;
    }
    try {
        $stmt = $portal->prepare('SELECT * FROM membership_years WHERE client_id = ? ORDER BY id DESC LIMIT 20');
        $stmt->execute([$clientId]);
        $out['years'] = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    } catch (Throwable $e) {
        $out['years'] = [];
    }
    try {
        $stmt = $portal->prepare(
            'SELECT id, certificate_number, status, issued_at, expiry_date, membership_number, classification, company_name
             FROM membership_certificates WHERE client_id = ? ORDER BY id DESC LIMIT 20'
        );
        $stmt->execute([$clientId]);
        $out['certs'] = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    } catch (Throwable $e) {
        $out['certs'] = [];
    }
    try {
        $stmt = $portal->prepare(
            'SELECT client_id, application_reference, application_status, Status, active, DateOfRegistration, created_at
             FROM tbl_client WHERE client_id = ? LIMIT 1'
        );
        $stmt->execute([$clientId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($row && trim((string) ($row['application_reference'] ?? '')) !== '') {
            $out['apps'] = [$row];
        }
    } catch (Throwable $e) {
        $out['apps'] = [];
    }
    try {
        $stmt = $portal->prepare('SELECT COUNT(*) FROM tbl_client_documents WHERE client_id = ?');
        $stmt->execute([$clientId]);
        $out['docs'] = (int) $stmt->fetchColumn();
    } catch (Throwable $e) {
        $out['docs'] = 0;
    }
    try {
        $stmt = $portal->prepare(
            'SELECT id, name, citizen, gender, shares, application_id, clientid
             FROM owners WHERE clientid = ? OR application_id = ? ORDER BY id ASC'
        );
        $stmt->execute([$clientId, $clientId]);
        $out['owners'] = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    } catch (Throwable $e) {
        $out['owners'] = [];
    }
    return $out;
}
