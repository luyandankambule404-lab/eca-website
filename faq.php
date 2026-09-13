
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
    
    
/* Cards */
.card{
    background:white;
    padding:22px;
    margin-bottom:20px;
    border-radius:10px;
    box-shadow:0 5px 18px rgba(0,0,0,0.08);
}

.card h2{
    margin-top:0;
    color:#b30000;
}

/* Steps */
.step{
    background:#fafafa;
    padding:12px 15px;
    border-left:4px solid #b30000;
    margin-bottom:10px;
    border-radius:6px;
}

/* Status badges */
.badge{
    padding:4px 10px;
    border-radius:15px;
    font-size:13px;
    font-weight:600;
}

/* FLOWCHART */
.flowchart{
    display:flex;
    flex-direction:column;
    align-items:center;
    gap:10px;
    margin-top:15px;
}

.flow-step{
    background:#f7f7f7;
    padding:12px 20px;
    border-radius:8px;
    font-weight:600;
    width:260px;
    text-align:center;
    box-shadow:0 3px 10px rgba(0,0,0,0.06);
}

.arrow{
    font-size:20px;
    color:#b30000;
}

.reviewer{ background:#d0e6ff; }
.approver{ background:#ffe5b4; }
.success{ background:#c8f7d1; }


/* FAQ */
.faq-btn{
    width:100%;
    text-align:left;
    background:#000066;
    color:white;
    padding:12px;
    margin-top:8px;
    border:none;
    border-radius:6px;
    cursor:pointer;
    font-weight:600;
}

.faq-content{
    display:none;
    padding:12px;
    color:#000;
    background:#f9f9f9;
    border-left:4px solid #000066;
    border-radius:6px;
    margin-bottom:6px;
}


.pending{background:#ffe5b4;}
.progress{background:#d0e6ff;}
.approved{background:#c8f7d1;}
.rejected{background:#ffd6d6;}
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
$pageKicker = 'Support';
$pageTitle = 'FAQ help centre';
$pageIntro = 'Answers to common questions about membership, applications, CPD and association services.';
$pageCrumb = 'FAQ';
require __DIR__ . '/includes/page-hero.php';
?>


<div class="container my-5">
    <div class="card shadow-lg">
        <div class="card-header bg-primary text-white">
          <style>
  .white-text {
    color: white;
  }
</style>

<div class="container my-5">
<div class="card">
<h2>❓ Frequently Asked Questions</h2>

<div class="faq">

<button class="faq-btn">What file format should I upload?</button>
<div class="faq-content">
All documents must be uploaded in PDF format only. Other formats will be rejected.
</div>

<button class="faq-btn">How do I know my application was submitted?</button>
<div class="faq-content">
You will receive a confirmation pop-up message and an email acknowledgement.
</div>

<button class="faq-btn">How long does approval take?</button>
<div class="faq-content">
 Processing time depends on document completeness. its may take 2 working days
</div>

<button class="faq-btn">Where can I download my certificate?</button>
<div class="faq-content">
Login with your ECA member number and password to your dashboard → My Applications → Download Certificate.
</div>

<button class="faq-btn">Who do I contact for support?</button>
<div class="faq-content">
Email support@eca.co.sz for technical or application assistance.
</div>

</div>
</div></div>
</div></div></div></div>
<iframe src="https://www.google.com/maps/embed?pb=!1m16!1m12!1m3!1d3575.9398627152696!2d31.139841774754323!3d-26.328446731859643!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!2m1!1sCooper%20Center%20eswatini%20contractors%20Association!5e0!3m2!1sen!2s!4v1756185111680!5m2!1sen!2s" width="100%" height="450" style="border:0;" allowfullscreen="" loading="lazy" referrerpolicy="no-referrer-when-downgrade"></iframe>

<?php require __DIR__ . '/includes/site-footer.php'; ?>

<script>
document.querySelectorAll(".faq-btn").forEach(btn=>{
    btn.addEventListener("click", function(){
        let content = this.nextElementSibling;

        if(content.style.display === "block"){
            content.style.display = "none";
        }else{
            document.querySelectorAll(".faq-content")
                .forEach(c => c.style.display="none");
            content.style.display = "block";
        }
    });
});
</script>

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

.accent-blue { background: linear-gradient(90deg, #0000b65, #0000066); }
.accent-green { background: linear-gradient(90deg, #000c046, #0000066); }
.accent-orange { background: linear-gradient(90deg, #fd7e14, #0000066); }

.service-item h4 {
    color: #0000066;
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


<!-- Mirrored from eca.co.sz/ by HTTrack Website Copier/3.x [XR&CO'2014], Sun, 24 Aug 2025 15:38:19 GMT -->
</html>