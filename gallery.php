<?php 
require_once 'config/db.php';
require_once 'includes/functions.php';

// Fetch gallery items
$stmt = $pdo->query("SELECT * FROM gallery ORDER BY display_order ASC, created_at DESC");
$gallery_items = $stmt->fetchAll();

require_once 'includes/header.php'; 
?>
<!-- Page Hero -->
<section class="page-hero">
   <div class="container">
      <?php renderBreadcrumbs([__('gallery') => null]); ?>
   </div>
</section>
<section class="web-wrapper section-padding">
    <div class="container mx-auto px-4">
        
        <!-- Standard Header (Matches Shop) -->
        <div class="flex flex-row justify-between items-center mb-10 gap-4">
           <div class="text-left m-0">
              <span class="text-primary font-bold uppercase tracking-widest text-sm"><?= __('our_gallery') ?></span>
              <h2 class="page-title-responsive font-black text-gray-800 mt-2"><?= __('visual') ?> <span class="text-primary">Experience</span></h2>
           </div>
        </div>
    <?php if (empty($gallery_items)): ?>
       <div class="text-center py-20 bg-gray-50 rounded-2xl border border-dashed border-gray-200">
          <i class="fa-solid fa-images text-6xl text-gray-300 mb-6"></i>
          <h3 class="text-2xl font-bold text-gray-700 mb-2">No Gallery Items Yet</h3>
          <p class="text-gray-500">Check back soon for our latest photos!</p>
       </div>
    <?php else: ?>
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
       <?php foreach ($gallery_items as $item): ?>
       <div class="group relative h-80 rounded-2xl overflow-hidden shadow-sm hover:shadow-xl transition-all cursor-pointer" onclick="openLightbox('<?= getBaseURL() ?>assets/uploads/<?= $item['image'] ?>', '<?= htmlspecialchars($item['title']) ?>')">
            <img src="<?= getBaseURL() ?>assets/uploads/<?= $item['image'] ?>" class="w-full h-full object-cover transform group-hover:scale-110 transition-transform duration-700" alt="<?= htmlspecialchars($item['title']) ?>">
            
            <?php if ($item['category']): ?>
            <div class="absolute top-4 right-4 bg-white/90 backdrop-blur-sm px-3 py-1 rounded-full text-xs font-bold uppercase tracking-wider text-primary shadow-sm z-10">
                <?= htmlspecialchars($item['category']) ?>
            </div>
            <?php endif; ?>
            
            <div class="absolute inset-0 bg-gradient-to-t from-black/80 via-black/20 to-transparent opacity-0 group-hover:opacity-100 transition-opacity duration-300 flex items-end p-6">
                <div class="transform translate-y-4 group-hover:translate-y-0 transition-transform duration-300 text-white">
                    <?php if ($item['category']): ?>
                    <span class="text-xs font-bold text-primary uppercase tracking-wider mb-1 block"><?= htmlspecialchars($item['category']) ?></span>
                    <?php endif; ?>
                    <h4 class="text-xl font-bold mb-2"><?= htmlspecialchars($item['title']) ?></h4>
                    <?php if ($item['description']): ?>
                    <p class="text-gray-300 text-sm line-clamp-2"><?= htmlspecialchars($item['description']) ?></p>
                    <?php endif; ?>
                </div>
            </div>
       </div>
       <?php endforeach; ?>
    </div>
    <?php endif; ?>
</div>
</section>

<!-- Lightbox Modal -->
<div id="lightbox" class="fixed inset-0 bg-black/95 z-50 flex items-center justify-center opacity-0 pointer-events-none transition-opacity duration-300" onclick="closeLightbox()">
   <button class="absolute top-6 right-6 text-white text-4xl hover:text-gray-300 transition-colors z-[60]" onclick="closeLightbox()">&times;</button>
   <img id="lightbox-img" src="" class="max-w-[90vw] max-h-[90vh] object-contain shadow-2xl rounded-lg transform scale-95 transition-transform duration-300">
   <div id="lightbox-caption" class="absolute bottom-6 left-0 right-0 text-center text-white text-lg font-medium tracking-wide drop-shadow-md px-4"></div>
</div>

<script>
function openLightbox(src, caption) {
   const lightbox = document.getElementById('lightbox');
   const img = document.getElementById('lightbox-img');
   
   img.src = src;
   document.getElementById('lightbox-caption').textContent = caption;
   
   lightbox.classList.remove('opacity-0', 'pointer-events-none');
   img.classList.remove('scale-95');
   img.classList.add('scale-100');
   
   document.body.style.overflow = 'hidden';
}

function closeLightbox() {
   const lightbox = document.getElementById('lightbox');
   const img = document.getElementById('lightbox-img');
   
   lightbox.classList.add('opacity-0', 'pointer-events-none');
   img.classList.remove('scale-100');
   img.classList.add('scale-95');
   
   document.body.style.overflow = 'auto';
}

// Close on Escape key
document.addEventListener('keydown', function(e) {
   if (e.key === 'Escape') closeLightbox();
});
</script>

<?php require_once 'includes/footer.php'; ?>
