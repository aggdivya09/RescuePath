const fs = require("fs");


let graph = {};


function makeNode(lon, lat) {
    return lat+","+lon;
}


function distance(lat1, lon1, lat2, lon2) {
    const R = 6371;
    const toRad = x => x * Math.PI / 180;

    let dLat = toRad(lat2 - lat1);
    let dLon = toRad(lon2 - lon1);

    lat1 = toRad(lat1);
    lat2 = toRad(lat2);

    let a = Math.sin(dLat/2) ** 2 +
            Math.sin(dLon/2) ** 2 * Math.cos(lat1) * Math.cos(lat2);

    let c = 2 * Math.atan2(Math.sqrt(a), Math.sqrt(1 - a));
    return R * c;
}


function addEdge(lon1, lat1, lon2, lat2) {
    const u = makeNode(lon1, lat1);
    const v = makeNode(lon2, lat2);
    const w = distance(lat1, lon1, lat2, lon2);

    if (!graph[u]) graph[u] = [];
    if (!graph[v]) graph[v] = [];

    graph[u].push({ node: v, weight: w });
    graph[v].push({ node: u, weight: w }); // remove if one-way
}


function processLine(coords) {
    for (let i = 0; i < coords.length - 1; i++) {
        let [lon1, lat1] = coords[i];
        let [lon2, lat2] = coords[i + 1];
        addEdge(lon1, lat1, lon2, lat2);
    }
}


function processMultiLine(multiCoords) {
    multiCoords.forEach(line => processLine(line));
}


function loadGeoJSON(file) {
    const data = JSON.parse(fs.readFileSync(file, "utf-8"));

    data.features.forEach(feature => {
        const geometry = feature.geometry;

        if (geometry.type === "LineString") {
            processLine(geometry.coordinates);
        } else if (geometry.type === "MultiLineString") {
            processMultiLine(geometry.coordinates);
        }
    });
}


function dijkstra(start) {
    let dist = {};
    let parent = {};
    let visited = {};
    let pq = [];

    
    for (let node in graph) {
        dist[node] = Infinity;
        parent[node] = null;
    }

    dist[start] = 0;
    pq.push({ node: start, dist: 0 });

    while (pq.length > 0) {
        pq.sort((a, b) => a.dist - b.dist);
        let { node: u } = pq.shift();

        if (visited[u]) continue;
        visited[u] = true;

        for (let neighbor of graph[u]) {
            let v = neighbor.node;
            let w = neighbor.weight;

            if (dist[u] + w < dist[v]) {
                dist[v] = dist[u] + w;
                parent[v] = u;
                pq.push({ node: v, dist: dist[v] });
            }
        }
    }

    return { dist, parent };
}


function getPath(parent, end) {
    let path = [];
    while (end) {
        path.push(end);
        end = parent[end];
    }
    return path.reverse();
}


loadGeoJSON("lines.geojson"); 
const nodes = Object.keys(graph);
const start = nodes[0];
const end = nodes[nodes.length - 1];

const { dist, parent } = dijkstra(start);

console.log("Shortest distance from start to end:");
console.log(dist[end]);

console.log("\nShortest path:");
console.log(getPath(parent, end));