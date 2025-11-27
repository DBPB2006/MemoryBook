<?php
session_start();
require_once __DIR__ . '/../data_structures/common_functions.php';
require_once __DIR__ . '/../data_structures/linkedlist.php';

if (!isset($_SESSION['email'])) {
    header('Location: login.php');
    exit();
}

$currentEmail = $_SESSION['email'];
$memoriesFile = __DIR__ . '/../data/memories.json';
$allMemories = file_exists($memoriesFile) ? json_decode(file_get_contents($memoriesFile), true) : [];
$memoryList = new MemoryList();
foreach ($allMemories as $m) {
    if (($m['owner'] ?? null) === $currentEmail) {
        $memoryList->add($m);
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['download']) && isset($_POST['download_path'])) {
    if (!handleSecureDownload($_POST['download_path'], __DIR__ . '/../')) {
        die("File not found or access denied.");
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_memory']) && isset($_POST['id'])) {
    $deleteId = filter_input(INPUT_POST, 'id', FILTER_SANITIZE_STRING);
    if ($deleteId) {
        $nodeToDelete = $memoryList->find($deleteId);
        $redirectId = null;

        if ($nodeToDelete) {
            if ($nodeToDelete->next) {
                $redirectId = $nodeToDelete->next->data['memory_id'] ?? $nodeToDelete->next->data['id'] ?? null;
            } elseif ($nodeToDelete->prev) {
                $redirectId = $nodeToDelete->prev->data['memory_id'] ?? $nodeToDelete->prev->data['id'] ?? null;
            }
        }
        
        $newMemories = [];
        foreach ($allMemories as $m) {
            if (($m['memory_id'] ?? $m['id'] ?? null) !== $deleteId) {
                $newMemories[] = $m;
            }
        }
        
        file_put_contents($memoriesFile, json_encode($newMemories, JSON_PRETTY_PRINT));

        // Redirect after deletion
        if ($redirectId) {
            header('Location: memory_details.php?id=' . urlencode($redirectId));
        } else {
            header('Location: dashboard.php');
        }
        exit();
    }
}


$memoryId = filter_input(INPUT_GET, 'id', FILTER_SANITIZE_STRING);
if (!$memoryId) {
    echo "Memory ID is missing.";
    exit();
}

$currentNode = $memoryList->find($memoryId);

if (!$currentNode) {
    echo "Memory not found or you do not have permission to view this memory.";
    exit();
}

$currentMemory = $currentNode->data;
$prevMemory = $currentNode->prev ? $currentNode->prev->data : null;
$nextMemory = $currentNode->next ? $currentNode->next->data : null;


$mediaList = [];
if (!empty($currentMemory['media']) && is_array($currentMemory['media'])) {
    $mediaList = $currentMemory['media'];
}

function getInitials($title) {
    $words = preg_split('/\s+/', trim($title));
    $initials = '';
    foreach ($words as $w) {
        if ($w !== '') $initials .= strtoupper($w[0]);
        if (strlen($initials) === 2) break;
    }
    return $initials ?: 'M';
}
$nodeColors = [
    '#2563eb', // blue-600
    '#14b8a6', // teal-500
    '#f59e42', // orange-400
    '#ec4899', // pink-500
    '#22c55e', // green-500
    '#eab308', // yellow-500
    '#ef4444', // red-500
];

$usersData = file_exists(__DIR__ . '/../data/users.json') ? json_decode(file_get_contents(__DIR__ . '/../data/users.json'), true) : [];
$firstLetter = strtoupper($currentEmail[0]);
$userObj = $usersData[$firstLetter][$currentEmail] ?? null;
$userName = $userObj['first_name'] ?? $userObj['username'] ?? $currentEmail;

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title><?= htmlspecialchars($currentMemory['title']) ?> - MemoryBook</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" crossorigin="anonymous" referrerpolicy="no-referrer" />
    <link rel="stylesheet" href="blobs.css" />
    <link rel="stylesheet" href="output.css" />
    <link href="https://fonts.googleapis.com/css2?family=Kalam:wght@400;700&family=Quicksand:wght@400;500;700&display=swap" rel="stylesheet">
  
    <style>
        html, body { font-family: 'Quicksand', sans-serif; }
        .font-kalam { font-family: 'Kalam', cursive !important; }
        .scrollbar-hide::-webkit-scrollbar { display: none; }
        .delete-btn {
            background: #b91c1c;
            color: #fff;
            border: none;
            border-radius: 12px;
            padding: 10px 22px;
            font-size: 1rem;
            font-weight: bold;
            box-shadow: 0 2px 8px rgba(185,28,28,0.15);
            display: flex;
            align-items: center;
            gap: 8px;
            cursor: pointer;
            transition: background 0.2s, box-shadow 0.2s;
        }
        .delete-btn:hover {
            background: #7f1d1d;
            box-shadow: 0 4px 16px rgba(185,28,28,0.25);
        }
    </style>
</head>
<body class="bg-gradient-to-br from-purple-50 via-blue-50 to-pink-50 min-h-screen overflow-x-hidden">
<div class="blobs-bg">
  <div class="blob blob1"></div>
  <div class="blob blob2"></div>
  <div class="blob blob3"></div>
  <div class="blob blob4"></div>
  <div class="blob blob5"></div>
  <div class="blob blob6"></div>
  <div class="blob blob7"></div>
  <div class="blob blob8"></div>
  <div class="blob blob9"></div>
  <div class="blob blob10"></div>
  <div class="blob blob11"></div>
  <div class="blob blob12"></div>
  <div class="blob blob13"></div>
</div>
<header class="bg-white/80 backdrop-blur-md border-b border-[#E8E3F5] sticky top-0 z-50 shadow-sm">
  <nav class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 flex justify-between items-center h-16">
    <a href="dashboard.php" class="text-[#8B7EC8] font-semibold hover:underline">&larr; Back to Dashboard</a>
    <a href="login.php?logout=1" class="text-[#F48498] hover:text-[#8B7EC8] font-medium">Logout</a>
  </nav>
</header>
<div class="flex min-h-screen">
    <aside class="w-24 flex flex-col items-center py-8 px-2 z-10 bg-white/80 rounded-tr-3xl rounded-br-3xl shadow-lg border-r border-[#e0e0f0]">
        <a href="<?= $prevMemory ? 'memory_details.php?id=' . urlencode($prevMemory['memory_id'] ?? $prevMemory['id']) : '#' ?>"
           class="mb-6 flex items-center justify-center w-10 h-10 rounded-full bg-[#8B7EC8] text-white hover:bg-[#6B6B7D] transition <?= $prevMemory ? '' : 'opacity-40 pointer-events-none' ?>"
           style="margin-top: 12px;" title="Previous Memory">
            <i class="fas fa-chevron-up"></i>
        </a>
        <div class="flex flex-col gap-4 items-center max-h-[60vh] overflow-y-auto scrollbar-hide">
            <?php
                $node = $memoryList->getHead();
                $index = 0;
                while ($node):
                    $m = $node->data;
                    $color = $nodeColors[$index % count($nodeColors)];
                    $isActive = ($node === $currentNode);
                    $isNavigable = ($node === $currentNode->prev || $node === $currentNode->next);

                    $href = $isNavigable ? 'memory_details.php?id=' . urlencode($m['memory_id'] ?? $m['id']) : '#';
                    $classes = 'flex items-center justify-center w-16 h-16 rounded-full font-bold text-lg transition-all duration-200';
                    $style = "background: #A8C8EC; color: #fff; border: 4px solid #A8C8EC;";

                    if ($isActive) {
                        $style = "background: {$color}; color: #fff; border: 4px solid #A8C8EC; pointer-events: none; box-shadow: 0 0 0 4px #fff, 0 4px 16px rgba(139,126,200,0.12);";
                    } elseif ($isNavigable) {
                        $classes .= ' hover:scale-105 cursor-pointer';
                    } else {
                        $classes .= ' opacity-40 pointer-events-none cursor-default';
                    }
            ?>
                <a href="<?= $href ?>" class="<?= $classes ?>" style="<?= $style ?>" title="<?= htmlspecialchars($m['title']) ?> (<?= htmlspecialchars($m['date']) ?>)">
                    <?= getInitials($m['title']) ?>
                </a>
            <?php
                    $node = $node->next;
                    $index++;
                endwhile;
            ?>
        </div>
        <a href="<?= $nextMemory ? 'memory_details.php?id=' . urlencode($nextMemory['memory_id'] ?? $nextMemory['id']) : '#' ?>"
           class="mt-6 flex items-center justify-center w-10 h-10 rounded-full bg-[#8B7EC8] text-white hover:bg-[#6B6B7D] transition <?= $nextMemory ? '' : 'opacity-40 pointer-events-none' ?>"
           title="Next Memory">
            <i class="fas fa-chevron-down"></i>
        </a>
    </aside>
    <main class="flex-1 flex flex-col items-center justify-center px-2 py-8">
        <div class="w-full max-w-4xl mx-auto flex flex-col gap-8 items-center">
            <div class="w-full flex flex-col md:flex-row gap-8">
                <section class="flex-1 bg-white/90 rounded-3xl shadow-2xl p-8 flex flex-col gap-4 min-h-[520px]">
                    <div class="flex items-center gap-4 mb-2">
                        <div class="w-16 h-16 rounded-full bg-[#A8C8EC] flex items-center justify-center text-2xl font-bold text-white border-4 border-[#8B7EC8]">
                            <i class="fa-solid fa-book-open"></i>
                        </div>
                        <div>
                            <h1 class="text-3xl font-bold text-gray-900 leading-tight mb-1">
                                <?= htmlspecialchars($currentMemory['title']) ?>
                            </h1>
                            <div class="flex items-center text-gray-500 text-base mb-1">
                                <i class="fa-regular fa-calendar mr-2"></i>
                                <?= htmlspecialchars($currentMemory['date']) ?>
                            </div>
                            <?php if (!empty($currentMemory['mood'])): ?>
                                <div class="flex items-center text-gray-500 text-base mb-1">
                                    <i class="fas fa-smile mr-2"></i> <?= htmlspecialchars($currentMemory['mood']) ?>
                                </div>
                            <?php endif; ?>
                            <?php if (!empty($currentMemory['location'])): ?>
                                <div class="flex items-center text-gray-500 text-base mb-1">
                                    <i class="fa-solid fa-location-dot mr-2"></i>
                                    <a href="https://www.google.com/maps/search/<?= urlencode($currentMemory['location']) ?>" target="_blank" rel="noopener noreferrer" class="text-[#8B7EC8] hover:underline">
                                        <?= htmlspecialchars($currentMemory['location']) ?>
                                    </a>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                    <div class="flex justify-end mb-2">
                        <?php if (($currentMemory['owner'] ?? null) === $currentEmail): ?>
                        <form method="post" action="" onsubmit="return confirm('Are you sure you want to delete this memory? This cannot be undone.');">
                            <input type="hidden" name="id" value="<?= htmlspecialchars($currentMemory['memory_id']) ?>" />
                            <button type="submit" name="delete_memory" class="delete-btn" value="delete">
                                <i class="fas fa-trash"></i> Delete Memory
                            </button>
                        </form>
                        <?php endif; ?>
                    </div>
                    <div class="mb-2">
                        <span class="font-semibold text-gray-700">Description</span>
                        <div class="text-gray-700 mt-1 text-base leading-relaxed bg-[#F5F3FB] rounded-xl p-4 border border-[#E8E3F5]">
                            <?= nl2br(htmlspecialchars($currentMemory['description'] ?? $currentMemory['message'] ?? '')) ?>
                        </div>
                    </div>
                    <?php if (!empty($currentMemory['tags'])): ?>
                        <div class="flex flex-wrap gap-2 mb-2">
                            <?php foreach (is_array($currentMemory['tags']) ? $currentMemory['tags'] : [$currentMemory['tags']] as $tag): ?>
                                <span class="inline-block px-3 py-1 rounded-full bg-[#A8C8EC] text-white text-xs font-medium">#<?= htmlspecialchars($tag) ?></span>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                    <?php
                    $friendList = [];
                    if (!empty($currentMemory['friends'])) {
                        foreach ((array)$currentMemory['friends'] as $friendEmail) {
                            $letter = strtoupper($friendEmail[0]);
                            $user = $usersData[$letter][$friendEmail] ?? null;
                            $name = $user['first_name'] ?? $user['username'] ?? $friendEmail;
                            $friendList[] = htmlspecialchars($name);
                        }
                    }
                    ?>
                    <div class="text-gray-600 mb-2"><strong>Friends included:</strong> <?= !empty($friendList) ? implode(', ', $friendList) : 'No friends included' ?></div>
                    <div class="text-gray-600 mb-2"><strong>Created by:</strong> <?= htmlspecialchars($userName) ?></div>
                </section>
                <section class="flex-1 bg-white rounded-3xl shadow-lg p-8 flex flex-col gap-6 min-h-[520px]">
                    <h2 class="text-xl font-semibold text-[#8B7EC8] mb-2 flex items-center gap-2"><i class="fa-regular fa-images"></i> Memory Media</h2>
                    <div class="w-full flex flex-col items-center mb-6">
                        <?php if (!empty($mediaList)): ?>
                            <div class="media-gallery w-full flex flex-col items-center" id="media-gallery-<?= htmlspecialchars($currentMemory['memory_id']) ?>">
                                <?php foreach ($mediaList as $idx => $file): ?>
                                    <div class="media-slide" data-index="<?= $idx ?>" style="<?= $idx === 0 ? '' : 'display:none;' ?>">
                                    <?php
                                        $type = $file['type'] ?? 'other';
                                        if (strpos($type, 'image/') === 0): ?>
                                            <img src="../<?= htmlspecialchars($file['url']) ?>" alt="Memory Image" class="gallery-img w-full max-w-lg h-auto max-h-[400px] object-contain rounded-xl shadow border-2 border-[#8B7EC8] bg-white mb-2" />
                                        <?php elseif (strpos($type, 'video/') === 0): ?>
                                            <video src="../<?= htmlspecialchars($file['url']) ?>" controls class="w-full max-w-lg h-auto max-h-[400px] rounded-xl shadow border-2 border-[#8B7EC8] bg-black mb-2"></video>
                                        <?php elseif (strpos($type, 'audio/') === 0): ?>
                                            <audio src="../<?= htmlspecialchars($file['url']) ?>" controls class="w-full max-w-lg mb-2"></audio>
                                    <?php else: ?>
                                            <div class="w-full flex flex-col items-center justify-center h-32 bg-[#F5F3FB] rounded-xl border border-[#E8E3F5] mb-2">
                                            <i class="fa fa-file-alt text-4xl text-[#8B7EC8]"></i>
                                                <span class="ml-2 text-sm"><?= htmlspecialchars($file['name'] ?? basename($file['url'])) ?></span>
                                        </div>
                                    <?php endif; ?>
                                        <?php
                                        $downloadPath = $file['url'];
                                        if (strpos($downloadPath, '../') === 0) {
                                            $downloadPath = substr($downloadPath, 3);
                                        }
                                        $downloadPath = ltrim($downloadPath, '/');
                                        $formId = 'downloadForm_' . $idx;
                                        ?>
                                        <form id="<?= $formId ?>" method="post" action="" style="display:none;">
                                            <input type="hidden" name="download" value="1">
                                            <input type="hidden" name="download_path" value="<?= htmlspecialchars($downloadPath) ?>">
                                        </form>
                                        <button type="button" onclick="document.getElementById('<?= $formId ?>').submit();" class="download-btn px-3 py-1 bg-[#8B7EC8] text-white rounded-md text-xs font-semibold hover:bg-[#6BCB77] transition flex items-center gap-2">
                                            <i class="fa fa-download"></i> Download
                                        </button>
                                    </div>
                                <?php endforeach; ?>
                                <div class="flex gap-4 mt-4">
                                    <button class="prev-btn px-3 py-1 bg-[#ede9fe] text-[#7c3aed] rounded-lg font-semibold"><i class="fa fa-chevron-left"></i> Prev</button>
                                    <button class="next-btn px-3 py-1 bg-[#ede9fe] text-[#7c3aed] rounded-lg font-semibold">Next <i class="fa fa-chevron-right"></i></button>
                                </div>
                            </div>
                        <?php else: ?>
                            <div class="text-center text-gray-400">No media files.</div>
                        <?php endif; ?>
                    </div>
                </section>
            </div>
            <section class="w-full bg-white/80 rounded-2xl shadow-lg p-6 mt-6">
                <h2 class="text-lg font-semibold text-[#8B7EC8] mb-2 flex items-center gap-2"><i class="fa fa-info-circle"></i> Memory Details</h2>
                <dl class="divide-y divide-[#E8E3F5]">
                    <?php
                    $skipFields = ['memory_id', 'owner', 'media'];
                    foreach ($currentMemory as $key => $value) {
                        if (in_array($key, $skipFields)) continue;
                        $displayValue = '';
                        if (is_array($value)) {
                            $displayValue = implode(', ', $value);
                        } else {
                            $displayValue = ($value === '' || $value === null) ? '—' : $value;
                        }
                        echo '<div class="flex justify-between items-center py-2">';
                        echo '<dt class="font-medium text-gray-600 capitalize">' . htmlspecialchars(str_replace('_',' ', $key)) . '</dt>';
                        echo '<dd class="text-gray-800 text-right">' . htmlspecialchars($displayValue) . '</dd>';
                        echo '</div>';
                    }
                    ?>
                </dl>
            </section>
            <div class="mt-4">
                <a href="dashboard.php" class="inline-block px-6 py-2 bg-[#8B7EC8] text-white rounded-xl hover:bg-[#6BCB77] transition">Back to Dashboard</a>
            </div>
        </div>
    </main>
</div>
<script>
document.addEventListener('DOMContentLoaded', function() {
    var gallery = document.getElementById('media-gallery-<?= htmlspecialchars($currentMemory['memory_id']) ?>');
    if (!gallery) return;
    var slides = gallery.querySelectorAll('.media-slide');
    var current = 0;
    function showSlide(idx) {
        slides.forEach(function(slide, i) {
            slide.style.display = (i === idx) ? '' : 'none';
            if (i !== idx) {
                var vid = slide.querySelector('video');
                if (vid) vid.pause();
                var aud = slide.querySelector('audio');
                if (aud) aud.pause();
            }
        });
    }
    var prevBtn = gallery.querySelector('.prev-btn');
    var nextBtn = gallery.querySelector('.next-btn');
    if (prevBtn) {
        prevBtn.onclick = function() {
            current = (current - 1 + slides.length) % slides.length;
            showSlide(current);
        };
    }
    if (nextBtn) {
        nextBtn.onclick = function() {
            current = (current + 1) % slides.length;
            showSlide(current);
        };
    }
    showSlide(current);
});
</script>
</body>
</html>
