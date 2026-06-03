<?php

use App\Http\Controllers\ContributionController;
use App\Http\Controllers\DashboardRedirectController;
use App\Http\Controllers\ExpenseController;
use App\Http\Controllers\HouseholdController;
use App\Http\Controllers\HouseholdImportController;
use App\Http\Controllers\Onboarding\DoneStepController;
use App\Http\Controllers\Onboarding\HouseholdsStepController;
use App\Http\Controllers\Onboarding\OrganizationStepController;
use App\Http\Controllers\OrganizationController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\ReportController;
use App\Http\Middleware\EnsureOrganizationMember;
use App\Http\Middleware\RedirectIfOnboarded;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::view('/privacy', 'legal.privacy')->name('privacy');
Route::view('/terms', 'legal.terms')->name('terms');

Route::get('/dashboard', DashboardRedirectController::class)
    ->middleware('auth')
    ->name('dashboard');

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

require __DIR__.'/auth.php';

Route::middleware('auth')->prefix('onboarding')->name('onboarding.')->group(function () {
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
        ->name('contributions.store');

    Route::middleware(['auth', EnsureOrganizationMember::class])->group(function () {
        Route::get('dashboard', [OrganizationController::class, 'index'])->name('organizations.index');

        Route::get('households/import', [HouseholdImportController::class, 'create'])->name('households.import.create');
        Route::post('households/import', [HouseholdImportController::class, 'store'])->name('households.import.store');
        Route::resource('households', HouseholdController::class)->except(['index']);

        Route::get('contributions', [ContributionController::class, 'index'])->name('contributions.index');
        Route::delete('households/{household}/contributions/{contribution}', [ContributionController::class, 'destroy'])->name('contributions.destroy');
        Route::get('contributions/verify', [ContributionController::class, 'verify'])->name('contributions.verify');
        Route::patch('contributions/{reference_number}/verify', [ContributionController::class, 'verifyContribution'])->name('contributions.verify.store');

        Route::resource('expenses', ExpenseController::class)->except(['show', 'edit', 'update', 'destroy']);

        Route::get('reports/contributions', [ReportController::class, 'contributions'])->name('reports.contributions.index');
        Route::get('reports/contributions/export/pdf', [ReportController::class, 'contributionsPdf'])->name('reports.contributions.export.pdf');
        Route::get('reports/contributions/export/excel', [ReportController::class, 'contributionsExcel'])->name('reports.contributions.export.excel');
        Route::get('reports/expenses', [ReportController::class, 'expenses'])->name('reports.expenses.index');
        Route::get('reports/expenses/export/pdf', [ReportController::class, 'expensesPdf'])->name('reports.expenses.export.pdf');
        Route::get('reports/expenses/export/excel', [ReportController::class, 'expensesExcel'])->name('reports.expenses.export.excel');
        Route::get('reports/delinquency', [ReportController::class, 'delinquency'])->name('reports.delinquency.index');
        Route::get('reports/delinquency/export/csv', [ReportController::class, 'delinquencyCsv'])->name('reports.delinquency.export.csv');
    });
});
