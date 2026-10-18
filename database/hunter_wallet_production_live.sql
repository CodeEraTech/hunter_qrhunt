-- MySQL dump 10.13  Distrib 9.7.1, for macos14.8 (x86_64)
--
-- Host: 127.0.0.1    Database: hunter_wallet
-- ------------------------------------------------------
-- Server version	9.7.1

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!50503 SET NAMES utf8mb4 */;
/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;

--
-- Table structure for table `admin_otp_challenges`
--

DROP TABLE IF EXISTS `admin_otp_challenges`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `admin_otp_challenges` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `mobile` varchar(30) COLLATE utf8mb4_unicode_ci NOT NULL,
  `admin_id` int unsigned DEFAULT NULL,
  `otp_hash` char(64) COLLATE utf8mb4_unicode_ci NOT NULL,
  `purpose` enum('LOGIN','KYC') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'LOGIN',
  `attempts` tinyint unsigned NOT NULL DEFAULT '0',
  `expires_at` datetime NOT NULL,
  `consumed_at` datetime DEFAULT NULL,
  `ip_address` varchar(45) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_admin_otp` (`mobile`,`purpose`,`expires_at`),
  KEY `admin_id` (`admin_id`),
  CONSTRAINT `admin_otp_challenges_ibfk_1` FOREIGN KEY (`admin_id`) REFERENCES `admins` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `admin_otp_challenges`
--

LOCK TABLES `admin_otp_challenges` WRITE;
/*!40000 ALTER TABLE `admin_otp_challenges` DISABLE KEYS */;
/*!40000 ALTER TABLE `admin_otp_challenges` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `admin_permissions`
--

DROP TABLE IF EXISTS `admin_permissions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `admin_permissions` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `code` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `code` (`code`)
) ENGINE=InnoDB AUTO_INCREMENT=97 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `admin_permissions`
--

LOCK TABLES `admin_permissions` WRITE;
/*!40000 ALTER TABLE `admin_permissions` DISABLE KEYS */;
INSERT INTO `admin_permissions` VALUES (1,'dashboard.view','View dashboard'),(2,'users.view','View users'),(3,'users.edit','Edit users'),(4,'users.block','Change user status'),(5,'wallet.view','View wallets'),(6,'wallet.credit','Credit wallets'),(7,'wallet.debit','Debit wallets'),(8,'wallet.reverse','Reverse wallet transactions'),(9,'withdrawals.view','View withdrawals'),(10,'withdrawals.approve','Approve withdrawals'),(11,'withdrawals.reject','Reject withdrawals'),(12,'notifications.manage','Manage notifications'),(13,'reports.view','View reports'),(14,'reports.export','Export reports'),(15,'security.view','View security events'),(16,'security.manage','Manage devices and security events'),(17,'admins.view','View admins'),(18,'admins.create','Create admins'),(19,'admins.edit','Edit admins and role permissions'),(20,'audit.view','View audit logs'),(21,'settings.edit','Edit settings'),(22,'hunts.view','View hunts and scan activity'),(23,'hunts.manage','Create and configure hunts'),(24,'hunts.activate','Activate hunts for users'),(25,'payments.view','View hunt payments'),(26,'rewards.view','View pending and decided rewards'),(27,'rewards.approve','Approve or reject rewards'),(28,'vendors.view','View vendors and redemptions'),(29,'vendors.manage','Manage vendors and vendor users'),(30,'gifts.manage','Manage gifts and reward codes'),(31,'video_categories.manage','Manage video categories'),(32,'video_ads.manage','Manage video advertisements'),(33,'qr_campaigns.manage','Manage QR campaigns'),(34,'wallet.bonus','Adjust bonus and promo balances'),(35,'wallet.checkout','Review wallet checkout'),(36,'payouts.manage','Manage payout accounts and payout workflow'),(37,'vendors.kyc.approve','Approve vendor KYC'),(38,'video.upload','Upload/edit media'),(39,'qr.generate','Generate bulk QR'),(40,'qr.download','Download bulk QR files'),(41,'payouts.process','Process vendor payouts'),(42,'staff.kyc','Manage staff KYC'),(43,'staff.otp','Manage staff OTP'),(45,'campaigns.manage','Manage master spot campaigns'),(46,'campaigns.audit','View campaign scan audit'),(47,'security.governance','View Security & Governance dashboard and staff controls'),(51,'vendor_products.view','View vendor product moderation queue'),(52,'vendor_products.moderate','Approve, reject or disable vendor products'),(53,'vendor_products.export','Export vendor product moderation records'),(55,'vendor_settlements.view','View vendor money requests and withdrawals'),(56,'vendor_settlements.approve','Approve vendor settlements'),(57,'vendor_settlements.export','Export vendor settlement reports'),(66,'wallet.master.view','View the master wallet and distribution ledger'),(67,'wallet.master.distribute','Distribute and recall master wallet funds using PIN and OTP'),(68,'wallet.master.fund','Record received master wallet funding using PIN and OTP'),(69,'products.view','View product directory'),(70,'products.create','Create new products'),(71,'products.edit','Edit existing products'),(72,'products.delete','Delete/disable products'),(73,'products.approve','Approve/reject products'),(74,'products.export','Export product data'),(75,'products.bulk_upload','Bulk upload products'),(76,'products.categories.manage','Manage product categories'),(77,'products.qr.manage','Manage product QR codes'),(78,'products.audit.view','View product audit history');
/*!40000 ALTER TABLE `admin_permissions` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `admin_role_permissions`
--

DROP TABLE IF EXISTS `admin_role_permissions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `admin_role_permissions` (
  `role_id` int unsigned NOT NULL,
  `permission_id` int unsigned NOT NULL,
  PRIMARY KEY (`role_id`,`permission_id`),
  KEY `permission_id` (`permission_id`),
  CONSTRAINT `admin_role_permissions_ibfk_1` FOREIGN KEY (`role_id`) REFERENCES `admin_roles` (`id`),
  CONSTRAINT `admin_role_permissions_ibfk_2` FOREIGN KEY (`permission_id`) REFERENCES `admin_permissions` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `admin_role_permissions`
--

LOCK TABLES `admin_role_permissions` WRITE;
/*!40000 ALTER TABLE `admin_role_permissions` DISABLE KEYS */;
INSERT INTO `admin_role_permissions` VALUES (1,1),(2,1),(3,1),(4,1),(1,2),(3,2),(4,2),(1,3),(3,3),(1,4),(3,4),(1,5),(2,5),(3,5),(1,6),(2,6),(1,7),(2,7),(1,8),(2,8),(1,9),(2,9),(3,9),(1,10),(2,10),(1,11),(2,11),(1,12),(1,13),(2,13),(1,14),(2,14),(1,15),(4,15),(1,16),(4,16),(1,17),(1,18),(1,19),(1,20),(4,20),(1,21),(1,22),(2,22),(3,22),(4,22),(1,23),(1,24),(3,24),(1,25),(2,25),(1,26),(2,26),(3,26),(4,26),(1,27),(2,27),(1,28),(3,28),(4,28),(1,29),(1,30),(1,31),(2,31),(3,31),(1,32),(2,32),(3,32),(1,33),(2,33),(3,33),(1,34),(2,34),(1,35),(2,35),(1,36),(2,36),(1,47),(1,51),(1,52),(1,53),(1,55),(1,56),(1,57),(1,66),(1,67),(1,68),(1,69),(1,70),(1,71),(1,72),(1,73),(1,74),(1,75),(1,76),(1,77),(1,78);
/*!40000 ALTER TABLE `admin_role_permissions` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `admin_roles`
--

DROP TABLE IF EXISTS `admin_roles`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `admin_roles` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(80) COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `name` (`name`)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `admin_roles`
--

LOCK TABLES `admin_roles` WRITE;
/*!40000 ALTER TABLE `admin_roles` DISABLE KEYS */;
INSERT INTO `admin_roles` VALUES (1,'Super Admin','2026-08-23 19:08:10'),(2,'Finance Admin','2026-08-23 19:08:10'),(3,'Support Admin','2026-08-23 19:08:10'),(4,'Security Admin','2026-08-23 19:08:10');
/*!40000 ALTER TABLE `admin_roles` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `admins`
--

DROP TABLE IF EXISTS `admins`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `admins` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `role_id` int unsigned NOT NULL,
  `name` varchar(120) COLLATE utf8mb4_unicode_ci NOT NULL,
  `username` varchar(80) COLLATE utf8mb4_unicode_ci NOT NULL,
  `email` varchar(190) COLLATE utf8mb4_unicode_ci NOT NULL,
  `password_hash` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `status` enum('ACTIVE','BLOCKED') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'ACTIVE',
  `mfa_enabled` tinyint(1) NOT NULL DEFAULT '0',
  `mfa_secret_encrypted` text COLLATE utf8mb4_unicode_ci,
  `last_login_at` datetime DEFAULT NULL,
  `last_login_ip` varchar(45) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `username` (`username`),
  UNIQUE KEY `email` (`email`),
  KEY `role_id` (`role_id`),
  CONSTRAINT `admins_ibfk_1` FOREIGN KEY (`role_id`) REFERENCES `admin_roles` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=19 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `admins`
--

LOCK TABLES `admins` WRITE;
/*!40000 ALTER TABLE `admins` DISABLE KEYS */;
INSERT INTO `admins` VALUES (1,1,'Integration Admin','integration-admin','integration-admin@example.test','$2y$12$94.eSalb80ArHPRjOKuoH.sySXbKwlHc6MONMG16KyfIgmkOpErWq','ACTIVE',0,NULL,NULL,NULL,'2026-08-23 19:08:21','2026-08-23 19:08:21'),(2,1,'Integration Admin','integration','integration@example.test','$2y$12$yLcCGdfWGDMkYnTV6YW98OYJKx3mKlBFQkus7d.ZjJ6mgohxU2OFq','ACTIVE',0,NULL,'2026-09-13 16:47:15','127.0.0.1','2026-08-23 19:08:37','2026-09-13 16:47:15'),(4,1,'Integration Admin','adm-8ce8ea53','adm-8ce8ea53@example.test','$2y$12$JrBy.4XL2CoXGQkBGiuUwesLFxIcQvFQbzeKZ8Edp.WbdXWRe0YkG','ACTIVE',0,NULL,NULL,NULL,'2026-10-17 18:37:51','2026-10-17 18:37:51'),(5,1,'Integration Admin','adm-129b73ae','adm-129b73ae@example.test','$2y$12$mI4czl9nnKSh/70q.PfnfegUdcyEVy8DXkjowguK3KR.KWU3GS716','ACTIVE',0,NULL,NULL,NULL,'2026-10-17 18:38:34','2026-10-17 18:38:34'),(6,1,'Integration Admin','adm-1bac2a6b','adm-1bac2a6b@example.test','$2y$12$A4JJ7PT11YvKfiP.sZyDK.71mi7X5YtXsR7wQwQbPagMNhLFqQlLi','ACTIVE',0,NULL,NULL,NULL,'2026-10-17 18:38:56','2026-10-17 18:38:56'),(7,1,'Super Admin','superadmin','superadmin@hunterwallet.test','$2y$12$wfSWPKsDmZZHWrYv/9EcgOqa9zV28FdlrPT7JzA1wM7A/72lT05RG','ACTIVE',0,NULL,'2026-10-18 09:53:10','127.0.0.1','2026-10-17 18:55:29','2026-10-18 09:53:10'),(8,1,'Integration Admin','adm-97790886','adm-97790886@example.test','$2y$12$1A3xF8mk/WJC7qM7IVTzpOsKcvHejQnf9aU2prkTsRAqjR8gJVwam','ACTIVE',0,NULL,NULL,NULL,'2026-10-17 19:57:24','2026-10-17 19:57:24'),(9,1,'Integration Admin','adm-6415b496','adm-6415b496@example.test','$2y$12$m6Z/KFVcGzTrz7KMP86ceeyw81lt/RAfxM.0SxRkZeOPo7SpDnMiO','ACTIVE',0,NULL,NULL,NULL,'2026-10-17 21:21:24','2026-10-17 21:21:24'),(10,1,'Test Admin','adm-353d6fb1','adm-353d6fb1@example.test','$2y$12$RZwkrczo1G1vGcO8KAZyp.YAVlguTRv4iyuiCMqhqS/XLh4Bie/8m','ACTIVE',0,NULL,NULL,NULL,'2026-10-17 21:32:33','2026-10-17 21:32:33'),(11,1,'Test Admin','adm-183d672e','adm-183d672e@example.test','$2y$12$7oSn.N0WiiSzh3XNuBu3iu8Dr3d5y12faUJ5jST4DN9rHxr30gboW','ACTIVE',0,NULL,NULL,NULL,'2026-10-17 21:35:19','2026-10-17 21:35:19'),(12,1,'Test Admin','adm-4120c86a','adm-4120c86a@example.test','$2y$12$AlBYJgTH5XKSif3tfo1A6eh2Jq2n.H/o6SrIlPHhl/c5tut1c/V6q','ACTIVE',0,NULL,NULL,NULL,'2026-10-17 21:37:27','2026-10-17 21:37:27'),(13,1,'Test Admin','adm-32a00416','adm-32a00416@example.test','$2y$12$QCuV.AKwPCI8BnDOXY9lROzINQcRPsr7mg7SXO1fkWdOt.GqC5IF.','ACTIVE',0,NULL,NULL,NULL,'2026-10-17 21:38:31','2026-10-17 21:38:31'),(14,1,'Integration Admin','adm-d1411073','adm-d1411073@example.test','$2y$12$iuhr.rTvtFXbK6ybmS98y.tNODnXARLJ9n1T07pqQCHnYje.U0xQS','ACTIVE',0,NULL,NULL,NULL,'2026-10-17 21:38:33','2026-10-17 21:38:33'),(15,1,'Test Admin','adm-4f67df73','adm-4f67df73@example.test','$2y$12$zGr6VEMpnqQMjJDd.UEUAuAHQmJir/QSeTTfNOwn0iGV6fTNoHSiK','ACTIVE',0,NULL,NULL,NULL,'2026-10-17 22:51:21','2026-10-17 22:51:21'),(16,1,'Integration Admin','adm-3439d2e2','adm-3439d2e2@example.test','$2y$12$.Oay0Q7yPKjVXCNiVhfAl.qrUiGpEQnqaLIrwXC9wlNgeYXOuiKfO','ACTIVE',0,NULL,NULL,NULL,'2026-10-17 22:51:23','2026-10-17 22:51:23'),(17,1,'Test Admin','adm-2a0bf7e2','adm-2a0bf7e2@example.test','$2y$12$XDx/WwkYnkWRQElTqPxiUe.J1IxD./pLYlvcg7hrQZV4sEhtpqIqC','ACTIVE',0,NULL,NULL,NULL,'2026-10-18 09:25:15','2026-10-18 09:25:15'),(18,1,'Integration Admin','adm-86f7090e','adm-86f7090e@example.test','$2y$12$0MtpJVJM3eSX0F7bCYxOp.tIcVOW23OSyRXmZXyl4ImzL2l4m0PCu','ACTIVE',0,NULL,NULL,NULL,'2026-10-18 09:25:17','2026-10-18 09:25:17');
/*!40000 ALTER TABLE `admins` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `api_idempotency`
--

DROP TABLE IF EXISTS `api_idempotency`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `api_idempotency` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint unsigned NOT NULL,
  `idempotency_key` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `endpoint` varchar(120) COLLATE utf8mb4_unicode_ci NOT NULL,
  `response_code` smallint unsigned NOT NULL,
  `response_body` json NOT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `user_id` (`user_id`,`idempotency_key`,`endpoint`),
  CONSTRAINT `api_idempotency_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `api_idempotency`
--

LOCK TABLES `api_idempotency` WRITE;
/*!40000 ALTER TABLE `api_idempotency` DISABLE KEYS */;
INSERT INTO `api_idempotency` VALUES (1,4,'integration-api-key-000001','withdrawals.create',201,'{\"data\": {\"status\": \"PENDING\", \"withdrawal_id\": \"WD-20261017-FA143BD5\"}, \"status\": true, \"message\": \"Withdrawal request created\"}','2026-10-17 18:58:32');
/*!40000 ALTER TABLE `api_idempotency` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `api_request_logs`
--

DROP TABLE IF EXISTS `api_request_logs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `api_request_logs` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint unsigned DEFAULT NULL,
  `endpoint` varchar(120) COLLATE utf8mb4_unicode_ci NOT NULL,
  `method` varchar(10) COLLATE utf8mb4_unicode_ci NOT NULL,
  `ip_address` varchar(45) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `device_id` varchar(190) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_api_rate` (`user_id`,`endpoint`,`created_at`),
  CONSTRAINT `api_request_logs_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=8 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `api_request_logs`
--

LOCK TABLES `api_request_logs` WRITE;
/*!40000 ALTER TABLE `api_request_logs` DISABLE KEYS */;
INSERT INTO `api_request_logs` VALUES (1,4,'wallet.balance','GET','127.0.0.1','api-test-device-01','2026-10-17 18:58:32'),(2,4,'withdrawals.create','POST','127.0.0.1','api-test-device-01','2026-10-17 18:58:32'),(3,4,'withdrawals.list','GET','127.0.0.1','api-test-device-01','2026-10-17 18:58:32'),(4,4,'wallet.balance','GET','127.0.0.1','api-test-device-01','2026-10-17 18:58:32'),(5,4,'wallet.balance','GET','127.0.0.1','api-test-device-01','2026-10-17 19:58:00'),(6,4,'withdrawals.list','GET','127.0.0.1','api-test-device-01','2026-10-17 19:58:00'),(7,4,'wallet.balance','GET','127.0.0.1','api-test-device-01','2026-10-17 19:58:00');
/*!40000 ALTER TABLE `api_request_logs` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `audit_logs`
--

DROP TABLE IF EXISTS `audit_logs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `audit_logs` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `admin_id` int unsigned DEFAULT NULL,
  `action` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `module` varchar(80) COLLATE utf8mb4_unicode_ci NOT NULL,
  `record_id` varchar(80) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `old_data` json DEFAULT NULL,
  `new_data` json DEFAULT NULL,
  `ip_address` varchar(45) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `user_agent` varchar(500) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_audit_date` (`created_at`),
  KEY `admin_id` (`admin_id`),
  CONSTRAINT `audit_logs_ibfk_1` FOREIGN KEY (`admin_id`) REFERENCES `admins` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=212 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `audit_logs`
--

LOCK TABLES `audit_logs` WRITE;
/*!40000 ALTER TABLE `audit_logs` DISABLE KEYS */;
INSERT INTO `audit_logs` VALUES (1,1,'HUNT_ACTIVATED','hunt_activation','1',NULL,'{\"type\": \"COUNTER\", \"hunt_id\": 1, \"user_id\": 1, \"payment_id\": null}','127.0.0.1','Hunter integration test','2026-08-23 19:08:22'),(2,1,'REWARD_APPROVED','rewards','1','{\"status\": \"PENDING\"}','{\"amount\": \"500.00\", \"reason\": null, \"status\": \"APPROVED\", \"wallet_transaction_id\": 1}','127.0.0.1','Hunter integration test','2026-08-23 19:08:23'),(3,1,'WITHDRAWAL_UNDER_REVIEW','withdrawals','1','{\"status\": \"PENDING\"}','{\"reason\": null, \"status\": \"UNDER_REVIEW\"}','127.0.0.1','Hunter integration test','2026-08-23 19:08:23'),(4,1,'WITHDRAWAL_APPROVED','withdrawals','1','{\"status\": \"UNDER_REVIEW\"}','{\"reason\": null, \"status\": \"APPROVED\"}','127.0.0.1','Hunter integration test','2026-08-23 19:08:23'),(5,NULL,'HUNT_ACTIVATED','hunt_activation',NULL,NULL,'{\"type\": \"ONLINE\", \"hunt_id\": 1, \"user_id\": 2, \"payment_id\": 1}','127.0.0.1','Hunter integration test','2026-08-23 19:08:23'),(6,NULL,'PAYMENT_VERIFIED','payments','1','{\"status\": \"PENDING\"}','{\"status\": \"VERIFIED\", \"provider\": \"RAZORPAY\"}','127.0.0.1','Hunter integration test','2026-08-23 19:08:23'),(7,NULL,'GIFT_REDEEMED','vendor_redemptions','RED-20260823-CC1584DD',NULL,'{\"gift_id\": 1, \"user_id\": 1, \"vendor_id\": 1}','127.0.0.1','Hunter integration test','2026-08-23 19:08:23'),(8,1,'WALLET_BONUS_CREDIT','wallet','3',NULL,'{\"amount\": \"50.00\", \"reference\": \"test-bonus\", \"expires_at\": \"2026-08-23 20:08:23\"}','127.0.0.1','Bonus QR integration test','2026-08-23 19:08:23'),(9,1,'WALLET_PROMO_CREDIT','wallet','3',NULL,'{\"amount\": \"15.00\", \"reference\": \"test-promo\", \"expires_at\": \"2026-08-23 20:08:23\"}','127.0.0.1','Bonus QR integration test','2026-08-23 19:08:23'),(10,NULL,'WALLET_CHECKOUT','wallet_checkout','CHK-20260823-EBFDB531',NULL,'{\"user_id\": 3, \"real_used\": \"75.00\", \"bonus_used\": \"10.00\", \"promo_used\": \"15.00\", \"bill_amount\": \"100.00\"}','127.0.0.1','Bonus QR integration test','2026-08-23 19:08:23'),(11,NULL,'WALLET_BONUS_EXPIRED','wallet','3',NULL,'{\"amount\": \"40.00\", \"reference\": \"test-bonus\"}','127.0.0.1','Bonus QR integration test','2026-08-23 19:08:23'),(12,2,'LOGIN','auth','2',NULL,'{\"ip\": \"127.0.0.1\"}','127.0.0.1','unknown','2026-08-23 19:09:00'),(13,2,'LOGOUT','auth','2',NULL,NULL,'127.0.0.1','unknown','2026-08-23 19:09:00'),(14,2,'LOGIN','auth','2',NULL,'{\"ip\": \"::1\"}','::1','Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36','2026-08-23 19:18:45'),(15,2,'LOGIN','auth','2',NULL,'{\"ip\": \"::1\"}','::1','Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36','2026-08-23 20:21:24'),(16,2,'LOGIN','auth','2',NULL,'{\"ip\": \"::1\"}','::1','Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36','2026-08-23 20:55:26'),(17,2,'LOGOUT','auth','2',NULL,NULL,'::1','Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36','2026-08-23 20:56:20'),(18,2,'LOGIN','auth','2',NULL,'{\"ip\": \"::1\"}','::1','Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36','2026-08-23 21:26:32'),(19,2,'LOGIN','auth','2',NULL,'{\"ip\": \"::1\"}','::1','Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36','2026-08-24 21:03:53'),(20,2,'LOGIN','auth','2',NULL,'{\"ip\": \"::1\"}','::1','Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36','2026-08-29 11:25:08'),(21,2,'WALLET_PROMO_CREDIT','wallet','4',NULL,'{\"amount\": \"100\", \"reference\": \"admin-grant:d76969cf7e034f4b\", \"expires_at\": null}','::1','Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36','2026-08-29 11:53:08'),(22,2,'CATEGORY_BROADCAST_QUEUED','category_broadcasts','1',NULL,NULL,'::1','Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36','2026-08-29 12:44:33'),(23,2,'LOGOUT','auth','2',NULL,NULL,'::1','Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36','2026-08-29 12:53:08'),(24,2,'LOGIN','auth','2',NULL,'{\"ip\": \"::1\"}','::1','Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36','2026-08-29 12:53:26'),(25,2,'LOGOUT','auth','2',NULL,NULL,'::1','Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36','2026-08-29 13:23:05'),(26,2,'LOGIN','auth','2',NULL,'{\"ip\": \"::1\"}','::1','Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36','2026-08-29 13:23:13'),(27,2,'LOGIN','auth','2',NULL,'{\"ip\": \"::1\"}','::1','Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36','2026-08-29 17:34:00'),(28,2,'LOGIN','auth','2',NULL,'{\"ip\": \"::1\"}','::1','Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36','2026-08-30 13:34:58'),(29,2,'LOGIN','auth','2',NULL,'{\"ip\": \"::1\"}','::1','Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36','2026-08-30 15:34:46'),(30,2,'LOGIN','auth','2',NULL,'{\"ip\": \"::1\"}','::1','Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36','2026-08-30 16:30:11'),(31,2,'LOGIN','auth','2',NULL,'{\"ip\": \"::1\"}','::1','Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36','2026-09-01 16:55:34'),(32,2,'QUESTION_CREATED','riddle_pool','2',NULL,NULL,'::1','Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36','2026-09-01 17:42:36'),(33,2,'LOGIN','auth','2',NULL,'{\"ip\": \"::1\"}','::1','Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36','2026-09-01 18:57:12'),(34,2,'LOGIN','auth','2',NULL,'{\"ip\": \"::1\"}','::1','Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36','2026-09-03 21:21:48'),(35,2,'LOGIN','auth','2',NULL,'{\"ip\": \"::1\"}','::1','Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36','2026-09-06 14:04:32'),(36,2,'LOGIN','auth','2',NULL,'{\"ip\": \"127.0.0.1\"}','127.0.0.1','Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36','2026-09-11 18:32:28'),(37,2,'LOGOUT','auth','2',NULL,NULL,'127.0.0.1','Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36','2026-09-11 18:48:08'),(38,2,'LOGIN','auth','2',NULL,'{\"ip\": \"127.0.0.1\"}','127.0.0.1','Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36','2026-09-11 19:16:02'),(39,NULL,'VENDOR_CREATED','vendors','VND-TEST-001',NULL,'{\"created_by\": 1, \"vendor_code\": \"VND-TEST-001\"}','unknown','unknown','2026-09-11 19:51:13'),(40,NULL,'VENDOR_LOGIN','vendor_auth','2',NULL,NULL,'127.0.0.1','Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36','2026-09-11 19:51:44'),(41,NULL,'VENDOR_LOGIN','vendor_auth','2',NULL,NULL,'127.0.0.1','curl/8.7.1','2026-09-11 21:33:06'),(42,NULL,'VENDOR_LOGOUT','vendor_auth','2',NULL,NULL,'127.0.0.1','Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36','2026-09-11 21:41:06'),(43,2,'LOGIN','auth','2',NULL,'{\"ip\": \"127.0.0.1\"}','127.0.0.1','Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36','2026-09-11 21:41:20'),(44,NULL,'VENDOR_LOGIN','vendor_auth','2',NULL,NULL,'127.0.0.1','Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36','2026-09-11 21:41:30'),(45,2,'LOGOUT','auth','2',NULL,NULL,'127.0.0.1','Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36','2026-09-11 21:47:13'),(46,2,'LOGIN','auth','2',NULL,'{\"ip\": \"127.0.0.1\"}','127.0.0.1','Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36','2026-09-11 21:47:18'),(47,2,'LOGIN','auth','2',NULL,'{\"ip\": \"127.0.0.1\"}','127.0.0.1','Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36','2026-09-12 16:12:54'),(48,NULL,'VENDOR_LOGIN','vendor_auth','2',NULL,NULL,'127.0.0.1','curl/8.7.1','2026-09-12 16:33:17'),(49,2,'LOGIN','auth','2',NULL,'{\"ip\": \"127.0.0.1\"}','127.0.0.1','Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36','2026-09-13 10:48:00'),(50,NULL,'VENDOR_LOGIN','vendor_auth','2',NULL,NULL,'127.0.0.1','curl/8.7.1','2026-09-13 12:08:23'),(51,2,'LOGIN','auth','2',NULL,'{\"ip\": \"127.0.0.1\"}','127.0.0.1','Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36','2026-09-13 14:17:57'),(52,NULL,'VENDOR_LOGIN','vendor_auth','2',NULL,NULL,'127.0.0.1','curl/8.7.1','2026-09-13 14:36:01'),(53,2,'LOGIN','auth','2',NULL,'{\"ip\": \"127.0.0.1\"}','127.0.0.1','Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36','2026-09-13 16:47:15'),(54,1,'WALLET_BONUS_CREDIT','wallet','6',NULL,'{\"amount\": \"50.00\", \"reference\": \"test-bonus-b59f52db\", \"expires_at\": \"2026-10-17 19:36:24\"}','127.0.0.1','Bonus QR integration test','2026-10-17 18:36:24'),(55,1,'WALLET_PROMO_CREDIT','wallet','6',NULL,'{\"amount\": \"15.00\", \"reference\": \"test-promo-b59f52db\", \"expires_at\": \"2026-10-17 19:36:24\"}','127.0.0.1','Bonus QR integration test','2026-10-17 18:36:24'),(56,NULL,'WALLET_CHECKOUT','wallet_checkout','CHK-20261017-64FFEBEF',NULL,'{\"user_id\": 6, \"coupon_id\": null, \"real_used\": \"75.00\", \"bonus_used\": \"10.00\", \"promo_used\": \"15.00\", \"bill_amount\": \"100.00\"}','127.0.0.1','Bonus QR integration test','2026-10-17 18:36:24'),(57,NULL,'WALLET_PROMO_EXPIRED','wallet','3',NULL,'{\"amount\": \"0.00\", \"reference\": \"test-promo\"}','127.0.0.1','Bonus QR integration test','2026-10-17 18:36:24'),(58,NULL,'WALLET_BONUS_EXPIRED','wallet','10',NULL,'{\"amount\": \"40.00\", \"reference\": \"test-bonus-b59f52db\"}','127.0.0.1','Bonus QR integration test','2026-10-17 18:36:24'),(59,4,'HUNT_ACTIVATED','hunt_activation','4',NULL,'{\"type\": \"COUNTER\", \"hunt_id\": 5, \"user_id\": 7, \"payment_id\": null}','127.0.0.1','Hunter integration test','2026-10-17 18:37:52'),(60,4,'REWARD_APPROVED','rewards','2','{\"status\": \"PENDING\"}','{\"amount\": \"500.00\", \"reason\": null, \"status\": \"APPROVED\", \"wallet_transaction_id\": 16}','127.0.0.1','Hunter integration test','2026-10-17 18:37:52'),(61,4,'WITHDRAWAL_UNDER_REVIEW','withdrawals','2','{\"status\": \"PENDING\"}','{\"reason\": null, \"status\": \"UNDER_REVIEW\"}','127.0.0.1','Hunter integration test','2026-10-17 18:37:52'),(62,4,'WITHDRAWAL_APPROVED','withdrawals','2','{\"status\": \"UNDER_REVIEW\"}','{\"reason\": null, \"status\": \"APPROVED\"}','127.0.0.1','Hunter integration test','2026-10-17 18:37:52'),(63,5,'HUNT_ACTIVATED','hunt_activation','5',NULL,'{\"type\": \"COUNTER\", \"hunt_id\": 6, \"user_id\": 9, \"payment_id\": null}','127.0.0.1','Hunter integration test','2026-10-17 18:38:35'),(64,5,'REWARD_APPROVED','rewards','3','{\"status\": \"PENDING\"}','{\"amount\": \"500.00\", \"reason\": null, \"status\": \"APPROVED\", \"wallet_transaction_id\": 18}','127.0.0.1','Hunter integration test','2026-10-17 18:38:35'),(65,5,'WITHDRAWAL_UNDER_REVIEW','withdrawals','3','{\"status\": \"PENDING\"}','{\"reason\": null, \"status\": \"UNDER_REVIEW\"}','127.0.0.1','Hunter integration test','2026-10-17 18:38:35'),(66,5,'WITHDRAWAL_APPROVED','withdrawals','3','{\"status\": \"UNDER_REVIEW\"}','{\"reason\": null, \"status\": \"APPROVED\"}','127.0.0.1','Hunter integration test','2026-10-17 18:38:35'),(67,6,'HUNT_ACTIVATED','hunt_activation','6',NULL,'{\"type\": \"COUNTER\", \"hunt_id\": 7, \"user_id\": 11, \"payment_id\": null}','127.0.0.1','Hunter integration test','2026-10-17 18:38:57'),(68,6,'REWARD_APPROVED','rewards','4','{\"status\": \"PENDING\"}','{\"amount\": \"500.00\", \"reason\": null, \"status\": \"APPROVED\", \"wallet_transaction_id\": 20}','127.0.0.1','Hunter integration test','2026-10-17 18:38:57'),(69,6,'WITHDRAWAL_UNDER_REVIEW','withdrawals','4','{\"status\": \"PENDING\"}','{\"reason\": null, \"status\": \"UNDER_REVIEW\"}','127.0.0.1','Hunter integration test','2026-10-17 18:38:57'),(70,6,'WITHDRAWAL_APPROVED','withdrawals','4','{\"status\": \"UNDER_REVIEW\"}','{\"reason\": null, \"status\": \"APPROVED\"}','127.0.0.1','Hunter integration test','2026-10-17 18:38:57'),(71,NULL,'HUNT_ACTIVATED','hunt_activation',NULL,NULL,'{\"type\": \"ONLINE\", \"hunt_id\": 7, \"user_id\": 12, \"payment_id\": 4}','127.0.0.1','Hunter integration test','2026-10-17 18:38:57'),(72,NULL,'PAYMENT_VERIFIED','payments','4','{\"status\": \"PENDING\"}','{\"status\": \"VERIFIED\", \"provider\": \"RAZORPAY\"}','127.0.0.1','Hunter integration test','2026-10-17 18:38:57'),(73,NULL,'GIFT_REDEEMED','vendor_redemptions','RED-20261017-18929A1C',NULL,'{\"gift_id\": 2, \"user_id\": 11, \"vendor_id\": 4}','127.0.0.1','Hunter integration test','2026-10-17 18:38:58'),(74,NULL,'VENDOR_LOGIN','vendor_auth','2',NULL,NULL,'127.0.0.1','unknown','2026-10-17 18:57:21'),(75,NULL,'VENDOR_LOGOUT','vendor_auth','2',NULL,NULL,'127.0.0.1','unknown','2026-10-17 18:57:21'),(76,NULL,'VENDOR_LOGIN','vendor_auth','2',NULL,NULL,'127.0.0.1','unknown','2026-10-17 18:57:45'),(77,NULL,'VENDOR_LOGOUT','vendor_auth','2',NULL,NULL,'127.0.0.1','unknown','2026-10-17 18:57:45'),(78,7,'LOGIN','auth','7',NULL,'{\"ip\": \"127.0.0.1\"}','127.0.0.1','unknown','2026-10-17 18:58:15'),(79,7,'LOGOUT','auth','7',NULL,NULL,'127.0.0.1','unknown','2026-10-17 18:58:15'),(80,7,'LOGIN','auth','7',NULL,'{\"ip\": \"127.0.0.1\"}','127.0.0.1','unknown','2026-10-17 18:59:11'),(81,NULL,'VENDOR_LOGIN','vendor_auth','2',NULL,NULL,'127.0.0.1','unknown','2026-10-17 18:59:12'),(82,7,'LOGIN','auth','7',NULL,'{\"ip\": \"127.0.0.1\"}','127.0.0.1','unknown','2026-10-17 18:59:29'),(83,NULL,'VENDOR_LOGIN','vendor_auth','2',NULL,NULL,'127.0.0.1','unknown','2026-10-17 18:59:29'),(84,7,'LOGIN','auth','7',NULL,'{\"ip\": \"127.0.0.1\"}','127.0.0.1','unknown','2026-10-17 19:36:34'),(85,NULL,'VENDOR_LOGIN','vendor_auth','2',NULL,NULL,'127.0.0.1','unknown','2026-10-17 19:36:35'),(86,7,'LOGIN','auth','7',NULL,'{\"ip\": \"127.0.0.1\"}','127.0.0.1','unknown','2026-10-17 19:53:00'),(87,NULL,'VENDOR_LOGIN','vendor_auth','2',NULL,NULL,'127.0.0.1','unknown','2026-10-17 19:53:00'),(88,7,'LOGIN','auth','7',NULL,'{\"ip\": \"127.0.0.1\"}','127.0.0.1','unknown','2026-10-17 19:54:32'),(89,NULL,'VENDOR_LOGIN','vendor_auth','2',NULL,NULL,'127.0.0.1','unknown','2026-10-17 19:54:32'),(90,7,'LOGIN','auth','7',NULL,'{\"ip\": \"127.0.0.1\"}','127.0.0.1','unknown','2026-10-17 19:55:32'),(91,NULL,'VENDOR_LOGIN','vendor_auth','2',NULL,NULL,'127.0.0.1','unknown','2026-10-17 19:55:32'),(92,7,'LOGIN','auth','7',NULL,'{\"ip\": \"127.0.0.1\"}','127.0.0.1','unknown','2026-10-17 19:56:01'),(93,NULL,'VENDOR_LOGIN','vendor_auth','2',NULL,NULL,'127.0.0.1','unknown','2026-10-17 19:56:01'),(94,7,'LOGIN','auth','7',NULL,'{\"ip\": \"127.0.0.1\"}','127.0.0.1','unknown','2026-10-17 19:56:24'),(95,7,'LOGOUT','auth','7',NULL,NULL,'127.0.0.1','unknown','2026-10-17 19:56:24'),(96,NULL,'VENDOR_LOGIN','vendor_auth','2',NULL,NULL,'127.0.0.1','unknown','2026-10-17 19:56:35'),(97,NULL,'VENDOR_LOGOUT','vendor_auth','2',NULL,NULL,'127.0.0.1','unknown','2026-10-17 19:56:35'),(98,1,'WALLET_BONUS_CREDIT','wallet','14',NULL,'{\"amount\": \"50.00\", \"reference\": \"test-bonus-93bd279a\", \"expires_at\": \"2026-10-17 20:57:16\"}','127.0.0.1','Bonus QR integration test','2026-10-17 19:57:16'),(99,1,'WALLET_PROMO_CREDIT','wallet','14',NULL,'{\"amount\": \"15.00\", \"reference\": \"test-promo-93bd279a\", \"expires_at\": \"2026-10-17 20:57:16\"}','127.0.0.1','Bonus QR integration test','2026-10-17 19:57:16'),(100,NULL,'WALLET_CHECKOUT','wallet_checkout','CHK-20261017-3ABC9757',NULL,'{\"user_id\": 14, \"coupon_id\": null, \"real_used\": \"75.00\", \"bonus_used\": \"10.00\", \"promo_used\": \"15.00\", \"bill_amount\": \"100.00\"}','127.0.0.1','Bonus QR integration test','2026-10-17 19:57:16'),(101,NULL,'WALLET_PROMO_EXPIRED','wallet','10',NULL,'{\"amount\": \"0.00\", \"reference\": \"test-promo-b59f52db\"}','127.0.0.1','Bonus QR integration test','2026-10-17 19:57:16'),(102,NULL,'WALLET_BONUS_EXPIRED','wallet','18',NULL,'{\"amount\": \"40.00\", \"reference\": \"test-bonus-93bd279a\"}','127.0.0.1','Bonus QR integration test','2026-10-17 19:57:16'),(103,8,'HUNT_ACTIVATED','hunt_activation','8',NULL,'{\"type\": \"COUNTER\", \"hunt_id\": 9, \"user_id\": 15, \"payment_id\": null}','127.0.0.1','Hunter integration test','2026-10-17 19:57:25'),(104,8,'REWARD_APPROVED','rewards','5','{\"status\": \"PENDING\"}','{\"amount\": \"500.00\", \"reason\": null, \"status\": \"APPROVED\", \"wallet_transaction_id\": 28}','127.0.0.1','Hunter integration test','2026-10-17 19:57:25'),(105,8,'WITHDRAWAL_UNDER_REVIEW','withdrawals','6','{\"status\": \"PENDING\"}','{\"reason\": null, \"status\": \"UNDER_REVIEW\"}','127.0.0.1','Hunter integration test','2026-10-17 19:57:25'),(106,8,'WITHDRAWAL_APPROVED','withdrawals','6','{\"status\": \"UNDER_REVIEW\"}','{\"reason\": null, \"status\": \"APPROVED\"}','127.0.0.1','Hunter integration test','2026-10-17 19:57:25'),(107,NULL,'HUNT_ACTIVATED','hunt_activation',NULL,NULL,'{\"type\": \"ONLINE\", \"hunt_id\": 9, \"user_id\": 16, \"payment_id\": 5}','127.0.0.1','Hunter integration test','2026-10-17 19:57:25'),(108,NULL,'PAYMENT_VERIFIED','payments','5','{\"status\": \"PENDING\"}','{\"status\": \"VERIFIED\", \"provider\": \"RAZORPAY\"}','127.0.0.1','Hunter integration test','2026-10-17 19:57:25'),(109,NULL,'GIFT_REDEEMED','vendor_redemptions','RED-20261017-19286762',NULL,'{\"gift_id\": 3, \"user_id\": 15, \"vendor_id\": 5}','127.0.0.1','Hunter integration test','2026-10-17 19:57:26'),(110,7,'LOGIN','auth','7',NULL,'{\"ip\": \"127.0.0.1\"}','127.0.0.1','unknown','2026-10-17 20:12:38'),(111,7,'LOGOUT','auth','7',NULL,NULL,'127.0.0.1','unknown','2026-10-17 20:12:39'),(112,NULL,'VENDOR_LOGIN','vendor_auth','2',NULL,NULL,'127.0.0.1','unknown','2026-10-17 20:12:44'),(113,NULL,'VENDOR_LOGOUT','vendor_auth','2',NULL,NULL,'127.0.0.1','unknown','2026-10-17 20:12:44'),(114,7,'LOGIN','auth','7',NULL,'{\"ip\": \"127.0.0.1\"}','127.0.0.1','unknown','2026-10-17 20:18:16'),(115,NULL,'VENDOR_LOGIN','vendor_auth','2',NULL,NULL,'127.0.0.1','unknown','2026-10-17 20:18:16'),(116,7,'LOGIN','auth','7',NULL,'{\"ip\": \"127.0.0.1\"}','127.0.0.1','unknown','2026-10-17 20:24:34'),(117,7,'LOGIN','auth','7',NULL,'{\"ip\": \"127.0.0.1\"}','127.0.0.1','Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36','2026-10-17 20:26:46'),(118,1,'WALLET_BONUS_CREDIT','wallet','17',NULL,'{\"amount\": \"50.00\", \"reference\": \"test-bonus-84aaf380\", \"expires_at\": \"2026-10-17 22:21:24\"}','127.0.0.1','Bonus QR integration test','2026-10-17 21:21:24'),(119,1,'WALLET_PROMO_CREDIT','wallet','17',NULL,'{\"amount\": \"15.00\", \"reference\": \"test-promo-84aaf380\", \"expires_at\": \"2026-10-17 22:21:24\"}','127.0.0.1','Bonus QR integration test','2026-10-17 21:21:24'),(120,NULL,'WALLET_CHECKOUT','wallet_checkout','CHK-20261017-E6443186',NULL,'{\"user_id\": 17, \"coupon_id\": null, \"real_used\": \"75.00\", \"bonus_used\": \"10.00\", \"promo_used\": \"15.00\", \"bill_amount\": \"100.00\"}','127.0.0.1','Bonus QR integration test','2026-10-17 21:21:24'),(121,NULL,'WALLET_PROMO_EXPIRED','wallet','18',NULL,'{\"amount\": \"0.00\", \"reference\": \"test-promo-93bd279a\"}','127.0.0.1','Bonus QR integration test','2026-10-17 21:21:24'),(122,NULL,'WALLET_BONUS_EXPIRED','wallet','21',NULL,'{\"amount\": \"40.00\", \"reference\": \"test-bonus-84aaf380\"}','127.0.0.1','Bonus QR integration test','2026-10-17 21:21:24'),(123,9,'HUNT_ACTIVATED','hunt_activation','10',NULL,'{\"type\": \"COUNTER\", \"hunt_id\": 11, \"user_id\": 18, \"payment_id\": null}','127.0.0.1','Hunter integration test','2026-10-17 21:21:25'),(124,9,'REWARD_APPROVED','rewards','6','{\"status\": \"PENDING\"}','{\"amount\": \"500.00\", \"reason\": null, \"status\": \"APPROVED\", \"wallet_transaction_id\": 36}','127.0.0.1','Hunter integration test','2026-10-17 21:21:26'),(125,9,'WITHDRAWAL_UNDER_REVIEW','withdrawals','7','{\"status\": \"PENDING\"}','{\"reason\": null, \"status\": \"UNDER_REVIEW\"}','127.0.0.1','Hunter integration test','2026-10-17 21:21:26'),(126,9,'WITHDRAWAL_APPROVED','withdrawals','7','{\"status\": \"UNDER_REVIEW\"}','{\"reason\": null, \"status\": \"APPROVED\"}','127.0.0.1','Hunter integration test','2026-10-17 21:21:26'),(127,NULL,'HUNT_ACTIVATED','hunt_activation',NULL,NULL,'{\"type\": \"ONLINE\", \"hunt_id\": 11, \"user_id\": 19, \"payment_id\": 6}','127.0.0.1','Hunter integration test','2026-10-17 21:21:26'),(128,NULL,'PAYMENT_VERIFIED','payments','6','{\"status\": \"PENDING\"}','{\"status\": \"VERIFIED\", \"provider\": \"RAZORPAY\"}','127.0.0.1','Hunter integration test','2026-10-17 21:21:26'),(129,NULL,'GIFT_REDEEMED','vendor_redemptions','RED-20261017-67A54CA2',NULL,'{\"gift_id\": 4, \"user_id\": 18, \"vendor_id\": 6}','127.0.0.1','Hunter integration test','2026-10-17 21:21:26'),(130,7,'LOGIN','auth','7',NULL,'{\"ip\": \"127.0.0.1\"}','127.0.0.1','unknown','2026-10-17 21:21:31'),(131,7,'LOGOUT','auth','7',NULL,NULL,'127.0.0.1','unknown','2026-10-17 21:21:31'),(132,12,'HUNT_BROADCAST_CREATED','hunt_broadcasts','1',NULL,'{\"reward\": \"200 COINS\", \"target_id\": \"PROM_RED_DRESS_01\", \"expires_at\": \"2026-10-17 22:37:27\", \"campaign_id\": \"SPOT_4120c86a\", \"target_type\": \"PROMOTER\", \"broadcast_code\": \"BC-7F536C34\", \"validity_minutes\": 60}','127.0.0.1','Hunter integration test','2026-10-17 21:37:27'),(133,NULL,'HUNT_BROADCAST_CLAIMED','hunt_broadcast_claims','2',NULL,'{\"user_id\": 22, \"reward_type\": \"COINS\", \"broadcast_id\": 1, \"reward_amount\": \"200.00\"}','127.0.0.1','Hunter integration test','2026-10-17 21:37:28'),(134,12,'TARGETED_OFFER_CREATED','targeted_offers','1',NULL,'{\"bonus\": \"10%\", \"target_id\": \"CAT_ELEC\", \"offer_title\": \"10% on Electronics\", \"target_type\": \"CATEGORY\"}','127.0.0.1','Hunter integration test','2026-10-17 21:37:28'),(135,12,'TARGETED_OFFER_CREATED','targeted_offers','2',NULL,'{\"bonus\": \"15%\", \"target_id\": \"PROD_PHONE\", \"offer_title\": \"15% on Phone\", \"target_type\": \"PRODUCT\"}','127.0.0.1','Hunter integration test','2026-10-17 21:37:28'),(136,12,'TARGETED_OFFER_CREATED','targeted_offers','3',NULL,'{\"bonus\": \"200 INR\", \"target_id\": \"22\", \"offer_title\": \"Special VIP User Offer\", \"target_type\": \"USER\"}','127.0.0.1','Hunter integration test','2026-10-17 21:37:28'),(137,7,'LOGIN','auth','7',NULL,'{\"ip\": \"127.0.0.1\"}','127.0.0.1','unknown','2026-10-17 21:37:56'),(138,NULL,'VENDOR_LOGIN','vendor_auth','2',NULL,NULL,'127.0.0.1','unknown','2026-10-17 21:37:57'),(139,7,'LOGIN','auth','7',NULL,'{\"ip\": \"127.0.0.1\"}','127.0.0.1','unknown','2026-10-17 21:38:18'),(140,NULL,'VENDOR_LOGIN','vendor_auth','2',NULL,NULL,'127.0.0.1','unknown','2026-10-17 21:38:18'),(141,13,'HUNT_BROADCAST_CREATED','hunt_broadcasts','2',NULL,'{\"reward\": \"200 COINS\", \"target_id\": \"PROM_RED_DRESS_01\", \"expires_at\": \"2026-10-17 22:38:31\", \"campaign_id\": \"SPOT_32a00416\", \"target_type\": \"PROMOTER\", \"broadcast_code\": \"BC-18E583C6\", \"validity_minutes\": 60}','127.0.0.1','Hunter integration test','2026-10-17 21:38:31'),(142,NULL,'HUNT_BROADCAST_CLAIMED','hunt_broadcast_claims','4',NULL,'{\"user_id\": 23, \"reward_type\": \"COINS\", \"broadcast_id\": 2, \"reward_amount\": \"200.00\"}','127.0.0.1','Hunter integration test','2026-10-17 21:38:32'),(143,13,'TARGETED_OFFER_CREATED','targeted_offers','4',NULL,'{\"bonus\": \"10%\", \"target_id\": \"CAT_ELEC\", \"offer_title\": \"10% on Electronics\", \"target_type\": \"CATEGORY\"}','127.0.0.1','Hunter integration test','2026-10-17 21:38:32'),(144,13,'TARGETED_OFFER_CREATED','targeted_offers','5',NULL,'{\"bonus\": \"15%\", \"target_id\": \"PROD_PHONE\", \"offer_title\": \"15% on Phone\", \"target_type\": \"PRODUCT\"}','127.0.0.1','Hunter integration test','2026-10-17 21:38:32'),(145,13,'TARGETED_OFFER_CREATED','targeted_offers','6',NULL,'{\"bonus\": \"200 INR\", \"target_id\": \"23\", \"offer_title\": \"Special VIP User Offer\", \"target_type\": \"USER\"}','127.0.0.1','Hunter integration test','2026-10-17 21:38:32'),(146,1,'WALLET_BONUS_CREDIT','wallet','24',NULL,'{\"amount\": \"50.00\", \"reference\": \"test-bonus-eefa6591\", \"expires_at\": \"2026-10-17 22:38:32\"}','127.0.0.1','Bonus QR integration test','2026-10-17 21:38:32'),(147,1,'WALLET_PROMO_CREDIT','wallet','24',NULL,'{\"amount\": \"15.00\", \"reference\": \"test-promo-eefa6591\", \"expires_at\": \"2026-10-17 22:38:32\"}','127.0.0.1','Bonus QR integration test','2026-10-17 21:38:32'),(148,NULL,'WALLET_CHECKOUT','wallet_checkout','CHK-20261017-3B503ED1',NULL,'{\"user_id\": 24, \"coupon_id\": null, \"real_used\": \"75.00\", \"bonus_used\": \"10.00\", \"promo_used\": \"15.00\", \"bill_amount\": \"100.00\"}','127.0.0.1','Bonus QR integration test','2026-10-17 21:38:32'),(149,NULL,'WALLET_BONUS_EXPIRED','wallet','28',NULL,'{\"amount\": \"40.00\", \"reference\": \"test-bonus-eefa6591\"}','127.0.0.1','Bonus QR integration test','2026-10-17 21:38:32'),(150,14,'HUNT_ACTIVATED','hunt_activation','12',NULL,'{\"type\": \"COUNTER\", \"hunt_id\": 13, \"user_id\": 25, \"payment_id\": null}','127.0.0.1','Hunter integration test','2026-10-17 21:38:33'),(151,14,'REWARD_APPROVED','rewards','7','{\"status\": \"PENDING\"}','{\"amount\": \"500.00\", \"reason\": null, \"status\": \"APPROVED\", \"wallet_transaction_id\": 54}','127.0.0.1','Hunter integration test','2026-10-17 21:38:34'),(152,14,'WITHDRAWAL_UNDER_REVIEW','withdrawals','8','{\"status\": \"PENDING\"}','{\"reason\": null, \"status\": \"UNDER_REVIEW\"}','127.0.0.1','Hunter integration test','2026-10-17 21:38:34'),(153,14,'WITHDRAWAL_APPROVED','withdrawals','8','{\"status\": \"UNDER_REVIEW\"}','{\"reason\": null, \"status\": \"APPROVED\"}','127.0.0.1','Hunter integration test','2026-10-17 21:38:34'),(154,NULL,'HUNT_ACTIVATED','hunt_activation',NULL,NULL,'{\"type\": \"ONLINE\", \"hunt_id\": 13, \"user_id\": 26, \"payment_id\": 7}','127.0.0.1','Hunter integration test','2026-10-17 21:38:34'),(155,NULL,'PAYMENT_VERIFIED','payments','7','{\"status\": \"PENDING\"}','{\"status\": \"VERIFIED\", \"provider\": \"RAZORPAY\"}','127.0.0.1','Hunter integration test','2026-10-17 21:38:34'),(156,NULL,'GIFT_REDEEMED','vendor_redemptions','RED-20261017-99A091E0',NULL,'{\"gift_id\": 5, \"user_id\": 25, \"vendor_id\": 7}','127.0.0.1','Hunter integration test','2026-10-17 21:38:34'),(157,7,'LOGIN','auth','7',NULL,'{\"ip\": \"127.0.0.1\"}','127.0.0.1','unknown','2026-10-17 21:44:16'),(158,7,'LOGIN','auth','7',NULL,'{\"ip\": \"127.0.0.1\"}','127.0.0.1','unknown','2026-10-17 21:44:28'),(159,7,'LOGOUT','auth','7',NULL,NULL,'127.0.0.1','unknown','2026-10-17 21:44:29'),(160,7,'LOGIN','auth','7',NULL,'{\"ip\": \"127.0.0.1\"}','127.0.0.1','unknown','2026-10-17 21:45:18'),(161,7,'LOGIN','auth','7',NULL,'{\"ip\": \"127.0.0.1\"}','127.0.0.1','unknown','2026-10-17 22:51:14'),(162,15,'HUNT_BROADCAST_CREATED','hunt_broadcasts','3',NULL,'{\"reward\": \"200 COINS\", \"target_id\": \"PROM_RED_DRESS_01\", \"expires_at\": \"2026-10-17 23:51:22\", \"campaign_id\": \"SPOT_4f67df73\", \"target_type\": \"PROMOTER\", \"broadcast_code\": \"BC-1BF16619\", \"validity_minutes\": 60}','127.0.0.1','Hunter integration test','2026-10-17 22:51:22'),(163,NULL,'HUNT_BROADCAST_CLAIMED','hunt_broadcast_claims','6',NULL,'{\"user_id\": 27, \"reward_type\": \"COINS\", \"broadcast_id\": 3, \"reward_amount\": \"200.00\"}','127.0.0.1','Hunter integration test','2026-10-17 22:51:23'),(164,15,'TARGETED_OFFER_CREATED','targeted_offers','7',NULL,'{\"bonus\": \"10%\", \"target_id\": \"CAT_ELEC\", \"offer_title\": \"10% on Electronics\", \"target_type\": \"CATEGORY\"}','127.0.0.1','Hunter integration test','2026-10-17 22:51:23'),(165,15,'TARGETED_OFFER_CREATED','targeted_offers','8',NULL,'{\"bonus\": \"15%\", \"target_id\": \"PROD_PHONE\", \"offer_title\": \"15% on Phone\", \"target_type\": \"PRODUCT\"}','127.0.0.1','Hunter integration test','2026-10-17 22:51:23'),(166,15,'TARGETED_OFFER_CREATED','targeted_offers','9',NULL,'{\"bonus\": \"200 INR\", \"target_id\": \"27\", \"offer_title\": \"Special VIP User Offer\", \"target_type\": \"USER\"}','127.0.0.1','Hunter integration test','2026-10-17 22:51:23'),(167,1,'WALLET_BONUS_CREDIT','wallet','28',NULL,'{\"amount\": \"50.00\", \"reference\": \"test-bonus-c4b8a794\", \"expires_at\": \"2026-10-17 23:51:23\"}','127.0.0.1','Bonus QR integration test','2026-10-17 22:51:23'),(168,1,'WALLET_PROMO_CREDIT','wallet','28',NULL,'{\"amount\": \"15.00\", \"reference\": \"test-promo-c4b8a794\", \"expires_at\": \"2026-10-17 23:51:23\"}','127.0.0.1','Bonus QR integration test','2026-10-17 22:51:23'),(169,NULL,'WALLET_CHECKOUT','wallet_checkout','CHK-20261017-87D55E18',NULL,'{\"user_id\": 28, \"coupon_id\": null, \"real_used\": \"75.00\", \"bonus_used\": \"10.00\", \"promo_used\": \"15.00\", \"bill_amount\": \"100.00\"}','127.0.0.1','Bonus QR integration test','2026-10-17 22:51:23'),(170,NULL,'WALLET_PROMO_EXPIRED','wallet','21',NULL,'{\"amount\": \"0.00\", \"reference\": \"test-promo-84aaf380\"}','127.0.0.1','Bonus QR integration test','2026-10-17 22:51:23'),(171,NULL,'WALLET_PROMO_EXPIRED','wallet','28',NULL,'{\"amount\": \"0.00\", \"reference\": \"test-promo-eefa6591\"}','127.0.0.1','Bonus QR integration test','2026-10-17 22:51:23'),(172,NULL,'WALLET_BONUS_EXPIRED','wallet','32',NULL,'{\"amount\": \"40.00\", \"reference\": \"test-bonus-c4b8a794\"}','127.0.0.1','Bonus QR integration test','2026-10-17 22:51:23'),(173,16,'HUNT_ACTIVATED','hunt_activation','14',NULL,'{\"type\": \"COUNTER\", \"hunt_id\": 15, \"user_id\": 29, \"payment_id\": null}','127.0.0.1','Hunter integration test','2026-10-17 22:51:24'),(174,16,'REWARD_APPROVED','rewards','8','{\"status\": \"PENDING\"}','{\"amount\": \"500.00\", \"reason\": null, \"status\": \"APPROVED\", \"wallet_transaction_id\": 67}','127.0.0.1','Hunter integration test','2026-10-17 22:51:25'),(175,16,'WITHDRAWAL_UNDER_REVIEW','withdrawals','9','{\"status\": \"PENDING\"}','{\"reason\": null, \"status\": \"UNDER_REVIEW\"}','127.0.0.1','Hunter integration test','2026-10-17 22:51:25'),(176,16,'WITHDRAWAL_APPROVED','withdrawals','9','{\"status\": \"UNDER_REVIEW\"}','{\"reason\": null, \"status\": \"APPROVED\"}','127.0.0.1','Hunter integration test','2026-10-17 22:51:25'),(177,NULL,'HUNT_ACTIVATED','hunt_activation',NULL,NULL,'{\"type\": \"ONLINE\", \"hunt_id\": 15, \"user_id\": 30, \"payment_id\": 8}','127.0.0.1','Hunter integration test','2026-10-17 22:51:25'),(178,NULL,'PAYMENT_VERIFIED','payments','8','{\"status\": \"PENDING\"}','{\"status\": \"VERIFIED\", \"provider\": \"RAZORPAY\"}','127.0.0.1','Hunter integration test','2026-10-17 22:51:25'),(179,NULL,'GIFT_REDEEMED','vendor_redemptions','RED-20261017-57A6E0DC',NULL,'{\"gift_id\": 6, \"user_id\": 29, \"vendor_id\": 8}','127.0.0.1','Hunter integration test','2026-10-17 22:51:25'),(180,7,'LOGIN','auth','7',NULL,'{\"ip\": \"127.0.0.1\"}','127.0.0.1','unknown','2026-10-17 22:51:31'),(181,NULL,'VENDOR_LOGIN','vendor_auth','2',NULL,NULL,'127.0.0.1','unknown','2026-10-17 22:51:31'),(182,17,'HUNT_BROADCAST_CREATED','hunt_broadcasts','4',NULL,'{\"reward\": \"200 COINS\", \"target_id\": \"PROM_RED_DRESS_01\", \"expires_at\": \"2026-10-18 10:25:15\", \"campaign_id\": \"SPOT_2a0bf7e2\", \"target_type\": \"PROMOTER\", \"broadcast_code\": \"BC-CA3EC623\", \"validity_minutes\": 60}','127.0.0.1','Hunter integration test','2026-10-18 09:25:15'),(183,NULL,'HUNT_BROADCAST_CLAIMED','hunt_broadcast_claims','8',NULL,'{\"user_id\": 31, \"reward_type\": \"COINS\", \"broadcast_id\": 4, \"reward_amount\": \"200.00\"}','127.0.0.1','Hunter integration test','2026-10-18 09:25:16'),(184,17,'TARGETED_OFFER_CREATED','targeted_offers','10',NULL,'{\"bonus\": \"10%\", \"target_id\": \"CAT_ELEC\", \"offer_title\": \"10% on Electronics\", \"target_type\": \"CATEGORY\"}','127.0.0.1','Hunter integration test','2026-10-18 09:25:16'),(185,17,'TARGETED_OFFER_CREATED','targeted_offers','11',NULL,'{\"bonus\": \"15%\", \"target_id\": \"PROD_PHONE\", \"offer_title\": \"15% on Phone\", \"target_type\": \"PRODUCT\"}','127.0.0.1','Hunter integration test','2026-10-18 09:25:16'),(186,17,'TARGETED_OFFER_CREATED','targeted_offers','12',NULL,'{\"bonus\": \"200 INR\", \"target_id\": \"31\", \"offer_title\": \"Special VIP User Offer\", \"target_type\": \"USER\"}','127.0.0.1','Hunter integration test','2026-10-18 09:25:16'),(187,1,'WALLET_BONUS_CREDIT','wallet','32',NULL,'{\"amount\": \"50.00\", \"reference\": \"test-bonus-8d4dc97f\", \"expires_at\": \"2026-10-18 10:25:16\"}','127.0.0.1','Bonus QR integration test','2026-10-18 09:25:16'),(188,1,'WALLET_PROMO_CREDIT','wallet','32',NULL,'{\"amount\": \"15.00\", \"reference\": \"test-promo-8d4dc97f\", \"expires_at\": \"2026-10-18 10:25:16\"}','127.0.0.1','Bonus QR integration test','2026-10-18 09:25:17'),(189,NULL,'WALLET_CHECKOUT','wallet_checkout','CHK-20261018-9DD2DC45',NULL,'{\"user_id\": 32, \"coupon_id\": null, \"real_used\": \"75.00\", \"bonus_used\": \"10.00\", \"promo_used\": \"15.00\", \"bill_amount\": \"100.00\"}','127.0.0.1','Bonus QR integration test','2026-10-18 09:25:17'),(190,NULL,'WALLET_PROMO_EXPIRED','wallet','32',NULL,'{\"amount\": \"0.00\", \"reference\": \"test-promo-c4b8a794\"}','127.0.0.1','Bonus QR integration test','2026-10-18 09:25:17'),(191,NULL,'WALLET_BONUS_EXPIRED','wallet','36',NULL,'{\"amount\": \"40.00\", \"reference\": \"test-bonus-8d4dc97f\"}','127.0.0.1','Bonus QR integration test','2026-10-18 09:25:17'),(192,18,'HUNT_ACTIVATED','hunt_activation','16',NULL,'{\"type\": \"COUNTER\", \"hunt_id\": 17, \"user_id\": 33, \"payment_id\": null}','127.0.0.1','Hunter integration test','2026-10-18 09:25:18'),(193,18,'REWARD_APPROVED','rewards','9','{\"status\": \"PENDING\"}','{\"amount\": \"500.00\", \"reason\": null, \"status\": \"APPROVED\", \"wallet_transaction_id\": 80}','127.0.0.1','Hunter integration test','2026-10-18 09:25:18'),(194,18,'WITHDRAWAL_UNDER_REVIEW','withdrawals','10','{\"status\": \"PENDING\"}','{\"reason\": null, \"status\": \"UNDER_REVIEW\"}','127.0.0.1','Hunter integration test','2026-10-18 09:25:18'),(195,18,'WITHDRAWAL_APPROVED','withdrawals','10','{\"status\": \"UNDER_REVIEW\"}','{\"reason\": null, \"status\": \"APPROVED\"}','127.0.0.1','Hunter integration test','2026-10-18 09:25:18'),(196,NULL,'HUNT_ACTIVATED','hunt_activation',NULL,NULL,'{\"type\": \"ONLINE\", \"hunt_id\": 17, \"user_id\": 34, \"payment_id\": 9}','127.0.0.1','Hunter integration test','2026-10-18 09:25:18'),(197,NULL,'PAYMENT_VERIFIED','payments','9','{\"status\": \"PENDING\"}','{\"status\": \"VERIFIED\", \"provider\": \"RAZORPAY\"}','127.0.0.1','Hunter integration test','2026-10-18 09:25:18'),(198,NULL,'GIFT_REDEEMED','vendor_redemptions','RED-20261018-E7F74323',NULL,'{\"gift_id\": 7, \"user_id\": 33, \"vendor_id\": 9}','127.0.0.1','Hunter integration test','2026-10-18 09:25:19'),(199,NULL,'VENDOR_LOGIN','vendor_auth','2',NULL,NULL,'127.0.0.1','unknown','2026-10-18 09:40:00'),(200,NULL,'VENDOR_LOGOUT','vendor_auth','2',NULL,NULL,'127.0.0.1','unknown','2026-10-18 09:40:00'),(201,NULL,'VENDOR_LOGIN','vendor_auth','2',NULL,NULL,'127.0.0.1','unknown','2026-10-18 09:42:12'),(202,7,'LOGIN','auth','7',NULL,'{\"ip\": \"127.0.0.1\"}','127.0.0.1','unknown','2026-10-18 09:53:10'),(203,NULL,'VENDOR_LOGIN','vendor_auth','2',NULL,NULL,'127.0.0.1','unknown','2026-10-18 09:53:10'),(204,NULL,'VENDOR_LOGIN','vendor_auth','2',NULL,NULL,'127.0.0.1','unknown','2026-10-18 09:53:43'),(205,NULL,'VENDOR_LOGIN','vendor_auth','2',NULL,NULL,'127.0.0.1','unknown','2026-10-18 09:56:04'),(206,NULL,'VENDOR_LOGIN','vendor_auth','2',NULL,NULL,'127.0.0.1','unknown','2026-10-18 10:31:34'),(207,NULL,'VENDOR_LOGIN','vendor_auth','2',NULL,NULL,'127.0.0.1','unknown','2026-10-18 10:32:28'),(208,NULL,'VENDOR_LOGIN','vendor_auth','2',NULL,NULL,'127.0.0.1','unknown','2026-10-18 10:35:58'),(209,NULL,'VENDOR_LOGOUT','vendor_auth','2',NULL,NULL,'127.0.0.1','unknown','2026-10-18 10:35:58'),(210,NULL,'VENDOR_LOGIN','vendor_auth','2',NULL,NULL,'127.0.0.1','Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36','2026-10-18 10:44:37'),(211,NULL,'VENDOR_LOGOUT','vendor_auth','2',NULL,NULL,'127.0.0.1','Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36','2026-10-18 10:47:16');
/*!40000 ALTER TABLE `audit_logs` ENABLE KEYS */;
UNLOCK TABLES;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = utf8mb4 */ ;
/*!50003 SET character_set_results = utf8mb4 */ ;
/*!50003 SET collation_connection  = utf8mb4_unicode_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'ONLY_FULL_GROUP_BY,STRICT_TRANS_TABLES,NO_ZERO_IN_DATE,NO_ZERO_DATE,ERROR_FOR_DIVISION_BY_ZERO,NO_ENGINE_SUBSTITUTION' */ ;
DELIMITER ;;
/*!50003 CREATE*/ /*!50003 TRIGGER `audit_logs_no_update` BEFORE UPDATE ON `audit_logs` FOR EACH ROW BEGIN SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT='Audit logs are immutable'; END */;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = utf8mb4 */ ;
/*!50003 SET character_set_results = utf8mb4 */ ;
/*!50003 SET collation_connection  = utf8mb4_unicode_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'ONLY_FULL_GROUP_BY,STRICT_TRANS_TABLES,NO_ZERO_IN_DATE,NO_ZERO_DATE,ERROR_FOR_DIVISION_BY_ZERO,NO_ENGINE_SUBSTITUTION' */ ;
DELIMITER ;;
/*!50003 CREATE*/ /*!50003 TRIGGER `audit_logs_no_delete` BEFORE DELETE ON `audit_logs` FOR EACH ROW BEGIN SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT='Audit logs are immutable'; END */;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;

--
-- Table structure for table `category_broadcasts`
--

DROP TABLE IF EXISTS `category_broadcasts`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `category_broadcasts` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `category_id` bigint unsigned NOT NULL,
  `topic` varchar(120) COLLATE utf8mb4_unicode_ci NOT NULL,
  `message` varchar(500) COLLATE utf8mb4_unicode_ci NOT NULL,
  `status` enum('QUEUED','SENT','FAILED') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'QUEUED',
  `provider_reference` varchar(190) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_by` int unsigned NOT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `sent_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `created_by` (`created_by`),
  KEY `idx_category_broadcast` (`category_id`,`status`,`created_at`),
  CONSTRAINT `category_broadcasts_ibfk_1` FOREIGN KEY (`category_id`) REFERENCES `video_categories` (`id`),
  CONSTRAINT `category_broadcasts_ibfk_2` FOREIGN KEY (`created_by`) REFERENCES `admins` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `category_broadcasts`
--

LOCK TABLES `category_broadcasts` WRITE;
/*!40000 ALTER TABLE `category_broadcasts` DISABLE KEYS */;
INSERT INTO `category_broadcasts` VALUES (1,3,'category_3','Demo notice: hidden reward spots are live!','QUEUED',NULL,2,'2026-08-29 12:44:33',NULL);
/*!40000 ALTER TABLE `category_broadcasts` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `coupons`
--

DROP TABLE IF EXISTS `coupons`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `coupons` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `code_hash` char(64) COLLATE utf8mb4_unicode_ci NOT NULL,
  `code_reference` varchar(80) COLLATE utf8mb4_unicode_ci NOT NULL,
  `title` varchar(190) COLLATE utf8mb4_unicode_ci NOT NULL,
  `discount_amount` decimal(18,2) NOT NULL,
  `min_cart_amount` decimal(18,2) NOT NULL DEFAULT '0.00',
  `merchant_id` bigint unsigned DEFAULT NULL,
  `ttl_minutes` int unsigned DEFAULT NULL,
  `expires_at` datetime DEFAULT NULL,
  `status` enum('ACTIVE','USED','EXPIRED','CANCELLED') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'ACTIVE',
  `user_id` bigint unsigned DEFAULT NULL,
  `used_at` datetime DEFAULT NULL,
  `created_by` int unsigned NOT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `code_hash` (`code_hash`),
  UNIQUE KEY `code_reference` (`code_reference`),
  KEY `merchant_id` (`merchant_id`),
  KEY `user_id` (`user_id`),
  KEY `created_by` (`created_by`),
  KEY `idx_coupon_status` (`status`),
  CONSTRAINT `coupons_ibfk_1` FOREIGN KEY (`merchant_id`) REFERENCES `vendors` (`id`),
  CONSTRAINT `coupons_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`),
  CONSTRAINT `coupons_ibfk_3` FOREIGN KEY (`created_by`) REFERENCES `admins` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `coupons`
--

LOCK TABLES `coupons` WRITE;
/*!40000 ALTER TABLE `coupons` DISABLE KEYS */;
INSERT INTO `coupons` VALUES (1,'ab85e467e4496fa12da0a3d2c8ab035dc1386723c3bcf91c5b9d47edb3952a23','DEMO-COUPON','Demo ₹100 off',100.00,300.00,2,30,'2026-09-28 11:29:07','ACTIVE',1,NULL,1,'2026-08-29 11:29:07');
/*!40000 ALTER TABLE `coupons` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `finance_admin_security`
--

DROP TABLE IF EXISTS `finance_admin_security`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `finance_admin_security` (
  `admin_id` int unsigned NOT NULL,
  `pin_hash` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `failures` int unsigned NOT NULL DEFAULT '0',
  `locked_until` datetime DEFAULT NULL,
  `last_otp_hash` char(64) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `last_otp_at` datetime DEFAULT NULL,
  PRIMARY KEY (`admin_id`),
  CONSTRAINT `finance_admin_security_ibfk_1` FOREIGN KEY (`admin_id`) REFERENCES `admins` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `finance_admin_security`
--

LOCK TABLES `finance_admin_security` WRITE;
/*!40000 ALTER TABLE `finance_admin_security` DISABLE KEYS */;
/*!40000 ALTER TABLE `finance_admin_security` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `finance_master_account`
--

DROP TABLE IF EXISTS `finance_master_account`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `finance_master_account` (
  `id` tinyint unsigned NOT NULL,
  `balance` decimal(18,2) NOT NULL DEFAULT '0.00',
  `updated_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  CONSTRAINT `finance_master_account_chk_1` CHECK ((`balance` >= 0))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `finance_master_account`
--

LOCK TABLES `finance_master_account` WRITE;
/*!40000 ALTER TABLE `finance_master_account` DISABLE KEYS */;
INSERT INTO `finance_master_account` VALUES (1,0.00,'2026-09-13 11:13:56');
/*!40000 ALTER TABLE `finance_master_account` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `finance_master_entries`
--

DROP TABLE IF EXISTS `finance_master_entries`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `finance_master_entries` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `transaction_id` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `operation` enum('FUND','DISTRIBUTE','RECALL') COLLATE utf8mb4_unicode_ci NOT NULL,
  `target` enum('MASTER','VENDOR','REAL','BONUS') COLLATE utf8mb4_unicode_ci NOT NULL,
  `recipient_id` bigint unsigned DEFAULT NULL,
  `amount` decimal(18,2) NOT NULL,
  `previous_balance` decimal(18,2) NOT NULL,
  `new_balance` decimal(18,2) NOT NULL,
  `recipient_before` decimal(18,2) DEFAULT NULL,
  `recipient_after` decimal(18,2) DEFAULT NULL,
  `reference` varchar(120) COLLATE utf8mb4_unicode_ci NOT NULL,
  `funding_reference` varchar(120) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `reason` varchar(500) COLLATE utf8mb4_unicode_ci NOT NULL,
  `idempotency_key` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `admin_id` int unsigned NOT NULL,
  `ip_address` varchar(45) COLLATE utf8mb4_unicode_ci NOT NULL,
  `user_agent` varchar(500) COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `transaction_id` (`transaction_id`),
  UNIQUE KEY `idempotency_key` (`idempotency_key`),
  UNIQUE KEY `funding_reference` (`funding_reference`),
  KEY `admin_id` (`admin_id`),
  CONSTRAINT `finance_master_entries_ibfk_1` FOREIGN KEY (`admin_id`) REFERENCES `admins` (`id`),
  CONSTRAINT `finance_master_entries_chk_1` CHECK ((`amount` > 0)),
  CONSTRAINT `finance_master_entries_chk_2` CHECK ((`new_balance` >= 0))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `finance_master_entries`
--

LOCK TABLES `finance_master_entries` WRITE;
/*!40000 ALTER TABLE `finance_master_entries` DISABLE KEYS */;
/*!40000 ALTER TABLE `finance_master_entries` ENABLE KEYS */;
UNLOCK TABLES;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = utf8mb4 */ ;
/*!50003 SET character_set_results = utf8mb4 */ ;
/*!50003 SET collation_connection  = utf8mb4_unicode_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'STRICT_ALL_TABLES,NO_ENGINE_SUBSTITUTION' */ ;
DELIMITER ;;
/*!50003 CREATE*/ /*!50003 TRIGGER `finance_master_no_update` BEFORE UPDATE ON `finance_master_entries` FOR EACH ROW BEGIN SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT='Master ledger is append-only'; END */;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = utf8mb4 */ ;
/*!50003 SET character_set_results = utf8mb4 */ ;
/*!50003 SET collation_connection  = utf8mb4_unicode_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'STRICT_ALL_TABLES,NO_ENGINE_SUBSTITUTION' */ ;
DELIMITER ;;
/*!50003 CREATE*/ /*!50003 TRIGGER `finance_master_no_delete` BEFORE DELETE ON `finance_master_entries` FOR EACH ROW BEGIN SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT='Master ledger is append-only'; END */;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;

--
-- Table structure for table `finance_payout_records`
--

DROP TABLE IF EXISTS `finance_payout_records`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `finance_payout_records` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `kind` enum('CUSTOMER','VENDOR') COLLATE utf8mb4_unicode_ci NOT NULL,
  `request_id` bigint unsigned NOT NULL,
  `reference` varchar(120) COLLATE utf8mb4_unicode_ci NOT NULL,
  `note` varchar(500) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `admin_id` int unsigned NOT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `reference` (`reference`),
  UNIQUE KEY `uq_finance_payout` (`kind`,`request_id`),
  KEY `admin_id` (`admin_id`),
  CONSTRAINT `finance_payout_records_ibfk_1` FOREIGN KEY (`admin_id`) REFERENCES `admins` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `finance_payout_records`
--

LOCK TABLES `finance_payout_records` WRITE;
/*!40000 ALTER TABLE `finance_payout_records` DISABLE KEYS */;
/*!40000 ALTER TABLE `finance_payout_records` ENABLE KEYS */;
UNLOCK TABLES;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = utf8mb4 */ ;
/*!50003 SET character_set_results = utf8mb4 */ ;
/*!50003 SET collation_connection  = utf8mb4_unicode_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'STRICT_ALL_TABLES,NO_ENGINE_SUBSTITUTION' */ ;
DELIMITER ;;
/*!50003 CREATE*/ /*!50003 TRIGGER `finance_payout_no_update` BEFORE UPDATE ON `finance_payout_records` FOR EACH ROW BEGIN SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT='Payout references are append-only'; END */;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = utf8mb4 */ ;
/*!50003 SET character_set_results = utf8mb4 */ ;
/*!50003 SET collation_connection  = utf8mb4_unicode_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'STRICT_ALL_TABLES,NO_ENGINE_SUBSTITUTION' */ ;
DELIMITER ;;
/*!50003 CREATE*/ /*!50003 TRIGGER `finance_payout_no_delete` BEFORE DELETE ON `finance_payout_records` FOR EACH ROW BEGIN SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT='Payout references are append-only'; END */;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;

--
-- Table structure for table `finance_vendor_debits`
--

DROP TABLE IF EXISTS `finance_vendor_debits`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `finance_vendor_debits` (
  `request_id` bigint unsigned NOT NULL,
  `vendor_id` bigint unsigned NOT NULL,
  `amount` decimal(18,2) NOT NULL,
  `previous_balance` decimal(18,2) NOT NULL,
  `new_balance` decimal(18,2) NOT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`request_id`),
  KEY `vendor_id` (`vendor_id`),
  CONSTRAINT `finance_vendor_debits_ibfk_1` FOREIGN KEY (`request_id`) REFERENCES `vendor_payout_requests` (`id`),
  CONSTRAINT `finance_vendor_debits_ibfk_2` FOREIGN KEY (`vendor_id`) REFERENCES `vendors` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `finance_vendor_debits`
--

LOCK TABLES `finance_vendor_debits` WRITE;
/*!40000 ALTER TABLE `finance_vendor_debits` DISABLE KEYS */;
/*!40000 ALTER TABLE `finance_vendor_debits` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `finance_vendor_payout_events`
--

DROP TABLE IF EXISTS `finance_vendor_payout_events`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `finance_vendor_payout_events` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `request_id` bigint unsigned NOT NULL,
  `old_status` varchar(30) COLLATE utf8mb4_unicode_ci NOT NULL,
  `new_status` varchar(30) COLLATE utf8mb4_unicode_ci NOT NULL,
  `note` varchar(500) COLLATE utf8mb4_unicode_ci NOT NULL,
  `admin_id` int unsigned NOT NULL,
  `ip_address` varchar(45) COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `request_id` (`request_id`),
  KEY `admin_id` (`admin_id`),
  CONSTRAINT `finance_vendor_payout_events_ibfk_1` FOREIGN KEY (`request_id`) REFERENCES `vendor_payout_requests` (`id`),
  CONSTRAINT `finance_vendor_payout_events_ibfk_2` FOREIGN KEY (`admin_id`) REFERENCES `admins` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `finance_vendor_payout_events`
--

LOCK TABLES `finance_vendor_payout_events` WRITE;
/*!40000 ALTER TABLE `finance_vendor_payout_events` DISABLE KEYS */;
/*!40000 ALTER TABLE `finance_vendor_payout_events` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `gift_redemption_attempts`
--

DROP TABLE IF EXISTS `gift_redemption_attempts`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `gift_redemption_attempts` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `vendor_id` bigint unsigned DEFAULT NULL,
  `vendor_user_id` bigint unsigned DEFAULT NULL,
  `code_reference` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `code_hash` char(64) COLLATE utf8mb4_unicode_ci NOT NULL,
  `ip_address` varchar(45) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `result` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `reason` varchar(500) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `attempted_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_redemption_attempt_date` (`vendor_id`,`attempted_at`),
  KEY `vendor_user_id` (`vendor_user_id`),
  CONSTRAINT `gift_redemption_attempts_ibfk_1` FOREIGN KEY (`vendor_id`) REFERENCES `vendors` (`id`),
  CONSTRAINT `gift_redemption_attempts_ibfk_2` FOREIGN KEY (`vendor_user_id`) REFERENCES `vendor_users` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=22 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `gift_redemption_attempts`
--

LOCK TABLES `gift_redemption_attempts` WRITE;
/*!40000 ALTER TABLE `gift_redemption_attempts` DISABLE KEYS */;
INSERT INTO `gift_redemption_attempts` VALUES (1,1,1,'GFT-VALID','6aede01191b0eeecebceb0e169f2ef12e930648fbabed460bb6c21564fd513be','127.0.0.1','REDEEMED',NULL,'2026-08-23 19:08:23'),(2,1,1,'GFT-VALID','6aede01191b0eeecebceb0e169f2ef12e930648fbabed460bb6c21564fd513be','127.0.0.1','REJECTED','Code is not active.','2026-08-23 19:08:23'),(3,1,1,'GFT-EXPIRED','b36bd8ba65928f5db9f879878cd37d0fd9753ef32962876208a3a6a3e54d0e76','127.0.0.1','REJECTED','Code has expired.','2026-08-23 19:08:23'),(4,4,3,'GFT-VAL-1BAC2A6B','4c881a8cd62d77eb868e23ef3ea1bc4910b10b54e4e240f656dcee24306c52a3','127.0.0.1','REDEEMED',NULL,'2026-10-17 18:38:58'),(5,4,3,'GFT-VAL-1BAC2A6B','4c881a8cd62d77eb868e23ef3ea1bc4910b10b54e4e240f656dcee24306c52a3','127.0.0.1','REJECTED','Code is not active.','2026-10-17 18:38:58'),(6,4,3,'GFT-EXP-1BAC2A6B','307a9d128f34cce09b18172002a4e273c3e1a70254d8325bc7cca0bba30e7e99','127.0.0.1','REJECTED','Code has expired.','2026-10-17 18:38:58'),(7,5,4,'GFT-VAL-97790886','19135d650ac346d0f2207fce3b4c46a53a13fd8435f7739de1535d30b84ddb6a','127.0.0.1','REDEEMED',NULL,'2026-10-17 19:57:26'),(8,5,4,'GFT-VAL-97790886','19135d650ac346d0f2207fce3b4c46a53a13fd8435f7739de1535d30b84ddb6a','127.0.0.1','REJECTED','Code is not active.','2026-10-17 19:57:26'),(9,5,4,'GFT-EXP-97790886','450d0508dfe5cb912d2d256ca6c5329bd97006e73983e13cdce3406aa18a2bc7','127.0.0.1','REJECTED','Code has expired.','2026-10-17 19:57:26'),(10,6,5,'GFT-VAL-6415B496','c8a347e37257a3ad19262be976cd472c305755ca673425a06f259b7199e8ba71','127.0.0.1','REDEEMED',NULL,'2026-10-17 21:21:26'),(11,6,5,'GFT-VAL-6415B496','c8a347e37257a3ad19262be976cd472c305755ca673425a06f259b7199e8ba71','127.0.0.1','REJECTED','Code is not active.','2026-10-17 21:21:26'),(12,6,5,'GFT-EXP-6415B496','f8678842558b61460e5524845c553fdea9bedf07d058b56a424a5ee43a05bb32','127.0.0.1','REJECTED','Code has expired.','2026-10-17 21:21:26'),(13,7,6,'GFT-VAL-D1411073','810dbeeff86390eeae0442d1fc9942829b679cf363d191d569e3ebc407525ca5','127.0.0.1','REDEEMED',NULL,'2026-10-17 21:38:34'),(14,7,6,'GFT-VAL-D1411073','810dbeeff86390eeae0442d1fc9942829b679cf363d191d569e3ebc407525ca5','127.0.0.1','REJECTED','Code is not active.','2026-10-17 21:38:34'),(15,7,6,'GFT-EXP-D1411073','9426b6d8e22a1c21b9826be1b10bbdb3f8ef05a02adb7f29c5e7462cf7f39a85','127.0.0.1','REJECTED','Code has expired.','2026-10-17 21:38:34'),(16,8,7,'GFT-VAL-3439D2E2','ea5adf3afa19b336c8f9c27bdffa3c0776965dd459429918dfdd39c1f21604df','127.0.0.1','REDEEMED',NULL,'2026-10-17 22:51:25'),(17,8,7,'GFT-VAL-3439D2E2','ea5adf3afa19b336c8f9c27bdffa3c0776965dd459429918dfdd39c1f21604df','127.0.0.1','REJECTED','Code is not active.','2026-10-17 22:51:25'),(18,8,7,'GFT-EXP-3439D2E2','c5f5c29856df7e43341cd7d8817b1b036c13b5cb9061b6d476cc61ff2869ed2a','127.0.0.1','REJECTED','Code has expired.','2026-10-17 22:51:25'),(19,9,8,'GFT-VAL-86F7090E','07a8f5fa36eb368eadfdca03f35bcb06ebc0fa40fa33003240ff695b0996b1d1','127.0.0.1','REDEEMED',NULL,'2026-10-18 09:25:19'),(20,9,8,'GFT-VAL-86F7090E','07a8f5fa36eb368eadfdca03f35bcb06ebc0fa40fa33003240ff695b0996b1d1','127.0.0.1','REJECTED','Code is not active.','2026-10-18 09:25:19'),(21,9,8,'GFT-EXP-86F7090E','c7ed0d983ae0e937a595e79d942c8da514ad7b357109078bf7bcf6ff8c34a06a','127.0.0.1','REJECTED','Code has expired.','2026-10-18 09:25:19');
/*!40000 ALTER TABLE `gift_redemption_attempts` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `gift_redemptions`
--

DROP TABLE IF EXISTS `gift_redemptions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `gift_redemptions` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `redemption_id` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `reward_code_id` bigint unsigned NOT NULL,
  `gift_id` bigint unsigned NOT NULL,
  `user_id` bigint unsigned NOT NULL,
  `vendor_id` bigint unsigned NOT NULL,
  `vendor_user_id` bigint unsigned NOT NULL,
  `redeemed_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `redemption_id` (`redemption_id`),
  UNIQUE KEY `reward_code_id` (`reward_code_id`),
  KEY `idx_redemption_vendor_date` (`vendor_id`,`redeemed_at`),
  KEY `gift_id` (`gift_id`),
  KEY `user_id` (`user_id`),
  KEY `vendor_user_id` (`vendor_user_id`),
  CONSTRAINT `gift_redemptions_ibfk_1` FOREIGN KEY (`reward_code_id`) REFERENCES `reward_codes` (`id`),
  CONSTRAINT `gift_redemptions_ibfk_2` FOREIGN KEY (`gift_id`) REFERENCES `gifts` (`id`),
  CONSTRAINT `gift_redemptions_ibfk_3` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`),
  CONSTRAINT `gift_redemptions_ibfk_4` FOREIGN KEY (`vendor_id`) REFERENCES `vendors` (`id`),
  CONSTRAINT `gift_redemptions_ibfk_5` FOREIGN KEY (`vendor_user_id`) REFERENCES `vendor_users` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=8 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `gift_redemptions`
--

LOCK TABLES `gift_redemptions` WRITE;
/*!40000 ALTER TABLE `gift_redemptions` DISABLE KEYS */;
INSERT INTO `gift_redemptions` VALUES (1,'RED-20260823-CC1584DD',1,1,1,1,1,'2026-08-23 19:08:23'),(2,'RED-20261017-18929A1C',3,2,11,4,3,'2026-10-17 18:38:58'),(3,'RED-20261017-19286762',5,3,15,5,4,'2026-10-17 19:57:26'),(4,'RED-20261017-67A54CA2',7,4,18,6,5,'2026-10-17 21:21:26'),(5,'RED-20261017-99A091E0',9,5,25,7,6,'2026-10-17 21:38:34'),(6,'RED-20261017-57A6E0DC',11,6,29,8,7,'2026-10-17 22:51:25'),(7,'RED-20261018-E7F74323',13,7,33,9,8,'2026-10-18 09:25:19');
/*!40000 ALTER TABLE `gift_redemptions` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `gifts`
--

DROP TABLE IF EXISTS `gifts`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `gifts` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(190) COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` text COLLATE utf8mb4_unicode_ci,
  `image_path` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `campaign` varchar(190) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `total_quantity` int unsigned NOT NULL,
  `redeemed_quantity` int unsigned NOT NULL DEFAULT '0',
  `valid_from` datetime DEFAULT NULL,
  `valid_until` datetime DEFAULT NULL,
  `status` enum('DRAFT','ACTIVE','INACTIVE','EXPIRED') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'DRAFT',
  `created_by` int unsigned NOT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_gift_status_dates` (`status`,`valid_from`,`valid_until`),
  KEY `created_by` (`created_by`),
  CONSTRAINT `gifts_ibfk_1` FOREIGN KEY (`created_by`) REFERENCES `admins` (`id`),
  CONSTRAINT `gifts_chk_1` CHECK ((`redeemed_quantity` <= `total_quantity`))
) ENGINE=InnoDB AUTO_INCREMENT=8 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `gifts`
--

LOCK TABLES `gifts` WRITE;
/*!40000 ALTER TABLE `gifts` DISABLE KEYS */;
INSERT INTO `gifts` VALUES (1,'Test Gift',NULL,NULL,NULL,2,1,'2026-08-22 19:08:23','2026-08-24 19:08:23','ACTIVE',1,'2026-08-23 19:08:23','2026-08-23 19:08:23'),(2,'Test Gift',NULL,NULL,NULL,2,1,'2026-10-16 18:38:58','2026-10-18 18:38:58','ACTIVE',6,'2026-10-17 18:38:58','2026-10-17 18:38:58'),(3,'Test Gift',NULL,NULL,NULL,2,1,'2026-10-16 19:57:26','2026-10-18 19:57:26','ACTIVE',8,'2026-10-17 19:57:26','2026-10-17 19:57:26'),(4,'Test Gift',NULL,NULL,NULL,2,1,'2026-10-16 21:21:26','2026-10-18 21:21:26','ACTIVE',9,'2026-10-17 21:21:26','2026-10-17 21:21:26'),(5,'Test Gift',NULL,NULL,NULL,2,1,'2026-10-16 21:38:34','2026-10-18 21:38:34','ACTIVE',14,'2026-10-17 21:38:34','2026-10-17 21:38:34'),(6,'Test Gift',NULL,NULL,NULL,2,1,'2026-10-16 22:51:25','2026-10-18 22:51:25','ACTIVE',16,'2026-10-17 22:51:25','2026-10-17 22:51:25'),(7,'Test Gift',NULL,NULL,NULL,2,1,'2026-10-17 09:25:19','2026-10-19 09:25:19','ACTIVE',18,'2026-10-18 09:25:19','2026-10-18 09:25:19');
/*!40000 ALTER TABLE `gifts` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `hunt_broadcast_claims`
--

DROP TABLE IF EXISTS `hunt_broadcast_claims`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `hunt_broadcast_claims` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `broadcast_id` bigint unsigned NOT NULL,
  `user_id` bigint unsigned NOT NULL,
  `qr_token_scanned` varchar(64) COLLATE utf8mb4_unicode_ci NOT NULL,
  `pin_verified` tinyint(1) NOT NULL DEFAULT '0',
  `reward_type` varchar(32) COLLATE utf8mb4_unicode_ci NOT NULL,
  `reward_amount` decimal(18,2) NOT NULL DEFAULT '0.00',
  `status` enum('SUCCESS','INVALID_PIN','EXPIRED','DUPLICATE','REJECTED') COLLATE utf8mb4_unicode_ci NOT NULL,
  `idempotency_key` varchar(128) COLLATE utf8mb4_unicode_ci NOT NULL,
  `claimed_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `idempotency_key` (`idempotency_key`),
  KEY `broadcast_id` (`broadcast_id`),
  KEY `idx_hbc_user` (`user_id`),
  KEY `idx_hbc_token` (`qr_token_scanned`),
  CONSTRAINT `hunt_broadcast_claims_ibfk_1` FOREIGN KEY (`broadcast_id`) REFERENCES `hunt_broadcasts` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=9 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `hunt_broadcast_claims`
--

LOCK TABLES `hunt_broadcast_claims` WRITE;
/*!40000 ALTER TABLE `hunt_broadcast_claims` DISABLE KEYS */;
INSERT INTO `hunt_broadcast_claims` VALUES (1,1,22,'HBQR_bdb83edba1023510f47e839f130efc8f7b6574ec282006d7',0,'COINS',0.00,'INVALID_PIN','claim-wrong-pin:fail:fe79a718','2026-10-17 21:37:28'),(2,1,22,'HBQR_bdb83edba1023510f47e839f130efc8f7b6574ec282006d7',1,'COINS',200.00,'SUCCESS','claim-valid-4120c86a','2026-10-17 21:37:28'),(3,2,23,'HBQR_828df387039c1ec525bbde3af12b933dfcb34e23cdadb801',0,'COINS',0.00,'INVALID_PIN','claim-wrong-pin:fail:04ee4fe4','2026-10-17 21:38:32'),(4,2,23,'HBQR_828df387039c1ec525bbde3af12b933dfcb34e23cdadb801',1,'COINS',200.00,'SUCCESS','claim-valid-32a00416','2026-10-17 21:38:32'),(5,3,27,'HBQR_67b047a72a2c2fc3f6a365a0a53b5debb1d4728b554b316a',0,'COINS',0.00,'INVALID_PIN','claim-wrong-pin:fail:42e4918f','2026-10-17 22:51:23'),(6,3,27,'HBQR_67b047a72a2c2fc3f6a365a0a53b5debb1d4728b554b316a',1,'COINS',200.00,'SUCCESS','claim-valid-4f67df73','2026-10-17 22:51:23'),(7,4,31,'HBQR_b781df148b0b3260b9eee80c1abe9bb67a8f48ffb0ac6165',0,'COINS',0.00,'INVALID_PIN','claim-wrong-pin:fail:a242f5ed','2026-10-18 09:25:16'),(8,4,31,'HBQR_b781df148b0b3260b9eee80c1abe9bb67a8f48ffb0ac6165',1,'COINS',200.00,'SUCCESS','claim-valid-2a0bf7e2','2026-10-18 09:25:16');
/*!40000 ALTER TABLE `hunt_broadcast_claims` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `hunt_broadcasts`
--

DROP TABLE IF EXISTS `hunt_broadcasts`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `hunt_broadcasts` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `broadcast_code` varchar(64) COLLATE utf8mb4_unicode_ci NOT NULL,
  `campaign_id` varchar(64) COLLATE utf8mb4_unicode_ci NOT NULL,
  `target_type` enum('PROMOTER','VENDOR','SHOP','CUSTOMER') COLLATE utf8mb4_unicode_ci NOT NULL,
  `target_id` varchar(120) COLLATE utf8mb4_unicode_ci NOT NULL,
  `message` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `reward_type` enum('COINS','BONUS_CASH','COUPON') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'COINS',
  `reward_value` decimal(18,2) NOT NULL DEFAULT '0.00',
  `validity_window_minutes` int unsigned NOT NULL DEFAULT '60',
  `starts_at` datetime NOT NULL,
  `expires_at` datetime NOT NULL,
  `pin_hash` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `qr_token` varchar(64) COLLATE utf8mb4_unicode_ci NOT NULL,
  `status` enum('DRAFT','ACTIVE','EXPIRED','CANCELLED','FAILED') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'ACTIVE',
  `fcm_status` enum('QUEUED','SENT','FAILED') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'SENT',
  `fcm_response` json DEFAULT NULL,
  `created_by` int unsigned NOT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `broadcast_code` (`broadcast_code`),
  UNIQUE KEY `qr_token` (`qr_token`),
  KEY `idx_hb_campaign` (`campaign_id`),
  KEY `idx_hb_status` (`status`,`expires_at`),
  KEY `idx_hb_qr` (`qr_token`)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `hunt_broadcasts`
--

LOCK TABLES `hunt_broadcasts` WRITE;
/*!40000 ALTER TABLE `hunt_broadcasts` DISABLE KEYS */;
INSERT INTO `hunt_broadcasts` VALUES (1,'BC-7F536C34','SPOT_4120c86a','PROMOTER','PROM_RED_DRESS_01','Red dress wali ladki ke paas QR code hai, jao aur 200 points jeeto!','COINS',200.00,60,'2026-10-17 21:37:27','2026-10-17 22:37:27','$2y$12$YOIQvpX528WnJ/EIpbNiPeEouAFDJQWzBvB7xoulHPW2HKygw49Uu','HBQR_bdb83edba1023510f47e839f130efc8f7b6574ec282006d7','ACTIVE','SENT','{\"body\": \"Red dress wali ladki ke paas QR code hai, jao aur 200 points jeeto!\", \"title\": \"Hunter Live Hunt Alert!\", \"reward\": \"200 COINS\", \"expires_at\": \"2026-10-17 22:37:27\", \"target_type\": \"PROMOTER\", \"dispatched_at\": \"2026-10-17T21:37:27+05:30\", \"broadcast_code\": \"BC-7F536C34\", \"validity_minutes\": 60}',12,'2026-10-17 21:37:27'),(2,'BC-18E583C6','SPOT_32a00416','PROMOTER','PROM_RED_DRESS_01','Red dress wali ladki ke paas QR code hai, jao aur 200 points jeeto!','COINS',200.00,60,'2026-10-17 21:38:31','2026-10-17 22:38:31','$2y$12$ozNvuHG9H7..EvYHcRH2.eK.Cw3EeMPAxyC7tzMxRJf6vprQa3eIK','HBQR_828df387039c1ec525bbde3af12b933dfcb34e23cdadb801','ACTIVE','SENT','{\"body\": \"Red dress wali ladki ke paas QR code hai, jao aur 200 points jeeto!\", \"title\": \"Hunter Live Hunt Alert!\", \"reward\": \"200 COINS\", \"expires_at\": \"2026-10-17 22:38:31\", \"target_type\": \"PROMOTER\", \"dispatched_at\": \"2026-10-17T21:38:31+05:30\", \"broadcast_code\": \"BC-18E583C6\", \"validity_minutes\": 60}',13,'2026-10-17 21:38:31'),(3,'BC-1BF16619','SPOT_4f67df73','PROMOTER','PROM_RED_DRESS_01','Red dress wali ladki ke paas QR code hai, jao aur 200 points jeeto!','COINS',200.00,60,'2026-10-17 22:51:22','2026-10-17 23:51:22','$2y$12$HmLDnxfhFu21uf9gdCwkieW4suUpeqVIwalNvjMmkWB5JSx5ciJTa','HBQR_67b047a72a2c2fc3f6a365a0a53b5debb1d4728b554b316a','ACTIVE','SENT','{\"body\": \"Red dress wali ladki ke paas QR code hai, jao aur 200 points jeeto!\", \"title\": \"Hunter Live Hunt Alert!\", \"reward\": \"200 COINS\", \"expires_at\": \"2026-10-17 23:51:22\", \"target_type\": \"PROMOTER\", \"dispatched_at\": \"2026-10-17T22:51:22+05:30\", \"broadcast_code\": \"BC-1BF16619\", \"validity_minutes\": 60}',15,'2026-10-17 22:51:22'),(4,'BC-CA3EC623','SPOT_2a0bf7e2','PROMOTER','PROM_RED_DRESS_01','Red dress wali ladki ke paas QR code hai, jao aur 200 points jeeto!','COINS',200.00,60,'2026-10-18 09:25:15','2026-10-18 10:25:15','$2y$12$IGwMvKJkWi5J1da52wvm0eYAFGyZ8TUQPt6WD9X9i.XIWzCck1uR2','HBQR_b781df148b0b3260b9eee80c1abe9bb67a8f48ffb0ac6165','ACTIVE','SENT','{\"body\": \"Red dress wali ladki ke paas QR code hai, jao aur 200 points jeeto!\", \"title\": \"Hunter Live Hunt Alert!\", \"reward\": \"200 COINS\", \"expires_at\": \"2026-10-18 10:25:15\", \"target_type\": \"PROMOTER\", \"dispatched_at\": \"2026-10-18T09:25:15+05:30\", \"broadcast_code\": \"BC-CA3EC623\", \"validity_minutes\": 60}',17,'2026-10-18 09:25:15');
/*!40000 ALTER TABLE `hunt_broadcasts` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `hunt_challenge_attempts`
--

DROP TABLE IF EXISTS `hunt_challenge_attempts`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `hunt_challenge_attempts` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `scan_id` bigint unsigned NOT NULL,
  `user_id` bigint unsigned NOT NULL,
  `challenge_type` enum('SECRET','QUESTION') COLLATE utf8mb4_unicode_ci NOT NULL,
  `challenge_id` bigint unsigned NOT NULL,
  `successful` tinyint(1) NOT NULL,
  `answer_hash` char(64) COLLATE utf8mb4_unicode_ci NOT NULL,
  `ip_address` varchar(45) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `device_id` varchar(190) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `attempted_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_challenge_rate` (`user_id`,`challenge_type`,`attempted_at`),
  KEY `idx_challenge_success` (`scan_id`,`challenge_type`,`challenge_id`,`successful`),
  CONSTRAINT `hunt_challenge_attempts_ibfk_1` FOREIGN KEY (`scan_id`) REFERENCES `hunt_scans` (`id`),
  CONSTRAINT `hunt_challenge_attempts_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=37 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `hunt_challenge_attempts`
--

LOCK TABLES `hunt_challenge_attempts` WRITE;
/*!40000 ALTER TABLE `hunt_challenge_attempts` DISABLE KEYS */;
INSERT INTO `hunt_challenge_attempts` VALUES (1,1,1,'SECRET',0,0,'8810ad581e59f2bc3928b261707a71308f7e139eb04820366dc4d5c18d980225','127.0.0.1','device-1','2026-08-23 19:08:22'),(2,1,1,'SECRET',1,1,'962411f188765b8d7ccee697298e15184e524a54a3d58f1124ecd92d0c7702b3','127.0.0.1','device-1','2026-08-23 19:08:23'),(3,1,1,'QUESTION',1,0,'b289f80a9a07f8f89930f70cae5317b6179a3b42dd825cec68d978657f8900d3','127.0.0.1','device-1','2026-08-23 19:08:23'),(4,1,1,'QUESTION',1,1,'6b465516e7221fc65d4c48330a398de95ce1b11089f185d2e4874499ae96f1c8','127.0.0.1','device-1','2026-08-23 19:08:23'),(5,2,7,'SECRET',0,0,'8810ad581e59f2bc3928b261707a71308f7e139eb04820366dc4d5c18d980225','127.0.0.1','device-7','2026-10-17 18:37:52'),(6,2,7,'SECRET',2,1,'962411f188765b8d7ccee697298e15184e524a54a3d58f1124ecd92d0c7702b3','127.0.0.1','device-7','2026-10-17 18:37:52'),(7,2,7,'QUESTION',2,0,'b289f80a9a07f8f89930f70cae5317b6179a3b42dd825cec68d978657f8900d3','127.0.0.1','device-7','2026-10-17 18:37:52'),(8,2,7,'QUESTION',2,1,'6b465516e7221fc65d4c48330a398de95ce1b11089f185d2e4874499ae96f1c8','127.0.0.1','device-7','2026-10-17 18:37:52'),(9,3,9,'SECRET',0,0,'8810ad581e59f2bc3928b261707a71308f7e139eb04820366dc4d5c18d980225','127.0.0.1','device-9','2026-10-17 18:38:35'),(10,3,9,'SECRET',3,1,'962411f188765b8d7ccee697298e15184e524a54a3d58f1124ecd92d0c7702b3','127.0.0.1','device-9','2026-10-17 18:38:35'),(11,3,9,'QUESTION',3,0,'b289f80a9a07f8f89930f70cae5317b6179a3b42dd825cec68d978657f8900d3','127.0.0.1','device-9','2026-10-17 18:38:35'),(12,3,9,'QUESTION',3,1,'6b465516e7221fc65d4c48330a398de95ce1b11089f185d2e4874499ae96f1c8','127.0.0.1','device-9','2026-10-17 18:38:35'),(13,4,11,'SECRET',0,0,'8810ad581e59f2bc3928b261707a71308f7e139eb04820366dc4d5c18d980225','127.0.0.1','device-11','2026-10-17 18:38:57'),(14,4,11,'SECRET',4,1,'962411f188765b8d7ccee697298e15184e524a54a3d58f1124ecd92d0c7702b3','127.0.0.1','device-11','2026-10-17 18:38:57'),(15,4,11,'QUESTION',4,0,'b289f80a9a07f8f89930f70cae5317b6179a3b42dd825cec68d978657f8900d3','127.0.0.1','device-11','2026-10-17 18:38:57'),(16,4,11,'QUESTION',4,1,'6b465516e7221fc65d4c48330a398de95ce1b11089f185d2e4874499ae96f1c8','127.0.0.1','device-11','2026-10-17 18:38:57'),(17,5,15,'SECRET',0,0,'8810ad581e59f2bc3928b261707a71308f7e139eb04820366dc4d5c18d980225','127.0.0.1','device-15','2026-10-17 19:57:25'),(18,5,15,'SECRET',5,1,'962411f188765b8d7ccee697298e15184e524a54a3d58f1124ecd92d0c7702b3','127.0.0.1','device-15','2026-10-17 19:57:25'),(19,5,15,'QUESTION',5,0,'b289f80a9a07f8f89930f70cae5317b6179a3b42dd825cec68d978657f8900d3','127.0.0.1','device-15','2026-10-17 19:57:25'),(20,5,15,'QUESTION',5,1,'6b465516e7221fc65d4c48330a398de95ce1b11089f185d2e4874499ae96f1c8','127.0.0.1','device-15','2026-10-17 19:57:25'),(21,6,18,'SECRET',0,0,'8810ad581e59f2bc3928b261707a71308f7e139eb04820366dc4d5c18d980225','127.0.0.1','device-18','2026-10-17 21:21:25'),(22,6,18,'SECRET',6,1,'962411f188765b8d7ccee697298e15184e524a54a3d58f1124ecd92d0c7702b3','127.0.0.1','device-18','2026-10-17 21:21:26'),(23,6,18,'QUESTION',6,0,'b289f80a9a07f8f89930f70cae5317b6179a3b42dd825cec68d978657f8900d3','127.0.0.1','device-18','2026-10-17 21:21:26'),(24,6,18,'QUESTION',6,1,'6b465516e7221fc65d4c48330a398de95ce1b11089f185d2e4874499ae96f1c8','127.0.0.1','device-18','2026-10-17 21:21:26'),(25,7,25,'SECRET',0,0,'8810ad581e59f2bc3928b261707a71308f7e139eb04820366dc4d5c18d980225','127.0.0.1','device-25','2026-10-17 21:38:34'),(26,7,25,'SECRET',7,1,'962411f188765b8d7ccee697298e15184e524a54a3d58f1124ecd92d0c7702b3','127.0.0.1','device-25','2026-10-17 21:38:34'),(27,7,25,'QUESTION',7,0,'b289f80a9a07f8f89930f70cae5317b6179a3b42dd825cec68d978657f8900d3','127.0.0.1','device-25','2026-10-17 21:38:34'),(28,7,25,'QUESTION',7,1,'6b465516e7221fc65d4c48330a398de95ce1b11089f185d2e4874499ae96f1c8','127.0.0.1','device-25','2026-10-17 21:38:34'),(29,8,29,'SECRET',0,0,'8810ad581e59f2bc3928b261707a71308f7e139eb04820366dc4d5c18d980225','127.0.0.1','device-29','2026-10-17 22:51:25'),(30,8,29,'SECRET',8,1,'962411f188765b8d7ccee697298e15184e524a54a3d58f1124ecd92d0c7702b3','127.0.0.1','device-29','2026-10-17 22:51:25'),(31,8,29,'QUESTION',8,0,'b289f80a9a07f8f89930f70cae5317b6179a3b42dd825cec68d978657f8900d3','127.0.0.1','device-29','2026-10-17 22:51:25'),(32,8,29,'QUESTION',8,1,'6b465516e7221fc65d4c48330a398de95ce1b11089f185d2e4874499ae96f1c8','127.0.0.1','device-29','2026-10-17 22:51:25'),(33,9,33,'SECRET',0,0,'8810ad581e59f2bc3928b261707a71308f7e139eb04820366dc4d5c18d980225','127.0.0.1','device-33','2026-10-18 09:25:18'),(34,9,33,'SECRET',9,1,'962411f188765b8d7ccee697298e15184e524a54a3d58f1124ecd92d0c7702b3','127.0.0.1','device-33','2026-10-18 09:25:18'),(35,9,33,'QUESTION',9,0,'b289f80a9a07f8f89930f70cae5317b6179a3b42dd825cec68d978657f8900d3','127.0.0.1','device-33','2026-10-18 09:25:18'),(36,9,33,'QUESTION',9,1,'6b465516e7221fc65d4c48330a398de95ce1b11089f185d2e4874499ae96f1c8','127.0.0.1','device-33','2026-10-18 09:25:18');
/*!40000 ALTER TABLE `hunt_challenge_attempts` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `hunt_completions`
--

DROP TABLE IF EXISTS `hunt_completions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `hunt_completions` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint unsigned NOT NULL,
  `hunt_id` bigint unsigned NOT NULL,
  `activation_id` bigint unsigned NOT NULL,
  `reward_id` bigint unsigned DEFAULT NULL,
  `completed_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_hunt_completion` (`user_id`,`hunt_id`),
  KEY `hunt_id` (`hunt_id`),
  KEY `activation_id` (`activation_id`),
  KEY `reward_id` (`reward_id`),
  CONSTRAINT `hunt_completions_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`),
  CONSTRAINT `hunt_completions_ibfk_2` FOREIGN KEY (`hunt_id`) REFERENCES `hunts` (`id`),
  CONSTRAINT `hunt_completions_ibfk_3` FOREIGN KEY (`activation_id`) REFERENCES `hunt_user_activations` (`id`),
  CONSTRAINT `hunt_completions_ibfk_4` FOREIGN KEY (`reward_id`) REFERENCES `rewards` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=10 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `hunt_completions`
--

LOCK TABLES `hunt_completions` WRITE;
/*!40000 ALTER TABLE `hunt_completions` DISABLE KEYS */;
INSERT INTO `hunt_completions` VALUES (1,1,1,1,1,'2026-08-23 19:08:23'),(2,7,5,4,2,'2026-10-17 18:37:52'),(3,9,6,5,3,'2026-10-17 18:38:35'),(4,11,7,6,4,'2026-10-17 18:38:57'),(5,15,9,8,5,'2026-10-17 19:57:25'),(6,18,11,10,6,'2026-10-17 21:21:26'),(7,25,13,12,7,'2026-10-17 21:38:34'),(8,29,15,14,8,'2026-10-17 22:51:25'),(9,33,17,16,9,'2026-10-18 09:25:18');
/*!40000 ALTER TABLE `hunt_completions` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `hunt_locations`
--

DROP TABLE IF EXISTS `hunt_locations`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `hunt_locations` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `hunt_id` bigint unsigned NOT NULL,
  `name` varchar(190) COLLATE utf8mb4_unicode_ci NOT NULL,
  `address` varchar(500) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `latitude` decimal(10,7) NOT NULL,
  `longitude` decimal(10,7) NOT NULL,
  `radius_meters` int unsigned NOT NULL DEFAULT '100',
  `sort_order` int unsigned NOT NULL DEFAULT '0',
  `status` enum('ACTIVE','INACTIVE') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'ACTIVE',
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_hunt_location_name` (`hunt_id`,`name`),
  KEY `idx_location_hunt_order` (`hunt_id`,`sort_order`),
  CONSTRAINT `hunt_locations_ibfk_1` FOREIGN KEY (`hunt_id`) REFERENCES `hunts` (`id`),
  CONSTRAINT `hunt_locations_chk_1` CHECK ((`latitude` between -(90) and 90)),
  CONSTRAINT `hunt_locations_chk_2` CHECK ((`longitude` between -(180) and 180)),
  CONSTRAINT `hunt_locations_chk_3` CHECK ((`radius_meters` between 1 and 100000))
) ENGINE=InnoDB AUTO_INCREMENT=18 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `hunt_locations`
--

LOCK TABLES `hunt_locations` WRITE;
/*!40000 ALTER TABLE `hunt_locations` DISABLE KEYS */;
INSERT INTO `hunt_locations` VALUES (1,1,'Test Location',NULL,12.9716000,77.5946000,100,0,'ACTIVE','2026-08-23 19:08:22','2026-08-23 19:08:22'),(2,2,'QR Spot',NULL,12.9716000,77.5946000,100,0,'ACTIVE','2026-08-23 19:08:23','2026-08-23 19:08:23'),(3,3,'Patna Central Mall - Lift Lobby','Fraser Road, Patna',25.6120450,85.1440210,100,0,'ACTIVE','2026-08-29 11:29:07','2026-08-29 11:29:07'),(4,4,'QR Spot',NULL,12.9716000,77.5946000,100,0,'ACTIVE','2026-10-17 18:36:24','2026-10-17 18:36:24'),(5,5,'Test Location',NULL,12.9716000,77.5946000,100,0,'ACTIVE','2026-10-17 18:37:51','2026-10-17 18:37:51'),(6,6,'Test Location',NULL,12.9716000,77.5946000,100,0,'ACTIVE','2026-10-17 18:38:34','2026-10-17 18:38:34'),(7,7,'Test Location',NULL,12.9716000,77.5946000,100,0,'ACTIVE','2026-10-17 18:38:56','2026-10-17 18:38:56'),(8,8,'QR Spot',NULL,12.9716000,77.5946000,100,0,'ACTIVE','2026-10-17 19:57:16','2026-10-17 19:57:16'),(9,9,'Test Location',NULL,12.9716000,77.5946000,100,0,'ACTIVE','2026-10-17 19:57:24','2026-10-17 19:57:24'),(10,10,'QR Spot',NULL,12.9716000,77.5946000,100,0,'ACTIVE','2026-10-17 21:21:24','2026-10-17 21:21:24'),(11,11,'Test Location',NULL,12.9716000,77.5946000,100,0,'ACTIVE','2026-10-17 21:21:25','2026-10-17 21:21:25'),(12,12,'QR Spot',NULL,12.9716000,77.5946000,100,0,'ACTIVE','2026-10-17 21:38:32','2026-10-17 21:38:32'),(13,13,'Test Location',NULL,12.9716000,77.5946000,100,0,'ACTIVE','2026-10-17 21:38:33','2026-10-17 21:38:33'),(14,14,'QR Spot',NULL,12.9716000,77.5946000,100,0,'ACTIVE','2026-10-17 22:51:23','2026-10-17 22:51:23'),(15,15,'Test Location',NULL,12.9716000,77.5946000,100,0,'ACTIVE','2026-10-17 22:51:24','2026-10-17 22:51:24'),(16,16,'QR Spot',NULL,12.9716000,77.5946000,100,0,'ACTIVE','2026-10-18 09:25:17','2026-10-18 09:25:17'),(17,17,'Test Location',NULL,12.9716000,77.5946000,100,0,'ACTIVE','2026-10-18 09:25:18','2026-10-18 09:25:18');
/*!40000 ALTER TABLE `hunt_locations` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `hunt_qr_codes`
--

DROP TABLE IF EXISTS `hunt_qr_codes`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `hunt_qr_codes` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `hunt_id` bigint unsigned NOT NULL,
  `location_id` bigint unsigned NOT NULL,
  `token_hash` char(64) COLLATE utf8mb4_unicode_ci NOT NULL,
  `label` varchar(190) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `status` enum('ACTIVE','INACTIVE','EXPIRED') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'ACTIVE',
  `expires_at` datetime DEFAULT NULL,
  `max_scans` int unsigned DEFAULT NULL,
  `scan_count` int unsigned NOT NULL DEFAULT '0',
  `created_by` int unsigned NOT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `token_hash` (`token_hash`),
  KEY `idx_qr_hunt_location` (`hunt_id`,`location_id`,`status`),
  KEY `location_id` (`location_id`),
  KEY `created_by` (`created_by`),
  CONSTRAINT `hunt_qr_codes_ibfk_1` FOREIGN KEY (`hunt_id`) REFERENCES `hunts` (`id`),
  CONSTRAINT `hunt_qr_codes_ibfk_2` FOREIGN KEY (`location_id`) REFERENCES `hunt_locations` (`id`),
  CONSTRAINT `hunt_qr_codes_ibfk_3` FOREIGN KEY (`created_by`) REFERENCES `admins` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=18 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `hunt_qr_codes`
--

LOCK TABLES `hunt_qr_codes` WRITE;
/*!40000 ALTER TABLE `hunt_qr_codes` DISABLE KEYS */;
INSERT INTO `hunt_qr_codes` VALUES (1,1,1,'7899e858e002186475bb5ba58ed629cfc6e9a11088568ed6e9d82a46071aaaa3','Test QR','ACTIVE',NULL,NULL,1,1,'2026-08-23 19:08:22','2026-08-23 19:08:22'),(2,2,2,'2806d41fbb3dd2865bc82c83e4a66c45e97a58ace5e1e9bca36b92737cb40d25','Campaign QR','ACTIVE',NULL,NULL,0,1,'2026-08-23 19:08:23','2026-08-23 19:08:23'),(3,3,3,'24572a80bb2afb5a86228c99fb4e886d5360e1dba97b3b4ddda975a8dca7778f','DEMO-MALL-001','ACTIVE',NULL,500,0,1,'2026-08-29 11:29:07','2026-08-29 11:29:07'),(4,4,4,'ddabac49c5b1d0c2bd4c22b8c6e72bffe9dbabbaff844211c350645c05adc79e','Campaign QR','ACTIVE',NULL,NULL,0,1,'2026-10-17 18:36:24','2026-10-17 18:36:24'),(5,5,5,'7f77ba93ecb6756c6d4589805d11604bb85084844aa3df351caf6be71cd1dc62','Test QR','ACTIVE',NULL,NULL,1,4,'2026-10-17 18:37:51','2026-10-17 18:37:52'),(6,6,6,'db9bd067095d96d9f97e2b6ff7cef6b40598598c2c051cbb6b0c5644063e906b','Test QR','ACTIVE',NULL,NULL,1,5,'2026-10-17 18:38:34','2026-10-17 18:38:35'),(7,7,7,'c7baec65520926982253cb283a04c7270af06a13f8dd9b0bd9f026289b00eb24','Test QR','ACTIVE',NULL,NULL,1,6,'2026-10-17 18:38:56','2026-10-17 18:38:57'),(8,8,8,'9b263d1ce3c3cb3ce281e3184cebbca2137a4ad3a13ed93ec6a33ca03773fe2a','Campaign QR','ACTIVE',NULL,NULL,0,1,'2026-10-17 19:57:16','2026-10-17 19:57:16'),(9,9,9,'beb2aace1002a4370b407e9d3ad29c6f8181e8cc6369291997a9f0f37fd0b20f','Test QR','ACTIVE',NULL,NULL,1,8,'2026-10-17 19:57:24','2026-10-17 19:57:25'),(10,10,10,'b9a3c7e068dd5c188a9a0c58a31fc5abc540b6fc99b917bf6da460d70cf139e9','Campaign QR','ACTIVE',NULL,NULL,0,1,'2026-10-17 21:21:24','2026-10-17 21:21:24'),(11,11,11,'c501ccded0bbcce8cc1eedc4abc19b071e6c42509597f5694c6b697059fe5586','Test QR','ACTIVE',NULL,NULL,1,9,'2026-10-17 21:21:25','2026-10-17 21:21:25'),(12,12,12,'b4412450c953e52ddaad4585f904242639b685b18096cb72dadbd9ecd0e2e92f','Campaign QR','ACTIVE',NULL,NULL,0,1,'2026-10-17 21:38:32','2026-10-17 21:38:32'),(13,13,13,'99ac7c851e713bbca91b6f5c7c2d87ce21463ff9952ad3b619193cf49afb2508','Test QR','ACTIVE',NULL,NULL,1,14,'2026-10-17 21:38:33','2026-10-17 21:38:34'),(14,14,14,'278da9764864bad301f49d30ded8a45ad1b5e6ed5337f083e4c78540b98ec852','Campaign QR','ACTIVE',NULL,NULL,0,1,'2026-10-17 22:51:23','2026-10-17 22:51:23'),(15,15,15,'c80d89b07d071d2bd8b425fb7f1993e037167e9bd982ef27f317d777d5de349b','Test QR','ACTIVE',NULL,NULL,1,16,'2026-10-17 22:51:24','2026-10-17 22:51:24'),(16,16,16,'702a6c366dd2959245cc1f301afdc1ffa4eff15551992c1f44b7d7e0edf87524','Campaign QR','ACTIVE',NULL,NULL,0,1,'2026-10-18 09:25:17','2026-10-18 09:25:17'),(17,17,17,'23490cb761325c6461fcd0c3e9ef20123919cc6e6f812efe120e8b1aa540c637','Test QR','ACTIVE',NULL,NULL,1,18,'2026-10-18 09:25:18','2026-10-18 09:25:18');
/*!40000 ALTER TABLE `hunt_qr_codes` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `hunt_question_options`
--

DROP TABLE IF EXISTS `hunt_question_options`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `hunt_question_options` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `question_id` bigint unsigned NOT NULL,
  `option_text` varchar(500) COLLATE utf8mb4_unicode_ci NOT NULL,
  `sort_order` int unsigned NOT NULL DEFAULT '0',
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_option_question_order` (`question_id`,`sort_order`),
  CONSTRAINT `hunt_question_options_ibfk_1` FOREIGN KEY (`question_id`) REFERENCES `hunt_questions` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `hunt_question_options`
--

LOCK TABLES `hunt_question_options` WRITE;
/*!40000 ALTER TABLE `hunt_question_options` DISABLE KEYS */;
/*!40000 ALTER TABLE `hunt_question_options` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `hunt_questions`
--

DROP TABLE IF EXISTS `hunt_questions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `hunt_questions` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `hunt_id` bigint unsigned NOT NULL,
  `location_id` bigint unsigned DEFAULT NULL,
  `question_text` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `question_type` enum('TEXT','MCQ') COLLATE utf8mb4_unicode_ci NOT NULL,
  `correct_answer_hash` char(64) COLLATE utf8mb4_unicode_ci NOT NULL,
  `sort_order` int unsigned NOT NULL DEFAULT '0',
  `status` enum('ACTIVE','INACTIVE') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'ACTIVE',
  `created_by` int unsigned NOT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_question_hunt_order` (`hunt_id`,`location_id`,`sort_order`),
  KEY `location_id` (`location_id`),
  KEY `created_by` (`created_by`),
  CONSTRAINT `hunt_questions_ibfk_1` FOREIGN KEY (`hunt_id`) REFERENCES `hunts` (`id`),
  CONSTRAINT `hunt_questions_ibfk_2` FOREIGN KEY (`location_id`) REFERENCES `hunt_locations` (`id`),
  CONSTRAINT `hunt_questions_ibfk_3` FOREIGN KEY (`created_by`) REFERENCES `admins` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=10 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `hunt_questions`
--

LOCK TABLES `hunt_questions` WRITE;
/*!40000 ALTER TABLE `hunt_questions` DISABLE KEYS */;
INSERT INTO `hunt_questions` VALUES (1,1,1,'Capital of Karnataka?','TEXT','6b465516e7221fc65d4c48330a398de95ce1b11089f185d2e4874499ae96f1c8',0,'ACTIVE',1,'2026-08-23 19:08:22','2026-08-23 19:08:22'),(2,5,5,'Capital of Karnataka?','TEXT','6b465516e7221fc65d4c48330a398de95ce1b11089f185d2e4874499ae96f1c8',0,'ACTIVE',4,'2026-10-17 18:37:52','2026-10-17 18:37:52'),(3,6,6,'Capital of Karnataka?','TEXT','6b465516e7221fc65d4c48330a398de95ce1b11089f185d2e4874499ae96f1c8',0,'ACTIVE',5,'2026-10-17 18:38:35','2026-10-17 18:38:35'),(4,7,7,'Capital of Karnataka?','TEXT','6b465516e7221fc65d4c48330a398de95ce1b11089f185d2e4874499ae96f1c8',0,'ACTIVE',6,'2026-10-17 18:38:57','2026-10-17 18:38:57'),(5,9,9,'Capital of Karnataka?','TEXT','6b465516e7221fc65d4c48330a398de95ce1b11089f185d2e4874499ae96f1c8',0,'ACTIVE',8,'2026-10-17 19:57:25','2026-10-17 19:57:25'),(6,11,11,'Capital of Karnataka?','TEXT','6b465516e7221fc65d4c48330a398de95ce1b11089f185d2e4874499ae96f1c8',0,'ACTIVE',9,'2026-10-17 21:21:25','2026-10-17 21:21:25'),(7,13,13,'Capital of Karnataka?','TEXT','6b465516e7221fc65d4c48330a398de95ce1b11089f185d2e4874499ae96f1c8',0,'ACTIVE',14,'2026-10-17 21:38:33','2026-10-17 21:38:33'),(8,15,15,'Capital of Karnataka?','TEXT','6b465516e7221fc65d4c48330a398de95ce1b11089f185d2e4874499ae96f1c8',0,'ACTIVE',16,'2026-10-17 22:51:24','2026-10-17 22:51:24'),(9,17,17,'Capital of Karnataka?','TEXT','6b465516e7221fc65d4c48330a398de95ce1b11089f185d2e4874499ae96f1c8',0,'ACTIVE',18,'2026-10-18 09:25:18','2026-10-18 09:25:18');
/*!40000 ALTER TABLE `hunt_questions` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `hunt_scan_attempts`
--

DROP TABLE IF EXISTS `hunt_scan_attempts`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `hunt_scan_attempts` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint unsigned DEFAULT NULL,
  `hunt_id` bigint unsigned DEFAULT NULL,
  `location_id` bigint unsigned DEFAULT NULL,
  `qr_code_id` bigint unsigned DEFAULT NULL,
  `device_id` varchar(190) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `ip_address` varchar(45) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `latitude` decimal(10,7) DEFAULT NULL,
  `longitude` decimal(10,7) DEFAULT NULL,
  `distance_meters` decimal(12,2) DEFAULT NULL,
  `result` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `reason` varchar(500) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `risk_level` enum('LOW','MEDIUM','HIGH','CRITICAL') COLLATE utf8mb4_unicode_ci NOT NULL,
  `metadata` json DEFAULT NULL,
  `attempted_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_scan_attempt_user_date` (`user_id`,`attempted_at`),
  KEY `idx_scan_attempt_risk` (`risk_level`,`attempted_at`),
  KEY `hunt_id` (`hunt_id`),
  KEY `location_id` (`location_id`),
  KEY `qr_code_id` (`qr_code_id`),
  CONSTRAINT `hunt_scan_attempts_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`),
  CONSTRAINT `hunt_scan_attempts_ibfk_2` FOREIGN KEY (`hunt_id`) REFERENCES `hunts` (`id`),
  CONSTRAINT `hunt_scan_attempts_ibfk_3` FOREIGN KEY (`location_id`) REFERENCES `hunt_locations` (`id`),
  CONSTRAINT `hunt_scan_attempts_ibfk_4` FOREIGN KEY (`qr_code_id`) REFERENCES `hunt_qr_codes` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=28 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `hunt_scan_attempts`
--

LOCK TABLES `hunt_scan_attempts` WRITE;
/*!40000 ALTER TABLE `hunt_scan_attempts` DISABLE KEYS */;
INSERT INTO `hunt_scan_attempts` VALUES (1,1,1,1,1,'device-1','127.0.0.1',13.0500000,77.7000000,14366.39,'REJECTED','User is outside the allowed GPS radius.','HIGH','[]','2026-08-23 19:08:22'),(2,1,1,1,1,'device-1','127.0.0.1',12.9716100,77.5946100,1.55,'VALIDATED','Scan validated','LOW','[]','2026-08-23 19:08:22'),(3,1,1,1,1,'device-1','127.0.0.1',12.9716100,77.5946100,1.55,'DUPLICATE','QR was already claimed by this user','HIGH','[]','2026-08-23 19:08:22'),(4,7,5,5,5,'device-7','127.0.0.1',13.0500000,77.7000000,14366.39,'REJECTED','User is outside the allowed GPS radius.','HIGH','[]','2026-10-17 18:37:52'),(5,7,5,5,5,'device-7','127.0.0.1',12.9716100,77.5946100,1.55,'VALIDATED','Scan validated','LOW','[]','2026-10-17 18:37:52'),(6,7,5,5,5,'device-7','127.0.0.1',12.9716100,77.5946100,1.55,'DUPLICATE','QR was already claimed by this user','HIGH','[]','2026-10-17 18:37:52'),(7,9,6,6,6,'device-9','127.0.0.1',13.0500000,77.7000000,14366.39,'REJECTED','User is outside the allowed GPS radius.','HIGH','[]','2026-10-17 18:38:35'),(8,9,6,6,6,'device-9','127.0.0.1',12.9716100,77.5946100,1.55,'VALIDATED','Scan validated','LOW','[]','2026-10-17 18:38:35'),(9,9,6,6,6,'device-9','127.0.0.1',12.9716100,77.5946100,1.55,'DUPLICATE','QR was already claimed by this user','HIGH','[]','2026-10-17 18:38:35'),(10,11,7,7,7,'device-11','127.0.0.1',13.0500000,77.7000000,14366.39,'REJECTED','User is outside the allowed GPS radius.','HIGH','[]','2026-10-17 18:38:57'),(11,11,7,7,7,'device-11','127.0.0.1',12.9716100,77.5946100,1.55,'VALIDATED','Scan validated','LOW','[]','2026-10-17 18:38:57'),(12,11,7,7,7,'device-11','127.0.0.1',12.9716100,77.5946100,1.55,'DUPLICATE','QR was already claimed by this user','HIGH','[]','2026-10-17 18:38:57'),(13,15,9,9,9,'device-15','127.0.0.1',13.0500000,77.7000000,14366.39,'REJECTED','User is outside the allowed GPS radius.','HIGH','[]','2026-10-17 19:57:25'),(14,15,9,9,9,'device-15','127.0.0.1',12.9716100,77.5946100,1.55,'VALIDATED','Scan validated','LOW','[]','2026-10-17 19:57:25'),(15,15,9,9,9,'device-15','127.0.0.1',12.9716100,77.5946100,1.55,'DUPLICATE','QR was already claimed by this user','HIGH','[]','2026-10-17 19:57:25'),(16,18,11,11,11,'device-18','127.0.0.1',13.0500000,77.7000000,14366.39,'REJECTED','User is outside the allowed GPS radius.','HIGH','[]','2026-10-17 21:21:25'),(17,18,11,11,11,'device-18','127.0.0.1',12.9716100,77.5946100,1.55,'VALIDATED','Scan validated','LOW','[]','2026-10-17 21:21:25'),(18,18,11,11,11,'device-18','127.0.0.1',12.9716100,77.5946100,1.55,'DUPLICATE','QR was already claimed by this user','HIGH','[]','2026-10-17 21:21:25'),(19,25,13,13,13,'device-25','127.0.0.1',13.0500000,77.7000000,14366.39,'REJECTED','User is outside the allowed GPS radius.','HIGH','[]','2026-10-17 21:38:34'),(20,25,13,13,13,'device-25','127.0.0.1',12.9716100,77.5946100,1.55,'VALIDATED','Scan validated','LOW','[]','2026-10-17 21:38:34'),(21,25,13,13,13,'device-25','127.0.0.1',12.9716100,77.5946100,1.55,'DUPLICATE','QR was already claimed by this user','HIGH','[]','2026-10-17 21:38:34'),(22,29,15,15,15,'device-29','127.0.0.1',13.0500000,77.7000000,14366.39,'REJECTED','User is outside the allowed GPS radius.','HIGH','[]','2026-10-17 22:51:24'),(23,29,15,15,15,'device-29','127.0.0.1',12.9716100,77.5946100,1.55,'VALIDATED','Scan validated','LOW','[]','2026-10-17 22:51:24'),(24,29,15,15,15,'device-29','127.0.0.1',12.9716100,77.5946100,1.55,'DUPLICATE','QR was already claimed by this user','HIGH','[]','2026-10-17 22:51:24'),(25,33,17,17,17,'device-33','127.0.0.1',13.0500000,77.7000000,14366.39,'REJECTED','User is outside the allowed GPS radius.','HIGH','[]','2026-10-18 09:25:18'),(26,33,17,17,17,'device-33','127.0.0.1',12.9716100,77.5946100,1.55,'VALIDATED','Scan validated','LOW','[]','2026-10-18 09:25:18'),(27,33,17,17,17,'device-33','127.0.0.1',12.9716100,77.5946100,1.55,'DUPLICATE','QR was already claimed by this user','HIGH','[]','2026-10-18 09:25:18');
/*!40000 ALTER TABLE `hunt_scan_attempts` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `hunt_scans`
--

DROP TABLE IF EXISTS `hunt_scans`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `hunt_scans` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint unsigned NOT NULL,
  `hunt_id` bigint unsigned NOT NULL,
  `location_id` bigint unsigned NOT NULL,
  `qr_code_id` bigint unsigned NOT NULL,
  `activation_id` bigint unsigned NOT NULL,
  `device_id` varchar(190) COLLATE utf8mb4_unicode_ci NOT NULL,
  `latitude` decimal(10,7) NOT NULL,
  `longitude` decimal(10,7) NOT NULL,
  `gps_accuracy_meters` decimal(10,2) DEFAULT NULL,
  `distance_meters` decimal(12,2) NOT NULL,
  `allowed_radius_meters` int unsigned NOT NULL,
  `ip_address` varchar(45) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `result` enum('VALIDATED','REJECTED','COMPLETED') COLLATE utf8mb4_unicode_ci NOT NULL,
  `reason` varchar(500) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `risk_level` enum('LOW','MEDIUM','HIGH','CRITICAL') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'LOW',
  `scanned_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_user_qr_claim` (`user_id`,`qr_code_id`),
  KEY `idx_scan_hunt_date` (`hunt_id`,`scanned_at`),
  KEY `idx_scan_result_risk` (`result`,`risk_level`),
  KEY `location_id` (`location_id`),
  KEY `qr_code_id` (`qr_code_id`),
  KEY `activation_id` (`activation_id`),
  CONSTRAINT `hunt_scans_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`),
  CONSTRAINT `hunt_scans_ibfk_2` FOREIGN KEY (`hunt_id`) REFERENCES `hunts` (`id`),
  CONSTRAINT `hunt_scans_ibfk_3` FOREIGN KEY (`location_id`) REFERENCES `hunt_locations` (`id`),
  CONSTRAINT `hunt_scans_ibfk_4` FOREIGN KEY (`qr_code_id`) REFERENCES `hunt_qr_codes` (`id`),
  CONSTRAINT `hunt_scans_ibfk_5` FOREIGN KEY (`activation_id`) REFERENCES `hunt_user_activations` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=10 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `hunt_scans`
--

LOCK TABLES `hunt_scans` WRITE;
/*!40000 ALTER TABLE `hunt_scans` DISABLE KEYS */;
INSERT INTO `hunt_scans` VALUES (1,1,1,1,1,1,'device-1',12.9716100,77.5946100,10.00,1.55,100,'127.0.0.1','COMPLETED',NULL,'LOW','2026-08-23 19:08:22'),(2,7,5,5,5,4,'device-7',12.9716100,77.5946100,10.00,1.55,100,'127.0.0.1','COMPLETED',NULL,'LOW','2026-10-17 18:37:52'),(3,9,6,6,6,5,'device-9',12.9716100,77.5946100,10.00,1.55,100,'127.0.0.1','COMPLETED',NULL,'LOW','2026-10-17 18:38:35'),(4,11,7,7,7,6,'device-11',12.9716100,77.5946100,10.00,1.55,100,'127.0.0.1','COMPLETED',NULL,'LOW','2026-10-17 18:38:57'),(5,15,9,9,9,8,'device-15',12.9716100,77.5946100,10.00,1.55,100,'127.0.0.1','COMPLETED',NULL,'LOW','2026-10-17 19:57:25'),(6,18,11,11,11,10,'device-18',12.9716100,77.5946100,10.00,1.55,100,'127.0.0.1','COMPLETED',NULL,'LOW','2026-10-17 21:21:25'),(7,25,13,13,13,12,'device-25',12.9716100,77.5946100,10.00,1.55,100,'127.0.0.1','COMPLETED',NULL,'LOW','2026-10-17 21:38:34'),(8,29,15,15,15,14,'device-29',12.9716100,77.5946100,10.00,1.55,100,'127.0.0.1','COMPLETED',NULL,'LOW','2026-10-17 22:51:24'),(9,33,17,17,17,16,'device-33',12.9716100,77.5946100,10.00,1.55,100,'127.0.0.1','COMPLETED',NULL,'LOW','2026-10-18 09:25:18');
/*!40000 ALTER TABLE `hunt_scans` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `hunt_scratch_sessions`
--

DROP TABLE IF EXISTS `hunt_scratch_sessions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `hunt_scratch_sessions` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `session_id` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `scan_id` bigint unsigned NOT NULL,
  `user_id` bigint unsigned NOT NULL,
  `token_hash` char(64) COLLATE utf8mb4_unicode_ci NOT NULL,
  `status` enum('CREATED','REVEALED','EXPIRED') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'CREATED',
  `expires_at` datetime NOT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `revealed_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `session_id` (`session_id`),
  UNIQUE KEY `scan_id` (`scan_id`),
  UNIQUE KEY `token_hash` (`token_hash`),
  KEY `user_id` (`user_id`),
  CONSTRAINT `hunt_scratch_sessions_ibfk_1` FOREIGN KEY (`scan_id`) REFERENCES `hunt_scans` (`id`),
  CONSTRAINT `hunt_scratch_sessions_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=10 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `hunt_scratch_sessions`
--

LOCK TABLES `hunt_scratch_sessions` WRITE;
/*!40000 ALTER TABLE `hunt_scratch_sessions` DISABLE KEYS */;
INSERT INTO `hunt_scratch_sessions` VALUES (1,'SCR-20260823-6B789270',1,1,'3d2f3406a6ad76ef5525b029272af6bdb84991771a3c5884bfce2ec25e2c9743','REVEALED','2026-08-23 19:23:23','2026-08-23 19:08:23',NULL),(2,'SCR-20261017-356C266F',2,7,'7c39f81b2c7509bdda9051ada4218b99810762d1f6ffdb63b04ce1cd9f85a72d','REVEALED','2026-10-17 18:52:52','2026-10-17 18:37:52',NULL),(3,'SCR-20261017-5AE5F6A8',3,9,'8b307721ca6562d230a59d3564b54c3b4ee0535ec8f513405fabd1971b8c4056','REVEALED','2026-10-17 18:53:35','2026-10-17 18:38:35',NULL),(4,'SCR-20261017-EACE2AFF',4,11,'2c7d62cb4a759a9e2367f59b6925467cc699322ba904332ef8b116d52d195f96','REVEALED','2026-10-17 18:53:57','2026-10-17 18:38:57',NULL),(5,'SCR-20261017-60D188D5',5,15,'478aafb4cfe8a1dfd46fb18c718934429e856b23e79354aa1df769c53ef10f78','REVEALED','2026-10-17 20:12:25','2026-10-17 19:57:25',NULL),(6,'SCR-20261017-97F41C8E',6,18,'a0720b3a585272df645721a857a95eda4acbb09797bebda2b4ce9a98ea92cb8a','REVEALED','2026-10-17 21:36:26','2026-10-17 21:21:26',NULL),(7,'SCR-20261017-51C73487',7,25,'f16955001fb602a4f6a80621a83980fee113632f7eb78e1972a65f3687746a67','REVEALED','2026-10-17 21:53:34','2026-10-17 21:38:34',NULL),(8,'SCR-20261017-7ECC1479',8,29,'b525b9ab32cc12d5f676f2cc0136c7772ff39ae8444e9d6df5da69601837cfcf','REVEALED','2026-10-17 23:06:25','2026-10-17 22:51:25',NULL),(9,'SCR-20261018-8CC5C240',9,33,'6d449fe2cf2cc0367b2275785bbd8e72a7281dd4474fed49e316e1a112ee8b5a','REVEALED','2026-10-18 09:40:18','2026-10-18 09:25:18',NULL);
/*!40000 ALTER TABLE `hunt_scratch_sessions` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `hunt_secret_codes`
--

DROP TABLE IF EXISTS `hunt_secret_codes`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `hunt_secret_codes` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `hunt_id` bigint unsigned NOT NULL,
  `location_id` bigint unsigned DEFAULT NULL,
  `code_hash` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `valid_from` datetime NOT NULL,
  `valid_until` datetime NOT NULL,
  `status` enum('ACTIVE','INACTIVE') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'ACTIVE',
  `created_by` int unsigned NOT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_secret_window` (`hunt_id`,`location_id`,`status`,`valid_from`,`valid_until`),
  KEY `location_id` (`location_id`),
  KEY `created_by` (`created_by`),
  CONSTRAINT `hunt_secret_codes_ibfk_1` FOREIGN KEY (`hunt_id`) REFERENCES `hunts` (`id`),
  CONSTRAINT `hunt_secret_codes_ibfk_2` FOREIGN KEY (`location_id`) REFERENCES `hunt_locations` (`id`),
  CONSTRAINT `hunt_secret_codes_ibfk_3` FOREIGN KEY (`created_by`) REFERENCES `admins` (`id`),
  CONSTRAINT `hunt_secret_codes_chk_1` CHECK ((`valid_until` > `valid_from`))
) ENGINE=InnoDB AUTO_INCREMENT=10 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `hunt_secret_codes`
--

LOCK TABLES `hunt_secret_codes` WRITE;
/*!40000 ALTER TABLE `hunt_secret_codes` DISABLE KEYS */;
INSERT INTO `hunt_secret_codes` VALUES (1,1,1,'$2y$12$krci.9yIZx9Bw6ugos59he12x8hl5Z2dwrtmw/zNLHwCuLFs52J0m','2026-08-23 18:08:22','2026-08-23 20:08:22','ACTIVE',1,'2026-08-23 19:08:22'),(2,5,5,'$2y$12$1g/Ml5EebCyqk8P6tjxfrepkbELAPm5iDoz1dwZB978Nl30lAYSEq','2026-10-17 17:37:52','2026-10-17 19:37:52','ACTIVE',4,'2026-10-17 18:37:52'),(3,6,6,'$2y$12$4K5MQQKOkatEAnQK0C/uaO.kwi8OZdcR9mTJXO.dkF9tV/TL/Jnce','2026-10-17 17:38:35','2026-10-17 19:38:35','ACTIVE',5,'2026-10-17 18:38:35'),(4,7,7,'$2y$12$CrLnxAeQIqlSfNL8w2IgO.3cjHV0QYcaSCuFrCj8x65Jdp8B6sfcC','2026-10-17 17:38:57','2026-10-17 19:38:57','ACTIVE',6,'2026-10-17 18:38:57'),(5,9,9,'$2y$12$2FcclwBwx1GuWNJINrhtueaoDRRv6t4VdjzGKL5GQBWrBpQ8hdL6.','2026-10-17 18:57:25','2026-10-17 20:57:25','ACTIVE',8,'2026-10-17 19:57:25'),(6,11,11,'$2y$12$okIUslawav7HQHM5iW/hbuJFcqP6VCL0JH5LtHxzFCdjiaf9hcV4O','2026-10-17 20:21:25','2026-10-17 22:21:25','ACTIVE',9,'2026-10-17 21:21:25'),(7,13,13,'$2y$12$RYFqiuk7Se6amI5qSR9w..YvFWvW4xt/XAXalmVmbdEufLZZUMJ7a','2026-10-17 20:38:33','2026-10-17 22:38:33','ACTIVE',14,'2026-10-17 21:38:33'),(8,15,15,'$2y$12$heUwAFibXY/6hg/ech3jpOnvfXWHJZMGUuDZyLZcFsPKyCXylJvsC','2026-10-17 21:51:24','2026-10-17 23:51:24','ACTIVE',16,'2026-10-17 22:51:24'),(9,17,17,'$2y$12$KWldpfDtyzR9KzvIAFcvwOpkTF3g9URnHJUkSbgxZK7VnzsU4NpKG','2026-10-18 08:25:18','2026-10-18 10:25:18','ACTIVE',18,'2026-10-18 09:25:18');
/*!40000 ALTER TABLE `hunt_secret_codes` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `hunt_user_activations`
--

DROP TABLE IF EXISTS `hunt_user_activations`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `hunt_user_activations` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint unsigned NOT NULL,
  `hunt_id` bigint unsigned NOT NULL,
  `activation_type` enum('ONLINE','COUNTER','ADMIN') COLLATE utf8mb4_unicode_ci NOT NULL,
  `payment_transaction_id` bigint unsigned DEFAULT NULL,
  `activated_by` int unsigned DEFAULT NULL,
  `status` enum('ACTIVE','REVOKED','COMPLETED') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'ACTIVE',
  `activated_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `expires_at` datetime DEFAULT NULL,
  `revoked_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_user_hunt_activation` (`user_id`,`hunt_id`),
  KEY `idx_activation_hunt_status` (`hunt_id`,`status`),
  KEY `payment_transaction_id` (`payment_transaction_id`),
  KEY `activated_by` (`activated_by`),
  CONSTRAINT `hunt_user_activations_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`),
  CONSTRAINT `hunt_user_activations_ibfk_2` FOREIGN KEY (`hunt_id`) REFERENCES `hunts` (`id`),
  CONSTRAINT `hunt_user_activations_ibfk_3` FOREIGN KEY (`payment_transaction_id`) REFERENCES `payment_transactions` (`id`),
  CONSTRAINT `hunt_user_activations_ibfk_4` FOREIGN KEY (`activated_by`) REFERENCES `admins` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=18 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `hunt_user_activations`
--

LOCK TABLES `hunt_user_activations` WRITE;
/*!40000 ALTER TABLE `hunt_user_activations` DISABLE KEYS */;
INSERT INTO `hunt_user_activations` VALUES (1,1,1,'COUNTER',NULL,1,'COMPLETED','2026-08-23 19:08:22','2026-08-25 19:08:22',NULL),(2,2,1,'ONLINE',1,NULL,'ACTIVE','2026-08-23 19:08:23','2026-08-25 19:08:22',NULL),(3,1,3,'ADMIN',NULL,1,'ACTIVE','2026-08-29 11:29:07','2026-09-28 11:29:07',NULL),(4,7,5,'COUNTER',NULL,4,'COMPLETED','2026-10-17 18:37:52','2026-10-19 18:37:51',NULL),(5,9,6,'COUNTER',NULL,5,'COMPLETED','2026-10-17 18:38:35','2026-10-19 18:38:34',NULL),(6,11,7,'COUNTER',NULL,6,'COMPLETED','2026-10-17 18:38:57','2026-10-19 18:38:56',NULL),(7,12,7,'ONLINE',4,NULL,'ACTIVE','2026-10-17 18:38:57','2026-10-19 18:38:56',NULL),(8,15,9,'COUNTER',NULL,8,'COMPLETED','2026-10-17 19:57:25','2026-10-19 19:57:24',NULL),(9,16,9,'ONLINE',5,NULL,'ACTIVE','2026-10-17 19:57:25','2026-10-19 19:57:24',NULL),(10,18,11,'COUNTER',NULL,9,'COMPLETED','2026-10-17 21:21:25','2026-10-19 21:21:25',NULL),(11,19,11,'ONLINE',6,NULL,'ACTIVE','2026-10-17 21:21:26','2026-10-19 21:21:25',NULL),(12,25,13,'COUNTER',NULL,14,'COMPLETED','2026-10-17 21:38:33','2026-10-19 21:38:33',NULL),(13,26,13,'ONLINE',7,NULL,'ACTIVE','2026-10-17 21:38:34','2026-10-19 21:38:33',NULL),(14,29,15,'COUNTER',NULL,16,'COMPLETED','2026-10-17 22:51:24','2026-10-19 22:51:24',NULL),(15,30,15,'ONLINE',8,NULL,'ACTIVE','2026-10-17 22:51:25','2026-10-19 22:51:24',NULL),(16,33,17,'COUNTER',NULL,18,'COMPLETED','2026-10-18 09:25:18','2026-10-20 09:25:18',NULL),(17,34,17,'ONLINE',9,NULL,'ACTIVE','2026-10-18 09:25:18','2026-10-20 09:25:18',NULL);
/*!40000 ALTER TABLE `hunt_user_activations` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `hunt_videos`
--

DROP TABLE IF EXISTS `hunt_videos`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `hunt_videos` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `hunt_id` bigint unsigned NOT NULL,
  `location_id` bigint unsigned DEFAULT NULL,
  `category_id` bigint unsigned DEFAULT NULL,
  `title` varchar(190) COLLATE utf8mb4_unicode_ci NOT NULL,
  `video_url` varchar(500) COLLATE utf8mb4_unicode_ci NOT NULL,
  `clue_text` text COLLATE utf8mb4_unicode_ci,
  `sort_order` int unsigned NOT NULL DEFAULT '0',
  `status` enum('ACTIVE','INACTIVE') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'ACTIVE',
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_video_hunt_order` (`hunt_id`,`sort_order`),
  KEY `location_id` (`location_id`),
  KEY `fk_video_category` (`category_id`),
  CONSTRAINT `fk_video_category` FOREIGN KEY (`category_id`) REFERENCES `video_categories` (`id`),
  CONSTRAINT `hunt_videos_ibfk_1` FOREIGN KEY (`hunt_id`) REFERENCES `hunts` (`id`),
  CONSTRAINT `hunt_videos_ibfk_2` FOREIGN KEY (`location_id`) REFERENCES `hunt_locations` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `hunt_videos`
--

LOCK TABLES `hunt_videos` WRITE;
/*!40000 ALTER TABLE `hunt_videos` DISABLE KEYS */;
/*!40000 ALTER TABLE `hunt_videos` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `hunts`
--

DROP TABLE IF EXISTS `hunts`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `hunts` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `title` varchar(190) COLLATE utf8mb4_unicode_ci NOT NULL,
  `slug` varchar(190) COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` text COLLATE utf8mb4_unicode_ci,
  `rules` text COLLATE utf8mb4_unicode_ci,
  `tutorial_video_url` varchar(500) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `tutorial_video_path` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `featured_image` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `latitude` decimal(10,7) DEFAULT '25.5940950',
  `longitude` decimal(10,7) DEFAULT '85.1375660',
  `allowed_radius_meters` int unsigned NOT NULL DEFAULT '20',
  `qr_token` varchar(64) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `start_at` datetime NOT NULL,
  `end_at` datetime NOT NULL,
  `activation_fee` decimal(18,2) NOT NULL DEFAULT '0.00',
  `reward_min` decimal(18,2) NOT NULL,
  `reward_max` decimal(18,2) NOT NULL,
  `status` enum('DRAFT','ACTIVE','INACTIVE','ENDED') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'DRAFT',
  `created_by` int unsigned NOT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `slug` (`slug`),
  KEY `idx_hunts_status_dates` (`status`,`start_at`,`end_at`),
  KEY `created_by` (`created_by`),
  CONSTRAINT `hunts_ibfk_1` FOREIGN KEY (`created_by`) REFERENCES `admins` (`id`),
  CONSTRAINT `hunts_chk_1` CHECK ((`end_at` > `start_at`)),
  CONSTRAINT `hunts_chk_2` CHECK ((`activation_fee` >= 0)),
  CONSTRAINT `hunts_chk_3` CHECK ((`reward_min` > 0)),
  CONSTRAINT `hunts_chk_4` CHECK ((`reward_max` >= `reward_min`))
) ENGINE=InnoDB AUTO_INCREMENT=18 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `hunts`
--

LOCK TABLES `hunts` WRITE;
/*!40000 ALTER TABLE `hunts` DISABLE KEYS */;
INSERT INTO `hunts` VALUES (1,'Integration Hunt','integration-hunt','Test','Rules',NULL,NULL,NULL,25.5940950,85.1375660,20,NULL,'2026-08-22 19:08:22','2026-08-25 19:08:22',100.00,500.00,500.00,'ACTIVE',1,'2026-08-23 19:08:22','2026-08-23 19:08:22'),(2,'QR Campaign Hunt','qr-campaign-hunt',NULL,NULL,NULL,NULL,NULL,25.5940950,85.1375660,20,NULL,'2026-08-23 18:08:23','2026-08-23 20:08:23',0.00,1.00,1.00,'ACTIVE',1,'2026-08-23 19:08:23','2026-08-23 19:08:23'),(3,'Demo City Treasure Hunt','demo-city-hunt','Seeded campaign for testing the complete admin workflow.','One scan per device.',NULL,NULL,NULL,25.5940950,85.1375660,20,NULL,'2026-08-28 11:29:07','2026-09-28 11:29:07',0.00,15.00,500.00,'ACTIVE',1,'2026-08-29 11:29:07','2026-08-29 11:29:07'),(4,'QR Campaign Hunt','qr-hunt-b59f52db',NULL,NULL,NULL,NULL,NULL,25.5940950,85.1375660,20,NULL,'2026-10-17 17:36:24','2026-10-17 19:36:24',0.00,1.00,1.00,'ACTIVE',1,'2026-10-17 18:36:24','2026-10-17 18:36:24'),(5,'Integration Hunt','hunt-8ce8ea53','Test','Rules',NULL,NULL,NULL,25.5940950,85.1375660,20,NULL,'2026-10-16 18:37:51','2026-10-19 18:37:51',100.00,500.00,500.00,'ACTIVE',4,'2026-10-17 18:37:51','2026-10-17 18:37:51'),(6,'Integration Hunt','hunt-129b73ae','Test','Rules',NULL,NULL,NULL,25.5940950,85.1375660,20,NULL,'2026-10-16 18:38:34','2026-10-19 18:38:34',100.00,500.00,500.00,'ACTIVE',5,'2026-10-17 18:38:34','2026-10-17 18:38:34'),(7,'Integration Hunt','hunt-1bac2a6b','Test','Rules',NULL,NULL,NULL,25.5940950,85.1375660,20,NULL,'2026-10-16 18:38:56','2026-10-19 18:38:56',100.00,500.00,500.00,'ACTIVE',6,'2026-10-17 18:38:56','2026-10-17 18:38:56'),(8,'QR Campaign Hunt','qr-hunt-93bd279a',NULL,NULL,NULL,NULL,NULL,25.5940950,85.1375660,20,NULL,'2026-10-17 18:57:16','2026-10-17 20:57:16',0.00,1.00,1.00,'ACTIVE',1,'2026-10-17 19:57:16','2026-10-17 19:57:16'),(9,'Integration Hunt','hunt-97790886','Test','Rules',NULL,NULL,NULL,25.5940950,85.1375660,20,NULL,'2026-10-16 19:57:24','2026-10-19 19:57:24',100.00,500.00,500.00,'ACTIVE',8,'2026-10-17 19:57:24','2026-10-17 19:57:24'),(10,'QR Campaign Hunt','qr-hunt-84aaf380',NULL,NULL,NULL,NULL,NULL,25.5940950,85.1375660,20,NULL,'2026-10-17 20:21:24','2026-10-17 22:21:24',0.00,1.00,1.00,'ACTIVE',1,'2026-10-17 21:21:24','2026-10-17 21:21:24'),(11,'Integration Hunt','hunt-6415b496','Test','Rules',NULL,NULL,NULL,25.5940950,85.1375660,20,NULL,'2026-10-16 21:21:25','2026-10-19 21:21:25',100.00,500.00,500.00,'ACTIVE',9,'2026-10-17 21:21:25','2026-10-17 21:21:25'),(12,'QR Campaign Hunt','qr-hunt-eefa6591',NULL,NULL,NULL,NULL,NULL,25.5940950,85.1375660,20,NULL,'2026-10-17 20:38:32','2026-10-17 22:38:32',0.00,1.00,1.00,'ACTIVE',1,'2026-10-17 21:38:32','2026-10-17 21:38:32'),(13,'Integration Hunt','hunt-d1411073','Test','Rules',NULL,NULL,NULL,25.5940950,85.1375660,20,NULL,'2026-10-16 21:38:33','2026-10-19 21:38:33',100.00,500.00,500.00,'ACTIVE',14,'2026-10-17 21:38:33','2026-10-17 21:38:33'),(14,'QR Campaign Hunt','qr-hunt-c4b8a794',NULL,NULL,NULL,NULL,NULL,25.5940950,85.1375660,20,NULL,'2026-10-17 21:51:23','2026-10-17 23:51:23',0.00,1.00,1.00,'ACTIVE',1,'2026-10-17 22:51:23','2026-10-17 22:51:23'),(15,'Integration Hunt','hunt-3439d2e2','Test','Rules',NULL,NULL,NULL,25.5940950,85.1375660,20,NULL,'2026-10-16 22:51:24','2026-10-19 22:51:24',100.00,500.00,500.00,'ACTIVE',16,'2026-10-17 22:51:24','2026-10-17 22:51:24'),(16,'QR Campaign Hunt','qr-hunt-8d4dc97f',NULL,NULL,NULL,NULL,NULL,25.5940950,85.1375660,20,NULL,'2026-10-18 08:25:17','2026-10-18 10:25:17',0.00,1.00,1.00,'ACTIVE',1,'2026-10-18 09:25:17','2026-10-18 09:25:17'),(17,'Integration Hunt','hunt-86f7090e','Test','Rules',NULL,NULL,NULL,25.5940950,85.1375660,20,NULL,'2026-10-17 09:25:18','2026-10-20 09:25:18',100.00,500.00,500.00,'ACTIVE',18,'2026-10-18 09:25:18','2026-10-18 09:25:18');
/*!40000 ALTER TABLE `hunts` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `login_attempts`
--

DROP TABLE IF EXISTS `login_attempts`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `login_attempts` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `identity_hash` char(64) COLLATE utf8mb4_unicode_ci NOT NULL,
  `ip_address` varchar(45) COLLATE utf8mb4_unicode_ci NOT NULL,
  `successful` tinyint(1) NOT NULL DEFAULT '0',
  `attempted_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_login_ip_date` (`ip_address`,`attempted_at`)
) ENGINE=InnoDB AUTO_INCREMENT=57 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `login_attempts`
--

LOCK TABLES `login_attempts` WRITE;
/*!40000 ALTER TABLE `login_attempts` DISABLE KEYS */;
INSERT INTO `login_attempts` VALUES (1,'eb92cc52a766c500e001d02f1eaa78784da47f8c96b019ce874d3e3c205b4e3c','127.0.0.1',1,'2026-08-23 19:09:00'),(2,'8c6976e5b5410415bde908bd4dee15dfb167a9c873fc4bb8a81f6f2ab448a918','::1',0,'2026-08-23 19:12:42'),(3,'8c6976e5b5410415bde908bd4dee15dfb167a9c873fc4bb8a81f6f2ab448a918','::1',0,'2026-08-23 19:12:54'),(4,'8c6976e5b5410415bde908bd4dee15dfb167a9c873fc4bb8a81f6f2ab448a918','::1',0,'2026-08-23 19:13:12'),(5,'eb92cc52a766c500e001d02f1eaa78784da47f8c96b019ce874d3e3c205b4e3c','::1',1,'2026-08-23 19:18:45'),(6,'eb92cc52a766c500e001d02f1eaa78784da47f8c96b019ce874d3e3c205b4e3c','::1',1,'2026-08-23 20:21:24'),(7,'eb92cc52a766c500e001d02f1eaa78784da47f8c96b019ce874d3e3c205b4e3c','::1',1,'2026-08-23 20:55:26'),(8,'eb92cc52a766c500e001d02f1eaa78784da47f8c96b019ce874d3e3c205b4e3c','::1',1,'2026-08-23 21:26:32'),(9,'eb92cc52a766c500e001d02f1eaa78784da47f8c96b019ce874d3e3c205b4e3c','::1',1,'2026-08-24 21:03:53'),(10,'eb92cc52a766c500e001d02f1eaa78784da47f8c96b019ce874d3e3c205b4e3c','::1',1,'2026-08-29 11:25:08'),(11,'eb92cc52a766c500e001d02f1eaa78784da47f8c96b019ce874d3e3c205b4e3c','::1',1,'2026-08-29 12:53:26'),(12,'eb92cc52a766c500e001d02f1eaa78784da47f8c96b019ce874d3e3c205b4e3c','::1',1,'2026-08-29 13:23:13'),(13,'eb92cc52a766c500e001d02f1eaa78784da47f8c96b019ce874d3e3c205b4e3c','::1',1,'2026-08-29 17:34:00'),(14,'eb92cc52a766c500e001d02f1eaa78784da47f8c96b019ce874d3e3c205b4e3c','::1',1,'2026-08-30 13:34:58'),(15,'eb92cc52a766c500e001d02f1eaa78784da47f8c96b019ce874d3e3c205b4e3c','::1',1,'2026-08-30 15:34:46'),(16,'eb92cc52a766c500e001d02f1eaa78784da47f8c96b019ce874d3e3c205b4e3c','::1',1,'2026-08-30 16:30:11'),(17,'eb92cc52a766c500e001d02f1eaa78784da47f8c96b019ce874d3e3c205b4e3c','::1',1,'2026-09-01 16:55:34'),(18,'eb92cc52a766c500e001d02f1eaa78784da47f8c96b019ce874d3e3c205b4e3c','::1',1,'2026-09-01 18:57:11'),(19,'eb92cc52a766c500e001d02f1eaa78784da47f8c96b019ce874d3e3c205b4e3c','::1',1,'2026-09-03 21:21:48'),(20,'eb92cc52a766c500e001d02f1eaa78784da47f8c96b019ce874d3e3c205b4e3c','::1',1,'2026-09-06 14:04:32'),(21,'eb92cc52a766c500e001d02f1eaa78784da47f8c96b019ce874d3e3c205b4e3c','127.0.0.1',1,'2026-09-11 18:32:28'),(22,'eb92cc52a766c500e001d02f1eaa78784da47f8c96b019ce874d3e3c205b4e3c','127.0.0.1',1,'2026-09-11 19:16:02'),(23,'eb92cc52a766c500e001d02f1eaa78784da47f8c96b019ce874d3e3c205b4e3c','127.0.0.1',1,'2026-09-11 21:41:20'),(24,'eb92cc52a766c500e001d02f1eaa78784da47f8c96b019ce874d3e3c205b4e3c','127.0.0.1',1,'2026-09-11 21:47:18'),(25,'eb92cc52a766c500e001d02f1eaa78784da47f8c96b019ce874d3e3c205b4e3c','127.0.0.1',1,'2026-09-12 16:12:54'),(26,'eb92cc52a766c500e001d02f1eaa78784da47f8c96b019ce874d3e3c205b4e3c','127.0.0.1',1,'2026-09-13 10:48:00'),(27,'eb92cc52a766c500e001d02f1eaa78784da47f8c96b019ce874d3e3c205b4e3c','127.0.0.1',1,'2026-09-13 14:17:57'),(28,'e3b0c44298fc1c149afbf4c8996fb92427ae41e4649b934ca495991b7852b855','127.0.0.1',0,'2026-09-13 15:47:06'),(29,'4fe686dee93e405cf724a6de299e1a1fe906e5a147dfc46d414048d4b1738f21','127.0.0.1',0,'2026-09-13 15:50:20'),(30,'eb92cc52a766c500e001d02f1eaa78784da47f8c96b019ce874d3e3c205b4e3c','127.0.0.1',1,'2026-09-13 16:47:15'),(31,'186cf774c97b60a1c106ef718d10970a6a06e06bef89553d9ae65d938a886eae','127.0.0.1',1,'2026-10-17 18:58:15'),(32,'6e52df6458cfbfd348f7332a5e910b4d06f64764601a442297325b858a0cf121','127.0.0.1',1,'2026-10-17 18:58:32'),(33,'186cf774c97b60a1c106ef718d10970a6a06e06bef89553d9ae65d938a886eae','127.0.0.1',1,'2026-10-17 18:59:11'),(34,'186cf774c97b60a1c106ef718d10970a6a06e06bef89553d9ae65d938a886eae','127.0.0.1',1,'2026-10-17 18:59:29'),(35,'186cf774c97b60a1c106ef718d10970a6a06e06bef89553d9ae65d938a886eae','127.0.0.1',1,'2026-10-17 19:36:34'),(36,'186cf774c97b60a1c106ef718d10970a6a06e06bef89553d9ae65d938a886eae','127.0.0.1',1,'2026-10-17 19:53:00'),(37,'186cf774c97b60a1c106ef718d10970a6a06e06bef89553d9ae65d938a886eae','127.0.0.1',1,'2026-10-17 19:54:32'),(38,'186cf774c97b60a1c106ef718d10970a6a06e06bef89553d9ae65d938a886eae','127.0.0.1',1,'2026-10-17 19:55:32'),(39,'186cf774c97b60a1c106ef718d10970a6a06e06bef89553d9ae65d938a886eae','127.0.0.1',1,'2026-10-17 19:56:01'),(40,'186cf774c97b60a1c106ef718d10970a6a06e06bef89553d9ae65d938a886eae','127.0.0.1',1,'2026-10-17 19:56:24'),(41,'6e52df6458cfbfd348f7332a5e910b4d06f64764601a442297325b858a0cf121','127.0.0.1',1,'2026-10-17 19:58:00'),(42,'186cf774c97b60a1c106ef718d10970a6a06e06bef89553d9ae65d938a886eae','127.0.0.1',1,'2026-10-17 20:12:38'),(43,'186cf774c97b60a1c106ef718d10970a6a06e06bef89553d9ae65d938a886eae','127.0.0.1',1,'2026-10-17 20:18:16'),(44,'186cf774c97b60a1c106ef718d10970a6a06e06bef89553d9ae65d938a886eae','127.0.0.1',1,'2026-10-17 20:24:34'),(45,'186cf774c97b60a1c106ef718d10970a6a06e06bef89553d9ae65d938a886eae','127.0.0.1',1,'2026-10-17 20:26:46'),(46,'186cf774c97b60a1c106ef718d10970a6a06e06bef89553d9ae65d938a886eae','127.0.0.1',1,'2026-10-17 21:21:31'),(47,'186cf774c97b60a1c106ef718d10970a6a06e06bef89553d9ae65d938a886eae','127.0.0.1',1,'2026-10-17 21:37:56'),(48,'186cf774c97b60a1c106ef718d10970a6a06e06bef89553d9ae65d938a886eae','127.0.0.1',1,'2026-10-17 21:38:18'),(49,'186cf774c97b60a1c106ef718d10970a6a06e06bef89553d9ae65d938a886eae','127.0.0.1',1,'2026-10-17 21:44:16'),(50,'186cf774c97b60a1c106ef718d10970a6a06e06bef89553d9ae65d938a886eae','127.0.0.1',1,'2026-10-17 21:44:28'),(51,'186cf774c97b60a1c106ef718d10970a6a06e06bef89553d9ae65d938a886eae','127.0.0.1',1,'2026-10-17 21:45:18'),(52,'186cf774c97b60a1c106ef718d10970a6a06e06bef89553d9ae65d938a886eae','127.0.0.1',1,'2026-10-17 22:51:14'),(53,'186cf774c97b60a1c106ef718d10970a6a06e06bef89553d9ae65d938a886eae','127.0.0.1',1,'2026-10-17 22:51:31'),(54,'186cf774c97b60a1c106ef718d10970a6a06e06bef89553d9ae65d938a886eae','127.0.0.1',0,'2026-10-17 23:13:35'),(55,'186cf774c97b60a1c106ef718d10970a6a06e06bef89553d9ae65d938a886eae','127.0.0.1',0,'2026-10-17 23:13:57'),(56,'186cf774c97b60a1c106ef718d10970a6a06e06bef89553d9ae65d938a886eae','127.0.0.1',1,'2026-10-18 09:53:10');
/*!40000 ALTER TABLE `login_attempts` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `merchant_settlements`
--

DROP TABLE IF EXISTS `merchant_settlements`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `merchant_settlements` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `settlement_id` varchar(60) COLLATE utf8mb4_unicode_ci NOT NULL,
  `checkout_id` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `merchant_id` bigint unsigned NOT NULL,
  `bill_amount` decimal(18,2) NOT NULL,
  `promo_amount` decimal(18,2) NOT NULL DEFAULT '0.00',
  `customer_paid` decimal(18,2) NOT NULL DEFAULT '0.00',
  `platform_fee` decimal(18,2) NOT NULL DEFAULT '0.00',
  `merchant_amount` decimal(18,2) NOT NULL DEFAULT '0.00',
  `status` enum('PENDING','SETTLED','REVERSED') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'PENDING',
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `settled_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `settlement_id` (`settlement_id`),
  UNIQUE KEY `checkout_id` (`checkout_id`),
  KEY `merchant_id` (`merchant_id`),
  KEY `idx_settlement_status` (`status`,`created_at`),
  CONSTRAINT `merchant_settlements_ibfk_1` FOREIGN KEY (`merchant_id`) REFERENCES `vendors` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `merchant_settlements`
--

LOCK TABLES `merchant_settlements` WRITE;
/*!40000 ALTER TABLE `merchant_settlements` DISABLE KEYS */;
INSERT INTO `merchant_settlements` VALUES (1,'SET-33587bf2','CHK-7e9b9bd2',3,1499.00,100.00,1399.00,29.98,1469.02,'SETTLED','2026-10-18 09:50:53','2026-10-18 09:50:53'),(2,'SET-b8b5a1ac','CHK-f1c609a3',3,2299.00,200.00,2099.00,45.98,2253.02,'SETTLED','2026-10-18 09:50:53','2026-10-18 09:50:53'),(3,'SET-5ae38948','CHK-6b5864b2',3,350.00,50.00,300.00,7.00,343.00,'SETTLED','2026-10-18 09:50:53','2026-10-18 09:50:53');
/*!40000 ALTER TABLE `merchant_settlements` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `notification_logs`
--

DROP TABLE IF EXISTS `notification_logs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `notification_logs` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `notification_id` bigint unsigned NOT NULL,
  `provider` varchar(80) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `provider_reference` varchar(190) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `status` varchar(40) COLLATE utf8mb4_unicode_ci NOT NULL,
  `response_summary` varchar(500) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_notification_log` (`notification_id`,`created_at`),
  CONSTRAINT `notification_logs_ibfk_1` FOREIGN KEY (`notification_id`) REFERENCES `notifications` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `notification_logs`
--

LOCK TABLES `notification_logs` WRITE;
/*!40000 ALTER TABLE `notification_logs` DISABLE KEYS */;
/*!40000 ALTER TABLE `notification_logs` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `notifications`
--

DROP TABLE IF EXISTS `notifications`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `notifications` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint unsigned DEFAULT NULL,
  `type` varchar(80) COLLATE utf8mb4_unicode_ci NOT NULL,
  `channel` enum('PUSH','SMS','EMAIL','IN_APP') COLLATE utf8mb4_unicode_ci NOT NULL,
  `title` varchar(190) COLLATE utf8mb4_unicode_ci NOT NULL,
  `message` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `status` enum('QUEUED','SENT','FAILED','READ') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'QUEUED',
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `sent_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`),
  CONSTRAINT `notifications_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `notifications`
--

LOCK TABLES `notifications` WRITE;
/*!40000 ALTER TABLE `notifications` DISABLE KEYS */;
INSERT INTO `notifications` VALUES (1,1,'DEMO','IN_APP','Welcome to Hunter Wallet','Your demo wallet and campaign are ready.','QUEUED','2026-08-29 11:29:07',NULL),(2,1,'DEMO','IN_APP','Welcome to Hunter Wallet','Your demo wallet and campaign are ready.','QUEUED','2026-08-29 11:58:00',NULL);
/*!40000 ALTER TABLE `notifications` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `payment_transactions`
--

DROP TABLE IF EXISTS `payment_transactions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `payment_transactions` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `payment_id` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `user_id` bigint unsigned NOT NULL,
  `hunt_id` bigint unsigned NOT NULL,
  `provider` enum('RAZORPAY','PHONEPE','COUNTER') COLLATE utf8mb4_unicode_ci NOT NULL,
  `provider_order_id` varchar(190) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `provider_payment_id` varchar(190) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `amount` decimal(18,2) NOT NULL,
  `currency` char(3) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'INR',
  `status` enum('CREATED','PENDING','VERIFIED','FAILED','REFUNDED') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'CREATED',
  `idempotency_key` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `signature_verified_at` datetime DEFAULT NULL,
  `failure_reason` varchar(500) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `payment_id` (`payment_id`),
  UNIQUE KEY `uq_payment_user_key` (`user_id`,`idempotency_key`),
  UNIQUE KEY `uq_provider_payment` (`provider`,`provider_payment_id`),
  KEY `idx_payment_hunt_status` (`hunt_id`,`status`),
  CONSTRAINT `payment_transactions_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`),
  CONSTRAINT `payment_transactions_ibfk_2` FOREIGN KEY (`hunt_id`) REFERENCES `hunts` (`id`),
  CONSTRAINT `payment_transactions_chk_1` CHECK ((`amount` >= 0))
) ENGINE=InnoDB AUTO_INCREMENT=10 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `payment_transactions`
--

LOCK TABLES `payment_transactions` WRITE;
/*!40000 ALTER TABLE `payment_transactions` DISABLE KEYS */;
INSERT INTO `payment_transactions` VALUES (1,'PAY-20260823-4D6276ED',2,1,'RAZORPAY','HUNTER-PAY-20260823-4D6276ED','provider-pay-1',100.00,'INR','VERIFIED','payment-integration-key-0001','2026-08-23 19:08:23',NULL,'2026-08-23 19:08:23','2026-08-23 19:08:23'),(2,'PAY-20261017-5EFB5DEF',8,5,'RAZORPAY','HUNTER-PAY-20261017-5EFB5DEF',NULL,100.00,'INR','PENDING','pay-key-8ce8ea53',NULL,NULL,'2026-10-17 18:37:52','2026-10-17 18:37:52'),(3,'PAY-20261017-B663FE68',10,6,'RAZORPAY','HUNTER-PAY-20261017-B663FE68',NULL,100.00,'INR','PENDING','pay-key-129b73ae',NULL,NULL,'2026-10-17 18:38:35','2026-10-17 18:38:35'),(4,'PAY-20261017-6E0CDF8F',12,7,'RAZORPAY','HUNTER-PAY-20261017-6E0CDF8F','provider-pay-1bac2a6b',100.00,'INR','VERIFIED','pay-key-1bac2a6b','2026-10-17 18:38:57',NULL,'2026-10-17 18:38:57','2026-10-17 18:38:57'),(5,'PAY-20261017-80A971C4',16,9,'RAZORPAY','HUNTER-PAY-20261017-80A971C4','provider-pay-97790886',100.00,'INR','VERIFIED','pay-key-97790886','2026-10-17 19:57:25',NULL,'2026-10-17 19:57:25','2026-10-17 19:57:25'),(6,'PAY-20261017-F19886DF',19,11,'RAZORPAY','HUNTER-PAY-20261017-F19886DF','provider-pay-6415b496',100.00,'INR','VERIFIED','pay-key-6415b496','2026-10-17 21:21:26',NULL,'2026-10-17 21:21:26','2026-10-17 21:21:26'),(7,'PAY-20261017-A95839D8',26,13,'RAZORPAY','HUNTER-PAY-20261017-A95839D8','provider-pay-d1411073',100.00,'INR','VERIFIED','pay-key-d1411073','2026-10-17 21:38:34',NULL,'2026-10-17 21:38:34','2026-10-17 21:38:34'),(8,'PAY-20261017-85075765',30,15,'RAZORPAY','HUNTER-PAY-20261017-85075765','provider-pay-3439d2e2',100.00,'INR','VERIFIED','pay-key-3439d2e2','2026-10-17 22:51:25',NULL,'2026-10-17 22:51:25','2026-10-17 22:51:25'),(9,'PAY-20261018-506FC994',34,17,'RAZORPAY','HUNTER-PAY-20261018-506FC994','provider-pay-86f7090e',100.00,'INR','VERIFIED','pay-key-86f7090e','2026-10-18 09:25:18',NULL,'2026-10-18 09:25:18','2026-10-18 09:25:18');
/*!40000 ALTER TABLE `payment_transactions` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `payment_webhook_events`
--

DROP TABLE IF EXISTS `payment_webhook_events`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `payment_webhook_events` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `provider` enum('RAZORPAY','PHONEPE') COLLATE utf8mb4_unicode_ci NOT NULL,
  `event_id` varchar(190) COLLATE utf8mb4_unicode_ci NOT NULL,
  `signature_hash` char(64) COLLATE utf8mb4_unicode_ci NOT NULL,
  `payload_hash` char(64) COLLATE utf8mb4_unicode_ci NOT NULL,
  `payment_transaction_id` bigint unsigned DEFAULT NULL,
  `status` enum('ACCEPTED','IGNORED','FAILED') COLLATE utf8mb4_unicode_ci NOT NULL,
  `received_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_payment_event` (`provider`,`event_id`),
  KEY `payment_transaction_id` (`payment_transaction_id`),
  CONSTRAINT `payment_webhook_events_ibfk_1` FOREIGN KEY (`payment_transaction_id`) REFERENCES `payment_transactions` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=8 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `payment_webhook_events`
--

LOCK TABLES `payment_webhook_events` WRITE;
/*!40000 ALTER TABLE `payment_webhook_events` DISABLE KEYS */;
INSERT INTO `payment_webhook_events` VALUES (1,'RAZORPAY','evt-integration-1','e5669fed65bb9d53cbbdba846db165500a952bc5ae7ffe723e7faf2595fae936','2cebbe1229c01297316b991cba67d98295254d8eb3ebcf8babbe50f36c005481',1,'ACCEPTED','2026-08-23 19:08:23'),(2,'RAZORPAY','evt-1bac2a6b','50b6a54f300924525ef786d694eeecee752043a8b43000e17039735ef18d8b08','dce7c03efc5105151c47959e82d6c5a0149e9c01b1229037f707e0e7552420c8',4,'ACCEPTED','2026-10-17 18:38:57'),(3,'RAZORPAY','evt-97790886','fa83d336005c92cebf69377610820365e09f851ad90698429a829731b102fd37','7ffb8404e61d3f98249af0dc608e2d731c0920cd6018a9a784fb8738daec51fa',5,'ACCEPTED','2026-10-17 19:57:25'),(4,'RAZORPAY','evt-6415b496','7ba2bd4f1572e3c06f08a0228e8a519f79ebc3dbe61293c9fa08905627ed9cf6','3f97168c03857f7b66953a1f2abe5cc6fe708ba922e24cb6f3c9f12af97d6410',6,'ACCEPTED','2026-10-17 21:21:26'),(5,'RAZORPAY','evt-d1411073','9a9bdd33afa812f6b107ae111fb0e24ff44805fd18873259ddfea022c3df207c','02f5e38a0b42e1a8a56cdf3349bf9a3c4f5b4ee4a6423fe65c12109f92eff275',7,'ACCEPTED','2026-10-17 21:38:34'),(6,'RAZORPAY','evt-3439d2e2','b541edace96e72d34006b1ec2d13a8b654a65bb752295ed3506042f011cdaac9','8d1b6ac0dfed45d4a8e1a5fd1a4a13003e067125406e2f6c87720dd70fea8b01',8,'ACCEPTED','2026-10-17 22:51:25'),(7,'RAZORPAY','evt-86f7090e','7fe01711cf9e18547af6af58dfbb24d83582169252d86c568038c44e3c959638','5f754efca559ca2d21c55eff4df1f76dc50b6e9eceea7848d42e4eb4f7e062a1',9,'ACCEPTED','2026-10-18 09:25:18');
/*!40000 ALTER TABLE `payment_webhook_events` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `product_audit_log`
--

DROP TABLE IF EXISTS `product_audit_log`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `product_audit_log` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `product_id` bigint unsigned NOT NULL,
  `product_code` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `vendor_id` bigint unsigned NOT NULL,
  `action` varchar(60) COLLATE utf8mb4_unicode_ci NOT NULL,
  `field_name` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `old_value` text COLLATE utf8mb4_unicode_ci,
  `new_value` text COLLATE utf8mb4_unicode_ci,
  `reason` varchar(500) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `admin_id` int unsigned DEFAULT NULL,
  `user_id` bigint unsigned DEFAULT NULL,
  `ip_address` varchar(45) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `user_agent` text COLLATE utf8mb4_unicode_ci,
  `status` enum('PENDING','COMPLETED','FAILED') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'COMPLETED',
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_product_audit_product` (`product_id`,`created_at`),
  KEY `idx_product_audit_vendor` (`vendor_id`,`created_at`),
  KEY `idx_product_audit_action` (`action`,`created_at`),
  KEY `idx_product_audit_admin` (`admin_id`,`created_at`),
  KEY `user_id` (`user_id`),
  CONSTRAINT `product_audit_log_ibfk_1` FOREIGN KEY (`product_id`) REFERENCES `vendor_products` (`id`) ON DELETE CASCADE,
  CONSTRAINT `product_audit_log_ibfk_2` FOREIGN KEY (`vendor_id`) REFERENCES `vendors` (`id`) ON DELETE CASCADE,
  CONSTRAINT `product_audit_log_ibfk_3` FOREIGN KEY (`admin_id`) REFERENCES `admins` (`id`) ON DELETE SET NULL,
  CONSTRAINT `product_audit_log_ibfk_4` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `product_audit_log`
--

LOCK TABLES `product_audit_log` WRITE;
/*!40000 ALTER TABLE `product_audit_log` DISABLE KEYS */;
/*!40000 ALTER TABLE `product_audit_log` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `product_categories`
--

DROP TABLE IF EXISTS `product_categories`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `product_categories` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(120) COLLATE utf8mb4_unicode_ci NOT NULL,
  `code` varchar(30) COLLATE utf8mb4_unicode_ci NOT NULL,
  `icon_url` varchar(500) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `status` enum('ACTIVE','INACTIVE') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'ACTIVE',
  `description` text COLLATE utf8mb4_unicode_ci,
  `sort_order` int unsigned NOT NULL DEFAULT '0',
  `created_by` int unsigned DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `code` (`code`),
  KEY `created_by` (`created_by`),
  KEY `idx_product_cat_status` (`status`),
  KEY `idx_product_cat_code` (`code`),
  CONSTRAINT `product_categories_ibfk_1` FOREIGN KEY (`created_by`) REFERENCES `admins` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=11 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `product_categories`
--

LOCK TABLES `product_categories` WRITE;
/*!40000 ALTER TABLE `product_categories` DISABLE KEYS */;
INSERT INTO `product_categories` VALUES (1,'Electronics','ELECTRONICS',NULL,'ACTIVE','Electronic devices and accessories',10,NULL,'2026-09-13 12:04:51','2026-09-13 12:04:51'),(2,'Clothing & Apparel','CLOTHING',NULL,'ACTIVE','Fashion, clothing and wearables',20,NULL,'2026-09-13 12:04:51','2026-09-13 12:04:51'),(3,'Food & Beverages','FOOD',NULL,'ACTIVE','Food items, beverages and groceries',30,NULL,'2026-09-13 12:04:51','2026-09-13 12:04:51'),(4,'Health & Beauty','HEALTH_BEAUTY',NULL,'ACTIVE','Healthcare, cosmetics and personal care',40,NULL,'2026-09-13 12:04:51','2026-09-13 12:04:51'),(5,'Home & Garden','HOME_GARDEN',NULL,'ACTIVE','Home decor, furniture and garden supplies',50,NULL,'2026-09-13 12:04:51','2026-09-13 12:04:51'),(6,'Sports & Outdoors','SPORTS',NULL,'ACTIVE','Sports equipment and outdoor gear',60,NULL,'2026-09-13 12:04:51','2026-09-13 12:04:51'),(7,'Books & Media','BOOKS_MEDIA',NULL,'ACTIVE','Books, digital media and entertainment',70,NULL,'2026-09-13 12:04:51','2026-09-13 12:04:51'),(8,'Automotive','AUTOMOTIVE',NULL,'ACTIVE','Vehicle parts, accessories and tools',80,NULL,'2026-09-13 12:04:51','2026-09-13 12:04:51'),(9,'Toys & Games','TOYS_GAMES',NULL,'ACTIVE','Toys, games and children products',90,NULL,'2026-09-13 12:04:51','2026-09-13 12:04:51'),(10,'Other','OTHER',NULL,'ACTIVE','Miscellaneous products',999,NULL,'2026-09-13 12:04:51','2026-09-13 12:04:51');
/*!40000 ALTER TABLE `product_categories` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `product_qr_codes`
--

DROP TABLE IF EXISTS `product_qr_codes`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `product_qr_codes` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `qr_code` varchar(64) COLLATE utf8mb4_unicode_ci NOT NULL,
  `vendor_id` bigint unsigned NOT NULL,
  `product_id` bigint unsigned NOT NULL,
  `product_code` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `payload` json NOT NULL,
  `status` enum('ACTIVE','REGENERATED','ARCHIVED') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'ACTIVE',
  `generated_by` int unsigned DEFAULT NULL,
  `regenerated_at` datetime DEFAULT NULL,
  `regenerated_by` int unsigned DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `qr_code` (`qr_code`),
  KEY `idx_product_qr_product` (`product_id`),
  KEY `idx_product_qr_vendor` (`vendor_id`),
  KEY `idx_product_qr_code` (`qr_code`),
  KEY `generated_by` (`generated_by`),
  KEY `regenerated_by` (`regenerated_by`),
  CONSTRAINT `product_qr_codes_ibfk_1` FOREIGN KEY (`product_id`) REFERENCES `vendor_products` (`id`) ON DELETE CASCADE,
  CONSTRAINT `product_qr_codes_ibfk_2` FOREIGN KEY (`vendor_id`) REFERENCES `vendors` (`id`) ON DELETE CASCADE,
  CONSTRAINT `product_qr_codes_ibfk_3` FOREIGN KEY (`generated_by`) REFERENCES `admins` (`id`) ON DELETE SET NULL,
  CONSTRAINT `product_qr_codes_ibfk_4` FOREIGN KEY (`regenerated_by`) REFERENCES `admins` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `product_qr_codes`
--

LOCK TABLES `product_qr_codes` WRITE;
/*!40000 ALTER TABLE `product_qr_codes` DISABLE KEYS */;
/*!40000 ALTER TABLE `product_qr_codes` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `product_upload_batches`
--

DROP TABLE IF EXISTS `product_upload_batches`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `product_upload_batches` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `batch_code` varchar(32) COLLATE utf8mb4_unicode_ci NOT NULL,
  `vendor_id` bigint unsigned NOT NULL,
  `admin_id` int unsigned DEFAULT NULL,
  `filename` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `total_rows` int unsigned NOT NULL DEFAULT '0',
  `success_rows` int unsigned NOT NULL DEFAULT '0',
  `failed_rows` int unsigned NOT NULL DEFAULT '0',
  `status` enum('PENDING','PROCESSING','COMPLETED','FAILED') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'PENDING',
  `error_summary` json DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `completed_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `batch_code` (`batch_code`),
  KEY `idx_batch_vendor` (`vendor_id`,`created_at`),
  KEY `idx_batch_admin` (`admin_id`,`created_at`),
  KEY `idx_batch_status` (`status`),
  CONSTRAINT `product_upload_batches_ibfk_1` FOREIGN KEY (`vendor_id`) REFERENCES `vendors` (`id`) ON DELETE CASCADE,
  CONSTRAINT `product_upload_batches_ibfk_2` FOREIGN KEY (`admin_id`) REFERENCES `admins` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `product_upload_batches`
--

LOCK TABLES `product_upload_batches` WRITE;
/*!40000 ALTER TABLE `product_upload_batches` DISABLE KEYS */;
/*!40000 ALTER TABLE `product_upload_batches` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `product_upload_records`
--

DROP TABLE IF EXISTS `product_upload_records`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `product_upload_records` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `batch_id` bigint unsigned NOT NULL,
  `row_num` int unsigned NOT NULL,
  `product_code` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `title` varchar(190) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `status` enum('SUCCESS','FAILED','SKIPPED') COLLATE utf8mb4_unicode_ci NOT NULL,
  `error_message` text COLLATE utf8mb4_unicode_ci,
  `product_id` bigint unsigned DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_upload_batch` (`batch_id`,`row_num`),
  KEY `idx_upload_product` (`product_id`),
  CONSTRAINT `product_upload_records_ibfk_1` FOREIGN KEY (`batch_id`) REFERENCES `product_upload_batches` (`id`) ON DELETE CASCADE,
  CONSTRAINT `product_upload_records_ibfk_2` FOREIGN KEY (`product_id`) REFERENCES `vendor_products` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `product_upload_records`
--

LOCK TABLES `product_upload_records` WRITE;
/*!40000 ALTER TABLE `product_upload_records` DISABLE KEYS */;
/*!40000 ALTER TABLE `product_upload_records` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `qr_batch_items`
--

DROP TABLE IF EXISTS `qr_batch_items`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `qr_batch_items` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `batch_id` bigint unsigned NOT NULL,
  `spot_id` varchar(80) COLLATE utf8mb4_unicode_ci NOT NULL,
  `spot_name` varchar(190) COLLATE utf8mb4_unicode_ci NOT NULL,
  `category_tag` varchar(120) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `riddle_text` text COLLATE utf8mb4_unicode_ci,
  `youtube_video_id` varchar(40) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `correct_answer_hash` char(64) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `reward_type` varchar(40) COLLATE utf8mb4_unicode_ci NOT NULL,
  `reward_value` decimal(18,2) NOT NULL DEFAULT '0.00',
  `timer_ttl_minutes` int unsigned DEFAULT NULL,
  `qr_code_id` bigint unsigned DEFAULT NULL,
  `encrypted_url` varchar(500) COLLATE utf8mb4_unicode_ci NOT NULL,
  `status` enum('ACTIVE','PAUSED','DEACTIVATED','SCANNED') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'ACTIVE',
  `scanned_count` int unsigned NOT NULL DEFAULT '0',
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `spot_id` (`spot_id`),
  KEY `idx_qr_batch_item` (`batch_id`,`status`),
  KEY `qr_code_id` (`qr_code_id`),
  CONSTRAINT `qr_batch_items_ibfk_1` FOREIGN KEY (`batch_id`) REFERENCES `qr_batches` (`id`) ON DELETE CASCADE,
  CONSTRAINT `qr_batch_items_ibfk_2` FOREIGN KEY (`qr_code_id`) REFERENCES `hunt_qr_codes` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `qr_batch_items`
--

LOCK TABLES `qr_batch_items` WRITE;
/*!40000 ALTER TABLE `qr_batch_items` DISABLE KEYS */;
/*!40000 ALTER TABLE `qr_batch_items` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `qr_batches`
--

DROP TABLE IF EXISTS `qr_batches`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `qr_batches` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `batch_code` varchar(60) COLLATE utf8mb4_unicode_ci NOT NULL,
  `batch_title` varchar(190) COLLATE utf8mb4_unicode_ci NOT NULL,
  `category` enum('VIDEO_PAHELI','PRODUCT_SKU','COUPON_VOUCHER','BONUS_POINT') COLLATE utf8mb4_unicode_ci NOT NULL,
  `question_bank_category` varchar(190) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `generation_mode` enum('UPLOAD','AUTO_COUNT') COLLATE utf8mb4_unicode_ci NOT NULL,
  `quantity` int unsigned NOT NULL,
  `scan_frequency` enum('SINGLE_USE','PER_DEVICE','DAILY','POOL') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'PER_DEVICE',
  `reward_type` varchar(40) COLLATE utf8mb4_unicode_ci NOT NULL,
  `reward_value` decimal(18,2) NOT NULL DEFAULT '0.00',
  `status` enum('ACTIVE','PAUSED','DEACTIVATED','COMPLETED') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'ACTIVE',
  `hunt_id` bigint unsigned DEFAULT NULL,
  `location_id` bigint unsigned DEFAULT NULL,
  `created_by` int unsigned NOT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `batch_code` (`batch_code`),
  KEY `idx_qr_batch_status` (`status`),
  KEY `hunt_id` (`hunt_id`),
  KEY `location_id` (`location_id`),
  KEY `created_by` (`created_by`),
  CONSTRAINT `qr_batches_ibfk_1` FOREIGN KEY (`hunt_id`) REFERENCES `hunts` (`id`),
  CONSTRAINT `qr_batches_ibfk_2` FOREIGN KEY (`location_id`) REFERENCES `hunt_locations` (`id`),
  CONSTRAINT `qr_batches_ibfk_3` FOREIGN KEY (`created_by`) REFERENCES `admins` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `qr_batches`
--

LOCK TABLES `qr_batches` WRITE;
/*!40000 ALTER TABLE `qr_batches` DISABLE KEYS */;
/*!40000 ALTER TABLE `qr_batches` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `qr_campaigns`
--

DROP TABLE IF EXISTS `qr_campaigns`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `qr_campaigns` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `campaign_code` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `name` varchar(190) COLLATE utf8mb4_unicode_ci NOT NULL,
  `category_id` bigint unsigned DEFAULT NULL,
  `qr_code_id` bigint unsigned NOT NULL,
  `video_id` bigint unsigned DEFAULT NULL,
  `clue_text` text COLLATE utf8mb4_unicode_ci,
  `geofence_enabled` tinyint(1) NOT NULL DEFAULT '0',
  `reward_type` enum('PROMO_CASH','REWARD_COINS','VENDOR_VOUCHER') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'PROMO_CASH',
  `reward_value` decimal(18,2) NOT NULL DEFAULT '0.00',
  `ttl_minutes` int unsigned DEFAULT NULL,
  `auto_reversal_enabled` tinyint(1) NOT NULL DEFAULT '0',
  `merchant_id` bigint unsigned DEFAULT NULL,
  `scan_frequency` enum('PER_DEVICE','DAILY','UNLIMITED') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'PER_DEVICE',
  `budget_limit` int unsigned DEFAULT NULL,
  `claimed_count` int unsigned NOT NULL DEFAULT '0',
  `opens_at` time DEFAULT NULL,
  `closes_at` time DEFAULT NULL,
  `status` enum('ACTIVE','INACTIVE','EXPIRED') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'ACTIVE',
  `created_by` int unsigned NOT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `campaign_code` (`campaign_code`),
  UNIQUE KEY `qr_code_id` (`qr_code_id`),
  KEY `idx_qr_campaign_status` (`status`),
  KEY `category_id` (`category_id`),
  KEY `video_id` (`video_id`),
  KEY `merchant_id` (`merchant_id`),
  KEY `created_by` (`created_by`),
  CONSTRAINT `qr_campaigns_ibfk_1` FOREIGN KEY (`category_id`) REFERENCES `video_categories` (`id`),
  CONSTRAINT `qr_campaigns_ibfk_2` FOREIGN KEY (`qr_code_id`) REFERENCES `hunt_qr_codes` (`id`),
  CONSTRAINT `qr_campaigns_ibfk_3` FOREIGN KEY (`video_id`) REFERENCES `hunt_videos` (`id`),
  CONSTRAINT `qr_campaigns_ibfk_4` FOREIGN KEY (`merchant_id`) REFERENCES `vendors` (`id`),
  CONSTRAINT `qr_campaigns_ibfk_5` FOREIGN KEY (`created_by`) REFERENCES `admins` (`id`),
  CONSTRAINT `qr_campaigns_chk_1` CHECK ((`reward_value` >= 0)),
  CONSTRAINT `qr_campaigns_chk_2` CHECK (((`ttl_minutes` is null) or (`ttl_minutes` between 1 and 10080))),
  CONSTRAINT `qr_campaigns_chk_3` CHECK (((`budget_limit` is null) or (`budget_limit` > 0)))
) ENGINE=InnoDB AUTO_INCREMENT=9 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `qr_campaigns`
--

LOCK TABLES `qr_campaigns` WRITE;
/*!40000 ALTER TABLE `qr_campaigns` DISABLE KEYS */;
INSERT INTO `qr_campaigns` VALUES (1,'CMP-TEST','Test Campaign',NULL,2,NULL,NULL,0,'PROMO_CASH',15.00,30,1,NULL,'PER_DEVICE',NULL,0,NULL,NULL,'ACTIVE',1,'2026-08-23 19:08:23','2026-08-23 19:08:23'),(2,'DEMO-CAMPAIGN','Demo Mall Reward',1,3,NULL,'Find the red pillar',1,'PROMO_CASH',15.00,30,1,NULL,'PER_DEVICE',500,0,NULL,NULL,'ACTIVE',1,'2026-08-29 11:29:07','2026-08-29 11:29:07'),(3,'CMP-B59F52DB','Test Campaign',NULL,4,NULL,NULL,0,'PROMO_CASH',15.00,30,1,NULL,'PER_DEVICE',NULL,0,NULL,NULL,'ACTIVE',1,'2026-10-17 18:36:24','2026-10-17 18:36:24'),(4,'CMP-93BD279A','Test Campaign',NULL,8,NULL,NULL,0,'PROMO_CASH',15.00,30,1,NULL,'PER_DEVICE',NULL,0,NULL,NULL,'ACTIVE',1,'2026-10-17 19:57:16','2026-10-17 19:57:16'),(5,'CMP-84AAF380','Test Campaign',NULL,10,NULL,NULL,0,'PROMO_CASH',15.00,30,1,NULL,'PER_DEVICE',NULL,0,NULL,NULL,'ACTIVE',1,'2026-10-17 21:21:24','2026-10-17 21:21:24'),(6,'CMP-EEFA6591','Test Campaign',NULL,12,NULL,NULL,0,'PROMO_CASH',15.00,30,1,NULL,'PER_DEVICE',NULL,0,NULL,NULL,'ACTIVE',1,'2026-10-17 21:38:32','2026-10-17 21:38:32'),(7,'CMP-C4B8A794','Test Campaign',NULL,14,NULL,NULL,0,'PROMO_CASH',15.00,30,1,NULL,'PER_DEVICE',NULL,0,NULL,NULL,'ACTIVE',1,'2026-10-17 22:51:23','2026-10-17 22:51:23'),(8,'CMP-8D4DC97F','Test Campaign',NULL,16,NULL,NULL,0,'PROMO_CASH',15.00,30,1,NULL,'PER_DEVICE',NULL,0,NULL,NULL,'ACTIVE',1,'2026-10-18 09:25:17','2026-10-18 09:25:17');
/*!40000 ALTER TABLE `qr_campaigns` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `qr_promo_grants`
--

DROP TABLE IF EXISTS `qr_promo_grants`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `qr_promo_grants` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `grant_id` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `campaign_id` bigint unsigned NOT NULL,
  `user_id` bigint unsigned NOT NULL,
  `device_id` varchar(190) COLLATE utf8mb4_unicode_ci NOT NULL,
  `amount` decimal(18,2) NOT NULL,
  `status` enum('ACTIVE','SPENT','EXPIRED','REVERSED') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'ACTIVE',
  `expires_at` datetime DEFAULT NULL,
  `spent_at` datetime DEFAULT NULL,
  `reversed_at` datetime DEFAULT NULL,
  `checkout_reference` varchar(120) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `grant_id` (`grant_id`),
  UNIQUE KEY `uq_campaign_user_device` (`campaign_id`,`user_id`,`device_id`),
  KEY `idx_promo_expiry` (`status`,`expires_at`),
  KEY `user_id` (`user_id`),
  CONSTRAINT `qr_promo_grants_ibfk_1` FOREIGN KEY (`campaign_id`) REFERENCES `qr_campaigns` (`id`),
  CONSTRAINT `qr_promo_grants_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=8 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `qr_promo_grants`
--

LOCK TABLES `qr_promo_grants` WRITE;
/*!40000 ALTER TABLE `qr_promo_grants` DISABLE KEYS */;
INSERT INTO `qr_promo_grants` VALUES (1,'PRM-TEST',1,3,'test-device',15.00,'ACTIVE','2026-08-23 19:38:23',NULL,NULL,NULL,'2026-08-23 19:08:23'),(2,'PRM-B59F52DB',3,6,'test-device',15.00,'ACTIVE','2026-10-17 19:06:24',NULL,NULL,NULL,'2026-10-17 18:36:24'),(3,'PRM-93BD279A',4,14,'test-device',15.00,'ACTIVE','2026-10-17 20:27:16',NULL,NULL,NULL,'2026-10-17 19:57:16'),(4,'PRM-84AAF380',5,17,'test-device',15.00,'ACTIVE','2026-10-17 21:51:24',NULL,NULL,NULL,'2026-10-17 21:21:24'),(5,'PRM-EEFA6591',6,24,'test-device',15.00,'ACTIVE','2026-10-17 22:08:32',NULL,NULL,NULL,'2026-10-17 21:38:32'),(6,'PRM-C4B8A794',7,28,'test-device',15.00,'ACTIVE','2026-10-17 23:21:23',NULL,NULL,NULL,'2026-10-17 22:51:23'),(7,'PRM-8D4DC97F',8,32,'test-device',15.00,'ACTIVE','2026-10-18 09:55:17',NULL,NULL,NULL,'2026-10-18 09:25:17');
/*!40000 ALTER TABLE `qr_promo_grants` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `question_attempts`
--

DROP TABLE IF EXISTS `question_attempts`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `question_attempts` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint unsigned NOT NULL,
  `riddle_id` bigint unsigned NOT NULL,
  `collection_id` bigint unsigned DEFAULT NULL,
  `spot_id` varchar(64) COLLATE utf8mb4_unicode_ci NOT NULL,
  `submitted_answer` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `is_correct` tinyint(1) NOT NULL DEFAULT '0',
  `penalty_applied` decimal(18,2) NOT NULL DEFAULT '0.00',
  `reward_type` varchar(32) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `reward_amount` decimal(18,2) NOT NULL DEFAULT '0.00',
  `fallback_offer` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `idempotency_key` varchar(128) COLLATE utf8mb4_unicode_ci NOT NULL,
  `attempted_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `idempotency_key` (`idempotency_key`),
  KEY `idx_qa_user` (`user_id`),
  KEY `idx_qa_riddle` (`riddle_id`),
  KEY `idx_qa_spot` (`spot_id`)
) ENGINE=InnoDB AUTO_INCREMENT=21 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `question_attempts`
--

LOCK TABLES `question_attempts` WRITE;
/*!40000 ALTER TABLE `question_attempts` DISABLE KEYS */;
INSERT INTO `question_attempts` VALUES (1,22,3,NULL,'SPOT_4120c86a','watch',1,0.00,'COINS',50.00,NULL,'attempt-correct-4120c86a','2026-10-17 21:37:27'),(2,22,3,2,'SPOT_4120c86a','Incorrect Answer',0,5.00,NULL,0.00,'Q-Fallback-Offer','attempt-wrong1-4120c86a','2026-10-17 21:37:27'),(3,22,4,2,'SPOT_4120c86a','Wrong Answer',0,5.00,NULL,0.00,'Collection-Fallback-Offer','attempt-wrong2-4120c86a','2026-10-17 21:37:27'),(4,22,4,2,'SPOT_4120c86a','Wrong Again',0,2.00,NULL,0.00,'Collection-Fallback-Offer','attempt-wrong-cap-4120c86a','2026-10-17 21:37:27'),(5,22,4,2,'SPOT_4120c86a','Wrong Again 2',0,0.00,NULL,0.00,'Collection-Fallback-Offer','attempt-wrong-zero-4120c86a','2026-10-17 21:37:27'),(6,23,5,NULL,'SPOT_32a00416','watch',1,0.00,'COINS',50.00,NULL,'attempt-correct-32a00416','2026-10-17 21:38:31'),(7,23,5,3,'SPOT_32a00416','Incorrect Answer',0,5.00,NULL,0.00,'Q-Fallback-Offer','attempt-wrong1-32a00416','2026-10-17 21:38:31'),(8,23,6,3,'SPOT_32a00416','Wrong Answer',0,5.00,NULL,0.00,'Collection-Fallback-Offer','attempt-wrong2-32a00416','2026-10-17 21:38:31'),(9,23,6,3,'SPOT_32a00416','Wrong Again',0,2.00,NULL,0.00,'Collection-Fallback-Offer','attempt-wrong-cap-32a00416','2026-10-17 21:38:31'),(10,23,6,3,'SPOT_32a00416','Wrong Again 2',0,0.00,NULL,0.00,'Collection-Fallback-Offer','attempt-wrong-zero-32a00416','2026-10-17 21:38:31'),(11,27,7,NULL,'SPOT_4f67df73','watch',1,0.00,'COINS',50.00,NULL,'attempt-correct-4f67df73','2026-10-17 22:51:21'),(12,27,7,4,'SPOT_4f67df73','Incorrect Answer',0,5.00,NULL,0.00,'Q-Fallback-Offer','attempt-wrong1-4f67df73','2026-10-17 22:51:21'),(13,27,8,4,'SPOT_4f67df73','Wrong Answer',0,5.00,NULL,0.00,'Collection-Fallback-Offer','attempt-wrong2-4f67df73','2026-10-17 22:51:21'),(14,27,8,4,'SPOT_4f67df73','Wrong Again',0,2.00,NULL,0.00,'Collection-Fallback-Offer','attempt-wrong-cap-4f67df73','2026-10-17 22:51:21'),(15,27,8,4,'SPOT_4f67df73','Wrong Again 2',0,0.00,NULL,0.00,'Collection-Fallback-Offer','attempt-wrong-zero-4f67df73','2026-10-17 22:51:21'),(16,31,9,NULL,'SPOT_2a0bf7e2','watch',1,0.00,'COINS',50.00,NULL,'attempt-correct-2a0bf7e2','2026-10-18 09:25:15'),(17,31,9,5,'SPOT_2a0bf7e2','Incorrect Answer',0,5.00,NULL,0.00,'Q-Fallback-Offer','attempt-wrong1-2a0bf7e2','2026-10-18 09:25:15'),(18,31,10,5,'SPOT_2a0bf7e2','Wrong Answer',0,5.00,NULL,0.00,'Collection-Fallback-Offer','attempt-wrong2-2a0bf7e2','2026-10-18 09:25:15'),(19,31,10,5,'SPOT_2a0bf7e2','Wrong Again',0,2.00,NULL,0.00,'Collection-Fallback-Offer','attempt-wrong-cap-2a0bf7e2','2026-10-18 09:25:15'),(20,31,10,5,'SPOT_2a0bf7e2','Wrong Again 2',0,0.00,NULL,0.00,'Collection-Fallback-Offer','attempt-wrong-zero-2a0bf7e2','2026-10-18 09:25:15');
/*!40000 ALTER TABLE `question_attempts` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `question_collection_items`
--

DROP TABLE IF EXISTS `question_collection_items`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `question_collection_items` (
  `collection_id` bigint unsigned NOT NULL,
  `riddle_id` bigint unsigned NOT NULL,
  `question_order` int unsigned NOT NULL DEFAULT '1',
  PRIMARY KEY (`collection_id`,`riddle_id`),
  UNIQUE KEY `uq_collection_order` (`collection_id`,`question_order`),
  KEY `riddle_id` (`riddle_id`),
  CONSTRAINT `question_collection_items_ibfk_1` FOREIGN KEY (`collection_id`) REFERENCES `question_collections` (`collection_id`) ON DELETE CASCADE,
  CONSTRAINT `question_collection_items_ibfk_2` FOREIGN KEY (`riddle_id`) REFERENCES `riddle_pool` (`riddle_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `question_collection_items`
--

LOCK TABLES `question_collection_items` WRITE;
/*!40000 ALTER TABLE `question_collection_items` DISABLE KEYS */;
/*!40000 ALTER TABLE `question_collection_items` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `question_collections`
--

DROP TABLE IF EXISTS `question_collections`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `question_collections` (
  `collection_id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `collection_name` varchar(190) COLLATE utf8mb4_unicode_ci NOT NULL,
  `spot_id` varchar(64) COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` text COLLATE utf8mb4_unicode_ci,
  `rotation_method` enum('SEQUENTIAL','RANDOM','RANDOM_NO_REPEAT','ROUND_ROBIN','WEIGHTED') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'SEQUENTIAL',
  `questions_per_session` int unsigned NOT NULL DEFAULT '1',
  `negative_marking_points` int NOT NULL DEFAULT '0',
  `fallback_offer_title` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `status` enum('ACTIVE','INACTIVE') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'ACTIVE',
  `created_by` int unsigned NOT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`collection_id`),
  KEY `spot_id` (`spot_id`),
  KEY `created_by` (`created_by`),
  KEY `idx_collection_status` (`status`,`spot_id`),
  CONSTRAINT `question_collections_ibfk_1` FOREIGN KEY (`spot_id`) REFERENCES `spot_campaigns` (`spot_id`) ON DELETE CASCADE,
  CONSTRAINT `question_collections_ibfk_2` FOREIGN KEY (`created_by`) REFERENCES `admins` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `question_collections`
--

LOCK TABLES `question_collections` WRITE;
/*!40000 ALTER TABLE `question_collections` DISABLE KEYS */;
INSERT INTO `question_collections` VALUES (1,'Test','DEMO_SPOT_MALL_001','Test','RANDOM',1,0,NULL,'ACTIVE',2,'2026-08-30 16:39:22','2026-08-30 16:39:22'),(2,'Test Col','SPOT_4120c86a',NULL,'RANDOM',1,5,'Collection-Fallback-Offer','ACTIVE',12,'2026-10-17 21:37:27','2026-10-17 21:37:27'),(3,'Test Col','SPOT_32a00416',NULL,'RANDOM',1,5,'Collection-Fallback-Offer','ACTIVE',13,'2026-10-17 21:38:31','2026-10-17 21:38:31'),(4,'Test Col','SPOT_4f67df73',NULL,'RANDOM',1,5,'Collection-Fallback-Offer','ACTIVE',15,'2026-10-17 22:51:21','2026-10-17 22:51:21'),(5,'Test Col','SPOT_2a0bf7e2',NULL,'RANDOM',1,5,'Collection-Fallback-Offer','ACTIVE',17,'2026-10-18 09:25:15','2026-10-18 09:25:15');
/*!40000 ALTER TABLE `question_collections` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `reward_approvals`
--

DROP TABLE IF EXISTS `reward_approvals`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `reward_approvals` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `reward_id` bigint unsigned NOT NULL,
  `admin_id` int unsigned NOT NULL,
  `action` enum('APPROVED','REJECTED') COLLATE utf8mb4_unicode_ci NOT NULL,
  `reason` varchar(500) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_reward_decision` (`reward_id`),
  KEY `admin_id` (`admin_id`),
  CONSTRAINT `reward_approvals_ibfk_1` FOREIGN KEY (`reward_id`) REFERENCES `rewards` (`id`),
  CONSTRAINT `reward_approvals_ibfk_2` FOREIGN KEY (`admin_id`) REFERENCES `admins` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=10 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `reward_approvals`
--

LOCK TABLES `reward_approvals` WRITE;
/*!40000 ALTER TABLE `reward_approvals` DISABLE KEYS */;
INSERT INTO `reward_approvals` VALUES (1,1,1,'APPROVED',NULL,'2026-08-23 19:08:23'),(2,2,4,'APPROVED',NULL,'2026-10-17 18:37:52'),(3,3,5,'APPROVED',NULL,'2026-10-17 18:38:35'),(4,4,6,'APPROVED',NULL,'2026-10-17 18:38:57'),(5,5,8,'APPROVED',NULL,'2026-10-17 19:57:25'),(6,6,9,'APPROVED',NULL,'2026-10-17 21:21:26'),(7,7,14,'APPROVED',NULL,'2026-10-17 21:38:34'),(8,8,16,'APPROVED',NULL,'2026-10-17 22:51:25'),(9,9,18,'APPROVED',NULL,'2026-10-18 09:25:18');
/*!40000 ALTER TABLE `reward_approvals` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `reward_codes`
--

DROP TABLE IF EXISTS `reward_codes`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `reward_codes` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `code_reference` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `code_hash` char(64) COLLATE utf8mb4_unicode_ci NOT NULL,
  `user_id` bigint unsigned NOT NULL,
  `gift_id` bigint unsigned NOT NULL,
  `status` enum('ACTIVE','REDEEMED','EXPIRED','CANCELLED') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'ACTIVE',
  `expires_at` datetime DEFAULT NULL,
  `created_by` int unsigned NOT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `redeemed_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `code_reference` (`code_reference`),
  UNIQUE KEY `code_hash` (`code_hash`),
  KEY `idx_reward_code_user` (`user_id`,`status`),
  KEY `gift_id` (`gift_id`),
  KEY `created_by` (`created_by`),
  CONSTRAINT `reward_codes_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`),
  CONSTRAINT `reward_codes_ibfk_2` FOREIGN KEY (`gift_id`) REFERENCES `gifts` (`id`),
  CONSTRAINT `reward_codes_ibfk_3` FOREIGN KEY (`created_by`) REFERENCES `admins` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=15 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `reward_codes`
--

LOCK TABLES `reward_codes` WRITE;
/*!40000 ALTER TABLE `reward_codes` DISABLE KEYS */;
INSERT INTO `reward_codes` VALUES (1,'GFT-VALID','6aede01191b0eeecebceb0e169f2ef12e930648fbabed460bb6c21564fd513be',1,1,'REDEEMED','2026-08-24 19:08:23',1,'2026-08-23 19:08:23','2026-08-23 19:08:23'),(2,'GFT-EXPIRED','b36bd8ba65928f5db9f879878cd37d0fd9753ef32962876208a3a6a3e54d0e76',1,1,'ACTIVE','2026-08-22 19:08:23',1,'2026-08-23 19:08:23',NULL),(3,'GFT-VAL-1BAC2A6B','4c881a8cd62d77eb868e23ef3ea1bc4910b10b54e4e240f656dcee24306c52a3',11,2,'REDEEMED','2026-10-18 18:38:58',6,'2026-10-17 18:38:58','2026-10-17 18:38:58'),(4,'GFT-EXP-1BAC2A6B','307a9d128f34cce09b18172002a4e273c3e1a70254d8325bc7cca0bba30e7e99',11,2,'ACTIVE','2026-10-16 18:38:58',6,'2026-10-17 18:38:58',NULL),(5,'GFT-VAL-97790886','19135d650ac346d0f2207fce3b4c46a53a13fd8435f7739de1535d30b84ddb6a',15,3,'REDEEMED','2026-10-18 19:57:26',8,'2026-10-17 19:57:26','2026-10-17 19:57:26'),(6,'GFT-EXP-97790886','450d0508dfe5cb912d2d256ca6c5329bd97006e73983e13cdce3406aa18a2bc7',15,3,'ACTIVE','2026-10-16 19:57:26',8,'2026-10-17 19:57:26',NULL),(7,'GFT-VAL-6415B496','c8a347e37257a3ad19262be976cd472c305755ca673425a06f259b7199e8ba71',18,4,'REDEEMED','2026-10-18 21:21:26',9,'2026-10-17 21:21:26','2026-10-17 21:21:26'),(8,'GFT-EXP-6415B496','f8678842558b61460e5524845c553fdea9bedf07d058b56a424a5ee43a05bb32',18,4,'ACTIVE','2026-10-16 21:21:26',9,'2026-10-17 21:21:26',NULL),(9,'GFT-VAL-D1411073','810dbeeff86390eeae0442d1fc9942829b679cf363d191d569e3ebc407525ca5',25,5,'REDEEMED','2026-10-18 21:38:34',14,'2026-10-17 21:38:34','2026-10-17 21:38:34'),(10,'GFT-EXP-D1411073','9426b6d8e22a1c21b9826be1b10bbdb3f8ef05a02adb7f29c5e7462cf7f39a85',25,5,'ACTIVE','2026-10-16 21:38:34',14,'2026-10-17 21:38:34',NULL),(11,'GFT-VAL-3439D2E2','ea5adf3afa19b336c8f9c27bdffa3c0776965dd459429918dfdd39c1f21604df',29,6,'REDEEMED','2026-10-18 22:51:25',16,'2026-10-17 22:51:25','2026-10-17 22:51:25'),(12,'GFT-EXP-3439D2E2','c5f5c29856df7e43341cd7d8817b1b036c13b5cb9061b6d476cc61ff2869ed2a',29,6,'ACTIVE','2026-10-16 22:51:25',16,'2026-10-17 22:51:25',NULL),(13,'GFT-VAL-86F7090E','07a8f5fa36eb368eadfdca03f35bcb06ebc0fa40fa33003240ff695b0996b1d1',33,7,'REDEEMED','2026-10-19 09:25:19',18,'2026-10-18 09:25:19','2026-10-18 09:25:19'),(14,'GFT-EXP-86F7090E','c7ed0d983ae0e937a595e79d942c8da514ad7b357109078bf7bcf6ff8c34a06a',33,7,'ACTIVE','2026-10-17 09:25:19',18,'2026-10-18 09:25:19',NULL);
/*!40000 ALTER TABLE `reward_codes` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `rewards`
--

DROP TABLE IF EXISTS `rewards`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `rewards` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `reward_id` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `user_id` bigint unsigned NOT NULL,
  `hunt_id` bigint unsigned NOT NULL,
  `scan_id` bigint unsigned NOT NULL,
  `scratch_session_id` bigint unsigned NOT NULL,
  `amount` decimal(18,2) NOT NULL,
  `status` enum('PENDING','APPROVED','REJECTED') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'PENDING',
  `rejection_reason` varchar(500) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `wallet_transaction_id` bigint unsigned DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `decided_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `reward_id` (`reward_id`),
  UNIQUE KEY `scan_id` (`scan_id`),
  UNIQUE KEY `scratch_session_id` (`scratch_session_id`),
  UNIQUE KEY `wallet_transaction_id` (`wallet_transaction_id`),
  KEY `idx_reward_status_date` (`status`,`created_at`),
  KEY `user_id` (`user_id`),
  KEY `hunt_id` (`hunt_id`),
  CONSTRAINT `rewards_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`),
  CONSTRAINT `rewards_ibfk_2` FOREIGN KEY (`hunt_id`) REFERENCES `hunts` (`id`),
  CONSTRAINT `rewards_ibfk_3` FOREIGN KEY (`scan_id`) REFERENCES `hunt_scans` (`id`),
  CONSTRAINT `rewards_ibfk_4` FOREIGN KEY (`scratch_session_id`) REFERENCES `hunt_scratch_sessions` (`id`),
  CONSTRAINT `rewards_ibfk_5` FOREIGN KEY (`wallet_transaction_id`) REFERENCES `wallet_transactions` (`id`),
  CONSTRAINT `rewards_chk_1` CHECK ((`amount` > 0))
) ENGINE=InnoDB AUTO_INCREMENT=10 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `rewards`
--

LOCK TABLES `rewards` WRITE;
/*!40000 ALTER TABLE `rewards` DISABLE KEYS */;
INSERT INTO `rewards` VALUES (1,'RWD-20260823-B77B5D1F',1,1,1,1,500.00,'APPROVED',NULL,1,'2026-08-23 19:08:23','2026-08-23 19:08:23'),(2,'RWD-20261017-4702DB16',7,5,2,2,500.00,'APPROVED',NULL,16,'2026-10-17 18:37:52','2026-10-17 18:37:52'),(3,'RWD-20261017-6E570504',9,6,3,3,500.00,'APPROVED',NULL,18,'2026-10-17 18:38:35','2026-10-17 18:38:35'),(4,'RWD-20261017-67F876EC',11,7,4,4,500.00,'APPROVED',NULL,20,'2026-10-17 18:38:57','2026-10-17 18:38:57'),(5,'RWD-20261017-CBFDBB1D',15,9,5,5,500.00,'APPROVED',NULL,28,'2026-10-17 19:57:25','2026-10-17 19:57:25'),(6,'RWD-20261017-52C89046',18,11,6,6,500.00,'APPROVED',NULL,36,'2026-10-17 21:21:26','2026-10-17 21:21:26'),(7,'RWD-20261017-B5094C11',25,13,7,7,500.00,'APPROVED',NULL,54,'2026-10-17 21:38:34','2026-10-17 21:38:34'),(8,'RWD-20261017-2FD64895',29,15,8,8,500.00,'APPROVED',NULL,67,'2026-10-17 22:51:25','2026-10-17 22:51:25'),(9,'RWD-20261018-E32C8E21',33,17,9,9,500.00,'APPROVED',NULL,80,'2026-10-18 09:25:18','2026-10-18 09:25:18');
/*!40000 ALTER TABLE `rewards` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `riddle_pool`
--

DROP TABLE IF EXISTS `riddle_pool`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `riddle_pool` (
  `riddle_id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `spot_id` varchar(64) COLLATE utf8mb4_unicode_ci NOT NULL,
  `question_mode` enum('SINGLE','MULTIPLE') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'SINGLE',
  `riddle_text` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `instruction_text` text COLLATE utf8mb4_unicode_ci,
  `primary_answer` varchar(128) COLLATE utf8mb4_unicode_ci NOT NULL,
  `synonyms` json NOT NULL,
  `yt_video_id` varchar(32) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `channel_url` varchar(500) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `answer_time_seconds` int unsigned NOT NULL DEFAULT '60',
  `reward_type` enum('COINS','CASH','CASHBACK','COUPON','FREE_PIN') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'COINS',
  `reward_value` decimal(18,2) NOT NULL DEFAULT '50.00',
  `reward_expiry_days` int unsigned NOT NULL DEFAULT '7',
  `fallback_offer_title` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `collection_id` bigint unsigned DEFAULT NULL,
  `weight_percentage` int unsigned NOT NULL DEFAULT '100',
  `status` enum('ACTIVE','INACTIVE') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'ACTIVE',
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`riddle_id`),
  KEY `idx_riddle_spot` (`spot_id`,`weight_percentage`),
  CONSTRAINT `riddle_pool_ibfk_1` FOREIGN KEY (`spot_id`) REFERENCES `spot_campaigns` (`spot_id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=11 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `riddle_pool`
--

LOCK TABLES `riddle_pool` WRITE;
/*!40000 ALTER TABLE `riddle_pool` DISABLE KEYS */;
INSERT INTO `riddle_pool` VALUES (1,'DEMO_SPOT_MALL_001','SINGLE','Mujhe pehno toh waqt rukta nahi, main kaun?',NULL,'Ghadi','[\"watch\", \"clock\", \"samay\"]','dQw4w9WgXcQ',NULL,60,'COINS',50.00,7,NULL,NULL,100,'ACTIVE','2026-08-29 11:58:00','2026-08-30 16:37:58'),(2,'DEMO_SPOT_MALL_001','SINGLE','Mujhe pehno toh waqt rukta nahi, batao main kaun?','Submit one answer within the time limit.','Watch','[\"ghadi\", \"clock\", \"samay\"]','dQw4w9WgXcQ','https://youtube.com/@example',60,'COINS',50.00,7,NULL,NULL,100,'ACTIVE','2026-09-01 17:42:36','2026-09-01 17:42:36'),(3,'SPOT_4120c86a','SINGLE','Mujhe pehno toh waqt rukta nahi?','Scan QR','Ghadi','[\"watch\", \"clock\", \"samay\"]','vid123','',60,'COINS',50.00,7,'Q-Fallback-Offer',NULL,100,'ACTIVE','2026-10-17 21:37:27','2026-10-17 21:37:27'),(4,'SPOT_4120c86a','SINGLE','Question 2',NULL,'Right','[]',NULL,NULL,60,'COINS',30.00,7,NULL,2,100,'ACTIVE','2026-10-17 21:37:27','2026-10-17 21:37:27'),(5,'SPOT_32a00416','SINGLE','Mujhe pehno toh waqt rukta nahi?','Scan QR','Ghadi','[\"watch\", \"clock\", \"samay\"]','vid123','',60,'COINS',50.00,7,'Q-Fallback-Offer',NULL,100,'ACTIVE','2026-10-17 21:38:31','2026-10-17 21:38:31'),(6,'SPOT_32a00416','SINGLE','Question 2',NULL,'Right','[]',NULL,NULL,60,'COINS',30.00,7,NULL,3,100,'ACTIVE','2026-10-17 21:38:31','2026-10-17 21:38:31'),(7,'SPOT_4f67df73','SINGLE','Mujhe pehno toh waqt rukta nahi?','Scan QR','Ghadi','[\"watch\", \"clock\", \"samay\"]','vid123','',60,'COINS',50.00,7,'Q-Fallback-Offer',NULL,100,'ACTIVE','2026-10-17 22:51:21','2026-10-17 22:51:21'),(8,'SPOT_4f67df73','SINGLE','Question 2',NULL,'Right','[]',NULL,NULL,60,'COINS',30.00,7,NULL,4,100,'ACTIVE','2026-10-17 22:51:21','2026-10-17 22:51:21'),(9,'SPOT_2a0bf7e2','SINGLE','Mujhe pehno toh waqt rukta nahi?','Scan QR','Ghadi','[\"watch\", \"clock\", \"samay\"]','vid123','',60,'COINS',50.00,7,'Q-Fallback-Offer',NULL,100,'ACTIVE','2026-10-18 09:25:15','2026-10-18 09:25:15'),(10,'SPOT_2a0bf7e2','SINGLE','Question 2',NULL,'Right','[]',NULL,NULL,60,'COINS',30.00,7,NULL,5,100,'ACTIVE','2026-10-18 09:25:15','2026-10-18 09:25:15');
/*!40000 ALTER TABLE `riddle_pool` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `scan_audit_ledger`
--

DROP TABLE IF EXISTS `scan_audit_ledger`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `scan_audit_ledger` (
  `audit_id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `device_hash` varchar(128) COLLATE utf8mb4_unicode_ci NOT NULL,
  `user_id` bigint unsigned NOT NULL,
  `spot_id` varchar(64) COLLATE utf8mb4_unicode_ci NOT NULL,
  `riddle_id` bigint unsigned DEFAULT NULL,
  `user_input_answer` text COLLATE utf8mb4_unicode_ci,
  `is_correct` tinyint(1) NOT NULL DEFAULT '0',
  `attempt_count` int unsigned NOT NULL DEFAULT '1',
  `gps_lat_submitted` decimal(10,7) DEFAULT NULL,
  `gps_lng_submitted` decimal(10,7) DEFAULT NULL,
  `distance_calculated_m` decimal(12,2) DEFAULT NULL,
  `status` enum('PASS','WRONG_ATTEMPT','BLOCKED_DEVICE','EXHAUSTED','EXPIRED','SETTLED') COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`audit_id`),
  KEY `user_id` (`user_id`),
  KEY `riddle_id` (`riddle_id`),
  KEY `idx_scan_audit_spot` (`spot_id`,`created_at`),
  KEY `idx_scan_device` (`device_hash`,`spot_id`),
  CONSTRAINT `scan_audit_ledger_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`),
  CONSTRAINT `scan_audit_ledger_ibfk_2` FOREIGN KEY (`spot_id`) REFERENCES `spot_campaigns` (`spot_id`),
  CONSTRAINT `scan_audit_ledger_ibfk_3` FOREIGN KEY (`riddle_id`) REFERENCES `riddle_pool` (`riddle_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `scan_audit_ledger`
--

LOCK TABLES `scan_audit_ledger` WRITE;
/*!40000 ALTER TABLE `scan_audit_ledger` DISABLE KEYS */;
/*!40000 ALTER TABLE `scan_audit_ledger` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `security_events`
--

DROP TABLE IF EXISTS `security_events`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `security_events` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint unsigned DEFAULT NULL,
  `event_type` varchar(80) COLLATE utf8mb4_unicode_ci NOT NULL,
  `risk_level` enum('LOW','MEDIUM','HIGH','CRITICAL') COLLATE utf8mb4_unicode_ci NOT NULL,
  `ip_address` varchar(45) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `device_id` varchar(190) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `user_agent` varchar(500) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `description` varchar(500) COLLATE utf8mb4_unicode_ci NOT NULL,
  `metadata` json DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_security_risk_date` (`risk_level`,`created_at`),
  KEY `user_id` (`user_id`),
  CONSTRAINT `security_events_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=65 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `security_events`
--

LOCK TABLES `security_events` WRITE;
/*!40000 ALTER TABLE `security_events` DISABLE KEYS */;
INSERT INTO `security_events` VALUES (1,1,'HUNT_SCAN_REJECTED','HIGH','127.0.0.1','device-1','Hunter integration test','User is outside the allowed GPS radius.','{\"device_id\": \"device-1\"}','2026-08-23 19:08:22'),(2,1,'HUNT_SCAN_DUPLICATE','HIGH','127.0.0.1','device-1','Hunter integration test','QR was already claimed by this user','{\"device_id\": \"device-1\"}','2026-08-23 19:08:22'),(3,1,'HUNT_SECRET_FAILED','MEDIUM','127.0.0.1','device-1','Hunter integration test','Invalid hunt secret submitted','{\"scan_id\": 1, \"device_id\": \"device-1\"}','2026-08-23 19:08:22'),(4,1,'GIFT_REDEMPTION_REJECTED','MEDIUM','127.0.0.1',NULL,'Hunter integration test','Code is not active.','{\"vendor_id\": 1}','2026-08-23 19:08:23'),(5,1,'GIFT_REDEMPTION_REJECTED','MEDIUM','127.0.0.1',NULL,'Hunter integration test','Code has expired.','{\"vendor_id\": 1}','2026-08-23 19:08:23'),(6,NULL,'ADMIN_LOGIN_FAILED','MEDIUM','::1',NULL,'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36','Failed admin login','{\"identity_hash\": \"8c6976e5b5410415bde908bd4dee15dfb167a9c873fc4bb8a81f6f2ab448a918\"}','2026-08-23 19:12:42'),(7,NULL,'ADMIN_LOGIN_FAILED','MEDIUM','::1',NULL,'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36','Failed admin login','{\"identity_hash\": \"8c6976e5b5410415bde908bd4dee15dfb167a9c873fc4bb8a81f6f2ab448a918\"}','2026-08-23 19:12:54'),(8,NULL,'ADMIN_LOGIN_FAILED','MEDIUM','::1',NULL,'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36','Failed admin login','{\"identity_hash\": \"8c6976e5b5410415bde908bd4dee15dfb167a9c873fc4bb8a81f6f2ab448a918\"}','2026-08-23 19:13:12'),(9,NULL,'VENDOR_LOGIN_FAILED','MEDIUM','127.0.0.1',NULL,'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36','Failed vendor login','{\"identity_hash\": \"da5653469d0e01da4eda96d361ff6dbd8fd499ea8883c679f57a66e0f3fad756\"}','2026-09-11 19:16:29'),(10,NULL,'VENDOR_LOGIN_FAILED','MEDIUM','127.0.0.1',NULL,'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36','Failed vendor login','{\"identity_hash\": \"eb92cc52a766c500e001d02f1eaa78784da47f8c96b019ce874d3e3c205b4e3c\"}','2026-09-11 19:24:21'),(11,NULL,'VENDOR_LOGIN_FAILED','MEDIUM','127.0.0.1',NULL,'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36','Failed vendor login','{\"identity_hash\": \"5d1ce093d11f093703a4eb9903c720a1b97b838c0ae4fcef561d6edc243d5b45\"}','2026-09-11 19:32:55'),(12,NULL,'VENDOR_LOGIN_FAILED','MEDIUM','127.0.0.1',NULL,'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36','Failed vendor login','{\"identity_hash\": \"36035da416089b0f1a075642fa959d4ed6e956002f1d40057f44bfe4d845dd8b\"}','2026-09-11 19:44:05'),(13,NULL,'VENDOR_LOGIN_FAILED','MEDIUM','127.0.0.1',NULL,'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36','Failed vendor login','{\"identity_hash\": \"d502cfcfc4860af6b0ed6144fde9f1182b491694ddf48c13fb08ab9660a426d6\"}','2026-09-11 19:44:31'),(14,NULL,'ADMIN_LOGIN_FAILED','MEDIUM','127.0.0.1',NULL,'curl/8.7.1','Failed admin login','{\"identity_hash\": \"e3b0c44298fc1c149afbf4c8996fb92427ae41e4649b934ca495991b7852b855\"}','2026-09-13 15:47:06'),(15,NULL,'ADMIN_LOGIN_FAILED','MEDIUM','127.0.0.1',NULL,'curl/8.7.1','Failed admin login','{\"identity_hash\": \"4fe686dee93e405cf724a6de299e1a1fe906e5a147dfc46d414048d4b1738f21\"}','2026-09-13 15:50:20'),(16,7,'HUNT_SCAN_REJECTED','HIGH','127.0.0.1','device-7','Hunter integration test','User is outside the allowed GPS radius.','{\"device_id\": \"device-7\"}','2026-10-17 18:37:52'),(17,7,'HUNT_SCAN_DUPLICATE','HIGH','127.0.0.1','device-7','Hunter integration test','QR was already claimed by this user','{\"device_id\": \"device-7\"}','2026-10-17 18:37:52'),(18,7,'HUNT_SECRET_FAILED','MEDIUM','127.0.0.1','device-7','Hunter integration test','Invalid hunt secret submitted','{\"scan_id\": 2, \"device_id\": \"device-7\"}','2026-10-17 18:37:52'),(19,9,'HUNT_SCAN_REJECTED','HIGH','127.0.0.1','device-9','Hunter integration test','User is outside the allowed GPS radius.','{\"device_id\": \"device-9\"}','2026-10-17 18:38:35'),(20,9,'HUNT_SCAN_DUPLICATE','HIGH','127.0.0.1','device-9','Hunter integration test','QR was already claimed by this user','{\"device_id\": \"device-9\"}','2026-10-17 18:38:35'),(21,9,'HUNT_SECRET_FAILED','MEDIUM','127.0.0.1','device-9','Hunter integration test','Invalid hunt secret submitted','{\"scan_id\": 3, \"device_id\": \"device-9\"}','2026-10-17 18:38:35'),(22,11,'HUNT_SCAN_REJECTED','HIGH','127.0.0.1','device-11','Hunter integration test','User is outside the allowed GPS radius.','{\"device_id\": \"device-11\"}','2026-10-17 18:38:57'),(23,11,'HUNT_SCAN_DUPLICATE','HIGH','127.0.0.1','device-11','Hunter integration test','QR was already claimed by this user','{\"device_id\": \"device-11\"}','2026-10-17 18:38:57'),(24,11,'HUNT_SECRET_FAILED','MEDIUM','127.0.0.1','device-11','Hunter integration test','Invalid hunt secret submitted','{\"scan_id\": 4, \"device_id\": \"device-11\"}','2026-10-17 18:38:57'),(25,11,'GIFT_REDEMPTION_REJECTED','MEDIUM','127.0.0.1',NULL,'Hunter integration test','Code is not active.','{\"vendor_id\": 4}','2026-10-17 18:38:58'),(26,11,'GIFT_REDEMPTION_REJECTED','MEDIUM','127.0.0.1',NULL,'Hunter integration test','Code has expired.','{\"vendor_id\": 4}','2026-10-17 18:38:58'),(27,NULL,'VENDOR_LOGIN_FAILED','MEDIUM','127.0.0.1',NULL,'unknown','Failed vendor login','{\"identity_hash\": \"36035da416089b0f1a075642fa959d4ed6e956002f1d40057f44bfe4d845dd8b\"}','2026-10-17 18:57:21'),(28,NULL,'VENDOR_LOGIN_FAILED','MEDIUM','127.0.0.1',NULL,'unknown','Failed vendor login','{\"identity_hash\": \"36035da416089b0f1a075642fa959d4ed6e956002f1d40057f44bfe4d845dd8b\"}','2026-10-17 18:57:44'),(29,4,'LOGIN_SUCCESS','LOW','127.0.0.1','api-test-device-01','unknown','Mobile API login successful','{\"device_id\": \"api-test-device-01\"}','2026-10-17 18:58:32'),(30,4,'WITHDRAWAL_CREATED','LOW','127.0.0.1','api-test-device-01','unknown','Withdrawal requested','{\"device_id\": \"api-test-device-01\", \"withdrawal_id\": \"WD-20261017-FA143BD5\"}','2026-10-17 18:58:32'),(31,4,'LOGOUT','LOW','127.0.0.1','api-test-device-01','unknown','Mobile API session revoked','{\"device_id\": \"api-test-device-01\"}','2026-10-17 18:58:32'),(32,NULL,'VENDOR_LOGIN_FAILED','MEDIUM','127.0.0.1',NULL,'unknown','Failed vendor login','{\"identity_hash\": \"36035da416089b0f1a075642fa959d4ed6e956002f1d40057f44bfe4d845dd8b\"}','2026-10-17 19:56:35'),(33,15,'HUNT_SCAN_REJECTED','HIGH','127.0.0.1','device-15','Hunter integration test','User is outside the allowed GPS radius.','{\"device_id\": \"device-15\"}','2026-10-17 19:57:25'),(34,15,'HUNT_SCAN_DUPLICATE','HIGH','127.0.0.1','device-15','Hunter integration test','QR was already claimed by this user','{\"device_id\": \"device-15\"}','2026-10-17 19:57:25'),(35,15,'HUNT_SECRET_FAILED','MEDIUM','127.0.0.1','device-15','Hunter integration test','Invalid hunt secret submitted','{\"scan_id\": 5, \"device_id\": \"device-15\"}','2026-10-17 19:57:25'),(36,15,'GIFT_REDEMPTION_REJECTED','MEDIUM','127.0.0.1',NULL,'Hunter integration test','Code is not active.','{\"vendor_id\": 5}','2026-10-17 19:57:26'),(37,15,'GIFT_REDEMPTION_REJECTED','MEDIUM','127.0.0.1',NULL,'Hunter integration test','Code has expired.','{\"vendor_id\": 5}','2026-10-17 19:57:26'),(38,4,'LOGIN_SUCCESS','LOW','127.0.0.1','api-test-device-01','unknown','Mobile API login successful','{\"device_id\": \"api-test-device-01\"}','2026-10-17 19:58:00'),(39,4,'LOGOUT','LOW','127.0.0.1','api-test-device-01','unknown','Mobile API session revoked','{\"device_id\": \"api-test-device-01\"}','2026-10-17 19:58:00'),(40,NULL,'VENDOR_LOGIN_FAILED','MEDIUM','127.0.0.1',NULL,'unknown','Failed vendor login','{\"identity_hash\": \"36035da416089b0f1a075642fa959d4ed6e956002f1d40057f44bfe4d845dd8b\"}','2026-10-17 20:12:44'),(41,18,'HUNT_SCAN_REJECTED','HIGH','127.0.0.1','device-18','Hunter integration test','User is outside the allowed GPS radius.','{\"device_id\": \"device-18\"}','2026-10-17 21:21:25'),(42,18,'HUNT_SCAN_DUPLICATE','HIGH','127.0.0.1','device-18','Hunter integration test','QR was already claimed by this user','{\"device_id\": \"device-18\"}','2026-10-17 21:21:25'),(43,18,'HUNT_SECRET_FAILED','MEDIUM','127.0.0.1','device-18','Hunter integration test','Invalid hunt secret submitted','{\"scan_id\": 6, \"device_id\": \"device-18\"}','2026-10-17 21:21:25'),(44,18,'GIFT_REDEMPTION_REJECTED','MEDIUM','127.0.0.1',NULL,'Hunter integration test','Code is not active.','{\"vendor_id\": 6}','2026-10-17 21:21:26'),(45,18,'GIFT_REDEMPTION_REJECTED','MEDIUM','127.0.0.1',NULL,'Hunter integration test','Code has expired.','{\"vendor_id\": 6}','2026-10-17 21:21:26'),(46,25,'HUNT_SCAN_REJECTED','HIGH','127.0.0.1','device-25','Hunter integration test','User is outside the allowed GPS radius.','{\"device_id\": \"device-25\"}','2026-10-17 21:38:34'),(47,25,'HUNT_SCAN_DUPLICATE','HIGH','127.0.0.1','device-25','Hunter integration test','QR was already claimed by this user','{\"device_id\": \"device-25\"}','2026-10-17 21:38:34'),(48,25,'HUNT_SECRET_FAILED','MEDIUM','127.0.0.1','device-25','Hunter integration test','Invalid hunt secret submitted','{\"scan_id\": 7, \"device_id\": \"device-25\"}','2026-10-17 21:38:34'),(49,25,'GIFT_REDEMPTION_REJECTED','MEDIUM','127.0.0.1',NULL,'Hunter integration test','Code is not active.','{\"vendor_id\": 7}','2026-10-17 21:38:34'),(50,25,'GIFT_REDEMPTION_REJECTED','MEDIUM','127.0.0.1',NULL,'Hunter integration test','Code has expired.','{\"vendor_id\": 7}','2026-10-17 21:38:34'),(51,29,'HUNT_SCAN_REJECTED','HIGH','127.0.0.1','device-29','Hunter integration test','User is outside the allowed GPS radius.','{\"device_id\": \"device-29\"}','2026-10-17 22:51:24'),(52,29,'HUNT_SCAN_DUPLICATE','HIGH','127.0.0.1','device-29','Hunter integration test','QR was already claimed by this user','{\"device_id\": \"device-29\"}','2026-10-17 22:51:24'),(53,29,'HUNT_SECRET_FAILED','MEDIUM','127.0.0.1','device-29','Hunter integration test','Invalid hunt secret submitted','{\"scan_id\": 8, \"device_id\": \"device-29\"}','2026-10-17 22:51:25'),(54,29,'GIFT_REDEMPTION_REJECTED','MEDIUM','127.0.0.1',NULL,'Hunter integration test','Code is not active.','{\"vendor_id\": 8}','2026-10-17 22:51:25'),(55,29,'GIFT_REDEMPTION_REJECTED','MEDIUM','127.0.0.1',NULL,'Hunter integration test','Code has expired.','{\"vendor_id\": 8}','2026-10-17 22:51:25'),(56,NULL,'ADMIN_LOGIN_FAILED','MEDIUM','127.0.0.1',NULL,'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36','Failed admin login','{\"identity_hash\": \"186cf774c97b60a1c106ef718d10970a6a06e06bef89553d9ae65d938a886eae\"}','2026-10-17 23:13:35'),(57,NULL,'ADMIN_LOGIN_FAILED','MEDIUM','127.0.0.1',NULL,'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36','Failed admin login','{\"identity_hash\": \"186cf774c97b60a1c106ef718d10970a6a06e06bef89553d9ae65d938a886eae\"}','2026-10-17 23:13:57'),(58,33,'HUNT_SCAN_REJECTED','HIGH','127.0.0.1','device-33','Hunter integration test','User is outside the allowed GPS radius.','{\"device_id\": \"device-33\"}','2026-10-18 09:25:18'),(59,33,'HUNT_SCAN_DUPLICATE','HIGH','127.0.0.1','device-33','Hunter integration test','QR was already claimed by this user','{\"device_id\": \"device-33\"}','2026-10-18 09:25:18'),(60,33,'HUNT_SECRET_FAILED','MEDIUM','127.0.0.1','device-33','Hunter integration test','Invalid hunt secret submitted','{\"scan_id\": 9, \"device_id\": \"device-33\"}','2026-10-18 09:25:18'),(61,33,'GIFT_REDEMPTION_REJECTED','MEDIUM','127.0.0.1',NULL,'Hunter integration test','Code is not active.','{\"vendor_id\": 9}','2026-10-18 09:25:19'),(62,33,'GIFT_REDEMPTION_REJECTED','MEDIUM','127.0.0.1',NULL,'Hunter integration test','Code has expired.','{\"vendor_id\": 9}','2026-10-18 09:25:19'),(63,NULL,'VENDOR_LOGIN_FAILED','MEDIUM','127.0.0.1',NULL,'unknown','Failed vendor login','{\"identity_hash\": \"36035da416089b0f1a075642fa959d4ed6e956002f1d40057f44bfe4d845dd8b\"}','2026-10-18 09:40:00'),(64,NULL,'VENDOR_LOGIN_FAILED','MEDIUM','127.0.0.1',NULL,'unknown','Failed vendor login','{\"identity_hash\": \"36035da416089b0f1a075642fa959d4ed6e956002f1d40057f44bfe4d845dd8b\"}','2026-10-18 10:35:58');
/*!40000 ALTER TABLE `security_events` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `spot_campaigns`
--

DROP TABLE IF EXISTS `spot_campaigns`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `spot_campaigns` (
  `spot_id` varchar(64) COLLATE utf8mb4_unicode_ci NOT NULL,
  `master_token_hash` char(64) COLLATE utf8mb4_unicode_ci NOT NULL,
  `spot_name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `category_id` bigint unsigned DEFAULT NULL,
  `question_mode` enum('SINGLE','COLLECTION') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'SINGLE',
  `question_id` bigint unsigned DEFAULT NULL,
  `collection_id` bigint unsigned DEFAULT NULL,
  `target_scope` enum('ALL_PUBLIC','WHITELIST','NEW_USER') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'ALL_PUBLIC',
  `whitelisted_users` json DEFAULT NULL,
  `city_zone` varchar(128) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `venue_name` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `gps_mode` enum('GPS_REQUIRED','DESK_STUDY') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'GPS_REQUIRED',
  `latitude` decimal(10,7) NOT NULL,
  `longitude` decimal(10,7) NOT NULL,
  `proximity_alert_radius` int unsigned NOT NULL DEFAULT '30',
  `scanner_unlock_radius` int unsigned NOT NULL DEFAULT '20',
  `indoor_micro_clue` text COLLATE utf8mb4_unicode_ci,
  `reward_type` enum('PROMO_CASH','COUPON_VOUCHER','DIRECT_CASH','MILESTONE') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'PROMO_CASH',
  `reward_amount` decimal(18,2) NOT NULL DEFAULT '15.00',
  `min_cart_value` decimal(18,2) NOT NULL DEFAULT '300.00',
  `ttl_minutes` int unsigned NOT NULL DEFAULT '30',
  `auto_reversal` tinyint(1) NOT NULL DEFAULT '1',
  `max_budget_scans` int unsigned NOT NULL DEFAULT '500',
  `scan_frequency_policy` enum('PER_DEVICE','SINGLE_USE','DAILY','POOL') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'PER_DEVICE',
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  `created_by` int unsigned NOT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`spot_id`),
  UNIQUE KEY `master_token_hash` (`master_token_hash`),
  KEY `category_id` (`category_id`),
  KEY `created_by` (`created_by`),
  KEY `idx_spot_active` (`is_active`,`category_id`),
  CONSTRAINT `spot_campaigns_ibfk_1` FOREIGN KEY (`category_id`) REFERENCES `video_categories` (`id`),
  CONSTRAINT `spot_campaigns_ibfk_2` FOREIGN KEY (`created_by`) REFERENCES `admins` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `spot_campaigns`
--

LOCK TABLES `spot_campaigns` WRITE;
/*!40000 ALTER TABLE `spot_campaigns` DISABLE KEYS */;
INSERT INTO `spot_campaigns` VALUES ('DEMO_SPOT_MALL_001','0b1c0f0b616aca8641b55e695d1b44d8696cbb074958c5125d86ddb5c8274c3c','Demo Mall Lift Lobby',1,'SINGLE',NULL,NULL,'ALL_PUBLIC','[]','Patna','Demo Mall','GPS_REQUIRED',25.6120450,85.1440210,30,20,'First floor → red pillar','PROMO_CASH',15.00,300.00,30,1,500,'PER_DEVICE',1,1,'2026-08-29 11:58:00','2026-08-29 11:58:00'),('SPOT_2a0bf7e2','b5545dab6256d08d50226b4c2d3465c57a29606d72de25d9517a07594f1c6d20','Test Spot',NULL,'SINGLE',NULL,NULL,'ALL_PUBLIC',NULL,NULL,NULL,'GPS_REQUIRED',25.5940950,85.1375660,30,20,NULL,'PROMO_CASH',15.00,300.00,30,1,500,'PER_DEVICE',1,17,'2026-10-18 09:25:15','2026-10-18 09:25:15'),('SPOT_32a00416','b4ceb61763b8bc96a2a7ebedcc246dc0fbaaf64e15f2be8b677f047bd77a8ccc','Test Spot',NULL,'SINGLE',NULL,NULL,'ALL_PUBLIC',NULL,NULL,NULL,'GPS_REQUIRED',25.5940950,85.1375660,30,20,NULL,'PROMO_CASH',15.00,300.00,30,1,500,'PER_DEVICE',1,13,'2026-10-17 21:38:31','2026-10-17 21:38:31'),('SPOT_4120c86a','2613621b5baca40b9902836f1f77d74b4a7505823da8af8cc689a65506bbc66f','Test Spot',NULL,'SINGLE',NULL,NULL,'ALL_PUBLIC',NULL,NULL,NULL,'GPS_REQUIRED',25.5940950,85.1375660,30,20,NULL,'PROMO_CASH',15.00,300.00,30,1,500,'PER_DEVICE',1,12,'2026-10-17 21:37:27','2026-10-17 21:37:27'),('SPOT_4f67df73','c59ebf98f1b2c2f7d260e818f05936c8112a10c7597ce3703e3197de870da49c','Test Spot',NULL,'SINGLE',NULL,NULL,'ALL_PUBLIC',NULL,NULL,NULL,'GPS_REQUIRED',25.5940950,85.1375660,30,20,NULL,'PROMO_CASH',15.00,300.00,30,1,500,'PER_DEVICE',1,15,'2026-10-17 22:51:21','2026-10-17 22:51:21');
/*!40000 ALTER TABLE `spot_campaigns` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `staff_profiles`
--

DROP TABLE IF EXISTS `staff_profiles`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `staff_profiles` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `admin_id` int unsigned NOT NULL,
  `mobile` varchar(30) COLLATE utf8mb4_unicode_ci NOT NULL,
  `address` varchar(500) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `designation` varchar(120) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `aadhaar_front_path` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `aadhaar_back_path` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `pan_path` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `photo_path` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `kyc_status` enum('PENDING','APPROVED','REJECTED') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'PENDING',
  `approved_by` int unsigned DEFAULT NULL,
  `approved_at` datetime DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `admin_id` (`admin_id`),
  UNIQUE KEY `mobile` (`mobile`),
  KEY `approved_by` (`approved_by`),
  CONSTRAINT `staff_profiles_ibfk_1` FOREIGN KEY (`admin_id`) REFERENCES `admins` (`id`) ON DELETE CASCADE,
  CONSTRAINT `staff_profiles_ibfk_2` FOREIGN KEY (`approved_by`) REFERENCES `admins` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `staff_profiles`
--

LOCK TABLES `staff_profiles` WRITE;
/*!40000 ALTER TABLE `staff_profiles` DISABLE KEYS */;
/*!40000 ALTER TABLE `staff_profiles` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `system_settings`
--

DROP TABLE IF EXISTS `system_settings`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `system_settings` (
  `key` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `value` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `is_secret` tinyint(1) NOT NULL DEFAULT '0',
  `updated_by` int unsigned DEFAULT NULL,
  `updated_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`key`),
  KEY `updated_by` (`updated_by`),
  CONSTRAINT `system_settings_ibfk_1` FOREIGN KEY (`updated_by`) REFERENCES `admins` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `system_settings`
--

LOCK TABLES `system_settings` WRITE;
/*!40000 ALTER TABLE `system_settings` DISABLE KEYS */;
INSERT INTO `system_settings` VALUES ('accent_color','#24c79a',0,NULL,'2026-08-30 16:37:58'),('brand_name','Hunter Wallet',0,NULL,'2026-08-30 16:37:58'),('brand_tagline','Secure administration portal',0,NULL,'2026-08-30 16:37:58'),('default_currency','INR',0,NULL,'2026-08-30 16:37:58'),('gps_max_accuracy_meters','150',0,NULL,'2026-08-23 19:08:10'),('hunt_scan_rate_per_minute','10',0,NULL,'2026-08-23 19:08:10'),('primary_color','#12304a',0,NULL,'2026-08-30 16:37:58'),('reward_code_valid_days','30',0,NULL,'2026-08-23 19:08:10'),('session_timeout','1800',0,NULL,'2026-08-30 16:37:58'),('smtp_port','587',0,NULL,'2026-08-30 16:37:58'),('theme_mode','light',0,NULL,'2026-08-30 16:37:58');
/*!40000 ALTER TABLE `system_settings` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `targeted_offers`
--

DROP TABLE IF EXISTS `targeted_offers`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `targeted_offers` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `offer_title` varchar(190) COLLATE utf8mb4_unicode_ci NOT NULL,
  `target_type` enum('PRODUCT','CATEGORY','VENDOR','USER') COLLATE utf8mb4_unicode_ci NOT NULL,
  `target_id` varchar(120) COLLATE utf8mb4_unicode_ci NOT NULL,
  `target_name` varchar(190) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `bonus_type` enum('PERCENTAGE','FIXED') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'PERCENTAGE',
  `bonus_value` decimal(18,2) NOT NULL DEFAULT '0.00',
  `shop_id` varchar(120) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `min_cart` decimal(18,2) NOT NULL DEFAULT '0.00',
  `max_limit` decimal(18,2) NOT NULL DEFAULT '0.00',
  `start_date` datetime NOT NULL,
  `end_date` datetime NOT NULL,
  `status` enum('ACTIVE','INACTIVE') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'ACTIVE',
  `created_by` int unsigned NOT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_offer_target` (`target_type`,`target_id`,`status`)
) ENGINE=InnoDB AUTO_INCREMENT=13 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `targeted_offers`
--

LOCK TABLES `targeted_offers` WRITE;
/*!40000 ALTER TABLE `targeted_offers` DISABLE KEYS */;
INSERT INTO `targeted_offers` VALUES (1,'10% on Electronics','CATEGORY','CAT_ELEC',NULL,'PERCENTAGE',10.00,NULL,300.00,100.00,'2026-10-16 21:37:28','2026-11-16 21:37:28','ACTIVE',12,'2026-10-17 21:37:28'),(2,'15% on Phone','PRODUCT','PROD_PHONE',NULL,'PERCENTAGE',15.00,NULL,500.00,250.00,'2026-10-16 21:37:28','2026-11-16 21:37:28','ACTIVE',12,'2026-10-17 21:37:28'),(3,'Special VIP User Offer','USER','22',NULL,'FIXED',200.00,NULL,200.00,200.00,'2026-10-16 21:37:28','2026-11-16 21:37:28','ACTIVE',12,'2026-10-17 21:37:28'),(4,'10% on Electronics','CATEGORY','CAT_ELEC',NULL,'PERCENTAGE',10.00,NULL,300.00,100.00,'2026-10-16 21:38:32','2026-11-16 21:38:32','ACTIVE',13,'2026-10-17 21:38:32'),(5,'15% on Phone','PRODUCT','PROD_PHONE',NULL,'PERCENTAGE',15.00,NULL,500.00,250.00,'2026-10-16 21:38:32','2026-11-16 21:38:32','ACTIVE',13,'2026-10-17 21:38:32'),(6,'Special VIP User Offer','USER','23',NULL,'FIXED',200.00,NULL,200.00,200.00,'2026-10-16 21:38:32','2026-11-16 21:38:32','ACTIVE',13,'2026-10-17 21:38:32'),(7,'10% on Electronics','CATEGORY','CAT_ELEC',NULL,'PERCENTAGE',10.00,NULL,300.00,100.00,'2026-10-16 22:51:23','2026-11-16 22:51:23','ACTIVE',15,'2026-10-17 22:51:23'),(8,'15% on Phone','PRODUCT','PROD_PHONE',NULL,'PERCENTAGE',15.00,NULL,500.00,250.00,'2026-10-16 22:51:23','2026-11-16 22:51:23','ACTIVE',15,'2026-10-17 22:51:23'),(9,'Special VIP User Offer','USER','27',NULL,'FIXED',200.00,NULL,200.00,200.00,'2026-10-16 22:51:23','2026-11-16 22:51:23','ACTIVE',15,'2026-10-17 22:51:23'),(10,'10% on Electronics','CATEGORY','CAT_ELEC',NULL,'PERCENTAGE',10.00,NULL,300.00,100.00,'2026-10-17 09:25:16','2026-11-17 09:25:16','ACTIVE',17,'2026-10-18 09:25:16'),(11,'15% on Phone','PRODUCT','PROD_PHONE',NULL,'PERCENTAGE',15.00,NULL,500.00,250.00,'2026-10-17 09:25:16','2026-11-17 09:25:16','ACTIVE',17,'2026-10-18 09:25:16'),(12,'Special VIP User Offer','USER','31',NULL,'FIXED',200.00,NULL,200.00,200.00,'2026-10-17 09:25:16','2026-11-17 09:25:16','ACTIVE',17,'2026-10-18 09:25:16');
/*!40000 ALTER TABLE `targeted_offers` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `transaction_limits`
--

DROP TABLE IF EXISTS `transaction_limits`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `transaction_limits` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint unsigned DEFAULT NULL,
  `min_withdrawal` decimal(18,2) NOT NULL DEFAULT '100.00',
  `max_withdrawal` decimal(18,2) NOT NULL DEFAULT '50000.00',
  `daily_withdrawal` decimal(18,2) NOT NULL DEFAULT '100000.00',
  `monthly_withdrawal` decimal(18,2) DEFAULT NULL,
  `updated_by` int unsigned DEFAULT NULL,
  `updated_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_user_limits` (`user_id`),
  KEY `updated_by` (`updated_by`),
  CONSTRAINT `transaction_limits_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`),
  CONSTRAINT `transaction_limits_ibfk_2` FOREIGN KEY (`updated_by`) REFERENCES `admins` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `transaction_limits`
--

LOCK TABLES `transaction_limits` WRITE;
/*!40000 ALTER TABLE `transaction_limits` DISABLE KEYS */;
INSERT INTO `transaction_limits` VALUES (1,NULL,100.00,50000.00,100000.00,NULL,NULL,'2026-08-23 19:08:10');
/*!40000 ALTER TABLE `transaction_limits` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `user_devices`
--

DROP TABLE IF EXISTS `user_devices`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `user_devices` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint unsigned NOT NULL,
  `device_id` varchar(190) COLLATE utf8mb4_unicode_ci NOT NULL,
  `device_name` varchar(120) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `platform` varchar(30) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `os_version` varchar(40) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `app_version` varchar(40) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `ip_address` varchar(45) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `status` enum('ACTIVE','BLOCKED','REPLACEMENT_PENDING') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'ACTIVE',
  `last_active_at` datetime DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `user_id` (`user_id`,`device_id`),
  KEY `idx_device_status` (`status`),
  CONSTRAINT `user_devices_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=28 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `user_devices`
--

LOCK TABLES `user_devices` WRITE;
/*!40000 ALTER TABLE `user_devices` DISABLE KEYS */;
INSERT INTO `user_devices` VALUES (1,1,'device-1',NULL,NULL,NULL,NULL,NULL,'ACTIVE','2026-08-23 19:08:21','2026-08-23 19:08:21','2026-08-23 19:08:21'),(2,2,'device-2',NULL,NULL,NULL,NULL,NULL,'ACTIVE','2026-08-23 19:08:22','2026-08-23 19:08:22','2026-08-23 19:08:22'),(3,1,'demo-device-1','Demo device','Android',NULL,NULL,NULL,'ACTIVE','2026-08-29 11:29:07','2026-08-29 11:29:07','2026-08-29 11:29:07'),(4,2,'demo-device-2','Demo device','Android',NULL,NULL,NULL,'ACTIVE','2026-08-29 11:29:07','2026-08-29 11:29:07','2026-08-29 11:29:07'),(5,4,'demo-device-4','Demo device','Android',NULL,NULL,NULL,'ACTIVE','2026-08-29 11:29:07','2026-08-29 11:29:07','2026-08-29 11:29:07'),(9,7,'device-7',NULL,NULL,NULL,NULL,NULL,'ACTIVE','2026-10-17 18:37:51','2026-10-17 18:37:51','2026-10-17 18:37:51'),(10,8,'device-8',NULL,NULL,NULL,NULL,NULL,'ACTIVE','2026-10-17 18:37:51','2026-10-17 18:37:51','2026-10-17 18:37:51'),(11,9,'device-9',NULL,NULL,NULL,NULL,NULL,'ACTIVE','2026-10-17 18:38:34','2026-10-17 18:38:34','2026-10-17 18:38:34'),(12,10,'device-10',NULL,NULL,NULL,NULL,NULL,'ACTIVE','2026-10-17 18:38:34','2026-10-17 18:38:34','2026-10-17 18:38:34'),(13,11,'device-11',NULL,NULL,NULL,NULL,NULL,'ACTIVE','2026-10-17 18:38:56','2026-10-17 18:38:56','2026-10-17 18:38:56'),(14,12,'device-12',NULL,NULL,NULL,NULL,NULL,'ACTIVE','2026-10-17 18:38:56','2026-10-17 18:38:56','2026-10-17 18:38:56'),(15,4,'api-test-device-01','Test Device',NULL,NULL,NULL,'127.0.0.1','ACTIVE','2026-10-17 19:58:00','2026-10-17 18:58:25','2026-10-17 19:58:00'),(17,15,'device-15',NULL,NULL,NULL,NULL,NULL,'ACTIVE','2026-10-17 19:57:24','2026-10-17 19:57:24','2026-10-17 19:57:24'),(18,16,'device-16',NULL,NULL,NULL,NULL,NULL,'ACTIVE','2026-10-17 19:57:24','2026-10-17 19:57:24','2026-10-17 19:57:24'),(20,18,'device-18',NULL,NULL,NULL,NULL,NULL,'ACTIVE','2026-10-17 21:21:25','2026-10-17 21:21:25','2026-10-17 21:21:25'),(21,19,'device-19',NULL,NULL,NULL,NULL,NULL,'ACTIVE','2026-10-17 21:21:25','2026-10-17 21:21:25','2026-10-17 21:21:25'),(22,25,'device-25',NULL,NULL,NULL,NULL,NULL,'ACTIVE','2026-10-17 21:38:33','2026-10-17 21:38:33','2026-10-17 21:38:33'),(23,26,'device-26',NULL,NULL,NULL,NULL,NULL,'ACTIVE','2026-10-17 21:38:33','2026-10-17 21:38:33','2026-10-17 21:38:33'),(24,29,'device-29',NULL,NULL,NULL,NULL,NULL,'ACTIVE','2026-10-17 22:51:24','2026-10-17 22:51:24','2026-10-17 22:51:24'),(25,30,'device-30',NULL,NULL,NULL,NULL,NULL,'ACTIVE','2026-10-17 22:51:24','2026-10-17 22:51:24','2026-10-17 22:51:24'),(26,33,'device-33',NULL,NULL,NULL,NULL,NULL,'ACTIVE','2026-10-18 09:25:17','2026-10-18 09:25:17','2026-10-18 09:25:17'),(27,34,'device-34',NULL,NULL,NULL,NULL,NULL,'ACTIVE','2026-10-18 09:25:18','2026-10-18 09:25:18','2026-10-18 09:25:18');
/*!40000 ALTER TABLE `user_devices` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `user_payment_methods`
--

DROP TABLE IF EXISTS `user_payment_methods`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `user_payment_methods` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint unsigned NOT NULL,
  `method_type` enum('BANK','UPI') COLLATE utf8mb4_unicode_ci NOT NULL,
  `account_holder` varchar(190) COLLATE utf8mb4_unicode_ci NOT NULL,
  `account_reference_encrypted` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `account_masked` varchar(120) COLLATE utf8mb4_unicode_ci NOT NULL,
  `ifsc` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `status` enum('ACTIVE','BLOCKED') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'ACTIVE',
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_user_payment_method` (`user_id`,`status`),
  CONSTRAINT `user_payment_methods_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `user_payment_methods`
--

LOCK TABLES `user_payment_methods` WRITE;
/*!40000 ALTER TABLE `user_payment_methods` DISABLE KEYS */;
/*!40000 ALTER TABLE `user_payment_methods` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `user_sessions`
--

DROP TABLE IF EXISTS `user_sessions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `user_sessions` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint unsigned NOT NULL,
  `token_hash` char(64) COLLATE utf8mb4_unicode_ci NOT NULL,
  `device_id` varchar(190) COLLATE utf8mb4_unicode_ci NOT NULL,
  `ip_address` varchar(45) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `user_agent` varchar(500) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `expires_at` datetime NOT NULL,
  `revoked_at` datetime DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `token_hash` (`token_hash`),
  KEY `idx_session_user` (`user_id`),
  CONSTRAINT `user_sessions_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `user_sessions`
--

LOCK TABLES `user_sessions` WRITE;
/*!40000 ALTER TABLE `user_sessions` DISABLE KEYS */;
INSERT INTO `user_sessions` VALUES (1,4,'34860571f697dd3149f1fe5185d6db318fcca60a601e835862b10d2746879141','api-test-device-01','127.0.0.1','unknown','2026-11-16 18:58:32','2026-10-17 18:58:32','2026-10-17 18:58:32'),(2,4,'8f6f9ac072326118403000e76f7b10e615b9d340020446debf4c837f8c293aa4','api-test-device-01','127.0.0.1','unknown','2026-11-16 19:58:00','2026-10-17 19:58:00','2026-10-17 19:58:00');
/*!40000 ALTER TABLE `user_sessions` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `users`
--

DROP TABLE IF EXISTS `users`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `users` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(120) COLLATE utf8mb4_unicode_ci NOT NULL,
  `mobile` varchar(30) COLLATE utf8mb4_unicode_ci NOT NULL,
  `email` varchar(190) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `password_hash` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `profile_image` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `status` enum('ACTIVE','BLOCKED','SUSPENDED','PENDING') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'PENDING',
  `status_reason` varchar(500) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `is_verified` tinyint(1) NOT NULL DEFAULT '0',
  `balance_privacy_enabled` tinyint(1) NOT NULL DEFAULT '0',
  `risk_level` enum('LOW','MEDIUM','HIGH','CRITICAL') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'LOW',
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `last_login_at` datetime DEFAULT NULL,
  `last_login_ip` varchar(45) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `settlement_upi` varchar(190) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `bank_account_number` varchar(80) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `bank_ifsc` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `bank_name` varchar(190) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `mobile` (`mobile`),
  UNIQUE KEY `email` (`email`),
  KEY `idx_users_status` (`status`),
  KEY `idx_users_created` (`created_at`)
) ENGINE=InnoDB AUTO_INCREMENT=35 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `users`
--

LOCK TABLES `users` WRITE;
/*!40000 ALTER TABLE `users` DISABLE KEYS */;
INSERT INTO `users` VALUES (1,'Hunt User','9000000001','hunt-user@example.test','$2y$12$aJxjDah2o4ush8TkCsQ.I.dgMcGVRaSus55lCeZaBj2g7ex/RCRSS',NULL,'ACTIVE',NULL,1,0,'LOW','2026-08-23 19:08:21','2026-08-23 19:08:21',NULL,NULL,NULL,NULL,NULL,NULL),(2,'Paid User','9000000002','paid-user@example.test','$2y$12$UF1XKn0V3sqB6ietomUWSOV19sOi2T9RBE9A2Q4FV1SGgqQiUgIoe',NULL,'ACTIVE',NULL,1,0,'LOW','2026-08-23 19:08:22','2026-08-23 19:08:22',NULL,NULL,NULL,NULL,NULL,NULL),(3,'Bonus QR User','9000000099','bonus-qr@example.test','$2y$12$zKWIO8uJY7FRR2RSnMVkleHruTKPf/KdTwSYtMdUCYyg0PC.UMw8S',NULL,'ACTIVE',NULL,1,0,'LOW','2026-08-23 19:08:23','2026-08-23 19:08:23',NULL,NULL,NULL,NULL,NULL,NULL),(4,'Demo Customer','9000000003','customer.demo@example.test','$2y$12$77vNCiHbtcN/WMCqDMnmcumd.05N0tfONuDLzhw1kmMIg229nb.xm',NULL,'ACTIVE',NULL,1,0,'LOW','2026-08-29 11:29:07','2026-10-17 19:58:00','2026-10-17 19:58:00','127.0.0.1',NULL,NULL,NULL,NULL),(6,'Bonus QR User','9151874501','bonus-qr-b59f52db@example.test','UserPassword123!',NULL,'ACTIVE',NULL,1,0,'LOW','2026-10-17 18:36:24','2026-10-17 18:36:24',NULL,NULL,NULL,NULL,NULL,NULL),(7,'Hunt User','9351198761','hunt-user-8ce8ea53@example.test','$2y$12$wszlupqbNBEtGqNL4.kauOX4t1BVKmcjaQ/VqISiJ8YFvHAuDX5.y',NULL,'ACTIVE',NULL,1,0,'LOW','2026-10-17 18:37:51','2026-10-17 18:37:51',NULL,NULL,NULL,NULL,NULL,NULL),(8,'Paid User','9764784136','paid-user-8ce8ea53@example.test','$2y$12$2pde2VRudOIxLW9iTCuPbekW0rAo7CfJwqDL6UGecW9/TMvK5Meh6',NULL,'ACTIVE',NULL,1,0,'LOW','2026-10-17 18:37:51','2026-10-17 18:37:51',NULL,NULL,NULL,NULL,NULL,NULL),(9,'Hunt User','9794857634','hunt-user-129b73ae@example.test','$2y$12$n8cYsWFwPAlhxkqBkMwc7uJqnn5TwjWtKeA2P9xGKS0lrmniZooJ.',NULL,'ACTIVE',NULL,1,0,'LOW','2026-10-17 18:38:34','2026-10-17 18:38:34',NULL,NULL,NULL,NULL,NULL,NULL),(10,'Paid User','9505514906','paid-user-129b73ae@example.test','$2y$12$G6epx9w/SBY3...bwhrvxObyaOo9cnA4KUXCTO2z3wkIxchCW6gGS',NULL,'ACTIVE',NULL,1,0,'LOW','2026-10-17 18:38:34','2026-10-17 18:38:34',NULL,NULL,NULL,NULL,NULL,NULL),(11,'Hunt User','9893391939','hunt-user-1bac2a6b@example.test','$2y$12$RObUo2DzrFwUhhm5fev3s.VcvHz6XHcVSCGNDVWMUfAujDUtyF/QW',NULL,'ACTIVE',NULL,1,0,'LOW','2026-10-17 18:38:56','2026-10-17 18:38:56',NULL,NULL,NULL,NULL,NULL,NULL),(12,'Paid User','9289900374','paid-user-1bac2a6b@example.test','$2y$12$D0jRklkUhpcgoUuJT5so1eIU/fZ3Zp.AuExSkmj2zDN2OcndCnbT2',NULL,'ACTIVE',NULL,1,0,'LOW','2026-10-17 18:38:56','2026-10-17 18:38:56',NULL,NULL,NULL,NULL,NULL,NULL),(14,'Bonus QR User','9343327683','bonus-qr-93bd279a@example.test','UserPassword123!',NULL,'ACTIVE',NULL,1,0,'LOW','2026-10-17 19:57:16','2026-10-17 19:57:16',NULL,NULL,NULL,NULL,NULL,NULL),(15,'Hunt User','9841233522','hunt-user-97790886@example.test','$2y$12$Zwkz4YtnMdAiuks.yEdud.giFjQrY73tCDtoPzhYuW5/X7w2MSEs2',NULL,'ACTIVE',NULL,1,0,'LOW','2026-10-17 19:57:24','2026-10-17 19:57:24',NULL,NULL,NULL,NULL,NULL,NULL),(16,'Paid User','9361448607','paid-user-97790886@example.test','$2y$12$4flOImG9/BJz1VPYuF81B.TwV9U980kAlJhIWbnkvkYPs582Q5SlC',NULL,'ACTIVE',NULL,1,0,'LOW','2026-10-17 19:57:24','2026-10-17 19:57:24',NULL,NULL,NULL,NULL,NULL,NULL),(17,'Bonus QR User','9714817511','bonus-qr-84aaf380@example.test','UserPassword123!',NULL,'ACTIVE',NULL,1,0,'LOW','2026-10-17 21:21:24','2026-10-17 21:21:24',NULL,NULL,NULL,NULL,NULL,NULL),(18,'Hunt User','9207242091','hunt-user-6415b496@example.test','$2y$12$No5o.tLd4as6nahupPxgoe/UzwKLBZTj007VJ4ojmh.Nwut33y3qO',NULL,'ACTIVE',NULL,1,0,'LOW','2026-10-17 21:21:25','2026-10-17 21:21:25',NULL,NULL,NULL,NULL,NULL,NULL),(19,'Paid User','9244910501','paid-user-6415b496@example.test','$2y$12$d2Y.oZ8jJYFUvbJD9tDmlueZCmOU51gBU6oar6XCZUqNFR9KJdraG',NULL,'ACTIVE',NULL,1,0,'LOW','2026-10-17 21:21:25','2026-10-17 21:21:25',NULL,NULL,NULL,NULL,NULL,NULL),(20,'Test Hunter User','9126476008','hunter-353d6fb1@example.test','$2y$12$R3QQaf0DA.uenYKMtx3uG.So4IBkh5Ka0uEWSwhCkBLk56vIFadWO',NULL,'ACTIVE',NULL,1,0,'LOW','2026-10-17 21:32:33','2026-10-17 21:32:33',NULL,NULL,NULL,NULL,NULL,NULL),(21,'Test Hunter User','9912568339','hunter-183d672e@example.test','$2y$12$d/6FKG/oLsBEWlDSrmOJP.s3TebkpQu8H2ZQ5LtD9.mheT5QXkPXG',NULL,'ACTIVE',NULL,1,0,'LOW','2026-10-17 21:35:19','2026-10-17 21:35:19',NULL,NULL,NULL,NULL,NULL,NULL),(22,'Test Hunter User','9776647490','hunter-4120c86a@example.test','$2y$12$HH6oa7ydeuBJdo78o92ZTucu36.Dj.sFmOg31X8cEFOktZyOd/WkO',NULL,'ACTIVE',NULL,1,0,'LOW','2026-10-17 21:37:26','2026-10-17 21:37:26',NULL,NULL,NULL,NULL,NULL,NULL),(23,'Test Hunter User','9618427617','hunter-32a00416@example.test','$2y$12$ddJRt7eeX3TUY3zgA34xqOumDwBk7x7aSBLV0Wav4icDgs2vxzYvq',NULL,'ACTIVE',NULL,1,0,'LOW','2026-10-17 21:38:31','2026-10-17 21:38:31',NULL,NULL,NULL,NULL,NULL,NULL),(24,'Bonus QR User','9707941641','bonus-qr-eefa6591@example.test','UserPassword123!',NULL,'ACTIVE',NULL,1,0,'LOW','2026-10-17 21:38:32','2026-10-17 21:38:32',NULL,NULL,NULL,NULL,NULL,NULL),(25,'Hunt User','9357672963','hunt-user-d1411073@example.test','$2y$12$fbgNb4Q4L1CTZyt4O4jFn.xtvq66N/lXqi1.Xa0qzt1Dbbaz1Ja92',NULL,'ACTIVE',NULL,1,0,'LOW','2026-10-17 21:38:33','2026-10-17 21:38:33',NULL,NULL,NULL,NULL,NULL,NULL),(26,'Paid User','9655108464','paid-user-d1411073@example.test','$2y$12$.6N.SfjAZFa4T6SpUGh3uu3a4JqgtZbzPrRuDzPxGJzDEFJb/ql1O',NULL,'ACTIVE',NULL,1,0,'LOW','2026-10-17 21:38:33','2026-10-17 21:38:33',NULL,NULL,NULL,NULL,NULL,NULL),(27,'Test Hunter User','9493748861','hunter-4f67df73@example.test','$2y$12$GnglaHurz0iXt39ZO9qWjeOnLzkz6fHpNDCLJXjtJCB9vtwhklH7i',NULL,'ACTIVE',NULL,1,0,'LOW','2026-10-17 22:51:21','2026-10-17 22:51:21',NULL,NULL,NULL,NULL,NULL,NULL),(28,'Bonus QR User','9997246256','bonus-qr-c4b8a794@example.test','UserPassword123!',NULL,'ACTIVE',NULL,1,0,'LOW','2026-10-17 22:51:23','2026-10-17 22:51:23',NULL,NULL,NULL,NULL,NULL,NULL),(29,'Hunt User','9253513119','hunt-user-3439d2e2@example.test','$2y$12$U4i0ao50kNcOIXSgZeeZVuSIuCj8naRR1ZyFBF0bvkpNurKP38QAa',NULL,'ACTIVE',NULL,1,0,'LOW','2026-10-17 22:51:24','2026-10-17 22:51:24',NULL,NULL,NULL,NULL,NULL,NULL),(30,'Paid User','9297591627','paid-user-3439d2e2@example.test','$2y$12$ekBqxV2AaODJEdTwxTdLcuBR3XGBCgq5oegxzHvH7Vp8yzm9XEZcC',NULL,'ACTIVE',NULL,1,0,'LOW','2026-10-17 22:51:24','2026-10-17 22:51:24',NULL,NULL,NULL,NULL,NULL,NULL),(31,'Test Hunter User','9555157815','hunter-2a0bf7e2@example.test','$2y$12$ZqsE2PPhAjqFjGcYvW5eUuuvL8x1HdkyFSJ2SCzWr6Mz24pbyrdF6',NULL,'ACTIVE',NULL,1,0,'LOW','2026-10-18 09:25:15','2026-10-18 09:25:15',NULL,NULL,NULL,NULL,NULL,NULL),(32,'Bonus QR User','9218912204','bonus-qr-8d4dc97f@example.test','UserPassword123!',NULL,'ACTIVE',NULL,1,0,'LOW','2026-10-18 09:25:16','2026-10-18 09:25:16',NULL,NULL,NULL,NULL,NULL,NULL),(33,'Hunt User','9450325936','hunt-user-86f7090e@example.test','$2y$12$Yb1feA2khTG1k.O8jqBNGeQhmCg8YwndKITydhvNXv4UqyLmrtblK',NULL,'ACTIVE',NULL,1,0,'LOW','2026-10-18 09:25:17','2026-10-18 09:25:17',NULL,NULL,NULL,NULL,NULL,NULL),(34,'Paid User','9802126334','paid-user-86f7090e@example.test','$2y$12$3qr1dTBc8pOQAfFVVbDx8OMB0sALyzJ9Hd0Oxo08y2CAOr.PT/sTa',NULL,'ACTIVE',NULL,1,0,'LOW','2026-10-18 09:25:18','2026-10-18 09:25:18',NULL,NULL,NULL,NULL,NULL,NULL);
/*!40000 ALTER TABLE `users` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `vendor_login_attempts`
--

DROP TABLE IF EXISTS `vendor_login_attempts`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `vendor_login_attempts` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `identity_hash` char(64) COLLATE utf8mb4_unicode_ci NOT NULL,
  `ip_address` varchar(45) COLLATE utf8mb4_unicode_ci NOT NULL,
  `successful` tinyint(1) NOT NULL DEFAULT '0',
  `attempted_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_vendor_login_rate` (`identity_hash`,`ip_address`,`attempted_at`)
) ENGINE=InnoDB AUTO_INCREMENT=42 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `vendor_login_attempts`
--

LOCK TABLES `vendor_login_attempts` WRITE;
/*!40000 ALTER TABLE `vendor_login_attempts` DISABLE KEYS */;
INSERT INTO `vendor_login_attempts` VALUES (1,'da5653469d0e01da4eda96d361ff6dbd8fd499ea8883c679f57a66e0f3fad756','127.0.0.1',0,'2026-09-11 19:16:29'),(2,'eb92cc52a766c500e001d02f1eaa78784da47f8c96b019ce874d3e3c205b4e3c','127.0.0.1',0,'2026-09-11 19:24:21'),(3,'5d1ce093d11f093703a4eb9903c720a1b97b838c0ae4fcef561d6edc243d5b45','127.0.0.1',0,'2026-09-11 19:32:55'),(4,'36035da416089b0f1a075642fa959d4ed6e956002f1d40057f44bfe4d845dd8b','127.0.0.1',0,'2026-09-11 19:44:05'),(5,'d502cfcfc4860af6b0ed6144fde9f1182b491694ddf48c13fb08ab9660a426d6','127.0.0.1',0,'2026-09-11 19:44:31'),(6,'5d1ce093d11f093703a4eb9903c720a1b97b838c0ae4fcef561d6edc243d5b45','127.0.0.1',1,'2026-09-11 19:51:44'),(7,'36035da416089b0f1a075642fa959d4ed6e956002f1d40057f44bfe4d845dd8b','127.0.0.1',1,'2026-09-11 21:33:06'),(8,'5d1ce093d11f093703a4eb9903c720a1b97b838c0ae4fcef561d6edc243d5b45','127.0.0.1',1,'2026-09-11 21:41:30'),(9,'36035da416089b0f1a075642fa959d4ed6e956002f1d40057f44bfe4d845dd8b','127.0.0.1',1,'2026-09-12 16:33:17'),(10,'36035da416089b0f1a075642fa959d4ed6e956002f1d40057f44bfe4d845dd8b','127.0.0.1',1,'2026-09-13 12:08:23'),(11,'36035da416089b0f1a075642fa959d4ed6e956002f1d40057f44bfe4d845dd8b','127.0.0.1',1,'2026-09-13 14:36:01'),(12,'36035da416089b0f1a075642fa959d4ed6e956002f1d40057f44bfe4d845dd8b','127.0.0.1',0,'2026-10-17 18:57:21'),(13,'36035da416089b0f1a075642fa959d4ed6e956002f1d40057f44bfe4d845dd8b','127.0.0.1',1,'2026-10-17 18:57:21'),(14,'36035da416089b0f1a075642fa959d4ed6e956002f1d40057f44bfe4d845dd8b','127.0.0.1',0,'2026-10-17 18:57:44'),(15,'36035da416089b0f1a075642fa959d4ed6e956002f1d40057f44bfe4d845dd8b','127.0.0.1',1,'2026-10-17 18:57:45'),(16,'36035da416089b0f1a075642fa959d4ed6e956002f1d40057f44bfe4d845dd8b','127.0.0.1',1,'2026-10-17 18:59:12'),(17,'36035da416089b0f1a075642fa959d4ed6e956002f1d40057f44bfe4d845dd8b','127.0.0.1',1,'2026-10-17 18:59:29'),(18,'36035da416089b0f1a075642fa959d4ed6e956002f1d40057f44bfe4d845dd8b','127.0.0.1',1,'2026-10-17 19:36:35'),(19,'36035da416089b0f1a075642fa959d4ed6e956002f1d40057f44bfe4d845dd8b','127.0.0.1',1,'2026-10-17 19:53:00'),(20,'36035da416089b0f1a075642fa959d4ed6e956002f1d40057f44bfe4d845dd8b','127.0.0.1',1,'2026-10-17 19:54:32'),(21,'36035da416089b0f1a075642fa959d4ed6e956002f1d40057f44bfe4d845dd8b','127.0.0.1',1,'2026-10-17 19:55:32'),(22,'36035da416089b0f1a075642fa959d4ed6e956002f1d40057f44bfe4d845dd8b','127.0.0.1',1,'2026-10-17 19:56:01'),(23,'36035da416089b0f1a075642fa959d4ed6e956002f1d40057f44bfe4d845dd8b','127.0.0.1',0,'2026-10-17 19:56:35'),(24,'36035da416089b0f1a075642fa959d4ed6e956002f1d40057f44bfe4d845dd8b','127.0.0.1',1,'2026-10-17 19:56:35'),(25,'36035da416089b0f1a075642fa959d4ed6e956002f1d40057f44bfe4d845dd8b','127.0.0.1',0,'2026-10-17 20:12:44'),(26,'36035da416089b0f1a075642fa959d4ed6e956002f1d40057f44bfe4d845dd8b','127.0.0.1',1,'2026-10-17 20:12:44'),(27,'36035da416089b0f1a075642fa959d4ed6e956002f1d40057f44bfe4d845dd8b','127.0.0.1',1,'2026-10-17 20:18:16'),(28,'36035da416089b0f1a075642fa959d4ed6e956002f1d40057f44bfe4d845dd8b','127.0.0.1',1,'2026-10-17 21:37:57'),(29,'36035da416089b0f1a075642fa959d4ed6e956002f1d40057f44bfe4d845dd8b','127.0.0.1',1,'2026-10-17 21:38:18'),(30,'36035da416089b0f1a075642fa959d4ed6e956002f1d40057f44bfe4d845dd8b','127.0.0.1',1,'2026-10-17 22:51:31'),(31,'36035da416089b0f1a075642fa959d4ed6e956002f1d40057f44bfe4d845dd8b','127.0.0.1',0,'2026-10-18 09:40:00'),(32,'36035da416089b0f1a075642fa959d4ed6e956002f1d40057f44bfe4d845dd8b','127.0.0.1',1,'2026-10-18 09:40:00'),(33,'36035da416089b0f1a075642fa959d4ed6e956002f1d40057f44bfe4d845dd8b','127.0.0.1',1,'2026-10-18 09:42:12'),(34,'36035da416089b0f1a075642fa959d4ed6e956002f1d40057f44bfe4d845dd8b','127.0.0.1',1,'2026-10-18 09:53:10'),(35,'36035da416089b0f1a075642fa959d4ed6e956002f1d40057f44bfe4d845dd8b','127.0.0.1',1,'2026-10-18 09:53:43'),(36,'36035da416089b0f1a075642fa959d4ed6e956002f1d40057f44bfe4d845dd8b','127.0.0.1',1,'2026-10-18 09:56:04'),(37,'36035da416089b0f1a075642fa959d4ed6e956002f1d40057f44bfe4d845dd8b','127.0.0.1',1,'2026-10-18 10:31:34'),(38,'36035da416089b0f1a075642fa959d4ed6e956002f1d40057f44bfe4d845dd8b','127.0.0.1',1,'2026-10-18 10:32:28'),(39,'36035da416089b0f1a075642fa959d4ed6e956002f1d40057f44bfe4d845dd8b','127.0.0.1',0,'2026-10-18 10:35:58'),(40,'36035da416089b0f1a075642fa959d4ed6e956002f1d40057f44bfe4d845dd8b','127.0.0.1',1,'2026-10-18 10:35:58'),(41,'36035da416089b0f1a075642fa959d4ed6e956002f1d40057f44bfe4d845dd8b','127.0.0.1',1,'2026-10-18 10:44:37');
/*!40000 ALTER TABLE `vendor_login_attempts` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `vendor_money_requests`
--

DROP TABLE IF EXISTS `vendor_money_requests`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `vendor_money_requests` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `vendor_id` bigint unsigned NOT NULL,
  `request_code` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `amount` decimal(18,2) NOT NULL,
  `destination_type` enum('BANK','UPI','WALLET') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'WALLET',
  `destination_reference` varchar(190) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `status` enum('PENDING','UNDER_REVIEW','APPROVED','PROCESSING','SETTLED','REJECTED','HELD','FAILED','DISPUTED') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'PENDING',
  `admin_note` varchar(1000) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `processed_by` int unsigned DEFAULT NULL,
  `processed_at` datetime DEFAULT NULL,
  `requested_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `request_code` (`request_code`),
  KEY `processed_by` (`processed_by`),
  KEY `idx_vendor_money_status` (`status`,`requested_at`),
  KEY `idx_vendor_money_vendor` (`vendor_id`),
  CONSTRAINT `vendor_money_requests_ibfk_1` FOREIGN KEY (`vendor_id`) REFERENCES `vendors` (`id`),
  CONSTRAINT `vendor_money_requests_ibfk_2` FOREIGN KEY (`processed_by`) REFERENCES `admins` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `vendor_money_requests`
--

LOCK TABLES `vendor_money_requests` WRITE;
/*!40000 ALTER TABLE `vendor_money_requests` DISABLE KEYS */;
/*!40000 ALTER TABLE `vendor_money_requests` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `vendor_otp_challenges`
--

DROP TABLE IF EXISTS `vendor_otp_challenges`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `vendor_otp_challenges` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `vendor_user_id` bigint unsigned NOT NULL,
  `email` varchar(190) COLLATE utf8mb4_unicode_ci NOT NULL,
  `otp_hash` char(64) COLLATE utf8mb4_unicode_ci NOT NULL,
  `purpose` enum('LOGIN','PASSWORD_RESET') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'PASSWORD_RESET',
  `attempts` tinyint unsigned NOT NULL DEFAULT '0',
  `expires_at` datetime NOT NULL,
  `consumed_at` datetime DEFAULT NULL,
  `ip_address` varchar(45) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_vendor_otp` (`vendor_user_id`,`purpose`,`expires_at`),
  CONSTRAINT `vendor_otp_challenges_ibfk_1` FOREIGN KEY (`vendor_user_id`) REFERENCES `vendor_users` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `vendor_otp_challenges`
--

LOCK TABLES `vendor_otp_challenges` WRITE;
/*!40000 ALTER TABLE `vendor_otp_challenges` DISABLE KEYS */;
/*!40000 ALTER TABLE `vendor_otp_challenges` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `vendor_payout_requests`
--

DROP TABLE IF EXISTS `vendor_payout_requests`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `vendor_payout_requests` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `vendor_id` bigint unsigned NOT NULL,
  `amount` decimal(18,2) NOT NULL,
  `status` enum('PENDING','UNDER_REVIEW','APPROVED','PROCESSING','PAID','REJECTED','HELD') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'PENDING',
  `requested_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `paid_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_vendor_payout_status` (`vendor_id`,`status`),
  CONSTRAINT `vendor_payout_requests_ibfk_1` FOREIGN KEY (`vendor_id`) REFERENCES `vendors` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `vendor_payout_requests`
--

LOCK TABLES `vendor_payout_requests` WRITE;
/*!40000 ALTER TABLE `vendor_payout_requests` DISABLE KEYS */;
INSERT INTO `vendor_payout_requests` VALUES (1,1,500.00,'PENDING','2026-10-17 19:44:06',NULL);
/*!40000 ALTER TABLE `vendor_payout_requests` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `vendor_products`
--

DROP TABLE IF EXISTS `vendor_products`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `vendor_products` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `vendor_id` bigint unsigned NOT NULL,
  `category_id` bigint unsigned DEFAULT NULL,
  `product_code` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `title` varchar(190) COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` text COLLATE utf8mb4_unicode_ci,
  `price` decimal(18,2) NOT NULL DEFAULT '0.00',
  `image_url` varchar(500) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `metadata` json DEFAULT NULL,
  `status` enum('PENDING_REVIEW','APPROVED','REJECTED','DISABLED','RESUBMITTED') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'PENDING_REVIEW',
  `admin_note` varchar(1000) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `moderated_by` int unsigned DEFAULT NULL,
  `moderated_at` datetime DEFAULT NULL,
  `submitted_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `product_code` (`product_code`),
  KEY `moderated_by` (`moderated_by`),
  KEY `idx_vendor_product_status` (`status`,`submitted_at`),
  KEY `idx_vendor_product_vendor` (`vendor_id`),
  KEY `fk_vendor_product_category` (`category_id`),
  CONSTRAINT `fk_vendor_product_category` FOREIGN KEY (`category_id`) REFERENCES `product_categories` (`id`) ON DELETE SET NULL,
  CONSTRAINT `vendor_products_ibfk_1` FOREIGN KEY (`vendor_id`) REFERENCES `vendors` (`id`),
  CONSTRAINT `vendor_products_ibfk_2` FOREIGN KEY (`moderated_by`) REFERENCES `admins` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `vendor_products`
--

LOCK TABLES `vendor_products` WRITE;
/*!40000 ALTER TABLE `vendor_products` DISABLE KEYS */;
INSERT INTO `vendor_products` VALUES (1,3,NULL,'PRD-TEST-001','Test Product 1','Sample fixture product',299.00,NULL,NULL,'APPROVED',NULL,NULL,NULL,'2026-10-17 19:53:19','2026-10-17 19:53:19'),(2,3,NULL,'PRD-FIT-001','Smart Fitness Tracker Band','Real-time heart rate and step tracking with OLED screen.',1499.00,NULL,NULL,'APPROVED',NULL,NULL,NULL,'2026-10-18 09:50:53','2026-10-18 09:50:53'),(3,3,NULL,'PRD-EAR-002','Wireless Noise-Canceling Earbuds','Bluetooth 5.3 with 30-hour playback and deep bass.',2299.00,NULL,NULL,'APPROVED',NULL,NULL,NULL,'2026-10-18 09:50:53','2026-10-18 09:50:53'),(4,3,NULL,'PRD-TEA-003','Organic Green Tea Herbal Pack','100% natural Darjeeling whole leaf herbal green tea.',350.00,NULL,NULL,'APPROVED',NULL,NULL,NULL,'2026-10-18 09:50:53','2026-10-18 09:50:53'),(5,3,NULL,'PRD-TSH-004','Premium Cotton Graphic T-Shirt','Breathable combed cotton tee with modern minimalist print.',699.00,NULL,NULL,'APPROVED',NULL,NULL,NULL,'2026-10-18 09:50:53','2026-10-18 09:50:53');
/*!40000 ALTER TABLE `vendor_products` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `vendor_sessions`
--

DROP TABLE IF EXISTS `vendor_sessions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `vendor_sessions` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `vendor_user_id` bigint unsigned NOT NULL,
  `token_hash` char(64) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `php_session_hash` char(64) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `ip_address` varchar(45) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `expires_at` datetime NOT NULL,
  `revoked_at` datetime DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `token_hash` (`token_hash`),
  UNIQUE KEY `php_session_hash` (`php_session_hash`),
  KEY `vendor_user_id` (`vendor_user_id`),
  CONSTRAINT `vendor_sessions_ibfk_1` FOREIGN KEY (`vendor_user_id`) REFERENCES `vendor_users` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=31 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `vendor_sessions`
--

LOCK TABLES `vendor_sessions` WRITE;
/*!40000 ALTER TABLE `vendor_sessions` DISABLE KEYS */;
INSERT INTO `vendor_sessions` VALUES (1,2,NULL,'b43b5f126a7385a6418917292cd3d26af9613f5200cd6fd17d757ae8a4af247f','127.0.0.1','2026-09-12 03:51:44','2026-09-11 21:41:06','2026-09-11 19:51:44'),(2,2,NULL,'f564500f0dfb7161f51b8001e78e38ab02c808063c56a96511ad7172512da602','127.0.0.1','2026-09-12 05:33:06',NULL,'2026-09-11 21:33:06'),(3,2,NULL,'f0f44da665844ffbff955e30ea6e73219df29126a34237665e13290a613c5019','127.0.0.1','2026-09-12 05:41:30',NULL,'2026-09-11 21:41:30'),(4,2,NULL,'1dac591389da34996c16177f72fedaa31b4a42bacf26fd9eb06c0711317621df','127.0.0.1','2026-09-13 00:33:17',NULL,'2026-09-12 16:33:17'),(5,2,NULL,'034e664536ade2a8c35f9fc398dfb7fb644a63382289b7ebe4dc8f78b51024b5','127.0.0.1','2026-09-13 20:08:23',NULL,'2026-09-13 12:08:23'),(6,2,NULL,'fe43e1c5d390b5c13ec63a8e5cd9a10d6ec7efd0e28480e7e67aac3e52eb23f4','127.0.0.1','2026-09-13 22:36:01',NULL,'2026-09-13 14:36:01'),(7,2,NULL,'b40490bd8e7bb94720acae59e88b0badedd36d03054d3425e952f22f21d02126','127.0.0.1','2026-10-18 02:57:21','2026-10-17 18:57:21','2026-10-17 18:57:21'),(8,2,NULL,'cb8d262a331b28ef93d6f2eb78c41dd347ef3d32bc1a391ee522b4a57d7f11a9','127.0.0.1','2026-10-18 02:57:45','2026-10-17 18:57:45','2026-10-17 18:57:45'),(9,2,NULL,'be66509fcd69a263709ad644b880e6486fadaa88c8c1e2ae10613a19a66ddbbf','127.0.0.1','2026-10-18 02:59:12',NULL,'2026-10-17 18:59:12'),(10,2,NULL,'e958f279746d158c861b00294281c22717e7f6c6e9bdbbab522ab815a8fadaec','127.0.0.1','2026-10-18 02:59:29',NULL,'2026-10-17 18:59:29'),(11,2,NULL,'88ca663f15db8b981ac1a2953aa9397d67f4a24884c596bce8abcaf712fdacea','127.0.0.1','2026-10-18 03:36:35',NULL,'2026-10-17 19:36:35'),(12,2,NULL,'c2712ea953c0a711fe2bc4d089f13ed4a32ce4404895fd95b1f26595a3adca7f','127.0.0.1','2026-10-18 03:53:00',NULL,'2026-10-17 19:53:00'),(13,2,NULL,'66a53e7b3c3785283777778a910aab232c32b415ee71ef9d0b1b5a128dd5cab5','127.0.0.1','2026-10-18 03:54:32',NULL,'2026-10-17 19:54:32'),(14,2,NULL,'1497a0e28e8815dde6d715e6d522826f1da6c86feddecc7cd5ef74a9806f7fab','127.0.0.1','2026-10-18 03:55:32',NULL,'2026-10-17 19:55:32'),(15,2,NULL,'2b3bdf15a01e08c29c72f638addb068fd1d8d435eb29004896bd7c5bbc4e27fd','127.0.0.1','2026-10-18 03:56:01',NULL,'2026-10-17 19:56:01'),(16,2,NULL,'1a60cd609f98ccb35285eaa5dcce0ef63f6c0d8bc48bcbb6a2c965bb98c6e149','127.0.0.1','2026-10-18 03:56:35','2026-10-17 19:56:35','2026-10-17 19:56:35'),(17,2,NULL,'35a8ca72dc289f2f63e52fa4063f93ff37fdf17510688c59a7c57a5bd3806c41','127.0.0.1','2026-10-18 04:12:44','2026-10-17 20:12:44','2026-10-17 20:12:44'),(18,2,NULL,'aa53478ec327e9951aded39ff6dc7dc9395d4917b16cc0e4f0ae73a1db8df597','127.0.0.1','2026-10-18 04:18:16',NULL,'2026-10-17 20:18:16'),(19,2,NULL,'9753e2f513f5a8b8d69d74620c62d1e8eb8bce1e6ca2349c760a1bf148d0498c','127.0.0.1','2026-10-18 05:37:57',NULL,'2026-10-17 21:37:57'),(20,2,NULL,'9a60c855a8291a2b54a36f8728b9e34e2143f397d2e14d2de9f4ebb0e654ce52','127.0.0.1','2026-10-18 05:38:18',NULL,'2026-10-17 21:38:18'),(21,2,NULL,'f450ea3cd0b72587ddfd9723f16ebbead9f2f57217b89a1c871b091c1fd86a3e','127.0.0.1','2026-10-18 06:51:31',NULL,'2026-10-17 22:51:31'),(22,2,NULL,'3e16f85c486bfe9b55a3bc20874c2a8d6992f8b74282b3c7acf11026e4fbdbcf','127.0.0.1','2026-10-18 17:40:00','2026-10-18 09:40:00','2026-10-18 09:40:00'),(23,2,NULL,'8aabc99f8e322ad0cafc869c00a630ba270b2b54bb52c6764fcca862e562f0fa','127.0.0.1','2026-10-18 17:42:12',NULL,'2026-10-18 09:42:12'),(24,2,NULL,'59c6ea24bd9053c97df49d97b459d52d4d0d8478cf7f688a817df54149bf71d8','127.0.0.1','2026-10-18 17:53:10',NULL,'2026-10-18 09:53:10'),(25,2,NULL,'fbbb5126fad6abc5db2eb1a752ffefe68662fb3b25b3abe234b1786f0f5bf102','127.0.0.1','2026-10-18 17:53:43',NULL,'2026-10-18 09:53:43'),(26,2,NULL,'ae73f582728f348c5f5af6859f8446f15c46ce7cf6394b5faddd4c28cf1fb06d','127.0.0.1','2026-10-18 17:56:04',NULL,'2026-10-18 09:56:04'),(27,2,NULL,'5d8da276450a1af9bdbb58d1b1bc70ffc0d2b19f8486e6f7813e161df1be484a','127.0.0.1','2026-10-18 18:31:34',NULL,'2026-10-18 10:31:34'),(28,2,NULL,'ecabe8d6ff8ba7b04f5eff9d2766a484c720ba980998c34686474912061a609b','127.0.0.1','2026-10-18 18:32:28',NULL,'2026-10-18 10:32:28'),(29,2,NULL,'4a549b27d364bdfde1460975a42f34d09c46864e6faf521170b0179687d3d1e5','127.0.0.1','2026-10-18 18:35:58','2026-10-18 10:35:58','2026-10-18 10:35:58'),(30,2,NULL,'12dda1e09f9531784df739801e587a231f67c80ca64f8de4cf90b49b5e86bcaa','127.0.0.1','2026-10-18 18:44:37','2026-10-18 10:47:16','2026-10-18 10:44:37');
/*!40000 ALTER TABLE `vendor_sessions` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `vendor_users`
--

DROP TABLE IF EXISTS `vendor_users`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `vendor_users` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `vendor_id` bigint unsigned NOT NULL,
  `name` varchar(120) COLLATE utf8mb4_unicode_ci NOT NULL,
  `username` varchar(80) COLLATE utf8mb4_unicode_ci NOT NULL,
  `email` varchar(190) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `password_hash` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `status` enum('ACTIVE','BLOCKED') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'ACTIVE',
  `last_login_at` datetime DEFAULT NULL,
  `last_login_ip` varchar(45) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `mfa_enabled` tinyint(1) NOT NULL DEFAULT '0',
  `mfa_secret_encrypted` varchar(500) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `username` (`username`),
  UNIQUE KEY `email` (`email`),
  KEY `vendor_id` (`vendor_id`),
  CONSTRAINT `vendor_users_ibfk_1` FOREIGN KEY (`vendor_id`) REFERENCES `vendors` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=9 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `vendor_users`
--

LOCK TABLES `vendor_users` WRITE;
/*!40000 ALTER TABLE `vendor_users` DISABLE KEYS */;
INSERT INTO `vendor_users` VALUES (1,1,'Clerk','clerk-test',NULL,'$2y$12$/WR382F6PBlX8.tP/BKDbum2SEF/mIzzCeEPjV3pBgmNIyuXGbLa6','ACTIVE',NULL,NULL,'2026-08-23 19:08:23',0,NULL),(2,3,'Demo Vendor User','vendor.test','vendor.test@hunterwallet.test','$2y$12$GWbW297o.LMekrFP4ivSyOJRHTP9LFRdy.ZMYR5ktWLjOio2FJiPW','ACTIVE','2026-10-18 10:44:37','127.0.0.1','2026-09-11 19:51:13',0,NULL),(3,4,'Clerk','clerk-1bac2a6b',NULL,'$2y$12$5vIFsx3Z0Fha1yLx8DrVh.z9Ls2443y4V5l8z7TaCxJSrUV.eJP36','ACTIVE',NULL,NULL,'2026-10-17 18:38:58',0,NULL),(4,5,'Clerk','clerk-97790886',NULL,'$2y$12$ijUWm1j00kPSm02O7SREeOaSrwH6IHPtdJilTY2T0/So7RRR2ySDS','ACTIVE',NULL,NULL,'2026-10-17 19:57:26',0,NULL),(5,6,'Clerk','clerk-6415b496',NULL,'$2y$12$lQOoMUvU.pjRPqt90POS2e62VowpIxaKegZRnc4UycTgelFZ5oV96','ACTIVE',NULL,NULL,'2026-10-17 21:21:26',0,NULL),(6,7,'Clerk','clerk-d1411073',NULL,'$2y$12$tlPzaelFQs5Bb5fxxe72cO9ps/OYvUuA7jkyWQ3Uw8vl.1BS5vX36','ACTIVE',NULL,NULL,'2026-10-17 21:38:34',0,NULL),(7,8,'Clerk','clerk-3439d2e2',NULL,'$2y$12$MTOIa67MXoUDMZ5c9gxBo.5WpOSLELnl0iHWz.qVSdeaGgp8DOmEW','ACTIVE',NULL,NULL,'2026-10-17 22:51:25',0,NULL),(8,9,'Clerk','clerk-86f7090e',NULL,'$2y$12$2u9N5u5H0Fhwo8QEqW2a9eNp/Tvf83TESzX9sXtvMtwZbMTpo9lq.','ACTIVE',NULL,NULL,'2026-10-18 09:25:19',0,NULL);
/*!40000 ALTER TABLE `vendor_users` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `vendors`
--

DROP TABLE IF EXISTS `vendors`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `vendors` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `vendor_code` varchar(40) COLLATE utf8mb4_unicode_ci NOT NULL,
  `name` varchar(190) COLLATE utf8mb4_unicode_ci NOT NULL,
  `shop_name` varchar(190) COLLATE utf8mb4_unicode_ci NOT NULL,
  `business_category` varchar(120) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `owner_name` varchar(190) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `owner_address` varchar(500) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `gstin` varchar(30) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `gst_document_path` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `identity_proof_path` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `address_proof_path` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `cancelled_cheque_path` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `bank_account_holder` varchar(190) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `bank_account_number` varchar(80) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `bank_ifsc` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `bank_branch` varchar(120) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `settlement_upi` varchar(190) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `commission_rate` decimal(5,2) NOT NULL DEFAULT '0.00',
  `commission_type` enum('PERCENTAGE','CUSTOM_RULE') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'PERCENTAGE',
  `commission_rule` varchar(500) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `onboarding_notes` text COLLATE utf8mb4_unicode_ci,
  `vendor_qr_payload` varchar(500) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `escrow_balance` decimal(18,2) NOT NULL DEFAULT '0.00',
  `settlement_cycle` enum('DAILY','WEEKLY','MONTHLY') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'DAILY',
  `bank_verification_status` enum('PENDING','VERIFIED','REJECTED') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'PENDING',
  `email` varchar(190) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `mobile` varchar(30) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `mobile_otp_verified` tinyint(1) NOT NULL DEFAULT '0',
  `address` varchar(500) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `latitude` decimal(10,7) DEFAULT NULL,
  `longitude` decimal(10,7) DEFAULT NULL,
  `proximity_radius_m` int unsigned NOT NULL DEFAULT '300',
  `logo_path` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `cover_image_path` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `youtube_promo_url` varchar(500) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `status` enum('DRAFT','PENDING_KYC','PENDING_BANK','PENDING_APPROVAL','VERIFIED','ACTIVE','REJECTED','SUSPENDED','CLOSED','BLOCKED','INACTIVE') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'DRAFT',
  `created_by` int unsigned NOT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `kyc_status` enum('PENDING','APPROVED','VERIFIED','REJECTED') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'PENDING',
  `kyc_document_path` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `bank_name` varchar(190) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `vendor_code` (`vendor_code`),
  KEY `idx_vendor_status` (`status`),
  KEY `created_by` (`created_by`),
  CONSTRAINT `vendors_ibfk_1` FOREIGN KEY (`created_by`) REFERENCES `admins` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=10 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `vendors`
--

LOCK TABLES `vendors` WRITE;
/*!40000 ALTER TABLE `vendors` DISABLE KEYS */;
INSERT INTO `vendors` VALUES (1,'VND-TEST','Test Vendor','Test Shop',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,0.00,'PERCENTAGE',NULL,NULL,NULL,0.00,'DAILY','PENDING',NULL,NULL,0,NULL,NULL,NULL,300,NULL,NULL,NULL,'ACTIVE',1,'2026-08-23 19:08:23','2026-08-23 19:08:23','PENDING',NULL,NULL),(2,'DEMO-VENDOR','Demo Merchant','Demo Retail Counter',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,0.00,'PERCENTAGE',NULL,NULL,NULL,0.00,'DAILY','PENDING','merchant.demo@example.test','9000000099',0,'Patna Central Mall',NULL,NULL,300,NULL,NULL,NULL,'ACTIVE',1,'2026-08-29 11:29:07','2026-08-29 11:29:07','APPROVED',NULL,NULL),(3,'VND-TEST-001','Demo Retail Counter','Demo Retail Counter','Retail','Rajesh Kumar','Shop #12, Ground Floor, Central Plaza, Frazer Road, Patna','10ABCDE1234F1Z5',NULL,NULL,NULL,NULL,'Demo Retail Counter','50100456789123','HDFC0001234','Frazer Road, Patna','demoretail@hdfcbank',2.00,'PERCENTAGE',NULL,NULL,NULL,14500.00,'DAILY','VERIFIED','store@democounter.com','9876543210',0,'Demo retail counter for testing',NULL,NULL,300,NULL,NULL,NULL,'ACTIVE',1,'2026-09-11 19:51:13','2026-10-18 09:50:53','VERIFIED',NULL,'HDFC Bank'),(4,'VND-1BAC2A6B','Test Vendor','Test Shop',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,0.00,'PERCENTAGE',NULL,NULL,NULL,0.00,'DAILY','PENDING',NULL,NULL,0,NULL,NULL,NULL,300,NULL,NULL,NULL,'ACTIVE',6,'2026-10-17 18:38:57','2026-10-17 18:38:57','PENDING',NULL,NULL),(5,'VND-97790886','Test Vendor','Test Shop',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,0.00,'PERCENTAGE',NULL,NULL,NULL,0.00,'DAILY','PENDING',NULL,NULL,0,NULL,NULL,NULL,300,NULL,NULL,NULL,'ACTIVE',8,'2026-10-17 19:57:25','2026-10-17 19:57:25','PENDING',NULL,NULL),(6,'VND-6415B496','Test Vendor','Test Shop',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,0.00,'PERCENTAGE',NULL,NULL,NULL,0.00,'DAILY','PENDING',NULL,NULL,0,NULL,NULL,NULL,300,NULL,NULL,NULL,'ACTIVE',9,'2026-10-17 21:21:26','2026-10-17 21:21:26','PENDING',NULL,NULL),(7,'VND-D1411073','Test Vendor','Test Shop',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,0.00,'PERCENTAGE',NULL,NULL,NULL,0.00,'DAILY','PENDING',NULL,NULL,0,NULL,NULL,NULL,300,NULL,NULL,NULL,'ACTIVE',14,'2026-10-17 21:38:34','2026-10-17 21:38:34','PENDING',NULL,NULL),(8,'VND-3439D2E2','Test Vendor','Test Shop',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,0.00,'PERCENTAGE',NULL,NULL,NULL,0.00,'DAILY','PENDING',NULL,NULL,0,NULL,NULL,NULL,300,NULL,NULL,NULL,'ACTIVE',16,'2026-10-17 22:51:25','2026-10-17 22:51:25','PENDING',NULL,NULL),(9,'VND-86F7090E','Test Vendor','Test Shop',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,0.00,'PERCENTAGE',NULL,NULL,NULL,0.00,'DAILY','PENDING',NULL,NULL,0,NULL,NULL,NULL,300,NULL,NULL,NULL,'ACTIVE',18,'2026-10-18 09:25:18','2026-10-18 09:25:18','PENDING',NULL,NULL);
/*!40000 ALTER TABLE `vendors` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `video_ads`
--

DROP TABLE IF EXISTS `video_ads`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `video_ads` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `video_id` bigint unsigned NOT NULL,
  `enabled` tinyint(1) NOT NULL DEFAULT '1',
  `display_type` enum('PRE_ROLL','MID_ROLL','FULL_SCREEN') COLLATE utf8mb4_unicode_ci NOT NULL,
  `trigger_seconds` int unsigned DEFAULT NULL,
  `media_url` varchar(500) COLLATE utf8mb4_unicode_ci NOT NULL,
  `skip_after_seconds` int unsigned DEFAULT NULL,
  `cta_text` varchar(120) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `cta_url` varchar(500) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_by` int unsigned NOT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `created_by` (`created_by`),
  KEY `idx_video_ad_enabled` (`video_id`,`enabled`),
  CONSTRAINT `video_ads_ibfk_1` FOREIGN KEY (`video_id`) REFERENCES `hunt_videos` (`id`) ON DELETE CASCADE,
  CONSTRAINT `video_ads_ibfk_2` FOREIGN KEY (`created_by`) REFERENCES `admins` (`id`),
  CONSTRAINT `video_ads_chk_1` CHECK (((`display_type` <> _utf8mb4'MID_ROLL') or (`trigger_seconds` is not null)))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `video_ads`
--

LOCK TABLES `video_ads` WRITE;
/*!40000 ALTER TABLE `video_ads` DISABLE KEYS */;
/*!40000 ALTER TABLE `video_ads` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `video_categories`
--

DROP TABLE IF EXISTS `video_categories`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `video_categories` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(190) COLLATE utf8mb4_unicode_ci NOT NULL,
  `slug` varchar(190) COLLATE utf8mb4_unicode_ci NOT NULL,
  `commission_type` enum('PERCENTAGE','FIXED') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'PERCENTAGE',
  `platform_commission_rate` decimal(5,2) NOT NULL DEFAULT '0.00',
  `customer_reward_rate` decimal(5,2) NOT NULL DEFAULT '0.00',
  `status` enum('ACTIVE','INACTIVE') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'ACTIVE',
  `created_by` int unsigned NOT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `character_name` varchar(190) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `character_asset_url` varchar(500) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `channel_url` varchar(500) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `teaser_video_id` varchar(80) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `broadcast_message` varchar(500) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `overlay_asset_url` varchar(500) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `capacity_limit` int unsigned NOT NULL DEFAULT '500',
  PRIMARY KEY (`id`),
  UNIQUE KEY `slug` (`slug`),
  KEY `created_by` (`created_by`),
  KEY `idx_video_category_status` (`status`),
  CONSTRAINT `video_categories_ibfk_1` FOREIGN KEY (`created_by`) REFERENCES `admins` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `video_categories`
--

LOCK TABLES `video_categories` WRITE;
/*!40000 ALTER TABLE `video_categories` DISABLE KEYS */;
INSERT INTO `video_categories` VALUES (1,'Shopping Mall & Retail Hunt','shopping-mall','PERCENTAGE',0.00,0.00,'ACTIVE',1,'2026-08-29 11:29:07','2026-08-29 11:29:07','Shopping Robot Mascot',NULL,'https://youtube.com/@HunterWalletRetail','demo_teaser_v1','Demo notice: hidden reward spots are live!',NULL,1000),(2,'Patna Zoo & Wildlife Safari','patna-zoo','PERCENTAGE',0.00,0.00,'ACTIVE',1,'2026-08-29 11:29:07','2026-08-29 11:29:07','Zoo Explorer Mascot',NULL,'https://youtube.com/@HunterWalletRetail','demo_teaser_v1','Demo notice: hidden reward spots are live!',NULL,500),(3,'Mobile Recharge & Utility Deals','recharge','PERCENTAGE',0.00,0.00,'ACTIVE',1,'2026-08-29 11:29:07','2026-08-29 11:29:07','Recharge Lightning Mascot',NULL,'https://youtube.com/@HunterWalletRetail','demo_teaser_v1','Demo notice: hidden reward spots are live!',NULL,2000);
/*!40000 ALTER TABLE `video_categories` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `wallet_bonus_rules`
--

DROP TABLE IF EXISTS `wallet_bonus_rules`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `wallet_bonus_rules` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `signup_bonus` decimal(18,2) NOT NULL DEFAULT '0.00',
  `referral_bonus` decimal(18,2) NOT NULL DEFAULT '0.00',
  `bonus_burn_percent` decimal(5,2) NOT NULL DEFAULT '10.00',
  `updated_by` int unsigned DEFAULT NULL,
  `updated_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `updated_by` (`updated_by`),
  CONSTRAINT `wallet_bonus_rules_ibfk_1` FOREIGN KEY (`updated_by`) REFERENCES `admins` (`id`),
  CONSTRAINT `wallet_bonus_rules_chk_1` CHECK ((`signup_bonus` >= 0)),
  CONSTRAINT `wallet_bonus_rules_chk_2` CHECK ((`referral_bonus` >= 0)),
  CONSTRAINT `wallet_bonus_rules_chk_3` CHECK ((`bonus_burn_percent` between 0 and 100))
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `wallet_bonus_rules`
--

LOCK TABLES `wallet_bonus_rules` WRITE;
/*!40000 ALTER TABLE `wallet_bonus_rules` DISABLE KEYS */;
INSERT INTO `wallet_bonus_rules` VALUES (1,50.00,25.00,10.00,NULL,'2026-08-23 19:08:10');
/*!40000 ALTER TABLE `wallet_bonus_rules` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `wallet_checkout_transactions`
--

DROP TABLE IF EXISTS `wallet_checkout_transactions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `wallet_checkout_transactions` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `checkout_id` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `user_id` bigint unsigned NOT NULL,
  `merchant_id` bigint unsigned DEFAULT NULL,
  `bill_amount` decimal(18,2) NOT NULL,
  `promo_used` decimal(18,2) NOT NULL DEFAULT '0.00',
  `bonus_used` decimal(18,2) NOT NULL DEFAULT '0.00',
  `real_used` decimal(18,2) NOT NULL DEFAULT '0.00',
  `status` enum('PENDING','SUCCESS','FAILED','REVERSED') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'PENDING',
  `idempotency_key` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `checkout_id` (`checkout_id`),
  UNIQUE KEY `uq_checkout_idempotency` (`user_id`,`idempotency_key`),
  KEY `merchant_id` (`merchant_id`),
  CONSTRAINT `wallet_checkout_transactions_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`),
  CONSTRAINT `wallet_checkout_transactions_ibfk_2` FOREIGN KEY (`merchant_id`) REFERENCES `vendors` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=11 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `wallet_checkout_transactions`
--

LOCK TABLES `wallet_checkout_transactions` WRITE;
/*!40000 ALTER TABLE `wallet_checkout_transactions` DISABLE KEYS */;
INSERT INTO `wallet_checkout_transactions` VALUES (1,'CHK-20260823-EBFDB531',3,NULL,100.00,15.00,10.00,75.00,'SUCCESS','checkout-integration-key-01','2026-08-23 19:08:23'),(2,'CHK-20261017-64FFEBEF',6,3,100.00,15.00,10.00,75.00,'SUCCESS','checkout-key-b59f52db','2026-10-17 18:36:24'),(3,'CHK-20261017-3ABC9757',14,NULL,100.00,15.00,10.00,75.00,'SUCCESS','checkout-key-93bd279a','2026-10-17 19:57:16'),(4,'CHK-20261017-E6443186',17,NULL,100.00,15.00,10.00,75.00,'SUCCESS','checkout-key-84aaf380','2026-10-17 21:21:24'),(5,'CHK-20261017-3B503ED1',24,NULL,100.00,15.00,10.00,75.00,'SUCCESS','checkout-key-eefa6591','2026-10-17 21:38:32'),(6,'CHK-20261017-87D55E18',28,NULL,100.00,15.00,10.00,75.00,'SUCCESS','checkout-key-c4b8a794','2026-10-17 22:51:23'),(7,'CHK-20261018-9DD2DC45',32,NULL,100.00,15.00,10.00,75.00,'SUCCESS','checkout-key-8d4dc97f','2026-10-18 09:25:17'),(8,'CHK-7e9b9bd2',1,3,1499.00,100.00,150.00,1249.00,'SUCCESS','idemp-9790406272dbc60c','2026-10-18 09:50:53'),(9,'CHK-f1c609a3',4,3,2299.00,200.00,200.00,1899.00,'SUCCESS','idemp-4924ec3c0bfff887','2026-10-18 09:50:53'),(10,'CHK-6b5864b2',2,3,350.00,50.00,30.00,270.00,'SUCCESS','idemp-f9d90d22365d0061','2026-10-18 09:50:53');
/*!40000 ALTER TABLE `wallet_checkout_transactions` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `wallet_expiry_jobs`
--

DROP TABLE IF EXISTS `wallet_expiry_jobs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `wallet_expiry_jobs` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `bucket` enum('BONUS','PROMO') COLLATE utf8mb4_unicode_ci NOT NULL,
  `wallet_id` bigint unsigned NOT NULL,
  `amount` decimal(18,2) NOT NULL,
  `reference` varchar(120) COLLATE utf8mb4_unicode_ci NOT NULL,
  `expires_at` datetime NOT NULL,
  `status` enum('PENDING','PROCESSED','CANCELLED') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'PENDING',
  `processed_at` datetime DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `reference` (`reference`),
  KEY `idx_expiry_job_due` (`status`,`expires_at`),
  KEY `wallet_id` (`wallet_id`),
  CONSTRAINT `wallet_expiry_jobs_ibfk_1` FOREIGN KEY (`wallet_id`) REFERENCES `wallets` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=15 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `wallet_expiry_jobs`
--

LOCK TABLES `wallet_expiry_jobs` WRITE;
/*!40000 ALTER TABLE `wallet_expiry_jobs` DISABLE KEYS */;
INSERT INTO `wallet_expiry_jobs` VALUES (1,'BONUS',3,50.00,'test-bonus','2026-08-23 19:07:23','PROCESSED','2026-08-23 19:08:23','2026-08-23 19:08:23'),(2,'PROMO',3,15.00,'test-promo','2026-08-23 20:08:23','PROCESSED','2026-10-17 18:36:24','2026-08-23 19:08:23'),(3,'BONUS',10,50.00,'test-bonus-b59f52db','2026-10-17 18:35:24','PROCESSED','2026-10-17 18:36:24','2026-10-17 18:36:24'),(4,'PROMO',10,15.00,'test-promo-b59f52db','2026-10-17 19:36:24','PROCESSED','2026-10-17 19:57:16','2026-10-17 18:36:24'),(5,'BONUS',18,50.00,'test-bonus-93bd279a','2026-10-17 19:56:16','PROCESSED','2026-10-17 19:57:16','2026-10-17 19:57:16'),(6,'PROMO',18,15.00,'test-promo-93bd279a','2026-10-17 20:57:16','PROCESSED','2026-10-17 21:21:24','2026-10-17 19:57:16'),(7,'BONUS',21,50.00,'test-bonus-84aaf380','2026-10-17 21:20:24','PROCESSED','2026-10-17 21:21:24','2026-10-17 21:21:24'),(8,'PROMO',21,15.00,'test-promo-84aaf380','2026-10-17 22:21:24','PROCESSED','2026-10-17 22:51:23','2026-10-17 21:21:24'),(9,'BONUS',28,50.00,'test-bonus-eefa6591','2026-10-17 21:37:32','PROCESSED','2026-10-17 21:38:32','2026-10-17 21:38:32'),(10,'PROMO',28,15.00,'test-promo-eefa6591','2026-10-17 22:38:32','PROCESSED','2026-10-17 22:51:23','2026-10-17 21:38:32'),(11,'BONUS',32,50.00,'test-bonus-c4b8a794','2026-10-17 22:50:23','PROCESSED','2026-10-17 22:51:23','2026-10-17 22:51:23'),(12,'PROMO',32,15.00,'test-promo-c4b8a794','2026-10-17 23:51:23','PROCESSED','2026-10-18 09:25:17','2026-10-17 22:51:23'),(13,'BONUS',36,50.00,'test-bonus-8d4dc97f','2026-10-18 09:24:17','PROCESSED','2026-10-18 09:25:17','2026-10-18 09:25:16'),(14,'PROMO',36,15.00,'test-promo-8d4dc97f','2026-10-18 10:25:16','PENDING',NULL,'2026-10-18 09:25:16');
/*!40000 ALTER TABLE `wallet_expiry_jobs` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `wallet_transactions`
--

DROP TABLE IF EXISTS `wallet_transactions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `wallet_transactions` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `transaction_id` varchar(40) COLLATE utf8mb4_unicode_ci NOT NULL,
  `user_id` bigint unsigned NOT NULL,
  `wallet_id` bigint unsigned NOT NULL,
  `type` enum('CREDIT','DEBIT','WITHDRAWAL','REFUND','ADJUSTMENT','REVERSAL','BONUS_CREDIT','PROMO_CREDIT','PROMO_DEBIT','BONUS_DEBIT','CHECKOUT','PROMO_REVERSAL') COLLATE utf8mb4_unicode_ci NOT NULL,
  `bucket` enum('REAL','BONUS','PROMO') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'REAL',
  `amount` decimal(18,2) NOT NULL,
  `previous_balance` decimal(18,2) NOT NULL,
  `new_balance` decimal(18,2) NOT NULL,
  `status` enum('PENDING','PROCESSING','SUCCESS','FAILED','REVERSED') COLLATE utf8mb4_unicode_ci NOT NULL,
  `reference` varchar(120) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `description` varchar(500) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `idempotency_key` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `original_transaction_id` bigint unsigned DEFAULT NULL,
  `created_by` int unsigned DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `transaction_id` (`transaction_id`),
  UNIQUE KEY `uq_wallet_idempotency` (`idempotency_key`),
  UNIQUE KEY `uq_reversal_original` (`original_transaction_id`),
  KEY `idx_txn_user_date` (`user_id`,`created_at`),
  KEY `idx_txn_status` (`status`),
  KEY `wallet_id` (`wallet_id`),
  KEY `created_by` (`created_by`),
  CONSTRAINT `wallet_transactions_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`),
  CONSTRAINT `wallet_transactions_ibfk_2` FOREIGN KEY (`wallet_id`) REFERENCES `wallets` (`id`),
  CONSTRAINT `wallet_transactions_ibfk_3` FOREIGN KEY (`original_transaction_id`) REFERENCES `wallet_transactions` (`id`),
  CONSTRAINT `wallet_transactions_ibfk_4` FOREIGN KEY (`created_by`) REFERENCES `admins` (`id`),
  CONSTRAINT `wallet_transactions_chk_1` CHECK ((`amount` > 0))
) ENGINE=InnoDB AUTO_INCREMENT=82 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `wallet_transactions`
--

LOCK TABLES `wallet_transactions` WRITE;
/*!40000 ALTER TABLE `wallet_transactions` DISABLE KEYS */;
INSERT INTO `wallet_transactions` VALUES (1,'TXN-20260823-2F5BDF11',1,1,'CREDIT','REAL',500.00,0.00,500.00,'SUCCESS','RWD-20260823-B77B5D1F','Approved Hunt reward','reward-credit:1',NULL,1,'2026-08-23 19:08:23'),(2,'TXN-20260823-E6E17A3E',1,1,'WITHDRAWAL','REAL',150.00,500.00,350.00,'SUCCESS','WDL-20260823-480AADBC','Withdrawal approved',NULL,NULL,1,'2026-08-23 19:08:23'),(3,'TXN-20260823-1DF87A39',3,3,'BONUS_CREDIT','BONUS',50.00,0.00,50.00,'SUCCESS','test-bonus','Signup bonus','bonus-integration-key-01',NULL,1,'2026-08-23 19:08:23'),(4,'TXN-20260823-3FC6DBC3',3,3,'PROMO_CREDIT','PROMO',15.00,0.00,15.00,'SUCCESS','test-promo','QR promo','promo-integration-key-01',NULL,1,'2026-08-23 19:08:23'),(5,'TXN-20260823-EB18996B',3,3,'PROMO_DEBIT','PROMO',15.00,15.00,0.00,'SUCCESS','CHK-20260823-EBFDB531','Promo cash checkout','checkout-integration-key-01:promo',NULL,NULL,'2026-08-23 19:08:23'),(6,'TXN-20260823-AE9289A9',3,3,'BONUS_DEBIT','BONUS',10.00,50.00,40.00,'SUCCESS','CHK-20260823-EBFDB531','Bonus balance checkout','checkout-integration-key-01:bonus',NULL,NULL,'2026-08-23 19:08:23'),(7,'TXN-20260823-2A2FE0A1',3,3,'ADJUSTMENT','REAL',75.00,100.00,25.00,'SUCCESS','CHK-20260823-EBFDB531','Real cash checkout','checkout-integration-key-01:real',NULL,NULL,'2026-08-23 19:08:23'),(8,'TXN-20260823-9DB4A201',3,3,'BONUS_DEBIT','BONUS',40.00,40.00,0.00,'SUCCESS','test-bonus','Expired BONUS balance','expiry-wallet:1',NULL,NULL,'2026-08-23 19:08:23'),(9,'TXN-20260829-28D93928',4,6,'PROMO_CREDIT','PROMO',100.00,15.00,115.00,'SUCCESS','admin-grant:d76969cf7e034f4b','Administrator PROMO grant','wallet-bonus:37ea6bb98eeb88838ada4712cb5bec0aa624e3fccc44293f',NULL,2,'2026-08-29 11:53:08'),(10,'TXN-20261017-F62672BC',6,10,'BONUS_CREDIT','BONUS',50.00,0.00,50.00,'SUCCESS','test-bonus-b59f52db','Signup bonus','bonus-key-b59f52db',NULL,1,'2026-10-17 18:36:24'),(11,'TXN-20261017-F868BCF4',6,10,'PROMO_CREDIT','PROMO',15.00,0.00,15.00,'SUCCESS','test-promo-b59f52db','QR promo','promo-key-b59f52db',NULL,1,'2026-10-17 18:36:24'),(12,'TXN-20261017-A17EF19F',6,10,'PROMO_DEBIT','PROMO',15.00,15.00,0.00,'SUCCESS','CHK-20261017-64FFEBEF','Promo cash checkout','checkout-key-b59f52db:promo',NULL,NULL,'2026-10-17 18:36:24'),(13,'TXN-20261017-1BEF6CF1',6,10,'BONUS_DEBIT','BONUS',10.00,50.00,40.00,'SUCCESS','CHK-20261017-64FFEBEF','Bonus balance checkout','checkout-key-b59f52db:bonus',NULL,NULL,'2026-10-17 18:36:24'),(14,'TXN-20261017-31106199',6,10,'ADJUSTMENT','REAL',75.00,100.00,25.00,'SUCCESS','CHK-20261017-64FFEBEF','Real cash checkout','checkout-key-b59f52db:real',NULL,NULL,'2026-10-17 18:36:24'),(15,'TXN-20261017-6E941F7A',6,10,'BONUS_DEBIT','BONUS',40.00,40.00,0.00,'SUCCESS','test-bonus-b59f52db','Expired BONUS balance','expiry-wallet:3',NULL,NULL,'2026-10-17 18:36:24'),(16,'TXN-20261017-31C7DB0B',7,11,'CREDIT','REAL',500.00,0.00,500.00,'SUCCESS','RWD-20261017-4702DB16','Approved Hunt reward','reward-credit:2',NULL,4,'2026-10-17 18:37:52'),(17,'TXN-20261017-ED440090',7,11,'WITHDRAWAL','REAL',150.00,500.00,350.00,'SUCCESS','WDL-20261017-F2A348B4','Withdrawal approved',NULL,NULL,4,'2026-10-17 18:37:52'),(18,'TXN-20261017-F9317167',9,13,'CREDIT','REAL',500.00,0.00,500.00,'SUCCESS','RWD-20261017-6E570504','Approved Hunt reward','reward-credit:3',NULL,5,'2026-10-17 18:38:35'),(19,'TXN-20261017-7F7FC787',9,13,'WITHDRAWAL','REAL',150.00,500.00,350.00,'SUCCESS','WDL-20261017-E12F6F02','Withdrawal approved',NULL,NULL,5,'2026-10-17 18:38:35'),(20,'TXN-20261017-BA281808',11,15,'CREDIT','REAL',500.00,0.00,500.00,'SUCCESS','RWD-20261017-67F876EC','Approved Hunt reward','reward-credit:4',NULL,6,'2026-10-17 18:38:57'),(21,'TXN-20261017-17A09476',11,15,'WITHDRAWAL','REAL',150.00,500.00,350.00,'SUCCESS','WDL-20261017-77212D11','Withdrawal approved',NULL,NULL,6,'2026-10-17 18:38:57'),(22,'TXN-20261017-FAB881E0',14,18,'BONUS_CREDIT','BONUS',50.00,0.00,50.00,'SUCCESS','test-bonus-93bd279a','Signup bonus','bonus-key-93bd279a',NULL,1,'2026-10-17 19:57:16'),(23,'TXN-20261017-91BAA4CC',14,18,'PROMO_CREDIT','PROMO',15.00,0.00,15.00,'SUCCESS','test-promo-93bd279a','QR promo','promo-key-93bd279a',NULL,1,'2026-10-17 19:57:16'),(24,'TXN-20261017-2C068BCB',14,18,'PROMO_DEBIT','PROMO',15.00,15.00,0.00,'SUCCESS','CHK-20261017-3ABC9757','Promo cash checkout','checkout-key-93bd279a:promo',NULL,NULL,'2026-10-17 19:57:16'),(25,'TXN-20261017-38EF6D16',14,18,'BONUS_DEBIT','BONUS',10.00,50.00,40.00,'SUCCESS','CHK-20261017-3ABC9757','Bonus balance checkout','checkout-key-93bd279a:bonus',NULL,NULL,'2026-10-17 19:57:16'),(26,'TXN-20261017-FE7A332A',14,18,'ADJUSTMENT','REAL',75.00,100.00,25.00,'SUCCESS','CHK-20261017-3ABC9757','Real cash checkout','checkout-key-93bd279a:real',NULL,NULL,'2026-10-17 19:57:16'),(27,'TXN-20261017-D5AE036C',14,18,'BONUS_DEBIT','BONUS',40.00,40.00,0.00,'SUCCESS','test-bonus-93bd279a','Expired BONUS balance','expiry-wallet:5',NULL,NULL,'2026-10-17 19:57:16'),(28,'TXN-20261017-3199843E',15,19,'CREDIT','REAL',500.00,0.00,500.00,'SUCCESS','RWD-20261017-CBFDBB1D','Approved Hunt reward','reward-credit:5',NULL,8,'2026-10-17 19:57:25'),(29,'TXN-20261017-B130BF75',15,19,'WITHDRAWAL','REAL',150.00,500.00,350.00,'SUCCESS','WDL-20261017-321A1E1D','Withdrawal approved',NULL,NULL,8,'2026-10-17 19:57:25'),(30,'TXN-20261017-773BBD40',17,21,'BONUS_CREDIT','BONUS',50.00,0.00,50.00,'SUCCESS','test-bonus-84aaf380','Signup bonus','bonus-key-84aaf380',NULL,1,'2026-10-17 21:21:24'),(31,'TXN-20261017-D77A4180',17,21,'PROMO_CREDIT','PROMO',15.00,0.00,15.00,'SUCCESS','test-promo-84aaf380','QR promo','promo-key-84aaf380',NULL,1,'2026-10-17 21:21:24'),(32,'TXN-20261017-FA994048',17,21,'PROMO_DEBIT','PROMO',15.00,15.00,0.00,'SUCCESS','CHK-20261017-E6443186','Promo cash checkout','checkout-key-84aaf380:promo',NULL,NULL,'2026-10-17 21:21:24'),(33,'TXN-20261017-91BF8390',17,21,'BONUS_DEBIT','BONUS',10.00,50.00,40.00,'SUCCESS','CHK-20261017-E6443186','Bonus balance checkout','checkout-key-84aaf380:bonus',NULL,NULL,'2026-10-17 21:21:24'),(34,'TXN-20261017-23220CC1',17,21,'ADJUSTMENT','REAL',75.00,100.00,25.00,'SUCCESS','CHK-20261017-E6443186','Real cash checkout','checkout-key-84aaf380:real',NULL,NULL,'2026-10-17 21:21:24'),(35,'TXN-20261017-B120E840',17,21,'BONUS_DEBIT','BONUS',40.00,40.00,0.00,'SUCCESS','test-bonus-84aaf380','Expired BONUS balance','expiry-wallet:7',NULL,NULL,'2026-10-17 21:21:24'),(36,'TXN-20261017-A3A615C0',18,22,'CREDIT','REAL',500.00,0.00,500.00,'SUCCESS','RWD-20261017-52C89046','Approved Hunt reward','reward-credit:6',NULL,9,'2026-10-17 21:21:26'),(37,'TXN-20261017-4EDB0FF4',18,22,'WITHDRAWAL','REAL',150.00,500.00,350.00,'SUCCESS','WDL-20261017-369DF997','Withdrawal approved',NULL,NULL,9,'2026-10-17 21:21:26'),(38,'TXN-20261017-E6F9D1D4',22,26,'BONUS_CREDIT','BONUS',50.00,20.00,70.00,'SUCCESS','RDL-3','Reward for solving riddle #3','attempt-correct-4120c86a:reward',NULL,NULL,'2026-10-17 21:37:27'),(39,'TXN-20261017-8C1FBB19',22,26,'BONUS_DEBIT','BONUS',5.00,70.00,65.00,'SUCCESS','PENALTY-RDL-3','Negative marking penalty for wrong answer on riddle #3','attempt-wrong1-4120c86a:penalty',NULL,NULL,'2026-10-17 21:37:27'),(40,'TXN-20261017-5A8D59EF',22,26,'BONUS_DEBIT','BONUS',5.00,65.00,60.00,'SUCCESS','PENALTY-RDL-4','Negative marking penalty for wrong answer on riddle #4','attempt-wrong2-4120c86a:penalty',NULL,NULL,'2026-10-17 21:37:27'),(41,'TXN-20261017-D024BCDF',22,26,'BONUS_DEBIT','BONUS',2.00,2.00,0.00,'SUCCESS','PENALTY-RDL-4','Negative marking penalty for wrong answer on riddle #4','attempt-wrong-cap-4120c86a:penalty',NULL,NULL,'2026-10-17 21:37:27'),(42,'TXN-20261017-BECE13B5',22,26,'BONUS_CREDIT','BONUS',200.00,50.00,250.00,'SUCCESS','HB-BC-7F536C34','Broadcast hunt reward: Red dress wali ladki ke paas QR code hai, jao a...','claim-valid-4120c86a:credit',NULL,NULL,'2026-10-17 21:37:28'),(43,'TXN-20261017-FB1322C5',23,27,'BONUS_CREDIT','BONUS',50.00,20.00,70.00,'SUCCESS','RDL-5','Reward for solving riddle #5','attempt-correct-32a00416:reward',NULL,NULL,'2026-10-17 21:38:31'),(44,'TXN-20261017-23A9C839',23,27,'BONUS_DEBIT','BONUS',5.00,70.00,65.00,'SUCCESS','PENALTY-RDL-5','Negative marking penalty for wrong answer on riddle #5','attempt-wrong1-32a00416:penalty',NULL,NULL,'2026-10-17 21:38:31'),(45,'TXN-20261017-33F14E5D',23,27,'BONUS_DEBIT','BONUS',5.00,65.00,60.00,'SUCCESS','PENALTY-RDL-6','Negative marking penalty for wrong answer on riddle #6','attempt-wrong2-32a00416:penalty',NULL,NULL,'2026-10-17 21:38:31'),(46,'TXN-20261017-800E769B',23,27,'BONUS_DEBIT','BONUS',2.00,2.00,0.00,'SUCCESS','PENALTY-RDL-6','Negative marking penalty for wrong answer on riddle #6','attempt-wrong-cap-32a00416:penalty',NULL,NULL,'2026-10-17 21:38:31'),(47,'TXN-20261017-4B10523C',23,27,'BONUS_CREDIT','BONUS',200.00,50.00,250.00,'SUCCESS','HB-BC-18E583C6','Broadcast hunt reward: Red dress wali ladki ke paas QR code hai, jao a...','claim-valid-32a00416:credit',NULL,NULL,'2026-10-17 21:38:32'),(48,'TXN-20261017-0C4A423C',24,28,'BONUS_CREDIT','BONUS',50.00,0.00,50.00,'SUCCESS','test-bonus-eefa6591','Signup bonus','bonus-key-eefa6591',NULL,1,'2026-10-17 21:38:32'),(49,'TXN-20261017-44ED7031',24,28,'PROMO_CREDIT','PROMO',15.00,0.00,15.00,'SUCCESS','test-promo-eefa6591','QR promo','promo-key-eefa6591',NULL,1,'2026-10-17 21:38:32'),(50,'TXN-20261017-E706E141',24,28,'PROMO_DEBIT','PROMO',15.00,15.00,0.00,'SUCCESS','CHK-20261017-3B503ED1','Promo cash checkout','checkout-key-eefa6591:promo',NULL,NULL,'2026-10-17 21:38:32'),(51,'TXN-20261017-9C0F1DE0',24,28,'BONUS_DEBIT','BONUS',10.00,50.00,40.00,'SUCCESS','CHK-20261017-3B503ED1','Bonus balance checkout','checkout-key-eefa6591:bonus',NULL,NULL,'2026-10-17 21:38:32'),(52,'TXN-20261017-5C876CF0',24,28,'ADJUSTMENT','REAL',75.00,100.00,25.00,'SUCCESS','CHK-20261017-3B503ED1','Real cash checkout','checkout-key-eefa6591:real',NULL,NULL,'2026-10-17 21:38:32'),(53,'TXN-20261017-C89F7A5B',24,28,'BONUS_DEBIT','BONUS',40.00,40.00,0.00,'SUCCESS','test-bonus-eefa6591','Expired BONUS balance','expiry-wallet:9',NULL,NULL,'2026-10-17 21:38:32'),(54,'TXN-20261017-B496FCDB',25,29,'CREDIT','REAL',500.00,0.00,500.00,'SUCCESS','RWD-20261017-B5094C11','Approved Hunt reward','reward-credit:7',NULL,14,'2026-10-17 21:38:34'),(55,'TXN-20261017-D395C43B',25,29,'WITHDRAWAL','REAL',150.00,500.00,350.00,'SUCCESS','WDL-20261017-5345617E','Withdrawal approved',NULL,NULL,14,'2026-10-17 21:38:34'),(56,'TXN-20261017-1D6E3B99',27,31,'BONUS_CREDIT','BONUS',50.00,20.00,70.00,'SUCCESS','RDL-7','Reward for solving riddle #7','attempt-correct-4f67df73:reward',NULL,NULL,'2026-10-17 22:51:21'),(57,'TXN-20261017-91D8A83F',27,31,'BONUS_DEBIT','BONUS',5.00,70.00,65.00,'SUCCESS','PENALTY-RDL-7','Negative marking penalty for wrong answer on riddle #7','attempt-wrong1-4f67df73:penalty',NULL,NULL,'2026-10-17 22:51:21'),(58,'TXN-20261017-D572E843',27,31,'BONUS_DEBIT','BONUS',5.00,65.00,60.00,'SUCCESS','PENALTY-RDL-8','Negative marking penalty for wrong answer on riddle #8','attempt-wrong2-4f67df73:penalty',NULL,NULL,'2026-10-17 22:51:21'),(59,'TXN-20261017-063DCF5A',27,31,'BONUS_DEBIT','BONUS',2.00,2.00,0.00,'SUCCESS','PENALTY-RDL-8','Negative marking penalty for wrong answer on riddle #8','attempt-wrong-cap-4f67df73:penalty',NULL,NULL,'2026-10-17 22:51:21'),(60,'TXN-20261017-A5FE85F5',27,31,'BONUS_CREDIT','BONUS',200.00,50.00,250.00,'SUCCESS','HB-BC-1BF16619','Broadcast hunt reward: Red dress wali ladki ke paas QR code hai, jao a...','claim-valid-4f67df73:credit',NULL,NULL,'2026-10-17 22:51:23'),(61,'TXN-20261017-86F76CA9',28,32,'BONUS_CREDIT','BONUS',50.00,0.00,50.00,'SUCCESS','test-bonus-c4b8a794','Signup bonus','bonus-key-c4b8a794',NULL,1,'2026-10-17 22:51:23'),(62,'TXN-20261017-8A09FDD6',28,32,'PROMO_CREDIT','PROMO',15.00,0.00,15.00,'SUCCESS','test-promo-c4b8a794','QR promo','promo-key-c4b8a794',NULL,1,'2026-10-17 22:51:23'),(63,'TXN-20261017-4FCD5A0E',28,32,'PROMO_DEBIT','PROMO',15.00,15.00,0.00,'SUCCESS','CHK-20261017-87D55E18','Promo cash checkout','checkout-key-c4b8a794:promo',NULL,NULL,'2026-10-17 22:51:23'),(64,'TXN-20261017-1D443967',28,32,'BONUS_DEBIT','BONUS',10.00,50.00,40.00,'SUCCESS','CHK-20261017-87D55E18','Bonus balance checkout','checkout-key-c4b8a794:bonus',NULL,NULL,'2026-10-17 22:51:23'),(65,'TXN-20261017-ECB55418',28,32,'ADJUSTMENT','REAL',75.00,100.00,25.00,'SUCCESS','CHK-20261017-87D55E18','Real cash checkout','checkout-key-c4b8a794:real',NULL,NULL,'2026-10-17 22:51:23'),(66,'TXN-20261017-42F9745C',28,32,'BONUS_DEBIT','BONUS',40.00,40.00,0.00,'SUCCESS','test-bonus-c4b8a794','Expired BONUS balance','expiry-wallet:11',NULL,NULL,'2026-10-17 22:51:23'),(67,'TXN-20261017-CDAFC903',29,33,'CREDIT','REAL',500.00,0.00,500.00,'SUCCESS','RWD-20261017-2FD64895','Approved Hunt reward','reward-credit:8',NULL,16,'2026-10-17 22:51:25'),(68,'TXN-20261017-BB60EC00',29,33,'WITHDRAWAL','REAL',150.00,500.00,350.00,'SUCCESS','WDL-20261017-81EBCEA0','Withdrawal approved',NULL,NULL,16,'2026-10-17 22:51:25'),(69,'TXN-20261018-8FB5F447',31,35,'BONUS_CREDIT','BONUS',50.00,20.00,70.00,'SUCCESS','RDL-9','Reward for solving riddle #9','attempt-correct-2a0bf7e2:reward',NULL,NULL,'2026-10-18 09:25:15'),(70,'TXN-20261018-DEE763D5',31,35,'BONUS_DEBIT','BONUS',5.00,70.00,65.00,'SUCCESS','PENALTY-RDL-9','Negative marking penalty for wrong answer on riddle #9','attempt-wrong1-2a0bf7e2:penalty',NULL,NULL,'2026-10-18 09:25:15'),(71,'TXN-20261018-133F4B0F',31,35,'BONUS_DEBIT','BONUS',5.00,65.00,60.00,'SUCCESS','PENALTY-RDL-10','Negative marking penalty for wrong answer on riddle #10','attempt-wrong2-2a0bf7e2:penalty',NULL,NULL,'2026-10-18 09:25:15'),(72,'TXN-20261018-B4DB91C9',31,35,'BONUS_DEBIT','BONUS',2.00,2.00,0.00,'SUCCESS','PENALTY-RDL-10','Negative marking penalty for wrong answer on riddle #10','attempt-wrong-cap-2a0bf7e2:penalty',NULL,NULL,'2026-10-18 09:25:15'),(73,'TXN-20261018-82BDB0E0',31,35,'BONUS_CREDIT','BONUS',200.00,50.00,250.00,'SUCCESS','HB-BC-CA3EC623','Broadcast hunt reward: Red dress wali ladki ke paas QR code hai, jao a...','claim-valid-2a0bf7e2:credit',NULL,NULL,'2026-10-18 09:25:16'),(74,'TXN-20261018-5483EA3B',32,36,'BONUS_CREDIT','BONUS',50.00,0.00,50.00,'SUCCESS','test-bonus-8d4dc97f','Signup bonus','bonus-key-8d4dc97f',NULL,1,'2026-10-18 09:25:16'),(75,'TXN-20261018-141B60AC',32,36,'PROMO_CREDIT','PROMO',15.00,0.00,15.00,'SUCCESS','test-promo-8d4dc97f','QR promo','promo-key-8d4dc97f',NULL,1,'2026-10-18 09:25:16'),(76,'TXN-20261018-31FD1D5F',32,36,'PROMO_DEBIT','PROMO',15.00,15.00,0.00,'SUCCESS','CHK-20261018-9DD2DC45','Promo cash checkout','checkout-key-8d4dc97f:promo',NULL,NULL,'2026-10-18 09:25:17'),(77,'TXN-20261018-7C65934F',32,36,'BONUS_DEBIT','BONUS',10.00,50.00,40.00,'SUCCESS','CHK-20261018-9DD2DC45','Bonus balance checkout','checkout-key-8d4dc97f:bonus',NULL,NULL,'2026-10-18 09:25:17'),(78,'TXN-20261018-807078A2',32,36,'ADJUSTMENT','REAL',75.00,100.00,25.00,'SUCCESS','CHK-20261018-9DD2DC45','Real cash checkout','checkout-key-8d4dc97f:real',NULL,NULL,'2026-10-18 09:25:17'),(79,'TXN-20261018-0608CA73',32,36,'BONUS_DEBIT','BONUS',40.00,40.00,0.00,'SUCCESS','test-bonus-8d4dc97f','Expired BONUS balance','expiry-wallet:13',NULL,NULL,'2026-10-18 09:25:17'),(80,'TXN-20261018-D28353E8',33,37,'CREDIT','REAL',500.00,0.00,500.00,'SUCCESS','RWD-20261018-E32C8E21','Approved Hunt reward','reward-credit:9',NULL,18,'2026-10-18 09:25:18'),(81,'TXN-20261018-78DAC312',33,37,'WITHDRAWAL','REAL',150.00,500.00,350.00,'SUCCESS','WDL-20261018-430336C2','Withdrawal approved',NULL,NULL,18,'2026-10-18 09:25:18');
/*!40000 ALTER TABLE `wallet_transactions` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `wallets`
--

DROP TABLE IF EXISTS `wallets`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `wallets` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint unsigned NOT NULL,
  `balance` decimal(18,2) NOT NULL DEFAULT '0.00',
  `bonus_balance` decimal(18,2) NOT NULL DEFAULT '0.00',
  `promo_balance` decimal(18,2) NOT NULL DEFAULT '0.00',
  `bonus_expires_at` datetime DEFAULT NULL,
  `promo_expires_at` datetime DEFAULT NULL,
  `currency` char(3) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'INR',
  `status` enum('ACTIVE','FROZEN','CLOSED') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'ACTIVE',
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `user_id` (`user_id`),
  CONSTRAINT `wallets_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`),
  CONSTRAINT `wallets_chk_1` CHECK ((`balance` >= 0)),
  CONSTRAINT `wallets_chk_2` CHECK ((`bonus_balance` >= 0)),
  CONSTRAINT `wallets_chk_3` CHECK ((`promo_balance` >= 0))
) ENGINE=InnoDB AUTO_INCREMENT=39 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `wallets`
--

LOCK TABLES `wallets` WRITE;
/*!40000 ALTER TABLE `wallets` DISABLE KEYS */;
INSERT INTO `wallets` VALUES (1,1,350.00,0.00,0.00,NULL,NULL,'INR','ACTIVE','2026-08-23 19:08:21','2026-08-23 19:08:23'),(2,2,0.00,0.00,0.00,NULL,NULL,'INR','ACTIVE','2026-08-23 19:08:22','2026-08-23 19:08:22'),(3,3,25.00,0.00,0.00,NULL,NULL,'INR','ACTIVE','2026-08-23 19:08:23','2026-10-17 18:36:24'),(6,4,500.00,25.00,115.00,NULL,NULL,'INR','ACTIVE','2026-08-29 11:29:07','2026-10-17 18:58:25'),(10,6,25.00,0.00,0.00,NULL,NULL,'INR','ACTIVE','2026-10-17 18:36:24','2026-10-17 19:57:16'),(11,7,350.00,0.00,0.00,NULL,NULL,'INR','ACTIVE','2026-10-17 18:37:51','2026-10-17 18:37:52'),(12,8,0.00,0.00,0.00,NULL,NULL,'INR','ACTIVE','2026-10-17 18:37:51','2026-10-17 18:37:51'),(13,9,350.00,0.00,0.00,NULL,NULL,'INR','ACTIVE','2026-10-17 18:38:34','2026-10-17 18:38:35'),(14,10,0.00,0.00,0.00,NULL,NULL,'INR','ACTIVE','2026-10-17 18:38:34','2026-10-17 18:38:34'),(15,11,350.00,0.00,0.00,NULL,NULL,'INR','ACTIVE','2026-10-17 18:38:56','2026-10-17 18:38:57'),(16,12,0.00,0.00,0.00,NULL,NULL,'INR','ACTIVE','2026-10-17 18:38:56','2026-10-17 18:38:56'),(18,14,25.00,0.00,0.00,NULL,NULL,'INR','ACTIVE','2026-10-17 19:57:16','2026-10-17 21:21:24'),(19,15,350.00,0.00,0.00,NULL,NULL,'INR','ACTIVE','2026-10-17 19:57:24','2026-10-17 19:57:25'),(20,16,0.00,0.00,0.00,NULL,NULL,'INR','ACTIVE','2026-10-17 19:57:24','2026-10-17 19:57:24'),(21,17,25.00,0.00,0.00,NULL,NULL,'INR','ACTIVE','2026-10-17 21:21:24','2026-10-17 22:51:23'),(22,18,350.00,0.00,0.00,NULL,NULL,'INR','ACTIVE','2026-10-17 21:21:25','2026-10-17 21:21:26'),(23,19,0.00,0.00,0.00,NULL,NULL,'INR','ACTIVE','2026-10-17 21:21:25','2026-10-17 21:21:25'),(24,20,100.00,20.00,0.00,NULL,NULL,'INR','ACTIVE','2026-10-17 21:32:33','2026-10-17 21:32:33'),(25,21,100.00,20.00,0.00,NULL,NULL,'INR','ACTIVE','2026-10-17 21:35:19','2026-10-17 21:35:19'),(26,22,100.00,250.00,0.00,NULL,NULL,'INR','ACTIVE','2026-10-17 21:37:26','2026-10-17 21:37:28'),(27,23,100.00,250.00,0.00,NULL,NULL,'INR','ACTIVE','2026-10-17 21:38:31','2026-10-17 21:38:32'),(28,24,25.00,0.00,0.00,NULL,NULL,'INR','ACTIVE','2026-10-17 21:38:32','2026-10-17 22:51:23'),(29,25,350.00,0.00,0.00,NULL,NULL,'INR','ACTIVE','2026-10-17 21:38:33','2026-10-17 21:38:34'),(30,26,0.00,0.00,0.00,NULL,NULL,'INR','ACTIVE','2026-10-17 21:38:33','2026-10-17 21:38:33'),(31,27,100.00,250.00,0.00,NULL,NULL,'INR','ACTIVE','2026-10-17 22:51:21','2026-10-17 22:51:23'),(32,28,25.00,0.00,0.00,NULL,NULL,'INR','ACTIVE','2026-10-17 22:51:23','2026-10-18 09:25:17'),(33,29,350.00,0.00,0.00,NULL,NULL,'INR','ACTIVE','2026-10-17 22:51:24','2026-10-17 22:51:25'),(34,30,0.00,0.00,0.00,NULL,NULL,'INR','ACTIVE','2026-10-17 22:51:24','2026-10-17 22:51:24'),(35,31,100.00,250.00,0.00,NULL,NULL,'INR','ACTIVE','2026-10-18 09:25:15','2026-10-18 09:25:16'),(36,32,25.00,0.00,0.00,NULL,'2026-10-18 10:25:16','INR','ACTIVE','2026-10-18 09:25:16','2026-10-18 09:25:17'),(37,33,350.00,0.00,0.00,NULL,NULL,'INR','ACTIVE','2026-10-18 09:25:17','2026-10-18 09:25:18'),(38,34,0.00,0.00,0.00,NULL,NULL,'INR','ACTIVE','2026-10-18 09:25:18','2026-10-18 09:25:18');
/*!40000 ALTER TABLE `wallets` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `withdrawal_attempts`
--

DROP TABLE IF EXISTS `withdrawal_attempts`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `withdrawal_attempts` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `withdrawal_id` bigint unsigned DEFAULT NULL,
  `user_id` bigint unsigned NOT NULL,
  `idempotency_key` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `amount` decimal(18,2) DEFAULT NULL,
  `ip_address` varchar(45) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `device_id` varchar(190) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `outcome` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `reason` varchar(500) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_wda_user_date` (`user_id`,`created_at`),
  KEY `withdrawal_id` (`withdrawal_id`),
  CONSTRAINT `withdrawal_attempts_ibfk_1` FOREIGN KEY (`withdrawal_id`) REFERENCES `withdrawals` (`id`),
  CONSTRAINT `withdrawal_attempts_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=20 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `withdrawal_attempts`
--

LOCK TABLES `withdrawal_attempts` WRITE;
/*!40000 ALTER TABLE `withdrawal_attempts` DISABLE KEYS */;
INSERT INTO `withdrawal_attempts` VALUES (1,1,1,NULL,150.00,'127.0.0.1',NULL,'ADMIN_UNDER_REVIEW',NULL,'2026-08-23 19:08:23'),(2,1,1,NULL,150.00,'127.0.0.1',NULL,'ADMIN_APPROVED',NULL,'2026-08-23 19:08:23'),(3,2,7,NULL,150.00,'127.0.0.1',NULL,'ADMIN_UNDER_REVIEW',NULL,'2026-10-17 18:37:52'),(4,2,7,NULL,150.00,'127.0.0.1',NULL,'ADMIN_APPROVED',NULL,'2026-10-17 18:37:52'),(5,3,9,NULL,150.00,'127.0.0.1',NULL,'ADMIN_UNDER_REVIEW',NULL,'2026-10-17 18:38:35'),(6,3,9,NULL,150.00,'127.0.0.1',NULL,'ADMIN_APPROVED',NULL,'2026-10-17 18:38:35'),(7,4,11,NULL,150.00,'127.0.0.1',NULL,'ADMIN_UNDER_REVIEW',NULL,'2026-10-17 18:38:57'),(8,4,11,NULL,150.00,'127.0.0.1',NULL,'ADMIN_APPROVED',NULL,'2026-10-17 18:38:57'),(9,5,4,'integration-api-key-000001',150.00,'127.0.0.1','api-test-device-01','CREATED',NULL,'2026-10-17 18:58:32'),(10,6,15,NULL,150.00,'127.0.0.1',NULL,'ADMIN_UNDER_REVIEW',NULL,'2026-10-17 19:57:25'),(11,6,15,NULL,150.00,'127.0.0.1',NULL,'ADMIN_APPROVED',NULL,'2026-10-17 19:57:25'),(12,7,18,NULL,150.00,'127.0.0.1',NULL,'ADMIN_UNDER_REVIEW',NULL,'2026-10-17 21:21:26'),(13,7,18,NULL,150.00,'127.0.0.1',NULL,'ADMIN_APPROVED',NULL,'2026-10-17 21:21:26'),(14,8,25,NULL,150.00,'127.0.0.1',NULL,'ADMIN_UNDER_REVIEW',NULL,'2026-10-17 21:38:34'),(15,8,25,NULL,150.00,'127.0.0.1',NULL,'ADMIN_APPROVED',NULL,'2026-10-17 21:38:34'),(16,9,29,NULL,150.00,'127.0.0.1',NULL,'ADMIN_UNDER_REVIEW',NULL,'2026-10-17 22:51:25'),(17,9,29,NULL,150.00,'127.0.0.1',NULL,'ADMIN_APPROVED',NULL,'2026-10-17 22:51:25'),(18,10,33,NULL,150.00,'127.0.0.1',NULL,'ADMIN_UNDER_REVIEW',NULL,'2026-10-18 09:25:18'),(19,10,33,NULL,150.00,'127.0.0.1',NULL,'ADMIN_APPROVED',NULL,'2026-10-18 09:25:18');
/*!40000 ALTER TABLE `withdrawal_attempts` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `withdrawals`
--

DROP TABLE IF EXISTS `withdrawals`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `withdrawals` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `withdrawal_id` varchar(40) COLLATE utf8mb4_unicode_ci NOT NULL,
  `user_id` bigint unsigned NOT NULL,
  `wallet_id` bigint unsigned NOT NULL,
  `amount` decimal(18,2) NOT NULL,
  `payment_method` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `payment_account_masked` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `payment_reference` varchar(120) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `status` enum('PENDING','UNDER_REVIEW','APPROVED','PROCESSING','PAID','REJECTED','CANCELLED') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'PENDING',
  `risk_level` enum('LOW','MEDIUM','HIGH','CRITICAL') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'LOW',
  `requested_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `reviewed_at` datetime DEFAULT NULL,
  `processed_at` datetime DEFAULT NULL,
  `reviewed_by` int unsigned DEFAULT NULL,
  `rejection_reason` varchar(500) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `withdrawal_id` (`withdrawal_id`),
  KEY `idx_wd_status_date` (`status`,`requested_at`),
  KEY `user_id` (`user_id`),
  KEY `wallet_id` (`wallet_id`),
  KEY `reviewed_by` (`reviewed_by`),
  CONSTRAINT `withdrawals_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`),
  CONSTRAINT `withdrawals_ibfk_2` FOREIGN KEY (`wallet_id`) REFERENCES `wallets` (`id`),
  CONSTRAINT `withdrawals_ibfk_3` FOREIGN KEY (`reviewed_by`) REFERENCES `admins` (`id`),
  CONSTRAINT `withdrawals_chk_1` CHECK ((`amount` > 0))
) ENGINE=InnoDB AUTO_INCREMENT=11 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `withdrawals`
--

LOCK TABLES `withdrawals` WRITE;
/*!40000 ALTER TABLE `withdrawals` DISABLE KEYS */;
INSERT INTO `withdrawals` VALUES (1,'WDL-20260823-480AADBC',1,1,150.00,'UPI','XXXX XXXX 0001',NULL,'APPROVED','LOW','2026-08-23 19:08:23','2026-08-23 19:08:23',NULL,1,NULL,'2026-08-23 19:08:23','2026-08-23 19:08:23'),(2,'WDL-20261017-F2A348B4',7,11,150.00,'UPI','XXXX XXXX 0001',NULL,'APPROVED','LOW','2026-10-17 18:37:52','2026-10-17 18:37:52',NULL,4,NULL,'2026-10-17 18:37:52','2026-10-17 18:37:52'),(3,'WDL-20261017-E12F6F02',9,13,150.00,'UPI','XXXX XXXX 0001',NULL,'APPROVED','LOW','2026-10-17 18:38:35','2026-10-17 18:38:35',NULL,5,NULL,'2026-10-17 18:38:35','2026-10-17 18:38:35'),(4,'WDL-20261017-77212D11',11,15,150.00,'UPI','XXXX XXXX 0001',NULL,'APPROVED','LOW','2026-10-17 18:38:57','2026-10-17 18:38:57',NULL,6,NULL,'2026-10-17 18:38:57','2026-10-17 18:38:57'),(5,'WD-20261017-FA143BD5',4,6,150.00,'UPI','XXXX XXXX 9012',NULL,'PENDING','LOW','2026-10-17 18:58:32',NULL,NULL,NULL,NULL,'2026-10-17 18:58:32','2026-10-17 18:58:32'),(6,'WDL-20261017-321A1E1D',15,19,150.00,'UPI','XXXX XXXX 0001',NULL,'APPROVED','LOW','2026-10-17 19:57:25','2026-10-17 19:57:25',NULL,8,NULL,'2026-10-17 19:57:25','2026-10-17 19:57:25'),(7,'WDL-20261017-369DF997',18,22,150.00,'UPI','XXXX XXXX 0001',NULL,'APPROVED','LOW','2026-10-17 21:21:26','2026-10-17 21:21:26',NULL,9,NULL,'2026-10-17 21:21:26','2026-10-17 21:21:26'),(8,'WDL-20261017-5345617E',25,29,150.00,'UPI','XXXX XXXX 0001',NULL,'APPROVED','LOW','2026-10-17 21:38:34','2026-10-17 21:38:34',NULL,14,NULL,'2026-10-17 21:38:34','2026-10-17 21:38:34'),(9,'WDL-20261017-81EBCEA0',29,33,150.00,'UPI','XXXX XXXX 0001',NULL,'APPROVED','LOW','2026-10-17 22:51:25','2026-10-17 22:51:25',NULL,16,NULL,'2026-10-17 22:51:25','2026-10-17 22:51:25'),(10,'WDL-20261018-430336C2',33,37,150.00,'UPI','XXXX XXXX 0001',NULL,'APPROVED','LOW','2026-10-18 09:25:18','2026-10-18 09:25:18',NULL,18,NULL,'2026-10-18 09:25:18','2026-10-18 09:25:18');
/*!40000 ALTER TABLE `withdrawals` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Dumping routines for database 'hunter_wallet'
--
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed on 2026-10-18 10:51:46
