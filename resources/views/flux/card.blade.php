@blaze

@props([
    'padding' => 'p-6',
])

@php
$classes = Flux::classes()
    ->add('rounded-2xl bg-white ring-1 ring-zinc-200 shadow-sm dark:bg-zinc-900 dark:ring-zinc-800')
    ->add($padding);
@endphp

<div {{ $attributes->class($classes) }} data-flux-card>
    {{ $slot }}
</div>
