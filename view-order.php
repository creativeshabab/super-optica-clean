<?php require_once 'includes/header.php'; ?>
<?php require_once 'config/db.php'; ?>
<?php require_once 'includes/functions.php'; ?>

<?php
if (!isLoggedIn()) {
    redirect('login.php');
}

if (!isset($_GET['id'])) {
    redirect('my-orders.php');
}

$user_id = $_SESSION['user_id'];
$order_id = $_GET['id'];

// Fetch order and verify it belongs to the user
$stmt = $pdo->prepare("SELECT * FROM orders WHERE id = ? AND user_id = ?");
$stmt->execute([$order_id, $user_id]);
$order = $stmt->fetch();

if (!$order) {
    redirect('my-orders.php');
}

// Fetch order items
$items_stmt = $pdo->prepare("SELECT oi.*, p.name, p.image FROM order_items oi JOIN products p ON oi.product_id = p.id WHERE oi.order_id = ?");
$items_stmt->execute([$order_id]);
$orderItems = $items_stmt->fetchAll();
?>

<div class="web-wrapper section-padding bg-gray-50 min-h-screen">
    <div class="container mx-auto px-4 max-w-4xl">
        <div class="flex flex-col md:flex-row md:items-center justify-between mb-8 gap-4">
            <div>
                <a href="my-orders.php" class="text-gray-500 hover:text-primary font-bold text-sm flex items-center gap-2 mb-2 transition-colors">
                    <i class="fa-solid fa-arrow-left"></i> <?= __('back_to_orders') ?>
                </a>
                <h1 class="text-3xl font-black text-gray-900"><?= __('order_details') ?> <span class="text-gray-400 ml-2 text-xl font-bold">#<?= $order['id'] ?></span></h1>
            </div>
            <div class="text-left md:text-right">
                <?php
                    $statusColors = [
                        'completed' => 'bg-green-100 text-green-700',
                        'shipped' => 'bg-blue-100 text-blue-700',
                        'cancelled' => 'bg-red-100 text-red-700',
                        'pending' => 'bg-yellow-100 text-yellow-700'
                    ];
                    $bgClass = $statusColors[strtolower($order['status'])] ?? 'bg-gray-100 text-gray-700';
                ?>
                <span class="inline-block px-4 py-1.5 rounded-full text-sm font-bold uppercase tracking-wide mb-1 <?= $bgClass ?>">
                    <?= __(strtolower($order['status'])) ?>
                </span>
                <p class="text-gray-500 text-sm font-medium"><?= __('placed_on') ?> <?= date('M d, Y', strtotime($order['created_at'])) ?></p>
            </div>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
            <div class="lg:col-span-2 space-y-8">
                <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-8">
                    <h3 class="text-lg font-bold text-gray-900 mb-6 flex items-center gap-3 pb-4 border-b border-gray-50">
                        <i class="fa-solid fa-list-ul text-primary"></i> <?= __('items_purchased') ?>
                    </h3>
                    <div class="space-y-6">
                        <?php foreach ($orderItems as $item): ?>
                            <div class="flex gap-4 items-start">
                                <div class="w-20 h-20 bg-gray-50 rounded-xl border border-gray-100 flex items-center justify-center p-2 shrink-0">
                                    <?php if($item['image']): ?>
                                        <img src="assets/uploads/<?= $item['image'] ?>" class="w-full h-full object-contain">
                                    <?php else: ?>
                                        <i class="fa-solid fa-image text-gray-300 text-2xl"></i>
                                    <?php endif; ?>
                                </div>
                                <div class="flex-1">
                                    <h4 class="font-bold text-gray-900 mb-1"><?= htmlspecialchars($item['name']) ?></h4>
                                    <p class="text-gray-500 text-sm font-medium">₹<?= number_format($item['price'], 2) ?> × <?= $item['quantity'] ?></p>
                                </div>
                                <div class="font-bold text-gray-900">₹<?= number_format($item['price'] * $item['quantity'], 2) ?></div>
                            </div>
                        <?php endforeach; ?>
                    </div>

                    <div class="mt-8 pt-6 border-t border-gray-100 space-y-3">
                        <div class="flex justify-between text-gray-500 font-medium">
                            <span><?= __('subtotal') ?></span>
                            <span>₹<?= number_format($order['total_amount'], 2) ?></span>
                        </div>
                        <div class="flex justify-between text-gray-500 font-medium">
                            <span><?= __('shipping') ?></span>
                            <span class="text-green-600 font-bold"><?= __('free') ?></span>
                        </div>
                        <div class="flex justify-between text-xl font-black text-gray-900 pt-4 border-t border-gray-100">
                            <span><?= __('grand_total') ?></span>
                            <span>₹<?= number_format($order['total_amount'], 2) ?></span>
                        </div>
                    </div>
                </div>
            </div>

            <div class="space-y-6">
                <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-8">
                    <h3 class="text-lg font-bold text-gray-900 mb-6 flex items-center gap-3 pb-4 border-b border-gray-50">
                        <i class="fa-solid fa-truck text-primary"></i> <?= __('delivery_information') ?>
                    </h3>
                    <div class="space-y-6">
                        <div>
                            <h4 class="text-xs font-bold text-gray-400 uppercase tracking-wider mb-2"><?= __('recipient') ?></h4>
                            <p class="font-bold text-gray-900 text-lg"><?= htmlspecialchars($order['customer_name'] ?? $_SESSION['user_name']) ?></p>
                            <p class="text-gray-500 font-medium text-sm"><?= htmlspecialchars($order['phone'] ?? __('no_contact')) ?></p>
                        </div>
                        <div>
                            <h4 class="text-xs font-bold text-gray-400 uppercase tracking-wider mb-2"><?= __('shipping_address') ?></h4>
                            <div class="text-gray-600 leading-relaxed font-medium">
                                <?= nl2br(htmlspecialchars($order['address'])) ?>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="bg-indigo-50 border border-indigo-100 rounded-2xl p-6 text-center">
                    <p class="text-indigo-800 text-sm font-medium mb-4"><?= __('copy_purchase_msg') ?></p>
                    <a href="admin/invoice.php?id=<?= $order['id'] ?>" target="_blank" class="block w-full py-3 bg-white border-2 border-indigo-100 text-indigo-600 font-bold rounded-xl hover:bg-indigo-50 hover:border-indigo-200 transition-colors">
                        <i class="fa-solid fa-file-pdf mr-2"></i> <?= __('download_invoice') ?>
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>
