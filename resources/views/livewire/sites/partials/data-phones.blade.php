{{-- Phones sub-section --}}
<div class="card ctable ctable--failover">
    <div class="failover-head">
        <span></span>
        <span>Номер</span>
        <span>Мітка</span>
        <span>Приналежність</span>
        <span>Гео</span>
        <span>Роль</span>
        <span></span>
    </div>
    <div x-data="sortable()" class="failover-stack">
        @forelse($phonePrimaries as $i => $phone)
            @php
                $serving = $phone->failoverServing();
                $servingId = $serving?->id;
                $isDown = (bool) $phone->failover_down;
            @endphp
            <div class="failover-group" data-entry-id="{{ $phone->id }}">
                <div wire:click="editEntry({{ $phone->id }})" class="failover-row failover-row--primary">
                    <span class="failover-dot {{ $isDown ? 'failover-dot--bad' : '' }}"></span>
                    <span class="mono failover-value" style="{{ $isDown ? 'text-decoration:line-through; color:var(--ink-5);' : '' }}">{{ $phone->value }}</span>
                    <span class="failover-label">{{ $phone->label ?: 'Без мітки' }}</span>
                    <span class="failover-owner">{{ $phone->preview_geo_label ?: '—' }}</span>
                    <span class="failover-geo">{{ $phone->geo_label }}</span>
                    <span class="failover-role">
                        @if($isDown)
                            Збій
                        @else
                            Основний
                        @endif
                    </span>
                    <span class="failover-actions">
                        @if($isDown)
                            <button class="cc-promote" wire:click.stop="restoreFailover({{ $phone->id }})" title="Відновити">
                                <x-icon.refresh width="13" height="13" /> Відновити
                            </button>
                        @elseif($phone->backups->count() > 0)
                            <button class="cc-trigger" wire:click.stop="triggerFailover({{ $phone->id }})" wire:confirm="Перемкнути на резерв? Працюватиме наступний у черзі." title="Перемкнути на резерв">
                                <x-icon.bolt width="13" height="13" /> На резерв
                            </button>
                        @endif
                        <button class="cc-hide" wire:click.stop="toggleEntryVisibility({{ $phone->id }})" title="Приховати (весь набір)">
                            <x-icon.eye-off width="13" height="13" />
                        </button>
                        @if($phone->role === 'backup' || ($phone->role === 'primary' && $phone->backups->count() === 0))
                            <button class="cc-assign" wire:click.stop="openAssignModal({{ $phone->id }})" title="Зробити резервом іншого номера">→</button>
                        @else
                            <button class="cc-edit" wire:click.stop="editEntry({{ $phone->id }})" title="Редагувати">
                                <x-icon.edit width="13" height="13" />
                            </button>
                        @endif
                        <button class="cc-delete" wire:click.stop="requestDeleteEntry({{ $phone->id }})" title="Видалити">
                            <x-icon.trash width="13" height="13" />
                        </button>
                        <span class="cc-drag failover-drag" @click.stop title="Змінити порядок">&#x2807;</span>
                    </span>
                </div>

                <div class="failover-reserves" x-data="backupSortable({{ $phone->id }})">
                    @foreach($phone->backups as $j => $backup)
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
                            <span class="failover-label failover-label--muted">{{ $backup->label ?: 'резерв · гео успадковане' }}</span>
                            <span class="failover-owner">{{ $backup->preview_geo_label ?: '—' }}</span>
                            <span class="failover-geo">{{ $phone->geo_label }}</span>
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
                    <button wire:click.stop="addEntry('phone', {{ $phone->id }})">+ приєднати резерв</button>
                </div>
            </div>
        @empty
            <div class="ctable__empty">Немає телефонів для обраного гео.</div>
        @endforelse
    </div>

    @foreach($hiddenPhones as $phone)
        <div wire:click="editEntry({{ $phone->id }})" class="failover-row failover-row--hidden">
            <span class="failover-dot failover-dot--muted"></span>
            <span class="mono failover-value failover-value--sub">{{ $phone->value }}</span>
            <span class="failover-label failover-label--muted">{{ $phone->label ?: 'Без мітки' }}</span>
            <span class="failover-owner">{{ $phone->preview_geo_label ?: '—' }}</span>
            <span class="failover-geo">{{ $phone->geo_label }}</span>
            <span class="failover-role failover-role--muted">
                Приховано
                @if($phone->backups->count() > 0)
                    <span class="failover-cascade-badge">+{{ $phone->backups->count() }}</span>
                @endif
            </span>
            <span class="failover-actions">
                <button class="cc-show" wire:click.stop="toggleEntryVisibility({{ $phone->id }})" title="Активувати (весь набір)">
                    <x-icon.eye width="13" height="13" />
                </button>
                <button class="cc-edit" wire:click.stop="editEntry({{ $phone->id }})" title="Редагувати">
                    <x-icon.edit width="13" height="13" />
                </button>
                <button class="cc-delete" wire:click.stop="requestDeleteEntry({{ $phone->id }})" title="Видалити">
                    <x-icon.trash width="13" height="13" />
                </button>
            </span>
        </div>
        {{-- Cascade-hidden backups --}}
        @foreach($phone->backups as $backup)
            <div class="failover-row failover-row--hidden failover-row--backup-hidden">
                <span class="failover-backup-index">
                    <span class="failover-branch">└</span>
                    <span class="failover-order">{{ $loop->index + 1 }}</span>
                </span>
                <span class="mono failover-value failover-value--sub">{{ $backup->value }}</span>
                <span class="failover-label failover-label--muted">{{ $backup->label ?: 'резерв' }}</span>
                <span class="failover-owner">{{ $backup->preview_geo_label ?: '—' }}</span>
                <span class="failover-geo">{{ $phone->geo_label }}</span>
                <span class="failover-role failover-role--muted">Сховано (резерв)</span>
                <span class="failover-actions"></span>
            </div>
        @endforeach
    @endforeach

    <div class="ctable__foot">
        @php $gkParam = isset($geoKey) && $geoKey !== 'all' ? "'{$geoKey}'" : 'null'; @endphp
        <button class="ctable__add" wire:click="addEntry('phone', null, {{ $gkParam }})">
            + Додати {{ isset($geoKey) && $geoKey !== 'all' ? $geoKey : '' }} телефон
        </button>
    </div>
</div>

@include('livewire.sites.partials.assign-backup-modal')
