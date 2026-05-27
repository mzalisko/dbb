{{-- ─── Tab: Налаштування ───────────────────────────────── --}}
<div x-show="tab==='settings'" class="tab-pane" style="padding-top:24px;">
    {{-- Sub-tabs --}}
    <div class="tabs sub-tabs">
        @foreach([
            ['key'=>'failover','label'=>'Failover','count'=>$phonePrimaries->count()],
            ['key'=>'categories','label'=>'Категорії даних','count'=>6],
            ['key'=>'api','label'=>'API доступ','count'=>1],
        ] as $st)
            <button class="tab" :class="settingsSub==='{{ $st['key'] }}' ? 'active' : ''" @click="settingsSub='{{ $st['key'] }}'">
                {{ $st['label'] }}
                <span class="tab-n">{{ $st['count'] }}</span>
            </button>
        @endforeach
    </div>

    {{-- Failover --}}
    <div x-show="settingsSub==='failover'" class="set-section">
        <div class="eyebrow eyebrow-xs" style="margin-bottom:10px;">01 &middot; Failover</div>
        <h3 class="set-title set-title--lead">SIM-керування та автоматичне перемикання</h3>
        <p class="set-lead">
            Якщо головний номер не відповідає — система перемикає на резерв за порядком (#1, #2, …). Гео-правила резерву мають збігатися з головним.
        </p>

        {{-- Stats — повна ширина --}}
        <div class="card set-stats">
            @foreach([
                ['l'=>'Статус','v'=>'<span class="set-stat__status"><span class="role-dot" style="background:var(--ok);"></span>Стабільно</span>','m'=>'усі головні відповідають'],
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
                    <button class="btn btn-sm btn-danger-fill">&#x26A1; Тригер вручну</button>
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
                        <div class="set-row__desc">Як часто пінгуємо головний номер. Менший інтервал = швидша реакція, більше навантаження.</div>
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
                        <span style="font:12px var(--font-sans);color:var(--ink-5);align-self:center;padding-left:4px;">підряд</span>
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

            {{-- ЖУРНАЛ ПЕРЕМИКАНЬ --}}
            <div class="card set-journal">
                <div class="set-journal__head">
                    <div style="display:flex;align-items:baseline;gap:0;">
                        <span class="eyebrow eyebrow-xs">Журнал перемикань</span>
                        <span class="set-journal__meta">5 подій &middot; 30 днів</span>
                    </div>
                    <button class="btn btn-secondary btn-xs">Експорт CSV</button>
                </div>
                {{-- Header row --}}
                <div class="set-jrow set-jrow--head">
                    <span></span>
                    <span>З / НА</span>
                    <span></span>
                    <span>Причина / Коли</span>
                </div>
                @foreach([
                    ['geo'=>false,'from'=>'+48 00 000 00 00','to'=>'+48 99 999 99 99','type'=>'manual','cause'=>'manual · SIM block','when'=>'13 трав 18:54','ok'=>true],
                    ['geo'=>false,'from'=>'11111111111','to'=>'+099 11 22 33','type'=>'auto','cause'=>'auto · 5xx · 3/3','when'=>'10 трав 03:22','ok'=>true],
                    ['geo'=>false,'from'=>'+48 22 555 33 11','to'=>'+48 71 222 11 00','type'=>'auto','cause'=>'auto · timeout · 3/3','when'=>'08 трав 21:09','ok'=>true],
                    ['geo'=>true,'from'=>'@demo_main','to'=>'@demo_support','type'=>'manual','cause'=>'manual · перевірка','when'=>'05 трав 12:40','ok'=>true],
                    ['geo'=>false,'from'=>'+38 099 11 22 33','to'=>'+38 073 000 11 22','type'=>'auto','cause'=>'auto · no-answer','when'=>'02 трав 09:18','ok'=>false],
                ] as $row)
                <div class="set-jrow">
                    <span>
                        @if($row['geo'])<span style="font-size:14px;">🌐</span>@endif
                    </span>
                    <div>
                        <div class="set-jfrom">{{ $row['from'] }}</div>
                        <div class="set-jto">{{ $row['to'] }}</div>
                    </div>
                    <span class="set-jarrow">→</span>
                    <div>
                        <div style="margin-bottom:3px;">
                            <span class="set-jbadge set-jbadge--{{ $row['type'] }}">{{ strtoupper($row['type']) }}</span>
                            <span style="font:11px var(--font-mono);color:var(--ink-5);">{{ $row['cause'] }}</span>
                        </div>
                        <div class="set-jrow-time">{{ $row['when'] }}
                            @if($row['ok'])
                                &nbsp;<span class="set-jrollback">&#10003; rollback</span>
                            @else
                                &nbsp;<span style="font:11px var(--font-mono);color:var(--ink-4);">Rollback</span>
                            @endif
                        </div>
                    </div>
                </div>
                @endforeach
            </div>

        </div>
    </div>

    {{-- Категорії --}}
    <div x-show="settingsSub==='categories'" class="set-section">
        <div class="eyebrow eyebrow-xs" style="margin-bottom:10px;">02 &middot; Категорії даних</div>
        <h3 class="set-title">Що саме зберігаємо для цього сайту</h3>
        <div class="card" style="overflow:hidden;">
            @foreach([
                ['id'=>'phones','label'=>'Телефони','n'=>$phoneCount,'req'=>true],
                ['id'=>'messengers','label'=>'Месенджери','n'=>$msgCount,'req'=>true],
                ['id'=>'prices','label'=>'Ціни','n'=>$priceCount,'req'=>false],
                ['id'=>'addresses','label'=>'Адреси','n'=>$addressCount,'req'=>false],
                ['id'=>'socials','label'=>'Соц. мережі','n'=>$socialCount,'req'=>false],
                ['id'=>'custom','label'=>'Custom','n'=>0,'req'=>false],
            ] as $cat)
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
                            <span class="toggle-sm {{ $cat['n']>0 ? 'is-on' : '' }}"><span class="toggle-sm__knob"></span></span>
                        </span>
                    @endif
                </div>
            @endforeach
        </div>
    </div>

    {{-- API --}}
    <div x-show="settingsSub==='api'" class="set-section">
        <div class="eyebrow eyebrow-xs" style="margin-bottom:10px;">03 &middot; API доступ</div>
        <h3 class="set-title">Ключ цього сайту</h3>
        <div class="card api-card">
            <x-icon.key width="18" height="18" style="color:var(--ink-5);" />
            <span class="mono api-key">db_live_{{ substr(md5($site->id.'key'),0,10) }}…</span>
            <span class="mono api-status">активний</span>
            <button class="btn btn-secondary btn-sm">Перегенерувати</button>
        </div>
    </div>
</div>
