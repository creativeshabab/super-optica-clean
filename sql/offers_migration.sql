-- Final Migration script for Offer Management System
-- This adds all necessary columns to the coupons table

ALTER TABLE coupons 
ADD COLUMN min_order_amount DECIMAL(10,2) DEFAULT 0.00, 
ADD COLUMN is_active TINYINT(1) DEFAULT 1, 
ADD COLUMN is_prepaid_only TINYINT(1) DEFAULT 0, 
ADD COLUMN description VARCHAR(255) DEFAULT NULL,
ADD COLUMN created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
ADD COLUMN updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP;

-- Ensure coupon codes are unique
ALTER TABLE coupons ADD UNIQUE (code);
