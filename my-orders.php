<?php
// my-orders.php - Full Order History
require_once 'includes/header.php';

if (!isLoggedIn()) redirect('login.php');

$user_id = $_SESSION['user_id'];

// Initial Page Layout
?>
<link rel="stylesheet" href="<?= getBaseURL() ?>assets/css/account.css?v=<?= time() ?>">

<div class="account-container">
    <div class="container">
        <div class="account-grid">
            <?php require_once 'includes/account-sidebar.php'; ?>
            
            <div class="dashboard-content">
                <div class="section-header">
                    <h2>My Order History</h2>
                </div>
                
                <?php
                // Fetch All Orders
                $stmt = $pdo->prepare("SELECT * FROM orders WHERE user_id = ? ORDER BY created_at DESC");
                $stmt->execute([$user_id]);
                $orders = $stmt->fetchAll(PDO::FETCH_ASSOC);
                ?>

                <?php if (!empty($orders)): ?>
                    <div class="table-responsive bg-white border rounded p-3">
                         <table class="orders-table">
                            <thead>
                                <tr>
                                    <th>Order ID</th>
                                    <th>Date</th>
                                    <th>Total</th>
                                    <th>Status</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($orders as $order): 
                                    $status_class = match(strtolower($order['status'])) {
                                        'completed', 'delivered' => 'status-completed',
                                        'processing', 'shipped' => 'status-processing',
                                        'cancelled' => 'status-cancelled',
                                        default => 'status-pending'
                                    };
                                ?>
                                <tr>
                                    <td>#SO-<?= str_pad($order['id'], 6, '0', STR_PAD_LEFT) ?></td>
                                    <td><?= date('d M Y', strtotime($order['created_at'])) ?></td>
                                    <td>₹<?= number_format($order['total_amount'], 2) ?></td>
                                    <td>
                                        <span class="status-badge <?= $status_class ?>">
                                            <?= ucfirst($order['status']) ?>
                                        </span>
                                    </td>
                                    <td>
                                        <a href="<?= getBaseURL() ?>view-order.php?id=<?= $order['id'] ?>" class="btn-view-details">
                                            View
                                        </a>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <div class="bg-white p-5 rounded border text-center">
                        <i class="fa-solid fa-box-open text-gray-300 text-5xl mb-3"></i>
                        <p class="text-gray-500">No orders found.</p>
                        <a href="shop.php" class="btn btn-primary mt-3">Browse Products</a>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>
