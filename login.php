<?php
session_start();
require_once __DIR__ . '/../data_structures/bucketed_user_store.php';
require_once __DIR__ . '/../data_structures/common_functions.php';

$login_error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = isset($_POST['email']) ? strtolower(trim($_POST['email'])) : '';
    $password = isset($_POST['password']) ? $_POST['password'] : '';
    if (!$email || !$password) {
        $login_error = 'Please enter both email and password.';
    } else {
        $user = get_user($email);
        if (!$user) {
            $login_error = 'User not found.';
        } elseif (!function_exists('verifyPassword')) {
            $login_error = 'Password verification function missing.';
        } elseif (!verifyPassword($password, $user['password'])) {
            $login_error = 'Incorrect password.';
        } else {
            $_SESSION['email'] = $email;
            header('Location: homepage.php');
            exit();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <meta name="description" content="Login to MemoryBook - Connect with friends and share memories" />
    <meta name="keywords" content="login, authentication, memories, friends, social network" />
    <meta name="author" content="MemoryBook" />
    <meta name="robots" content="noindex, nofollow" />
    <title>Login - MemoryBook</title>
    <link rel="preconnect" href="https://fonts.googleapis.com" />
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" crossorigin="anonymous" referrerpolicy="no-referrer" />
    <link rel="stylesheet" href="output.css" />
    <link href="https://fonts.googleapis.com/css2?family=Kalam:wght@400;700&family=Quicksand:wght@400;500;700&display=swap" rel="stylesheet">
    <style>
        html, body { font-family: 'Quicksand', sans-serif; }
        .font-kalam { font-family: 'Kalam', cursive !important; }
        /* Enhanced Background Styles */
        body {
            background: linear-gradient(135deg, rgba(139, 126, 200, 0.1) 0%, rgba(168, 200, 236, 0.08) 50%, rgba(244, 166, 166, 0.06) 100%);
            min-height: 100vh;
        }
        
        .login-container {
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
        
        .btn-secondary {
            background: rgba(255, 255, 255, 0.8);
            border: 2px solid rgba(139, 126, 200, 0.2);
            transition: all 0.3s ease;
        }
        
        .btn-secondary:hover {
            background: rgba(139, 126, 200, 0.05);
            border-color: #8B7EC8;
        }
        
        .social-btn {
            transition: all 0.3s ease;
        }
        
        .social-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
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
        
        .password-toggle {
            cursor: pointer;
            transition: color 0.3s ease;
        }
        
        .password-toggle:hover {
            color: #8B7EC8;
        }
        
        /* Loading animation */
        .loading {
            display: none;
        }
        
        .loading.active {
            display: inline-block;
            animation: spin 1s linear infinite;
        }
        
        @keyframes spin {
            from { transform: rotate(0deg); }
            to { transform: rotate(360deg); }
        }
        
        /* Accessibility improvements */
        .focus-visible:focus-visible {
            outline: 2px solid #8B7EC8;
            outline-offset: 2px;
        }
     
    </style>
</head>
<body class="min-h-screen flex items-center justify-center p-4">
    <div class="w-full max-w-md">
        <!-- Logo and Header -->
        <div class="text-center mb-8">
            <a href="homepage.php" class="inline-flex items-center space-x-2 mb-4 hover:scale-105 transition-all duration-200 focus-visible" aria-label="Go to homepage">
                <i class="fas fa-heart text-4xl text-[#8B7EC8]" aria-hidden="true"></i>
                <span class="text-3xl font-bold text-[#2D2A3D]">MemoryBook</span>
            </a>
            <h1 class="text-2xl font-semibold text-[#2D2A3D] mb-2">Welcome back!</h1>
            <p class="text-[#6B6B7D]">Sign in to continue your journey with friends</p>
        </div>

        <!-- Login Form -->
        <div class="login-container p-8">
            <?php if ($login_error): ?>
                <div class="error-message p-3 rounded-lg text-sm mb-4"><?php echo htmlspecialchars($login_error); ?></div>
            <?php endif; ?>
            <!-- Error/Success Messages -->
            <div id="message-container" class="mb-6 hidden">
                <div id="error-message" class="error-message p-3 rounded-lg text-sm hidden"></div>
                <div id="success-message" class="success-message p-3 rounded-lg text-sm hidden"></div>
            </div>

            <form id="loginForm" class="space-y-6" method="POST" novalidate>
                <!-- Email Field -->
                <div>
                    <label for="email" class="block text-sm font-medium text-[#2D2A3D] mb-2">
                        Email Address
                    </label>
                    <div class="relative">
                        <i class="fas fa-envelope absolute left-3 top-1/2 transform -translate-y-1/2 text-[#6B6B7D]" aria-hidden="true"></i>
                        <input 
                            type="email" 
                            id="email" 
                            name="email" 
                            class="form-input w-full pl-10 pr-4 py-3 rounded-xl focus-visible"
                            placeholder="Enter your email address"
                            required
                            autocomplete="email"
                        />
                    </div>
                    <div class="text-red-500 text-xs mt-1 hidden" id="email-error"></div>
                </div>

                <!-- Password Field -->
                <div>
                    <label for="password" class="block text-sm font-medium text-[#2D2A3D] mb-2">
                        Password
                    </label>
                    <div class="relative">
                        <i class="fas fa-lock absolute left-3 top-1/2 transform -translate-y-1/2 text-[#6B6B7D]" aria-hidden="true"></i>
                        <input 
                            type="password" 
                            id="password" 
                            name="password" 
                            class="form-input w-full pl-10 pr-12 py-3 rounded-xl focus-visible"
                            placeholder="Enter your password"
                            required
                            autocomplete="current-password"
                        />
                        <button 
                            type="button" 
                            class="password-toggle absolute right-3 top-1/2 transform -translate-y-1/2 text-[#6B6B7D]"
                            aria-label="Toggle password visibility"
                        >
                            <i class="fas fa-eye" id="password-icon"></i>
                        </button>
                    </div>
                    <div class="text-red-500 text-xs mt-1 hidden" id="password-error"></div>
                </div>

                <!-- Remember Me & Forgot Password -->
                <div class="flex items-center justify-between">
                    <label class="flex items-center">
                        <input type="checkbox" id="remember" name="remember" class="rounded border-[#8B7EC8] text-[#8B7EC8] focus:ring-[#8B7EC8]" />
                        <span class="ml-2 text-sm text-[#6B6B7D]">Remember me</span>
                    </label>
                    <a href="#" class="text-sm text-[#8B7EC8] hover:text-[#7A6BB5] transition-colors focus-visible">
                        Forgot password?
                    </a>
                </div>

                <!-- Login Button -->
                <button 
                    type="submit" 
                    class="btn-primary w-full py-3 rounded-xl text-white font-medium focus-visible"
                    id="loginBtn"
                >
                    <span id="loginText">Sign In</span>
                    <i class="fas fa-spinner loading ml-2" id="loginSpinner"></i>
                </button>
            </form>

            <!-- Sign Up Link -->
            <div class="text-center mt-6">
                <p class="text-sm text-[#6B6B7D]">
                    Don't have an account? 
                    <a href="signup.php" class="text-[#8B7EC8] hover:text-[#7A6BB5] font-medium transition-colors focus-visible">
                        Sign up here
                    </a>
                </p>
            </div>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const loginForm = document.getElementById('loginForm');
            const emailInput = document.getElementById('email');
            const passwordInput = document.getElementById('password');
            const loginBtn = document.getElementById('loginBtn');
            const loginText = document.getElementById('loginText');
            const loginSpinner = document.getElementById('loginSpinner');
            const passwordToggle = document.querySelector('.password-toggle');
            const passwordIcon = document.getElementById('password-icon');
            const messageContainer = document.getElementById('message-container');
            const errorMessage = document.getElementById('error-message');
            const successMessage = document.getElementById('success-message');

            // Password visibility toggle
            passwordToggle.addEventListener('click', function() {
                const type = passwordInput.type === 'password' ? 'text' : 'password';
                passwordInput.type = type;
                passwordIcon.className = type === 'password' ? 'fas fa-eye' : 'fas fa-eye-slash';
            });

            // Form validation
            function validateEmail(email) {
                const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
                return emailRegex.test(email);
            }

            function showError(element, message) {
                const errorDiv = document.getElementById(element.id + '-error');
                errorDiv.textContent = message;
                errorDiv.classList.remove('hidden');
                element.classList.add('border-red-500');
            }

            function hideError(element) {
                const errorDiv = document.getElementById(element.id + '-error');
                errorDiv.classList.add('hidden');
                element.classList.remove('border-red-500');
            }

            function showMessage(type, message) {
                messageContainer.classList.remove('hidden');
                if (type === 'error') {
                    errorMessage.textContent = message;
                    errorMessage.classList.remove('hidden');
                    successMessage.classList.add('hidden');
                } else {
                    successMessage.textContent = message;
                    successMessage.classList.remove('hidden');
                    errorMessage.classList.add('hidden');
                }
            }

            function hideMessages() {
                messageContainer.classList.add('hidden');
                errorMessage.classList.add('hidden');
                successMessage.classList.add('hidden');
            }

            // Real-time validation
            emailInput.addEventListener('blur', function() {
                if (!this.value) {
                    showError(this, 'Email is required');
                } else if (!validateEmail(this.value)) {
                    showError(this, 'Please enter a valid email address');
                } else {
                    hideError(this);
                }
            });

            passwordInput.addEventListener('blur', function() {
                if (!this.value) {
                    showError(this, 'Password is required');
                } else if (this.value.length < 6) {
                    showError(this, 'Password must be at least 6 characters');
                } else {
                    hideError(this);
                }
            });

            // In the <script> block, remove the entire loginForm.addEventListener('submit', ...) handler and any setTimeout or simulated redirection logic. The form should submit normally to PHP.

        });
    </script>
</body>
</html>