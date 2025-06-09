<?php
$page_title = "Contact - Professional Photography & Cinematography";
include 'includes/header.php';
?>

<!-- Hero Section -->
<section class="relative h-screen">
    <div class="absolute inset-0">
        <img src="https://images.unsplash.com/photo-1492691527719-9d1e07e534b4" alt="Contact Hero" class="w-full h-full object-cover">
        <div class="absolute inset-0 bg-black/60"></div>
    </div>
    <div class="relative container mx-auto px-4 h-full flex items-center">
        <div class="max-w-3xl text-white">
            <h1 class="text-6xl font-bold mb-6 leading-tight">Get in Touch</h1>
            <p class="text-2xl mb-8 text-gray-200">Let's discuss your next photography or cinematography project.</p>
        </div>
    </div>
</section>

<!-- Contact Content -->
<section class="py-20 bg-white">
    <div class="container mx-auto px-4">
        <div class="max-w-6xl mx-auto">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-12">
                <!-- Contact Form -->
                <div>
                    <h2 class="text-4xl font-bold mb-8">Send a Message</h2>
                    <form action="process_contact.php" method="POST" class="space-y-6">
                        <div>
                            <label for="name" class="block text-sm font-medium text-gray-700 mb-2">Name</label>
                            <input type="text" id="name" name="name" required
                                class="w-full px-4 py-3 rounded-lg border border-gray-300 focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                        </div>
                        <div>
                            <label for="email" class="block text-sm font-medium text-gray-700 mb-2">Email</label>
                            <input type="email" id="email" name="email" required
                                class="w-full px-4 py-3 rounded-lg border border-gray-300 focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                        </div>
                        <div>
                            <label for="subject" class="block text-sm font-medium text-gray-700 mb-2">Subject</label>
                            <input type="text" id="subject" name="subject" required
                                class="w-full px-4 py-3 rounded-lg border border-gray-300 focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                        </div>
                        <div>
                            <label for="message" class="block text-sm font-medium text-gray-700 mb-2">Message</label>
                            <textarea id="message" name="message" rows="6" required
                                class="w-full px-4 py-3 rounded-lg border border-gray-300 focus:ring-2 focus:ring-blue-500 focus:border-blue-500"></textarea>
                        </div>
                        <button type="submit"
                            class="w-full bg-blue-600 text-white px-8 py-4 rounded-lg text-lg font-semibold hover:bg-blue-700 transition">
                            Send Message
                        </button>
                    </form>
                </div>

                <!-- Contact Information -->
                <div>
                    <h2 class="text-4xl font-bold mb-8">Contact Information</h2>
                    <div class="space-y-8">
                        <div>
                            <h3 class="text-xl font-semibold mb-4">Location</h3>
                            <p class="text-gray-600">123 Photography Street<br>New York, NY 10001<br>United States</p>
                        </div>
                        <div>
                            <h3 class="text-xl font-semibold mb-4">Contact Details</h3>
                            <ul class="space-y-4">
                                <li class="flex items-center text-gray-600">
                                    <i class="fas fa-phone w-6"></i>
                                    <a href="tel:+1234567890" class="hover:text-blue-600 transition">+1 (234) 567-890</a>
                                </li>
                                <li class="flex items-center text-gray-600">
                                    <i class="fas fa-envelope w-6"></i>
                                    <a href="mailto:contact@example.com" class="hover:text-blue-600 transition">contact@example.com</a>
                                </li>
                            </ul>
                        </div>
                        <div>
                            <h3 class="text-xl font-semibold mb-4">Follow Me</h3>
                            <div class="flex space-x-4">
                                <a href="#" class="text-gray-600 hover:text-blue-600 transition text-2xl">
                                    <i class="fab fa-instagram"></i>
                                </a>
                                <a href="#" class="text-gray-600 hover:text-blue-600 transition text-2xl">
                                    <i class="fab fa-facebook"></i>
                                </a>
                                <a href="#" class="text-gray-600 hover:text-blue-600 transition text-2xl">
                                    <i class="fab fa-twitter"></i>
                                </a>
                                <a href="#" class="text-gray-600 hover:text-blue-600 transition text-2xl">
                                    <i class="fab fa-linkedin"></i>
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<?php include 'includes/footer.php'; ?> 