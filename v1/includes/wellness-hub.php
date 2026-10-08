<?php

require_once __DIR__ . '/env.php';
require_once __DIR__ . '/wellness.php';
require_once __DIR__ . '/session.php';

function eca_wellness_hub_local(?PDO $conn = null): bool
{
    return eca_env('ECA_DB_NAME', '') === 'eca_local' || eca_env('ECA_HUB_DB_NAME', '') === 'eca_local';
}

function eca_wellness_hub_sections(): array
{
    return [
        'home' => ['Wellness Hub', '/wellness/', 'fa-heart', 'Welcome, mental health, holistic wellness and practical support for contractors and their teams.'],
        'mental' => ['Mental Health', '/wellness/mental-health.php', 'fa-brain', 'Understanding stress, resilience, burnout, stigma and where to get help.'],
        'holistic' => ['Holistic Wellness', '/wellness/holistic.php', 'fa-spa', 'The eight dimensions of wellness applied to construction work and business life.'],
        'library' => ['Wellness Library', '/wellness/library.php', 'fa-book-open', 'Articles, videos, guides, checklists and downloadable resources.'],
        'checkin' => ['Contractor Check-In', '/wellness/check-in.php', 'fa-clipboard-check', 'A short, confidential self-assessment for awareness and early support — not a diagnosis.'],
        'toolbox' => ['Hard Hat, Soft Mind', '/wellness/toolbox.php', 'fa-hard-hat', 'Short toolbox talks for supervisors to use with teams on site.'],
        'support' => ['Support & Referrals', '/wellness/support.php', 'fa-hands-helping', 'Pathways to counselling, psychosocial support and emergency help.'],
        'groups' => ['Support Groups', '/wellness/groups.php', 'fa-users', 'Approved, moderated online support groups with privacy rules.'],
    ];
}

function eca_wellness_hub_kinds(): array
{
    return [
        'article' => 'Article',
        'video' => 'Video',
        'document' => 'Document',
        'guide' => 'Guide',
        'checklist' => 'Checklist',
        'talk' => 'Toolbox talk',
        'referral' => 'Referral',
        'group' => 'Support group',
    ];
}

function eca_wellness_hub_section_keys(): array
{
    return ['', 'mental-health', 'holistic', 'library', 'toolbox', 'support', 'groups'];
}

function eca_wellness_mental_topics(): array
{
    return [
        'understanding-mental-health' => [
            'Understanding mental health',
            'Mental health is how we think, feel and cope with work, family and pressure. It changes over time. Feeling stressed after a difficult site week is common. Lasting hopelessness, panic or withdrawal is a signal to get support.',
            'On a construction site, mental health shows up in concentration, decision-making, communication and safety. Looking after it is part of running a professional business — not a private weakness.',
        ],
        'stress-coping' => [
            'Stress & coping',
            'Stress is the body and mind reacting to deadlines, cash flow, weather, labour shortages and client demands. Short bursts can sharpen focus. Unrelieved stress affects sleep, temper and judgement.',
            'Practical coping: name the pressure, break the day into safe tasks, take a real break, talk to a trusted colleague, and keep one end-of-day shutdown habit. Coping is a skill, not a personality test.',
        ],
        'resilience' => [
            'Resilience',
            'Resilience is the ability to recover after a hard job, a delayed payment or a site incident. It is built by habits and support, not by “toughing it out” alone.',
            'Teams become more resilient when supervisors plan realistically, recognise effort, and make it normal to speak up before someone is exhausted.',
        ],
        'burnout-fatigue' => [
            'Burnout & fatigue',
            'Fatigue is more than feeling tired. It reduces reaction time and increases incident risk. Burnout is longer-term exhaustion plus cynicism and a sense that nothing you do is enough.',
            'Warning signs include snapping at the team, skipping meals, working through illness, and dreading Monday. Treat fatigue as a safety issue. Rest is a control measure.',
        ],
        'mental-health-awareness' => [
            'Mental-health awareness',
            'Awareness means noticing changes in yourself and others early: withdrawal, missed days, more arguments, more mistakes, or talking about giving up.',
            'Awareness is not diagnosing a colleague. It is asking “Are you alright?” once, listening, and pointing them to support if they want it.',
        ],
        'prevention-treatment' => [
            'Prevention & treatment',
            'Prevention includes sleep, hydration, fair rosters, clear instructions and a culture where people can say they are struggling. Treatment is professional care — counselling, medical advice or specialist services.',
            'The Wellness Hub does not diagnose or treat anyone. If symptoms persist, speak to a qualified professional. ECA can help you find a referral pathway.',
        ],
        'stigma-awareness' => [
            'Stigma & awareness',
            'Stigma is the idea that asking for help means you are weak or unfit to lead. That belief keeps people silent until a crisis hits the site or the business.',
            'Strong teams treat wellbeing the same way they treat PPE: planned, spoken about, and never mocked. Confidentiality matters. Gossip is not support.',
        ],
    ];
}

function eca_wellness_dimensions(): array
{
    return [
        'physical' => ['Physical', 'Sleep, hydration, nutrition, movement and injury prevention. A tired body makes unsafe decisions on tools, heights and traffic.'],
        'spiritual' => ['Spiritual', 'Meaning, values and the beliefs that keep a person steady. This is personal. The Hub respects faith and personal practice without prescribing one path.'],
        'occupational' => ['Occupational', 'Pride in the trade, fair work, skill growth and a manageable workload. A contractor’s work is a large part of identity — it should not consume the whole person.'],
        'financial' => ['Financial', 'Cash flow, late payments, quoting pressure and household bills. Financial strain is one of the most common stressors in contracting. Seek advice early.'],
        'emotional' => ['Emotional', 'How feelings are noticed and handled — frustration on site, worry at night, or relief after a completed job. Naming emotion is a leadership skill.'],
        'social' => ['Social', 'Family, crew, suppliers and community. Isolation after long hours is common. A check-in with a trusted person is part of staying well.'],
        'environmental' => ['Environmental', 'Site conditions, weather, noise, dust, travel and home rest. A safer, cleaner environment supports both body and mind.'],
        'mental' => ['Mental', 'Focus, learning, problem-solving and rest for the mind. Mental load rises with multi-job management. Protect thinking time the way you protect a hold point.'],
    ];
}

function eca_wellness_checkin_questions(): array
{
    return [
        'stress' => 'How often has work stress felt hard to manage this week?',
        'fatigue' => 'How often have you felt too tired to work safely?',
        'pressure' => 'How heavy is the pressure from cash flow, deadlines or clients?',
        'leadership' => 'How difficult is it to talk about wellbeing with your team or supervisor?',
        'wellbeing' => 'How would you rate the strain on your personal wellbeing right now?',
    ];
}

function eca_wellness_toolbox_fallback(): array
{
    return [
        [
            'title' => 'Starting the day well',
            'body' => "Take two minutes at the morning huddle.\n1. What is the highest-risk task today?\n2. Who is new or returning from leave?\n3. Has anyone had less than six hours’ sleep?\nClose with: look after the person next to you, not only the programme.",
        ],
        [
            'title' => 'Fatigue on site',
            'body' => "Fatigue is a safety hazard.\nAsk: are we rushing because we are tired, not because the task is urgent?\nActions: rotate high-focus work, drink water, take the scheduled break, and stop if someone is unsteady or snapping at the crew.",
        ],
        [
            'title' => 'Looking out for each other',
            'body' => "You do not need to be a counsellor.\nIf a teammate is withdrawn, missing days or talking about giving up, ask once: “I’ve noticed you don’t seem yourself. Do you want to talk or should I help you find support?”\nThen point them to the Wellness Hub support page or ECA.",
        ],
    ];
}

function eca_wellness_hub_ensure_schema(?PDO $conn): void
{
    if (!$conn || !eca_wellness_hub_local($conn)) {
        return;
    }
    try {
        $cols = $conn->query('SHOW COLUMNS FROM wellness_resources')->fetchAll(PDO::FETCH_COLUMN) ?: [];
        $add = [
            'hub_section' => 'VARCHAR(64) NULL',
            'kind' => 'VARCHAR(32) NULL',
            'body_text' => 'TEXT NULL',
            'topic_slug' => 'VARCHAR(128) NULL',
        ];
        foreach ($add as $col => $def) {
            if (!in_array($col, $cols, true)) {
                $conn->exec('ALTER TABLE wellness_resources ADD COLUMN ' . $col . ' ' . $def);
            }
        }
        $conn->exec(
            'CREATE TABLE IF NOT EXISTS wellness_checkins (
                id INT UNSIGNED NOT NULL AUTO_INCREMENT,
                scores_json VARCHAR(255) NOT NULL,
                band VARCHAR(32) NOT NULL,
                created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
                PRIMARY KEY (id),
                KEY idx_wellness_checkin_created (created_at)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4'
        );
    } catch (Throwable $e) {
        // Hub still works from built-in educational copy.
    }
}

function eca_wellness_hub_items(?PDO $conn, string $section, string $kind = '', int $limit = 40): array
{
    if (!$conn) {
        return [];
    }
    $limit = max(1, min(80, $limit));
    try {
        $sql = "SELECT r.id, r.title, r.description, r.body_text, r.kind, r.hub_section, r.topic_slug,
                       r.file_path, r.file_type, r.external_url, r.published_at, c.name AS category_name
                FROM wellness_resources r
                LEFT JOIN wellness_categories c ON c.id = r.category_id
                WHERE r.status = 'PUBLISHED' AND r.is_public = 1 AND r.hub_section = ?";
        $params = [$section];
        if ($kind !== '') {
            $sql .= ' AND r.kind = ?';
            $params[] = $kind;
        }
        $sql .= ' ORDER BY r.published_at DESC, r.id DESC LIMIT ' . $limit;
        $stmt = $conn->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    } catch (Throwable $e) {
        return [];
    }
}

function eca_wellness_hub_item(?PDO $conn, int $id): ?array
{
    if (!$conn || $id < 1) {
        return null;
    }
    try {
        $stmt = $conn->prepare(
            "SELECT r.*, c.name AS category_name
             FROM wellness_resources r
             LEFT JOIN wellness_categories c ON c.id = r.category_id
             WHERE r.id = ? AND r.status = 'PUBLISHED' AND r.is_public = 1
             LIMIT 1"
        );
        $stmt->execute([$id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    } catch (Throwable $e) {
        return null;
    }
}

function eca_wellness_safe_url(string $url): string
{
    $url = trim($url);
    if ($url === '' || !preg_match('#^https?://#i', $url)) {
        return '';
    }
    return $url;
}

function eca_wellness_youtube_id(string $url): string
{
    if (preg_match('#(?:youtube\.com/(?:watch\?v=|embed/)|youtu\.be/)([A-Za-z0-9_-]{6,})#', $url, $m)) {
        return $m[1];
    }
    return '';
}

function eca_wellness_checkin_band(float $average): string
{
    if ($average < 2.3) {
        return 'lower';
    }
    if ($average < 3.5) {
        return 'mixed';
    }
    return 'higher';
}

function eca_wellness_checkin_copy(string $band): array
{
    return [
        'lower' => [
            'Lower current strain',
            'Your answers suggest a more manageable load this week. Keep the habits that are working: rest, honest conversations, and stopping when a task is no longer safe.',
        ],
        'mixed' => [
            'Mixed pressure',
            'Some areas are heavier than others. That is common in contracting. Use this as an early signal — adjust the week, talk to someone you trust, and browse the support page if pressure is climbing.',
        ],
        'higher' => [
            'Higher current strain',
            'Your answers suggest a heavy load. This is not a diagnosis. It is a prompt to get support sooner, not later. Use the Support & Referrals page, speak to a trusted person, and seek professional help if you feel unsafe.',
        ],
    ][$band];
}

function eca_wellness_hub_subnav(string $active = 'home'): void
{
    echo '<nav class="edu-subnav" aria-label="Wellness Hub sections">';
    foreach (eca_wellness_hub_sections() as $key => $item) {
        $class = $key === $active ? ' class="is-active"' : '';
        echo '<a href="' . htmlspecialchars($item[1], ENT_QUOTES, 'UTF-8') . '"' . $class . '>'
            . htmlspecialchars($item[0], ENT_QUOTES, 'UTF-8') . '</a>';
    }
    echo '</nav>';
}

function eca_wellness_page_start(string $title, string $heading, string $intro, string $nav = 'home', ?array $lines = null): void
{
    require_once __DIR__ . '/public-page.php';
    $conn = eca_wellness_db();
    eca_wellness_hub_ensure_schema($conn);
    eca_public_page_start($title, 'ECA Wellness Hub', $heading, $intro, 'Wellness', $lines, true, 'wellness');
    echo '<div class="edu-wrap eca-wellness-wrap">';
    eca_wellness_hub_subnav($nav);
}

function eca_wellness_page_end(): void
{
    echo '</div>';
    eca_public_page_end();
}

function eca_wellness_h($value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

function eca_wellness_prose(string $text): string
{
    return nl2br(eca_wellness_h($text), false);
}

function eca_wellness_disclaimer(): void
{
    echo '<p class="wh-disclaimer">The Wellness Hub offers practical information and early support. It does not diagnose, treat or replace professional care.</p>';
}

function eca_wellness_section_labels(): array
{
    return [
        '' => 'General / library',
        'mental-health' => 'Mental health',
        'holistic' => 'Holistic wellness',
        'library' => 'Wellness library',
        'toolbox' => 'Hard Hat, Soft Mind',
        'support' => 'Support & referrals',
        'groups' => 'Support groups',
    ];
}

function eca_wellness_library_kinds(): array
{
    return [
        'article' => 'Articles',
        'video' => 'Videos',
        'document' => 'Documents',
        'guide' => 'Guides',
        'checklist' => 'Checklists',
    ];
}

function eca_wellness_library_items(?PDO $conn, string $kind = ''): array
{
    if (!$conn) {
        return [];
    }
    $kinds = eca_wellness_library_kinds();
    if ($kind !== '' && !isset($kinds[$kind])) {
        $kind = '';
    }
    try {
        $sql = "SELECT r.id, r.title, r.description, r.body_text, r.kind, r.hub_section, r.topic_slug,
                       r.file_path, r.file_type, r.external_url, r.published_at, c.name AS category_name
                FROM wellness_resources r
                LEFT JOIN wellness_categories c ON c.id = r.category_id
                WHERE r.status = 'PUBLISHED' AND r.is_public = 1
                  AND (
                    r.hub_section = 'library'
                    OR (IFNULL(r.hub_section,'') = '' AND IFNULL(r.kind,'') NOT IN ('talk','referral','group'))
                  )";
        $params = [];
        if ($kind !== '') {
            $sql .= ' AND r.kind = ?';
            $params[] = $kind;
        }
        $sql .= ' ORDER BY r.published_at DESC, r.id DESC LIMIT 60';
        $stmt = $conn->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    } catch (Throwable $e) {
        return eca_wellness_public_resources($conn, 24);
    }
}

function eca_wellness_save_checkin(?PDO $conn, array $scores, string $band): bool
{
    if (!$conn || !in_array($band, ['lower', 'mixed', 'higher'], true)) {
        return false;
    }
    $clean = [];
    foreach (array_keys(eca_wellness_checkin_questions()) as $key) {
        $n = (int) ($scores[$key] ?? 0);
        if ($n < 1 || $n > 5) {
            return false;
        }
        $clean[$key] = $n;
    }
    try {
        $stmt = $conn->prepare('INSERT INTO wellness_checkins (scores_json, band) VALUES (?, ?)');
        $stmt->execute([json_encode($clean, JSON_UNESCAPED_UNICODE), $band]);
        return true;
    } catch (Throwable $e) {
        return false;
    }
}

function eca_wellness_hub_stats(?PDO $conn): array
{
    $stats = [
        'checkins' => 0,
        'checkins_30d' => 0,
        'hub_published' => 0,
        'by_section' => [],
        'by_kind' => [],
        'by_band' => [],
    ];
    if (!$conn) {
        return $stats;
    }
    try {
        $stats['checkins'] = (int) $conn->query('SELECT COUNT(*) FROM wellness_checkins')->fetchColumn();
        $stats['checkins_30d'] = (int) $conn->query(
            'SELECT COUNT(*) FROM wellness_checkins WHERE created_at >= (NOW() - INTERVAL 30 DAY)'
        )->fetchColumn();
        $stats['by_band'] = $conn->query(
            'SELECT band, COUNT(*) AS cnt FROM wellness_checkins GROUP BY band'
        )->fetchAll(PDO::FETCH_ASSOC) ?: [];
    } catch (Throwable $e) {
        // table may not exist yet
    }
    try {
        $stats['hub_published'] = (int) $conn->query(
            "SELECT COUNT(*) FROM wellness_resources WHERE status = 'PUBLISHED' AND is_public = 1 AND IFNULL(hub_section,'') <> ''"
        )->fetchColumn();
        $stats['by_section'] = $conn->query(
            "SELECT IFNULL(NULLIF(hub_section,''), '(general)') AS hub_section, COUNT(*) AS cnt
             FROM wellness_resources WHERE status = 'PUBLISHED' GROUP BY IFNULL(NULLIF(hub_section,''), '(general)')"
        )->fetchAll(PDO::FETCH_ASSOC) ?: [];
        $stats['by_kind'] = $conn->query(
            "SELECT IFNULL(NULLIF(kind,''), '(unset)') AS kind, COUNT(*) AS cnt
             FROM wellness_resources WHERE status = 'PUBLISHED' GROUP BY IFNULL(NULLIF(kind,''), '(unset)')"
        )->fetchAll(PDO::FETCH_ASSOC) ?: [];
    } catch (Throwable $e) {
        // columns may not exist yet
    }
    return $stats;
}

function eca_wellness_admin_content_types(): array
{
    return [
        'article' => [
            'nav' => 'articles',
            'label' => 'Articles',
            'singular' => 'article',
            'add' => 'Add article',
            'kind' => 'article',
            'lock_kind' => true,
            'default_section' => 'library',
            'lock_section' => false,
            'help' => 'Write and publish articles for the Wellness Library, Mental Health or Holistic Wellness pages.',
            'body_label' => 'Article text',
            'body_required' => true,
            'file' => '',
            'url' => false,
            'topic' => true,
        ],
        'video' => [
            'nav' => 'videos',
            'label' => 'Videos',
            'singular' => 'video',
            'add' => 'Upload video',
            'kind' => 'video',
            'lock_kind' => true,
            'default_section' => 'library',
            'lock_section' => false,
            'help' => 'Upload an MP4 (maximum 32 MB) or add a YouTube link. Published videos appear in the Wellness Library.',
            'body_label' => 'Description',
            'body_required' => false,
            'file' => 'video',
            'url' => true,
            'topic' => true,
        ],
        'document' => [
            'nav' => 'documents',
            'label' => 'Documents',
            'singular' => 'document',
            'add' => 'Upload document',
            'kind' => 'document',
            'lock_kind' => true,
            'default_section' => 'library',
            'lock_section' => false,
            'help' => 'Upload PDFs or Word documents for the Wellness Library.',
            'body_label' => 'Notes',
            'body_required' => false,
            'file' => 'document',
            'url' => false,
            'topic' => true,
        ],
        'talk' => [
            'nav' => 'talks',
            'label' => 'Toolbox talks',
            'singular' => 'toolbox talk',
            'add' => 'Create toolbox talk',
            'kind' => 'talk',
            'lock_kind' => true,
            'default_section' => 'toolbox',
            'lock_section' => true,
            'help' => 'Short talks for supervisors to use with teams on site. These appear on Hard Hat, Soft Mind.',
            'body_label' => 'Talk script',
            'body_required' => true,
            'file' => 'document',
            'url' => false,
            'topic' => false,
        ],
        'referral' => [
            'nav' => 'referrals',
            'label' => 'Referrals',
            'singular' => 'referral',
            'add' => 'Add referral',
            'kind' => 'referral',
            'lock_kind' => true,
            'default_section' => 'support',
            'lock_section' => true,
            'help' => 'Publish only organisations ECA has verified. Do not invent names, clinics or hotlines.',
            'body_label' => 'How members should use this referral',
            'body_required' => true,
            'file' => '',
            'url' => true,
            'topic' => false,
        ],
        'group' => [
            'nav' => 'groups',
            'label' => 'Support groups',
            'singular' => 'support group',
            'add' => 'Add support group',
            'kind' => 'group',
            'lock_kind' => true,
            'default_section' => 'groups',
            'lock_section' => true,
            'help' => 'Approved, moderated groups only. Include joining details and privacy notes. This is not an open chat.',
            'body_label' => 'Group information',
            'body_required' => true,
            'file' => '',
            'url' => true,
            'topic' => false,
        ],
        'resource' => [
            'nav' => 'resources',
            'label' => 'Wellness resources',
            'singular' => 'resource',
            'add' => 'Add resource',
            'kind' => '',
            'lock_kind' => false,
            'default_section' => 'library',
            'lock_section' => false,
            'help' => 'All Wellness Hub items in one place. Prefer Articles, Videos, Documents, Toolbox talks, Referrals or Groups when the type is known.',
            'body_label' => 'Full text',
            'body_required' => false,
            'file' => 'any',
            'url' => true,
            'topic' => true,
        ],
    ];
}

function eca_wellness_admin_content_type(string $type): array
{
    $types = eca_wellness_admin_content_types();
    return $types[$type] ?? $types['resource'];
}

function eca_wellness_admin_type_from_row(array $row): string
{
    $kind = (string) ($row['kind'] ?? '');
    $map = [
        'article' => 'article',
        'video' => 'video',
        'document' => 'document',
        'talk' => 'talk',
        'referral' => 'referral',
        'group' => 'group',
    ];
    return $map[$kind] ?? 'resource';
}

function eca_wellness_count_kind(?PDO $conn, string $kind): int
{
    if (!$conn || $kind === '') {
        return 0;
    }
    try {
        $stmt = $conn->prepare('SELECT COUNT(*) FROM wellness_resources WHERE kind = ?');
        $stmt->execute([$kind]);
        return (int) $stmt->fetchColumn();
    } catch (Throwable $e) {
        return 0;
    }
}

function eca_wellness_video_embed(string $url): string
{
    $id = eca_wellness_youtube_id($url);
    if ($id === '') {
        return '';
    }
    $src = 'https://www.youtube-nocookie.com/embed/' . rawurlencode($id);
    return '<div class="wh-video"><iframe src="' . eca_wellness_h($src) . '" title="Wellness video" allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture" allowfullscreen loading="lazy"></iframe></div>';
}

function eca_wellness_support_pathways(): array
{
    return [
        ['Counselling', 'The Hub is not a counselling service. If you want to talk with a counsellor, contact the ECA office and ask for a referral pathway.'],
        ['Psychosocial support', 'Start with a trusted colleague, family member or supervisor, then use the Contractor Check-In and Support page. ECA can help you find the next step.'],
        ['Professional services', 'Persistent distress, panic, substance harm or thoughts of not wanting to live need a qualified professional. ECA can help you locate appropriate services — we do not treat or diagnose.'],
        ['Emergency support', 'If you or someone else is in immediate danger, contact local emergency services first. Then tell someone you trust, and contact ECA when it is safe to do so.'],
        ['Approved support organisations', 'Only organisations published by the ECA administrator appear here. If none are listed yet, use the ECA office contacts below.'],
    ];
}

function eca_wellness_group_rules(): array
{
    return [
        'Groups listed here are approved by ECA. This is not an open chat or social feed.',
        'Participation is voluntary. Do not share another person’s story without their permission.',
        'Moderators may remove posts that identify someone, give medical advice, or put a person or site at risk.',
        'What is said in a group stays in the group. Screenshots and forwarding are not allowed.',
        'If you need urgent help, leave the group discussion and use emergency services or the Support & Referrals page.',
    ];
}

function eca_wellness_render_cards(array $items, string $empty = ''): void
{
    if (!$items) {
        echo '<div class="edu-empty"><p>' . eca_wellness_h($empty !== '' ? $empty : 'Published items for this section will appear here when the Wellness administrator adds them.') . '</p></div>';
        return;
    }
    echo '<div class="edu-card-grid">';
    foreach ($items as $item) {
        $id = (int) ($item['id'] ?? 0);
        $kind = (string) ($item['kind'] ?? '');
        $href = $id > 0 ? '/wellness/item.php?id=' . $id : '#';
        $label = eca_wellness_hub_kinds()[$kind] ?? ((string) ($item['category_name'] ?? 'Resource'));
        echo '<article class="edu-card">';
        echo '<p class="edu-kicker">' . eca_wellness_h($label) . '</p>';
        echo '<h3>' . eca_wellness_h((string) ($item['title'] ?? '')) . '</h3>';
        echo '<p>' . eca_wellness_h((string) ($item['description'] ?? '')) . '</p>';
        if ($id > 0) {
            echo '<a class="edu-more" href="' . eca_wellness_h($href) . '">Open</a>';
        }
        echo '</article>';
    }
    echo '</div>';
}
