{{-- ─── AddressDrawer ───────────────────────────────────── --}}

{{-- EDIT mode --}}
@if ($editingEntry && $entryType === 'address')
    @php $ad = $allAddressesAll->firstWhere('id', $editEntryId); @endphp
    <div wire:key="address-edit-drawer-{{ $entryFormKey }}">
        <x-ui.drawer :open="true" title="Редагувати адресу" :sub="$ad?->label ?? ''" @drawer-close.window="$wire.resetEntryForm()">
            @include('livewire.sites.partials.address-form')
            <x-slot:footer>
                <button class="btn btn-danger" wire:click="requestDeleteEntry({{ $editEntryId }})">
                    <x-icon.trash width="13" height="13" /> Видалити
                </button>
                <span style="flex:1;"></span>
                <button class="btn btn-ghost" wire:click="resetEntryForm">Скасувати</button>
                <button class="btn btn-primary" wire:click="saveEntry">Зберегти</button>
            </x-slot:footer>
        </x-ui.drawer>
    </div>
@endif

{{-- ADD mode --}}
@if ($addingEntry && $entryType === 'address')
    <div wire:key="address-add-drawer-{{ $entryFormKey }}">
        <x-ui.drawer :open="true" title="Додати адресу" @drawer-close.window="$wire.resetEntryForm()">
            @include('livewire.sites.partials.address-form')
            <x-slot:footer>
                <button class="btn btn-ghost" wire:click="resetEntryForm">Скасувати</button>
                <button class="btn btn-primary" wire:click="saveEntry">Додати адресу</button>
            </x-slot:footer>
        </x-ui.drawer>
    </div>
@endif
