{{-- ─── Tab: Активність ─────────────────────────────────── --}}
<div x-show="tab==='activity'" class="tab-pane">

    <header class="act-head">
        <div style="flex:1;">
            <h3 class="act-head__title">Журнал змін</h3>
            <p class="act-head__sub">Усі зміни на сайті — старі дані, нові, хто змінив і коли.</p>
        </div>
        <button class="btn btn-secondary btn-sm">
            <x-icon.export width="12" height="12" /> Експорт
        </button>
    </header>

    {{-- Type filter pills (actFilter lives in parent x-data on show.blade.php root) --}}
    @php
        $filterTypes = [
            ['k'=>'all',      'l'=>'Усі',         'n'=>$activityLogs->count()],
            ['k'=>'update',   'l'=>'Зміни',        'n'=>$activityLogs->filter(fn($l)=>!str_contains($l->action,'creat')&&!str_contains($l->action,'delet')&&!str_contains($l->action,'fail'))->count()],
            ['k'=>'failover', 'l'=>'Failover',     'n'=>$activityLogs->filter(fn($l)=>str_contains($l->action,'fail'))->count()],
            ['k'=>'create',   'l'=>'Створення',    'n'=>$activityLogs->filter(fn($l)=>str_contains($l->action,'creat'))->count()],
            ['k'=>'delete',   'l'=>'Видалення',    'n'=>$activityLogs->filter(fn($l)=>str_contains($l->action,'delet'))->count()],
            ['k'=>'alert',    'l'=>'Сповіщення',   'n'=>0],
        ];
    @endphp
    <div class="act-filters">
        @foreach ($filterTypes as $f)
            <button class="filter-pill" :class="actFilter==='{{ $f['k'] }}' ? 'is-active' : ''" @click="actFilter='{{ $f['k'] }}'">
                {{ $f['l'] }}
                <span class="pill-count">{{ $f['n'] }}</span>
            </button>
        @endforeach
    </div>

    {{-- Timeline --}}
    @forelse ($activityLogs as $log)
        @php
            $action  = $log->action ?? '';
            $isCreate  = str_contains($action, 'creat') || str_contains($action, 'add');
            $isDelete  = str_contains($action, 'delet') || str_contains($action, 'remov');
            $isFailover= str_contains($action, 'failover') || str_contains($action, 'fail');
            $isAlert   = str_contains($action, 'alert') || str_contains($action, 'warn');
            $type = $isCreate ? 'create' : ($isDelete ? 'delete' : ($isFailover ? 'failover' : ($isAlert ? 'alert' : 'update')));

            $typeColor = match($type) {
                'create'   => 'var(--ok)',
                'delete'   => 'var(--bad)',
                'failover' => 'var(--bad)',
                'alert'    => 'var(--warn)',
                default    => 'var(--info)',
            };
            $typeBg = match($type) {
                'create'   => 'var(--ok-soft)',
                'delete'   => 'var(--bad-soft)',
                'failover' => 'var(--bad-soft)',
                'alert'    => 'var(--warn-soft)',
                default    => 'var(--info-soft)',
            };
            $typeLabel = match($type) {
                'create'   => 'Створено',
                'delete'   => 'Видалено',
                'failover' => 'Failover',
                'alert'    => 'Сповіщення',
                default    => 'Зміна',
            };
            $scope  = $log->subject_type ? class_basename($log->subject_type) : 'System';
            $target = $log->subject?->value ?? $log->subject?->name ?? '—';
            $isSystem = is_null($log->user_id);
            $avatarBg = $isSystem ? 'var(--ink-4)' : 'var(--ink-9)';
            $avatarText = strtoupper(substr($log->user?->name ?? 'S', 0, 2));
            $properties = is_array($log->properties) ? $log->properties : [];
        @endphp

        {{-- Card in timeline --}}
        <div x-show="actFilter === 'all' || actFilter === '{{ $type }}'" class="tl-item">

            {{-- Vertical timeline line (only between cards) --}}
            @if (!$loop->last)
                <div class="tl-line"></div>
            @endif

            {{-- Circle marker --}}
            <span class="tl-marker" style="background:{{ $typeBg }}; color:{{ $typeColor }};">
                @if ($isCreate)
                    <x-icon.plus width="13" height="13" />
                @elseif ($isDelete)
                    <x-icon.trash width="13" height="13" />
                @elseif ($isFailover || $isAlert)
                    <x-icon.bolt width="13" height="13" />
                @else
                    <x-icon.edit width="13" height="13" />
                @endif
            </span>

            {{-- Card --}}
            <article class="card tl-card">

                {{-- Card header --}}
                <header class="tl-card__head">
                    <span class="pill" style="background:{{ $typeBg }}; color:{{ $typeColor }};">
                        <span class="dot" style="margin:0; background:{{ $typeColor }};"></span>
                        {{ $typeLabel }}
                    </span>
                    <span class="tl-scope">{{ $scope }}</span>
                    <span class="tl-target">{{ $target }}</span>
                    <div style="flex:1;"></div>
                    <span class="tl-time">{{ $log->created_at->diffForHumans(null, true) }}</span>
                </header>

                {{-- Card body — diff grid --}}
                <div class="tl-body">
                    @if (!empty($properties))
                        <div class="tl-diffs">
                            @foreach ($properties as $field => $change)
                                @php
                                    $from = is_array($change) ? ($change['old'] ?? null) : null;
                                    $to   = is_array($change) ? ($change['new'] ?? $change) : $change;
                                @endphp
                                <div class="tl-diff">
                                    <span class="eyebrow eyebrow-xs">{{ $field }}</span>
                                    <span class="diff-val {{ $from === null ? 'diff-val--empty' : 'diff-val--old' }}">
                                        {{ $from === null ? 'пусто' : $from }}
                                    </span>
                                    <span class="tl-arrow">→</span>
                                    <span class="diff-val {{ $to === null ? 'diff-val--empty' : 'diff-val--new' }}">
                                        {{ $to === null ? 'видалено' : $to }}
                                    </span>
                                </div>
                            @endforeach
                        </div>
                    @else
                        <p class="tl-plain">{{ ucfirst($action) }}</p>
                    @endif
                </div>

                {{-- Card footer — actor + meta --}}
                <footer class="tl-card__foot">
                    <span class="avatar avatar-xs" style="background:{{ $avatarBg }}; color:var(--paper);">{{ $avatarText }}</span>
                    <span class="tl-actor">{{ $log->user?->name ?? 'System · auto' }}</span>
                    <span class="tl-meta">{{ $isSystem ? 'system' : 'user' }}</span>
                    <div style="flex:1;"></div>
                    @if ($log->ip_address)
                        <span class="tl-meta">IP {{ $log->ip_address }}</span>
                    @endif
                    <span class="tl-meta--mid">{{ $log->created_at->format('d M Y · H:i:s') }}</span>
                    @if (in_array($type, ['update', 'delete', 'failover']))
                        <button class="btn btn-ghost btn-sm btn-xs">
                            <x-icon.refresh width="10" height="10" /> Rollback
                        </button>
                    @endif
                </footer>

            </article>
        </div>
    @empty
        <div class="tl-empty">Немає подій для цього сайту.</div>
    @endforelse

    @if ($activityLogs->count() > 0)
        <div class="tl-loadmore">
            <button class="btn btn-secondary btn-sm">Завантажити старіші</button>
        </div>
    @endif

</div>
