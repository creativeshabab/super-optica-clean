<?php
// includes/account-sidebar.php
$base = getBaseURL();
$current_page = basename($_SERVER['PHP_SELF']);
$user_name = $_SESSION['user_name'] ?? 'User';
$user_letter = strtoupper(substr($user_name, 0, 1));
?>
<div class="account-sidebar">
    <!-- User Info Card -->
    <div class="user-card">
        <div class="avatar"><?= $user_letter ?></div>
        <h3><?= htmlspecialchars($user_name) ?></h3>
        <p>Enjoy your vision!</p>
    </div>

    <!-- Navigation Menu -->
    <ul class="sidebar-menu">
        <li>
            <a href="<?= $base ?>profile.php" class="<?= $current_page == 'profile.php' ? 'active' : '' ?>">
                Dashboard <i class="fa-solid fa-chevron-right"></i>
            </a>
        </li>
        <li>
            <a href="<?= $base ?>my-orders.php" class="<?= $current_page == 'my-orders.php' ? 'active' : '' ?>">
                My Orders <i class="fa-solid fa-chevron-right"></i>
            </a>
        </li>
        <li>
            <a href="<?= $base ?>prescriptions.php" class="<?= $current_page == 'prescriptions.php' ? 'active' : '' ?>">
                My Prescription <i class="fa-solid fa-chevron-right"></i>
            </a>
        </li>
        <li>
            <a href="<?= $base ?>store-credit.php" class="<?= $current_page == 'store-credit.php' ? 'active' : '' ?>">
                My Store Credit <i class="fa-solid fa-chevron-right"></i>
            </a>
        </li>
        <li>
            <a href="<?= $base ?>account-info.php" class="<?= $current_page == 'account-info.php' ? 'active' : '' ?>">
                Account Information <i class="fa-solid fa-chevron-right"></i>
            </a>
        </li>
        <li>
            <a href="<?= $base ?>logout.php" class="logout">
                Logout
            </a>
        </li>
    </ul>
</div>
