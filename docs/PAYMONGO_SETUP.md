# PayMongo Billing Setup

This project uses [PayMongo](https://paymongo.com) for subscription billing (cards, GCash, Maya, GrabPay).

## 1. Get API keys

1. Sign in to the [PayMongo Dashboard](https://dashboard.paymongo.com).
2. Toggle to **Test mode** for development, **Live mode** for production.
3. Go to **Developers → API keys** and copy:
   - `sk_test_...` or `sk_live_...` → `PAYMONGO_SECRET_KEY` (server-side only)
   - `pk_test_...` or `pk_live_...` → `PAYMONGO_PUBLIC_KEY` (optional, only for client-side flows)

## 2. Create a webhook endpoint

1. In the dashboard, go to **Developers → Webhooks**.
2. Click **Create a webhook**.
3. Set **URL** to `https://your-domain.tld/billing/webhook`.
   - Must be HTTPS — PayMongo refuses to deliver to plaintext HTTP in live mode.
4. Subscribe to events. We currently handle `payment.paid`.
5. Copy the **Secret** → `PAYMONGO_WEBHOOK_SECRET`.

If you ever rotate the secret, update `PAYMONGO_WEBHOOK_SECRET` on the host before the dashboard invalidates the old one — otherwise deliveries will 401.

## 3. Environment variables

Set on Railway / Render / Heroku before the first deploy:

```
PAYMONGO_SECRET_KEY=sk_test_...
PAYMONGO_PUBLIC_KEY=pk_test_...
PAYMONGO_WEBHOOK_SECRET=whsec_...
PAYMONGO_MODE=test                # or "live"
PAYMONGO_BASE_URL=https://api.paymongo.com/v1
PAYMONGO_SUCCESS_URL=https://your-domain.tld/billing/return/success
PAYMONGO_CANCEL_URL=https://your-domain.tld/billing/return/cancel
PAYMONGO_TRIAL_DAYS=14
```

`config:cache` bakes env into a cached file, so changed vars require a redeploy.

## 4. How signature verification works

The webhook handler at `POST /billing/webhook` is wrapped by `App\Http\Middleware\VerifyPayMongoSignature` (`app/Http/Middleware/VerifyPayMongoSignature.php`).

PayMongo signs every request body with HMAC-SHA256 using your endpoint secret and ships the hex digest in the `Paymongo-Signature` header. The middleware:

1. Reads the **raw** request body via `$request->getContent()` (no parsing first).
2. Computes `hash_hmac('sha256', $rawBody, $secret)`.
3. Compares with `hash_equals()` (timing-safe).
4. Returns `401` on mismatch, `500` if the secret is unset.

The route is **excluded from CSRF** in `bootstrap/app.php` via `validateCsrfTokens(except: ['billing/webhook'])`.

## 5. Local testing

Use `sk_test_*` keys plus PayMongo's [test card numbers](https://docs.paymongo.com/docs/payment-acceptance-testing). Toggle the dashboard to **Test mode** so the webhook endpoint receives test events.

If you develop locally with `php artisan serve`, expose it via ngrok or similar so PayMongo can reach your webhook URL. Alternatively, point the webhook URL at your staging deployment.

## 6. Plan catalog

Default plans are seeded by `database/seeders/DatabaseSeeder.php`:

| Plan       | Price (PHP) | Households | CSV import | API access |
|------------|-------------|------------|------------|------------|
| Community  | ₱199/mo     | 50         | yes        | no         |
| Barangay   | ₱499/mo     | 250        | yes        | no         |
| City       | ₱999/mo     | unlimited  | yes        | yes        |

Edit prices in `database/seeders/DatabaseSeeder.php` or via tinker — never mutate price_cents on existing `subscriptions` rows retroactively.

## 7. Plan-gated middleware

`App\Http\Middleware\EnsurePlanAllowsHousehold` blocks household creation once the tenant reaches `plan.max_households`. Apply it to any route that creates households, e.g.:

```php
Route::middleware(EnsurePlanAllowsHousehold::class)->group(function () {
    Route::resource('households', HouseholdController::class)->except(['index']);
});
```

## 8. Idempotency

`BillingService::handlePaymentPaid()` looks up the existing `payments` row by `paymongo_payment_id` first, then by `paymongo_checkout_session_id`. Replayed webhooks are safe — the subscription is `updateOrCreate`'d and the payment row's `status` is set to `paid` again.