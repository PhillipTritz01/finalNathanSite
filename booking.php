<!doctype html>
<html lang="en-US">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>Book a Session - Professional Photography & Cinematography</title>
        <script src="https://cdn.tailwindcss.com"></script>
        <link rel="stylesheet" href="style.css">
    </head>
    <body class="bg-gray-50">
        <header class="fixed w-full bg-white/80 backdrop-blur-md shadow-sm z-50">
            <div class="container mx-auto px-4">
                <nav class="py-4">
                    <ul class="flex items-center justify-between space-x-8">
                        <li><a href="index.php" class="text-gray-800 hover:text-blue-600 transition">Home</a></li>
                        <li class="relative group">
                            <span class="text-gray-800 cursor-default flex items-center">
                                Services
                                <svg class="w-4 h-4 ml-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                                </svg>
                            </span>
                            <ul class="absolute left-0 mt-2 w-48 bg-white rounded-md shadow-lg py-2 opacity-0 invisible group-hover:opacity-100 group-hover:visible transition-all duration-200">
                                <li><a href="services/photography.php" class="block px-4 py-2 text-gray-800 hover:bg-blue-50">Photography</a></li>
                                <li><a href="services/cinematography.php" class="block px-4 py-2 text-gray-800 hover:bg-blue-50">Cinematography</a></li>
                                <li><a href="services/videography.php" class="block px-4 py-2 text-gray-800 hover:bg-blue-50">Videography</a></li>
                                <li><a href="services/commercial.php" class="block px-4 py-2 text-gray-800 hover:bg-blue-50">Commercial Projects</a></li>
                            </ul>
                        </li>
                        <li><span class="text-gray-800">Portfolio</span></li>
                        <li><a href="about.php" class="text-gray-800 hover:text-blue-600 transition">About</a></li>
                        <li><a href="contact.php" class="text-gray-800 hover:text-blue-600 transition">Contact</a></li>
                        <li><a href="booking.php" class="bg-blue-600 text-white px-6 py-2 rounded-full hover:bg-blue-700 transition">Book a Session</a></li>
                    </ul>
                </nav>
            </div>
        </header>

        <main class="pt-20">
            <section class="relative py-20 bg-gray-900">
                <div class="absolute inset-0">
                    <img src="https://images.unsplash.com/photo-1492691527719-9d1e07e534b4" alt="Book Your Session" class="w-full h-full object-cover opacity-50">
                </div>
                <div class="relative container mx-auto px-4">
                    <div class="max-w-3xl mx-auto text-center text-white">
                        <h1 class="text-4xl font-bold mb-6">Book Your Session</h1>
                        <p class="text-xl mb-8">Schedule your photography or videography session with us.</p>
                    </div>
                </div>
            </section>

            <section class="py-20 bg-white">
                <div class="container mx-auto px-4">
                    <div class="max-w-3xl mx-auto">
                        <div class="bg-gray-50 rounded-lg p-8 mb-12">
                            <h2 class="text-2xl font-bold mb-4">Booking Process</h2>
                            <ol class="space-y-4">
                                <li class="flex items-start">
                                    <span class="flex-shrink-0 w-8 h-8 bg-blue-600 text-white rounded-full flex items-center justify-center mr-4">1</span>
                                    <div>
                                        <h3 class="font-semibold mb-1">Select Your Service</h3>
                                        <p class="text-gray-600">Choose from our range of photography and videography services.</p>
                                    </div>
                                </li>
                                <li class="flex items-start">
                                    <span class="flex-shrink-0 w-8 h-8 bg-blue-600 text-white rounded-full flex items-center justify-center mr-4">2</span>
                                    <div>
                                        <h3 class="font-semibold mb-1">Choose Date & Time</h3>
                                        <p class="text-gray-600">Pick a convenient date and time for your session.</p>
                                    </div>
                                </li>
                                <li class="flex items-start">
                                    <span class="flex-shrink-0 w-8 h-8 bg-blue-600 text-white rounded-full flex items-center justify-center mr-4">3</span>
                                    <div>
                                        <h3 class="font-semibold mb-1">Fill in Details</h3>
                                        <p class="text-gray-600">Provide your contact information and project requirements.</p>
                                    </div>
                                </li>
                                <li class="flex items-start">
                                    <span class="flex-shrink-0 w-8 h-8 bg-blue-600 text-white rounded-full flex items-center justify-center mr-4">4</span>
                                    <div>
                                        <h3 class="font-semibold mb-1">Confirmation</h3>
                                        <p class="text-gray-600">Receive a confirmation email with all the details.</p>
                                    </div>
                                </li>
                            </ol>
                        </div>

                        <form class="space-y-6">
                            <div>
                                <label for="service" class="block text-sm font-medium text-gray-700 mb-1">Select Service</label>
                                <select id="service" name="service" class="w-full px-4 py-2 border border-gray-300 rounded-md focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                                    <option value="">Choose a service</option>
                                    <option value="photography">Photography</option>
                                    <option value="cinematography">Cinematography</option>
                                    <option value="videography">Videography</option>
                                    <option value="commercial">Commercial Projects</option>
                                </select>
                            </div>
                            <div>
                                <label for="name" class="block text-sm font-medium text-gray-700 mb-1">Full Name</label>
                                <input type="text" id="name" name="name" class="w-full px-4 py-2 border border-gray-300 rounded-md focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                            </div>
                            <div>
                                <label for="email" class="block text-sm font-medium text-gray-700 mb-1">Email</label>
                                <input type="email" id="email" name="email" class="w-full px-4 py-2 border border-gray-300 rounded-md focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                            </div>
                            <div>
                                <label for="date" class="block text-sm font-medium text-gray-700 mb-1">Preferred Date</label>
                                <input type="date" id="date" name="date" class="w-full px-4 py-2 border border-gray-300 rounded-md focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                            </div>
                            <div>
                                <label for="time" class="block text-sm font-medium text-gray-700 mb-1">Preferred Time</label>
                                <input type="time" id="time" name="time" class="w-full px-4 py-2 border border-gray-300 rounded-md focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                            </div>
                            <div>
                                <label for="details" class="block text-sm font-medium text-gray-700 mb-1">Additional Details</label>
                                <textarea id="details" name="details" rows="4" class="w-full px-4 py-2 border border-gray-300 rounded-md focus:ring-2 focus:ring-blue-500 focus:border-blue-500" placeholder="Tell us about your project requirements..."></textarea>
                            </div>
                            <button type="submit" class="w-full bg-blue-600 text-white px-6 py-3 rounded-md hover:bg-blue-700 transition">Book Now</button>
                        </form>
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
                            <li><a href="services/photography.php" class="text-gray-400 hover:text-white transition">Photography</a></li>
                            <li><a href="services/cinematography.php" class="text-gray-400 hover:text-white transition">Cinematography</a></li>
                            <li><a href="services/videography.php" class="text-gray-400 hover:text-white transition">Videography</a></li>
                            <li><a href="services/commercial.php" class="text-gray-400 hover:text-white transition">Commercial</a></li>
                        </ul>
                    </div>
                    <div>
                        <h3 class="text-lg font-semibold mb-4">Resources</h3>
                        <ul class="space-y-2">
                            <li><a href="blog.php" class="text-gray-400 hover:text-white transition">Blog</a></li>
                            <li><a href="faq.php" class="text-gray-400 hover:text-white transition">FAQ</a></li>
                            <li><a href="pricing.php" class="text-gray-400 hover:text-white transition">Pricing</a></li>
                            <li><a href="privacy.php" class="text-gray-400 hover:text-white transition">Privacy Policy</a></li>
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
    </body>
</html> 