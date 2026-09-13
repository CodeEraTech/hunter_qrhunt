# Hunter Wallet production update — 13 September 2026

Import **production_update_2026_09_13.sql** into an existing application database. It combines the incremental vendor/campaign changes, product management, vendor MFA/password-reset schema, and master wallet/payout schema. It also adds nullable payout profile columns used by withdrawal details and exports.

This release expects the core schema in `hunter_wallet.sql` to be installed already, including Real/Bonus/Promo wallets, wallet transaction idempotency, withdrawals, vendor accounts, video categories and QR batches. It is not a migration from an arbitrary older or customized schema. The opening preflight checks core columns; test against a restored copy of your actual live database to detect additional differences. MySQL 8.0.16+ is required; automated import/re-import testing was performed on MySQL 9.7.1. MariaDB has not been tested.

## Import

1. Back up the live database, including triggers, and retain the currently deployed PHP files. Restore the backup to a staging database and test the update there first.
2. Pause application writes, scheduled jobs, payment callbacks and workers for the import window. DDL auto-commits and can lock tables; wrapping this file in a transaction cannot roll back the whole migration.
3. In phpMyAdmin, select the **existing application database**, choose **Import**, and select `production_update_2026_09_13.sql`. Alternatively, from the project directory:

   ```sh
   mysql --default-character-set=utf8mb4 -u YOUR_DB_USER -p YOUR_DATABASE < database/production_update_2026_09_13.sql
   ```

4. Check for errors and the final `Hunter Wallet 2026-09-13 update completed` result. Do not use `--force` or continue after an error. Some earlier statements may already have committed; resolve the error on staging before retrying.
5. Deploy the matching updated PHP files, then check Admin Wallet, Transactions, Withdrawals, Products and vendor login. SQL alone does not update navigation, layouts or PHP functionality.
6. Compare balances and transaction counts with the backup, verify role access, then resume application traffic and jobs.

The import account needs SELECT, INSERT, CREATE, ALTER, INDEX, REFERENCES and TRIGGER privileges. Hosting providers may impose additional restrictions on trigger creation. Finance append-only triggers are recreated by this update, so keep writes paused until import completes.

**Do not import `hunter_wallet.sql` into the live database: it resets existing tables.** The older `production_update.sql` contains sample campaign inserts; use this dated release file instead. You do not need to import the separate product, finance or vendor MFA migrations afterward.

## Data behavior

- Existing customer balances, financial transaction rows, logins and passwords are not rewritten.
- Missing master wallet account ID 1 is initialized at zero. An existing master balance is preserved. The migration does not infer a master balance from historic customer/vendor balances.
- New nullable payout profile fields remain empty; no bank details or KYC verification are invented or backfilled.
- Missing permissions are added and relevant grants are assigned to the existing Super Admin role. Other roles require deliberate access configuration.
- No sample users, products, categories, questions or financial transactions are inserted. Missing branding/settings receive defaults without overwriting existing values.
- MFA secrets and finance PINs are not generated. Preserve the deployed APP_KEY and configure authentication through the existing application setup.
- Named status enums are updated to the values supported by this release. Custom statuses require reconciliation on staging before import; strict SQL mode prevents silent coercion.

## Verification

`php tests/production_migration.php` creates a uniquely named temporary database, imports the baseline and release twice, checks preservation of wallet/ledger rows and a nonzero master balance, checks no sample categories were added and verifies the four finance triggers. It drops its temporary database on completion. This test requires local database CREATE/DROP privileges and does not import into the configured application database.

Testing the migration does not certify payment-provider integration or every application workflow. A failed production migration requires investigation of the committed changes; there is no destructive down migration. If restoring a backup is necessary, do so during the maintenance window before accepting new transactions.
