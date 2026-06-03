{{-- ─── MessengerDrawer ─────────────────────────────────── --}}

{{-- EDIT mode --}}
@if ($editingEntry && $entryType === 'messenger')
    @php $msg = $allMsgsAll->firstWhere('id', $editEntryId); @endphp
    <div wire:key="msg-edit-drawer-{{ $entryFormKey }}">
        <x-ui.drawer :open="true" title="Редагувати месенджер" :sub="($msg?->value ?? '') . ($msg?->label ? ' · ' . $msg->label : '')" @drawer-close.window="$wire.resetEntryForm()">
            @include('livewire.sites.partials.messenger-form')
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
@if ($addingEntry && $entryType === 'messenger')
    @php
        $isBackup = !is_null($entryParentId);
        $parentMsg = $isBackup ? $allMsgsAll->firstWhere('id', $entryParentId) : null;
        $drawerTitle = $isBackup ? 'Додати резерв' : 'Додати месенджер';
        $drawerSub = $isBackup && $parentMsg ? 'До: ' . $parentMsg->value : '';
    @endphp
    <div wire:key="msg-add-drawer-{{ $entryFormKey }}">
        <x-ui.drawer :open="true" :title="$drawerTitle" :sub="$drawerSub" @drawer-close.window="$wire.resetEntryForm()">
            @include('livewire.sites.partials.messenger-form')
            <x-slot:footer>
                <button class="btn btn-ghost" wire:click="resetEntryForm">Скасувати</button>
                <button class="btn btn-primary" wire:click="saveEntry">
                    {{ $isBackup ? 'Додати резерв' : 'Додати месенджер' }}
                </button>
            </x-slot:footer>
        </x-ui.drawer>
    </div>
@endif
