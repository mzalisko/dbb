@props([
    'size' => 'md',
])

@php
    $cls = 'spinner';
    if ($size === 'sm') $cls .= ' spinner-sm';
    if ($size === 'lg') $cls .= ' spinner-lg';
@endphp

<span {{ $attributes->merge(['class' => $cls]) }}></span>