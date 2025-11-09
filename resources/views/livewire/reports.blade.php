<?php

use function Livewire\Volt\{state};

state([
    'icon' => 'document-chart-bar',
    'title' => 'Reports & Analytics',
    'description' => 'Get detailed insights into your group payment patterns, collection rates, member performance, and financial summaries. Export data as PDF or CSV for your records.',
    'estimatedRelease' => 'Q2 2025',
]);

?>

<div>
    <x-app-layout>
        <div class="py-12">
            <div class="mx-auto max-w-4xl px-4 sm:px-6 lg:px-8">
                {{-- Header --}}
                <div class="mb-8 text-center">
                    <div class="mb-6 inline-flex items-center justify-center">
                        <div class="rounded-full bg-gradient-to-br from-indigo-100 to-purple-100 p-8 shadow-lg">
                            <flux:icon.{{ $icon }} class="size-20 text-indigo-600" />
                        </div>
                    </div>

                    <div class="mb-4 inline-flex items-center gap-2 rounded-full border-2 border-orange-200 bg-orange-50 px-6 py-2">
                        <flux:icon.clock class="size-5 text-orange-600" />
                        <span class="text-sm font-bold uppercase tracking-widest text-orange-700">Coming Soon</span>
                    </div>

                    <h1 class="mb-4 text-4xl font-bold tracking-tight text-gray-900">
                        {{ $title }}
                    </h1>

                    <p class="mx-auto mb-6 max-w-2xl text-lg leading-relaxed text-gray-600">
                        {{ $description }}
                    </p>

                    <div class="mb-8 inline-flex items-center gap-3 rounded-xl bg-indigo-50 px-6 py-3 shadow-sm">
                        <flux:icon.calendar class="size-5 text-indigo-700" />
                        <span class="text-base font-semibold text-indigo-900">
                            Expected: {{ $estimatedRelease }}
                        </span>
                    </div>
                </div>

                {{-- Feature Preview --}}
                <div class="mb-8 grid gap-6 md:grid-cols-2">
                    <div class="rounded-xl border-2 border-dashed border-gray-200 bg-white p-6">
                        <flux:icon.chart-bar class="mb-4 size-10 text-indigo-600" />
                        <h3 class="mb-2 text-lg font-bold text-gray-900">Payment Analytics</h3>
                        <ul class="space-y-2 text-sm text-gray-600">
                            <li class="flex items-start gap-2">
                                <flux:icon.check-circle class="mt-0.5 size-4 flex-shrink-0 text-green-600" />
                                <span>Track collection rates over time</span>
                            </li>
                            <li class="flex items-start gap-2">
                                <flux:icon.check-circle class="mt-0.5 size-4 flex-shrink-0 text-green-600" />
                                <span>Identify late payment patterns</span>
                            </li>
                            <li class="flex items-start gap-2">
                                <flux:icon.check-circle class="mt-0.5 size-4 flex-shrink-0 text-green-600" />
                                <span>View payment method preferences</span>
                            </li>
                        </ul>
                    </div>

                    <div class="rounded-xl border-2 border-dashed border-gray-200 bg-white p-6">
                        <flux:icon.user-group class="mb-4 size-10 text-purple-600" />
                        <h3 class="mb-2 text-lg font-bold text-gray-900">Member Performance</h3>
                        <ul class="space-y-2 text-sm text-gray-600">
                            <li class="flex items-start gap-2">
                                <flux:icon.check-circle class="mt-0.5 size-4 flex-shrink-0 text-green-600" />
                                <span>On-time payment percentages</span>
                            </li>
                            <li class="flex items-start gap-2">
                                <flux:icon.check-circle class="mt-0.5 size-4 flex-shrink-0 text-green-600" />
                                <span>Average payment delay</span>
                            </li>
                            <li class="flex items-start gap-2">
                                <flux:icon.check-circle class="mt-0.5 size-4 flex-shrink-0 text-green-600" />
                                <span>Member contribution heatmaps</span>
                            </li>
                        </ul>
                    </div>

                    <div class="rounded-xl border-2 border-dashed border-gray-200 bg-white p-6">
                        <flux:icon.document-arrow-down class="mb-4 size-10 text-blue-600" />
                        <h3 class="mb-2 text-lg font-bold text-gray-900">Export Options</h3>
                        <ul class="space-y-2 text-sm text-gray-600">
                            <li class="flex items-start gap-2">
                                <flux:icon.check-circle class="mt-0.5 size-4 flex-shrink-0 text-green-600" />
                                <span>PDF formatted reports</span>
                            </li>
                            <li class="flex items-start gap-2">
                                <flux:icon.check-circle class="mt-0.5 size-4 flex-shrink-0 text-green-600" />
                                <span>CSV data exports</span>
                            </li>
                            <li class="flex items-start gap-2">
                                <flux:icon.check-circle class="mt-0.5 size-4 flex-shrink-0 text-green-600" />
                                <span>Scheduled email reports</span>
                            </li>
                        </ul>
                    </div>

                    <div class="rounded-xl border-2 border-dashed border-gray-200 bg-white p-6">
                        <flux:icon.calendar class="mb-4 size-10 text-green-600" />
                        <h3 class="mb-2 text-lg font-bold text-gray-900">Historical Data</h3>
                        <ul class="space-y-2 text-sm text-gray-600">
                            <li class="flex items-start gap-2">
                                <flux:icon.check-circle class="mt-0.5 size-4 flex-shrink-0 text-green-600" />
                                <span>Year-over-year comparisons</span>
                            </li>
                            <li class="flex items-start gap-2">
                                <flux:icon.check-circle class="mt-0.5 size-4 flex-shrink-0 text-green-600" />
                                <span>Billing cycle summaries</span>
                            </li>
                            <li class="flex items-start gap-2">
                                <flux:icon.check-circle class="mt-0.5 size-4 flex-shrink-0 text-green-600" />
                                <span>Trend analysis</span>
                            </li>
                        </ul>
                    </div>
                </div>

                {{-- Actions --}}
                <div class="flex justify-center gap-4">
                    <flux:button variant="primary" disabled>
                        <flux:icon.bell class="size-4" />
                        Notify Me When Ready
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
