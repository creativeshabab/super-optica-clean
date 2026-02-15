-- Payment Gateway Integration - Database Schema Updates
-- Run this in phpMyAdmin or MySQL command line

-- Add payment tracking columns to orders table
ALTER TABLE orders 
ADD COLUMN razorpay_order_id VARCHAR(100) NULL AFTER payment_method,
ADD COLUMN razorpay_payment_id VARCHAR(100) NULL,
ADD COLUMN razorpay_signature VARCHAR(255) NULL,
ADD COLUMN payment_status ENUM('pending', 'paid', 'failed', 'refunded') DEFAULT 'pending',
ADD COLUMN paid_at TIMESTAMP NULL;

-- Create payment logs table for transaction tracking
CREATE TABLE IF NOT EXISTS payment_logs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    order_id INT NOT NULL,
    razorpay_order_id VARCHAR(100),
    razorpay_payment_id VARCHAR(100),
    amount DECIMAL(10,2) NOT NULL,
    currency VARCHAR(10) DEFAULT 'INR',
    status VARCHAR(50),
    method VARCHAR(50),
    response_data TEXT,
    error_description TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE,
    INDEX idx_razorpay_order (razorpay_order_id),
    INDEX idx_razorpay_payment (razorpay_payment_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
