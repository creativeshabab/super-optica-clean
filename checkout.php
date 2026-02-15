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

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $_SESSION['checkout_data'] = [
        'customer_name' => $_POST['customer_name'],
        'phone' => $_POST['phone'],
        'address' => $_POST['address_line1'] . ", " . $_POST['city'] . " - " . $_POST['pincode'] . ", " . $_POST['state']
    ];
    redirect('checkout_payment.php');
    exit;
}
?>

<?php require_once 'includes/header.php'; ?>

<div class="checkout-container">
    <div class="container mx-auto px-4">
        <!-- Stepper -->
        <div class="checkout-stepper flex justify-between items-center mb-5">
            <div class="step active flex flex-col items-center">
                <span class="step-number bg-primary text-white rounded-full w-8 h-8 flex items-center justify-center font-bold">01</span>
                <span class="text-sm mt-1 font-medium"><?= __('shipping') ?></span>
            </div>
            <div class="step-line flex-1 h-1 bg-gray-200 mx-2"></div>
            <div class="step flex flex-col items-center opacity-50">
                <span class="step-number bg-gray-200 text-gray-500 rounded-full w-8 h-8 flex items-center justify-center font-bold">02</span>
                <span class="text-sm mt-1 font-medium"><?= __('payment') ?></span>
            </div>
            <div class="step-line flex-1 h-1 bg-gray-200 mx-2"></div>
            <div class="step flex flex-col items-center opacity-50">
                <span class="step-number bg-gray-200 text-gray-500 rounded-full w-8 h-8 flex items-center justify-center font-bold">03</span>
                <span class="text-sm mt-1 font-medium"><?= __('success') ?></span>
            </div>
        </div>

        <div class="checkout-grid grid grid-cols-1 md:grid-cols-3 gap-6">
            <!-- Form Section -->
            <div class="checkout-card card md:col-span-2">
                <h3 class="checkout-card-title text-xl font-bold mb-4 flex items-center gap-2">
                    <i class="fa-solid fa-truck-fast text-primary"></i> <?= __('shipping_details') ?>
                </h3>
                
                <form method="POST">
                    <div class="form-group mb-4">
                        <label class="form-label"><?= __('full_name') ?></label>
                        <input type="text" name="customer_name" class="form-input" value="<?= htmlspecialchars($_SESSION['user_name'] ?? '') ?>" required placeholder="<?= __('full_name') ?>">
                    </div>
                    
                    <div class="form-group mb-4">
                        <label class="form-label"><?= __('contact_number') ?></label>
                        <input type="text" name="phone" class="form-input" required placeholder="<?= __('contact_number') ?>">
                    </div>

                    <!-- Address Section -->
                    <div class="address-header-wrapper flex flex-col md:flex-row justify-between items-start md:items-center mb-2 mt-6 gap-3">
                        <label class="font-bold"><?= __('detailed_address') ?></label>
                        <button type="button" onclick="detectCheckoutLocation()" class="btn btn-outline btn-sm flex items-center gap-2 btn-detect w-full md:w-auto justify-center">
                            <i class="fa-solid fa-location-crosshairs"></i> <?= __('detect_location') ?>
                        </button>
                    </div>

                    <div class="form-group mb-4 relative">
                        <label class="form-label text-sm text-muted"><?= __('address_placeholder_detailed') ?></label>
                        <input type="text" name="address_line1" id="autocomplete_address" class="form-input" required placeholder="<?= __('search_area') ?>" autocomplete="off">
                        <div id="address_suggestions" class="suggestions-dropdown absolute w-full bg-white border rounded-md shadow-lg z-50 hidden"></div>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                        <div class="form-group">
                            <label class="form-label text-sm text-muted"><?= __('city') ?></label>
                            <input type="text" name="city" id="checkout_city" class="form-input" required placeholder="<?= __('city') ?>">
                        </div>
                        <div class="form-group">
                            <label class="form-label text-sm text-muted"><?= __('pincode') ?></label>
                            <input type="text" name="pincode" id="checkout_pincode" class="form-input" required placeholder="<?= __('pincode') ?>">
                        </div>
                    </div>

                    <div class="form-group mb-6">
                        <label class="form-label text-sm text-muted"><?= __('state') ?></label>
                        <input type="text" name="state" id="checkout_state" class="form-input" required placeholder="<?= __('state') ?>">
                    </div>

                    <button type="submit" class="btn btn-primary w-full md:w-auto flex items-center justify-center gap-2 py-3 px-6 text-lg">
                        <?= __('continue') ?> <i class="fa-solid fa-arrow-right"></i>
                    </button>
                </form>
            </div>

            <!-- Summary Section -->
            <div class="checkout-card card h-fit sticky top-4">
                <h3 class="checkout-card-title text-xl font-bold mb-4 flex items-center gap-2">
                    <i class="fa-solid fa-receipt text-primary"></i> <?= __('order_summary') ?>
                </h3>
                
                <div class="summary-list flex flex-col gap-4 mb-6">
                    <?php foreach ($cart as $item): ?>
                    <div class="summary-item flex justify-between items-center pb-4 border-b border-gray-100 last:border-0">
                        <div class="flex gap-3 items-center">
                            <?php if($item['image']): ?>
                                <img src="assets/uploads/<?= $item['image'] ?>" class="w-16 h-16 object-cover rounded-md flex-shrink-0 border border-gray-200">
                            <?php endif; ?>
                            <div>
                                <div class="font-bold text-sm"><?= htmlspecialchars($item['name']) ?></div>
                                <div class="text-xs text-muted"><?= __('quantity') ?>: <?= $item['quantity'] ?></div>
                            </div>
                        </div>
                        <span class="font-bold text-primary">₹<?= number_format($item['price'] * $item['quantity'], 2) ?></span>
                    </div>
                    <?php endforeach; ?>
                </div>

                <!-- Coupon Section -->
                <div class="coupon-section pt-4 border-t border-dashed border-gray-300 mb-6">
                    <label class="text-xs font-bold text-muted mb-2 block"><?= __('have_coupon') ?></label>
                    <div class="flex flex-col md:flex-row gap-2">
                        <input type="text" id="couponCode" placeholder="<?= __('enter_code') ?>" class="form-input text-sm uppercase w-full md:flex-1 h-10 md:h-auto">
                        <button type="button" id="applyCoupon" class="btn btn-outline text-xs font-bold px-4 py-2 w-fit md:w-auto self-end md:self-auto h-10 md:h-auto"><?= __('apply') ?></button>
                    </div>
                    <div id="couponMessage" class="text-xs mt-2 font-semibold"></div>
                </div>


                <div class="total-wrapper space-y-2">
                    <div class="flex justify-between text-sm">
                        <span><?= __('subtotal') ?></span>
                        <span class="font-bold">₹<?= number_format($total, 2) ?></span>
                    </div>
                    <div id="discountRow" class="flex justify-between text-sm hidden text-success">
                        <span><?= __('discount') ?></span>
                        <span>-₹<span id="discountAmount">0.00</span></span>
                    </div>
                    <div class="flex justify-between text-sm">
                        <span><?= __('shipping') ?></span>
                        <span class="text-success font-bold"><?= __('free') ?></span>
                    </div>
                    <div class="flex justify-between text-lg font-bold border-t pt-2 mt-2">
                        <span><?= __('total_pay') ?></span>
                        <span class="text-primary">₹<span id="finalTotal"><?= number_format($total, 2) ?></span></span>
                    </div>
                </div>


                <div class="mt-6 bg-red-50 p-4 rounded-lg border border-red-100 flex gap-3 items-start">
                    <i class="fa-solid fa-shield-halved text-primary mt-1"></i>
                    <p class="text-xs text-red-800 leading-snug">
                        <strong><?= __('secure_checkout') ?></strong><br>
                        <?= __('secure_checkout_desc') ?>
                    </p>
                </div>
            </div>
        </div>
    </div>
</div>

</script>

<script>
// Coupon Logic
document.getElementById('applyCoupon').addEventListener('click', function() {
    const code = document.getElementById('couponCode').value;
    const msg = document.getElementById('couponMessage');
    const btn = this;

    if (!code) {
        msg.classList.add('text-error');
        msg.innerText = 'Please enter a code.';
        return;
    }

    btn.disabled = true;
    btn.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i>';

    const formData = new FormData();
    formData.append('code', code);

    fetch('api/apply_coupon.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        btn.disabled = false;
        btn.innerText = 'Apply';

        if (data.success) {
            msg.classList.add('text-success');
            msg.innerText = data.message;
            
            // Update Totals
            document.getElementById('discountRow').classList.remove('hidden');
            document.getElementById('discountAmount').innerText = data.discount.toFixed(2);
            document.getElementById('finalTotal').innerText = data.new_total.toLocaleString('en-IN', {minimumFractionDigits: 2});
            
            if (data.coupon_details.is_prepaid_only) {
                msg.innerHTML += `<br><span class="note-prepaid">* <?= __('prepaid_only_note') ?></span>`;
            }
        } else {
            msg.classList.add('text-error');
            msg.innerText = data.message;
        }
    })
    .catch(error => {
        btn.disabled = false;
        btn.innerText = 'Apply';
        console.error('Error:', error);
    });
});

// Autocomplete Logic (Nominatim)
const addressInput = document.getElementById('autocomplete_address');
const suggestionsBox = document.getElementById('address_suggestions');
let debounceTimer;

addressInput.addEventListener('input', function() {
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
                        div.className = 'suggestion-item';
                        div.textContent = place.display_name;
                        div.onclick = () => {
                            addressInput.value = place.display_name.split(',')[0]; // First part as line 1
                            
                            // Try to parse basic details
                            const parts = place.display_name.split(',');
                            const len = parts.length;
                            if (len > 1) document.getElementById('checkout_city').value = parts[len - 4]?.trim() || ''; 
                            if (len > 1) document.getElementById('checkout_state').value = parts[len - 2]?.trim() || '';
                            // Pincode usually isn't in display_name clearly, leave manually
                             
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

// Close suggestions on click outside
document.addEventListener('click', function(e) {
    if (e.target !== addressInput && e.target !== suggestionsBox) {
        suggestionsBox.classList.add('hidden');
    }
});

// Geolocation Logic (BigDataCloud)
function detectCheckoutLocation() {
    const btn = document.querySelector('.btn-detect');
    const originalText = btn.innerHTML;
    
    if (!navigator.geolocation) {
        alert("Geolocation is not supported by your browser");
        return;
    }

    btn.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> <?= __('locating') ?>';
    btn.disabled = true;

    navigator.geolocation.getCurrentPosition(
        (position) => {
            const lat = position.coords.latitude;
            const lon = position.coords.longitude;
            
            fetch(`https://api.bigdatacloud.net/data/reverse-geocode-client?latitude=${lat}&longitude=${lon}&localityLanguage=en`)
            .then(response => response.json())
            .then(data => {
                // Populate fields
                let city = data.city || data.locality || data.principalSubdivision || "";
                let postcode = data.postcode || "";
                let state = data.principalSubdivision || "";
                
                document.getElementById('checkout_city').value = city;
                document.getElementById('checkout_pincode').value = postcode;
                document.getElementById('checkout_state').value = state;
                document.getElementById('autocomplete_address').value = data.locality || "";
                
                btn.innerHTML = '<i class="fa-solid fa-check"></i> <?= __('found') ?>';
                setTimeout(() => {
                    btn.innerHTML = originalText;
                    btn.disabled = false;
                }, 2000);
            })
            .catch(error => {
                console.error("Geocoding error:", error);
                btn.innerHTML = '<?= __('error') ?>';
                btn.disabled = false;
            });
        },
        (error) => {
            btn.innerHTML = '<?= __('permission_denied') ?>';
            btn.disabled = false;
        }
    );
}
</script>

<?php require_once 'includes/footer.php'; ?>

