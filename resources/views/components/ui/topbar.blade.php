@props(['crumbs' => []])

<header style="position:relative; z-index:1; display:flex; align-items:center; gap:16px; height:56px; padding:0 32px; border-bottom:1px solid var(--ink-3); background:var(--paper); flex-shrink:0;">
    <div style="flex:1; display:flex; align-items:center; gap:8px;">
        @foreach ($crumbs as $i => $crumb)
            @if ($i > 0)
                <span style="color:var(--ink-4);">/</span>
            @endif
            <span style="font:13.5px var(--font-sans); color:{{ $i === count($crumbs) - 1 ? 'var(--ink-9)' : 'var(--ink-5)' }};">
                {{ $crumb }}
            </span>
        @endforeach
    </div>
    {{ $slot }}
    <button style="width:32px; height:32px; border-radius:6px; color:var(--ink-5); display:inline-flex; align-items:center; justify-content:center;">
        <x-icon.bell width="14" height="14" />
    </button>
</header>
