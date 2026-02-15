<?php
require_once 'header.php';
require_once '../includes/IntegrationManager.php';

$integrationManager = IntegrationManager::getInstance($pdo);

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_gateway'])) {
    $serviceName = $_POST['service_name'];
    $serviceType = 'payment';
    
    // Build config array based on gateway
    $config = [];
    if ($serviceName === 'razorpay') {
        $config = [
            'key_id' => $_POST['razorpay_key_id'] ?? '',
            'key_secret' => $_POST['razorpay_key_secret'] ?? '',
            'webhook_secret' => $_POST['razorpay_webhook_secret'] ?? ''
        ];
    } elseif ($serviceName === 'stripe') {
        $config = [
            'publishable_key' => $_POST['stripe_publishable_key'] ?? '',
            'secret_key' => $_POST['stripe_secret_key'] ?? '',
            'webhook_secret' => $_POST['stripe_webhook_secret'] ?? ''
        ];
    } elseif ($serviceName === 'payu') {
        $config = [
            'merchant_key' => $_POST['payu_merchant_key'] ?? '',
            'merchant_salt' => $_POST['payu_merchant_salt'] ?? ''
        ];
    }
    
    $options = [
        'is_enabled' => isset($_POST['is_enabled']) ? 1 : 0,
        'is_test_mode' => isset($_POST['is_test_mode']) ? 1 : 0,
        'display_name' => $_POST['display_name'] ?? ucfirst($serviceName)
    ];
    
    $result = $integrationManager->saveIntegration($serviceName, $serviceType, $config, $options);
    
    if ($result) {
        setFlash('success', 'Payment gateway configuration saved successfully!');
    } else {
        setFlash('error', 'Failed to save configuration. Please try again.');
    }
    
    header('Location: integrations_payment.php?gateway=' . $serviceName);
    exit;
}

// Handle test connection
if (isset($_GET['test']) && isset($_GET['gateway'])) {
    $testResult = $integrationManager->testConnection($_GET['gateway']);
    echo json_encode($testResult);
    exit;
}

// Get gateway from URL or default to razorpay
$currentGateway = $_GET['gateway'] ?? 'razorpay';
$gatewayConfig = $integrationManager->getIntegration($currentGateway);
$allGateways = $integrationManager->getIntegrationsByType('payment');
?>

<style>
/* Using Design System Variables */
.gateway-sidebar {
    background: var(--admin-card);
    border-radius: var(--admin-radius);
    border: 2px solid var(--admin-border);
    padding: var(--space-6);
    height: fit-content;
    position: sticky;
    top: var(--space-8);
}

.gateway-list-item {
    display: flex;
    align-items: center;
    gap: var(--space-4);
    padding: var(--space-4);
    border-radius: var(--radius-lg);
    margin-bottom: var(--space-2);
    cursor: pointer;
    transition: var(--btn-transition);
    text-decoration: none;
    color: inherit;
}

.gateway-list-item:hover {
    background: var(--color-surface-hover);
}

.gateway-list-item.active {
    background: var(--admin-primary);
    color: var(--color-white);
}

.gateway-list-item.active .gateway-icon {
    background: rgba(255, 255, 255, 0.2);
    color: var(--color-white);
}

.gateway-icon {
    width: 40px;
    height: 40px;
    border-radius: var(--radius-md);
    display: flex;
    align-items: center;
    justify-content: center;
    background: var(--admin-primary-light);
    color: var(--admin-primary);
}

.gateway-info h4 {
    margin: 0;
    font-size: var(--font-size-base);
    font-weight: var(--font-weight-semibold);
}

.gateway-info .status {
    font-size: var(--font-size-xs);
    opacity: 0.8;
}

.config-section {
    background: var(--admin-card);
    border-radius: var(--admin-radius);
    border: 2px solid var(--admin-border);
    padding: var(--space-8);
}

.section-header {
    display: flex;
    align-items: center;
    gap: var(--space-4);
    margin-bottom: var(--space-8);
    padding-bottom: var(--space-4);
    border-bottom: 2px solid var(--admin-border);
}

.section-icon {
    width: 50px;
    height: 50px;
    border-radius: var(--radius-lg);
    display: flex;
    align-items: center;
    justify-content: center;
    background: var(--admin-primary);
    color: var(--color-white);
    font-size: var(--font-size-2xl);
}

.toggle-group {
    display: flex;
    gap: var(--space-8);
    margin: var(--space-6) 0;
    padding: var(--space-6);
    background: var(--color-bg-secondary);
    border-radius: var(--radius-xl);
}

.toggle-item {
    display: flex;
    align-items: center;
    gap: var(--space-4);
}

.webhook-url {
    background: var(--color-bg-secondary);
    padding: var(--space-4);
    border-radius: var(--radius-md);
    border: 1px solid var(--admin-border);
    font-family: var(--font-mono);
    font-size: var(--font-size-sm);
    word-break: break-all;
    margin-top: var(--space-2);
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.copy-btn {
    padding: var(--space-2) var(--space-4);
    background: var(--admin-primary);
    color: var(--color-white);
    border: none;
    border-radius: var(--radius-md);
    cursor: pointer;
    font-size: var(--font-size-sm);
    white-space: nowrap;
    font-weight: var(--font-weight-semibold);
    transition: var(--btn-transition);
}

.copy-btn:hover {
    opacity: 0.9;
    transform: translateY(-1px);
}
</style>

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h1 class="admin-title">Payment Gateway Configuration</h1>
        <p style="color: var(--admin-text-light); margin: var(--space-2) 0 0 0;">Configure and manage payment processing services</p>
    </div>
    <a href="integrations.php" class="btn btn-secondary">
        <i class="fa-solid fa-arrow-left"></i> Back to Integrations
    </a>
</div>

<div class="admin-grid" style="grid-template-columns: 300px 1fr; gap: var(--space-8);">
    <!-- Gateway Sidebar -->
    <div class="gateway-sidebar">
        <h3 style="margin: 0 0 var(--space-4) 0; font-size: var(--font-size-base); color: var(--admin-text-light); text-transform: uppercase; letter-spacing: var(--letter-spacing-wide);">Available Gateways</h3>
        <?php foreach ($allGateways as $gateway): ?>
            <a href="?gateway=<?= $gateway['service_name'] ?>" class="gateway-list-item <?= $currentGateway === $gateway['service_name'] ? 'active' : '' ?>">
                <div class="gateway-icon">
                    <?php
                    $icons = [
                        'razorpay' => 'fa-bolt',
                        'stripe' => 'fa-stripe-s',
                        'payu' => 'fa-dollar-sign',
                        'paytm' => 'fa-mobile-screen',
                        'cashfree' => 'fa-wallet',
                        'cod' => 'fa-money-bill-wave'
                    ];
                    ?>
                    <i class="fa-solid <?= $icons[$gateway['service_name']] ?? 'fa-credit-card' ?>"></i>
                </div>
                <div class="gateway-info">
                    <h4><?= htmlspecialchars($gateway['display_name']) ?></h4>
                    <div class="status">
                        <i class="fa-solid fa-circle" style="font-size: 0.5rem;"></i>
                        <?= $gateway['is_enabled'] ? 'Active' : 'Inactive' ?>
                    </div>
                </div>
            </a>
        <?php endforeach; ?>
    </div>

    <!-- Configuration Panel -->
    <div class="config-section">
        <div class="section-header">
            <div class="section-icon">
                <?php
                $currentIcon = $icons[$currentGateway] ?? 'fa-credit-card';
                ?>
                <i class="fa-solid <?= $currentIcon ?>"></i>
            </div>
            <div>
                <h2 style="margin: 0; font-size: var(--font-size-2xl); font-weight: var(--font-weight-bold);"><?= htmlspecialchars($gatewayConfig['display_name'] ?? ucfirst($currentGateway)) ?> Configuration</h2>
                <p style="margin: var(--space-1) 0 0 0; color: var(--admin-text-light);">Enter your API credentials and configure settings</p>
            </div>
        </div>

        <form method="POST" id="gatewayForm">
            <input type="hidden" name="service_name" value="<?= $currentGateway ?>">
            
            <!-- Status Toggles -->
            <div class="toggle-group">
                <div class="toggle-item">
                    <label class="toggle-switch">
                        <input type="checkbox" name="is_enabled" <?= ($gatewayConfig['is_enabled'] ?? 0) ? 'checked' : '' ?>>
                        <span class="toggle-slider"></span>
                    </label>
                    <div>
                        <strong style="color: var(--admin-text);">Enable Gateway</strong>
                        <div style="font-size: var(--font-size-sm); color: var(--admin-text-light);">Allow customers to use this payment method</div>
                    </div>
                </div>

                <?php if ($currentGateway !== 'cod'): ?>
                <div class="toggle-item">
                    <label class="toggle-switch">
                        <input type="checkbox" name="is_test_mode" <?= ($gatewayConfig['is_test_mode'] ?? 1) ? 'checked' : '' ?>>
                        <span class="toggle-slider"></span>
                    </label>
                    <div>
                        <strong style="color: var(--admin-text);">Test Mode</strong>
                        <div style="font-size: var(--font-size-sm); color: var(--admin-text-light);">Use test credentials (sandbox)</div>
                    </div>
                </div>
                <?php endif; ?>
            </div>

            <?php if ($currentGateway === 'razorpay'): ?>
                <!-- Razorpay Configuration -->
                <div class="form-group">
                    <label>Display Name</label>
                    <input type="text" name="display_name" value="<?= htmlspecialchars($gatewayConfig['display_name'] ?? 'Razorpay') ?>">
                </div>

                <div class="form-group">
                    <label>API Key ID</label>
                    <input type="text" name="razorpay_key_id" value="<?= htmlspecialchars($gatewayConfig['config']['key_id'] ?? '') ?>" 
                           placeholder="rzp_test_xxxxxxxxxxxxx">
                    <small>Get this from your Razorpay Dashboard → Settings → API Keys</small>
                </div>

                <div class="form-group">
                    <label>API Key Secret</label>
                    <input type="password" name="razorpay_key_secret" value="<?= htmlspecialchars($gatewayConfig['config']['key_secret'] ?? '') ?>" 
                           placeholder="Enter your API secret">
                    <small>Keep this secret secure. Never share it publicly.</small>
                </div>

                <div class="form-group">
                    <label>Webhook Secret (Optional)</label>
                    <input type="text" name="razorpay_webhook_secret" value="<?= htmlspecialchars($gatewayConfig['config']['webhook_secret'] ?? '') ?>" 
                           placeholder="whsec_xxxxxxxxxxxxx">
                    <small>Used to verify webhook signatures from Razorpay</small>
                </div>

                <div class="form-group">
                    <label>Webhook URL</label>
                    <div class="webhook-url">
                        <span><?= getBaseURL() ?>api/webhooks/razorpay.php</span>
                        <button type="button" class="copy-btn" onclick="copyToClipboard('<?= getBaseURL() ?>api/webhooks/razorpay.php')">
                            <i class="fa-solid fa-copy"></i> Copy
                        </button>
                    </div>
                    <small>Add this URL to your Razorpay Dashboard → Webhooks</small>
                </div>

            <?php elseif ($currentGateway === 'stripe'): ?>
                <!-- Stripe Configuration -->
                <div class="form-group">
                    <label>Display Name</label>
                    <input type="text" name="display_name" value="<?= htmlspecialchars($gatewayConfig['display_name'] ?? 'Stripe') ?>">
                </div>

                <div class="form-group">
                    <label>Publishable Key</label>
                    <input type="text" name="stripe_publishable_key" value="<?= htmlspecialchars($gatewayConfig['config']['publishable_key'] ?? '') ?>" 
                           placeholder="pk_test_xxxxxxxxxxxxx">
                </div>

                <div class="form-group">
                    <label>Secret Key</label>
                    <input type="password" name="stripe_secret_key" value="<?= htmlspecialchars($gatewayConfig['config']['secret_key'] ?? '') ?>" 
                           placeholder="sk_test_xxxxxxxxxxxxx">
                </div>

                <div class="form-group">
                    <label>Webhook Secret</label>
                    <input type="text" name="stripe_webhook_secret" value="<?= htmlspecialchars($gatewayConfig['config']['webhook_secret'] ?? '') ?>" 
                           placeholder="whsec_xxxxxxxxxxxxx">
                </div>

            <?php elseif ($currentGateway === 'payu'): ?>
                <!-- PayU Configuration -->
                <div class="form-group">
                    <label>Display Name</label>
                    <input type="text" name="display_name" value="<?= htmlspecialchars($gatewayConfig['display_name'] ?? 'PayU') ?>">
                </div>

                <div class="form-group">
                    <label>Merchant Key</label>
                    <input type="text" name="payu_merchant_key" value="<?= htmlspecialchars($gatewayConfig['config']['merchant_key'] ?? '') ?>">
                </div>

                <div class="form-group">
                    <label>Merchant Salt</label>
                    <input type="password" name="payu_merchant_salt" value="<?= htmlspecialchars($gatewayConfig['config']['merchant_salt'] ?? '') ?>">
                </div>

            <?php elseif ($currentGateway === 'cod'): ?>
                <!-- Cash on Delivery -->
                <div class="form-group">
                    <label>Display Name</label>
                    <input type="text" name="display_name" value="<?= htmlspecialchars($gatewayConfig['display_name'] ?? 'Cash on Delivery') ?>">
                </div>

                <div style="padding: var(--space-6); background: var(--color-success-light); border: 2px solid var(--color-success); border-radius: var(--radius-xl);">
                    <h4 style="margin: 0 0 var(--space-2) 0; color: var(--color-success-dark);">
                        <i class="fa-solid fa-circle-info"></i> Cash on Delivery
                    </h4>
                    <p style="margin: 0; color: var(--color-success-dark);">
                        No API configuration needed. Enable this option to allow customers to pay with cash when they receive their order.
                    </p>
                </div>

            <?php else: ?>
                <div class="form-group">
                    <label>Display Name</label>
                    <input type="text" name="display_name" value="<?= htmlspecialchars($gatewayConfig['display_name'] ?? ucfirst($currentGateway)) ?>">
                </div>

                <div style="padding: var(--space-6); background: var(--color-warning-light); border: 2px solid var(--color-warning); border-radius: var(--radius-xl);">
                    <p style="margin: 0; color: var(--color-warning-dark);">
                        <i class="fa-solid fa-triangle-exclamation"></i> This payment gateway integration is coming soon!
                    </p>
                </div>
            <?php endif; ?>

            <!-- Action Buttons -->
            <div style="display: flex; gap: var(--space-4); margin-top: var(--space-8); padding-top: var(--space-8); border-top: 2px solid var(--admin-border);">
                <button type="submit" name="save_gateway" class="btn btn-primary" style="padding: var(--space-4) var(--space-8); font-size: var(--font-size-base);">
                    <i class="fa-solid fa-save"></i> Save Configuration
                </button>

                <?php if ($currentGateway !== 'cod' && !empty($gatewayConfig['config'])): ?>
                <button type="button" class="btn btn-secondary" style="padding: var(--space-4) var(--space-8); font-size: var(--font-size-base);" onclick="testConnection()">
                    <i class="fa-solid fa-vial"></i> Test Connection
                </button>
                <?php endif; ?>
            </div>
        </form>
    </div>
</div>

<script>
function copyToClipboard(text) {
    navigator.clipboard.writeText(text).then(() => {
        showToast('Webhook URL copied to clipboard!', 'success');
    });
}

function testConnection() {
    const btn = event.target;
    const originalText = btn.innerHTML;
    btn.disabled = true;
    btn.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Testing...';
    
    fetch('?test=1&gateway=<?= $currentGateway ?>')
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                showToast('Connection successful! ✓', 'success');
            } else {
                showToast('Connection failed: ' + data.message, 'error');
            }
        })
        .catch(error => {
            showToast('Test failed: ' + error.message, 'error');
        })
        .finally(() => {
            btn.disabled = false;
            btn.innerHTML = originalText;
        });
}
</script>

<?php require_once 'footer.php'; ?>
