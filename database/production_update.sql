-- Hunter Wallet production-safe incremental update
-- Select the target database in phpMyAdmin before importing this file.
-- This file does not create/drop a database and does not insert demo data.
SET @db := DATABASE();
SET @sql := (SELECT IF(COUNT(*)=0,'ALTER TABLE vendors ADD COLUMN logo_path VARCHAR(255) NULL AFTER address','SELECT 1') FROM information_schema.columns WHERE table_schema=@db AND table_name='vendors' AND column_name='logo_path'); PREPARE s FROM @sql; EXECUTE s; DEALLOCATE PREPARE s;
SET @sql := (SELECT IF(COUNT(*)=0,'ALTER TABLE vendors ADD COLUMN cover_image_path VARCHAR(255) NULL AFTER logo_path','SELECT 1') FROM information_schema.columns WHERE table_schema=@db AND table_name='vendors' AND column_name='cover_image_path'); PREPARE s FROM @sql; EXECUTE s; DEALLOCATE PREPARE s;
SET @sql := (SELECT IF(COUNT(*)=0,'ALTER TABLE spot_campaigns ADD COLUMN gps_mode ENUM(''GPS_REQUIRED'',''DESK_STUDY'') NOT NULL DEFAULT ''GPS_REQUIRED'' AFTER venue_name','SELECT 1') FROM information_schema.columns WHERE table_schema=@db AND table_name='spot_campaigns' AND column_name='gps_mode'); PREPARE s FROM @sql; EXECUTE s; DEALLOCATE PREPARE s;
INSERT IGNORE INTO system_settings(`key`,`value`,is_secret) VALUES ('brand_name','Hunter Wallet',0),('brand_tagline','Secure administration portal',0),('theme_mode','light',0),('primary_color','#12304a',0),('accent_color','#24c79a',0),('default_currency','INR',0),('smtp_port','587',0),('session_timeout','1800',0);
