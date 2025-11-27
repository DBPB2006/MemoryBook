<?php
session_start();
require_once __DIR__ . '/../data_structures/common_functions.php';
if (isset($_POST['logout']) && sanitizeInput($_POST['logout']) === '1') {
    session_unset(); session_destroy(); header('Location: login.php'); exit();
}
if (!isLoggedIn()) { header('Location: login.php'); exit(); }
$users = loadUsers();
$currentEmail = $_SESSION['email'];
$currentUser = getCurrentUser($users, $currentEmail);
$userFriends = $currentUser['friends'] ?? [];
$memories = loadMemories();
function countMemoriesForFriend($memories, $friendEmail) {
    $count = 0;
    foreach ($memories as $memory) {
        if (isset($memory['friends'])) {
            foreach ($memory['friends'] as $f) {
                if ($f === $friendEmail) { $count++; break; }
            }
        }
    }
    return $count;
}
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_friend_id'])) {
    $deleteId = sanitizeInput($_POST['delete_friend_id']);
    $users = loadUsers();
    $currentUser = getCurrentUser($users, $currentEmail);
    if (!empty($currentUser['friends'])) {
        $currentUser['friends'] = array_values(array_filter($currentUser['friends'], function($f) use ($deleteId) { return $f['friend_id'] !== $deleteId; }));
        $users[$currentUser['bucket']][$currentEmail] = $currentUser;
        saveUsers($users);
        header('Location: view_friends.php?deleted=1');
        exit();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>View Friends - MemoryBook</title>
    <link rel="stylesheet" href="output.css" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" />
    <link href="https://fonts.googleapis.com/css2?family=Kalam:wght@400;700&family=Quicksand:wght@400;500;700&display=swap" rel="stylesheet">
    <style>html, body { font-family: 'Quicksand', sans-serif; } .font-kalam { font-family: 'Kalam', cursive !important; }</style>
</head>
<body class="bg-gradient-to-br from-[#F5F3FB] via-[#FDFCFC] to-[#F4F8FD] min-h-screen">
<link rel="stylesheet" href="blobs.css">
<div class="blobs-bg">
  <div class="blob blob1"></div><div class="blob blob2"></div><div class="blob blob3"></div><div class="blob blob4"></div><div class="blob blob5"></div><div class="blob blob6"></div><div class="blob blob7"></div><div class="blob blob8"></div><div class="blob blob9"></div><div class="blob blob10"></div><div class="blob blob11"></div><div class="blob blob12"></div><div class="blob blob13"></div>
</div>
<?php include 'navbar.php'; ?>
<main class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
    <div class="mb-8">
        <h1 class="text-3xl sm:text-4xl font-semibold text-[#2D2A3D] mb-2">Your Friends</h1>
        <p class="text-lg text-[#6B6B7D]">Manage and explore your friendship network</p>
    </div>
    <div class="flex justify-end mb-6">
        <a href="add_friend.php" class="bg-[#8B7EC8] text-white px-6 py-3 rounded-lg hover:bg-[#7A6BB5] transition font-medium flex items-center gap-2"><i class="fas fa-user-plus"></i> Add Another Friend</a>
    </div>
    <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-6" id="friendsGrid">
        <?php if (empty($userFriends)): ?>
            <div class="col-span-2 md:col-span-3 lg:col-span-4 text-center py-16">
                <div class="max-w-md mx-auto">
                    <i class="fas fa-users text-[#6B6B7D] text-6xl mx-auto mb-6"></i>
                    <h3 class="text-xl font-semibold text-[#2D2A3D] mb-2">No Friends Found</h3>
                    <p class="text-[#6B6B7D] mb-6">Start building your friendship network by adding your first friend.</p>
                    <a href="add_friend.php" class="bg-[#8B7EC8] text-white px-6 py-3 rounded-lg hover:bg-[#7A6BB5] transition font-medium">Add Your First Friend</a>
                </div>
            </div>
        <?php else: ?>
            <?php foreach ($userFriends as $friend): ?>
                <?php $friendId = $friend['friend_id']; $memoriesCount = countMemoriesForFriend($memories, $friendId); ?>
                <div class="bg-white rounded-2xl p-6 shadow transition hover:scale-105 group relative">
                    <form method="post" class="absolute top-2 right-2">
                        <input type="hidden" name="delete_friend_id" value="<?= htmlspecialchars($friend['friend_id']) ?>" />
                        <button type="submit" onclick="return confirm('Are you sure you want to remove <?= htmlspecialchars($friend['name']) ?> from your friends list? This action cannot be undone.');" class="w-8 h-8 bg-[#E88B8B] text-white rounded-full hover:bg-[#E47777] flex items-center justify-center"><i class="fas fa-trash text-sm"></i></button>
                    </form>
                    <div class="text-center">
                        <div class="relative mb-4">
                            <?php
                            $imgSrc = '';
                            if (!empty($friend['image_url']) && file_exists(__DIR__ . '/../' . $friend['image_url'])) {
                                $imgSrc = '../' . $friend['image_url'];
                            } elseif (!empty($friend['image_url']) && strpos($friend['image_url'], 'uploads/') === 0) {
                                $imgSrc = $friend['image_url'];
                            } else {
                                $imgSrc = 'https://ui-avatars.com/api/?name=' . urlencode($friend['name']);
                            }
                            ?>
                            <img src="<?= htmlspecialchars($imgSrc) ?>" alt="<?= htmlspecialchars($friend['name']); ?>" class="w-20 h-20 rounded-full object-cover mx-auto border-4 border-[#E8E3F5] group-hover:border-[#D1C7EB] transition" loading="lazy" />
                        </div>
                        <h3 class="font-semibold text-[#2D2A3D] mb-1"><?= htmlspecialchars($friend['name']); ?></h3>
                        <span class="inline-block bg-[#E8E3F5] text-[#6958A2] px-3 py-1 rounded-full text-sm font-medium mb-3">
                            <?= htmlspecialchars($friend['relationship_type'] ?? 'Friend'); ?>
                        </span>
                        <div class="flex items-center justify-center space-x-1 mb-4">
                            <i class="fas fa-star text-[#F4A6A6] text-sm"></i>
                            <span class="text-sm text-[#6B6B7D]">
                                <?= $memoriesCount; ?> memories
                            </span>
                        </div>
                        <a href="friendship_map_of_memories.php?friend_id=<?= urlencode($friend['friend_id'] ?? $friend['id'] ?? ''); ?>" class="w-full bg-[#8B7EC8] text-white py-2 px-4 rounded-lg hover:bg-[#7A6BB5] transition font-medium text-sm">View Friendship Map</a>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
    <?php if (isset($_GET['deleted']) && $_GET['deleted'] == '1'): ?>
    <div id="deleteSuccess" class="fixed top-4 right-4 bg-[#7BC97B] text-white px-6 py-3 rounded-lg shadow-lg z-50">
        <div class="flex items-center"><i class="fas fa-check-circle mr-2"></i><span>Friend removed successfully!</span></div>
    </div>
    <script>setTimeout(() => { document.getElementById('deleteSuccess').style.display = 'none'; }, 3000);</script>
    <?php endif; ?>
</main>
</body>
</html>