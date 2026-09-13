<?php
if (!isset($currentPage) || $currentPage === '') {
    $currentPage = basename($_SERVER['PHP_SELF'] ?? 'index.php');
}
?>
<div class="eca-topbar d-none d-lg-block">
    <div class="container eca-topbar-inner">
        <span>Eswatini Contractors Association</span>
        <div class="eca-topbar-right">
            <a href="mailto:info@eca.co.sz"><i class="fa fa-envelope"></i> info@eca.co.sz</a>
            <a href="tel:+26824044987"><i class="fa fa-phone-alt"></i> +268 2404 4987</a>
            <div class="eca-top-social">
                <a href="#" aria-label="Facebook"><i class="fab fa-facebook-f"></i></a>
                <a href="#" aria-label="Twitter"><i class="fab fa-twitter"></i></a>
                <a href="#" aria-label="LinkedIn"><i class="fab fa-linkedin-in"></i></a>
            </div>
        </div>
    </div>
</div>

<nav class="navbar navbar-expand-lg bg-white navbar-light sticky-top p-0 eca-mainnav">
    <a href="index.php" class="navbar-brand d-flex align-items-center px-4 px-lg-5">
        <img class="img-fluid" src="img/ecalogo.png" alt="Eswatini Contractors Association">
    </a>

    <button type="button" class="navbar-toggler me-4" data-bs-toggle="collapse" data-bs-target="#navbarCollapse">
        <span class="navbar-toggler-icon"></span>
    </button>

    <div class="collapse navbar-collapse" id="navbarCollapse">
        <div class="navbar-nav ms-auto p-4 p-lg-0">
            <a href="index.php" class="nav-item nav-link <?= ($currentPage == 'index.php') ? 'active' : '' ?>">Home</a>

            <a href="application.php" class="nav-item nav-link mobile-membership <?= ($currentPage == 'application.php') ? 'active' : '' ?>">Membership Application</a>
            <a href="apply_artisan.php" class="nav-item nav-link mobile-membership <?= ($currentPage == 'apply_artisan.php') ? 'active' : '' ?>">Artisan Application</a>
            <a href="renewal.php" class="nav-item nav-link mobile-membership <?= ($currentPage == 'renewal.php') ? 'active' : '' ?>">Membership Renewal</a>
            <a href="client/index.php" class="nav-item nav-link mobile-membership">Login</a>

            <div class="nav-item dropdown">
                <a href="#"
                   class="nav-link dropdown-toggle <?= in_array($currentPage, ['about.php','about-bod.php']) ? 'active' : '' ?>"
                   data-bs-toggle="dropdown">
                   About ECA
                </a>
                <ul class="dropdown-menu rounded-0 rounded-bottom m-0">
                    <li>
                        <a href="about.php" class="dropdown-item <?= ($currentPage == 'about.php') ? 'active' : '' ?>">About ECA</a>
                    </li>
                    <li>
                        <a href="about-bod.php" class="dropdown-item <?= ($currentPage == 'about-bod.php') ? 'active' : '' ?>">ECA Executive Committee</a>
                    </li>
                </ul>
            </div>

            <div class="nav-item dropdown desktop-membership">
                <a href="#"
                   class="nav-link dropdown-toggle <?= in_array($currentPage, ['application.php','renewal.php','directory.php','apply_artisan.php']) ? 'active' : '' ?>"
                   data-bs-toggle="dropdown">
                   Membership
                </a>
                <div class="dropdown-menu rounded-0 rounded-bottom m-0">
                    <a href="application.php" class="dropdown-item <?= ($currentPage == 'application.php') ? 'active' : '' ?>">Membership Application</a>
                    <a href="renewal.php" class="dropdown-item <?= ($currentPage == 'renewal.php') ? 'active' : '' ?>">Membership Renewal</a>
                    <a href="directory.php" class="dropdown-item <?= ($currentPage == 'directory.php') ? 'active' : '' ?>">Membership Directory</a>
                </div>
            </div>

            <a href="balingani-directory.php" class="nav-item nav-link <?= ($currentPage == 'balingani-directory.php') ? 'active' : '' ?>">Balingani</a>
            <a href="resources.php" class="nav-item nav-link <?= ($currentPage == 'resources.php') ? 'active' : '' ?>">Resources</a>
            <a href="news.php" class="nav-item nav-link <?= ($currentPage == 'news.php') ? 'active' : '' ?>">News</a>
            <a href="gallery.php" class="nav-item nav-link <?= ($currentPage == 'gallery.php') ? 'active' : '' ?>">Gallery</a>
            <a href="contact.php" class="nav-item nav-link <?= ($currentPage == 'contact.php') ? 'active' : '' ?>">Contact</a>
        </div>

        <a href="client/index.php" class="eca-portal-login d-none d-lg-inline-flex">Portal login</a>
    </div>
</nav>
