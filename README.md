# MarketHub — Laravel Multi-Vendor Marketplace

[![Live Demo](https://img.shields.io/badge/Live_Demo-Render-46a2f1?style=for-the-badge)](https://markethub-b3fz.onrender.com/shop)
[![Backend](https://img.shields.io/badge/Backend-Laravel_12-ff2d20?style=for-the-badge)](https://laravel.com/)
[![Database](https://img.shields.io/badge/Database-Aiven_MySQL_8.4-4479a1?style=for-the-badge)](https://aiven.io/mysql)
[![Frontend](https://img.shields.io/badge/Frontend-Vue_3-42b883?style=for-the-badge)](https://vuejs.org/)

**Laravel 12 · Vue 3 · Inertia.js · Managed MySQL (Aiven) · Docker · Render**

## 🌐 Live application

**[▶ Open the MarketHub storefront](https://markethub-b3fz.onrender.com/shop)**

| Area | Link | Purpose |
| --- | --- | --- |
| 🛍️ Storefront | [Browse products](https://markethub-b3fz.onrender.com/shop) | Product catalogue, search, filters and product details |
| 🛒 Shopping cart | [View cart](https://markethub-b3fz.onrender.com/cart) | Logged-in customer cart |
| 👤 Customer accounts | [Log in](https://markethub-b3fz.onrender.com/login) · [Register](https://markethub-b3fz.onrender.com/register) | Customer and vendor sign-in |
| 📦 Orders | [Order history](https://markethub-b3fz.onrender.com/orders) | Authenticated customer's previous demo orders |
| 🧰 Admin dashboard | [Admin overview](https://markethub-b3fz.onrender.com/admin) | Admin-only vendor approval, inventory and order management |
| 📊 Inventory | [Admin products](https://markethub-b3fz.onrender.com/admin/products) | Admin-only stock control and listing visibility |
| 🧾 Fulfilment | [Admin orders](https://markethub-b3fz.onrender.com/admin/orders) | Admin-only order review and shipment updates |
| ❤️ Health endpoint | [Application health](https://markethub-b3fz.onrender.com/health) | Checks database connectivity |

> **Portfolio demo:** checkout uses simulated payment processing; no real payment is charged. Login is required for carts, orders and dashboards. Public registration cannot create an administrator.

## 🗄️ Database and hosting

**Backend:** Laravel/PHP runs as a Docker web service on Render. **Database:** Aiven for MySQL 8.4 (managed relational database; free-tier configuration). The backend uses Laravel Eloquent and migrations with a private TLS-secured MySQL connection.

```text
Browser (Vue 3 + Inertia.js)
          |
          v
Render (Laravel 12 + PHP + Apache)
          |
          | PDO MySQL + TLS / Aiven CA
          v
Aiven MySQL 8.4 (private managed database)
          |
          +-- users / stores / categories / products
          +-- cart_items / orders / order_items
          +-- sessions / jobs / cache / notifications
```

**No downloadable `.db` file is checked into this repository.** MySQL on Aiven is a managed server database, not a local SQLite `.db` file. Access to the live Aiven console, server address, username, password and CA certificate is intentionally kept private; hosting secrets live in Render environment settings, **never in GitHub**. [Learn about Aiven MySQL](https://aiven.io/mysql).

MarketHub is a full-stack e-commerce portfolio app with customer, vendor and admin accounts, real relational persistence, REST catalogue API and transactional demo checkout. The payment provider is **a simulator**, not an actual payment service. Never supply real card numbers or delivery details.

## Shared product catalogue (all users)

MarketHub products belong to the **marketplace**, not to individual customer accounts. Every visitor and every newly registered customer sees the same active listings from approved vendors at [the storefront](https://markethub-b3fz.onrender.com/shop). A customer account is only needed for cart, checkout and order history.

On the **first startup with an empty MySQL product catalogue**, the application runs `php artisan markethub:seed-shared-catalog` and adds **10 sample items** across electronics, fashion, home, books and sports, owned by a fictional approved `MarketHub Demo Store`. This system-owned vendor receives a cryptographically random, undisclosed password; **no shared public vendor/admin credentials** are created. The catalogue seed does **not** run when listings exist and does **not** delete, replace, or reset registered customers, orders or inventory. It will not recreate deleted products once the system demo store exists.

To opt out of automatic initial sample-product creation, set `MARKETHUB_SEED_CATALOG=false` in Render. The existing `MARKETHUB_SEED_DEMO=false` remains unchanged. New real vendors may register, get store approval from an admin, and publish their own products; their approved, active listings are then shared with all customers. Stock remains shared and decreases on successful **simulated** checkout.

> Product photos are illustrative external Unsplash images. This is a portfolio demo, not a real payment-enabled business.

## Admin analytics dashboard

The [role-protected dashboard](https://markethub-b3fz.onrender.com/admin) includes two real-data charts:
- **Simulated sales trend** (PKR, line/area) and **completed demo order volume** (bar chart), with 7 / 30 / 90-day selectors.
- **Stock by category** (shared active inventory from approved stores), a low-stock watchlist, counts of users, orders, pending vendors and listings, and an admin-only refresh button.

All charts aggregate **actual stored Laravel orders and products** from the configured persistent database (Aiven MySQL in production). Empty periods display zero-activity states, never fabricated transactions. *Revenue is simulated:* only demo orders with paid/shipped statuses count towards demo sales, not pending orders. The feature runs on SQLite and MySQL in CI and does not require charting SaaS, paid API keys or new JavaScript dependencies. No changes to existing data or migrations are required.

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
4. Open your existing MarketHub **Render web service → Environment**. Add these environment variable names and set their values from Aiven; use the Render dashboard's secret values, not GitHub files. This repository's `main` branch already includes the MySQL-compatible Docker startup script; configure the database credentials privately in Render, then use the regular deploy workflow.

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

## Account and administrator management

- **Customers and vendors** register themselves through the [public registration page](https://markethub-b3fz.onrender.com/register), set their own private passwords and log in at [MarketHub login](https://markethub-b3fz.onrender.com/login).
- **Existing admins** can use the [Admin Users panel](https://markethub-b3fz.onrender.com/admin/users) to search users, view account roles and grant/revoke admin privileges for ordinary customers. Re-entering the existing admin's password is required for role changes. An admin cannot revoke their own access; vendor identities cannot be reassigned because they own stores and fulfillment records.
- **First admin without Render credentials:** The service owner can create their own customer account through registration, then use a privileged database client (DBeaver connected to Aiven MySQL) to promote only that verified account to the first administrator. Do not run a bulk role update; never allow public signup to choose `admin`.
- **One-time alternative:** The `markethub:bootstrap-admin` command is still available via temporary Render environment variables, but ordinary user accounts and passwords are never managed through deployment settings.

For security, a newly created marketplace has no built-in public admin credentials. Existing users and orders are unaffected by installing this admin user-management page.

### Secure initial admin

**The admin dashboard cannot be accessed by creating a regular customer or vendor account.** Use a *new, unused email address* for the first admin (the command refuses to promote an existing customer automatically).

1. Open **Render → MarketHub → Environment**. Set `MARKETHUB_ADMIN_EMAIL` to your intended admin email and `MARKETHUB_ADMIN_PASSWORD` to a randomly generated password (12+ characters). **Enter them only in Render, not in GitHub or this chat.**
2. Click **Save, deploy**. Wait for the new deploy to become **Live**, then check the deployment logs for `Initial administrator created` or `Administrator already exists`.
3. In Render, **delete both** temporary `MARKETHUB_ADMIN_*` entries and save/deploy again. **Do not delete `APP_KEY` or your existing `DB_*` / TLS settings.**
4. Visit **https://markethub-b3fz.onrender.com/login**, sign in with the newly created admin account and open **https://markethub-b3fz.onrender.com/admin**.

Only the admin can approve/suspend vendors; review all orders, mark paid-demo order items as shipped; and review, show/hide and restock every vendor's products. New vendor accounts remain unapproved until the admin approves the store. Payments are simulations.


Categories are created idempotently; customer and vendor accounts are created via registration. Admins must approve new vendor stores.

## Disposable reviewer demo on Render

For a disposable reviewer demo, configure `DB_CONNECTION=sqlite` and `MARKETHUB_SEED_DEMO=true` in Render's Environment. The Blueprint intentionally **does not** set those variables: it must not override the live persistent MySQL configuration. It provisions APP_KEY for new Blueprint services. The storage is **ephemeral**; customers and orders are not guaranteed to survive Render instance recreation. The demo bootstrap only seeds when the SQLite user table is empty.

The disposable local SQLite demo seeder creates test-only accounts. **Never enable these seeded accounts against the live Aiven database**, and do not use real customer or payment details in any demo.

## Payments and media

Checkout currently uses a fake gateway and synthetic card numbers. The interface labels all payments as demo transactions. Integrate a real processor and its verification/webhooks before calling the store production-ready. Image URLs are validated on input and displayed directly; for production media management, use a dedicated object-storage service.

## API

`GET /api/v1/products?q=&category=&min=&max=&per_page=` and `GET /api/v1/products/{slug}`.

## Security and deployment

- Never commit `.env`, credentials, API tokens, real customer data or card numbers.
- Keep `APP_DEBUG=false` and `APP_KEY` stable in production.
- **MySQL setup does not need Supabase or any SQL bootstrap script**: Laravel creates its tables automatically with `php artisan migrate --force`. The private-schema script is **only** for optional Supabase PostgreSQL deployments.
- Before changing database hosts, back up existing records and confirm the Render service points at the intended persistent database. A successful `/health` check verifies the configured database responds, but does not by itself prove which provider is in use.
- Startups do not delete database tables; schema changes are regular migrations.
- The queue worker runs alongside the web service for portfolio demonstrations; move it to its own worker for higher availability.
