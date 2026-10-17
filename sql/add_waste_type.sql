-- Only needed if your live complaints table was created before waste_type existed.
-- (The app also does this automatically on first load if the DB user is allowed to ALTER.)
-- Check first:   SHOW COLUMNS FROM complaints LIKE 'waste_type';
ALTER TABLE complaints
    ADD COLUMN waste_type VARCHAR(100) NOT NULL DEFAULT 'Organic' AFTER location;
