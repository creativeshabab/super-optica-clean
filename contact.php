<?php require_once 'includes/header.php'; ?>

<!-- Page Hero -->
<section class="page-hero">
    <div class="container">
        <?php renderBreadcrumbs([__('contact') => null]); ?>
    </div>
</section>

<!-- Link Checkout CSS for consistency -->
<link rel="stylesheet" href="assets/css/checkout.css">

<section class="web-wrapper section-padding">
    <div class="container mx-auto px-4">
        
        <!-- Standard Header (Matches Shop) -->
        <div class="flex flex-row justify-between items-center mb-10 gap-4">
           <div class="text-left m-0">
              <span class="text-primary font-bold uppercase tracking-widest text-sm"><?= __('visit_us') ?></span>
              <h2 class="page-title-responsive font-black text-gray-800 mt-2"><?= __('contact') ?> <span class="text-primary">Us</span></h2>
           </div>
        </div>

        <div class="checkout-container bg-transparent p-0">
            <!-- Top Row: Info & Map -->
        <div class="grid grid-cols-1 md:grid-cols-2 gap-8 mb-10 items-stretch">
            
            <!-- Contact Info Card -->
            <div class="checkout-card h-full flex flex-col justify-center">
                <h3 class="checkout-card-title mb-6">
                    <i class="fa-solid fa-circle-info text-primary"></i> <?= __('contact_info') ?>
                </h3>
                
                <div class="space-y-6">
                    <!-- Location -->
                    <div class="flex gap-4 items-start">
                        <div class="w-12 h-12 bg-red-50 rounded-full flex items-center justify-center text-primary text-lg flex-shrink-0">
                            <i class="fa-solid fa-location-dot"></i>
                        </div>
                        <div>
                            <h4 class="font-bold text-gray-800 mb-1"><?= __('our_location') ?></h4>
                            <p class="text-gray-500 text-sm leading-relaxed"><?= __('address_full') ?></p>
                        </div>
                    </div>
                    
                    <!-- Phone -->
                    <div class="flex gap-4 items-start">
                        <div class="w-12 h-12 bg-red-50 rounded-full flex items-center justify-center text-primary text-lg flex-shrink-0">
                            <i class="fa-solid fa-phone"></i>
                        </div>
                        <div>
                            <h4 class="font-bold text-gray-800 mb-1"><?= __('phone_no') ?></h4>
                            <div class="flex flex-col gap-1">
                                <a href="tel:+919523798222" class="text-gray-500 hover:text-primary transition-colors text-sm font-medium">+91 95237 98222</a>
                                <a href="tel:+916209747650" class="text-gray-500 hover:text-primary transition-colors text-sm font-medium">+91 62097 47650</a>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Email -->
                    <div class="flex gap-4 items-start">
                         <div class="w-12 h-12 bg-red-50 rounded-full flex items-center justify-center text-primary text-lg flex-shrink-0">
                            <i class="fa-solid fa-envelope"></i>
                        </div>
                        <div>
                            <h4 class="font-bold text-gray-800 mb-1"><?= __('email_address') ?></h4>
                            <a href="mailto:info@superoptical.in" class="text-gray-500 hover:text-primary transition-colors text-sm font-medium">info@superoptical.in</a>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Map Card -->
            <div class="checkout-card p-0 overflow-hidden h-full min-h-[300px]">
                <iframe src="https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d3607.784462136069!2d86.13098527598857!3d25.44509167755322!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x39f1f900dc187103%3A0xc39f8f411b98c37!2sSuper%20Optical!5e0!3m2!1sen!2sin!4v1709666000000!5m2!1sen!2sin" 
                width="100%" height="100%" style="border:0;" allowfullscreen="" loading="lazy" referrerpolicy="no-referrer-when-downgrade"></iframe>
            </div>

        </div>

        <!-- Contact Form Card (Full Width) -->
        <div class="checkout-card max-w-4xl mx-auto">
            <h3 class="checkout-card-title text-center justify-center mb-8">
                <i class="fa-solid fa-paper-plane text-primary"></i> <?= __('send_message') ?>
            </h3>
            
            <form action="includes/process_contact.php" method="POST">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div class="form-floating">
                        <label><?= __('your_name') ?></label>
                        <input type="text" name="name" required placeholder="<?= __('placeholder_name') ?>">
                    </div>
                    <div class="form-floating">
                        <label><?= __('email_address') ?></label>
                        <input type="email" name="email" required placeholder="<?= __('placeholder_email') ?>">
                    </div>
                </div>
                
                <div class="form-floating mt-4">
                    <label><?= __('subject') ?></label>
                    <input type="text" name="subject" required placeholder="<?= __('placeholder_subject') ?>">
                </div>
                
                <div class="form-floating mt-4">
                    <label><?= __('message') ?></label>
                    <textarea name="message" rows="5" required placeholder="<?= __('placeholder_message') ?>" style="min-height: 150px;"></textarea>
                </div>
                
                <div class="text-center mt-6">
                    <button type="submit" class="btn btn-primary shadow-lg hover:shadow-xl hover:-translate-y-1 transition-all rounded-full">
                        <?= __('send_message') ?> <i class="fa-solid fa-paper-plane ml-2"></i>
                    </button>
                </div>
            </form>
        </div>
    </div>
</section>

<?php require_once 'includes/footer.php'; ?>
