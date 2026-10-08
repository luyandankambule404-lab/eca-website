<?php

function eca_next_ticket_reference(PDO $conn): string
{
    $prefix = 'ECA-TKT-';
    $next = 1;
    try {
        $stmt = $conn->query("SELECT ticket_reference FROM contact_messages WHERE ticket_reference LIKE 'ECA-TKT-%' ORDER BY ticket_reference DESC LIMIT 1");
        $last = (string) $stmt->fetchColumn();
        if ($last !== '' && preg_match('/-(\d+)$/', $last, $m)) {
            $next = ((int) $m[1]) + 1;
        }
    } catch (Throwable $e) {
        $next = 1;
    }
    return $prefix . str_pad((string) $next, 6, '0', STR_PAD_LEFT);
}

function eca_content_statuses(): array
{
    return ['DRAFT', 'PUBLISHED', 'CLOSED', 'ARCHIVED'];
}

function eca_news_public_status_sql(string $column = 'status'): string
{
    return '(' . $column . " IS NULL OR " . $column . " IN ('Active','Published','active','published'))";
}

function eca_resource_is_publicly_downloadable(?PDO $conn, string $file): bool
{
    $file = trim($file);
    $base = basename(str_replace(['\\', '..'], ['/', ''], $file));
    if ($file === '' || $base === '' || !$conn) {
        return true;
    }
    try {
        $stmt = $conn->prepare(
            'SELECT status FROM resources
             WHERE file_path = ? OR file_path = ? OR file_path LIKE ?
             LIMIT 1'
        );
        $stmt->execute([$file, $base, '%/' . $base]);
        $status = $stmt->fetchColumn();
        if ($status === false) {
            return true;
        }
        $st = strtolower(trim((string) $status));
        return $st === '' || in_array($st, ['published', 'active'], true);
    } catch (Throwable $e) {
        return true;
    }
}
