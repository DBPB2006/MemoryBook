<?php
session_start();
require_once __DIR__ . '/../data_structures/common_functions.php';

requireLogin();
handleLogout();

$usersData = loadUsers(__DIR__ . '/../data/users.json');
$currentEmail = $_SESSION['email'];
$currentUser = getCurrentUser($usersData, $currentEmail);

if (!$currentUser) {
    echo "User not found.";
    exit();
}

$successMsg = $errorMsg = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['update_profile'])) {
        $firstName = trim($_POST['first_name'] ?? '');
        $lastName = trim($_POST['last_name'] ?? '');
        $username = trim($_POST['username'] ?? '');
        $email = trim($_POST['email'] ?? '');
        
        if (isset($_FILES['profile_pic']) && $_FILES['profile_pic']['error'] === UPLOAD_ERR_OK) {
            $file = $_FILES['profile_pic'];
            $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
            if (in_array($ext, ['jpg', 'jpeg', 'png', 'gif', 'webp'])) {
                $filename = uniqid('profile_', true) . '.' . $ext;
                $targetPath = __DIR__ . '/../uploads/' . $filename;
                if (!is_dir(__DIR__ . '/../uploads/')) {
                    mkdir(__DIR__ . '/../uploads/', 0777, true);
                }
                if (move_uploaded_file($file['tmp_name'], $targetPath)) {
                    $currentUser['profile_pic'] = 'uploads/' . $filename;
                }
            }
        }
        
        $currentUser['first_name'] = $firstName;
        $currentUser['last_name'] = $lastName;
        $currentUser['username'] = $username;
        
        if ($email !== $currentEmail) {
           $oldBucket = getBucket($currentEmail);
           unset($usersData[$oldBucket][$currentEmail]);
           $currentUser['email'] = $email;
           saveCurrentUser($usersData, $currentUser);
           $currentEmail = $email;
           $_SESSION['email'] = $email;
        } else {
           saveCurrentUser($usersData, $currentUser);
        }
        saveUsers($usersData, __DIR__ . '/../data/users.json');
        
        $successMsg = 'Profile updated successfully!';
    } elseif (isset($_POST['change_password'])) {
        $old = $_POST['old_password'] ?? '';
        $new = $_POST['new_password'] ?? '';
        $confirm = $_POST['confirm_password'] ?? '';
        if (!verifyPassword($old, $currentUser['password'])) {
            $errorMsg = 'Old password is incorrect.';
        } elseif ($new !== $confirm) {
            $errorMsg = 'New passwords do not match.';
        } elseif (strlen($new) < 6) {
            $errorMsg = 'Password must be at least 6 characters.';
        } else {
            $currentUser['password'] = password_hash($new, PASSWORD_DEFAULT);
            saveCurrentUser($usersData, $currentUser);
            saveUsers($usersData, __DIR__ . '/../data/users.json');
            $successMsg = 'Password changed successfully!';
        }
    } elseif (isset($_POST['remove_friend_id'])) {
        $removeId = $_POST['remove_friend_id'];
        $friends = $currentUser['friends'] ?? [];
        foreach ($friends as $idx => $friend) {
            if (($friend['friend_id'] ?? '') === $removeId || ($friend['email'] ?? '') === $removeId) {
                unset($friends[$idx]);
                break;
            }
        }
        $currentUser['friends'] = array_values($friends);
        saveCurrentUser($usersData, $currentUser);
        saveUsers($usersData, __DIR__ . '/../data/users.json');
        $successMsg = 'Friend removed successfully.';
    } elseif (isset($_POST['delete_account'])) {
        $bucket = getBucket($currentEmail);
        unset($usersData[$bucket][$currentEmail]);
        saveUsers($usersData, __DIR__ . '/../data/users.json');
        session_unset();
        session_destroy();
        header('Location: signup.php?deleted=1');
        exit();
    }
}

$friends = $currentUser['friends'] ?? [];

$allMemories = loadMemories(__DIR__ . '/../data/memories.json');
$myMemories = [];
foreach ($allMemories as $mem) {
    if (($mem['owner'] ?? '') === $currentEmail) {
        $myMemories[] = $mem;
    }
}
usort($myMemories, function($a, $b) {
    return strtotime($b['created_at'] ?? '0') - strtotime($a['created_at'] ?? '0');
});
$recentMemories = array_slice($myMemories, 0, 3);

$img = (!empty($currentUser['profile_pic']) && strpos($currentUser['profile_pic'], 'uploads/') === 0) 
    ? '/memorybookdsa2/' . htmlspecialchars($currentUser['profile_pic']) 
    : 'https://ui-avatars.com/api/?name=' . urlencode(($currentUser['first_name'] ?? '') . ' ' . ($currentUser['last_name'] ?? '')) . '&background=A8C8EC&color=fff&size=200';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Profile - MemoryBook</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" />
    <link rel="stylesheet" href="output.css" />
    <link rel="stylesheet" href="blobs.css" />
    <link href="https://fonts.googleapis.com/css2?family=Quicksand:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Quicksand', sans-serif; }
        .avatar-container {
            width: 8rem; height: 8rem;
            border-radius: 9999px;
            background: linear-gradient(135deg, #A8C8EC 0%, #FDE8E8 100%);
            display: flex; align-items: center; justify-content: center;
            box-shadow: 0 4px 14px rgba(139, 126, 200, 0.3);
            position: relative;
            overflow: hidden;
            border: 4px solid white;
        }
        .avatar-container:hover .avatar-overlay {
            opacity: 1;
        }
        .avatar-overlay {
            position: absolute;
            inset: 0;
            background: rgba(0, 0, 0, 0.4);
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 1.5rem;
            opacity: 0;
            transition: opacity 0.2s;
            cursor: pointer;
        }
    </style>
</head>
<body class="bg-gradient-to-br from-[#F5F3FB] to-[#F4F8FD] min-h-screen relative">
    <div class="blobs-bg">
        <div class="blob blob1"></div><div class="blob blob2"></div><div class="blob blob3"></div>
        <div class="blob blob4"></div><div class="blob blob5"></div><div class="blob blob6"></div>
        <div class="blob blob7"></div><div class="blob blob8"></div><div class="blob blob9"></div>
        <div class="blob blob10"></div><div class="blob blob11"></div><div class="blob blob12"></div>
        <div class="blob blob13"></div>
    </div>
    
    <?php include 'navbar.php'; ?>

    <main class="max-w-4xl mx-auto px-4 py-8 relative z-10">
        <div class="mb-8">
            <h1 class="text-3xl font-semibold text-[#2D2A3D] mb-2">Account Settings</h1>
            <p class="text-lg text-[#6B6B7D]">Manage your profile and preferences</p>
        </div>

        <?php if ($successMsg): ?>
            <div class="bg-green-100 border border-green-200 text-green-700 px-4 py-3 rounded-xl mb-6 flex items-center shadow-sm">
                <i class="fas fa-check-circle mr-2"></i> <?= htmlspecialchars($successMsg) ?>
            </div>
        <?php endif; ?>
        
        <?php if ($errorMsg): ?>
            <div class="bg-red-100 border border-red-200 text-red-700 px-4 py-3 rounded-xl mb-6 flex items-center shadow-sm">
                <i class="fas fa-exclamation-circle mr-2"></i> <?= htmlspecialchars($errorMsg) ?>
            </div>
        <?php endif; ?>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
            
            <!-- Left Column: Avatar & Quick Actions -->
            <div class="lg:col-span-1 space-y-6">
                <div class="bg-white rounded-2xl p-6 shadow-sm border border-[#E8E3F5] text-center flex flex-col items-center">
                    <form method="POST" enctype="multipart/form-data" id="avatarForm">
                        <input type="hidden" name="update_profile" value="1">
                        <input type="hidden" name="first_name" value="<?= htmlspecialchars($currentUser['first_name'] ?? '') ?>">
                        <input type="hidden" name="last_name" value="<?= htmlspecialchars($currentUser['last_name'] ?? '') ?>">
                        <input type="hidden" name="username" value="<?= htmlspecialchars($currentUser['username'] ?? '') ?>">
                        <input type="hidden" name="email" value="<?= htmlspecialchars($currentUser['email'] ?? '') ?>">
                        
                        <div class="avatar-container mb-4">
                            <img src="<?= htmlspecialchars($img) ?>" alt="Profile avatar" class="w-full h-full object-cover">
                            <label class="avatar-overlay">
                                <i class="fas fa-camera"></i>
                                <input type="file" name="profile_pic" accept="image/*" class="hidden" onchange="document.getElementById('avatarForm').submit();">
                            </label>
                        </div>
                    </form>
                    <h2 class="text-xl font-bold text-[#2D2A3D]"><?= htmlspecialchars(($currentUser['first_name'] ?? '') . ' ' . ($currentUser['last_name'] ?? '')) ?></h2>
                    <p class="text-[#6B6B7D] text-sm">@<?= htmlspecialchars($currentUser['username'] ?? '') ?></p>
                </div>

                <div class="bg-white rounded-2xl p-6 shadow-sm border border-[#E8E3F5]">
                    <h3 class="text-lg font-semibold text-[#2D2A3D] mb-4">Danger Zone</h3>
                    <form method="POST" onsubmit="return confirm('Are you sure you want to delete your account? This action cannot be undone and you will lose all your memories.');">
                        <button type="submit" name="delete_account" class="w-full py-2.5 bg-red-50 text-red-600 rounded-xl font-medium hover:bg-red-100 transition border border-red-100">
                            Delete Account
                        </button>
                    </form>
                </div>
            </div>

            <!-- Right Column: Forms & Data -->
            <div class="lg:col-span-2 space-y-8">
                
                <!-- Profile Information -->
                <div class="bg-white rounded-2xl p-6 shadow-sm border border-[#E8E3F5]">
                    <h3 class="text-xl font-semibold text-[#2D2A3D] mb-6">Profile Information</h3>
                    <form method="POST" class="space-y-4">
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-sm font-semibold text-[#6B6B7D] mb-1">First Name</label>
                                <input type="text" name="first_name" value="<?= htmlspecialchars($currentUser['first_name'] ?? '') ?>" class="w-full px-4 py-2 border border-[#E8E3F5] rounded-xl focus:ring-2 focus:ring-[#8B7EC8] focus:border-transparent outline-none transition" required />
                            </div>
                            <div>
                                <label class="block text-sm font-semibold text-[#6B6B7D] mb-1">Last Name</label>
                                <input type="text" name="last_name" value="<?= htmlspecialchars($currentUser['last_name'] ?? '') ?>" class="w-full px-4 py-2 border border-[#E8E3F5] rounded-xl focus:ring-2 focus:ring-[#8B7EC8] focus:border-transparent outline-none transition" required />
                            </div>
                        </div>
                        <div>
                            <label class="block text-sm font-semibold text-[#6B6B7D] mb-1">Username</label>
                            <input type="text" name="username" value="<?= htmlspecialchars($currentUser['username'] ?? '') ?>" class="w-full px-4 py-2 border border-[#E8E3F5] rounded-xl focus:ring-2 focus:ring-[#8B7EC8] focus:border-transparent outline-none transition" required />
                        </div>
                        <div>
                            <label class="block text-sm font-semibold text-[#6B6B7D] mb-1">Email</label>
                            <input type="email" name="email" value="<?= htmlspecialchars($currentUser['email'] ?? '') ?>" class="w-full px-4 py-2 border border-[#E8E3F5] rounded-xl focus:ring-2 focus:ring-[#8B7EC8] focus:border-transparent outline-none transition" required />
                        </div>
                        
                        <div class="pt-4 flex justify-end">
                            <button type="submit" name="update_profile" class="px-6 py-2.5 bg-[#8B7EC8] text-white rounded-xl font-medium hover:bg-[#7A6BB5] transition shadow-sm">
                                Save Profile
                            </button>
                        </div>
                    </form>
                </div>

                <!-- Password Settings -->
                <div class="bg-white rounded-2xl p-6 shadow-sm border border-[#E8E3F5]">
                    <h3 class="text-xl font-semibold text-[#2D2A3D] mb-6">Change Password</h3>
                    <form method="POST" class="space-y-4">
                        <div>
                            <label class="block text-sm font-semibold text-[#6B6B7D] mb-1">Current Password</label>
                            <input type="password" name="old_password" class="w-full px-4 py-2 border border-[#E8E3F5] rounded-xl focus:ring-2 focus:ring-[#8B7EC8] focus:border-transparent outline-none transition" required />
                        </div>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-sm font-semibold text-[#6B6B7D] mb-1">New Password</label>
                                <input type="password" name="new_password" class="w-full px-4 py-2 border border-[#E8E3F5] rounded-xl focus:ring-2 focus:ring-[#8B7EC8] focus:border-transparent outline-none transition" required />
                            </div>
                            <div>
                                <label class="block text-sm font-semibold text-[#6B6B7D] mb-1">Confirm New Password</label>
                                <input type="password" name="confirm_password" class="w-full px-4 py-2 border border-[#E8E3F5] rounded-xl focus:ring-2 focus:ring-[#8B7EC8] focus:border-transparent outline-none transition" required />
                            </div>
                        </div>
                        
                        <div class="pt-4 flex justify-end">
                            <button type="submit" name="change_password" class="px-6 py-2.5 bg-gray-100 text-[#2D2A3D] rounded-xl font-medium hover:bg-gray-200 transition">
                                Update Password
                            </button>
                        </div>
                    </form>
                </div>

                <!-- Friends List -->
                <div class="bg-white rounded-2xl p-6 shadow-sm border border-[#E8E3F5]">
                    <div class="flex justify-between items-center mb-6">
                        <h3 class="text-xl font-semibold text-[#2D2A3D]">Your Friends</h3>
                        <a href="add_friend.php" class="text-sm text-[#8B7EC8] hover:underline font-medium">Find Friends</a>
                    </div>
                    <div class="space-y-3">
                        <?php if (empty($friends)): ?>
                            <div class="text-[#6B6B7D] text-center py-4 bg-[#F5F3FB] rounded-xl">No friends added yet.</div>
                        <?php else: foreach ($friends as $friend): ?>
                            <div class="flex items-center space-x-4 p-3 hover:bg-[#F5F3FB] rounded-xl transition border border-transparent hover:border-[#E8E3F5]">
                                <div class="w-12 h-12 bg-[#E8E3F5] rounded-full flex items-center justify-center overflow-hidden">
                                    <?php $fimg = (!empty($friend['image_url']) && strpos($friend['image_url'], 'uploads/') === 0)
                                        ? '/memorybook/' . htmlspecialchars($friend['image_url'])
                                        : 'https://ui-avatars.com/api/?name=' . urlencode($friend['name'] ?? 'U') . '&background=random'; ?>
                                    <img src="<?= $fimg ?>" alt="<?= htmlspecialchars($friend['name']) ?>" class="w-full h-full object-cover" />
                                </div>
                                <div class="flex-1 min-w-0">
                                    <h4 class="font-medium text-[#2D2A3D] truncate"><?= htmlspecialchars($friend['name']) ?></h4>
                                    <p class="text-sm text-[#6B6B7D] truncate"><?= htmlspecialchars($friend['relationship_type'] ?? 'Friend') ?></p>
                                </div>
                                <div>
                                    <form method="post" style="display:inline;" onsubmit="return confirm('Remove this friend?');">
                                        <input type="hidden" name="remove_friend_id" value="<?= htmlspecialchars($friend['friend_id']) ?>" />
                                        <button type="submit" class="w-8 h-8 rounded-full flex justify-center items-center text-red-400 hover:bg-red-50 hover:text-red-600 transition">
                                            <i class="fas fa-user-times"></i>
                                        </button>
                                    </form>
                                </div>
                            </div>
                        <?php endforeach; endif; ?>
                    </div>
                </div>

            </div>
        </div>
    </main>
</body>
</html>