<?php
session_start();
require_once __DIR__ . '/../data_structures/common_functions.php';
require_once __DIR__ . '/../data_structures/network_matrix.php';

// --- User and Data Loading ---
$currentUserEmail = $_SESSION['email'] ?? 'user@example.com';
$usersData = loadUsers(__DIR__ . '/../data/users.json');
$memoriesData = loadMemories(__DIR__ . '/../data/memories.json');

// --- Build Full Friendship Graph (for BFS and suggestions) ---
$allUsers = [];
foreach ($usersData as $bucket) {
    foreach ($bucket as $email => $user) {
        $allUsers[$email] = $user;
    }
}
list($nodes, $emails, $emailIndexMap) = buildNodesAndIndexMap($usersData);
$size = count($nodes);
$friendshipMatrix = buildFriendshipMatrix($nodes, $emailIndexMap, $allUsers);

// --- Personalized Memory Data (only current user's memories) ---
$myMemories = [];
foreach ($memoriesData as $memory) {
    if (($memory['owner'] ?? null) === $currentUserEmail) {
        $myMemories[] = $memory;
    }
}
// Memory matrix: current user's memories
$memoryMatrix = array_fill(0, $size, array_fill(0, $size, 0));
foreach ($myMemories as $memory) {
    $ownerIdx = $emailIndexMap[$currentUserEmail];
    foreach ($memory['friends'] as $friendEmail) {
        if (isset($emailIndexMap[$friendEmail])) {
            $friendIdx = $emailIndexMap[$friendEmail];
            $memoryMatrix[$ownerIdx][$friendIdx]++;
            $memoryMatrix[$friendIdx][$ownerIdx]++;
        }
    }
}
$memoryEdges = [];
foreach ($myMemories as $memory) {
    $ownerIdx = $emailIndexMap[$currentUserEmail];
    foreach ($memory['friends'] as $friendEmail) {
        if (isset($emailIndexMap[$friendEmail])) {
            $friendIdx = $emailIndexMap[$friendEmail];
            $edgeKey = $ownerIdx < $friendIdx ? $ownerIdx.'_'.$friendIdx : $friendIdx.'_'.$ownerIdx;
            $memoryEdges[$edgeKey][] = [
                'id' => $memory['memory_id'] ?? '',
                'title' => $memory['title'] ?? '',
                'ownerIdx' => $ownerIdx,
                'friendIdx' => $friendIdx
            ];
        }
    }
}
$userTypes = [];
$friendEmails = [];
if (isset($allUsers[$currentUserEmail]) && !empty($allUsers[$currentUserEmail]['friends'])) {
    foreach ($allUsers[$currentUserEmail]['friends'] as $f) {
        $friendEmails[] = $f['email'];
    }
}
foreach ($emails as $i => $email) {
    if ($email === $currentUserEmail) $userTypes[$i] = 'current';
    elseif (in_array($email, $friendEmails)) $userTypes[$i] = 'friend';
    else $userTypes[$i] = 'other';
}
$nodePositions = [];
$centerX = 450; $centerY = 450; $radius = 350;
for ($i = 0; $i < $size; $i++) {
    $angle = 2 * M_PI * $i / $size - M_PI/2;
    $nodePositions[] = [
        'x' => $centerX + $radius * cos($angle),
        'y' => $centerY + $radius * sin($angle)
    ];
}
$memoryLocations = [];
foreach ($myMemories as $memory) {
    if (!empty($memory['location'])) {
        $memoryLocations[] = [
            'title' => $memory['title'] ?? '',
            'location' => $memory['location'],
            'id' => $memory['memory_id'] ?? ''
        ];
    }
}
// Friend suggestions (BFS on full friendship graph)
$friendGraph = [];
foreach ($allUsers as $email => $user) {
    $friendGraph[$email] = array_map(function($f){return $f['email'];}, $user['friends'] ?? []);
}
$suggestedEmails = getFriendSuggestions($friendshipMatrix, $emailIndexMap, $emails, $currentUserEmail, $friendGraph);
$suggestedFriends = [];
foreach ($suggestedEmails as $email) {
    $suggestedFriends[] = $nodes[$email]['name'] ?? $email;
}
$graphData = [
    'userCount' => $size,
    'currentUserIndex' => $emailIndexMap[$currentUserEmail] ?? -1,
    'users' => array_values($nodes),
    'nodeTypes' => array_values($userTypes),
    'nodePositions' => $nodePositions,
    'friendshipMatrix' => $friendshipMatrix,
    'memoryMatrix' => $memoryMatrix,
    'memoryEdges' => $memoryEdges,
    'memoryLocations' => $memoryLocations,
    'suggestedFriends' => $suggestedFriends
];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Friendship & Memory Graph</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Quicksand:wght@400;500;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="blobs.css">
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" crossorigin=""/>
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js" crossorigin=""></script>
    <style>
        body { font-family: 'Quicksand', sans-serif; background: linear-gradient(135deg, #f8fafc 60%, #f3e8ff 100%); }
        .graph-container { width: 100%; max-width: 900px; margin-left: auto; margin-right: auto; }
        .graph-svg { background: #fff; border-radius: 2rem; box-shadow: 0 6px 24px 0 #e9d5ff44; border: 1px solid #f3e8ff; }
        .node-label { font-size: 13px; fill: #6b7280; text-anchor: middle; pointer-events: none; font-weight: 500; }
        .node-initials { font-size: 15px; fill: #fff; text-anchor: middle; dy: .3em; pointer-events: none; font-weight: 700; }
        .tooltip { position: absolute; padding: 0.5rem 0.75rem; background: #fff0fa; color: #7c3aed; border-radius: 0.75rem; font-size: 0.95rem; pointer-events: none; opacity: 0; transition: opacity 0.2s; z-index: 10; box-shadow: 0 2px 8px #e9d5ff44; }
        #map { height: 600px; border-radius: 2rem; border: 1px solid #e2e8f0; box-shadow: 0 4px 16px 0 #f3e8ff66; z-index: 1; }
        .soft-card { background: rgba(255,255,255,0.7); backdrop-filter: blur(10px); border-radius: 2rem; box-shadow: 0 6px 24px 0 #e9d5ff33; border: 1px solid #f3e8ff; }
        .soft-toggle { background: #f3e8ff; border-radius: 1.5rem; }
        .soft-toggle button { border-radius: 1.5rem; transition: background-color 0.2s, color 0.2s; }
        .soft-toggle .active { background: #818cf8; color: #fff; }
        .memory-pill { background: #f9a8d4; color: #7c3aed; border-radius: 9999px; padding: 2px 12px; font-size: 12px; font-weight: 600; box-shadow: 0 2px 8px #f9a8d455; cursor: pointer; border: 1px solid #f3e8ff; }
        .memory-pill:hover { background: #f472b6; color: #fff; }
    </style>
</head>
<body class="text-gray-800 relative overflow-x-hidden">
<div class="blobs-bg">
    <div class="blob blob1"></div><div class="blob blob2"></div><div class="blob blob3"></div><div class="blob blob4"></div>
    <div class="blob blob5"></div><div class="blob blob6"></div><div class="blob blob7"></div><div class="blob blob8"></div>
    <div class="blob blob9"></div><div class="blob blob10"></div><div class="blob blob11"></div><div class="blob blob12"></div><div class="blob blob13"></div>
</div>
<?php include 'navbar.php'; ?>
<div class="max-w-7xl mx-auto p-4 lg:p-6">
    <div class="flex flex-col lg:flex-row gap-8">
        <main class="flex-1 min-w-0">
            <div class="soft-card p-6 mb-8">
                <div class="text-center mb-6">
                    <h2 class="text-3xl font-bold text-purple-700">Your Social Universe</h2>
                    <p class="text-gray-500 mt-1">Visualizing your friendships and shared memories.</p>
                </div>
                <div class="flex flex-col items-center gap-2 mb-4">
                    <div class="soft-toggle flex w-full gap-2 max-w-xs mx-auto p-1">
                        <button id="toggleNetworkBtn" class="px-4 py-2 text-sm font-semibold w-full">Network</button>
                        <button id="toggleMapBtn" class="px-4 py-2 text-sm font-semibold w-full">Map</button>
                    </div>
                    <div id="graphControls" class="soft-toggle flex w-full gap-2 mt-2 max-w-xs mx-auto p-1">
                        <button id="showFriendshipsBtn" class="px-2 py-1 text-xs font-semibold w-full">Friendships</button>
                        <button id="showMemoriesBtn" class="px-2 py-1 text-xs font-semibold w-full">Memories</button>
                        <button id="showBothBtn" class="px-2 py-1 text-xs font-semibold w-full">Both</button>
                    </div>
                </div>
                <div id="graphView">
                    <div class="flex flex-wrap justify-center items-center gap-x-6 gap-y-2 mb-4 p-4 bg-[#f3e8ff] rounded-xl text-sm">
                        <span class="font-bold">Legend:</span>
                        <span class="flex items-center"><span class="w-3 h-3 rounded-full bg-pink-400 mr-2"></span> You</span>
                        <span class="flex items-center"><span class="w-3 h-3 rounded-full bg-purple-400 mr-2"></span> Friend</span>
                        <span class="flex items-center"><span class="w-3 h-3 rounded-full bg-teal-400 mr-2"></span> Suggested</span>
                        <span class="flex items-center"><span class="w-3 h-3 rounded-full bg-gray-300 mr-2"></span> Other</span>
                        <span class="flex items-center"><svg width="20" height="10" class="mr-2"><line x1="0" y1="5" x2="20" y2="5" stroke="#4ade80" stroke-width="2" marker-end="url(#arrowhead-suggestion-legend)"/></svg> Suggestion Path</span>
                        <span class="flex items-center"><svg width="20" height="10" class="mr-2"><line x1="0" y1="5" x2="20" y2="5" stroke="#f9a8d4" stroke-width="2.5" stroke-dasharray="7,7"/></svg> Your Memory</span>
                    </div>
                    <div class="graph-container">
                        <svg id="friendshipGraph" class="graph-svg" width="100%" viewBox="0 0 900 900"></svg>
                    </div>
                </div>
                <div id="mapView" class="hidden">
                    <div class="max-w-5xl mx-auto"><div id="map"></div></div>
                </div>
            </div>
        </main>
        <aside class="w-full lg:w-72 shrink-0">
            <div class="soft-card p-5 sticky top-6">
                <h3 class="text-lg font-bold text-gray-800 mb-3">People to Follow</h3>
                <div id="suggestions-list" class="space-y-2">
                    <?php if (empty($graphData['suggestedFriends'])): ?>
                        <p class="text-sm text-gray-500">No suggestions right now.</p>
                    <?php else: ?>
                        <?php foreach ($graphData['suggestedFriends'] as $name): ?>
                            <div class="p-2 rounded-lg bg-[#ede9fe]"><p class="font-semibold text-sm text-[#7c3aed]"><?= htmlspecialchars($name) ?></p></div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
        </aside>
    </div>
</div>
<div id="tooltip" class="tooltip"></div>
<script>
const graphData = <?php echo json_encode($graphData); ?>;
const svg = document.getElementById('friendshipGraph');
const tooltip = document.getElementById('tooltip');
const nodeColors = { current: '#f472b6', friend: '#a78bfa', suggestion: '#2dd4bf', other: '#d1d5db' };
const friendshipColor = '#a5b4fc';
const memoryColor = '#f9a8d4';

let showFriendships = true;
let showMemories = false;

function getEdgeEndpoints(p1, p2, radius, offset = 0) {
    const dx = p2.x - p1.x;
    const dy = p2.y - p1.y;
    const dist = Math.sqrt(dx * dx + dy * dy);
    if (dist === 0) return { x1: p1.x, y1: p1.y, x2: p2.x, y2: p2.y };
    const offsetX = (dx / dist) * radius;
    const offsetY = (dy / dist) * radius;
    const perpX = -(dy / dist) * offset;
    const perpY = (dx / dist) * offset;
    return {
        x1: p1.x + offsetX + perpX,
        y1: p1.y + offsetY + perpY,
        x2: p2.x - offsetX + perpX,
        y2: p2.y - offsetY + perpY
    };
}
function createSvgElement(tag, attrs) {
    const el = document.createElementNS('http://www.w3.org/2000/svg', tag);
    for (const k in attrs) el.setAttribute(k, attrs[k]);
    return el;
}
function createMarkers(defs) {
    const marker = createSvgElement('marker', { id: 'arrowhead', viewBox: '0 0 10 10', refX: '8', refY: '5', markerWidth: '7', markerHeight: '7', orient: 'auto-start-reverse' });
    marker.appendChild(createSvgElement('path', { d: 'M 0 0 L 10 5 L 0 10 z', fill: friendshipColor, opacity: 0.7 }));
    defs.appendChild(marker);
}
function drawGraph() {
    svg.innerHTML = '';
    const defs = createSvgElement('defs', {});
    createMarkers(defs);
    svg.appendChild(defs);
    // Draw memory edges (undirected, with pills)
    if (showMemories) {
        for (let i = 0; i < graphData.userCount; i++) {
            for (let j = i + 1; j < graphData.userCount; j++) {
                const freq = graphData.memoryMatrix[i][j];
                if (freq > 0) {
                    const p1 = graphData.nodePositions[i], p2 = graphData.nodePositions[j];
                    const offset = -12;
                    const { x1, y1, x2, y2 } = getEdgeEndpoints(p1, p2, 28, offset);
                    svg.appendChild(createSvgElement('line', { x1, y1, x2, y2, stroke: memoryColor, 'stroke-width': 2 + freq, 'stroke-dasharray': '7,7', opacity: 0.7 }));
                    // Pills for each memory between i and j
                    const edgeKey = i < j ? i + '_' + j : j + '_' + i;
                    if (graphData.memoryEdges[edgeKey]) {
                        const mx = (x1 + x2) / 2, my = (y1 + y2) / 2;
                        graphData.memoryEdges[edgeKey].forEach(mem => {
                            const f = document.createElementNS('http://www.w3.org/2000/svg', 'foreignObject');
                            f.setAttribute('x', mx - 40); f.setAttribute('y', my - 16); f.setAttribute('width', 80); f.setAttribute('height', 32);
                            const div = document.createElement('div');
                            div.setAttribute('class', 'memory-pill');
                            div.innerText = mem.title;
                            div.onclick = () => { window.location.href = 'memory_details.php?id=' + encodeURIComponent(mem.id); };
                            f.appendChild(div);
                            svg.appendChild(f);
                        });
                    }
                }
            }
        }
    }
    // Draw friendship edges (directed)
    if (showFriendships) {
        for (let i = 0; i < graphData.userCount; i++) {
            for (let j = 0; j < graphData.userCount; j++) {
                if (graphData.friendshipMatrix[i][j]) {
                    const p1 = graphData.nodePositions[i], p2 = graphData.nodePositions[j];
                    let offset = 0;
                    if (graphData.friendshipMatrix[j][i] && i !== j) offset = 12;
                    const { x1, y1, x2, y2 } = getEdgeEndpoints(p1, p2, 28, offset);
                    svg.appendChild(createSvgElement('line', { x1, y1, x2, y2, stroke: friendshipColor, 'stroke-width': 2, opacity: 0.8, 'marker-end': 'url(#arrowhead)' }));
                }
            }
        }
    }
    // Draw nodes
    graphData.users.forEach((user, i) => {
        const pos = graphData.nodePositions[i];
        const type = graphData.nodeTypes[i];
        const initials = (user.name || user.username || '??').substring(0, 2).toUpperCase();
        const displayName = user.name || user.username || 'Unknown';
        const nodeGroup = createSvgElement('g', { class: 'node' });
        nodeGroup.appendChild(createSvgElement('circle', { cx: pos.x, cy: pos.y, r: 28, fill: nodeColors[type], stroke: '#ffffff88', 'stroke-width': 4 }));
        nodeGroup.appendChild(createSvgElement('text', { x: pos.x, y: pos.y, class: 'node-initials' })).textContent = initials;
        nodeGroup.appendChild(createSvgElement('text', { x: pos.x, y: pos.y + 28 + 15, class: 'node-label' })).textContent = displayName;
        nodeGroup.addEventListener('mousemove', (e) => {
            tooltip.style.opacity = '1';
            tooltip.style.left = `${e.pageX + 15}px`;
            tooltip.style.top = `${e.pageY}px`;
            tooltip.innerHTML = `<strong>${displayName}</strong>`;
        });
        nodeGroup.addEventListener('mouseleave', () => { tooltip.style.opacity = '0'; });
        svg.appendChild(nodeGroup);
    });
}
function updateEdgeToggleButtons() {
    const btns = [document.getElementById('showFriendshipsBtn'), document.getElementById('showMemoriesBtn'), document.getElementById('showBothBtn')];
    btns.forEach(btn => btn.classList.remove('active'));
    if (showFriendships && showMemories) btns[2].classList.add('active');
    else if (showFriendships) btns[0].classList.add('active');
    else if (showMemories) btns[1].classList.add('active');
}
document.getElementById('showFriendshipsBtn').onclick = () => { showFriendships = true; showMemories = false; updateEdgeToggleButtons(); drawGraph(); };
document.getElementById('showMemoriesBtn').onclick = () => { showFriendships = false; showMemories = true; updateEdgeToggleButtons(); drawGraph(); };
document.getElementById('showBothBtn').onclick = () => { showFriendships = true; showMemories = true; updateEdgeToggleButtons(); drawGraph(); };
document.getElementById('toggleNetworkBtn').onclick = () => {
    document.getElementById('mapView').classList.add('hidden');
    document.getElementById('graphView').classList.remove('hidden');
    document.getElementById('graphControls').style.display = 'flex';
    document.getElementById('toggleNetworkBtn').classList.add('active');
    document.getElementById('toggleMapBtn').classList.remove('active');
};
document.getElementById('toggleMapBtn').onclick = () => {
    document.getElementById('graphView').classList.add('hidden');
    document.getElementById('mapView').classList.remove('hidden');
    document.getElementById('graphControls').style.display = 'none';
    document.getElementById('toggleMapBtn').classList.add('active');
    document.getElementById('toggleNetworkBtn').classList.remove('active');
    if (!window._leafletMapLoaded) {
        initMap();
        window._leafletMapLoaded = true;
    } else {
        setTimeout(()=>window._leafletMap.invalidateSize(), 200);
    }
};
const LOCATIONIQ_API_KEY = "";

function geocodeLocation(location) {
  // Returns a Promise that resolves to [lat, lon] or null
  return fetch(`https://us1.locationiq.com/v1/search?key=${LOCATIONIQ_API_KEY}&q=${encodeURIComponent(location)}&format=json&limit=1`)
    .then(res => res.json())
    .then(data => {
      if (Array.isArray(data) && data.length > 0) {
        return [parseFloat(data[0].lat), parseFloat(data[0].lon)];
      }
      return null;
    });
}

function initMap() {
  const map = L.map('map').setView([20.5937, 78.9629], 4);
  window._leafletMap = map;
  L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
    maxZoom: 18,
    attribution: '© OpenStreetMap'
  }).addTo(map);

  let anyMarker = false;
  let pending = graphData.memoryLocations.length;
  if (pending === 0) {
    map._container.innerHTML = '<div class="text-center text-gray-400 mt-12">No memory locations found.</div>';
    return;
  }
  graphData.memoryLocations.forEach(mem => {
    geocodeLocation(mem.location).then(coords => {
      let lat, lon, found = false;
      if (coords) {
        lat = coords[0]; lon = coords[1]; found = true;
      } else {
        lat = 20.5937; lon = 78.9629; // fallback
      }
      anyMarker = true;
      const marker = L.marker([lat, lon]).addTo(map);
      let popup = `<a href='memory_details.php?id=${encodeURIComponent(mem.id)}' class='text-[#7c3aed] font-bold'>${mem.title}</a><br>`;
      if (found) {
        popup += `<a href='https://www.google.com/maps/search/?api=1&query=${encodeURIComponent(mem.location)}' target='_blank' class='text-blue-600 underline'>${mem.location}</a>`;
      } else {
        popup += `<span class='text-xs text-gray-500'>Could not find an exact location for: <b>${mem.location}</b>. Showing approximate area.</span>`;
      }
      marker.bindPopup(popup);
      pending--;
      if (pending === 0 && !anyMarker) {
        map._container.innerHTML = '<div class="text-center text-gray-400 mt-12">No memory locations found.</div>';
      }
    });
  });
}
// Initial draw
updateEdgeToggleButtons();
drawGraph();
document.getElementById('toggleNetworkBtn').classList.add('active');
</script>
</body>
</html>
