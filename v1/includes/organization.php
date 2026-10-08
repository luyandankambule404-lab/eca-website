<?php
/**
 * Public organizational structure helpers — presentation layer only.
 * No database access. No admin/RBAC coupling.
 */

function eca_org_h(string $value): string
{
    return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
}

/**
 * Five official ECA organizational pillars (source terminology preserved).
 */
function eca_org_pillars(): array
{
    return [
        'advocacy' => [
            'verb' => 'Advocate',
            'title' => 'Advocacy, Policy & Legal Affairs',
            'short' => 'Advocacy',
            'href' => '/advocacy.php',
            'icon' => 'fa-landmark',
            'blurb' => 'Policy, legislation and industry representation — the voice of Eswatini\'s contractors.',
            'focus' => 'Advocacy is the original heartbeat and core foundation of the association.',
        ],
        'digital' => [
            'verb' => 'Understand',
            'title' => 'Digital Systems & Data Intelligence',
            'short' => 'Digital Intelligence',
            'href' => '/digital-intelligence.php',
            'icon' => 'fa-chart-line',
            'blurb' => 'Data, intelligence and industry insights that inform advocacy and development.',
            'focus' => 'Digital systems generate actionable industry intelligence for the wider organization.',
        ],
        'professionalization' => [
            'verb' => 'Develop',
            'title' => 'Professionalization & Capacity Building',
            'short' => 'Professionalization',
            'href' => '/professionalization.php',
            'icon' => 'fa-graduation-cap',
            'blurb' => 'Training, CPD and professionalization for a skilled construction industry.',
            'focus' => 'Competency gaps identified through intelligence inform training and CPD programmes.',
        ],
        'support' => [
            'verb' => 'Support',
            'title' => 'Technical Support & Advisory',
            'short' => 'Technical Support',
            'href' => '/technical-support.php',
            'icon' => 'fa-clipboard-list',
            'blurb' => 'Practical business, contract and tender advisory support for contractors.',
            'focus' => 'Hands-on guidance that helps contractors operate with greater confidence.',
        ],
        'care' => [
            'verb' => 'Care',
            'title' => 'Member Wellness, Inclusivity & CSR Operations',
            'short' => 'Wellness & Inclusivity',
            'href' => '/wellness-inclusivity.php',
            'icon' => 'fa-heartbeat',
            'blurb' => 'Wellness, inclusion and social impact — supporting the person behind the company.',
            'focus' => 'Caring for the person behind the company through wellness, inclusivity and CSR operations.',
        ],
    ];
}

/**
 * Whole-of-association operating narrative (presentation layer).
 * Distinct from the Advocacy page process steps (Listen → Understand → Represent…).
 */
function eca_org_operating_steps(): array
{
    return [
        ['LISTEN', 'Members and industry stakeholders raise contractor challenges and sector concerns.'],
        ['COLLECT', 'Digital systems and engagement gather information on capabilities, hurdles and opportunities.'],
        ['ANALYSE', 'That information becomes industry intelligence for advocacy and capacity building.'],
        ['ADVOCATE', 'Advocacy, Policy & Legal Affairs represents contractors on legislation, policy and procurement.'],
        ['SUPPORT', 'Technical Support & Advisory provides practical business, contract and tender guidance.'],
        ['DEVELOP', 'Professionalization & Capacity Building strengthens skills through training and CPD.'],
        ['IMPACT', 'Together with wellness and inclusivity, ECA works for a fairer, more capable industry.'],
    ];
}

function eca_org_operating_intro(): string
{
    return 'ECA is governed by its Board / Executive Committee and led through the Executive Secretariat. '
        . 'Members are served through advocacy, digital systems, professionalization, wellness and inclusivity, '
        . 'and technical advisory support. Intelligence from digital systems informs advocacy and training; '
        . 'advisory and wellness functions support the contractor and the person behind the company.';
}

function eca_org_assets(): void
{
    static $printed = false;
    if ($printed) {
        return;
    }
    $printed = true;
    echo '<link rel="stylesheet" href="/css/organization.css?v=20261008-org3">' . "\n";
}

function eca_org_pillar_nav(string $active = ''): void
{
    echo '<nav class="org-pillar-nav" aria-label="ECA organizational pillars">';
    foreach (eca_org_pillars() as $key => $pillar) {
        $class = $key === $active ? ' class="is-active"' : '';
        echo '<a href="' . eca_org_h($pillar['href']) . '"' . $class . '>' . eca_org_h($pillar['short']) . '</a>';
    }
    echo '</nav>';
}

function eca_org_link_card(string $title, string $text, string $href, string $cta = 'Open →', string $icon = 'fa-arrow-right'): void
{
    echo '<a class="org-link-card" href="' . eca_org_h($href) . '">';
    echo '<span class="org-link-icon" aria-hidden="true"><i class="fas ' . eca_org_h($icon) . '"></i></span>';
    echo '<h3>' . eca_org_h($title) . '</h3>';
    echo '<p>' . eca_org_h($text) . '</p>';
    echo '<span class="org-link-cta">' . eca_org_h($cta) . '</span>';
    echo '</a>';
}

function eca_org_topic_card(string $title, string $text, string $icon = 'fa-circle'): void
{
    echo '<article class="org-topic-card">';
    echo '<span class="org-link-icon" aria-hidden="true"><i class="fas ' . eca_org_h($icon) . '"></i></span>';
    echo '<h3>' . eca_org_h($title) . '</h3>';
    echo '<p>' . eca_org_h($text) . '</p>';
    echo '</article>';
}

function eca_org_note(string $message): void
{
    echo '<p class="org-note">' . eca_org_h($message) . '</p>';
}
