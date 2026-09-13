
<html lang="en">

<head>
    <meta charset="utf-8">
    <title>E.C.A | Home</title>
    <meta content="width=device-width, initial-scale=1.0" name="viewport">
    <meta content="" name="keywords">
    <meta content="Eswatini Contractors’ Official Website" name="description">

    <!-- Favicon -->
    <link href="img/favicon.ico" rel="icon">

    <!-- Google Web Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com/">
    <link rel="preconnect" href="https://fonts.gstatic.com/" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Open+Sans:wght@400;500&amp;family=Roboto:wght@500;700;900&amp;display=swap" rel="stylesheet"> 

    <!-- Icon Font Stylesheet -->
    <link href="cdnjs.cloudflare.com/ajax/libs/font-awesome/5.10.0/css/all.min.css" rel="stylesheet">
    <link href="cdn.jsdelivr.net/npm/bootstrap-icons%401.4.1/font/bootstrap-icons.css" rel="stylesheet">

    <!-- Libraries Stylesheet -->
    <link href="lib/animate/animate.min.css" rel="stylesheet">
    <link href="lib/owlcarousel/assets/owl.carousel.min.css" rel="stylesheet">
    <link href="lib/tempusdominus/css/tempusdominus-bootstrap-4.min.css" rel="stylesheet" />

    <!-- Customized Bootstrap Stylesheet -->
    <link href="css/bootstrap.min.css" rel="stylesheet">

    <!-- Template Stylesheet -->
    <link href="css/style.css" rel="stylesheet">
    <link href="css/theme.css" rel="stylesheet">
    
    <!-- Slick CSS -->
<link rel="stylesheet" type="text/css" href="https://cdn.jsdelivr.net/npm/slick-carousel@1.8.1/slick/slick.css"/>
<link rel="stylesheet" type="text/css" href="https://cdn.jsdelivr.net/npm/slick-carousel@1.8.1/slick/slick-theme.css"/>

<!-- jQuery (required for Slick) -->
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>

<!-- Slick JS -->
<script src="https://cdn.jsdelivr.net/npm/slick-carousel@1.8.1/slick/slick.min.js"></script>
<style>

/* =============================
   HERO SLIDER BASE
   ============================= */
   
   .header-custom {
    background-color: #000066; /* Blue similar to the logo */
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
$pageKicker = 'Choose with confidence';
$pageTitle = 'ECA checklist';
$pageIntro = 'Use this checklist when selecting an ECA-registered contractor.';
$pageCrumb = 'Checklist';
require __DIR__ . '/includes/page-hero.php';
?>
	
<!-- Header / Hero Section End -->
    <style>
        :root {
            --primary-blue: #004a99;
            --accent-gold: #d4af37;
            --light-bg: #f4f7f6;
            --text-dark: #333;
        }

       

        .container {
            max-width: 800px;
            margin: auto;
            background: white;
            padding: 40px;
            border-radius: 8px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
        }
    .containers {
            max-width: 900px;
            margin: auto;
            background: #000066;
          
        }
        header {
            text-align: center;
            border-bottom: 3px solid var(--primary-blue);
            margin-bottom: 30px;
            padding-bottom: 20px;
        }

        h1 { color: var(--primary-blue); margin-bottom: 5px; }
        .motto { font-style: italic; color: #666; font-size: 1.1em; }

        .section {
            margin-bottom: 25px;
            padding: 20px;
            border-left: 5px solid var(--accent-gold);
            background: #fafafa;
        }

        h2 {
            color: var(--primary-blue);
            font-size: 1.25em;
            margin-top: 0;
            text-transform: uppercase;
        }

        ul {
            list-style: none;
            padding: 0;
        }

        li {
            padding: 8px 0;
            border-bottom: 1px solid #eee;
            display: flex;
            align-items: center;
        }

        li::before {
            content: "✓";
            color: var(--accent-gold);
            font-weight: bold;
            margin-right: 15px;
        }
.dir-link{
  color:#0d6efd;
  font-weight:600;
  text-decoration:none;
}

.dir-link:hover{
  text-decoration:underline;
}

      
    </style>
<div class="container-xxl py-5">
  <div class="row contact-card">
<div class="container">
    <header>
        <p class="motto">Working together for the construction industry</p>
<h3>Contractor Selection Checklist (ECA Members)</h3>
<p><em>Ensure the following are confirmed before signing a contract:</em></p>
    </header>

   <div class="section">
  <h2>1. Membership & Credentials</h2>
  <ul>
    <li>
      Verify company details and any complaints in the
      <a href="directory.php" class="dir-link">&nbsp;ECA Membership Directory&nbsp;</a>
    </li>
    <li>Confirm a valid ECA membership certificate</li>
    <li>Check that the contractor holds a valid license</li>
  </ul>
</div>

<div class="section">
  <h2>2. Comparison & References</h2>
  <ul>
    <li>Request and compare estimates from several ECA contractors</li>
    <li>Obtain references from past clients</li>
  </ul>
</div>

<div class="section">
  <h2>3. Scope & Deliverables</h2>
  <ul>
    <li>Ensure the scope of work is clearly defined</li>
    <li>Review a detailed list of materials to be provided</li>
    <li>Confirm cost requirements at the time of signing</li>
  </ul>
</div>

<div class="section">
  <h2>4. Financial Protections</h2>
  <ul>
    <li>Include contract cancellation and refund clauses</li>
    <li>Ensure payment terms are clearly stated</li>
    <li>Outline the process for confirming work completion</li>
  </ul>
</div>

<div class="section">
  <h2>5. Training & Compliance</h2>
  <ul>
    <li>Verify evidence of attendance at Association organized trainings</li>
    <li>Confirm agreement to abide by the ECA Code of Conduct</li>
  </ul>
</div>
</div> </div></div>

<style>
/* ================= FOOTER CLEAN FIX ================= */

/* remove underline everywhere inside footer */
.footer a,
.footer a:link,
.footer a:visited,
.footer a:hover,
.footer a:focus,
.footer a:active {
    text-decoration: none !important;
}

/* footer links */
.footer-link {
    color: #ffffff;
    transition: all 0.25s ease;
}

/* hover effect */
.footer-link:hover {
    color: #00c3ff !important;
    padding-left: 6px;
}

/* section headers */
.footer-header {
    color: #ffffff !important;
    font-weight: 600;
}

/* logo rounded */
.footer img {
    border-radius: 12px;
}

/* social icons */
.btn-social {
    width: 40px;
    height: 40px;
    display: flex;
    align-items: center;
    justify-content: center;
    border-radius: 50%;
    transition: 0.3s;
}

.btn-social:hover {
    background: #00c3ff;
    border-color: #00c3ff;
    transform: scale(1.1);
}

</style>


<iframe src="https://www.google.com/maps/embed?pb=!1m16!1m12!1m3!1d3575.9398627152696!2d31.139841774754323!3d-26.328446731859643!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!2m1!1sCooper%20Center%20eswatini%20contractors%20Association!5e0!3m2!1sen!2s!4v1756185111680!5m2!1sen!2s" width="100%" height="450" style="border:0;" allowfullscreen="" loading="lazy" referrerpolicy="no-referrer-when-downgrade"></iframe>

<?php require __DIR__ . '/includes/site-footer.php'; ?>



    <!-- Back to Top -->
    <a href="#" class="btn btn-lg btn-primary btn-lg-square rounded-circle back-to-top"><i class="bi bi-arrow-up"></i></a>


    <!-- JavaScript Libraries -->
    <script src="../code.jquery.com/jquery-3.4.1.min.js"></script>
    <script src="../cdn.jsdelivr.net/npm/bootstrap%405.0.0/dist/js/bootstrap.bundle.min.js"></script>
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
let uIndex = 0;

function showUSlide(index) {
    uSlides.forEach((slide, i) => {
        slide.classList.toggle('active', i === index);
    });
}

uPrevBtn.addEventListener('click', () => {
    uIndex = (uIndex - 1 + uSlides.length) % uSlides.length;
    showUSlide(uIndex);
});

uNextBtn.addEventListener('click', () => {
    uIndex = (uIndex + 1) % uSlides.length;
    showUSlide(uIndex);
});

// Auto-play
setInterval(() => {
    uIndex = (uIndex + 1) % uSlides.length;
    showUSlide(uIndex);
}, 5000);

// Initialize
showUSlide(uIndex);


const newsContainer = document.getElementById('newsContainer');
const sliderContainer = document.getElementById('sliderContainer');
let slideInterval;

function renderSlider() {
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
    slideInterval = setInterval(nextSlide, 4000);

    // Prev/Next buttons
    const prevBtn = sliderContainer.querySelector('.u-prev');
    const nextBtn = sliderContainer.querySelector('.u-next');

    prevBtn.onclick = () => { currentIndex = (currentIndex - 1 + slides.length) % slides.length; showSlide(currentIndex); resetInterval(); };
    nextBtn.onclick = () => { nextSlide(); resetInterval(); };

    function resetInterval() {
        clearInterval(slideInterval);
        slideInterval = setInterval(nextSlide, 4000);
    }
}

// AJAX search
function performSearch() {
    let query = document.getElementById('searchInput').value;

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
document.getElementById('searchBtn').addEventListener('click', performSearch);
document.getElementById('searchInput').addEventListener('keypress', function(e){
    if(e.key === 'Enter') performSearch();
});

// Initialize slider on page load
renderSlider();


	</script>
	
	


<!-- Floating WhatsApp Button -->
<a href="https://wa.me/26876702898" target="_blank" class="whatsapp-float">
  <i class="bi bi-whatsapp"></i>
</a>

<style>
/* Floating WhatsApp Button */
.whatsapp-float {
  position: fixed;
  bottom: 100px;      /* distance from bottom */
  right: 35px;        /* distance from right */
  background: #25D366;   /* WhatsApp green */
  color: #fff;
  border-radius: 50%;
  width: 60px;
  height: 60px;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 30px;
  z-index: 1000;
  box-shadow: 0 0 10px rgba(37, 211, 102, 0.7), 0 0 20px rgba(37, 211, 102, 0.5);
  animation: glow 1.5s infinite alternate;
  text-decoration: none;
  transition: transform 0.3s ease;
}

/* Hover zoom */
.whatsapp-float:hover {
  transform: scale(1.15);
}

/* Glow animation */
@keyframes glow {
  0% {
    box-shadow: 0 0 5px rgba(37, 211, 102, 0.5), 0 0 10px rgba(37, 211, 102, 0.3);
  }
  100% {
    box-shadow: 0 0 20px rgba(37, 211, 102, 0.9), 0 0 40px rgba(37, 211, 102, 0.7);
  }
}
</style>


<!-- Bootstrap Icons -->
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">

	
	

<!-- Link your JS and CSS -->
<link rel="stylesheet" href="css/pop-upstyle.css">
<script src="js/pop-upscript.js" defer></script>
</body>


</html>