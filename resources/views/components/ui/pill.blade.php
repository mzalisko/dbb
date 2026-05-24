@props(['status' => null])

@php
    $cls = 'pill';
    if ($status) $cls .= ' pill-' . $status;
@endphp

<span {{ $attributes->merge(['class' => $cls]) }}>
    @if ($status)
        <span class="dot dot-{{ $status }}" style="margin:0;"></span>
    @endif
    {{ $slot }}
</span>
