@blaze

@props([
    'variant' => 'info',
    'icon' => null,
])

@php
$variants = [
    'success' => [
        'classes' => 'bg-green-50 text-green-800 ring-green-200 dark:bg-green-900/30 dark:text-green-200 dark:ring-green-800/60',
        'icon' => 'check-circle',
        'iconClass' => 'text-green-500 dark:text-green-300',
    ],
    'warning' => [
        'classes' => 'bg-yellow-50 text-yellow-800 ring-yellow-200 dark:bg-yellow-900/30 dark:text-yellow-100 dark:ring-yellow-800/60',
        'icon' => 'exclamation-triangle',
        'iconClass' => 'text-yellow-600 dark:text-yellow-300',
    ],
    'danger' => [
        'classes' => 'bg-red-50 text-red-800 ring-red-200 dark:bg-red-900/30 dark:text-red-100 dark:ring-red-800/60',
        'icon' => 'x-circle',
        'iconClass' => 'text-red-500 dark:text-red-300',
    ],
    'info' => [
        'classes' => 'bg-blue-50 text-blue-800 ring-blue-200 dark:bg-blue-900/30 dark:text-blue-100 dark:ring-blue-800/60',
        'icon' => 'information-circle',
        'iconClass' => 'text-blue-500 dark:text-blue-300',
    ],
];

$styles = $variants[$variant] ?? $variants['info'];
$icon ??= $styles['icon'];

$classes = Flux::classes()
    ->add('flex items-start gap-3 rounded-xl p-4 ring-1')
    ->add($styles['classes']);

$iconClasses = Flux::classes()
    ->add('mt-0.5 size-5')
    ->add($styles['iconClass']);
@endphp

<div {{ $attributes->class($classes) }} data-flux-banner>
    <?php if ($icon): ?>
        <flux:icon :icon="$icon" variant="mini" :class="$iconClasses" />
    <?php endif; ?>

    <div class="flex-1 text-sm leading-6">
        {{ $slot }}
    </div>
</div>
