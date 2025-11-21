<?php

namespace App\Providers;

use App\Listeners\LogEmailFailed;
use App\Listeners\LogEmailSent;
use Illuminate\Mail\Events\MessageSendingFailed;
use Illuminate\Mail\Events\MessageSent;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Register email logging listeners
        Event::listen(MessageSent::class, LogEmailSent::class);
        Event::listen(MessageSendingFailed::class, LogEmailFailed::class);
    }
}
