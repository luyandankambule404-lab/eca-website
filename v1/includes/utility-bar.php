<?php
if (!isset($searchAction) || $searchAction === '') {
    $searchAction = '/directory.php';
}
$searchValue = htmlspecialchars((string) ($_GET['search'] ?? ''), ENT_QUOTES, 'UTF-8');
?>
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
                    <div class="eca-search-suggest-wrap">
                        <form class="eca-utility-search" action="<?= htmlspecialchars($searchAction, ENT_QUOTES, 'UTF-8') ?>" method="get" role="search">
                            <label class="eca-sr-only" for="ecaSiteSearch">Search the member directory</label>
                            <input id="ecaSiteSearch" type="search" name="search" value="<?= $searchValue ?>" autocomplete="off" required pattern=".*\S.*" title="Enter a company name or membership number" aria-autocomplete="list">
                            <button type="submit" aria-label="Search directory"><i class="bi bi-search" aria-hidden="true"></i></button>
                        </form>
                    </div>
                    <?php
                    require_once __DIR__ . '/session.php';
                    require_once __DIR__ . '/hub.php';
                    eca_public_signed_in_back();
                    ?>
                    <?php require __DIR__ . '/login-menu.php'; ?>
                </div>
            </div>
        </div>
