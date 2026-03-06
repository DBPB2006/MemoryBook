<?php
$currentEmail = $_SESSION['email'] ?? '';
$users = file_exists(__DIR__ . '/../data/users.json') ? json_decode(file_get_contents(__DIR__ . '/../data/users.json'), true) : [];
$userObj = null;
if ($currentEmail) {
    $firstLetter = strtoupper($currentEmail[0]);
    $userObj = $users[$firstLetter][$currentEmail] ?? null;
}
$userName = $userObj['first_name'] ?? $userObj['username'] ?? ($currentEmail ? explode('@', $currentEmail)[0] : '');
$userInitial = strtoupper(substr($userName, 0, 1));
if (!isset($currentPage)) $currentPage = basename($_SERVER['PHP_SELF']);

// Compute profile image URL for nav avatar
$userImg = null;
if (!empty($userObj['profile_pic'])) {
    $userImg = '/memorybookdsa/' . $userObj['profile_pic'];
} else {
    $userImg = 'https://ui-avatars.com/api/?name=' . urlencode($userObj['first_name'] ?? $userObj['username'] ?? $userInitial);
}
?>
<header class="bg-white/80 backdrop-blur-md border-b border-[#E8E3F5] sticky top-0 z-50 shadow-sm">
    <nav class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex justify-between items-center h-16">
            <div class="flex items-center">
                <a href="homepage.php" class="flex items-center space-x-2 hover:scale-105 transition-transform">
                    <img src="logo.png" alt="MemoryBook Logo" class="h-8 w-8 object-contain rounded-full" style="max-width:2rem;max-height:2rem;" />
                    <span class="text-xl font-semibold text-[#2D2A3D]">MemoryBook</span>
                </a>
            </div>
            <div class="flex items-center space-x-8">
                <a href="homepage.php" class="<?php echo $currentPage === 'homepage.php' ? 'font-medium text-[#8B7EC8]' : 'text-[#6B6B7D]'; ?> hover:text-[#8B7EC8] transition-colors">Home</a>
                <a href="dashboard.php" class="<?php echo $currentPage === 'dashboard.php' ? 'font-medium text-[#8B7EC8]' : 'text-[#6B6B7D]'; ?> hover:text-[#8B7EC8] transition-colors">Dashboard</a>
                <a href="view_friends.php" class="<?php echo $currentPage === 'view_friends.php' ? 'font-medium text-[#8B7EC8]' : 'text-[#6B6B7D]'; ?> hover:text-[#8B7EC8] transition-colors">Friends</a>
                <a href="friendship_map_of_memories.php" class="<?php echo $currentPage === 'friendship_map_of_memories.php' ? 'font-medium text-[#8B7EC8]' : 'text-[#6B6B7D]'; ?> hover:text-[#8B7EC8] transition-colors">Memory Map</a>
                <a href="add_memory.php" class="<?php echo $currentPage === 'add_memory.php' ? 'font-medium text-[#8B7EC8]' : 'text-[#6B6B7D]'; ?> hover:text-[#8B7EC8] transition-colors">Add Memory</a>
                <a href="profile.php" class="<?php echo $currentPage === 'profile.php' ? 'font-medium text-[#8B7EC8]' : 'text-[#6B6B7D]'; ?> hover:text-[#8B7EC8] transition-colors">Profile</a>
                <form method="post" action="login.php" style="display:inline;">
                  <button type="submit" name="logout" value="1" class="text-[#F48498] hover:text-[#8B7EC8] transition-colors bg-transparent border-none cursor-pointer px-0 py-0">Logout</button>
                </form>
            </div>
            <div class="flex items-center space-x-4">
                <div class="relative" id="userMenuContainer">
                    <button id="userAvatar" class="w-8 h-8 bg-[#E8E3F5] rounded-full flex items-center justify-center cursor-pointer hover:bg-[#D8D0F0] transition-colors focus:outline-none focus:ring-2 focus:ring-[#8B7EC8]">
                        <?php if (!empty($userObj['profile_pic'])): ?>
                            <img src="<?php echo $userImg; ?>" alt="Avatar" class="w-full h-full object-cover rounded-full"/>
                        <?php else: ?>
                            <span class="text-sm font-medium text-[#8B7EC8]" id="userInitial"><?php echo htmlspecialchars($userInitial); ?></span>
                        <?php endif; ?>
                    </button>
                    <div id="userDropdown" class="dropdown-menu absolute right-0 mt-2 w-48 bg-white rounded-xl shadow-lg border border-gray-200 z-50 hidden">
                        <div class="py-2">
                            <div class="px-4 py-2 text-sm text-gray-600 border-b border-gray-200">
                                <div class="font-medium text-gray-900" id="userName"><?php echo htmlspecialchars($userName); ?></div>
                                <div id="userEmail" class="break-all"><?php echo htmlspecialchars($currentEmail); ?></div>
                            </div>
                            <a href="profile.php" class="block px-4 py-2 text-sm text-gray-600 hover:bg-gray-50 transition-colors"><i class="fas fa-user mr-2"></i>Profile</a>
                            <form method="post" action="login.php" class="block">
                              <button type="submit" name="logout" value="1" class="w-full text-left px-4 py-2 text-sm text-red-600 hover:bg-red-50 transition-colors flex items-center">
                                <i class="fas fa-sign-out-alt mr-2"></i>Sign Out
                              </button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </nav>
</header>
<script>
const userAvatar = document.getElementById('userAvatar');
const userDropdown = document.getElementById('userDropdown');
if (userAvatar && userDropdown) {
    userAvatar.addEventListener('click', (e) => {
        e.stopPropagation();
        userDropdown.classList.toggle('hidden');
    });
    document.addEventListener('click', (e) => {
        if (!userAvatar.contains(e.target) && !userDropdown.contains(e.target)) {
            userDropdown.classList.add('hidden');
        }
    });
    document.addEventListener('keydown', (e) => {
        if (e.key === 'Escape') {
            userDropdown.classList.add('hidden');
            userAvatar.focus();
        }
    });
}
</script> 