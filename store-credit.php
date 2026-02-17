<?php
// store-credit.php
require_once 'includes/header.php';

if (!isLoggedIn()) redirect('login.php');

$user_id = $_SESSION['user_id'];

// Fetch Balance
$stmt = $pdo->prepare("SELECT store_credit FROM users WHERE id = ?");
$stmt->execute([$user_id]);
$balance = $stmt->fetchColumn() ?: 0.00;

// Fetch Transactions
$t_stmt = $pdo->prepare("SELECT * FROM store_credit_transactions WHERE user_id = ? ORDER BY created_at DESC");
$t_stmt->execute([$user_id]);
$transactions = $t_stmt->fetchAll(PDO::FETCH_ASSOC);

?>
<link rel="stylesheet" href="<?= getBaseURL() ?>assets/css/account.css?v=<?= time() ?>">

<div class="account-container">
    <div class="container">
        <div class="account-grid">
            <?php require_once 'includes/account-sidebar.php'; ?>
            
            <div class="dashboard-content">
                <div class="section-header">
                    <h2>My Store Credit</h2>
                </div>
                
                <!-- Balance Card -->
                <div class="stats-grid mb-4">
                    <div class="stat-card" style="border-left: 4px solid var(--primary);">
                        <div class="stat-info">
                            <span>Available Balance</span>
                            <strong style="font-size: 28px;">₹<?= number_format($balance, 2) ?></strong>
                        </div>
                        <div class="stat-icon text-green-500">
                            <i class="fa-solid fa-wallet"></i>
                        </div>
                    </div>
                </div>

                <!-- Transaction History -->
                <div class="recent-orders-section">
                    <h3 class="text-sm font-bold text-gray-700 mb-3 uppercase">Transaction History</h3>
                    
                    <?php if (!empty($transactions)): ?>
                    <div class="table-responsive">
                        <table class="orders-table">
                            <thead>
                                <tr>
                                    <th>Date</th>
                                    <th>Description</th>
                                    <th>Type</th>
                                    <th class="text-right">Amount</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($transactions as $t): 
                                    $is_credit = strtolower($t['type']) === 'credit';
                                    $amount_color = $is_credit ? 'text-green-600' : 'text-red-500';
                                    $sign = $is_credit ? '+' : '-';
                                ?>
                                <tr>
                                    <td><?= date('d M Y, h:i A', strtotime($t['created_at'])) ?></td>
                                    <td><?= htmlspecialchars($t['description']) ?></td>
                                    <td>
                                        <span class="badge <?= $is_credit ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800' ?> px-2 py-1 rounded text-xs font-bold">
                                            <?= ucfirst($t['type']) ?>
                                        </span>
                                    </td>
                                    <td class="text-right <?= $amount_color ?> font-bold">
                                        <?= $sign ?> ₹<?= number_format($t['amount'], 2) ?>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                    <?php else: ?>
                        <div class="bg-gray-50 p-5 rounded border text-center">
                            <i class="fa-solid fa-receipt text-gray-300 text-4xl mb-2"></i>
                            <p class="text-gray-500 text-sm">No transaction history found.</p>
                        </div>
                    <?php endif; ?>
                </div>

            </div>
        </div>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>
