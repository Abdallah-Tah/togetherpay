<?php

use App\Jobs\CloseCyclesJob;
use App\Jobs\CreateUpcomingCyclesJob;
use App\Jobs\GeneratePaymentRequestsJob;
use App\Jobs\GenerateSquareInvoicesJob;
use App\Jobs\ProcessAutoConfirmations;
use App\Jobs\ProcessDunningJob;
use App\Jobs\SendWeeklyMemberSummaries;
use App\Models\BillingCycle;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Schedule billing cycle and payment request jobs
Schedule::job(new CreateUpcomingCyclesJob)->dailyAt('01:10')->name('create-cycles');
Schedule::job(new GeneratePaymentRequestsJob)->dailyAt('01:30')->name('generate-requests');

// Generate Square invoices for auto_square groups (2 days before due date)
Schedule::call(function () {
    BillingCycle::whereBetween('due_at', [
        now()->addDays(2)->startOfDay(),
        now()->addDays(2)->endOfDay(),
    ])
        ->where('status', 'active')
        ->whereHas('group', fn ($q) => $q->where('billing_mode', 'auto_square'))
        ->whereDoesntHave('paymentRequests')
        ->each(fn ($cycle) => GenerateSquareInvoicesJob::dispatch($cycle));
})->dailyAt('09:00')->name('generate-square-invoices');

Schedule::job(new ProcessDunningJob)->dailyAt('10:00')->name('process-dunning-morning');
Schedule::job(new ProcessDunningJob)->dailyAt('15:00')->name('process-dunning-afternoon');
Schedule::job(new CloseCyclesJob)->dailyAt('23:30')->name('close-cycles');

// Auto-confirm payments that have passed their grace period (hourly)
Schedule::job(new ProcessAutoConfirmations)->hourly()->name('auto-confirm-payments');

// Send weekly member summaries (Fridays at 9 AM)
Schedule::job(new SendWeeklyMemberSummaries)->weeklyOn(5, '09:00')->name('weekly-member-summaries');
