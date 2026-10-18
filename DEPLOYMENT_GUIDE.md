# HUNTER WALLET — LIVE PRODUCTION DEPLOYMENT GUIDE

This document provides step-by-step instructions for deploying the **Hunter Wallet** application (Admin Panel, Vendor Portal, API, and Core Ledger Engine) to a live production server (cPanel, DirectAdmin, Ubuntu/Debian VPS, AWS EC2, or DigitalOcean).

---

## 1. Server Prerequisites

- **Web Server**: Apache 2.4+ (with `mod_rewrite` enabled) OR Nginx
- **PHP Version**: PHP 8.1, 8.2, 8.3, or 8.4+
- **PHP Extensions Required**:
  - `pdo_mysql`
  - `curl`
  - `mbstring`
  - `json`
  - `openssl`
  - `fileinfo`
- **Database**: MySQL 8.0+ (or MariaDB 10.5+) with InnoDB engine support
- **SSL / HTTPS**: Recommended for live production (`COOKIE_SECURE=1`)

---

## 2. Database Setup

1. Create a fresh MySQL database on your live server (e.g., `hunter_wallet_prod`).
2. Create a database user with full privileges on that database.
3. Import the included SQL dump file:
   - Via **cPanel phpMyAdmin**: Open phpMyAdmin → Select your database → Click **Import** → Choose `hunter_wallet_production_live.sql` → Click **Go**.
   - Via **SSH Terminal**:
     ```bash
     mysql -u your_db_user -p your_db_name < hunter_wallet_production_live.sql
     ```

---

## 3. Upload & File Extraction

1. Upload `hunter_wallet_production_live.zip` to your web server document root (e.g. `public_html` or `/var/www/html`).
2. Extract the archive contents into the document root.
3. Ensure directory permissions allow write access to `uploads/` and `logs/`:
   ```bash
   chmod -R 755 .
   chmod -R 775 uploads logs
   # or on shared hosting:
   chmod -R 777 uploads logs
   ```

---

## 4. Environment Configuration (`.env`)

1. Copy `.env.example` to `.env`:
   ```bash
   cp .env.example .env
   ```
2. Open `.env` and fill in your live server details:
   ```env
   APP_ENV=production
   APP_URL=https://your-domain.com
   APP_KEY=generate_a_random_64_character_hex_string

   DB_HOST=127.0.0.1
   DB_PORT=3306
   DB_NAME=your_db_name
   DB_USER=your_db_user
   DB_PASS=your_db_password

   SESSION_TIMEOUT=1800
   COOKIE_SECURE=1

   BACKUP_DIR=/tmp/hunter-backups
   BACKUP_RETENTION_DAYS=7

   RAZORPAY_WEBHOOK_SECRET=your_razorpay_webhook_secret
   PHONEPE_WEBHOOK_SECRET=your_phonepe_webhook_secret
   ```

---

## 5. Web Server Configuration

### For Apache (cPanel, LAMP)
The included `.htaccess` file handles URL rewriting, security headers, and asset caching out of the box. Ensure `AllowOverride All` is enabled in your Apache virtual host configuration.

### For Nginx
Add the following location block to your server configuration:
```nginx
server {
    listen 80;
    listen [::]:80;
    server_name your-domain.com;
    return 301 https://$host$request_uri;
}

server {
    listen 443 ssl http2;
    listen [::]:443 ssl http2;
    server_name your-domain.com;

    root /var/www/html;
    index index.php index.html;

    # SSL certificates
    ssl_certificate /path/to/fullchain.pem;
    ssl_certificate_key /path/to/privkey.pem;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location ~ \.php$ {
        include fastcgi_params;
        fastcgi_pass unix:/var/run/php/php8.2-fpm.sock; # Adjust to your PHP version
        fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name;
    }

    location ~ /\. {
        deny all;
    }

    location ^~ /logs/ {
        deny all;
    }
}
```

---

## 6. Default Live Logins

### Super Administrator Portal
- **URL**: `https://your-domain.com/admin/login.php`
- **Username**: `superadmin`
- **Password**: `SuperAdmin@12345`
*(Please change this password immediately in Admin Profile after first login!)*

### Vendor Store Portal
- **URL**: `https://your-domain.com/vendor/login.php`
- **Username**: `vendor.test`
- **Password**: `Demo@12345`
- **Store Name**: Demo Retail Counter (`VND-TEST-001`)

---

## 7. Key Production Features Included

1. **Question Manager & Reward Blueprint**: Structured question entry, Hindi/English riddles, YouTube video clues, server-enforced countdown timers, multi-synonym normalization, reward payout types (`COINS`, `CASH`, `CASHBACK`, `COUPON`, `FREE_PIN`), and consolation fallback offers.
2. **Question Collections**: Configurable rotation (`SEQUENTIAL`, `RANDOM`, `RANDOM_NO_REPEAT`, `ROUND_ROBIN`, `WEIGHTED`), questions-per-session limits, and **Negative Marking** with zero-balance safety guards.
3. **Hunt Broadcast Messaging**: Live clue push notifications, 4-digit verification PIN (stored hashed), auto-deactivating validity windows, and dynamic unpredictable QR tokens.
4. **Customer Claim API**: Secure dynamic QR claim endpoint (`/api/broadcast/claim.php`) with verbal 4-digit PIN verification, anti-fraud rate limiting, and idempotency protection.
5. **Targeted Bonus & Offers Module**: Manual and bulk spreadsheet upload with auto-cut checkout priority rule engine (`User > Product > Category > Vendor`).
6. **Vendor Portal**: Complete 32-page merchant management system for products, inventory, orders, QR counters, withdrawals, settlements, and store security.

