<?php
session_start();
require_once __DIR__ . '/../data_structures/user_search.php';
if (!isset($_SESSION['email'])) {
    header('Location: login.php');
    exit();
}

$currentUserEmail = $_SESSION['email'];
$currentUsername = isset($_SESSION['username']) ? $_SESSION['username'] : '';
$allUsersRaw = get_all_users_flat();
$allUsers = [];
foreach ($allUsersRaw as $u) {
    if ($u['email'] !== $currentUserEmail) {
        $allUsers[] = $u;
    }
}
$searchQuery = isset($_POST['query']) ? $_POST['query'] : '';
$selectedUserEmail = isset($_POST['selected_user_email']) ? $_POST['selected_user_email'] : '';
$selectedMemoryId = isset($_POST['selected_memory_id']) ? $_POST['selected_memory_id'] : '';

$results = [];
if ($searchQuery) {
    $results = search_users_by_query($searchQuery, $allUsers, 10);
}

$selectedUser = null;
foreach ($allUsers as $u) {
    if ($u['email'] === $selectedUserEmail) {
        $selectedUser = $u;
        break;
    }
}

$memoriesFile = __DIR__ . '/../data/memories.json';
$allMemories = file_exists($memoriesFile) ? json_decode(file_get_contents($memoriesFile), true) : [];
$userMemories = [];
foreach ($allMemories as $m) {
    if (isset($m['owner']) && $m['owner'] === $currentUserEmail) {
        $userMemories[] = $m;
    }
}

$selectedMemory = null;
foreach ($userMemories as $m) {
    if ($m['memory_id'] === $selectedMemoryId) {
        $selectedMemory = $m;
        break;
    }
}

$sharedMemoriesFile = __DIR__ . '/../data/shared_memories.json';
$allShared = file_exists($sharedMemoriesFile) ? json_decode(file_get_contents($sharedMemoriesFile), true) : [];
$recentShared = [];
for ($i = count($allShared) - 1; $i >= 0; $i--) {
    $m = $allShared[$i];
    if (isset($m['from']) && $m['from'] === $currentUserEmail) {
        $recentShared[] = $m;
        if (count($recentShared) >= 3) break;
    }
}

$success = false;
$error = '';
$validationErrors = [];
if ($selectedUser && isset($_POST['memory_title'], $_POST['message'], $_POST['date'])) {
    $memoryTitle = trim($_POST['memory_title']);
    $message = trim($_POST['message']);
    $date = trim($_POST['date']);
    $attachments = [];
    $imagePath = $selectedMemory['image'] ?? null;

    
    if ($memoryTitle === '') $validationErrors['memory_title'] = 'Title is required.';
    if ($message === '') $validationErrors['message'] = 'Message is required.';
    if ($date === '') $validationErrors['date'] = 'Date is required.';

    if (isset($_FILES['attachments']) && is_array($_FILES['attachments']['name'])) {
        $fileCount = count($_FILES['attachments']['name']);
        for ($i = 0; $i < $fileCount; $i++) {
            if ($_FILES['attachments']['error'][$i] === UPLOAD_ERR_OK) {
                $file = [
                    'name' => $_FILES['attachments']['name'][$i],
                    'tmp_name' => $_FILES['attachments']['tmp_name'][$i],
                    'size' => $_FILES['attachments']['size'][$i],
                ];
                $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
                if (in_array($ext, ['jpg','jpeg','png','gif','webp','mp4','mov','avi','mp3','wav','ogg','m4a'])) {
                    $unique_name = 'shared_' . uniqid() . '.' . $ext;
                    $target_path = __DIR__ . '/../uploads/' . $unique_name;
                    if (move_uploaded_file($file['tmp_name'], $target_path)) {
                        $attachments[] = 'uploads/' . $unique_name;
                    } else {
                        $validationErrors['attachments'] = 'Failed to upload file.';
                    }
                } else {
                    $validationErrors['attachments'] = 'Only image, video, or audio files are allowed.';
                }
            }
        }
    }

    if (!$validationErrors) {
        $newMemory = $selectedMemory ? $selectedMemory : [];
        $newMemory['from'] = $currentUserEmail;
        $newMemory['from_username'] = $currentUsername;
        $newMemory['to'] = $selectedUser['email'];
        $newMemory['to_username'] = $selectedUser['username'];
        $newMemory['memory_id'] = uniqid('m_');
        $newMemory['original_memory_id'] = $selectedMemory['memory_id'] ?? null;
        $newMemory['memory_title'] = $memoryTitle;
        $newMemory['message'] = $message;
        $newMemory['date'] = $date;
        $newMemory['seen'] = false;
        $newMemory['attachments'] = $attachments;
        if ($imagePath) $newMemory['image'] = $imagePath;
        $allShared[] = $newMemory;
        if (file_put_contents($sharedMemoriesFile, json_encode($allShared, JSON_PRETTY_PRINT))) {
            header('Location: dashboard.php');
            exit();
        } else {
            $error = 'Failed to save memory.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Share a Memory - MemoryBook</title>
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" crossorigin="anonymous" referrerpolicy="no-referrer" />
  <link rel="stylesheet" href="blobs.css" />
  <link rel="stylesheet" href="output.css" />
  <link href="https://fonts.googleapis.com/css2?family=Kalam:wght@400;700&family=Quicksand:wght@400;500;700&display=swap" rel="stylesheet">
  <style>
    html, body { font-family: 'Quicksand', sans-serif; }
    .font-kalam { font-family: 'Kalam', cursive !important; }
  </style>
  <script src="ajax_user_search.js"></script>
</head>
<body class="bg-[#FDE8E8]/60 min-h-screen">
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
<main class="flex flex-col items-center justify-center min-h-[80vh] py-8 px-2">
  <div class="w-full max-w-xl">
    <div class="bg-white rounded-2xl shadow-lg border border-[#F4A6A6]/30 p-8 mb-8">
      <h1 class="text-3xl font-bold text-center text-[#8B7EC8] mb-6 flex items-center justify-center gap-2">
        <i class="fas fa-share-alt"></i> Share a Memory
      </h1>
      <?php if ($success): ?>
        <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded-xl mb-6 text-center">
          Memory shared successfully!
        </div>
      <?php endif; ?>
      <?php if ($error): ?>
        <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded-xl mb-6 text-center">
          <?= htmlspecialchars($error) ?>
        </div>
      <?php endif; ?>
      <?php if (!$selectedUser): ?>
        <h2 class="text-xl font-semibold text-[#2D2A3D] mb-4">Step 1: Choose a Friend</h2>
        <form method="POST" class="space-y-6" autocomplete="off" id="user-search-form">
          <label for="userSearchInput" class="block text-sm font-medium text-[#2D2A3D]">Search for a user</label>
          <input id="userSearchInput" type="text" placeholder="Type username or email..." class="rounded-xl border border-gray-200 p-3 focus:ring-2 focus:ring-[#F4A6A6] focus:border-[#F4A6A6] w-full" autocomplete="off" autofocus />
          <div id="userSuggestions" class="space-y-2 mt-2 max-h-40 overflow-y-auto"></div>
          <input type="hidden" name="selected_user_email" id="selectedUserEmail" />
          <div class="flex justify-end">
            <button type="submit" class="px-10 py-3 rounded-xl bg-[#F4A6A6] text-white font-semibold shadow hover:bg-[#D1C7EB] hover:text-[#2D2A3D] focus:outline-none focus:ring-2 focus:ring-[#F4A6A6] transition">
              Continue
            </button>
          </div>
        </form>
        <script src="ajax_user_search.js"></script>
        <script>
        setupUserSearch('userSearchInput', 'userSuggestions', true);
        document.getElementById('userSearchInput').addEventListener('user-selected', function(e) {
          document.getElementById('userSearchInput').value = e.detail.name + ' (' + e.detail.email + ')';
          document.getElementById('selectedUserEmail').value = e.detail.email;
        });
        document.getElementById('user-search-form').addEventListener('submit', function(e) {
          if (!document.getElementById('selectedUserEmail').value) {
            e.preventDefault();
            alert('Please select a user from the suggestions.');
          }
        });
        </script>
      <?php else: ?>
        <h2 class="text-xl font-semibold text-[#2D2A3D] mb-4">Step 2: Share a Memory with <span class="text-[#8B7EC8]"><?= htmlspecialchars($selectedUser['username']) ?></span></h2>
        <div class="flex items-center gap-4 mb-4 p-4 rounded-xl bg-[#F5F3FB] border border-[#F4A6A6]/30">
          <div>
            <div class="font-semibold text-[#8B7EC8] text-lg">@<?= htmlspecialchars($selectedUser['username']) ?></div>
            <div class="text-xs text-gray-500"><?= htmlspecialchars($selectedUser['email']) ?></div>
          </div>
        </div>
        <form class="space-y-6" method="POST" autocomplete="off" enctype="multipart/form-data">
          <input type="hidden" name="selected_user_email" value="<?= htmlspecialchars($selectedUser['email']) ?>" />
          <div>
            <label class="block text-[#2D2A3D] font-semibold mb-2">Select a Memory to Share <span class="text-gray-400">(optional)</span></label>
            <select name="selected_memory_id" class="rounded-xl border border-gray-200 p-3 focus:ring-2 focus:ring-[#F4A6A6] focus:border-[#F4A6A6] w-full">
              <option value="">-- Choose a memory (optional) --</option>
              <?php foreach ($userMemories as $m): ?>
                <option value="<?= htmlspecialchars($m['memory_id']) ?>" <?= $selectedMemoryId === $m['memory_id'] ? 'selected' : '' ?>>
                  <?= htmlspecialchars($m['title'] ?? '(No Title)') ?> (<?= htmlspecialchars($m['date'] ?? '') ?>)
                </option>
              <?php endforeach; ?>
            </select>
          </div>
          <?php if ($selectedMemory): ?>
            <div class="border rounded-xl p-4 bg-[#E6F1FB] mb-2">
              <?php if (!empty($selectedMemory['image'])): ?>
                <img src="<?php echo (!empty($selectedMemory['image']) && strpos($selectedMemory['image'], 'uploads/') === 0) ? '/memorybook/' . htmlspecialchars($selectedMemory['image']) : 'https://ui-avatars.com/api/?name=' . urlencode($selectedMemory['title'] ?? 'Memory'); ?>" class="w-32 h-32 object-cover rounded-xl border mb-2 mx-auto" alt="Memory Image">
              <?php endif; ?>
              <div class="font-bold text-lg text-[#8B7EC8] mb-1"><?= htmlspecialchars($selectedMemory['title'] ?? '') ?></div>
              <div class="text-gray-500 text-sm mb-1"><?= htmlspecialchars($selectedMemory['date'] ?? '') ?></div>
              <div class="text-gray-700 text-base mb-1"><?= nl2br(htmlspecialchars($selectedMemory['description'] ?? '')) ?></div>
            </div>
          <?php endif; ?>
          <div>
            <label class="block text-[#2D2A3D] font-semibold mb-2">Memory Title</label>
            <input type="text" name="memory_title" class="rounded-xl border border-gray-200 p-3 focus:ring-2 focus:ring-[#F4A6A6] focus:border-[#F4A6A6] w-full <?php if(isset($validationErrors['memory_title'])) echo 'border-red-400'; ?>" placeholder="Memory Title" required value="<?= isset($_POST['memory_title']) ? htmlspecialchars($_POST['memory_title']) : ($selectedMemory['title'] ?? '') ?>">
            <?php if(isset($validationErrors['memory_title'])): ?><div class="text-red-500 text-xs mt-1"><?= $validationErrors['memory_title'] ?></div><?php endif; ?>
          </div>
          <div>
            <label class="block text-[#2D2A3D] font-semibold mb-2">Message/Description</label>
            <textarea name="message" class="rounded-xl border border-gray-200 p-3 focus:ring-2 focus:ring-[#F4A6A6] focus:border-[#F4A6A6] w-full <?php if(isset($validationErrors['message'])) echo 'border-red-400'; ?>" rows="4" placeholder="Describe your memory..." required><?= isset($_POST['message']) ? htmlspecialchars($_POST['message']) : ($selectedMemory['description'] ?? '') ?></textarea>
            <?php if(isset($validationErrors['message'])): ?><div class="text-red-500 text-xs mt-1"><?= $validationErrors['message'] ?></div><?php endif; ?>
          </div>
          <div>
            <label class="block text-[#2D2A3D] font-semibold mb-2">Date</label>
            <input type="date" name="date" class="rounded-xl border border-gray-200 p-3 focus:ring-2 focus:ring-[#F4A6A6] focus:border-[#F4A6A6] w-full <?php if(isset($validationErrors['date'])) echo 'border-red-400'; ?>" required value="<?= isset($_POST['date']) ? htmlspecialchars($_POST['date']) : ($selectedMemory['date'] ?? '') ?>">
            <?php if(isset($validationErrors['date'])): ?><div class="text-red-500 text-xs mt-1"><?= $validationErrors['date'] ?></div><?php endif; ?>
          </div>
          <div>
            <label class="block text-[#2D2A3D] font-semibold mb-2">Attachment (optional)</label>
            <input type="file" name="attachments[]" class="rounded-xl border border-gray-200 p-3 focus:ring-2 focus:ring-[#F4A6A6] focus:border-[#F4A6A6] w-full" accept="image/*,video/*,audio/*" multiple>
            <?php if(isset($validationErrors['attachments'])): ?><div class="text-red-500 text-xs mt-1"><?= $validationErrors['attachments'] ?></div><?php endif; ?>
          </div>
          <div class="flex justify-end mt-2">
            <button type="submit" class="px-10 py-3 rounded-xl bg-[#F4A6A6] text-white font-semibold shadow hover:bg-[#D1C7EB] hover:text-[#2D2A3D] focus:outline-none focus:ring-2 focus:ring-[#F4A6A6] transition text-lg flex items-center gap-2">
              Share Memory
            </button>
          </div>
        </form>
        <form method="POST" class="mt-4 text-center">
          <button type="submit" class="text-[#8B7EC8] underline">&larr; Search for a different user</button>
        </form>
      <?php endif; ?>
    </div>
    <div class="bg-white rounded-2xl shadow-lg border border-[#F4A6A6]/30 p-8 mt-8">
      <h2 class="text-lg font-bold text-[#8B7EC8] mb-4 flex items-center gap-2"><i class="fas fa-history"></i> Recently Shared</h2>
      <?php if (empty($recentShared)): ?>
        <div class="text-gray-400 text-center">You haven’t shared any memories yet.</div>
      <?php else: ?>
        <ul class="space-y-4">
          <?php foreach ($recentShared as $mem): ?>
            <?php
              $targetId = !empty($mem['original_memory_id']) ? $mem['original_memory_id'] : $mem['memory_id'];
              $galleryId = 'media-gallery-' . htmlspecialchars($mem['memory_id']);
            ?>
            <li class="flex items-center gap-3 p-0 rounded-xl bg-[#F5F3FB] hover:bg-[#E6F1FB] transition border border-[#F4A6A6]/30 cursor-pointer recent-shared-item" data-to="<?= htmlspecialchars($mem['to']) ?>" onclick="window.location.href='view_shared_memory.php?id=<?= urlencode($targetId) ?>'">
              <div class="flex-1 p-4 relative">
                <div class="font-semibold text-[#8B7EC8]">To: <?= htmlspecialchars($mem['to_username'] ?: $mem['to']) ?></div>
                <div class="text-sm text-gray-500"><?= htmlspecialchars($mem['memory_title'] ?? '(No Title)') ?></div>
                <div class="text-xs text-gray-400"><?= htmlspecialchars($mem['date'] ?? '') ?></div>
                <div class="text-xs text-gray-600 mt-1 line-clamp-2" style="max-width:200px;overflow:hidden;white-space:nowrap;text-overflow:ellipsis;">
                  <?= htmlspecialchars(mb_substr($mem['message'] ?? '', 0, 60)) ?><?= strlen($mem['message'] ?? '') > 60 ? '...' : '' ?>
                </div>
                <?php if (!empty($mem['attachments'])): ?>
                  <div class="media-gallery mt-2" id="<?= $galleryId ?>">
                    <?php foreach ($mem['attachments'] as $idx => $attachment):
                      $ext = strtolower(pathinfo($attachment, PATHINFO_EXTENSION));
                      $type = 'other';
                      if (in_array($ext, ['jpg','jpeg','png','gif','webp'])) $type = 'image';
                      elseif (in_array($ext, ['mp4','mov','avi','webm'])) $type = 'video';
                      elseif (in_array($ext, ['mp3','wav','ogg','aac','m4a'])) $type = 'audio';
                    ?>
                      <div class="media-slide" data-index="<?= $idx ?>" style="<?= $idx === 0 ? '' : 'display:none;' ?>">
                        <?php if ($type === 'image'): ?>
                          <img src="<?= htmlspecialchars($attachment) ?>" alt="Attachment" class="max-h-32 rounded-lg mb-2" />
                        <?php elseif ($type === 'video'): ?>
                          <video src="<?= htmlspecialchars($attachment) ?>" controls class="max-h-32 rounded-lg mb-2"></video>
                        <?php elseif ($type === 'audio'): ?>
                          <audio src="<?= htmlspecialchars($attachment) ?>" controls class="mb-2"></audio>
                        <?php else: ?>
                          <div class="flex flex-col items-center mb-2"><i class="fa fa-file-alt text-2xl text-[#8B7EC8] mb-1"></i><span class="text-xs text-[#6B6B7D]"><?= htmlspecialchars(basename($attachment)) ?></span></div>
                        <?php endif; ?>
                        <button class="download-btn px-2 py-1 bg-[#8B7EC8] text-white rounded text-xs font-semibold hover:bg-[#6BCB77] transition flex items-center gap-2" data-url="<?= htmlspecialchars($attachment) ?>" data-name="<?= htmlspecialchars(basename($attachment)) ?>">
                          <i class="fa fa-download"></i> Download
                        </button>
                      </div>
                    <?php endforeach; ?>
                    <div class="flex gap-2 mt-2">
                      <button class="prev-btn px-2 py-1 bg-[#ede9fe] text-[#7c3aed] rounded font-semibold">Prev</button>
                      <button class="next-btn px-2 py-1 bg-[#ede9fe] text-[#7c3aed] rounded font-semibold">Next</button>
                    </div>
                  </div>
                <?php endif; ?>
              </div>
            </li>
            <script>document.addEventListener('DOMContentLoaded',function(){initMediaGallery('<?= $galleryId ?>');});</script>
          <?php endforeach; ?>
        </ul>
      <?php endif; ?>
    </div>
  </div>
</main>
</body>
</html> 
