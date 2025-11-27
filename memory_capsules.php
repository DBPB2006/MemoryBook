<?php


require_once __DIR__ . '/../data_structures/common_functions.php';
require_once __DIR__ . '/../data_structures/stack.php'; // Using the updated stack file
require_once __DIR__ . '/../data_structures/priority_queue.php';
session_start();
date_default_timezone_set('Asia/Kolkata');

// AJAX endpoint for user search
if (isset($_GET['action']) && $_GET['action'] === 'search_users') {
    header('Content-Type: application/json');
    $usersData = file_exists(__DIR__ . '/../data/users.json') ? json_decode(file_get_contents(__DIR__ . '/../data/users.json'), true) : [];
    $flatUsers = [];
    foreach ($usersData as $letterGroup) {
        foreach ($letterGroup as $email => $userData) {
            // Ensure 'name' exists, otherwise fallback to email
            $name = !empty($userData['name']) ? $userData['name'] : $email;
            $flatUsers[] = [
                'email' => $email,
                'name' => $name,
                'display' => "$name ($email)"
            ];
        }
    }
    echo json_encode($flatUsers);
    exit();
}


if (!isLoggedIn()) {
    header('Location: login.php');
    exit();
}

$currentEmail = $_SESSION['email'];
$users = file_exists(__DIR__ . '/../data/users.json') ? json_decode(file_get_contents(__DIR__ . '/../data/users.json'), true) : [];
$userObj = $users[strtoupper($currentEmail[0])][$currentEmail] ?? null;

// capsule creation
$successMsg = $errorMsg = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['create_capsule'])) {
    $recipient_email = sanitizeInput($_POST['recipient_email'] ?? '');
    $message = sanitizeInput($_POST['message'] ?? '');
    $description = sanitizeInput($_POST['description'] ?? '');
    // Normalize reveal_date to Y-m-d H:i:s for consistency
    $reveal_date_raw = $_POST['reveal_date'] ?? '';
    $reveal_date = '';
    if ($reveal_date_raw) {
        // Accepts both 'Y-m-d\TH:i' and 'Y-m-d H:i:s'
        $ts = strtotime($reveal_date_raw);
        if ($ts !== false) {
            $reveal_date = date('Y-m-d H:i:s', $ts);
        }
    }
    $media = [];
    // Validate recipient: must be a valid email address (user search must fill the email, not just a name)
    if (!filter_var($recipient_email, FILTER_VALIDATE_EMAIL)) {
        $errorMsg = 'Invalid recipient email. Please select a user from the suggestions.';
    } elseif (!isset($users[strtoupper($recipient_email[0])][$recipient_email])) {
        $errorMsg = 'Recipient email does not exist. Please select a valid user from the search results.';
    } elseif (empty($message) || empty($reveal_date)) {
        $errorMsg = 'Message and reveal date are required.';
    } else {
        // Process file uploads (optional) using the single, modern handleFileUpload function
        if (isset($_FILES['media']) && !empty($_FILES['media']['name'][0])) {
            foreach ($_FILES['media']['tmp_name'] as $idx => $tmpName) {
                if ($_FILES['media']['error'][$idx] === UPLOAD_ERR_NO_FILE) {
                    continue;
                }
                $file = [
                    'name' => $_FILES['media']['name'][$idx],
                    'type' => $_FILES['media']['type'][$idx],
                    'tmp_name' => $tmpName,
                    'error' => $_FILES['media']['error'][$idx],
                    'size' => $_FILES['media']['size'][$idx],
                ];
                $upload = handleFileUpload($file, __DIR__ . '/../uploads/', [
                    'jpg','jpeg','png','gif','webp','mp3','wav','ogg','webm','mp4','m4a','mov','avi','mpeg','mpg','mpga','mkv','aac','flac','3gp','3g2','oga','opus','amr','aiff','aif','aifc','au','snd','wma','wmv','flv','m4v','ogv','qt','ts','m2ts','mts','mxf','vob'
                ], 'cap_');
                if ($upload['success']) {
                    $media[] = [
                        'path' => $upload['path'],
                        'type' => $file['type']
                    ];
                } else {
                    $errorMsg .= "Upload failed for '{$file['name']}': {$upload['error']}. ";
                }
            }
        }
        // Only proceed to save the capsule if there were no upload errors
        if (empty($errorMsg)) {
            $capsulesFile = __DIR__ . '/../data/time_capsules.json';
            $capsules = file_exists($capsulesFile) ? json_decode(file_get_contents($capsulesFile), true) : [];
            $newCapsule = [
                'id' => 'tc_' . uniqid('', true),
                'user_email' => $currentEmail,
                'recipient_email' => $recipient_email,
                'reveal_date' => $reveal_date,
                'message' => $message,
                'description' => $description,
                'media' => $media,
                'created_at' => date('Y-m-d H:i:s'),
                'unlocked' => 0
            ];
            $capsules[] = $newCapsule;
            file_put_contents($capsulesFile, json_encode($capsules, JSON_PRETTY_PRINT));
            // Redirect to avoid duplicate submissions on refresh (POST/Redirect/GET)
            header('Location: ' . $_SERVER['REQUEST_URI'] . '?success=1');
            exit;
        }
    }
}

// Load capsules for current user
$capsulesFile = __DIR__ . '/../data/time_capsules.json';
$capsules = file_exists($capsulesFile) ? json_decode(file_get_contents($capsulesFile), true) : [];
$userCapsules = [];
$now = date('Y-m-d H:i:s');
$updated = false;
foreach ($capsules as $i => $c) {
    if (($c['user_email'] ?? '') === $currentEmail || ($c['recipient_email'] ?? '') === $currentEmail) {
        if ((!isset($c['unlocked']) || $c['unlocked'] != 1) && isCapsuleUnlocked($c['reveal_date'])) {
            $capsules[$i]['unlocked'] = 1;
            $c['unlocked'] = 1;
            $updated = true;
        }
        $userCapsules[] = $c;
    }
}
if ($updated) {
    file_put_contents($capsulesFile, json_encode($capsules, JSON_PRETTY_PRINT));
}

$pq = new PriorityQueue();
foreach ($userCapsules as $c) {
    $pq->insert($c, $c['reveal_date']);
}
$userCapsulesSorted = $pq->toSortedArray();

include 'navbar.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Memory Capsules</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" crossorigin="anonymous" referrerpolicy="no-referrer" />
    <link rel="stylesheet" href="output.css" />
    <link rel="stylesheet" href="blobs.css" />
    <link href="https://fonts.googleapis.com/css2?family=Kalam:wght@400;700&family=Quicksand:wght@400;500;700&display=swap" rel="stylesheet">
    <style>
        body {
            font-family: 'Quicksand', sans-serif;
            background-color: #f8f0fc; /* Light pastel purple background */
        }
        /* Custom Pastel Palette */
        .text-pastel-purple { color: #957dad; }
        .bg-pastel-purple { background-color: #e0bbe4; }
        .border-pastel-purple { border-color: #d291bc; }
        .bg-pastel-purple-light { background-color: #f8f0fc; }
        .bg-pastel-pink { background-color: #fec8d8; }
        .bg-pastel-blue { background-color: #a2d2ff; }
        .text-pastel-blue { color: #5e84e2; }
        .bg-pastel-green { background-color: #d4f0f0; }
        .text-pastel-green { color: #4CAF50; }
        .recipient-btn.active {
            background-color: #957dad;
            color: white;
            border-color: #957dad;
        }

    </style>
</head>
<body class="min-h-screen">
<div class="blobs-bg">
  <div class="blob blob1"></div><div class="blob blob2"></div><div class="blob blob3"></div>
  <div class="blob blob4"></div><div class="blob blob5"></div><div class="blob blob6"></div>
  <div class="blob blob7"></div><div class="blob blob8"></div><div class="blob blob9"></div>
  <div class="blob blob10"></div><div class="blob blob11"></div><div class="blob blob12"></div><div class="blob blob13"></div>
</div>
<div class="max-w-4xl mx-auto mt-10 mb-8 p-8 rounded-2xl shadow-xl border bg-white/90" style="border-color:#E5E7EB;">
  <h2 class="text-2xl font-bold mb-4 flex items-center text-pastel-purple"><i class="fas fa-box-open mr-2"></i>Create a New Time Capsule</h2>
  <?php if ($successMsg): ?><div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-4"><?php echo $successMsg; ?></div><?php endif; ?>
  <?php if ($errorMsg): ?><div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4"><?php echo $errorMsg; ?></div><?php endif; ?>
  <form class="space-y-5" method="POST" enctype="multipart/form-data" id="capsuleForm">
    <input type="hidden" name="create_capsule" value="1" />
    <div>
      <label class="block font-medium mb-1 text-pastel-purple">Message</label>
      <textarea required name="message" class="w-full rounded-lg p-2 mt-1 border bg-pastel-purple-light border-pastel-purple" rows="3" placeholder="Write your message here..."></textarea>
    </div>
    <div>
      <label class="block font-medium mb-1 text-pastel-purple">Description (Optional)</label>
      <textarea name="description" class="w-full rounded-lg p-2 mt-1 border bg-pastel-purple-light border-pastel-purple" rows="2" placeholder="Add a short description..."></textarea>
    </div>
    <div>
      <label class="block font-medium mb-1 text-pastel-purple">Media</label>
      <p class="text-sm mb-2 text-gray-500">You can upload files directly OR record a new audio/video message below.</p>
      <input type="file" name="media[]" multiple accept="image/*,audio/*,video/*" class="w-full rounded-lg p-2 mb-4 border bg-pastel-purple-light border-pastel-purple" />
      <div class="p-4 rounded-lg border bg-gray-50 border-pastel-purple">
        <h4 class="font-semibold mb-2 text-gray-600">Record Media</h4>
        <div class="rounded-lg overflow-hidden mb-4 border-2 aspect-video bg-black border-pastel-purple">
            <video id="videoPlayer" class="w-full h-full" autoplay muted playsinline></video>
        </div>
        <div class="flex items-center gap-2 mb-2">
          <button type="button" id="recordVideoBtn" class="py-2 px-4 rounded-lg font-bold flex-1 shadow bg-pastel-pink hover:bg-pastel-blue text-white flex items-center justify-center"><i class="fas fa-video mr-2"></i>Record Video</button>
          <button type="button" id="stopVideoBtn" class="py-2 px-4 rounded-lg font-bold flex-1 shadow bg-pastel-pink hover:bg-pastel-blue text-white flex items-center justify-center"><i class="fas fa-stop-circle mr-2"></i>Stop Video</button>
          <button type="button" id="recordAudioBtn" class="py-2 px-4 rounded-lg font-bold flex-1 shadow bg-pastel-pink hover:bg-pastel-blue text-white flex items-center justify-center"><i class="fas fa-microphone mr-2"></i>Record Audio</button>
          <button type="button" id="stopAudioBtn" class="py-2 px-4 rounded-lg font-bold flex-1 shadow bg-pastel-pink hover:bg-pastel-blue text-white flex items-center justify-center"><i class="fas fa-stop-circle mr-2"></i>Stop Audio</button>
        </div>
        <div id="mediaPreview" class="mt-3 text-center"></div>
      </div>
    </div>
    <div>
      <label class="block font-medium mb-1 text-pastel-purple">Recipient</label>
      <div class="flex gap-2 mb-2">
        <button type="button" id="btnMyself" class="recipient-btn px-4 py-1 rounded-lg border font-semibold border-pastel-purple text-pastel-purple">Myself</button>
        <button type="button" id="btnSearchUser" class="recipient-btn px-4 py-1 rounded-lg border font-semibold border-pastel-purple text-pastel-purple">Search User</button>
      </div>
      <!-- REBUILT USER SEARCH -->
      <div id="recipientSelectionContainer">
        <div class="relative">
            <input type="text" id="userSearchInput" class="w-full rounded-lg p-2 mt-1 border bg-pastel-purple-light border-pastel-purple disabled:bg-gray-200" placeholder="Select an option above..." autocomplete="off" disabled>
            <input type="hidden" name="recipient_email" id="recipientEmailHidden">
            <div id="userSuggestions" class="absolute z-50 rounded shadow-lg mt-1 w-full bg-white hidden max-h-60 overflow-y-auto"></div>
        </div>
        <div id="selectedUserDisplay" class="hidden mt-2 p-2 bg-pastel-green border border-pastel-blue rounded-lg flex justify-between items-center">
            <span id="selectedUserName" class="font-semibold text-pastel-blue"></span>
            <button type="button" id="clearUserSelection" class="text-red-500 hover:text-red-700 font-bold text-xl leading-none">&times;</button>
        </div>
      </div>
      <div class="text-xs text-gray-500 mt-1">Select a user from the suggestions. The recipient must be a registered user.</div>
    </div>
    <div>
      <label class="block font-medium text-pastel-purple">Reveal Date & Time</label>
      <input type="datetime-local" required name="reveal_date" class="w-full rounded-lg p-2 mt-1 border bg-pastel-purple-light border-pastel-purple">
    </div>
    <div class="text-center pt-2">
      <button type="submit" class="px-6 py-2 rounded-lg font-bold shadow bg-pastel-blue hover:bg-pastel-pink text-white"> <i class="fas fa-paper-plane mr-1"></i> Create Capsule</button>
    </div>
  </form>
</div>

<!-- Capsule List -->
<div class="max-w-6xl mx-auto p-4 grid gap-10 gap-y-16 md:grid-cols-2">
<?php foreach ($userCapsulesSorted as $capsule):
    $isUnlocked = $capsule['unlocked'] == 1;
    $now = date('Y-m-d H:i:s');
    $timeDiff = displayTimeDifference($now, $capsule['reveal_date']); // Using the function from stack.php
    $mediaItems = $capsule['media'] ?? [];
    $capsuleUrl = 'open_capsule.php?id=' . urlencode($capsule['id']);
?>
  <a href="<?php echo $capsuleUrl; ?>" class="block group">
  <div class="rounded-2xl shadow-lg border max-w-md w-full mx-auto p-6 flex flex-col justify-between h-full relative bg-white/90 transition-transform group-hover:scale-105 group-hover:shadow-2xl border-pastel-purple" style="cursor:pointer;">
    <div class="absolute top-4 right-4">
      <span class="inline-block px-3 py-1 rounded-full text-xs font-bold <?php echo $isUnlocked ? 'bg-pastel-green text-green-800' : 'bg-pastel-pink text-red-800'; ?>">
        <i class="fas <?php echo $isUnlocked ? 'fa-lock-open mr-1' : 'fa-lock mr-1'; ?>"></i><?php echo $isUnlocked ? 'Unlocked' : 'Locked'; ?>
      </span>
    </div>
    <div class="flex items-center mb-3">
      <i class="fas fa-user-circle text-2xl text-pastel-purple"></i>
      <span class="text-lg font-semibold ml-3 text-gray-700">To: <?php echo htmlspecialchars($capsule['recipient_email']); ?></span>
    </div>
    <div class="flex items-center text-sm mb-2 text-pastel-purple">
      <i class="fas fa-calendar-alt mr-1"></i>Reveal: <span class="font-mono ml-1"><?php echo htmlspecialchars($capsule['reveal_date']); ?></span>
    </div>
    <div class="flex items-center text-sm mb-2 text-pastel-purple">
      <i class="fas fa-clock mr-1"></i>Created: <span class="font-mono ml-1"><?php echo htmlspecialchars($capsule['created_at']); ?></span>
    </div>
    <div class="mt-2 mb-3 text-pastel-purple"><i class="fas fa-comment-dots mr-1"></i><?php echo nl2br(htmlspecialchars($capsule['message'])); ?></div>
    <?php if (!empty($capsule['description'])): ?>
    <div class="mb-3 text-sm text-gray-600"><i class="fas fa-sticky-note mr-1"></i><?php echo nl2br(htmlspecialchars($capsule['description'])); ?></div>
    <?php endif; ?>
    <?php if ($isUnlocked): ?>
      <div class="mb-3">
        <?php foreach ($mediaItems as $media): ?>
          <?php if (strpos($media['type'], 'image/') === 0): ?>
            <img src="../<?php echo htmlspecialchars($media['path']); ?>" class="w-full h-48 object-contain rounded-lg mb-2" alt="Capsule Media" />
          <?php elseif (strpos($media['type'], 'audio/') === 0): ?>
            <audio controls class="w-full mb-2"><source src="../<?php echo htmlspecialchars($media['path']); ?>" type="<?php echo htmlspecialchars($media['type']); ?>">Your browser does not support audio.</audio>
          <?php elseif (strpos($media['type'], 'video/') === 0): ?>
            <video controls class="w-full h-48 rounded-lg mb-2"><source src="../<?php echo htmlspecialchars($media['path']); ?>" type="<?php echo htmlspecialchars($media['type']); ?>">Your browser does not support video.</video>
          <?php endif; ?>
        <?php endforeach; ?>
      </div>
      <div class="mt-3">
        <p class="font-semibold mt-2 text-pastel-green"><i class="fas fa-gift mr-1"></i>Capsule is Unlocked</p>
      </div>
    <?php else: ?>
      <div class="mt-3">
        <div class="font-semibold text-yellow-700 flex items-center"><i class="fas fa-hourglass-start mr-2"></i>Capsule unlocks in <span class="ml-2 countdown" data-reveal="<?php echo htmlspecialchars($capsule['reveal_date']); ?>"><?php echo htmlspecialchars($timeDiff); ?></span></div>
        <div class="text-center text-gray-400 w-full mt-4">
          <i class="fas fa-lock text-5xl mb-4"></i>
          <p class="text-lg font-semibold">Content is Locked</p>
        </div>
      </div>
    <?php endif; ?>
  </div>
  </a>
<?php endforeach; ?>
</div>

<script>
document.addEventListener('DOMContentLoaded', () => {

    // --- 1. User Search Functionality ---
    function setupUserSearch() {
        const searchInput = document.getElementById('userSearchInput');
        const suggestionsBox = document.getElementById('userSuggestions');
        const hiddenInput = document.getElementById('recipientEmailHidden');
        const selectedDisplay = document.getElementById('selectedUserDisplay');
        const selectedName = document.getElementById('selectedUserName');
        const myselfBtn = document.getElementById('btnMyself');
        const searchUserBtn = document.getElementById('btnSearchUser');
        
        let allUsers = [];
        fetch('?action=search_users').then(res => res.json()).then(data => { allUsers = data; });

        const selectUser = (user) => {
            if (!user) return;
            hiddenInput.value = user.email;
            selectedName.textContent = user.display;
            suggestionsBox.classList.add('hidden');
            searchInput.classList.add('hidden');
            selectedDisplay.classList.remove('hidden');
            searchInput.disabled = true;
        };

        const clearSelection = () => {
            searchInput.value = '';
            hiddenInput.value = '';
            searchInput.classList.remove('hidden');
            selectedDisplay.classList.add('hidden');
            searchInput.disabled = false;
            searchInput.placeholder = 'Type a name or email...';
            searchInput.focus();
        };

        searchInput.addEventListener('input', () => {
            const query = searchInput.value.toLowerCase();
            suggestionsBox.innerHTML = '';
            if (!query) {
                suggestionsBox.classList.add('hidden');
                return;
            }
            const filtered = allUsers.filter(u => u.display.toLowerCase().includes(query));
            if (filtered.length > 0) {
                filtered.forEach(user => {
                    const div = document.createElement('div');
                    div.textContent = user.display;
                    div.className = 'p-2 hover:bg-gray-200 cursor-pointer';
                    div.onclick = () => selectUser(user);
                    suggestionsBox.appendChild(div);
                });
                suggestionsBox.classList.remove('hidden');
            }
        });

        myselfBtn.addEventListener('click', () => {
            const currentUserEmail = '<?php echo htmlspecialchars($currentEmail); ?>';
            selectUser(allUsers.find(u => u.email === currentUserEmail));
            myselfBtn.classList.add('active');
            searchUserBtn.classList.remove('active');
        });

        searchUserBtn.addEventListener('click', () => {
            clearSelection();
            searchUserBtn.classList.add('active');
            myselfBtn.classList.remove('active');
        });

        document.getElementById('clearUserSelection').addEventListener('click', clearSelection);
        document.addEventListener('click', (e) => {
            if (!searchInput.contains(e.target)) suggestionsBox.classList.add('hidden');
        });
    }

    // --- 2. Countdown Timer ---
    function setupCountdown() {
        const countdowns = document.querySelectorAll('.countdown');
        if (countdowns.length === 0) return;

        const interval = setInterval(() => {
            let shouldReload = false;
            countdowns.forEach(el => {
                const revealDate = new Date(el.dataset.reveal.replace(' ', 'T'));
                const diff = Math.floor((revealDate - new Date()) / 1000);

                if (diff <= 0) {
                    if (el.textContent.trim() !== 'Unlocked') {
                       shouldReload = true;
                    }
                    return;
                }

                const d = Math.floor(diff / 86400);
                const h = Math.floor((diff % 86400) / 3600);
                const m = Math.floor((diff % 3600) / 60);
                const s = diff % 60;
                el.textContent = `${d}d ${h}h ${m}m ${s}s`;
            });

            if (shouldReload) {
                clearInterval(interval);
                setTimeout(() => window.location.reload(), 1200);
            }
        }, 1000);
    }

    // --- 3. Media Recording ---
    function setupMediaRecording() {
        const videoPlayer = document.getElementById('videoPlayer');
        const mediaPreview = document.getElementById('mediaPreview');
        const fileInput = document.querySelector('input[type="file"][name="media[]"]');
        let mediaRecorder;
        let recordedChunks = [];

        const startRecording = async (isAudioOnly) => {
            try {
                const constraints = isAudioOnly 
                    ? { audio: true } 
                    : { video: true, audio: true };
                const stream = await navigator.mediaDevices.getUserMedia(constraints);
                
                videoPlayer.srcObject = stream;
                videoPlayer.src = null;
                videoPlayer.muted = !isAudioOnly;
                videoPlayer.controls = false;
                
                recordedChunks = [];
                mediaRecorder = new MediaRecorder(stream);
                mediaRecorder.ondataavailable = e => {
                    if (e.data.size > 0) recordedChunks.push(e.data);
                };
                mediaRecorder.onstop = () => {
                    const mimeType = isAudioOnly ? 'audio/webm' : 'video/webm';
                    const blob = new Blob(recordedChunks, { type: mimeType });
                    showPreviewAndAttachFile(blob, mimeType);
                    stream.getTracks().forEach(track => track.stop());
                };
                mediaRecorder.start();
            } catch (err) {
                alert(`Could not start recording. Please grant permission. Error: ${err.message}`);
            }
        };

        const stopRecording = () => {
            if (mediaRecorder && mediaRecorder.state === 'recording') {
                mediaRecorder.stop();
            }
        };

        const showPreviewAndAttachFile = (blob, mimeType) => {
            mediaPreview.innerHTML = '';
            const isVideo = mimeType.startsWith('video/');
            const el = document.createElement(isVideo ? 'video' : 'audio');
            el.src = URL.createObjectURL(blob);
            el.controls = true;
            el.className = isVideo ? 'w-full h-48 rounded-lg mb-2' : 'w-full mb-2';
            mediaPreview.appendChild(el);
            
            const confirmation = document.createElement('div');
            confirmation.className = 'text-pastel-green font-semibold mt-2';
            confirmation.textContent = 'Recording is ready to be submitted.';
            mediaPreview.appendChild(confirmation);

            const fileName = `recording-${Date.now()}.${isVideo ? 'webm' : 'webm'}`;
            const file = new File([blob], fileName, { type: mimeType });
            const dataTransfer = new DataTransfer();
            Array.from(fileInput.files).forEach(f => dataTransfer.items.add(f));
            dataTransfer.items.add(file);
            fileInput.files = dataTransfer.files;
        };

        document.getElementById('recordVideoBtn').onclick = () => startRecording(false);
        document.getElementById('recordAudioBtn').onclick = () => startRecording(true);
        document.getElementById('stopVideoBtn').onclick = stopRecording;
        document.getElementById('stopAudioBtn').onclick = stopRecording;
    }

    // Initialize all functionalities
    setupUserSearch();
    setupCountdown();
    setupMediaRecording();
});
</script>
</body>
</html>
