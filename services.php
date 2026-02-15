<?php require_once 'includes/header.php'; ?>
<!-- Page Hero -->
<section class="page-hero">
   <div class="container">
      <?php renderBreadcrumbs([__('services') => null]); ?>
   </div>
</section>
<?php 
   $services = $pdo->query("SELECT * FROM services ORDER BY display_order ASC, created_at DESC")->fetchAll();
   ?>
<div class="web-wrapper section-padding bg-gray-50">
   <div class="container mx-auto px-4">
      
      <div class="text-center max-w-3xl mx-auto mb-16">
            <span class="text-primary font-bold uppercase tracking-widest text-sm"><?= __('our_expertise') ?></span>
            <h1 class="text-4xl font-black text-gray-800 mt-2 mb-4"><?= __('premium_eye_care') ?></h1>
            <p class="text-gray-500 text-lg">Comprehensive eye care solutions delivered by certified professionals using state-of-the-art technology.</p>
        </div>
      <div class="flex flex-col gap-20">
      <?php 
      $index = 0;
      foreach ($services as $s): 
         $is_even = ($index % 2 == 0);
         $features = !empty($s['features']) ? json_decode($s['features'], true) : [];
      ?>
      <div class="grid grid-cols-1 md:grid-cols-2 gap-12 items-center <?= $is_even ? '' : 'md:flex-row-reverse' ?>">
         
         <!-- Image Content -->
         <div class="<?= $is_even ? 'order-1 md:order-2' : 'order-1' ?>">
            <div class="relative rounded-2xl overflow-hidden shadow-lg h-80 md:h-96 group">
               <!-- Icon Badge (if exists) -->
               <?php if ($s['icon'] && strpos($s['icon'], 'fa-') !== false): ?>
               <div class="absolute top-4 left-4 w-12 h-12 bg-white rounded-full flex items-center justify-center text-primary shadow-md z-10">
                  <i class="<?= $s['icon'] ?> text-xl"></i>
               </div>
               <?php endif; ?>
               
               <!-- Service Image or Placeholder -->
               <div class="w-full h-full bg-gray-200 flex items-center justify-center">
                  <?php if ($s['image']): ?>
                  <img src="assets/uploads/<?= $s['image'] ?>" class="w-full h-full object-cover transform group-hover:scale-105 transition-transform duration-500" alt="<?= htmlspecialchars($s['title']) ?>">
                  <?php elseif ($s['icon'] && strpos($s['icon'], 'fa-') === false): ?>
                  <img src="assets/uploads/<?= $s['icon'] ?>" class="w-full h-full object-cover">
                  <?php else: ?>
                  <span class="text-gray-400 font-bold">Image for <?= htmlspecialchars($s['title']) ?></span>
                  <?php endif; ?>
               </div>
            </div>
         </div>

         <!-- Text Content -->
         <div class="service-content <?= $is_even ? 'order-2 md:order-1' : 'order-2' ?>">
            <h2 class="text-3xl font-black text-accent mb-4"><?= htmlspecialchars($s['title']) ?></h2>
            <p class="text-lg text-gray-500 mb-6 leading-relaxed">
               <?= htmlspecialchars($s['description']) ?>
            </p>
            
            <!-- Dynamic Features -->
            <?php if (!empty($features)): ?>
            <ul class="space-y-3 mb-8">
               <?php foreach ($features as $feature): ?>
               <li class="flex items-center gap-3 text-gray-700">
                  <i class="fa-solid fa-circle-check text-primary"></i>
                  <span><?= htmlspecialchars($feature) ?></span>
               </li>
               <?php endforeach; ?>
            </ul>
            <?php endif; ?>
            
            <a href="contact.php" class="btn btn-primary px-8 py-3 rounded-full shadow-md hover:shadow-xl transition-shadow">
                <?= __('contact_us') ?> <i class="fa-solid fa-arrow-right ml-2"></i>
            </a>
         </div>
         
      </div>
      <?php 
      $index++;
      endforeach; 
      ?>
      </div>
   </div>
</div>
<section class="section-cta py-16 bg-white border-t border-gray-100">
   <div class="container mx-auto px-4">
      <div class="Experience text-center bg-gray-900 rounded-3xl p-10 md:p-16 relative overflow-hidden">
         <div class="absolute inset-0 opacity-20 bg-[url('../images/pattern.png')]"></div>
         <div class="relative z-10 text-white">
            <div class="w-20 h-20 mx-auto bg-white/10 rounded-full flex items-center justify-center text-3xl mb-6 backdrop-blur-sm">
                <i class="fa-solid fa-location-dot"></i>
            </div>
        
            <h2 class="text-3xl md:text-5xl font-black mb-4"><?= __('ready_experience') ?></h2>
            <p class="text-gray-300 text-lg mb-8 max-w-2xl mx-auto"><?= __('book_appointment_desc') ?></p>
            
            <div class="flex flex-col md:flex-row justify-center gap-4">
                <a href="contact.php" class="btn bg-white text-gray-900 hover:bg-gray-100 px-8 py-3 rounded-full font-bold transition-transform hover:-translate-y-1"><?= __('contact_us') ?></a>
                <a href="tel:+919523798222" class="btn border border-white text-white hover:bg-white hover:text-gray-900 px-8 py-3 rounded-full font-bold transition-all"><?= __('call_now') ?></a>
            </div>
         </div>
      </div>
   </div>
</section>
<?php require_once 'includes/footer.php'; ?>