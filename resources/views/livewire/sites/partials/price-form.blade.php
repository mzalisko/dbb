{{-- Price entry form (shared by edit + add modes) --}}
<div class="drawer-stack">

    <div>
        <label class="label">Ціновий блок</label>
        <input class="input mono" wire:model="entrySku" placeholder="напр. ПОЛЬША, КАНАДА, США">
        <p class="field-hint field-hint--price-block">Блок групує варіанти цін. У майбутній логіці гео блоку буде джерелом для дочірніх цін.</p>
        @error('entrySku') <div class="field-error">{{ $message }}</div> @enderror
    </div>

    <div>
        <label class="label">Текст ціни</label>
        <input class="input mono" wire:model="entryValue" placeholder="напр. 3000, 3000грн, 3000 &lt;span class=&quot;price-currency&quot;&gt;EUR&lt;/span&gt;">
        <p class="field-hint">HTML видно при редагуванні, створенні і на фронтенді. В оглядах CRM показується текст без тегів. Дозволено: span class, b, strong, i, em, small, sup, sub, br.</p>
        @error('entryValue') <div class="field-error">{{ $message }}</div> @enderror
    </div>

    <div>
        <label class="label">Мітка <span class="label-opt">(необов'язково)</span></label>
        <input class="input" wire:model="entryLabel" placeholder="напр. для всіх, для України, окремо RU/BY">
        @error('entryLabel') <div class="field-error">{{ $message }}</div> @enderror
    </div>

    <div>
        <label class="label">Правило видимості</label>
        <p class="field-hint">Кому показувати цю ціну: всім, тільки вибраним країнам або всім крім вибраних.</p>
        <div class="country-pills">
            <button type="button"
                    class="country-pill {{ $entryGeoMode === 'all' ? 'is-active' : '' }}"
                    wire:click="setGeoAll()">Усі</button>
            @foreach($geoTabs as $code)
                @php $sel = in_array($code, $entryCountries ?? []); @endphp
                <button type="button"
                        class="country-pill {{ $sel ? 'is-active' : '' }}"
                        wire:click="toggleCountry('{{ $code }}')">{{ $code }}</button>
            @endforeach
        </div>
        @if(!empty($entryCountries))
            <div class="geo-mode-row">
                <span class="eyebrow eyebrow-xxs">Режим:</span>
                <div class="seg seg--xs">
                    <button type="button"
                            class="seg__btn {{ $entryGeoMode === 'only' ? 'is-active' : '' }}"
                            wire:click="setEntryGeoMode('only')">Тільки</button>
                    <button type="button"
                            class="seg__btn {{ $entryGeoMode === 'except' ? 'is-active' : '' }}"
                            wire:click="setEntryGeoMode('except')">Крім</button>
                </div>
            </div>
        @endif
    </div>

</div>
