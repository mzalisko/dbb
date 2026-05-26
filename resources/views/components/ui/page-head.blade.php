@props([
    'eyebrow' => null,
    'title'   => null,
    'number'  => null,
    'label'   => null,
    'sub'     => null,
])

<header style="padding:40px 40px 28px; flex-shrink:0;">
    @if ($eyebrow)
        <div class="eyebrow">{{ $eyebrow }}</div>
    @endif
    <div style="margin-top:{{ $eyebrow ? '14px' : '0' }}; display:flex; align-items:flex-end; gap:20px;">
        @if ($number !== null)
            <h1 style="font:400 36px/1.05 var(--font-sans); letter-spacing:-0.03em; flex:1;">
                <span style="color:var(--ink-9);">{{ $number }}</span>
                <span style="color:var(--ink-5);"> {{ $label }}</span>
            </h1>
        @else
            <h1 style="font:400 36px/1.05 var(--font-sans); letter-spacing:-0.03em; color:var(--ink-9); flex:1;">{{ $title }}</h1>
        @endif
        @if (isset($actions))
            <div style="display:flex; gap:8px; align-items:center; padding-bottom:4px;">{{ $actions }}</div>
        @endif
    </div>
    @if ($sub)
        <p style="margin-top:12px; font:14.5px/1.55 var(--font-sans); color:var(--ink-5); max-width:580px;">{{ $sub }}</p>
    @endif
</header>
