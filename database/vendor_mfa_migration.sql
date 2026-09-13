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
