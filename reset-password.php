<?php require_once 'includes/header.php'; ?>
<?php require_once 'config/db.php'; ?>
<?php require_once 'includes/functions.php'; ?>

<?php
if (isLoggedIn()) redirect('index.php');

$token = $_GET['token'] ?? '';
$error = '';
$success = '';

if (!$token) {
    redirect('login.php');
}

// Verify Token
$stmt = $pdo->prepare("SELECT * FROM users WHERE reset_token = ?");
$stmt->execute([$token]);
$user = $stmt->fetch();

if (!$user || (strtotime($user['token_expires']) < time())) {
    $error = "Invalid or expired reset link. Please request a new one.";
    $user = null; // Ensure user is null if expired
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $user) {
    $password = $_POST['password'];
    $confirm = $_POST['confirm_password'];

    if ($password !== $confirm) {
        $error = "Passwords do not match.";
    } else {
        $hashed = password_hash($password, PASSWORD_DEFAULT);
        $update = $pdo->prepare("UPDATE users SET password = ?, reset_token = NULL, token_expires = NULL WHERE id = ?");
        if ($update->execute([$hashed, $user['id']])) {
            $success = "Password reset successful! You can now login.";
        } else {
            $error = "Failed to update password. Try again later.";
        }
    }
}
?>

<div class="checkout-container section-padding">
    <div class="checkout-card card max-w-lg mx-auto p-10 bg-white rounded-2xl shadow-xl">
        <div class="text-center mb-10">
            <div class="icon-circle-lg bg-success-light text-success">
                <i class="fa-solid fa-shield-halved"></i>
            </div>
            <h2 class="text-3xl font-black text-accent mb-2">Set New Password</h2>
            <p class="text-secondary font-medium">Create a strong password for your account.</p>
        </div>

        <?php if ($success): ?>
            <div class="alert--success mb-8 text-center">
                <p class="font-bold mb-4"><?= $success ?></p>
                <a href="login.php" class="btn btn-primary w-full">Login Now</a>
            </div>
        <?php elseif ($error): ?>
            <div class="alert--error mb-8">
                <i class="fa-solid fa-circle-exclamation mr-2"></i> <?= htmlspecialchars($error) ?>
                <?php if (strpos($error, 'Invalid or expired') !== false): ?>
                    <div class="mt-4">
                        <a href="forgot-password.php" class="text-primary font-black">Request New Link</a>
                    </div>
                <?php endif; ?>
            </div>
        <?php endif; ?>

        <?php if (!$success && $user): ?>
            <form method="POST" class="space-y-6">
                <div class="form-group">
                    <label class="form-label">New Password</label>
                    <input type="password" name="password" required placeholder="••••••••" class="form-input">
                </div>
                
                <div class="form-group">
                    <label class="form-label">Confirm Password</label>
                    <input type="password" name="confirm_password" required placeholder="••••••••" class="form-input">
                </div>
                
                <button type="submit" class="btn btn-primary w-full py-3 mt-4 transform hover:-translate-y-1 transition-all">
                    Update Password <i class="fa-solid fa-check ml-2"></i>
                </button>
            </form>
        <?php endif; ?>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>
