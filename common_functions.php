<?php
// data_structures/common_functions.php
// Shared utility functions used across multiple pages

// Password verification helper
if (!function_exists('verifyPassword')) {
    function verifyPassword($inputPassword, $hashedPassword) {
        return password_verify($inputPassword, $hashedPassword);
    }
}

// Check if user is logged in
function isLoggedIn() {
    return isset($_SESSION['email']);
}

// Get bucket letter from email
function getBucket($email) {
    return strtoupper($email[0]);
}

// Load all users from users.json
function loadUsers($file = '../data/users.json') {
    if (!file_exists($file)) return [];
    $json = file_get_contents($file);
    return json_decode($json, true) ?: [];
}

// Save all users to users.json
function saveUsers($users, $file = '../data/users.json') {
    file_put_contents($file, json_encode($users, JSON_PRETTY_PRINT));
}

//  Get current user data by email
function getCurrentUser(&$users, $email) {
    $bucket = getBucket($email);
    return $users[$bucket][$email] ?? null;
}

// Save current user data back to users array
function saveCurrentUser(&$users, $user) {
    $bucket = getBucket($user['email']);
    $users[$bucket][$user['email']] = $user;
}

//Load all memories from memories.json
function loadMemories($file = '../data/memories.json') {
    if (!file_exists($file)) return [];
    $json = file_get_contents($file);
    return json_decode($json, true) ?: [];
}

// Save all memories to memories.json
function saveMemories($memories, $file = '../data/memories.json') {
    file_put_contents($file, json_encode($memories, JSON_PRETTY_PRINT));
}

// Generate unique filename for uploaded image
function generateUniqueFilename($originalName, $prefix = 'file_') {
    $ext = pathinfo($originalName, PATHINFO_EXTENSION);
    return uniqid($prefix, true) . ($ext ? ".$ext" : "");
}

//  Get first name from full name or email
function getFirstName($nameOrEmail) {
    if (!$nameOrEmail) return '';
    $parts = explode(' ', $nameOrEmail);
    return $parts[0];
}
function handleFileUpload(array $file, string $targetDir, array $allowedExtensions = [], string $prefix = ''): array
{
    // Only essential error handling: file upload and move
    if (!isset($file['error']) || $file['error'] !== UPLOAD_ERR_OK) {
        return [
            'success' => false
        ];
    }
    $fileExtension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    $isRecorded = (
        (strpos($file['type'], 'audio/webm') === 0 || strpos($file['type'], 'video/webm') === 0)
        && (strpos($file['name'], 'recorded_') === 0)
    );
    if ($isRecorded) {
        $short = bin2hex(random_bytes(4)); // 8 hex chars
        $newFileName = $short . '.' . $fileExtension;
    } else {
        $newFileName = $prefix . uniqid('', true) . '.' . $fileExtension;
    }
    $destination = rtrim($targetDir, '/') . '/' . $newFileName;
    if (!is_dir($targetDir)) {
        mkdir($targetDir, 0777, true);
    }
    if (!move_uploaded_file($file['tmp_name'], $destination)) {
        return [
            'success' => false
        ];
    }
    return [
        'success' => true,
        'path' => 'uploads/' . $newFileName
    ];
}


// Get initial from name or email
function getInitial($nameOrEmail) {
    if (!$nameOrEmail) return '';
    return strtoupper($nameOrEmail[0]);
}

// Find user by email in flattened user array
function findUserByEmail($users, $email) {
    foreach ($users as $bucket) {
        if (isset($bucket[$email])) {
            return $bucket[$email];
        }
    }
    return null;
}

//: Validate email format
function validateEmail($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL);
}

//: Sanitize input
function sanitizeInput($input) {
    return trim(strip_tags($input));
}

//: Redirect with error message
function redirectWithError($url, $error) {
    $_SESSION['error'] = $error;
    header('Location: ' . $url);
    exit();
}

//: Redirect with success message
function redirectWithSuccess($url, $message) {
    $_SESSION['success'] = $message;
    header('Location: ' . $url);
    exit();
}

//: Display flash messages
function displayFlashMessages() {
    $output = '';
    
    if (isset($_SESSION['error'])) {
        $output .= '<div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4">';
        $output .= htmlspecialchars($_SESSION['error']);
        $output .= '</div>';
        unset($_SESSION['error']);
    }
    
    if (isset($_SESSION['success'])) {
        $output .= '<div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-4">';
        $output .= htmlspecialchars($_SESSION['success']);
        $output .= '</div>';
        unset($_SESSION['success']);
    }
    
    return $output;
}

// Handle file upload with validation

// Secure file download handler
function handleSecureDownload($filename, $uploadsDir = __DIR__ . '/../uploads/') {
    // Support both 'uploads/filename' and just 'filename'
    $filename = ltrim($filename, '/');
    if (strpos($filename, 'uploads/') === 0) {
        $filepath = dirname($uploadsDir) . '/' . $filename;
        $basename = basename($filename);
    } else {
        $filepath = $uploadsDir . basename($filename);
        $basename = basename($filename);
    }
    if (file_exists($filepath)) {
        header('Content-Description: File Transfer');
        header('Content-Type: application/octet-stream');
        header('Content-Disposition: attachment; filename="' . $basename . '"');
        header('Content-Length: ' . filesize($filepath));
        readfile($filepath);
        exit;
    } else {
        return false;
    }
}

// ====== PROFILE.PHP HELPER STUBS BELOW ======

// Get file extension from filename
function getFileExtension($filename) {
    return strtolower(pathinfo($filename, PATHINFO_EXTENSION));
}
// Only allow common image types
function isAllowedImageExtension($ext) {
    return in_array($ext, ['jpg', 'jpeg', 'png', 'gif', 'webp']);
}
// Generate unique profile image name
function generateUniqueProfileName($ext) {
    return 'profile_' . uniqid() . '.' . $ext;
}
// Move uploaded file (returns true on success, false on failure)
function moveUploadedFile($tmp, $dest) {
    return move_uploaded_file($tmp, $dest);
}
// Save a single user: removes old email if changed, and saves back
function saveUser(&$users, $user, $oldEmail, $newEmail) {
    $oldBucket = strtoupper($oldEmail[0]);
    $newBucket = strtoupper($newEmail[0]);
    // Remove old if email changed
    if ($oldEmail !== $newEmail && isset($users[$oldBucket][$oldEmail])) {
        unset($users[$oldBucket][$oldEmail]);
    }
    $users[$newBucket][$newEmail] = $user;
    saveUsers($users);
}
// Check password is at least 6 chars (add more rules as needed)
function isStrongPassword($pw) { return strlen($pw) >= 6; }
// Hash password using bcrypt
function hashPassword($pw) { return password_hash($pw, PASSWORD_DEFAULT); }
// Delete user from all buckets
function deleteUser(&$users, $user) {
    $bucket = strtoupper($user['email'][0]);
    unset($users[$bucket][$user['email']]);
    saveUsers($users);
}
// Load memories for one user (stub - adapt as needed)
function loadUserMemories($email) { 
    $memories = loadMemories();
    $result = [];
    foreach ($memories as $mem) {
        if (($mem['user_email'] ?? '') === $email) $result[] = $mem;
    }
    return $result;
}
// Get N most recent memories by date
function getRecentMemories($memories, $n) {
    usort($memories, function($a, $b) {
        return strtotime($b['date'] ?? '') - strtotime($a['date'] ?? '');
    });
    return array_slice($memories, 0, $n);
}
// Get profile image URL, fallback to default
function getProfileImageUrl($user) {
    if (!empty($user['profile_pic'])) {
        return '/memorybookdsa/' . $user['profile_pic'];
    }
    return 'https://ui-avatars.com/api/?name=' . urlencode($user['first_name'] ?? $user['username'] ?? 'U');
}
// ====== END PROFILE.PHP HELPER STUBS ======
?> 