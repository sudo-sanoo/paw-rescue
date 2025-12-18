<?php
session_start();
require_once "includes/db.php";
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>PawRescue</title>
    
    <!-- CSS -->
    <link rel="stylesheet" href="css/index.css">
    <!-- JS -->
    <script defer src="js/index.js"></script>
    <!-- Tailwind CSS -->
    <script src="https://cdn.tailwindcss.com"></script>
    
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Nunito:wght@400;600;700;800&display=swap" rel="stylesheet">
    <!-- FontAwesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- Favicon -->
    <link rel="icon" href="favicon.svg">

    <script>
        tailwind.config = {
            theme: {
                extend: {
                    fontFamily: {
                        sans: ['Nunito', 'sans-serif'],
                    },
                    colors: {
                        brand: {
                            light: '#FDE68A', // Amber 200
                            DEFAULT: '#F59E0B', // Amber 500
                            dark: '#B45309', // Amber 700
                            accent: '#10B981', // Emerald 500
                        }
                    },
                    animation: {
                        'float': 'float 6s ease-in-out infinite',
                        'float-delayed': 'float 6s ease-in-out 3s infinite',
                        'paw-walk': 'pawWalk 10s linear infinite',
                    },
                    keyframes: {
                        float: {
                            '0%, 100%': { transform: 'translateY(0)' },
                            '50%': { transform: 'translateY(-20px)' },
                        },
                        pawWalk: {
                            '0%': { opacity: '0', transform: 'translate(0, 0) rotate(0deg)' },
                            '10%': { opacity: '1' },
                            '90%': { opacity: '1' },
                            '100%': { opacity: '0', transform: 'translate(100px, -100px) rotate(20deg)' },
                        }
                    }
                }
            }
        }
    </script>

</head>
<body class="font-sans text-gray-800 antialiased bg-gray-50">

    <!-- Navbar (Fixed Top) -->
    <nav class="fixed top-0 left-0 right-0 z-50 bg-white shadow-lg backdrop-blur-sm bg-opacity-95">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between items-center h-16">
                <!-- Logo -->
                <a href="#home" class="flex items-center space-x-2 text-xl font-extrabold text-gray-900">
                    <i class="fa-solid fa-paw text-brand text-2xl"></i>
                    <span>Paw<span class="text-brand">Rescue</span></span>
                </a>
                
                <!-- Desktop Menu -->
                <div class="hidden md:flex space-x-6 lg:space-x-8">
                    <a href="#home" class="text-gray-600 hover:text-brand transition duration-150 font-semibold">Home</a>
                    <a href="#problems" class="text-gray-600 hover:text-brand transition duration-150 font-semibold">The Reality</a>
                    <a href="#solution" class="text-gray-600 hover:text-brand transition duration-150 font-semibold">Our Solution</a>
                    <a href="#auth" class="bg-brand text-white py-1 px-4 rounded-full font-bold hover:bg-brand-dark transition duration-150 shadow-md">Join Us</a>
                </div>

                <!-- Mobile Menu Button -->
                <button id="mobile-menu-button" class="md:hidden text-gray-600 hover:text-brand focus:outline-none" onclick="toggleMobileMenu()">
                    <i class="fa-solid fa-bars text-xl"></i>
                </button>
            </div>
        </div>

        <!-- Mobile Menu Dropdown -->
        <div id="mobile-menu" class="hidden md:hidden pb-2">
            <a href="#home" onclick="closeMobileMenu()" class="block px-3 py-2 text-base font-medium text-gray-700 hover:bg-orange-50 hover:text-brand transition duration-150 border-t">Home</a>
            <a href="#problems" onclick="closeMobileMenu()" class="block px-3 py-2 text-base font-medium text-gray-700 hover:bg-orange-50 hover:text-brand transition duration-150">The Reality</a>
            <a href="#solution" onclick="closeMobileMenu()" class="block px-3 py-2 text-base font-medium text-gray-700 hover:bg-orange-50 hover:text-brand transition duration-150">Our Solution</a>
            <a href="#auth" onclick="closeMobileMenu()" class="block px-3 py-2 text-base font-medium text-brand font-bold bg-orange-50 border-t">Join Us Now</a>
        </div>
    </nav>
    
    <!-- Custom Modal/Message Box -->
    <div id="custom-modal" class="fixed inset-0 bg-black bg-opacity-50 hidden items-center justify-center z-[100] p-4" onclick="closeModal()">
        <div class="bg-white p-8 rounded-xl shadow-2xl max-w-sm w-full transform transition-all duration-300" onclick="event.stopPropagation()">
            <div class="text-center">
                <i id="modal-icon" class="fa-solid fa-check-circle text-brand text-5xl mb-4 animate-pulse-slow"></i>
                <h3 id="modal-title" class="text-2xl font-bold text-gray-800 mb-2">Success!</h3>
                <p id="modal-message" class="text-gray-600 mb-6">Action completed successfully (Demo Mode).</p>
                <button onclick="closeModal()" class="bg-brand hover:bg-brand-dark text-white font-bold py-2 px-6 rounded-lg transition">
                    Got it
                </button>
            </div>
        </div>
    </div>

    <div class="snap-container">
        
        <!-- SECTION 1: INTRO -->
        <section id="home" class="flex flex-col items-center justify-center bg-orange-50 relative">
            <!-- Background Decor -->
            <div class="absolute top-20 left-10 text-brand-DEFAULT opacity-20 text-6xl animate-float">
                <i class="fa-solid fa-paw"></i>
            </div>
            <div class="absolute bottom-20 right-20 text-brand-dark opacity-20 text-5xl animate-float-delayed">
                <i class="fa-solid fa-paw"></i>
            </div>
            <div class="absolute top-1/3 right-10 text-brand opacity-10 text-8xl">
                <i class="fa-solid fa-heart"></i>
            </div>

            <!-- Content -->
            <div class="z-10 text-center px-4 max-w-4xl">
                <div class="flex items-center justify-center space-x-4 mb-6">
                    <i class="fa-solid fa-paw text-5xl md:text-7xl text-brand animate-bounce"></i>
                    <h1 class="text-5xl md:text-8xl font-extrabold text-gray-900 tracking-tight">
                        Paw<span class="text-brand">Rescue</span>
                    </h1>
                    <i class="fa-solid fa-paw text-5xl md:text-7xl text-brand animate-bounce" style="animation-delay: 0.1s;"></i>
                </div>
                
                <p class="text-xl md:text-2xl text-gray-600 font-light mt-4 max-w-2xl mx-auto">
                    Every tail has a tale. Be the hero they’ve been waiting for.
                </p>
                
                <div class="mt-10">
                    <a href="#problems" class="bg-brand hover:bg-brand-dark text-white font-bold py-3 px-8 rounded-full text-lg transition transform hover:scale-105 shadow-lg inline-flex items-center gap-2">
                        Start the Journey <i class="fa-solid fa-arrow-down"></i>
                    </a>
                </div>
            </div>
            
            <!-- Scroll Indicator -->
            <div class="absolute bottom-8 animate-bounce text-gray-400">
                <i class="fa-solid fa-chevron-down text-2xl"></i>
            </div>
        </section>


        <!-- SECTION 2: PROBLEMS (100vh) - SLIDER -->
        <section id="problems" class="bg-white flex flex-col items-center justify-center relative">
            
            <div class="text-center mb-8 z-10 px-4">
                <h2 class="text-3xl md:text-5xl font-bold text-gray-800">The Reality</h2>
                <p class="text-gray-500 mt-2">Three major challenges animals face today.</p>
            </div>

            <!-- Slider Container -->
            <div class="relative w-full max-w-5xl h-[60vh] bg-gray-100 rounded-3xl shadow-2xl overflow-hidden mx-4">
                
                <!-- Slide 1 -->
                <div class="slide active flex flex-col md:flex-row h-full" id="slide-1">
                    <div class="w-full md:w-1/2 h-1/2 md:h-full bg-gray-200 relative overflow-hidden group">
                        <img src="images/homeless_dog.avif" alt="Stray Dog" class="w-full h-full object-cover transition duration-700 group-hover:scale-110">
                        <div class="absolute inset-0 bg-black bg-opacity-30 flex items-center justify-center">
                            <i class="fa-solid fa-house-crack text-white text-6xl opacity-80"></i>
                        </div>
                    </div>
                    <div class="w-full md:w-1/2 h-1/2 md:h-full p-8 md:p-12 flex flex-col justify-center bg-white">
                        <h3 class="text-3xl font-bold text-red-600 mb-4">1. Homelessness</h3>
                        <p class="text-lg text-gray-600 leading-relaxed">
                            Millions of animals wander the streets without food, shelter, or love. Overpopulation leads to overcrowded shelters and tragic outcomes for healthy animals.
                        </p>
                    </div>
                </div>

                <!-- Slide 2 -->
                <div class="slide flex flex-col md:flex-row h-full" id="slide-2">
                    <div class="w-full md:w-1/2 h-1/2 md:h-full bg-gray-200 relative overflow-hidden group">
                        <img src="images/injured_cat.jpg" alt="Injured Cat" class="w-full h-full object-cover transition duration-700 group-hover:scale-110">
                        <div class="absolute inset-0 bg-black bg-opacity-30 flex items-center justify-center">
                            <i class="fa-solid fa-user-injured text-white text-6xl opacity-80"></i>
                        </div>
                    </div>
                    <div class="w-full md:w-1/2 h-1/2 md:h-full p-8 md:p-12 flex flex-col justify-center bg-white">
                        <h3 class="text-3xl font-bold text-orange-600 mb-4">2. Medical Neglect</h3>
                        <p class="text-lg text-gray-600 leading-relaxed">
                            Injuries from accidents, fights, or diseases often go untreated. Simple infections can become fatal without timely veterinary intervention.
                        </p>
                    </div>
                </div>

                <!-- Slide 3 -->
                <div class="slide flex flex-col md:flex-row h-full" id="slide-3">
                    <div class="w-full md:w-1/2 h-1/2 md:h-full bg-gray-200 relative overflow-hidden group">
                        <img src="images/sad_dog.avif" alt="Sad Dog" class="w-full h-full object-cover transition duration-700 group-hover:scale-110">
                        <div class="absolute inset-0 bg-black bg-opacity-30 flex items-center justify-center">
                            <i class="fa-solid fa-hand-holding-heart text-white text-6xl opacity-80"></i>
                        </div>
                    </div>
                    <div class="w-full md:w-1/2 h-1/2 md:h-full p-8 md:p-12 flex flex-col justify-center bg-white">
                        <h3 class="text-3xl font-bold text-blue-600 mb-4">3. Lack of Resources</h3>
                        <p class="text-lg text-gray-600 leading-relaxed">
                            Rescuers are often overwhelmed. There is a disconnect between people who want to help and the resources (money, transport, foster homes) needed to do so.
                        </p>
                    </div>
                </div>

                <!-- Controls -->
                <button onclick="prevSlide()" class="absolute left-4 top-1/2 transform -translate-y-1/2 bg-white bg-opacity-80 hover:bg-white p-3 rounded-full shadow-lg text-gray-800 transition z-20">
                    <i class="fa-solid fa-chevron-left text-xl"></i>
                </button>
                <button onclick="nextSlide()" class="absolute right-4 top-1/2 transform -translate-y-1/2 bg-white bg-opacity-80 hover:bg-white p-3 rounded-full shadow-lg text-gray-800 transition z-20">
                    <i class="fa-solid fa-chevron-right text-xl"></i>
                </button>
                
                <!-- Dots -->
                <div class="absolute bottom-4 left-1/2 transform -translate-x-1/2 flex space-x-2 z-20">
                    <button onclick="goToSlide(0)" class="dot w-3 h-3 rounded-full bg-brand"></button>
                    <button onclick="goToSlide(1)" class="dot w-3 h-3 rounded-full bg-gray-800"></button>
                    <button onclick="goToSlide(2)" class="dot w-3 h-3 rounded-full bg-gray-800"></button>
                </div>
            </div>

        </section>


        <!-- SECTION 3: SOLUTION (100vh) -->
        <section id="solution" class="bg-gray-900 text-white flex items-center justify-center">
            <div class="container mx-auto px-6 py-12 h-full flex flex-col justify-center">
                
                <div class="text-center mb-12">
                    <h2 class="text-4xl md:text-6xl font-bold mb-4 text-brand">We Are The Bridge</h2>
                    <p class="text-xl text-gray-400 max-w-2xl mx-auto">PawRescue connects caring hearts with animals in need through technology.</p>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-3 gap-8 max-w-6xl mx-auto w-full">
                    
                    <!-- Feature 1 -->
                    <div class="bg-gray-800 p-8 rounded-2xl border border-gray-700 hover:border-brand transition duration-300 transform hover:-translate-y-2 shadow-xl flex flex-col items-center text-center">
                        <div class="w-16 h-16 bg-red-500 bg-opacity-10 rounded-full flex items-center justify-center mb-6 text-red-500 text-3xl">
                            <i class="fa-solid fa-location-dot"></i>
                        </div>
                        <h3 class="text-2xl font-bold mb-3">Geo-Tag Rescue</h3>
                        <p class="text-gray-400">Spot an injured or missing animal? Snap a photo and drop a pin. Our app alerts nearby rescuers and vets instantly.</p>
                    </div>

                    <!-- Feature 2 -->
                    <div class="bg-gray-800 p-8 rounded-2xl border border-gray-700 hover:border-brand transition duration-300 transform hover:-translate-y-2 shadow-xl flex flex-col items-center text-center">
                        <div class="w-16 h-16 bg-blue-500 bg-opacity-10 rounded-full flex items-center justify-center mb-6 text-blue-500 text-3xl">
                            <i class="fa-solid fa-kit-medical"></i>
                        </div>
                        <h3 class="text-2xl font-bold mb-3">Vet Network</h3>
                        <p class="text-gray-400">We partner with local clinics to provide subsidized emergency care funded by our community donations.</p>
                    </div>

                    <!-- Feature 3 -->
                    <div class="bg-gray-800 p-8 rounded-2xl border border-gray-700 hover:border-brand transition duration-300 transform hover:-translate-y-2 shadow-xl flex flex-col items-center text-center">
                        <div class="w-16 h-16 bg-brand bg-opacity-10 rounded-full flex items-center justify-center mb-6 text-brand text-3xl">
                            <i class="fa-solid fa-hand-holding-heart"></i>
                        </div>
                        <h3 class="text-2xl font-bold mb-3">Foster & Adopt</h3>
                        <p class="text-gray-400">Swipe through verified profiles to find your new best friend or offer a temporary home to a recovering soul.</p>
                    </div>

                </div>
            </div>
        </section>


        <!-- SECTION 4: AUTH & FOOTER (100vh) -->
        <section id="auth" class="bg-gradient-to-br from-orange-100 to-white flex flex-col justify-center h-screen">
            
            <!-- Main Content Area -->
            <div class="flex-grow flex items-center justify-center px-4 py-8 w-full">
                <div class="bg-white rounded-3xl shadow-2xl overflow-hidden w-full max-w-4xl flex flex-col md:flex-row min-h-[500px]">
                    
                    <!-- Left Panel: CTA -->
                    <div class="w-full md:w-2/5 bg-brand p-8 md:p-12 text-white flex flex-col justify-center relative overflow-hidden">
                        <div class="absolute top-0 left-0 w-full h-full opacity-10 pointer-events-none">
                             <i class="fa-solid fa-paw absolute top-10 left-10 text-6xl"></i>
                             <i class="fa-solid fa-paw absolute bottom-20 right-10 text-4xl"></i>
                             <i class="fa-solid fa-cat absolute top-1/2 left-1/2 text-8xl transform -translate-x-1/2 -translate-y-1/2"></i>
                        </div>
                        <h2 class="text-3xl md:text-4xl font-bold mb-6 relative z-10">Join the Pack</h2>
                        <p class="text-white-50 mb-8 relative z-10">Sign up to start reporting cases, donating, or adopting. One click can save a life.</p>
                        <div class="relative z-10 hidden md:block">
                            <button id="switch-to-register" onclick="toggleAuth('register')" class="border-2 border-white text-white py-2 px-6 rounded-full font-bold hover:bg-white hover:text-brand transition w-full mb-4">Create Account</button>
                            <button id="switch-to-login" onclick="toggleAuth('login')" class="border-2 border-white text-white py-2 px-6 rounded-full font-bold hover:bg-white hover:text-brand transition w-full opacity-50 hidden">Log In Instead</button>
                        </div>
                    </div>

                    <!-- Right Panel: Forms -->
                    <div class="w-full md:w-3/5 p-8 md:p-12 flex flex-col justify-center bg-white relative">
                        
                        <!-- Tabs for Mobile -->
                        <div class="flex mb-6 md:hidden border-b">
                            <button onclick="toggleAuth('login')" class="flex-1 pb-2 font-bold text-brand border-b-2 border-brand" id="mob-login-tab">Login</button>
                            <button onclick="toggleAuth('register')" class="flex-1 pb-2 font-bold text-gray-400" id="mob-register-tab">Register</button>
                        </div>

                        <!-- Login Form -->
                        <div id="login-form" class="auth-form transition-opacity duration-300">
                            <h3 class="text-3xl font-bold text-gray-800 mb-6">Welcome Back</h3>
                            <form action="auth/login.php" method="POST">
                                <div class="mb-4">
                                    <label class="block text-gray-600 text-sm font-bold mb-2">Phone Number</label>
                                    <div class="flex">
                                        <input id="login-phone" name="phone" type="text" class="w-full px-4 py-3 rounded-r-lg bg-gray-50 border border-gray-300 focus:border-brand focus:ring-2 focus:ring-brand-light outline-none transition" placeholder="Phone Number (Malaysia)">
                                    </div>
                                </div>
                                <div class="mb-6">
                                    <label class="block text-gray-600 text-sm font-bold mb-2">Password</label>
                                    <div class="relative">
                                        <input id="login-password" name="password" type="password" class="w-full px-4 py-3 rounded-lg bg-gray-50 border border-gray-300 focus:border-brand focus:ring-2 focus:ring-brand-light outline-none transition" placeholder="••••••••">
                                        <i id="toggle-login-password" class="fa-solid fa-eye-slash absolute right-3 top-1/2 transform -translate-y-1/2 text-gray-500 hover:text-gray-800 cursor-pointer" onclick="togglePasswordVisibility('login-password')"></i>
                                    </div>
                                    <a href="#" class="text-xs text-brand mt-2 block text-right hover:underline">Forgot Password?</a>
                                </div>
                                <button type="submit" class="w-full bg-brand hover:bg-brand-dark text-white font-bold py-3 px-4 rounded-lg shadow-lg transform transition hover:scale-[1.02]">
                                    Log In
                                </button>
                            </form>
                        </div>

                        <!-- Register Form (Hidden by default on desktop) -->
                        <div id="register-form" class="auth-form absolute inset-0 p-8 md:p-12 flex flex-col justify-center bg-white transition-opacity duration-300 opacity-0 pointer-events-none" style="visibility: hidden;">
                            <h3 class="text-3xl font-bold text-gray-800 mb-6">Create Account</h3>
                            <form action="auth/register.php" method="POST">
                                <div class="mb-4">
                                    <label class="block text-gray-600 text-sm font-bold mb-2">Full Name</label>
                                    <input name="name" type="text" class="w-full px-4 py-3 rounded-lg bg-gray-50 border border-gray-300 focus:border-brand focus:ring-2 focus:ring-brand-light outline-none transition" placeholder="John Doe">
                                </div>
                                <div class="mb-4">
                                    <label class="block text-gray-600 text-sm font-bold mb-2">Phone Number</label>
                                    <div class="flex">
                                        <input id="register-phone" name="phone" type="text" class="w-full px-4 py-3 rounded-r-lg bg-gray-50 border border-gray-300 focus:border-brand focus:ring-2 focus:ring-brand-light outline-none transition" placeholder="Phone Number (Malaysia)">
                                    </div>
                                </div>
                                <div class="mb-6">
                                    <label class="block text-gray-600 text-sm font-bold mb-2">Create Password</label>
                                    <div class="relative">
                                        <input id="register-password" name="password" type="password" class="w-full px-4 py-3 rounded-lg bg-gray-50 border border-gray-300 focus:border-brand focus:ring-2 focus:ring-brand-light outline-none transition" placeholder="••••••••">
                                        <i id="toggle-register-password" class="fa-solid fa-eye-slash absolute right-3 top-1/2 transform -translate-y-1/2 text-gray-500 hover:text-gray-800 cursor-pointer" onclick="togglePasswordVisibility('register-password')"></i>
                                    </div>
                                </div>
                                <button type="submit" class="w-full bg-brand hover:bg-brand-dark text-white font-bold py-3 px-4 rounded-lg shadow-lg transform transition hover:scale-[1.02]">
                                    Sign Up
                                </button>
                            </form>
                            <div class="mt-4 text-center md:hidden">
                                <button onclick="toggleAuth('login')" class="text-brand text-sm font-bold">Already have an account? Login</button>
                            </div>
                        </div>

                    </div>
                </div>
            </div>
        </section>

        <!-- Footer -->
        <footer class="bg-gray-900 text-gray-400 py-6">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 text-center text-sm">
                &copy; 2025 PawRescue. All rights reserved. | Built with compassion and love.
            </div>
        </footer>
    </div>

    <?php if (isset($_SESSION['auth_status']) && isset($_SESSION['auth_message'])): ?>
    <script>
        document.addEventListener("DOMContentLoaded", () => {
            showModal(
                "<?php echo $_SESSION['auth_status'] === 'success' ? 'Success!' : 'Error'; ?>",
                "<?php echo $_SESSION['auth_message']; ?>",
                "<?php echo $_SESSION['auth_status'] === 'success' ? 'fa-solid fa-check-circle text-brand' : 'fa-solid fa-circle-exclamation text-red-500'; ?>"
            );
        });
    </script>
    <?php 
    unset($_SESSION['auth_status']);
    unset($_SESSION['auth_message']);
    endif;
    ?>

</body>
</html>