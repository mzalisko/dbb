<div style="flex:1; display:flex; flex-direction:column; overflow-y:auto;" x-data="{ section: 'countries' }">
    <x-ui.topbar :crumbs="['Налаштування']" />

    <x-ui.page-head
        title="Налаштування"
        sub="Workspace-параметри, довідники, інтеграції." />

    <div style="padding:0 40px 64px; display:grid; grid-template-columns:240px 1fr; gap:40px;">

        {{-- Left nav --}}
        <nav style="display:flex; flex-direction:column; gap:2px; align-self:flex-start; position:sticky; top:0;">
            @foreach ([
                ['k' => 'countries',  'l' => 'Довідник країн',   'd' => 'PL, UA, DE, …'],
                ['k' => 'webhooks',   'l' => 'Webhooks',          'd' => '3 активних'],
                ['k' => 'api',        'l' => 'API ключі',          'd' => 'Tokens, scopes'],
            ] as $s)
                <button @click="section = '{{ $s['k'] }}'" style="
                    display:flex; align-items:flex-start; gap:10px;
                    padding:10px 12px; border-radius:4px; text-align:left; cursor:pointer;
                    background:transparent; border:1px solid transparent; transition:all .12s;"
                    :style="section === '{{ $s['k'] }}' ? 'background:var(--card); border-color:var(--ink-3); color:var(--ink-9);' : 'color:var(--ink-5);'">
                    <div style="flex:1;">
                        <div style="font:13.5px var(--font-sans);">{{ $s['l'] }}</div>
                        <div style="margin-top:3px; font:11px var(--font-mono); color:var(--ink-4);">{{ $s['d'] }}</div>
                    </div>
                    <x-icon.arrow width="12" height="12" x-show="section === '{{ $s['k'] }}'" style="margin-top:2px; color:var(--ink-4);" />
                </button>
            @endforeach
        </nav>

        {{-- Content area --}}
        <div>

            {{-- Countries --}}
            <div x-show="section === 'countries'">
                <header style="display:flex; align-items:flex-end; margin-bottom:24px;">
                    <div style="flex:1;">
                        <h2 style="font:400 24px/1 var(--font-sans); color:var(--ink-9);">Довідник країн</h2>
                        <p style="margin-top:8px; font:13.5px var(--font-sans); color:var(--ink-5);">Використовується для гео-правил. ISO-2 + dial code.</p>
                    </div>
                    <button class="btn btn-primary btn-sm"><x-icon.plus width="13" height="13" /> Додати країну</button>
                </header>
                <div class="card" style="overflow:hidden;">
                    <div style="display:grid; grid-template-columns:60px 1fr 90px 110px 70px; gap:16px; padding:12px 18px; background:var(--paper-2); border-bottom:1px solid var(--ink-3);">
                        @foreach (['', 'Назва', 'ISO-2', 'Dial code', ''] as $h)
                            <span class="eyebrow" style="font-size:10px;">{{ $h }}</span>
                        @endforeach
                    </div>
                    @foreach ([
                        ['🇵🇱', 'Польща',           'PL', '+48'],
                        ['🇺🇦', 'Україна',           'UA', '+380'],
                        ['🇩🇪', 'Німеччина',         'DE', '+49'],
                        ['🇺🇸', 'США',               'US', '+1'],
                        ['🇬🇧', 'Велика Британія',   'GB', '+44'],
                        ['🇫🇷', 'Франція',           'FR', '+33'],
                    ] as $i => $c)
                        <div style="display:grid; grid-template-columns:60px 1fr 90px 110px 70px; gap:16px; padding:14px 18px; border-top:{{ $i ? '1px solid var(--ink-3)' : 'none' }}; align-items:center;">
                            <span style="font:22px var(--font-sans);">{{ $c[0] }}</span>
                            <span style="font:14px var(--font-sans); color:var(--ink-9);">{{ $c[1] }}</span>
                            <span class="mono" style="font:13px var(--font-mono); color:var(--ink-7); letter-spacing:.06em;">{{ $c[2] }}</span>
                            <span class="mono" style="font:13px var(--font-mono); color:var(--ink-7);">{{ $c[3] }}</span>
                            <div style="display:flex; justify-content:flex-end; gap:4px; color:var(--ink-4);">
                                <button style="width:24px; height:24px; cursor:pointer;"><x-icon.edit width="13" height="13" /></button>
                                <button style="width:24px; height:24px; cursor:pointer;"><x-icon.trash width="13" height="13" /></button>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>

            {{-- Webhooks --}}
            <div x-show="section === 'webhooks'">
                <h2 style="font:400 24px/1 var(--font-sans); color:var(--ink-9); margin-bottom:24px;">Webhooks</h2>
                <div class="card" style="overflow:hidden;">
                    @foreach (['phone.created', 'phone.failover', 'site.error'] as $i => $e)
                        <div style="display:grid; grid-template-columns:1fr auto auto; gap:12px; padding:14px 18px; {{ $i ? 'border-top:1px solid var(--ink-3);' : '' }} align-items:center;">
                            <div>
                                <div class="mono" style="font:13px var(--font-mono); color:var(--ink-9);">{{ $e }}</div>
                                <div class="mono" style="margin-top:3px; font:11.5px var(--font-mono); color:var(--ink-5);">https://hooks.acme.com/db/{{ explode('.', $e)[0] }}</div>
                            </div>
                            <span style="width:32px; height:18px; border-radius:999px; background:var(--ink-9); position:relative; display:inline-block; cursor:pointer;">
                                <span style="position:absolute; top:2px; left:16px; width:14px; height:14px; border-radius:999px; background:var(--paper);"></span>
                            </span>
                            <button style="color:var(--ink-4); cursor:pointer;"><x-icon.trash width="13" height="13" /></button>
                        </div>
                    @endforeach
                </div>
            </div>

            {{-- API keys --}}
            <div x-show="section === 'api'">
                <h2 style="font:400 24px/1 var(--font-sans); color:var(--ink-9); margin-bottom:24px;">API ключі</h2>
                <div class="card" style="overflow:hidden;">
                    @foreach ([['Production server', 'db_prod_2K7sX9m...', '1 хв'], ['CI bot', 'db_ci_4M8sX2k...', 'вчора']] as $i => $t)
                        <div style="display:grid; grid-template-columns:1fr 1fr 100px auto; gap:12px; padding:16px 18px; {{ $i ? 'border-top:1px solid var(--ink-3);' : '' }} align-items:center;">
                            <div>
                                <div style="font:13px var(--font-sans); color:var(--ink-9);">{{ $t[0] }}</div>
                                <div class="mono" style="margin-top:3px; font:12px var(--font-mono); color:var(--ink-5);">{{ $t[1] }}</div>
                            </div>
                            <span style="font:12px var(--font-sans); color:var(--ink-5);">Останнє: {{ $t[2] }}</span>
                            <button class="btn btn-secondary btn-sm">Копіювати</button>
                            <button class="btn btn-ghost btn-sm" style="color:var(--bad);">Відкликати</button>
                        </div>
                    @endforeach
                </div>
            </div>

        </div>
    </div>
</div>
