<?php

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

function eca_table_count(PDO $conn, string $table): int
{
    if (!eca_has_table($conn, $table)) {
        return 0;
    }
    try {
        return (int) $conn->query('SELECT COUNT(*) FROM `' . str_replace('`', '', $table) . '`')->fetchColumn();
    } catch (Throwable $e) {
        return 0;
    }
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
        status AS Status
    FROM `{$table}`";
}

function eca_directory_base_sql(PDO $conn, string $mode = 'members'): array
{
    if ($mode === 'balingani') {
        if (eca_has_table($conn, 'companies1') && eca_table_count($conn, 'companies1') > 0) {
            return ['sql' => eca_company_select_sql('companies1'), 'params' => []];
        }
        if (eca_has_table($conn, 'tbl_client')) {
            return ['sql' => 'SELECT * FROM tbl_client WHERE Enterprise = :enterprise', 'params' => [':enterprise' => 'Female']];
        }
        return ['sql' => '', 'params' => []];
    }

    if (eca_has_table($conn, 'companies') && eca_table_count($conn, 'companies') > 0) {
        return ['sql' => eca_company_select_sql('companies'), 'params' => []];
    }
    if (eca_has_table($conn, 'tbl_client')) {
        return ['sql' => 'SELECT * FROM tbl_client', 'params' => []];
    }
    return ['sql' => '', 'params' => []];
}

function eca_directory_industries(): array
{
    return ['Building', 'Civil', 'Electrical', 'Mechanical', 'Specialist'];
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

function eca_industry_filter_values(string $industry): array
{
    $key = strtolower(trim($industry));
    if ($key === '' || in_array($key, ['all', 'all industries', '*'], true)) {
        return [];
    }
    if ($key === 'building' || $key === 'builiding') {
        return ['building', 'builiding'];
    }
    return [$key];
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
            if ($key === 'builiding') {
                $key = 'building';
            }
            foreach ($types as $type) {
                if (strtolower($type) === $key) {
                    $counts[$type] += (int) $row['total'];
                    break;
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
    $base = eca_directory_base_sql($conn, $mode);
    if ($base['sql'] === '') {
        return ['rows' => [], 'total' => 0];
    }

    $sql = 'SELECT * FROM (' . $base['sql'] . ') AS directory_rows WHERE 1=1';
    $params = $base['params'];

    if ($search !== '') {
        $like = '%' . $search . '%';
        $fields = [
            'TradingName',
            'CompanyRegistrationName',
            'MembershipNumber',
            'Clasification',
            'Region',
            'EmailAddress',
            'Cellphone',
            'Status',
        ];
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
        $values = eca_industry_filter_values($industry);
        $placeholders = [];
        foreach ($values as $i => $val) {
            $ph = ':ind' . $i;
            $placeholders[] = $ph;
            $params[$ph] = $val;
        }
        if ($placeholders) {
            $sql .= ' AND LOWER(Clasification) IN (' . implode(',', $placeholders) . ')';
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
