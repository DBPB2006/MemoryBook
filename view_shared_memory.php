<?php
session_start();
require_once __DIR__ . '/../data_structures/common_functions.php';

if (!isset($_SESSION['email'])) {
    header('Location: login.php');
    exit();
}

$currentUserEmail = $_SESSION['email'];
$currentUsername = $_SESSION['username'] ?? '';
$memoryId = $_GET['id'] ?? '';

$sharedMemoriesFile = __DIR__ . '/../data/shared_memories.json';
$allShared = file_exists($sharedMemoriesFile) ? json_decode(file_get_contents($sharedMemoriesFile), true) : [];

// Get all received shared memories for sidebar navigation
$receivedMemories = array_values(array_filter($allShared, function($m) use ($currentUserEmail) {
    return isset($m['to']) && $m['to'] === $currentUserEmail;
}));

// Finds the shared memory for this original memory id
$sharedMemory = null;
$currentIndex = -1;
foreach ($receivedMemories as $i => $mem) {
    if (isset($mem['original_memory_id']) && $mem['original_memory_id'] === $memoryId) {
        $sharedMemory = $mem;
        $currentIndex = $i;
        break;
    }
}
if (!$sharedMemory) {
    foreach ($receivedMemories as $i => $mem) {
        if (isset($mem['memory_id']) && $mem['memory_id'] === $memoryId) {
            $sharedMemory = $mem;
            $currentIndex = $i;
            break;
        }
    }
}

// Marks as seen
if ($sharedMemory && (empty($sharedMemory['seen']) || $sharedMemory['seen'] === false)) {
    foreach ($allShared as &$mem) {
        if ($mem['memory_id'] === $sharedMemory['memory_id']) {
            $mem['seen'] = true;
            break;
        }
    }
    file_put_contents($sharedMemoriesFile, json_encode($allShared, JSON_PRETTY_PRINT));
}

$error = '';
if (!$sharedMemory) {
    $error = 'Shared memory not found, or you are not authorized to view it.';
}
$prevMemory = $receivedMemories[$currentIndex - 1] ?? null;
$nextMemory = $receivedMemories[$currentIndex + 1] ?? null;


if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['download'])) {
    $filename = basename($_POST['download']); // Prevent path traversal
    $filepath = __DIR__ . '/../uploads/' . $filename;
    if (file_exists($filepath)) {
        header('Content-Description: File Transfer');
        header('Content-Type: application/octet-stream');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Content-Length: ' . filesize($filepath));
        readfile($filepath);
        exit;
    } else {
        die("File not found.");
    }
}
$allAttachments = [];
if (!empty($sharedMemory['attachments']) && is_array($sharedMemory['attachments'])) {
    foreach ($sharedMemory['attachments'] as $att) {
        if ($att) $allAttachments[] = $att;
    }
}
if (!empty($sharedMemory['attachment']) && is_string($sharedMemory['attachment'])) {
    $allAttachments[] = $sharedMemory['attachment'];
}
$displayedFiles = [];
foreach ($allAttachments as $attachment) {
    $attachmentFile = basename($attachment);
    if (in_array($attachmentFile, $displayedFiles)) continue; // avoid duplicates
    $displayedFiles[] = $attachmentFile;
}


$mainMedia = [];
$mainOthers = [];
if (!empty($sharedMemory['image'])) {
    $imgFile = basename($sharedMemory['image']);
    $imgWebPath = (strpos($sharedMemory['image'], 'uploads/') === 0)
        ? '/memorybook/' . htmlspecialchars($sharedMemory['image'])
        : 'https://ui-avatars.com/api/?name=' . urlencode($sharedMemory['memory_title'] ?? 'Memory');
    if (file_exists(__DIR__ . '/../uploads/' . $imgFile)) {
        $ext = strtolower(pathinfo($imgFile, PATHINFO_EXTENSION));
        if (in_array($ext, ['jpg', 'jpeg', 'png', 'gif', 'webp'])) {
            $mainMedia[] = [ 'type' => 'image', 'src' => $imgWebPath, 'file' => $imgFile, 'name' => 'Memory Image' ];
        } elseif (in_array($ext, ['mp4', 'mov', 'avi', 'webm'])) {
            $mainMedia[] = [ 'type' => 'video', 'src' => $imgWebPath, 'file' => $imgFile, 'name' => 'Memory Video' ];
        } elseif (in_array($ext, ['mp3', 'wav', 'ogg', 'aac', 'm4a'])) {
            $mainMedia[] = [ 'type' => 'audio', 'src' => $imgWebPath, 'file' => $imgFile, 'name' => 'Memory Audio' ];
        } else {
            $mainOthers[] = [ 'file' => $imgFile, 'name' => $imgFile ];
        }
    }
}
$attMedia = [];
$attOthers = [];
$displayedFiles = [];
foreach ($allAttachments as $attachment) {
    $attachmentFile = basename($attachment);
    if (in_array($attachmentFile, $displayedFiles)) continue;
    $displayedFiles[] = $attachmentFile;
    $attachmentPath = (strpos($attachment, 'uploads/') !== false) ? '/memorybook/uploads/' . $attachmentFile : htmlspecialchars($attachment);
    $ext = strtolower(pathinfo($attachmentFile, PATHINFO_EXTENSION));
    $fileExists = file_exists(__DIR__ . '/../uploads/' . $attachmentFile);
    if (!$fileExists) continue;
    if (in_array($ext, ['jpg', 'jpeg', 'png', 'gif', 'webp'])) {
        $attMedia[] = [ 'type' => 'image', 'src' => $attachmentPath, 'file' => $attachmentFile, 'name' => 'Attachment Image' ];
    } elseif (in_array($ext, ['mp4', 'mov', 'avi', 'webm'])) {
        $attMedia[] = [ 'type' => 'video', 'src' => $attachmentPath, 'file' => $attachmentFile, 'name' => 'Attachment Video' ];
    } elseif (in_array($ext, ['mp3', 'wav', 'ogg', 'aac', 'm4a'])) {
        $attMedia[] = [ 'type' => 'audio', 'src' => $attachmentPath, 'file' => $attachmentFile, 'name' => 'Attachment Audio' ];
    } else {
        $attOthers[] = [ 'file' => $attachmentFile, 'name' => $attachmentFile ];
    }
}

// Friend name lookup
$usersData = loadUsers(__DIR__ . '/../data/users.json');
$friendIdToName = [];
foreach ($usersData as $bucket) {
    foreach ($bucket as $user) {
        if (!empty($user['friends']) && is_array($user['friends'])) {
            foreach ($user['friends'] as $f) {
                if (!empty($f['friend_id'])) {
                    $friendIdToName[$f['friend_id']] = $f['name'] ?? $f['friend_id'];
                }
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Shared Memory - MemoryBook</title>
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" crossorigin="anonymous" referrerpolicy="no-referrer" />
  <link rel="stylesheet" href="blobs.css" />
  <link rel="stylesheet" href="output.css" />
  <link href="https://fonts.googleapis.com/css2?family=Kalam:wght@400;700&family=Quicksand:wght@400;500;700&display=swap" rel="stylesheet">
  <style>
    html, body { font-family: 'Quicksand', sans-serif; }
    .node-active { box-shadow: 0 0 0 4px #ede9fa; border: 3px solid #8B7EC8 !important; }
    .node-inactive { border: 2px solid #e0e0f0; }
    .gallery-img { transition: box-shadow 0.2s; }
    .gallery-img:hover { box-shadow: 0 8px 24px rgba(139,126,200,0.18); }
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
    <a href="shared_memories.php" class="text-[#8B7EC8] font-semibold hover:underline">&larr; Back to Shared Memories</a>
    <a href="login.php?logout=1" class="text-[#F48498] hover:text-[#8B7EC8] font-medium">Logout</a>
  </nav>
</header>
<div class="flex min-h-screen pt-20">
  <aside class="w-24 flex flex-col items-center py-8 px-2 z-10 bg-[#F5F3FB] rounded-tr-3xl rounded-br-3xl shadow-lg border-r border-[#E8E3F5]">
    <div class="flex flex-col gap-4 items-center w-full">
      <?php foreach ($receivedMemories as $i => $m): 
        $isActive = (isset($m['original_memory_id']) && $m['original_memory_id'] === $memoryId) || (isset($m['memory_id']) && $m['memory_id'] === $memoryId);
        $initials = htmlspecialchars(mb_substr($m['memory_title'] ?? $m['original_memory_id'] ?? 'M', 0, 2));
      ?>
        <a href="view_shared_memory.php?id=<?= urlencode($m['original_memory_id'] ?? $m['memory_id']) ?>"
           class="flex flex-col items-center w-full py-2 rounded-xl transition <?php if($isActive) echo 'bg-[#8B7EC8]/20 border-l-4 border-[#8B7EC8]'; else echo 'hover:bg-[#A8C8EC]/10'; ?>"
           title="<?= htmlspecialchars($m['memory_title'] ?? $m['original_memory_id'] ?? 'Shared Memory') ?>">
          <div class="w-10 h-10 rounded-full bg-[#A8C8EC] flex items-center justify-center text-lg font-bold text-white border-2 border-[#8B7EC8] mb-1">
            <?= $initials ?>
          </div>
          <span class="text-xs text-[#8B7EC8] font-semibold truncate w-16 text-center"><?= htmlspecialchars($m['memory_title'] ?? 'M') ?></span>
        </a>
      <?php endforeach; ?>
    </div>
  </aside>
  <main class="flex-1 flex flex-col items-center justify-center px-2 py-8">
    <div class="w-full max-w-4xl mx-auto flex flex-col gap-8 items-center">
      <?php if ($error): ?>
        <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded-xl mb-6 text-center w-full">
          <?= htmlspecialchars($error) ?>
        </div>
      <?php else: ?>
      <div class="w-full flex flex-col md:flex-row gap-8">
        <section class="flex-1 bg-white/90 rounded-3xl shadow-2xl p-8 flex flex-col gap-4 min-h-[520px]">
          <div class="flex items-center gap-4 mb-2">
            <div class="w-16 h-16 rounded-full bg-[#A8C8EC] flex items-center justify-center text-2xl font-bold text-white border-4 border-[#8B7EC8]">
              <i class="fa-solid fa-book-open"></i>
            </div>
            <div>
              <h1 class="text-3xl font-bold text-gray-900 leading-tight mb-1">
                <?= htmlspecialchars($sharedMemory['memory_title'] ?? 'Memory') ?>
              </h1>
              <div class="flex items-center text-gray-500 text-base mb-1">
                <i class="fa-regular fa-calendar mr-2"></i>
                <?= htmlspecialchars($sharedMemory['date'] ?? '') ?>
              </div>
              <?php if (!empty($sharedMemory['mood'])): ?>
                <div class="flex items-center text-gray-500 text-base mb-1">
                  <i class="fas fa-smile mr-2"></i> <?= htmlspecialchars($sharedMemory['mood']) ?>
                </div>
              <?php endif; ?>
              <?php if (!empty($sharedMemory['location'])): ?>
                <div class="flex items-center text-gray-500 text-base mb-1">
                  <i class="fa-solid fa-location-dot mr-2"></i>
                  <a href="https://www.google.com/maps/search/<?= urlencode($sharedMemory['location']) ?>" target="_blank" rel="noopener noreferrer" class="text-[#8B7EC8] hover:underline">
                    <?= htmlspecialchars($sharedMemory['location']) ?>
                  </a>
                </div>
              <?php endif; ?>
            </div>
          </div>
          <div class="mb-2">
            <span class="font-semibold text-gray-700">Description</span>
            <div class="text-gray-700 mt-1 text-base leading-relaxed bg-[#F5F3FB] rounded-xl p-4 border border-[#E8E3F5]">
              <?= nl2br(htmlspecialchars($sharedMemory['description'] ?? $sharedMemory['message'] ?? '')) ?>
            </div>
          </div>
          <?php if (!empty($sharedMemory['message'])): ?>
            <div class="mb-2">
              <span class="font-semibold text-gray-700">Shared Message</span>
              <div class="text-gray-700 mt-1 text-base leading-relaxed">
                <?= nl2br(htmlspecialchars($sharedMemory['message'])) ?>
              </div>
            </div>
          <?php endif; ?>
          <?php if (!empty($sharedMemory['tags'])): ?>
            <div class="flex flex-wrap gap-2 mb-2">
              <?php foreach (is_array($sharedMemory['tags']) ? $sharedMemory['tags'] : [$sharedMemory['tags']] as $tag): ?>
                <span class="inline-block px-3 py-1 rounded-full bg-[#A8C8EC] text-white text-xs font-medium">#<?= htmlspecialchars($tag) ?></span>
              <?php endforeach; ?>
            </div>
          <?php endif; ?>
          <?php if (!empty($sharedMemory['friends'])): ?>
            <?php
            $friendList = is_array($sharedMemory['friends']) ? $sharedMemory['friends'] : [$sharedMemory['friends']];
            $friendNames = [];
            foreach ($friendList as $fid) {
                $friendNames[] = $friendIdToName[$fid] ?? $fid;
            }
            ?>
            <div class="text-gray-600"><strong>Shared with:</strong> <?= htmlspecialchars(implode(', ', $friendNames)) ?></div>
          <?php endif; ?>
          <?php if (!empty($sharedMemory['username'])): ?>
            <div class="text-gray-600"><strong>Created by:</strong> <?= htmlspecialchars($sharedMemory['username']) ?></div>
          <?php endif; ?>
        </section>
        <section class="flex-1 bg-white rounded-3xl shadow-lg p-8 flex flex-col gap-6 min-h-[520px]">
          <h2 class="text-xl font-semibold text-[#8B7EC8] mb-2 flex items-center gap-2"><i class="fa-regular fa-images"></i> Main Memory Media</h2>
          <div class="w-full flex flex-col items-center mb-6">
            <?php if (!empty($mainMedia)): ?>
              <div id="main-media-gallery" class="w-full flex flex-col items-center">
                <?php foreach ($mainMedia as $i => $media): ?>
                  <div class="main-media-slide" data-index="<?= $i ?>" style="<?= $i === 0 ? '' : 'display:none;' ?>">
                    <?php if ($media['type'] === 'image'): ?>
                      <img src="<?= $media['src'] ?>" alt="<?= htmlspecialchars($media['name']) ?>" class="gallery-img w-full max-w-lg h-auto max-h-[400px] object-contain rounded-xl shadow border-2 border-[#8B7EC8] bg-white mb-2" />
                    <?php elseif ($media['type'] === 'video'): ?>
                      <video src="<?= $media['src'] ?>" controls class="w-full max-w-lg h-auto max-h-[400px] rounded-xl shadow border-2 border-[#8B7EC8] bg-black mb-2"></video>
                    <?php elseif ($media['type'] === 'audio'): ?>
                      <audio src="<?= $media['src'] ?>" controls class="w-full max-w-lg mb-2"></audio>
                    <?php endif; ?>
                    <form method="post" action="" target="_blank">
                      <input type="hidden" name="download" value="<?= htmlspecialchars($media['file']) ?>" />
                      <button type="submit" class="px-3 py-1 bg-[#8B7EC8] text-white rounded-md text-xs font-semibold hover:bg-[#6BCB77] transition flex items-center gap-2">
                        <i class="fa fa-download"></i> Download <?= ucfirst($media['type']) ?>
                      </button>
                    </form>
                  </div>
                <?php endforeach; ?>
                <?php if (count($mainMedia) > 1): ?>
                <div class="flex justify-center gap-4 mt-4">
                  <button id="main-media-prev" class="px-4 py-2 rounded-full bg-[#E8E3F5] text-[#8B7EC8] font-bold shadow hover:bg-[#8B7EC8] hover:text-white transition"><i class="fa fa-chevron-left"></i></button>
                  <button id="main-media-next" class="px-4 py-2 rounded-full bg-[#E8E3F5] text-[#8B7EC8] font-bold shadow hover:bg-[#8B7EC8] hover:text-white transition"><i class="fa fa-chevron-right"></i></button>
                </div>
                <script>
                  const mainMediaSlides = document.querySelectorAll('.main-media-slide');
                  let mainMediaCurrent = 0;
                  function showMainMediaSlide(idx) {
                    mainMediaSlides.forEach((el, i) => { el.style.display = (i === idx) ? '' : 'none'; });
                  }
                  function prevMainMediaSlide() {
                    mainMediaCurrent = (mainMediaCurrent - 1 + mainMediaSlides.length) % mainMediaSlides.length;
                    showMainMediaSlide(mainMediaCurrent);
                  }
                  function nextMainMediaSlide() {
                    mainMediaCurrent = (mainMediaCurrent + 1) % mainMediaSlides.length;
                    showMainMediaSlide(mainMediaCurrent);
                  }
                  document.getElementById('main-media-prev').onclick = prevMainMediaSlide;
                  document.getElementById('main-media-next').onclick = nextMainMediaSlide;
                  showMainMediaSlide(mainMediaCurrent);
                </script>
                <?php endif; ?>
              </div>
            <?php else: ?>
              <div class="text-center text-gray-400">No media files.</div>
            <?php endif; ?>
          </div>
          <?php if (!empty($mainOthers)): ?>
          <div class="mt-8">
            <h3 class="text-base font-semibold text-[#8B7EC8] mb-2 flex items-center gap-2"><i class="fa fa-paperclip"></i> Other Files</h3>
            <div class="flex flex-col gap-4">
              <?php foreach ($mainOthers as $file): ?>
                <div class="flex flex-col items-center">
                  <div class="w-full flex items-center justify-center h-32 bg-[#F5F3FB] rounded-xl border border-[#E8E3F5] mb-2">
                    <i class="fa fa-file-alt text-4xl text-[#8B7EC8]"></i>
                  </div>
                  <span class="text-sm text-gray-700 mb-1"><?= htmlspecialchars($file['name']) ?></span>
                  <form method="post" action="" target="_blank">
                    <input type="hidden" name="download" value="<?= htmlspecialchars($file['file']) ?>" />
                    <button type="submit" class="px-3 py-1 bg-[#8B7EC8] text-white rounded-md text-xs font-semibold hover:bg-[#6BCB77] transition flex items-center gap-2">
                      <i class="fa fa-download"></i> Download File
                    </button>
                  </form>
                </div>
              <?php endforeach; ?>
            </div>
          </div>
          <?php endif; ?>
          <hr class="my-8 border-[#E8E3F5]" />
          <h2 class="text-xl font-semibold text-[#8B7EC8] mb-2 flex items-center gap-2"><i class="fa-regular fa-images"></i> Attachments Media</h2>
          <div class="w-full flex flex-col items-center mb-6">
            <?php if (!empty($attMedia)): ?>
              <div id="att-media-gallery" class="w-full flex flex-col items-center">
                <?php foreach ($attMedia as $i => $media): ?>
                  <div class="att-media-slide" data-index="<?= $i ?>" style="<?= $i === 0 ? '' : 'display:none;' ?>">
                    <?php if ($media['type'] === 'image'): ?>
                      <img src="<?= $media['src'] ?>" alt="<?= htmlspecialchars($media['name']) ?>" class="gallery-img w-full max-w-lg h-auto max-h-[400px] object-contain rounded-xl shadow border-2 border-[#8B7EC8] bg-white mb-2" />
                    <?php elseif ($media['type'] === 'video'): ?>
                      <video src="<?= $media['src'] ?>" controls class="w-full max-w-lg h-auto max-h-[400px] rounded-xl shadow border-2 border-[#8B7EC8] bg-black mb-2"></video>
                    <?php elseif ($media['type'] === 'audio'): ?>
                      <audio src="<?= $media['src'] ?>" controls class="w-full max-w-lg mb-2"></audio>
                    <?php endif; ?>
                    <form method="post" action="" target="_blank">
                      <input type="hidden" name="download" value="<?= htmlspecialchars($media['file']) ?>" />
                      <button type="submit" class="px-3 py-1 bg-[#8B7EC8] text-white rounded-md text-xs font-semibold hover:bg-[#6BCB77] transition flex items-center gap-2">
                        <i class="fa fa-download"></i> Download <?= ucfirst($media['type']) ?>
                      </button>
                    </form>
                  </div>
                <?php endforeach; ?>
                <?php if (count($attMedia) > 1): ?>
                <div class="flex justify-center gap-4 mt-4">
                  <button id="att-media-prev" class="px-4 py-2 rounded-full bg-[#E8E3F5] text-[#8B7EC8] font-bold shadow hover:bg-[#8B7EC8] hover:text-white transition"><i class="fa fa-chevron-left"></i></button>
                  <button id="att-media-next" class="px-4 py-2 rounded-full bg-[#E8E3F5] text-[#8B7EC8] font-bold shadow hover:bg-[#8B7EC8] hover:text-white transition"><i class="fa fa-chevron-right"></i></button>
                </div>
                <script>
                  const attMediaSlides = document.querySelectorAll('.att-media-slide');
                  let attMediaCurrent = 0;
                  function showAttMediaSlide(idx) {
                    attMediaSlides.forEach((el, i) => { el.style.display = (i === idx) ? '' : 'none'; });
                  }
                  function prevAttMediaSlide() {
                    attMediaCurrent = (attMediaCurrent - 1 + attMediaSlides.length) % attMediaSlides.length;
                    showAttMediaSlide(attMediaCurrent);
                  }
                  function nextAttMediaSlide() {
                    attMediaCurrent = (attMediaCurrent + 1) % attMediaSlides.length;
                    showAttMediaSlide(attMediaCurrent);
                  }
                  document.getElementById('att-media-prev').onclick = prevAttMediaSlide;
                  document.getElementById('att-media-next').onclick = nextAttMediaSlide;
                  showAttMediaSlide(attMediaCurrent);
                </script>
                <?php endif; ?>
              </div>
            <?php else: ?>
              <div class="text-center text-gray-400">No media files.</div>
            <?php endif; ?>
          </div>
          <?php if (!empty($attOthers)): ?>
          <div class="mt-8">
            <h3 class="text-base font-semibold text-[#8B7EC8] mb-2 flex items-center gap-2"><i class="fa fa-paperclip"></i> Other Files</h3>
            <div class="flex flex-col gap-4">
              <?php foreach ($attOthers as $file): ?>
                <div class="flex flex-col items-center">
                  <div class="w-full flex items-center justify-center h-32 bg-[#F5F3FB] rounded-xl border border-[#E8E3F5] mb-2">
                    <i class="fa fa-file-alt text-4xl text-[#8B7EC8]"></i>
                  </div>
                  <span class="text-sm text-gray-700 mb-1"><?= htmlspecialchars($file['name']) ?></span>
                  <form method="post" action="" target="_blank">
                    <input type="hidden" name="download" value="<?= htmlspecialchars($file['file']) ?>" />
                    <button type="submit" class="px-3 py-1 bg-[#8B7EC8] text-white rounded-md text-xs font-semibold hover:bg-[#6BCB77] transition flex items-center gap-2">
                      <i class="fa fa-download"></i> Download File
                    </button>
                  </form>
                </div>
              <?php endforeach; ?>
            </div>
          </div>
          <?php endif; ?>
        </section>
      </div>
      <?php if (!empty($sharedMemory['description'])): ?>
      <section class="w-full bg-[#F5F3FB] rounded-2xl shadow-lg p-6 mt-6 border border-[#E8E3F5]">
        <h2 class="text-lg font-semibold text-[#8B7EC8] mb-2 flex items-center gap-2"><i class="fa fa-align-left"></i> User Description</h2>
        <div class="text-gray-700 text-base leading-relaxed whitespace-pre-line">
          <?= nl2br(htmlspecialchars($sharedMemory['description'])) ?>
        </div>
      </section>
      <?php endif; ?>
      <section class="w-full bg-white/80 rounded-2xl shadow-lg p-6 mt-6">
        <h2 class="text-lg font-semibold text-[#8B7EC8] mb-2 flex items-center gap-2"><i class="fa fa-info-circle"></i> Memory Details</h2>
        <dl class="divide-y divide-[#E8E3F5]">
          <?php
          $backendFields = ['image', 'memory_id', 'created_at', 'updated_at', 'original_memory_id', 'user_email', 'email', 'created_by', 'attachment', 'attachments'];
          foreach ($sharedMemory as $key => $value) {
              if (in_array($key, $backendFields)) continue;
              if ($key === 'description') continue; // Already shown above
              if ($key === 'date' || $key === 'mood') continue;
              if (is_array($value)) $value = implode(', ', $value);
              if ($value === '' || $value === null) continue;
              if (strpos($key, 'email') !== false || filter_var($value, FILTER_VALIDATE_EMAIL)) continue;
              if (is_string($value) && (str_contains($value, '/Applications/') || str_contains($value, 'uploads/') || str_contains($value, '.jpg') || str_contains($value, '.png') || str_contains($value, '.jpeg'))) continue;
              echo '<div class="flex justify-between items-center py-2">';
              echo '<dt class="font-medium text-gray-600 capitalize">' . htmlspecialchars(str_replace('_',' ', $key)) . '</dt>';
              echo '<dd class="text-gray-800 text-right">' . htmlspecialchars($value) . '</dd>';
              echo '</div>';
          }
          ?>
        </dl>
      </section>
      <?php endif; ?>
      <div class="mt-4">
        <a href="dashboard.php" class="inline-block px-6 py-2 bg-[#8B7EC8] text-white rounded-xl hover:bg-[#6BCB77] transition">Back to Dashboard</a>
      </div>
    </div>
  </main>
</div>
</body>
</html> 