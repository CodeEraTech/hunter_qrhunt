# Hunter Wallet Admin Panel

Security-first wallet administration built with Core PHP 8.1+, PDO, MySQL 8+, HTML/CSS, and vanilla JavaScript. The backend and database ledger—not the client—are the source of truth for every balance change.

## Requirements

- PHP 8.1+ with `pdo_mysql`, `openssl`, `json`, `session`, and `curl`
- MySQL 8+
- `mysqldump` for CLI backups
- HTTPS in production

## Database setup

Create a protected environment file:

```bash
cp .env.example .env
```

Generate `APP_KEY` with `php -r 'echo bin2hex(random_bytes(32)), PHP_EOL;'`, then update `.env`. Select the target database in phpMyAdmin and import the schema:

```bash
mysql -u YOUR_DB_USER -p YOUR_DATABASE < database/hunter_wallet.sql
```

The schema creates 60 InnoDB tables, immutable audit triggers, four roles, the complete permission catalog, role mappings, withdrawal limits, bonus rules, QR batches, the Category & Character Master Hub, staff KYC/OTP, coupons, settlements, and Hunt security settings. It does not create or drop a database, so it works with cPanel-restricted users; it resets tables inside the selected database. Back up any existing live database before importing.

## Initial administrator

No default administrator or default password is shipped. Create the first Super Admin from the CLI:

```bash
php database/create_admin.php "Administrator Name" admin admin@example.com "use-a-unique-12+-character-password"
```

The password is stored only through `password_hash()`. Enable TOTP MFA after setting a strong `APP_KEY`:

```bash
php database/enable_admin_mfa.php admin
```

Add the one-time displayed secret to the administrator's authenticator app, verify login, and securely discard the displayed secret. The database stores only an AES-256-GCM encrypted form.

## Local run instructions

On macOS with Homebrew:

```bash
brew install php mysql
brew services start mysql
mysql -u root < database/hunter_wallet.sql
php database/create_admin.php "Local Admin" admin admin@example.test "choose-a-local-password"
php database/seed_demo.php
php -S 127.0.0.1:8080 router.php
```

Open `http://127.0.0.1:8080/admin/login.php`. Use `COOKIE_SECURE=0` only for local HTTP. Production must use HTTPS, `COOKIE_SECURE=1`, `APP_ENV=production`, a non-development web server, and a document-root configuration that blocks `.env`, `config/`, `includes/`, `database/`, `logs/`, `tests/`, and upload execution. Apache protections are included in `.htaccess`; equivalent Nginx rules must be configured explicitly.

## Completed modules

- Secure admin authentication, persisted login throttling, session rotation/timeout, logout CSRF, and optional TOTP MFA
- Granular RBAC with Super Admin, Finance Admin, Support Admin, Security Admin, and editable role permissions
- Dashboard statistics and recent wallet, withdrawal, and administrator activity
- User search/filter/pagination, detail/edit, account status controls, session revocation, devices, and activity
- Wallet overview, atomic credit/debit adjustments, idempotency, ledger, detail view, and compensating reversals
- Three-tier wallet buckets: withdrawable real cash, non-withdrawable bonus, and expiring promo cash; checkout priority is promo → configured bonus slab → real cash
- Bonus/promo expiry jobs with partial-spend-safe reversal, bonus rules, and admin bucket controls
- Withdrawal creation, attempt history, risk/device review, strict state machine, limits, duplicate protection, and one-time debit on approval
- Device binding/blocking, API-session revocation, login-attempt tracking, security events, and risk levels
- Notification composition and queue storage, plus provider-log schema
- User, transaction, withdrawal, and security reports with date filters and CSV formula-injection protection
- Administrator accounts, roles, permissions, immutable audit logs, and financial settings
- Device-bound bearer APIs for login/logout, balance, transactions, withdrawals, devices, and notifications
- Hunt campaigns, QR master spots, geofence/device validation, server-generated rewards, verified payment activation, video clues, rotating secrets, questions, scratch sessions, reward approvals, vendors, gifts, and single-use reward codes
- Dynamic video categories, normalized YouTube privacy embeds, clue text, QR linking, pre-roll/mid-roll/full-screen advertisements, skip timing, and sponsor CTA metadata
- Field-by-field master campaign manager with spot binding IDs, category theme inheritance, public/whitelist targeting, city/venue, GPS radar and unlock radii, indoor breadcrumbs, riddle chains/synonyms, reward gates, TTL/reversal, frequency caps, and campaign audit history
- Programmable QR campaigns with reward type/value, time lock, auto reversal, merchant binding, frequency, budget pool, operating window, and promo grant API
- Bulk QR batch generation from the exact eight-column CSV specification, auto-count mode, batch history, mapping CSV, and downloadable print-card ZIP
- Staff KYC profiles with protected document uploads, KYC status, passwordless OTP challenge flow, coupons with minimum-cart rules, and merchant settlement records
- API request logging/rate limiting and persisted idempotent withdrawal responses
- Responsive navigation, cards, tables, filters, custom confirmations, loading states, toasts, empty/error states, and accessible controls
- Security headers, prepared statements, output escaping, CSRF, generic production errors, protected uploads, environment configuration, and CLI backups

## Mobile API

Every protected endpoint requires:

```text
Authorization: Bearer <token>
X-Device-ID: <login-device-id>
```

Endpoints:

- `POST /api/auth/login.php` — JSON: `identity`, `password`, `device_id`, optional `device_name` and `platform`
- `POST /api/auth/logout.php`
- `GET /api/wallet/balance.php`
- `GET|POST /api/wallet/payment-methods.php` — bank/UPI payout references are encrypted at rest and only masked values are returned
- `POST /api/wallet/checkout.php` — requires `Idempotency-Key`; JSON: `bill_amount`, optional `merchant_id`; server applies promo → bonus slab → real cash
- `GET /api/transactions/index.php?page=1`
- `GET /api/withdrawals/index.php?page=1`
- `POST /api/withdrawals/create.php` — also requires a 16–100 character `Idempotency-Key`; JSON: `amount`, `payment_method`, and `payment_account`
- `GET /api/devices/index.php`
- `GET /api/notifications/index.php`
- `GET /api/hunts/index.php`, `GET /api/hunts/details.php`
- `POST /api/payments/create.php`, `POST /api/payments/webhook.php`
- `POST /api/hunts/scan.php`, `POST /api/qr/scan.php`, `POST /api/hunts/gps.php`
- `GET /api/hunts/videos.php`, `POST /api/hunts/secret.php`, `GET /api/hunts/questions.php`, `POST /api/hunts/answer.php`, `POST /api/hunts/scratch.php`
- `GET /api/rewards/index.php`, `GET /api/rewards/codes.php`
- `POST /api/vendor/login.php`, `POST /api/vendor/redeem.php`, `GET /api/vendor/stats.php`

New admin screens include `/admin/video/index.php`, `/admin/qr/index.php`, `/admin/wallet/bonus.php`, `/admin/payments/index.php`, Hunt management/activation/reward screens, vendor/gift screens, and Hunt reports.

Only a masked final-four representation of `payment_account` is stored. API tokens are returned once and stored only as SHA-256 hashes. Pending withdrawals do not alter wallet balances.

## Financial invariants

- All money uses validated two-decimal strings, integer paise for PHP arithmetic, and `DECIMAL(18,2)` in MySQL.
- Wallet operations lock rows with `SELECT ... FOR UPDATE` and commit balance, ledger, and audit changes together.
- Real cash, bonus, and promo cash are separate database columns and ledger buckets; withdrawals read only real cash.
- Checkout is idempotent and always deducts active promo first, then only the configured percentage of bonus, then real cash.
- Promo/bonus expiry reversals are transactional and cap the reversal at the remaining bucket balance after partial spending.
- A debit cannot produce a negative balance.
- Adjustment idempotency is enforced by a unique database key.
- Reversal creates a new compensating ledger entry and cannot run twice.
- Withdrawal flow is `PENDING → UNDER_REVIEW → APPROVED → PROCESSING → PAID`; rejection/cancellation paths are explicitly limited.
- Approval rechecks account status, current balance, effective limits, daily totals, other pending requests, and critical security events.
- Audit rows cannot be updated or deleted because MySQL triggers reject both operations.

## Backups

Set `BACKUP_DIR` to an existing writable absolute directory outside the web root, then run:

```bash
php database/backup.php
```

Backups use a consistent `mysqldump --single-transaction`, receive mode `0600`, and expire according to `BACKUP_RETENTION_DAYS`. Schedule this command with cron/systemd and copy backups to encrypted off-server storage.

## Verification

Syntax and static checks:

```bash
find . -name '*.php' -print0 | xargs -0 -n1 php -l
node --check assets/js/app.js
```

Reusable HTTP smoke tests are in `tests/`. They require deliberately created test accounts and environment variables; never run them against production financial data.

## Remaining limitations

- SMS, email, and push delivery require provider-specific workers; the queue and delivery-log data model are implemented.
- Razorpay/PhonePe webhook signing is HMAC-ready and server-verifies signed order identifiers, amount, and currency; production provider SDK/webhook field mapping and payout API credentials still require deployment-specific adapters.
- QR/video reward expiry is implemented with MySQL expiry jobs and `process_wallet_expiries()`; schedule it from cron/worker (Redis is not required by this implementation).
- The PDF-described mobile ad pause/resume and “reward unlock after ad completion” player is represented by secure ad metadata and APIs; actual playback/analytics must be implemented in the consumer mobile/web player.
- Excel binary parsing and production QR raster/vector encoding require a deployment-selected library; the built-in importer accepts standards-compliant CSV and exports SVG print cards plus mapping CSV.
- OTP challenges are fully persisted and rate-bounded, but production SMS/WhatsApp delivery still requires provider credentials/adapter configuration.
- Two-person approval for high-value withdrawals is not enabled; strict single-admin approval and auditing are implemented.
- Automated fraud handling is rule-based (limits, duplicates, device/account state, and recent critical events), not an ML risk engine.
- Backup scheduling, encrypted off-server replication, TLS termination, WAF, and platform-specific mobile screenshot protection are deployment responsibilities.
- The project supplies wallet administration APIs, not a complete consumer registration/password-reset/payment-provider API suite.
- Profile-image/document upload workflows are not exposed; when added, they must use the protected upload directory and MIME/size/renaming controls from the specification.
