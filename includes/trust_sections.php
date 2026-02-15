<?php
/**
 * Render Section 1: Why Choose Us (With Dynamic Rating)
 */
function renderTrustSection() {
    $title = getSetting('trust_title', 'Your Vision is Our Priority');
    $desc = getSetting('trust_desc', 'With over 12 years of experience ensuring day-to-day clarity for better living through our premium eyewear and exceptional service.');
    $points = explode("\n", getSetting('trust_points', ''));
    
    $rating = getAverageRating();
    $rating_score = $rating['score'] ?: '4.6';
    ?>
    <section class="section-dark">
        <div class="container">
            <div class="priority-grid">
                <!-- Text Content -->
                <div class="priority-content">
                    <span class="label-pill">WHY CHOOSE US</span>
                    <h2 class="fs-3rem text-white mb-3 mt-4"><?= htmlspecialchars($title) ?></h2>
                    <p class="lead mb-4" style="color: rgba(255, 255, 255, 0.7); font-size: 1rem; line-height: 1.6;">
                        <?= nl2br(htmlspecialchars($desc)) ?>
                    </p>
                    
                    <ul class="priority-list">
                        <?php foreach ($points as $point): if (empty(trim($point))) continue; ?>
                            <li class="inline-flex-start">
                                <i class="fa-solid fa-circle-check" style="color: var(--primary); font-size: 1.1rem;"></i>
                                <span style="color: white; font-weight: 500;"><?= htmlspecialchars(trim($point)) ?></span>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                </div>
                
                <!-- Image Content -->
                <div class="position-relative">
                    <div class="image-hero">
                        <img src="assets/uploads/happy_customer.jpg" alt="Happy Customer" style="width: 100%; height: 100%; object-fit: cover; border-radius: 24px;">
                        <div class="rating-badge animate-pulse-subtle">
                             <div class="rating-score"><?= $rating_score ?><span class="rating-max">/5</span></div>
                             <div class="rating-text-wrapper">
                                 <div class="rating-text">Google</div>
                                 <div class="rating-text">Rating</div>
                             </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
    <?php
}


