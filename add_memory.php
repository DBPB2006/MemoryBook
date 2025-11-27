<?php
session_start();
require_once __DIR__ . '/../data_structures/common_functions.php';
$current_user_email = $_SESSION['email'] ?? null;
$usersData = file_exists(__DIR__ . '/../data/users.json') ? json_decode(file_get_contents(__DIR__ . '/../data/users.json'), true) : [];
$currentUser = null;
if ($current_user_email && isset($usersData[strtoupper($current_user_email[0])][$current_user_email])) {
    $currentUser = $usersData[strtoupper($current_user_email[0])][$current_user_email];
}

$availableFriends = [];
if ($currentUser && !empty($currentUser['friends'])) {
    foreach ($currentUser['friends'] as $f) {
        if (!empty($f['email'])) $availableFriends[$f['email']] = $f['name'] . ' (' . $f['email'] . ')';
    }
}

$feedback = '';
if (isset($_POST['final_submit'])) {
    $title = trim($_POST['title'] ?? '');
    $date = trim($_POST['date'] ?? '');
    if (!$title || !$date) {
        $feedback = '<div class="bg-red-100 text-red-700 p-3 rounded mb-4">Title and Date are required.</div>';
    } else {
        $media_array = [];
        if (isset($_FILES['memory_media']) && !empty($_FILES['memory_media']['name'][0])) {
            foreach ($_FILES['memory_media']['tmp_name'] as $idx => $tmpName) {
                if ($_FILES['memory_media']['error'][$idx] === UPLOAD_ERR_NO_FILE) {
                    continue;
                }
                $file = [
                    'name' => $_FILES['memory_media']['name'][$idx],
                    'type' => $_FILES['memory_media']['type'][$idx],
                    'tmp_name' => $tmpName,
                    'error' => $_FILES['memory_media']['error'][$idx],
                    'size' => $_FILES['memory_media']['size'][$idx],
                ];
                $upload = handleFileUpload($file, __DIR__ . '/../uploads/', [
                    'jpg','jpeg','png','gif','webp','mp3','wav','ogg','webm','mp4','m4a','mov','avi','mpeg','mpg','mpga','mkv','aac','flac','3gp','3g2','oga','opus','amr','aiff','aif','aifc','au','snd','wma','wmv','flv','m4v','ogv','qt','ts','m2ts','mts','mxf','vob'
                ], 'mem_');
                if ($upload['success']) {
                    $media_array[] = [
                        'type' => $file['type'],
                        'url' => $upload['path'],
                        'name' => $file['name']
                    ];
                } else {
                    $feedback .= "<div class='bg-red-100 text-red-700 p-3 rounded mb-4'>Upload failed for {$file['name']}: {$upload['error']}</div>";
                }
            }
        }
        
        if (strpos($feedback, 'failed') === false) {
            $memories = file_exists(__DIR__ . '/../data/memories.json') ? json_decode(file_get_contents(__DIR__ . '/../data/memories.json'), true) : [];
            $memories[] = [
                'memory_id' => uniqid('m_'),
                'title' => $title,
                'date' => $date,
                'location' => trim($_POST['location'] ?? ''),
                'description' => trim($_POST['description'] ?? ''),
                'tags' => trim($_POST['tags'] ?? ''),
                'mood' => trim($_POST['mood'] ?? ''),
                'media' => $media_array,
                'owner' => $current_user_email,
                'friends' => $_POST['selected_friends'] ?? [],
                'created_at' => date('c'),
            ];
            file_put_contents(__DIR__ . '/../data/memories.json', json_encode($memories, JSON_PRETTY_PRINT));
            $feedback = '<div class="bg-green-100 text-green-700 p-3 rounded mb-4">Memory created successfully!</div>';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add Memory - MemoryBook</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link rel="stylesheet" href="output.css">
    <link rel="stylesheet" href="blobs.css">
    <link href="https://fonts.googleapis.com/css2?family=Kalam:wght@400;700&family=Quicksand:wght@400;500;700&display=swap" rel="stylesheet">
    <style>html, body { font-family: 'Quicksand', sans-serif; } .font-kalam { font-family: 'Kalam', cursive !important; }</style>
</head>
<body class="bg-gradient-to-br from-[#F5F3FB] to-[#F4F8FD] min-h-screen">
<div class="blobs-bg">
    <div class="blob blob1"></div><div class="blob blob2"></div><div class="blob blob3"></div><div class="blob blob4"></div><div class="blob blob5"></div><div class="blob blob6"></div><div class="blob blob7"></div><div class="blob blob8"></div><div class="blob blob9"></div><div class="blob blob10"></div><div class="blob blob11"></div><div class="blob blob12"></div><div class="blob blob13"></div>
</div>
<header class="bg-white/80 backdrop-blur-md border-b border-[#E8E3F5] sticky top-0 z-50 shadow-sm">
    <nav class="max-w-7xl mx-auto px-4 py-4 flex justify-between items-center">
        <a href="dashboard.php" class="text-[#8B7EC8] font-semibold">Back to Dashboard</a>
        <a href="login.php?logout=1" class="text-[#F48498] font-medium">Logout</a>
    </nav>
</header>
<main class="max-w-7xl mx-auto px-4 py-8">
    <?php if (!empty($feedback)) echo $feedback; ?>
    <div class="mb-8">
        <h1 class="text-3xl font-semibold text-[#2D2A3D] mb-2">Create a New Memory</h1>
        <p class="text-lg text-[#6B6B7D]">Capture a special moment with your friends</p>
    </div>
    <form id="memoryForm" class="grid lg:grid-cols-2 gap-8" method="POST" enctype="multipart/form-data">
        <div class="order-2 lg:order-1">
            <div class="bg-white rounded-2xl p-6 shadow mb-6">
                <h3 class="text-xl font-semibold text-[#2D2A3D] mb-4">Add Media</h3>
                 <!-- Live video preview element -->
                <video id="liveVideoPreview" class="w-full rounded-lg mb-4 hidden" autoplay muted playsinline></video>
                <div id="uploadArea" class="border-2 border-dashed border-[#D1C7EB] rounded-xl p-8 text-center">
                    <div class="flex flex-col items-center">
                        <i class="fas fa-cloud-upload-alt text-[#A394D4] text-4xl mb-4"></i>
                        <p class="text-[#2D2A3D] font-medium mb-2">Drag & drop any file here</p>
                        <p class="text-[#6B6B7D] text-sm mb-4">or click to browse or record</p>
                        <label class="bg-[#8B7EC8] text-white px-4 py-2 rounded-lg hover:bg-[#7A6BB5] cursor-pointer">
                            Choose File
                            <input type="file" id="memoryMediaInput" name="memory_media[]" accept="*/*" class="hidden" multiple>
                        </label>
                        <div id="recordButtonsContainer" class="flex flex-wrap justify-center gap-4 mt-4">
                            <button type="button" id="recordAudioBtn" class="px-4 py-2 bg-[#8B7EC8] text-white rounded-lg hover:bg-[#7A6BB5] flex items-center gap-2 disabled:opacity-50" ><i class="fa fa-microphone"></i> Record Audio</button>
                            <button type="button" id="recordVideoBtn" class="px-4 py-2 bg-[#8B7EC8] text-white rounded-lg hover:bg-[#7A6BB5] flex items-center gap-2 disabled:opacity-50"><i class="fa fa-video"></i> Record Video</button>
                            <button type="button" id="stopRecordingBtn" class="px-4 py-2 bg-[#8B7EC8] text-white rounded-lg hover:bg-[#7A6BB5] flex items-center gap-2 disabled:opacity-50" disabled><i class="fa fa-stop-circle"></i> Stop</button>
                        </div>
                    </div>
                </div>
                <div id="mediaPreview" class="grid grid-cols-2 md:grid-cols-3 gap-4 mt-6"></div>
            </div>
            <div class="bg-white rounded-2xl p-6 shadow">
                <h3 class="text-xl font-semibold text-[#2D2A3D] mb-4">Location</h3>
                <div class="relative">
                    <input type="text" id="location" name="location" class="w-full px-4 py-3 border border-[#E8E3F5] rounded-lg focus:ring-2 focus:ring-[#8B7EC8]" placeholder="Where did this memory happen?">
                    <i class="fas fa-map-marker-alt absolute right-3 top-3 text-[#6B6B7D]"></i>
                </div>
            </div>
        </div>
        <div class="order-1 lg:order-2 space-y-6">
            <div class="bg-white rounded-2xl p-6 shadow">
                <label for="title" class="block text-lg font-semibold text-[#2D2A3D] mb-3">Memory Title *</label>
                <input type="text" id="title" name="title" required class="w-full px-4 py-3 border border-[#E8E3F5] rounded-lg focus:ring-2 focus:ring-[#8B7EC8]">
            </div>
            <div class="bg-white rounded-2xl p-6 shadow">
                <label for="date" class="block text-lg font-semibold text-[#2D2A3D] mb-3">Date *</label>
                <input type="date" id="date" name="date" required class="w-full px-4 py-3 border border-[#E8E3F5] rounded-lg focus:ring-2 focus:ring-[#8B7EC8]">
            </div>
            <div class="bg-white rounded-2xl p-6 shadow">
                <label for="userSearchInput" class="block text-lg font-semibold text-[#2D2A3D] mb-3">Add Friends</label>
                <div id="availableFriendsList" class="mb-4">
                    <?php foreach ($availableFriends as $email => $label): ?>
                        <label class="flex items-center gap-2 mb-2">
                            <input type="checkbox" name="selected_friends[]" value="<?= htmlspecialchars($email) ?>" />
                            <span><?= htmlspecialchars($label) ?></span>
                        </label>
                    <?php endforeach; ?>
                </div>
                <input type="text" id="userSearchInput" placeholder="Type a name or email..." class="w-full px-4 py-3 border border-[#E8E3F5] rounded-lg" autocomplete="off">
                <div id="userSuggestions" class="space-y-2 mt-2 max-h-40 overflow-y-auto"></div>
                <div id="selectedFriends" class="mt-4"></div>
            </div>
            <div class="bg-white rounded-2xl p-6 shadow">
                <label for="description" class="block text-lg font-semibold text-[#2D2A3D] mb-3">Memory Description</label>
                <textarea id="description" name="description" rows="4" class="w-full px-4 py-3 border border-[#E8E3F5] rounded-lg focus:ring-2 focus:ring-[#8B7EC8]" placeholder="Tell the story of this memory..."></textarea>
                <div class="flex justify-between mt-2">
                    <span class="text-sm text-[#6B6B7D]">Share the details</span>
                    <span id="charCount" class="text-sm text-[#6B6B7D]">0/500</span>
                </div>
            </div>
            <div class="bg-white rounded-2xl p-6 shadow">
                <label for="tags" class="block text-lg font-semibold text-[#2D2A3D] mb-3">Tags (comma separated)</label>
                <input type="text" id="tags" name="tags" class="w-full px-4 py-3 border border-[#E8E3F5] rounded-lg focus:ring-2 focus:ring-[#8B7EC8]" placeholder="e.g. friendship, adventure">
            </div>
            <div class="bg-white rounded-2xl p-6 shadow">
                <label for="mood" class="block text-lg font-semibold text-[#2D2A3D] mb-3">Memory Mood</label>
                <select id="mood" name="mood" class="w-full px-4 py-3 border border-[#E8E3F5] rounded-lg focus:ring-2 focus:ring-[#8B7EC8]">
                    <option value="">Select mood</option>
                    <option value="happy">Happy</option>
                    <option value="loved">Loved</option>
                    <option value="excited">Excited</option>
                    <option value="peaceful">Peaceful</option>
                    <option value="grateful">Grateful</option>
                    <option value="magical">Magical</option>
                </select>
            </div>
            <div class="flex gap-4 pt-6">
                <button type="submit" name="final_submit" class="flex-1 bg-[#8B7EC8] text-white px-6 py-3 rounded-lg hover:bg-[#7A6BB5] shadow">Create Memory</button>
            </div>
        </div>
    </form>
</main>
<script src="ajax_user_search.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    // User search logic (unchanged)
    setupUserSearch('userSearchInput', 'userSuggestions', true);
    const userSearchInput = document.getElementById('userSearchInput');
    const selectedFriends = document.getElementById('selectedFriends');
    let selectedFriendEmails = new Set();
    document.querySelectorAll('#availableFriendsList input[type=checkbox]').forEach(cb => {
        cb.addEventListener('change', function() {
            if (this.checked) selectedFriendEmails.add(this.value);
            else selectedFriendEmails.delete(this.value);
            selectedFriends.querySelectorAll('input[type=checkbox]').forEach(selCb => {
                if (selCb.value === this.value) selCb.closest('div').remove();
            });
        });
    });
    if (userSearchInput) {
        userSearchInput.addEventListener('user-selected', function(e) {
            if (e.detail && e.detail.email) {
                const cb = document.querySelector(`#availableFriendsList input[type=checkbox][value='${e.detail.email}']`);
                if (cb) { cb.checked = true; selectedFriendEmails.add(e.detail.email); }
                else if (!selectedFriendEmails.has(e.detail.email)) {
                    selectedFriendEmails.add(e.detail.email);
                    const friendDiv = document.createElement('div');
                    friendDiv.className = 'flex items-center justify-between bg-[#F5F3FB] px-3 py-2 rounded-lg mt-2';
                    friendDiv.innerHTML = `<label><input type="checkbox" name="selected_friends[]" value="${e.detail.email}" checked />${e.detail.name} (${e.detail.email})</label><button type="button" class="text-[#F48498] hover:text-[#E57373] remove-friend"><i class="fa fa-times"></i></button>`;
                    friendDiv.querySelector('.remove-friend').addEventListener('click', () => { selectedFriendEmails.delete(e.detail.email); friendDiv.remove(); });
                    selectedFriends.appendChild(friendDiv);
                }
                userSearchInput.value = '';
            }
        });
    }
    const description = document.getElementById('description');
    const charCount = document.getElementById('charCount');
    description.addEventListener('input', function() {
        const count = description.value.length;
        charCount.textContent = `${count}/500`;
        if (count > 500) charCount.classList.add('text-red-500');
        else charCount.classList.remove('text-red-500');
    });

    // --- REWRITTEN MEDIA HANDLING LOGIC ---
    const mediaInput = document.getElementById('memoryMediaInput');
    const mediaPreviewContainer = document.getElementById('mediaPreview');
    const liveVideoPreview = document.getElementById('liveVideoPreview');
    const stopRecordingBtn = document.getElementById('stopRecordingBtn');
    const recordAudioBtn = document.getElementById('recordAudioBtn');
    const recordVideoBtn = document.getElementById('recordVideoBtn');
    
    let mediaRecorder;
    let stream;
    const dataTransfer = new DataTransfer();

    function updateFileInput() {
        mediaInput.files = dataTransfer.files;
        renderPreviews();
    }

    function addFile(file) {
        dataTransfer.items.add(file);
        updateFileInput();
    }

    function renderPreviews() {
        mediaPreviewContainer.innerHTML = '';
        Array.from(dataTransfer.files).forEach((file, index) => {
            const url = URL.createObjectURL(file);
            const previewWrapper = document.createElement('div');
            previewWrapper.className = 'relative';

            let el;
            if (file.type.startsWith('image/')) {
                el = document.createElement('img');
                el.src = url;
                el.className = 'w-full h-24 object-cover rounded-lg';
            } else if (file.type.startsWith('video/')) {
                el = document.createElement('video');
                el.src = url;
                el.className = 'w-full h-24 object-cover rounded-lg';
                el.muted = true;
                el.addEventListener('mouseenter', () => el.play());
                el.addEventListener('mouseleave', () => el.pause());
            } else if (file.type.startsWith('audio/')) {
                el = document.createElement('div');
                el.className = 'w-full h-24 rounded-lg bg-gray-100 flex flex-col items-center justify-center p-2';
                el.innerHTML = `<i class="fa fa-music text-3xl text-[#8B7EC8]"></i><p class="text-xs text-center truncate w-full mt-2">${file.name}</p>`;
            } else {
                el = document.createElement('div');
                el.className = 'w-full h-24 rounded-lg bg-gray-100 flex flex-col items-center justify-center p-2';
                el.innerHTML = `<i class="fa fa-file-alt text-3xl text-[#8B7EC8]"></i><p class="text-xs text-center truncate w-full mt-2">${file.name}</p>`;
            }
            previewWrapper.appendChild(el);

            const removeBtn = document.createElement('button');
            removeBtn.type = 'button';
            removeBtn.className = 'absolute top-1 right-1 bg-red-500 text-white rounded-full w-5 h-5 flex items-center justify-center text-xs';
            removeBtn.innerHTML = '<i class="fa fa-times"></i>';
            removeBtn.onclick = () => {
                dataTransfer.items.remove(index);
                updateFileInput();
            };
            previewWrapper.appendChild(removeBtn);
            mediaPreviewContainer.appendChild(previewWrapper);
        });
    }

    mediaInput.addEventListener('change', () => {
        Array.from(mediaInput.files).forEach(file => dataTransfer.items.add(file));
        mediaInput.value = ''; 
        updateFileInput();
    });

    function toggleRecordingControls(isRecording) {
        recordAudioBtn.disabled = isRecording;
        recordVideoBtn.disabled = isRecording;
        stopRecordingBtn.disabled = !isRecording;

        if (isRecording && stream && stream.getVideoTracks().length > 0) {
            liveVideoPreview.classList.remove('hidden');
            liveVideoPreview.srcObject = stream;
        } else {
            liveVideoPreview.classList.add('hidden');
            if (liveVideoPreview.srcObject) {
                liveVideoPreview.srcObject.getTracks().forEach(track => track.stop());
            }
            liveVideoPreview.srcObject = null;
        }
    }

    async function startRecording(type) {
        try {
            const constraints = type === 'audio' ? { audio: true } : { audio: true, video: true };
            stream = await navigator.mediaDevices.getUserMedia(constraints);
            toggleRecordingControls(true);
            
            const recordedChunks = [];
            mediaRecorder = new MediaRecorder(stream);
            
            mediaRecorder.ondataavailable = e => {
                if (e.data.size > 0) recordedChunks.push(e.data);
            };

            mediaRecorder.onstop = () => {
                const mimeType = type === 'audio' ? 'audio/webm' : 'video/webm';
                const fileExtension = type === 'audio' ? 'webm' : 'webm';
                const blob = new Blob(recordedChunks, { type: mimeType });
                const fileName = `recording-${Date.now()}.${fileExtension}`;
                const file = new File([blob], fileName, { type: mimeType });
                addFile(file);
                
                // This will also hide the live preview
                toggleRecordingControls(false);
            };

            mediaRecorder.start();
        } catch (err) {
            alert('Could not start recording: ' + err.message);
            toggleRecordingControls(false);
        }
    }

    stopRecordingBtn.onclick = () => {
        if (mediaRecorder && mediaRecorder.state !== 'inactive') {
            mediaRecorder.stop();
        }
    };

    recordAudioBtn.onclick = () => startRecording('audio');
    recordVideoBtn.onclick = () => startRecording('video');
});
</script>
</body>
</html>
