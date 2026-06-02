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
            @if(in_array($cat['key'], $visibleDataCategories, true))
                <button @click="cat='{{ $cat['key'] }}'"
                        :class="cat==='{{ $cat['key'] }}' ? 'is-active' : ''"
                        class="filter-pill">
                    {{ $cat['label'] }}
                    <span class="pill-count">{{ $cat['count'] }}</span>
                </button>
            @endif
        @endforeach
        @if(in_array('custom', $visibleDataCategories, true))
            <button @click="cat='custom'" :class="cat==='custom' ? 'is-active' : ''" class="filter-pill">
                + Custom <span class="pill-count">0</span>
            </button>
        @endif
    </div>

    {{-- ПРИНАЛЕЖНІСТЬ + geo filter --}}
    <div class="view-row">
        <span class="eyebrow eyebrow-xs">Приналежність</span>
        <div class="seg">
            <button @click="geo='all'" :class="geo==='all' ? 'is-active' : ''" class="seg__btn">
                Всі <span x-text="cat==='phones' ? {{ $allPhonesAll->count() }} : (cat==='messengers' ? {{ $allMsgsAll->count() }} : (cat==='socials' ? {{ $allSocialsAll->count() }} : (cat==='addresses' ? {{ $allAddressesAll->count() }} : {{ $priceCount }})))"></span>
            </button>
            @foreach($geoTabs as $geoCode)
                @php
                    $geoPhoneCount = ($phonePrimariesByGeo[$geoCode] ?? collect())->count() + ($hiddenPhonesByGeo[$geoCode] ?? collect())->count();
                    $geoMsgCount = ($msgPrimariesByGeo[$geoCode] ?? collect())->count() + ($hiddenMsgsByGeo[$geoCode] ?? collect())->count();
                    $geoSocialCount = ($socialPrimariesByGeo[$geoCode] ?? collect())->count() + ($hiddenSocialsByGeo[$geoCode] ?? collect())->count();
                    $geoAddressCount = ($addressPrimariesByGeo[$geoCode] ?? collect())->count() + ($hiddenAddressesByGeo[$geoCode] ?? collect())->count();
                @endphp
                <span class="seg__tab">
                    <button @click="geo='{{ $geoCode }}'" :class="geo==='{{ $geoCode }}' ? 'is-active' : ''" class="seg__btn">
                        {{ $geoCode }}
                        <span class="seg__count pill-count" x-text="cat==='phones' ? {{ $geoPhoneCount }} : (cat==='messengers' ? {{ $geoMsgCount }} : (cat==='socials' ? {{ $geoSocialCount }} : (cat==='addresses' ? {{ $geoAddressCount }} : {{ $priceCount }})))"></span>
                    </button>
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
                    'msgKindCounts' => $msgKindCountsByGeo[$geoKey],
                ])
            </div>
        @endforeach
    </div>

    {{-- ── ЦІНИ ── --}}
    <div x-show="cat==='prices'" x-cloak>
        @include('livewire.sites.partials.data-prices')
    </div>

    {{-- ── СОЦМЕРЕЖІ ── --}}
    <div x-show="cat==='socials'" x-cloak>
        @foreach(array_merge(['all'], $geoTabs) as $geoKey)
            <div x-show="geo==='{{ $geoKey }}'">
                @include('livewire.sites.partials.data-socials', [
                    'socialPrimaries' => $socialPrimariesByGeo[$geoKey],
                    'hiddenSocials' => $hiddenSocialsByGeo[$geoKey],
                ])
            </div>
        @endforeach
    </div>

    {{-- ── АДРЕСИ ── --}}
    <div x-show="cat==='addresses'" x-cloak>
        @foreach(array_merge(['all'], $geoTabs) as $geoKey)
            <div x-show="geo==='{{ $geoKey }}'">
                @include('livewire.sites.partials.data-addresses', [
                    'addressPrimaries' => $addressPrimariesByGeo[$geoKey],
                    'hiddenAddresses' => $hiddenAddressesByGeo[$geoKey],
                ])
            </div>
        @endforeach
    </div>

    {{-- Custom — ще в розробці --}}
    <div x-show="cat==='custom'" x-cloak class="data-empty">Цей розділ у розробці.</div>

</div>
