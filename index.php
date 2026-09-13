    <?php
$currentPage = basename($_SERVER['PHP_SELF']);

require_once 'config.php';
$db   = new Database();
$conn = $db->getConnection(false);
/*
|--------------------------------------------------------------------------
| Fetch latest news
|--------------------------------------------------------------------------
*/
$newsItems = [];
if ($conn) {
    try {
        $query = "SELECT * FROM news ORDER BY date DESC LIMIT 3";
        $stmt  = $conn->prepare($query);
        $stmt->execute();
        $newsItems = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (Exception $e) {
        $newsItems = [];
    }
}

/*
|--------------------------------------------------------------------------
| SEO variables
|--------------------------------------------------------------------------
*/
$siteName        = "Eswatini Contractors Association";
$pageTitle       = "Contractors in Eswatini | ECA Verified Construction Companies";
$metaDescription = "Find trusted contractors in Eswatini through the Eswatini Contractors Association (ECA). Browse verified construction companies, industry news, membership information, and construction resources in Mbabane, Manzini, and nationwide.";
$metaKeywords    = "contractors in Eswatini, construction companies Eswatini, building contractors Mbabane, civil contractors Manzini, Eswatini Contractors Association, ECA Eswatini, verified contractors Eswatini";
$canonicalUrl    = "https://eca.co.sz/index.php";
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title><?= htmlspecialchars($pageTitle) ?></title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <!-- SEO -->
    <meta name="description" content="<?= htmlspecialchars($metaDescription) ?>">
    <meta name="keywords" content="<?= htmlspecialchars($metaKeywords) ?>">
    <meta name="author" content="Eswatini Contractors Association">
    <meta name="robots" content="index, follow">
    <link rel="canonical" href="<?= htmlspecialchars($canonicalUrl) ?>">

    <!-- Open Graph -->
    <meta property="og:type" content="website">
    <meta property="og:title" content="<?= htmlspecialchars($pageTitle) ?>">
    <meta property="og:description" content="<?= htmlspecialchars($metaDescription) ?>">
    <meta property="og:url" content="<?= htmlspecialchars($canonicalUrl) ?>">
    <meta property="og:site_name" content="<?= htmlspecialchars($siteName) ?>">
    <meta property="og:image" content="https://eca.co.sz/img/logo.jpg">

    <!-- Twitter -->
    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:title" content="<?= htmlspecialchars($pageTitle) ?>">
    <meta name="twitter:description" content="<?= htmlspecialchars($metaDescription) ?>">
    <meta name="twitter:image" content="https://eca.co.sz/img/logo.jpg">

    <!-- Favicon -->
    <link href="favicon.ico" rel="icon">

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Open+Sans:wght@400;500;600;700&family=Plus+Jakarta+Sans:wght@400;500;600;700;800&family=Roboto:wght@500;700;900&display=swap" rel="stylesheet">

    <!-- CSS -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.10.0/css/all.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
    <link href="lib/animate/animate.min.css" rel="stylesheet">
    <link href="css/bootstrap.min.css" rel="stylesheet">
    <link href="css/style.css" rel="stylesheet">
    <link href="css/theme.css" rel="stylesheet">
    <link href="css/home.css" rel="stylesheet">

    <!-- Structured Data -->
    <script type="application/ld+json">
    {
      "@context":"https://schema.org",
      "@type":"Organization",
      "name":"Eswatini Contractors Association",
      "url":"https://eca.co.sz",
      "logo":"https://eca.co.sz/img/logo.jpg",
      "email":"info@eca.co.sz",
      "telephone":"+26824044987",
      "address":{
        "@type":"PostalAddress",
        "streetAddress":"Suite 40, Cooper Centre",
        "addressLocality":"Mbabane",
        "addressCountry":"SZ"
      },
      "sameAs":[
        "https://facebook.com/",
        "https://instagram.com/",
        "https://linkedin.com/"
      ]
    }
    </script>

    <script type="application/ld+json">
    {
      "@context":"https://schema.org",
      "@type":"WebSite",
      "name":"Eswatini Contractors Association",
      "url":"https://eca.co.sz/",
      "potentialAction":{
        "@type":"SearchAction",
        "target":"https://eca.co.sz/news.php?search={search_term_string}",
        "query-input":"required name=search_term_string"
      }
    }
    </script>
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
    <link href="css/home.css" rel="stylesheet">
    
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

/* ======================================
   MOBILE TOP-LEVEL MEMBERSHIP BUTTONS
   ====================================== */

.mobile-membership {
    display: none;
}

/* Desktop dropdown visible normally */
.desktop-membership {
    display: block;
}

@media (max-width: 768px) {

    .mobile-membership {
        display: block;
        border-radius: 12px;
        margin: 8px 0;
        font-weight: 700;
        padding: 12px 16px !important;
        text-align: center;
    }

    /* ALWAYS highlighted */
    .always-highlight {
        background: linear-gradient(135deg,#000066,#003d80);
        color: #fff !important;
        box-shadow: 0 10px 25px rgba(0,0,102,.30);
    }

    .always-highlight:hover {
        background: linear-gradient(135deg,#003d80,#000066);
    }

    /* Hide desktop dropdown on mobile */
    .desktop-membership {
        display: none !important;
    }
}


  

/* =====================================================
   ECA TRAINING POSTER POPUP â€” FULL POSTER VISIBLE
   Poster ratio: 864 Ã— 1080 = 4:5
   ===================================================== */
.eca-poster-modal {
    position: fixed;
    inset: 0;
    z-index: 999999;
    display: none;
    place-items: center;
    padding: clamp(20px, 4vh, 42px) clamp(14px, 3vw, 36px);
    overflow: auto;
    background:
        radial-gradient(circle at 10% 10%, rgba(196, 0, 0, 0.32), transparent 30%),
        radial-gradient(circle at 90% 90%, rgba(0, 0, 102, 0.60), transparent 36%),
        rgba(0, 0, 0, 0.90);
    backdrop-filter: blur(8px);
    -webkit-backdrop-filter: blur(8px);
}

.eca-poster-modal.show {
    display: grid;
    animation: ecaPosterFadeIn 0.35s ease both;
}

/*
 * The width is limited by both the browser width and height.
 * 72vh is 80% of 90vh, preserving the poster's 4:5 ratio.
 */
.eca-poster-box {
    position: relative;
    box-sizing: border-box;
    width: min(94vw, 72vh, 760px);
    aspect-ratio: 4 / 5;
    max-height: 90vh;
    padding: 8px;
    background: linear-gradient(145deg, #000066, #c40000);
    border: 2px solid rgba(255, 255, 255, 0.40);
    border-radius: 18px;
    box-shadow:
        0 30px 80px rgba(0, 0, 0, 0.65),
        0 0 0 5px rgba(196, 0, 0, 0.18);
    animation: ecaPosterScaleIn 0.35s ease both;
}

/* Keep controls above the image without changing the poster size. */
.eca-poster-topbar {
    position: absolute;
    inset: 0;
    z-index: 10;
    pointer-events: none;
}

/* The poster already contains its own title, so hide the duplicate badge. */
.eca-poster-badge {
    display: none;
}

.eca-poster-close-x {
    position: absolute;
    top: -16px;
    right: -16px;
    width: 48px;
    height: 48px;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    padding: 0;
    border: 3px solid #ffffff;
    border-radius: 50%;
    background: linear-gradient(145deg, #d50000, #8d0000);
    color: #ffffff;
    font: 900 30px/1 Arial, sans-serif;
    cursor: pointer;
    box-shadow: 0 10px 28px rgba(196, 0, 0, 0.52);
    transition: transform 0.2s ease, background 0.2s ease;
    pointer-events: auto;
}

.eca-poster-close-x:hover,
.eca-poster-close-x:focus-visible {
    transform: scale(1.08);
    background: #ff0000;
    outline: 3px solid rgba(255, 255, 255, 0.75);
    outline-offset: 3px;
}

.eca-poster-image-wrap,
.eca-poster-image-wrap > a {
    width: 100%;
    height: 100%;
    min-width: 0;
    min-height: 0;
    display: flex;
    align-items: center;
    justify-content: center;
}

.eca-poster-image-wrap {
    overflow: hidden;
    border-radius: 12px;
    background: #ffffff;
}

.eca-poster-image-wrap > a {
    text-decoration: none;
}

.eca-poster-image {
    display: block;
    width: 100%;
    height: 100%;
    max-width: 100%;
    max-height: 100%;
    object-fit: contain;
    object-position: center;
    background: #ffffff;
}

@keyframes ecaPosterFadeIn {
    from { opacity: 0; }
    to { opacity: 1; }
}

@keyframes ecaPosterScaleIn {
    from { opacity: 0; transform: translateY(14px) scale(0.96); }
    to { opacity: 1; transform: translateY(0) scale(1); }
}

@media (max-width: 576px) {
    .eca-poster-modal {
        padding: 26px 10px 18px;
    }

    .eca-poster-box {
        width: min(94vw, 72vh);
        padding: 5px;
        border-radius: 14px;
    }

    .eca-poster-image-wrap {
        border-radius: 9px;
    }

    .eca-poster-close-x {
        top: -13px;
        right: -8px;
        width: 42px;
        height: 42px;
        font-size: 26px;
    }
}

@media (max-height: 520px) {
    .eca-poster-modal {
        padding-top: 18px;
        padding-bottom: 12px;
    }

    .eca-poster-box {
        width: min(90vw, 68vh);
        max-height: 85vh;
    }

    .eca-poster-close-x {
        top: -10px;
        right: -12px;
        width: 38px;
        height: 38px;
        font-size: 24px;
    }
}
</style>

    
</head>

<body class="eca-home">
    <!-- Spinner Start -->
    <div id="spinner" class="show bg-white position-fixed translate-middle w-100 vh-100 top-50 start-50 d-flex align-items-center justify-content-center">
        <div class="spinner-grow text-primary" style="width: 3rem; height: 3rem;" role="status">
            <span class="sr-only">Loading...</span>
        </div>
    </div>
  





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

<section class="eca-enpf-hero" aria-label="Welcome">
    <div class="container eca-enpf-hero-grid">
        <div class="eca-enpf-copy">
            <img src="img/ecalogo.png" alt="Eswatini Contractors Association" class="eca-enpf-logo">
            <p>
                Established in 1991 as the professional body for construction companies and allied trades.
                ECA represents members, raises industry standards, and advances Swati contractors.
            </p>
            <a href="about.php" class="eca-learn-more">Learn more</a>
        </div>
        <div class="eca-enpf-photo">
            <img src="assets/images/slider/slide-3.jpg" alt="ECA contractors at work">
        </div>
    </div>
</section>

<section class="eca-section eca-home-actions" aria-label="Get started">
    <div class="container">
        <div class="eca-page-intro">
            <p class="eca-kicker">Get started</p>
            <h2>What you need, in one place.</h2>
            <p>Use the directory, join the association, or check how to choose a contractor you can trust.</p>
        </div>
        <div class="eca-action-grid">
            <a class="eca-action-card" href="directory.php">
                <i class="fas fa-address-book" aria-hidden="true"></i>
                <h3>Member directory</h3>
                <p>Find verified ECA contractors working across Eswatini.</p>
                <span>Browse members</span>
            </a>
            <a class="eca-action-card" href="application.php">
                <i class="fas fa-id-card" aria-hidden="true"></i>
                <h3>Join ECA</h3>
                <p>Apply for membership and stand with the industry's professional body.</p>
                <span>Start application</span>
            </a>
            <a class="eca-action-card" href="checklist.php">
                <i class="fas fa-clipboard-check" aria-hidden="true"></i>
                <h3>Contractor checklist</h3>
                <p>A clear guide for clients choosing a contractor they can trust.</p>
                <span>Use the checklist</span>
            </a>
            <a class="eca-action-card" href="balingani-directory.php">
                <i class="fas fa-users" aria-hidden="true"></i>
                <h3>Balingani</h3>
                <p>Eswatini Women in Construction - visibility and opportunity.</p>
                <span>Meet Balingani</span>
            </a>
        </div>
    </div>
</section>

<section class="eca-section eca-home-news" aria-labelledby="ecaNewsHeading">
    <div class="container">
        <div class="eca-news-head">
            <div class="eca-page-intro mb-0">
                <p class="eca-kicker">Newsroom</p>
                <h2 id="ecaNewsHeading">Latest updates</h2>
            </div>
            <a href="news.php" class="eca-btn eca-btn-navy">View all news</a>
        </div>

        <div class="eca-news-simple-grid">
            <?php if (!empty($newsItems)): ?>
                <?php foreach ($newsItems as $news): ?>
                    <?php
                    $newsTitle = trim((string)($news['title'] ?? 'ECA update'));
                    $newsDate  = !empty($news['date']) ? date('d M Y', strtotime($news['date'])) : '';
                    $newsImage = !empty($news['image'])
                        ? 'portal/' . ltrim((string)$news['image'], '/')
                        : 'img/logo.jpg';
                    $newsUrl = trim((string)($news['link'] ?? ''));
                    if ($newsUrl === '') {
                        $newsUrl = 'news.php';
                    } elseif (!preg_match('~^https?://~i', $newsUrl)) {
                        $newsUrl = ltrim($newsUrl, '/');
                    }
                    $newsSummary = trim(strip_tags((string)($news['summary'] ?? '')));
                    if ($newsSummary !== '' && strlen($newsSummary) > 140) {
                        $newsSummary = substr($newsSummary, 0, 137) . '...';
                    }
                    ?>
                    <article class="eca-news-simple-card">
                        <a href="<?= htmlspecialchars($newsUrl, ENT_QUOTES, 'UTF-8') ?>">
                            <img
                                src="<?= htmlspecialchars($newsImage, ENT_QUOTES, 'UTF-8') ?>"
                                alt="<?= htmlspecialchars($newsTitle, ENT_QUOTES, 'UTF-8') ?>"
                                loading="lazy"
                                onerror="this.onerror=null;this.src='img/logo.jpg';"
                            >
                            <div>
                                <?php if ($newsDate !== ''): ?>
                                    <time><?= htmlspecialchars($newsDate, ENT_QUOTES, 'UTF-8') ?></time>
                                <?php endif; ?>
                                <h3><?= htmlspecialchars($newsTitle, ENT_QUOTES, 'UTF-8') ?></h3>
                                <?php if ($newsSummary !== ''): ?>
                                    <p><?= htmlspecialchars($newsSummary, ENT_QUOTES, 'UTF-8') ?></p>
                                <?php endif; ?>
                            </div>
                        </a>
                    </article>
                <?php endforeach; ?>
            <?php else: ?>
                <p class="eca-news-empty">Association news will appear here when new updates are published.</p>
            <?php endif; ?>
        </div>
    </div>
</section>

<section class="eca-section eca-home-cta">
    <div class="container">
        <div class="eca-home-cta-panel">
            <div>
                <p class="eca-kicker" style="color:#ffd48a;">Membership</p>
                <h2>Join Eswatini's professional contractor community.</h2>
                <p>Membership gives your firm a trusted listing and a voice on issues that shape the industry.</p>
            </div>
            <div class="eca-home-cta-actions">
                <a href="application.php" class="eca-btn">Apply now</a>
                <a href="contact.php" class="eca-btn-ghost">Contact ECA</a>
            </div>
        </div>
    </div>
</section>

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

.accent-blue { background: linear-gradient(90deg, #000066, #c8102e); }
.accent-green { background: linear-gradient(90deg, #0f3d38, #000066); }
.accent-orange { background: linear-gradient(90deg, #c4a35a, #000066); }

.service-item h4 {
    color: #000066;
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
	
const uPrevBtn = document.querySelector('.unique-slider .u-prev');
const uNextBtn = document.querySelector('.unique-slider .u-next');
const searchBtn = document.getElementById('searchBtn');
const searchInput = document.getElementById('searchInput');
if (uPrevBtn && uNextBtn) {
    const uSlides = document.querySelectorAll('.unique-slider .u-slide');
    let uIndex = 0;
    function showUSlide(index) {
        uSlides.forEach((slide, i) => slide.classList.toggle('active', i === index));
    }
    uPrevBtn.addEventListener('click', () => {
        uIndex = (uIndex - 1 + uSlides.length) % uSlides.length;
        showUSlide(uIndex);
    });
    uNextBtn.addEventListener('click', () => {
        uIndex = (uIndex + 1) % uSlides.length;
        showUSlide(uIndex);
    });
    if (uSlides.length) {
        setInterval(() => {
            uIndex = (uIndex + 1) % uSlides.length;
            showUSlide(uIndex);
        }, 5000);
        showUSlide(uIndex);
    }
}

const newsContainer = document.getElementById('newsContainer');
const sliderContainer = document.getElementById('sliderContainer');
if (searchBtn && searchInput && newsContainer && sliderContainer) {
    searchBtn.addEventListener('click', function () {
        fetch('search-news.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: 'query=' + encodeURIComponent(searchInput.value)
        })
        .then(res => res.json())
        .then(data => {
            newsContainer.innerHTML = data.news;
        });
    });
}


	</script>

<!-- =====================================================
     MDB COMPLIANCE TRAINING POSTER POPUP
     Opens automatically whenever the page finishes loading.

<div
    id="ecaTrainingPosterModal"
    class="eca-poster-modal"
    role="dialog"
    aria-modal="true"
    aria-hidden="true"
    aria-labelledby="ecaTrainingPosterTitle">

    <div class="eca-poster-box" role="document">
        <div class="eca-poster-topbar">
            <span id="ecaTrainingPosterTitle" class="eca-poster-badge">
                MDB COMPLIANCE TRAINING
            </span>

            <button
                id="ecaTrainingPosterClose"
                class="eca-poster-close-x"
                type="button"
                aria-label="Close training poster">
                &times;
            </button>
        </div>

        <div class="eca-poster-image-wrap">
            <a
                href="https://eca.co.sz/cpd/registration.php"
                aria-label="Open MDB Compliance Training registration page">
                <img
                    src="img/mdb-training-poster.jpeg"
                    alt="Multilateral Development Bank Standard Bidding Documents Compliance Training poster"
                    class="eca-poster-image">
            </a>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    'use strict';

    const modal = document.getElementById('ecaTrainingPosterModal');
    const closeButton = document.getElementById('ecaTrainingPosterClose');

    if (!modal || !closeButton) {
        return;
    }

    let lastFocusedElement = null;

    function openTrainingPoster() {
        lastFocusedElement = document.activeElement;
        modal.classList.add('show');
        modal.setAttribute('aria-hidden', 'false');
        document.body.style.overflow = 'hidden';
        closeButton.focus();
    }

    function closeTrainingPoster() {
        modal.classList.remove('show');
        modal.setAttribute('aria-hidden', 'true');
        document.body.style.overflow = '';

        if (lastFocusedElement && typeof lastFocusedElement.focus === 'function') {
            lastFocusedElement.focus();
        }
    }

    /* Open shortly after the full page has loaded. */
    window.addEventListener('load', function () {
        window.setTimeout(openTrainingPoster, 700);
    });

    closeButton.addEventListener('click', closeTrainingPoster);

    /* Close when the dark background outside the poster is clicked. */
    modal.addEventListener('click', function (event) {
        if (event.target === modal) {
            closeTrainingPoster();
        }
    });

    /* Close with the Escape key. */
    document.addEventListener('keydown', function (event) {
        if (event.key === 'Escape' && modal.classList.contains('show')) {
            closeTrainingPoster();
        }
    });
});
</script>
===================================================== -->
</body>
</html>
