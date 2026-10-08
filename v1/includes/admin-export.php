<?php

require_once __DIR__ . '/audit.php';

function eca_admin_export_limit(): int
{
    return 5000;
}

function eca_admin_export_requested(): bool
{
    return strtolower(trim((string) ($_GET['export'] ?? ''))) === 'csv';
}

function eca_admin_export_query(array $extra = []): array
{
    $query = $_GET;
    unset($query['export'], $query['page'], $query['per_page']);
    foreach ($extra as $key => $value) {
        if ($value === '' || $value === null) {
            unset($query[$key]);
            continue;
        }
        $query[$key] = $value;
    }
    return array_filter(
        $query,
        static fn ($value) => $value !== '' && $value !== null
    );
}

function eca_admin_export_href(array $extra = []): string
{
    $path = (string) (parse_url((string) ($_SERVER['REQUEST_URI'] ?? ''), PHP_URL_PATH) ?: '');
    $query = eca_admin_export_query($extra);
    $query['export'] = 'csv';
    return $path . '?' . http_build_query($query);
}

function eca_admin_csv_button(string $label = 'Download CSV'): string
{
    if (!function_exists('eca_can') || !eca_can('reports.export')) {
        return '';
    }
    $href = eca_admin_export_href();
    return '<a class="hub-btn" href="' . htmlspecialchars($href, ENT_QUOTES, 'UTF-8') . '">' . htmlspecialchars($label, ENT_QUOTES, 'UTF-8') . '</a>';
}

function eca_admin_export_rows(PDO $conn, string $sql, array $params = [], int $limit = 0): array
{
    $limit = $limit > 0 ? $limit : eca_admin_export_limit();
    $trimmed = rtrim($sql);
    if (!preg_match('/\blimit\s+\d+/i', $trimmed)) {
        $sql = $trimmed . ' LIMIT ' . $limit;
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

function eca_admin_send_csv(string $basename, array $headers, iterable $rows, string $entity = 'export'): void
{
    $safeBase = preg_replace('/[^a-z0-9._-]+/i', '-', $basename) ?: 'eca-export';
    eca_audit('report.export', $entity, $safeBase, [
        'format' => 'csv',
        'filename' => $safeBase . '-' . date('Ymd') . '.csv',
    ]);
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="' . $safeBase . '-' . date('Ymd') . '.csv"');
    header('Cache-Control: no-store');
    $out = fopen('php://output', 'w');
    fputcsv($out, $headers);
    $count = 0;
    $max = eca_admin_export_limit();
    foreach ($rows as $row) {
        fputcsv($out, $row);
        if (++$count >= $max) {
            break;
        }
    }
    fclose($out);
    exit;
}

function eca_admin_require_csv_export(): void
{
    if (!function_exists('eca_can') || !eca_can('reports.export')) {
        if (function_exists('eca_forbid')) {
            eca_forbid('You cannot export this list.');
        }
        http_response_code(403);
        echo 'Forbidden';
        exit;
    }
}
