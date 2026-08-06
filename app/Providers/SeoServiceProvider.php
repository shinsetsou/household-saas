<?php

namespace App\Providers;

use App\Support\Seo;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class SeoServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(Seo::class, function (): Seo {
            return Seo::make();
        });
    }

    public function boot(): void
    {
        Blade::directive('seo', function (): string {
            return '<?php echo app(\App\Support\Seo::class)->render(); ?>';
        });

        $views = [
            'welcome',
            'layouts.layout',
            'layouts.guest',
            'layouts.app',
        ];

        View::composer($views, function ($view): void {
            $seo = app(Seo::class);
            $viewTitle = $view->getData()['title'] ?? null;
            $viewDescription = $view->getData()['description'] ?? null;

            if (request()->routeIs(['home'])) {
                $org = request()->route('organization');
                $orgName = $org?->name ?? 'your HOA';

                $seo = Seo::make(
                    title: $viewTitle ?: 'Household lookup — '.$seo->siteName,
                    description: $viewDescription ?: "Find your household record by block and lot and submit a verified payment to {$orgName}.",
                    canonical: request()->fullUrl(),
                    type: 'website',
                )->withSchema([
                    [
                        '@context' => 'https://schema.org',
                        '@type' => 'Organization',
                        'name' => $orgName,
                        'url' => request()->url(),
                    ],
                    [
                        '@context' => 'https://schema.org',
                        '@type' => 'BreadcrumbList',
                        'itemListElement' => [
                            ['@type' => 'ListItem', 'position' => 1, 'name' => 'Home', 'item' => rtrim(config('app.url') ?: url('/'), '/')],
                            ['@type' => 'ListItem', 'position' => 2, 'name' => $orgName, 'item' => request()->url()],
                        ],
                    ],
                ]);
            } elseif (request()->routeIs('welcome')) {
                $appUrl = rtrim(config('app.url') ?: url('/'), '/');
                $siteName = config('app.name', 'BarangayBooks');

                $seo = Seo::make(
                    title: $viewTitle ?: 'HOA dues collection without the group chat chaos — BarangayBooks',
                    description: $viewDescription ?: 'BarangayBooks helps Philippine Homeowners Associations collect monthly dues, verify payments with photos, and export audit-ready reports. Free during beta.',
                    canonical: request()->fullUrl(),
                    type: 'website',
                )->withSchema([
                    [
                        '@context' => 'https://schema.org',
                        '@type' => 'Organization',
                        'name' => $siteName,
                        'url' => $appUrl,
                        'logo' => $appUrl.'/og/default.png',
                        'contactPoint' => [[
                            '@type' => 'ContactPoint',
                            'contactType' => 'customer support',
                            'email' => 'hello@barangaybooks.app',
                            'areaServed' => 'PH',
                        ]],
                    ],
                    [
                        '@context' => 'https://schema.org',
                        '@type' => 'SoftwareApplication',
                        'name' => $siteName,
                        'applicationCategory' => 'BusinessApplication',
                        'applicationSubCategory' => 'Homeowners Association Management Software',
                        'operatingSystem' => 'Web',
                        'url' => $appUrl,
                        'image' => $appUrl.'/og/default.png',
                        'offers' => [
                            ['@type' => 'Offer', 'name' => 'Community', 'price' => '199', 'priceCurrency' => 'PHP', 'priceSpecification' => ['@type' => 'UnitPriceSpecification', 'price' => '199', 'priceCurrency' => 'PHP', 'unitText' => 'MONTH']],
                            ['@type' => 'Offer', 'name' => 'Barangay', 'price' => '499', 'priceCurrency' => 'PHP', 'priceSpecification' => ['@type' => 'UnitPriceSpecification', 'price' => '499', 'priceCurrency' => 'PHP', 'unitText' => 'MONTH']],
                            ['@type' => 'Offer', 'name' => 'City', 'price' => '999', 'priceCurrency' => 'PHP', 'priceSpecification' => ['@type' => 'UnitPriceSpecification', 'price' => '999', 'priceCurrency' => 'PHP', 'unitText' => 'MONTH']],
                        ],
                    ],
                    [
                        '@context' => 'https://schema.org',
                        '@type' => 'FAQPage',
                        'mainEntity' => [
                            ['@type' => 'Question', 'name' => 'Who owns the data?', 'acceptedAnswer' => ['@type' => 'Answer', 'text' => 'Your HOA does. Always. We are the custodian, not the owner.']],
                            ['@type' => 'Question', 'name' => 'Can we export everything?', 'acceptedAnswer' => ['@type' => 'Answer', 'text' => 'Yes. Contributions, expenses, delinquency, and household lists export to PDF, Excel, and CSV with one click.']],
                            ['@type' => 'Question', 'name' => 'Is it secure enough for handling member dues?', 'acceptedAnswer' => ['@type' => 'Answer', 'text' => 'Records and uploaded receipts are encrypted at rest and in transit. Role-based access limits what each officer can see and do.']],
                            ['@type' => 'Question', 'name' => 'How fast can we onboard?', 'acceptedAnswer' => ['@type' => 'Answer', 'text' => 'Most HOAs are live the same afternoon. Sign up, upload a CSV of your households, and you are collecting verified payments within the hour.']],
                            ['@type' => 'Question', 'name' => 'Do you support GCash, Maya, or local bank integrations?', 'acceptedAnswer' => ['@type' => 'Answer', 'text' => 'Not yet — that is on the roadmap. Today, members log payments with a photo of their GCash receipt or deposit slip.']],
                        ],
                    ],
                    [
                        '@context' => 'https://schema.org',
                        '@type' => 'BreadcrumbList',
                        'itemListElement' => [
                            ['@type' => 'ListItem', 'position' => 1, 'name' => 'Home', 'item' => $appUrl],
                        ],
                    ],
                ]);
            } elseif (request()->routeIs('privacy')) {
                $seo = Seo::make(
                    title: $viewTitle ?: 'Privacy Policy — BarangayBooks',
                    description: $viewDescription ?: 'How BarangayBooks collects, stores, and protects your HOA records, household data, contribution receipts, and account information.',
                    canonical: request()->fullUrl(),
                );
            } elseif (request()->routeIs('terms')) {
                $seo = Seo::make(
                    title: $viewTitle ?: 'Terms of Service — BarangayBooks',
                    description: $viewDescription ?: 'Terms and conditions for using BarangayBooks to manage your homeowners association records, payments, and member contributions.',
                    canonical: request()->fullUrl(),
                );
            } elseif (request()->routeIs('login')) {
                $seo = Seo::make(
                    title: $viewTitle ?: 'Sign in — BarangayBooks',
                    description: $viewDescription ?: "Sign in to verify HOA contributions, run reports, and keep your association's books in order.",
                    canonical: request()->fullUrl(),
                )->withNoIndex();
            } elseif (request()->routeIs('register')) {
                $seo = Seo::make(
                    title: $viewTitle ?: 'Create your account — BarangayBooks',
                    description: $viewDescription ?: 'Start your 14-day free trial of BarangayBooks — photo-verified HOA dues collection for Philippine homeowners associations.',
                    canonical: request()->fullUrl(),
                )->withNoIndex();
            } elseif (request()->routeIs([
                'password.request',
                'password.email',
                'password.reset',
                'password.confirm',
                'verification.notice',
                'verification.verify',
                'verification.send',
                'invitations.*',
                'onboarding.*',
                'dashboard',
                'profile.*',
                'organizations.index',
                'organizations.settings.*',
                'households.*',
                'contributions.*',
                'expenses.*',
                'reports.*',
                'billing.*',
            ])) {
                $seo = Seo::make(
                    title: $viewTitle,
                    description: $viewDescription,
                    canonical: request()->fullUrl(),
                )->withNoIndex();
            } elseif ($viewTitle) {
                $seo = Seo::make(
                    title: $viewTitle,
                    description: $viewDescription,
                    canonical: request()->fullUrl(),
                );
            }

            app()->instance(Seo::class, $seo);
            $view->with('seo', $seo);
        });
    }
}
