{{-- ─── Tab: Налаштування ───────────────────────────────── --}}
<div x-show="tab==='settings'" x-cloak class="tab-pane" style="padding-top:24px;">
    {{-- Sub-tabs --}}
    <div class="tabs sub-tabs">
        @foreach([
            ['key'=>'failover','label'=>'Failover','count'=>$phonePrimaries->count()],
            ['key'=>'categories','label'=>'Категорії даних','count'=>count($dataCategories)],
            ['key'=>'general','label'=>'Загальне','count'=>2],
        ] as $st)
            <button class="tab" :class="settingsSub==='{{ $st['key'] }}' ? 'active' : ''" @click="settingsSub='{{ $st['key'] }}'">
                {{ $st['label'] }}
                <span class="tab-n">{{ $st['count'] }}</span>
            </button>
        @endforeach
    </div>

    {{-- Failover --}}
    @php
        $queueGroups = [];
        foreach ($phonePrimaries as $primary) {
            $geo = $primary->preview_geo_label ?? (($primary->geo_mode === 'all') ? 'ALL' : '');
            $group = ['items' => [
                ['id' => 'p' . $primary->id, 'num' => $primary->value ?? '—', 'geo' => $geo, 'status' => 'active', 'label' => 'АКТИВНИЙ'],
            ]];
            $rn = 1;
            foreach ($primary->backups->where('visible', true)->sortBy('order') as $backup) {
                $bgeo = $backup->preview_geo_label ?? $geo;
                $group['items'][] = ['id' => 'b' . $backup->id, 'num' => $backup->value ?? '—', 'geo' => $bgeo, 'status' => 'reserve', 'label' => 'РЕЗЕРВ ' . $rn++];
            }
            $queueGroups[] = $group;
        }
    @endphp
    <div x-show="settingsSub==='failover'" x-cloak class="set-section"
         @phones-updated.window="groups = @js($queueGroups)"
         x-data="{
           jTab: 'queue',
           groups: @js($queueGroups),
           sortGroup(gi) {
             const ord = { active: 0, reserve: 1, waiting: 2 };
             this.groups[gi].items.sort((a, b) => ord[a.status] - ord[b.status]);
           },
           renumber(gi) {
             let rn = 1;
             this.groups[gi].items.forEach(n => { if (n.status === 'reserve') n.label = 'РЕЗЕРВ ' + rn++; });
           },
           doTrigger(gi) {
             const items = this.groups[gi].items;
             const active  = items.find(n => n.status === 'active');
             const reserve = items.find(n => n.status === 'reserve');
             if (!active || !reserve) return;
             active.status  = 'waiting'; active.label  = 'ОЧІКУВАННЯ';
             reserve.status = 'active';  reserve.label = 'АКТИВНИЙ';
             this.renumber(gi);
             this.sortGroup(gi);
             this.jTab = 'queue';
           },
           doRollback(gi, itemId) {
             const items   = this.groups[gi].items;
             const current = items.find(n => n.status === 'active');
             const target  = items.find(n => n.id === itemId);
             if (!target) return;
             if (current) { current.status = 'reserve'; }
             target.status = 'active'; target.label = 'АКТИВНИЙ';
             this.renumber(gi);
             this.sortGroup(gi);
           }
         }">
        <div class="eyebrow eyebrow-xs" style="margin-bottom:10px;">01 &middot; Failover</div>
        <h3 class="set-title set-title--lead">SIM-керування та автоматичне перемикання</h3>
        <p class="set-lead">
            Якщо активний номер не відповідає — система перемикає на резерв за порядком (#1, #2, …). Гео-правила резерву успадковуються від батьківського номера.
        </p>

        {{-- Stats — повна ширина --}}
        <div class="card set-stats">
            @foreach([
                ['l'=>'Статус','v'=>'<span class="set-stat__status"><span class="role-dot" style="background:var(--ok);"></span>Стабільно</span>','m'=>'усі активні відповідають'],
                ['l'=>'Активних правил','v'=>'4','m'=>'у 2 гео-пулах'],
                ['l'=>'Резервів','v'=>(string)$phonePrimaries->flatMap->backups->count(),'m'=>'у середньому 1.25 / пул'],
                ['l'=>'Останній failover','v'=>'13 трав','m'=>'manual · PL пул'],
            ] as $s)
                <div class="set-stat">
                    <div class="eyebrow eyebrow-xxs" style="margin-bottom:8px;">{{ $s['l'] }}</div>
                    <div class="set-stat__val">{!! $s['v'] !!}</div>
                    <div class="set-stat__meta">{{ $s['m'] }}</div>
                </div>
            @endforeach
        </div>

        {{-- Rules + Journal side by side --}}
        <div class="set-failover-grid">

            {{-- ПРАВИЛА ПЕРЕМИКАННЯ --}}
            <div class="card set-rules">
                <div class="set-rules__head">
                    <span class="eyebrow eyebrow-xs">Правила перемикання</span>
                </div>
                <div class="set-row">
                    <div class="set-row__info">
                        <div class="set-row__title">Авто-failover</div>
                        <div class="set-row__desc">Перемикати на резерв без участі оператора, коли health-check провалює поріг.</div>
                    </div>
                    <div class="toggle {{ $failoverEnabled ? 'is-on' : '' }}" wire:click="toggleFailover" style="cursor:pointer;">
                        <span class="toggle__knob"></span>
                    </div>
                </div>
                <div class="set-row">
                    <div class="set-row__info">
                        <div class="set-row__title">Інтервал перевірки</div>
                        <div class="set-row__desc">Як часто пінгуємо активний номер. Менший інтервал = швидша реакція, більше навантаження.</div>
                    </div>
                    <div class="set-opts">
                        @foreach(['1min'=>'1 хв','5min'=>'5 хв','15min'=>'15 хв'] as $val=>$label)
                            <button class="set-opt {{ $failoverInterval===$val ? 'is-active' : '' }}"
                                    wire:click="$set('failoverInterval','{{ $val }}')">{{ $label }}</button>
                        @endforeach
                    </div>
                </div>
                <div class="set-row">
                    <div class="set-row__info">
                        <div class="set-row__title">Поріг провалів</div>
                        <div class="set-row__desc">Скільки невдалих перевірок підряд має статись перед перемиканням.</div>
                    </div>
                    <div class="set-opts">
                        @foreach([2,3,5,10] as $t)
                            <button class="set-opt {{ $failoverThreshold===$t ? 'is-active' : '' }}"
                                    wire:click="$set('failoverThreshold',{{ $t }})">{{ $t }}</button>
                        @endforeach
                        <span style="font:12px var(--font-sans);color:var(--ink-5);padding-left:4px;">підряд</span>
                    </div>
                </div>
                <div class="set-row">
                    <div class="set-row__info">
                        <div class="set-row__title">Сповіщення</div>
                        <div class="set-row__desc">Слати email + webhook коли спрацював failover.</div>
                    </div>
                    <div class="toggle is-on" style="cursor:pointer;">
                        <span class="toggle__knob"></span>
                    </div>
                </div>
                <div class="set-rules__foot">
                    <button class="btn btn-primary btn-sm" wire:click="saveFailover">Зберегти</button>
                </div>
            </div>

            {{-- ЖУРНАЛ: дві вкладки --}}
            <div class="card set-journal">

                {{-- Tabs --}}
                <div class="set-jtabs">
                    <button class="set-jtab" :class="jTab==='log'   ? 'is-active' : ''" @click="jTab='log'">Журнал</button>
                    <button class="set-jtab" :class="jTab==='queue' ? 'is-active' : ''" @click="jTab='queue'">Черга номерів</button>
                </div>

                {{-- Tab: Журнал перемикань --}}
                <div x-show="jTab==='log'">
                    <div class="set-journal__head">
                        <div>
                            <span class="eyebrow eyebrow-xs">Журнал перемикань</span>
                            <span class="set-journal__meta">5 подій &middot; 30 днів</span>
                        </div>
                        <button class="btn btn-secondary btn-xs">Експорт CSV</button>
                    </div>
                    <div class="set-jrow set-jrow--head">
                        <span>З / НА</span>
                        <span>Причина / Коли</span>
                    </div>
                    @foreach([
                        ['geo'=>false,'from'=>'+48 00 000 00 00','to'=>'+48 99 999 99 99','type'=>'manual','cause'=>'manual · SIM block','when'=>'13 трав 18:54','ok'=>true],
                        ['geo'=>false,'from'=>'11111111111',      'to'=>'+099 11 22 33',   'type'=>'auto',  'cause'=>'auto · 5xx · 3/3','when'=>'10 трав 03:22','ok'=>true],
                        ['geo'=>false,'from'=>'+48 22 555 33 11', 'to'=>'+48 71 222 11 00','type'=>'auto',  'cause'=>'auto · timeout · 3/3','when'=>'08 трав 21:09','ok'=>true],
                        ['geo'=>true, 'from'=>'@demo_main',       'to'=>'@demo_support',  'type'=>'manual','cause'=>'manual · перевірка','when'=>'05 трав 12:40','ok'=>true],
                        ['geo'=>false,'from'=>'+38 099 11 22 33', 'to'=>'+38 073 000 11 22','type'=>'auto', 'cause'=>'auto · no-answer','when'=>'02 трав 09:18','ok'=>false],
                    ] as $row)
                    <div class="set-jrow">
                        <div>
                            <div class="set-jfrom">
                                @if($row['geo'])<span style="font-size:13px;">🌐</span>@endif
                                {{ $row['from'] }}
                                <span class="set-jarrow">→</span>
                            </div>
                            <div class="set-jto">{{ $row['to'] }}</div>
                        </div>
                        <div>
                            <div style="margin-bottom:2px;">
                                <span class="set-jbadge set-jbadge--{{ $row['type'] }}">{{ strtoupper($row['type']) }}</span>
                                <span class="set-jcause">{{ $row['cause'] }}</span>
                            </div>
                            <div class="set-jwhen">{{ $row['when'] }}
                                @if($row['ok'])
                                    <span class="set-jrollback">&#10003; rollback</span>
                                @else
                                    <span style="color:var(--ink-4);">Rollback</span>
                                @endif
                            </div>
                        </div>
                    </div>
                    @endforeach
                </div>

                {{-- Tab: Черга номерів --}}
                <div x-show="jTab==='queue'">
                    <div class="set-journal__head">
                        <span class="eyebrow eyebrow-xs">Черга номерів</span>
                        <span class="set-journal__meta">поточний стан</span>
                    </div>
                    {{-- Групи: кожен активний + його резерви --}}
                    <div x-show="groups.length === 0" class="ctable__empty">Черга порожня: немає активних телефонів.</div>
                    <template x-for="(group, gi) in groups" :key="gi">
                        <div class="set-qgroup">
                            <template x-for="(item, ii) in group.items" :key="item.id">
                                <div style="display:contents">
                                    {{-- Flow-connector між рядками --}}
                                    <div x-show="ii > 0" class="set-qflow">
                                        <span class="set-qflow__arrow">↓</span>
                                        <span class="set-qflow__text"
                                              x-text="item.status === 'waiting' ? 'в очікуванні (ручний тригер)' : 'не відповідає — стане активним:'"></span>
                                    </div>
                                    {{-- Рядок номера --}}
                                    <div class="set-qrow"
                                         :class="{
                                           'set-qrow--active':  item.status==='active',
                                           'set-qrow--waiting': item.status==='waiting',
                                           'set-qrow--child':   ii > 0
                                         }">
                                        <span class="set-qbadge"
                                              :class="{
                                                'set-qbadge--active':  item.status==='active',
                                                'set-qbadge--reserve': item.status==='reserve',
                                                'set-qbadge--waiting': item.status==='waiting'
                                              }"
                                              x-text="item.label"></span>
                                        <span class="set-qnum"
                                              :style="item.status==='waiting' ? 'text-decoration:line-through;color:var(--ink-5)' : ''"
                                              x-text="item.num"></span>
                                        <span class="set-qgeo" x-text="item.geo"></span>
                                        <button x-show="item.status==='active' && group.items.some(n => n.status==='reserve')"
                                                @click="doTrigger(gi)"
                                                class="set-qtrigger">&#x26A1; Тригер</button>
                                        <button x-show="item.status==='waiting'"
                                                @click="doRollback(gi, item.id)"
                                                class="set-qrollback">&#x21A9; Відновити</button>
                                    </div>
                                </div>
                            </template>
                        </div>
                    </template>
                </div>

            </div>

        </div>
    </div>

    {{-- Категорії --}}
    <div x-show="settingsSub==='categories'" x-cloak class="set-section">
        <div class="eyebrow eyebrow-xs" style="margin-bottom:10px;">02 &middot; Категорії даних</div>
        <h3 class="set-title">Що саме зберігаємо для цього сайту</h3>
        <p class="set-lead">Вимкнені категорії зникають із вкладки «Дані» — менше візуального шуму. Телефони та месенджери обов'язкові.</p>
        <div class="card" style="overflow:hidden;">
            @foreach([
                ['id'=>'phones','label'=>'Телефони','n'=>$phoneCount,'req'=>true],
                ['id'=>'messengers','label'=>'Месенджери','n'=>$msgCount,'req'=>true],
                ['id'=>'prices','label'=>'Ціни','n'=>$priceCount,'req'=>false],
                ['id'=>'addresses','label'=>'Адреси','n'=>$addressCount,'req'=>false],
                ['id'=>'socials','label'=>'Соц. мережі','n'=>$socialCount,'req'=>false],
                ['id'=>'custom','label'=>'Custom','n'=>0,'req'=>false],
            ] as $cat)
                @php $isOn = in_array($cat['id'], $dataCategories, true); @endphp
                <div class="cat-row">
                    <div class="cat-row__name">
                        <span class="cat-label">{{ $cat['label'] }}</span>
                        @if($cat['req'])<span class="pill pill--req">обов'язкове</span>@endif
                    </div>
                    <span class="mono cat-count">{{ $cat['n'] }}</span>
                    @if($cat['req'])
                        <span class="cat-lock"><x-icon.lock width="14" height="14" /></span>
                    @else
                        <span class="cat-toggle-wrap">
                            <span class="toggle-sm {{ $isOn ? 'is-on' : '' }}"
                                  wire:click="toggleDataCategory('{{ $cat['id'] }}')"
                                  title="{{ $isOn ? 'Сховати з вкладки Дані' : 'Показати у вкладці Дані' }}"
                                  style="cursor:pointer;">
                                <span class="toggle-sm__knob"></span>
                            </span>
                        </span>
                    @endif
                </div>
            @endforeach
        </div>
    </div>

    {{-- General --}}
    <div x-show="settingsSub==='general'" x-cloak class="set-section">
        <div class="eyebrow eyebrow-xs" style="margin-bottom:10px;">03 &middot; Загальне</div>
        @php
            $siteStatusLabel = match($site->status) {
                'active' => 'Активний',
                'maintenance' => 'Пауза',
                default => 'Помилка',
            };
            $siteStatusMeta = match($site->status) {
                'active' => 'Сайт працює у штатному режимі.',
                'maintenance' => 'Сайт призупинено вручну.',
                default => 'Сайт позначено як проблемний.',
            };
        @endphp
        <div class="card" style="overflow:hidden; margin-bottom:18px;">
            <div class="set-row">
                <div class="set-row__info">
                    <div class="set-row__title">Стан сайту</div>
                    <div class="set-row__desc">Поточний стан: {{ $siteStatusLabel }}. {{ $siteStatusMeta }}</div>
                </div>
                <div class="set-opts">
                    <button class="set-opt {{ $site->status === 'active' ? 'is-active' : '' }}"
                            wire:click="requestSetSiteStatus('active')"
                            @disabled($site->status === 'active')>Активний</button>
                    <button class="set-opt {{ $site->status === 'maintenance' ? 'is-active' : '' }}"
                            wire:click="requestSetSiteStatus('maintenance')"
                            @disabled($site->status === 'maintenance')>Пауза</button>
                </div>
            </div>
            @can('delete', $site)
                <div class="set-row">
                    <div class="set-row__info">
                        <div class="set-row__title">Видалення сайту</div>
                        <div class="set-row__desc">Видалення потребує підтвердження і прибере сайт зі списку.</div>
                    </div>
                    <button class="btn btn-danger btn-sm" wire:click="requestDeleteSite">
                        <x-icon.trash width="13" height="13" /> Видалити сайт
                    </button>
                </div>
            @endcan
        </div>
        <h3 class="set-title" style="margin-top:24px;">API доступ</h3>
        <div class="card api-card">
            <x-icon.key width="18" height="18" style="color:var(--ink-5);" />
            <span class="mono api-key">db_live_{{ substr(md5($site->id.'key'),0,10) }}…</span>
            <span class="mono api-status">активний</span>
            <button class="btn btn-secondary btn-sm">Перегенерувати</button>
        </div>
    </div>
</div>
