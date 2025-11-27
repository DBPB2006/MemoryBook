<?php
session_start();
require_once __DIR__ . '/../data_structures/common_functions.php';
require_once __DIR__ . '/../data_structures/linkedlist.php';

// Redirects to login if not logged in
if (!isset($_SESSION['email'])) {
    header('Location: login.php');
    exit();
}

// Logout logic
$logout = false;
if (isset($_POST['logout'])) {
    $logout = ($_POST['logout'] === '1');
}
if ($logout) {
    session_unset();
    session_destroy();
    header('Location: login.php');
    exit();
}

// User info 
$currentEmail = $_SESSION['email'];
// Loads users data for name lookup
$usersData = file_exists(__DIR__ . '/../data/users.json') ? json_decode(file_get_contents(__DIR__ . '/../data/users.json'), true) : [];
$firstLetter = strtoupper($currentEmail[0]);
$userObj = $usersData[$firstLetter][$currentEmail] ?? null;
$userName = $userObj['first_name'] ?? $userObj['username'] ?? $currentEmail;

// Load user memories 
$memoriesFile = __DIR__ . '/../data/memories.json';
$memories = file_exists($memoriesFile) ? json_decode(file_get_contents($memoriesFile), true) : [];
$memoryList = new MemoryList();
foreach ($memories as $m) {
    $email = $m['owner'] ?? null;
    if ($email === $currentEmail) {
        $memoryList->add($m);
    }
}
$userMemories = $memoryList->toArray();

// Shared memory popup logic (manual filter)
$sharedMemoriesFile = __DIR__ . '/../data/shared_memories.json';
$allShared = file_exists($sharedMemoriesFile) ? json_decode(file_get_contents($sharedMemoriesFile), true) : [];
$hasUnseenSharedMemory = false;
$newestUnseenMemoryId = null;
$receivedSharedMemories = [];
foreach ($allShared as $m) {
    if (isset($m['to']) && $m['to'] === $currentEmail) {
        $receivedSharedMemories[] = $m;
    }
}
$unseenReceived = [];
foreach ($receivedSharedMemories as $m) {
    if (empty($m['seen']) || $m['seen'] === false) {
        $unseenReceived[] = $m;
    }
}
if (!empty($unseenReceived)) {
    // Manual sort by date descending
    for ($i = 0; $i < count($unseenReceived) - 1; $i++) {
        for ($j = $i + 1; $j < count($unseenReceived); $j++) {
            $dateA = isset($unseenReceived[$i]['date']) ? strtotime($unseenReceived[$i]['date']) : 0;
            $dateB = isset($unseenReceived[$j]['date']) ? strtotime($unseenReceived[$j]['date']) : 0;
            if ($dateB > $dateA) {
                $tmp = $unseenReceived[$i];
                $unseenReceived[$i] = $unseenReceived[$j];
                $unseenReceived[$j] = $tmp;
            }
        }
    }
    $hasUnseenSharedMemory = true;
    $newestUnseenMemoryId = isset($unseenReceived[0]['memory_id']) ? $unseenReceived[0]['memory_id'] : (isset($unseenReceived[0]['original_memory_id']) ? $unseenReceived[0]['original_memory_id'] : null);
}
// Only show sent shared memories in the list below (manual filter)
$sentSharedMemories = [];
foreach ($allShared as $m) {
    if (isset($m['from']) && $m['from'] === $currentEmail) {
        $sentSharedMemories[] = $m;
    }
}
// Manual sort by date descending
for ($i = 0; $i < count($sentSharedMemories) - 1; $i++) {
    for ($j = $i + 1; $j < count($sentSharedMemories); $j++) {
        $dateA = isset($sentSharedMemories[$i]['date']) ? strtotime($sentSharedMemories[$i]['date']) : 0;
        $dateB = isset($sentSharedMemories[$j]['date']) ? strtotime($sentSharedMemories[$j]['date']) : 0;
        if ($dateB > $dateA) {
            $tmp = $sentSharedMemories[$i];
            $sentSharedMemories[$i] = $sentSharedMemories[$j];
            $sentSharedMemories[$j] = $tmp;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>MemoryBook - Home</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" crossorigin="anonymous" referrerpolicy="no-referrer" />
    <link rel="stylesheet" href="output.css" />
    <link href="https://fonts.googleapis.com/css2?family=Kalam:wght@400;700&family=Quicksand:wght@400;500;700&display=swap" rel="stylesheet">
    <style>
        html, body { font-family: 'Quicksand', sans-serif; }
        .font-kalam { font-family: 'Kalam', cursive; }
        #userAvatar:focus { outline: 2px solid #8B7EC8; outline-offset: 2px; }
    </style>
</head>
<body class="min-h-screen">
<link rel="stylesheet" href="blobs.css">
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
<?php include 'navbar.php'; ?>
<?php if ($hasUnseenSharedMemory): ?>
<!-- Shared Memory Popup Notification -->
<div id="sharedMemoryPopup" class="fixed inset-0 flex items-center justify-center z-50 bg-black/30">
    <div class="bg-white rounded-2xl shadow-lg p-8 max-w-md w-full flex flex-col items-center border-2 border-[#8B7EC8] animate-bounce-in">
        <div class="text-4xl mb-2 text-[#8B7EC8]"><i class="fas fa-envelope-open-text"></i></div>
        <h2 class="text-xl font-bold text-[#8B7EC8] mb-2">📬 You’ve got a new memory!</h2>
        <p class="text-[#2D2A3D] mb-4 text-center">A friend has shared a special memory with you. Click below to view it.</p>
        <a href="view_shared_memory.php?id=<?= urlencode($newestUnseenMemoryId) ?>" class="px-6 py-3 rounded-xl bg-[#8B7EC8] text-white font-semibold shadow hover:bg-[#7A6BB5] transition text-lg flex items-center gap-2"><i class="fas fa-eye"></i> View Now</a>
    </div>
</div>
<script>
window.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') {
        document.getElementById('sharedMemoryPopup').style.display = 'none';
    }
});
document.getElementById('sharedMemoryPopup').addEventListener('click', function(e) {
    if (e.target === this) {
        this.style.display = 'none';
    }
});
</script>
<?php endif; ?>
    <!-- Hero Section -->
    <section class="relative overflow-hidden py-20 lg:py-32">
        <div class="relative max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="grid lg:grid-cols-2 gap-12 items-center">
                <div class="text-center lg:text-left">
                    <h1 class="text-4xl sm:text-5xl lg:text-6xl font-semibold leading-tight mb-6 text-[#2D2A3D]">
                        Welcome back, <span class="bg-gradient-to-r from-[#8B7EC8] via-[#A8C8EC] to-[#F4A6A6] bg-clip-text text-transparent"><?php echo htmlspecialchars($userName); ?></span>
                    </h1>
                    <p class="text-lg sm:text-xl mb-8 max-w-2xl mx-auto lg:mx-0 text-[#6B6B7D]">
                        Your digital home for preserving friendships and memories. Dive in and start exploring your scrapbook!
                    </p>
                    <div class="flex flex-col sm:flex-row gap-4 justify-center lg:justify-start">
                        <a href="dashboard.php" class="px-8 py-4 rounded-xl font-medium hover:scale-105 transition-transform shadow-lg text-white bg-[#8B7EC8] hover:bg-[#7A6BB5]">
                            Go to Dashboard
                        </a>
                    </div>
                </div>
                <div class="relative">
                    <img src="https://images.unsplash.com/photo-1511988617509-a57c8a288659?q=80&w=2832&auto=format&fit=crop&ixlib=rb-4.0.3&ixid=M3wxMjA3fDB8MHxwaG90by1wYWdlfHx8fGVufDB8fHx8fA%3D%3D" alt="Friends sharing memories and photos together" class="w-full h-96 lg:h-[500px] object-cover rounded-2xl shadow-xl hover:scale-105 transition-transform" />
                </div>
            </div>
        </div>
    </section>
    <section class="relative py-20 bg-[#FDE8E8]/60">
        <div class="relative max-w-4xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="text-center mb-12">
                <h2 class="text-3xl sm:text-4xl font-semibold mb-4 text-[#2D2A3D] flex items-center justify-center gap-3">
                    <i class="fas fa-images text-[#F4A6A6]"></i> Shared Memories <span class="text-lg font-normal text-[#F4A6A6]">(<?php echo count($sentSharedMemories); ?>)</span>
                </h2>
                <p class="text-lg max-w-2xl mx-auto text-[#6B6B7D]">
                    See what you and your friends have shared.
                </p>
            </div>
            <!-- Shared Memories List (Dynamic) -->
            <section class="relative py-10 bg-[#F5F3FB]/60">
              <div class="max-w-2xl mx-auto px-4 sm:px-6 lg:px-8">
                <h2 class="text-2xl font-bold text-[#8B7EC8] mb-6 flex items-center gap-2"><i class="fas fa-images"></i> Shared Memories</h2>
                <?php if (empty($sentSharedMemories)): ?>
                  <div class="text-gray-400 text-center">No shared memories yet. Memories you share with friends will appear here.</div>
                <?php else: ?>
                  <ul class="space-y-4">
                    <?php foreach ($sentSharedMemories as $mem): ?>
                      <li class="flex items-center gap-3 p-4 rounded-xl bg-white shadow hover:bg-gray-50 transition border border-[#e0e0f0]">
                        <div class="flex-1">
                          <a href="view_shared_memory.php?id=<?= urlencode($mem['memory_id']) ?>" class="font-semibold text-[#8B7EC8] hover:underline text-base">
                            <?= htmlspecialchars($mem['to_username'] ?? $mem['to']) ?>
                          </a>
                          <div class="text-sm text-gray-900 font-medium truncate">
                            <?= htmlspecialchars($mem['memory_title'] ?? '(No Title)') ?>
                          </div>
                          <div class="text-xs text-gray-400 mb-1">
                            <?= htmlspecialchars($mem['date'] ?? '') ?>
                          </div>
                          <div class="text-xs text-gray-600 line-clamp-2" style="max-width:220px;overflow:hidden;white-space:nowrap;text-overflow:ellipsis;">
                            <?= htmlspecialchars(mb_substr($mem['message'] ?? '', 0, 60)) ?><?= strlen($mem['message'] ?? '') > 60 ? '...' : '' ?>
                          </div>
                        </div>
                      </li>
                    <?php endforeach; ?>
                  </ul>
                <?php endif; ?>
              </div>
            </section>
        </div>
    </section>
    <section class="relative py-20 bg-white/30">
      <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
      
        <div class="mb-16">
          <div class="text-center mb-8 flex flex-col items-center">
            <span class="w-16 h-16 flex items-center justify-center rounded-full mb-4 bg-[#E6F1FB]">
              <i class="fas fa-brain text-4xl text-[#8B7EC8]"></i>
            </span>
            <h2 class="text-3xl sm:text-4xl font-bold mb-4 text-[#2D2A3D]">
              Explore Your Memories
            </h2>
            <p class="text-lg text-[#6B6B7D] max-w-2xl mx-auto">
              Capture, organize, and relive your precious moments with powerful memory tools.
            </p>
          </div>
          <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-2 gap-8">
            <?php if (!empty($userMemories)):
              $firstMemory = $userMemories[0];
            ?>
            <a href="memory_details.php?id=<?= urlencode($firstMemory['memory_id']) ?>" class="bg-white rounded-2xl p-8 shadow-md hover:shadow-lg transition group flex flex-col items-center text-center">
              <div class="w-16 h-16 rounded-xl flex items-center justify-center mb-6 bg-[#E6F1FB] group-hover:scale-110 transition-transform">
                <i class="fas fa-book-open text-2xl text-[#8B7EC8]"></i>
              </div>
              <h3 class="text-lg font-semibold mb-2 text-gray-700">View Memories</h3>
              <p class="text-gray-500 text-sm mb-2 line-clamp-2">Feel nostalgic</p>
              <span class="inline-block px-4 py-2 rounded-xl bg-[#A8C8EC] text-white text-sm font-medium shadow hover:bg-[#8B7EC8] transition">Go to Memory</span>
            </a>
            <?php endif; ?>
            <a href="add_memory.php" class="bg-white rounded-2xl p-8 shadow-md hover:shadow-lg transition group flex flex-col items-center text-center">
              <div class="w-16 h-16 rounded-xl flex items-center justify-center mb-6 bg-[#E6F1FB] group-hover:scale-110 transition-transform">
                <i class="fas fa-plus text-2xl text-[#A8C8EC]"></i>
              </div>
              <h3 class="text-lg font-semibold mb-2 text-gray-700">Create a Memory</h3>
              <p class="text-gray-500 text-sm mb-4">Save your favorite moments instantly, with photos and notes.</p>
              <span class="inline-block px-4 py-2 rounded-xl bg-[#A8C8EC] text-white text-sm font-medium shadow hover:bg-[#8B7EC8] transition">Add New</span>
            </a>
            <a href="dashboard.php" class="bg-white rounded-2xl p-8 shadow-md hover:shadow-lg transition group flex flex-col items-center text-center">
              <div class="w-16 h-16 rounded-xl flex items-center justify-center mb-6 bg-[#F5F3FB] group-hover:scale-110 transition-transform">
                <i class="fas fa-book-open text-2xl text-[#D1C7EB]"></i>
              </div>
              <h3 class="text-lg font-semibold mb-2 text-[#5B5B6B]">Browse Timeline</h3>
              <p class="text-[#8B8BA3] text-sm mb-4">Flip through your digital scrapbook and relive every story.</p>
              <span class="inline-block px-4 py-2 rounded-xl bg-[#D1C7EB] text-[#2D2A3D] text-sm font-medium shadow hover:bg-[#8B7EC8] hover:text-white transition">View All</span>
            </a>
            <a href="shared_memories.php" class="bg-white rounded-2xl p-8 shadow-md hover:shadow-lg transition group flex flex-col items-center text-center">
              <div class="relative w-16 h-16 rounded-xl flex items-center justify-center mb-6 bg-[#F4A6A6] group-hover:scale-110 transition-transform">
                <i class="fas fa-images text-2xl text-white"></i>
                <?php if (count($sentSharedMemories) > 0): ?>
                  <span class="absolute -top-2 -right-2 bg-[#F4A6A6] text-white text-xs font-bold rounded-full px-2 py-0.5 shadow">
                    <?php echo count($sentSharedMemories); ?>
                  </span>
                <?php endif; ?>
              </div>
              <h3 class="text-lg font-semibold mb-2 text-[#5B5B6B]">Share with Friends</h3>
              <p class="text-[#8B8BA3] text-sm mb-4">Exchange special moments with friends and see what they share.</p>
              <span class="inline-block px-4 py-2 rounded-xl bg-[#F4A6A6] text-white text-sm font-medium shadow hover:bg-[#D1C7EB] transition">View Shared</span>
            </a>
            <a href="memory_capsules.php" class="bg-white rounded-2xl p-8 shadow-md hover:shadow-lg transition group flex flex-col items-center text-center">
              <div class="w-16 h-16 rounded-xl flex items-center justify-center mb-6 bg-[#FFF7E6] group-hover:scale-110 transition-transform">
                <i class="fas fa-lock text-2xl text-[#D1C7EB]"></i>
              </div>
              <h3 class="text-lg font-semibold mb-2 text-[#5B5B6B]">Send a Time Capsule</h3>
              <p class="text-[#8B8BA3] text-sm mb-4">Send a message to your future self or friends—open it later!</p>
              <span class="inline-block px-4 py-2 rounded-xl bg-[#D1C7EB] text-[#2D2A3D] text-sm font-medium shadow hover:bg-[#8B7EC8] hover:text-white transition">Open Capsule</span>
            </a>
          </div>
        </div>
        <div class="mb-16">
          <div class="text-center mb-8 flex flex-col items-center">
            <span class="w-16 h-16 flex items-center justify-center rounded-full mb-4 bg-[#FDE8E8]">
              <i class="fas fa-user-friends text-4xl text-[#F4A6A6]"></i>
            </span>
            <h2 class="text-3xl sm:text-4xl font-bold mb-4 text-[#2D2A3D]">
              Your Friend Network
            </h2>
            <p class="text-lg text-[#6B6B7D] max-w-2xl mx-auto">
              Connect with friends and visualize your relationships through interactive tools.
            </p>
          </div>
          <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-8">
            <a href="view_friends.php" class="bg-white rounded-2xl p-8 shadow-md hover:shadow-lg transition group flex flex-col items-center text-center">
              <div class="w-16 h-16 rounded-xl flex items-center justify-center mb-6 bg-[#FDE8E8] group-hover:scale-110 transition-transform">
                <i class="fas fa-users text-2xl text-[#F4A6A6]"></i>
              </div>
              <h3 class="text-lg font-semibold mb-2 text-[#5B5B6B]">Manage Friends</h3>
              <p class="text-[#8B8BA3] text-sm mb-4">Grow your circle and keep in touch with everyone who matters.</p>
              <span class="inline-block px-4 py-2 rounded-xl bg-[#F4A6A6] text-white text-sm font-medium shadow hover:bg-[#D1C7EB] transition">View Friends</span>
            </a>
            <a href="friendship_map_of_memories.php?view=network" class="bg-white rounded-2xl p-8 shadow-md hover:shadow-lg transition group flex flex-col items-center text-center">
              <div class="w-16 h-16 rounded-xl flex items-center justify-center mb-6 bg-[#E6F1FB] group-hover:scale-110 transition-transform">
                <i class="fas fa-project-diagram text-2xl text-[#A8C8EC]"></i>
              </div>
              <h3 class="text-lg font-semibold mb-2 text-[#5B5B6B]">Network of Friends</h3>
              <p class="text-[#8B8BA3] text-sm mb-4">See your friendship network as an interactive graph.</p>
              <span class="inline-block px-4 py-2 rounded-xl bg-[#A8C8EC] text-white text-sm font-medium shadow hover:bg-[#8B7EC8] transition">See Network</span>
            </a>
            <a href="friendship_map_of_memories.php?view=map" class="bg-white rounded-2xl p-8 shadow-md hover:shadow-lg transition group flex flex-col items-center text-center">
              <div class="w-16 h-16 rounded-xl flex items-center justify-center mb-6 bg-[#F5F3FB] group-hover:scale-110 transition-transform">
                <i class="fas fa-map-marked-alt text-2xl text-[#8B7EC8]"></i>
              </div>
              <h3 class="text-lg font-semibold mb-2 text-[#5B5B6B]">Map Your Memories</h3>
              <p class="text-[#8B8BA3] text-sm mb-4">Explore your memories and friendships on a vibrant map.</p>
              <span class="inline-block px-4 py-2 rounded-xl bg-[#8B7EC8] text-white text-sm font-medium shadow hover:bg-[#7A6BB5] transition">Open Map</span>
            </a>
          </div>
        </div>
        <div class="mb-16">
          <div class="text-center mb-8 flex flex-col items-center">
            <span class="w-16 h-16 flex items-center justify-center rounded-full mb-4 bg-[#FFF7E6]">
              <i class="fas fa-paint-brush text-4xl text-[#D1C7EB]"></i>
            </span>
            <h2 class="text-3xl sm:text-4xl font-bold mb-4 text-[#2D2A3D]">
            Design Your Space
            </h2>
            <p class="text-lg text-[#6B6B7D] max-w-2xl mx-auto">
              Express yourself and organize your memories with creative visualization tools.
            </p>
          </div>
          <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-8">
            <a href="mood_board.php" class="bg-white rounded-2xl p-8 shadow-md hover:shadow-lg transition group flex flex-col items-center text-center">
              <div class="w-16 h-16 rounded-xl flex items-center justify-center mb-6 bg-[#E6F1FB] group-hover:scale-110 transition-transform">
                <i class="fas fa-palette text-2xl text-[#A8C8EC]"></i>
              </div>
              <h3 class="text-lg font-semibold mb-2 text-[#5B5B6B]">Design Mood Board</h3>
              <p class="text-[#8B8BA3] text-sm mb-4">Design your own mood boards to reflect every season of life.</p>
              <span class="inline-block px-4 py-2 rounded-xl bg-[#A8C8EC] text-white text-sm font-medium shadow hover:bg-[#8B7EC8] transition">Create Board</span>
            </a>
            <a href="dashboard.php" class="bg-white rounded-2xl p-8 shadow-md hover:shadow-lg transition group flex flex-col items-center text-center">
              <div class="w-16 h-16 rounded-xl flex items-center justify-center mb-6 bg-[#F5F3FB] group-hover:scale-110 transition-transform">
                <i class="fas fa-th-large text-2xl text-[#8B7EC8]"></i>
              </div>
              <h3 class="text-lg font-semibold mb-2 text-[#5B5B6B]">Your Dashboard</h3>
              <p class="text-[#8B8BA3] text-sm mb-4">See your memory stats and quick actions in one place.</p>
              <span class="inline-block px-4 py-2 rounded-xl bg-[#8B7EC8] text-white text-sm font-medium shadow hover:bg-[#7A6BB5] transition">Go to Hub</span>
            </a>
            <a href="profile.php" class="bg-white rounded-2xl p-8 shadow-md hover:shadow-lg transition group flex flex-col items-center text-center">
              <div class="w-16 h-16 rounded-xl flex items-center justify-center mb-6 bg-[#FDE8E8] group-hover:scale-110 transition-transform">
                <i class="fas fa-user text-2xl text-[#F4A6A6]"></i>
              </div>
              <h3 class="text-lg font-semibold mb-2 text-[#5B5B6B]">Edit Profile</h3>
              <p class="text-[#8B8BA3] text-sm mb-4">Personalize your account and update your details anytime.</p>
              <span class="inline-block px-4 py-2 rounded-xl bg-[#F4A6A6] text-white text-sm font-medium shadow hover:bg-[#D1C7EB] transition">Edit Profile</span>
            </a>
          </div>
        </div>
      </div>
    </section>
    <footer class="text-white py-12" style="background-color: #2D2A3D;">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="text-center">
                <div class="flex items-center justify-center space-x-2 mb-4">
                    <i class="fas fa-heart text-2xl" style="color: #8B7EC8;"></i>
                        <span class="text-xl font-semibold">MemoryBook</span>
                    </div>
                <p class="text-gray-300 mb-4 max-w-md mx-auto">
                        Preserve your friendships and memories in a beautiful, interactive digital scrapbook that grows with your relationships.
                </p>
                <div class="border-t border-gray-700 pt-8">
                    <p class="text-gray-300">
                        © 2025 MemoryBook. All Rights Reserved. Made with ❤️ for preserving friendships.
                    </p>
                </div>
            </div>
        </div>
    </footer>
</body>
</html>
