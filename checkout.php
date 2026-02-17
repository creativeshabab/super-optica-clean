<?php
require_once 'config/db.php';
require_once 'includes/functions.php';

if (!isLoggedIn()) {
    setFlash('error', __('login_to_purchase'));
    redirect('login.php');
}

$cart = $_SESSION['cart'] ?? [];
if (empty($cart)) redirect('shop.php');

$total = getCartTotal();
$user_id = $_SESSION['user_id'];

// Fetch Wizard Setting
$enable_wizard = getSetting('enable_advanced_lens_wizard', 'off') === 'on';


// Fetch Active Coupons
$now = date('Y-m-d H:i:s');
$coupon_stmt = $pdo->prepare("SELECT * FROM coupons WHERE is_active = 1 AND (start_date IS NULL OR start_date <= ?) AND (end_date IS NULL OR end_date >= ?) ORDER BY id DESC");
$coupon_stmt->execute([$now, $now]);
$available_coupons = $coupon_stmt->fetchAll();

// Check for Applied Coupon
$coupon_id = null;
$coupon_code = '';
$coupon_discount = 0;

if (isset($_SESSION['applied_coupon'])) {
    $applied = $_SESSION['applied_coupon'];
    $coupon_id = $applied['id'];
    $coupon_code = $applied['code'];
    
    // Calculate initial discount for display
    if ($applied['type'] === 'percent') {
        $coupon_discount = ($total * $applied['value']) / 100;
    } else {
        $coupon_discount = $applied['value'];
    }

    // New: Check if the applied coupon is still valid (not expired)
    $check_stmt = $pdo->prepare("SELECT id FROM coupons WHERE id = ? AND is_active = 1 AND (start_date IS NULL OR start_date <= ?) AND (end_date IS NULL OR end_date >= ?)");
    $check_stmt->execute([$applied['id'], $now, $now]);
    if (!$check_stmt->fetch()) {
        unset($_SESSION['applied_coupon']);
        $coupon_id = null;
        $coupon_code = '';
        $coupon_discount = 0;
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!validateCSRFToken($_POST['csrf_token'] ?? '')) {
         setFlash('error', 'Invalid form submission (CSRF)');
         redirect('checkout.php');
         exit;
    }

    // Server-side Validation
    if (empty($_POST['customer_name']) || strlen(trim($_POST['customer_name'])) < 3) {
        setFlash('error', "Please enter a valid full name.");
        redirect('checkout.php');
        exit;
    } elseif (empty($_POST['phone']) || !preg_match('/^[0-9]{10}$/', $_POST['phone'])) {
        setFlash('error', "Please enter a valid 10-digit phone number.");
        redirect('checkout.php');
        exit;
    } elseif (empty($_POST['address_line1']) || strlen(trim($_POST['address_line1'])) < 5) {
        setFlash('error', "Please enter a valid address.");
        redirect('checkout.php');
        exit;
    } elseif (empty($_POST['pincode']) || !preg_match('/^[0-9]{6}$/', $_POST['pincode'])) {
        setFlash('error', "Please enter a valid 6-digit pincode.");
        redirect('checkout.php');
        exit;
    } elseif (empty($_POST['city'])) {
        setFlash('error', "City is required.");
        redirect('checkout.php');
        exit;
    } elseif (empty($_POST['state'])) {
        setFlash('error', "State is required.");
        redirect('checkout.php');
        exit;
    }

    $_SESSION['checkout_data'] = [
        'customer_name' => htmlspecialchars(trim($_POST['customer_name'])),
        'phone' => htmlspecialchars(trim($_POST['phone'])),
        'address' => htmlspecialchars(trim($_POST['address_line1'])) . ", " . htmlspecialchars(trim($_POST['city'])) . " - " . htmlspecialchars(trim($_POST['pincode'])) . ", " . htmlspecialchars(trim($_POST['state'])),
        'prescriptions' => [],
        'lens_total' => 0
    ];
    redirect('checkout_payment.php');
    exit;
}
?>

<?php require_once 'includes/header.php'; ?>

<div class="checkout-container">
    <div class="container mx-auto px-4">
        <!-- Stepper -->
        <div class="checkout-stepper">
            <div class="step active">
                <span class="step-number">01</span>
                <span class="step-label"><?= __('shipping') ?></span>
            </div>
            <div class="step">
                <span class="step-number">02</span>
                <span class="step-label">Payment</span>
            </div>
            <div class="step">
                <span class="step-number">03</span>
                <span class="step-label">Success</span>
            </div>
        </div>

        <div class="checkout-grid">
            <!-- Form Section -->
            <div class="space-y-6">
                
                    <form method="POST" id="checkoutForm" enctype="multipart/form-data">
                        <?= csrfField() ?>

                <!-- Shipping Card -->
                <div class="checkout-card">
                    <h3 class="checkout-card-title">
                        <i class="fa-solid fa-truck-fast"></i> 1. Enter Shipping Details
                    </h3>
                    <span class="checkout-section-desc">Where should we deliver your premium eyewear?</span>
                    
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
                        <div class="form-floating">
                            <label><?= __('full_name') ?></label>
                            <input type="text" name="customer_name" value="<?= htmlspecialchars($_SESSION['user_name'] ?? '') ?>" required placeholder="<?= __('full_name') ?>">
                        </div>
                        <div class="form-floating">
                            <label><?= __('contact_number') ?></label>
                            <input type="text" name="phone" required placeholder="<?= __('contact_number') ?>">
                        </div>
                    </div>

                    <div class="flex justify-between items-end mb-2">
                        <label class="checkout-section-header mb-0 border-none pb-0">Map Your Shipping Address</label>
                        <button type="button" onclick="detectCheckoutLocation()" class="btn-utility btn-detect">
                            <i class="fa-solid fa-location-crosshairs"></i> <?= __('detect_location') ?>
                        </button>
                    </div>
                    <span class="checkout-section-desc">Provide a detailed address or use 'Detect Location' for faster entry.</span>

                    <div class="form-floating mb-6">
                        <input type="text" name="address_line1" id="autocomplete_address" required placeholder="Flat / House No / Building / Street" autocomplete="off">
                        <div id="address_suggestions" class="suggestions-dropdown absolute w-full bg-white border rounded-xl shadow-2xl z-50 hidden mt-2 py-2 overflow-hidden"></div>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
                        <div class="form-floating">
                            <label><?= __('city') ?></label>
                            <input type="text" name="city" id="checkout_city" required placeholder="<?= __('city') ?>">
                        </div>
                        <div class="form-floating">
                            <label><?= __('pincode') ?></label>
                            <input type="text" name="pincode" id="checkout_pincode" required placeholder="<?= __('pincode') ?>">
                        </div>
                    </div>

                    <div class="form-floating mb-10">
                        <label><?= __('state') ?></label>
                        <input type="text" name="state" id="checkout_state" required placeholder="<?= __('state') ?>">
                    </div>

                    <div class="flex gap-4">
                        <a href="cart.php" class="btn btn-outline flex-1 py-4 flex items-center justify-center gap-2 no-margin">
                             <i class="fa-solid fa-chevron-left text-xs"></i> Back
                        </a>
                        <button type="submit" class="btn btn-primary flex-1 py-4 shadow-xl hover:shadow-primary/20 transition-all flex items-center justify-center gap-3">
                            <?= __('continue') ?> <i class="fa-solid fa-chevron-right text-xs"></i>
                        </button>
                    </div>
                </div>
            </div>

            <!-- Summary Section -->
            <div class="space-y-6 sticky top-6">
                <div class="checkout-card">
                    <h3 class="checkout-card-title">
                        <i class="fa-solid fa-receipt"></i> <?= __('order_summary') ?>
                    </h3>
                    
                    <div class="summary-list flex flex-col gap-6 mb-8">
                        <?php foreach ($cart as $id => $item): ?>
                        <div class="flex gap-4 group">
                            <div class="w-16 h-16 bg-white border border-gray-100 rounded-lg p-1.5 shrink-0 overflow-hidden flex items-center justify-center">
                                <?php if($item['image']): ?>
                                    <img src="assets/uploads/<?= $item['image'] ?>" class="w-full h-full object-contain">
                                <?php endif; ?>
                            </div>
                            <div class="flex-1 min-w-0">
                                <div class="flex justify-between items-start gap-2">
                                    <h5 class="font-extrabold text-sm text-accent truncate"><?= htmlspecialchars($item['name']) ?></h5>
                                    <span class="font-black text-accent text-sm whitespace-nowrap">₹<?= number_format($item['price'] * $item['quantity'], 2) ?></span>
                                </div>
                                <div class="flex justify-between items-center mt-2">
                                    <span class="text-[10px] font-black text-gray-400 uppercase tracking-widest"><?= __('quantity') ?>: <?= $item['quantity'] ?></span>
                                    <button type="button" onclick="removeFromCheckout('<?= $id ?>')" class="btn-remove-clean">
                                        <i class="fa-solid fa-trash-can"></i> <?= __('remove') ?>
                                    </button>
                                </div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>

                    <div class="coupon-section pt-10 border-t border-dashed border-gray-100 mb-8">
                        <label class="checkout-section-header">Redeem a Discount Coupon</label>
                        <span class="checkout-section-desc">Have a promo code? Enter it below to unlock exclusive savings.</span>
                        
                        <div id="couponInteractions">
                            <?php if (!empty($available_coupons)): ?>
                            <div class="offers-grid mt-6">
                                <?php foreach ($available_coupons as $coupon): 
                                    $is_applied = ($coupon_id == $coupon['id']);
                                ?>
                                <div class="offer-card <?= $is_applied ? 'active-offer' : '' ?>" 
                                     onclick="handleCouponClick('<?= htmlspecialchars($coupon['code']) ?>', <?= $is_applied ? 'true' : 'false' ?>)">
                                    
                                    <?php if ($is_applied): ?>
                                        <span class="offer-badge bg-success"><i class="fa-solid fa-check"></i> Applied</span>
                                    <?php else: ?>
                                        <span class="offer-badge">Click to apply</span>
                                    <?php endif; ?>
                                    
                                    <div class="offer-code"><?= htmlspecialchars($coupon['code']) ?></div>
                                    <div class="offer-desc"><?= htmlspecialchars($coupon['description'] ?: 'Special offer for you!') ?></div>
                                    <?php if ($coupon['is_prepaid_only']): ?>
                                        <div class="offer-tag">
                                            <i class="fa-solid fa-bolt"></i> Prepaid Only
                                        </div>
                                    <?php endif; ?>
                                </div>
                                <?php endforeach; ?>
                            </div>
                            <?php endif; ?>

                            <div class="flex gap-2 items-center">
                                <input type="text" id="couponCode" placeholder="Enter code" value="<?= htmlspecialchars($coupon_code) ?>" class="form-input text-sm font-black uppercase flex-1 h-11" <?= $coupon_id ? 'disabled' : '' ?>>
                                
                                <?php if ($coupon_id): ?>
                                    <button type="button" id="removeCouponBtn" onclick="removeCoupon()" class="btn btn-outline text-red-500 border-red-200 px-4 h-11">
                                        <i class="fa-solid fa-trash"></i>
                                    </button>
                                <?php else: ?>
                                    <button type="button" id="applyCoupon" class="btn btn-outline px-6 h-11"><?= __('apply') ?></button>
                                <?php endif; ?>
                            </div>
                        </div>
                        <div id="couponMessage" class="text-[10px] mt-2 font-bold"></div>
                        
                        <!-- Confirmation Modal (Hidden by default) -->
                        <div id="couponConfirmModal" class="fixed inset-0 bg-black/50 z-50 hidden flex items-center justify-center">
                            <div class="bg-white p-6 rounded-2xl shadow-2xl max-w-sm w-full mx-4">
                                <h3 class="text-lg font-bold mb-2">Switch Promo Code?</h3>
                                <p class="text-sm text-gray-600 mb-6">Replace current promo code <strong id="currentPromoCode" class="text-primary"></strong> with <strong id="newPromoCode" class="text-success"></strong>?</p>
                                <div class="flex justify-end gap-3">
                                    <button onclick="closeCouponModal()" class="btn btn-ghost text-gray-500">Cancel</button>
                                    <button id="confirmSwitchBtn" class="btn btn-primary">Replace</button>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="total-wrapper border-none pt-0 space-y-3">
                        <div class="flex justify-between text-sm font-bold text-gray-500 uppercase tracking-wider">
                            <span>Subtotal</span>
                            <span class="text-accent">₹<?= number_format($total, 2) ?></span>
                        </div>
                        <div id="discountRow" class="flex justify-between text-sm font-extrabold text-success uppercase tracking-wider <?= $coupon_discount > 0 ? '' : 'hidden' ?> transition-all">
                            <span>Discount</span>
                            <span>-₹<span id="discountAmount"><?= number_format($coupon_discount, 2) ?></span></span>
                        </div>
                        <div class="flex justify-between text-sm font-bold text-gray-500 uppercase tracking-wider">
                            <span>Shipping</span>
                            <span class="text-success font-black">FREE</span>
                        </div>
                        
                        <div class="flex justify-between items-baseline pt-6 mt-4 border-t border-gray-100">
                            <span class="text-xl font-black text-accent uppercase tracking-tighter italic">Total Pay</span>
                            <?php $final_total = max(0, $total - $coupon_discount); ?>
                            <span class="text-3xl font-black text-primary tracking-tighter">₹<span id="finalTotal"><?= number_format($final_total, 2) ?></span></span>
                        </div>
                    </div>

                    <div class="mt-8 bg-red-50/50 p-5 rounded-2xl border border-red-100 flex gap-4 items-start">
                        <i class="fa-solid fa-shield-halved text-primary text-lg mt-1"></i>
                        <p class="text-[10px] text-red-900 leading-normal font-medium">
                            <strong class="uppercase tracking-widest block mb-1">Secure Checkout Guaranteed</strong>
                            Your details are protected with enterprise-grade encryption.
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>


function handleLensChange(radio) {
    updatePageTotal();
}

function updatePageTotal() {
    const displayFinalTotal = document.getElementById('finalTotal');
    const baseAmount = <?= $total ?>; 
    
    // 3. Get current discount
    let discountVal = 0;
    if (typeof currentDiscount !== 'undefined') {
        discountVal = currentDiscount;
    } else {
        const discountEl = document.getElementById('discountAmount');
        if(discountEl) {
             discountVal = parseFloat(discountEl.innerText.replace(/[^\d.]/g, '')) || 0;
        }
    }
    
    const finalCalculated = Math.max(0, baseAmount - discountVal);
    if (displayFinalTotal) displayFinalTotal.innerText = finalCalculated.toLocaleString('en-IN', {minimumFractionDigits: 2});
}

function switchRxTab(btn, id, idx) {
    // Tab switching rolled back
}

function validateCheckoutForm() {
    let isValid = true;
    const errorMsg = (msg) => {
        // You could implement a toast here, or just alert for now as per "Improve error messages"
        alert(msg);
        return false;
    };

    const name = document.querySelector('input[name="customer_name"]').value.trim();
    if (name.length < 3) return errorMsg("Please enter a valid full name.");

    const phone = document.querySelector('input[name="phone"]').value.trim();
    if (!/^[0-9]{10}$/.test(phone)) return errorMsg("Please enter a valid 10-digit phone number.");

    const address = document.querySelector('input[name="address_line1"]').value.trim();
    if (address.length < 5) return errorMsg("Please enter a valid address.");

    const pincode = document.querySelector('input[name="pincode"]').value.trim();
    if (!/^[0-9]{6}$/.test(pincode)) return errorMsg("Please enter a valid 6-digit pincode.");

    const city = document.querySelector('input[name="city"]').value.trim();
    if (!city) return errorMsg("City is required.");

    const state = document.querySelector('input[name="state"]').value.trim();
    if (!state) return errorMsg("State is required.");

    return true;
}

// Power range modifiers rolled back
window.lensData = [];
window.priceModifiers = [];

function handleSyncToggle(checkbox) {
    // Sync rolled back
}

function copyFromFirstUnit(id) {
    // Copy unit rolled back
}

document.addEventListener('DOMContentLoaded', () => {
    // Prescription cleanup
});

</script>
        </div>
    </div>
</div>

<script>
// Coupon Logic
let currentAppliedCoupon = '<?= $coupon_code ?>'; 
let currentDiscount = <?= $coupon_discount ?>;

function handleCouponClick(code, isApplied) {
    if (isApplied) return;
    applyPromoCode(code);
}

function removeCoupon() {
    const btn = document.getElementById('removeCouponBtn');
    if(btn) btn.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i>';

    fetch('api/remove_coupon.php', { method: 'POST' })
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                currentAppliedCoupon = '';
                currentDiscount = 0;
                updateCouponUI(false, null);
                updatePageTotal();
                
                // Reset Input
                const input = document.getElementById('couponCode');
                if(input) {
                    input.value = '';
                    input.disabled = false;
                }
            }
        });
}

function applyPromoCode(code) {
    const input = document.getElementById('couponCode');
    if(input) input.value = code;
    
    // UI Loading
    const applyBtn = document.getElementById('applyCoupon');
    const removeBtn = document.getElementById('removeCouponBtn');
    
    if(applyBtn) {
        applyBtn.disabled = true;
        applyBtn.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i>';
    } else if(removeBtn) {
        removeBtn.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i>';
    }

    submitCoupon(code);
}

function submitCoupon(code) {
    const msg = document.getElementById('couponMessage');
    if(msg) {
        msg.className = 'text-[10px] mt-2 font-bold';
        msg.innerText = ''; 
    }

    const formData = new FormData();
    formData.append('code', code);

    fetch('api/apply_coupon.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        const applyBtn = document.getElementById('applyCoupon');
        if(applyBtn) {
            applyBtn.disabled = false;
            applyBtn.innerText = 'APPLY';
        }

        if (data.success) {
            currentAppliedCoupon = code;
            currentDiscount = data.discount;
            updateCouponUI(true, code);
            updatePageTotal();
            
            // Show Success Message
            if(msg) {
                msg.className = 'mt-2 coupon-success-msg';
                msg.innerHTML = `
                    <div class="success-title"><i class="fa-solid fa-circle-check"></i> <span>${data.message}</span></div>
                `;
                if (data.coupon_details && data.coupon_details.is_prepaid_only) {
                    msg.innerHTML += `<div class="success-subtitle"><i class="fa-solid fa-circle-info"></i> <span>Applicable on Prepaid only</span></div>`;
                }
            }
            
            // Disable Input
            const input = document.getElementById('couponCode');
            if(input) input.disabled = true;

        } else {
            // Restore Remove Button if it was a switch attempt
            const removeBtn = document.getElementById('removeCouponBtn');
            if(removeBtn) removeBtn.innerHTML = '<i class="fa-solid fa-trash"></i>';

            if(msg) {
                msg.className = 'text-[10px] mt-2 font-bold text-red-500';
                msg.innerText = data.message;
            }
        }
    })
    .catch(error => {
         const applyBtn = document.getElementById('applyCoupon');
         if(applyBtn) {
            applyBtn.disabled = false;
            applyBtn.innerText = 'APPLY';
         }
         const removeBtn = document.getElementById('removeCouponBtn');
         if(removeBtn) removeBtn.innerHTML = '<i class="fa-solid fa-trash"></i>';
         
         console.error(error);
         if(msg) msg.innerText = 'System Error';
    });
}

function updateCouponUI(isApplied, code) {
    // 1. Update Offer Cards
    document.querySelectorAll('.offer-card').forEach(card => {
        const cardCode = card.querySelector('.offer-code').innerText;
        const badge = card.querySelector('.offer-badge');
        
        if (isApplied && cardCode === code) {
            card.classList.add('active-offer');
            card.setAttribute('onclick', `handleCouponClick('${cardCode}', true)`);
            if(badge) {
                badge.className = 'offer-badge bg-success';
                badge.innerHTML = '<i class="fa-solid fa-check"></i> Applied';
            }
        } else {
            card.classList.remove('active-offer');
            card.setAttribute('onclick', `handleCouponClick('${cardCode}', false)`);
            if(badge) {
                badge.className = 'offer-badge';
                badge.innerText = 'Click to apply';
            }
        }
    });

    // 2. Update Input Area Buttons (Swap Apply/Remove)
    // We need to rebuild the button html or toggle visibility. 
    // Since we don't have a container for just buttons, let's assume we might need to reload if structure changes too much
    // BUT for better UX, let's swap them manually if they exist, or reload if complex. 
    // Actually, simply reloading purely for button swap state is easiest if we don't want complex DOM manips, 
    // but user wants NO RELOAD. So we utilize a container replacement.
    
    // Easier approach: If applied, show remove button. If not, show apply.
    // We can interact with parent of applyCoupon/removeCouponBtn
    
    const inputContainer = document.getElementById('couponCode').parentNode; // The flex container
    const existingRemove = document.getElementById('removeCouponBtn');
    const existingApply = document.getElementById('applyCoupon');
    
    if (isApplied) {
        if(existingApply) existingApply.remove();
        if(!existingRemove) {
            const btn = document.createElement('button');
            btn.type = 'button';
            btn.id = 'removeCouponBtn';
            btn.onclick = removeCoupon;
            btn.className = 'btn btn-outline text-red-500 border-red-200 hover:bg-red-50 text-[10px] font-black uppercase px-4 h-11 tracking-widest';
            btn.innerHTML = '<i class="fa-solid fa-trash"></i>';
            inputContainer.appendChild(btn);
        }
    } else {
        if(existingRemove) existingRemove.remove();
        if(!existingApply) {
             const btn = document.createElement('button');
             btn.type = 'button';
             btn.id = 'applyCoupon';
             btn.className = 'btn btn-outline text-[10px] font-black uppercase px-6 h-11 tracking-widest';
             btn.innerText = 'APPLY';
             
             // Re-attach listener mechanism or just use onclick inline
             // Since we use addEventListener below, we need to be careful. 
             // Let's use inline for simplicity OR re-bind. 
             // Updating HTML is risky for listeners. 
             // Let's just create element and bind.
             btn.addEventListener('click', function() {
                const code = document.getElementById('couponCode').value;
                if (!code) {
                    const msg = document.getElementById('couponMessage');
                    if(msg) msg.innerText = 'Please enter a code';
                    return;
                }
                submitCoupon(code);
             });
             
             inputContainer.appendChild(btn);
        }
    }
}

// Autocomplete Logic (Nominatim)
const addressInput = document.getElementById('autocomplete_address');
const suggestionsBox = document.getElementById('address_suggestions');
let debounceTimer;

addressInput?.addEventListener('input', function() {
    clearTimeout(debounceTimer);
    const query = this.value;
    
    if (query.length < 3) {
        suggestionsBox.classList.add('hidden');
        return;
    }

    debounceTimer = setTimeout(() => {
        fetch(`https://nominatim.openstreetmap.org/search?format=json&q=${encodeURIComponent(query)}&countrycodes=in&limit=5`)
            .then(res => res.json())
            .then(data => {
                suggestionsBox.innerHTML = '';
                if (data.length > 0) {
                    suggestionsBox.classList.remove('hidden');
                    data.forEach(place => {
                        const div = document.createElement('div');
                        div.className = 'p-3 hover:bg-gray-50 cursor-pointer text-sm border-b last:border-0 font-medium';
                        div.textContent = place.display_name;
                        div.onclick = () => {
                            addressInput.value = place.display_name.split(',')[0]; 
                            const parts = place.display_name.split(',');
                            const len = parts.length;
                            if (len > 1) {
                                document.getElementById('checkout_city').value = parts[len - 4]?.trim() || ''; 
                                document.getElementById('checkout_state').value = parts[len - 2]?.trim() || '';
                            }
                            suggestionsBox.classList.add('hidden');
                        };
                        suggestionsBox.appendChild(div);
                    });
                } else {
                    suggestionsBox.classList.add('hidden');
                }
            });
    }, 500);
});

document.addEventListener('click', (e) => {
    if (e.target !== addressInput && e.target !== suggestionsBox) {
        suggestionsBox?.classList.add('hidden');
    }
});

// Geolocation
function detectCheckoutLocation() {
    const btn = document.querySelector('.btn-detect');
    if (!navigator.geolocation) { alert("Geolocation not supported"); return; }
    
    btn.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Locating...';
    btn.disabled = true;
    
    navigator.geolocation.getCurrentPosition(pos => {
        fetch(`https://api.bigdatacloud.net/data/reverse-geocode-client?latitude=${pos.coords.latitude}&longitude=${pos.coords.longitude}&localityLanguage=en`)
        .then(r => r.json())
        .then(data => {
             document.getElementById('checkout_city').value = data.city || data.locality || "";
             document.getElementById('checkout_pincode').value = data.postcode || "";
             document.getElementById('checkout_state').value = data.principalSubdivision || "";
             document.getElementById('autocomplete_address').value = data.locality || "";
             btn.innerHTML = '<i class="fa-solid fa-check"></i> Found';
             btn.disabled = false;
        })
        .catch(() => {
            btn.innerHTML = 'Error';
            btn.disabled = false;
        });
    }, () => { 
        btn.innerHTML = 'Error'; 
        btn.disabled = false; 
    });
}
</script>
</form>
<?php require_once 'includes/footer.php'; ?>

