<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ExpenseController;
use App\Http\Controllers\HouseholdController;
use App\Http\Controllers\ContributionController;
use App\Http\Controllers\OrganizationController;

Route::get('/{organization:slug}', [OrganizationController::class, 'show'])->name('home');

Route::prefix('/{organization:slug}')->group(function () {
    Route::resource('households', HouseholdController::class);
    Route::get('contributions',[ContributionController::class, 'index'])->name('contributions.index');
    Route::get('households/{household}/contributions/create', [ContributionController::class, 'create'])->name('contributions.create');
    Route::post('households/{household}/contributions', [ContributionController::class, 'store'])->name('contributions.store');
    Route::delete('households/{household}/contributions/{contribution}', [ContributionController::class, 'destroy'])->name('contributions.destroy');
    Route::get('dashboard', [OrganizationController::class, 'index'])->name('organizations.index');
    Route::get('contributions/verify', [ContributionController::class, 'verify'])->name('contributions.verify');
    Route::patch('contributions/{reference_number}/verify', [ContributionController::class, 'verifyContribution'])->name('contributions.verify.store');
    Route::resource('expenses', ExpenseController::class)->except(['show', 'edit', 'update', 'destroy']);
});

