<?php require_once '../config/db.php'; ?>
<?php require_once '../includes/functions.php'; ?>

<?php
// Authorization Check
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$order_id = $_GET['id'] ?? null;
if (!$order_id) die("Invalid Order ID");

// Fetch Order first to check ownership
$stmt = $pdo->prepare("SELECT o.*, u.name, u.email FROM orders o JOIN users u ON o.user_id = u.id WHERE o.id = ?");
$stmt->execute([$order_id]);
$order = $stmt->fetch();

if (!$order) die("Order not found");

// Check if user is admin OR the owner of the order
$isAdmin = isset($_SESSION['role']) && $_SESSION['role'] === 'admin';
$isOwner = isset($_SESSION['user_id']) && $_SESSION['user_id'] == $order['user_id'];

if (!$isAdmin && !$isOwner) {
    die("Unauthorized access");
}

// Fetch Order Items
$items_stmt = $pdo->prepare("SELECT oi.*, p.name FROM order_items oi JOIN products p ON oi.product_id = p.id WHERE oi.order_id = ?");
$items_stmt->execute([$order_id]);
$orderItems = $items_stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Invoice #<?= $order['id'] ?> - Super Optical</title>
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary: #e31e24;
            --text: #1e293b;
            --text-light: #64748b;
            --border: #e2e8f0;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Montserrat', sans-serif;
            color: var(--text);
            line-height: 1.6;
            background: #fff;
            padding: 40px;
        }

        .invoice-container {
            max-width: 800px;
            margin: 0 auto;
        }

        .header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 50px;
            padding-bottom: 30px;
            border-bottom: 2px solid var(--border);
        }

        .logo {
            font-size: 24px;
            font-weight: 800;
            color: var(--primary);
            letter-spacing: -1px;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .logo-icon {
            width: 32px;
            height: 32px;
            background: var(--primary);
            color: white;
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 18px;
        }

        .invoice-info {
            text-align: right;
        }

        .invoice-info h1 {
            font-size: 32px;
            font-weight: 800;
            text-transform: uppercase;
            color: #0f172a;
            margin-bottom: 5px;
        }

        .invoice-info p {
            color: var(--text-light);
            font-weight: 600;
        }

        .details-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 40px;
            margin-bottom: 50px;
        }

        .section-title {
            font-size: 12px;
            font-weight: 700;
            text-transform: uppercase;
            color: var(--text-light);
            letter-spacing: 0.05em;
            margin-bottom: 12px;
            border-bottom: 1px solid var(--border);
            padding-bottom: 8px;
        }

        .address-box p {
            margin-bottom: 4px;
        }

        .buyer-name {
            font-size: 18px;
            font-weight: 700;
            color: #0f172a;
            margin-bottom: 8px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 40px;
        }

        th {
            text-align: left;
            padding: 12px 15px;
            background: #f8fafc;
            font-size: 12px;
            font-weight: 700;
            text-transform: uppercase;
            color: var(--text-light);
            border-bottom: 2px solid var(--border);
        }

        td {
            padding: 15px;
            border-bottom: 1px solid var(--border);
        }

        .item-name {
            font-weight: 600;
            color: #0f172a;
        }

        .total-row td {
            border-bottom: none;
            padding-top: 25px;
        }

        .grand-total-label {
            font-size: 14px;
            font-weight: 700;
            text-align: right;
            color: var(--text-light);
            text-transform: uppercase;
        }

        .grand-total-value {
            font-size: 24px;
            font-weight: 800;
            text-align: right;
            color: var(--primary);
        }

        .footer {
            margin-top: 80px;
            text-align: center;
            color: var(--text-light);
            font-size: 13px;
            padding-top: 30px;
            border-top: 1px solid var(--border);
        }

        @media print {
            body {
                padding: 0;
            }
            .no-print {
                display: none !important;
            }
        }

        .print-btn {
            position: fixed;
            bottom: 30px;
            right: 30px;
            background: var(--primary);
            color: white;
            padding: 12px 25px;
            border-radius: 50px;
            font-weight: 700;
            cursor: pointer;
            border: none;
            box-shadow: 0 10px 15px -3px rgba(227, 30, 36, 0.3);
            display: flex;
            align-items: center;
            gap: 10px;
            text-decoration: none;
        }

        .print-btn:hover {
            transform: translateY(-2px);
            background: #c21a1f;
        }
    </style>
</head>
<body>

    <a href="javascript:window.print()" class="print-btn no-print">
        <span>Print Invoice</span>
    </a>

    <div class="invoice-container">
        <div class="header">
            <div class="logo">
                <div class="logo-icon">S</div>
                SUPER OPTICAL
                <p style="font-size: 12px; color: var(--text-light); font-weight: 400; margin-top: 5px;">Premium Eyewear Store</p>
            </div>
            <div class="invoice-info">
                <h1>Invoice</h1>
                <p>#INV-<?= str_pad($order['id'], 6, '0', STR_PAD_LEFT) ?></p>
                <p style="font-size: 14px; margin-top: 5px; color: var(--text);"><?= date('M d, Y', strtotime($order['created_at'])) ?></p>
            </div>
        </div>

        <div class="details-grid">
            <div class="address-box">
                <h3 class="section-title">Sold By</h3>
                <p class="buyer-name">Super Optical Pvt Ltd.</p>
                <p>Dak Bunglow Rd, near mahila college, Chanakya Nagar, Begusarai, Bihar 851101</p>
                <p>Contact: +91 95237 98222</p>
                <p>GSTIN: 10EDIPR0137L1ZM</p>
            </div>
            <div class="address-box" style="text-align: right;">
                <h3 class="section-title">Billed To</h3>
                <p class="buyer-name">Name: <?= htmlspecialchars($order['customer_name'] ?? $order['name']) ?></p>
                <p><?= nl2br(htmlspecialchars($order['address'])) ?></p>
                <p>Phone: <?= htmlspecialchars($order['phone'] ?? 'N/A') ?></p>
                <p>Email: <?= htmlspecialchars($order['email']) ?></p>
            </div>
        </div>

        <table>
            <thead>
                <tr>
                    <th>Description</th>
                    <th style="text-align: center;">Price</th>
                    <th style="text-align: center;">Qty</th>
                    <th style="text-align: right;">Total</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($orderItems as $item): ?>
                <tr>
                    <td>
                        <p class="item-name"><?= htmlspecialchars($item['name']) ?></p>
                        <p style="font-size: 11px; color: var(--text-light);">Product ID: #<?= $item['product_id'] ?></p>
                    </td>
                    <td style="text-align: center;">₹<?= number_format($item['price'], 2) ?></td>
                    <td style="text-align: center;">x<?= $item['quantity'] ?></td>
                    <td style="text-align: right; font-weight: 700;">₹<?= number_format($item['price'] * $item['quantity'], 2) ?></td>
                </tr>
                <?php endforeach; ?>
                <tr class="total-row">
                    <td colspan="2"></td>
                    <td class="grand-total-label">Grand Total</td>
                    <td class="grand-total-value">₹<?= number_format($order['total_amount'], 2) ?></td>
                </tr>
            </tbody>
        </table>

        <div class="card" style="background: #f1f5f9; padding: 20px; border-radius: 12px; margin-bottom: 40px;">
            <p style="font-size: 12px; font-weight: 700; color: var(--text-light); text-transform: uppercase; margin-bottom: 8px;">Order Summary</p>
            <div style="display: flex; justify-content: space-between; font-size: 14px;">
                <span>Payment Status:</span>
                <span style="font-weight: 700; color: #166534;">PAID / COD Verified</span>
            </div>
            <div style="display: flex; justify-content: space-between; font-size: 14px; margin-top: 5px;">
                <span>Order Status:</span>
                <span style="font-weight: 700;"><?= strtoupper($order['status']) ?></span>
            </div>
        </div>

        <div class="footer">
            <p style="font-weight: 700; margin-bottom: 5px; color: var(--text);">Thank you for choosing Super Optical!</p>
            <p>Visit us again at www.superoptical.com</p>
            <p style="margin-top: 15px; font-size: 11px;">This is a computer-generated invoice and doesn't require a physical signature.</p>
        </div>
    </div>

</body>
</html>
