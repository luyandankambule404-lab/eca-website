<?php
require_once 'config.php';
$db = new Database();
$conn = $db->getConnection();

$search = $_POST['query'] ?? '';
$search = "%$search%";

$query = "SELECT * FROM news WHERE title LIKE :search OR summary LIKE :search ORDER BY date DESC LIMIT 10";
$stmt = $conn->prepare($query);
$stmt->bindParam(':search', $search);
$stmt->execute();
$newsItems = $stmt->fetchAll(PDO::FETCH_ASSOC);

$newsHTML = '';
$sliderHTML = '';
$delay = 0.1;
$first = true;

if ($newsItems):
    foreach ($newsItems as $news):
        // News cards
        $newsHTML .= '<div class="news-card wow fadeInUp" data-wow-delay="'.$delay.'s">';
        $newsHTML .= '<span class="news-date">'.date('d M Y', strtotime($news['date'])).'</span>';
        $newsHTML .= '<h3 class="news-title">'.htmlspecialchars($news['title']).'</h3>';
        $newsHTML .= '<p class="news-author">by '.htmlspecialchars($news['author']).' <span class="news-categories">in '.htmlspecialchars($news['categories']).'</span></p>';
        $newsHTML .= '<p class="news-summary">'.htmlspecialchars($news['summary']).'</p>';
        $newsHTML .= '<a href="'.$news['link'].'" class="news-read-more">Continue reading →</a>';
        $newsHTML .= '</div>';

        // Slider
        $sliderHTML .= '<div class="u-slide '.($first?'active':'').'">';
        $sliderHTML .= '<img src="img/'.$news['image'].'" alt="'.htmlspecialchars($news['title']).'">';
        $sliderHTML .= '<div class="u-caption">'.htmlspecialchars($news['title']).'</div>';
        $sliderHTML .= '</div>';

        $first = false;
        $delay += 0.2;
    endforeach;
else:
    $newsHTML = '<p>No news found.</p>';
    $sliderHTML = '';
endif;

echo json_encode(['news' => $newsHTML, 'slider' => $sliderHTML]);
