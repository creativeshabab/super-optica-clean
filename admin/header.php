<?php
ob_start();
require_once '../config/db.php';
require_once '../includes/functions.php';

if (!isAdmin()) {
    redirect('login.php');
}

// Get unread notification count
$unread_count_stmt = $pdo->query("SELECT COUNT(*) FROM admin_notifications WHERE is_read = 0");
$unread_count = $unread_count_stmt->fetchColumn();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Panel - Super Optical</title>
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/admin.css">
    <link rel="stylesheet" href="../assets/css/variables.css">
    <link rel="stylesheet" href="../assets/css/components.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <!-- Dynamic Typography & Theme Styles -->
    <?php include '../includes/dynamic_styles.php'; ?>
    
    <style>
        /* Notification Bell Styles */
        .notification-bell {
            position: relative;
            display: flex;
            align-items: center;
            justify-content: center;
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background: rgba(255, 255, 255, 0.1);
            color: white;
            transition: all 0.2s ease;
            text-decoration: none;
        }
        
        .notification-bell:hover {
            background: rgba(255, 255, 255, 0.2);
            transform: scale(1.05);
        }
        
        .notification-badge {
            position: absolute;
            top: -4px;
            right: -4px;
            background: var(--admin-primary);
            color: white;
            font-size: 0.65rem;
            font-weight: 700;
            padding: 0.15rem 0.4rem;
            border-radius: 10px;
            min-width: 18px;
            text-align: center;
            box-shadow: 0 2px 8px rgba(227, 30, 36, 0.4);
        }
        
        .sidebar-notification-badge {
            position: absolute;
            right: 1rem;
            top: 50%;
            transform: translateY(-50%);
            background: var(--admin-primary);
            color: white;
            font-size: 0.65rem;
            font-weight: 700;
            padding: 0.15rem 0.5rem;
            border-radius: 10px;
            min-width: 20px;
            text-align: center;
        }
    </style>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <link href="https://cdn.jsdelivr.net/npm/summernote@0.8.18/dist/summernote-lite.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/summernote@0.8.18/dist/summernote-lite.min.js"></script>
    <script src="../assets/js/theme-toggle.js"></script>

    <script>
        $(document).ready(function() {
            $('.rich-editor').summernote({
                placeholder: 'Write your content here...',
                tabsize: 2,
                height: 300,
                toolbar: [
                    ['style', ['style']],
                    ['font', ['bold', 'underline', 'clear']],
                    ['color', ['color']],
                    ['para', ['ul', 'ol', 'paragraph']],
                    ['table', ['table']],
                    ['insert', ['link', 'picture', 'video']],
                    ['view', ['fullscreen', 'codeview', 'help']]
                ]
            });
        });

        function initDragAndDrop(boxId, inputId, previewId, isGallery = false) {
            const box = document.getElementById(boxId);
            const input = document.getElementById(inputId);
            const preview = document.getElementById(previewId);

            if (!box || !input) return;

            function handleFiles(files) {
                if (files.length > 0) {
                    if (isGallery && preview) preview.innerHTML = ''; // Clear for gallery
                    
                    Array.from(files).forEach((file, index) => {
                        if (!file.type.startsWith('image/')) return;
                        if (!isGallery && index > 0) return; // Only process first for single upload

                        const reader = new FileReader();
                        reader.onload = function(e) {
                            if (preview) {
                                if (isGallery) {
                                    const previewItem = document.createElement('div');
                                    previewItem.style = 'position: relative; width: 60px; height: 60px; border-radius: 8px; overflow: hidden; border: 1px solid #e2e8f0;';
                                    previewItem.innerHTML = `<img src="${e.target.result}" style="width: 100%; height: 100%; object-fit: cover;">`;
                                    preview.appendChild(previewItem);
                                } else {
                                    preview.style.display = 'flex';
                                    const img = preview.querySelector('.drag-preview-image');
                                    if (img) img.src = e.target.result;
                                }
                            }
                        }
                        reader.readAsDataURL(file);
                    });
                    
                    if (!isGallery) {
                        const dt = new DataTransfer();
                        dt.items.add(files[0]);
                        input.files = dt.files;
                    } else {
                        input.files = files;
                    }
                }
            }

            box.addEventListener('click', () => input.click());

            box.addEventListener('dragover', (e) => {
                e.preventDefault();
                box.classList.add('dragging');
            });

            box.addEventListener('dragleave', () => {
                box.classList.remove('dragging');
            });

            box.addEventListener('drop', (e) => {
                e.preventDefault();
                box.classList.remove('dragging');
                handleFiles(e.dataTransfer.files);
            });

            input.addEventListener('change', (e) => {
                handleFiles(e.target.files);
            });
        }
    </script>
</head>
<body class="admin-body">
    <header class="mobile-header">
        <div class="admin-logo" style="margin-bottom: 0;">
            <i class="fa-solid fa-bolt" style="color: var(--admin-primary);"></i>
            Luxe
        </div>
        <div style="display: flex; align-items: center; gap: 1rem;">
            <!-- Language Switcher Mobile -->
            <div class="language-switcher" style="margin-right: 0.5rem;">
                <a href="?lang=en" class="<?= !isset($_SESSION['lang']) || $_SESSION['lang'] == 'en' ? 'active' : '' ?>" style="color: var(--admin-text); text-decoration: none; font-weight: 600; font-size: 0.8rem;">EN</a>
                <span style="color: var(--admin-text-light);">|</span>
                <a href="?lang=hi" class="<?= isset($_SESSION['lang']) && $_SESSION['lang'] == 'hi' ? 'active' : '' ?>" style="color: var(--admin-text); text-decoration: none; font-weight: 600; font-size: 0.8rem;">HI</a>
            </div>

            <a href="notifications.php" class="notification-bell" title="<?= __('notifications') ?>">
                <i class="fa-solid fa-bell"></i>
                <?php if ($unread_count > 0): ?>
                    <span class="notification-badge"><?= $unread_count > 99 ? '99+' : $unread_count ?></span>
                <?php endif; ?>
            </a>
            <button class="sidebar-toggle" id="sidebarToggle">
                <i class="fa-solid fa-bars"></i>
            </button>
        </div>
    </header>

    <div class="sidebar-overlay" id="sidebarOverlay"></div>

    <div class="admin-layout">
        <aside class="sidebar" id="adminSidebar">
            <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 2rem;">
                <div class="admin-logo" style="margin-bottom: 0;">
                    <i class="fa-solid fa-bolt" style="color: var(--admin-primary);"></i>
                    Super Optical
                </div>
                <div style="display: flex; gap: 10px; align-items: center;">
                    <!-- Language Switcher Desktop -->
                    <div class="language-switcher" style="display: flex; gap: 5px; font-size: 0.75rem; font-weight: 700; background: rgba(255,255,255,0.05); padding: 4px 8px; border-radius: 6px;">
                        <a href="?lang=en" class="<?= !isset($_SESSION['lang']) || $_SESSION['lang'] == 'en' ? 'text-primary' : 'text-muted' ?>" style="text-decoration: none; color: <?= !isset($_SESSION['lang']) || $_SESSION['lang'] == 'en' ? 'var(--admin-primary)' : 'var(--admin-text-light)' ?>">EN</a>
                        <span style="color: var(--admin-text-light);">|</span>
                        <a href="?lang=hi" class="<?= isset($_SESSION['lang']) && $_SESSION['lang'] == 'hi' ? 'text-primary' : 'text-muted' ?>" style="text-decoration: none; color: <?= isset($_SESSION['lang']) && $_SESSION['lang'] == 'hi' ? 'var(--admin-primary)' : 'var(--admin-text-light)' ?>">HI</a>
                    </div>
                    <button class="theme-toggle" aria-label="Toggle theme" title="Toggle theme">
                        <i class="fa-solid fa-moon"></i>
                    </button>
                </div>
            </div>
            
            <nav class="sidebar-nav">
                <?php 
                $current_page = basename($_SERVER['PHP_SELF']); 
                function isActive($page, $current) { return $page == $current ? 'active' : ''; }
                ?>
                
                <a href="index.php" class="sidebar-link <?= isActive('index.php', $current_page) ?>">
                    <i class="fa-solid fa-chart-pie"></i> <?= __('dashboard') ?>
                </a>

                <!-- Commerce & Operations -->
                <div class="sidebar-group-label"><?= __('commerce_operations') ?></div>
                
                <a href="orders.php" class="sidebar-link <?= isActive('orders.php', $current_page) ?>">
                    <i class="fa-solid fa-cart-shopping"></i> <?= __('orders') ?>
                </a>
                <a href="products.php" class="sidebar-link <?= isActive('products.php', $current_page) ?>">
                    <i class="fa-solid fa-glasses"></i> <?= __('products') ?>
                </a>
                <a href="categories.php" class="sidebar-link <?= isActive('categories.php', $current_page) ?>">
                    <i class="fa-solid fa-layer-group"></i> <?= __('categories') ?>
                </a>
                <a href="coupons.php" class="sidebar-link <?= isActive('coupons.php', $current_page) ?>">
                    <i class="fa-solid fa-ticket"></i> <?= __('coupons_offers') ?>
                </a>
                <a href="appointments.php" class="sidebar-link <?= isActive('appointments.php', $current_page) ?>">
                    <i class="fa-solid fa-calendar-check"></i> <?= __('appointments') ?>
                </a>

                <!-- Content Management -->
                <div class="sidebar-group-label"><?= __('content_management') ?></div>

                <a href="services.php" class="sidebar-link <?= isActive('services.php', $current_page) ?>">
                    <i class="fa-solid fa-hand-holding-medical"></i> <?= __('services') ?>
                </a>
                <a href="gallery.php" class="sidebar-link <?= isActive('gallery.php', $current_page) ?>">
                    <i class="fa-solid fa-images"></i> <?= __('gallery') ?>
                </a>
                <a href="sliders.php" class="sidebar-link <?= isActive('sliders.php', $current_page) ?>">
                    <i class="fa-solid fa-images"></i> <?= __('main_slider') ?>
                </a>
                <a href="reviews.php" class="sidebar-link <?= isActive('reviews.php', $current_page) ?>">
                    <i class="fa-solid fa-star"></i> <?= __('reviews') ?>
                </a>
                <a href="posts.php" class="sidebar-link <?= isActive('posts.php', $current_page) ?>">
                    <i class="fa-solid fa-newspaper"></i> <?= __('blog_posts') ?>
                </a>

                <!-- System & Design -->
                <div class="sidebar-group-label"><?= __('system_design') ?></div>

                <a href="theme.php" class="sidebar-link <?= isActive('theme.php', $current_page) ?>">
                    <i class="fa-solid fa-palette"></i> <?= __('design_system') ?>
                </a>
                <a href="settings.php" class="sidebar-link <?= isActive('settings.php', $current_page) ?>">
                    <i class="fa-solid fa-gear"></i> <?= __('settings') ?>
                </a>
                <a href="integrations.php" class="sidebar-link <?= isActive('integrations.php', $current_page) ?>">
                    <i class="fa-solid fa-plug"></i> <?= __('integrations') ?>
                </a>
                <a href="notifications.php" class="sidebar-link <?= isActive('notifications.php', $current_page) ?>" style="position: relative;">
                    <i class="fa-solid fa-bell"></i> <?= __('notifications') ?>
                    <?php if ($unread_count > 0): ?>
                        <span class="sidebar-notification-badge"><?= $unread_count > 99 ? '99+' : $unread_count ?></span>
                    <?php endif; ?>
                </a>
                
                <a href="../index.php" class="sidebar-link" target="_blank" style="margin-top: 2rem; border: 1px solid rgba(255,255,255,0.1);">
                    <i class="fa-solid fa-arrow-up-right-from-square"></i> <?= __('view_site') ?>
                </a>
                <a href="../logout.php" class="sidebar-link logout-link">
                    <i class="fa-solid fa-right-from-bracket"></i> <?= __('logout') ?>
                </a>
            </nav>
        </aside>

    <script>
    document.getElementById('sidebarToggle').addEventListener('click', function() {
        document.getElementById('adminSidebar').classList.add('active');
        document.getElementById('sidebarOverlay').classList.add('active');
    });

    document.getElementById('sidebarOverlay').addEventListener('click', function() {
        document.getElementById('adminSidebar').classList.remove('active');
        document.getElementById('sidebarOverlay').classList.remove('active');
    });
    </script>
    <main class="main-content">
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
