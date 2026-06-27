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
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(PayMongoClient::class, fn () => PayMongoClient::make());
    }

    public function boot(): void
    {
        OrganizationInvitation::observe(OrganizationInvitationObserver::class);

        $this->ensurePlansExist();

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
     * The plans catalog is read from the DB by every billing surface (billing
     * page, signup onboarding, downgrade flow, webhook handler). A missing plan
     * row silently breaks upgrade UI — so we make sure all three exist on every
     * boot. updateOrCreate keeps this idempotent and runs in <5ms.
     */
    protected function ensurePlansExist(): void
    {
        if (! Schema::hasTable('plans')) {
            return;
        }

        try {
            app(BillingService::class)->seedDefaultPlans();
        } catch (\Throwable $e) {
            // Boot-time seed must never break the request cycle. The next
            // request will retry.
            report($e);
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
