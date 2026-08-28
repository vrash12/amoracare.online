<?php

use App\Services\UserAccountStatusService;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Artisan::command('users:deactivate-inactive', function (UserAccountStatusService $accountStatusService) {
    $count = $accountStatusService->deactivateDormantUsers();

    $this->info(sprintf(
        '%d account(s) were set to inactive after %d days without a login.',
        $count,
        $accountStatusService->inactivityDays()
    ));
})->purpose('Set dormant active user accounts to inactive');

Schedule::command('users:deactivate-inactive')
    ->dailyAt('00:05')
    ->timezone(config('app.display_timezone', 'Asia/Manila'))
    ->withoutOverlapping();
