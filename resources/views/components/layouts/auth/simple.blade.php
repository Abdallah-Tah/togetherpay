<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="h-full">
    <head>
        @include('partials.head')
    </head>
    <body class="h-full bg-gradient-to-br from-brand-50 via-white to-brand-50 dark:from-zinc-950 dark:via-zinc-900 dark:to-zinc-950 antialiased">
        <div class="flex min-h-full flex-col justify-center py-12 sm:px-6 lg:px-8">
            <div class="sm:mx-auto sm:w-full sm:max-w-md">
                <!-- Logo -->
                <a href="{{ route('welcome') }}" class="flex flex-col items-center gap-3 mb-8" wire:navigate>
                    <div class="flex h-20 w-20 items-center justify-center">
                        <x-app-logo-icon class="h-full w-full shadow-xl shadow-brand-500/30 rounded-full" />
                    </div>
                    <span class="text-2xl font-bold bg-gradient-to-r from-brand-600 to-brand-500 bg-clip-text text-transparent">
                        {{ config('app.name', 'TogetherPay') }}
                    </span>
                </a>

                <!-- Auth Card -->
                <div class="bg-white dark:bg-zinc-900 rounded-3xl shadow-2xl shadow-zinc-900/10 dark:shadow-zinc-950/50 border border-zinc-100 dark:border-zinc-800 px-8 py-10 sm:px-10">
                    {{ $slot }}
                </div>
            </div>
        </div>
    </body>
</html>
