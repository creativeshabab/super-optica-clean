<?php
require_once 'header.php';
require_once '../includes/IntegrationManager.php';

$integrationManager = IntegrationManager::getInstance($pdo);

// Handle Shiprocket configuration
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_shiprocket'])) {
    $config = [
        'email' => $_POST['shiprocket_email'] ?? '',
        'password' => $_POST['shiprocket_password'] ?? ''
    ];
    
    $options = [
        'is_enabled' => isset($_POST['is_enabled']) ? 1 : 0,
        'is_test_mode' => isset($_POST['is_test_mode']) ? 1 : 0,
        'display_name' => 'Shiprocket'
    ];
    
    $result = $integrationManager->saveIntegration('shiprocket', 'shipping', $config, $options);
    
    if ($result) {
        setFlash('success', 'Shiprocket configuration saved successfully!');
    } else {
        setFlash('error', 'Failed to save configuration.');
    }
    
    header('Location: integrations_shipping.php');
    exit;
}

// Handle shipping method save
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_method'])) {
   $stmt = $pdo->prepare("
        INSERT INTO shipping_methods (name, method_type, base_cost, cost_per_kg, is_enabled, min_order_amount, estimated_days)
        VALUES (?, ?, ?, ?, ?, ?, ?)
        ON DUPLICATE KEY UPDATE
            name = VALUES(name),
            method_type = VALUES(method_type),
            base_cost = VALUES(base_cost),
            cost_per_kg = VALUES(cost_per_kg),
            is_enabled = VALUES(is_enabled),
            min_order_amount = VALUES(min_order_amount),
            estimated_days = VALUES(estimated_days)
    ");
    
    $stmt->execute([
        $_POST['method_name'],
        $_POST['method_type'],
        $_POST['base_cost'] ?? 0,
        $_POST['cost_per_kg'] ?? 0,
        isset($_POST['is_enabled']) ? 1 : 0,
        $_POST['min_order_amount'] ?? 0,
        $_POST['estimated_days'] ?? ''
    ]);
    
    setFlash('success', 'Shipping method saved successfully!');
    header('Location: integrations_shipping.php');
    exit;
}

// Handle toggle method
if (isset($_GET['toggle_method'])) {
    $methodId = $_GET['toggle_method'];
    $stmt = $pdo->prepare("UPDATE shipping_methods SET is_enabled = NOT is_enabled WHERE id = ?");
    $stmt->execute([$methodId]);
    setFlash('success', 'Shipping method updated!');
    header('Location: integrations_shipping.php');
    exit;
}

$shiprocketConfig = $integrationManager->getIntegration('shiprocket');
$shippingMethods = $integrationManager->getShippingMethods();
?>

<style>
/* Using Design System Variables */
.tabs-container {
    background: var(--admin-card);
    border-radius: var(--admin-radius);
    border: 2px solid var(--admin-border);
    overflow: hidden;
}

.tabs {
    display: flex;
    border-bottom: 2px solid var(--admin-border);
}

.tab {
    flex: 1;
    padding: var(--space-5);
    text-align: center;
    cursor: pointer;
    font-weight: var(--font-weight-semibold);
    color: var(--admin-text-light);
    transition: var(--btn-transition);
    border: none;
    background: none;
}

.tab:hover {
    background: var(--color-surface-hover);
}

.tab.active {
    color: var(--admin-primary);
    background: var(--admin-card);
    position: relative;
}

.tab.active::after {
    content: '';
    position: absolute;
    bottom: 0;
    left: 0;
    right: 0;
    height: 3px;
    background: var(--admin-primary);
}

.tab-content {
    padding: var(--space-8);
    display: none;
}

.tab-content.active {
    display: block;
}

.method-card {
    background: var(--admin-card);
    border: 2px solid var(--admin-border);
    border-radius: var(--radius-xl);
    padding: var(--space-6);
    margin-bottom: var(--space-4);
    display: flex;
    justify-content: space-between;
    align-items: center;
    transition: var(--btn-transition);
}

.method-card:hover {
    border-color: var(--admin-primary);
    box-shadow: var(--shadow-primary-sm);
}

.method-info h4 {
    margin: 0 0 var(--space-2) 0;
    color: var(--admin-text);
    font-weight: var(--font-weight-bold);
}

.method-details {
    display: flex;
    gap: var(--space-6);
    margin-top: var(--space-2);
}

.detail-item {
    font-size: var(--font-size-sm);
    color: var(--admin-text-light);
}

.detail-item strong {
    color: var(--admin-text);
}
</style>

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h1 class="admin-title">Shipping & Logistics</h1>
        <p style="color: var(--admin-text-light); margin: var(--space-2) 0 0 0;">Configure shipping carriers and delivery services</p>
    </div>
    <a href="integrations.php" class="btn btn-secondary">
        <i class="fa-solid fa-arrow-left"></i> Back to Integrations
    </a>
</div>

<div class="tabs-container">
    <div class="tabs">
        <button class="tab active" onclick="switchTab('shiprocket')">
            <i class="fa-solid fa-rocket"></i> Shiprocket Integration
        </button>
        <button class="tab" onclick="switchTab('methods')">
            <i class="fa-solid fa-truck"></i> Shipping Methods
        </button>
        <button class="tab" onclick="switchTab('zones')">
            <i class="fa-solid fa-earth-asia"></i> Shipping Zones
        </button>
    </div>

    <!-- Shiprocket Tab -->
    <div id="shiprocket" class="tab-content active">
        <div style="max-width: 800px;">
            <div style="background: var(--color-info-light); padding: var(--space-6); border-radius: var(--radius-xl); border: 2px solid var(--color-info); margin-bottom: var(--space-8);">
                <h3 style="margin: 0 0 var(--space-2) 0; color: var(--color-info-dark);">
                    <i class="fa-solid fa-info-circle"></i> About Shiprocket
                </h3>
                <p style="margin: 0; color: var(--color-info-dark);">
                    Shiprocket is an eCommerce shipping solution that helps you ship orders across India and internationally. 
                    Connect your Shiprocket account to automate order fulfillment, generate shipping labels, and track shipments.
                </p>
            </div>

            <form method="POST">
                <div style="display: flex; gap: var(--space-8); margin: var(--space-6) 0; padding: var(--space-6); background: var(--color-bg-secondary); border-radius: var(--radius-xl);">
                    <div style="display: flex; align-items: center; gap: var(--space-4);">
                        <label class="toggle-switch">
                            <input type="checkbox" name="is_enabled" <?= ($shiprocketConfig['is_enabled'] ?? 0) ? 'checked' : '' ?>>
                            <span class="toggle-slider"></span>
                        </label>
                        <div>
                            <strong style="color: var(--admin-text);">Enable Shiprocket</strong>
                            <div style="font-size: var(--font-size-sm); color: var(--admin-text-light);">Use Shiprocket for order fulfillment</div>
                        </div>
                    </div>

                    <div style="display: flex; align-items: center; gap: var(--space-4);">
                        <label class="toggle-switch">
                            <input type="checkbox" name="is_test_mode" <?= ($shiprocketConfig['is_test_mode'] ?? 1) ? 'checked' : '' ?>>
                            <span class="toggle-slider"></span>
                        </label>
                        <div>
                            <strong style="color: var(--admin-text);">Test Mode</strong>
                            <div style="font-size: var(--font-size-sm); color: var(--admin-text-light);">Use test credentials</div>
                        </div>
                    </div>
                </div>

                <div class="form-group">
                    <label>Shiprocket Email</label>
                    <input type="email" name="shiprocket_email" value="<?= htmlspecialchars($shiprocketConfig['config']['email'] ?? '') ?>" 
                           placeholder="your@email.com" required>
                    <small>Email address associated with your Shiprocket account</small>
                </div>

                <div class="form-group">
                    <label>Shiprocket Password</label>
                    <input type="password" name="shiprocket_password" value="<?= htmlspecialchars($shiprocketConfig['config']['password'] ?? '') ?>" 
                           placeholder="••••••••" required>
                    <small>Your Shiprocket account password</small>
                </div>

                <div style="margin-top: var(--space-8); padding-top: var(--space-8); border-top: 2px solid var(--admin-border);">
                    <button type="submit" name="save_shiprocket" class="btn btn-primary" style="padding: var(--space-4) var(--space-8); font-size: var(--font-size-base);">
                        <i class="fa-solid fa-save"></i> Save Configuration
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Shipping Methods Tab -->
    <div id="methods" class="tab-content">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: var(--space-8);">
            <h3 style="margin: 0; font-weight: var(--font-weight-bold);">Available Shipping Methods</h3>
            <button class="btn btn-primary" onclick="document.getElementById('addMethodModal').style.display='block'">
                <i class="fa-solid fa-plus"></i> Add Method
            </button>
        </div>

        <?php foreach ($shippingMethods as $method): ?>
            <div class="method-card">
                <div class="method-info">
                    <h4><?= htmlspecialchars($method['name']) ?></h4>
                    <div class="method-details">
                        <div class="detail-item">
                            <strong>Type:</strong> <?= ucwords(str_replace('_', ' ', $method['method_type'])) ?>
                        </div>
                        <div class="detail-item">
                            <strong>Base Cost:</strong> ₹<?= number_format($method['base_cost'], 2) ?>
                        </div>
                        <?php if ($method['cost_per_kg'] > 0): ?>
                            <div class="detail-item">
                                <strong>Per KG:</strong> ₹<?= number_format($method['cost_per_kg'], 2) ?>
                            </div>
                        <?php endif; ?>
                        <div class="detail-item">
                            <strong>Delivery:</strong> <?= htmlspecialchars($method['estimated_days']) ?>
                        </div>
                    </div>
                </div>
                <div style="display: flex; gap: var(--space-2); align-items: center;">
                    <label class="toggle-switch">
                        <input type="checkbox" <?= $method['is_enabled'] ? 'checked' : '' ?> 
                               onchange="window.location.href='?toggle_method=<?= $method['id'] ?>'">
                        <span class="toggle-slider"></span>
                    </label>
                    <span style="font-size: var(--font-size-sm); color: var(--admin-text-light);">
                        <?= $method['is_enabled'] ? 'Enabled' : 'Disabled' ?>
                    </span>
                </div>
            </div>
        <?php endforeach; ?>
    </div>

    <!-- Shipping Zones Tab -->
    <div id="zones" class="tab-content">
        <div style="background: var(--color-warning-light); padding: var(--space-6); border-radius: var(--radius-xl); border: 2px solid var(--color-warning);">
            <h3 style="margin: 0 0 var(--space-2) 0; color: var(--color-warning-dark);">
                <i class="fa-solid fa-wrench"></i> Coming Soon
            </h3>
            <p style="margin: 0; color: var(--color-warning-dark);">
                Zone-based shipping configuration will be available in the next update. 
                This will allow you to set different shipping rates for different geographic regions.
            </p>
        </div>
    </div>
</div>

<!-- Add Method Modal -->
<div id="addMethodModal" style="display: none; position: fixed; top: 0; left: 0; right: 0; bottom: 0; background: rgba(0,0,0,0.5); z-index: var(--z-modal); padding: var(--space-8);">
    <div style="background: var(--admin-card); max-width: 600px; margin: var(--space-8) auto; border-radius: var(--admin-radius); padding: var(--space-8); max-height: 90vh; overflow-y: auto;">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: var(--space-6);">
            <h2 style="margin: 0; color: var(--admin-text);">Add Shipping Method</h2>
            <button onclick="document.getElementById('addMethodModal').style.display='none'" style="background: none; border: none; font-size: var(--font-size-2xl); cursor: pointer; color: var(--admin-text-light);">×</button>
        </div>

        <form method="POST">
            <div class="form-group">
                <label>Method Name</label>
                <input type="text" name="method_name" placeholder="e.g., Express Shipping" required>
            </div>

            <div class="form-group">
                <label>Method Type</label>
                <select name="method_type" required>
                    <option value="flat_rate">Flat Rate</option>
                    <option value="free_shipping">Free Shipping</option>
                    <option value="local_pickup">Local Pickup</option>
                </select>
            </div>

            <div class="admin-grid" style="grid-template-columns: 1fr 1fr;">
                <div class="form-group">
                    <label>Base Cost (₹)</label>
                    <input type="number" name="base_cost" step="0.01" value="0">
                </div>

                <div class="form-group">
                    <label>Cost per KG (₹)</label>
                    <input type="number" name="cost_per_kg" step="0.01" value="0">
                </div>
            </div>

            <div class="form-group">
                <label>Minimum Order Amount (₹)</label>
                <input type="number" name="min_order_amount" step="0.01" value="0">
                <small>Minimum cart value required to use this method</small>
            </div>

            <div class="form-group">
                <label>Estimated Delivery Time</label>
                <input type="text" name="estimated_days" placeholder="e.g., 3-5 days">
            </div>

            <div class="form-group">
                <label style="display: flex; align-items: center; gap: var(--space-2);">
                    <input type="checkbox" name="is_enabled" checked>
                    Enable this method
                </label>
            </div>

            <div style="display: flex; gap: var(--space-4); margin-top: var(--space-8);">
                <button type="submit" name="save_method" class="btn btn-primary">
                    <i class="fa-solid fa-save"></i> Save Method
                </button>
                <button type="button" class="btn btn-secondary" onclick="document.getElementById('addMethodModal').style.display='none'">
                    Cancel
                </button>
            </div>
        </form>
    </div>
</div>

<script>
function switchTab(tabName) {
    // Hide all tabs
    document.querySelectorAll('.tab-content').forEach(content => {
        content.classList.remove('active');
    });
    document.querySelectorAll('.tab').forEach(tab => {
        tab.classList.remove('active');
    });
    
    // Show selected tab
    document.getElementById(tabName).classList.add('active');
    event.target.classList.add('active');
}
</script>

<?php require_once 'footer.php'; ?>
