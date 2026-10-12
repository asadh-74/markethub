# MarketHub

A multi-vendor marketplace built with **Laravel 12 + Vue 3 (Inertia)**.
Customers browse and buy, vendors run their own store, admins approve stores.

## Roles
| Role | Can do |
|---|---|
| Customer | Search / filter products, cart, checkout (demo payment), order history |
| Vendor | Own store dashboard, product CRUD, ship their items, low-stock + new-order notifications |
| Admin | Approve / suspend stores, platform stats |

Demo logins (password `password`): `customer@markethub.test`, `vendor1@markethub.test`, `vendor2@markethub.test`, `admin@markethub.test`.
`vendor3@markethub.test` owns a store that is still pending approval.

## Run it
```bash
composer run dev        # server + queue worker + Vite (hot reload)
php artisan test        # 35 tests
```
Without `composer run dev`: `npm run build`, `php artisan serve`, `php artisan queue:work`.

Demo payment card: `4242 4242 4242 4242` succeeds, `4000 0000 0000 0002` is declined.

## What it demonstrates
| Topic | Where |
|---|---|
| Atomic checkout (transaction, row locks, rollback on failed payment) | `app/Services/CheckoutService.php` |
| Payment gateway abstraction (swap in Stripe without touching checkout) | `app/Services/Payment/*`, bound in `AppServiceProvider` |
| Events, queued listeners, mail + database notifications | `app/Events`, `app/Listeners`, `app/Notifications` |
| Role-based access (middleware) and ownership checks (policies) | `app/Http/Middleware/EnsureRole.php`, `app/Policies` |
| Multi-vendor orders (per-seller fulfilment, order auto-ships when all sellers ship) | `Vendor/OrderItemController.php` |
| Money as integer cents, price/name snapshots on order items | migrations, `OrderItem` |
| Public REST API with resources, validation, throttling, caching | `routes/api.php`, `Api/ProductController.php` |
| Vue 3 SPA via Inertia (no separate API needed for the UI) | `resources/js` |
| Tests: checkout rollback, stock, authorization, filters, API | `tests/Feature` |
| Docker + Render deploy, CI | `Dockerfile`, `render.yaml`, `.github/workflows/ci.yml` |

## API
`GET /api/v1/products?q=&category=&min=&max=&per_page=` and `GET /api/v1/products/{slug}` (public, read-only, cached 60s).

## Deploy (Render, free)
New -> Blueprint -> pick this repo. The demo database is rebuilt on every start, and the free tier sleeps when idle.
For real use switch `DB_CONNECTION` to MySQL/PostgreSQL, set `MAIL_MAILER` to SMTP, and replace `FakeGateway` with a real gateway.

## Ideas to extend
Image uploads (S3), Stripe, coupon codes, reviews, Redis queue/cache, Laravel Scout search.
