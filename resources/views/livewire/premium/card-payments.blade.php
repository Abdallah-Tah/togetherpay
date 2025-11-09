<?php

use function Livewire\Volt\{state};

state([
    'icon' => 'credit-card',
    'title' => 'Premium Auto-Charge',
    'description' => 'Never worry about collecting payments again. Automatic charges ensure you get paid on time, every time.',
    'estimatedRelease' => 'Q3 2025',
]);

?>

<div>
    <x-app-layout>
        <div class="py-12">
            <div class="mx-auto max-w-5xl px-4 sm:px-6 lg:px-8">
                {{-- Header with Premium Badge --}}
                <div class="mb-8 text-center">
                    <div class="mb-6 inline-flex items-center justify-center">
                        <div class="rounded-full bg-gradient-to-br from-amber-100 to-orange-100 p-8 shadow-lg">
                            <flux:icon.{{ $icon }} class="size-20 text-amber-600" />
                        </div>
                    </div>

                    <div class="mb-4 flex items-center justify-center gap-3">
                        <div class="inline-flex items-center gap-2 rounded-full border-2 border-orange-200 bg-orange-50 px-6 py-2">
                            <flux:icon.clock class="size-5 text-orange-600" />
                            <span class="text-sm font-bold uppercase tracking-widest text-orange-700">Coming Soon</span>
                        </div>
                        <div class="inline-flex items-center gap-2 rounded-full border-2 border-amber-200 bg-amber-50 px-6 py-2">
                            <flux:icon.star class="size-5 text-amber-600" />
                            <span class="text-sm font-bold uppercase tracking-widest text-amber-700">Premium</span>
                        </div>
                    </div>

                    <h1 class="mb-4 text-4xl font-bold tracking-tight text-gray-900">
                        {{ $title }}
                    </h1>

                    <p class="mx-auto mb-6 max-w-2xl text-lg leading-relaxed text-gray-600">
                        {{ $description }}
                    </p>

                    <div class="mb-8 inline-flex items-center gap-3 rounded-xl bg-amber-50 px-6 py-3 shadow-sm">
                        <flux:icon.calendar class="size-5 text-amber-700" />
                        <span class="text-base font-semibold text-amber-900">
                            Expected: {{ $estimatedRelease }}
                        </span>
                    </div>
                </div>

                {{-- Feature Grid --}}
                <div class="mb-8 grid gap-6 md:grid-cols-2">
                    {{-- Secure Card Vaulting --}}
                    <div class="rounded-xl border-2 border-dashed border-purple-200 bg-gradient-to-br from-purple-50 to-white p-6">
                        <div class="mb-4 inline-flex items-center justify-center rounded-full bg-purple-100 p-3">
                            <flux:icon.lock-closed class="size-8 text-purple-600" />
                        </div>
                        <h3 class="mb-2 text-lg font-bold text-gray-900">Secure Card Vaulting</h3>
                        <p class="mb-4 text-sm text-gray-600">
                            Industry-standard encryption protects your payment information. PCI-compliant storage ensures maximum security.
                        </p>
                        <ul class="space-y-2 text-sm text-gray-600">
                            <li class="flex items-start gap-2">
                                <flux:icon.check-circle class="mt-0.5 size-4 flex-shrink-0 text-purple-600" />
                                <span>PCI DSS Level 1 compliance</span>
                            </li>
                            <li class="flex items-start gap-2">
                                <flux:icon.check-circle class="mt-0.5 size-4 flex-shrink-0 text-purple-600" />
                                <span>256-bit encryption</span>
                            </li>
                            <li class="flex items-start gap-2">
                                <flux:icon.check-circle class="mt-0.5 size-4 flex-shrink-0 text-purple-600" />
                                <span>Tokenized card storage</span>
                            </li>
                        </ul>
                    </div>

                    {{-- Automatic Charging --}}
                    <div class="rounded-xl border-2 border-dashed border-blue-200 bg-gradient-to-br from-blue-50 to-white p-6">
                        <div class="mb-4 inline-flex items-center justify-center rounded-full bg-blue-100 p-3">
                            <flux:icon.arrow-path class="size-8 text-blue-600" />
                        </div>
                        <h3 class="mb-2 text-lg font-bold text-gray-900">Automatic Charging</h3>
                        <p class="mb-4 text-sm text-gray-600">
                            Set it and forget it. Charges happen automatically on your billing cycle so you always get paid on time.
                        </p>
                        <ul class="space-y-2 text-sm text-gray-600">
                            <li class="flex items-start gap-2">
                                <flux:icon.check-circle class="mt-0.5 size-4 flex-shrink-0 text-blue-600" />
                                <span>Customizable charge schedules</span>
                            </li>
                            <li class="flex items-start gap-2">
                                <flux:icon.check-circle class="mt-0.5 size-4 flex-shrink-0 text-blue-600" />
                                <span>Pre-charge notifications</span>
                            </li>
                            <li class="flex items-start gap-2">
                                <flux:icon.check-circle class="mt-0.5 size-4 flex-shrink-0 text-blue-600" />
                                <span>Instant payment confirmation</span>
                            </li>
                        </ul>
                    </div>

                    {{-- Smart Retry Logic --}}
                    <div class="rounded-xl border-2 border-dashed border-green-200 bg-gradient-to-br from-green-50 to-white p-6">
                        <div class="mb-4 inline-flex items-center justify-center rounded-full bg-green-100 p-3">
                            <flux:icon.receipt-refund class="size-8 text-green-600" />
                        </div>
                        <h3 class="mb-2 text-lg font-bold text-gray-900">Smart Retry Logic</h3>
                        <p class="mb-4 text-sm text-gray-600">
                            Failed payments are automatically retried with intelligent scheduling to maximize successful charges.
                        </p>
                        <ul class="space-y-2 text-sm text-gray-600">
                            <li class="flex items-start gap-2">
                                <flux:icon.check-circle class="mt-0.5 size-4 flex-shrink-0 text-green-600" />
                                <span>Automatic retry attempts</span>
                            </li>
                            <li class="flex items-start gap-2">
                                <flux:icon.check-circle class="mt-0.5 size-4 flex-shrink-0 text-green-600" />
                                <span>Optimized retry timing</span>
                            </li>
                            <li class="flex items-start gap-2">
                                <flux:icon.check-circle class="mt-0.5 size-4 flex-shrink-0 text-green-600" />
                                <span>Detailed failure reporting</span>
                            </li>
                        </ul>
                    </div>

                    {{-- Payment Notifications --}}
                    <div class="rounded-xl border-2 border-dashed border-orange-200 bg-gradient-to-br from-orange-50 to-white p-6">
                        <div class="mb-4 inline-flex items-center justify-center rounded-full bg-orange-100 p-3">
                            <flux:icon.bell class="size-8 text-orange-600" />
                        </div>
                        <h3 class="mb-2 text-lg font-bold text-gray-900">Payment Notifications</h3>
                        <p class="mb-4 text-sm text-gray-600">
                            Both you and your members receive instant notifications when charges succeed or fail.
                        </p>
                        <ul class="space-y-2 text-sm text-gray-600">
                            <li class="flex items-start gap-2">
                                <flux:icon.check-circle class="mt-0.5 size-4 flex-shrink-0 text-orange-600" />
                                <span>Real-time charge alerts</span>
                            </li>
                            <li class="flex items-start gap-2">
                                <flux:icon.check-circle class="mt-0.5 size-4 flex-shrink-0 text-orange-600" />
                                <span>Email and SMS notifications</span>
                            </li>
                            <li class="flex items-start gap-2">
                                <flux:icon.check-circle class="mt-0.5 size-4 flex-shrink-0 text-orange-600" />
                                <span>Customizable alert preferences</span>
                            </li>
                        </ul>
                    </div>
                </div>

                {{-- Pricing Teaser --}}
                <div class="mb-8 rounded-xl border-2 border-blue-200 bg-gradient-to-br from-blue-50 to-white p-8 text-center">
                    <flux:icon.information-circle class="mx-auto mb-4 size-12 text-blue-600" />
                    <h3 class="mb-3 text-2xl font-bold text-blue-900">Premium Feature</h3>
                    <p class="mx-auto max-w-2xl text-blue-700">
                        Card payments will be available as a premium add-on. Pricing and plans will be announced closer to launch.
                        Early adopters may receive special pricing.
                    </p>
                </div>

                {{-- Benefits Summary --}}
                <div class="mb-8 rounded-xl bg-gradient-to-r from-amber-50 to-orange-50 p-8">
                    <h3 class="mb-6 text-center text-2xl font-bold text-gray-900">Why Upgrade to Auto-Charge?</h3>
                    <div class="grid gap-6 md:grid-cols-3">
                        <div class="text-center">
                            <div class="mb-3 text-4xl font-bold text-amber-600">100%</div>
                            <div class="font-semibold text-gray-900">On-Time Collection</div>
                            <div class="mt-1 text-sm text-gray-600">Never chase payments again</div>
                        </div>
                        <div class="text-center">
                            <div class="mb-3 text-4xl font-bold text-amber-600">5min</div>
                            <div class="font-semibold text-gray-900">Setup Time</div>
                            <div class="mt-1 text-sm text-gray-600">Quick and easy configuration</div>
                        </div>
                        <div class="text-center">
                            <div class="mb-3 text-4xl font-bold text-amber-600">24/7</div>
                            <div class="font-semibold text-gray-900">Automatic Processing</div>
                            <div class="mt-1 text-sm text-gray-600">Works while you sleep</div>
                        </div>
                    </div>
                </div>

                {{-- Actions --}}
                <div class="flex justify-center gap-4">
                    <flux:button variant="primary" disabled>
                        <flux:icon.bell class="size-4" />
                        Notify Me at Launch
                    </flux:button>

                    <flux:button variant="ghost" href="{{ route('app.dashboard') }}" wire:navigate>
                        <flux:icon.arrow-left class="size-4" />
                        Back to Dashboard
                    </flux:button>
                </div>
            </div>
        </div>
    </x-app-layout>
</div>
