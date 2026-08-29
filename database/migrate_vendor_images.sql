-- Run once on an existing installation after selecting the Hunter Wallet database.
ALTER TABLE vendors ADD COLUMN logo_path VARCHAR(255) NULL AFTER address, ADD COLUMN cover_image_path VARCHAR(255) NULL AFTER logo_path;
