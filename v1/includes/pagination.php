<?php

/** Default rows per page for hub dashboards (override with ?per_page=6|10|20|50). */
function eca_pager_default_limit(): int
{
    return 6;
}

function eca_pager_allowed_limits(): array
{
    return [6, 10, 20, 50];
}

function eca_pager_page(string $key = 'page'): int
{
    if (!isset($_GET[$key]) || !is_numeric($_GET[$key])) {
        return 1;
    }
    return max(1, (int) $_GET[$key]);
}

/** Dashboard list feeds: fixed 10/page (independent of hub ?per_page=). */
function eca_dashboard_feed_limit(): int
{
    $requested = (int) ($_GET['dash_per_page'] ?? 10);
    return in_array($requested, [10, 20, 50], true) ? $requested : 10;
}

function eca_empty_paged_result(string $pageKey = 'page', int $limit = 10): array
{
    return [
        'rows' => [],
        'total' => 0,
        'page' => 1,
        'pages' => 1,
        'limit' => max(1, min(50, $limit)),
        'offset' => 0,
        'page_key' => $pageKey,
    ];
}

/**
 * Clamp page into range without HTTP redirect (safe when multiple sections
 * each have their own *_page query parameter on one URL).
 */
function eca_pager_clamp_page(int $page, int $totalPages, int $total): int
{
    if ($total === 0) {
        return 1;
    }
    return max(1, min(max(1, $page), max(1, $totalPages)));
}

function eca_pager_limit(?int $fallback = null): int
{
    $fallback = $fallback ?? eca_pager_default_limit();
    $requested = (int) ($_GET['per_page'] ?? $fallback);
    return in_array($requested, eca_pager_allowed_limits(), true) ? $requested : $fallback;
}

function eca_pager_page_list(int $current, int $total): array
{
    if ($total <= 7) {
        $pages = [];
        for ($i = 1; $i <= $total; $i++) {
            $pages[] = $i;
        }
        return $pages;
    }
    $pages = [1];
    $start = max(2, $current - 1);
    $end = min($total - 1, $current + 1);
    if ($start > 2) {
        $pages[] = '…';
    }
    for ($i = $start; $i <= $end; $i++) {
        $pages[] = $i;
    }
    if ($end < $total - 1) {
        $pages[] = '…';
    }
    $pages[] = $total;
    return $pages;
}

function eca_pager_redirect_if_out_of_range(int $page, int $totalPages, int $total, string $pageKey = 'page'): int
{
    if ($total === 0) {
        return 1;
    }
    if ($page <= $totalPages) {
        return max(1, $page);
    }
    $path = (string) (parse_url($_SERVER['REQUEST_URI'] ?? '', PHP_URL_PATH) ?: '');
    $query = $_GET;
    if ($totalPages > 1) {
        $query[$pageKey] = $totalPages;
    } else {
        unset($query[$pageKey]);
    }
    $qs = http_build_query($query);
    header('Location: ' . $path . ($qs === '' ? '' : '?' . $qs), true, 302);
    exit;
}

function eca_paged_query(\PDO $conn, string $countSql, string $selectSql, array $params = [], ?int $page = null, ?int $limit = null): array
{
    $limit = max(1, $limit ?? eca_pager_limit());
    $page = max(1, $page ?? eca_pager_page());
    $countStmt = $conn->prepare($countSql);
    $countStmt->execute($params);
    $total = (int) $countStmt->fetchColumn();
    $pages = $total > 0 ? max(1, (int) ceil($total / $limit)) : 1;
    $page = eca_pager_redirect_if_out_of_range($page, $pages, $total);
    $offset = ($page - 1) * $limit;
    $stmt = $conn->prepare($selectSql . ' LIMIT ' . (int) $limit . ' OFFSET ' . (int) $offset);
    $stmt->execute($params);
    return [
        'rows' => $stmt->fetchAll(PDO::FETCH_ASSOC),
        'total' => $total,
        'page' => $page,
        'pages' => $pages,
        'limit' => $limit,
        'offset' => $offset,
    ];
}

function eca_mysqli_bind_params(mysqli_stmt $stmt, string $types, array $params): void
{
    if ($types === '' || $params === []) {
        return;
    }
    $refs = [$types];
    foreach ($params as $key => &$value) {
        $refs[] = &$value;
    }
    unset($value);
    call_user_func_array([$stmt, 'bind_param'], $refs);
}

function eca_paged_query_mysqli(
    mysqli $conn,
    string $countSql,
    string $selectSql,
    string $types,
    array $params = [],
    ?int $page = null,
    ?int $limit = null
): array {
    $limit = max(1, $limit ?? eca_pager_limit());
    $page = max(1, $page ?? eca_pager_page());
    $countStmt = $conn->prepare($countSql);
    if (!$countStmt) {
        return ['rows' => [], 'total' => 0, 'page' => 1, 'pages' => 1, 'limit' => $limit, 'offset' => 0];
    }
    eca_mysqli_bind_params($countStmt, $types, $params);
    $countStmt->execute();
    $countRes = $countStmt->get_result();
    $total = $countRes ? (int) ($countRes->fetch_row()[0] ?? 0) : 0;
    $countStmt->close();
    $pages = $total > 0 ? max(1, (int) ceil($total / $limit)) : 1;
    $page = eca_pager_redirect_if_out_of_range($page, $pages, $total);
    $offset = ($page - 1) * $limit;
    $stmt = $conn->prepare($selectSql . ' LIMIT ' . (int) $limit . ' OFFSET ' . (int) $offset);
    if (!$stmt) {
        return ['rows' => [], 'total' => $total, 'page' => $page, 'pages' => $pages, 'limit' => $limit, 'offset' => $offset];
    }
    eca_mysqli_bind_params($stmt, $types, $params);
    $stmt->execute();
    $result = $stmt->get_result();
    $rows = [];
    if ($result) {
        while ($row = $result->fetch_assoc()) {
            $rows[] = $row;
        }
    }
    $stmt->close();
    return [
        'rows' => $rows,
        'total' => $total,
        'page' => $page,
        'pages' => $pages,
        'limit' => $limit,
        'offset' => $offset,
    ];
}

/**
 * Named-key server-side page (COUNT + LIMIT/OFFSET). Clamps out-of-range pages;
 * does not redirect so peer section page params stay intact.
 */
function eca_paged_query_named(
    \PDO $conn,
    string $countSql,
    string $selectSql,
    array $params = [],
    string $pageKey = 'page',
    ?int $limit = null
): array {
    $limit = max(1, min(50, $limit ?? eca_dashboard_feed_limit()));
    $page = eca_pager_page($pageKey);
    $countStmt = $conn->prepare($countSql);
    $countStmt->execute($params);
    $total = (int) $countStmt->fetchColumn();
    $pages = $total > 0 ? max(1, (int) ceil($total / $limit)) : 1;
    $page = eca_pager_clamp_page($page, $pages, $total);
    $offset = ($page - 1) * $limit;
    $stmt = $conn->prepare($selectSql . ' LIMIT ' . (int) $limit . ' OFFSET ' . (int) $offset);
    $stmt->execute($params);
    return [
        'rows' => $stmt->fetchAll(\PDO::FETCH_ASSOC),
        'total' => $total,
        'page' => $page,
        'pages' => $pages,
        'limit' => $limit,
        'offset' => $offset,
        'page_key' => $pageKey,
    ];
}

function eca_render_request_pager(int $page, int $totalPages, int $total = 0, ?int $perPage = null, string $pageKey = 'page'): void
{
    $perPage = $perPage ?? eca_pager_default_limit();
    $path = (string) (parse_url($_SERVER['REQUEST_URI'] ?? '', PHP_URL_PATH) ?: '');
    $keep = $_GET;
    unset($keep[$pageKey]);
    eca_render_pager($page, $totalPages, static function (int $i) use ($path, $keep, $pageKey): string {
        $query = $keep;
        if ($i > 1) {
            $query[$pageKey] = $i;
        } else {
            unset($query[$pageKey]);
        }
        $qs = http_build_query($query);
        return $qs === '' ? $path : $path . '?' . $qs;
    }, $total, $perPage);
}

function eca_render_named_request_pager(array $paged): void
{
    $page = (int) ($paged['page'] ?? 1);
    $pages = (int) ($paged['pages'] ?? 1);
    $total = (int) ($paged['total'] ?? 0);
    $limit = (int) ($paged['limit'] ?? eca_dashboard_feed_limit());
    $key = (string) ($paged['page_key'] ?? 'page');
    if ($key === '') {
        $key = 'page';
    }
    eca_render_request_pager($page, $pages, $total, $limit, $key);
}

function eca_render_pager(int $page, int $totalPages, callable $hrefForPage, int $total = 0, ?int $perPage = null): void
{
    $perPage = $perPage ?? eca_pager_default_limit();
    if ($totalPages < 2) {
        return;
    }
    $page = max(1, min($page, $totalPages));
    $start = $total === 0 ? 0 : (($page - 1) * $perPage) + 1;
    $end = $total === 0 ? 0 : min($total, $page * $perPage);
    $buttons = eca_pager_page_list($page, $totalPages);
    ?>
<nav class="dash-pager" aria-label="Table pages">
    <div class="dash-pager-bar">
        <?php if ($total > 0): ?>
            <p class="dash-pager-meta">Showing <?= (int) $start ?>–<?= (int) $end ?> of <?= (int) $total ?></p>
        <?php else: ?>
            <p class="dash-pager-meta">Page <?= (int) $page ?> of <?= (int) $totalPages ?></p>
        <?php endif; ?>
        <div class="dash-pager-buttons">
            <?php if ($page > 1): ?>
                <a class="dash-pager-btn" href="<?= htmlspecialchars((string) $hrefForPage($page - 1), ENT_QUOTES, 'UTF-8') ?>">Previous</a>
            <?php else: ?>
                <span class="dash-pager-btn" aria-disabled="true">Previous</span>
            <?php endif; ?>
            <?php foreach ($buttons as $item): ?>
                <?php if ($item === '…'): ?>
                    <span class="dash-pager-ellipsis">…</span>
                <?php elseif ((int) $item === $page): ?>
                    <span class="dash-pager-btn is-active" aria-current="page"><?= (int) $item ?></span>
                <?php else: ?>
                    <a class="dash-pager-btn" href="<?= htmlspecialchars((string) $hrefForPage((int) $item), ENT_QUOTES, 'UTF-8') ?>"><?= (int) $item ?></a>
                <?php endif; ?>
            <?php endforeach; ?>
            <?php if ($page < $totalPages): ?>
                <a class="dash-pager-btn" href="<?= htmlspecialchars((string) $hrefForPage($totalPages), ENT_QUOTES, 'UTF-8') ?>">Last</a>
            <?php else: ?>
                <span class="dash-pager-btn" aria-disabled="true">Last</span>
            <?php endif; ?>
            <?php if ($page < $totalPages): ?>
                <a class="dash-pager-btn" href="<?= htmlspecialchars((string) $hrefForPage($page + 1), ENT_QUOTES, 'UTF-8') ?>">Next</a>
            <?php else: ?>
                <span class="dash-pager-btn" aria-disabled="true">Next</span>
            <?php endif; ?>
        </div>
    </div>
</nav>
    <?php
}
