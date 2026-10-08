<?php

function eca_education_admin_nav(string $active = 'overview'): void
{
    $links = [
        'overview' => ['/admin/education.php', 'Overview'],
        'courses' => ['/admin/education-courses.php', 'Courses'],
        'knowledge' => ['/admin/education-articles.php?kind=knowledge', 'Knowledge'],
        'policy' => ['/admin/education-articles.php?kind=policy', 'Policy'],
        'programmes' => ['/admin/education-programmes.php', 'Programmes'],
        'resources' => ['/admin/education-resources.php', 'Resources'],
        'library' => ['/admin/education-library.php', 'Venues & categories'],
    ];
    echo '<p class="hub-card" style="display:flex;gap:12px;flex-wrap:wrap;">';
    foreach ($links as $key => $item) {
        $label = $item[1];
        if ($key === $active) {
            echo '<strong>' . eca_admin_h($label) . '</strong>';
        } else {
            echo '<a href="' . eca_admin_h($item[0]) . '">' . eca_admin_h($label) . '</a>';
        }
    }
    echo '</p>';
}

function eca_education_admin_field_style(): string
{
    return '';
}

/**
 * Soft-fail when education tables are missing locally (no hard 500).
 * Ends the request after rendering a Hub empty state.
 */
function eca_education_admin_require_ready(?PDO $conn, string $title = 'Education'): void
{
    if ($conn && function_exists('eca_education_ready') && eca_education_ready($conn)) {
        return;
    }
    eca_admin_hub_start($title, 'education');
    echo '<div class="hub-hello">';
    echo '<h1 class="hub-hello-title">' . eca_admin_h($title) . '</h1>';
    echo '<p><strong>DATA NOT AVAILABLE LOCALLY.</strong> Education CMS tables are not ready in this local database. Run local-db education setup when needed — this is not a silent zero.</p>';
    echo '</div>';
    echo '<p class="hub-card"><a href="/admin/index.php">Back to dashboard</a></p>';
    eca_admin_hub_end();
    exit;
}
