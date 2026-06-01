{{-- ─── Messenger entry form (shared by edit + add modes) ─── --}}
@php
    $baseKinds = \App\Models\ContactEntry::MSG_KINDS;
    $kinds = $baseKinds;
    foreach (($messengerKinds ?? []) as $customKind) {
        if (isset($kinds[$customKind])) {
            continue;
        }
        $label = ucfirst(str_replace(['-', '_'], ' ', $customKind));
        $short = strtoupper(substr(preg_replace('/[^a-z0-9]/i', '', $customKind), 0, 2) ?: '??');
        $kinds[$customKind] = ['label' => $label, 'color' => '#888', 'short' => $short];
    }
    $editedMsg = $editEntryId ? $allMsgsAll->firstWhere('id', $editEntryId) : null;
    // A reserve is locked to its primary's platform (Telegram → Telegram, etc.).
    $kindLocked = !is_null($entryParentId) || ($editedMsg && $editedMsg->role === 'backup');
    $lockedMeta = $entryKind ? ($kinds[$entryKind] ?? null) : null;
@endphp
<div class="drawer-stack">

    {{-- БАТЬКІВСЬКИЙ КОНТАКТ --}}
    @if(!is_null($entryParentId))
        @php $parentMsg = $allMsgsAll->firstWhere('id', $entryParentId); @endphp
        <div class="drawer-context">
            <div class="eyebrow eyebrow-xxs">Резерв для</div>
            <div class="drawer-context__row">
                @if($parentMsg && ($pm = $kinds[$parentMsg->kind] ?? null))
                    <span class="msg-badge msg-badge--sm" style="background:{{ $pm['color'] }};">{{ $pm['short'] }}</span>
                @endif
                <span class="mono drawer-context__val">{{ $parentMsg?->value ?? '—' }}</span>
                <span class="drawer-context__label">{{ $parentMsg?->label }}</span>
            </div>
            @if($parentMsg?->geo_label)
                <div class="drawer-context__geo">Гео-правило: {{ $parentMsg->geo_label }}</div>
            @endif
        </div>
    @endif

    {{-- МЕСЕНДЖЕР (платформа) --}}
    <div>
        <label class="label">Месенджер</label>
        @if($kindLocked)
            <div class="msg-kind-pills">
                <div class="msg-kind-pill is-active is-locked" style="{{ $lockedMeta ? '--mk:'.$lockedMeta['color'].';' : '' }}">
                    @if($lockedMeta)
                        <span class="msg-kind-pill__badge" style="background:{{ $lockedMeta['color'] }};">{{ $lockedMeta['short'] }}</span>
                        {{ $lockedMeta['label'] }}
                    @else
                        —
                    @endif
                </div>
            </div>
            <p class="field-hint field-hint--after-pills">Резерв успадковує платформу активного контакту.</p>
        @else
            <div class="msg-kind-pills">
                @foreach($kinds as $kkey => $kmeta)
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
        @endif
        @error('entryKind') <div class="field-error">{{ $message }}</div> @enderror
    </div>

    {{-- КОНТАКТ --}}
    <div>
        <label class="label">Контакт</label>
        <input class="input mono" wire:model="entryValue"
               placeholder="напр. @username, +48 … або посилання">
        @error('entryValue') <div class="field-error">{{ $message }}</div> @enderror
    </div>

    {{-- МІТКА --}}
    <div>
        <label class="label">Мітка</label>
        <input class="input" wire:model="entryLabel" placeholder="напр. Підтримка">
        @error('entryLabel') <div class="field-error">{{ $message }}</div> @enderror
    </div>

    {{-- ПРИНАЛЕЖНІСТЬ --}}
    <div x-show="$wire.entryRole !== 'backup'" x-cloak>
        <label class="label">Приналежність</label>
        <p class="field-hint">Це приналежність контакту. Вона не керує видимістю для відвідувачів.</p>
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
    <div x-show="$wire.entryRole !== 'backup'" x-cloak>
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

    {{-- РОЛЬ --}}
    @php
        $hasOwnBackups = $editedMsg && $editedMsg->backups->count() > 0;
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
                         title="{{ $hasOwnBackups ? 'Контакт має власні резерви — не може бути резервом' : 'Додайте через + Додати резерв' }}">
                        <span class="role-card__radio"></span>
                        <div>
                            <div class="role-card__label">{{ $role['l'] }}</div>
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
                            <div class="role-card__label">{{ $role['l'] }}</div>
                            <div class="role-card__desc">{{ $role['d'] }}</div>
                        </div>
                    </div>
                @endif
            @endforeach
        </div>
    </div>

    {{-- ПРИВ'ЯЗАТИ ДО — коли роль=backup і немає батька (тільки той самий тип) --}}
    <div x-show="$wire.entryRole === 'backup' && !$wire.entryParentId" x-cloak>
        <label class="label">Прив'язати до активного</label>
        @php
            $sameKindPrimaries = $allMsgsAll->filter(
                fn($m) => $m->role === 'primary'
                    && is_null($m->parent_id)
                    && $m->id !== $editEntryId
                    && (!$entryKind || $m->kind === $entryKind)
            );
        @endphp
        <select class="input" wire:model.live="entryParentId">
            <option value="">— оберіть контакт —</option>
            @foreach($sameKindPrimaries as $primary)
                @php $pk = $kinds[$primary->kind] ?? ['short'=>'??']; @endphp
                <option value="{{ $primary->id }}">{{ $pk['short'] }} · {{ $primary->value }}{{ $primary->label ? ' · '.$primary->label : '' }}</option>
            @endforeach
        </select>
        <p class="field-hint">Резерв можна прив'язати лише до активного контакту тієї самої платформи.</p>
    </div>

</div>
