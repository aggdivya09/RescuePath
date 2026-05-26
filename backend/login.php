<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);
$error = isset($_GET['error']) ? htmlspecialchars($_GET['error']) : '';
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8"/>
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Rescue Path — Login</title>
  <link href="https://fonts.googleapis.com/css2?family=Rajdhani:wght@500;600;700&family=DM+Sans:wght@300;400;500&display=swap" rel="stylesheet"/>
  <style>
    *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
    :root {
      --bg: #ffffff; --card: #ffffff; --border: #30363d;
      --accent: #ff4444; --accent2: #ff8800;
      --text: #1c1d1e; --muted: #7d8590;
      --input-bg: #fffcfc; --error: #ff4f6d;
    }
    body {
      min-height: 100vh; background: var(--bg);
      display: flex; align-items: center; justify-content: center;
      font-family: 'DM Sans', sans-serif; color: var(--text); overflow: hidden;
    }
    .bg-blobs { position: fixed; inset: 0; z-index: 0; pointer-events: none; }
    .blob {
      position: absolute; border-radius: 50%;
      filter: blur(90px); opacity: 0.12;
      animation: drift 14s ease-in-out infinite alternate;
    }
    .blob1 { width: 500px; height: 500px; background: var(--accent); top: -150px; left: -150px; }
    .blob2 { width: 380px; height: 380px; background: var(--accent2); bottom: -100px; right: -80px; animation-delay: -5s; }
    .blob3 { width: 280px; height: 280px; background: #3b82f6; bottom: 25%; left: 35%; animation-delay: -9s; }
    @keyframes drift {
      from { transform: translate(0,0) scale(1); }
      to   { transform: translate(35px,25px) scale(1.06); }
    }
    .card {
      position: relative; z-index: 1;
      background: var(--card); border: 1px solid var(--border);
      border-radius: 20px; padding: 48px 44px 40px;
      width: 100%; max-width: 420px;
      box-shadow: 0 32px 80px rgb(9, 9, 9);
      animation: fadeUp 0.55s cubic-bezier(.22,1,.36,1) both;
    }
    @keyframes fadeUp {
      from { opacity: 0; transform: translateY(24px); }
      to   { opacity: 1; transform: translateY(0); }
    }
    .brand { display: flex; align-items: center; gap: 12px; margin-bottom: 36px; }
    .brand-icon {
      width: 40px; height: 40px; border-radius: 10px;
      background: linear-gradient(135deg, var(--accent), var(--accent2));
      display: flex; align-items: center; justify-content: center; font-size: 20px;
    }
    .brand-name { font-family: 'Rajdhani', sans-serif; font-size: 22px; font-weight: 700; letter-spacing: 1px; }
    .brand-tag  { font-size: 11px; color: var(--muted); }
    h1 { font-family: 'Rajdhani', sans-serif; font-size: 30px; font-weight: 700; margin-bottom: 4px; }
    .subtitle { color: var(--muted); font-size: 14px; margin-bottom: 32px; }
    .alert {
      background: rgba(255,79,109,0.08); border: 1px solid rgba(255,79,109,0.3);
      border-radius: 10px; color: var(--error);
      font-size: 13px; padding: 11px 14px; margin-bottom: 20px;
    }
    .field { margin-bottom: 18px; }
    label {
      display: block; font-size: 11px; font-weight: 500;
      letter-spacing: 0.1em; text-transform: uppercase;
      color: var(--muted); margin-bottom: 7px;
    }
    input[type="tel"],
    input[type="password"] {
      width: 100%; background: var(--input-bg);
      border: 1px solid var(--border); border-radius: 10px;
      color: var(--text); font-family: 'DM Sans', sans-serif;
      font-size: 15px; padding: 13px 15px; outline: none;
      transition: border-color 0.2s, box-shadow 0.2s;
    }
    input:focus { border-color: var(--accent); box-shadow: 0 0 0 3px rgba(255,68,68,0.12); }
    input::placeholder { color: var(--muted); }
    .btn {
      width: 100%; padding: 14px;
      background: linear-gradient(135deg, var(--accent), var(--accent2));
      color: #fff; border: none; border-radius: 10px;
      font-family: 'Rajdhani', sans-serif; font-size: 16px; font-weight: 700;
      letter-spacing: 1px; cursor: pointer; margin-top: 8px;
      box-shadow: 0 4px 20px rgba(255,68,68,0.3);
      transition: opacity 0.2s, transform 0.15s;
    }
    .btn:hover { opacity: 0.9; transform: translateY(-1px); }
    .btn:active { transform: translateY(0); }
    .footer-note { text-align: center; font-size: 12px; color: var(--muted); margin-top: 24px; }
    .footer-note a { color: var(--accent); text-decoration: none; font-weight: 500; }
    .footer-note a:hover { text-decoration: underline; }
  </style>
</head>
<body>
  <div class="bg-blobs">
    <div class="blob blob1"></div>
    <div class="blob blob2"></div>
    <div class="blob blob3"></div>
  </div>

  <div class="card">
    <div class="brand">
      <div class="brand-icon">&#128680;</div>
      <div>
        <div class="brand-name">RESCUE PATH</div>
        <div class="brand-tag">Emergency Evacuation System</div>
      </div>
    </div>

    <h1>Sign In</h1>
    <p class="subtitle">Enter your mobile number and password to continue</p>

    <?php if ($error !== '') { ?>
      <div class="alert">&#9888; <?php echo $error; ?></div>
    <?php } ?>

    <!-- action uses relative path — works regardless of folder name -->
    <form action="submit.php" method="POST">

      <div class="field">
        <label for="number">Mobile Number</label>
        <input
          type="tel"
          id="number"
          name="number"
          placeholder="Enter your mobile number"
          required
          autocomplete="tel"
          maxlength="15"
        />
      </div>

      <div class="field">
        <label for="password">Password</label>
        <input
          type="password"
          id="password"
          name="password"
          placeholder="Enter your password"
          required
          autocomplete="current-password"
        />
      </div>

      <button type="submit" class="btn">ACCESS SYSTEM &#8594;</button>

    </form>

    <p class="footer-note">
      New user? <a href="register.php">Create an account</a>
      &nbsp;&middot;&nbsp; Rescue Path v1.0
    </p>
  </div>
</body>
</html>