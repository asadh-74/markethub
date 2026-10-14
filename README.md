# MarketHub — Laravel Multi-Vendor Marketplace

**Laravel 12 · Vue 3 · Inertia · Aiven MySQL Free · Docker · Render**

MarketHub is a full-stack e-commerce portfolio app with customer, vendor and admin accounts, real relational persistence, REST catalogue API and transactional checkout. The payment provider is **a simulator**, not an actual payment service. Never supply real card numbers or delivery details.

## Key features

- Product catalogue with search, category and price filters, pagination and responsive product photo cards.
- Vendor-owned stores, admin approval/suspension, product inventory and optional HTTPS image URLs.
- Customer accounts, persisted carts, demo orders, inventory locking, order status and notifications.
- Multi-vendor fulfilment, role-based middleware, policies, API throttling/caching and CI tests.
- /health endpoint checks the database; Docker includes both pdo_mysql and pdo_pgsql.
- Startup uses **migrate**, never **migrate:fresh**; customer and order data survives application restarts **when an external persistent database is configured**.

## Local development

Requires PHP 8.2+, Composer, Node.js 20+ and SQLite/MySQL/PostgreSQL.

```bash
cp .env.example .env
composer install
php artisan key:generate
# For a disposable demo, set MARKETHUB_SEED_DEMO=true in .env
php artisan migrate
php artisan markethub:seed-categories
php artisan markethub:seed-demo        # SQLite demo only, optional
npm ci
npm run build
composer run dev
```

Run backend tests with `php artisan test`; build the frontend with `npm run build`.

## Persistent database — Aiven free MySQL (recommended)

For a **$0 portfolio deployment**, use **Aiven for MySQL Free** + **Render Free Web Service**. Aiven hosts real MySQL with 1 GB storage, 1 GB RAM and backups, with no required credit card and no preset trial expiry. Aiven may power down a database after inactivity; the Render web service also spins down when idle. Use these services for a *portfolio/demo*, not a high-availability store.

1. Sign up at https://console.aiven.io and create a project/service: **MySQL → Free** (not a trial/paid size). Name the MySQL service `markethub-db`. Aiven picks the free region.
2. Wait for the new MySQL service to report **Running**. Its existing `defaultdb` is enough to deploy MarketHub—**you do not need to create another database**. You may choose to create a separate `markethub` database from **Connect → Databases** later, but match `DB_DATABASE` to whichever database you actually use.
3. From the service **Overview → Connection information**, copy the **Host, Port, Username, Password, Database** and download **CA Certificate** as `ca.pem`. Aiven ports are service-specific; **do not assume 3306**. Keep the password and certificate private.
4. Open your existing MarketHub **Render web service → Environment**. Add these environment variable names and set their values from Aiven; use the Render dashboard's secret values, not GitHub files. The site will remain on its current database until you update the live Render environment and deploy the GitHub feature branch.

```dotenv
APP_ENV=production
APP_DEBUG=false
APP_URL=https://markethub-b3fz.onrender.com
APP_KEY=base64:YOUR_EXISTING_PERSISTENT_LARAVEL_KEY
DB_CONNECTION=mysql
DB_HOST=YOUR_AIVEN_HOST
DB_PORT=YOUR_AIVEN_MYSQL_PORT
DB_DATABASE=defaultdb
DB_USERNAME=YOUR_AIVEN_MYSQL_USERNAME
DB_PASSWORD=YOUR_PRIVATE_AIVEN_PASSWORD
MARKETHUB_MYSQL_CA_PEM="CONTENTS_OF_AIVEN_ca.pem"
MARKETHUB_SEED_DEMO=false
```

**Certificate**: `MARKETHUB_MYSQL_CA_PEM` must contain the actual multi-line PEM certificate from Aiven, including BEGIN/END CERTIFICATE. The Docker startup script writes it to `storage/certs/aiven-ca.pem` with owner-only permissions, sets Laravel's `MYSQL_ATTR_SSL_CA` to that path, and uses PDO's CA validation. Do not put the certificate, password, or encryption key in GitHub. You may instead configure `MYSQL_ATTR_SSL_CA` with an existing CA file path in the container.

**Important**: Changing `DB_CONNECTION` to MySQL does **not** migrate existing SQLite records. If the old demo has real data, back it up/export it first. The script runs `php artisan migrate --force` on startup and never wipes tables. If the database connection is temporarily unavailable, the deployment should fail rather than silently switch back to SQLite.

**Do not rotate `APP_KEY`** when switching databases if this is an existing Laravel deployment; changing it can invalidate sessions/encrypted data. If APP_KEY is missing for a *brand-new* instance, generate once using `php artisan key:generate --show`, then store that key only on Render.

Aiven guides: [Create free MySQL](https://aiven.io/docs/products/mysql/get-started) · [Connect PHP via PDO and a CA](https://aiven.io/docs/products/mysql/howto/connect-with-php) · [Free tier limits](https://aiven.io/docs/products/mysql/concepts/mysql-free-tier).

### Other database providers

The application also supports ordinary managed MySQL/MariaDB and Supabase PostgreSQL via Laravel, but **MySQL is our intended persistent database**. If you later choose Supabase, the dedicated private-schema SQL is at `database/supabase/001_private_schema.sql` and you must set `DB_SEARCH_PATH=markethub`; this is *not* necessary for Aiven MySQL.

### Secure initial admin

**The admin dashboard cannot be accessed by creating a regular customer or vendor account.** Use a *new, unused email address* for the first admin (the command refuses to promote an existing customer automatically).

1. Open **Render → MarketHub → Environment**. Set `MARKETHUB_ADMIN_EMAIL` to your intended admin email and `MARKETHUB_ADMIN_PASSWORD` to a randomly generated password (12+ characters). **Enter them only in Render, not in GitHub or this chat.**
2. Click **Save, deploy**. Wait for the new deploy to become **Live**, then check the deployment logs for `Initial administrator created` or `Administrator already exists`.
3. In Render, **delete both** temporary `MARKETHUB_ADMIN_*` entries and save/deploy again. **Do not delete `APP_KEY` or your existing `DB_*` / TLS settings.**
4. Visit **https://markethub-b3fz.onrender.com/login**, sign in with the newly created admin account and open **https://markethub-b3fz.onrender.com/admin**.

Only the admin can approve/suspend vendors; review all orders, mark paid-demo order items as shipped; and review, show/hide and restock every vendor's products. New vendor accounts remain unapproved until the admin approves the store. Payments are simulations.


Categories are created idempotently; customer and vendor accounts are created via registration. Admins must approve new vendor stores.

## Disposable reviewer demo on Render

The included `render.yaml` defaults to SQLite and `MARKETHUB_SEED_DEMO=true` so reviewers can explore the site without provisioning a database. It provisions APP_KEY for new Blueprint services. The storage is **ephemeral**; customers and orders are not guaranteed to survive Render instance recreation. The demo bootstrap only seeds when the SQLite user table is empty.

Reviewer-only logins (password `password`): `customer@markethub.test`, `vendor1@markethub.test`, `vendor2@markethub.test`, `admin@markethub.test`. Do not enable these sample accounts against your persistent database.

## Payments and media

Checkout currently uses a fake gateway and synthetic card numbers. The interface labels all payments as demo transactions. Integrate a real processor and its verification/webhooks before calling the store production-ready. Image URLs are validated on input and displayed directly; for production media management, use a dedicated object-storage service.

## API

`GET /api/v1/products?q=&category=&min=&max=&per_page=` and `GET /api/v1/products/{slug}`.

## Security and deployment

- Never commit `.env`, credentials, API tokens, real customer data or card numbers.
- Keep `APP_DEBUG=false` and `APP_KEY` stable in production.
- **MySQL setup does not need Supabase or any SQL bootstrap script**: Laravel creates its tables automatically with `php artisan migrate --force`. The private-schema script is **only** for optional Supabase PostgreSQL deployments.
- Confirm the live Render service uses its intended persistent database **before merging/deploying this upgrade**. Back up existing data.
- Startups do not delete database tables; schema changes are regular migrations.
- The queue worker runs alongside the web service for portfolio demonstrations; move it to its own worker for higher availability.
