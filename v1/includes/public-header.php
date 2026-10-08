<?php
require_once __DIR__ . '/responsive-assets.php';
eca_responsive_assets();
if (!isset($currentPage) || $currentPage === '') {
    $currentPage = basename($_SERVER['SCRIPT_NAME'] ?? $_SERVER['PHP_SELF'] ?? 'index.php');
}

$navExt = $navExt ?? (preg_match('/\.html$/i', (string) $currentPage) ? 'html' : 'php');

$p = static function (string $name) use ($navExt): string {
    return $name . '.' . $navExt;
};

$navIs = static function (array $pages) use ($currentPage): bool {
    $base = strtolower((string) $currentPage);
    $stem = preg_replace('/\.(php|html)$/i', '', $base);
    foreach ($pages as $page) {
        $pageStem = strtolower(preg_replace('/\.(php|html)$/i', '', (string) $page));
        if ($stem === $pageStem) {
            return true;
        }
    }
    return false;
};

$homeHref = $p('index');
$searchAction = $p('directory');
$aboutOn = $navIs(['about', 'about-bod', 'about-by-laws', 'about-mission', 'about-structure', 'about-history', 'contact']);
$connectOn = $navIs(['directory', 'balingani-directory', 'tenders', 'tender', 'events']);
$memberOn = $navIs(['application', 'apply_artisan', 'renewal', 'checklist', 'verify', 'track', 'membership-registration']);
$registerOn = $navIs(['membership-registration', 'application', 'apply_artisan', 'renewal']);
$eduOn = $navIs([
    'resources', 'faq', 'documents', 'training', 'wellness', 'education', 'education-training', 'education-course',
    'education-knowledge', 'education-article', 'education-learner', 'education-development', 'education-programme',
    'education-policy', 'education-resources', 'digital-intelligence', 'professionalization', 'wellness-inclusivity',
    'technical-support',
]);
$newsOn = $navIs(['news', 'gallery', 'news-details']);
$advocacyOn = $navIs(['advocacy']);
$homeOn = $navIs(['index']);
$uriPath = strtolower((string) (parse_url($_SERVER['REQUEST_URI'] ?? '', PHP_URL_PATH) ?: ''));
if (
    str_starts_with($uriPath, '/wellness')
    || str_starts_with($uriPath, '/client/wellness')
    || str_starts_with($uriPath, '/education')
    || str_starts_with($uriPath, '/digital-intelligence')
    || str_starts_with($uriPath, '/professionalization')
    || str_starts_with($uriPath, '/wellness-inclusivity')
    || str_starts_with($uriPath, '/technical-support')
) {
    $eduOn = true;
    $homeOn = false;
}
?>
    <header class="eca-public-header">
        <?php require __DIR__ . '/utility-bar.php'; ?>
        <div class="eca-masthead">
            <a class="eca-landing-logo" href="<?= htmlspecialchars($homeHref, ENT_QUOTES, 'UTF-8') ?>" aria-label="ECA home">
                <img src="/img/ecalogo.png" alt="Eswatini Contractors Association" width="754" height="326" decoding="async">
            </a>
            <button class="eca-landing-toggle" type="button" aria-expanded="false" aria-controls="ecaLandingNav" aria-label="Open menu">
                <i class="bi bi-list"></i>
            </button>
            <nav class="eca-landing-nav eca-mega-nav" id="ecaLandingNav">
                <a href="<?= htmlspecialchars($homeHref, ENT_QUOTES, 'UTF-8') ?>"<?= $homeOn ? ' class="is-active"' : '' ?>>Home</a>

                <div class="eca-nav-drop eca-mega-drop<?= $aboutOn ? ' is-current' : '' ?>">
                    <button class="eca-nav-link" type="button" aria-expanded="false" aria-haspopup="true">About ECA <i class="bi bi-chevron-down"></i></button>
                    <div class="eca-nav-menu eca-mega-menu">
                        <p class="eca-mega-label">The association</p>
                        <a href="<?= htmlspecialchars($p('about'), ENT_QUOTES, 'UTF-8') ?>">About ECA</a>
                        <a href="/about-history.php">Our History</a>
                        <a href="/about-mission.php">Mission &amp; purpose</a>
                        <a href="/about-structure.php">Organizational structure</a>
                        <a href="<?= htmlspecialchars($p('about-bod'), ENT_QUOTES, 'UTF-8') ?>">Leadership</a>
                        <a href="about-by-laws.php">Bylaws</a>
                        <a href="<?= htmlspecialchars($p('contact'), ENT_QUOTES, 'UTF-8') ?>">Contact</a>
                    </div>
                </div>

                <div class="eca-nav-drop eca-mega-drop<?= $connectOn ? ' is-current' : '' ?>">
                    <button class="eca-nav-link" type="button" aria-expanded="false" aria-haspopup="true">Connect <i class="bi bi-chevron-down"></i></button>
                    <div class="eca-nav-menu eca-mega-menu eca-mega-menu-wide">
                        <div class="eca-mega-col">
                            <p class="eca-mega-label">Find a contractor</p>
                            <a href="<?= htmlspecialchars($p('directory'), ENT_QUOTES, 'UTF-8') ?>">Member directory</a>
                            <a href="<?= htmlspecialchars($p('balingani-directory'), ENT_QUOTES, 'UTF-8') ?>">Balingani</a>
                        </div>
                        <div class="eca-mega-col">
                            <p class="eca-mega-label">Opportunities</p>
                            <a href="/tenders.php">Tenders</a>
                            <a href="/events.php">Events</a>
                        </div>
                    </div>
                </div>

                <div class="eca-nav-drop eca-mega-drop<?= $memberOn ? ' is-current' : '' ?>">
                    <button class="eca-nav-link" type="button" aria-expanded="false" aria-haspopup="true">Membership <i class="bi bi-chevron-down"></i></button>
                    <div class="eca-nav-menu eca-mega-menu">
                        <p class="eca-mega-label">Members</p>
                        <a href="<?= htmlspecialchars($p('directory'), ENT_QUOTES, 'UTF-8') ?>">Member directory</a>
                        <a href="/verify.php">Verify membership</a>
                        <a href="/track.php">Track application</a>
                        <a href="<?= htmlspecialchars($p('checklist'), ENT_QUOTES, 'UTF-8') ?>">Membership checklist</a>
                    </div>
                </div>

                <a href="/advocacy.php"<?= $advocacyOn ? ' class="is-active"' : '' ?>>Advocacy</a>

                <div class="eca-nav-drop eca-mega-drop<?= $eduOn ? ' is-current' : '' ?>">
                    <button class="eca-nav-link" type="button" aria-expanded="false" aria-haspopup="true">Our work <i class="bi bi-chevron-down"></i></button>
                    <div class="eca-nav-menu eca-mega-menu eca-mega-menu-wide">
                        <div class="eca-mega-col">
                            <p class="eca-mega-label">Organizational pillars</p>
                            <a href="/digital-intelligence.php">Digital Intelligence</a>
                            <a href="/professionalization.php">Professionalization</a>
                            <a href="/technical-support.php">Technical Support</a>
                            <a href="/wellness-inclusivity.php">Wellness &amp; Inclusivity</a>
                        </div>
                        <div class="eca-mega-col">
                            <p class="eca-mega-label">Learning &amp; care</p>
                            <a href="/education-knowledge.php">Knowledge centre</a>
                            <a href="/education-training.php">Training &amp; CPD</a>
                            <a href="/wellness/">Wellness Hub</a>
                            <a href="/client/wellness/">Member wellness events</a>
                        </div>
                    </div>
                </div>

                <div class="eca-nav-drop eca-mega-drop<?= $newsOn ? ' is-current' : '' ?>">
                    <button class="eca-nav-link" type="button" aria-expanded="false" aria-haspopup="true">News <i class="bi bi-chevron-down"></i></button>
                    <div class="eca-nav-menu eca-mega-menu">
                        <p class="eca-mega-label">Updates</p>
                        <a href="<?= htmlspecialchars($p('news'), ENT_QUOTES, 'UTF-8') ?>">News</a>
                        <a href="<?= htmlspecialchars($p('gallery'), ENT_QUOTES, 'UTF-8') ?>">Gallery</a>
                    </div>
                </div>

                <a href="/membership-registration.php"<?= $registerOn ? ' class="is-active"' : '' ?>>Register</a>
            </nav>
        </div>
    </header>
    <script src="/js/public-nav.js?v=20261008-dirlink" defer></script>
    <script src="/js/login-menu.js?v=20260929-drop" defer></script>
    <script src="/js/directory-live-search.js?v=20260923-dir" defer></script>
