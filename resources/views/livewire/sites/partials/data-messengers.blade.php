{{-- ── Messengers sub-section ── --}}
@php
    $kinds = \App\Models\ContactEntry::MSG_KINDS;
    $hiddenMsgs = $hiddenMsgs ?? collect();
    $msgKindCounts = $msgKindCounts ?? [];
    $messengerKinds = $messengerKinds ?? array_keys($msgKindCounts);
    $availableMessengerKinds = $availableMessengerKinds ?? [];
    $gkParam = isset($geoKey) && $geoKey !== 'all' ? "'{$geoKey}'" : 'null';
@endphp

{{-- Platform tabs --}}
<div class="msg-pills">
    <button class="msg-pill msg-pill--all"
            type="button"
            @click="msgKind='all'"
            :class="msgKind==='all' ? 'is-active' : ''">
        Всі <span class="pill-count">{{ $msgPrimaries->count() + $hiddenMsgs->count() }}</span>
    </button>

    @foreach($messengerKinds as $kindKey)
        @php
            $customLabel = ucfirst(str_replace(['-', '_'], ' ', $kindKey));
            $customShort = strtoupper(substr(preg_replace('/[^a-z0-9]/i', '', $kindKey), 0, 2) ?: '??');
            $kd = $kinds[$kindKey] ?? ['label'=>$customLabel,'color'=>'#888','short'=>$customShort];
            $count = $msgKindCounts[$kindKey] ?? 0;
        @endphp
        <span class="msg-pill-tab">
            <button class="msg-pill"
                    type="button"
                    @click="msgKind=@js($kindKey)"
                    :class="msgKind===@js($kindKey) ? 'is-active' : ''">
                <span class="msg-pill__badge" style="background:{{ $kd['color'] }};">{{ $kd['short'] }}</span>
                {{ $kd['label'] }} <span class="pill-count">{{ $count }}</span>
            </button>
            <button class="msg-pill__remove"
                    type="button"
                    title="Видалити платформу"
                    @click.stop="if (msgKind===@js($kindKey)) msgKind='all'; $wire.removeMessengerKind(@js($kindKey));">
                &times;
            </button>
        </span>
    @endforeach

        <span class="msg-pill-add" x-data="{open:false}">
            <button class="msg-pill-add__btn"
                    type="button"
                    title="Додати платформу"
                    @click="open=!open"
                    :class="open ? 'is-active' : ''">+</button>
            <span x-show="open" x-cloak class="msg-pill-add__pop" @click.outside="open=false">
                <input wire:model="newMessengerKind"
                       list="messenger-kind-options-{{ $geoKey ?? 'all' }}"
                       class="msg-pill-add__input"
                       placeholder="Signal або Custom"
                       @keydown.enter.prevent="$wire.addMessengerKind(); open=false"
                       @keydown.escape="open=false">
                <datalist id="messenger-kind-options-{{ $geoKey ?? 'all' }}">
                    @foreach($availableMessengerKinds as $kindKey => $kindMeta)
                        <option value="{{ $kindMeta['label'] }}"></option>
                    @endforeach
                </datalist>
                <button class="msg-pill-add__ok"
                        type="button"
                        wire:click="addMessengerKind"
                        @click="open=false">OK</button>
            </span>
        </span>
</div>

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
            <div class="ctable-group"
                 data-entry-id="{{ $msg->id }}"
                 data-msg-kind="{{ $msg->kind }}"
                 x-show="msgKind === 'all' || msgKind === @js($msg->kind)">

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
        <div wire:click="editEntry({{ $msg->id }})"
             class="crow crow--msg crow--hidden"
             data-msg-kind="{{ $msg->kind }}"
             x-show="msgKind === 'all' || msgKind === @js($msg->kind)"
             style="cursor:pointer;">
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
