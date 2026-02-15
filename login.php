<?php
require_once 'config/db.php';
require_once 'includes/functions.php';

if (isLoggedIn()) {
    if (isAdmin()) {
        redirect('admin/index.php');
    } else {
        redirect('index.php');
    }
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = $_POST['email'];
    // $password = $_POST['password']; // No longer using password

    $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ? OR phone = ?");
    $stmt->execute([$email, $email]); // Check both email and phone
    $user = $stmt->fetch();

    if ($user) {
        // Generate OTP for Login
        $_SESSION['verify_email'] = $user['email'];
        $_SESSION['verify_user_id'] = $user['id'];
        
        $otp = sprintf("%06d", mt_rand(1, 999999));
        $expires_at = date('Y-m-d H:i:s', strtotime('+10 minutes'));
        
        $pdo->prepare("INSERT INTO verification_codes (user_id, code, expires_at) VALUES (?, ?, ?)")->execute([$user['id'], $otp, $expires_at]);
        
        require_once 'includes/order_templates.php';
        sendEmail($user['email'], "Login Verification Code - Super Optical", getOTPEmailTemplate($otp));
        
        $msg = 'A verification code has been sent to your email.';
        
        setFlash('success', $msg);
        redirect('verify-otp.php');
        exit;
    } else {
        $error = __('account_not_found');
    }
}
?>

<?php require_once 'includes/header.php'; ?>

<div class="min-h-screen flex items-center justify-center section-padding px-4 sm:px-6 lg:px-8 bg-gray-50">
    <div class="card max-w-md w-full space-y-8 p-10 bg-white rounded-2xl shadow-xl">
        <div class="text-center">
            <div class="w-20 h-20 bg-primary/10 text-primary rounded-2xl flex items-center justify-center text-3xl mx-auto mb-6 shadow-sm">
                <i class="fa-solid fa-fingerprint"></i>
            </div>
            <h2 class="text-3xl font-black text-gray-900 mb-2"><?= __('welcome_back') ?></h2>
            <p class="text-gray-500 font-medium"><?= __('enter_email_login') ?></p>
        </div>

        <?php if (!empty($error)): ?>
            <div class="bg-red-50 text-red-700 p-4 rounded-lg flex items-center gap-3 border border-red-100">
                <i class="fa-solid fa-circle-exclamation text-xl"></i> 
                <span class="font-medium"><?= $error ?></span>
            </div>
        <?php endif; ?>



        <form method="POST" class="space-y-6">
            <div>
                <label class="form-label text-gray-700 font-bold mb-2"><?= __('email_mobile') ?></label>
                <div class="relative">
                    <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none text-gray-400">
                        <i class="fa-solid fa-envelope"></i>
                    </div>
                    <input type="text" name="email" required placeholder="name@example.com" class="form-input pl-10 block w-full border-gray-300 rounded-lg focus:ring-primary focus:border-primary">
                </div>
            </div>
            
            <!-- Password Field Removed -->

            <div>
                <button type="submit" class="btn btn-primary w-full py-3 text-lg font-bold shadow-md hover:shadow-lg transform hover:-translate-y-1 transition-all">
                    <?= __('get_verification_code') ?> <i class="fa-solid fa-arrow-right ml-2"></i>
                </button>
            </div>
            
             <!-- Forgot Password Removed (Not needed for OTP flow) -->
        </form>

        <div class="text-center mt-4">
            <p class="text-gray-500 font-medium">
                <?= __('no_account') ?> <a href="register.php" class="text-primary font-bold hover:underline"><?= __('create_account') ?></a>
            </p>
        </div>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>
