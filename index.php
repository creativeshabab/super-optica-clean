<?php require_once 'includes/header.php'; ?>
<?php require_once 'config/db.php'; ?>
<?php require_once 'includes/trust_sections.php'; ?>

<?php
// Fetch Featured Products (Latest 4)
// Fetch Featured Products (Limit matches Column setting)
$prod_limit = getThemeSetting('theme_products_per_row', '4');
$featured = $pdo->query("SELECT p.*, c.slug as category_slug FROM products p LEFT JOIN categories c ON p.category_id = c.id ORDER BY p.created_at DESC LIMIT $prod_limit")->fetchAll();

// Fetch Latest Blog (Limit matches Column setting)
$blog_limit = getThemeSetting('theme_blog_per_row', '3');
$posts = $pdo->query("SELECT * FROM posts ORDER BY created_at DESC LIMIT $blog_limit")->fetchAll();

// Fetch Sliders
$hero_sliders = $pdo->query("SELECT * FROM sliders ORDER BY created_at DESC")->fetchAll();
// Fallback if no sliders
if (empty($hero_sliders)) {
    $hero_sliders = [[
        'title' => __('hero_title_default'),
        'subtitle' => __('hero_subtitle_default'),
        'image' => '', // Use default gradient
        'link' => 'shop',
        'link_text' => __('shop_now')
    ]];
}
?>

<!-- Hero Slider -->
<?php if (getThemeSetting('theme_show_hero', '1') == '1'): ?>
<section id="hero-slider">
    <?php foreach ($hero_sliders as $index => $slide): 
        $bg_style = $slide['image'] 
            ? "background: url('assets/uploads/{$slide['image']}') center/cover no-repeat;" 
            : "background: linear-gradient(135deg, #e31e24 0%, #991b1b 100%);";
    ?>
    <div class="hero-slide <?= $index === 0 ? 'active' : '' ?>">
        <?php if ($slide['image']): ?>
            <img src="assets/uploads/<?= $slide['image'] ?>" alt="<?= htmlspecialchars($slide['title']) ?>" class="absolute inset-0 w-full h-full object-cover z-0">
        <?php else: ?>
            <div class="absolute inset-0 w-full h-full gradient-primary z-0"></div>
        <?php endif; ?>
        
        <div class="hero-overlay z-1"></div>
        
        <div class="container hero-container relative z-2">
            <span class="hero-badge">
                <?= htmlspecialchars($slide['badge_text'] ?? __('visit_us')) ?>
            </span>
            <h1 class="hero-title">
                <?= mb_strimwidth($slide['title'], 0, 40, "...") ?>
            </h1>
            <p class="hero-subtitle">
                <?= mb_strimwidth(htmlspecialchars($slide['subtitle']), 0, 100, "...") ?>
            </p>
            <div class="hero-actions">
                <?php if ($slide['link']): ?>
                    <a href="<?= htmlspecialchars($slide['link']) ?>" class="btn btn-hero-primary"><?= htmlspecialchars($slide['link_text']) ?></a>
                <?php endif; ?>
                <?php 
                    $sec_link = $slide['secondary_link'] ?? 'shop.php';
                    $sec_text = $slide['secondary_link_text'] ?? __('explore_collection');
                ?>
                <a href="<?= htmlspecialchars($sec_link) ?>" class="btn btn-hero-secondary"><?= htmlspecialchars($sec_text) ?></a>
            </div>
        </div>
    </div>
    <?php endforeach; ?>

    <!-- Slider Controls -->
    <?php if(count($hero_sliders) > 1): ?>
        <button onclick="changeSlide(-1)" class="slider-control prev"><i class="fa-solid fa-chevron-left"></i></button>
        <button onclick="changeSlide(1)" class="slider-control next"><i class="fa-solid fa-chevron-right"></i></button>
        
        <!-- Dots Container -->
        <div class="slider-dots" id="sliderDots">
            <?php foreach($hero_sliders as $i => $s): ?>
                <span class="dot <?= $i === 0 ? 'active' : '' ?>" onclick="goToSlide(<?= $i ?>)"></span>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</section>
<?php endif; ?>

<script>
document.addEventListener('DOMContentLoaded', function() {
    let currentSlide = 0;
    const slides = document.querySelectorAll('.hero-slide');
    const dots = document.querySelectorAll('.dot');
    const totalSlides = slides.length;
    let slideInterval;
    const intervalTime = 5000;

    // Initialize
    startSlideShow();

    // Functions
    window.showSlide = function(n) {
        // Wrap around index
        if (n >= totalSlides) currentSlide = 0;
        else if (n < 0) currentSlide = totalSlides - 1;
        else currentSlide = n;

        // Update Slides
        slides.forEach(slide => slide.classList.remove('active'));
        slides[currentSlide].classList.add('active');

        // Update Dots
        dots.forEach(dot => dot.classList.remove('active'));
        if(dots[currentSlide]) dots[currentSlide].classList.add('active');
    };

    window.changeSlide = function(n) {
        showSlide(currentSlide + n);
        resetTimer();
    };

    window.goToSlide = function(n) {
        showSlide(n);
        resetTimer();
    };

    function startSlideShow() {
        slideInterval = setInterval(() => {
            showSlide(currentSlide + 1);
        }, intervalTime);
    }

    function stopSlideShow() {
        clearInterval(slideInterval);
    }

    function resetTimer() {
        stopSlideShow();
        startSlideShow();
    }

    // Touch Swipe Support
    let touchStartX = 0;
    let touchEndX = 0;
    const slider = document.getElementById('hero-slider');

    if(slider) {
        slider.addEventListener('touchstart', e => {
            touchStartX = e.changedTouches[0].screenX;
        }, {passive: true});

        slider.addEventListener('touchend', e => {
            touchEndX = e.changedTouches[0].screenX;
            handleSwipe();
        }, {passive: true});
        
        // Pause on hover
        slider.addEventListener('mouseenter', stopSlideShow);
        slider.addEventListener('mouseleave', startSlideShow);
    }

    function handleSwipe() {
        const threshold = 50;
        if (touchEndX < touchStartX - threshold) {
            changeSlide(1); // Swipe Left -> Next
        }
        if (touchEndX > touchStartX + threshold) {
            changeSlide(-1); // Swipe Right -> Prev
        }
    }
});
</script>

<!-- Eye Test Promo Section -->
<?php if (getThemeSetting('theme_show_eyetest', '1') == '1'): ?>
<section class="section-padding bg-white mb-8 border-b border-gray-100">
    <div class="container mx-auto px-4 text-center">
        <h2 class="text-3xl font-bold text-accent mb-4">Need an Eye Checkup?</h2>
        <p class="text-gray-500 mb-8 max-w-2xl mx-auto leading-relaxed">Book your appointment online and skip the queue. Professional eye testing by certified opticians using state-of-the-art equipment.</p>
        <a href="book_appointment.php" class="btn btn-primary rounded-full shadow-lg hover:shadow-xl transition-shadow inline-flex items-center gap-2">
            <i class="fa-solid fa-calendar-check"></i> Book Appointment Now
        </a>
    </div>
</section>
<?php endif; ?>

<?php if (getThemeSetting('theme_show_featured', '1') == '1'): ?>
<section class="bg-gray-50 section-padding">
<div class="container mx-auto px-4">
   
    <div class="flex justify-between items-end mb-8 md:mb-12">
        <div>
            <span class="text-primary font-bold uppercase tracking-widest text-xs md:text-sm">Best Sellers</span>
            <h2 class="text-2xl md:text-4xl font-black text-gray-800 mt-2">Featured <span class="text-primary">Products</span></h2>
        </div>
        <!-- Mobile See More -->
        <a href="shop.php" class="text-primary font-bold text-sm hover:underline md:hidden">See More &rarr;</a>
        <!-- Desktop View All -->
        <a href="shop.php" class="text-primary font-bold hover:underline hidden md:inline-block">View All Products &rarr;</a>
    </div>

    <!-- Added mobile-snap-slider class -->
    <div class="dynamic-product-grid-v2 mobile-snap-slider">
        <?php foreach ($featured as $p): ?>
            <div class="slider-item-wrapper">
                <?php renderProductCard($p); ?>
            </div>
        <?php endforeach; ?>
    </div>

    </div>
    </section>
<?php endif; ?>
    
<!-- Services Section -->
<?php 
$services = $pdo->query("SELECT * FROM services ORDER BY created_at DESC")->fetchAll();
if (getThemeSetting('theme_show_services', '1') == '1' && count($services) > 0): 
?>

<section class="section-padding bg-white">
<div class="container mx-auto px-4">
    <div class="flex justify-between items-end mb-12">
        <div>
            <span class="text-primary font-bold uppercase tracking-widest text-sm">Top Quality</span>
            <h2 class="text-4xl font-black text-gray-800 mt-2">Our <span class="text-primary">Services</span></h2>
        </div>
        <a href="services.php" class="text-primary font-bold hover:underline hidden md:inline-block">View All Services &rarr;</a>
    </div>
    
    <div class="dynamic-service-grid-v2">
        <?php foreach ($services as $s): ?>
        <div class="service-card-dynamic p-8 text-center group">
            <div class="w-16 h-16 mx-auto mb-6 bg-primary/10 rounded-full flex items-center justify-center text-primary text-2xl group-hover:bg-primary group-hover:text-white transition-colors">
                <?php if ($s['icon'] && strpos($s['icon'], 'fa-') !== false): ?>
                    <i class="<?= $s['icon'] ?>"></i>
                <?php elseif ($s['icon']): ?>
                    <img src="assets/uploads/<?= $s['icon'] ?>" class="w-8 h-8 object-contain">
                <?php else: ?>
                    <i class="fa-solid fa-star"></i>
                <?php endif; ?>
            </div>
            <h3 class="text-xl font-bold text-gray-800 mb-3"><?= htmlspecialchars($s['title']) ?></h3>
            <p class="text-gray-500 leading-relaxed"><?= htmlspecialchars($s['description']) ?></p>
        </div>
        <?php endforeach; ?>
    </div>
</div>
</section>


<?php endif; ?>

<!-- Featured Products Grid -->


<!-- Dynamic Trust Section -->
<?php if (getThemeSetting('theme_show_trust', '1') == '1') renderTrustSection(); ?>

<!-- Customer Reviews Section -->
<?php 
// Limit reviews to match the desktop column count so it looks even
$reviews_limit = getThemeSetting('theme_reviews_per_row', '3');
$reviews = $pdo->query("SELECT * FROM reviews ORDER BY created_at DESC LIMIT $reviews_limit")->fetchAll();
if (getThemeSetting('theme_show_reviews', '1') == '1' && count($reviews) > 0): 
?>
<section class="section-padding bg-white">
    <div class="container mx-auto px-4">
        <div class="flex justify-between items-end mb-12">
            <div>
                <span class="text-primary font-bold uppercase tracking-widest text-sm"><?= __('testimonials') ?></span>
                <h2 class="text-4xl font-black text-gray-800 mt-2">Customers <span class="text-primary">Say</span></h2>
            </div>
            <a href="reviews.php" class="text-primary font-bold hover:underline hidden md:inline-block">View All Reviews &rarr;</a>
        </div>
        
        <div class="dynamic-review-grid-v2">
            <?php foreach ($reviews as $r): ?>
            <div class="review-card-premium h-full group p-2">
                <div class="card-inner h-full flex flex-col p-8 relative">
                    <i class="fa-solid fa-quote-right text-gray-100/50 text-6xl absolute top-6 right-6 -z-0 transition-transform duration-500 group-hover:scale-110 group-hover:rotate-12 group-hover:text-primary/10"></i>
                    
                    <div class="flex gap-1 mb-6 text-yellow-400">
                        <?php for($i=0; $i<$r['rating']; $i++) echo '<i class="fa-solid fa-star text-sm"></i>'; ?>
                    </div>
                    
                    <p class="text-gray-600 mb-8 italic leading-relaxed relative z-10 flex-1">
                        "<?= htmlspecialchars($r['comment']) ?>"
                    </p>
                    
                    <div class="flex items-center gap-4 pt-6 border-t border-gray-100 relative z-10">
                        <?php if ($r['image']): ?>
                            <img src="assets/uploads/<?= $r['image'] ?>" class="w-12 h-12 rounded-full object-cover border-2 border-white shadow-md">
                        <?php else: ?>
                            <div class="w-12 h-12 rounded-full bg-primary/10 flex items-center justify-center font-bold text-primary text-lg border-2 border-white shadow-md">
                                <?= strtoupper(substr($r['name'], 0, 1)) ?>
                            </div>
                        <?php endif; ?>
                        <div>
                            <h4 class="font-black text-gray-900 text-base"><?= htmlspecialchars($r['name']) ?></h4>
                            <span class="text-xs font-bold text-primary uppercase tracking-wider">Verified Customer</span>
                        </div>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>
<?php endif; ?>

<!-- Dynamic Visit Section -->

<!-- Blog Section -->
<?php if (getThemeSetting('theme_show_blog', '1') == '1'): ?>
<section class="section-padding bg-gray-50">
    <div class="container mx-auto px-4">
        <div class="flex justify-between items-end mb-12">
            <div>
                <span class="text-primary font-bold uppercase tracking-widest text-sm"><?= __('our_blog') ?></span>
                <h2 class="text-4xl font-black text-gray-800 mt-2">Latest <span class="text-primary">News</span></h2>
            </div>
            <a href="blog.php" class="text-primary font-bold hover:underline hidden md:inline-block">View All News &rarr;</a>
        </div>
        
        <div class="dynamic-blog-grid-final">
            <?php foreach ($posts as $post): ?>
            <div class="blog-card-premium h-full group">
                <div class="card-inner h-full flex flex-col bg-surface rounded-2xl overflow-hidden shadow-sm transition-all duration-300 hover:shadow-xl">
                    <?php if($post['image']): ?>
                        <div class="aspect-video overflow-hidden relative">
                            <img src="assets/uploads/<?= $post['image'] ?>" class="w-full h-full object-cover transition-transform duration-500 group-hover:scale-105">
                            <div class="absolute inset-0 bg-black/0 group-hover:bg-black/10 transition-colors duration-300"></div>
                        </div>
                    <?php endif; ?>
                    <div class="p-6 flex flex-col flex-1">
                        <div class="flex items-center gap-2 mb-3">
                            <span class="px-2 py-1 bg-gray-100 text-xs font-bold uppercase tracking-wider text-gray-500 rounded-md">
                                <?= date('M d', strtotime($post['created_at'])) ?>
                            </span>
                        </div>
                        <h3 class="text-xl font-black text-gray-800 mb-3 line-clamp-2 leading-tight group-hover:text-primary transition-colors">
                            <a href="blog.php?id=<?= $post['id'] ?>"><?= htmlspecialchars($post['title']) ?></a>
                        </h3>
                        <div class="mt-auto pt-4 border-t border-gray-100">
                            <a href="blog.php?id=<?= $post['id'] ?>" class="text-primary font-bold text-sm hover:underline inline-flex items-center gap-1">
                                <?= __('read_more') ?> <i class="fa-solid fa-arrow-right text-xs transition-transform group-hover:translate-x-1"></i>
                            </a>
                        </div>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        

    </div>
</section>
<?php endif; ?>

<?php require_once 'includes/footer.php'; ?>
