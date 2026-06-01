{{-- ── Socials sub-section ── --}}
@php
    $kinds = \App\Models\ContactEntry::SOCIAL_KINDS;
    $socialPrimaries = $socialPrimaries ?? collect();
    $hiddenSocials = $hiddenSocials ?? collect();
    $gkParam = isset($geoKey) && $geoKey !== 'all' ? "'{$geoKey}'" : 'null';
    $resolveSocialMeta = function (?string $kind) use ($kinds) {
        $kind = (string) $kind;
        $fallback = ['label' => ucfirst($kind ?: '—'), 'color' => '#888', 'short' => strtoupper(substr($kind, 0, 2) ?: '??')];
        return $kinds[$kind] ?? $fallback;
    };
@endphp

<div class="card ctable">
    {{-- Header --}}
    <div class="crow crow--msg crow--head">
        <span></span>
        <span class="eyebrow eyebrow-xxs">#</span>
        <span class="eyebrow eyebrow-xxs">Профіль</span>
        <span class="eyebrow eyebrow-xxs">ISO</span>
        <span class="eyebrow eyebrow-xxs">Мітка</span>
        <span class="eyebrow eyebrow-xxs">Гео-правило</span>
        <span class="eyebrow eyebrow-xxs">Стан</span>
        <span></span>
    </div>

    @forelse($socialPrimaries as $i => $soc)
        @php $k = $resolveSocialMeta($soc->kind); @endphp
        <div wire:click="editEntry({{ $soc->id }})" class="crow crow--msg crow--main" style="cursor:pointer;">
            <span></span>
            <span class="cc-num">#{{ $i+1 }}</span>
            <div class="msg-contact">
                <span class="msg-badge" style="background:{{ $k['color'] }};">{{ $k['short'] }}</span>
                <div>
                    <div class="mono cc-val">{{ $soc->value }}</div>
                    <div class="msg-kind">{{ $k['label'] }}</div>
                </div>
            </div>
            <span class="cc-iso">@include('livewire.sites.partials.preview-tag-badge', ['entry' => $soc])</span>
            <span class="cc-label">{{ $soc->label }}</span>
            <span class="cc-geo">{{ $soc->geo_label }}</span>
            <span class="cc-role"><span class="role-dot" style="background:var(--ok);"></span> Активний</span>
            <span class="cc-actions">
                <button class="cc-edit" wire:click.stop="editEntry({{ $soc->id }})" title="Редагувати"><x-icon.edit width="13" height="13" /></button>
                <button class="cc-delete" wire:click.stop="requestDeleteEntry({{ $soc->id }})" title="Видалити"><x-icon.trash width="13" height="13" /></button>
            </span>
        </div>
    @empty
        <div class="ctable__empty">Немає соцмереж для обраного гео.</div>
    @endforelse

    {{-- Hidden --}}
    @foreach($hiddenSocials as $soc)
        @php $k = $resolveSocialMeta($soc->kind); @endphp
        <div wire:click="editEntry({{ $soc->id }})" class="crow crow--msg crow--hidden" style="cursor:pointer;">
            <span></span>
            <span class="cc-num">#{{ $loop->index+1 }}</span>
            <div class="msg-contact">
                <span class="msg-badge msg-badge--sm" style="background:{{ $k['color'] }};">{{ $k['short'] }}</span>
                <span class="mono cc-val--sub">{{ $soc->value }}</span>
            </div>
            <span class="cc-iso">@include('livewire.sites.partials.preview-tag-badge', ['entry' => $soc])</span>
            <span class="cc-label--muted">{{ $soc->label }}</span>
            <span class="cc-geo">{{ $soc->geo_label }}</span>
            <span class="cc-role cc-role--muted"><x-icon.eye-off width="13" height="13" class="state-icon state-icon--hidden" /> Приховано</span>
            <span class="cc-actions">
                <button class="cc-edit" wire:click.stop="editEntry({{ $soc->id }})" title="Редагувати"><x-icon.edit width="13" height="13" /></button>
                <button class="cc-delete" wire:click.stop="requestDeleteEntry({{ $soc->id }})" title="Видалити"><x-icon.trash width="13" height="13" /></button>
            </span>
        </div>
    @endforeach

    {{-- Footer --}}
    <div class="ctable__foot">
        <button class="ctable__add" wire:click="addEntry('social', null, {{ $gkParam }})">
            + Додати {{ isset($geoKey) && $geoKey !== 'all' ? $geoKey : '' }} соцмережу
        </button>
    </div>
</div>
