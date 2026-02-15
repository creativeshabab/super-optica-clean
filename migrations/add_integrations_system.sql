-- ============================================================================
-- CENTRALIZED ADMIN PANEL - SERVICE INTEGRATIONS & SHIPPING SYSTEM
-- ============================================================================
-- This migration adds support for managing payment gateways, shipping services,
-- and other third-party integrations from the admin panel.
--
-- Run this migration in phpMyAdmin or MySQL command line
-- ============================================================================

-- ============================================================================
-- 1. SERVICE INTEGRATIONS TABLE
-- ============================================================================
-- Stores configuration for all third-party services (payment, shipping, etc.)
CREATE TABLE IF NOT EXISTS service_integrations (
    id INT AUTO_INCREMENT PRIMARY KEY,
    service_name VARCHAR(50) NOT NULL UNIQUE COMMENT 'razorpay, stripe, shiprocket, etc.',
    service_type ENUM('payment', 'shipping', 'email', 'sms', 'analytics') NOT NULL,
    is_enabled TINYINT(1) DEFAULT 0,
    is_test_mode TINYINT(1) DEFAULT 1,
    config_data TEXT COMMENT 'Encrypted JSON configuration with API credentials',
    logo_url VARCHAR(255),
    display_name VARCHAR(100),
    display_order INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_service_type (service_type),
    INDEX idx_enabled (is_enabled)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================================
-- 2. SHIPPING METHODS TABLE
-- ============================================================================
-- Defines available shipping methods and their pricing
CREATE TABLE IF NOT EXISTS shipping_methods (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    method_type ENUM('flat_rate', 'free_shipping', 'local_pickup', 'shiprocket', 'custom') NOT NULL,
    base_cost DECIMAL(10,2) DEFAULT 0.00,
    cost_per_kg DECIMAL(10,2) DEFAULT 0.00,
    is_enabled TINYINT(1) DEFAULT 1,
    min_order_amount DECIMAL(10,2) DEFAULT 0.00 COMMENT 'Minimum order value required',
    max_order_amount DECIMAL(10,2) DEFAULT NULL COMMENT 'Maximum order value allowed',
    estimated_days VARCHAR(50) COMMENT 'Delivery time estimate (e.g., "3-5 days")',
    settings JSON COMMENT 'Additional method-specific settings',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================================
-- 3. SHIPPING ZONES TABLE
-- ============================================================================
-- Geographic zones for zone-based shipping rates
CREATE TABLE IF NOT EXISTS shipping_zones (
    id INT AUTO_INCREMENT PRIMARY KEY,
    zone_name VARCHAR(100) NOT NULL,
    countries TEXT COMMENT 'Comma-separated country codes (e.g., IN,US,UK)',
    states TEXT COMMENT 'Comma-separated state codes or names',
    postal_codes TEXT COMMENT 'Comma-separated postal codes or ranges',
    is_enabled TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================================
-- 4. SHIPPING ZONE METHODS (Junction Table)
-- ============================================================================
-- Maps shipping methods to zones with optional cost overrides
CREATE TABLE IF NOT EXISTS shipping_zone_methods (
    id INT AUTO_INCREMENT PRIMARY KEY,
    zone_id INT NOT NULL,
    method_id INT NOT NULL,
    cost_override DECIMAL(10,2) DEFAULT NULL COMMENT 'Override base cost for this zone',
    FOREIGN KEY (zone_id) REFERENCES shipping_zones(id) ON DELETE CASCADE,
    FOREIGN KEY (method_id) REFERENCES shipping_methods(id) ON DELETE CASCADE,
    UNIQUE KEY unique_zone_method (zone_id, method_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================================
-- 5. EXTEND ORDERS TABLE - Add Shipping & Tracking Fields
-- ============================================================================
ALTER TABLE orders
ADD COLUMN IF NOT EXISTS shipping_method_id INT DEFAULT NULL COMMENT 'FK to shipping_methods',
ADD COLUMN IF NOT EXISTS shiprocket_order_id VARCHAR(100) DEFAULT NULL COMMENT 'Shiprocket order ID',
ADD COLUMN IF NOT EXISTS shiprocket_shipment_id VARCHAR(100) DEFAULT NULL COMMENT 'Shiprocket shipment ID',
ADD COLUMN IF NOT EXISTS tracking_number VARCHAR(100) DEFAULT NULL COMMENT 'AWB/Tracking number',
ADD COLUMN IF NOT EXISTS tracking_url VARCHAR(255) DEFAULT NULL COMMENT 'Online tracking URL',
ADD COLUMN IF NOT EXISTS shipped_at TIMESTAMP NULL COMMENT 'When order was shipped',
ADD COLUMN IF NOT EXISTS delivered_at TIMESTAMP NULL COMMENT 'When order was delivered',
ADD COLUMN IF NOT EXISTS courier_name VARCHAR(100) DEFAULT NULL COMMENT 'Courier/logistics company',
ADD INDEX IF NOT EXISTS idx_shiprocket_order (shiprocket_order_id),
ADD INDEX IF NOT EXISTS idx_tracking (tracking_number),
ADD INDEX IF NOT EXISTS idx_shipping_method (shipping_method_id);

-- ============================================================================
-- 6. SEED DEFAULT DATA - Payment Gateways
-- ============================================================================
INSERT INTO service_integrations (service_name, service_type, display_name, logo_url, display_order, is_enabled, is_test_mode) VALUES
('razorpay', 'payment', 'Razorpay', 'https://razorpay.com/favicon.png', 1, 0, 1),
('stripe', 'payment', 'Stripe', 'https://stripe.com/favicon.ico', 2, 0, 1),
('payu', 'payment', 'PayU', '', 3, 0, 1),
('paytm', 'payment', 'Paytm', '', 4, 0, 1),
('cashfree', 'payment', 'Cashfree', '', 5, 0, 1),
('cod', 'payment', 'Cash on Delivery', '', 6, 1, 0)
ON DUPLICATE KEY UPDATE 
    display_name = VALUES(display_name),
    logo_url = VALUES(logo_url);

-- ============================================================================
-- 7. SEED DEFAULT DATA - Shipping Services
-- ============================================================================
INSERT INTO service_integrations (service_name, service_type, display_name, display_order, is_enabled, is_test_mode) VALUES
('shiprocket', 'shipping', 'Shiprocket', 1, 0, 1),
('delhivery', 'shipping', 'Delhivery', 2, 0, 1),
('bluedart', 'shipping', 'Blue Dart', 3, 0, 1)
ON DUPLICATE KEY UPDATE 
    display_name = VALUES(display_name);

-- ============================================================================
-- 8. SEED DEFAULT DATA - Default Shipping Methods
-- ============================================================================
INSERT INTO shipping_methods (name, method_type, base_cost, is_enabled, estimated_days) VALUES
('Free Shipping', 'free_shipping', 0.00, 1, '5-7 days'),
('Standard Shipping', 'flat_rate', 50.00, 1, '3-5 days'),
('Express Shipping', 'flat_rate', 150.00, 1, '1-2 days'),
('Local Pickup', 'local_pickup', 0.00, 1, 'Same day')
ON DUPLICATE KEY UPDATE 
    name = VALUES(name);

-- ============================================================================
-- 9. SEED DEFAULT DATA - Default Shipping Zone (India)
-- ============================================================================
INSERT INTO shipping_zones (zone_name, countries, is_enabled) VALUES
('India - All States', 'IN', 1)
ON DUPLICATE KEY UPDATE 
    zone_name = VALUES(zone_name);

-- ============================================================================
-- MIGRATION COMPLETE
-- ============================================================================
-- Next steps:
-- 1. Configure payment gateway credentials in Admin Panel > Integrations
-- 2. Set up Shiprocket account and add API credentials
-- 3. Configure shipping methods and zones as needed
-- ============================================================================
