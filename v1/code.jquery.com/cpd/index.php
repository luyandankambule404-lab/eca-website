<?php require_once "header.php"; ?>

<style>
body:not(.hub-root){
  padding:0 !important;
  background:#020f29 !important;
}
body:not(.hub-root) > .container.py-4{
  max-width:none;
  padding:0;
  margin:0;
}
:root{
  --eca-navy:#041f52;
  --eca-navy-dark:#020f29;
  --eca-navy-soft:#0a2f6b;
  --eca-red:#c40000;
  --eca-red-bright:#e60000;
  --eca-red-dark:#8f0000;
  --white:#ffffff;
}
.cpd-landing{
  position:relative;
  width:100vw;
  min-height:100vh;
  margin-left:calc(50% - 50vw);
  margin-right:calc(50% - 50vw);
  overflow:hidden;
  padding:0;
  background:
    linear-gradient(90deg, rgba(2,15,41,.98) 0%, rgba(4,31,82,.94) 42%, rgba(4,31,82,.76) 100%),
    url("/cpd/image/construction12.png") right center / cover no-repeat;
}
.cpd-landing::before{
  content:"";
  position:absolute;
  inset:88px 0 0;
  background-image:
    linear-gradient(rgba(255,255,255,.075) 1px, transparent 1px),
    linear-gradient(90deg, rgba(255,255,255,.075) 1px, transparent 1px);
  background-size:42px 42px;
  z-index:1;
  pointer-events:none;
}
.cpd-landing::after{
  content:"";
  position:absolute;
  inset:0;
  background:linear-gradient(90deg, rgba(2,10,24,.24), rgba(4,31,82,.10), rgba(2,10,24,.30));
  z-index:2;
  pointer-events:none;
}
.portal-topbar{
  position:relative;
  z-index:5;
  height:88px;
  padding:18px 56px;
  display:grid;
  grid-template-columns:260px 1fr 220px;
  align-items:center;
  background:linear-gradient(180deg, rgba(2,15,41,.98), rgba(4,31,82,.94));
  border-bottom:3px solid var(--eca-red);
  box-shadow:0 10px 28px rgba(0,0,0,.24);
}
.portal-logo{
  display:flex;
  align-items:center;
  gap:12px;
}
.portal-logo img{
  width:170px;
  max-height:62px;
  object-fit:contain;
}
.portal-system-title{
  text-align:center;
  color:#ffffff;
  font-size:34px;
  font-weight:950;
  letter-spacing:.8px;
  text-transform:uppercase;
  text-shadow:0 3px 8px rgba(0,0,0,.45);
}
.portal-system-title::after{
  content:"";
  display:block;
  width:90px;
  height:4px;
  margin:8px auto 0;
  border-radius:999px;
  background:linear-gradient(90deg, transparent, var(--eca-red), transparent);
}
.training-badge{
  justify-self:end;
  display:inline-flex;
  align-items:center;
  justify-content:center;
  min-height:38px;
  padding:8px 18px;
  border:2px solid rgba(255,255,255,.75);
  border-radius:8px;
  color:#ffffff;
  font-size:14px;
  font-weight:950;
  text-transform:uppercase;
  letter-spacing:.4px;
  background:linear-gradient(135deg, var(--eca-red-dark), var(--eca-red));
  box-shadow:0 10px 22px rgba(196,0,0,.26);
}
.eca-left-shape{
  position:absolute;
  z-index:3;
  left:-90px;
  top:88px;
  width:430px;
  height:calc(100% - 88px);
  background:linear-gradient(135deg, rgba(196,0,0,.92), rgba(143,0,0,.82));
  clip-path:polygon(0 0, 54% 0, 84% 54%, 60% 100%, 0 100%);
  opacity:.92;
}
.eca-right-shape{
  position:absolute;
  z-index:3;
  right:-140px;
  bottom:78px;
  width:360px;
  height:330px;
  border-radius:50%;
  background:linear-gradient(135deg, rgba(196,0,0,.38), rgba(143,0,0,.24));
  opacity:.78;
}
.building-overlay{
  position:absolute;
  z-index:2;
  right:0;
  top:88px;
  bottom:0;
  width:48%;
  background:
    linear-gradient(90deg, rgba(4,31,82,.92), rgba(4,31,82,.28)),
    url("/cpd/image/construction12.png") right center / cover no-repeat;
  opacity:.78;
  mix-blend-mode:screen;
}
.blueprint-lines{
  position:absolute;
  z-index:3;
  left:90px;
  bottom:36px;
  width:430px;
  height:250px;
  opacity:.28;
  background:
    linear-gradient(90deg, rgba(255,255,255,.45) 1px, transparent 1px),
    linear-gradient(rgba(255,255,255,.42) 1px, transparent 1px);
  background-size:38px 38px;
  clip-path:polygon(0 38%, 18% 26%, 30% 36%, 45% 18%, 64% 30%, 80% 12%, 100% 34%, 100% 100%, 0 100%);
}
.portal-content{
  position:relative;
  z-index:6;
  min-height:calc(100vh - 88px);
  padding:48px 22px 44px;
  display:flex;
  flex-direction:column;
  align-items:center;
}
.hero-title{
  max-width:930px;
  margin:0 auto;
  color:#ffffff;
  text-align:center;
  font-size:clamp(36px,4.6vw,66px);
  font-weight:950;
  line-height:1.08;
  letter-spacing:-1.5px;
  text-shadow:0 4px 0 rgba(2,15,41,.55), 0 12px 26px rgba(0,0,0,.45);
}
.learner-login-card{
  position:relative;
  width:min(760px,94%);
  margin:34px auto 0;
  border-radius:22px;
  overflow:hidden;
  background:
    radial-gradient(circle at 92% 20%, rgba(255,255,255,.18), transparent 22%),
    linear-gradient(135deg, rgba(2,15,41,.98) 0%, rgba(4,31,82,.98) 45%, rgba(196,0,0,.96) 100%);
  color:#fff;
  border:1px solid rgba(255,255,255,.30);
  box-shadow:0 26px 65px rgba(0,0,0,.38), inset 0 1px 0 rgba(255,255,255,.18);
}
.learner-login-content{
  position:relative;
  z-index:2;
  padding:28px 32px;
  display:grid;
  grid-template-columns:96px 1fr auto;
  align-items:center;
  gap:22px;
}
.learner-icon{
  width:82px;
  height:82px;
  border-radius:18px;
  display:flex;
  align-items:center;
  justify-content:center;
  background:rgba(255,255,255,.12);
  border:1px solid rgba(255,255,255,.30);
  color:#ffffff;
  font-size:42px;
}
.learner-text h3{
  margin:0;
  color:#fff;
  font-size:29px;
  font-weight:950;
}
.learner-text p{
  margin:7px 0 0;
  color:rgba(255,255,255,.92);
  font-size:15px;
  line-height:1.45;
}
.learner-btn{
  min-height:50px;
  padding:12px 24px;
  display:inline-flex;
  align-items:center;
  justify-content:center;
  gap:10px;
  border-radius:999px;
  border:2px solid rgba(255,255,255,.78);
  background:linear-gradient(135deg, var(--eca-red-dark), var(--eca-red));
  color:#fff;
  font-size:14px;
  font-weight:950;
  text-decoration:none;
  text-transform:uppercase;
  white-space:nowrap;
}
.learner-btn:hover{
  color:#fff;
  background:linear-gradient(135deg, var(--eca-red), var(--eca-red-bright));
}
.portal-actions{
  width:min(760px,94%);
  margin:38px auto 0;
  display:grid;
  grid-template-columns:repeat(3,1fr);
  gap:42px;
}
.action-card{
  position:relative;
  text-align:center;
  color:#fff;
  text-decoration:none;
}
.action-card:not(:last-child)::after{
  content:"";
  position:absolute;
  right:-22px;
  top:14px;
  width:1px;
  height:92px;
  background:linear-gradient(180deg, transparent, rgba(255,255,255,.45), transparent);
}
.action-icon{
  width:88px;
  height:88px;
  margin:0 auto 14px;
  border-radius:50%;
  display:flex;
  align-items:center;
  justify-content:center;
  color:#ffffff;
  font-size:36px;
  background:linear-gradient(135deg, var(--eca-navy-dark), var(--eca-red));
  border:2px solid rgba(255,255,255,.35);
}
.action-title{
  color:#fff;
  font-size:20px;
  font-weight:950;
}
@media(max-width:992px){
  .portal-topbar{
    grid-template-columns:1fr;
    height:auto;
    gap:12px;
    padding:18px 22px;
    text-align:center;
  }
  .portal-logo,
  .training-badge{
    justify-self:center;
  }
  .portal-system-title{
    font-size:28px;
  }
  .learner-login-content{
    grid-template-columns:1fr;
    text-align:center;
    justify-items:center;
  }
  .learner-btn{
    width:100%;
  }
  .action-card:not(:last-child)::after{
    display:none;
  }
}
@media(max-width:576px){
  .portal-actions{
    grid-template-columns:1fr;
  }
}
.hub-root .cpd-landing{
  width:auto;
  min-height:calc(100vh - 180px);
  margin:0;
  border-radius:18px;
}
.hub-root .portal-topbar{
  border-radius:18px 18px 0 0;
}
</style>

<div class="cpd-landing">
  <div class="building-overlay"></div>
  <div class="eca-left-shape"></div>
  <div class="eca-right-shape"></div>
  <div class="blueprint-lines"></div>

  <div class="portal-topbar">
    <a class="portal-logo" href="/index.php">
      <img src="/cpd/images/logo.jpg" alt="ECA Logo">
    </a>
    <div class="portal-system-title">CPD Point System</div>
    <div class="training-badge">Training</div>
  </div>

  <main class="portal-content">
    <h1 class="hero-title">Welcome to the ECA CPD Portal</h1>

    <div class="learner-login-card">
      <div class="learner-login-content">
        <div class="learner-icon"><i class="fa fa-user-graduate"></i></div>
        <div class="learner-text">
          <h3>Learner Login</h3>
          <p>Access your learner dashboard, training records, CPD points, and certificates.</p>
        </div>
        <a href="/cpd/app/index.php" class="learner-btn">
          <i class="fa fa-graduation-cap"></i>
          Login as Learner
          <span class="arrow">→</span>
        </a>
      </div>
    </div>

    <div class="portal-actions">
      <a href="/education-training.php" class="action-card">
        <div class="action-icon"><i class="fa fa-book"></i></div>
        <div class="action-title">Catalogue</div>
      </a>
      <a href="/cpd/contractor/courses.php" class="action-card">
        <div class="action-icon"><i class="fa fa-clipboard-list"></i></div>
        <div class="action-title">Courses</div>
      </a>
      <a href="/cpd/contractor/transcript.php" class="action-card">
        <div class="action-icon"><i class="fa fa-award"></i></div>
        <div class="action-title">Points</div>
      </a>
    </div>
  </main>
</div>

<?php require_once "footer.php"; ?>
