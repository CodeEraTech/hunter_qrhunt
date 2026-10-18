-- Hunter Wallet Database Migration: Questions, Collections, Broadcast Messaging, Targeted Offers
-- Supports Blueprint 1, 2, 3, 4 Developer Specification

ALTER TABLE riddle_pool
  ADD COLUMN IF NOT EXISTS reward_type ENUM('COINS','CASH','CASHBACK','COUPON','FREE_PIN') NOT NULL DEFAULT 'COINS' AFTER answer_time_seconds,
  ADD COLUMN IF NOT EXISTS reward_value DECIMAL(18,2) NOT NULL DEFAULT 50.00 AFTER reward_type,
  ADD COLUMN IF NOT EXISTS reward_expiry_days INT UNSIGNED NOT NULL DEFAULT 7 AFTER reward_value,
  ADD COLUMN IF NOT EXISTS fallback_offer_title VARCHAR(255) NULL AFTER reward_expiry_days,
  ADD COLUMN IF NOT EXISTS collection_id BIGINT UNSIGNED NULL AFTER fallback_offer_title;

ALTER TABLE question_collections
  ADD COLUMN IF NOT EXISTS negative_marking_points INT NOT NULL DEFAULT 0 AFTER questions_per_session,
  ADD COLUMN IF NOT EXISTS fallback_offer_title VARCHAR(255) NULL AFTER negative_marking_points;

CREATE TABLE IF NOT EXISTS question_attempts (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id BIGINT UNSIGNED NOT NULL,
    riddle_id BIGINT UNSIGNED NOT NULL,
    collection_id BIGINT UNSIGNED NULL,
    spot_id VARCHAR(64) NOT NULL,
    submitted_answer VARCHAR(255) NOT NULL,
    is_correct TINYINT(1) NOT NULL DEFAULT 0,
    penalty_applied DECIMAL(18,2) NOT NULL DEFAULT 0.00,
    reward_type VARCHAR(32) NULL,
    reward_amount DECIMAL(18,2) NOT NULL DEFAULT 0.00,
    fallback_offer VARCHAR(255) NULL,
    idempotency_key VARCHAR(128) NOT NULL UNIQUE,
    attempted_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_qa_user(user_id),
    INDEX idx_qa_riddle(riddle_id),
    INDEX idx_qa_spot(spot_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS hunt_broadcasts (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    broadcast_code VARCHAR(64) NOT NULL UNIQUE,
    campaign_id VARCHAR(64) NOT NULL,
    target_type ENUM('PROMOTER','VENDOR','SHOP','CUSTOMER') NOT NULL,
    target_id VARCHAR(120) NOT NULL,
    message TEXT NOT NULL,
    reward_type ENUM('COINS','BONUS_CASH','COUPON') NOT NULL DEFAULT 'COINS',
    reward_value DECIMAL(18,2) NOT NULL DEFAULT 0.00,
    validity_window_minutes INT UNSIGNED NOT NULL DEFAULT 60,
    starts_at DATETIME NOT NULL,
    expires_at DATETIME NOT NULL,
    pin_hash VARCHAR(255) NOT NULL,
    qr_token VARCHAR(64) NOT NULL UNIQUE,
    status ENUM('DRAFT','ACTIVE','EXPIRED','CANCELLED','FAILED') NOT NULL DEFAULT 'ACTIVE',
    fcm_status ENUM('QUEUED','SENT','FAILED') NOT NULL DEFAULT 'SENT',
    fcm_response JSON NULL,
    created_by INT UNSIGNED NOT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_hb_campaign(campaign_id),
    INDEX idx_hb_status(status, expires_at),
    INDEX idx_hb_qr(qr_token)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS hunt_broadcast_claims (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    broadcast_id BIGINT UNSIGNED NOT NULL,
    user_id BIGINT UNSIGNED NOT NULL,
    qr_token_scanned VARCHAR(64) NOT NULL,
    pin_verified TINYINT(1) NOT NULL DEFAULT 0,
    reward_type VARCHAR(32) NOT NULL,
    reward_amount DECIMAL(18,2) NOT NULL DEFAULT 0.00,
    status ENUM('SUCCESS','INVALID_PIN','EXPIRED','DUPLICATE','REJECTED') NOT NULL,
    idempotency_key VARCHAR(128) NOT NULL UNIQUE,
    claimed_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (broadcast_id) REFERENCES hunt_broadcasts(id) ON DELETE CASCADE,
    INDEX idx_hbc_user(user_id),
    INDEX idx_hbc_token(qr_token_scanned)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS targeted_offers (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    offer_title VARCHAR(190) NOT NULL,
    target_type ENUM('PRODUCT','CATEGORY','VENDOR','USER') NOT NULL,
    target_id VARCHAR(120) NOT NULL,
    target_name VARCHAR(190) NULL,
    bonus_type ENUM('PERCENTAGE','FIXED') NOT NULL DEFAULT 'PERCENTAGE',
    bonus_value DECIMAL(18,2) NOT NULL DEFAULT 0.00,
    shop_id VARCHAR(120) NULL,
    min_cart DECIMAL(18,2) NOT NULL DEFAULT 0.00,
    max_limit DECIMAL(18,2) NOT NULL DEFAULT 0.00,
    start_date DATETIME NOT NULL,
    end_date DATETIME NOT NULL,
    status ENUM('ACTIVE','INACTIVE') NOT NULL DEFAULT 'ACTIVE',
    created_by INT UNSIGNED NOT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_offer_target(target_type, target_id, status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

