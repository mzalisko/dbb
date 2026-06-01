{{-- ── Addresses sub-section ── --}}
@php
    $addressPrimaries = $addressPrimaries ?? collect();
    $hiddenAddresses = $hiddenAddresses ?? collect();
    $gkParam = isset($geoKey) && $geoKey !== 'all' ? "'{$geoKey}'" : 'null';
@endphp

<div class="card ctable">
    {{-- Header --}}
    <div class="crow crow--msg crow--head">
        <span></span>
        <span class="eyebrow eyebrow-xxs">#</span>
        <span class="eyebrow eyebrow-xxs">Адреса</span>
        <span class="eyebrow eyebrow-xxs">ISO</span>
        <span class="eyebrow eyebrow-xxs">Мітка</span>
        <span class="eyebrow eyebrow-xxs">Гео-правило</span>
        <span class="eyebrow eyebrow-xxs">Стан</span>
        <span></span>
    </div>

    @forelse($addressPrimaries as $i => $addr)
        <div wire:click="editEntry({{ $addr->id }})" class="crow crow--msg crow--main" style="cursor:pointer;">
            <span></span>
            <span class="cc-num">#{{ $i+1 }}</span>
            <div class="msg-contact"><span class="cc-val">{{ $addr->value }}</span></div>
            <span class="cc-iso">@include('livewire.sites.partials.preview-tag-badge', ['entry' => $addr])</span>
            <span class="cc-label">{{ $addr->label }}</span>
            <span class="cc-geo">{{ $addr->geo_label }}</span>
            <span class="cc-role"><span class="role-dot" style="background:var(--ok);"></span> Активний</span>
            <span class="cc-actions">
                <button class="cc-edit" wire:click.stop="editEntry({{ $addr->id }})" title="Редагувати"><x-icon.edit width="13" height="13" /></button>
                <button class="cc-delete" wire:click.stop="requestDeleteEntry({{ $addr->id }})" title="Видалити"><x-icon.trash width="13" height="13" /></button>
            </span>
        </div>
    @empty
        <div class="ctable__empty">Немає адрес для обраного гео.</div>
    @endforelse

    {{-- Hidden --}}
    @foreach($hiddenAddresses as $addr)
        <div wire:click="editEntry({{ $addr->id }})" class="crow crow--msg crow--hidden" style="cursor:pointer;">
            <span></span>
            <span class="cc-num">#{{ $loop->index+1 }}</span>
            <div class="msg-contact"><span class="cc-val--sub">{{ $addr->value }}</span></div>
            <span class="cc-iso">@include('livewire.sites.partials.preview-tag-badge', ['entry' => $addr])</span>
            <span class="cc-label--muted">{{ $addr->label }}</span>
            <span class="cc-geo">{{ $addr->geo_label }}</span>
            <span class="cc-role cc-role--muted"><x-icon.eye-off width="13" height="13" class="state-icon state-icon--hidden" /> Приховано</span>
            <span class="cc-actions">
                <button class="cc-edit" wire:click.stop="editEntry({{ $addr->id }})" title="Редагувати"><x-icon.edit width="13" height="13" /></button>
                <button class="cc-delete" wire:click.stop="requestDeleteEntry({{ $addr->id }})" title="Видалити"><x-icon.trash width="13" height="13" /></button>
            </span>
        </div>
    @endforeach

    {{-- Footer --}}
    <div class="ctable__foot">
        <button class="ctable__add" wire:click="addEntry('address', null, {{ $gkParam }})">
            + Додати {{ isset($geoKey) && $geoKey !== 'all' ? $geoKey : '' }} адресу
        </button>
    </div>
</div>
