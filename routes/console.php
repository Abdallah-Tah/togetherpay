<?php

use App\Jobs\CloseCyclesJob;
use App\Jobs\CreateUpcomingCyclesJob;
use App\Jobs\GeneratePaymentRequestsJob;
use App\Jobs\ProcessDunningJob;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Schedule billing cycle and payment request jobs
Schedule::job(new CreateUpcomingCyclesJob)->dailyAt('01:10')->name('create-cycles');
Schedule::job(new GeneratePaymentRequestsJob)->dailyAt('01:30')->name('generate-requests');
Schedule::job(new ProcessDunningJob)->dailyAt('09:00')->name('process-dunning-morning');
Schedule::job(new ProcessDunningJob)->dailyAt('15:00')->name('process-dunning-afternoon');
Schedule::job(new CloseCyclesJob)->dailyAt('23:30')->name('close-cycles');
