{{-- ─── SocialDrawer ────────────────────────────────────── --}}

{{-- EDIT mode --}}
@if ($editingEntry && $entryType === 'social')
    @php $sc = $allSocialsAll->firstWhere('id', $editEntryId); @endphp
    <div wire:key="social-edit-drawer-{{ $entryFormKey }}">
        <x-ui.drawer :open="true" title="Редагувати соцмережу" :sub="($sc?->value ?? '')" @drawer-close.window="$wire.resetEntryForm()">
            @include('livewire.sites.partials.social-form')
            <x-slot:footer>
                <button class="btn btn-danger" wire:click="requestDeleteEntry({{ $editEntryId }})">
                    <x-icon.trash width="13" height="13" /> Видалити
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
@if ($addingEntry && $entryType === 'social')
    <div wire:key="social-add-drawer-{{ $entryFormKey }}">
        <x-ui.drawer :open="true" title="Додати соцмережу" @drawer-close.window="$wire.resetEntryForm()">
            @include('livewire.sites.partials.social-form')
            <x-slot:footer>
                <button class="btn btn-ghost" wire:click="resetEntryForm">Скасувати</button>
                <button class="btn btn-primary" wire:click="saveEntry">Додати соцмережу</button>
            </x-slot:footer>
        </x-ui.drawer>
    </div>
@endif
