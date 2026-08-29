-- Select the target database in phpMyAdmin, then run once.
ALTER TABLE spot_campaigns ADD COLUMN gps_mode ENUM('GPS_REQUIRED','DESK_STUDY') NOT NULL DEFAULT 'GPS_REQUIRED' AFTER venue_name;
