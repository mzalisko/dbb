<div style="flex:1; display:flex; flex-direction:column; overflow-y:auto;" x-data="{ tab: 'overview' }">

    <x-ui.topbar :crumbs="['Сайти', $site->name]">
        <a href="{{ $site->url }}" target="_blank" rel="noopener"
           class="btn btn-secondary btn-sm">
            <x-icon.external-link width="13" height="13" /> Відкрити
        </a>
    </x-ui.topbar>

    {{-- Page head with status pill --}}
    <x-ui.page-head :title="$site->name">
        <x-slot:actions>
            @php
                $statusColor = match($site->status) {
                    'active'      => 'ok',
                    'maintenance' => 'warn',
                    default       => 'bad',
                };
                $statusLabel = match($site->status) {
                    'active'      => 'Активний',
                    'maintenance' => 'Пауза',
                    default       => 'Offline',
                };
            @endphp
            <x-ui.pill :status="$statusColor" style="margin-bottom:4px;">{{ $statusLabel }}</x-ui.pill>
        </x-slot:actions>
    </x-ui.page-head>

    {{-- Tab nav --}}
    <div class="tabs" style="padding:0 40px; flex-shrink:0;">
        <button @click="tab='overview'" :class="{ 'active': tab === 'overview' }" class="tab">
            Огляд
            <span class="tab-n">{{ $phoneCount + $msgCount }}</span>
        </button>
        <button @click="tab='data'" :class="{ 'active': tab === 'data' }" class="tab">
            Дані
        </button>
        <button @click="tab='activity'" :class="{ 'active': tab === 'activity' }" class="tab">
            Активність
            <span class="tab-n">{{ $activityLogs->count() }}</span>
        </button>
        <button @click="tab='settings'" :class="{ 'active': tab === 'settings' }" class="tab">
            Налаштування
        </button>
    </div>

    {{-- ─── Tab: Огляд ─────────────────────────────────────── --}}
    <div x-show="tab === 'overview'" style="padding:32px 40px 64px;">
        <div style="display:grid; grid-template-columns:repeat(3, 1fr); gap:14px;">
            @foreach ($geos as $geo)
                <div x-data="{ expanded: false }" class="card" style="padding:22px; position:relative; overflow:hidden;">
                    {{-- Color accent bar --}}
                    @if ($geo['key'] === 'PL')
                        <div style="position:absolute; top:0; left:0; right:0; height:3px; background:#dc143c;"></div>
                    @elseif ($geo['key'] === 'UA')
                        <div style="position:absolute; top:0; left:0; right:0; height:3px; background:#0057b7;"></div>
                    @else
                        <div style="position:absolute; top:0; left:0; right:0; height:3px; background:var(--ink-4);"></div>
                    @endif

                    <header style="display:flex; align-items:center; gap:10px; margin-bottom:16px; margin-top:4px;">
                        <span style="font:22px var(--font-sans);">{{ $geo['flag'] }}</span>
                        <div>
                            <div style="font:500 14px/1.2 var(--font-sans); color:var(--ink-9);">{{ $geo['label'] }}</div>
                            <div style="font:11.5px var(--font-mono); color:var(--ink-5); margin-top:3px;">
                                {{ $geo['backupPhones']->count() + ($geo['primaryPhone'] ? 1 : 0) }} тел.
                                · {{ $geo['backupMsgs']->count() + ($geo['primaryMsg'] ? 1 : 0) }} месенджерів
                            </div>
                        </div>
                    </header>

                    {{-- Primary phone --}}
                    @if ($geo['primaryPhone'])
                        <div style="display:flex; align-items:center; gap:10px; margin-bottom:10px;">
                            <x-icon.phone width="14" height="14" style="color:var(--ink-5); flex-shrink:0;" />
                            <div style="flex:1; min-width:0;">
                                <div class="mono" style="font:14px var(--font-mono); color:var(--ink-9);">{{ $geo['primaryPhone']->value }}</div>
                                <div style="font:11.5px var(--font-sans); color:var(--ink-5);">{{ $geo['primaryPhone']->label }}</div>
                            </div>
                            @if ($geo['backupPhones']->count() > 0)
                                <button @click="expanded = !expanded"
                                    style="font:11.5px var(--font-mono); color:var(--ink-5); white-space:nowrap; cursor:pointer;"
                                    x-text="expanded ? '−' : '+' + {{ $geo['backupPhones']->count() }}"></button>
                            @endif
                        </div>

                        {{-- Backup phones (expandable) --}}
                        <div x-show="expanded" style="margin-left:24px; margin-bottom:10px; display:flex; flex-direction:column; gap:6px;">
                            @foreach ($geo['backupPhones'] as $backup)
                                <div style="display:flex; align-items:center; gap:8px;">
                                    <span style="width:6px; height:6px; border-radius:999px; background:var(--info); flex-shrink:0;"></span>
                                    <span class="mono" style="font:12.5px var(--font-mono); color:var(--ink-7);">{{ $backup->value }}</span>
                                    <span style="font:11px var(--font-sans); color:var(--ink-4);">{{ $backup->geo_label }}</span>
                                </div>
                            @endforeach
                        </div>
                    @else
                        <div style="font:13px var(--font-sans); color:var(--ink-4); margin-bottom:10px; font-style:italic;">Немає телефону</div>
                    @endif

                    {{-- Divider --}}
                    <div style="border-top:1px solid var(--ink-3); margin:10px 0;"></div>

                    {{-- Primary messenger --}}
                    @if ($geo['primaryMsg'])
                        @php $kind = \App\Models\ContactEntry::MSG_KINDS[$geo['primaryMsg']->kind] ?? ['label' => $geo['primaryMsg']->kind, 'color' => '#888', 'short' => '??']; @endphp
                        <div style="display:flex; align-items:center; gap:10px;">
                            <span style="width:24px; height:24px; border-radius:4px; display:inline-flex; align-items:center; justify-content:center; font:bold 9.5px var(--font-mono); color:#fff; background:{{ $kind['color'] }}; flex-shrink:0;">
                                {{ $kind['short'] }}
                            </span>
                            <div style="flex:1; min-width:0;">
                                <div class="mono" style="font:13.5px var(--font-mono); color:var(--ink-9);">{{ $geo['primaryMsg']->value }}</div>
                                <div style="font:11.5px var(--font-sans); color:var(--ink-5);">{{ $geo['primaryMsg']->label }}</div>
                            </div>
                            @if ($geo['backupMsgs']->count() > 0)
                                <span style="font:11.5px var(--font-mono); color:var(--ink-5);">+{{ $geo['backupMsgs']->count() }}</span>
                            @endif
                        </div>
                    @else
                        <div style="font:13px var(--font-sans); color:var(--ink-4); font-style:italic;">Немає месенджера</div>
                    @endif
                </div>
            @endforeach
        </div>

        {{-- Technical info strip --}}
        <div class="card" style="margin-top:24px; display:grid; grid-template-columns:repeat(4, 1fr);">
            @foreach ([
                ['label' => 'WordPress', 'value' => $site->wp_version ?? 'Unknown'],
                ['label' => 'PHP', 'value' => $site->php_version ?? 'Unknown'],
                ['label' => 'Статус', 'value' => ucfirst($site->status)],
                ['label' => 'Остання перевірка', 'value' => $site->last_checked_at?->format('d M H:i') ?? 'Ніколи'],
            ] as $i => $info)
                <div style="padding:16px 20px; {{ $i ? 'border-left:1px solid var(--ink-3);' : '' }}">
                    <div class="eyebrow" style="font-size:10px; margin-bottom:8px;">{{ $info['label'] }}</div>
                    <div class="mono" style="font:14px var(--font-mono); color:var(--ink-9);">{{ $info['value'] }}</div>
                </div>
            @endforeach
        </div>
    </div>

    {{-- ─── Tab: Дані ───────────────────────────────────────── --}}
    <div x-show="tab === 'data'" style="padding:24px 40px 64px;">
        <div style="display:flex; align-items:center; justify-content:space-between; margin-bottom:24px; flex-wrap:wrap; gap:12px;">
            {{-- Category pills --}}
            <div style="display:flex; gap:6px; flex-wrap:wrap;">
                @foreach ([
                    ['key' => 'phones',     'label' => 'Телефони',   'count' => $phoneCount],
                    ['key' => 'messengers', 'label' => 'Месенджери', 'count' => $msgCount],
                    ['key' => 'prices',     'label' => 'Ціни',       'count' => $priceBySku->count()],
                ] as $cat)
                    <button wire:click="$set('category', '{{ $cat['key'] }}')"
                        style="
                            display:inline-flex; align-items:center; gap:6px;
                            height:30px; padding:0 12px; border-radius:999px;
                            background:{{ $category === $cat['key'] ? 'var(--ink-9)' : 'var(--card)' }};
                            box-shadow:{{ $category === $cat['key'] ? 'none' : 'inset 0 0 0 1px var(--ink-3)' }};
                            font:12.5px var(--font-sans); color:{{ $category === $cat['key'] ? 'var(--paper)' : 'var(--ink-7)' }}; cursor:pointer;">
                        {{ $cat['label'] }}
                        <span class="mono" style="font:10.5px var(--font-mono); opacity:.7;">{{ $cat['count'] }}</span>
                    </button>
                @endforeach
            </div>

            {{-- Geo selector --}}
            <div style="display:flex; gap:4px; padding:3px; background:var(--ink-2); border-radius:6px;">
                @foreach ([['key' => 'all', 'label' => 'Усі'], ['key' => 'PL', 'label' => '🇵🇱 PL'], ['key' => 'UA', 'label' => '🇺🇦 UA'], ['key' => 'world', 'label' => '🌐 Світ']] as $geo)
                    <button wire:click="$set('geoFilter', '{{ $geo['key'] }}')"
                        style="
                            padding:5px 12px; border-radius:4px; font:500 12px var(--font-sans);
                            color:{{ $geoFilter === $geo['key'] ? 'var(--ink-9)' : 'var(--ink-5)' }};
                            background:{{ $geoFilter === $geo['key'] ? 'var(--card)' : 'transparent' }};
                            box-shadow:{{ $geoFilter === $geo['key'] ? '0 1px 1px rgba(0,0,0,.04)' : 'none' }};
                            cursor:pointer;">
                        {{ $geo['label'] }}
                    </button>
                @endforeach
            </div>
        </div>

        {{-- Phones table --}}
        @if ($category === 'phones')
        <div class="card" style="overflow:hidden; margin-bottom:20px;">
            <div style="display:grid; grid-template-columns:32px 1.4fr 1fr 100px 80px; gap:12px; padding:12px 18px; background:var(--paper-2); border-bottom:1px solid var(--ink-3);">
                <span></span>
                @foreach (['Номер', 'Мітка', 'Гео', 'Роль'] as $h)
                    <span class="eyebrow" style="font-size:10px;">{{ $h }}</span>
                @endforeach
            </div>

            @forelse ($phonePrimaries as $i => $phone)
                <div style="border-top:{{ $i ? '1px solid var(--ink-3)' : 'none' }};">
                    {{-- Primary row --}}
                    <div style="display:grid; grid-template-columns:32px 1.4fr 1fr 100px 80px; gap:12px; padding:14px 18px; align-items:center;">
                        <x-icon.phone width="14" height="14" style="color:var(--ink-4);" />
                        <span class="mono" style="font:14px var(--font-mono); color:var(--ink-9);">{{ $phone->value }}</span>
                        <span style="font:13px var(--font-sans); color:var(--ink-7);">{{ $phone->label }}</span>
                        <span style="font:12.5px var(--font-sans); color:var(--ink-5);">{{ $phone->geo_label }}</span>
                        <span style="display:inline-flex; align-items:center; gap:4px; font:12.5px var(--font-sans);">
                            <span class="dot dot-ok"></span> Головний
                        </span>
                    </div>

                    {{-- Backup rows --}}
                    @foreach ($phone->backups as $backup)
                        <div style="display:grid; grid-template-columns:32px 1.4fr 1fr 100px 80px; gap:12px; padding:10px 18px 10px 34px; align-items:center; background:var(--paper-2); border-top:1px solid var(--ink-3);">
                            <span style="width:6px; height:6px; border-radius:999px; background:var(--info); margin:auto;"></span>
                            <span class="mono" style="font:13px var(--font-mono); color:var(--ink-7);">{{ $backup->value }}</span>
                            <span style="font:12.5px var(--font-sans); color:var(--ink-5);">{{ $backup->label }}</span>
                            <span style="font:12px var(--font-sans); color:var(--ink-5);">{{ $backup->geo_label }}</span>
                            <span style="display:inline-flex; align-items:center; gap:4px; font:12.5px var(--font-sans); color:var(--ink-5);">
                                <span class="dot dot-info"></span> Резерв
                            </span>
                        </div>
                    @endforeach
                </div>
            @empty
                <div style="padding:40px; text-align:center; color:var(--ink-5); font:13px var(--font-sans);">
                    Немає телефонів для обраного гео.
                </div>
            @endforelse
        </div>
        @endif

        {{-- Messengers table --}}
        @if ($category === 'messengers')
        @if ($msgPrimaries->count() > 0)
        <div class="card" style="overflow:hidden; margin-bottom:20px;">
            <div style="display:grid; grid-template-columns:32px 1.4fr 1fr 100px 80px; gap:12px; padding:12px 18px; background:var(--paper-2); border-bottom:1px solid var(--ink-3);">
                <span></span>
                @foreach (['Акаунт', 'Мітка', 'Гео', 'Роль'] as $h)
                    <span class="eyebrow" style="font-size:10px;">{{ $h }}</span>
                @endforeach
            </div>

            @foreach ($msgPrimaries as $i => $msg)
                @php $kind = \App\Models\ContactEntry::MSG_KINDS[$msg->kind] ?? ['label' => $msg->kind, 'color' => '#888', 'short' => '??']; @endphp
                <div style="border-top:{{ $i ? '1px solid var(--ink-3)' : 'none' }};">
                    <div style="display:grid; grid-template-columns:32px 1.4fr 1fr 100px 80px; gap:12px; padding:14px 18px; align-items:center;">
                        <span style="width:24px; height:24px; border-radius:4px; display:inline-flex; align-items:center; justify-content:center; font:bold 9px var(--font-mono); color:#fff; background:{{ $kind['color'] }};">{{ $kind['short'] }}</span>
                        <span class="mono" style="font:13.5px var(--font-mono); color:var(--ink-9);">{{ $msg->value }}</span>
                        <span style="font:13px var(--font-sans); color:var(--ink-7);">{{ $msg->label }}</span>
                        <span style="font:12.5px var(--font-sans); color:var(--ink-5);">{{ $msg->geo_label }}</span>
                        <span style="display:inline-flex; align-items:center; gap:4px; font:12.5px var(--font-sans);">
                            <span class="dot dot-ok"></span> Головний
                        </span>
                    </div>
                    @foreach ($msg->backups as $backup)
                        @php $bk = \App\Models\ContactEntry::MSG_KINDS[$backup->kind] ?? ['label' => $backup->kind, 'color' => '#888', 'short' => '??']; @endphp
                        <div style="display:grid; grid-template-columns:32px 1.4fr 1fr 100px 80px; gap:12px; padding:10px 18px 10px 34px; align-items:center; background:var(--paper-2); border-top:1px solid var(--ink-3);">
                            <span style="width:20px; height:20px; border-radius:3px; display:inline-flex; align-items:center; justify-content:center; font:bold 9px var(--font-mono); color:#fff; background:{{ $bk['color'] }}; opacity:.7;">{{ $bk['short'] }}</span>
                            <span class="mono" style="font:12.5px var(--font-mono); color:var(--ink-7);">{{ $backup->value }}</span>
                            <span style="font:12.5px var(--font-sans); color:var(--ink-5);">{{ $backup->label }}</span>
                            <span style="font:12px var(--font-sans); color:var(--ink-5);">{{ $backup->geo_label }}</span>
                            <span style="display:inline-flex; align-items:center; gap:4px; font:12.5px var(--font-sans); color:var(--ink-5);">
                                <span class="dot dot-info"></span> Резерв
                            </span>
                        </div>
                    @endforeach
                </div>
            @endforeach
        </div>
        @endif
        @endif

        {{-- Prices table --}}
        @if ($category === 'prices')
        @if ($priceBySku->count() > 0)
        <div class="card" style="overflow:hidden;">
            <header style="padding:14px 20px; display:flex; align-items:center; border-bottom:1px solid var(--ink-3); background:var(--paper-2);">
                <span class="eyebrow">Ціни · {{ $priceBySku->count() }} SKU</span>
            </header>
            @foreach ($priceBySku as $sku => $prices)
                <div style="border-top:1px solid var(--ink-3);">
                    <div style="padding:10px 18px; background:var(--paper-2);">
                        <span class="mono" style="font:11.5px var(--font-mono); color:var(--ink-7); letter-spacing:.06em; text-transform:uppercase;">{{ $sku }}</span>
                    </div>
                    @foreach ($prices as $price)
                        <div style="display:grid; grid-template-columns:1fr 1fr 120px 100px; gap:12px; padding:12px 18px; border-top:1px solid var(--ink-3); align-items:center;">
                            <span style="font:13.5px var(--font-sans); color:var(--ink-9);">{{ $price->label }}</span>
                            <div style="display:flex; align-items:baseline; gap:6px;">
                                <span class="mono" style="font:400 18px var(--font-mono); color:var(--ink-9);">
                                    {{ number_format($price->price, 0, '.', ' ') }}
                                    {{ match($price->currency) { 'PLN' => 'zł', 'UAH' => '₴', 'EUR' => '€', 'USD' => '$', default => $price->currency } }}
                                </span>
                                @if ($price->old_price)
                                    <span class="mono" style="font:13px var(--font-mono); color:var(--ink-4); text-decoration:line-through;">
                                        {{ number_format($price->old_price, 0, '.', ' ') }}
                                    </span>
                                @endif
                                <span style="font:12px var(--font-sans); color:var(--ink-5);">{{ $price->price_unit }}</span>
                            </div>
                            <span style="font:12.5px var(--font-sans); color:var(--ink-5);">{{ $price->geo_label }}</span>
                            <span class="mono" style="font:11.5px var(--font-mono); color:var(--ink-5);">{{ $price->currency }}</span>
                        </div>
                    @endforeach
                </div>
            @endforeach
        </div>
        @endif
        @endif
    </div>

    {{-- ─── Tab: Активність ─────────────────────────────────── --}}
    <div x-show="tab === 'activity'" style="padding:32px 40px 64px;">
        <header style="display:flex; align-items:flex-end; margin-bottom:24px;">
            <div style="flex:1;">
                <h3 style="font:400 22px/1 var(--font-sans); color:var(--ink-9);">Журнал змін</h3>
                <p style="margin-top:8px; font:14px var(--font-sans); color:var(--ink-5);">Усі зміни на сайті.</p>
            </div>
        </header>

        @forelse ($activityLogs as $log)
            @php
                $dotClass = str_contains($log->action, 'fail') || str_contains($log->action, 'offline') ? 'dot-bad' : 'dot-ok';
                $bgClass  = str_contains($log->action, 'fail') ? 'var(--bad-soft)' : 'var(--ok-soft)';
            @endphp
            <article class="card" style="margin-bottom:12px; padding:0; overflow:hidden;">
                <header style="padding:12px 18px; display:flex; align-items:center; gap:12px; border-bottom:1px solid var(--ink-3);">
                    <span style="display:inline-flex; align-items:center; gap:6px; height:22px; padding:0 10px; border-radius:999px; font:11.5px/1 var(--font-sans); background:{{ $bgClass }};">
                        <span class="dot {{ $dotClass }}" style="margin:0;"></span>
                        {{ ucfirst($log->action) }}
                    </span>
                    <div style="flex:1;"></div>
                    <span class="mono" style="font:11.5px var(--font-mono); color:var(--ink-4);">{{ $log->created_at->diffForHumans() }}</span>
                </header>
                <footer style="padding:10px 18px; display:flex; align-items:center; gap:10px; background:var(--paper-2);">
                    <span class="avatar" style="width:22px; height:22px; font-size:10px; background:{{ $log->user ? 'var(--ink-9)' : 'var(--ink-4)' }}; color:var(--paper);">
                        {{ strtoupper(substr($log->user?->name ?? 'S', 0, 2)) }}
                    </span>
                    <span style="font:12.5px var(--font-sans); color:var(--ink-9);">{{ $log->user?->name ?? 'System' }}</span>
                    <div style="flex:1;"></div>
                    <span class="mono" style="font:11px var(--font-mono); color:var(--ink-5);">{{ $log->created_at->format('d M H:i') }}</span>
                </footer>
            </article>
        @empty
            <div style="padding:80px 40px; text-align:center; color:var(--ink-5); font:13.5px var(--font-sans);">
                Немає подій для цього сайту.
            </div>
        @endforelse
    </div>

    {{-- ─── Tab: Налаштування ───────────────────────────────── --}}
    <div x-show="tab === 'settings'" style="padding:24px 40px 64px;" x-data="{ sub: 'failover' }">
        <div style="display:flex; gap:2px; margin-bottom:32px; border-bottom:1px solid var(--ink-3);">
            @foreach ([['key' => 'failover', 'label' => 'Failover'], ['key' => 'categories', 'label' => 'Категорії даних'], ['key' => 'api', 'label' => 'API доступ']] as $st)
                <button @click="sub='{{ $st['key'] }}'" style="
                    display:inline-flex; align-items:center; gap:8px;
                    padding:12px 18px; margin-bottom:-1px;
                    border-bottom:2px solid transparent;
                    color:var(--ink-5); font:13.5px var(--font-sans); cursor:pointer; background:transparent;"
                    :style="sub === '{{ $st['key'] }}' ? 'border-bottom-color:var(--ink-9); color:var(--ink-9); font-weight:500;' : ''">
                    {{ $st['label'] }}
                </button>
            @endforeach
        </div>

        {{-- Failover --}}
        <div x-show="sub === 'failover'" style="max-width:900px;">
            <header style="margin-bottom:24px;">
                <div class="eyebrow" style="margin-bottom:8px;">01 · Failover</div>
                <h3 style="font:400 24px/1.15 var(--font-sans); color:var(--ink-9);">SIM-керування та автоматичне перемикання</h3>
                <p style="margin-top:8px; font:13.5px/1.55 var(--font-sans); color:var(--ink-5); max-width:620px;">
                    Якщо головний номер не відповідає — система перемикає на резерв за порядком (#1, #2, …).
                </p>
            </header>
            <div class="card" style="display:grid; grid-template-columns:repeat(4, 1fr);">
                @foreach ([
                    ['l' => 'Статус', 'v' => '<span class="dot dot-ok"></span>Стабільно', 'm' => 'усі головні відповідають'],
                    ['l' => 'Активних правил', 'v' => '4', 'm' => 'у 2 гео-пулах'],
                    ['l' => 'Резервів', 'v' => (string)$phonePrimaries->flatMap->backups->count(), 'm' => 'резервних номерів'],
                    ['l' => 'Останній failover', 'v' => '—', 'm' => 'не було'],
                ] as $i => $s)
                    <div style="padding:16px 20px; {{ $i ? 'border-left:1px solid var(--ink-3);' : '' }}">
                        <div class="eyebrow" style="font-size:10px; margin-bottom:8px;">{{ $s['l'] }}</div>
                        <div style="font:400 18px var(--font-sans); color:var(--ink-9);">{!! $s['v'] !!}</div>
                        <div style="margin-top:4px; font:11.5px var(--font-mono); color:var(--ink-5);">{{ $s['m'] }}</div>
                    </div>
                @endforeach
            </div>
        </div>

        {{-- Categories --}}
        <div x-show="sub === 'categories'" style="max-width:900px;">
            <header style="margin-bottom:24px;">
                <div class="eyebrow" style="margin-bottom:8px;">02 · Категорії даних</div>
                <h3 style="font:400 24px/1.15 var(--font-sans); color:var(--ink-9);">Що саме зберігаємо для цього сайту</h3>
            </header>
            <div class="card" style="overflow:hidden;">
                @foreach ([
                    ['id' => 'phones',     'label' => 'Телефони',        'n' => $phoneCount, 'required' => true],
                    ['id' => 'messengers', 'label' => 'Месенджери',      'n' => $msgCount, 'required' => true],
                    ['id' => 'prices',     'label' => 'Ціни',             'n' => $priceBySku->count(), 'required' => false],
                    ['id' => 'addresses',  'label' => 'Адреси',           'n' => 0, 'required' => false],
                    ['id' => 'socials',    'label' => 'Соц. мережі',      'n' => 0, 'required' => false],
                ] as $i => $cat)
                    <div style="display:grid; grid-template-columns:1fr 80px 80px; gap:16px; padding:16px 20px; {{ $i ? 'border-top:1px solid var(--ink-3);' : '' }} align-items:center;">
                        <div>
                            <div style="display:flex; align-items:center; gap:8px;">
                                <span style="font:14px var(--font-sans); color:var(--ink-9);">{{ $cat['label'] }}</span>
                                @if ($cat['required'])
                                    <span class="pill" style="height:18px; font-size:9.5px; background:var(--accent-soft); color:var(--accent);">обов'язкове</span>
                                @endif
                            </div>
                        </div>
                        <span class="mono num" style="font:14px var(--font-mono); color:var(--ink-9);">{{ $cat['n'] }}</span>
                        @if ($cat['required'])
                            <span style="color:var(--ink-4); display:flex; justify-content:flex-end;"><x-icon.lock width="14" height="14" /></span>
                        @else
                            <span style="display:flex; justify-content:flex-end;">
                                <span style="width:32px; height:18px; border-radius:999px; background:{{ $cat['n'] > 0 ? 'var(--ink-9)' : 'var(--ink-3)' }}; position:relative; display:inline-block; cursor:pointer;">
                                    <span style="position:absolute; top:2px; left:{{ $cat['n'] > 0 ? '16px' : '2px' }}; width:14px; height:14px; border-radius:999px; background:var(--paper);"></span>
                                </span>
                            </span>
                        @endif
                    </div>
                @endforeach
            </div>
        </div>

        {{-- API --}}
        <div x-show="sub === 'api'" style="max-width:900px;">
            <header style="margin-bottom:24px;">
                <div class="eyebrow" style="margin-bottom:8px;">03 · API доступ</div>
                <h3 style="font:400 24px/1.15 var(--font-sans); color:var(--ink-9);">Ключ цього сайту</h3>
            </header>
            <div class="card" style="padding:18px; display:flex; align-items:center; gap:14px;">
                <x-icon.key width="18" height="18" style="color:var(--ink-5);" />
                <span class="mono" style="font:14px var(--font-mono); color:var(--ink-9); flex:1;">db_live_{{ substr(md5($site->id . 'key'), 0, 10) }}…</span>
                <span class="mono" style="font:11px var(--font-mono); color:var(--ink-5);">активний</span>
                <button class="btn btn-secondary btn-sm">Перегенерувати</button>
            </div>
        </div>
    </div>

</div>
