
<head>
    <meta charset="utf-8">
    <title>About ECA | Eswatini Contractors Association</title>
    <meta content="width=device-width, initial-scale=1.0" name="viewport">
    <meta content="" name="keywords">
    <meta content="Eswatini Contractors’ Official Website" name="description">

    <!-- Favicon -->
    <link href="favicon.ico" rel="icon">

    <!-- Google Web Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com/">
    <link rel="preconnect" href="https://fonts.gstatic.com/" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Open+Sans:wght@400;500&amp;family=Roboto:wght@500;700;900&amp;display=swap" rel="stylesheet"> 

    <!-- Icon Font Stylesheet -->
    <link href="cdnjs.cloudflare.com/ajax/libs/font-awesome/5.10.0/css/all.min.css" rel="stylesheet">
    <link href="cdn.jsdelivr.net/npm/bootstrap-icons%401.4.1/font/bootstrap-icons.css" rel="stylesheet">
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>

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
.hero-slider {
    position: relative;
    overflow: hidden;
}

/* Each Slide */
.hero-slider .slide {
  
    top: 0;
    left: 0;
    width: 100%;
    height: 100vh; /* full screen height */
    opacity: 0;
    transition: opacity 1s ease-in-out;
}

/* Background Image */
.hero-slider .slide img {
    width: 100%;
    height: 100%;
    object-fit: cover;
}

/* Dark Overlay */
.hero-slider .slide::before {
    content: "";
    position: absolute;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: rgba(0, 0, 0, 0.45);
    z-index: 1;
}

/* =============================
   CAPTION STYLES
   ============================= */
.slide-caption {
    position: absolute;
    top: 50%;
    left: 50%;
    transform: translate(-50%, -40%);
    text-align: center;
    color: #fff;
    max-width: 80%;
    z-index: 2;
    opacity: 0;
    transition: all 0.8s ease-in-out;
}

/* Active Caption */
.slide.active .slide-caption {
    opacity: 1;
    transform: translate(-50%, -50%);
}

/* Headline */
.slide-caption h2 {
    font-size: 2.5rem;
   
    margin-bottom: 15px;
      color: #ffffff;        /* White text */
    font-weight: 700; 
    animation: fadeUp 1s ease forwards;
}

.slide-caption h2 span {
    color: red;       /* Example: highlight span in orange */
    font-weight: 700;     /* bold too */
}

/* Paragraph */
.slide-caption p {
    font-size: 1.2rem;
    margin: 15px 0;
    line-height: 1.5;
    animation: fadeUp 1.4s ease forwards;
}

/* Buttons */
.slide-caption .btns {
    margin-top: 20px;
    animation: fadeUp 1.8s ease forwards;
}

.slide-caption .theme-btn,
.slide-caption .theme-btn-s2 {
    display: inline-block;
    padding: 12px 25px;
    border-radius: 25px;
    font-size: 1rem;
    font-weight: 600;
    text-decoration: none;
    transition: 0.3s ease;
}

.slide-caption .theme-btn {
    background: #000066;
    color: #fff;
    margin-right: 10px;
}

.slide-caption .theme-btn:hover {
    background: #e65c00;
}

.slide-caption .theme-btn-s2 {
    background: transparent;
    border: 2px solid #fff;
    color: #fff;
}

.slide-caption .theme-btn-s2:hover {
    background: #fff;
    color: #000;
}
/* Caption Container */
.slide-caption {
    position: absolute;
    top: 50%;
    left: 50%;
    transform: translate(-50%, -40%);
    text-align: center;
    color: #fff;
    max-width: 80%;
    z-index: 2;
    opacity: 0;
    transition: all 0.8s ease-in-out;

    /* NEW background styling */
    background: rgba(0, 0, 80, 0.7); /* dark blue, semi-transparent */
    padding: 30px 40px;
    border-radius: 12px;
}
   /* Blue background for the whole header */
    .news-header {
        background: #000066; /* deep blue */
        border-radius: 8px;
    }

    /* White heading */
    .news-title h2 {
        color: #fff !important;
    }

    /* White subtitle */
    .news-title p {
        color: #eaeaea !important;
    }

    /* Search bar styling */
    .search-wrapper {
        display: flex;
        align-items: center;
    }

    .search-input {
        border: none;
        padding: 6px 10px;
        border-radius: 5px 0 0 5px;
        outline: none;
    }

    .search-btn {
        background: #003d80; /* darker blue for contrast */
        border: none;
        color: #fff; /* white icon/text */
        padding: 6px 12px;
        border-radius: 0 5px 5px 0;
        cursor: pointer;
        font-weight: bold;
    }

    .search-btn:hover {
        background: #002952; /* even darker blue on hover */
    }
/* Responsive adjustments */
@media (max-width: 576px) {
    .search-wrapper {
        width: 100%;
    }
    .search-input {
        flex: 1;
        width: 100%;
    }
    .search-btn {
        width: 100%;
        margin-top: 2px;
        border-radius: 0 0 25px 25px;
    }
}


/* Active Caption */
.slide.active .slide-caption {
    opacity: 1;
    transform: translate(-50%, -50%);
}

/* Blinking with neon glow */
    .blink-text {
        animation: blinkGlow 1.5s infinite;
    }

    @keyframes blinkGlow {
        0% {
            color: white;
            text-shadow: none;
        }
        50% {
            color: red;
            text-shadow: 0 0 8px red, 0 0 16px red, 0 0 24px red;
        }
        100% {
            color: white;
            text-shadow: none;
        }
    }

    /* Small button styling for icons */
    .btn-sm-square {
        width: 30px;
        height: 30px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        font-size: 14px;
    }
/* =============================
   NAVIGATION ARROWS
   ============================= */
.slider-arrow {
    position: absolute;
    top: 50%;
    transform: translateY(-50%);
    font-size: 2rem;
    color: #fff;
    background: rgba(0,0,0,0.5);
    border-radius: 50%;
    width: 45px;
    height: 45px;
    line-height: 45px;
    text-align: center;
    cursor: pointer;
    z-index: 5;
    transition: 0.3s ease;
}

.slider-arrow:hover {
    background: rgba(255,255,255,0.7);
    color: #000;
}

.slider-arrow.prev { left: 20px; }
.slider-arrow.next { right: 20px; }

/* =============================
   DOTS / PAGINATION
   ============================= */
.slider-dots {
    position: absolute;
    bottom: 25px;
    left: 50%;
    transform: translateX(-50%);
    display: flex;
    gap: 10px;
    z-index: 5;
}

.slider-dots span {
    display: block;
    width: 12px;
    height: 12px;
    background: white;
    border-radius: 50%;
    cursor: pointer;
    transition: background 0.3s ease;
}

.slider-dots span.active {
    background: #000066;
}



.slide-caption span{
    color: #ff0000;
}
/* =============================
   ANIMATIONS
   ============================= */
@keyframes fadeUp {
    0% {
        opacity: 0;
        transform: translateY(30px);
    }
    100% {
        opacity: 1;
        transform: translateY(0);
    }
}

  /* Wrapper */
    .showcase-wrapper{
      position:relative;width:100%;height:100vh;overflow:hidden;
    }
    .showcase-track{
      display:flex;transition:transform 1s ease-in-out;height:100%;
    }
    .showcase-item{
      min-width:100%;height:100vh;position:relative;
    }
    .showcase-item img.bg{
      width:100%;height:100%;object-fit:cover;
      filter:brightness(70%);transition:transform 10s ease;
    }
    .showcase-item:hover img.bg{transform:scale(1.1)}


/* Caption container */
.showcase-caption {
  position: absolute;
  top: 50%;
  left: 50%;
  transform: translate(-50%, -50%);
  max-width: 700px;
  background: rgba(0, 0, 102, 0.85); /* #000066 with slight transparency */
  padding: 25px 35px;
  border-radius: 12px;
  text-align: center;
  color: #fff;
  opacity: 0;                 /* start hidden */
  animation: fadeBox 1.2s ease forwards; /* animate background fade-in */
}

/* Headings and paragraph */
.showcase-caption h2,
.showcase-caption p {
  color: #fff;
  opacity: 0;
  animation: fadeText 1s ease forwards;
}

.showcase-caption h2 {
  animation-delay: 0.4s;
}

.showcase-caption p {
  animation-delay: 0.7s;
}

/* Buttons inside caption */
.showcase-caption .btns {
  margin-top: 15px;
  opacity: 0;
  animation: fadeText 1s ease forwards;
  animation-delay: 1s;
}

/* Animation for caption box */
@keyframes fadeBox {
  0% {
    opacity: 0;
    transform: translate(-50%, -40%);
  }
  100% {
    opacity: 1;
    transform: translate(-50%, -50%);
  }
}

/* Animation for text inside */
@keyframes fadeText {
  0% {
    opacity: 0;
    transform: translateY(20px);
  }
  100% {
    opacity: 1;
    transform: translateY(0);
  }
}

/* Dots */
.nav-dots div {
  width: 12px;
  height: 12px;
  background: #000066; /* lighter blue for inactive */
  border-radius: 50%;
  cursor: pointer;
  transition: 0.3s;
}
    .popup-overlay {
      position: fixed;
      top: 0;
      left: 0;
      width: 100%;
      height: 100%;
      background: rgba(0, 0, 0, 0.7);
      display: none;
      justify-content: center;
      align-items: center;
      z-index: 9999;
      animation: fadeIn 0.8s ease forwards;
    }

    /* Popup box */
    .popup-box {
      position: relative;
      background: #fff;
      border-radius: 12px;
      padding: 0 0 20px 0;
      box-shadow: 0 5px 20px rgba(0, 0, 0, 0.3);
      max-width: 550px;
      width: 100%;
      animation: scaleIn 0.5s ease forwards;
      overflow: hidden;
      text-align: center;
    }

    /* Advert image */
    .popup-box img {
      width: 100%;
      height: auto;
      object-fit: cover;
      display: block;
    }

    /* Register button */
    .register-btn {
      background: #007bff;
      color: white;
      border: none;
      border-radius: 6px;
      padding: 12px 25px;
      margin-top: 15px;
      font-size: 16px;
      cursor: pointer;
      transition: background 0.3s ease;
    }

    .register-btn:hover {
      background: #0056b3;
    }

    /* Close button */
    .close-btn {
      position: absolute;
      top: 10px;
      right: 10px;
      background: #ff3333;
      color: #fff;
      border: none;
      border-radius: 50%;
      font-size: 22px;
      width: 35px;
      height: 35px;
      line-height: 35px;
      cursor: pointer;
      text-align: center;
      z-index: 10;
    }

    /* Animations */
    @keyframes fadeIn {
      from { opacity: 0; }
      to { opacity: 1; }
    }

    @keyframes scaleIn {
      from { transform: scale(0.9); opacity: 0; }
      to { transform: scale(1); opacity: 1; }
    }
.nav-dots div.active {
  background: #000066; /* dark blue for active */
  transform: scale(1.3);
}

  .showcase-caption h2,
  .showcase-caption p {
    color: #fff; /* force white text */
  }

  /* Update dots */
  .nav-dots div.active {
    background: #000066; /* dark blue active */
    transform: scale(1.3);
  }
    /* Caption */
 
    
    .btns a{
      display:inline-block;margin-right:10px;
      padding:12px 25px;background:#fff;color:#222;
      text-decoration:none;font-weight:600;border-radius:30px;
      transition:.3s;
    }
    .btns a:hover{background:#ffd;color:#111;transform:scale(1.05)}

    /* Arrows */
    .nav-arrow{
      position:absolute;top:50%;transform:translateY(-50%);
      font-size:2rem;color:#fff;background:rgba(0,0,0,.5);
      padding:10px;border-radius:50%;cursor:pointer;z-index:1000;
      transition:.3s;
    }
    .nav-arrow:hover{background:#ffb400;color:#111}
    .nav-arrow.prev{left:20px}
    .nav-arrow.next{right:20px}

    /* Dots */
    .nav-dots{
      position:absolute;bottom:20px;left:50%;transform:translateX(-50%);
      display:flex;gap:10px;
    }
    .nav-dots div{
      width:12px;height:12px;background:rgba(255,255,255,.6);
      border-radius:50%;cursor:pointer;transition:.3s;
    }
    .nav-dots div.active{background:#ffb400;transform:scale(1.3)}

    /* Animations */
    @keyframes fadeInUp{from{opacity:0;transform:translateY(20px)}to{opacity:1;transform:translateY(0)}}
    @keyframes slideInLeft{from{opacity:0;transform:translateX(-50px)}to{opacity:1;transform:translateX(0)}}
    @keyframes slideInRight{from{opacity:0;transform:translateX(50px)}to{opacity:1;transform:translateX(0)}}
    
    /* ===============================
   Mobile Dropdown Styling
   =============================== */
@media (max-width: 768px) {
    /* Make dropdown items full width and easy to tap */
    .navbar-nav .dropdown-menu {
        width: 100%;       /* full width of navbar */
        left: 0 !important; /* override positioning */
        right: 0 !important;
        border-radius: 0;
        margin: 0;
    }

    /* Increase padding for touch-friendly menu items */
    .navbar-nav .dropdown-item {
        padding: 12px 20px;
        font-size: 0.95rem;  /* slightly smaller text on mobile */
    }

    /* Make dropdown toggle link occupy full width */
    .navbar-nav .nav-item.dropdown > .nav-link {
        width: 100%;
        display: flex;
        justify-content: space-between; /* text left, caret right */
        padding: 12px 20px;
    }

    /* Optional: add a small separator between dropdowns */
    .navbar-nav .dropdown-menu .dropdown-item + .dropdown-item {
        border-top: 1px solid rgba(0,0,0,0.1);
    }

    /* Reduce navbar padding on mobile */
    .navbar-nav.ms-auto {
        padding-left: 0;
        padding-right: 0;
    }
}
/* ====== Mobile adjustments for slider arrows and dots ====== */
@media (max-width: 576px) {
    /* Reduce size of arrows */
    .nav-arrow {
        font-size: 1.5rem;   /* smaller arrow */
        width: 35px;         /* narrower */
        height: 35px;        /* shorter */
        line-height: 35px;   /* center the arrow vertically */
    }

    /* Reduce size of dots */
    .nav-dots div {
        width: 8px;
        height: 8px;
    }

    /* Active dot smaller too */
    .nav-dots div.active {
        transform: scale(1.1);
    }
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
  
  <!-- Popup container -->
  

    <script>
    window.addEventListener("load", function() {
      const popup = document.getElementById("advertPopup");
      const closeBtn = document.getElementById("closeBtn");

      // Check if advert was already shown in this session
      if (!sessionStorage.getItem("ecaAdvertShown")) {
        popup.style.display = "flex";

        // Auto close after 6 seconds
        const timer = setTimeout(closePopup, 6000);

        // Close button manually closes popup
        closeBtn.addEventListener("click", function() {
          clearTimeout(timer);
          closePopup();
        });

        // Mark advert as shown
        sessionStorage.setItem("ecaAdvertShown", "true");
      }

      // Function to close popup smoothly
      function closePopup() {
        popup.style.transition = "opacity 0.5s ease";
        popup.style.opacity = "0";
        setTimeout(() => {
          popup.style.display = "none";
          popup.style.opacity = "1"; // reset opacity for next page load
        }, 500);
      }
    });
  </script>
</body>
</html>

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


<!-- Bootstrap Icons -->
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">

    <?php require __DIR__ . '/includes/site-nav.php'; ?>

<?php
$pageKicker = 'About';
$pageTitle = 'About ECA';
$pageIntro = 'The professional body for construction companies and allied trades in Eswatini.';
$pageCrumb = 'About';
require __DIR__ . '/includes/page-hero.php';
?>
	
<!-- Header / Hero Section End -->

<div class="container-xxl py-5">
  <div class="eca-page-intro">
    <p class="eca-kicker">Who we are</p>
    <h2>The professional voice of Eswatini’s contractors</h2>
    <p>ECA represents construction companies and allied trades, and works for a fairer, more professional industry.</p>
  </div>

  <div class="row g-4">
  <div class="col-lg-6 wow fadeIn" data-wow-delay="0.3s">
    <div class="card border-0 h-100">
      <div class="card-body p-4 p-md-5">
        <h2 class="fw-bold mb-3">About the Eswatini Contractors Association (ECA)</h2>
        <p style="line-height:1.7;">
          The Eswatini Contractors Association (ECA), formerly known as the Swaziland Contractors Association, 
          is an industry trade association representing construction companies and allied tradespeople. 
          Following the country’s official name change in 2018, the Association adopted its current name 
          to reflect this national transition.
        </p>
        <p style="line-height:1.7;">
          ECA’s roots trace back to 1991, when a coalition of local contractors came together with a shared vision: 
          to promote and protect the interests of Swati contractors. Recognizing the need for a unified industry voice, 
          they established an organization dedicated to advancing the professional and economic interests of its members.
        </p>
        <p style="line-height:1.7; margin-bottom:0;">
          In response to challenges within the construction sector—particularly around government procurement procedures—
          ECA championed the formation of a regulatory body to bring structure and accountability to the industry. 
          This advocacy led to the establishment of the Construction Industry Council (CIC), with ECA playing a 
          leading role in drafting the CIC Act of 2013.
        </p>
      </div>
    </div>
  </div>

  <div class="col-lg-6 wow fadeIn" data-wow-delay="0.5s">
    <div class="card border-0 h-100">
      <div class="card-body p-4 p-md-5">
        <h2 class="fw-bold mb-3">Our aims and objectives</h2>
        <ul class="eca-aim-list">
          <li>To protect the market in the construction industry on behalf of the members in the Kingdom of Eswatini</li>
          <li>To promote and advance the status and public recognition of ECA and its members by maintaining high standards, ethical conduct, and integrity in the construction industry</li>
          <li>To proactively review any proposed legislative or other measures affecting the interest of the Association and its members</li>
          <li>To provide dialogue and mutual consultation by encouraging exchange of information and technical know-how for the improvement of construction technology and management</li>
          <li>To secure cooperative action in advancing common purposes of its members by working with manufacturers and financial institutions for mutual advantage</li>
          <li>To establish and implement initiatives for future sustainability and continuity of the Association</li>
          <li>To promote and advance the common interests of its members</li>
          <li>To foster economic development and the empowerment of women for meaningful participation in the industry</li>
        </ul>
      </div>
    </div>
  </div>
</div>

      <div class="contact-card wow fadeInUp mt-5" data-wow-delay="0.6s">
        <div class="eca-contact-band">
          <h3 class="fw-bold mb-3">Questions or comments?</h3>
          <p class="mb-4">We would love to hear from you. Reach the ECA office today.</p>

          <div class="d-flex flex-column flex-md-row justify-content-center gap-4">
            <div class="contact-item">
              <i class="fa fa-phone fa-2x mb-2"></i>
              <h5 class="mb-1">Call us</h5>
              <p class="mb-0"><a href="tel:+26824044987" class="text-white text-decoration-none">+268 2404 4987</a></p>
            </div>
            <div class="contact-item">
              <i class="fa fa-envelope fa-2x mb-2"></i>
              <h5 class="mb-1">Email</h5>
              <p class="mb-0"><a href="mailto:info@eca.co.sz" class="text-white text-decoration-none">info@eca.co.sz</a></p>
            </div>
          </div>
        </div>
      </div>
</div>

<!-- Contact Card Styling -->
<style>
.contact-gradient {
  background: linear-gradient(135deg, #ffffff, #e6f2ff);
}
.contact-card .contact-item {
  transition: transform 0.3s ease, color 0.3s ease;
}
.contact-card .contact-item:hover {
  transform: translateY(-5px);
}
.contact-card .contact-item i {
  transition: transform 0.4s ease, color 0.4s ease;
}
.contact-card .contact-item:hover i {
  transform: scale(1.2);
  color: #f4c542;
}
</style>

    <!-- About End -->
<!-- Services Section -->
<style>
.service-item {
  background: #fff;
  border-radius: 16px;
  padding: 40px 30px;
  transition: all 0.4s ease-in-out;
  box-shadow: 0 4px 12px rgba(0,0,0,0.08);
  position: relative;
  overflow: hidden;
}

.service-item::before {
  content: "";
  position: absolute;
  top: -100%;
  left: 0;
  width: 100%;
  height: 100%;
  background: linear-gradient(135deg, #000066, #d90920);
  transition: all 0.5s ease;
  z-index: 0;
}

.service-item:hover::before {
  top: 0;
}

.service-item h4, 
.service-item p, 
.service-item a {
  position: relative;
  z-index: 1;
  transition: color 0.3s ease;
}

.service-item:hover h4,
.service-item:hover p {
  color: #fff;
}

.service-item:hover a {
  color: #fff;
}

.service-item:hover {
  transform: translateY(-10px) scale(1.02);
  box-shadow: 0 10px 25px rgba(0,0,0,0.15);
}

.service-item a.btn {
  margin-top: 10px;
  font-weight: 600;
  transition: all 0.3s ease;
}

.service-item a.btn i {
  transition: transform 0.3s ease;
}

.service-item:hover a.btn i {
  transform: rotate(90deg);
}
</style>

<div class="container-xxl py-5">
  <div class="eca-page-intro">
    <p class="eca-kicker">Direction</p>
    <h2>Vision, mission and goals</h2>
  </div>
  <div class="row g-4">
      <div class="col-lg-4 col-md-6 wow fadeInUp" data-wow-delay="0.1s">
        <div class="service-item h-100">
          <h4 class="mb-3">Vision</h4>
          <p class="mb-0">
            “To be a strong advocacy voice for Eswatini’s dynamic construction industry that embraces the best business and industry practices”
          </p>
        </div>
      </div>
      <div class="col-lg-4 col-md-6 wow fadeInUp" data-wow-delay="0.3s">
        <div class="service-item h-100">
          <h4 class="mb-3">Mission</h4>
          <p class="mb-0">
            “To provide collaborative and influential leadership by empowering contractors and promoting excellence in the construction industry”
          </p>
        </div>
      </div>
      <div class="col-lg-4 col-md-6 wow fadeInUp" data-wow-delay="0.5s">
        <div class="service-item h-100">
          <h4 class="mb-3">Goals</h4>
          <p class="mb-0">
            “To support members through training, business development, financial aid, and advocacy to achieve sustainable growth.”
          </p>
        </div>
      </div>
    </div>
</div>


<iframe src="https://www.google.com/maps/embed?pb=!1m16!1m12!1m3!1d3575.9398627152696!2d31.139841774754323!3d-26.328446731859643!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!2m1!1sCooper%20Center%20eswatini%20contractors%20Association!5e0!3m2!1sen!2s!4v1756185111680!5m2!1sen!2s" width="100%" height="450" style="border:0;" allowfullscreen="" loading="lazy" referrerpolicy="no-referrer-when-downgrade"></iframe>

<?php require __DIR__ . '/includes/site-footer.php'; ?>


<style>
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