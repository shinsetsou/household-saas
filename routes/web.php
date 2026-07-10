<?php

use App\Http\Controllers\BillingController;
use App\Http\Controllers\BillingSettingsController;
use App\Http\Controllers\ContributionController;
use App\Http\Controllers\DashboardRedirectController;
use App\Http\Controllers\ExpenseController;
use App\Http\Controllers\HouseholdController;
use App\Http\Controllers\HouseholdImportController;
use App\Http\Controllers\InvitationController;
use App\Http\Controllers\Onboarding\DoneStepController;
use App\Http\Controllers\Onboarding\HouseholdsStepController;
use App\Http\Controllers\Onboarding\OrganizationStepController;
use App\Http\Controllers\OrganizationController;
use App\Http\Controllers\OrganizationSettingsController;
use App\Http\Controllers\PendingInvitationsController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\Settings\MemberSettingsController;
use App\Http\Middleware\EnsureOrganizationActive;
use App\Http\Middleware\EnsureOrganizationAdmin;
use App\Http\Middleware\EnsureOrganizationMember;
use App\Http\Middleware\EnsureOrganizationRole;
use App\Http\Middleware\EnsurePlanAllowsHousehold;
use App\Http\Middleware\RedirectIfOnboarded;
use App\Http\Middleware\VerifyPayMongoSignature;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::view('/privacy', 'legal.privacy')->name('privacy');
Route::view('/terms', 'legal.terms')->name('terms');

Route::get('/dashboard', DashboardRedirectController::class)
    ->middleware(['auth', 'verified', 'force.password.change'])
    ->name('dashboard');

Route::middleware(['auth', 'verified', 'force.password.change'])->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

require __DIR__.'/auth.php';

Route::middleware(['auth', 'verified'])->prefix('onboarding')->name('onboarding.')->group(function () {
    Route::get('organization', [OrganizationStepController::class, 'create'])->name('organization.create');
    Route::post('organization', [OrganizationStepController::class, 'store'])->name('organization.store');

    Route::middleware(RedirectIfOnboarded::class)->group(function () {
        Route::get('households', [HouseholdsStepController::class, 'create'])->name('households.create');
        Route::post('households', [HouseholdsStepController::class, 'store'])->name('households.store');
        Route::post('households/skip', [HouseholdsStepController::class, 'skip'])->name('households.skip');
        Route::get('done', [DoneStepController::class, 'show'])->name('done.show');
    });
});

Route::get('/{organization:slug}', [OrganizationController::class, 'show'])->name('home');

Route::prefix('/{organization:slug}')->group(function () {
    Route::get('households', [HouseholdController::class, 'index'])
        ->middleware('throttle:10,1')
        ->name('households.index');

    Route::get('households/{household}/contributions/create', [ContributionController::class, 'create'])
        ->name('contributions.create');
    Route::post('households/{household}/contributions', [ContributionController::class, 'store'])
        ->middleware(['signed', 'throttle:5,1'])
        ->name('contributions.store');

    Route::middleware(['auth', 'verified', 'force.password.change', EnsureOrganizationMember::class, EnsureOrganizationActive::class])->group(function () {
        Route::get('dashboard', [OrganizationController::class, 'index'])->name('organizations.index');

        Route::middleware(EnsurePlanAllowsHousehold::class)->group(function () {
            Route::get('households/import', [HouseholdImportController::class, 'create'])->name('households.import.create');
            Route::get('households/import/sample', [HouseholdImportController::class, 'sample'])->name('households.import.sample');
            Route::post('households/import', [HouseholdImportController::class, 'store'])->name('households.import.store');
            Route::resource('households', HouseholdController::class)->except(['index']);
        });

        Route::get('contributions', [ContributionController::class, 'index'])->name('contributions.index');
        Route::delete('households/{household}/contributions/{contribution}', [ContributionController::class, 'destroy'])
            ->middleware(EnsureOrganizationRole::class.':admin,treasurer')
            ->name('contributions.destroy');
        Route::get('contributions/verify', [ContributionController::class, 'verify'])
            ->middleware(EnsureOrganizationRole::class.':admin,treasurer')
            ->name('contributions.verify');
        Route::patch('contributions/{reference_number}/verify', [ContributionController::class, 'verifyContribution'])
            ->middleware(EnsureOrganizationRole::class.':admin,treasurer')
            ->name('contributions.verify.store');
        Route::patch('contributions/verify-bulk', [ContributionController::class, 'bulkVerifyContribution'])
            ->middleware(EnsureOrganizationRole::class.':admin,treasurer')
            ->name('contributions.verify.bulk');

        Route::resource('expenses', ExpenseController::class)
            ->middleware(EnsureOrganizationRole::class.':admin,treasurer')
            ->except(['edit', 'update', 'destroy']);

        Route::post('expenses/{expense}/mark-paid', [ExpenseController::class, 'markPaid'])
            ->middleware(EnsureOrganizationRole::class.':admin,treasurer')
            ->name('expenses.mark-paid');
        Route::post('expenses/{expense}/cancel', [ExpenseController::class, 'markCancelled'])
            ->middleware(EnsureOrganizationRole::class.':admin,treasurer')
            ->name('expenses.cancel');
        Route::post('expenses/{expense}/restore', [ExpenseController::class, 'restore'])
            ->middleware(EnsureOrganizationRole::class.':admin,treasurer')
            ->name('expenses.restore');

        Route::get('reports/contributions', [ReportController::class, 'contributions'])->name('reports.contributions.index');
        Route::get('reports/contributions/export/pdf', [ReportController::class, 'contributionsPdf'])->name('reports.contributions.export.pdf');
        Route::get('reports/contributions/export/excel', [ReportController::class, 'contributionsExcel'])->name('reports.contributions.export.excel');
        Route::get('reports/expenses', [ReportController::class, 'expenses'])->name('reports.expenses.index');
        Route::get('reports/expenses/export/pdf', [ReportController::class, 'expensesPdf'])->name('reports.expenses.export.pdf');
        Route::get('reports/expenses/export/excel', [ReportController::class, 'expensesExcel'])->name('reports.expenses.export.excel');
        Route::get('reports/delinquency', [ReportController::class, 'delinquency'])->name('reports.delinquency.index');
        Route::get('reports/delinquency/export/csv', [ReportController::class, 'delinquencyCsv'])->name('reports.delinquency.export.csv');

        Route::middleware(EnsureOrganizationAdmin::class)->group(function () {
            Route::get('settings', [OrganizationSettingsController::class, 'show'])->name('organizations.settings.show');
            Route::patch('settings/general', [OrganizationSettingsController::class, 'updateGeneral'])->name('organizations.settings.update-general');

            Route::get('settings/members', [MemberSettingsController::class, 'show'])->name('organizations.settings.members.show');
            Route::post('settings/members/invite', [MemberSettingsController::class, 'invite'])->name('organizations.settings.members.invite');
            Route::post('settings/members/{member}/remove', [MemberSettingsController::class, 'removeMember'])->name('organizations.settings.members.remove');
            Route::patch('settings/members/{member}/role', [MemberSettingsController::class, 'changeRole'])->name('organizations.settings.members.role');
            Route::post('settings/invitations/{invitation}/revoke', [MemberSettingsController::class, 'revoke'])->name('organizations.settings.invitations.revoke');
            Route::post('settings/invitations/{invitation}/resend', [MemberSettingsController::class, 'resend'])->name('organizations.settings.invitations.resend');
            Route::get('settings/invitations/{invitation}/link', [MemberSettingsController::class, 'link'])->name('organizations.settings.invitations.link');

            Route::get('settings/billing', [BillingSettingsController::class, 'show'])->name('organizations.settings.billing.show');
            Route::post('settings/billing/checkout/{plan}', [BillingController::class, 'checkout'])->name('organizations.settings.billing.checkout');
            Route::post('settings/billing/cancel', [BillingSettingsController::class, 'cancel'])->name('organizations.settings.billing.cancel');
            Route::post('settings/billing/resume', [BillingSettingsController::class, 'resume'])->name('organizations.settings.billing.resume');
        });
    });
});

Route::get('/invitations/{invitation}', [InvitationController::class, 'show'])->middleware('signed')->name('invitations.show');
Route::post('/invitations/{invitation}/accept', [InvitationController::class, 'accept'])->middleware(['auth', 'verified', 'signed'])->name('invitations.accept');
Route::post('/invitations/{invitation}/decline', [InvitationController::class, 'decline'])->middleware(['auth', 'verified', 'signed'])->name('invitations.decline');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('/invitations', [PendingInvitationsController::class, 'index'])->name('invitations.index');
    Route::delete('/invitations/{invitation}', [PendingInvitationsController::class, 'destroy'])->name('invitations.pending.destroy');
    Route::get('/invitations/locked', [InvitationController::class, 'locked'])->name('invitations.locked');
});

Route::get('/billing/return/success', [BillingController::class, 'returnSuccess'])->name('billing.return.success');
Route::get('/billing/return/cancel', [BillingController::class, 'returnCancel'])->name('billing.return.cancel');
Route::post('/billing/webhook', [BillingController::class, 'webhook'])
    ->middleware(VerifyPayMongoSignature::class)
    ->name('billing.webhook');
