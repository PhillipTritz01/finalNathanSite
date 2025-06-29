<!doctype html>
<html lang="en-US">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>Our Services - Professional Photography & Cinematography</title>
        <script src="https://cdn.tailwindcss.com"></script>
        <link rel="stylesheet" href="style.css">
    </head>
    <body class="bg-gray-50">
        <header class="fixed w-full bg-white/80 backdrop-blur-md shadow-sm z-50">
            <div class="container mx-auto px-4">
                <nav class="py-4">
                    <ul class="flex items-center justify-between space-x-8">
                        <li><a href="/Photography%20Website/index.php" class="text-gray-800 hover:text-blue-600 transition">Home</a></li>
                        <li class="relative group">
                            <span class="text-gray-800 cursor-default flex items-center">
                                Services
                                <svg class="w-4 h-4 ml-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                                </svg>
                            </span>
                            <ul class="absolute left-0 mt-2 w-48 bg-white rounded-md shadow-lg py-2 opacity-0 invisible group-hover:opacity-100 group-hover:visible transition-all duration-200">
                                <li><a href="/Photography%20Website/services/photography.php" class="block px-4 py-2 text-gray-800 hover:bg-blue-50">Photography</a></li>
                                <li><a href="/Photography%20Website/services/cinematography.php" class="block px-4 py-2 text-gray-800 hover:bg-blue-50">Cinematography</a></li>
                                <li><a href="/Photography%20Website/services/videography.php" class="block px-4 py-2 text-gray-800 hover:bg-blue-50">Videography</a></li>
                                <li><a href="/Photography%20Website/services/commercial.php" class="block px-4 py-2 text-gray-800 hover:bg-blue-50">Commercial Projects</a></li>
                            </ul>
                        </li>
                        <li><span class="text-gray-800">Portfolio</span></li>
                        <li><a href="/Photography%20Website/about.php" class="text-gray-800 hover:text-blue-600 transition">About</a></li>
                        <li><a href="/Photography%20Website/contact.php" class="text-gray-800 hover:text-blue-600 transition">Contact</a></li>
                        <li><a href="/Photography%20Website/booking.php" class="bg-blue-600 text-white px-6 py-2 rounded-full hover:bg-blue-700 transition">Book a Session</a></li>
                    </ul>
                </nav>
            </div>
        </header>

        <main class="pt-20">
            <section class="relative py-20 bg-gray-900">
                <div class="absolute inset-0">
                    <img src="https://images.unsplash.com/photo-1492691527719-9d1e07e534b4" alt="Our Services" class="w-full h-full object-cover opacity-50">
                </div>
                <div class="relative container mx-auto px-4">
                    <div class="max-w-3xl mx-auto text-center text-white">
                        <h1 class="text-4xl font-bold mb-6">Our Professional Services</h1>
                        <p class="text-xl mb-8">Comprehensive photography and videography solutions for every need.</p>
                        <div class="flex justify-center space-x-4">
                            <a href="booking.php" class="bg-blue-600 text-white px-8 py-3 rounded-full hover:bg-blue-700 transition">Book a Session</a>
                            <a href="contact.php" class="bg-white text-gray-800 px-8 py-3 rounded-full hover:bg-gray-100 transition">Get in Touch</a>
                        </div>
                    </div>
                </div>
            </section>

            <section class="py-20 bg-white">
                <div class="container mx-auto px-4">
                    <h2 class="text-3xl font-bold text-center mb-12">Our Services</h2>
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-8" id="services-grid">
                        <button class="flex flex-col items-center bg-gray-100 rounded-lg p-8 shadow hover:bg-blue-50 transition group cursor-default" type="button" tabindex="-1">
                            <svg class="w-12 h-12 text-blue-600 mb-4 group-hover:text-blue-800 transition" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 10l4.553-2.276A2 2 0 0020 6.382V5a2 2 0 00-2-2H6a2 2 0 00-2 2v1.382a2 2 0 00.447 1.342L9 10m6 0v4m0 0l-3 3m3-3l3 3m-3-3H9m6 0V10m0 4v4"/></svg>
                            <span class="text-xl font-semibold mb-2">Photography</span>
                            <span class="text-gray-600 text-center">Professional photography for events, portraits, and more.</span>
                        </button>
                        <button class="flex flex-col items-center bg-gray-100 rounded-lg p-8 shadow hover:bg-blue-50 transition group cursor-default" type="button" tabindex="-1">
                            <svg class="w-12 h-12 text-blue-600 mb-4 group-hover:text-blue-800 transition" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.75 17L9 21m5.25-4l.75 4m-7.5-8.5A2.5 2.5 0 0112 7.5a2.5 2.5 0 012.5 2.5m-7.5 0A2.5 2.5 0 0112 7.5a2.5 2.5 0 012.5 2.5m-7.5 0v.5a2.5 2.5 0 002.5 2.5h5a2.5 2.5 0 002.5-2.5v-.5"/></svg>
                            <span class="text-xl font-semibold mb-2">Cinematography</span>
                            <span class="text-gray-600 text-center">Cinematic video production for weddings, events, and businesses.</span>
                        </button>
                        <button class="flex flex-col items-center bg-gray-100 rounded-lg p-8 shadow hover:bg-blue-50 transition group cursor-default" type="button" tabindex="-1">
                            <svg class="w-12 h-12 text-blue-600 mb-4 group-hover:text-blue-800 transition" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 10l4.553-2.276A2 2 0 0020 6.382V5a2 2 0 00-2-2H6a2 2 0 00-2 2v1.382a2 2 0 00.447 1.342L9 10m6 0v4m0 0l-3 3m3-3l3 3m-3-3H9m6 0V10m0 4v4"/></svg>
                            <span class="text-xl font-semibold mb-2">Videography</span>
                            <span class="text-gray-600 text-center">Creative videography for personal, commercial, and promotional needs.</span>
                        </button>
                        <button class="flex flex-col items-center bg-gray-100 rounded-lg p-8 shadow hover:bg-blue-50 transition group cursor-default" type="button" tabindex="-1">
                            <svg class="w-12 h-12 text-blue-600 mb-4 group-hover:text-blue-800 transition" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 7v4a1 1 0 001 1h3v2a1 1 0 001 1h4a1 1 0 001-1v-2h3a1 1 0 001-1V7a1 1 0 00-1-1h-3V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v2H4a1 1 0 00-1 1z"/></svg>
                            <span class="text-xl font-semibold mb-2">Commercial Projects</span>
                            <span class="text-gray-600 text-center">Custom photo and video solutions for brands and businesses.</span>
                        </button>
                    </div>
                </div>
            </section>
        </main>

        <footer class="bg-gray-900 text-white py-12">
            <div class="container mx-auto px-4">
                <div class="grid grid-cols-1 md:grid-cols-4 gap-8">
                    <div>
                        <h3 class="text-lg font-semibold mb-4">Company</h3>
                        <ul class="space-y-2">
                            <li><a href="about.php" class="text-gray-400 hover:text-white transition">About Us</a></li>
                            <li><span class="text-gray-400">Portfolio</span></li>
                            <li><a href="services.php" class="text-gray-400 hover:text-white transition">Services</a></li>
                            <li><a href="contact.php" class="text-gray-400 hover:text-white transition">Contact</a></li>
                        </ul>
                    </div>
                    <div>
                        <h3 class="text-lg font-semibold mb-4">Services</h3>
                        <ul class="space-y-2">
                            <li><a href="/Photography%20Website/services/photography.php" class="text-gray-400 hover:text-white transition">Photography</a></li>
                            <li><a href="/Photography%20Website/services/cinematography.php" class="text-gray-400 hover:text-white transition">Cinematography</a></li>
                            <li><a href="/Photography%20Website/services/videography.php" class="text-gray-400 hover:text-white transition">Videography</a></li>
                            <li><a href="/Photography%20Website/services/commercial.php" class="text-gray-400 hover:text-white transition">Commercial</a></li>
                        </ul>
                    </div>
                    <div>
                        <h3 class="text-lg font-semibold mb-4">Resources</h3>
                        <ul class="space-y-2">
                            <li><a href="blog.html" class="text-gray-400 hover:text-white transition">Blog</a></li>
                            <li><a href="faq.html" class="text-gray-400 hover:text-white transition">FAQ</a></li>
                            <li><a href="pricing.html" class="text-gray-400 hover:text-white transition">Pricing</a></li>
                            <li><a href="privacy.html" class="text-gray-400 hover:text-white transition">Privacy Policy</a></li>
                        </ul>
                    </div>
                    <div>
                        <h3 class="text-lg font-semibold mb-4">Connect</h3>
                        <div class="flex space-x-4">
                            <a href="#" class="text-gray-400 hover:text-white transition">Facebook</a>
                            <a href="#" class="text-gray-400 hover:text-white transition">Instagram</a>
                            <a href="#" class="text-gray-400 hover:text-white transition">Twitter</a>
                            <a href="#" class="text-gray-400 hover:text-white transition">LinkedIn</a>
                        </div>
                    </div>
                </div>
                <div class="border-t border-gray-800 mt-8 pt-8 text-center">
                    <p class="text-gray-400">&copy; 2024 Photography & Cinematography Services. All rights reserved.</p>
                </div>
            </div>
        </footer>

        <script src="script.js"></script>
        <script src="content.js"></script>
    </body>
</html> 