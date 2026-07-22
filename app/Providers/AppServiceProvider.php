<?php

namespace App\Providers;

use App\Models\Contribution;
use App\Models\Organization;
use App\Models\OrganizationInvitation;
use App\Models\Plan;
use App\Observers\OrganizationInvitationObserver;
use App\Services\Billing\BillingService;
use App\Services\PayMongo\PayMongoClient;
use App\Support\NavContext;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;
use Symfony\Component\Mailer\Bridge\Mailtrap\Transport\MailtrapTransportFactory;
use Symfony\Component\Mailer\Transport;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(PayMongoClient::class, fn () => PayMongoClient::make());
    }

    public function boot(): void
    {
        $this->registerMailtrapTransport();

        OrganizationInvitation::observe(OrganizationInvitationObserver::class);

        // Register the `emails` namespace so `<x-emails::layout>` resolves to
        // App\View\Components\Emails\Layout. This is belt-and-suspenders for
        // the anonymous component lookup at resources/views/components/emails/.
        Blade::componentNamespace('App\\View\\Components\\Emails', 'emails');

        if (! $this->shouldSeedPlans()) {
            return;
        }

        $this->ensurePlansExist();
        $this->warnOnMisconfiguredMail();

        View::composer('layouts.layout', function ($view): void {
            $org = $this->resolveOrganization($view);

            $user = auth()->user();

            $view->with('navContext', NavContext::build($org, $user));

            $pendingVerificationCount = 0;

            if ($org && $user && $user->belongsToOrganization($org)) {
                $pendingVerificationCount = Contribution::forOrganization($org)
                    ->where('verification_status', 'pending')
                    ->distinct()
                    ->count('reference_number');
            }

            $view->with('pendingVerificationCount', $pendingVerificationCount);
        });
    }

    /**
     * Skip plan seeding during artisan cache commands and migrations.
     * - `config:cache`/`route:cache`/`view:cache` fire during the build image
     *   on hosts like Railway where the DB host (`*.railway.internal`) isn't
     *   resolvable yet, and a `Schema::hasTable` call will hard-fail the build.
     * - `migrate` runs before the `plans` table exists, so `seedDefaultPlans`
     *   would just `updateOrCreate` against a missing table anyway.
     */
    protected function shouldSeedPlans(): bool
    {
        if (! app()->runningInConsole()) {
            return true;
        }

        $command = $_SERVER['argv'][1] ?? null;

        return ! in_array($command, [
            'config:cache',
            'config:clear',
            'route:cache',
            'route:clear',
            'view:cache',
            'view:clear',
            'event:cache',
            'event:clear',
            'optimize',
            'optimize:clear',
            'migrate',
            'migrate:fresh',
            'migrate:install',
            'migrate:rollback',
            'migrate:reset',
            'package:discover',
        ], true);
    }

    /**
     * The plans catalog is read from the DB by every billing surface (billing
     * page, signup onboarding, downgrade flow, webhook handler). A missing plan
     * row silently breaks upgrade UI — so we make sure all three exist on every
     * boot. updateOrCreate keeps this idempotent and runs in <5ms.
     */
    protected function ensurePlansExist(): void
    {
        try {
            if (! Schema::hasTable('plans')) {
                return;
            }

            app(BillingService::class)->ensurePlansExist();
        } catch (\Throwable $e) {
            // Boot-time seed must never break the request cycle. The next
            // request will retry.
            report($e);
        }
    }

    /**
     * Mailtrap's sandbox SMTP endpoint is IP-allowlisted and unreachable from
     * cloud hosts (Railway, Render, AWS, GCP, Azure, Fly). Using it in
     * production causes every email to hang until the 30s PHP timeout fires.
     * Catch the misconfiguration at boot and log it loudly so it's visible
     * in `storage/logs/laravel.log` instead of surfacing as user-facing
     * TransportException stack traces.
     *
     * Dev / local is exempted — sandbox SMTP works fine from a developer
     * machine and that's its intended use.
     */
    /**
     * Register a custom creator for the `mailtrap` mailer so Laravel hands
     * the DSN straight to Symfony's Mailer, which knows about the
     * `mailtrap+api` / `mailtrap+sandbox` / `mailtrap+smtp` schemes via
     * the symfony/mailtrap-mailer bridge. Without this, Laravel's
     * `createSymfonyTransport` looks for a `createMailtrapTransport` method
     * that doesn't exist and throws "Unsupported mail transport [mailtrap]".
     */
    protected function registerMailtrapTransport(): void
    {
        if (! class_exists(Transport::class)) {
            return;
        }

        Mail::extend('mailtrap', function (array $config) {
            $dsn = $config['dsn'] ?? null;
            if (! $dsn) {
                throw new \RuntimeException('mailtrap mailer requires MAIL_MAILTRAP_DSN (e.g. mailtrap+api://TOKEN@default). See docs/MAIL_SETUP.md.');
            }

            return Transport::fromDsn($dsn);
        });
    }

    protected function warnOnMisconfiguredMail(): void
    {
        if (! app()->environment('production')) {
            return;
        }

        $mailer = (string) config('mail.default');
        $host = (string) config('mail.mailers.smtp.host');

        if ($mailer === 'smtp' && $host === 'sandbox.smtp.mailtrap.io') {
            Log::error('mail.driver.sandbox_in_production', [
                'mailer' => $mailer,
                'host' => $host,
                'fix' => 'Switch to MAIL_MAILER=mailtrap with MAIL_MAILTRAP_DSN=mailtrap+api://TOKEN@default (Mailtrap Email API). See docs/MAIL_SETUP.md.',
            ]);
        }

        if ($mailer === 'mailtrap' && ! class_exists(MailtrapTransportFactory::class)) {
            Log::error('mail.driver.mailtrap_package_missing', [
                'mailer' => $mailer,
                'fix' => 'symfony/mailtrap-mailer is not installed. Run `composer require symfony/mailtrap-mailer` and redeploy. See docs/MAIL_SETUP.md.',
            ]);
        }
    }

    protected function resolveOrganization($view): ?Organization
    {
        $routeOrg = request()->route('organization');
        if ($routeOrg instanceof Organization) {
            return $routeOrg;
        }

        $viewOrg = $view->getData()['organization'] ?? null;

        return $viewOrg instanceof Organization ? $viewOrg : null;
    }
}
