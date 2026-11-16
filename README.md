# Yogitech — Gadget E-commerce on the Yogi PHP Framework

IPT10 Final Project. A custom PHP MVC framework ("Yogi") and a gadget store built on it.
Prices are in Philippine pesos (₱). Includes buyer and seller dashboards and order tracking.
Yogitech runs as a single store: one seller account manages products and orders, and sign-up creates shopper accounts.

## Requirements
- **Docker Desktop** (runs the app, the MariaDB 11.4 database and phpMyAdmin)
- Internet connection (for the product API and exchange-rate API)
- Optional: **Oracle SQL Developer Data Modeler**, to open the database diagram

## Run it
1. Install **Docker Desktop** (docker.com/products/docker-desktop), open it and wait for "Engine running".
2. Open a terminal in this folder (the one with `docker-compose.yml`) and run:
   ```
   docker compose up -d --build
   ```
   The first start takes a few minutes. It creates the `yogitech` database, the demo accounts, sample orders, and
   imports gadgets from the DummyJSON API. Later starts keep your data.
3. Open **http://localhost:8080**. phpMyAdmin is at **http://localhost:8081**.

| What | Command |
|---|---|
| Stop | `docker compose down` |
| Start again | `docker compose up -d` |
| See logs / errors | `docker compose logs -f app` |
| Reset everything (deletes data) | `docker compose down -v` then `docker compose up -d --build` |

Database: MariaDB 11.4, database `yogitech`, user `root`, password `root`.
Inside Docker the host is `db`; from your PC use `localhost`, port **3307**.

## Accounts
| Role   | Email                   | Password       | Store             |
|--------|-------------------------|----------------|-------------------|
| Seller | lynsaldevar@gmail.com   | `LynSeller123` | Yogitech Official |
| Buyer  | buyer@yogitech.test     | `password123`  | — (demo shopper)  |

Change the seller password in **Account settings**, and don't publish this README with the real password.

Vouchers: `YOGI10`, `CYBER500`, `FREESHIP`, `NEON2077` (each has total and per-buyer limits).

## Payments (sandbox)
GCash and card checkouts go through Yogitech's built-in sandbox gateway, so no real money moves.
- Cards: `4242 4242 4242 4242` (success), `4000 0000 0000 0002` (declined), `4000 0000 0000 9995` (no funds).
  Any future expiry and any CVC.
- GCash: enter a mobile number and a 4-digit MPIN (`0000` = wrong MPIN), then the 6-digit code shown on screen.
- If a payment fails, the order page shows **Pay now**. The seller can confirm GCash/card orders only after they're paid.

## Commands
Run with `docker compose exec app php yogi <command>`.

| Command | What it does |
|---|---|
| `test` | Run the unit tests (30 tests) |
| `migrate` | Run new migrations |
| `setup` | Fresh database + demo data (deletes current data) |
| `import <smartphones\|laptops\|tablets\|accessories>` | Import a category from the API |
| `routes` | List all routes |
| `mail:test <email>` | Send a test email |
| `db:repair` | Create missing tables or columns without deleting data |
| `gmail:auth` | One-time Gmail sign-in. Run on your PC, not in Docker (see below) |

## Emails (Gmail API)
Buyers and the seller get emails for sign-up, password resets, new orders and every order status change.
Set `MAIL_DRIVER` in `.env`: `gmail` sends real emails from the store's Gmail, and `log` sends nothing and saves each
email as HTML in `storage/mail/`.

Gmail setup (one time):
1. In console.cloud.google.com, create a project and enable the **Gmail API**.
2. Set up the OAuth consent screen (Audience **External**) and add the sending Gmail as a **test user**.
3. Create a **Desktop app** client. Put its ID and secret in `.env` as `GMAIL_CLIENT_ID` and `GMAIL_CLIENT_SECRET`,
   and set `MAIL_FROM_ADDRESS` to that Gmail.
4. Run `php yogi gmail:auth` **on your PC** (needs PHP 8). It can't run inside Docker, because Google sends the
   sign-in back to `127.0.0.1` on your PC. It saves `GMAIL_REFRESH_TOKEN` and sets `MAIL_DRIVER=gmail`.
5. `docker compose restart app`, then `docker compose exec app php yogi mail:test you@gmail.com`.

While the Google app is in **Testing**, the sign-in expires every 7 days. Run `gmail:auth` again, or click
**Audience → Publish app** once.

## External APIs
1. **DummyJSON**: `GET https://dummyjson.com/products/category/{slug}?limit=0` (JSON, no key). Product catalog.
2. **ExchangeRate-API**: `GET https://open.er-api.com/v6/latest/USD` (JSON, no key). USD to PHP rate, cached 12 h,
   with a fallback rate.
3. **Gmail API**: `POST https://gmail.googleapis.com/gmail/v1/users/me/messages/send` (OAuth 2.0). Notification emails.

Yogitech's own JSON API: `/api/products?q=`, `/api/products/{id}`, `/api/track/{tracking}`,
`/api/exchange-rate`, `/api/shipping-quote?region=&subtotal=`.

## Folder structure
```
yogitech/
├── core/                  Yogi framework (Router, Pipeline, Request, Response, Database, Model, View ...)
├── app/                   Controllers/, Middleware/, Models/, Services/, Views/
├── config/                Settings (app, database, middleware, services, shipping)
├── routes/                web.php, api.php
├── database/              migrations/*.sql, seeds/, schema.sql, yogitech.sql
├── public/                index.php (front controller), assets/, uploads/
├── storage/               logs/, cache/, mail/
├── tests/                 Unit tests + runner
├── docker/, Dockerfile, docker-compose.yml
├── Yogitech_Erd.ddl       Database in Oracle DDL, for SQL Developer Data Modeler
├── yogitech_datamodeler/  Saved Data Modeler design
├── product-images/        Product photos (save them with: docker compose exec app php export-images.php)
└── yogi                   CLI tool
```

## Database diagram (SQL Developer Data Modeler)
`Yogitech_Erd.ddl` is the same database as `database/yogitech.sql`, converted to Oracle DDL so Data Modeler can draw it.
It has the 15 tables (14 app tables plus `migrations`), their keys and 20 named relationships, with no data.

1. **File → Import → DDL File** → choose `Yogitech_Erd.ddl` → **Oracle Database 12c** or newer → **Finish** → **Merge**.
   This gives the relational (physical) model. Right-click → **Auto Layout** if the tables overlap.
2. Click **<<** (**Engineer to Logical Model**) → **Engineer** for the logical model, then switch it to **Barker** notation.
3. Type each relation's **Name on Source / Name on Target** (double-click the relation → **General**):

| Relation | Source → Target | Name on Source | Name on Target |
|---|---|---|---|
| buyers_place_orders | users → orders | places | placed by |
| sellers_fulfill_orders | users → orders | fulfills | fulfilled by |
| sellers_list_products | users → products | lists | listed by |
| categories_group_products | categories → products | groups | belongs to |
| orders_contain_order_items | orders → order_items | contains | part of |
| products_appear_in_order_items | products → order_items | appears in | refers to |
| orders_have_shipment_events | orders → shipment_events | has | tracks |
| users_have_cart_items | users → cart_items | has | added by |
| products_added_to_cart_items | products → cart_items | added as | holds |
| users_have_wishlists | users → wishlists | saves | saved by |
| products_saved_in_wishlists | products → wishlists | saved in | holds |
| users_write_reviews | users → reviews | writes | written by |
| products_receive_reviews | products → reviews | receives | rates |
| orders_have_reviews | orders → reviews | has | comes from |
| buyers_make_payments | users → payments | makes | made by |
| users_redeem_vouchers | users → voucher_redemptions | redeems | redeemed by |
| vouchers_have_redemptions | vouchers → voucher_redemptions | used in | uses |
| users_request_password_resets | users → password_resets | requests | requested by |
| users_receive_email_logs | users → email_logs | receives | sent to |
| orders_have_email_logs | orders → email_logs | has | about |

To open the saved design in `yogitech_datamodeler/` with **File → Open**, you also need its `.dmd` file.

## Troubleshooting
- **"Connection refused" / "Still waiting for the database"** → make sure Docker Desktop is running. If it keeps
  waiting, reset with `docker compose down -v` then `docker compose up -d --build`.
- **Products show placeholder images** → there was no internet during setup. Use Seller → **Import products**.
- **Emails not arriving** → run `docker compose exec app php yogi mail:test you@gmail.com` and read the error; check Spam.
  "Gmail sign-in expired" means run `gmail:auth` again.
- **A table is missing** → `docker compose exec app php yogi db:repair` (keeps your data).
- **Data Modeler: Engineer is greyed out** → the import didn't load any tables. Import `Yogitech_Erd.ddl` again, then use **<<**, not **>>**.
