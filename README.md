# ♻️ Smart Waste Management System

Citizens report overflowing bins and other waste problems. Municipal officers review
the reports and move them from **Pending → In Progress → Resolved**.

PHP 8.2 + Apache + MySQL (Cloud SQL), deployed with Docker on Render.

## What changed in this version

**Security fixes (important)**
- `admin_panel.php` is now officers-only. Before, anyone with the URL could change statuses.
- `login_process.php` no longer logs in unknown emails ("fallback for testing" removed).
- Reports are saved with the real signed-in user (it was hard-coded to user 1).
- All SQL uses prepared statements. All output is HTML-escaped (names were not).
- CSRF tokens on every form; session ID regenerated at login; secure cookie flags behind Render's HTTPS proxy.
- Same error for wrong email and wrong password. No SQL errors shown to visitors.

**Features**
- Dashboard: *My reports* / *All city reports* tabs, stat cards, search, status filter, pagination.
- Citizens see other people's names masked ("Asha K."); officers see full names.
- Waste type (Organic / Recyclable / Hazardous / E-waste / Mixed) is saved in the existing `complaints.waste_type` column, shown as a tag, filterable, and included in the CSV.
- Report form: issue-type quick picks, character counter, validation, redirect after saving (no duplicate on refresh).
- Officer panel: stats, overdue banner (pending > 3 days), filters, per-row status save that keeps your filter, CSV download.
- Register: confirm password, duplicate-email message, password minimum.
- Times are stored in UTC and shown in `APP_TIMEZONE` (default `Asia/Kolkata`).

**Code structure**
- `includes/bootstrap.php` (session, DB, helpers) and `includes/layout.php` (header/footer) replace code repeated on every page.
- One stylesheet: `assets/css/style.css`.
- `Login.php` (capital L, duplicate of `index.php`) now just redirects. You can delete it if nothing links to it.

**Docker**
- The old `DirectoryIndex` line wrote to a file that doesn't exist (`apache2.doc.conf`), so it did nothing. Removed.
- Apache now listens on Render's `$PORT` (falls back to 80), blocks web access to `includes/`, `config/`, `sql/`, adds basic security headers, and PHP errors are logged instead of shown.

## Files

    index.php               sign in (also the landing page)
    register.php / register_process.php
    login_process.php / logout.php
    dashboard.php           citizens: reports, stats, filters
    report_bin.php          submit a report
    admin_panel.php         officers: manage statuses, CSV
    includes/               bootstrap.php, layout.php
    assets/css/style.css
    config/db.php           YOUR existing file (not included here)
    sql/                    schema_reference.sql, make_officer.sql, add_waste_type.sql
    docker/                 start.sh, apache + php config

## Setup notes

1. Keep your existing `config/db.php`. It only needs to create a mysqli connection named `$conn`
   (see `config/db.example.php`).
2. **No manual database changes are required.** The code uses your existing `users` and `complaints` tables.
   If your live `complaints` table was created before the `waste_type` column existed (`CREATE TABLE IF NOT EXISTS`
   in `db.php` never adds columns), the app adds it automatically on first load when the DB user is allowed to
   `ALTER`. If not, it hides waste type and everything else keeps working. To add it yourself, run
   `sql/add_waste_type.sql`. Existing reports get the default `Organic`.
3. Make an officer: register normally, then run `sql/make_officer.sql` with their email.
4. Optional Render env var: `APP_TIMEZONE` (e.g. `Asia/Kolkata`).

## Known limits / next steps

- Sessions are stored on the container's disk, so users are signed out when Render redeploys or restarts.
  A database-backed session handler fixes that if it matters.
- No login rate limiting yet (add Cloudflare or a failed-attempts table if the site gets public traffic).
- Ideas that need a schema change: issue category column, photo upload (needs object storage, since
  Render's disk is temporary), email/SMS when a status changes.
