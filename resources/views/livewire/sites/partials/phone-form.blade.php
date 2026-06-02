{{-- ─── Phone entry form (shared by edit + add modes) ─── --}}
<div class="drawer-stack">

    {{-- БАТЬКІВСЬКИЙ НОМЕР --}}
    @if(!is_null($entryParentId))
        @php $parentPhone = $allPhonesAll->firstWhere('id', $entryParentId); @endphp
        <div class="drawer-context">
            <div class="eyebrow eyebrow-xxs">Резерв для</div>
            <div class="drawer-context__row">
                <span class="mono drawer-context__val">{{ $parentPhone?->value ?? '—' }}</span>
                <span class="drawer-context__label">{{ $parentPhone?->label }}</span>
            </div>
            @if($parentPhone?->geo_label)
                <div class="drawer-context__geo">Гео-правило: {{ $parentPhone->geo_label }}</div>
            @endif
        </div>
    @endif

    {{-- НОМЕР --}}
    <div>
        <label class="label">Номер</label>
        <input class="input mono phone-input" wire:model="entryValue" placeholder="+48 ..."
               inputmode="tel"
               @input="$el.value = $el.value.replace(/[^0-9+\-()\s.]/g, '')">
        @error('entryValue') <div class="field-error">{{ $message }}</div> @enderror
    </div>

    {{-- МІТКА --}}
    <div>
        <label class="label">Мітка</label>
        <input class="input" wire:model="entryLabel" placeholder="напр. Польща">
        @error('entryLabel') <div class="field-error">{{ $message }}</div> @enderror
    </div>

    {{-- ПРИНАЛЕЖНІСТЬ --}}
    <div x-show="$wire.entryRole !== 'backup'" x-cloak>
        <label class="label">Приналежність</label>
        <p class="field-hint">Це приналежність номера. Вона не керує видимістю для відвідувачів.</p>
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

    {{-- ГЕО (hidden for backup — inherits parent's geo) --}}
    <div x-show="$wire.entryRole !== 'backup'" x-cloak>
        <label class="label">Правило видимості</label>
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

    {{-- РОЛЬ --}}
    @php
        $editedPhone  = $editEntryId ? $allPhonesAll->firstWhere('id', $editEntryId) : null;
        $hasOwnBackups = $editedPhone && $editedPhone->backups->count() > 0;
    @endphp
    <div>
        <label class="label">Роль</label>
        <div class="role-cards">
            @foreach([
                ['k'=>'primary', 'l'=>'Активний',  'd'=>'Показується відвідувачам'],
                ['k'=>'backup',  'l'=>'Резерв',     'd'=>'Якщо активний недоступний'],
                ['k'=>'hidden',  'l'=>'Приховано',  'd'=>'У базі, але не показується'],
            ] as $role)
                @php
                    $isBackupDisabled = $role['k'] === 'backup' && (
                        ($addingEntry && is_null($entryParentId)) ||
                        $hasOwnBackups
                    );
                    $isActive = $entryRole === $role['k'];
                @endphp
                @if($isBackupDisabled)
                    <div class="role-card role-card--disabled"
                         title="{{ $hasOwnBackups ? 'Номер має власні резерви — не може бути резервом' : 'Додайте через + Додати резерв' }}">
                        <span class="role-card__radio"></span>
                        <div>
                            <div class="role-card__label {{ $role['k'] === 'hidden' ? 'role-card__label--with-icon' : '' }}">
                                @if($role['k'] === 'hidden')
                                    <x-icon.eye-off width="14" height="14" class="state-icon state-icon--hidden" />
                                @endif
                                {{ $role['l'] }}
                            </div>
                            <div class="role-card__desc">{{ $hasOwnBackups ? 'Має власні резерви' : 'Додайте через "+ Додати резерв"' }}</div>
                        </div>
                    </div>
                @else
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
                @endif
            @endforeach
        </div>
    </div>

    {{-- ПРИВ'ЯЗАТИ ДО — коли роль=backup і немає батька --}}
    <div x-show="$wire.entryRole === 'backup' && !$wire.entryParentId" x-cloak>
        <label class="label">Прив'язати до активного</label>
        <select class="input" wire:model.live="entryParentId">
            <option value="">— оберіть номер —</option>
            @foreach($allPhonesAll->filter(fn($p) => $p->role === 'primary' && is_null($p->parent_id) && $p->id !== $editEntryId) as $primary)
                <option value="{{ $primary->id }}">{{ $primary->value }}{{ $primary->label ? ' · '.$primary->label : '' }}</option>
            @endforeach
        </select>
        <p class="field-hint">Або скористайтесь кнопкою → в таблиці для швидкого вибору.</p>
    </div>

</div>
