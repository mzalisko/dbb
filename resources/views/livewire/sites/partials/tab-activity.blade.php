{{-- ─── Tab: Активність ─────────────────────────────────── --}}
<div x-show="tab==='activity'" x-cloak class="tab-pane">

    <header class="act-head">
        <div style="flex:1;">
            <h3 class="act-head__title">Журнал змін</h3>
            <p class="act-head__sub">Усі зміни на сайті — старі дані, нові, хто змінив і коли.</p>
        </div>
        <button class="btn btn-secondary btn-sm" disabled title="Скоро">
            <x-icon.export width="12" height="12" /> Експорт
        </button>
        @if(auth()->user()?->isAdmin())
            <button class="btn btn-danger btn-sm" @click="$wire.requestClearSiteHistory(actFilter)">
                <x-icon.trash width="12" height="12" /> Очистити
            </button>
        @endif
    </header>

    @php
        $typeOf = function (string $code): string {
            if (str_contains($code, 'creat')) return 'create';
            if (str_contains($code, 'delet') || str_contains($code, 'purg')) return 'delete';
            if (str_contains($code, 'failover')) return 'failover';
            return 'update';
        };
        $fmt = function ($v) {
            if (is_null($v) || $v === '') return 'пусто';
            if (is_bool($v)) return $v ? 'так' : 'ні';
            if (is_array($v)) return empty($v) ? 'пусто' : implode(' · ', array_map(fn ($x) => is_scalar($x) ? $x : json_encode($x, JSON_UNESCAPED_UNICODE), $v));
            return (string) $v;
        };
        $activityForCounts = $activityAll ?? collect($activityLogs->items());
        $tabCounts = ['all' => $activityForCounts->count()];
        foreach (['update', 'create', 'delete', 'failover'] as $t) {
            $tabCounts[$t] = $activityForCounts->filter(fn ($e) => $typeOf($e->actionCode) === $t)->count();
        }
    @endphp

    {{-- Type filter pills (actFilter lives in parent x-data on show.blade.php root) --}}
    <div class="act-filters">
        @foreach ([
            ['k' => 'all', 'l' => 'Усі'], ['k' => 'update', 'l' => 'Зміни'],
            ['k' => 'create', 'l' => 'Створення'], ['k' => 'delete', 'l' => 'Видалення'],
            ['k' => 'failover', 'l' => 'Failover'],
        ] as $f)
            <button class="filter-pill" :class="actFilter==='{{ $f['k'] }}' ? 'is-active' : ''" @click="activeActivity=''; actFilter='{{ $f['k'] }}'">
                {{ $f['l'] }} <span class="pill-count">{{ $tabCounts[$f['k']] }}</span>
            </button>
        @endforeach
    </div>

    {{-- Timeline --}}
    @forelse ($activityLogs as $e)
        @php
            $type = $typeOf($e->actionCode);
            $sev = $e->severity;
            $typeColor = $sev === 2 ? 'var(--bad)' : ($sev === 1 ? 'var(--warn)' : ($type === 'create' ? 'var(--ok)' : 'var(--info)'));
            $typeBg = $sev === 2 ? 'var(--bad-soft)' : ($sev === 1 ? 'var(--warn-soft)' : ($type === 'create' ? 'var(--ok-soft)' : 'var(--info-soft)'));
            $changes = $e->humanChanges();
            $isBulk = str_contains($e->actionCode, '.bulk.');
            $isSystem = is_null($e->userId);
            $avatarText = strtoupper(substr($e->userName ?? 'S', 0, 2));
            $brief = $isBulk ? (($e->new['done'] ?? 0).' записів')
                : ($type === 'failover' ? (($e->new['from'] ?? '—').' → '.($e->new['to'] ?? '—'))
                : (count($changes) ? ($changes[0]['field'].(count($changes) > 1 ? ' · +'.(count($changes) - 1) : '')) : ''));
            $eventKey = $e->source . '-' . $e->id;
        @endphp

        {{-- «Усі»: стислий рядок таймлайну; клік → детальна вкладка типу --}}
        <div x-show="actFilter === 'all'" @click="focusActivity('{{ $type }}', '{{ $eventKey }}')"
             style="display:grid; grid-template-columns:64px 24px 1fr auto 14px; gap:12px; align-items:center; padding:11px 12px; border-top:1px solid var(--ink-2); cursor:pointer; transition:background .12s;"
             onmouseover="this.style.background='var(--paper-2)'" onmouseout="this.style.background='transparent'">
            <span class="mono" style="font:12px var(--font-mono); color:var(--ink-6);">{{ $e->occurredAt->format('H:i:s') }}</span>
            <span style="display:inline-flex; align-items:center; justify-content:center; width:24px; height:24px; border-radius:6px; background:{{ $typeBg }}; color:{{ $typeColor }};">
                <x-dynamic-component :component="'icon.' . $e->icon()" width="12" height="12" />
            </span>
            <span style="display:inline-flex; align-items:center; gap:8px; min-width:0;">
                <span style="font:13px var(--font-sans); color:var(--ink-9); white-space:nowrap;">{{ $e->label() }}</span>
                @if ($brief)<span class="mono" style="font:11.5px var(--font-mono); color:var(--ink-5); overflow:hidden; text-overflow:ellipsis; white-space:nowrap;">{{ $brief }}</span>@endif
            </span>
            <span style="font:11.5px var(--font-sans); color:var(--ink-4); white-space:nowrap;">{{ $e->userName ?? 'Система' }}</span>
            <x-icon.arrow width="13" height="13" style="color:var(--ink-4);" />
        </div>

        <div x-show="actFilter === '{{ $type }}'" id="activity-detail-{{ $eventKey }}" class="tl-item" :class="activeActivity === '{{ $eventKey }}' ? 'tl-item--focus' : ''">
            @if (!$loop->last)
                <div class="tl-line"></div>
            @endif

            <span class="tl-marker" style="background:{{ $typeBg }}; color:{{ $typeColor }};">
                <x-dynamic-component :component="'icon.' . $e->icon()" width="13" height="13" />
            </span>

            <article class="card tl-card">
                <header class="tl-card__head">
                    <span class="pill" style="background:{{ $typeBg }}; color:{{ $typeColor }};">
                        <span class="dot" style="margin:0; background:{{ $typeColor }};"></span>
                        {{ $e->label() }}
                    </span>
                    <div style="flex:1;"></div>
                    <span class="tl-time">{{ $e->occurredAt->diffForHumans(null, true) }}</span>
                </header>

                <div class="tl-body">
                    @if ($type === 'failover')
                        {{-- Журнал перемикань, у такому ж вигляді як у налаштуваннях --}}
                        @php $p = $e->new; $fok = ($p['ok'] ?? true) !== false; @endphp
                        <div class="set-jrow" style="padding:0;">
                            <div>
                                <div class="set-jfrom">
                                    @if (!empty($p['geo']))<span style="font-size:13px;">{{ $p['geo'] }}</span>@endif
                                    {{ $p['from'] ?? '—' }}
                                    <span class="set-jarrow">→</span>
                                </div>
                                <div class="set-jto">{{ $p['to'] ?? '—' }}</div>
                            </div>
                            <div>
                                @if (!$fok)
                                    <span class="set-jbadge" style="background:var(--bad-soft); color:var(--bad);">ПОМИЛКА</span>
                                @else
                                    <span class="set-jbadge set-jbadge--{{ $p['mode'] ?? 'manual' }}">{{ strtoupper($p['mode'] ?? 'manual') }}</span>
                                @endif
                                <span class="set-jcause">{{ $p['cause'] ?? '' }}</span>
                            </div>
                        </div>
                    @elseif ($isBulk)
                        <p class="tl-plain">
                            {{ $e->new['done'] ?? 0 }} записів@if (!empty($e->new['skipped'])) · {{ $e->new['skipped'] }} пропущено @endif
                        </p>
                    @elseif (count($changes))
                        <div class="tl-diffs">
                            @foreach ($changes as $c)
                                @if ($c['kind'] === 'group')
                                    {{-- Permissions: one row per changed toggle --}}
                                    <div class="tl-diff" style="align-items:start;">
                                        <span class="eyebrow eyebrow-xs">{{ $c['field'] }}</span>
                                        <div style="grid-column:2 / -1; display:flex; flex-direction:column; gap:6px;">
                                            @foreach ($c['lines'] as $l)
                                                <div style="display:grid; grid-template-columns:1fr 1fr 20px 1fr; gap:12px; align-items:center;">
                                                    <span style="font:12px var(--font-sans); color:var(--ink-6);">{{ $l['label'] }}</span>
                                                    <span class="diff-val diff-val--old">{{ $l['old'] }}</span>
                                                    <span class="tl-arrow">→</span>
                                                    <span class="diff-val diff-val--new">{{ $l['new'] }}</span>
                                                </div>
                                            @endforeach
                                        </div>
                                    </div>
                                @elseif ($c['kind'] === 'delta')
                                    <div class="tl-diff">
                                        <span class="eyebrow eyebrow-xs">{{ $c['field'] }}</span>
                                        <span class="diff-val" style="grid-column:2 / -1; background:var(--paper-2); color:var(--ink-8);">{{ \App\Support\AuditEntry::deltaText($c['added'], $c['removed']) }}</span>
                                    </div>
                                @else
                                    <div class="tl-diff">
                                        <span class="eyebrow eyebrow-xs">{{ $c['field'] }}</span>
                                        <span class="diff-val {{ $c['oldEmpty'] ? 'diff-val--empty' : 'diff-val--old' }}">{{ $c['old'] }}</span>
                                        <span class="tl-arrow">→</span>
                                        <span class="diff-val {{ $c['newEmpty'] ? 'diff-val--empty' : 'diff-val--new' }}">{{ $c['new'] }}</span>
                                    </div>
                                @endif
                            @endforeach
                        </div>
                    @else
                        <p class="tl-plain">{{ $e->label() }}</p>
                    @endif
                </div>

                <footer class="tl-card__foot">
                    <span class="avatar avatar-xs" style="background:{{ $isSystem ? 'var(--ink-4)' : 'var(--ink-9)' }}; color:var(--paper);">{{ $avatarText }}</span>
                    <span class="tl-actor">{{ $e->userName ?? 'Система' }}</span>
                    <span class="tl-meta">{{ $isSystem ? 'system' : 'user' }}</span>
                    <div style="flex:1;"></div>
                    @if ($e->ip)
                        <span class="tl-meta">IP {{ $e->ip }}</span>
                    @endif
                    <span class="tl-meta--mid">{{ $e->occurredAt->format('d M Y · H:i:s') }}</span>
                    @if ($e->source === 'audit' && ! $isBulk && $type === 'update' && count($changes))
                        <button class="btn btn-ghost btn-sm btn-xs" wire:click="rollbackAudit({{ $e->id }})"
                                wire:confirm="Відновити попередні значення цього запису?">
                            <x-icon.refresh width="10" height="10" /> Відновити
                        </button>
                    @elseif ($type === 'failover' && ($e->new['mode'] ?? null) === 'manual' && !empty($e->new['from_id']) && ($e->new['ok'] ?? true))
                        {{-- Rollback this failover: bring the failed number back up --}}
                        <button class="btn btn-ghost btn-sm btn-xs" wire:click="restoreFailover({{ $e->new['from_id'] }})"
                                wire:confirm="Відновити номер «{{ $e->new['from'] ?? '' }}»?">
                            <x-icon.refresh width="10" height="10" /> Відновити
                        </button>
                    @endif
                </footer>
            </article>
        </div>
    @empty
        <div class="tl-empty">Немає подій для цього сайту.</div>
    @endforelse

    @if($activityLogs->hasPages())
        <div style="margin-top:18px;">{{ $activityLogs->links('livewire.quiet-pagination') }}</div>
    @endif

</div>
