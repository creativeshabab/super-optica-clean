<?php require_once 'includes/header.php'; ?>
<?php require_once 'config/db.php'; ?>
<?php require_once 'includes/functions.php'; ?>

<?php
if (!isLoggedIn()) {
    redirect('login.php');
}

$user_id = $_SESSION['user_id'];

// Fetch user orders
$stmt = $pdo->prepare("SELECT * FROM orders WHERE user_id = ? ORDER BY created_at DESC");
$stmt->execute([$user_id]);
$orders = $stmt->fetchAll();
?>

<div class="web-wrapper section-padding bg-gray-50 min-h-screen">
    <div class="container mx-auto px-4 max-w-4xl">
        <div class="text-center mb-10">
            <h1 class="text-3xl font-black text-gray-900 mb-2"><?= __('order_history') ?></h1>
            <p class="text-gray-500 text-lg"><?= __('track_manage') ?></p>
        </div>

        <?php if (empty($orders)): ?>
            <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-12 text-center">
                <div class="w-20 h-20 bg-gray-100 rounded-full flex items-center justify-center text-4xl text-gray-400 mx-auto mb-6">
                    <i class="fa-solid fa-bag-shopping"></i>
                </div>
                <h2 class="text-2xl font-bold text-gray-800 mb-2"><?= __('no_orders') ?></h2>
                <p class="text-gray-500 mb-8"><?= __('no_orders_desc') ?></p>
                <a href="shop.php" class="btn btn-primary px-8 py-3 text-lg font-bold shadow-lg shadow-primary/30 hover:-translate-y-1 transition-transform"><?= __('start_shopping') ?></a>
            </div>
        <?php else: ?>
            <div class="space-y-4">
                <?php foreach ($orders as $order): ?>
                    <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6 transition-all hover:shadow-md">
                        <div class="flex flex-col md:flex-row gap-6 items-start md:items-center justify-between">
                            <div class="flex gap-4 items-center">
                                <div class="w-14 h-14 bg-gray-50 rounded-xl flex items-center justify-center text-2xl text-gray-400">
                                    <i class="fa-solid fa-box"></i>
                                </div>
                                <div>
                                    <div class="text-xs font-bold text-gray-400 uppercase tracking-wider mb-1"><?= __('order') ?> #<?= $order['id'] ?></div>
                                    <div class="text-xl font-bold text-gray-900">₹<?= number_format($order['total_amount'], 2) ?></div>
                                    <div class="text-sm text-gray-500 mt-1">
                                        <?= __('placed_on') ?> <?= date('M d, Y', strtotime($order['created_at'])) ?>
                                    </div>
                                </div>
                            </div>

                            <div class="flex flex-row md:flex-col items-center md:items-end gap-3 w-full md:w-auto justify-between md:justify-start">
                                <?php 
                                    $statusColors = [
                                        'completed' => 'bg-green-100 text-green-700',
                                        'shipped' => 'bg-blue-100 text-blue-700',
                                        'cancelled' => 'bg-red-100 text-red-700',
                                        'pending' => 'bg-yellow-100 text-yellow-700'
                                    ];
                                    $bgClass = $statusColors[strtolower($order['status'])] ?? 'bg-gray-100 text-gray-700';
                                ?>
                                <span class="px-3 py-1 rounded-full text-xs font-bold uppercase tracking-wide <?= $bgClass ?>">
                                    <?= __(strtolower($order['status'])) ?>
                                </span>
                                <a href="view-order.php?id=<?= $order['id'] ?>" class="text-primary font-bold text-sm hover:underline flex items-center gap-1">
                                    <?= __('view_details') ?> <i class="fa-solid fa-chevron-right text-xs"></i>
                                </a>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>
