{{-- ─── PhoneDrawer ─────────────────────────────────────── --}}

{{-- EDIT mode --}}
@if ($editingEntry && $entryType === 'phone')
    @php $ph = $allPhonesAll->firstWhere('id', $editEntryId); @endphp
    <div wire:key="phone-edit-drawer-{{ $entryFormKey }}">
        <x-ui.drawer :open="true" title="Редагувати телефон" :sub="($ph?->value ?? '') . ' · ' . ($ph?->label ?? '')" @drawer-close.window="$wire.resetEntryForm()">
            @include('livewire.sites.partials.phone-form')
            <x-slot:footer>
                <button class="btn btn-danger" wire:click="requestDeleteEntry({{ $editEntryId }})">
                    <x-icon.trash width="13" height="13" />
                    Видалити
                </button>
                <span style="flex:1;"></span>
                <button class="btn btn-ghost" wire:click="resetEntryForm">Скасувати</button>
                <button class="btn btn-primary" wire:click="saveEntry">Зберегти</button>
            </x-slot:footer>
        </x-ui.drawer>
    </div>
@endif

{{-- ADD mode --}}
@if ($addingEntry && $entryType === 'phone')
    @php
        $isBackup = !is_null($entryParentId);
        $parentPhone = $isBackup ? $allPhonesAll->firstWhere('id', $entryParentId) : null;
        $drawerTitle = $isBackup ? 'Додати резерв' : 'Додати телефон';
        $drawerSub = $isBackup && $parentPhone ? 'До: ' . $parentPhone->value : '';
    @endphp
    <div wire:key="phone-add-drawer-{{ $entryFormKey }}">
        <x-ui.drawer :open="true" :title="$drawerTitle" :sub="$drawerSub" @drawer-close.window="$wire.resetEntryForm()">
            @include('livewire.sites.partials.phone-form')
            <x-slot:footer>
                <button class="btn btn-ghost" wire:click="resetEntryForm">Скасувати</button>
                <button class="btn btn-primary" wire:click="saveEntry">
                    {{ $isBackup ? 'Додати резерв' : 'Додати телефон' }}
                </button>
            </x-slot:footer>
        </x-ui.drawer>
    </div>
@endif
