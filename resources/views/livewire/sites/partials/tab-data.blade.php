{{-- ─── Tab: Дані ───────────────────────────────────────── --}}
<div x-show="tab==='data'" class="tab-pane">

    {{-- КАТЕГОРІЯ ДАНИХ --}}
    <div class="eyebrow eyebrow-xs" style="margin-bottom:12px;">Категорія даних</div>
    <div class="data-cats">
        @foreach([
            ['key'=>'phones',     'label'=>'Телефони',   'count'=>$phoneCount,   'icon'=>'phone'],
            ['key'=>'messengers', 'label'=>'Месенджери', 'count'=>$msgCount,     'icon'=>'msg'],
            ['key'=>'prices',     'label'=>'Ціни',       'count'=>$priceCount,   'icon'=>'price'],
            ['key'=>'addresses',  'label'=>'Адреси',     'count'=>$addressCount, 'icon'=>'address'],
            ['key'=>'socials',    'label'=>'Соц. мережі','count'=>$socialCount,  'icon'=>'social'],
        ] as $cat)
            <button wire:click="$set('category','{{ $cat['key'] }}')"
                    class="filter-pill {{ $category===$cat['key'] ? 'is-active' : '' }}">
                {{ $cat['label'] }}
                <span class="pill-count">{{ $cat['count'] }}</span>
            </button>
        @endforeach
        <button wire:click="$set('category','custom')"
                class="filter-pill {{ $category==='custom' ? 'is-active' : '' }}">
            + Custom <span class="pill-count">0</span>
        </button>
    </div>

    {{-- Hint --}}
    <div class="data-hint">
        <span class="data-hint__dot"></span>
        <span><strong style="color:var(--ink-7);">Основні</strong> — телефони і месенджери. Інші — ціни, адреси, соцмережі.</span>
    </div>

    {{-- ПЕРЕГЛЯД + geo filter --}}
    <div class="view-row">
        <span class="eyebrow eyebrow-xs">Перегляд</span>
        <div class="seg">
            @php
                $totalFiltered = $category==='phones' ? $allPhonesAll->count() : ($category==='messengers' ? $allMsgsAll->count() : $priceCount);
            @endphp
            @foreach([
                ['key'=>'all',   'label'=>'Усі '.$totalFiltered],
                ['key'=>'world', 'label'=>'🌐 Світ'],
                ['key'=>'PL',    'label'=>'🇵🇱 PL'],
                ['key'=>'UA',    'label'=>'🇺🇦 UA'],
            ] as $geo)
                <button wire:click="$set('geoFilter','{{ $geo['key'] }}')"
                        class="seg__btn {{ $geoFilter===$geo['key'] ? 'is-active' : '' }}">
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
        <div class="data-empty">Цей розділ у розробці.</div>
    @endif

</div>
