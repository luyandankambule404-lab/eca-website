<?php require_once "header.php"; ?>

<style>
:root{
  --navy:#06254a;
  --blue:#0e5aa7;
  --cyan:#35c7ff;
  --gold:#f7b731;
  --ink:#0f172a;
  --muted:#64748b;
  --white:#ffffff;
  --shadow:0 30px 80px rgba(2,8,23,.18);
}

*{
  box-sizing:border-box;
}

body{
  background:#f4f7fb;
}

.cpd-landing{
  position:relative;
  min-height:calc(100vh - 120px);
  overflow:hidden;
  padding:60px 22px 80px;
  border-radius:28px;
  background:
    radial-gradient(circle at 10% 10%, rgba(53,199,255,.30), transparent 32%),
    radial-gradient(circle at 90% 18%, rgba(247,183,49,.24), transparent 30%),
    radial-gradient(circle at 50% 100%, rgba(14,90,167,.14), transparent 35%),
    linear-gradient(135deg,#eef7ff 0%, #f8fbff 45%, #eef3ff 100%);
}

.cpd-landing::before{
  content:"";
  position:absolute;
  inset:0;
  background-image:
    linear-gradient(rgba(6,37,74,.055) 1px, transparent 1px),
    linear-gradient(90deg, rgba(6,37,74,.055) 1px, transparent 1px);
  background-size:42px 42px;
  pointer-events:none;
}

.cpd-orb{
  position:absolute;
  border-radius:50%;
  animation:floatOrb 8s ease-in-out infinite;
}

.cpd-orb.one{
  width:230px;
  height:230px;
  left:-70px;
  top:80px;
  background:linear-gradient(135deg,rgba(14,90,167,.25),rgba(53,199,255,.18));
}

.cpd-orb.two{
  width:170px;
  height:170px;
  right:6%;
  top:18%;
  background:linear-gradient(135deg,rgba(247,183,49,.28),rgba(255,255,255,.16));
  animation-delay:1.4s;
}

.cpd-orb.three{
  width:200px;
  height:200px;
  right:18%;
  bottom:6%;
  background:linear-gradient(135deg,rgba(14,90,167,.14),rgba(53,199,255,.18));
  animation-delay:2.2s;
}

@keyframes floatOrb{
  0%,100%{
    transform:translateY(0) translateX(0);
  }
  50%{
    transform:translateY(-18px) translateX(10px);
  }
}

.learning-shell{
  position:relative;
  z-index:2;
  max-width:1050px;
  margin:0 auto;
}

.hero-card{
  position:relative;
  overflow:hidden;
  border-radius:34px;
  padding:44px;
  background:rgba(255,255,255,.78);
  backdrop-filter:blur(18px);
  border:1px solid rgba(255,255,255,.75);
  box-shadow:var(--shadow);
  text-align:center;
}

.hero-card::after{
  content:"";
  position:absolute;
  width:360px;
  height:360px;
  right:-150px;
  top:-150px;
  border-radius:50%;
  background:linear-gradient(135deg,rgba(14,90,167,.18),rgba(53,199,255,.13));
}

.hero-chip{
  position:relative;
  z-index:2;
  display:inline-flex;
  align-items:center;
  gap:9px;
  padding:10px 18px;
  border-radius:999px;
  background:linear-gradient(135deg,rgba(6,37,74,.10),rgba(53,199,255,.13));
  color:var(--navy);
  font-size:13px;
  font-weight:900;
  letter-spacing:.4px;
  text-transform:uppercase;
  margin-bottom:22px;
}

.hero-chip i{
  color:var(--gold);
}

.hero-title{
  position:relative;
  z-index:2;
  max-width:850px;
  margin:0 auto;
  color:var(--ink);
  font-size:clamp(36px,4.5vw,62px);
  font-weight:950;
  line-height:1.02;
  letter-spacing:-1.8px;
}

.hero-title span{
  background:linear-gradient(135deg,var(--navy),var(--blue),#15b8ff);
  -webkit-background-clip:text;
  -webkit-text-fill-color:transparent;
}

.hero-subtitle{
  position:relative;
  z-index:2;
  max-width:760px;
  margin:20px auto 0;
  color:var(--muted);
  font-size:16px;
  line-height:1.75;
  font-weight:500;
}

.learner-login-card{
  position:relative;
  z-index:2;
  max-width:760px;
  margin:34px auto 0;
  border-radius:32px;
  overflow:hidden;
  background:linear-gradient(135deg,#06254a,#0e5aa7 58%,#35c7ff);
  color:#fff;
  box-shadow:0 28px 70px rgba(6,37,74,.28);
}

.learner-login-card::before{
  content:"";
  position:absolute;
  width:260px;
  height:260px;
  top:-125px;
  right:-90px;
  border-radius:50%;
  background:rgba(255,255,255,.18);
}

.learner-login-card::after{
  content:"";
  position:absolute;
  width:150px;
  height:150px;
  bottom:-75px;
  left:80px;
  border-radius:50%;
  background:rgba(247,183,49,.22);
}

.learner-login-content{
  position:relative;
  z-index:2;
  padding:34px;
  display:flex;
  align-items:center;
  justify-content:space-between;
  gap:24px;
  text-align:left;
}

.learner-icon{
  width:92px;
  height:92px;
  border-radius:28px;
  display:flex;
  align-items:center;
  justify-content:center;
  background:rgba(255,255,255,.16);
  border:1px solid rgba(255,255,255,.28);
  font-size:42px;
  color:var(--gold);
  flex-shrink:0;
}

.learner-text h3{
  margin:0;
  font-weight:950;
  font-size:30px;
  letter-spacing:-.7px;
}

.learner-text p{
  margin:9px 0 0;
  color:rgba(255,255,255,.84);
  font-size:15px;
  line-height:1.65;
}

.learner-btn{
  display:inline-flex;
  align-items:center;
  justify-content:center;
  gap:10px;
  min-height:54px;
  padding:14px 26px;
  border-radius:999px;
  background:#fff;
  color:var(--navy);
  font-weight:950;
  text-decoration:none;
  box-shadow:0 14px 30px rgba(0,0,0,.16);
  transition:.25s ease;
  white-space:nowrap;
}

.learner-btn:hover{
  transform:translateY(-3px);
  color:var(--blue);
  background:#fff;
}

.learning-steps{
  display:grid;
  grid-template-columns:repeat(3,1fr);
  gap:18px;
  margin-top:30px;
}

.step-card{
  padding:24px;
  border-radius:26px;
  background:rgba(255,255,255,.72);
  backdrop-filter:blur(18px);
  border:1px solid rgba(255,255,255,.80);
  box-shadow:0 18px 45px rgba(15,23,42,.08);
  text-align:left;
}

.step-card i{
  width:56px;
  height:56px;
  border-radius:18px;
  display:flex;
  align-items:center;
  justify-content:center;
  color:#fff;
  background:linear-gradient(135deg,var(--navy),var(--blue));
  margin-bottom:18px;
  font-size:23px;
}

.step-card h4{
  margin:0 0 9px;
  color:var(--ink);
  font-size:18px;
  font-weight:950;
}

.step-card p{
  margin:0;
  color:var(--muted);
  line-height:1.65;
  font-weight:500;
}

.footer-note{
  margin-top:24px;
  padding:17px 20px;
  border-radius:22px;
  background:rgba(255,255,255,.64);
  border:1px solid rgba(255,255,255,.78);
  color:var(--muted);
  font-weight:700;
  text-align:center;
  box-shadow:0 12px 30px rgba(15,23,42,.06);
}

.footer-note i{
  color:var(--gold);
  margin-right:8px;
}

@media(max-width:992px){
  .learner-login-content{
    flex-direction:column;
    align-items:flex-start;
  }

  .learning-steps{
    grid-template-columns:1fr;
  }
}

@media(max-width:576px){
  .cpd-landing{
    padding:32px 14px 48px;
    border-radius:20px;
  }

  .hero-card{
    border-radius:24px;
    padding:26px;
  }

  .learner-login-content{
    padding:26px;
  }

  .learner-text h3{
    font-size:24px;
  }

  .learner-btn{
    width:100%;
  }
}
</style>

<div class="cpd-landing">
  <div class="cpd-orb one"></div>
  <div class="cpd-orb two"></div>
  <div class="cpd-orb three"></div>

  <div class="learning-shell">

    <div class="hero-card">

      <div class="hero-chip">
        <i class="fa fa-graduation-cap"></i>
        Learner Access Only
      </div>

      <h1 class="hero-title">
        Welcome to the <span>CPD Learning Portal</span>
      </h1>

      <p class="hero-subtitle">
        Login as a learner to manage your CPD training applications, view attendance progress,
        track CPD points, and download completed course certificates.
      </p>

      <div class="learner-login-card">
        <div class="learner-login-content">

          <div class="learner-icon">
            <i class="fa fa-user-graduate"></i>
          </div>

          <div class="learner-text">
            <h3>Learner Login</h3>
            <p>
              Access your learner dashboard, training records, CPD points, and certificates.
            </p>
          </div>

          <a href="/cpd/app/index.php" class="learner-btn">
            <i class="fa fa-sign-in-alt"></i>
            Login as Learner
          </a>

        </div>
      </div>

      <div class="learning-steps">

        <div class="step-card">
          <i class="fa fa-file-signature"></i>
          <h4>Apply</h4>
          <p>Submit your CPD training application through the learner portal.</p>
        </div>

        <div class="step-card">
          <i class="fa fa-user-check"></i>
          <h4>Attend</h4>
          <p>Track your course attendance and training participation progress.</p>
        </div>

        <div class="step-card">
          <i class="fa fa-certificate"></i>
          <h4>Certify</h4>
          <p>Download your certificate after successful course completion.</p>
        </div>

      </div>

      <div class="footer-note">
        <i class="fa fa-circle-info"></i>
        Learner Portal • Applications • Attendance • CPD Points • Certificates
      </div>

    </div>

  </div>
</div>

<?php require_once "footer.php"; ?>