<?php
require_once 'header.php';
require_once '../includes/IntegrationManager.php';

// Enable error display for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

$integrationManager = IntegrationManager::getInstance($pdo);

// Get all integrations by type
$paymentGateways = $integrationManager->getIntegrationsByType('payment');
$shippingServices = $integrationManager->getIntegrationsByType('shipping');

// Debug output (remove after fixing)
if (isset($_GET['debug'])) {
    echo "<pre>";
    echo "Payment Gateways Count: " . count($paymentGateways) . "\n";
    print_r($paymentGateways);
    echo "\nShipping Services Count: " . count($shippingServices) . "\n";
    print_r($shippingServices);
    echo "</pre>";
    exit;
}
?>

<style>
/* Using Design System Variables */
.integrations-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
    gap: var(--space-6);
    margin-bottom: var(--space-8);
}

.integration-card {
    background: var(--admin-card);
    border-radius: var(--admin-radius);
    border: 2px solid var(--admin-border);
    padding: var(--space-6);
    transition: var(--btn-transition);
    cursor: pointer;
}

.integration-card:hover {
    border-color: var(--admin-primary);
    box-shadow: var(--shadow-primary-md);
    transform: translateY(-2px);
}

.integration-header {
    display: flex;
    align-items: center;
    gap: var(--space-4);
    margin-bottom: var(--space-4);
}

.integration-icon {
    width: 48px;
    height: 48px;
    border-radius: var(--radius-lg);
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: var(--font-size-2xl);
    background: var(--admin-primary-light);
    color: var(--admin-primary);
}

.integration-icon.shipping {
    background: var(--color-warning-light);
    color: var(--color-warning);
}

.integration-info h3 {
    margin: 0;
    font-size: var(--font-size-lg);
    color: var(--admin-text);
    font-weight: var(--font-weight-bold);
}

.integration-info .status {
    font-size: var(--font-size-sm);
    margin-top: var(--space-1);
    display: flex;
    align-items: center;
    gap: var(--space-2);
}

.status.connected {
    color: var(--color-success);
}

.status.disconnected {
    color: var(--admin-text-light);
}

.integration-actions {
    display: flex;
    gap: var(--space-2);
    margin-top: var(--space-4);
}

.category-header {
    display: flex;
    align-items: center;
    gap: var(--space-4);
    margin-bottom: var(--space-6);
    margin-top: var(--space-12);
}

.category-header:first-of-type {
    margin-top: 0;
}

.category-icon {
    width: 40px;
    height: 40px;
    border-radius: var(--radius-lg);
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: var(--font-size-xl);
}

.category-icon.payment {
    background: var(--color-info-light);
    color: var(--color-info-dark);
}

.category-icon.shipping {
    background: var(--color-warning-light);
    color: var(--color-warning-dark);
}

.category-header h2 {
    margin: 0;
    font-size: var(--font-size-2xl);
    color: var(--admin-text);
    font-weight: var(--font-weight-bold);
}

.category-header p {
    margin: 0;
    color: var(--admin-text-light);
    font-size: var(--font-size-sm);
}

.badge-mode {
    display: inline-block;
    padding: var(--space-1) var(--space-3);
    border-radius: var(--radius-full);
    font-size: var(--font-size-xs);
   font-weight: var(--font-weight-semibold);
    margin-left: var(--space-2);
}

.badge-mode.test {
    background: var(--color-warning-light);
    color: var(--color-warning-dark);
}

.badge-mode.live {
    background: var(--color-success-light);
    color: var(--color-success-dark);
}
</style>

<div class="page-header">
    <div class="page-header-info">
        <h1 class="page-title">Integrations Hub</h1>
    </div>
</div>

<?php if ($flash = getFlash()): ?>
    <script>showToast("<?= addslashes($flash['message']) ?>", "<?= $flash['type'] ?>");</script>
<?php endif; ?>

<!-- Payment Gateways -->
<div class="category-header">
    <div class="category-icon payment">
        <i class="fa-solid fa-credit-card"></i>
    </div>
    <div>
        <h2>Payment Gateways</h2>
        <p>Manage payment processing services</p>
    </div>
</div>

<div class="integrations-grid">
    <?php foreach ($paymentGateways as $gateway): ?>
        <div class="integration-card" onclick="window.location.href='integrations_payment.php?gateway=<?= $gateway['service_name'] ?>'">
            <div class="integration-header">
                <div class="integration-icon">
                    <?php
                    $icons = [
                        'razorpay' => 'fa-bolt',
                        'stripe' => 'fa-stripe-s',
                        'payu' => 'fa-dollar-sign',
                        'paytm' => 'fa-mobile-screen',
                        'cashfree' => 'fa-wallet',
                        'cod' => 'fa-money-bill-wave'
                    ];
                    $icon = $icons[$gateway['service_name']] ?? 'fa-credit-card';
                    ?>
                    <i class="fa-solid <?= $icon ?>"></i>
                </div>
                <div class="integration-info">
                    <h3><?= htmlspecialchars($gateway['display_name']) ?></h3>
                    <div class="status <?= $gateway['is_enabled'] ? 'connected' : 'disconnected' ?>">
                        <i class="fa-solid fa-circle" style="font-size: 0.5rem;"></i>
                        <?= $gateway['is_enabled'] ? 'Active' : 'Inactive' ?>
                        <?php if ($gateway['is_enabled'] && $gateway['service_name'] !== 'cod'): ?>
                            <span class="badge-mode <?= $gateway['is_test_mode'] ? 'test' : 'live' ?>">
                                <?= $gateway['is_test_mode'] ? 'TEST' : 'LIVE' ?>
                            </span>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            
            <div class="integration-actions">
                <button class="btn btn-primary" style="flex: 1;" onclick="event.stopPropagation(); window.location.href='integrations_payment.php?gateway=<?= $gateway['service_name'] ?>'">
                    <i class="fa-solid fa-gear"></i> Configure
                </button>
            </div>
        </div>
    <?php endforeach; ?>
</div>

<!-- Shipping & Logistics -->
<div class="category-header">
    <div class="category-icon shipping">
        <i class="fa-solid fa-truck-fast"></i>
    </div>
    <div>
        <h2>Shipping & Logistics</h2>
        <p>Manage shipping carriers and delivery services</p>
    </div>
</div>

<div class="integrations-grid">
    <?php foreach ($shippingServices as $service): ?>
        <div class="integration-card" onclick="window.location.href='integrations_shipping.php?service=<?= $service['service_name'] ?>'">
            <div class="integration-header">
                <div class="integration-icon shipping">
                    <i class="fa-solid fa-box"></i>
                </div>
                <div class="integration-info">
                    <h3><?= htmlspecialchars($service['display_name']) ?></h3>
                    <div class="status <?= $service['is_enabled'] ? 'connected' : 'disconnected' ?>">
                        <i class="fa-solid fa-circle" style="font-size: 0.5rem;"></i>
                        <?= $service['is_enabled'] ? 'Active' : 'Inactive' ?>
                        <?php if ($service['is_enabled']): ?>
                            <span class="badge-mode <?= $service['is_test_mode'] ? 'test' : 'live' ?>">
                                <?= $service['is_test_mode'] ? 'TEST' : 'LIVE' ?>
                            </span>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            
            <div class="integration-actions">
                <button class="btn btn-primary" style="flex: 1;" onclick="event.stopPropagation(); window.location.href='integrations_shipping.php?service=<?= $service['service_name'] ?>'">
                    <i class="fa-solid fa-gear"></i> Configure
                </button>
            </div>
        </div>
    <?php endforeach; ?>
    
    <!-- Manual Shipping Methods Card -->
    <div class="integration-card" onclick="window.location.href='integrations_shipping.php'">
        <div class="integration-header">
            <div class="integration-icon" style="background: var(--color-info-light); color: var(--color-info-dark);">
                <i class="fa-solid fa-sliders"></i>
            </div>
            <div class="integration-info">
                <h3>Shipping Methods</h3>
                <div class="status connected">
                    <i class="fa-solid fa-circle" style="font-size: 0.5rem;"></i>
                    Manual Configuration
                </div>
            </div>
        </div>
        
        <p style="color: var(--admin-text-light); font-size: var(--font-size-sm); margin: var(--space-2) 0;">
            Configure flat rate, free shipping, and local pickup options
        </p>
        
        <div class="integration-actions">
            <button class="btn btn-primary" style="flex: 1;" onclick="event.stopPropagation(); window.location.href='integrations_shipping.php'">
                <i class="fa-solid fa-gear"></i> Manage Methods
            </button>
        </div>
    </div>
</div>

<?php require_once 'footer.php'; ?>
