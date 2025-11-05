<?php

use App\Http\Controllers\SmartPayController;
use App\Livewire\Groups\Create as GroupsCreate;
use App\Livewire\Groups\Index as GroupsIndex;
use App\Livewire\Groups\Show as GroupsShow;
use App\Livewire\Members\Index as MembersIndex;
use App\Livewire\PaymentRequests\Index as PaymentRequestsIndex;
use App\Livewire\Reports\Index as ReportsIndex;
use Illuminate\Support\Facades\Route;
use Laravel\Fortify\Features;
use Livewire\Volt\Volt;

Route::get('/', function () {
    return view('welcome');
})->name('home');

// Smart Pay - Public payment page (no auth required)
Route::get('/pay/{request}', [SmartPayController::class, 'show'])->name('smartpay.show');

// Onboarding routes (auth required but no onboarding check)
Route::middleware(['auth'])->prefix('onboarding')->name('onboarding.')->group(function () {
    Volt::route('welcome', 'onboarding.welcome')->name('welcome');
    Volt::route('payment-setup', 'onboarding.payment-setup')->name('payment-setup');
    Volt::route('complete', 'onboarding.complete')->name('complete');
});

Route::view('dashboard', 'dashboard')
    ->middleware(['auth', 'verified', \App\Http\Middleware\EnsureOnboardingCompleted::class])
    ->name('dashboard');

Route::middleware(['auth', \App\Http\Middleware\EnsureOnboardingCompleted::class])->group(function () {
    // Groups
    Route::get('groups', GroupsIndex::class)->name('app.groups.index');
    Route::get('groups/create', GroupsCreate::class)->name('app.groups.create');
    Route::get('groups/{group}', GroupsShow::class)->name('app.groups.show');

    // Members
    Route::get('members', MembersIndex::class)->name('app.members.index');

    // Payment Requests
    Route::get('payment-requests', PaymentRequestsIndex::class)->name('app.payment-requests.index');

    // Reports
    Route::get('reports', ReportsIndex::class)->name('app.reports.index');

    // Settings
    Route::redirect('settings', 'settings/profile');
    Volt::route('settings/profile', 'settings.profile')->name('profile.edit');
    Volt::route('settings/password', 'settings.password')->name('user-password.edit');
    Volt::route('settings/payment-accounts', 'settings.payment-accounts')->name('payment-accounts.edit');
    Volt::route('settings/appearance', 'settings.appearance')->name('appearance.edit');

    Volt::route('settings/two-factor', 'settings.two-factor')
        ->middleware(
            when(
                Features::canManageTwoFactorAuthentication()
                    && Features::optionEnabled(Features::twoFactorAuthentication(), 'confirmPassword'),
                ['password.confirm'],
                [],
            ),
        )
        ->name('two-factor.show');
});

// Payment callback for Cash App redirects
Route::get('/payment/callback', function () {
    return view('payment.callback');
})->name('payment.callback');
