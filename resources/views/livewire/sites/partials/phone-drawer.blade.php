{{-- ─── PhoneDrawer ─────────────────────────────────────── --}}

{{-- VIEW mode: open phone, not editing, not adding --}}
@if ($openPhoneId && !$editingEntry && !$addingEntry)
    @php $ph = $allPhonesAll->firstWhere('id', $openPhoneId); @endphp
    @if ($ph)
        <x-ui.drawer :open="true" :title="$ph->value" :sub="$ph->label . ' · ' . $ph->geo_label" @drawer-close.window="$wire.closePhone()">
            <div class="drawer-stack">
                <div>
                    <label class="label">Номер</label>
                    <div class="mono field-value-lg">{{ $ph->value }}</div>
                </div>
                <div>
                    <label class="label">Мітка</label>
                    <div class="field-value">{{ $ph->label ?: '—' }}</div>
                </div>
                <div>
                    <label class="label">Гео-правило</label>
                    <div class="field-value">{{ $ph->geo_label ?: 'Усі гео' }}</div>
                </div>
                <div>
                    <label class="label">Роль</label>
                    <div class="role-pills">
                        @foreach([['primary','Головний'],['backup','Резерв'],['hidden','Сховано']] as [$k,$l])
                            <span class="role-pill {{ $ph->role===$k ? 'is-active' : '' }}">{{ $l }}</span>
                        @endforeach
                    </div>
                </div>
            </div>
            <x-slot:footer>
                <button class="btn btn-ghost" wire:click="closePhone">Закрити</button>
                <button class="btn btn-secondary btn-sm" wire:click="toggleEntryVisibility({{ $ph->id }})">
                    {{ $ph->visible ? 'Сховати' : 'Показати' }}
                </button>
                <button class="btn btn-danger-fill btn-sm" wire:click="deleteEntry({{ $ph->id }})" wire:confirm="Видалити цей номер?">Видалити</button>
                <button class="btn btn-primary" wire:click="editEntry({{ $ph->id }})">Редагувати</button>
            </x-slot:footer>
        </x-ui.drawer>
    @endif
@endif

{{-- EDIT mode --}}
@if ($editingEntry && $entryType === 'phone')
    @php $ph = $allPhonesAll->firstWhere('id', $editEntryId); @endphp
    <x-ui.drawer :open="true" title="Редагувати телефон" :sub="($ph?->value ?? '') . ' · ' . ($ph?->label ?? '')" @drawer-close.window="$wire.resetEntryForm()">
        @include('livewire.sites.partials.phone-form')
        <x-slot:footer>
            <button class="btn btn-ghost" wire:click="resetEntryForm">Скасувати</button>
            <button class="btn btn-primary" wire:click="saveEntry">Зберегти</button>
        </x-slot:footer>
    </x-ui.drawer>
@endif

{{-- ADD mode --}}
@if ($addingEntry && $entryType === 'phone')
    @php
        $isBackup = !is_null($entryParentId);
        $parentPhone = $isBackup ? $allPhonesAll->firstWhere('id', $entryParentId) : null;
        $drawerTitle = $isBackup ? 'Додати резерв' : 'Додати телефон';
        $drawerSub = $isBackup && $parentPhone ? 'До головного ' . $parentPhone->value : '';
    @endphp
    <x-ui.drawer :open="true" :title="$drawerTitle" :sub="$drawerSub" @drawer-close.window="$wire.resetEntryForm()">
        {{-- Context box for backup --}}
        @if ($isBackup && $parentPhone)
            <div class="drawer-context">
                <div class="eyebrow eyebrow-xxs">Резерв для</div>
                <div class="drawer-context__row">
                    <span class="mono drawer-context__val">{{ $parentPhone->value }}</span>
                    <span class="drawer-context__label">{{ $parentPhone->label }}</span>
                </div>
                <div class="drawer-context__geo">
                    Гео-правило: {{ $parentPhone->geo_label ?: 'Усі гео' }}
                </div>
            </div>
        @endif
        @include('livewire.sites.partials.phone-form')
        <x-slot:footer>
            <button class="btn btn-ghost" wire:click="resetEntryForm">Скасувати</button>
            <button class="btn btn-primary" wire:click="saveEntry">
                {{ $isBackup ? 'Додати резерв' : 'Додати телефон' }}
            </button>
        </x-slot:footer>
    </x-ui.drawer>
@endif
