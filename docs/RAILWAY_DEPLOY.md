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

# --- Mail (Mailtrap or real SMTP) ---
MAIL_MAILER=smtp
MAIL_HOST=sandbox.smtp.mailtrap.io
MAIL_PORT=2525
MAIL_USERNAME=...
MAIL_PASSWORD=...
MAIL_FROM_ADDRESS="hello@example.com"
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

## ⚠️ Two issues to address before relying on B2

### 1. B2 key is "not entitled" (must fix in Backblaze)

Connectivity test against the current `.env` creds: the adapter reaches B2, signs correctly, and
the endpoint resolves — but **every read and write returns `AccessDenied: not entitled`**.

That is a Backblaze key-permission problem, not a code problem. Fix in the B2 dashboard:

- The Application Key must have capabilities `listFiles`, `readFiles`, `writeFiles`, `deleteFiles`.
- If the key is **restricted to a specific bucket**, that bucket must be exactly
  `householdsaas-bucket` (a key scoped to a different bucket gives "not entitled").
- Confirm the bucket name and region (`us-east-005`) match the key's bucket.
- Easiest path: create a fresh Application Key scoped to `householdsaas-bucket` with all file
  capabilities, then update `AWS_ACCESS_KEY_ID` / `AWS_SECRET_ACCESS_KEY`.

Also required: `league/flysystem-aws-s3-v3` (now added to `composer.json`). Without it the S3 disk
throws `Class "League\Flysystem\AwsS3V3\PortableVisibilityConverter" not found`.

### 2. Receipt photos now upload to B2 (RESOLVED)

`ContributionController::store` writes uploads to the **`s3`** disk:

```php
$photoPath = $request->file('photo')->store('contributions', 's3');
```

Display + email now read from B2 via the `Contribution::photo_url` accessor
(`Storage::disk('s3')->url($photo)`) instead of the old local `asset('storage/...')`.
Verified end-to-end against the live bucket (store → exists → url → delete).

Requirements for this to keep working on Railway:

- The B2 bucket must be **Public** (the app stores receipts with `public-read` so the
  `asset`-style public URL works). `config/filesystems.php` sets `'visibility' => 'public'`
  on the `s3` disk for this reason — a Private bucket rejects it.
- All `AWS_*` env vars set (see above). No Railway volume / `storage:link` needed for receipts.

---

## Post-deploy checklist

- [ ] Build logs show `npm run build` running and writing `public/build/assets/app-*.js`
- [ ] Avatar dropdown opens (Alpine loaded) — hard refresh to bypass cache
- [ ] `php artisan migrate --force` ran (check session/cache tables exist)
- [ ] B2 key fixed and a test upload round-trips (see issue #1)
- [ ] Receipt photo storage decided (B2 disk vs. volume — issue #2)
- [ ] `APP_DEBUG=false`, `APP_ENV=production`
