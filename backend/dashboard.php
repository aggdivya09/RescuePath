<?php
ini_set('session.use_cookies', 1);
ini_set('session.use_only_cookies', 1);
session_start();

if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    session_write_close();
    header('Location: login.php');
    exit;
}

define('DB_HOST',    'localhost');
define('DB_NAME',    'rescue_path');
define('DB_USER',    'root');
define('DB_PASS',    '');
define('DB_CHARSET', 'utf8mb4');

$user_id   = $_SESSION['user_id']   ?? '';
$user_name = htmlspecialchars($_SESSION['user_name'] ?? 'Operator');

$update_success = '';
$update_error   = '';
$user_number    = '';

// Connect to DB
try {
    $pdo = new PDO(
        "mysql:host=".DB_HOST.";dbname=".DB_NAME.";charset=".DB_CHARSET,
        DB_USER, DB_PASS,
        [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ]
    );
} catch (PDOException $e) {
    $pdo = null;
}

// Handle phone number update POST
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_number'])) {
    $new_number = trim($_POST['new_number'] ?? '');
    if ($new_number === '' || !ctype_digit($new_number) || strlen($new_number) < 10 || strlen($new_number) > 15) {
        $update_error = 'Please enter a valid phone number (10–15 digits).';
    } elseif ($pdo) {
        $check = $pdo->prepare('SELECT Id FROM user WHERE Number = ? AND Id != ? LIMIT 1');
        $check->execute([$new_number, $user_id]);
        if ($check->fetch()) {
            $update_error = 'This number is already registered to another account.';
        } else {
            $upd = $pdo->prepare('UPDATE user SET Number = ? WHERE Id = ?');
            $upd->execute([$new_number, $user_id]);
            $update_success = 'Phone number updated successfully!';
        }
    } else {
        $update_error = 'Database connection failed.';
    }
}

// Fetch current user details from DB
if ($pdo) {
    $stmt = $pdo->prepare('SELECT Name, Number FROM user WHERE Id = ? LIMIT 1');
    $stmt->execute([$user_id]);
    $row = $stmt->fetch();
    if ($row) {
        $user_name   = htmlspecialchars($row['Name']);
        $user_number = htmlspecialchars($row['Number']);
    }
}

$user_id_display = htmlspecialchars($user_id);
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8"/>
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Rescue Path — Dashboard</title>
  <link href="https://fonts.googleapis.com/css2?family=Rajdhani:wght@500;600;700&family=DM+Sans:wght@300;400;500&display=swap" rel="stylesheet"/>
  <style>
    *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

    :root {
      --bg:      #ffffff;
      --surface: #f3f7fd;
      --surface2:#1c2128;
      --border:  #30363d;
      --red:     #ff4444;
      --orange:  #ff8800;
      --green:   #22c55e;
      --blue:    #3b82f6;
      --text:    #090909;
      --muted:   #000000;
    }

    body { min-height: 100vh; background: var(--bg); font-family: 'DM Sans', sans-serif; color: var(--text); }

    /* ── Sidebar ── */
    .sidebar {
      position: fixed; top: 0; left: 0;
      width: 240px; height: 100vh;
      background: var(--surface);
      border-right: 1px solid var(--border);
      display: flex; flex-direction: column;
      padding: 24px 18px; z-index: 100;
    }
    .brand { display: flex; align-items: center; gap: 10px; margin-bottom: 36px; }
    .brand-icon {
      width: 36px; height: 36px; border-radius: 9px;
      background: linear-gradient(135deg, var(--red), var(--orange));
      display: flex; align-items: center; justify-content: center; font-size: 17px;
    }
    .brand-name { font-family: 'Rajdhani', sans-serif; font-size: 18px; font-weight: 700; letter-spacing: 0.5px; }

    nav { flex: 1; }
    .nav-label { font-size: 10px; letter-spacing: 0.1em; text-transform: uppercase; color: var(--muted); padding: 0 10px; margin: 20px 0 6px; }
    .nav-item {
      display: flex; align-items: center; gap: 11px;
      padding: 10px 12px; border-radius: 9px;
      font-size: 14px; color: var(--muted);
      text-decoration: none; cursor: pointer;
      transition: background 0.15s, color 0.15s;
      border: none; background: none; width: 100%; text-align: left;
    }
    .nav-item:hover { background: rgb(235,226,226); color: var(--red); }
    .nav-item.active { background: rgb(235, 226, 226); color: var(--red); font-weight: 500; }
    .nav-icon { font-size: 15px; width: 18px; text-align: center; }

    .sidebar-footer { border-top: 1px solid var(--border); padding-top: 18px; }
    .user-chip {
      display: flex; align-items: center; gap: 10px;
      padding: 10px 12px; border-radius: 9px;
      margin-bottom: 10px;
    }
    .avatar {
      width: 30px; height: 30px; border-radius: 50%;
      background: linear-gradient(135deg, var(--red), var(--orange));
      display: flex; align-items: center; justify-content: center;
      font-family: 'Rajdhani', sans-serif; font-size: 13px; font-weight: 700;
      color: #fff;
    }
    .user-name { font-size: 13px; font-weight: 500; }
    .user-role { font-size: 11px; color: var(--muted); }

    /* ── Main ── */
    .main { margin-left: 240px; padding: 36px 40px; animation: fadeIn 0.4s ease both; }
    @keyframes fadeIn { from { opacity:0; transform:translateY(8px); } to { opacity:1; transform:none; } }

    .topbar { display: flex; align-items: center; justify-content: space-between; margin-bottom: 32px; }
    .page-title { font-family: 'Rajdhani', sans-serif; font-size: 28px; font-weight: 700; letter-spacing: 0.5px; }
    .welcome-badge {
      font-size: 13px; color: var(--muted);
      background: var(--surface); border: 1px solid var(--border);
      padding: 7px 16px; border-radius: 20px;
    }
    .welcome-badge strong { color: var(--text); }

    /* Stats */
    .stats { display: grid; grid-template-columns: repeat(4,1fr); gap: 16px; margin-bottom: 24px; }
    .stat-card {
      background: var(--surface); border: 1px solid var(--border);
      border-radius: 14px; padding: 20px 18px;
      transition: border-color 0.2s;
    }
    .stat-card:hover { border-color: var(--red); }
    .stat-label { font-size: 10px; letter-spacing: 0.1em; text-transform: uppercase; color: var(--muted); margin-bottom: 8px; }
    .stat-value { font-family: 'Rajdhani', sans-serif; font-size: 32px; font-weight: 700; line-height: 1; }
    .stat-delta { font-size: 12px; color: var(--green); margin-top: 5px; }

    /* Map CTA */
    .map-cta {
      background: var(--surface); border: 1px solid var(--border);
      border-radius: 14px; padding: 28px 32px;
      display: flex; align-items: center; justify-content: space-between;
      margin-bottom: 24px;
      background-image: linear-gradient(135deg, rgb(249, 244, 244), rgba(255, 248, 241, 0.03));
    }
    .map-cta-text h2 { font-family: 'Rajdhani', sans-serif; font-size: 22px; font-weight: 700; margin-bottom: 4px; }
    .map-cta-text p  { font-size: 14px; color: var(--muted); }
    .map-btn {
      padding: 12px 28px;
      background: linear-gradient(135deg, var(--red), var(--orange));
      color: #fff; border: none; border-radius: 10px;
      font-family: 'Rajdhani', sans-serif; font-size: 15px; font-weight: 700;
      letter-spacing: 0.5px; cursor: pointer; text-decoration: none;
      box-shadow: 0 4px 20px rgb(228, 228, 228);
      transition: opacity 0.2s, transform 0.15s;
      white-space: nowrap;
    }
    .map-btn:hover { opacity: 0.88; transform: translateY(-1px); }

    /* Panels */
    .panels { display: grid; grid-template-columns: 2fr 1fr; gap: 16px; }
    .panel { background: var(--surface); border: 1px solid var(--border); border-radius: 14px; padding: 22px; }
    .panel-title { font-family: 'Rajdhani', sans-serif; font-size: 16px; font-weight: 700; margin-bottom: 18px; letter-spacing: 0.3px; }

    table { width: 100%; border-collapse: collapse; font-size: 13px; }
    th { text-align: left; color: var(--muted); font-weight: 500; font-size: 10px; letter-spacing: 0.1em; text-transform: uppercase; padding-bottom: 10px; border-bottom: 1px solid var(--border); }
    td { padding: 11px 0; border-bottom: 1px solid var(--border); vertical-align: middle; }
    tr:last-child td { border-bottom: none; }
    .badge { display: inline-block; font-size: 10px; font-weight: 600; padding: 3px 9px; border-radius: 20px; }
    .badge.green  { background: rgba(34,197,94,0.12);  color: #16a34a; }
    .badge.yellow { background: rgba(234,179,8,0.12);  color: #ca8a04; }
    .badge.red    { background: rgba(255,68,68,0.12);  color: #dc2626; }

    .activity-item { display: flex; align-items: flex-start; gap: 11px; padding: 11px 0; border-bottom: 1px solid var(--border); }
    .activity-item:last-child { border-bottom: none; }
    .activity-dot { width: 8px; height: 8px; border-radius: 50%; margin-top: 5px; flex-shrink: 0; }
    .activity-text { font-size: 13px; line-height: 1.5; }
    .activity-time { font-size: 11px; color: var(--muted); }

    /* Logout */
    .logout-form { margin: 0; }
    .logout-btn {
      width: 100%; padding: 9px; background: none;
      border: 1px solid var(--border); color: var(--muted);
      border-radius: 8px; font-family: 'DM Sans', sans-serif;
      font-size: 13px; cursor: pointer;
      transition: border-color 0.2s, color 0.2s;
    }
    .logout-btn:hover { border-color: var(--red); color: var(--red); }

    /* ── Settings Modal ── */
    .modal-overlay {
      display: none; position: fixed; inset: 0;
      background: rgba(0,0,0,0.4); z-index: 500;
      align-items: center; justify-content: center;
      animation: fadeOverlay 0.2s ease;
    }
    .modal-overlay.open { display: flex; }
    @keyframes fadeOverlay { from { opacity:0; } to { opacity:1; } }

    .modal {
      background: var(--bg); border: 1px solid var(--border);
      border-radius: 20px; padding: 36px 36px 30px;
      width: 100%; max-width: 460px;
      box-shadow: 0 24px 60px rgba(0,0,0,0.15);
      animation: slideUp 0.25s cubic-bezier(.22,1,.36,1);
      position: relative;
    }
    @keyframes slideUp { from { opacity:0; transform:translateY(20px); } to { opacity:1; transform:none; } }

    .modal-close {
      position: absolute; top: 16px; right: 18px;
      background: none; border: none; font-size: 20px;
      cursor: pointer; color: var(--muted); line-height: 1;
      transition: color 0.15s;
    }
    .modal-close:hover { color: var(--red); }

    .modal-header { display: flex; align-items: center; gap: 13px; margin-bottom: 28px; }
    .modal-icon {
      width: 44px; height: 44px; border-radius: 11px;
      background: linear-gradient(135deg, var(--red), var(--orange));
      display: flex; align-items: center; justify-content: center; font-size: 20px;
    }
    .modal-title { font-family: 'Rajdhani', sans-serif; font-size: 22px; font-weight: 700; }
    .modal-sub { font-size: 12px; color: var(--muted); }

    /* Info rows */
    .info-row {
      display: flex; align-items: center; justify-content: space-between;
      padding: 13px 16px; background: var(--surface);
      border: 1px solid var(--border); border-radius: 10px;
      margin-bottom: 10px;
    }
    .info-row-label { font-size: 11px; text-transform: uppercase; letter-spacing: 0.08em; color: var(--muted); margin-bottom: 3px; }
    .info-row-value { font-size: 15px; font-weight: 500; color: var(--text); }

    .divider { border: none; border-top: 1px solid var(--border); margin: 22px 0; }

    .section-title {
      font-family: 'Rajdhani', sans-serif; font-size: 14px; font-weight: 700;
      letter-spacing: 0.05em; text-transform: uppercase;
      color: var(--muted); margin-bottom: 14px;
    }

    .modal label {
      display: block; font-size: 11px; font-weight: 500;
      letter-spacing: 0.1em; text-transform: uppercase;
      color: var(--muted); margin-bottom: 7px;
    }
    .modal input[type="tel"] {
      width: 100%; background: var(--surface);
      border: 1px solid var(--border); border-radius: 10px;
      color: var(--text); font-family: 'DM Sans', sans-serif;
      font-size: 15px; padding: 12px 15px; outline: none;
      transition: border-color 0.2s, box-shadow 0.2s;
      margin-bottom: 14px;
    }
    .modal input[type="tel"]:focus {
      border-color: var(--red);
      box-shadow: 0 0 0 3px rgba(255,68,68,0.1);
    }

    .modal-alert {
      border-radius: 9px; font-size: 13px;
      padding: 10px 14px; margin-bottom: 14px;
    }
    .modal-alert.error   { background: rgba(255,68,68,0.07); border: 1px solid rgba(255,68,68,0.25); color: #dc2626; }
    .modal-alert.success { background: rgba(34,197,94,0.07); border: 1px solid rgba(34,197,94,0.25); color: #16a34a; }

    .modal-btn {
      width: 100%; padding: 13px;
      background: linear-gradient(135deg, var(--red), var(--orange));
      color: #fff; border: none; border-radius: 10px;
      font-family: 'Rajdhani', sans-serif; font-size: 15px; font-weight: 700;
      letter-spacing: 0.5px; cursor: pointer;
      box-shadow: 0 4px 16px rgba(255,68,68,0.25);
      transition: opacity 0.2s, transform 0.15s;
    }
    .modal-btn:hover { opacity: 0.88; transform: translateY(-1px); }
  </style>
</head>
<body>

  <!-- ── Settings Modal ── -->
  <div class="modal-overlay" id="settingsModal">
    <div class="modal">
      <button class="modal-close" onclick="closeSettings()">&#10005;</button>

      <div class="modal-header">
        <div class="modal-icon">⚙️</div>
        <div>
          <div class="modal-title">Account Settings</div>
          <div class="modal-sub">View your details &amp; update your phone number</div>
        </div>
      </div>

      <!-- User Info Display -->
      <div class="info-row">
        <div>
          <div class="info-row-label">Full Name</div>
          <div class="info-row-value"><?php echo $user_name; ?></div>
        </div>
        <span>👤</span>
      </div>
      <div class="info-row">
        <div>
          <div class="info-row-label">User ID</div>
          <div class="info-row-value">#<?php echo $user_id_display; ?></div>
        </div>
        <span>🪪</span>
      </div>
      <div class="info-row">
        <div>
          <div class="info-row-label">Current Phone Number</div>
          <div class="info-row-value" id="currentNumberDisplay"><?php echo $user_number ?: 'Not available'; ?></div>
        </div>
        <span>📱</span>
      </div>

      <hr class="divider"/>

      <!-- Update Phone Number -->
      <div class="section-title">📱 Update Phone Number</div>

      <?php if ($update_error !== ''): ?>
        <div class="modal-alert error">⚠️ <?php echo htmlspecialchars($update_error); ?></div>
      <?php endif; ?>
      <?php if ($update_success !== ''): ?>
        <div class="modal-alert success">✓ <?php echo htmlspecialchars($update_success); ?></div>
      <?php endif; ?>

      <form method="POST" action="dashboard.php">
        <label for="new_number">New Phone Number</label>
        <input
          type="tel"
          id="new_number"
          name="new_number"
          placeholder="Enter new number (10–15 digits)"
          maxlength="15"
          value="<?php echo ($update_error !== '') ? htmlspecialchars($_POST['new_number'] ?? '') : ''; ?>"
        />
        <input type="hidden" name="update_number" value="1"/>
        <button type="submit" class="modal-btn">SAVE CHANGES →</button>
      </form>

    </div>
  </div>

  <!-- ── Sidebar ── -->
  <aside class="sidebar">
    <div class="brand">
      <div class="brand-icon">🚨</div>
      <span class="brand-name">RESCUE PATH</span>
    </div>

    <nav>
      <div class="nav-label">Main</div>
      <a class="nav-item active" href="dashboard.php">
        <span class="nav-icon">▦</span> Dashboard
      </a>
      <a class="nav-item" href="map.php">
        <span class="nav-icon">🗺️</span> Evacuation Map
      </a>
      <div class="nav-label">System</div>
      <button class="nav-item" onclick="openSettings()">
        <span class="nav-icon">⚙️</span> Settings
      </button>
    </nav>

    <div class="sidebar-footer">
      <div class="user-chip">
        <div class="avatar"><?php echo strtoupper(substr($user_name, 0, 1)); ?></div>
        <div>
          <div class="user-name"><?php echo $user_name; ?></div>
          <div class="user-role">ID: <?php echo $user_id_display; ?></div>
        </div>
      </div>
      <form class="logout-form" action="logout.php" method="POST">
        <button type="submit" class="logout-btn">Sign out →</button>
      </form>
    </div>
  </aside>

  <!-- ── Main ── -->
  <main class="main">
    <div class="topbar">
      <h1 class="page-title">Dashboard</h1>
      <div class="welcome-badge">Welcome back, <strong><?php echo $user_name; ?></strong> &nbsp;·&nbsp; ID: <?php echo $user_id_display; ?></div>
    </div>

    <!-- Stats -->
    <div class="stats">
      <div class="stat-card">
        <div class="stat-label">Active Routes</div>
        <div class="stat-value">12</div>
        <div class="stat-delta">↑ 3 new today</div>
      </div>
      <div class="stat-card">
        <div class="stat-label">Hospitals Mapped</div>
        <div class="stat-value">24</div>
        <div class="stat-delta">Dehradun region</div>
      </div>
      <div class="stat-card">
        <div class="stat-label">Safe Zones</div>
        <div class="stat-value">11</div>
        <div class="stat-delta">Schools & shelters</div>
      </div>
      <div class="stat-card">
        <div class="stat-label">System Status</div>
        <div class="stat-value" style="color:var(--green);font-size:22px">● Online</div>
        <div class="stat-delta">All systems normal</div>
      </div>
    </div>

    <!-- Map CTA -->
    <div class="map-cta">
      <div class="map-cta-text">
        <h2>🗺️ Open Evacuation Map</h2>
        <p>View live rescue routes, hospitals, and safe zones on the interactive Dehradun map.</p>
      </div>
      <a href="map.php" class="map-btn">OPEN MAP →</a>
    </div>

    <!-- Panels -->
    <div class="panels">
      <div class="panel">
        <div class="panel-title">Recent Evacuations</div>
        <table>
          <thead>
            <tr><th>Route ID</th><th>Zone</th><th>People</th><th>Status</th></tr>
          </thead>
          <tbody>
            <tr><td>#R-041</td><td>Paltan Bazar</td><td>320</td><td><span class="badge green">Completed</span></td></tr>
            <tr><td>#R-040</td><td>Haridwar Road</td><td>185</td><td><span class="badge yellow">In Progress</span></td></tr>
            <tr><td>#R-039</td><td>Ring Road</td><td>540</td><td><span class="badge green">Completed</span></td></tr>
            <tr><td>#R-038</td><td>Raipur Road</td><td>90</td><td><span class="badge red">Delayed</span></td></tr>
            <tr><td>#R-037</td><td>Gandhi Road</td><td>210</td><td><span class="badge green">Completed</span></td></tr>
          </tbody>
        </table>
      </div>

      <div class="panel">
        <div class="panel-title">System Activity</div>
        <div class="activity-item">
          <div class="activity-dot" style="background:var(--green)"></div>
          <div>
            <div class="activity-text">Map data loaded — Dehradun OSM</div>
            <div class="activity-time">Just now</div>
          </div>
        </div>
        <div class="activity-item">
          <div class="activity-dot" style="background:var(--red)"></div>
          <div>
            <div class="activity-text">Operator <strong><?php echo $user_name; ?></strong> logged in</div>
            <div class="activity-time">Just now</div>
          </div>
        </div>
        <div class="activity-item">
          <div class="activity-dot" style="background:var(--orange)"></div>
          <div>
            <div class="activity-text">Route #R-040 updated</div>
            <div class="activity-time">18 min ago</div>
          </div>
        </div>
        <div class="activity-item">
          <div class="activity-dot" style="background:var(--blue)"></div>
          <div>
            <div class="activity-text">24 hospitals verified on map</div>
            <div class="activity-time">1 hour ago</div>
          </div>
        </div>
      </div>
    </div>
  </main>

  <script>
    function openSettings() {
      document.getElementById('settingsModal').classList.add('open');
    }
    function closeSettings() {
      document.getElementById('settingsModal').classList.remove('open');
    }
    // Close modal on overlay click
    document.getElementById('settingsModal').addEventListener('click', function(e) {
      if (e.target === this) closeSettings();
    });

    // Auto-open modal if there was a form submission (success or error)
    <?php if ($update_success !== '' || $update_error !== ''): ?>
    window.addEventListener('DOMContentLoaded', function() { openSettings(); });
    <?php endif; ?>
  </script>

</body>
</html>