<?php

require_once __DIR__ . '/content.php';

function eca_education_h($value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

function eca_education_db(): ?PDO
{
    static $conn = false;
    if ($conn !== false) {
        return $conn;
    }
    if (!class_exists('Database')) {
        $config = dirname(__DIR__) . DIRECTORY_SEPARATOR . 'config.php';
        if (is_file($config)) {
            require_once $config;
        }
    }
    if (!class_exists('Database')) {
        $conn = null;
        return null;
    }
    try {
        $db = new Database();
        $pdo = $db->getConnection(false);
        $conn = $pdo instanceof PDO ? $pdo : null;
    } catch (Throwable $e) {
        $conn = null;
    }
    return $conn;
}

function eca_education_ready(?PDO $conn = null): bool
{
    $conn = $conn ?: eca_education_db();
    if (!$conn) {
        return false;
    }
    try {
        $conn->query('SELECT 1 FROM education_courses LIMIT 1');
        return true;
    } catch (Throwable $e) {
        return false;
    }
}

function eca_education_query(PDO $conn, string $sql, array $params = []): array
{
    try {
        $stmt = $conn->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (Throwable $e) {
        return [];
    }
}

function eca_education_one(PDO $conn, string $sql, array $params = []): ?array
{
    $rows = eca_education_query($conn, $sql, $params);
    return $rows[0] ?? null;
}

function eca_education_setting(PDO $conn, string $key, string $default = ''): string
{
    $row = eca_education_one($conn, 'SELECT setting_value FROM education_settings WHERE setting_key = ? LIMIT 1', [$key]);
    $value = trim((string) ($row['setting_value'] ?? ''));
    return $value !== '' ? $value : $default;
}

function eca_education_set_setting(PDO $conn, string $key, string $value): void
{
    $conn->prepare(
        'INSERT INTO education_settings (setting_key, setting_value) VALUES (?, ?)
         ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value)'
    )->execute([$key, $value]);
}

function eca_education_slugify(string $title): string
{
    $slug = strtolower(trim($title));
    $slug = preg_replace('/[^a-z0-9]+/', '-', $slug) ?? '';
    $slug = trim($slug, '-');
    return $slug !== '' ? substr($slug, 0, 180) : 'item';
}

function eca_education_unique_slug(PDO $conn, string $table, string $title, int $ignoreId = 0, string $kind = ''): string
{
    $allowed = [
        'education_courses' => 'slug',
        'education_programmes' => 'slug',
        'education_articles' => 'slug',
        'education_categories' => 'slug',
    ];
    if (!isset($allowed[$table])) {
        return eca_education_slugify($title);
    }
    $base = eca_education_slugify($title);
    $slug = $base;
    $n = 2;
    while (true) {
        if ($table === 'education_articles') {
            $sql = 'SELECT id FROM education_articles WHERE slug = ? AND kind = ? AND id <> ? LIMIT 1';
            $row = eca_education_one($conn, $sql, [$slug, $kind !== '' ? $kind : 'knowledge', $ignoreId]);
        } else {
            $row = eca_education_one($conn, 'SELECT id FROM `' . $table . '` WHERE slug = ? AND id <> ? LIMIT 1', [$slug, $ignoreId]);
        }
        if (!$row) {
            return $slug;
        }
        $slug = $base . '-' . $n;
        $n++;
    }
}

function eca_education_upload_dir(): string
{
    $dir = dirname(__DIR__) . DIRECTORY_SEPARATOR . 'storage' . DIRECTORY_SEPARATOR . 'education';
    if (!is_dir($dir)) {
        mkdir($dir, 0775, true);
    }
    return $dir;
}

function eca_education_allowed_extensions(): array
{
    return ['pdf', 'doc', 'docx', 'ppt', 'pptx', 'xls', 'xlsx', 'jpg', 'jpeg', 'png', 'webp', 'mp4'];
}

function eca_education_store_upload(array $file, string $prefix = 'edu'): ?string
{
    if (empty($file['tmp_name']) || !is_uploaded_file($file['tmp_name'])) {
        return null;
    }
    if ((int) ($file['size'] ?? 0) > 15 * 1024 * 1024) {
        return null;
    }
    if (!function_exists('eca_uploaded_file_meta')) {
        require_once __DIR__ . '/documents.php';
    }
    $meta = eca_uploaded_file_meta($file, eca_upload_mime_map(eca_education_allowed_extensions()), 15 * 1024 * 1024);
    if (!$meta) {
        return null;
    }
    $ext = $meta['ext'];
    $base = preg_replace('/[^A-Za-z0-9._-]/', '_', basename((string) $file['name']));
    $name = $prefix . '_' . date('YmdHis') . '_' . bin2hex(random_bytes(4)) . '_' . $base;
    $dest = eca_education_upload_dir() . DIRECTORY_SEPARATOR . $name;
    if (!move_uploaded_file($file['tmp_name'], $dest)) {
        return null;
    }
    return $name;
}

function eca_education_file_path(string $storedName): ?string
{
    $storedName = basename($storedName);
    if ($storedName === '' || str_contains($storedName, '..')) {
        return null;
    }
    $path = eca_education_upload_dir() . DIRECTORY_SEPARATOR . $storedName;
    return is_file($path) ? $path : null;
}

function eca_education_course_statuses(): array
{
    return ['DRAFT' => 'Draft', 'UPCOMING' => 'Upcoming', 'COMPLETED' => 'Completed'];
}

function eca_education_publish_statuses(): array
{
    return ['DRAFT' => 'Draft', 'PUBLISHED' => 'Published', 'ARCHIVED' => 'Archived'];
}

function eca_education_knowledge_types(): array
{
    return [
        'article' => 'Article',
        'guide' => 'Guide',
        'checklist' => 'Checklist',
        'template' => 'Template',
        'video' => 'Video',
        'faq' => 'FAQ',
        'presentation' => 'Presentation',
    ];
}

function eca_education_resource_types(): array
{
    return [
        'pdf' => 'PDF',
        'presentation' => 'Training presentation',
        'guide' => 'Guide',
        'form' => 'Form',
        'checklist' => 'Checklist',
        'video' => 'Video',
        'reference' => 'Reference document',
    ];
}

function eca_education_label_key(string $value): string
{
    $value = strtolower(trim($value));
    $value = preg_replace('/[^a-z0-9]+/', '', $value) ?? '';
    return rtrim($value, 's');
}

function eca_education_category_matches_type(array $category, array $types): bool
{
    $nameKey = eca_education_label_key((string) ($category['name'] ?? ''));
    $slugKey = eca_education_label_key((string) ($category['slug'] ?? ''));
    foreach ($types as $key => $label) {
        $typeKey = eca_education_label_key((string) $label);
        $codeKey = eca_education_label_key((string) $key);
        if ($nameKey === $typeKey || $nameKey === $codeKey || $slugKey === $typeKey || $slugKey === $codeKey) {
            return true;
        }
    }
    return false;
}

function eca_education_topic_categories(array $categories, array $types): array
{
    return array_values(array_filter(
        $categories,
        static fn (array $category): bool => !eca_education_category_matches_type($category, $types)
    ));
}

function eca_education_kicker(string $typeLabel, string $categoryName = ''): string
{
    $typeLabel = trim($typeLabel);
    $categoryName = trim($categoryName);
    if ($categoryName === '' || eca_education_label_key($categoryName) === eca_education_label_key($typeLabel)) {
        return $typeLabel;
    }
    if ($typeLabel === '') {
        return $categoryName;
    }
    return $typeLabel . ' · ' . $categoryName;
}

function eca_education_date(?string $value, string $format = 'd M Y'): string
{
    if (!$value) {
        return '';
    }
    $ts = strtotime($value);
    return $ts ? date($format, $ts) : '';
}

function eca_education_course_dates(array $course): string
{
    $start = eca_education_date($course['start_date'] ?? null);
    $end = eca_education_date($course['end_date'] ?? null);
    if ($start !== '' && $end !== '' && $start !== $end) {
        return $start . ' – ' . $end;
    }
    return $start !== '' ? $start : ($end !== '' ? $end : 'Date to be confirmed');
}

function eca_education_venue_name(array $course): string
{
    $name = trim((string) ($course['venue_name'] ?? ''));
    if ($name !== '') {
        return $name;
    }
    return trim((string) ($course['venue_text'] ?? '')) ?: 'Venue to be confirmed';
}

function eca_education_categories(PDO $conn, string $section, bool $activeOnly = true): array
{
    $sql = 'SELECT * FROM education_categories WHERE section = ?';
    $params = [$section];
    if ($activeOnly) {
        $sql .= " AND status = 'ACTIVE'";
    }
    $sql .= ' ORDER BY sort_order ASC, name ASC';
    return eca_education_query($conn, $sql, $params);
}

function eca_education_venues(PDO $conn, bool $activeOnly = true): array
{
    $sql = 'SELECT * FROM education_venues';
    if ($activeOnly) {
        $sql .= " WHERE status = 'ACTIVE'";
    }
    $sql .= ' ORDER BY name ASC';
    return eca_education_query($conn, $sql);
}

function eca_education_facilitators(PDO $conn, bool $activeOnly = true): array
{
    $sql = 'SELECT * FROM education_facilitators';
    if ($activeOnly) {
        $sql .= " WHERE status = 'ACTIVE'";
    }
    $sql .= ' ORDER BY name ASC';
    return eca_education_query($conn, $sql);
}

function eca_education_course_sql(): string
{
    return 'SELECT c.*, v.name AS venue_name, f.name AS facilitator_name, f.organisation AS facilitator_org
            FROM education_courses c
            LEFT JOIN education_venues v ON v.id = c.venue_id
            LEFT JOIN education_facilitators f ON f.id = c.facilitator_id';
}

function eca_education_public_courses(PDO $conn, string $status = '', bool $featuredOnly = false): array
{
    $sql = eca_education_course_sql() . " WHERE c.status IN ('UPCOMING','COMPLETED')";
    $params = [];
    if ($status !== '' && in_array($status, ['UPCOMING', 'COMPLETED'], true)) {
        $sql .= ' AND c.status = ?';
        $params[] = $status;
    }
    if ($featuredOnly) {
        $sql .= ' AND c.is_featured = 1';
    }
    $sql .= ' ORDER BY (c.status = \'UPCOMING\') DESC, c.start_date DESC, c.id DESC';
    return eca_education_query($conn, $sql, $params);
}

function eca_education_course_by_slug(PDO $conn, string $slug): ?array
{
    return eca_education_one($conn, eca_education_course_sql() . ' WHERE c.slug = ? LIMIT 1', [$slug]);
}

function eca_education_public_articles(PDO $conn, string $kind, ?int $categoryId = null, string $type = ''): array
{
    $sql = 'SELECT a.*, cat.name AS category_name, cat.slug AS category_slug
            FROM education_articles a
            LEFT JOIN education_categories cat ON cat.id = a.category_id
            WHERE a.kind = ? AND a.status = ?';
    $params = [$kind, 'PUBLISHED'];
    if ($categoryId) {
        $sql .= ' AND a.category_id = ?';
        $params[] = $categoryId;
    }
    if ($type !== '') {
        $sql .= ' AND a.resource_type = ?';
        $params[] = $type;
    }
    $sql .= ' ORDER BY a.is_featured DESC, a.published_at DESC, a.id DESC';
    return eca_education_query($conn, $sql, $params);
}

function eca_education_article_by_slug(PDO $conn, string $kind, string $slug): ?array
{
    return eca_education_one(
        $conn,
        'SELECT a.*, cat.name AS category_name
         FROM education_articles a
         LEFT JOIN education_categories cat ON cat.id = a.category_id
         WHERE a.kind = ? AND a.slug = ? LIMIT 1',
        [$kind, $slug]
    );
}

function eca_education_public_programmes(PDO $conn): array
{
    return eca_education_query(
        $conn,
        "SELECT * FROM education_programmes WHERE status = 'PUBLISHED' ORDER BY sort_order ASC, title ASC"
    );
}

function eca_education_programme_by_slug(PDO $conn, string $slug): ?array
{
    return eca_education_one($conn, 'SELECT * FROM education_programmes WHERE slug = ? LIMIT 1', [$slug]);
}

function eca_education_public_resources(PDO $conn, ?int $categoryId = null, string $type = ''): array
{
    $sql = 'SELECT r.*, cat.name AS category_name
            FROM education_resources r
            LEFT JOIN education_categories cat ON cat.id = r.category_id
            WHERE r.status = ?';
    $params = ['PUBLISHED'];
    if ($categoryId) {
        $sql .= ' AND r.category_id = ?';
        $params[] = $categoryId;
    }
    if ($type !== '') {
        $sql .= ' AND r.resource_type = ?';
        $params[] = $type;
    }
    $sql .= ' ORDER BY r.is_featured DESC, r.id DESC';
    return eca_education_query($conn, $sql, $params);
}

function eca_education_counts(PDO $conn): array
{
    $count = static function (string $sql) use ($conn): int {
        try {
            return (int) $conn->query($sql)->fetchColumn();
        } catch (Throwable $e) {
            return 0;
        }
    };
    return [
        'courses' => $count('SELECT COUNT(*) FROM education_courses'),
        'upcoming' => $count("SELECT COUNT(*) FROM education_courses WHERE status = 'UPCOMING'"),
        'articles' => $count("SELECT COUNT(*) FROM education_articles WHERE kind = 'knowledge'"),
        'policy' => $count("SELECT COUNT(*) FROM education_articles WHERE kind = 'policy'"),
        'programmes' => $count('SELECT COUNT(*) FROM education_programmes'),
        'resources' => $count('SELECT COUNT(*) FROM education_resources'),
    ];
}

function eca_education_download_file_param(string $url): string
{
    if (!str_starts_with($url, '/download.php')) {
        return '';
    }
    $query = parse_url($url, PHP_URL_QUERY);
    if (!is_string($query) || $query === '') {
        return '';
    }
    parse_str($query, $params);
    $file = basename(str_replace(['\\', "\0"], '', (string) ($params['file'] ?? '')));
    return $file;
}

function eca_education_inline_url(string $url): string
{
    if (!str_starts_with($url, '/download.php') && !str_starts_with($url, '/education-download.php')) {
        return $url;
    }
    if (str_contains($url, 'inline=')) {
        return $url;
    }
    return $url . (str_contains($url, '?') ? '&' : '?') . 'inline=1';
}

function eca_education_resource_href(array $resource, string $mode = 'download'): string
{
    $open = $mode === 'open';
    $id = (int) ($resource['id'] ?? 0);
    $file = trim((string) ($resource['file_path'] ?? ''));
    if ($file !== '') {
        if ($open) {
            return '/education-view.php?id=' . $id;
        }
        return '/education-download.php?id=' . $id;
    }
    $url = trim((string) ($resource['external_url'] ?? ''));
    if ($url === '') {
        return '#';
    }
    if ($open) {
        $downloadFile = eca_education_download_file_param($url);
        if ($downloadFile !== '') {
            return '/education-view.php?file=' . rawurlencode($downloadFile);
        }
        if ($id > 0) {
            return '/education-view.php?id=' . $id;
        }
    }
    return $url;
}

function eca_education_register_href(array $course): string
{
    $url = trim((string) ($course['registration_url'] ?? ''));
    if ($url !== '') {
        return $url;
    }
    if (!empty($course['cpd_course_id'])) {
        return '/cpd/registration.php?course_id=' . (int) $course['cpd_course_id'];
    }
    return '/cpd/registration.php';
}

function eca_education_learner_href(PDO $conn, ?array $course = null): string
{
    if ($course) {
        $url = trim((string) ($course['learner_portal_url'] ?? ''));
        if ($url !== '') {
            return $url;
        }
    }
    return eca_education_setting($conn, 'learner_portal_url', '/cpd/login.php');
}

function eca_education_status_class(string $status): string
{
    $status = strtoupper($status);
    if ($status === 'UPCOMING' || $status === 'PUBLISHED' || $status === 'ACTIVE') {
        return 'is-live';
    }
    if ($status === 'COMPLETED') {
        return 'is-done';
    }
    return 'is-muted';
}

function eca_education_sections(): array
{
    return [
        'hub' => ['Education hub', '/education.php', 'fa-graduation-cap', 'Overview of ECA membership development, training and learning pathways.'],
        'training' => ['Training & CPD', '/education-training.php', 'fa-chalkboard-teacher', 'Upcoming and completed ECA courses with dates, venues, fees and registration.'],
        'knowledge' => ['Knowledge centre', '/education-knowledge.php', 'fa-book', 'Articles, guides, checklists, templates, videos and FAQs for contractors.'],
        'learner' => ['Learner Portal', '/education-learner.php', 'fa-laptop', 'Access courses, progress, materials, assessments and certificates.'],
        'development' => ['Contractor development', '/education-development.php', 'fa-hard-hat', 'Programmes beyond single courses, from mentorship to youth and women contractors.'],
        'policy' => ['Industry & policy', '/education-policy.php', 'fa-balance-scale', 'What changed, why it matters, what contractors must do, and how ECA can help.'],
        'resources' => ['Resources', '/education-resources.php', 'fa-folder-open', 'Downloadable PDFs, presentations, guides, forms, checklists and videos.'],
        'documents' => ['Documents', '/documents/', 'fa-file-alt', 'Official association documents and downloadable files.'],
        'faq' => ['FAQ', '/faq.php', 'fa-question-circle', 'Answers to common membership, training and portal questions.'],
    ];
}

function eca_education_subnav(string $active = ''): void
{
    echo '<nav class="edu-subnav" aria-label="Education sections">';
    foreach (eca_education_sections() as $key => $item) {
        $class = $key === $active ? ' class="is-active"' : '';
        echo '<a href="' . eca_education_h($item[1]) . '"' . $class . '>' . eca_education_h($item[0]) . '</a>';
    }
    echo '</nav>';
}

function eca_education_empty(string $message): void
{
    echo '<div class="edu-empty"><p>' . eca_education_h($message) . '</p></div>';
}
