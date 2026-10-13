# MarketHub — Laravel Multi-Vendor Marketplace

**Laravel 12 · Vue 3 · Inertia · MySQL / Supabase PostgreSQL · Docker · Render**

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

## Persistent database — option A: Supabase (recommended for Render)

Create a dedicated Supabase project, run the SQL in `database/supabase/001_private_schema.sql` through its SQL Editor, and use **Connect → Session pooler** (port 5432). This app runs through Laravel's server-side PostgreSQL driver, not the Supabase public Data API. The `markethub` schema is deliberately private: **do not add it to the Supabase exposed schemas**. In Render's service **Environment** section, set the variables below using your project's actual values (do not commit passwords):

```dotenv
APP_ENV=production
APP_DEBUG=false
APP_KEY=base64:YOUR_PERSISTENT_LARAVEL_KEY
DB_CONNECTION=pgsql
DB_HOST=YOUR_SUPABASE_SESSION_POOLER_HOST
DB_PORT=5432
DB_DATABASE=postgres
DB_USERNAME=YOUR_SUPABASE_POOLER_USERNAME
DB_PASSWORD=YOUR_SUPABASE_DATABASE_PASSWORD
DB_SSLMODE=require
DB_SEARCH_PATH=markethub
APP_URL=https://markethub-b3fz.onrender.com
MARKETHUB_SEED_DEMO=false
```

Use `php artisan key:generate --show` **once** to create APP_KEY, and retain it through future deploys. Never reveal it or put it in GitHub. Only use the database password and normal PostgreSQL connection: you do **not** need to expose Supabase `service_role` or other API keys.

## Persistent database — option B: managed MySQL

```dotenv
DB_CONNECTION=mysql
DB_HOST=YOUR_MANAGED_MYSQL_HOST
DB_PORT=3306
DB_DATABASE=markethub
DB_USERNAME=YOUR_DB_USERNAME
DB_PASSWORD=YOUR_DB_PASSWORD
MARKETHUB_SEED_DEMO=false
```

Configure TLS on your MySQL provider and use a least-privileged application user. Laravel's existing schema and Eloquent models work with both drivers. Database migrations run at startup. **Switching database providers does not import data from your old SQLite database; back it up and migrate separately if needed.**

### Secure initial admin

For a fresh persistent database you will have no publicly known demo accounts. Set `MARKETHUB_ADMIN_EMAIL` and `MARKETHUB_ADMIN_PASSWORD` (12+ characters) temporarily in Render, let the app boot once to create the admin account, then **delete these two variables** from the hosting environment. Subsequent restarts do not replace an existing admin password. Alternatively run `php artisan markethub:bootstrap-admin` with the temporary environment variables.

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
- Run `database/supabase/001_private_schema.sql` before applying Laravel migrations. Do not expose the `markethub` schema in Supabase's Data API settings. Avoid using Supabase anonymous/frontend keys; the app uses only server-side PDO.
- Confirm the live Render service uses its intended persistent database **before merging/deploying this upgrade**. Back up existing data.
- Startups do not delete database tables; schema changes are regular migrations.
- The queue worker runs alongside the web service for portfolio demonstrations; move it to its own worker for higher availability.
