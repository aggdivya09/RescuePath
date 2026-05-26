<?php
// ── Session guard — only logged in users can access ───────────
ini_set('session.use_cookies', 1);
ini_set('session.use_only_cookies', 1);
session_start();

if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    session_write_close();
    header('Location: login.php');
    exit;
}
$user_name = htmlspecialchars($_SESSION['user_name'] ?? 'Operator');
$user_id   = htmlspecialchars($_SESSION['user_id']   ?? '');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Rescue Path — Emergency Route Finder</title>

    <!-- Leaflet CSS -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/leaflet/1.9.4/leaflet.min.css"/>

    <link href="https://fonts.googleapis.com/css2?family=Rajdhani:wght@500;600;700&family=DM+Sans:wght@300;400;500&display=swap" rel="stylesheet"/>

    <style>
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

        :root {
            --bg:      #ffffff;
            --surface: #f3f7fd;
            --border:  #30363d;
            --red:     #ff4444;
            --orange:  #ff8800;
            --green:   #22c55e;
            --blue:    #3b82f6;
            --text:    #090909;
            --muted:   #000000;
        }

        html, body {
            height: 100%;
            overflow: hidden;
        }
        body {
            font-family: 'DM Sans', sans-serif;
            background: var(--bg);
            color: var(--text);
            height: 100vh;
            overflow: hidden;
            display: flex;
            flex-direction: column;
        }

        /* ── Top Header ── */
        .header {
            background: var(--surface);
            border-bottom: 1px solid var(--border);
            padding: 12px 24px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            z-index: 200;
            flex-shrink: 0;
        }
        .header-left { display: flex; align-items: center; gap: 12px; }
        .brand-icon {
            width: 36px; height: 36px; border-radius: 9px;
            background: linear-gradient(135deg, var(--red), var(--orange));
            display: flex; align-items: center; justify-content: center; font-size: 17px;
        }
        .brand-name {
            font-family: 'Rajdhani', sans-serif;
            font-size: 20px; font-weight: 700; letter-spacing: 0.5px;
        }
        .brand-sub { font-size: 12px; color: var(--muted); }

        .header-right { display: flex; align-items: center; gap: 12px; }
        .user-pill {
            background: var(--bg); border: 1px solid var(--border);
            border-radius: 20px; padding: 6px 14px;
            font-size: 13px; color: var(--muted);
        }
        .user-pill strong { color: var(--text); }
        .back-btn {
            background: none; border: 1px solid var(--border);
            color: var(--muted); padding: 7px 14px; border-radius: 8px;
            font-family: 'DM Sans', sans-serif; font-size: 13px;
            cursor: pointer; text-decoration: none;
            transition: border-color 0.2s, color 0.2s;
            display: inline-block;
        }
        .back-btn:hover { border-color: var(--red); color: var(--red); }

        /* ── Main Layout ── */
        .container {
            display: flex;
            flex: 1;
            overflow: hidden;
            min-height: 0;
        }

        /* ── Map ── */
        #map {
            flex: 1;
            min-height: 0;
            height: 100%;
            z-index: 1;
        }

        /* ── Sidebar ── */
        .sidebar {
            width: 300px;
            background: var(--surface);
            border-left: 1px solid var(--border);
            overflow-y: auto;
            padding: 16px;
            z-index: 10;
            flex-shrink: 0;
        }

        .panel {
            margin-bottom: 18px;
            padding-bottom: 16px;
            border-bottom: 1px solid var(--border);
        }
        .panel:last-child { border-bottom: none; }

        .panel h3 {
            font-family: 'Rajdhani', sans-serif;
            font-size: 13px; font-weight: 700;
            letter-spacing: 0.08em; text-transform: uppercase;
            color: var(--muted); margin-bottom: 12px;
            display: flex; align-items: center; gap: 7px;
        }

        .info-text {
            color: var(--muted); font-size: 13px; line-height: 1.7;
        }
        .info-text strong { color: var(--text); }

        /* Hospital list */
        .hospital-list { list-style: none; }
        .hospital-item {
            padding: 10px 12px; margin-bottom: 8px;
            background: var(--bg); border: 1px solid var(--border);
            border-radius: 10px; border-left: 3px solid var(--red);
            font-size: 13px; cursor: pointer;
            transition: border-color 0.2s, background 0.2s, transform 0.15s;
        }
        .hospital-item:hover {
            background: rgba(255,68,68,0.05);
            border-color: var(--red);
            transform: translateX(3px);
        }
        .hospital-name { font-weight: 600; color: var(--text); margin-bottom: 4px; }
        .hospital-meta { color: var(--muted); font-size: 12px; }

        /* Buttons */
        .btn-primary {
            width: 100%; padding: 11px;
            background: linear-gradient(135deg, var(--red), var(--orange));
            color: #fff; border: none; border-radius: 9px;
            font-family: 'Rajdhani', sans-serif; font-size: 14px; font-weight: 700;
            letter-spacing: 0.5px; cursor: pointer;
            box-shadow: 0 4px 16px rgba(255,68,68,0.25);
            transition: opacity 0.2s, transform 0.15s;
        }
        .btn-primary:hover { opacity: 0.88; transform: translateY(-1px); }

        .btn-secondary {
            width: 100%; padding: 9px;
            background: none; border: 1px solid var(--border);
            color: var(--muted); border-radius: 9px;
            font-family: 'DM Sans', sans-serif; font-size: 13px;
            cursor: pointer; margin-top: 8px;
            transition: border-color 0.2s, color 0.2s;
        }
        .btn-secondary:hover { border-color: var(--muted); color: var(--text); }

        /* Stats */
        .stats-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 8px; }
        .stat-box {
            background: var(--bg); border: 1px solid var(--border);
            border-radius: 8px; padding: 10px;
            text-align: center;
        }
        .stat-val {
            font-family: 'Rajdhani', sans-serif;
            font-size: 24px; font-weight: 700; color: var(--red);
        }
        .stat-lbl { font-size: 10px; color: var(--muted); text-transform: uppercase; letter-spacing: 0.06em; }

        /* Leaflet popup light theme */
        .leaflet-popup-content-wrapper {
            background: var(--surface) !important;
            color: var(--text) !important;
            border: 1px solid var(--border) !important;
            border-radius: 10px !important;
            box-shadow: 0 8px 30px rgba(0,0,0,0.15) !important;
        }
        .leaflet-popup-tip { background: var(--surface) !important; }
        .leaflet-popup-content { font-family: 'DM Sans', sans-serif !important; }

        /* Scrollbar */
        .sidebar::-webkit-scrollbar { width: 5px; }
        .sidebar::-webkit-scrollbar-track { background: var(--surface); }
        .sidebar::-webkit-scrollbar-thumb { background: #c0c8d0; border-radius: 3px; }

        /* Route info box */
        #routeInfo {
            position: absolute;
            bottom: 24px; left: 24px;
            background: var(--surface);
            border: 1px solid var(--border);
            padding: 16px; border-radius: 12px;
            box-shadow: 0 8px 30px rgba(0,0,0,0.12);
            font-family: 'DM Sans', sans-serif;
            z-index: 1000; width: 260px;
            color: var(--text);
        }
        #routeInfo h3 {
            font-family: 'Rajdhani', sans-serif;
            color: var(--red); font-size: 16px;
            margin-bottom: 10px;
        }
        #routeInfo p { font-size: 13px; margin: 6px 0; color: var(--muted); }
        #routeInfo p strong { color: var(--text); }
        #routeInfo span.val { color: var(--red); font-weight: 700; }

        @keyframes pulse {
            0%, 100% { box-shadow: 0 4px 12px rgba(76,175,80,0.5); }
            50%       { box-shadow: 0 4px 24px rgba(76,175,80,0.9); }
        }

        @media (max-width: 768px) {
            .container { flex-direction: column; }
            .sidebar { width: 100%; max-height: 38vh; border-left: none; border-top: 1px solid var(--border); }
        }
    </style>
</head>
<body>

    <!-- ── Header ── -->
    <div class="header">
        <div class="header-left">
            <div class="brand-icon">&#128680;</div>
            <div>
                <div class="brand-name">RESCUE PATH</div>
                <div class="brand-sub">Emergency Route Finder &mdash; Dehradun</div>
            </div>
        </div>
        <div class="header-right">
            <div class="user-pill">&#128100; <strong><?php echo $user_name; ?></strong></div>
            <a href="dashboard.php" class="back-btn">&#8592; Dashboard</a>
            <form action="logout.php" method="POST" style="margin:0;">
                <button type="submit" class="back-btn" style="border-color:var(--red);color:var(--red);">Sign out</button>
            </form>
        </div>
    </div>

    <!-- ── Main ── -->
    <div class="container">

        <!-- Map -->
        <div id="map"></div>

        <!-- Sidebar -->
        <div class="sidebar">

            <div class="panel">
                <h3>&#128205; Instructions</h3>
                <p class="info-text">
                    <strong>1.</strong> Click anywhere on map to set accident location<br>
                    <strong>2.</strong> Click <strong>Find Nearest Hospital</strong><br>
                    <strong>3.</strong> Or pick a hospital from the list below
                </p>
            </div>

            <div class="panel">
                <h3>&#128680; Accident Location</h3>
                <p class="info-text" id="currentLocation">Click on the map to set location</p>
            </div>

            <div class="panel">
                <h3>&#127973; Route</h3>
                <button class="btn-primary" onclick="findNearestHospital()">
                    &#127973; Find Nearest Hospital
                </button>
                <button class="btn-secondary" onclick="resetMap()">
                    &#10005; Clear Route
                </button>
            </div>

            <div class="panel">
                <h3>&#127973; All Hospitals</h3>
                <ul class="hospital-list" id="hospitalsList"></ul>
            </div>

            <div class="panel">
                <h3>&#128202; Statistics</h3>
                <div class="stats-grid">
                    <div class="stat-box">
                        <div class="stat-val" id="totalHospitals">0</div>
                        <div class="stat-lbl">Hospitals</div>
                    </div>
                    <div class="stat-box">
                        <div class="stat-val" id="emergencyCount">0</div>
                        <div class="stat-lbl">24/7 Emergency</div>
                    </div>
                    <div class="stat-box" style="grid-column:span 2;">
                        <div class="stat-val" id="totalBeds">0</div>
                        <div class="stat-lbl">Total Beds</div>
                    </div>
                </div>
            </div>

        </div>
    </div>

    <!-- Leaflet JS -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/leaflet/1.9.4/leaflet.min.js"></script>
    <!-- Map data & logic -->
    <script src="map.js"></script>

    <script>
        // ── Sidebar init ──────────────────────────────────────
        function initializeSidebar() {
            const list = document.getElementById('hospitalsList');
            let emergencyCount = 0;
            let totalBeds = 0;

            hospitals.forEach(function(hospital) {
                const li = document.createElement('li');
                li.className = 'hospital-item';
                li.onclick = function() { selectHospital(hospital.id); };

                if (hospital.emergency) emergencyCount++;
                totalBeds += hospital.beds;

                li.innerHTML =
                    '<div class="hospital-name">' + hospital.name + '</div>' +
                    '<div class="hospital-meta">&#128716; ' + hospital.beds + ' beds &bull; ' +
                    (hospital.emergency ? '&#9989; 24/7 Emergency' : '&#9888; Limited hours') +
                    '</div>';

                list.appendChild(li);
            });

            document.getElementById('totalHospitals').textContent = hospitals.length;
            document.getElementById('emergencyCount').textContent = emergencyCount;
            document.getElementById('totalBeds').textContent      = totalBeds;
        }

        // ── Update location display ───────────────────────────
        function updateLocationDisplay() {
            if (typeof sourceCoords !== 'undefined' && sourceCoords) {
                document.getElementById('currentLocation').innerHTML =
                    '<strong>Lat:</strong> ' + sourceCoords.lat.toFixed(5) + '&deg;<br>' +
                    '<strong>Lng:</strong> ' + sourceCoords.lng.toFixed(5) + '&deg;';
            }
        }

        // Hook into map click to update sidebar
        map.on('click', function() {
            setTimeout(updateLocationDisplay, 100);
        });

        // Force Leaflet to recalculate map size after page fully loads
        window.addEventListener('load', function() {
            setTimeout(function() { map.invalidateSize(); }, 200);
            initializeSidebar();
        });
    </script>

</body>
</html>