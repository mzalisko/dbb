@props(['crumbs' => [], 'back' => false, 'backHref' => null])

<header style="position:relative; z-index:1; display:flex; align-items:center; gap:16px; height:56px; padding:0 32px; border-bottom:1px solid var(--ink-3); background:var(--paper); flex-shrink:0;">
    <div style="flex:1; display:flex; align-items:center; gap:8px; min-width:0;">
        @if ($back)
            @if ($backHref)
                <a href="{{ $backHref }}" wire:navigate class="icon-btn" style="margin-left:-6px;" title="Назад" aria-label="Назад">
                    <x-icon.chevron-left width="17" height="17" />
                </a>
            @else
                <button onclick="history.back()" class="icon-btn" style="margin-left:-6px;" title="Назад" aria-label="Назад">
                    <x-icon.chevron-left width="17" height="17" />
                </button>
            @endif
        @endif
        @foreach ($crumbs as $i => $crumb)
            @if ($i > 0)
                <span style="color:var(--ink-4);">/</span>
            @endif
            <span style="font:13.5px var(--font-sans); color:{{ $i === count($crumbs) - 1 ? 'var(--ink-9)' : 'var(--ink-5)' }};">
                {{ $crumb }}
            </span>
        @endforeach
    </div>

    @if ($slot->hasActualContent())
        <div style="display:flex; align-items:center; gap:8px;">
            {{ $slot }}
        </div>
    @endif
</header>
