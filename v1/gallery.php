<?php require_once __DIR__ . '/includes/session.php'; ?>
<html lang="en">

<head>
    <meta charset="utf-8">
    <?php
    require_once __DIR__ . '/includes/public-seo.php';
    eca_public_head(
        'ECA Gallery',
        'View photos from Eswatini Contractors Association events, training programmes, industry engagements and member activities.',
        '/gallery.php'
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
    <link href="css/theme.css?v=20260918-8" rel="stylesheet">
    
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
$pageKicker = 'ECA gallery';
$pageTitle = 'Gallery';
$pageIntro = 'Photos from ECA events, training and industry programmes.';
$pageCrumb = 'Gallery';
require __DIR__ . '/includes/page-hero.php';
?>
<!-- ================== Gallery Section ================== -->
<section class="gallery-section py-5 bg-light">
  <div class="container">
    <div class="text-center mb-5 eca-page-intro mx-auto">
      <p class="eca-kicker justify-content-center">Moments</p>
      <h2 class="fw-bold">Our gallery</h2>
      <p>Explore moments from ECA events, training and industry gatherings.</p>
    </div>

    <div class="row g-4">
      <!-- Gallery Item -->
      <div class="col-6 col-md-4 col-lg-3">
        <a href="img/gallery/IMG_4634.jpg" class="gallery-item glightbox" data-gallery="gallery">
          <img src="img/gallery/IMG_4634.jpg" alt="ECA construction industry event" class="img-fluid rounded shadow" loading="lazy" decoding="async">
          <div class="overlay"><i class="bi bi-search"></i></div>
        </a>
      </div>

      <div class="col-6 col-md-4 col-lg-3">
        <a href="img/gallery/IMG_4682.jpg" class="gallery-item glightbox" data-gallery="gallery">
          <img src="img/gallery/IMG_4682.jpg" alt="ECA members at an association event" class="img-fluid rounded shadow" loading="lazy" decoding="async">
          <div class="overlay"><i class="bi bi-search"></i></div>
        </a>
      </div>

      <div class="col-6 col-md-4 col-lg-3">
        <a href="img/gallery/IMG_4733.jpg" class="gallery-item glightbox" data-gallery="gallery">
          <img src="img/gallery/IMG_4733.jpg" alt="ECA industry engagement event" class="img-fluid rounded shadow" loading="lazy" decoding="async">
          <div class="overlay"><i class="bi bi-search"></i></div>
        </a>
      </div>

      <div class="col-6 col-md-4 col-lg-3">
        <a href="img/gallery/IMG_5066.jpg" class="gallery-item glightbox" data-gallery="gallery">
          <img src="img/gallery/IMG_5066.jpg" alt="ECA contractor community gathering" class="img-fluid rounded shadow" loading="lazy" decoding="async">
          <div class="overlay"><i class="bi bi-search"></i></div>
        </a>
      </div>
      
           <div class="col-6 col-md-4 col-lg-3">
        <a href="img/gallery/_MG_9796.jpg" class="gallery-item glightbox" data-gallery="gallery">
          <img src="img/gallery/_MG_9646.jpg" alt="Participants at an ECA programme" class="img-fluid rounded shadow" loading="lazy" decoding="async">
          <div class="overlay"><i class="bi bi-search"></i></div>
        </a>
      </div>

      <div class="col-6 col-md-4 col-lg-3">
        <a href="img/gallery/_MG_9796.jpg" class="gallery-item glightbox" data-gallery="gallery">
          <img src="img/gallery/_MG_9796.jpg" alt="ECA programme participants" class="img-fluid rounded shadow" loading="lazy" decoding="async">
          <div class="overlay"><i class="bi bi-search"></i></div>
        </a>
      </div>

      <div class="col-6 col-md-4 col-lg-3">
        <a href="img/gallery/_MG_9739.jpg" class="gallery-item glightbox" data-gallery="gallery">
          <img src="img/gallery/_MG_9739.jpg" alt="ECA training and industry engagement" class="img-fluid rounded shadow" loading="lazy" decoding="async">
          <div class="overlay"><i class="bi bi-search"></i></div>
        </a>
      </div>

      <div class="col-6 col-md-4 col-lg-3">
        <a href="img/gallery/_MG_9577.jpg" class="gallery-item glightbox" data-gallery="gallery">
          <img src="img/gallery/_MG_9577.jpg" alt="ECA members during an industry event" class="img-fluid rounded shadow" loading="lazy" decoding="async">
          <div class="overlay"><i class="bi bi-search"></i></div>
        </a>
      </div>
      
          <div class="col-6 col-md-4 col-lg-3">
        <a href="img/gallery/_MG_9748.jpg" class="gallery-item glightbox" data-gallery="gallery">
          <img src="img/gallery/_MG_9748.jpg" alt="ECA association event" class="img-fluid rounded shadow" loading="lazy" decoding="async">
          <div class="overlay"><i class="bi bi-search"></i></div>
        </a>
      </div>
      
          <div class="col-6 col-md-4 col-lg-3">
        <a href="img/gallery/_MG_9832.jpg" class="gallery-item glightbox" data-gallery="gallery">
          <img src="img/gallery/_MG_9832.jpg" alt="ECA contractors at an association programme" class="img-fluid rounded shadow" loading="lazy" decoding="async">
          <div class="overlay"><i class="bi bi-search"></i></div>
        </a>
      </div>
          <div class="col-6 col-md-4 col-lg-3">
        <a href="img/gallery/_MG_9822.jpg" class="gallery-item glightbox" data-gallery="gallery">
          <img src="img/gallery/_MG_9822.jpg" alt="ECA stakeholder engagement" class="img-fluid rounded shadow" loading="lazy" decoding="async">
          <div class="overlay"><i class="bi bi-search"></i></div>
        </a>
      </div>
      
            <div class="col-6 col-md-4 col-lg-3">
        <a href="img/gallery/_MG_9819.jpg" class="gallery-item glightbox" data-gallery="gallery">
          <img src="img/gallery/_MG_9819.jpg" alt="ECA construction industry gathering" class="img-fluid rounded shadow" loading="lazy" decoding="async">
          <div class="overlay"><i class="bi bi-search"></i></div>
        </a>
      </div>
    </div>
  </div>
</section>

<!-- ================== CSS ================== -->
<style>
.gallery-item {
  position: relative;
  overflow: hidden;
  cursor: pointer;
  display: block;
  border-radius: 12px;
  transition: transform 0.3s ease;
}
.gallery-item img {
  transition: transform 0.5s ease;
}
.gallery-item:hover img {
  transform: scale(1.1);
}
.gallery-item .overlay {
  position: absolute;
  top: 0; left: 0;
  width: 100%; height: 100%;
  background: rgba(25,39,84,0.6);
  display: flex;
  justify-content: center;
  align-items: center;
  opacity: 0;
  transition: opacity 0.4s ease;
}
.gallery-item:hover .overlay {
  opacity: 1;
}
.gallery-item .overlay i {
  font-size: 2rem;
  color: #fff;
  transform: scale(0.8);
  transition: transform 0.3s ease;
}
.gallery-item:hover .overlay i {
  transform: scale(1.1);
}
</style>

<!-- ================== Lightbox JS ================== -->
<link href="https://cdn.jsdelivr.net/npm/glightbox/dist/css/glightbox.min.css" rel="stylesheet">
<script src="https://cdn.jsdelivr.net/npm/glightbox/dist/js/glightbox.min.js"></script>
<script>
document.addEventListener("DOMContentLoaded", () => {
  const lightbox = GLightbox({
    selector: '.glightbox',
    touchNavigation: true,
    loop: true,
    zoomable: true
  });
});
</script>


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
document.getElementById('searchBtn')?.addEventListener('click', performSearch);
document.getElementById('searchInput')?.addEventListener('keypress', function(e){
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