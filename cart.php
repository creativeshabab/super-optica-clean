<?php
require_once 'includes/functions.php';
require_once 'config/db.php';

// Handle Add
if (isset($_GET['add'])) {
    $id = $_GET['add'];
    if (addToCart($id)) {
        setFlash('success', __('item_added_cart'));
    } else {
        setFlash('error', __('product_not_found'));
    }
    
    if (isset($_GET['redirect'])) {
        $allowed_redirects = ['checkout.php', 'cart.php', 'shop.php'];
        $target = basename($_GET['redirect']); // Strip path traversal
        if (in_array($target, $allowed_redirects)) {
            redirect($target);
        }
    } else {
        redirect('cart.php');
    }
}

// Handle Remove
if (isset($_GET['remove'])) {
    unset($_SESSION['cart'][$_GET['remove']]);
    setFlash('success', __('item_removed_cart'));
    redirect('cart.php');
}

// Handle Update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_cart'])) {
    if (isset($_POST['qty'])) {
        foreach ($_POST['qty'] as $id => $qty) {
            if ($qty < 1) {
                unset($_SESSION['cart'][$id]);
            } else {
                $_SESSION['cart'][$id]['quantity'] = $qty;
            }
        }
    }
    setFlash('success', __('cart_updated'));
    redirect('cart.php');
}

$cart = $_SESSION['cart'] ?? [];
$total = getCartTotal();
?>

<?php require_once 'includes/header.php'; ?>

<div class="checkout-container bg-gray-50 section-padding">
    <div class="container mx-auto px-4">
        <div class="mb-12">
            <h1 class="text-4xl font-black text-accent m-0"><?= __('shopping_cart') ?></h1>
            <p class="text-secondary font-bold mt-2"><?= __('review_items') ?></p>
        </div>

        <?php if (empty($cart)): ?>
            <div class="checkout-card text-center py-20 px-8 bg-white rounded-lg shadow-sm">
                <div class="w-20 h-20 bg-red-50 text-primary rounded-full flex items-center justify-center text-4xl mx-auto mb-8">
                    <i class="fa-solid fa-cart-shopping"></i>
                </div>
                <h2 class="text-3xl font-black text-accent mb-4"><?= __('empty_cart') ?></h2>
                <p class="text-secondary mb-10 max-w-md mx-auto"><?= __('empty_cart_desc') ?></p>
                <a href="shop.php" class="btn btn-primary min-w-[200px]"><?= __('start_shopping') ?></a>
            </div>
        <?php else: ?>
            <form method="POST">
                <div class="checkout-grid grid grid-cols-1 md:grid-cols-3 gap-6">
                    <!-- Cart Items -->
                    <div class="md:col-span-2 space-y-6">
                        <div class="checkout-card card p-8 h-fit">
                            <h3 class="checkout-card-title text-xl font-bold mb-6 flex items-center gap-2 border-b border-gray-100 pb-4">
                                <i class="fa-solid fa-list-check text-primary"></i> <?= __('your_items') ?>
                            </h3>
                            <!-- Desktop Table View -->
                            <div class="hidden md:block overflow-x-auto">
                                <table class="w-full min-w-[600px] border-collapse">
                                    <thead>
                                        <tr class="text-left border-b-2 border-gray-100">
                                            <th class="py-5 px-4 text-secondary text-sm uppercase tracking-wide font-bold"><?= __('product') ?></th>
                                            <th class="py-5 px-4 text-secondary text-sm uppercase tracking-wide font-bold"><?= __('price') ?></th>
                                            <th class="py-5 px-4 text-secondary text-sm uppercase tracking-wide font-bold"><?= __('quantity') ?></th>
                                            <th class="py-5 px-4 text-secondary text-sm uppercase tracking-wide font-bold text-right"><?= __('total') ?></th>
                                            <th class="py-5 px-4"></th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($cart as $id => $item): ?>
                                        <tr class="border-b border-gray-100 last:border-0 hover:bg-gray-50 transition-colors">
                                            <td class="py-6 px-4">
                                                <div class="flex items-center gap-5">
                                                    <div class="w-20 h-20 bg-white border border-gray-200 rounded-xl flex items-center justify-center p-2 flex-shrink-0 overflow-hidden">
                                                        <?php if($item['image']): ?>
                                                            <img src="assets/uploads/<?= $item['image'] ?>" class="w-full h-full object-contain">
                                                        <?php else: ?>
                                                            <i class="fa-solid fa-image text-gray-300 text-2xl"></i>
                                                        <?php endif; ?>
                                                    </div>
                                                    <strong class="text-accent font-extrabold text-base"><?= htmlspecialchars($item['name']) ?></strong>
                                                </div>
                                            </td>
                                            <td class="py-4 px-4 text-accent font-bold">₹<?= number_format($item['price'], 2) ?></td>
                                            <td class="py-4 px-4">
                                                <input type="number" name="qty[<?= $id ?>]" value="<?= $item['quantity'] ?>" min="1" 
                                                       class="w-20 p-2 border-2 border-gray-100 rounded-lg font-bold text-center text-accent focus:border-primary focus:outline-none transition-colors">
                                            </td>
                                            <td class="py-4 px-4 text-right text-accent font-black text-lg">₹<?= number_format($item['price'] * $item['quantity'], 2) ?></td>
                                            <td class="py-4 px-4 text-right">
                                                <a href="cart.php?remove=<?= $id ?>" class="text-gray-300 text-xl hover:text-red-500 transition-colors" title="<?= __('remove_item') ?>">
                                                    <i class="fa-solid fa-trash-can"></i>
                                                </a>
                                            </td>
                                        </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>

                            <!-- Mobile Card View -->
                            <div class="md:hidden space-y-6">
                                <?php foreach ($cart as $id => $item): ?>
                                    <div class="flex gap-4 border-b border-gray-100 pb-6 last:border-0 last:pb-0">
                                        <!-- Image -->
                                        <div class="w-24 h-24 bg-white border border-gray-200 rounded-xl flex items-center justify-center p-2 flex-shrink-0 overflow-hidden">
                                            <?php if($item['image']): ?>
                                                <img src="assets/uploads/<?= $item['image'] ?>" class="w-full h-full object-contain">
                                            <?php else: ?>
                                                <i class="fa-solid fa-image text-gray-300 text-2xl"></i>
                                            <?php endif; ?>
                                        </div>
                                        
                                        <!-- Content -->
                                        <div class="flex-1 flex flex-col justify-between">
                                            <div>
                                                <div class="flex justify-between items-start">
                                                    <strong class="text-accent font-extrabold text-base line-clamp-2 pr-2"><?= htmlspecialchars($item['name']) ?></strong>
                                                    <a href="cart.php?remove=<?= $id ?>" class="text-gray-400 hover:text-red-500 transition-colors">
                                                        <i class="fa-solid fa-trash-can"></i>
                                                    </a>
                                                </div>
                                                <div class="text-primary font-bold mt-1">₹<?= number_format($item['price'], 2) ?></div>
                                            </div>
                                            
                                            <div class="flex justify-between items-end mt-2">
                                                <div class="flex items-center gap-2">
                                                    <span class="text-xs text-gray-500 font-bold uppercase">Qty:</span>
                                                    <input type="number" name="qty[<?= $id ?>]" value="<?= $item['quantity'] ?>" min="1" 
                                                           class="w-16 p-1.5 border-2 border-gray-100 rounded-lg font-bold text-center text-accent text-sm focus:border-primary focus:outline-none">
                                                </div>
                                                <div class="text-accent font-black">
                                                    ₹<?= number_format($item['price'] * $item['quantity'], 2) ?>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                            <div class="mt-8 flex justify-between items-center pt-6 border-t border-gray-100">
                                <a href="shop.php" class="text-secondary font-bold flex items-center gap-2 hover:text-primary transition-colors">
                                    <i class="fa-solid fa-arrow-left"></i> <?= __('continue_shopping') ?>
                                </a>
                                <button type="submit" name="update_cart" class="btn btn-outline py-3 px-6">
                                    <i class="fa-solid fa-rotate mr-2"></i> <?= __('update_cart') ?>
                                </button>
                            </div>
                        </div>
                    </div>

                    <!-- Summary -->
                    <div class="checkout-card card h-fit sticky top-4">
                        <h3 class="checkout-card-title text-xl font-bold mb-6 flex items-center gap-2">
                            <i class="fa-solid fa-receipt text-primary"></i> <?= __('order_summary') ?>
                        </h3>

                        <div class="summary-list flex flex-col gap-4 mb-6">
                            <?php foreach ($cart as $item): ?>
                            <div class="summary-item flex justify-between items-center pb-4 border-b border-gray-100 last:border-0 border-dashed">
                                <div class="flex gap-3 items-center">
                                    <div>
                                        <div class="font-bold text-sm line-clamp-1"><?= htmlspecialchars($item['name']) ?></div>
                                        <div class="text-[10px] text-gray-400 font-bold uppercase tracking-wider">Qty: <?= $item['quantity'] ?></div>
                                    </div>
                                </div>
                                <span class="font-bold text-primary">₹<?= number_format($item['price'] * $item['quantity'], 2) ?></span>
                            </div>
                            <?php endforeach; ?>
                        </div>

                        <div class="total-wrapper border-none pt-0 space-y-3">
                            <div class="flex justify-between items-center text-sm font-bold text-gray-600">
                                <span><?= __('subtotal') ?></span>
                                <span>₹<?= number_format($total, 2) ?></span>
                            </div>
                            <div class="flex justify-between items-center text-sm font-bold text-gray-600">
                                <span><?= __('shipping') ?></span>
                                <span class="text-green-500"><?= __('free') ?></span>
                            </div>
                            <div class="flex justify-between items-center pt-4 mt-2 border-t border-gray-100 text-lg font-black text-accent">
                                <span><?= __('estimated_total') ?></span>
                                <span class="text-primary">₹<?= number_format($total, 2) ?></span>
                            </div>
                        </div>
                        
                        <div class="mt-8 space-y-6">
                            <a href="checkout.php" class="btn btn-primary w-full py-4 flex items-center justify-center gap-3 shadow-lg hover:shadow-xl transition-all">
                                <?= __('proceed_to_checkout') ?> <i class="fa-solid fa-chevron-right text-xs"></i>
                            </a>
                            <p class="text-center text-[10px] text-gray-400 font-medium flex items-center justify-center gap-2 uppercase tracking-widest">
                                <i class="fa-solid fa-shield-halved text-success"></i> <?= __('secure_checkout') ?>
                            </p>
                        </div>
                    </div>
                </div>
            </form>
        <?php endif; ?>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>
