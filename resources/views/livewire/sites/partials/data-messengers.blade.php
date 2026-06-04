{{-- Messengers sub-section --}}
@php
    $kinds = \App\Models\ContactEntry::MSG_KINDS;
    $hiddenMsgs = $hiddenMsgs ?? collect();
    $msgKindCounts = $msgKindCounts ?? [];
    $messengerKinds = $messengerKinds ?? array_keys($msgKindCounts);
    $availableMessengerKinds = $availableMessengerKinds ?? [];
    $gkParam = isset($geoKey) && $geoKey !== 'all' ? "'{$geoKey}'" : 'null';
    $resolveMsgKindMeta = function (?string $kind) use ($kinds) {
        $kind = (string) $kind;
        $customLabel = ucfirst(str_replace(['-', '_'], ' ', $kind));
        $customShort = strtoupper(substr(preg_replace('/[^a-z0-9]/i', '', $kind), 0, 2) ?: '??');

        return $kinds[$kind] ?? ['label' => $customLabel, 'color' => '#888', 'short' => $customShort];
    };
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
            $kd = $resolveMsgKindMeta($kindKey);
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
                    @click.stop="if (msgKind===@js($kindKey)) msgKind='all'; $wire.requestRemoveMessengerKind(@js($kindKey));">
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

<div class="card ctable ctable--failover">
    <div x-data="sortable()" class="failover-stack">
        @forelse($msgPrimaries as $i => $msg)
            @php
                $k = $resolveMsgKindMeta($msg->kind);
                $serving = $msg->failoverServing();
                $servingId = $serving?->id;
                $isDown = (bool) $msg->failover_down;
            @endphp
            <div class="failover-group"
                 data-entry-id="{{ $msg->id }}"
                 data-msg-kind="{{ $msg->kind }}"
                 x-show="msgKind === 'all' || msgKind === @js($msg->kind)">
                <div wire:click="editEntry({{ $msg->id }})" class="failover-row failover-row--primary">
                    <span class="msg-badge msg-badge--compact" style="background:{{ $k['color'] }};">{{ $k['short'] }}</span>
                    <span class="mono failover-value" style="{{ $isDown ? 'text-decoration:line-through; color:var(--ink-5);' : '' }}">{{ $msg->value }}</span>
                    <span class="failover-label">{{ $msg->label ?: $k['label'] }}</span>
                    <span class="failover-geo">{{ $msg->geo_label }}</span>
                    <span class="failover-role">
                        @if($isDown)
                            Збій
                        @else
                            Основний
                        @endif
                    </span>
                    <span class="failover-actions">
                        @if($isDown)
                            <button class="cc-promote" wire:click.stop="restoreFailover({{ $msg->id }})" title="Відновити">↩</button>
                        @endif
                        <button class="cc-edit" wire:click.stop="editEntry({{ $msg->id }})" title="Редагувати">
                            <x-icon.edit width="13" height="13" />
                        </button>
                        <button class="cc-delete" wire:click.stop="requestDeleteEntry({{ $msg->id }})" title="Видалити">
                            <x-icon.trash width="13" height="13" />
                        </button>
                        <span class="cc-drag failover-drag" @click.stop title="Змінити порядок">&#x2807;</span>
                    </span>
                </div>

                <div class="failover-reserves" x-data="backupSortable({{ $msg->id }})">
                    @foreach($msg->backups as $j => $backup)
                        @php $bk = $resolveMsgKindMeta($backup->kind); @endphp
                        <div wire:click="editEntry({{ $backup->id }})"
                             class="failover-row failover-row--backup {{ $servingId === $backup->id ? 'failover-row--serving' : '' }}"
                             data-backup-id="{{ $backup->id }}">
                            <span class="failover-backup-index">
                                @if($servingId === $backup->id)
                                    <span class="failover-dot failover-dot--serving" title="Працює зараз"></span>
                                @endif
                                <span class="failover-branch">└</span>
                                <span class="failover-order">{{ $j + 1 }}</span>
                            </span>
                            <span class="mono failover-value failover-value--sub" style="{{ $backup->failover_down ? 'text-decoration:line-through;' : '' }}">{{ $backup->value }}</span>
                            <span class="failover-label failover-label--muted">{{ $backup->label ?: 'резерв · ' . $bk['label'] }}</span>
                            <span class="failover-geo">{{ $msg->geo_label }}</span>
                            <span class="failover-role failover-role--muted">Резерв</span>
                            <span class="failover-actions">
                                @if($backup->failover_down)
                                    <button class="cc-promote" wire:click.stop="restoreFailover({{ $backup->id }})" title="Відновити">↩</button>
                                @endif
                                <button class="cc-promote" wire:click.stop="promoteEntry({{ $backup->id }})" title="Зробити основним">↑</button>
                                <button class="cc-delete" wire:click.stop="requestDeleteEntry({{ $backup->id }})" title="Видалити">
                                    <x-icon.trash width="13" height="13" />
                                </button>
                                <span class="cc-drag failover-drag" @click.stop title="Змінити порядок">&#x2807;</span>
                            </span>
                        </div>
                    @endforeach
                </div>

                <div class="failover-addrow">
                    <button wire:click.stop="addEntry('messenger', {{ $msg->id }})">+ приєднати резерв</button>
                </div>
            </div>
        @empty
            <div class="ctable__empty">Немає месенджерів для обраного гео.</div>
        @endforelse
    </div>

    @foreach($hiddenMsgs as $msg)
        @php $k = $resolveMsgKindMeta($msg->kind); @endphp
        <div wire:click="editEntry({{ $msg->id }})"
             class="failover-row failover-row--hidden"
             data-msg-kind="{{ $msg->kind }}"
             x-show="msgKind === 'all' || msgKind === @js($msg->kind)">
            <span class="msg-badge msg-badge--compact msg-badge--muted" style="background:{{ $k['color'] }};">{{ $k['short'] }}</span>
            <span class="mono failover-value failover-value--sub">{{ $msg->value }}</span>
            <span class="failover-label failover-label--muted">{{ $msg->label ?: $k['label'] }}</span>
            <span class="failover-geo">{{ $msg->geo_label }}</span>
            <span class="failover-role failover-role--muted">Приховано</span>
            <span class="failover-actions">
                <button class="cc-edit" wire:click.stop="editEntry({{ $msg->id }})" title="Редагувати">
                    <x-icon.edit width="13" height="13" />
                </button>
                <button class="cc-delete" wire:click.stop="requestDeleteEntry({{ $msg->id }})" title="Видалити">
                    <x-icon.trash width="13" height="13" />
                </button>
            </span>
        </div>
    @endforeach

    <div class="ctable__foot">
        <button class="ctable__add" wire:click="addEntry('messenger', null, {{ $gkParam }})">
            + Додати {{ isset($geoKey) && $geoKey !== 'all' ? $geoKey : '' }} месенджер
        </button>
    </div>
</div>
