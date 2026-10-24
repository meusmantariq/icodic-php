# Quantum Nexis Enterprise Solutions — Standalone PHP/MySQL edition (v1.1.1)

Independent rebuild of the Base44 app `6a7240b50f7686103f060c79` as a plain PHP 8.2 + MySQL/MariaDB application.
No framework, no Composer, no Node: PDO prepared statements, modular routing, 4 languages (en/ar/ur/hi, RTL) and 4 regions (UK/UAE/IN/PK), WhatsApp-integrated order flow, admin console for all 13 content entities.

## Run it

| Target | How |
|---|---|
| **cPanel / shared hosting** | Upload, open `/install/`, follow `docs/DEPLOYMENT.md` |
| **Render (free)** | [![Deploy to Render](https://render.com/images/deploy-to-render-button.svg)](https://render.com/deploy?repo=https://github.com/meusmantariq/icodic-php) — uses `render.yaml` → `deploy/Dockerfile.allinone` (Apache + PHP + embedded MariaDB) |
| **Railway / any Docker host with external MySQL** | `deploy/Dockerfile` + `railway.json`; set `MYSQL_URL` (or `MYSQLHOST`…), `ADMIN_EMAIL`, `ADMIN_PASSWORD` |
| **Local** | `cd public_html && php -S 127.0.0.1:8080 index.php` then open `/install/` |

Container boots run `deploy/make_config.php` (config from environment) and `database/cli_install.php` (idempotent schema + seed + admin). If `ADMIN_PASSWORD` is not set, a random one is generated and printed **once** in the deploy log.

## Environment variables (containers)
`MYSQL_URL` or `DB_HOST/DB_PORT/DB_NAME/DB_USER/DB_PASS` · `ADMIN_EMAIL` · `ADMIN_PASSWORD` · `APP_URL` · `MAIL_DRIVER` (`log`|`mail`|`smtp`) + `SMTP_*` · `OPENAI_API_KEY` (optional live-chat) · `WHATSAPP_NUMBER` · `APP_DEBUG`.

## Repository layout
`public_html/` web root (index.php, .htaccess, assets, uploads, install) · `app/` Core, Controllers, Models, Views, config, lang · `database/` schema.sql, seed.sql (content only), build_seed.php, source exports · `deploy/` Dockerfiles + entrypoints · `docs/` deployment, inventory, migration checklist · `tests/smoke.sh`.

Customer records (orders, contact messages, security assessments) are **not** in this public repository; they ship only in the private release ZIP.
