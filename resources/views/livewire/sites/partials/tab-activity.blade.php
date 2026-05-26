{{-- ─── Tab: Активність ─────────────────────────────────── --}}
<div x-show="tab==='activity'" style="padding:32px 40px 64px;">

    <header style="display:flex; align-items:flex-end; margin-bottom:24px;">
        <div style="flex:1;">
            <h3 style="font:400 22px/1 var(--font-sans); color:var(--ink-9);">Журнал змін</h3>
            <p style="margin-top:8px; font:14px var(--font-sans); color:var(--ink-5);">
                Усі зміни на сайті — старі дані, нові, хто змінив і коли.
            </p>
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
    <div style="display:flex; gap:6px; margin-bottom:20px; flex-wrap:wrap;">
        @foreach ($filterTypes as $f)
            <button @click="actFilter='{{ $f['k'] }}'" style="display:inline-flex; align-items:center; gap:6px; height:30px; padding:0 12px; border-radius:999px; font:12.5px var(--font-sans); cursor:pointer;"
                :style="actFilter==='{{ $f['k'] }}' ? 'background:var(--ink-9);color:var(--paper);' : 'background:var(--card);color:var(--ink-7);box-shadow:inset 0 0 0 1px var(--ink-3);'">
                {{ $f['l'] }}
                <span style="font:10.5px var(--font-mono); opacity:.7;">{{ $f['n'] }}</span>
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
        <div x-show="actFilter === 'all' || actFilter === '{{ $type }}'" style="position:relative; margin-bottom:12px;">

            {{-- Vertical timeline line (only between cards) --}}
            @if (!$loop->last)
                <div style="position:absolute; left:19px; top:46px; bottom:-12px; width:1px; background:var(--ink-3); z-index:0;"></div>
            @endif

            {{-- Circle marker --}}
            <span style="position:absolute; left:6px; top:18px; width:28px; height:28px; border-radius:999px; background:{{ $typeBg }}; color:{{ $typeColor }}; display:inline-flex; align-items:center; justify-content:center; border:2px solid var(--paper); z-index:1;">
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
            <article class="card" style="position:relative; padding:0; margin-left:48px; overflow:hidden;">

                {{-- Card header --}}
                <header style="padding:14px 18px; display:flex; align-items:center; gap:12px; border-bottom:1px solid var(--ink-3);">
                    <span class="pill" style="background:{{ $typeBg }}; color:{{ $typeColor }};">
                        <span class="dot" style="margin:0; background:{{ $typeColor }};"></span>
                        {{ $typeLabel }}
                    </span>
                    <span style="font:12px var(--font-mono); color:var(--ink-5);">{{ $scope }}</span>
                    <span style="font:13.5px var(--font-mono); color:var(--ink-9);">{{ $target }}</span>
                    <div style="flex:1;"></div>
                    <span style="font:11.5px var(--font-mono); color:var(--ink-4);">{{ $log->created_at->diffForHumans(null, true) }}</span>
                </header>

                {{-- Card body — diff grid --}}
                <div style="padding:16px 18px;">
                    @if (!empty($properties))
                        <div style="display:flex; flex-direction:column; gap:8px;">
                            @foreach ($properties as $field => $change)
                                @php
                                    $from = is_array($change) ? ($change['old'] ?? null) : null;
                                    $to   = is_array($change) ? ($change['new'] ?? $change) : $change;
                                @endphp
                                <div style="display:grid; grid-template-columns:140px 1fr 20px 1fr; gap:12px; align-items:center;">
                                    <span class="eyebrow" style="font-size:10px;">{{ $field }}</span>
                                    <span style="padding:4px 10px; border-radius:4px; font:12.5px var(--font-mono);
                                        {{ $from === null ? 'background:transparent; color:var(--ink-4); border:1px dashed var(--ink-3); text-align:center;' : 'background:var(--bad-soft); color:var(--bad); text-decoration:line-through;' }}">
                                        {{ $from === null ? 'пусто' : $from }}
                                    </span>
                                    <span style="color:var(--ink-4); text-align:center;">→</span>
                                    <span style="padding:4px 10px; border-radius:4px; font:12.5px var(--font-mono);
                                        {{ $to === null ? 'background:transparent; color:var(--ink-4); border:1px dashed var(--ink-3); text-align:center;' : 'background:var(--ok-soft); color:var(--ok);' }}">
                                        {{ $to === null ? 'видалено' : $to }}
                                    </span>
                                </div>
                            @endforeach
                        </div>
                    @else
                        <p style="font:13px var(--font-sans); color:var(--ink-5);">{{ ucfirst($action) }}</p>
                    @endif
                </div>

                {{-- Card footer — actor + meta --}}
                <footer style="padding:12px 18px; display:flex; align-items:center; gap:12px; border-top:1px solid var(--ink-3); background:var(--paper-2);">
                    <span class="avatar" style="width:22px; height:22px; font-size:10px; background:{{ $avatarBg }}; color:var(--paper);">{{ $avatarText }}</span>
                    <span style="font:12.5px var(--font-sans); color:var(--ink-9);">{{ $log->user?->name ?? 'System · auto' }}</span>
                    <span style="font:11px var(--font-mono); color:var(--ink-4);">{{ $isSystem ? 'system' : 'user' }}</span>
                    <div style="flex:1;"></div>
                    @if ($log->ip_address)
                        <span style="font:11px var(--font-mono); color:var(--ink-4);">IP {{ $log->ip_address }}</span>
                    @endif
                    <span style="font:11px var(--font-mono); color:var(--ink-5);">{{ $log->created_at->format('d M Y · H:i:s') }}</span>
                    @if (in_array($type, ['update', 'delete', 'failover']))
                        <button class="btn btn-ghost btn-sm" style="height:24px; padding:0 10px; font-size:11px;">
                            <x-icon.refresh width="10" height="10" /> Rollback
                        </button>
                    @endif
                </footer>

            </article>
        </div>
    @empty
        <div style="padding:64px; text-align:center; color:var(--ink-5); font:13.5px var(--font-sans);">
            Немає подій для цього сайту.
        </div>
    @endforelse

    @if ($activityLogs->count() > 0)
        <div style="text-align:center; margin-top:20px;">
            <button class="btn btn-secondary btn-sm">Завантажити старіші</button>
        </div>
    @endif

</div>
