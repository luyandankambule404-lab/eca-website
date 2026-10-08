<?php

require_once __DIR__ . '/membership.php';

function eca_has_table(PDO $conn, string $table): bool
{
    static $cache = [];
    if (array_key_exists($table, $cache)) {
        return $cache[$table];
    }
    try {
        $stmt = $conn->prepare(
            'SELECT 1 FROM information_schema.tables WHERE table_schema = DATABASE() AND table_name = ? LIMIT 1'
        );
        $stmt->execute([$table]);
        $cache[$table] = (bool) $stmt->fetchColumn();
    } catch (Throwable $e) {
        $cache[$table] = false;
    }
    return $cache[$table];
}

function eca_has_column(PDO $conn, string $table, string $column): bool
{
    static $cache = [];
    $key = $table . '.' . $column;
    if (array_key_exists($key, $cache)) {
        return $cache[$key];
    }
    try {
        $stmt = $conn->prepare(
            'SELECT 1 FROM information_schema.columns
             WHERE table_schema = DATABASE() AND table_name = ? AND column_name = ? LIMIT 1'
        );
        $stmt->execute([$table, $column]);
        $cache[$key] = (bool) $stmt->fetchColumn();
    } catch (Throwable $e) {
        $cache[$key] = false;
    }
    return $cache[$key];
}

function eca_table_count(PDO $conn, string $table): int
{
    static $cache = [];
    if (array_key_exists($table, $cache)) {
        return $cache[$table];
    }
    if (!eca_has_table($conn, $table)) {
        return 0;
    }
    try {
        $cache[$table] = (int) $conn->query('SELECT COUNT(*) FROM `' . str_replace('`', '', $table) . '`')->fetchColumn();
        return $cache[$table];
    } catch (Throwable $e) {
        $cache[$table] = 0;
        return 0;
    }
}

function eca_member_directory_company(PDO $conn, array $member): ?array
{
    $membership = trim((string) ($member['membership'] ?? ''));
    $email = trim((string) ($member['email'] ?? ''));
    try {
        if ($membership !== '') {
            $stmt = $conn->prepare('SELECT id, name, registration_number, email, phone, address, industry, status, website, description FROM companies WHERE registration_number = ? LIMIT 1');
            $stmt->execute([$membership]);
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            if ($row) {
                return $row;
            }
        }
        if ($email !== '') {
            $stmt = $conn->prepare('SELECT id, name, registration_number, email, phone, address, industry, status, website, description FROM companies WHERE email = ? LIMIT 1');
            $stmt->execute([$email]);
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            if ($row) {
                return $row;
            }
        }
    } catch (Throwable $e) {
        return null;
    }
    return null;
}

function eca_company_select_sql(string $table): string
{
    $table = str_replace('`', '', $table);
    return "SELECT
        id,
        name AS TradingName,
        name AS CompanyRegistrationName,
        registration_number AS MembershipNumber,
        email AS EmailAddress,
        phone AS Cellphone,
        CASE
            WHEN LOWER(COALESCE(address, '')) IN ('hhohho','manzini','lubombo','shiselweni') THEN address
            WHEN LOWER(COALESCE(industry, '')) IN ('hhohho','manzini','lubombo','shiselweni') THEN industry
            ELSE COALESCE(NULLIF(address, ''), industry)
        END AS Region,
        TRIM(CASE
            WHEN LOWER(COALESCE(industry, '')) IN ('building','builiding','civil','electrical','mechanical','specialist') THEN industry
            WHEN LOWER(COALESCE(address, '')) IN ('building','builiding','civil','electrical','mechanical','specialist') THEN address
            ELSE COALESCE(NULLIF(industry, ''), address)
        END) AS Clasification,
        status AS Status,
        website AS website,
        description AS description
    FROM `{$table}`";
}

function eca_directory_base_sql(PDO $conn, string $mode = 'members'): array
{
    $clientPk = eca_has_column($conn, 'tbl_client', 'client_id') ? 'client_id' : 'id';
    $memberColumns = 'SELECT
        ' . $clientPk . ' AS id,
        TradingName,
        CompanyRegistrationName,
        MembershipNumber,
        EmailAddress,
        Cellphone,
        Region,
        Clasification,
        Status,
        NULL AS website,
        NULL AS description
        FROM tbl_client';

    if ($mode === 'balingani') {
        if (eca_has_table($conn, 'companies1') && eca_table_count($conn, 'companies1') > 0) {
            return ['sql' => eca_company_select_sql('companies1'), 'params' => []];
        }
        if (eca_has_table($conn, 'tbl_client')) {
            return ['sql' => $memberColumns . ' WHERE Enterprise = :enterprise', 'params' => [':enterprise' => 'Female']];
        }
        return ['sql' => '', 'params' => []];
    }

    if (eca_has_table($conn, 'companies') && eca_table_count($conn, 'companies') > 0) {
        return ['sql' => eca_company_select_sql('companies'), 'params' => []];
    }
    if (eca_has_table($conn, 'tbl_client')) {
        return ['sql' => $memberColumns, 'params' => []];
    }
    return ['sql' => '', 'params' => []];
}

function eca_directory_industries(): array
{
    return array_values(eca_specialisation_options());
}

function eca_request_industry(): string
{
    foreach (['industry', 'Clasification', 'classification', 'type'] as $key) {
        $value = trim((string) ($_GET[$key] ?? ''));
        if ($value !== '' && !in_array(strtolower($value), ['all', 'all industries', '*'], true)) {
            return $value;
        }
    }
    return '';
}

function eca_industry_tokens(string $industry): array
{
    $key = strtolower(trim(preg_replace('/\s+/', ' ', $industry) ?? $industry));
    $key = str_replace(['mechenical', 'mecanical'], 'mechanical', $key);
    if ($key === '' || in_array($key, ['all', 'all industries', '*'], true)) {
        return [];
    }
    $electricalTokens = ['electrical/mechanical', 'electrical', 'electric', 'mechanical'];
    $map = [
        'building' => ['building', 'builiding'],
        'builiding' => ['building', 'builiding'],
        'civil' => ['civil'],
        'electrical' => $electricalTokens,
        'electric' => $electricalTokens,
        'mechanical' => $electricalTokens,
        'electrical/mechanical' => $electricalTokens,
        'electrical / mechanical' => $electricalTokens,
        'specialist' => ['specialist'],
    ];
    return $map[$key] ?? [$key];
}

function eca_industry_filter_values(string $industry): array
{
    return eca_industry_tokens($industry);
}

function eca_type_filter_url(string $base, string $search = '', string $industry = '', int $page = 1): string
{
    $query = [];
    if ($search !== '') {
        $query['search'] = $search;
    }
    if ($industry !== '' && !in_array(strtolower($industry), ['all', 'all industries', '*'], true)) {
        $query['industry'] = $industry;
    }
    if ($page > 1) {
        $query['page'] = (string) $page;
    }
    return $base . ($query ? ('?' . http_build_query($query)) : '');
}

if (!function_exists('eca_h')) {
    function eca_h($value): string
    {
        return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
    }
}

function eca_directory_type_counts(PDO $conn, string $mode = 'members'): array
{
    $types = eca_directory_industries();
    $counts = array_fill_keys($types, 0);
    $base = eca_directory_base_sql($conn, $mode);
    if ($base['sql'] === '') {
        return $counts;
    }

    try {
        $stmt = $conn->prepare(
            'SELECT Clasification, COUNT(*) AS total FROM (' . $base['sql'] . ') AS directory_rows GROUP BY Clasification'
        );
        $stmt->execute($base['params']);
        foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
            $key = strtolower((string) ($row['Clasification'] ?? ''));
            foreach ($types as $type) {
                foreach (eca_industry_tokens($type) as $token) {
                    if ($token !== '' && str_contains($key, $token)) {
                        $counts[$type] += (int) $row['total'];
                        break;
                    }
                }
            }
        }
    } catch (Throwable $e) {
        return $counts;
    }

    return $counts;
}

function eca_fetch_directory(PDO $conn, string $search, string $industry, int $limit, int $offset, string $mode = 'members', bool $prefixFirst = false): array
{
    $search = mb_substr(trim($search), 0, 80);
    $base = eca_directory_base_sql($conn, $mode);
    if ($base['sql'] === '') {
        return ['rows' => [], 'total' => 0];
    }
    $limit = max(1, min($limit, 500));
    $offset = max(0, $offset);

    $sql = 'SELECT * FROM (' . $base['sql'] . ') AS directory_rows WHERE 1=1';
    $params = $base['params'];

    if ($search !== '') {
        $like = '%' . $search . '%';
        $fields = [
            'TradingName',
            'CompanyRegistrationName',
            'MembershipNumber',
        ];
        if (mb_strlen($search) > 1) {
            $fields[] = 'Region';
        }
        if (mb_strlen($search) > 2) {
            $fields = array_merge($fields, [
                'Clasification',
                'EmailAddress',
                'Cellphone',
                'Status',
            ]);
        }
        $ors = [];
        foreach ($fields as $i => $field) {
            $ph = ':search' . $i;
            $ors[] = $field . ' LIKE ' . $ph;
            $params[$ph] = $like;
        }
        if (ctype_digit($search)) {
            $ors[] = 'id = :search_id_eq';
            $params[':search_id_eq'] = (int) $search;
        }
        $sql .= ' AND (' . implode(' OR ', $ors) . ')';
    }
    if ($industry !== '') {
        $values = eca_industry_tokens($industry);
        $ors = [];
        foreach ($values as $i => $val) {
            $ph = ':ind' . $i;
            $ors[] = 'LOWER(Clasification) LIKE ' . $ph;
            $params[$ph] = '%' . $val . '%';
        }
        if ($ors) {
            $sql .= ' AND (' . implode(' OR ', $ors) . ')';
        }
    }

    $stmt = $conn->prepare('SELECT COUNT(*) FROM (' . $sql . ') AS directory_count');
    $stmt->execute($params);
    $total = (int) $stmt->fetchColumn();

    $order = ' ORDER BY TradingName ASC';
    if ($prefixFirst && $search !== '') {
        $order = ' ORDER BY CASE WHEN TradingName LIKE :name_prefix THEN 0 ELSE 1 END, TradingName ASC';
        $params[':name_prefix'] = $search . '%';
    }

    $pageSql = $sql . $order . ' LIMIT :limit OFFSET :offset';
    $stmt = $conn->prepare($pageSql);
    foreach ($params as $key => $val) {
        $stmt->bindValue($key, $val);
    }
    $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
    $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
    $stmt->execute();

    return [
        'rows' => $stmt->fetchAll(PDO::FETCH_ASSOC),
        'total' => $total,
    ];
}
