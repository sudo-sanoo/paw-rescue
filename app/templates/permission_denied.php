<?php
session_start();

// If someone goes directly to permission_denied.php without coming from a protected page:
if (!isset($_SESSION['user_id'])) {
    header("Location: ../index.php#auth");
    exit;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Permission Denied</title>
    <link rel="icon" href="../favicon.svg">
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Fredoka:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        body {
            user-select: none; 
            font-family: 'Fredoka', sans-serif;
        }
        .paw-print {
            animation: float 3s ease-in-out infinite;
        }
        @keyframes float {
            0% { transform: translateY(0px) rotate(0deg); }
            50% { transform: translateY(-10px) rotate(5deg); }
            100% { transform: translateY(0px) rotate(0deg); }
        }
        .ear-wiggle {
            animation: wiggle 2s ease-in-out infinite;
            transform-origin: bottom center;
        }
        @keyframes wiggle {
            0%, 100% { transform: rotate(0deg); }
            25% { transform: rotate(-5deg); }
            75% { transform: rotate(5deg); }
        }
    </style>
</head>
<body class="bg-orange-50 min-h-screen flex items-center justify-center p-4">

    <!-- Changed max-w-md to max-w-4xl and added flex-row layout for wider screens -->
    <div class="max-w-4xl w-full bg-white rounded-3xl shadow-2xl overflow-hidden border-4 border-white flex flex-col md:flex-row">
        
        <!-- Illustration Section - Now on the left (5/12 width) -->
        <div class="bg-orange-100 h-64 md:h-auto md:w-5/12 flex items-center justify-center relative overflow-hidden flex-shrink-0">
            
            <!-- Decorative Background Paws -->
            <svg class="absolute top-4 left-4 w-12 h-12 text-orange-200 opacity-50 transform -rotate-12" fill="currentColor" viewBox="0 0 24 24"><path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm0 18c-4.41 0-8-3.59-8-8s3.59-8 8-8 8 3.59 8 8-3.59 8-8 8z"/></svg>
            <svg class="absolute bottom-4 right-4 w-16 h-16 text-orange-200 opacity-50 transform rotate-12" fill="currentColor" viewBox="0 0 24 24"><path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm0 18c-4.41 0-8-3.59-8-8s3.59-8 8-8 8 3.59 8 8-3.59 8-8 8z"/></svg>

            <!-- Main Icon Composition -->
            <div class="relative z-10 paw-print scale-90 md:scale-110">
                <!-- Shield Background -->
                <svg class="w-40 h-40 text-orange-500 drop-shadow-lg" fill="currentColor" viewBox="0 0 24 24">
                    <path d="M12 1L3 5v6c0 5.55 3.84 10.74 9 12 5.16-1.26 9-6.45 9-12V5l-9-4z"/>
                </svg>
                
                <!-- White Paw Cutout -->
                <svg class="w-20 h-20 text-white absolute top-1/2 left-1/2 transform -translate-x-1/2 -translate-y-1/2" fill="currentColor" viewBox="0 0 512 512">
                    <path d="M256,224c-17.7,0-32,14.3-32,32s14.3,32,32,32c17.7,0,32-14.3,32-32S273.7,224,256,224z M428.3,195.5 c-16.7-18.5-44-23.7-66.8-12.6c-7,3.4-13.4,8-19,13.8c-10.4-16.3-26.4-28.5-45.3-34.1c-10.2-3-20.9-3.7-31.5-2 c-25.1-40.3-68.5-66.2-116.5-66.2c-29.8,0-58.1,9.9-79.6,27.8C42.4,145.1,32,174.5,32,208c0,5.3,0.3,10.6,0.8,15.8 C14.6,243,3.3,266,1.2,289.8c-0.1,1.5-0.2,3-0.2,4.5c0,43.2,30.5,79.6,71.2,88.4c3.6,0.8,7.3,1.2,10.9,1.2 c16.5,0,32.3-5.2,45.4-15c12.4,18,33.1,29.8,56.5,29.8c5.4,0,10.8-0.6,15.9-1.9c13.2,16.7,33.7,27.4,56.7,27.4 c7,0,13.8-1,20.3-2.9c12.1,10.7,27.9,17.1,44.9,17.1c3.5,0,7-0.3,10.5-0.9c37.7-6.5,65.6-39.7,65.6-78c0-1.8-0.1-3.6-0.2-5.4 C457.7,301.9,461,231.8,428.3,195.5z M176,336c-26.5,0-48-21.5-48-48s21.5-48,48-48s48,21.5,48,48S202.5,336,176,336z M256,368 c-26.5,0-48-21.5-48-48s21.5-48,48-48s48,21.5,48,48S282.5,368,256,368z M336,336c-26.5,0-48-21.5-48-48s21.5-48,48-48 s48,21.5,48,48S362.5,336,336,336z"/>
                </svg>

                <!-- Lock Icon Overlay -->
                <div class="absolute -bottom-2 -right-2 bg-white rounded-full p-2 shadow-md">
                     <svg class="w-6 h-6 text-orange-600" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5">
                        <rect x="3" y="11" width="18" height="11" rx="2" ry="2"></rect>
                        <path d="M7 11V7a5 5 0 0 1 10 0v4"></path>
                    </svg>
                </div>
            </div>
        </div>

        <!-- Right Side Container (Content + Footer) -->
        <div class="flex flex-col md:w-7/12">
            <!-- Content Section -->
            <div class="p-8 md:p-10 text-center flex flex-col justify-center flex-grow">
                <h1 class="text-3xl font-bold text-gray-800 mb-2">Paw-mission Denied!</h1>
                <div class="h-1 w-20 bg-orange-400 mx-auto rounded-full mb-6"></div>
                
                <p class="text-gray-600 text-lg mb-6 leading-relaxed">
                    Uh oh! It looks like you've wandered into a restricted kennel. This area is for authorized personnel only.
                </p>

                <div class="bg-orange-50 rounded-xl p-4 mb-8 border border-orange-100">
                    <p class="text-sm text-orange-800 font-medium">Error Code: 403 Forbidden</p>
                    <p class="text-xs text-orange-600 mt-1">Please check your credentials or contact the shelter admin. (+60128256211)</p>
                </div>

                <!-- Action Buttons -->
                <div class="flex flex-col sm:flex-row gap-3 justify-center">
                    <button onclick="window.history.back()" class="w-full sm:w-auto flex-1 bg-orange-500 hover:bg-orange-600 text-white font-bold py-3 px-6 rounded-xl transition duration-300 transform hover:-translate-y-0.5 active:translate-y-0 shadow-lg hover:shadow-orange-200 flex items-center justify-center group">
                        <svg class="w-5 h-5 mr-2 transform transition-transform group-hover:-translate-x-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path>
                        </svg>
                        Go Back
                    </button>
                </div>
            </div>

            <!-- Footer Strip -->
            <div class="bg-gray-50 p-4 border-t border-gray-100 text-center">
                <p class="text-xs text-gray-400">© 2025 Paw Rescue App</p>
            </div>
        </div>
    </div>

</body>
</html>