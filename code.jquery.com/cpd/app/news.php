<?php
require_once "../auth.php";
require_role('CONTRACTOR');
require_once "../config.php";

/* ===============================
GET NEWS
================================ */

$news = $conn->query("
SELECT *
FROM news
WHERE status='published'
ORDER BY created_at DESC
");
?>

<!DOCTYPE html>
<html>
<head>

<meta name="viewport" content="width=device-width, initial-scale=1">

<link rel="stylesheet"
href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css">

<link rel="stylesheet"
href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">

<style>

body{
background:#f4f6f9;
font-family:Poppins;
margin:0;
}

/* HEADER */

.app-header{
background:linear-gradient(135deg,#06254a,#0b4c92);
padding:18px 16px 45px 16px;
color:white;
position:sticky;
top:0;
z-index:999;
border-bottom-left-radius:20px;
border-bottom-right-radius:20px;
box-shadow:0 15px 40px rgba(0,0,0,0.35);
}

.header-inner{
display:flex;
align-items:center;
gap:10px;
}

.logo{
height:38px;
border-radius:10px;
}

.app-title{
font-weight:700;
font-size:15px;
}

.app-sub{
font-size:11px;
opacity:.8;
}

/* NEWS CARD */

.news-card{

background:white;

border-radius:16px;

overflow:hidden;

box-shadow:0 10px 25px rgba(0,0,0,.1);

margin-bottom:16px;

}

.news-image{

height:180px;

background-size:cover;

background-position:center;

}

.news-body{

padding:16px;

}

.news-title{

font-size:16px;

font-weight:600;

}

.news-summary{

font-size:13px;

color:#555;

margin-top:6px;

}

.news-date{

font-size:11px;

color:#999;

margin-top:6px;

}

.btn-read{

margin-top:10px;

background:#e3262e;

color:white;

padding:8px 12px;

border-radius:8px;

font-size:13px;

border:none;

}

/* BOTTOM NAV */

.bottom-nav{

position:fixed;

bottom:12px;

left:50%;

transform:translateX(-50%);

width:92%;

max-width:500px;

background:white;

border-radius:18px;

box-shadow:0 10px 30px rgba(0,0,0,0.15);

display:flex;

justify-content:space-around;

padding:10px;

}

.nav-item{

display:flex;

flex-direction:column;

align-items:center;

font-size:11px;

text-decoration:none;

color:#6b7280;

}

.nav-item.active{

color:#e3262e;

font-weight:600;

}

body{
padding-bottom:90px;
}

</style>

</head>

<body>


<!-- HEADER -->

<div class="app-header">

<div class="header-inner">

<img src="https://eca.co.sz/cpd/images/logo.jpg" class="logo">

<div>

<div class="app-title">ECA CPD</div>
<div class="app-sub">News & Updates</div>

</div>

</div>

</div>


<div class="container mt-3">


<?php if($news && $news->num_rows > 0): ?>

<?php while($n=$news->fetch_assoc()): ?>

<div class="news-card">

<div class="news-image"
style="background-image:url('../uploads/news/<?=$n['image']?>')">
</div>

<div class="news-body">

<div class="news-title">
<?=$n['title']?>
</div>

<div class="news-summary">
<?=substr($n['summary'],0,120)?>...
</div>

<div class="news-date">
<i class="fa fa-clock"></i>
<?=date("d M Y",strtotime($n['created_at']))?>
</div>

<button class="btn-read"
data-bs-toggle="modal"
data-bs-target="#news<?=$n['id']?>">

Read More

</button>

</div>

</div>


<!-- NEWS MODAL -->

<div class="modal fade" id="news<?=$n['id']?>">

<div class="modal-dialog modal-dialog-centered">

<div class="modal-content">

<div class="modal-header">

<h5><?=$n['title']?></h5>

<button class="btn-close" data-bs-dismiss="modal"></button>

</div>

<div class="modal-body">

<p><?=$n['content']?></p>

</div>

</div>

</div>

</div>


<?php endwhile; ?>

<?php else: ?>

<div class="text-center text-muted">
No news available.
</div>

<?php endif; ?>


</div>


<!-- BOTTOM NAV -->

<div class="bottom-nav">

<a href="dashboard.php" class="nav-item">
<i class="fa fa-home"></i>
<span>Home</span>
</a>

<a href="courses.php" class="nav-item">
<i class="fa fa-book"></i>
<span>Courses</span>
</a>

<a href="news.php" class="nav-item active">
<i class="fa fa-star"></i>
<span>News</span>
</a>

<a href="account.php" class="nav-item">
<i class="fa fa-user"></i>
<span>Account</span>
</a>

</div>


<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>

</body>
</html>