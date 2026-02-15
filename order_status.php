<?php
require_once 'config/db.php';
require_once 'includes/functions.php';

$status = $_GET['status'] ?? 'pending';
$order_id = $_GET['id'] ?? 0;

if (!$order_id) redirect('index.php');

// Fetch order for confirmation
$stmt = $pdo->prepare("SELECT * FROM orders WHERE id = ?");
$stmt->execute([$order_id]);
$order = $stmt->fetch();

if (!$order) redirect('index.php');
?>

<?php require_once 'includes/header.php'; ?>

<div class="checkout-container section-padding bg-gray-50 min-h-[80vh] flex items-center justify-center">
    <div class="container mx-auto px-4 max-w-2xl">
        <!-- Stepper -->
        <div class="checkout-stepper flex justify-between items-center mb-10">
            <div class="step completed text-success flex flex-col items-center gap-2">
                <span class="step-number bg-success text-white w-8 h-8 rounded-full flex items-center justify-center"><i class="fa-solid fa-check"></i></span>
                <span class="text-sm font-bold text-gray-500"><?= __('shipping') ?></span>
            </div>
            <div class="step-line flex-1 h-1 bg-success mx-2 rounded-full"></div>
            <div class="step completed text-success flex flex-col items-center gap-2">
                <span class="step-number bg-success text-white w-8 h-8 rounded-full flex items-center justify-center"><i class="fa-solid fa-check"></i></span>
                <span class="text-sm font-bold text-gray-500"><?= __('payment') ?></span>
            </div>
            <div class="step-line flex-1 h-1 bg-success mx-2 rounded-full"></div>
            <div class="step active <?= $status === 'success' ? 'completed text-success' : 'text-primary' ?> flex flex-col items-center gap-2">
                <span class="step-number <?= $status === 'success' ? 'bg-success text-white' : 'bg-primary text-white' ?> w-8 h-8 rounded-full flex items-center justify-center font-bold">
                    <?= $status === 'success' ? '<i class="fa-solid fa-check"></i>' : '03' ?>
                </span>
                <span class="text-sm font-bold <?= $status === 'success' ? 'text-gray-500' : 'text-primary' ?>"><?= $status === 'success' ? __('confirmed') : __('status') ?></span>
            </div>
        </div>

        <div class="bg-white rounded-3xl shadow-xl p-8 md:p-12 text-center border border-gray-100 relative overflow-hidden">
            <?php if ($status === 'success'): ?>
                <!-- Decorative Background Element -->
                <div class="absolute top-0 left-0 w-full h-2 bg-gradient-to-r from-success to-emerald-400"></div>

                <div class="mb-6 relative inline-block">
                    <div class="w-24 h-24 bg-green-50 rounded-full flex items-center justify-center mx-auto mb-4 animate-bounce-slow">
                        <i class="fa-solid fa-circle-check text-5xl text-success drop-shadow-md"></i>
                    </div>
                </div>

                <h1 class="text-3xl md:text-4xl font-black text-gray-900 mb-2 tracking-tight"><?= __('order_confirmed') ?>!</h1>
                <p class="text-gray-500 mb-8 max-w-md mx-auto leading-relaxed">
                    <?= __('order_success_msg') ?> <br>
                    <span class="font-bold text-gray-700">Order #<?= $order_id ?></span>
                </p>
                
                <div class="bg-gray-50 rounded-2xl p-6 mb-8 text-left border border-gray-100">
                    <h4 class="text-xs font-bold text-gray-400 uppercase tracking-wider mb-4 border-b border-gray-200 pb-2">
                        <i class="fa-solid fa-receipt text-primary mr-2"></i> <?= __('order_details') ?>
                    </h4>
                    
                    <div class="flex justify-between items-center mb-3">
                        <span class="text-sm font-medium text-gray-600"><?= __('customer') ?></span>
                        <span class="text-sm font-bold text-gray-900"><?= htmlspecialchars($order['customer_name']) ?></span>
                    </div>
                    
                    <div class="flex justify-between items-center mb-4">
                        <span class="text-sm font-medium text-gray-600"><?= __('amount_paid') ?></span>
                        <span class="text-lg font-black text-gray-900">₹<?= number_format($order['total_amount'] ?? 0, 2) ?></span>
                    </div>
                    
                    <div class="bg-blue-50 rounded-xl p-4 flex gap-3 items-start">
                        <i class="fa-solid fa-truck-fast text-blue-500 mt-1"></i>
                        <p class="text-sm text-blue-800 leading-snug">
                            <strong><?= __('arrival_msg') ?></strong><br>
                            Your eyewear is being prepared. Our experts are conducting a quality check. Arrival in 3-5 business days.
                        </p>
                    </div>
                </div>

                <div class="flex flex-col md:flex-row gap-4 justify-center">
                    <a href="shop.php" class="btn btn-outline px-8 py-3 rounded-xl font-bold border-2 hover:bg-gray-50 transition-colors">
                        <i class="fa-solid fa-bag-shopping mr-2"></i> <?= __('shop_more') ?>
                    </a>
                    <a href="view-order.php?id=<?= $order_id ?>" class="btn btn-primary px-8 py-3 rounded-xl font-bold shadow-lg hover:shadow-xl hover:-translate-y-1 transition-all">
                        <?= __('view_order') ?> <i class="fa-solid fa-arrow-right ml-2 text-xs"></i>
                    </a>
                </div>

            <?php else: ?>
                <div class="absolute top-0 left-0 w-full h-2 bg-gradient-to-r from-red-500 to-orange-500"></div>
                
                <div class="w-24 h-24 bg-red-50 rounded-full flex items-center justify-center mx-auto mb-6">
                    <i class="fa-solid fa-circle-xmark text-5xl text-red-500"></i>
                </div>
                
                <h1 class="text-3xl font-black text-gray-900 mb-2"><?= __('payment_failed') ?></h1>
                <p class="text-gray-500 mb-8 max-w-md mx-auto">
                    <?= __('payment_failed_msg') ?>
                </p>

                <div class="flex gap-4 justify-center">
                    <a href="checkout.php" class="btn btn-primary px-8 py-3 rounded-xl font-bold shadow-lg">
                        <i class="fa-solid fa-rotate-right mr-2"></i> Try Again
                    </a>
                    <a href="contact.php" class="btn btn-outline px-8 py-3 rounded-xl font-bold border-2">
                        Contact Support
                    </a>
                </div>
            <?php endif; ?>
        </div>
        
        <p class="text-center mt-8 text-sm text-gray-400">
            <?= __('need_help') ?> <a href="contact.php" class="text-primary font-bold hover:underline"><?= __('contact_support') ?></a>
        </p>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>
