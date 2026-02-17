<?php
// prescriptions.php
require_once 'includes/header.php';
if (!isLoggedIn()) redirect('login.php');
?>
<link rel="stylesheet" href="<?= getBaseURL() ?>assets/css/account.css?v=<?= time() ?>">

<div class="account-container">
    <div class="container">
        <div class="account-grid">
            <?php require_once 'includes/account-sidebar.php'; ?>
            
            <div class="dashboard-content">
                <div class="section-header">
                    <h2>My Prescriptions</h2>
                    <a href="#" class="btn-view-details">+ Upload New</a>
                </div>
                
                <div class="bg-white p-5 rounded border text-center">
                    <i class="fa-solid fa-file-medical text-gray-300 text-5xl mb-3"></i>
                    <p class="text-gray-500">No prescriptions found.</p>
                </div>
            </div>
        </div>
    </div>
</div>
<?php require_once 'includes/footer.php'; ?>
