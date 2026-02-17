<?php require_once 'includes/header.php'; ?>
<?php require_once 'config/db.php'; ?>
<?php require_once 'includes/functions.php'; ?>

<?php
if (isLoggedIn()) redirect('index.php');

$message = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $identifier = $_POST['identifier']; // Email or Phone

    $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ? OR phone = ?");
    $stmt->execute([$identifier, $identifier]);
    $user = $stmt->fetch();

    if ($user) {
        $token = bin2hex(random_bytes(32));
        $expires = date('Y-m-d H:i:s', strtotime('+1 hour'));

        $update = $pdo->prepare("UPDATE users SET reset_token = ?, token_expires = ? WHERE id = ?");
        $update->execute([$token, $expires, $user['id']]);

        // Send Real Email
        $reset_link = "http://" . $_SERVER['HTTP_HOST'] . dirname($_SERVER['PHP_SELF']) . "/reset-password.php?token=" . $token;
        
        require_once 'includes/order_templates.php';
        $body = getPasswordResetEmailTemplate($user['name'], $reset_link);

        if (sendEmail($user['email'], "Password Reset - Super Optical", $body)) {
            $message = "Reset instructions have been sent to your registered email address!";
        } else {
            $error = "We couldn't send the email. Please try again later or contact support.";
        }
    } else {
        $error = "No account found with that information.";
    }
}
?>

<div class="min-h-screen flex items-center justify-center section-padding px-4 sm:px-6 lg:px-8 bg-gray-50">
    <div class="card max-w-md w-full space-y-8 p-10 bg-white rounded-2xl shadow-xl">
        <div class="text-center">
            <div class="w-20 h-20 bg-yellow-100 text-yellow-700 rounded-2xl flex items-center justify-center text-3xl mx-auto mb-6 shadow-sm">
                <i class="fa-solid fa-key"></i>
            </div>
            <h2 class="text-3xl font-black text-gray-900 mb-2">Forgot Password</h2>
            <p class="text-gray-500 font-medium">Enter your email or phone to reset.</p>
        </div>

        <?php if ($message): ?>
            <div class="bg-green-50 text-green-700 p-4 rounded-lg flex items-center gap-3 border border-green-100">
                <i class="fa-solid fa-circle-check text-xl"></i>
                <span class="font-medium"><?= $message ?></span>
            </div>
        <?php endif; ?>

        <?php if ($error): ?>
            <div class="bg-red-50 text-red-700 p-4 rounded-lg flex items-center gap-3 border border-red-100">
                <i class="fa-solid fa-circle-exclamation text-xl"></i> 
                <span class="font-medium"><?= htmlspecialchars($error) ?></span>
            </div>
        <?php endif; ?>

        <form method="POST" class="space-y-6">
            <div>
                <label class="form-label text-gray-700 font-bold mb-2">Email or Mobile Number</label>
                <div class="relative">
                    <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none text-gray-400">
                        <i class="fa-solid fa-user-tag"></i>
                    </div>
                    <input type="text" name="identifier" required placeholder="name@example.com or 98765..." class="form-input pl-10 block w-full border-gray-300 rounded-lg focus:ring-primary focus:border-primary">
                </div>
            </div>

            <button type="submit" class="btn btn-primary w-full py-3 shadow-md hover:shadow-lg transform hover:-translate-y-1 transition-all">
                Reset Password <i class="fa-solid fa-envelope ml-2"></i>
            </button>
        </form>

        <div class="text-center mt-4">
            <p class="text-gray-500 font-medium">
                Remembered? <a href="login.php" class="text-primary font-bold hover:underline">Back to Login</a>
            </p>
        </div>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>
