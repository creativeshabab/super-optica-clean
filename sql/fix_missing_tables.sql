-- FIX for 500 Error (Missing Tables/Columns)
-- Run this in PHPMyAdmin to ensure your database has the new tables required by the new code.

-- 1. Create shipping_methods table if it doesn't exist
CREATE TABLE IF NOT EXISTS `shipping_methods` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL,
  `method_type` enum('flat_rate','free_shipping','local_pickup','shiprocket','custom') NOT NULL,
  `base_cost` decimal(10,2) DEFAULT 0.00,
  `cost_per_kg` decimal(10,2) DEFAULT 0.00,
  `is_enabled` tinyint(1) DEFAULT 1,
  `min_order_amount` decimal(10,2) DEFAULT 0.00,
  `max_order_amount` decimal(10,2) DEFAULT NULL,
  `estimated_days` varchar(50) DEFAULT NULL,
  `settings` longtext DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 2. Add columns to orders table if they are missing
-- (Most of these seem to exist already, skipping to avoid errors)

-- ALTER TABLE `orders` ADD COLUMN `shipping_method_id` int(11) DEFAULT NULL;
-- ALTER TABLE `orders` ADD COLUMN `shiprocket_order_id` varchar(100) DEFAULT NULL;
-- ALTER TABLE `orders` ADD COLUMN `shiprocket_shipment_id` varchar(100) DEFAULT NULL;
-- ALTER TABLE `orders` ADD COLUMN `tracking_number` varchar(100) DEFAULT NULL;
-- ALTER TABLE `orders` ADD COLUMN `tracking_url` varchar(255) DEFAULT NULL;
-- ALTER TABLE `orders` ADD COLUMN `courier_name` varchar(100) DEFAULT NULL;
-- ALTER TABLE `orders` ADD COLUMN `shipped_at` timestamp NULL DEFAULT NULL;
-- ALTER TABLE `orders` ADD COLUMN `delivered_at` timestamp NULL DEFAULT NULL;

-- 3. Add columns to users table (if missing)
-- ALTER TABLE `users` ADD COLUMN `phone` varchar(20) DEFAULT NULL;
-- ALTER TABLE `users` ADD COLUMN `is_verified` tinyint(1) DEFAULT 0;


-- 4. Create service_integrations table (for Shiprocket/Payment settings)
CREATE TABLE IF NOT EXISTS `service_integrations` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `service_name` varchar(50) NOT NULL,
  `service_type` enum('payment','shipping','email','sms','analytics') NOT NULL,
  `is_enabled` tinyint(1) DEFAULT 0,
  `is_test_mode` tinyint(1) DEFAULT 1,
  `config_data` text DEFAULT NULL,
  `logo_url` varchar(255) DEFAULT NULL,
  `display_name` varchar(100) DEFAULT NULL,
  `display_order` int(11) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `service_name` (`service_name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
