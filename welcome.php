<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>MemoryBook - Your Digital Friendship Scrapbook</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" crossorigin="anonymous" referrerpolicy="no-referrer" />
    <link rel="stylesheet" href="blobs.css" />
    <link rel="stylesheet" href="output.css" />
    <link href="https://fonts.googleapis.com/css2?family=Kalam:wght@400;700&family=Quicksand:wght@400;500;700&display=swap" rel="stylesheet">
    <style>
        html, body { font-family: 'Quicksand', sans-serif; }
        .font-kalam { font-family: 'Kalam', cursive !important; }
        .enhanced-backdrop {
            backdrop-filter: blur(12px);
            background: rgba(255, 255, 255, 0.95);
            border-radius: 16px;
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.1);
            border: 1px solid rgba(139, 126, 200, 0.1);
        }
        .gradient-text {
            background: linear-gradient(135deg, #8B7EC8, #A8C8EC);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }
        .feature-card {
            transition: all 0.3s ease;
        }
        .feature-card:hover {
            transform: translateY(-8px);
            box-shadow: 0 20px 40px rgba(139, 126, 200, 0.15);
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

  
    <header class="bg-gray/80 backdrop-blur-md border-b border-gray-200 sticky top-0 z-50 shadow-sm">
        <nav class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between items-center h-16">
               
                <div class="flex items-center">
                    <a href="#" class="flex items-center space-x-2 hover:scale-105 transition-transform">
                        <i class="fas fa-heart text-2xl text-[#8B7EC8]"></i>
                        <span class="text-xl font-semibold text-[#2D2A3D]">MemoryBook</span>
                    </a>
                </div>
                <div class="flex items-center space-x-4">
                    <a href="login.php" class="px-4 py-2 rounded-lg border border-[#8B7EC8] text-[#8B7EC8] hover:bg-[#8B7EC8] hover:text-white transition-colors font-medium">
                        Login
                    </a>
                    <a href="signup.php" class="px-4 py-2 rounded-lg bg-[#8B7EC8] hover:bg-[#7A6BB5] transition-colors font-medium text-white">
                        Sign Up
                    </a>
                </div>
            </div>
        </nav>
    </header>
    <section class="relative overflow-hidden py-20 lg:py-32">
        <div class="relative max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="grid lg:grid-cols-2 gap-12 items-center">
                
                <div class="text-center lg:text-left">
                    <h1 class="text-4xl sm:text-5xl lg:text-6xl font-bold leading-tight mb-6 text-[#2D2A3D]">
                        Your Digital <span class="gradient-text">Friendship Scrapbook</span>
                    </h1>
                    <p class="text-xl sm:text-2xl mb-8 max-w-2xl mx-auto lg:mx-0 text-[#6B6B7D] leading-relaxed">
                        Store, share, and preserve your most precious memories with friends. 
                        <span class="font-semibold text-[#8B7EC8]">MemoryBook</span> offers secure memory storage, easy sharing, time capsule features, and more to create your perfect digital scrapbook.
                    </p>
                   
                    <div class="flex flex-wrap gap-4 mb-8 justify-center lg:justify-start">
                        <span class="px-4 py-2 bg-[#ede9fa] text-[#8B7EC8] rounded-full text-sm font-medium">
                            <i class="fas fa-shield-alt mr-2"></i>Secure Storage
                        </span>
                        <span class="px-4 py-2 bg-[#d1c7eb] text-[#8B7EC8] rounded-full text-sm font-medium">
                            <i class="fas fa-share-alt mr-2"></i>Easy Sharing
                        </span>
                        <span class="px-4 py-2 bg-[#FDE8E8] text-[#F4A6A6] rounded-full text-sm font-medium">
                            <i class="fas fa-clock mr-2"></i>Time Capsule
                        </span>
                    </div>
               
                    <div class="flex flex-col sm:flex-row gap-4 justify-center lg:justify-start">
                        <a href="signup.php" class="px-8 py-4 rounded-xl font-semibold hover:scale-105 transition-transform shadow-lg text-white bg-[#8B7EC8] hover:bg-[#7A6BB5]">
                            Start Your Journey
                        </a>
                        <a href="login.php" class="bg-white px-8 py-4 rounded-xl font-semibold hover:bg-gray-50 transition-colors shadow-lg border border-gray-200">
                            <span class="text-[#8B7EC8]">Login</span>
                        </a>
                    </div>
                </div>

                <div class="relative">
                    <div class="relative z-10">
                        <img src="https://images.unsplash.com/photo-1529156069898-49953e39b3ac?q=80&w=2832&auto=format&fit=crop&ixlib=rb-4.0.3&ixid=M3wxMjA3fDB8MHxwaG90by1wYWdlfHx8fGVufDB8fHx8fA%3D%3D" alt="Friends creating memories together" class="w-full h-96 lg:h-[500px] object-cover rounded-2xl shadow-xl hover:scale-105 transition-transform" loading="lazy" />
                    </div>
                    <div class="absolute -top-4 -right-4 w-20 h-20 rounded-full opacity-60 bg-[#F9BABA] blur-xl"></div>
                    <div class="absolute -bottom-6 -left-6 w-16 h-16 rounded-full opacity-60 bg-[#CCE3F7] blur-lg"></div>
                    <div class="absolute top-1/2 -right-8 w-12 h-12 rounded-full opacity-40 bg-[#D1C7EB] blur-md"></div>
                </div>
            </div>
        </div>
    </section>
    <section class="relative py-20 bg-white/50">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="text-center mb-16">
                <h2 class="text-3xl sm:text-4xl font-bold mb-4 text-[#2D2A3D]">
                    Powerful Features for Memory Keepers
                </h2>
                <p class="text-xl max-w-3xl mx-auto text-[#6B6B7D]">
                    MemoryBook combines cutting-edge data structures with beautiful design to create the ultimate digital scrapbook experience.
                </p>
            </div>
            <div class="grid md:grid-cols-2 lg:grid-cols-3 gap-8">
               
                <div class="feature-card enhanced-backdrop p-8">
                    <div class="w-16 h-16 rounded-xl flex items-center justify-center mb-6 bg-[#8B7EC8]">
                        <i class="fas fa-shield-alt text-2xl text-white"></i>
                    </div>
                    <h3 class="text-xl font-bold mb-4 text-[#2D2A3D]">Secure Memory Storage</h3>
                    <p class="text-[#6B6B7D] mb-4">
                        Your precious memories are safely stored and protected. Never lose a moment with our reliable, secure storage system.
                    </p>
                    <ul class="text-sm text-[#6B6B7D] space-y-2">
                        <li><i class="fas fa-check text-[#8B7EC8] mr-2"></i>Safe and encrypted storage</li>
                        <li><i class="fas fa-check text-[#8B7EC8] mr-2"></i>Automatic backup system</li>
                        <li><i class="fas fa-check text-[#8B7EC8] mr-2"></i>Never lose your memories</li>
                    </ul>
                </div>
                <div class="feature-card enhanced-backdrop p-8">
                    <div class="w-16 h-16 rounded-xl flex items-center justify-center mb-6 bg-[#A8C8EC]">
                        <i class="fas fa-share-alt text-2xl text-white"></i>
                    </div>
                    <h3 class="text-xl font-bold mb-4 text-[#2D2A3D]">Easy Memory Sharing</h3>
                    <p class="text-[#6B6B7D] mb-4">
                        Share your memories effortlessly with friends and family. Create shared albums and collaborative memory collections.
                    </p>
                    <ul class="text-sm text-[#6B6B7D] space-y-2">
                        <li><i class="fas fa-check text-[#8B7EC8] mr-2"></i>One-click memory sharing</li>
                        <li><i class="fas fa-check text-[#8B7EC8] mr-2"></i>Collaborative albums</li>
                        <li><i class="fas fa-check text-[#8B7EC8] mr-2"></i>Privacy controls</li>
                    </ul>
                </div>
                <div class="feature-card enhanced-backdrop p-8">
                    <div class="w-16 h-16 rounded-xl flex items-center justify-center mb-6 bg-[#F4A6A6]">
                        <i class="fas fa-clock text-2xl text-white"></i>
                    </div>
                    <h3 class="text-xl font-bold mb-4 text-[#2D2A3D]">Time Capsule Feature</h3>
                    <p class="text-[#6B6B7D] mb-4">
                        Create time capsules to preserve memories for future viewing. Set reminders and rediscover moments at the perfect time.
                    </p>
                    <ul class="text-sm text-[#6B6B7D] space-y-2">
                        <li><i class="fas fa-check text-[#8B7EC8] mr-2"></i>Future memory scheduling</li>
                        <li><i class="fas fa-check text-[#8B7EC8] mr-2"></i>Automatic reminders</li>
                        <li><i class="fas fa-check text-[#8B7EC8] mr-2"></i>Surprise memory reveals</li>
                    </ul>
                </div>
                <div class="feature-card enhanced-backdrop p-8">
                    <div class="w-16 h-16 rounded-xl flex items-center justify-center mb-6 bg-[#D1C7EB]">
                        <i class="fas fa-map-marker-alt text-2xl text-white"></i>
                    </div>
                    <h3 class="text-xl font-bold mb-4 text-[#2D2A3D]">Interactive Memory Maps</h3>
                    <p class="text-[#6B6B7D] mb-4">
                        Visualize your memories geographically with interactive maps. Pin memories to locations and see your adventures unfold across the world.
                    </p>
                    <ul class="text-sm text-[#6B6B7D] space-y-2">
                        <li><i class="fas fa-check text-[#8B7EC8] mr-2"></i>Location-based organization</li>
                        <li><i class="fas fa-check text-[#8B7EC8] mr-2"></i>Interactive map navigation</li>
                        <li><i class="fas fa-check text-[#8B7EC8] mr-2"></i>Travel memory tracking</li>
                    </ul>
                </div>
                <div class="feature-card enhanced-backdrop p-8">
                    <div class="w-16 h-16 rounded-xl flex items-center justify-center mb-6 bg-[#FDE8E8]">
                        <i class="fas fa-stream text-2xl text-[#F4A6A6]"></i>
                    </div>
                    <h3 class="text-xl font-bold mb-4 text-[#2D2A3D]">Chronological Timeline</h3>
                    <p class="text-[#6B6B7D] mb-4">
                        Journey through your memories in perfect chronological order. Navigate seamlessly through your life's timeline with ease.
                    </p>
                    <ul class="text-sm text-[#6B6B7D] space-y-2">
                        <li><i class="fas fa-check text-[#8B7EC8] mr-2"></i>Perfect memory sequencing</li>
                        <li><i class="fas fa-check text-[#8B7EC8] mr-2"></i>Seamless navigation</li>
                        <li><i class="fas fa-check text-[#8B7EC8] mr-2"></i>Memory relationship tracking</li>
                    </ul>
                </div>

                Feature 6: Mood Boards & Organization -->
                <div class="feature-card enhanced-backdrop p-8">
                    <div class="w-16 h-16 rounded-xl flex items-center justify-center mb-6 bg-[#E6F1FB]">
                        <i class="fas fa-palette text-2xl text-[#A8C8EC]"></i>
                    </div>
                    <h3 class="text-xl font-bold mb-4 text-[#2D2A3D]">Mood Boards & Organization</h3>
                    <p class="text-[#6B6B7D] mb-4">
                        Create beautiful mood boards and organize memories by themes, emotions, and seasons. Customize your visual memory collections.
                    </p>
                    <ul class="text-sm text-[#6B6B7D] space-y-2">
                        <li><i class="fas fa-check text-[#8B7EC8] mr-2"></i>Theme-based organization</li>
                        <li><i class="fas fa-check text-[#8B7EC8] mr-2"></i>Emotional memory tagging</li>
                        <li><i class="fas fa-check text-[#8B7EC8] mr-2"></i>Seasonal collections</li>
                    </ul>
                </div>
            </div>
        </div>
    </section>
    <section class="relative py-20 bg-white">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="text-center mb-16">
                <h2 class="text-3xl sm:text-4xl font-bold mb-4 text-[#2D2A3D]">
                    How MemoryBook Works
                </h2>
                <p class="text-xl max-w-3xl mx-auto text-[#6B6B7D]">
                    Simple steps to start preserving your precious memories and friendships.
                </p>
            </div>
            <div class="grid md:grid-cols-3 gap-8">
                                
                 <div class="text-center p-8 enhanced-backdrop">
                     <div class="w-20 h-20 mx-auto mb-6 rounded-full flex items-center justify-center bg-[#8B7EC8] text-white text-2xl font-bold">
                         1
                     </div>
                    <h3 class="text-xl font-bold mb-4 text-[#2D2A3D]">Create Your Account</h3>
                    <p class="text-[#6B6B7D]">
                        Sign up in seconds and start building your digital scrapbook. Your memories are safe and secure with us.
                    </p>
                </div>

                              
                 <div class="text-center p-8 enhanced-backdrop">
                     <div class="w-20 h-20 mx-auto mb-6 rounded-full flex items-center justify-center bg-[#A8C8EC] text-white text-2xl font-bold">
                         2
                     </div>
                    <h3 class="text-xl font-bold mb-4 text-[#2D2A3D]">Add Friends & Memories</h3>
                    <p class="text-[#6B6B7D]">
                        Invite friends and start creating memories together. Add photos, locations, and emotions to each moment.
                    </p>
                </div>

                                 
                 <div class="text-center p-8 enhanced-backdrop">
                     <div class="w-20 h-20 mx-auto mb-6 rounded-full flex items-center justify-center bg-[#F4A6A6] text-white text-2xl font-bold">
                         3
                     </div>
                    <h3 class="text-xl font-bold mb-4 text-[#2D2A3D]">Explore & Relive</h3>
                    <p class="text-[#6B6B7D]">
                        Navigate through your memories using interactive maps, timelines, and mood boards. Relive every special moment.
                    </p>
                </div>
            </div>
        </div>
    </section>

    <section class="relative py-20 text-center" style="background: linear-gradient(135deg, #8B7EC8, #A8C8EC);">
        <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8">
            <h2 class="text-3xl sm:text-4xl font-bold text-white mb-6">
                Ready to Start Your Memory Journey?
            </h2>
            <p class="text-xl text-white/90 mb-8 max-w-2xl mx-auto">
                Join thousands of users who are already preserving their precious friendships and memories with MemoryBook.
            </p>
            <div class="flex flex-col sm:flex-row gap-4 justify-center">
                <a href="signup.php" class="bg-white px-8 py-4 rounded-xl font-bold hover:scale-105 transition-transform shadow-lg text-[#8B7EC8]">
                    Start Free Today
                </a>
                <a href="login.php" class="bg-white/20 backdrop-blur-sm text-white px-8 py-4 rounded-xl font-bold hover:bg-white/30 transition-colors border border-white/30">
                    Login
                </a>
            </div>
        </div>
    </section>
    <footer class="text-white py-12" style="background-color: #2D2A3D;">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="grid md:grid-cols-4 gap-8">
        
                <div class="md:col-span-2">
                    <div class="flex items-center space-x-2 mb-4">
                        <i class="fas fa-heart text-2xl" style="color: #8B7EC8;"></i>
                        <span class="text-xl font-bold">MemoryBook</span>
                    </div>
                    <p class="text-gray-300 mb-4 max-w-md">
                        Your digital friendship scrapbook powered by advanced data structures and beautiful design. 
                        Preserve memories, strengthen friendships, and create lasting connections.
                    </p>
                </div>
                <div>
                    <h4 class="font-bold mb-4">Quick Links</h4>
                    <ul class="space-y-2">
                        <li><a href="login.php" class="text-gray-300 hover:text-white transition-colors">Login</a></li>
                        <li><a href="signup.php" class="text-gray-300 hover:text-white transition-colors">Sign Up</a></li>
                    </ul>
                </div>
                <div>
                    <h4 class="font-bold mb-4">Features</h4>
                    <ul class="space-y-2">
                        <li><span class="text-gray-300">Interactive Memory Maps</span></li>
                        <li><span class="text-gray-300">Chronological Timeline</span></li>
                        <li><span class="text-gray-300">Friendship Networks</span></li>
                        <li><span class="text-gray-300">Mood Boards</span></li>
                        <li><span class="text-gray-300">Advanced Data Structures</span></li>
                    </ul>
                </div>
            </div>
            <div class="border-t border-gray-700 mt-8 pt-8 text-center">
                <p class="text-gray-300">
                    © 2025 MemoryBook. All Rights Reserved. Made with ❤️ for preserving friendships and memories.
                </p>
            </div>
        </div>
    </footer>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Welcome page initialization
            console.log('MemoryBook welcome page loaded successfully');
          
            document.querySelectorAll('a[href^="#"]').forEach(anchor => {
                anchor.addEventListener('click', function (e) {
                    e.preventDefault();
                    const target = document.querySelector(this.getAttribute('href'));
                    if (target) {
                        target.scrollIntoView({
                            behavior: 'smooth',
                            block: 'start'
                        });
                    }
                });
            });
        });
    </script>
</body>
</html> 