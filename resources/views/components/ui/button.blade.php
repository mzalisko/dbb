@props([
    'variant' => 'primary',
    'size'    => null,
    'type'    => 'button',
    'href'    => null,
])

@php
    $classes = 'btn btn-' . $variant;
    if ($size) $classes .= ' btn-' . $size;
@endphp

@if ($href)
    <a href="{{ $href }}" {{ $attributes->merge(['class' => $classes]) }}>{{ $slot }}</a>
@else
    <button type="{{ $type }}" {{ $attributes->merge(['class' => $classes]) }}>{{ $slot }}</button>
@endif
