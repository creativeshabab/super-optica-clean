<?php
require_once '../config/db.php';
require_once '../includes/functions.php';

if (isAdmin()) {
    redirect('index.php');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = $_POST['email'];
    $password = $_POST['password'];

    $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ? AND role = 'admin'");
    $stmt->execute([$email]);
    $user = $stmt->fetch();

    if ($user && password_verify($password, $user['password'])) {
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['role'] = $user['role'];
        $_SESSION['name'] = $user['name'];
        redirect('index.php');
    } else {
        $error = "Invalid admin credentials";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Login - Super Optical</title>
    
    <!-- Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">
    
    <!-- CSS -->
    <link rel="stylesheet" href="../assets/css/admin.css">
    <link rel="stylesheet" href="../assets/css/variables.css">
    <link rel="stylesheet" href="../assets/css/components.css">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <!-- Dynamic Typography & Theme Styles -->
    <?php include '../includes/dynamic_styles.php'; ?>

    <style>
        body {
            background-color: var(--background);
            display: flex;
            align-items: center;
            justify-content: center;
            min-height: 100vh;
            margin: 0;
            font-family: var(--font-family);
            background-image: radial-gradient(circle at top right, rgba(var(--primary-rgb), 0.05), transparent 40%),
                              radial-gradient(circle at bottom left, rgba(var(--secondary-rgb), 0.05), transparent 40%);
        }
        
        .login-card {
            background: var(--surface);
            padding: 3rem;
            border-radius: 1.5rem;
            box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04);
            width: 100%;
            max-width: 450px;
            position: relative;
            overflow: hidden;
            border: 1px solid rgba(0,0,0,0.05);
        }
        
        .login-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 6px;
            background: linear-gradient(90deg, var(--primary), var(--secondary));
        }

        .login-header {
            text-align: center;
            margin-bottom: 2.5rem;
        }

        .login-logo {
            font-size: 2rem;
            font-weight: 800;
            color: var(--text-main);
            text-transform: uppercase;
            letter-spacing: -0.02em;
            margin-bottom: 0.5rem;
            display: inline-block;
        }
        
        .login-logo span {
            color: var(--primary);
        }

        .login-subtitle {
            color: var(--text-light);
            font-size: 0.95rem;
            font-weight: 500;
        }

        .form-group {
            margin-bottom: 1.5rem;
        }

        .form-label {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: 600;
            color: var(--text-main);
            font-size: 0.9rem;
        }

        .form-control {
            width: 100%;
            padding: 0.875rem 1rem;
            border: 2px solid #e2e8f0;
            border-radius: 0.75rem;
            font-family: inherit;
            font-size: 1rem;
            transition: all 0.2s;
            background-color: #f8fafc;
        }

        .form-control:focus {
            border-color: var(--primary);
            background-color: #fff;
            outline: none;
            box-shadow: 0 0 0 4px rgba(var(--primary-rgb), 0.1);
        }

        .btn-login {
            background-color: var(--primary);
            color: #fff;
            width: 100%;
            padding: 1rem;
            border-radius: 0.75rem;
            font-weight: 700;
            font-size: 1rem;
            border: none;
            cursor: pointer;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.75rem;
            box-shadow: 0 4px 6px -1px rgba(var(--primary-rgb), 0.3);
            margin-top: 1rem;
        }

        .btn-login:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 15px -3px rgba(var(--primary-rgb), 0.4);
            filter: brightness(110%);
        }
        
        .btn-login:active {
            transform: translateY(0);
        }

        .back-link {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            text-decoration: none;
            color: var(--text-light);
            font-weight: 600;
            font-size: 0.9rem;
            margin-top: 2rem;
            transition: color 0.2s;
            width: 100%;
        }
        
        .back-link:hover {
            color: var(--primary);
        }

        .alert-error {
            background-color: #fef2f2;
            color: #dc2626;
            padding: 1rem;
            border-radius: 0.75rem;
            margin-bottom: 1.5rem;
            font-size: 0.9rem;
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 0.75rem;
            border: 1px solid #fee2e2;
        }
    </style>
</head>
<body>

<div class="login-card">
    <div class="login-header">
        <div class="login-logo">Super <span>Optical</span></div>
        <p class="login-subtitle">Sign in to manage your store</p>
    </div>

    <?php if (isset($error)): ?>
        <div class="alert-error">
            <i class="fa-solid fa-circle-exclamation"></i> <?= $error ?>
        </div>
    <?php endif; ?>

    <form method="POST">
        <div class="form-group">
            <label class="form-label">Email Address</label>
            <div class="relative">
                <input type="email" name="email" class="form-control" required placeholder="admin@superoptical.in" autocomplete="email">
            </div>
        </div>
        
        <div class="form-group">
            <label class="form-label">Password</label>
            <div class="relative">
                <input type="password" name="password" class="form-control" required placeholder="••••••••" autocomplete="current-password">
            </div>
        </div>
        
        <button type="submit" class="btn-login">
            Login to Dashboard <i class="fa-solid fa-arrow-right-long"></i>
        </button>
    </form>
    
    <a href="../index.php" class="back-link">
        <i class="fa-solid fa-arrow-left mr-2"></i> Back to Website
    </a>
</div>

</body>
</html>
