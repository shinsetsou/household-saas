<?php

namespace App\Providers;

use App\Models\Contribution;
use App\Models\Organization;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        View::composer('*', function ($view): void {
            static $cache = [];
            $requestId = spl_object_id(request());

            if (! isset($cache[$requestId])) {
                $count = 0;
                $org = request()->route('organization');
                $user = auth()->user();

                if ($org instanceof Organization && $user && $user->belongsToOrganization($org)) {
                    $count = Contribution::forOrganization($org)
                        ->where('verification_status', 'pending')
                        ->distinct()
                        ->count('reference_number');
                }

                $cache[$requestId] = $count;
            }

            $view->with('pendingVerificationCount', $cache[$requestId]);
        });
    }
}
