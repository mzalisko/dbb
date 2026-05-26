<div style="flex:1; display:flex; flex-direction:column; overflow-y:auto;" x-data="{ tab: 'data', settingsSub: 'failover', actFilter: 'all' }">

    <x-ui.topbar :crumbs="['Сайти', $site->name]">
        <button onclick="history.back()" class="btn btn-ghost btn-sm">
            &larr; Назад
        </button>
        <button class="btn btn-ghost btn-sm" style="margin-left:4px;">
            &#8635; Sync
        </button>
        <button class="btn btn-primary btn-sm" style="margin-left:4px;">
            + Додати
        </button>
    </x-ui.topbar>

    {{-- Page head (inline, no component) --}}
    <div style="padding:28px 40px 0; flex-shrink:0;">
        @php
            $groupLabel = $site->group ? strtoupper($site->group) : 'NO GROUP';
            $statusLabel = match($site->status) { 'active'=>'АКТИВНИЙ','maintenance'=>'ПАУЗА',default=>'ПОМИЛКА' };
            $statusColor = match($site->status) { 'active'=>'var(--ok)','maintenance'=>'var(--warn)',default=>'var(--bad)' };
        @endphp
        <div style="display:flex; align-items:center; gap:10px; font:11px var(--font-mono); letter-spacing:.08em; color:var(--ink-5); margin-bottom:14px;">
            <span style="display:inline-flex; align-items:center; gap:5px;">
                <span style="width:7px;height:7px;border-radius:999px;background:var(--ok);"></span>
                {{ $groupLabel }}
            </span>
            <span style="color:var(--ink-3);">&middot;</span>
            <span style="display:inline-flex; align-items:center; gap:5px;">
                <span style="width:7px;height:7px;border-radius:999px;background:{{ $statusColor }};"></span>
                {{ $statusLabel }}
            </span>
        </div>
        <h1 style="font:400 36px/1.05 var(--font-sans); letter-spacing:-0.03em; color:var(--ink-9);">{{ $site->name }}</h1>
        <p style="margin-top:12px; font:14px/1.6 var(--font-sans); color:var(--ink-5); max-width:640px;">
            Кілька номерів одночасно. Кожен видимий за своїм гео-правилом — нижче переключіть «Перегляд» щоб побачити, що бачить відвідувач з певної країни.
        </p>
    </div>

    {{-- Tabs --}}
    <div style="padding:0 40px; margin-top:24px; border-bottom:1px solid var(--ink-3); display:flex; gap:0; flex-shrink:0;">
        @php $dataCount = $phoneCount + $msgCount; @endphp
        @foreach ([
            ['key'=>'overview','label'=>'Огляд','count'=>null],
            ['key'=>'data','label'=>'Дані','count'=>$dataCount],
            ['key'=>'activity','label'=>'Активність','count'=>null],
            ['key'=>'settings','label'=>'Налаштування','count'=>null],
        ] as $t)
        <button @click="tab='{{ $t['key'] }}'"
            style="display:inline-flex; align-items:center; gap:6px; padding:12px 20px; margin-bottom:-1px;
                border-bottom:2px solid transparent; font:13.5px var(--font-sans); cursor:pointer; background:transparent;"
            :style="tab==='{{ $t['key'] }}' ? 'border-bottom-color:var(--ink-9);color:var(--ink-9);font-weight:500;' : 'color:var(--ink-5);'">
            {{ $t['label'] }}
            @if ($t['count'] !== null)
                <span style="display:inline-flex;align-items:center;justify-content:center;min-width:20px;height:20px;padding:0 5px;border-radius:999px;font:500 11px var(--font-mono);"
                    :style="tab==='{{ $t['key'] }}' ? 'background:var(--ink-9);color:var(--paper);' : 'background:var(--ink-2);color:var(--ink-6);'">
                    {{ $t['count'] }}
                </span>
            @endif
        </button>
        @endforeach
    </div>

    {{-- ─── Tab: Огляд ─────────────────────────────────────── --}}
    <div x-show="tab==='overview'" style="padding:32px 40px 64px;">
        <div style="display:grid; grid-template-columns:repeat(3, 1fr); gap:14px;">
            @foreach ($geos as $geo)
                <div x-data="{expanded:false}" class="card" style="padding:22px; position:relative; overflow:hidden;">
                    @if($geo['key']==='PL')
                        <div style="position:absolute;top:0;left:0;right:0;height:3px;background:#dc143c;"></div>
                    @elseif($geo['key']==='UA')
                        <div style="position:absolute;top:0;left:0;right:0;height:3px;background:#0057b7;"></div>
                    @else
                        <div style="position:absolute;top:0;left:0;right:0;height:3px;background:var(--ink-4);"></div>
                    @endif
                    <header style="display:flex;align-items:center;gap:10px;margin-bottom:16px;margin-top:4px;">
                        <span style="font:22px var(--font-sans);">{{ $geo['flag'] }}</span>
                        <div>
                            <div style="font:500 14px/1.2 var(--font-sans);color:var(--ink-9);">{{ $geo['label'] }}</div>
                            <div style="font:11.5px var(--font-mono);color:var(--ink-5);margin-top:3px;">
                                {{ $geo['backupPhones']->count()+($geo['primaryPhone']?1:0) }} тел.
                                &middot; {{ $geo['backupMsgs']->count()+($geo['primaryMsg']?1:0) }} месенджерів
                            </div>
                        </div>
                    </header>
                    @if($geo['primaryPhone'])
                        <div style="display:flex;align-items:center;gap:10px;margin-bottom:10px;">
                            <x-icon.phone width="14" height="14" style="color:var(--ink-5);flex-shrink:0;" />
                            <div style="flex:1;min-width:0;">
                                <div class="mono" style="font:14px var(--font-mono);color:var(--ink-9);">{{ $geo['primaryPhone']->value }}</div>
                                <div style="font:11.5px var(--font-sans);color:var(--ink-5);">{{ $geo['primaryPhone']->label }}</div>
                            </div>
                            @if($geo['backupPhones']->count()>0)
                                <button @click="expanded=!expanded" style="font:11.5px var(--font-mono);color:var(--ink-5);cursor:pointer;"
                                    x-text="expanded?'−':'+{{ $geo['backupPhones']->count() }}'"></button>
                            @endif
                        </div>
                        <div x-show="expanded" style="margin-left:24px;margin-bottom:10px;display:flex;flex-direction:column;gap:6px;">
                            @foreach($geo['backupPhones'] as $b)
                                <div style="display:flex;align-items:center;gap:8px;">
                                    <span style="width:6px;height:6px;border-radius:999px;background:var(--info);flex-shrink:0;"></span>
                                    <span class="mono" style="font:12.5px var(--font-mono);color:var(--ink-7);">{{ $b->value }}</span>
                                </div>
                            @endforeach
                        </div>
                    @else
                        <div style="font:13px var(--font-sans);color:var(--ink-4);margin-bottom:10px;font-style:italic;">Немає телефону</div>
                    @endif
                    <div style="border-top:1px solid var(--ink-3);margin:10px 0;"></div>
                    @if($geo['primaryMsg'])
                        @php $k=\App\Models\ContactEntry::MSG_KINDS[$geo['primaryMsg']->kind]??['label'=>$geo['primaryMsg']->kind,'color'=>'#888','short'=>'??']; @endphp
                        <div style="display:flex;align-items:center;gap:10px;">
                            <span style="width:24px;height:24px;border-radius:4px;display:inline-flex;align-items:center;justify-content:center;font:bold 9.5px var(--font-mono);color:#fff;background:{{ $k['color'] }};flex-shrink:0;">{{ $k['short'] }}</span>
                            <div style="flex:1;min-width:0;">
                                <div class="mono" style="font:13.5px var(--font-mono);color:var(--ink-9);">{{ $geo['primaryMsg']->value }}</div>
                                <div style="font:11.5px var(--font-sans);color:var(--ink-5);">{{ $geo['primaryMsg']->label }}</div>
                            </div>
                        </div>
                    @else
                        <div style="font:13px var(--font-sans);color:var(--ink-4);font-style:italic;">Немає месенджера</div>
                    @endif
                </div>
            @endforeach
        </div>
        <div class="card" style="margin-top:24px;display:grid;grid-template-columns:repeat(4,1fr);">
            @foreach([
                ['l'=>'WordPress','v'=>$site->wp_version??'Unknown'],
                ['l'=>'PHP','v'=>$site->php_version??'Unknown'],
                ['l'=>'Статус','v'=>ucfirst($site->status)],
                ['l'=>'Остання перевірка','v'=>$site->last_checked_at?->format('d M H:i')??'Ніколи'],
            ] as $i=>$info)
                <div style="padding:16px 20px;{{ $i?'border-left:1px solid var(--ink-3);':'' }}">
                    <div class="eyebrow" style="font-size:10px;margin-bottom:8px;">{{ $info['l'] }}</div>
                    <div class="mono" style="font:14px var(--font-mono);color:var(--ink-9);">{{ $info['v'] }}</div>
                </div>
            @endforeach
        </div>
    </div>

    {{-- ─── Tab: Дані ───────────────────────────────────────── --}}
    <div x-show="tab==='data'" style="padding:24px 40px 64px;">

        {{-- КАТЕГОРІЯ ДАНИХ --}}
        <div class="eyebrow" style="font-size:10px; margin-bottom:12px;">Категорія даних</div>
        <div style="display:flex; gap:6px; flex-wrap:wrap;">
            @foreach([
                ['key'=>'phones',     'label'=>'Телефони',   'count'=>$phoneCount,   'icon'=>'phone'],
                ['key'=>'messengers', 'label'=>'Месенджери', 'count'=>$msgCount,     'icon'=>'msg'],
                ['key'=>'prices',     'label'=>'Ціни',       'count'=>$priceCount,   'icon'=>'price'],
                ['key'=>'addresses',  'label'=>'Адреси',     'count'=>$addressCount, 'icon'=>'address'],
                ['key'=>'socials',    'label'=>'Соц. мережі','count'=>$socialCount,  'icon'=>'social'],
            ] as $cat)
                <button wire:click="$set('category','{{ $cat['key'] }}')" style="
                    display:inline-flex; align-items:center; gap:6px; height:32px; padding:0 14px; border-radius:999px; cursor:pointer;
                    background:{{ $category===$cat['key']?'var(--ink-9)':'var(--card)' }};
                    color:{{ $category===$cat['key']?'var(--paper)':'var(--ink-7)' }};
                    box-shadow:{{ $category===$cat['key']?'none':'inset 0 0 0 1px var(--ink-3)' }};
                    font:13px var(--font-sans);">
                    {{ $cat['label'] }}
                    <span style="font:10.5px var(--font-mono); opacity:.7;">{{ $cat['count'] }}</span>
                </button>
            @endforeach
            <button wire:click="$set('category','custom')" style="
                display:inline-flex; align-items:center; gap:6px; height:32px; padding:0 14px; border-radius:999px; cursor:pointer;
                background:{{ $category==='custom'?'var(--ink-9)':'var(--card)' }};
                color:{{ $category==='custom'?'var(--paper)':'var(--ink-7)' }};
                box-shadow:{{ $category==='custom'?'none':'inset 0 0 0 1px var(--ink-3)' }};
                font:13px var(--font-sans);">
                + Custom <span style="font:10.5px var(--font-mono);opacity:.7;">0</span>
            </button>
        </div>

        {{-- Hint --}}
        <div style="margin-top:10px; font:12px var(--font-sans); color:var(--ink-5); display:flex; align-items:center; gap:6px;">
            <span style="width:6px;height:6px;border-radius:999px;background:var(--ink-5);flex-shrink:0;"></span>
            <span><strong style="color:var(--ink-7);">Основні</strong> — телефони і месенджери. Інші — ціни, адреси, соцмережі.</span>
        </div>

        {{-- ПЕРЕГЛЯД + geo filter --}}
        <div style="margin-top:20px; display:flex; align-items:center; gap:16px;">
            <span class="eyebrow" style="font-size:10px;">Перегляд</span>
            <div style="display:flex; gap:4px; padding:3px; background:var(--ink-2); border-radius:6px;">
                @php
                    $totalFiltered = $category==='phones' ? $allPhonesAll->count() : ($category==='messengers' ? $allMsgsAll->count() : $priceCount);
                @endphp
                @foreach([
                    ['key'=>'all',   'label'=>'Усі '.$totalFiltered],
                    ['key'=>'world', 'label'=>'🌐 Світ'],
                    ['key'=>'PL',    'label'=>'🇵🇱 PL'],
                    ['key'=>'UA',    'label'=>'🇺🇦 UA'],
                ] as $geo)
                    <button wire:click="$set('geoFilter','{{ $geo['key'] }}')" style="
                        padding:5px 12px; border-radius:4px; font:500 12px var(--font-sans); cursor:pointer;
                        color:{{ $geoFilter===$geo['key']?'var(--ink-9)':'var(--ink-5)' }};
                        background:{{ $geoFilter===$geo['key']?'var(--card)':'transparent' }};
                        box-shadow:{{ $geoFilter===$geo['key']?'0 1px 1px rgba(0,0,0,.04)':'none' }};">
                        {{ $geo['label'] }}
                    </button>
                @endforeach
            </div>
        </div>

        {{-- ── ТЕЛЕФОНИ ── --}}
        @if($category==='phones')
            {{-- ЯК ЦЕ ПРАЦЮЄ box --}}
            <div style="margin-top:16px; padding:14px 18px; border:1px solid #e8cfa0; background:#fdf5e6; border-radius:4px;">
                <div style="font:500 10.5px var(--font-mono); letter-spacing:.08em; color:#b08020; margin-bottom:6px;">ЯК ЦЕ ПРАЦЮЄ</div>
                <p style="font:13px/1.5 var(--font-sans); color:#7a5a10; margin:0;">
                    Резервні номери прив'язані до конкретного <strong>головного</strong> та показані з відступом під ним. Натисніть <strong>+ резерв</strong> щоб додати запасний до будь-якого головного.
                </p>
            </div>

            {{-- Phone table --}}
            <div class="card" style="margin-top:16px; overflow:hidden;">
                {{-- Header --}}
                <div style="display:grid; grid-template-columns:24px 40px 1fr 1fr 160px 120px 32px; gap:12px; padding:10px 18px; background:var(--paper-2); border-bottom:1px solid var(--ink-3);">
                    <span></span>
                    <span class="eyebrow" style="font-size:9.5px;">#</span>
                    <span class="eyebrow" style="font-size:9.5px;">Номер</span>
                    <span class="eyebrow" style="font-size:9.5px;">Мітка</span>
                    <span class="eyebrow" style="font-size:9.5px;">Гео-правило</span>
                    <span class="eyebrow" style="font-size:9.5px;">Роль</span>
                    <span></span>
                </div>

                @forelse($phonePrimaries as $i => $phone)
                    {{-- Separator --}}
                    @if($i > 0)
                        <div style="border-top:1px solid var(--ink-3);"></div>
                    @endif

                    {{-- Primary row --}}
                    <div wire:click="openPhone({{ $phone->id }})"
                         style="display:grid; grid-template-columns:24px 40px 1fr 1fr 160px 120px 32px; gap:12px; padding:14px 18px; align-items:center; cursor:pointer; transition:background .1s;"
                         onmouseover="this.style.background='var(--paper-2)'" onmouseout="this.style.background='transparent'">
                        <span style="color:var(--ink-3); font:14px var(--font-mono); cursor:grab;">&#x2807;</span>
                        <span style="font:11px var(--font-mono); color:var(--ink-4);">#{{ $i+1 }}</span>
                        <span class="mono" style="font:14px var(--font-mono); color:var(--ink-9);">{{ $phone->value }}</span>
                        <span style="font:13px var(--font-sans); color:var(--ink-7);">{{ $phone->label }}</span>
                        <span style="font:12.5px var(--font-sans); color:var(--ink-5);">{{ $phone->geo_label }}</span>
                        <span style="display:inline-flex; align-items:center; gap:5px; font:12.5px var(--font-sans);">
                            <span style="width:7px;height:7px;border-radius:999px;background:var(--ok);"></span> Головний
                        </span>
                        <span style="color:var(--ink-4);">&rarr;</span>
                    </div>

                    {{-- РЕЗЕРВ section --}}
                    @if($phone->backups->count() > 0)
                        <div x-data="{open:true}" style="background:var(--paper-2); border-top:1px solid var(--ink-3);">
                            {{-- РЕЗЕРВ header --}}
                            <div style="display:flex; align-items:center; justify-content:space-between; padding:8px 18px 8px 64px; cursor:pointer;" @click="open=!open">
                                <span style="display:inline-flex; align-items:center; gap:6px; font:11px var(--font-mono); color:var(--ink-5); letter-spacing:.06em;">
                                    <span x-text="open?'&#x25BE;':'&#x25B8;'"></span>
                                    РЕЗЕРВ &middot; {{ $phone->backups->count() }}
                                </span>
                                <button style="font:11.5px var(--font-sans); color:var(--ink-5); cursor:pointer;" @click.stop>
                                    + Додати резерв
                                </button>
                            </div>
                            {{-- Backup rows --}}
                            <div x-show="open">
                                @foreach($phone->backups as $j => $backup)
                                    <div style="display:grid; grid-template-columns:24px 40px 1fr 1fr 160px 120px 32px; gap:12px; padding:10px 18px 10px 64px; align-items:center; border-top:1px solid var(--ink-3); cursor:pointer; transition:background .1s;"
                                         onmouseover="this.style.background='var(--paper-2)'" onmouseout="this.style.background='transparent'">
                                        <span style="color:var(--ink-4); font:12px var(--font-mono);">&hookrightarrow;</span>
                                        <span style="font:11px var(--font-mono); color:var(--ink-4);">#{{ $j+1 }}</span>
                                        <span class="mono" style="font:13px var(--font-mono); color:var(--ink-7);">{{ $backup->value }}</span>
                                        <span style="font:12.5px var(--font-sans); color:var(--ink-5);">{{ $backup->label }}</span>
                                        <span style="font:12px var(--font-sans); color:var(--ink-5);">{{ $backup->geo_label }}</span>
                                        <span style="display:inline-flex; align-items:center; gap:5px; font:12px var(--font-sans); color:var(--ink-5);">
                                            <span style="width:7px;height:7px;border-radius:999px;background:var(--info);"></span> Резерв
                                        </span>
                                        <span style="color:var(--ink-4);">&rarr;</span>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    @endif
                @empty
                    <div style="padding:48px; text-align:center; color:var(--ink-5); font:13px var(--font-sans);">Немає телефонів для обраного гео.</div>
                @endforelse

                {{-- Hidden entries --}}
                @foreach($allPhonesAll->filter(fn($e)=>!$e->visible && is_null($e->parent_id)) as $phone)
                    <div style="display:grid; grid-template-columns:24px 40px 1fr 1fr 160px 120px 32px; gap:12px; padding:14px 18px; align-items:center; border-top:1px solid var(--ink-3); opacity:.5;">
                        <span style="color:var(--ink-3); font:14px var(--font-mono);">&#x2807;</span>
                        <span style="font:11px var(--font-mono); color:var(--ink-4);">#{{ $loop->index+1 }}</span>
                        <span class="mono" style="font:14px var(--font-mono); color:var(--ink-7);">{{ $phone->value }}</span>
                        <span style="font:13px var(--font-sans); color:var(--ink-5);">{{ $phone->label }}</span>
                        <span style="font:12.5px var(--font-sans); color:var(--ink-5);">{{ $phone->geo_label }}</span>
                        <span style="display:inline-flex; align-items:center; gap:5px; font:12.5px var(--font-sans); color:var(--ink-5);">
                            <span style="width:7px;height:7px;border-radius:999px;background:var(--ink-4);"></span> Сховано
                        </span>
                        <span style="color:var(--ink-4);">&rarr;</span>
                    </div>
                    @if($phone->backups->count()>0)
                        <div style="padding:6px 18px 6px 64px; border-top:1px solid var(--ink-3); font:11.5px var(--font-sans); color:var(--ink-4);">
                            + Додати резерв для цього номера
                        </div>
                    @endif
                @endforeach

                {{-- Footer add row --}}
                <div style="padding:12px 18px; border-top:1px solid var(--ink-3); display:flex; align-items:center;">
                    <button style="font:13px var(--font-sans); color:var(--ink-5); cursor:pointer; display:inline-flex; align-items:center; gap:6px;">
                        + Додати головний номер
                    </button>
                </div>
            </div>
        @endif

        {{-- ── МЕСЕНДЖЕРИ ── --}}
        @if($category==='messengers')
            {{-- ЯК ЦЕ ПРАЦЮЄ box --}}
            <div style="margin-top:16px; padding:14px 18px; border:1px solid #e8cfa0; background:#fdf5e6; border-radius:4px;">
                <div style="font:500 10.5px var(--font-mono); letter-spacing:.08em; color:#b08020; margin-bottom:6px;">ЯК ЦЕ ПРАЦЮЄ</div>
                <p style="font:13px/1.5 var(--font-sans); color:#7a5a10; margin:0;">
                    Один <strong>головний месенджер</strong> на гео-пул, інші — резерв. Можна змішувати Telegram / Viber / WhatsApp в одному пулі — клієнт обере зручний.
                </p>
            </div>

            {{-- Platform pills --}}
            @if($msgByKind->count() > 0)
                <div style="margin-top:14px; display:flex; gap:6px; flex-wrap:wrap;">
                    @foreach($msgByKind as $kindKey => $kindEntries)
                        @php $kd = \App\Models\ContactEntry::MSG_KINDS[$kindKey] ?? ['label'=>$kindKey,'color'=>'#888','short'=>'??']; @endphp
                        <span style="display:inline-flex; align-items:center; gap:6px; height:28px; padding:0 12px; border-radius:999px; font:12.5px var(--font-sans);
                            background:{{ $kd['color'] }}20; color:{{ $kd['color'] }}; border:1px solid {{ $kd['color'] }}40;">
                            <span style="width:18px;height:18px;border-radius:3px;background:{{ $kd['color'] }};display:inline-flex;align-items:center;justify-content:center;font:bold 8px var(--font-mono);color:#fff;">{{ $kd['short'] }}</span>
                            {{ $kd['label'] }} {{ $kindEntries->count() }}
                        </span>
                    @endforeach
                </div>
            @endif

            {{-- Messenger table --}}
            <div class="card" style="margin-top:14px; overflow:hidden;">
                <div style="display:grid; grid-template-columns:24px 40px 1.5fr 1fr 160px 120px 32px; gap:12px; padding:10px 18px; background:var(--paper-2); border-bottom:1px solid var(--ink-3);">
                    <span></span>
                    <span class="eyebrow" style="font-size:9.5px;">#</span>
                    <span class="eyebrow" style="font-size:9.5px;">Контакт</span>
                    <span class="eyebrow" style="font-size:9.5px;">Мітка</span>
                    <span class="eyebrow" style="font-size:9.5px;">Гео-правило</span>
                    <span class="eyebrow" style="font-size:9.5px;">Роль</span>
                    <span></span>
                </div>
                @forelse($msgPrimaries as $i => $msg)
                    @php $k=\App\Models\ContactEntry::MSG_KINDS[$msg->kind]??['label'=>$msg->kind,'color'=>'#888','short'=>'??']; @endphp
                    @if($i>0)<div style="border-top:1px solid var(--ink-3);"></div>@endif
                    <div style="display:grid; grid-template-columns:24px 40px 1.5fr 1fr 160px 120px 32px; gap:12px; padding:14px 18px; align-items:center; cursor:pointer; transition:background .1s;"
                         onmouseover="this.style.background='var(--paper-2)'" onmouseout="this.style.background='transparent'">
                        <span style="color:var(--ink-3);font:14px var(--font-mono);">&#x2807;</span>
                        <span style="font:11px var(--font-mono);color:var(--ink-4);">#{{ $i+1 }}</span>
                        <div style="display:flex;align-items:center;gap:8px;">
                            <span style="width:28px;height:28px;border-radius:6px;display:inline-flex;align-items:center;justify-content:center;font:bold 10px var(--font-mono);color:#fff;background:{{ $k['color'] }};flex-shrink:0;">{{ $k['short'] }}</span>
                            <div>
                                <div class="mono" style="font:13.5px var(--font-mono);color:var(--ink-9);">{{ $msg->value }}</div>
                                <div style="font:11px var(--font-sans);color:var(--ink-5);">{{ $k['label'] }}</div>
                            </div>
                        </div>
                        <span style="font:13px var(--font-sans);color:var(--ink-7);">{{ $msg->label }}</span>
                        <span style="font:12.5px var(--font-sans);color:var(--ink-5);">{{ $msg->geo_label }}</span>
                        <span style="display:inline-flex;align-items:center;gap:5px;font:12.5px var(--font-sans);">
                            <span style="width:7px;height:7px;border-radius:999px;background:var(--ok);"></span> Головний
                        </span>
                        <span style="color:var(--ink-4);">&rarr;</span>
                    </div>
                    @if($msg->backups->count()>0)
                        <div x-data="{open:true}" style="background:var(--paper-2);border-top:1px solid var(--ink-3);">
                            <div style="display:flex;align-items:center;justify-content:space-between;padding:8px 18px 8px 64px;cursor:pointer;" @click="open=!open">
                                <span style="font:11px var(--font-mono);color:var(--ink-5);letter-spacing:.06em;">
                                    <span x-text="open?'&#x25BE;':'&#x25B8;'"></span> РЕЗЕРВ &middot; {{ $msg->backups->count() }}
                                </span>
                                @php
                                    $backupKinds = $msg->backups->groupBy('kind');
                                    $kindShorts = $backupKinds->keys()->map(fn($k)=>(\App\Models\ContactEntry::MSG_KINDS[$k]??['short'=>'??'])['short'])->join(' · ');
                                @endphp
                                <span style="font:11px var(--font-mono);color:var(--ink-4);">{{ $kindShorts }}</span>
                            </div>
                            <div x-show="open">
                                @foreach($msg->backups as $j=>$backup)
                                    @php $bk=\App\Models\ContactEntry::MSG_KINDS[$backup->kind]??['label'=>$backup->kind,'color'=>'#888','short'=>'??']; @endphp
                                    <div style="display:grid;grid-template-columns:24px 40px 1.5fr 1fr 160px 120px 32px;gap:12px;padding:10px 18px 10px 64px;align-items:center;border-top:1px solid var(--ink-3);">
                                        <span style="color:var(--ink-4);font:12px var(--font-mono);">&hookrightarrow;</span>
                                        <span style="font:11px var(--font-mono);color:var(--ink-4);">#{{ $j+1 }}</span>
                                        <div style="display:flex;align-items:center;gap:8px;">
                                            <span style="width:22px;height:22px;border-radius:4px;display:inline-flex;align-items:center;justify-content:center;font:bold 9px var(--font-mono);color:#fff;background:{{ $bk['color'] }};opacity:.8;">{{ $bk['short'] }}</span>
                                            <span class="mono" style="font:13px var(--font-mono);color:var(--ink-7);">{{ $backup->value }}</span>
                                        </div>
                                        <span style="font:12.5px var(--font-sans);color:var(--ink-5);">{{ $backup->label }}</span>
                                        <span style="font:12px var(--font-sans);color:var(--ink-5);">{{ $backup->geo_label }}</span>
                                        <span style="display:inline-flex;align-items:center;gap:5px;font:12px var(--font-sans);color:var(--ink-5);">
                                            <span style="width:7px;height:7px;border-radius:999px;background:var(--info);"></span> Резерв
                                        </span>
                                        <span style="color:var(--ink-4);">&rarr;</span>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    @endif
                @empty
                    <div style="padding:48px;text-align:center;color:var(--ink-5);font:13px var(--font-sans);">Немає месенджерів.</div>
                @endforelse
                <div style="padding:12px 18px;border-top:1px solid var(--ink-3);">
                    <button style="font:13px var(--font-sans);color:var(--ink-5);cursor:pointer;">+ Додати головний месенджер</button>
                </div>
            </div>
        @endif

        {{-- ── ЦІНИ ── --}}
        @if($category==='prices')
            {{-- MULTI-CURRENCY info box --}}
            <div style="margin-top:16px; padding:14px 18px; border:1px solid #e8cfa0; background:#fdf5e6; border-radius:4px;">
                <div style="font:500 10.5px var(--font-mono); letter-spacing:.08em; color:#b08020; margin-bottom:6px;">MULTI-CURRENCY</div>
                <p style="font:13px/1.5 var(--font-sans); color:#7a5a10; margin:0;">
                    Один <strong>SKU</strong>, кілька цін під різні гео — клієнт у Польщі бачить PLN, у Україні — UAH, решта світу — EUR/USD. Стара ціна показується як <span style="text-decoration:line-through;">перекреслена</span>.
                </p>
            </div>

            {{-- Stats --}}
            @php
                $activePriceCount = $allPricesAll->where('visible',true)->count();
                $currencyCount = $allPricesAll->pluck('currency')->unique()->count();
            @endphp
            <div style="margin-top:10px; font:12px var(--font-mono); color:var(--ink-5);">
                {{ $activePriceCount }} активних &middot; {{ $currencyCount }} {{ $currencyCount===1?'валюта':'валют' }}
            </div>

            <div class="card" style="margin-top:14px; overflow:hidden;">
                <div style="display:grid; grid-template-columns:24px 40px 1.4fr 1fr 180px 160px 80px 32px; gap:12px; padding:10px 18px; background:var(--paper-2); border-bottom:1px solid var(--ink-3);">
                    <span></span><span></span>
                    <span class="eyebrow" style="font-size:9.5px;">Назва &middot; SKU</span>
                    <span class="eyebrow" style="font-size:9.5px;">Ціна</span>
                    <span class="eyebrow" style="font-size:9.5px;">Валюта &middot; Одиниця</span>
                    <span class="eyebrow" style="font-size:9.5px;">Гео-правило</span>
                    <span class="eyebrow" style="font-size:9.5px;">Статус</span>
                    <span></span>
                </div>
                @foreach($priceBySkuAll as $sku => $prices)
                    {{-- SKU group header --}}
                    <div style="display:flex; align-items:center; justify-content:space-between; padding:8px 18px; background:var(--paper-2); border-top:1px solid var(--ink-3);">
                        <span style="font:11px var(--font-mono); color:var(--ink-5); letter-spacing:.06em;">
                            <span class="eyebrow" style="font-size:9px; margin-right:8px;">SKU</span>
                            <strong style="color:var(--ink-9);">{{ $sku }}</strong>
                            <span style="color:var(--ink-4); margin-left:8px;">&middot; {{ $prices->first()?->label }}</span>
                        </span>
                        <span style="font:11px var(--font-mono); color:var(--ink-4);">{{ $prices->count() }} {{ $prices->count()===1?'ціна':'цін' }}</span>
                    </div>
                    @foreach($prices as $j => $price)
                        @php
                            $currSymbol = match($price->currency) {'PLN'=>'zł','UAH'=>'₴','EUR'=>'€','USD'=>'$',default=>$price->currency};
                            $currFlag = match($price->currency) {'PLN'=>'🇵🇱','UAH'=>'🇺🇦','EUR'=>'🇪🇺','USD'=>'🇺🇸',default=>''};
                        @endphp
                        <div style="display:grid; grid-template-columns:24px 40px 1.4fr 1fr 180px 160px 80px 32px; gap:12px; padding:14px 18px; align-items:center; border-top:1px solid var(--ink-3); cursor:pointer; transition:background .1s;"
                             onmouseover="this.style.background='var(--paper-2)'" onmouseout="this.style.background='transparent'">
                            <span style="color:var(--ink-3);font:14px var(--font-mono);">&#x2807;</span>
                            <span style="font:11px var(--font-mono);color:var(--ink-4);">#{{ $j+1 }}</span>
                            <div>
                                <div style="font:13.5px var(--font-sans);color:var(--ink-9);">{{ $price->label }}</div>
                                <div style="font:11px var(--font-mono);color:var(--ink-4);margin-top:2px;">SKU &middot; {{ $price->sku }}</div>
                            </div>
                            <div style="display:flex;align-items:baseline;gap:6px;">
                                <span class="mono" style="font:400 18px var(--font-mono);color:var(--ink-9);">
                                    {{ number_format($price->price,0,'.',' ') }} {{ $currSymbol }}
                                </span>
                                @if($price->old_price)
                                    <span class="mono" style="font:13px var(--font-mono);color:var(--ink-4);text-decoration:line-through;">{{ number_format($price->old_price,0,'.',' ') }}</span>
                                @endif
                            </div>
                            <span style="font:12.5px var(--font-sans);color:var(--ink-5);">
                                {{ $currFlag }} {{ $price->currency }} /{{ $price->price_unit }}
                            </span>
                            <span style="font:12.5px var(--font-sans);color:var(--ink-5);">{{ $price->geo_label }}</span>
                            <span style="display:inline-flex;align-items:center;gap:5px;font:12px var(--font-sans);">
                                <span style="width:7px;height:7px;border-radius:999px;background:{{ $price->visible?'var(--ok)':'var(--ink-4)' }};"></span>
                            </span>
                            <span style="color:var(--ink-4);">&rarr;</span>
                        </div>
                    @endforeach
                @endforeach
                @if($priceBySkuAll->isEmpty())
                    <div style="padding:48px;text-align:center;color:var(--ink-5);font:13px var(--font-sans);">Немає цін.</div>
                @endif
            </div>
        @endif

        {{-- Адреси / Соц. мережі / Custom --}}
        @if(in_array($category,['addresses','socials','custom']))
            <div style="margin-top:48px; padding:80px; text-align:center; color:var(--ink-5); font:13.5px var(--font-sans);">
                Цей розділ у розробці.
            </div>
        @endif

    </div>{{-- end tab data --}}

    {{-- ─── Tab: Активність ─────────────────────────────────── --}}
    <div x-show="tab==='activity'" style="padding:32px 40px 64px;">
        <div style="display:flex; align-items:flex-end; justify-content:space-between; margin-bottom:8px;">
            <h3 style="font:400 28px/1 var(--font-sans); color:var(--ink-9); letter-spacing:-0.02em;">Журнал змін</h3>
            <span style="font:12px var(--font-mono); color:var(--ink-5);">{{ $activityLogs->count() }} подій &middot; 30 днів</span>
        </div>
        <p style="font:13.5px var(--font-sans); color:var(--ink-5); margin-bottom:20px;">
            Натисніть рядок щоб переглянути повний diff і метадані. Експорт у CSV — справа зверху.
        </p>

        {{-- Filter pills --}}
        <div style="display:flex; align-items:center; gap:6px; margin-bottom:20px; flex-wrap:wrap;">
            @php
                $actTypes = ['all'=>'Усі','change'=>'Зміни','failover'=>'Failover','create'=>'Створення','delete'=>'Видалення','alert'=>'Сповіщення'];
            @endphp
            @foreach($actTypes as $key => $label)
                @php
                    $cnt = $key==='all' ? $activityLogs->count() : $activityLogs->filter(fn($l)=>str_contains($l->action,$key))->count();
                @endphp
                <button @click="actFilter='{{ $key }}'" style="
                    display:inline-flex; align-items:center; gap:5px; height:30px; padding:0 12px; border-radius:999px; cursor:pointer; font:12.5px var(--font-sans);"
                    :style="actFilter==='{{ $key }}' ? 'background:var(--ink-9);color:var(--paper);' : 'background:var(--card);color:var(--ink-7);box-shadow:inset 0 0 0 1px var(--ink-3);'">
                    {{ $label }} {{ $cnt }}
                </button>
            @endforeach
            <div style="flex:1;"></div>
            <button style="display:inline-flex;align-items:center;gap:5px;font:12.5px var(--font-sans);color:var(--ink-5);cursor:pointer;">
                &uarr; Експорт
            </button>
        </div>

        {{-- Activity table --}}
        <div class="card" style="overflow:hidden;">
            <div style="display:grid; grid-template-columns:40px 1fr 160px 120px 32px; gap:12px; padding:10px 18px; background:var(--paper-2); border-bottom:1px solid var(--ink-3);">
                <span></span>
                <span class="eyebrow" style="font-size:9.5px;">Подія та об'єкт</span>
                <span class="eyebrow" style="font-size:9.5px;">Хто</span>
                <span class="eyebrow" style="font-size:9.5px;">Коли</span>
                <span></span>
            </div>
            @forelse($activityLogs as $i => $log)
                @php
                    $isFail = str_contains($log->action,'fail')||str_contains($log->action,'offline')||str_contains($log->action,'error');
                    $isCreate = str_contains($log->action,'creat')||str_contains($log->action,'add');
                    $isDelete = str_contains($log->action,'delet')||str_contains($log->action,'remov');
                    $dotBg = $isFail ? 'var(--bad)' : ($isCreate ? 'var(--ok)' : ($isDelete ? 'var(--bad)' : 'var(--info)'));
                    $iconText = $isFail ? '&#x26A1;' : ($isCreate ? '+' : ($isDelete ? '&#x2715;' : '&#x270E;'));
                @endphp
                <div x-data="{open:false}" style="border-top:{{ $i?'1px solid var(--ink-3)':'none' }};">
                    <div @click="open=!open" style="display:grid; grid-template-columns:40px 1fr 160px 120px 32px; gap:12px; padding:14px 18px; align-items:center; cursor:pointer; transition:background .1s;"
                         onmouseover="this.style.background='var(--paper-2)'" onmouseout="this.style.background='transparent'">
                        <span style="width:28px;height:28px;border-radius:6px;background:{{ $dotBg }}20;display:inline-flex;align-items:center;justify-content:center;font:13px var(--font-sans);color:{{ $dotBg }};">{!! $iconText !!}</span>
                        <div>
                            <div style="font:13.5px var(--font-sans);color:var(--ink-9);">{{ ucfirst($log->action) }}</div>
                            @if($log->subject_type)
                                <div style="font:12px var(--font-mono);color:var(--ink-5);margin-top:2px;">{{ class_basename($log->subject_type) }}</div>
                            @endif
                        </div>
                        <div style="display:flex;align-items:center;gap:8px;">
                            <span class="avatar" style="width:22px;height:22px;font-size:10px;background:{{ $log->user?'var(--ink-9)':'var(--ink-4)' }};color:var(--paper);">
                                {{ strtoupper(substr($log->user?->name??'S',0,2)) }}
                            </span>
                            <span style="font:12.5px var(--font-sans);color:var(--ink-7);">{{ $log->user?->name??'System' }}</span>
                        </div>
                        <span class="mono" style="font:12px var(--font-mono);color:var(--ink-5);">{{ $log->created_at->diffForHumans(null,true) }}</span>
                        <span style="color:var(--ink-4);" x-text="open?'&#x2228;':'&#x203A;'"></span>
                    </div>
                    {{-- Expandable diff --}}
                    <div x-show="open" style="padding:14px 18px 14px 70px; background:var(--paper-2); border-top:1px solid var(--ink-3);">
                        <div style="font:12px var(--font-mono); color:var(--ink-5); margin-bottom:8px;">{{ $log->created_at->format('d M · H:i:s') }}</div>
                        @if($log->properties)
                            @foreach(is_array($log->properties) ? $log->properties : [] as $field => $change)
                                <div style="display:grid;grid-template-columns:120px 1fr 24px 1fr;gap:8px;padding:6px 0;border-top:1px solid var(--ink-3);align-items:center;font:12.5px var(--font-sans);">
                                    <span style="color:var(--ink-5);">{{ $field }}</span>
                                    <span style="padding:4px 8px;background:#fde8e8;border-radius:3px;font:12px var(--font-mono);color:#c00;">{{ is_array($change)?($change['old']??'—'):$change }}</span>
                                    <span style="text-align:center;color:var(--ink-4);">&rarr;</span>
                                    <span style="padding:4px 8px;background:#e6f4ea;border-radius:3px;font:12px var(--font-mono);color:#1a7a30;">{{ is_array($change)?($change['new']??'—'):$change }}</span>
                                </div>
                            @endforeach
                        @else
                            <p style="font:13px var(--font-sans);color:var(--ink-5);">Деталі недоступні.</p>
                        @endif
                    </div>
                </div>
            @empty
                <div style="padding:64px;text-align:center;color:var(--ink-5);font:13.5px var(--font-sans);">Немає подій для цього сайту.</div>
            @endforelse
        </div>
    </div>

    {{-- ─── Tab: Налаштування ───────────────────────────────── --}}
    <div x-show="tab==='settings'" style="padding:24px 40px 64px;">
        {{-- Sub-tabs --}}
        <div style="display:flex; gap:0; border-bottom:1px solid var(--ink-3); margin-bottom:32px;">
            @foreach([
                ['key'=>'failover','label'=>'Failover','count'=>$phonePrimaries->count()],
                ['key'=>'categories','label'=>'Категорії даних','count'=>6],
                ['key'=>'api','label'=>'API доступ','count'=>1],
            ] as $st)
                <button @click="settingsSub='{{ $st['key'] }}'" style="
                    display:inline-flex;align-items:center;gap:6px;padding:12px 18px;margin-bottom:-1px;
                    border-bottom:2px solid transparent;font:13.5px var(--font-sans);cursor:pointer;background:transparent;"
                    :style="settingsSub==='{{ $st['key'] }}' ? 'border-bottom-color:var(--ink-9);color:var(--ink-9);font-weight:500;' : 'color:var(--ink-5);'">
                    {{ $st['label'] }}
                    <span style="display:inline-flex;align-items:center;justify-content:center;width:18px;height:18px;border-radius:999px;font:10px var(--font-mono);"
                        :style="settingsSub==='{{ $st['key'] }}' ? 'background:var(--ink-9);color:var(--paper);' : 'background:var(--ink-2);color:var(--ink-5);'">
                        {{ $st['count'] }}
                    </span>
                </button>
            @endforeach
        </div>

        {{-- Failover --}}
        <div x-show="settingsSub==='failover'" style="max-width:900px;">
            <div class="eyebrow" style="font-size:10px;margin-bottom:10px;">01 &middot; Failover</div>
            <h3 style="font:400 28px/1.1 var(--font-sans);color:var(--ink-9);letter-spacing:-0.02em;margin-bottom:10px;">SIM-керування та автоматичне перемикання</h3>
            <p style="font:13.5px/1.55 var(--font-sans);color:var(--ink-5);max-width:600px;margin-bottom:24px;">
                Якщо головний номер не відповідає — система перемикає на резерв за порядком (#1, #2, …). Гео-правила резерву мають збігатися з головним.
            </p>
            <div class="card" style="display:grid;grid-template-columns:repeat(4,1fr);margin-bottom:24px;">
                @foreach([
                    ['l'=>'Статус','v'=>'<span style="display:inline-flex;align-items:center;gap:5px;"><span style="width:7px;height:7px;border-radius:999px;background:var(--ok);"></span>Стабільно</span>','m'=>'усі головні відповідають'],
                    ['l'=>'Активних правил','v'=>'4','m'=>'у 2 гео-пулах'],
                    ['l'=>'Резервів','v'=>(string)$phonePrimaries->flatMap->backups->count(),'m'=>'у середньому 1.25 / пул'],
                    ['l'=>'Останній failover','v'=>'13 трав','m'=>'manual · PL пул'],
                ] as $i=>$s)
                    <div style="padding:16px 20px;{{ $i?'border-left:1px solid var(--ink-3);':'' }}">
                        <div class="eyebrow" style="font-size:9.5px;margin-bottom:8px;">{{ $s['l'] }}</div>
                        <div style="font:400 22px var(--font-sans);color:var(--ink-9);">{!! $s['v'] !!}</div>
                        <div style="margin-top:4px;font:11.5px var(--font-mono);color:var(--ink-5);">{{ $s['m'] }}</div>
                    </div>
                @endforeach
            </div>

            {{-- ПРАВИЛА ПЕРЕМИКАННЯ --}}
            <div class="card" style="overflow:hidden;">
                <div style="display:flex;align-items:center;justify-content:space-between;padding:14px 20px;border-bottom:1px solid var(--ink-3);">
                    <span class="eyebrow" style="font-size:10px;">Правила перемикання</span>
                    <button class="btn btn-primary btn-sm" style="background:var(--bad);border-color:var(--bad);">&#x26A1; Тригер вручну</button>
                </div>
                @foreach([
                    ['title'=>'Авто-failover','desc'=>'Перемикати на резерв без участі оператора, коли health-check провалює поріг.','toggle'=>true],
                    ['title'=>'Інтервал перевірки','desc'=>'Як часто пінгуємо головний номер. Менший інтервал = швидша реакція, більше навантаження.','toggle'=>false,'options'=>['1 хв','5 хв','15 хв'],'active'=>'5 хв'],
                    ['title'=>'Поріг провалів','desc'=>'Кількість невдалих перевірок поспіль перед failover.','toggle'=>false,'options'=>['2','3','5'],'active'=>'3'],
                ] as $i=>$row)
                    <div style="display:flex;align-items:center;justify-content:space-between;padding:18px 20px;{{ $i?'border-top:1px solid var(--ink-3);':'' }}">
                        <div style="max-width:500px;">
                            <div style="font:14px var(--font-sans);color:var(--ink-9);margin-bottom:4px;">{{ $row['title'] }}</div>
                            <div style="font:12.5px var(--font-sans);color:var(--ink-5);">{{ $row['desc'] }}</div>
                        </div>
                        @if($row['toggle'])
                            <div style="width:44px;height:24px;border-radius:999px;background:var(--ink-9);position:relative;cursor:pointer;flex-shrink:0;">
                                <span style="position:absolute;top:3px;right:3px;width:18px;height:18px;border-radius:999px;background:var(--paper);"></span>
                            </div>
                        @else
                            <div style="display:flex;gap:4px;flex-shrink:0;">
                                @foreach($row['options'] as $opt)
                                    <button style="padding:5px 14px;border-radius:4px;font:12.5px var(--font-sans);cursor:pointer;
                                        background:{{ $opt===$row['active']?'var(--ink-9)':'var(--card)' }};
                                        color:{{ $opt===$row['active']?'var(--paper)':'var(--ink-7)' }};
                                        box-shadow:{{ $opt===$row['active']?'none':'inset 0 0 0 1px var(--ink-3)' }};">{{ $opt }}</button>
                                @endforeach
                            </div>
                        @endif
                    </div>
                @endforeach
            </div>
        </div>

        {{-- Категорії --}}
        <div x-show="settingsSub==='categories'" style="max-width:900px;">
            <div class="eyebrow" style="font-size:10px;margin-bottom:10px;">02 &middot; Категорії даних</div>
            <h3 style="font:400 28px/1.1 var(--font-sans);color:var(--ink-9);letter-spacing:-0.02em;margin-bottom:24px;">Що саме зберігаємо для цього сайту</h3>
            <div class="card" style="overflow:hidden;">
                @foreach([
                    ['id'=>'phones','label'=>'Телефони','n'=>$phoneCount,'req'=>true],
                    ['id'=>'messengers','label'=>'Месенджери','n'=>$msgCount,'req'=>true],
                    ['id'=>'prices','label'=>'Ціни','n'=>$priceCount,'req'=>false],
                    ['id'=>'addresses','label'=>'Адреси','n'=>$addressCount,'req'=>false],
                    ['id'=>'socials','label'=>'Соц. мережі','n'=>$socialCount,'req'=>false],
                    ['id'=>'custom','label'=>'Custom','n'=>0,'req'=>false],
                ] as $i=>$cat)
                    <div style="display:grid;grid-template-columns:1fr 60px 60px;gap:16px;padding:16px 20px;{{ $i?'border-top:1px solid var(--ink-3);':'' }};align-items:center;">
                        <div style="display:flex;align-items:center;gap:8px;">
                            <span style="font:14px var(--font-sans);color:var(--ink-9);">{{ $cat['label'] }}</span>
                            @if($cat['req'])<span class="pill" style="height:18px;font-size:9.5px;background:var(--accent-soft);color:var(--accent);">обов'язкове</span>@endif
                        </div>
                        <span class="mono" style="font:14px var(--font-mono);color:var(--ink-9);">{{ $cat['n'] }}</span>
                        @if($cat['req'])
                            <span style="color:var(--ink-4);display:flex;justify-content:flex-end;"><x-icon.lock width="14" height="14" /></span>
                        @else
                            <span style="display:flex;justify-content:flex-end;">
                                <span style="width:32px;height:18px;border-radius:999px;background:{{ $cat['n']>0?'var(--ink-9)':'var(--ink-3)' }};position:relative;display:inline-block;cursor:pointer;">
                                    <span style="position:absolute;top:2px;left:{{ $cat['n']>0?'16px':'2px' }};width:14px;height:14px;border-radius:999px;background:var(--paper);"></span>
                                </span>
                            </span>
                        @endif
                    </div>
                @endforeach
            </div>
        </div>

        {{-- API --}}
        <div x-show="settingsSub==='api'" style="max-width:900px;">
            <div class="eyebrow" style="font-size:10px;margin-bottom:10px;">03 &middot; API доступ</div>
            <h3 style="font:400 28px/1.1 var(--font-sans);color:var(--ink-9);letter-spacing:-0.02em;margin-bottom:24px;">Ключ цього сайту</h3>
            <div class="card" style="padding:18px;display:flex;align-items:center;gap:14px;">
                <x-icon.key width="18" height="18" style="color:var(--ink-5);" />
                <span class="mono" style="font:14px var(--font-mono);color:var(--ink-9);flex:1;">db_live_{{ substr(md5($site->id.'key'),0,10) }}…</span>
                <span class="mono" style="font:11px var(--font-mono);color:var(--ink-5);">активний</span>
                <button class="btn btn-secondary btn-sm">Перегенерувати</button>
            </div>
        </div>
    </div>

    {{-- ─── PhoneDrawer ─────────────────────────────────────── --}}
    @if ($openPhoneId)
        @php $ph = $allPhones->firstWhere('id', $openPhoneId); @endphp
        @if ($ph)
            <x-ui.drawer :open="true" :title="$ph->value" :sub="$ph->label . ' · ' . $ph->geo_label" @drawer-close.window="$wire.closePhone()">
                <div style="display:flex;flex-direction:column;gap:20px;">
                    <div><label class="label">Номер</label><div class="mono" style="font:20px var(--font-mono);color:var(--ink-9);margin-top:8px;">{{ $ph->value }}</div></div>
                    <div><label class="label">Мітка</label><input class="input" value="{{ $ph->label }}" readonly /></div>
                    <div>
                        <label class="label">Роль</label>
                        <div style="display:flex;gap:8px;margin-top:8px;">
                            @foreach([['primary','Головний'],['backup','Резерв'],['archive','Архів']] as [$k,$l])
                                <span style="flex:1;padding:10px;text-align:center;border-radius:4px;font:13px var(--font-sans);
                                    border:1px solid {{ $ph->role===$k?'var(--ink-9)':'var(--ink-3)' }};
                                    background:{{ $ph->role===$k?'var(--ink-9)':'transparent' }};
                                    color:{{ $ph->role===$k?'var(--paper)':'var(--ink-7)' }};">{{ $l }}</span>
                            @endforeach
                        </div>
                    </div>
                    <div><label class="label">Гео-правило</label><div style="margin-top:8px;font:13.5px var(--font-sans);color:var(--ink-7);">{{ $ph->geo_label ?: 'Усі гео' }}</div></div>
                </div>
                <x-slot:footer>
                    <button class="btn btn-ghost" wire:click="closePhone">Закрити</button>
                    <button class="btn btn-primary" disabled style="opacity:.4;">Редагувати (незабаром)</button>
                </x-slot:footer>
            </x-ui.drawer>
        @endif
    @endif
</div>
