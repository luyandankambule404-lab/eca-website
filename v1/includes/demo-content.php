<?php
/**
 * Local demonstration content helpers — presentation layer only.
 * No schema changes. Labels known local seed content so it cannot be mistaken
 * for official live ECA announcements.
 */

/**
 * Titles/summaries seeded by local-db/setup-local-db.php for local QA.
 * Match on exact known titles or obvious local-demo markers.
 */
function eca_local_demo_news_titles(): array
{
    return [
        'Local ECA site is connected to MySQL',
        'CPD training window is open',
        '[LOCAL DEMO] Local ECA site is connected to MySQL',
        '[LOCAL DEMO] CPD training window is open',
    ];
}

function eca_is_local_demo_news(?string $title, ?string $summary = null): bool
{
    $title = trim((string) $title);
    $summary = trim((string) $summary);

    if ($title !== '' && str_starts_with(strtoupper($title), '[LOCAL DEMO]')) {
        return true;
    }

    foreach (eca_local_demo_news_titles() as $known) {
        if (strcasecmp($title, $known) === 0) {
            return true;
        }
    }

    $hay = strtolower($title . ' ' . $summary);
    if ($hay === '') {
        return false;
    }

    $markers = [
        'test article confirms the local database',
        'sample open course is seeded',
        'tested locally',
        'local demonstration',
        'sample content for local',
    ];
    foreach ($markers as $marker) {
        if (str_contains($hay, $marker)) {
            return true;
        }
    }

    return false;
}

function eca_local_demo_news_badge_html(): string
{
    return '<span class="eca-demo-badge" title="Local demonstration content — not an official live ECA announcement">LOCAL DEMO</span>';
}

function eca_local_demo_news_display_title(string $title): string
{
    $title = trim($title);
    if (str_starts_with(strtoupper($title), '[LOCAL DEMO]')) {
        return trim(substr($title, strlen('[LOCAL DEMO]')));
    }
    return $title;
}
