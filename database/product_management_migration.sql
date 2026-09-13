-- Product Management Module Migration
-- Idempotent: safe to re-run

SET @db := DATABASE();

-- product_categories: Separate from hunt/campaign categories
CREATE TABLE IF NOT EXISTS product_categories (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(120) NOT NULL,
    code VARCHAR(30) NOT NULL UNIQUE,
    icon_url VARCHAR(500) NULL,
    status ENUM('ACTIVE','INACTIVE') NOT NULL DEFAULT 'ACTIVE',
    description TEXT NULL,
    sort_order INT UNSIGNED NOT NULL DEFAULT 0,
    created_by INT UNSIGNED NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (created_by) REFERENCES admins(id),
    INDEX idx_product_cat_status (status),
    INDEX idx_product_cat_code (code)
) ENGINE=InnoDB;

-- vendor_products: Add foreign key to product_categories if not exists
SET @sql := (SELECT IF(
    EXISTS(SELECT 1 FROM information_schema.columns WHERE table_schema=@db AND table_name='vendor_products' AND column_name='category_id'),
    'SELECT 1',
    'ALTER TABLE vendor_products ADD COLUMN category_id BIGINT UNSIGNED NULL AFTER vendor_id'
)); PREPARE s FROM @sql; EXECUTE s; DEALLOCATE PREPARE s;

SET @sql := (SELECT IF(
    EXISTS(SELECT 1 FROM information_schema.table_constraints WHERE table_schema=@db AND table_name='vendor_products' AND constraint_name='fk_vendor_product_category'),
    'SELECT 1',
    'ALTER TABLE vendor_products ADD CONSTRAINT fk_vendor_product_category FOREIGN KEY (category_id) REFERENCES product_categories(id) ON DELETE SET NULL'
)); PREPARE s FROM @sql; EXECUTE s; DEALLOCATE PREPARE s;

-- product_audit_log: Immutable product activity history
CREATE TABLE IF NOT EXISTS product_audit_log (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    product_id BIGINT UNSIGNED NOT NULL,
    product_code VARCHAR(50) NOT NULL,
    vendor_id BIGINT UNSIGNED NOT NULL,
    action VARCHAR(60) NOT NULL,
    field_name VARCHAR(100) NULL,
    old_value TEXT NULL,
    new_value TEXT NULL,
    reason VARCHAR(500) NULL,
    admin_id INT UNSIGNED NULL,
    user_id BIGINT UNSIGNED NULL,
    ip_address VARCHAR(45) NULL,
    user_agent TEXT NULL,
    status ENUM('PENDING','COMPLETED','FAILED') NOT NULL DEFAULT 'COMPLETED',
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_product_audit_product (product_id, created_at),
    INDEX idx_product_audit_vendor (vendor_id, created_at),
    INDEX idx_product_audit_action (action, created_at),
    INDEX idx_product_audit_admin (admin_id, created_at),
    FOREIGN KEY (product_id) REFERENCES vendor_products(id) ON DELETE CASCADE,
    FOREIGN KEY (vendor_id) REFERENCES vendors(id) ON DELETE CASCADE,
    FOREIGN KEY (admin_id) REFERENCES admins(id) ON DELETE SET NULL,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB;

-- product_qr_codes: Centralized product QR management
CREATE TABLE IF NOT EXISTS product_qr_codes (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    qr_code VARCHAR(64) NOT NULL UNIQUE,
    vendor_id BIGINT UNSIGNED NOT NULL,
    product_id BIGINT UNSIGNED NOT NULL,
    product_code VARCHAR(50) NOT NULL,
    payload JSON NOT NULL,
    status ENUM('ACTIVE','REGENERATED','ARCHIVED') NOT NULL DEFAULT 'ACTIVE',
    generated_by INT UNSIGNED NULL,
    regenerated_at DATETIME NULL,
    regenerated_by INT UNSIGNED NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_product_qr_product (product_id),
    INDEX idx_product_qr_vendor (vendor_id),
    INDEX idx_product_qr_code (qr_code),
    FOREIGN KEY (product_id) REFERENCES vendor_products(id) ON DELETE CASCADE,
    FOREIGN KEY (vendor_id) REFERENCES vendors(id) ON DELETE CASCADE,
    FOREIGN KEY (generated_by) REFERENCES admins(id) ON DELETE SET NULL,
    FOREIGN KEY (regenerated_by) REFERENCES admins(id) ON DELETE SET NULL
) ENGINE=InnoDB;

-- product_upload_batches: Track bulk uploads
CREATE TABLE IF NOT EXISTS product_upload_batches (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    batch_code VARCHAR(32) NOT NULL UNIQUE,
    vendor_id BIGINT UNSIGNED NOT NULL,
    admin_id INT UNSIGNED NULL,
    filename VARCHAR(255) NOT NULL,
    total_rows INT UNSIGNED NOT NULL DEFAULT 0,
    success_rows INT UNSIGNED NOT NULL DEFAULT 0,
    failed_rows INT UNSIGNED NOT NULL DEFAULT 0,
    status ENUM('PENDING','PROCESSING','COMPLETED','FAILED') NOT NULL DEFAULT 'PENDING',
    error_summary JSON NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    completed_at DATETIME NULL,
    INDEX idx_batch_vendor (vendor_id, created_at),
    INDEX idx_batch_admin (admin_id, created_at),
    INDEX idx_batch_status (status),
    FOREIGN KEY (vendor_id) REFERENCES vendors(id) ON DELETE CASCADE,
    FOREIGN KEY (admin_id) REFERENCES admins(id) ON DELETE SET NULL
) ENGINE=InnoDB;

-- product_upload_records: Individual row results for each batch
CREATE TABLE IF NOT EXISTS product_upload_records (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    batch_id BIGINT UNSIGNED NOT NULL,
    `row_num` INT UNSIGNED NOT NULL,
    product_code VARCHAR(50) NULL,
    title VARCHAR(190) NULL,
    status ENUM('SUCCESS','FAILED','SKIPPED') NOT NULL,
    error_message TEXT NULL,
    product_id BIGINT UNSIGNED NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_upload_batch (batch_id, `row_num`),
    INDEX idx_upload_product (product_id),
    FOREIGN KEY (batch_id) REFERENCES product_upload_batches(id) ON DELETE CASCADE,
    FOREIGN KEY (product_id) REFERENCES vendor_products(id) ON DELETE SET NULL
) ENGINE=InnoDB;

-- Admin permissions for Product Management
INSERT IGNORE INTO admin_permissions(code, description) VALUES
    ('products.view', 'View product directory'),
    ('products.create', 'Create new products'),
    ('products.edit', 'Edit existing products'),
    ('products.delete', 'Delete/disable products'),
    ('products.approve', 'Approve/reject products'),
    ('products.export', 'Export product data'),
    ('products.bulk_upload', 'Bulk upload products'),
    ('products.categories.manage', 'Manage product categories'),
    ('products.qr.manage', 'Manage product QR codes'),
    ('products.audit.view', 'View product audit history');

INSERT IGNORE INTO admin_role_permissions(role_id, permission_id)
SELECT r.id, p.id FROM admin_roles r JOIN admin_permissions p ON p.code IN (
    'products.view','products.create','products.edit','products.delete',
    'products.approve','products.export','products.bulk_upload',
    'products.categories.manage','products.qr.manage','products.audit.view'
) WHERE r.name = 'Super Admin';

-- Seed default product categories
INSERT IGNORE INTO product_categories (id, name, code, description, sort_order, status) VALUES
    (1, 'Electronics', 'ELECTRONICS', 'Electronic devices and accessories', 10, 'ACTIVE'),
    (2, 'Clothing & Apparel', 'CLOTHING', 'Fashion, clothing and wearables', 20, 'ACTIVE'),
    (3, 'Food & Beverages', 'FOOD', 'Food items, beverages and groceries', 30, 'ACTIVE'),
    (4, 'Health & Beauty', 'HEALTH_BEAUTY', 'Healthcare, cosmetics and personal care', 40, 'ACTIVE'),
    (5, 'Home & Garden', 'HOME_GARDEN', 'Home decor, furniture and garden supplies', 50, 'ACTIVE'),
    (6, 'Sports & Outdoors', 'SPORTS', 'Sports equipment and outdoor gear', 60, 'ACTIVE'),
    (7, 'Books & Media', 'BOOKS_MEDIA', 'Books, digital media and entertainment', 70, 'ACTIVE'),
    (8, 'Automotive', 'AUTOMOTIVE', 'Vehicle parts, accessories and tools', 80, 'ACTIVE'),
    (9, 'Toys & Games', 'TOYS_GAMES', 'Toys, games and children products', 90, 'ACTIVE'),
    (10, 'Other', 'OTHER', 'Miscellaneous products', 999, 'ACTIVE');