<?php
require_once 'config.php';
require_once __DIR__ . '/includes/portal-db.php';
require_once __DIR__ . '/includes/content.php';
$conn = eca_portal_pdo(false);

header('Content-Type: application/json; charset=UTF-8');

$search = trim((string) ($_POST['query'] ?? $_GET['search'] ?? ''));
$newsItems = [];
$published = eca_news_public_status_sql();

if ($conn) {
    try {
        if ($search === '') {
            $stmt = $conn->query('SELECT * FROM news WHERE ' . $published . ' ORDER BY `date` DESC LIMIT 10');
            $newsItems = $stmt->fetchAll(PDO::FETCH_ASSOC);
        } else {
            $like = '%' . $search . '%';
            $stmt = $conn->prepare('SELECT * FROM news WHERE (' . $published . ') AND (title LIKE :search OR summary LIKE :search) ORDER BY `date` DESC LIMIT 10');
            $stmt->execute([':search' => $like]);
            $newsItems = $stmt->fetchAll(PDO::FETCH_ASSOC);
        }
    } catch (Throwable $e) {
        $newsItems = [];
    }
}

$newsHTML = '';
$sliderHTML = '';
$delay = 0.1;
$first = true;

if ($newsItems) {
    foreach ($newsItems as $news) {
        $id = (int) ($news['id'] ?? 0);
        $href = $id > 0 ? ('news-details.php?id=' . $id) : 'news.php';
        $newsHTML .= '<div class="news-card wow fadeInUp" data-wow-delay="' . $delay . 's">';
        $newsHTML .= '<span class="news-date">' . htmlspecialchars(date('d M Y', strtotime((string) ($news['date'] ?? 'now')))) . '</span>';
        $newsHTML .= '<h3 class="news-title">' . htmlspecialchars((string) ($news['title'] ?? '')) . '</h3>';
        $newsHTML .= '<p class="news-author">by ' . htmlspecialchars((string) ($news['author'] ?? 'ECA')) . ' <span class="news-categories">in ' . htmlspecialchars((string) ($news['categories'] ?? '')) . '</span></p>';
        $newsHTML .= '<p class="news-summary">' . htmlspecialchars((string) ($news['summary'] ?? '')) . '</p>';
        $newsHTML .= '<a href="' . htmlspecialchars($href) . '" class="news-read-more">Continue reading →</a>';
        $newsHTML .= '</div>';

        $sliderHTML .= '<div class="u-slide ' . ($first ? 'active' : '') . '">';
        $sliderHTML .= '<img src="img/news.jpg" alt="' . htmlspecialchars((string) ($news['title'] ?? '')) . '">';
        $sliderHTML .= '<div class="u-caption">' . htmlspecialchars((string) ($news['title'] ?? '')) . '</div>';
        $sliderHTML .= '</div>';

        $first = false;
        $delay += 0.2;
    }
} else {
    $newsHTML = '<p>No news found.</p>';
    $sliderHTML = '';
}

echo json_encode(['news' => $newsHTML, 'slider' => $sliderHTML]);
