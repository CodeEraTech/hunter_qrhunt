-- Apply after production_update.sql. Does not seed financial balances or alter legacy ledger rows.
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
