# Observability (Sentry)

Errors and performance traces are reported to Sentry via the official [`sentry/sentry-laravel`](https://github.com/getsentry/sentry-laravel) package. The integration is **opt-in** — set `SENTRY_LARAVEL_DSN` and you're done.

## 1. Create a Sentry project

1. Sign in to [sentry.io](https://sentry.io) (free tier covers most SaaS).
2. Create a new project. Pick **Laravel** as the platform.
3. Copy the DSN from **Settings → Projects → Client Keys (DSN)**.
4. Set `SENTRY_LARAVEL_DSN=https://...@sentry.io/...` on your host.

## 2. Environment variables

```
SENTRY_LARAVEL_DSN=                          # required to enable
SENTRY_RELEASE=                              # optional: git SHA / deploy tag
SENTRY_ERRORS_SAMPLE_RATE=1.0                # 1.0 = report every error
SENTRY_TRACES_SAMPLE_RATE=0.2                # 20% of performance transactions
SENTRY_PROFILES_SAMPLE_RATE=0.2              # 20% profiling within traced txns
```

`config:cache` bakes env into a cached file, so changed values require a redeploy.

`SENTRY_RELEASE` is what groups errors by deploy. Wire it from your deploy pipeline — e.g. on Railway use `${{ Railway.GIT_COMMIT_SHA }}`, on Render use `$COMMIT_SHA`. Without it, errors don't show which release introduced them.

## 3. What gets reported

### Errors (100% sample by default)

- Uncaught exceptions from HTTP requests
- Failed queue jobs (`ShouldQueue` listeners/mailables)
- Console command failures

Ignored automatically:
- `AuthenticationException`, `ValidationException`, `HttpException` (4xx noise)
- `/up` healthcheck
- `/billing/webhook` (PayMongo already has its own observability)

### Performance traces (20% sample by default)

- HTTP request spans with route name, method, status code
- DB queries under each span
- View renders, queue dispatches

Profiles are sampled at 20% of traced transactions.

## 4. PII scrubbing

The SDK is configured with `send_default_pii=false`, but we go further with `App\Support\SentryPiiScrubber` (`app/Support/SentryPiiScrubber.php`) registered as a Sentry event processor via `App\Providers\SentryServiceProvider`.

The scrubber redacts:

| Field fragment | Examples |
|----------------|----------|
| `password`     | `password`, `password_confirmation`, `current_password`, `new_password` |
| `secret`       | any field containing `secret` |
| `api_key`, `apikey` | `api_key`, `apiKey`, `apikey` |
| `token`        | `access_token`, `refresh_token`, `remember_token`, `_token`, `csrf` |
| `paymongo_`    | `PAYMONGO_SECRET_KEY`, `PAYMONGO_WEBHOOK_SECRET`, `PAYMONGO_PUBLIC_KEY` |
| `aws_secret`, `aws_access_key` | `AWS_SECRET_ACCESS_KEY`, `AWS_ACCESS_KEY_ID` |
| `mail_password` | SMTP password |
| `card_*`, `cvv`, `ssn` | payment data |
| `bearer` | `Authorization: Bearer ...` |

It also redacts card-shaped numbers (13-19 digit runs) anywhere in string values.

The scrubber recursively walks `extra`, breadcrumbs, request data, and user context. It runs before every event is sent to Sentry.

## 5. User context

By default we set `username = null`, `email = null`, `ip_address = null`, and only keep `id`. If you want richer per-user debugging, override the user scrubber logic in `SentryPiiScrubber::scrubUser()` — but be aware of GDPR / privacy considerations.

## 6. Local development

Sentry is disabled when `SENTRY_LARAVEL_DSN` is empty. Errors fall through to Laravel's default reporter and end up in `storage/logs/laravel.log`. Set the DSN to a Sentry dev project if you want to test the integration locally.

## 7. Alerting

Configure alerts in the Sentry dashboard under **Alerts → Create Alert**. Suggested starter alerts:

- New error type: notify within 5 minutes
- Spike: >10 events in 5 minutes
- Performance regression: p95 latency > 2s for `/{org}/dashboard`

## 8. Source maps / release tracking

For frontend errors, configure Vite to upload source maps via `@sentry/vite-plugin`. Not currently in this project — Alpine.js doesn't generate meaningful source maps.

## 9. Cost control

- Drop `SENTRY_TRACES_SAMPLE_RATE` to 0.05 (5%) on high-traffic days.
- Set up **Inbound Filters** in Sentry to drop healthcheck noise from load balancers.
- Use **Rate Limiting** under Project Settings → Performance to cap traces per minute.

## 10. Tests

- `tests/Unit/Support/SentryPiiScrubberTest.php` — exercises the scrubbing rules via reflection (doesn't require the Sentry SDK installed).
- `tests/Feature/SentryServiceProviderTest.php` — verifies the provider registers the scrubber as a singleton and is safe to boot when the SDK is absent or DSN is unset.

Run with:

```bash
php artisan test --filter=SentryPiiScrubberTest
php artisan test --filter=SentryServiceProviderTest
```