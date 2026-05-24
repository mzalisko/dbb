@props([
    'align' => 'left',
    'width' => '48',
])

@php
    $alignStyle = $align === 'right' ? 'right:0;' : 'left:0;';
    $widthPx = $width * 4;
    $widthStyle = "min-width:{$widthPx}px;";
@endphp

<div
    x-data="{ open: false }"
    @click.away="open = false"
    x-on:close-dropdown.window="open = false"
    style="position:relative;display:inline-block;"
>
    {{-- Trigger --}}
    <div @click="open = !open" style="cursor:pointer;">
        {{ $trigger }}
    </div>

    {{-- Dropdown content --}}
    <div
        class="dropdown"
        x-show="open"
        x-transition:enter="fade-in"
        x-cloak
        style="{{ $alignStyle }}{{ $widthStyle }}top:100%;margin-top:4px;display:none;"
    >
        {{ $slot }}
    </div>
</div>