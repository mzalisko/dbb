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
        $tabCounts = ['all' => $activityLogs->count()];
        foreach (['update', 'create', 'delete', 'failover'] as $t) {
            $tabCounts[$t] = $activityLogs->filter(fn ($e) => $typeOf($e->actionCode) === $t)->count();
        }
    @endphp

    {{-- Type filter pills (actFilter lives in parent x-data on show.blade.php root) --}}
    <div class="act-filters">
        @foreach ([
            ['k' => 'all', 'l' => 'Усі'], ['k' => 'update', 'l' => 'Зміни'],
            ['k' => 'create', 'l' => 'Створення'], ['k' => 'delete', 'l' => 'Видалення'],
            ['k' => 'failover', 'l' => 'Failover'],
        ] as $f)
            <button class="filter-pill" :class="actFilter==='{{ $f['k'] }}' ? 'is-active' : ''" @click="actFilter='{{ $f['k'] }}'">
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
            $changes = $e->changes();
            $isBulk = str_contains($e->actionCode, '.bulk.');
            $isSystem = is_null($e->userId);
            $avatarText = strtoupper(substr($e->userName ?? 'S', 0, 2));
        @endphp

        <div x-show="actFilter === 'all' || actFilter === '{{ $type }}'" class="tl-item">
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
                    @if ($isBulk)
                        <p class="tl-plain">
                            {{ $e->new['done'] ?? 0 }} записів@if (!empty($e->new['skipped'])) · {{ $e->new['skipped'] }} пропущено @endif
                        </p>
                    @elseif (count($changes))
                        <div class="tl-diffs">
                            @foreach ($changes as $c)
                                <div class="tl-diff">
                                    <span class="eyebrow eyebrow-xs">{{ $c['field'] }}</span>
                                    <span class="diff-val {{ ($c['old'] === null || $c['old'] === '' || $c['old'] === []) ? 'diff-val--empty' : 'diff-val--old' }}">{{ $fmt($c['old']) }}</span>
                                    <span class="tl-arrow">→</span>
                                    <span class="diff-val {{ ($c['new'] === null || $c['new'] === '' || $c['new'] === []) ? 'diff-val--empty' : 'diff-val--new' }}">{{ $fmt($c['new']) }}</span>
                                </div>
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
                </footer>
            </article>
        </div>
    @empty
        <div class="tl-empty">Немає подій для цього сайту.</div>
    @endforelse

</div>
