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
