<?php

use function Livewire\Volt\{state};

state([
    'icon' => 'sparkles',
    'title' => 'Coming Soon',
    'description' => 'This feature is currently under development and will be available soon.',
    'estimatedRelease' => null,
]);

?>

<div>
    <x-app-layout>
        <div class="py-12">
            <div class="mx-auto max-w-2xl px-4 sm:px-6 lg:px-8">
                <div class="text-center">
                    {{-- Icon --}}
                    <div class="mb-8 inline-flex items-center justify-center">
                        <div class="rounded-full bg-gradient-to-br from-blue-100 to-purple-100 p-8 shadow-lg">
                            <flux:icon.{{ $icon }} class="size-20 text-blue-600" />
                        </div>
                    </div>

                    {{-- Coming Soon Badge --}}
                    <div class="mb-6 inline-flex items-center gap-2 rounded-full border-2 border-orange-200 bg-orange-50 px-6 py-2">
                        <flux:icon.clock class="size-5 text-orange-600" />
                        <span class="text-sm font-bold uppercase tracking-widest text-orange-700">Coming Soon</span>
                    </div>

                    {{-- Title --}}
                    <h1 class="mb-4 text-4xl font-bold tracking-tight text-gray-900 sm:text-5xl">
                        {{ $title }}
                    </h1>

                    {{-- Description --}}
                    <p class="mx-auto mb-8 max-w-xl text-lg leading-relaxed text-gray-600">
                        {{ $description }}
                    </p>

                    {{-- Estimated Release --}}
                    @if($estimatedRelease)
                        <div class="mb-8 inline-flex items-center gap-3 rounded-xl bg-blue-50 px-6 py-3 shadow-sm">
                            <flux:icon.calendar class="size-5 text-blue-700" />
                            <span class="text-base font-semibold text-blue-900">
                                Expected: {{ $estimatedRelease }}
                            </span>
                        </div>
                    @endif

                    {{-- Progress Bar --}}
                    <div class="mx-auto mb-8 w-64">
                        <div class="mb-2 h-2 overflow-hidden rounded-full bg-gray-200">
                            <div class="h-full w-2/3 rounded-full bg-gradient-to-r from-blue-500 to-purple-500 transition-all duration-500"></div>
                        </div>
                        <p class="text-sm text-gray-500">In Development</p>
                    </div>

                    {{-- Action Buttons --}}
                    <div class="flex flex-col items-center justify-center gap-4 sm:flex-row">
                        <flux:button variant="primary" disabled>
                            <flux:icon.bell class="size-4" />
                            Notify Me When Ready
                        </flux:button>

                        <flux:button variant="ghost" href="{{ route('app.dashboard') }}" wire:navigate>
                            <flux:icon.arrow-left class="size-4" />
                            Back to Dashboard
                        </flux:button>
                    </div>

                    {{-- Feature Highlights --}}
                    <div class="mt-12 grid gap-4 sm:grid-cols-3">
                        <div class="rounded-lg bg-white p-6 shadow-sm ring-1 ring-gray-100">
                            <flux:icon.rocket-launch class="mx-auto mb-3 size-8 text-blue-600" />
                            <h3 class="mb-1 font-semibold text-gray-900">Powerful</h3>
                            <p class="text-sm text-gray-600">Built with the latest technology</p>
                        </div>
                        <div class="rounded-lg bg-white p-6 shadow-sm ring-1 ring-gray-100">
                            <flux:icon.shield-check class="mx-auto mb-3 size-8 text-green-600" />
                            <h3 class="mb-1 font-semibold text-gray-900">Secure</h3>
                            <p class="text-sm text-gray-600">Your data is protected</p>
                        </div>
                        <div class="rounded-lg bg-white p-6 shadow-sm ring-1 ring-gray-100">
                            <flux:icon.bolt class="mx-auto mb-3 size-8 text-purple-600" />
                            <h3 class="mb-1 font-semibold text-gray-900">Fast</h3>
                            <p class="text-sm text-gray-600">Optimized for performance</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </x-app-layout>
</div>
