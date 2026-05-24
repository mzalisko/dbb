@props(['padding' => true])

<div {{ $attributes->merge(['class' => 'card' . ($padding ? '' : '')]) }}
     style="{{ $padding ? 'padding:20px 24px;' : '' }}{{ $attributes->get('style', '') }}">
    {{ $slot }}
</div>
