<?php
require_once __DIR__ . '/../data_structures/bucketed_user_store.php';
require_once __DIR__ . '/../data_structures/common_functions.php';

$signup_error = '';
$signup_success = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = isset($_POST['username']) ? trim(strip_tags($_POST['username'])) : '';
    $firstName = isset($_POST['firstName']) ? trim(strip_tags($_POST['firstName'])) : '';
    $lastName = isset($_POST['lastName']) ? trim(strip_tags($_POST['lastName'])) : '';
    $email = isset($_POST['email']) ? strtolower(trim($_POST['email'])) : '';
    $password = isset($_POST['password']) ? $_POST['password'] : '';
    $confirmPassword = isset($_POST['confirmPassword']) ? $_POST['confirmPassword'] : '';

    // Username: 3-20 chars, letters, numbers, underscores
    $validUsername = preg_match('/^[A-Za-z0-9_]{3,20}$/', $username);
    // Email validation
    $validEmail = filter_var($email, FILTER_VALIDATE_EMAIL);
    // Password: at least 8 chars
    $strongPassword = strlen($password) >= 8;

    if (!$username || !$firstName || !$lastName || !$email || !$password || !$confirmPassword) {
        $signup_error = 'All fields are required.';
    } elseif (!$validUsername) {
        $signup_error = 'Username must be 3-20 characters and contain only letters, numbers, and underscores.';
    } elseif (username_exists($username)) {
        $signup_error = 'This username is already taken.';
    } elseif (!$validEmail) {
        $signup_error = 'Invalid email address.';
    } elseif ($password !== $confirmPassword) {
        $signup_error = 'Passwords do not match.';
    } elseif (!$strongPassword) {
        $signup_error = 'Password must be at least 8 characters.';
    } elseif (user_exists($email)) {
        $signup_error = 'An account with this email already exists.';
    } else {
        $profilePicPath = '';
        if (isset($_FILES['profilePic']) && $_FILES['profilePic']['error'] === UPLOAD_ERR_OK) {
            $file = $_FILES['profilePic'];
            $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
            $allowed = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
            if (in_array($ext, $allowed)) {
                $safeEmail = preg_replace('/[^a-z0-9]/i', '', $email);
                $target = __DIR__ . '/../uploads/' . $safeEmail . '.' . $ext;
                if (move_uploaded_file($file['tmp_name'], $target)) {
                    $profilePicPath = 'uploads/' . $safeEmail . '.' . $ext;
                } else {
                    $signup_error = 'Failed to upload profile picture.';
                }
            } else {
                $signup_error = 'Only image files (jpg, jpeg, png, gif, webp) are allowed for profile picture.';
            }
        }
        if (isset($_FILES['profilePic']) && $_FILES['profilePic']['error'] !== UPLOAD_ERR_OK && $_FILES['profilePic']['error'] !== UPLOAD_ERR_NO_FILE) {
            $signup_error = 'Profile picture upload error: ' . $_FILES['profilePic']['error'];
        }
        if (!$signup_error) {
            $details = [
                'username' => $username,
                'first_name' => $firstName,
                'last_name' => $lastName,
                'email' => $email,
                'password' => password_hash($password, PASSWORD_DEFAULT),
                'profile_pic' => $profilePicPath
            ];
            add_user($email, $details);
            $signup_success = 'Account created successfully! Redirecting to login...';
            header('Refresh: 1; URL=login.php');
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <title>Sign Up - MemoryBook</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" />
    <link rel="stylesheet" href="output.css" />
    <link href="https://fonts.googleapis.com/css2?family=Kalam:wght@400;700&family=Quicksand:wght@400;500;700&display=swap" rel="stylesheet">
    <style>
        html, body { font-family: 'Quicksand', sans-serif; }
        .font-kalam { font-family: 'Kalam', cursive !important; }
        body {
            background: linear-gradient(135deg, rgba(139, 126, 200, 0.1) 0%, rgba(168, 200, 236, 0.08) 50%, rgba(244, 166, 166, 0.06) 100%);
            min-height: 100vh;
        }
        .signup-container {
            backdrop-filter: blur(12px);
            background: rgba(255, 255, 255, 0.95);
            border-radius: 24px;
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.1);
            border: 1px solid rgba(139, 126, 200, 0.1);
        }
        .form-input {
            background: rgba(255, 255, 255, 0.8);
            border: 2px solid rgba(139, 126, 200, 0.1);
            transition: all 0.3s ease;
        }
        .form-input:focus {
            border-color: #8B7EC8;
            box-shadow: 0 0 0 3px rgba(139, 126, 200, 0.1);
            outline: none;
        }
        .btn-primary {
            background: linear-gradient(135deg, #8B7EC8 0%, #7A6BB5 100%);
            transition: all 0.3s ease;
        }
        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(139, 126, 200, 0.3);
        }
        .error-message {
            background: rgba(239, 68, 68, 0.1);
            border: 1px solid rgba(239, 68, 68, 0.2);
            color: #dc2626;
        }
        .success-message {
            background: rgba(34, 197, 94, 0.1);
            border: 1px solid rgba(34, 197, 94, 0.2);
            color: #16a34a;
        }
        .password-toggle { cursor: pointer; transition: color 0.3s ease; }
        .password-toggle:hover { color: #8B7EC8; }
        .password-strength { height: 4px; border-radius: 2px; transition: all 0.3s ease; }
        .strength-weak { background: #ef4444; }
        .strength-fair { background: #f59e0b; }
        .strength-good { background: #10b981; }
        .strength-strong { background: #059669; }
        @media (max-width: 640px) {
            .signup-container { margin: 1rem; padding: 1.5rem; }
        }
    </style>
</head>
<body class="min-h-screen flex items-center justify-center p-4">
    <div class="w-full max-w-md">
        <div class="text-center mb-8">
            <a href="homepage.php" class="inline-flex items-center space-x-2 mb-4 hover:scale-105 transition-all duration-200" aria-label="Go to homepage">
                <i class="fas fa-heart text-4xl text-[#8B7EC8]"></i>
                <span class="text-3xl font-bold text-[#2D2A3D]">MemoryBook</span>
            </a>
            <h1 class="text-2xl font-semibold text-[#2D2A3D] mb-2">Join MemoryBook</h1>
            <p class="text-[#6B6B7D]">Create your account and start sharing memories with friends</p>
        </div>
        <div class="signup-container p-8">
            <?php if ($signup_error): ?>
                <div class="error-message p-3 rounded-lg text-sm mb-4"><?php echo $signup_error; ?></div>
            <?php endif; ?>
            <?php if ($signup_success): ?>
                <div class="success-message p-3 rounded-lg text-sm mb-4"><?php echo $signup_success; ?></div>
            <?php endif; ?>
            <form id="signupForm" class="space-y-6" method="POST" enctype="multipart/form-data" novalidate>
                <div>
                    <label for="username" class="block text-sm font-medium text-[#2D2A3D] mb-2">Username</label>
                    <div class="relative">
                        <i class="fas fa-user-tag absolute left-3 top-1/2 transform -translate-y-1/2 text-[#6B6B7D]"></i>
                        <input type="text" id="username" name="username" class="form-input w-full pl-10 pr-4 py-3 rounded-xl" placeholder="Choose a username" required autocomplete="username" />
                    </div>
                </div>
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label for="firstName" class="block text-sm font-medium text-[#2D2A3D] mb-2">First Name</label>
                        <div class="relative">
                            <i class="fas fa-user absolute left-3 top-1/2 transform -translate-y-1/2 text-[#6B6B7D]"></i>
                            <input type="text" id="firstName" name="firstName" class="form-input w-full pl-10 pr-4 py-3 rounded-xl" placeholder="First name" required autocomplete="given-name" />
                        </div>
                    </div>
                    <div>
                        <label for="lastName" class="block text-sm font-medium text-[#2D2A3D] mb-2">Last Name</label>
                        <div class="relative">
                            <i class="fas fa-user absolute left-3 top-1/2 transform -translate-y-1/2 text-[#6B6B7D]"></i>
                            <input type="text" id="lastName" name="lastName" class="form-input w-full pl-10 pr-4 py-3 rounded-xl" placeholder="Last name" required autocomplete="family-name" />
                        </div>
                    </div>
                </div>
                <div>
                    <label for="email" class="block text-sm font-medium text-[#2D2A3D] mb-2">Email Address</label>
                    <div class="relative">
                        <i class="fas fa-envelope absolute left-3 top-1/2 transform -translate-y-1/2 text-[#6B6B7D]"></i>
                        <input type="email" id="email" name="email" class="form-input w-full pl-10 pr-4 py-3 rounded-xl" placeholder="Enter your email" required autocomplete="email" />
                    </div>
                </div>
                <div>
                    <label for="password" class="block text-sm font-medium text-[#2D2A3D] mb-2">Password</label>
                    <div class="relative">
                        <i class="fas fa-lock absolute left-3 top-1/2 transform -translate-y-1/2 text-[#6B6B7D]"></i>
                        <input type="password" id="password" name="password" class="form-input w-full pl-10 pr-12 py-3 rounded-xl" placeholder="Create a password" required autocomplete="new-password" />
                        <button type="button" class="password-toggle absolute right-3 top-1/2 transform -translate-y-1/2 text-[#6B6B7D]" aria-label="Toggle password visibility">
                            <i class="fas fa-eye" id="password-icon"></i>
                        </button>
                    </div>
                    <div class="mt-2">
                        <div class="flex space-x-1">
                            <div class="password-strength flex-1" id="strength-bar"></div>
                        </div>
                        <p class="text-xs text-[#6B6B7D] mt-1" id="strength-text">Password strength</p>
                    </div>
                </div>
                <div>
                    <label for="confirmPassword" class="block text-sm font-medium text-[#2D2A3D] mb-2">Confirm Password</label>
                    <div class="relative">
                        <i class="fas fa-lock absolute left-3 top-1/2 transform -translate-y-1/2 text-[#6B6B7D]"></i>
                        <input type="password" id="confirmPassword" name="confirmPassword" class="form-input w-full pl-10 pr-4 py-3 rounded-xl" placeholder="Confirm your password" required autocomplete="new-password" />
                    </div>
                </div>
                <div>
                    <label for="profilePic" class="block text-sm font-medium text-[#2D2A3D] mb-2">Profile Picture (optional)</label>
                    <input type="file" id="profilePic" name="profilePic" accept="image/*" class="hidden" />
                </div>
                <button type="submit" class="btn-primary w-full py-3 rounded-xl text-white font-medium">Create Account</button>
            </form>
            <div class="text-center mt-6">
                <p class="text-sm text-[#6B6B7D]">
                    Already have an account? 
                    <a href="login.php" class="text-[#8B7EC8] hover:text-[#7A6BB5] font-medium transition-colors">Sign in here</a>
                </p>
            </div>
        </div>
    </div>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const passwordInput = document.getElementById('password');
            const passwordToggle = document.querySelector('.password-toggle');
            const passwordIcon = document.getElementById('password-icon');
            const strengthBar = document.getElementById('strength-bar');
            const strengthText = document.getElementById('strength-text');
            passwordToggle.addEventListener('click', function() {
                const type = passwordInput.type === 'password' ? 'text' : 'password';
                passwordInput.type = type;
                passwordIcon.className = type === 'password' ? 'fas fa-eye' : 'fas fa-eye-slash';
            });
            function checkPasswordStrength(password) {
                let strength = 0;
                if (password.length >= 8) strength += 1;
                if (/[a-z]/.test(password)) strength += 1;
                if (/[A-Z]/.test(password)) strength += 1;
                if (/[0-9]/.test(password)) strength += 1;
                if (/[^A-Za-z0-9]/.test(password)) strength += 1;
                return strength;
            }
            function updatePasswordStrength(password) {
                const strength = checkPasswordStrength(password);
                const strengthClasses = ['', 'strength-weak', 'strength-fair', 'strength-good', 'strength-strong'];
                const strengthLabels = ['', 'Weak', 'Fair', 'Good', 'Strong'];
                strengthBar.className = `password-strength flex-1 ${strengthClasses[strength] || ''}`;
                strengthText.textContent = strength > 0 ? strengthLabels[strength] : 'Password strength';
            }
            passwordInput.addEventListener('input', function() {
                updatePasswordStrength(this.value);
            });
        });
    </script>
</body>
</html> 