<?php 
require_once "../config.php";
require_once "../helpers.php";

$err="";

if(is_post()){

$email=trim($_POST['email']);
$pass=$_POST['password'];

$stmt=$conn->prepare("
SELECT id,role,email,full_name,password_hash,status
FROM user
WHERE email=? LIMIT 1
");

$stmt->bind_param("s",$email);
$stmt->execute();

$u=$stmt->get_result()->fetch_assoc();

if(!$u || !password_verify($pass,$u['password_hash'])){

$err="Invalid login.";

}elseif($u['role']!='CONTRACTOR'){

$err="Please use the admin portal.";

}else{

$_SESSION['user_id']=$u['id'];
$_SESSION['role']=$u['role'];
$_SESSION['full_name']=$u['full_name'];
$_SESSION['email']=$u['email'];

redirect("/cpd/app/dashboard.php");

}

}
?>

<!DOCTYPE html>
<html>
<head>

<meta name="viewport" content="width=device-width, initial-scale=1">

<title>ECA CPD Portal</title>

<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css">

<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">

<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">

<style>

*{
box-sizing:border-box;
}

body{
font-family:Poppins;
height:100vh;
margin:0;
display:flex;
align-items:center;
justify-content:center;
background:linear-gradient(135deg,#06254a,#0e5aa7,#1a73d9);
background-size:300% 300%;
animation:gradientMove 10s ease infinite;
}

/* animated gradient */

@keyframes gradientMove{
0%{background-position:0% 50%;}
50%{background-position:100% 50%;}
100%{background-position:0% 50%;}
}

/* container */

.app-login{
width:100%;
max-width:400px;
padding:20px;
}

/* logo */

.logo{
width:120px;
border-radius:8px;

display:block;
margin:auto;
margin-bottom:20px;
}

/* card */

.login-card{

background:rgba(255,255,255,0.95);
backdrop-filter:blur(15px);

border-radius:8px;

padding:35px 30px;

box-shadow:
0 30px 60px rgba(0,0,0,0.35);

}

/* titles */

.login-title{
text-align:center;
font-weight:700;
font-size:22px;
color:#fff;
}

.login-sub{
text-align:center;
font-size:13px;
color:#fff;
margin-bottom:25px;
}

/* inputs */

.input-group{
border-radius:12px;
overflow:hidden;
}

.input-group-text{
background:#f6f8fb;
border:none;
color:#0e5aa7;
width:45px;
justify-content:center;
}

.form-control{

border:none;
height:48px;
font-size:14px;

}

.form-control:focus{
box-shadow:none;
border:1px solid #0e5aa7;
}

/* password icon */

.toggle-eye{
cursor:pointer;
color:#777;
}

/* button */

.btn-login{

height:48px;

border:none;

border-radius:30px;

background:linear-gradient(135deg,#e3262e,#ff4040);

font-weight:600;

color:white;

transition:all .3s;

}

.btn-login:hover{

transform:translateY(-2px);

box-shadow:0 10px 20px rgba(227,38,46,0.35);

}

/* register */

.register{
text-align:center;
margin-top:20px;
font-size:14px;
}

.register a{
color:#06254a;
font-weight:600;
text-decoration:none;
}

.register a:hover{
text-decoration:underline;
}

/* mobile */

@media(max-width:500px){

.login-card{
padding:25px 20px;
}

}

*{
box-sizing:border-box;
}

body{

font-family:Poppins;
height:100vh;
margin:0;

display:flex;
align-items:center;
justify-content:center;

background: radial-gradient(circle at 20% 30%, #1f7ae0 0%, transparent 40%),
radial-gradient(circle at 80% 70%, #e3262e 0%, transparent 40%),
linear-gradient(135deg,#051f3c,#0a4b8a,#0e5aa7);

background-size:200% 200%;
animation:gradientMove 12s ease infinite;

}

/* animated background */

@keyframes gradientMove{

0%{background-position:0% 50%;}
50%{background-position:100% 50%;}
100%{background-position:0% 50%;}

}

/* container */

.app-login{

width:100%;
max-width:380px;
padding:20px;

}

/* glass login card */

.login-card{

background:rgba(255,255,255,0.10);
backdrop-filter:blur(18px);

border-radius:24px;

padding:35px 25px;

box-shadow:
0 20px 60px rgba(0,0,0,0.45),
inset 0 1px 1px rgba(255,255,255,0.25);

border:1px solid rgba(255,255,255,0.18);

color:white;

}

/* logo */



/* titles */

.login-title{

text-align:center;
font-weight:700;
font-size:22px;
margin-bottom:4px;

}

.login-sub{

text-align:center;
font-size:13px;
opacity:.8;
margin-bottom:25px;

}

/* inputs */

.input-group{

border-radius:14px;
overflow:hidden;

background:rgba(255,255,255,0.15);

}

.input-group-text{

background:transparent;
border:none;
color:white;

width:45px;
justify-content:center;

}

.form-control{

background:transparent;
border:none;
color:white;

height:48px;

}

.form-control::placeholder{
color:rgba(255,255,255,0.7);
}

.form-control:focus{

box-shadow:none;

}

/* password icon */

.toggle-eye{

cursor:pointer;
color:white;

}

/* login button */

.btn-login{

height:50px;

border:none;

border-radius:10px;

background:linear-gradient(135deg,#ff3c3c,#e3262e);

font-weight:600;

color:white;

box-shadow:
0 6px 18px rgba(227,38,46,0.4);

transition:.3s;

}

.btn-login:hover{

transform:translateY(-2px);

box-shadow:
0 10px 28px rgba(227,38,46,0.6);

}

/* register */

.register{

text-align:center;
margin-top:18px;
font-size:14px;

}

.register a{

color:white;
font-weight:600;
text-decoration:none;

}

.register a:hover{
text-decoration:underline;
}

/* alert */

.alert{

background:rgba(255,0,0,0.15);
border:none;
color:white;

}

/* mobile adjustments */

@media(max-width:500px){

.login-card{
padding:28px 20px;
}

.logo{
width:80px;
}

}</style>
</head>

<body>

<div class="app-login">

<div class="login-card">

<img src="https://eca.co.sz/cpd/images/logo.jpg" class="logo">

<div class="login-title">
Login
</div>

<div class="login-sub">
ECA CPD Training Portal
</div>

<?php if($err): ?>
<div class="alert alert-danger"><?=$err?></div>
<?php endif; ?>

<form method="post">

<div class="mb-3">

<div class="input-group">

<span class="input-group-text">
<i class="fa fa-envelope"></i>
</span>

<input class="form-control" name="email" type="email" placeholder="Email address" required>

</div>

</div>

<div class="mb-3">

<div class="input-group">

<span class="input-group-text">
<i class="fa fa-lock"></i>
</span>

<input class="form-control" id="password" name="password" type="password" placeholder="Password" required>

<span class="input-group-text toggle-eye" onclick="togglePassword()">
<i id="eyeIcon" class="fa fa-eye"></i>
</span>

</div>

</div>

<button class="btn btn-login w-100">
<i class="fa fa-sign-in-alt me-2"></i> Login
</button>

</form>



</div>

</div>

<script>

function togglePassword(){

var p=document.getElementById("password");
var icon=document.getElementById("eyeIcon");

if(p.type==="password"){
p.type="text";
icon.classList.remove("fa-eye");
icon.classList.add("fa-eye-slash");
}else{
p.type="password";
icon.classList.remove("fa-eye-slash");
icon.classList.add("fa-eye");
}

}

</script>

</body>
</html>