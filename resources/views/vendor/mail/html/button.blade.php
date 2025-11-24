@props([
    'url',
    'color' => 'primary',
    'align' => 'center',
])
<div class="button-container">
    <a href="{{ $url }}" class="button button-{{ $color }}" target="_blank" rel="noopener">{{ $slot }}</a>
</div>
