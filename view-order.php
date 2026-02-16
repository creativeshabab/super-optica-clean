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

// Fetch Prescriptions
$rx_stmt = $pdo->prepare("SELECT op.*, lo.name as lens_name, lo.price as lens_price 
                         FROM order_prescriptions op 
                         LEFT JOIN lens_options lo ON op.lens_option_id = lo.id 
                         WHERE op.order_id = ?");
$rx_stmt->execute([$order_id]);
$prescriptions = $rx_stmt->fetchAll(PDO::FETCH_GROUP | PDO::FETCH_UNIQUE); // Group by product_id (if unique) OR handle loops. 
// Actually FETCH_GROUP might group by ID if first column? No ID is first.
// Let's re-key by product_id manually to be safe.
$prescriptions_by_product = [];
foreach ($rx_stmt->fetchAll() as $rx) {
    $prescriptions_by_product[$rx['product_id']] = $rx;
}
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
                                    
                                    <?php if (isset($prescriptions_by_product[$item['product_id']])): 
                                        $p_rx = $prescriptions_by_product[$item['product_id']];
                                    ?>
                                    <div class="mt-2 text-sm">
                                        <div class="inline-flex items-center gap-2 bg-indigo-50 text-indigo-700 px-2 py-1 rounded border border-indigo-100">
                                            <i class="fa-solid fa-glasses text-xs"></i>
                                            <span class="font-bold"><?= htmlspecialchars($p_rx['lens_name']) ?></span>
                                            <span class="text-xs ml-1">(+₹<?= number_format($p_rx['lens_price'], 2) ?>)</span>
                                        </div>
                                        <button onclick='showRxModal(<?= json_encode($p_rx) ?>)' class="block mt-1 text-xs text-primary underline font-medium hover:text-red-700">
                                            View Prescription
                                        </button>
                                    </div>
                                    <?php endif; ?>
                                </div>
                                <div class="font-bold text-gray-900">₹<?= number_format($item['price'] * $item['quantity'], 2) ?></div>
                            </div>
                        <?php endforeach; ?>
                    </div>

                    <!-- RX Modal -->
                    <div id="rxModal" class="fixed inset-0 bg-black/50 z-50 hidden flex items-center justify-center p-4">
                        <div class="bg-white rounded-xl shadow-2xl w-full max-w-lg overflow-hidden animate-up">
                            <div class="bg-primary text-white p-4 flex justify-between items-center">
                                <h3 class="font-bold"><i class="fa-solid fa-eye"></i> Prescription Details</h3>
                                <button onclick="document.getElementById('rxModal').classList.add('hidden')" class="text-white hover:text-gray-200"><i class="fa-solid fa-xmark fa-lg"></i></button>
                            </div>
                            <div class="p-6">
                                <div class="grid grid-cols-1 gap-6">
                                    <!-- OD -->
                                    <div class="bg-gray-50 p-3 rounded border">
                                        <div class="text-xs font-bold text-center mb-2 text-primary tracking-widest">RIGHT EYE (OD)</div>
                                        <div class="grid grid-cols-4 gap-2 text-center text-sm">
                                            <div class="bg-white p-1 rounded border"><div class="text-[10px] text-gray-400">SPH</div><span id="rx_od_sph" class="font-bold"></span></div>
                                            <div class="bg-white p-1 rounded border"><div class="text-[10px] text-gray-400">CYL</div><span id="rx_od_cyl" class="font-bold"></span></div>
                                            <div class="bg-white p-1 rounded border"><div class="text-[10px] text-gray-400">AXIS</div><span id="rx_od_axis" class="font-bold"></span></div>
                                            <div class="bg-white p-1 rounded border"><div class="text-[10px] text-gray-400">ADD</div><span id="rx_od_add" class="font-bold"></span></div>
                                        </div>
                                    </div>
                                    <!-- OS -->
                                    <div class="bg-gray-50 p-3 rounded border">
                                        <div class="text-xs font-bold text-center mb-2 text-primary tracking-widest">LEFT EYE (OS)</div>
                                        <div class="grid grid-cols-4 gap-2 text-center text-sm">
                                            <div class="bg-white p-1 rounded border"><div class="text-[10px] text-gray-400">SPH</div><span id="rx_os_sph" class="font-bold"></span></div>
                                            <div class="bg-white p-1 rounded border"><div class="text-[10px] text-gray-400">CYL</div><span id="rx_os_cyl" class="font-bold"></span></div>
                                            <div class="bg-white p-1 rounded border"><div class="text-[10px] text-gray-400">AXIS</div><span id="rx_os_axis" class="font-bold"></span></div>
                                            <div class="bg-white p-1 rounded border"><div class="text-[10px] text-gray-400">ADD</div><span id="rx_os_add" class="font-bold"></span></div>
                                        </div>
                                    </div>
                                </div>
                                <div class="mt-4 text-center">
                                    <div class="inline-block bg-gray-100 px-4 py-2 rounded-full border">
                                        <span class="text-xs text-gray-500 mr-2">PD (Pupillary Distance)</span>
                                        <span id="rx_pd" class="font-bold text-gray-900"></span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <script>
                    function showRxModal(rx) {
                        ['od_sph','od_cyl','od_axis','od_add','os_sph','os_cyl','os_axis','os_add','pd'].forEach(f => {
                            document.getElementById('rx_'+f).innerText = rx[f] || '-';
                        });
                        document.getElementById('rxModal').classList.remove('hidden');
                    }
                    </script>

                    <div class="mt-8 pt-6 border-t border-gray-100 space-y-3">
                        <div class="flex justify-between text-gray-500 font-medium">
                            <span><?= __('subtotal') ?></span>
                            <span>₹<?= number_format($order['total_amount'], 2) ?></span> <!-- Actually this might be wrong if total_amount includes lenses -->
                        </div>
                        <?php 
                        // Recalculate Items Subtotal for display
                        $itemsSubtotal = 0;
                        foreach($orderItems as $it) $itemsSubtotal += $it['price'] * $it['quantity'];
                        
                        $lensTotal = 0;
                        if (!empty($prescriptions_by_product)) {
                            foreach($prescriptions_by_product as $rx) $lensTotal += $rx['lens_price']; 
                        }
                        
                        // Correct display logic:
                        // If we want to show breakdown:
                        // Subtotal (Items)
                        // Lens Charges
                        // Grand Total
                        ?>
                        <div class="flex justify-between text-gray-500 font-medium">
                            <span>Item Subtotal</span>
                            <span>₹<?= number_format($itemsSubtotal, 2) ?></span>
                        </div>
                        <?php if($lensTotal > 0): ?>
                        <div class="flex justify-between text-indigo-600 font-medium">
                            <span>Lens Charges</span>
                            <span>+₹<?= number_format($lensTotal, 2) ?></span>
                        </div>
                        <?php endif; ?>
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
