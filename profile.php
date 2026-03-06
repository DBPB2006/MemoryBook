<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
session_start();
include 'navbar.php';
require_once __DIR__ . '/../data_structures/common_functions.php';

if (!isset($_SESSION['email'])) {
    header('Location: login.php');
    exit();
}

$usersData = loadUsers();
$currentEmail = $_SESSION['email'];
$currentUser = getCurrentUser($usersData, $currentEmail);
if (!$currentUser) {
    echo "User not found.";
    exit();
}

$successMsg = $errorMsg = '';
if (
    $_SERVER['REQUEST_METHOD'] === 'POST'
) {
    if (isset($_POST['update_profile'])) {
        $firstName = trim($_POST['first_name'] ?? '');
        $lastName = trim($_POST['last_name'] ?? '');
        $username = trim($_POST['username'] ?? '');
        $email = trim($_POST['email'] ?? '');
        if (
            isset($_FILES['profile_pic']) && $_FILES['profile_pic']['error'] === UPLOAD_ERR_OK
        ) {
            $file = $_FILES['profile_pic'];
            $ext = getFileExtension($file['name']);
            if (isAllowedImageExtension($ext)) {
                $filename = generateUniqueProfileName($ext);
                $targetPath = __DIR__ . '/../uploads/' . $filename;
                if (moveUploadedFile($file['tmp_name'], $targetPath)) {
                    $currentUser['profile_pic'] = 'uploads/' . $filename;
                }
            }
        }
        $currentUser['first_name'] = $firstName;
        $currentUser['last_name'] = $lastName;
        $currentUser['username'] = $username;
        $currentUser['email'] = $email;
        saveUser($usersData, $currentUser, $currentEmail, $email);
        $successMsg = 'Profile updated successfully!';
    } elseif (isset($_POST['change_password'])) {
        $old = $_POST['old_password'] ?? '';
        $new = $_POST['new_password'] ?? '';
        $confirm = $_POST['confirm_password'] ?? '';
        if (!verifyPassword($old, $currentUser['password'])) {
            $errorMsg = 'Old password is incorrect.';
        } elseif ($new !== $confirm) {
            $errorMsg = 'New passwords do not match.';
        } elseif (!isStrongPassword($new)) {
            $errorMsg = 'Password must be at least 6 characters.';
        } else {
            $currentUser['password'] = hashPassword($new);
            saveUser($usersData, $currentUser, $currentEmail, $currentUser['email']);
            $successMsg = 'Password changed successfully!';
        }
    } elseif (isset($_POST['delete_account'])) {
        deleteUser($usersData, $currentUser);
        session_unset();
        session_destroy();
        header('Location: signup.php?deleted=1');
        exit();
    }
}
$friends = $currentUser['friends'] ?? [];
$memories = loadUserMemories($currentEmail);
$recentMemories = getRecentMemories($memories, 3);
$img = getProfileImageUrl($currentUser);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Profile - MemoryBook</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" crossorigin="anonymous" referrerpolicy="no-referrer" />
    <link rel="stylesheet" href="blobs.css" />
    <link rel="stylesheet" href="output.css" />
    <link href="https://fonts.googleapis.com/css2?family=Kalam:wght@400;700&family=Quicksand:wght@400;500;700&display=swap" rel="stylesheet">
    <style>
        html, body { font-family: 'Quicksand', sans-serif; }
        .font-kalam { font-family: 'Kalam', cursive !important; }
        .glass {
            background: rgba(255,255,255,0.7);
            box-shadow: 0 8px 32px rgba(139,126,200,0.12);
            backdrop-filter: blur(16px);
            border: 2px solid rgba(200,200,255,0.18);
        }
        .highlight-underline {
            background: linear-gradient(90deg, #FDE8E8 60%, #A8C8EC 100%);
            border-radius: 1em;
            padding: 0 0.3em;
            box-decoration-break: clone;
            -webkit-box-decoration-break: clone;
        }
        .avatar {
            width: 6rem; height: 6rem;
            border-radius: 9999px;
            background: linear-gradient(135deg, #A8C8EC 60%, #FDE8E8 100%);
            display: flex; align-items: center; justify-content: center;
            font-weight: bold; color: #fff; font-size: 2.5rem;
            box-shadow: 0 2px 12px #A8C8EC44;
            position: relative;
            z-index: 2;
        }
    </style>
</head>
<body class="bg-gradient-to-br from-purple-50 via-blue-50 to-pink-50 min-h-screen">
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
<div class="content-overlay">
    <main class="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        <h1 class="text-4xl font-bold text-[#8B7EC8] mb-6 text-center"><span class="highlight-underline">Your Profile</span></h1>
        <?php if ($successMsg): ?><div class="bg-green-100 text-green-800 px-4 py-2 rounded mb-4 text-center"><?= $successMsg ?></div><?php endif; ?>
        <?php if ($errorMsg): ?><div class="bg-red-100 text-red-800 px-4 py-2 rounded mb-4 text-center"><?= $errorMsg ?></div><?php endif; ?>
        <form method="POST" enctype="multipart/form-data" class="glass rounded-3xl shadow-2xl p-8 mb-8 flex flex-col items-center gap-6 border-2 border-[#E8E3F5]">
            <div class="avatar mb-2">
                <img src="<?= $img ?>" alt="Profile Image" class="w-full h-full object-cover rounded-full">
                </div>
            <label class="block text-[#8B7EC8] font-medium cursor-pointer mb-2">
                Change Profile Picture
                <input type="file" name="profile_pic" accept="image/*" class="hidden" onchange="this.form.submit()">
            </label>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6 w-full">
                <div>
                    <label class="block text-[#2D2A3D] font-semibold mb-1">First Name</label>
                    <input type="text" name="first_name" value="<?= htmlspecialchars($currentUser['first_name'] ?? '') ?>" class="w-full rounded-xl border border-gray-200 p-3 focus:ring-2 focus:ring-[#8B7EC8] focus:border-[#8B7EC8]" required />
                </div>
                <div>
                    <label class="block text-[#2D2A3D] font-semibold mb-1">Last Name</label>
                    <input type="text" name="last_name" value="<?= htmlspecialchars($currentUser['last_name'] ?? '') ?>" class="w-full rounded-xl border border-gray-200 p-3 focus:ring-2 focus:ring-[#8B7EC8] focus:border-[#8B7EC8]" required />
                </div>
                <div>
                    <label class="block text-[#2D2A3D] font-semibold mb-1">Username</label>
                    <input type="text" name="username" value="<?= htmlspecialchars($currentUser['username'] ?? '') ?>" class="w-full rounded-xl border border-gray-200 p-3 focus:ring-2 focus:ring-[#8B7EC8] focus:border-[#8B7EC8]" required />
                </div>
                <div>
                    <label class="block text-[#2D2A3D] font-semibold mb-1">Email</label>
                    <input type="email" name="email" value="<?= htmlspecialchars($currentUser['email'] ?? '') ?>" class="w-full rounded-xl border border-gray-200 p-3 focus:ring-2 focus:ring-[#8B7EC8] focus:border-[#8B7EC8]" required />
                </div>
            </div>
            <div class="flex gap-4 mt-4">
                <button type="submit" name="update_profile" class="px-8 py-3 rounded-xl bg-[#8B7EC8] text-white font-semibold shadow hover:bg-[#7A6BB5] transition">Save Changes</button>
            </div>
        </form>
        <form method="POST" class="glass rounded-3xl shadow-2xl p-8 mb-8 border-2 border-[#E8E3F5]">
            <h2 class="text-2xl font-semibold text-[#2D2A3D] mb-4">Change Password</h2>
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                    <div>
                    <label class="block text-[#2D2A3D] font-semibold mb-1">Old Password</label>
                    <input type="password" name="old_password" class="w-full rounded-xl border border-gray-200 p-3 focus:ring-2 focus:ring-[#8B7EC8] focus:border-[#8B7EC8]" required />
                </div>
                    <div>
                    <label class="block text-[#2D2A3D] font-semibold mb-1">New Password</label>
                    <input type="password" name="new_password" class="w-full rounded-xl border border-gray-200 p-3 focus:ring-2 focus:ring-[#8B7EC8] focus:border-[#8B7EC8]" required />
                </div>
                    <div>
                    <label class="block text-[#2D2A3D] font-semibold mb-1">Confirm Password</label>
                    <input type="password" name="confirm_password" class="w-full rounded-xl border border-gray-200 p-3 focus:ring-2 focus:ring-[#8B7EC8] focus:border-[#8B7EC8]" required />
                </div>
            </div>
            <div class="flex gap-4 mt-4">
                <button type="submit" name="change_password" class="px-8 py-3 rounded-xl bg-[#A8C8EC] text-white font-semibold shadow hover:bg-[#8B7EC8] transition">Change Password</button>
            </div>
        </form>
        <div class="glass rounded-3xl shadow-2xl p-8 mb-8 border-2 border-[#E8E3F5]">
            <h2 class="text-2xl font-semibold text-[#2D2A3D] mb-4">Your Friends</h2>
                    <div class="space-y-5">
                <?php if (empty($friends)): ?>
                    <div class="text-[#6B6B7D]">No friends yet.</div>
                <?php else: foreach ($friends as $friend): ?>
                        <div class="flex items-center space-x-5 p-4 bg-[#F5F3FB] rounded-xl">
                        <div class="w-14 h-14 bg-[#E8E3F5] rounded-full flex items-center justify-center overflow-hidden">
                            <?php $fimg = (!empty($friend['image_url']) && strpos($friend['image_url'], 'uploads/') === 0)
                                ? '/memorybook/' . htmlspecialchars($friend['image_url'])
                                : 'https://ui-avatars.com/api/?name=' . urlencode($friend['name']); ?>
                            <img src="<?= $fimg ?>" alt="<?= htmlspecialchars($friend['name']) ?>" class="w-full h-full object-cover rounded-full" />
                            </div>
                            <div class="flex-1">
                            <h4 class="font-medium text-[#2D2A3D]"><?= htmlspecialchars($friend['name']) ?></h4>
                            <p class="text-base text-[#6B6B7D]"><?= htmlspecialchars($friend['relationship_type'] ?? '') ?></p>
                        </div>
                        <div class="text-base text-[#8B7EC8]">
                            <form method="post" style="display:inline;">
                                <input type="hidden" name="remove_friend_id" value="<?= htmlspecialchars($friend['friend_id']) ?>" />
                                <button type="submit" class="text-red-500 hover:text-red-700"><i class="fas fa-user-minus"></i></button>
                            </form>
                        </div>
                    </div>
                <?php endforeach; endif; ?>
                    </div>
                </div>
        <div class="glass rounded-3xl shadow-2xl p-8 mb-8 border-2 border-[#E8E3F5]">
            <h2 class="text-2xl font-semibold text-[#2D2A3D] mb-4">Recent Memories</h2>
                    <div class="space-y-5">
                <?php if (empty($recentMemories)): ?>
                    <div class="text-[#6B6B7D]">No memories yet.</div>
                <?php else: foreach ($recentMemories as $mem): ?>
                        <div class="flex items-center space-x-5 p-4 bg-[#F5F3FB] rounded-xl">
                        <div class="w-14 h-14 bg-[#E8E3F5] rounded-xl flex items-center justify-center overflow-hidden">
                            <?php $mimg = (!empty($mem['image']) && strpos($mem['image'], 'uploads/') === 0)
                                ? '/memorybook/' . htmlspecialchars($mem['image'])
                                : 'https://ui-avatars.com/api/?name=' . urlencode($mem['title'] ?? 'Memory'); ?>
                            <img src="<?= $mimg ?>" alt="<?= htmlspecialchars($mem['title']) ?>" class="w-full h-full object-cover rounded-xl" />
                            </div>
                            <div class="flex-1">
                            <h4 class="font-medium text-[#2D2A3D]"><?= htmlspecialchars($mem['title']) ?></h4>
                            <p class="text-base text-[#6B6B7D]"><?= htmlspecialchars($mem['location'] ?? '') ?></p>
                        </div>
                        <div class="text-base text-[#8B7EC8]">
                            <?= htmlspecialchars(date('M d', strtotime($mem['date'] ?? ''))) ?>
                        </div>
                        <div>
                            <a href="memory_details.php?id=<?= urlencode($mem['memory_id']) ?>" class="text-[#8B7EC8] hover:underline"><i class="fas fa-eye"></i></a>
                        </div>
                    </div>
                <?php endforeach; endif; ?>
            </div>
                </div>
        <form method="POST" onsubmit="return confirm('Are you sure you want to delete your account? This cannot be undone.');" class="text-center">
            <button type="submit" name="delete_account" class="px-8 py-3 rounded-xl bg-red-500 text-white font-semibold shadow hover:bg-red-700 transition">Delete Account</button>
        </form>
        </main>
    </div>
</body>
</html> 