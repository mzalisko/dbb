{{-- ─── Social entry form (shared by edit + add modes) ─── --}}
@php $socialKindsMeta = \App\Models\ContactEntry::SOCIAL_KINDS; @endphp
<div class="drawer-stack">

    {{-- ПЛАТФОРМА --}}
    <div>
        <label class="label">Платформа</label>
        <div class="msg-kind-pills">
            @foreach($socialKindsMeta as $kkey => $kmeta)
                @php $active = $entryKind === $kkey; @endphp
                <button type="button"
                        class="msg-kind-pill {{ $active ? 'is-active' : '' }}"
                        wire:click="$set('entryKind', '{{ $kkey }}')"
                        @style(['--mk:' . $kmeta['color'] => $active])>
                    <span class="msg-kind-pill__badge" style="background:{{ $kmeta['color'] }};">{{ $kmeta['short'] }}</span>
                    {{ $kmeta['label'] }}
                </button>
            @endforeach
        </div>
        @error('entryKind') <div class="field-error">{{ $message }}</div> @enderror
    </div>

    {{-- ПОСИЛАННЯ --}}
    <div>
        <label class="label">Посилання / профіль</label>
        <input class="input mono" wire:model="entryValue" placeholder="напр. https://instagram.com/brand або @brand">
        @error('entryValue') <div class="field-error">{{ $message }}</div> @enderror
    </div>

    {{-- МІТКА --}}
    <div>
        <label class="label">Мітка</label>
        <input class="input" wire:model="entryLabel" placeholder="напр. Офіційна сторінка">
        @error('entryLabel') <div class="field-error">{{ $message }}</div> @enderror
    </div>

    {{-- ПРИНАЛЕЖНІСТЬ --}}
    <div>
        <label class="label">Приналежність</label>
        <p class="field-hint">Це приналежність запису. Вона не керує видимістю для відвідувачів.</p>
        <div class="country-pills">
            <button type="button"
                    class="country-pill {{ $entryGeoTag === '' ? 'is-active' : '' }}"
                    wire:click="setEntryGeoTag(null)">Без прив'язки</button>
            @foreach($geoTabs as $code)
                <button type="button"
                        class="country-pill {{ $entryGeoTag === $code ? 'is-active' : '' }}"
                        wire:click="setEntryGeoTag('{{ $code }}')">{{ $code }}</button>
            @endforeach
        </div>
    </div>

    {{-- ПРАВИЛО ВИДИМОСТІ --}}
    <div>
        <label class="label">Правило видимості</label>
        <p class="field-hint">Окремо від належності: показувати всім, тільки вибраним країнам або всім крім вибраних.</p>
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

    {{-- СТАН --}}
    <div>
        <label class="label">Стан</label>
        <div class="role-cards">
            @foreach([
                ['k'=>'primary', 'l'=>'Активний',  'd'=>'Показується відвідувачам'],
                ['k'=>'hidden',  'l'=>'Приховано',  'd'=>'У базі, але не показується'],
            ] as $role)
                @php $isActive = $entryRole === $role['k']; @endphp
                <div class="role-card {{ $isActive ? 'is-active' : '' }}" wire:click="setEntryRole('{{ $role['k'] }}')">
                    <span class="role-card__radio">@if($isActive)<span class="role-card__dot"></span>@endif</span>
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
