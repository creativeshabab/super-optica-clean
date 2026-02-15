<?php
require_once 'config/db.php';
require_once 'includes/functions.php';

if (isLoggedIn()) redirect('index.php');

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = $_POST['name'];
    $email = $_POST['email'] ?? null;
    $phone = $_POST['phone'] ?? null;
    if (false) { // Passwords are no longer used for registration
        $error = "Passwords do not match";
    } else {
        // Check if email or phone exists
        $stmt = $pdo->prepare("SELECT id FROM users WHERE (email IS NOT NULL AND email = ?) OR (phone IS NOT NULL AND phone = ?)");
        $stmt->execute([$email, $phone]);
        if ($stmt->fetch()) {
            $error = "Email or Phone already registered";
        } else {
            // Generate a secure random password for the DB constraint
            $random_password = bin2hex(random_bytes(16)); 
            $hashed = password_hash($random_password, PASSWORD_DEFAULT);
            
            $stmt = $pdo->prepare("INSERT INTO users (name, email, phone, password, is_verified) VALUES (?, ?, ?, ?, 0)");
            if ($stmt->execute([$name, $email, $phone, $hashed])) {
                $user_id = $pdo->lastInsertId();
                
                // Create OTP
                $otp = sprintf("%06d", mt_rand(1, 999999));
                $expires_at = date('Y-m-d H:i:s', strtotime('+10 minutes'));
                
                $stmt = $pdo->prepare("INSERT INTO verification_codes (user_id, code, expires_at) VALUES (?, ?, ?)");
                $stmt->execute([$user_id, $otp, $expires_at]);

                // Send OTP Email
                require_once 'includes/order_templates.php';
                $subject = "Verify Your Account - Super Optical";
                $body = getOTPEmailTemplate($otp);
                
                if (sendEmail($email, $subject, $body)) {
                    $_SESSION['verify_email'] = $email;
                    $_SESSION['verify_user_id'] = $user_id;

                    $msg = 'Verification code sent to your email.';
                    
                    setFlash('success', $msg);
                    redirect('verify-otp.php');
                } else {
                     // Get detailed error information
                     $email_error = getLastEmailError();
                     
                     // Log the error for admin review
                     error_log("Registration email failed for user ID: $user_id, Email: $email");
                     
                     // Build user-friendly error message
                     $error = "Registration successful but failed to send verification email. ";
                     
                     if ($email_error) {
                         // In development, show detailed error
                         if (isset($_GET['debug'])) {
                             $error .= "<br><small>Error: " . htmlspecialchars($email_error['error']) . "</small>";
                         }
                     }
                     
                     $error .= "Please contact support or try again later.";
                     
                     // Still allow them to try OTP page (they can resend)
                     $_SESSION['verify_email'] = $email;
                     $_SESSION['verify_user_id'] = $user_id;
                }
                exit;
            } else {
                $error = "Registration failed";
            }
        }
    }
}
?>

<?php require_once 'includes/header.php'; ?>

<div class="min-h-screen flex items-center justify-center section-padding px-4 sm:px-6 lg:px-8 bg-gray-50">
    <div class="card max-w-lg w-full space-y-8 p-10 bg-white rounded-2xl shadow-xl">
        <div class="text-center">
            <div class="w-20 h-20 bg-primary text-white rounded-2xl flex items-center justify-center text-3xl mx-auto mb-6 shadow-lg shadow-primary/30">
                <i class="fa-solid fa-user-plus"></i>
            </div>
            <h2 class="text-3xl font-black text-gray-900 mb-2"><?= __('create_account_heading') ?></h2>
            <p class="text-gray-500 font-medium"><?= __('join_community') ?></p>
        </div>

        <?php if (!empty($error)): ?>
             <div class="bg-red-50 text-red-700 p-4 rounded-lg flex items-center gap-3 border border-red-100">
                <i class="fa-solid fa-circle-exclamation text-xl"></i> 
                <span class="font-medium"><?= $error ?></span>
            </div>
        <?php endif; ?>

        <!-- Google Sign-in Button -->


        <form method="POST" class="space-y-6">
            <div>
                 <label class="form-label text-gray-700 font-bold mb-2"><?= __('full_name') ?></label>
                 <div class="relative">
                    <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none text-gray-400">
                        <i class="fa-solid fa-user"></i>
                    </div>
                     <input type="text" name="name" required placeholder="<?= __('example_name') ?>" class="form-input pl-10 block w-full border-gray-300 rounded-lg focus:ring-primary focus:border-primary">
                 </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div>
                    <label class="form-label text-gray-700 font-bold mb-2"><?= __('email_address') ?></label>
                    <div class="relative">
                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none text-gray-400">
                            <i class="fa-solid fa-envelope"></i>
                        </div>
                        <input type="email" name="email" placeholder="name@example.com" class="form-input pl-10 block w-full border-gray-300 rounded-lg focus:ring-primary focus:border-primary">
                    </div>
                </div>
                <div>
                    <label class="form-label text-gray-700 font-bold mb-2"><?= __('mobile_number') ?></label>
                    <div class="relative">
                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none text-gray-400">
                            <i class="fa-solid fa-phone"></i>
                        </div>
                        <input type="text" name="phone" required placeholder="9876543210" class="form-input pl-10 block w-full border-gray-300 rounded-lg focus:ring-primary focus:border-primary">
                    </div>
                </div>
            </div>

            <button type="submit" class="btn btn-primary w-full py-3 text-lg font-bold shadow-md hover:shadow-lg transform hover:-translate-y-1 transition-all">
                <?= __('register_now') ?> <i class="fa-solid fa-user-check ml-2"></i>
            </button>
        </form>

        <div class="text-center mt-4">
             <p class="text-gray-500 font-medium">
                <?= __('already_have_account') ?> <a href="login.php" class="text-primary font-bold hover:underline"><?= __('login_here') ?></a>
            </p>
        </div>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>
