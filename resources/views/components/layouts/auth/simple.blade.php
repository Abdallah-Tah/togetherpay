<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="h-full">
    <head>
        @include('partials.head')
    </head>
    <body class="h-full bg-zinc-50 dark:bg-zinc-900 antialiased overflow-hidden">
        <!-- Animated Background Layers -->
        <div class="bg-animated-aurora"></div>
        <div class="bg-animated-grid"></div>

        <div class="relative flex min-h-full flex-col justify-center py-12 sm:px-6 lg:px-8 z-10">
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
                <div class="bg-white/70 dark:bg-zinc-900/70 backdrop-blur-xl rounded-3xl shadow-2xl shadow-zinc-900/10 dark:shadow-zinc-950/50 border border-zinc-200/50 dark:border-zinc-800/50 px-8 py-10 sm:px-10">
                    {{ $slot }}
                </div>
            </div>
        </div>
    </body>
</html>
