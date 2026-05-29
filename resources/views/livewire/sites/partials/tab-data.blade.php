{{-- ─── Tab: Дані ───────────────────────────────────────── --}}
<div x-show="tab==='data'" x-cloak class="tab-pane">

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
            @if(in_array($cat['key'], $dataCategories, true))
                <button @click="cat='{{ $cat['key'] }}'"
                        :class="cat==='{{ $cat['key'] }}' ? 'is-active' : ''"
                        class="filter-pill">
                    {{ $cat['label'] }}
                    <span class="pill-count">{{ $cat['count'] }}</span>
                </button>
            @endif
        @endforeach
        @if(in_array('custom', $dataCategories, true))
            <button @click="cat='custom'" :class="cat==='custom' ? 'is-active' : ''" class="filter-pill">
                + Custom <span class="pill-count">0</span>
            </button>
        @endif
    </div>

    {{-- ПЕРЕГЛЯД + geo filter --}}
    <div class="view-row">
        <span class="eyebrow eyebrow-xs">Перегляд</span>
        <div class="seg">
            <button @click="geo='all'" :class="geo==='all' ? 'is-active' : ''" class="seg__btn">
                Всі <span x-text="cat==='phones' ? {{ $allPhonesAll->count() }} : (cat==='messengers' ? {{ $allMsgsAll->count() }} : {{ $priceCount }})"></span>
            </button>
            @foreach($geoTabs as $geoCode)
                <span class="seg__tab">
                    <button @click="geo='{{ $geoCode }}'" :class="geo==='{{ $geoCode }}' ? 'is-active' : ''" class="seg__btn">{{ $geoCode }}</button>
                    <button class="seg__tab-x"
                            @click.stop="if (geo === '{{ $geoCode }}') geo = 'all'; $wire.requestRemoveGeoTab('{{ $geoCode }}');"
                            title="Видалити вкладку">×</button>
                </span>
            @endforeach
            <span class="seg__add" x-data="{open:false}">
                <button @click="open=!open" class="seg__add-btn" :class="open?'is-active':''" title="Додати країну">+</button>
                <div x-show="open" x-cloak class="seg__add-pop" @click.outside="open=false">
                    <input wire:model="newGeoTab"
                           class="seg__add-input"
                           placeholder="PL"
                           maxlength="3"
                           @keydown.enter.prevent="$wire.addGeoTab(); open=false"
                           @keydown.escape="open=false">
                    <button wire:click="addGeoTab()" @click="open=false" class="seg__add-ok">OK</button>
                </div>
            </span>
        </div>
    </div>

    {{-- ── ТЕЛЕФОНИ ── --}}
    <div x-show="cat==='phones'" x-cloak>
        @foreach(array_merge(['all'], $geoTabs) as $geoKey)
            <div x-show="geo==='{{ $geoKey }}'">
                @include('livewire.sites.partials.data-phones', [
                    'phonePrimaries' => $phonePrimariesByGeo[$geoKey],
                    'hiddenPhones' => $hiddenPhonesByGeo[$geoKey],
                ])
            </div>
        @endforeach
    </div>

    {{-- ── МЕСЕНДЖЕРИ ── --}}
    <div x-show="cat==='messengers'" x-cloak>
        @foreach(array_merge(['all'], $geoTabs) as $geoKey)
            <div x-show="geo==='{{ $geoKey }}'">
                @include('livewire.sites.partials.data-messengers', [
                    'msgPrimaries' => $msgPrimariesByGeo[$geoKey],
                    'hiddenMsgs' => $hiddenMsgsByGeo[$geoKey],
                ])
            </div>
        @endforeach
    </div>

    {{-- ── ЦІНИ ── --}}
    <div x-show="cat==='prices'" x-cloak>
        @include('livewire.sites.partials.data-prices')
    </div>

    {{-- Адреси / Соц. мережі / Custom --}}
    <div x-show="['addresses','socials','custom'].includes(cat)" x-cloak class="data-empty">Цей розділ у розробці.</div>

</div>
