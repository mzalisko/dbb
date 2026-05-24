@props([
    'variant' => 'info',
])

@php
    $variantMap = [
        'info'    => 'alert-info',
        'success' => 'alert-ok',
        'warning' => 'alert-warn',
        'danger'  => 'alert-bad',
    ];
    $iconMap = [
        'info'    => 'info',
        'success' => 'success',
        'warning' => 'warning',
        'danger'  => 'error',
    ];
    $cls = 'alert ' . ($variantMap[$variant] ?? 'alert-info');
    $icon = $iconMap[$variant] ?? 'info';
@endphp

<div {{ $attributes->merge(['class' => $cls]) }}>
    <x-dynamic-component :component="'icon.' . $icon" width="18" height="18" style="flex-shrink:0;margin-top:1px;" />
    <div>{{ $slot }}</div>
</div>