<?php
session_start();
require_once __DIR__ . '/../data_structures/common_functions.php';

if (empty($_SESSION['email'])) {
    header('Location: login.php');
    exit();
}

function handleProfileImageUpload($file) {
    if ($file && $file['error'] === UPLOAD_ERR_OK) {
        $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        $allowed = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
        if (in_array($ext, $allowed)) {
            $dir = __DIR__ . '/../uploads/friends/';
            if (!is_dir($dir)) mkdir($dir, 0777, true);
            $name = 'friend_' . uniqid() . '.' . $ext;
            if (move_uploaded_file($file['tmp_name'], $dir . $name)) {
                return 'uploads/friends/' . $name;
            }
        }
    }
    return '';
}

$users = loadUsers();
$currentUser = getCurrentUser($users, $_SESSION['email']);
if (!$currentUser) exit('User not found.');
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $friendName = trim($_POST['friendName'] ?? '');
    $friendEmail = strtolower(trim($_POST['friendEmail'] ?? ''));
    $relationshipType = trim($_POST['relationshipTag'] ?? '');
    $howYouMet = trim($_POST['howYouMet'] ?? '');

    if (!$friendName || !$howYouMet || !$friendEmail) {
        $error = 'All required fields must be filled.';
    } elseif (!filter_var($friendEmail, FILTER_VALIDATE_EMAIL)) {
        $error = 'Invalid friend email address.';
    } elseif (!empty($currentUser['friends']) && array_filter($currentUser['friends'], fn($f) => isset($f['email']) && strtolower($f['email']) === $friendEmail)) {
        $error = 'Friend with this email already exists.';
    } else {
        $img = handleProfileImageUpload($_FILES['profileImage'] ?? null);
        $currentUser['friends'][] = [
            'friend_id' => uniqid('f_'),
            'name' => $friendName,
            'email' => $friendEmail,
            'relationship_type' => $relationshipType,
            'how_you_met' => $howYouMet,
            'image_url' => $img,
            'date_added' => date('Y-m-d H:i:s'),
        ];
        saveCurrentUser($users, $currentUser);
        saveUsers($users);
        header('Location: view_friends.php');
        exit();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Add Friend - MemoryBook</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css"  />
    <link rel="stylesheet" href="output.css" />
    <link href="https://fonts.googleapis.com/css2?family=Kalam:wght@400;700&family=Quicksand:wght@400;500;700&display=swap" rel="stylesheet">
    <style>
      html, body { font-family: 'Quicksand', sans-serif; }
      .font-kalam { font-family: 'Kalam', cursive !important; }
    </style>
</head>
<body class="bg-gradient-to-br from-[#F5F3FB] via-[#FDFCFC] to-[#F4F8FD] min-h-screen">
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
<main class="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
    <div class="text-center mb-8">
        <h1 class="text-3xl sm:text-4xl font-semibold text-[#2D2A3D] mb-4">
            Add New Friend
        </h1>
        <p class="text-lg text-[#6B6B7D] max-w-2xl mx-auto">
            Expand your network by capturing the story of your friendship and connection details.
        </p>
    </div>
    <?php if ($error): ?>
        <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>
    <div class="bg-white rounded-2xl shadow p-6 lg:p-8">
        <form id="addFriendForm" class="grid lg:grid-cols-2 gap-8" method="POST" enctype="multipart/form-data">
            <div class="lg:order-1 order-2">
                <div class="text-center">
                    <h3 class="text-lg font-semibold text-[#2D2A3D] mb-6">Profile Picture</h3>
                    <div class="relative inline-block">
                        <div id="imagePreview" class="w-32 h-32 sm:w-40 sm:h-40 bg-[#F5F3FB] rounded-full border-4 border-[#D1C7EB] border-dashed flex items-center justify-center cursor-pointer hover:bg-[#E8E3F5] group">
                            <div id="placeholderContent" class="text-center">
                                <i class="fas fa-camera text-3xl text-[#A394D4] mx-auto mb-2 group-hover:text-[#7A6BB5] transition"></i>
                                <p class="text-sm text-[#7A6BB5] font-medium">Add Photo</p>
                            </div>
                            <img id="selectedImage" class="w-full h-full object-cover rounded-full hidden" alt="Friend profile" />
                        </div>
                        <input type="file" id="profileImage" name="profileImage" accept="image/*" class="hidden" />
                        <button type="button" id="removeImageBtn" class="absolute -top-2 -right-2 w-8 h-8 bg-[#E88B8B] text-white rounded-full hidden hover:bg-[#E47777] transition">
                            <i class="fas fa-times text-sm"></i>
                        </button>
                    </div>
                    <p class="text-sm text-[#6B6B7D] mt-4">
                        Upload a profile picture to personalize your friend's profile
                    </p>
                </div>
            </div>
            <div class="lg:order-2 order-1 space-y-6">
                <div>
                    <label for="userSearchInput" class="block text-sm font-medium text-[#2D2A3D] mb-2">
                        Friend's Name or Email *
                    </label>
                    <input type="text" id="userSearchInput" name="userSearchInput" class="w-full px-4 py-3 border border-[#D1C7EB] rounded-lg focus:ring-2 focus:ring-[#8B7EC8] focus:border-transparent text-[#2D2A3D] placeholder-[#6B6B7D]" placeholder="Type a name or email..." autocomplete="off" />
                    <div id="userSuggestions" class="space-y-2 mt-2 max-h-40 overflow-y-auto"></div>
                </div>
                <div>
                    <label for="friendEmail" class="block text-sm font-medium text-[#2D2A3D] mb-2">
                        Email Address
                    </label>
                    <input type="email" id="friendEmail" name="friendEmail" class="w-full px-4 py-3 border border-[#D1C7EB] rounded-lg focus:ring-2 focus:ring-[#8B7EC8] focus:border-transparent text-[#2D2A3D] placeholder-[#6B6B7D]" placeholder="friend@example.com" />
                </div>
                <input type="hidden" id="friendName" name="friendName" />
                <div>
                    <label for="relationshipTag" class="block text-sm font-medium text-[#2D2A3D] mb-2">
                        Relationship Tag
                    </label>
                    <div class="grid grid-cols-2 gap-3 mb-3">
                        <button type="button" class="relationship-tag px-4 py-2 border-2 border-[#D1C7EB] rounded-lg text-[#6B6B7D] hover:border-[#8B7EC8] hover:text-[#8B7EC8] transition" data-tag="Best Friend">
                            <i class="fab fa-gratipay mr-2"></i> Best Friend
                        </button>
                        <button type="button" class="relationship-tag px-4 py-2 border-2 border-[#D1C7EB] rounded-lg text-[#6B6B7D] hover:border-[#8B7EC8] hover:text-[#8B7EC8] transition" data-tag="Family">
                            <i class="fas fa-users mr-2"></i> Family
                        </button>
                        <button type="button" class="relationship-tag px-4 py-2 border-2 border-[#D1C7EB] rounded-lg text-[#6B6B7D] hover:border-[#8B7EC8] hover:text-[#8B7EC8] transition" data-tag="Colleague">
                            <i class="fab fa-linkedin mr-2"></i> Colleague
                        </button>
                        <button type="button" class="relationship-tag px-4 py-2 border-2 border-[#D1C7EB] rounded-lg text-[#6B6B7D] hover:border-[#8B7EC8] hover:text-[#8B7EC8] transition" data-tag="Acquaintance">
                            <i class="fab fa-meetup mr-2"></i> Acquaintance
                        </button>
                    </div>
                    <input type="text" id="customTag" name="customTag" class="w-full px-4 py-3 border border-[#D1C7EB] rounded-lg focus:ring-2 focus:ring-[#8B7EC8] focus:border-transparent text-[#2D2A3D] placeholder-[#6B6B7D]" placeholder="Or create a custom tag..." />
                    <input type="hidden" id="selectedTag" name="relationshipTag" />
                </div>
            </div>
            <div class="lg:col-span-2 order-3">
                <label for="howYouMet" class="block text-sm font-medium text-[#2D2A3D] mb-2">
                    How You Met *
                </label>
                <textarea id="howYouMet" name="howYouMet" rows="4" required maxlength="500" class="w-full px-4 py-3 border border-[#D1C7EB] rounded-lg focus:ring-2 focus:ring-[#8B7EC8] focus:border-transparent text-[#2D2A3D] placeholder-[#6B6B7D] resize-none" placeholder="Share the story of how you met this friend... What made your first encounter special?"></textarea>
                <div class="flex justify-between items-center mt-2">
                    <div id="storyError" class="text-[#E88B8B] text-sm hidden">Please share how you met this friend</div>
                    <div class="text-sm text-[#6B6B7D]">
                        <span id="charCount">0</span>/500 characters
                    </div>
                </div>
            </div>
            <div class="lg:col-span-2 order-4 flex flex-col sm:flex-row gap-4 pt-6 border-t border-[#E8E3F5]">
                <button type="submit" class="flex-1 bg-[#8B7EC8] text-white px-8 py-4 rounded-xl font-medium hover:bg-[#7A6BB5] transition shadow hover:scale-105">
                    Save Friend
                </button>
                <button type="button" id="resetFormBtn" class="flex-1 bg-white text-[#8B7EC8] px-8 py-4 rounded-xl font-medium hover:bg-[#F5F3FB] transition shadow border border-[#D1C7EB]">
                    Reset Form
                </button>
            </div>
        </form>
    </div>
</main>
<script src="ajax_user_search.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    setupUserSearch('userSearchInput', 'userSuggestions', true);
    const userSearchInput = document.getElementById('userSearchInput');
    const friendName = document.getElementById('friendName');
    const friendEmail = document.getElementById('friendEmail');
    userSearchInput.addEventListener('user-selected', e => {
        friendName.value = e.detail.name;
        friendEmail.value = e.detail.email;
    });
    document.getElementById('addFriendForm').addEventListener('submit', function(e) {
        if (!friendName.value) friendName.value = userSearchInput.value;
    });
    const imgPrev = document.getElementById('imagePreview');
    const imgInput = document.getElementById('profileImage');
    const img = document.getElementById('selectedImage');
    const ph = document.getElementById('placeholderContent');
    const rmBtn = document.getElementById('removeImageBtn');
    imgPrev.addEventListener('click', () => imgInput.click());
    imgInput.addEventListener('change', e => {
        const file = e.target.files[0];
        if (file) {
            const r = new FileReader();
            r.onload = ev => { img.src = ev.target.result; img.classList.remove('hidden'); ph.classList.add('hidden'); rmBtn.classList.remove('hidden'); };
            r.readAsDataURL(file);
        }
    });
    rmBtn.addEventListener('click', function() {
        imgInput.value = '';
        img.classList.add('hidden');
        ph.classList.remove('hidden');
        rmBtn.classList.add('hidden');
    });
    document.querySelectorAll('.relationship-tag').forEach(tag => {
        tag.addEventListener('click', function() {
            document.querySelectorAll('.relationship-tag').forEach(t => t.classList.remove('border-[#8B7EC8]', 'bg-[#F5F3FB]', 'text-[#8B7EC8]'));
            this.classList.add('border-[#8B7EC8]', 'bg-[#F5F3FB]', 'text-[#8B7EC8]');
            document.getElementById('selectedTag').value = this.dataset.tag;
            document.getElementById('customTag').value = '';
        });
    });
    document.getElementById('customTag').addEventListener('input', function() {
        if (this.value) {
            document.querySelectorAll('.relationship-tag').forEach(t => t.classList.remove('border-[#8B7EC8]', 'bg-[#F5F3FB]', 'text-[#8B7EC8]'));
            document.getElementById('selectedTag').value = this.value;
        }
    });
});
</script>
</body>
</html>
