<?php

namespace App\Providers;

use App\Models\Contribution;
use App\Models\Organization;
use App\Support\NavContext;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
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