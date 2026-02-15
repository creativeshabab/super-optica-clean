<?php require_once 'includes/header.php'; ?>
<!-- Page Hero -->
<section class="page-hero">
   <div class="container">
      
      <?php renderBreadcrumbs([__('reviews') => null]); ?>
   </div>
</section>
<section class="web-wrapper section-padding bg-gray-50">
    <div class="container mx-auto px-4">

      <div class="text-left mb-12">
        <span class="text-primary font-bold uppercase tracking-widest text-sm"><?= __('testimonials') ?></span>
        <h2 class="text-4xl font-black text-gray-800 mt-2"><?= __('customer') ?> <span class="text-primary"><?= __('reviews') ?></span></h2>
    </div>

   <?php 
      $reviews = $pdo->query("SELECT * FROM reviews ORDER BY created_at DESC")->fetchAll();
      ?>
   <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
      <?php foreach ($reviews as $r): ?>
      <div class="review-card-premium h-full group p-2 bg-white border border-gray-100 rounded-2xl overflow-hidden transition-all duration-300 hover:shadow-xl">
          <div class="card-inner h-full flex flex-col p-8 relative">
              <i class="fa-solid fa-quote-right text-gray-100/50 text-6xl absolute top-6 right-6 -z-0 transition-transform duration-500 group-hover:scale-110 group-hover:rotate-12 group-hover:text-primary/10"></i>
              
              <div class="flex gap-1 mb-6 text-yellow-400">
                  <?php for($i=0; $i<$r['rating']; $i++) echo '<i class="fa-solid fa-star text-sm"></i>'; ?>
              </div>
              
              <p class="text-gray-600 mb-8 italic leading-relaxed relative z-10 flex-1 text-left">
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
<?php require_once 'includes/footer.php'; ?>