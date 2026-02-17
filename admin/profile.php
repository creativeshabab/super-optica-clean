<?php 
require_once 'header.php';

// Fetch current admin data
$stmt = $pdo->prepare("SELECT * FROM users WHERE id = ? AND role = 'admin'");
$stmt->execute([$_SESSION['user_id']]);
$admin = $stmt->fetch();

if (!$admin) {
    redirect('index.php');
}

$success = '';
$error = '';

// Handle profile update
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $first_name = trim($_POST['first_name']);
    $last_name = trim($_POST['last_name']);
    $email = trim($_POST['email']);
    $current_password = $_POST['current_password'] ?? '';
    $new_password = $_POST['new_password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';
    
    // Validate inputs
    if (empty($first_name) || empty($email)) {
        $error = "First name and email are required.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = "Invalid email format.";
    } else {
        // Check if email is already taken by another user
        $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ? AND id != ?");
        $stmt->execute([$email, $_SESSION['user_id']]);
        if ($stmt->fetch()) {
            $error = "Email is already in use by another account.";
        } else {
            // Update basic info
            $stmt = $pdo->prepare("UPDATE users SET first_name = ?, last_name = ?, email = ? WHERE id = ?");
            $stmt->execute([$first_name, $last_name, $email, $_SESSION['user_id']]);
            
            // Update session
            $_SESSION['name'] = $first_name;
            $_SESSION['user_name'] = $first_name . ' ' . $last_name;
            $_SESSION['email'] = $email;
            
            $success = "Profile updated successfully!";
            
            // Handle password change if provided
            if (!empty($new_password)) {
                if (empty($current_password)) {
                    $error = "Current password is required to set a new password.";
                } elseif (!password_verify($current_password, $admin['password'])) {
                    $error = "Current password is incorrect.";
                } elseif (strlen($new_password) < 6) {
                    $error = "New password must be at least 6 characters.";
                } elseif ($new_password !== $confirm_password) {
                    $error = "New passwords do not match.";
                } else {
                    $hashed = password_hash($new_password, PASSWORD_DEFAULT);
                    $stmt = $pdo->prepare("UPDATE users SET password = ? WHERE id = ?");
                    $stmt->execute([$hashed, $_SESSION['user_id']]);
                    $success = "Profile and password updated successfully!";
                }
            }
            
            // Refresh admin data
            $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
            $stmt->execute([$_SESSION['user_id']]);
            $admin = $stmt->fetch();
        }
    }
}
?>

<div class="admin-container">
    <div style="max-width: 800px; margin: 0 auto;">
        <div class="page-header">
            <div class="page-header-info">
                <h1 class="page-title"><i class="fa-regular fa-user"></i> My Profile</h1>
                <p class="page-subtitle">Update your personal information and security settings.</p>
            </div>
        </div>
        
        <?php if ($success): ?>
            <div class="alert alert-success" style="background: rgba(16, 185, 129, 0.1); color: #10b981; padding: 1rem; border-radius: 12px; margin-bottom: 1.5rem; display: flex; align-items: center; gap: 0.75rem; border: 1px solid rgba(16, 185, 129, 0.2);">
                <i class="fa-solid fa-circle-check"></i>
                <span><?= htmlspecialchars($success) ?></span>
            </div>
        <?php endif; ?>
        
        <?php if ($error): ?>
            <div class="alert alert-danger" style="background: rgba(239, 68, 68, 0.1); color: #ef4444; padding: 1rem; border-radius: 12px; margin-bottom: 1.5rem; display: flex; align-items: center; gap: 0.75rem; border: 1px solid rgba(239, 68, 68, 0.2);">
                <i class="fa-solid fa-circle-exclamation"></i>
                <span><?= htmlspecialchars($error) ?></span>
            </div>
        <?php endif; ?>
        
        <div class="admin-table-widget" style="padding: 2rem;">
            <form method="POST" style="display: flex; flex-direction: column; gap: 1.5rem;">
                <!-- Profile Information Section -->
                <div>
                    <h3 style="font-size: 1.1rem; font-weight: 700; color: var(--admin-text); margin-bottom: 1.5rem; display: flex; align-items: center; gap: 0.75rem;">
                        <span style="width: 32px; height: 32px; background: var(--admin-primary-light); color: var(--admin-primary); border-radius: 8px; display: flex; align-items: center; justify-content: center;">
                            <i class="fa-solid fa-id-card"></i>
                        </span>
                        Profile Information
                    </h3>
                    
                    <div style="display: grid; gap: 1.25rem;">
                        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem;">
                            <div class="form-group">
                                <label for="first_name">First Name</label>
                                <input 
                                    type="text" 
                                    id="first_name" 
                                    name="first_name" 
                                    class="form-control" 
                                    value="<?= htmlspecialchars($admin['first_name'] ?? '') ?>" 
                                    required
                                    style="background: var(--admin-bg);"
                                >
                            </div>
                            <div class="form-group">
                                <label for="last_name">Last Name</label>
                                <input 
                                    type="text" 
                                    id="last_name" 
                                    name="last_name" 
                                    class="form-control" 
                                    value="<?= htmlspecialchars($admin['last_name'] ?? '') ?>" 
                                    style="background: var(--admin-bg);"
                                >
                            </div>
                        </div>
                        
                        <div class="form-group">
                            <label for="email">Email Address</label>
                            <input 
                                type="email" 
                                id="email" 
                                name="email" 
                                class="form-control" 
                                value="<?= htmlspecialchars($admin['email']) ?>" 
                                required
                                style="background: var(--admin-bg);"
                            >
                        </div>
                        
                        <div class="form-group">
                            <label>Role</label>
                            <div style="padding: 0.75rem 1rem; background: var(--admin-bg); border-radius: 10px; border: 1px solid var(--admin-border); color: var(--admin-text-light); display: flex; align-items: center; gap: 0.5rem; cursor: not-allowed;">
                                <i class="fa-solid fa-shield-halved"></i>
                                <?= ucfirst($admin['role']) ?>
                            </div>
                            <small style="color: var(--admin-text-light); font-size: 0.8rem; margin-top: 0.5rem; display: block;">
                                <i class="fa-solid fa-info-circle"></i> Permissions are managed by the system administrator.
                            </small>
                        </div>
                    </div>
                </div>
                
                <hr style="border: none; border-top: 1px solid var(--admin-border); margin: 0.5rem 0;">
                
                <!-- Change Password Section -->
                <div>
                    <h3 style="font-size: 1.1rem; font-weight: 700; color: var(--admin-text); margin-bottom: 1.5rem; display: flex; align-items: center; gap: 0.75rem;">
                        <span style="width: 32px; height: 32px; background: var(--admin-primary-light); color: var(--admin-primary); border-radius: 8px; display: flex; align-items: center; justify-content: center;">
                            <i class="fa-solid fa-lock"></i>
                        </span>
                        Change Password
                    </h3>
                    
                    <div style="display: grid; gap: 1.25rem;">
                        <div class="form-group">
                            <label for="current_password">Current Password</label>
                            <input 
                                type="password" 
                                id="current_password" 
                                name="current_password" 
                                class="form-control"
                                placeholder="Enter current password to change"
                                style="background: var(--admin-bg);"
                            >
                        </div>
                        
                        <div class="form-group">
                            <label for="new_password">New Password</label>
                            <input 
                                type="password" 
                                id="new_password" 
                                name="new_password" 
                                class="form-control"
                                placeholder="At least 6 characters"
                                style="background: var(--admin-bg);"
                            >
                        </div>
                        
                        <div class="form-group">
                            <label for="confirm_password">Confirm New Password</label>
                            <input 
                                type="password" 
                                id="confirm_password" 
                                name="confirm_password" 
                                class="form-control"
                                placeholder="Re-enter new password"
                                style="background: var(--admin-bg);"
                            >
                        </div>
                    </div>
                    
                    <div style="background: rgba(37, 99, 235, 0.05); color: #2563eb; padding: 0.75rem 1rem; border-radius: 8px; font-size: 0.85rem; margin-top: 1rem; border: 1px solid rgba(37, 99, 235, 0.1);">
                        <i class="fa-solid fa-circle-info"></i> Leave password fields empty if you don't want to change your password.
                    </div>
                </div>
                
                <div style="display: flex; gap: 1rem; margin-top: 1.5rem;">
                    <button type="submit" class="btn-action" style="flex: 1; height: 48px; background: var(--admin-primary); color: white; border-radius: 12px; font-weight: 700; gap: 0.5rem;">
                        <i class="fa-solid fa-floppy-disk"></i> Save Profile
                    </button>
                    <a href="index.php" class="btn-action" style="flex: 1; height: 48px; background: var(--admin-bg); color: var(--admin-text); border: 1px solid var(--admin-border); border-radius: 12px; font-weight: 600; text-decoration: none; gap: 0.5rem; display: flex; align-items: center; justify-content: center;">
                        <i class="fa-solid fa-xmark"></i> Cancel
                    </a>
                </div>
            </form>
        </div>
        
        <!-- Account Info Card -->
        <div class="admin-table-widget" style="margin-top: 2rem; padding: 1.5rem; background: linear-gradient(135deg, var(--admin-card) 0%, var(--admin-bg) 100%); border-left: 4px solid var(--admin-primary);">
            <h3 style="font-size: 0.9rem; font-weight: 700; color: var(--admin-text); margin-bottom: 1rem; display: flex; align-items: center; gap: 0.5rem;">
                <i class="fa-solid fa-circle-info" style="color: var(--admin-primary);"></i>
                Account Meta
            </h3>
            <div style="display: grid; gap: 0.75rem; font-size: 0.85rem;">
                <div style="display: flex; justify-content: space-between; padding-bottom: 0.5rem; border-bottom: 1px dashed var(--admin-border);">
                    <span style="color: var(--admin-text-light);">Account ID</span>
                    <strong style="color: var(--admin-text); font-family: monospace;">UUID-<?= str_pad($admin['id'] ?? 0, 6, '0', STR_PAD_LEFT) ?></strong>
                </div>
                <div style="display: flex; justify-content: space-between;">
                    <span style="color: var(--admin-text-light);">Member Since</span>
                    <strong style="color: var(--admin-text);"><?= date('F d, Y', strtotime($admin['created_at'])) ?></strong>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once 'footer.php'; ?>
