<?php
// profile.php - User Dashboard
require_once 'includes/header.php';

// Access Control
if (!isLoggedIn()) {
    redirect('login.php');
}

$user_id = $_SESSION['user_id'];
$user_name = $_SESSION['user_name'] ?? 'User';

// 1. Fetch Stats
// Total Orders
$stmt = $pdo->prepare("SELECT COUNT(*) FROM orders WHERE user_id = ?");
$stmt->execute([$user_id]);
$total_orders = $stmt->fetchColumn();

// Wishlist Count (Assuming 'wishlist' table exists, otherwise 0)
$total_wishlist = 0;
// Check if wishlist table exists to prevent crash
try {
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM wishlist WHERE user_id = ?");
    $stmt->execute([$user_id]);
    $total_wishlist = $stmt->fetchColumn();
} catch (PDOException $e) {
    // Table might not exist yet
    $total_wishlist = 0;
}

// Store Credit (Assuming column in users table, default 0)
$store_credit = 0;
// We'll check column existence in logic or just assume 0 for now if not found
// For audit safety, let's fetch user again to be sure
$stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$user_id]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);
$store_credit = $user['store_credit'] ?? 0;

// 2. Fetch Recent Orders (Limit 5)
$stmt = $pdo->prepare("SELECT * FROM orders WHERE user_id = ? ORDER BY created_at DESC LIMIT 5");
$stmt->execute([$user_id]);
$recent_orders = $stmt->fetchAll(PDO::FETCH_ASSOC);

?>

<link rel="stylesheet" href="<?= getBaseURL() ?>assets/css/account.css?v=<?= time() ?>">

<div class="account-container">
    <div class="container">
        <div class="account-grid">
            <!-- Sidebar -->
            <?php require_once 'includes/account-sidebar.php'; ?>

            <!-- Main Content -->
            <div class="dashboard-content">
                <div class="welcome-section">
                    <h1>Hello, <?= htmlspecialchars(explode(' ', $user_name)[0]) ?>!</h1>
                </div>

                <!-- Stats Grid -->
                <div class="stats-grid">
                    <div class="stat-card">
                        <div class="stat-info">
                            <span>Total Orders</span>
                            <strong><?= $total_orders ?></strong>
                        </div>
                        <div class="stat-icon">
                            <i class="fa-solid fa-bag-shopping"></i>
                        </div>
                    </div>

                    <div class="stat-card">
                        <div class="stat-info">
                            <span>Wishlist</span>
                            <strong><?= $total_wishlist ?></strong>
                        </div>
                        <div class="stat-icon">
                            <i class="fa-regular fa-heart"></i>
                        </div>
                    </div>

                    <div class="stat-card">
                        <div class="stat-info">
                            <span>Store Credit</span>
                            <strong>₹<?= number_format($store_credit, 2) ?></strong>
                        </div>
                        <div class="stat-icon">
                            <i class="fa-solid fa-wallet"></i>
                        </div>
                    </div>
                </div>

                <!-- Recent Orders -->
                <?php if (!empty($recent_orders)): ?>
                <div class="recent-orders-section">
                    <div class="section-header">
                        <h2>Recent Orders</h2>
                        <a href="<?= getBaseURL() ?>my-orders.php">View All</a>
                    </div>
                    
                    <div class="table-responsive">
                        <table class="orders-table">
                            <thead>
                                <tr>
                                    <th>Order ID</th>
                                    <th>Date</th>
                                    <th>Amount</th>
                                    <th>Status</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($recent_orders as $order): 
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
                                            Details
                                        </a>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
                <?php else: ?>
                    <div class="recent-orders-section">
                        <p class="text-gray-500">You haven't placed any orders yet.</p>
                        <a href="<?= getBaseURL() ?>shop.php" class="btn btn-primary mt-2">Start Shopping</a>
                    </div>
                <?php endif; ?>

            </div>
        </div>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>
