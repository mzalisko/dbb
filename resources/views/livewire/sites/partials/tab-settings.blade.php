{{-- ─── Tab: Налаштування ───────────────────────────────── --}}
<div x-show="tab==='settings'" x-cloak class="tab-pane" style="padding-top:24px;">
    {{-- Sub-tabs --}}
    <div class="tabs sub-tabs">
        @foreach([
            ['key'=>'failover','label'=>'Failover','count'=>$phonePrimaries->count()],
            ['key'=>'categories','label'=>'Категорії даних','count'=>count($visibleDataCategories)],
            ['key'=>'general','label'=>'Загальне','count'=>3],
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
            $reserves = $primary->backups->where('visible', true)->sortBy('order')->values();
            // A number with no reserves isn't part of the failover queue — skip it.
            if ($reserves->isEmpty()) {
                continue;
            }
            // The canonical (original) primary keeps the dot even after a failover.
            $anchorId = $primary->failover_anchor_id ?? $primary->id;
            $geo = $primary->preview_geo_label ?? (($primary->geo_mode === 'all') ? 'ALL' : '');
            $group = ['anchorEntryId' => $anchorId, 'items' => [
                ['id' => 'p' . $primary->id, 'entryId' => $primary->id, 'num' => $primary->value ?? '—', 'geo' => $geo, 'status' => 'active', 'label' => 'АКТИВНИЙ', 'anchor' => ($primary->id === $anchorId)],
            ]];
            $rn = 1;
            foreach ($reserves as $backup) {
                $bgeo = $backup->preview_geo_label ?? $geo;
                $group['items'][] = ['id' => 'b' . $backup->id, 'entryId' => $backup->id, 'num' => $backup->value ?? '—', 'geo' => $bgeo, 'status' => 'reserve', 'label' => 'РЕЗЕРВ ' . $rn++, 'anchor' => ($backup->id === $anchorId)];
            }
            // Rollback target only exists while the original primary is still around.
            $group['anchorPresent'] = collect($group['items'])->contains('anchor', true);
            $queueGroups[] = $group;
        }
    @endphp
    <div x-show="settingsSub==='failover'" x-cloak class="set-section">
        <div class="eyebrow eyebrow-xs" style="margin-bottom:10px;">01 &middot; Failover</div>
        <h3 class="set-title set-title--lead">SIM-керування та автоматичне перемикання</h3>
        <p class="set-lead">
            Якщо активний номер не відповідає — система перемикає на резерв за порядком (#1, #2, …). Гео-правила резерву успадковуються від батьківського номера.
        </p>

        {{-- Stats — повна ширина --}}
        @php
            $latestFailover = $failoverLogs->first();
            $latestFailoverProps = $latestFailover?->properties ?? [];
            $latestFailoverMode = $latestFailoverProps['mode'] ?? null;
        @endphp
        <div class="card set-stats">
            @foreach([
                ['l'=>'Статус','v'=>'<span class="set-stat__status"><span class="role-dot" style="background:var(--ok);"></span>Стабільно</span>','m'=>'усі активні відповідають'],
                ['l'=>'Активних правил','v'=>'4','m'=>'у 2 гео-пулах'],
                ['l'=>'Резервів','v'=>(string)$phonePrimaries->flatMap->backups->count(),'m'=>'у середньому 1.25 / пул'],
                ['l'=>'Останній failover','v'=>$latestFailover?->created_at?->format('d M') ?? '—','m'=>$latestFailover ? (($latestFailoverMode ?? 'manual') . ' · журнал') : 'подій ще немає'],
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
                <div class="set-rules__foot">
                    <button class="btn btn-primary btn-sm" wire:click="saveFailover">Зберегти</button>
                </div>
            </div>

            {{-- ЧЕРГА НОМЕРІВ — server-rendered, єдине джерело правди = БД --}}
            <div class="card set-journal">
                <div class="set-journal__head">
                    <span class="eyebrow eyebrow-xs">Черга номерів</span>
                    <span class="set-journal__meta">поточний стан · журнал на вкладці «Активність»</span>
                </div>

                @forelse($queueGroups as $group)
                    @php $items = $group['items']; $active = $items[0]; $reserves = array_slice($items, 1); @endphp
                    <div class="set-qgroup">
                        <div class="set-qrow set-qrow--active">
                            <span class="role-dot" style="flex-shrink:0; background:{{ $active['anchor'] ? 'var(--ink-9)' : 'transparent' }};"
                                  title="{{ $active['anchor'] ? 'Першочерговий (основний) номер' : '' }}"></span>
                            <span class="set-qbadge set-qbadge--active">{{ $active['label'] }}</span>
                            <span class="set-qnum">{{ $active['num'] }}</span>
                            <span class="set-qgeo">{{ $active['geo'] }}</span>
                            {{-- Failover в дію: перемкнути на перший резерв --}}
                            @if(count($reserves))
                                <button wire:click="triggerFailover({{ $active['entryId'] }}, {{ $reserves[0]['entryId'] }})"
                                        wire:confirm="Перемкнути активний номер на «{{ $reserves[0]['num'] }}»?"
                                        class="set-qtrigger">&#x26A1; Тригер</button>
                            @endif
                            {{-- Ролбек: повернути першочерговий (основний) номер, якщо зараз активний резерв --}}
                            @if(! $active['anchor'] && $group['anchorPresent'])
                                <button wire:click="restoreFailover({{ $active['entryId'] }}, {{ $group['anchorEntryId'] }})"
                                        wire:confirm="Повернути першочерговий номер активним?"
                                        class="set-qrollback">&#x21A9; Відновити</button>
                            @endif
                        </div>
                        @foreach($reserves as $i => $r)
                            <div class="set-qflow">
                                <span class="set-qflow__arrow">↓</span>
                                <span class="set-qflow__text">{{ $i === 0 ? 'не відповідає — стане активним:' : 'наступний у черзі' }}</span>
                            </div>
                            <div class="set-qrow set-qrow--child">
                                <span class="role-dot" style="flex-shrink:0; background:{{ $r['anchor'] ? 'var(--ink-9)' : 'transparent' }};"
                                      title="{{ $r['anchor'] ? 'Першочерговий (основний) номер' : '' }}"></span>
                                <span class="set-qbadge set-qbadge--reserve">{{ $r['label'] }}</span>
                                <span class="set-qnum">{{ $r['num'] }}</span>
                                <span class="set-qgeo">{{ $r['geo'] }}</span>
                            </div>
                        @endforeach
                    </div>
                @empty
                    <div class="ctable__empty">Черга порожня: немає активних телефонів.</div>
                @endforelse
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
                @continue(! in_array($cat['id'], $visibleDataCategories, true))
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
                    <div class="set-row__title">Назва сайту</div>
                    <div class="set-row__desc">Зміна назви не змінює ID сайту, доступи, ключі чи прив'язані дані.</div>
                </div>
                <div style="display:flex; align-items:flex-start; gap:8px; min-width:min(360px, 100%);">
                    <div class="field" style="margin:0; flex:1;">
                        <input type="text" class="input" wire:model="siteName" wire:keydown.enter="updateSiteName" />
                        @error('siteName') <span class="field-error">{{ $message }}</span> @enderror
                    </div>
                    <button class="btn btn-primary btn-sm" wire:click="updateSiteName">
                        <x-icon.save width="13" height="13" /> Зберегти
                    </button>
                </div>
            </div>
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
            <div class="set-row">
                <div class="set-row__info">
                    <div class="set-row__title">Група сайту</div>
                    <div class="set-row__desc">Поточна група: {{ $site->group ? ucfirst($site->group) : 'без групи' }}.</div>
                </div>
                <div class="group-select" x-data="{ open: false }" @click.outside="open = false">
                    <button type="button" class="group-select__button" @click="open = !open">
                        @if($site->group)
                            <span class="pill-dot" style="background:{{ $site->group_color ?? '#a39d8c' }};"></span>
                            <span>{{ ucfirst($site->group) }}</span>
                        @else
                            <span>Оберіть групу</span>
                        @endif
                        <x-icon.chevron-down width="13" height="13" />
                    </button>
                    <div class="dropdown group-select__dropdown" x-show="open" x-cloak>
                        @foreach($siteGroups as $group)
                            <button type="button"
                                    class="pill-menu-item {{ $site->group === $group->name ? 'is-active' : '' }}"
                                    wire:click="setSiteGroup({{ $group->id }})"
                                    @click="open = false">
                                <span class="pill-dot" style="background:{{ $group->color }};"></span>
                                <span>{{ ucfirst($group->name) }}</span>
                                @if($site->group === $group->name)
                                    <x-icon.check width="12" height="12" class="group-select__check" />
                                @endif
                            </button>
                        @endforeach
                    </div>
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
        @php $siteApiKey = $site->api_key; @endphp
        <div class="card api-card"
             x-data="{
                 copied: false,
                 key: @js($siteApiKey),
                 copyKey() {
                     if (navigator.clipboard) {
                         navigator.clipboard.writeText(this.key).catch(() => {});
                     }
                     this.copied = true;
                     setTimeout(() => this.copied = false, 1400);
                 }
             }">
            <x-icon.key width="18" height="18" style="color:var(--ink-5);" />
            <span class="mono api-key">{{ $siteApiKey }}…</span>
            <span class="mono api-status">активний</span>
            <button class="btn btn-secondary btn-sm"
                    type="button"
                    @click="copyKey()"
                    :title="copied ? 'Скопійовано' : 'Скопіювати ключ'">
                <x-icon.copy width="13" height="13" />
                <span x-text="copied ? 'Скопійовано' : 'Копіювати'"></span>
            </button>
            <button class="btn btn-secondary btn-sm" type="button" wire:click="regenerateApiKey">Перегенерувати</button>
        </div>
    </div>
</div>
