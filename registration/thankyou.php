<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Registration Successful | Eswatini Contractors Association</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<style>
  body {
    position: relative;
    min-height: 100vh;
    font-family: 'Segoe UI', sans-serif;
    overflow-x: hidden;
  }

  body::before {
    content: "";
    position: fixed;
    inset: 0;
    background:
      linear-gradient(135deg, rgba(11,44,102,0.85) 0%, rgba(26,76,160,0.85) 50%, rgba(43,111,210,0.85) 100%),
      url("https://images.unsplash.com/photo-1581090700227-1e37b190418e?auto=format&fit=crop&w=1600&q=80") center/cover no-repeat;
    z-index: -1;
  }

  .thankyou-card {
    max-width: 600px;
    background: #ffffff;
    color: #333;
    margin: 80px auto;
    border-radius: 18px;
    box-shadow: 0 10px 35px rgba(0,0,0,0.4);
    text-align: center;
    padding: 40px 25px;
  }

  .thankyou-card img {
    width: 100px;
    margin-bottom: 15px;
  }

  h2 {
    color: #0b2c66;
    font-weight: 700;
  }

  .details {
    background: #f2f5fa;
    border-radius: 10px;
    padding: 15px;
    text-align: left;
    margin-top: 20px;
  }

  .btn-home {
    margin-top: 25px;
    background: linear-gradient(90deg, #0b2c66, #2b6fd2);
    border: none;
    color: #fff;
    border-radius: 50px;
    padding: 10px 30px;
    text-decoration: none;
    transition: 0.3s;
  }

  .btn-home:hover {
    background: linear-gradient(90deg, #2b6fd2, #0b2c66);
    transform: translateY(-2px);
  }

  @media (max-width: 576px) {
    .thankyou-card {
      margin: 50px 15px;
      padding: 25px 15px;
    }
  }
</style>
</head>
<body>

<div class="thankyou-card">
  <img src="https://eca.co.sz/registration/images/logo.png" alt="ECA Logo">
  <h2>Thank You!</h2>
  <p>Dear <strong><?php echo htmlspecialchars($_GET['attendee_name'] ?? 'Participant'); ?></strong>,</p>
  <p>Your registration for the <strong>Priliminaries & Generals Course Registration Training (November 10-12, 2025)</strong> has been successfully received.</p>

  <div class="details">
    <p><strong>Training Venue:</strong> Sibane Sami Hotel</p>
    <p><strong>Time:</strong> 08:30 AM – 2:00 PM</p>
  
    <p><strong>Reminder:</strong> Please bring a calculator for the training.</p>
  </div>

  <a href="index.php" class="btn-home">Back to Registration</a>
</div>

</body>
</html>
