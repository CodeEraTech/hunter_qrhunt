-- Hunter Wallet production schema (fresh installation)
--
-- This is the current, self-contained schema for a new live server. It includes
-- the QR batch, staff KYC/OTP, coupon, merchant-settlement, and vendor-KYC
-- additions that were previously supplied in migrate_client_requirements.sql.
--
-- Import with the target database selected in phpMyAdmin, or:
--   mysql -u YOUR_DB_USER -p YOUR_DATABASE < database/hunter_wallet.sql
--
-- This file does not create/drop a database because shared-hosting users normally
-- do not have that privilege. It resets tables inside the selected database.

SET FOREIGN_KEY_CHECKS=0;
DROP TABLE IF EXISTS merchant_settlements,coupons,admin_otp_challenges,staff_profiles,qr_batch_items,qr_batches,wallet_expiry_jobs,wallet_checkout_transactions,user_payment_methods,wallet_bonus_rules,qr_promo_grants,qr_campaigns,video_ads,video_categories,gift_redemption_attempts,gift_redemptions,reward_codes,gifts,vendor_login_attempts,vendor_sessions,vendor_users,vendors,hunt_completions,reward_approvals,rewards,hunt_scratch_sessions,hunt_challenge_attempts,hunt_scan_attempts,hunt_scans,hunt_question_options,hunt_questions,hunt_secret_codes,hunt_videos,hunt_qr_codes,hunt_user_activations,payment_webhook_events,payment_transactions,hunt_locations,hunts,system_settings,notification_logs,notifications,transaction_limits,audit_logs,security_events,withdrawal_attempts,login_attempts,user_sessions,user_devices,withdrawals,wallet_transactions,wallets,users,api_request_logs,api_idempotency,admin_role_permissions,admin_permissions,admins,admin_roles;
SET FOREIGN_KEY_CHECKS=1;

CREATE TABLE admin_roles (id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY, name VARCHAR(80) NOT NULL UNIQUE, created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP) ENGINE=InnoDB;
CREATE TABLE admin_permissions (id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY, code VARCHAR(100) NOT NULL UNIQUE, description VARCHAR(255) NULL) ENGINE=InnoDB;
CREATE TABLE admin_role_permissions (role_id INT UNSIGNED NOT NULL, permission_id INT UNSIGNED NOT NULL, PRIMARY KEY(role_id,permission_id), FOREIGN KEY(role_id) REFERENCES admin_roles(id), FOREIGN KEY(permission_id) REFERENCES admin_permissions(id)) ENGINE=InnoDB;
CREATE TABLE admins (
 id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY, role_id INT UNSIGNED NOT NULL, name VARCHAR(120) NOT NULL, username VARCHAR(80) NOT NULL UNIQUE, email VARCHAR(190) NOT NULL UNIQUE,
 password_hash VARCHAR(255) NOT NULL, status ENUM('ACTIVE','BLOCKED') NOT NULL DEFAULT 'ACTIVE', mfa_enabled TINYINT(1) NOT NULL DEFAULT 0, mfa_secret_encrypted TEXT NULL, last_login_at DATETIME NULL, last_login_ip VARCHAR(45) NULL,
 created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP, updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP, FOREIGN KEY(role_id) REFERENCES admin_roles(id)
) ENGINE=InnoDB;
CREATE TABLE users (
 id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY, name VARCHAR(120) NOT NULL, mobile VARCHAR(30) NOT NULL UNIQUE, email VARCHAR(190) NULL UNIQUE, password_hash VARCHAR(255) NOT NULL, profile_image VARCHAR(255) NULL,
 status ENUM('ACTIVE','BLOCKED','SUSPENDED','PENDING') NOT NULL DEFAULT 'PENDING', status_reason VARCHAR(500) NULL, is_verified TINYINT(1) NOT NULL DEFAULT 0, balance_privacy_enabled TINYINT(1) NOT NULL DEFAULT 0, risk_level ENUM('LOW','MEDIUM','HIGH','CRITICAL') NOT NULL DEFAULT 'LOW',
 created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP, updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP, last_login_at DATETIME NULL, last_login_ip VARCHAR(45) NULL,
 INDEX idx_users_status(status), INDEX idx_users_created(created_at)
) ENGINE=InnoDB;
CREATE TABLE wallets (
 id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY, user_id BIGINT UNSIGNED NOT NULL UNIQUE, balance DECIMAL(18,2) NOT NULL DEFAULT 0.00, bonus_balance DECIMAL(18,2) NOT NULL DEFAULT 0.00, promo_balance DECIMAL(18,2) NOT NULL DEFAULT 0.00, bonus_expires_at DATETIME NULL, promo_expires_at DATETIME NULL, currency CHAR(3) NOT NULL DEFAULT 'INR', status ENUM('ACTIVE','FROZEN','CLOSED') NOT NULL DEFAULT 'ACTIVE',
 created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP, updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP, CHECK(balance >= 0), CHECK(bonus_balance >= 0), CHECK(promo_balance >= 0), FOREIGN KEY(user_id) REFERENCES users(id)
) ENGINE=InnoDB;
CREATE TABLE wallet_transactions (
 id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY, transaction_id VARCHAR(40) NOT NULL UNIQUE, user_id BIGINT UNSIGNED NOT NULL, wallet_id BIGINT UNSIGNED NOT NULL,
 type ENUM('CREDIT','DEBIT','WITHDRAWAL','REFUND','ADJUSTMENT','REVERSAL','BONUS_CREDIT','PROMO_CREDIT','PROMO_DEBIT','BONUS_DEBIT','CHECKOUT','PROMO_REVERSAL') NOT NULL, bucket ENUM('REAL','BONUS','PROMO') NOT NULL DEFAULT 'REAL', amount DECIMAL(18,2) NOT NULL, previous_balance DECIMAL(18,2) NOT NULL, new_balance DECIMAL(18,2) NOT NULL,
 status ENUM('PENDING','PROCESSING','SUCCESS','FAILED','REVERSED') NOT NULL, reference VARCHAR(120) NULL, description VARCHAR(500) NULL, idempotency_key VARCHAR(100) NULL, original_transaction_id BIGINT UNSIGNED NULL, created_by INT UNSIGNED NULL, created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
 CHECK(amount > 0), UNIQUE KEY uq_wallet_idempotency(idempotency_key), UNIQUE KEY uq_reversal_original(original_transaction_id), INDEX idx_txn_user_date(user_id,created_at), INDEX idx_txn_status(status), FOREIGN KEY(user_id) REFERENCES users(id), FOREIGN KEY(wallet_id) REFERENCES wallets(id), FOREIGN KEY(original_transaction_id) REFERENCES wallet_transactions(id), FOREIGN KEY(created_by) REFERENCES admins(id)
) ENGINE=InnoDB;
CREATE TABLE withdrawals (
 id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY, withdrawal_id VARCHAR(40) NOT NULL UNIQUE, user_id BIGINT UNSIGNED NOT NULL, wallet_id BIGINT UNSIGNED NOT NULL, amount DECIMAL(18,2) NOT NULL,
 payment_method VARCHAR(50) NOT NULL, payment_account_masked VARCHAR(100) NULL, payment_reference VARCHAR(120) NULL,
 status ENUM('PENDING','UNDER_REVIEW','APPROVED','PROCESSING','PAID','REJECTED','CANCELLED') NOT NULL DEFAULT 'PENDING', risk_level ENUM('LOW','MEDIUM','HIGH','CRITICAL') NOT NULL DEFAULT 'LOW',
 requested_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP, reviewed_at DATETIME NULL, processed_at DATETIME NULL, reviewed_by INT UNSIGNED NULL, rejection_reason VARCHAR(500) NULL,
 created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP, updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
 CHECK(amount > 0), INDEX idx_wd_status_date(status,requested_at), FOREIGN KEY(user_id) REFERENCES users(id), FOREIGN KEY(wallet_id) REFERENCES wallets(id), FOREIGN KEY(reviewed_by) REFERENCES admins(id)
) ENGINE=InnoDB;
CREATE TABLE user_devices (
 id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY, user_id BIGINT UNSIGNED NOT NULL, device_id VARCHAR(190) NOT NULL, device_name VARCHAR(120) NULL, platform VARCHAR(30) NULL, os_version VARCHAR(40) NULL, app_version VARCHAR(40) NULL,
 ip_address VARCHAR(45) NULL, status ENUM('ACTIVE','BLOCKED','REPLACEMENT_PENDING') NOT NULL DEFAULT 'ACTIVE', last_active_at DATETIME NULL, created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP, updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
 UNIQUE(user_id,device_id), INDEX idx_device_status(status), FOREIGN KEY(user_id) REFERENCES users(id)
) ENGINE=InnoDB;
CREATE TABLE user_sessions (
 id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY, user_id BIGINT UNSIGNED NOT NULL, token_hash CHAR(64) NOT NULL UNIQUE, device_id VARCHAR(190) NOT NULL, ip_address VARCHAR(45) NULL, user_agent VARCHAR(500) NULL,
 expires_at DATETIME NOT NULL, revoked_at DATETIME NULL, created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP, INDEX idx_session_user(user_id), FOREIGN KEY(user_id) REFERENCES users(id)
) ENGINE=InnoDB;
CREATE TABLE login_attempts (id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY, identity_hash CHAR(64) NOT NULL, ip_address VARCHAR(45) NOT NULL, successful TINYINT(1) NOT NULL DEFAULT 0, attempted_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP, INDEX idx_login_ip_date(ip_address,attempted_at)) ENGINE=InnoDB;
CREATE TABLE api_idempotency (id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY, user_id BIGINT UNSIGNED NOT NULL, idempotency_key VARCHAR(100) NOT NULL, endpoint VARCHAR(120) NOT NULL, response_code SMALLINT UNSIGNED NOT NULL, response_body JSON NOT NULL, created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP, UNIQUE(user_id,idempotency_key,endpoint), FOREIGN KEY(user_id) REFERENCES users(id)) ENGINE=InnoDB;
CREATE TABLE api_request_logs (id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY, user_id BIGINT UNSIGNED NULL, endpoint VARCHAR(120) NOT NULL, method VARCHAR(10) NOT NULL, ip_address VARCHAR(45) NULL, device_id VARCHAR(190) NULL, created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP, INDEX idx_api_rate(user_id,endpoint,created_at), FOREIGN KEY(user_id) REFERENCES users(id)) ENGINE=InnoDB;
CREATE TABLE withdrawal_attempts (id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY, withdrawal_id BIGINT UNSIGNED NULL, user_id BIGINT UNSIGNED NOT NULL, idempotency_key VARCHAR(100) NULL, amount DECIMAL(18,2) NULL, ip_address VARCHAR(45) NULL, device_id VARCHAR(190) NULL, outcome VARCHAR(50) NOT NULL, reason VARCHAR(500) NULL, created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP, INDEX idx_wda_user_date(user_id,created_at), FOREIGN KEY(withdrawal_id) REFERENCES withdrawals(id), FOREIGN KEY(user_id) REFERENCES users(id)) ENGINE=InnoDB;
CREATE TABLE security_events (
 id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY, user_id BIGINT UNSIGNED NULL, event_type VARCHAR(80) NOT NULL, risk_level ENUM('LOW','MEDIUM','HIGH','CRITICAL') NOT NULL, ip_address VARCHAR(45) NULL, device_id VARCHAR(190) NULL,
 user_agent VARCHAR(500) NULL, description VARCHAR(500) NOT NULL, metadata JSON NULL, created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP, INDEX idx_security_risk_date(risk_level,created_at), FOREIGN KEY(user_id) REFERENCES users(id)
) ENGINE=InnoDB;
CREATE TABLE audit_logs (
 id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY, admin_id INT UNSIGNED NULL, action VARCHAR(100) NOT NULL, module VARCHAR(80) NOT NULL, record_id VARCHAR(80) NULL, old_data JSON NULL, new_data JSON NULL,
 ip_address VARCHAR(45) NULL, user_agent VARCHAR(500) NULL, created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP, INDEX idx_audit_date(created_at), FOREIGN KEY(admin_id) REFERENCES admins(id)
) ENGINE=InnoDB;
CREATE TABLE transaction_limits (id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY, user_id BIGINT UNSIGNED NULL, min_withdrawal DECIMAL(18,2) NOT NULL DEFAULT 100, max_withdrawal DECIMAL(18,2) NOT NULL DEFAULT 50000, daily_withdrawal DECIMAL(18,2) NOT NULL DEFAULT 100000, monthly_withdrawal DECIMAL(18,2) NULL, updated_by INT UNSIGNED NULL, updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP, UNIQUE KEY uq_user_limits(user_id), FOREIGN KEY(user_id) REFERENCES users(id), FOREIGN KEY(updated_by) REFERENCES admins(id)) ENGINE=InnoDB;
CREATE TABLE notifications (id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY, user_id BIGINT UNSIGNED NULL, type VARCHAR(80) NOT NULL, channel ENUM('PUSH','SMS','EMAIL','IN_APP') NOT NULL, title VARCHAR(190) NOT NULL, message TEXT NOT NULL, status ENUM('QUEUED','SENT','FAILED','READ') NOT NULL DEFAULT 'QUEUED', created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP, sent_at DATETIME NULL, FOREIGN KEY(user_id) REFERENCES users(id)) ENGINE=InnoDB;
CREATE TABLE notification_logs (id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY, notification_id BIGINT UNSIGNED NOT NULL, provider VARCHAR(80) NULL, provider_reference VARCHAR(190) NULL, status VARCHAR(40) NOT NULL, response_summary VARCHAR(500) NULL, created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP, INDEX idx_notification_log(notification_id,created_at), FOREIGN KEY(notification_id) REFERENCES notifications(id)) ENGINE=InnoDB;
CREATE TABLE system_settings (`key` VARCHAR(100) PRIMARY KEY, `value` TEXT NOT NULL, is_secret TINYINT(1) NOT NULL DEFAULT 0, updated_by INT UNSIGNED NULL, updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP, FOREIGN KEY(updated_by) REFERENCES admins(id)) ENGINE=InnoDB;

CREATE TABLE hunts (
 id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY, title VARCHAR(190) NOT NULL, slug VARCHAR(190) NOT NULL UNIQUE, description TEXT NULL, rules TEXT NULL, featured_image VARCHAR(255) NULL,
 start_at DATETIME NOT NULL, end_at DATETIME NOT NULL, activation_fee DECIMAL(18,2) NOT NULL DEFAULT 0.00, reward_min DECIMAL(18,2) NOT NULL, reward_max DECIMAL(18,2) NOT NULL,
 status ENUM('DRAFT','ACTIVE','INACTIVE','ENDED') NOT NULL DEFAULT 'DRAFT', created_by INT UNSIGNED NOT NULL, created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP, updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
 CHECK(end_at>start_at), CHECK(activation_fee>=0), CHECK(reward_min>0), CHECK(reward_max>=reward_min), INDEX idx_hunts_status_dates(status,start_at,end_at), FOREIGN KEY(created_by) REFERENCES admins(id)
) ENGINE=InnoDB;
CREATE TABLE hunt_locations (
 id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY, hunt_id BIGINT UNSIGNED NOT NULL, name VARCHAR(190) NOT NULL, address VARCHAR(500) NULL, latitude DECIMAL(10,7) NOT NULL, longitude DECIMAL(10,7) NOT NULL, radius_meters INT UNSIGNED NOT NULL DEFAULT 100, sort_order INT UNSIGNED NOT NULL DEFAULT 0,
 status ENUM('ACTIVE','INACTIVE') NOT NULL DEFAULT 'ACTIVE', created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP, updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
 CHECK(latitude BETWEEN -90 AND 90), CHECK(longitude BETWEEN -180 AND 180), CHECK(radius_meters BETWEEN 1 AND 100000), UNIQUE KEY uq_hunt_location_name(hunt_id,name), INDEX idx_location_hunt_order(hunt_id,sort_order), FOREIGN KEY(hunt_id) REFERENCES hunts(id)
) ENGINE=InnoDB;
CREATE TABLE payment_transactions (
 id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY, payment_id VARCHAR(50) NOT NULL UNIQUE, user_id BIGINT UNSIGNED NOT NULL, hunt_id BIGINT UNSIGNED NOT NULL, provider ENUM('RAZORPAY','PHONEPE','COUNTER') NOT NULL, provider_order_id VARCHAR(190) NULL, provider_payment_id VARCHAR(190) NULL,
 amount DECIMAL(18,2) NOT NULL, currency CHAR(3) NOT NULL DEFAULT 'INR', status ENUM('CREATED','PENDING','VERIFIED','FAILED','REFUNDED') NOT NULL DEFAULT 'CREATED', idempotency_key VARCHAR(100) NOT NULL, signature_verified_at DATETIME NULL, failure_reason VARCHAR(500) NULL,
 created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP, updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP, CHECK(amount>=0), UNIQUE KEY uq_payment_user_key(user_id,idempotency_key), UNIQUE KEY uq_provider_payment(provider,provider_payment_id), INDEX idx_payment_hunt_status(hunt_id,status), FOREIGN KEY(user_id) REFERENCES users(id), FOREIGN KEY(hunt_id) REFERENCES hunts(id)
) ENGINE=InnoDB;
CREATE TABLE payment_webhook_events (
 id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY, provider ENUM('RAZORPAY','PHONEPE') NOT NULL, event_id VARCHAR(190) NOT NULL, signature_hash CHAR(64) NOT NULL, payload_hash CHAR(64) NOT NULL, payment_transaction_id BIGINT UNSIGNED NULL, status ENUM('ACCEPTED','IGNORED','FAILED') NOT NULL, received_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
 UNIQUE KEY uq_payment_event(provider,event_id), FOREIGN KEY(payment_transaction_id) REFERENCES payment_transactions(id)
) ENGINE=InnoDB;
CREATE TABLE hunt_user_activations (
 id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY, user_id BIGINT UNSIGNED NOT NULL, hunt_id BIGINT UNSIGNED NOT NULL, activation_type ENUM('ONLINE','COUNTER','ADMIN') NOT NULL, payment_transaction_id BIGINT UNSIGNED NULL, activated_by INT UNSIGNED NULL,
 status ENUM('ACTIVE','REVOKED','COMPLETED') NOT NULL DEFAULT 'ACTIVE', activated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP, expires_at DATETIME NULL, revoked_at DATETIME NULL,
 UNIQUE KEY uq_user_hunt_activation(user_id,hunt_id), INDEX idx_activation_hunt_status(hunt_id,status), FOREIGN KEY(user_id) REFERENCES users(id), FOREIGN KEY(hunt_id) REFERENCES hunts(id), FOREIGN KEY(payment_transaction_id) REFERENCES payment_transactions(id), FOREIGN KEY(activated_by) REFERENCES admins(id)
) ENGINE=InnoDB;
CREATE TABLE hunt_qr_codes (
 id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY, hunt_id BIGINT UNSIGNED NOT NULL, location_id BIGINT UNSIGNED NOT NULL, token_hash CHAR(64) NOT NULL UNIQUE, label VARCHAR(190) NULL, status ENUM('ACTIVE','INACTIVE','EXPIRED') NOT NULL DEFAULT 'ACTIVE', expires_at DATETIME NULL, max_scans INT UNSIGNED NULL, scan_count INT UNSIGNED NOT NULL DEFAULT 0,
 created_by INT UNSIGNED NOT NULL, created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP, updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP, INDEX idx_qr_hunt_location(hunt_id,location_id,status), FOREIGN KEY(hunt_id) REFERENCES hunts(id), FOREIGN KEY(location_id) REFERENCES hunt_locations(id), FOREIGN KEY(created_by) REFERENCES admins(id)
) ENGINE=InnoDB;
CREATE TABLE hunt_videos (
 id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY, hunt_id BIGINT UNSIGNED NOT NULL, location_id BIGINT UNSIGNED NULL, category_id BIGINT UNSIGNED NULL, title VARCHAR(190) NOT NULL, video_url VARCHAR(500) NOT NULL, clue_text TEXT NULL, sort_order INT UNSIGNED NOT NULL DEFAULT 0, status ENUM('ACTIVE','INACTIVE') NOT NULL DEFAULT 'ACTIVE', created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP, updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
 INDEX idx_video_hunt_order(hunt_id,sort_order), FOREIGN KEY(hunt_id) REFERENCES hunts(id), FOREIGN KEY(location_id) REFERENCES hunt_locations(id)
) ENGINE=InnoDB;
CREATE TABLE hunt_secret_codes (
 id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY, hunt_id BIGINT UNSIGNED NOT NULL, location_id BIGINT UNSIGNED NULL, code_hash VARCHAR(255) NOT NULL, valid_from DATETIME NOT NULL, valid_until DATETIME NOT NULL, status ENUM('ACTIVE','INACTIVE') NOT NULL DEFAULT 'ACTIVE', created_by INT UNSIGNED NOT NULL, created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
 CHECK(valid_until>valid_from), INDEX idx_secret_window(hunt_id,location_id,status,valid_from,valid_until), FOREIGN KEY(hunt_id) REFERENCES hunts(id), FOREIGN KEY(location_id) REFERENCES hunt_locations(id), FOREIGN KEY(created_by) REFERENCES admins(id)
) ENGINE=InnoDB;
CREATE TABLE hunt_questions (
 id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY, hunt_id BIGINT UNSIGNED NOT NULL, location_id BIGINT UNSIGNED NULL, question_text TEXT NOT NULL, question_type ENUM('TEXT','MCQ') NOT NULL, correct_answer_hash CHAR(64) NOT NULL, sort_order INT UNSIGNED NOT NULL DEFAULT 0, status ENUM('ACTIVE','INACTIVE') NOT NULL DEFAULT 'ACTIVE', created_by INT UNSIGNED NOT NULL, created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP, updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
 INDEX idx_question_hunt_order(hunt_id,location_id,sort_order), FOREIGN KEY(hunt_id) REFERENCES hunts(id), FOREIGN KEY(location_id) REFERENCES hunt_locations(id), FOREIGN KEY(created_by) REFERENCES admins(id)
) ENGINE=InnoDB;
CREATE TABLE hunt_question_options (
 id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY, question_id BIGINT UNSIGNED NOT NULL, option_text VARCHAR(500) NOT NULL, sort_order INT UNSIGNED NOT NULL DEFAULT 0, created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP, INDEX idx_option_question_order(question_id,sort_order), FOREIGN KEY(question_id) REFERENCES hunt_questions(id) ON DELETE CASCADE
) ENGINE=InnoDB;
CREATE TABLE hunt_scans (
 id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY, user_id BIGINT UNSIGNED NOT NULL, hunt_id BIGINT UNSIGNED NOT NULL, location_id BIGINT UNSIGNED NOT NULL, qr_code_id BIGINT UNSIGNED NOT NULL, activation_id BIGINT UNSIGNED NOT NULL, device_id VARCHAR(190) NOT NULL,
 latitude DECIMAL(10,7) NOT NULL, longitude DECIMAL(10,7) NOT NULL, gps_accuracy_meters DECIMAL(10,2) NULL, distance_meters DECIMAL(12,2) NOT NULL, allowed_radius_meters INT UNSIGNED NOT NULL, ip_address VARCHAR(45) NULL,
 result ENUM('VALIDATED','REJECTED','COMPLETED') NOT NULL, reason VARCHAR(500) NULL, risk_level ENUM('LOW','MEDIUM','HIGH','CRITICAL') NOT NULL DEFAULT 'LOW', scanned_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
 UNIQUE KEY uq_user_qr_claim(user_id,qr_code_id), INDEX idx_scan_hunt_date(hunt_id,scanned_at), INDEX idx_scan_result_risk(result,risk_level), FOREIGN KEY(user_id) REFERENCES users(id), FOREIGN KEY(hunt_id) REFERENCES hunts(id), FOREIGN KEY(location_id) REFERENCES hunt_locations(id), FOREIGN KEY(qr_code_id) REFERENCES hunt_qr_codes(id), FOREIGN KEY(activation_id) REFERENCES hunt_user_activations(id)
) ENGINE=InnoDB;
CREATE TABLE hunt_scan_attempts (
 id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY, user_id BIGINT UNSIGNED NULL, hunt_id BIGINT UNSIGNED NULL, location_id BIGINT UNSIGNED NULL, qr_code_id BIGINT UNSIGNED NULL, device_id VARCHAR(190) NULL, ip_address VARCHAR(45) NULL, latitude DECIMAL(10,7) NULL, longitude DECIMAL(10,7) NULL, distance_meters DECIMAL(12,2) NULL,
 result VARCHAR(50) NOT NULL, reason VARCHAR(500) NULL, risk_level ENUM('LOW','MEDIUM','HIGH','CRITICAL') NOT NULL, metadata JSON NULL, attempted_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP, INDEX idx_scan_attempt_user_date(user_id,attempted_at), INDEX idx_scan_attempt_risk(risk_level,attempted_at), FOREIGN KEY(user_id) REFERENCES users(id), FOREIGN KEY(hunt_id) REFERENCES hunts(id), FOREIGN KEY(location_id) REFERENCES hunt_locations(id), FOREIGN KEY(qr_code_id) REFERENCES hunt_qr_codes(id)
) ENGINE=InnoDB;
CREATE TABLE hunt_challenge_attempts (
 id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY, scan_id BIGINT UNSIGNED NOT NULL, user_id BIGINT UNSIGNED NOT NULL, challenge_type ENUM('SECRET','QUESTION') NOT NULL, challenge_id BIGINT UNSIGNED NOT NULL, successful TINYINT(1) NOT NULL, answer_hash CHAR(64) NOT NULL, ip_address VARCHAR(45) NULL, device_id VARCHAR(190) NULL, attempted_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
 INDEX idx_challenge_rate(user_id,challenge_type,attempted_at), INDEX idx_challenge_success(scan_id,challenge_type,challenge_id,successful), FOREIGN KEY(scan_id) REFERENCES hunt_scans(id), FOREIGN KEY(user_id) REFERENCES users(id)
) ENGINE=InnoDB;
CREATE TABLE hunt_scratch_sessions (
 id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY, session_id VARCHAR(50) NOT NULL UNIQUE, scan_id BIGINT UNSIGNED NOT NULL UNIQUE, user_id BIGINT UNSIGNED NOT NULL, token_hash CHAR(64) NOT NULL UNIQUE, status ENUM('CREATED','REVEALED','EXPIRED') NOT NULL DEFAULT 'CREATED', expires_at DATETIME NOT NULL, created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP, revealed_at DATETIME NULL,
 FOREIGN KEY(scan_id) REFERENCES hunt_scans(id), FOREIGN KEY(user_id) REFERENCES users(id)
) ENGINE=InnoDB;
CREATE TABLE rewards (
 id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY, reward_id VARCHAR(50) NOT NULL UNIQUE, user_id BIGINT UNSIGNED NOT NULL, hunt_id BIGINT UNSIGNED NOT NULL, scan_id BIGINT UNSIGNED NOT NULL UNIQUE, scratch_session_id BIGINT UNSIGNED NOT NULL UNIQUE, amount DECIMAL(18,2) NOT NULL,
 status ENUM('PENDING','APPROVED','REJECTED') NOT NULL DEFAULT 'PENDING', rejection_reason VARCHAR(500) NULL, wallet_transaction_id BIGINT UNSIGNED NULL UNIQUE, created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP, decided_at DATETIME NULL, CHECK(amount>0), INDEX idx_reward_status_date(status,created_at), FOREIGN KEY(user_id) REFERENCES users(id), FOREIGN KEY(hunt_id) REFERENCES hunts(id), FOREIGN KEY(scan_id) REFERENCES hunt_scans(id), FOREIGN KEY(scratch_session_id) REFERENCES hunt_scratch_sessions(id), FOREIGN KEY(wallet_transaction_id) REFERENCES wallet_transactions(id)
) ENGINE=InnoDB;
CREATE TABLE reward_approvals (
 id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY, reward_id BIGINT UNSIGNED NOT NULL, admin_id INT UNSIGNED NOT NULL, action ENUM('APPROVED','REJECTED') NOT NULL, reason VARCHAR(500) NULL, created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP, UNIQUE KEY uq_reward_decision(reward_id), FOREIGN KEY(reward_id) REFERENCES rewards(id), FOREIGN KEY(admin_id) REFERENCES admins(id)
) ENGINE=InnoDB;
CREATE TABLE hunt_completions (
 id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY, user_id BIGINT UNSIGNED NOT NULL, hunt_id BIGINT UNSIGNED NOT NULL, activation_id BIGINT UNSIGNED NOT NULL, reward_id BIGINT UNSIGNED NULL, completed_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP, UNIQUE KEY uq_hunt_completion(user_id,hunt_id), FOREIGN KEY(user_id) REFERENCES users(id), FOREIGN KEY(hunt_id) REFERENCES hunts(id), FOREIGN KEY(activation_id) REFERENCES hunt_user_activations(id), FOREIGN KEY(reward_id) REFERENCES rewards(id)
) ENGINE=InnoDB;
CREATE TABLE vendors (
 id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY, vendor_code VARCHAR(40) NOT NULL UNIQUE, name VARCHAR(190) NOT NULL, shop_name VARCHAR(190) NOT NULL, email VARCHAR(190) NULL, mobile VARCHAR(30) NULL, address VARCHAR(500) NULL, logo_path VARCHAR(255) NULL, cover_image_path VARCHAR(255) NULL, kyc_status ENUM('PENDING','APPROVED','REJECTED') NOT NULL DEFAULT 'PENDING', kyc_document_path VARCHAR(255) NULL, status ENUM('ACTIVE','BLOCKED','INACTIVE') NOT NULL DEFAULT 'ACTIVE', created_by INT UNSIGNED NOT NULL, created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP, updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP, INDEX idx_vendor_status(status), FOREIGN KEY(created_by) REFERENCES admins(id)
) ENGINE=InnoDB;
CREATE TABLE vendor_users (
 id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY, vendor_id BIGINT UNSIGNED NOT NULL, name VARCHAR(120) NOT NULL, username VARCHAR(80) NOT NULL UNIQUE, email VARCHAR(190) NULL UNIQUE, password_hash VARCHAR(255) NOT NULL, status ENUM('ACTIVE','BLOCKED') NOT NULL DEFAULT 'ACTIVE', last_login_at DATETIME NULL, last_login_ip VARCHAR(45) NULL, created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP, FOREIGN KEY(vendor_id) REFERENCES vendors(id)
) ENGINE=InnoDB;
CREATE TABLE vendor_sessions (
 id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY, vendor_user_id BIGINT UNSIGNED NOT NULL, token_hash CHAR(64) NULL UNIQUE, php_session_hash CHAR(64) NULL UNIQUE, ip_address VARCHAR(45) NULL, expires_at DATETIME NOT NULL, revoked_at DATETIME NULL, created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP, FOREIGN KEY(vendor_user_id) REFERENCES vendor_users(id)
) ENGINE=InnoDB;
CREATE TABLE vendor_login_attempts (id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY, identity_hash CHAR(64) NOT NULL, ip_address VARCHAR(45) NOT NULL, successful TINYINT(1) NOT NULL DEFAULT 0, attempted_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP, INDEX idx_vendor_login_rate(identity_hash,ip_address,attempted_at)) ENGINE=InnoDB;
CREATE TABLE gifts (
 id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY, name VARCHAR(190) NOT NULL, description TEXT NULL, image_path VARCHAR(255) NULL, campaign VARCHAR(190) NULL, total_quantity INT UNSIGNED NOT NULL, redeemed_quantity INT UNSIGNED NOT NULL DEFAULT 0, valid_from DATETIME NULL, valid_until DATETIME NULL, status ENUM('DRAFT','ACTIVE','INACTIVE','EXPIRED') NOT NULL DEFAULT 'DRAFT', created_by INT UNSIGNED NOT NULL, created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP, updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP, CHECK(redeemed_quantity<=total_quantity), INDEX idx_gift_status_dates(status,valid_from,valid_until), FOREIGN KEY(created_by) REFERENCES admins(id)
) ENGINE=InnoDB;
CREATE TABLE reward_codes (
 id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY, code_reference VARCHAR(50) NOT NULL UNIQUE, code_hash CHAR(64) NOT NULL UNIQUE, user_id BIGINT UNSIGNED NOT NULL, gift_id BIGINT UNSIGNED NOT NULL, status ENUM('ACTIVE','REDEEMED','EXPIRED','CANCELLED') NOT NULL DEFAULT 'ACTIVE', expires_at DATETIME NULL, created_by INT UNSIGNED NOT NULL, created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP, redeemed_at DATETIME NULL,
 INDEX idx_reward_code_user(user_id,status), FOREIGN KEY(user_id) REFERENCES users(id), FOREIGN KEY(gift_id) REFERENCES gifts(id), FOREIGN KEY(created_by) REFERENCES admins(id)
) ENGINE=InnoDB;
CREATE TABLE gift_redemptions (
 id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY, redemption_id VARCHAR(50) NOT NULL UNIQUE, reward_code_id BIGINT UNSIGNED NOT NULL UNIQUE, gift_id BIGINT UNSIGNED NOT NULL, user_id BIGINT UNSIGNED NOT NULL, vendor_id BIGINT UNSIGNED NOT NULL, vendor_user_id BIGINT UNSIGNED NOT NULL, redeemed_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
 INDEX idx_redemption_vendor_date(vendor_id,redeemed_at), FOREIGN KEY(reward_code_id) REFERENCES reward_codes(id), FOREIGN KEY(gift_id) REFERENCES gifts(id), FOREIGN KEY(user_id) REFERENCES users(id), FOREIGN KEY(vendor_id) REFERENCES vendors(id), FOREIGN KEY(vendor_user_id) REFERENCES vendor_users(id)
) ENGINE=InnoDB;
CREATE TABLE gift_redemption_attempts (
 id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY, vendor_id BIGINT UNSIGNED NULL, vendor_user_id BIGINT UNSIGNED NULL, code_reference VARCHAR(50) NULL, code_hash CHAR(64) NOT NULL, ip_address VARCHAR(45) NULL, result VARCHAR(50) NOT NULL, reason VARCHAR(500) NULL, attempted_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP, INDEX idx_redemption_attempt_date(vendor_id,attempted_at), FOREIGN KEY(vendor_id) REFERENCES vendors(id), FOREIGN KEY(vendor_user_id) REFERENCES vendor_users(id)
) ENGINE=InnoDB;

CREATE TABLE video_categories (
 id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY, name VARCHAR(190) NOT NULL, slug VARCHAR(190) NOT NULL UNIQUE, character_name VARCHAR(190) NULL, character_asset_url VARCHAR(500) NULL, channel_url VARCHAR(500) NULL, teaser_video_id VARCHAR(80) NULL, broadcast_message VARCHAR(500) NULL, overlay_asset_url VARCHAR(500) NULL, capacity_limit INT UNSIGNED NOT NULL DEFAULT 500, status ENUM('ACTIVE','INACTIVE') NOT NULL DEFAULT 'ACTIVE', created_by INT UNSIGNED NOT NULL, created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP, updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP, FOREIGN KEY(created_by) REFERENCES admins(id), INDEX idx_video_category_status(status)
) ENGINE=InnoDB;
CREATE TABLE category_broadcasts (id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY, category_id BIGINT UNSIGNED NOT NULL, topic VARCHAR(120) NOT NULL, message VARCHAR(500) NOT NULL, status ENUM('QUEUED','SENT','FAILED') NOT NULL DEFAULT 'QUEUED', provider_reference VARCHAR(190) NULL, created_by INT UNSIGNED NOT NULL, created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP, sent_at DATETIME NULL, FOREIGN KEY(category_id) REFERENCES video_categories(id), FOREIGN KEY(created_by) REFERENCES admins(id), INDEX idx_category_broadcast(category_id,status,created_at)) ENGINE=InnoDB;
CREATE TABLE spot_campaigns (spot_id VARCHAR(64) PRIMARY KEY, master_token_hash CHAR(64) NOT NULL UNIQUE, spot_name VARCHAR(255) NOT NULL, category_id BIGINT UNSIGNED NULL, target_scope ENUM('ALL_PUBLIC','WHITELIST','NEW_USER') NOT NULL DEFAULT 'ALL_PUBLIC', whitelisted_users JSON NULL, city_zone VARCHAR(128) NULL, venue_name VARCHAR(255) NULL, gps_mode ENUM('GPS_REQUIRED','DESK_STUDY') NOT NULL DEFAULT 'GPS_REQUIRED', latitude DECIMAL(10,7) NOT NULL DEFAULT 0, longitude DECIMAL(10,7) NOT NULL DEFAULT 0, proximity_alert_radius INT UNSIGNED NOT NULL DEFAULT 30, scanner_unlock_radius INT UNSIGNED NOT NULL DEFAULT 20, indoor_micro_clue TEXT NULL, reward_type ENUM('PROMO_CASH','COUPON_VOUCHER','DIRECT_CASH','MILESTONE') NOT NULL DEFAULT 'PROMO_CASH', reward_amount DECIMAL(18,2) NOT NULL DEFAULT 15, min_cart_value DECIMAL(18,2) NOT NULL DEFAULT 300, ttl_minutes INT UNSIGNED NOT NULL DEFAULT 30, auto_reversal TINYINT(1) NOT NULL DEFAULT 1, max_budget_scans INT UNSIGNED NOT NULL DEFAULT 500, scan_frequency_policy ENUM('PER_DEVICE','SINGLE_USE','DAILY','POOL') NOT NULL DEFAULT 'PER_DEVICE', is_active TINYINT(1) NOT NULL DEFAULT 1, created_by INT UNSIGNED NOT NULL, created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP, updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP, FOREIGN KEY(category_id) REFERENCES video_categories(id), FOREIGN KEY(created_by) REFERENCES admins(id), CHECK(latitude BETWEEN -90 AND 90), CHECK(longitude BETWEEN -180 AND 180), CHECK(scanner_unlock_radius<=proximity_alert_radius), INDEX idx_spot_active(is_active,category_id)) ENGINE=InnoDB;
CREATE TABLE riddle_pool (riddle_id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY, spot_id VARCHAR(64) NOT NULL, riddle_text TEXT NOT NULL, primary_answer VARCHAR(128) NOT NULL, synonyms JSON NOT NULL, yt_video_id VARCHAR(32) NULL, weight_percentage INT UNSIGNED NOT NULL DEFAULT 100, created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP, FOREIGN KEY(spot_id) REFERENCES spot_campaigns(spot_id) ON DELETE CASCADE, INDEX idx_riddle_spot(spot_id,weight_percentage)) ENGINE=InnoDB;
CREATE TABLE scan_audit_ledger (audit_id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY, device_hash VARCHAR(128) NOT NULL, user_id BIGINT UNSIGNED NOT NULL, spot_id VARCHAR(64) NOT NULL, riddle_id BIGINT UNSIGNED NULL, user_input_answer TEXT NULL, is_correct TINYINT(1) NOT NULL DEFAULT 0, attempt_count INT UNSIGNED NOT NULL DEFAULT 1, gps_lat_submitted DECIMAL(10,7) NULL, gps_lng_submitted DECIMAL(10,7) NULL, distance_calculated_m DECIMAL(12,2) NULL, status ENUM('PASS','WRONG_ATTEMPT','BLOCKED_DEVICE','EXHAUSTED','EXPIRED','SETTLED') NOT NULL, created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP, FOREIGN KEY(user_id) REFERENCES users(id), FOREIGN KEY(spot_id) REFERENCES spot_campaigns(spot_id), FOREIGN KEY(riddle_id) REFERENCES riddle_pool(riddle_id), INDEX idx_scan_audit_spot(spot_id,created_at), INDEX idx_scan_device(device_hash,spot_id), UNIQUE KEY idx_device_spot_success(device_hash,spot_id,is_correct)) ENGINE=InnoDB;
ALTER TABLE hunt_videos ADD CONSTRAINT fk_video_category FOREIGN KEY(category_id) REFERENCES video_categories(id);
CREATE TABLE video_ads (
 id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY, video_id BIGINT UNSIGNED NOT NULL, enabled TINYINT(1) NOT NULL DEFAULT 1, display_type ENUM('PRE_ROLL','MID_ROLL','FULL_SCREEN') NOT NULL, trigger_seconds INT UNSIGNED NULL, media_url VARCHAR(500) NOT NULL, skip_after_seconds INT UNSIGNED NULL, cta_text VARCHAR(120) NULL, cta_url VARCHAR(500) NULL, created_by INT UNSIGNED NOT NULL, created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP, updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP, CHECK((display_type<>'MID_ROLL') OR trigger_seconds IS NOT NULL), FOREIGN KEY(video_id) REFERENCES hunt_videos(id) ON DELETE CASCADE, FOREIGN KEY(created_by) REFERENCES admins(id), INDEX idx_video_ad_enabled(video_id,enabled)
) ENGINE=InnoDB;
CREATE TABLE qr_campaigns (
 id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY, campaign_code VARCHAR(50) NOT NULL UNIQUE, name VARCHAR(190) NOT NULL, category_id BIGINT UNSIGNED NULL, qr_code_id BIGINT UNSIGNED NOT NULL UNIQUE, video_id BIGINT UNSIGNED NULL, clue_text TEXT NULL, geofence_enabled TINYINT(1) NOT NULL DEFAULT 0, reward_type ENUM('PROMO_CASH','REWARD_COINS','VENDOR_VOUCHER') NOT NULL DEFAULT 'PROMO_CASH', reward_value DECIMAL(18,2) NOT NULL DEFAULT 0.00, ttl_minutes INT UNSIGNED NULL, auto_reversal_enabled TINYINT(1) NOT NULL DEFAULT 0, merchant_id BIGINT UNSIGNED NULL, scan_frequency ENUM('PER_DEVICE','DAILY','UNLIMITED') NOT NULL DEFAULT 'PER_DEVICE', budget_limit INT UNSIGNED NULL, claimed_count INT UNSIGNED NOT NULL DEFAULT 0, opens_at TIME NULL, closes_at TIME NULL, status ENUM('ACTIVE','INACTIVE','EXPIRED') NOT NULL DEFAULT 'ACTIVE', created_by INT UNSIGNED NOT NULL, created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP, updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
 CHECK(reward_value>=0), CHECK(ttl_minutes IS NULL OR ttl_minutes BETWEEN 1 AND 10080), CHECK(budget_limit IS NULL OR budget_limit>0), INDEX idx_qr_campaign_status(status), FOREIGN KEY(category_id) REFERENCES video_categories(id), FOREIGN KEY(qr_code_id) REFERENCES hunt_qr_codes(id), FOREIGN KEY(video_id) REFERENCES hunt_videos(id), FOREIGN KEY(merchant_id) REFERENCES vendors(id), FOREIGN KEY(created_by) REFERENCES admins(id)
) ENGINE=InnoDB;
CREATE TABLE qr_promo_grants (
 id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY, grant_id VARCHAR(50) NOT NULL UNIQUE, campaign_id BIGINT UNSIGNED NOT NULL, user_id BIGINT UNSIGNED NOT NULL, device_id VARCHAR(190) NOT NULL, amount DECIMAL(18,2) NOT NULL, status ENUM('ACTIVE','SPENT','EXPIRED','REVERSED') NOT NULL DEFAULT 'ACTIVE', expires_at DATETIME NULL, spent_at DATETIME NULL, reversed_at DATETIME NULL, checkout_reference VARCHAR(120) NULL, created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP, UNIQUE KEY uq_campaign_user_device(campaign_id,user_id,device_id), INDEX idx_promo_expiry(status,expires_at), FOREIGN KEY(campaign_id) REFERENCES qr_campaigns(id), FOREIGN KEY(user_id) REFERENCES users(id)
) ENGINE=InnoDB;
CREATE TABLE wallet_bonus_rules (
 id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY, signup_bonus DECIMAL(18,2) NOT NULL DEFAULT 0.00, referral_bonus DECIMAL(18,2) NOT NULL DEFAULT 0.00, bonus_burn_percent DECIMAL(5,2) NOT NULL DEFAULT 10.00, updated_by INT UNSIGNED NULL, updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP, CHECK(signup_bonus>=0), CHECK(referral_bonus>=0), CHECK(bonus_burn_percent BETWEEN 0 AND 100), FOREIGN KEY(updated_by) REFERENCES admins(id)
) ENGINE=InnoDB;
CREATE TABLE user_payment_methods (
 id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY, user_id BIGINT UNSIGNED NOT NULL, method_type ENUM('BANK','UPI') NOT NULL, account_holder VARCHAR(190) NOT NULL, account_reference_encrypted TEXT NOT NULL, account_masked VARCHAR(120) NOT NULL, ifsc VARCHAR(20) NULL, status ENUM('ACTIVE','BLOCKED') NOT NULL DEFAULT 'ACTIVE', created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP, updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP, INDEX idx_user_payment_method(user_id,status), FOREIGN KEY(user_id) REFERENCES users(id)
) ENGINE=InnoDB;
CREATE TABLE wallet_checkout_transactions (
 id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY, checkout_id VARCHAR(50) NOT NULL UNIQUE, user_id BIGINT UNSIGNED NOT NULL, merchant_id BIGINT UNSIGNED NULL, bill_amount DECIMAL(18,2) NOT NULL, promo_used DECIMAL(18,2) NOT NULL DEFAULT 0.00, bonus_used DECIMAL(18,2) NOT NULL DEFAULT 0.00, real_used DECIMAL(18,2) NOT NULL DEFAULT 0.00, status ENUM('PENDING','SUCCESS','FAILED','REVERSED') NOT NULL DEFAULT 'PENDING', idempotency_key VARCHAR(100) NOT NULL, created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP, UNIQUE KEY uq_checkout_idempotency(user_id,idempotency_key), FOREIGN KEY(user_id) REFERENCES users(id), FOREIGN KEY(merchant_id) REFERENCES vendors(id)
) ENGINE=InnoDB;
CREATE TABLE wallet_expiry_jobs (
 id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY, bucket ENUM('BONUS','PROMO') NOT NULL, wallet_id BIGINT UNSIGNED NOT NULL, amount DECIMAL(18,2) NOT NULL, reference VARCHAR(120) NOT NULL UNIQUE, expires_at DATETIME NOT NULL, status ENUM('PENDING','PROCESSED','CANCELLED') NOT NULL DEFAULT 'PENDING', processed_at DATETIME NULL, created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP, INDEX idx_expiry_job_due(status,expires_at), FOREIGN KEY(wallet_id) REFERENCES wallets(id)
) ENGINE=InnoDB;

CREATE TABLE qr_batches (
 id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY, batch_code VARCHAR(60) NOT NULL UNIQUE, batch_title VARCHAR(190) NOT NULL,
 category ENUM('VIDEO_PAHELI','PRODUCT_SKU','COUPON_VOUCHER','BONUS_POINT') NOT NULL, generation_mode ENUM('UPLOAD','AUTO_COUNT') NOT NULL,
 quantity INT UNSIGNED NOT NULL, scan_frequency ENUM('SINGLE_USE','PER_DEVICE','DAILY','POOL') NOT NULL DEFAULT 'PER_DEVICE',
 reward_type VARCHAR(40) NOT NULL, reward_value DECIMAL(18,2) NOT NULL DEFAULT 0, status ENUM('ACTIVE','PAUSED','DEACTIVATED','COMPLETED') NOT NULL DEFAULT 'ACTIVE',
 hunt_id BIGINT UNSIGNED NULL, location_id BIGINT UNSIGNED NULL, created_by INT UNSIGNED NOT NULL, created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
 updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP, INDEX idx_qr_batch_status(status),
 FOREIGN KEY(hunt_id) REFERENCES hunts(id), FOREIGN KEY(location_id) REFERENCES hunt_locations(id), FOREIGN KEY(created_by) REFERENCES admins(id)
) ENGINE=InnoDB;
CREATE TABLE qr_batch_items (
 id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY, batch_id BIGINT UNSIGNED NOT NULL, spot_id VARCHAR(80) NOT NULL UNIQUE, spot_name VARCHAR(190) NOT NULL,
 category_tag VARCHAR(120) NULL, riddle_text TEXT NULL, youtube_video_id VARCHAR(40) NULL, correct_answer_hash CHAR(64) NULL,
 reward_type VARCHAR(40) NOT NULL, reward_value DECIMAL(18,2) NOT NULL DEFAULT 0, timer_ttl_minutes INT UNSIGNED NULL,
 qr_code_id BIGINT UNSIGNED NULL, encrypted_url VARCHAR(500) NOT NULL, status ENUM('ACTIVE','PAUSED','DEACTIVATED','SCANNED') NOT NULL DEFAULT 'ACTIVE',
 scanned_count INT UNSIGNED NOT NULL DEFAULT 0, created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP, INDEX idx_qr_batch_item(batch_id,status),
 FOREIGN KEY(batch_id) REFERENCES qr_batches(id) ON DELETE CASCADE, FOREIGN KEY(qr_code_id) REFERENCES hunt_qr_codes(id)
) ENGINE=InnoDB;
CREATE TABLE staff_profiles (
 id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY, admin_id INT UNSIGNED NOT NULL UNIQUE, mobile VARCHAR(30) NOT NULL UNIQUE, address VARCHAR(500) NULL,
 designation VARCHAR(120) NULL, aadhaar_front_path VARCHAR(255) NULL, aadhaar_back_path VARCHAR(255) NULL, pan_path VARCHAR(255) NULL, photo_path VARCHAR(255) NULL,
 kyc_status ENUM('PENDING','APPROVED','REJECTED') NOT NULL DEFAULT 'PENDING', approved_by INT UNSIGNED NULL, approved_at DATETIME NULL,
 created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP, updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
 FOREIGN KEY(admin_id) REFERENCES admins(id) ON DELETE CASCADE, FOREIGN KEY(approved_by) REFERENCES admins(id)
) ENGINE=InnoDB;
CREATE TABLE admin_otp_challenges (
 id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY, mobile VARCHAR(30) NOT NULL, admin_id INT UNSIGNED NULL, otp_hash CHAR(64) NOT NULL,
 purpose ENUM('LOGIN','KYC') NOT NULL DEFAULT 'LOGIN', attempts TINYINT UNSIGNED NOT NULL DEFAULT 0, expires_at DATETIME NOT NULL,
 consumed_at DATETIME NULL, ip_address VARCHAR(45) NULL, created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
 INDEX idx_admin_otp(mobile,purpose,expires_at), FOREIGN KEY(admin_id) REFERENCES admins(id)
) ENGINE=InnoDB;
CREATE TABLE coupons (
 id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY, code_hash CHAR(64) NOT NULL UNIQUE, code_reference VARCHAR(80) NOT NULL UNIQUE, title VARCHAR(190) NOT NULL,
 discount_amount DECIMAL(18,2) NOT NULL, min_cart_amount DECIMAL(18,2) NOT NULL DEFAULT 0, merchant_id BIGINT UNSIGNED NULL,
 ttl_minutes INT UNSIGNED NULL, expires_at DATETIME NULL, status ENUM('ACTIVE','USED','EXPIRED','CANCELLED') NOT NULL DEFAULT 'ACTIVE', user_id BIGINT UNSIGNED NULL,
 used_at DATETIME NULL, created_by INT UNSIGNED NOT NULL, created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
 FOREIGN KEY(merchant_id) REFERENCES vendors(id), FOREIGN KEY(user_id) REFERENCES users(id), FOREIGN KEY(created_by) REFERENCES admins(id), INDEX idx_coupon_status(status)
) ENGINE=InnoDB;
CREATE TABLE merchant_settlements (
 id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY, settlement_id VARCHAR(60) NOT NULL UNIQUE, checkout_id VARCHAR(50) NOT NULL UNIQUE, merchant_id BIGINT UNSIGNED NOT NULL,
 bill_amount DECIMAL(18,2) NOT NULL, promo_amount DECIMAL(18,2) NOT NULL DEFAULT 0, customer_paid DECIMAL(18,2) NOT NULL DEFAULT 0,
 platform_fee DECIMAL(18,2) NOT NULL DEFAULT 0, merchant_amount DECIMAL(18,2) NOT NULL DEFAULT 0, status ENUM('PENDING','SETTLED','REVERSED') NOT NULL DEFAULT 'PENDING',
 created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP, settled_at DATETIME NULL, FOREIGN KEY(merchant_id) REFERENCES vendors(id), INDEX idx_settlement_status(status,created_at)
) ENGINE=InnoDB;

INSERT INTO admin_roles(name) VALUES ('Super Admin'),('Finance Admin'),('Support Admin'),('Security Admin');
INSERT INTO admins(role_id,name,username,email,password_hash,status,mfa_enabled)
SELECT id,'Admin','admin','admin@gmail.com','$2y$12$QUt/5aDRXpK6dgMW4LR58.2nlQeFX.ZPfWzmyPR.0PFAdfdyluLge','ACTIVE',0
FROM admin_roles WHERE name='Super Admin' LIMIT 1;
INSERT INTO admin_permissions(code,description) VALUES
('dashboard.view','View dashboard'),('users.view','View users'),('users.edit','Edit users'),('users.block','Change user status'),('wallet.view','View wallets'),('wallet.credit','Credit wallets'),('wallet.debit','Debit wallets'),('wallet.reverse','Reverse wallet transactions'),
('withdrawals.view','View withdrawals'),('withdrawals.approve','Approve withdrawals'),('withdrawals.reject','Reject withdrawals'),('notifications.manage','Manage notifications'),('reports.view','View reports'),('reports.export','Export reports'),('security.view','View security events'),('security.manage','Manage devices and security events'),('admins.view','View admins'),('admins.create','Create admins'),('admins.edit','Edit admins and role permissions'),('audit.view','View audit logs'),('settings.edit','Edit settings'),
('hunts.view','View hunts and scan activity'),('hunts.manage','Create and configure hunts'),('hunts.activate','Activate hunts for users'),('campaigns.manage','Manage master spot campaigns'),('campaigns.audit','View campaign scan audit'),('payments.view','View hunt payments'),('rewards.view','View pending and decided rewards'),('rewards.approve','Approve or reject rewards'),('vendors.view','View vendors and redemptions'),('vendors.manage','Manage vendors and vendor users'),('vendors.kyc.approve','Approve vendor KYC'),('gifts.manage','Manage gifts and reward codes'),('video_categories.manage','Manage video categories'),('video_ads.manage','Manage video advertisements'),('video.upload','Upload/edit media'),('qr_campaigns.manage','Manage QR campaigns'),('qr.generate','Generate bulk QR'),('qr.download','Download bulk QR files'),('wallet.bonus','Adjust bonus and promo balances'),('wallet.checkout','Review wallet checkout'),('payouts.manage','Manage payout accounts and payout workflow'),('payouts.process','Process vendor payouts'),('staff.kyc','Manage staff KYC'),('staff.otp','Manage staff OTP');
INSERT INTO admin_role_permissions(role_id,permission_id) SELECT r.id,p.id FROM admin_roles r CROSS JOIN admin_permissions p WHERE r.name='Super Admin';
INSERT INTO admin_role_permissions(role_id,permission_id) SELECT r.id,p.id FROM admin_roles r JOIN admin_permissions p ON p.code IN ('dashboard.view','wallet.view','wallet.credit','wallet.debit','wallet.reverse','withdrawals.view','withdrawals.approve','withdrawals.reject','reports.view','reports.export') WHERE r.name='Finance Admin';
INSERT INTO admin_role_permissions(role_id,permission_id) SELECT r.id,p.id FROM admin_roles r JOIN admin_permissions p ON p.code IN ('dashboard.view','users.view','users.edit','users.block','wallet.view','withdrawals.view') WHERE r.name='Support Admin';
INSERT INTO admin_role_permissions(role_id,permission_id) SELECT r.id,p.id FROM admin_roles r JOIN admin_permissions p ON p.code IN ('dashboard.view','users.view','security.view','security.manage','audit.view') WHERE r.name='Security Admin';
INSERT INTO admin_role_permissions(role_id,permission_id) SELECT r.id,p.id FROM admin_roles r JOIN admin_permissions p ON p.code IN ('payments.view','rewards.view','rewards.approve','hunts.view') WHERE r.name='Finance Admin';
INSERT INTO admin_role_permissions(role_id,permission_id) SELECT r.id,p.id FROM admin_roles r JOIN admin_permissions p ON p.code IN ('hunts.view','hunts.activate','rewards.view','vendors.view') WHERE r.name='Support Admin';
INSERT INTO admin_role_permissions(role_id,permission_id) SELECT r.id,p.id FROM admin_roles r JOIN admin_permissions p ON p.code IN ('hunts.view','rewards.view','vendors.view') WHERE r.name='Security Admin';
INSERT INTO admin_role_permissions(role_id,permission_id) SELECT r.id,p.id FROM admin_roles r JOIN admin_permissions p ON p.code IN ('wallet.bonus','wallet.checkout','payouts.manage','video_categories.manage','video_ads.manage','qr_campaigns.manage') WHERE r.name='Finance Admin';
INSERT INTO admin_role_permissions(role_id,permission_id) SELECT r.id,p.id FROM admin_roles r JOIN admin_permissions p ON p.code IN ('video_categories.manage','video_ads.manage','qr_campaigns.manage') WHERE r.name='Support Admin';
INSERT INTO transaction_limits(user_id,min_withdrawal,max_withdrawal,daily_withdrawal) VALUES(NULL,100,50000,100000);
INSERT INTO system_settings(`key`,`value`,is_secret) VALUES ('hunt_scan_rate_per_minute','10',0),('gps_max_accuracy_meters','150',0),('reward_code_valid_days','30',0);
INSERT INTO wallet_bonus_rules(signup_bonus,referral_bonus,bonus_burn_percent) VALUES(50.00,25.00,10.00);

DELIMITER $$
CREATE TRIGGER audit_logs_no_update BEFORE UPDATE ON audit_logs FOR EACH ROW BEGIN SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT='Audit logs are immutable'; END$$
CREATE TRIGGER audit_logs_no_delete BEFORE DELETE ON audit_logs FOR EACH ROW BEGIN SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT='Audit logs are immutable'; END$$
DELIMITER ;
