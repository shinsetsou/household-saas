<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ExpenseController;
use App\Http\Controllers\HouseholdController;
use App\Http\Controllers\HouseholdImportController;
use App\Http\Controllers\ContributionController;
use App\Http\Controllers\OrganizationController;
use App\Http\Controllers\OrganizationSettingsController;
use App\Http\Middleware\EnsureOrganizationAdmin;

Route::get('/{organization:slug}', [OrganizationController::class, 'show'])->name('home');

Route::prefix('/{organization:slug}')->group(function () {
    Route::resource('households', HouseholdController::class);
    Route::get('households/import', [HouseholdImportController::class, 'create'])->name('households.import.create');
    Route::post('households/import', [HouseholdImportController::class, 'store'])->name('households.import.store');
    Route::get('contributions',[ContributionController::class, 'index'])->name('contributions.index');
    Route::get('households/{household}/contributions/create', [ContributionController::class, 'create'])->name('contributions.create');
    Route::post('households/{household}/contributions', [ContributionController::class, 'store'])->name('contributions.store');
    Route::delete('households/{household}/contributions/{contribution}', [ContributionController::class, 'destroy'])->name('contributions.destroy');
    Route::get('dashboard', [OrganizationController::class, 'index'])->name('organizations.index');
    Route::get('contributions/verify', [ContributionController::class, 'verify'])->name('contributions.verify');
    Route::patch('contributions/{reference_number}/verify', [ContributionController::class, 'verifyContribution'])->name('contributions.verify.store');
    Route::resource('expenses', ExpenseController::class)->except(['show', 'edit', 'update', 'destroy']);
});

Route::prefix('/{organization:slug}')->middleware(['auth', EnsureOrganizationAdmin::class])->group(function () {
    Route::get('settings', [OrganizationSettingsController::class, 'show'])->name('organizations.settings.show');
    Route::patch('settings/general', fn () => 'placeholder')->name('organizations.settings.update-general');
    // PATCH route added in Task 4
});

