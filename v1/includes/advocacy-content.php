<?php
/**
 * Advocacy updates — presentation helpers using existing news table.
 * No schema changes. Category marker: "Advocacy" in news.categories.
 */

require_once __DIR__ . '/demo-content.php';

/**
 * Allowed advocacy category tokens (case-insensitive match against news.categories).
 */
function eca_advocacy_news_category_tokens(): array
{
    return ['advocacy', 'advocacy updates', 'policy & advocacy', 'policy and advocacy'];
}

function eca_news_is_advocacy_category(?string $categories): bool
{
    $categories = strtolower(trim((string) $categories));
    if ($categories === '') {
        return false;
    }
    foreach (eca_advocacy_news_category_tokens() as $token) {
        if ($categories === $token || str_contains($categories, $token)) {
            return true;
        }
    }
    return false;
}

/**
 * @return list<array<string,mixed>>
 */
function eca_fetch_advocacy_updates(?PDO $conn, int $limit = 12): array
{
    if (!$conn) {
        return [];
    }
    $limit = max(1, min(50, $limit));
    try {
        $stmt = $conn->query(
            "SELECT id, title, summary, author, categories, link, date, status
             FROM news
             WHERE (status IS NULL OR status IN ('Active','Published','active','published'))
             ORDER BY date DESC, id DESC
             LIMIT 80"
        );
        $rows = $stmt ? $stmt->fetchAll(PDO::FETCH_ASSOC) : [];
    } catch (Throwable $e) {
        return [];
    }

    $out = [];
    foreach ($rows as $row) {
        $title = (string) ($row['title'] ?? '');
        $summary = (string) ($row['summary'] ?? '');
        if (eca_is_local_demo_news($title, $summary)) {
            continue;
        }
        if (!eca_news_is_advocacy_category($row['categories'] ?? null)) {
            continue;
        }
        $out[] = $row;
        if (count($out) >= $limit) {
            break;
        }
    }
    return $out;
}

function eca_advocacy_update_href(array $row): string
{
    $link = trim((string) ($row['link'] ?? ''));
    if ($link === '' || $link === '#') {
        $id = (int) ($row['id'] ?? 0);
        return $id > 0 ? '/news-details.php?id=' . $id : '/news.php';
    }
    if (preg_match('~^https?://~i', $link)) {
        return $link;
    }
    return '/' . ltrim($link, '/');
}

/**
 * Render advocacy updates list or professional empty state.
 */
function eca_render_advocacy_updates(array $items): void
{
    if (!$items) {
        echo '<div class="adv-empty org-source-required" role="status">';
        echo '<strong>No verified advocacy updates are published yet.</strong> ';
        echo 'When ECA Communications publishes news items categorised as <em>Advocacy</em>, they will appear here. ';
        echo 'This space does not show invented campaigns or unverified outcomes.';
        echo '</div>';
        echo '<p class="org-note">Management note: publish via Admin → News with category containing “Advocacy”. Demo/local seed articles are excluded.</p>';
        return;
    }

    echo '<div class="adv-updates-list">';
    foreach ($items as $row) {
        $title = eca_local_demo_news_display_title((string) ($row['title'] ?? 'Advocacy update'));
        $summary = trim(strip_tags((string) ($row['summary'] ?? '')));
        if (strlen($summary) > 220) {
            $summary = substr($summary, 0, 217) . '…';
        }
        $dateRaw = (string) ($row['date'] ?? '');
        $dateLabel = $dateRaw !== '' ? date('d M Y', strtotime($dateRaw)) : '';
        $category = trim((string) ($row['categories'] ?? 'Advocacy'));
        $href = eca_advocacy_update_href($row);
        $author = trim((string) ($row['author'] ?? ''));

        echo '<article class="adv-update-card">';
        echo '<p class="adv-update-meta">';
        if ($dateLabel !== '') {
            echo '<time datetime="' . htmlspecialchars($dateRaw, ENT_QUOTES, 'UTF-8') . '">' . htmlspecialchars($dateLabel, ENT_QUOTES, 'UTF-8') . '</time>';
        }
        echo '<span class="adv-update-cat">' . htmlspecialchars($category, ENT_QUOTES, 'UTF-8') . '</span>';
        echo '</p>';
        echo '<h3><a href="' . htmlspecialchars($href, ENT_QUOTES, 'UTF-8') . '">' . htmlspecialchars($title, ENT_QUOTES, 'UTF-8') . '</a></h3>';
        if ($summary !== '') {
            echo '<p>' . htmlspecialchars($summary, ENT_QUOTES, 'UTF-8') . '</p>';
        }
        if ($author !== '') {
            echo '<p class="adv-update-source">Source / publisher: ' . htmlspecialchars($author, ENT_QUOTES, 'UTF-8') . '</p>';
        }
        echo '</article>';
    }
    echo '</div>';
}
