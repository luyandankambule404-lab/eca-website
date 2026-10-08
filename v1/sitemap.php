<?php
require_once __DIR__ . '/includes/env.php';
header('Content-Type: application/xml; charset=UTF-8');
$base = rtrim(eca_env('ECA_PUBLIC_URL', 'https://eca.co.sz'), '/');
$pages = [
    '/',
    '/about.html',
    '/about-bod.html',
    '/about-by-laws.php',
    '/code-of-conduct.php',
    '/privacy.php',
    '/directory.html',
    '/balingani-directory.html',
    '/membership-registration.php',
    '/application.html',
    '/renewal.html',
    '/checklist.html',
    '/resources.html',
    '/faq.html',
    '/news.html',
    '/gallery.html',
    '/contact.html',
    '/verify.php',
    '/track.php',
    '/tenders.php',
    '/events.php',
    '/training.php',
    '/education.php',
    '/education-training.php',
    '/education-knowledge.php',
    '/education-learner.php',
    '/education-development.php',
    '/education-policy.php',
    '/education-resources.php',
    '/advocacy.php',
    '/advocacy-updates.php',
    '/about-mission.php',
    '/about-structure.php',
    '/about-history.php',
    '/digital-intelligence.php',
    '/professionalization.php',
    '/wellness-inclusivity.php',
    '/technical-support.php',
    '/wellness/',
    '/wellness/mental-health.php',
    '/wellness/holistic.php',
    '/wellness/library.php',
    '/wellness/check-in.php',
    '/wellness/toolbox.php',
    '/wellness/support.php',
    '/wellness/groups.php',
    '/documents/',
];
echo '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
echo '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . "\n";
foreach ($pages as $path) {
    $loc = htmlspecialchars($base . $path, ENT_XML1 | ENT_QUOTES, 'UTF-8');
    echo "  <url><loc>{$loc}</loc></url>\n";
}
echo '</urlset>';
