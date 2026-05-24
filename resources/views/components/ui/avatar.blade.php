@props([
    'initials' => '?',
    'size'     => null,
    'square'   => false,
    'src'      => null,
])

@php
    $cls = 'avatar';
    if ($size === 'lg') $cls .= ' avatar-lg';
    if ($square)        $cls .= ' avatar-sq';
@endphp

<span {{ $attributes->merge(['class' => $cls]) }}>
    @if ($src)
        <img src="{{ $src }}" alt="{{ $initials }}" style="width:100%;height:100%;object-fit:cover;border-radius:inherit;">
    @else
        {{ strtoupper(substr($initials, 0, 2)) }}
    @endif
</span>
