@props([
    'name'     => '',
    'maxWidth' => 'md',
])

@php
    $maxWidthPx = match ($maxWidth) {
        'sm' => '380px',
        'lg' => '720px',
        'xl' => '960px',
        default => '520px',
    };
@endphp

<div
    wire:ignore.self
    x-data="{ open: false }"
    x-on:open-modal.window="const _n='{{ $name }}'; const _d=$event.detail; if(_d===_n||(Array.isArray(_d)&&_d[0]===_n)) open = true"
    x-on:close-modal.window="const _n='{{ $name }}'; const _d=$event.detail; if(_d===_n||(Array.isArray(_d)&&_d[0]===_n)) open = false"
    @keydown.escape.window="open = false"
    x-show="open"
    x-cloak
>
    {{-- Backdrop --}}
    <div
        class="modal-backdrop"
        x-show="open"
        x-transition:enter="transition ease-out duration-200"
        x-transition:enter-start="opacity-0"
        x-transition:enter-end="opacity-100"
        x-transition:leave="transition ease-in duration-150"
        x-transition:leave-start="opacity-100"
        x-transition:leave-end="opacity-0"
        @click="open = false"
    >
        {{-- Modal --}}
        <div
            class="modal"
            style="max-width:{{ $maxWidthPx }};"
            x-show="open"
            x-transition:enter="transition ease-out duration-200"
            x-transition:enter-start="opacity-0 scale-[.97]"
            x-transition:enter-end="opacity-100 scale-100"
            x-transition:leave="transition ease-in duration-150"
            x-transition:leave-start="opacity-100 scale-100"
            x-transition:leave-end="opacity-0 scale-[.97]"
            @click.stop
        >
            @if (isset($title))
                <div class="modal-header">
                    <span>{{ $title }}</span>
                    <button @click="open = false" style="color:var(--ink-5);">
                        <x-icon.close width="18" height="18" />
                    </button>
                </div>
            @endif

            <div class="modal-body">
                {{ $slot }}
            </div>

            @if (isset($footer))
                <div class="modal-footer">
                    {{ $footer }}
                </div>
            @endif
        </div>
    </div>
</div>