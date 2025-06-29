    </main>
    <!-- Footer -->
    <footer class="bg-gray-900 text-white py-12">
        <div class="container mx-auto px-4">
            <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
                <div>
                    <h3 class="text-lg font-semibold mb-4">Contact</h3>
                    <ul class="space-y-2">
                        <li><a href="mailto:contact@example.com" class="text-gray-400 hover:text-white transition">contact@example.com</a></li>
                        <li><a href="tel:+1234567890" class="text-gray-400 hover:text-white transition">+1 (234) 567-890</a></li>
                    </ul>
                </div>
                <div>
                    <h3 class="text-lg font-semibold mb-4">Quick Links</h3>
                    <ul class="space-y-2">
                        <li><span class="text-gray-400">Portfolio</span></li>
                        <li><a href="about.php" class="text-gray-400 hover:text-white transition">About</a></li>
                        <li><a href="contact.php" class="text-gray-400 hover:text-white transition">Contact</a></li>
                    </ul>
                </div>
                <div>
                    <h3 class="text-lg font-semibold mb-4">Follow Us</h3>
                    <div class="flex space-x-4">
                        <a href="#" class="text-gray-400 hover:text-white transition text-2xl"><i class="fab fa-instagram"></i></a>
                        <a href="#" class="text-gray-400 hover:text-white transition text-2xl"><i class="fab fa-facebook"></i></a>
                        <a href="#" class="text-gray-400 hover:text-white transition text-2xl"><i class="fab fa-twitter"></i></a>
                        <a href="#" class="text-gray-400 hover:text-white transition text-2xl"><i class="fab fa-linkedin"></i></a>
                    </div>
                </div>
            </div>
            <div class="border-t border-gray-800 mt-8 pt-8 text-center">
                <p class="text-gray-400">&copy; <?php echo date('Y'); ?> Photography & Cinematography Services. All rights reserved.</p>
            </div>
        </div>
    </footer>

    <!-- Scroll to Top Button -->
    <button id="scrollToTop" class="fixed bottom-8 right-8 bg-gray-800 text-white p-3 rounded-full shadow-lg opacity-0 transition-opacity duration-300 hover:bg-gray-700 focus:outline-none focus:ring-2 focus:ring-gray-500 focus:ring-offset-2">
        <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 10l7-7m0 0l7 7m-7-7v18" />
        </svg>
    </button>

    <script>
        const scrollToTopButton = document.getElementById('scrollToTop');

        // Show/hide button based on scroll position
        const toggleScrollButton = () => {
            if (window.scrollY > 300) {
                scrollToTopButton.classList.remove('opacity-0');
                scrollToTopButton.classList.add('opacity-100');
            } else {
                scrollToTopButton.classList.remove('opacity-100');
                scrollToTopButton.classList.add('opacity-0');
            }
        };

        // Scroll to top when button is clicked
        const scrollToTop = () => {
            window.scrollTo({
                top: 0,
                behavior: 'smooth'
            });
        };

        // Add event listeners
        window.addEventListener('scroll', toggleScrollButton);
        scrollToTopButton.addEventListener('click', scrollToTop);
    </script>
</body>
</html> 