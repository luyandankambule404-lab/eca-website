<?php
require_once __DIR__ . '/includes/session.php';
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/includes/portal-db.php';
require_once __DIR__ . '/includes/public-seo.php';
require_once __DIR__ . '/includes/content.php';

$currentPage = 'news-details.php';
$id = (int) ($_GET['id'] ?? 0);
$conn = eca_portal_pdo(false);
$article = null;
$related = [];

function eca_news_h($value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

function eca_news_image($image): string
{
    $filename = basename(trim((string) $image));
    if ($filename === '') {
        return 'img/news.jpg';
    }
    foreach (['uploads/news/' . $filename, 'img/' . $filename] as $relative) {
        if (is_file(__DIR__ . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $relative))) {
            return $relative;
        }
    }
    return 'img/news.jpg';
}

if ($conn && $id > 0) {
    try {
        $stmt = $conn->prepare('SELECT * FROM news WHERE id = :id AND ' . eca_news_public_status_sql() . ' LIMIT 1');
        $stmt->execute([':id' => $id]);
        $article = $stmt->fetch(PDO::FETCH_ASSOC) ?: null;

        $relatedStmt = $conn->prepare('SELECT * FROM news WHERE id <> :id AND ' . eca_news_public_status_sql() . ' ORDER BY `date` DESC LIMIT 4');
        $relatedStmt->execute([':id' => $id]);
        $related = $relatedStmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (Throwable $e) {
        error_log('News details error: ' . $e->getMessage());
    }
}

if (!$article) {
    http_response_code(404);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title><?= $article ? eca_news_h($article['title']) . ' | ECA News' : 'Article not found | ECA' ?></title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <?php if ($article): ?>
        <?php
        $articleDescription = mb_substr(trim((string) ($article['summary'] ?? '')), 0, 180);
        if ($articleDescription === '') {
            $articleDescription = 'News and updates from the Eswatini Contractors Association.';
        }
        eca_public_meta(
            (string) $article['title'],
            $articleDescription,
            '/news-details.php?id=' . $id,
            'article',
            '/' . eca_news_image($article['image'] ?? '')
        );
        eca_json_ld([
            '@context' => 'https://schema.org',
            '@type' => 'NewsArticle',
            'headline' => (string) $article['title'],
            'description' => $articleDescription,
            'datePublished' => (string) ($article['date'] ?? ''),
            'author' => [
                '@type' => 'Organization',
                'name' => (string) ($article['author'] ?? 'Eswatini Contractors Association'),
            ],
            'publisher' => [
                '@type' => 'Organization',
                'name' => 'Eswatini Contractors Association',
                'logo' => ['@type' => 'ImageObject', 'url' => eca_public_url('/img/ecalogo.png')],
            ],
            'mainEntityOfPage' => eca_public_url('/news-details.php?id=' . $id),
            'image' => eca_public_url('/' . eca_news_image($article['image'] ?? '')),
        ]);
        ?>
    <?php else: ?>
        <meta name="robots" content="noindex, follow">
    <?php endif; ?>
    <link href="img/favicon.ico" rel="icon">
    <link href="https://fonts.googleapis.com/css2?family=Open+Sans:wght@400;500;600;700&family=Roboto:wght@500;700;900&display=swap" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.10.0/css/all.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
    <link href="css/bootstrap.min.css" rel="stylesheet">
    <link href="css/style.css" rel="stylesheet">
    <link href="css/theme.css?v=20260918-8" rel="stylesheet">
    <?php require_once __DIR__ . '/includes/responsive-assets.php'; eca_responsive_assets(); ?>
</head>
<body>
<?php require __DIR__ . '/includes/site-nav.php'; ?>
<?php
$pageKicker = 'News';
$pageTitle = $article ? (string) $article['title'] : 'Article not found';
$pageIntro = $article
    ? trim((string) ($article['author'] ?? 'ECA Communications')) . ' · ' . date('d M Y', strtotime((string) ($article['date'] ?? 'now')))
    : 'This news item is not available on the local site.';
$pageCrumb = 'News details';
require __DIR__ . '/includes/page-hero.php';
?>

<div class="container-xxl py-4">
    <div class="container">
        <?php if (!$article): ?>
            <p>The requested article could not be found.</p>
            <p><a href="news.php">Back to news</a></p>
        <?php else: ?>
            <article class="eca-news-article">
                <img src="<?= eca_news_h(eca_news_image($article['image'] ?? '')) ?>" alt="<?= eca_news_h((string) ($article['title'] ?? 'ECA news')) ?>" fetchpriority="high" decoding="async" style="width:100%;max-height:420px;object-fit:cover;border-radius:12px;margin-bottom:24px;">
                <p class="eca-kicker"><?= eca_news_h($article['categories'] ?? 'ECA Update') ?></p>
                <div style="font-size:1.05rem;line-height:1.8;color:#1f2937;white-space:pre-wrap;"><?= eca_news_h(str_ireplace(['https://www.eca.co.sz/cpd', 'https://eca.co.sz/cpd'], '/cpd', (string) ($article['summary'] ?? ''))) ?></div>
                <p style="margin-top:24px;"><a href="news.php">Back to all news</a></p>
            </article>

            <?php if ($related): ?>
                <h2 style="margin-top:48px;color:#192754;">Related updates</h2>
                <div class="row g-4 mt-1">
                    <?php foreach ($related as $item): ?>
                        <div class="col-md-6 col-lg-3">
                            <a href="news-details.php?id=<?= (int) $item['id'] ?>" style="text-decoration:none;color:inherit;">
                                <strong style="color:#192754;"><?= eca_news_h($item['title'] ?? 'ECA update') ?></strong>
                                <p class="mb-0" style="color:#667085;"><?= eca_news_h(date('d M Y', strtotime((string) ($item['date'] ?? 'now')))) ?></p>
                            </a>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        <?php endif; ?>
    </div>
</div>

<?php require __DIR__ . '/includes/site-footer.php'; ?>
</body>
</html>
