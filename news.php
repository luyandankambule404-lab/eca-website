<?php
require_once 'config.php';

$db = new Database();
$conn = $db->getConnection(false);
$currentPage = basename($_SERVER['PHP_SELF'] ?? 'news.php');

/**
 * Escape content before printing it in HTML.
 */
function e($value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

/**
 * Create a short, clean summary while remaining compatible with servers
 * where mbstring may not be enabled.
 */
function newsExcerpt($text, int $length = 180): string
{
    $text = trim(strip_tags((string) $text));

    if ($text === '') {
        return 'Read the latest update from the Eswatini Contractors Association.';
    }

    if (function_exists('mb_strimwidth')) {
        return mb_strimwidth($text, 0, $length, '…', 'UTF-8');
    }

    return strlen($text) > $length
        ? substr($text, 0, $length - 3) . '...'
        : $text;
}

/**
 * Allow normal relative links and valid HTTP/HTTPS links only.
 */
function safeNewsLink($link): string
{
    $link = trim((string) $link);

    if ($link === '') {
        return '#';
    }

    if (preg_match('~^(https?://)~i', $link)) {
        return filter_var($link, FILTER_VALIDATE_URL) ? $link : '#';
    }

    if (preg_match('~^[a-zA-Z0-9_./?&=#%-]+$~', $link)) {
        return $link;
    }

    return '#';
}

/**
 * Build a safe news image path with a site image as fallback.
 */
function newsImage($image): string
{
    $filename = basename(trim((string) $image));

    if ($filename === '') {
        return 'img/news.jpg';
    }

    return '../portal/uploads/news/' . rawurlencode($filename);
}

/**
 * Return the first category when multiple categories are stored together.
 */
function primaryCategory($categories): string
{
    $categories = trim((string) $categories);

    if ($categories === '') {
        return 'ECA Update';
    }

    $parts = preg_split('/[,|]/', $categories);
    return trim((string) ($parts[0] ?? 'ECA Update')) ?: 'ECA Update';
}

/**
 * Provide a valid display date without generating warnings for bad values.
 */
function displayNewsDate($date, string $format = 'd M Y'): string
{
    $timestamp = strtotime((string) $date);
    return $timestamp ? date($format, $timestamp) : 'Latest Update';
}

$newsItems = [];
if ($conn) {
    try {
        $query = "SELECT * FROM news ORDER BY date DESC LIMIT 24";
        $stmt = $conn->prepare($query);
        $stmt->execute();
        $newsItems = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (Throwable $exception) {
        error_log('ECA news page error: ' . $exception->getMessage());
        $newsItems = [];
    }
}

if (empty($newsItems)) {
    $newsItems = [
        [
            'title' => 'ECA Featured in the Times of Eswatini Business & Farming',
            'summary' => 'The Eswatini Contractors Association (ECA) was featured in the Times of Eswatini Business & Farming on 19 August 2026, highlighting challenges faced by local contractors in accessing major infrastructure opportunities.',
            'author' => 'ECA Communications',
            'categories' => 'Industry News',
            'date' => '2026-08-19',
            'image' => '',
            'link' => 'https://eca.co.sz/news.php',
        ],
        [
            'title' => 'DAY 1 OF TRAINING | MDB Standard Bidding Documents Compliance Training Underway',
            'summary' => 'Day 1 of the MDB Standard Bidding Documents Compliance Training is underway, equipping contractors with practical knowledge and hands-on skills to prepare compliant, responsive and competitive tenders.',
            'author' => 'ECA Communications',
            'categories' => 'Training',
            'date' => '2026-08-10',
            'image' => '',
            'link' => 'https://eca.co.sz/news.php',
        ],
        [
            'title' => 'Upcoming MDB Compliance Training to Strengthen Tender Success',
            'summary' => 'Contractors are encouraged to watch the training video and register for the upcoming MDB Standard Bidding Documents Compliance Training, designed to strengthen tender success.',
            'author' => 'ECA Communications',
            'categories' => 'Training',
            'date' => '2026-08-06',
            'image' => '',
            'link' => 'https://eca.co.sz/news.php',
        ],
        [
            'title' => 'ECA Featured in the Saturday Times of Eswatini',
            'summary' => 'The Eswatini Contractors Association was featured in the Saturday Times of Eswatini on 1 August 2026 for its continued work in building a stronger, more professional construction sector.',
            'author' => 'ECA Communications',
            'categories' => 'Media',
            'date' => '2026-08-01',
            'image' => '',
            'link' => 'https://eca.co.sz/news.php',
        ],
        [
            'title' => 'STAGE 4 TRAINING: Mastering Construction Contract Administration',
            'summary' => 'ECA continues its staged contractor development programme with Stage 4 training on mastering construction contract administration.',
            'author' => 'ECA Communications',
            'categories' => 'Training',
            'date' => '2026-05-13',
            'image' => '',
            'link' => 'https://eca.co.sz/news.php',
        ],
    ];
}

$featuredNews = $newsItems[0] ?? null;
$gridNews = array_slice($newsItems, 1);

$categories = [];
foreach ($newsItems as $item) {
    $category = primaryCategory($item['categories'] ?? '');
    $categories[strtolower($category)] = $category;
}
natcasesort($categories);
?>


<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>E.C.A | News</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="keywords" content="ECA news, Eswatini contractors, construction news, industry updates">
    <meta name="description" content="Latest news, announcements and industry updates from the Eswatini Contractors Association.">

    <link href="img/favicon.ico" rel="icon">

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Open+Sans:wght@400;500;600;700&family=Roboto:wght@500;700;900&display=swap" rel="stylesheet">

    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">

    <link href="lib/animate/animate.min.css" rel="stylesheet">
    <link href="lib/owlcarousel/assets/owl.carousel.min.css" rel="stylesheet">
    <link href="lib/tempusdominus/css/tempusdominus-bootstrap-4.min.css" rel="stylesheet">
    <link href="css/bootstrap.min.css" rel="stylesheet">
    <link href="css/style.css" rel="stylesheet">
    <link href="css/theme.css" rel="stylesheet">

    <style>
        :root {
            --eca-navy: #000066;
            --eca-navy-deep: #02033f;
            --eca-blue: #1426a7;
            --eca-red: #d90920;
            --eca-red-dark: #a90618;
            --eca-gold: #f4c542;
            --eca-ink: #111827;
            --eca-muted: #667085;
            --eca-border: #e6eaf2;
            --eca-surface: #ffffff;
            --eca-soft: #f5f7fb;
            --eca-shadow: 0 22px 55px rgba(0, 0, 102, 0.12);
        }

        * {
            box-sizing: border-box;
        }

        body {
            background: var(--eca-soft);
            color: var(--eca-ink);
            font-family: 'Open Sans', sans-serif;
        }

        a {
            transition: all 0.25s ease;
        }

        .header-custom {
            background: var(--eca-navy);
            color: #ffffff;
        }

        .header-custom small {
            color: #ffffff;
        }

        .blink-text {
            animation: softPulse 2.2s infinite;
        }

        @keyframes softPulse {
            0%, 100% { opacity: 1; }
            50% { opacity: 0.62; }
        }

        .btn-sm-square {
            width: 30px;
            height: 30px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-size: 14px;
        }

        .faq-btn-ui {
            display: inline-flex;
            align-items: center;
            min-height: 34px;
            padding: 6px 12px;
            margin-right: 8px;
            color: #ffffff;
            font-size: 13px;
            font-weight: 700;
            text-decoration: none;
            border: 1px solid rgba(255, 255, 255, 0.25);
            border-radius: 999px;
        }

        .faq-btn-ui:hover {
            color: var(--eca-navy);
            background: #ffffff;
        }

        /* Premium news hero */
        .news-hero {
            position: relative;
            min-height: 0;
            overflow: hidden;
            padding: 48px 0 28px;
            background:
                radial-gradient(circle at 88% 18%, rgba(217, 9, 32, 0.05), transparent 22%),
                radial-gradient(circle at 8% 80%, rgba(0, 0, 102, 0.05), transparent 24%),
                #f4f6f9;
        }

        .news-hero-image,
        .news-hero::before,
        .news-hero::after {
            display: none;
        }

        .news-hero-content {
            position: relative;
            z-index: 2;
            min-height: 0;
            display: flex;
            align-items: flex-end;
        }

        .hero-kicker {
            display: inline-flex;
            align-items: center;
            gap: 9px;
            margin-bottom: 18px;
            padding: 0;
            color: #d90920;
            font-size: 12px;
            font-weight: 800;
            letter-spacing: 0.12em;
            text-transform: uppercase;
            background: transparent;
            border: 0;
        }

        .hero-kicker::before {
            content: '';
            width: 8px;
            height: 8px;
            border-radius: 50%;
            background: var(--eca-red);
            box-shadow: 0 0 0 5px rgba(217, 9, 32, 0.18);
        }

        .news-hero h1 {
            max-width: 760px;
            margin: 0 0 16px;
            color: #000066;
            font-family: "Plus Jakarta Sans", "Roboto", sans-serif;
            font-size: clamp(1.8rem, 3.4vw, 2.6rem);
            font-weight: 800;
            line-height: 1.15;
            letter-spacing: -0.03em;
        }

        .news-hero h1 span {
            color: #d90920;
        }

        .news-hero p {
            max-width: 670px;
            margin: 0;
            color: #667085;
            font-size: 1.05rem;
            line-height: 1.75;
        }

        .hero-breadcrumb {
            display: inline-flex;
            align-items: center;
            gap: 10px;
            margin-top: 28px;
            padding: 9px 14px;
            color: rgba(255, 255, 255, 0.86);
            font-size: 13px;
            font-weight: 700;
            background: rgba(3, 6, 51, 0.35);
            border: 1px solid rgba(255, 255, 255, 0.14);
            border-radius: 999px;
        }

        .hero-breadcrumb a {
            color: #000066;
            text-decoration: none;
        }

        .hero-breadcrumb i {
            color: #ff6574;
        }

        /* Main news area */
        .news-main {
            position: relative;
            padding: 70px 0 90px;
        }

        .news-main::before {
            content: '';
            position: absolute;
            inset: 0;
            pointer-events: none;
            background-image: radial-gradient(rgba(0, 0, 102, 0.055) 1px, transparent 1px);
            background-size: 24px 24px;
            mask-image: linear-gradient(to bottom, #000, transparent 58%);
        }

.featured-media {
    position: relative;
    min-height: 420px;
    overflow: hidden;
    background: #071d35;
}

.featured-media > img,
. {
    position: relative;
    z-index: 1;
    background: #000;
}

.featured-marker,
.featured-marker {
    top: 18px;
    left: 18px;
    padding: 10px 14px;
    background: rgba(4, 31, 82, 0.88);
    box-shadow: 0 8px 25px rgba(0, 0, 0, 0.22);
}

@media (max-width: 767px) {
    .featured-media,
    .featured-media > img {
        min-height: 250px;
    }

    .featured-marker {
        top: 12px;
        left: 12px;
        font-size: 0.78rem;
    }

    . {
        right: 12px;
        bottom: 50px;
        font-size: 0.78rem;
    }
}

        .section-heading {
            position: relative;
            z-index: 1;
            display: flex;
            align-items: end;
            justify-content: space-between;
            gap: 24px;
            margin-bottom: 30px;
        }

        .section-label {
            display: inline-block;
            margin-bottom: 9px;
            color: var(--eca-red);
            font-size: 12px;
            font-weight: 800;
            letter-spacing: 0.14em;
            text-transform: uppercase;
        }

        .section-heading h2 {
            margin: 0;
            color: var(--eca-navy-deep);
            font-family: 'Roboto', sans-serif;
            font-size: clamp(2rem, 3.6vw, 3.2rem);
            font-weight: 900;
            letter-spacing: -0.035em;
        }

        .section-heading p {
            max-width: 610px;
            margin: 10px 0 0;
            color: var(--eca-muted);
            line-height: 1.7;
        }

        .news-count {
            flex: 0 0 auto;
            display: inline-flex;
            align-items: center;
            gap: 10px;
            padding: 10px 14px;
            color: var(--eca-navy);
            font-size: 13px;
            font-weight: 800;
            background: #ffffff;
            border: 1px solid var(--eca-border);
            border-radius: 999px;
            box-shadow: 0 8px 25px rgba(16, 24, 40, 0.06);
        }

        .news-count i {
            color: var(--eca-red);
        }

        .news-toolbar {
            position: relative;
            z-index: 2;
            display: grid;
            grid-template-columns: minmax(0, 1fr) minmax(220px, 280px);
            gap: 14px;
            margin-bottom: 34px;
            padding: 14px;
            background: rgba(255, 255, 255, 0.92);
            border: 1px solid rgba(230, 234, 242, 0.95);
            border-radius: 20px;
            box-shadow: 0 15px 45px rgba(16, 24, 40, 0.08);
            backdrop-filter: blur(12px);
        }

        .field-shell {
            position: relative;
        }

        .field-shell > i {
            position: absolute;
            top: 50%;
            left: 18px;
            z-index: 2;
            color: var(--eca-navy);
            transform: translateY(-50%);
        }

        .news-search-input,
        .news-filter-select {
            width: 100%;
            height: 54px;
            color: var(--eca-ink);
            font-size: 14px;
            font-weight: 600;
            background: #f8f9fc;
            border: 1px solid transparent;
            border-radius: 14px;
            outline: none;
            transition: all 0.25s ease;
        }

        .news-search-input {
            padding: 0 18px 0 50px;
        }

        .news-filter-select {
            padding: 0 42px 0 48px;
            appearance: none;
            cursor: pointer;
        }

        .select-arrow {
            position: absolute;
            top: 50%;
            right: 18px;
            pointer-events: none;
            color: var(--eca-muted) !important;
            transform: translateY(-50%);
        }

        .news-search-input:focus,
        .news-filter-select:focus {
            background: #ffffff;
            border-color: rgba(0, 0, 102, 0.28);
            box-shadow: 0 0 0 4px rgba(0, 0, 102, 0.07);
        }

        /* Featured story */
        .featured-story {
            position: relative;
            z-index: 1;
            display: grid;
            grid-template-columns: minmax(0, 1.15fr) minmax(340px, 0.85fr);
            min-height: 500px;
            margin-bottom: 36px;
            overflow: hidden;
            background: #ffffff;
            border: 1px solid var(--eca-border);
            border-radius: 28px;
            box-shadow: var(--eca-shadow);
        }

        .featured-media {
            position: relative;
            min-height: 500px;
            overflow: hidden;
            background: #dfe4ed;
        }

        .featured-media img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            transition: transform 0.8s cubic-bezier(.2,.8,.2,1);
        }

        .featured-story:hover .featured-media img {
            transform: scale(1.045);
        }

        .featured-media::after {
            content: '';
            position: absolute;
            inset: 0;
            pointer-events: none;
            background: linear-gradient(to top, rgba(0, 0, 70, 0.42), transparent 55%);
        }

        .featured-marker {
            position: absolute;
            top: 24px;
            left: 24px;
            z-index: 2;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 9px 13px;
            color: #ffffff;
            font-size: 11px;
            font-weight: 900;
            letter-spacing: 0.1em;
            text-transform: uppercase;
            background: var(--eca-red);
            border-radius: 999px;
            box-shadow: 0 12px 24px rgba(217, 9, 32, 0.28);
        }

        .featured-content {
            display: flex;
            flex-direction: column;
            justify-content: center;
            padding: clamp(34px, 5vw, 68px);
        }

        .story-meta {
            display: flex;
            flex-wrap: wrap;
            align-items: center;
            gap: 11px 18px;
            margin-bottom: 22px;
            color: var(--eca-muted);
            font-size: 12px;
            font-weight: 700;
        }

        .story-meta span {
            display: inline-flex;
            align-items: center;
            gap: 7px;
        }

        .story-meta i {
            color: var(--eca-red);
        }

        .category-chip {
            display: inline-flex !important;
            align-items: center;
            width: fit-content;
            padding: 7px 11px;
            color: var(--eca-navy) !important;
            font-size: 11px !important;
            font-weight: 900 !important;
            letter-spacing: 0.06em;
            text-transform: uppercase;
            background: rgba(0, 0, 102, 0.07);
            border: 1px solid rgba(0, 0, 102, 0.10);
            border-radius: 999px;
        }

        .featured-content h3 {
            margin: 0 0 20px;
            color: var(--eca-navy-deep);
            font-family: 'Roboto', sans-serif;
            font-size: clamp(1.8rem, 3.1vw, 3rem);
            font-weight: 900;
            line-height: 1.12;
            letter-spacing: -0.035em;
        }

        .featured-content p {
            margin: 0 0 28px;
            color: var(--eca-muted);
            font-size: 15px;
            line-height: 1.8;
        }

        .story-author {
            display: flex;
            align-items: center;
            gap: 12px;
            margin-bottom: 28px;
        }

        .author-avatar {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 42px;
            height: 42px;
            color: #ffffff;
            font-size: 14px;
            font-weight: 900;
            background: linear-gradient(135deg, var(--eca-navy), var(--eca-blue));
            border-radius: 13px;
            box-shadow: 0 8px 18px rgba(0, 0, 102, 0.20);
        }

        .author-copy small {
            display: block;
            margin-bottom: 2px;
            color: #98a2b3;
            font-size: 10px;
            font-weight: 800;
            letter-spacing: 0.08em;
            text-transform: uppercase;
        }

        .author-copy strong {
            color: var(--eca-ink);
            font-size: 13px;
        }

        .read-button {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            width: fit-content;
            min-height: 50px;
            padding: 0 21px;
            color: #ffffff;
            font-size: 13px;
            font-weight: 800;
            text-decoration: none;
            background: linear-gradient(135deg, var(--eca-navy), var(--eca-blue));
            border-radius: 14px;
            box-shadow: 0 13px 25px rgba(0, 0, 102, 0.22);
        }

        .read-button:hover {
            color: #ffffff;
            transform: translateY(-2px);
            box-shadow: 0 17px 30px rgba(0, 0, 102, 0.28);
        }

        .read-button i {
            transition: transform 0.25s ease;
        }

        .read-button:hover i {
            transform: translateX(4px);
        }

        /* News grid */
        .news-grid {
            position: relative;
            z-index: 1;
            display: grid;
            grid-template-columns: repeat(3, minmax(0, 1fr));
            gap: 24px;
        }

        .news-card-pro {
            display: flex;
            flex-direction: column;
            min-width: 0;
            overflow: hidden;
            background: #ffffff;
            border: 1px solid var(--eca-border);
            border-radius: 22px;
            box-shadow: 0 14px 38px rgba(16, 24, 40, 0.07);
            transition: transform 0.35s ease, box-shadow 0.35s ease, border-color 0.35s ease;
        }

        .news-card-pro:hover {
            transform: translateY(-8px);
            border-color: rgba(0, 0, 102, 0.16);
            box-shadow: 0 24px 52px rgba(0, 0, 102, 0.13);
        }

        .news-card-media {
            position: relative;
            height: 230px;
            overflow: hidden;
            background: #e9edf5;
        }

        .news-card-media img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            transition: transform 0.65s cubic-bezier(.2,.8,.2,1);
        }

        .news-card-pro:hover .news-card-media img {
            transform: scale(1.065);
        }

        .news-card-media::after {
            content: '';
            position: absolute;
            inset: 0;
            background: linear-gradient(to top, rgba(0, 0, 60, 0.32), transparent 48%);
        }

        .news-card-category {
            position: absolute;
            left: 16px;
            bottom: 16px;
            z-index: 2;
            max-width: calc(100% - 32px);
            padding: 7px 11px;
            overflow: hidden;
            color: #ffffff;
            font-size: 10px;
            font-weight: 900;
            letter-spacing: 0.08em;
            text-overflow: ellipsis;
            text-transform: uppercase;
            white-space: nowrap;
            background: rgba(0, 0, 102, 0.88);
            border: 1px solid rgba(255, 255, 255, 0.22);
            border-radius: 999px;
            backdrop-filter: blur(8px);
        }

        .news-card-body {
            display: flex;
            flex: 1;
            flex-direction: column;
            padding: 24px;
        }

        .news-card-date {
            display: flex;
            align-items: center;
            gap: 8px;
            margin-bottom: 14px;
            color: #7b8497;
            font-size: 11px;
            font-weight: 800;
            letter-spacing: 0.04em;
            text-transform: uppercase;
        }

        .news-card-date i {
            color: var(--eca-red);
        }

        .news-card-body h3 {
            margin: 0 0 13px;
            color: var(--eca-navy-deep);
            font-family: 'Roboto', sans-serif;
            font-size: 1.28rem;
            font-weight: 800;
            line-height: 1.35;
            letter-spacing: -0.02em;
        }

        .news-card-body p {
            display: -webkit-box;
            margin: 0 0 22px;
            overflow: hidden;
            color: var(--eca-muted);
            font-size: 13px;
            line-height: 1.75;
            -webkit-box-orient: vertical;
            -webkit-line-clamp: 3;
        }

        .news-card-footer {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 14px;
            margin-top: auto;
            padding-top: 18px;
            border-top: 1px solid #eef1f6;
        }

        .news-card-author {
            min-width: 0;
            color: #7b8497;
            font-size: 11px;
            font-weight: 700;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }

        .news-card-link {
            flex: 0 0 auto;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 39px;
            height: 39px;
            color: var(--eca-navy);
            text-decoration: none;
            background: rgba(0, 0, 102, 0.07);
            border-radius: 12px;
        }

        .news-card-link:hover {
            color: #ffffff;
            background: var(--eca-red);
            transform: translateX(2px);
        }

        .news-card-pro.is-hidden,
        .featured-story.is-hidden {
            display: none !important;
        }

        .load-more-wrap {
            position: relative;
            z-index: 1;
            display: flex;
            justify-content: center;
            margin-top: 38px;
        }

        .load-more-btn {
            display: inline-flex;
            align-items: center;
            gap: 10px;
            min-height: 50px;
            padding: 0 22px;
            color: var(--eca-navy);
            font-size: 13px;
            font-weight: 900;
            background: #ffffff;
            border: 1px solid rgba(0, 0, 102, 0.13);
            border-radius: 14px;
            box-shadow: 0 10px 28px rgba(16, 24, 40, 0.07);
        }

        .load-more-btn:hover {
            color: #ffffff;
            background: var(--eca-navy);
            border-color: var(--eca-navy);
        }

        .news-empty {
            position: relative;
            z-index: 1;
            display: none;
            padding: 60px 25px;
            text-align: center;
            background: #ffffff;
            border: 1px dashed #cfd5e2;
            border-radius: 24px;
        }

        .news-empty.show {
            display: block;
        }

        .news-empty-icon {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 72px;
            height: 72px;
            margin-bottom: 18px;
            color: var(--eca-navy);
            font-size: 27px;
            background: rgba(0, 0, 102, 0.07);
            border-radius: 22px;
        }

        .news-empty h3 {
            margin: 0 0 8px;
            color: var(--eca-navy-deep);
            font-family: 'Roboto', sans-serif;
            font-weight: 800;
        }

        .news-empty p {
            margin: 0;
            color: var(--eca-muted);
        }

        .news-cta {
            position: relative;
            z-index: 1;
            display: grid;
            grid-template-columns: minmax(0, 1fr) auto;
            align-items: center;
            gap: 30px;
            margin-top: 70px;
            padding: 38px 42px;
            overflow: hidden;
            background: linear-gradient(135deg, var(--eca-navy-deep), var(--eca-navy) 58%, #1618a5);
            border-radius: 26px;
            box-shadow: 0 24px 60px rgba(0, 0, 102, 0.23);
        }

        .news-cta::before {
            content: '';
            position: absolute;
            top: -120px;
            right: -80px;
            width: 310px;
            height: 310px;
            border-radius: 50%;
            background: rgba(255, 255, 255, 0.07);
            box-shadow: 0 0 0 45px rgba(255, 255, 255, 0.025);
        }

        .news-cta-copy,
        .news-cta-action {
            position: relative;
            z-index: 1;
        }

        .news-cta h3 {
            margin: 0 0 9px;
            color: #ffffff;
            font-family: 'Roboto', sans-serif;
            font-size: clamp(1.45rem, 2.5vw, 2.2rem);
            font-weight: 900;
        }

        .news-cta p {
            max-width: 690px;
            margin: 0;
            color: rgba(255, 255, 255, 0.74);
            line-height: 1.7;
        }

        .news-cta a {
            display: inline-flex;
            align-items: center;
            gap: 10px;
            min-height: 50px;
            padding: 0 20px;
            color: var(--eca-navy);
            font-size: 13px;
            font-weight: 900;
            text-decoration: none;
            background: #ffffff;
            border-radius: 14px;
            box-shadow: 0 12px 24px rgba(0, 0, 0, 0.16);
        }

        .news-cta a:hover {
            color: #ffffff;
            background: var(--eca-red);
            transform: translateY(-2px);
        }

        /* Footer */
        .footer-link:hover {
            color: #65d9ff !important;
            padding-left: 5px;
        }

        .footer-header {
            color: #ffffff !important;
            cursor: pointer;
        }

        .footer-header:hover {
            color: #65d9ff !important;
        }

        .btn-social {
            width: 40px;
            height: 40px;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: transform 0.3s ease, background 0.3s ease;
        }

        .btn-social:hover {
            transform: scale(1.08);
            background: #00c3ff;
            border-color: #00c3ff;
        }

        .whatsapp-float {
            position: fixed;
            right: 28px;
            bottom: 96px;
            z-index: 1000;
            display: flex;
            align-items: center;
            justify-content: center;
            width: 58px;
            height: 58px;
            color: #ffffff;
            font-size: 28px;
            text-decoration: none;
            background: #25d366;
            border-radius: 50%;
            box-shadow: 0 10px 30px rgba(37, 211, 102, 0.38);
            transition: transform 0.25s ease, box-shadow 0.25s ease;
        }

        .whatsapp-float:hover {
            color: #ffffff;
            transform: translateY(-4px) scale(1.04);
            box-shadow: 0 16px 36px rgba(37, 211, 102, 0.48);
        }

        @media (max-width: 1199.98px) {
            .news-grid {
                grid-template-columns: repeat(2, minmax(0, 1fr));
            }

            .featured-story {
                grid-template-columns: 1.05fr 0.95fr;
            }
        }

        @media (max-width: 991.98px) {
            .news-hero,
            .news-hero-content {
                min-height: 0;
            }

            .featured-story {
                grid-template-columns: 1fr;
            }

            .featured-media {
                min-height: 360px;
            }

            .news-cta {
                grid-template-columns: 1fr;
            }

            .news-cta-action {
                justify-self: start;
            }
        }

        @media (max-width: 767.98px) {
            .news-main {
                padding: 52px 0 70px;
            }

            .section-heading {
                align-items: flex-start;
                flex-direction: column;
                margin-bottom: 24px;
            }

            .news-toolbar {
                grid-template-columns: 1fr;
                border-radius: 17px;
            }

            .news-grid {
                grid-template-columns: 1fr;
            }

            .featured-story {
                border-radius: 22px;
            }

            .featured-media {
                min-height: 300px;
            }

            .featured-content {
                padding: 32px 25px;
            }

            .news-card-media {
                height: 245px;
            }

            .news-cta {
                margin-top: 52px;
                padding: 32px 25px;
                border-radius: 22px;
            }
        }

        @media (max-width: 575.98px) {
            .news-hero,
            .news-hero-content {
                min-height: 0;
            }

            .news-hero-content {
                padding: 45px 0;
            }

            .news-hero h1 {
                font-size: 2.55rem;
            }

            .news-hero p {
                font-size: 0.94rem;
            }

            .hero-breadcrumb {
                margin-top: 22px;
            }

            .news-card-media {
                height: 215px;
            }

            .whatsapp-float {
                right: 18px;
                bottom: 86px;
                width: 54px;
                height: 54px;
            }
        }

        @media (prefers-reduced-motion: reduce) {
            *, *::before, *::after {
                scroll-behavior: auto !important;
                animation-duration: 0.01ms !important;
                animation-iteration-count: 1 !important;
                transition-duration: 0.01ms !important;
            }
        }
    </style>
</head>
<body>
    <!-- Spinner Start -->
    <div id="spinner" class="show bg-white position-fixed translate-middle w-100 vh-100 top-50 start-50 d-flex align-items-center justify-content-center">
        <div class="spinner-grow text-primary" style="width: 3rem; height: 3rem;" role="status">
            <span class="sr-only">Loading...</span>
        </div>
    </div>
    <!-- Spinner End -->

    <?php require __DIR__ . '/includes/site-nav.php'; ?>

<!-- Navbar End -->

<?php
$pageKicker = 'Newsroom';
$pageTitle = 'Latest ECA news';
$pageIntro = 'Announcements, training updates and industry notices from the association.';
$pageCrumb = 'News';
require __DIR__ . '/includes/page-hero.php';
?>

    <!-- News Content Start -->
    <main class="news-main">
        <div class="container">
            <div class="section-heading">
                <div>
                    <span class="section-label">ECA Newsroom</span>
                    <h2>News, insights and announcements</h2>
                    <p>Stay informed about ECA programmes, construction-sector developments, member opportunities and official association notices.</p>
                </div>
                <div class="news-count" aria-live="polite">
                    <i class="bi bi-newspaper"></i>
                    <span id="visibleNewsCount"><?= count($newsItems) ?> article<?= count($newsItems) === 1 ? '' : 's' ?></span>
                </div>
            </div>

            <div class="news-toolbar" aria-label="News search and filter">
                <div class="field-shell">
                    <i class="bi bi-search"></i>
                    <input
                        type="search"
                        id="newsSearch"
                        class="news-search-input"
                        placeholder="Search by title, summary, author or category..."
                        autocomplete="off"
                    >
                </div>

                <div class="field-shell">
                    <i class="bi bi-funnel"></i>
                    <select id="categoryFilter" class="news-filter-select" aria-label="Filter news by category">
                        <option value="all">All categories</option>
                        <?php foreach ($categories as $categoryKey => $categoryLabel): ?>
                            <option value="<?= e($categoryKey) ?>"><?= e($categoryLabel) ?></option>
                        <?php endforeach; ?>
                    </select>
                    <i class="bi bi-chevron-down select-arrow"></i>
                </div>
            </div>

           <?php if ($featuredNews):
    $featuredCategory = primaryCategory($featuredNews['categories'] ?? '');

    $featuredSearch = strtolower(implode(' ', [
        $featuredNews['title'] ?? '',
        $featuredNews['summary'] ?? '',
        $featuredNews['author'] ?? '',
        $featuredNews['categories'] ?? ''
    ]));

    $featuredAuthor = trim(
        (string) ($featuredNews['author'] ?? 'ECA Communications')
    ) ?: 'ECA Communications';

    $featuredInitial = strtoupper(substr($featuredAuthor, 0, 1));

    $featuredImage = newsImage($featuredNews['image'] ?? '');
?>
    <article
        class="featured-story"
        data-news-item
        data-category="<?= e(strtolower($featuredCategory)) ?>"
        data-search="<?= e($featuredSearch) ?>"
    >
        <div class="featured-media">
            <img
                src="<?= e($featuredImage) ?>"
                alt="<?= e($featuredNews['title'] ?? 'ECA featured news') ?>"
                loading="eager"
                onerror="this.onerror=null;this.src='img/news.jpg';"
            >

            <span class="featured-marker">
                <i class="bi bi-stars"></i>
                Featured Story
            </span>
        </div>

        <div class="featured-content">
            <div class="story-meta">
                <span class="category-chip">
                    <?= e($featuredCategory) ?>
                </span>

                <span>
                    <i class="bi bi-calendar3"></i>
                    <?= e(displayNewsDate($featuredNews['date'] ?? '')) ?>
                </span>

                <span>
                        <i class="bi bi-clock"></i>
                        <?= max(
                            1,
                            (int) ceil(
                                str_word_count(
                                    strip_tags(
                                        (string) ($featuredNews['summary'] ?? '')
                                    )
                                ) / 200
                            )
                        ) ?> min read
                    </span>
            </div>

            <h3>
                <?= e($featuredNews['title'] ?? 'Latest ECA Update') ?>
            </h3>

            <p>
                <?= e(newsExcerpt($featuredNews['summary'] ?? '', 320)) ?>
            </p>

            <div class="story-author">
                <span class="author-avatar">
                    <?= e($featuredInitial ?: 'E') ?>
                </span>

                <span class="author-copy">
                    <small>Published by</small>
                    <strong><?= e($featuredAuthor) ?></strong>
                </span>
            </div>

            <?php
            $articleLink = safeNewsLink($featuredNews['link'] ?? '#');
            ?>

            <?php if ($articleLink !== '#'): ?>
                <a
                    href="<?= e($articleLink) ?>"
                    class="read-button"
                >
                    Read full article
                    <i class="bi bi-arrow-right"></i>
                </a>
            <?php endif; ?>
        </div>
    </article>
<?php endif; ?>

            <?php if ($gridNews): ?>
                <div id="newsGrid" class="news-grid">
                    <?php foreach ($gridNews as $index => $news):
                        $category = primaryCategory($news['categories'] ?? '');
                        $searchText = strtolower(implode(' ', [
                            $news['title'] ?? '',
                            $news['summary'] ?? '',
                            $news['author'] ?? '',
                            $news['categories'] ?? ''
                        ]));
                        $author = trim((string) ($news['author'] ?? 'ECA Communications')) ?: 'ECA Communications';
                    ?>
                        <article
                            class="news-card-pro"
                            data-news-item
                            data-grid-card
                            data-category="<?= e(strtolower($category)) ?>"
                            data-search="<?= e($searchText) ?>"
                            data-original-index="<?= (int) $index ?>"
                        >
                            <div class="news-card-media">
                                <img
                                    src="<?= e(newsImage($news['image'] ?? '')) ?>"
                                    alt="<?= e($news['title'] ?? 'ECA news') ?>"
                                    loading="lazy"
                                    onerror="this.onerror=null;this.src='img/news.jpg';"
                                >
                                <span class="news-card-category"><?= e($category) ?></span>
                            </div>

                            <div class="news-card-body">
                                <div class="news-card-date">
                                    <i class="bi bi-calendar-event"></i>
                                    <?= e(displayNewsDate($news['date'] ?? '')) ?>
                                </div>

                                <h3><?= e($news['title'] ?? 'ECA News Update') ?></h3>
                                <p><?= e(newsExcerpt($news['summary'] ?? '', 190)) ?></p>

                                <div class="news-card-footer">
                                    <span class="news-card-author">By <?= e($author) ?></span>
                                    <a
                                        href="<?= e(safeNewsLink($news['link'] ?? '#')) ?>"
                                        class="news-card-link"
                                        aria-label="Read <?= e($news['title'] ?? 'article') ?>"
                                        title="Read full article"
                                    >
                                        <i class="bi bi-arrow-up-right"></i>
                                    </a>
                                </div>
                            </div>
                        </article>
                    <?php endforeach; ?>
                </div>

                <?php if (count($gridNews) > 6): ?>
                    <div id="loadMoreWrap" class="load-more-wrap">
                        <button type="button" id="loadMoreNews" class="load-more-btn">
                            <i class="bi bi-plus-circle"></i> Load more news
                        </button>
                    </div>
                <?php endif; ?>
            <?php endif; ?>

            <div id="newsEmpty" class="news-empty <?= empty($newsItems) ? 'show' : '' ?>">
                <div class="news-empty-icon"><i class="bi bi-newspaper"></i></div>
                <h3><?= empty($newsItems) ? 'No news has been published yet' : 'No matching news found' ?></h3>
                <p><?= empty($newsItems) ? 'Please check back soon for official ECA updates and announcements.' : 'Try another keyword or choose a different category.' ?></p>
            </div>

            <section class="news-cta">
                <div class="news-cta-copy">
                    <h3>Need more information from ECA?</h3>
                    <p>Contact the Association for official clarification, membership support, training information or construction-industry enquiries.</p>
                </div>
                <div class="news-cta-action">
                    <a href="contact.php">Contact ECA <i class="bi bi-arrow-right"></i></a>
                </div>
            </section>
        </div>
    </main>
    <!-- News Content End -->

    <?php require __DIR__ . '/includes/site-footer.php'; ?>


    <a href="#" class="btn btn-lg btn-primary btn-lg-square rounded-circle back-to-top"><i class="bi bi-arrow-up"></i></a>

    <a href="https://wa.me/26876702898" target="_blank" rel="noopener" class="whatsapp-float" aria-label="Chat with ECA on WhatsApp">
        <i class="bi bi-whatsapp"></i>
    </a>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="lib/wow/wow.min.js"></script>
    <script src="lib/easing/easing.min.js"></script>
    <script src="lib/waypoints/waypoints.min.js"></script>
    <script src="lib/counterup/counterup.min.js"></script>
    <script src="lib/owlcarousel/owl.carousel.min.js"></script>
    <script src="lib/tempusdominus/js/moment.min.js"></script>
    <script src="lib/tempusdominus/js/moment-timezone.min.js"></script>
    <script src="lib/tempusdominus/js/tempusdominus-bootstrap-4.min.js"></script>
    <script src="js/main.js"></script>

    <script>
        (function () {
            'use strict';

            const searchInput = document.getElementById('newsSearch');
            const categoryFilter = document.getElementById('categoryFilter');
            const allNewsItems = Array.from(document.querySelectorAll('[data-news-item]'));
            const gridCards = Array.from(document.querySelectorAll('[data-grid-card]'));
            const emptyState = document.getElementById('newsEmpty');
            const countLabel = document.getElementById('visibleNewsCount');
            const loadMoreButton = document.getElementById('loadMoreNews');
            const loadMoreWrap = document.getElementById('loadMoreWrap');

            let visibleGridLimit = 6;

            function normalize(value) {
                return (value || '').toString().toLowerCase().trim();
            }

            function matchesFilters(item, query, category) {
                const searchableText = normalize(item.dataset.search);
                const itemCategory = normalize(item.dataset.category);
                const matchesQuery = !query || searchableText.includes(query);
                const matchesCategory = category === 'all' || itemCategory === category;

                return matchesQuery && matchesCategory;
            }

            function updateNewsView(resetLimit) {
                if (resetLimit) {
                    visibleGridLimit = 6;
                }

                const query = normalize(searchInput ? searchInput.value : '');
                const category = normalize(categoryFilter ? categoryFilter.value : 'all') || 'all';
                let matchedCount = 0;
                let shownGridCount = 0;
                let totalMatchedGrid = 0;

                allNewsItems.forEach(function (item) {
                    if (matchesFilters(item, query, category)) {
                        matchedCount += 1;
                        if (item.hasAttribute('data-grid-card')) {
                            totalMatchedGrid += 1;
                        }
                    }
                });

                allNewsItems.forEach(function (item) {
                    const matched = matchesFilters(item, query, category);

                    if (!matched) {
                        item.classList.add('is-hidden');
                        return;
                    }

                    if (item.hasAttribute('data-grid-card')) {
                        if (shownGridCount < visibleGridLimit) {
                            item.classList.remove('is-hidden');
                            shownGridCount += 1;
                        } else {
                            item.classList.add('is-hidden');
                        }
                    } else {
                        item.classList.remove('is-hidden');
                    }
                });

                if (countLabel) {
                    countLabel.textContent = matchedCount + ' article' + (matchedCount === 1 ? '' : 's');
                }

                if (emptyState) {
                    emptyState.classList.toggle('show', matchedCount === 0);
                }

                if (loadMoreWrap) {
                    loadMoreWrap.style.display = totalMatchedGrid > visibleGridLimit ? 'flex' : 'none';
                }
            }

            if (searchInput) {
                searchInput.addEventListener('input', function () {
                    updateNewsView(true);
                });
            }

            if (categoryFilter) {
                categoryFilter.addEventListener('change', function () {
                    updateNewsView(true);
                });
            }

            if (loadMoreButton) {
                loadMoreButton.addEventListener('click', function () {
                    visibleGridLimit += 6;
                    updateNewsView(false);
                });
            }

            window.addEventListener('load', function () {
                const spinner = document.getElementById('spinner');
                if (spinner) {
                    spinner.classList.remove('show');
                }
            });

            updateNewsView(false);
        })();
    </script>

    <link rel="stylesheet" href="css/pop-upstyle.css">
    <script src="js/pop-upscript.js" defer></script>
</body>
</html>