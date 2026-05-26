{{-- ─── Tab: Дані ───────────────────────────────────────── --}}
<div x-show="tab==='data'" style="padding:24px 40px 64px;">

    {{-- КАТЕГОРІЯ ДАНИХ --}}
    <div class="eyebrow" style="font-size:10px; margin-bottom:12px;">Категорія даних</div>
    <div style="display:flex; gap:6px; flex-wrap:wrap;">
        @foreach([
            ['key'=>'phones',     'label'=>'Телефони',   'count'=>$phoneCount,   'icon'=>'phone'],
            ['key'=>'messengers', 'label'=>'Месенджери', 'count'=>$msgCount,     'icon'=>'msg'],
            ['key'=>'prices',     'label'=>'Ціни',       'count'=>$priceCount,   'icon'=>'price'],
            ['key'=>'addresses',  'label'=>'Адреси',     'count'=>$addressCount, 'icon'=>'address'],
            ['key'=>'socials',    'label'=>'Соц. мережі','count'=>$socialCount,  'icon'=>'social'],
        ] as $cat)
            <button wire:click="$set('category','{{ $cat['key'] }}')" style="
                display:inline-flex; align-items:center; gap:6px; height:32px; padding:0 14px; border-radius:999px; cursor:pointer;
                background:{{ $category===$cat['key']?'var(--ink-9)':'var(--card)' }};
                color:{{ $category===$cat['key']?'var(--paper)':'var(--ink-7)' }};
                box-shadow:{{ $category===$cat['key']?'none':'inset 0 0 0 1px var(--ink-3)' }};
                font:13px var(--font-sans);">
                {{ $cat['label'] }}
                <span style="font:10.5px var(--font-mono); opacity:.7;">{{ $cat['count'] }}</span>
            </button>
        @endforeach
        <button wire:click="$set('category','custom')" style="
            display:inline-flex; align-items:center; gap:6px; height:32px; padding:0 14px; border-radius:999px; cursor:pointer;
            background:{{ $category==='custom'?'var(--ink-9)':'var(--card)' }};
            color:{{ $category==='custom'?'var(--paper)':'var(--ink-7)' }};
            box-shadow:{{ $category==='custom'?'none':'inset 0 0 0 1px var(--ink-3)' }};
            font:13px var(--font-sans);">
            + Custom <span style="font:10.5px var(--font-mono);opacity:.7;">0</span>
        </button>
    </div>

    {{-- Hint --}}
    <div style="margin-top:10px; font:12px var(--font-sans); color:var(--ink-5); display:flex; align-items:center; gap:6px;">
        <span style="width:6px;height:6px;border-radius:999px;background:var(--ink-5);flex-shrink:0;"></span>
        <span><strong style="color:var(--ink-7);">Основні</strong> — телефони і месенджери. Інші — ціни, адреси, соцмережі.</span>
    </div>

    {{-- ПЕРЕГЛЯД + geo filter --}}
    <div style="margin-top:20px; display:flex; align-items:center; gap:16px;">
        <span class="eyebrow" style="font-size:10px;">Перегляд</span>
        <div style="display:flex; gap:4px; padding:3px; background:var(--ink-2); border-radius:6px;">
            @php
                $totalFiltered = $category==='phones' ? $allPhonesAll->count() : ($category==='messengers' ? $allMsgsAll->count() : $priceCount);
            @endphp
            @foreach([
                ['key'=>'all',   'label'=>'Усі '.$totalFiltered],
                ['key'=>'world', 'label'=>'🌐 Світ'],
                ['key'=>'PL',    'label'=>'🇵🇱 PL'],
                ['key'=>'UA',    'label'=>'🇺🇦 UA'],
            ] as $geo)
                <button wire:click="$set('geoFilter','{{ $geo['key'] }}')" style="
                    padding:5px 12px; border-radius:4px; font:500 12px var(--font-sans); cursor:pointer;
                    color:{{ $geoFilter===$geo['key']?'var(--ink-9)':'var(--ink-5)' }};
                    background:{{ $geoFilter===$geo['key']?'var(--card)':'transparent' }};
                    box-shadow:{{ $geoFilter===$geo['key']?'0 1px 1px rgba(0,0,0,.04)':'none' }};">
                    {{ $geo['label'] }}
                </button>
            @endforeach
        </div>
    </div>

    {{-- ── ТЕЛЕФОНИ ── --}}
    @if($category==='phones')
        @include('livewire.sites.partials.data-phones')
    @endif

    {{-- ── МЕСЕНДЖЕРИ ── --}}
    @if($category==='messengers')
        @include('livewire.sites.partials.data-messengers')
    @endif

    {{-- ── ЦІНИ ── --}}
    @if($category==='prices')
        @include('livewire.sites.partials.data-prices')
    @endif

    {{-- Адреси / Соц. мережі / Custom --}}
    @if(in_array($category,['addresses','socials','custom']))
        <div style="margin-top:48px; padding:80px; text-align:center; color:var(--ink-5); font:13.5px var(--font-sans);">
            Цей розділ у розробці.
        </div>
    @endif

</div>
