{{-- ─── Price entry form (shared by edit + add modes) ─── --}}
@php
    $currencies = ['EUR' => '€', 'USD' => '$', 'PLN' => 'zł', 'UAH' => '₴'];
@endphp
<div class="drawer-stack">

    {{-- НАЗВА --}}
    <div>
        <label class="label">Назва позиції</label>
        <input class="input" wire:model="entryLabel" placeholder="напр. Преміум-підписка">
        @error('entryLabel') <div class="field-error">{{ $message }}</div> @enderror
    </div>

    {{-- SKU --}}
    <div>
        <label class="label">SKU</label>
        <input class="input mono" wire:model="entrySku" placeholder="напр. SKU-1024">
        <p class="field-hint">Спільний SKU групує одну позицію в різних валютах (multi-currency).</p>
        @error('entrySku') <div class="field-error">{{ $message }}</div> @enderror
    </div>

    {{-- ЦІНА + СТАРА ЦІНА --}}
    <div class="field-row2">
        <div>
            <label class="label">Ціна</label>
            <input class="input mono" type="number" step="0.01" min="0" wire:model="entryPrice" placeholder="0.00">
            @error('entryPrice') <div class="field-error">{{ $message }}</div> @enderror
        </div>
        <div>
            <label class="label">Стара ціна <span class="label-opt">(необов'язково)</span></label>
            <input class="input mono" type="number" step="0.01" min="0" wire:model="entryOldPrice" placeholder="—">
            @error('entryOldPrice') <div class="field-error">{{ $message }}</div> @enderror
        </div>
    </div>

    {{-- ВАЛЮТА --}}
    <div>
        <label class="label">Валюта</label>
        <div class="country-pills">
            @foreach($currencies as $code => $symbol)
                <button type="button"
                        class="country-pill {{ $entryCurrency === $code ? 'is-active' : '' }}"
                        wire:click="$set('entryCurrency', '{{ $code }}')">{{ $symbol }} {{ $code }}</button>
            @endforeach
        </div>
        @error('entryCurrency') <div class="field-error">{{ $message }}</div> @enderror
    </div>

    {{-- ОДИНИЦЯ --}}
    <div>
        <label class="label">Одиниця <span class="label-opt">(необов'язково)</span></label>
        <input class="input" wire:model="entryPriceUnit" placeholder="напр. /міс, /рік, за шт.">
        @error('entryPriceUnit') <div class="field-error">{{ $message }}</div> @enderror
    </div>

    {{-- ПРАВИЛО ВИДИМОСТІ --}}
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

    {{-- СТАТУС --}}
    <div>
        <label class="label">Статус</label>
        <div class="role-cards">
            @foreach([
                ['k'=>'primary', 'l'=>'Активна',   'd'=>'Показується відвідувачам'],
                ['k'=>'hidden',  'l'=>'Прихована', 'd'=>'У базі, але не показується'],
            ] as $role)
                @php $isActive = $entryRole === $role['k']; @endphp
                <div class="role-card {{ $isActive ? 'is-active' : '' }}"
                     wire:click="setEntryRole('{{ $role['k'] }}')">
                    <span class="role-card__radio">
                        @if($isActive)<span class="role-card__dot"></span>@endif
                    </span>
                    <div>
                        <div class="role-card__label {{ $role['k'] === 'hidden' ? 'role-card__label--with-icon' : '' }}">
                            @if($role['k'] === 'hidden')
                                <x-icon.eye-off width="14" height="14" class="state-icon state-icon--hidden" />
                            @endif
                            {{ $role['l'] }}
                        </div>
                        <div class="role-card__desc">{{ $role['d'] }}</div>
                    </div>
                </div>
            @endforeach
        </div>
    </div>

</div>
