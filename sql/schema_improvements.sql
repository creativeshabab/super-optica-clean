-- Database Schema Improvements for Super Optical
-- Safe Mode updates to add relationships without breaking existing data

-- 1. Orders -> Users Relationship
-- Check if user_id exists in users table before adding constraint (Manual check recommended first)
-- ALTER TABLE orders ADD CONSTRAINT fk_orders_users FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL;

-- 2. Order Items -> Orders Relationship
ALTER TABLE order_items ADD CONSTRAINT fk_order_items_order FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE;

-- 3. Order Items -> Products Relationship
ALTER TABLE order_items ADD CONSTRAINT fk_order_items_product FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE SET NULL;

-- 4. Products -> Brands Relationship
ALTER TABLE products ADD CONSTRAINT fk_products_brands FOREIGN KEY (brand_id) REFERENCES brands(id) ON DELETE SET NULL;

-- 5. Products -> Categories Relationship
ALTER TABLE products ADD CONSTRAINT fk_products_categories FOREIGN KEY (category_id) REFERENCES categories(id) ON DELETE SET NULL;

-- 6. Inventory -> Products Relationship
ALTER TABLE inventory ADD CONSTRAINT fk_inventory_products FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE;

-- 7. Inventory -> Warehouses Relationship
ALTER TABLE inventory ADD CONSTRAINT fk_inventory_warehouses FOREIGN KEY (warehouse_id) REFERENCES warehouses(id) ON DELETE CASCADE;

-- 8. Payments -> Orders Relationship
ALTER TABLE payments ADD CONSTRAINT fk_payments_orders FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE;

-- 9. Shipments -> Orders Relationship
ALTER TABLE shipments ADD CONSTRAINT fk_shipments_orders FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE;

-- 10. Addresses -> Users Relationship
ALTER TABLE addresses ADD CONSTRAINT fk_addresses_users FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE;

-- 11. Product Variants (New Feature)
-- Add image_path column if it doesn't exist
-- ALTER TABLE product_variants ADD COLUMN image_path VARCHAR(255) DEFAULT NULL;

-- Add Foreign Key to Products (Cascade Delete)
ALTER TABLE product_variants ADD CONSTRAINT fk_product_variants_product_id FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE;

-- Note: Run these commands individually or as a block. 
-- If any command fails due to data inconsistency (e.g. order_id in order_items not found in orders), 
-- you must clean up the data first.
