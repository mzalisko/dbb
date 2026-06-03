{{-- ── Phones sub-section ── --}}
<div class="card ctable">
    {{-- Header --}}
    <div class="crow crow--head">
        <span></span>
        <span class="eyebrow eyebrow-xxs">#</span>
        <span class="eyebrow eyebrow-xxs">Номер</span>
        <span class="eyebrow eyebrow-xxs">ISO</span>
        <span class="eyebrow eyebrow-xxs">Мітка</span>
        <span class="eyebrow eyebrow-xxs">Гео-правило</span>
        <span class="eyebrow eyebrow-xxs">Роль</span>
        <span></span>
    </div>

    {{-- Sortable primary entries --}}
    <div x-data="sortable()">
        @forelse($phonePrimaries as $i => $phone)
            @php $serving = $phone->failoverServing(); $servingId = $serving?->id; @endphp
            <div class="ctable-group" data-entry-id="{{ $phone->id }}">

                {{-- Primary row --}}
                <div wire:click="editEntry({{ $phone->id }})" class="crow crow--main">
                    <span class="cc-drag" @click.stop>&#x2807;</span>
                    <span class="cc-num">#{{ $i+1 }}</span>
                    <span class="mono cc-val" style="{{ $phone->failover_down ? 'text-decoration:line-through; color:var(--ink-5);' : '' }}">{{ $phone->value }}</span>
                    <span class="cc-iso">@include('livewire.sites.partials.preview-tag-badge', ['entry' => $phone])</span>
                    <span class="cc-label">{{ $phone->label }}</span>
                    <span class="cc-geo">{{ $phone->geo_label }}</span>
                    <span class="cc-role {{ $phone->role !== 'primary' ? 'cc-role--muted' : '' }}">
                        @if($phone->role === 'backup')
                            <span class="role-dot" style="background:var(--warn);"></span> Резерв
                        @elseif($phone->role === 'hidden')
                            <x-icon.eye-off width="13" height="13" class="state-icon state-icon--hidden" /> Приховано
                        @elseif($phone->failover_down)
                            <span class="role-dot" style="background:var(--bad);"></span> Збій
                        @else
                            <span class="role-dot" style="background:var(--ok);"></span> Головний
                        @endif
                    </span>
                    {{-- Остання колонка: assign-кнопка для orphan/без-резервів; edit-кнопка для активних з резервами --}}
                    <span class="cc-actions">
                    @if($phone->failover_down)
                        <button class="cc-promote" wire:click.stop="restoreFailover({{ $phone->id }})"
                                title="Відновити — зняти збій" style="color:var(--ok);">&#x21A9;</button>
                    @endif
                    @if($phone->role === 'backup' || ($phone->role === 'primary' && $phone->backups->count() === 0))
                        <button class="cc-assign"
                                wire:click.stop="openAssignModal({{ $phone->id }})"
                                title="Зробити резервом іншого номера">→</button>
                    @else
                        <button class="cc-edit"
                                wire:click.stop="editEntry({{ $phone->id }})"
                                title="Редагувати">
                            <x-icon.edit width="13" height="13" />
                        </button>
                    @endif
                        <button class="cc-delete"
                                wire:click.stop="requestDeleteEntry({{ $phone->id }})"
                                title="Видалити">
                            <x-icon.trash width="13" height="13" />
                        </button>
                    </span>
                </div>

                {{-- РЕЗЕРВ section --}}
                @if($phone->backups->count() > 0)
                    <div x-data="{open:false}" class="creserve">
                        <div class="creserve__head" @click="open=!open">
                            <span class="creserve__title">
                                <span x-text="open?'&#x25BE;':'&#x25B8;'"></span>
                                РЕЗЕРВ &middot; {{ $phone->backups->count() }}
                            </span>
                            <button class="creserve__add" wire:click.stop="addEntry('phone', {{ $phone->id }})">+ Додати резерв</button>
                        </div>
                        {{-- Backup rows — sortable --}}
                        <div x-show="open" x-data="backupSortable({{ $phone->id }})">
                            @foreach($phone->backups as $j => $backup)
                                <div wire:click="editEntry({{ $backup->id }})"
                                     class="crow crow--backup"
                                     data-backup-id="{{ $backup->id }}"
                                     style="cursor:pointer;">
                                    <span class="cc-drag" @click.stop style="color:var(--ink-4);">&#x2807;</span>
                                    <span class="cc-num cc-num--backup">#{{ $i+1 }}.{{ $j+1 }}</span>
                                    <span class="mono cc-val--sub" style="{{ $backup->failover_down ? 'text-decoration:line-through;' : '' }}">{{ $backup->value }}</span>
                                    <span class="cc-iso">@include('livewire.sites.partials.preview-tag-badge', ['entry' => $phone])</span>
                                    <span class="cc-label--muted">{{ $backup->label }}</span>
                                    <span class="cc-geo">{{ $phone->geo_label }}</span>
                                    <span class="cc-role cc-role--muted">
                                        @if($backup->role === 'hidden')
                                            <x-icon.eye-off width="13" height="13" class="state-icon state-icon--hidden" /> Приховано
                                        @elseif($backup->failover_down)
                                            <span class="role-dot" style="background:var(--bad);"></span> Збій
                                        @elseif($servingId === $backup->id)
                                            <span class="role-dot" style="background:var(--ok);"></span> Працює зараз
                                        @else
                                            <span class="role-dot" style="background:var(--info);"></span> Резерв
                                        @endif
                                    </span>
                                    <span class="cc-actions">
                                        @if($backup->failover_down)
                                            <button class="cc-promote" wire:click.stop="restoreFailover({{ $backup->id }})"
                                                    title="Відновити — зняти збій" style="color:var(--ok);">&#x21A9;</button>
                                        @endif
                                        <button class="cc-promote" wire:click.stop="promoteEntry({{ $backup->id }})" title="Зробити головним">↑</button>
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
                        <button class="creserve__add-inline" wire:click.stop="addEntry('phone', {{ $phone->id }})">+ Додати резерв</button>
                    </div>
                @endif

            </div>
        @empty
            <div class="ctable__empty">Немає телефонів для обраного гео.</div>
        @endforelse
    </div>

    {{-- Hidden entries --}}
    @foreach($hiddenPhones as $phone)
        <div wire:click="editEntry({{ $phone->id }})" class="crow crow--hidden" style="cursor:pointer;">
            <span style="color:var(--ink-4);">&#x2807;</span>
            <span class="cc-num">#{{ $loop->index+1 }}</span>
            <span class="mono cc-val--sub">{{ $phone->value }}</span>
            <span class="cc-iso">@include('livewire.sites.partials.preview-tag-badge', ['entry' => $phone])</span>
            <span class="cc-label--muted">{{ $phone->label }}</span>
            <span class="cc-geo">{{ $phone->geo_label }}</span>
            <span class="cc-role cc-role--muted">
                <x-icon.eye-off width="13" height="13" class="state-icon state-icon--hidden" /> Приховано
            </span>
            <span class="cc-actions">
                <button class="cc-edit" wire:click.stop="editEntry({{ $phone->id }})" title="Редагувати">
                    <x-icon.edit width="13" height="13" />
                </button>
                <button class="cc-delete"
                        wire:click.stop="requestDeleteEntry({{ $phone->id }})"
                        title="Видалити">
                    <x-icon.trash width="13" height="13" />
                </button>
            </span>
        </div>
    @endforeach

    {{-- Footer --}}
    <div class="ctable__foot">
        @php $gkParam = isset($geoKey) && $geoKey !== 'all' ? "'{$geoKey}'" : 'null'; @endphp
        <button class="ctable__add" wire:click="addEntry('phone', null, {{ $gkParam }})">
            + Додати {{ isset($geoKey) && $geoKey !== 'all' ? $geoKey : '' }} телефон
        </button>
    </div>
</div>

@include('livewire.sites.partials.assign-backup-modal')
