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


    <div class="sidebar-overlay" id="sidebarOverlay"></div>

    <div class="admin-layout">
        <aside class="sidebar" id="adminSidebar">
            <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 2rem;">
                <div class="admin-logo" style="margin-bottom: 0;">
                    <i class="fa-solid fa-bolt" style="color: var(--admin-primary);"></i>
                    <span class="logo-text">Super Optical</span>
                </div>
            </div>
            
            <nav class="sidebar-nav">
                <?php 
                $current_page = basename($_SERVER['PHP_SELF']); 
                function isActive($page, $current) { return $page == $current ? 'active' : ''; }
                ?>
                
                <a href="index.php" class="sidebar-link <?= isActive('index.php', $current_page) ?>" title="<?= __('dashboard') ?>">
                    <i class="fa-solid fa-chart-pie"></i> <span><?= __('dashboard') ?></span>
                </a>

                <!-- Commerce & Operations -->
                <div class="sidebar-group-label"><?= __('commerce_operations') ?></div>
                
                <a href="orders.php" class="sidebar-link <?= isActive('orders.php', $current_page) ?>" title="<?= __('orders') ?>">
                    <i class="fa-solid fa-cart-shopping"></i> <span><?= __('orders') ?></span>
                </a>
                <a href="products.php" class="sidebar-link <?= isActive('products.php', $current_page) ?>" title="<?= __('products') ?>">
                    <i class="fa-solid fa-glasses"></i> <span><?= __('products') ?></span>
                </a>
                <a href="categories.php" class="sidebar-link <?= isActive('categories.php', $current_page) ?>" title="<?= __('categories') ?>">
                    <i class="fa-solid fa-layer-group"></i> <span><?= __('categories') ?></span>
                </a>
                <a href="coupons.php" class="sidebar-link <?= isActive('coupons.php', $current_page) ?>" title="<?= __('coupons_offers') ?>">
                    <i class="fa-solid fa-ticket"></i> <span><?= __('coupons_offers') ?></span>
                </a>
                <a href="appointments.php" class="sidebar-link <?= isActive('appointments.php', $current_page) ?>" title="<?= __('appointments') ?>">
                    <i class="fa-solid fa-calendar-check"></i> <span><?= __('appointments') ?></span>
                </a>
                <a href="lens_options.php" class="sidebar-link <?= isActive('lens_options.php', $current_page) ?>" title="Lens Options">
                    <i class="fa-solid fa-eye"></i> <span>Lens Options</span>
                </a>

                <!-- Content Management -->
                <div class="sidebar-group-label"><?= __('content_management') ?></div>

                <a href="services.php" class="sidebar-link <?= isActive('services.php', $current_page) ?>" title="<?= __('services') ?>">
                    <i class="fa-solid fa-hand-holding-medical"></i> <span><?= __('services') ?></span>
                </a>
                <a href="gallery.php" class="sidebar-link <?= isActive('gallery.php', $current_page) ?>" title="<?= __('gallery') ?>">
                    <i class="fa-solid fa-images"></i> <span><?= __('gallery') ?></span>
                </a>
                <a href="sliders.php" class="sidebar-link <?= isActive('sliders.php', $current_page) ?>" title="<?= __('main_slider') ?>">
                    <i class="fa-solid fa-images"></i> <span><?= __('main_slider') ?></span>
                </a>
                <a href="reviews.php" class="sidebar-link <?= isActive('reviews.php', $current_page) ?>" title="<?= __('reviews') ?>">
                    <i class="fa-solid fa-star"></i> <span><?= __('reviews') ?></span>
                </a>
                <a href="posts.php" class="sidebar-link <?= isActive('posts.php', $current_page) ?>" title="<?= __('blog_posts') ?>">
                    <i class="fa-solid fa-newspaper"></i> <span><?= __('blog_posts') ?></span>
                </a>

                <!-- System & Design -->
                <div class="sidebar-group-label"><?= __('system_design') ?></div>

                <a href="theme.php" class="sidebar-link <?= isActive('theme.php', $current_page) ?>" title="<?= __('design_system') ?>">
                    <i class="fa-solid fa-palette"></i> <span><?= __('design_system') ?></span>
                </a>
                <a href="settings.php" class="sidebar-link <?= isActive('settings.php', $current_page) ?>" title="<?= __('settings') ?>">
                    <i class="fa-solid fa-gear"></i> <span><?= __('settings') ?></span>
                </a>
                <a href="integrations.php" class="sidebar-link <?= isActive('integrations.php', $current_page) ?>" title="<?= __('integrations') ?>">
                    <i class="fa-solid fa-plug"></i> <span><?= __('integrations') ?></span>
                </a>
                <a href="notifications.php" class="sidebar-link <?= isActive('notifications.php', $current_page) ?>" style="position: relative;" title="<?= __('notifications') ?>">
                    <i class="fa-solid fa-bell"></i> <span><?= __('notifications') ?></span>
                    <?php if ($unread_count > 0): ?>
                        <span class="sidebar-notification-badge"><?= $unread_count > 99 ? '99+' : $unread_count ?></span>
                    <?php endif; ?>
                </a>
                
                <a href="../index.php" class="sidebar-link" target="_blank" style="margin-top: 2rem; border: 1px solid rgba(255,255,255,0.1);" title="<?= __('view_site') ?>">
                    <i class="fa-solid fa-arrow-up-right-from-square"></i> <span><?= __('view_site') ?></span>
                </a>
                <a href="../logout.php" class="sidebar-link logout-link" title="<?= __('logout') ?>">
                    <i class="fa-solid fa-right-from-bracket"></i> <span><?= __('logout') ?></span>
                </a>
            </nav>
        </aside>

    <script>
    document.addEventListener('DOMContentLoaded', function() {
        // Unified Sidebar Toggle
        const sidebarToggle = document.getElementById('sidebarToggle');
        const body = document.body;
        const sidebar = document.getElementById('adminSidebar');
        const overlay = document.getElementById('sidebarOverlay');
        
        // Handle Sidebar Toggle Logic
        if(sidebarToggle) {
            sidebarToggle.addEventListener('click', function() {
                if(window.innerWidth > 1024) {
                    // Desktop Behavior: Collapse
                    body.classList.toggle('sidebar-collapsed');
                    localStorage.setItem('sidebar-collapsed', body.classList.contains('sidebar-collapsed'));
                } else {
                    // Mobile Behavior: Overlay
                    sidebar.classList.toggle('active');
                    overlay.classList.toggle('active');
                }
            });
        }

        // Check local storage for desktop collapse on load
        if(window.innerWidth > 1024 && localStorage.getItem('sidebar-collapsed') === 'true') {
            body.classList.add('sidebar-collapsed');
        }

        // Overlay Click
        if(overlay) {
            overlay.addEventListener('click', function() {
                document.getElementById('adminSidebar').classList.remove('active');
                overlay.classList.remove('active');
            });
        }

        // Close Dropdowns on Outside Click
        document.addEventListener('click', function(e) {
            if (!e.target.closest('.dropdown')) {
                document.querySelectorAll('.dropdown-menu.show').forEach(function(menu) {
                    menu.classList.remove('show');
                });
            }
        });
    });
    </script>
    <main class="main-content">
        <!-- Top Header -->
        <header class="admin-top-bar">
            <div class="top-bar-left">
                <!-- Sidebar Toggle (Unified) -->
                <button class="icon-btn" id="sidebarToggle" title="Toggle Sidebar">
                    <i class="fa-solid fa-bars-staggered"></i>
                </button>
                <div class="dashboard-date">
                    <i class="fa-regular fa-calendar"></i> <?= date('F j, Y') ?>
                </div>
            </div>

            <div class="top-bar-right">
                <!-- Theme Toggle -->
                <button class="icon-btn theme-toggle" title="Toggle Theme">
                    <i class="fa-solid fa-moon"></i>
                </button>

                <!-- Language Info (Static for now, using Session) -->
                <div class="dropdown" style="position: relative;">
                    <button class="icon-btn" onclick="document.getElementById('langDropdown').classList.toggle('show')">
                        <i class="fa-solid fa-globe"></i>
                        <span class="btn-text" style="font-size: 0.8rem; font-weight: 700; margin-left: 0.25rem;">
                            <?= strtoupper($_SESSION['lang'] ?? 'EN') ?>
                        </span>
                    </button>
                    <div id="langDropdown" class="dropdown-menu">
                        <a href="?lang=en" class="dropdown-item <?= (!isset($_SESSION['lang']) || $_SESSION['lang'] == 'en') ? 'active' : '' ?>">
                            English
                        </a>
                        <a href="?lang=hi" class="dropdown-item <?= (isset($_SESSION['lang']) && $_SESSION['lang'] == 'hi') ? 'active' : '' ?>">
                            Hindi
                        </a>
                    </div>
                </div>

                <!-- Notification -->
                <div class="notification-wrapper">
                    <a href="notifications.php" class="icon-btn relative" title="Notifications">
                        <i class="fa-regular fa-bell"></i>
                        <?php if ($unread_count > 0): ?>
                            <span class="notification-badge-dot"></span>
                        <?php endif; ?>
                    </a>
                </div>

                <!-- Profile Dropdown -->
                <div class="dropdown" style="position: relative;">
                    <button class="profile-btn" onclick="document.getElementById('profileDropdown').classList.toggle('show')">
                        <div class="avatar-circle">
                            <?= strtoupper(substr($_SESSION['user_name'] ?? 'Admin', 0, 1)) ?>
                        </div>
                        <div class="profile-info mobile-hidden">
                            <span class="user-name"><?= htmlspecialchars($_SESSION['user_name'] ?? 'Admin') ?></span>
                            <span class="user-role"><?= ucfirst($_SESSION['role'] ?? 'Administrator') ?></span>
                        </div>
                        <i class="fa-solid fa-chevron-down mobile-hidden" style="font-size: 0.7rem;"></i>
                    </button>
                    <div id="profileDropdown" class="dropdown-menu right">
                        <div class="dropdown-header">
                            <strong style="display: block;"><?= htmlspecialchars($_SESSION['user_name'] ?? 'Admin') ?></strong>
                            <small class="text-muted"><?= $_SESSION['email'] ?? 'admin@example.com' ?></small>
                        </div>
                        <div class="dropdown-divider"></div>
                        <a href="profile.php" class="dropdown-item">
                            <i class="fa-regular fa-user"></i> My Profile
                        </a>
                        <a href="settings.php" class="dropdown-item">
                            <i class="fa-solid fa-gear"></i> Settings
                        </a>
                        <div class="dropdown-divider"></div>
                        <a href="../logout.php" class="dropdown-item text-danger">
                            <i class="fa-solid fa-right-from-bracket"></i> Logout
                        </a>
                    </div>
                </div>
            </div>
        </header>

        <div class="admin-container">
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
