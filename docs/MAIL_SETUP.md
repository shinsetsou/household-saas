# Mail Setup

Mail is sent by Laravel for:

- Email verification on registration (`Registered` event → `SendEmailVerificationNotification`)
- Member invitations (`OrganizationInvitationSent` event → `SendOrganizationInvitationEmail`)
- Trial-ending warnings + expiry notices (`ExpireTrials` command → mail)
- PayMongo receipt forwarding (out of scope of this codebase — sent by PayMongo directly)

The app degrades gracefully: mail failures are logged but never break user flows (`SendEmailVerificationNotification`, `SendOrganizationInvitationEmail`, `SendContributionAddedEmail` all catch `TransportException`).

## 1. Local dev (default)

`MAIL_MAILER=log` — emails are written to `storage/logs/laravel.log`. No external service needed.

```bash
MAIL_MAILER=log
tail -f storage/logs/laravel.log
```

## 2. Production

Pick a real provider. The codebase supports:

- **`mailtrap`** — Mailtrap transactional Email API (HTTP). Default recommendation.
- **`smtp`** — generic SMTP. Use Postmark / Mailgun / SES / SendGrid / etc.

### ⚠️ Mailtrap sandbox SMTP does NOT work from production

Mailtrap exposes two distinct products that look similar but are different:

1. **Sandbox** (`sandbox.smtp.mailtrap.io`) — free, dev-only. IP-allowlisted to prevent abuse. **Unreachable from cloud provider IPs** (Railway, Render, AWS, GCP, Azure, Fly). Connections hang and PHP times out at 30s with `Maximum execution time exceeded` inside Symfony's SMTP transport.
2. **Email Sending / Transactional** (paid tiers) — designed for production. HTTP-based via the Mailtrap Email API. Works from anywhere.

If you see this in `storage/logs/laravel.log`:

```
Symfony\Component\Mailer\Exception\TransportException:
Connection could not be established with host "sandbox.smtp.mailtrap.io"
```

…you are using sandbox credentials in production. Switch to the Email API (Option A) or a different provider.

The app also **warns at boot** when it detects this misconfiguration in production — search `storage/logs/laravel.log` for `mail.driver.sandbox_in_production`.

### Option A — Mailtrap Email API (HTTP) — recommended

Use the Mailtrap transactional Email API. Works from any cloud IP because it speaks HTTPS, not SMTP.

This project uses the official **`railsware/mailtrap-php`** Laravel bridge (already in `composer.json` + `composer.lock`). Its service provider auto-registers the `mailtrap` mailer and reads three env vars.

In production env, set:

```bash
MAIL_MAILER=mailtrap
MAILTRAP_HOST=send.api.mailtrap.io          # or sandbox.api.mailtrap.io / bulk.api.mailtrap.io
MAILTRAP_API_KEY=<from Mailtrap dashboard → Email Sending → API tokens>
# MAILTRAP_INBOX_ID=...                     # only for sandbox host
MAIL_FROM_ADDRESS="hello@your-domain.tld"
MAIL_FROM_NAME="BarangayBooks"
```

Host values:

- `send.api.mailtrap.io` — transactional (default for production)
- `sandbox.api.mailtrap.io` — sandbox inbox (dev only; `MAILTRAP_INBOX_ID` required)
- `bulk.api.mailtrap.io` — bulk stream

Get a token from Mailtrap dashboard → **Email Sending** → **API tokens**. The `From` domain must be a domain you've installed in Mailtrap (otherwise Mailtrap returns 401).

### Option B — Postmark / SES / Mailgun / SendGrid

Standard SMTP. Set:

```bash
MAIL_MAILER=smtp
MAIL_HOST=<provider's SMTP host>
MAIL_PORT=587
MAIL_USERNAME=<username or api-key>
MAIL_PASSWORD=<api-key or password>
MAIL_ENCRYPTION=tls
```

## 3. Verifying it works

Send a test registration:

```bash
php artisan tinker
> $user = User::factory()->create(['email' => 'you@example.com']);
> event(new \Illuminate\Auth\Events\Registered($user));
```

Watch `storage/logs/laravel.log` (if `MAIL_MAILER=log`) or check the inbox (if real provider).

## 4. Production hardening already in place

- All mail listeners catch `TransportException` and log via `Log::error`. User flows (registration, invitation accept, contribution create) never 500 on mail failure.
- Mailable class is `ShouldQueue` so mail is dispatched to the queue rather than blocking the HTTP request. With `QUEUE_CONNECTION=database` and a real scheduler-driven worker, email sends happen async. Without a worker, queued mail sits in the `jobs` table — it WILL still go out once you wire a worker (`php artisan queue:work`).
