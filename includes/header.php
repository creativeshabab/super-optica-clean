<?php
ob_start();
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/functions.php';

// Get cart count
$cart_count = getCartCount();
    // SEO & Site Defaults
    $site_title = getSetting('site_title', 'Super Optical | Begusarai\'s Premier Eyewear');
    $meta_desc = getSetting('meta_description', 'Experience crystal clear vision with our advanced eye testing and premium eyewear collections.');
    $meta_keys = getSetting('meta_keywords', 'optician, begusarai, glasses');
    $favicon = getSetting('favicon');
    $og_img_setting = getSetting('og_image');
    $analytics_id = getSetting('analytics_id');
    $gtm_id = getSetting('google_tag_manager_id');
    $site_logo = getSetting('site_logo');
    $header_code = getSetting('header_code');
    $maintenance_mode = getSetting('maintenance_mode', 'off');
    $custom_scripts = json_decode(getSetting('custom_scripts', '[]'), true);

    // Maintenance Mode Check
    if ($maintenance_mode === 'on' && !isAdmin()) {
        require_once 'maintenance_screen.php';
        exit;
    }

    // Page Overrides
    $page_title = isset($page_title) ? $page_title . " | Super Optical" : $site_title;
    $page_desc = isset($page_desc) ? $page_desc : $meta_desc;
    $page_keys = isset($page_keys) ? $page_keys : $meta_keys;
    $page_og_image = isset($page_og_image) ? $page_og_image : ($og_img_setting ? getBaseURL() . 'assets/uploads/' . $og_img_setting : getBaseURL() . 'assets/images/og-image.jpg');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    
    <!-- SEO Meta Tags -->
    <title><?= htmlspecialchars($page_title) ?></title>
    <meta name="description" content="<?= htmlspecialchars($page_desc) ?>">
    <meta name="keywords" content="<?= htmlspecialchars($page_keys) ?>">
    <meta name="author" content="Super Optical">
    <link rel="canonical" href="<?= getBaseURL() . basename($_SERVER['PHP_SELF']) ?><?= $_SERVER['QUERY_STRING'] ? '?' . $_SERVER['QUERY_STRING'] : '' ?>">
    
    <!-- Google Tag Manager -->
    <?php if ($gtm_id): ?>
    <script>(function(w,d,s,l,i){w[l]=w[l]||[];w[l].push({'gtm.start':
    new Date().getTime(),event:'gtm.js'});var f=d.getElementsByTagName(s)[0],
    j=d.createElement(s),dl=l!='dataLayer'?'&l='+l:'';j.async=true;j.src=
    'https://www.googletagmanager.com/gtm.js?id='+i+dl;f.parentNode.insertBefore(j,f);
    })(window,document,'script','dataLayer','<?= $gtm_id ?>');</script>
    <?php endif; ?>
    <!-- End Google Tag Manager -->

    <!-- Favicon -->
    <?php if ($favicon): ?>
        <link rel="icon" type="image/x-icon" href="<?= getBaseURL() ?>assets/uploads/<?= $favicon ?>">
    <?php else: ?>
        <link rel="icon" type="image/x-icon" href="<?= getBaseURL() ?>favicon.ico">
    <?php endif; ?>
    
    <!-- Open Graph / Facebook -->
    <meta property="og:type" content="website">
    <meta property="og:title" content="<?= htmlspecialchars($page_title) ?>">
    <meta property="og:description" content="<?= htmlspecialchars($page_desc) ?>">
    <meta property="og:image" content="<?= $page_og_image ?>">

    <!-- Twitter -->
    <meta property="twitter:card" content="summary_large_image">
    <meta property="twitter:title" content="<?= htmlspecialchars($page_title) ?>">
    <meta property="twitter:description" content="<?= htmlspecialchars($page_desc) ?>">
    <meta property="twitter:image" content="<?= $page_og_image ?>">

    <!-- Google Analytics -->
    <?php if ($analytics_id): ?>
        <script async src="https://www.googletagmanager.com/gtag/js?id=<?= $analytics_id ?>"></script>
        <script>
            window.dataLayer = window.dataLayer || [];
            function gtag(){dataLayer.push(arguments);}
            gtag('js', new Date());
            gtag('config', '<?= $analytics_id ?>');
        </script>
    <?php endif; ?>

    <!-- Fonts -->
    <?php 
    $font_setting = getSetting('theme_font_family', "'Montserrat', sans-serif");
    // Extract font name for Google Fonts URL (simple extraction from CSS string)
    preg_match("/'([^']+)'/", $font_setting, $matches);
    $font_name = $matches[1] ?? 'Montserrat';
    $google_font_url = "https://fonts.googleapis.com/css2?family=" . str_replace(' ', '+', $font_name) . ":wght@300;400;500;600;700;800;900&display=swap";
    ?>
    <link href="<?= $google_font_url ?>" rel="stylesheet">
    <!-- FontAwesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <!-- Design System -->
    <link rel="stylesheet" href="<?= getBaseURL() ?>/assets/css/design-system.css">
    
    <!-- Main Stylesheet (Legacy - to be refactored) -->
    <link rel="stylesheet" href="<?= getBaseURL() ?>/style.css?v=<?= time() ?>">
    
    <!-- Main Custom Styles - Load AFTER components to allow overrides -->
    <!-- Dynamic CSS Loading (Minified vs Dev) -->
    <?php
    $css_files = [
        'assets/css/variables.css', 
        'assets/css/utilities.css', 
        'assets/css/main.css', 
        'assets/css/style.css',
        'assets/css/components.css',
        'assets/css/product-page.css',
        'assets/css/checkout.css'
    ];
    
    foreach ($css_files as $css) {
        $min_path = str_replace('.css', '.min.css', $css);
        $full_min_path = __DIR__ . '/../' . $min_path; // Assuming this file is in /includes and assets are in /assets
        $full_css_path = __DIR__ . '/../' . $css;

        if (defined('IS_PRODUCTION') && IS_PRODUCTION && file_exists($full_min_path)) {
            echo '<link rel="stylesheet" href="' . getBaseURL() . $min_path . '?v=' . time() . '">' . "\n";
        } else if (file_exists($full_css_path)) {
            echo '<link rel="stylesheet" href="' . getBaseURL() . $css . '?v=' . time() . '">' . "\n";
        }
    }
    ?>
    
    <!-- Header V2 (Isolated Fix) -->
    <link rel="stylesheet" href="<?= getBaseURL() ?>assets/css/header_v2.css?v=1.1">

    <!-- Dynamic Theme Styles (Database Settings) -->
    <?php require_once __DIR__ . '/dynamic_styles.php'; ?>
    
    <!-- AOS Animation Library -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/aos/2.3.4/aos.css" />

    <!-- Custom Header Code -->
    <?= $header_code ?>

    <!-- Dynamic Custom Services (Head) -->
    <?php 
    if (!empty($custom_scripts)) {
        foreach ($custom_scripts as $script) {
            if ($script['placement'] === 'head') {
                echo "<!-- Service: " . htmlspecialchars($script['name']) . " -->\n";
                echo $script['code'] . "\n";
            }
        }
    }
    ?>
</head>
<body>

<!-- Google Tag Manager (noscript) -->
<?php if ($gtm_id): ?>
<noscript><iframe src="https://www.googletagmanager.com/ns.html?id=<?= $gtm_id ?>"
height="0" width="0" style="display:none;visibility:hidden"></iframe></noscript>
<?php endif; ?>
<!-- End Google Tag Manager (noscript) -->

<!-- Top Bar (Corporate Links) -->
<div class="top-bar mobile-hidden">
    <div class="container">
        <div class="top-links">
            <div class="lang-switcher" style="display: inline-flex; gap: 10px; margin-right: 15px;">
                <?php $current_lang = isset($_SESSION['lang']) ? $_SESSION['lang'] : 'en'; ?>
                <a href="?lang=en" style="<?= $current_lang === 'en' ? 'font-weight: bold; color: var(--primary);' : 'color: #64748b;' ?>">EN</a>
                <span class="sep" style="color: #cbd5e1;">|</span>
                <a href="?lang=hi" style="<?= $current_lang === 'hi' ? 'font-weight: bold; color: var(--primary);' : 'color: #64748b;' ?>">HI</a>
            </div>
        <?php
            $social_facebook = getSetting('theme_social_facebook', 'https://facebook.com');
            $social_instagram = getSetting('theme_social_instagram', 'https://instagram.com');
            $social_twitter = getSetting('theme_social_twitter', 'https://twitter.com');
            $social_youtube = getSetting('theme_social_youtube', 'https://youtube.com');
            
            if ($social_facebook): ?>    
        <a href="#"><?= __('store_locator') ?></a>
        </div>
        
        <!-- Social Media Icons -->
        <div class="top-social">
            
                <a href="<?= htmlspecialchars($social_facebook) ?>" target="_blank" rel="noopener" title="Facebook">
                    <i class="fa-brands fa-facebook"></i>
                </a>
            <?php endif; ?>
            
            <?php if ($social_instagram): ?>
                <a href="<?= htmlspecialchars($social_instagram) ?>" target="_blank" rel="noopener" title="Instagram">
                    <i class="fa-brands fa-instagram"></i>
                </a>
            <?php endif; ?>
            
            <?php if ($social_twitter): ?>
                <a href="<?= htmlspecialchars($social_twitter) ?>" target="_blank" rel="noopener" title="Twitter">
                    <i class="fa-brands fa-twitter"></i>
                </a>
            <?php endif; ?>
            
            <?php if ($social_youtube): ?>
                <a href="<?= htmlspecialchars($social_youtube) ?>" target="_blank" rel="noopener" title="YouTube">
                    <i class="fa-brands fa-youtube"></i>
                </a>
            <?php endif; ?>
        </div>
        
        <div class="top-details">
            <a href="contact.php">Contact Us</a> 
            <span class="mx-2">|</span>
            <i class="fa-solid fa-phone"></i> 95237 98222
        </div>
    </div>
</div>

<!-- ============================
     MOBILE HEADER (Lenskart Style)
     Visible only on max-width 1100px
     ============================ -->
<div class="mobile-header-container">
    <!-- Top Row -->
    <div class="mobile-top-row">
        <div class="mobile-left-section">
            <!-- Avatar -->
            <a href="<?= getBaseURL() ?>profile.php" class="mobile-user-avatar">
                <?= strtoupper(substr($_SESSION['user_name'] ?? 'M', 0, 1)) ?>
            </a>
            <!-- Location/Delivery Info -->
            <div class="mobile-location-info cursor-pointer" onclick="detectLocation()">
                <span class="fast-delivery-tag">Get faster delivery ⚡</span>
                <span class="location-text" id="location-display">Select Location <i class="fa-solid fa-caret-down"></i></span>
            </div>
        </div>

        <div class="mobile-right-actions">
            <!-- Wishlist -->
            <a href="<?= getBaseURL() ?>wishlist.php" class="mobile-action-icon">
                <i class="fa-regular fa-heart"></i>
            </a>

            <!-- Cart -->
            <a href="<?= getBaseURL() ?>cart.php" class="mobile-action-icon relative">
                <i class="fa-solid fa-bag-shopping"></i>
                <?php if ($cart_count > 0): ?>
                    <span class="mobile-cart-badge"><?= $cart_count ?></span>
                <?php endif; ?>
            </a>
            
            <!-- Menu Toggle -->
             <button class="mobile-menu-toggle" id="lenskartMobileToggle">
                <i class="fa-solid fa-bars"></i>
            </button>
        </div>
    </div>

    <!-- Bottom Row: Search Only -->
    <div class="mobile-bottom-row">
        <form action="shop.php" method="GET" class="mobile-search-wrapper w-full">
             <i class="fa-solid fa-magnifying-glass search-icon"></i>
             <input type="text" name="search" placeholder="Search product" class="w-full">
        </form>
    </div>
</div>
<!-- End Mobile Header -->
<!-- End Mobile Header -->

<nav class="navbar desktop-only">
    <div class="container nav-container">
        <!-- Logo -->
        <a href="<?= getBaseURL() ?>index.php" class="logo">
            <?php if ($site_logo): ?>
                <img src="<?= getBaseURL() ?>assets/uploads/<?= $site_logo ?>" alt="Super Optical">
            <?php else: ?>
                <img src="https://static1.lenskart.com/media/desktop/img/site-images/main_logo.svg" alt="Lenskart" class="h-6">
            <?php endif; ?>
        </a>

        <!-- Desktop Nav Links (Dynamic) -->
        <ul class="nav-links mobile-hidden">
            <?php 
            // Fetch Categories Tree (Recursive) needed for mobile, but for desktop we just list top roots
            if (!isset($mobile_cats)) $mobile_cats = getCategoryTree();
            
            // Limit to top 7 parent categories for the navbar to prevent overflow
            $desktop_cats = array_slice($mobile_cats, 0, 7); 
            
            foreach ($desktop_cats as $cat) {
                // Desktop: Only Parents, No Dropdowns
                echo '<li><a href="' . getBaseURL() . 'shop.php?category=' . $cat['id'] . '">' . htmlspecialchars($cat['name']) . '</a></li>';
            }
            ?>
            
            <li><a href="services.php" class="text-accent"><?= __('services') ?></a></li>
            <li><a href="contact.php" class="text-accent"><?= __('contact') ?></a></li>
            <li><a href="book_appointment.php" class="btn-nav-booking">Book Eye Test</a></li>
        </ul>

         <!-- Desktop Search Bar -->
         <form action="shop.php" method="GET" class="header-search mobile-hidden">
            <i class="fa-solid fa-search"></i>
            <input type="text" name="search" placeholder="Search for products">
        </form>

        <!-- Actions -->
        <div class="nav-actions">
            <div class="relative"> 
                <a href="<?= getBaseURL() ?>cart.php" class="nav-action-icon">
                    <i class="fa-solid fa-bag-shopping"></i>
                    <?php if ($cart_count > 0): ?>
                        <span class="cart-badge"><?= $cart_count ?></span>
                    <?php endif; ?>
                </a>
            </div>

            <?php if (isLoggedIn()): ?>
                <div class="user-dropdown-container">
                    <!-- Toggle Button (Click) -->
                    <div class="user-avatar-circle" onclick="toggleUserDropdown(event)" id="userDropdownBtn">
                        <?= strtoupper(substr($_SESSION['user_name'] ?? 'U', 0, 1)) ?>
                    </div>
                    
                    <!-- Dropdown Menu -->
                    <div class="user-dropdown-menu" id="userDropdownMenu">
                        <div class="dropdown-header">
                            <div class="user-avatar-circle large">
                                <?= strtoupper(substr($_SESSION['user_name'] ?? 'U', 0, 1)) ?>
                            </div>
                            <div class="user-info">
                                <span class="user-name"><?= htmlspecialchars($_SESSION['user_name'] ?? 'User') ?></span>
                                <span class="user-subtext">Enjoy your vision!</span>
                            </div>
                        </div>
                        <ul class="dropdown-list">
                            <li><a href="<?= getBaseURL() ?>my-orders.php">My Orders <i class="fa-solid fa-chevron-right"></i></a></li>
                            <li><a href="<?= getBaseURL() ?>profile.php">My Prescription <i class="fa-solid fa-chevron-right"></i></a></li>
                            <li><a href="<?= getBaseURL() ?>profile.php">My Store Credit <i class="fa-solid fa-chevron-right"></i></a></li>
                            <li><a href="<?= getBaseURL() ?>profile.php">Account Information <i class="fa-solid fa-chevron-right"></i></a></li>
                            <li class="logout-item"><a href="<?= getBaseURL() ?>logout.php">Logout</a></li>
                        </ul>
                    </div>
                </div>

                <script>
                    function toggleUserDropdown(e) {
                        e.preventDefault();
                        e.stopPropagation();
                        // Toggle active class
                        const menu = document.getElementById('userDropdownMenu');
                        menu.classList.toggle('active');
                    }

                    // Close on click outside
                    document.addEventListener('click', function(e) {
                        const menu = document.getElementById('userDropdownMenu');
                        const btn = document.getElementById('userDropdownBtn');
                        
                        if (menu && menu.classList.contains('active')) {
                            if (!menu.contains(e.target) && !btn.contains(e.target)) {
                                menu.classList.remove('active');
                            }
                        }
                    });
                </script>
            <?php else: ?>
                <a href="<?= getBaseURL() ?>login.php" class="nav-action-icon mobile-hidden" title="Login"><i class="fa-regular fa-user"></i></a>
            <?php endif; ?>
            
            <!-- Mobile Menu Button -->
            <button class="mobile-menu-btn" id="mobileMenuBtn">
                <i class="fa-solid fa-bars"></i>
            </button>
        </div>
    </div>
</nav>

<!-- Mobile Menu Overlay -->
<div class="mobile-menu-overlay" id="mobileMenuOverlay"></div>

<!-- Mobile Sidebar (Lenskart Style) -->
<div class="mobile-sidebar" id="mobileSidebar">
    <div class="mobile-sidebar-header">
        <div class="user-greeting">
            <div class="user-avatar-circle">
                <i class="fa-regular fa-user"></i>
            </div>
            <div class="greeting-text">
                <?php if (isLoggedIn()): ?>
                    <span>Hi <?= htmlspecialchars($_SESSION['user_name'] ?? 'User') ?>!</span>
                    <small>Member</small>
                <?php else: ?>
                    <span>Hi Guest!</span>
                    <small>Login for best experience</small>
                <?php endif; ?>
            </div>
        </div>
        <button class="close-sidebar-btn" id="closeSidebarBtn">&times;</button>
    </div>

    <div class="mobile-sidebar-content">
        <!-- Main CTA -->
        <div class="mobile-cta-section">
            <?php if (isLoggedIn()): ?>
                 <a href="<?= getBaseURL() ?>profile.php" class="btn-sidebar-cta">
                    <span>View Profile</span>
                    <i class="fa-solid fa-chevron-right"></i>
                 </a>
            <?php else: ?>
                <a href="<?= getBaseURL() ?>login.php" class="btn-sidebar-cta">
                    <span>Login / Suggest</span>
                    <i class="fa-solid fa-chevron-right"></i>
                </a>
            <?php endif; ?>
        </div>
        
        <!-- Contact Strip -->
        <div class="mobile-contact-strip">
            <span>Talk to us</span>
            <a href="tel:+919523798222"><i class="fa-solid fa-phone"></i> +91 95237 98222</a>
        </div>

        <!-- Menu Links -->
        <div class="mobile-menu-group">
            <a href="<?= getBaseURL() ?>index.php" class="mobile-menu-link">
                <span><?= __('home') ?></span>
                <i class="fa-solid fa-chevron-right"></i>
            </a>

            <!-- Categories Accordion -->
            <div class="mobile-menu-expandable">
                <div class="menu-heading">
                    <span><?= __('categories') ?></span>
                    <i class="fa-solid fa-chevron-down"></i>
                </div>
                <div class="menu-subitems">
                    <a href="<?= getBaseURL() ?>shop.php">All Products</a>
                    <?php 
                    // Dynamic Categories with Subcategories (Recursive)
                    $mobile_cats = getCategoryTree(); 
                    
                    function renderMobileCategory($cat, $level = 0) {
                        $hasChildren = !empty($cat['children']);
                        $indent = $level * 12; // px indentation
                        
                        echo '<div class="mobile-cat-item" style="padding-left: ' . $indent . 'px;">';
                        
                        if ($hasChildren) {
                            echo '<div class="mobile-menu-expandable-child">';
                            echo '<div class="menu-heading-child flex justify-between items-center py-2 pr-4">';
                            echo '<a href="' . getBaseURL() . 'shop.php?category=' . $cat['id'] . '" class="text-gray-800 font-medium">' . htmlspecialchars($cat['name']) . '</a>';
                            echo '<i class="fa-solid fa-chevron-down text-xs text-gray-400"></i>';
                            echo '</div>';
                            echo '<div class="menu-subitems-child hidden pl-2 border-l-2 border-gray-100 ml-1">';
                            foreach ($cat['children'] as $child) {
                                renderMobileCategory($child, $level + 1);
                            }
                            echo '</div>';
                            echo '</div>';
                        } else {
                            echo '<a href="' . getBaseURL() . 'shop.php?category=' . $cat['id'] . '" class="block py-2 text-gray-600 hover:text-primary">' . htmlspecialchars($cat['name']) . '</a>';
                        }
                        
                        echo '</div>';
                    }

                    if (!empty($mobile_cats)) {
                        foreach ($mobile_cats as $m_cat) {
                            renderMobileCategory($m_cat);
                        }
                    }
                    ?>
                    
                    <script>
                        // Tiny script for the new nested menus
                        document.addEventListener('DOMContentLoaded', () => {
                            document.querySelectorAll('.menu-heading-child').forEach(header => {
                                header.querySelector('.fa-chevron-down').addEventListener('click', (e) => {
                                    e.preventDefault();
                                    e.stopPropagation();
                                    const sub = header.nextElementSibling;
                                    sub.classList.toggle('hidden');
                                    header.querySelector('.fa-chevron-down').classList.toggle('rotate-180');
                                });
                            });
                        });
                    </script>
                </div>
            </div>

            <a href="<?= getBaseURL() ?>services.php" class="mobile-menu-link">
                <span><?= __('services') ?></span>
                <i class="fa-solid fa-chevron-right"></i>
            </a>

            <a href="<?= getBaseURL() ?>contact.php" class="mobile-menu-link">
                <span><?= __('contact') ?></span>
                <i class="fa-solid fa-chevron-right"></i>
            </a>

            <a href="<?= getBaseURL() ?>book_appointment.php" class="mobile-menu-link text-primary font-semibold">
                <span><i class="fa-solid fa-calendar-check mr-2"></i>Book Eye Test</span>
                <i class="fa-solid fa-chevron-right"></i>
            </a>

            <?php if (isLoggedIn()): ?>
                <div class="divider my-2"></div>
                <a href="<?= getBaseURL() ?>my-orders.php" class="mobile-menu-link">
                    <span><i class="fa-solid fa-box-open"></i> My Orders</span>
                </a>
                <a href="<?= getBaseURL() ?>logout.php" class="mobile-menu-link log-out">
                    <span><i class="fa-solid fa-power-off"></i> Logout</span>
                </a>
            <?php endif; ?>
            
            <!-- Social Media Section -->
            <div class="divider my-3"></div>
            <div class="mobile-social-section">
                <h6 style="font-size: 11px; color: #64748b; text-transform: uppercase; letter-spacing: 0.5px; margin-bottom: 12px; font-weight: 600;">Follow Us</h6>
                <div class="mobile-social-icons">
                    <?php
                    $social_fb = getSetting('theme_social_facebook', 'https://facebook.com');
                    $social_ig = getSetting('theme_social_instagram', 'https://instagram.com');
                    $social_tw = getSetting('theme_social_twitter', 'https://twitter.com');
                    $social_yt = getSetting('theme_social_youtube', 'https://youtube.com');
                    
                    if ($social_fb): ?>
                        <a href="<?= htmlspecialchars($social_fb) ?>" target="_blank" rel="noopener" title="Facebook">
                            <i class="fa-brands fa-facebook"></i>
                        </a>
                    <?php endif; ?>
                    
                    <?php if ($social_ig): ?>
                        <a href="<?= htmlspecialchars($social_ig) ?>" target="_blank" rel="noopener" title="Instagram">
                            <i class="fa-brands fa-instagram"></i>
                        </a>
                    <?php endif; ?>
                    
                    <?php if ($social_tw): ?>
                        <a href="<?= htmlspecialchars($social_tw) ?>" target="_blank" rel="noopener" title="Twitter">
                            <i class="fa-brands fa-twitter"></i>
                        </a>
                    <?php endif; ?>
                    
                    <?php if ($social_yt): ?>
                        <a href="<?= htmlspecialchars($social_yt) ?>" target="_blank" rel="noopener" title="YouTube">
                            <i class="fa-brands fa-youtube"></i>
                        </a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        
        <div class="mobile-sidebar-footer">
            <p>Super Optical v2.0</p>
            <p>&copy; <?= date('Y') ?> All Rights Reserved</p>
        </div>
    </div>
</div>

<!-- Sticky Bottom Mobile Menu -->
<div class="mobile-bottom-nav">
    <a href="<?= getBaseURL() ?>index.php" class="bottom-nav-item <?= basename($_SERVER['PHP_SELF']) == 'index.php' ? 'active' : '' ?>">
        <i class="fa-solid fa-house"></i>
        <span>Home</span>
    </a>
    
    <a href="<?= getBaseURL() ?>shop.php" class="bottom-nav-item <?= basename($_SERVER['PHP_SELF']) == 'shop.php' ? 'active' : '' ?>">
        <i class="fa-solid fa-layer-group"></i>
        <span>Categories</span>
    </a>

    <a href="<?= getBaseURL() ?>profile.php" class="bottom-nav-item <?= basename($_SERVER['PHP_SELF']) == 'profile.php' ? 'active' : '' ?>">
        <i class="fa-regular fa-user"></i>
        <span>You</span>
    </a>

    <a href="<?= getBaseURL() ?>cart.php" class="bottom-nav-item relative <?= basename($_SERVER['PHP_SELF']) == 'cart.php' ? 'active' : '' ?>">
        <div class="relative">
            <i class="fa-solid fa-cart-shopping"></i>
            <?php if ($cart_count > 0): ?>
                <span class="bottom-nav-badge"><?= $cart_count ?></span>
            <?php endif; ?>
        </div>
        <span>Cart</span>
    </a>

    <button class="bottom-nav-item" id="bottomMenuToggle">
        <i class="fa-solid fa-bars"></i>
        <span>Menu</span>
    </button>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        const mobileMenuBtn = document.getElementById('mobileMenuBtn');
        const sidebar = document.getElementById('mobileSidebar');
        const overlay = document.getElementById('mobileMenuOverlay');
        const closeBtn = document.getElementById('closeSidebarBtn');

        function toggleMenu() {
            sidebar.classList.toggle('active');
            overlay.classList.toggle('active');
            document.body.style.overflow = sidebar.classList.contains('active') ? 'hidden' : '';
        }

        if(mobileMenuBtn) mobileMenuBtn.addEventListener('click', toggleMenu);
        const lenskartToggle = document.getElementById('lenskartMobileToggle');
        if(lenskartToggle) lenskartToggle.addEventListener('click', toggleMenu);
        
        // Bottom Menu Toggle
        const bottomToggle = document.getElementById('bottomMenuToggle');
        if(bottomToggle) bottomToggle.addEventListener('click', toggleMenu);

        if(closeBtn) closeBtn.addEventListener('click', toggleMenu);
        if(overlay) overlay.addEventListener('click', toggleMenu);
        
        // Expandable menus
        const expandables = document.querySelectorAll('.menu-heading');
        expandables.forEach(head => {
            head.addEventListener('click', function() {
                this.parentElement.classList.toggle('open');
            });
        });
    });

    // Geolocation Logic
    function detectLocation() {
        const display = document.getElementById('location-display');
        
        if (!navigator.geolocation) {
            showToast("Geolocation is not supported by your browser", "error");
            return;
        }

        display.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Locating...';

        navigator.geolocation.getCurrentPosition(
            (position) => {
                const lat = position.coords.latitude;
                const lon = position.coords.longitude;
                
                // Switch to BigDataCloud API (Free, Client-side friendly, No Key)
                fetch(`https://api.bigdatacloud.net/data/reverse-geocode-client?latitude=${lat}&longitude=${lon}&localityLanguage=en`)
                .then(response => response.json())
                .then(data => {
                    // BigDataCloud returns 'city', 'locality', or 'principalSubdivision'
                    let city = data.city || data.locality || data.principalSubdivision || "Unknown Location";
                    let postcode = data.postcode || "";
                    
                    // Update Text
                    display.innerHTML = `${city} ${postcode} <i class="fa-solid fa-check-circle text-success"></i>`;
                    showToast(`Location set to ${city}`, "success");
                })
                .catch(error => {
                    console.error("Geocoding error:", error);
                    display.innerHTML = "Select Location <i class='fa-solid fa-caret-down'></i>";
                    showToast("Could not retrieve address details", "error");
                });
            },
            (error) => {
                let msg = "Unable to retrieve location";
                if (error.code === 1) msg = "Location permission denied";
                if (error.code === 2) msg = "Position unavailable";
                if (error.code === 3) msg = "Request timed out";
                
                display.innerHTML = "Select Location <i class='fa-solid fa-caret-down'></i>";
                showToast(msg, "error");
            }
        );
    }
</script>

<div id="toast-container"></div>

<script>
function showToast(message, type = 'success') {
    const container = document.getElementById('toast-container');
    const toast = document.createElement('div');
    toast.className = `toast ${type}`;
    
    const icon = type === 'success' ? 'fa-circle-check' : 'fa-circle-exclamation';
    const title = type === 'success' ? 'Success' : 'Error';
    
    toast.innerHTML = `
        <div class="toast-icon"><i class="fa-solid ${icon}"></i></div>
        <div class="toast-content">
            <span class="toast-title">${title}</span>
            <span class="toast-message">${message}</span>
        </div>
    `;
    
    container.appendChild(toast);
    
    // Trigger animation
    setTimeout(() => toast.classList.add('active'), 10);
    
    // Remove after 5 seconds
    setTimeout(() => {
        toast.classList.remove('active');
        setTimeout(() => toast.remove(), 400);
    }, 5000);
}

<?php if ($flash = getFlash()): ?>
    showToast("<?= addslashes($flash['message']) ?>", "<?= $flash['type'] ?>");
<?php endif; ?>
</script>
