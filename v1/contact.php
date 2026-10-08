<?php
require_once __DIR__ . '/includes/session.php';
require_once __DIR__ . '/includes/enquiry-routing.php';
$enquiryTypeSelected = eca_enquiry_type_from_request();
$enquiryTypes = eca_enquiry_types();
?>
<html lang="en">


<!-- Mirrored from eca.co.sz/ by HTTrack Website Copier/3.x [XR&CO'2014], Sun, 24 Aug 2025 15:37:54 GMT -->
<head>
    <meta charset="utf-8">
    <?php
    require_once __DIR__ . '/includes/public-seo.php';
    eca_public_head(
        'Contact ECA',
        'Contact the Eswatini Contractors Association secretariat for membership, contractor directory, training and general enquiries.',
        '/contact.php'
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
    <link href="css/theme.css?v=20260926-forms" rel="stylesheet">
    
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
$pageKicker = 'Get in touch';
$pageTitle = 'Contact us';
$pageIntro = 'Reach the Eswatini Contractors Association for membership, training and general enquiries.';
$pageCrumb = 'Contact';
require __DIR__ . '/includes/page-hero.php';
?>
    
    


<!-- CSS -->
<style>
.banner-responsive {
    position: relative;
    width: 100%;
    min-height: 250px;
    max-height: 500px;
    display: flex;
    align-items: center;
    justify-content: flex-start;
    overflow: hidden;
}

.banner-responsive img {
    width: 100%;
    height: 100%;
    object-fit: cover;
    object-position: center;
}

.overlay {
    position: absolute;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: linear-gradient(135deg, rgba(0,0,0,0.6) 0%, rgba(0,123,255,0.4) 100%);
    z-index: 1;
}

.banner-text {
    position: relative;
    z-index: 2;
    max-width: 600px;
}

.banner-text h2 {
    font-size: clamp(1.5rem, 4vw, 2.5rem);
    line-height: 1.2;
}

.banner-text p.lead {
    font-size: clamp(0.9rem, 2vw, 1.2rem);
}

.animated { animation-duration: 1s; animation-fill-mode: both; }
.fadeInDown { animation-name: fadeInDown; }
.fadeInUp { animation-name: fadeInUp; }

@keyframes fadeInDown {
    from { opacity: 0; transform: translateY(-50px); }
    to { opacity: 1; transform: translateY(0); }
}
@keyframes fadeInUp {
    from { opacity: 0; transform: translateY(50px); }
    to { opacity: 1; transform: translateY(0); }
}

/* Responsive adjustments */
@media (max-width: 992px) {
    .banner-text { max-width: 90%; padding: 1.5rem; }
}
</style>

    <!-- Header End -->

<div class="container-xxl py-5">
  <div class="container">
    <div class="eca-page-intro">
      <p class="eca-kicker">Office</p>
      <h2>Visit, call or write to the ECA secretariat</h2>
      <p>The association office is at Cooper Centre in Mbabane. Send a message and the team will get back to you.</p>
    </div>
    <div class="card border-0">
      <div class="card-body p-4 p-md-5">
        <div class="row g-4">
          <div class="col-lg-4">
            <div class="eca-info-tile">
              <div class="eca-info-icon">
                <i class="fa fa-map-marker-alt"></i>
              </div>
              <div class="ms-4">
                <p class="mb-2">Address</p>
                <h6 class="mb-0">Suite 40, Cooper Centre, Mbabane, Eswatini</h6>
              </div>
            </div>
          </div>

          <div class="col-lg-4">
            <div class="eca-info-tile">
              <div class="eca-info-icon">
                <i class="fa fa-phone-alt"></i>
              </div>
              <div class="ms-4">
                <p class="mb-2">Call us</p>
                <h6 class="mb-0"><a href="tel:+26824044987">+268 2404 4987</a></h6>
              </div>
            </div>
          </div>

          <div class="col-lg-4">
            <div class="eca-info-tile">
              <div class="eca-info-icon">
                <i class="fa fa-envelope-open"></i>
              </div>
              <div class="ms-4">
                <p class="mb-2">Email</p>
                <h6 class="mb-0"><a href="mailto:info@eca.co.sz">info@eca.co.sz</a></h6>
              </div>
            </div>
          </div>

          <div class="col-12 wow fadeIn" data-wow-delay="0.1s">
            <div class="eca-form-panel">
              <p class="eca-kicker">Contact us</p>
              <h2 class="mb-4">Have a query? Send a message</h2>
             <form id="contactForm" method="POST">
              <input type="hidden" name="csrf_token" id="csrfToken" value="<?= htmlspecialchars(eca_public_csrf_token(), ENT_QUOTES, 'UTF-8') ?>">
  <div class="row">
    <div class="col-md-6 mb-3">
      <label class="form-label" for="name">Your name</label>
      <input type="text" class="form-control" id="name" name="name" autocomplete="name" required>
    </div>
    <div class="col-md-6 mb-3">
      <label class="form-label" for="email">Your email</label>
      <input type="email" class="form-control" id="email" name="email" autocomplete="email" inputmode="email" required>
    </div>
    <div class="col-md-6 mb-3">
      <label class="form-label" for="enquiry_type">Enquiry type</label>
      <select class="form-control" id="enquiry_type" name="enquiry_type">
        <?php foreach ($enquiryTypes as $value => $label): ?>
          <option value="<?= htmlspecialchars($value, ENT_QUOTES, 'UTF-8') ?>"<?= $enquiryTypeSelected === $value ? ' selected' : '' ?>><?= htmlspecialchars($label, ENT_QUOTES, 'UTF-8') ?></option>
        <?php endforeach; ?>
      </select>
      <small class="text-muted">Routes your message for staff using the existing ticket subject (no new systems).</small>
    </div>
    <div class="col-md-6 mb-3">
      <label class="form-label" for="subject">Subject</label>
      <input type="text" class="form-control" id="subject" name="subject" value="<?= htmlspecialchars((string) ($_GET['subject'] ?? ''), ENT_QUOTES, 'UTF-8') ?>">
    </div>
    <div class="col-12 mb-3">
      <label class="form-label" for="message">Message</label>
      <textarea class="form-control" id="message" name="message" rows="3" required></textarea>
    </div>
    <div class="col-12">
      <button class="btn btn-primary w-100" type="submit">Send message</button>
    </div>
  </div>
  <div id="formMessage" class="mt-3" role="status" aria-live="polite"></div> <!-- Success/Error message -->
</form>

            </div>
          </div>
<script>
document.getElementById('contactForm').addEventListener('submit', function(e) {
    e.preventDefault(); // Prevent normal form submission

    const form = e.target;
    const formData = new FormData(form);
    const messageBox = document.getElementById('formMessage');

    fetch('contact_process.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.text())
    .then(data => {
        const trimmed = (data || '').trim();
        let ticketRef = '';
        let ok = trimmed === 'success';
        try {
            const parsed = JSON.parse(trimmed);
            if (parsed && parsed.ok) {
                ok = true;
                ticketRef = parsed.ticket_reference || '';
            }
        } catch (e) {}
        if (ok) {
            messageBox.setAttribute('role', 'status');
            const refHtml = ticketRef
                ? '<p class="mb-0 mt-2">Your ticket reference is <strong>' + ticketRef.replace(/[<>&]/g, '') + '</strong>. Keep it for follow-up.</p>'
                : '';
            messageBox.innerHTML = '<div class="alert alert-success">Your message has been sent successfully!' + refHtml + '</div>';
            form.reset();
        } else {
            messageBox.setAttribute('role', 'alert');
            messageBox.innerHTML = '<div class="alert alert-danger">' + trimmed + '</div>';
        }
    })
    .catch(error => {
        messageBox.setAttribute('role', 'alert');
        messageBox.innerHTML = '<div class="alert alert-danger">An error occurred. Please try again later.</div>';
        console.error('Error:', error);
    });
});
</script>

        </div>
      </div>
    </div>
  </div>
</div>
<!-- Contact Card End -->


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
	
	
	
	

<!-- Link your JS and CSS -->
<link rel="stylesheet" href="css/pop-upstyle.css">
<script src="js/pop-upscript.js" defer></script>
</body>


<!-- Mirrored from eca.co.sz/ by HTTrack Website Copier/3.x [XR&CO'2014], Sun, 24 Aug 2025 15:38:19 GMT -->
</html>