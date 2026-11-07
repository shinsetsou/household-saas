<?php

use App\AI\AIManager;
use App\AI\Jobs\CustomerSuccessScanJob;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote')->hourly();

Schedule::command('trials:expire')
    ->dailyAt('00:05')
    ->onOneServer()
    ->withoutOverlapping();

Schedule::command('subscriptions:expire')
    ->dailyAt('00:10')
    ->onOneServer()
    ->withoutOverlapping();

// Self-heal orphaned checkouts: a customer who paid on PayMongo's hosted
// checkout but whose payment.paid webhook never activated the org stays
// trialing forever without this. Active in-flight sessions are skipped, so
// running every 15 min is safe; --older-than-minutes adds a second guard
// against race-ing a customer who is still mid-checkout.
Schedule::command('billing:reconcile-pending', ['--older-than-minutes' => 15])
    ->everyFifteenMinutes()
    ->onOneServer()
    ->withoutOverlapping();

Schedule::call(function () {
    app(AIManager::class)->agent('founder')->task('daily-brief')->queue();
})
    ->name('ai:founder-brief')
    ->dailyAt('07:00')
    ->timezone(config('ai.timezone', 'Asia/Manila'))
    ->onOneServer()
    ->withoutOverlapping();

Schedule::call(function () {
    CustomerSuccessScanJob::dispatch();
})
    ->name('ai:cs-nightly')
    ->dailyAt('02:00')
    ->timezone(config('ai.timezone', 'Asia/Manila'))
    ->onOneServer()
    ->withoutOverlapping();
