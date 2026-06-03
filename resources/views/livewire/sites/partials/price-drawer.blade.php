{{-- ─── PriceDrawer ─────────────────────────────────────── --}}

{{-- EDIT mode --}}
@if ($editingEntry && $entryType === 'price')
    @php $price = $allPricesAll->firstWhere('id', $editEntryId); @endphp
    <div wire:key="price-edit-drawer-{{ $entryFormKey }}">
        <x-ui.drawer :open="true" title="Редагувати ціну" :sub="($price?->label ?? '') . ($price?->sku ? ' · ' . $price->sku : '')" @drawer-close.window="$wire.resetEntryForm()">
            @include('livewire.sites.partials.price-form')
            <x-slot:footer>
                <button class="btn btn-danger" wire:click="requestDeleteEntry({{ $editEntryId }})">
                    <x-icon.trash width="13" height="13" />
                    Видалити
                </button>
                <span style="align-self:center; margin-left:12px; font:10.5px var(--font-mono); color:var(--ink-4);">ID {{ $editEntryId }}</span>
                <span style="flex:1;"></span>
                <button class="btn btn-ghost" wire:click="resetEntryForm">Скасувати</button>
                <button class="btn btn-primary" wire:click="saveEntry">Зберегти</button>
            </x-slot:footer>
        </x-ui.drawer>
    </div>
@endif

{{-- ADD mode --}}
@if ($addingEntry && $entryType === 'price')
    <div wire:key="price-add-drawer-{{ $entryFormKey }}">
        <x-ui.drawer :open="true" title="Додати ціну" sub="" @drawer-close.window="$wire.resetEntryForm()">
            @include('livewire.sites.partials.price-form')
            <x-slot:footer>
                <button class="btn btn-ghost" wire:click="resetEntryForm">Скасувати</button>
                <button class="btn btn-primary" wire:click="saveEntry">Додати ціну</button>
            </x-slot:footer>
        </x-ui.drawer>
    </div>
@endif
