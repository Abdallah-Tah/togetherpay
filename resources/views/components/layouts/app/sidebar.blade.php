<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="dark">
    <head>
        @include('partials.head')
    </head>
    <body class="min-h-screen bg-zinc-50 dark:bg-zinc-900 font-sans antialiased">
        <flux:sidebar sticky stashable class="border-e border-zinc-200/60 dark:border-zinc-700/50 bg-white/80 dark:bg-zinc-900/80 backdrop-blur-xl supports-[backdrop-filter]:bg-white/60 dark:supports-[backdrop-filter]:bg-zinc-900/60">
            <flux:sidebar.toggle class="lg:hidden" icon="x-mark" />

            <a href="{{ route('dashboard') }}" class="me-5 flex items-center space-x-2 rtl:space-x-reverse px-2" wire:navigate>
                <x-app-logo class="h-8 w-auto" />
            </a>

            <flux:navlist variant="outline" class="space-y-1">
                <flux:navlist.group :heading="__('Platform')" class="grid gap-1">
                    <flux:navlist.item icon="home" :href="route('dashboard')" :current="request()->routeIs('dashboard')" wire:navigate class="rounded-xl transition-smooth hover:bg-zinc-100 dark:hover:bg-zinc-800">{{ __('Dashboard') }}</flux:navlist.item>
                    <flux:navlist.item icon="users" :href="route('app.groups.index')" :current="request()->routeIs('app.groups.*')" wire:navigate class="rounded-xl transition-smooth hover:bg-zinc-100 dark:hover:bg-zinc-800">{{ __('Groups') }}</flux:navlist.item>
                    <flux:navlist.item icon="user-circle" :href="route('app.members.index')" :current="request()->routeIs('app.members.*')" wire:navigate class="rounded-xl transition-smooth hover:bg-zinc-100 dark:hover:bg-zinc-800">{{ __('Members') }}</flux:navlist.item>
                    <flux:navlist.item icon="wallet" :href="route('app.payment-requests.index')" :current="request()->routeIs('app.payment-requests.*')" wire:navigate class="rounded-xl transition-smooth hover:bg-zinc-100 dark:hover:bg-zinc-800">{{ __('Payments') }}</flux:navlist.item>
                    <flux:navlist.item icon="chart-bar" :href="route('app.reports.index')" :current="request()->routeIs('app.reports.*')" wire:navigate class="rounded-xl transition-smooth hover:bg-zinc-100 dark:hover:bg-zinc-800">{{ __('Reports') }}</flux:navlist.item>
                </flux:navlist.group>
            </flux:navlist>

            <flux:spacer />

            <flux:navlist variant="outline" class="space-y-1">
                <flux:navlist.item icon="folder-git-2" href="https://github.com/laravel/livewire-starter-kit" target="_blank" class="rounded-xl transition-smooth hover:bg-zinc-100 dark:hover:bg-zinc-800">
                {{ __('Repository') }}
                </flux:navlist.item>

                <flux:navlist.item icon="book-open-text" href="https://laravel.com/docs/starter-kits#livewire" target="_blank" class="rounded-xl transition-smooth hover:bg-zinc-100 dark:hover:bg-zinc-800">
                {{ __('Documentation') }}
                </flux:navlist.item>
            </flux:navlist>

            <!-- Desktop User Menu -->
            <flux:dropdown class="hidden lg:block" position="bottom" align="start">
                <flux:profile
                    :name="auth()->user()->name"
                    :initials="auth()->user()->initials()"
                    icon-trailing="chevrons-up-down"
                    class="rounded-xl hover:bg-zinc-100 dark:hover:bg-zinc-800 transition-smooth p-2"
                />

                <flux:menu class="w-[220px] rounded-xl border border-zinc-200 dark:border-zinc-700 bg-white/90 dark:bg-zinc-900/90 backdrop-blur-xl shadow-lg">
                    <flux:menu.radio.group>
                        <div class="p-0 text-sm font-normal">
                            <div class="flex items-center gap-3 px-2 py-2 text-start text-sm">
                                <span class="relative flex h-9 w-9 shrink-0 overflow-hidden rounded-lg shadow-sm">
                                    <span
                                        class="flex h-full w-full items-center justify-center rounded-lg bg-gradient-to-br from-brand-400 to-brand-600 text-white font-semibold"
                                    >
                                        {{ auth()->user()->initials() }}
                                    </span>
                                </span>

                                <div class="grid flex-1 text-start text-sm leading-tight">
                                    <span class="truncate font-semibold text-zinc-900 dark:text-white">{{ auth()->user()->name }}</span>
                                    <span class="truncate text-xs text-zinc-500 dark:text-zinc-400">{{ auth()->user()->email }}</span>
                                </div>
                            </div>
                        </div>
                    </flux:menu.radio.group>

                    <flux:menu.separator class="bg-zinc-200 dark:bg-zinc-700" />

                    <flux:menu.radio.group>
                        <flux:menu.item :href="route('profile.edit')" icon="cog" wire:navigate class="rounded-lg">{{ __('Settings') }}</flux:menu.item>
                    </flux:menu.radio.group>

                    <flux:menu.separator class="bg-zinc-200 dark:bg-zinc-700" />

                    <form method="POST" action="{{ route('logout') }}" class="w-full">
                        @csrf
                        <flux:menu.item as="button" type="submit" icon="arrow-right-start-on-rectangle" class="w-full rounded-lg text-red-600 dark:text-red-400 hover:bg-red-50 dark:hover:bg-red-900/20" data-test="logout-button">
                            {{ __('Log Out') }}
                        </flux:menu.item>
                    </form>
                </flux:menu>
            </flux:dropdown>
        </flux:sidebar>

        <!-- Mobile User Menu -->
        <flux:header class="lg:hidden border-b border-zinc-200/60 dark:border-zinc-700/50 bg-white/80 dark:bg-zinc-900/80 backdrop-blur-xl">
            <flux:sidebar.toggle class="lg:hidden" icon="bars-2" inset="left" />

            <flux:spacer />

            <flux:dropdown position="top" align="end">
                <flux:profile
                    :initials="auth()->user()->initials()"
                    icon-trailing="chevron-down"
                    class="rounded-xl"
                />

                <flux:menu class="rounded-xl border border-zinc-200 dark:border-zinc-700 bg-white/90 dark:bg-zinc-900/90 backdrop-blur-xl shadow-lg">
                    <flux:menu.radio.group>
                        <div class="p-0 text-sm font-normal">
                            <div class="flex items-center gap-3 px-2 py-2 text-start text-sm">
                                <span class="relative flex h-9 w-9 shrink-0 overflow-hidden rounded-lg shadow-sm">
                                    <span
                                        class="flex h-full w-full items-center justify-center rounded-lg bg-gradient-to-br from-brand-400 to-brand-600 text-white font-semibold"
                                    >
                                        {{ auth()->user()->initials() }}
                                    </span>
                                </span>

                                <div class="grid flex-1 text-start text-sm leading-tight">
                                    <span class="truncate font-semibold text-zinc-900 dark:text-white">{{ auth()->user()->name }}</span>
                                    <span class="truncate text-xs text-zinc-500 dark:text-zinc-400">{{ auth()->user()->email }}</span>
                                </div>
                            </div>
                        </div>
                    </flux:menu.radio.group>

                    <flux:menu.separator class="bg-zinc-200 dark:bg-zinc-700" />

                    <flux:menu.radio.group>
                        <flux:menu.item :href="route('profile.edit')" icon="cog" wire:navigate class="rounded-lg">{{ __('Settings') }}</flux:menu.item>
                    </flux:menu.radio.group>

                    <flux:menu.separator class="bg-zinc-200 dark:bg-zinc-700" />

                    <form method="POST" action="{{ route('logout') }}" class="w-full">
                        @csrf
                        <flux:menu.item as="button" type="submit" icon="arrow-right-start-on-rectangle" class="w-full rounded-lg text-red-600 dark:text-red-400 hover:bg-red-50 dark:hover:bg-red-900/20" data-test="logout-button">
                            {{ __('Log Out') }}
                        </flux:menu.item>
                    </form>
                </flux:menu>
            </flux:dropdown>
        </flux:header>

        {{ $slot }}
        @livewireScripts
        @include('partials.flux-scripts')
    </body>
</html>
