<?php
require_once __DIR__ . '/../data_structures/common_functions.php';
session_start();
date_default_timezone_set('Asia/Kolkata');

if (!isLoggedIn()) {
    header('Location: login.php');
    exit();
}

// Loads capsule by id
$id = isset($_GET['id']) ? sanitizeInput($_GET['id']) : '';
if (!$id) {
    echo 'No capsule id provided.';
    exit();
}

$file = __DIR__ . '/../data/time_capsules.json';
$data = file_exists($file) ? json_decode(file_get_contents($file), true) : [];
$capsule = null;

foreach ($data as $i => $c) {
    if (($c['id'] ?? '') === $id && (($_SESSION['email'] === ($c['recipient_email'] ?? '')) || $_SESSION['email'] === ($c['user_email'] ?? ''))) {
        $capsule = $c;
        // Unlock if reveal date has passed
        if ((!isset($c['unlocked']) || $c['unlocked'] != 1) && isset($c['reveal_date']) && strcmp(date('Y-m-d H:i:s'), $c['reveal_date']) >= 0) {
            $data[$i]['unlocked'] = 1;
            file_put_contents($file, json_encode($data, JSON_PRETTY_PRINT));
            $capsule['unlocked'] = 1; // Update for current view
        }
        break;
    }
}

if (!$capsule) {
    echo 'Capsule not found or you do not have access.';
    exit();
}

$unlocked = isset($capsule['unlocked']) && $capsule['unlocked'] == 1;

$mediaItems = [];
if (isset($capsule['media']) && is_array($capsule['media'])) {
    $mediaItems = $capsule['media'];
} elseif (!empty($capsule['media']) && is_string($capsule['media'])) {
    $mediaItems = [
        [
            'path' => $capsule['media'],
            'type' => $capsule['media_type'] ?? ''
        ]
    ];
}


$otherCapsules = [];
foreach ($data as $c) {
    if (
        isset($c['id']) && $c['id'] !== $capsule['id'] &&
        (
            $_SESSION['email'] === ($c['recipient_email'] ?? '') ||
            $_SESSION['email'] === ($c['user_email'] ?? '')
        )
    ) {
        $otherCapsules[] = $c;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Time Capsule Viewer</title>
    <link rel="stylesheet" href="output.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Kalam:wght@400;700&family=Quicksand:wght@400;500;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.1.1/css/all.min.css">
    <link rel="stylesheet" href="blobs.css">
    <style>
        body { font-family: 'Quicksand', sans-serif; }
        .font-kalam { font-family: 'Kalam', cursive; }
    </style>
</head>
<body class="bg-gradient-to-br from-purple-50 via-blue-50 to-pink-50 min-h-screen">
    <div class="blobs-bg">
      <div class="blob blob1"></div><div class="blob blob2"></div><div class="blob blob3"></div>
      <div class="blob blob4"></div><div class="blob blob5"></div><div class="blob blob6"></div>
      <div class="blob blob7"></div><div class="blob blob8"></div><div class="blob blob9"></div>
      <div class="blob blob10"></div><div class="blob blob11"></div><div class="blob blob12"></div><div class="blob blob13"></div>
    </div>
    <div class="w-full max-w-7xl mx-auto p-4">
        <header class="bg-white/80 backdrop-blur-md border-b border-[#E8E3F5] sticky top-0 z-50 shadow-sm rounded-xl mb-8">
            <nav class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="flex justify-between items-center h-16">
                    <div class="flex items-center">
                        <a href="homepage.php" class="flex items-center space-x-2 hover:scale-105 transition-transform">
                            <i class="fas fa-heart text-2xl text-[#8B7EC8]"></i>
                            <span class="text-xl font-semibold text-[#2D2A3D]">MemoryBook</span>
                        </a>
                    </div>
                    <div class="hidden md:flex items-center space-x-8">
                        <a href="homepage.php" class="text-[#6B6B7D] hover:text-[#8B7EC8] transition-colors">Home</a>
                        <a href="dashboard.php" class="text-[#6B6B7D] hover:text-[#8B7EC8] transition-colors">Dashboard</a>
                        <a href="memory_capsules.php" class="font-medium text-[#8B7EC8] hover:text-[#8B7EC8] transition-colors">Memory Capsules</a>
                    </div>
                    <div class="flex items-center space-x-4">
                        <a href="memory_capsules.php" class="px-4 py-2 rounded-lg bg-[#8B7EC8] hover:bg-[#7A6BB5] text-white font-semibold shadow transition-colors">&larr; Return to Capsules</a>
                        <div class="relative" id="userMenuContainer">
                            <?php
                            $currentEmail = $_SESSION['email'];
                            $userInitial = strtoupper($currentEmail[0]);
                            ?>
                            <button id="userAvatar" class="w-8 h-8 bg-[#E8E3F5] rounded-full flex items-center justify-center cursor-pointer hover:bg-[#D8D0F0] transition-colors focus:outline-none focus:ring-2 focus:ring-[#8B7EC8]">
                                <span class="text-sm font-medium text-[#8B7EC8]"><?= htmlspecialchars($userInitial) ?></span>
                            </button>
                            <div id="userDropdown" class="hidden absolute right-0 mt-2 w-48 bg-white rounded-xl shadow-lg border border-gray-200 z-50">
                                <div class="px-4 py-2 text-sm text-gray-600 border-b border-gray-200">
                                    <div class="font-medium text-gray-900"><?= htmlspecialchars($currentEmail) ?></div>
                                </div>
                                <a href="profile.php" class="block px-4 py-2 text-sm text-gray-600 hover:bg-gray-50">Profile</a>
                                <a href="login.php?logout=1" class="block w-full text-left px-4 py-2 text-sm text-red-600 hover:bg-red-50">Sign Out</a>
                            </div>
                        </div>
                    </div>
                </div>
            </nav>
        </header>
        <main class="flex flex-col lg:flex-row gap-8 mb-8">
            <div class="flex-[2] flex flex-col gap-8">
                <div class="bg-white/90 backdrop-blur-sm border border-gray-200 rounded-2xl shadow-lg p-4 overflow-hidden">
                    <div class="bg-black aspect-video flex justify-center items-center relative rounded-lg" id="media-viewer-container">
                        <?php if ($unlocked): ?>
                            <?php if (!empty($mediaItems)): ?>
                                <div id="media-content" class="w-full h-full flex items-center justify-center"></div>
                            <?php else: ?>
                                <div class="text-center text-gray-400 w-full">
                                    <i class="fas fa-paperclip text-5xl mb-4"></i>
                                    <p class="text-lg font-semibold">No attached media</p>
                                </div>
                            <?php endif; ?>
                        <?php else: ?>
                            <div class="text-center text-gray-400 w-full">
                                <i class="fas fa-lock text-5xl mb-4"></i>
                                <p class="text-lg font-semibold">Content is Locked</p>
                            </div>
                        <?php endif; ?>
                    </div>
                     <?php if ($unlocked && count($mediaItems) > 1): ?>
                        <div class="flex justify-center items-center gap-4 mt-4">
                            <button id="prev-media" class="bg-gray-200 text-gray-800 rounded-full w-10 h-10 flex items-center justify-center hover:bg-gray-300 transition-all focus:outline-none">
                                <i class="fas fa-chevron-left"></i>
                            </button>
                            <button id="next-media" class="bg-gray-200 text-gray-800 rounded-full w-10 h-10 flex items-center justify-center hover:bg-gray-300 transition-all focus:outline-none">
                                <i class="fas fa-chevron-right"></i>
                            </button>
                        </div>
                    <?php endif; ?>
                </div>
                <div class="bg-white/90 backdrop-blur-sm border border-gray-200 p-6 rounded-2xl shadow-lg">
                    <h1 class="text-3xl font-bold text-[#2D2A3D] mb-2"><?= htmlspecialchars($capsule['title'] ?? 'Time Capsule') ?></h1>
                    <div class="text-sm text-gray-500 mb-6 border-b border-gray-200 pb-4">
                        <span class="mr-6"><strong>Created:</strong> <?= htmlspecialchars($capsule['created_at'] ?? '-') ?></span>
                        <span><strong>Reveals:</strong> <?= htmlspecialchars($capsule['reveal_date'] ?? '-') ?></span>
                    </div>
                    <div class="text-lg leading-relaxed text-gray-700 space-y-4">
                        <p class="font-semibold"><?= nl2br(htmlspecialchars($capsule['message'] ?? '')) ?></p>
                        <?php if (!empty($capsule['description'])): ?>
                            <p><?= nl2br(htmlspecialchars($capsule['description'])) ?></p>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            <div class="flex-1 lg:sticky top-24 flex flex-col gap-8">
                <div class="bg-white/90 backdrop-blur-sm border border-gray-200 text-gray-800 p-6 rounded-2xl shadow-lg">
                    <div class="flex items-center mb-4 pb-4 border-b border-gray-200">
                        <i class="fas <?= $unlocked ? 'fa-lock-open text-green-500' : 'fa-lock text-[#F48498]' ?> text-lg mr-4 w-6 text-center"></i>
                        <span class="font-bold">Status</span>
                        <span class="ml-auto font-semibold <?= $unlocked ? 'text-green-500' : 'text-[#F48498]' ?>"><?= $unlocked ? 'Unlocked' : 'Locked' ?></span>
                    </div>
                    <div class="flex items-center mb-4 pb-4 border-b border-gray-200">
                        <i class="fas fa-paper-plane text-lg mr-4 w-6 text-center text-[#8B7EC8]"></i>
                        <span class="font-bold">From</span>
                        <span class="ml-auto font-normal break-all"><?= htmlspecialchars($capsule['user_email']) ?></span>
                    </div>
                    <div class="flex items-center">
                        <i class="fas fa-inbox text-lg mr-4 w-6 text-center text-[#8B7EC8]"></i>
                        <span class="font-bold">To</span>
                        <span class="ml-auto font-normal break-all"><?= htmlspecialchars($capsule['recipient_email']) ?></span>
                    </div>
                </div>
                <div class="bg-white/90 backdrop-blur-sm border border-gray-200 p-6 rounded-2xl shadow-lg">
                    <h4 class="font-bold text-lg text-[#2D2A3D] mb-4 border-b border-gray-200 pb-4">Other Capsules</h4>
                    <ul class="list-none space-y-2">
                        <?php foreach ($otherCapsules as $oc): ?>
                            <li>
                                <a href="open_capsule.php?id=<?= urlencode($oc['id']) ?>" class="flex items-center p-2 rounded-lg hover:bg-gray-100 transition-colors">
                                    <i class="fas fa-box mr-4 text-[#8B7EC8]"></i>
                                    <div class="flex-grow">
                                        <span class="font-bold text-sm text-[#2D2A3D]"> <?= htmlspecialchars($oc['title'] ?? 'Capsule') ?> </span>
                                        <div class="text-xs text-gray-500"> <?= htmlspecialchars($oc['reveal_date'] ?? '-') ?> </div>
                                    </div>
                                </a>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            </div>
        </main>
        <footer class="text-center text-[#8B7EC8]/80 text-xs py-8 font-kalam">
            <p>MemoryBook &copy; <?= date('Y') ?> | Designed with <i class="fas fa-heart text-[#F48498]"></i></p>
        </footer>
    </div>
    <script>
        const userAvatar = document.getElementById('userAvatar');
        const userDropdown = document.getElementById('userDropdown');
        if (userAvatar && userDropdown) {
            userAvatar.addEventListener('click', (e) => {
                e.stopPropagation();
                userDropdown.classList.toggle('hidden');
            });
            document.addEventListener('click', (e) => {
                if (!userDropdown.classList.contains('hidden') && !userAvatar.contains(e.target) && !userDropdown.contains(e.target)) {
                    userDropdown.classList.add('hidden');
                }
            });
        }
    </script>
    <?php if ($unlocked && !empty($mediaItems)): ?>
<script>
document.addEventListener('DOMContentLoaded', function() {
    const mediaFiles = <?= json_encode($mediaItems); ?>;
    let currentIndex = 0;
    const mediaContentEl = document.getElementById('media-content');
    const prevBtn = document.getElementById('prev-media');
    const nextBtn = document.getElementById('next-media');

    function renderMedia(index) {
        if (!mediaFiles[index]) return;
        const item = mediaFiles[index];
        const mediaType = item.type || '';
        const src = item.path.startsWith('data:') ? item.path : `../${item.path}`;
        mediaContentEl.innerHTML = '';
        let mediaEl;
        if (mediaType.startsWith('image/')) {
            mediaEl = document.createElement('img');
            mediaEl.className = 'w-full h-full object-contain';
            mediaEl.alt = 'Capsule Media';
            mediaEl.src = src;
        } else if (mediaType.startsWith('audio/')) {
            mediaEl = document.createElement('audio');
            mediaEl.className = 'w-3/4';
            mediaEl.controls = true;
            mediaEl.src = src;
        } else if (mediaType.startsWith('video/')) {
            mediaEl = document.createElement('video');
            mediaEl.className = 'w-full h-full object-contain';
            mediaEl.controls = true;
            mediaEl.autoplay = false;
            mediaEl.src = src;
        } else {
            mediaEl = document.createElement('div');
            mediaEl.className = 'text-center text-gray-400 w-full';
            mediaEl.innerHTML = `Unsupported media type (${mediaType})`;
        }
        if (mediaEl && (mediaType.startsWith('audio/') || mediaType.startsWith('video/'))) {
            const sourceEl = document.createElement('source');
            sourceEl.src = src;
            sourceEl.type = mediaType;
            mediaEl.appendChild(sourceEl);
            mediaEl.appendChild(document.createTextNode('Your browser does not support this media type.'));
        }
        if (mediaEl) mediaContentEl.appendChild(mediaEl);
    }

    function showPrevMedia() {
        currentIndex = (currentIndex - 1 + mediaFiles.length) % mediaFiles.length;
        renderMedia(currentIndex);
    }
    function showNextMedia() {
        currentIndex = (currentIndex + 1) % mediaFiles.length;
        renderMedia(currentIndex);
    }
    
    if(prevBtn && nextBtn) {
        prevBtn.addEventListener('click', showPrevMedia);
        nextBtn.addEventListener('click', showNextMedia);
    }
    
    renderMedia(currentIndex);
});
</script>
<?php endif; ?>
</body>
</html>
 