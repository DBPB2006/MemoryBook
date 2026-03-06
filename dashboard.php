<?php
session_start();
require_once __DIR__ . '/../data_structures/common_functions.php';
require_once __DIR__ . '/../data_structures/linkedlist.php';

if (!isset($_SESSION['email'])) {
    header('Location: login.php');
    exit();
}
if (isset($_POST['logout']) && $_POST['logout'] === '1') {
    session_unset();
    session_destroy();
    header('Location: login.php');
    exit();
}

$users = loadUsers();
$currentEmail = $_SESSION['email'];
$currentUser = getCurrentUser($users, $currentEmail);
// Use MemoryList for all memory operations
$memories = loadMemories();
$memoryList = new MemoryList();
foreach ($memories as $memory) {
    if (isset($memory['owner']) && $memory['owner'] === $currentEmail) {
        $memoryList->add($memory);
    }
}
$userMemories = $memoryList->toArray();
$memoryCount = $memoryList->count();
$friendsCount = isset($currentUser['friends']) ? count($currentUser['friends']) : 0;
$userName = isset($currentUser['name']) ? $currentUser['name'] : $currentEmail;
$firstName = '';
for ($i = 0; $i < strlen($userName); $i++) {
    if ($userName[$i] === ' ' || $userName[$i] === '@') break;
    $firstName .= $userName[$i];
}
if (strlen($firstName) > 0) {
    $firstName = strtoupper($firstName[0]) . substr($firstName, 1);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Dashboard - MemoryBook</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" crossorigin="anonymous" referrerpolicy="no-referrer" />
    <link rel="stylesheet" href="blobs.css" />
    <link rel="stylesheet" href="output.css" />
    <link href="https://fonts.googleapis.com/css2?family=Kalam:wght@400;700&family=Quicksand:wght@400;500;700&display=swap" rel="stylesheet">
    <style>
        html, body { font-family: 'Quicksand', sans-serif; }
        .font-kalam { font-family: 'Kalam', cursive !important; }
        .scrollbar-hide::-webkit-scrollbar { display: none; }
    </style>
</head>
<body class="bg-gradient-to-br from-purple-50 via-blue-50 to-pink-50 min-h-screen">
<div class="blobs-bg">
    <div class="blob blob1"></div><div class="blob blob2"></div><div class="blob blob3"></div><div class="blob blob4"></div><div class="blob blob5"></div><div class="blob blob6"></div><div class="blob blob7"></div><div class="blob blob8"></div><div class="blob blob9"></div><div class="blob blob10"></div><div class="blob blob11"></div><div class="blob blob12"></div><div class="blob blob13"></div>
</div>
        <?php include 'navbar.php'; ?>
        <main class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
            <div class="mb-8">
            <h1 class="text-3xl sm:text-4xl font-semibold text-[#2D2A3D] mb-2">
                Welcome back, <span id="welcomeName"><?php echo htmlspecialchars($firstName); ?></span>! 👋
            </h1>
        <p class="text-lg text-[#6B6B7D]">Here's what's happening in your friendship network today</p>
        </div>
        <div class="grid lg:grid-cols-12 gap-8">
            <div class="lg:col-span-3">
                <div class="grid grid-cols-1 sm:grid-cols-3 lg:grid-cols-1 gap-4 mb-8 enhanced-backdrop p-6">
                <div class="bg-[#ede9fa] rounded-2xl p-6 shadow transition-all duration-300 hover:scale-105">
                        <div class="flex items-center justify-between mb-4">
                            <div class="w-12 h-12 bg-[#E8E3F5] rounded-xl flex items-center justify-center">
                                <i class="fas fa-users w-6 h-6 text-[#8B7EC8]"></i>
                            </div>
                            <span class="text-2xl font-semibold text-[#2D2A3D]"><?php echo $friendsCount; ?></span>
                        </div>
                        <h3 class="font-medium text-[#2D2A3D] mb-1">Total Friends</h3>
                        <p class="text-sm text-[#6B6B7D]">+<?php echo $friendsCount; ?> total</p>
                    </div>
                <div class="bg-[#d1c7eb] rounded-2xl p-6 shadow transition-all duration-300 hover:scale-105">
                        <div class="flex items-center justify-between mb-4">
                            <div class="w-12 h-12 bg-[#E6F1FB] rounded-xl flex items-center justify-center">
                                <i class="fas fa-heart w-6 h-6 text-[#A8C8EC]"></i>
                            </div>
                            <span class="text-2xl font-semibold text-[#2D2A3D]"><?php echo $memoryCount; ?></span>
                        </div>
                        <h3 class="font-medium text-[#2D2A3D] mb-1">Memories</h3>
                        <p class="text-sm text-[#6B6B7D]">+<?php echo $memoryCount; ?> total</p>
                    </div>
                </div>
            <div class="lg:hidden mb-8 enhanced-backdrop p-4 bg-[#d1c7eb]">
                    <h2 class="text-lg font-semibold text-[#2D2A3D] mb-3">Quick Actions</h2>
                    <div class="grid grid-cols-1 sm:grid-cols-3 gap-2">
                    <a href="add_friend.php" class="bg-[#8B7EC8] text-white p-4 rounded-xl hover:bg-[#7A6BB5] transition-all duration-300 text-center">
                            <i class="fas fa-user-plus w-7 h-7 mb-2"></i>
                            <span class="text-sm font-medium">Add Friend</span>
                        </a>
                    <a href="add_memory.php" class="bg-[#A8C8EC] text-white p-4 rounded-xl hover:bg-[#8BB4E0] transition-all duration-300 text-center">
                            <i class="fas fa-plus w-7 h-7 mb-2"></i>
                            <span class="text-sm font-medium">Create Memory</span>
                        </a>
                    <a href="friendship_map_of_memories.php?view=network" class="bg-[#E6F1FB] text-[#8B7EC8] p-4 rounded-xl hover:bg-[#D1C7EB] transition-all duration-300 text-center">
                            <i class="fas fa-project-diagram w-7 h-7 mb-2"></i>
                            <span class="text-sm font-medium">Network of Friends</span>
                        </a>
                    <a href="friendship_map_of_memories.php?view=map" class="bg-[#F4A6A6] text-white p-4 rounded-xl hover:bg-[#F18C8C] transition-all duration-300 text-center">
                            <i class="fas fa-map-marked-alt w-7 h-7 mb-2"></i>
                            <span class="text-sm font-medium">Map Your Memories</span>
                        </a>
                    </div>
                </div>
            </div>
            <div class="lg:col-span-6">
                <div class="hidden lg:block mb-8 enhanced-backdrop p-4 bg-[#ede9fa]">
                    <h2 class="text-lg font-semibold text-[#2D2A3D] mb-3">Quick Actions</h2>
                    <div class="grid grid-cols-4 gap-2">
                        <a href="add_friend.php" class="bg-[#8B7EC8] text-white p-4 rounded-xl hover:bg-[#7A6BB5] transition-all duration-300 text-center">
                            <i class="fas fa-user-plus w-7 h-7 mb-2"></i>
                            <span class="text-sm font-medium">Add Friend</span>
                        </a>
                        <a href="add_memory.php" class="bg-[#A8C8EC] text-white p-4 rounded-xl hover:bg-[#8BB4E0] transition-all duration-300 text-center">
                            <i class="fas fa-plus w-7 h-7 mb-2"></i>
                            <span class="text-sm font-medium">Create Memory</span>
                        </a>
                        <a href="friendship_map_of_memories.php?view=network" class="bg-[#E6F1FB] text-[#8B7EC8] p-4 rounded-xl hover:bg-[#D1C7EB] transition-all duration-300 text-center">
                            <i class="fas fa-project-diagram w-7 h-7 mb-2"></i>
                            <span class="text-sm font-medium">Network of Friends</span>
                        </a>
                        <a href="friendship_map_of_memories.php?view=map" class="bg-[#F4A6A6] text-white p-4 rounded-xl hover:bg-[#F18C8C] transition-all duration-300 text-center">
                            <i class="fas fa-map-marked-alt w-7 h-7 mb-2"></i>
                            <span class="text-sm font-medium">Map Your Memories</span>
                        </a>
                    </div>
                </div>
                <div class="mb-8 enhanced-backdrop p-6 bg-[#F4F8FD]">
                    <div class="flex items-center justify-between mb-6">
                        <h2 class="text-xl font-semibold text-[#2D2A3D]">Recent Memories</h2>
                    </div>
                    <div class="flex space-x-4 overflow-x-auto pb-4 scrollbar-hide">
                        <?php
                        $recentMemories = array_slice(array_reverse($userMemories), 0, 3);
                        if (empty($recentMemories)) {
                            echo '<div class="text-[#6B6B7D]">No memories yet. <a href="add_memory.php" class="text-[#8B7EC8] underline">Create one!</a></div>';
                        } else {
                            $rotations = ['rotate-1', '-rotate-1', 'rotate-2'];
                            $i = 0;
                            foreach ($recentMemories as $memory) {
                                $date = date('M j', strtotime($memory['date'] ?? ''));
                                $title = htmlspecialchars($memory['title'] ?? 'Untitled');
                                $desc = htmlspecialchars($memory['description'] ?? '');
                                $location = htmlspecialchars($memory['location'] ?? '');
                                $friendNames = [];
                                if (!empty($memory['friends']) && isset($currentUser['friends'])) {
                                    foreach ($memory['friends'] as $fid) {
                                        foreach ($currentUser['friends'] as $f) {
                                            if ($f['id'] == $fid) {
                                                $friendNames[] = htmlspecialchars($f['name']);
                                                break;
                                            }
                                        }
                                    }
                                }
                                $friendsStr = $friendNames ? 'with ' . implode(', ', $friendNames) : '';
                                $img = (!empty($memory['image']) && strpos($memory['image'], 'uploads/') === 0)
                                    ? '/memorybook/' . htmlspecialchars($memory['image'])
                                    : 'https://ui-avatars.com/api/?name=' . urlencode($title);
                                $rotation = $rotations[$i % count($rotations)];
                                echo '<a href="memory_details.php?id=' . urlencode($memory['memory_id']) . '" class="flex-shrink-0 w-64 block no-underline text-left">';
                            echo '<div class="w-full bg-white p-4 rounded-2xl shadow transition-all duration-300 hover:scale-105 transform ' . $rotation . ' hover:rotate-0 cursor-pointer">';
                                echo '<div class="relative mb-4">';
                                echo '<img src="' . $img . '" alt="' . $title . '" class="w-full h-48 object-cover rounded-lg" loading="lazy" />';
                                if ($location) {
                                    echo '<div class="absolute top-2 right-2 bg-white/90 backdrop-blur-sm px-2 py-1 rounded-full text-xs font-medium text-[#6B6B7D]">📍 ' . $location . '</div>';
                                }
                                echo '</div>';
                                echo '<h3 class="font-semibold text-[#2D2A3D] mb-2">' . $title . '</h3>';
                                echo '<p class="text-sm text-[#6B6B7D] mb-3">' . $desc . '</p>';
                                echo '<div class="flex items-center justify-between">';
                                echo '<span class="text-xs text-[#6B6B7D]">' . $friendsStr . '</span>';
                                echo '<span class="text-xs text-[#8B7EC8] font-medium">' . $date . '</span>';
                                echo '</div>';
                                echo '</div>';
                                echo '</a>';
                                $i++;
                            }
                        }
                        ?>
                    </div>
                </div>
            </div>
            <div class="lg:col-span-3">
                <div class="enhanced-backdrop p-6 mb-6 bg-[#F0F7FF]">
                    <h3 class="font-semibold text-[#2D2A3D] mb-4">Friends Activity</h3>
                    <div class="space-y-4">
                        <?php
                        $recentFriends = array_slice(array_reverse($currentUser['friends'] ?? []), 0, 3);
                        if (empty($recentFriends)) {
                            echo '<div class="text-[#6B6B7D]">No friends added yet.</div>';
                        } else {
                            foreach ($recentFriends as $f) {
                                $img = (!empty($f['profile_image']) && strpos($f['profile_image'], 'uploads/') === 0)
                                    ? '/memorybook/' . htmlspecialchars($f['profile_image'])
                                    : 'https://ui-avatars.com/api/?name=' . urlencode($f['name']);
                                $name = htmlspecialchars($f['name']);
                                $added = !empty($f['date_added']) ? date('M j', strtotime($f['date_added'])) : '';
                                echo '<div class="flex items-start space-x-3">';
                                echo '<img src="' . $img . '" alt="' . $name . ' profile" class="w-8 h-8 rounded-full object-cover flex-shrink-0" loading="lazy" />';
                                echo '<div class="flex-1 min-w-0">';
                                echo '<p class="text-sm text-[#2D2A3D]"><span class="font-medium">' . $name . '</span> added as a friend';
                                if ($added) echo ' <span class="text-xs text-[#8B7EC8]">(' . $added . ')</span>';
                                echo '</p>';
                                echo '</div>';
                                echo '</div>';
                            }
                        }
                        ?>
                    </div>
                </div>
                <div class="enhanced-backdrop p-6 bg-[#F9FBE7]">
                    <h3 class="font-semibold text-[#2D2A3D] mb-4">This Week</h3>
                    <div class="space-y-3">
                        <?php
                        $now = time();
                        $weekAgo = $now - 7*24*60*60;
                        $newMemories = 0;
                    $newFriends = 0;
                    if (!empty($memories)) {
                        foreach ($memories as $m) {
                            $t = strtotime($m['date'] ?? '');
                            if ($t && $t >= $weekAgo) $newMemories++;
                        }
                    }
                        if (!empty($currentUser['friends'])) {
                            foreach ($currentUser['friends'] as $f) {
                                $t = strtotime($f['date_added'] ?? '');
                            if ($t && $t >= $weekAgo) $newFriends++;
                            }
                        }
                        $mapViews = isset($currentUser['map_views_this_week']) ? intval($currentUser['map_views_this_week']) : 0;
                        ?>
                        <div class="flex items-center justify-between">
                            <span class="text-sm text-[#6B6B7D]">New memories</span>
                            <span class="text-sm font-medium text-[#2D2A3D]"><?php echo $newMemories; ?></span>
                        </div>
                        <div class="flex items-center justify-between">
                            <span class="text-sm text-[#6B6B7D]">Friends added</span>
                            <span class="text-sm font-medium text-[#2D2A3D]"><?php echo $newFriends; ?></span>
                        </div>
                        <div class="flex items-center justify-between">
                            <span class="text-sm text-[#6B6B7D]">Map views</span>
                            <span class="text-sm font-medium text-[#2D2A3D]"><?php echo $mapViews; ?></span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </main>
</body>
</html>