<?php
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
$aboutOn = $navIs(['about', 'about-bod', 'about-by-laws', 'contact']);
$connectOn = $navIs(['directory', 'balingani-directory']);
$memberOn = $navIs(['application', 'apply_artisan', 'renewal', 'checklist', 'pricing']);
$eduOn = $navIs(['resources', 'faq', 'documents']);
$newsOn = $navIs(['news', 'gallery', 'news-details']);
$homeOn = $navIs(['index']);
?>
    <header class="eca-public-header">
        <div class="eca-utility">
            <div class="eca-utility-inner">
                <div class="eca-utility-left">
                    <span class="eca-utility-brand">Eswatini Contractors Association</span>
                    <div class="eca-utility-social" aria-label="Social media">
                        <a href="https://www.facebook.com/people/Eswatini-Contractors-Association/61582863643851/" target="_blank" rel="noopener noreferrer" aria-label="Facebook"><i class="fab fa-facebook-f" aria-hidden="true"></i></a>
                        <a href="https://www.instagram.com/eca.sz/" target="_blank" rel="noopener noreferrer" aria-label="Instagram"><i class="fab fa-instagram" aria-hidden="true"></i></a>
                        <a href="https://www.linkedin.com/company/eswatini-contractors-association-eca" target="_blank" rel="noopener noreferrer" aria-label="LinkedIn"><i class="fab fa-linkedin-in" aria-hidden="true"></i></a>
                    </div>
                </div>
                <div class="eca-utility-tools">
                    <form class="eca-utility-search" action="<?= htmlspecialchars($searchAction, ENT_QUOTES, 'UTF-8') ?>" method="get" role="search">
                        <label class="eca-sr-only" for="ecaSiteSearch">Search the member directory</label>
                        <input id="ecaSiteSearch" type="search" name="search" placeholder="Search contractors" value="<?= htmlspecialchars((string) ($_GET['search'] ?? ''), ENT_QUOTES, 'UTF-8') ?>">
                        <button type="submit" aria-label="Search directory"><i class="bi bi-search" aria-hidden="true"></i></button>
                    </form>
                    <?php require __DIR__ . '/login-menu.php'; ?>
                </div>
            </div>
        </div>
        <div class="eca-masthead">
            <a class="eca-landing-logo" href="<?= htmlspecialchars($homeHref, ENT_QUOTES, 'UTF-8') ?>">
                <img src="img/ecalogo.png" alt="Eswatini Contractors Association">
            </a>
            <button class="eca-landing-toggle" type="button" aria-expanded="false" aria-controls="ecaLandingNav" aria-label="Open menu">
                <i class="bi bi-list"></i>
            </button>
            <nav class="eca-landing-nav eca-mega-nav" id="ecaLandingNav">
                <a href="<?= htmlspecialchars($homeHref, ENT_QUOTES, 'UTF-8') ?>" class="<?= $homeOn ? 'is-active' : '' ?>">Home</a>

                <div class="eca-nav-drop eca-mega-drop<?= $aboutOn ? ' is-current' : '' ?>">
                    <button class="eca-nav-link<?= $aboutOn ? ' is-active' : '' ?>" type="button" aria-expanded="false" aria-haspopup="true">About ECA <i class="bi bi-chevron-down"></i></button>
                    <div class="eca-nav-menu eca-mega-menu">
                        <p class="eca-mega-label">The association</p>
                        <a href="<?= htmlspecialchars($p('about'), ENT_QUOTES, 'UTF-8') ?>">About ECA</a>
                        <a href="<?= htmlspecialchars($p('about-bod'), ENT_QUOTES, 'UTF-8') ?>">Executive committee</a>
                        <a href="about-by-laws.php">Bylaws</a>
                        <a href="<?= htmlspecialchars($p('contact'), ENT_QUOTES, 'UTF-8') ?>">Contact</a>
                    </div>
                </div>

                <div class="eca-nav-drop eca-mega-drop<?= $connectOn ? ' is-current' : '' ?>">
                    <button class="eca-nav-link<?= $connectOn ? ' is-active' : '' ?>" type="button" aria-expanded="false" aria-haspopup="true">Connect <i class="bi bi-chevron-down"></i></button>
                    <div class="eca-nav-menu eca-mega-menu">
                        <p class="eca-mega-label">Find a contractor</p>
                        <a href="<?= htmlspecialchars($p('directory'), ENT_QUOTES, 'UTF-8') ?>">Member directory</a>
                        <a href="<?= htmlspecialchars($p('balingani-directory'), ENT_QUOTES, 'UTF-8') ?>">Balingani</a>
                    </div>
                </div>

                <div class="eca-nav-drop eca-mega-drop<?= $memberOn ? ' is-current' : '' ?>">
                    <button class="eca-nav-link<?= $memberOn ? ' is-active' : '' ?>" type="button" aria-expanded="false" aria-haspopup="true">Membership <i class="bi bi-chevron-down"></i></button>
                    <div class="eca-nav-menu eca-mega-menu eca-mega-menu-wide">
                        <div class="eca-mega-col">
                            <p class="eca-mega-label">Join ECA</p>
                            <a href="<?= htmlspecialchars($p('application'), ENT_QUOTES, 'UTF-8') ?>">Apply</a>
                            <a href="apply_artisan.php">Artisan application</a>
                            <a href="<?= htmlspecialchars($p('renewal'), ENT_QUOTES, 'UTF-8') ?>">Renew</a>
                        </div>
                        <div class="eca-mega-col">
                            <p class="eca-mega-label">Members</p>
                            <a href="<?= htmlspecialchars($p('directory'), ENT_QUOTES, 'UTF-8') ?>">Member directory</a>
                            <a href="<?= htmlspecialchars($p('checklist'), ENT_QUOTES, 'UTF-8') ?>">Membership checklist</a>
                            <a href="<?= htmlspecialchars($p('pricing'), ENT_QUOTES, 'UTF-8') ?>">Pricing</a>
                        </div>
                    </div>
                </div>

                <div class="eca-nav-drop eca-mega-drop<?= $eduOn ? ' is-current' : '' ?>">
                    <button class="eca-nav-link<?= $eduOn ? ' is-active' : '' ?>" type="button" aria-expanded="false" aria-haspopup="true">Education <i class="bi bi-chevron-down"></i></button>
                    <div class="eca-nav-menu eca-mega-menu eca-mega-menu-wide">
                        <div class="eca-mega-col">
                            <p class="eca-mega-label">Learning</p>
                            <a href="<?= htmlspecialchars($p('resources'), ENT_QUOTES, 'UTF-8') ?>">Resources</a>
                            <a href="/documents/">Documents</a>
                            <a href="<?= htmlspecialchars($p('faq'), ENT_QUOTES, 'UTF-8') ?>">FAQ</a>
                        </div>
                        <div class="eca-mega-col">
                            <p class="eca-mega-label">CPD training</p>
                            <a href="/cpd/login.php">CPD contractor</a>
                            <a href="/cpd/admin_login.php">CPD staff</a>
                        </div>
                    </div>
                </div>

                <div class="eca-nav-drop eca-mega-drop<?= $newsOn ? ' is-current' : '' ?>">
                    <button class="eca-nav-link<?= $newsOn ? ' is-active' : '' ?>" type="button" aria-expanded="false" aria-haspopup="true">News <i class="bi bi-chevron-down"></i></button>
                    <div class="eca-nav-menu eca-mega-menu">
                        <p class="eca-mega-label">Updates</p>
                        <a href="<?= htmlspecialchars($p('news'), ENT_QUOTES, 'UTF-8') ?>">News</a>
                        <a href="<?= htmlspecialchars($p('gallery'), ENT_QUOTES, 'UTF-8') ?>">Gallery</a>
                    </div>
                </div>
            </nav>
        </div>
    </header>
    <script src="/js/public-nav.js" defer></script>
