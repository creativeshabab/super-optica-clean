<?php
require_once __DIR__ . '/functions.php';
$site_logo = getSetting('site_logo');
$whatsapp = getSetting('whatsapp_number');
$instagram = getSetting('instagram_link');
$base_url = getBaseURL();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Maintenance Mode - Super Optical</title>
    <link rel="stylesheet" href="<?= $base_url ?>assets/css/design-system.css">
    <?php require_once __DIR__ . '/dynamic_styles.php'; ?>
</head>
<body class="maintenance-screen">
    <div class="maintenance-card">
        <?php if ($site_logo): ?>
            <img src="<?= $base_url ?>assets/uploads/<?= $site_logo ?>" alt="Super Optical" class="logo h-16 w-auto mb-10 mx-auto">
        <?php else: ?>
            <h2 class="text-accent mb-8 text-3xl font-black">SUPER <span class="text-primary">OPTICAL</span></h2>
        <?php endif; ?>

        <div class="maintenance-icon-box">
            <i class="fa-solid fa-tools"></i>
        </div>
        
        <h1 class="text-3xl font-black text-accent mb-4">Enhancing Your View</h1>
        <p class="text-light text-lg mb-10">Our website is currently undergoing a "Luxe" transformation to serve you better. We'll be back online very soon!</p>
        
        <div class="border-t border-gray-100 pt-8 mt-4">
            <span class="font-bold text-accent block mb-4">Need urgent assistance?</span>
            <div class="flex justify-center gap-6">
                <?php if ($whatsapp): ?>
                    <a href="https://wa.me/<?= $whatsapp ?>" class="social-btn whatsapp" title="WhatsApp Us">
                        <i class="fa-brands fa-whatsapp"></i>
                    </a>
                <?php endif; ?>
                <?php if ($instagram): ?>
                    <a href="<?= $instagram ?>" class="social-btn instagram" title="Follow Us">
                        <i class="fa-brands fa-instagram"></i>
                    </a>
                <?php endif; ?>
            </div>
        </div>
        
        <div class="mt-12 text-sm text-gray-400 font-medium">
            &copy; <?= date('Y') ?> Super Optical. Begusarai's Premier Eyewear.
        </div>
    </div>
</body>
</html>
