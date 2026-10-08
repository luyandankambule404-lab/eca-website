<?php require_once __DIR__ . '/includes/session.php'; ?>
<html lang="en">

<head>
    <meta charset="utf-8">
    <?php
    require_once __DIR__ . '/includes/public-seo.php';
    eca_public_head(
        'ECA Member Directory',
        'Search the directory of contractors registered with the Eswatini Contractors Association and find member contact and company details.',
        '/directory.php'
    );
    ?>
    <meta content="width=device-width, initial-scale=1.0" name="viewport">
    <meta content="" name="keywords">

    <!-- Favicon -->
    <link href="img/favicon.ico" rel="icon">

    <!-- Google Web Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com/">
    <link rel="preconnect" href="https://fonts.gstatic.com/" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Open+Sans:wght@400;500&amp;family=Roboto:wght@500;700;900&amp;display=swap" rel="stylesheet"> 

    <!-- Icon Font Stylesheet -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.10.0/css/all.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.4.1/font/bootstrap-icons.css" rel="stylesheet">

    <!-- Libraries Stylesheet -->
    <link href="lib/animate/animate.min.css" rel="stylesheet">
    <link href="lib/owlcarousel/assets/owl.carousel.min.css" rel="stylesheet">
    <link href="lib/tempusdominus/css/tempusdominus-bootstrap-4.min.css" rel="stylesheet" />

    <!-- Customized Bootstrap Stylesheet -->
    <link href="css/bootstrap.min.css" rel="stylesheet">

    <!-- Template Stylesheet -->
    <link href="css/style.css" rel="stylesheet">
    <link href="css/theme.css?v=20260923-dir" rel="stylesheet">
    
        <?php require_once __DIR__ . '/includes/responsive-assets.php'; eca_responsive_assets(); ?>
<!-- Slick CSS -->
<link rel="stylesheet" type="text/css" href="https://cdn.jsdelivr.net/npm/slick-carousel@1.8.1/slick/slick.css"/>
<link rel="stylesheet" type="text/css" href="https://cdn.jsdelivr.net/npm/slick-carousel@1.8.1/slick/slick-theme.css"/>

    <?php require_once __DIR__ . '/includes/responsive-assets.php'; eca_responsive_assets(); ?>
<!-- jQuery (required for Slick) -->
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>

<!-- Slick JS -->
<script src="https://cdn.jsdelivr.net/npm/slick-carousel@1.8.1/slick/slick.min.js"></script>
<style>

/* =============================
   HERO SLIDER BASE
   ============================= */
   
   .header-custom {
    background-color: #192754; /* Blue similar to the logo */
    color: white;
}
.header-custom small {
    color: white;
}
    .footer a {
        text-decoration: none;
        transition: color 0.3s ease;
    }
    .footer a:hover {
        color: #1abc9c !important; /* Aqua hover color */
    }
    .btn-social {
        width: 38px;
        height: 38px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        border-radius: 50%;
    }
        .faq-btn-ui{
    display:inline-block;
    background:#ffffff;
    color:#b30000;
    padding:10px 20px;
    border-radius:8px;
    font-weight:600;
    text-decoration:none;
    border:2px solid #b30000;
    transition:0.3s;
    box-shadow:0 4px 12px rgba(0,0,0,0.08);
}

.faq-btn-ui:hover{
    background:#b30000;
    color:white;
    transform:translateY(-2px);
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

	
	
<?php
$pageKicker = 'Directory';
$pageTitle = 'Membership directory';
$pageIntro = 'Search verified ECA contractors by company name, region or industry classification.';
$pageCrumb = 'Directory';
require __DIR__ . '/includes/page-hero.php';
?>
	
    <!-- Header End -->
<?php
include "config.php";

$database = new Database();
$conn = $database->getConnection(false);

require_once __DIR__ . '/includes/company-data.php';

$search   = trim((string) ($_GET['search'] ?? ''));
$industry = eca_request_industry();
$limit  = ($search !== '' || $industry !== '') ? 2000 : 30;
$page   = isset($_GET['page']) ? (int)$_GET['page'] : 1;
if ($page < 1) {
    $page = 1;
}
$offset = ($page - 1) * $limit;
$companies = [];
$totalPages = 0;
$total = 0;
$typeCounts = [];
$allCount = 0;
$chipBase = '/directory.php';

if ($conn) {
    $typeCounts = eca_directory_type_counts($conn, 'members');
    $result = eca_fetch_directory($conn, $search, $industry, $limit, $offset, 'members', $search !== '');
    $companies = $result['rows'];
    $total = $result['total'];
    $totalPages = $total > 0 ? (int) ceil($total / $limit) : 0;
    $allCount = ($search === '' && $industry === '') ? $total : eca_fetch_directory($conn, '', '', 1, 0, 'members')['total'];
}
?>

<!-- Bootstrap Icons -->
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">

<style>
    /* Search & Filter Card */
    .search-card {
        border-radius: 15px;
        padding: 20px;
        background: #ffffff;
        box-shadow: 0 6px 15px rgba(0,0,0,0.1);
        transition: transform 0.3s ease, box-shadow 0.3s ease;
        margin-bottom: 30px;
    }
    .search-card:hover {
        transform: translateY(-5px);
        box-shadow: 0 10px 25px rgba(0,0,0,0.15);
    }
    .search-card .form-control,
    .search-card .form-select {
        border-radius: 10px;
        padding-left: 40px;
    }
    .search-icon {
        position: absolute;
        left: 15px;
        top: 50%;
        transform: translateY(-50%);
        color: #6c757d;
    }
    .btn-search {
        border-radius: 10px;
        font-weight: 600;
        transition: all 0.3s ease;
    }
    .btn-search:hover {
        background: #d50d0e;
        transform: scale(1.05);
    }
    .type-chips { display: flex; flex-wrap: wrap; gap: 8px; margin: 0 0 16px; }
    .type-chips a { display: inline-flex; align-items: center; gap: 8px; padding: 8px 14px; border-radius: 999px; background: #f4f6f9; border: 1px solid #d0d5dd; color: #192754; text-decoration: none; font-weight: 700; }
    .type-chips a.is-active { background: #192754; color: #fff; border-color: #192754; }
    .type-chips span { font-size: 12px; font-weight: 800; opacity: .75; }
    .filter-summary { margin: 0 0 18px; font-weight: 600; color: #192754; }

    /* Company Cards */
    .company-card {
        border-radius: 15px;
        overflow: hidden;
        transition: transform 0.3s ease, box-shadow 0.3s ease;
    }
    .company-card:hover {
        transform: translateY(-8px);
        box-shadow: 0 8px 25px rgba(0,0,0,0.2);
    }
    .company-card .card-header {
        background: linear-gradient(135deg, #192754, #192754);
        color: #fff;
        font-weight: bold;
        text-transform: uppercase;
        font-size: 1.1rem;
        border: none;
        padding: 12px;
        text-align: center;
    }
    .company-card .card-body p {
        margin-bottom: 8px;
        font-size: 0.95rem;
    }
    .company-card .card-footer {
        background: #f8f9fa;
        border-top: 1px solid #e5e5e5;
        text-align: center;
    }
    .company-card .card-footer a {
        margin: 0 8px;
        text-decoration: none;
        color: #192754;
        font-weight: 600;
        transition: color 0.3s ease;
    }
    .company-card .card-footer a:hover {
        color: #d50d0e;
    }
</style>

<div class="container-xxl py-5">
    <div class="eca-page-intro">
        <p class="eca-kicker">Directory</p>
        <h2><i class="bi bi-building"></i> Registered ECA members</h2>
        <p>Click All for every member, or Civil / Electrical/Mechanical / another type for that list only.</p>
    </div>

    <!-- Search & Filter -->
    <div class="search-card">
        <?php require __DIR__ . '/includes/type-chips.php'; ?>
        <p class="filter-summary" id="directoryFilterSummary"><?= $industry === '' ? 'Showing all companies (' . (int) $total . ').' : 'Showing only ' . htmlspecialchars($industry) . ' companies (' . (int) $total . ').' ?></p>
        <div class="eca-search-suggest-wrap">
        <form class="eca-directory-search" method="get" action="/directory.php" role="search">
            <label class="eca-sr-only" for="directorySearch">Search the contractor directory</label>
            <input id="directorySearch" type="search" name="search" value="<?php echo htmlspecialchars($search); ?>" placeholder="Company or membership number" autocomplete="off" aria-autocomplete="list">
            <select name="industry" aria-label="Industry">
                <option value="">All Industries</option>
                <option value="Building"   <?php if(strcasecmp($industry,"Building")===0) echo "selected"; ?>>Building</option>
                <option value="Civil"      <?php if(strcasecmp($industry,"Civil")===0) echo "selected"; ?>>Civil</option>
                <option value="Electrical/Mechanical" <?php if(in_array(strtolower($industry), ['electrical','mechanical','electrical/mechanical'], true)) echo "selected"; ?>>Electrical/Mechanical</option>
                <option value="Specialist" <?php if(strcasecmp($industry,"Specialist")===0) echo "selected"; ?>>Specialist</option>
            </select>
            <button type="submit">Search directory</button>
        </form>
        </div>
    </div>

    <!-- Results -->
    <div class="row g-4" id="directoryResults">
        <?php if (!empty($companies)): ?>
            <?php foreach ($companies as $row): ?>
                <div class="col-md-4">
                    <div class="card company-card h-100 shadow-sm">
                        <!-- Header -->
                        <div class="card-header">
                            <a href="/contractor.php?id=<?= (int) ($row['id'] ?? 0) ?>" style="color:inherit;text-decoration:none;"><?php echo htmlspecialchars($row['TradingName'] ?? ''); ?></a>
                        </div>
                        <!-- Body -->
                        <div class="card-body">
                            <p><i class="bi bi-diagram-3"></i> 
                               <strong>Classification:</strong> <?php
                                   $rowType = trim((string) ($row['Clasification'] ?? ''));
                                   if ($rowType !== '') {
                                       echo '<a href="' . htmlspecialchars(eca_type_filter_url($chipBase, '', $rowType)) . '">' . htmlspecialchars($rowType) . '</a>';
                                   }
                               ?>
                            </p>
                            <p><i class="bi bi-check-circle-fill"></i> 
                               <strong>Status:</strong> <span class="badge">Active</span>
                            </p>
                            <p><i class="bi bi-envelope-fill"></i> 
                               <strong>Email:</strong> <?php echo htmlspecialchars($row['EmailAddress'] ?? ''); ?>
                            </p>
                            <p><i class="bi bi-telephone-fill"></i> 
                               <strong>Phone:</strong> <?php echo htmlspecialchars($row['Cellphone'] ?? ''); ?>
                            </p>
                            <p><i class="bi bi-geo-alt-fill"></i> 
                               <strong>Region:</strong> <?php echo htmlspecialchars($row['Region'] ?? ''); ?>
                            </p>
                        </div>
                        <!-- Footer -->
                        <div class="card-footer">
                            <a href="mailto:<?php echo htmlspecialchars($row['EmailAddress']); ?>">
                                <i class="bi bi-envelope"></i> Email
                            </a>
                            <a href="tel:<?php echo htmlspecialchars($row['Cellphone']); ?>">
                                <i class="bi bi-telephone"></i> Call
                            </a>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php else: ?>
            <p id="directoryEmpty"><?= $conn ? 'No results found.' : 'The live member list is unavailable in this preview. It will appear once the database is connected.' ?></p>
        <?php endif; ?>
    </div>

    <!-- Pagination -->
    <?php if ($totalPages > 1): ?>
    <nav id="directoryPagination">
        <ul class="pagination justify-content-center mt-4">
            <?php for ($i=1; $i <= $totalPages; $i++): ?>
                <li class="page-item <?php if($i==$page) echo 'active'; ?>">
                    <a class="page-link" 
                       href="?page=<?php echo $i; ?>&search=<?php echo urlencode($search); ?>&industry=<?php echo urlencode($industry); ?>">
                        <?php echo $i; ?>
                    </a>
                </li>
            <?php endfor; ?>
        </ul>
    </nav>
    <?php endif; ?>
</div>


<?php require __DIR__ . '/includes/site-footer.php'; ?>


<style>
.service-item {
    background-color: #ffffff;
    border-radius: 15px;
    padding: 40px 30px;
    box-shadow: 0 8px 20px rgba(0,0,0,0.15);
    transition: transform 0.3s ease, box-shadow 0.3s ease;
    position: relative;
    overflow: hidden;
    height: 100%;
}

.card-accent {
    position: absolute;
    top: 0;
    left: 0;
    width: 100%;
    height: 8px;
    border-top-left-radius: 15px;
    border-top-right-radius: 15px;
}

.accent-blue { background: linear-gradient(90deg, #2a3f73, #192754); }
.accent-green { background: linear-gradient(90deg, #192754, #192754); }
.accent-orange { background: linear-gradient(90deg, #d50d0e, #192754); }

.service-item h4 {
    color: #192754;
    font-weight: 700;
}

.service-item p {
    color: #333;
}

.service-item a.btn {
    font-weight: 600;
    transition: all 0.3s ease;
}

.service-item:hover {
    transform: translateY(-10px);
    box-shadow: 0 15px 30px rgba(0,0,0,0.25);
}

.service-item a.btn:hover {
    background-color: #0d6efd;
    color: #fff;
}

/* Footer Styles */
.footer-link:hover {
    color: #00c3ff !important;
    padding-left: 5px;
    transition: all 0.3s ease;
}
/* Ensure headers stay white */
.footer-header {
    color: #fff !important;
    cursor: pointer;
}
.footer-header:hover {
    color: #00c3ff !important; /* Optional hover effect */
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
    transform: scale(1.1);
    background: #00c3ff;
    border-color: #00c3ff;
}
</style>



    <!-- Back to Top -->
    <a href="#" class="btn btn-lg btn-primary btn-lg-square rounded-circle back-to-top"><i class="bi bi-arrow-up"></i></a>


    <!-- JavaScript Libraries -->
    <script src="https://code.jquery.com/jquery-3.4.1.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.0.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="lib/wow/wow.min.js"></script>
    <script src="lib/easing/easing.min.js"></script>
    <script src="lib/waypoints/waypoints.min.js"></script>
    <script src="lib/counterup/counterup.min.js"></script>
    <script src="lib/owlcarousel/owl.carousel.min.js"></script>
    <script src="lib/tempusdominus/js/moment.min.js"></script>
    <script src="lib/tempusdominus/js/moment-timezone.min.js"></script>
    <script src="lib/tempusdominus/js/tempusdominus-bootstrap-4.min.js"></script>

    <!-- Template Javascript -->
    <script src="js/main.js"></script>
	
	<script>
	window.addEventListener('load', function (){ 
	var spinner = document.getElementById('spinner');
	if (spinner) {
	spinner.classList.remove('show');
	}
	});
	
const uSlides = document.querySelectorAll('.unique-slider .u-slide');
const uPrevBtn = document.querySelector('.unique-slider .u-prev');
const uNextBtn = document.querySelector('.unique-slider .u-next');
const prefersReducedMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
let uIndex = 0;

function showUSlide(index) {
    uSlides.forEach((slide, i) => {
        slide.classList.toggle('active', i === index);
    });
}

uPrevBtn?.addEventListener('click', () => {
    uIndex = (uIndex - 1 + uSlides.length) % uSlides.length;
    showUSlide(uIndex);
});

uNextBtn?.addEventListener('click', () => {
    uIndex = (uIndex + 1) % uSlides.length;
    showUSlide(uIndex);
});

// Auto-play
if (uSlides.length && uPrevBtn && uNextBtn && !prefersReducedMotion) setInterval(() => {
    uIndex = (uIndex + 1) % uSlides.length;
    showUSlide(uIndex);
}, 5000);

// Initialize
if (uSlides.length && uPrevBtn && uNextBtn) showUSlide(uIndex);


const newsContainer = document.getElementById('newsContainer');
const sliderContainer = document.getElementById('sliderContainer');
let slideInterval;

function renderSlider() {
    if (!sliderContainer) return;
    let slides = sliderContainer.querySelectorAll('.u-slide');
    if (!slides.length) return;
    let currentIndex = 0;

    function showSlide(index) {
        slides.forEach((slide, i) => slide.classList.toggle('active', i === index));
    }

    function nextSlide() {
        currentIndex = (currentIndex + 1) % slides.length;
        showSlide(currentIndex);
    }

    clearInterval(slideInterval);
    if (!prefersReducedMotion && sliderContainer.querySelector('.u-prev') && sliderContainer.querySelector('.u-next')) slideInterval = setInterval(nextSlide, 4000);

    // Prev/Next buttons
    const prevBtn = sliderContainer.querySelector('.u-prev');
    const nextBtn = sliderContainer.querySelector('.u-next');
    if (!prevBtn || !nextBtn) return;

    prevBtn.onclick = () => { currentIndex = (currentIndex - 1 + slides.length) % slides.length; showSlide(currentIndex); resetInterval(); };
    nextBtn.onclick = () => { nextSlide(); resetInterval(); };

    function resetInterval() {
        clearInterval(slideInterval);
        if (!prefersReducedMotion) slideInterval = setInterval(nextSlide, 4000);
    }
}

// AJAX search
function performSearch() {
    const searchInput = document.getElementById('searchInput');
    if (!searchInput || !newsContainer || !sliderContainer) return;
    let query = searchInput.value;

    fetch('search-news.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: 'query=' + encodeURIComponent(query)
    })
    .then(res => res.json())
    .then(data => {
        newsContainer.innerHTML = data.news;
        sliderContainer.innerHTML = data.slider + '<button class="u-prev">&#10094;</button><button class="u-next">&#10095;</button>';
        renderSlider();
    });
}

// Event listeners
var searchBtn = document.getElementById('searchBtn');
var searchInput = document.getElementById('searchInput');
if (searchBtn) searchBtn.addEventListener('click', performSearch);
if (searchInput) searchInput.addEventListener('keypress', function(e){
    if(e.key === 'Enter') performSearch();
});

// Initialize slider on page load
renderSlider();


	</script>

<style>

/* Hover zoom */

/* Glow animation */
</style>


<!-- Bootstrap Icons -->
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">

	
	
	

<!-- Link your JS and CSS -->
<link rel="stylesheet" href="css/pop-upstyle.css">
<script src="js/pop-upscript.js" defer></script>
</body>


<!-- Mirrored from eca.co.sz/ by HTTrack Website Copier/3.x [XR&CO'2014], Sun, 24 Aug 2025 15:38:19 GMT -->
</html>