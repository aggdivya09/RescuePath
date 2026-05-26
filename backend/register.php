<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// ── Database config ───────────────────────────────────────────
define('DB_HOST',    'localhost');
define('DB_NAME',    'rescue_path');
define('DB_USER',    'root');
define('DB_PASS',    '');
define('DB_CHARSET', 'utf8mb4');

$success      = '';
$error        = '';
$field_errors = [];     // per-field error messages
$old          = [];     // keep form values on error
$assigned_id  = null;   // will be set after successful INSERT

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $name     = trim($_POST['name']     ?? '');
    $password = trim($_POST['password'] ?? '');
    $confirm  = trim($_POST['confirm']  ?? '');
    $number   = trim($_POST['number']   ?? '');

    // Keep values so the form stays filled on error
    $old = compact('name', 'number');

    // ── Field validation ──────────────────────────────────────
    if ($name === '') {
        $field_errors['name'] = 'Name is required.';
    } elseif (strlen($name) < 2) {
        $field_errors['name'] = 'Name must be at least 2 characters.';
    }

    if ($number === '') {
        $field_errors['number'] = 'Phone number is required.';
    } elseif (!ctype_digit($number)) {
        $field_errors['number'] = 'Phone number must contain digits only.';
    } elseif (strlen($number) < 10 || strlen($number) > 15) {
        $field_errors['number'] = 'Enter a valid phone number (10–15 digits).';
    }

    if ($password === '') {
        $field_errors['password'] = 'Password is required.';
    } elseif (strlen($password) < 4) {
        $field_errors['password'] = 'Password must be at least 4 characters.';
    }

    if ($confirm === '') {
        $field_errors['confirm'] = 'Please confirm your password.';
    } elseif ($password !== $confirm) {
        $field_errors['confirm'] = 'Passwords do not match. Try again.';
    }

    // ── Only hit DB if all fields pass ────────────────────────
    if (empty($field_errors)) {

        // Connect
        try {
            $pdo = new PDO(
                "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET,
                DB_USER, DB_PASS,
                [
                    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_EMULATE_PREPARES   => false,
                ]
            );
        } catch (PDOException $e) {
            $error = 'Cannot connect to database. Make sure XAMPP MySQL is running.';
        }

        if ($error === '') {
            // Check if phone number already registered
            try {
                $check = $pdo->prepare('SELECT Id FROM user WHERE Number = ? LIMIT 1');
                $check->execute([$number]);
                if ($check->fetch()) {
                    $field_errors['number'] = 'This phone number is already registered. Please use a different number.';
                }
            } catch (PDOException $e) {
                $error = 'Error checking details: ' . $e->getMessage();
            }
        }

        if ($error === '' && empty($field_errors)) {
            // Insert — Id is AUTO_INCREMENT so we skip it
            try {
                $insert = $pdo->prepare(
                    'INSERT INTO user (Name, Password, Number) VALUES (?, ?, ?)'
                );
                $insert->execute([$name, $password, $number]);
                $assigned_id = (int) $pdo->lastInsertId();  // cast to int, top-scope variable
                $success = 'Account created successfully!';
            } catch (PDOException $e) {
                $error = 'Could not create account: ' . $e->getMessage();
            }
        }
    }
}

// Helper: return field error HTML if exists
function field_error($key, $field_errors) {
    if (isset($field_errors[$key])) {
        echo '<div class="field-err">&#9888; ' . htmlspecialchars($field_errors[$key]) . '</div>';
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8"/>
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Rescue Path — Register</title>
  <link href="https://fonts.googleapis.com/css2?family=Rajdhani:wght@500;600;700&family=DM+Sans:wght@300;400;500&display=swap" rel="stylesheet"/>
  <style>
    *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

    :root {
      --bg:       #ffffff;
      --card:     #ffffff;
      --border:   #30363d;
      --accent:   #ff4444;
      --accent2:  #ff8800;
      --green:    #22c55e;
      --text:     #1c1d1e;
      --muted:    #7d8590;
      --input-bg: #fffcfc;
      --error:    #ff4f6d;
    }

    body {
      min-height: 100vh;
      background: var(--bg);
      display: flex; align-items: center; justify-content: center;
      font-family: 'DM Sans', sans-serif;
      color: var(--text);
      padding: 24px 0;
    }

    .bg-blobs { position: fixed; inset: 0; z-index: 0; pointer-events: none; }
    .blob {
      position: absolute; border-radius: 50%;
      filter: blur(90px); opacity: 0.12;
      animation: drift 14s ease-in-out infinite alternate;
    }
    .blob1 { width: 500px; height: 500px; background: var(--accent);  top: -150px; left: -150px; }
    .blob2 { width: 380px; height: 380px; background: var(--accent2); bottom: -100px; right: -80px; animation-delay: -5s; }
    .blob3 { width: 280px; height: 280px; background: #3b82f6; bottom: 25%; left: 35%; animation-delay: -9s; }

    @keyframes drift {
      from { transform: translate(0,0) scale(1); }
      to   { transform: translate(35px,25px) scale(1.06); }
    }

    .card {
      position: relative; z-index: 1;
      background: var(--card);
      border: 1px solid var(--border);
      border-radius: 20px;
      padding: 44px 44px 36px;
      width: 100%; max-width: 440px;
      box-shadow: 0 32px 80px rgb(9, 9, 9);
      animation: fadeUp 0.55s cubic-bezier(.22,1,.36,1) both;
    }

    @keyframes fadeUp {
      from { opacity: 0; transform: translateY(24px); }
      to   { opacity: 1; transform: translateY(0); }
    }

    .brand { display: flex; align-items: center; gap: 12px; margin-bottom: 32px; }
    .brand-icon {
      width: 40px; height: 40px; border-radius: 10px;
      background: linear-gradient(135deg, var(--accent), var(--accent2));
      display: flex; align-items: center; justify-content: center; font-size: 20px;
    }
    .brand-name { font-family: 'Rajdhani', sans-serif; font-size: 22px; font-weight: 700; letter-spacing: 1px; }
    .brand-tag  { font-size: 11px; color: var(--muted); }

    h1 { font-family: 'Rajdhani', sans-serif; font-size: 28px; font-weight: 700; margin-bottom: 4px; }
    .subtitle { color: var(--muted); font-size: 13px; margin-bottom: 28px; }

    /* Global alert */
    .alert {
      border-radius: 10px; font-size: 13px;
      padding: 11px 14px; margin-bottom: 20px;
    }
    .alert.error   { background: rgba(255,79,109,0.08); border: 1px solid rgba(255,79,109,0.3); color: var(--error); }
    .alert.success { background: rgba(34,197,94,0.08);  border: 1px solid rgba(34,197,94,0.3);  color: var(--green); font-size:14px; }

    /* Fields */
    .field { margin-bottom: 16px; }
    label {
      display: block; font-size: 11px; font-weight: 500;
      letter-spacing: 0.1em; text-transform: uppercase;
      color: var(--muted); margin-bottom: 6px;
    }
    input[type="text"],
    input[type="password"],
    input[type="tel"] {
      width: 100%;
      background: var(--input-bg);
      border: 1px solid var(--border);
      border-radius: 10px;
      color: var(--text);
      font-family: 'DM Sans', sans-serif;
      font-size: 15px; padding: 12px 15px;
      outline: none;
      transition: border-color 0.2s, box-shadow 0.2s;
    }
    input:focus {
      border-color: var(--accent);
      box-shadow: 0 0 0 3px rgba(255,68,68,0.12);
    }
    input.has-error { border-color: var(--error); }
    input::placeholder { color: var(--muted); }

    /* Per-field error */
    .field-err {
      font-size: 12px; color: var(--error);
      margin-top: 6px; padding: 6px 10px;
      background: rgba(255,79,109,0.06);
      border-radius: 6px; border-left: 3px solid var(--error);
    }

    /* Auto-ID badge */
    .auto-id-note {
      display: flex; align-items: center; gap: 8px;
      background: rgba(34,197,94,0.07);
      border: 1px solid rgba(34,197,94,0.2);
      border-radius: 10px; padding: 10px 14px;
      font-size: 12px; color: var(--green);
      margin-bottom: 20px;
    }

    /* Button */
    .btn {
      width: 100%; padding: 14px;
      background: linear-gradient(135deg, var(--accent), var(--accent2));
      color: #fff; border: none; border-radius: 10px;
      font-family: 'Rajdhani', sans-serif; font-size: 16px; font-weight: 700;
      letter-spacing: 1px; cursor: pointer; margin-top: 6px;
      box-shadow: 0 4px 20px rgba(255,68,68,0.3);
      transition: opacity 0.2s, transform 0.15s;
      display: block; text-align: center; text-decoration: none;
    }
    .btn:hover { opacity: 0.9; transform: translateY(-1px); }
    .btn:active { transform: translateY(0); }

    .login-link {
      text-align: center; font-size: 13px;
      color: var(--muted); margin-top: 20px;
    }
    .login-link a { color: var(--accent); text-decoration: none; font-weight: 500; }
    .login-link a:hover { text-decoration: underline; }
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
        <div class="brand-tag">Create New Account</div>
      </div>
    </div>

    <h1>Register</h1>
    <p class="subtitle">Fill in your details to create an account</p>

    <?php if ($error !== '') { ?>
      <div class="alert error">&#9888; <?php echo htmlspecialchars($error); ?></div>
    <?php } ?>

    <?php if ($success !== '') { ?>
      <div class="alert success">&#10003; <?php echo $success; ?></div>

      <a href="login.php" class="btn">GO TO LOGIN &#8594;</a>
      <p class="login-link" style="margin-top:16px;">
        <a href="register.php">Register another user</a>
      </p>

    <?php } else { ?>

      <!-- Auto ID note -->
      <div class="auto-id-note">
        &#10003; Your User ID will be assigned automatically after registration.
      </div>

      <form action="register.php" method="POST" novalidate>

        <!-- Name -->
        <div class="field">
          <label for="name">Full Name</label>
          <input
            type="text"
            id="name"
            name="name"
            placeholder="e.g. Rahul Sharma"
            value="<?php echo htmlspecialchars($old['name'] ?? ''); ?>"
            class="<?php echo isset($field_errors['name']) ? 'has-error' : ''; ?>"
            autocomplete="name"
          />
          <?php field_error('name', $field_errors); ?>
        </div>

        <!-- Phone Number -->
        <div class="field">
          <label for="number">Phone Number</label>
          <input
            type="tel"
            id="number"
            name="number"
            placeholder="e.g. 9876543210"
            value="<?php echo htmlspecialchars($old['number'] ?? ''); ?>"
            class="<?php echo isset($field_errors['number']) ? 'has-error' : ''; ?>"
            maxlength="15"
            autocomplete="tel"
          />
          <?php field_error('number', $field_errors); ?>
        </div>

        <!-- Password -->
        <div class="field">
          <label for="password">Password</label>
          <input
            type="password"
            id="password"
            name="password"
            placeholder="Create a password (min 4 chars)"
            class="<?php echo isset($field_errors['password']) ? 'has-error' : ''; ?>"
            autocomplete="new-password"
          />
          <?php field_error('password', $field_errors); ?>
        </div>

        <!-- Confirm Password -->
        <div class="field">
          <label for="confirm">Confirm Password</label>
          <input
            type="password"
            id="confirm"
            name="confirm"
            placeholder="Repeat your password"
            class="<?php echo isset($field_errors['confirm']) ? 'has-error' : ''; ?>"
            autocomplete="new-password"
          />
          <?php field_error('confirm', $field_errors); ?>
        </div>

        <button type="submit" class="btn">CREATE ACCOUNT &#8594;</button>

      </form>

    <?php } ?>

    <p class="login-link">Already have an account? <a href="login.php">Sign in here</a></p>

  </div>

</body>
</html>