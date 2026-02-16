<?php
// ENHANCED ORDER VIEW - ROBUST ERROR HANDLING
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once '../config/db.php';
require_once '../includes/functions.php';

if (!isset($_GET['id'])) redirect('orders.php');

$order = null;
try {
    $stmt = $pdo->prepare("
        SELECT o.*, u.name, u.email, sm.name as shipping_method_name 
        FROM orders o 
        JOIN users u ON o.user_id = u.id 
        LEFT JOIN shipping_methods sm ON o.shipping_method_id = sm.id 
        WHERE o.id = ?
    ");
    $stmt->execute([$_GET['id']]);
    $order = $stmt->fetch();
} catch (PDOException $e) {
    die("Error fetching order: " . $e->getMessage());
}

if (!$order) redirect('orders.php');

// --- HANDLE SHIP NOW (Restored & Secured) ---
if (isset($_POST['ship_now'])) {
    try {
        $pdo->beginTransaction();
        
        // 1. Update Local Status
        $stmt = $pdo->prepare("UPDATE orders SET status = 'shipped' WHERE id = ?");
        $stmt->execute([$order['id']]);
        
        // 2. Integration with Shiprocket (Optional)
        $shiprocketEnabled = false;
        $shippedMsg = "Order marked as shipped locally.";

        // Robust File Checks
        $imFile = '../includes/IntegrationManager.php';
        $srFile = '../includes/shiprocket/ShiprocketAPI.php';

        if (file_exists($imFile) && file_exists($srFile)) {
             try {
                require_once $imFile;
                // Only require Shiprocket if IntegrationManager succeeds
                
                if (class_exists('IntegrationManager')) {
                    $integrationManager = IntegrationManager::getInstance($pdo);
                    $shiprocketConfig = $integrationManager->getIntegration('shiprocket');
                    
                    if ($shiprocketConfig && $shiprocketConfig['is_enabled']) {
                        require_once $srFile; // Require here to avoid fatal if file corrupted
                        
                        if (class_exists('ShiprocketAPI')) {
                            $shiprocketEnabled = true;
                            $config = $shiprocketConfig['config'];
                            $shiprocket = new ShiprocketAPI($config['email'], $config['password']);
                            
                            // Prepare Order Data
                            $orderDate = date('Y-m-d H:i', strtotime($order['created_at']));
                            $paymentMethod = $order['payment_method'] == 'cod' ? 'COD' : 'Prepaid';
                            
                            // Get Order Items
                            $itemsStmt = $pdo->prepare("SELECT oi.*, p.sku FROM order_items oi LEFT JOIN products p ON oi.product_id = p.id WHERE oi.order_id = ?");
                            $itemsStmt->execute([$order['id']]);
                            $dbItems = $itemsStmt->fetchAll();
                            
                            $srItems = [];
                            foreach ($dbItems as $item) {
                                $srItems[] = [
                                    'name' => $item['product_name'],
                                    'sku' => $item['sku'] ?: 'SKU-' . $item['product_id'],
                                    'units' => $item['quantity'],
                                    'selling_price' => $item['price'],
                                    'discount' => 0,
                                    'tax' => 0,
                                    'hsn' => 0
                                ];
                            }
                            
                            $payload = [
                                'order_id' => $order['order_number'],
                                'order_date' => $orderDate,
                                'pickup_location' => 'Primary',
                                'billing_customer_name' => $order['customer_name'],
                                'billing_last_name' => '',
                                'billing_address' => $order['address'],
                                'billing_city' => 'Begusarai',
                                'billing_pincode' => '851101',
                                'billing_state' => 'Bihar',
                                'billing_country' => 'India',
                                'billing_email' => $order['email'],
                                'billing_phone' => $order['phone'],
                                'shipping_is_billing' => true,
                                'order_items' => $srItems,
                                'payment_method' => $paymentMethod,
                                'sub_total' => $order['total_amount'],
                                'length' => 10, 'breadth' => 10, 'height' => 10, 'weight' => 0.5
                            ];
                            
                            // Create Order in Shiprocket
                            $response = $shiprocket->createOrder($payload);
                            
                            if (isset($response['order_id'])) {
                                $updateSr = $pdo->prepare("UPDATE orders SET shiprocket_order_id = ?, shiprocket_shipment_id = ? WHERE id = ?");
                                $updateSr->execute([$response['order_id'], $response['shipment_id'], $order['id']]);
                                $shippedMsg = "Order shipped and pushed to Shiprocket successfully!";
                            } else {
                                $shippedMsg .= " (Shiprocket Failed: Check logs)";
                                error_log("Shiprocket Failure: " . json_encode($response));
                            }
                        }
                    }
                }
             } catch (Throwable $t) {
                 // Catch ANY error in integration to prevent 500
                 error_log("Integration Error: " . $t->getMessage());
                 $shippedMsg .= " (Integration marked skipped due to error)";
             }
        }
        
        $pdo->commit();
        setFlash('success', $shippedMsg);
        
    } catch (Throwable $e) {
        // GLOBAL CATCH for the Action
        if ($pdo->inTransaction()) $pdo->rollBack();
        error_log("Ship Action Fatal Error: " . $e->getMessage());
        setFlash('error', 'Error shipping order: ' . $e->getMessage());
    }
    
    header("Location: order_view.php?id=" . $order['id']);
    exit;
}
// ------------------------------------------

require_once 'header.php'; 

$orderItems = [];
$prescriptions_by_product = [];

try {
    // Fetch Items
    $items = $pdo->prepare("SELECT oi.*, p.name, p.image FROM order_items oi JOIN products p ON oi.product_id = p.id WHERE oi.order_id = ?");
    $items->execute([$order['id']]);
    $orderItems = $items->fetchAll();

    // Fetch Prescriptions
    $rx_stmt = $pdo->prepare("SELECT op.*, lo.name as lens_name, lo.price as lens_price 
                             FROM order_prescriptions op 
                             LEFT JOIN lens_options lo ON op.lens_option_id = lo.id 
                             WHERE op.order_id = ?");
    $rx_stmt->execute([$order['id']]);
    foreach ($rx_stmt->fetchAll() as $rx) {
        $prescriptions_by_product[$rx['product_id']] = $rx;
    }

} catch (PDOException $e) {
    echo "<div class='alert alert-danger'>Error fetching items: " . $e->getMessage() . "</div>";
}
?>

<div class="d-flex justify-content-between align-items-center mb-4" style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 2.5rem;">
    <h1 class="admin-title" style="margin-bottom: 0;">
        <?= __('view_order') ?> <span style="color: var(--admin-text-light); font-weight: 400;">#<?= $order['id'] ?></span>
    </h1>
    <a href="orders.php" class="btn btn-secondary">
        <i class="fa-solid fa-arrow-left"></i> <?= __('back_to_orders') ?>
    </a>
</div>

<div class="admin-grid">
    <!-- Left Column: Order Items -->
    <div class="card">
        <h3 class="card-title"><?= __('order_items') ?></h3>
        <div class="table-container">
            <table style="border-spacing: 0;">
                <thead>
                    <tr style="border-bottom: 1px solid var(--admin-border);">
                        <th width="100" style="padding-bottom: 1rem; border-bottom: 1px solid var(--admin-border);"><?= __('image') ?></th>
                        <th style="padding-bottom: 1rem; border-bottom: 1px solid var(--admin-border);"><?= __('product_name') ?></th>
                        <th style="padding-bottom: 1rem; border-bottom: 1px solid var(--admin-border);"><?= __('price') ?></th>
                        <th style="padding-bottom: 1rem; border-bottom: 1px solid var(--admin-border);"><?= __('quantity') ?></th>
                        <th style="text-align: right; padding-bottom: 1rem; border-bottom: 1px solid var(--admin-border);"><?= __('subtotal') ?: 'Subtotal' ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($orderItems as $item): ?>
                    <tr>
                        <td style="padding-top: 1.5rem; background: none; border: none;">
                            <div style="width: 64px; height: 60px; background: #fff; border: 1px solid #f1f5f9; border-radius: 12px; display: flex; align-items: center; justify-content: center; padding: 6px; box-shadow: 0 2px 4px rgba(0,0,0,0.02);">
                                <?php if($item['image']): ?>
                                    <img src="../assets/uploads/<?= $item['image'] ?>" style="max-width: 100%; max-height: 100%; object-fit: contain;">
                                <?php else: ?>
                                    <i class="fa-solid fa-image" style="color: #e2e8f0;"></i>
                                <?php endif; ?>
                            </div>
                        </td>
                        <td style="padding-top: 1.5rem; font-weight: 700; color: var(--admin-sidebar); background: none; border: none;">
                            <?= htmlspecialchars($item['name']) ?>
                            <?php if (isset($prescriptions_by_product[$item['product_id']])): 
                                $p_rx = $prescriptions_by_product[$item['product_id']];
                            ?>
                            <div style="margin-top: 5px; font-weight: 400; font-size: 0.85rem;">
                                <span style="background: #e0e7ff; color: #4338ca; padding: 2px 8px; border-radius: 4px; font-size: 0.75rem; border: 1px solid #c7d2fe;">
                                    <i class="fa-solid fa-glasses"></i> <?= htmlspecialchars($p_rx['lens_name']) ?>
                                </span>
                                <a href="javascript:void(0)" onclick='showRxModal(<?= json_encode($p_rx) ?>)' style="margin-left: 8px; color: #e31e24; text-decoration: none; font-weight: 600; font-size: 0.75rem;">
                                    <i class="fa-solid fa-eye"></i> View RX
                                </a>
                                <?php if($p_rx['lens_price'] > 0): ?>
                                <div style="color: #64748b; font-size: 0.75rem; margin-top: 2px;">+ Lens cost included in total</div>
                                <?php endif; ?>
                            </div>
                            <?php endif; ?>
                        </td>
                        <td style="padding-top: 1.5rem; color: var(--admin-text-light); font-weight: 500; background: none; border: none;">₹<?= number_format($item['price'], 2) ?></td>
                        <td style="padding-top: 1.5rem; font-weight: 600; color: #94a3b8; background: none; border: none;">x<?= $item['quantity'] ?></td>
                        <td style="padding-top: 1.5rem; text-align: right; font-weight: 800; color: var(--admin-sidebar); font-size: 1.1rem; background: none; border: none;">₹<?= number_format($item['price'] * $item['quantity'], 2) ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        
        <div style="margin-top: 3rem; padding-top: 2rem; border-top: 1px solid var(--admin-border); display: flex; justify-content: flex-end;">
            <div style="text-align: right;">
                <?php 
                $lensTotal = 0;
                if (!empty($prescriptions_by_product)) {
                    foreach($prescriptions_by_product as $rx) $lensTotal += $rx['lens_price']; 
                }
                if ($lensTotal > 0):
                ?>
                <p style="color: #4338ca; font-size: 0.9rem; font-weight: 600; margin-bottom: 0.25rem;">Lens Charges: +₹<?= number_format($lensTotal, 2) ?></p>
                <?php endif; ?>
                <p style="color: var(--admin-text-light); font-size: 0.85rem; font-weight: 600; margin-bottom: 0.5rem; text-transform: uppercase; letter-spacing: 0.05em;"><?= __('grand_total') ?></p>
                <h1 style="color: var(--admin-sidebar); font-weight: 900; margin: 0; font-size: 2.5rem; letter-spacing: -1px;">₹<?= number_format($order['total_amount'], 2) ?></h1>
            </div>
        </div>
    </div>

    <!-- Right Column: Info & Actions -->
    <div>
        <!-- Customer Info -->
        <div class="card" style="margin-bottom: 2rem;">
            <h3 class="card-title"><?= __('customer_info') ?></h3>
            <div class="info-list">
                <div class="info-item">
                    <span class="info-label"><?= __('customer') ?></span>
                    <span class="info-value"><?= htmlspecialchars($order['customer_name'] ?? $order['name']) ?></span>
                </div>
                <div class="info-item">
                    <span class="info-label"><?= __('mobile_number') ?: 'Contact Number' ?></span>
                    <span class="info-value"><?= htmlspecialchars($order['phone'] ?? 'N/A') ?></span>
                </div>
                <div class="info-item">
                    <span class="info-label"><?= __('email_mobile') ?: 'Email Address' ?></span>
                    <span class="info-value email"><?= htmlspecialchars($order['email']) ?></span>
                </div>
                <div class="info-item" style="margin-bottom: 0;">
                    <span class="info-label"><?= __('order_date') ?></span>
                    <span class="info-value"><?= date('M d, Y | h:i A', strtotime($order['created_at'])) ?></span>
                </div>
            </div>
        </div>

        <!-- Payment Info -->
        <div class="card" style="margin-bottom: 2rem;">
            <h3 class="card-title"><?= __('payment_info') ?></h3>
            <div class="info-list">
                <div class="info-item">
                    <span class="info-label"><?= __('payment_info') ?: 'Payment Method' ?></span>
                    <span class="info-value"><?= htmlspecialchars(ucwords(str_replace('_', ' ', $order['payment_method']))) ?></span>
                </div>
                <div class="info-item">
                    <span class="info-label"><?= __('payment_status') ?></span>
                    <span class="info-value">
                        <?php
                        $statusColors = [
                            'paid' => '#10b981',
                            'pending' => '#f59e0b',
                            'failed' => '#ef4444',
                            'refunded' => '#64748b'
                        ];
                        $status = $order['payment_status'] ?? 'pending';
                        $color = $statusColors[$status] ?? '#64748b';
                        ?>
                        <span style="color: <?= $color ?>; font-weight: 600;">
                            <i class="fa-solid fa-circle" style="font-size: 0.6rem;"></i>
                            <?= ucfirst($status) ?>
                        </span>
                    </span>
                </div>
                <?php if ($order['payment_method'] === 'razorpay' && !empty($order['razorpay_payment_id'])): ?>
                <div class="info-item">
                    <span class="info-label">Razorpay ID</span>
                    <span class="info-value" style="font-family: monospace; font-size: 0.85rem;"><?= htmlspecialchars($order['razorpay_payment_id']) ?></span>
                </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Shipping Info & Actions -->
        <div class="card">
            <h3 class="card-title"><?= __('shipping_info') ?></h3>
            <div class="info-list" style="margin-bottom: 1.5rem;">
                <?php if ($order['shipping_method_name']): ?>
                <div class="info-item">
                    <span class="info-label"><?= __('shipping_method') ?: 'Shipping Method' ?></span>
                    <span class="info-value"><?= htmlspecialchars($order['shipping_method_name']) ?></span>
                </div>
                <?php endif; ?>
                <?php if ($order['tracking_number']): ?>
                <div class="info-item">
                    <span class="info-label"><?= __('tracking_number') ?></span>
                    <span class="info-value" style="font-family: monospace; font-weight: 600;"><?= htmlspecialchars($order['tracking_number']) ?></span>
                </div>
                <?php endif; ?>
                <?php if ($order['courier_name']): ?>
                <div class="info-item">
                    <span class="info-label"><?= __('courier') ?></span>
                    <span class="info-value"><?= htmlspecialchars($order['courier_name']) ?></span>
                </div>
                <?php endif; ?>
            </div>

            <div style="background: #f8fafc; padding: 1.5rem; border-radius: 12px; border: 1px solid #f1f5f9; color: #475569; position: relative; margin-bottom: 1.5rem;">
                <i class="fa-solid fa-map-marker-alt" style="margin-bottom: 1rem; color: var(--admin-text-light); font-size: 1.1rem;"></i>
                <p style="line-height: 1.6; font-weight: 500;"><?= nl2br(htmlspecialchars($order['address'])) ?></p>
            </div>
            
            <form method="POST" style="display: flex; gap: 1rem; width: 100%; flex-wrap: wrap;">
                <a href="invoice.php?id=<?= $order['id'] ?>" target="_blank" class="btn btn-secondary" style="flex: 1; justify-content: center; font-size: 0.85rem; text-decoration: none; min-width: 140px;">
                    <i class="fa-solid fa-print" style="margin-right: 0.5rem;"></i> <?= __('print_invoice') ?>
                </a>
                
                <?php if ($order['payment_status'] === 'paid' && empty($order['shiprocket_shipment_id'])): ?>
                    <a href="../api/shipping/create_shipment.php?order_id=<?= $order['id'] ?>" class="btn btn-primary" style="flex: 1; justify-content: center; font-size: 0.85rem; background: #f59e0b; text-decoration: none; min-width: 140px;">
                        <i class="fa-solid fa-rocket" style="margin-right: 0.5rem;"></i> Create Shipment
                    </a>
                <?php elseif ($order['tracking_url']): ?>
                    <a href="<?= htmlspecialchars($order['tracking_url']) ?>" target="_blank" class="btn btn-primary" style="flex: 1; justify-content: center; font-size: 0.85rem; background: #3b82f6; text-decoration: none; min-width: 140px;">
                        <i class="fa-solid fa-map-location-dot" style="margin-right: 0.5rem;"></i> <?= __('track_shipment') ?>
                    </a>
                <?php endif; ?>
                
                <?php if ($order['status'] == 'pending'): ?>
                    <button type="submit" name="ship_now" class="btn btn-primary" style="flex: 1; justify-content: center; font-size: 0.85rem; background: var(--admin-primary); min-width: 140px;">
                        <?= __('ship_now') ?>
                    </button>
                <?php else: ?>
                    <button type="button" class="btn" style="flex: 1; justify-content: center; font-size: 0.85rem; background: #f1f5f9; color: #94a3b8; cursor: default; min-width: 140px;" disabled>
                        <i class="fa-solid fa-check" style="margin-right: 0.5rem;"></i> <?= ucfirst($order['status']) ?>
                    </button>
                <?php endif; ?>
            </form>
        </div>
    </div>
</div>

<?php require_once 'footer.php'; ?>

<!-- RX Modal -->
<div class="modal fade" id="rxModal" tabindex="-1" aria-hidden="true" style="display: none;">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content" style="border-radius: 16px; border: none; box-shadow: 0 10px 25px rgba(0,0,0,0.1);">
            <div class="modal-header" style="background: #e31e24; color: white; border-top-left-radius: 16px; border-top-right-radius: 16px;">
                <h5 class="modal-title" style="font-weight: 700; font-size: 1.1rem;"><i class="fa-solid fa-eye"></i> Prescription Details</h5>
                <button type="button" class="btn-close btn-close-white" onclick="closeRxModal()" aria-label="Close" style="filter: brightness(0) invert(1);"></button>
            </div>
            <div class="modal-body p-4">
                <div class="row g-3">
                    <!-- OD -->
                    <div class="col-12" style="margin-bottom: 1rem;">
                        <div style="background: #f8fafc; padding: 1rem; border-radius: 8px; border: 1px solid #e2e8f0;">
                            <div style="text-align: center; color: #e31e24; font-size: 0.75rem; font-weight: 700; letter-spacing: 1px; margin-bottom: 0.5rem;">RIGHT EYE (OD)</div>
                            <div style="display: grid; grid-template-columns: repeat(4, 1fr); gap: 10px; text-align: center;">
                                <div><div style="font-size: 10px; color: #64748b; margin-bottom: 2px;">SPH</div><strong id="admin_rx_od_sph"></strong></div>
                                <div><div style="font-size: 10px; color: #64748b; margin-bottom: 2px;">CYL</div><strong id="admin_rx_od_cyl"></strong></div>
                                <div><div style="font-size: 10px; color: #64748b; margin-bottom: 2px;">AXIS</div><strong id="admin_rx_od_axis"></strong></div>
                                <div><div style="font-size: 10px; color: #64748b; margin-bottom: 2px;">ADD</div><strong id="admin_rx_od_add"></strong></div>
                            </div>
                        </div>
                    </div>
                    <!-- OS -->
                    <div class="col-12">
                        <div style="background: #f8fafc; padding: 1rem; border-radius: 8px; border: 1px solid #e2e8f0;">
                            <div style="text-align: center; color: #e31e24; font-size: 0.75rem; font-weight: 700; letter-spacing: 1px; margin-bottom: 0.5rem;">LEFT EYE (OS)</div>
                            <div style="display: grid; grid-template-columns: repeat(4, 1fr); gap: 10px; text-align: center;">
                                <div><div style="font-size: 10px; color: #64748b; margin-bottom: 2px;">SPH</div><strong id="admin_rx_os_sph"></strong></div>
                                <div><div style="font-size: 10px; color: #64748b; margin-bottom: 2px;">CYL</div><strong id="admin_rx_os_cyl"></strong></div>
                                <div><div style="font-size: 10px; color: #64748b; margin-bottom: 2px;">AXIS</div><strong id="admin_rx_os_axis"></strong></div>
                                <div><div style="font-size: 10px; color: #64748b; margin-bottom: 2px;">ADD</div><strong id="admin_rx_os_add"></strong></div>
                            </div>
                        </div>
                    </div>
                    <div class="col-12 text-center" style="margin-top: 1rem;">
                        <span style="background: #1e293b; color: white; padding: 5px 15px; border-radius: 20px; font-size: 0.85rem;">PD: <span id="admin_rx_pd" style="font-weight: 700;"></span></span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<script>
function showRxModal(rx) {
    ['od_sph','od_cyl','od_axis','od_add','os_sph','os_cyl','os_axis','os_add','pd'].forEach(f => {
        const el = document.getElementById('admin_rx_'+f);
        if(el) el.innerText = rx[f] || '-';
    });
    
    // Simple custom modal show since we might not have bootstrap js loaded in admin everywhere?
    // Assuming bootstrap is loaded based on classes. 
    // If not, fallback to style display
    const modal = document.getElementById('rxModal');
    modal.style.display = 'block';
    modal.classList.add('show');
    document.body.appendChild(modal); // Ensure it's on top if needed
    
    // Add backdrop
    if(!document.getElementById('modalBackdrop')) {
        const bd = document.createElement('div');
        bd.id = 'modalBackdrop';
        bd.style.cssText = 'position:fixed;top:0;left:0;width:100%;height:100%;background:rgba(0,0,0,0.5);z-index:1040;';
        bd.onclick = closeRxModal;
        document.body.appendChild(bd);
        modal.style.zIndex = '1050';
    }
}

function closeRxModal() {
    const modal = document.getElementById('rxModal');
    modal.style.display = 'none';
    modal.classList.remove('show');
    const bd = document.getElementById('modalBackdrop');
    if(bd) bd.remove();
}
</script>
