@props([
    'open'  => false,
    'title' => '',
    'sub'   => null,
    'width' => 480,
])

<div x-data="{ show: false }" x-init="$nextTick(() => { show = @js($open) })"
     @keydown.escape.window="show = false; $dispatch('drawer-close')"
     style="display:contents;">

    {{-- Backdrop --}}
    <div x-show="show"
         x-transition:enter="transition ease-out duration-200"
         x-transition:enter-start="opacity-0"
         x-transition:enter-end="opacity-100"
         x-transition:leave="transition ease-in duration-150"
         x-transition:leave-start="opacity-100"
         x-transition:leave-end="opacity-0"
         @click="show = false; $dispatch('drawer-close')"
         style="position:fixed; inset:0; background:rgba(0,0,0,.4); z-index:60;"
         x-cloak></div>

    {{-- Panel outer (x-show controls visibility; display value is irrelevant for layout) --}}
    <div x-show="show"
         x-transition:enter="transition ease-out duration-250"
         x-transition:enter-start="opacity-0 translate-x-5"
         x-transition:enter-end="opacity-100 translate-x-0"
         x-transition:leave="transition ease-in duration-200"
         x-transition:leave-start="opacity-100 translate-x-0"
         x-transition:leave-end="opacity-0 translate-x-5"
         style="position:fixed; top:0; right:0; bottom:0; width:{{ $width }}px; max-width:100vw; z-index:61;"
         x-cloak>

        {{-- Panel inner — завжди flex, незалежно від того що Alpine робить з outer display --}}
        <div style="display:flex; flex-direction:column; height:100%; background:var(--card); border-left:1px solid var(--ink-3);">

            {{-- Header --}}
            <div style="padding:28px 28px 0; flex-shrink:0;">
                <div style="display:flex; align-items:flex-start; justify-content:space-between; gap:12px;">
                    <div>
                        <h2 style="font:400 22px/1.1 var(--font-sans); color:var(--ink-9); letter-spacing:-0.02em;">{{ $title }}</h2>
                        @if ($sub)
                            <div class="mono" style="margin-top:6px; font:12px var(--font-mono); color:var(--ink-5);">{{ $sub }}</div>
                        @endif
                    </div>
                    <button @click="show = false; $dispatch('drawer-close')"
                        style="width:28px; height:28px; border-radius:999px; color:var(--ink-5); display:inline-flex; align-items:center; justify-content:center; flex-shrink:0; cursor:pointer; transition:color .12s; margin-top:2px;"
                        onmouseover="this.style.color='var(--ink-9)'" onmouseout="this.style.color='var(--ink-5)'">
                        <x-icon.close width="16" height="16" />
                    </button>
                </div>
                <div style="margin-top:20px; border-bottom:1px solid var(--ink-3);"></div>
            </div>

            {{-- Body --}}
            <div style="flex:1; overflow-y:auto; padding:24px 28px;">
                {{ $slot }}
            </div>

            {{-- Footer --}}
            @if (isset($footer))
                <div style="flex-shrink:0; padding:16px 28px; border-top:1px solid var(--ink-3); display:flex; align-items:center; justify-content:flex-end; gap:8px; background:var(--paper-2);">
                    {{ $footer }}
                </div>
            @endif

        </div>
    </div>
</div>
