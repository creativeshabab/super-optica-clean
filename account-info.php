<?php
// account-info.php - Edit Profile
require_once 'includes/header.php';

if (!isLoggedIn()) redirect('login.php');

$user_id = $_SESSION['user_id'];
$message = '';
$error = '';

// Handle Form Submit
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!validateCSRFToken($_POST['csrf_token'] ?? '')) {
        $error = "Security check failed (CSRF).";
    } else {
        $name = trim($_POST['full_name']); // Form input name
        $phone = trim($_POST['phone']);
        
        if (empty($name) || empty($phone)) {
            $error = "Name and Phone are required.";
        } else {
            try {
                // Fixed: Column name is 'name', not 'full_name'
                $stmt = $pdo->prepare("UPDATE users SET name = ?, phone = ? WHERE id = ?");
                $stmt->execute([$name, $phone, $user_id]);
                
                // Update Session
                $_SESSION['user_name'] = $name;
                setFlash('success', "Profile updated successfully!");
                refresh();
            } catch (PDOException $e) {
                $error = "Update failed: " . $e->getMessage();
            }
        }
    }
}

// Fetch Current Data
$stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$user_id]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

?>
<link rel="stylesheet" href="<?= getBaseURL() ?>assets/css/account.css?v=<?= time() ?>">

<div class="account-container">
    <div class="container">
        <div class="account-grid">
            <?php require_once 'includes/account-sidebar.php'; ?>
            
            <div class="dashboard-content">
                <div class="section-header">
                    <h2>Account Information</h2>
                </div>
                
                <div class="bg-white border rounded p-5 max-w-lg">
                    <?php if ($error): ?>
                        <div class="bg-red-100 text-red-700 p-3 rounded mb-4"><?= htmlspecialchars($error) ?></div>
                    <?php endif; ?>
                    
                    <form method="POST" action="">
                        <?= csrfField() ?>
                        
                        <div class="mb-5">
                            <label class="form-label">Full Name</label>
                            <input type="text" name="full_name" value="<?= htmlspecialchars($user['name'] ?? '') ?>" class="form-input" placeholder="Enter your full name">
                        </div>
                        
                        <div class="mb-5">
                            <label class="form-label">Email Address</label>
                            <input type="email" value="<?= htmlspecialchars($user['email'] ?? '') ?>" class="form-input bg-gray-50 text-gray-500" readonly>
                            <small class="text-gray-400 mt-2 block"><i class="fa-solid fa-circle-info mr-1"></i> Email cannot be changed.</small>
                        </div>
                        
                        <div class="mb-6">
                            <label class="form-label">Phone Number</label>
                            <input type="text" name="phone" value="<?= htmlspecialchars($user['phone'] ?? '') ?>" class="form-input" placeholder="e.g. 9876543210">
                        </div>
                        
                        <button type="submit" class="btn btn-primary no-margin w-full">
                            Save Changes
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>
