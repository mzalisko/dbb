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
    x-data="{ open: false }"
    x-on:open-modal.window="if ($event.detail === '{{ $name }}') open = true"
    x-on:close-modal.window="if ($event.detail === '{{ $name }}') open = false"
    @keydown.escape.window="open = false"
    x-show="open"
    x-cloak
    style="display:none;"
>
    {{-- Backdrop --}}
    <div
        class="modal-backdrop"
        x-show="open"
        x-transition:enter="fade-in"
        @click="open = false"
    >
        {{-- Modal --}}
        <div
            class="modal"
            style="max-width:{{ $maxWidthPx }};"
            x-show="open"
            x-transition:enter="fade-in"
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