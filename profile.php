<?php
require_once 'includes/header.php';
require_once 'config/db.php';
require_once 'includes/functions.php';

if (!isLoggedIn()) {
    redirect('login.php');
}

$user_id = $_SESSION['user_id'];
$tab = $_GET['tab'] ?? 'personal';
$success = '';
$error = '';

// Fetch user data
$stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$user_id]);
$user = $stmt->fetch();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['update_profile'])) {
        $name = $_POST['name'];
        $email = $_POST['email'];

        $stmt = $pdo->prepare("UPDATE users SET name = ?, email = ? WHERE id = ?");
        if ($stmt->execute([$name, $email, $user_id])) {
            $_SESSION['user_name'] = $name;
            $success = __('profile_updated_success');
            $user['name'] = $name;
            $user['email'] = $email;
        } else {
            $error = __('profile_update_fail');
        }
    } elseif (isset($_POST['change_password'])) {
        $old_pass = $_POST['old_password'];
        $new_pass = $_POST['new_password'];
        $confirm_pass = $_POST['confirm_password'];

        if (password_verify($old_pass, $user['password'])) {
            if ($new_pass === $confirm_pass) {
                $hashed = password_hash($new_pass, PASSWORD_BCRYPT);
                $stmt = $pdo->prepare("UPDATE users SET password = ? WHERE id = ?");
                if ($stmt->execute([$hashed, $user_id])) {
                    $success = __('password_changed_success');
                } else {
                    $error = __('password_update_fail');
                }
            } else {
                $error = __('new_passwords_mismatch');
            }
        } else {
            $error = __('incorrect_current_password');
        }
    }
}

// Fetch orders for the Orders tab
$orders = [];
if ($tab === 'orders') {
    $stmtO = $pdo->prepare("SELECT * FROM orders WHERE user_id = ? ORDER BY created_at DESC");
    $stmtO->execute([$user_id]);
    $orders = $stmtO->fetchAll();
}
?>

<div class="web-wrapper section-padding bg-gray-50 min-h-[80vh]">
    <div class="container mx-auto px-4">
        <div class="grid grid-cols-1 lg:grid-cols-[280px_1fr] gap-8">
            <!-- Sidebar -->
            <aside class="space-y-6">
                <div class="bg-white p-6 rounded-2xl shadow-sm border border-gray-100 flex items-center gap-4">
                    <div class="w-12 h-12 bg-primary text-white rounded-full flex items-center justify-center text-xl shadow-lg shadow-primary/30">
                        <i class="fa-solid fa-user"></i>
                    </div>
                    <div>
                        <span class="text-xs font-bold text-gray-400 uppercase tracking-wider block mb-1"><?= __('hello') ?></span>
                        <h4 class="text-lg font-bold text-gray-900 leading-none"><?= htmlspecialchars($user['name']) ?></h4>
                    </div>
                </div>

                <nav class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
                    <div class="p-2 space-y-1">
                        <div class="px-4 py-3 text-xs font-bold text-gray-400 uppercase tracking-wider flex items-center gap-2">
                            <i class="fa-solid fa-box text-primary"></i>
                            <span><?= __('my_orders_menu') ?></span>
                        </div>
                        <a href="?tab=orders" class="block px-4 py-3 rounded-xl text-gray-700 font-medium hover:bg-gray-50 hover:text-primary transition-colors <?= $tab === 'orders' ? 'bg-primary/5 text-primary font-bold' : '' ?>">
                            <?= __('view_history') ?>
                        </a>
                    </div>
                    
                    <div class="border-t border-gray-100 my-1"></div>

                    <div class="p-2 space-y-1">
                        <div class="px-4 py-3 text-xs font-bold text-gray-400 uppercase tracking-wider flex items-center gap-2">
                            <i class="fa-solid fa-user-gear text-primary"></i>
                            <span><?= __('account_settings') ?></span>
                        </div>
                        <a href="?tab=personal" class="block px-4 py-3 rounded-xl text-gray-700 font-medium hover:bg-gray-50 hover:text-primary transition-colors <?= $tab === 'personal' ? 'bg-primary/5 text-primary font-bold' : '' ?>">
                            <?= __('personal_info') ?>
                        </a>
                        <a href="?tab=security" class="block px-4 py-3 rounded-xl text-gray-700 font-medium hover:bg-gray-50 hover:text-primary transition-colors <?= $tab === 'security' ? 'bg-primary/5 text-primary font-bold' : '' ?>">
                            <?= __('security_settings') ?>
                        </a>
                    </div>

                    <div class="border-t border-gray-100 my-1"></div>

                    <div class="p-2">
                        <a href="logout.php" class="block px-4 py-3 rounded-xl text-red-600 font-bold hover:bg-red-50 transition-colors flex items-center gap-3">
                            <i class="fa-solid fa-power-off"></i>
                            <span><?= __('logout') ?></span>
                        </a>
                    </div>
                </nav>
            </aside>

            <!-- Main Content -->
            <main>
                <?php if ($success): ?>
                    <div class="bg-green-50 text-green-700 p-4 rounded-xl flex items-center gap-3 border border-green-100 mb-6 shadow-sm">
                        <i class="fa-solid fa-circle-check text-xl"></i> 
                        <span class="font-medium"><?= $success ?></span>
                    </div>
                <?php endif; ?>
                <?php if ($error): ?>
                    <div class="bg-red-50 text-red-700 p-4 rounded-xl flex items-center gap-3 border border-red-100 mb-6 shadow-sm">
                        <i class="fa-solid fa-circle-exclamation text-xl"></i> 
                        <span class="font-medium"><?= $error ?></span>
                    </div>
                <?php endif; ?>

                <div class="bg-white p-8 rounded-2xl shadow-sm border border-gray-100 min-h-[400px]">
                    <?php if ($tab === 'personal'): ?>
                        <div class="mb-8 border-b border-gray-100 pb-4">
                            <h3 class="text-2xl font-bold text-gray-900 mb-2"><?= __('personal_info') ?></h3>
                            <p class="text-gray-500"><?= __('update_profile_desc') ?></p>
                        </div>
                        <form method="POST" class="max-w-2xl space-y-6">
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                <div>
                                    <label class="form-label text-gray-700 font-bold mb-2 block"><?= __('full_name') ?></label>
                                    <input type="text" name="name" value="<?= htmlspecialchars($user['name']) ?>" required class="form-input w-full border-gray-300 rounded-lg focus:ring-primary focus:border-primary px-4 py-3">
                                </div>
                                <div>
                                    <label class="form-label text-gray-700 font-bold mb-2 block"><?= __('email_address') ?></label>
                                    <input type="email" name="email" value="<?= htmlspecialchars($user['email']) ?>" required class="form-input w-full border-gray-300 rounded-lg focus:ring-primary focus:border-primary px-4 py-3">
                                </div>
                            </div>
                            <button type="submit" name="update_profile" class="btn btn-primary px-8 py-3 font-bold shadow-md hover:shadow-lg transform hover:-translate-y-1 transition-all"><?= __('save_changes') ?></button>
                        </form>

                    <?php elseif ($tab === 'security'): ?>
                        <div class="mb-8 border-b border-gray-100 pb-4">
                            <h3 class="text-2xl font-bold text-gray-900 mb-2"><?= __('security_settings') ?></h3>
                            <p class="text-gray-500"><?= __('security_settings_desc') ?></p>
                        </div>
                        <form method="POST" class="max-w-2xl space-y-6">
                            <div>
                                <label class="form-label text-gray-700 font-bold mb-2 block"><?= __('current_password') ?></label>
                                <input type="password" name="old_password" required placeholder="••••••••" class="form-input w-full border-gray-300 rounded-lg focus:ring-primary focus:border-primary px-4 py-3">
                            </div>
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                <div>
                                    <label class="form-label text-gray-700 font-bold mb-2 block"><?= __('new_password') ?></label>
                                    <input type="password" name="new_password" required placeholder="••••••••" class="form-input w-full border-gray-300 rounded-lg focus:ring-primary focus:border-primary px-4 py-3">
                                </div>
                                <div>
                                    <label class="form-label text-gray-700 font-bold mb-2 block"><?= __('confirm_new_password') ?></label>
                                    <input type="password" name="confirm_password" required placeholder="••••••••" class="form-input w-full border-gray-300 rounded-lg focus:ring-primary focus:border-primary px-4 py-3">
                                </div>
                            </div>
                            <button type="submit" name="change_password" class="btn btn-primary px-8 py-3 font-bold shadow-md hover:shadow-lg transform hover:-translate-y-1 transition-all"><?= __('update_password') ?></button>
                        </form>

                    <?php elseif ($tab === 'orders'): ?>
                        <div class="mb-8 border-b border-gray-100 pb-4">
                            <h3 class="text-2xl font-bold text-gray-900 mb-2"><?= __('my_orders_title') ?></h3>
                            <p class="text-gray-500"><?= __('track_orders_desc') ?></p>
                        </div>
                        <?php if (empty($orders)): ?>
                            <div class="text-center py-16 bg-gray-50 rounded-2xl border border-dashed border-gray-200">
                                <div class="w-20 h-20 bg-gray-200 rounded-full flex items-center justify-center text-4xl text-gray-400 mx-auto mb-6">
                                    <i class="fa-solid fa-bag-shopping"></i>
                                </div>
                                <h4 class="text-xl font-bold text-gray-800 mb-2"><?= __('no_orders_found') ?></h4>
                                <p class="text-gray-500 mb-6"><?= __('no_orders_desc') ?></p>
                                <a href="shop.php" class="btn btn-outline px-6 py-2 rounded-lg font-bold border-2 hover:bg-gray-100 transition-colors"><?= __('start_shopping') ?></a>
                            </div>
                        <?php else: ?>
                            <div class="space-y-4">
                                <?php foreach ($orders as $order): ?>
                                    <div class="bg-white border border-gray-100 rounded-xl p-6 hover:shadow-md transition-shadow flex flex-col md:flex-row gap-6 items-center">
                                        <div class="flex items-center gap-4 flex-1 w-full">
                                            <div class="w-16 h-16 bg-gray-50 rounded-lg flex items-center justify-center text-2xl text-gray-300 shrink-0">
                                                <i class="fa-solid fa-box-open"></i>
                                            </div>
                                            <div>
                                                <h4 class="font-bold text-lg text-gray-900 mb-1"><?= __('order_number') ?> <?= $order['id'] ?></h4>
                                                <div class="flex flex-wrap gap-x-4 gap-y-1 text-sm text-gray-500">
                                                    <span><i class="fa-regular fa-calendar mr-1"></i> <?= date('d M, Y', strtotime($order['created_at'])) ?></span>
                                                    <span class="font-bold text-gray-900">₹<?= number_format($order['total_amount'], 2) ?></span>
                                                </div>
                                            </div>
                                        </div>
                                        
                                        <div class="w-full md:w-auto flex flex-col items-start md:items-end gap-2">
                                            <div class="flex items-center gap-2">
                                                <span class="w-2.5 h-2.5 rounded-full <?= $order['status'] == 'completed' ? 'bg-green-500' : ($order['status'] == 'shipped' ? 'bg-blue-500' : ($order['status'] == 'cancelled' ? 'bg-red-500' : 'bg-yellow-500')) ?>"></span>
                                                <span class="font-bold text-gray-900 text-sm capitalize"><?= $order['status'] ?></span>
                                            </div>
                                            <p class="text-xs text-gray-500"><?= __('updated_recently') ?></p>
                                        </div>

                                        <div class="w-full md:w-auto">
                                            <a href="view-order.php?id=<?= $order['id'] ?>" class="w-10 h-10 rounded-full border border-gray-200 flex items-center justify-center text-gray-400 hover:text-primary hover:border-primary hover:bg-primary/5 transition-all ml-auto">
                                                <i class="fa-solid fa-chevron-right"></i>
                                            </a>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    <?php endif; ?>
                </div>
            </main>
        </div>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>
