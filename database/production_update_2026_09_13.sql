-- Hunter Wallet incremental release update: 2026-09-13
-- EXISTING installations only, based on database/hunter_wallet.sql.
-- Back up and test on a restored staging copy before importing into live.
-- Select your application database first. No USE or database reset is included.
-- Stop application writes/workers during import: MySQL DDL auto-commits.
-- Requires MySQL 8.0.16+ and CREATE, ALTER, INDEX, REFERENCES, TRIGGER,
-- SELECT and INSERT privileges. Do not use mysql --force.
-- No demo users/products/campaigns or financial transactions are inserted.
-- A missing master account starts at zero; existing balances are preserved.
-- Includes vendor onboarding, products, vendor MFA and master wallet migrations.
-- See PRODUCTION_UPDATE.md for deployment and verification instructions.
SET NAMES utf8mb4;
SET @release_old_sql_mode := @@SESSION.sql_mode;
SET SESSION sql_mode = 'STRICT_ALL_TABLES,NO_ENGINE_SUBSTITUTION';

-- Preflight: all of these core objects must already exist.
SELECT a.id, u.id, w.balance, w.bonus_balance, w.promo_balance,
       t.bucket, t.idempotency_key, t.previous_balance, t.new_balance,
       v.id, v.kyc_status, vu.id, wd.payment_reference, vc.id, qb.id
FROM admins a, users u, wallets w, wallet_transactions t, vendors v,
     vendor_users vu, withdrawals wd, video_categories vc, qr_batches qb
WHERE 1=0;

SET @db := DATABASE();
CREATE TABLE IF NOT EXISTS hunt_user_activations (id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY, user_id BIGINT UNSIGNED NOT NULL, hunt_id BIGINT UNSIGNED NOT NULL, status ENUM('ACTIVE','REVOKED','COMPLETED') NOT NULL DEFAULT 'ACTIVE', activated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP, expires_at DATETIME NULL, INDEX idx_activation_status(status));
CREATE TABLE IF NOT EXISTS qr_promo_grants (id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY, grant_id VARCHAR(50) NOT NULL UNIQUE, campaign_id BIGINT UNSIGNED NOT NULL, user_id BIGINT UNSIGNED NOT NULL, device_id VARCHAR(190) NOT NULL, amount DECIMAL(18,2) NOT NULL, status ENUM('ACTIVE','SPENT','EXPIRED','REVERSED') NOT NULL DEFAULT 'ACTIVE', expires_at DATETIME NULL, spent_at DATETIME NULL, created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP, UNIQUE KEY uq_campaign_user_device(campaign_id,user_id,device_id));
CREATE TABLE IF NOT EXISTS merchant_settlements (id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY, settlement_id VARCHAR(60) NOT NULL UNIQUE, checkout_id VARCHAR(50) NOT NULL UNIQUE, merchant_id BIGINT UNSIGNED NOT NULL, bill_amount DECIMAL(18,2) NOT NULL, promo_amount DECIMAL(18,2) NOT NULL DEFAULT 0, customer_paid DECIMAL(18,2) NOT NULL DEFAULT 0, platform_fee DECIMAL(18,2) NOT NULL DEFAULT 0, merchant_amount DECIMAL(18,2) NOT NULL DEFAULT 0, status ENUM('PENDING','SETTLED','REVERSED') NOT NULL DEFAULT 'PENDING', created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP, settled_at DATETIME NULL, INDEX idx_settlement_status(status,created_at));
CREATE TABLE IF NOT EXISTS qr_campaigns (id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY, campaign_code VARCHAR(50) NOT NULL UNIQUE, name VARCHAR(190) NOT NULL, category_id BIGINT UNSIGNED NULL, qr_code_id BIGINT UNSIGNED NOT NULL UNIQUE, video_id BIGINT UNSIGNED NULL, clue_text TEXT NULL, geofence_enabled TINYINT(1) NOT NULL DEFAULT 0, reward_type ENUM('PROMO_CASH','REWARD_COINS','VENDOR_VOUCHER') NOT NULL DEFAULT 'PROMO_CASH', reward_value DECIMAL(18,2) NOT NULL DEFAULT 0, ttl_minutes INT UNSIGNED NULL, auto_reversal_enabled TINYINT(1) NOT NULL DEFAULT 0, merchant_id BIGINT UNSIGNED NULL, scan_frequency ENUM('PER_DEVICE','DAILY','UNLIMITED') NOT NULL DEFAULT 'PER_DEVICE', budget_limit INT UNSIGNED NULL, claimed_count INT UNSIGNED NOT NULL DEFAULT 0, opens_at TIME NULL, closes_at TIME NULL, status ENUM('ACTIVE','INACTIVE','EXPIRED') NOT NULL DEFAULT 'ACTIVE', created_by INT UNSIGNED NOT NULL, created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP, updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP, INDEX idx_qr_campaign_status(status));
SET @sql := (SELECT IF(EXISTS(SELECT 1 FROM information_schema.tables WHERE table_schema=@db AND table_name='video_categories') AND NOT EXISTS(SELECT 1 FROM information_schema.columns WHERE table_schema=@db AND table_name='video_categories' AND column_name='character_name'),'ALTER TABLE video_categories ADD COLUMN character_name VARCHAR(190) NULL AFTER slug','SELECT 1')); PREPARE s FROM @sql; EXECUTE s; DEALLOCATE PREPARE s;
CREATE TABLE IF NOT EXISTS spot_campaigns (spot_id VARCHAR(64) PRIMARY KEY, master_token_hash CHAR(64) NOT NULL UNIQUE, spot_name VARCHAR(255) NOT NULL, category_id BIGINT UNSIGNED NULL, question_mode ENUM('SINGLE','COLLECTION') NOT NULL DEFAULT 'SINGLE', question_id BIGINT UNSIGNED NULL, collection_id BIGINT UNSIGNED NULL, target_scope ENUM('ALL_PUBLIC','WHITELIST','NEW_USER') NOT NULL DEFAULT 'ALL_PUBLIC', whitelisted_users JSON NULL, city_zone VARCHAR(128) NULL, venue_name VARCHAR(255) NULL, gps_mode ENUM('GPS_REQUIRED','DESK_STUDY') NOT NULL DEFAULT 'GPS_REQUIRED', latitude DECIMAL(10,7) NOT NULL DEFAULT 0, longitude DECIMAL(10,7) NOT NULL DEFAULT 0, proximity_alert_radius INT UNSIGNED NOT NULL DEFAULT 30, scanner_unlock_radius INT UNSIGNED NOT NULL DEFAULT 20, indoor_micro_clue TEXT NULL, reward_type ENUM('PROMO_CASH','COUPON_VOUCHER','DIRECT_CASH','MILESTONE') NOT NULL DEFAULT 'PROMO_CASH', reward_amount DECIMAL(18,2) NOT NULL DEFAULT 15, min_cart_value DECIMAL(18,2) NOT NULL DEFAULT 300, ttl_minutes INT UNSIGNED NOT NULL DEFAULT 30, auto_reversal TINYINT(1) NOT NULL DEFAULT 1, max_budget_scans INT UNSIGNED NOT NULL DEFAULT 500, scan_frequency_policy ENUM('PER_DEVICE','SINGLE_USE','DAILY','POOL') NOT NULL DEFAULT 'PER_DEVICE', is_active TINYINT(1) NOT NULL DEFAULT 1, created_by INT UNSIGNED NOT NULL, created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP, updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP, INDEX idx_spot_active(is_active,category_id)) ENGINE=InnoDB;
CREATE TABLE IF NOT EXISTS riddle_pool (riddle_id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY, spot_id VARCHAR(64) NOT NULL, riddle_text TEXT NOT NULL, primary_answer VARCHAR(128) NOT NULL, synonyms JSON NOT NULL, yt_video_id VARCHAR(32) NULL, weight_percentage INT UNSIGNED NOT NULL DEFAULT 100, created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP, INDEX idx_riddle_spot(spot_id,weight_percentage), FOREIGN KEY(spot_id) REFERENCES spot_campaigns(spot_id) ON DELETE CASCADE) ENGINE=InnoDB;
-- Add columns to installations running MySQL versions without ADD COLUMN IF NOT EXISTS.
SET @sql := (SELECT IF(EXISTS(SELECT 1 FROM information_schema.tables WHERE table_schema=@db AND table_name='riddle_pool') AND NOT EXISTS(SELECT 1 FROM information_schema.columns WHERE table_schema=@db AND table_name='riddle_pool' AND column_name='question_mode'), 'ALTER TABLE riddle_pool ADD COLUMN question_mode ENUM(''SINGLE'',''MULTIPLE'') NOT NULL DEFAULT ''SINGLE'' AFTER spot_id', 'SELECT 1')); PREPARE s FROM @sql; EXECUTE s; DEALLOCATE PREPARE s;
SET @sql := (SELECT IF(EXISTS(SELECT 1 FROM information_schema.tables WHERE table_schema=@db AND table_name='riddle_pool') AND NOT EXISTS(SELECT 1 FROM information_schema.columns WHERE table_schema=@db AND table_name='riddle_pool' AND column_name='instruction_text'), 'ALTER TABLE riddle_pool ADD COLUMN instruction_text TEXT NULL AFTER riddle_text', 'SELECT 1')); PREPARE s FROM @sql; EXECUTE s; DEALLOCATE PREPARE s;
SET @sql := (SELECT IF(EXISTS(SELECT 1 FROM information_schema.tables WHERE table_schema=@db AND table_name='riddle_pool') AND NOT EXISTS(SELECT 1 FROM information_schema.columns WHERE table_schema=@db AND table_name='riddle_pool' AND column_name='channel_url'), 'ALTER TABLE riddle_pool ADD COLUMN channel_url VARCHAR(500) NULL AFTER yt_video_id', 'SELECT 1')); PREPARE s FROM @sql; EXECUTE s; DEALLOCATE PREPARE s;
SET @sql := (SELECT IF(EXISTS(SELECT 1 FROM information_schema.tables WHERE table_schema=@db AND table_name='riddle_pool') AND NOT EXISTS(SELECT 1 FROM information_schema.columns WHERE table_schema=@db AND table_name='riddle_pool' AND column_name='answer_time_seconds'), 'ALTER TABLE riddle_pool ADD COLUMN answer_time_seconds INT UNSIGNED NOT NULL DEFAULT 60 AFTER channel_url', 'SELECT 1')); PREPARE s FROM @sql; EXECUTE s; DEALLOCATE PREPARE s;
CREATE TABLE IF NOT EXISTS scan_audit_ledger (audit_id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY, device_hash VARCHAR(128) NOT NULL, user_id BIGINT UNSIGNED NOT NULL, spot_id VARCHAR(64) NOT NULL, riddle_id BIGINT UNSIGNED NULL, user_input_answer TEXT NULL, is_correct TINYINT(1) NOT NULL DEFAULT 0, attempt_count INT UNSIGNED NOT NULL DEFAULT 1, gps_lat_submitted DECIMAL(10,7) NULL, gps_lng_submitted DECIMAL(10,7) NULL, distance_calculated_m DECIMAL(12,2) NULL, status ENUM('PASS','WRONG_ATTEMPT','BLOCKED_DEVICE','EXHAUSTED','EXPIRED','SETTLED') NOT NULL, created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP, INDEX idx_scan_audit_spot(spot_id,created_at), INDEX idx_scan_device(device_hash,spot_id)) ENGINE=InnoDB;
SET @sql := (SELECT IF(EXISTS(SELECT 1 FROM information_schema.tables WHERE table_schema=@db AND table_name='vendors') AND NOT EXISTS(SELECT 1 FROM information_schema.columns WHERE table_schema=@db AND table_name='vendors' AND column_name='logo_path'),'ALTER TABLE vendors ADD COLUMN logo_path VARCHAR(255) NULL AFTER address','SELECT 1')); PREPARE s FROM @sql; EXECUTE s; DEALLOCATE PREPARE s;
SET @sql := (SELECT IF(EXISTS(SELECT 1 FROM information_schema.tables WHERE table_schema=@db AND table_name='vendors') AND NOT EXISTS(SELECT 1 FROM information_schema.columns WHERE table_schema=@db AND table_name='vendors' AND column_name='cover_image_path'),'ALTER TABLE vendors ADD COLUMN cover_image_path VARCHAR(255) NULL AFTER logo_path','SELECT 1')); PREPARE s FROM @sql; EXECUTE s; DEALLOCATE PREPARE s;
SET @sql := (SELECT IF(EXISTS(SELECT 1 FROM information_schema.tables WHERE table_schema=@db AND table_name='spot_campaigns') AND NOT EXISTS(SELECT 1 FROM information_schema.columns WHERE table_schema=@db AND table_name='spot_campaigns' AND column_name='gps_mode'),'ALTER TABLE spot_campaigns ADD COLUMN gps_mode ENUM(''GPS_REQUIRED'',''DESK_STUDY'') NOT NULL DEFAULT ''GPS_REQUIRED'' AFTER venue_name','SELECT 1')); PREPARE s FROM @sql; EXECUTE s; DEALLOCATE PREPARE s;
INSERT IGNORE INTO system_settings(`key`,`value`,is_secret) VALUES ('brand_name','Hunter Wallet',0),('brand_tagline','Secure administration portal',0),('theme_mode','light',0),('primary_color','#12304a',0),('accent_color','#24c79a',0),('default_currency','INR',0),('smtp_port','587',0),('session_timeout','1800',0);
SET @sql := (SELECT IF(EXISTS(SELECT 1 FROM information_schema.tables WHERE table_schema=@db AND table_name='qr_batches') AND NOT EXISTS(SELECT 1 FROM information_schema.columns WHERE table_schema=@db AND table_name='qr_batches' AND column_name='question_bank_category'), 'ALTER TABLE qr_batches ADD COLUMN question_bank_category VARCHAR(190) NULL AFTER category', 'SELECT 1')); PREPARE s FROM @sql; EXECUTE s; DEALLOCATE PREPARE s;
SET @sql := (SELECT IF(EXISTS(SELECT 1 FROM information_schema.tables WHERE table_schema=@db AND table_name='riddle_pool') AND NOT EXISTS(SELECT 1 FROM information_schema.columns WHERE table_schema=@db AND table_name='riddle_pool' AND column_name='status'), 'ALTER TABLE riddle_pool ADD COLUMN status ENUM(''ACTIVE'',''INACTIVE'') NOT NULL DEFAULT ''ACTIVE'' AFTER weight_percentage', 'SELECT 1')); PREPARE s FROM @sql; EXECUTE s; DEALLOCATE PREPARE s;
SET @sql := (SELECT IF(EXISTS(SELECT 1 FROM information_schema.tables WHERE table_schema=@db AND table_name='riddle_pool') AND NOT EXISTS(SELECT 1 FROM information_schema.columns WHERE table_schema=@db AND table_name='riddle_pool' AND column_name='updated_at'), 'ALTER TABLE riddle_pool ADD COLUMN updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP AFTER created_at', 'SELECT 1')); PREPARE s FROM @sql; EXECUTE s; DEALLOCATE PREPARE s;
CREATE TABLE IF NOT EXISTS question_collections (collection_id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY, collection_name VARCHAR(190) NOT NULL, spot_id VARCHAR(64) NOT NULL, description TEXT NULL, rotation_method ENUM('SEQUENTIAL','RANDOM','RANDOM_NO_REPEAT','ROUND_ROBIN','WEIGHTED') NOT NULL DEFAULT 'SEQUENTIAL', questions_per_session INT UNSIGNED NOT NULL DEFAULT 1, status ENUM('ACTIVE','INACTIVE') NOT NULL DEFAULT 'ACTIVE', created_by INT UNSIGNED NOT NULL, created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP, updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP, FOREIGN KEY(spot_id) REFERENCES spot_campaigns(spot_id) ON DELETE CASCADE, FOREIGN KEY(created_by) REFERENCES admins(id), INDEX idx_collection_status(status,spot_id)) ENGINE=InnoDB;
CREATE TABLE IF NOT EXISTS question_collection_items (collection_id BIGINT UNSIGNED NOT NULL, riddle_id BIGINT UNSIGNED NOT NULL, question_order INT UNSIGNED NOT NULL DEFAULT 1, PRIMARY KEY(collection_id,riddle_id), UNIQUE KEY uq_collection_order(collection_id,question_order), FOREIGN KEY(collection_id) REFERENCES question_collections(collection_id) ON DELETE CASCADE, FOREIGN KEY(riddle_id) REFERENCES riddle_pool(riddle_id) ON DELETE CASCADE) ENGINE=InnoDB;
SET @sql := (SELECT IF(EXISTS(SELECT 1 FROM information_schema.tables WHERE table_schema=@db AND table_name='spot_campaigns') AND NOT EXISTS(SELECT 1 FROM information_schema.columns WHERE table_schema=@db AND table_name='spot_campaigns' AND column_name='question_mode'), 'ALTER TABLE spot_campaigns ADD COLUMN question_mode ENUM(''SINGLE'',''COLLECTION'') NOT NULL DEFAULT ''SINGLE'' AFTER category_id', 'SELECT 1')); PREPARE s FROM @sql; EXECUTE s; DEALLOCATE PREPARE s;
SET @sql := (SELECT IF(EXISTS(SELECT 1 FROM information_schema.tables WHERE table_schema=@db AND table_name='spot_campaigns') AND NOT EXISTS(SELECT 1 FROM information_schema.columns WHERE table_schema=@db AND table_name='spot_campaigns' AND column_name='question_id'), 'ALTER TABLE spot_campaigns ADD COLUMN question_id BIGINT UNSIGNED NULL AFTER question_mode', 'SELECT 1')); PREPARE s FROM @sql; EXECUTE s; DEALLOCATE PREPARE s;
SET @sql := (SELECT IF(EXISTS(SELECT 1 FROM information_schema.tables WHERE table_schema=@db AND table_name='spot_campaigns') AND NOT EXISTS(SELECT 1 FROM information_schema.columns WHERE table_schema=@db AND table_name='spot_campaigns' AND column_name='collection_id'), 'ALTER TABLE spot_campaigns ADD COLUMN collection_id BIGINT UNSIGNED NULL AFTER question_id', 'SELECT 1')); PREPARE s FROM @sql; EXECUTE s; DEALLOCATE PREPARE s;
INSERT IGNORE INTO admin_permissions(code,description) VALUES ('security.governance','View Security & Governance dashboard and staff controls');
INSERT IGNORE INTO admin_role_permissions(role_id,permission_id) SELECT r.id,p.id FROM admin_roles r JOIN admin_permissions p ON p.code='security.governance' WHERE r.name='Super Admin';
SET @sql := (SELECT IF(EXISTS(SELECT 1 FROM information_schema.tables WHERE table_schema=@db AND table_name='vendors') AND NOT EXISTS(SELECT 1 FROM information_schema.columns WHERE table_schema=@db AND table_name='vendors' AND column_name='latitude'),'ALTER TABLE vendors ADD COLUMN latitude DECIMAL(10,7) NULL AFTER address','SELECT 1')); PREPARE s FROM @sql; EXECUTE s; DEALLOCATE PREPARE s;
SET @sql := (SELECT IF(EXISTS(SELECT 1 FROM information_schema.tables WHERE table_schema=@db AND table_name='vendors') AND NOT EXISTS(SELECT 1 FROM information_schema.columns WHERE table_schema=@db AND table_name='vendors' AND column_name='longitude'),'ALTER TABLE vendors ADD COLUMN longitude DECIMAL(10,7) NULL AFTER latitude','SELECT 1')); PREPARE s FROM @sql; EXECUTE s; DEALLOCATE PREPARE s;
SET @sql := (SELECT IF(EXISTS(SELECT 1 FROM information_schema.tables WHERE table_schema=@db AND table_name='vendors') AND NOT EXISTS(SELECT 1 FROM information_schema.columns WHERE table_schema=@db AND table_name='vendors' AND column_name='owner_name'),'ALTER TABLE vendors ADD COLUMN owner_name VARCHAR(190) NULL AFTER shop_name','SELECT 1')); PREPARE s FROM @sql; EXECUTE s; DEALLOCATE PREPARE s;
SET @sql := (SELECT IF(EXISTS(SELECT 1 FROM information_schema.tables WHERE table_schema=@db AND table_name='vendors') AND NOT EXISTS(SELECT 1 FROM information_schema.columns WHERE table_schema=@db AND table_name='vendors' AND column_name='owner_address'),'ALTER TABLE vendors ADD COLUMN owner_address VARCHAR(500) NULL AFTER owner_name','SELECT 1')); PREPARE s FROM @sql; EXECUTE s; DEALLOCATE PREPARE s;
SET @sql := (SELECT IF(EXISTS(SELECT 1 FROM information_schema.tables WHERE table_schema=@db AND table_name='vendors') AND NOT EXISTS(SELECT 1 FROM information_schema.columns WHERE table_schema=@db AND table_name='vendors' AND column_name='gstin'),'ALTER TABLE vendors ADD COLUMN gstin VARCHAR(30) NULL AFTER owner_address','SELECT 1')); PREPARE s FROM @sql; EXECUTE s; DEALLOCATE PREPARE s;
SET @sql := (SELECT IF(EXISTS(SELECT 1 FROM information_schema.tables WHERE table_schema=@db AND table_name='vendors') AND NOT EXISTS(SELECT 1 FROM information_schema.columns WHERE table_schema=@db AND table_name='vendors' AND column_name='identity_proof_path'),'ALTER TABLE vendors ADD COLUMN identity_proof_path VARCHAR(255) NULL AFTER gstin','SELECT 1')); PREPARE s FROM @sql; EXECUTE s; DEALLOCATE PREPARE s;
SET @sql := (SELECT IF(EXISTS(SELECT 1 FROM information_schema.tables WHERE table_schema=@db AND table_name='vendors') AND NOT EXISTS(SELECT 1 FROM information_schema.columns WHERE table_schema=@db AND table_name='vendors' AND column_name='address_proof_path'),'ALTER TABLE vendors ADD COLUMN address_proof_path VARCHAR(255) NULL AFTER identity_proof_path','SELECT 1')); PREPARE s FROM @sql; EXECUTE s; DEALLOCATE PREPARE s;
SET @sql := (SELECT IF(EXISTS(SELECT 1 FROM information_schema.tables WHERE table_schema=@db AND table_name='vendors') AND NOT EXISTS(SELECT 1 FROM information_schema.columns WHERE table_schema=@db AND table_name='vendors' AND column_name='bank_account_holder'),'ALTER TABLE vendors ADD COLUMN bank_account_holder VARCHAR(190) NULL AFTER address_proof_path','SELECT 1')); PREPARE s FROM @sql; EXECUTE s; DEALLOCATE PREPARE s;
SET @sql := (SELECT IF(EXISTS(SELECT 1 FROM information_schema.tables WHERE table_schema=@db AND table_name='vendors') AND NOT EXISTS(SELECT 1 FROM information_schema.columns WHERE table_schema=@db AND table_name='vendors' AND column_name='bank_account_number'),'ALTER TABLE vendors ADD COLUMN bank_account_number VARCHAR(80) NULL AFTER bank_account_holder','SELECT 1')); PREPARE s FROM @sql; EXECUTE s; DEALLOCATE PREPARE s;
SET @sql := (SELECT IF(EXISTS(SELECT 1 FROM information_schema.tables WHERE table_schema=@db AND table_name='vendors') AND NOT EXISTS(SELECT 1 FROM information_schema.columns WHERE table_schema=@db AND table_name='vendors' AND column_name='bank_ifsc'),'ALTER TABLE vendors ADD COLUMN bank_ifsc VARCHAR(20) NULL AFTER bank_account_number','SELECT 1')); PREPARE s FROM @sql; EXECUTE s; DEALLOCATE PREPARE s;
SET @sql := (SELECT IF(EXISTS(SELECT 1 FROM information_schema.tables WHERE table_schema=@db AND table_name='vendors') AND NOT EXISTS(SELECT 1 FROM information_schema.columns WHERE table_schema=@db AND table_name='vendors' AND column_name='bank_branch'),'ALTER TABLE vendors ADD COLUMN bank_branch VARCHAR(120) NULL AFTER bank_ifsc','SELECT 1')); PREPARE s FROM @sql; EXECUTE s; DEALLOCATE PREPARE s;
SET @sql := (SELECT IF(EXISTS(SELECT 1 FROM information_schema.tables WHERE table_schema=@db AND table_name='vendors') AND NOT EXISTS(SELECT 1 FROM information_schema.columns WHERE table_schema=@db AND table_name='vendors' AND column_name='settlement_upi'),'ALTER TABLE vendors ADD COLUMN settlement_upi VARCHAR(190) NULL AFTER bank_branch','SELECT 1')); PREPARE s FROM @sql; EXECUTE s; DEALLOCATE PREPARE s;
SET @sql := (SELECT IF(EXISTS(SELECT 1 FROM information_schema.tables WHERE table_schema=@db AND table_name='vendors') AND NOT EXISTS(SELECT 1 FROM information_schema.columns WHERE table_schema=@db AND table_name='vendors' AND column_name='commission_rate'),'ALTER TABLE vendors ADD COLUMN commission_rate DECIMAL(5,2) NOT NULL DEFAULT 0 AFTER settlement_upi','SELECT 1')); PREPARE s FROM @sql; EXECUTE s; DEALLOCATE PREPARE s;
SET @sql := (SELECT IF(EXISTS(SELECT 1 FROM information_schema.tables WHERE table_schema=@db AND table_name='vendors') AND NOT EXISTS(SELECT 1 FROM information_schema.columns WHERE table_schema=@db AND table_name='vendors' AND column_name='escrow_balance'),'ALTER TABLE vendors ADD COLUMN escrow_balance DECIMAL(18,2) NOT NULL DEFAULT 0 AFTER commission_rate','SELECT 1')); PREPARE s FROM @sql; EXECUTE s; DEALLOCATE PREPARE s;
SET @sql := (SELECT IF(EXISTS(SELECT 1 FROM information_schema.tables WHERE table_schema=@db AND table_name='vendors') AND NOT EXISTS(SELECT 1 FROM information_schema.columns WHERE table_schema=@db AND table_name='vendors' AND column_name='settlement_cycle'),'ALTER TABLE vendors ADD COLUMN settlement_cycle ENUM(''DAILY'',''WEEKLY'',''MONTHLY'') NOT NULL DEFAULT ''DAILY'' AFTER escrow_balance','SELECT 1')); PREPARE s FROM @sql; EXECUTE s; DEALLOCATE PREPARE s;
SET @sql := (SELECT IF(EXISTS(SELECT 1 FROM information_schema.tables WHERE table_schema=@db AND table_name='vendors') AND NOT EXISTS(SELECT 1 FROM information_schema.columns WHERE table_schema=@db AND table_name='vendors' AND column_name='proximity_radius_m'),'ALTER TABLE vendors ADD COLUMN proximity_radius_m INT UNSIGNED NOT NULL DEFAULT 300 AFTER longitude','SELECT 1')); PREPARE s FROM @sql; EXECUTE s; DEALLOCATE PREPARE s;
SET @sql := (SELECT IF(EXISTS(SELECT 1 FROM information_schema.tables WHERE table_schema=@db AND table_name='vendors') AND NOT EXISTS(SELECT 1 FROM information_schema.columns WHERE table_schema=@db AND table_name='vendors' AND column_name='business_category'),'ALTER TABLE vendors ADD COLUMN business_category VARCHAR(120) NULL AFTER shop_name','SELECT 1')); PREPARE s FROM @sql; EXECUTE s; DEALLOCATE PREPARE s;
SET @sql := (SELECT IF(EXISTS(SELECT 1 FROM information_schema.tables WHERE table_schema=@db AND table_name='vendors') AND NOT EXISTS(SELECT 1 FROM information_schema.columns WHERE table_schema=@db AND table_name='vendors' AND column_name='youtube_promo_url'),'ALTER TABLE vendors ADD COLUMN youtube_promo_url VARCHAR(500) NULL AFTER cover_image_path','SELECT 1')); PREPARE s FROM @sql; EXECUTE s; DEALLOCATE PREPARE s;
SET @sql := (SELECT IF(EXISTS(SELECT 1 FROM information_schema.tables WHERE table_schema=@db AND table_name='vendors') AND NOT EXISTS(SELECT 1 FROM information_schema.columns WHERE table_schema=@db AND table_name='vendors' AND column_name='cancelled_cheque_path'),'ALTER TABLE vendors ADD COLUMN cancelled_cheque_path VARCHAR(255) NULL AFTER address_proof_path','SELECT 1')); PREPARE s FROM @sql; EXECUTE s; DEALLOCATE PREPARE s;
SET @sql := (SELECT IF(EXISTS(SELECT 1 FROM information_schema.tables WHERE table_schema=@db AND table_name='vendors') AND NOT EXISTS(SELECT 1 FROM information_schema.columns WHERE table_schema=@db AND table_name='vendors' AND column_name='gst_document_path'),'ALTER TABLE vendors ADD COLUMN gst_document_path VARCHAR(255) NULL AFTER gstin','SELECT 1')); PREPARE s FROM @sql; EXECUTE s; DEALLOCATE PREPARE s;
SET @sql := (SELECT IF(EXISTS(SELECT 1 FROM information_schema.tables WHERE table_schema=@db AND table_name='vendors') AND NOT EXISTS(SELECT 1 FROM information_schema.columns WHERE table_schema=@db AND table_name='vendors' AND column_name='mobile_otp_verified'),'ALTER TABLE vendors ADD COLUMN mobile_otp_verified TINYINT(1) NOT NULL DEFAULT 0 AFTER mobile','SELECT 1')); PREPARE s FROM @sql; EXECUTE s; DEALLOCATE PREPARE s;
SET @sql := (SELECT IF(EXISTS(SELECT 1 FROM information_schema.tables WHERE table_schema=@db AND table_name='vendors') AND NOT EXISTS(SELECT 1 FROM information_schema.columns WHERE table_schema=@db AND table_name='vendors' AND column_name='bank_verification_status'),'ALTER TABLE vendors ADD COLUMN bank_verification_status ENUM(''PENDING'',''VERIFIED'',''REJECTED'') NOT NULL DEFAULT ''PENDING'' AFTER settlement_cycle','SELECT 1')); PREPARE s FROM @sql; EXECUTE s; DEALLOCATE PREPARE s;
SET @sql := (SELECT IF(EXISTS(SELECT 1 FROM information_schema.tables WHERE table_schema=@db AND table_name='vendors') AND NOT EXISTS(SELECT 1 FROM information_schema.columns WHERE table_schema=@db AND table_name='vendors' AND column_name='commission_type'),'ALTER TABLE vendors ADD COLUMN commission_type ENUM(''PERCENTAGE'',''CUSTOM_RULE'') NOT NULL DEFAULT ''PERCENTAGE'' AFTER commission_rate','SELECT 1')); PREPARE s FROM @sql; EXECUTE s; DEALLOCATE PREPARE s;
SET @sql := (SELECT IF(EXISTS(SELECT 1 FROM information_schema.tables WHERE table_schema=@db AND table_name='vendors') AND NOT EXISTS(SELECT 1 FROM information_schema.columns WHERE table_schema=@db AND table_name='vendors' AND column_name='commission_rule'),'ALTER TABLE vendors ADD COLUMN commission_rule VARCHAR(500) NULL AFTER commission_type','SELECT 1')); PREPARE s FROM @sql; EXECUTE s; DEALLOCATE PREPARE s;
SET @sql := (SELECT IF(EXISTS(SELECT 1 FROM information_schema.tables WHERE table_schema=@db AND table_name='vendors') AND NOT EXISTS(SELECT 1 FROM information_schema.columns WHERE table_schema=@db AND table_name='vendors' AND column_name='onboarding_notes'),'ALTER TABLE vendors ADD COLUMN onboarding_notes TEXT NULL AFTER commission_rule','SELECT 1')); PREPARE s FROM @sql; EXECUTE s; DEALLOCATE PREPARE s;
SET @sql := (SELECT IF(EXISTS(SELECT 1 FROM information_schema.tables WHERE table_schema=@db AND table_name='vendors') AND NOT EXISTS(SELECT 1 FROM information_schema.columns WHERE table_schema=@db AND table_name='vendors' AND column_name='vendor_qr_payload'),'ALTER TABLE vendors ADD COLUMN vendor_qr_payload VARCHAR(500) NULL AFTER onboarding_notes','SELECT 1')); PREPARE s FROM @sql; EXECUTE s; DEALLOCATE PREPARE s;
SET @sql := (SELECT IF(EXISTS(SELECT 1 FROM information_schema.tables WHERE table_schema=@db AND table_name='vendors'),'ALTER TABLE vendors MODIFY status ENUM(''DRAFT'',''PENDING_KYC'',''PENDING_BANK'',''PENDING_APPROVAL'',''VERIFIED'',''ACTIVE'',''REJECTED'',''SUSPENDED'',''CLOSED'',''BLOCKED'',''INACTIVE'') NOT NULL DEFAULT ''DRAFT''','SELECT 1')); PREPARE s FROM @sql; EXECUTE s; DEALLOCATE PREPARE s;
SET @sql := (SELECT IF(EXISTS(SELECT 1 FROM information_schema.tables WHERE table_schema=@db AND table_name='vendors'),'ALTER TABLE vendors MODIFY kyc_status ENUM(''PENDING'',''APPROVED'',''VERIFIED'',''REJECTED'') NOT NULL DEFAULT ''PENDING''','SELECT 1')); PREPARE s FROM @sql; EXECUTE s; DEALLOCATE PREPARE s;
SET @sql := (SELECT IF(EXISTS(SELECT 1 FROM information_schema.tables WHERE table_schema=@db AND table_name='video_categories') AND NOT EXISTS(SELECT 1 FROM information_schema.columns WHERE table_schema=@db AND table_name='video_categories' AND column_name='commission_type'),'ALTER TABLE video_categories ADD COLUMN commission_type ENUM(''PERCENTAGE'',''FIXED'') NOT NULL DEFAULT ''PERCENTAGE'' AFTER slug','SELECT 1')); PREPARE s FROM @sql; EXECUTE s; DEALLOCATE PREPARE s;
SET @sql := (SELECT IF(EXISTS(SELECT 1 FROM information_schema.tables WHERE table_schema=@db AND table_name='video_categories') AND NOT EXISTS(SELECT 1 FROM information_schema.columns WHERE table_schema=@db AND table_name='video_categories' AND column_name='platform_commission_rate'),'ALTER TABLE video_categories ADD COLUMN platform_commission_rate DECIMAL(5,2) NOT NULL DEFAULT 0 AFTER commission_type','SELECT 1')); PREPARE s FROM @sql; EXECUTE s; DEALLOCATE PREPARE s;
SET @sql := (SELECT IF(EXISTS(SELECT 1 FROM information_schema.tables WHERE table_schema=@db AND table_name='video_categories') AND NOT EXISTS(SELECT 1 FROM information_schema.columns WHERE table_schema=@db AND table_name='video_categories' AND column_name='customer_reward_rate'),'ALTER TABLE video_categories ADD COLUMN customer_reward_rate DECIMAL(5,2) NOT NULL DEFAULT 0 AFTER platform_commission_rate','SELECT 1')); PREPARE s FROM @sql; EXECUTE s; DEALLOCATE PREPARE s;
CREATE TABLE IF NOT EXISTS vendor_products (id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY, vendor_id BIGINT UNSIGNED NOT NULL, product_code VARCHAR(50) NOT NULL UNIQUE, title VARCHAR(190) NOT NULL, description TEXT NULL, price DECIMAL(18,2) NOT NULL DEFAULT 0, image_url VARCHAR(500) NULL, metadata JSON NULL, status ENUM('PENDING_REVIEW','APPROVED','REJECTED','DISABLED','RESUBMITTED') NOT NULL DEFAULT 'PENDING_REVIEW', admin_note VARCHAR(1000) NULL, moderated_by INT UNSIGNED NULL, moderated_at DATETIME NULL, submitted_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP, updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP, FOREIGN KEY(vendor_id) REFERENCES vendors(id), FOREIGN KEY(moderated_by) REFERENCES admins(id), INDEX idx_vendor_product_status(status,submitted_at), INDEX idx_vendor_product_vendor(vendor_id)) ENGINE=InnoDB;
CREATE TABLE IF NOT EXISTS vendor_payout_requests (id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY, vendor_id BIGINT UNSIGNED NOT NULL, amount DECIMAL(18,2) NOT NULL, status ENUM('PENDING','APPROVED','PAID','REJECTED') NOT NULL DEFAULT 'PENDING', requested_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP, paid_at DATETIME NULL, FOREIGN KEY(vendor_id) REFERENCES vendors(id), INDEX idx_vendor_payout_status(vendor_id,status));
CREATE TABLE IF NOT EXISTS vendor_money_requests (id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY, vendor_id BIGINT UNSIGNED NOT NULL, request_code VARCHAR(50) NOT NULL UNIQUE, amount DECIMAL(18,2) NOT NULL, destination_type ENUM('BANK','UPI','WALLET') NOT NULL DEFAULT 'WALLET', destination_reference VARCHAR(190) NULL, status ENUM('PENDING','UNDER_REVIEW','APPROVED','PROCESSING','SETTLED','REJECTED','HELD','FAILED','DISPUTED') NOT NULL DEFAULT 'PENDING', admin_note VARCHAR(1000) NULL, processed_by INT UNSIGNED NULL, processed_at DATETIME NULL, requested_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP, FOREIGN KEY(vendor_id) REFERENCES vendors(id), FOREIGN KEY(processed_by) REFERENCES admins(id), INDEX idx_vendor_money_status(status,requested_at), INDEX idx_vendor_money_vendor(vendor_id));
INSERT IGNORE INTO admin_permissions(code,description) VALUES ('vendor_settlements.view','View vendor money requests and withdrawals'),('vendor_settlements.approve','Approve vendor settlements'),('vendor_settlements.export','Export vendor settlement reports');
INSERT IGNORE INTO admin_role_permissions(role_id,permission_id) SELECT r.id,p.id FROM admin_roles r JOIN admin_permissions p ON p.code IN ('vendor_settlements.view','vendor_settlements.approve','vendor_settlements.export') WHERE r.name='Super Admin';
INSERT IGNORE INTO admin_permissions(code,description) VALUES ('vendor_products.view','View vendor product moderation queue'),('vendor_products.moderate','Approve, reject or disable vendor products'),('vendor_products.export','Export vendor product moderation records');
INSERT IGNORE INTO admin_role_permissions(role_id,permission_id) SELECT r.id,p.id FROM admin_roles r JOIN admin_permissions p ON p.code IN ('vendor_products.view','vendor_products.moderate','vendor_products.export') WHERE r.name='Super Admin';

-- Nullable payout profile fields used by withdrawal detail views and exports.
SET @sql := IF(EXISTS(SELECT 1 FROM information_schema.columns WHERE table_schema=DATABASE() AND table_name='users' AND column_name='settlement_upi'), 'SELECT 1', 'ALTER TABLE `users` ADD COLUMN `settlement_upi` VARCHAR(190) NULL');
PREPARE s FROM @sql; EXECUTE s; DEALLOCATE PREPARE s;
SET @sql := IF(EXISTS(SELECT 1 FROM information_schema.columns WHERE table_schema=DATABASE() AND table_name='users' AND column_name='bank_account_number'), 'SELECT 1', 'ALTER TABLE `users` ADD COLUMN `bank_account_number` VARCHAR(80) NULL');
PREPARE s FROM @sql; EXECUTE s; DEALLOCATE PREPARE s;
SET @sql := IF(EXISTS(SELECT 1 FROM information_schema.columns WHERE table_schema=DATABASE() AND table_name='users' AND column_name='bank_ifsc'), 'SELECT 1', 'ALTER TABLE `users` ADD COLUMN `bank_ifsc` VARCHAR(20) NULL');
PREPARE s FROM @sql; EXECUTE s; DEALLOCATE PREPARE s;
SET @sql := IF(EXISTS(SELECT 1 FROM information_schema.columns WHERE table_schema=DATABASE() AND table_name='users' AND column_name='bank_name'), 'SELECT 1', 'ALTER TABLE `users` ADD COLUMN `bank_name` VARCHAR(190) NULL');
PREPARE s FROM @sql; EXECUTE s; DEALLOCATE PREPARE s;
SET @sql := IF(EXISTS(SELECT 1 FROM information_schema.columns WHERE table_schema=DATABASE() AND table_name='vendors' AND column_name='bank_name'), 'SELECT 1', 'ALTER TABLE `vendors` ADD COLUMN `bank_name` VARCHAR(190) NULL');
PREPARE s FROM @sql; EXECUTE s; DEALLOCATE PREPARE s;

-- Product management (no category seed data).
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


-- Hunter Wallet vendor MFA and OTP password-reset migration
-- Idempotent: safe to re-run on installations at any stage.
SET @db := DATABASE();

SET @sql := (SELECT IF(
  EXISTS(SELECT 1 FROM information_schema.tables WHERE table_schema=@db AND table_name='vendor_users')
  AND NOT EXISTS(SELECT 1 FROM information_schema.columns WHERE table_schema=@db AND table_name='vendor_users' AND column_name='mfa_enabled'),
  'ALTER TABLE vendor_users ADD COLUMN mfa_enabled TINYINT(1) NOT NULL DEFAULT 0',
  'SELECT 1'
)); PREPARE s FROM @sql; EXECUTE s; DEALLOCATE PREPARE s;

SET @sql := (SELECT IF(
  EXISTS(SELECT 1 FROM information_schema.tables WHERE table_schema=@db AND table_name='vendor_users')
  AND NOT EXISTS(SELECT 1 FROM information_schema.columns WHERE table_schema=@db AND table_name='vendor_users' AND column_name='mfa_secret_encrypted'),
  'ALTER TABLE vendor_users ADD COLUMN mfa_secret_encrypted VARCHAR(500) NULL',
  'SELECT 1'
)); PREPARE s FROM @sql; EXECUTE s; DEALLOCATE PREPARE s;

CREATE TABLE IF NOT EXISTS vendor_otp_challenges (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  vendor_user_id BIGINT UNSIGNED NOT NULL,
  email VARCHAR(190) NOT NULL,
  otp_hash CHAR(64) NOT NULL,
  purpose ENUM('LOGIN','PASSWORD_RESET') NOT NULL DEFAULT 'PASSWORD_RESET',
  attempts TINYINT UNSIGNED NOT NULL DEFAULT 0,
  expires_at DATETIME NOT NULL,
  consumed_at DATETIME NULL,
  ip_address VARCHAR(45) NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  INDEX idx_vendor_otp(vendor_user_id,purpose,expires_at),
  FOREIGN KEY(vendor_user_id) REFERENCES vendor_users(id)
) ENGINE=InnoDB;

-- Master wallet and payout ledger. Existing legacy entries are unchanged.
CREATE TABLE IF NOT EXISTS finance_master_account (
 id TINYINT UNSIGNED PRIMARY KEY, balance DECIMAL(18,2) NOT NULL DEFAULT 0,
 updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
 CHECK(balance>=0)
) ENGINE=InnoDB;
INSERT IGNORE INTO finance_master_account(id,balance) VALUES(1,0);
CREATE TABLE IF NOT EXISTS finance_admin_security (
 admin_id INT UNSIGNED PRIMARY KEY, pin_hash VARCHAR(255) NOT NULL,
 failures INT UNSIGNED NOT NULL DEFAULT 0, locked_until DATETIME NULL,
 last_otp_hash CHAR(64) NULL, last_otp_at DATETIME NULL,
 FOREIGN KEY(admin_id) REFERENCES admins(id)
) ENGINE=InnoDB;
CREATE TABLE IF NOT EXISTS finance_master_entries (
 id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY, transaction_id VARCHAR(50) NOT NULL UNIQUE,
 operation ENUM('FUND','DISTRIBUTE','RECALL') NOT NULL,
 target ENUM('MASTER','VENDOR','REAL','BONUS') NOT NULL, recipient_id BIGINT UNSIGNED NULL,
 amount DECIMAL(18,2) NOT NULL, previous_balance DECIMAL(18,2) NOT NULL, new_balance DECIMAL(18,2) NOT NULL,
 recipient_before DECIMAL(18,2) NULL, recipient_after DECIMAL(18,2) NULL,
 reference VARCHAR(120) NOT NULL, funding_reference VARCHAR(120) NULL UNIQUE, reason VARCHAR(500) NOT NULL,
 idempotency_key VARCHAR(100) NOT NULL UNIQUE, admin_id INT UNSIGNED NOT NULL,
 ip_address VARCHAR(45) NOT NULL, user_agent VARCHAR(500) NOT NULL,
 created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
 FOREIGN KEY(admin_id) REFERENCES admins(id), CHECK(amount>0), CHECK(new_balance>=0)
) ENGINE=InnoDB;
CREATE TABLE IF NOT EXISTS finance_payout_records (
 id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
 kind ENUM('CUSTOMER','VENDOR') NOT NULL, request_id BIGINT UNSIGNED NOT NULL,
 reference VARCHAR(120) NOT NULL UNIQUE, note VARCHAR(500) NULL,
 admin_id INT UNSIGNED NOT NULL, created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
 UNIQUE KEY uq_finance_payout(kind,request_id), FOREIGN KEY(admin_id) REFERENCES admins(id)
) ENGINE=InnoDB;
CREATE TABLE IF NOT EXISTS finance_vendor_payout_events (
 id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY, request_id BIGINT UNSIGNED NOT NULL,
 old_status VARCHAR(30) NOT NULL, new_status VARCHAR(30) NOT NULL,
 note VARCHAR(500) NOT NULL, admin_id INT UNSIGNED NOT NULL, ip_address VARCHAR(45) NOT NULL,
 created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
 FOREIGN KEY(request_id) REFERENCES vendor_payout_requests(id), FOREIGN KEY(admin_id) REFERENCES admins(id)
) ENGINE=InnoDB;
CREATE TABLE IF NOT EXISTS finance_vendor_debits (
 request_id BIGINT UNSIGNED PRIMARY KEY, vendor_id BIGINT UNSIGNED NOT NULL,
 amount DECIMAL(18,2) NOT NULL, previous_balance DECIMAL(18,2) NOT NULL, new_balance DECIMAL(18,2) NOT NULL,
 created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
 FOREIGN KEY(request_id) REFERENCES vendor_payout_requests(id), FOREIGN KEY(vendor_id) REFERENCES vendors(id)
) ENGINE=InnoDB;
ALTER TABLE vendor_payout_requests MODIFY status ENUM('PENDING','UNDER_REVIEW','APPROVED','PROCESSING','PAID','REJECTED','HELD') NOT NULL DEFAULT 'PENDING';
INSERT IGNORE INTO admin_permissions(code,description) VALUES
 ('wallet.master.view','View the master wallet and distribution ledger'),
 ('wallet.master.distribute','Distribute and recall master wallet funds using PIN and OTP'),
 ('wallet.master.fund','Record received master wallet funding using PIN and OTP');
INSERT IGNORE INTO admin_role_permissions(role_id,permission_id)
 SELECT r.id,p.id FROM admin_roles r CROSS JOIN admin_permissions p
 WHERE r.name='Super Admin' AND p.code IN ('wallet.master.view','wallet.master.distribute','wallet.master.fund');
DROP TRIGGER IF EXISTS finance_master_no_update;
DROP TRIGGER IF EXISTS finance_master_no_delete;
DROP TRIGGER IF EXISTS finance_payout_no_update;
DROP TRIGGER IF EXISTS finance_payout_no_delete;
DELIMITER $$
CREATE TRIGGER finance_master_no_update BEFORE UPDATE ON finance_master_entries FOR EACH ROW BEGIN SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT='Master ledger is append-only'; END$$
CREATE TRIGGER finance_master_no_delete BEFORE DELETE ON finance_master_entries FOR EACH ROW BEGIN SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT='Master ledger is append-only'; END$$
CREATE TRIGGER finance_payout_no_update BEFORE UPDATE ON finance_payout_records FOR EACH ROW BEGIN SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT='Payout references are append-only'; END$$
CREATE TRIGGER finance_payout_no_delete BEFORE DELETE ON finance_payout_records FOR EACH ROW BEGIN SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT='Payout references are append-only'; END$$
DELIMITER ;

SET SESSION sql_mode = @release_old_sql_mode;
SELECT 'Hunter Wallet 2026-09-13 update completed' AS migration_result;
