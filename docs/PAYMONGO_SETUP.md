# PayMongo Billing Setup

This project uses [PayMongo](https://paymongo.com) for subscription billing (cards, GCash, Maya, GrabPay).

## 1. Get API keys

1. Sign in to the [PayMongo Dashboard](https://dashboard.paymongo.com).
2. Toggle to **Test mode** for development, **Live mode** for production.
3. Go to **Developers → API keys** and copy:
   - `sk_test_...` or `sk_live_...` → `PAYMONGO_SECRET_KEY` (server-side only)
    - `pk_test_...` or `pk_live_...` → `PAYMONGO_PUBLIC_KEY` (optional, only for client-side flows)

## 2. Activate payment methods (live mode)

In **test mode** every supported method (`card`, `gcash`, `paymaya`, `grab_pay`) works immediately — no documents required.

In **live mode**, each payment method is a separate *capability* you request from PayMongo and they must approve. Until a method is `Active`, live checkouts requesting it can fail (see [Troubleshooting](#10-troubleshooting-could-not-start-checkout)).

### Prerequisites before you can request a method

The "Request Activation" button on a payment method only appears once your account has:

| Prerequisite            | Where to complete it                                                                                                                                                             |
| ----------------------- | -------------------------------------------------------------------------------------------------------------------------------------------------------------------------------- |
| Enabled wallet          | **Money Movement > Wallets → "Click here to enable"**. Enables immediately if the account owner passed the KYC liveness check at signup; otherwise under review up to 14 business days |
| Business information    | **Settings > Business Information**                                                                                                                                               |
| Registered business type | **Individual** accounts are limited (see matrix below). **GCash and cards require a registered business type** (sole proprietorship / partnership / corporation / OPC) — upgrade once under **Settings > Business Information** and it unlocks every method the new type is eligible for |
| Identity verification   | Completed KYC / liveness by the account owner. New activation requests are **paused while identity verification is under manual review**                                           |

### How to request a method

1. Switch the dashboard to **Live mode** and open **Settings > Payment Methods** (page is live-mode only; only the account owner can request).
2. Find the method → **Request Activation** → confirm.
3. Track the status badge on the same page: `Inactive → Submitted → Under Review → Active`. If it shows **Action Required**, click **View details & Resubmit** — it lists exactly which fields/documents to fix (you won't re-upload the whole KYB).
4. If a method needs manual approval or was **Declined**, email <support@paymongo.com>.

### Eligibility by business type

Per PayMongo's current payment-method eligibility matrix:

| Method                             | Individual (no website) | Individual (with website) | Sole Prop / Partnership / Corp / OPC |
| ---------------------------------- | ----------------------- | ------------------------- | ------------------------------------ |
| QR Ph (default, pre-enabled)       | ✅                       | ✅                         | ✅                                    |
| E-wallets: Maya, GrabPay, ShopeePay | ❌                      | ✅                         | ✅                                    |
| GCash                              | ❌                       | ❌                         | ✅                                    |
| Visa & Mastercard, Google Pay      | ❌                       | ❌                         | ✅                                    |

E-wallet and card configuration is additionally subject to PayMongo's payment partners' approval.

### Processing times

| Method                           | Estimated time      |
| -------------------------------- | ------------------- |
| GCash / GrabPay / Maya / ShopeePay | 3–7 business days   |
| Visa & Mastercard                | 5–10 business days  |

Start activation **before** you plan to switch to live keys — a fresh account can go through wallet + business-type upgrade + method requests in sequence, each adding days.

### Check what is actually enabled (API)

```
GET /v1/merchants/capabilities/payment_methods
```

Authenticated with your secret key. Returns only the currently-enabled identifiers, e.g. `["qrph", "gcash"]`. Anything missing from the response cannot be used in a live checkout session yet.

### Note on recurring billing

PayMongo's *Subscriptions* product (built-in recurring charges) is a separate capability that must be requested from <support@paymongo.com> and only supports Cards and Maya. BarangayBooks does **not** use it — every monthly renewal is a fresh Hosted Checkout session — so payment-method activation is the only live-mode prerequisite.

### Using Storefront / Payment Links while methods are pending activation

While your account is Individual, under verification, or waiting on method approvals, plan payments still work end-to-end via PayMongo **Storefront / Payment Links** instead of the API-hosted checkout:

- Storefront checkouts surface whatever methods the account currently has enabled — **QR Ph is enabled on every account by default** — and, being dashboard-managed, never send a `payment_method_types` list, so nothing gets rejected at session creation.
- Pricing on the storefront link **must match** the plan amounts in `config/services.php` (`plan_amounts`: ₱199 / ₱499 / ₱999). The verification flow matches paid amounts to plans by these values.
- The storefront URL is linked from the tenant billing page via `PAYMONGO_STOREFRONT_URL` (default `https://storefront.paymongo.com/barangay-books-subscription`).

**Trade-off:** storefront payments never auto-activate a plan. There is no local `payments` row for them, so the `payment.paid` webhook cannot match anything. Activation is manual: the org admin opens **Settings → Billing → "Verify a payment"** (throttled 5/min, admin-only, requires typing the org name) and pastes the PayMongo payment id (`pay_...`) or reference number. `BillingPaymentVerifyController` then:

1. Fetches the payment via `PayMongoClient::retrievePayment()` (by id, then reference-number scan of recent payments).
2. Validates: `status === 'paid'`, **`livemode === true`**, currency PHP, amount exactly equal to a plan price (or the selected plan's price).
3. Creates the `payments` row idempotently (`firstOrCreate` by `paymongo_payment_id`, refuses payments already tied to another org) and activates the subscription via `BillingService::activateForOrganization()`.

Caveats:

- The `livemode === true` check is deliberate — **test-mode storefront payments cannot be verified**. Rehearse the flow against live keys only (use a refundable small payment), or rely on `tests/Feature/BillingTest.php` which fakes the PayMongo client.
- Renewals stay manual for as long as you use this path — each billing period the storefront link must be paid and re-verified. Once the API methods (`card`/`gcash`/`paymaya`/`grab_pay`) are activated, switch back to the hosted-checkout button in the billing page and activation returns to being webhook-driven.

## 3. Create a webhook endpoint

1. In the dashboard, go to **Developers → Webhooks**.
2. Click **Create a webhook**.
3. Set **URL** to `https://your-domain.tld/billing/webhook`.
   - Must be HTTPS — PayMongo refuses to deliver to plaintext HTTP in live mode.
4. Subscribe to events. We currently handle `payment.paid`.
5. Copy the **Secret** → `PAYMONGO_WEBHOOK_SECRET`.

If you ever rotate the secret, update `PAYMONGO_WEBHOOK_SECRET` on the host before the dashboard invalidates the old one — otherwise deliveries will 401.

## 4. Environment variables

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

## 5. How signature verification works

The webhook handler at `POST /billing/webhook` is wrapped by `App\Http\Middleware\VerifyPayMongoSignature` (`app/Http/Middleware/VerifyPayMongoSignature.php`).

PayMongo signs every request body with HMAC-SHA256 using your endpoint secret and ships the hex digest in the `Paymongo-Signature` header. The middleware:

1. Reads the **raw** request body via `$request->getContent()` (no parsing first).
2. Computes `hash_hmac('sha256', $rawBody, $secret)`.
3. Compares with `hash_equals()` (timing-safe).
4. Returns `401` on mismatch, `500` if the secret is unset.

The route is **excluded from CSRF** in `bootstrap/app.php` via `validateCsrfTokens(except: ['billing/webhook'])`.

## 6. Local testing

Use `sk_test_*` keys plus PayMongo's [test card numbers](https://docs.paymongo.com/docs/payment-acceptance-testing). Toggle the dashboard to **Test mode** so the webhook endpoint receives test events.

If you develop locally with `php artisan serve`, expose it via ngrok or similar so PayMongo can reach your webhook URL. Alternatively, point the webhook URL at your staging deployment.

## 7. Plan catalog

Default plans are seeded by `database/seeders/DatabaseSeeder.php`:

| Plan       | Price (PHP) | Households | CSV import | API access |
|------------|-------------|------------|------------|------------|
| Community  | ₱199/mo     | 50         | yes        | no         |
| Barangay   | ₱499/mo     | 250        | yes        | no         |
| City       | ₱999/mo     | unlimited  | yes        | yes        |

Edit prices in `database/seeders/DatabaseSeeder.php` or via tinker — never mutate price_cents on existing `subscriptions` rows retroactively.

## 8. Plan-gated middleware

`App\Http\Middleware\EnsurePlanAllowsHousehold` blocks household creation once the tenant reaches `plan.max_households`. Apply it to any route that creates households, e.g.:

```php
Route::middleware(EnsurePlanAllowsHousehold::class)->group(function () {
    Route::resource('households', HouseholdController::class)->except(['index']);
});
```

## 9. Idempotency

`BillingService::handlePaymentPaid()` looks up the existing `payments` row by `paymongo_payment_id` first, then by `paymongo_payment_intent_id` (persisted in `startCheckout()`), with a metadata `organization_id`/`plan_id` fallback. Replayed webhooks are safe — the subscription is `updateOrCreate`'d and the payment row's `status` is set to `paid`.

## 10. Troubleshooting "Could not start checkout"

`BillingController::checkout` catches any exception from PayMongo and shows the generic *"Could not start checkout. Please try again."* flash. The real cause is logged to host logs (Railway: Deployments → Logs) as `PayMongo checkout failed` / `PayMongo request failed: <status> <body>`.

| Cause | Log signature | Fix |
| ----- | ------------- | --- |
| Missing CA bundle (Windows local dev only) | `cURL error 60: ... unable to get local issuer certificate` | Set `curl.cainfo` + `openssl.cafile` in php.ini to a downloaded `cacert.pem` (e.g. `C:\php\8.2\cacert.pem`) |
| Migration not applied (prod) | SQL error: column `paymongo_payment_intent_id` of relation `payments` does not exist | Rerun the release command `php artisan migrate --force`, or trigger a redeploy |
| Payment method not activated (live mode) | PayMongo 4xx body referencing method not enabled / account verification | Complete KYC + wallet enablement + method activation (see section 2), or trim the `payment_method_types` list in `BillingService::startCheckout()` |
| Key/mode mismatch | `401` from PayMongo | `sk_test_*/pk_test_*` pairs must be used with the dashboard's Test toggle; `sk_live_*` only in live |
| Webhook secret rotated | Webhook deliveries 401, payments stay `pending` | Update `PAYMONGO_WEBHOOK_SECRET` and redeploy |

After fixing a method/verification issue, the `billing:reconcile-pending` command (scheduled every 15 min) re-checks stuck `pending` payments and activates the org once PayMongo reports them paid.