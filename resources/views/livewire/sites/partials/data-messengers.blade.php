{{-- ── Messengers sub-section ── --}}
@php
    $kinds = \App\Models\ContactEntry::MSG_KINDS;
    $hiddenMsgs = $hiddenMsgs ?? collect();
    $gkParam = isset($geoKey) && $geoKey !== 'all' ? "'{$geoKey}'" : 'null';
@endphp

{{-- Platform pills --}}
@if($msgByKind->count() > 0)
    <div class="msg-pills">
        @foreach($msgByKind as $kindKey => $kindEntries)
            @php $kd = $kinds[$kindKey] ?? ['label'=>$kindKey,'color'=>'#888','short'=>'??']; @endphp
            <span class="msg-pill" style="background:{{ $kd['color'] }}20; color:{{ $kd['color'] }}; border:1px solid {{ $kd['color'] }}40;">
                <span class="msg-pill__badge" style="background:{{ $kd['color'] }};">{{ $kd['short'] }}</span>
                {{ $kd['label'] }} {{ $kindEntries->count() }}
            </span>
        @endforeach
    </div>
@endif

{{-- Messenger table --}}
<div class="card ctable">
    {{-- Header --}}
    <div class="crow crow--msg crow--head">
        <span></span>
        <span class="eyebrow eyebrow-xxs">#</span>
        <span class="eyebrow eyebrow-xxs">Контакт</span>
        <span class="eyebrow eyebrow-xxs">ISO</span>
        <span class="eyebrow eyebrow-xxs">Мітка</span>
        <span class="eyebrow eyebrow-xxs">Гео-правило</span>
        <span class="eyebrow eyebrow-xxs">Роль</span>
        <span></span>
    </div>

    {{-- Sortable primary entries --}}
    <div x-data="sortable()">
        @forelse($msgPrimaries as $i => $msg)
            @php $k = $kinds[$msg->kind] ?? ['label'=>$msg->kind,'color'=>'#888','short'=>'??']; @endphp
            <div class="ctable-group" data-entry-id="{{ $msg->id }}">

                {{-- Primary row --}}
                <div wire:click="editEntry({{ $msg->id }})" class="crow crow--msg crow--main">
                    <span class="cc-drag" @click.stop>&#x2807;</span>
                    <span class="cc-num">#{{ $i+1 }}</span>
                    <div class="msg-contact">
                        <span class="msg-badge" style="background:{{ $k['color'] }};">{{ $k['short'] }}</span>
                        <div>
                            <div class="mono cc-val">{{ $msg->value }}</div>
                            <div class="msg-kind">{{ $k['label'] }}</div>
                        </div>
                    </div>
                    <span class="cc-iso">@include('livewire.sites.partials.preview-tag-badge', ['entry' => $msg])</span>
                    <span class="cc-label">{{ $msg->label }}</span>
                    <span class="cc-geo">{{ $msg->geo_label }}</span>
                    <span class="cc-role">
                        <span class="role-dot" style="background:var(--ok);"></span> Активний
                    </span>
                    <span class="cc-actions">
                        <button class="cc-edit" wire:click.stop="editEntry({{ $msg->id }})" title="Редагувати">
                            <x-icon.edit width="13" height="13" />
                        </button>
                        <button class="cc-delete"
                                wire:click.stop="requestDeleteEntry({{ $msg->id }})"
                                title="Видалити">
                            <x-icon.trash width="13" height="13" />
                        </button>
                    </span>
                </div>

                {{-- РЕЗЕРВ section --}}
                @if($msg->backups->count() > 0)
                    <div x-data="{open:false}" class="creserve">
                        <div class="creserve__head" @click="open=!open">
                            <span class="creserve__title">
                                <span x-text="open?'&#x25BE;':'&#x25B8;'"></span>
                                РЕЗЕРВ &middot; {{ $msg->backups->count() }}
                            </span>
                            <button class="creserve__add" wire:click.stop="addEntry('messenger', {{ $msg->id }})">+ Додати резерв</button>
                        </div>
                        {{-- Backup rows — sortable --}}
                        <div x-show="open" x-data="backupSortable({{ $msg->id }})">
                            @foreach($msg->backups as $j => $backup)
                                @php $bk = $kinds[$backup->kind] ?? ['label'=>$backup->kind,'color'=>'#888','short'=>'??']; @endphp
                                <div class="crow crow--msg crow--backup" data-backup-id="{{ $backup->id }}">
                                    <span class="cc-drag" @click.stop style="color:var(--ink-4);">&#x2807;</span>
                                    <span class="cc-num cc-num--backup">#{{ $i+1 }}.{{ $j+1 }}</span>
                                    <div wire:click="editEntry({{ $backup->id }})" class="msg-contact" style="cursor:pointer;">
                                        <span class="msg-badge msg-badge--sm" style="background:{{ $bk['color'] }};">{{ $bk['short'] }}</span>
                                        <span class="mono cc-val--sub">{{ $backup->value }}</span>
                                    </div>
                                    <span class="cc-iso">@include('livewire.sites.partials.preview-tag-badge', ['entry' => $backup])</span>
                                    <span class="cc-label--muted">{{ $backup->label }}</span>
                                    <span class="cc-geo">—</span>
                                    <span class="cc-role cc-role--muted">
                                        <span class="role-dot" style="background:var(--info);"></span> Резерв
                                    </span>
                                    <span class="cc-actions">
                                        <button class="cc-promote" wire:click.stop="promoteEntry({{ $backup->id }})" title="Зробити активним">↑</button>
                                        <button class="cc-delete"
                                                wire:click.stop="requestDeleteEntry({{ $backup->id }})"
                                                title="Видалити">
                                            <x-icon.trash width="13" height="13" />
                                        </button>
                                    </span>
                                </div>
                            @endforeach
                        </div>
                    </div>
                @else
                    <div class="creserve creserve--empty">
                        <button class="creserve__add-inline" wire:click.stop="addEntry('messenger', {{ $msg->id }})">+ Додати резерв</button>
                    </div>
                @endif

            </div>
        @empty
            <div class="ctable__empty">Немає месенджерів для обраного гео.</div>
        @endforelse
    </div>

    {{-- Hidden entries --}}
    @foreach($hiddenMsgs as $msg)
        @php $k = $kinds[$msg->kind] ?? ['label'=>$msg->kind,'color'=>'#888','short'=>'??']; @endphp
        <div wire:click="editEntry({{ $msg->id }})" class="crow crow--msg crow--hidden" style="cursor:pointer;">
            <span style="color:var(--ink-4);">&#x2807;</span>
            <span class="cc-num">#{{ $loop->index+1 }}</span>
            <div class="msg-contact">
                <span class="msg-badge msg-badge--sm" style="background:{{ $k['color'] }};">{{ $k['short'] }}</span>
                <span class="mono cc-val--sub">{{ $msg->value }}</span>
            </div>
            <span class="cc-iso">@include('livewire.sites.partials.preview-tag-badge', ['entry' => $msg])</span>
            <span class="cc-label--muted">{{ $msg->label }}</span>
            <span class="cc-geo">{{ $msg->geo_label }}</span>
            <span class="cc-role cc-role--muted">
                <span class="role-dot" style="background:var(--ink-4);"></span> Приховано
            </span>
            <span class="cc-actions">
                <button class="cc-edit" wire:click.stop="editEntry({{ $msg->id }})" title="Редагувати">
                    <x-icon.edit width="13" height="13" />
                </button>
                <button class="cc-delete"
                        wire:click.stop="requestDeleteEntry({{ $msg->id }})"
                        title="Видалити">
                    <x-icon.trash width="13" height="13" />
                </button>
            </span>
        </div>
    @endforeach

    {{-- Footer --}}
    <div class="ctable__foot">
        <button class="ctable__add" wire:click="addEntry('messenger', null, {{ $gkParam }})">
            + Додати {{ isset($geoKey) && $geoKey !== 'all' ? $geoKey : '' }} месенджер
        </button>
    </div>
</div>
