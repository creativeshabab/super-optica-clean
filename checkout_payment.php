<?php
require_once 'config/db.php';
require_once 'includes/functions.php';

if (!isLoggedIn()) redirect('login.php');
if (empty($_SESSION['cart'])) redirect('shop.php');
if (empty($_SESSION['checkout_data'])) redirect('checkout.php');
// 1. Initial State
$checkout = $_SESSION['checkout_data'];
$cart = $_SESSION['cart'] ?? [];
$lens_total = $checkout['lens_total'] ?? 0;
$subtotal = getCartTotal() + $lens_total;
$discount = 0;
$coupon_id = null;
$coupon_code = '';
$is_prepaid_only = false;
$coupon_type = '';
$coupon_value = 0;

// 2. Check for Manual Coupon First
if (isset($_SESSION['applied_coupon'])) {
    $applied = $_SESSION['applied_coupon'];
    $coupon_id = $applied['id'];
    $coupon_code = $applied['code'];
    $coupon_type = $applied['type'];
    $coupon_value = $applied['value'];
    $is_prepaid_only = $applied['is_prepaid_only'];
} else {
    // 3. Fallback to Automatic Prepaid Offer
    $offer_stmt = $pdo->prepare("SELECT * FROM coupons WHERE is_active = 1 AND is_prepaid_only = 1 AND (start_date IS NULL OR start_date <= NOW()) AND (end_date IS NULL OR end_date >= NOW()) AND min_order_amount <= ? LIMIT 1");
    $offer_stmt->execute([$subtotal]);
    $active_offer = $offer_stmt->fetch();
    
    if ($active_offer) {
        $coupon_id = $active_offer['id'];
        $coupon_code = $active_offer['code'];
        $coupon_type = $active_offer['type'];
        $coupon_value = $active_offer['value'];
        $is_prepaid_only = true;
    }
}

// 4. Calculate Potential Discount (Actual application depends on payment method)
function calculateDiscountValue($subtotal, $type, $value) {
    if ($type === 'percent') {
        return ($subtotal * $value) / 100;
    }
    return $value;
}

$potential_discount = calculateDiscountValue($subtotal, $coupon_type, $coupon_value);
$total = $subtotal; // Default before payment method is finalized



if (isset($_POST['confirm_order'])) {
    $payment_method = $_POST['payment_method'] ?? 'cod';
    $user_id = $_SESSION['user_id'];
    $order_number = 'ORD-' . strtoupper(uniqid());
    
    try {
        $pdo->beginTransaction();

        // Final Discount Calculation
        $final_discount = 0;
        $final_coupon_id = null;
        
        if ($coupon_id) {
            // VALIDATION: Check if coupon actually exists to prevent Foreign Key Error
            $chk_coupon = $pdo->prepare("SELECT id FROM coupons WHERE id = ?");
            $chk_coupon->execute([$coupon_id]);
            if ($chk_coupon->fetch()) {
                 // Apply if: It's not prepaid-only OR payment is not COD
                if (!$is_prepaid_only || $payment_method !== 'cod') {
                    $final_discount = $potential_discount;
                    $final_coupon_id = $coupon_id;
                }
            } else {
                // Coupon ID in session is invalid/deleted
                $coupon_id = null; 
            }
        }
        
        $total = max(0, $subtotal - $final_discount);

        // Create Order - Include order_number, payment_method and coupon_id
        $stmt = $pdo->prepare("INSERT INTO orders (order_number, user_id, customer_name, phone, total_amount, address, status, payment_method, coupon_id) VALUES (?, ?, ?, ?, ?, ?, 'pending', ?, ?)");
        $stmt->execute([$order_number, $user_id, $checkout['customer_name'], $checkout['phone'], $total, $checkout['address'], $payment_method, $final_coupon_id]);

        $order_id = $pdo->lastInsertId();

        // Create Order Items & Reduce Stock
        foreach ($cart as $item) {
            // Fetch product details for SKU and Name consistency
            $p_stmt = $pdo->prepare("SELECT name, sku, stock_quantity FROM products WHERE id = ?");
            $p_stmt->execute([$item['id']]);
            $product = $p_stmt->fetch();
            
            $stmt = $pdo->prepare("INSERT INTO order_items (order_id, product_id, product_name, sku, quantity, price) VALUES (?, ?, ?, ?, ?, ?)");
            $stmt->execute([$order_id, $item['id'], $product['name'] ?? $item['name'], $product['sku'] ?? '', $item['quantity'], $item['price']]);
            
            // Reduce stock
            if (isset($product['stock_quantity']) && $product['stock_quantity'] !== null) {
                $new_stock = max(0, $product['stock_quantity'] - $item['quantity']);
                $update_stock = $pdo->prepare("UPDATE products SET stock_quantity = ? WHERE id = ?");
                $update_stock->execute([$new_stock, $item['id']]);
                
                checkStockAndNotify($item['id']);
            }
        }

        // Save Prescriptions (Nested Split)
        if (!empty($checkout['prescriptions'])) {
            $rx_stmt = $pdo->prepare("INSERT INTO order_prescriptions (order_id, product_id, lens_option_id, od_sph, od_cyl, od_axis, od_add, os_sph, os_cyl, os_axis, os_add, pd, prescription_file) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
            
            foreach ($checkout['prescriptions'] as $prod_id => $units) {
                foreach ($units as $idx => $rx) {
                    $rx_stmt->execute([
                        $order_id,
                        $prod_id,
                        $rx['lens_option_id'],
                        $rx['od_sph'], $rx['od_cyl'], $rx['od_axis'], $rx['od_add'],
                        $rx['os_sph'], $rx['os_cyl'], $rx['os_axis'], $rx['os_add'],
                        $rx['pd'],
                        $rx['file'] ?? null
                    ]);
                }
            }
        }

        $pdo->commit();
        
        // Create admin notification
        createAdminNotification(
            'new_order',
            '🛒 New Order Received!',
            'Order ' . $order_number . ' from ' . $checkout['customer_name'] . ' (₹' . number_format($total, 2) . ')',
            $order_id
        );

        // Send Confirmation Emails (Silent Failure to not block success page)
        try {
            require_once 'includes/order_templates.php';
            $user_email = $_SESSION['user_email'] ?? '';
            if (!$user_email) {
                $u_stmt = $pdo->prepare("SELECT email FROM users WHERE id = ?");
                $u_stmt->execute([$user_id]);
                $user_email = $u_stmt->fetchColumn();
            }
            if ($user_email) {
                $customer_subject = "Order Confirmed - #$order_number";
                $customer_body = getOrderEmailTemplate($checkout['customer_name'], $order_id, $cart, $total, $checkout['address']);
                sendEmail($user_email, $customer_subject, $customer_body);
            }
            $admin_email = getSetting('contact_email', 'shabab@superoptical.in'); 
            $admin_subject = "🚨 New Order Received: #$order_number";
            $admin_body = getAdminOrderAlertTemplate($checkout['customer_name'], $order_id, $total);
            sendEmail($admin_email, $admin_subject, $admin_body);
        } catch (Throwable $e) {
            error_log("Order email failed: " . $e->getMessage());
        }

        // Clear Cart and Checkout Session
        unset($_SESSION['cart']);
        unset($_SESSION['checkout_data']);
        unset($_SESSION['applied_coupon']); // Clear applied coupon
        
        redirect('order_status.php?id=' . $order_id . '&status=success');
        exit;

    } catch (Throwable $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        $error = "Order failed: " . $e->getMessage();
        error_log("Checkout Error: " . $e->getMessage() . "\n" . $e->getTraceAsString());
    }
}
?>

<?php require_once 'includes/header.php'; ?>

<div class="checkout-container">
    <div class="container">
        <!-- Stepper -->
        <div class="checkout-stepper">
            <div class="step completed">
                <span class="step-number"><i class="fa-solid fa-check"></i></span>
                <span><?= __('shipping') ?></span>
            </div>
            <div class="step-line bg-success"></div>
            <div class="step active">
                <span class="step-number">02</span>
                <span><?= __('payment') ?></span>
            </div>
            <div class="step-line"></div>
            <div class="step">
                <span class="step-number">03</span>
                <span><?= __('success') ?></span>
            </div>
        </div>

        <?php if (isset($error)): ?>
            <div class="alert--error mb-8">
                <i class="fa-solid fa-circle-exclamation"></i> <?= htmlspecialchars($error) ?>
            </div>
        <?php endif; ?>

        <div class="checkout-grid">
            <!-- Payment Section -->
            <div class="checkout-card">
                <h3 class="checkout-card-title">
                    <i class="fa-solid fa-credit-card"></i> <?= __('payment_method') ?>
                </h3>
                
                <form method="POST" id="paymentForm">
                    <input type="hidden" name="confirm_order" value="1">
                    <div class="flex flex-col gap-4 mb-8">
                        <?php
                        // Fetch enabled gateways
                        require_once 'includes/IntegrationManager.php';
                        $gateways = IntegrationManager::getInstance($pdo)->getEnabledPaymentGateways();
                        $first = true;
                        
                        foreach ($gateways as $gateway):
                            $value = $gateway['service_name'];
                            $checked = $first ? 'checked' : '';
                            $first = false;
                        ?>
                            <?php if ($gateway['service_name'] === 'cod'): ?>
                                <label class="payment-option-refined">
                                    <input type="radio" name="payment_method" value="cod" <?= $checked ?>>
                                    <div class="payment-icon"><i class="fa-solid fa-hand-holding-dollar"></i></div>
                                    <div class="payment-details">
                                        <strong><?= htmlspecialchars($gateway['display_name']) ?></strong>
                                        <span><?= __('cod_desc') ?></span>
                                    </div>
                                </label>
                            <?php elseif ($gateway['service_name'] === 'razorpay'): ?>
                                <label class="payment-option-refined">
                                    <input type="radio" name="payment_method" value="razorpay" <?= $checked ?>>
                                    <div class="payment-icon text-blue-400"><i class="fa-solid fa-wallet"></i></div>
                                    <div class="payment-details">
                                        <strong><?= htmlspecialchars($gateway['display_name']) ?></strong>
                                        <span>Cards, UPI, Net Banking & Wallets</span>
                                    </div>
                                </label>
                            <?php endif; ?>
                        <?php endforeach; ?>
                        
                        <?php if (empty($gateways)): ?>
                            <div class="alert alert-warning">
                                <i class="fa-solid fa-triangle-exclamation"></i> No payment methods available. Please contact support.
                            </div>
                        <?php endif; ?>
                    </div>

                    <button type="submit" name="confirm_order" id="placeOrderBtn" class="btn btn-checkout" <?= empty($gateways) ? 'disabled' : '' ?>>
                        <?= __('place_order') ?> (₹<?= number_format($total, 2) ?>) <i class="fa-solid fa-check ml-2"></i>
                    </button>
                    <p class="text-center mt-4 text-xs text-muted">
                        <?= __('terms_agree') ?>
                    </p>
                </form>
            </div>

            <!-- Review Section -->
            <div class="checkout-card">
                <h3 class="checkout-card-title">
                    <i class="fa-solid fa-magnifying-glass"></i> <?= __('order_review') ?>
                </h3>
                
                <div class="bg-placeholder p-6 rounded-lg mb-8 border border-gray-100">
                    <div class="flex justify-between items-start mb-4">
                        <h4 class="text-xs uppercase text-secondary"><?= __('deliver_to') ?></h4>
                        <a href="checkout.php" class="text-xs text-primary font-bold"><?= __('edit') ?></a>
                    </div>
                    <div class="font-bold text-accent mb-2"><?= htmlspecialchars($checkout['customer_name']) ?></div>
                    <div class="text-sm text-main leading-relaxed"><?= nl2br(htmlspecialchars($checkout['address'])) ?></div>
                    <div class="text-sm text-secondary mt-2"><?= __('phone_no') ?>: <?= htmlspecialchars($checkout['phone']) ?></div>
                </div>

                <div class="total-wrapper border-none pt-0 mt-0">
                    <div class="summary-item">
                        <span><?= __('items_total') ?></span>
                        <span>₹<?= number_format(getCartTotal(), 2) ?></span>
                    </div>
                    <?php if ($lens_total > 0): ?>
                    <div class="summary-item text-primary">
                        <span>Lens Charges</span>
                        <span>+₹<?= number_format($lens_total, 2) ?></span>
                    </div>
                    <?php endif; ?>
                    <?php if ($coupon_id): ?>
                    <div id="uiDiscountRow" class="summary-item <?= $is_prepaid_only ? 'hidden' : 'flex' ?>">
                        <span>Coupon (<?= htmlspecialchars($coupon_code) ?>)</span>
                        <span class="text-success">-₹<?= number_format($potential_discount, 2) ?></span>
                    </div>
                    <?php endif; ?>
                    <div class="summary-item">
                        <span><?= __('delivery_fee') ?></span>
                        <span class="text-success"><?= __('free') ?></span>
                    </div>
                    <div class="total-row mt-6">
                        <span><?= __('total_payable') ?></span>
                        <span>₹<span id="uiFinalTotal"><?= number_format($subtotal, 2) ?></span></span>
                    </div>
                </div>

            </div>
        </div>
    </div>
</div>



<!-- Razorpay Checkout SDK -->
<script src="https://checkout.razorpay.com/v1/checkout.js"></script>

<script>
// Payment Form Handler
document.getElementById('paymentForm').addEventListener('submit', function(e) {
    e.preventDefault();
    
    const paymentMethodInput = document.querySelector('input[name="payment_method"]:checked');
    if (!paymentMethodInput) {
        alert('Please select a payment method');
        return;
    }
    
    const paymentMethod = paymentMethodInput.value;
    const placeOrderBtn = document.getElementById('placeOrderBtn');
    
    if (paymentMethod === 'cod') {
        // COD - Submit form normally
        placeOrderBtn.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Processing...';
        placeOrderBtn.disabled = true;
        
        // Use native form submission to handle PHP redirects properly
        e.target.submit();
        
    } else if (paymentMethod === 'razorpay') {
        // Online Payment - Use Razorpay
        placeOrderBtn.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Loading...';
        placeOrderBtn.disabled = true;
        
        // Create Razorpay order
        fetch('api/create_razorpay_order.php', {
            method: 'POST'
        })
        .then(response => response.json())
        .then(data => {
            if (data.error) {
                throw new Error(data.error);
            }
            
            // Initialize Razorpay Checkout
            const options = {
                key: data.key_id,
                amount: data.amount * 100, // Paise
                currency: data.currency,
                name: data.name,
                description: data.description,
                order_id: data.order_id,
                prefill: data.prefill,
                theme: {
                    color: '#e31e24'
                },
                handler: function(response) {
                    // Payment successful - verify payment
                    verifyPayment(response);
                },
                modal: {
                    ondismiss: function() {
                        // Payment cancelled
                        placeOrderBtn.innerHTML = '<?= __("place_order") ?> (₹<?= number_format($total, 2) ?>) <i class="fa-solid fa-check ml-2"></i>';
                        placeOrderBtn.disabled = false;
                    }
                }
            };
            
            const rzp = new Razorpay(options);
            rzp.open();
            
            // Re-enable button if Razorpay fails to open (timeout safety)
            setTimeout(() => {
                 if (placeOrderBtn.disabled && !document.querySelector('.razorpay-checkout-frame')) {
                     // Keep disabled if frame is open, otherwise enable?
                     // Actually Razorpay usually handles this, but good to have safety
                 }
            }, 5000);
            
        })
        .catch(error => {
            console.error('Error:', error);
            alert('Failed to initialize payment: ' + (error.message || 'Unknown error'));
            placeOrderBtn.innerHTML = '<?= __("place_order") ?> (₹<?= number_format($total, 2) ?>) <i class="fa-solid fa-check ml-2"></i>';
            placeOrderBtn.disabled = false;
        });
    }
});

// Dynamic Price Update based on Payment Method
const subtotal = <?= $subtotal ?>;
const potentialDiscount = <?= $potential_discount ?>;
const isPrepaidOnly = <?= $is_prepaid_only ? 'true' : 'false' ?>;
const placeOrderBtn = document.getElementById('placeOrderBtn');
const uiFinalTotal = document.getElementById('uiFinalTotal');
const uiDiscountRow = document.getElementById('uiDiscountRow');

function updateSummary(method) {
    let finalTotal = subtotal;
    let showDiscount = false;

    if (potentialDiscount > 0) {
        if (!isPrepaidOnly || method !== 'cod') {
            finalTotal = Math.max(0, subtotal - potentialDiscount);
            showDiscount = true;
        }
    }

    if (uiDiscountRow) uiDiscountRow.style.display = showDiscount ? 'flex' : 'none';
    if (uiFinalTotal) uiFinalTotal.innerText = finalTotal.toLocaleString('en-IN', {minimumFractionDigits: 2});
    if (placeOrderBtn) {
        const btnText = method === 'razorpay' ? '<?= __("pay_now") ?>' : '<?= __("place_order") ?>';
        placeOrderBtn.innerHTML = `${btnText} (₹${finalTotal.toLocaleString('en-IN', {minimumFractionDigits: 2})}) <i class="fa-solid fa-check ml-2"></i>`;
    }
}

// Initial update
const initialMethod = document.querySelector('input[name="payment_method"]:checked')?.value || 'cod';
updateSummary(initialMethod);

// Listen for method changes
document.querySelectorAll('input[name="payment_method"]').forEach(input => {
    input.addEventListener('change', (e) => updateSummary(e.target.value));
});

// Verify Payment Function
function verifyPayment(paymentData) {
    const placeOrderBtn = document.getElementById('placeOrderBtn');
    placeOrderBtn.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Verifying Payment...';
    
    const formData = new FormData();
    formData.append('razorpay_order_id', paymentData.razorpay_order_id);
    formData.append('razorpay_payment_id', paymentData.razorpay_payment_id);
    formData.append('razorpay_signature', paymentData.razorpay_signature);
    
    fetch('api/verify_payment.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // Payment verified - redirect to success page
            window.location.href = data.redirect_url;
        } else {
            throw new Error(data.error || 'Payment verification failed');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('Payment verification failed: ' + error.message);
        placeOrderBtn.innerHTML = '<?= __("place_order") ?> (₹<?= number_format($total, 2) ?>) <i class="fa-solid fa-check ml-2"></i>';
        placeOrderBtn.disabled = false;
    });
}
</script>

<?php require_once 'includes/footer.php'; ?>
