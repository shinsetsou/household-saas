# Railway Deployment Guide

Laravel 11 + Vite (Alpine) + MySQL. This guide fixes the **avatar dropdown not working** issue
and lists everything Railway needs before deploy.

---

## Why the avatar dropdown breaks on Railway

The dropdown (`resources/views/layouts/layout.blade.php`) uses **Alpine.js** (`x-data`, `x-show`).
Alpine is **not** loaded from a CDN — it ships inside the Vite bundle:

```js
// resources/js/app.js
import Alpine from 'alpinejs';
window.Alpine = Alpine;
Alpine.start();
```

The layout pulls it in with `@vite(['resources/js/app.js'])`. That directive needs the built
manifest at `public/build/manifest.json`. If `npm run build` never runs during deploy:

- no `public/build/` assets exist,
- `@vite` falls back to dev mode (or 500s),
- **Alpine never loads → dropdown is dead.**

**Fix: run `npm install && npm run build` in the Railway build step.** (See build command below.)

> Note: `public/hot` is gitignored. If it ever lands in the image, `@vite` thinks a dev server
> is running and emits `/js/app.js` — also breaks the dropdown. Keep it out of the deploy.

---

## Build & start commands

Tailwind is loaded via CDN (`<script src="https://cdn.tailwindcss.com">`), so only the JS bundle
needs building.

### Build command

```bash
composer install --no-dev --optimize-autoloader --no-interaction \
  && npm ci \
  && npm run build \
  && php artisan config:cache \
  && php artisan route:cache \
  && php artisan view:cache
```

### Release/pre-start command (run migrations once per deploy)

```bash
php artisan migrate --force
```

### Start command

```bash
php artisan serve --host 0.0.0.0 --port $PORT
```

> `php artisan serve` is fine for low traffic. For real production use a proper SAPI
> (nginx + php-fpm via a custom Dockerfile, or the `nixpacks` PHP provider with apache).

### Nixpacks config (optional, pins the steps)

Create `nixpacks.toml` at repo root so Railway always builds JS:

```toml
[phases.setup]
nixPkgs = ["php82", "nodejs_20", "...composer"]

[phases.install]
cmds = ["composer install --no-dev --optimize-autoloader --no-interaction", "npm ci"]

[phases.build]
cmds = ["npm run build", "php artisan config:cache", "php artisan route:cache", "php artisan view:cache"]

[start]
cmd = "php artisan migrate --force && php artisan serve --host 0.0.0.0 --port $PORT"
```

---

## Environment variables (Railway → Variables)

```env
APP_NAME="Household Contributions"
APP_ENV=production
APP_KEY=base64:...                  # php artisan key:generate --show
APP_DEBUG=false
APP_URL=https://<your-app>.up.railway.app
APP_TIMEZONE=UTC

LOG_CHANNEL=stack
LOG_LEVEL=error

# --- Database (Railway MySQL plugin injects these; map or reference them) ---
DB_CONNECTION=mysql
DB_HOST=${{MySQL.MYSQLHOST}}
DB_PORT=${{MySQL.MYSQLPORT}}
DB_DATABASE=${{MySQL.MYSQLDATABASE}}
DB_USERNAME=${{MySQL.MYSQLUSER}}
DB_PASSWORD=${{MySQL.MYSQLPASSWORD}}

# These drivers use the DB → migrations must run (they do, via release cmd)
SESSION_DRIVER=database
CACHE_STORE=database
QUEUE_CONNECTION=database

# --- Mail (Mailtrap Email API — recommended; works from cloud IPs) ---
# Sandbox SMTP (sandbox.smtp.mailtrap.io) is dev-only and unreachable from Railway.
# See docs/MAIL_SETUP.md §"Sandbox vs Email API" for details.
# 1. composer require mailtrap/mailtrap-laravel   (one-time, locally)
# 2. In Railway Variables set:
MAIL_MAILER=mailtrap
MAILTRAP_API_TOKEN=<from Mailtrap dashboard → Email Sending → API tokens>
MAILTRAP_INBOX_ID=<default inbox id>
MAIL_FROM_ADDRESS="hello@your-domain.tld"
MAIL_FROM_NAME="${APP_NAME}"

# --- Storage / Backblaze B2 (S3-compatible) ---
FILESYSTEM_DISK=s3
AWS_ACCESS_KEY_ID=...
AWS_SECRET_ACCESS_KEY=...
AWS_DEFAULT_REGION=us-east-005
AWS_BUCKET=householdsaas-bucket
AWS_ENDPOINT=https://s3.us-east-005.backblazeb2.com
AWS_USE_PATH_STYLE_ENDPOINT=true
AWS_ENABLE_THROW=true

VITE_APP_NAME="${APP_NAME}"
```

> **Order matters:** set all env vars **before** the first deploy. `config:cache` bakes env into
> a cached file — if you change vars later, redeploy or run `php artisan config:clear && config:cache`.

---

## Receipt photo storage (Backblaze B2 via S3)

`ContributionController::store` writes uploads to the **`s3`** disk:

```php
$photoPath = $request->file('photo')->store('contributions', 's3');
```

Display + email read from B2 via the `Contribution::photo_url` accessor
(`Storage::disk('s3')->url($photo)`). On DB rollback the controller deletes the just-uploaded
file from B2 so a failed submit doesn't leak orphans on the bucket.

Requirements:

- `league/flysystem-aws-s3-v3` is in `composer.json` (required for the `s3` disk).
- The B2 bucket must be **Public** — `config/filesystems.php` sets `'visibility' => 'public'`
  on the `s3` disk so receipts are uploaded with `public-read`. A Private bucket rejects it.
- All `AWS_*` env vars set (see top of doc). No Railway volume / `storage:link` needed for receipts.
- `AWS_ENABLE_THROW=true` so S3 errors surface during diagnosis instead of silently returning null.

### ⚠️ "not entitled" — fix in Backblaze, not code

Symptom: the adapter reaches B2, signs correctly, the endpoint resolves — but **every read and
write returns `AccessDenied: not entitled`**. This is always a Backblaze key-permission problem.

**Step-by-step fix in the B2 dashboard:**

1. B2 Cloud Storage → **App Keys** → **Create a New Application Key** (don't reuse an existing one;
   the rotation is the whole point).
2. **Name:** `barangaybooks-prod` (or anything).
3. **Capabilities:** check **all four** — `listFiles`, `readFiles`, `writeFiles`, `deleteFiles`.
   Missing any one of these is the most common cause of "not entitled".
4. **Bucket Access:** either **All** or restricted to exactly `householdsaas-bucket`. **The bucket
   name must match `AWS_BUCKET` byte-for-byte** (including case). A key scoped to a different
   bucket gives "not entitled" even when capabilities are correct.
5. **Region / endpoint:** confirm the bucket's region matches `AWS_ENDPOINT` —
   `https://s3.us-east-005.backblazeb2.com` for a `us-east-005` bucket. Wrong region returns
   "not entitled" or DNS failures.
6. Copy the new `keyID` → `AWS_ACCESS_KEY_ID`, `applicationKey` → `AWS_SECRET_ACCESS_KEY` in
   Railway Variables. **Redeploy** so `config:cache` re-bakes the env.

### Verify the roundtrip from the Railway shell

Run this in Railway's one-off shell (or locally with the production env) to confirm the key +
endpoint + bucket are all aligned before the first real upload:

```bash
php artisan tinker
```

```php
$disk = Storage::disk('s3');

// write
$disk->put('healthcheck.txt', 'ok-'.now()->toIso8601String());

// read back
$disk->get('healthcheck.txt');                       // => "ok-2026-..."

// public URL (should resolve in browser)
$disk->url('healthcheck.txt');

// clean up
$disk->delete('healthcheck.txt');
$disk->exists('healthcheck.txt');                    // => false
```

If `put` throws `AccessDenied: not entitled`, the key capabilities or bucket scope are wrong
(re-check steps 3 + 4 above). If it throws a DNS or signature error, the endpoint/region don't
match (re-check step 5). Once the four-step roundtrip passes, real uploads will work.

---

## Post-deploy checklist

- [ ] Build logs show `npm run build` running and writing `public/build/assets/app-*.js`
- [ ] Avatar dropdown opens (Alpine loaded) — hard refresh to bypass cache
- [ ] `php artisan migrate --force` ran (check session/cache tables exist)
- [ ] B2 key roundtrip (write → read → url → delete) passes (see above)
- [ ] Mail works: trigger an invite or trial-expiry email and confirm it lands in Mailtrap (or check `storage/logs/laravel.log` if you don't have a real driver). The app warns at boot if `MAIL_MAILER=smtp` points at `sandbox.smtp.mailtrap.io` — search logs for `"sandbox.smtp.mailtrap.io"`.
- [ ] `APP_DEBUG=false`, `APP_ENV=production`
