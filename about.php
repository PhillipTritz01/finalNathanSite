<?php
$page_title = "About - Professional Photography & Cinematography";
include 'includes/header.php';
?>

<!-- Hero Section -->
<section class="relative h-screen">
    <div class="absolute inset-0">
        <img src="https://images.unsplash.com/photo-1492691527719-9d1e07e534b4" alt="About Hero" class="w-full h-full object-cover">
        <div class="absolute inset-0 bg-black/60"></div>
    </div>
    <div class="relative container mx-auto px-4 h-full flex items-center">
        <div class="max-w-3xl text-white">
            <h1 class="text-6xl font-bold mb-6 leading-tight">About the Artist</h1>
            <p class="text-2xl mb-8 text-gray-200">Capturing life's most precious moments through the lens of creativity and passion.</p>
        </div>
    </div>
</section>

<!-- About Content -->
<section class="py-20 bg-white">
    <div class="container mx-auto px-4">
        <div class="max-w-4xl mx-auto">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-12 items-center mb-20">
                <div>
                    <h2 class="text-4xl font-bold mb-6">The Story</h2>
                    <p class="text-lg text-gray-600 leading-relaxed mb-6">
                        With over a decade of experience in visual storytelling, I specialize in capturing authentic moments that tell compelling stories. My work combines technical expertise with artistic vision, creating imagery that resonates with viewers on an emotional level.
                    </p>
                    <p class="text-lg text-gray-600 leading-relaxed">
                        Whether it's a commercial project, wedding, or personal portrait session, I approach each assignment with the same dedication to excellence and attention to detail. My goal is to create timeless visual narratives that capture the essence of each moment.
                    </p>
                </div>
                <div class="relative aspect-square">
                    <img src="https://images.unsplash.com/photo-1492691527719-9d1e07e534b4" alt="Artist Portrait" class="w-full h-full object-cover rounded-lg shadow-xl">
                </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-8 mb-20">
                <div class="text-center">
                    <div class="text-4xl font-bold text-blue-600 mb-4">10+</div>
                    <h3 class="text-xl font-semibold mb-2">Years Experience</h3>
                    <p class="text-gray-600">Professional photography and cinematography expertise</p>
                </div>
                <div class="text-center">
                    <div class="text-4xl font-bold text-blue-600 mb-4">500+</div>
                    <h3 class="text-xl font-semibold mb-2">Projects Completed</h3>
                    <p class="text-gray-600">Successfully delivered projects for clients worldwide</p>
                </div>
                <div class="text-center">
                    <div class="text-4xl font-bold text-blue-600 mb-4">100%</div>
                    <h3 class="text-xl font-semibold mb-2">Client Satisfaction</h3>
                    <p class="text-gray-600">Dedicated to exceeding client expectations</p>
                </div>
            </div>

            <div class="text-center">
                <h2 class="text-4xl font-bold mb-8">Let's Work Together</h2>
                <p class="text-xl text-gray-600 mb-8 max-w-2xl mx-auto">
                    I'm always excited to take on new projects and collaborate with clients who share my passion for visual storytelling.
                </p>
                <a href="contact.php" class="inline-block bg-blue-600 text-white px-8 py-4 rounded-full text-lg font-semibold hover:bg-blue-700 transition">Get in Touch</a>
            </div>
        </div>
    </div>
</section>

<?php include 'includes/footer.php'; ?> 