<?php
// Fetch Dynamic Footer Settings
$footer_bg = getThemeSetting('theme_footer_bg', '#1e293b');
$footer_text = getThemeSetting('theme_footer_text', '#f8fafc');
$footer_desc = getThemeSetting('theme_footer_desc', 'Experience crystal clear vision with our advanced eye testing and premium eyewear collections.');
$copyright = getThemeSetting('theme_copyright_text', '&copy; ' . date('Y') . ' Super Optical. All Rights Reserved.');

// Decode Menus
$quick_links = json_decode(getThemeSetting('theme_footer_quick_links', '[]'), true);
if (empty($quick_links)) {
    $quick_links = [
        ['label' => 'Home', 'url' => 'index.php'],
        ['label' => 'Shop', 'url' => 'shop.php'],
        ['label' => 'About Us', 'url' => 'about.php'],
        ['label' => 'Contact', 'url' => 'contact.php']
    ];
}

$service_links = json_decode(getThemeSetting('theme_footer_service_links', '[]'), true);
if (empty($service_links)) {
    $service_links = [
        ['label' => 'Privacy Policy', 'url' => 'privacy.php'],
        ['label' => 'Terms of Service', 'url' => 'terms.php'],
        ['label' => 'Refund Policy', 'url' => 'refund.php'],
        ['label' => 'Shipping Policy', 'url' => 'shipping.php']
    ];
}
?>

<div class="footer-spacer"></div>
<footer class="site-footer" style="background: linear-gradient(135deg, <?= $footer_bg ?> 0%, #0f172a 100%); color: <?= $footer_text ?>;">
    
    <!-- Main Footer Content -->
    <div class="footer-main" style="padding: 3rem 0 2rem 0; position: relative; overflow: hidden;">
        <style>
            @media (min-width: 768px) {
                .footer-main {
                    padding: 0rem 0 3rem 0 !important;
                }
            }
        </style>
        <!-- Decorative Background -->
        <div style="position: absolute; top: -100px; right: -100px; width: 500px; height: 500px; background: radial-gradient(circle, rgba(227, 30, 36, 0.12) 0%, transparent 70%); border-radius: 50%; pointer-events: none; filter: blur(60px);"></div>
        <div style="position: absolute; bottom: -150px; left: -150px; width: 600px; height: 600px; background: radial-gradient(circle, rgba(15, 23, 42, 0.6) 0%, transparent 70%); border-radius: 50%; pointer-events: none; filter: blur(80px);"></div>
        
        <div class="container mx-auto" style="position: relative; z-index: 1; max-width: 1400px; padding: 0 1.5rem;">
            <!-- Responsive 4-Column Grid Layout -->
            <div style="display: grid; grid-template-columns: 1fr; gap: 3rem; align-items: start;">
                <style>
                    @media (min-width: 640px) {
                        .footer-main > div > div {
                            grid-template-columns: repeat(2, 1fr) !important;
                            gap: 2.5rem !important;
                        }
                    }
                    @media (min-width: 1024px) {
                        .footer-main > div > div {
                            grid-template-columns: 1.2fr 1fr 1.3fr 1fr !important;
                            gap: 4rem !important;
                        }
                    }
                </style>
                
                <!-- Column 1: Brand & About -->
                <div class="footer-col">
                    <a href="<?= getBaseURL() ?>index.php" class="inline-block mb-6 group">
                        <?php 
                        $footer_logo = getSetting('site_footer_logo');
                        if ($footer_logo): ?>
                            <img src="<?= getBaseURL() ?>assets/uploads/<?= $footer_logo ?>" alt="Super Optical" class="h-14 w-auto object-contain brightness-0 invert group-hover:opacity-80 transition-opacity">
                        <?php else: ?>
                            <h3 class="text-2xl font-black tracking-tight text-white">
                                <span class="text-primary">Super</span> Optical
                            </h3>
                        <?php endif; ?>
                    </a>
                    
                    <p class="text-gray-300 leading-relaxed text-sm mb-8 pr-4" style="line-height: 1.8;">
                        <?= nl2br(htmlspecialchars($footer_desc)) ?>
                    </p>
                    
                    <div class="footer-social">
                        <h5 class="text-white font-bold text-xs mb-4 uppercase tracking-widest" style="letter-spacing: 0.1em;"><?= __('connect_with_us') ?></h5>
                        <div class="flex gap-3">
                            <?php if ($wa = getSetting('whatsapp_number')): ?>
                                <a href="https://wa.me/<?= $wa ?>" target="_blank" 
                                   class="w-11 h-11 rounded-xl bg-white/5 border border-white/10 flex items-center justify-center hover:bg-[#25D366] hover:border-[#25D366] transition-all duration-300 hover:scale-110">
                                    <i class="fa-brands fa-whatsapp text-xl text-white"></i>
                                </a>
                            <?php endif; ?>
                            <?php if ($fb = getSetting('facebook_link')): ?>
                                <a href="<?= $fb ?>" target="_blank" 
                                   class="w-11 h-11 rounded-xl bg-white/5 border border-white/10 flex items-center justify-center hover:bg-[#1877F2] hover:border-[#1877F2] transition-all duration-300 hover:scale-110">
                                    <i class="fa-brands fa-facebook-f text-xl text-white"></i>
                                </a>
                            <?php endif; ?>
                            <?php if ($ig = getSetting('instagram_link')): ?>
                                <a href="<?= $ig ?>" target="_blank" 
                                   class="w-11 h-11 rounded-xl bg-white/5 border border-white/10 flex items-center justify-center hover:bg-[#E1306C] hover:border-[#E1306C] transition-all duration-300 hover:scale-110">
                                    <i class="fa-brands fa-instagram text-xl text-white"></i>
                                </a>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <!-- Column 2: Quick Links -->
                <div class="footer-col">
                    <h4 class="text-white font-bold text-base mb-6">
                        Quick Links
                    </h4>
                    
                    <ul class="space-y-3" style="list-style: none; padding: 0; margin: 0;">
                        <?php foreach ($quick_links as $link): 
                            if(empty($link['label'])) continue; ?>
                            <li style="list-style: none;" class="listStyle">
                                <a href="<?= $link['url'] ?>" 
                                   class="group flex items-center gap-2.5 text-gray-300 hover:text-white transition-all duration-200 text-sm">
                                    
                                   <i class="fa-solid fa-shield-halved text-primary/50 text-xs"></i>
                                    <span class="group-hover:translate-x-0.5 transition-transform"><?= htmlspecialchars($link['label']) ?></span>
                                </a>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                </div>

                <!-- Column 3: Contact Info -->
                <div class="footer-col">
                    <h4 class="text-white font-bold text-base mb-6">
                        Contact Us
                    </h4>
                    
                    <ul class="space-y-4" style="list-style: none; padding: 0; margin: 0;">
                        <li class="flex items-start gap-3" style="list-style: none;">
                            <div class="w-10 h-10 rounded-lg bg-primary/10 flex items-center justify-center flex-shrink-0 mt-0.5">
                                <i class="fa-solid fa-location-dot text-primary text-base"></i>
                            </div>
                            <div class="flex-1" style="min-width: 0;">
                                <p class="text-xs text-gray-400 mb-1.5 uppercase tracking-wider font-semibold">Address</p>
                                <p class="text-gray-200 text-sm leading-relaxed" style="word-break: break-word; overflow-wrap: break-word;"><?= htmlspecialchars(getSetting('contact_address')) ?></p>
                            </div>
                        </li>
                        <li class="flex items-start gap-3" style="list-style: none;">
                            <div class="w-10 h-10 rounded-lg bg-primary/10 flex items-center justify-center flex-shrink-0 mt-0.5">
                                <i class="fa-solid fa-phone text-primary text-base"></i>
                            </div>
                            <div class="flex-1">
                                <p class="text-xs text-gray-400 mb-1.5 uppercase tracking-wider font-semibold">Phone</p>
                                <a href="tel:<?= htmlspecialchars(getSetting('contact_phone')) ?>" class="text-gray-200 text-sm hover:text-primary transition-colors font-medium"><?= htmlspecialchars(getSetting('contact_phone')) ?></a>
                            </div>
                        </li>
                        <li class="flex items-start gap-3" style="list-style: none;">
                            <div class="w-10 h-10 rounded-lg bg-primary/10 flex items-center justify-center flex-shrink-0 mt-0.5">
                                <i class="fa-solid fa-envelope text-primary text-base"></i>
                            </div>
                            <div class="flex-1">
                                <p class="text-xs text-gray-400 mb-1.5 uppercase tracking-wider font-semibold">Email</p>
                                <a href="mailto:<?= htmlspecialchars(getSetting('contact_email')) ?>" class="text-gray-200 text-sm hover:text-primary transition-colors break-all font-medium"><?= htmlspecialchars(getSetting('contact_email')) ?></a>
                            </div>
                        </li>
                    </ul>
                </div>

                <!-- Column 4: Payment & Policies -->
                <div class="footer-col">
                    <!-- Payment & Policies -->
                    <div class="space-y-6">
                        <!-- Policies First -->
                        <div>
                            <h5 class="text-white font-bold text-base mb-4">
                                Policies
                            </h5>
                            <ul class="space-y-2.5" style="list-style: none; padding: 0; margin: 0;">
                                <?php foreach ($service_links as $link): 
                                    if(empty($link['label'])) continue; ?>
                                    <li style="list-style: none;">
                                        <a href="<?= $link['url'] ?>" 
                                           class="text-gray-300 hover:text-primary text-sm transition-all duration-200 inline-flex items-center gap-2">
                                            <i class="fa-solid fa-shield-halved text-primary/50 text-xs"></i>
                                            <?= htmlspecialchars($link['label']) ?>
                                        </a>
                                    </li>
                                <?php endforeach; ?>
                            </ul>
                        </div>
                        
                        <!-- Payment Icons Second -->
                        <div>
                            <h5 class="text-white font-bold text-base mb-2 mt-5">
                                We Accept
                            </h5>
                            <div class="flex gap-4 flex-wrap items-center">
                                <i class="fa-brands fa-cc-visa text-3xl text-white/70 hover:text-white hover:scale-110 transition-all duration-200"></i>
                                <i class="fa-brands fa-cc-mastercard text-3xl text-white/70 hover:text-white hover:scale-110 transition-all duration-200"></i>
                                <i class="fa-brands fa-cc-paypal text-3xl text-white/70 hover:text-white hover:scale-110 transition-all duration-200"></i>
                                <i class="fa-brands fa-google-pay text-3xl text-white/70 hover:text-white hover:scale-110 transition-all duration-200"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Footer Bottom -->
    <div class="footer-bottom" style="background: rgba(0, 0, 0, 0.3); backdrop-filter: blur(10px); border-top: 1px solid rgba(255, 255, 255, 0.05); padding: 1.75rem 0;">
        <div class="container mx-auto px-4 md:px-6 lg:px-8">
            <div class="flex flex-col md:flex-row justify-between items-center gap-4">
                <p class="text-gray-400 text-sm text-center md:text-left">
                    <?= $copyright ?>
                </p>
                <p class="text-gray-500 text-xs flex items-center gap-2">
                    Designed with <i class="fa-solid fa-heart text-primary animate-pulse"></i> by Super Optical Team
                </p>
            </div>
        </div>
    </div>
</footer>

<!-- Scripts -->
<script type="application/ld+json">
{
  "@context": "https://schema.org",
  "@type": "OpticalStore",
  "name": "Super Optical",
  "image": "<?= getBaseURL() ?>assets/images/logo.png",
  "address": {
    "@type": "PostalAddress",
    "streetAddress": "<?= htmlspecialchars(getSetting('contact_address')) ?>"
  },
  "telephone": "<?= htmlspecialchars(getSetting('contact_phone')) ?>",
  "priceRange": "₹₹"
}
</script>

<!-- AOS & Lazy Load (Preserved) -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/aos/2.3.4/aos.js"></script>
<script>
  AOS.init({duration: 600, easing: 'ease-in-out', once: true, offset: 50});
  // Lazy Load Fallback
  document.addEventListener("DOMContentLoaded", function() {
    var lazyImages = [].slice.call(document.querySelectorAll("img.lazy"));
    if ("IntersectionObserver" in window) {
      let lazyImageObserver = new IntersectionObserver(function(entries, observer) {
        entries.forEach(function(entry) {
          if (entry.isIntersecting) {
            let lazyImage = entry.target;
            lazyImage.src = lazyImage.dataset.src;
            lazyImage.classList.remove("lazy");
            lazyImageObserver.unobserve(lazyImage);
          }
        });
      });
      lazyImages.forEach(function(lazyImage) {
        lazyImageObserver.observe(lazyImage);
      });
    }
  });
</script>

<script>
  // Global Add to Cart Function
  function addToCart(productId) {
      if (!productId) return;
      window.location.href = 'cart.php?add=' + productId;
  }
</script>

<?= getSetting('footer_code') ?>
<?php 
if (!empty($custom_scripts)) {
    foreach ($custom_scripts as $script) {
        if ($script['placement'] === 'body') echo $script['code'] . "\n";
    }
}
?>
<script><?= getThemeSetting('theme_custom_js', '') ?></script>
</body>
</html>
<?php ob_end_flush(); ?>
