<?php
/**
 * Professional HTML Email Templates
 */



// Function to generate Order Email Template
function getOrderEmailTemplate($customer_name, $order_id, $items, $total, $address) {
    $item_rows = "";
    foreach ($items as $item) {
        $item_name = htmlspecialchars($item['name']);
        $item_qty = $item['quantity'];
        $item_price = number_format($item['price'], 2);
        $item_subtotal = number_format($item['price'] * $item['quantity'], 2);
        
        $item_rows .= "
        <tr>
            <td style='padding: 12px; border-bottom: 1px solid #edf2f7;'>$item_name</td>
            <td style='padding: 12px; border-bottom: 1px solid #edf2f7; text-align: center;'>$item_qty</td>
            <td style='padding: 12px; border-bottom: 1px solid #edf2f7; text-align: right;'>₹$item_price</td>
            <td style='padding: 12px; border-bottom: 1px solid #edf2f7; text-align: right;'>₹$item_subtotal</td>
        </tr>";
    }

    $base_url = getBaseURL();
    // $logo_url = $base_url . 'assets/uploads/' . getSetting('site_logo', 'logo.png');

    return "
    <div style='font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto; background: #f7fafc; padding: 20px;'>
        <div style='background: white; border-radius: 8px; overflow: hidden; box-shadow: 0 4px 6px rgba(0,0,0,0.05);'>
            <!-- Header -->
            <div style='background: #e31e24; padding: 30px; text-align: center;'>
                <h1 style='color: white; margin: 0; font-size: 24px;'>Order Confirmed!</h1>
                <p style='color: rgba(255,255,255,0.8); margin-top: 10px;'>Thank you for shopping with Super Optical</p>
            </div>
            
            <!-- Body -->
            <div style='padding: 30px;'>
                <p>Hi <strong>$customer_name</strong>,</p>
                <p>Your order <strong>#$order_id</strong> has been successfully placed. We are preparing it for shipment.</p>
                
                <h3 style='margin-top: 30px; border-bottom: 2px solid #f7fafc; padding-bottom: 10px;'>Order Summary</h3>
                <table style='width: 100%; border-collapse: collapse;'>
                    <thead>
                        <tr style='background: #f7fafc;'>
                            <th style='padding: 12px; text-align: left;'>Item</th>
                            <th style='padding: 12px; text-align: center;'>Qty</th>
                            <th style='padding: 12px; text-align: right;'>Price</th>
                            <th style='padding: 12px; text-align: right;'>Total</th>
                        </tr>
                    </thead>
                    <tbody>
                        $item_rows
                    </tbody>
                    <tfoot>
                        <tr>
                            <td colspan='3' style='padding: 20px 12px 10px; text-align: right; font-weight: bold;'>Total Payable:</td>
                            <td style='padding: 20px 12px 10px; text-align: right; font-weight: bold; font-size: 18px; color: #e31e24;'>₹" . number_format($total, 2) . "</td>
                        </tr>
                    </tfoot>
                </table>
                
                <div style='margin-top: 30px; background: #fffaf0; padding: 20px; border-radius: 8px; border: 1px solid #feebcb;'>
                    <h4 style='margin: 0 0 10px; color: #c05621;'>Shipping To:</h4>
                    <p style='margin: 0; font-size: 14px; line-height: 1.6;'>" . nl2br(htmlspecialchars($address)) . "</p>
                </div>
                
                <div style='margin-top: 40px; text-align: center;'>
                    <a href='{$base_url}my-orders.php' style='background: #1a202c; color: white; padding: 15px 30px; text-decoration: none; border-radius: 6px; font-weight: bold; display: inline-block;'>Track Your Order</a>
                </div>
            </div>
            
            <!-- Footer -->
            <div style='padding: 30px; text-align: center; border-top: 1px solid #edf2f7; color: #718096; font-size: 12px;'>
                <p>&copy; " . date('Y') . " Super Optical. All rights reserved.</p>
                <p>Dak Bunglow Rd, near mahila college, Begusarai, Bihar 851101</p>
            </div>
        </div>
    </div>";
}

/**
 * Admin Alert Template
 */
function getAdminOrderAlertTemplate($customer_name, $order_id, $total) {
    $base_url = getBaseURL();
    return "
    <div style='font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto; background: #fff5f5; padding: 20px;'>
        <div style='background: white; border: 2px solid #feb2b2; border-radius: 12px; padding: 30px;'>
            <h2 style='color: #c53030; margin-top: 0;'>🚨 New Order Received!</h2>
            <p>You have a new sale on Super Optical.</p>
            <hr style='border: 0; border-top: 1px solid #feb2b2; margin: 20px 0;'>
            <table style='width: 100%; font-size: 16px;'>
                <tr>
                    <td style='padding: 10px 0; color: #718096;'>Order ID:</td>
                    <td style='padding: 10px 0; font-weight: bold;'>#$order_id</td>
                </tr>
                <tr>
                    <td style='padding: 10px 0; color: #718096;'>Customer:</td>
                    <td style='padding: 10px 0; font-weight: bold;'>$customer_name</td>
                </tr>
                <tr>
                    <td style='padding: 10px 0; color: #718096;'>Amount:</td>
                    <td style='padding: 10px 0; font-weight: bold; color: #c53030;'>₹" . number_format($total, 2) . "</td>
                </tr>
            </table>
            <div style='margin-top: 30px; text-align: center;'>
                <a href='{$base_url}admin/order_view.php?id=$order_id' style='background: #c53030; color: white; padding: 12px 25px; text-decoration: none; border-radius: 6px; font-weight: bold; display: inline-block;'>Process Order Now</a>
            </div>
        </div>
    </div>";
}

/**
 * OTP Verification Template
 */
function getOTPEmailTemplate($otp_code) {
    return "
    <div style='font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto; background: #f7fafc; padding: 20px;'>
        <div style='background: white; border-radius: 12px; padding: 40px; text-align: center; box-shadow: 0 4px 10px rgba(0,0,0,0.05);'>
            <div style='background: #e31e24; width: 60px; height: 60px; border-radius: 50%; display: inline-flex; align-items: center; justify-content: center; margin-bottom: 20px;'>
                <span style='color: white; font-size: 30px; line-height: 60px;'>🔑</span>
            </div>
            <h2 style='margin: 0; color: #1a202c;'>Verify Your Account</h2>
            <p style='color: #718096; margin-top: 10px;'>Please use the following code to complete your registration.</p>
            
            <div style='margin: 40px 0; letter-spacing: 10px; font-size: 42px; font-weight: 800; color: #e31e24; background: #fff5f5; padding: 20px; border-radius: 8px;'>
                $otp_code
            </div>
            
            <p style='color: #a0aec0; font-size: 14px;'>This code will expire in 10 minutes.</p>
            <p style='margin-top: 40px; color: #718096; font-size: 12px;'>If you did not request this, please ignore this email.</p>
        </div>
    </div>";
}

/**
 * Order Status Update Template
 */
function getOrderStatusUpdateTemplate($customer_name, $order_id, $status) {
    $status_colors = [
        'completed' => '#16a34a',
        'cancelled' => '#dc2626',
        'pending' => '#854d0e',
        'shipped' => '#2563eb'
    ];
    $color = $status_colors[$status] ?? '#1a202c';
    $status_text = strtoupper($status);
    $base_url = getBaseURL();

    return "
    <div style='font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto; background: #f7fafc; padding: 20px;'>
        <div style='background: white; border-radius: 8px; overflow: hidden; box-shadow: 0 4px 6px rgba(0,0,0,0.05);'>
            <div style='padding: 30px; text-align: center; border-bottom: 1px solid #edf2f7;'>
                <h2 style='color: #1a202c; margin: 0;'>Order Update: #$order_id</h2>
            </div>
            <div style='padding: 40px; text-align: center;'>
                <p style='font-size: 18px; color: #4a5568;'>Hi <strong>$customer_name</strong>,</p>
                <p style='color: #718096;'>The status of your order has been updated to:</p>
                <div style='display: inline-block; margin: 20px 0; padding: 15px 40px; border-radius: 50px; background: {$color}20; color: $color; font-weight: 800; font-size: 20px; border: 2px solid $color;'>
                    $status_text
                </div>
                <p style='margin-top: 30px;'>
                    <a href='{$base_url}view-order.php?id=$order_id' style='color: #e31e24; font-weight: bold; text-decoration: underline;'>View Order Details</a>
                </p>
            </div>
            <div style='padding: 30px; text-align: center; background: #f7fafc; border-top: 1px solid #edf2f7; color: #718096; font-size: 12px;'>
                <p>&copy; " . date('Y') . " Super Optical. Stay focused on quality.</p>
            </div>
        </div>
    </div>";
}

/**
 * Welcome Email Template
 */
function getWelcomeEmailTemplate($name) {
    $base_url = getBaseURL();
    return "
    <div style='font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto; background: #f7fafc; padding: 20px;'>
        <div style='background: white; border-radius: 8px; overflow: hidden; box-shadow: 0 4px 6px rgba(0,0,0,0.05);'>
            <div style='background: #e31e24; padding: 30px; text-align: center;'>
                <h1 style='color: white; margin: 0;'>Welcome to Super Optical!</h1>
            </div>
            
            <div style='padding: 30px;'>
                <p style='font-size: 16px; color: #333;'>Hello <strong>" . htmlspecialchars($name) . "</strong>,</p>
                
                <p style='font-size: 16px; color: #555; line-height: 1.6;'>
                    Thank you for creating an account with Super Optical. We're thrilled to have you on board!
                </p>
                
                <p style='font-size: 16px; color: #555; line-height: 1.6;'>
                    You can now:
                </p>
                
                <ul style='color: #555; line-height: 1.6;'>
                    <li>Track your orders easily</li>
                    <li>Save your prescriptions</li>
                    <li>Checkout faster</li>
                </ul>
                
                <div style='text-align: center; margin: 30px 0;'>
                    <a href='" . $base_url . "shop.php' style='background: #e31e24; color: white; padding: 12px 25px; text-decoration: none; border-radius: 5px; font-weight: bold;'>Start Shopping</a>
                </div>
                
                <p style='font-size: 14px; color: #888; margin-top: 30px;'>
                    If you have any questions, feel free to contact us at <a href='mailto:info@superoptical.in'>info@superoptical.in</a>
                </p>
            </div>
            
            <div style='background: #f8f9fa; padding: 15px; text-align: center; font-size: 12px; color: #888;'>
                &copy; " . date('Y') . " Super Optical. All rights reserved.
            </div>
        </div>
    </div>
    ";
}

/**
 * Password Reset Template
 */
function getPasswordResetEmailTemplate($name, $reset_link) {
    return "
    <div style='font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto; padding: 20px; border: 1px solid #e2e8f0; border-radius: 12px;'>
        <h2 style='color: #e31e24; margin-bottom: 20px;'>Password Reset Request</h2>
        <p>Hello <strong>" . htmlspecialchars($name) . "</strong>,</p>
        <p>We received a request to reset your password for your Super Optical account. Click the button below to set a new password:</p>
        <div style='text-align: center; margin: 30px 0;'>
            <a href='$reset_link' style='background: #e31e24; color: white; padding: 12px 24px; text-decoration: none; border-radius: 8px; font-weight: bold; display: inline-block;'>Reset Password</a>
        </div>
        <p style='color: #64748b; font-size: 0.9rem;'>This link will expire in 1 hour. If you did not request this, please ignore this email.</p>
        <hr style='border: none; border-top: 1px solid #f1f5f9; margin: 20px 0;'>
        <p style='font-size: 0.8rem; color: #94a3b8;'>Super Optical | Begusarai's Premier Eyewear</p>
    </div>";
}
/**
 * Appointment Confirmation Template
 */
function getAppointmentConfirmationTemplate($name, $date, $time_slot) {
    $base_url = getBaseURL();
    return "
    <div style='font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto; background: #f7fafc; padding: 20px;'>
        <div style='background: white; border-radius: 12px; overflow: hidden; box-shadow: 0 4px 6px rgba(0,0,0,0.05);'>
            <div style='background: #e31e24; padding: 30px; text-align: center;'>
                <h1 style='color: white; margin: 0; font-size: 24px;'>Appointment Confirmed!</h1>
                <p style='color: rgba(255,255,255,0.9); margin-top: 5px;'>Your eye test is scheduled.</p>
            </div>
            
            <div style='padding: 30px;'>
                <p style='font-size: 16px; color: #333;'>Hi <strong>" . htmlspecialchars($name) . "</strong>,</p>
                <p style='color: #555; line-height: 1.6;'>
                    Thank you for booking an appointment with Super Optical. We look forward to seeing you.
                </p>
                
                <div style='background: #fff5f5; border-left: 4px solid #e31e24; padding: 15px 20px; margin: 25px 0; border-radius: 4px;'>
                    <h3 style='margin: 0 0 10px; color: #c53030;'>Appointment Details</h3>
                    <p style='margin: 5px 0;'><strong>Date:</strong> " . date('l, F j, Y', strtotime($date)) . "</p>
                    <p style='margin: 5px 0;'><strong>Time:</strong> " . htmlspecialchars($time_slot) . "</p>
                </div>
                
                <p style='font-size: 14px; color: #718096; margin-top: 30px;'>
                    Please arrive 5 minutes early. If you need to reschedule, please call us at +91 95237 98222.
                </p>
            </div>
            
            <div style='background: #f8f9fa; padding: 15px; text-align: center; font-size: 12px; color: #888;'>
                <p>&copy; " . date('Y') . " Super Optical. All rights reserved.</p>
                <p>Dak Bunglow Rd, near mahila college, Begusarai, Bihar 851101</p>
            </div>
        </div>
    </div>";
}
?>
