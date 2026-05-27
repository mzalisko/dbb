{{-- ─── Tab: Дані ───────────────────────────────────────── --}}
<div x-show="tab==='data'" class="tab-pane">

    {{-- КАТЕГОРІЯ ДАНИХ --}}
    <div class="eyebrow eyebrow-xs" style="margin-bottom:12px;">Категорія даних</div>
    <div class="data-cats">
        @foreach([
            ['key'=>'phones',     'label'=>'Телефони',   'count'=>$phoneCount],
            ['key'=>'messengers', 'label'=>'Месенджери', 'count'=>$msgCount],
            ['key'=>'prices',     'label'=>'Ціни',       'count'=>$priceCount],
            ['key'=>'addresses',  'label'=>'Адреси',     'count'=>$addressCount],
            ['key'=>'socials',    'label'=>'Соц. мережі','count'=>$socialCount],
        ] as $cat)
            <button @click="cat='{{ $cat['key'] }}'"
                    :class="cat==='{{ $cat['key'] }}' ? 'is-active' : ''"
                    class="filter-pill">
                {{ $cat['label'] }}
                <span class="pill-count">{{ $cat['count'] }}</span>
            </button>
        @endforeach
        <button @click="cat='custom'" :class="cat==='custom' ? 'is-active' : ''" class="filter-pill">
            + Custom <span class="pill-count">0</span>
        </button>
    </div>

    {{-- ПЕРЕГЛЯД + geo filter --}}
    <div class="view-row">
        <span class="eyebrow eyebrow-xs">Перегляд</span>
        <div class="seg">
            <button @click="geo='all'" :class="geo==='all' ? 'is-active' : ''" class="seg__btn">
                Усі <span x-text="cat==='phones' ? {{ $allPhonesAll->count() }} : (cat==='messengers' ? {{ $allMsgsAll->count() }} : {{ $priceCount }})"></span>
            </button>
            <button @click="geo='world'" :class="geo==='world' ? 'is-active' : ''" class="seg__btn">🌐 Світ</button>
            <button @click="geo='PL'" :class="geo==='PL' ? 'is-active' : ''" class="seg__btn">🇵🇱 PL</button>
            <button @click="geo='UA'" :class="geo==='UA' ? 'is-active' : ''" class="seg__btn">🇺🇦 UA</button>
        </div>
    </div>

    {{-- ── ТЕЛЕФОНИ ── --}}
    <div x-show="cat==='phones'">
        @foreach(['all', 'world', 'PL', 'UA'] as $geoKey)
            <div x-show="geo==='{{ $geoKey }}'">
                @include('livewire.sites.partials.data-phones', ['phonePrimaries' => $phonePrimariesByGeo[$geoKey]])
            </div>
        @endforeach
    </div>

    {{-- ── МЕСЕНДЖЕРИ ── --}}
    <div x-show="cat==='messengers'">
        @foreach(['all', 'world', 'PL', 'UA'] as $geoKey)
            <div x-show="geo==='{{ $geoKey }}'">
                @include('livewire.sites.partials.data-messengers', ['msgPrimaries' => $msgPrimariesByGeo[$geoKey]])
            </div>
        @endforeach
    </div>

    {{-- ── ЦІНИ ── --}}
    <div x-show="cat==='prices'">
        @include('livewire.sites.partials.data-prices')
    </div>

    {{-- Адреси / Соц. мережі / Custom --}}
    <div x-show="['addresses','socials','custom'].includes(cat)" class="data-empty">Цей розділ у розробці.</div>

</div>
