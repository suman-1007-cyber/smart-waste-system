-- Promote an existing registered user to municipal officer.
-- Run in Cloud SQL (Query / mysql client), changing the email:
UPDATE users SET role = 'officer' WHERE email = 'officer@example.com';
