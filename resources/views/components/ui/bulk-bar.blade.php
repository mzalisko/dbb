@props([
    'count' => 0,
    'total' => null,
    'allMatching' => false,
])

{{-- Sticky contextual bulk-action bar (Gmail / Linear style). Wrap in @if(hasSelection). --}}
<div class="bulk-bar" x-cloak>
    <span class="bulk-bar__count mono">{{ $count }} обрано</span>

    @if($total !== null && $total > $count && ! $allMatching)
        <button class="bulk-bar__all" wire:click="selectAllMatching">
            Обрати всі {{ $total }}
        </button>
    @elseif($allMatching && $total !== null)
        <span class="bulk-bar__all-note">усі {{ $total }} за фільтром</span>
    @endif

    <span class="bulk-bar__sep"></span>

    <div class="bulk-bar__actions">
        {{ $slot }}
    </div>

    <div class="bulk-bar__spacer"></div>

    <button class="bulk-bar__clear" wire:click="clearSelected">Зняти виділення</button>
</div>
