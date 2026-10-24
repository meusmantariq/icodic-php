# iCodic — cPanel Deployment Guide

Standalone PHP 8.2+ / MySQL 8 (MariaDB 10.4+) application. No Node, Composer, Docker, cron or SSH required.

## 1. Requirements (cPanel → *Select PHP Version*)
PHP 8.2 or newer with extensions: `pdo_mysql`, `mbstring`, `openssl`, `json`, `fileinfo`, `curl` (for AI chat), `gd` (optional).
Apache with `mod_rewrite` (standard on cPanel). `AllowOverride All` is standard on shared hosting.

## 2. Create the database
cPanel → **MySQL® Databases** → create a database (e.g. `cpuser_icodic`) and a user with **ALL PRIVILEGES**. Note host (`localhost`), name, user, password.

## 3. Upload files
Unzip the release locally. Two supported layouts:

**Layout A (recommended, app code outside the web root)**
```
/home/cpuser/
├── app/            ← upload
├── database/       ← upload
├── storage/        ← created automatically (logs, sessions)
└── public_html/    ← upload the CONTENTS of the zip's public_html/ here
```
**Layout B (everything inside public_html, e.g. for an add-on domain or sub-folder)**
Upload the whole folder. `app/` and `database/` are protected by their own `.htaccess` (deny all) and `index.php` auto-detects the layout. Sub-directory installs (e.g. `/icodic/`) work without changes.

Make sure `public_html/uploads/` is writable (755 is usually enough on cPanel because PHP runs as your user).

## 4. Run the installer
Open `https://your-domain/install/`. It checks requirements, asks for DB credentials and the admin account, creates all tables, imports the migrated content (118 software products, 5 ERP solutions, 17 projects, 11 services, 6 cyber services, 5 testimonials, 4 offices, 7 feed items, existing orders/messages) and writes `app/config/config.php`.

**Then delete the `install/` folder.**

Manual alternative: copy `app/config/config.example.php` → `config.php`, fill the placeholders, import `database/schema.sql` then `database/seed.sql` via phpMyAdmin, and insert an admin row:
`INSERT INTO admin_users (name,email,password_hash,role,is_active) VALUES ('Admin','you@domain.com','<password_hash()>','admin',1);`

## 5. Configure email & options (`app/config/config.php`)
- `mail.driver`: `mail` (PHP mail(), works on most cPanel hosts), `smtp` (recommended: use a cPanel mailbox, host `mail.your-domain.com`, port 465 `ssl` or 587 `tls`), or `log` (writes to `storage/logs/mail.log`).
- `mail.admin_email`: receives order, contact and security-assessment notifications.
- `site.whatsapp_number`, `site.review_url`, `site.linkedin_url`.
- `chat.api_key`: optional OpenAI-compatible key for the live-chat assistant. Without a key the assistant answers from the catalog database only.
- `app.debug` must stay `false` in production.

## 6. Admin console
`https://your-domain/admin` — email/password login, 5 failed attempts → 15-minute lock per IP+email, password reset by email, CSRF on every form, activity log.

## 7. Updating content images
Uploads go to `public_html/uploads/YYYY/MM/`. Migrated Base44 images live in `public_html/uploads/migrated/` and are referenced by relative path, so the site has **no dependency on media.base44.com**.

## 8. Troubleshooting
- **500 on every page**: check `storage/logs/php-error.log`; most often wrong DB credentials or PHP < 8.2.
- **404 on inner pages**: `mod_rewrite` disabled or `.htaccess` not uploaded (hidden file).
- **Emails not arriving**: switch `mail.driver` to `smtp` with an authenticated cPanel mailbox; check `storage/logs/mail.log` when the driver is `log`.
- **Local testing**: `cd public_html && php -S 127.0.0.1:8080 index.php`, then run `tests/smoke.sh http://127.0.0.1:8080`.

## 9. Container / PaaS deployment (Render, Railway, Docker)
- `deploy/Dockerfile.allinone` + `render.yaml`: one container with Apache + PHP 8.2 + embedded MariaDB. Used for the free Render test instance. Filesystem is ephemeral on Render Free: the database and uploads reset to the seed whenever the instance restarts or spins down after 15 idle minutes; the admin account is recreated from `ADMIN_EMAIL` / `ADMIN_PASSWORD` on every boot.
- `deploy/Dockerfile` + `railway.json`: app container with an external MySQL (`MYSQL_URL` or `MYSQLHOST`…) for persistent hosting.
- Both run `deploy/make_config.php` (config from env) and `database/cli_install.php` (idempotent schema + seed + admin) at start.
- The public GitHub seed (`database/seed.sql`) contains content only; `database/seed_full.sql` in the release ZIP also contains the original orders, contact messages and security assessments. Import it instead of `seed.sql` on your cPanel database (the web installer uses seed_full.sql automatically when present).
