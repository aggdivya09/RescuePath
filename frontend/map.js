
const map = L.map('map').setView([30.3165, 78.0322], 13);


L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
    attribution: '© OpenStreetMap'
}).addTo(map);


const hospitals = [
    { 
        id: 1,
        name: "ONGC Hospital", 
        coords: [30.3354, 78.0167],
        beds: 150,
        emergency: true
    },
    { 
        id: 2,
        name: "S K Memorial Hospital", 
        coords: [30.3288, 78.0524],
        beds: 120,
        emergency: true
    },
    { 
        id: 3,
        name: "Param Hospital", 
        coords: [30.3068, 78.0540],
        beds: 100,
        emergency: true
    },
    { 
        id: 4,
        name: "Doon Valley Hospital", 
        coords: [30.3120, 78.0480],
        beds: 180,
        emergency: true
    },
    { 
        id: 5,
        name: "General Hospital", 
        coords: [30.3193, 78.0503],
        beds: 200,
        emergency: true
    },
    { 
        id: 6,
        name: "Govind Hospital", 
        coords: [30.2828, 78.0685],
        beds: 90,
        emergency: false
    },
    {
        id: 7,
        name: "Himalayan Hospital",
        coords: [30.3250, 78.0400],
        beds: 160,
        emergency: true
    },
    {
        id: 8,
        name: "Apollo Clinic",
        coords: [30.3100, 78.0250],
        beds: 80,
        emergency: false
    }
];


function getHospitalIcon() {
    return L.divIcon({
        html: `
            <div style="
                background: #d32f2f;
                color: white;
                border-radius: 50%;
                width: 40px;
                height: 40px;
                display: flex;
                justify-content: center;
                align-items: center;
                font-size: 20px;
                box-shadow: 0 4px 8px rgba(0,0,0,0.3);
                border: 3px solid white;
                font-weight: bold;
            ">🏥</div>
        `,
        className: 'hospital-icon',
        iconSize: [40, 40],
        iconAnchor: [20, 20],
        popupAnchor: [0, -20]
    });
}

function getAccidentIcon() {
    return L.divIcon({
        html: `
            <div style="
                background: #4CAF50;
                color: white;
                border-radius: 50%;
                width: 45px;
                height: 45px;
                display: flex;
                justify-content: center;
                align-items: center;
                font-size: 24px;
                box-shadow: 0 4px 12px rgba(76, 175, 80, 0.5);
                border: 4px solid white;
                animation: pulse 2s infinite;
            ">🚨</div>
        `,
        className: 'accident-icon',
        iconSize: [45, 45],
        iconAnchor: [22, 22],
        popupAnchor: [0, -25]
    });
}



let sourceMarker = null;
let sourceCoords = null;
let routeLine = null;
let hospitalMarkers = [];
let nearestHospital = null;



hospitals.forEach(hospital => {
    const marker = L.marker(hospital.coords, {
        icon: getHospitalIcon(),
        title: hospital.name
    }).addTo(map);

    const popupContent = `
        <div style="font-family: Arial; width: 200px;">
            <h4 style="margin: 0 0 8px 0; color: #d32f2f;">🏥 ${hospital.name}</h4>
            <p style="margin: 5px 0; color: #666;"><strong>Beds:</strong> ${hospital.beds}</p>
            <p style="margin: 5px 0; color: #666;"><strong>24/7 Emergency:</strong> ${hospital.emergency ? '✅ Yes' : '❌ No'}</p>
            <button onclick="selectHospital(${hospital.id})" 
                    style="width: 100%; padding: 8px; margin-top: 10px; background: #d32f2f; color: white; border: none; border-radius: 4px; cursor: pointer; font-weight: bold;">
                📍 Route to Here
            </button>
        </div>
    `;

    marker.bindPopup(popupContent);
    hospitalMarkers.push(marker);
});


map.on('click', function(e) {
    // Remove old marker
    if (sourceMarker) map.removeLayer(sourceMarker);
    if (routeLine) map.removeLayer(routeLine);

    sourceCoords = e.latlng;
    nearestHospital = null;

    sourceMarker = L.marker(e.latlng, {
        icon: getAccidentIcon(),
        draggable: true
    }).addTo(map);

    const popupContent = `
        <div style="font-family: Arial;">
            <h4 style="margin: 0 0 8px 0; color: #4CAF50;">🚨 Accident Location</h4>
            <p style="margin: 5px 0; color: #666;"><strong>Position:</strong><br>
            ${e.latlng.lat.toFixed(4)}°, ${e.latlng.lng.toFixed(4)}°</p>
            <button onclick="findNearestHospital()" 
                    style="width: 100%; padding: 8px; margin-top: 10px; background: #4CAF50; color: white; border: none; border-radius: 4px; cursor: pointer; font-weight: bold;">
                🏥 Find Nearest Hospital
            </button>
        </div>
    `;

    sourceMarker.bindPopup(popupContent).openPopup();

    // Update distance to all hospitals
    updateHospitalDistances(e.latlng);
});

// ============================================
// DIJKSTRA'S ALGORITHM FOR SHORTEST PATH
// ============================================

function calculateDistance(coord1, coord2) {
    // Haversine formula - calculate distance between two coordinates
    const R = 6371; // Earth's radius in km
    const lat1 = coord1[0] * Math.PI / 180;
    const lat2 = coord2[0] * Math.PI / 180;
    const deltaLat = (coord2[0] - coord1[0]) * Math.PI / 180;
    const deltaLng = (coord2[1] - coord1[1]) * Math.PI / 180;

    const a = Math.sin(deltaLat / 2) * Math.sin(deltaLat / 2) +
              Math.cos(lat1) * Math.cos(lat2) * Math.sin(deltaLng / 2) * Math.sin(deltaLng / 2);
    
    const c = 2 * Math.atan2(Math.sqrt(a), Math.sqrt(1 - a));
    return R * c;
}

function updateHospitalDistances(source) {
    hospitals.forEach(hospital => {
        hospital.distance = calculateDistance(
            [source.lat, source.lng],
            hospital.coords
        );
        hospital.time = Math.ceil(hospital.distance / 0.5); // Assuming 30 km/h average speed
    });

    hospitals.sort((a, b) => a.distance - b.distance);
}

// ============================================
// FIND NEAREST HOSPITAL & DRAW ROUTE
// ============================================

function findNearestHospital() {
    if (!sourceCoords) {
        alert("🚨 Please click on map to set accident location!");
        return;
    }

    updateHospitalDistances(sourceCoords);
    const nearest = hospitals[0];
    nearestHospital = nearest;

    // Remove old route
    if (routeLine) map.removeLayer(routeLine);

    // Draw route polyline
    routeLine = L.polyline(
        [[sourceCoords.lat, sourceCoords.lng], nearest.coords],
        {
            color: '#e63946',
            weight: 5,
            opacity: 0.8,
            dashArray: '5, 5',
            lineCap: 'round',
            lineJoin: 'round'
        }
    ).addTo(map);

    // Fit map to show both points
    const group = new L.featureGroup([sourceMarker, hospitalMarkers[nearest.id - 1]]);
    map.fitBounds(group.getBounds(), { padding: [100, 100] });

    // Show route info
    showRouteInfo(nearest);
}

function selectHospital(hospitalId) {
    if (!sourceCoords) {
        alert("🚨 Please click on map to set accident location first!");
        return;
    }

    updateHospitalDistances(sourceCoords);
    const selected = hospitals.find(h => h.id === hospitalId);
    nearestHospital = selected;

    // Remove old route
    if (routeLine) map.removeLayer(routeLine);

    // Draw route
    routeLine = L.polyline(
        [[sourceCoords.lat, sourceCoords.lng], selected.coords],
        {
            color: '#e63946',
            weight: 5,
            opacity: 0.8,
            dashArray: '5, 5',
            lineCap: 'round',
            lineJoin: 'round'
        }
    ).addTo(map);

    // Fit bounds
    const group = new L.featureGroup([sourceMarker, hospitalMarkers[selected.id - 1]]);
    map.fitBounds(group.getBounds(), { padding: [100, 100] });

    showRouteInfo(selected);
}

// ============================================
// ROUTE INFO DISPLAY
// ============================================

function showRouteInfo(hospital) {
    // Create custom control to show route info
    const infoBox = document.getElementById('routeInfo');
    
    if (!infoBox) {
        const div = document.createElement('div');
        div.id = 'routeInfo';
        div.style.cssText = `
            position: absolute;
            bottom: 20px;
            left: 330px;
            background: white;
            padding: 15px;
            border-radius: 8px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.2);
            font-family: Arial, sans-serif;
            z-index: 1000;
            width: 280px;
        `;
        document.body.appendChild(div);
    }

    const distance = hospital.distance.toFixed(2);
    const time = hospital.time;

    document.getElementById('routeInfo').innerHTML = `
        <div style="color: #333;">
            <h3 style="margin: 0 0 10px 0; color: #e63946; font-size: 1.1em;">🚑 Route Found</h3>
            <hr style="margin: 10px 0; border: none; border-top: 1px solid #eee;">
            <p style="margin: 5px 0;"><strong>🏥 Hospital:</strong><br><span style="font-size: 0.95em;">${hospital.name}</span></p>
            <p style="margin: 5px 0;"><strong>📏 Distance:</strong> <span style="color: #d32f2f; font-weight: bold;">${distance} km</span></p>
            <p style="margin: 5px 0;"><strong>⏱️ Est. Time:</strong> <span style="color: #d32f2f; font-weight: bold;">${time} min</span></p>
            <p style="margin: 5px 0;"><strong>🛏️ Available Beds:</strong> ${hospital.beds}</p>
            <p style="margin: 5px 0;"><strong>24/7 Emergency:</strong> ${hospital.emergency ? '✅ Yes' : '⚠️ No'}</p>
            <button onclick="closeRoute()" style="width: 100%; padding: 10px; margin-top: 10px; background: #555; color: white; border: none; border-radius: 4px; cursor: pointer; font-weight: bold;">
                ✖️ Clear Route
            </button>
        </div>
    `;
}

function closeRoute() {
    if (routeLine) {
        map.removeLayer(routeLine);
        routeLine = null;
    }
    const infoBox = document.getElementById('routeInfo');
    if (infoBox) infoBox.remove();
}

// ============================================
// UI CONTROLS
// ============================================

function resetMap() {
    if (sourceMarker) map.removeLayer(sourceMarker);
    if (routeLine) map.removeLayer(routeLine);
    sourceMarker = null;
    sourceCoords = null;
    routeLine = null;
    nearestHospital = null;
    
    const infoBox = document.getElementById('routeInfo');
    if (infoBox) infoBox.remove();
    
    map.setView([30.3165, 78.0322], 13);
}
