{{--
    Coming Soon Card Component

    Usage:
    <livewire:components.coming-soon-card
        icon="document-chart-bar"
        title="PDF Reports"
        description="Generate detailed PDF reports of your payment history and group analytics."
        estimated-release="Q2 2025"
    />
--}}

@props([
    'icon' => 'sparkles',
    'title' => 'Coming Soon',
    'description' => 'This feature is currently under development.',
    'estimatedRelease' => null,
    'accentColor' => 'primary',
])

@php
    $colorMap = [
        'primary' => 'blue',
        'success' => 'green',
        'warning' => 'orange',
        'danger' => 'red',
        'purple' => 'purple',
    ];
    $color = $colorMap[$accentColor] ?? 'blue';
@endphp

<div class="relative overflow-hidden rounded-2xl border border-gray-200 bg-gradient-to-br from-{{ $color }}-50/50 to-white p-8 shadow-sm transition-all hover:shadow-md">
    {{-- Header --}}
    <div class="mb-6 flex items-start justify-between">
        {{-- Icon --}}
        <div class="flex-shrink-0">
            <div class="inline-flex items-center justify-center rounded-xl bg-{{ $color }}-100 p-4">
                <flux:icon.{{ $icon }} class="size-8 text-{{ $color }}-600" />
            </div>
        </div>

        {{-- Coming Soon Badge --}}
        <div class="inline-flex items-center gap-2 rounded-full border border-orange-200 bg-orange-50 px-3 py-1.5">
            <flux:icon.clock class="size-4 text-orange-600" />
            <span class="text-xs font-semibold uppercase tracking-wide text-orange-700">Coming Soon</span>
        </div>
    </div>

    {{-- Content --}}
    <div class="space-y-4">
        <h3 class="text-xl font-bold text-gray-900">
            {{ $title }}
        </h3>

        <p class="text-base leading-relaxed text-gray-600">
            {{ $description }}
        </p>

        @if($estimatedRelease)
            <div class="flex items-center gap-2 text-sm text-gray-500">
                <flux:icon.calendar class="size-4" />
                <span>Estimated: <strong class="text-gray-700">{{ $estimatedRelease }}</strong></span>
            </div>
        @endif
    </div>

    {{-- Optional Action --}}
    <div class="mt-6">
        <flux:button variant="outline" size="sm" disabled>
            <flux:icon.bell class="size-4" />
            Notify Me When Ready
        </flux:button>
    </div>

    {{-- Decorative Element --}}
    <div class="pointer-events-none absolute -right-8 -top-8 size-32 rounded-full bg-{{ $color }}-100 opacity-20 blur-3xl"></div>
</div>
