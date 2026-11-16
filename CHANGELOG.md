# Changelog

## 1.3.0 — Sandbox payments, product videos, bunny logo

### New
- **Sandbox payment gateway** for GCash and card (MPIN + SMS code, 3-D Secure, declines, low balance, cancel),
  with **Pay now** for unpaid orders. Optional **PayMongo test mode** gateway. Table `payments` (migration 012).
- **Product videos** (YouTube link or MP4/WebM upload), shown first in the gallery with click-to-play
  (migration 013 adds `products.video_url` and four official demo videos).
- **Bunny mascot logo** (yellow, cyan headphones) in the site, favicon and emails (inline image for Gmail).
- **Developer mailbox** at `/dev/mail` (local + `APP_DEBUG` only) and a hint on Forgot password when emails are
  only being saved (`MAIL_DRIVER=log`).

### Changes
- Seller account: `lynsaldevar@gmail.com` / `LynSeller123`. Demo accounts are no longer shown on the login page.
- **Add product** and **Import products** live in the seller side panel only (removed from page headers).
- Checkout no longer asks for card/GCash details; they're entered on the payment page.
- Sellers can't confirm unpaid GCash/card orders.


## 1.2.0 — One store, Gmail API, friendlier errors

### New
- **Gmail API mail driver** (`MAIL_DRIVER=gmail`): real emails from the store's Gmail account, landing in the inbox.
  One-time sign-in with `php yogi gmail:auth` (saves the token to `.env`). Clear messages when the sign-in expires.
- **Single-store mode** (default, `ALLOW_SELLER_SIGNUP=false`): sign-up makes shopper accounts only; "Sell on
  Yogitech", "Open a store" and "I want to sell" are hidden. Demo data has one store, Yogitech Official.
- **Repair database**: `php yogi db:repair` and a **Repair database now** button on the error page create missing
  tables/columns without touching data (local + `APP_DEBUG=true` only).

### Fixes
- Error 500 on product pages for the demo buyer: caused by a half-finished import (missing `wishlists` table).
  The error page now names the missing table and how to fix it; MySQL being off gets its own message.
- Sign-up and reset forms check required fields, the terms box and matching passwords before sending, so typed
  passwords aren't wiped. Error messages disappear as soon as the field is fixed.
- Side-by-side fields (Password / Confirm password) stay aligned when one shows an error.
- "Import from API" renamed to **Import products** in the seller center.


## 1.1.0 — QA fixes + forgot password

### New
- **Mailtrap Email Sandbox driver** (`MAIL_DRIVER=mailtrap`): emails go to a test inbox online, never to real people.
- **Forgot password**: emailed one-time reset link (60 min), security email after reset or change,
  every other device signed out. Tables: `password_resets`, `users.auth_version` (migration 009).
- **Voucher limits**: total and per-buyer limits, tracked per checkout in `voucher_redemptions` (migration 010).
- **Email change notice** sent to the old address.
- Pagination for buyer orders, seller orders and seller products.

### Security
- Array input (`?q[]=x`) no longer crashes pages with a 500 and stack trace.
- Changing the account email requires the current password.
- One password rule everywhere (8+ chars, letter + number); new password must differ from the current one.
- Email links never use a forged `Host:` header (`APP_TRUSTED_HOSTS`).
- Public tracking: no recipient full names (migration 011 cleans old rows); order numbers only work for the
  owner; `/track` and `/api/*` are rate-limited.
- Card number, CVC and passwords are never flashed back into forms after a failed submit.
- Logout issues a new CSRF token. `APP_DEBUG=false` in `.env.example`.

### Bug fixes
- Order status changes lock the order row: buyer and seller clicking at once can't restock twice.
- Buyers can't cancel part of a voucher checkout if the rest falls under the voucher's minimum spend.
  Cancelling a whole checkout gives the voucher use back.
- Checkout refuses to charge a total different from the one shown (price, shipping or voucher changed).
  An invalid voucher now stops the order instead of being silently dropped.
- Double-clicking "Place order" lands on the success page instead of "Your cart is empty".
- Store name field showed for shoppers (`.field { display: grid }` overrode `hidden`). Also fixed the
  seller order-status extra fields.
- Router supports quantifiers in route constraints, e.g. `{token:[a-f0-9]{64}}`.
- Cart quantity can't be set on an out-of-stock item.
- Cards are Luhn-checked; expired dates are rejected.
- Buyer-confirmed COD orders say "confirmed by buyer" instead of "collected from the recipient".
- Deleting a product with open orders is blocked (use Draft). Replaced or deleted uploaded images are
  removed, unless past orders still show them.
- Search treats `%` and `_` literally. Reviewer names are masked like on the tracking page.
- Emails are sent after the page is returned, so checkout doesn't wait on SMTP.

### Tests
- 30 unit tests (8 new in `tests/SecurityTest.php`) — `php yogi test`.
